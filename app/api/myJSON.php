<?php namespace api;
/** 
 * Description of ijson
 * myJSON.php 
 * interfaz
 */
interface myJSON {
	
	// Setear mensaaje en formato array para retornar como json
	public function setJSON($msg, $est);
	
	// Retornar mensaje a ajax
	public function getJSON($json);

}