<?php namespace model\subjefature;
use api as app;
use controller as ctrl;
use model as mdl;

class personalModel extends mdl\personModel {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='personal';
	private $config;
	private $succes=0;
	private $insert=0;
	private $update=0;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'subjefatura');
	}
	
	
	/*
	 * CONSULTAR SI ESTA EN UN PELOTON
	 */
	protected function vaidateStaffInPlatoon($personalId){
		// DATOS DE PELOTON
		$str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$personalId} AND tropa_estado='ACTIVO'");
		// RETORNAR ESTADO DE CONSULTA
		return array(
			'status'=>$this->db->numRows($str)>0,
			'data'=>$this->db->findOne($str)
		);
	}
	
	
	/*
	 * LISTADO DE PERSONAL POR ESTACIONES
	 */
	private function requestByPlatoon($post){
		// MODELO DE DATOS
		$data=array(
			'resources'=>[],
			'personal'=>[],
			'selected'=>[],
			'functions'=>$this->string2JSON($this->varGlobal['TTHH_OFFICIAL_FUNCTIONS_LIST'])
		);
		// LISTADO DE PERFILES
		$perfilId=$this->varGlobal['TTHH_OFFICIAL_LIST_ID'];
		// DATOS DE ESTACIÓN
		$data=array_merge($this->db->viewById($post['fk_peloton_id'],'pelotones'),$data);
		// LISTA DE PERSONAL YA INGRESADO
		foreach($this->db->findAll($this->db->selectFromView('vw_peloton_personal',"WHERE fk_peloton_id={$post['fk_peloton_id']}")) as $staff){
			// INGRESAR MODELO DE PERSONAL
			$data['selected'][$staff['fk_personal_id']]=$staff['ppersonal_cargo'];
		}
		// CONSULTAR PERSONAL DE UNA ESTACIÓN
		$where="WHERE fk_puesto_id IN ($perfilId) ORDER BY personal_nombre";
		$staff=$this->db->findAll($this->db->selectFromView("vw_{$this->entity}",$where));
		// PARSE ROW TO MODEL JSON
		foreach($staff as $v){
			// CREACIÓN DE MODELO DE PERSONAL POR PUESTOS
			if(!isset($data['resources'][$v['puesto_nombre']])) $data['resources'][$v['puesto_nombre']]=array();
			// INSERTAR DATOS DE PERSONAL EN MODELO DE DATOS
			$data['resources'][$v['puesto_nombre']][$v['personal_id']]=$v['personal_id'];
			// LISTA CON LOS NOMBRES DE LAS PERSONAS
			$data['personal'][$v['personal_id']]=array(
				'personal_nombre'=>$v['personal_nombre'],
				'persona_doc_identidad'=>$v['persona_doc_identidad']
			);
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * PERFIL DEL PERSONAL
	 */
	protected function profileByPersonal($personalId){
		// MODELO DE INFORMACIÓN DEL PERFIL
		$data=array();
		// PERFIL
		$data['session']=$this->db->viewById($personalId,$this->entity);
		// VALIDAR SI ES ENCARGADO DE ESTACION
		$data['session']['stationManager']=$this->db->numRows($this->db->selectFromView('vw_encargado_estacion',"WHERE ppersonal_id={$data['session']['ppersonal_id']} ORDER BY tropa_id DESC"))>0;
		// CARGO QUE DESEMPEÑA
		$data['job']=$this->db->findAll($this->db->selectFromView('vw_personal_puestos',"WHERE fk_personal_id={$personalId} AND ppersonal_estado='EN FUNCIONES'"));
		// PELOTON AL QUE SE HA ASIGNADO
		$data['platoon']=$this->db->findOne($this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$personalId} AND distributivo_estado='ACTIVO' LIMIT 1"));
		// RETORNAR PERFIL
		return $data;
	}
	
	/*
	 * GENERAR DASHBOARD
	 */
	private function dashboardEntity($personalId){
		// INFORMACION DE PERSONAL
		$auxPersonal=$this->db->findById($personalId,'personal');
		
		// MODELO DE DATOS
		$dashboard=array();
		$config=$this->getConfig(true,'dashboardStaff');
		// RECORRER LISTA DE ENTIDADES
		foreach($config['list'] as $val){
			
			// PARÁMETROS DE ENTIDAD
			$title=$config['entities'][$val]['title'];
			$icon=$config['entities'][$val]['icon'];
			$paramKey=$config['entities'][$val]['param'];
			$fkFilter=$config['entities'][$val]['fk'];
			$paramFilter=$config['entities'][$val]['filterId'];
			// CONTADORES
			$aux=array('total'=>0,'uri'=>$config['entities'][$val]['uri']);
			foreach($config['entities'][$val]['options'] as $k=>$v){
				
				// CONTADOR DE ENTIDADES
				$count=$this->db->numRows($this->db->selectFromView("vw_{$val}","WHERE {$fkFilter}={$auxPersonal[$paramFilter]} AND $paramKey='$k'"));
				$aux['total']+=$count;
				$aux['options'][]=array(
					'title'=>$v['title'],
					'icon'=>$icon,
					'css'=>$v['css'],
					'count'=>$count
				);
			}
			// INSERTAR SOLO SI HA SIDO INGRESADO UN DOCUMENTO
			if($aux['total']>0) $dashboard[$title]=$aux;
		}
		return $dashboard;
	}
	
	/*
	 * SESSION DE USUARIO
	 */
	private function getSession(){
		// ID DE USUARIO
		$sessionId=app\session::get();
		// MODELO DE SESSION
		$session=$this->profileByPersonal($sessionId);
		// DASHBOARD
		$session['dashboard']=array();
		// LISTADO DE VARIABLES DEL SISTEMA & PARÁMETROS 
		$session['setting']=array_merge($this->getParamsModule('SUBJEFATURA'),$this->configDev());
		// MÓDULOS 
		$session['modules']=$this->string2JSON($session['setting']['MAIN_TAMPLATE_AVAILABLE'])['subjefatura'];
		// RETORNAR DATOS DE PERSONAL
		return $session;
	}
	
	/*
	 * LOGIN DE PERSONAL
	 */
	private function testLogin($post){
		// GENERAR STRING PARA CONSULTAR SI EXISTE USUARIO
		$strWhere="WHERE (persona_doc_identidad='{$post['usuario_login']}' OR personal_correo_institucional='{$post['usuario_login']}') ORDER BY custom_sort(ARRAY['EN FUNCIONES', 'PASIVO'],ppersonal_estado)";
		$str=$this->db->selectFromView("vw_{$this->entity}",$strWhere);
		// CONSULTAR SI EXISTE USUARIO
		if($this->db->numRows($str)>0){
			// DATOS DE USUARIO
			$userData=$this->db->findOne($str);
			// COMPARAR CONTRASEÑA
			if($userData['personal_contrasenia']==md5($post['usuario_password'])){
				// VALIDAR SI LA CUENTA ESTÁ ACTIVA
				if($userData['ppersonal_estado']=='EN FUNCIONES'){
					
					// VALIDAR QUE SEA PERONAL OPERATIVO
					if($userData['puesto_modalidad']!='OPERATIVO') return $this->varGlobal['MSG_MODULE_NOT_ALLOW'];
					
					// CAMBIO DE CONTRASEÑA EN EL PRIMER LOGUEO - RESTABLECIMIENTO DE CREDECNCIALES
					if(isset($post['usuario_new_password']) && isset($post['usuario_password_new'])){
						// ENCRIPTAR LA NUEVA CONTRASEÑA
						$pass1=md5($post['usuario_new_password']);
						$pass2=md5($post['usuario_password_new']);
						// COMPARAR LA NUEVA CONTRASEÑA
						if($pass1==$pass2){
							// VALIDAR EL NIVEL DE ONTRASEÑA
							$validPass=$this->testLevelPwd($post['usuario_new_password']);
							// CONTRASEÑA DIFERENTE A LA ANTERIOR
							if($userData['personal_contrasenia']==$pass1) return $this->varGlobal['MSG_PASS_DEPRECATED'];
							// NO PUEDE SER EL NUMERO DE CEDULA
							elseif($userData['personal_contrasenia']==$userData['persona_doc_identidad']) return $this->varGlobal['MSG_PASS_EASY'];
							// NO PUEDE SER TAN DEBIL
							elseif(!$validPass['estado']) return $validPass;
							else{
								// ACTUALIZAR CONTRASEÑA Y DESHABILITAR EL CAMBIO DE CONTRASEÑA
								$userData['personal_contrasenia']=$pass2;
								$userData['personal_cambiar_pass']='NO';
								// REGISTRAR CAMBIO DE CONTRASEÑA
								$sql=$this->db->executeTested($this->db->getSQLUpdate($userData,$this->entity));
								// REGISTRAR CAMBIO DE CONTRASEÑA
								$sql=$this->setBinnacle($userData['personal_id'],$this->entity,"Cambio de contraseña por petición del sistema");
								// NOTIFICAR CAMBIO DE CONTRASEÑA
								if($sql['estado']){
									// INSTANCIA DE CONTROLADOR DE CORREOS
									$mailCtrl2=new ctrl\mailController('TTHHPersonal');
									// DEFINIR DATOS DE MENSAJE
									$userData['new_pass']=$post['usuario_new_password'];
									$userData['perfil_nombre']=$userData['puesto_nombre'];
									$userData['usuario_login']=$userData['persona_doc_identidad'];
									// CUERPO DEL MENSAJE
									$userData['correo_asunto']="CAMBIO DE CONTRASEÑA";
									$userData['mail_body_text']=$this->varGlobal['MAIL_BODY_NEWPASS'];
									// INGRESAR DATOS DE CORREO
									$mailCtrl2->mergeData($userData);
									// ENVIAR CORREO
									$mailCtrl2->asyncMail("CAMBIO DE CONTRASEÑA",$userData['personal_correo_institucional']);
								}
							}
						}else{
							// LA NUEVA CONTRASEÑA NO COINCIDE
							return $this->varGlobal['MSG_PASS_NOT_CONFIRM'];
						}
					}
					// VERIFICAR SI REQUIERE CAMBIO DE CONTRASEÑA
					if($userData['personal_cambiar_pass']=='SI'){
						return array(
							'pass'=>true,
							'img'=>$userData['persona_imagen'],
							'name'=>$userData['personal_nombre'],
							'estado'=>'info',
							'mensaje'=>$this->msgReplace($this->varGlobal['MSG_PASS_CHANGE_REQUIRED'],$userData),
							'info'=>array(
								'estado'=>'info',
								'mensaje'=>$this->msgReplace($this->varGlobal['MSG_LOGIN_NEW_PASS'],$userData)
							)
						);
					}
					// REGISTRAR SESSION PHP
					if($this->setSession($userData['personal_id'],$this->entity)['estado']){
						// NOTIFICAR ACCESO AL SISTEMA
						return array_merge($this->setJSON("Bienvenid@ {$userData['personal_nombre']}",true), array('sessionId'=>$userData['personal_id']));
					}
					// ERROR AL REGISTRAR LA SESIÓN
					return $this->varGlobal['SESSION_INIT_ERROR'];
				}
				// CUENTA DESHABILITADA
				return $this->varGlobal['MSG_ACCOUNT_DISABLED'];
			}else{
				// REGISTRAR ACCESO FALLIDO
				$this->setBinnacle($userData['personal_id'],$this->entity,"Accesso fallido - Usuario: {$this->post['usuario_login']} <br>Clave: {$this->post['usuario_password']}");
				// ERROR EN CREDENCIALES - CONTRASEÑA
				return $this->varGlobal['MSG_PASS_ERROR'];
			}
		}
		// ERROR EN CREDENCIALES - LOGIN
		return $this->varGlobal['MSG_LOGIN_ERROR'];
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
		// VERIFICAR SESSION
		if(isset($this->post['opt']) && $this->post['opt']=='LogIn') $this->getJSON($this->testLogin($this->post));
		// OBTENER DATOS PERSONALES DESDE - TTHH
		elseif(isset($this->post['account'])) $this->getJSON($this->getSession());
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
	}
	
}