<?php namespace controller;
use api as app;
use service;
/**
 * Description of route
 * route.php crea una validación de las rutas url de acceso de los usuarios, esto significa que cada vez
 * que el usuario desee redireccinarse a un determinado formulario o dirección, tendrá que someterse a las
 * validación de ruta. Como validación primordial se da los requerimientos de permisos de acceso del sistema
 */
class routeController {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $form;
	private $query;
	
	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct() {
		// DECLARAR NOMBRE DE MODULO
		$sys=app\session::$sysName;
		// OBTENER NOMBRE DE SERVICIO DE MODULO
		$q="service\\{$sys}Service";
		// INSTANCIA DE OBJETO A UTILIZAR
		$this->query=new $q;
		// VALIDAR SESIÓN DE USUARIO
		app\session::existe()?$this->sesion():$this->main();
	}
	
	/*
	 * Método: SEGUNDO FILTRO - USUARIO SIN LOGUEAR
	 */
	private function main(){
		// EXTRAER VARIABLES DE SESION
		extract($this->query->getURI());
		// VALIDAR SI HA SIDO ENVIADO EL PARAMETRO Q
		if(isset($qry)) $this->q();
		// VALIDAR SI ES UNA CONEXIÓN A BASE DE DATOS
		elseif(isset($db)) $this->query->executeQuery(array_merge($this->query->getConfig('mainQuery')[$sys],array('POST')));
		// MENSAJE DE SENTENCIA NO DECLARADA
		else $this->query->failJSON('MainCtrl');
	}
	
	/*
	 * Método: SEGUNDO FILTRO - USUARIO LOGUEADO
	 */
	private function sesion(){
		// EXTRAER VARIABLES DE SESION
		extract($this->query->getURI());
		// VALIDAR SI HA SIDO ENVIADO EL PARAMETRO Q
		if(isset($qry)) $this->q();
		// VALIDAR SI ES UNA CONEXIÓN A BASE DE DATOS
		elseif(isset($db)){
			$opList = array_merge($this->query->getConfig('sessionQuery'),$this->query->getConfig('mainQuery'));
			// print_r($opList);
			//foreach($opList as $k=>$v){if($db==$v){$rol=$k+1;}}
			//(in_array($rol,$this->query->getAccesRol()))?$this->query->executeQuery($opList):$this->query->e301();
			$this->query->executeQuery($opList);
		} 
		// MENSAJE DE SENTENCIA NO DECLARADA
		else $this->query->failJSON("MainCtrl");
	}
	
	// Método para acceder a los métodos de conexión de datos
	private function q(){
		extract($_GET);
		$opList = array('logout','testSession');
		if(in_array($qry,$opList))$this->query->$qry();
		elseif($qry=='uploadFile') $this->query->uploaderMng->$qry();
		elseif(in_array($qry,$this->query->getConfig('mailList'))) {
			$mail = new mailController;
			$mail->$qry();
		}	$this->query->failJSON("Query->$qry");
	}
}