<?php namespace model\prevention;
use api as app;
use controller as ctrl;

class visitModel extends ctrl\preventionController {

	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='visitas';
	
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
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestById($id){
		// OBTENER DATOS DE PROYECTO
		$json=$this->db->viewById($id,$this->entity);
		// LISTAR PERSONAL RELACIONADO CON EL EVENTO
		$json['capacitorsList']=$this->db->findAll($this->db->selectFromView('vw_capacitadores',"WHERE fk_entidad='{$this->entity}' AND fk_entidad_id=$id"));
		// GALERIA RELACIONADA
		$json['gallery']=$this->db->findAll($this->db->selectFromView('vw_gallery',"WHERE fk_table='{$this->entity}' AND fk_id={$id}"));
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}
	
	/*
	 * CONSULTAR STAND POR FECHAS
	 */
	private function requestByDate($date){
		// VALIDACIÓN DE FECHA
		$this->requestByDateAndEntity($date,$this->entity);
		
		// MODELO DE DATOS
		$json=array(
			'date'=>array()
		);
		
		// CALCULAR FECHA PARA CONFIRMACION
		$date2confirm=$this->addWorkDays($this->getFecha(),$this->localConfig[$this->entity]['days_expire']);
		// LISTADO DISPONIBLE DE CAPACITACIONES EN UNA FECHA
		foreach($this->times as $val){
			// GENERAR MODELO DE HORARIOS DISPONIBLES
			$json['date']["{$date} {$val}"]=array(
				'visita_fecha'=>"{$date} {$val}",
				'visita_confirmacion'=>$date2confirm
			);
		}
		
		// STRING PARA CONTAR NÚMERO DE SOLICITUDES REGISTRADAS
		$str=$this->db->selectFromView("vw_capacitaciones_ciudadanas","WHERE entidad='{$this->entity}' AND fecha='{$date}'");
		// OBETENER NÚMERO DE SOLICITUDES PARA ESAS FECHAS
		$count=$this->db->numRows($str);
		// VALIDACIONES: NO HAY HORARIOS DISPONIBLES
		if($count>=count($this->times)) $this->getJSON($this->varGlobal['REQUEST_UNAVAILABLE_SCHEDULE']);
		elseif($count>0){
			// DESVINCULAR HORARIO DE SOLICITUDES YA REGISTRADAS
			foreach($this->db->findAll($str) as $val){unset($json['date']["{$val['fecha']} {$val['horario']}"]);}
		}
		
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}
	
