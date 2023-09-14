<?php namespace app;
/**
 * Description of index
 * index.php hace referencia a una aplicación interna app/index.php la cual es la funcionalidad
 * en su totalidad del sistema, este método ayuda a mantener un directori base la carpeta reiz 
 * de un servidor (directorio) pero embeber una aplicación.
 * @author Lalytto
**/
// DEFINIR LA ZONA HORARIA
date_default_timezone_set('America/Guayaquil');
define('DS',DIRECTORY_SEPARATOR);
define('SRC_PATH','../app/src/');
define('LOCAL_SRC_PATH','/' . DS . 'src' . DS);
define('VIEW_PATH', 'views' . DS);
class app extends controller {
	
	private $page;
	private $path;
	
	public function __construct(){
		session::start();
		session::setKey('subjefatura');
		parent::__construct();
	}
	
	public function AutoLoader(){
		$this->path=(session::existe())?'template':'main';
		return $this->prepareHTML();
	}
	
	private function prepareHTML(){
		$this->page=view::decodeView(VIEW_PATH,$this->path);
		$this->prepareContainer();
		echo $this->page;
	}
	
	private function prepareContainer(){
		$row=array();
		foreach($this->findAll($this->getSQLSelect('sistema'))[0] as $key=>$val){
			$this->page=view::insertView('/{{'.$key.'}}/',$val,$this->page);
		}	
		foreach($this->configDev() as $key=>$val){
			$this->page=view::insertView('/{{'.$key.'}}/',$val,$this->page);
		}	
	}
	
	private function getURI(){
		if(isset($_GET['_url'])) $params=explode("/",$_GET['_url']);
		else $params=array('/','');
		$pageConf=array('page'=>$params[1]);
		if(count($params)>2)$pageConf['producto']=$params[2];
		return $pageConf;
	}
	
}