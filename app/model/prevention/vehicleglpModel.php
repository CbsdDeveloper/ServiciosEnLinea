<?php namespace model\prevention;
use api as app;
use model as mdl;

class vehicleglpModel extends mdl\vehicleModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='vehiculosglp';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/* 
	 * INGRESO DE REGISTRO
	 */ 
	public function insertVehicleGlp(){
		// RECIBIR FORMULARIO
		$post=$this->requestPost();
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR / ACTUALIZAR DATOS DEL VEHICULO
		$vehiculo=$this->testedInsertVehicule($post);
		// RELACIONAR PERMISO DE GLP CON VEHICULO
		$post['fk_vehiculo_id']=$vehiculo['vehiculo_id'];
		// ID DE SESSION DE ENTIDAD
		$post['fk_entidad_id']=app\session::get();
		// INGRESAR REGISTRO DE VEHÍCULO
		$json=$this->db->executeTested($this->db->getSQLInsert($post,$this->entity));
		// CIERRE DE TRANSACCIÓN
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO
	 */ 
	public function updateVehicleGlp(){
		// RECIBIR FORMULARIO
		$post=$this->requestPost();
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR / ACTUALIZAR DATOS DEL VEHICULO
		$vehiculo=$this->testedInsertVehicule($post);
		// RELACIONAR PERMISO DE GLP CON VEHICULO
		$post['fk_vehiculo_id']=$vehiculo['vehiculo_id'];
		// ID DE SESSION DE ENTIDAD
		$post['fk_entidad_id']=app\session::get();
		// INGRESAR REGISTRO DE VEHÍCULO
		$json=$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
		// CIERRE DE TRANSACCIÓN
		$this->getJSON($this->db->closeTransaction($json));
	}
	
}