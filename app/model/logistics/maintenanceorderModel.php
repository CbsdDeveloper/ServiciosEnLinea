<?php namespace model\logistics;
use model\subjefature as sub;

class maintenanceorderModel extends sub\trackingModel {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='ordenesmantenimiento';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * REGISTRO DE RECURSOS DE MANTENIMIENTO
	 */
	private function insertDetails($orderId,$details,$entity='mantenimiento_trabajos'){
		// RETORNO DE CONSULTAS
		$sql=$this->setJSON("No se han encontrado detalles de registros...");
		// ELIMINAR LOS REGISTROS EXISTENTES
		$sql=$this->db->executeTested($this->db->getSQLDelete($entity,"WHERE fk_orden_id={$orderId}"));
		// RECORRER TIPOS DE RECURSOS
		foreach($details as $v){
			// INGRESAR ID DE ORDEN DE MANTENIMIENTO EN REGISTRO DE TRABAJOS
			$v['fk_orden_id']=$orderId;
			// SENTENCIA DE INGRESO
			$str=$this->db->getSQLInsert($v,$entity);
			// EJECUTAR LA SENTENCIA SQL :: INGRESO / ACTUALIZAR
			$sql=$this->db->executeTested($str);
		}
		// RETORNO DE CONSULTA
		return $sql;
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */ 
	public function insertEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRO DE SALIDA - HORARIO
		if($this->post['orden_fecha_mantenimiento_tipo']=='SISTEMA') $this->post['orden_fecha_mantenimiento']=$this->getFecha('dateTime');
		// VERIFICAR/GENERAR NÚMERO DE ORDEN DE MANTENIMIENTO
		if($this->post['orden_serie']==0) $this->post['orden_serie']=$this->db->getNextSerie($this->entity);
		// INSERTAR DATOS DE FLOTAS
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// VALIDAR ESTADO DE INGRESO DE NUEVO REGISTRO
		if($sql['estado']){
			// INSERTAR ID DE REGISTRO
			$orderId=$this->db->getLastID($this->entity);
			// INGRESO DE DETALLE DE MANTENIMIENTO - RECURSOS
			$sql=$this->insertDetails($orderId,$this->post['actionsList'],'mantenimiento_trabajos');
			$sql=$this->insertDetails($orderId,$this->post['suggestedList'],'mantenimiento_sugeridos');
			// INGRESO DE DETALLE DE MANTENIMIENTO - ANEXOS
			$sql=$this->insertDetails($orderId,$this->post['anexxesList'],'mantenimiento_anexos');
			// VALIDAR ESTADO DE INGRESO DE NUEVO REGISTRO
			$sql['data']=$this->db->getLastEntity($this->entity);
		}
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO
	 */
	public function updateEntity(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// ACTUALIZAR DATOS DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// INGRESO DE DETALLE DE MANTENIMIENTO - RECURSOS
		$sql=$this->insertDetails($this->post['orden_id'],$this->post['actionsList'],'mantenimiento_trabajos');
		$sql=$this->insertDetails($this->post['orden_id'],$this->post['suggestedList'],'mantenimiento_sugeridos');
		// INGRESO DE DETALLE DE MANTENIMIENTO - ANEXOS
		$sql=$this->insertDetails($this->post['orden_id'],$this->post['anexxesList'],'mantenimiento_anexos');
		// INGRESO DE DETALLE DE MANTENIMIENTO - FACTURAS
		$sql=$this->insertDetails($this->post['orden_id'],$this->post['invoicesList'],'mantenimiento_facturas');
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * REGISTRAR FACTURAS PARA PROCESO
	 */
	public function setInvoices(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// ELIMINAR REGISTROS DE FACTURAS ANTERIORES
		$sql=$this->db->executeTested($this->db->getSQLDelete('mantenimiento_facturas',"WHERE fk_orden_id={$this->post['orden_id']}"));
		// RECORRER LISTA DE FACTURAS PARA INGRESARLAS
		foreach($this->post['invoicesList'] as $v){
			// GENERAR MODELO DE REGISTRO  DEFACTURAS
			$v['fk_orden_id']=$this->post['orden_id'];
			// INSERTAR FACTURAS
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'mantenimiento_facturas'));
		}
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * RELACION DE MANTENIMIENTOS CON INFORME
	 */
	private function setMaintenancesReport($reportId,$maintenances){
		// VALIDAR SI SE HA SELECCIONADO MANTENIMIENTOS
		if(count($maintenances)<1)$this->getJSON($this->db->closeTransaction($this->setJSON("Operación cancelada, debe seleccionar los mantenimientos para este informe!")));
		// ELIMINAR MANTENIMIENTOS RELACIONADOS CON INFORME
		$sql=$this->db->executeTested($this->db->getSQLDelete('informe_ordenesmantenimiento',"WHERE fk_informe_id={$reportId}"));
		// RECORRER LISTA DE FACTURAS PARA INGRESARLAS
		foreach($maintenances as $v){
			// GENERAR MODELO DE REGISTRO  DEFACTURAS
			$model=array(
				'fk_informe_id'=>$reportId,
				'fk_orden_id'=>$v
			);
			// INSERTAR FACTURAS
			$sql=$this->db->executeTested($this->db->getSQLInsert($model,'informe_ordenesmantenimiento'));
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * 
	 */
	public function insertReport(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// STRING PARA CONSULTAR SI EXISTE REGISTRO DE INFORME
		$str=$this->db->selectFromView('informemantenimiento',"WHERE UPPER(informe_numero)=UPPER('{$this->post['informe_numero']}')");
		// BUSCAR INFORME DE MANTENIMIENTO POR FECHA
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			// ACTUALIZAR REGISTRO
			$sql=$this->db->executeTested($this->db->getSQLUpdate(array_merge($row,$this->post),'informemantenimiento'));
			// ID DE INFORME
			$reportId=$row['informe_id'];
		}else{
			// STRING DE INGRESO DE NUEVO INFORME
			$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,'informemantenimiento'));
			// ID DE INFORME
			$reportId=$this->db->getLastID('informemantenimiento');
		}
		
		// INGRESO DE REGISTRO DE ORDNES DE MANTENIMIENTO PARA INFORME
		$sql=$this->setMaintenancesReport($reportId,$this->post['selected']);
		
		// INSERTAR ID DE INFORME PARA IMPRESION
		$sql['data']=array(
			'informe_id'=>$reportId
		);
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZAR INFORME
	 */
	public function updateReport(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// ACTUALIZAR REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,'informemantenimiento'));
		
		// INGRESO DE REGISTRO DE ORDNES DE MANTENIMIENTO PARA INFORME
		$sql=$this->setMaintenancesReport($this->post['informe_id'],$this->post['selected']);
		
		// INSERTAR ID DE INFORME PARA IMPRESION
		$sql['data']=$this->post;
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * OBTENER RESPONSABLES
	 */
	private function getCompleteData($optionNew=true,$orderId=0){
		// TRABAJOS A REALIZAR
		$actionsList=array();
		$suggestedList=array();
		// ANEXOS DE ORDEN
		$anexxesList=array();
		// FACTURAS DE ORDEN
		$invoicesList=array();
		// VALIDAR OPCIÓN DE NUEVO REGISTRO
		if($optionNew){
			// OBTENER INFORMACIÓN DE DIRECTOR ADMINISTRATIVO: 4=DIRECTOR ADMINISTRATIVO
			$strAdministrative=$this->db->selectFromView('vw_personal',"WHERE puesto_id=4 AND ppersonal_estado='EN FUNCIONES'");
			// VALIDAR EXISTENCIA DE DIRECTOR ADMINISTRATIVO
			if($this->db->numRows($strAdministrative)<1) $this->getJSON("No se puede continuar con la transacción debido a que no se ha registrado al responsable de la Dirección Administrativa.");
			
			// OBTENER INFORMACIÓN DE PROFESIONAL DE SERVICIOS GENERALES
			$strProfessional=$this->db->selectFromView('vw_personal',"WHERE puesto_id IN (46,60,60,68,71) AND ppersonal_estado='EN FUNCIONES'");
			// VALIDAR EXISTENCIA DE PROFESIONAL DE SERVICIOS GENERALES
			if($this->db->numRows($strProfessional)<1) $this->getJSON("No se puede continuar con la transacción debido a que no se ha registrado al Analista de Servicios Generales.");
			
		}else{
			// DATOS DE REGISTRO
			$row=$this->db->findById($orderId,$this->entity);
			// OBTENER INFORMACIÓN DE DIRECTOR ADMINISTRATIVO: 4=DIRECTOR ADMINISTRATIVO
			$strAdministrative=$this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['director_administrativo']}");
			// OBTENER INFORMACIÓN DE PROFESIONAL DE SERVICIOS GENERALES
			// $strProfessional=$this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['tecnico_servicios']}");
			$strProfessional=$this->db->selectFromView('vw_personal',"WHERE puesto_id IN (46,60,60,68,71) AND ppersonal_estado='EN FUNCIONES'");
			// TRABAJOS REALIZADOS DEL MANTENIMIENTO
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_trabajos',"WHERE fk_orden_id={$orderId}")) as $v){$actionsList[]=$v;}
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_sugeridos',"WHERE fk_orden_id={$orderId}")) as $v){$suggestedList[]=$v;}
			// ANEXOS DEL MANTENIMIENTO
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_anexos',"WHERE fk_orden_id={$orderId}")) as $v){$anexxesList[]=$v;}
			// REPORTE DE FACTURAS
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_facturas',"WHERE fk_orden_id={$orderId}")) as $v){$invoicesList[]=$v;}
		}
		// RETORNAR DATOS
		return array(
			'administrative'=>$this->db->findOne($strAdministrative),
			'professional'=>$this->db->findOne($strProfessional),
			'professionalList'=>$this->db->findAll($strProfessional),
			'actionsList'=>$actionsList,
			'suggestedList'=>$suggestedList,
			'anexxesList'=>$anexxesList,
			'invoicesList'=>$invoicesList/*,
			'orden_km_mantenimiento'=>$this->getLastRecords($this->unitModel['unidad_id'])*/
		);
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 */
	private function requestById($id){
		// DATOS DE REGISTRO
		$info=$this->db->viewById($id,$this->entity);
		// VALIDAR DATOS DE UNIDAD
		$this->validateUnitByCode($info['unidad_nombre']);
		// OBTENER DATOS DE RESPONSABLES
		$info=array_merge($info,$this->getCompleteData(false,$id));
		
		$info['tecnico_serviciosList']=$this->string2JSON($this->varGlobal['ORDENMANTENIMIENTO_CARGO_RESPONSABLES']);
		
		// RETORNAR DATOS DE CONSULTA
		return array('data'=>$info);
	}
	
	/*
	 * ULTIMO MANTENIMIENTO
	 */
	private function getLastMaintenance($unitId){
		// OBTENER ÚLTIMO MANTENIMIENTO
		$str=$this->db->selectFromView($this->entity,"WHERE fk_unidad_id={$unitId} ORDER BY orden_id DESC LIMIT 1");
		// VALIDAR EXISTENCIA DE ULTIMO MANTENIMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER INFORMACIÓN DEL ÚLTIMO MANTENIMIENTO
			$row=$this->db->findOne($str);
			// GENERAR MODELO DE ULTIMO MANTENIMIENTO
			return array('ultimo_fecha_mantenimiento'=>$row['orden_fecha_mantenimiento'],'ultimo_km_mantenimiento'=>$row['orden_km_mantenimiento']);
		}else{
			// GENERAR MODELO DE ULTIMO MANTENIMIENTO
			return array('ultimo_fecha_mantenimiento'=>$this->getFecha(),'ultimo_km_mantenimiento'=>'0');
		}
	}
	
	/*
	 * CONSULTAR MODELO POR UNIDAD
	 */
	private function requestByUnit($code){
		// MODELO TEMPORAL DE INFORMACIÓN
		$info=array(
			'actionsList'=>array(),
			'suggestedList'=>array(),
			'orden_estado'=>'MANTENIMIENTO',
			'orden_serie'=>0
		);
		// VALIDAR DATOS DE UNIDAD
		$unit=$this->validateUnitByCode($code);
		// STRING PARA CONSULTAR EXISTENCIA DE MANTENIMIENTO EN PROCESO
		$str=$this->db->selectFromView('ordenesmantenimiento',"WHERE fk_unidad_id={$unit['unidad_id']} AND orden_estado IN ('MANTENIMIENTO','RECIBIDO')");
		// VALIDAR LA EXISTENCIA DE UN MANTENIMIENTO EN PROCESO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE MANTENIMIENTO
			$info=array_merge($info,$this->db->findOne($str));
			// OBTENER DATOS DE RESPONSABLES
			$info=array_merge($info,$this->getCompleteData(false,$info['orden_id']));
			// CAMBIAR EL ESTADO DEL REGISTRO
			if($info['orden_estado']=='MANTENIMIENTO') $info['orden_estado']='RECIBIDO';
			else $info['orden_estado']='ENTREGADO';
		}else{
			// DATOS DE FORMULARIO
			$info['edit']=false;
			// OBTENER DATOS DE RESPONSABLES
			$info=array_merge($info,$this->getCompleteData());
			// DATOS COMPLEMENTARIOS DEL FORMULARIO
			$info['fk_unidad_id']=$unit['unidad_id'];
			
			$info['tecnico_servicios']=$info['professional']['ppersonal_id'];
			
			$info['director_administrativo']=$info['administrative']['ppersonal_id'];
			// DATOS DE ÚLTIMO MANTENIMIENTO
			$info=array_merge($info,$this->getLastMaintenance($unit['unidad_id']));
		}
		
		$info['tecnico_serviciosList']=$this->string2JSON($this->varGlobal['ORDENMANTENIMIENTO_CARGO_RESPONSABLES']);
		
		// MODELO DE RETORNO 
		return array(
			'data'=>array_merge($unit,$info),
			'modal'=>'Ordenesmantenimiento'
		);
	}
	
