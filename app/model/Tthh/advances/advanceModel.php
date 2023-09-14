<?php namespace model\Tthh\advances;
use api as app;
use controller as ctrl;
/**
 * Description of advanceModel
 *
 * @author Lalytto
 * @mail apinango@lalytto.com
 * 
 */
class advanceModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABLES LOCALES
	 */
	private $entity='anticipos';
	private $varLocal;
	private $insert=0;
	private $update=0;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PARENT
		parent::__construct();
		// VARIABLES LOCALES
		$this->varLocal=$this->getConfParams('tthh');
		// VARIABLES DE ENTORNO - DIAS PARA SOLICITAR ANTICIPOS
		$this->varLocal['TTHH_MIN_DAYS_TO_REQUEST']=$this->string2JSON($this->varGlobal['MAIN_DAYS_TO_REQUEST'])['tthh'];
		// PARAMETROS PARA ANTICIPOS
		$this->varLocal['ADVANCE_ALLOW_PARAMS']=$this->string2JSON($this->varGlobal['ADVANCE_ALLOW_PARAMS']);
	}
	
	/*
	 * CONSULTAR POR CÓDIGO
	 */
	private function requestByCode($code){
		// MENSAJE POR DEFECTO
		$json="Código de solicitud no registrado!";
		// CONSULTAR DISPONIBILIDAD EN BASE DE DATOS
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(anticipo_codigo)=UPPER('{$code}')");
		// VALIDAR CODIGO DE SOLICITUD
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN
			if(in_array($row['anticipo_estado'],['SOLICITUD GENERADA'])){
				// VALIDAR PERMISO PARA APROBAR PERMISO
				if(!in_array(80343,$this->getAccesRol())) $this->getJSON("No cuenta con permisos para aprobar esta solicitud!");
				// MODAL A DESPLEGAR
				$row['modal']='Anticipos';
				$row['tb']=$this->entity;
				// DATOS DE RETORNO
				return $row;
			} elseif(in_array($row['anticipo_estado'],['SOLICITUD NEGADA'])){
				$this->getJSON("La solicitud <b>{$code}</b> ha sido negada por <b>{$row['usuario']}</b>, para mayor información comuníquese con la Dirección de Talento Humano!");
			} elseif(in_array($row['anticipo_estado'],['SOLICITUD APROBADA'])){
				$this->getJSON("La solicitud <b>{$code}</b> ya ha sido aprobada!");
			} else{
				$this->getJSON("Solicitud inválida, favor acérquese a la Dirección de Talento Humano para mayor información.");
			}
		}
		// RETORNAR MENSAJE
		$this->getJSON($json);
	}
	
	/*
	 * CALCULAR CUOTAS POR PERFIL
	 */
	private function getCoutesProfile($staff,$advance,$date){
		
		// VARIABLES AUXILIARES
		$year=$this->setFormatDate($date,'Y');
		$month=$this->setFormatDate($date,'n');
		$currentMonth=$this->getFecha('n');
		
		// CALCULAR CUOTAS
		$coutes=array();
		
		// CALCULAR FECHA ACTUAL
		$currentDate=$this->setFormatDate("{$year}-{$month}",'Y-m');
		
		// VALIDAR SI EL ANTICIPO ESTA VIGENTE HASTA EL FINAL DEL PERIODO FISCAL
		$endFiscal=($advance['tanticipo_periodofiscal']=='SI')?(12-$currentMonth):$advance['tanticipo_meses'];
		
		// GENERAR LISTADO DE MESES QUE FINALIZAN EL PERIODO FISCAL
		for($i=1;$i<=($endFiscal-1);$i++){
			// PARSE MES ACTUAL
			$newMonth=date('Y-m',strtotime("+{$i} month",strtotime($currentDate)));
			// CUOTAS DE NUEVO MES
			$coutes[intval($i+1)]=$newMonth;
		}
		
		// RETORNAR CALCULO DE CUOTAS
		return $coutes;
		
	}
	
	/*
	 * VALIDAR PERFILES DE PERSONAL
	 */
	private function getProfileActive($personId){
	    
	    // CONSULTAR PERFILES DE SOLICITANTE
	    $str=$this->db->selectFromView('vw_personal',"WHERE fk_persona_id={$personId} AND ppersonal_estado='EN FUNCIONES' ORDER BY puesto_grado");
	    // VALIDAR CONSULTA
	    if($this->db->numRows($str)<1) $this->getJSON("Lamentamos los inconvenientes, al parecer su cuenta no está habilitada!");
	    
	    // BUSCAR TIPO DE CONTRATO PARA VALIDAR TIPO DE ANTICIPO
	    $typeAdvance=array();
	    $typeContract=array();
	    
	    // RECORRER PERFILES
	    foreach($this->db->findAll($str) as $job){
	        
	        // VALIDAR SI HA SIDO SETTEADO EL PERFIL
	        if(!empty($typeAdvance)) continue;
	        
	        // STRING PARA BUSCAR TIPO DE CONTRATO
	        $tmpStr=$this->db->selectFromView('tiposcontratos',"WHERE tcontrato_nombre='{$job['personal_contrato']}'");
	        // VALIDAR SI EXISTE TIPO DE CONTRATO
	        if($this->db->numRows($tmpStr)<1) continue;
	        // OBTENER TIPO DE CONTRATO
	        $tmpContract=$this->db->findOne($tmpStr);
	        
	        // VALIDAR SI ESTÁ HABILITADO PARA SOLICITAR ANTICIPOS
	        $tmpAdvance=$this->db->findOne($this->db->selectFromView('tiposanticipos',"WHERE tanticipo_id={$tmpContract['fk_tipoanticipo_id']}"));
	        // VALIDAR SI ES CORRECTO EL TIPO DE ANTICIPO
	        if(!isset($tmpAdvance) || empty($tmpAdvance)) continue;
	        // VALIDAR SI EL TIPO DE ANTICIPO ESTA HABILITADO
	        if($tmpAdvance['tanticipo_habilitado']=='NO') continue;
	        
	        // VALIDAR SI ES ENCARGO
	        if($job['personal_definicion']!='TITULAR' && $tmpAdvance['tanticipo_sueldoencargos']=='NO') continue;
	        
	        $typeAdvance=$tmpAdvance;
	        $typeContract=$job;
	        
	    }
	    
	    // VALIDAR SI NO SE HA VALIDADO NINGUN TIPO DE ANTICIPO
	    if(empty($typeAdvance)) $this->getJSON("Lo sentimos, al parecer el tipo de contrato que usted registra no se encuentra habilitado para proceder a realizar esta solicitud");
	    
	    // RETORNAR DATOS PARA ANTICIPO
	    return array('advance'=>$typeAdvance,'staff'=>$typeContract);
	    
	}
	
	/* 
	 * CONSULTAR POR CC DE USUARIO
	 */
	private function requestByPersonId($personId){
	    
	    // VARIABLES GENERALES
	    $gnrlConf=$this->varLocal['ADVANCE_ALLOW_PARAMS']['general'];
	    
	    // VALIDAR SI EL MÓDULO HA SIDO HABILITADO DESDE PARAMETRIZACION
	    if($gnrlConf['enable']=='NO') $this->getJSON($this->setJSON($gnrlConf['infoUnable'],'info'));
	    
	    // CONSULTAR PERFIL PARA SOLICITAR ANTICIPOS
	    $conf=$this->getProfileActive($personId);
	    // PASRSE DE VARIABLES
	    $confAdvance=$conf['advance'];
	    $staff=$conf['staff'];
		
		// VALIDAR TIPO DE CONTRATO Y MODALIDAD DE TRABAJO
		if(in_array($staff['personal_id'],$this->varLocal['ADVANCE_ALLOW_PARAMS']['personal']) || in_array($staff['puesto_modalidad'],$this->varLocal['ADVANCE_ALLOW_PARAMS']['modalidad'])){
			
			// STRING PARA CONSULTAR ANTICIPOS PREVIOS
			$str=$this->db->selectFromView("vw_{$this->entity}","WHERE fk_solicitante_id={$staff['ppersonal_id']} AND anticipo_status<>'ANTICIPO CANCELADO' AND anticipo_estado NOT IN ('ANTICIPO PRECANCELADO','SOLICITUD ANULADA','SOLICITUD NEGADA')");
			// CONSULTAR SI EL PERSONAL YA CUENTA CON UN ANTICIPO
			if($this->db->numRows($str)>0){
				// REGISTRO DE ANTICIPO
				$row=$this->db->findOne($str);
				// MENSAJE DE REGISTRO
				$this->getJSON("Lamentamos los inconvenientes, al parecer usted ya cuenta con un anticipo [<b>{$row['anticipo_codigo']}</b>], el cual tiene <b>{$row['anticipo_status']}</b>");
			}
			
			// VARIABLES AUXILIARES
			$year=$this->getFecha('Y');
			$month=$this->getFecha('n');
			$today=$this->getFecha();
			
			// GENERAR FECHA MÍNIMA PARA SOLICITAR PERMISO
			$minDate=$this->addDays($staff['personal_fecha_ingreso'],$this->varLocal['TTHH_MIN_DAYS_TO_REQUEST']['MIN_DAYS_REGISTERED_TO_REQUEST'][$this->entity]);
			// VALIDAR EL NÚMERO DE DÍAS MÍNIMOS LABORADOS PARA PODER SOLICITAR PERMISOS
			if(strtotime($today) < strtotime($minDate)) $this->getJSON($this->msgReplace($this->varLocal['TTHH_MIN_DAYS_TO_REQUEST']['MSG']['MIN_DAYS_REGISTERED_TO_REQUEST'],array('minDate'=>$minDate,'workDays'=>$this->varLocal['TTHH_MIN_DAYS_TO_REQUEST']['MIN_DAYS_REGISTERED_TO_REQUEST'][$this->entity])));
			
			// FECHA MÁXIMA PARA REGISTRAR SOLICITUD EN EL MES
			$maxRequest=$this->getFecha($gnrlConf['maxDate']);
			// VALIDAR SI ESTÁ DENTRO DEL MISMO MES SINO SE GENERA PARA EL SIGUIENTE MES
			if(strtotime($today)>strtotime($maxRequest)) $month++;
			
			// CALCULAR FECHA ACTUAL
			$currentDate=$this->setFormatDate("{$year}-{$month}",'Y-m');
			
			// CALCULAR EL VALOR MINIMO DE REMUNERACION
			$min=$staff['puesto_remuneracion']*$confAdvance['tanticipo_sueldominimo'];
			// CALCULOS DE VALORES
			$aux=array(
			    'indications'=>$gnrlConf['indications'],
				'minAmount'=>$min,
			    'maxAmount'=>$staff['puesto_remuneracion']*$confAdvance['tanticipo_sueldomaximo'],
			    'coutes'=>$this->getCoutesProfile($staff,$confAdvance,$currentDate)
			);
			$model=array(
				'fk_solicitante_id'=>$staff['ppersonal_id'],
				'anticipo_monto'=>$min,
			    'anticipo_interes'=>$gnrlConf['interestAdvance'],
			    'anticipo_interesmora'=>$gnrlConf['interestLatePayment'],
				'anticipo_inicio'=>$this->setFormatDate("{$year}-{$month}",'Y-m')
			);
			
			// DATOS DE DIRECTOR FINANCIERO
			$f=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE puesto_id=9 AND estado IN ('EN FUNCIONES','DELEGACIÓN ASIGNADA')"));
			$model['fk_financiero_entidad']=$f['entidad'];
			$model['fk_financiero_entidad_id']=$f['entidad_id'];
			
			// DATOS DE DIRECTOR DE TALENTO HUMANO
			$t=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE puesto_id=12 AND ppersonal_estado IN ('EN FUNCIONES','DELEGACIÓN ASIGNADA')"));
			// ANALISTA DE TALENTO HUMANO
			$model['fk_analistatthh_id']=$t['ppersonal_id'];
			
			// RETORNAR CONSULTA
			return array(
				'aux'=>$aux,
				'model'=>$model,
				'staff'=>$staff,
				'guarants'=>$this->db->findAll($this->db->selectFromView('vw_garantes_libres',"ORDER BY personal_nombre")),
				'banks'=>$this->db->findAll($this->db->selectFromView('cuentasbancarias',"WHERE fk_persona_id={$personId} AND banco_estado='ACTIVA'"))
			);
			
		}else{
			
			// MENSAJE DE NO PERMITIR ANTICIPOS
			$this->getJSON("Lo sentimos, el tipo de contrato que usted mantiene con la institución no le permite acceder a este servicio!");
			
		}
		
	}
	
	/*
	 * CONSULTAR POR REGISTRO DE ANTICIPO
	 */
	private function requestById($id){
	    
	    // VARIABLES GENERALES
	    $gnrlConf=$this->varLocal['ADVANCE_ALLOW_PARAMS']['general'];
	    
		// STRING PARA CONSULTAR PERSONAL POR CC
		$advance=$this->db->viewById($id,$this->entity);
		
		// OBTENER DATOS DEL SOLICITANTE
		$staff=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$advance['fk_solicitante_id']}"));
		
		// CONSULTAR PERFIL PARA SOLICITAR ANTICIPOS
		$conf=$this->getProfileActive($staff['fk_persona_id']);
		// PASRSE DE VARIABLES
		$confAdvance=$conf['advance'];
		$staff=$conf['staff'];
		
		// VALOR MÍNIMO DEL ANTICIPO
		$min=$staff['puesto_remuneracion']*$confAdvance['tanticipo_sueldominimo'];
		
		// CALCULOS DE VALORES
		$aux=array(
		    'indications'=>$gnrlConf['indications'],
			'minAmount'=>$min,
		    'maxAmount'=>$staff['puesto_remuneracion']*$confAdvance['tanticipo_sueldomaximo'],
		    'coutes'=>$this->getCoutesProfile($staff,$confAdvance,$this->setFormatDate($advance['anticipo_inicio'],'Y-m-d'))
		);
		
		// RETORNAR CONSULTA
		return array(
			'aux'=>$aux,
			'staff'=>$staff,
			'model'=>$advance,
			'guarants'=>$this->db->findAll($this->db->selectFromView('vw_garantes_libres',"ORDER BY personal_nombre")),
			'banks'=>$this->db->findAll($this->db->selectFromView('cuentasbancarias',"WHERE fk_persona_id={$advance['fk_persona_id']} AND banco_estado='ACTIVA'"))
		);
	}
	
	/*
	 * CONSULTA DE REGISTROS
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO DE ACCIÓN O CÉDULA DE PERSONAL
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTAR POR CÓDIGO DE ACCIÓN O CÉDULA DE PERSONAL
		elseif(isset($this->post['personId'])) $json=$this->requestByPersonId($this->post['personId']);
		// CONSULTAR POR ID DE REGISTRO
		elseif(isset($this->post['advanceId'])) $json=$this->requestById($this->post['advanceId']);
		// CONSULTAR POR CÓDIGO DE ACCIÓN O CÉDULA DE PERSONAL
		elseif(isset($this->post['entityId'])) $json=$this->requestById($this->post['entityId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * INGRESAR NUEVOS REGISTROS
	 */
	public function insertEntity(){
		
		// CALCULAR MESES A PAGAR
		$this->post['model']['anticipo_fin']=date('Y-m',strtotime("{$this->post['model']['anticipo_inicio']}-01 +{$this->post['model']['anticipo_meses']} months -1 months"));
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INSERTAR ID DE RESPONSABLE DE REGISTRO
		$this->post['model']['fk_personal_id']=$this->getStaffIdBySession();
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post['model'],$this->entity));
		
		// RETORNAR DATOS DE REGISTRO
		$sql['data']=$this->db->getLastEntity($this->entity);
		
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICACIÓN DE REGISTROS
	 */
	public function updateEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// ID DE ACTUALIZACION
		$staffId=$this->getStaffIdBySession();
		// VALIDAR FORMULARIO
		if(isset($this->post['model'])){
			// SUMAR LOS MESES PARA PERIODOS
			$this->post['model']['anticipo_fin']=date('Y-m',strtotime("{$this->post['model']['anticipo_inicio']}-01 +{$this->post['model']['anticipo_meses']} months"));
			// RETORNAR DATOS DE REGISTRO
			$model=$this->post['model'];
		}else{
			// VALIDAR EL ESTADO DE ANTICIPO
			if($this->post['anticipo_estado']=='SOLICITUD APROBADA'){
				// FECHA DE APROBACIÓN DE ANTICIPO
				$this->post['anticipo_aprobado']=$this->getFecha();
				if($this->post['anticipo_serie']==0) $this->post['anticipo_serie']=$this->db->getNextSerie($this->entity);
				// REGISTRO DE RESPONSABLE
				$r=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE personal_id={$staffId} ORDER BY ppersonal_id"));
				// RESPONSABLE DE APROBACIÓN DE ANTICIPO
				$this->post['fk_aprueba_id']=$r['ppersonal_id'];
			}
			// RETORNAR DATOS DE REGISTRO
			$model=$this->post;
		}
		// INSERTAR ID DE RESPONSABLE
		$model['fk_personal_id']=$staffId;
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLUpdate($model,$this->entity));
		// RETORNAR DATOS DE REGISTRO
		$sql['data']=$model;
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// EXTRAER ID DE REGISTRO
		$id=$this->get['id'];
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE anticipo_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			$this->getJSON($row);
			
			// INGRESO DE DATOS
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['anticipo_codigo']);
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE anticipo_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// DATOS DE SOLICITANTE
			$s=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['fk_solicitante_id']}"));
			$row['s_direccion']="{$s['persona_principal']} y {$s['persona_secundaria']}";
			$row['s_contacto']="{$s['persona_telefono']} / {$s['persona_celular']} / {$s['persona_correo']}";
			$row['s_barrio']="{$s['persona_barrio_ciudadela']} / {$s['persona_barrio_sector']} / {$s['persona_no_casa']}";
			
			// DATOS DE GARANTE
			$g=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['fk_garante_id']}"));
			$row['g_direccion']="{$g['persona_principal']} y {$g['persona_secundaria']}";
			$row['g_contacto']="{$g['persona_telefono']} / {$g['persona_celular']} / {$g['persona_correo']}";
			$row['g_barrio']="{$g['persona_barrio_ciudadela']} / {$g['persona_barrio_sector']} / {$g['persona_no_casa']}";
			
			// VALORES A LETRAS
			$row['anticipo_monto_letras']=$this->setNumberToLetter($row['anticipo_monto'],true);
			$row['anticipo_solicitado']=$this->setFormatDate($row['anticipo_solicitado'],'complete');
			$row['periodo_inicio']=$this->setFormatDate($row['anticipo_inicio'],'short');
			$row['periodo_fin']=$this->setFormatDate($row['anticipo_fin'],'short');
			
			// PARSE DAYS
			$days=$this->getNumDaysFromDates($row['anticipo_inicio'], $row['anticipo_fin']);
			$row['anticipo_dias']=$days->days;
			
			// INGRESO DE DATOS
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
}