<?php namespace model\Tthh;
use api as app;
use controller as ctrl;

/**
 * @author Lalytto
 *
 */
class feddingModel extends breakModel {
	
	/*
	 * VARIABLES GLOBALES
	 */
	private $entity='ranchos';
	private $insert=0;
	private $update=0;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct($this->entity);
	}
	
	
	/*
	 * PROCESAR VACACIONES GENERADAS
	 */
	private function processList($pathFile){
		// INSTANCIA XLS
		$myExcel=new ctrl\xlsController();
		// OBTENER DATOS DE EXCEL
		$data=$myExcel->getCellValue($this->post,$pathFile,'ranchoslista');
		// VALIDAR LONGITUD DE DATOS
		if(count($data)<1)$this->getJSON("Operación abortada, no se ha encontrado ningun registro...");
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// RECORRER LISTADO
		foreach($data as $k=>$v){
			// STRING PARA CONSULTAR REGISTRO DE PERSONAL
			$str=$this->db->selectFromView("vw_basic_personal","WHERE persona_doc_identidad='{$v['persona_doc_identidad']}'");
			// VALIDAR EXISTENCIA DE REGISTRO
			if($this->db->numRows($str)<1)$this->getJSON($this->db->closeTransaction($this->setJSON("No se ha encontrado ningún registro relacionado con el # de identificación <b>{$v['persona_doc_identidad']}</b>, favor revise los datos y vuelva a intentar.")));
			// OBTENER REGISTRO DE PERSONAL
			$staff=$this->db->findOne($str);
			// REGISTRAR ID DE PERSONAL
			$v['fk_personal_id']=$staff['personal_id'];
			// EJECUTAR SENTENCIA DE CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,$this->entity));
			// SUMAR CONTADOR
			$this->insert+=1;
		}
		// INGRESAR COMO RETORNO LOS DATOS QUE SE HAN INGRESADO
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: {$this->insert}  <br>Datos actualizados/ignorados: {$this->update}";
		// RETORNAR MENSAJE DE CONSULTA
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * CARGAR LISTADO DE EXCEL
	 */
	public function uploadList(){
		// SUBIR ARCHIVO
		$config=$this->uploaderMng->config['entityConfig']['ranchoslista'];
		// PARAMETROS DE CONSULTA
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA DE ARCHIVO
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// OBTENER ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['rancho_name']}";
		// COMPROBAR SI EL ARCHIVO SE HA SUBIDO CORRECTAMENTE
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// PROCEDER A INGRESAR DATOS
		$this->getJSON($this->processList($pathFile));
	}

	
	
	
}