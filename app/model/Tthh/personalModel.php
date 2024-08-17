<?php namespace model\Tthh;
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
		$this->config=$this->getConfig(true,'tthh');
	}
	
	
	/*
	 * VALIDAR SI EL PERSONAL ESTA DE VACACIONES
	 */
	protected function testPersonalInVacation($personalId,$date){
	    // CONSULTAR SI EL PERSONAL ESTA DE VACACIONES
	    $str=$this->db->selectFromView('vacaciones_solicitadas',"WHERE fk_personal_id={$personalId} AND ('{$date}' BETWEEN vacacion_fecha_desde AND vacacion_fecha_hasta) AND vacacion_estado NOT IN ('SOLICITUD ANULADA','SOLICITUD NEGADA')");
	    // RETORNAR CONSULTA
	    return array(
	        'status'=>$this->db->numRows($str)>0,
	        'data'=>$this->db->findOne($str)
	    );
	}
	
	/*
	 * CONSULTAR SI ESTA EN UN PELOTON
	 */
	protected function validateStaffInPlatoon($personalId){
		// DATOS DE PELOTON
		$str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$personalId} AND tropa_estado='ACTIVO'");
		// RETORNAR ESTADO DE CONSULTA
		return array(
			'status'=>$this->db->numRows($str)>0,
			'data'=>$this->db->findOne($str)
		);
	}
	
	/*
	 * INFORMACION DE PELOTON ASIGNADO A OPERATIVOS
	 */
	protected function getActivePlatoon($staff){
	    // GENERAR MODELO DE CONSULTA
	    $data=array();
	    
	    // VALIDAR SI EL PERSONAL ES OPERATIVO
	    if($staff['puesto_modalidad']=='OPERATIVO'){
	        // CONSULTAR EXISTENCIA DE PERSONAL
	        $platoon=$this->validateStaffInPlatoon($staff['personal_id']);
	        
	        // VALIDAR SI EXISTE REGISTRO
	        if(!$platoon['status']) return $data;
	        
            // OBTENER INFORMACION DE ENCARGADO DE ESTACION
            $encargado=$this->db->findOne($this->db->selectFromView('vw_encargado_estacion',"WHERE fk_dist_pelo_id={$platoon['data']['fk_dist_pelo_id']} AND tropa_estado='ACTIVO'"));
            // VALIDAR SI LA PERSONA QUE SOLICITA INFORMACION NO ES EL MISMO ENCARGADO DE ESTACION
            if($platoon['data']['tropa_id']!=$encargado['tropa_id']){
                $data['encargadoestacion']=$encargado;
                // INFORMACION DE FUNCIONES DE PERSONAL
                $data['encargadoestacion']['data']=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad_id={$encargado['ppersonal_id']} AND estado IN ('EN FUNCIONES','DELEGACIÓN ASIGNADA') ORDER BY puesto_grado"));
            }
            
            // VALIDAR JEFE DE OPERACIONES
            $str=$this->db->selectFromView('vw_personal_funciones',"WHERE puesto_id=56 AND estado IN ('EN FUNCIONES','DELEGACIÓN ASIGNADA') ORDER BY puesto_grado");
            if($this->db->numRows($str)>0) $data['operaciones']=$this->db->findOne($str);
	    }
	    
	    // RETORNAR CONSULTA
	    return $data;
	}
	
	/*
	 * CONSULTAR DIRECTOR DE UN PUESTO
	 */
	protected function getLeaderFromJob($job){
	    // GENERAR MODELO
	    $data=array();
	    
	    // CONSULTAR DATOS DE PUESTO
	    $row=$this->db->findOne($this->db->selectFromView('puestos',"WHERE puesto_id={$job['puesto_id']}"));
	    // VALIDAR SI PUESTO ES DIRECCION
	    if($row['puesto_direccion']=='SI') return array('boss'=>$job);
	    
	    // OBTENER PUESTO DE DIRECTOR
	    $strWhr=$this->db->selectFromView($this->db->setCustomTable('puestos','puesto_id'),"WHERE puesto_estado='ACTIVO' AND puesto_direccion='SI' AND fk_direccion_id={$row['fk_direccion_id']}");
	    $str=$this->db->selectFromView('vw_personal_funciones',"WHERE puesto_id=({$strWhr}) AND estado IN ('EN FUNCIONES','DELEGACIÓN ASIGNADA')");
	    if($this->db->numRows($str)>0){
	        // VALIDAR SI PUESTO ES DIRECTOR
	        $data['boss']=$this->db->findOne($str);
	        $data['concede']=$job;
	    }
	    
	    // RETORNAR MODELO
	    return $data;
	}
	
	/*
	 * DATOS DE JEFE INMEDIATO EN FUNCIONES
	 */
	protected function getActiveBoss($jobId){
		// GEENRAR MODELO TEMPORAL
		$data=array();
	
		//return $jobId;
		
		// DATOS DEL SUBORDINADO
		$subordinado=$this->db->findOne($this->db->selectFromView('subordinados',"WHERE fk_subordinado_id={$jobId}"));
		
		if ($subordinado){
			// CONSULTAR JEFE INMEDIARTO
			$str=$this->db->selectFromView('vw_personal_funciones',"WHERE puesto_id={$subordinado['fk_superior_id']} AND entidad='PUESTOS' AND estado='EN FUNCIONES'");
			if($this->db->numRows($str)>0){
				// MODELO TEMPORAL DE JEFES
				$temp=$this->getLeaderFromJob($this->db->findOne($str));
				// INGRESO DE PARÁMETROS
				$data=$temp;
			}
			
			// CONSULTAR SI EXISTE JEFE INMEDIATO DELEGADO
			$str=$this->db->selectFromView('vw_personal_funciones',"WHERE puesto_id={$subordinado['fk_superior_id']} AND entidad='DELEGACION' AND estado='DELEGACIÓN ASIGNADA'");
			if($this->db->numRows($str)>0){
				// MODELO TEMPORAL DE JEFES
				$temp=$this->getLeaderFromJob($this->db->findOne($str));
				// VALIDAR SI PUESTO ES DIRECTOR
				$data['bossAlt']=$temp['boss'];
				if(isset($temp['concede'])) $data['concedeAlt']=$temp['concede'];
			}
		}
		
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR DATOS DE PUESTOS DEL PERSONAL
	 */
	protected function getStaffProfiles($personalId){
		// GENERAR MODELO DE CONSULTA
	    $data=array('ACTIVO'=>null);
	    
		// LISTAR LOS PUESTOS Y CONSULTAR INFORMACIÓN
		foreach($this->db->findAll($this->db->selectFromView('vw_personal_funciones',"WHERE personal_id={$personalId} AND estado IN ('EN FUNCIONES','DELEGACIÓN ASIGNADA') ORDER BY puesto_grado")) as $v){
		    // MODELO PARA PELOTON
		    $platoon=ARRAY();
		    
			// VALIDAR TIPO DE REGISTRO
			if($v['definicion']=='DELEGADO'){
				// CONSULTAR DATOS DE DELEGACION
				$dl=$this->db->findOne($this->db->selectFromView('delegaciones',"WHERE fk_personal_id={$personalId} AND delegacion_estado='DELEGACIÓN ASIGNADA'"));
				// CONSULTAR SI A LA DELEGACION SE HA SIDO ASIGNADO UN JEFE, SI NO HA SIDO ASIGNADO BUSCAR EN BASE A PUESTO
				if(isset($dl['fk_jefe_id']) && $dl['fk_jefe_id']>0){
				    $boss=$this->getLeaderFromJob($this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad='PUESTOS' AND entidad_id={$dl['fk_jefe_id']} AND estado='EN FUNCIONES'")));
				}else{
				    $boss=$this->getActiveBoss($v['puesto_id']);
				}
			}else{
				// MODELO DE PUESTO
			    $boss=$this->getActiveBoss($v['puesto_id']);
			}
			
			// CONSULTAR DATOS DE OPERATIVOS
			$platoon=$this->getActivePlatoon($v);
			
			// INSERTAR REGISTRO
			$data[$v['definicion']]=array_merge(array('data'=>$v),$boss,$platoon);
		}
		
		// CONSULTAR SI EXISTE ALGÚN DELEGADO COMO DIRECTOR DE TTHH
		$str=$this->db->selectFromView('vw_personal_funciones',"WHERE puesto_id=12 AND estado='DELEGACION ASIGNADA'");
		// VALIDAR CONSULTA
		$data['TTHH']=($this->db->numRows($str)>0)?$this->db->findOne($str):$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE puesto_id=12 AND estado='EN FUNCIONES'"));
		
		// VALIDAR PERFIL ACTIVO
		if(isset($data['ENCARGADO'])){
		    $data['ACTIVO']=(isset($data['DELEGADO']) && $data['DELEGADO']['data']['puesto_grado']<$data['ENCARGADO']['data']['puesto_grado'])?$data['DELEGADO']:$data['ENCARGADO'];
		}elseif(isset($data['DELEGADO'])){
			$data['ACTIVO']=$data['DELEGADO'];
		}else{
		    $data['ACTIVO']=$data['TITULAR'];
		}
		
		// RETORNAR CONSULTA
		return $data;
	}
	
	
	
	/*
	 * PARSE MODEL
	 */
	private function parseEntityModel($data,$entity){
		// MODELO AUXILIAR
		$model=array();
		// RECORRER ATRIBUTOS DE ENTIDAD
		foreach($this->config['personal'][$entity] as $k=>$v){$model[$k]=$data[$v];}
		// RETORNAR MODELO PARSEADO
		return $model;
	}
	
	/*
	 * VALIDAR LOS CAMBIOS DE PERSONAL REALIZADOS DESDE TTHH
	 */
	private function validateUpdate($post,$old){
		// VALIDAR CONTRASEÑA DE PERSONAL
		if(md5($post['personal_password'])!=$old['personal_contrasenia']) $this->getJSON($this->varGlobal['MSG_PASS_ERROR']);
		// VALIDAR INGRESO DE NUEVA CONTRASEÑA
		if(isset($post['personal_new_password']) && !empty($post['personal_new_password'])){
			// VALIDAR NIVEL DE SEGURIDAD DE CONTRASEÑA
			$validPass=$this->testLevelPwd($post['personal_new_password']);
			// CONTRASEÑA DIFERENTE A LA ANTERIOR
			if($old['personal_contrasenia']==md5($post['personal_new_password'])) $this->getJSON($this->varGlobal['MSG_PASS_DEPRECATED']);
			// NO PUEDE SER EL NUMERO DE CEDULA
			elseif($old['persona_doc_identidad']==$post['personal_new_password'] || $post['persona_doc_identidad']==$post['personal_new_password']) $this->getJSON($this->varGlobal['MSG_PASS_EASY']);
			// NO PUEDE SER TAN DEBIL
			elseif(!$validPass['estado']) $this->getJSON($validPass);
			else {
				$post['personal_contrasenia']=md5($post['personal_new_password']);
				$post['new_pass']=$post['personal_new_password'];
			}
		}
		// RETORNAR DATOS
		return $post;
	}
	
	/*
	 * REGISTRAR HISTORIAL DE PUESTOS
	 */
	private function insertJobsPersonal($personalId,$job){
		// INSERTAR ID DE PERSONA EN PUESTO
		$job['personal_id']=$personalId;
		// GENERAR MODELO DE PUESTOS DE PERSONAL
		$job=$this->parseEntityModel($job,'jobModel');
		// BUSCAR LA EXISTENCIA DEL PUESTO
		$str=$this->db->selectFromView("personal_puestos","WHERE fk_personal_id={$personalId} AND fk_puesto_id={$job['fk_puesto_id']}");
		// VALIDAR SI EXISTE REGISTRO DE PUESTO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$job=array_merge($job,$this->db->findOne($str));
			// GENERAR STRING DE CONSULTA
			$str=$this->db->getSQLUpdate($job,'personal_puestos');
		}else{
			// GENERAR STRING DE CONSULTA
			$str=$this->db->getSQLInsert($job,'personal_puestos');
		}
		// EJECUTAR SENTENCIA
		return $this->db->executeTested($str);
	}
	
	/*
	 * PREPARA DATOS PARA EL ENVÍO DE CORREO ELECTRÓNICO
	 */
	private function notifyByEmail($modelId){
		// DATOS DE DEPARTAMENTO - JEFATURA - MÓDULO
		$varDirection=$this->getConfParams('TTHH');
		// DATOS DE CAPACITACIÓN
		$model=$this->db->viewById($modelId,$this->entity);
		// DATOS AUXILIARES
		$model['fecha_contrato']=$this->setFormatDate($model['personal_fecha_ingreso'],'complete');
		// LISTA DE DESTINATARIOS
		$recipientsList=array();
		// DEFINIR TEMPLATE PARA MAIL
		$template='TTHHMail';
		// VALIDAR ESTADOS INDIVIDUALES DEL REGISTRO
		if($model['personal_estado']=='EN FUNCIONES'){
			// DEFINIR ASUNTO DE MAIL
			$subject="INGRESO AL CB-GADM-SD";
		}elseif($model['personal_estado']=='PASIVO'){
			// DEFINIR ASUNTO DE MAIL
			$subject="CULMINACIÓN DE CONTRATO";
		}
		// INICIALIZAR SERVIDOR DE CORREOS
		$mailCtrl=new ctrl\mailController($template);
		// ADJUNTAR DATOS DE DEPARTAMENTO
		$mailCtrl->mergeData($varDirection);
		// ADJUNTAR DATOS DE REGISTRO
		$mailCtrl->mergeData($model);
		// LISTA DE DESTINATARIOS DEL CORREO
		$mailCtrl->destinatariosList=$recipientsList;
		// ENVIAR CORREO
		$mailCtrl->asyncMail($subject,$model['personal_correo_institucional']);
	}
	
	/*
	 * INGRESO DE PERSONAL AL CBSD
	 */
	public function insertPersonal(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// VALIDAR Y CARGAR ARCHIVO
		$this->post=$this->uploaderMng->decodeBase64ToImg($this->post,'personas',false);
		// REGISTRAR DATOS DE PERSONA
		$person=$this->requestPerson($this->post);
		
		
		// CONSULTAR SI EXISTE EL PERSONAL
		$str=$this->db->selectFromView('personal',"WHERE fk_persona_id={$person['persona_id']}");
		// CONSULTAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)<1){
			
			// ADD INDEX TO POST
			$this->post['fk_persona_id']=$person['persona_id'];
			// GENERAR CONTRASEÑA PARA LOGIN
			$this->post['personal_contrasenia']=md5($person['persona_doc_identidad']);
			// GENERAR STRING DE INGRESO DE PERSONAL Y EJECUTAR SENTENCIA
			$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
			// OBTENER ID DE REGISTRO
			$personalId=$this->db->getLastID($this->entity);
			
		}else{
			
			// 
			$staff=$this->db->findOne($str);
			// 
			$personalId=$staff['personal_id'];
			
		}
		
		// REGISTRAR HISTORIAL DE PUESTOS
		$sql=$this->insertJobsPersonal($personalId,$this->post);
		// VALIDAR Y NOTIFICAR MEDIANTE CORREO ELECTRÓNICO
		if($sql['estado']) $this->notifyByEmail($personalId);
		
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * RESTAURAR CREDENCIALES
	 */
	private function restoreAccount($data){
		// VALIDAR SI SE REQUIERE CAMBIAR LA CONTRASEÑA
		if($this->post['cambiar_pass']=='SI'){
			// VALIDAR INICIO DE SESION
			$data['personal_contrasenia']=md5($data['persona_doc_identidad']);
			// ACTUALIZAR CAMBIOS
			$sql=$this->db->executeTested($this->db->getSQLUpdate($data,$this->entity));
		}else{
			$sql=$this->setJSON("No se han efectuado cambios en la cuenta!",true);
		}
		// RETORNAR CONSULTA
		$this->getJSON($sql);
	}
	
	/*
	 * ACTUALIZAR DATOS DE DE PERSONAL AL CBSD
	 */
	public function updatePersonal(){
		// RESTAURAR CREDENCIALES
		if(isset($this->post['type']) && $this->post['type']=='restoreAccount') $this->restoreAccount($this->post);
		
		// DATOS ORIGINALES
		$old=$this->db->findById($this->post['personal_id'],$this->entity);
		
		// VALIDAR DE QUÉ MÓDULO VIENE LA ACTUALIZACIÓN
		if(app\session::getKey()=='session.tthh') $this->post=$this->validateUpdate($this->post,$old);
		
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDAR Y CARGAR ARCHIVO
		$this->post=$this->uploaderMng->decodeBase64ToImg($this->post,'personas',false);
		
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($this->post);
		// ADD INDEX TO POST
		$this->post['fk_persona_id']=$person['persona_id'];
		// ACTUALIZAR REGISTRO DE PERSONAL
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// ACTUALIZAR REGISTRO DE PERSONAL
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,'personal_puestos'));
		
		// NO REGISTRAR HISTORIAL DE PUESTOS SI LOS CAMBIOS LO HACE EL USUARIO
		if(app\session::getKey()=='session.system') $sql=$this->insertJobsPersonal($this->post['personal_id'],$this->post);
		
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * LISTADO DE USUARIOS CON PERFIL INSPECTOR
	 */
	private function requestByJob($perfilId){
		// LISTADO DE PERFILES
		$profiles=array(
			'driver'=>$this->varGlobal['TTHH_DRIVER_LIST_ID'],
			'official'=>$this->varGlobal['TTHH_OFFICIAL_LIST_ID']
		);
		$perfilId=$profiles[$perfilId];
		// CONSULTAR PERMISOS
		$where="WHERE fk_puesto_id IN ($perfilId) ORDER BY personal_nombre";
		return $this->db->findAll($this->db->selectFromView("vw_{$this->entity}",$where));
	}
	
	/*
	 * INFORMACIÓN DE PERSONAL POR USUARIO
	 */
	private function findByUser($userId){
		// DATOS DE USUARIO
		$user=$this->db->findById($userId,'usuarios');
		// CONSULTAR DATOS DE PERSONAL
		$personal=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE fk_persona_id={$user['fk_persona_id']}"));
		// RETORNAR DATOS DE PERSONAL
		return $personal;
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
// 		$data['session']=$this->db->findOne($this->db->selectFromView("vw_{$this->entity}","WHERE fk_personal_id={$personalId} AND ppersonal_estado='EN FUNCIONES'"));
		// CARGO QUE DESEMPEÑA
		$data['job']=$this->db->findAll($this->db->selectFromView('vw_personal_puestos',"WHERE fk_personal_id={$personalId} AND ppersonal_estado='EN FUNCIONES'"));
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
		$session=array();
		// DATOS DE SESIÓN
		$session=$this->profileByPersonal($sessionId);
		// DASHBOARD
		$session['dashboard']=$this->dashboardEntity($sessionId);
		// LISTADO DE VARIABLES DEL SISTEMA & PARÁMETROS 
		$session['setting']=array_merge($this->getParamsModule('TTHH'),$this->configDev());
		// INFORMACION DE ENTIDAD
		$session['info']=$this->db->findOne($this->db->selectFromView("vw_resumen_personal","WHERE personal_id={$sessionId}"));
		// MÓDULOS 
		$session['modules']=$this->string2JSON($session['setting']['MAIN_TAMPLATE_AVAILABLE'])['tthh'];
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
			
			// VALIDAR LA CANTIDAD DE REGISTROS
			if($this->db->numRows($str)>1) $str=$this->db->selectFromView("vw_{$this->entity}","{$strWhere} LIMIT 1");
			
			// DATOS DE USUARIO
			$userData=$this->db->findOne($str);
			// COMPARAR CONTRASEÑA
			if($userData['personal_contrasenia']==md5($post['usuario_password'])){
				// VALIDAR SI LA CUENTA ESTÁ ACTIVA
				if($userData['ppersonal_estado']=='EN FUNCIONES'){
					
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
		if(isset($this->get['testSession'])) $json=$this->testSession();
		// LOGIN DESDE TTHH
		elseif(isset($this->post['opt']) && $this->post['opt']=='LogIn') $this->getJSON($this->testLogin($this->post));
		// OBTENER DATOS PERSONALES DESDE - TTHH
		elseif(isset($this->post['account'])) $this->getJSON($this->getSession());
		// BUSCAR REGISTRO POR ID
		elseif(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// BUSCAR REGISTRO POR ID DE USUARIO
		elseif(isset($this->post['userId'])) $json=$this->findByUser($this->post['userId']);
		// OBTENER DATOS DE PERSONAL EN FUNCIONES
		elseif(isset($this->post['uiSelect']) && $this->post['uiSelect']=='filter') $json=$this->db->findAll($this->db->selectFromView('vw_personal',"WHERE ppersonal_estado='EN FUNCIONES' ORDER BY personal_nombre"));
		// BUSCAR REGISTROS POR PERFILES
		elseif(isset($this->post['job'])) $json=$this->requestByJob($this->post['job']);
		// BUSCAR REGISTROS DE PELOTONES
		elseif(isset($this->post['platoons'])) $json=$this->requestByPlatoon($this->post);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}

	
	
	/*
	 * LISTA DE FORMACION ACADEMICA
	 */
	private function getCompleteReport($personalId){
		// MODELO DE REPORTE
		$row=$this->db->findOne($this->db->selectFromView('vw_cv_personal',"WHERE personal_id={$personalId}"));
		// DATOS DE PERSONAL
		$personal=$this->db->findById($personalId,$this->entity);
		// CONTADOR DE HORAS DE CAPACITACION
		$row['cursosHorasTotal']=0;
		$row['cargas_total']="";
		$row['licencias_total']="";
		// IMPRIMIR DATOS AUXILIARES DE DV
		foreach($this->config['cv'] as $key=>$list){
			// MODELO AUXILIAR
			$aux="";
			// RECORRER LISTADO DE REGISTROS
			foreach($this->db->findAll($this->db->selectFromView($key,"WHERE {$list['fkKey']}={$personal[$list['fkVal']]} ORDER BY {$list['filterRow']}")) as $val){
				// TEMPLATE DE FOMRACION
				$aux.=$this->varGlobal[$list['templateRow']];
				// DATOS AUXILIARES
				if($key=='vw_formacionacademica'){
					$val['formacion_fregistro']=$this->setFormatDate($val['formacion_fregistro'],'complete');
					$val['formacion_fingreso']=$this->setFormatDate($val['formacion_fingreso'],'short');
					if($val['periodo_fin']!='LA FECHA') $val['periodo_fin']=$this->setFormatDate($val['periodo_fin'],'short');
					$aux=preg_replace('/{{FINALIZADO}}/',(($val['formacion_estado']=='FINALIZADO')?$this->varGlobal['cv.formacionAFinalizado.html']:""),$aux);
				}
				if(in_array($key,['vw_experiencias_laborales','vw_parse_personal_cargos'])){
					$aux=preg_replace('/{{FINALIZADO}}/',(($val['experiencia_estado']=='SALIDA')?$this->varGlobal['cv.experienciaLFinalizado.html']:""),$aux);
				}
				// SUMAR HORAS DE CAPACITACION
				if($key=='cursos_realizados') $row['cursosHorasTotal']+=$val['capacitacion_horas'];
				if($key=='vw_cargas_familiares' && $val['carga_estado']=='ACTIVO') $row['cargas_total'].=" {$val['carga_parentesco']} - {$val['edad']}, ";
				if($key=='vw_conductores' && $val['conductor_estado']=='ACTIVO') $row['licencias_total'].=" {$val['licencia_categoria']}, ";
				
				// REEMPLAZAR REGISTROS
				foreach($val as $k=>$v){$aux=preg_replace('/{{'.$k.'}}/',$v,$aux);}
			}
			// INSERTAR REGISTRO
			$row[$list['indexRow']]=$aux;
		}
		// RETORNAR MODELO
		return $row;
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
	    // GENERAR CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE personal_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// EXTRAER INFORMACION DE REGISTRO
			$row=$this->db->findOne($str);
			
			// INFORMACION COMPLETA DE PERSONA
			$pdfCtrl->loadSetting($this->db->getById('personas',$row['fk_persona_id']));
			
			// PARSE FECHA DE NACIMIENTO
			$row['persona_fnacimiento']=$this->setFormatDate($row['persona_fnacimiento'],'complete');
			
			// COMPLETAR REGISTROS
			$extra=$this->getCompleteReport($this->get['id']);
			// INGRESAR DATOS EXTRAS
			$pdfCtrl->loadSetting($extra);
			// CARGAR DATOS DE PEROSNAL PARA REPORTE
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}


}