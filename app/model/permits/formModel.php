<?php namespace model\permits;

class formModel extends selfInspectionModel {
	
	/*
	 * VARIABLES LOCALES
	 */
	private $statusChange;
	private $entity='formularios';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
		// VARIABLE PARA CAMBIOS
		$this->statusChange=array('ACTIVO'=>'SUSPENDIDO','SUSPENDIDO'=>'ACTIVO');
	}
	
	
	/*
	 * CONSULTAR ACTIVIDAD
	 */
	public function requestActivity(){
		// CONSULTAR REGISTRO
		$json=$this->setJSON("Ok",true,$this->db->findById($this->post['id'], 'actividades'));
		// RETORNAR CONSULTA
		$this->getJSON($json);
	}
	
	/*
	 * CREACIÓN DE FORMULARIO
	 * + ai: Creación de formulario con requerimientos para AutoInspección
	 */
	public function createForm(){
		// DATOS DE FORMULARIO
		$post=$this->requestPost();
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRAR FORMULARIO
		$sql=$this->db->executeSingle($this->db->getSQLInsert($post,$this->entity));
		if($sql['estado']){
			// OBTENER FORMULARIO
			$form=$this->db->getLastEntity($this->entity);
			// APLICAR SOLAMENTE A ACTIVIDADES
			if($form['fk_tabla']=='tb_actividades'){
				// RECORRER ITEMS Y ACTUALIZAR CANTIDAD
				$items=$this->db->findAll($this->db->getSQLSelect('items',[],"WHERE fk_formulario_id={$form['formulario_id']}"));
				foreach($items as $val){
					$pregunta=$this->db->findOne($this->db->getSQLSelect('preguntas',[],"WHERE pregunta_id={$val['fk_pregunta_id']}"));
					$requeridos=$this->db->findAll($this->db->getSQLSelect('requeridos',[],"WHERE fk_requerimiento_id={$pregunta['fk_requerimiento_id']} ORDER BY actividad DESC"));
					// RECORRER REQUERIDOS SEGUN ITEM
					$continue=false;
					foreach($requeridos as $val2){
						if($continue)continue;
						$customItem=array('aforo'=>1,'area'=>1,'pisos'=>1);
						if($val2['actividad']==$form['fk_tabla_id'] || $val2['actividad']==0){
							if($val2['aforo']>0)$customItem['aforo']=ceil($form['maximo_aforo']/$val2['aforo']);
							if($val2['area']>0)$customItem['area']=ceil($form['maximo_area']/$val2['area']);
							if($val2['pisos']>0)$customItem['pisos']=ceil($form['maximo_pisos']/$val2['pisos']);
							// ACTUALIZAR CANTIDAD REQUERIDA
							$val['item_cantidad']=max($customItem['aforo'],$customItem['area'],$customItem['pisos']);
							$sqlItem=$this->db->executeSingle($this->db->getSQLUpdate($val,'items'));
							if(!$sqlItem['estado'])$this->getJSON($this->db->closeTransaction($sqlItem));
							$continue=true;
						}
					}
				}
				// INGRESAR THREADS REQUERIDOS
				foreach($this->db->findAll($this->db->getSQLSelect('requerimientos',[],"WHERE requerimiento_estado='ACTIVO'")) as $val){
					$requeridos=$this->db->findAll($this->db->getSQLSelect('requeridos',[],"WHERE fk_requerimiento_id={$val['requerimiento_id']} AND requerido_tipo='CALIFICABLE' ORDER BY actividad DESC"));
					// RECORRER REQUERIDOS SEGUN REQUERIMIENTOS
					$continue=false;
					foreach($requeridos as $val2){
						if($continue)continue;
						if($val2['actividad']==$form['fk_tabla_id'] || $val2['actividad']==0){
							if($val2['aforo']<1 && $val2['area']<1 && $val2['pisos']<1)$state=true;
							elseif($val2['aforo']<1 && $val2['area']<1){
								if($val2['pisos']<=$form['maximo_pisos'])$state=true;
							}elseif($val2['aforo']<1 && $val2['pisos']<1){
								if($val2['area']<=$form['maximo_area'])$state=true;
							}elseif($val2['area']<1 && $val2['pisos']<1){
								if($val2['aforo']<=$form['maximo_aforo'])$state=true;
							}elseif($val2['aforo']>0 && $val2['area']>0 && $val2['pisos']>0){
								if($val2['aforo']<=$form['maximo_aforo'])$state=true;
								if($val2['area']<=$form['maximo_area'])$state=true;
								if($val2['pisos']<=$form['maximo_pisos'])$state=true;
							}
							if($state){
								// INGRESAR PREGUNTAS REQUERIDAS
								$noIn="SELECT fk_pregunta_id FROM permisos.tb_threads t WHERE t.fk_formulario_id={$form['formulario_id']}";
								$strWhere="WHERE pregunta_tipo='Preguntas' AND preguntas.fk_requerimiento_id={$val['requerimiento_id']} AND preguntas.pregunta_id NOT IN ($noIn)";
								foreach($this->db->findAll($this->db->getSQLSelect('preguntas',[],$strWhere)) as $val3){
									$thread['fk_usuario_id']=$form['fk_usuario_id'];
									$thread['fk_formulario_id']=$form['formulario_id'];
									$thread['fk_pregunta_id']=$val3['pregunta_id'];
									$thread['thread_requerido']=$val3['pregunta_requerido']?'true':'false';
									$sqlItem=$this->db->executeSingle($this->db->getSQLInsert($thread,'threads'));
									if(!$sqlItem['estado'])$this->getJSON($this->db->closeTransaction($sqlItem));
								}
							}	$continue=true;
						}
					}
				}
			}
			// INGRESAR PREGUNTAS OPCIONLES
			$strWhere="WHERE pregunta_id NOT IN (SELECT fk_pregunta_id FROM permisos.tb_threads t WHERE fk_formulario_id={$form['formulario_id']})";
			foreach($this->db->findAll($this->db->getSQLSelect('preguntas',[],$strWhere)) as $val3){
				$thread['fk_usuario_id']=$form['fk_usuario_id'];
				$thread['fk_formulario_id']=$form['formulario_id'];
				$thread['fk_pregunta_id']=$val3['pregunta_id'];
				$thread['thread_requerido']='false';
				$sql=$this->db->executeSingle($this->db->getSQLInsert($thread,'threads'));
				if(!$sql['estado'])$this->getJSON($this->db->closeTransaction($sql));
			}
		}	
		// RETORNAR CONSULTA 
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * OBTENER REQUERIMIENTOS PARA FORMULARIOS
	 */
	public function getRequirements(){
		// MODELO DE REQUERIMIENTOS
		$aux=array();
		// OBTENER LISTA DE REQUERIMIENTOS PARA FORMULARIO
		foreach($this->db->findAll($this->db->selectFromView("req","ORDER BY req_id")) as $v){
			// LISTAR REQUERIMIENTOS SECUNDARIOS
			$aux[$v['req_nombre']]=$this->db->findAll($this->db->selectFromView('requerimientos',"WHERE fk_req_id={$v['req_id']} ORDER BY requerimiento_nombre"));
		}	
		// RETORNAR LISTA DE REQUERIMIENTOS
		$this->getJSON($aux);
	}
	
	/*
	 * SETEAR REQUERIMIENTOS PARA FORMULARIOS
	 */
	public function toogleRequirements(){
		// MODELO DE REQUERIMIENTOS
		$post=$this->requestPost();
		extract($post);
		// DEFINIR ENTIDAD
		$tb="frm_has_req";
		// STRING DE CONDICION DE CONSULTA
		$where="WHERE fk_requerimiento_id={$fk_requerimiento_id} AND fk_formulario_id={$fk_formulario_id}";
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView($tb,$where);
		// CONSULTAR SI EXISTE EL RECURSO
		if($this->db->numRows($str)>0){
			$row=$this->db->findOne($str);
			$row['frmreq_estado']=$this->statusChange[$row['frmreq_estado']];
			$sql=$this->db->getSQLUpdate($row,$tb);
		} else $sql=$this->db->getSQLInsert($post,$tb);
		// RETORNAR CONSULTA
		$this->getJSON($this->db->executeSingle($sql));
	}
	
	/*
	 * PREGUNTAS PARA FORMULARIOS
	 * + ai: Paso 3 de AutoInspección
	 */
	public function getThreads(){
		// MODELO DE PREGUNTAS
		$aux=array();
		// LISTADO DE ÍCONOS DE PREGUNTAS
		$icon=array();
		// EXTRAER VARIABLES DE FORULARIO
		extract($this->getURI());
		// RECORRER LISTADO DE REQUERIMIENTOS
		foreach($this->db->findAll($this->db->selectFromView("req","ORDER BY req_id")) as $val){
			// MODELO AUXILIAR DE DATOS
			$axu=array();
			// REQUERIMIENTOS DE FORMULARIOS
			$requirements=$this->db->selectFromView($this->db->setCustomTable('frm_has_req','fk_requerimiento_id','permisos'),"WHERE frmreq_estado='ACTIVO' AND fk_formulario_id=$fk_formulario_id");
			// RECORRER LISTA DE REQUERIMIENTOS PRINCIPALES
			foreach($this->db->findAll($this->db->selectFromView('requerimientos',"WHERE requerimiento_id in ($requirements) AND fk_req_id={$val['req_id']} ORDER BY fk_req_id")) as $key2=>$val2){
				// PREGUNTAS PARA FORMULARIO
				$str=$this->db->selectFromView('preguntas',"WHERE pregunta_tipo='Preguntas' AND fk_requerimiento_id={$val2['requerimiento_id']}");
				// VALIDAR SI EXISTEN PREGUNTAS
				if($this->db->numRows($str)>0){
					$axu[$val2['requerimiento_nombre']]=$this->db->findAll($str);
					$icon[$val2['requerimiento_nombre']]=$val2['requerimiento_icono'];
				}
			}	
			// VALIDAR SI EL MODELO AUXILIAR HA SIDO LLENADO
			if(!empty($axu)) $aux[$val['req_nombre']]=$axu;
		}	
		// VALIDAR SI LA CONSULTA EXISTE
		if(isset($smart_query)) return array(); 
		// RETORNAR DATOS
		$this->getJSON(array('threads'=>$aux,'icons'=>$icon));
	}
	
	/*
	 * SETEAR PREGUNTAS PARA FORMULARIOS
	 */
	public function toogleThreads(){
		// OBTENER DATOS DE ENVÍO
		$post=$this->requestPost();
		// EXTRAER VARIABLES
		extract($post);
		// DEFINER ENTIDAD
		$tb="threads";
		// STRING DE CONSULTA
		$str=$this->db->selectFromView($tb,"WHERE fk_pregunta_id=$fk_pregunta_id and fk_formulario_id=$fk_formulario_id");
		// CONSULTAR SI EXISTE LA PREGUNTA
		if($this->db->numRows($str)>0){
			// OBTENER DATOR DE PREGUNTA
			$row=$this->db->findOne($str);
			// VALIDAR SI LA PREGUNTA ES REQUERIDA O NO Y CAMBIAR EL ESTADO
			$row['thread_requerido']=($row['thread_requerido']===true)?'false':'true';
			// GENERAR STRING DE ACTUALIZACIÓN
			$sql=$this->db->getSQLUpdate($row,$tb);
		}else{
			// GENERAR STRING DE INGRESO 
			$sql=$this->db->getSQLInsert($post,$tb);
		}
		// EJECUTAR CONSULTA Y RETORNAR DATOS
		$this->getJSON($this->db->executeSingle($sql));
	}
	
	/*
	 * ELIMINAR PREGUNTAS DE UN FORMULARIO 
	 */
	public function deleteThreads(){
		// OBTENER DATOS DE ENVÍO Y EXTRAER VARIABLES
		extract($this->requestPost());
		// OBTENER PREGUNTA DE FORMULARIO
		$row=$this->db->getById('threads',$id);
		// CAMBIAR EL ESTADO REQUERIDO DE PREGUNTA
		$row['thread_estado']=$this->statusChange[$row['thread_estado']];
		// GENERAR STRING DE CONSULTA, EJECUTAR CONSULTA Y RETORNAR DATOS
		$this->getJSON($this->db->executeSingle($this->db->getSQLUpdate($row,'threads')));
	} 
	
	
	/*
	 * FORMULARIO PARA CLIENTE
	 * + ai: Formulario para paso 3 AutoInspección
	 */
	public function getForm(){
		// OBTENER DATOS DE ENVÍO
		$post=$this->requestPost();
		// ID DE ACTIVIDAD ECONOMICA
		$localId=$post['localId'];
		// DATOS DE ACTIVIDAD ECONOMICA
		$local=$this->db->viewById($localId,'locales');
		// ID DE FORMULARIO DE ACTIVIDAD
		$form=$this->getFormByLocal($localId);
		// OBTENER MODELO DE FORMULARIO PARA AUTOINSPECCION
		$formSelfInspection=$this->getModelSelfInspection($local,$form);
		// MODELO DE DATOS
		$data=array(
			'local'=>$local,
			'rule'=>$form,
			'locked'=>$formSelfInspection,
			'form'=>array(),
			'config'=>$this->varLocal,
			'icons'=>array(),
			'images'=>array()
		);
		// DATOS DE AUTOINSPECCION
		$data['selfInspection']['items']=$formSelfInspection['items'];
		$data['selfInspection']['threads']=$formSelfInspection['threadsForm'];
		// LISTA DE REQUERIMIENTOS GENERALES
		foreach($this->db->findAll($this->db->selectFromView("req","ORDER BY req_id")) as $val){
			// VARIABLE PARA ALMACENAR DATOS TEMPORALES
			$axu=array();
			// STRING PARA OBTENER REQUERIMIENTOS QUE TIENEN PREGUNTAS
			$requirements=$this->db->selectFromView($this->db->setCustomTable('frm_has_req','fk_requerimiento_id','permisos'),"WHERE frmreq_estado='ACTIVO' AND fk_formulario_id={$form['formulario_id']}");
			foreach($this->db->findAll($this->db->selectFromView('requerimientos',"WHERE requerimiento_id in ($requirements) AND fk_req_id={$val['req_id']} ORDER BY fk_req_id")) as $val2){
				// GET ICONS
				$data['icons'][$val2['requerimiento_nombre']]=$val2['requerimiento_icono'];
				$data['images'][$val2['requerimiento_nombre']]=($val2['requerimiento_has_img']=='Si')?$val2['requerimiento_imagen']:"";
				// LISTAR THREADS
				$threads=$this->db->selectFromView('vw_threads',"WHERE thread_estado='ACTIVO' AND fk_requerimiento_id={$val2['requerimiento_id']} AND fk_formulario_id={$form['formulario_id']} ORDER BY fk_pregunta_id");
				if($this->db->numRows($threads)>0) $axu[$val2['requerimiento_nombre']]['threads']=$this->db->findAll($threads);
				// LISTAR ITEMS
				$items=$this->db->selectFromView('vw_items',"WHERE item_estado='ACTIVO' AND fk_requerimiento_id={$val2['requerimiento_id']} AND fk_formulario_id={$form['formulario_id']}");
				if($this->db->numRows($items)>0) $axu[$val2['requerimiento_nombre']]['items']=$this->db->findOne($items);
			}	
			// VALIDAR SI SE INGRESÓ AL MENOS UN REGISTRO EN EL LISTADO DE PREGUNTAS E ITEMS
			if(!empty($axu)) $data['form'][$val['req_nombre']]=$axu;
		}
		// RETORNAR DATOS DE FORMULARIO
		$this->getJSON($this->setJSON("OK",true,$data));
	}
	
	
	/*
	 * IMPRESIÓN DE FORMULARIO DESDE AUTOINSPECCION
	 * + ai: Impresión de requerimientos para locales en AutoInspección
	 * + system: Impresión de requerimientos para locales en AutoInspección
	 */
	public function printFormSelfInspection(){
		extract($this->getURI());
		// CAMBIAR EN: sweepModel->setBarridos  --  formModel->getForm
		$form=array();
		// OBTENER CIIU
		$id=$local;
		$local=$this->db->findOne($this->db->selectFromView('vw_locales',"WHERE local_id=$id"));
		// VERIFICAR FORMULARIO PARA CIIU
		$strCiiu=$this->db->getSQLSelect($this->entity,[],"WHERE fk_tabla='tb_ciiu' and fk_tabla_id={$local['fk_ciiu_id']}");
		// VERIFICAR FORMULARIO PARA MACRO-ACTIVIDAD
		$strActividad=$this->db->getSQLSelect($this->entity,[],"WHERE fk_tabla='tb_actividades' and fk_tabla_id={$local['fk_actividad_id']}");
		// VALIDAR REGLAS (FORMULARIOS)
		if($this->db->numRows($strCiiu)>0)$str=$strCiiu;
		elseif($this->db->numRows($strActividad)>0)$str=$strActividad;
		else $this->getJSON("Aún no ha sido creado el formulario, por favor .....");
		// OBTENER REGLAS DE FORMULARIO Y VALIDAR ACCIÓN
		$rules=$this->db->findAll("$str ORDER BY maximo_area, maximo_pisos, maximo_aforo");
		$firstId=0;
		$max=0;
		$maxId=0;
		$loop=0;
		foreach($rules as $key=>$val){
			$result=0;
			if($local['local_aforo']>$val['maximo_aforo'])$result++;
			if($local['local_plantas']>$val['maximo_pisos'])$result++;
			if($local['local_area']>$val['maximo_area'])$result++;
			if($max<=$result){
				if($loop==0)$firstId=$val['formulario_id'];
				$max=$result;
				$maxId=$val['formulario_id'];
			}	$loop++;
		}
		if($max==0)$maxId=$firstId;
		$form['local']=$this->db->findOne($this->db->getSQLSelect('locales',['entidades'],"WHERE local_id=$id"));
		$form['rule']=$this->db->findOne($this->db->getSQLSelect($this->entity,[],"WHERE formulario_id=$maxId"));
		$form['selfInspection']['threads']=array();
		$form['rule']['score']=$max;
		// temp form
		$temp=$form['rule'];
		$temp['maximo_aforo']=$form['local']['local_aforo'];
		$temp['maximo_area']=$form['local']['local_area'];
		$temp['maximo_pisos']=$form['local']['local_plantas'];
		$form['locked']=$this->getResourcesForm($temp);
		$form['selfInspection']['items']=$form['locked']['items'];
		$form['selfInspection']['threads']=$form['locked']['threadsForm'];
		// VERIFICAR AUTOINSPECCIONES
		$strAI=$this->db->getSQLSelect('autoinspecciones',[],"WHERE fk_local_id={$local['local_id']}");
		if($this->db->numRows($strAI)){
			$ai=$this->db->findOne($strAI);
			$form['ai']['threads']=array();
			$form['ai']['items']=array();
			$form['inspection']['threads']=array();
			$form['inspection']['items']=array();
			foreach($this->db->findAll($this->db->getSQLSelect('ai_has_threads',[],"WHERE fk_autoinspeccion_id={$ai['autoinspeccion_id']}")) as $key3=>$val3){
				$form['ai']['threads'][$val3['fk_thread_id']]=$val3['request_local'];
				$form['inspection']['threads'][$val3['fk_thread_id']]=$val3['request_inspector'];
			}
			foreach($this->db->findAll($this->db->getSQLSelect('ai_has_items',[],"WHERE fk_autoinspeccion_id={$ai['autoinspeccion_id']}")) as $key3=>$val3){
				$form['ai']['items'][$val3['fk_item_id']]=$val3['request_local'];
				$form['inspection']['items'][$val3['fk_item_id']]=$val3['request_inspector'];
			}	$form['inspection']['form']=$ai;
		}
		// LISTAR PREGUNTAS E ITEMS
		$aux=array();
		$icon=array();
		foreach($this->db->findAll($this->db->getSQLSelect("req",[],"ORDER BY req_id")) as $key=>$val){
			$axu=array();
			$requirements=$this->db->getSQLSelect($this->db->setCustomTable('frm_has_req','fk_requerimiento_id','permisos'),[],"WHERE frmreq_estado='ACTIVO' AND fk_formulario_id={$form['rule']['formulario_id']}");
			foreach($this->db->findAll($this->db->getSQLSelect('requerimientos',[],"WHERE requerimiento_id in ($requirements) AND fk_req_id={$val['req_id']} ORDER BY fk_req_id")) as $key2=>$val2){
				// GET ICONS
				$icon[$val2['requerimiento_nombre']]=$val2['requerimiento_icono'];
				// LISTAR THREADS
				$threads=$this->db->findAll($this->db->getSQLSelect('threads',['preguntas'],"WHERE fk_requerimiento_id={$val2['requerimiento_id']} AND fk_formulario_id=$maxId ORDER BY pregunta_id"));
				if(!empty($threads)){
					$axu[$val2['requerimiento_nombre']]['threads']=$threads;
				}	// LISTAR ITEMS
				$items=$this->db->findOne($this->db->getSQLSelect('items',['preguntas'],"WHERE preguntas.fk_requerimiento_id={$val2['requerimiento_id']} AND fk_formulario_id=$maxId"));
				if(!empty($items))$axu[$val2['requerimiento_nombre']]['items']=$items;
			}	if(!empty($axu))$aux['form'][$val['req_nombre']]=$axu;
		}	$data=array_merge($this->setJSON("OK",true),array('data'=>array_merge($form,$aux,array('icons'=>$icon))));
		
		
		// COMIENZA LA IMPRESIÓN DEL FORMULARIO
		$aiDetail=file_get_contents(REPORTS_PATH."detailLocal.html");
		$extraData=$this->db->findOne($this->db->getSQLSelect('ciiu',['tasas'],"WHERE ciiu_id={$data['data']['local']['fk_ciiu_id']}"));
		$extraData=array_merge($extraData,$this->db->findOne($this->db->getSQLSelect('actividades',[],"WHERE actividad_id={$extraData['fk_actividad_id']}")));
		$extraData['pago_permiso']=($extraData['tasa_riesgomoderado']/100)*$this->varGlobal['BASIC_SALARY'];
		foreach(array_merge($data['data']['local'],$extraData) as $k=>$v){$aiDetail=preg_replace('/{{'.$k.'}}/',$v,$aiDetail);}
		
		foreach($data['data']['form'] as $key1=>$val1){
			$aiDetail2="";
			// RECORRER REQUERIMIENTOS
			foreach($val1 as $key=>$val){
				$tempHeader="<tr><td colspan=2 class='text-center'>$key</td></tr>";
				$auxHTML="";
				$hasItem=false;
				// PRINT THREADS
				foreach($val['threads'] as $key2=>$val2){
					$val2['thread_requerido']=$data['data']['selfInspection']['threads'][$val2['thread_id']];
					if($val2['thread_requerido']=='SI'){
						$hasItem=true;
						$auxHTML.=file_get_contents(REPORTS_PATH."detailFormAILocal.html");
						foreach($val2 as $k=>$v){$auxHTML=preg_replace('/{{'.$k.'}}/',$v,$auxHTML);}
					} 
				}	
				if($hasItem){
					$auxHTML="$tempHeader $auxHTML";
					$auxHTML=preg_replace('/{{item_cantidad}}/','',$auxHTML);
					// PRINT ITEMS
					if(isset($val['items'])){
						$auxHTML.=file_get_contents(REPORTS_PATH."detailFormAILocal.html");
						foreach($val['items'] as $k=>$v){$auxHTML=preg_replace('/{{'.$k.'}}/',$v,$auxHTML);}
						$auxHTML=preg_replace('/{{thread_requerido}}/','',$auxHTML);
					}
				}	$aiDetail2.=$auxHTML;
			}	
			$bodyContainer=file_get_contents(REPORTS_PATH."formAILocal.html");
			$bodyContainer=preg_replace('/{{selfInspection}}/',$aiDetail2,$bodyContainer);
			$aiDetail.=$bodyContainer;
		}	return $aiDetail;
	}
	
	/*
	 * MÉTODO PARA EXTRAER LOS REQUERIMIENTOS DE UN ESTABLECIMIENTO
	 * + ai: Impresión de AutoInspección
	 * + system: Impresión de AutoInspección
	 */
	public function getLocalSelfInspection($id){
		// VERIFICAR AUTOINSPECCIONES
		$ai=$this->db->findById($id,'autoinspecciones');
		// LISTAR PREGUNTAS E ITEMS
		$data=array('data'=>array());
		// LISTADO DE THREADS
		$aiThreads=$this->db->findAll($this->db->getSQLSelect('ai_has_threads',['threads'],"WHERE fk_autoinspeccion_id={$ai['autoinspeccion_id']}"));
		$aiThreadsList=array();
		foreach($aiThreads as $key=>$val){
			$aiThreadsList[$val['fk_thread_id']]=$val;
		}
		// LISTADO DE ITEMS
		$aiItems=$this->db->findAll($this->db->getSQLSelect('ai_has_items',['items'],"WHERE fk_autoinspeccion_id={$ai['autoinspeccion_id']}"));
		$aiItemsList=array();
		foreach($aiItems as $key=>$val){
			$aiItemsList[$val['fk_item_id']]=$val;
		}
		// LISTADO DE REQUERIMIENTOS PARA FORMULARIO
		foreach($this->db->findAll($this->db->getSQLSelect("req",[],"ORDER BY req_id")) as $key=>$val){
			$axu=array();
			$requirements=$this->db->getSQLSelect($this->db->setCustomTable('frm_has_req','fk_requerimiento_id','permisos'),[],"WHERE frmreq_estado='ACTIVO' AND fk_formulario_id={$ai['fk_formulario_id']}");
			$requirementsList=$this->db->findAll($this->db->getSQLSelect('requerimientos',[],"WHERE requerimiento_id in ($requirements) AND fk_req_id={$val['req_id']} ORDER BY fk_req_id"));
			foreach($requirementsList as $key2=>$val2){
				// LISTAR THREADS
				$threads=$this->db->findAll($this->db->getSQLSelect('threads',['preguntas'],"WHERE fk_requerimiento_id={$val2['requerimiento_id']} AND fk_formulario_id={$ai['fk_formulario_id']} ORDER BY pregunta_id"));
				if(!empty($threads)){
					foreach($threads as $key3=>$val3){
						if(isset($aiThreadsList[$val3['thread_id']])){
							$axu[$val2['requerimiento_nombre']]['threads'][]=array_merge($val3,$aiThreadsList[$val3['thread_id']]);
						}
					}
				}
				// LISTAR ITEMS
				$items=$this->db->findOne($this->db->getSQLSelect('items',['preguntas'],"WHERE preguntas.fk_requerimiento_id={$val2['requerimiento_id']} AND fk_formulario_id={$ai['fk_formulario_id']}"));
				if(!empty($items)){
					if(isset($aiItemsList[$items['item_id']])){
						$axu[$val2['requerimiento_nombre']]['items']=array_merge($items,$aiItemsList[$items['item_id']]);
					}
				}
			}	$data['data'][$val['req_nombre']]=$axu;
		}
		// COMIENZA LA IMPRESIÓN DEL FORMULARIO
		$aiDetail=file_get_contents(REPORTS_PATH."detailLocal.html");
		$data['local']=$this->db->findOne($this->db->selectFromView('vw_locales',"WHERE local_id={$ai['fk_local_id']}"));
		$data['local']=array_merge($data['local'],$this->db->findById($data['local']['fk_actividad_id'],'actividades'));
		//$extraData['pago_permiso']=($extraData['tasa_riesgomoderado']/100)*$this->varGlobal['BASIC_SALARY'];
		foreach($data['local'] as $k=>$v){$aiDetail=preg_replace('/{{'.$k.'}}/',$v,$aiDetail);}
		$hasLocal=false;
		foreach($data['data'] as $key1=>$val1){
			$aiDetail2="";
			// RECORRER REQUERIMIENTOS
			foreach($val1 as $key=>$val){
				$hasLocal=true;
				$tempHeader="<tr><td colspan=2 class='text-center'>$key</td></tr>";
				$auxHTML="";
				// PRINT THREADS
				foreach($val['threads'] as $key2=>$val2){
					$auxHTML.=file_get_contents(REPORTS_PATH."detailFormAIRequestLocal.html");
					foreach($val2 as $k=>$v){$auxHTML=preg_replace('/{{'.$k.'}}/',$v,$auxHTML);}
				}	$auxHTML="$tempHeader $auxHTML";
				// PRINT ITEMS
				if(isset($val['items']) && !empty($val['items'])){
					$auxHTML.=file_get_contents(REPORTS_PATH."detailFormAIRequestLocal.html");
					foreach($val['items'] as $k=>$v){$auxHTML=preg_replace('/{{'.$k.'}}/',$v,$auxHTML);}
				}	$aiDetail2.=$auxHTML;
			}
			$bodyContainer=file_get_contents(REPORTS_PATH."formAILocal.html");
			$bodyContainer=preg_replace('/{{selfInspection}}/',$aiDetail2,$bodyContainer);
			$aiDetail.=$bodyContainer;
		}	
		if(!$hasLocal){
			$bodyContainer=file_get_contents(REPORTS_PATH."formAILocal.html");
			$bodyContainer=preg_replace('/{{selfInspection}}/',"<tr><td colspan=2 class='text-center'>".$this->getConfParams('AI')['SYS_MSG_EMPTY_SI']."</td></tr>",$bodyContainer);
			$aiDetail.=$bodyContainer;
		}	return $aiDetail;
	}
	
}