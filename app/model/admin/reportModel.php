<?php namespace model\admin;
use api as app;

class reportModel extends app\controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='reportes';
	
	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESAR REQUISITOS PARA REPORTE
	 */
	public function setRequirements(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// COMERNZAR TRANSACCION
		$this->db->begin();
		// ELIMINAR REQUISITOS ANTERIORES
		$this->db->executeTested($this->db->getSQLDelete('requisitos',"WHERE fk_reporte_id={$post['reporte_id']}"));
		// REGISTRO DE REQUISITOS
		foreach($post['model'] as $v){
			// INGRESAR NUEVO REQUISITO
			$model=array(
				'fk_reporte_id'=>$post['reporte_id'],
				'fk_recurso_id'=>$v
			);
			// GEERAR STRING DE INGRESO Y EJECUTAR SENTENCIA
			$json=$this->db->executeTested($this->db->getSQLInsert($model,'requisitos'));
		}
		// RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestRequirementsById($id){
		// OBTENER DATOS DE ENTIDAD
		$data=$this->db->findById($id,$this->entity);
		// MODELO PARA REQUISITOS
		$data['model']=array();
		$data['resources']=array();
		// LISTA DE RECURSOS
		foreach($this->db->findAll($this->db->selectFromView('recursos',"WHERE reportes='SI' AND recurso_estado='{$this->varGlobal['STATUS_ACTIVE']}'")) as $v){
			// LISTA DE REQUISITOS
			$data['resources'][$v['recurso_id']]=$v;
		}
		// SENTENCIA PARA CONSULTAR SI YA EXISTE LA REVISIÓN DEL AÑO EN CURSO
		$str=$this->db->selectFromView('requisitos',"WHERE fk_reporte_id=$id");
		// CONSULTAR SI EXISTEN RECURSOS DE UNA REVISIÓN PREVIA
		if($this->db->numRows($str)>0){
			foreach($this->db->findAll($str) as $v){
				$data['model'][]=$v['fk_recurso_id'];
			}
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR DATOS DE REPORTES
	 */
	public function requestReports(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// VALIDAR TIPO DE CONSULTA
		if(isset($post['reportId'])) $json=$this->requestRequirementsById($post['reportId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}