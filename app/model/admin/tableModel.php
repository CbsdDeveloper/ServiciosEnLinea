<?php namespace model\admin;
use api as app;

class tableModel extends app\controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='tablas';
	
	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESAR REQUISITOS PARA REPORTE
	 */
	public function setTables(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// COMERNZAR TRANSACCION
		$this->db->begin();
		// ELIMINAR REQUISITOS ANTERIORES
		$this->db->executeTested($this->db->getSQLDelete($this->entity,"WHERE fk_modulo_id={$post['moduleId']}"));
		// REGISTRO DE REQUISITOS
		foreach($post['model'] as $v){
			// INGRESAR NUEVO REQUISITO
			$model=array(
				'fk_modulo_id'=>$post['moduleId'],
				'tabla_name'=>$v
			);
			// GEERAR STRING DE INGRESO Y EJECUTAR SENTENCIA
			$json=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
		}
		// RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestTablesByModule($moduleId){
		// OBTENER DATOS DE ENTIDAD
		$data=$this->db->findById($moduleId,'modulos');
		// MODELO PARA REQUISITOS
		$data['model']=array();
		$data['modules']=array();
		$data['resources']=array();
		// LISTA DE RECURSOS
		foreach($this->getConfig(true,'model')['inSchema'] as $k=>$v){
			$data['modules'][]=$k;
			$data['resources'][$k]=$v;
		}
		// LISTA DE TABLAS YA ASIGNADAS
		foreach($this->db->findAll($this->db->selectFromView($this->entity,"WHERE fk_modulo_id={$moduleId}")) as $v){
			$data['model'][]=$v['tabla_name'];
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR DATOS DE REPORTES
	 */
	public function requestTables(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// VALIDAR TIPO DE CONSULTA
		if(isset($post['moduleId'])) $json=$this->requestTablesByModule($post['moduleId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}