<?php namespace model\prevention;
use model as mdl;
use api as app;

class supportModel extends mdl\personModel {

	/*
	 * VARIABLES LOCALES
	 */
	private $entity='atencionusuario';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'prevention');
	}
	
	/*
	 * INGRESO DE NUEVOS REGISTROS
	 */
	public function insertSupport(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRAR DATOS DE PERSONA
		$person=$this->requestPerson($this->post);
		// ADD INDEX TO POST
		$this->post['fk_persona_id']=$person['persona_id'];
		// GENERAR STRING DE INGRESO DE PERSONAL Y EJECUTAR SENTENCIA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZAR DATOS DE DE PERSONAL AL CBSD
	 */
	public function updateSupport(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($this->post);
		// ADD INDEX TO POST
		$this->post['fk_persona_id']=$person['persona_id'];
		// REGISTRAR USUARIO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// CERRAR CONEXIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR REGISTROS POR CÓDIGO DE PROYECTO
	 */
	private function requestByCode($code){
		// VALIDAR EL INGRESO DE CÓDIGO PARA DIRECCIÓN
		$params=explode('+',$code);
		// MODELO DE REGISTRO
		$model=$this->config['supportModel'];
		// INGRESAR DATOS DE INSPECTOR
		$model['fk_inspector_id']=app\session::get();
		$model['modal']='Atencionusuario';
		$model['fk_name']='CONSULTA GENERAL';
		$model['atencion_fecha']=$this->getFecha('dateTime');
		// GENERAR STRING PARA CONSUTAR DIRECCIÓN - PRIMER PARÁMETRO
		$model['persona_doc_identidad']=$params[0];
		$model['persona_tipo_doc']='CEDULA';
		$str=$this->db->selectFromView('personas',"WHERE UPPER(persona_doc_identidad)=UPPER('{$params[0]}')");
		// VERIFICAR SI EL PRIMER PARÁMETRO COINCIDE CON LA DIRECCIÓN
		if($this->db->numRows($str)>0){
			// OBTENER INFORMACIÓN DE PERSONA
			$person=$this->db->findOne($str);
			// INSERTAR DATOS DE PERSONA
			$model['fk_persona_id']=$person['persona_id'];
			$model['persona_doc_identidad']=$person['persona_doc_identidad'];
			$model['persona_tipo_doc']=$person['persona_tipo_doc'];
			$model['persona_nombres']=$person['persona_nombres'];
			$model['persona_apellidos']=$person['persona_apellidos'];
		}
		// BUSCAR PROYECTO
		if(sizeof($params)>1){
			// RECORRER LAS ENTIDADES DE CODIGOS
			foreach($this->config['supportCode'] as $entity=>$attr){
				// GENERAR CÓDIGO DE CONSULTA DE CADA ENTIDAD
				$str=$this->db->selectFromView("vw_{$entity}","WHERE UPPER({$attr['entityCode']})=UPPER('{$params[1]}')");
				// VALIDAR SI EXISTE EL REGISTRO
				if($this->db->numRows($str)>0){
					// OBTENER DATOS DE CHECKING
					$row=$this->db->findOne($str);
					// INSERTAR DATOS DE ENTIDAD EN MODELO DE ATENCION
					$model['fk_id']=$row[$attr['entityId']];
					$model['fk_tb']=$entity;
					$model['fk_name']=$attr['entityName'];
					$model['fk_codigo']=$row[$attr['entityCode']];
					// RETORNAR DATOS DE CONSULTA
					return array_merge($row,$model);
				}
			}
		}
		// RETORNAR CONSULTA
		return $model;
	}
	
	/*
	 * INGRESO DE NUEVOS REGISTROS
	 */
	public function requestSupport(){
		// VERIFICAR SESSION
		if(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}