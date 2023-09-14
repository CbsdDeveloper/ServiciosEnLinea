<?php namespace app;
/**
 * Description of index
 * index.php hace referencia a una aplicación interna app/index.php la cual es la funcionalidad
 * en su totalidad del sistema, este método ayuda a mantener un directori base la carpeta reiz 
 * de un servidor (directorio) pero embeber una aplicación.
 * @author Lalytto
**/
class view {

	public static function decodeView($path,$view){
		return file_get_contents("{$path}/{$view}.html");
	}

	public static function insertView($find, $chng, $out){
		return preg_replace($find, $chng, $out);
	}
	
}