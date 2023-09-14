<?php namespace model\prevention;
use model as mdl;

class selfProtectionModel extends mdl\personModel {
	
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
	 * RECURSOS :: FACTORES DE PLAN DE AUTOPROTECCION
	 * 5. PREVENCIÓN Y CONTROL DE RIESGOS
	 * 6. MANTENIMIENTO
	 */
	public function insertResources(){
		// NOMBRE DE LA ENTIDAD
		$params=$this->getURI();
		
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		
		// ELIMINAR RECURSOS ANTERIORES
		$sql=$this->db->executeTested($this->db->getSQLDelete($params['tb'],"WHERE fk_plan_id={$this->post['plan_id']}"));
		// RECORRER LISTADO DE RECURSOS
		foreach($this->post['model'] as $resourceId=>$resource){
			// GENERAR MODELO TEMPORAL
			$resource['fk_plan_id']=$this->post['plan_id'];
			$resource['fk_recurso_id']=$resourceId;
			// GENERAR STRING DE CONSULTAR EJECUTARLA
			$sql=$this->db->executeTested($this->db->getSQLInsert($resource,$params['tb']));
		}
		
		// ACTUALIZAR OBSERVACIONES DE PLAN - PASO 5
		if($params['tb']=='autoproteccion_prevencion'){
			// GENERAR MODELO DE ACTUALIZACION
			$tempModel=array(
				'plan_id'=>$this->post['plan_id'],
				'prevencion_proximo_mantenimiento'=>$this->post['prevencion_proximo_mantenimiento']
			);
			// ACTUALIZAR MODELO DE PLAN
			$sql=$this->db->executeTested($this->db->getSQLUpdate($tempModel,$this->entity));
		}
		
		// DATOS DE RETORNO
		$sql['data']=$this->post;
		// CERRAR TRANSACCIÓN y RETORNAR DATOS A UI
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * SUBIR ARCHIVOS ADJUNTOS DE MANTENIIENTO
	 */
	public function uploadResources(){
		
		// MODELO DE RETORNO
		$sql=$this->setJSON("¡Cambios realizados exitosamente!");
		
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// OBTENER INFORMACION DE PLAN
		$plan=$this->db->findById($this->post['planId'],'planesemergencia');
		
		// PARAMETROS DE CARGA
		$uploadVars=$this->uploaderMng->setParamsToUploadToFolder("{$plan['plan_codigo']}/anexos","mantenimiento-{$this->post['fk_recurso_id']}",'media_file');
		// CARGAR ANEXOS DE MANTENIMIENTO
		$this->post['mantenimiento_adjunto']=$this->uploaderMng->uploadFileToFolder($this->post,'planesemergencia',$uploadVars);
		
		// ACTUALIZAR REGISTRO DE ANEXOS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,'autoproteccion_mantenimiento'));
		
		// RETORNAR MENSAJE
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * RECURSOS :: REGISTRO DE MESERI DE PLANES
	 */
	public function insertMeseri(){
		// NOMBRE DE LA ENTIDAD
		$params=$this->getURI();
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		// ELIMINAR RECURSOS ANTERIORES
		$sql=$this->db->executeTested($this->db->getSQLDelete($params['tb'],"WHERE fk_plan_id={$this->post['plan_id']}"));
		// RECORRER LISTADO DE RECURSOS
		foreach($this->post['temp'] as $resourceList){
			// LISTADO SECUNDARIO
			foreach($resourceList as $resourceId=>$resource){
				// GENERAR MODELO TEMPORAL
				$resource['fk_plan_id']=$this->post['plan_id'];
				$resource['fk_recurso_id']=$resourceId;
				$resource['meseri_coeficiente']=json_encode($resource['meseri_coeficiente']);
				// GENERAR STRING DE CONSULTAR EJECUTARLA
				$sql=$this->db->executeTested($this->db->getSQLInsert($resource,$params['tb']));
			}
		}
		// ACTUALIZAR MESERI
		if($params['tb']=='autoproteccion_meseri'){
			// ACTUALIZAR PLAN DE AUTOPROTECCIÓN
			$model=array(
				'plan_id'=>$this->post['plan_id'],
				'meseri_observaciones'=>$this->post['meseri_observaciones'],
				'meseri_valor_p'=>$this->post['meseri_valor_p'],
				'meseri_valor_x'=>$this->post['meseri_valor_x'],
				'meseri_valor_y'=>$this->post['meseri_valor_y']
			);
			// REGISTRAR CAMBIOS
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,$this->entity));
		}
		// DATOS DE RETORNO
		$sql['data']=$this->post;
		// CERRAR TRANSACCIÓN y RETORNAR DATOS A UI
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	
	
	/*
	 * ACTUALIZAR RECURSOS DEL PLAN
	 */
	public function uploadAnnexes(){
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("¡No se ha recibido ningun archivo adjunto!",true);
		$model=array();
		
		// DATOS DE REGISTRO
		$local=$this->db->viewById($this->post['fk_local_id'],'locales');
		
		// CARGAR REGISTRO SENESCYT
		if($this->post['annexeEntity']=='formacionacademica'){
			
			// CARGAR ARCHIVO 
			$this->post=$this->uploaderMng->uploadFileService($this->post,$this->post['annexeEntity']);
			// GENERAR MODELO TEMPORAL
			$model=array(
				'formacion_id'=>$this->post['profesional_sos_id'],
				'formacion_pdf'=>$this->post['formacion_pdf']
			);
			// ACTUALIZAR DATOS DE ENTIDAD
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,$this->post['annexeEntity']));
			
		}elseif($this->post['annexeEntity']=='profesionaldeseguridad'){
		    
		    // PARAMETROS DE CARGA
		    $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","formacion-{$local['fk_entidad_id']}",'profesionaldeseguridad_file');
			// CARGAR ARCHIVO
			$file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
			// GENERAR MODELO TEMPORAL
			$model=array(
				'formacion_id'=>$this->post['profesional_sos_id'],
				'formacion_pdf'=>$file
			);
			// ACTUALIZAR DATOS DE ENTIDAD
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,'formacionacademica'));
			
		}elseif($this->post['annexeEntity']=='ruc'){
		    
		    // PARAMETROS DE CARGA
		    $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","ruc-{$local['fk_entidad_id']}",'ruc_file');
			// CARGAR ARCHIVO
			$file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
			// GENERAR MODELO TEMPORAL
			$model=array(
				'entidad_id'=>$local['fk_entidad_id'],
				'entidad_sitioweb'=>$file
			);
			// ACTUALIZAR DATOS DE ENTIDAD
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,'entidades'));
			
		}elseif($this->post['annexeEntity']=='representantelegal'){
		    
		    // PARAMETROS DE CARGA
		    $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","cedula-{$local['local_id']}",'representantelegal_file');
			// CARGAR ARCHIVO
			$file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
			// GENERAR MODELO TEMPORAL
			$model=array(
				'persona_id'=>$local['fk_representante_id'],
				'persona_anexo_cedula'=>$file
			);
			// ACTUALIZAR DATOS DE ENTIDAD
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,'personas'));
			
		}elseif($this->post['annexeEntity']=='responsabledeltramite'){
		    
		    // PARAMETROS DE CARGA
		    $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","cedula-{$local['local_id']}",'responsabledeltramite_file');
			// CARGAR ARCHIVO
			$file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
			// GENERAR MODELO TEMPORAL
			$model=array(
				'persona_id'=>$this->post['fk_responsable_tramite'],
				'persona_anexo_cedula'=>$file
			);
			// ACTUALIZAR DATOS DE ENTIDAD
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,'personas'));
			
		}elseif($this->post['annexeEntity']=='impuestopredial'){
		    
		    // PARAMETROS DE CARGA
		    $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","impuestopredial-{$local['local_id']}",'impuestopredial_file');
			// CARGAR ARCHIVO
			$file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
			// GENERAR MODELO TEMPORAL
			$model=array(
				'local_id'=>$local['local_id'],
				'local_impuestopredial'=>$file
			);
			// ACTUALIZAR DATOS DE ENTIDAD
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,'locales'));
			
		}elseif($this->post['annexeEntity']=='usodesuelo'){
		    
		    // PARAMETROS DE CARGA
		    $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","usodesuelo-{$local['local_id']}",'usodesuelo_file');
			// CARGAR ARCHIVO
			$file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
			// GENERAR MODELO TEMPORAL
			$model=array(
					'local_id'=>$local['local_id'],
					'local_usodesuelo'=>$file
			);
			// ACTUALIZAR DATOS DE ENTIDAD
			$sql=$this->db->executeTested($this->db->getSQLUpdate($model,'locales'));
			
		}
		
		// ADJUNTAR DATOS DE ARCHIVO ADJUNTO
		if($sql['estado']) $sql['data']=$model;
		
		// RETORNAR CONSULTA
		$this->getJSON($sql);
		
	}
	
	/*
	 * INGRESAR REGISTROS
	 */
	public function insertAnnexesList(){
		// DATOS DE PLAN
		$plan=$this->db->findById($this->post['fk_plan_id'],'planesemergencia');
		
		// PARAMETROS DE CARGA
		$uploadVars=$this->uploaderMng->setParamsToUploadToFolder("{$plan['plan_codigo']}/anexos","anexo-",'anexo_file');
		// VALIDAR Y CARGAR ARCHIVO
		$this->post['anexo_adjunto']=$this->uploaderMng->uploadFileToFolder($this->post,'autoproteccion_anexos',$uploadVars);
		
		// INSERTAR EN BASE DE DATOS
		$sql=$this->db->executeSingle($this->db->getSQLInsert($this->post,'autoproteccion_anexos'));
		// DATOS PARA ACTUALIZAR
		$sql['data']=$this->post;
		// RETORNAR DATOS
		$this->getJSON($sql);
		
	}
	
	/*
	 * EDITAR REGISTROS
	 */
	public function updateAnnexesList(){
		// DATOS DE PLAN
	    $plan=$this->db->findById($this->post['fk_plan_id'],'planesemergencia');
	    
	    // PARAMETROS DE CARGA
	    $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("{$plan['plan_codigo']}/anexos","anexo",'anexo_file');
		// VALIDAR Y CARGAR ARCHIVO
	    $this->post['anexo_adjunto']=$this->uploaderMng->uploadFileToFolder($this->post,'autoproteccion_anexos',$uploadVars,false);
		
		// INSERTAR EN BASE DE DATOS
		$sql=$this->db->executeSingle($this->db->getSQLUpdate($this->post,'autoproteccion_anexos'));
		// DATOS PARA ACTUALIZAR
		$sql['data']=$this->post;
		// RETORNAR DATOS
		$this->getJSON($sql);
		
	}
	
	/*
	 * ELIMINAR REGISTROS
	 */
	public function deleteAnnexesList(){
		
		// OBTENER DATOS DE ANEXO
		$annexe=$this->db->findById($this->post['annexeId'],'autoproteccion_anexos');
		
		// OBTENER DATOS DE PLAN
		$plan=$this->db->findById($annexe['fk_plan_id'],$this->entity);
		
		// PATH DE ANEXOS
		$config=$this->uploaderMng->config['entityConfig']['autoproteccion_anexos'];
		
		// VALIDAR Y CARGAR ARCHIVO
		$path="{$config['folder']}/{$plan['plan_codigo']}/anexos/{$annexe['anexo_adjunto']}";
		
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// ELIMINAR ANEXO
		$sql=$this->db->executeTested($this->db->getSQLDelete('autoproteccion_anexos',"WHERE anexo_id={$this->post['annexeId']}"));
		
		// ELIMINAR REGISTRO
		$statusUnlink=$this->uploaderMng->deleteFileFormPath($path);
		// CONSULTAR LA ELIMINACION DEL REGISTRO
		if($statusUnlink['estado']) $sql['mensaje'].="<br>{$statusUnlink['mensaje']}";
		
		// CERRA TRANSACCION Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
		
	}
	
}