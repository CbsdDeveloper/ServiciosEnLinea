<?php namespace prevencion;
/**
 * Description of index
 * index.php hace referencia a una aplicación interna app/index.php la cual es la funcionalidad
 * en su totalidad del sistema, este método ayuda a mantener un directori base la carpeta reiz 
 * de un servidor (directorio) pero embeber una aplicación.
 * @author Lalytto
**/

/*
 * HABILITAR PRESENTACION DE ERRORES
 */
ini_set('display_errors',1);
/*
 * AUTOLOAD DE CLASES
 */
spl_autoload_register(function($clase){
	// OBETNER DIRECCION DE CLASE
	$clase=str_replace("\\","/",$clase);
	// VERIFICAR SI EXISTE LA CLASE A LA QUE SE HACE REFERENCIA
	if(!file_exists("{$clase}.php")) return FALSE;
	else require_once "{$clase}.php";
});

/*
 * DECLARACION DE OBJETO 
 */
class app {

	// METODO CONSTRUCTOR POR DEFECTO
	public static function index(){
		// HABILITAR CONSTRUCTOR DE INTERFAZ
		$app=new app\app;
		// IMPRIMIR INTERFAZ
		echo $app->AutoLoader();
	}
	
}