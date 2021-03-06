<?php

/**
 * 删除数组中指定元素
 */
function array_remove_value(& $arr, $var) {
	foreach ($arr as $key => $value) {
		if (is_array($value)) {
			array_remove_value($arr[$key], $var);
		} else {
			$value = trim($value);
			if ($value == $var) {
				unset ($arr[$key]);
			} else {
				$arr[$key] = $value;
			}
		}
	}
}

/** Json数据格式化
* @param  Mixed  $data   数据
* @param  String $indent 缩进字符，默认4个空格
* @return JSON
*/
function jsonFormat($data, $indent=null){
    // 对数组中每个元素递归进行urlencode操作，保护中文字符
    array_walk_recursive($data, 'jsonFormatProtect');
    // json encode
    $data = json_encode($data);
    // 将urlencode的内容进行urldecode
    $data = urldecode($data);
    
    // 缩进处理
    $ret = '';
    $pos = 0;
    $length = strlen($data);
    $indent = isset($indent)? $indent : '  ';
    $newline = "\n";
    $prevchar = '';
    $outofquotes = true;
    for($i=0; $i<=$length; $i++){
        $char = substr($data, $i, 1);
        if($char=='"' && $prevchar!='\\'){
            $outofquotes = !$outofquotes;
        }elseif(($char=='}' || $char==']') && $outofquotes){
            $ret .= $newline;
            $pos --;
            for($j=0; $j<$pos; $j++){
                $ret .= $indent;
            }
        }
        $ret .= $char;
        if(($char==',' || $char=='{' || $char=='[') && $outofquotes){
            $ret .= $newline;
            if($char=='{' || $char=='['){
                $pos ++;
            }
            for($j=0; $j<$pos; $j++){
                $ret .= $indent;
            }
        }
        $prevchar = $char;
    }
    return $ret;
}

/** 将数组元素进行urlencode
* @param String $val
*/
function jsonFormatProtect(&$val){
    if($val!==true && $val!==false && $val!==null){
       if(is_string($val)){
	        $val = urlencode($val);
		}
    }
}
/**
 * 返回数组的维度
 * @param  [type] $arr [description]
 * @return [type]      [description]
 */
function arrayLevel($arr){
    $al = array(0);
    function aL($arr,&$al,$level=0){
        if(is_array($arr)){
            $level++;
            $al[] = $level;
            foreach($arr as $v){
                aL($v,$al,$level);
            }
        }
    }
    aL($arr,$al);
    return max($al);
}
/**
 * 过滤数组中的某个键
 */
function fiterKeys($array,$filterKeys){
	$arrayLevel = arrayLevel($array);
	if($arrayLevel==1){
		return array_diff_key( $array, array_flip( $filterKeys ) );
	}else{
		foreach ( $array as $key => $value ) {
       		$array[$key] = array_diff_key( $value, array_flip( $filterKeys ) );
		}
		return $array;
	}
}


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
				$field = $fields[$i];
				$flag &= ("*" == $src||"*"==$field) ? true : $src == $field;
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

function format_html($str) {
  return htmlentities($str, ENT_COMPAT, 'UTF-8');
}


function format_ago($time, $ago = false) {
  $minute = 60;
  $hour   = $minute * 60;
  $day    = $hour   * 24;

  $when = $time;

  if ($when >= 0)
    $suffix = 'ago';
  else {
    $when = -$when;
    $suffix = 'in the future';
  }

  if ($when > $day) {
    $when = round($when / $day);
    $what = 'day';
  } else if ($when > $hour) {
    $when = round($when / $hour);
    $what = 'hour';
  } else if ($when > $minute) {
    $when = round($when / $minute);
    $what = 'minute';
  } else {
    $what = 'second';
  }

  if ($when != 1) $what .= 's';

  if ($ago) {
    return "$when $what $suffix";
  } else {
    return "$when $what";
  }
}


function format_size($size) {
  $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

  if ($size == 0) {
    return '0 B';
  } else {
    return round($size / pow(1024, ($i = floor(log($size, 1024)))), 1).' '.$sizes[$i];
  }
}


function str_rand($length) {
  $r = '';

  for (; $length > 0; --$length) {
    $r .= chr(rand(32, 126)); // 32 - 126 is the printable ascii range
  }

  return $r;
}

