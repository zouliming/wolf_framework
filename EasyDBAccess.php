<?php

#===================================
#===Author  : qian.yu            ===
#===Email   : foolfish@gmail.com ===
#===QQ      : 9097939            ===
#===Homepage: www.fishlib.cn     ===
#===================================

#===1.2.1(2009-11-20): when only one param don't replace ? with data
#===1.2.0(2009-08-20): add connect function,it will return false when connect fail,remove halt function for no use at all
#===1.1.2(2009-08-04)

class EasyDBAccess{
	private $link;
	private $query_count=0;
	private $version;
	public $database="";
	private $prefix='';

	private function _connect($db_conf){
		if(function_exists("timer")){$t=timer("connect db ".var_export($db_conf,true));}
		if(isset($db_conf['persistent']) && $db_conf['persistent']) {
			$this->link=@mysql_pconnect($db_conf['host'], $db_conf['usr'], $db_conf['pass']);
		}else{
			$this->link=@mysql_connect($db_conf['host'], $db_conf['usr'], $db_conf['pass'],true);
		}
		
		if(!$this->link) {
			return array(false,'DATABASE CONNECT ERROR');
		}
		
		$this->version= substr(mysql_get_server_info($this->link),0,3);
		
		if($this->version >= '4.1') {
			if($db_conf['encoding']) {
				mysql_query("SET NAMES '".$db_conf['encoding']."'",$this->link);
			}
		}

		if($this->version >= '5.0') {
			mysql_query("SET sql_mode=''",$this->link);
		}

		if(isset($db_conf['database'])&& ($db_conf['database']!="")) {
			if(mysql_select_db($db_conf['database'],$this->link)){
				$this->database=$db_conf['database'];
			}else{
				return array(false,'DATABASE '.$db_conf['database'].'NOT FOUND');
			}
		}
		if(isset($db_conf['prefix'])&& ($db_conf['prefix']!="")) {
			$this->prefix=$db_conf['prefix'];
		}
        if(isset($db_conf['time_zone'])) {
            mysql_query("SET time_zone='".$db_conf['time_zone']."';", $this->link);
        }
        if(function_exists("timer")){end_timer($t);}
        return array(true,'');
	}
	
	function connect($db_conf){
		$dba=new EasyDBAccess(false);
		list($succ,$err)=$dba->_connect($db_conf);
		return $succ?$dba:false;
	}
	
	function EasyDBAccess($db_conf){
		if($db_conf===false){
			return;
		}
		list($succ,$err)=$this->_connect($db_conf);
		if(!$succ){
			function_exists("app_die")?app_die($err):die($err);
		}
	}

	function dbh(){
		return $this->link;
	}
	
	function execute(){
		$sql=$this->sql(func_get_args());
		if(function_exists("timer")){$t=timer("sql $sql");}
		if(!mysql_query($sql,$this->link)) {
			function_exists("app_die")?app_die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql):die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql);
		}
		$r=mysql_affected_rows($this->link);
		if(function_exists("timer")){end_timer($t);}
		return $r;
	}
	
	function exec(){
		$sql=$this->sql(func_get_args());
		if(function_exists("timer")){$t=timer("sql $sql");}
		if(!mysql_query($sql,$this->link)) {
			function_exists("app_die")?app_die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql):die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql);
		}
		if(function_exists("timer")){end_timer($t);}
		return true;
	}
	
	
	function query(){
		$sql=$this->sql(func_get_args());
		if(function_exists("timer")){$t=timer("sql $sql");}
		$result = mysql_query($sql,$this->link);
		if($result==false) {
			function_exists("app_die")?app_die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql):die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql);
		}
		if(function_exists("timer")){end_timer($t);}
		return $result;
	}
	
	function select(){
		$sql=$this->sql(func_get_args());
		if(function_exists("timer")){$t=timer("sql $sql");}
		$result = mysql_query($sql,$this->link);
		if($result==false) {
			function_exists("app_die")?app_die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql):die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql);
		}
		$r=array();
		while ( ( $row = mysql_fetch_array($result, MYSQL_ASSOC) )!=false ) {
			array_push($r,$row);
		}
		mysql_free_result($result);
		if(function_exists("timer")){end_timer($t);}
		return $r;
	}
	
	function select_col(){
		$sql=$this->sql(func_get_args());
		if(function_exists("timer")){$t=timer("sql $sql");}
		$result = mysql_query($sql,$this->link);
		if($result==false) {
			function_exists("app_die")?app_die('Query failed: ' . mysql_error($this->link)):die('Query failed: ' . mysql_error($this->link));
		}
		$r=array();
		while (($row = mysql_fetch_array($result, MYSQL_NUM))!=false) {
			array_push($r,$row[0]);
		}
		mysql_free_result($result);
		if(function_exists("timer")){end_timer($t);}
		return $r;	
	}
	
	function select_row(){
		$sql=$this->sql(func_get_args());
		if(function_exists("timer")){$t=timer("sql $sql");}
		$result = mysql_query($sql,$this->link);
		if($result==false) {
			function_exists("app_die")?app_die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql):die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql);
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		mysql_free_result($result);
		if(function_exists("timer")){end_timer($t);}
		return $row;
	}
	
	function select_one(){
		$sql=$this->sql(func_get_args());
		if(function_exists("timer")){$t=timer("sql $sql");}
		$result = mysql_query($sql,$this->link);
		if($result==false) {
			function_exists("app_die")?app_die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql):die('Query failed: ' . mysql_error($this->link)."\nSQL: ".$sql);
		}
		$row = mysql_fetch_array($result, MYSQL_NUM);
		mysql_free_result($result);
		if(function_exists("timer")){end_timer($t);}
		if($row===false){
			return false;
			#function_exists("app_die")?app_die('BUG select_one:'.$sql):die('BUG select_one:'.$sql);
		}else{
			return $row[0];
		}
	}
	
	#CREATE TABLE RES(ATTRIB VARCHAR(255) NOT NULL,ID INT NOT NULL ,PRIMARY KEY (ATTRIB)) ENGINE=MYISAM
	function id($name,$count=1){
		$affected_rows=$this->execute('UPDATE #PREFIX#RES SET ID=LAST_INSERT_ID(ID+?) WHERE ATTRIB=?',$count,$name);
		if($affected_rows==0){
			$this->execute('INSERT INTO #PREFIX#RES(ATTRIB,ID) VALUES(?,0)',$name);
			$this->execute('UPDATE #PREFIX#RES SET ID=LAST_INSERT_ID(ID+?) WHERE ATTRIB=?',$count,$name);
		}
		return $this->select_one('SELECT LAST_INSERT_ID()');
	}

	function close() {
		return mysql_close($this->link);
	}

	function free($result){
		mysql_free_result($result);
	}

	function sql($args){
		#===1.2.1
		if(count($args)==1){
			return str_replace(
				array("#PREFIX#","#DB#"),
				array($this->prefix,$this->database),
			$args[0]);
		}
		#===end
		$args[0]=str_replace(
			array("#PREFIX#","#DB#","%","?"),
			array($this->prefix,$this->database,"%%","%s"),
		$args[0]);
		for($i=count($args);$i-->1;){
			if($args[$i]===null){
				$args[$i]='null';
			}else{
				$args[$i]="'".mysql_escape_string($args[$i])."'";
			}
		}
		return call_user_func_array('sprintf',$args);
	}
}





