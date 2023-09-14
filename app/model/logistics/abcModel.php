<?php namespace model\logistics;
use api as app;

class abcModel extends app\controller {
	
	private $entity='reglasmantenimiento';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * MÉTODO PARA INGRESAR/ELIMINAR RECURSOS AL PLAN DE MANTENIMIENTO
	 */
	private function testResourceModel($modelId,$list){
		// ELIMINAR RECURSO INGRESADOS
		$sql=$this->db->executeSingle($this->db->getSQLDelete('mantenimiento_recursos',"WHERE fk_mantenimiento_id={$modelId}"));
		if(!$sql['estado']) return $sql;
		// VARIABLE POR DEFECTO
		$sql=$this->setJSON("Recursos ingresados correctamente",false);
		// RECORRER LISTADO DE RECURSOS EMPLEADOS
		foreach($list as $k=>$v){
			// VALIDAR SI YA HA SIDO INGRESADO EL RECURSO
			$aux=array('fk_mantenimiento_id'=>$modelId,'fk_recurso_id'=>$v);
			// SENTENCIA PARA EL REGISTRO DE REGISTRO / EJECUTAR SENTENCIA
			$sql=$this->db->executeSingle($this->db->getSQLInsert($aux,'mantenimiento_recursos'));
			// VALIDAR CONSULTA EJECUTADA
			if(!$sql['estado']) return $sql;
		}
		// RETORNAR CONSULTA GENERADA
		return $sql;
	}
	
	/*
	 * INGRESAR FLOTA
	 */ 
	public function insertMaintenance(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR PLAN DE MANTENIMIENTO
		$str=$this->db->getSQLInsert($this->post,$this->entity);
		// EJECUTAR SENTENCIA SQL
		$sql=$this->db->executeTested($str);
		// VALIDAR EL INGRESO/ACTUALIZACIÓN DE LOS RECURSOS EMPLEADOS PARA EL MANTENIMIENTO
		$sql=$this->testResourceModel($this->db->getLastID($this->entity),$this->post['selected']);
		// EJECUTAR CONSULTA Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * CONSULTA
	 */ 
	public function updateMaintenance(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR PLAN DE MANTENIMIENTO
		$str=$this->db->getSQLUpdate($this->post,$this->entity);
		// EJECUTAR SENTENCIA SQL
		$sql=$this->db->executeTested($str);
		// VALIDAR EL INGRESO/ACTUALIZACIÓN DE LOS RECURSOS EMPLEADOS PARA EL MANTENIMIENTO
		$sql=$this->testResourceModel($this->post['mantenimiento_id'],$this->post['selected']);
		// EJECUTAR CONSULTA Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * LISTADO DE PUESTOS POR DIRECCIÓN
	 */
	private function resourcesByModel($post){
		// MODELO DE DATOS PARA PRESENTAR
		$data=array('selected'=>array(),'resources'=>array());
		// VALIDAR SI HA SIDO ENVIADO EL ID DE MOVIMIENTO
		if(isset($post['mantenimiento_id'])){
			// COMPLETAR LISTADO - selected
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_recursos',"WHERE fk_mantenimiento_id={$post['mantenimiento_id']}")) as $k=>$v){
				// VALIDAR SI NO HA SIDO CREADO EL TIPO DE REPUESTO
				if(!in_array($v['fk_recurso_id'],$data['selected'])) $data['selected'][]=$v['fk_recurso_id'];
			}
		}
		// LISTADO DE RECURSOS DE TIPO MANO DE OBRA Y REPUESTOS
		foreach($this->db->findAll($this->db->selectFromView('recursosmantenimiento',"WHERE recurso_estado='ACTIVO' ORDER BY recurso_tipo")) as $k=>$v){
			// VALIDAR SI NO HA SIDO CREADO EL TIPO DE REPUESTO
			if(!isset($data['resources'][$v['recurso_tipo']])) $data['resources'][$v['recurso_tipo']]=array();
			// INGRESAR EL NUEVO REPUESTO
			$data['resources'][$v['recurso_tipo']][]=$v;
		}
		// RETORNAR DATOS
		return $data;
	}

	/*
	 * LISTADO DE PUESTOS POR DIRECCIÓN
	 */
	private function requestPlansMaintenance(){
		// CONDICIÓN PARA PLANES DE EMERGENCIA
		$strWhr="plan_id IN (SELECT fk_plan_id FROM logistica.tb_reglasmantenimiento WHERE mantenimiento_estado='ACTIVO')";
		// GENERAR SENTENCIA PARA CONSULTAR PLANES DE MANTENIMIENTO
		return $this->db->findAll($this->db->selectFromView('planesmantenimiento',"WHERE plan_estado='ACTIVO' AND {$strWhr}"));
	}
	
	/*
	 * CONSULTAR PUESTOS Y DIRECCIONES
	 */
	public function requestMaintenance(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// RETORNAR EL LISTADO DE RECURSOS PARA MANTENIMIENTO 
		elseif(isset($this->post['type']) && $this->post['type']=='repuestos') $json=$this->resourcesByModel($this->post);
		// LISTA DE PLANES DE MANTENIMIENTO
		elseif(isset($this->post['type']) && $this->post['type']=='plansList') $json=$this->requestPlansMaintenance();
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}