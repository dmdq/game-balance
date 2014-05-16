<?php
require dirname(__FILE__) . '/../vendor/autoload.php';

function get_uuid(){
	return strtoupper(md5(guid()));
}
function guid(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(0)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(0);// "}"
        return $uuid;
    }
}
/** 
 * Indents a flat JSON string to make it more human-readable. 
 * @param string $json The original JSON string to process. 
 * @return string Indented version of the original JSON string. 
 */
function indent($json) {

	$result = '';
	$pos = 0;
	$strLen = strlen($json);
	$indentStr = ' ';
	$newLine = "\n";
	$prevChar = '';
	$outOfQuotes = true;

	for ($i = 0; $i <= $strLen; $i++) {

		// Grab the next character in the string. 
		$char = substr($json, $i, 1);
		// Are we inside a quoted string? 
		if ($char == '"' && $prevChar != '\\') {
			$outOfQuotes = !$outOfQuotes;
			// If this character is the end of an element, 
			// output a new line and indent the next line. 
		} else
			if (($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos--;
				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}
		// Add the character to the result string. 
		$result .= $char;
		// If the last character was the beginning of an element, 
		// output a new line and indent the next line. 
		if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
			$result .= $newLine;
			if ($char == '{' || $char == '[') {
				$pos++;
			}
			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}
		$prevChar = $char;
	}
	return stripslashes($result);
}

