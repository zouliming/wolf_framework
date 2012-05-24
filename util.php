<?php 

#================================================================
#===EasyTemplate 对象维持
function tt(){
	static $tt;
	if(!$tt){
		$t = new EasyTemplate();
	}
	return $tt;
}

#================================================================
#===EasyDBAccess 对象维持
function dba(){
	static $dba;
	if(!$dba){
		global $db_conf;
		$dba = new EasyDBAccess($db_conf);
	}
	return $dba;
}
function get_dba($id){
	static $dba_buf=array();
	if(!isset($dba_buf[$id])){
		$conf=conf("dba_pool");
		if(!is_array($conf) || !isset($conf[$id])){
			app_die(__LINE__."@".__FILE__.":"."CONF NOT FOUND");
		}
		$dba_buf[$id]=new EasyDBAccess($conf[$id]);
		//echo "connect\n";
	}else{
		//echo "use buf\n";
	}
	return $dba_buf[$id];
}
function select_dba($tb=NULL,$key=NULL){
	if(in_array($tb,array("xiaonei_friend","fight"))){
		$key=intval(intval($key)/10000)+1;
		if($key>count(conf("dba_pool"))-1){
			$key=0;
		}
		return get_dba($key);
	}else{
		return get_dba(0);
	}
}

#================================================================
#===Validator 对象维持
function va(){
	static $va;
	if(!$va){
		$va = new Validator();
	}
	return $va;
}

#================================================================
#===输出页面
function view($vars=array(), $tmpl_name=NULL, $layout=NULL){
	$tt = new EasyTemplate($vars);
	if($tmpl_name){
		$tt->set_tmpl($tmpl_name);
	}
	if($layout){
		$tt->set_layout($layout);
	}
	return $tt->process();
}

function PicBig2Small($bigpic){
	$num = strrpos($bigpic,'/');
	if($num){
		$new=substr($bigpic,$num);
		$temp = explode(".",$new);
		if(count($temp)>1){
			return substr($bigpic,0,$num).$temp[0]."_small.".$temp[1];
		}
		return $bigpic;
	}else{
		$temp = explode(".",$bigpic);
		if(count($temp)>1){
			return $temp[0]."_small.".$temp[1];
		}
		return $bigpic;
	}
}
function getLvExp($lv){
	$exp = 0;
	$levelConf=conf('level');
	if($lv<=20){
		$exp = $levelConf[0][$lv]['points'];
	}elseif($lv<=40){
		$exp = $levelConf[0][20]['points']+($lv-20)*50000;
	}else{
		$exp = $levelConf[0][20]['points']+20*50000+($lv-40)*200000;
	}
	return $exp;
}

#================================================================
#===执行任务相关
function call_worker_func(){
	$dba=dba();
	$args=func_get_args();
	$name=array_shift($args);
	$dba->exec("insert into worker_jobs values (default,?,?)",$name,serialize($args));
}
function do_worker_func(){
	$dba=dba();
	$rows=$dba->select("select * from worker_jobs order by id limit 20");
	foreach($rows as $row){
		$affected_rows=$dba->execute("insert ignore into worker_jobs_history values(?,?,?,0,unix_timestamp())",$row["id"],$row["name"],$row["args"]);
		if($affected_rows==1){
			$dba->execute("delete from worker_jobs where id=?",$row["id"]);
			#取到任务了
			$status=call_user_func("call_user_func_array",$row["name"],unserialize($row["args"]));
			if($status!=0){
				$dba->execute("update worker_jobs_history set stat=?,update_time=unix_timestamp() where id=?",$status,$row["id"]);
			}
			return 1;
		}
	}
	return 0;
}

#================================================================
#===是否需要使用session
if(!(defined("NO_SESSION") && NO_SESSION) && key_exists("SERVER_NAME",$_SERVER)){
	session_start();
}

?>