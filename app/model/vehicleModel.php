<?php namespace model;

class vehicleModel extends personModel {
	
	private $entity='vehiculos';
	private $config;
	protected $validateTypePlate=true;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
		// PARÁMETROS DE VERIFICACIÓN DE DATOS DE VEHÍCULOS
		$this->config=$this->getConfig(true,'vehicles');
	}
	
	/*
	 * VALIDAR PLACA DE VEHÍCULO
	 */
	private function validatePlate($plate,$type,$class){
		$plate=strtoupper($plate);
		// CONVERTIR LA PLACA EN ARRAY
		$_plate=str_split($plate);
		// VALIDACIÓN POR TIPO DE VEHÍCULO
		if($type!='MOTOCICLETA'){
			// VALIDACION INVALIDA
			$strMain="Validación de placa inválida;";
			// VALIDAR LONGITUD DE PLACA
			if(strlen($plate)<>7) $this->getJSON("{$strMain} la placa debe contener 7 caracteres: <<b>{$plate}</b>>");
			// DIVIDIR CARACTERES
			$letters=substr($plate,0,3);
			$numbers=substr($plate,3,6);
			// VALIDAR CARACTERES - LETRAS
			if(!ctype_alpha($letters)) $this->getJSON("{$strMain} los 3 primeros caracteres deben ser letras: <<b>{$plate}</b>>");
			// VALIDAR CARACTERES - NÚMEROS
			if(!is_numeric($numbers)) $this->getJSON("{$strMain} los 4 últimos dígitos deben ser numéricos: <<b>{$plate}</b>>");
			// VERIFICAR QUE SEA UNA LETRA ACEPTABLE DE PLACA
			if(!in_array($_plate[0],$this->config['plates'])) $this->getJSON("{$strMain} <<b>{$plate}</b>> no es una placa válida para un vehículo matriculado en ecuador");
			// VERIFICAR QUE SEA UNA LETRA ACEPTABLE DE PLACA
			if(!in_array($_plate[1],$this->config['typePlates'][$class]['letter']) && $this->validateTypePlate) $this->getJSON("{$strMain} la segunda letra de la placa no corresponde al tipo de entidad del vehículo: <b>{$plate}</b>");
		}	
		// RETORNAR PLACA
		return strtoupper($plate);
	}
	
	/*
	 * BUSCAR VEHÍCULO
	 */
	protected function getVehicule($plate){
		$str=$this->db->selectFromView($this->entity,"WHERE UPPER(vehiculo_placa)=UPPER('$plate')");
		if($this->db->numRows($str)>0) return $this->db->findOne($str);
		return null;
	}
	
	/*
	 * INGRESO DE UN REGISTRO DE VEHÍCULOS
	 * - VALIDA LA PLACA
	 * - REGISTRA PROPIETARIO EN CASO DE NO EXISTIR
	 * - REGISTRA VEHÍCULO EN CASO DE NO EXISTIR
	 * - VALIDACIÓN DE MOTOR Y CHASIS EN DB
	 */
	protected function testedInsertVehicule($post){
		// MODELO POR DEFECTO DE TIPO DE ENTIDAD O PROPÓSITO DE VEHÍCULO
		if(!isset($post['vehiculo_proposito'])) $post['vehiculo_proposito']='PARTICULAR';
		// VALIDAR PLACA
		$post['vehiculo_placa']=$this->validatePlate($post['vehiculo_placa'],$post['vehiculo_tipo'],$post['vehiculo_proposito']);
		// VERIFICAR SI EXISTE PROPIETARIO
		$person=$this->requestPerson($post);
		$post['propietario_id']=$person['persona_id'];
		// VERIFICAR SI ES UN USUARIO QUE CAMBIA DE CC
		if(isset($post['vehiculo_id'])) $vehicule=$this->db->findById($post['vehiculo_id'],$this->entity);
		else $vehicule=$this->getVehicule($post['vehiculo_placa']);
		// VALIDAR OBJETO DE PERSONA
		if($vehicule!=null && !empty($vehicule)){
			$post=array_merge($vehicule,$post);
			$sql=$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
		} else {
			$sql=$this->db->executeTested($this->db->getSQLInsert($post,$this->entity));
			$post['vehiculo_id']=$this->db->getLastID($this->entity);
		}	return $post;
	}
	
	/*
	 * INGRESO DE PROCESO PARA PERMISO DE TRANSPORTE DE GLP
	 */ 
	public function insertVehicle(){
		$post=$this->requestPost();
		
		$this->getJSON($post);
	}
	
	/*
	 * ACTUALIZACIÓN DE PROCESO PARA PERMISO DE TRANSPORTE DE GLP
	 */ 
	public function updateVehicule(){
		$this->db->begin();
		$this->testedInsertVehicule($this->requestPost());
		$this->getJSON($this->db->closeTransaction($this->setJSON("Transacción completa",true)));
	}
	
}