	/*
	 * CONSULTAR REGISTRO POR CÓDIGO
	 */
	private function requestByCode($code){
		// GENERAR STRING DE CONSULTA DE SOLICITUD POR CODIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(visita_codigo)=UPPER('$code')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO INCIADO
			if(in_array($row['visita_estado'],$this->trainingProcess['init'])){
				// VERIFICAR SI LA CAPACITACIÓN HA VENCIDO
				if(strtotime($this->getFecha()) > strtotime($row['visita_confirmacion'])){
					// PERMISO EN BASE DE DATOS PARA APROBAR UNA SOLICITUD VENCIDA
					if(!in_array(6106,$this->getAccesRol())) $this->getJSON($this->varGlobal['REQUEST_TIMEOUT']);
					// VERIFICAR QUE LA FECHA DE CAPACITACIÓN NO HAYA PASADO
					if(strtotime($this->getFecha()) > strtotime($row['visita_fecha'])) $this->getJSON($this->varGlobal['REQUEST_UNAVAILABLE_DATE']);
					// STRING PARA VERIFICAR QUE NO EXISTA OTRO REGISTRO EN ESA FECHA
					$str=$this->db->selectFromView($this->entity,"WHERE visita_fecha::text='{$row['visita_fecha']}' AND visita_estado NOT IN ('PENDIENTE','ANULADA')");
					// VALIDAR EL NUMERO DE REGISTROS
					if($this->db->numRows($str)>0) $this->getJSON($this->varGlobal['REQUEST_UNAVAILABLE_ITEM']);
				}
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['visita_estado']='CONFIRMADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
				$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO EN PROCESO 
			}elseif(in_array($row['visita_estado'],$this->trainingProcess['process'])){
				// VALIDAR CON RESPECTO A LOS TIEMPOS. MENOR QUE FECHA ACTUAL PARA ASIGNAR RESPONSABLES
				if($row['visita_estado']=='CONFIRMADO' || strtotime($this->getFecha('dateTime'))<strtotime($row['visita_fecha'])){
					// RELACION PARA CAPACITADOR
					$row['entityId']=$row['visita_id'];
					// MODAL A DESPLEGAR
					$row['modal']='Capacitadores';
					$row['tb']='capacitadores';
					$row['edit']=false;
				}else{
					// MODAL A DESPLEGAR
					$row['modal']='Visitas';
					$row['tb']='visitas';
				}
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
	 * CONSULTAR PRESENTACIONES
	 */
	public function requestVisit(){
		// CONSULTAR POR ID DE REGISTRO
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR FECHA
		elseif(isset($this->post['date'])) $json=$this->requestByDate($this->post['date']);
		// CONSULTAR POR CÓDIGO DE REGISTRO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("OK",true,$json));
	}
	
	
	/*
	 * INGRESAR NUEVAS PRESENTACIONES - VISITAS
	 */
	public function insertVisit(){
		
		// VALIDAR FECHA PARA GENERAR REGISTRO
		$this->requestByDateAndEntity($this->post['date']['visita_fecha'],$this->entity);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// IDE DE SESIÓN DE ENTIDAD
		$sessionId=app\session::get();
		// REGISTRAR COORDINADOR DEL EVENTO
		$person=$this->requestPerson($this->post['coordinator']);
		// GENERAR MODELO PARA INGRESO
		$model=array_merge($this->post['visit'],$this->post['date']);
		// RELACIONAR REGISTRO CON COORDINADOR
		$model['fk_persona_id']=$person['persona_id'];
		// INGRESAR REGISTRO CON ENTIDAD
		$model['fk_entidad_id']=$sessionId;
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
		$model['jtp_solicitud']=$this->getResponsibleByLeadership('PUESTO_PREVENCION_ID','puesto_id');
		
		// INSERTAR STAND
		$json=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
		
		// ID DEL MODELO
		$modelId=$this->db->getLastID($this->entity);
		
		// VALIDAR Y NOTIFICAR MEDIANTE CORREO ELECTRÓNICO
		if($json['estado']) $this->notifyByEmail($modelId);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * MODIFICAR REGISTRO DE PRESENTACIONES - CASAS ABIERTAS
	 */
	public function updateVisit(){
		// DATOS DE INFORMACION
		$post=$this->requestPost();
		$noTrainers="Antes de despachar una visita debe asignar el personal que acudió a dicho evento!";
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// MODELO PARA DATOS ORIGINALES
		$oldData=$this->db->findById($post['visita_id'],$this->entity);
		// VALIDAR POSIBLES ESTADOS DE REGISTRO
		if($post['visita_estado']=='CONFIRMADO' && $oldData['visita_estado']=='PENDIENTE'){
			// VALIDAR NÚMERO DE SOLICITUD
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $post['visita_solicitud']=$this->testRequestNumber($this->entity,$post['visita_solicitud'],$post['visita_id']);
		}elseif($post['visita_estado']=='REAGENDADA'){
			// NOTIFICAR REAGENDAMIENTO
		}elseif($post['visita_estado']=='DESPACHADA'){
			// SENTENCIA PARA CONSULTAR NÚMERO DE CAPACITADORES ANTES DE DESPACHAR
			$strTrainer=$this->db->selectFromView('vw_capacitadores',"WHERE fk_entidad='{$this->entity}' AND fk_entidad_id={$post['visita_id']}");
			// VALIDAR - DESPACHADA SOLO SI SE HA DESIGNADO LOS CAPACITADORES
			if($this->db->numRows($strTrainer)<1) $this->getJSON($this->db->closeTransaction($this->getJSON($noTrainers)));
			// GENERAR NÚMERO DE SERIE DE STAND
			if($post['visita_serie']<1) $post['visita_serie']=$this->db->getNextSerie($this->entity);
		}
		// // ACTUALIZAR DATOS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
		// VALIDAR Y NOTIFICAR MEDIANTE CORREO ELECTRÓNICO
		if($sql['estado']) $this->notifyByEmail($post['visita_id'],$oldData);
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
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
		$model['module']='visit';
		$model['proceso_nombre']="VISITA";
		$model['codigo']=$model['visita_codigo'];
		$model['solicitud']="{$model['proceso_nombre']} #{$model['codigo']}";
		$model['proceso']="{$model['proceso_nombre']}";
		// DATOS AUXILIARES
		$model['tema']="Recorrido por las instalaciones del Cuerpo de Bomberos";
		$model['fecha']=$this->setFormatDate($model['visita_fecha'],'full');
		$model['descripcion']=$model['visita_observacion'];
		$model['responsable']=$model['usuario'];
		$model['registro']=$this->setFormatDate($model['visita_registro'],'full');
		// LISTA DE DESTINATARIOS
		$recipientsList=array();
		// VALIDAR ESTADOS INDIVIDUALES DEL REGISTRO
		if($model['visita_estado']=='PENDIENTE'){
			$template='visitRequest';
			$subject="SOLICITUD DE {$model['solicitud']} - REGISTRADA";
		}elseif($model['visita_estado']=='CANCELADA'){
			$template='processStatus';
			$subject="{$model['solicitud']} - CANCELADA";
			// DATOS AUXILIARES
			$model['proceso_estado']="SOLICITUD CANCELADA";
			$model['estado']="CANCELADA";
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['CONFIRMED_RECIPIENTS']);
		}elseif($model['visita_estado']=='CONFIRMADO'){
			$template='processStatus';
			$subject="{$model['solicitud']} - CONFIRMADA";
			// DATOS AUXILIARES
			$model['proceso_estado']="SOLICITUD CONFIRMADA";
			$model['estado']="CONFIRMADA";
			$model['descripcion']="confirmado con especie valorada #{$model['visita_solicitud']}";
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['CONFIRMED_RECIPIENTS']);
		}elseif($model['visita_estado']=='REAGENDADA'){
			$template='rescheduleProcess';
			$subject="{$model['solicitud']} - REAGENDADA PARA {$model['visita_fecha']}";
			// DATOS AUXILIARES
			$model['proceso_estado']="SOLICITUD REAGENDADA";
			$model['fecha']=$this->setFormatDate($oldData['visita_fecha'],'full');
			$model['fecha_new']=$this->setFormatDate($model['visita_fecha'],'full');
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['RESCHEDULE_RECIPIENTS']);
		}elseif($model['visita_estado']=='DESPACHADA'){
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
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		extract($this->requestGet());
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE visita_id=$id");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			$row=$this->db->findOne($str);
			$row['visita_fecha']=$this->setFormatDate($row['visita_fecha'],'full');
			$row['visita_confirmacion']=$this->setFormatDate($row['visita_confirmacion'],'complete');
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['visita_codigo']);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}