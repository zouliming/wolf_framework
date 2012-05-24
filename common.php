<?php
	//FJ_JOB
define("FJ_SUCC",1);
define("FJ_FAIL",2);
define("FJ_STOP",3);
define("FJ_NO_JOB",4);
define("FJ_CONTINUE",5);


//ERROR CODE

/*
0  运行正常

1  正常的情况下也有一定概率发生的错误,比如不稳定的网络连接，和环境错误的区别是后者需要人工恢复
   A 返回错误
   B 告诉操作失败
   C 过段时间重试

2  所传入的参数不符合条件，无法完成一个成功的操作
   A 返回错误
   B 告诉操作失败
   C SKIP
   
3  环境错误，由于数据库连接不上，或者其他服务没开，或者帐号错误之类，需要等待错误解决
   A DIE
   B INTERNAL SERVER ERROR
   C 人工维护环境

4  程序不一致，但是出于原因的不可预防，或者很难预防，很难完全杜绝
   A 返回错误
   B 告诉操作失败
   C SKIP

5  参数错误，参数传得牛头不对马嘴，绝对是程序写的BUG
   A 
   B INTERNAL SERVER ERROR
   C 修改BUG
*/

define("RET_OK",0);
define("RET_ERROR",1);
define("RET_RUN_FAIL",2);
define("RET_ENV_ERROR",3);
define("RET_CONSISTENT_ERROR",4);
define("RET_BUG",5);

define("UTF8_CHARSET","utf-8");
define("ASCII_CHARSET","gb18030");

define("SOURCE_CHARSET",UTF8_CHARSET);

define("UID",sprintf("%08s",base_convert(crc32(uniqid(rand(), true)),10,16)));

function error_info($info=NULL){
	static $error_info="";
	if($info===NULL){
		return $error_info;
	}else{
		$error_info=$info;
		if(DEBUG){
			echo $error_info;
			exit;
		}else{
			@file_put_contents(ROOT.PATH_CACHE.'error.log', $error_info);
		}
		return $error_info;
	}
}

function app_die($info=NULL){
	if($info){
		error_info($info);
	}
	header('Location:'.WEBROOT.'error.php');
	exit;
}

#空函数,消除某些变量没有使用的警告时用
function foo(){
	
}


function a2u($str){
	return $str===false?false:@iconv(ASCII_CHARSET,UTF8_CHARSET,$str);
}

function u2a($str){
	return $str===false?false:@iconv(UTF8_CHARSET,ASCII_CHARSET,$str);
}

function a2o($str){
	return $str===false?false:@iconv(ASCII_CHARSET,OUTPUT_CHARSET,$str);
}

function u2o($str){
	return $str===false?false:@iconv(UTF8_CHARSET,OUTPUT_CHARSET,$str);
}

function s2a($str){
	return $str===false?false:@iconv(SOURCE_CHARSET,ASCII_CHARSET,$str);
}

function s2u($str){
	return $str===false?false:@iconv(SOURCE_CHARSET,UTF8_CHARSET,$str);
}

function s2o($str){
	return $str===false?false:@iconv(SOURCE_CHARSET,OUTPUT_CHARSET,$str);
}

function u2s($str){
	return $str===false?false:@iconv(UTF8_CHARSET,SOURCE_CHARSET,$str);
}

function tmpl($name){
	if(!is_string($name)){
		app_die("TMPL NOT SET");
	}
	if(defined("ROOT")&&defined("PATH_VIEW")){
		return ROOT.PATH_VIEW.$name.".php";
	}else{
		return $name.".php";
	}
}


function curl_set_default_option($ch){
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
	curl_setopt($ch,CURLOPT_COOKIEJAR,null);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_TIMEOUT,3);
	curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)");	
}

function curl_get($p_ch,$url=null,$charset=null){
	$use_curl=extension_loaded("curl");
	if(is_resource($p_ch)){
		$ch=$p_ch;	
	}else{
		$charset=$url;
		$url=$p_ch;
		if($use_curl){
			$ch=curl_init();
			curl_set_default_option($ch);
		}
	}

	if($use_curl){
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_HTTPGET,1);
		for($i=5;$i-->0;){
			$content=curl_exec($ch);
			if($content!==false){
				break;
			}else{
				sleep(2);
			}
		}
	}else{
		$content=@file_get_contents($url);
	}

	if($content===false){
		if(!is_resource($p_ch) && $use_curl ){
			curl_close($ch);
		}
		return false;
	}else{
		if($use_curl){
			$http_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
			if(!is_resource($p_ch)){
				curl_close($ch);
			}
			if($http_code>=400){
				return false;
			}
		}
		if($charset!==NULL){
			return @iconv($charset,UTF8_CHARSET,$content);
		}else{
			return $content;
		}
	}
}

function interval($millisecond){
	static $last_time=NULL;
	if($last_time===NULL){
		$last_time=microtime(true);
		return;
	}
	$now=microtime(true);
	if(($now-$last_time)*1000>$millisecond){
		$last_time=$now;
	}else{
		$last_time+=$millisecond/1000;
		usleep( ($last_time-$now)*1000000  );
	}
}

function suggest_password($pwchars=NULL,$passwordlength=NULL){
	if($pwchars===NULL){
		$pwchars='abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHJKLMNOPQRSTUVWYXZ';
	}
	if($passwordlength===NULL){
		$passwordlength=16;
	}
    $passwd='';
    for($i = 0;$i<$passwordlength;$i++){
        $passwd.= substr($pwchars,rand(0,strlen($pwchars)-1),1);
    }
    return $passwd;
}

function inet_aton($ip){
	if(is_string($ip) && preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/',$ip,$m) && $m[1]>=0 && $m[1]<256 && $m[2]>=0 && $m[2]<256 && $m[3]>=0 && $m[3]<256 && $m[4]>=0 && $m[4]<256){
		return $m[1] * 256 * 256 * 256 + $m[2] * 256 * 256 + $m[3] * 256 + $m[4];
	}else{
		return null;
	}
}

function stripslashes_deep($value){ 
	return is_array($value)?array_map('stripslashes_deep',$value):stripslashes($value);
}

if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())||(ini_get('magic_quotes_sybase')&&(strtolower(ini_get('magic_quotes_sybase'))!="off")) ){ 
    $_POST    = stripslashes_deep($_POST);
    $_GET     = stripslashes_deep($_GET);
    $_COOKIE  = stripslashes_deep($_COOKIE);
    $_REQUEST = stripslashes_deep($_REQUEST);
}

function guess_webroot(){
	if(!key_exists("SERVER_NAME", $_SERVER)){
		#CLI
		$script=realpath($_SERVER["SCRIPT_NAME"]);
		if(strpos($script,ROOT)==0){
			return array("/",substr($script,strlen(ROOT)));
		}else{
			app_die(__LINE__."@".__FILE__.":"."BUG");
		}
	}

	#WEBPAGE
	$root=str_replace("\\", "/",ROOT);
	for($i=strlen($_SERVER['SCRIPT_NAME']);$i-->1;){
		#echo "strrpos(".var_export($root,true).",substr(".var_export($_SERVER['SCRIPT_NAME'],true).",0,$i)=";
		$pos=strrpos($root,substr($_SERVER['SCRIPT_NAME'],0,$i));
		#var_export($pos);
		#echo "<br/>";
		if($pos!==false && $pos+$i== strlen($root) ){
			return array(substr($_SERVER['SCRIPT_NAME'],0,$i),substr($_SERVER['SCRIPT_NAME'],$i));
		}
	}
	app_die(__LINE__."@".__FILE__.":"."BUG");
}

//error_reporting(E_ALL);

?>