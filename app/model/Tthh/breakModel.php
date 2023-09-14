<?php namespace model\Tthh;

/**
 * @author Lalytto
 *
 */
class breakModel extends personalModel {
	
	/*
	 * VARIABLES GLOBALES
	 */
	private $model;
	protected $varLocal;
	protected $PARAMS_TO_REQUEST;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct($entity){
		// CONSTRUCTOR PADRE
		parent::__construct();
		// VARIABLE LOCAL DE MÓDULO - TALENTO HUMANO
		$this->varLocal=$this->getConfParams('tthh');
		
		// PARSE VARIABLE
		$this->PARAMS_TO_REQUEST=$this->string2JSON($this->varGlobal['MAIN_DAYS_TO_REQUEST'])['tthh'];
		// NUMERO DE DIAS MINIMOS PARA GENERAR UNA SOLICITUD
		$this->varLocal['MIN_DAYS_TO_REQUEST']=$this->PARAMS_TO_REQUEST['MIN_DAYS_TO_REQUEST'][$entity];
		// NUMERO DE DIAS MAXIMOS PARA GENERAR UNA SOLICITUD
		$this->varLocal['MAX_DAYS_TO_REQUEST']=$this->PARAMS_TO_REQUEST['MAX_DAYS_TO_REQUEST'][$entity];
		// NUMERO MAXIMO DE SOLICITUDES PENDIENTES 
		$this->varLocal['MAX_AWAITING_REQUESTS']=$this->PARAMS_TO_REQUEST['MAX_AWAITING_REQUESTS'][$entity];
		// NUMERO DE DIAS LABORADOS PARA GENERAR UNA SOLICITUD
		$this->varLocal['MIN_DAYS_REGISTERED_TO_REQUEST']=$this->PARAMS_TO_REQUEST['MIN_DAYS_REGISTERED_TO_REQUEST'][$entity];
		// CANTIDAD MINIMA Y MAXIMA DE DIAS A SOLICITAR
		$this->varLocal['TOTAL_DAYS_MIN_REQUESTED']=$this->PARAMS_TO_REQUEST['TOTAL_DAYS_MIN_REQUESTED'][$entity];
		$this->varLocal['TOTAL_DAYS_MAX_REQUESTED']=$this->PARAMS_TO_REQUEST['TOTAL_DAYS_MAX_REQUESTED'][$entity];
		
		// DATOS PARA FORMULARIO
		$this->model=array(
		    
		    'solicita'=>null, // solicita
		    'concede'=>null, // jefe inmediato - 1
		    'revisa'=>null, // jefe inmediato - 2 
		    'autoriza'=>null, // director o responsable de la unidad
		    'acb'=>null, // analista control de bienes
		    'tthh'=>null,
		    
		    'encargadoestacion'=>null, // operativos
		    
		    'frmParent'=>array(
		        'fk_encargadoestacion'=>null, // ppersonal_id
		        
		        'fk_controlbienes'=>null, // ppersonal_id
		        
		        'permiso_motivo'=>'ASUNTOS PERSONALES',
		        'minTime'=>'00:00',
		        'maxTime'=>'00:00'
		    )
		);
		
	}
	
	
	/*
	 * VALIDAR FECHAS PARA SOLICITUD DE PERMISO Y VACACIONES
	 */
	private function testAvailableToRequest($staff){
	    // VALIDAR FECHA MÍNIMA PARA SOLICITAR PERMISO
	    $minDate=$this->addDays($staff['fecha_ingreso'],$this->varLocal['MIN_DAYS_REGISTERED_TO_REQUEST']);
	    // VALIDAR EL NÚMERO DE DÍAS MÍNIMOS LABORADOS PARA PODER SOLICITAR PERMISOS
	    if(strtotime($this->getFecha()) < strtotime($minDate)) $this->getJSON($this->msgReplace($this->PARAMS_TO_REQUEST['MSG']['MIN_DAYS_REGISTERED_TO_REQUEST'],array('minDate'=>$minDate,'workDays'=>$this->varLocal['MIN_DAYS_REGISTERED_TO_REQUEST'])));
	}
	
