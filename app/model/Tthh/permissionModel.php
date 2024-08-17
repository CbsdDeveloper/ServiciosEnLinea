<?php namespace model\Tthh;
use api as app;
use controller as ctrl;

/**
 * @author Lalytto
 *
 */
class permissionModel extends breakModel {
	
	/*
	 * VARIABLES GLOBALES
	 */
	private $entity='permisos_solicitados';
	private $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct($this->entity);
	}
	
	
	/*
	 * PROCESAR DATOS SUBIDOS
	 */
	private function processList($pathFile){
		// INSTANCIA XLS
		$myExcel=new ctrl\xlsController();
		// OBTENER DATOS DE EXCEL
		$data=$myExcel->getCellValue($this->post,$pathFile,$this->entity);
		// VALIDAR LONGITUD DE DATOS
		if(count($data)<1)$this->getJSON("Operación abortada, no se ha encontrado ningun registro...");
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// RECORRER LISTADO
		foreach($data as $v){
			// STRING PARA CONSULTAR REGISTRO DE PERSONAL
			$str=$this->db->selectFromView("vw_basic_personal","WHERE persona_doc_identidad='{$v['persona_doc_identidad']}'");
			// VALIDAR EXISTENCIA DE REGISTRO
			if($this->db->numRows($str)<1)$this->getJSON($this->db->closeTransaction($this->setJSON("No se ha encontrado ningún registro relacionado con el # de identificación <b>{$v['persona_doc_identidad']}</b>, favor revise los datos y vuelva a intentar.")));
			// OBTENER REGISTRO DE PERSONAL
			$staff=$this->db->findOne($str);
			// VALIDAR DATOS
			if(in_array($v['permiso_fecha_hasta'],['1970-01-01',null,''])) $v['permiso_fecha_hasta']=$v['permiso_fecha_desde'];
			// GENERAR MODELO DE PERMISO
			$model=array(
				'fk_usuario'=>$staff['personal_id'],
				'permiso_registro'=>'2019-03-31',
				'permiso_estado'=>'SOLICITUD REGISTRADA',
				'permiso_parametro'=>$v['permiso_parametro'],
				'fk_personal_id'=>$staff['personal_id'],
				'permiso_motivo'=>'ASUNTOS PERSONALES',
				'permiso_tipo'=>'PERMISO',
				'permiso_multiplicador'=>$v['permiso_multiplicador'],
				'permiso_fecha_desde'=>$v['permiso_fecha_desde'],
				'permiso_fecha_hasta'=>$v['permiso_fecha_hasta'],
				'permiso_dias'=>($v['permiso_dias']==null)?0:$v['permiso_dias'],
				'permiso_hora_desde'=>$this->setDecimalToTime($v['permiso_hora_desde']*24,'H:i'),
				'permiso_hora_hasta'=>$this->setDecimalToTime($v['permiso_hora_hasta']*24,'H:i'),
				'permiso_horas'=>$v['permiso_horas'],
				'permiso_detalle'=>'SISTEMATIZACION DE PROCESOS DE TTHH 2019-04-07'
			);
			// EJECUTAR SENTENCIA DE CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
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
		$config=$this->uploaderMng->config['entityConfig'][$this->entity];
		// PARAMETROS DE CONSULTA
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA DE ARCHIVO
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// OBTENER ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['permisos_name']}";
		// COMPROBAR SI EL ARCHIVO SE HA SUBIDO CORRECTAMENTE
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// PROCEDER A INGRESAR DATOS
		$this->getJSON($this->processList($pathFile));
	}
	
	
	
	/*
	 * CONSULTAR DIAS PARA SOLICITAR PERMISO
	 */
	private function getScheduleWorkdays($workdayId){
	    // GENERAR MODELO TEMPORAL
	    $scheduleType=array();
	    
	    // GENERAR REGISTRO INDIVIDUAL DE HORARIOS DIARIOS
	    foreach($this->db->findAll($this->db->selectFromView('jornadashorario',"WHERE fk_jornada_id={$workdayId}")) as $t){
	        // RECORRER DIAS
	        foreach($this->string2JSON($t['horario_dias_semana']) as $i){
	            // HORARIO DE TRABAJO
	            $scheduleType[$i]=array(
	                'horario_rancho'=>$t['horario_rancho'],
	                'horario_horas_dia'=>$t['horario_horas_dia'],
	                'horario_entrada'=>$t['horario_entrada'],
	                'horario_salida'=>$t['horario_salida'],
	                'horario_break'=>$t['horario_break'],
	                'horario_entrada_break'=>$t['horario_entrada_break'],
	                'horario_salida_break'=>$t['horario_salida_break']
	            );
	        }
	    }
	    // RETORNAR HORARIOS DE TRABAJO
	    return $scheduleType;
	}
	
	/*
	 * CALCULO DE DIAS Y HORAS
	 */
	private function getDataToBreak($personal,$data){
	    // DÍAS ACEPTADOS PARA PERMISOS DEL PERSONAL POR MODALIDAD DE PERFIL
	    $schedules=$this->getScheduleWorkdays($personal['fk_jornada_id']);
	    
		// CONTADOR DE DÍAS
		$data['permiso_semana']=0;
		$data['permiso_weekend']=0;
		$data['permiso_dias']=0;
		$data['permiso_horas']=0;
		$data['permiso_hora_desde']="0:00";
		$data['permiso_hora_hasta']="0:00";
 		// PARSE FECHAS DE SOLICITUD
		$data['permiso_fecha_desde']=$this->setFormatDate($data['permiso_desde'],'Y-m-d');
		$data['permiso_fecha_hasta']=$this->setFormatDate($data['permiso_hasta'],'Y-m-d');
		
		// NUMERO DE DIA DE LA SEMANA DE SOLICITUD DE PERMISO
		$auxDate=$this->setFormatDate($data['permiso_fecha_desde'],'w');
		
		// VALIDAR SI EL PERMISO HA SIDO GENERADO DENTRO DE LOS DIAS PERMITIDOS PARA LAS JORNADAS DE TRABAJO
		if(!isset($schedules[$auxDate])) $this->getJSON("Usted no puede generar esta solicitud, la solicitud que está intentando generar no está dentro de su carga de trabajo.");
		
		// VALIDAR SI EL PERMISO ES POR DIAS O POR HORAS
		if($data['permiso_parametro']=='HORAS'){
		    
			// PARSE FECHAS DE SOLICITUD
			$data['permiso_hora_desde']=$this->setFormatDate($data['permiso_desde'],'H:i');
			$data['permiso_hora_hasta']=$this->setFormatDate($data['permiso_hasta'],'H:i');
			
			// DATOS DEL DIA SOLICITADO
			$scheduleDay=$schedules[$auxDate];
			
			// CARGAR INFORMACIÓN DE HORAS A DESCONTAR
			$data['permiso_multiplicador']=$scheduleDay['horario_horas_dia'];
			
			// RECORRER LOS DÍAS PARA CONTABILIZAR LOS DÍAS
			$data['permiso_horas']=((strtotime("{$data['permiso_hasta']}")-strtotime("{$data['permiso_desde']}"))/3600);
			
			// VALIDAR SI LA JORNADA DE TRABAJO TIENE TIEMPO DE RECESO
			if($scheduleDay['horario_break']=='SI'){
			    // TIEMPO DE RECESO
			    $timeBreak=abs(strtotime($scheduleDay['horario_entrada_break'])-strtotime($scheduleDay['horario_salida_break']))/3600;
			    // VALIDAR HORARIO DE ALMUERZO
			    if( strtotime("{$data['permiso_hora_desde']}")<=strtotime($scheduleDay['horario_entrada_break']) && strtotime("{$data['permiso_hora_hasta']}")>=strtotime($scheduleDay['horario_salida_break']) ) $data['permiso_horas']-=$timeBreak;
			}
			
		}elseif($data['permiso_parametro']=='DIAS'){
			
			// RECORRER FECHAS PARA CONTABILIZAR LOS DÍAS
			for($i=strtotime($data['permiso_fecha_desde']);$i<=strtotime($data['permiso_fecha_hasta']);$i+=86400){
				// PARSE DE FECHA
				$auxDate=date('w',$i);
				// CONTAR NÚMEROS DE DIAS
				if($auxDate<1 || $auxDate>5) $data['permiso_weekend']+=1;
				else $data['permiso_semana']+=1;
			}
			
			// VALIDAR NIVEL ADMINISTRATIVO - NO SUMAR FINES DE SEMANA
			if($personal['puesto_modalidad']=='ADMINISTRATIVO'){
				// CALCULAR FINES DE SEMANA VALIDOS
				$data['weekend']=$data['permiso_weekend']-(2*intval(($data['permiso_semana']+$data['permiso_weekend'])/7));
				// CALCULAR LOS DIAS VALIDOS
				$data['permiso_dias']=($data['permiso_semana']+$data['permiso_weekend'])-$data['weekend'];
			}else{
				// PARSE FECHAS
				$date1=new \DateTime($data['permiso_fecha_desde']);
				$date2=new \DateTime($data['permiso_fecha_hasta']);
				// RESTAR FECHAS
				$diff=$date1->diff($date2);
				// OBTENER RESULTADO DE FECHAS
				$data['permiso_dias']=abs($diff->days)+1;
			}
			
		}
		
		// RETORNAR DATOS RE CONSULTA
		return $data;
	}
	
	/*
	 * VALIDAR FECHA
	 * - PERMISOS DÍAS DE EVENTOS
	 * - PERMISOS EN HORAS LABORABLES
	 * - CALCULAR LAS HORAS Y DÍAS QUE SERÁN UTILIZADAS 
	 */
	private function validateDate($personal,$data){
	    
		// FECHA MÍNIMA PARA SOLICITAR PERMISO
		$minDate=$this->addDays($this->getFecha(),$this->varLocal['MIN_DAYS_TO_REQUEST']);
		// FECHA MÁXIMA PARA SOLICITAR PERMISO
		$maxDate=$this->addDays($this->getFecha(),$this->varLocal['MAX_DAYS_TO_REQUEST']);
		
		// DATOS PARA PERMISOS
		$data=$this->getDataToBreak($personal,$data);
		
		// MINIMO MAXIMOS DE PERMISOS A SOLICITAR
		if($data['permiso_dias']>$this->varLocal['TOTAL_DAYS_MAX_REQUESTED']) $this->getJSON("Usted podrá solicitar un máximo de <b>{$this->varLocal['TOTAL_DAYS_MAX_REQUESTED']} días</b> de permisos con cargo a vacaciones!");
		
		// VALIDACIÓN DE TIEMPO MINIMO Y MAXIMO PARA SOLICITAR PERMISOS
		if(strtotime($data['permiso_fecha_desde']) < strtotime($minDate) && app\session::getKey()=='session.tthh') $this->getJSON($this->msgReplace($this->PARAMS_TO_REQUEST['MSG']['MIN_DAYS_TO_REQUEST'],$this->varLocal));
		if(strtotime($data['permiso_fecha_desde']) >= strtotime($maxDate)) $this->getJSON($this->msgReplace($this->PARAMS_TO_REQUEST['MSG']['MAX_DAYS_TO_REQUEST'],$this->varLocal));
		
		// RETORNAR DATOS RE CONSULTA
		return $data;
	}
	
	/*
	 * INGRESAR NUEVAS SOLICITUDES
	 */
	public function insertEntity(){
		// TIPO DE SESIÓN
		$sessionType=app\session::getKey();
		// VALIDAR MÓDULO DE PETICION
		if($sessionType=='session.system'){
			// OBTENER INFORMACION DEL PERSONAL
			$personal=$this->db->viewById($this->post['fk_personal_id'],'personal');
		}else{
			// OBTENER ID DE PERSONAL (SESSION)
			$personalId=app\session::get();
			// OBTENER INFORMACION DEL PERSONAL
			$personal=$this->db->viewById($personalId,'personal');
		}
		
		// VALIDAR EL HORARIO PARA EL PERMISO
		$this->post=$this->validateDate($personal,$this->post);
		
		// EMPEZAR TRANSACCIÓIN
		$this->db->begin();
		// GENERAR SENTENCIA DE INGRESO & EJECUTAR SENTENCIA SQL
		$json=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// INSERTAR DATOS
		$json['data']=$this->db->getLastEntity($this->entity);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * MODIFICAR REGISTRO DE PRESENTACIONES - CASAS ABIERTAS
	 */
	public function updateEntity(){
	    // VALIDAR SI EL PROYECTO SE EJECUTA EN SYSTEM O DEL LADO DE TTHH Y OBTENER ID DE PERSONAL (SESSION)
        $sessionType=$this->getModuleBySession();
		// $this->getJSON($sessionType);

		// Prohibiendo la anulación de permisos
		if($sessionType=='session.system'){
			if($this->post['permiso_estado']=='SOLICITUD ANULADA' && !in_array(802411,$this->getAccesRol())){
				$this->getJSON("No cuenta con permisos para anular los permisos solicitados! <br> Por favor comuníquese con la dirección de Talento Humano del CBSD");
			}
		}elseif( $sessionType=='session.tthh'){
	        if($this->post['permiso_estado']=='SOLICITUD ANULADA'){
				$this->getJSON("No cuenta con permisos para anular los permisos solicitados! <br> Por favor comuníquese con la dirección de Talento Humano del CBSD");
			}
	    }

	    $personalId=$this->post['fk_personal_id'];
	    
	    // OBTENER INFORMACION DEL PERSONALs
	    $personal=$this->db->viewById($personalId,'personal');
		
		// OBTENER DATOS DE FORMULARIO Y VALIDAR EL HORARIO PARA EL PERMISO
		if($this->post['permiso_estado']=='SOLICITUD GENERADA') $this->post=$this->validateDate($personal,$this->post);
		else $this->post=$this->getDataToBreak($personal,$this->post);
		
		// INGRESAR EL PERSONAL QUE REALIZA LA SOLICITUD
		$this->post['fk_usuario']=$this->getStaffIdBySession();
		
		// VERIFICAR EL ESTADO DE TRANSICIÓN
		if($this->post['permiso_estado']=='SOLICITUD AUTORIZADA'){
			
			$this->post['permiso_sumillado']=$this->getFecha('Y-m-d H:i:s');
			
		}elseif($this->post['permiso_estado']=='SOLICITUD REGISTRADA'){
			
			// PERMISO EN BASE DE DATOS PARA APROBAR UNA SOLICITUD VENCIDA
			if(!in_array(802411,$this->getAccesRol())) $this->getJSON("No cuenta con permisos para aprobar los permisos del personal!");
			
			// REGISTRAR DATOS DE APROBACION
			$this->post['permiso_aprobado']=$this->getFecha('Y-m-d H:i:s');
			// GENERAR SERIE DE APROBACIÓN
			if($this->post['permiso_serie']==0) $this->post['permiso_serie']=$this->db->getNextSerie($this->entity);
			
		}
		
		// EMPEZAR TRANSACCIÓIN
		$this->db->begin();
		// GENERAR SENTENCIA DE INGRESO & EJECUTAR SENTENCIA SQL
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	
	/*
	 * CONSULTAR PERMISO POR CÓDIGO
	 */
	private function requestByCode($code){
		// MENSAJE POR DEFECTO
		$json="Código de solicitud no registrado!";
		// CONSULTAR DISPONIBILIDAD EN BASE DE DATOS
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(permiso_codigo)=UPPER('{$code}')");
		// VALIDAR CODIGO DE SOLICITUD
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN
			if(in_array($row['permiso_estado'],['SOLICITUD AUTORIZADA'])){
				// VALIDAR PERMISO PARA APROBAR PERMISO
				if(!in_array(802411,$this->getAccesRol())) $this->getJSON("No cuenta con permisos para aprobar los permisos del personal!");
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['permiso_estado']='SOLICITUD REGISTRADA';
				// MODAL A DESPLEGAR
				$row['modal']='Personal_permisos';
				$row['tb']=$this->entity;
				// DATOS DEL PERFIL Y DATOS DEL JEFE INMEDIATO
				$row=array_merge($row,$this->db->viewById($row['fk_personal_id'],'personal'));
				// DATOS DE RETORNO
				return $row;
			} elseif(in_array($row['permiso_estado'],['SOLICITUD REGISTRADA'])){
				$this->getJSON("La solicitud <b>{$code}</b> ya ha sido aprobada por <b>{$row['usuario']}</b>!");
			} elseif(in_array($row['permiso_estado'],['SOLICITUD GENERADA'])){
				// VALIDAR PERMISO PARA APROBAR PERMISO
				if(!in_array(8024111,$this->getAccesRol())) $this->getJSON("No cuenta con permisos para aprobar los permisos del personal sin previa aprobación del jefe inmediato!");
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['permiso_estado']='SOLICITUD REGISTRADA';
				// MODAL A DESPLEGAR
				$row['modal']='Personal_permisos';
				$row['tb']=$this->entity;
				// DATOS DEL PERFIL Y DATOS DEL JEFE INMEDIATO
				$row=array_merge($row,$this->db->viewById($row['fk_personal_id'],'personal'));
				// DATOS DE RETORNO
				return $row;
			} else{
				$this->getJSON("Esta solicitud no procede para aprobación puesto que se encuentra como <b><b>{$row['permiso_estado']}</b></b> por <b><b>{$row['usuario']}</b></b>.");
			}
		}else{
			// BUSCAR POR CEDULA DE PERSONAL
			$str=$this->db->selectFromView("vw_basic_personal","WHERE UPPER(persona_doc_identidad)=UPPER('{$code}')");
			// VALIDAR CODIGO DE SOLICITUD
			if($this->db->numRows($str)>0){
				// OBTENER REGISTRO DE PERSONAL
				$row=$this->db->findOne($str);
				// MODELO DE PERMISO
				$info=$this->requestByAccount($row['personal_id'],$this->entity);
				// UNIR MODELOS
				$row=array_merge($row,$info['frmParent']);
				// MODAL A DESPLEGAR
				$row['modal']='Personal_permisos';
				$row['tb']='personal_permisos';
				$row['edit']=false;
				$row['permiso_estado']='SOLICITUD REGISTRADA';
				// DATOS DE RETORNO
				return $row;
			}
		}
		// RETORNAR CONSULTA POR DEFECTO
		$this->getJSON($json);
	}
	
	/*
	 * CONSULTAR PERSONAL PARA VERIFICAR DISPONIBILIDAD DE PERMISOS
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR FECHA - NUEVO REGISTRO
		elseif(isset($this->post['date'])) $json=$this->requestByDate($this->post['date']);
		// CONSULTAR POR CÓDIGO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTAR POR SESSION DE USUARIO
		elseif(isset($this->post['account']) && $this->post['account']=='tthh') $json=$this->requestByAccount(app\session::get(),$this->entity);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..............!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
	    // STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_permisos_solicitados","WHERE permiso_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
		    
			// DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// PARSE FECHAS
			$parseDates=$this->setParseRangeDates($row['permiso_desde'],$row['permiso_hasta'],$row['permiso_parametro']);
			
			// IMPRESION DE REGISTROS
			$row['permiso_desde_alt']=$parseDates['desde_alt'];
			$row['permiso_hasta_alt']=$parseDates['hasta_alt'];
			$row['permiso_fecha_alt']=$parseDates['fecha_alt'];
			$row['permiso_solicitud']=$this->setFormatDate($row['permiso_solicitud'],'complete');
			
			// PARSE DE CANTIDAD DE HORAS O DIAS
			if($row['permiso_parametro']=='HORAS'){
				$row['permiso_cantidad']=$this->setNumbreToTime($row['permiso_horas'],'time');
				$row['permiso_cantidad_correccion']=$this->setNumbreToTime($row['permiso_horas']*$row['permiso_factorcorreccion'],'time');
			}
			
			// VALIDAR SI EL PERSONAL ES OPERATIVO Y SI SE ENCUENTRA EN PELOTON
			$row=$this->signatureResponsible($row);
			
			// CARGAR DATOS DE PERMISO
			$pdfCtrl->loadSetting($row);
			
			// *** CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	
}