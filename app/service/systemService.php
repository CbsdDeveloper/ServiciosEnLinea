<?php namespace service;
use api as app;
use controller as ctrl;

class systemService extends app\controller implements ctrl\crudController {
	
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
		$uri=$this->getURI();
		// VALIDAR PERMISOS CON RUTAS ASOCIADAS
		if(in_array($uri['db'],$opList) || in_array($uri['tb'],$opList)){
			// RETORNAR AL USUARIO LA CONSULTA
		    $db=$uri['db'];
		    $this->getJSON($this->$db());
		}else {
			// $this->getJSON(array('estado'=>'info',mensaje=>QUERY_UNDEFINED."execute->{$tb}<br><a href='./' target='_blank'><b>Haga click aquí para iniciar sesión nuevamente!</b></a>"));
			$this->getJSON(QUERY_UNDEFINED."execute->{$uri['tb']}");
		}
	}
	
	/*
	 * MÉTODO PARA CONSULTAS
	 */
	public function GET(){
	    // EXTRAER PARAMETROS ENVIADOS EN URL
	    $uri=$this->getURI();
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$uri['tb']]) && !isset($uri['smart_query']))$this->interceptor($uri['tb'],'GET');
		// HISTORIAL DE ENTIDADES
		if($uri['tb']=='records') new smartQuery;
		// LISTAS DE CONSULTAS (TABLAS)
		if(isset($uri['smart_query'])) new smartQuery;
		// ENTIDADES CON CONSULTA
        // $selectSingle=$this->getConfig('selectSingle');
		$selectList=$this->getConfig('selectList');
		$fetch=false;
		if(in_array($uri['tb'],$selectList)){
			// PARSE PARA FETCH INDIVIDUAL
			$fetch=true;
			// GENERAR STRING DE CONSULTA - SELECT
			$str=$this->db->getSQLSelect($uri['tb']);
		} else {
			// RETORNAR SENTENCIA NO DECLARADA
			return $this->setJSON(QUERY_UNDEFINED."GET->{$uri['tb']}");
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
	    // EXTRAER PARAMETROS ENVIADOS EN URL
	    $uri=$this->getURI();
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$uri['tb']]))$this->interceptor($uri['tb'],'POST');
		// VALIDAR TODAS LAS OPCIONES
		if(!in_array($uri['tb'],$this->getConfig('insertList'))) return $this->setJSON(QUERY_UNDEFINED."POST->{$uri['tb']}");
		// OBTENER FORMULARIO
		$post=$this->requestPost();
		// Open transaction
		$this->db->begin();
		// INSERT WITH FILEUPLOAD
		if(in_array($uri['tb'],$this->getConfig(true,'uploader')['list']))$post=$this->uploaderMng->uploadFileService($post,$uri['tb']);
		// INSERT POR DEFECTO
		if(in_array($uri['tb'],$this->getConfig('insertSingle')))$sql=$this->db->executeSingle($this->db->getSQLInsert($post,$uri['tb']));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."PUT->{$uri['tb']}");
		// Close transaction
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * ACTUALIZACIÓN DE ENTIDADES
	 */
	public function PUT(){
		// EXTRAER PARAMETROS ENVIADOS EN URL
		$uri=$this->getURI();
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$uri['tb']]))$this->interceptor($uri['tb'],'PUT');
		// VALIDAR TODAS LAS OPCIONES
		if(!in_array($uri['tb'],$this->getConfig('updateList')))return $this->setJSON(QUERY_UNDEFINED."PUT->{$uri['tb']}");
		// Open transaction
		$this->db->begin();
		// UPDATE WITH FILEUPLOAD
		if(in_array($uri['tb'],$this->getConfig(true,'uploader')['list']))$this->post=$this->uploaderMng->uploadFileService($this->post,$uri['tb'],false);
		// UPDATE POR DEFECTO
		if(in_array($uri['tb'],$this->getConfig('updateSingle')))$sql=$this->db->executeSingle($this->db->getSQLUpdate($this->post,$uri['tb']));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."PUT->{$uri['tb']}");
		// Close transaction
		return $this->db->closeTransaction($sql);
	}

	/*
	 * MÉTODO DE CONSULTAS
	 */
	public function REQUEST(){
	    // EXTRAER PARAMETROS ENVIADOS EN URL
	    $uri=$this->getURI();
		// VALIDACIONES - INTERCEPTOR
		$this->interceptor($uri['tb'],'REQUEST');
	}
	
	/*
	 * MÉTODO DE ELIMINACIÓN
	 */
	public function DELETE(){
	    // EXTRAER PARAMETROS ENVIADOS EN URL
	    $uri=$this->getURI();
		// VALIDACIONES - INTERCEPTOR
		if(isset($this->getConfig('interceptor')['model'][$uri['tb']]))$this->interceptor($uri['tb'],'DELETE');
		// VALIDAR TODAS LAS OPCIONES
		if(!in_array($uri['tb'],$this->getConfig('deleteList')))return $this->setJSON(QUERY_UNDEFINED."DELETE->{$uri['tb']}");
		// OBTENER FORMULARIO
		$post=$this->requestPost();
		// UPDATE POR DEFECTO
		if(in_array($uri['tb'],$this->getConfig('deleteSingle')))$sql=$this->db->executeSingle($this->db->deleteById($uri['tb'],$post['id']));
		// NO HAY ACCIÓN
		else $sql=$this->setJSON(QUERY_UNDEFINED."DELETE->{$uri['tb']}");
		// Close transaction
		$this->getJSON($sql);
	}
	
	/*
	 * MÉTODO PARA DESCARGAR
	 */
	public function DOWNLOAD(){
	    // EXTRAER PARAMETROS ENVIADOS EN URL
	    $uri=$this->getURI();
		// VALIDACIONES - INTERCEPTOR
	    $this->interceptor($uri['tb'],'DOWNLOAD');
	}
	
}