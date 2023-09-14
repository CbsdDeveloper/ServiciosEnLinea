<?php namespace app;
use api as ini;
use controller as ctrl;
/** 
 * Description of app
 * Página de inicio (ruta principal) permite preparar el conjunto de clases
 * que se van a utilizar durante la ejecución del sistema, además de extraer los
 * parámetros de para establecer las conexiones de acuerdo al usuario
 * @author Lalytto
 */
class app {
	
	/*
	 * AUTOLOADER DE PÁGINA
	 */
	public static function AutoLoader(){
		// CONFIGURACIÓN PERSONALIZADA PHP v5.5+
		require_once 'api/conf.ini.php';
		// CREATE RESTFULL
		extract(self::getURI());
		// RUTA DE DEBUG DE APLICACIÓN
		ini_set("error_log", "src/debug/$sys-php-error.log");
		// TEST DE ACCESOS PERMITIDOS
		if(!in_array($sys,loadConfig('sudo')))return301("Acceso no autorizado - Modulo");
		// AUTOLOADER DE CLASES
		spl_autoload_register(function($clase){
			$clase = str_replace("\\","/",$clase);
			// echo "<pre>$clase</pre>";
			if(!file_exists("$clase.php")) return FALSE;
			else require_once "$clase.php";
		});
		try{
			// INICIAR SESIONES DE PHP
			ini\session::start();
			// SETTEAR NOMBRE DE MODULO COMO CLAVE DE SESIÓN
			ini\session::setKey($sys);
			// INSTANCIA DE CONTROLADOR DE RUTAS - FILTRO DE MÓDULOS
			new ctrl\routeController;
		} catch(Exception $e) {
			echo $e->getMessage();
		}	exit;
	}
	
	/*
	 * DECODIFICAR URL DE ACCESO
	 */
	private static function getURI(){
		// DECODIFICAR RUTA DE ACCESO 
		$params=explode("/",$_GET['_url']);
		// VALIDAR RUTA PARA REPORTES
		if(isset($_GET['r'])) return $_GET;
		// VALIDAR CANTIDAD DE PARÁMETROS ENVIADOS
		if(count($params)>=3 || isset($_GET['qry'])){
			// CONEXIÓN A LA BASE DE DATOS
			$request=array('sys'=>$params[1],'db'=>$params[2]);
			// VALIDAR ID DE ENTIDAD
			if(count($params)>3)$request['serial']=$params[3];
			// RETORNAR CONSULTA
			return $request;
		} 
		// RETORNAR PARÁMETROS INCOMPLETOS
		return301('Parámetros inclompletos!');
	}
	
}