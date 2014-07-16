<?php
require_once 'includes/common.inc.php';
require_once 'includes/functions.inc.php';
require_once 'includes/balance.redis.inc.php';
//gk = get Key
function gk($key) {
	return $_REQUEST[$key];
}
/**
	ne = Not Empty
*/
function ne($key) {
	if (!empty ($_REQUEST[$key])) {
		return true;
	}
	return false;
}

function nea($arrKey) {
	foreach ($arrKey as $key) {
		if (!ne($key)) {
			return false;
		}
	}
	return true;
}
/**
	ne = Not Set
*/
function ns($key) {
	if (isset($_REQUEST[$key])) {
		return true;
	}
	return false;
}

function nsa($arrKey) {
	foreach ($arrKey as $key) {
		if (!ns($key)) {
			return false;
		}
	}
	return true;
}

if (isset ($_REQUEST['method'])) {
	try {
		//header("Content-Type = 'application/json;charset=UTF-8'");
		header('Content-type: text/json ;charset=utf-8');
		$method = $_REQUEST['method'];
		
		if ('addUser' == $method) {
			if(nea(array("args","args2"))){
				$name = gk("args");
				$password = gk("args2");
				echo jsonFormat(addUser($name,$password,022));
			}
		}
		elseif ('addApp' == $method) {
			if(nsa(array("args","args2","args3","args4","args5"))){
				$userName = gk("args");
				$name = gk("args2");
				$order = gk("args3");
				$forbidden_type = gk("args4");
				$forbidden_value = gk("args5");
				echo jsonFormat(addApp($userName, $name,$order,$forbidden_type, $forbidden_value));
			}
		}
		elseif ('addRegion' == $method) {
			if(nsa(array("args","args2","args3","args4","args5"))){
				$parent_uuid = gk("args");
				$name = gk("args2");
				$order = gk("args3");
				$forbidden_type = gk("args4");
				$forbidden_value = gk("args5");
				echo jsonFormat(addRegion($parent_uuid, $name, $order, $forbidden_type, $forbidden_value));
			}
		}
		elseif ('addServer' == $method) {
			if(nsa(array("args","args2","args3","args4","args5","args6"))){
				$parent_uuid = gk("args");
				$name = gk("args2");
				$version = gk("args3");
				$order = gk("args4");
				$forbidden_type = gk("args5");
				$forbidden_value = gk("args6");
				echo jsonFormat(addServer($parent_uuid, $name, $version, $order, $forbidden_type, $forbidden_value));

			}
		}
		elseif ('addProcess' == $method) {
			if(nsa(array("args","args2","args3","args4","args5","args6","args7"))){
				$parentId = gk("args");
				$uuid = gk("args2");
				$order = gk("args3");
				$host = gk("args4");
				$port = gk("args5");
				$usedMemory = gk("args6");
				$online = gk("args7");
				echo jsonFormat(addProcess($parentId, $uuid, $order, $host, $port, $usedMemory, $online));
			}
		}

		elseif ('removeApp' == $method) {
			if(nea(array("args","args2"))){
				$userName = gk("args");
				$uuid = gk("args2");
				echo jsonFormat(removeApp($userName,$uuid));
			}
		}
		elseif ('removeRegion' == $method) {
			if(nea(array("args","args2"))){
				$appId = gk("args");
				$uuid = gk("args2");
				echo jsonFormat(removeRegion($appId,$uuid));
			}
		}
		elseif ('removeServer' == $method) {
			if(nea(array("args","args2"))){
				$regionId = gk("args");
				$uuid = gk("args2");
				echo jsonFormat(removeServer($regionId,$uuid));
			}
		}
		elseif ('removeProcess' == $method) {
			if(nea(array("args","args2"))){
				$serverId = gk("args");
				$uuid = gk("args2");
				echo jsonFormat(removeProcess($serverId,$uuid));
			}
		}
		elseif ('updateApp' == $method) {
			if(nsa(array("args","args2","args3","args4"))){
				$userName = gk("args");
				$uuid = gk("args2");
				$name = gk("args3");
				$order = gk("args4");
				echo jsonFormat(updateApp($userName,$uuid,$name,$order));
			}
		}
		elseif ('updateRegion' == $method) {
			if(nsa(array("args","args2","args3","args4","args5","args6"))){
				$parentId = gk("args");
				$uuid = gk("args2");
				$name = gk("args3");
				$order = gk("args4");
				$forbidden_type = gk("args5");
				$forbidden_value = gk("args6");
				echo jsonFormat(updateRegion($parentId,$uuid,$name,$order,$forbidden_type,$forbidden_value));
			}
		}
		elseif ('updateServer' == $method) {
			if(nsa(array("args","args2","args3","args4","args5","args6","args7","args8"))){
				$parentId = gk("args");
				$uuid = gk("args2");
				$name = gk("args3");
				$order = gk("args4");
				$forbidden_type = gk("args5");
				$forbidden_value = gk("args6");
				$version = gk("args7");
				$status = gk("args8");
				echo jsonFormat(updateServer($parentId,$uuid,$name,$order,$forbidden_type,$forbidden_value,$version,$status));
			}
		}
		elseif ('updateProcess' == $method) {
			if(nsa(array("args","args2","args3"))){
				$serverId = gk("args");
				$uuid = gk("args2");
				$jsonObject = gk("args3");
				echo jsonFormat(updateProcess($serverId,$uuid,$jsonObject));
			}
		}
		elseif ('loadUser' == $method) {
			if(nea(array("args","args2"))){
				$name = gk("args");
				$password = gk("args2");
				echo jsonFormat(loadUser($name,$password));
			}
		}	
		elseif ('loadApps' == $method) {
			if(nea(array("args"))){
				$userName = gk("args");
				$array = load_apps($userName);
				echo jsonFormat($array);
			}
		}
		elseif ('loadRegions' == $method) {
			if(nea(array("args"))){
				$appId = gk("args");
				$array = load_regions($appId);
				echo jsonFormat($array);
			}
		}
		elseif ('loadServers' == $method) {
			if(nea(array("args"))){
				$id = gk("args");
				$array = load_servers($id);
				echo jsonFormat($array);
			}
		}
		elseif ('loadProcesses' == $method) {
			if(nea(array("args"))){
				$serverId = gk("args");
				$array = load_process($serverId);
				echo jsonFormat($array);
			}
		}
		elseif ('loadByRegion' == $method) {
			if(nea(array("args"))){
				$start = time();
				$client_ip = getIp();
				$regionId = gk("args");
				//;
				$array = array();
				for($i=0;$i<1;$i++){
					//$array = loadByRegion($regionId,$client_ip);
					$array = loadTest($regionId,$client_ip);					
				}
				print_r($array);
				$array = fiterKeys($array,array("forbiddenType","forbiddenValue","parent","online","regions","processes"));
				echo jsonFormat($array);
				$end = time();
				echo "\n$start";				
				echo "\n$end";				
			}
		}
		elseif ('restoreFromLocal' == $method) {
			if(nea(array("args"))){
				$userName = gk("args");
				$result = restoreFromLocal($userName);
				echo $result;
			}
		}
		
		//$array = loadByRegion("8f72e2f30b26604e25082ce0d32f36cfddf8", getIP());
		//echo indent(jsonFormat($array, true));
	} catch (Exception $e) {
		die(jsonFormat(array("code"=>0,"msg"=>'ERROR: Can\'t connect to Redis')));
	}
}