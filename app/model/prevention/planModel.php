<?php namespace model\prevention;
use model as mdl;
use api as app;

class planModel extends mdl\personModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='planesemergencia';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * LISTA DE INSPECTORES DE UNA INSPECCION
	 */
	private function requestInspectorsById($glptId){
		// MODELO DE DATOS
		$data=$this->db->viewById($glptId,$this->entity);
		$data['local']=$this->db->findById($data['local_id'],'locales');
		// VALIDAR TRANSICIÓN DE ETADOS
		if($data['plan_estado']=='INGRESADO') $data['plan_estado']='ASIGNADO';
		// OBJETO PARA PERSONAL SELECCIONADO
		$data['tb']=$this->entity;
		$data['asignInspector']=true;
		$data['selected']=array();
		// ID DE USUARIOS COMO INSPECTORES
		$inspectorsList=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE (fk_perfil_id=6 OR fk_perfil_id=5) AND usuario_estado='ACTIVO'");
		// LISTA DE INSPECTORES
		$data['list']=$this->db->findAll($this->db->selectFromView('vw_personal',"WHERE fk_persona_id IN ($inspectorsList) AND ppersonal_estado='EN FUNCIONES'"));
		
		// INSPECTORES YA INGRESADOS
		foreach($this->db->findAll($this->db->selectFromView('planesemergencia_inspector',"WHERE fk_plan_id={$glptId}")) AS $v){
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
		$row['local']=$this->db->viewById($row['fk_local_id'],'locales');
		// RETORNAR DATOS
		return $row;
	}
	
	/*
	 * BUSCAR REGISTRO POR CÓDIGO DE SOLICITUD
	 */
	private function requestByCode($code){
		// GENERAR CONSULTA POR CÓDIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(plan_codigo)=UPPER('$code')");
		// VERIFICAR SI EXISTE EL PLAN DE EMERGENCIA
		if($this->db->numRows($str)<1) $this->getJSON("La solicitud <b>#$code</b> no ha sido registrada, favor verifiqe su código y vuelva a intentar!");
		// OBTENER DATOS DEL PERMISO
		$row=$this->db->findOne($str);
		// VERIFICAR EL ESTADO DEL POF
		if(in_array($row['plan_estado'],['PENDIENTE','SUSPENDIDO'])){
			$row['plan_estado']='INGRESADO';
			// MODAL A DESPLEGAR
			$row['modal']='Input';
			$row['tb']='planesemergencia';
			$row['REQUIRED_FORM']=$this->testCollectionRequestSpecies($this->entity);
		}elseif(in_array($row['plan_estado'],['INGRESADO'])){
			// VALIDAR PERMISO PARA ASIGNAR INSPECTOR
			if(!in_array(6072,$this->getAccesRol())) $this->getJSON("La solicitud <b>#$code</b> ya ha sido ingresada, por favor direccione el trámite con quien corresponda para que se asigne un inspector!");
			// EDICIÓN DE REGISTRO PARA MODAL
			$row['plan_estado']='ASIGNADO';
			// MODAL A DESPLEGAR
			$row['modal']='planesemergenciainspector';
			$row['tb']=$this->entity;
		}elseif(in_array($row['plan_estado'],['ASIGNADO','REVISION','CORRECCION'])){
			// VALIDAR PERMISO PARA ASIGNAR INSPECTOR
			if(!in_array(6073,$this->getAccesRol())) $this->getJSON("La solicitud <b>#$code</b> ya ha sido ASIGNADA, por favor direccione el trámite con quien corresponda para que se asigne un inspector!");
			// MODAL A DESPLEGAR
			$row['modal']="Planesemergencia";
			$row['tb']=$this->entity;
		}elseif($row['plan_estado']=='REVISADO'){
			// VALIDAR PERMISO PARA GENERAR ORDEN DE COBRO
			if(!in_array(60711,$this->getAccesRol())) $this->getJSON("La solicitud <b>#$code</b> ya ha sido ingresada, por favor direccione el trámite con quien corresponda para que genere la orden de cobro!");
			
			// VALIDAR SI PROCEDE A PAGO
			$payment=$this->validateEntityExemptPayment($row['fk_entidad_id'],$this->entity,$row['plan_id']);
			// VALIDAR ROL PARA APROBAR TRAMITES SIN PAGO
			if($payment['estado']=='info') {$row['plan_estado']='APROBADO'; $this->getJSON((!in_array(607111,$this->getAccesRol()))?$this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE']:$payment); }
			
			// DATOS PARA GENERAR ORDEN DE COBRO
			$row['entity']='planesemergencia';
			$row['entityId']=$row['plan_id'];
			// MODAL A DESPLEGAR
			$row['modal']='Generarordenescobro';
			$row['edit']=false;
		}elseif(in_array($row['plan_estado'],['ORDEN DE COBRO GENERADA'])){
			$this->getJSON("El Plan de emergencia <b>#$code</b> se encuentra pendiente de pago!");
		}elseif(in_array($row['plan_estado'],['PAGADO'])){
			// VALIDAR PERMISO PARA GENERAR ORDEN DE COBRO
			if(!in_array(60712,$this->getAccesRol())) $this->getJSON("La solicitud <b>#$code</b> ya ha sido pagada, por favor direccione el trámite con quien corresponda para que apruebe el plan!");
			$row['plan_estado']='APROBADO';
			// MODAL A DESPLEGAR
			$row['modal']='planesemergencia';
		}elseif(in_array($row['plan_estado'],['APROBADO'])){
			$this->getJSON("El Plan de emergencia <b>#$code</b> ya ha sido revisado y aprobado!");
		}else{
			$this->getJSON("El Plan de emergencia <b>#$code</b> ya ha sido aprobado!");
		}
		// RETORNAR DATOS
		return array_merge($row,$this->db->viewById($row['fk_local_id'],'locales'));
	}
	
	/*
	 * BUSCAR PLAN DE EMERGENCIA DE UN ESTABLECIMIENTO
	 */
	private function requestByLocal($localId){
		// GENERAR MODELO DE CONSULTA
		$model=array();
		// BUSCAR ESTABLECIMIENTO
		$model['local']=$this->db->findById($localId,'locales');
		
		// MODELO DEL PLAN DE EMERGENCIA
		$model['plan']=array();
		
		// RETORNAR MODELO DE CONSULTA
		return $model;
	}
	
	/*
	 * CONSULTAR REGISTRO
	 * + system: CONSULTAR EL PLAN POR CODIGO PARA INGRESO A CBSD
	 */
	public function requestPlans(){
		// CONSULTAR POR ID - INGRESO EN CBSD
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO - INGRESO EN CBSD
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// INSPECTORES POR ID
		elseif(isset($this->post['type']) && $this->post['type']=='getInspectors') $json=$this->requestInspectorsById($this->post['plan_id']);
		// CONSULTAR DATOS DE LOCAL
		elseif(isset($this->post['localId'])) $json=$this->db->findById($this->post['localId'],'locales');
		// CONSULTAR DATOS DE LOCAL
		elseif(isset($this->post['localId'])) $json=$this->requestByLocal($this->post['localId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("ok",true,$json));
	}
	
	
	/*
	 * ACTUALIZAR DATOS DE LOCAL
	 */
	private function updateLocals($post){
		// VALIDAR Y CARGAR ARCHIVO
		$post=$this->uploaderMng->decodeBase64ToImg($post,'locales',false,$post['local_id']);
		// GENERAR STRING DE ACTUALIZACIÓN Y EJECUTAR SENTENCIA
		$this->db->executeTested($this->db->getSQLUpdate($post,'locales'));
		
		// SET MAPA DE COORDENADAS
		$this->setMapCoordinates('locales',$post['local_id'],$post);
		// SET GEOJSON
		if(isset($post['coordenada_marks'])) $this->setGeoJSON('locales',$post['local_id'],$post['coordenada_marks']);
		
		// CREAR TIPO DE REGISTRO
		$post['plan_tipo']=(isset($post['plan_tipo']))?$post['plan_tipo']:'FORMATO PROPIO';
		
		// GENERAR MODELO DE REGISTRO DE PLAN 
		$plan=array(
			'fk_local_id'=>$post['local_id'],
			'plan_tipo'=>$post['plan_tipo'],
			'plan_area'=>$post['local_area'],
			'plan_aforo'=>$post['local_ocupantes'],
			'plan_plantas'=>$post['local_pisos']+$post['local_subsuelos'],
			'plan_clavecatastral'=>$post['local_clavecatastral'],
			'jtp_solicitud'=>$this->varGlobal['RUPIF_PPERSONAL_ID']
		);
		
		// RETORNAR DATA->PLAN
		return $plan;
	}
	
	/*
	 * INGRESO DE PLANES DE EMERGENCIA PARA LOCALES
	 */
	public function insertEntity(){
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		
		// ACTUALIZAR DATOS DE LOCAL Y RETORNAR MODELO DE PLAN DE EMERGENCIA
		$plan=$this->updateLocals((isset($this->post['local']))?$this->post['local']:$this->post);
		
		// FLAG PARA INGRESO DE PLAN
		$newPlan=true;
		
		// STRING PARA CONSULTAR SI YA SE HA REGISTRADO EL PLAN
		$str=$this->db->selectFromView($this->entity,"WHERE fk_local_id={$this->post['local_id']} ORDER BY plan_id DESC LIMIT 1");
		// VERIFICAR SI EL REGISTRO EXISTE
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PLAN
			$row=$this->db->findOne($str);
			
			// VALIDAR ESTADOS DEL PLAN
			if($row['plan_estado']=='APROBADO'){
				// ACTUALIZAR ESTADO DE REGISTRO
				$row['plan_estado']='VENCIDO';
				$row['plan_observacion']="ACTUALIZACIÓN DE PLAN DE EMERGENCIA";
				// GENERAR STRING DE ACTUALIZACIÓN Y EJECUTAR CONSULTA
				$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}elseif($row['plan_estado']=='VENCIDO'){
				
				// GENERAR UNA NUEVA SOLICITUD
				
			}else{
				// GENERAR STRING DE ACTUALIZACIÓN Y EJECUTAR CONSULTA
			    $sql=$this->db->executeTested($this->db->getSQLUpdate(array_merge($row,$plan),$this->entity));
				// NO SE PUEDE GENERAR UN NUEVO PLAN
				$sql=$this->setJSON("Los datos de su establecimiento/actividad económica <b>{$this->post['local_nombrecomercial']}</b> se ha actualizado correctamente.<br><br>A continuación deberá completar los datos del <b>PLAN DE AUTOPROTECCIÓN</b>.",true);
				// CANCELAR EL INGRESO DE UN PLAN NUEVO
				$newPlan=false;
			}
		}
		
		// VALIDAR EL INGRESO DE UN NUEVO PLAN
		if($newPlan){
			// GENERAR STRING DE INGRESO
			$sql=$this->db->executeTested($this->db->getSQLInsert($plan,$this->entity));
			// DATOS DE PLAN REGISTRADO
			$plan=$this->db->getLastEntity($this->entity);
			// ANEXAR MENSAJE DE NUEVO PLAN CREADO
			$sql['mensaje'].="<br><br>Favor, a continuación complete los datos de su <b>Plan de AutoProtección</b> registrado bajo el número de solicitud <b>{$plan['plan_codigo']}</b>.";
			// OBTENER ID DE NUEVO PLAN DE EMERGENCIA
			$sql['data']=array('id'=>$this->db->getLastID($this->entity));
		}
		
		// CERRAR TRANSACCIÓN y RETORNAR DATOS A UI
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * REGISTRO DE NUEVAS SOLICITUDES DESDE MODULO DE PREVENCION
	 */
	public function insertPlan(){
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		
		// VARIABLE PARA REGISTRAR NUEVO PLAN
		$newPlan=true;
		
		// ACTUALIZAR DATOS DE LOCAL Y RETORNAR MODELO DE PLAN DE EMERGENCIA
		$plan=$this->updateLocals($this->post['local']);
		
		// STRING PARA CONSULTAR SI YA SE HA REGISTRADO EL PLAN
		$str=$this->db->selectFromView($this->entity,"WHERE fk_local_id={$this->post['local']['local_id']} ORDER BY plan_id DESC LIMIT 1");
		// VERIFICAR SI EL REGISTRO EXISTE
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PLAN
			$row=$this->db->findOne($str);
			// VALIDAR ESTADOS DEL PLAN
			if($row['plan_estado']=='APROBADO'){
				// ACTUALIZAR ESTADO DE REGISTRO
				$row['plan_estado']='VENCIDO';
				$row['plan_observacion']="ACTUALIZACIÓN DE PLAN DE EMERGENCIA";
				// GENERAR STRING DE ACTUALIZACIÓN Y EJECUTAR CONSULTA
				$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
			}elseif($row['plan_estado']=='VENCIDO'){
				
			    // VALIDAR QUE NO HAYAN OTROS REGISTROS PENDIENTES
			    
			}else{
				// CAMBIAR EL ESTADO DE LA VARIABLE PARA EL INGRESO
				$newPlan=false;
				// MENSAJE POR DEFECTO
				$sql=$this->getJSON("No puede realizar el ingreso de un nuevo plan...");
			}
		}
		// VALIDAR EL INGRESO DE UN NUEVO PLAN
		if($newPlan){
			// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
			$plan['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
			
			// GENERAR STRING DE INGRESO
			$sql=$this->db->executeTested($this->db->getSQLInsert($plan,$this->entity));
			// OBTENER ID DE NUEVO PLAN DE EMERGENCIA
			$sql['data']=array('id'=>$this->db->getLastID($this->entity));
		}
		
		// CERRAR TRANSACCIÓN y RETORNAR DATOS A UI
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * INSPECTORES
	 */
	private function setPersonal($processId,$list){
		// VALIDAR LONGITUD DE LISTADO DE PERSONAL
		if(count($list)>0){
			// ELIMINAR PERSONAL ASIGNADO
			$this->db->executeTested($this->db->getSQLDelete('planesemergencia_inspector',"WHERE fk_plan_id={$processId}"));
		}
		// RECORRER LISTA DE PERSONAL
		foreach($list as $personalId){
			// GENERAR MODELO TEMPORAL
			$aux=array(
				'fk_plan_id'=>$processId,
				'fk_personal_id'=>$personalId
			);
			// INGRESAR PERSONAL PARA REVISIÓN
			$this->db->executeTested($this->db->getSQLInsert($aux,'planesemergencia_inspector'));
		}
	}
	
	/*
	 * EDICIÓN DE REGISTRO DE PLAN DE EMERGENCIA
	 * ai: 
	 * system: INGRESO DE PROCESO A CBSD
	 */
	public function updatePlans(){
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDACIÓN DE ESTADOS
		if(in_array($this->post['plan_estado'],['PENDIENTE','REVISION','CORRECCION','REVISADO','ORDEN DE COBRO GENERADA'])){
			// ACTUALIZAR DATOS DE LOCAL Y OBTENER DATOS DE PLAN
			$plan=$this->updateLocals($this->post['local']);
			
			// VALIDAR TIPO DE PLAN
			$plan['plan_tipo']=(isset($this->post['plan_tipo']))?$this->post['plan_tipo']:$plan['plan_tipo'];
			
			// ACTUALIZAR DATOS DE PLAN DE EMERGENCIA
			$this->post=array_merge($this->post,$plan);
			// OBTENER DATOS ANTERIORES
			$oldData=$this->db->findById($this->post['plan_id'],$this->entity);
			// ACTUALIZAR EPNDIENTE A INGRESADO -> DESDE PERFIL DE INSPECTOR
			if($oldData['plan_estado']=='PENDIENTE' && in_array($this->post['plan_estado'],['REVISION','CORRECCION','REVISADO'])){
				// DATOS DE ASIGNACION
				$oldData['plan_estado']='ASIGNADO';
				$oldData['fk_inspector_id']=app\session::get();
				// ACTUALIZAR DATOS DE PLAN -> REALIZAR INGRESO
				$this->db->executeTested($this->db->getSQLUpdate($oldData,$this->entity));
			}
		}
		
		// VALIDAR ESTADOS PARA ACCIONES
		if($this->post['plan_estado']=='INGRESADO'){
			// REALIZAR INGRESO DE PLAN DE EMERGENCIA
			if($this->testCollectionRequestSpecies($this->entity)=='SI') $this->post['plan_solicitud']=$this->testRequestNumber($this->entity,$this->post['plan_solicitud'],$this->post['plan_id']);
		}
		
		// REGISTRAR EL INGRESO DE PERSONAL PARA REVISIÓN
		if(isset($this->post['asignInspector']) && $this->post['asignInspector']){
			// INVOCAR MÉTODO PARA REGISTRO DE PERSONAL
			$this->setPersonal($this->post['plan_id'],$this->post['selected']);
		}
		
		// GENERAR CONSULTA SQL Y EJECUTAR
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// INSERTAR ID DE REGISTRO
		$sql['data']=array('id'=>$this->post['plan_id']);
		// CERRAR TRANSACCIÓN y RETORNAR DATOS A UI
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ELIMINAR ANEXOS
	 */
	public function deleteAnnexe(){
	    
	    // OBTENER INFORMACION DE ANEXO
	    $src=$this->db->findById($this->post['galleryId'],'gallery');
	    $plan=$this->db->findById($this->post['planId'],$this->entity);
	    
	    // MENSAJE POR DEFECTO
	    $sql=$this->setJSON("Transacción completada con éxito",true);
	    
	    // ELIMINAR REGISTRO DE CARPETA
	    $json=$this->uploaderMng->deletSingleFile($src['media_nombre'],$plan,$this->entity);
	    
	    // ELIMINAR REGISTRO
	    if($json['estado']) $sql=$this->db->executeTested($this->db->getSQLDelete('gallery',"WHERE fk_table='planesemergencia' AND media_id={$this->post['galleryId']}"));
	    
	    // UNIR MENSAJES
	    if($json['estado'] && $sql['estado']) $sql['mensaje']="Registro eliminado exitosamente!";
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
	}
	
	
	/*
	 * IMPRESION DE PLAN DE AUTOPROTECCION
	 */
	private function printFactors($planId){
		// DECLARACION DE MODELO
		$data=array(
			'3_INTERNOS'=>'',
			'3_SOLUCION_INTERNOS'=>'',
			'3_EXTERNOS'=>'',
			'3_SOLUCION_EXTERNOS'=>'',
			'4_MESERI_X'=>'',
			'4_MESERI_Y'=>'',
			'5_PREVENCION'=>'',
			'6_MANTENIMIENTO'=>''
		);
		
		// FACTORES INTERNOS
		$data['3_INTERNOS']="";
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_recursos',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_clasificacion='FACTORES INTERNOS'")) as $val){
			// VALOR A IMPRIMIR
			$value=(in_array($val['recurso_tipo_formulario'],['DESCRIPCION','SELECCIONPROPIA']))?$val['factor_descripcion']:$val['factor_aplica'];
			// DECLARACION DE FILA
			$data['3_INTERNOS'].="<tr><td><b>{$val['recurso_nombre']}</b><br><br>{$value}</td></tr>";
		}
		
		// POSIBLE SOLUCION - FACTORES INTERNOS
		$data['3_SOLUCION_INTERNOS']="";
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_recursos',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_clasificacion='POSIBLE SOLUCION A RIESGOS Y PELIGROS INTERNOS'")) as $val){
			// VALOR A IMPRIMIR
			$value=($val['recurso_tipo_formulario']=='DESCRIPCION')?$val['factor_descripcion']:$val['factor_aplica'];
			// DECLARACION DE FILA
			$data['3_SOLUCION_INTERNOS'].="<tr><td>{$val['recurso_nombre']}</td><td>{$value}</td></tr>";
		}
		
		// FACTORES EXTERNOS
		$data['3_EXTERNOS']="";
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_recursos',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_clasificacion='FACTORES EXTERNOS'")) as $val){
			// VALOR A IMPRIMIR
			$value=($val['recurso_tipo_formulario']=='DESCRIPCION')?$val['factor_descripcion']:$val['factor_aplica'];
			// DECLARACION DE FILA
			$data['3_EXTERNOS'].="<tr><td>{$val['recurso_nombre']}</td><td>{$value}</td></tr>";
		}
		
		// POSIBLE SOLUCION - FACTORES EXTERNOS
		$data['3_SOLUCION_EXTERNOS']="";
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_recursos',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_clasificacion='POSIBLE SOLUCION A RIESGOS Y PELIGROS EXTERNOS'")) as $val){
			// VALOR A IMPRIMIR
			$value=($val['recurso_tipo_formulario']=='DESCRIPCION')?$val['factor_descripcion']:$val['factor_aplica'];
			// DECLARACION DE FILA
			$data['3_SOLUCION_EXTERNOS'].="<tr><td>{$val['recurso_nombre']}</td><td>{$value}</td></tr>";
		}
		
		// 4. MESERI - X
		$temp=array();
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_meseri',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_tipo_formulario='FACTORES PROPIOS DE LAS INSTALACIONES (X)'")) as $val){
			// IMPRESION DE CABECERA
			if(!in_array($val['recurso_clasificacion'],$temp)){
				$temp[]=$val['recurso_clasificacion'];
				$data['4_MESERI_X'].="<tr class='subheader'><td colspan='3' class='text-center text-bold'>{$val['recurso_clasificacion']}</td></tr>";
			}
			// VALOR DE COEFICIENTE
			$value=$this->string2JSON($val['meseri_coeficiente']);
			// DECLARACION DE FILA
			$data['4_MESERI_X'].="<tr><td>{$val['recurso_nombre']}</td><td>{$value['text']}</td><td>{$value['val']}</td></tr>";
		}
		
		// 4. MESERI - Y
		$temp=array();
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_meseri',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_tipo_formulario='FACTORES DE PROTECCION (Y)'")) as $val){
			// IMPRESION DE CABECERA
			if(!in_array($val['recurso_clasificacion'],$temp)){
				$temp[]=$val['recurso_clasificacion'];
				$data['4_MESERI_Y'].="<tr class='subheader'><td colspan='3' class='text-center text-bold'>{$val['recurso_clasificacion']}</td></tr>";
			}
			// VALOR DE COEFICIENTE
			$value=$this->string2JSON($val['meseri_coeficiente']);
			// DECLARACION DE FILA
			$data['4_MESERI_Y'].="<tr><td>{$val['recurso_nombre']}</td><td>{$value['text']}</td><td>{$value['val']}</td></tr>";
		}
		
		// 5. PREVENCION
		$temp=array();
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_prevencion',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_clasificacion_prevencion='PREVENCIÓN Y CONTROL DE RIESGOS' ORDER BY recurso_id")) as $val){
			// IMPRESION DE CABECERA
			if(!in_array($val['recurso_clasificacion'],$temp)){
				$temp[]=$val['recurso_clasificacion'];
				$data['5_PREVENCION'].="<tr class='subheader'><td colspan='6' class='text-center text-bold'>{$val['recurso_clasificacion']}</td></tr>";
			}
			// DECLARACION DE FILA
			if(!in_array($val['prevencion_cantidad'],array('0',null,'NA','SN'))){
			    if($val['prevencion_mantenimiento']=='NO APLICA') $data['5_PREVENCION'].="<tr><td><b>{$val['recurso_articulo']}</b><br>{$val['recurso_nombre']}<br>({$val['recurso_unidad_medida']})</td><td>{$val['prevencion_cantidad']}</td><td>{$val['prevencion_estado']}</td><td>{$val['prevencion_revision']}</td><td>{$val['prevencion_mantenimiento']}</td><td></td></tr>";
			    else $data['5_PREVENCION'].="<tr><td><b>{$val['recurso_articulo']}</b><br>{$val['recurso_nombre']}<br>({$val['recurso_unidad_medida']})</td><td>{$val['prevencion_cantidad']}</td><td>{$val['prevencion_estado']}</td><td>{$val['prevencion_revision']}</td><td>{$val['prevencion_mantenimiento']}</td><td>{$val['prevencion_proximo_mantenimiento']}</td></tr>";
			}else{
                $data['5_PREVENCION'].="<tr><td><b>{$val['recurso_articulo']}</b><br>{$val['recurso_nombre']}<br>({$val['recurso_unidad_medida']})</td><td>{$val['prevencion_cantidad']}</td><td></td><td></td><td></td><td></td></tr>";
			}
		}
		
		// 6. MANTENIMIENTO
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_mantenimiento',['recursos'],"WHERE fk_plan_id={$planId} AND recurso_clasificacion_prevencion='MANTENIMIENTO'")) as $val){
			// IMPRESION DE DETALLE DE MANTENIMIENTO
			$_6_MANTENIMIENTO_APLICA="";
			// VALIDAR SI SE APLICA O NO EL MANTENIMIENTO
			if($val['mantenimiento_aplicacion']=='SI APLICA'){
				// VALIDAR RESPONSABLE DEL MANTENIMIENTO
				if($val['mantenimiento_responsable_tipo']=='PROFESIONAL O EMPRESA CONTRATADO EXTERNAMENTE'){
					
					// VALIDAR QUE SE HAYA REGISTRADO EL RESPONSABLE DEL MANTENIMIENTO
					if(in_array($val['mantenimiento_responsable_id'],[null,''])) $this->getJSON("No se ha registrado los datos del responsable de mantenimiento {$val['recurso_nombre']} (Paso 6).");
					
					// BUSCAR RESPONSBALE DE MANTENIMIENTO
					$entity=$this->db->viewById($val['mantenimiento_responsable_id'],'entidades');
					// IMPRESION DE RESPONSABLE
					$val['mantenimiento_responsable']="<b>Razón social: </b>{$entity['entidad_razonsocial']}<br>";
					$val['mantenimiento_responsable'].="<b>RUC: </b>{$entity['entidad_razonsocial']}<br>";
					$val['mantenimiento_responsable'].="<b>Representante legal: </b>{$entity['representantelegal_nombre']}";
				}else{
					$val['mantenimiento_responsable']=$val['mantenimiento_responsable_tipo'];
				}
				// IMPRESION DE REGISTROS
				$_6_MANTENIMIENTO_APLICA="<tr><td>Actividades realizadas</td><td>{$val['mantenimiento_actividad']}</td></tr>
				<tr><td>Fecha de mantenimiento</td><td>{$val['mantenimiento_fecha']}</td></tr>
				<tr><td>Responsable del mantenimiento (persona/empresa)</td><td>{$val['mantenimiento_responsable']}</td></tr>";
			}
			// DECLARACION DE FILA
			$data['6_MANTENIMIENTO'].="<table class='table table-bordered text-uppercase'>
			<col style='width:35%;text-weight:bold'>
			<col style='width:65%;'>
			<thead><tr><th colspan=2>{$val['recurso_nombre']}</th></tr></thead>
			<tbody>
			<tr><td>¿Aplica este mantenimiento?</td><td>{$val['mantenimiento_aplicacion']}</td></tr>
			{$_6_MANTENIMIENTO_APLICA}
			</tbody>
			</table>
			<BR>";
			
		}
		
		// RETORNAR MODELO
		return $data;
	}
	
	/*
	 * HISTORIAL DE LA EMPRESA CON EL CUERPO DE BOMBEROS
	 */
	private function printHistoryByLocalId($localId,$entityId){
		// GENERAR MODELO DE CONSULTA;
		$data=$this->varGlobal['SELFPROTECTION_9_HISTORY'];
		
		// MENSAJE POR DEFECTO
		$strDefault='<tr><td colspan="2">No se han registrado a la fecha</td></tr>';
		
		// CONSULTAR PERMISOS DE ESTABLECIEMIENTO
		$str=$this->db->selectFromView('vw_permisos',"WHERE local_id={$localId} ORDER BY permiso_fecha DESC");
		if($this->db->numRows($str)<1){ $data=preg_replace('/{{permisosLista}}/',$strDefault,$data); }
		else{
			$tr="";
			foreach($this->db->findAll($str) as $v){ $tr.="<tr><td>{$v['permiso_fecha']}</td><td>{$v['codigo_per']}</td></tr>"; }
			$data=preg_replace('/{{permisosLista}}/',$tr,$data);
		}
		
		// CONSULTAR CAPACITACIONES DE LA EMPRESA
		$str=$this->db->selectFromView('vw_training',"WHERE fk_entidad_id={$entityId} AND capacitacion_estado IN ('DESPACHADA','EVALUADA') ORDER BY capacitacion_fecha DESC");
		if($this->db->numRows($str)<1){ $data=preg_replace('/{{capacitacionesLista}}/',$strDefault,$data); }
		else{
			$tr="";
			foreach($this->db->findAll($str) as $v){ $tr.="<tr><td>{$v['capacitacion_fecha']}</td><td>{$v['tema_nombre']}</td></tr>"; }
			$data=preg_replace('/{{capacitacionesLista}}/',$tr,$data);
		}
		
		// CONSULTAR SIMULACROS DE LA EMPRESA
		$str=$this->db->selectFromView('vw_simulacros',"WHERE fk_entidad_id={$entityId} AND simulacro_estado IN ('DESPACHADA')");
		if($this->db->numRows($str)<1){ $data=preg_replace('/{{simulacrosLista}}/',$strDefault,$data); }
		else{
			$tr="";
			foreach($this->db->findAll($str) as $v){ $tr.="<tr><td>{$v['simulacro_fecha']}</td><td>{$v['simulacro_tema']}</td></tr>"; }
			$data=preg_replace('/{{simulacrosLista}}/',$tr,$data);
		}
		
		// CONSULTAR STANDS DE LA EMPRESA
		$str=$this->db->selectFromView('vw_stands',"WHERE fk_entidad_id={$entityId} AND stand_estado IN ('DESPACHADA')");
		if($this->db->numRows($str)<1){ $data=preg_replace('/{{standsLista}}/',$strDefault,$data); }
		else{
			$tr="";
			foreach($this->db->findAll($str) as $v){ $tr.="<tr><td>{$v['stand_fecha']}</td><td>{$v['stand_tema']}</td></tr>"; }
			$data=preg_replace('/{{standsLista}}/',$tr,$data);
		}
		
		// RETORNAR INFORMACION
		return $data;
	}
	
	/*
	 * IMPRESION DE BRIGADAS
	 */
	private function printBrigadesByLocal($localId){
		// MODELO DE DATOS
		$data=$this->varGlobal['SELFPROTECTION_BRIGADES_TEMPLATE'];
		
		// IMPRESION DE LISTADO DE PERSONAL
		$listado_personal="";
		// LISTADO DE PERSONAL
		$str=$this->db->selectFromView('vw_local_empleados',"WHERE fk_local_id={$localId}");
		
		// VALIDAR LISTADO DE PERSONAL
		if($this->db->numRows($str)<1) $this->getJSON("No se ha registrado al personal de la empresa (Paso 8).");
		// IMPRESION DE LISTADO
		foreach($this->db->findAll($str) as $idx=>$row){
			$index=$idx+1;
			$listado_personal.="<tr><td>{$index}</td><td>{$row['persona_doc_identidad']}</td><td>{$row['persona_apellidos']}</td><td>{$row['persona_nombres']}</td><td>{$row['empleado_funcion']}</td></tr>";
		}
		
		// BRIGADISTAS
		$brigadas="";
		// IMPRESION DE BRIGADAS
		$str=$this->db->selectFromView('vw_brigadas',"WHERE fk_local_id={$localId}");
		// VALIDAR LISTADO DE BRIGADAS
		if($this->db->numRows($str)<1) $this->getJSON("No se ha registrado las brigadas de la empresa (Paso 8).");
		// IMPRESION DE BRIGADAS
		foreach($this->db->findAll($str) as $idx=>$row){
			
			// SUBALTERNO DE BRIGADA
			if($row['fk_responsable_id']==null || $row['fk_responsable_id']<1) $this->getJSON("No se ha registrado al responsable de la brigada {$row['brigada_nombre']} (Paso 8).");
			
			// BRIGADISTAS
			$brigada_personal="";
			// CONSULTAR PERSONAL
			$str=$this->db->selectFromView('vw_brigadistas',"WHERE fk_brigada_id={$row['brigada_id']} ORDER BY persona_apellidos, persona_nombres");
			// VALIDAR LISTADO DE BRIGADAS
			if($this->db->numRows($str)<1) $this->getJSON("No se ha registrado el personal para la brigada {$row['brigada_nombre']} (Paso 8).");
			// IMPRESION DE BRIGADISTAS
			foreach($this->db->findAll($str) as $k=>$v){
				$index=$k+1;
				$brigada_personal.="<tr><td>{$index}</td><td>{$v['persona_doc_identidad']}</td><td>{$v['persona_apellidos']} {$v['persona_nombres']}</td></tr>";
			}
			
			// SUBALTERNO DE BRIGADA
			$junior="";
			if($row['fk_subalterno_id']!=null && $row['fk_subalterno_id']>0) $junior="<tr><td colspan='2' class='text-left text-bold text-uppercase'>Subalterno:</td><td>{$row['subalterno']}<br>C.C.: {$row['s_doc_identidad']}</td></tr>";
			
			// IMPRESION DE BRIGADAS
			$brigadas.="<table class='table table-bordered text-uppercase' style='font-size:9px;'>
							<col style='width:10%;text-align:center;'><col style='width:20%;'><col style='width:70%;'>
							<thead><tr><th colspan='3'>{$row['brigada_nombre']}</th></tr></thead>
							<tbody>
								<tr><td colspan='2' class='text-left text-bold text-uppercase'>Responsable de la brigada:</td><td>{$row['responsable']}<br>C.C.: {$row['r_doc_identidad']}</td></tr>
								{$junior}
								<tr><td colspan='3' class='text-center subheader'><b>PERSONAL DE LA BRIGADA</b></td></tr>
								{$brigada_personal}
							</tbody>
						</table><br>";
		}
		
		// IMPRESION DE PERSONAL
		$data=preg_replace('/{{listado_personal}}/',$listado_personal,$data);
		// IMPRESION DE BRIGADAS
		$data=preg_replace('/{{listado_brigadas}}/',$brigadas,$data);
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * VALIDACIÓN DE ANEXOS
	 */ 
	private function testAnnexes($plan){
		
		// VARIABLE PARA IMPRESION DE MENSAJES
		$msg="";
		// VARIABLE PARA VALIDAR ANEXOS
		$isEmpty=array('',null,'null','NO','NA');
		
		// CONSULTAR DATOS DE ENTIDAD Y REPRESENTANTE LEGAL
		$entity=$this->db->findById($plan['fk_entidad_id'],'entidades');
		// DATOS DE REPRESENTANTE LEGAL
		$agent=$this->db->findById($entity['fk_representante_id'],'personas');
		
		// VALIDAR DATOS DE LOCAL
		if(in_array($entity['entidad_sitioweb'],$isEmpty)) $msg.="<li>SUBIR ANEXO - COPIA DE RUC <B>(PASO 11)</B></li>";
		if(in_array($agent['persona_anexo_cedula'],$isEmpty)) $msg.="<li>SUBIR ANEXO - CEDULA DE REPRESENTANTE LEGAL <B>(PASO 11)</B></li>";
		
		// OBTENER DATOS DE LOCAL
		$local=$this->db->findById($plan['fk_local_id'],'locales');
		// VALIDAR DATOS DE LOCAL
		// if(in_array($local['local_impuestopredial'],$isEmpty)) $msg.="<li>SUBIR ANEXO - PAGO DEL IMPUESTO PREDIAL DEL ESTABLECIMIENTO <B>(PASO 11)</B></li>";
		
		// VALIDAR RESPONSABLE DEL PLAN
		if(in_array($plan['plan_sos'],['PROFESIONAL DE SEGURIDAD DE LA EMPRESA','PROFESIONAL CONTRATADO EXTERNAMENTE POR LA EMPRESA'])){
			// OBTENER DATOS DE FORMACION ACADÉMICA
			$sos=$this->db->findById($plan['profesional_sos_id'],'formacionacademica');
			// VALIDAR DATOS DE SOS
			if(in_array($sos['formacion_pdf'],$isEmpty)) $msg.="<li>SUBIR ANEXO - REGISTRO DE SENESCYT DEL PROFESIONAL RESPONSABLE DEL PLAN DE AUTOPROTECCION <B>(PASO 11)</B></li>";
		}
		
		// VALIDAR DATOS DE RESPONSABLE DEL TRAMITE
		if(in_array($plan['plan_responsable_tramite'],['DELEGADO DEL TITULAR','ADMINISTRADOR DE LA EMPRESA'])){
			// OBTENER DATOS DE RESPONSABLE DEL TRAMITE
			$person=$this->db->findById($plan['fk_responsable_tramite'],'personas');
			// VALIDAR DATOS DE SOS
			if(in_array($person['persona_anexo_cedula'],$isEmpty)) $msg.="<li>SUBIR ANEXO - COPIA DE CEDULA DEL RESPONSABLE DEL TRAMITE <B>(PASO 11)</B></li>";
			
		}
		
		// LISTADO DE MANTENIMIENTOS
		foreach($this->db->findAll($this->db->getSQLSelect('autoproteccion_mantenimiento',['recursos'],"WHERE fk_plan_id={$plan['plan_id']}")) as $resource){
			// VALIDAR SI NECESITA ANEXO
			if($resource['mantenimiento_aplicacion']=='SI APLICA'){
				// VALIDAR ANEXO DE MANTENIMIENTO
				if(in_array($resource['mantenimiento_adjunto'],$isEmpty)) $msg.="<li>SUBIR ANEXO - MANTENIMIENTOS REALIZADOS - {$resource['recurso_nombre']} <B>(PASO 11)</B></li>";
				// VALIDAR SI NECESITA ANEXO
				if($resource['mantenimiento_responsable_tipo']=='PROFESIONAL O EMPRESA CONTRATADO EXTERNAMENTE' && in_array($resource['mantenimiento_responsable_id'],$isEmpty)) $msg.="<li>REGISTRAR EL PROFESIONAL O EMPRESA EXTERNO QUE HA REALIZADO EL MANTENIMIENTO - {$resource['recurso_nombre']} <B>(PASO 11)</B></li>";
			}
		}
		
		// RETORNAR CONSULTA
		return $msg;
	}
	
	/*
	 * REPORTE REGISTROS INCOMPLETOS
	 */
	private function testCustomReport($pdfCtrl,$user,$row){
		// VARIABLE PARA IMPRESION DE MENSAJES
		$msg="";
		
		// REGISTRAR COORDENADAS DEL ESTABLECIMIENTO
		// if($row['local_imagen']==null || $row['local_imagen']=='default.png') $msg.="<li>REGISTRAR LAS COORDENADAS / FOTOGRAFIA DE FACHADA DEL ESTABLECIMIENTO/ACTIVIDAD ECONÓMICA <B>(PASO 1)</B></li>";
		// VALIDAR RESPONSABLE DEL PLAN DE AUTOPROTECCIÓN
		if($row['fk_sos_id']==null || $row['fk_sos_id']<1) $msg.="<li>REGISTRAR LOS DATOS DEL RESPONSABLE DE IMPLEMENTACIÓN DEL PLAN DE AUTOPROTECCION <B>(PASO 10)</B></li>";
		// REGISTRAR LOS DATOS DE FACTURACIÓN
		if($row['facturacion_id']==null || $row['facturacion_id']<1) $msg.="<li>REGISTRAR LOS DATOS PARA FACTURACIÓN <B>(PASO 10)</B></li>";
		
		// VALIDAR ANEXOS DE APROBACIÓN
		$msg.=$this->testAnnexes($row);
		
		// VALIDAR MENSAJE
		if($msg=="") return true;
		// RETORNAR CONSULTA
		return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * IMPRIMIR MODELO
	 */
	private function printModelPlan($data,$pdfCtrl,$config){
		
		// IMPRESION DE DATOS DE PLAN DE AUTOPROTECCION
		$pdfCtrl->loadSetting($data);
		
		// CUSTOM TEMPLATE
		$pdfCtrl->template='planAutoProteccionDraft';
	
		return '';
	}
	
	/*
	 * IMPRESION DE PLAN DE AUTOPROTECCIÓN
	 */
	public function printSelfProtection($pdfCtrl,$id,$config){
		// STRING DE CONSULTA PARA REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE plan_id={$id}");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
		    $row=$this->db->findOne($str);
			
		    // DATOS DEL ESTABLECIMIENTO
		    $local=$this->db->viewById($row['fk_local_id'],'locales');
			// DATOS DE ENTIDADES
			$entity=$this->db->findOne($this->db->selectFromView('vw_informacion_entidades',"WHERE entidad_id={$row['fk_entidad_id']}"));
			
			// IMPRESION DE MODELO
			if(isset($this->get['printType'])) return $this->printModelPlan(array_merge($row,$local,$entity),$pdfCtrl,$config);
			
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testCustomReport($pdfCtrl,$local['representantelegal_nombre'],array_merge($local,$row))) return '';
			
			// PARSE DATOS
			$row['plan_periodo']=$this->setFormatDate($row['plan_elaborado'],'short');
			
			/*
			 * DATOS ADICIONALES
			 */
			// PROFESIONAL DE SEGURIDAD Y SALUD OCUPACIONAL
			$pdfCtrl->loadSetting($this->db->findOne($this->db->selectFromView('vw_sos',"WHERE sos_id={$row['fk_sos_id']}")));
			// INFORMACIÓN DEL ESTABLECIMIENTO Y ENTIDAD
			$pdfCtrl->loadSetting(array_merge($entity,$local));
			// COORDENADAS DE ESTABLECIMIENTO
			$pdfCtrl->loadSetting($this->db->findOne($this->db->selectFromView('coordenadas',"WHERE coordenada_entidad='locales' AND coordenada_entidad_id={$row['fk_local_id']}")));
			
			// 3. FACTORES
			$pdfCtrl->loadSetting($this->printFactors($id));
			
			// 5. PREVENCION
			$row['prevencion_proximo_mantenimiento']=nl2br($row['prevencion_proximo_mantenimiento']);
			
			// 7. PROTOCOLO DE ALARMA Y COMUNICACIONES PARA EMERGENCIAS
			$row['alarma_otros']=nl2br($row['alarma_otros']);
			$row['alarma_comunicacion_internos']=nl2br($row['alarma_comunicacion_internos']);
			$row['alarma_comunicacion_externos']=nl2br($row['alarma_comunicacion_externos']);
			
			// 8. PROTOCOLOS DE INTERVENCIÓN ANTE EMERGENCIAS
			$row['intervencion_brigadas']=nl2br($row['intervencion_brigadas']);
			$row['intervencion_zonaseguridad']=nl2br($row['intervencion_zonaseguridad']);
			$row['intervencion_viasevacuacion']=nl2br($row['intervencion_viasevacuacion']);
			$row['intervencion_puntoencuentro']=nl2br($row['intervencion_puntoencuentro']);
			$row['autoproteccion_conformacion_brigadas']=($local['local_aforo_planta']>=$this->varGlobal['SELFPROTECTION_AFORO_BRIGADAS'])?$this->printBrigadesByLocal($local['local_id']):'';
			
			// 9. EVACUACIÓN
			$row['evacuacion_decisiones']=nl2br($row['evacuacion_decisiones']);
			$row['evacuacion_procedimientos']=nl2br($row['evacuacion_procedimientos']);
			
			// 9. HISTORIAL CON EL CB
			$row['evacuacion_historial']=$this->printHistoryByLocalId($local['local_id'],$local['fk_entidad_id']);
			
			// 10. PROCEDIMIENTOS PARA LA IMPLANTACIÓN DEL PLAN DE EMERGENCIA
			$row['procedimiento_senializacion']=nl2br($row['procedimiento_senializacion']);
			$row['procedimiento_cartelesinformativos']=nl2br($row['procedimiento_cartelesinformativos']);
			$row['procedimiento_capacitaciones']=nl2br($row['procedimiento_capacitaciones']);
			$row['procedimiento_simulacros']=nl2br($row['procedimiento_simulacros']);
			
			// IMPRESION DE DATOS DE PLAN DE AUTOPROTECCION
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_entidad_id'],'entidades'));
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,'planautoproteccion');
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe la solicitud</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*
	 * REPORTE REGISTROS INCOMPLETOS
	 */
	private function testPrintRequest($pdfCtrl,$user,$row){
	    // VARIABLE PARA IMPRESION DE MENSAJES
	    $msg="";
	    // VARIABLE PARA VALIDAR ANEXOS
	    $isEmpty=array('',null,'null','NO','NA');
	    
	    // REGISTRAR COORDENADAS DEL ESTABLECIMIENTO
	    if($row['local_imagen']==null || $row['local_imagen']=='default.png') $msg.="<li>REGISTRAR LAS COORDENADAS / FOTOGRAFIA DE FACHADA DEL ESTABLECIMIENTO/ACTIVIDAD ECONÓMICA</li>";
	    // VALIDAR RESPONSABLE DEL PLAN DE AUTOPROTECCIÓN
	    if($row['fk_sos_id']==null || $row['fk_sos_id']<1) $msg.="<li>REGISTRAR LOS DATOS DEL RESPONSABLE DE IMPLEMENTACIÓN DEL PLAN DE AUTOPROTECCION</li>";
	    // REGISTRAR LOS DATOS DE FACTURACIÓN
	    if($row['facturacion_id']==null || $row['facturacion_id']<1) $msg.="<li>REGISTRAR LOS DATOS PARA FACTURACIÓN</li>";
	    
	    
	    // CONSULTAR DATOS DE ENTIDAD Y REPRESENTANTE LEGAL
	    $entity=$this->db->findById($row['fk_entidad_id'],'entidades');
	    // DATOS DE REPRESENTANTE LEGAL
	    $agent=$this->db->findById($entity['fk_representante_id'],'personas');
	    // OBTENER DATOS DE LOCAL
	    $local=$this->db->findById($row['fk_local_id'],'locales');
	    
	    // VALIDAR DATOS DE LOCAL
	    if(in_array($entity['entidad_sitioweb'],$isEmpty)) $msg.="<li>SUBIR ANEXO - COPIA DE RUC</li>";
	    if(in_array($agent['persona_anexo_cedula'],$isEmpty)) $msg.="<li>SUBIR ANEXO - CEDULA DE REPRESENTANTE LEGAL</li>";
	    
	    // VALIDAR DATOS DE LOCAL
	    // if(in_array($local['local_impuestopredial'],$isEmpty)) $msg.="<li>SUBIR ANEXO - PAGO DEL IMPUESTO PREDIAL DEL ESTABLECIMIENTO</li>";
	    
	    // VALIDAR RESPONSABLE DEL PLAN
	    if(in_array($row['plan_sos'],['PROFESIONAL DE SEGURIDAD DE LA EMPRESA','PROFESIONAL CONTRATADO EXTERNAMENTE POR LA EMPRESA'])){
	        // OBTENER DATOS DE FORMACION ACADÉMICA
	        $sos=$this->db->findById($row['profesional_sos_id'],'formacionacademica');
	        // VALIDAR DATOS DE SOS
	        if(in_array($sos['formacion_pdf'],$isEmpty)) $msg.="<li>SUBIR ANEXO - REGISTRO DE SENESCYT DEL PROFESIONAL RESPONSABLE DEL PLAN DE AUTOPROTECCION</li>";
	    }
	    
	    // VALIDAR DATOS DE RESPONSABLE DEL TRAMITE
	    if(in_array($row['plan_responsable_tramite'],['DELEGADO DEL TITULAR','ADMINISTRADOR DE LA EMPRESA'])){
	        // OBTENER DATOS DE RESPONSABLE DEL TRAMITE
	        $person=$this->db->findById($row['fk_responsable_tramite'],'personas');
	        // VALIDAR DATOS DE SOS
	        if(in_array($person['persona_anexo_cedula'],$isEmpty)) $msg.="<li>SUBIR ANEXO - COPIA DE CEDULA DEL RESPONSABLE DEL TRAMITE</li>";
	        
	    }
	    
	    // VALIDAR MENSAJE
	    if($msg=="") return true;
	    // RETORNAR CONSULTA
	    return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printDetail($pdfCtrl,$config){
		// RECIBIR DATOS GET - URL
		$get=$this->requestGet();
		// STRING DE CONSULTA PARA REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE plan_id={$get['id']}");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			// DATOS DE LOCAL COMERCIAL
			$local=$this->db->viewById($row['fk_local_id'],'locales');
			
			// RESPONSABLES DE PLAN
			$responsibles=$this->db->findOne($this->db->selectFromView('vw_planesemergencia_responsables',"WHERE plan_id={$get['id']}"));
			
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testPrintRequest($pdfCtrl,$local['representantelegal_nombre'],array_merge($local,$row))) return '';
			
			// IMPRESION DE DATOS DE PLAN DE AUTOPROTECCION
			$pdfCtrl->loadSetting($row);
			// IMPRESION DE DATOS LOCAL COMERCIAL
			$pdfCtrl->loadSetting($local);
			// IMPRESION DE DATOS DE RESPONSABLES
			$pdfCtrl->loadSetting($responsibles);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['plan_codigo']);
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
		$strInspectors=$this->db->selectFromView($this->db->setCustomTable('planesemergencia_inspector','fk_personal_id'),"WHERE fk_plan_id={$processId}");
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
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE plan_id=$id");
		// VALIDAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR REGISTRO DE EMISION SIN PAGO
			if($row['plan_estado']=='REVISADO' && isset($this->get['exemptPayment'])){
				// VALIDAR PERMISO PARA APROBAR TRÁMITE SIN PAGO
				if(!in_array(607111,$this->getAccesRol())){
					return $pdfCtrl->printCustomReport($this->varGlobal['EXEMPT_FROM_PAYMENT_ROLE'],$this->getProfile()['usuario_login'],$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
				}
				// REGISTRAR SOLICITUD SIN PAGO
				$this->insertExemptPayment($row['fk_entidad_id'],$this->entity,$id);
				// ACTUALIZAR DATOS DE APROBACIÓN
				$row['plan_estado']='APROBADO';
			}
			
			// VALIDAR ESTADO DE REGISTRO
			if(in_array($row['plan_estado'],['PENDIENTE','INGRESADO'])){
				// PLANTILLA PARA REPORTE NO ENCONTRADO
				$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>El Plan de Emergencia no ha sido aprobado</li>','usuario'=>'usuario'));
				$pdfCtrl->template='emptyReport';
				return '';
			}
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO
			if($row['plan_estado']=='PAGADO'){
				$row['plan_estado']='APROBADO';
				$row['plan_aprobado']=$this->getFecha('dateTime');
				$row['plan_caduca']=$this->addYears($row['plan_aprobado'],2);
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['plan_serie']<1) $row['plan_serie']=$this->db->getNextSerie($this->entity);
				// ACTUALIZAR DATOS
				$this->db->executeSingle($this->db->getSQLUpdate($row,$this->entity));
			}
			// GENERAR NUMERO DE TRÁMITE - EDICIÓN DE 0 POR DEFECTO - VERIFICAR SI HA SIDO APROBADO SIN GENERAR ORDEN DE COBRO
			if($row['plan_estado']=='APROBADO'){
				// VARIABLE PARA ACTUALIZAR PERMISO
				$stateUpdate=false;
				// COMPROBAR SI LA FECHA HA SIDO INGRESADA
				if($row['plan_aprobado']=='' || $row['plan_aprobado']==null){
					// NOTIFICAR CAMBIOS
					$stateUpdate=true;
					// CAMBIOS
					$row['plan_aprobado']=$this->getFecha('dateTime');
				}
				
				// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
				$row['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
				
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($row['plan_serie']==0){
					// NOTIFICAR CAMBIOS
					$stateUpdate=true;
					// CAMBIOS
					$row['plan_serie']=$this->db->getNextSerie($this->entity);
				}
				// VALIDAR SI ES NECESARIA LA ACTUALIZACIÓN
				if($stateUpdate) $this->db->executeSingle($this->db->getSQLUpdate($row,$this->entity));
			}
			// FORMATO DE FECHAS
			$row['plan_serie']=str_pad($row['plan_serie'],4,"0",STR_PAD_LEFT);
			$row['plan_aprobado_date']=$this->setFormatDate($row['plan_aprobado'],'medium');
			$row['plan_aprobado']=$this->setFormatDate($row['plan_aprobado'],'full');
			$row['plan_elaborado']=$this->setFormatDate($row['plan_elaborado'],'complete');
			$row['plan_caduca']=$this->setFormatDate($this->setFormatDate($row['plan_caduca'],'Y').'-12-31','complete');
			// DATOS DE PLAN DE EMERGENCIA
			$pdfCtrl->loadSetting($row);
			// DATOS DE ENTIDAD	
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_local_id'],'locales'));
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['plan_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity,'permit');
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
}