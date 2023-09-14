<?php namespace service;
use api as app;
use controller as ctrl;

class tthhService extends app\controller implements ctrl\crudController {

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
		if(in_array($db,$opList) || in_array($tb,$opList)){
			$this->getJSON($this->$db());
		}else $this->getJSON(QUERY_UNDEFINED."execute->$tb");
	}

	/*
	 * MÉTODO PARA CONSULTAS
	 */
	public function GET(){
		// PARÁMETROS DE CONSULTA
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]) && !isset($smart_query))$this->interceptor($tb,'GET');
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
		// Open transaction
		$this->db->begin();
		// INSERT WITH FILEUPLOAD
		if(in_array($tb,$this->getConfig(true,'uploader')['list']))$this->post=$this->uploaderMng->uploadFileService($this->post,$tb);
		// INSERT POR DEFECTO
		if(in_array($tb,$this->getConfig('insertSingle')))$sql=$this->db->executeSingle($this->db->getSQLInsert($this->post,$tb));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."POST->$tb");
		// Close transaction
		return $this->db->closeTransaction($sql);
	}

	/*
	 * ACTUALIZACIÓN DE ENTIDADES
	 */
	public function PUT(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->interceptor($tb,'PUT');
		// Open transaction
		$this->db->begin();
		// UPDATE WITH FILEUPLOAD
		if(in_array($tb,$this->getConfig(true,'uploader')['list']))$this->post=$this->uploaderMng->uploadFileService($this->post,$tb,false);
		// UPDATE POR DEFECTO
		if(in_array($tb,$this->getConfig('updateSingle')))$sql=$this->db->executeSingle($this->db->getSQLUpdate($this->post,$tb));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."PUT->$tb");
		// VALIDACIONES - INTERCEPTOR - AFTER
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->interceptor($tb,'AFTER');
		// Close transaction
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * MÉTODO DE ELIMINACIÓN
	 */
	public function DELETE(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->interceptor($tb,'DELETE');
		// VALIDAR TODAS LAS OPCIONES
		if(!in_array($tb,$this->getConfig('deleteList')))return $this->setJSON(QUERY_UNDEFINED."DELETE->$tb");
		// UPDATE POR DEFECTO
		if(in_array($tb,$this->getConfig('deleteSingle')))$sql=$this->db->executeSingle($this->db->deleteById($tb,$this->post['id']));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."DELETE->$tb");
		// Close transaction
		$this->getJSON($sql);
	}

	/*
	 * MÉTODO DE CONSULTAS
	 */
	public function REQUEST(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		$this->interceptor($tb,'REQUEST');
	}

}