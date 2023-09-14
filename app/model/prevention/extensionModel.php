<?php namespace model\prevention;

class extensionModel extends inspectionModel {

	/*
	 * VARIABLES LOCALES
	 */
	private $entity='prorrogas';
	private $config;
	private $model;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * RECURSOS DE PRORROGA
	 */
	private function getResourcesByExtension($model,$extensionId){
		// STRING PARA CONSULTAR SI EXISTEN REGISTROS ANTERIORES: model
		foreach($this->db->findAll($this->db->selectFromView('prorroga_recursos',"WHERE fk_prorroga_id={$extensionId}")) as $v){
			// INGRESAR DATOS DE RECURSO
			$model[$v['fk_recurso_id']]=$v;
		}
		// RETORNAR MODELO DE CONSULTA
		return $model;
	}
	
	/*
	 * CONSULTAR INSPECCION POR CODIGO
	 */
	private function requestByInspection($inspectionId){
		// MODELO DE CONSULTA
		$data['i']=$this->db->viewById($inspectionId,'inspecciones');
		// LISTA DE RECURSOS Y COMENTARIO QUE NO CUMPLEN
		$src=$this->getResourcesByInspection($inspectionId);
		
		// PERSONA QUE SOLICITA LA PRORROGA
		$solicitante=$this->db->findById($data['i']['fk_representante_id'],'personas');
		// DATOS DE REPRESENTANTE LEGAL
		$src['frmParent']=array(
			'persona_tipo_doc'=>$solicitante['persona_tipo_doc'],
			'persona_doc_identidad'=>$solicitante['persona_doc_identidad'],
			'persona_apellidos'=>$solicitante['persona_apellidos'],
			'persona_nombres'=>$solicitante['persona_nombres'],
			'persona_celular'=>$solicitante['persona_celular'],
			'persona_correo'=>$solicitante['persona_correo']
		);
		// REGISTRAR ID DE INSPECCION
		$src['frmParent']['fk_inspeccion_id']=$inspectionId;
		$src['frmParent']['prorroga_desde']=$this->getFecha();
		
		// DATOS DE PEROSNAL
		$staffId=$this->getStaffIdBySession();
		// OBTENER DATOS DE PUESTO
		$staff=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE personal_id={$staffId} AND ppersonal_estado='EN FUNCIONES'"));
		// INGRESAR PERSONAL QUE ESTA APROBANDO
		$src['frmParent']['fk_autoriza_id']=$staff['ppersonal_id'];
		
		// BUSCAR REGISTRO DE PRORROGA
		$str=$this->db->selectFromView($this->entity,"WHERE fk_inspeccion_id={$inspectionId}");
		// VALIDAR EXISTENCIA DE REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DE PRORROGA
			$src['frmParent']=array_merge($src['frmParent'],$this->db->findOne($str));
			// INSERTAR RECURSOS REGISTRADOS
			$src['model']=$this->getResourcesByExtension($src['model'],$src['frmParent']['prorroga_id']);
		}
		
