<?php

namespace yii\phpredis;

use yii\base\Component;
use yii\db\Exception;
use RedisException;

/**
 * Class Connection
 * @package yii\phpredis
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
     * Redis connection
     *
     * @var	Redis
     */
    protected $_redis;
    
    /**
     * Initializes the redis Session component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     * @throws InvalidConfigException if [[redis]] is invalid.
     */
    public function init()
    {
        \Yii::trace("_redis init ", __CLASS__);
        
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
            \Yii::trace("_redis had open", __CLASS__);
            return;
        }
        
        $this->_redis = new \Redis();
        \Yii::trace("_redis new class", __CLASS__);
        
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
            \Yii::trace('_redis select '. $this->database, __CLASS__);
        }
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
        \Yii::trace('_redis call '. $name, __CLASS__);
        return call_user_func_array([$this->_redis, $name], $params);
    }
}
