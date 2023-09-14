<?php namespace model\Tthh;
use api as app;

class stationModel extends app\controller {
	
	private $entity='estaciones';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * CONSULTAR DATOS DE REGISTRO - ID
	 */
	private function requestById($id){
		// MODELO DE DATOS
		$data=array();
		// CONSULTAR REGISTRO POR ID
		$data=$this->db->viewById($id,$this->entity);
		// ADJUNTAR PERSONAL QUE PERTENECE A LA ESTACION
		$data['staff']=$this->db->findAll($this->db->selectFromView('vw_personal',"WHERE fk_estacion_id={$id} ORDER BY personal_estado, personal_nombre"));
		// ADJUNTAR PELOTONES DE ESTACIÓN
		$data['platoons']=array();
		// ADJUNTAR UNIDADES DE ESTACIÓN
		$data['units']=$this->db->findAll($this->db->selectFromView('vw_unidades',"WHERE fk_estacion_id={$id} ORDER BY unidad_nombre"));
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAS
	 */
	public function requestStation(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}