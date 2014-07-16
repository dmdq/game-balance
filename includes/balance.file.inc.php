<?php
include(dirname(__FILE__).'/config.inc.php');
/**
 * 添加用户
 */
function addUser($userName, $password, $chmod) {
	$file = BALANCE_DATA_PATH_USER.$userName.BALANCE_FILE_SUFFIX;
	$map = array ();
	$code = 1;
	if (file_exists($file)) {
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
		$config['apps'] = array();
		@file_put_contents($file,@json_encode($config)) ;
		$map["md5key"] = $md5key;
	}
	$map["code"] = $code;

	return $map;
}

/**
 * 添加应用
 */
function addApp($userName, $appName, $order, $forbiddenType, $forbiddenValue, $id = null) {
	
	$code = 1;
	$map = array ();
	$uuid = $id != null ? $id : get_uuid();
	$file_user = BALANCE_DATA_PATH_USER.$userName.BALANCE_FILE_SUFFIX;
	
	$existsUser = file_exists($file_user);
	if (!$existsUser) {
		$code = 1004;
	} else {
		$file_app = BALANCE_DATA_PATH_APP.$uuid.BALANCE_FILE_SUFFIX;
		if (!file_exists($file_app)) {
			//$redis->rpush(CHILDREN_USER . $userName, $uuid);
			$userMap = @json_decode(@file_get_contents($file_user),true);
			$apps = $userMap['apps'];
			array_push($apps,$uuid);
			$userMap['apps'] = $apps;
			@file_put_contents($file_user,@json_encode($userMap));
			
			$config = array ();
			$config["id"] = $uuid;
			$config["name"] = $appName;
			$config["order"] = $order;
			$config["forbiddenType"] = $forbiddenType;
			$config["forbiddenValue"] = $forbiddenValue;
			$config["parent"] = $userName;
			$config["regions"] = array();
			
			@file_put_contents($file_app,@json_encode($config));
			$map["id"] = $uuid;
		} else {
			$code = 0;
		}
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 添加分区
 */
function addRegion($appId, $regionName, $order, $forbiddenType, $forbiddenValue,$id=null) {
	
	$code = 1;
	$map = array ();
	$uuid = $id != null ? $id : get_uuid();
	$file_region = BALANCE_DATA_PATH_REGION.$uuid.BALANCE_FILE_SUFFIX;
	if (!file_exists($file_region)) {
		$parent_file = BALANCE_DATA_PATH_APP.$appId.BALANCE_FILE_SUFFIX;
		$parent_object = @json_decode(@file_get_contents($parent_file),true);
		$regions = $parent_object['regions'];
		array_push($regions,$uuid);
		$parent_object['regions'] = $regions;
		@file_put_contents($parent_file,@json_encode($parent_object));
		$config = array ();
		$config["id"] = $uuid;
		$config["name"] = $regionName;
		$config["order"] = $order;
		$config["forbiddenType"] = $forbiddenType;
		$config["forbiddenValue"] = $forbiddenValue;
		$config["parent"] = $appId;
		$config["servers"] = array();
		@file_put_contents($file_region,@json_encode($config));
		$map["id"] = $uuid;
	} else {
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 添加服务器
 */
function addServer($regionId, $serverName, $version, $order, $forbiddenType, $forbiddenValue,$status=100,$id=null) {
	$code = 1;
	$map = array ();
	$uuid = $id != null ? $id : get_uuid();
	$file_server = BALANCE_DATA_PATH_SERVER.$uuid.BALANCE_FILE_SUFFIX;
	if (!file_exists($file_server)) {
//		$redis->rpush(CHILDREN_REGION . $regionId, $uuid);
		$parent_file = BALANCE_DATA_PATH_REGION.$regionId.BALANCE_FILE_SUFFIX;
		$parent_object = json_decode(file_get_contents($parent_file),true);
		$servers = $parent_object['servers'];
		array_push($servers,$uuid);
		$parent_object['servers'] = $servers;
		@file_put_contents($parent_file,@json_encode($parent_object));
		$data = array ();
		$data["id"] = $uuid;
		$data["name"] = $serverName;
		$data["version"] = $version;
		$data["order"] = $order;
		$data["status"] = $status;
		$data["forbiddenType"] = $forbiddenType;
		$data["forbiddenValue"] = $forbiddenValue;
		$data["parent"] = $regionId;
		$data["processes"] = array();
		@file_put_contents($file_server,@json_encode($data));
		$map["id"] = $uuid;
	} else {
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 添加进程
 */
function addProcess($serverId, $processId, $order, $host, $port, $usedMemory, $online) {
	
	$code = 1;
	$map = array ();
	$uuid = strlen($processId) > 0 ? $processId : get_uuid();
	$file_process  =  BALANCE_DATA_PATH_PROCESS.$uuid.BALANCE_FILE_SUFFIX;
	if (file_exists($file_process)) {
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
		$data["port"] = intval($port);
	}
	if (strlen($usedMemory) > 0) {
		$data["usedMemory"] = strval($usedMemory);
	}
	if (strlen($online) > 0) {
		$data["online"] = strval($online);
	}
	if (strlen($order) > 0) {
		$data["order"] = intval($order);
	}
	$data["parent"] = $serverId;
	
	$parent_file = BALANCE_DATA_PATH_SERVER.$serverId.BALANCE_FILE_SUFFIX;
	$parent_object = @json_decode(@file_get_contents($parent_file),true);
	$processes = array();
	if(array_key_exists("processes",$parent_object)){
		$processes = $parent_object['processes'];
	}
	array_push($processes,$uuid);	
	$parent_object['processes'] = $processes;
	@file_put_contents($parent_file,@json_encode($parent_object));
	@file_put_contents($file_process,@json_encode($data));
	$map["id"] = $uuid;
	$map["code"] = $code;
	return $map;
}

/**
 * 移除应用
 */
function removeApp($userName, $appId) {
	$code = 1;
	$map = array ();
	$key = CONFIG_APP . $appId;
	$file_app = BALANCE_DATA_PATH_APP.$appId.BALANCE_FILE_SUFFIX;
	$file_user = BALANCE_DATA_PATH_USER.$userName.BALANCE_FILE_SUFFIX;
	if (file_exists($file_user)&&file_exists($file_app)) {
		$object_app = @json_decode(@file_get_contents($file_app),true);
		$parent = $object_app['parent'];
		if ($parent == $userName) {
			$childrens = $object_app['regions'];
			foreach ($childrens as $children) {
				removeRegion($appId, $children);
			}
			@unlink($file_app);
			$object_user = @json_decode(@file_get_contents($file_user),true);
			$parent_items = $object_user['apps'];
			array_remove_value($parent_items,$appId);
			$object_user['apps'] = $parent_items;
			@file_put_contents($file_user,@json_encode($object_user));
		}
	} else {
		// 不存在该应用
		$code = 1002;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 移除分区
 */
function removeRegion($appId, $regionId) {
	
	$code = 1;
	$map = array ();
	$file_app = BALANCE_DATA_PATH_APP.$appId.BALANCE_FILE_SUFFIX;
	$file_region = BALANCE_DATA_PATH_REGION.$regionId.BALANCE_FILE_SUFFIX;
	if (file_exists($file_app)&&file_exists($file_region)) {
		$object_app = @json_decode(@file_get_contents($file_app),true);
		$object_region = @json_decode(@file_get_contents($file_region),true);
		$parent = $object_region['parent'];
		if ($parent == $appId) {
			$childrens = $object_region['servers'];
			foreach ($childrens as $children) {
				removeServer($regionId, $children);
			}
			@unlink($file_region);
			$parent_items = $object_app['regions'];
			array_remove_value($parent_items,$regionId);
			$object_app['regions'] = $parent_items;
			@file_put_contents($file_app,@json_encode($object_app));
			
		}
	} else {
		// 不存在该分区名称
		$code = 1006;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 移除服务器
 */
function removeServer($regionId, $serverId) {
	
	$code = 1;
	$map = array ();

	$file_region = BALANCE_DATA_PATH_REGION.$regionId.BALANCE_FILE_SUFFIX;
	$file_server = BALANCE_DATA_PATH_SERVER.$serverId.BALANCE_FILE_SUFFIX;
	// 存在则进行对应删除动作
	if (file_exists($file_region)&&file_exists($file_server)) {
		$object_region = @json_decode(@file_get_contents($file_region),true);
		$object_server = @json_decode(@file_get_contents($file_server),true);
		$parent = $object_server['parent'];
		//验证归属
		if ($regionId == $parent) {
			$childrens = $object_server['processes'];
			foreach ($childrens as $children) {
				removeProcess($serverId, $children);
			}
			@unlink($file_server);
			$parent_items = $object_region['servers'];
			array_remove_value($parent_items,$serverId);
			$object_region['servers'] = $parent_items;
			@file_put_contents($file_region,@json_encode($object_region));
			
		}
	} else {
		// 不存在该服务器名称
		$code = 1008;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 移除进程
 */
function removeProcess($serverId, $processId) {
	$code = 1;
	$map = array ();
	$file_server = BALANCE_DATA_PATH_SERVER.$serverId.BALANCE_FILE_SUFFIX;
	$file_process = BALANCE_DATA_PATH_PROCESS.$processId.BALANCE_FILE_SUFFIX;
	if (file_exists($file_server)&&file_exists($file_process)) {
		$object_server = @json_decode(@file_get_contents($file_server),true);
		$object_process = @json_decode(@file_get_contents($file_process),true);
		$parent = $object_process['parent'];
		//验证归属
		if ($serverId == $parent) {
			@unlink($file_process);
			$parent_items = $object_server['processes'];
			array_remove_value($parent_items,$processId);
			$object_server['processes'] = $parent_items;
			@file_put_contents($file_server,@json_encode($object_server));
		}
	} else {
		$code = 1010;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 更新应用相关信息配置
 */
function updateApp($userName, $appId, $appName, $order) {
	$map = array ();
	$code = 1;
	$file_app = BALANCE_DATA_PATH_APP.$appId.BALANCE_FILE_SUFFIX;
	// 验证是否存在应用
	if (file_exists($file_app)) {
		$object_app = @json_decode(@file_get_contents($file_app),true);
		$parent = $object_app['parent'];
		if ($parent == $userName) {
			// 名称保存不变,只是修改序号
			$object_app['order'] = $order;
			$object_app['name'] = $appName;
			@file_put_contents($file_app,@json_encode($object_app));
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
 */
function updateRegion($appId, $regionId, $regionName, $order, $forbiddenType, $forbiddenValue) {
	
	$map = array ();
	$code = 1;
	$file_region = BALANCE_DATA_PATH_REGION.$regionId.BALANCE_FILE_SUFFIX;
	if (file_exists($file_region)) {
		$object_region = @json_decode(@file_get_contents($file_region),true);
		$parent = $object_region['parent'];
		if ($parent == $appId) {
			$object_region['name'] = $regionName;
			$object_region['order'] = $order;
			$object_region['forbiddenType'] = $forbiddenType;
			$object_region['forbiddenValue'] = $forbiddenValue;
			@file_put_contents($file_region,@json_encode($object_region));
		}
	} else {
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 更新应用相关信息配置
 */
function updateServer($regionId, $serverId, $serverName, $order, $forbiddenType, $forbiddenValue, $version, $status) {
	$map = array ();
	$code = 1;
	$fiel_server = BALANCE_DATA_PATH_SERVER.$serverId.BALANCE_FILE_SUFFIX;
	if (file_exists($fiel_server)) {
		$object_server = @json_decode(@file_get_contents($fiel_server),true);
		$parent = $object_server['parent'];
		if ($parent == $regionId) {
			$object_server['name'] = $serverName;
			$object_server['version'] = $version;
			$object_server['status'] = $status;
			$object_server['order'] = $order;
			$object_server['forbiddenType'] = $forbiddenType;
			$object_server['forbiddenValue'] = $forbiddenValue;
			@file_put_contents($fiel_server,@json_encode($object_server));
		}
	} else {
		$code = 0;
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 更新进程相关信息
 */
function updateProcess($sid, $pid, $json) {
	$map = array ();
	$file_process = BALANCE_DATA_PATH_PROCESS.$pid.BALANCE_FILE_SUFFIX;
	if (file_exists($file_process)) {
		$object_process = @json_decode(@file_get_contents($file_process),true);
		$parent = $object_process['parent'];
		if ($parent == $sid) {
			try {
				$jsonObject = json_decode($json, true);
				unset ($jsonObject["id"]);
				unset ($jsonObject["parent"]);
				$object_process = array_merge($object_process,$jsonObject);
				@file_put_contents($file_process,@json_encode($object_process));
			} catch (Exception $e) {
			}
		}
	} else {
		$code = 0;
	}
	$map["code"] = 1;
	return $map;
}

/**
 * 加载用户信息
 */
function loadUser($userName, $password) {
	$code = 1;
	$map = array ();
	$user_file = BALANCE_DATA_PATH_USER.$userName.BALANCE_FILE_SUFFIX;
	
	if (!file_exists($user_file)) {
		$code = 1004;
	} else {
		$user_object = json_decode(file_get_contents($user_file),true);
		$truePassword = $user_object["password"];
		if ($password != $truePassword) {
			$code = 1011;
		} else {
			$map = $user_object;
		}
	}
	$map["code"] = $code;
	return $map;
}

/**
 * 加载应用列表
 */
function load_apps($userName) {
	$user_file = BALANCE_DATA_PATH_USER.$userName.BALANCE_FILE_SUFFIX;
	$apps = array();
	if(file_exists($user_file)){
		$user_object = json_decode(file_get_contents($user_file),true);
		$apps = $user_object['apps'];
	}
	$list = array ();
	foreach ($apps as $app) {
		$file_app = BALANCE_DATA_PATH_APP.$app.BALANCE_FILE_SUFFIX;
		if(file_exists($file_app)){
			array_push($list,json_decode(file_get_contents($file_app),true));
		}
	}
	$target = array_multisort_sort($list, 'order');
	return $target;
}

/**
 * 加载分区列表
 */
function load_regions($app_id) {
	$app_file = BALANCE_DATA_PATH_APP.$app_id.BALANCE_FILE_SUFFIX;
	$regions = array();
	if(file_exists($app_file)){
		$app_object = json_decode(file_get_contents($app_file),true);
		$regions = $app_object['regions'];
	}
	$list = array();
	foreach ($regions as $region) {
		$file_region = BALANCE_DATA_PATH_REGION.$region.BALANCE_FILE_SUFFIX;
		if(file_exists($file_region)){
			array_push($list, json_decode(file_get_contents($file_region),true));
		}
	}
	$target = array_multisort_sort($list, 'order');
	return $target;
}

/**
 * 加载服务器列表
 */
function load_servers($region_id) {
	$region_file = BALANCE_DATA_PATH_REGION.$region_id.BALANCE_FILE_SUFFIX;
	$servers = array();
	if(file_exists($region_file)){
		$region_object = json_decode(file_get_contents($region_file),true);
		$servers = $region_object['servers'];
	}
	$list = array ();
	foreach ($servers as $server) {
		$file_server = BALANCE_DATA_PATH_SERVER.$server.BALANCE_FILE_SUFFIX;
		if(file_exists($file_server)){
			array_push($list, json_decode(file_get_contents($file_server),true));
		}
	}
	$target = array_multisort_sort($list, 'order');

	return $target;
}

/**
 * 加载进程列表
 */
function load_process($server_id) {
	$server_file = BALANCE_DATA_PATH_SERVER.$server_id.BALANCE_FILE_SUFFIX;
	$processes = array();
	if(file_exists($server_file)){
		$server_object = json_decode(file_get_contents($server_file),true);
		$processes = $server_object['processes'];
	}
	$list = array ();
	foreach ($processes as $process) {
		$file_process = BALANCE_DATA_PATH_PROCESS.$process.BALANCE_FILE_SUFFIX;
		if(file_exists($file_process)){
			array_push($list, json_decode(file_get_contents($file_process),true));
		}
	}
	$target = array_multisort_sort($list, 'order');
	return $target;
}

/**
 * 从本地文件中恢复
 */
function restoreFromDumpFull($userName) {
	$file = BALANCE_DUMP_PATH_PULL . $userName.BALANCE_FILE_SUFFIX;
	if (file_exists($file) && is_file($file)) {
		$file_content = file_get_contents($file);
		$json_object = json_decode($file_content, true);
		$user = $json_object['user']; //用户名
		$password = $json_object['password']; //密码
		$result = addUser($user,$password,022);
		if($result['code']==1){
			$apps = $json_object['apps'];
			foreach($apps as $app){
				$name = $app['name'];
				$order = $app['order'];
				$forbiddenType = 0;
				$forbiddenValue = "";
				if(array_key_exists('forbiddenType',$app)){
					$forbiddenType = $app['forbiddenType'];
				}
				if(array_key_exists('forbiddenValue',$app)){
					$forbiddenValue = $app['forbiddenValue'];
				}
				$result = addApp($user, $name, $order, $forbiddenType, $forbiddenValue);
				if($result['code']==1){
					$appId = $result['id'];
					$regions = $app['regions'];
					foreach($regions as $region){
						$forbiddenType = 0;
						$forbiddenValue = "";
						if(array_key_exists('forbiddenType',$region)){
							$forbiddenType = $region['forbiddenType'];
						}
						if(array_key_exists('forbiddenValue',$region)){
							$forbiddenValue = $region['forbiddenValue'];
						}
						$regionId = $region['id'];
						$regionName = $region['name'];
						$regionOrder =  $region['order'];
						$result = addRegion($appId, $regionName, $regionOrder, $forbiddenType, $forbiddenValue,$regionId);
						if($result['code']==1){
							$servers = 	$region['servers'];
							foreach($servers as $server){
								$serverId = $server['id'];
								$serverName = $server['name'];
								$serverOrder = $server['order'];
								$serverStatus = $server['status'];
								$serverVersion = $server['version'];
								$serverForbiddenType = $server['forbiddenType'];
								$serverForbiddenValue = $server['forbiddenValue'];
								$result = addServer($regionId,$serverName,$serverVersion,$serverOrder,$serverForbiddenType,$serverForbiddenValue,$serverStatus,$serverId);
								if($result['code']==1){
									$processes = $server['processes'];
									foreach($processes as $process){
										$processOrder = $process['order'];
										$processHost = $process['host'];
										$processPort = $process['port'];
										$processOnline = $process['online'];
										$processUsedMemory = $process['usedMemory'];
										addProcess($serverId,"",$processOrder,$processHost,$processPort,$processUsedMemory,$processOnline);
									}
								}						
							}						
						}
					}
				}
			}			
		}
		return array("code" => 1);
	} else {
		return array("code" => 0);
	}
}

/**
 * 加载分区下的服务器列表
 */
function loadByRegion($regionId, $ip = "127.0.0.1",$fromCache=true) {
	$cache_file = BALANCE_CACHE_PATH_REGION.$regionId.BALANCE_FILE_SUFFIX;
	$list = array();
	if(@file_exists($cache_file)&&@is_file($cache_file)&&$fromCache){
		$cache_content = file_get_contents($cache_file);
		$cache_object = json_decode($cache_content,true);
		if(is_array($cache_object)){
			foreach($cache_object as  $item){
				$item_forbiddenType = $item['forbiddenType'];
				$item_forbiddenValue = $item['forbiddenValue'];
				$item_isVaildIp = isVaildIp(intval($item_forbiddenType), $item_forbiddenValue, $ip);
				if ($item_isVaildIp)
					array_push($list,$item);
			}
		}
	}else{
		$region_file = BALANCE_DATA_PATH_REGION.$regionId.BALANCE_FILE_SUFFIX;
		if(@file_exists($region_file)&&@is_file($region_file)){
			$file_content = file_get_contents($region_file);
			$file_object = json_decode($file_content,true);
			if(is_array($file_object)){
				$region_forbiddenType = $file_object['forbiddenType'];
				$region_forbiddenValue = $file_object['forbiddenValue'];
				$region_isVaildIp = isVaildIp(intval($region_forbiddenType), $region_forbiddenValue, $ip);
				if($region_isVaildIp){
					$servers = $file_object['servers'];
					foreach($servers as $server){
						$server_file = BALANCE_DATA_PATH_SERVER.$server.BALANCE_FILE_SUFFIX;
						if(@file_exists($server_file)&&@is_file($server_file)){
							$server_content  = file_get_contents($server_file);
							$server_object = json_decode($server_content,true);
							$server_forbiddenType = $server_object['forbiddenType'];
							$server_forbiddenValue = $server_object['forbiddenValue'];
							$server_isVaildIp = isVaildIp(intval($server_forbiddenType), $server_forbiddenValue, $ip);
							if($server_isVaildIp){
								$processes = $server_object['processes'];
								if(count($processes)>0){
									$process_list = array();
									foreach($processes as $process){
										$process_file = BALANCE_DATA_PATH_PROCESS.$process.BALANCE_FILE_SUFFIX;
										if(@file_exists($process_file)&&@is_file($process_file)){
											$process_content  = file_get_contents($process_file);
											$process_object = json_decode($process_content,true);
											array_push($process_list,$process_object);
										}
									}
									if(count($process_list)>0){
										$process_min = $process_list[0];
										$server_object['host'] = $process_min['host'];
										$server_object['port'] = $process_min['port'];
										$server_object['online'] = $process_min['online'];
										array_push($list, $server_object);
									}
								}						
							}
						}
					}
				}
			}
		}
	}
	$list = array_multisort_sort($list,'order');
	return $list;
}


function cacheRegion($uuid){
	$array = loadByRegion($uuid,"*.*.*.*",false);
	$json = jsonFormat($array);
	$file_cache = BALANCE_CACHE_PATH_REGION.$uuid.BALANCE_FILE_SUFFIX;
	$bytes = @file_put_contents($file_cache,$json);
	$result = array("bytes"=>$bytes,"file"=>$file_cache);
	return $result;
}