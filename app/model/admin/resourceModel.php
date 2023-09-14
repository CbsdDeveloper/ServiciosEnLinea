<?php namespace model\admin;
use api as app;

class resourceModel extends app\controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='recursos';
	
	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESAR REQUISITOS PARA REPORTE
	 */
	public function setResources(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// MENSAJE POR DEFECTO
		$json=$this->setJSON("No se han enviado datos");
		// COMERNZAR TRANSACCION
		$this->db->begin();
		// REGISTRO DE REQUISITOS
		foreach($post['resources'] as $v){
			// GEERAR STRING DE INGRESO Y EJECUTAR SENTENCIA
			$json=$this->db->executeTested($this->db->getSQLUpdate($v,$this->entity));
		}
		// RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestResourcesModel(){
		// MODELO DE LA ENTIDAD
		$data=array();
		// MODELO PARA REQUISITOS
		foreach($this->db->findAll($this->db->selectFromView($this->entity,"WHERE recurso_estado='ACTIVO' ORDER BY recurso_id")) as $v){
			// CARGAR MODELO
			$data[]=$v;
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR DATOS DE REPORTES
	 */
	public function requestResources(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// VALIDAR TIPO DE CONSULTA
		if(isset($post['types'])) $json=$this->requestResourcesModel($post['reportId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * REGISTRO DE ENTIDADDES
	 */
	public function insertEntity(){
	    
	    // GENERAR CONSULTA SQL
	    $sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
	    
	}
	
	
	/*
	 * ACTUALIZAR REGISTRO
	 */
	public function updateEntity(){
	    
	    // GENERAR CONSULTA SQL
	    $sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
	    
	}
	
	
}