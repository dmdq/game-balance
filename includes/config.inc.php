<?php
//Copy this file to config.inc.php and make changes to that file to customize your configuration.

define('ROOT_PATH', dirname(dirname(__FILE__)));

//数据路径
define('BALANCE_DATA_ROOT_PATH', ROOT_PATH . '/data/');
//数据导出路径
define('BALANCE_DUMP_PATH', BALANCE_DATA_ROOT_PATH . '/dumps/');
//完整数据导出路径
define('BALANCE_DUMP_PATH_PULL', BALANCE_DUMP_PATH . 'full/');
//用户数据路径
define('BALANCE_DATA_PATH_USER', BALANCE_DATA_ROOT_PATH . 'users/');
//用户数据路径
define('BALANCE_DATA_PATH_APP', BALANCE_DATA_ROOT_PATH . 'apps/');
//分区数据路径
define('BALANCE_DATA_PATH_REGION', BALANCE_DATA_ROOT_PATH . 'regions/');
//服务器数据路径
define('BALANCE_DATA_PATH_SERVER', BALANCE_DATA_ROOT_PATH . 'servers/');
//进程数据路径
define('BALANCE_DATA_PATH_PROCESS', BALANCE_DATA_ROOT_PATH . 'processes/');
//缓存根路径
define('BALANCE_CACHE_ROOT_PATH', BALANCE_DATA_ROOT_PATH . '/caches/');
//缓存分区路径
define('BALANCE_CACHE_PATH_REGION', BALANCE_CACHE_ROOT_PATH . 'regions/');
//保存文件时候的后缀名
define('BALANCE_FILE_SUFFIX', '.bdb');

//保存到redis中的前缀名
define('CONFIG_PREFIX', 'game.balance:');

define('CONFIG_USER', CONFIG_PREFIX . 'config:user:');

define('CHILDREN_USER', CONFIG_PREFIX . 'children:user:');

define('CONFIG_APP', CONFIG_PREFIX . 'config:app:');

define('CHILDREN_APP', CONFIG_PREFIX . 'children:app:');

define('CONFIG_REGION', CONFIG_PREFIX . 'config:region:');

define('CHILDREN_REGION', CONFIG_PREFIX . 'children:region:');

define('CONFIG_SERVER', CONFIG_PREFIX . 'config:server:');

define('CHILDREN_SERVER', CONFIG_PREFIX . 'children:server:');

define('CONFIG_PROCESS', CONFIG_PREFIX . 'config:process:');


$config = array(
  'servers' => array(
  array(
      'name' => '192.168.1.188', // Optional name.
      'host' => '192.168.1.188',
      'port' => 6379,
      'auth' => 'lion',
      'filter' => '*'

      // Optional Redis authentication.
      //'auth' => 'redispasswordhere' // Warning: The password is sent in plain-text to the Redis server.
    )


  

    /*array(
      'host' => 'localhost',
      'port' => 6380
    ),*/

    /*array(
      'name' => 'local db 2',
      'host' => 'localhost',
      'port' => 6379,
      'db'   => 1 // Optional database number, see http://redis.io/commands/select
      'filter' => 'something:*' // Show only parts of database for speed or security reasons
    )*/
  ),


  'seperator' => ':',


  // Uncomment to show less information and make phpRedisAdmin fire less commands to the Redis server. Recommended for a really busy Redis server.
  //'faster' => true,


  // Uncomment to enable HTTP authentication
  /*'login' => array(
    // Username => Password
    // Multiple combinations can be used
    'admin' => array(
      'password' => 'adminpassword',
    ),
    'guest' => array(
      'password' => '',
      'servers'  => array(1) // Optional list of servers this user can access.
    )
  ),*/




  // You can ignore settings below this point.

  'maxkeylen'           => 100,
  'count_elements_page' => 100
);

?>
