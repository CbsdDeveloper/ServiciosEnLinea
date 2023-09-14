<?php namespace model\subjefature;


class trackingModel extends binnacleModel {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='flotasvehiculares';
	private $config;
	protected $unitModel;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// METODO CONSTRCUTOR
		parent::__construct();
		// PARÁMETROS DE LOGÍSTICA
		$this->config=$this->getConfig(true,'logistics');
	}
	
	
	/*
	 * ÚLTIMO KILOMETRAJE DE UNIDAD
	 */
	protected function getLastRecords($unitId){
		// DATOS DEL ÚLTIMO MIVIMIENTO REGISTRADO
		$strOld=$this->db->selectFromView($this->entity,"WHERE fk_unidad_id={$unitId} ORDER BY flota_id DESC");
		// SI EXISTE ALGÚN REGISTRO OBTENER EL KM DE ENTRADA, SINO INICIAR EN 0
		if($this->db->numRows($strOld)>0){
			// OBTENER DATOS DE MOVIMIENTO
			$row=$this->db->findOne($strOld);
			// KILOMETRAJE DE SALIDA
			return $row['flota_arribo_km'];
		}else{
			return 0;
		}
	}
	
	/*
	 * OBTENER INFORMACIÓN DE UNIDAD PARA REGISTRO
	 */
	protected function validateUnitByCode($code){
		// BUSCAR UNIDAD POR CÓDIGO
		$str=$this->db->selectFromView('vw_unidades',"WHERE UPPER(unidad_nombre)=UPPER('{$code}')");
		// BUSCAR SI EXISTE LA UNIDAD
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado la unidad: <b>{$code}</b>");
		// OBTENER DATOS DE UNIDAD
		$this->unitModel=$this->db->findOne($str);
		// RETORNAR DATOS DE UNIDAD
		return $this->unitModel;
	}
	
	/*
	 * VALIDAR QUE NO EXISTAN OTROS REGISTROS DE SALIDA
	 */
	protected function validateTrackingByCode($unit,$entity){
		// LISTADO DE VALIDACIONES POR TIPO DE MOVIMIENTOS
		$tracking=$this->config['tracking'][$entity];
		// STRING PARA CONDICIONAR CONSULTA
		$strWhr="WHERE (fk_unidad_id={$unit['unidad_id']} AND flota_estado='SALIDA')";
		// RETORNAR CONSULTA
		return $this->db->selectFromView("vw_{$entity}","{$strWhr}");
		
		// RECORRER LISTADO DE VALIDACIONES
		foreach($tracking['entities'] as $k=>$v){
			// SENTENCIA DE CONSULTA
			$str=$this->db->selectFromView("vw_{$this->entity}","{$strWhr} {$v}");
			// VALIDAR SI EXISTE UN MOVIMIENTO REGISTRADO QUE NO SE RELACIONE CON EL REGISTRO ACTUAL
			if($this->db->numRows($str)>0){
				// DATOS DE ENTIDAD
				$row=$this->db->findOne($str);
				// LABEL PARA MENSAJE
				$row['label']=$this->config['labels'][$k];
				// MENSAJE DE RETORNO
				$this->getJSON($this->msgReplace($this->varGlobal['TRACKING_BUSY_MSG'],$row));
			}
		}
		// RETORNAR CONSULTA
		return $this->db->selectFromView("vw_{$entity}","{$strWhr}");
	}
	
	
	/*
	 * REGISTRAR EL INGRESO DE LA ORDEN DE MOVILIZACIÓN
	 */
	private function closeMovilizationOrder($data){
		// STRING PARA CONSULTAR SI DESTINO ES UNA ESTACION
		$str=$this->db->selectFromView('ordenesmovilizacion',"WHERE fk_unidad_id={$data['fk_unidad_id']} AND orden_estado='SALIDA'");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DE MODELO
			$order=$this->db->findOne($str);
			// GENERAR MODELO DE FLOTA VEHICULAR
			$order['orden_estado']='ESTACION';
			$order['fk_personal_id']=$this->getStaffIdBySession();
			$order['orden_kilometraje_entrada']=$data['flota_arribo_km'];
			$order['orden_hora_entrada_tipo']=$data['flota_arribo_tipo'];
			$order['orden_hora_entrada']=$data['flota_arribo_hora'];
			// ACTUALIZAR DATOS DE REGISTRO
			$this->db->executeTested($this->db->getSQLUpdate($order,'ordenesmovilizacion'));
		}
		// RETORNAR CONSULTA POR DEFECTO 
		return $this->setJSON("Ok",true);
	}
	
	/*
	 * INSERTAR BITÁCORA
	 */
	private function insertBinnacle($post,$io='SALIDA'){
		// DATOS DE UNIDAD
		$unit=$this->db->viewById($post['fk_unidad_id'],'unidades');
		
		// LISTA DE PASAJEROS
		$list=(count($post['passengers'])<1)?"NA":"";
		// RECORRER LISTA DE PASAJEROS
		foreach($post['passengers'] as $v){
			// DATOS DE PERSONAL
			$personal=$this->db->findOne($this->db->selectFromView('vw_basic_personal',"WHERE personal_id={$v}"));
			// CONCATENAR NOMBRES
			$list.="{$personal['personal_nombre']} ({$personal['persona_doc_identidad']}); ";
		}
		
		// DATOS PARA BITACORA
		if($io=='SALIDA'){
			// DATOS DE CÓDIGOS INSTITUCIONALES
			$code=$this->db->findById($post['fk_codigo_id'],'codigosinstitucionales');
			// DATOS DE OPERADOR DE SALIDA
			$driver=$this->db->viewById($post['flota_salida_conductor'],'conductores');
			// DATOS DE ESTACION DE SALIDA
			$station=$this->db->findById($post['flota_salida_estacion'],'estaciones');
			// DESCRIPCIÓN DE LIBRO
			$detail="SALIDA DE {$station['estacion_nombre']} // DESTINO: {$post['flota_destino']} // CLAVE: {$code['codigo_clave']} ({$code['codigo_detalle']})";
			// KILOMETRAJE
			$km=$post['flota_salida_km'];
			// FECHA DE REGISTRO
			$binnacleDate=$post['flota_salida_hora'];
		}else{
			// DATOS DE OPERADOR DE INGRESO
			$driver=$this->db->viewById($post['flota_arribo_conductor'],'conductores');
			// DATOS DE ESTACION DE INGRESO
			$station=$this->db->findById($post['flota_arribo_estacion'],'estaciones');
			// DESCRIPCIÓN DE LIBRO
			$detail="INGRESA A {$station['estacion_nombre']}";
			// KILOMETRAJE
			$km=$post['flota_arribo_km'];
			// FECHA DE REGISTRO
			$binnacleDate=$post['flota_arribo_hora'];
		}
		
		// CODIGO DE SALIDA
		$binnacleCode="UNIDAD: {$unit['unidad_nombre']} ({$unit['vehiculo_placa']}) // {$detail} // KM: {$km} // OPERADOR: {$driver['conductor']} ({$driver['persona_doc_identidad']}) // PASAJEROS: {$list}";
		
		// MODELO DE BITACORA
		$model=array(
			'fk_entidad'=>$this->entity,
			'fk_id'=>$post['flota_id'],
			'bitacora_fecha_tipo'=>$post['flota_salida_tipo'],
			'bitacora_fecha'=>$binnacleDate,
			'bitacora_detalle'=>$binnacleCode
		);
		
		// REGISTRAR BITACORA
		return $this->insertHierarchyBinnacle($model,$model['bitacora_fecha']);
	}
	
	/*
	 * REGISTRAR PASAJEROS: SALIDA/ARRIBO
	 */
	private function registerPassengers($trackingId,$passengerList,$io){
		// VARIABLE POR DEFECTO DE RETORNO
		$sql=$this->setJSON("No se registró ningun pasajero",true);
		// RECORRER LISTADO DE PASAJEROS
		foreach($passengerList as $v){
			// MODELO AUXILIAR DE PASAJEROS
			$aux=array('fk_flota_id'=>$trackingId,'fk_personal_id'=>$v,'pasajero_estado'=>$io);
			// CONSULTAR SI YA SE HA REGISTRADO EL PASAJERO
			$str=$this->db->selectFromView('pasajeros',"WHERE fk_flota_id={$trackingId} AND fk_personal_id={$v}");
			// CONSULTAR EL NUMERO DE REGISTRO
			if($this->db->numRows($str)>0){
				// OBTENER PASAJER
				$passenger=$this->db->findOne($str);
				// ADJUNTAR NUEVO ESTADO: SALIDA ARRIBO
				$passenger['pasajero_estado'].=" Y {$aux['pasajero_estado']}";
				// ACTUALIZAR DATOS DE PASAJERO
				$str=$this->db->getSQLUpdate($passenger,'pasajeros');
			}else{
				// SENTENCIA PARA REGISTRAR PASAJEROS
				$str=$this->db->getSQLInsert($aux,'pasajeros');
			}
			// EJECUTAR CONSULTA
			$sql=$this->db->executeSingle($str);
			// VALIDAR EL INGRESO DE PASAJEROS
			if(!$sql['estado']){
				$sql['mensaje'].="<br>Error al registrar pasajeros.";
				return $sql;
			}
		}
		// RETORNAR DATOS DE CONSULTA
		return $sql;
	}
	
	/*
	 * GENERAR CÓDIGO DE MOVIMIENTO DE UNIDAD
	 */
	private function getNumberTracking($data,$type){
		// ALMACENAMIENTO DE AÑO EN CURSO
		$year=$this->getFecha('y');
		// SUPRIMIR EL SIMBOLO "-" DEL NOMBRE DE UNIDAD
		$unitName=str_replace('-','',$data['unidad_nombre']);
		// NÚMERO DE MOVIMIENTO AL AÑO
		$number=$this->db->numRows($this->db->selectFromView($this->entity,"WHERE fk_unidad_id={$data['unidad_id']} AND (date_part('year',flota_salida_hora)=date_part('year',CURRENT_DATE))"))+1;
		// PARSE DE NÚMERO DE SERIE
		$number=str_pad($number,3,'0',STR_PAD_LEFT);
		// RETORNAR NUMERO DE MOVIMIENTO
		return "{$data['estacion_nombre']}-{$unitName}-{$year}{$type}{$number}";
	}
	
	/*
	 * INGRESAR REGISTRO DE FLOTAS
	 */ 
	protected function insertData($post,$type='F'){
		// GENERAR NÚMERO DE MOVIMIENTO
		$post['flota_codigo']=$this->getNumberTracking($post,$type);
		
		// REGISTRO DE SALIDA - HORARIO
		if($post['flota_salida_tipo']=='SISTEMA') $post['flota_salida_hora']=$this->getFecha('dateTime');
		// OBTENER ID DE PERSONAL CON SESIÓN ACTIVA
		$post['flota_salida_usuario']=$this->getStaffIdBySession();
		
		// VALIDAR EL REGISTRO DE UNA ORDEN DE MOVILIZACIÓN
		$str=$this->db->selectFromView($this->entity,"WHERE fk_unidad_id={$post['unidad_id']} AND flota_estado='PREVALIDACION'");
		// VALIDAR REGISTRO
		if($this->db->numRows($str)>0){
			// ACTUALIZAR ESTADO
			$post['flota_estado']='SALIDA';
			// INGRESAR MOVIMIENTO DE UNIDAD
			$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
			// ID DE MOVIMIENTO
			$trackingId=$post['flota_id'];
		}else{
			// INGRESAR MOVIMIENTO DE UNIDAD
			$this->db->executeTested($this->db->getSQLInsert($post,$this->entity));
			// ID DE MOVIMIENTO
			$trackingId=$this->db->getLastID($this->entity);
		}
		
		// INSERTAR ID DE MOVIMIENTO EN FORMULARIO
		$post['flota_id']=$trackingId;
		
		// REGISTRAR PASAJEROS
		$this->registerPassengers($trackingId,$post['passengers'],'SALIDA');
		
		// VALIDAR SI LA SESION ESTA DENTRO DE UN DISTRIBUTIVO
		if(!$this->vaidateSsessionInPlatoon()) return $this->setJSON("Datos guardados correctamente",true);
		
		// REGISTRO EN BITACORA
		return $this->insertBinnacle($post);
	}
	
	/*
	 * ACTUALIZAR REGISTRO DE FLOTA
	 */
	protected function updateData($post){
		// PARSE FORMAT DATETIME LLEGADA
		if($post['flota_estado']=='ESTACION'){
			// OBTENER ID DE PERSONAL CON SESIÓN ACTIVA
			$post['flota_arribo_usuario']=$this->getStaffIdBySession();
		}
		// REGISTRO DE ARRIBO - HORARIO
		if($post['flota_arribo_tipo']=='SISTEMA') $post['flota_arribo_hora']=$this->getFecha('dateTime');
		
		// REGISTRAR ENTRADA DE UNIDAD
		$this->closeMovilizationOrder($post);
		
		// INGRESAR MOVIMIENTO DE UNIDAD
		$this->db->executeTested($this->db->getSQLUpdate($post,$this->entity));
		
		// OBTENER EL ID DE MOVIMIENTO Y REGISTRAR PASAJEROS A BORDO
		$this->registerPassengers($post['flota_id'],$post['passengers'],'ARRIBO');
		
		// VALIDAR SI LA SESION ESTA DENTRO DE UN DISTRIBUTIVO
		if(!$this->vaidateSsessionInPlatoon()) return $this->setJSON("Datos guardados correctamente",true);
		
		// REGISTRO EN BITACORA
		return $this->insertBinnacle($post,'ARRIBO');
	}
	
	
	/*
	 * INGRESAR REGISTRO DE MOVILIZACIÓN DE VEHICULOS
	 */ 
	public function insertTracking(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// REGISTRAR MOVIMIENTO DE UNIDAD
		$sql=$this->insertData($this->post,'F');
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZACIÓN DE PROCESO - FLOTA
	 */ 
	public function updateTracking(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRAR MOVIMIENTO DE UNIDAD
		$sql=$this->updateData($this->post);
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR MODELO POR UNIDAD
	 */
	private function requestByUnit($code){
		// VALIDAR EL INGRESO DE CÓDIGO { UNIDAD + CODIGO }
		$params=explode(' ',$code);
		
		// VALIDACIÓN DE CÓDIGO DE UNIDAD Y OBTENCIÓN DE INFORMACIÓN DE UNIDAD
		$unit=$this->validateUnitByCode($params[0]);
		// DECLARACIÓN Y ASIGNACIÓN DE INFORMACIÓN DE UNIDAD AL MODELO DE RETORNO
		$data=array('data'=>$unit);
		
		// STRING DE VALIDACION
		$str=$this->db->selectFromView($this->entity,"WHERE fk_unidad_id={$data['data']['unidad_id']} AND flota_estado='PREVALIDACION'");
		// VALIDAR SI EXISTE UNA SALIDA RELACIONADA CON ORDENES DE MOVILIZACIÓN
		if($this->db->numRows($str)>0){
			// REGISTRO DE MOVIMIENTO
			$tracking=$this->db->findOne($str);
			// AGREGAR DATOS DE MOVIMIENTO A DATOS DE UNIDAD
			$data['data']=array_merge($data['data'],$tracking);
			// OPCIÓN DE EDICIÓN O NUEVO
			$data['data']['edit']=false;
			$data['data']['passengers']=array();
		}
		
		// VALDIAR CODIGO DE SALIDA
		if(sizeof($params)>1){
			// OBTENER INFORMACIÓN DE PERSONA
			$str=$this->db->selectFromView('codigosinstitucionales',"WHERE UPPER(codigo_clave)=UPPER('{$params[1]}')");
			// INGRESAR DATOS SI EXISTE REGISTRO
			if($this->db->numRows($str)>0){
				// DATOS DE PERSONA
				$row=$this->db->findOne($str);
				// INGRESAR CÓDIGO INSTITUCIONAL
				$data['data']['fk_codigo_id']=$row['codigo_id'];
			}
		}
		
		// VALIDAR QUE NO EXISTA OTRO REGISTRO DE SALIDA - TRACKING
		$str=$this->validateTrackingByCode($unit,$this->entity);
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
			// KM D EINGRESO POR DEFECTO
			$data['data']['flota_arribo_km']=($tracking['flota_arribo_km']<1)?$tracking['flota_salida_km']:$tracking['flota_arribo_km'];
		}else{
			// OPCIÓN DE EDICIÓN O NUEVO
			$data['data']['edit']=false;
			$data['data']['passengers']=array();
			$data['data']['fk_unidad_id']=$unit['unidad_id'];
			$data['data']['flota_salida_estacion']=$unit['fk_estacion_id'];
			$data['data']['flota_salida_km']=$this->getLastRecords($unit['unidad_id']);
		}
		
		// MODAL A DESPLEGAR
		$data['modal']='Flotasvehiculares';
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 */
	private function requestById($trackingId){
		$json=$this->db->viewById($trackingId,$this->entity);
		// LISTAR PERSONAL RELACIONADO CON EL EVENTO
		foreach($this->db->findAll($this->db->selectFromView('vw_pasajeros',"WHERE fk_flota_id={$json['flota_id']} ORDER BY pasajero")) as $val){
			$json['passengers'][]=$val['fk_personal_id'];
			$json['passengersList'][]=$val;
		}
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}

	/*
	 * CONSULTAR LISTA DE PASAJEROS
	 */
	private function requestPassengers(){
		// MODELO DE DATOS PARA PRESENTAR
		$data=array(
			'selected'=>array(),
			'passengers'=>array()
		);
		// LISTADO DE PERSONAL
		$data['passengers']=$this->db->findAll($this->db->selectFromView('vw_personal',"WHERE ppersonal_estado='EN FUNCIONES' ORDER BY personal_nombre"));
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * FLOTAS VEHICULARES DE PARTES
	 */
	private function trackingByPart($partId){
		// MODELO DE CONSULTA
		$model=array(
			'selected'=>array(),
			'trackingList'=>array()
		);
		
		// DETALLES DE PARTE
		$part=$this->db->findById($partId,'partes');
		
		// FECHAS DE BUSQUEDA
		$minDate=$this->addDays($part['parte_fecha'],1,'-');
		$maxDate=$part['parte_fecha'];
		
		// STRING PARA CONSULTAR FLOTAS DE UNA FECHA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE (flota_salida_hora::date BETWEEN '{$minDate}'::date AND '{$maxDate}'::date) ORDER BY flota_salida_hora DESC");
		// GENERAR MODELO 
		foreach($this->db->findAll($str) as $v){
			$model['trackingList'][$v['flota_id']]=$v;
		}
		
		// CONSULTAR REGISTROS EXISTENTES DE FLOTAS
		foreach($this->db->findAll($this->db->selectFromView('partes_flotas',"WHERE fk_parte_id={$partId}")) as $v){
			$model['selected'][]=$v['fk_flota_id'];
		}
		
		// RETORNAR CONSULTA
		return $model;
	}
	
	/*
	 * CONSULTAR USUARIOS
	 */
	public function requestTracking(){
		// VERIFICAR
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// MODELO PARA CONSULTAR POR VEHÍCULO
		elseif(isset($this->post['type']) && $this->post['type']=='unit') $json=$this->requestByUnit($this->post['code']);
		// LISTA DE PASAJEROS
		elseif(isset($this->post['type']) && $this->post['type']=='passengers') $json=$this->requestPassengers();
		// FLOTAS VEICULARES PARA PARTES
		elseif(isset($this->post['partId'])) $json=$this->trackingByPart($this->post['partId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}