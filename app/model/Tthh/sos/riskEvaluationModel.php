<?php namespace model\Tthh\sos;
use api as app;
use controller as ctrl;

/**
 * @author Lalytto
 *
 */
class riskEvaluationModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='evaluacionesriesgopsicosocial';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * INGRESAR NUEVOS REGISTROS
	 */
	public function insertEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// STRING DE INGRESO DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICACIÓN DE REGISTROS
	 */
	public function updateEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// STRING DE INGRESO DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * CONSULTA DE REGISTROS
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// CONSULTAR POR FECHA - NUEVO REGISTRO
		elseif(isset($this->post['param'])) $json=$this->requestByParam($this->post['param']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("filtrocascada","WHERE filtro_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			
			$this->getJSON($row);
			
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_filtrocascada","WHERE filtro_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			
			$this->getJSON($row);
			
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}