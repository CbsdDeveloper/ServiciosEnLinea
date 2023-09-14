<?php namespace model\Tthh\md;
use model as mdl;
use controller as ctrl;

class medicalCertificatesModel extends mdl\personModel {
	
	/*
	 * VARIABES GLOBALES
	 */
    private $entity='certificadosmedicos';
    private $PARAMS_TO_REQUEST;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
	    parent::__construct();
	    
	    // PARSE VARIABLE
	    $this->PARAMS_TO_REQUEST=$this->string2JSON($this->varGlobal['MAIN_DAYS_TO_REQUEST'])['tthh'];
	}
	
	
	/*
	 * REGISTRAR OBSERVACION DE JUSTIFICACION
	 * REGISTRAR JUSTIFICACIÓN DE INASISTENCIA
	 */
	private function justifyNonAttendance($staffId,$responsibleId){
	    // ESTADO DE INASISTENCIA
	    $opt="NO";
	    // VALIDAR ESTADO DE CERTIFICADO
	    if($this->post['certificado_estado']=='CERTIFICADO VALIDADO'){
	        
	        // MODELO DE INASISTENCIA
	        $absence=$this->db->findById($this->post['absenceId'],'inasistencias');
	        // ACTUALIZACION DE MODELO
	        $absence['inasistencia_estado']='REGISTRO JUSTIFICADO';
	        $absence['inasistencia_fecha_justificacion']=$this->getFecha('dateTime');
	        $absence['fk_justifica_id']=$responsibleId;
	        // JUSTIFICAR INASISTENCIA
	        $this->db->executeTested($this->db->getSQLUpdate($absence,'inasistencias'));
	        
	        // ACTUALIZAR REGISTRO
	        $opt="SI";
	    }
	    
	    // MODELO PARA REGISTRO DE OBSERVACIONES
	    $description="El día {$this->post['certificado_fecha_registro']} se registra el certificado # {$this->post['certificado_serie']} ({$this->post['certificado_estado']}) ";
	    $description.="emitido por {$this->post['certificado_doctor']} ({$this->post['certificado_entidad']}), mismo que {$opt} justifica la inasistencia registrada.";
	    $temp=array(
	        'fk_inasistencia_id'=>$this->post['absenceId'],
	        'control_tipo'=>'JUSTIFICACIÓN',
	        'control_descripcion'=>$description,
	        'control_adjunto'=>$this->post['certificado_adjunto']
	    );
	    // REGISTRAR OBSERVACION
	    $this->db->executeTested($this->db->getSQLInsert($temp,'inasistencias_control'));
	    
	}
	
	/*
	 * NÚMERO DE ORDEN DE ABASTECIMIENTO POR UNIDAD
	 */
	private function getNextOrder($clinicalId){
		// CUSTOM TABLE
		$tb=$this->db->setCustomTable($this->entity,'COUNT(fk_historiaclinica_id) serie');
		// GENERAR CONSULTA
		$serie=$this->db->findOne($this->db->selectFromView($tb,"WHERE fk_historiaclinica_id={$clinicalId}"));
		// RETORNAR SERIE DE ATENCION
		return ($serie['serie']==null?0:$serie['serie'])+1;
	}
	
	/*
	 * COMPLETAR INFORMACION DE FORMULARIO
	 */
	private function testModule(){
	    // OBTENER DIAS MÁXIMOS PARA REGISTRAR CERTIFICADOS
	    $maxDaysToRequest=$this->PARAMS_TO_REQUEST['MAX_DAYS_TO_REQUEST'][$this->entity];
	    
	    // ESTADO DE CERTIFICADOS
	    $this->post['certificado_estado']=(isset($this->post['certificado_estado']))?$this->post['certificado_estado']:'VALIDACION PENDIENTE';
	    
	    // VALIDAR MODULO DE ENVÍO
	    if($this->sysName=='tthh'){
	        // OBTENER FECHA MÍNIMA DE VALIDACIÓN
	        $minDate=$this->addDays($this->getFecha(), $maxDaysToRequest, '-');
	        // VALIDAR FECHA DE EMISIÓN
	        if( ($this->post['certificado_reposomedico']=='SI' && strtotime($minDate) > strtotime($this->post['certificado_fecha_emision'])) || 
	            (strtotime($minDate) > strtotime($this->post['certificado_reposomedico_desde'])) ){
	            $this->getJSON($this->msgReplace($this->PARAMS_TO_REQUEST['MSG']['MAX_DAYS_TO_REGISTER_MEDICALCERTIFICATES'], array('MAX_DAYS_TO_REQUEST'=>$maxDaysToRequest)));
	        }
	        // DEFINIR ID DE PERSONAL - JUSTIFICAR
	        $staffId=$this->getStaffIdBySession();
	    }else{
	        if(isset($this->post['staffId'])) $staffId=$this->post['staffId'];
	        elseif(isset($this->post['personal_id'])) $staffId=$this->post['personal_id'];
	    }
	    
	    // CONSULTAR HISTORIA UMERO DE CLINICA 
	    $hc=$this->db->findOne($this->db->selectFromView('historiasclinicas',"WHERE fk_paciente_id={$staffId}"));
	    // REGISTRAR NUMERO DE HISTORIA CLINICA
	    $this->post['fk_historiaclinica_id']=$hc['historia_id'];
	    
	    // OBTENER PERFIL DE SESION
	    $session=$this->getPPersonalIdBySession();
	    // REGISTRAR ID DE PPERSONAL
	    $this->post['fk_registra_id']=$session;
	    
	    // VALIDAR FECHA DE REGISTRO
	    if(!isset($this->post['certificado_fecha_registro'])) $this->post['certificado_fecha_registro']=$this->getFecha('dateTime');
	}
	
	/*
	 INGRESO DE UN NUEVO REGISTRO
	 */
	public function insertEntity(){
	    // VALIDAR FORMULARIO
	    $this->testModule();
	    
	    // VALIDAR Y CARGAR ARCHIVO
	    $this->post=$this->uploaderMng->uploadFileService($this->post,'certificadosmedicos');
		
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// VERIFICAR/GENERAR NÚMERO DE ORDEN DE MANTENIMIENTO
		$this->post['certificado_serie']=$this->getNextOrder($this->post['fk_historiaclinica_id']);
		
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		$entityId=$this->db->getLastID($this->entity);
		
		// ADJUNTAR ID DE REGISTRO PARA IMRPRESIÓN
		$sql['data']=array('id'=>$entityId);
		
		// NOTIFICAR POR EMAIL
		$this->notifyByEmail($entityId);
		
		// JUSTIFICAR INASISTENCIA
		if(isset($this->post['absenceId'])) $this->justifyNonAttendance($this->post['staffId'],$this->getStaffIdBySession(),$entityId);
		
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZACION DE CONSULTAS MEDICAS
	 */
	public function updateEntity(){
		
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// STRING PARA ACTUALIZAR REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * NOTIFICACIÓN POR CORREO ELECTRONICO
	 */
	private function notifyByEmail($modelId,$oldData=array()){
	    
	    // DATOS DE CAPACITACIÓN
	    $model=$this->db->findOne($this->db->selectFromView("vw_certificadosmedicos_externos","WHERE certificado_id={$modelId}"));
	    $model=array_merge($model,$this->db->findById($modelId,$this->entity));
	    
	    // OBTENER CORREO DEL PACIENTE
	    $aux=$this->db->findById($model['fk_personal_id'],'personal');
	    // CORREO DE PERSONA DE CERTIFICADO
	    $model['paciente_correo']=$aux['personal_correo_institucional'];
	    
	    // DEFINIR ASUNTO
	    $subject="Nuevo certificado médico";
	    
	    // INICIALIZAR SERVIDOR DE CORREOS
	    $mailCtrl=new ctrl\mailController('certificadomedico');
	    
	    // LISTA DE DESTINATARIOS
	    foreach($this->db->findAll($this->db->selectFromView("vw_certificadosmedicos_destinatarios")) as $v){
    	    // LISTA DE DESTINATARIOS DEL CORREO
	        if(!in_array($v['correo'],$mailCtrl->destinatariosList)) $mailCtrl->destinatariosList[]=$v['correo'];
	    }
	    
	    // VALIDAR VARIABLES
	    $model['ausencia']=$model['reposo']=='NO'?"NO REQUERIDO":"{$model['reposo_desde']} AL {$model['reposo_hasta']}"; 
	    
	    // ADJUNTAR DATOS DE DEPARTAMENTO
	    $mailCtrl->mergeData($this->getConfParams('TTHH'));
	    // ADJUNTAR DATOS DE REGISTRO
	    $mailCtrl->mergeData($model);
	    
	    // ENVIAR CORREO
	    $mailCtrl->asyncMail($subject,$model['paciente_correo']);
	}
	
	/*
	 * CONSULTAR DATOS DE PERSONAL
	 */
	private function requestByPerson($code){
		// STRING PARA CONSULTAR DATOS DE PERSONAL
		$str=$this->db->selectFromView("vw_historiasclinicas","WHERE persona_doc_identidad='{$code}'");
		// VALIDAR LA EXISTENCIA DEL PERSONAL
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado ninguna historia clínica para el número de cédula ingresado.");
		// OBTENER Y RETORNAR INFORMACIÓN DE REGISTRO
		$history=$this->db->findOne($str);
		
		// DATOS DE MODELO
		$history['fk_personal_id']=$this->getStaffIdBySession();
		$history['fk_historiaclinica_id']=$history['historia_id'];
		$history['certificado_reposomedico']='NO';
		$history['certificado_estado']='VALIDACION PENDIENTE';
		
		// RETORNAR DATOS DE PERSONAL
		return $history;
	}
	
	/*
	 * REENVIAR NOTIFICACIONES DE CORREO
	 */
	private function resendNotification($entityId){
	    
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
	    // CONSULTAR POR CODIGO
	    if(isset($this->post['code'])) $json=$this->requestByPerson($this->post['code']);
	    // REENVIAR CORREO ELECTRONICO
	    if(isset($this->post['type']) && $this->post['type']=='notification') $json=$this->notifyByEmail($this->post['entityId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}