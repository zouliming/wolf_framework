<?php
	require_once 'common.php';
	class EasyTemplate{
		private $tmpl_vars;
		private $layout;
		private $tmpl_name;
		
		function EasyTemplate($default_vars=array()){
			$this->layout = 'default';
			$this->tmpl_name = substr(SCRIPT_NAME,0,strlen(SCRIPT_NAME)-4);
			$this->tmpl_vars = $default_vars;
			$this->tmpl_vars["this"] = $this;
		}
		
		function set_layout($name){
			$this->layout = $name;
		}
		
		function set_tmpl($name){
			$this->tmpl_name = $name;
		}
		
		function set(){
			$args = func_get_args();
			$count=count($args);
			if($count==1&&is_array($args[0])){
				$this->tmpl_vars=$args[0]+$this->tmpl_vars;
			}elseif($count==2&&is_scalar($args[0])){
				$this->tmpl_vars[$args[0]]=$args[1];
			}else{
				function_exists("app_die")?app_die("PARAM ERROR"):die("PARAM ERROR");
			}
		}
		
		function process($return=false){
			if($return){
				ob_start();
			}
			extract($this->tmpl_vars,EXTR_OVERWRITE);
			require ROOT.PATH_VIEW.'layout'.SEP.$this->layout.'.layout.php';
			if($return){
				ob_end_flush();
			}
		}
	}
?>