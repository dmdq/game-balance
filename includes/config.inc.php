<?php
//Copy this file to config.inc.php and make changes to that file to customize your configuration.

$config = array(
  'servers' => array(
  array(
      'name' => '192.168.1.188', // Optional name.
      'host' => '192.168.1.188',
      'port' => 6379,
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
