<?php namespace model\collection;
use api as app;

class processcontractsModel extends app\controller {

	private $entity='procesocontratacion';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
	 * INGRESAR CIERRE DE CAJA
	 */
	public function insertEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
	
		// STRING PARA CONSULTA
		$json=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// MODELO AUXILIAR
		$temp=$this->db->getLastEntity($this->entity);
		// OBTENER IDE DE CIERRE
		$json['data']=$temp;
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * ACTUALIZAR CIERRE DE CAJA
	 */
	public function updateEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// STRING PARA CONSULTA
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// OBTENER IDE DE CIERRE
		$json['data']=$this->post;
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
}