<?php namespace model\prevention;
use api as app;
use controller as ctrl;

class trainingModel extends ctrl\preventionController {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='training';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// MODELO CONSTRUCTOR
		parent::__construct();
		// CARGAR PARAMETROS DE MÓDULO
		$this->loadModuleData($this->entity);
	}
	
	/*
	 * LISTADO DE REGISTROS DEL AÑO
	 */
	public function getListApplications(){
		// VARIABLE TEMPORAL PARA FECHA YA AGENDADAS
		$info=$this->getListApplicationsParent($this->entity);
		// RETORNAR DATOS
		$this->getJSON(array('info'=>$info));
	}
	
	
	/*
	 * CONSULTAR CAPACITACIÓN POR ID
	 */
	private function requestById($id){
		// OBTENER DATOS DE PROYECTO
		$data=$this->db->viewById($id,$this->entity);
		// LISTAR PERSONAL RELACIONADO CON EL EVENTO
		$data['capacitorsList']=$this->db->findAll($this->db->selectFromView('vw_capacitadores',"WHERE fk_entidad='{$this->entity}' AND fk_entidad_id=$id"));
		// GALERIA RELACIONADA
		$data['gallery']=$this->db->findAll($this->db->selectFromView('vw_gallery',"WHERE fk_table='{$this->entity}' AND fk_id={$id}"));
		// ENTIDAD RELACIONADA
		$data['entity']=$this->db->viewById($data['fk_entidad_id'],'entidades');
		// LISTADO DE PERSONAS EN CAPACITACIÓN
		$data['people']=$this->db->findAll($this->db->selectFromView('vw_participantes_in_training',"WHERE fk_capacitacion_id=$id ORDER BY persona_apellidos,persona_nombres"));
		// VALIDAR ID DE USUARIO PARA RELACIÓN CON CAPACITACIONES
		$sessionPhp=app\session::decodeSession();
		if($sessionPhp['session']['type']=='system'){
			$sessionId=app\session::get();
			$user=$this->getProfile();
			if(!in_array($sessionId,$data['capacitors'])){
				if(!in_array($user['fk_perfil_id'],[1,5]) && !in_array(60601,$this->getAccesRol())) $this->getJSON("Este registro no se encuentra asociado a su perfil!");
			}
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR CAPACITACIÓN POR FECHAS
	 */
	private function requestByDate($date){
		// VALIDACIÓN DE FECHA
		$this->requestByDateAndEntity($date,$this->entity);
		
		// CONSULTAR SI HAY HORARIOS DISPONIBLES
		$strEntity=$this->db->selectFromView("vw_capacitaciones_ciudadanas","WHERE entidad IN ('training','stands') AND fecha='{$date}'::date ORDER BY horario");
		// NUMERO DE REGISTROS
		$count=$this->db->numRows($strEntity);
		// VALIDACIONES: NO HAY HORARIOS DISPONIBLES
		if($count>=count($this->times)) $this->getJSON($this->varGlobal['REQUEST_UNAVAILABLE_SCHEDULE']);
		
		// VALIDAR SI SE HAN DECLARADO EXCEPCIONES PARA REGISTROS
		if(isset($this->localConfig[$this->entity]['exceptions'])){
			// RECORRER EXCEPCIONES DE ENTIDADES
			foreach($this->localConfig[$this->entity]['exceptions'] as $entity=>$val){
				// STRING PARA CONSULTAR SI EXISTEN SOLICITUDES ACTIVAS :: CASAS ABIERTAS
				$str=$this->db->selectFromView("vw_capacitaciones_ciudadanas","WHERE entidad='{$entity}' AND fecha='{$date}' AND horario {$val} ORDER BY horario");
				// VALIDACIONES SI EXISTEN SOLICITUDES
				if($this->db->numRows($str)>0) $this->getJSON($this->varGlobal['REQUEST_UNAVAILABLE_SCHEDULE']);
			}
		}
		
		// TEMAS DISPONIBLES
		$topis=$this->db->findAll($this->db->selectFromView('temario',"WHERE tema_estado='ACTIVO'"));
		// MODELO DE DATOS
		$json=array(
			'topics'=>$topis,
			'date'=>array()
		);
		// CALCULAR FECHA PARA CONFIRMACION
		$date2confirm=$this->addWorkDays($this->getFecha(),$this->localConfig[$this->entity]['days_expire']);
		// LISTADO DISPONIBLE DE CAPACITACIONES EN UNA FECHA
		foreach($this->times as $val){
			// MODELO DE SOLICITUD
			$json['date']["{$date} {$val}"]=array(
				'capacitacion_fecha'=>"{$date} {$val}",
				'capacitacion_confirmacion'=>$date2confirm,
				'close'=>$this->addHoursTodate("{$date} {$val}",$this->localConfig[$this->entity]['time'])
			);
		}
		// DESVINCULAR HORARIO DE SOLICITUDES YA REGISTRADAS
		if($count>0){ foreach($this->db->findAll($strEntity) as $val){ unset($json['date']["{$val['fecha']} {$val['horario']}"]); } }
	
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}
	
	/*
	 * CONSULTAR REGISTRO POR CÓDIGO
	 */
	private function requestByCode($code){
		// GENERAR STRING DE CONSULTA DE SOLICITUD POR CODIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(capacitacion_codigo)=UPPER('{$code}')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR QUE SE HAYA REALIZADO EL INGRESO DE PARTICIPANTES
			if($row['people_in_training']<$this->varGlobal['TRY_MIN_PEOPLE']) $this->getJSON($this->varGlobal['TRY_MIN_PEOPLE_MSG']);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO INCIADO
			if(in_array($row['capacitacion_estado'],$this->trainingProcess['init'])){
				// VERIFICAR SI LA CAPACITACIÓN HA VENCIDO
				if(strtotime($this->getFecha()) > strtotime($row['capacitacion_confirmacion'])){
					// PERMISO EN BASE DE DATOS PARA APROBAR UNA SOLICITUD VENCIDA
					if(!in_array(6065,$this->getAccesRol())) $this->getJSON($this->varGlobal['REQUEST_TIMEOUT']);
					// VERIFICAR QUE LA FECHA DE CAPACITACIÓN NO HAYA PASADO
					if(strtotime($this->getFecha()) > strtotime($row['capacitacion_fecha'])) $this->getJSON($this->varGlobal['REQUEST_UNAVAILABLE_DATE']);
					// STRING PARA VERIFICAR QUE NO EXISTA OTRO REGISTRO EN ESA FECHA
					$str=$this->db->selectFromView($this->entity,"WHERE capacitacion_fecha::text='{$row['capacitacion_fecha']}' AND capacitacion_estado NOT IN ('PENDIENTE','ANULADA')");
					// VALIDAR EL NUMERO DE REGISTROS
					if($this->db->numRows($str)>0) $this->getJSON($this->varGlobal['REQUEST_UNAVAILABLE_ITEM']);
				}
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['capacitacion_estado']='CONFIRMADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
				$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO EN PROCESO
			}elseif(in_array($row['capacitacion_estado'],$this->trainingProcess['process'])){
				// VALIDAR CON RESPECTO A LOS TIEMPOS. MENOR QUE FECHA ACTUAL PARA ASIGNAR RESPONSABLES
				if($row['capacitacion_estado']=='CONFIRMADO' || strtotime($this->getFecha('dateTime'))<strtotime($row['capacitacion_fecha'])){
					// RELACION PARA CAPACITADOR
					$row['entityId']=$row['capacitacion_id'];
					// MODAL A DESPLEGAR
					$row['modal']='Capacitadores';
					$row['tb']='capacitadores';
					$row['edit']=false;
				}else{
					// MODAL A DESPLEGAR
					$row['modal']='Training';
					$row['tb']='training';
				}
			// CAPACITACION DESPACHADA - EVALUAR LA ASISTENCIA DE PERSONAL
			}elseif(in_array($row['capacitacion_estado'],$this->trainingProcess['dispatched'])){
				// MODAL A DESPLEGAR
				$row['modal']='Participantes';
				$row['tb']='Participantes';
			}else{
				$this->getJSON($this->varGlobal['REQUEST_ENTERED']);
			}
			// DEFINIR NOMBRE DE ENTIDAD
			$row['entity']=$this->entity;
			// RETORNAR CONSULTA
			return $row;
		}else{
			$this->getJSON($this->varGlobal['CODE_NOFOUND_MSG']);
		}
	}
	
	/*
	 * CONSULTA ESTADO DE FECHA PARA PREVIA SOLICITUD
	 */
	public function requestTraining(){
		// CONSULTAR POR ID DE REGISTRO
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR CALENDARIO DE DISPONIBILIDAD
		elseif(isset($this->post['date'])) $json=$this->requestByDate($this->post['date']);
		// CONSULTAR POR CÓDIGO DE REGISTRO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("OK",true,$json));
	}
	
	
	/*
	 * CONSULTAR TEMAS DE CAPACITACIONES
	 */
	public function requestTopics(){
		// CONSULTAR POR ID DE REGISTRO
		if(isset($this->post['id'])) $json=$this->db->findById($this->post['id'],'temario');
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("OK",true,$json));
	}
	
	
	/*
	 * PREPARA DATOS PARA EL ENVÍO DE CORREO ELECTRÓNICO
	 */
	private function notifyByEmail($modelId,$oldData=array()){
		// DATOS DE DEPARTAMENTO - JEFATURA - MÓDULO
		$varDirection=$this->getConfParams('PREVENTION');
		// DATOS DE CAPACITACIÓN
		$model=$this->db->viewById($modelId,$this->entity);
		// PARA PRESENTACIÓN DE E-MAIL MULTI PROCESO
		$model['module']='training';
		$model['proceso_nombre']="CAPACITACIÓN CIUDADANA";
		$model['codigo']=$model['capacitacion_codigo'];
		$model['solicitud']="{$model['proceso_nombre']} #{$model['codigo']}";
		$model['proceso']="{$model['proceso_nombre']} - {$model['tema_nombre']}";
		// DATOS AUXILIARES
		$model['tema']=$model['tema_nombre'];
		$model['fecha']=$this->setFormatDate($model['capacitacion_fecha'],'full');
		$model['descripcion']=$model['capacitacion_observacion'];
		$model['responsable']=$model['usuario'];
		$model['registro']=$this->setFormatDate($model['capacitacion_registro'],'full');
		// LISTA DE DESTINATARIOS
		$recipientsList=array();
		// VALIDAR ESTADOS INDIVIDUALES DEL REGISTRO
		if($model['capacitacion_estado']=='PENDIENTE'){
			$template='trainingRequest';
			$subject="SOLICITUD DE {$model['solicitud']} - REGISTRADA";
		}elseif($model['capacitacion_estado']=='CANCELADA'){
			$template='processStatus';
			$subject="{$model['solicitud']} - CANCELADA";
			// DATOS AUXILIARES
			$model['proceso_estado']="{$model['proceso_nombre']} CANCELADA";
			$model['estado']="CANCELADA";
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['CONFIRMED_RECIPIENTS']);
		}elseif($model['capacitacion_estado']=='CONFIRMADO'){
			$template='processStatus';
			$subject="{$model['solicitud']} - CONFIRMADA";
			// DATOS AUXILIARES
			$model['proceso_estado']="{$model['proceso_nombre']} CONFIRMADA";
			$model['estado']="CONFIRMADA";
			$model['descripcion']="confirmado con especie valorada #{$model['capacitacion_solicitud']}";
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['CONFIRMED_RECIPIENTS']);
		}elseif($model['capacitacion_estado']=='REAGENDADA'){
			$template='rescheduleProcess';
			$subject="{$model['solicitud']} - REAGENDADA PARA {$model['capacitacion_fecha']}";
			// DATOS AUXILIARES
			$model['proceso_estado']="{$model['proceso_nombre']} REAGENDADA";
			$model['fecha']=$this->setFormatDate($oldData['capacitacion_fecha'],'full');
			$model['fecha_new']=$this->setFormatDate($model['capacitacion_fecha'],'full');
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['RESCHEDULE_RECIPIENTS']);
		}elseif($model['capacitacion_estado']=='DESPACHADA'){
			$template='processStatus';
			$subject="{$model['solicitud']} - DESPACHADA";
			// DATOS AUXILIARES
			$model['proceso_estado']="{$model['proceso_nombre']} DESPACHADA";
			$model['estado']="DESPACHADA";
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
		}
		// INICIALIZAR SERVIDOR DE CORREOS
		$mailCtrl=new ctrl\mailController($template);
		// ADJUNTAR DATOS DE DEPARTAMENTO
		$mailCtrl->mergeData($varDirection);
		// ADJUNTAR DATOS DE REGISTRO
		$mailCtrl->mergeData($model);
		// ADJUNTAR DATOS DE ENTIDAD RELACIONADA
		$mailCtrl->mergeData($this->db->findById($model['fk_entidad_id'],'entidades'));
		// LISTA DE DESTINATARIOS DEL CORREO
		$mailCtrl->destinatariosList=$recipientsList;
		// ENVIAR CORREO
		$mailCtrl->asyncMail($subject,$model['persona_correo']);
	}
	
	/*
	 * GUARDAR CAPACITACIÓN
	 */
	public function insertTraining(){
		
		// VALIDAR FECHA PARA GENERAR REGISTRO
		$this->requestByDateAndEntity($this->post['date']['capacitacion_fecha'],$this->entity);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// IDE DE SESIÓN DE ENTIDAD
		$sessionId=app\session::get();
		// REGISTRAR COORDINADOR DEL EVENTO
		$person=$this->requestPerson($this->post['coordinator']);
		// GENERAR MODELO PARA INGRESO
		$model=array_merge($this->post['training'],$this->post['date']);
		// RELACIONAR REGISTRO CON COORDINADOR
		$model['fk_persona_id']=$person['persona_id'];
		// INGRESAR REGISTRO CON ENTIDAD
		$model['fk_entidad_id']=$sessionId;
		// ID DE TOPIC
		$model['fk_tema_id']=$this->post['topic']['tema_id'];
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
		$model['jtp_solicitud']=$this->getResponsibleByLeadership('PUESTO_PREVENCION_ID','puesto_id');
		
		// INSERTAR CAPACITACIÓN
		$json=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
		
		// ID DEL MODELO
		$trainingId=$this->db->getLastID($this->entity);
		
		// VALIDAR Y NOTIFICAR MEDIANTE CORREO ELECTRÓNICO
		if($json['estado']) $this->notifyByEmail($trainingId);
		
		// SET MAPA DE COORDENADAS
		$this->setMapCoordinates($this->entity,$trainingId,$this->post);
		// SET GEOJSON
		$this->setGeoJSON($this->entity,$trainingId,$this->post['coordenada_marks']);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * EDICIÓN DE REGISTRO DE CAPACITACIÓN
	 */
	public function updateTraining(){
		
		// VALIDAR SI SE VA A ELIMINAR LOS PARTICIPANTES
		if(isset($this->post['opt']) && $this->post['opt']=='truncate'){
			// ELIMINAR PARTICIPANTES
			$this->getJSON($this->db->executeTested($this->db->getSQLDelete('training_has_people',"WHERE fk_capacitacion_id={$this->post['trainingId']}")));
		}
		
		$noTrainers="Antes de despachar una capacitación debe asignar el personal para impartir la capacitación!";
		
		// INICIAR TRASACCIÓN
		$this->db->begin();
		
		// MODELO PARA DATOS ORIGINALES
		$oldData=$this->db->viewById($this->post['capacitacion_id'],$this->entity);
		
		// VALIDAR POSIBLES ESTADOS DE REGISTRO
		if($this->post['capacitacion_estado']=='CONFIRMADO'){
			// VALIDAR NÚMERO DE SOLICITUD
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $this->post['capacitacion_solicitud']=$this->testRequestNumber($this->entity,$this->post['capacitacion_solicitud'],$this->post['capacitacion_id']);
		}elseif($this->post['capacitacion_estado']=='REAGENDADA'){
			// OBTENER DATOS ANTES DE LA MODIFICACIÓN
		}elseif($this->post['capacitacion_estado']=='DESPACHADA'){
			// SENTENCIA PARA CONSULTAR NÚMERO DE CAPACITADORES ANTES DE DESPACHAR
			$strTrainer=$this->db->selectFromView('vw_capacitadores',"WHERE fk_entidad='{$this->entity}' AND fk_entidad_id={$this->post['capacitacion_id']}");
			// VALIDAR - DESPACHADA SOLO SI SE HA DESIGNADO LOS CAPACITADORES
			if($this->db->numRows($strTrainer)<1) $this->getJSON($this->db->closeTransaction($this->getJSON($noTrainers)));
			// GENERAR NÚMERO DE SERIE DE CAPACITACIÓN
			if($this->post['capacitacion_serie']<1) $this->post['capacitacion_serie']=$this->db->getNextSerie($this->entity);
		}
		
		// ACTUALIZAR DATOS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// VALIDAR Y NOTIFICAR MEDIANTE CORREO ELECTRÓNICO
		if($sql['estado']) $this->notifyByEmail($this->post['capacitacion_id'],$oldData);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * REPORTE REGISTROS INCOMPLETOS
	 */
	private function testCustomReport($pdfCtrl,$user,$row,$type){
		$msg="";
		// VERIFICAR EL TIPO DE VALIDACIÓN
		if($type=='request'){
			if($row['people_in_training']<1) $msg.="<li>Registrar el personal que asistirá a la capacitación.</li>";
			if($row['people_in_training']>0 && $row['people_in_training']<$this->varGlobal['TRY_MIN_PEOPLE']) $msg.="<li>Registrar al menos {$this->varGlobal['TRY_MIN_PEOPLE']} participantes.</li>";
		}elseif($type=='certificateGroup'){
			if($row['capacitacion_estado']!='EVALUADA') $msg.="<li>El certificado colectivo de participación no se puede emitir porque aun no se ha evaluado a los capacitadores.</li>";
			if($row['gallery_entity']<1) $msg.="<li>El certificado colectivo de participación no se puede emitir porque aun no se ha subido al menos 1 fuente de verificación de la capacitación (Imagen que demuestre la asistencia del grupo).</li>";
		}
		// VALIDAR MENSAJE
		if($msg=="") return true;
		// RETORNAR CONSULTA
		return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE - SOLICITUD CBSD
	 */
	public function printDetail($pdfCtrl,$config){
	    // STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE capacitacion_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testCustomReport($pdfCtrl,$row['coordinador'],$row,'request')) return '';
			
			// PARSE DE FECHAS
			$row['capacitacion_fecha']=$this->setFormatDate($row['capacitacion_fecha'],'full');
			$row['capacitacion_confirmacion']=$this->setFormatDate($row['capacitacion_confirmacion'],'complete');
			// LISTADO DE PERSONAS EN CAPACITACIÓN
			$str=$this->db->selectFromView('vw_participantes_in_training',"WHERE fk_capacitacion_id={$this->get['id']} ORDER BY persona_apellidos,persona_nombres");
			$row['tbPersonal']=($this->db->numRows($str)>0)?"":"<tr><td colspan=4 class=text-center>No registrado</td></tr>";
			foreach($this->db->findAll($str) as $key=>$person){
				// LISTADO DE PERSONAL EN CAPACITACION
				$auxDetail=$this->varGlobal['TRAINING_PARTICIPANTS_LIST_DETAIL'];
				$person['index']=$key+1;
				foreach($person as $k=>$v){
					$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);
				}	$row['tbPersonal'].=$auxDetail;
			}
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['capacitacion_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE capacitacion_id=$id");
		$get=$this->requestGet();
		// VALIDAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			$row=$this->db->findOne($str);
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testCustomReport($pdfCtrl,$row['coordinador'],$row,'certificateGroup')) return '';
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			$row['capacitacion_fecha']=$this->setFormatDate($row['capacitacion_fecha'],'full');
			// CALIDAR TIPO DE REPORTE - SINDIVIDUAL O LISTADO
			if(isset($get['participanteId'])){
				$participantData=$this->db->findOne($this->db->selectFromView("vw_participantes_in_training","WHERE fk_capacitacion_id=$id AND fk_participante_id={$get['participanteId']}"));
				$participantData['refrendacion_fecha_full']=$this->setFormatDate($participantData['refrendacion_fecha'],'complete');
				$pdfCtrl->loadSetting($participantData);
			}else{
				// LISTADO DE PERSONAS EN CAPACITACIÓN
				$str=$this->db->selectFromView('vw_participantes_in_training',"WHERE fk_capacitacion_id=$id AND estado IN ('ASISTIO','REFRENDADO') ORDER BY persona_apellidos,persona_nombres");
				$row['participantes']="";
				foreach($this->db->findAll($str) as $person){
					// LISTADO DE PERSONAL EN CAPACITACION
					$auxDetail="<li style='padding:5px 0px;text-transform:uppercase;'>{$person['persona_apellidos']} {$person['persona_nombres']} - ({$person['persona_doc_identidad']})</li>";
					$row['participantes'].=$auxDetail;
				}
			}
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['capacitacion_codigo']);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}