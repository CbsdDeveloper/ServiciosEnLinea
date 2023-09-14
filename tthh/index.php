<?php
/**
 * Description of index
 * index.php hace referencia a una aplicación interna app/index.php la cual es la funcionalidad
 * en su totalidad del sistema, este método ayuda a mantener un directori base la carpeta reiz 
 * de un servidor (directorio) pero embeber una aplicación.
 * @author Lalytto
**/
ini_set('display_errors',1);
spl_autoload_register(function($clase){
	$clase=str_replace("\\","/",$clase);
	if(!file_exists("$clase.php")) return FALSE;
	else require_once "$clase.php";
});
class app {
	public static function index(){
		$app=new app\app;
		echo $app->AutoLoader();
	}
}	app::index();