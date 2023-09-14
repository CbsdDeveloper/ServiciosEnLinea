<?php namespace model\permits;
use api as app;
use model as mdl;
use controller as ctrl;

class entityModel extends mdl\personModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='entidades';
	private $tempData;
	
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
	 * VALIDACIÓN - DOCUMENTOS IDENTIDAD
	 */
	private function validateEntity($post){
		return $this->validateDocIdentity($post['entidad_ruc'],$post['entidad_contribuyente']);
	}
	
	/*
	 * GUARDAR REGISTRO
	 */
	public function insertEntity(){
		
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		
		// VERIFICAR IDENTIFICACIÓN DE ENTIDAD
		if($this->post['entidad_contribuyente']=='CEDULA'){
			// DATOS DE ENTIDAD
			$this->post['person']['persona_telefono']=$this->post['entidad_telefono'];
			$this->post['person']['persona_celular']=$this->post['entidad_celular'];
			$this->post['person']['persona_correo']=$this->post['entidad_correo'];
		}
		
		// VALIDAR SI EL REGISTRO INGRESA CON OBJETO - PERSON
		if(isset($this->post['person']) && !empty($this->post['person'])){
			// SI NO SE HA SELECCIONADO EL TIPO DE IDENTIFICACIÓN
			if(!isset($this->post['persona_tipo_doc'])) $this->post['persona_tipo_doc']='CEDULA';
			// INGRESAR PERSONA
			$person=$this->requestPerson($this->post['person']);
		}else{
			// INGRESAR PERSONA
			$person=$this->requestPerson($this->post);
		}
		
		// DATOS DE REPRESENTANTE LEGAL
		$this->post['fk_representante_id']=$person['persona_id'];
		
		// VERIFICAR IDENTIFICACIÓN DE ENTIDAD
		if($this->post['entidad_contribuyente']=='CEDULA'){
			// DATOS DE ENTIDAD
			$this->post['entidad_ruc']=$person['persona_doc_identidad'];
		}
		
		// INGRESAR DATOS DE ENTIDAD DESDE REPRESENTANTE LEGAL
		if(in_array($this->post['entidad_contribuyente'],['CEDULA','natural','extranjero'])){
			// DATOS DE REPRESENTANTE LEGAL COMO ENTIDAD
			$this->post['entidad_razonsocial']=strtoupper("{$person['persona_apellidos']} {$person['persona_nombres']}");
		}
		
		// VALIDAR SI YA SE HA INGRESADO COMO RUC
		if($this->db->numRows($this->db->selectFromView($this->entity,"WHERE UPPER(entidad_ruc) LIKE UPPER('{$this->post['entidad_ruc']}%')"))>0){
			// MENSAJE QUE YA HA SIDO REGISTRADO EL RUC
			$this->getJSON($this->db->closeTransaction($this->setJSON("(<b>{$this->post['entidad_ruc']}</b>) Este registro ya ha sido ingresado anteriormente.")));
		}
		
		// VALIDAR TIPO DE CONTRIBUYENTE
		$status=$this->validateEntity($this->post);
		
		// MENSAJE DE VALIDACION ERRONEA
		if(!$status['estado']) $this->getJSON($this->db->closeTransaction($status));
		
		// VALIDAR MODULO DE ACCESO
		if(app\session::getKey()=='session.prevencion'){
			// ACTUALIZAR DATOS DE ENTIDAD PARA CREAR USUARIO
			$this->post['fk_responsableregistro_id']=$person['persona_id'];
			$this->post['entidad_usuario']='SI';
			$this->post['entidad_usuario_creacion']=$this->getFecha('dateTime');
			$this->post['entidad_contrasenia_cambio']='NO';
			$this->post['entidad_terminos']=($this->post['entidad_terminos']===true)?'SI':'NO';
			$this->post['entidad_contrasenia']=$this->post['entidad_contrasenia'];
			// GENERAR CONTRASEÑA
			$this->post['entidad_contrasenia']=$this->validatePairPassword($this->post['entidad_contrasenia'],$this->post['entidad_contrasenia_confirmacion'],'sha256');
		}
		
		// EJECUTAR CONSULTA SQL
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		
		// VALIDAR MODULO DE ACCESO
		if(app\session::getKey()=='session.prevencion'){
			// ID DE ENTIDAD
			$entityId=$this->db->getLastID($this->entity);
			// ENVIAR CORREO ELECTRÓNICO DE CREACION DE USUARIO
			$this->sendMail($entityId,'signup');
			// MENSAJE DE RETORNO
			$sql['mensaje']="Usuario creado existosamente, ahora puede ingresar en la plataforma de Servicios en Línea y gozar de todos los servicios.";
		}
		
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * RESTAURAR CREDENCIALES
	 */
	private function restoreAccount($data){
		// VALIDAR SI SE REQUIERE CAMBIAR LA CONTRASEÑA
		if($this->post['cambiar_pass']=='SI'){
			// VALIDAR INICIO DE SESION
			$data['entidad_contrasenia']=$this->encryptText($data['entidad_ruc'],'sha256');
			// ACTUALIZAR CAMBIOS
			$sql=$this->db->executeTested($this->db->getSQLUpdate($data,$this->entity));
		}else{
			$sql=$this->setJSON("No se han efectuado cambios en la cuenta!",true);
		}
		// RETORNAR CONSULTA
		$this->getJSON($sql);
	}
	
	/*
	 * VALIDAR LOS CAMBIOS DE PERSONAL REALIZADOS DESDE TTHH
	 */
	private function validateUpdate($post,$entityId){
		
		// OBTENER DATOS DE ENTIDAD
		$old=$this->db->viewById($entityId,$this->entity);
		
		// VALIDAR CONTRASEÑA DE PERSONAL
		if($this->encryptText($post['entity_password'],'sha256')!=$old['entidad_contrasenia']) $this->getJSON($this->varGlobal['MSG_PASS_ERROR']);
		
		// VALIDAR INGRESO DE NUEVA CONTRASEÑA
		if(isset($post['entity_new_password']) && !empty($post['entity_new_password'])){
			// VALIDAR NIVEL DE SEGURIDAD DE CONTRASEÑA
			$validPass=$this->testLevelPwd($post['entity_new_password']);
			// CONTRASEÑA DIFERENTE A LA ANTERIOR
			if($old['entidad_contrasenia']==$this->encryptText($post['entity_new_password'],'sha256')) $this->getJSON($this->varGlobal['MSG_PASS_DEPRECATED']);
			// NO PUEDE SER EL NUMERO DE CEDULA
			elseif($old['representantelegal_ruc']==$post['entity_new_password'] || $post['person']['persona_doc_identidad']==$post['entity_new_password']) $this->getJSON($this->varGlobal['MSG_PASS_EASY']);
			// NO PUEDE SER TAN DEBIL
			elseif(!$validPass['estado']) $this->getJSON($validPass);
			else {
				$post['entidad_contrasenia']=$this->encryptText($post['entity_new_password'],'sha256');
				$post['new_pass']=$post['entity_new_password'];
			}
		}
		
		// RETORNAR VARIABLE PARA ACTUALIZACION DE INFORMACION
		$post['entidad_actualizacion']=$this->getFecha('dateTime');
		
		// RETORNAR DATOS
		return $post;
	}
	
	/*
	 * ACTUALIZAR REGISTRO
	 */
	public function updateEntity(){
		
		// RESTAURAR CREDENCIALES
		if(isset($this->post['type']) && $this->post['type']=='restoreAccount') $this->restoreAccount($this->post);
		
		// VALIDAR DE QUÉ MÓDULO VIENE LA ACTUALIZACIÓN
		if(app\session::getKey()=='session.prevencion') $this->post=$this->validateUpdate($this->post,$this->post['entidad_id']);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDAR LA ACTUALIZACIÓN DE DATOS
		if(isset($this->post['person']) && !empty($this->post['person'])){
			// SERVICIO PARA REGISTRO DE REPRESENTANTE LEGAL
			$person=$this->requestPerson($this->post['person']);
		}else{
			// SERVICIO PARA REGISTRO DE REPRESENTANTE LEGAL
			$person=$this->requestPerson($this->post);
		}
		
		// VALIDAR ENVIO DE APODERADO LOCAL
		if($this->post['entidad_apoderado']=='SI' && isset($this->post['adopted']) && !empty($this->post['adopted'])){
			// SERVICIO PARA REGISTRO DE REPRESENTANTE LEGAL
			$adopted=$this->requestPerson($this->post['adopted']);
			// INGRESO DE REPRESENTANTE LEGAL
			$this->post['fk_apoderado_id']=$adopted['persona_id'];
		}
		
		// INGRESO DE REPRESENTANTE LEGAL
		$this->post['fk_representante_id']=$person['persona_id'];
		
		// VALIDAR TIPO DE ENTIDAD
		$status=$this->validateEntity($this->post);
		
		// PRESENTAR MENSAJE DE VALIDACION DE RUC
		if(!$status['estado']) $this->getJSON($this->db->closeTransaction($status));
		
		// INGRESAR DATOS DE ENTIDAD DESDE REPRESENTANTE LEGAL
		if(in_array($this->post['entidad_contribuyente'],['CEDULA','natural','extranjero'])){
			$this->post['entidad_razonsocial']=strtoupper("{$person['persona_apellidos']} {$person['persona_nombres']}");
		}
		
		// GENERAR CONSULTA SQL
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * ACTUALIZAR RECURSOS DE ENTIDAD
	 */
	public function uploadAnnexes(){
	    // MENSAJE POR DEFECTO
	    $sql=$this->setJSON("¡No se ha recibido ningun archivo adjunto!",true);
	    $model=array();
	    
	    // DATOS DE REGISTRO
	    $entity=$this->db->viewById($this->post['entidad_id'],$this->entity);
	    
	    // CARGAR REGISTRO SENESCYT
	    if($this->post['annexeEntity']=='ruc'){
	        
	        // PARAMETROS DE CARGA
	        $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","ruc-{$this->post['entidad_ruc']}",'ruc_file',0);
	        
	        // CARGAR ARCHIVO
	        $file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
	        // GENERAR MODELO TEMPORAL
	        $model=array(
	            'entidad_id'=>$entity['entidad_id'],
	            'entidad_sitioweb'=>$file
	        );
	        // ACTUALIZAR DATOS DE ENTIDAD
	        $sql=$this->db->executeTested($this->db->getSQLUpdate($model,'entidades'));
	        
	    }elseif($this->post['annexeEntity']=='representantelegal'){
	        
	        // INFORMACION DE REPRESENTANTE LEGAL
	        $person=$this->db->findById($entity['fk_representante_id'],'personas');
	        
	        // PARAMETROS DE CARGA
	        $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","cedula-{$person['persona_doc_identidad']}",'representantelegal_file',0);
	        // CARGAR ARCHIVO
	        $file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
	        
	        // GENERAR MODELO TEMPORAL
	        $model=array(
	            'persona_id'=>$entity['fk_representante_id'],
	            'persona_anexo_cedula'=>$file
	        );
	        // ACTUALIZAR DATOS DE ENTIDAD
	        $sql=$this->db->executeTested($this->db->getSQLUpdate($model,'personas'));
	        
	    }elseif($this->post['annexeEntity']=='apoderado'){
	        
	        // INFORMACION DE REPRESENTANTE LEGAL
	        $person=$this->db->findById($entity['fk_apoderado_id'],'personas');
	        
	        // PARAMETROS DE CARGA
	        $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("","cedula-{$person['persona_doc_identidad']}",'apoderado_file',0);
	        // CARGAR ARCHIVO
	        $file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
	        
	        // GENERAR MODELO TEMPORAL
	        $model=array(
	            'persona_id'=>$entity['fk_apoderado_id'],
	            'persona_anexo_cedula'=>$file
	        );
	        // ACTUALIZAR DATOS DE ENTIDAD
	        $sql=$this->db->executeTested($this->db->getSQLUpdate($model,'personas'));
	        
	    }
	    
	    // ADJUNTAR DATOS DE ARCHIVO ADJUNTO
	    if($sql['estado']) $sql['data']=$model;
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
	    
	}
	
	
	
	/*
	 * NOTIFICACIONES Y RECAUDACIONES POR ACTUALIZACIÓN EN RAZON SOCIAL
	 */ 
	private function prevUpdateDataFromClient(){
		$_SESSION['tempData']=$this->db->findById($this->post['entidad_id'],'entidades');
	}
	
	/*
	 * ENVÍAR CORREO ELECTRÓNICO SI LOS DATOS HAN SIDO ALTERADOS
	 */
	public function postUpdateDataFromClient(){
		//unset($_SESSION['tempData']);
		$prev=$_SESSION['tempData'];
		$post=$this->requestPost();
		if($prev['notificar_cambioinformacion'] || $post['notificar_cambioinformacion']){
			$mailCtrl=new ctrl\mailController('changeData');
			$auxData=array(
				'title_information'=>"Cambio de información",
				'persona_apellidos'=>$post['representantelegal_nombre'],
				'persona_nombres'=>" ({$post['representantelegal_ruc']})"
			);
			$mailCtrl->mergeData($auxData);
			if($prev['representantelegal_email']!=$post['representantelegal_email'])
				$mailCtrl->destinatariosList=array($post['representantelegal_email']);
			$mailCtrl->asyncMail("Cambio en los datos",$prev['representantelegal_email']);
			unset($_SESSION['tempData']);
		}
	}
	
	/*
	 * REALIZAR LA ACTUALIZACIÓN EN EL TOTAL DE COBRO POR ACTUALIZACIÓN DE RAZÓN SOCIAL
	 */
	private function testUpdateEntity(){
		$post=$this->requestPost();
		if(isset($post['entidad_update']) && $post['entidad_update']=='SI'){
			$total=$this->getConfParams()['SYS_VALUE_UPDATE_ENTITY_TOTAL']+$this->getConfParams()['SYS_VALUE_UPDATE_ENTITY'];
			$str="UPDATE admin.tb_params SET param_value='$total' WHERE param_key='SYS_VALUE_UPDATE_ENTITY_TOTAL'";
			$this->db->executeSingle($str);
		}
	} 
	
	/*
	 * ACCIÓN PARA VALIDAR EL LOGIN - USUARIO Y CONTRASEÑA
	 */
	public function testLogin(){
		
		// VALIDAR ESTADO DEL MÓDULO
		$this->testLoginModule();
		
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(entidad_ruc)=UPPER('{$this->post['usuario_login']}')");
		
		// CONSULTAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			
			// OBTENER INFORMACIÓN DE REGISTRO
			$row=$this->db->findOne($str);
			
			// VALIDAR LA CREACIÓN DEL USUARIO
			if($row['entidad_usuario']!='SI') $this->getJSON($this->setJSON("Estimad@ <b>{$row['representantelegal_nombre']}</b>, antes de continuar debe registrar sus credenciales de acceso a la plataforma en el siguiente enlace <b><a href='/signup/'>registro de credenciales de acceso</a></b>.",'info'));
			
			// COMPARAR CONTRASEÑA
			if($row['entidad_contrasenia']==$this->encryptText($this->post['usuario_password'],'sha256')){
			
				// VALIDAR ESTADO DE CUENTA
				if($row['entidad_estado']==$this->varGlobal['STATUS_ACTIVE']){
				
					// CAMBIO DE CONTRASEÑA EN EL PRIMER LOGUEO - RESTABLECIMIENTO DE CREDECNCIALES
					if(isset($this->post['usuario_new_password']) && isset($this->post['usuario_password_new'])){
						
						// GENERAR NUEVA CONTRASEÑA
						$newPass=$this->validatePairPassword($this->post['usuario_new_password'],$this->post['usuario_password_new'],'sha256');
						
						// CONTRASEÑA DIFERENTE A LA ANTERIOR
						if($row['entidad_contrasenia']==$newPass) $this->getJSON($this->varGlobal['MSG_PASS_DEPRECATED']);
						// NO PUEDE SER EL NUMERO DE CEDULA
						elseif(in_array($row['entidad_contrasenia'],[$row['entidad_ruc'],$row['persona_doc_identidad']])) $this->getJSON($this->varGlobal['MSG_PASS_EASY']);
						// NO PUEDE SER TAN DEBIL
						else{
							
							// GENERAR CREDENCIALES NUEVAS
							$row['entidad_contrasenia']=$newPass;
							$row['entidad_contrasenia_cambio']='NO';
							// ACTUALIZAR NUEVA CREDENCIAL
							$sql=$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
							
							// REGISTRAR CAMBIO DE CONTRASEÑA
							$sql=$this->setBinnacle($row['entidad_id'],$this->entity,"Cambio de contraseña por petición del sistema");
							
							// NOTIFICAR CAMBIO DE CONTRASEÑA
							if($sql['estado']){
								$mailCtrl2=new ctrl\mailController('newpass');
								$row['new_pass']=$this->post['usuario_new_password'];
								$mailCtrl2->mergeData($row);
								$mailCtrl2->mergeData(array('title_information'=>"Cambio de contraseña"));
								$mailCtrl2->asyncMail("Cambio de contraseña",$row['persona_correo']);
							}
							
						}
						
					}
					
					// VERIFICAR SI REQUIERE CAMBIO DE CONTRASEÑA
					if($row['entidad_contrasenia_cambio']=='SI'){
						// DATOS DE RETORNO
						$data=array(
							'estado'=>'info',
							'mensaje'=>"Bienvenid@ <b>{$row['entidad_razonsocial']}</b>".SESSION_NEW_PASSWORD,
							'pass'=>true,
							'name'=>$row['entidad_razonsocial']
						);
						// ENVIAR MENSAJE DE SALIDA
						$this->getJSON($data);
					}
					
					// REGISTRAR SESIÓN
					if($this->setSession($row['entidad_id'],$this->entity)['estado']){
						
						// DEFINIR MENSAJE DE BIENVENIDA PARA EL USUARIO
						$json=array_merge($this->setJSON("Bienvenid@ {$row['entidad_razonsocial']}",true),array('sessionId'=>$row['entidad_id']));
						
						// NOTIFICACIÓN DE ACCESO AL SISTEMA
						/*
						if($this->varGlobal['NOTIFY_SESSION_STATUS']==$this->varGlobal['STATUS_ACTIVE'] && $row['notificar_ingreso']){
							// CARGAR CONTROLADOR DE MAILER
							$mailCtrl=new ctrl\mailController('activityLogin');
							$auxData=array(
								'title_information'=>"Acceso exitoso",
								'serviceName'=>$this->varGlobal['MODULE_NAME'],
								'persona_apellidos'=>$row['representantelegal_nombre'],
								'persona_nombres'=>" ({$row['representantelegal_ruc']})"
							);
							$mailCtrl->mergeData($auxData);
							$mailCtrl->asyncMail("ACCESO EXITOSO - {$this->varGlobal['MODULE_NAME']}",$row['representantelegal_email']);
						}
						*/
						
					}else{
						// DEFINIR MENSAJE DE ERROR - NO SE PUDO INICIAR SESIÓN
						$json=$this->setJSON($this->varGlobal['SESSION_INIT_ERROR']);
					}
					
					// RETORNAR MENSAJE DE WS
					$this->getJSON($json);
					
				}else{
					// MENSAJE DE CUENTA DESHABILITADA
					$this->getJSON($this->varGlobal['MSG_ACCOUNT_DISABLED']);
				}
				
				
				
				
				
			}else{
				// REGISTRAR ACCESO FALLIDO
				$this->setBinnacle($row['entidad_id'],$this->entity,"Accesso fallido - Usuario: {$this->post['usuario_login']} <br>Clave: {$this->post['usuario_password']}");
				// NOTIFICAR ACCESO FALLIDO AL SISTEMA
				$this->getJSON($this->varGlobal['MSG_PASS_ERROR']);
			}
			
			
			
			
			
			
		}else{
			// DEFINIR MENSAJE DE INFORMACIÓN - CUENTA NO REGISTRADA
			$json=$this->setJSON($this->varGlobal['LOGIN_ENTITY_NOT_REGISTERED'],'info');
		}
		// RETORNAR CONSULTA
		$this->getJSON($json);
	}
	
	
	
	
	/*
	 * GENERAR DASHBOARD
	 */
	private function dashboardEntity($entityId){
		// MODELO DE DASHBOARD
		$dashboard=array();
		// LEER MODELO DE DATOS
		$config=$this->getConfig(true,'dashboardEntity');
		// RECORRER LISTA DE ENTIDADES
		foreach($config['list'] as $val){
			// PARÁMETROS DE ENTIDAD - ENCABEZADO - ICONO - FILTROS
			$title=$config['entities'][$val]['title'];
			$icon=$config['entities'][$val]['icon'];
			$paramKey=$config['entities'][$val]['param'];
			// MODELO DE ENTIDAD
			$aux=array('total'=>0,'uri'=>$config['entities'][$val]['uri']);
			// LISTAR ENTIDADES POR ESTADO
			foreach($config['entities'][$val]['options'] as $k=>$v){
				// CONTADOR DE REGISTROS
				$count=$this->db->numRows($this->db->selectFromView("vw_{$val}","WHERE fk_entidad_id={$entityId} AND $paramKey='{$k}'"));
				$aux['total']+=$count;
				$aux['options'][]=array(
					'title'=>$v['title'],
					'icon'=>$icon,
					'css'=>$v['css'],
					'count'=>$count
				);
			}
			// VALIDADOR DE ENTIDADES
			if($aux['total']>0) $dashboard[$title]=$aux;
		}
		// RETORNAR DATOS DE 
		return $dashboard;
	}
	
	/*
	 * PERFIL DE ENTIDAD
	 */
	public function getEntity(){
		// OBTENER SESIÓN DE USUARIO
		$sessionId=app\session::get();
		
		// PARÁMETROS DEL SISTEMA
		$config=array_merge($this->getConfParams(),$this->configSys(),$this->configDev());
		
		// MÓDULOS HABILITADOS
		$modules=$this->string2JSON($this->varGlobal['MAIN_TAMPLATE_AVAILABLE']);
		$validations=$this->string2JSON($this->varGlobal['MAIN_TAMPLATE_VALIDATIONS']);
		
		// INFORMACION DE ENTIDAD
		$entity=$this->db->findOne($this->db->selectFromView("vw_sesion_entidades","WHERE entidad_id={$sessionId}"));
		
		// DASHBOARD - DESGLOZADO
		// $info['dashboard']=$this->dashboardEntity($sessionId);
		
		// VALIDAR MODULOS HABILITADOS
		$mods=($entity['entidad_terminos']=='SI' && $entity['entidad_actualizacion']!=$validations['prevencion'])?$modules['prevencion']:[];
		
		// RETORNO DE DATOS
		$this->getJSON(array('data'=>$entity,'config'=>$config,'modules'=>$mods));
	}
	
	
	
	
	/*
	 * ENVIO DE CORREOS
	 */
	private function sendMail($entityId,$type='signup'){
		
		// OBTENER DATOS DE ENTIDAD
		$entity=$this->db->findById($entityId,$this->entity);
		
		// VALIDAR INGRESO DE CONTRASEÑA
		if($type=='signup') $entity['entidad_password']=$this->post['entidad_contrasenia_confirmacion'];
		
		// OBTENER DATOS DE REPRESENTANTE LEGAL
		$agent=$this->db->findById($entity['fk_representante_id'],'personas');
		
		// OBTENER DATOS DE PERSONA QUE REALIZÓ EL REGISTRO
		$responsable=$this->db->findById($entity['fk_responsableregistro_id'],'personas');
		
		// NOTIFICACIÓN MEDIANTE CORREO ELECTRÓNICO
		$mailCtrl=new ctrl\mailController('createUserEntities');
		// DATOS DE ENTIDAD DE REGISTRO
		$mailCtrl->mergeData($entity);
		// DATOS DE REPRESENTANTE LEGAL
		$mailCtrl->mergeData($agent);
		// DATOS DE RESPONSABLE
		$mailCtrl->mergeData(array(
			'registrador_nombre'=>"{$responsable['persona_nombres']} {$responsable['persona_nombres']}",
			'registrador_identificacion'=>$responsable['persona_doc_identidad'],
			'registrador_celular'=>$responsable['persona_celular']
		));
		// ENVIO DE CORREO
		$mailCtrl->asyncMail("CREACIÓN DE USUARIO {$entity['entidad_ruc']} | SERVICIOS EN LÍNEA",$agent['persona_correo']);
		
	}
	
	/*
	 * CONSULTAR REGISTRO POR RUC
	 */
	private function requestByRUC($ruc){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(entidad_ruc)=UPPER('{$ruc}')");
		// CONSULTAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0) return $this->db->findOne($str);
		// MENSAJE POR DEFECTO
		$this->getJSON("No se ha encontrado ningun registro relacionado con su dato ingresado!");
	}
	
	/*
	 * CONSULTAR SI EXISTE REGISTRO DE USUARIO
	 */
	private function requestByRegistre($type,$ruc){
		
		// VALIDAR SI EXISTE EL USUARIO CON RUC Y CEDULA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(entidad_ruc)=UPPER('{$ruc}') OR UPPER(entidad_ruc) LIKE UPPER('{$ruc}%')");
		// VALIDAR NUMERO DE REGISTROS
		if($this->db->numRows($str)>1) $this->getJSON("Al parecer existe un duplicado en su registro, favor acérquese a la Unidad de Prevención e Ingeniería del Fuego para que le ayuden con este inconveniente.");
		
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(entidad_ruc)=UPPER('{$ruc}')");
		// CONSULTAR SI EXISTE REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado ningun registro relacionado con su dato ingresado, por favor acérquese a las oficinas del Cuerpo de Bomberos con su copia de RUC o documento habilitante para el registro!");
		else{
			
			// OBTENER REGISTRO DE ENTIDAD
			$entity=$this->db->findOne($str);
			
			// VALIDAR EL TIPO DE CONSULTA
			if($type=='registre'){
				
				// VALIDAR SI SE HA REGISTRADO EL REPRESENTANTE LEGAL
				if(!isset($entity['fk_representante_id'])) $this->getJSON("Estimad@ <b>{$entity['entidad_razonsocial']}</b>, lamentamos los inconvenientes, al parecer existen inconsistencias para crear su usuario.<br><br><b>Favor acérquese a las instalaciones del Cuerpo de Bomberos para que actualicen sus datos.</b>");
				
				// VALIDAR SI YA HA SIDO CREADO EL USUARIO
				if($entity['entidad_usuario']=='SI') $this->getJSON("Estimad@ <b>{$entity['representantelegal_nombre']}</b>, lamentamos los inconvenientes, al parecer usted ya ha realizado este proceso.<br>Si ha olvidado su contraseña favor intente restablecerla en el siguiente enlace <a href='/restore/'>restablecer contraseña</a>, o acérquese a las instalaciones del Cuerpo de Bomberos para que le ayuden con el inconveniente.");
				
				// VALIDAR LAS CREDENCIALES NULAS DE CORREO Y TELEFONOS PARA ENTIDADES
				if(in_array($entity['entidad_correo'],[null,''])){
					$entity['entidad_correo']=$entity['representantelegal_email'];
					$entity['entidad_telefono']=$entity['persona_telefono'];
					$entity['entidad_celular']=$entity['persona_celular'];
				}
				
				// ENVIAR MENSAJE PARA SIGUIENTE PASO
				$this->getJSON($this->setJSON("A continuación complete la información de los siguientes formularios.",true,$entity));
				
			}else{
				
				// VALIDAR QUE NO EXISTA EL USUARIO
				if($entity['entidad_usuario']=='SI') $this->getJSON("Este usuario ya ha sido registrado!");
				// GENERAR CONTRASEÑA
				$this->post['entidad_contrasenia']=$this->validatePairPassword($this->post['entidad_contrasenia'],$this->post['entidad_contrasenia_confirmacion'],'sha256');
				
				// INICIAR TRANSACCION
				$this->db->begin();
				
				// VALOR POR DEFECTO PARA TIPO DE IDENTIFICACION DE REGISTRANTE
				$this->post['registre']['persona_tipo_doc']='CEDULA';
				// VALIDAR DATOS DE RESPONSABLE DE REGISTRO
				$responsable=$this->requestPerson($this->post['registre']);
				
				// ACTUALIZAR DATOS DE ENTIDAD PARA CREAR USUARIO
				$entity['fk_responsableregistro_id']=$responsable['persona_id'];
				$entity['entidad_usuario']='SI';
				$entity['entidad_usuario_creacion']=$this->getFecha('dateTime');
				$entity['entidad_contrasenia_cambio']='NO';
				$entity['entidad_apoderado']=$this->post['entidad_apoderado'];
				$entity['entidad_terminos']=($this->post['entidad_terminos']===true)?'SI':'NO';
				$entity['entidad_contrasenia']=$this->post['entidad_contrasenia'];
				
				// ACTUALIZAR Y CREAR USUARIO DE ENTIDAD
				$this->db->executeTested($this->db->getSQLUpdate($entity,$this->entity));
				
				// ACTUALIZAR DATOS DE REPRESENTANTE LEGAL
				$person=array(
					'persona_id'=>$entity['fk_representante_id'],
					'persona_correo'=>$this->post['persona_correo'],
					'persona_telefono'=>$this->post['persona_telefono'],
					'persona_celular'=>$this->post['persona_celular']
				);
				
				// ACTUALIZAR DATOS DE REPRESENTANTE LEGAL/PROPIETARIO
				$this->db->executeTested($this->db->getSQLUpdate($person,'personas'));
				
				// ENVIAR CORREO ELECTRÓNICO DE CREACION DE USUARIO
				$this->sendMail($entity['entidad_id'],'signup');
				
				// CERRAR TRANSACCION
				$this->getJSON($this->db->closeTransaction($this->setJSON("Usuario creado existosamente, ahora puede ingresar en la plataforma de Servicios en Línea y gozar de todos los servicios.",true)));
				
			}
			
		}
		
	}
	
	/*
	 * CONSULTAR REGISTRO
	 */
	public function requestEntity(){
		// CONSULTAR POR ID 
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// VALIDAR CREACION DE USUARIO
		elseif(isset($this->post['type']) && $this->post['type']=='registre') $json=$this->requestByRegistre($this->post['type'],$this->post['ruc']);
		// CREACION DE USUARIO
		elseif(isset($this->post['type']) && $this->post['type']=='createuser') $json=$this->requestByRegistre($this->post['type'],$this->post['entidad_ruc']);
		// CONSULTAR POR RUC
		elseif(isset($this->post['ruc'])) $json=$this->requestByRUC($this->post['ruc']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("ok",true,$json));
	}
	
	
}