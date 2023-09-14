<?php namespace model\Tthh;
use api as app;
use controller as ctrl;

class absenceModel extends app\controller {
	
	/*
	 * VARIABES GLOBALES
	 */
    private $entity='inasistencias';
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
	 * NÚMERO DE ORDEN DE ABASTECIMIENTO POR UNIDAD
	 */
	private function getNextOrder($staffId){
		// CUSTOM TABLE
		$tb=$this->db->setCustomTable($this->entity,'COUNT(fk_faltante_id) serie');
		// GENERAR CONSULTA
		$serie=$this->db->findOne($this->db->selectFromView($tb,"WHERE fk_faltante_id={$staffId}"));
		// RETORNAR SERIE DE ATENCION
		return ($serie['serie']==null?0:$serie['serie'])+1;
	}
	
	/*
	 * COMPLETAR INFORMACION DE FORMULARIO
	 */
	private function testModule(){
	    // ID DE SESSION
	    $sessionId=$this->getStaffIdBySession();
	    
	    // ESTADO DE CERTIFICADOS
	    $this->post['inasistencia_estado']='REGISTRO PENDIENTE';
	    
	    // OBTENER DIAS MÁXIMOS PARA REGISTRAR CERTIFICADOS
	    $maxDaysToRequest=$this->PARAMS_TO_REQUEST['MAX_DAYS_TO_REQUEST'][$this->entity];
    
        // OBTENER FECHA MÍNIMA DE VALIDACIÓN
	    $this->post['inasistencia_justificacion_maxima']=$this->addWorkDays($this->post['inasistencia_desde'], $maxDaysToRequest);
	    $this->post['inasistencia_fecha_registro']=$this->getFecha('dateTime');
	    
	    // REGISTRAR NUMERO DE HISTORIA CLINICA
	    $this->post['fk_faltante_id']=$this->post['personal_id'];
	    
	    // REGISTRAR ID DE PPERSONAL
	    $this->post['fk_registra_id']=$sessionId;
	    
	    // VERIFICAR/GENERAR NÚMERO DE ORDEN DE MANTENIMIENTO
	    $this->post['inasistencia_serie']=$this->getNextOrder($this->post['personal_id']);
	}
	
	/*
	 INGRESO DE UN NUEVO REGISTRO
	 */
	public function insertEntity(){
	    // VALIDAR FORMULARIO
	    $this->testModule();
		
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		$entityId=$this->db->getLastID($this->entity);
		
		// ADJUNTAR ID DE REGISTRO PARA IMRPRESIÓN
		$sql['data']=array('id'=>$entityId);
		
		// NOTIFICAR POR EMAIL
		$this->notifyByEmail($entityId);
		
		// MODELO TEMPORAL PARA EL REGISTRO DE OBSERVACIONES Y CONTROLES
		$description="El día {$this->post['inasistencia_fecha_registro']} se registra la inasistencia # {$this->post['inasistencia_serie']} de {$this->post['person']['persona_apellidos']} {$this->post['person']['persona_nombres']} ";
		$description.="y se deja registrado como fecha máxima de justificación el día {$this->post['inasistencia_justificacion_maxima']}; ";
		$description.="adicionalmente, se ha notificado por correo electrónico a las siguientes direcciones {$this->post['recipientsList']}";
		$temp=array(
		    'fk_personal_id'=>$this->post['fk_registra_id'],
		    'fk_inasistencia_id'=>$entityId,
		    'control_tipo'=>'OBSERVACIÓN',
		    'control_descripcion'=>$description
		);
		
		// REGISTRAR PRIMERA OBSERVACION
		$this->db->executeTested($this->db->getSQLInsert($temp,'inasistencias_control'));
		
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
	    $model=$this->db->findOne($this->db->selectFromView("vw_inasistencias","WHERE inasistencia_id={$modelId}"));
	    
	    // PARSE DATA
	    $model['inasistencia_justificacion_maxima']=$this->setFormatDate($model['inasistencia_justificacion_maxima'],'complete');
	    $model['inasistencia_fecha_registro']=$this->setFormatDate($model['inasistencia_fecha_registro'],'full');
	    $model['inasistencia']=$this->setParseRangeDates($model['inasistencia_desde'],$model['inasistencia_hasta'])['fecha_alt'];
	    // LISTA DE DESTINATARIOS
	    $this->post['recipientsList']="{$model['faltante_correo']}";
	    
	    // INICIALIZAR SERVIDOR DE CORREOS
	    $mailCtrl=new ctrl\mailController('inasistencia');
	    
	    // LISTA DE DESTINATARIOS
	    foreach($this->db->findAll($this->db->selectFromView("vw_certificadosmedicos_destinatarios")) as $v){
    	    // LISTA DE DESTINATARIOS DEL CORREO
	        if(!in_array($v['correo'],$mailCtrl->destinatariosList)){
	            $mailCtrl->destinatariosList[]=$v['correo'];
	            $this->post['recipientsList'].=", {$v['correo']}";
	        }
	    }
	    
	    // ADJUNTAR DATOS DE DEPARTAMENTO
	    $mailCtrl->mergeData($this->getConfParams('TTHH'));
	    // ADJUNTAR DATOS DE REGISTRO
	    $mailCtrl->mergeData($model);
	    
	    // ENVIAR CORREO
	    $mailCtrl->asyncMail("Nueva inasistencia registrada",$model['faltante_correo']);
	}
	
	
	/*
	 * INGRESO DE COMENTARIOS
	 */
	public function insertComments(){
	    
	    // VALIDAR Y CARGAR ARCHIVO
	    $this->post=$this->uploaderMng->uploadFileService($this->post,'inasistencias_control',false);
	    
	    // EJECUTAR SENTENCIA SQL
	    $sql=$this->db->executeSingle($this->db->getSQLInsert($this->post,'inasistencias_control'));
	    
	    // CERRAR CONEXIÓN Y RETORNAR CONSULTA
	    $this->getJSON($sql);
	}
	
	
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
	    
		$this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true));
		
	}
	
}