<?php namespace model;
use api as app;

class geoJSONModel extends app\controller {
	
	private $entity='georeferenciacion';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * CONSULTAR ENTIDADES
	 */
	public function requestEntity(){
		// CONSULTAR POR ID DE ENTIDAD
		if(isset($this->post['entityId'])) $json=$this->getGeoJSON($this->post['entity'],$this->post['entityId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}