	/*
	 * FUNCIÓN PARA OBTENER LAS FACTURAS REACIONADAS A LA ORDEN DE MANTENIMIENTO
	 */
	private function requestInvoicesByUnit($orderId){
		// MODELO DE RETORNO
		$data=array();
		// STRING PARA CONSULTAR FACTURAS RELACIONADAS
		$str=$this->db->selectFromView('mantenimiento_facturas',"WHERE fk_orden_id={$orderId}");
		// VALIDAR LA EXISTENCIA DE REGISTROS
		if($this->db->numRows($str)>0){
			// INSERTAR FACTURAS
			foreach($this->db->findAll($str) as $v){$data[]=$v;}
		}
		// RETORNAR MODELO
		return array('invoicesList'=>$data);
	}
	
	/*
	 * CONSULTAR POR INFORME ELABORADO
	 */
	private function requestByReport($post){
		// GENERAR MODELO
	    $model=array();
	    // GENERAR NUEVO MODELO
	    $report=array();
		
		// CONSULTAR SI SE HA ENVIADO UN ID DE INFORME
		if(isset($post['informe_id'])){
			// OBTENER REGISTRO DE INFORME
			$report=$this->db->findById($post['informe_id'],'informemantenimiento');
		}else{
			// OBTENER DATOS DE RESPONSABLES
			$model=array_merge($model,$this->getCompleteData());
			// DATOS COMPLEMENTARIOS DEL FORMULARIO
			$model['tecnico_servicios']=$model['professional']['ppersonal_id'];
			$model['director_administrativo']=$model['administrative']['ppersonal_id'];
		}
		
		// MODELO PARA LISTA DE MANTENIMIENTOS
		$model['list']=array();
		// MODELO PARA MANTENIMIENTOS SELECCIONADOS
		$model['selected']=array();
		
		// RECORRER MANTENIMIENTOS ENTREGADOS
		$str=$this->db->getSQLSelect($this->db->setCustomTable('informe_ordenesmantenimiento',"fk_orden_id"));
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_estado='ENTREGADO' AND tecnico_puesto IN ('ADMINISTRADOR DE CONTRATO','ADMINISTRADOR DE LA ORDEN DE COMPRA') AND orden_id NOT IN ({$str})");
		// INSERTAR LISTADO
		$model['list']=$this->db->findAll($str);
		
		// INSERTAR MANTENIMIENTOS SELECCIONADOS DE UN INFORME 
		if(isset($post['informe_id'])){
			// MANTENIMIENTOS SELECCIONADOS CON INFORME
			$str=$this->db->getSQLSelect($this->db->setCustomTable('informe_ordenesmantenimiento',"fk_orden_id"),[],"WHERE fk_informe_id={$post['informe_id']}");
			// GENERAR CONSULTA
			$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_estado='ENTREGADO' AND tecnico_puesto='ADMINISTRADOR DE CONTRATO' AND orden_id IN ({$str})");
			// INSERTAR MANTENIMIENTOS SELECCIONADOS
			$model['list']=array_merge($this->db->findAll($str),$model['list']);
		}
		
		// VALIDAR SI EXISTE REGISTRO DE INFORME
		if(isset($post['informe_id'])){
			// LISTAR REGISTROS EXISTENTES
			foreach($this->db->findAll($this->db->selectFromView('informe_ordenesmantenimiento',"WHERE fk_informe_id={$post['informe_id']}")) as $v){
				$model['selected'][]=$v['fk_orden_id'];
			}
		}
		
		// RETORNAR MODELO
		return $model;
	}
	