	/*
	 * INFORMACIÓN DEL PUESTO DEL PERSONAL
	 */
	private function getJobByStaff($personalId){
	    
	    // OBTENER INFORMACIÓN DE PUESTOS DEL PERSONAL
	    $staffInformation=$this->getStaffProfiles($personalId);
	    
	    /*
	     * VALIDACIONES DE PUESTO PARA GENERAR SOLICITUD
	     */
	    // VALIDAR EL TIPO DE CONTRATO
	    if($staffInformation['TITULAR']['data']['contrato']=='CONTRATO CIVIL') $this->getJSON("La modalidad del contrato no le permite acceder a este servicio!");
	    // VALIDAR FECHAS PARA SOLICITUD
	    $this->testAvailableToRequest($staffInformation['TITULAR']['data']);
	    // INFORMACION DE PERFIL ACTIVO
	    $cInformation=$staffInformation['ACTIVO'];
	    
	    /*
	     * DATOS DE SOLICITANTE
	     */
	    // DATOS DEL PERSONAL
	    $this->model['solicita']=$cInformation['data'];
	    // RELACION CON REGISTRO ACTIVO
	    $this->model['frmParent']['fk_usuario']=$cInformation['data']['personal_id'];
	    $this->model['frmParent']['fk_personal_id']=$cInformation['data']['personal_id'];
	    $this->model['frmParent']['fk_ppersonal_id']=$cInformation['data']['entidad_id'];
	    $this->model['frmParent']['fk_personal_entidad']=$cInformation['data']['entidad'];
	    $this->model['frmParent']['fk_personal_entidad_id']=$cInformation['data']['entidad_id'];
	    
	    /*
	     * DATOS DE DIRECTOR DE TALENTO HUMANO
	     */
	    // DATOS DE DIRECTOR DE TALENTO HUMANO
	    $this->model['tthh']=$staffInformation['TTHH'];
	    // SETTEAR ID REGISTRO DE TALENTO HUMANO
	    $this->model['frmParent']['fk_tthh']=$this->model['tthh']['entidad_id'];
	    $this->model['frmParent']['fk_tthh_id']=$this->model['tthh']['personal_id'];
	    $this->model['frmParent']['fk_tthh_entidad']=$this->model['tthh']['entidad'];
	    $this->model['frmParent']['fk_tthh_entidad_id']=$this->model['tthh']['entidad_id'];
	    
	    /*
	     * DATOS DE JEFE INMEDIATO
	     */
	    // VALIDAR ENTIDAD PARA REGISTRO DE JEFE INMEDIATO
	    $this->model['autoriza']=(isset($cInformation['bossAlt']))?$cInformation['bossAlt']:$cInformation['boss'];
	    // SETTEAR ID REGISTRO DE JEFE INMEDIAITO
	    $this->model['frmParent']['fk_jf']=$this->model['autoriza']['entidad_id'];
	    $this->model['frmParent']['fk_jf_id']=$this->model['autoriza']['personal_id'];
	    $this->model['frmParent']['fk_jf_entidad']=$this->model['autoriza']['entidad'];
	    $this->model['frmParent']['fk_jf_entidad_id']=$this->model['autoriza']['entidad_id'];
	    
	    /*
	     * VALIDAR PUESTO QUIEN CONCEDE EL PERMISO
	     */
	    if(isset($cInformation['concede']) && !isset($cInformation['encargadoestacion'])){
	        // VALIDAR SI PERSONAL ESTA DE VACACIONES
	        $validation=$this->testPersonalInVacation($cInformation['concede']['personal_id'], $this->getFecha());
	        if(!$validation['status']){
    	        $this->model['concede']=$cInformation['concede'];
    	        // SETTEAR ID REGISTRO DE JEFE INMEDIAITO
    	        $this->model['frmParent']['fk_concede_id']=$this->model['concede']['personal_id'];
    	        $this->model['frmParent']['fk_concede_entidad']=$this->model['concede']['entidad'];
    	        $this->model['frmParent']['fk_concede_entidad_id']=$this->model['concede']['entidad_id'];
	        }
	    }
	    
	    /*
	     * VALIDAR PUESTO QUIEN CONCEDE EL PERMISO EN OPERATIVOS
	     */
	    if(isset($cInformation['encargadoestacion'])){
	        // VALIDAR SI PERSONAL ESTA DE VACACIONES
	        $validation=$this->testPersonalInVacation($cInformation['encargadoestacion']['fk_personal_id'], $this->getFecha());
	        if(!$validation['status']){
    	        $this->model['encargadoestacion']=$cInformation['encargadoestacion'];
    	        // SETTEAR ID REGISTRO DE JEFE INMEDIAITO
    	        $this->model['frmParent']['fk_jf']=$this->model['encargadoestacion']['ppersonal_id'];
    	        $this->model['frmParent']['fk_encargadoestacion']=$this->model['encargadoestacion']['ppersonal_id'];
    	        $this->model['frmParent']['fk_peloton']=$this->model['encargadoestacion']['tropa_id'];
    	        
    	        $this->model['concede']=$cInformation['encargadoestacion']['data'];
    	        // SETTEAR ID REGISTRO DE JEFE INMEDIAITO
    	        $this->model['frmParent']['fk_concede_id']=$cInformation['encargadoestacion']['data']['personal_id'];
    	        $this->model['frmParent']['fk_concede_entidad']=$cInformation['encargadoestacion']['data']['entidad'];
    	        $this->model['frmParent']['fk_concede_entidad_id']=$cInformation['encargadoestacion']['data']['entidad_id'];
	        }
	    }
	    
	    /*
	     * DATOS DE UNIDAD DE OPERACIONES
	     */
	    if(isset($cInformation['operaciones'])){
	        // VALIDAR SI PERSONAL ESTA DE VACACIONES
	        $validation=$this->testPersonalInVacation($cInformation['operaciones']['personal_id'], $this->getFecha());
	        if(!$validation['status']){
	            if(isset($cInformation['encargadoestacion']) && isset($this->model['concede'])){
    	            $this->model['revisa']=$cInformation['operaciones'];
    	            // SETTEAR ID REGISTRO DE JEFE INMEDIAITO
    	            $this->model['frmParent']['fk_revisa_id']=$cInformation['operaciones']['personal_id'];
    	            $this->model['frmParent']['fk_revisa_entidad']=$cInformation['operaciones']['entidad'];
    	            $this->model['frmParent']['fk_revisa_entidad_id']=$cInformation['operaciones']['entidad_id'];
    	        }else{
    	            $this->model['concede']=$cInformation['operaciones'];
    	            // SETTEAR ID REGISTRO DE JEFE INMEDIAITO
    	            $this->model['frmParent']['fk_concede_id']=$cInformation['operaciones']['personal_id'];
    	            $this->model['frmParent']['fk_concede_entidad']=$cInformation['operaciones']['entidad'];
    	            $this->model['frmParent']['fk_concede_entidad_id']=$cInformation['operaciones']['entidad_id'];
    	        }
	        }
	    }
	    
	    /*
	     * HISTORIAL DE REGISTRO DE VACACIONES
	     */
	    $this->model['solicita']['vacations']=$this->db->findOne($this->db->selectFromView('vacaciones_generadas',"WHERE fk_personal_id={$personalId} ORDER BY vacacion_periodo_inicio LIMIT 1"))['vacacion_periodo_inicio'];
	    
	}
	
	
	/*
	 * CONSULTAR DISPONIBILIDAD DE LA CUENTA
	 */
	protected function requestByAccount($personalId,$entity){
		// STATUS ENTITY
	    $status=($entity=='vacaciones_solicitadas')?'vacacion_estado':'permiso_estado';
	    
	    // MODELO DE CONSULTA
	    $this->getJobByStaff($personalId,$entity);
		
		// VALIDAR EL MÁXIMO DE PERMISOS PENDIENTES ACUMULADOS
		$str=$this->db->selectFromView($entity,"WHERE fk_personal_id={$personalId} AND {$status}='SOLICITUD GENERADA'");
		// return $str;
		// SI EXISTE MÁS DEL NÚMERO DE PERMISOS PENDIENTES RETORNAR MENSAJE
		if($this->db->numRows($str)>=$this->varLocal['MAX_AWAITING_REQUESTS']) $this->getJSON($this->msgReplace($this->PARAMS_TO_REQUEST['MSG']['MAX_AWAITING_REQUESTS'],$this->varLocal));
		
		// OBTENER LA INFORMACIÓN DE VACACIONES DEL PERSONAL
		$this->model['vacation']=$this->db->findOne($this->db->selectFromView('vw_vacaciones',"WHERE fk_personal_id={$personalId}"));
		
		// RETORNAR LOS DATOS DE CONSULTA
		return $this->model;
	}
	
