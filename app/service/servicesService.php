<?php namespace service;
use api as app;

class servicesService extends app\controller {
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * MÉTODO MAIN PARA INSTANCIA DE MÉTODOS
	 */
	public function executeQuery($opList){
		extract($this->getURI());
		if(in_array($db,$opList))$this->getJSON($this->$db());
		elseif(in_array($tb,$opList))$this->getJSON($this->$tb());
		else $this->getJSON(QUERY_UNDEFINED."execute->$tb");
	}
	
	/*
	 * MÉTODO PARA CONSULTAS
	 */
	public function GET(){
		// PARÁMETROS DE CONSULTA
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->getJSON($this->interceptor($tb,'GET'));
		// RETORNA LISTAS DE CONSULTAS (TABLAS)
		if(isset($smart_query)) new smartQuery;
		// LISTA DE ENTIDADES CON CONSULTA
		$selectList=$this->getConfig('selectList');
		// ********************************* PETICIONES PARA CONTROL *********************************
		// VALIDAR SI LA ENTIDAD SE ENCUENTRA EN EL LISTADO PERMITIDO
		if(in_array($tb,$selectList)){
			// GENERAR STRING DE CONSULTA - SELECT
			$str=$this->db->getSQLSelect($tb);
		}	else {
			// RETORNAR SENTENCIA NO DECLARADA
			return $this->setJSON(QUERY_UNDEFINED."GET->$tb");
		}
		// RETORNAR CONSULTA
		return $this->db->findAll($str);
	}
	
	/*
	 * INGRESO DE ENTIDADES
	 */
	public function POST(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->interceptor($tb,'POST');
		// OBTENER FORMULARIO
		$post=$this->requestPost();
		// Open transaction
		$this->db->begin();
		// INSERT WITH FILEUPLOAD
		if(in_array($tb,$this->getConfig(true,'uploader')['list']))$post=$this->uploaderMng->uploadFileService($post,$tb);
		// INSERT POR DEFECTO
		if(in_array($tb,$this->getConfig('insertSingle')))$sql=$this->db->executeSingle($this->db->getSQLInsert($post,$tb));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."POST->$tb");
		// Close transaction
		return $this->db->closeTransaction($sql);
	}
	
}