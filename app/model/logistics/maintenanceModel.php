<?php namespace model\logistics;

class maintenanceModel extends trackingModel {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='mantenimientosvehiculares';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * REGISTRO DE RECURSOS DE MANTENIMIENTO
	 */
	private function insertDetails($maintenanceId,$details){
		// RETORNO DE CONSULTAS
		$sql=$this->setJSON("No se han encontrado detalles de registros...");
		// RECORRER TIPOS DE RECURSOS
		foreach($details as $k=>$resources){
			// CONSULTAR LOS REGISTROS
			if(sizeof($resources)>0){
				// RECORRER LISTADO DE RECURSOS
				foreach($resources as $k=>$v){
					// STRING PARA CONDICIÓN
					$strWhr="WHERE fk_mantenimiento_id={$maintenanceId} AND fk_recurso_id={$v['fk_recurso_id']}";
					// PREPARAR SENTENCIA SQL PARA CONSULTAR SI EL RECURSO HA SIDO INGRESADO
					$str=$this->db->selectFromView('mantenimiento_detalle',$strWhr);
					// CONSULTAR SI HA SIDO REGISTRADO EL RECURSO
					if($this->db->numRows($str)>0){
						// SENTENCIA DE ACTUALIZACIÓN
						$str="UPDATE logistica.tb_mantenimiento_detalle SET detalle_cantidad={$v['detalle_cantidad']}, detalle_estado='{$v['detalle_estado']}', detalle_observacion='{$v['detalle_observacion']}' $strWhr";
					}else{
						$v['fk_mantenimiento_id']=$maintenanceId;
						// SENTENCIA DE INGRESO
						$str=$this->db->getSQLInsert($v,'mantenimiento_detalle');
					}
					// EJECUTAR LA SENTENCIA SQL :: INGRESO / ACTUALIZAR
					$sql=$this->db->executeTested($str);
				}
			}
		}
		// RETORNO DE CONSULTA
		return $sql;
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */ 
	public function insertMaintenance(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INSERTAR DATOS DE FLOTAS
		$sql=$this->insertData($this->post,'M');
		// INGRESAR ID DE MOVIMIENTO - CASO CONTRARIO RETORNAR CONSULTA
		if($sql['estado']) $this->post['fk_flota_id']=$sql['data']['flota_id'];
		else $this->getJSON($this->db->closeTransaction($sql));
		// DATOS DE MANTENIMIENTO
		$this->post['mantenimiento_lugar']=$this->post['flota_destino'];
		// INGRESAR REGISTRO DE ABASTECIMIENTO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// ID DE MOVIMIENTO
		$maintenanceId=$this->db->getLastID($this->entity);
		// INGRESO DE DETALLE DE MANTENIMIENTO - RECURSOS
		$sql=$this->insertDetails($maintenanceId,$this->post['maintenance']);
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO
	 */
	public function updateMaintenance(){
		// RECEPCIÓN DE DATOS POST
		$post=$this->requestPost();
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INSERTAR DATOS DE FLOTAS
		$sql=$this->updateData($post);
		// ACTUALIZAR REGISTRO DE ABASTECIMIENTO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
		// INGRESO DE DETALLE DE MANTENIMIENTO - RECURSOS
		$sql=$this->insertDetails($post['mantenimiento_id'],$post['maintenance']);
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * PARSE MODEL - MODELO DE MANTENIMIENTO
	 */
	private function parseResourceModel($model,$index,$type){
		// MODELO DE RETORNO
		$aux=array(
			'fk_recurso_id'=>$model[$index],
			'detalle_cantidad'=>1,
			'detalle_estado'=>'APLICA',
			'detalle_observacion'=>'',
			'detalle_tipo'=>$type,
			'recurso_tipo'=>$model['recurso_tipo'],
			'recurso_codigo'=>$model['recurso_codigo'],
			'recurso_descripcion'=>$model['recurso_descripcion']
		);
		// RETORNAR MODELO
		return $aux;
	}

	/*
	 * CONSULTAR MODELO POR UNIDAD
	 */
	private function requestByUnit($code){
		// VALIDACIÓN DE CÓDIGO DE UNIDAD Y OBTENCIÓN DE INFORMACIÓN
		$unit=$this->validateUnitByCode($code);
		// VALIDAR QUE NO EXISTA OTRO REGISTRO DE SALIDA - FLOTAS VEHICULARES
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
		}
		// MODAL A DESPLEGAR
		$data['modal']='Mantenimientosvehiculares';
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}

	/*
	 * CONSULTAR MANTENIMIENTO POR UNIDAD
	 */
	private function requestMaintenanceByUnit($unitId){
		// BUSCAR UNIDAD POR CÓDIGO
		$str=$this->db->selectFromView('vw_unidades',"WHERE unidad_id={$unitId}");
		// BUSCAR SI EXISTE LA UNIDAD
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado la unidad");
		// OBTENER REGISTRO DE UNIDAD
		$row=$this->db->findOne($str);
		
		// DECLARACIÓN DE MODELO
		$data=array(
			'resources'=>array(
				'correctives'=>array('label'=>'LB_CORRECTIVE_MAINTENANCE','list'=>array())
			),
			'maintenance'=>array(
				'preventives'=>array(),
				'correctives'=>array()
			)
		);
		// LISTADO PARA DESCARTAR RECURSOS YA ASIGNADOS
		$decline=array();
		$updated=array();
		// CONSULTAR SI YA SE HA REGISTRADO EL MANTENIMIENTO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE fk_unidad_id={$unitId} AND flota_estado='SALIDA'");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DE MANTENIMIENTOS
			$_row=$this->db->findOne($str);
			// RECURSOS DEL MANTENIMIENTO
			foreach($this->db->findAll($this->db->selectFromView('vw_mantenimiento_detalle',"WHERE fk_mantenimiento_id={$_row['mantenimiento_id']}")) as $k=>$v){
				unset($v['fk_mantenimiento_id']);
				$updated[$v['fk_recurso_id']]=$v;
			}
		}
		// VALIDAR SI EL CARRO SE ENCUENTRA EN KM PARA MANTENIMIENTO
		$str="SELECT * from logistica.fn_verificar_mantenimientos({$row['fk_plan_id']},{$row['vehiculo_kilometraje']})";
		if($this->db->numRows($str)>0){
			// MODELO AUXILIAR
			$aux=array();
			// AGREGAR REPUESTOS PARA MANTENIMIENTOS CORRECTIVOS
			foreach($this->db->findAll($str) as $k=>$v){
				// LISTADO DE RECURSOS
				foreach($this->db->findAll($this->db->selectFromView('vw_mantenimiento_recursos',"WHERE fk_mantenimiento_id={$v['mantenimiento_id']}")) as $k_=>$_v){
					// VALIDAR SI EL TIPO DE RECURSO HA SIDO CREADO
					if(!isset($aux[$_v['recurso_tipo']])) $aux[$_v['recurso_tipo']]=array();
					// CARGAR MODELO
					if(isset($updated[$_v['fk_recurso_id']])){
						$aux[$_v['recurso_tipo']][]=$updated[$_v['fk_recurso_id']];
						$data['maintenance']['preventives'][]=$updated[$_v['fk_recurso_id']];
					} else $aux[$_v['recurso_tipo']][]=$this->parseResourceModel($_v,'fk_recurso_id','PREVENTIVO');
					// CARGAR MODELO PARA NO DUPLICADOS
					$decline[]=$_v['fk_recurso_id'];
				}
			}
			// CREAR MODELO DE MANTENIMIENTO PROGRAMADO
			$data['resources']['preventives']=array('label'=>'LB_SCHEDULED_MAINTENANCE','list'=>$aux);
		}
		// AGREGAR REPUESTOS PARA MANTENIMIENTOS CORRECTIVOS
		foreach($this->db->findAll($this->db->selectFromView('recursosmantenimiento',"WHERE recurso_estado='ACTIVO'")) as $v){
			// VERIFICAR QUE EL RECURSO NO HAYA SIDO ASIGNADO
			if(!in_array($v['recurso_id'],$decline)){
				// VALIDAR SI EL TIPO DE RECURSO HA SIDO CREADO
				if(!isset($data['resources']['correctives']['list'][$v['recurso_tipo']])) $data['resources']['correctives']['list'][$v['recurso_tipo']]=array();
				// CARGAR MODELO
				if(isset($updated[$v['recurso_id']])){
					$data['resources']['correctives']['list'][$v['recurso_tipo']][]=$updated[$v['recurso_id']];
					$data['maintenance']['correctives'][]=$updated[$v['recurso_id']];
				} else $data['resources']['correctives']['list'][$v['recurso_tipo']][]=$this->parseResourceModel($v,'recurso_id','PREVENTIVO');
			}
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 */
	private function requestById($id){
		// DATOS DE REGISTRO
		$json=$this->db->viewById($id,$this->entity);
		
		// DATOS DE UNIDAD RELACIONADA
		$json=array_merge($json,$this->db->viewById($json['fk_unidad_id'],'unidades'));
		
		// MODLEO PARA RECURSOS DE MANTENIMIENTO
		$json['maintenance']=array();
		
		// RECURSOS DEL MANTENIMIENTO
		foreach($this->db->findAll($this->db->selectFromView('vw_mantenimiento_detalle',"WHERE fk_mantenimiento_id={$id}")) as $k=>$v){
			$json['maintenance'][]=$v;
		}
		
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}
	
	/*
	 * CONSULTAR PUESTOS Y DIRECCIONES
	 */
	public function requestMaintenance(){
		// RECEPCIÓN DE DATOS POST
		$post=$this->requestPost();
		// VALIDAR TIPO DE CONSULTA
		if(isset($post['id'])) $json=$this->requestById($post['id']);
		// MODELO PARA CONSULTAR POR VEHÍCULO
		elseif(isset($post['type']) && $post['type']=='unit') $json=$this->requestByUnit($post['code']);
		// MODELO PARA CONSULTAR POR VEHÍCULO
		elseif(isset($post['type']) && $post['type']=='unitId') $json=$this->requestMaintenanceByUnit($post['unitId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}