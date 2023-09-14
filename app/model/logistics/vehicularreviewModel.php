<?php namespace model\logistics;

class vehicularreviewModel extends trackingModel {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='revisionvehicular';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESAR FLOTA
	 */ 
	public function insertReview(){
		// RECEPCIÓN DE DATOS POST
		$post=$this->requestPost();
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INSERTAR DATOS DE FLOTAS
		$sql=$this->insertData($post,'R');
		// INGRESAR ID DE MOVIMIENTO - CASO CONTRARIO RETORNAR CONSULTA
		if($sql['estado']) $post['fk_flota_id']=$sql['data']['flota_id'];
		else $this->getJSON($this->db->closeTransaction($sql));
		// INGRESAR REGISTRO DE ABASTECIMIENTO
		$sql=$this->db->executeTested($this->db->getSQLInsert($post,$this->entity));
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZACIÓN DE PROCESO - FLOTA
	 */ 
	public function updateReview(){
		// RECEPCIÓN DE DATOS POST
		$post=$this->requestPost();
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INSERTAR DATOS DE FLOTAS
		$sql=$this->updateData($post);
		// ACTUALIZAR REGISTRO DE ABASTECIMIENTO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}


	/*
	 * CONSULTAR MODELO POR UNIDAD
	 */
	private function requestByUnit($code){
		// VALIDACIÓN DE CÓDIGO DE UNIDAD Y OBTENCIÓN DE INFORMACIÓN
		$unit=$this->validateUnitByCode($code);
		// VALIDAR QUE NO EXISTA OTRO REGISTRO DE SALIDA - TRACKING
		$str=$this->validateTrackingByCode($unit,$this->entity);
		// DECLARACIÓN Y ASIGNACIÓN DE INFORMACIÓN DE UNIDAD AL MODELO DE RETORNO
		$data=array('data'=>$unit);
		// VALIDAR SI EXISTE UN MOVIMIENTO REGISTRADO
		if($this->db->numRows($str)>0){
			// REGISTRO DE MOVIMIENTO
			$tracking=$this->db->findOne($str);
			// AGREGAR DATOS DE MOVIMIENTO A DATOS DE UNIDAD
			$data['data']=array_merge($data['data'],$tracking);
			// ESTADO DE MOVIMIENTO
			$data['data']['flota_estado']='ESTACION';
			// VALIDAR SI LA ESTACION DE LLEGADA HA SIDO SETTEADA
			if($data['data']['flota_arribo_estacion']==null) $data['data']['flota_arribo_estacion']=$unit['fk_estacion_id'];
		}else{
			// OPCIÓN DE EDICIÓN O NUEVO
			$data['data']['edit']=false;
			$data['data']['fk_unidad_id']=$unit['unidad_id'];
			$data['data']['flota_salida_estacion']=$unit['fk_estacion_id'];
			$data['data']['flota_destino']=$this->varGlobal['VEHICULAR_REVIEW_AGENCY'];
		}
		// MODAL A DESPLEGAR
		$data['modal']='Revisionvehicular';
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR USUARIOS
	 */
	public function requestReview(){
		// RECEPCIÓN DE DATOS POST
		$post=$this->requestPost();
		// VERIFICAR 
		if(isset($post['id'])) $json=$this->requestById($post['id']);
		// MODELO PARA CONSULTAR POR VEHÍCULO
		if(isset($post['type']) && $post['type']=='unit') $json=$this->requestByUnit($post['code']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}