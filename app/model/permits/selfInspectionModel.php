<?php namespace model\permits;
use api as app;
/**
 * Description of controller
 *
 * @author Lalytto
 * @mail apinango@lalytto.com
 */
class selfInspectionModel extends app\controller {
	
	/*
	 * VARIABLES LOCALES
	 */
	private $entity='autoinspecciones';
	protected $varLocal;
	private $msg;
	private $state=true;
	private $itemList;
	private $threadList;
	protected $currentYear;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		parent::__construct();
		// VARIABLE LOCAL DE MÓDULO - CAPACITACIONES
		$this->varLocal=$this->getConfParams('AI');
		// VARIABLES LOCALES - AÑO ACTUAL
		$this->currentYear=$this->getCurrentYear();
	}
	
	
	
	/*
	 * OBTENER FORMULARIO DE UNA ACTIVIDAD COMERCIAL
	 */
	protected function getFormByLocal($localId){
		// OBTENER CIIU
		$local=$this->db->viewById($localId,'locales');
		// 1. VERIFICAR FORMULARIO PARA CIIU
		$strCiiu=$this->db->selectFromView('formularios',"WHERE fk_tabla='tb_ciiu' AND fk_tabla_id={$local['fk_ciiu_id']}");
		
		// 2. VERIFICAR FORMULARIO PARA MACRO-ACTIVIDAD
		$strActividad=$this->db->selectFromView('formularios',"WHERE fk_tabla='tb_actividades' AND fk_tabla_id={$local['fk_actividad_id']}");
		
		// VALIDAR REGLAS (FORMULARIOS) CON 1. Y 2.
		if($this->db->numRows($strCiiu)>0) $str=$strCiiu;
		elseif($this->db->numRows($strActividad)>0) $str=$strActividad;
		else $this->getJSON("Aún no ha sido creado el formulario, por favor intente nuevamente...");
		
		// VARIABLES AUXILIARES PARA VALIDACIONES
		$firstId=0;
		$max=0;
		$maxId=0;
		$loop=0;
		// RECORRER REGLAS PARA FORMULARIO
		foreach($this->db->findAll("{$str} ORDER BY maximo_area, maximo_pisos, maximo_aforo") as $val){
			// AUXILIAR DE VALIDACION
			$result=0;
			// VALIDAR ATRIBUTOS DE ACTIVIDAD COMERCIAL [LOCALES]
			if($local['local_aforo']>$val['maximo_aforo'])$result++;
			if($local['local_plantas']>$val['maximo_pisos'])$result++;
			if($local['local_area']>$val['maximo_area'])$result++;
			// VALIDAR LAS REGLAS DE FORMULARIO
			if($max<=$result){
				// VALIDAR SI EXISTE ITERACION
				if($loop==0) $firstId=$val['formulario_id'];
				// ACTUALIZAR DATOS
				$max=$result;
				$maxId=$val['formulario_id'];
			}
			// INCREMENTAR ITERACION
			$loop++;
		}
		
		// SI NO EXISTEN ITERACIONES EL PRIMER FORMULARIO VALIDADO SERÁ EL DE AUTOINSPECCION
		if($max==0) $maxId=$firstId;
		
		// RETORNAR FORMULARIO
		return $this->db->getById('formularios',$maxId);
	}
	
	/*
	 * REQUERIMIENTOS PARA FORMULARIO
	 */
	private function getResourcesForm($form){
		// REQUERIMIENTOS
		$requirements=array();
		// PREGUNTAS DE FORMULARIO
		$requirements['threadsForm']=array();
		// APLICAR SOLAMENTE A MACROACTIVIDADES
		if($form['fk_tabla']=='tb_actividades'){
			// GENERAR STRING DE CONSULTA PARA OBTENER ITEMS DE FORMULARIO
			$str=$this->db->selectFromView('vw_items',"WHERE fk_formulario_id={$form['formulario_id']}");
			// LISTAR ITEMS DE FORMULARIOS
			foreach($this->db->findAll($str) as $v){
				// AUXILIAR PARA OMITIR VALIDACIONES
				$continue=false;
				// RECORRER REQUERIDOS SEGUN ITEM
				foreach($this->db->findAll($this->db->selectFromView('requeridos',"WHERE fk_requerimiento_id={$v['fk_requerimiento_id']} ORDER BY actividad DESC")) as $v2){
					// VALIDAR SI YA SE HA CUMPLIDO LA VALIDACION
					if($continue) continue;
					// CUSTOM ITEM PARA VALIDAR
					$customItem=array('aforo'=>1,'area'=>1,'pisos'=>1);
					// VALIDAR SI EXISTEN REQUERIMIENTOS PARA MACRO-ACTIVIDAD
					if($v2['actividad']==$form['fk_tabla_id'] || $v2['actividad']==0){
						// VALIDAR AFORO
						if($v2['aforo']>0) $customItem['aforo']=ceil($form['maximo_aforo']/$v2['aforo']);
						// VALIDAR AREA
						if($v2['area']>0) $customItem['area']=ceil($form['maximo_area']/$v2['area']);
						// VALIDAR NUMERO DE PISOS
						if($v2['pisos']>0) $customItem['pisos']=ceil($form['maximo_pisos']/$v2['pisos']);
						// ACTUALIZAR CANTIDAD REQUERIDA
						$requirements['items'][$v['item_id']]=$v2['requiere']*max($customItem['aforo'],$customItem['area'],$customItem['pisos']);
						// DECLARAR QUE YA SE HA VALIDADO
						$continue=true;
					}
				}
			}
			// GENERAR STRING DE CONSULTA PARA OBTENER PREGUNTAS DE FORMULARIO
			$requirementsListID=$this->db->selectFromView($this->db->setCustomTable('frm_has_req','fk_requerimiento_id'),"WHERE fk_formulario_id={$form['formulario_id']} AND frmreq_estado='ACTIVO'");
			$str=$this->db->selectFromView('vw_threads',"WHERE fk_formulario_id={$form['formulario_id']} AND thread_estado='ACTIVO' AND fk_requerimiento_id IN ($requirementsListID)");
			// LISTAR PREGUNTAS DE FORMULARIOS
			foreach($this->db->findAll($str) as $v){
				// STRING DE CONSULTA PARA OBTENER REQUERIDOS
				$str2=$this->db->selectFromView('requeridos',"WHERE fk_requerimiento_id={$v['fk_requerimiento_id']} AND requerido_tipo='CALIFICABLE' ORDER BY actividad DESC");
				// VALIDAR SI EXISTEN REQUERIDOS DE TIPO PREGUNTA
				if($this->db->numRows($str2)>0){
					// AUXILIAR PARA OMITIR VALIDACIONES
					$continue=false;
					// RECORRER REQUERIDOS SEGUN PREGUNTAS
					foreach($this->db->findAll($str2) as $val2){
						// VALIDAR SI YA SE HA CUMPLIDO LA VALIDACION
						if($continue) continue;
						// VALIDAR SI EXISTEN REQUERIMIENTOS PARA MACRO-ACTIVIDAD
						if($val2['actividad']==$form['fk_tabla_id'] || $val2['actividad']==0){
							// VARIABLE INTERNA PARA VALIDAR ATRIBUTOS DE LOCAL
							$state=false;
							// VALIDAR RECURSOS
							if($val2['aforo']<1 && $val2['area']<1 && $val2['pisos']<1)$state=true;
							// EVALUAR UN SOLO PARAMETRO
							elseif($val2['aforo']<1 && $val2['area']<1){ // SOLO INTERESA # PISOS
								if($val2['pisos']<=$form['maximo_pisos'])$state=true;
							}elseif($val2['aforo']<1 && $val2['pisos']<1){ // SOLO INTERESA AREA
								if($val2['area']<=$form['maximo_area'])$state=true;
							}elseif($val2['area']<1 && $val2['pisos']<1){ // SOLO INTERESA AFORO
								if($val2['aforo']<=$form['maximo_aforo'])$state=true;
							}
							// EVALUAR 2 PARÁMETROS
							elseif($val2['aforo']>0 && $val2['area']>0 && $val2['pisos']<1){ // EVALUAR 2 PARAMETROS
								if($val2['aforo']<=$form['maximo_aforo'] || $val2['area']<=$form['maximo_area'])$state=true;
							}elseif($val2['aforo']>0 && $val2['pisos']>0 && $val2['area']<1){ // EVALUAR 2 PARAMETROS
								if($val2['aforo']<=$form['maximo_aforo'] || $val2['pisos']<=$form['maximo_pisos'])$state=true;
							}elseif($val2['area']>0 && $val2['pisos']>0 && $val2['aforo']<1){ // EVALUAR 2 PARAMETROS
								if($val2['area']<=$form['maximo_area'] || $val2['pisos']<=$form['maximo_pisos'])$state=true;
							}
							// EVALUAR TODO
							elseif($val2['aforo']>0 && $val2['area']>0 && $val2['pisos']>0){
								if($val2['aforo']<=$form['maximo_aforo'])$state=true;
								if($val2['area']<=$form['maximo_area'])$state=true;
								if($val2['pisos']<=$form['maximo_pisos'])$state=true;
							}	
							// DECLARAR QUE YA SE HA VALIDADO
							$continue=true;
							// ACTUALIZAR REQUERIMIENTOS
							$requirements['threads'][$v['thread_id']]=($state && $v['thread_requerido']);
							$requirements['threadsForm'][$v['thread_id']]=($state && $v['thread_requerido'])?'SI':'NA';
							// CAMBIAR A REQUERIDO UNICAMENTE SI ES OBLIGATORIO Y SI CUMPLE CON LAS REGLAS (REQUERIDOS)
							if($val2['requiere']==1 && $state){
								$requirements['threads'][$v['thread_id']]=true;
								$requirements['threadsForm'][$v['thread_id']]='SI';
							}
						}
					}
				}else{
					// ACTUALIZAR REQUERIMIENTOS
					$requirements['threads'][$v['thread_id']]=$v['thread_requerido'];
					$requirements['threadsForm'][$v['thread_id']]=$v['thread_requerido']?'SI':'NA';
				}
			}
		}else{ // APLICAR A ACTIVIDADES CIIU O TASAS PRESUPUESTARIAS
			// RECORRER ITEMS Y ACTUALIZAR CANTIDAD
			foreach($this->db->findAll($this->db->selectFromView('items',"WHERE fk_formulario_id={$form['formulario_id']}")) as $v){
				// ACTUALIZAR CANTIDAD REQUERIDA
				$requirements['items'][$v['item_id']]=$v['item_cantidad'];
			}
			// GENERAR STRING DE CONSULTA PARA OBTENER PREGUNTAS DE FORMULARIO
			$requirementsListID=$this->db->selectFromView($this->db->setCustomTable('frm_has_req','fk_requerimiento_id'),"WHERE fk_formulario_id={$form['formulario_id']} AND frmreq_estado='ACTIVO'");
			$str=$this->db->selectFromView('vw_threads',"WHERE fk_formulario_id={$form['formulario_id']} AND thread_estado='ACTIVO' AND fk_requerimiento_id IN ($requirementsListID)");
			// LISTAR PREGUNTAS DE FORMULARIOS
			foreach($this->db->findAll($str) as $v){
				$requirements['threads'][$v['thread_id']]=$v['thread_requerido'];
				$requirements['threadsForm'][$v['thread_id']]=$v['thread_requerido']?'SI':'NA';
			}
		}	
		// RETORNAR REQUERIMIENTOS DE FORMULARIO
		return $requirements;
	}
	
	/*
	 * FORMULARIO DE AUTOINSPECCION
	 */
	 protected function getModelSelfInspection($local,$form){
	 	// PARAMETROS DE FORMULARIO PARA OBTENER ITEMS Y PREGUNTAS PARA AUTOINSPECCION
	 	$form['maximo_aforo']=$local['local_aforo'];
	 	$form['maximo_area']=$local['local_area'];
	 	$form['maximo_pisos']=$local['local_plantas'];
	 	// ITEM Y PREGUNTAS REQUERIDOS PARA AI
	 	return $this->getResourcesForm($form);
	 }
	
	/*
	 * AUTOINSPECCION DE LOCAL
	 */
	protected function getSelfInspectionByLocal($localId){
		// MODELO DE DATOS
		$data=array();
		// VALIDAR SI EXISTE AUTOINSPECCIÓN PARA EL AÑO EN CURSO
		$strAI=$this->db->selectFromView($this->entity,"WHERE fk_local_id={$localId} AND (date_part('year',autoinspeccion_fecha)=date_part('year',CURRENT_DATE))");
		// AUTOCOMPLETAR AUTOINSPECCIÓN SI YA HA SIDO GENERADA & AGREGAR DATOS DE AUTOINSPECCIÓN A INSPECCIÓN SI AUN NO HA SIDO REALIZADA LA INSPECCIÓN
		if($this->db->numRows($strAI)>0){
			$ai=$this->db->findOne($strAI);
			$data['ai']['threads']=array();
			$data['ai']['items']=array();
			$data['inspection']['threads']=array();
			$data['inspection']['items']=array();
			// RECORRER DATOS DE INSPECCIÓN - THREADS & ITEMS
			foreach($this->db->findAll($this->db->selectFromView('ai_has_threads',"WHERE fk_autoinspeccion_id={$ai['autoinspeccion_id']}")) as $val3){
				// AUTOINSPECCION
				$data['ai']['threads'][$val3['fk_thread_id']]=$val3['request_local'];
				// INSPECCION
				if($val3['request_inspector']==null)$val3['request_inspector']=$val3['request_local'];
				$data['inspection']['threads'][$val3['fk_thread_id']]=$val3['request_inspector'];
			}
			foreach($this->db->findAll($this->db->selectFromView('ai_has_items',"WHERE fk_autoinspeccion_id={$ai['autoinspeccion_id']}")) as $val3){
				// AUTOINSPECCION
				$data['ai']['items'][$val3['fk_item_id']]=$val3['request_local'];
				// INSPECCION
				if($val3['request_inspector']==null)$val3['request_inspector']=$val3['request_local'];
				$data['inspection']['items'][$val3['fk_item_id']]=$val3['request_inspector'];
			}
			// DATOS DE AUTOINSPECCIÓN
			$data['inspection']['form']=$ai;
		}
		// RETORNAR DATOS
		return $data;
	}
	
	
	
	
	
	/*
	 * GENERAR CODIGO DE AUTOINSPECCION
	 */
	private function createPerCode($localId){
		// NUMERO DE LOCAL
		$localId=str_pad($localId,6,"0",STR_PAD_LEFT);
		// RETORNAR PARSE DE CODIGO
		return "{$this->currentYear}PER{$localId}";
	}
	
	/*
	 * REGISTRO DE AUTOINSPECCIONES - SIN ESTABLECIMIENTO
	 */
	public function createEmptyAI(){
		
		// OBTENER FORMULARIO DE ACTIVIDAD COMERCIAL PARA AUTOINSPECCION
		$form=$this->getFormByLocal($this->post['local_id']);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDAR SI EXISTE AUTOINSPECCIÓN
		$str=$this->db->selectFromView($this->entity,"WHERE fk_local_id={$this->post['local_id']} AND date_part('year',autoinspeccion_fecha)=date_part('year',CURRENT_DATE)");
		if($this->db->numRows($str)<1){
			// MODELO DE AUTOINSPECCION
			$ai=array(
				'fk_usuario_id'=>2,
				'fk_local_id'=>$this->post['local_id'],
				'fk_formulario_id'=>$form['formulario_id'],
				'autoinspeccion_codigo'=>$this->createPerCode($this->post['local_id']),
			);
			// GENERAR SENTENCIA DE INGRESO A DB
			$str=$this->db->getSQLInsert($ai,$this->entity);
			// EJECUTAR SENTENCIA SQL
			$this->db->executeTested($str);
			// OBTENER ID DE AUTOINSPECCION
			$id=$this->db->getLastID($this->entity);
		}else{
			// OBTENER DATOS DE AUTOINSPECCION
			$ai=$this->db->findOne($str);
			// codigo de autoinspeccion
			$ai['autoinspeccion_codigo']=$this->createPerCode($this->post['local_id']);
			// CAMBIAR ID DE FORMULARIO
			$ai['fk_formulario_id']=$form['formulario_id'];
			// EJECUTAR SENTENCIA SQL
			$this->db->executeTested($this->db->getSQLUpdate($ai,$this->entity));
			// OBTENER ID DE AUTOINSPECCION
			$id=$ai['autoinspeccion_id'];
		}
		
		// VALIDAR SI YA EXISTE UN PERMISO Y ACTUALIZAR PREGUNTAS E ITEMS
		if($this->db->numRows($this->db->selectFromView('permisos',"WHERE fk_autoinspeccion_id={$id}"))>0){
			$this->getJSON($this->db->closeTransaction($this->setJSON('NO SE PUEDE REALIZAR ESTA ACCIÓN DEBIDO A QUE YA SE HA EMITIDO EL PERMISO DE FUNCIONAMIENTO')));
		}else{
			$sql=$this->db->executeTested($this->db->getSQLDelete('ai_has_items',"WHERE fk_autoinspeccion_id={$id}"));
			$sql=$this->db->executeTested($this->db->getSQLDelete('ai_has_threads',"WHERE fk_autoinspeccion_id={$id}"));
		}
		
		// INGRESAR ID DE AUTOINSPECCIÓN
		$sql['id']=$id;
		
		// CAMBIAR MENSAJE DE RETORNO
		$sql['mensaje']=$this->varLocal['SYS_MSG_EMPTY_SI'];
		
		// CERRAR CONEXIÓN A DB Y RETORNAR MENSAJE
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	
	/*
	 * INSERTAR 
	 */
	private function createNewPlan($ai,$local){
		// CREAR POR PRIMERA VEZ EL PLAN
		$modelSelfProtection=array(
			'fk_local_id'=>$local['local_id'],
			'plan_plantas'=>$local['local_plantas'],
			'plan_aforo'=>$local['local_aforo'],
			'plan_area'=>$local['local_area'],
			'plan_clavecatastral'=>$local['local_clavecatastral'],
			'plan_observacion'=>"GENERADO EN AUTOINSPECCIÓN {$ai['autoinspeccion_codigo']} :: [ Aforo={$local['local_aforo']} Plantas={$local['local_plantas']} Área={$local['local_area']}m2 ]"
		);
		// GENERAR STRING DE EJECUCIÓN
		return $this->db->getSQLInsert($modelSelfProtection,'planesemergencia');
	}
	
	/*
	 * VALIDAR PLAN DE AUTOPROTECCIÓN
	 */
	private function validateSelfProtectionPlan($ai){
		// STRING PARA CONSULTAR SI EXISTE SOLICITUD DE PLAN DE AUTOPROTECCION
		$str=$this->db->selectFromView("planesemergencia","WHERE fk_local_id={$ai['fk_local_id']} ORDER BY plan_id DESC LIMIT 1");
		// INFORMACIÓN DE ESTABLECIMIENTO
		$local=$this->db->findById($ai['fk_local_id'],'locales');
		// VALIDAR SI EXISTE UNA SOLICITUD DE PLAN DE AUTOPROTECCION
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PLAN
			$plan=$this->db->findOne($str);
			// VALIDAR SI ES NECESARIO EL PLAN
			if($plan['request_local']=='SI'){
				// VALIDAR FECHA DE APROBACIÓN Y VENCIMIENTO
				if($plan['plan_estado']=='APROBADO' && strtotime($plan['plan_caduca'])<strtotime($this->getFecha('Y-m-d'))){
					// GENERAR MODELO DE ACTUALIZACION
					$plan['plan_estado']='VENCIDO';
					$plan['plan_observacion']="ACTUALIZACIÓN EN AUTOINSPECCIÓN {$ai['autoinspeccion_codigo']} :: [ Aforo={$local['local_aforo']} Plantas={$local['local_plantas']} Área={$local['local_area']}m2 ]";
					// INGRESAR NUEVO PLAN DE EMERGENCIA
					$this->db->executeTested($this->createNewPlan($ai,$local));
				}elseif($plan['plan_estado']=='VENCIDO'){
					// GENERAR UNO NUEVO
					return $this->db->executeTested($this->createNewPlan($ai,$local));
				}
			}else{
				// VALIDAR FECHA DE APROBACIÓN Y VENCIMIENTO
				if($plan['plan_estado']=='PENDIENTE'){
					// GENERAR MODELO DE ACTUALIZACION
					$plan['plan_estado']='SUSPENDIDO';
					$plan['plan_observacion']="SUSPENDIDO EN AUTOINSPECCIÓN {$ai['autoinspeccion_codigo']} :: [ Aforo={$local['local_aforo']} Plantas={$local['local_plantas']} Área={$local['local_area']}m2 ]";
				}
			}
			// GENERAR STRING DE EJECUCIÓN
			$strExecute=$this->db->getSQLUpdate($plan,'planesemergencia');
		}else{
			// CREAR POR PRIMERA VEZ EL PLAN
			$strExecute=$this->createNewPlan($ai,$local);
		}
		// EJECUTAR SENTENCIA A BASE DE DATOS
		return $this->db->executeTested($strExecute);
	}
	
	/*
	 * REGISTRAR ITEMS Y PREGUNTAS DE AUTOINSPECCION
	 */
	private function validateSelfInspection($selfInspectionId,$post){
		// LISTADO DE ITEMS DE AUTOINSPECCION
		$this->itemList=array();
		// RECORRER LISTADO ITEMS
		foreach($post['items'] as $key=>$val){
			// CONSULTAR ITEM
			$row=$this->db->viewById($key,'items');
			// CONSULTAR LOS DATOS REQUERIDOS PARA UN FORMULARIO
			$row=array_merge($row,$this->db->findOne($this->db->getSQLSelect('requerimientos',[],"WHERE requerimiento_id={$row['fk_requerimiento_id']}")));
			// VALIDAR DATOS ENVIADOS CON LOS DATOS REQUERIDOS
			if($row['item_requerido'] && $val<$row['item_cantidad']){
				// SETTEAR MENSAJE
				$this->msg.="Debe contar con un mínimo de <b>{$row['item_cantidad']} {$row['requerimiento_nombre']}</b> <br>";
			}
			// AGREGAR ITEM A LISTADO DE INGRESO
			$this->itemList[]=array(
				'fk_autoinspeccion_id'=>$selfInspectionId,
				'fk_item_id'=>$row['item_id'],
				'request_requerido'=>$row['item_requerido']?'true':'false',
				'request_local'=>$val
			);
		}
		
		// LISTADO DE THREADS DE AUTOINSPECCION
		$this->threadList=array();
		// RECORRER LISTADO PREGUNTAS
		foreach($post['threads'] as $key=>$val){
			// CONSULTAR PREGUNTA
			$row=$this->db->viewById($key,'threads');
			// VALIDAR SI ES UNA PREGUNTA REQUERIDA
			if($row['thread_requerido'] && $val!='SI'){
				// SETTEAR MENSAJE
				$this->msg.="La pregunta <b>{$row['fk_pregunta_id']}</b> es necesaria.<br>";
			}
			// AGREGAR PREGUNTA A LISTADO DE INGRESO
			$this->threadList[]=array(
				'fk_autoinspeccion_id'=>$selfInspectionId,
				'fk_thread_id'=>$row['thread_id'],
				'request_requerido'=>$row['thread_requerido']?'true':'false',
				'request_local'=>$val
			);
		}
		
		// RETORNAR CONSULTA
		return $this->state;
	}
	
	/*
	 * VALIDAR ESTADO DE ACTIVIDAD ECONOMICA
	 */
	private function validateLocal($localId){
		// ESTADO DE VALIDACION
		$status=false;
		// VARIABLE PARA ADJUNTR MENSAJE DE RESPUESTA
		$msg="";
		// OBTENER INFORMACION DE ESTABLECIMIENTO
		$local=$this->db->viewById($localId,'locales');
		// VALIDAR EXISTENCIA DE PRORROGA
		if($local['prorroga_id']>0) return true;
		// VALIDAR SI SE CANCELA INSPECCION
		if(($local['plan_id']>0 && !in_array($local['plan_status'],['SIN INSPECCION','SUSPENDIDO','APROBADO'])) || ($local['inspeccion_id']>0 && !in_array($local['inspeccion_status'],['SIN INSPECCION','APROBADO']))) $status=true;
		// VALIDAR ESTADO DE INSPECCION
		if($local['inspeccion_id']>0 && !in_array($local['inspeccion_status'],['SIN INSPECCION','APROBADO'])) $msg.="<br>INSPECCION {$local['inspeccion_code']}<br><b>{$local['inspeccion_status']}</b>";
		// VALIDAR ESTADO DE PLAN DE EMERGENCIA
		if($local['plan_id']>0 && !in_array($local['plan_status'],['SIN INSPECCION','SUSPENDIDO','APROBADO'])) $msg.="<br>PLAN DE EMERGENCIA {$local['plan_code']}<br><b>{$local['plan_status']}</b>";
		// VALIDAR ESTADO DE INSPECCIONES
		if($status) $this->getJSON($this->varGlobal['LOCAL_INSPECTIONS_REQUIRED']."{$msg}");
	}
	
	/*
	 * INGRESO DE NUEVO REGISTRO
	 */
	public function insertSelfInspection(){
		
		// VALIDAR LA EXITENCIA DE UNA INSPECCIÓN EN PROCESO
		$this->validateLocal($this->post['form']['local']);
		
		// DECLARAR VARIABLE DE FORMULARIO
		$form=$this->post['form'];
		
		// VALIDAR SI YA SE HA EMITIDO EL PERMISO DE FUNCIONAMIENTO PARA LA ACTIVIDAD COMERCIAL
		if($this->db->numRows($this->db->selectFromView('vw_permisos',"WHERE local_id={$form['local']} AND (date_part('year',permiso_fecha)=date_part('year',CURRENT_DATE))"))>0){
			$this->getJSON("No se puede realizar esta operación, ya se ha emitido el <b>PERMISO DE FUNCIONAMIENTO {$this->currentYear}</b> para esta actividad!");
		}
		
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		// STRING PARA CONSULTAR SI EXISTE AUTOINSPECCIÓN PARA AÑO EN CURSO
		$str=$this->db->selectFromView('autoinspecciones',"WHERE fk_local_id={$form['local']} AND (date_part('year',autoinspeccion_fecha)=date_part('year',CURRENT_DATE))");
		// VALIDAR SI EXISTE AUTOINSPECCION PARA AÑO EN CURSO
		if($this->db->numRows($str)<1){
			// DECLARACION DE MODELO DE AUTOINSPECCION
			$ai=array(
				'autoinspeccion_codigo'=>$this->createPerCode($form['local']),
				'fk_local_id'=>$form['local'],
				'fk_formulario_id'=>$form['rule']['formulario_id']
			); 
			// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
			$this->db->executeTested($this->db->getSQLInsert($ai,$this->entity));
			// OBTENER ID DE AUTOINSPECCION
			$id=$this->db->getLastID($this->entity);
		}else{
			// OBTENER DATOS DE AUTOINSPECCION
			$ai=$this->db->findOne($str);
			// CREAR CODIGO DE AUTOINSPECCION SI NO HA SIDO GENERADO
			if(strlen($ai['autoinspeccion_codigo'])<1) $ai['autoinspeccion_codigo']=$this->createPerCode($form['local']);
			// ACTUALIZAR ID DE FORMULARIO
			$ai['fk_formulario_id']=$form['rule']['formulario_id'];
			// GENERAR STRING DE ACTUALIZACION Y EJECUTAR CONSULTA
			$this->db->executeTested($this->db->getSQLUpdate($ai,$this->entity));
			// OBTENER ID DE AUTOINSPECCION
			$id=$ai['autoinspeccion_id'];
		}
		
		// ELIMINAR RECURSOS DE AUTOINSPECCION - ITEMS Y PREGUNTAS
		$this->db->executeTested($this->db->getSQLDelete('ai_has_items',"WHERE fk_autoinspeccion_id=$id"));
		$this->db->executeTested($this->db->getSQLDelete('ai_has_threads',"WHERE fk_autoinspeccion_id=$id"));
		
		// INGRESAR PREGUNTAS E ITEMS A LA AUTOINSPECCION
		if(!$this->validateSelfInspection($id,$this->post))$this->getJSON($this->msg);
		
		// INSERTAR RECURSOS DE CUMPLIMIENTOS - CUALITATIVOS
		foreach($this->itemList as $val){$this->db->executeTested($this->db->getSQLInsert($val,'ai_has_items'));}
		
		// INSERTAR RECURSOS DE CUMPLIMIENTO - CUANTITATIVOS
		foreach($this->threadList as $val){
			// CONSULTAR ELREGISTRO DE PLAN DE AUTOPROTECCION
			$row=$this->db->findById($val['fk_thread_id'],'threads');
			
			// VALIDAR SI EL RECURSO A IMPLEMENTAR ES PLAN DE EMERGENCIA
			if($row['fk_pregunta_id']==27 && $val['request_local']=='SI') $this->validateSelfProtectionPlan($ai);
			
			// INGRESAR RECURSOS
			$this->db->executeTested($this->db->getSQLInsert($val,'ai_has_threads'));
		}
		
		// PERSONALIZAR MENSAJE DE REGRESO
		$sql=$this->setJSON(str_replace("{{autoinspeccion_id}}",$id,$this->varLocal['AI_INFO_FINAL_STEP']),true,array('id'=>$id));
		
		// RETORNAR MENSAJE DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * SET REQUEST INSPECTOR -> INSPECCIÓN
	 */
	public function setSelfInspection(){
		
		// DATOS DE AUTOINSPECCION
		$ai=$this->post['autoinspeccion'];
		
		// VALIDAR ÍTEMS Y THREADS
		$autoinspeccion_id=$ai['form']['autoinspeccion_id'];
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// ACTUALIZAR ITEMS
		foreach($this->itemList as $val){
			$strAux=$this->db->getSQLSelect('ai_has_items',[],"WHERE fk_autoinspeccion_id=$autoinspeccion_id AND fk_item_id={$val['fk_item_id']}");
			if($this->db->numRows($strAux,true)<1)$str=$this->db->getSQLInsert($val,'ai_has_items');
			else{
				$row=$this->db->findOne($strAux);
				$str=$this->db->getSQLUpdate(array_merge($row,$val),'ai_has_items');
			}	$this->db->executeTested($str);
		}
		
		// ACTUALIZAR THREADS
		foreach($this->threadList as $val){
			$strAux=$this->db->getSQLSelect('ai_has_threads',[],"WHERE fk_autoinspeccion_id=$autoinspeccion_id AND fk_thread_id={$val['fk_thread_id']}");
			if($this->db->numRows($strAux)<1)$str=$this->db->getSQLInsert($val,'ai_has_threads');
			else{
				$row=$this->db->findOne($strAux);
				$str=$this->db->getSQLUpdate(array_merge($row,$val),'ai_has_threads');
			}	$this->db->executeTested($str);
		}
		
		// INSPECCIÓN
		$inspeccion=$this->post['inspeccion'];
		if(!$this->state || $inspeccion['inspeccion_estado']!='APROBADO'){
			// CUSTOM MENSAJE - REQUEST (IMPLEMENTOS QUE FALTAN)
			$inspeccion['inspeccion_observacion'].='<br>'.$this->msg;
			// INGRESAR/MODIFICAR CITACIÓN
			$strCitacion=$this->db->getSQLSelect('citaciones',[],"WHERE fk_inspeccion_id={$inspeccion['inspeccion_id']} ORDER BY citacion_id DESC");
			// GENERAR NÚMERO DE CITACIÓN
			$inspeccion['citacion_numero']=rand(1,1000);
			// CALCULAR FECHA DE REINSPECCIÓN
			$days=explode(' ',$this->getConfParams()['SYS_DIAS_CITACION']);
			$row['citacion_fecha_reinspeccion']=$this->addWorkDays($this->getFecha(),$days[0]);
			$inspeccion['citacion_tipo']=$inspeccion['inspeccion_estado'];
			if($this->db->numRows($strCitacion,true)>0){
				$row=$this->db->findOne($strCitacion);
				$row['citacion_fecha_reinspeccion']=$this->addWorkDays($this->getFecha(),$days[0]);
				$str=$this->db->getSQLUpdate(array_merge($row,$inspeccion),'citaciones');
			}else{
				$row=array('fk_inspeccion_id'=>$inspeccion['inspeccion_id']);
				$str=$this->db->getSQLInsert(array_merge($row,$inspeccion),'citaciones');
			}	$this->db->executeTested($str);
		}
			// CAMBIAR ESTADO DE INSPECCIÓN
			$sql=$this->db->executeSingle($this->db->getSQLUpdate($inspeccion,'inspecciones'));
			if(!$sql['estado'])$this->getJSON($this->db->closeTransaction($sql));
			$sql['id']=$inspeccion['inspeccion_id'];
			$sql['mensaje']="Inspección corroborada con éxito!";
			$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZAR LOS ITEMS Y PREGUNTAS DE AUTOINSPECCION
	 */
	private function updateItems($post,$lockedItems){
		$this->itemList=array();
		// VALIDAR ITEMS
		foreach($post['items'] as $key=>$val){
			if($val != null){
				$row=$this->db->findOne($this->db->getSQLSelect('items',['preguntas'],"WHERE item_id=$key"));
				$row=array_merge($row,$this->db->findOne($this->db->getSQLSelect('requerimientos',[],"WHERE requerimiento_id={$row['fk_requerimiento_id']}")));
				if($row['item_requerido'] && ($val<$row['item_cantidad'] && $val<$lockedItems[$row['item_id']])){
					$this->state=false;
					// SETTEAR MENSAJE
					$this->msg.="No cumple con el requisito mínimo de <b>{$row['item_cantidad']} {$row['requerimiento_nombre']}</b> <br>";
				}	$this->itemList[]=array('fk_autoinspeccion_id'=>$post['form']['autoinspeccion_id'],
											'fk_item_id'=>$row['item_id'],
											'request_requerido'=>$row['item_requerido']?'true':'false',
											'request_inspector'=>$val);
			}
		}
		// VALIDAR THREADS
		$countThreads='';
		$this->threadList=array();
		foreach($post['threads'] as $key=>$val){
			if($val != null){
				$row=$this->db->findOne($this->db->getSQLSelect('threads',['preguntas'],"WHERE thread_id=$key"));
				if($row['thread_requerido'] && $val=='NO'){
					$this->state=false;
					$countThreads.="<b>{$row['pregunta_id']}.</b> {$row['pregunta_nombre']} <br>";
				}	$this->threadList[]=array('fk_autoinspeccion_id'=>$post['form']['autoinspeccion_id'],
												'fk_thread_id'=>$row['thread_id'],
												'request_requerido'=>$row['thread_requerido']?'true':'false',
												'request_inspector'=>$val);
			}
		}
		// SETTEAR MENSAJE
		if($countThreads!='')$this->msg.="No cumple con los siguientes requerimientos del formulario:<br>$countThreads";
		return $this->state;
	}
	

	/*
	 * CONSULTAR POR CODIGO DE BARRA
	 */
	private function requestByCode($code){
		
	    // STRING PARA CONSULTAR SI EXISTE PERMISO
	    $str=$this->db->selectFromView('permisos',"WHERE UPPER(codigo_per)=UPPER('{$code}')");
		// CONSULTAR SI YA SE HA EMITIDO EL PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        // OBTENER ID DE PERMISO
	        $permit=$this->db->findOne($str);
	        
	        // CONSULTAR SI EXISTE UN DUPLICADO
	        $str=$this->db->selectFromView('duplicados',"WHERE fk_permiso_id={$permit['permiso_id']} AND (date_part('year',fecha_solicitado)=date_part('year',CURRENT_DATE)) ORDER BY fecha_solicitado DESC LIMIT 1");
	        // VALIDAR SI EXISTE DUPLICADO
	        if($this->db->numRows($str)>0){
	            
	            // DATOS DE REGISTRO
	            $duplicated=$this->db->findOne($str);
	            // VERIFICAR SI LA SOLICITUD HA SIDO REGISTRADA
	            if($duplicated['duplicado_estado']=='PENDIENTE'){
	                $this->getJSON("¡Actualmente existe una solicitud de duplicado pendiente de pago, dirigir al usuario con la oficina respectiva para que cancele y se acerque a retirar su duplicado!");
	            }elseif($duplicated['duplicado_estado']=='APROBADO'){
	                // MODAL A DESPLEGAR
	                $permit['modal']='Permisos';
	                $permit['reimpresion_motivo']='DUPLICADO';
	                $permit['reimpresion_detalle']="REIMPRESION DE DUPLICADO APROBADO EL {$duplicated['fecha_aprobado']}";
	                // REGRESAR RESPUESTA
	                return $permit;
	            }
	            
	        }
	        
	        // ENVIAR MENSAJE DE PERMISO YA EMITIDO
	        $this->getJSON("No se puede realizar esta acción, ya se ha emitido el <b class='text-uppercase'>Permiso de Funcionamiento #{$code}</b>");
	        
	    }
		
		// STRING PARA CONSULTAR SI EXISTE LA AUTOINSPECCION
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(autoinspeccion_codigo)=UPPER('{$code}') AND date_part('year',autoinspeccion_fecha)=date_part('year',CURRENT_DATE)");
		// VALIDAR SI EXISTE AUTOINSPECCIÓN
		if($this->db->numRows($str)<1) $this->getJSON("La <b class='text-uppercase'>AutoInspeccion #{$code}</b> no se encuentra registada en el sistema, intente nuevamente.");
		// OBTENER DATOS DE AUTOINSPECCION
		$row=$this->db->findOne($str);
		
		// VERIFICAR SI YA SE HA EMITIDO EL PERMISO DE FUNCIONAMIENTO
		$row['code']=$code;
		
		// 	RETORNAR CONSULTA
		return $row;
	}
	
	/*
	 * VALIDAR AUTOINSPECCIÓN ANTES DE EMITIR PERMISO DE FUNCIONAMIENTO
	 */
	public function requestSelfInspection(){
		// CONSULTAR POR CODIGO
	    if(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("ok",true,$json));
	}
	
	
	
	
	/*
	 * REPORTE REGISTROS INCOMPLETOS
	 */
	private function testPrintRequest($pdfCtrl,$user,$row){
	    // VARIABLE PARA IMPRESION DE MENSAJES
	    $msg="";
	    // VARIABLE PARA VALIDAR ANEXOS
	    $isEmpty=array('',null,'null','NO','NA');
	    
	    // OBTENER INFORMACION DE LOCAL
	    $local=$this->db->findById($row['fk_local_id'],'locales');
	    // OBTENER INFORMACION DE ENTIDAD
	    $entity=$this->db->findById($local['fk_entidad_id'],'entidades');
	    $agent=$this->db->findById($entity['fk_representante_id'],'personas');
	    
	    // REGISTRAR COORDENADAS DEL ESTABLECIMIENTO
	    if(in_array($entity['entidad_sitioweb'],$isEmpty)) $msg.="<li>CARGAR CERTIFICADO - REGISTRO UNICO DE CONTRIBUYENTE</li>";
	    // VALIDAR CEDULA DE REPRESENTANTE LEGAL
	    if(in_array($agent['persona_anexo_cedula'],$isEmpty)) $msg.="<li>CARGAR CEDULA - REPRESENTANTE LEGAL</li>";
	    
	    // VALIDAR CERTIFICADO DE ESTABLECIMIENTO
	    if(in_array($local['local_certificadostablecimiento'],$isEmpty)) $msg.="<li>CARGAR CERTIFICADO - ESTABLECIMIENTO REGISTRADO</li>";
	    
	    // VALIDAR SI EXISTE APODERAD
	    if($entity['entidad_apoderado']=='SI'){
	        $adopted=$this->db->findById($entity['fk_apoderado_id'],'personas');
	        if(in_array($adopted['persona_anexo_cedula'],$isEmpty)) $msg.="<li>CARGAR CEDULA - APODERADO LOCAL</li>";
	    }
	    
	    // VALIDAR MENSAJE
	    if($msg=="") return true;
	    
	    // RETORNAR CONSULTA
	    return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// GENERAR SENTENCIA PARA CONSULTAR EXISTENCIA DE AUTOINSPECCIÓN
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE autoinspeccion_id=$id");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
		    
			// CARGAR DATOS DE AUTOINSPECCIÓN
			$selfInspection=$this->db->findOne($str);
			// ADJUNTAR FORMULARIO DE INSPECCIÓN
			$_GET['id']=$selfInspection['fk_formulario_id'];
			
			// CARGAR DATOS DE LOCAL COMERCIAL - ACTIVIDAD ECONÓMICA
			$activity=$this->db->viewById($selfInspection['fk_local_id'],'locales');
			$_GET['local']=$activity['local_id'];
			$activity['ciiu_nombre']=strtoupper($activity['ciiu_nombre']);
			$selfInspection['autoinspeccionFecha']=strtolower($this->setFormatDate($selfInspection['autoinspeccion_fecha'],'complete'));
			
			// VALIDAR FORMATO DE IMPRESION
			if($selfInspection['autoinspeccion_anio']>=2021){
			    
			    // VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			    // if(!$this->testPrintRequest($pdfCtrl,$selfInspection['representantelegal_nombre'],$selfInspection)) return '';
			    
			    // FIJAR LA PLANTILLA QUE SE VA A INSERTAR AL REPORTE
			    if($activity['fk_actividad_id']==6) $pdfCtrl->template='autoinspeccionGLP';
			    else $pdfCtrl->template='autoinspeccion2021';
			    
			    // APARMETRIZAR PLANTILLA
			    $template="_2021";
			    // VALIDAR SI CUENTA CON ESTABLECIMIENTO
			    if($activity['local_establecimiento']=='SI'){ $template="_2021_LOCAL"; }
			    
			    // ADJUNTAR DATOS DE DECLARACIÓN DE AUTOINSPECCIÓN - PRIMER PÁRRAFO
			    if(in_array($activity['entidad_contribuyente'],['natural','extranjero'])) $selfInspection['AUTOINSPECCION_DECLARACION']=$this->varGlobal['AUTOINSPECCION_DECLARACION_NATURALES'.$template];
			    if(in_array($activity['entidad_contribuyente'],['publica','privada'])) $selfInspection['AUTOINSPECCION_DECLARACION']=$this->varGlobal['AUTOINSPECCION_DECLARACION_SOCIEDADES'.$template];
			    
			}else{
			    
			    // FIJAR LA PLANTILLA QUE SE VA A INSERTAR AL REPORTE
			    if($activity['fk_actividad_id']==6) $pdfCtrl->template='autoinspeccionGLP';
			    else $pdfCtrl->template='autoinspeccion';
			    
			    // ADJUNTAR DATOS DE DECLARACIÓN DE AUTOINSPECCIÓN - PRIMER PÁRRAFO
			    if(in_array($activity['entidad_contribuyente'],['natural','extranjero'])) $selfInspection['AUTOINSPECCION_DECLARACION']=$this->varGlobal['AUTOINSPECCION_DECLARACION_NATURALES'];
			    if(in_array($activity['entidad_contribuyente'],['publica','privada'])) $selfInspection['AUTOINSPECCION_DECLARACION']=$this->varGlobal['AUTOINSPECCION_DECLARACION_SOCIEDADES'];
			    
			    // REPORTE - DATOS DE AUTOINSPECCIÓN
			    $form=new formModel;
			    // PARÁMETROS PARA REPORTE ADJUNTO EN LA AUTOINSPECCIÓN
			    $pdfCtrl->extraHTML=$form->getLocalSelfInspection($id);
			    // ENCABEZADO DE REPORTE - AUXILIAR
			    $pdfCtrl->titleExtra='REQUERIMIENTOS MÍNIMOS';
			    
			}
			
			// INSERTAR DATOS DE IMPRESIÓN - AUTOINSPECCIÓN
			$pdfCtrl->loadSetting($selfInspection);
			// INSERTAR DATOS DE IMPRESIÓN - LOCAL COMERCIAL
			$pdfCtrl->loadSetting($activity);
			
			
			// CREAR CODIGO DE AUTOINSPECCION SI NO HA SIDO GENERADO
			if(strlen($selfInspection['autoinspeccion_codigo'])<1) $selfInspection['autoinspeccion_codigo']=$this->createPerCode($activity['local_id']);
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$selfInspection['autoinspeccion_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($selfInspection,$this->entity);
			
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe la AutoInspección</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
}