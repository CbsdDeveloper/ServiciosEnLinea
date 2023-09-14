<?php namespace model\prevention;
use model as mdl;
use controller as ctrl;
use api as app;

class occasionalModel extends mdl\personModel {

	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='ocasionales';
	private $config;

	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'prevention')['occasionals'];
	}
	
	/*
	 * BUSCAR DATOS DEL PERMISO
	 */
	private function requestPermit($post){
		// CONSULTAR SI EXISTE PERMISO
		$str=$this->db->selectFromView('vw_permisos',"WHERE UPPER(codigo_per)=UPPER('{$post['ocasional_permiso']}')");
		if($this->db->numRows($str)>0){
			// DATOS DE PERMISO DE FUNCIONAMIENTO
			$permit=$this->db->findOne($str);
			// INTEGRAR DATOS DE PERMISO EN FORMULARIO
			$post['fk_permiso_id']=$permit['permiso_id'];
			$post['ocasional_parroquia']=$permit['local_parroquia'];
			$post['ocasional_principal']=$permit['local_principal'];
			$post['ocasional_secundaria']=$permit['local_secundaria'];
			// RETORNAR DATOS
			return $post;
		}else{
			$this->getJSON("El número de permiso ingresado no es válido, por favor intente nuevamente.");
		}
	}
	
	/*
	 * GENERAR RECURSOS NECESARIO PARA EL PERMISO OCASIONAL
	 */
	private function getResourcesById($id){
		// OBTENER DATOS DEL POF
		$pof=$this->db->viewById($id,$this->entity);
		// MODELO DE COMPARACIONES
		$rulesPof=array();
		// MODELO DE DOCUMENTOS
		$document=array('fk_ocasional_id'=>$id);
		// ELIMINAR DOCUMENTOS EXISTENTES PARA PERMISOS OCASIONALES
		$this->db->executeTested($this->db->getSQLDelete('ocasional_has_documentos',"WHERE fk_ocasional_id=$id"));
		// BUSCAR LOS REQUERIMIENTOS PARA EL PERMISO
		$rules=$this->db->findAll($this->db->selectFromView('reglasocasionales',"WHERE regla_estado='ACTIVO' ORDER BY regla_parametro, regla_index"));
		foreach($rules as $k=>$v){
			// VARIABLE AUXILIAR PARA VALIDACIÓN
			$auxRule=array();
			// VARIABLE PARA SENTENCIA SQL
			$str="";
			// VALIDAR LOS TIPOS DE DATOS INGRESADOS
			if($v['regla_tipo']=='NUMERICO'){
				// INGRESAR REGLA
				$auxRule=$v;
				// VALIDAR EL CARACTER DE COMPARACIÓN
				if($v['regla_comparador']=='<') $auxRule['status']=($pof[$v['regla_parametro']]<$v['regla_valor']);
				elseif($v['regla_comparador']=='>') $auxRule['status']=($pof[$v['regla_parametro']]>$v['regla_valor']);
				elseif($v['regla_comparador']=='<>') $auxRule['status']=($pof[$v['regla_parametro']]<>$v['regla_valor']);					
				elseif($v['regla_comparador']=='==') $auxRule['status']=($pof[$v['regla_parametro']]==$v['regla_valor']);										
			}elseif($v['regla_tipo']=='TEXTO'){
				// INGRESAR REGLA
				$auxRule=$v;
				// VALIDAR EL CARACTER DE COMPARACIÓN
				if($v['regla_comparador']=='<>') $auxRule['status']=($pof[$v['regla_parametro']]!=$v['regla_valor']);
				elseif($v['regla_comparador']=='==') $auxRule['status']=($pof[$v['regla_parametro']]==$v['regla_valor']);
			}
			// VALIDAR SI SE HA GENERADO UNA VALIDACIÓN CORRECTA
			if(!empty($auxRule)){
				// VALIDAR SI SE CREADO ANTERIORMENTE EL PARÁMETRO DE VALIDACIÓN - SINO SE CREA
				if(!isset($rulesPof[$v['fk_recurso_id']])) $rulesPof[$v['fk_recurso_id']]=array();
				// INGRESAR EL PARÁMETRO DE VALIDACIÓN
				$rulesPof[$v['fk_recurso_id']][]=$auxRule;
			}
		}
		// PROCESAR VARIABLES ALMACENADAS
		foreach($rulesPof as $k=>$v){
			// AUX - ID DE RECURSO
			$recursoId=0;
			// ESTADO INICIAL DE VALIDACIÓN
			$status=false;
			// VALIDAR LISTADO DE RECURSOS INGRESADOS
			foreach($v as $recurso){
				// ID DE RECURSO
				$recursoId=$recurso['fk_recurso_id'];
				// VALIDAR - MAS DE UNO
				if($recurso['regla_index']>1){
					if($recurso['regla_condicion_multiple']=='AND') $status=($status && $recurso['status']);
					else $status=($status || $recurso['status']);
				}else $status=$recurso['status'];
			}
			// INGRESAR ID DE RECURSO
			if($status){
				$document['fk_recurso_id']=$recursoId;
				// GENERAR CONSULTA
				$str=$this->db->getSQLInsert($document,'ocasional_has_documentos');
				// EJECUTAR CONSULTA
				$sql=$this->db->executeTested($str);
			}
		}
		// RETORNAR DOCUMENTOS PARA POF
		return $this->db->findAll($this->db->getSQLSelect('ocasional_has_documentos',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
	}
	
	/*
	 * INSERTAR NUEVA REGISTRO
	 */
	public function insertOccasional(){
		// VALIDAR EL LUGAR DEL EVENTO / INGRESAR DATOS DEL ESTABLECIMIENTO
		if($this->post['ocasional_lugar']=='ESTABLECIMIENTO') $this->post=$this->requestPermit($this->post);
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRAR PERSONA - PRESIDENTE DEL EVENTO
		$person=$this->requestPerson($this->post);
		$this->post['fk_presidente']=$person['persona_id'];
		// INGRESAR LA REFERENCIA DE QUIEN ESTÁ LOGUEADO
		$this->post['fk_entidad_id']=app\session::get();
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
		$this->post['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
		// GENERAR CONSULTA DE INGRESO
		$json=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		$pof=$this->db->viewById($this->db->getLastID($this->entity),$this->entity);
		// SET MAPA DE COORDENADAS
		$this->setMapCoordinates($this->entity,$pof['ocasional_id'],$this->post);
		// SET GEOJSON
		$this->setGeoJSON($this->entity,$pof['ocasional_id'],$this->post['coordenada_marks']);
		// INGRESAR RECURSOS PARA EL PERMISO OCASIONAL
		$resources=$this->getResourcesById($pof['ocasional_id']);
		// RETORNO DE CONSULTA
		$json=array_merge($this->db->closeTransaction($json),array('data'=>$pof));
		// PARSE DOCUMENTOS PARA LISTADO
		$pof['requirementsList']="";
		foreach($resources as $val){$pof['requirementsList'].="<li>{$val['recurso_nombre']}</li>";}
		
		// NOTIFICACIÓN MEDIANTE CORREO ELECTRÓNICO
		$mailCtrl=new ctrl\mailController('occasionalRequest');
		// DATOS DE PERMISO OCASIONAL
		$mailCtrl->mergeData($pof);
		// DATOS DE PRESIDENTE
		$mailCtrl->mergeData($person);
		$mailCtrl->asyncMail("Solicitud para Permiso Ocasional de Funcionamiento",$person['persona_correo']);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($json);
	}
	
	/*
	 * ACTUALIZAR REGISTRO
	 */
	public function updateOccasional(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// VALIDAR EL LUGAR DEL EVENTO / INGRESAR DATOS DEL ESTABLECIMIENTO
 		if($this->post['ocasional_lugar']=='ESTABLECIMIENTO') $this->post=$this->requestPermit($this->post);
		// REGISTRAR PERSONA - PRESIDENTE DEL EVENTO
 		$person=$this->requestPerson($this->post);
 		$this->post['fk_presidente']=$person['persona_id'];
		// DATOS ORIGINALES DEL PERMISO
		$oldEntity=$this->db->findById($this->post['ocasional_id'],$this->entity);
		// VALIDAR NÚMERO DE SOLICITUD
		if($this->post['ocasional_estado']=='INGRESADO' && $oldEntity['ocasional_estado']=='PENDIENTE'){
			// ESPECIES VALORADAS OMITIDAS EN 2018
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $this->post['ocasional_solicitud']=$this->testRequestNumber($this->entity,$this->post['ocasional_solicitud'],$this->post['ocasional_id']);
		}
		// VALIDAR SI EL PERMISO ES APROBADO SIN HABER GENERADO ORDEN DE COBRO
		if($this->post['ocasional_estado']=='APROBADO' && $oldEntity['ocasional_estado']=='INGRESADO'){}
		// NOTIFICAR MEDIANTE EMAIL
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// SET MAPA DE COORDENADAS
		$this->setMapCoordinates($this->entity,$this->post['ocasional_id'],$this->post,false);
		// SET GEOJSON
		$this->setGeoJSON($this->entity,$this->post['ocasional_id'],$this->post['coordenada_marks']);
		// INGRESAR/MODIFICAR RECURSOS PARA EL PERMISO OCASIONAL
		if(in_array($this->post['ocasional_estado'],['INGRESADO','PENDIENTE'])){
			$resources=$this->getResourcesById($this->post['ocasional_id']);
		}
		// VALIDAR ENVÍO DE PERMISO OCASIONAL APROBADO EN CBSD
		if($sql['estado'] && $this->post['ocasional_estado']=='APROBADO'){
			/*
			$training=$this->viewsById($post['ocasional_id'],$this->entity);
			// COMPROBAR ESTADO PARA REALIZAR ENVÍO DE MAIL
			$mailCtrl=new ctrl\mailController('trainingDispatched');
			$mailCtrl->mergeData($training);
			$mailCtrl->mergeData($this->db->findById($training['fk_entidad_id'],'entidades'));
			$mailCtrl->asyncMail("Capacitación despachada",$training['persona_correo']);
			*/
		}
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZAR REGISTRO DE RECURSOS
	 */
	public function updateResources(){
		// TIPOS DE RECURSOS
		$model=$this->config['referencesList'];
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// RECORRER MODELO DE RECURSOS - CADA RECURSOS SERÁ INGRESADO
		foreach($this->post['model'] as $tb=>$data){
			// ELIMINAR REGISTRO DE RECURSOS
			$this->db->executeTested($this->db->getSQLDelete($model[$tb],"WHERE fk_ocasional_id={$this->post['ocasional_id']}"));
			// INGRESAR REGISTROS DE RECURSOS
			foreach($data as $v){
				$sql=$this->db->executeTested($this->db->getSQLInsert($v,$model[$tb]));
			}
		}
		// ACTUALIZAR REGISTROS DE OCASIONAL
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZAR REGISTRO DE REPONSABLES
	 */
	public function updateAgents(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// RECORRER LISTADO DE RESPONSABLES
		foreach($this->post['model'] as $v){
			// INGRESAR/ACTUALIZAR REGISTRO DE REPONSABLE
			$person=$this->requestPerson($v);
			// MODELO DE OCASIONALES PARA INGRESO/ACTUALIZACIÓN DE RESPONSABLE
			$occasional=array(
				'ocasional_id'=>$v['ocasional_id'],
				$v['fk']=>$person['persona_id']
			);
			// CONSULTA PARA INGRESO/ACTUALIZACIÓN DE RESPONSABLE
			$sql=$this->db->executeTested($this->db->getSQLUpdate($occasional,$this->entity));
		}
		// RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR POF POR ID
	 * -> modalResources
	 * -> modalAmenazas
	 * -> modalInternos
	 * -> modalExternos
	 */
	private function requestResourcesById($id,$types){
		// TIPOS DE RECURSOS
		$tb=$this->config['referencesList'];
		// MODELO DE RECURSOS
		$model=$this->config['resourcesModel'];
		// MODELO DE RECURSOS
		$srcList=array();
		// RECORRER EL LISTADO DE RECURSOS
		foreach($types as $type){
			// BUSCAR RECURSOS DE LA LISTA
			$src=$this->db->findAll($this->db->selectFromView('recursosocasionales',"WHERE recurso_estado='ACTIVO' AND UPPER(recurso_tipo)=UPPER('{$type}') ORDER BY recurso_nombre"));
			foreach($src as $v){
				$aux=$model[$type];
				// INSERTAR MODELO DE RECURSOS
				$aux['fk_ocasional_id']=$id;
				$aux['recurso_nombre']=$v['recurso_nombre'];
				$aux['fk_recurso_id']=$v['recurso_id'];
				
				// VALIDAR RECURSOS POR DEFECTO
				if(isset($this->config['defaultResources'][$v['recurso_id']])) $aux=array_merge($aux,$this->config['defaultResources'][$v['recurso_id']]);
				
				$srcList[$type][$v['recurso_id']]=$aux;
			}
			// BUSCAR LISTADO DE REFERENCIAS
			$src=$this->db->findAll($this->db->getSQLSelect($tb[$type],['recursosocasionales'],"WHERE fk_ocasional_id=$id ORDER BY recurso_nombre"));
			foreach($src as $v){$srcList[$type][$v['recurso_id']]=$v;}
		}
		// RETORNAR DATOS
		return $srcList;
	}
	
	/*
	 * CONSULTAR RESPONSABLES DE POF POR ID
	 */
	private function requestResponsibleById($id){
		// REGISTRO
		$json=$this->db->findById($id,$this->entity);
		// REPRESENTANTES DEL COMITE
		$json['agents']=array();
		foreach($this->config['agents'] as $k=>$v){
			// MODELO POR DEFECTO DE RESPONSABLE
			$agent=$this->config['agenModel'];
			// BUSCAR REPONSABLE SI HA SIDO SETTEADO
			if(isset($json[$v['fk']]) && $json[$v['fk']]>0) $agent=$this->db->findById($json[$v['fk']],'personas');
			// ADJUNTAR ID DE REGISTRO DE OCASIONAL
			$agent['ocasional_id']=$id;
			$json['agents'][$k]=array_merge($v,$agent);
		} return $json;
	}
	
	/*
	 * CONSULTAR POF POR CÓDIGO
	 * -> requestOccasional
	 */
	private function requestByCode($code){
		// GENERAR CONSULTA POR CÓDIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(ocasional_codigo)=UPPER('{$code}')");
		// VERIFICAR SI EXISTE EL POF
		if($this->db->numRows($str)<1) $this->getJSON("La solicitud <b>#{$code}</b> no ha sido registrada, favor verifiqe su código y vuelva a intentar!");
		// OBTENER DATOS DEL PERMISO
		$pof=$this->db->findOne($str);
		// VERIFICAR EL ESTADO DEL POF
		if($pof['ocasional_estado']=='INGRESADO'){
			// VALIDAR PERMISO PARA APROBAR PERMISO Y GENERAR ORDEN DE COBRO
			if(!in_array(6092,$this->getAccesRol())) $this->getJSON("La solicitud <b>#{$code}</b> ya ha sido ingresada, por favor direccione el trámite con quien corresponda!");
			
			// VALIDAR SI PROCEDE A PAGO
			$payment=$this->validateEntityExemptPayment($pof['fk_entidad_id'],$this->entity,$pof['ocasional_id']);
			// VALIDAR ROL PARA APROBAR TRAMITES SIN PAGO
			if($payment['estado']=='info') $this->getJSON((!in_array(60921,$this->getAccesRol()))?$this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE']:$payment);
			
			// DATOS PARA GENERAR ORDEN DE COBRO
			$pof['entity']='ocasionales';
			$pof['entityId']=$pof['ocasional_id'];
			// MODAL A DESPLEGAR
			$pof['modal']='Generarordenescobro';
			$pof['edit']=false;
		}elseif($pof['ocasional_estado']=='PENDIENTE'){
			$pof['ocasional_estado']='INGRESADO';
			// MODAL A DESPLEGAR
			$pof['modal']='Input';
			$pof['tb']=$this->entity;
			$pof['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
		} else $this->getJSON("El Permiso Ocasional <b>#{$code}</b> ya ha sido aprobado!");
		// RETORNAR DATOS
		return $pof;
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestById($id){
		// OBTENER DATOS DE PROYECTO
		$json=$this->db->viewById($id,$this->entity);
		// REPRESENTANTES DEL COMITE
		$json['agents']=$this->requestResponsibleById($id)['agents'];
		// LISTADO DE AMENAZAS
		$json['threats']=$this->db->findAll($this->db->getSQLSelect('plan_has_amenazas',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
		// LISTADO DE RECURSOS INTERNOS
		$json['internal']=$this->db->findAll($this->db->getSQLSelect('plan_has_rinternos',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
		// LISTADO DE RECURSOS EXTERNOS
		$json['external']=$this->db->findAll($this->db->getSQLSelect('plan_has_rexternos',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
		// LISTADO DE DOCUMENTOS
		$json['documents']=$this->db->findAll($this->db->getSQLSelect('ocasional_has_documentos',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}
	
	/*
	 * CONSULTAR PRESENTACIONES
	 */
	public function requestOccasional(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR FECHA - NUEVO REGISTRO
		elseif(isset($this->post['date'])) $json=$this->requestByDate($this->post['date']);
		// CONSULTAR POR CÓDIGO - INGRESO EN CBSD
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTAR LOS RECURSOS DEL PLAN DE CONTINGENCIA ASOCIADO AL POF
		elseif(isset($this->post['type'])) $json=$this->requestResourcesById($this->post['occasionalId'],$this->post['type']);
		// LISTADO DE LAYERS
		elseif(isset($this->post['geoJSON'])) $json=$this->getGeoJSON($this->entity,$this->post['geoJSON']);
		// CONSULTAR LOS RESPONSABLES DE LA ELABORACIÓN DEL PLAN DE CONTIGENCIA
		elseif(isset($this->post['agents'])) $json=$this->requestResponsibleById($this->post['occasionalId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * REPORTE REGISTROS INCOMPLETOS
	 */
	private function testCustomReport($pdfCtrl,$user,$row){
		$msg="";
		if($row['amenazas']<1) $msg.="<li>IDENTIFICAR LOS FACTORES DE RIESGOS PROPIOS DE LA ORGANIZACIÓN del plan de contingencia</li>";
		if($row['internos']<1) $msg.="<li>IDENTIFICAR LOS RECURSOS INTERNOS del plan de contingencia</li>";
		if($row['externos']<1) $msg.="<li>IDENTIFICAR LOS RECURSOS EXTERNOS del plan de contingencia</li>";
		if($row['presidente']=="") $msg.="<li>Datos del presidente del comité del plan de contingencia</li>";
		if($row['fk_primerosauxilios']==null) $msg.="<li>Datos del Coordinador General del plan de contingencia</li>";
		if($row['fk_encargadobusqueda']==null) $msg.="<li>Datos del Encargado de Búsqueda, Rescate y Evacuación del plan de contingencia</li>";
		if($row['fk_primerosauxilios']==null) $msg.="<li>Datos del encargado de Primeros Auxilios del plan de contingencia</li>";
		// VALIDAR MENSAJE
		if($msg=="") return true;
		// RETORNAR CONSULTA
		return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * IMPRESION DE FECHA DE EVENTO
	 */
	private function getEventDate($row){
		
		// FECHA DEL EVENTO
		$initDate=$this->setFormatDate($row['ocasional_fecha_inicio'],'complete');
		$finalDate=$this->setFormatDate($row['ocasional_fecha_cierre'],'complete');
		$initTime=$this->setFormatDate($row['ocasional_fecha_inicio'],'H:i');
		$finalTime=$this->setFormatDate($row['ocasional_fecha_cierre'],'H:i');
		
		// MENSAJE POR DEFECTO
		$fecha=" desde la(s) {$initTime} del día {$initDate} hasta la(s) {$finalTime} el día {$finalDate}";
		
		// VALIDAR DATOS
		if($this->setFormatDate($row['ocasional_fecha_inicio'],'Y-m-d') == $this->setFormatDate($row['ocasional_fecha_cierre'],'Y-m-d')){
			
			// PARSE HORAS
			$timeFrom=$this->setFormatDate($row['ocasional_fecha_inicio'],'H:i');
			$timeTo=$this->setFormatDate($row['ocasional_fecha_cierre'],'H:i');
			// IMPRESION DE FECHAS
			$fecha=" desde la(s) {$timeFrom} hasta la(s) {$timeTo} del día {$initDate}";
			
		}
		
		// RETORNAR FECHAS DEL EVENTO
		return strtolower($fecha);
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// EXTRAER DATOS GET
		extract($this->requestGet());
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE ocasional_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testCustomReport($pdfCtrl,$row['presidente'],$row)) return '';
			
			// FECHA DEL EVENTO
			$row['ocasional_fecha_detail']=$this->getEventDate($row);
			
			// PARSE DE FECHAS
			$row['ocasional_fecha_inicio_alt']=$this->setFormatDate($row['ocasional_fecha_inicio'],'complete');
			$row['ocasional_fecha_cierre_alt']=$this->setFormatDate($row['ocasional_fecha_cierre'],'complete');
			$row['ocasional_hora_inicio_alt']=$this->setFormatDate($row['ocasional_fecha_inicio'],'H:i:s');
			$row['ocasional_hora_cierre_alt']=$this->setFormatDate($row['ocasional_fecha_cierre'],'H:i:s');
				
			$row['ocasional_lucro']=($row['ocasional_finesdelucro']=='SI'?'CON':'SIN').' FINES DE LUCRO';
			// LUGAR DEL ESTABLECIMIENTO
			if($row['ocasional_lugar']=='ESTABLECIMIENTO'){
				$permiso=$this->db->viewById($row['fk_permiso_id'],'permisos');
				$row['ocasional_lugar']="{$permiso['local_nombrecomercial']} <br> PERMISO # <b>{$permiso['codigo_per']}</b>";
			}elseif($row['ocasional_lugar']=='OTRO'){
				$row['ocasional_lugar']=$row['ocasional_nombrelugar'];
			}
			// LISTADO DE DOCUMENTOS
			$documents=$this->db->findAll($this->db->getSQLSelect('ocasional_has_documentos',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
			$row['tbDocuments']=(count($documents)<1)?"<tr><td colspan=2 class='text-center isa_error'>{$this->varGlobal['MSG_EMPTY_REPORT']}</td></tr>":"";
			foreach($documents as $key=>$val){
				$auxDetail=file_get_contents(REPORTS_PATH."ocasionalDocumentsList.html");
				$val['index']=$key+1;
				foreach($val as $k=>$v){
					$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);
				}	$row['tbDocuments'].=$auxDetail;
			}	$pdfCtrl->loadSetting($row);
			// LISTADO DE AMENAZAS
			$documents=$this->db->findAll($this->db->getSQLSelect('plan_has_amenazas',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
			$row['tbThreats']=(count($documents)<1)?"<tr><td colspan=4 class='text-center isa_error'>{$this->varGlobal['MSG_EMPTY_REPORT']}</td></tr>":"";
			foreach($documents as $key=>$val){
				$auxDetail=file_get_contents(REPORTS_PATH."ocasionalThreatsList.html");
				$val['index']=$key+1;
				foreach($val as $k=>$v){
					$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);
				}	$row['tbThreats'].=$auxDetail;
			}	$pdfCtrl->loadSetting($row);
			// LISTADO DE RECURSOS INTERNOS
			$documents=$this->db->findAll($this->db->getSQLSelect('plan_has_rinternos',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
			$row['tbInternal']=(count($documents)<1)?"<tr><td colspan=5 class='text-center isa_error'>{$this->varGlobal['MSG_EMPTY_REPORT']}</td></tr>":"";
			foreach($documents as $key=>$val){
				$auxDetail=file_get_contents(REPORTS_PATH."ocasionalInternalList.html");
				$val['index']=$key+1;
				foreach($val as $k=>$v){
					$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);
				}	$row['tbInternal'].=$auxDetail;
			}	$pdfCtrl->loadSetting($row);
			// LISTADO DE RECURSOS EXTERNOS
			$documents=$this->db->findAll($this->db->getSQLSelect('plan_has_rexternos',['recursosocasionales'],"WHERE fk_ocasional_id=$id"));
			$row['tbExternal']=(count($documents)<1)?"<tr><td colspan=4 class='text-center isa_error'>{$this->varGlobal['MSG_EMPTY_REPORT']}</td></tr>":"";
			foreach($documents as $key=>$val){
				$auxDetail=file_get_contents(REPORTS_PATH."ocasionalExternalList.html");
				$val['index']=$key+1;
				foreach($val as $k=>$v){
					$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);
				}	$row['tbExternal'].=$auxDetail;
			}
			// CARGAR DATOS DE RESPONSABLE
			$pdfCtrl->loadSetting($row);
			
			// CARGAR DATOS DE RESPONSBALES
			$pdfCtrl->loadSetting($this->db->findOne($this->db->selectFromView('vw_ocasionales_comite',"WHERE ocasional_id={$row['ocasional_id']}")));
				
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['ocasional_codigo']);
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
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE ocasional_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// EMPEZAR TRANSACCIÓN
			$this->db->begin();
			// RESPUESTA POR DEFECTO
			$sql=$this->setJSON("OPERACIÓN ABORTADA",true);
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// VALIDAR REGISTRO DE EMISION SIN PAGO
			if($row['ocasional_estado']=='INGRESADO' && isset($this->get['exemptPayment'])){
				// VALIDAR PERMISO PARA APROBAR TRÁMITE SIN PAGO
				if(!in_array(60921,$this->getAccesRol())){ 
					return $pdfCtrl->printCustomReport($this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE'],$this->getProfile()['usuario_login'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
				}
				// REGISTRAR SOLICITUD SIN PAGO
				$this->insertExemptPayment($row['fk_entidad_id'],$this->entity,$id);
				// ACTUALIZAR DATOS DE APROBACIÓN
				$row['ocasional_estado']='APROBADO';
			}
			
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['ocasional_estado'],['PENDIENTE','INGRESADO'])){
				// MENSAJE DE PERMISO NO APROBADO
				return $pdfCtrl->printCustomReport('<li>El permiso ocasional de funcionamiento no ha sido aprobado</li>',$row['presidente'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
			}
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			if($row['ocasional_estado']=='PAGADO'){
				$row['ocasional_estado']='APROBADO';
				$row['ocasional_despachado']=$this->getFecha('dateTime');
				
				// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
				$row['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
				
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['ocasional_serie']==0) $row['ocasional_serie']=$this->db->getNextSerie($this->entity);
				$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO - VERIFICAR SI HA SIDO APROBADO SIN GENERAR ORDEN DE COBRO
			if($row['ocasional_estado']=='APROBADO'){
				// VARIABLE PARA ACTUALIZAR PERMISO
				$stateUpdate=false;
				// COMPROBAR SI LA FECHA HA SIDO INGRESADA
				if($row['ocasional_despachado']=='' || $row['ocasional_despachado']==null){
					// NOTIFICAR CAMBIOS
					$stateUpdate=true;
					// CAMBIOS
					$row['ocasional_despachado']=$this->getFecha('dateTime');
				}
				
				// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
				$row['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
				
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['ocasional_serie']==0){
					// NOTIFICAR CAMBIOS
					$stateUpdate=true;
					// CAMBIOS
					$row['ocasional_serie']=$this->db->getNextSerie($this->entity);
				}
				// VALIDAR SI ES NECESARIA LA ACTUALIZACIÓN
				if($stateUpdate) $sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}
			
			// FECHA DEL EVENTO
			$row['ocasional_fecha_detail']=$this->getEventDate($row);
			
			// FECHA DE EVENTO
			$row['ocasional_fecha_inicio_alt']=$this->setFormatDate($row['ocasional_fecha_inicio'],'complete');
			$row['ocasional_fecha_cierre_alt']=$this->setFormatDate($row['ocasional_fecha_cierre'],'complete');
			// HORARIO DE EVENTO
			$row['ocasional_hora_inicio_alt']=$this->setFormatDate($row['ocasional_fecha_inicio'],'H:i:s');
			$row['ocasional_hora_cierre_alt']=$this->setFormatDate($row['ocasional_fecha_cierre'],'H:i:s');
			// PARÁMETROS	
			$row['ocasional_despachado']=$this->setFormatDate($row['ocasional_despachado'],'full');
			$row['ocasional_anio']=$this->setFormatDate($row['ocasional_despachado'],'Y');
			$row['ocasional_lucro']=($row['ocasional_finesdelucro']=='SI'?'CON':'SIN').' FINES DE LUCRO';
			// NUMERO DE PERMISO
			$row['ocasional_serie']=str_pad($row['ocasional_serie'],4,"0",STR_PAD_LEFT);
			// CARGAR DATOS DE OCASIONAL
			$pdfCtrl->loadSetting($row);
			
			// CARGAR DATOS DE RESPONSBALES
			$pdfCtrl->loadSetting($this->db->findOne($this->db->selectFromView('vw_ocasionales_comite',"WHERE ocasional_id={$row['ocasional_id']}")));
			
			// HORA DE INGRESO A CBSD
			$str="SELECT ocasional_registro fecha_ingreso FROM records.tb_ocasionales WHERE ocasional_id={$id} AND ocasional_estado='INGRESADO' ORDER BY ocasional_registro limit 1";
			$record=$this->db->findOne($str);
			$record['fecha_ingreso']=$this->setFormatDate($record['fecha_ingreso'],'full');
			$pdfCtrl->loadSetting($record);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['ocasional_codigo']);
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