	/*
	 * CONSULTAR PUESTOS Y DIRECCIONES
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// DETALLE DE REGISTRO
		elseif(isset($this->post['entityId'])) $json=$this->requestById($this->post['entityId']);
		// MODELO PARA CONSULTAR POR VEHÍCULO
		elseif(isset($this->post['code'])) $json=$this->requestByUnit($this->post['code']);
		// CONSULTAR POR TIPO DE INFORME
		elseif(isset($this->post['type'])) $json=$this->requestByReport($this->post);
		// MODELO PARA CONSULTAR LAS FACTURAS GENERADAS
		elseif(isset($this->post['getInvoicesFrom'])) $json=$this->requestInvoicesByUnit($this->post['getInvoicesFrom']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * IMPRESIÓN DE INFORMES DE MANTENIMIENTO
	 */
	public function printMaitenanceReport($pdfCtrl,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_informemantenimiento","WHERE informe_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
		    $row=$this->db->findOne($str);
			// DETALLE DE UNIDADES
			$row['reporte_unidades']='';
			// VALOR A CANCELAR
			$row['valor_total_mantenimientos']=0;
			$row['informe_detalle']=nl2br($row['informe_detalle']);
			// RECORRER MANTENIMIENTOS SELECCIONADOS
			foreach($this->db->findAll($this->db->getSQLSelect('informe_ordenesmantenimiento',['ordenesmantenimiento'],"WHERE fk_informe_id={$this->get['id']} ORDER BY orden_serie")) as $val){
				// AGREGAR INFORMACIÓN DE ORDEN DE MANTENIMIENTO
				$maintenance=$this->db->viewById($val['fk_orden_id'],'ordenesmantenimiento');
				// TRABAJOS
				$maintenance['workList']="";
				// LISTAR LOS TRABAJOS A REALIZARSE
				$workMaintenances=$this->db->findAll($this->db->selectFromView('mantenimiento_trabajos',"WHERE fk_orden_id={$val['fk_orden_id']}"));
				$suggestedMaintenances=$this->db->findAll($this->db->selectFromView('mantenimiento_sugeridos',"WHERE fk_orden_id={$val['fk_orden_id']}"));
				foreach(array_merge($workMaintenances,$suggestedMaintenances) as $idx=>$v){ $maintenance['workList'].="<small style='margin-left:15px;'>- {$v['trabajo_descripcion']}</small><br>"; }
				
				// VARIABLE PARA EL LISTADO FACTURAS GENERADAS
				$maintenance['tbodyInvoices']="";
				// FACTURAS INSCRITAS
				$maintenance['facturas_total']=0;
				// LISTAR FACTURAS GENERADAS
				foreach($this->db->findAll($this->db->selectFromView('mantenimiento_facturas',"WHERE fk_orden_id={$val['fk_orden_id']}")) as $idx=>$v){
					// ITEM PARA REGISTRO DE FACTURAS
					$maintenance['tbodyInvoices'].="<tr><td>".($idx+1)."</td><td>{$v['factura_numero']}</td><td>{$v['factura_tipo']}</td><td>$ {$v['factura_valor']}</td></tr>";
					// TOTAL DE FACTURAS INSCRITAS
					$maintenance['facturas_total']+=$v['factura_valor'];
				}
				$maintenance['facturas_total']=number_format($maintenance['facturas_total'], 2, '.', '');
				$row['valor_total_mantenimientos']+=$maintenance['facturas_total'];
				// VALOR DE FACTURAS A TEXTO
				$maintenance['facturas_total_text']=$this->setNumberToLetter($maintenance['facturas_total'],true);
				
				// PLANTILLA DE UNIDADES
				$templateUnits=$this->varGlobal['TEMPLATE_REPORT_MAINTENANCES_UNIDADES'];
				foreach($maintenance as $k=>$v){$templateUnits=preg_replace('/{{'.$k.'}}/',$v,$templateUnits);}
				// DETALLE DE UNIDADES
				$row['reporte_unidades'].=$templateUnits;
			}
			
			// VALOR TOTAL A CANCELAR
			$row['valor_total_mantenimientos_alt']=$this->setNumberToLetter($row['valor_total_mantenimientos'],true);
			$row['valor_total_mantenimientos']=number_format($row['valor_total_mantenimientos'],2);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,'informemantenimiento');
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			// PARSE NÚMERO DE ORDEN
			$row['orden_serie']=str_pad($row['orden_serie'],3,"0",STR_PAD_LEFT);
			// PARSE DATOS
			$row['orden_km_mantenimiento']=intval($row['orden_km_mantenimiento']);
			$row['ultimo_km_mantenimiento']=intval($row['ultimo_km_mantenimiento']);
			$row['orden_fecha_mantenimiento']=$this->setFormatDate($row['orden_fecha_mantenimiento'],'Y-m-d');
			
