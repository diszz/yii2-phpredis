# yii2-phpredis
yii2框架的基于new Redis() 方式连接redis, 有效解决fgets, fwrite 操作socket方式的弊端

# 安装方法
下载文件包, 在vendor文件夹下新建文件夹lihuixu, 解压后复制yii2-phpredis到lihuixu文件夹下
结构将是如下结构
``` php
vendor\lihuixu\yii2-phpredis\Connection.php

```

并在vendor\yiisoft\extensions.php 文件末尾添加如下配置:

``` php
'lihuixu/yii2-phpredis' =>
    array (
        'name' => 'lihuixu/yii2-phpredis',
        'version' => '1.0.0.0',
        'alias' =>
        array (
            '@yii/phpredis' => $vendorDir . '/lihuixu/yii2-phpredis',
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
            //'class' => 'yii\caching\FileCache',
            'class' => 'yii\phpredis\Cache',
        ],
        'redis' => [
            'class' => 'yii\phpredis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'session' => [
            'class' => 'yii\phpredis\Session',
            // 'class' => 'yii\web\DbSession',
            // 'db' => 'mydb',  // 数据库连接的应用组件ID，默认为'db'.
            // 'sessionTable' => 'my_session', // session 数据表名，默认为'session'.
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

# 再也没有出现这个报错 -_-

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

