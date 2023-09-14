<?php namespace model\prevention;
use api as app;
use model as mdl;

class feasibilityModel extends mdl\personModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='factibilidadglp';
	protected $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'prevention')['glp'];
	}
	
	
	/*
	 * REGISTRAR CILINDROS DE GLP
	 */
	private function insertTanks($feasibilityId,$tanksList){
		// VALIDAR TAMAÑO DE LISTA
		if(count($tanksList)<1) return $this->setJSON("Transacción rechazada, debe registrar los tanques de GLP con sus respectiva capacidad en m3");
		// ELIMINAR REGISTRO DE TANQUES EXISTENTES
		$sql=$this->db->executeTested($this->db->getSQLDelete('factibilidad_tanques',"WHERE fk_factibilidad_id={$feasibilityId}"));
		// RECORRER LISTADO DE TANQUES DE GLP
		foreach($tanksList as $k=>$v){
			$v['fk_factibilidad_id']=$feasibilityId;
			// GENERAR STRING DE CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'factibilidad_tanques'));
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * INSERTAR PROFESIONALES
	 */
	private function insertProfessionals(){
		// VALIDAR LONGITUD DE PROFESIONALES
		if(count($this->post['professionalList'])<1) return $this->setJSON("No se ha registrado ningún profesional");
		// ELIMINAR LOS PROFESIONALES EXISTENTES
		$sql=$this->db->executeTested($this->db->getSQLDelete('factibilidad_profesionales',"WHERE fk_factibilidad_id={$this->post['factibilidad_id']}"));
		// RECORRER LISTADO DE PROFESIONALES
		foreach($this->post['professionalList'] as $k=>$v){
			// VALIDAR PERFIL ACADÉMICO DE PROFESIONAL
			$v=$this->testAcademicInformation($v);
			// REGISTRAR RESPONSABLE
			$person=$this->requestPerson($v['person']);
			// CONSULTAR PERFIL ACADÉMICO
			$sql=$this->requestAcademicTraining($v,$person['persona_id']);
			// OBTENER ID DE REGISTRO DE TITULO
			$titulo=$this->db->findOne($this->db->selectFromView('formacionacademica',"WHERE UPPER(formacion_senescyt)=UPPER('{$v['formacion_senescyt']}')"));
			// GENERAR MODELO PARA REGISTRAR PROFESIONAL
			$v['fk_factibilidad_id']=$this->post['factibilidad_id'];
			$v['fk_profesional_id']=$titulo['formacion_id'];
			// MODELO DE
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'factibilidad_profesionales'));
		}
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * INSPECTORES
	 */
	protected function setPersonal($process,$processId,$list){
		// RELACION DE MODELO
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
	 * INSERTAR REGISTRO PARA VALIDACIÓN
	 */
	protected function insertProject(){
		// IDE DE SESIÓN DE ENTIDAD
		$sessionId=app\session::get();
		// INGRESAR REGISTRO CON ENTIDAD
		$this->post['fk_entidad_id']=$sessionId;
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITA
		$this->post['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
		// VALIDAR DATOS DE FACTURACIÓN
		$this->post['facturacion_id']=$this->post['fk_entidad_id'];
		// INGRESO DE ESTADO DE VALIDACIÓN
		$this->post['factibilidad_estado']='VALIDACION';
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		return $this->db->getLastID($this->entity);
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
		$inspectorsList=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE fk_perfil_id=6 AND usuario_estado='ACTIVO'");
		// LISTA DE INSPECTORES
		$data['list']=$this->db->findAll($this->db->selectFromView('vw_personal',"WHERE fk_persona_id IN ($inspectorsList) AND ppersonal_estado='EN FUNCIONES'"));
		// INSPECTORES YA INGRESADOS
		foreach($this->db->findAll($this->db->selectFromView($relation['fk'],"WHERE {$relation['fk_id']}={$processId}")) AS $k=>$v){
			// VALIDAR SI YA SE HA REGISTRADO EL INSPECTOR
			if(!in_array($v['fk_personal_id'],$data['selected'])) $data['selected'][]=$v['fk_personal_id'];
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR RESPONSABLES DE POF POR ID
	 */
	private function requestProfesionalsByProject(){
		// MODELO DE PROFESIONALES
		$json['professionalList']=array();
		// VALIDAR SI SE HA ENVIADO EL ID DEL PROYECTO
		if(isset($this->post['projectId'])){
			// DATOS DE PROYECTO
			$json=$this->db->viewById($this->post['projectId'],$this->entity);
			// GENERAR MODELO DE PROFESIONALES
			foreach($this->db->findAll($this->db->selectFromView('factibilidad_profesionales',"WHERE fk_factibilidad_id={$this->post['projectId']}")) as $k=>$v){
				// DATOS DE PROFESIONAL
				$title=$this->db->findById($v['fk_profesional_id'],'formacionacademica');
				// CONSULTAR EL PERFIL ACADÉMICO
				$agent=$this->db->findById($title['fk_persona_id'],'personas');
				// CONSULTAR SI EXISTE REGISTRO
				$json['professionalList'][]=array_merge($v,$title,array('person'=>$agent));
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
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(factibilidad_codigo)=UPPER('{$code}')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO INCIADO
			if(in_array($row['factibilidad_estado'],['VALIDACION'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6178,$this->getAccesRol())) $this->getJSON($this->varGlobal['BAD_INPUT_REQUEST_ROL']."<br>Esta solicitud debe ser aprobada por un responsable de la Unidad de Prevención.");
				// INGRESAR DATOS DE VBP
				$row['factibilidad_estado']='APROBADO';
				$row['factibilidad_observacion']='APROBACIÓN POR VALIDACIÓN PREVIA';
				// MODAL A DESPLEGAR
				$row['modal']='ValidacionVbp';
				$row['tb']=$this->entity;
			}elseif(in_array($row['factibilidad_estado'],['PENDIENTE'])){
				// VALIDAR QUE SE REGISTREN LOS PROFESIONALES RESPONSABLES
				if(intval($row['r_memoriatecnica'])<1 || intval($row['r_planos'])<1){
					$this->getJSON("Para realizar el ingreso de esta solicitud debe registrar los datos de los profesionales responsables del proyecto!");
				}
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6172,$this->getAccesRol())) $this->getJSON($this->varGlobal['BAD_INPUT_REQUEST_ROL']);
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['factibilidad_estado']='INGRESADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
				$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
			}elseif(in_array($row['factibilidad_estado'],['INGRESADO'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6173,$this->getAccesRol())) $this->getJSON("No tiene permisos para asignar personal para la revisión!");
				// GENERAR DATOS PARA ASIGNAR UN INSPECTOR
				$row['factibilidad_estado']='ASIGNADO';
				// MODAL A DESPLEGAR
				$row['modal']='factibilidadinspector';
				$row['tb']=$this->entity;
			}elseif(in_array($row['factibilidad_estado'],['ASIGNADO','REVISION','CORRECCION'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6174,$this->getAccesRol())) $this->getJSON("No tiene permisos para registrar la revisión de este proyecto!");
				// MODAL A DESPLEGAR
				$row['modal']='Factibilidadglp';
				$row['tb']=$this->entity;
			}elseif(in_array($row['factibilidad_estado'],['REVISADO'])){
				// VALIDAR PERMISO PARA GENERAR ORDEN DE COBRO
				if(!in_array(6175,$this->getAccesRol())) $this->getJSON("No cuenta con los privilegios para generar la orden de cobro de la solicitud <b>#$code</b>, favor direccione el trámite con quien corresponda para que genere la orden de cobro!");
				
				// VALIDAR SI PROCEDE A PAGO
				$payment=$this->validateEntityExemptPayment($row['fk_entidad_id'],$this->entity,$row['factibilidad_id']);
				// VALIDAR ROL PARA APROBAR TRAMITES SIN PAGO
				if($payment['estado']=='info') $this->getJSON((!in_array(61751,$this->getAccesRol()))?$this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE']:$payment);
				
				// DATOS PARA GENERAR ORDEN DE COBRO
				$row['entity']=$this->entity;
				$row['entityId']=$row['factibilidad_id'];
				// MODAL A DESPLEGAR
				$row['modal']='Generarordenescobro';
				$row['edit']=false;
			}elseif(in_array($row['factibilidad_estado'],['ORDEN DE COBRO GENERADA'])){
				$this->getJSON("La solicitud <b>#$code</b> de VISTO BUENO DE PLANOS se encuentra pendiente de pago!");
			}elseif(in_array($row['factibilidad_estado'],['PAGADO'])){
				$this->getJSON("La solicitud <b>#$code</b> de VISTO BUENO DE PLANOS se encuentra pagada, por favor dirija el trámite a quien corresponda autorizar el Visto Bueno!");
			}elseif(in_array($row['factibilidad_estado'],['APROBADO'])){
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
	private function requestById($id,$option){
		// OBTENER DATOS DE REGISTRO
		$data=$this->db->viewById($id,$this->entity);
		// MODELO PARA DATOS DE FACTURACIÓN
		$data['billing']=array();
		// VALIDAR DATOS DE FACTURACIÓN
		if($data['factibilidad_facturacion']=='OTRA'){
			// CARGAR DATOS DE FACTURACIÓN
			$data['billing']=$this->db->findOne($this->db->selectFromView('entidades',"WHERE entidad_ruc='{$data['facturacion_ruc']}'"));
		}
		// OBTENER DATOS DE TANQUES
		$data['tanksList']=$this->db->findAll($this->db->selectFromView('factibilidad_tanques',"WHERE fk_factibilidad_id={$id}"));
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	
	/*
	 * CONSULTA DE PROYECTOS
	 */ 
	public function requestEntity(){
		// CONSULTAR POR ID
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id'],'full');
		// CONSULTAR POR ID
		if(isset($this->post['feasibilityId'])) $json=$this->requestById($this->post['feasibilityId'],'short');
		// CONSULTAR POR CÓDIGO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// INSPECTORES POR ID
		elseif(isset($this->post['type']) && $this->post['type']=='getInspectors') $json=$this->getInspectorsByCode($this->post['factibilidad_id'],$this->entity);
		// CONSULTAR LOS RESPONSABLES DE LA ELABORACIÓN DEL PLAN DE CONTIGENCIA
		elseif(isset($this->post['profesionals'])) $json=$this->requestProfesionalsByProject();
		// CONSULTA POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * INGRESO DE NUEVOS REGISTROS
	 */
	public function insertEntity(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// IDE DE SESIÓN DE ENTIDAD
		$sessionId=app\session::get();
		// INGRESAR REGISTRO CON ENTIDAD
		$this->post['fk_entidad_id']=$sessionId;
		// VALIDAR DATOS DE FACTURACIÓN
		if($this->post['factibilidad_facturacion']=='MISMA') $this->post['facturacion_id']=$this->post['fk_entidad_id'];
		else{
			if(isset($this->post['billing']['entidad_id']) && $this->post['billing']['entidad_id']>0) $this->post['facturacion_id']=$this->post['billing']['entidad_id'];
			else $this->getJSON("No se ha encontrado los datos para facturación, por favor asegúrese de ingresar bien los datos!.");
		}
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITA
		$this->post['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		$feasibilityId=$this->db->getLastID($this->entity);
		// REGISTRAR TANQUES DE GLP
		$sql=$this->insertTanks($feasibilityId,$this->post['tanksList']);
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * EDICIÓN DE REGISTROS
	 */
	public function updateEntity(){
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// MODELO PARA DATOS ORIGINALES
		$oldData=$this->db->viewById($this->post['factibilidad_id'],$this->entity);
		// VALIDAR DATOS DE FACTURACIÓN
		if($this->post['factibilidad_facturacion']=='MISMA') $this->post['facturacion_id']=$this->post['fk_entidad_id'];
		else{
			if(isset($this->post['billing']['entidad_id']) && $this->post['billing']['entidad_id']>0) $this->post['facturacion_id']=$this->post['billing']['entidad_id'];
			else{
				if($oldData['factibilidad_estado']=='PENDIENTE' && $this->post['factibilidad_estado']!='INGRESADO') $this->getJSON("No se ha encontrado los datos para facturación, por favor asegúrese de ingresar bien los datos!.");
			}
		}
		// VALIDAR POSIBLES ESTADOS DE REGISTRO
		if($this->post['factibilidad_estado']=='INGRESADO'){
			// VALIDAR NÚMERO DE SOLICITUD
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $this->post['factibilidad_solicitud']=$this->testRequestNumber($this->entity,$this->post['factibilidad_solicitud'],$this->post['factibilidad_id']);
		}elseif($this->post['factibilidad_estado']=='APROBADO'){
			
			// INSERTAR RESPONSABLE DE PREVENCION - SOLICITA
			$this->post['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
			
			// GENERAR NÚMERO DE SERIE
			if($this->post['factibilidad_serie']<1 && $oldData['factibilidad_estado']!='VALIDACION') $this->post['factibilidad_serie']=$this->db->getNextSerie($this->entity);
		}else{
			// REGISTRAR EL INGRESO DE PERSONAL PARA REVISIÓN
			if(isset($this->post['asignInspector']) && $this->post['asignInspector']){
				// INVOCAR MÉTODO PARA REGISTRO DE PERSONAL
				$this->setPersonal($this->entity,$this->post['factibilidad_id'],$this->post['selected']);
			}
		}
		// ACTUALIZAR DATOS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// REGISTRAR TANQUES DE GLP
		if(!in_array($this->post['factibilidad_estado'],['INGRESADO','ASIGNADO'])) $sql=$this->insertTanks($this->post['factibilidad_id'],$this->post['tanksList']);
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
	private function testCustomReport($pdfCtrl,$user,$row){
		$msg="";
		// VALIDACIONES
		if($row['r_memoriatecnica']<1) $msg.="<li>Registrar al (los) responsable(s) de la memoria técnica.</li>";
		if($row['r_planos']<1) $msg.="<li>Registrar al (los) responsable(s) de planos.</li>";
		// VALIDAR MENSAJE
		if($msg=="") return true;
		// RETORNAR CONSULTA
		return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * INFORMACIÓN ADICIONAL DE REPORTES
	 */
	protected function completeReportProfesionals($feasibilityId){
		// MODELO DE PROFESIONALES
		$data="";
		// ENVIAR ID DE REGISTRO
		$this->post['projectId']=$feasibilityId;
		// LISTADO DE PROFESIONALES DE PROYECTO
		foreach($this->requestProfesionalsByProject()['professionalList'] as $key=>$val){
			$val['professional']=$val['profesional_tipo'];
			// VARIABLE AUXILIAR
			$aux=$this->varGlobal['vbp.professionals.html'];
			// INSERTAR CAMPOS DE PROFESIONAL
			foreach($val as $k=>$v){$aux=preg_replace('/{{'.$k.'}}/',$v,$aux);}
			// CARGAR DATOS DE PROFESIONAL
			$data.=$aux;
		}
		// RETORNAR MODELO
		return $data;
	}
	
	/*
	 * DEGLOZAR LOS TANQUES DE GLP
	 */
	protected function getTanksGLP($feasibilityId,$option='request'){
		// CONSULTAR DATOS
		$tanks=$this->db->findAll($this->db->selectFromView('factibilidad_tanques',"WHERE fk_factibilidad_id={$feasibilityId}"));
		// VALIDAR OPCION
		if($option=='request'){
			// VARIABLES PARA RETORNAR TEXTO
			$txt="";
			$length=count($tanks);
			// RECORRER LISTA DE TANQUES
			foreach($tanks as $k=>$v){
				$txt.="<b>{$v['tanque_capacidad']} <small>m3</small></b>";
				if(($length-1)==($k+1) && !$length<($k+1)) $txt.=" y ";
				if($k<$length-2) $txt.=", ";
			}
			// RETORNAR DATOS
			return $txt;
		}elseif($option=='table'){
			return 'total';
		}
		// RETORNAR DATOS
		return $tanks;
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
	protected function getBillingData($row){
		// DATOS DE FACTURACIÓN
		$tempate=$this->varGlobal['vbp.billing.html'];
		// OBTENER DATOS DE FACTURACIÓN
		$billingData=$this->db->viewById($row['facturacion_id'],'entidades');
		// PARSE DATOS DE FACTURACIÓN
		$billingData['address']="{$row['factibilidad_parroquia']} / {$row['factibilidad_principal']} Y {$row['factibilidad_secundaria']}";
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
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE factibilidad_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testCustomReport($pdfCtrl,$row['entidad_razonsocial'],$row)) return '';
			
			// ADJUNTAR DATOS DE DECLARACIÓN DE AUTOINSPECCIÓN - PRIMER PÁRRAFO
			if(in_array($row['entidad_contribuyente'],['publica','privada'])) $row['FACTIBILIDAD_SOLICITUD']=$this->varGlobal['FACTIBILIDAD_SOLICITUD_SOCIEDADES'];
			else $row['FACTIBILIDAD_SOLICITUD']=$this->varGlobal['FACTIBILIDAD_SOLICITUD_NATURALES'];
			
			// DESCRIPCIÓN DE TANQUES ESTACIONARIOS
			$row['tanques_capacidad_unidades']=$this->getTanksGLP($row['factibilidad_id'],'request');
			// LISTA DE PROFESIONALES
			$row['TbProfessionals']=$this->completeReportProfesionals($row['factibilidad_id']);
			// DATOS DE FACTURACIÓN
			$row['TbBilling']=$this->getBillingData($row);
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['factibilidad_codigo']);
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
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE factibilidad_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR REGISTRO DE EMISION SIN PAGO
			if($row['factibilidad_estado']=='REVISADO' && isset($this->get['exemptPayment'])){
				// VALIDAR PERMISO PARA APROBAR TRÁMITE SIN PAGO
				if(!in_array(61751,$this->getAccesRol())){
					return $pdfCtrl->printCustomReport($this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE'],$this->getProfile()['usuario_login'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
				}
				// REGISTRAR SOLICITUD SIN PAGO
				$this->insertExemptPayment($row['fk_entidad_id'],$this->entity,$id);
				// ACTUALIZAR DATOS DE APROBACIÓN
				$row['factibilidad_estado']='APROBADO';
			}
			
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['factibilidad_estado'],['CORRECCION'])) return $pdfCtrl->printCustomEmptyReport($this->varGlobal['VBP_CORRECCION_TEMPLATE'],$row);
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['factibilidad_estado'],['PENDIENTE','INGRESADO'])) return $pdfCtrl->printCustomReport('<li>La FACTIBILIDAD no ha sido aprobado</li>',$row['representantelegal_nombre'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
			
			// EMPEZAR TRANSACCIÓN
			$this->db->begin();
			
			// RESPUESTA POR DEFECTO
			$sql=$this->setJSON("OPERACIÓN ABORTADA",true);
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			if($row['factibilidad_estado']=='PAGADO'){
				// TRANSICIÓN DE ESTADO
				$row['factibilidad_estado']='APROBADO';
				$row['factibilidad_aprobado']=$this->getFecha('dateTime');
				$row['factibilidad_anio']=$this->setFormatDate($row['factibilidad_aprobado'],'Y');
				// OBTENER ID DE PERSONAL RESPONSABLE DE APROBACIÓN
				$userId=app\session::getSessionAdmin();
				$strWhr=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE usuario_id={$userId}");
				$strAprueba=$this->db->selectFromView('personal',"WHERE fk_persona_id=({$strWhr})");
				if($this->db->numRows($strAprueba)<1) $this->getJSON("No se ha podido generar la aprobación, el usuario que intenta generar el VBP no cuenta con un registro como personal institucional!");
				$row['factibilidad_aprueba']=$this->db->findOne($strAprueba)['personal_id'];
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['factibilidad_serie']==0) $row['factibilidad_serie']=$this->db->getNextSerie($this->entity);
				
				// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
				$this->post['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
				
				$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}
			
			// NOMBRE DE RESPONSABLE QUE APRUEBA EL VBP
			$row['factibilidad_aprueba_nombre']=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['factibilidad_aprueba']}"))['personal_nombre'];
			// PARSE FECHAS
			$row['factibilidad_elaborado']=$this->setFormatDate($row['factibilidad_elaborado'],'complete');
			$row['factibilidad_revisado']=$this->setFormatDate($row['factibilidad_revisado'],'complete');
			$row['factibilidad_aprobado']=$this->setFormatDate($row['factibilidad_aprobado'],'complete');
			// NUMERO DE PERMISO
			$row['factibilidad_serie']=str_pad($row['factibilidad_serie'],3,"0",STR_PAD_LEFT);
			// DESCRIPCIÓN DE TANQUES ESTACIONARIOS
			$row['tanques_capacidad_unidades']=$this->getTanksGLP($row['factibilidad_id'],'request');
			
			// PERSONAL TÉCNICO DE REVISIÓN
			$row['listaTecnicos']=$this->getInspectorsTemplate($id,$this->entity);
			// LISTA DE PROFESIONALES
			$row['TbProfessionals']=$this->completeReportProfesionals($row['factibilidad_id']);
			
			// DATOS DE PAGO
			$billing=$this->db->findOne($this->db->selectFromView('ordenescobro',"WHERE orden_entidad='{$this->entity}' AND orden_entidad_id={$row['factibilidad_id']}"));
			// PARSE NUMBER TO LETTER
			$billing['orden_total_text']=$this->setNumberToLetter($billing['orden_total'],true);
			// CARGAR DATOS DE PAGO
			$pdfCtrl->loadSetting($billing);
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['factibilidad_codigo']);
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