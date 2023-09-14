<?php namespace model\Tthh\md;
use controller as ctrl;
use model as mdl;

class medicalConsultationModel extends mdl\personModel {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='consultasmedicas';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'tthh');
	}
	
	
	/*
	 * PREPARA DATOS PARA EL ENVÍO DE CORREO ELECTRÓNICO
	 */
	private function notifyByEmail($modelId){
		// DATOS DE DEPARTAMENTO - JEFATURA - MÓDULO
		$varDirection=$this->getConfParams('PREVENTION');
		// DATOS DE CAPACITACIÓN
		$model=$this->db->viewById($modelId,$this->entity);
		// PARA PRESENTACIÓN DE E-MAIL MULTI PROCESO
		$model['module']='training';
		$model['proceso_nombre']="CAPACITACIÓN CIUDADANA";
		$model['codigo']=$model['capacitacion_codigo'];
		// LISTA DE DESTINATARIOS
		$recipientsList=array();
		// VALIDAR ESTADOS INDIVIDUALES DEL REGISTRO
		if($model['capacitacion_estado']=='PENDIENTE'){
			$template='processStatus';
			$subject="{$model['solicitud']} - CANCELADA";
			// DATOS AUXILIARES
			$model['proceso_estado']="{$model['proceso_nombre']} CANCELADA";
			$model['estado']="CANCELADA";
			// LISTA DE DESTINATARIOS PARA NOTIFICACIÓN DE PROCESO
			$recipientsList=explode(',',$this->varLocal['CONFIRMED_RECIPIENTS']);
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
	 * REGISTRAR EL DIAGNÓTICO
	 */
	private function setDiagnosisItem($entityId,$list){
		// MENSAJE POR DEFECTO PARA RETORNO
		$sql=$this->setJSON("¡No se ha ingresado el diagnóstico, la transacción será cancelada!");
		// ELIMINAR LISTADO DE DIAGNOSTICOS PREVIOS
		$sql=$this->db->executeTested($this->db->getSQLDelete('consulta_diagnostico',"WHERE fk_consulta_id={$entityId}"));
		// RECORRER EL LISTADO DE DIAGNÓSTICO
		foreach($list as $v){
			// PARSE MODELO PARA INGRESO
			$v['fk_consulta_id']=$entityId;
			$v['fk_cie_id']=$v['cie_id'];
			// GENERAR STRING DE INGRESO DE ÍTEMS DE DIAGÓSTICO
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'consulta_diagnostico'));
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * REGISTRAR EL MEDICAMENTOS
	 */
	private function setMedicinesItem($entityId,$serial,$list){
		// MENSAJE POR DEFECTO PARA RETORNO
		$sql=$this->setJSON("¡No se ha ingresado el diagnóstico, la transacción será cancelada!");
		// FECHA DE TRANSACCION
		$date=$this->getFecha();
		// ID DE PERSONAL
		$staffId=$this->getStaffIdBySession();
		// ELIMINAR LISTADO ANTERIOR DE MEDICAMENTOS ASIGNADOS
		$sql=$this->db->executeTested($this->db->getSQLDelete('consultasmedicas_medicamentos',"WHERE fk_consulta_id={$entityId}"));
		// LIBERAR STOCK PEDIDO POR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLDelete('inventario_medicamentos',"WHERE fk_id={$entityId} AND fk_table='{$this->entity}'"));
		// RECORRER EL LISTADO DE DIAGNÓSTICO
		foreach($list as $v){
			// REGISTRO DE CONSULTA
			$v['fk_consulta_id']=$entityId;
			// VALIDAR EL TIPO DE MEDICAMENTO
			if($v['receta_medicamento_tipo']=='INTERNO'){
				// PARSE MODELO PARA INGRESO
				$v['fk_medicamento_id']=$v['medicamento_id'];
				$v['receta_medicamento_nombre']=$v['medicamento_nombre'];
			}
			// GENERAR STRING DE INGRESO DE ÍTEMS DE DIAGÓSTICO
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'consultasmedicas_medicamentos'));

			// ACTUALIZAR INVENTARIO DE MEDICAMENTOS
			if($v['receta_medicamento_tipo']=='INTERNO'){
				// GENERAR MODELO TEMPORAL DE INVENTARIO
				$tempModel=array(
					'fk_personal_id'=>$staffId,
					'fk_medicamento_id'=>$v['medicamento_id'],
					'inventario_transaccion'=>'DESCARGO',
					'inventario_cantidad'=>$v['receta_cantidad'],
					'inventario_descripcion'=>"DESCARGO EN CONSULTA # {$serial} EL {$date}",
					'fk_table'=>$this->entity,
					'fk_id'=>$entityId
				);
				// GENERAR STRING DE INGRESO DE ÍTEMS DE DIAGÓSTICO
				$sql=$this->db->executeTested($this->db->getSQLInsert($tempModel,'inventario_medicamentos'));
			}
			
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * NÚMERO DE ORDEN DE ABASTECIMIENTO POR UNIDAD
	 */
	private function getNextOrder($clinicalId){
		// CUSTOM TABLE
		$tb=$this->db->setCustomTable("vw_{$this->entity}",'COUNT(fk_historiaclinica_id) serie');
		// GENERAR CONSULTA
		$serie=$this->db->findOne($this->db->selectFromView($tb,"WHERE fk_historiaclinica_id={$clinicalId}"));
		// RETORNAR SERIE DE ATENCION
		return ($serie['serie']==null?0:$serie['serie'])+1;
	}
	
	/*
	 INGRESO DE UN NUEVO REGISTRO
	 */
	public function insertEntity(){
		
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// CONSULTA MEDICA
		$record=array_merge($this->post,$this->post['patient']);
		
		// VALIDAR SI ES INGRESO NUEVO O EDICIÓN
		if(isset($this->post['consulta_id'])){
			// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLUpdate($record,$this->entity));
			// OBTENER ID DE REGISTRO
			$entityId=$this->post['consulta_id'];
		}else{
			// VERIFICAR/GENERAR NÚMERO DE ORDEN DE MANTENIMIENTO
			if($record['consulta_serie']==0) $record['consulta_serie']=$this->getNextOrder($record['historia_id']);
			// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($record,$this->entity));
			// OBTENER ID DE REGISTRO
			$entityId=$this->db->getLastID($this->entity);
		}
		
		// ACTUALIZAR HISTORIA CLINICA
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post['patient'],'historiasclinicas'));
		
		// REGISTRO DE MEDICAMENTOS APLICADOS
		if($this->post['consulta_medicamento']=='SI') $sql=$this->setMedicinesItem($entityId,$this->post['consulta_serie'],$this->post['dose']);
		else $sql=$this->db->executeTested($this->db->getSQLDelete('consultasmedicas_medicamentos',"WHERE fk_consulta_id={$entityId}"));
		
		// REGISTRO DE DIAGNOSTICOS
		$sql=$this->setDiagnosisItem($entityId,$this->post['diagnosis']);
		
		// ACTUALIZACION DE DATOS PERSONALES DEL PACIENTE
		$modelPerson=array(
			'persona_id'=>$this->post['patient']['persona_id'],
			'persona_estatura'=>$this->post['consulta_estatura'],
			'persona_peso'=>$this->post['consulta_peso']
		);
		// ACTUALIZAR REGISTRO DE PERSONAS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($modelPerson,'personas'));
		
		// ADJUNTAR ID DE REGISTRO PARA IMRPRESIÓN
		$sql['data']=array('id'=>$entityId);
		
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZACION DE CONSULTAS MEDICAS
	 */
	public function updateEntity(){
		
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// STRING PARA ACTUALIZAR REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR HISTORIA CLÍNICA DEL PACIENTE
	 */
	private function getMedicalHistoryByPatientId($historyId){
		// MODELO DE CONSULTA
		$data=array();
		// STRING PARA CONSULTAR DATOS DE PERSONAL
		$str=$this->db->selectFromView("vw_historiasclinicas","WHERE historia_id={$historyId}");
		// VALIDAR LA EXISTENCIA DEL PERSONAL
		if($this->db->numRows($str)<0) $this->getJSON("No se ha encontrado el registro solicitado.");
		// DATOS DEL PACIENTE
		$data['patient']=$this->db->findOne($str);
		// CONSULTAS PREVIAS
		$data['previous']=$this->db->findAll($this->db->selectFromView("vw_{$this->entity}","WHERE fk_historiaclinica_id={$historyId} ORDER BY consulta_fecha_consulta DESC"));
		// ANTECEDENTES DEL PACIENTE
		$data['background']=$this->db->findAll($this->db->selectFromView("vw_antecedentesenfermedades","WHERE fk_historiaclinica_id={$historyId} ORDER BY antecedente_registro DESC"));
		// MODELO DE DIAGNÓSTICOS
		$data['diagnosis']=array();
		$data['dose']=array();
		// RETORNAR HISTORIA CLINICA
		return $data;
	}
	
	/*
	 * CONSULTAR DATOS DE PERSONAL
	 */
	private function requestByPerson($code){
		// STRING PARA CONSULTAR DATOS DE PERSONAL
		$str=$this->db->selectFromView("vw_historiasclinicas","WHERE persona_doc_identidad='{$code}'");
		// VALIDAR LA EXISTENCIA DEL PERSONAL
		if($this->db->numRows($str)<0) $this->getJSON("No se ha encontrado el registro solicitado.");
		// OBTENER Y RETORNAR INFORMACIÓN DE REGISTRO
		return $this->db->findOne($str);
	}
	
	/*
	 * CONSULTAR DATOS DE PERSONAL
	 */
	private function requestByHistory($historyId){
		// MODELO DE CONSULTA
		$data=$this->getMedicalHistoryByPatientId($historyId);
		
		// SESION DE PERSONAL
		$sessionId=$this->getStaffIdBySession();
		
		// TODO - CORREGIR ESTO CON PARAMETRIZACION DEL SISTEMA
		// OBTENER INFORMACIÓN DEL DOCTOR
		// $str=$this->db->selectFromView('vw_personal',"WHERE puesto_id=39 AND ppersonal_estado='EN FUNCIONES'");
		$str=$this->db->selectFromView('vw_personal',"WHERE fk_personal_id={$sessionId} AND ppersonal_estado='EN FUNCIONES'");
		// VALIDAR EXISTENCIA DE DIRECTOR ADMINISTRATIVO
		if($this->db->numRows($str)<1) $this->getJSON("No se puede continuar con la transacción debido a que no se ha registrado al Médico Institucional.");
		
		// OBTENER INFORMACIÓN DE DIRECTOR ADMINISTRATIVO
		$data['doctor']=$this->db->findOne($str);
		
		// DATOS DE MODELO
		$data['fk_doctor_id']=$data['doctor']['ppersonal_id'];
		$data['fk_historiaclinica_id']=$historyId;
		$data['consulta_descansomedico']='NO';
		$data['consulta_certificadomedico']='NO';
		$data['consulta_cita_subsecuente']='NO';
		$data['consulta_medicamento']='NO';
		$data['historia_actualizar']='NO';
		$data['consulta_serie']=0;
		$data['consulta_fecha_consulta']=$this->getFecha('Y-m-d H:i');
		$data['consulta_peso']=$data['patient']['persona_peso'];
		$data['consulta_estatura']=$data['patient']['persona_estatura'];
		if(intval($data['patient']['persona_estatura'])>0) $data['consulta_imc']=($data['consulta_peso']/pow($data['consulta_estatura'],2));
		
		// RETORNAR DATOS DE PERSONAL
		return $data;
	}
	
	/*
	 * CONSULTAR DATOS DE PERSONAL
	 */
	private function requestByEntityId($entityId){
		// STRING PARA CONSULTAR DATOS DE PERSONAL
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE consulta_id={$entityId}");
		// VALIDAR LA EXISTENCIA DEL PERSONAL
		if($this->db->numRows($str)<0) $this->getJSON("No se ha encontrado el registro solicitado.");
		// OBTENER INFORMACIÓN DE REGISTRO
		$data=$this->db->findOne($str);
		
		// JOIN CON HISTORIA CLINICA
		$data=array_merge($data,$this->getMedicalHistoryByPatientId($data['fk_historiaclinica_id']));
		
		// OBTENER INFORMACIÓN DEL DOCTOR
		$str=$this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$data['fk_doctor_id']}");
		// VALIDAR EXISTENCIA DE DIRECTOR ADMINISTRATIVO
		if($this->db->numRows($str)<1) $this->getJSON("No se puede continuar con la transacción debido a que no se ha registrado al Médico Institucional.");
		// OBTENER INFORMACIÓN DE DIRECTOR ADMINISTRATIVO
		$data['doctor']=$this->db->findOne($str);
		
		// RECORRER EL LISTADO DE DIAGNÓSTICO
		foreach($this->db->findAll($this->db->getSQLSelect('consulta_diagnostico',['cie'],"WHERE fk_consulta_id={$entityId}")) as $v){
			// PARSE MODELO PARA INGRESO
			$data['diagnosis'][]=$v;
		}
		
		// RECORRER EL LISTADO DE DIAGNÓSTICO
		$str1=$this->db->findAll($this->db->getSQLSelect('consultasmedicas_medicamentos',['medicamentos'],"WHERE fk_consulta_id={$entityId} AND receta_medicamento_tipo='INTERNO'"));
		$str2=$this->db->findAll($this->db->getSQLSelect('consultasmedicas_medicamentos',[],"WHERE fk_consulta_id={$entityId} AND receta_medicamento_tipo='EXTERNO'"));
		foreach(array_merge($str1,$str2) as $v){
			// PARSE MODELO PARA INGRESO
			$data['dose'][]=$v;
		}
		
		// RETORNAR DATOS DE PERSONAL
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
		// CONSULTAR POR CODEIGO
		if(isset($this->post['code'])) $json=$this->requestByPerson($this->post['code']);
		// CONSULTAR POR ID DE HISTORIA
		elseif(isset($this->post['historyId'])) $json=$this->requestByHistory($this->post['historyId']);
		// CONSULTAR POR ID DE HISTORIA
		elseif(isset($this->post['entityId'])) $json=$this->requestByEntityId($this->post['entityId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView($this->entity,"WHERE consulta_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE consulta_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			
			// DATOS DE INDICACIONES
			$row['historia_serie']=str_pad($row['historia_serie'],4,"0",STR_PAD_LEFT);;
			$row['consulta_serie']=str_pad($row['consulta_serie'],4,"0",STR_PAD_LEFT);;
			$row['consulta_fecha_consulta']=$this->setFormatDate($row['consulta_fecha_consulta'],'full');
			
			$row['consulta_planes']=nl2br($row['consulta_planes']);
			$row['consulta_revision_organos']=nl2br($row['consulta_revision_organos']);
			$row['consulta_examen_fisico']=nl2br($row['consulta_examen_fisico']);
			$row['consulta_enfermedad_actual']=nl2br($row['consulta_enfermedad_actual']);
			
			// LISTADO CIE10
			$row['cie10list']="";
			// RECORRER EL LISTADO DE DIAGNÓSTICO
			foreach($this->db->findAll($this->db->getSQLSelect('consulta_diagnostico',['cie'],"WHERE fk_consulta_id={$id}")) as $v){
				// PARSE MODELO PARA INGRESO
				$row['cie10list'].="<tr><td colspan=3 style='font-weight:normal'><b>{$v['cie_codigo']}</b>. {$v['cie_descripcion']}</td><td>{$v['diagnostico_estado']}</td></tr>";
			}
			
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	
	/*
	 * IMPRESION DE CERTIFICADO MEDICO
	 */
	public function printCertificationById($pdfCtrl,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE consulta_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			
			// PARSE FECHAS
			$row['consulta_fecha']=$this->setFormatDate($row['consulta_fecha_consulta'],'complete');
			$row['consulta_descansomedico_desde']=$this->setFormatDate($row['consulta_descansomedico_desde'],'full');
			$row['consulta_descansomedico_hasta']=$this->setFormatDate($row['consulta_descansomedico_hasta'],'full');
			$row['consulta_descansomedico_indicaciones']=nl2br($row['consulta_descansomedico_indicaciones']);
			
			// PLANTILLA PARA CERTIFICADO
			$row['TEMPLATE_CERTIFICADO_DESCANSO_MEDICO']=($row['consulta_descansomedico']=='SI')?$this->varGlobal['TEMPLATE_DESCANSOMEDICO']:$this->varGlobal['TEMPLATE_CERTIFICADOMEDICO'];
			
			// LISTADO CIE10
			$row['cie10List']="";
			// RECORRER EL LISTADO DE DIAGNÓSTICO
			foreach($this->db->findAll($this->db->getSQLSelect('consulta_diagnostico',['cie'],"WHERE fk_consulta_id={$this->get['id']}")) as $v){
				// PARSE MODELO PARA INGRESO
				$row['cie10List'].="<b>{$v['cie_descripcion']}</b> CIE 10 <B>{$v['cie_codigo']}</B>; ";
			}
			
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,'certificadosmedicos');
			
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRESION DE RECETA
	 */
	public function printPrescriptionById($pdfCtrl,$config){
		
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE consulta_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			
			// LISTADO CIE10
			$row['medicinesList']="";
			$row['medicinesIndications']="";
			// RECORRER EL LISTADO DE DIAGNÓSTICO
			$str1=$this->db->findAll($this->db->getSQLSelect('consultasmedicas_medicamentos',['medicamentos'],"WHERE fk_consulta_id={$this->get['id']} AND receta_medicamento_tipo='INTERNO'"));
			$str2=$this->db->findAll($this->db->selectFromView('consultasmedicas_medicamentos',"WHERE fk_consulta_id={$this->get['id']} AND receta_medicamento_tipo='EXTERNO'"));
			foreach(array_merge($str1,$str2) as $v){
				// NUMEROS TO STRING
				$v['receta_cantidad_alt']=$this->setNumberToLetter($v['receta_cantidad']);
				// PARSE MODELO PARA INGRESO
				if($v['receta_medicamento_tipo']=='INTERNO'){
					$row['medicinesList'].="<li>{$v['medicamento_nombre']} {$v['medicamento_dosis']}<br><small style='margin-left:20px;'>> {$v['medicamento_presentacion']} # {$v['receta_cantidad']} ({$v['receta_cantidad_alt']})</small></li>";
					$row['medicinesIndications'].="<li>{$v['medicamento_nombre']} {$v['medicamento_dosis']} ({$v['medicamento_presentacion']})<br><small style='margin-left:20px;'>> {$v['receta_prescripcion']}</small></li>";
				}else{
					$row['medicinesList'].="<li> {$v['receta_medicamento_nombre']} (COMPRAR)<br><small style='margin-left:20px;'>{$v['receta_cantidad']} ({$v['receta_cantidad_alt']})</small></li>";
					$row['medicinesIndications'].="<li> {$v['receta_medicamento_nombre']}<br><small style='margin-left:20px;'>> {$v['receta_prescripcion']}</small></li>";
				}
			}
			
			// ADJUNTAR INDICACIONES A RECETA
			$row['medicinesIndications'].=$this->textarea2Li($row['consulta_planes']);
			
			// LISTADO CIE10
			$row['cie10List']="";
			$row['diagnosisList']="";
			// RECORRER EL LISTADO DE DIAGNÓSTICO
			foreach($this->db->findAll($this->db->getSQLSelect('consulta_diagnostico',['cie'],"WHERE fk_consulta_id={$this->get['id']}")) as $v){
				// PARSE MODELO PARA INGRESO
				$row['cie10List'].="{$v['cie_codigo']}; ";
				$row['diagnosisList'].="- {$v['cie_descripcion']} <br>";
			}
			
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,'prescripcionmedica');
			
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}