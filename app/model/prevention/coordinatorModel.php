<?php namespace model\prevention;
use model as mdl;

class coordinatorModel extends mdl\personModel {
	
	private $entity='personas';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * ACTUALIZAR DATOS DE PERSONAS
	 */
	public function updateEntity(){
		
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// ACTUALIZAR DATOS DE COORDINADOR
		$person=$this->requestPerson($this->post);
		
		// INGRESO DE ID DE PERSONA RESPONSABLE DE ENTIDAD
		$this->post['fk_persona_id']=$person['persona_id'];
		
		// ACTUALIZAR RESPONSABLE EN ENTIDAD DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->post['entityName']));
		
		// CERRAR TRANSACCION Y RETORNR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
}