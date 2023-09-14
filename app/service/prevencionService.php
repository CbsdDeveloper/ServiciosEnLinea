<?php namespace service;
use api as app;
use controller as ctrl;

class prevencionService extends app\controller implements ctrl\crudController {
	
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
		// EXTRAER PARAMETROS ENVIADOS EN URL
		extract($this->getURI());
		// VALIDAR PERMISOS CON RUTAS ASOCIADAS
		if(in_array($db,$opList) || in_array($tb,$opList)){
			// RETORNAR AL USUARIO LA CONSULTA
			$this->getJSON($this->$db());
			// DEFINIR MENSAJE POR DEFECTO
		}else {
			// $this->getJSON(array('estado'=>'info',mensaje=>QUERY_UNDEFINED."execute->{$tb}<br><a href='./' target='_blank'><b>Haga click aquí para iniciar sesión nuevamente!</b></a>"));
			$this->getJSON(QUERY_UNDEFINED."execute->{$tb}");
		}
	}
	
	/*
	 * MÉTODO PARA CONSULTAS
	 */
	public function GET(){
		// PARÁMETROS DE CONSULTA
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]) && !isset($smart_query))$this->interceptor($tb,'GET');
		// HISTORIAL DE ENTIDADES
		if($tb=='records') new smartQuery;
		// LISTAS DE CONSULTAS (TABLAS)
		if(isset($smart_query)) new smartQuery;
		// ENTIDADES CON CONSULTA
		$selectSingle=$this->getConfig('selectSingle');
		$selectList=$this->getConfig('selectList');
		$fetch=false;
		if(in_array($tb,$selectList)){
			// PARSE PARA FETCH INDIVIDUAL
			$fetch=true;
			// GENERAR STRING DE CONSULTA - SELECT
			$str=$this->db->getSQLSelect($tb);
		} else {
			// RETORNAR SENTENCIA NO DECLARADA
			return $this->setJSON(QUERY_UNDEFINED."GET->{$tb}");
		}
		// GENERA SENTENCIA DE RETORNO
		$fetch=$fetch?'findAll':'findOne';
		// RETORNAR CONSULTA
		return $this->db->$fetch($str);
	}
	
	/*
	 * INGRESO DE ENTIDADES
	 */
	public function POST(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->interceptor($tb,'POST');
		// VALIDAR TODAS LAS OPCIONES
		if(!in_array($tb,$this->getConfig('insertList'))) return $this->setJSON(QUERY_UNDEFINED."POST->$tb");
		// OBTENER FORMULARIO
		$post=$this->requestPost();
		// Open transaction
		$this->db->begin();
		// INSERT WITH FILEUPLOAD
		if(in_array($tb,$this->getConfig(true,'uploader')['list']))$post=$this->uploaderMng->uploadFileService($post,$tb);
		// INSERT POR DEFECTO
		if(in_array($tb,$this->getConfig('insertSingle')))$sql=$this->db->executeSingle($this->db->getSQLInsert($post,$tb));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."PUT->{$tb}");
		// Close transaction
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * ACTUALIZACIÓN DE ENTIDADES
	 */
	public function PUT(){
		// OBTENER Y EXTRAER PARÁMETROS DE URL
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->interceptor($tb,'PUT');
		// VALIDAR TODAS LAS OPCIONES
		if(!in_array($tb,$this->getConfig('updateList')))return $this->setJSON(QUERY_UNDEFINED."PUT->$tb");
		// Open transaction
		$this->db->begin();
		// UPDATE WITH FILEUPLOAD
		if(in_array($tb,$this->getConfig(true,'uploader')['list']))$this->post=$this->uploaderMng->uploadFileService($this->post,$tb,false);
		// UPDATE POR DEFECTO
		if(in_array($tb,$this->getConfig('updateSingle')))$sql=$this->db->executeSingle($this->db->getSQLUpdate($this->post,$tb));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."PUT->{$tb}");
		// Close transaction
		return $this->db->closeTransaction($sql);
	}

	/*
	 * MÉTODO DE CONSULTAS
	 */
	public function REQUEST(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		$this->interceptor($tb,'REQUEST');
	}
	
	/*
	 * MÉTODO DE ELIMINACIÓN
	 */
	public function DELETE(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$tb]))$this->interceptor($tb,'DELETE');
		// VALIDAR TODAS LAS OPCIONES
		if(!in_array($tb,$this->getConfig('deleteList')))return $this->setJSON(QUERY_UNDEFINED."DELETE->{$tb}");
		// OBTENER FORMULARIO
		$post=$this->requestPost();
		// UPDATE POR DEFECTO
		if(in_array($tb,$this->getConfig('deleteSingle')))$sql=$this->db->executeSingle($this->db->deleteById($tb,$post['id']));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."DELETE->{$tb}");
		// Close transaction
		$this->getJSON($sql);
	}
	
	/*
	 * MÉTODO PARA DESCARGAR
	 */
	public function DOWNLOAD(){
		extract($this->getURI());
		// VALIDACIONES - INTERCEPTOR
		$this->interceptor($tb,'DOWNLOAD');
	}
	
}