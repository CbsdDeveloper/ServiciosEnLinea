<?php namespace model\prevention;
use api as app;
use model as mdl;

class glptModel extends mdl\vehicleModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='transporteglp';
	private $entityId;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
		// INGRESAR LA REFERENCIA DE QUIEN ESTÁ LOGUEADO
		$this->entityId=app\session::get();
	}
	
	/*
	 * VALIDAR QUE EL NUMERO PERMISO DE FUNCIONAMIENTO INGRESADO SE RELACIONA DIRECTAMENTE CON LOS
	 * DATOS DEL RUC INGRESADO
	 */
	private function validatePermit($code){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_permisos","WHERE UPPER(codigo_per)=UPPER('{$code}') AND date_part('year',permiso_fecha)=date_part('year',CURRENT_DATE)");
		// VALIDAR PEMRISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)<1) $this->getJSON("El <b>permiso de funcionamiento #{$code}</b> no se encuentra registrado dentro del <b>año calendario {$this->getCurrentYear('Y')}</b>!");
		// DATOS DE PERMISO
		$permit=$this->db->findOne($str);
		// VALIDAR QUE EL PERMISO SEA DE LA MISMA ENTIDAD
		if($this->db->numRows($this->db->selectFromView("locales","WHERE local_id={$permit['local_id']} AND fk_entidad_id={$permit['entidad_id']}"))<1) $this->getJSON("El <b>permiso de funcionamiento #{$code}</b> no se encuentra relacionado con el su cuenta de RUC.");
		// RETORNAR DATOS DE PERMISO
		return $permit;
	}
	
	/* FROM: ai
	 * INGRESAR NUEVO REGISTRO
	 * * VALIDAR PERMISO DE FUNCIONAMIENTO
	 */ 
	public function insertTransportGlp(){
		// VALIDAR Y OBTENER DATOS DE PERMISO
		$permit=$this->validatePermit($this->post['codigo_per']);
		// RELACIONAR PERMISO DE GLP CON PERMISO DE FUNCIONAMIENTO
		$this->post['fk_permiso_id']=$permit['permiso_id'];
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// ELIMINAR ATRIBUTOS BASURA
		if(isset($this->post['transporte_id'])) unset($this->post['transporte_id']);
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
		$this->post['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
		// INGRESAR REGISTRO DE VEHÍCULO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO 
		$sql['data']=array('id'=>$this->db->getLastID($this->entity));
		// CIERRE DE TRANSACCIÓN
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * INSPECTORES
	 */
	private function setPersonal($processId,$list){
		// VALIDAR LONGITUD DE LISTADO DE PERSONAL
		if(count($list)>0){
			// ELIMINAR PERSONAL ASIGNADO
			$this->db->executeTested($this->db->getSQLDelete('tglp_inspector',"WHERE fk_tglp_id={$processId}"));
		}
		// RECORRER LISTA DE PERSONAL
		foreach($list as $personalId){
			// GENERAR MODELO TEMPORAL
			$aux=array(
				'fk_tglp_id'=>$processId,
				'fk_personal_id'=>$personalId
			);
			// INGRESAR PERSONAL PARA REVISIÓN
			$this->db->executeTested($this->db->getSQLInsert($aux,'tglp_inspector'));
		}
	}
	
	/*
	 * ACTUALIZACIÓN DE PROCESO PARA PERMISO DE TRANSPORTE DE GLP
	 * - asignación de inspector
	 * - cambios en registro de vehículo y propietario
	 */ 
	public function updateTransportGlp(){
		// VALIDAR Y OBTENER DATOS DE PERMISO
		$permit=$this->validatePermit($this->post['codigo_per']);
		// RELACIONAR PERMISO DE GLP CON PERMISO DE FUNCIONAMIENTO
		$this->post['fk_permiso_id']=$permit['permiso_id'];
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		
		// REGISTRAR EL INGRESO DE PERSONAL PARA REVISIÓN
		if(isset($this->post['asignInspector']) && $this->post['asignInspector']){
			// INVOCAR MÉTODO PARA REGISTRO DE PERSONAL
			$this->setPersonal($this->post['transporte_id'],$this->post['selected']);
		}
		
		// INGRESAR REGISTRO DE VEHÍCULO
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		$json['data']=array('id'=>$this->post['transporte_id']);
		
		// CIERRE DE TRANSACCIÓN
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	
	/*
	 * CONSULTA LISTADO DE RECURSOS PARA REVISIÓN VEHÍCULO DE TRANSPORTE DE GLP
	 * HACE USO DEL MÉTODO: getResourcesGLPT
	 */
	private function resourcesById($transportId){
		// DATOS DE ENTIDAD
		$data=$this->db->viewById($transportId,$this->entity);
		// DATOS DE PERMISO RELACIONADO
		$data=array_merge($data,$this->db->viewById($data['fk_permiso_id'],'permisos'));
		// RECURSOS PARA PERMIOSO DE TRANSPORTE DE GLP
		$data['model']=array();
		$data['resources']=array();
		// LISTA DE RECURSOS
		foreach($this->db->findAll($this->db->selectFromView('recursos',"WHERE transporteglp='SI' AND recurso_estado='{$this->varGlobal['STATUS_ACTIVE']}'")) as $v){
			// LISTA DE REQUISITOS
			$data['resources'][$v['recurso_id']]=$v;
			// CARGAR REQUISITO PARA REVISION
			$data['model'][$v['recurso_id']]=array(
				'fk_transporte_id'=>$transportId,
				'fk_recurso_id'=>$v['recurso_id'],
				'recurso_estado'=>'SI'
			);
		}
		// SENTENCIA PARA CONSULTAR SI YA EXISTE LA REVISIÓN DEL AÑO EN CURSO
		$str=$this->db->selectFromView('transporte_has_recursos',"WHERE fk_transporte_id=$transportId AND (date_part('year',recurso_registro)=date_part('year',CURRENT_DATE))");
		// CONSULTAR SI EXISTEN RECURSOS DE UNA REVISIÓN PREVIA
		if($this->db->numRows($str)>0){
			foreach($this->db->findAll($str) as $v){
				$data['model'][$v['fk_recurso_id']]=$v;
			}
		}
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTRO POR CÓDIGO PER
	 */
	private function requestByCode($code){
		// BUSCAR SI EXISTE EL REGISTRO
		$str=$this->db->selectFromView($this->entity,"WHERE UPPER(transporte_codigo)=UPPER('$code')");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO PARA GENERAR REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("La solicitud <b>#{$code}</b> no se encuentra registrada en el sistema!");
		// SI EXISTE EL REGISTRO PRESENTAR LA INFORMACION
		$row=$this->db->findOne($str);
		// VALIDAR ESTADOS DE PERMISO
		if(in_array($row['transporte_estado'],['PENDIENTE','INGRESADO','ASIGNADO','REVISADO','ENVIADO A CORRECCION'])){
			// DATOS DE VEHICULO
			$row=array_merge($row,$this->db->viewById($row['fk_permiso_id'],'permisos'));
			// DATOS DE VEHICULO
			$row=array_merge($row,$this->db->viewById($row['fk_vehiculo_id'],'vehiculos'));
			// TRAMITE PENDIENTE
			if($row['transporte_estado']=='PENDIENTE'){
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['transporte_estado']='INGRESADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
				$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
			// TRAMITE PARA ASIGNAR A INSPECTOR
			}elseif($row['transporte_estado']=='INGRESADO'){
			    // PERMISO EN BASE DE DATOS PARA APROBAR UNA SOLICITUD VENCIDA
			    if(!in_array(6052,$this->getAccesRol())) $this->getJSON("No cuenta con el permiso para asignar al personal para la inspección del vehículo...");
			    // EDICIÓN DE REGISTRO PARA MODAL
			    $row['transporte_estado']='ASIGNADO';
			    // MODAL A DESPLEGAR
			    $row['modal']='transporteglpinspector';
			    $row['tb']=$this->entity;
			}elseif(in_array($row['transporte_estado'],['ASIGNADO','REVISADO','ENVIADO A CORRECCION'])){
			    // PERMISO EN BASE DE DATOS PARA APROBAR UNA SOLICITUD VENCIDA
			    if(!in_array(6053,$this->getAccesRol())) $this->getJSON("No cuenta con el permiso para registar la inspección del vehículo...");
			    // DATOS PARA REDIRECCION
			    $row['goUI']=array('ui'=>'prevention.glp.reviewTransport','data'=>array('transportId'=>$row['transporte_id']));
			}
			// DATOS DE RETORNO
			return $row;
		}
		// MENSAJE: ACCION NO PERMITIDA
		$this->getJSON("La solicitud <b>#{$code}</b> ya ha sido ingresada anteriormente!");
	}
	
	/*
	 * LISTA DE INSPECTORES DE UNA INSPECCION
	 */
	private function requestInspectorsById($glptId){
		// MODELO DE DATOS
		$data=$this->db->viewById($glptId,$this->entity);
		// VALIDAR TRANSICIÓN DE ETADOS
		if($data['transporte_estado']=='INGRESADO') $data['transporte_estado']='ASIGNADO';
		// OBJETO PARA PERSONAL SELECCIONADO
		$data['tb']=$this->entity;
		$data['asignInspector']=true;
		$data['selected']=array();
		// ID DE USUARIOS COMO INSPECTORES
		$inspectorsList=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE fk_perfil_id=6 AND usuario_estado='ACTIVO'");
		// LISTA DE INSPECTORES
		$data['list']=$this->db->findAll($this->db->selectFromView('vw_personal',"WHERE fk_persona_id IN ($inspectorsList) AND ppersonal_estado='EN FUNCIONES'"));
		
		// INSPECTORES YA INGRESADOS
		foreach($this->db->findAll($this->db->selectFromView('tglp_inspector',"WHERE fk_tglp_id={$glptId}")) AS $v){
			// VALIDAR SI YA SE HA REGISTRADO EL INSPECTOR
			if(!in_array($v['fk_personal_id'],$data['selected'])) $data['selected'][]=$v['fk_personal_id'];
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 */
	private function requestById($id){
		// REGISTRO POR ID
		$row=$this->db->viewById($id,$this->entity);
		// INSERTAR DATOS DE PERMISO DE FUNCIONAMIENTO
		$row=array_merge($row,$this->db->viewById($row['fk_permiso_id'],'permisos'));
		// GALERIA RELACIONADA
		$row['gallery']=$this->db->findAll($this->db->selectFromView('vw_gallery',"WHERE fk_table='{$this->entity}' AND fk_id={$row['transporte_id']}"));
		// RETORNAR DATOS
		return $row;
	}
	
	/*
	 * CONSULTA REGISTRO DE VEHÍCULO CON PERMISO DE FUNCIONAMIENTO
	 * SI EXISTE EN UI SE DESPLEGARÁ EL MODAL PARA HACER EL INGRESO DE PROYECTO EN CBSD
	 * CASO CONTRARIO SE EMITIRÁ UN MENSAJE DE ALERTA
	 */
	public function requestEntity(){
		// CONSULTAR POR ID - INGRESO EN CBSD
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO - INGRESO EN CBSD
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// INSPECTORES POR ID
		elseif(isset($this->post['type']) && $this->post['type']=='getInspectors') $json=$this->requestInspectorsById($this->post['transporte_id']);
		// CONSULTAR POR ID - INGRESO EN CBSD
		elseif(isset($this->post['reviewId'])) $json=$this->resourcesById($this->post['reviewId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("ok",true,$json));
	}
	
	
	/*
	 * INGRESAR DATOS DE REVISIÓN TRANSPORTE GLP
	 * * SI EXISTE ELIMINA LOS ANTERIORES E INGRESA NUEVOS REGISTROS
	 * * SINO, INGRESA LOS REGISTROS
	 */
	public function saveResources(){
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// DEFINIR ENTIDAD PARA REGISTRO DE RECURSOS
		$tb='transporte_has_recursos';
		// STRING PARA ELIMINAR RECURSOS REGISTRADOS ANTERIORMENTE Y EJECUTAR SENTENCIA
		$this->db->executeTested($this->db->getSQLDelete($tb,"WHERE fk_transporte_id={$this->post['transporte_id']} AND (date_part('year',recurso_registro)=date_part('year',CURRENT_DATE))"));
		
		// VARIABLES PARA VALIDACIÓN
		$msg="";
		$status=true;
		$src=$this->post['resources'];
		
		// EVALUAR RECURSOS
		foreach($this->post['model'] as $k=>$v){
			// VALIDAR LOS REQUERIMIENTOS QUE FALTAN
			if($v['recurso_estado']=='NO'){
				$status=false;
				$msg.="No cumple con {$src[$k]['recurso_nombre']} en orden.\n";
			}
			// GENERAR STRING DE REGISTRO Y EJECUTAR CONSULTA
			$this->db->executeTested($this->db->getSQLInsert($v,$tb));
		}	
		
		// OBTENER DATOS DE INSPECCION DE VEHICULO
		$row=$this->db->findById($this->post['transporte_id'],$this->entity);
		// ACTUALIZAR DATOS DE INSPECCION
		$row['transporte_aprobado']=$this->getFecha('dateTime');
		$row['transporte_observacion']=($status?"Inspección {$this->getCurrentYear()} aprobada.":$msg);
		$row['transporte_estado']=$status?"APROBADO":"ENVIADO A CORRECCION";
		// ACTUALIZAR DATOS DE INSPECCION DE VEHICULO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
		// INSERTAR DATOS DE CONSULTA
		$sql['data']=array('id'=>$this->post['transporte_id'],'status'=>$status);
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * REPORTE REGISTROS INCOMPLETOS
	 */
	private function testCustomReport($pdfCtrl,$user,$row){
		$msg="";
		if(strlen($row['vehiculo_motor'])<1) $msg.="<li>COMPLETAR EL NUMERO DE CHASIS DEL VEHÍCULO</li>";
		if(strlen($row['vehiculo_chasis'])<1) $msg.="<li>COMPLETAR EL NUMERO DE MOTOR DEL VEHÍCULO</li>";
		if(strlen($row['vehiculo_color2'])<1) $msg.="<li>COMPLETAR EL COLOR DEL VEHÍCULO</li>";
		if(strlen($row['vehiculo_anio'])<1) $msg.="<li>COMPLETAR EL AÑO DEL VEHÍCULO</li>";
		// PLANTILLA PARA REPORTE NO ENCONTRADO
		$pdfCtrl->loadSetting(array('listEmptyReport'=>$msg,'usuario'=>$user));
		$pdfCtrl->status=$msg;
		$pdfCtrl->template='emptyReport';
		return $pdfCtrl;
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 * -- SOLICITUD DE PERMISO DE TRANSPORTE DE GLP
	 */
	public function printDetail($pdfCtrl,$config){
		// RECIBIR DATOS GET - URL
		$get=$this->requestGet();
		// STRING DE CONSULTA PARA REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE transporte_id={$get['id']}");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DE PERMISO
			$row=$this->db->findOne($str);
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			$pdfCtrl=$this->testCustomReport($pdfCtrl,$row['propietario'],$row);
			if($pdfCtrl->status!="") return '';
			// CARGAR DATOS A REPORTE
			$pdfCtrl->loadSetting($row);
			// DATOS DE VEHICULO
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_vehiculo_id'],'vehiculos'));
			// DATOS DE PERMISO DE FUNCIONAMIENTO
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_permiso_id'],'permisos'));
			// LISTADO DE PERSONAS EN CAPACITACIÓN
			$row['tbResources']="";
			foreach($this->db->findAll($this->db->selectFromView('recursos',"WHERE transporteglp='SI' AND recurso_estado='{$this->varGlobal['STATUS_ACTIVE']}'")) as $key=>$val){
				// LISTADO DE PERSONAL EN CAPACITACION
				$auxDetail=file_get_contents(REPORTS_PATH."resourcesByTGLP.html");
				$val['index']=$key+1;
				foreach($val as $k=>$v){$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);}
				$row['tbResources'].=$auxDetail;
			}
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['transporte_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe la solicitud</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
	
	/*
	 * LISTA DE PERSONAL ASIGNADO PARA INSPECCIÓN Y REVISIÓN
	 */
	private function getInspectorsTemplate($processId){
		// GENERAR STRING DE CONSULTA
		$strInspectors=$this->db->selectFromView($this->db->setCustomTable('tglp_inspector','fk_personal_id'),"WHERE fk_tglp_id={$processId}");
		// PLANTILLA
		$template="";
		// BUSCAR INSPECTORES Y PARSEAR DATOS
		foreach($this->db->findAll($this->db->selectFromView('vw_personal',"WHERE ppersonal_id IN ({$strInspectors})")) AS $key=>$val){
			// INDEX DE FILA
			$val['index']=$key+1;
			// FILA PARA INSERTAR PERSONAL
			$template.="{$val['index']}. {$val['personal_nombre']}<br>";
		}
		// RETORNAR PLANTILLA DE PROFESIONALES
		return $template;
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// VALIDAR PERMISO -- 6054
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE transporte_id=$id");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			// DATOS DE ENTIDAD
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_entidad_id'],'entidades'));
			// VALIDAR QUE EL PERMISO HAYA SIDO APROBADO
			if($row['transporte_estado']!='APROBADO'){
				// PLANTILLA PARA REPORTE NO ENCONTRADO
				$pdfCtrl->loadSetting(array('listEmptyReport'=>"<li>No se puede imprimir el Permiso #{$row['transporte_codigo']}, este no ha sido revisado por un Técnico de Inspección.</li>",'usuario'=>'usuario'));
				$pdfCtrl->template='emptyReport';
				return '';
			}
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			if($row['transporte_serie']==0){
				
				// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
				$row['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
				
				$row['transporte_serie']=$this->db->getNextSerie($this->entity);
				$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}
			$row['transporte_serie']=str_pad($row['transporte_serie'],4,"0",STR_PAD_LEFT);
			$cilndros=number_format(($row['vehiculo_toneladas']*1000)/(15 * 2.2), 0, '.', '');
			$m3=number_format(($cilndros*36)/1000, 2, '.', '');
			$row['transporte_tonelaje']="$cilndros CILINDROS DE GLP (15KG) CUYO VOLUMEN MÁXIMO REPRESENTA $m3 m3";
			// FORMATO DE FECHAS
			$row['permiso_caduca']=$this->setFormatDate($row['transporte_aprobado'],'Y');
			$row['transporte_aprobado']=$this->setFormatDate($row['transporte_aprobado'],'full');
			// LISTADO DE INSPECTORES
			$row['inspectorList']=$this->getInspectorsTemplate($id);
			// DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['transporte_codigo'],'none','64px','100%');
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity,'permit');
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
		
		
	}
	
}