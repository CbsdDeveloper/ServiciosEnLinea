<?php namespace model\prevention;
use api as app;
use controller as ctrl;
use model as mdl;

class vbpModel extends mdl\personModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='vbp';
	protected $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'prevention')['vbp'];
	}
	
	
	/*
	 * INSERTAR REGISTRO PARA VALIDACIÓN
	 */
	protected function insertProject(){
		// IDE DE SESIÓN DE ENTIDAD
		$sessionId=app\session::get();
		// INGRESAR REGISTRO CON ENTIDAD
		$this->post['fk_entidad_id']=$sessionId;
		// VALIDAR DATOS DE FACTURACIÓN
		$this->post['facturacion_id']=$this->post['fk_entidad_id'];
		// INGRESO DE ESTADO DE VALIDACIÓN
		$this->post['vbp_estado']='VALIDACION';
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		return $this->db->getLastID($this->entity);
	}
	
	/*
	 * INSERTAR PROFESIONALES
	 */
	protected function insertProfessionals(){
		// MENSAJE DE INICIO
		$sql=$this->setJSON("No se ha registrado ningún profesional");
		// RECORRER LISTADO DE PROFESIONALES
		foreach($this->post['model'] as $v){
			// VALIDAR PERFIL ACADÉMICO DE PROFESIONAL
			$v=$this->testAcademicInformation($v);
			// REGISTRAR RESPONSABLE
			$person=$this->requestPerson($v['person']);
			// CONSULTAR PERFIL ACADÉMICO
			$sql=$this->requestAcademicTraining($v,$person['persona_id']);
			// OBTENER ID DE REGISTRO DE TITULO
			$titulo=$this->db->findOne($this->db->selectFromView('formacionacademica',"WHERE UPPER(formacion_senescyt)=UPPER('{$v['formacion_senescyt']}')"));
			// GENERAR MODELO PARA REGISTRAR PROFESIONAL
			$model=array(
				'vbp_obervacion'=>"REGISTRO DE {$v['professional']}",
				'vbp_id'=>$this->post['vbp_id'],
				$v['fk']=>$titulo['formacion_id']
			);
			// MODELO DE
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,$this->entity));
		}
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * CONSULTAR INSPECTORES ASIGNADOS
	 */
	protected function getInspectorsByCode($processId,$process){
		// RELACION DE PROCESO
		$relation=$this->config['modelRelations'][$process];
		// MODELO DE DATOS
		$data=$this->db->viewById($processId,$process);
		// VALIDAR TRANSICIÓN DE ETADOS
		if($data[$relation['status']]=='INGRESADO') $data[$relation['status']]='ASIGNADO';
		// OBJETO PARA PERSONAL SELECCIONADO
		$data['tb']=$process;
		$data['asignInspector']=true;
		$data['selected']=array();
		// ID DE USUARIOS COMO INSPECTORES
		$inspectorsList=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE fk_perfil_id in (5,6) AND usuario_estado='ACTIVO'");
		// LISTA DE INSPECTORES
		$data['list']=$this->db->findAll($this->db->selectFromView('vw_personal',"WHERE fk_persona_id IN ({$inspectorsList}) AND ppersonal_estado='EN FUNCIONES'"));
		// INSPECTORES YA INGRESADOS
		foreach($this->db->findAll($this->db->selectFromView($relation['fk'],"WHERE {$relation['fk_id']}={$processId}")) AS $v){
			// VALIDAR SI YA SE HA REGISTRADO EL INSPECTOR
			if(!in_array($v['fk_personal_id'],$data['selected'])) $data['selected'][]=$v['fk_personal_id'];
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * LISTADO DE INSPECTORES
	 */
	protected function getInspectorsById($processId,$process){
		// RELACION DE PROCESO
		$relation=$this->config['modelRelations'][$process];
		// GENERAR STRING DE CONSULTA
		$strInspectors=$this->db->selectFromView($this->db->setCustomTable($relation['fk'],'fk_personal_id'),"WHERE {$relation['fk_id']}={$processId}");
		// RETORNAR DATOS
		return $this->db->findAll($this->db->selectFromView('vw_personal',"WHERE ppersonal_id IN ({$strInspectors})"));
	}
	
	/*
	 * CONSULTAR RESPONSABLES DE POF POR ID
	 */
	private function requestProfesionalsByProject(){
		// VALIDAR SI SE HA ENVIADO EL ID DEL PROYECTO
		if(!isset($this->post['projectId'])){
			// MODELO DE PROFESIONALES
			$json['professionals']=array();
			// GENERAR MODELO DE PROFESIONALES
			foreach($this->config['professionals'] as $k=>$v){
				// MODELO POR DEFECTO DE RESPONSABLE
				$agent=$this->config['professionalModel'];
				// INGRESAR PROFESIONAL A MODELO
				$json['professionals'][$k]=array_merge($v,$agent);
			}
		}else{
			// DATOS DE PROYECTO
			$json=$this->db->viewById($this->post['projectId'],$this->entity);
			// MODELO DE PROFESIONALES
			$json['professionals']=array();
			// GENERAR MODELO DE PROFESIONALES
			foreach($this->config['professionals'] as $k=>$v){
				// MODELO POR DEFECTO DE RESPONSABLE
				$agent=$this->config['professionalModel'];
				// BUSCAR REPONSABLE SI HA SIDO SETTEADO
				if(isset($json[$v['fk']]) && $json[$v['fk']]>0){
					// DATOS DE PROFESIONAL
					$agent=$this->db->findById($json[$v['fk']],'formacionacademica');
					// CONSULTAR EL PERFIL ACADÉMICO
					$agent['person']=$this->db->findById($agent['fk_persona_id'],'personas');
				}
				// ADJUNTAR ID DE REGISTRO DE OCASIONAL
				$agent['vbp_id']=$this->post['projectId'];
				// INGRESAR PROFESIONAL A MODELO
				$json['professionals'][$k]=array_merge($v,$agent);
			}
		}
		// RETORNAR DATOS
		return $json;
	}
	
	/*
	 * CONSULTAR PROYECTO POR CODIGO
	 */
	private function requestByCode($code){
		// STRING DE CONSULTA POR CÓDIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(vbp_codigo)=UPPER('{$code}')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO INCIADO
			if(in_array($row['vbp_estado'],['VALIDACION'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6038,$this->getAccesRol())) $this->getJSON($this->varGlobal['BAD_INPUT_REQUEST_ROL']."<br>Esta solicitud debe ser aprobada por un responsable de la Unidad de Prevención.");
				// INGRESAR DATOS DE VBP
				$row['vbp_estado']='APROBADO';
				$row['vbp_observacion']='APROBACIÓN POR VALIDACIÓN PREVIA';
				// MODAL A DESPLEGAR
				$row['modal']='ValidacionVbp';
				$row['tb']=$this->entity;
			}elseif(in_array($row['vbp_estado'],['PENDIENTE'])){
				// VALIDAR QUE SE REGISTREN LOS PROFESIONALES RESPONSABLES
				// if(intval($row['responsable_mt_rpmci'])<1 || intval($row['proyectista_id'])<1){
				if(intval($row['responsable_mt_rpmci'])<1) $this->getJSON("Para realizar el ingreso de esta solicitud debe registrar los datos de los profesionales responsables del proyecto!");
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6032,$this->getAccesRol())) $this->getJSON($this->varGlobal['BAD_INPUT_REQUEST_ROL']);
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['vbp_estado']='INGRESADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
				$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
			}elseif(in_array($row['vbp_estado'],['INGRESADO'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6033,$this->getAccesRol())) $this->getJSON("No tiene permisos para asignar personal para la revisión!");
				// GENERAR DATOS PARA ASIGNAR UN INSPECTOR
				$row['vbp_estado']='ASIGNADO';
				// MODAL A DESPLEGAR
				$row['modal']='vbpinspector';
				$row['tb']=$this->entity;
			}elseif(in_array($row['vbp_estado'],['ASIGNADO','REVISION','CORRECCION'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6034,$this->getAccesRol())) $this->getJSON("No tiene permisos para registrar la revisión de este proyecto!");
				// MODAL A DESPLEGAR
				$row['modal']="Vbp";
				$row['tb']=$this->entity;
			}elseif(in_array($row['vbp_estado'],['REVISADO'])){
				// VALIDAR PERMISO PARA GENERAR ORDEN DE COBRO
				if(!in_array(6035,$this->getAccesRol())) $this->getJSON("No cuenta con los privilegios para generar la orden de cobro de la solicitud <b>#$code</b>, favor direccione el trámite con quien corresponda para que genere la orden de cobro!");
				
				// VALIDAR SI PROCEDE A PAGO
				$payment=$this->validateEntityExemptPayment($row['fk_entidad_id'],$this->entity,$row['vbp_id']);
				// VALIDAR ROL PARA APROBAR TRAMITES SIN PAGO
				if($payment['estado']=='info') $this->getJSON((!in_array(60351,$this->getAccesRol()))?$this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE']:$payment);
				
				// DATOS PARA GENERAR ORDEN DE COBRO
				$row['entity']=$this->entity;
				$row['entityId']=$row['vbp_id'];
				// MODAL A DESPLEGAR
				$row['modal']='Generarordenescobro';
				$row['edit']=false;
			}elseif(in_array($row['vbp_estado'],['ORDEN DE COBRO GENERADA'])){
				$this->getJSON("La solicitud <b>#$code</b> de VISTO BUENO DE PLANOS se encuentra pendiente de pago!");
			}elseif(in_array($row['vbp_estado'],['PAGADO'])){
				$this->getJSON("La solicitud <b>#$code</b> de VISTO BUENO DE PLANOS se encuentra pagada, por favor dirija el trámite a quien corresponda autorizar el Visto Bueno!");
			}elseif(in_array($row['vbp_estado'],['APROBADO'])){
				$this->getJSON("La solicitud <b>#$code</b> de VISTO BUENO DE PLANOS ya ha sido revisado y aprobado!");
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
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestById($id){
		// OBTENER DATOS DE PROYECTO
		$json=$this->db->viewById($id,$this->entity);
		// ID DE REGISTRO PARA CONSULTAR PROFESIONALES
		$this->post['projectId']=$this->post['id'];
		// PROFESIONALES RESPONSABLES
		$json['professionals']=$this->requestProfesionalsByProject()['professionals'];
		// TECNICOS DE INSPECCION
		$json['inspectors']=$this->getInspectorsById($this->post['id'],'vbp');
		// REVISIONES DE PROCESOS
		$json['reviews']=$this->db->findAll($this->db->selectFromView('revisiones_procesos',"WHERE fk_entidad='{$this->entity}' AND fk_entidad_id={$id} ORDER BY revision_fecha_registro DESC"));
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}
	
	/*
	 * CONSULTA DE PROYECTOS
	 */ 
	public function requestVbp(){
		// CONSULTAR POR ID
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// INSPECTORES POR ID
		elseif(isset($this->post['type']) && $this->post['type']=='getInspectors') $json=$this->getInspectorsByCode($this->post['vbp_id'],$this->entity);
		// LISTADO DE LAYERS
		elseif(isset($this->post['geoJSON'])) $json=$this->getGeoJSON($this->entity,$this->post['geoJSON']);
		// CONSULTAR LOS RESPONSABLES DE LA ELABORACIÓN DEL PLAN DE CONTIGENCIA
		elseif(isset($this->post['profesionals'])) $json=$this->requestProfesionalsByProject();
		// CONSULTA POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("Ok",true,$json));
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
		$model['module']='vbp';
		$model['proceso_nombre']="VISTO BUENO DE PLANOS";
		$model['codigo']=$model['vbp_codigo'];
		$model['solicitud']="{$model['proceso_nombre']} #{$model['codigo']}";
		$model['registro']=$this->setFormatDate($model['vbp_registro'],'full');
		// LISTA DE DESTINATARIOS
		$recipientsList=array();
		// VALIDAR ESTADOS INDIVIDUALES DEL REGISTRO
		if($model['vbp_estado']=='PENDIENTE'){
			$template='vbpRequest';
			$subject="SOLICITUD DE {$model['solicitud']} - REGISTRADA";
		}elseif($model['vbp_estado']=='INGRESADO'){
			$template='processStatus';
			$subject="{$model['solicitud']} - INGRESADO";
			// DATOS AUXILIARES
			$model['proceso_estado']="{$model['proceso_nombre']} CONFIRMADA";
			$model['estado']="CONFIRMADA";
			$model['descripcion']="confirmado con especie valorada #{$model['vbp_solicitud']}";
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['CONFIRMED_RECIPIENTS']);
		}elseif($model['vbp_estado']=='APROBADO'){
			$template='processStatus';
			$subject="{$model['solicitud']} - APROBADO";
			// DATOS AUXILIARES
			$model['proceso_estado']="{$model['proceso_nombre']} APROBADO";
			$model['estado']="DESPACHADA";
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
		$mailCtrl->asyncMail($subject,$model['representantelegal_email']);
	}
	
	/*
	 * INGRESO DE NUEVOS REGISTROS
	 */
	public function insertVbp(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// IDE DE SESIÓN DE ENTIDAD
		$sessionId=app\session::get();
		// INGRESAR REGISTRO CON ENTIDAD
		$this->post['fk_entidad_id']=$sessionId;
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
		$this->post['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
		// VALIDAR DATOS DE FACTURACIÓN
		if($this->post['vbp_facturacion']=='MISMA') $this->post['facturacion_id']=$this->post['fk_entidad_id'];
		else{
			if(isset($this->post['billing']['entidad_id']) && $this->post['billing']['entidad_id']>0) $this->post['facturacion_id']=$this->post['billing']['entidad_id'];
			else $this->getJSON("No se ha encontrado los datos para facturación, por favor asegúrese de ingresar bien los datos!.");
		}
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		
		// OBTENER ID DE VBP INGRESADO
		$vbpId=$this->db->getLastID($this->entity);
		
		// SET MAPA DE COORDENADAS
		$this->setMapCoordinates($this->entity,$vbpId,$this->post);
		// SET GEOJSON
		$this->setGeoJSON($this->entity,$vbpId,$this->post['coordenada_marks']);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * INSPECTORES
	 */
	protected function setPersonal($process,$processId,$list){
		// RELACION DE MODELO+
		$relation=$this->config['modelRelations'][$process];
		// VALIDAR LONGITUD DE LISTADO DE PERSONAL
		if(count($list)>0){
			// ELIMINAR PERSONAL ASIGNADO
			$this->db->executeTested($this->db->getSQLDelete($relation['fk'],"WHERE {$relation['fk_id']}={$processId}"));
		}
		// RECORRER LISTA DE PERSONAL
		foreach($list as $personalId){
			// GENERAR MODELO TEMPORAL
			$aux=array(
				$relation['fk_id']=>$processId,
				'fk_personal_id'=>$personalId
			);
			// INGRESAR PERSONAL PARA REVISIÓN
			$this->db->executeTested($this->db->getSQLInsert($aux,$relation['fk']));
		}
	}
	
	
	/*
	 * EDICIÓN DE REGISTROS
	 */
	public function updateVbp(){
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// MODELO PARA DATOS ORIGINALES
		$oldData=$this->db->viewById($this->post['vbp_id'],$this->entity);
		
		// VALIDAR DATOS DE FACTURACIÓN
		if($this->post['vbp_facturacion']=='MISMA') $this->post['facturacion_id']=$this->post['fk_entidad_id'];
		else{
			if(isset($this->post['billing']['entidad_id']) && $this->post['billing']['entidad_id']>0) $this->post['facturacion_id']=$this->post['billing']['entidad_id'];
			else{
				if($oldData['vbp_estado']=='PENDIENTE' && $this->post['vbp_estado']!='INGRESADO') $this->getJSON("No se ha encontrado los datos para facturación, por favor asegúrese de ingresar bien los datos!.");
			}
		}
		// VALIDAR POSIBLES ESTADOS DE REGISTRO
		if($this->post['vbp_estado']=='INGRESADO'){
			// VALIDAR NÚMERO DE SOLICITUD
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $this->post['vbp_solicitud']=$this->testRequestNumber($this->entity,$this->post['vbp_solicitud'],$this->post['vbp_id']);
		}elseif($this->post['vbp_estado']=='APROBADO'){
			
			// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
			$this->post['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
			
			// GENERAR NÚMERO DE SERIE
			if($this->post['vbp_serie']<1 && $oldData['vbp_estado']!='VALIDACION') $this->post['vbp_serie']=$this->db->getNextSerie($this->entity);
		}else{
			// REGISTRAR EL INGRESO DE PERSONAL PARA REVISIÓN
			if(isset($this->post['asignInspector']) && $this->post['asignInspector']){
				// INVOCAR MÉTODO PARA REGISTRO DE PERSONAL
				$this->setPersonal($this->entity,$this->post['vbp_id'],$this->post['selected']);
			}
		}
		// ACTUALIZAR DATOS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// SET MAPA DE COORDENADAS
		$this->setMapCoordinates($this->entity,$this->post['vbp_id'],$this->post,false);
		// SET GEOJSON
		$this->setGeoJSON($this->entity,$this->post['vbp_id'],$this->post['coordenada_marks']);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * INGRESAR DATOS DE RESPONSABLES
	 */
	public function setProfessionals(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRO DE PROFESIONALES
		$sql=$this->insertProfessionals();
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * REPORTE REGISTROS INCOMPLETOS
	 */
	protected function testCustomReport($pdfCtrl,$user,$row){
		$msg="";
		// VALIDACIONES
		// if($row['proyectista_id']<1) $msg.="<li>Completar los datos del Proyectista.</li>";
		if($row['responsable_mt_rpmci']<1) $msg.="<li>Completar los datos del Diseñador del Sistema Contra Incendios.</li>";
		// VALIDAR MENSAJE
		if($msg=="") return true;
		// RETORNAR CONSULTA
		return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * INFORMACIÓN ADICIONAL DE REPORTES
	 */
	protected function completeReportProfesionals($vbpId){
		// MODELO DE PROFESIONALES
		$data="";
		// ENVIAR ID DE REGISTRO
		$this->post['projectId']=$vbpId;
		// LISTADO DE PROFESIONALES
		$professionals=$this->requestProfesionalsByProject();
		// LISTADO DE PROFESIONALES DE PROYECTO
		foreach($professionals['professionals'] as $val){
			// VARIABLE AUXILIAR
			$aux=$this->varGlobal['vbp.professionals.html'];
			// DATOS DE PROFESSIONAL
			$professional=array_merge($val,$val['person']);
			unset($professional['person']);
			// INSERTAR CAMPOS DE PROFESIONAL
			foreach($professional as $k=>$v){$aux=preg_replace('/{{'.$k.'}}/',$v,$aux);}
			// CARGAR DATOS DE PROFESIONAL
			$data.=$aux;
		}
		// RETORNAR MODELO
		return $data;
	}
	
	/*
	 * LISTA DE PERSONAL ASIGNADO PARA INSPECCIÓN Y REVISIÓN
	 */
	protected function getInspectorsTemplate($processId,$process){
		// RELACION DE PROCESO
		$relation=$this->config['modelRelations'][$process];
		// GENERAR STRING DE CONSULTA
		$strInspectors=$this->db->selectFromView($this->db->setCustomTable($relation['fk'],'fk_personal_id'),"WHERE {$relation['fk_id']}={$processId}");
		// PLANTILLA
		$template="";
		// BUSCAR INSPECTORES Y PARSEAR DATOS
		foreach($this->db->findAll($this->db->selectFromView('vw_personal',"WHERE ppersonal_id IN ({$strInspectors})")) AS $key=>$val){
			// FILA PARA INSERTAR PERSONAL
			$template.="<tr><td>{{index}}</td><td>{{personal_nombre}}, {{puesto_definicion}}</td></tr>";
			// INDEX DE FILA
			$val['index']=$key+1;
			// VALIDAR SI YA SE HA REGISTRADO EL INSPECTOR
			foreach($val as $k=>$v){$template=preg_replace('/{{'.$k.'}}/',$v,$template);}
		}
		// RETORNAR PLANTILLA DE PROFESIONALES
		return $template;
	}
	
	/*
	 * DATOS PARA FACTURACIÓN
	 */
	protected function getBillingData($row,$entityId){
		// DATOS DE FACTURACIÓN
		$tempate=$this->varGlobal['vbp.billing.html'];
		// OBTENER DATOS DE FACTURACIÓN
		$billingData=$this->db->viewById($entityId,'entidades');
		// PARSE DATOS DE FACTURACIÓN
		$billingData['address']="{$row['vbp_parroquia']} / {$row['vbp_principal']} Y {$row['vbp_secundaria']}";
		// IMPRIMIR DATOS EN PLANTILLA
		foreach($billingData as $k=>$v){$tempate=preg_replace('/{{'.$k.'}}/',$v,$tempate);}
		// RETORNAR DATOS DE FACTURACIÓN
		return $tempate;
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE - SOLICITUD CBSD
	 */
	public function printDetail($pdfCtrl,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE vbp_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testCustomReport($pdfCtrl,$row['responsable_nombre'],$row)) return '';
			// ADJUNTAR DATOS DE DECLARACIÓN DE AUTOINSPECCIÓN - PRIMER PÁRRAFO
			if(in_array($row['entidad_contribuyente'],['publica','privada'])) $row['VBP_SOLICITUD']=$this->varGlobal['VBP_SOLICITUD_SOCIEDADES'];
			else $row['VBP_SOLICITUD']=$this->varGlobal['VBP_SOLICITUD_NATURALES'];
			// LISTA DE PROFESIONALES
			$row['TbProfessionals']=$this->completeReportProfesionals($row['vbp_id']);
			
			// DATOS DE FACTURACIÓN
			$row['TbBilling']=$this->getBillingData($row,$row['facturacion_id']);
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['vbp_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRIMIR SOLICITUD DE ESTADO
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE vbp_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR REGISTRO DE EMISION SIN PAGO
			if($row['vbp_estado']=='REVISADO' && isset($this->get['exemptPayment'])){
				// VALIDAR PERMISO PARA APROBAR TRÁMITE SIN PAGO
				if(!in_array(60351,$this->getAccesRol())){
					return $pdfCtrl->printCustomReport($this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE'],$this->getProfile()['usuario_login'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
				}
				
				// REGISTRAR SOLICITUD SIN PAGO
				$this->insertExemptPayment($row['fk_entidad_id'],$this->entity,$id);
				// ACTUALIZAR DATOS DE APROBACIÓN
				$row['vbp_estado']='PAGADO';
				
			}
			
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['vbp_estado'],['CORRECCION'])) return $pdfCtrl->printCustomEmptyReport($this->varGlobal['VBP_CORRECCION_TEMPLATE'],$row);
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['vbp_estado'],['PENDIENTE','INGRESADO'])) return $pdfCtrl->printCustomReport('<li>El VISTO BUENO no ha sido aprobado</li>',$row['responsable_firma_nombre'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
			
			// EMPEZAR TRANSACCIÓN
			$this->db->begin();
			
			// RESPUESTA POR DEFECTO
			$sql=$this->setJSON("OPERACIÓN ABORTADA",true);
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			if($row['vbp_estado']=='PAGADO'){
				// TRANSICIÓN DE ESTADO
				$row['vbp_estado']='APROBADO';
				$row['vbp_aprobado']=$this->getFecha('dateTime');
				$row['vbp_anio']=$this->setFormatDate($row['vbp_aprobado'],'Y');
				// OBTENER ID DE PERSONAL RESPONSABLE DE APROBACIÓN
				$userId=app\session::getSessionAdmin();
				$strWhr=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE usuario_id={$userId}");
				$strAprueba=$this->db->selectFromView('vw_personal',"WHERE fk_persona_id=({$strWhr}) AND ppersonal_estado='EN FUNCIONES' "); // AND personal_definicion='TITULAR'
				if($this->db->numRows($strAprueba)<1) $this->getJSON("No se ha podido generar la aprobación, el usuario que intenta generar el VBP no cuenta con un registro como personal institucional!");
				$row['vbp_aprueba']=$this->db->findOne($strAprueba)['ppersonal_id'];
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['vbp_serie']==0) $row['vbp_serie']=$this->db->getNextSerie($this->entity);
				$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}
			
			// NOMBRE DE RESPONSABLE QUE APRUEBA EL VBP
			$row['vbp_aprueba_nombre']=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['vbp_aprueba']}"))['personal_nombre'];
			// PARSE FECHAS
			$row['vbp_elaborado']=$this->setFormatDate($row['vbp_elaborado'],'complete');
			$row['vbp_revisado']=$this->setFormatDate($row['vbp_revisado'],'complete');
			$row['vbp_aprobado']=$this->setFormatDate($row['vbp_aprobado'],'complete');
			// NUMERO DE PERMISO
			$row['vbp_serie']=str_pad($row['vbp_serie'],3,"0",STR_PAD_LEFT);
			
			// PERSONAL TÉCNICO DE REVISIÓN
			$row['listaTecnicos']=$this->getInspectorsTemplate($id,$this->entity);
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// DATOS DE PAGO
			$billing=$this->db->findOne($this->db->selectFromView('ordenescobro',"WHERE orden_entidad='{$this->entity}' AND orden_entidad_id={$row['vbp_id']}"));
			// PARSE NUMBER TO LETTER
			$billing['orden_total_text']=$this->setNumberToLetter($billing['orden_total'],true);
			// CARGAR DATOS DE PAGO
			$pdfCtrl->loadSetting($billing);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['vbp_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity,'permit');
			// CLOSE TRANSACCIÓN
			$sql=$this->db->closeTransaction($sql);
			// VALIDAR OPERACIÓN
			if(!$sql['estado']) $this->getJSON($sql); 
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	} 
	
}