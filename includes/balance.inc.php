<?php





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
		$map = array();
		$code = 1;
		$key = "data:user:$userName";
		if ($redis->exists($key)) {
			$code = 1003;
		} else {
			$userInfo = array();
			$regTime = strval(time());
			$md5key = strtoupper(md5($userName.$password.$regTime));

			$userInfo["name"] = $userName;
			$userInfo["password"] = $password;
			$userInfo["regTime"] = $regTime;
			$userInfo["md5key"] = $md5key;
			$userInfo["chmod"] = strval($chmod);
			$userInfo["name"] = $userName;
	
			$map["md5key"] = $md5key;
			
			$redis->hmset($key, $userInfo);
			$redis->hset("_users", $md5key, $userName);

		}
		$map["code"] = $code;

		return $map;
	}


/**
 * 添加应用
 */
function addApp($userName, $appName,$order,$forbiddenType, $forbiddenValue) {
		global $config, $server, $redis;
		$code = 1;
		$map = array();
		$appId = get_uuid();
		$existsUser = $redis->exists("data:user:$userName");
		if (!$existsUser) {
			$code = 1004;
		} else {
			$appKey = "data:app:$userName:$appName";
			$existsAppName = $redis->exists($appKey);
			if ($existsAppName) {
				$code = 1001;
			} else {
				$map["id"] = $appId;
				$userKey = "index:user:app:$userName";
				// 保存用户应用列表
				$redis->hset($userKey, $appId, $appName);
				// 保存应用具体信息
				$appData = array();
				$appData["id"] =  $appId;
				$appData["name"] =  $appName;
				$appData["order"] =  $order;
				$appData["forbiddenType"] =  $forbiddenType;
				$appData["forbiddenValue"] =  $forbiddenValue;
				$redis->hmset($appKey, $appData);
			}
		}
		$map["code"] = $code;
		return $map;
}

function addRegion($appId, $regionName, $order, $forbiddenType, $forbiddenValue) {
		global $config, $server, $redis;
		$code = 1;
		$map = array();
		$key_region = "index:app:region:$appId";
		$existsRegionName = $redis->hexists($key_region, $regionName);
		if (!$existsRegionName) {
			$uuid = get_uuid();
			// 新建索引
			$redis->hset($key_region, $regionName, $uuid);
			// 新增数据
		    $regionData = array();
		    $regionData["id"] = $uuid;
		    $regionData["name"] = $regionName;
		    $regionData["order"] = $order;
		    $regionData["forbiddenType"] = $forbiddenType;
		    $regionData["forbiddenValue"] = $forbiddenValue;
			$redis->hmset("data:region:$uuid", $regionData);
			$map["id"] = $uuid;
		} else {
			$code = 1005;
		}
		$map["code"] = $code;
		return $map;
}

