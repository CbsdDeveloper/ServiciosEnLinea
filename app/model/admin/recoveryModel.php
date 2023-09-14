<?php namespace model\admin;
use api as app;
use controller as ctrl;
use api\session;

class recoveryModel extends app\controller {

	private $entity='recovery';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS LOCALES
		$this->config=$this->getConfig(true,'recovery');
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
	 * RESTABLECIMIENTO DE CONTRASEÑA - PASO 1
	 */
	private function resetPwd(){
		// MÓDULOS
		$mods=$this->config['modules'];
		// MODULO
		$sysName=session::$sysName;
		
		// VALIDAR SI EL MODULO CORRESPONDE A UNA ENTIDAD DEFINIDA 
		$entity=(isset($mods[$sysName]))?$mods[$sysName]:$mods['other'];
		
		// OBTENER ATRIBUTOS DE ENTIDAD
		$attr=$this->config['attr'][$entity];
		// VERIFICAR EL TIPO DE ACCESO
		$str=$this->db->selectFromView("vw_{$entity}","WHERE ({$attr['user']}='{$this->post['usuario_login']}' OR {$attr['mail']}='{$this->post['usuario_login']}')");
		
		// COMPROBAR SI EXISTE USUARIO
		if($this->db->numRows($str)>0){
    		
			// OBTENER DATOS DE USUARIO
		    $row=$this->db->findOne($str);
			
		    // VALIDAR SI EL REGISTRO ES REQUERIDO POR USUARIOS EXTERNOS
		    if($entity=='entidades' && $row['entidad_usuario']=='NO') $this->getJSON("Estimad@ <b>{$row['representantelegal_nombre']}</b>, su cuenta de usuario aún no ha sido registrada. Regístrela en el siguiente link para que pueda acceder a la plataforma <a href='/signup/'>regístrese aquí</a>");
		    
		    // VALIDAR QUE LA DIRECCION DE CORREO ELECTRÓNICO SEA REAL
		    if(!isset($row[$attr['mail']])) $this->getJSON("Estimad@ <b>{$row[$attr['name']]}</b>, no se puede completar esta acción, no ha sido registrado el correo electrónico para el restablecimiento de contraseña.<br>Favor, acérquese a las instalaciones del Cuerpo de Bomberos para que le ayuden con la actualización de datos.");
		    
			// COMENZAR TRANSACCIÓN
			$this->db->begin();
			
			// GENERAR TOKEN PARA RECOVERY EN BASE DE DATOS
			$sql=$this->setRecoveryToken($row[$attr['serial']],$entity);
			
			// INGRESAR TOKEN GENERADO
			$row['correo_asunto']="CAMBIO DE CONTRASEÑA";
			$row['recoveryURI']="{$this->varGlobal['URL_PATH_MAIN']}app/{$sysName}/recovery/REQUEST/?opt=resetPwdStep2&token={$sql['token']}";
			$row['mail_body_text']=$this->msgReplace($this->varGlobal['MAIL_BODY_RECOVERY'],$row);
			
			// INSTANCIA DE CORREO ELECTRÓNICO
			$mailCtrl=new ctrl\mailController('customMail',$this->config['mailServer'][$entity]);
			// CARGAR DATOS DE CORREO
			$mailCtrl->mergeData($row);
			// ENVIAR CORREO
			$mailCtrl->prepareMail("CAMBIO DE CONTRASEÑA",$row[$attr['mail']]);
			
			// RETORNAR CONSULTA
			$sql=$this->setJSON($this->msgReplace($this->varGlobal['MSG_LOGIN_RESET_PASSWORD'], array('userName'=>$row[$attr['name']],'userMail'=>$row[$attr['mail']])),true);
			
			// CERRAR TRANSACCIÓN
			return $this->db->closeTransaction($sql);
		} else {
			// ERROR EN CREDENCIALES - LOGIN
		    return $this->setJSON($this->varGlobal['MSG_LOGIN_USER_NOEXIST'],'info');
		}
	}
	