		// BUSCAR UNA INSPECCIÓN POR CÓDIGO
		return array_merge($src,$data);
	}
	
	/*
	 * CONSULTAR POR ID DE PRORROGA
	 */
	private function requestByExtension($extensionId){
		// CONSULTAR REGISTRO DE PRORROGA
		$frmParent=$this->db->viewById($extensionId,$this->entity);
		
		// ID DE INSPECCION
		$inspectionId=$frmParent['fk_inspeccion_id'];
		// MODELO DE CONSULTA
		$data['i']=$this->db->viewById($inspectionId,'inspecciones');
		// LISTA DE RECURSOS Y COMENTARIO QUE NO CUMPLEN
		$src=$this->getResourcesByInspection($inspectionId);
		
		// DATOS DE FORMULARIO
		$src['frmParent']=array_merge($src['frmParent'],$frmParent);
		// PERSONA QUE SOLICITA LA PRORROGA
		$solicitante=$this->db->findById($frmParent['prorroga_solicitante'],'personas');
		
		// DATOS DE REPRESENTANTE LEGAL
		$src['frmParent']['persona_tipo_doc']=$solicitante['persona_tipo_doc'];
		$src['frmParent']['persona_doc_identidad']=$solicitante['persona_doc_identidad'];
		$src['frmParent']['persona_apellidos']=$solicitante['persona_apellidos'];
		$src['frmParent']['persona_nombres']=$solicitante['persona_nombres'];
		$src['frmParent']['persona_celular']=$solicitante['persona_celular'];
		$src['frmParent']['persona_correo']=$solicitante['persona_correo'];
		
		// BUSCAR REGISTRO DE PRORROGA
		$str=$this->db->selectFromView($this->entity,"WHERE fk_inspeccion_id={$inspectionId}");
		// VALIDAR EXISTENCIA DE REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DE PRORROGA
			$src['frmParent']=array_merge($src['frmParent'],$this->db->findOne($str));
			// INSERTAR RECURSOS REGISTRADOS
			$src['model']=$this->getResourcesByExtension($src['model'],$src['frmParent']['prorroga_id']);
		}
		
		// BUSCAR UNA INSPECCIÓN POR CÓDIGO
		return array_merge($src,$data);
	}
	
	/*
	 * CONSULTAR REGISTRO POR CÓDIGO
	 */
	private function requestByCode($code){
		// BUSCAR UNA INSPECCIÓN POR CÓDIGO
		$str=$this->db->selectFromView('inspecciones',"WHERE UPPER(inspeccion_codigo)=UPPER('{$code}')");
		// CONSULTAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0) return array_merge(array('entity'=>'inspeccion'),$this->db->findOne($str));
		// GENERAR STRING DE CONSULTA DE SOLICITUD POR CODIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(prorroga_codigo)=UPPER('{$code}')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DE SOLICITUD
			if(in_array($row['prorroga_estado'],['SOLICITUD GENERADA'])){
				// VALIDAR ROL PARA INGRESAR SOLICITUD
				if(!in_array(62011,$this->getAccesRol())) $this->getJSON("La solicitud <b>#{$code}</b> ya ha sido ingresada, por favor direccione el trámite con quien corresponda!");
				
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['prorroga_estado']='INGRESADO';
				// MODAL A DESPLEGAR
				$row['modal']='Input';
				$row['tb']=$this->entity;
			}elseif(in_array($row['prorroga_estado'],['INGRESADO'])){
				// VALIDAR ROL PARA INGRESAR SOLICITUD
				if(!in_array(62012,$this->getAccesRol())) $this->getJSON("La solicitud <b>#{$code}</b> ya ha sido ingresada, por favor direccione el trámite con quien corresponda!");
				
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['prorroga_estado']='PRORROGA APROBADA';
				// MODAL A DESPLEGAR
				$row['modal']='Prorrogas';
				$row['tb']=$this->entity;
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
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
		// CONSULTAR PRORROGA POR CODIGO
		if(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTAR INSPECCION POR CODIGO
		elseif(isset($this->post['inspectionId'])) $json=$this->requestByInspection($this->post['inspectionId']);
		// CONSULTAR INSPECCION POR CODIGO
		elseif(isset($this->post['extensionId'])) $json=$this->requestByExtension($this->post['extensionId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * REGISTRAR RECURSOS CON PRORROGAS
	 */
	private function setResources($extensionId,$resourcesList){
		// ELIMINAR REGISTRO EXISTENTE DE RECURSOS
		$sql=$this->db->executeTested($this->db->getSQLDelete('prorroga_recursos',"WHERE fk_prorroga_id={$extensionId}"));
		// RECORRER EL LISTADO DE RECURSOS
		foreach($resourcesList as $v){
			// INSERTAR ID DE PRORROGA
			$v['fk_prorroga_id']=$extensionId;
			// REGISTRAR RECURSO
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'prorroga_recursos'));
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO
	 */
	public function updateEntity(){
		// COMENZAR TRANSACCION
		$this->db->begin();
		
		// VALIDAR SI EXISTE REGISTRO DE INGRESO
		if(isset($this->post['prorroga_id']) && isset($this->post['prorroga_estado'])){
			// VALIDAR SI HA SIDO APROBADO
		    if(in_array($this->post['prorroga_estado'],['APROBADO','PRORROGA APROBADA'])){
				// ACTUALIZAR FECHA DE APROBACIÓN
				$this->post['prorroga_aprobado']=$this->getFecha();
				
				// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
				$this->post['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
				
				// GENERAR NÚMERO DE SERIE DE DESPACHADOS
				if($this->post['prorroga_serie']==0) $this->post['prorroga_serie']=$this->db->getNextSerie($this->entity);
			}
			// MODIFICAR DATOS DE PRORROGA
			$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		}else{
			// MODELO DE FORMULARIO
			$frmParent=$this->post['frmParent'];
			// REGISTRAR DATOS DE SOLICITANTE
			$person=$this->requestPerson($frmParent);
			// RELACIONAR REGISTRO CON COORDINADOR
			$frmParent['prorroga_solicitante']=$person['persona_id'];
			// STRING PARA CONSULTAR REGISTRO
			$str=$this->db->selectFromView($this->entity,"WHERE fk_inspeccion_id={$frmParent['fk_inspeccion_id']}");
			// CONSULTAR SI EXISTE REGISTRO
			if($this->db->numRows($str)>0){
				// MODIFICAR DATOS DE PRORROGA
				$sql=$this->db->executeTested($this->db->getSQLUpdate($frmParent,$this->entity));
				// OBTENER ID DE PRORROGA
				$extensionId=$frmParent['prorroga_id'];
			}else{
				
				// INSERTAR RESPONSABLE DE PREVENCION - SOLICITA
				$frmParent['jtp_solicitud']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
				
				// REGISTRAR PRORROGA
				$sql=$this->db->executeTested($this->db->getSQLInsert($frmParent,$this->entity));
				// OBTENER ID DE PRORROGA
				$extensionId=$this->db->getLastID($this->entity);
			}
			// REGISTRAR RECURSOS
			$sql=$this->setResources($extensionId,$this->post['model']);
			// INSERTAR ID DE INSPECCION
			$sql['data']['extensionId']=$extensionId;
		}
		
		// CERRAR TRANSACCION Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * LISTA DE RECURSOS A IMPLEMENTAR
	 */
	private function getResourcesApplication($extensionId){
		// TEMPATE 
		$template="";
		// RECORRER LISTA DE RECURSOS
		foreach($this->db->findAll($this->db->selectFromView('prorroga_recursos',"WHERE fk_prorroga_id={$extensionId}")) as $val){
			// OBTENER NOMRBRE DE RECURSO
			$info=$this->db->findOne($this->db->getSQLSelect('reglasinspecciones',['recursosinspecciones'],"WHERE regla_id={$val['fk_recurso_id']}"));
			$val['regla_nombre']="{$info['recurso_nombre']} :: {$info['regla_nombre']}";
			// VALIDAR SI EL RECURSO APLICA PARA PRORROGA
			$aux=($val['implementacion_aplica']=='SI APLICA')?$this->varGlobal['PREVENTION_INSPECTION_RESOURCES_APLICA']:$this->varGlobal['PREVENTION_INSPECTION_RESOURCES_NO_APLICA'];
			// REEMPLAZAR DATOS
			foreach($val as $k=>$v){ $aux=preg_replace('/{{'.$k.'}}/',$v,$aux); }
			// INSERTAR PLANTILLA
			$template.=$aux;
		}
		// RETORNAR PLANTILLA
		return $template;
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLES
	 */
	public function printDetail($pdfCtrl,$config){
		// DATOS DE ENVÍO
		$get=$this->requestGet();
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE prorroga_id={$get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER INFORMACIÓN DE REGISTRO
			$row=$this->db->findOne($str);
			
			// PARSE FECHAS
			$row['prorroga_desde']=$this->setFormatDate($row['prorroga_desde'],'complete');
			$row['prorroga_hasta']=$this->setFormatDate($row['prorroga_hasta'],'complete');
			$row['inspeccion_fecha_inspeccion']=$this->setFormatDate($row['inspeccion_fecha_inspeccion'],'complete');
			
			// LISTADO DE RECURSOS PARA IMPLANTACION
			$row['resourcesList']=$this->getResourcesApplication($row['prorroga_id']);
			
			// CARGAR DATOS DE REPORTE
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['prorroga_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	
}