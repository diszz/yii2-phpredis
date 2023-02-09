<?php
namespace diszz\phpredis;

use Yii;
use yii\console\Controller;
//use common\helpers\ProcessHelper;
use common\models\DatasetTrainUserData;

class QueueController extends Controller
{
    /**
     * 启动进程
     *
     * php ./yii mq/start
     */
    public function actionStart()
    {
        $_logs = [];
        
        $queue = Yii::$app->queue;
        
        foreach ($queue->channels as $channel => $maxProcess) {
            
            $_logs['$channel'] = $channel;
            $_logs['$maxProcess'] = $maxProcess;
            
            if (is_numeric($channel)) {
                $channel = $maxProcess;
                $maxProcess = 10;
            }
            $_logs['$channel1'] = $channel;
            $_logs['$maxProcess1'] = $maxProcess;
            
            // $cmd_keys = [dirname(Yii::getAlias('@app')), $queue->consoleListenAction, $channel];
            // $processCount = ProcessHelper::ps($cmd_keys);
            // $_logs['$processCount'] = $processCount;
            
            // if ($processCount >= $maxProcess) {
            //     Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $processCount>$maxProcess ' . json_encode($_logs));
            //     continue;
            // }
            
            //获取消费者数量与队列剩余消息数
            $messageCount = $queue->getMessageCount($channel);
            $_logs['$messageCount'] = $messageCount;
            //$_logs['consumerMaxCount'] = $this->consumerMaxCount;
            
            //最大进程数不能大于10
            /*
             if ($consumerCount >= $this->consumerMaxCount)
             {
             Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $consumerCount exist ' . json_encode($_logs));
             return false;
             }*/
            
            $consoleFile = dirname(Yii::getAlias('@common')) . '/yii';
            $phpbin = $queue->phpbin;
            $queueDivisor = 300;
            
            $_logs['$consoleFile'] = $consoleFile;
            $_logs['$phpbin'] = $phpbin;
            
            $scriptNum = 0;
            if ($queueDivisor != 0) {
                //四舍五入取整
                $scriptNum = round($messageCount / $queueDivisor);
            }
            
            //最少开启一个监听进程 最多开启9个监听进程
            $scriptNum = ($scriptNum < 1) ? 1 : (($scriptNum < $maxProcess) ? $scriptNum : $maxProcess);
            $_logs['$scriptNum'] = $scriptNum;
            
            //需要启动的进程数<=当前进程数, 为保证始终存在进程, 所以每次都启动, 此处注释
            /*
            if ($scriptNum <= $consumerCount) {
                Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $scriptNum <= $consumerCount ' . json_encode($_logs));
                return false;
            }*/
                
            //循环启动监听进程
            for ($i = 0; $i < $scriptNum; $i++) {
                //执行数据导出脚本
                // nohup /usr/local/php/bin/php /www/saas.xx.com/api/yii mq/work site/pack > /dev/null 2>&1 &
                $command = sprintf('nohup %s %s %s %s > /dev/null 2>&1 &', $phpbin, $consoleFile, $queue->consoleListenAction, $channel);
                @exec($command, $outPut, $return);
                $_logs['exec.$command'] = $command;
                $_logs['exec.$outPut'] = $outPut;
                $_logs['exec.$return'] = $return;
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' exec ' . json_encode($_logs));
            }
        }
        
        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
        return true;
    }
    
    /**
     * 处理业务进程
     *
     * php ./yii mq/listen
     */
    public function actionListen(string $channel){
        
        $_logs = ['$channel' => $channel];
        
        //--------------------------------------
        
        // $cmd_keys = [dirname(Yii::getAlias('@app')), 'mq/listen', $channel];
        // $_logs['$cmd_keys'] = $cmd_keys;
        // if (ProcessHelper::processIsRunning($cmd_keys, 300, 20, false))
        // {
        //     echo date('Y-m-d H:i:s')." - isRunning \n";
        //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Process isRunning '. json_encode($_logs));
        //     exit();
        // }
        
        //当前进程id
        $_logs['pid'] = getmypid();
        
        //--------------------------------------
        
        $queue = Yii::$app->queue;
        $queue->listen($channel);
        
        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ '. json_encode($_logs));
    }
    
    // php ./yii mq/stat
    public function actionStat(){
        
        $queue = Yii::$app->queue;
        
        $stats = [];
        foreach ($queue->channels as $channel => $maxProcess) {
            
            if (is_numeric($channel)) {
                $channel = $maxProcess;
                $maxProcess = 10;
            }
            
            $messageCount = $queue->getMessageCount($channel);
            
            //$cmd_keys = [dirname(Yii::getAlias('@app')), $queue->consoleListenAction, $channel];
            //$processCount = ProcessHelper::ps($cmd_keys);
            
            $stats[$channel.'.$messageCount'] = $messageCount;
            //$stats[$channel.'.$processCount'] = $processCount;
        }
        
        var_dump($stats);
    }
    
    // php ./yii mq/test
    public function actionTest() {
        
        $starttime = microtime(true);
        $_logs['starttime'] = $starttime;
        
        $usedtimeArr = [];
        for ($i = 0; $i < 1000; $i++) {
            
            $_logs['starttime1'] = microtime(true);
            
            Yii::$app->queue->publish('mq/test-func', ['starttime' => $_logs['starttime1']]);
            $_logs['endtime1'] = microtime(true);
            
            $usedtimeArr[] = $_logs['endtime1'] - $_logs['starttime1'];
        }
        
        asort($usedtimeArr);
        var_dump($usedtimeArr);
        
        $_logs['endtime'] = microtime(true);
        
        var_dump($_logs);
        
    }
    
    public static function actionTestFunc($param) {
        $_logs = ['$param' => $param];
        
        $starttime = 0;
        if (!empty($param['starttime'])) {
            $starttime = $param['starttime'];
        }
        
        $_logs['starttime'] = $starttime;
        $_logs['lasttime'] = microtime(true);
        
        $_logs['timelong'] = $_logs['lasttime'] - $_logs['starttime'];
        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
    }
    
    // php ./yii mq/test-normal
    public static function actionTestNormal() {
        
        $params = [
            'action' => 'addtoOneCacheKeyMembers',
            'attrs' => [
                'class' => 'UserRecord',
                'table' => 'user_record_2301',
                'action' => 'addtoOneCacheKeyMembers',
                'idAndKeyArr' => [1, 'aaa'],
            ]
        ];
        Yii::$app->queue->publish('mq/normal-func', $params);
    }
    
    public static function actionNormalFunc($param) {
        $_logs = ['$param' => $param];
        
        if (empty($param['action'])) {
            return ;
        }
        
        $starttime = microtime(true);
        if (!empty($param['starttime'])) {
            $starttime = $param['starttime'];
        }
        
        if ($param['action'] == 'addtoOneCacheKeyMembersMul') {
            
            if (empty($param['attrs'])) {
                return ;
            }
            
            $className = $param['attrs']['class'];
            $tableName = $param['attrs']['table'];
            $action = $param['attrs']['action'];
            $idAndKeyArr = $param['attrs']['idAndKeyArr'];
            
            if ($idAndKeyArr) {
                foreach ($idAndKeyArr as $v) {
                    list($vid, $vkey) = $v;
                    
                    if (!method_exists($className, $action)){
                        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' method_exists exception ' . json_encode($_logs));
                        continue;
                    }
                    
                    if (!method_exists($className, 'setTableName')){
                        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' method_exists exception ' . json_encode($_logs));
                        continue;
                    }
                    
                    $className::setTableName($tableName);
                    $className::$action($vid, $vkey);
                    //call_user_func_array(['common\\models\\' . $class, $action], [$vid, $vkey]);
                }
            }
        }
        
        $_logs['starttime'] = $starttime;
        $_logs['lasttime'] = microtime(true);
        
        $_logs['timelong'] = $_logs['lasttime'] - $_logs['starttime'];
        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
    }
    
    
}