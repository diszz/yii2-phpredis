<?php

namespace diszz\phpredis;

use yii\base\Component;
use yii\db\Exception;
use RedisException;

/**
 * 
 * [
        'class' => 'diszz\phpredis\Connection',
        'hostname' => '127.0.0.1',
        'password' => null,
        'port' => 6379,
        'database' => 0,
        'keyPrefix' => 'v3redis:'
    ],
 * 
 * Class Connection
 * @package diszz\phpredis
 */
class Connection extends Component
{
    /**
     * @var string the hostname or ip address to use for connecting to the redis server. Defaults to 'localhost'.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $hostname = 'localhost';
    /**
     * @var integer the port to use for connecting to the redis server. Default port is 6379.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $port = 6379;
    /**
     * @var string the unix socket path (e.g. `/var/run/redis/redis.sock`) to use for connecting to the redis server.
     * This can be used instead of [[hostname]] and [[port]] to connect to the server using a unix socket.
     * If a unix socket path is specified, [[hostname]] and [[port]] will be ignored.
     */
    public $unixSocket;
    /**
     * @var string the password for establishing DB connection. Defaults to null meaning no AUTH command is send.
     * See http://redis.io/commands/auth
     */
    public $password;
    /**
     * @var integer the redis database to use. This is an integer value starting from 0. Defaults to 0.
     */
    public $database = 0;
    /**
     * @var float value in seconds (optional, default is 0.0 meaning unlimited)
     */
    public $connectionTimeout = 0.0;
    
    /**
     * 缓存前缀
     * 需手动添加
     * $cackeyKey = Yii::$app->redis->buildKey($key);
     * Yii::$app->redis->exists($cackeyKey);
     * 
     * @var string
     */
    public $keyPrefix = '';
    
    /**
     * Redis connection
     *
     * @var	Redis
     */
    protected $_redis;
    
    /**
     * 是否开启哨兵模式
     * @var int
     */
    public $sentinel = 0;
    
    /**
     * 是否开启集群模式
     * @var int
     */
    public $cluster = 0;
    
    /**
     * 集群服务器配置
     * 
     * 'servers' => ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7002']
     * 
     * @var array
     */
    public $servers = [];
    
    /**
     * 集群参数
     *
     * @var array
     */
    protected $optionParam = array(
        'timeOut' => 3,
        'readTime' => 3,
        'persistent' => false //是否复用链接
    );
    
    /**
     * Initializes the redis Session component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     * @throws InvalidConfigException if [[redis]] is invalid.
     */
    public function init()
    {
        $this->open();
        
        parent::init();
    }
    
    /**
     * Returns the fully qualified name of this class.
     * @return string the fully qualified name of this class.
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws RedisException if connection fails
     */
    public function open()
    {
        if ($this->_redis !== null) {
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis had open', __CLASS__);
            return;
        }
        
        //使用哨兵模式
        if ($this->sentinel)
        {
            $this->_redis = new \Redis();
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis new class', __CLASS__);
            
            if ($this->unixSocket !== null) {
                $isConnected = $this->_redis->connect($this->unixSocket);
            } else {
                $isConnected = $this->_redis->connect($this->hostname, $this->port, $this->connectionTimeout);
            }
            
            if ($isConnected === false) {
                throw new RedisException('Connect to redis server error.');
            }
            \Yii::trace('_redis $isConnected', __CLASS__);
            
            if ($this->password !== null) {
                $this->_redis->auth($this->password);
            }
            
            if ($this->database !== null) {
                $this->_redis->select($this->database);
                \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
            }
            
            //获取主库列表及其状态信息
            $sentinelInfo = $this->_redis->rawCommand('SENTINEL', 'masters');
            //var_dump($sentinelInfo);
            //\Yii::trace('_redis select '. $this->database, __CLASS__);
            
            $masterInfo = $this->parseArrayResult($sentinelInfo);
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis $masterInfo '. json_encode($masterInfo), __CLASS__);
            //var_dump($masterInfo);
            
            if (empty($masterInfo['ip']) || empty($masterInfo['port']))
            {
                $masterInfo = current($masterInfo);
                if (empty($masterInfo['ip']) || empty($masterInfo['port']))
                {
                    \Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
                    throw new RedisException('redis conf error');
                }
            }
            
            $this->hostname = $masterInfo['ip'];
            $this->port = $masterInfo['port'];
            
            \Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis $masterInfo '. json_encode([$this->hostname, $this->port]), __CLASS__);
            
            if ($this->unixSocket !== null) {
                $isConnected = $this->_redis->connect($this->unixSocket);
            } else {
                $isConnected = $this->_redis->connect($this->hostname, $this->port, $this->connectionTimeout);
            }
            
            if ($isConnected === false) {
                throw new RedisException('Connect to redis server error.');
            }
            
            if ($this->password !== null) {
                $this->_redis->auth($this->password);
            }
            
            if ($this->database !== null) {
                $this->_redis->select($this->database);
                \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
            }
            
            \Yii::trace('_redis $isConnected', __CLASS__);
        }
        //使用集群模式
        else if ($this->cluster)
        {
            if (empty($this->servers))
            {
                \Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->servers, __CLASS__);
                throw new RedisException('redisCluster servers error');
            }
            
            $this->_redis = new \RedisCluster(
                null, 
                $this->servers, 
                $this->optionParam['timeOut'],
                $this->optionParam['readTime'],
                $this->optionParam['persistent']
            );
            
            //主从节点 读取分配策略
            //$this->_redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES);
            
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redisCluster $isConnected', __CLASS__);
        }
        //普通模式
        else 
        {
            $this->_redis = new \Redis();
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__." _redis new class", __CLASS__);
            
            if ($this->unixSocket !== null) {
                $isConnected = $this->_redis->connect($this->unixSocket);
            } else {
                $isConnected = $this->_redis->connect($this->hostname, $this->port, $this->connectionTimeout);
            }
            
            if ($isConnected === false) {
                throw new RedisException('Connect to redis server error.');
            }
            
            if ($this->password !== null) {
                $this->_redis->auth($this->password);
            }
            
            if ($this->database !== null) {
                $this->_redis->select($this->database);
                \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
            }
            
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis $isConnected', __CLASS__);
        }

        
        //------------------------------------------
    }
    
    //这个方法可以将以上sentinel返回的信息解析为数组
    public function parseArrayResult(array $data)
    {
        $result = array();
        $count = count($data);
        for ($i = 0; $i < $count;) {
            $record = $data[$i];
            if (is_array($record)) {
                $result[] = $this->parseArrayResult($record);
                $i++;
            } else {
                $result[$record] = $data[$i + 1];
                $i += 2;
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return $this->_redis->ping() === '+PONG';
    }

    public function flushdb()
    {
        return $this->_redis->flushDB();
    }
    
    public function buildKey($key)
    {
        return $this->keyPrefix . $key;
    }
    
    /**
     * Allows issuing all supported commands via magic methods.
     *
     * ```php
     * $redis->set('key1', 'val1')
     * ```
     *
     * @param string $name name of the missing method to execute
     * @param array $params method call arguments
     * @return mixed
     */
    public function __call($name, $params)
    {
        \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis call '. $name .' '. json_encode($params), __CLASS__);
        
        if (!method_exists($this->_redis, $name))
        {
            \Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis call '. $name .' '. json_encode($params), __CLASS__);
            throw new RedisException('redis conf error');
        }
        
        return call_user_func_array([$this->_redis, $name], $params);
    }
}