function getIP()
{
	global $ip;
	if (getenv("HTTP_CLIENT_IP"))
		$ip = getenv("HTTP_CLIENT_IP");
	else if(getenv("HTTP_X_FORWARDED_FOR"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	else if(getenv("REMOTE_ADDR"))
		$ip = getenv("REMOTE_ADDR");
	else $ip = "Unknow";
	
	return $ip;
}

/**
 * 校验是否是IP基本格式
 * 
 * @param ip
 */
function isTrueIpFormat($ip) {
	$rule = '/^([1-9]|[1-9]\\d|1\\d{2}|2[0-4]\\d|25[0-5])(\\.(\\d|[0-9]\\d|1\\d{2}|2[0-4]\\d|25[0-5])){3}$/';
	return count(explode(".", $ip)) == 4 && preg_match($rule, str_replace("*", "1", $ip));
}

/**
 * 校验指定IP是否在某个名单或者区间内
 * 
 * @param list
 *            校验的列表
 * @param between
 *            校验的区间
 * @param ip
 *            等待校验的ip
 */
function isVaild($list, $between, $ip) {
	if (!isTrueIpFormat($ip)) {
		return false;
	}
	$fields = explode(".", $ip);
	$isWhite = false;
	foreach ($list as $key => $value) {
		if ($isWhite)
			return $isWhite;
		$flag = true;
		if (isTrueIpFormat($value)) {
			$srcs = explode(".", $value);
			for ($i = 0; $i < 4; $i++) {
				$src = $srcs[$i];
				$flag &= "*" == $src ? true : $src == $fields[$i];
			}
			$isWhite |= $flag;
		}
	}
	foreach ($between as $key => $value) {
		if ($isWhite)
			return $isWhite;
		$values = explode("-", $value);
		$star = trim($values[0]);
		$end = trim($values[1]);

		if (isTrueIpFormat($star) && isTrueIpFormat($end)) {
			$srcStar = explode(".", $star);
			$srcEnd = explode(".", $end);
			$flag = true;
			for ($i = 0; $i < 4; $i++) {
				if (!$flag) {
					break;
				}
				$field = intval($fields[$i]);
				$filedStar = $srcStar[$i];
				$flag &= "*" == $filedStar ? true : $field >= intval($filedStar);
			}
			if ($flag) { // 如果最小区间匹配则匹配最大区间
				for ($i = 0; $i < 4; $i++) {
					$field = intval($fields[$i]);
					$filedEnd = $srcEnd[$i];
					if ("*" == $filedEnd) {
						$flag &= true;
					} else {
						if ($field < intval($filedEnd)) {
							$flag &= true;
							break;
						} else
							if ($field == intval($filedEnd)) {
								$flag &= true;
							} else {
								$flag &= false;
								break;
							}
					}
				}
			}
			$isWhite |= $flag;
		}
	}
	return $isWhite;
}

/**
 * 校验ip是否可以正常加载数据
 * 
 * @param channel
 * @param forbiddenType
 * @param id
 * @return
 */
function isVaildIp($forbiddenType, $forbiddenValue, $ip) {
	$flag = true;
	if (0 == $forbiddenType) {
		$flag = true;
	} else
		if (1 == $forbiddenType) { // 白名单模式
			$forbidden_item_value_array = explode(",", $forbiddenValue);

			$balance_forbidden_list = array ();

			$balance_forbidden_list_between = array ();

			foreach ($forbidden_item_value_array as $forbidden_item_index => $forbidden_item_value) {
				if (strpos($forbidden_item_value, "-") != false) {
					array_push($balance_forbidden_list_between, $forbidden_item_value);
				} else {
					array_push($balance_forbidden_list, $forbidden_item_value);
				}
			}
			$flag = isVaild($balance_forbidden_list, $balance_forbidden_list_between, $ip);

		} else
			if (2 == $forbiddenType) { // 黑名单模式
				$forbidden_item_value_array = explode(",", $forbiddenValue);

				$balance_forbidden_list = array ();

				$balance_forbidden_list_between = array ();

				foreach ($forbidden_item_value_array as $forbidden_item_index => $forbidden_item_value) {
					if (strpos($forbidden_item_value, "-") != false) {
						array_push($balance_forbidden_list_between, $forbidden_item_value);
					} else {
						array_push($balance_forbidden_list, $forbidden_item_value);
					}
				}
				$flag = !isVaild($balance_forbidden_list, $balance_forbidden_list_between, $ip);
			}
	return $flag;
}

function array_sort($arr, $keys, $type = 'asc') {
	$keysvalue = array ();
	$new_array = array ();
	foreach ($arr as $k => $v) {
		$keysvalue[$k] = $v[$keys];
	}
	if ($type == 'asc') {
		asort($keysvalue);
	} else {
		arsort($keysvalue);
	}
	reset($keysvalue);
	foreach ($keysvalue as $k => $v) {
		$new_array[$k] = $arr[$k];
	}
	return $new_array;
}


function array_multisort_sort($data,$keyName,$sort_type = SORT_ASC){
	if(!is_array($data)||0==count($data)){
		return array();
	}
	// 取得列的列表
	foreach ($data as $key => $row) {
	    $volume[$key]  = $row[$keyName];
	}

// 将数据根据 volume 降序排列，根据 edition 升序排列
// 把 $data 作为最后一个参数，以通用键排序
array_multisort($volume, $sort_type , $data);

return $data;
}

define('PHPREDIS_ADMIN_PATH', dirname(__DIR__));

// Undo magic quotes (both in keys and values)
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
  $process = array(&$_GET, &$_POST);

  while (list($key, $val) = each($process)) {
    foreach ($val as $k => $v) {
      unset($process[$key][$k]);

      if (is_array($v)) {
        $process[$key][stripslashes($k)] = $v;
        $process[] = &$process[$key][stripslashes($k)];
      } else {
        $process[$key][stripslashes($k)] = stripslashes($v);
      }
    }
  }

  unset($process);
}

// These includes are needed by each script.
if(file_exists(PHPREDIS_ADMIN_PATH . '/includes/config.inc.php')){
  require_once PHPREDIS_ADMIN_PATH . '/includes/config.inc.php';
}else{
  require_once PHPREDIS_ADMIN_PATH . '/includes/config.sample.inc.php';
}
require_once PHPREDIS_ADMIN_PATH . '/includes/functions.inc.php';
require_once PHPREDIS_ADMIN_PATH . '/includes/page.inc.php';

if (isset($config['login'])) {
  require_once PHPREDIS_ADMIN_PATH . '/includes/login.inc.php';
}




if (isset($login['servers'])) {
  $i = current($login['servers']);
} else {
  $i = 0;
}


if (isset($_GET['s']) && is_numeric($_GET['s']) && ($_GET['s'] < count($config['servers']))) {
  $i = $_GET['s'];
}

$server = $config['servers'][$i];
$server['id'] = $i;


if (isset($login, $login['servers'])) {
  if (array_search($i, $login['servers']) === false) {
    die('You are not allowed to access this database.');
  }

  foreach ($config['servers'] as $key => $ignore) {
    if (array_search($key, $login['servers']) === false) {
      unset($config['servers'][$key]);
    }
  }
}


if (!isset($server['db'])) {
  $server['db'] = 0;
}

if (!isset($server['filter'])) {
  $server['filter'] = '*';
}

// Setup a connection to Redis.
$redis = new Predis\Client('tcp://'.$server['host'].':'.$server['port']);

if (isset($server['auth'])) {
  if (!$redis->auth($server['auth'])) {
    die('ERROR: Authentication failed ('.$server['host'].':'.$server['port'].')');
  }
}


if ($server['db'] != 0) {
  if (!$redis->select($server['db'])) {
    die('ERROR: Selecting database failed ('.$server['host'].':'.$server['port'].','.$server['db'].')');
  }
}

?>
