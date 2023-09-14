<?php namespace model\prevention;
use api as app;

class definitiveModel extends feasibilityModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='definitivoglp';
	
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
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(definitivo_codigo)=UPPER('{$code}')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// OBTENER REGISTRO DE VBP
			$feasibility=$this->db->findById($row['fk_factibilidad_id'],'factibilidadglp');
			// VALIDAR SI EL VBP HA SIDO APROBADO
			if($feasibility['factibilidad_estado']<>'APROBADO') $this->getJSON("Favor, tomar contacto con la Unidad de Prevención; solicitar la validación del Visto Bueno de Planos <b>{$feasibility['factibilidad_codigo']}</b>");
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN - SERVICIO INCIADO
			if(in_array($row['definitivo_estado'],['PENDIENTE'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6182,$this->getAccesRol())) $this->getJSON($this->varGlobal['BAD_INPUT_REQUEST_ROL']);
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['definitivo_estado']='INGRESADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
				$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
			}elseif(in_array($row['definitivo_estado'],['INGRESADO'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6183,$this->getAccesRol())) $this->getJSON("No tiene permisos para asignar personal para la revisión!");
				// GENERAR DATOS PARA ASIGNAR UN INSPECTOR
				$row['definitivo_estado']='ASIGNADO';
				// MODAL A DESPLEGAR
				$row['modal']='habitabilidadinspector';
				$row['tb']=$this->entity;
			}elseif(in_array($row['definitivo_estado'],['ASIGNADO','REVISION','CORRECCION'])){
				// VALIDAR ROL DE INGRESO DE SOLICITUD
				if(!in_array(6184,$this->getAccesRol())) $this->getJSON("No tiene permisos para registrar la revisión de este proyecto!");
				// MODAL A DESPLEGAR
				$row['modal']='Definitivoglp';
				$row['tb']=$this->entity;
			}elseif(in_array($row['definitivo_estado'],['REVISADO'])){
				// VALIDAR PERMISO PARA GENERAR ORDEN DE COBRO
				if(!in_array(6185,$this->getAccesRol())) $this->getJSON("No cuenta con los privilegios para generar la orden de cobro de la solicitud <b>#$code</b>, por favor direccione el trámite con quien corresponda para que genere la orden de cobro!");
				
				// VALIDAR SI PROCEDE A PAGO
				$payment=$this->validateEntityExemptPayment($row['fk_entidad_id'],$this->entity,$row['definitivo_id']);
				// VALIDAR ROL PARA APROBAR TRAMITES SIN PAGO
				if($payment['estado']=='info') $this->getJSON((!in_array(61851,$this->getAccesRol()))?$this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE']:$payment);
				
				// DATOS PARA GENERAR ORDEN DE COBRO
				$row['entity']=$this->entity;
				$row['entityId']=$row['definitivo_id'];
				// MODAL A DESPLEGAR
				$row['modal']='Generarordenescobro';
				$row['edit']=false;
			}elseif(in_array($row['definitivo_estado'],['ORDEN DE COBRO GENERADA'])){
				$this->getJSON("La solicitud <b>#$code</b> de PERMISO DE OCUPACIÓN Y HABITABILIDAD se encuentra pendiente de pago!");
			}elseif(in_array($row['factibilidad_estado'],['PAGADO'])){
				$this->getJSON("La solicitud <b>#$code</b> de PERMISO DE OCUPACIÓN Y HABITABILIDAD ya se encuentra pagada, por favor dirija el trámite a quien corresponda autorizar el respectivo permiso!");
			}elseif(in_array($row['definitivo_estado'],['APROBADO'])){
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
		elseif(isset($this->post['type']) && $this->post['type']=='getInspectors') $json=$this->getInspectorsByCode($this->post['definitivo_id'],$this->entity);
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
		if(isset($this->post['factibilidad_estado']) && $this->post['factibilidad_estado']=='APROBADO'){
			
		}else{
			// INGRESAR REGISTRO CON ENTIDAD
			$this->post['factibilidad_id']=$this->insertProject();
			// INSERTAR REGISTRO DE PROFESIONALES
			$sql=$this->insertProfessionals();
		}
		// INGRESAR REGISTRO CON ENTIDAD
		$this->post['fk_factibilidad_id']=$this->post['factibilidad_id'];
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITA
		$this->post['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
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
		// MODELO PARA DATOS ORIGINALES
		$oldData=$this->db->viewById($this->post['definitivo_id'],$this->entity);
		// VALIDAR POSIBLES ESTADOS DE REGISTRO
		if($this->post['definitivo_estado']=='INGRESADO'){
			// VALIDAR NÚMERO DE SOLICITUD
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $this->post['definitivo_solicitud']=$this->testRequestNumber($this->entity,$this->post['definitivo_solicitud'],$this->post['definitivo_id']);
		}elseif($this->post['definitivo_estado']=='APROBADO'){
			
			// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
			$this->post['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
			
			// GENERAR NÚMERO DE SERIE
			if($this->post['definitivo_serie']<1) $this->post['definitivo_serie']=$this->db->getNextSerie($this->entity);
		}else{
			// REGISTRAR EL INGRESO DE PERSONAL PARA REVISIÓN
			if(isset($this->post['asignInspector']) && $this->post['asignInspector']){
				// INVOCAR MÉTODO PARA REGISTRO DE PERSONAL
				$this->setPersonal($this->entity,$this->post['definitivo_id'],$this->post['selected']);
			}
		}
		// ACTUALIZAR DATOS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE - SOLICITUD CBSD
	 */
	public function printDetail($pdfCtrl,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE definitivo_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			// PARSE FECHAS
			$row['factibilidad_aprobado_dia']=$this->setFormatDate($row['factibilidad_aprobado'],'complete');
			// NOMBRE DE LA PLANTILLA DE REPORTE
			$templateKey=in_array($row['entidad_contribuyente'],['publica','privada'])?'SOCIEDADES':'NATURALES';
			// ADJUNTAR DATOS DE DECLARACIÓN DE AUTOINSPECCIÓN - PRIMER PÁRRAFO
			$row['DEFINITIVO_SOLICITUD']=$this->varGlobal["DEFINITIVO_SOLICITUD_{$templateKey}"];
			
			// DESCRIPCIÓN DE TANQUES ESTACIONARIOS
			$row['tanques_capacidad_unidades']=$this->getTanksGLP($row['factibilidad_id'],'request');
			// LISTA DE PROFESIONALES
			$row['TbProfessionals']=$this->completeReportProfesionals($row['fk_factibilidad_id']);
			// DATOS DE FACTURACIÓN
			$row['TbBilling']=$this->getBillingData($row);
			
			// INFORMACION DE FACTIBILIDAD
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_factibilidad_id'],'factibilidadglp'));
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['definitivo_codigo']);
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
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE definitivo_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// EMPEZAR TRANSACCIÓN
			$this->db->begin();
			// RESPUESTA POR DEFECTO
			$sql=$this->setJSON("OPERACIÓN ABORTADA",true);
			// DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR REGISTRO DE EMISION SIN PAGO
			if($row['definitivo_estado']=='REVISADO' && isset($this->get['exemptPayment'])){
				// VALIDAR PERMISO PARA APROBAR TRÁMITE SIN PAGO
				if(!in_array(61851,$this->getAccesRol())){
					return $pdfCtrl->printCustomReport($this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE'],$this->getProfile()['usuario_login'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
				}
				// REGISTRAR SOLICITUD SIN PAGO
				$this->insertExemptPayment($row['fk_entidad_id'],$this->entity,$id);
				// ACTUALIZAR DATOS DE APROBACIÓN
				$row['definitivo_estado']='APROBADO';
			}
			
			// DATOS DE FACTIBILIDAD
			$row=array_merge($this->db->viewById($row['factibilidad_id'],'factibilidadglp'),$row);
			
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['definitivo_estado'],['PENDIENTE','INGRESADO'])) return $pdfCtrl->printCustomReport('<li>El CERTIFICADO DEFINITIVO DE GLP no ha sido aprobado</li>',$row['representantelegal_nombre'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
			
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			if($row['definitivo_estado']=='PAGADO'){
				// TRANSICIÓN DE ESTADO
				$row['definitivo_estado']='APROBADO';
				$row['definitivo_aprobado']=$this->getFecha('dateTime');
				$row['definitivo_anio']=$this->setFormatDate($row['definitivo_aprobado'],'Y');
				// OBTENER ID DE PERSONAL RESPONSABLE DE APROBACIÓN
				$userId=app\session::getSessionAdmin();
				$strWhr=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE usuario_id={$userId}");
				$strAprueba=$this->db->selectFromView('vw_personal',"WHERE fk_persona_id=({$strWhr}) AND ppersonal_estado='EN FUNCIONES' AND personal_definicion='TITULAR'");
				if($this->db->numRows($strAprueba)<1) $this->getJSON("No se ha podido generar la aprobación, el usuario que intenta generar el permiso no cuenta con un registro como personal institucional!");
				$row['definitivo_aprueba']=$this->db->findOne($strAprueba)['ppersonal_id'];
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['definitivo_serie']==0) $row['definitivo_serie']=$this->db->getNextSerie($this->entity);
				$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}
			
			// NOMBRE DE RESPONSABLE QUE APRUEBA EL VBP
			$row['definitivo_aprueba_nombre']=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['definitivo_aprueba']}"))['personal_nombre'];
			// NUMERO DE PERMISO
			$row['definitivo_serie']=str_pad($row['definitivo_serie'],3,"0",STR_PAD_LEFT);
			// PARSE FECHAS
			$row['factibilidad_aprobado']=$this->setFormatDate($row['factibilidad_aprobado'],'complete');
			$row['definitivo_revisado']=$this->setFormatDate($row['definitivo_revisado'],'complete');
			$row['definitivo_aprobado']=$this->setFormatDate($row['definitivo_aprobado'],'complete');
			// DESCRIPCIÓN DE TANQUES ESTACIONARIOS
			$row['tanques_capacidad_unidades']=$this->getTanksGLP($row['fk_factibilidad_id'],'request');

			// LISTA DE PROFESIONALES
			$row['TbProfessionals']=$this->completeReportProfesionals($row['fk_factibilidad_id']);
			// PERSONAL TÉCNICO DE REVISIÓN
			$row['listaTecnicos']=$this->getInspectorsTemplate($id,$this->entity);
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// DATOS DE PAGO
			$billing=$this->db->findOne($this->db->selectFromView('ordenescobro',"WHERE orden_entidad='{$this->entity}' AND orden_entidad_id={$row['definitivo_id']}"));
			// PARSE NUMBER TO LETTER
			$billing['orden_total_text']=$this->setNumberToLetter($billing['orden_total'],true);
			// CARGAR DATOS DE PAGO
			$pdfCtrl->loadSetting($billing);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['definitivo_codigo']);
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