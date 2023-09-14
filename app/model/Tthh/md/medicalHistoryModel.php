<?php namespace model\Tthh\md;
use model as mdl;

class medicalHistoryModel extends mdl\personModel {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='historiasclinicas';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'tthh');
	}
	
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */
	public function insertEntity(){
		// GENERAR MODELO DE INGRESO
		$this->post['fk_paciente_id']=$this->post['personal_id'];
		$this->post['historia_serie']=$this->post['personal_id'];
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeSingle($this->db->getSQLInsert($this->post,$this->entity));
		// RETORNAR CONSULTA
		$this->getJSON($sql);
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */
	public function updateEntity(){
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeSingle($this->db->getSQLUpdate($this->post,$this->entity));
		// RETORNAR CONSULTA
		$this->getJSON($sql);
	}
	
	
	/*
	 * REGISTRAR ENFERMEDAD
	 */
	public function insertDisease(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// OBTENER ID DE HISTORIA CLINICA
		$medicalHistory=$this->requestByStaffId($this->post['staffId']);
		// GENERAR MODELO DE INGRESO
		$this->post['fk_historiaclinica_id']=$medicalHistory['historia_id'];
		// VALIDAR RELACION DE ENFERMEDAD
		if($this->post['antecedente_relacion']=='FAMILIAR' && $this->post['antecedente_datos_familiar']=='SI'){
			// CONCATENAR SEXO DE FAMILIAR
			$this->post['persona_sexo']=$this->post['antecedente_sexo_familiar'];
			// REGISTRO DE PERSONA
			$person=$this->requestPerson($this->post);
			// INGRESAR ID DE RELACION
			$this->post['fk_familiar_id']=$person['persona_id'];
		}
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,'antecedentesenfermedades'));
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICAR ENFERMEDAD
	 */
	public function updateDisease(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// OBTENER ID DE HISTORIA CLINICA
		$medicalHistory=$this->requestByStaffId($this->post['fk_personal_id']);
		// GENERAR MODELO DE INGRESO
		$this->post['fk_historiaclinica_id']=$medicalHistory['historia_id'];
		// VALIDAR RELACION DE ENFERMEDAD
		if($this->post['antecedente_relacion']=='FAMILIAR' && $this->post['antecedente_datos_familiar']=='SI'){
			// CONCATENAR SEXO DE FAMILIAR
			$this->post['persona_sexo']=$this->post['antecedente_sexo_familiar'];
			// REGISTRO DE PERSONA
			$person=$this->requestPerson($this->post);
			// INGRESAR ID DE RELACION
			$this->post['fk_familiar_id']=$person['persona_id'];
		}
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,'antecedentesenfermedades'));
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR HISTORIA CLINICA POR ID DE PERSONA
	 */
	private function requestByStaffId($staffId){
		// STRING PARA CONSULTAR HISTORIA CLINICA
		$str=$this->db->selectFromView($this->entity,"WHERE fk_paciente_id={$staffId}");
		// VALIDAR SI EXISTE LA HISTORIA CLINICA
		if($this->db->numRows($str)<0) $this->getJSON("La historia clínica no ha sido registrada, favor comuníquese con la unidad de enfermería!");
		// RETORNAR DATOS DE PERSONAL
		return $this->db->findOne($str);
	}
	
	/*
	 * CONSULTAR HISTORIA CLINICA POR ID DE PERSONA
	 */
	private function requestByPersonId($personId){
		// STRING PARA CONSULTAR DATOS DE PERSONAL
		$str=$this->db->selectFromView("vw_personal","WHERE fk_persona_id={$personId} ORDER BY personal_definicion DESC LIMIT 1");
		// VALIDAR LA EXISTENCIA DEL PERSONAL
		if($this->db->numRows($str)<0) $this->getJSON("No se ha encontrado el registro solicitado.");
		// OBTENER INFORMACIÓN DE REGISTRO
		$data=$this->db->findOne($str);
		// STRING PARA CONSULTAR HISTORIA CLINICA
		$str=$this->db->selectFromView($this->entity,"WHERE fk_paciente_id={$data['personal_id']}");
		// VALIDAR SI EXISTE LA HISTORIA CLINICA
		if($this->db->numRows($str)<0) $this->getJSON("La historia clínica para <b>{$data['personal_nombre']}</b> no ha sido registrada, favor comuníquese con la unidad de enfermería!");
		// RETORNAR DATOS DE PERSONAL
		return $data;
	}
	
	/*
	 * CONSULTAR DATOS DE PERSONAL
	 */
	private function requestByPersonCC($code){
		// STRING PARA CONSULTAR DATOS DE PERSONAL
		$str=$this->db->selectFromView("vw_personal","WHERE persona_doc_identidad='{$code}' ORDER BY personal_definicion DESC LIMIT 1");
		// VALIDAR LA EXISTENCIA DEL PERSONAL
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado ningún registro asociado con el número de cédula que ha ingresado.");
		// OBTENER INFORMACIÓN DE REGISTRO
		$data=$this->db->findOne($str);
		// STRING PARA CONSULTAR HISTORIA CLINICA
		$str=$this->db->selectFromView($this->entity,"WHERE fk_paciente_id={$data['personal_id']}");
		// VALIDAR SI EXISTE LA HISTORIA CLINICA
		if($this->db->numRows($str)>0) $this->getJSON("La historia clínica para <b>{$data['personal_nombre']}</b> ya ha sido registrada!");
		// MODAL PARA REGISTRO
		$data['modal']='Historiasclinicas';
		$data['edit']=false;
		// RETORNAR DATOS DE PERSONAL
		return $data;
	}
	
	/*
	 * CONSULTAR DETALLE DE HISTORIA CLÍNICA
	 */
	private function requestById($historyId){
		// CONSULTAR REGISTRO
		$data=$this->db->viewById($historyId,$this->entity);
		// PERFIL LABORAL DEL PERSONAL
		$data['info']=$this->db->findAll($this->db->selectFromView('vw_ppersonal',"WHERE fk_personal_id={$data['personal_id']}"));
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
		// CONSUTAR POR ID
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO DE REGISTRO
		elseif(isset($this->post['code'])) $json=$this->requestByPersonCC($this->post['code']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
}