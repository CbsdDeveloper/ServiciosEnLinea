<?php namespace prevencion\app;
/**
 * Description of index
 * index.php hace referencia a una aplicación interna app/index.php la cual es la funcionalidad
 * en su totalidad del sistema, este método ayuda a mantener un directori base la carpeta raiz 
 * de un servidor (directorio) pero embeber una aplicación.
 * @author Lalytto
**/

// DEFINIR LA ZONA HORARIA
date_default_timezone_set('America/Guayaquil');
define('DS',DIRECTORY_SEPARATOR);
define('SRC_PATH','app/src/');
define('LOCAL_SRC_PATH','/' . DS . 'src' . DS);
define('VIEW_PATH', 'prevencion/views' . DS);

/*
 * DECLARACION DE CLASE INTERFAZ
 */
class app extends controller {
	
	private $page;
	private $path;
	
	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct(){
		// INCIAR SESION
		session::start();
		// SETTEAR NOMBRE DE SESION
		session::setKey('prevencion');
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	/*
	 * AUTOCARGA DE INTERFAZ
	 */
	public function AutoLoader(){
		// VALIDAR LA SESION
		$this->path=(session::existe())?'template':'main';
		// INGRESO PLANTILLA
		$this->page=view::decodeView(VIEW_PATH,$this->path);
		// INGRESO DE VISTAS
		$this->prepareContainer();
		// IMPRIMIR PLANTILLA
		return $this->page;
	}
	
	/*
	 * INSTANCIA DE CONTENEDOR
	 */
	private function prepareContainer(){
		// INGRESO DE DATOS DE SISTEMA
		foreach($this->findAll($this->getSQLSelect('sistema'))[0] as $key=>$val){
			$this->page=view::insertView('/{{'.$key.'}}/',$val,$this->page);
		}
		// PARAMETROS DE CONFIGURACIÓN
		foreach($this->configDev() as $key=>$val){
			$this->page=view::insertView('/{{'.$key.'}}/',$val,$this->page);
		}	
	}
	
}