<?php namespace model\collection;
use api as app;

class contractingProcedureModel extends app\controller {

	private $entity='procedimientoscontratacion';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * SETTEAR LOS REQUERIMIENTOS 
	 */
	public function setRequirements(){
		
		// VALIDAR QUE SE HAYA SELECCIONADO REQUERIMIENTOS
		if(isset($this->post['selected']) && count($this->post['selected'])<1) $this->getJSON("No se ha seleccionado los requerimientos para este procedimiento!");
		
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// ELIMINAR REGISTROS ANTERIORES
		$sql=$this->db->executeTested($this->db->getSQLDelete('documentos_procedimientoscontratacion',"WHERE fk_procedimiento_id={$this->post['procedimiento_id']}"));
		
		// RECORRER LISTADO DE REQUERIMIENTOS
		foreach($this->post['selected'] as $v){
			// INGRESO DE ID DE PROCEDIMIENTO 
			$v['fk_procedimiento_id']=$this->post['procedimiento_id'];
			// BUSCAR REGISTRO DE MODELO
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'documentos_procedimientoscontratacion'));
		}
		
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR CAJA PARA CIERRE
	 */
	private function requestById($recordId){
		// MODELO
		$model=$this->db->findById($recordId,$this->entity);
		// MODELO PARA LISTA DE SELECCION
		$model['selected']=array();
		
		// LISTADO DE REQUERIMIENTOS SELECCIONADOS
		$model['selected']=$this->db->findAll($this->db->selectFromView('documentos_procedimientoscontratacion',"WHERE fk_procedimiento_id={$recordId}"));
		
		// RETORNO DE MODELO
		return $model;
	}
	
	/*
	 * CONSULTA DE CAJAS
	 */
	public function requestEntity(){
		// CONSULTAR SI SE HA ENVIADO EL PARAMETRO TYPE
		if(isset($this->post['procedureId'])) $json=$this->requestById($this->post['procedureId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}