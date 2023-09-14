<?php namespace app;
/**
 * Description of controller
 * controller.php permite heredar parte de los métodos a controladores como usuario
 * conexión de datos, los cuales realizan las consultas respectivas
 * ***************************** FUNCIONES *****************************
 * 1. Permite establecer conexión con la base de datos (cadena de conexión)
 *		- Conexión de datos
 *		- Métodos para acceder a los registros
 * 2. Obtener parametrización del sistema
 *		- Información del sistema
 *		- Parametrización de formularios y resoluciones imprimibles
 * 3. Obtener información en tiempo de ejecución del sistema (indispensable para sesión de usuarios)
 *
 */
class controller extends connectDB {
	
	// Método 1: Constructor
	public function __construct(){
		parent::__construct();
	}
	
	// **********************************************************************************************
	// *************************** MÉTODOS PARA EL PARÁMETROS DEL SISTEMA ***************************
	// **********************************************************************************************
	// Método 2: Obtiene la fecha del sistema en base a la ubicación 
	public function getConfig($index,$file='config'){
		$json=json_decode(file_get_contents(SRC_PATH."config/$file.json"),true);
		return is_bool($index)?$json:$this->joinCrudMngr($index);
	}
	private function joinCrudMngr($index){
		$config=json_decode(file_get_contents(SRC_PATH."config/config.json"),true);
		$crud=json_decode(file_get_contents(SRC_PATH."config/crud.json"),true);
		return array_merge($config,$crud)[$index];
	}
	public function setConfig($data,$file='model'){
		$myFile=SRC_PATH."config/$file.json";
		$fh=fopen($myFile,'w');
		if($fh){
			fwrite($fh, json_encode($data,JSON_PRETTY_PRINT));
			fclose($fh);
		}
	}
	public function getFecha($formato='Y-m-d'){
		$dias=array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
		$meses=array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
 		if($formato=='complete')return $dias[date('w')].", ".date('d')." de ".$meses[date('n')-1]. " del ".date('Y');
		elseif($formato=='full')return date('"l jS \of F Y h:i:s A');
		else return date($formato);
	}
	// Retornar los parámetros del sistema
	public function getSetting(){
		return $this->findAll($this->getSQLSelect("params"));
	}
	// PARÁMETROS DEV
	public function configDev(){
		$config=$this->getConfig(true,'dev');
		return array_merge($config['main'],$config[session::$sysName]);
	}
	
	
	// **********************************************************************************************
	// ************************** MÉTODOS PARA RETORNAR MENSAJE: PHP - AJAX *************************
	// **********************************************************************************************
	// Setear mensaje en formato array para retornar como json
	public function setJSON($msg, $est=false){
		return array('mensaje'=>$msg, 'estado'=>$est);
	}
	// Retornar mensaje a ajax
	public function getJSON($json){
		// Json AutoDeclare
		if(!is_array($json)) $json=$this->setJSON($json);
		// json es un conjunto de datos presentados como un archivo json
		echo json_encode($json,JSON_PRETTY_PRINT);
		exit;
	}
	public function e301(){
		$this->getJSON($this->setJSON('¡Lamentablemente no cuenta con permisos para realizar la operación!'));
	}
	// Retornar mensaje: No se encuetra la petición que desea realizar
	public function failJSON($metodo){
		$this->getJSON($this->setJSON("La acción que desea ralizar no se encuentra en los métodos: $metodo"));
	}
	public function testEmpty($data){
		return (empty($data)||$data==''||!isset($data))?false:true;
	}
	// Desvincular array
	public function recursive_replace($row,$path){
		foreach($row as $key=>$val){
			$path = (is_array($val))?$this->recursive_replace($val,$path):preg_replace('/{{'.$key.'}}/', $val, $path); 
		}	return $path;
	}
	
}
