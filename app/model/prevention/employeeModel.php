<?php namespace model\prevention;
use controller as ctrl;
use model as mdl;

class employeeModel extends mdl\personModel {
	
	private $entity='local_empleados';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * PROCESAR DATOS
	 */
	private function processList($pathFile){
		// Instancia de librería y lectura de archivo
		$myExcel=new ctrl\xlsController();
		$data=$myExcel->getCellValue($this->post,$pathFile,$this->entity);
		// Empezar transacción
		$succes=0;
		$insert=0;
		$update=0;
		
		// INICIAR TRANSACCION
		$this->db->begin();
		// RECORRER LISTADO
		foreach($data as $key=>$val){
			
			// FLAG PARA INGRESO DE SOLO CEDULAS REALES
			$val['persona_tipo_doc']='CEDULA';
			// CONTROL DE DATOS DE PERSONAS
			$person=$this->requestPerson($val);
			
			// INGRESAR ID DE REFERENCIA A PERSONA
			$val['fk_persona_id']=$person['persona_id'];
			$val['fk_local_id']=$this->post['fk_local_id'];
			
			// VALIDAR SI EXISTE PARTICIPANTES
			$str=$this->db->selectFromView($this->entity,"WHERE fk_persona_id={$person['persona_id']} AND fk_local_id={$val['fk_local_id']}");
			// VALIDAR SI EXISTE EL REGISTRO
			if($this->db->numRows($str)>0){
				// GENERAR ACTUALIZACION DE REGISTRO
				$update++;
				// OBTENER REGISTRO
				$row=$this->db->findOne($str);
				// ACTUALIZAR REGISTRO
				$str=$this->db->getSQLUpdate(array_merge($row,$val),$this->entity);
			}else{
				// GENERAR INGRESO DE REGISTRO
				$insert++;
				// INGRESAR REGISTRO
				$str=$this->db->getSQLInsert($val,$this->entity);
			}	
			
			// EJECUTAR OPERACION
			$sql=$this->db->executeSingle($str);
			
			// VALIDAR TRANSACCION
			if(!$sql['estado']){
				$sql['mensaje'].="<br>Error provocado en registro con código -> {$person['persona_doc_identidad']}";
				return $this->db->closeTransaction($sql);
			}
			
		}	
		// VALIDAR CARGA DE DATOS
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: $insert  <br>Datos actualizados: $update";
		// RETORNAR CONSULTA Y CERRAR TRANSACCION
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * LISTADO DE PERSONAL
	 */
	public function uploadList(){
		// SUBIR ARCHIVO
		$config=$this->uploaderMng->config['entityConfig'][$this->entity];
		// OBTENER ARCHIVO
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// DIRECCION DE ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['empleados_name']}";
		// SI NO EXISTE EL ARCHIVO
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// PROCESAR DATOS
		$this->getJSON($this->processList($pathFile));
	}
	
}