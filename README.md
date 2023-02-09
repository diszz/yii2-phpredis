# yii2-phpredis
yii2框架的基于new Redis() 方式连接redis, 有效解决fgets, fwrite 操作socket方式的弊端. 支持哨兵模式!

2023年2月9号增加支持redis mq 队列功能, 增加 Queue.php 文件.

# 安装方法

1.命令安装
php composer.phar require --prefer-dist diszz/yii2-phpredis dev-master
或
composer require --prefer-dist diszz/yii2-phpredis dev-master

2.下载文件包, 
在vendor文件夹下新建文件夹diszz, 解压后复制yii2-phpredis到diszz文件夹下
结构将是如下结构

``` php
vendor\diszz\yii2-phpredis\Connection.php

```

并在vendor\yiisoft\extensions.php 文件末尾添加如下配置:

``` php
'diszz/yii2-phpredis' =>
    array (
        'name' => 'diszz/yii2-phpredis',
        'version' => '1.0.0.0',
        'alias' =>
        array (
            '@diszz/phpredis' => $vendorDir . '/diszz/yii2-phpredis',
        ),
    ),

```

# 使用方法
在main.php 或 main-local.php 配置如下信息:

``` php
<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'charset' => 'utf-8',
    'language' => 'zh-CN',
    'timeZone' => 'PRC',
    'components' => [
        'cache' => [
            //'class' => 'diszz\caching\FileCache',
            'class' => 'diszz\phpredis\Cache',
        ],
        'redis' => [
            'class' => 'diszz\phpredis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'session' => [
            'class' => 'diszz\phpredis\Session',
            // 'class' => 'diszz\web\DbSession',
            // 'db' => 'mydb',  // 数据库连接的应用组件ID，默认为'db'.
            // 'sessionTable' => 'my_session', // session 数据表名，默认为'session'.
        ],
	'queue' => [
              'class' => 'diszz\phpredis\Queue',
              'keyPrefix' => 'saas.queue:',
              'maxRunTimelong' => 299,//进程最大运行时间,单位秒
              'phpbin' => 'php',
              'consoleStartAction' => 'queue/start',//把此命令加到crontab里
              'consoleListenAction' => 'queue/listen',
              'channels' => [
                  'mq/test-func' => 2,//启动的最大进程数,最少2个
              ],
          ],
        
 ....

 ```


# 代码中使用

``` php
	$key = 'aaaaa';
        $value = 'aaaa1111';
        if (Yii::$app->cache->exists($key))
        {
            var_dump('get');
            var_dump(Yii::$app->cache->get($key));
            
        }
        else
        {
            var_dump('set');
            Yii::$app->cache->set($key, $value, 3000);
            
        }
        
        $key = 'ccc';
        $value = 'ccccc111';
        
        if (Yii::$app->session->get($key))
        {
            var_dump('get');
            var_dump(Yii::$app->session->get($key));
            
        }
        else
        {
            var_dump('set');
            Yii::$app->session->set($key, $value);
            
        }

```

``` php

//队列示例

把 QueueController.php放到console\controller文件夹内

//添加到计划任务
php ./yii queue/start

//添加业务到队列
Yii::$app->queue->publish('queue/test-func', ['starttime' => $_logs['starttime1']]);


```

# 解决了 yii2-redis 的这个bug -_-

``` php
2018-03-05 17:28:51 [-][-][-][error][yii\db\Exception] exception 'yii\db\Exception' with message 'Failed to read from socket.
Redis command was: GET b4e9e33d1c3bc2b09b4b0cecd175e8d8' in /basicfinder/www/basicfinder_web/vendor/yiisoft/yii2-redis/Connection.php:663
Stack trace:
#0 /basicfinder/www/basicfinder_web/vendor/yiisoft/yii2-redis/Connection.php(652): yii\redis\Connection->parseResponse('GET b4e9e33d1c3...')
#1 /basicfinder/www/basicfinder_web/vendor/yiisoft/yii2-redis/Cache.php(102): yii\redis\Connection->executeCommand('GET', Array)
#2 /basicfinder/www/basicfinder_web/vendor/yiisoft/yii2/caching/Cache.php(114): yii\redis\Cache->getValue('b4e9e33d1c3bc2b...')
#3 /basicfinder/www/basicfinder_web/vendor/yiisoft/yii2/db/Schema.php(137): yii\caching\Cache->get(Array)
#4 /basicfinder/www/basicfinder_web/vendor/yiisoft/yii2/db/QueryBuilder.php(247): yii\db\Schema->getTableSchema('task_item')

```