function addServer($regionId, $serverName, $version, $order, $forbiddenType, $forbiddenValue) {
		global $config, $server, $redis;
		$code = 1;
		$map = array();
		$key_server = "index:region:server:$regionId";
		$existsServerName = $redis->hexists($key_server, $serverName);
		if (!$existsServerName) {
			$uuid = get_uuid();
			// 新建索引
			$redis->hset($key_server, $serverName, $uuid);
			// 新增数据
			$data = array();
			$data["id"] = $uuid;
			$data["name"] = $serverName;
			$data["version"] = $version;
			$data["order"] = $order;
			$data["status"] = 100;
			$data["forbiddenType"] = $forbiddenType;
			$data["forbiddenValue"] = $forbiddenValue;
			$redis->hmset("data:server:$uuid", $data);
			$map["id"] = $uuid;
		} else {
			$code = 1007;
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
		$map = array();
		// 服务器下的进程映射表
		$key_index = "index:server:process:$serverId";
		// 进程uuid
		$uuid = strlen($processId)>0?$processId:get_uuid();

		$processMap = $redis->hgetall($key_index);

		// if (!processMap.containsKey(uuid)) {
		$server_index = $host . ":" . $port;
		// 是否存在相同主机地址以及端口配置
		$same = array();
		foreach($processMap as $processKey => $processValue){
			if($processValue==$server_index){
				array_push($same,$processKey);
			}
		}
		
		if (count($same)>0) {
			// 存在相同主机地址和端口
			// code = InfoCode.EXISTS_HOST_AND_PORT;
			// 获取旧的键
			$old_uuid = $same[0];
			// 删除旧数据
			$redis->hdel($key_index, $old_uuid);
			// 删除数据键
			$redis->del("data:process:$serverId:$old_uuid");
		}
		$_port = intval($port);
		if ($_port <= 0) {
			$_port = 9999;
			$port = "9999";
		}

		// 新增数据
		$data = array();
		if (strlen($uuid)> 0) {
			$data["id"] = strval($uuid);
		}
		if (strlen($host)  > 0) {
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
		$key_data = "data:process:$serverId:$uuid";
		// 保存完整数据
		$redis->hmset($key_data, $data);
		// 新建索引
		$redis->hset($key_index, $uuid, $server_index);
		
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
		$map = array();

		// 用户键
		$key_user = "index:user:app:$userName";

		if ($redis->hexists($key_user, $appId)) {
			// 获取应用名称
			$name = $redis->hget($key_user, $appId);
			//
			$key_data = "data:app:$userName:$name";
			// 获取应用下的所有分区,一并进行删除
			$key_children = "index:app:region:$appId";
			// 从用户索引中删除该应用数据
			$redis->hdel($key_user, $appId);
			// 删除该应用的详细配置数据
			$redis->del($key_data);
			// 删除子分区
			$childrenMap = $redis->hgetall($key_children);

			foreach ($childrenMap as $childrenKey => $childrenValue) {
				// 删除服务器
				removeRegion($appId, $childrenValue);
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

		$map = array();
		// 数据配置键
		$key_data = "data:region:$regionId";
		// 索引键
		$key_index = "index:app:region:$appId";
		// 服务器名称
		$name = $redis->hget($key_data, "name");
		// 是否存在该服务器
		$existsName = $redis->hexists($key_index, (null != $name&& false != $name) ? $name : "");
		// 存在则进行对应删除动作
		if ($existsName) {
			// 删除数据详情
			$redis->del($key_data);
			// 从分区索引中删除
			$redis->hdel($key_index, $name);
			// 获取分区下的所有服务器,一并进行删除
			$key_children = "index:region:server:$regionId";
			// 分区下的服务器索引映射表
			$childrenMap = $redis->hgetAll($key_children);

			foreach($childrenMap as $childrenKey => $childrenValue){
				// 删除服务器
				removeServer($regionId, $childrenValue);
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
		$map = array();
		// 数据配置键
		$key_data = "data:server:$serverId";
		// 索引键
		$key_index = "index:region:server:$regionId";
		
		// 服务器名称
		$name = $redis->hget($key_data, "name");

		// 是否存在该服务器
		$existsName = $redis->hexists($key_index, (null != $name && false != $name)? $name : "");
		
		// 存在则进行对应删除动作
		if ($existsName) {
			// 删除数据详情
			$redis->del($key_data);
			// 从分区索引中删除
			$redis->hdel($key_index, $name);
			// 获取该服务器下的进程配置

			$processMap = $redis->hgetall("index:server:process:$serverId");

			foreach($processMap as $processKey => $processValue){
				removeProcess($serverId, $processKey);
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
		$map = array();
		// 数据键
		$key_data = "data:process:serverId:$processId";
		// 索引键
		$key_index = "index:server:process:$serverId";
		
		// 如果存在则进行对应的删除动作
		if ($redis->hexists($key_index, $processId)) {
			$redis->del($key_data);
			$redis->hdel($key_index, $processId);
		} else {
			//不存在进程
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
		$map = array();
		$code = 1;
		$key_index = "index:user:app:$userName";
		// 用户所绑定的应用映射表
		$appMap = $redis->hgetall($key_index);

		// 验证是否存在应用
		if (array_key_exists($appId,$appMap)) {
			// 原来的应用名称
			$srcName = $appMap[$appId];
			// 原来的应用key
			$key_data = "data:app:$userName:$srcName";
			// 名称发生变化
			if ($srcName!=$appName) {
				// 是否已经存在该名称
				$newKey = "data:app:$userName:$appName";
				if ($redis->exists($newKey)) {
					$code = 1001;
				} else {
					$oldKey = $key_data;
					$redis->hset($key_index, $appId, $appName);					
					$redis->renamenx($oldKey, $newKey);
					$redis->hset($newKey, "order", $order);
					$redis->hset($newKey, "name", $appName);
				}
			} else {
				// 名称保存不变,只是修改序号
				$redis->hset($key_data, "order", $order);
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
		$map = array();
		$code = 1;
		$key_index = "index:app:region:$appId";
		// 用户所绑定的分区映射表
		$regionMap = $redis->hgetall($key_index);
		$srcName = false;
		foreach ($regionMap as $regionKey => $regionValue) {
			if($regionValue==$regionId){
				$srcName = $regionKey;
				break;
			}
		}
		$key_data = "data:region:$regionId";
		// 验证是否存在分区
		if ($srcName!=false) {
			// 名称发生变化
			if ($srcName!=$regionName) {
				// 是否已经存在该名称
				if (array_key_exists($regionName, $regionMap)) {
					$code = 1005;
				} else {
					// 新增索引
					$redis->hset($key_index, $regionName, $regionId);
					// 删除原有键
					$redis->hdel($key_index, $srcName);
					// 修改保存呢最新配置
					$redis->hset($key_data, "name", $regionName);
					$redis->hset($key_data, "order", $order);
					$redis->hset($key_data, "forbiddenType", $forbiddenType);
					$redis->hset($key_data, "forbiddenValue", $forbiddenValue);
				}
			} else {
				// 名称保存不变,其他修改
				$redis->hset($key_data, "order", $order);
				$redis->hset($key_data, "forbiddenType", $forbiddenType);
				$redis->hset($key_data, "forbiddenValue", $forbiddenValue);
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
	function updateServer($regionId, $serverId, $serverName, $order, $forbiddenType, $forbiddenValue, $version, $status) {
		global $config, $server, $redis;
		$map = array();
		$code = 1;
		$key_index = "index:region:server:$regionId";
	
		$serverMap = $redis->hgetall($key_index);

		$srcName = false;
		
		foreach ($serverMap as $serverKey => $serverValue) {
			if($serverValue==$serverId){
				$srcName = $serverKey;
				break;
			}
		}
		$key_data = "data:server:$serverId";
		// 验证是否存在服务器
		if ($srcName!=false) {
			// 名称发生变化
			if ($srcName!=$serverName) {
				// 是否已经存在该名称
				if (array_key_exists($serverName, $serverMap)) {
					$code = 1007;
				} else {
					// 新增索引
					$redis->hset($key_index, $serverName, $serverId);
					// 删除原有键
					$redis->hdel($key_index, $srcName);
					// 修改保存呢最新配置
					$redis->hset($key_data, "name", $serverName);
					$redis->hset($key_data, "version", $version);
					$redis->hset($key_data, "status", $status);
					$redis->hset($key_data, "order", $order);
					$redis->hset($key_data, "forbiddenType", $forbiddenType);
					$redis->hset($key_data, "forbiddenValue", $forbiddenValue);
				}
			} else {
				// 名称保存不变,其他修改
				$redis->hset($key_data, "version", $version);
				$redis->hset($key_data, "status", $status);
				$redis->hset($key_data, "order", $order);
				$redis->hset($key_data, "forbiddenType", $forbiddenType);
				$redis->hset($key_data, "forbiddenValue", $forbiddenValue);
			}
		} else {
			// 异常操作
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
		$map = array();
		$key_index = "index:server:process:$sid";	
		$data = $redis->hgetall($key_index);
		if (array_key_exists($pid, $data)) {
			try {
				$jsonObject = json_decode($json,true);
				unset($jsonObject["id"]);
				$key_data = "data:process:$sid:$pid";
				$redis->hmset($key_data, json_encode($jsonObject));
			} catch (Exception $e) {
				
			}
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
		$map = array();
		$key_user = "data:user:$userName";
		$userData = $redis->hgetall($key_user);

		
		if (count($userData)==0) {
			$code = 1004;
		} else {
			$truePassword = $userData["password"];
			if ($password!=$truePassword) {
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
function load_apps($userName, $ip = "127.0.0.1") {
	global $config, $server, $redis;

	$key_app_index = "index:user:app:$userName";

	$map_index = $redis->hgetall($key_app_index);

	$list = array ();

	foreach ($map_index as $id => $name) {

		$key_data = "data:app:$userName:$name";

		$value = $redis->hgetall($key_data);

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
function load_regions($app_id, $ip = "127.0.0.1") {

	global $config, $server, $redis;

	$key_index = "index:app:region:$app_id";

	$map_index = $redis->hgetall($key_index);

	$list = array ();

	foreach ($map_index as $name => $id) {

		$key_data = "data:region:$id";

		$value = $redis->hgetall($key_data);

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
function load_servers($region_id, $ip = "127.0.0.1") {

	global $config, $server, $redis;

	$key_index = "index:region:server:$region_id";

	$map_index = $redis->hgetall($key_index);

	$list = array ();

	foreach ($map_index as $name => $id) {

		$key_data = "data:server:$id";

		$value = $redis->hgetall($key_data);

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
function load_process($server_id, $ip = "127.0.0.1") {

	global $config, $server, $redis;

	$key_index = "index:server:process:$server_id";
	
	$map_index = $redis->hgetall($key_index);

	$list = array ();

	foreach ($map_index as $id => $name) {

		$key_data = "data:process:$server_id:$id";

		$value = $redis->hgetall($key_data);

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
function loadByRegion($regionId,$ip="127.0.0.1"){
	
	global $config, $server, $redis;
	
	$key_data = "data:region:$regionId";

	$value = $redis->hgetall($key_data);
	
	$list = array();
	
	if(count($value)>0){
		
		$this_forbiddenType = $value['forbiddenType'];
		
		$this_forbiddenValue = $value['forbiddenValue'];
		
		$this_isVaildIp = isVaildIp(intval($this_forbiddenType),$this_forbiddenValue,$ip);
		
		if($this_isVaildIp){
			
			$item_list = load_servers($regionId,$ip);
			
			foreach($item_list as $item){
			
				$item_forbiddenType = $item['forbiddenType'];	
				
				$item_forbiddenValue = $item['forbiddenValue'];
				
				$item_isVaildIp = isVaildIp(intval($item_forbiddenType),$item_forbiddenValue,$ip);
				
				if($item_isVaildIp){
					
					$server_id = $item['id'];
					
					$process_list = load_process($server_id,$ip);
					
					if(count($process_list)>0){
						$process_min = $process_list[0];
						$item['host'] = $process_min['host'];
						$item['port'] = $process_min['port'];
						$item['online'] = $process_min['online'];
						array_push($list,$item);
					}
				}
			}	
		}
	}
	return $list;
}