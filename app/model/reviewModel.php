<?php namespace model;
use api as app;

class reviewModel extends app\controller {
	
	private $entity='revisiones_procesos';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * REGISTRAR NUEVO ARCHIVO
	 */
	public function insertEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// PREFIJO DE PROCESO
		$entityName=$this->crudConfig['entityPrefix'][$this->post['fk_entidad']];
		
		// GENERAR STRING DE CONSULTA DE REGISTRO
		$str=$this->db->selectFromView($this->entity,"WHERE fk_entidad='{$this->post['fk_entidad']}' AND fk_entidad_id={$this->post['fk_entidad_id']} AND revision_fecha_registro::date='{$this->post['revision_fecha_registro']}'::date");
		// CONSULTAR LA EXISTENCIA DEL REGISTRO PARA DETERMINAR INGRESO O ACTUALIZACION DEL REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			// ACTUALIAZR REGISTRO
			$row=array_merge($row,$this->post);
			// GENERAR STRING DE ACTUALIZACION
			$str=$this->db->getSQLUpdate($row,$this->entity);
		}else{
			// ELIMINAR ID DE REGISTRO
			unset($this->post['revision_id']);
			// GENERAR STRING DE INGRESO
			$str=$this->db->getSQLInsert($this->post,$this->entity);
		}
		// EJECUTAR CONSULTA
		$sql=$this->db->executeTested($str);
		
		// ACTUALIZAR ESTADO DE PROCESO
		$process=array(
			"{$entityName}_id"=>$this->post['fk_entidad_id'],
			"{$entityName}_estado"=>$this->post['revision_estado']
		);
		// GENERAR STRING DE ACTUALIZACION DE PROCESO Y EJECUTAR SENTENCIA SQL
		$sql=$this->db->executeTested($this->db->getSQLUpdate($process,$this->post['fk_entidad']));
		
		// CERRAR SESIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * CONSULTAR REGISTRO DE PROCESO
	 */
	public function requestEntity(){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView($this->entity,"WHERE fk_entidad='{$this->post['fk_entidad']}' AND fk_entidad_id={$this->post['fk_entidad_id']} ORDER BY revision_fecha_registro DESC");
		// CONSULTAR LA EXISTENCIA DEL REGISTRO
		if($this->db->numRows($str)>0) $json=$this->setJSON("A continuación, si desea modificar la revisión edite la descripción. Si desea registrar una nueva cambie la fecha de registro e ingrese su nuevo comentario",true,$this->db->findOne($str));
		else $json=$this->setJSON("No existe ninguna revisión previa, a continuación ingrese su descripción...");
		// RETORNAR DATOS
		$this->getJSON($json);
	}
	
}