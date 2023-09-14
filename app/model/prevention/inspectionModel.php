<?php namespace model\prevention;
use model as mdl;

class inspectionModel extends mdl\personModel {

	/*
	 * VARIABLES LOCALES
	 */
	private $entity='inspecciones';
	private $config;
	private $model;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		parent::__construct();
		// VARIABLE LOCAL DE MÓDULO - CAPACITACIONES
		$this->config=$this->getConfig(true,'prevention');
	}
	
	
	/*
	 * 1. CONSULTAR RUC O PERMISO PARA BARRIDOS
	 * A. BUSCAR INSPECCIÓN POR CÓDIGO
	 * B. LISTAR LOS ESTABLECIMIENTOS QUE TIENE UN RUC
	 * C. BUSCAR ESTABLECIMIENTO POR CODIGO DE PERMISO
	 */
	public function requestSweeps(){
		// A. BUSCAR INSPECCIÓN POR CÓDIGO
		// BUSCAR UNA INSPECCIÓN POR CÓDIGO
		$str=$this->db->selectFromView($this->entity,"WHERE UPPER(inspeccion_codigo)=UPPER('{$this->post['code']}')");
		// CONSULTAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DE INSPECCION
			$row=$this->db->findOne($str);
			// TIPO  DE CONSULTA
			$row['entity']='inspection';
			// RETORNAR INFORMACION
			$this->getJSON($this->setJSON("Inspección encontrada!",true,$row));
		}
		
		// MODELO DE RETORNO
		$data=array(
			'modal'=>'Sweeps',
			'selected'=>array(),
			'entity'=>'',
			'list'=>array()
		);
		
		// B. LISTAR LOS ESTABLECIMIENTOS QUE TIENE UN RUC
		// GENERAR STRING PARA CONSULTAR LOCALES QUE NO HAN SIDO INSPECCIONADOS EN EL PRESENTE AÑO
		$str=$this->db->selectFromView("vw_locales","WHERE (entidad_ruc='{$this->post['code']}' AND local_estado='ACTIVO') AND (inspeccion_id<0 OR date_part('year',inspeccion_date)<>date_part('year',CURRENT_DATE) OR inspeccion_date IS NULL)");
		// OBTENER NÚMERO TOTAL DE REGISTROS
		$num=$this->db->numRows($str);
		// COMPROBAR SI EXISTEN LOCALES CON ESE RUC
		if($num>0){
			// RECORRER EL LISTADO DE ESTABLECIMIENTOS DEL RUC
			foreach($this->db->findAll($str) as $local){
				// COMPROBAR SI EL ESTABLECIMIENTO YA HA SACADO EL PERMISO ANUAL DE FUNCIONAMIENTO
				if($local['codigo_per']=='0'){
					// STRING PARA OBTENER EL CODIGO PER
					$tb=$this->db->setCustomTable('vw_informacion_permisos',"COALESCE(codigo_per,'0') codigo_per");
					// GENERAR STRING DE CONSULTA Y OBTENER PERMISO DE FUNCIONAMIENTO
					$permit=$this->db->findOne($this->db->selectFromView($tb,"WHERE local_id={$local['local_id']} ORDER BY permiso_fecha DESC LIMIT 1"));
					// INSERTAR CODIGO PER
					$local['codigo_per']=$permit['codigo_per'];
				}
				// LISTAR ESTABLECIMIENTO
				$data['list'][]=$local;
			}
			// MODAL A DESPLEGAR
			$data['entity']='locals';
			// RETORNAR INFORMACION
			$this->getJSON($this->setJSON("{$num} Actividad(es) económica(s) encontrada(s).",true,$data));
		}
		
		// C. BUSCAR ESTABLECIMIENTO POR CODIGO DE PERMISO
		// GENERAR STRING PARA CONSULTAR - PERMISO
		$str=$this->db->selectFromView("vw_permisos","WHERE UPPER(codigo_per)=UPPER('{$this->post['code']}') AND date_part('year',permiso_fecha)=date_part('year',CURRENT_DATE)");
		// COMPROBAR SI EXISTE EL PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DEL PERMISO
			$permiso=$this->db->findOne($str);
			// STRING PARA CONSULTAR SI EL ESTABLECIMIENTO RELACIONADO AL PERMISO HA SIDO INSPECCIONADO
			$_str=$this->db->selectFromView('inspeccion_local',"WHERE fk_local_id={$permiso['local_id']}");
			// VALIDAR SI EL ESTABLECIMIENTO NO HA SIDO INSPECCIONADO
			if($this->db->numRows($_str)>0) $this->getJSON("El establecimiento relacionado al PERMISO ANUAL DE FUNCIONAMIENTO <b>{$this->post['code']}</b> ya ha sido inspeccionado.");
			// OBTENER INFORMACION
			$data['list']=$this->db->findAll($str);
			// MODAL A DESPLEGAR
			$data['entity']='permits';
			// RETORNAR INFORMACION
			$this->getJSON($this->setJSON("Permiso de funcionamiento encontrado!",true,$data));
		}
		
		// RETORNAR MENSAJE POR DEFECTO
		$this->getJSON("No se ha encontrado ninguna información relacionada con su dato de ingreso: <b>{$this->post['code']}</b>");
	}
	
	
	/*
	 * 2.C.1 VALIDAR QUE EL ESTABLECIMIENTO NO HA SIDO INSPECCIONADO PREVIAMENTE EN EL MISMO AÑO
	 */
	private function requestInspectionByLocal($localId){
		// STRING PARA CONSULTAR EXISTENCIA DE INSPECCION EN EL PRESENTE AÑO
		$str=$this->db->selectFromView("inspeccion_local","WHERE fk_local_id={$localId} AND date_part('year',inspeccion_fecha)=date_part('year',CURRENT_DATE)");
		// COMPROBAR SI EXISTE LA INSPECCION
		if($this->db->numRows($str)>0){
			// OBTENER INSPECCION
			$row=$this->db->findOne($str);
			// MENSAJE QUE YA SE HA REGISTRADO BARRIDO
			return $this->setJSON("Ya se ha registrado esta inspección, búsquela con el código <b>{$row['inspeccion_codigo']}</b>");
		}
		// MENSAJE POR DEFECTO
		return $this->setJSON("ok",true);
	}
	
	/*
	 * 2.C REGISTRAR LOCALES
	 */
	private function insertLocal($inspectionId,$inspectionDate,$localId){
		// VALIDAR LA EXISTENCIA DE UNA INSPECCIÓN EN EL PRESENTE AÑO
		$sql=$this->requestInspectionByLocal($localId);
		// COMPROBAR ESTADO DE VALIDACIÓN
		if(!$sql['estado']) $this->getJSON($this->db->closeTransaction($sql));
		// DATOS DE LOCAL
		$local=$this->db->findById($localId,'locales');
		// MODELO PARA INSERTAR RELACIÓN CON LOCALES
		$aux=array(
			'fk_local_id'=>$localId,
			'fk_inspeccion_id'=>$inspectionId,
			'inspeccion_fecha'=>$inspectionDate,
			'inspeccion_area'=>$local['local_area'],
			'inspeccion_area_construccion'=>$local['local_area_construccion'],
			'inspeccion_aforo'=>$local['local_aforo'],
			'inspeccion_plantas'=>$local['local_plantas']
		);
		// INSERTAR RELACIÓN
		return $this->db->executeTested($this->db->getSQLInsert($aux,'inspeccion_local'));
	}
	
	/*
	 * 2.B.2 REGISTRAR INSPECTORES
	 */
	private function insertInspector($inspectionId,$inspector,$type='PRINCIPAL'){
		// RELACION DE INSPECCION CON INSPECTOR
		$review=array(
			'fk_inspector_id'=>$inspector,
			'fk_inspeccion_id'=>$inspectionId,
			'inspector_tipo'=>$type
		);
		// REGISTRAR INSPECTOR PARA INSPECCIONES
		return $this->db->executeTested($this->db->getSQLInsert($review,'inspeccion_inspector'));
	}
	
	
	/*
	 * 2.B.1 REGITRAR INSPECCION
	 */
	private function insertInspection($inspectorList){
		/// REGISTRAR INSPECCION
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->model,$this->entity));
		// OBTENER ID DE INSPECCION
		$inspectionId=$this->db->getLastID($this->entity);
		// INSERTAR ID DE INSPECCION
		$this->model['inspeccion_id']=$inspectionId;
		// REGISTRAR INSPECTORES
		foreach($inspectorList as $v){
			$sql=$this->insertInspector($inspectionId,$v);
		}
		// MENSAJE DE RETORNO - ADJUNTAR ID DE INSPECCION
		$sql['data']=array('id'=>$inspectionId);
		// RETORNAR DATOS
		return $sql;
	}
	
	
	/*
	 * 2. REGISTRO DE BARRIDOS
	 * A.1 VERIFICAR QUE SE HAYAN SELECCIONADO LOS ESTABLECIMIENTOS
	 * A.2 VERIFICAR QUE SE HAYAN SELECCIONADO LOS INSPECTORES
	 * B. REGISTRAR BARRIDO E INSPECTOR PRINCIPAL
	 * C. REGISTRAR ESTABLECIMIENTOS PARA INSPECCIÓN
	 */
	public function setSweeps(){
		
		// A.1 VALIDAR SI SE HA SELECCIONADO AL MENOS UN ESTABLECIMIENTO
		if(!isset($this->post['selected']) || count($this->post['selected'])<1 || empty($this->post['selected'])){
			$this->getJSON("Para continuar debe seleccionar al menos un establecimiento!");
		}
		// A.2 VALIDAR SI SE HA SELECCIONADO AL MENOS UN INSPECTOR
		if(!isset($this->post['selectedInspectors']) || count($this->post['selectedInspectors'])<1 || empty($this->post['selectedInspectors'])){
			$this->getJSON("Para continuar debe seleccionar al personal responsable de la inspección!");
		}
		
		// GENERAR MODELO DE BARRIDO
		$this->model=array(
			'inspeccion_tipo'=>'BARRIDO',
			'inspeccion_fecha_inspeccion'=>$this->post['inspeccion_fecha_inspeccion']
		);
		// COMENZAR TRANSACCION
		$this->db->begin();
		// B. REGISTRAR BARRIDO
		$sql=$this->insertInspection($this->post['selectedInspectors']);
		// C. REGISTRAR ESTABLECIMIENTOS PARA INSPECCIÓN
		foreach($this->post['selected'] as $localId){
			// INSERTAR RELACIÓN
			$this->insertLocal($this->model['inspeccion_id'],$this->model['inspeccion_fecha_inspeccion'],$localId);
		}
		// CERRAR TRANSACCION Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
		
	}
	
	
	/*
	 * 3. INGRESO DE INSPECTORES
	 */
	public function setInspectors(){
		// COMENZAR TRANSACCION
		$this->db->begin();
		// ELIMINAR REGISTRO DE INSPECTORES QUE NO SON PRINCIPALES
		$this->db->executeTested($this->db->getSQLDelete('inspeccion_inspector',"WHERE inspector_tipo<>'PRINCIPAL' AND fk_inspeccion_id={$this->post['inspeccion_id']}"));
		// INGRESAR LOS INSPECTORES
		foreach($this->post['selected'] as $v){
			$sql=$this->insertInspector($this->post['inspeccion_id'],$v,'SECUNDARIO');
		}
		// CERRAR TRANSACCION Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * LISTADO DE RECURSOS Y COMENTARIOS
	 */
	protected function getResourcesByInspection($inspectionId){
		// MODELO DE RECURSOS
		$data=array(
			'src'=>$this->getResources(),
			'model'=>array(),
			'fails'=>array(),
			'frmParent'=>array()
		);
		
		// REEMPLAZAR MODELO DE COMENTARIOS POR MODELO DE BASE DE DATOS
		foreach($this->db->findAll($this->db->getSQLSelect('inspeccion_comentarios',['recursosinspecciones'],"WHERE fk_inspeccion_id={$inspectionId} AND comentario_require='NO'")) as $key=>$val){
			// REEMPLAZAR MODELO DE RECURSOS POR MODELO DE BASE DE DATOS
			foreach($this->db->findAll($this->db->selectFromView('vw_inspeccion_recursos',"WHERE regla_recurso_id={$val['recurso_id']} AND fk_inspeccion_id={$inspectionId}")) as $k=>$v){
				// CLASIFICAR EL RECURSO
				if(!isset($data['fails'][$val['recurso_clasificacion']][$v['recurso_nombre']])) $data['fails'][$v['recurso_clasificacion']][$v['recurso_nombre']]=array();
				// VALIDAR TIPO DE RECURSO 
				if((in_array($v['regla_tipo'],['CONTROL','AUMENTAR','MANTENIMIENTO']) && $v['incrementar_recurso']=='SI') || (in_array($v['regla_tipo'],['CUMPLE']) && $v['cumple_recurso']=='NO') || (in_array($v['regla_tipo'],['IMPLEMENTAR']) && $v['implementar_recurso']=='SI') || (in_array($v['regla_tipo'],['INFORME']) && $v['informe_tecnico']=='SI') || (in_array($v['regla_tipo'],['ACTUALIZAR']) && in_array($v['implementar_recurso'],['IMPLEMENTAR','ACTUALIZAR']))){
					// REGISTRAR EL RECURSO
					$data['fails'][$v['recurso_clasificacion']][$v['recurso_nombre']][$v['regla_id']]=$v;
					// REGISTRAR RECURSO EN MODELO
					$data['model'][$v['regla_id']]=array(
						'fk_recurso_id'=>$v['regla_id'],
						'implementacion_aplica'=>'NO APLICA'
					);
				}
			}
		}
		
		// ELIMINAR REGISTROS VACIOS
		foreach($data['fails'] as $key=>$val){
			// VALIDAR LONGITUD
			if(count($val)<1) unset($data['fails'][$key]);
			else{
				foreach($val as $k=>$v){ if(count($v)<1) unset($data['fails'][$key][$k]); }
			}
		}
		
		// RETORNAR MODELO
		return $data;
	}
	
	/*
	 * CREAR MODELO DE RECURSOS Y COMENTARIOS DE INSPECCION
	 */
	protected function setInspectionModel($inspectionId){
		// MODELO DE RECURSOS
		$data=array(
			'src'=>array(),
			'comments'=>array()
		);
		
		// LISTA DE RECURSOS - GENERAR MODELO
		foreach($this->db->findAll($this->db->selectFromView('reglasinspecciones')) as $v){
			// INGRESAR MODELO
			$data['src'][$v['regla_id']]=$this->config['inspections']['resource'];
		}
		// REEMPLAZAR MODELO DE RECURSOS POR MODELO DE BASE DE DATOS
		foreach($this->db->findAll($this->db->selectFromView('inspeccion_recursos',"WHERE fk_inspeccion_id={$inspectionId}")) as $v){
			// VALIDAR SI EXISTE EL RECURSO EN EL MODELO
			$data['src'][$v['fk_recurso_id']]=$v;
		}
		
		// LISTA DE COMENTARIOS DE RECURSOS - GENERAR MODELO
		foreach($this->db->findAll($this->db->selectFromView('recursosinspecciones')) as $v){
			// INGRESAR MODELO
			$data['comments'][$v['recurso_id']]=$this->config['inspections']['comment'];
		}
		// REEMPLAZAR MODELO DE COMENTARIOS POR MODELO DE BASE DE DATOS
		foreach($this->db->findAll($this->db->selectFromView('inspeccion_comentarios',"WHERE fk_inspeccion_id={$inspectionId}")) as $v){
			// VALIDAR SI EXISTE EL RECURSO EN EL MODELO
			$data['comments'][$v['fk_recurso_id']]=$v;
		}
		
		// RETORNAR MODELO
		return $data;
	}
	
	
	/*
	 * LISTA DE RECURSOS PARA LA INSPECCION
	 */
	private function getResources(){
		// MODELO DE RECURSOS
		$data=array();
		$aux=array();
		$axu=array();
		$src=array();
		$icon=array();
		
		// LISTA DE RECURSOS DE INSPECCIONES
		$resources=$this->db->findAll($this->db->selectFromView('recursosinspecciones',"ORDER BY recurso_id"));
		
		// GENERAR MODELO DE INSPECCION
		foreach($resources as $v){
			// LISTADO DE ICONOS
			if(!isset($icon[$v['recurso_nombre']])) $icon[$v['recurso_nombre']]=$v['recurso_icon'];
			// GENERAR MODELO DE RECURSO
			if(!isset($data[$v['recurso_clasificacion']])) $data[$v['recurso_clasificacion']]=array();
			
			// INGRESAR NOMBRE DE RECURSO
			if(!isset($data[$v['recurso_clasificacion']][$v['recurso_nombre']])){
				$data[$v['recurso_clasificacion']][$v['recurso_nombre']]=array();
				$axu[$v['recurso_nombre']]=$v['recurso_id'];
				$src[$v['recurso_id']]=$v['recurso_nombre'];
			}
			
			// LISTA DE REGLAS O RECURSOS NIVEL 2 & INSERTAR AL MODELO DE RECURSOS
			foreach($this->db->findAll($this->db->selectFromView('reglasinspecciones',"WHERE fk_recurso_id={$v['recurso_id']} ORDER BY regla_id")) as $v2){
				// REGISTRAR REGLAS POR CADA RECURSO
				$data[$v['recurso_clasificacion']][$v['recurso_nombre']][]=$v2['regla_id'];
				// MODELO DE RECURSOS
				$aux[$v2['regla_id']]=array(
					'src_name'=>$v2['regla_nombre'],
					'src_type'=>$v2['regla_tipo']
				);
			}
			
		}
		// RETORNAR RECURSOS
		return array(
			'icon'=>$icon,
			'list'=>$data,
			'info'=>$aux,
			'srcId'=>$src,
			'comments'=>$axu
		);
	}
	
	
	/*
	 * VALIDAR PLANES DE EMERGENCIA DE LOCALES
	 */
	private function validatePlanByLocal($localId){
		// INFORMACIÓN DE LOCAL
		$local=$this->db->findById($localId,'locales');
		// MODELO DE CONSULTA
		$plan=array('local_nombrecomercial'=>$local['local_nombrecomercial']);
		
		// INFORMACION DE REGISTRO DE PLAN DE EMERGENCIA
		$str=$this->db->selectFromView("vw_planesemergencia","WHERE fk_local_id={$localId} ORDER BY plan_id DESC LIMIT 1");
		
		// VALIDAR SI TIENE PLAN DE EMERGENCIA
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PLAN
			$plan=$this->db->findOne($str);
			// VALIDAR FECHA DE VALIDEZ
			if($plan['plan_estado']=='APROBADO' && strtotime($plan['plan_caduca'])<strtotime($this->getFecha())){
				// ANULAR PLAN DE EMERGENCIA
				$plan['plan_estado']='VENCIDO';
				// ACTUALIZAR REGISTRO DE PLAN
				$this->db->executeTested($this->db->getSQLUpdate($plan,'planesemergencia'));
			}
		}else return null;
		
		// VALIDAR INFORMACIÓN DE PLAN
		$status="";
		if(!isset($plan['plan_estado'])) $status='NA';
		elseif($plan['plan_estado']=='SUSPENDIDO') $status='NA';
		elseif($plan['plan_estado']=='APROBADO') $status='VIGENTE';
		elseif($plan['plan_estado']=='VENCIDO') $status='ACTUALIZAR';
		elseif($plan['plan_estado']=='PENDIENTE') $status='IMPLEMENTAR';		
		elseif($plan['plan_estado']=='NO APLICA') $status='NO APLICA';		
		else $status='EN TRAMITE';
		// RETORNAR CONSULTA
		return array('plan'=>$plan,'status'=>$status);
	}
	
	
	/*
	 * INFORMACIÓN: PREINSPECCIÓN
	 */
	private function getPreinspection($inspection,$data){
		// DATOS POR DEFECTO DE INSPECCIÓN
		$data['i']['inspeccion_estado']=($inspection['inspeccion_estado']=='PENDIENTE'?'NO CUMPLE':$inspection['inspeccion_estado']);
		// INFORMACION DE REGISTRO DE CITACIONES
		$str=$this->db->selectFromView("citaciones","WHERE fk_inspeccion_id={$inspection['inspeccion_id']}");
		$data['citations']=array(
			'total'=>$this->db->numRows($str),
			'data'=>$this->db->findAll($str)
		);
		// INFORMACION DE REGISTRO DE REINSPECCIONES
		$str=$this->db->selectFromView("vw_reinspecciones","WHERE fk_inspeccion_id={$inspection['inspeccion_id']} ORDER BY reinspeccion_registro DESC");
		$data['reinspections']=array(
			'total'=>$this->db->numRows($str),
			'data'=>$this->db->findAll($str)
		);
		// RELACIÓN DE INSPECCIÓN CON LOCALES
		$str=$this->db->selectFromView("inspeccion_local","WHERE fk_inspeccion_id={$inspection['inspeccion_id']}");
		$data['locals']=array();
		// LISTAR LOCALES
		foreach($this->db->findAll($str) as $v){
			// INFORMACIÓN DE LOCAL
			$local=$this->db->findById($v['fk_local_id'],'locales');
			// GENERAR REGISTRO POR RUC
			if(!isset($data['locals'][$local['fk_entidad_id']])){
				// REGISRAR ID DE RUC
				$data['locals'][$local['fk_entidad_id']]=array(
					'entity'=>$this->db->viewById($local['fk_entidad_id'],'entidades'),
					'list'=>array()
				);
				// GENERAR ID DE ENTREVISTADO
				if($inspection['fk_entrevistado_id']<1){
					// OBTENER DATOS DE RUC
					$entity=$this->db->findById($local['fk_entidad_id'],'entidades');
					// OBTENER INFORMACIÓN DE REPRESENTANTE LEGAL
					$interviewed=$this->db->findById($entity['fk_representante_id'],'personas');
					$interviewed['entrevistado_cargo']='PROPIETARIO';
				}
			}
			// REGISTRAR LOCALES
			$data['locals'][$local['fk_entidad_id']]['list'][$local['local_id']]=array_merge($local,$v);
		}
		// DATOS DE ENTREVISTADO
		if($inspection['fk_entrevistado_id']>0) $interviewed=$this->db->findById($inspection['fk_entrevistado_id'],'personas');
		$data['entrevistado']=$interviewed;
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * INFORMACIÓN: INSPECCIÓN
	 */
	private function getInspection($inspection,$data){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// DATOS DE INSPECCION
		$model=$this->setInspectionModel($inspection['inspeccion_id']);
		
		// DATOS PARA INSPECCION
		$data['plans']=array();
		$data['model']=$model['src'];
		$data['src']=$this->getResources();
		$data['comments']=$model['comments'];
		
		// INFORMACION DE REGISTRO DE REINSPECCIONES
		$str=$this->db->selectFromView("inspeccion_local","WHERE fk_inspeccion_id={$inspection['inspeccion_id']}");
		
		// VALIDAR ESTADO DE ETIQUETA
		$auxTmp=true;
		
		// LISTAR LOCALES
		foreach($this->db->findAll($str) as $v){
			// VALIDAR PLAN DE EMERGENCIA
			$aux=$this->validatePlanByLocal($v['fk_local_id']);
			
			// INFORMACIÓN Y ESTADO DE PLAN
			$data['plans'][$v['fk_local_id']]=$aux['plan'];
			$data['model'][88]['locals'][$v['fk_local_id']]['implementar_recurso']=$aux['status'];
			
			// VALIDAR SI EXISTE EL PLAN
			if(!isset($aux)){
			    $data['comments'][9]['comentario_require']='NA';
			    continue;
			}
			
			// VALIDAR SI EXISTE ALGUN PLAN EN TRAMITE
			if(!in_array($aux['status'],['VIGENTE']) && $auxTmp) { $data['comments'][9]['comentario_require']='NO'; $auxTmp=false; }
			elseif(in_array($aux['status'],['VIGENTE']) && $auxTmp) $data['comments'][9]['comentario_require']='SI';
		}
		
		// CERRAR TRANSACCIÓN
		$this->db->closeTransaction($this->setJSON("ok",true));
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * INFORMACIÓN: DETALLE
	 */
	private function getDetailInspection($inspection,$data){
		// VARIABLES DE RECURSOS
		$src=$data['src'];
		// ELIMINAR REQUISITOS QUE NO APLICAN
		foreach($src['list'] as $key=>$list){
			// VALIDAR EL TIPO DE RECURSO
			foreach($list as $type=>$srcList){
				// RECORRER LISTADO DE RECURSOS
				foreach($srcList as $srcKey=>$srcId){
					// INFORMACIÓN DE RECURSO
					$info=$src['info'][$srcId];
					// OBTENER INDEX A VALIDAR
					if($info['src_type']=='CONTROL' || $info['src_type']=='AUMENTAR') $idx='incrementar_recurso';
					elseif($info['src_type']=='CUMPLE') $idx='cumple_recurso';
					elseif($info['src_type']=='IMPLEMENTAR') $idx='implementar_recurso';
					elseif($info['src_type']=='INFORME') $idx='informe_tecnico';
					// ILIMINAR EL RECURSO SI NO APLICA
					if($data['model'][$srcId][$idx]=='NA' && $info['src_type']!='ACTUALIZAR') unset($data['src']['list'][$key][$type][$srcKey]);
				}
			}
		}
		// ELIMINAR REQUISITOS QUE NO APLICAN
		foreach($src['list'] as $key=>$list){
			// VALIDAR EL TIPO DE RECURSO
			foreach($list as $type=>$srcList){
				// RECORRER LISTADO DE RECURSOS
				if(count($srcList)<1) unset($data['src']['list'][$key][$type]);
			}
		}
		
		// LISTADO DE INSPECTORES
		$data['inspectors']=$this->db->findAll($this->db->selectFromView("vw_inspeccion_inspector","WHERE fk_inspeccion_id={$inspection['inspeccion_id']} ORDER BY relacion_registro DESC"));
		
		// RETORNAR CONSULTA
		return $data;
	}
	
	
	
	/*
	 * LISTA DE INSPECTORES DE UNA INSPECCION
	 */
	private function requestInspectorsByInspection($inspectionId){
		// MODELO DE DATOS
		$data=$this->db->viewById($inspectionId,$this->entity);
		$data['selected']=array();
		// LISTA DE INSPECTORES
		$data['list']=$this->db->findAll($this->db->selectFromView('vw_informacion_usuarios',"WHERE usuario_id<>{$data['inspector_id']} AND perfil_id=6 AND usuario_estado='ACTIVO'"));
		// INSPECTORES YA INGRESADOS
		foreach($this->db->findAll($this->db->selectFromView('inspeccion_inspector',"WHERE fk_inspeccion_id={$inspectionId}")) AS $v){
			// VALIDAR SI YA SE HA REGISTRADO EL INSPECTOR
			if(!in_array($v['fk_inspector_id'],$data['selected'])) $data['selected'][]=$v['fk_inspector_id'];
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR INSPECCION POR CODIGO
	 */
	private function requestInspectionByCode($code){
		// BUSCAR UNA INSPECCIÓN POR CÓDIGO
		$str=$this->db->selectFromView($this->entity,"WHERE UPPER(inspeccion_codigo)=UPPER('{$code}')");
		// CONSULTAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER Y RETORNAR DATOS DE INSPECCION
			return $this->db->findOne($str);
		}
		// MENSAJE POR DEFECTO
		$this->getJSON("No se ha encontrado el registro");
	}
	
	/*
	 * 4.1 CONSULTAR PROYECTO POR ID
	 */
	protected function requestInspectionById($id,$type){
		// VALIDAR INSPECCIÓN
		$inspection=$this->db->viewById($id,$this->entity);
		// OBTENER FORMULARIO DE INSPECCIÓN
		$data=array('type'=>$type,'i'=>$inspection);
		// VALIDAR EL TIPO DE CONSULTA: PREINSPECCION
		if($type=='PREINSPECCION'){
			$data=$this->getPreinspection($inspection,$data);
		}elseif($type=='INSPECCION'){
			$data=$this->getInspection($inspection,$data);
		}elseif($type=='DETAIL'){
			$data=$this->getPreinspection($inspection,$data);
			$data=$this->getInspection($inspection,$data);
			$data=$this->getDetailInspection($inspection,$data);
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	
	
	
	/*
	 * LISTA DE RECURSOS PARA LA INSPECCION
	 */
	private function getResourcesForSelfInspection(){
	    // MODELO DE RECURSOS
	    $data=array();
	    $aux=array();
	    $icon=array();
	    
	    // GENERAR MODELO DE INSPECCION
	    foreach($this->db->findAll($this->db->selectFromView('vw_recursos_autoinspecciones')) as $v){
	        
	        // LISTADO DE ICONOS
	        if(!isset($icon[$v['recurso_nombre']])) $icon[$v['recurso_nombre']]=$v['recurso_icon'];
	        
	        // GENERAR MODELO DE RECURSO
	        if(!isset($data[$v['recurso_clasificacion']])) $data[$v['recurso_clasificacion']]=array();
	        
	        // REGISTRAR NOMBRE DE RECURSO
	        if(!isset($data[$v['recurso_clasificacion']][$v['recurso_nombre']])) $data[$v['recurso_clasificacion']][$v['recurso_nombre']]=array();
	        
	        // REGISTRAR LISTA DE RECURSO PARA AUTOINSPECCION
	        if(!isset($data[$v['recurso_clasificacion']][$v['recurso_nombre']][$v['regla_nombre']])) $data[$v['recurso_clasificacion']][$v['recurso_nombre']][$v['regla_nombre']]=array();
	        
	        // INGRESAR NOMBRE DE RECURSO
	        $data[$v['recurso_clasificacion']][$v['recurso_nombre']][$v['regla_nombre']][]=$v;
	        
	    }
	    
	    // RETORNAR RECURSOS
	    return array(
	        'icon'=>$icon,
	        'list'=>$data,
	        'info'=>$aux
	    );
	}
	
	/*
	 * CONSULTAR FORMULARIO DE INSPECCION PARA LOCAL
	 */
	protected function requestInspectionforLocalId($localId){
	    
	    // MODELO DE RECURSOS
	    $data=array(
	        'model'=>array(),
	        'src'=>$this->getResourcesForSelfInspection()
	    );
	    
	    // LISTA DE RECURSOS - GENERAR MODELO
	    foreach($this->db->findAll($this->db->selectFromView('reglasinspecciones')) as $v){
	        // INGRESAR MODELO
	        $data['model'][$v['regla_id']]=$this->config['inspections']['resource'];
	    }
	    
	    // RETORNAR DATOS DE CONSULTA
	    return $data;
	    
	}
	
	/*
	 * 4. CONSULTAR REGISTROS
	 */
	public function requestInspections(){
		// 4.1 VALIDAR TIPO DE CONSULTA
	    if(isset($this->post['id'])) $json=$this->requestInspectionById($this->post['id'],$this->post['type']);
	    // CONSULTAR INSPECCION POR CODIGO
	    elseif(isset($this->post['code'])) $json=$this->requestInspectionByCode($this->post['code']);
		// CONSULTAR INSPECTORES ASIGNADOS
		elseif(isset($this->post['type']) && $this->post['type']=='getInspectors') $json=$this->requestInspectorsByInspection($this->post['inspeccion_id']);
		// INFORMACION PARA AUTOINSPECCION
		elseif(isset($this->post['type']) && $this->post['type']=='selfInspection') $json=$this->requestInspectionforLocalId($this->post['localId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * 5.3 INGRESAR / ACTUALIZAR COMENTARIOS
	 */
	private function setComments($inspectionId,$comments){
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("Ok",true);
		// RECORRER LISTADO DE RECURSOS
		foreach($comments as $k=>$v){
			// ACTUALIZAR MODELO
			$v['fk_inspeccion_id']=$inspectionId;
			$v['fk_recurso_id']=$k;
			// GENERAR STRING PARA VALIDAR SI EXISTE O NO EL RECURSO
			$str=$this->db->selectFromView("inspeccion_comentarios","WHERE fk_inspeccion_id={$inspectionId} AND fk_recurso_id={$k}");
			// VALIDAR SI EXISTE RECUROSO
			if($this->db->numRows($str)>0){
				// OBTENER DATOS DE MODELO
				$row=$this->db->findOne($str);
				// INSERTAR PK A RECURSO
				$v['comentario_id']=$row['comentario_id'];
				// GENERAR STRING PARA ACTUALIZACION
				$str=$this->db->getSQLUpdate($v,'inspeccion_comentarios');
			}else{
				// GENERAR STRING PARA ACTUALIZACION
				$str=$this->db->getSQLInsert($v,'inspeccion_comentarios');
			}
			// GENERAR STRING DE ACTUALIZACION Y GENERAR CONSULTA
			$sql=$this->db->executeTested($str);
		}
		// RETORNAR DATOS
		return $sql;
	}
	
	
	/*
	 * 5.2.A REALIZAR ACTIVIDADES DE PLANES DE EMERGENCIA
	 */
	private function planModelRequest($inspectionId,$localId){
		// DATOS DE INSPECCIONES
		$inspectionLocal=$this->db->findOne($this->db->selectFromView("inspeccion_local","WHERE fk_local_id={$localId} AND fk_inspeccion_id={$inspectionId}"));
		$inspection=$this->db->findById($inspectionId,$this->entity);
		// STRING PARA CONSULTAR SI EXISTE PLAN DE EMERGENCIA
		$str=$this->db->selectFromView('planesemergencia',"WHERE fk_local_id={$localId} ORDER BY plan_id DESC LIMIT 1");
		// ESTADOS PARA GENERAR UN NUEVO PLAN
		$statusNewPlan=['VENCIDO','APROBADO']; // ['PENDIENTE','INGRESADO','ASIGNADO','REVISION','EN REVISION','CORRECCION','REVISADO','APROBADO'];this->post[''])
		
		// VERIFICAR SI EL PLAN ESTA EN TRAMITE - NO SE HACE NADA
		if($this->db->numRows($str)>0){
			
			// OBTENER DATOS DE PLAN
			$plan=$this->db->findOne($str);
			
			// VALIDAR ESTADO DE PLAN
			if(!in_array($plan['plan_estado'],$statusNewPlan)) return $this->setJSON("ok",true);
			
		    // VERIFICAR SI EL PLAN ESTA APROBADO - SE GENERA UNO NUEVO
			if($plan['plan_estado']=='APROBADO'){
			    // DATOS PARA ACTUALIZAR REGISTRO DE PLAN
			    $tmpPlan=array(
			        'plan_id'=>$plan['plan_id'],
			        'plan_estado'=>'VENCIDO',
			        'plan_observacion'=>"SE REQUIERE ACTUALIZAR PLAN EN INSPECCION #{$inspection['inspeccion_codigo']}"
			    );
			    // ACTUALIZAR REGISTRO DE PLAN
			    $this->db->executeTested($this->db->getSQLUpdate($tmpPlan, 'planesemergencia'));
			}
			
		}
		
		// MODELO DE PLAN DE EMERGENCIA
		$plan=array(
			'fk_local_id'=>$localId,
			'plan_area'=>$inspectionLocal['inspeccion_area'],
			'plan_aforo'=>$inspectionLocal['inspeccion_aforo'],
			'plan_plantas'=>$inspectionLocal['inspeccion_plantas'],
			'plan_observacion'=>"GENERADO EN INSPECCIÓN #{$inspection['inspeccion_codigo']}"
		);
		// GENERAR STRING DE INGRESO Y EJECUTAR SENTENCIA 
		return $this->db->executeTested($this->db->getSQLInsert($plan,'planesemergencia'));
	}
	
	/*
	 * 5.2 INGRESAR / ACTUALIZAR RECURSOS
	 */
	private function setResources($inspectionId,$resources){
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("Ok",true);
		// RECORRER LISTADO DE RECURSOS
		foreach($resources as $k=>$v){
			// ACTUALIZAR MODELO
			$v['fk_inspeccion_id']=$inspectionId;
			$v['fk_recurso_id']=$k;
			
			// VALIDAR SI EL ITEM DE PLAN DE EMERGENCIA HA SIDO ACTIVADO
			if($k==88){
				// RECORRER LISTADO DE LOCALES EN INSPECCIÓN
				foreach($v['locals'] as $localId=>$local){
					// 5.2.A VALIDAR SI ES NECESARIO IMPLEMENTAR O ACTUALIZAR
					if(in_array($local['implementar_recurso'],['ACTUALIZAR','IMPLEMENTAR'])) $this->planModelRequest($inspectionId,$localId);
				}
			}
			
			// GENERAR STRING PARA VALIDAR SI EXISTE O NO EL RECURSO
			$str=$this->db->selectFromView("inspeccion_recursos","WHERE fk_inspeccion_id={$inspectionId} AND fk_recurso_id={$k}");
			// VALIDAR SI EXISTE RECUROSO
			if($this->db->numRows($str)>0){
				// OBTENER DATOS DE MODELO
				$row=$this->db->findOne($str);
				// INSERTAR PK A RECURSO
				$v['revision_id']=$row['revision_id'];
				// GENERAR STRING PARA ACTUALIZACION
				$str=$this->db->getSQLUpdate($v,'inspeccion_recursos');
			}else{
				// GENERAR STRING PARA ACTUALIZACION
				$str=$this->db->getSQLInsert($v,'inspeccion_recursos');
			}
			// GENERAR STRING DE ACTUALIZACION Y GENERAR CONSULTA
			$sql=$this->db->executeTested($str);
		}
		// RETORNAR DATOS
		return $sql;
	}
	
	/*
	 * VALIDAR ACCIONES PARA PLANES DE AUTOPROTECCION
	 */
	private function validateSelfProtectionPlan($inspectionId,$comment){
	    
	    // DATOS DE INSPECCION
	    $inspection=$this->db->findById($inspectionId,$this->entity);
	    
	    // VALIDAR ESTADO DE COMENTARIO
	    if($comment['comentario_require']=='NA'){
	        
	        // VALIDAR ESTADOS DE PLANES
	        foreach ($this->post['plans'] as $localId=>$plan){
	            
	            // VAIDAR EXISTENCIA DE PLAN
	            if(isset($plan)){
	                
	                // DAR DE BAJA PLANES
	                if(in_array($plan['plan_estado'],['PENDIENTE','INGRESADO','SUSPENDIDO','ASIGNADO','NO APLICA'])){
	                    
	                    // DATOS PARA ACTUALIZAR REGISTRO DE PLAN
	                    $tmpPlan=array(
	                        'plan_id'=>$plan['plan_id'],
	                        'plan_estado'=>'NO APLICA',
	                        'plan_observacion'=>"BAJO CRITERIO TÉCNICO EN INSPECCION #{$inspection['inspeccion_codigo']} SE DETERMINA QUE NO APLICA UN PLAN DE AUTOPROTECCIÓN PARA EL PRESENTE ESTABLECIMIENTO"
	                    );
	                    // ACTUALIZAR REGISTRO DE PLAN
	                    $this->db->executeTested($this->db->getSQLUpdate($tmpPlan,'planesemergencia'));
	                    
	                    // ELIMINAR REGISTRO DE PLAN
	                    unset($this->post['model'][88]['locals'][$localId]);
	                    
	                }
	                
	            }
	            
	        }
	        
	    }
	    
	}
	
	
	
	
	/*
	 * 5.1.B REGISTRAR DATOS DE ENTREVISTADO
	 */
	private function setInterviewed($data){
		// MODELO DE PERSONA
		$model=array(
			'persona_apellidos'=>$data['persona_apellidos'],
			'persona_nombres'=>$data['persona_nombres'],
			'persona_tipo_doc'=>$data['persona_tipo_doc'],
			'persona_doc_identidad'=>$data['persona_doc_identidad']
		);
		// INGRESO DE RESPONSABLE
		return $this->requestPerson($model);
	}
	
	
	/*
	 * 5.1.A ESTABLECER REINSPECCIONES - CONTADOR DE INSPECCIONES REALES
	 * - SI LA FECHA DE REINSPECCIÓN ES LA MISMA QUE LA DE INSPECCIÓN NO SE REGISTRARÁ UNA NUEVA
	 */
	private function setReInspection($inspection){
		// MODELO DE REINSPECCIÓN
		$reinspection=array(
			'fk_inspeccion_id'=>$inspection['inspeccion_id'],
			'reinspeccion_tipo'=>'INSPECCION',
			'reinspeccion_informe_numero'=>$inspection['inspeccion_informe_numero'],
			'reinspeccion_estado'=>$inspection['inspeccion_estado']
		);
		// VALIDAR LA EXISTENCIA DE REINSPECCIONES
		$str=$this->db->selectFromView('reinspecciones',"WHERE fk_inspeccion_id={$inspection['inspeccion_id']} ORDER BY reinspeccion_fecha DESC LIMIT 1");
		if($this->db->numRows($str)>0){
			// DATOS DE ÚLTIMA REINSPECCION
			$row=$this->db->findOne($str);
			// VALIDAR SI LA FECHA DE REINSPECCION ES IGUAL A LA ANTERIOR INSPECCIÓN REGISTRADA
			if($this->setFormatDate($row['reinspeccion_fecha'],'Y-m-d')==$this->setFormatDate($inspection['inspeccion_fecha'],'Y-m-d')) return $this->setJSON("Ok",true);
			// CASO CONTRARIO PROCEDER A REGISTRAR REINSPECCION
			$reinspection['reinspeccion_tipo']='REINSPECCION';
			// OBTENER LA FECHA DE REINSPECCIÓN - ALT
			$date=$this->setFormatDate($inspection['inspeccion_fecha'],'Y-m-d');
		}else{
			// OBTENER LA FECHA DE INSPECCIÓN
			$date=$this->setFormatDate($inspection['inspeccion_fecha_inspeccion'],'Y-m-d');
		}
		// ESTABLECE LA HORA DE INSPECCIÓN
		$reinspection['reinspeccion_fecha']=$date;
		$reinspection['reinspeccion_hora_ingreso']=$this->setFormatDate("{$date} {$inspection['inspeccion_hora_ingreso_alt']}",'Y-m-d H:i');
		$reinspection['reinspeccion_hora_salida']=$this->setFormatDate("{$date} {$inspection['inspeccion_hora_salida_alt']}",'Y-m-d H:i');
		// REGISTRAR FECHAS DE INSPECCIÓN EN MODELO
		$this->model['inspeccion_hora_ingreso']=$reinspection['reinspeccion_hora_ingreso'];
		$this->model['inspeccion_hora_salida']=$reinspection['reinspeccion_hora_salida'];
		// REGISTRAR REINSPECCION
		return $this->db->executeTested($this->db->getSQLInsert($reinspection,'reinspecciones'));
	}
	
	/*
	 * 5.1.A CONSULTAR SI HA SIDO REGISTRADO EL FORMULARIO DE INSPECCION
	 */
	private function testRegistrationForms($i){
	    
	    // DATOS DE INSPECCION
	    $typeForm=$i['inspeccion_estado'];
	    $numberForm=$i['inspeccion_informe_numero'];
	    $numberCitation=$i['citacion_numero'];
	    
	    // VALIDAR TIPO DE INSPECCION
	    $list=array(
	        'NO CUMPLE'=>'INSPECCION',
	        'PRIMERA CITACION'=>'PRIMERA CITACION',
	        'SEGUNDA CITACION'=>'SEGUNDA CITACION',
	        'NOTIFICADO'=>'NOTIFICACION',
	        'CLAUSURADO'=>'INSPECCION'
	    );
	    
	    // REGISTRAR TIPO DE FORMULARIO
	    $typeForm=$list[$typeForm];
	    
	    // CONSULTAR ID DE SESION
	    $sessionId=$this->getSessionId();
	    
	    // OBTENER VALOR ENTERO DE FORMULARIO
	    $numberForm=intval($numberForm);
	    
	    // VALIDAR TIPO DE REGISTRO
	    if(in_array($typeForm, ['PRIMERA CITACION','SEGUNDA CITACION'])){
	        
	        // STRING PARA CONSULTAR REGISTRO
	        $str=$this->db->selectFromView('vw_formulariosinspeccion',"WHERE fk_inspector_id={$sessionId} AND formulario_tipo='{$typeForm}' AND ({$numberCitation} BETWEEN formulario_serie_inicio AND formulario_serie_fin)");
	        // VALIDAR EXISTENCIA DE FORMULARIO
	        if($this->db->numRows($str)<1){
	            $msg="Error en el número de formulario, para continuar esta operación primero debe registrar los formularios de inspección correspondiente a <b>{$typeForm}</b> donde se encuentre la serie <b>{$numberCitation}</b>.";
	            $link="<br>Puede registrarlo haciendo click aquí: <br><a href=/system/prevention/inspections/forms target=_blank>Registro de formularios de inspección.</a>";
	            $this->getJSON("{$msg}{$link}");
	        }
	        // VALIDAR NUEVAMENTE FORMULARIO DE INSPECCION
	        $typeForm='INSPECCION';
	    }
	        
        // STRING PARA CONSULTAR REGISTRO
        $str=$this->db->selectFromView('vw_formulariosinspeccion',"WHERE fk_inspector_id={$sessionId} AND formulario_tipo='{$typeForm}' AND ({$numberForm} BETWEEN formulario_serie_inicio AND formulario_serie_fin)");
        // VALIDAR EXISTENCIA DE FORMULARIO
        if($this->db->numRows($str)<1){
            $msg="Error en el número de formulario, para continuar esta operación primero debe registrar los formularios de inspección correspondiente a <b>{$typeForm}</b> donde se encuentre la serie <b>{$numberForm}</b>.";
            $link="<br>Puede registrarlo haciendo click aquí: <br><a href=/system/prevention/inspections/forms target=_blank>Registro de formularios de inspección.</a>";
            $this->getJSON("{$msg}{$link}");
        }
	    
	}
	
	/*
	 * 5.1 UPDATE PREINSPECTION
	 */
	private function setPreinspection($post){
	    
	    // VALIDAR SI EL REGISTRO HA SIDO APROBADO
	    if($this->post['i']['inspeccion_estado']!='APROBADO') $this->testRegistrationForms($this->post['i']);
	    
		// INSPECCION
		$this->model=$post['i'];
		// 5.1.A REGISTRAR NÚMERO DE INSPECCIONES
		$this->setReInspection($this->model);
		// 5.1.B REGISTRAR ENTREVISTADO
		// CONSULTAR DATOS DE RESPONSABLE
		$entreviewed=$this->setInterviewed($post['entrevistado']);
		// INSERTARA REFERENCIA DE RESPONSABLE EN REGISTRO DE INSPECCIÓN
		$this->model['fk_entrevistado_id']=$entreviewed['persona_id'];
		// 5.1.C ACTUALIZAR DATOS DE ACTIVIDAD COMERCIAL / LOCALES / ESTABLECIMIENTOS
		foreach($post['locals'] as $data){
			// RECORRER LISTADO DE LOCALES
			foreach($data['list'] as $localId=>$local){
				// CALCULAR DATOS FALTANTES
				$local['local_area_construccion']=$local['local_area_planta_baja']+$local['local_area_subsuelos'];
				// ACTUALIZAR DATOS DE LOCAL
				$this->db->executeTested($this->db->getSQLUpdate($local,'locales'));
				// ACTUALIZAR DATOS DE LOCAL RELACIONADO CON INSPECCIÓN - CONDICIÓN DE RELACIÓN
				$strWhr="WHERE fk_local_id={$localId} AND fk_inspeccion_id={$this->model['inspeccion_id']}";
				// MODELO DE RELACION
				$aux=array(
					'inspeccion_area'=>$local['local_area'],
					'inspeccion_area_construccion'=>$local['local_area_construccion'],
					'inspeccion_plantas'=>$local['local_plantas'],
					'inspeccion_aforo'=>$local['local_aforo']
				);
				// GENERAR SENTENCIA Y ACTUALIZAR RELACIÓN
				$this->db->executeTested($this->db->getSQLUpdate($aux,'inspeccion_local',$strWhr));
			}
		}
		// 5.1.D ACTUALIZAR DATOS DE INSPECCIÓN
		// SI SE HA APROBADO GENERAR EL NÚMERO DE SERIE CORRESPONDIENTE AL AÑO
		if(in_array($this->model['inspeccion_estado'],['APROBADO'])){
			// GENERAR NUMERO DE INSPECCION APROBADA
			$this->model['inspeccion_serie']=$this->db->getNextSerie($this->entity);
			$this->model['inspeccion_fecha_aprobado']=$this->model['inspeccion_hora_salida'];
		}else{
			// DIAS PARA REINSPECCIÓN
			$daysReinspections=$this->string2JSON($this->varGlobal['DAYS_FOR_REINSPECTION']);
			// ACTUALIZAR DIAS PARA REINSPECCION
			$this->model['inspeccion_fecha_reinspeccion']=$this->addWorkDays($this->model['inspeccion_fecha_inspeccion'],$daysReinspections[$this->model['inspeccion_estado']]);
			// VALIDAR ESTADOS PARA REGISTRO DE CITACIONES DE ESTABECIMIENTOS
			if(in_array($this->model['inspeccion_estado'],['PRIMERA CITACION','SEGUNDA CITACION','NOTIFICADO'])){
				// STRING PARA CONSULTAR SI EXISTE UN REGISTRO DE CITACION
				$str=$this->db->selectFromView("citaciones","WHERE fk_inspeccion_id={$this->model['inspeccion_id']} AND citacion_tipo='{$this->model['inspeccion_estado']}'");
				// CONSULTAR SI YA EXISTE UN REGISTRO DE CITACION
				if($this->db->numRows($str)<1){
					// GENERAR MODELO DE CITACION
					$citation=array(
						'citacion_serie'=>$this->db->getNextSerie('citaciones'),
						'citacion_numero'=>$this->model['citacion_numero'],
						'fk_inspeccion_id'=>$this->model['inspeccion_id'],
						'citacion_tipo'=>$this->model['inspeccion_estado'],
						'citacion_fecha'=>$this->setFormatDate($this->model['inspeccion_fecha_inspeccion'],'Y-m-d'),
						'citacion_reinspeccion'=>$this->setFormatDate($this->model['inspeccion_fecha_reinspeccion'],'Y-m-d')
					);
					// GENERAR STRING DE INGRESO DE CITACION - EJECUTAR SENTENCIA DE INGRESO / ACTUALIZACION DE CITACIONES
					$this->db->executeTested($this->db->getSQLInsert($citation,'citaciones'));
				}
			}
		}
		// ACTUALIZAR DATOS DE INSPECCION
		return $this->db->executeTested($this->db->getSQLUpdate($this->model,$this->entity));
	}
	
	
	/*
	 * 5. ACTUALIZACIÓN DE REGISTRO
	 */
	public function updateInspections(){
		// COMENZAR TRANSACCION
		$this->db->begin();
		// VALIDAR EL TIPO DE ACTULIZACION 
		if($this->post['type']=='PREINSPECCION'){
			// ACTUALIZAR DATOS DE PREINSPECCION
			$sql=$this->setPreinspection($this->post);
		}else{
			// MODELO DE INSPECCION
		    $inspection=$this->post['i'];
		    
		    // VALIDAR IMPLEMENTACION DE PLANES
		    $this->validateSelfProtectionPlan($inspection['inspeccion_id'],$this->post['comments'][9]);
		    
		    // 5.3 ACTUALIZAR COMENTARIOS
		    $sql=$this->setComments($inspection['inspeccion_id'],$this->post['comments']);
		    
			// 5.2 ACTUALIZAR RECURSOS
			$sql=$this->setResources($inspection['inspeccion_id'],$this->post['model']);
		}
		// CERRAR TRANSACCION Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * 6. ACTUALIZACIÓN DE REGISTRO
	 */
	public function insertInspections(){
		// VALIDAR LA EXISTENCIA DE UNA INSPECCIÓN EN EL PRESENTE AÑO
		$this->requestInspectionByLocal($this->post['local_id']);
		// DECLARAR MODELO DE BARRIDO - INSPECCION
		$this->model=array(
			'inspeccion_tipo'=>'INSPECCION',
			'inspeccion_fingreso'=>$this->getFecha(),
			'inspeccion_fecha_inspeccion'=>$this->post['inspeccion_fecha_inspeccion'],
			'fk_local_id'=>$this->post['local_id'],
			'inspeccion_area'=>$this->post['local_area'],
			'inspeccion_area_construccion'=>$this->post['local_area_construccion'],
			'inspeccion_aforo'=>$this->post['local_aforo'],
			'inspeccion_plantas'=>$this->post['local_plantas']
		);
		// COMENZAR TRANSACCION
		$this->db->begin();
		/// REGISTRAR INSPECCION
		$sql=$this->insertInspection($this->post['fk_inspector_id']);
		// CERRAR TRANSACCION Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	
	/*
	 * SET SELFINSPECTION
	 */
	public function insertSelfInspectionByLocal(){
	    
	    // BUSCAR DATOS DE LOCAL
	    
	    
	    // BUSCAR FORMULARIO DE INSPECCION
	    
	    
	    // IMPRIMIR FORMULARIO
	    
	    
	    //
	    $this->getJSON("sdsd");
	}
	
	
	
	/*
	 * IMPRIMIR DETALLE DE PREGUNTAS
	 */
	private function getReportInformation($data){
		// INDEX PARA OBTENER CELDAS DE PREGUNTAS
		$commentIndex=$this->string2JSON($this->varGlobal['ROWS_FOR_INSPECTION_COMMENTS']);
		$rowIndex=$this->string2JSON($this->varGlobal['ROWS_FOR_INSPECTION']);
		$answrIndex=$this->string2JSON($this->varGlobal['VALUES_FOR_SRC_INSPECTION']);
		// TEMPLATE
		$html="";
		// OBTENER INFORMACIÓN DE PREGUNTAS ALMACENADAS
		foreach($data['src']['list'] as $titleKey=>$srcList){
			// RECOLECTAR TABLE
			$aux=$this->varGlobal['inspectionTableItem.html'];
			// PLANTILLA SUBHEADER
			$subheader="";
			// RECORRER RECURSOS
			foreach($srcList as $subheaderKey=>$srcRows){
				// VALIDAR SI REQUIERE EL ITEM
				if($data['comments'][$data['src']['comments'][$subheaderKey]]['comentario_require']=='NO' && count($srcRows)>0){
					// PLANTILLA SUBHEADER
					$subheader.=$this->varGlobal['inspectionTableHeader.html'];
					// PLANTILLA DE RECURSOS
					$srcRowsHTML="";
					// RECORRER RECURSOS
					foreach($srcRows as $srcId){
						// PLANES DE EMERGENCIA DE LOCALES
						if($srcId==88){
							foreach($data['plans'] as $plan){
								// PLANTILLA DE RECURSOS
								$srcRowsHTML.=$this->varGlobal['inspectionTableRow.html'];
								// DATOS PARA IMPRESIÓN
								$srcName="{$data['src']['info'][$srcId]['src_name']} / {$plan['local_nombrecomercial']}";
								// VALIDAR INFORMACIÓN DE PLAN
								if(!isset($plan['plan_estado'])) $srcStatus='NA';
								elseif($plan['plan_estado']=='APROBADO') $srcStatus='VIGENTE';
								elseif($plan['plan_estado']=='VENCIDO') $srcStatus='ACTUALIZAR';
								elseif($plan['plan_estado']=='PENDIENTE') $srcStatus='IMPLEMENTAR';	
								// IMPRIMIR RECURSOS
								$srcRowsHTML=preg_replace('/{{srcId}}/',$srcId,$srcRowsHTML);
								$srcRowsHTML=preg_replace('/{{srcName}}/',$srcName,$srcRowsHTML);
								$srcRowsHTML=preg_replace('/{{srcStatus}}/',$srcStatus,$srcRowsHTML);
							}
						}else{
							// PLANTILLA DE RECURSOS
							$srcRowsHTML.=$this->varGlobal['inspectionTableRow.html'];
							// DATOS PARA IMPRESIÓN
							$srcName=$data['src']['info'][$srcId]['src_name'];
							$srcStatus=$answrIndex[$data['src']['info'][$srcId]['src_type']][$data['model'][$srcId][$rowIndex[$data['src']['info'][$srcId]['src_type']]]];
							// IMPRIMIR RECURSOS
							$srcRowsHTML=preg_replace('/{{srcId}}/',$srcId,$srcRowsHTML);
							$srcRowsHTML=preg_replace('/{{srcName}}/',$srcName,$srcRowsHTML);
							$srcRowsHTML=preg_replace('/{{srcStatus}}/',$srcStatus,$srcRowsHTML);
							if(in_array($data['src']['info'][$srcId]['src_type'],['CONTROL','AUMENTAR','IMPLEMENTAR'])){
								$commentIdx=$commentIndex[$data['src']['info'][$srcId]['src_type']];
								if(!in_array($data['model'][$srcId][$commentIndex[$data['src']['info'][$srcId]['src_type']]],['',null])){
									// ADJUNTAR COMENTARIOS
									$label=($data['src']['info'][$srcId]['src_type']=='IMPLEMENTAR')?"Observación":"Incrementar";
									$comments="<tr><td colspan=2><b>{$label}:</b> {$data['model'][$srcId][$commentIdx]}</td></tr>";
									$srcRowsHTML.=$comments;
								}
							}
						}
					}
					// IMPRIMIR SUBHEADER
					$subheader=preg_replace('/{{subheaderName}}/',$subheaderKey,$subheader);
					// IMPRIMIR ROWS EN TABLE
					$subheader=preg_replace('/{{resourcesRows}}/',$srcRowsHTML,$subheader);
				}
				
				// OBTENER COMENTARIO DE SECCION
				$commentSection=$data['comments'][$data['src']['comments'][$subheaderKey]];
				// VALIDAR SI HA SIDO INGRESADO EL COMENTARIO
				if(isset($commentSection['comentario_descripcion']) && !in_array($commentSection['comentario_descripcion'],['NA','NO',null,''])){
				    $subheader.="<tr><td colspan=2><b>Observación(es):</b> {$commentSection['comentario_descripcion']}</td></tr>";
				}
				
			}
			
			// IMPRIMIR NOMBRE DE CLASIFICACIÓN DE RECURSOS
			$aux=preg_replace('/{{titleKey}}/',$titleKey,$aux);
			// IMPRIMIR SUBHEADER EN TABLE
			$aux=preg_replace('/{{subheaderHTML}}/',$subheader,$aux);
			// ADJUNTAR TABLA DE CLASIFICACIÓN
			$html.=$aux;
		}
		// RETORNAR MODELO
		return $html;
	}
	
	/*
	 * 7. IMPRIMIR REGISTRO CON DETALLES
	 */
	public function printDetail($pdfCtrl,$config){
		// DATOS DE ENVÍO
		$get=$this->requestGet();
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE inspeccion_id={$get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER INFORMACIÓN DE REGISTRO
			$row=$this->db->findOne($str);
			$row['inspeccion_hora_ingreso']=$this->setFormatDate($row['inspeccion_hora_ingreso'],'H:i:s');
			$row['inspeccion_hora_salida']=$this->setFormatDate($row['inspeccion_hora_salida'],'H:i:s');
			$row['inspeccion_fecha_inspeccion']=$this->setFormatDate($row['inspeccion_fecha_inspeccion'],'complete');
			$row['reinspeccion']=$this->setFormatDate($row['reinspeccion'],'complete');
			// CARGAR DATOS
			$pdfCtrl->loadSetting($row);
			// OBTENER FORMULARIO DE INSPECCIÓN
			$data=array('i'=>$row);
			// INFORMACIÓN DE INSPECCIÓN
			$data=$this->getPreinspection($row,$data);
			$data=$this->getInspection($row,$data);
			$data=$this->getDetailInspection($row,$data);
			// DETALLE DE INSPECCIÓN
			$pdfCtrl->loadSetting(array('inspection'=>$this->getReportInformation($data)));
			
			// IMPRIMIR INSPECTORES
			$auxHTML="";
			foreach($data['inspectors'] as $insp){
				$auxHTML.=$this->varGlobal['inspectionInspectorRow.html'];
				foreach($insp as $k=>$v){$auxHTML=preg_replace('/{{'.$k.'}}/',$v,$auxHTML);}
			}	
			// CARGAR INSPECTORES
			$pdfCtrl->loadSetting(array('inspectorsList'=>$auxHTML));
			
			// IMPRIMIR LOCALES
			$auxHTML="";
			foreach($data['locals'] as $locals){
				// IMRPIMIR DATOS DE RUC
				$auxHTML.=$this->varGlobal['inspectionEntityRow.html'];
				foreach($locals['entity'] as $k=>$v){$auxHTML=preg_replace('/{{'.$k.'}}/',$v,$auxHTML);}
				// IMRPIMIR LOCALES
				$auxHTML2="";
				foreach($locals['list'] as $local){
					$auxHTML2.=$this->varGlobal['inspectionLocalRow.html'];
					foreach($local as $k=>$v){$auxHTML2=preg_replace('/{{'.$k.'}}/',$v,$auxHTML2);}
				}	
				$auxHTML=preg_replace('/{{localsList}}/',$auxHTML2,$auxHTML);
			}	
			// CARGAR LOCALES
			$pdfCtrl->loadSetting(array('entitiesList'=>$auxHTML));
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * 8. IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// VALIDAR EL TIPO DE INSPECCIÓN SI ESTÁ APROBADO O EN CITACIÓN Y ELEGIR EL HTML EL PAPEL QUE SE VA A IMPRIMIR
		if(!isset($id))$this->getJSON('no reporte');
		// LOAD DATA LOCAL
		$row=$this->db->findOne($this->db->selectFromView('vw_inspecciones',"WHERE inspeccion_id=$id"));
		$pdfCtrl->loadSetting($row);
		// LOAD DATA ENTITY
		$row=$this->db->findOne($this->db->selectFromView('vw_locales',"WHERE local_id={$row['local_id']}"));
		$pdfCtrl->loadSetting($row);
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		// FECHA
		$pdfCtrl->loadSetting(array('today'=>$this->getFecha()));
		// RETORNAR
		return '';
	}
	
	
}