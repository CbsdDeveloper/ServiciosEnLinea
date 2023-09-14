<?php namespace model\logistics;
use model\subjefature as subj; 

class supplyModel extends subj\trackingModel {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='abastecimiento';
	private $supplyCode;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
		// INSERTAR CÓDIGO INSTITUCIONAL DE ABASTECIMIENTO
		$code=$this->db->findOne($this->db->selectFromView('codigosinstitucionales',"WHERE codigo_clave='{$this->varGlobal['VAR_SUPPLYING_CODE']}'"));
		$this->supplyCode=$code['codigo_id'];
	}
	
	/*
	 * NÚMERO DE ORDEN DE ABASTECIMIENTO POR UNIDAD
	 */
	private function getNextOrder(){
		// CUSTOM TABLE
		$tb=$this->db->setCustomTable($this->entity,'MAX(abastecimiento_orden) id','logistica');
		// GENERAR CONSULTA
		extract($this->db->findOne($this->db->getSQLSelect($tb)));
		return ($id==null?0:$id)+1;
	}
	
	/*
	 * INGRESAR FLOTA
	 */ 
	public function insertSupply(){
		// INSERTAR CÓDIGO INSTITUCIONAL DE ABASTECIMIENTO
		$this->post['fk_codigo_id']=$this->supplyCode;
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INSERTAR DATOS DE FLOTAS
		$sql=$this->insertData($this->post,'A');
		// INGRESAR ID DE MOVIMIENTO - CASO CONTRARIO RETORNAR CONSULTA
		if($sql['estado']) $this->post['fk_flota_id']=$sql['data']['flota_id'];
		else $this->getJSON($this->db->closeTransaction($sql));
		// VERIFICAR/GENERAR NÚMERO DE ORDEN DE ABASTECIMIENTO
		if($this->post['abastecimiento_orden']==0) $this->post['abastecimiento_orden']=$this->getNextOrder();
		// INGRESAR REGISTRO DE ABASTECIMIENTO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZACIÓN DE PROCESO - FLOTA
	 */ 
	public function updateSupply(){
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
		// LISTA Y PRECIOS DE COMBUSTIBLES
		$data['data']['gasList']=$this->string2JSON($this->varGlobal['SUPPLYING_GAS_LIST']);
		$data['data']['gasPrice']=$this->string2JSON($this->varGlobal['SUPPLYING_GAS_PRICE']);
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
			$data['data']['flota_destino']=$this->varGlobal['SUPPLYING_GAS_STATION'];
			$data['data']['abastecimiento_orden']=$this->getNextOrder();
		}
		// MODAL A DESPLEGAR
		$data['modal']='Abastecimiento';
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR USUARIOS
	 */
	public function requestSupply(){
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