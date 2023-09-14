<?php namespace api;

/** 
 * Description of view
 * view.php Establece la conexión con las plantillas del sistema, prepara las plantillas para ser presentadas.
 * La plnatilla debe introducir la presentación de un formulario en un entorno dedicado para el trabajo de la
 * interfaz
 */
 
class view {
	
	// Método Prepara la vista a ser mostrada
	public function get_view($name){
		return file_get_contents(VIEW_PATH . "$name.html");
	}
	// Método: Reemplaza los las etiquetas desarrolladas en la plantilla por el contenido del formulario llamado
	public function replace_div($find, $chng, $out){
		return preg_replace($find, $chng, $out);
	}
	
}