	/*
	 * FIRMAS DE RESPONSABLES
	 */
	protected function signatureResponsible($row,$entity='PERMISO'){
	    // LISTADO DE FIRMAS PARA IMPRESION
	    $signatures=array(
	        'encargadoestacion'=>array('puesto'=>'es_estacion','nombre'=>'es_titulo','accion'=>'CONCEDE'),
	        'concede'=>array('puesto'=>'cd_puesto','nombre'=>'cd_titulo','accion'=>'CONCEDE'),
	        'revisa'=>array('puesto'=>'rev_puesto','nombre'=>'rev_titulo','accion'=>'REVISA'),
	        'autoriza'=>array('puesto'=>'jf_puesto','nombre'=>'jf_titulo','accion'=>'AUTORIZA'),
	        'tthh'=>'..........................................................................<br /><strong>TTHH</strong><br />REGISTRA',
	    );
	    // INDEX DE FIRMA
	    $i=1;
	    
	    // VALIDAR SI EXISTE ENCARGADO DE ESTACION
	    if(isset($row['fk_encargadoestacion'])){
	        // IMPRIMIR FIRMA
	        $row["firma{$i}"]=$this->varGlobal['ROW_SIGNATURE_PERMISOSVACACIONES'];
	        // RECORRER ATRIBUTOS DE IMPRESION
	        foreach($signatures['encargadoestacion'] as $k1=>$v1){ $row["firma{$i}"]=preg_replace('/{{'.$k1.'}}/',((isset($row[$v1])?$row[$v1]:$v1)),$row["firma{$i}"]); }
	        unset($signatures['concede']);
	        // INCREMENTAR INDEX
	        $i++;
	    }
	    
	    // VALIDAR INFORMACION
	    foreach($signatures as $k=>$v){
	        // VALIDAR SI EXISTE EL RESPONSABLE
	        if(isset($row[$k])){
	            // IMPRIMIR FIRMA
	            $row["firma{$i}"]=$this->varGlobal['ROW_SIGNATURE_PERMISOSVACACIONES'];
	            // RECORRER ATRIBUTOS DE IMPRESION
	            foreach($v as $k1=>$v1){ $row["firma{$i}"]=preg_replace('/{{'.$k1.'}}/',((isset($row[$v1])?$row[$v1]:$v1)),$row["firma{$i}"]); }
	            // INCREMENTAR INDEX
	            $i++;
	        }
	        
	    }
	    // IMPRESION DE TTHH
	    $row["firma{$i}"]=$signatures['tthh'];
	    
	    // ELIMINAR RESTO DE FIRMAS SIN UTILIZAR
	    for($x=($i+1);$x<=4;$x++){ $row["firma{$x}"]=""; }
		
		// CARGAR PLANTILLA 
	    $row['FIRMAS_RESPONSABLES']=$this->varGlobal["TEMPLATE_SIGNATURE_PERMISOSVACACIONES"];
		
		// MODELO
		return $row;
	}
	

}