			// VARIABLE PARA EL LISTADO DE TRABAJOS A REALIZAR
			$row['tbodyWorksToDo']="";
			// LISTAR LOS TRABAJOS A REALIZARSE
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_trabajos',"WHERE fk_orden_id={$id}")) as $idx=>$v){
				// INSERTAR REGISTRO
				$row['tbodyWorksToDo'].="<tr><td>".($idx + 1)."</td><td>".$v['trabajo_descripcion']."</td></tr>";
			}
			
			// MANTENIMIENTOS SUGERIDOS POR EL TALLER
			$row['suggestedMaintenances']="";
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_sugeridos',"WHERE fk_orden_id={$id}")) as $idx=>$v){
				$row['suggestedMaintenances'].="<tr><td>".($idx + 1)."</td><td>".$v['trabajo_descripcion']."</td></tr>";
			}
			
			// VARIABLE PARA EL LISTADO DE TRABAJOS A REALIZAR
			$row['anexxesList']="";
			// LISTAR LOS TRABAJOS A REALIZARSE
			foreach($this->db->findAll($this->db->selectFromView('mantenimiento_anexos',"WHERE fk_orden_id={$id}")) as $idx=>$v){
				// INSERTAR REGISTRO
				$row['anexxesList'].="<tr><td>".($idx + 1)."</td><td>".$v['anexo_descripcion']."</td></tr>";
			}
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
		    $row=$this->db->findOne($str);
		    
		    // PARSE NÚMERO DE ORDEN
		    $row['orden_serie']=str_pad($row['orden_serie'],3,"0",STR_PAD_LEFT);
		    
			
			// MODELO DE IMPRESION
			$contratoList=$this->string2JSON($this->varGlobal['ORDENMANTENIMIENTO_CARGO_RESPONSABLES_TEMPLATE_ACTA']);
			$row['ORDENMANTENIMIENTO_ACTA_ENTREGA_TEMPLATE']=(in_array($row['tecnico_puesto'],$contratoList))?$this->varGlobal['ORDENMANTENIMIENTO_ACTA_ENTREGA']:$this->varGlobal['ORDENMANTENIMIENTO_ACTA_ENTREGA_CONTRATO'];
			$row['administrador_contrato']=(in_array($row['tecnico_puesto'],$contratoList))?'':$this->varGlobal['ORDENMANTENIMIENTO_ACTA_ENTREGA_TEMPLATE_CONTRATO'];
			
			// VALIDAR EL TIPO DE REPORTE
			if(isset($this->get['opt'])){
				
				// VARIABLE PARA EL LISTADO DE TRABAJOS A REALIZAR
				$row['tbodyWorksToDo']="";
				// LISTAR LOS TRABAJOS A REALIZARSE
				foreach($this->db->findAll($this->db->selectFromView('mantenimiento_trabajos',"WHERE fk_orden_id={$this->get['id']}")) as $idx=>$v){
					// INSERTAR REGISTRO
					$row['tbodyWorksToDo'].="<small>- {$v['trabajo_descripcion']}</small><br>";
				}
				
				// PARSE FECHA
				$row['orden_fecha_mantenimiento']=$this->setFormatDate($row['orden_fecha_mantenimiento'],'complete');
				// VARIABLE PARA EL LISTADO FACTURAS GENERADAS
				$row['tbodyInvoices']="";
				// FACTURAS INSCRITAS
				$row['facturas_total']=0;
				// LISTAR FACTURAS GENERADAS
				foreach($this->db->findAll($this->db->selectFromView('mantenimiento_facturas',"WHERE fk_orden_id={$this->get['id']}")) as $idx=>$v){
					// ITEM PARA REGISTRO DE FACTURAS
					$row['tbodyInvoices'].="<tr><td>".($idx+1)."</td><td>{$v['factura_numero']}</td><td>{$v['factura_tipo']}</td><td>$ {$v['factura_valor']}</td></tr>";
					// TOTAL DE FACTURAS INSCRITAS
					$row['facturas_total']+=$v['factura_valor'];
				}
				$row['facturas_total']=number_format($row['facturas_total'], 2, '.', '');
				// VALOR DE FACTURAS A TEXTO
				$row['facturas_total_text']=$this->setNumberToLetter($row['facturas_total'],true);
				// CUSTOM TEMPLATE
				$pdfCtrl->template='facturasMantenimientoById';
				
				// CONSULTAR SI EXISTEN ANEXOS FOTOGRAFICOS
				$strImg=$this->db->selectFromView('gallery',"WHERE fk_table='ordenesmantenimiento' and fk_id={$this->get['id']}");
				// VALIDAR EXISTENCIA DE ANEXOS
				if($this->db->numRows($strImg)>0){
				    // IMPRESION DE PLANTILLA
				    $row['ORDENMANTENIMIENTO_ACTA_ENTREGA_TEMPLATE']=preg_replace('/{{TEMPLATE_ANNEXXE_MAINTENANCEORDER}}/',$this->varGlobal['TEMPLATE_ANNEXXE_MAINTENANCEORDER'],$row['ORDENMANTENIMIENTO_ACTA_ENTREGA_TEMPLATE']);
				    // INICIALIZACION DE VARIABLE
				    $row['annexxeimg']='';
				    // IMPRIMIR LISTADO
				    foreach($this->db->findAll($strImg) as $v){
				        $row['annexxeimg'].="
                            <tr>
                				<td>
                					<img style='width:300px;max-height:300px;margin:8px;' src=src/img/gallery/{$v['media_nombre']} />
                					<h3 class='text-uppercase no-margin'>{$v['media_titulo']}</h3>
                					<span><i>{$v['media_descripcion']}</i></span>
                				</td>
                			</tr>
                        ";
				    }
				}else{
				    $row['TEMPLATE_ANNEXXE_MAINTENANCEORDER']='';
				}
				
				
			}else{
			    
				// PARSE FECHA
				$deliveryDay=$this->setFormatDate($row['orden_fecha_entrega'],'d');
				$deliveryMonth=$this->setFormatDate($row['orden_fecha_entrega'],'short');
				$row['orden_fecha_entrega_alt']="{$deliveryDay} días del mes de {$deliveryMonth}";
				
				// VARIABLE PARA EL LISTADO DE TRABAJOS A REALIZAR
				$row['mantenimiento_trabajos']="";
				// LISTAR LOS TRABAJOS A REALIZARSE
				$workMaintenances=$this->db->findAll($this->db->selectFromView('mantenimiento_trabajos',"WHERE fk_orden_id={$this->get['id']}"));
				$suggestedMaintenances=$this->db->findAll($this->db->selectFromView('mantenimiento_sugeridos',"WHERE fk_orden_id={$this->get['id']}"));
				foreach(array_merge($workMaintenances,$suggestedMaintenances) as $idx=>$v){ $row['mantenimiento_trabajos'].="<li>{$v['trabajo_descripcion']}</li>"; }
				
				// CONCATENAR LISTADO
				$row['mantenimiento_trabajos']="<ol>{$row['mantenimiento_trabajos']}</ol>";
				// CUSTOM TEMPLATE
				$pdfCtrl->template=$config['reporte_template'];
				
			}
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}