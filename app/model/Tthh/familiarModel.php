<?php namespace model\Tthh;
use model as mdl;

class familiarModel extends mdl\personModel {
	
	private $entity='cargas_familiares';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESO - CARGAS RESPONSABLES
	 */
	public function insertFamiliar(){
		// DATOS DE FORMULARIO
		$post=$this->uploaderMng->uploadFileService($this->requestPost(),$this->entity);
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($post);
		// RELACIÓN CON PERSONA INGRESADA
		$post['carga_persona']=$person['persona_id'];
		// REGISTRAR ENTIDAD
		$sql=$this->db->closeTransaction($this->db->executeTested($this->db->getSQLInsert($post,$this->entity)));
		// RETORNAR CONSULTA
		$this->getJSON($sql);
	}
	
	/*
	 * ACTUALIZAR - CARGAS RESPONSABLES
	 */
	public function updateFamiliar(){
		// DATOS DE FORMULARIO
		$post=$this->uploaderMng->uploadFileService($this->requestPost(),$this->entity,false);
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($post);
		// RELACIÓN CON PERSONA INGRESADA
		$post['carga_persona']=$person['persona_id'];
		// REGISTRAR ENTIDAD
		$sql=$this->db->closeTransaction($this->db->executeTested($this->db->getSQLUpdate($post,$this->entity)));
		// RETORNAR CONSULTA
		$this->getJSON($sql);
	}

}