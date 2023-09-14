<?php
	// Desactivar la visualización de errores por parte de php, este proceso se lo realiza con el
	// fin de capturar todas las excepciones de las tareas en el retorno de json a ajaxx al momemnto 
	ini_set('display_errors',0);
	error_reporting(E_ALL);
	// Memoria usada
	ini_set('memory_limit','5120M');
	// Tiempo de carga - segundos
	set_time_limit(300);
	// DEFINIR LA ZONA HORARIA
	date_default_timezone_set('America/Guayaquil');
	// Información de PHP
	if(isset($_GET['phpInfo'])){echo phpinfo();exit;}
	$upload_max_filesize=ini_get('upload_max_filesize');
	$post_max_size=ini_get('post_max_size');
	// Nombre y ruta del directorio de las clases y controladores de php
	define('APP_NAME','CUERPO DE BOMBEROS SANTO DOMINGO');
	// Strings to app System
	define('TRY_AGAIN','<br>Por favor intente nuevamente..!');
	define('LOGIN_ERROR','Usuario & contraseña incorrectos');
	define('PASS_ERROR','Contraseña incorrecta!, si ha olvidado su contraseña intente con la opción  <b>"recuperar contraseña"</b>');
	define('SESSION_INIT_ERROR','No se ha podido registrar la sesión. <br> Por favor intente nuevamente!');
	define('SESSION_NEW_PASSWORD','<br> Por su seguridad es necesario que cambie sus credenciales de acceso para poder iniciar sesión!');
	define('SESSION_UNABLED','Lamentablemente su cuenta se encuentra desactivada!');
	define('PASSWORD_NOT_CONFIRM','Las contraseñas no coinciden o no cumplen con las políticas de seguridad!'.TRY_AGAIN);
	define('STATUS_ACTIVE','Activo');
	define('STATUS_DELETED','Eliminado');
	define('STATUS_UNABLED','Suspendido');
	define('QUERY_UNDEFINED','Sentencia no declarada :: ');
	define('PASSWORD_INVALID','La contraseña ingresa no es correcta!');
	define('UPLOAD_ERROR','Error al subir el archivo.!');
	define('UPLOAD_E0','Archivo cargado correctamente');
	define('UPLOAD_E1',"El archivo seleccionado excede el tamaño en MB permitidos!");
	define('UPLOAD_E2','The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
	define('UPLOAD_E3','The uploaded file was only partially uploaded');
	define('UPLOAD_E4','No file was uploaded');
	define('UPLOAD_E6','Missing a temporary folder');
	define('DELETE_OK','Archivo eliminado correctamente');
	define('DELETE_ERROR','Error al eliminar el archivo, vuelva a intentar!');
	define('FILE_FORMAT_ERROR','Formato no válido, solo se admiten archivos con extensión: ');
	define('FILE_EMPTY','No se ha recibido ningun archivo!');
	define('POST_EMPTY','No se ha recibido ningun dato!');
	define('DIRECTORY_404','El directorio de destino no existe, póngase en contacto con el administrador!');
	define('ERROR_401',"Origen desconocido: {$_SERVER['HTTP_HOST']}! <br>Lo sentimos pero usted no puede realizar esta operación..! :( <br> Consulte con el administrador");
	define('ERROR_405',"Método no permitido: {$_SERVER['REQUEST_METHOD']}");
	define('ERROR_SEARCH_BY_ID','Existen errores en las consultas,por favor comuníquese con el administrador');
	// INSPECCIONES
	define('NO_FORM_DEFINED','Aún no ha sido creado el formulario..');
	// Toma el separador de directorios del sistema. Windows=\, Linux=/
	define('DS', DIRECTORY_SEPARATOR);
	// SEPARADOR DE CARPETAS
	(PHP_OS=="Windows" || PHP_OS=="WINNT")?define("SEPARATOR","\\"):define("SEPARATOR","/");
	// String to Paths
	define('API_PATH', __DIR__ . DS);
	define('APP_PATH', 'app' . DS);
	define('COMPOSER_PATH', 'composer' . DS);
	define('VENDOR_PATH', 'vendor' . DS);
	define('SRC_PATH', 'src' . DS );
	define('VIEWS_PATH', SRC_PATH .'views' . DS );
	define('REPORTS_PATH', VIEWS_PATH . 'reports'. DS );
	// RETORNO DE MENSAJE DE ERROR - NO CUMPLE CONDICIONES PARA ACCEDER AL API
	function getRequestMethod(){return $_SERVER['REQUEST_METHOD'];}
	function return301($msg){echo json_encode(array('mensaje'=>$msg,'estado'=>false));exit;}
	function loadConfig($path){return json_decode(file_get_contents(SRC_PATH.'config/config.json'),true)[$path];}
	// CONFIGURACIÓN DE ACCESSO DE ORIGEN - SERVICIOS
	if(!in_array(getRequestMethod(),loadConfig('request_method'))) return301(ERROR_405);
	if ((isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'],loadConfig('server'))) || in_array($_SERVER['HTTP_HOST'],loadConfig('hosts'))){
		// PERMISOS ANGULAR - PHP
		header('Access-Control-Allow-Origin: *');
		header('content-type: application/json; charset=utf-8');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 86400');
		// METODO DE ENVIO
		$requestMethod=$_SERVER['REQUEST_METHOD'];
		// MÉTODOS PERMITIDOS
		if($requestMethod=="GET"){
			if(empty($_GET)) return301("No se ha enviado datos: GET");
		} elseif($requestMethod=="OPTIONS") {
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))	header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))	header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
		} elseif(in_array($requestMethod,["PUT","POST"])) {
			// UNIÓN DE DATOS POST
			$post=array_merge((array)json_decode(file_get_contents("php://input"),true),(array)filter_input_array(INPUT_POST));
			
			// VALIDAR TAMAÑO DE ARCHIVOS Y DATOS POST
			if ($requestMethod=='POST' && empty($post) && empty($_FILES) && $_SERVER['CONTENT_LENGTH']>0){
				// DATOS DE CONFIGURACIÓN
				$post_max_size=ini_get('post_max_size');
				// TAMAÑO DE FORMULARIO
				$sendFileMB=number_format($_SERVER['CONTENT_LENGTH']/(1024*1024), 2, '.', '');
				// RETORNAR MENSAJE DE TAMAÑO EXCEDIDO DE ARCHIVOS
				return301("Lamentamos los inconvenientes, usted ha excedido el tamaño límite permitido para subir información (<b>{$post_max_size}</b>).<br><br>El tamaño de sus datos es de <b>{$sendFileMB}M</b>");
			}	
			// NO SE HAN ENVIADO DATOS EN EL FORMULARIO
			if(empty($post) && empty($_FILES)) return301(POST_EMPTY);
			
		}	else {
			header('HTTP/1.1 405 Method Not Allowed');
			return301(ERROR_405);
		}
	}	else {
		header('HTTP/1.1 401 Unauthorized');
		return301(ERROR_401);
	}