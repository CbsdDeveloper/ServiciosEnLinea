<?php namespace model\Tthh;
use api as app;
use controller as ctrl;

class delegationModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABLES LOCALES
	 */
	private $entity='delegaciones';
	private $varLocal;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PARENT
		parent::__construct();
		// VARIABLES LOCALES
		$this->varLocal=$this->getConfParams('tthh');
	}
	
	/*
	 * DETALLE DE REGISTROS
	 */
	private function getFormInformation(){
		// MODELO DE CONSULTA
		$data=array();
		// REGISTRO DE DIRECCIONES Y UNIDADES
		$data['leadership']=$this->db->findAll($this->db->selectFromView('vw_direcciones',"ORDER BY grado, puesto"));
		
		// INSERTAR UNIDADES
		$data['leadership']=array_merge($data['leadership'],$this->db->findAll($this->db->selectFromView("vw_personal_puestos","WHERE fk_puesto_id IN (44,50) AND ppersonal_estado='EN FUNCIONES'")));
		
		// RETORNAR MODELO DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR PERMISO POR CÓDIGO
	 */
	private function requestByCode($code){
		// ID DE PERSONAL
		$staffId=$this->getStaffIdBySession();
		// PPERSONAL
		$staff=$this->db->findOne($this->db->selectFromView('vw_relations_personal',"WHERE personal_id={$staffId} AND ppersonal_estado='EN FUNCIONES'"));
		
		// OBTENER INFORMACIÓN DE PERSONA
		$str=$this->db->selectFromView("vw_personal","WHERE ppersonal_estado='EN FUNCIONES' AND UPPER(persona_doc_identidad)=UPPER('{$code}')");
		// VERIFICAR REGISTRO DE CÉDULA
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado el registro <b>{$code}</b>.");
		// DATOS DE PERSONA
		$model=$this->db->findOne($str);
		
		// OBTENER DATOS DE DIRECTOR DE TALENTO HUMANO
		$tthh=$this->db->viewById(6,'direcciones');
		// ID DE PPERSONAL -> DIRECTOR
		$model['fk_tthh_id']=$tthh['director_id'];
		
		// ID DE RESPONSABLE
		$model['fk_responsable_id']=$staff['ppersonal_id'];
		
		// INSERTAR FK DE RELACION
		$model['delegacion_serie']=0;
		$model['delegacion_estado']='DELEGACIÓN ASIGNADA';
		
		// MODAL A DESPLEGAR
		$model['modal']='Delegaciones';
		$model['tb']=$this->entity;
		
		// DATOS DE RETORNO
		return $model;
	}
	
	/*
	 * CONSULTA DE REGISTROS
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO DE ACCIÓN O CÉDULA DE PERSONAL
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * INGRESAR NUEVOS REGISTROS
	 */
	public function insertEntity(){
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON("EmptyPost");
	}
	
	/*
	 * MODIFICACIÓN DE REGISTROS
	 */
	public function updateEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDAR SI LA ACCION EXISTE
		if(isset($this->post['delegacion_id'])){
			// INGRESAR ACCION DE PERSONAL
			$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		}else{
			// GENERAR SERIE DE ACCION
			$this->post['delegacion_serie']=$this->db->getNextSerie($this->entity);
			// INGRESAR ACCION DE PERSONAL
			$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
			// OBTENER ID DE NUEVA ACCIÓN
			$this->post['delegacion_id']=$this->db->getLastID($this->entity);
		}
		
		// ENVIAR ID PARA PROCESOS
		$sql['data']=$this->post;
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// EXTRAER ID DE REGISTRO
		$id=$this->get['id'];
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE delegacion_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// PARSE DE FECHAS
			$row['delegacion_serie']=str_pad($row['delegacion_serie'],4,"0",STR_PAD_LEFT);
			$row['delegacion_fecha_registro']=$this->setFormatDate($row['delegacion_fecha_registro'],'complete');
			$row['delegacion_fecha_inicio']=$this->setFormatDate($row['delegacion_fecha_inicio'],'complete');
			
			// INGRESO DE DATOS
			$pdfCtrl->loadSetting($row);
			
			// DATOS DE RESPONSABLES
			$pdfCtrl->loadSetting($this->db->findOne($this->db->selectFromView('vw_responsables_delegaciones',"WHERE delegacion_id={$id}")));
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['delegacion_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
}