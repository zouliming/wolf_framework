<?php
$_Timer_pool=array();
function timer($txt){
	
	global $_Timer_pool;
	if(strlen($txt)>100000||count($_Timer_pool)>200){
		return 0;
	}
	$t=split(" ",microtime());
	return array_push($_Timer_pool,array($t[0],$t[1],$t[0],$t[1],$txt))-1;
}
function end_timer($id){
	global $_Timer_pool;
	if($id==0){
		return;
	}
	$t=split(" ",microtime());
	$_Timer_pool[$id][2]=$t[0];
	$_Timer_pool[$id][3]=$t[1];
}
timer("application start");

function _TimerOnExit(){
	timer("application end");
}
register_shutdown_function('_TimerOnExit');

function _TimerReport(){
	global $_Timer_pool;
	
	if(!(defined("TimerMax")&&defined("TimerLogDir"))){
		return;
	}

	$first_item=$_Timer_pool[0];
	$last_item =$_Timer_pool[count($_Timer_pool)-1];
	$execution_time=intval(($last_item[3]-$first_item[1])*1000000+($last_item[2]*1000000-$first_item[0]*1000000));
	if($execution_time<=TimerMax){
		return;
	}
	$s="";
	$last_time=NULL;
	foreach($_Timer_pool as $r){
		if($last_time===NULL){
			$last_time=array($r[0],$r[1]);
		}
		#print unknown
		$t=intval(($r[1]-$last_time[1])*1000000+($r[0]*1000000-$last_time[0]*1000000));
		
		if($t>0){
			$s.="$t unknown\n";
		}
		#print row
		$last_time=array($r[2],$r[3]);
		$t=intval(($last_time[1]-$r[1])*1000000+($last_time[0]*1000000-$r[0]*1000000));
		$s.="$t ".$r[4]."\n";
	}
	ob_start();
	phpinfo();
	$s.=ob_get_contents();
	ob_end_clean();
	chdir(TimerLogDir);
	file_put_contents(time().".txt",$s);
}
register_shutdown_function('_TimerReport');