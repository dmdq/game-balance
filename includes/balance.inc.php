<?php
define('CONFIG_USER', 'config:user:');

define('CHILDREN_USER', 'children:user:');

define('CONFIG_APP', 'config:app:');

define('CHILDREN_APP', 'children:app:');

define('CONFIG_REGION', 'config:region:');

define('CHILDREN_REGION', 'children:region:');

define('CONFIG_SERVER', 'config:server:');

define('CHILDREN_SERVER', 'children:server:');

define('CONFIG_PROCESS', 'config:process:');

/**
 * 添加用户
 * 
 * @param userName
 *            用户名
 * @param password
 *            密码
 * @param chmod
 *            权限
 */
function addUser($userName, $password, $chmod) {
	global $config, $server, $redis;
	$map = array ();
	$code = 1;
	$key = CONFIG_USER . $userName;
	if ($redis->exists($key)) {
		$code = 1003;
	} else {
		$regTime = strval(time());
		$md5key = strtoupper(md5($userName . $password . $regTime));
		$config = array ();
		$config["name"] = $userName;
		$config["password"] = $password;
		$config["regTime"] = $regTime;
		$config["md5key"] = $md5key;
		$config["chmod"] = strval($chmod);
		$config["name"] = $userName;
		$redis->hmset($key, $config);
		$map["md5key"] = $md5key;
	}
	$map["code"] = $code;

	return $map;
}

/**
 * 添加应用
 */
function addApp($userName, $appName, $order, $forbiddenType, $forbiddenValue) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	$uuid = get_uuid();
	$existsUser = $redis->exists(CONFIG_USER . $userName);
	if (!$existsUser) {
		$code = 1004;
	} else {
		$key = CONFIG_APP . $uuid;
		if(!$redis->exists($key)){
			$redis->rpush(CHILDREN_USER . $userName, $uuid);
			$config = array ();
			$config["id"] = $uuid;
			$config["name"] = $appName;
			$config["order"] = $order;
			$config["forbiddenType"] = $forbiddenType;
			$config["forbiddenValue"] = $forbiddenValue;
			$config["parent"] = $userName;
			$redis->hmset($key, $config);
			$map["id"] = $uuid;
		}else{
			$code = 0;
		}
	}
	$map["code"] = $code;
	return $map;
}