	/*
	 * PASO 2 PARA RESTABLECER CONTRASEÑA
	 */
	private function resetPwdStep2(){
		// SALIDA PRO DEFECTO
		$sql=$this->setJSON('Token inválido');
		// VALIDAR SI SE HA ENVIADO TOKEN
		if(isset($this->get['token'])){
			// STRING PARA BUSCAR TOKEN
			$str=$this->db->selectFromView('recovery',"WHERE recovery_token='{$this->get['token']}'");
			// CONSULTAR SI EXISTE TOKEN
			if($this->db->numRows($str)>0){
				// OBTENER INFORMACIÓN DE TOKEN
				$row=$this->db->findOne($str);
				// VALIDAR SI EL TOKEN HA SIDO ENVIADO
				if($row['recovery_estado']=='ENVIADO'){
					// COMENZAR TRANSACCIÓN
					$this->db->begin();
					
					// CAMBIAR ESTADO DE TOKEN
					$row['recovery_estado']='APLICADO';
					// ACTUALIZAR TOKEN - EXPIRADO
					$sql=$this->db->executeTested($this->db->getSQLUpdate($row,'recovery'));
					
					// OBTENER ATRIBUTOS DE ENTIDAD
					$attr=$this->config['attr'][$row['fk_tb']];
					// OBTENER DATOS DE ENTIDAD
					$userData=$this->db->findOne($this->db->selectFromView("vw_{$row['fk_tb']}","WHERE {$attr['serial']}={$row['fk_id']}"));
					// GENERAR NUEVA CONTRASEÑA
					$pass=$this->generatePassword(5);
					// ACTUALIZAR DATOS :: GENERAR NUEVA CONTRASENIA ENCRIPTADA
					$userData[$attr['pwd']]=$this->encryptText($pass,$attr['encrypt']);
					// ACTUALIZAR DATOS :: SOLICITAR CAMBIO DE CONTRASENIA
					$userData[$attr['flag']]=$attr['reset'];
					// GENERAR CONSULTA Y EJECUTAR SENTENCIA
					$sql=$this->db->executeTested($this->db->getSQLUpdate($userData,$row['fk_tb']));
					
					// INGRESAR TOKEN GENERADO
					$userData['correo_asunto']="NUEVA CONTRASEÑA GENERADA";
					$userData['new_pass']=$pass;
					$userData['mail_body_text']=$this->msgReplace($this->varGlobal['MAIL_BODY_SEND_NEWPWD'],$userData);
					
					// INSTANCIA DE CORREO ELECTRÓNICO
					$mailCtrl=new ctrl\mailController('customMail');
					// CARGAR DATOS DE CORREO
					$mailCtrl->mergeData($userData);
					// ENVIAR CORREO
					$mailCtrl->asyncMail("NUEVA CONTRASEÑA GENERADA",$userData[$attr['mail']]);
					
					// GUARDAR SOLICITUD DE RECOVERY
					$this->setBinnacle($row['fk_id'],$row['fk_tb'],"Nueva contraseña generada: [PWD={$pass};TOKEN={$this->get['token']}]");
					
					// MENSAJE DE RETORNO
					$sql=$this->setJSON($this->msgReplace($this->varGlobal['MSG_LOGIN_NEW_PASSWORD'],array('newPass'=>$pass)),true);
					
					// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
					$this->db->closeTransaction($sql);
					
				}else{
					// MENSAJE DE TOKE EXPIRADO
					$sql=$this->setJSON('Token expirado');
				}
			}
		}
		// INGRESO DE VARIABLE CON NOMBRE DE SESION DE FORMULARIO
		$sessionName='/'.session::$sysName;
		if($sessionName=='/prevencion')$sessionName='';
		// RETORNAR MENSAJE
		header("Location: {$sessionName}/requested/{$sql['mensaje']}/");
	}
	
	/*
	 * CONSULTAR ENTIDAD
	 */
	public function requestEntity(){
		// REALIZAR SOLICITUD DE RESTABLECIMIENTO - PASO 1
		if(isset($this->post['opt']) && $this->post['opt']=='resetPwd') $json=$this->resetPwd();
		// REALIZAR CAMBIO DE CONTRASEÑA - PASO 2
		elseif(isset($this->get['opt']) && $this->get['opt']=='resetPwdStep2') $json=$this->resetPwdStep2();
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}

}