<?php namespace model\prevention;
use api as app;

class habitabilityModel extends vbpModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='habitabilidad';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * CONSULTAR PROYECTO POR CODIGO
	 */
	private function requestByCode($code){
		// STRING DE CONSULTA POR CÓDIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(habitabilidad_codigo)=UPPER('{$code}')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// OBTENER REGISTRO DE VBP
			$vbp=$this->db->findById($row['fk_vbp_id'],'vbp');
			// VALIDAR SI EL VBP HA SIDO APROBADO
			if($vbp['vbp_estado']<>'APROBADO') $this->getJSON("Favor, tomar contacto con la Unidad de Prevención; solicitar la validación del Visto Bueno de Planos <b>{$vbp['vbp_codigo']}</b>");
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO INCIADO
			if(in_array($row['habitabilidad_estado'],['PENDIENTE'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6132,$this->getAccesRol())) $this->getJSON($this->varGlobal['BAD_INPUT_REQUEST_ROL']);
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['habitabilidad_estado']='INGRESADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
				$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
			}elseif(in_array($row['habitabilidad_estado'],['INGRESADO'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6133,$this->getAccesRol())) $this->getJSON("No tiene permisos para asignar personal para la revisión!");
				// GENERAR DATOS PARA ASIGNAR UN INSPECTOR
				$row['habitabilidad_estado']='ASIGNADO';
				// MODAL A DESPLEGAR
				$row['modal']='habitabilidadinspector';
				$row['tb']=$this->entity;
			}elseif(in_array($row['habitabilidad_estado'],['ASIGNADO','REVISION','CORRECCION'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6134,$this->getAccesRol())) $this->getJSON("No tiene permisos para registrar la revisión de este proyecto!");
				// MODAL A DESPLEGAR
				$row['modal']='Vbp';
				$row['tb']=$this->entity;
			}elseif(in_array($row['habitabilidad_estado'],['REVISADO'])){
				// VALIDAR PERMISO PARA GENERAR ORDEN DE COBRO
				if(!in_array(6135,$this->getAccesRol())) $this->getJSON("No cuenta con los privilegios para generar la orden de cobro de la solicitud <b>#$code</b>, por favor direccione el trámite con quien corresponda para que genere la orden de cobro!");
				
				// VALIDAR SI PROCEDE A PAGO
				$payment=$this->validateEntityExemptPayment($row['fk_entidad_id'],$this->entity,$row['habitabilidad_id']);
				// VALIDAR ROL PARA APROBAR TRAMITES SIN PAGO
				if($payment['estado']=='info') $this->getJSON((!in_array(61351,$this->getAccesRol()))?$this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE']:$payment);
				
				// DATOS PARA GENERAR ORDEN DE COBRO
				$row['entity']=$this->entity;
				$row['entityId']=$row['habitabilidad_id'];
				// MODAL A DESPLEGAR
				$row['modal']='Generarordenescobro';
				$row['edit']=false;
			}elseif(in_array($row['habitabilidad_estado'],['ORDEN DE COBRO GENERADA'])){
				$this->getJSON("La solicitud <b>#$code</b> de PERMISO DE OCUPACIÓN Y HABITABILIDAD se encuentra pendiente de pago!");
			}elseif(in_array($row['vbp_estado'],['PAGADO'])){
				$this->getJSON("La solicitud <b>#$code</b> de PERMISO DE OCUPACIÓN Y HABITABILIDAD ya se encuentra pagada, por favor dirija el trámite a quien corresponda autorizar el respectivo permiso!");
			}elseif(in_array($row['habitabilidad_estado'],['APROBADO'])){
				$this->getJSON("La solicitud <b>#$code</b> de PERMISO DE OCUPACIÓN Y HABITABILIDAD ya ha sido revisado y aprobado!");
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
		
	}
	
	/*
	 * CONSULTA DE PROYECTOS
	 */ 
	public function requestEntity(){
		// CONSULTAR POR ID
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// INSPECTORES POR ID
		elseif(isset($this->post['type']) && $this->post['type']=='getInspectors') $json=$this->getInspectorsByCode($this->post['habitabilidad_id'],$this->entity);
		// LISTADO DE LAYERS
		elseif(isset($this->post['geoJSON'])) $json=$this->getGeoJSON($this->entity,$this->post['geoJSON']);
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
		// VALIDAR SOLICITUD DE REGISTRO
		if(isset($this->post['vbp_estado']) && $this->post['vbp_estado']=='APROBADO'){
			
		}else{
			// INGRESAR REGISTRO CON ENTIDAD
			$this->post['vbp_id']=$this->insertProject();
			// INSERTAR REGISTRO DE PROFESIONALES
			$sql=$this->insertProfessionals();
		}
		// INGRESAR REGISTRO CON ENTIDAD
		$this->post['fk_vbp_id']=$this->post['vbp_id'];
		
		// CONSULTAR REGISTRO DE VBP PARA OBTENER ID DE PROFESIONALES
		$vbp=$this->db->findById($this->post['vbp_id'],'vbp');
		// INSERTAR ID DE PROFESIONALES
		$this->post['habitabilidad_proyectista_id']=$vbp['proyectista_id'];
		$this->post['habitabilidad_responsable_mt_rpmci']=$vbp['responsable_mt_rpmci'];
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
		$this->post['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
		// VALIDAR DATOS DE FACTURACIÓN
		if($this->post['habitabilidad_facturacion']=='MISMA') $this->post['habitabilidad_facturacion_id']=$vbp['fk_entidad_id'];
		else{
			if(isset($this->post['billing']['entidad_id']) && $this->post['billing']['entidad_id']>0) $this->post['habitabilidad_facturacion_id']=$this->post['billing']['entidad_id'];
			else $this->getJSON("No se ha encontrado los datos para facturación, por favor asegúrese de ingresar bien los datos!.");
		}
		
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DEL NUEVO REGISTRO
		$sql['data']=array('id'=>$this->db->getLastID($this->entity));
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * EDICIÓN DE REGISTROS
	 */
	public function updateEntity(){
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDAR DATOS DE FACTURACIÓN
		if($this->post['habitabilidad_facturacion']=='MISMA') $this->post['habitabilidad_facturacion_id']=$this->post['fk_entidad_id'];
		else{
			if(isset($this->post['billing']['entidad_id']) && $this->post['billing']['entidad_id']>0) $this->post['habitabilidad_facturacion_id']=$this->post['billing']['entidad_id'];
			else $this->getJSON("No se ha encontrado los datos para facturación, por favor asegúrese de ingresar bien los datos!.");
		}
		
		// VALIDAR POSIBLES ESTADOS DE REGISTRO
		if($this->post['habitabilidad_estado']=='INGRESADO'){
			// VALIDAR NÚMERO DE SOLICITUD
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $this->post['habitabilidad_solicitud']=$this->testRequestNumber($this->entity,$this->post['habitabilidad_solicitud'],$this->post['habitabilidad_id']);
		}elseif($this->post['habitabilidad_estado']=='APROBADO'){
			
			// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
			$this->post['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
			
			// GENERAR NÚMERO DE SERIE
			if($this->post['habitabilidad_serie']<1) $this->post['habitabilidad_serie']=$this->db->getNextSerie($this->entity);
		}else{
			// REGISTRAR EL INGRESO DE PERSONAL PARA REVISIÓN
			if(isset($this->post['asignInspector']) && $this->post['asignInspector']){
				// INVOCAR MÉTODO PARA REGISTRO DE PERSONAL
				$this->setPersonal($this->entity,$this->post['habitabilidad_id'],$this->post['selected']);
			}
		}
		
		// ACTUALIZAR DATOS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// INSERATR ID DE REGISTRO
		$sql['data']=array('id'=>$this->post['habitabilidad_id']);
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE - SOLICITUD CBSD
	 */
	public function printDetail($pdfCtrl,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE habitabilidad_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			// NOMBRE DE LA PLANTILLA DE REPORTE
			$templateKey=in_array($row['entidad_contribuyente'],['publica','privada'])?'SOCIEDADES':'NATURALES';
			// ADJUNTAR DATOS DE DECLARACIÓN DE AUTOINSPECCIÓN - PRIMER PÁRRAFO
			$row['HABITABILIDAD_SOLICITUD']=$this->varGlobal["HABITABILIDAD_SOLICITUD_{$templateKey}"];
			// LISTA DE PROFESIONALES
			$row['TbProfessionals']=$this->completeReportProfesionals($row['fk_vbp_id']);
			// DATOS DE FACTURACIÓN
			$row['TbBilling']=$this->getBillingData($row,$row['habitabilidad_facturacion_id']);
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['habitabilidad_codigo']);
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
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE habitabilidad_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// EMPEZAR TRANSACCIÓN
			$this->db->begin();
			// RESPUESTA POR DEFECTO
			$sql=$this->setJSON("OPERACIÓN ABORTADA",true);
			// DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR REGISTRO DE EMISION SIN PAGO
			if($row['habitabilidad_estado']=='REVISADO' && isset($this->get['exemptPayment'])){
				// VALIDAR PERMISO PARA APROBAR TRÁMITE SIN PAGO
				if(!in_array(61351,$this->getAccesRol())){
					return $pdfCtrl->printCustomReport($this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE'],$this->getProfile()['usuario_login'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
				}
				// REGISTRAR SOLICITUD SIN PAGO
				$this->insertExemptPayment($row['fk_entidad_id'],$this->entity,$id);
				// ACTUALIZAR DATOS DE APROBACIÓN
				$row['habitabilidad_estado']='APROBADO';
			}
			
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['habitabilidad_estado'],['PENDIENTE','INGRESADO'])) return $pdfCtrl->printCustomReport('<li>El PERMISO DE OCUPACIÓN Y HABITABILIDAD no ha sido aprobado</li>',$row['responsable_firma_nombre'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
			
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			if($row['habitabilidad_estado']=='PAGADO'){
				// TRANSICIÓN DE ESTADO
				$row['habitabilidad_estado']='APROBADO';
				$row['habitabilidad_aprobado']=$this->getFecha('dateTime');
				$row['habitabilidad_anio']=$this->setFormatDate($row['habitabilidad_aprobado'],'Y');
				// OBTENER ID DE PERSONAL RESPONSABLE DE APROBACIÓN
				$userId=app\session::getSessionAdmin();
				$strWhr=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE usuario_id={$userId}");
				$strAprueba=$this->db->selectFromView('vw_personal',"WHERE fk_persona_id=({$strWhr}) AND ppersonal_estado='EN FUNCIONES' AND personal_definicion='TITULAR'");
				if($this->db->numRows($strAprueba)<1) $this->getJSON("No se ha podido generar la aprobación, el usuario que intenta generar el VBP no cuenta con un registro como personal institucional!");
				$row['habitabilidad_aprueba']=$this->db->findOne($strAprueba)['ppersonal_id'];
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['habitabilidad_serie']==0) $row['habitabilidad_serie']=$this->db->getNextSerie($this->entity);
				$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}
			
			// NOMBRE DE RESPONSABLE QUE APRUEBA EL VBP
			$row['habitabilidad_aprueba_nombre']=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['habitabilidad_aprueba']}"))['personal_nombre'];
			// PARSE FECHAS
			$row['vbp_aprobado']=$this->setFormatDate($row['vbp_aprobado'],'complete');
			$row['habitabilidad_revisado']=$this->setFormatDate($row['habitabilidad_revisado'],'complete');
			$row['habitabilidad_aprobado']=$this->setFormatDate($row['habitabilidad_aprobado'],'complete');
			// NUMERO DE PERMISO
			$row['habitabilidad_serie']=str_pad($row['habitabilidad_serie'],3,"0",STR_PAD_LEFT);
			
			// PERSONAL TÉCNICO DE REVISIÓN
			$row['listaTecnicos']=$this->getInspectorsTemplate($id,$this->entity);
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// DATOS DE PAGO
			$billing=$this->db->findOne($this->db->selectFromView('ordenescobro',"WHERE orden_entidad='{$this->entity}' AND orden_entidad_id={$row['habitabilidad_id']}"));
			// PARSE NUMBER TO LETTER
			$billing['orden_total_text']=$this->setNumberToLetter($billing['orden_total'],true);
			// CARGAR DATOS DE PAGO
			$pdfCtrl->loadSetting($billing);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['habitabilidad_codigo']);
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