function addRegion($appId, $regionName, $order, $forbiddenType, $forbiddenValue) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	$uuid = get_uuid();
	$key = CONFIG_REGION . $uuid;
	if(!$redis->exists($key)){
		$redis->rpush(CHILDREN_APP . $appId, $uuid);
		$config = array ();
		$config["id"] = $uuid;
		$config["name"] = $regionName;
		$config["order"] = $order;
		$config["forbiddenType"] = $forbiddenType;
		$config["forbiddenValue"] = $forbiddenValue;
		$config["parent"] = $appId;
		$redis->hmset($key, $config);
		$map["id"] = $uuid;
	}else{
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

function addServer($regionId, $serverName, $version, $order, $forbiddenType, $forbiddenValue) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	$uuid = get_uuid();
	$key = CONFIG_SERVER . $uuid;
	if(!$redis->exists($key)){
		$redis->rpush(CHILDREN_REGION . $regionId, $uuid);
		$data = array ();
		$data["id"] = $uuid;
		$data["name"] = $serverName;
		$data["version"] = $version;
		$data["order"] = $order;
		$data["status"] = 100;
		$data["forbiddenType"] = $forbiddenType;
		$data["forbiddenValue"] = $forbiddenValue;
		$data["parent"] = $regionId;
		$redis->hmset($key, $data);
		$map["id"] = $uuid;
	}else{
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * {@inheritDoc}
 */
function addProcess($serverId, $processId, $order, $host, $port, $usedMemory, $online) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	$uuid = strlen($processId) > 0 ? $processId : get_uuid();
	$key = CONFIG_PROCESS . $uuid;
	if ($redis->exists($key)) {
		$code = 0;
		$map["code"] = $code;
		return $map;
	}
	$_port = intval($port);
	if ($_port <= 0) {
		$_port = 9999;
		$port = "9999";
	}

	// 新增数据
	$data = array ();
	if (strlen($uuid) > 0) {
		$data["id"] = strval($uuid);
	}
	if (strlen($host) > 0) {
		$data["host"] = strval($host);
	}
	if (strlen($port) > 0) {
		$data["port"] = strval($port);
	}
	if (strlen($usedMemory) > 0) {
		$data["usedMemory"] = strval($usedMemory);
	}
	if (strlen($online) > 0) {
		$data["online"] = strval($online);
	}
	if (strlen($order) > 0) {
		$data["order"] = strval($order);
	}
	$data["parent"] = $serverId;
	$redis->hmset($key, $data);
	$redis->rpush(CHILDREN_SERVER . $serverId, $uuid);
	$map["id"] = $uuid;
	$map["code"] = $code;
	return $map;
}

/**
 * {@inheritDoc}
 */
function removeApp($userName, $appId) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	$key = CONFIG_APP . $appId;
	if ($redis->exists($key)) {
		$parent = $redis->hget($key, "parent");
		if ($parent == $userName) {
			$childrens = $redis->lrange(CHILDREN_APP . $appId, 0, -1);
			foreach ($childrens as $children) {
				removeRegion($appId, $children);
			}
			$redis->lrem(CHILDREN_USER . $userName, 1, strval($appId));
			$redis->del($key);
		}
	} else {
		// 不存在该应用
		$code = 1002;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * {@inheritDoc}
 */
function removeRegion($appId, $regionId) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	// 数据配置键
	$key = CONFIG_REGION . $regionId;
	// 是否存在该服务器
	$exists = $redis->exists($key);
	// 存在则进行对应删除动作
	if ($exists) {
		$parent = $redis->hget($key, "parent");
		if ($parent == $appId) {
			$childrens = $redis->lrange(CHILDREN_REGION . $regionId, 0, -1);
			foreach ($childrens as $children) {
				removeServer($regionId, $children);
			}
			$redis->lrem(CHILDREN_APP . $appId, 1, strval($regionId));
			$redis->del($key);
		}
	} else {
		// 不存在该分区名称
		$code = 1006;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * {@inheritDoc}
 */
function removeServer($regionId, $serverId) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	// 数据配置键
	$key = CONFIG_SERVER . $serverId;
	// 是否存在该服务器
	$exists = $redis->exists($key);
	// 存在则进行对应删除动作
	if ($exists) {
		$parent = $redis->hget($key, "parent");
		//验证归属
		if ($regionId == $parent) {
			$childrens = $redis->lrange(CHILDREN_SERVER . $serverId, 0, -1);
			foreach ($childrens as $children) {
				removeProcess($serverId, $children);
			}
			$redis->lrem(CHILDREN_REGION . $regionId, 1, strval($serverId));
			$redis->del($key);
		}
	} else {
		// 不存在该服务器名称
		$code = 1008;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * {@inheritDoc}
 */
function removeProcess($serverId, $processId) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	// 数据键
	$key = CONFIG_PROCESS . $processId;
	if ($redis->exists($key)) {
		$parent = $redis->hget($key, "parent");
		//验证归属
		if ($serverId == $parent) {
			$redis->lrem(CHILDREN_SERVER . $serverId, 1, strval($processId));
			$redis->del($key);
		}
	} else {
		$code = 1010;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 更新应用相关信息配置
 * 
 * @param channel
 * @param appId
 * @param appName
 * @param order
 * @return
 */
function updateApp($userName, $appId, $appName, $order) {
	global $config, $server, $redis;
	$map = array ();
	$code = 1;
	$key = CONFIG_APP.$appId;
	// 验证是否存在应用
	if ($redis->exists($key)) {
		$parent = $redis->hget($key,"parent");
		if($parent==$userName){
			// 名称保存不变,只是修改序号
			$redis->hset($key, "order", $order);
			$redis->hset($key, "name", $appName);
		}
	} else {
		// 异常操作
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 更新应用相关信息配置
 * 
 * @param channel
 * @param appId
 * @param appName
 * @param order
 * @return
 */
function updateRegion($appId, $regionId, $regionName, $order, $forbiddenType, $forbiddenValue) {
	global $config, $server, $redis;
	$map = array ();
	$code = 1;
	$key = CONFIG_REGION.$regionId;
	if($redis->exists($key)){
		$parent = $redis->hget($key,"parent");
		if($parent==$appId){
			$redis->hset($key, "name", $regionName);
			$redis->hset($key, "order", $order);
			$redis->hset($key, "forbiddenType", $forbiddenType);
			$redis->hset($key, "forbiddenValue", $forbiddenValue);
		}		
	}else{
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 更新应用相关信息配置
 * 
 * @param channel
 * @param appId
 * @param appName
 * @param order
 * @return
 */
function updateServer($regionId, $serverId, $serverName, $order, $forbiddenType, $forbiddenValue, $version, $status) {
	global $config, $server, $redis;
	$map = array ();
	$code = 1;
	$key = CONFIG_SERVER.$serverId;
	if($redis->exists($key)){
		$parent = $redis->hget($key,"parent");
		if($parent==$regionId){
			$redis->hset($key, "name", $serverName);
			$redis->hset($key, "version", $version);
			$redis->hset($key, "status", $status);
			$redis->hset($key, "order", $order);
			$redis->hset($key, "forbiddenType", $forbiddenType);
			$redis->hset($key, "forbiddenValue", $forbiddenValue);
		}
	}else{
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 更新进程相关信息
 * 
 * @param channel
 * @param sid
 * @param pid
 * @param json
 * @return
 */
function updateProcess($sid, $pid, $json) {
	global $config, $server, $redis;
	$map = array ();
	$key = CONFIG_PROCESS.$pid;
	if($redis->exists($key)){
		$parent = $redis->hget($key,"parent");
		if($parent==$sid){
			try {
				$jsonObject = json_decode($json, true);
				unset ($jsonObject["id"]);
				unset ($jsonObject["parent"]);
				$redis->hmset($key, json_encode($jsonObject));
			}catch(Exception $e){
				
			}
		}
	}else{
		$code = 0;
	}
	
	$map["code"] = 1;
	return $map;
}

/**
 * 加载用户信息
 * 
 * @param userName
 * @param password
 * @return
 */
function loadUser($userName, $password) {
	global $config, $server, $redis;
	$code = 1;
	$map = array ();
	$key_user = CONFIG_USER . $userName;

	$userData = $redis->hgetall($key_user);

	if (count($userData) == 0) {
		$code = 1004;
	} else {
		$truePassword = $userData["password"];
		if ($password != $truePassword) {
			$code = 1011;
		} else {
			$map = $userData;
		}
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 加载应用列表
 */
function load_apps($userName) {
	global $config, $server, $redis;
	$childrens = $redis->lrange(CHILDREN_USER . $userName, 0, -1);
	$list = array ();
	foreach ($childrens as $children) {
		$value = $redis->hgetall(CONFIG_APP . $children);
		if (count($value) > 0) {
			array_push($list, $value);
		}
	}
	$target = array_multisort_sort($list, 'order');
	return $target;
}

/**
 * 加载分区列表
 */
function load_regions($app_id) {

	global $config, $server, $redis;

	$childrens = $redis->lrange(CHILDREN_APP . $app_id, 0, -1);

	$list = array ();

	foreach ($childrens as $children) {
		$value = $redis->hgetall(CONFIG_REGION . $children);
		if (count($value) > 0) {
			array_push($list, $value);
		}
	}
	$target = array_multisort_sort($list, 'order');

	return $target;
}

/**
 * 加载服务器列表
 */
function load_servers($region_id) {

	global $config, $server, $redis;

	$childrens = $redis->lrange(CHILDREN_REGION . $region_id, 0, -1);

	$list = array ();

	foreach ($childrens as $children) {

		$value = $redis->hgetall(CONFIG_SERVER . $children);

		if (count($value) > 0) {
			array_push($list, $value);
		}
	}
	$target = array_multisort_sort($list, 'order');

	return $target;
}

/**
 * 加载进程列表
 */
function load_process($server_id) {

	global $config, $server, $redis;

	$childrens = $redis->lrange(CHILDREN_SERVER . $server_id, 0, -1);

	$list = array ();

	foreach ($childrens as $children) {

		$value = $redis->hgetall(CONFIG_PROCESS . $children);

		if (count($value) > 0) {
			array_push($list, $value);
		}
	}
	$target = array_multisort_sort($list, 'order');

	return $target;
}

/**
 * 加载分区下的服务器列表
 */
function loadByRegion($regionId, $ip = "127.0.0.1") {

	global $config, $server, $redis;

	$key_data = CONFIG_REGION . $regionId;

	$value = $redis->hgetall($key_data);

	$list = array ();

	if (count($value) > 0) {

		$this_forbiddenType = $value['forbiddenType'];

		$this_forbiddenValue = $value['forbiddenValue'];

		$this_isVaildIp = isVaildIp(intval($this_forbiddenType), $this_forbiddenValue, $ip);

		if ($this_isVaildIp) {

			$item_list = load_servers($regionId, $ip);

			foreach ($item_list as $item) {

				$item_forbiddenType = $item['forbiddenType'];

				$item_forbiddenValue = $item['forbiddenValue'];

				$item_isVaildIp = isVaildIp(intval($item_forbiddenType), $item_forbiddenValue, $ip);

				if ($item_isVaildIp) {

					$server_id = $item['id'];

					$process_list = load_process($server_id, $ip);

					if (count($process_list) > 0) {
						$process_min = $process_list[0];
						$item['host'] = $process_min['host'];
						$item['port'] = $process_min['port'];
						$item['online'] = $process_min['online'];
						array_push($list, $item);
					}
				}
			}
		}
	}
	return $list;
}