<?php namespace model\logistics;
use model as mdl;

class checkingModel extends mdl\personModel {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='checking_externos';	
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE LOGÍSTICA
		$this->config=$this->getConfig(true,'logistics');
	}
	
	
	/*
	 * INGRESO DE REGISTROS
	 */
	public function insertChecking(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRAR DATOS DE PERSONA
		$person=$this->requestPerson($this->post);
		// ADD INDEX TO POST
		$this->post['fk_externo_id']=$person['persona_id'];
		// GENERAR REGISTRO PARA ENTREGA DE DOCUMENTOS - NO REGISTRA INGRESO A BOMBEROS
		if($this->post['checking_motivo']=='ENTREGA DE DOCUMENTOS'){
			// GENERAR ESTADO COMO SALIDA
			$this->post['checking_estado']='SIN INGRESO';
			$this->post['checking_salida']=$this->post['checking_ingreso'];
			// VALIDAR LA DIRECCIÓN A LA QUE SE VA A ENVIAR
			if($this->post['fk_direccion_id']==1){
				// INICIALIZAR SERVIDOR DE SMS
				// $mailCtrl=new ctrl\smsController();
				// ADJUNTAR DATOS DE DEPARTAMENTO - 39 INTRO.
				// $mailCtrl->sendSMS("CB-GADM-SD: NUEVO DOCUMENTO RECIBIDO - {$this->post['checking_asunto']}",'+593980098156');
			}
		}
		// OBTENER DATOS DE DIRECCIÓN
		$leadership=$this->db->findById($this->post['fk_direccion_id'],'direcciones');
		// INSERTAR NOMBRE DE DIRECCIÓN EN MODELO
		$this->post['checking_direccion']=$leadership['direccion_nombre'];
		// VALIDAR Y CARGAR ARCHIVO
		$this->post=$this->uploaderMng->uploadFileService($this->post,$this->entity);
		// GENERAR STRING DE INGRESO DE PERSONAL Y EJECUTAR SENTENCIA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		$sql['data']=array('id'=>$this->db->getLastID($this->entity));
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZAR DATOS DE DE PERSONAL AL CBSD
	 */
	public function updateChecking(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($this->post);
		// ADD INDEX TO POST
		$this->post['fk_persona_id']=$person['persona_id'];
		// VALIDAR Y CARGAR ARCHIVO
		$this->post=$this->uploaderMng->uploadFileService($this->post,$this->entity);
		// REGISTRAR USUARIO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * BUSCAR REGISTRO POR CÓDIGO
	 */
	private function requestByCode($code){
		// VALIDAR PRIVILEGIO PARA REALIZAR EL CHEKING
		if(!in_array(7201,$this->getAccesRol())) $this->getJSON($this->varGlobal['CHECKING_NOT_ALLOW']);
		// CONSULTAR EL CÓDIGO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(checking_codigo)=UPPER('{$code}')");
		// VALIDAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			// PRIVILEGIO PARA REALIZAR CHECKOUT
			if(!in_array(72011,$this->getAccesRol())) $this->getJSON($this->varGlobal['CHECKOUT_NOT_ALLOW']);
			// OBTENER DATOS DE CHECKING
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DEL REGISTRO
			if($row['checking_estado']!='EN ESTACION') $this->getJSON("Ya se ha registrado esta solicitud!.");
			// ACTUALIZAR DATOS DE SALIDA
			$row['checking_estado']='SALIDA';
			$row['checking_salida']=$this->getFecha('dateTime');
			// RETORNAR DATOS DE CONSULTA
			return $row;
		}
		// VALIDAR EL INGRESO DE CÓDIGO PARA DIRECCIÓN
		$params=explode('+',$code);
		// MODELO DE REGISTRO
		$model=$this->config['checkingModel'];
		// GENERAR STRING PARA CONSUTAR DIRECCIÓN - PRIMER PARÁMETRO
		$str=$this->db->selectFromView('direcciones',"WHERE UPPER(direccion_codigo)=UPPER('{$params[0]}')");
		// VERIFICAR SI EL PRIMER PARÁMETRO COINCIDE CON LA DIRECCIÓN 
		if($this->db->numRows($str)>0){
			// OBTENER INFORMACIÓN DE DIRECCIÓN
			$leadership=$this->db->findOne($str);
			// INSERTAR ID DE DIRECCIÓN
			$model['fk_direccion_id']=$leadership['direccion_id'];
		}
		// VALDIAR SI SE HA INGRESADO LA CÉDULA
		if(sizeof($params)>1){
			// OBTENER INFORMACIÓN DE PERSONA
			$str=$this->db->selectFromView('personas',"WHERE UPPER(persona_doc_identidad)=UPPER('{$params[1]}')");
			// INGRESAR DATOS SI EXISTE REGISTRO
			if($this->db->numRows($str)>0){
				// DATOS DE PERSONA
				$row=$this->db->findOne($str);
				// REGISTRAR PERSONA
				$model['fk_externo_id']=$row['persona_id'];
				$model['persona_nombres']=$row['persona_nombres'];
				$model['persona_apellidos']=$row['persona_apellidos'];
				$model['persona_doc_identidad']=$row['persona_doc_identidad'];
				$model['persona_tipo_doc']=$row['persona_tipo_doc'];
			}
		}
		// VALDIAR SI SE HA INGRESADO EL ASUNTO
		if(sizeof($params)>2){
			$model['checking_asunto']=$params[2];
		}
		// REGISTRAR HORA DE INGRESO
		$model['checking_ingreso']=$this->getFecha('dateTime');
		// RETORNAR CONSULTA
		return $model;
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestChecking(){
		// VERIFICAR
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// SESSION DE PERSONAL
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}

}