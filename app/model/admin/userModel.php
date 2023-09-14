<?php namespace model\admin;
use api as app;
use model as mdl;
use controller as ctrl;

class userModel extends mdl\personModel {

	private $entity='usuarios';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}

	/*
	 * VALIDACIONES EN INICIO DE SESIÓN
	 */
	private function testLogin(){
		// VALIDAR ESTADO DEL MÓDULO
		$this->testLoginModule();
		// OBTENER DATOS DE FORMULARIO
		$this->post=$this->requestPost();
		
		// PERPARAR CONSULTA - INICIO DE SESION
		$str=$this->db->getSQLSelect('usuarios',['personas','perfiles'],"WHERE usuario_login='{$this->post['usuario_login']}' OR persona_doc_identidad='{$this->post['usuario_login']}' OR persona_correo='{$this->post['usuario_login']}'");
		
		// VALIDAR SI EXISTE EL USUARIO
		if($this->db->numRows($str)>0){
			
			// MANTENER RELACIÓN DE USUARIO
			$this->keepFKUser=true;
			
			// DATOS DE USUARIO
			$prev_row=$this->db->findOne($str);
			
			// INSTANCIA DE MAILERS
			$mailCtrl=new ctrl\mailController('activityLogin');
			$mailCtrl->mergeData($prev_row);
			
			// COMPARAR CONTRASEÑA
			if($prev_row['usuario_pass']==md5($this->post['usuario_password'])){
				
				// VALIDAR ESTADO DE CUENTA
				if($prev_row['usuario_estado']==$this->varGlobal['STATUS_ON']){
					
					// CAMBIO DE CONTRASEÑA EN EL PRIMER LOGUEO - RESTABLECIMIENTO DE CREDECNCIALES
					if(isset($this->post['usuario_new_password']) && isset($this->post['usuario_password_new'])){
						
						// ENCRIPTAR CONTRASEÑAS
						$pass1=md5($this->post['usuario_new_password']);
						$pass2=md5($this->post['usuario_password_new']);
						
						// VALIDAR LAS CONTRASEÑAS INGRESADAS
						if($pass1==$pass2){
							// VALIDAR NIVEL DE SEGURIDAD DE LA CONTRASEÑA
							$validPass=$this->testLevelPwd($this->post['usuario_new_password']);
							// CONTRASEÑA DIFERENTE A LA ANTERIOR
							if($prev_row['usuario_pass']==$pass1) return $this->varGlobal['MSG_PASS_DEPRECATED'];
							// NO PUEDE SER EL NUMERO DE CEDULA
							elseif($prev_row['usuario_pass']==$prev_row['persona_doc_identidad']) return $this->varGlobal['MSG_PASS_EASY'];
							// NO PUEDE SER TAN DEBIL
							elseif(!$validPass['estado']) return $validPass;
							else{
								// GENERAR CREDENCIALES NUEVAS
								$prev_row['usuario_pass']=$pass2;
								$prev_row['usuario_cambiar_pass']=0;
								// ACTUALIZAR NUEVA CREDENCIAL
								$sql=$this->db->executeTested($this->db->getSQLUpdate($prev_row,'usuarios'));
								// REGISTRAR CAMBIO DE CONTRASEÑA
								$sql=$this->setBinnacle($prev_row['usuario_id'],$this->entity,"Cambio de contraseña por petición del sistema");
								// NOTIFICAR CAMBIO DE CONTRASEÑA
								if($sql['estado']){
									$mailCtrl2=new ctrl\mailController('newpass');
									$prev_row['new_pass']=$this->post['usuario_new_password'];
									$mailCtrl2->mergeData($prev_row);
									$mailCtrl2->mergeData(array('title_information'=>"Cambio de contraseña"));
									$mailCtrl2->asyncMail("Cambio de contraseña",$prev_row['persona_correo']);
								}
								// OMITIR EL CAMBIO DE CONTRASEÑA OBLIGATORIO
								$prev_row['usuario_cambiar_pass']=false;
							}
						} else{
							// MENSAJE PARA EL USUARIO
							return $this->varGlobal['MSG_PASS_NOT_CONFIRM'];
						}
					}
		
					// VERIFICAR SI REQUIERE CAMBIO DE CONTRASEÑA
					if($prev_row['usuario_cambiar_pass']) return array('pass'=>true,'mensaje'=>"Bienvenid@ <b>{$prev_row['persona_apellidos']} {$prev_row['persona_nombres']}</b>".SESSION_NEW_PASSWORD);
					
					// REGISTRAR SESSION PHP
					if($this->setSession($prev_row['usuario_id'])['estado']){
						// NOTIFICAR ACCESO AL SISTEMA
						if($prev_row['usuario_acceso_correcto']){
							$mailCtrl->mergeData(array('title_information'=>"Acceso exitoso"));
							$mailCtrl->asyncMail("Acceso exitoso",$prev_row['persona_correo']);
						}	return array_merge($this->setJSON("Bienvenid@ {$prev_row['persona_apellidos']} {$prev_row['persona_nombres']}",true), array('sessionId'=>$prev_row['usuario_id'],'roles'=>$this->getAccesRol()));
					}else{
						// ERROR EN CREACIÓN DE SESION
						return $this->varGlobal['SESSION_INIT_ERROR'];
					}
					
				}else{
					// MENSAJE DE CUENTA DESHABILITADA
					return $this->varGlobal['MSG_ACCOUNT_DISABLED'];
				}
			}else{
				// REGISTRAR ACCESO FALLIDO
				$this->setBinnacle($prev_row['usuario_id'],$this->entity,"Accesso fallido - Usuario: {$this->post['usuario_login']} <br>Clave: {$this->post['usuario_password']}");
				// NOTIFICAR ACCESO FALLIDO AL SISTEMA
				if($prev_row['usuario_acceso_fallido']){
					$mailCtrl->mergeData(array('title_information'=>"Autenticación fallida"));
					$mailCtrl->asyncMail("Autenticación fallida",$prev_row['persona_correo']);
				}	return $this->varGlobal['MSG_PASS_ERROR'];
			}
		}
		// RETORNAR MENSAJE DE USUARIO INCORRECTO
		return $this->varGlobal['MSG_LOGIN_ERROR'];
	}
	
	/*
	 * LOGIN DE USAURIOS
	 */
	public function postLogin(){
		// VALIDAR INICIO DE SESION DE USUARIO
		$this->getJSON($this->testLogin());
	}

	
	
	/*
	 * GENERAR TOKEN PARA CONSULTA
	 */
	private function setRecoveryToken($entityId,$entity){
	    // GENERAR TOKEN PARA RECOVERY EN BASE DE DATOS
	    $model=array(
	        'fk_id'=>$entityId,
	        'fk_tb'=>$entity,
	        'recovery_token'=>md5($this->generatePassword(10).$this->getFecha())
	    );
	    // GUARDAR SOLICITUD DE RECOVERY
	    $this->setBinnacle($entityId,$entity,"Solicitud de restablecimiento de contraseña: [TOKEN={$model['recovery_token']}]");
	    // EJECUTAR SENTENCIA
	    $sql=$this->db->executeTested($this->db->getSQLInsert($model,'recovery'));
	    // RETORNAR CONSULTA
	    return array_merge($sql,array('token'=>$model['recovery_token']));
	}
	
	
	/*
	 * RETORNA LA SESIÓN ACTIVA DEL SISTEMA
	 */
	public function getSession(){
		// ID DE USUARIO
		$id=app\session::getSessionAdmin();
		
		// MODELO DE SESSION
		$session=array();
		
		// DATOS DE SESIÓN
		$session['session']=$this->db->viewById($id,$this->entity);
		
		// MENSAJES RELACIONADOS A USUARIO
		$session['messages']=array();
		
		// LISTADO DE VARIABLES DEL SISTEMA & PARÁMETROS 
		$session['setting']=array_merge($this->getParamsModule('ALL'),$this->configDev());
		
		// ROLES DE USUARIO
		$session['roles']=$this->getAccesRol();
		
		// RETORNAR DATOS DE USUARIO
		$this->getJSON($session);
	}
	
	
	
	/*
	 * REGISTRO DE USUARIOS
	 */
	public function insertUser(){
		// OBTENER DATOS DE FORMULARIO
		$post=$this->requestPost();
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($post);
		
		$user=array(
		    'fk_persona_id'=>$person['persona_id'],
			'fk_perfil_id'=>$post['fk_perfil_id'],
			'usuario_login'=>$post['usuario_login'],
		    'usuario_pass'=>md5($person['persona_doc_identidad'])
		);
		// REGISTRAR USUARIO
		$sql=$this->db->closeTransaction($this->db->executeTested($this->db->getSQLInsert($user,$this->entity)));
		// NOTIFICAR MEDIANTE MAIL EL REGISTRO AL USUARIO
		if($sql['estado']){
			$mailCtrl=new ctrl\mailController('signUp');
			$mailCtrl->mergeData($this->db->findOne($this->db->selectFromView("vw_$this->entity","WHERE usuario_login='{$post['usuario_login']}'")));
			$mailCtrl->asyncMail("Registro en el Sistema",$person['persona_correo']);
		}	$this->getJSON($sql);
	}

	/*
	 * RESTABLECER DATOS DE USUARIO
	 */
	private function restoreAccount($type,$userId){
		$user=$this->db->viewById($userId,$this->entity);
		$pass='*****';
		if($type=='pass'){
			$opcion='Clave de acceso';
			// MODELO DE USUARIO
			$pass=$user['persona_doc_identidad'];
			$user['usuario_pass']=md5($pass);
			$user['usuario_cambiar_pass']='true';
			// GENERAR SENTENCIA
			$sql=$this->db->executeTested($this->db->getSQLUpdate($user,$this->entity));
		}elseif($type=='roles'){
			$opcion='Roles de acceso';
			// GENERAR SENTENCIA
			$this->db->executeTested($this->db->getSQLDelete("tb_usuario_rol", "WHERE fk_usuario_id=$userId"));
			$sql=$this->db->executeTested("INSERT INTO admin.tb_usuario_rol (SELECT $userId, fk_rol_id FROM admin.tb_groups WHERE fk_perfil_id={$user['fk_perfil_id']})");
		}else return $this->setJSON("Especificar la operación a realizar!");
		// enviar nuevas credenciales
		$mailCtrl=new ctrl\mailController('changeprofile');
		$user['new_pass']=$pass;
		$user['loginURI']=$this->getConfig('loginURI');
		$mailCtrl->mergeData($user);
		$mailCtrl->mergeData(array('title_information'=>"Restablecimiento de datos: ($opcion)",'msg_description'=>"Se realizaron cambio en su perfil de usuario"));
		$mailCtrl->asyncMail("Cambios en perfil: ($opcion)",$user['persona_correo']);
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * ACTUALIZACIÓN DE PERFIL DE USUARIOS
	 */
	private function updateProfile($post){
		$post['new_pass']="***";
		// DATOS DE USUARIO
		$old_data=$this->db->findOne($this->db->getSQLSelect($this->entity,['personas'],"WHERE usuario_id={$post['usuario_id']}"));
		// VALIDAR SI ES UN CAMBIO DE PERFIL O DE CUENTA
		// PERFIL: SESSION
		// CUENTA: ADMINISTRACIÓN DE USUARIOS
		if(isset($post['option']) && $post['option']=='profile'){
			if(isset($post['usuario_password'])){
				// REGISTRAR NUEVA CONTRASEÑA
				$post['usuario_pass']=md5($post['usuario_password']);
				// VERIFICAR QUE LA CONTRASEÑA INGRESADA ES CORRECTA
				if(md5($post['usuario_password'])!=$old_data['usuario_pass']) $this->getJSON($this->setJSON(PASSWORD_INVALID));
				// VERIFICAR SI SE HA INGRESADO LA NUEVA CONTRASEÑA
				// CASO CONTRASRIO NO HACER CASO
				if(isset($post['usuario_new_password']) && !empty($post['usuario_new_password'])){
					// VALIDAR NIVEL DE SEGURIDAD DE CONTRASEÑA
					$validPass=$this->testLevelPwd($post['usuario_new_password']);
					// CONTRASEÑA DIFERENTE A LA ANTERIOR
					if($old_data['usuario_pass']==md5($post['usuario_new_password'])) return $this->varGlobal['MSG_PASS_DEPRECATED'];
					// NO PUEDE SER EL NUMERO DE CEDULA
					elseif($old_data['usuario_pass']==$old_data['persona_doc_identidad']) return $this->varGlobal['MSG_PASS_EASY'];
					elseif($old_data['usuario_pass']==$post['persona_doc_identidad']) return $this->varGlobal['MSG_PASS_EASY'];
					// NO PUEDE SER TAN DEBIL
					elseif(!$validPass['estado']) return $validPass;
					else {
						$post['usuario_pass']=md5($post['usuario_new_password']);
						$post['new_pass']=$post['usuario_new_password'];
					}
				}
			}else return $this->setJSON("No ha ingresado su clave de acceso!");
		}
		// ACTUALIZAR DATOS DE USUARIO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
		// VALIDAR ACTUALIZACIONES Y NOTIFICAR MEDIANTE CORREO
		if($sql['estado']){
			// ACTUALIZAR DATOS DE PERSONA
			$sql=$this->updatePerson(false);
			// VERIFICAR ENVIÍO DE NOTIFICACIONES
			if($sql['estado'] && $old_data['usuario_cambio_perfil']){
				$mailCtrl=new ctrl\mailController('changeprofile');
				$mailCtrl->mergeData($post);
				$mailCtrl->mergeData(array('title_information'=>"Cambio en datos de la cuenta",'msg_description'=>"Hemos detectado el cambio en los datos del sistema correspondientes a tu cuenta"));
				$mailCtrl->asyncMail("Registro de cambio",$post['persona_correo']);
			}
		}	return $sql;
	}
	
	
	
	/*
	 * ACTUALIZAR DATOS DE USUARIOS
	 */
	public function updateUser(){
		// OBTENER DATOS DE FORMULARIO
		$post=$this->requestPost();
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// RESTABLECER CONTRASEÑA Y/O PROVILEGIOS EN EL SISTEMA 
		if(isset($post['option']) && in_array($post['option'],['pass','roles'])) $json=$this->restoreAccount($post['option'],$post['id']);
		// CAMBIOS EN DATOS DE CUENTA
		elseif(isset($post['option']) && in_array($post['option'],['account','profile'])) $json=$this->updateProfile($post);
		// RESPUESTA POR DEFECTO
		else $json=$this->setJSON("Especificar la operación a realizar!");
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	

	/*
	 * LISTADO DE USUARIOS CON PERFIL INSPECTOR
	 */
	private function requestByProfile($perfilId){
		// LISTADO DE PERFILES
		$profiles=array(
			'jtp'=>'5',
			'inspector'=>'6,5',
			'coordinator'=>'9'
		);
		$perfilId=$profiles[$perfilId];
		// CONSULTAR PERMISOS
		$where="WHERE fk_perfil_id IN ($perfilId) AND usuario_estado='ACTIVO' ORDER BY persona_apellidos ";
		return $this->db->findAll($this->db->selectFromView("vw_{$this->entity}",$where));
	}
	
	/*
	 * CONSULTA DE USUARIOS
	 */
	private function requestById($id){
		$json=$this->setJSON("Usuario no encontrado!");
		// CONSULTAR USUARIO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE usuario_id=$id");
		if($this->db->numRows($str)>0){
			$data=$this->db->findOne($str);
			$permiso=$this->db->findAll($this->db->selectFromView('vw_usuario_rol',"WHERE fk_usuario_id=$id ORDER BY modulo_nombre,submodulo_id::text"));
			$data['permisos']=$permiso;
			$json=$this->setJSON("OK",true,$data);
		}	$this->getJSON($json);
	}
	
	/*
	 * CONSULTAR ENTIDAD
	 */
	public function requestUser(){
		// OBTENER DATOS DE FORMULARIO
		$post=$this->requestPost();
		// VERIFICAR 
		if(isset($post['id'])) $json=$this->requestById($post['id']);
		// BUSCAR POR PERFILES
		elseif(isset($post['profile'])) $json=$this->requestByProfile($post['profile']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}

}