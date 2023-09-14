<?php namespace model\logistics;
use model as mdl;
use controller as ctrl;

class unitModel extends mdl\vehicleModel {
	
	private $entity='unidades';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}

	/*
	 * PROCESAR DATOS
	 * - RECIBIR DATOS DE ARCHIVO EXCEL
	 * - CONSULTAR/INGRESAR DATOS DE PROPIETARIO
	 */
	private function processList($pathFile){
		// INSTANCIA DE CONTROLADOR
		$myExcel=new ctrl\xlsController();
		// OBTNER DATOS DE CELDAS
		$data=$myExcel->getCellValue($this->post,$pathFile,$this->entity);
		// VALIDAR PLACAS DE UNIDADES
		$this->validateTypePlate=false;
		// VARIABLES DE TRANSACCIÓN
		$succes=0;
		$insert=0;
		$update=0;
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// LISTA DE ESTACIONES
		foreach($this->db->findAll($this->db->selectFromView('estaciones')) as $station){ $stationsList[$station['estacion_nombre']]=$station['estacion_id']; }
		// RECORRER LISTA DE DATOS
		foreach($data as $key=>$val){
			// MODELO DE UNIDAD
			$unit=array();
			// PARSE DATA
			$val['vehiculo_marca']=strtoupper($val['vehiculo_marca']);
			$val['estacion_nombre']=strtoupper($val['estacion_nombre']);
			// CONSULTAR/INGRESAR MARCAS DE VEHÍCULOS
			if($this->db->numRows($this->db->selectFromView('brands',"WHERE UPPER(brand_title)=UPPER('{$val['vehiculo_marca']}')"))<1){
				// GENERAR MODELO DE MARCA
				$brand=array('brand_title'=>$val['vehiculo_marca'],'brand_code'=>$val['vehiculo_marca']);
				// INGRESAR MARCA DE VEHICULO
				$sql=$this->db->executeTested($this->db->getSQLInsert($brand,'brands'));
			}
			// INGRESAR VEHÍCULO O ACTUALIZAR DATOS DEL MISMO
			$vehiculo=$this->testedInsertVehicule($val);
			// CONSULTAR ESTACIÓN DE UNIDAD
			$unit['fk_estacion_id']=(isset($stationsList[$val['estacion_nombre']]))?$stationsList[$val['estacion_nombre']]:1;
			// OBTENER ID DEL VEHICULO
			$unit['fk_vehiculo_id']=$vehiculo['vehiculo_id'];
			// MODELO DE UNIDAD
			$unit['unidad_nombre']=$val['unidad_nombre'];
			// CONSULTAR/INGRESAR/ACTUALIZAR UNIDAD
			$str=$this->db->selectFromView($this->entity,"WHERE UPPER(unidad_nombre)=UPPER('{$unit['unidad_nombre']}')");
			// VALIDAR SI EXISTE EL REGISTRO
			if($this->db->numRows($str)>0){
				$update++;
				$sql=$this->db->executeSingle($this->db->getSQLUpdate(array_merge($unit,$this->db->findOne($str)),$this->entity));
			} else {
				$insert++;
				$sql=$this->db->executeSingle($this->db->getSQLInsert($unit,$this->entity));
			}
			// VALIDAR REGISTRO
			if(!$sql['estado']){
				// ADJUNTAR MENSAJE DE ERROR
				$sql['mensaje'].="<br>Error provocado en registro con placa -> {$vehiculo['vehiculo_placa']}";
				// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
				return $this->db->closeTransaction($sql);
			}
		}	
		// ADJUNTAR ESTADO DE CONSULTA
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: {$insert}  <br>Datos actualizados: {$update}";
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * LISTADO DE PERSONAL
	 * - SUBIR ARCHIVO EXCEL CON EL LISTADO DE VEHICULOS
	 */
	public function uploadList(){
		// SUBIR ARCHIVO
		$config=$this->uploaderMng->config['entityConfig'][$this->entity];
		// PARAMETROS DE CONSULTA
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA DE ARCHIVO
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// OBTENER ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['unidad_nombre']}";
		// COMPROBAR SI EL ARCHIVO SE HA SUBIDO CORRECTAMENTE
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// PROCEDER A INGRESAR DATOS
		$this->getJSON($this->processList($pathFile));
	}
	
	
	/*
	 * INGRESO DE PROCESO PARA PERMISO DE TRANSPORTE DE GLP
	 * - Validar placa
	 * - Ingresar/Actualizar propietario
	 * - Ingresar/Actualizar vehículo
	 * - Validar vehículo y permiso no repetidos - DB
	 */ 
	public function insertUnit(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR VEHÍCULO O ACTUALIZAR DATOS DEL MISMO
		$vehiculo=$this->testedInsertVehicule($this->post);
		// INSERTAR ID DE VEHICULO
		$this->post['fk_vehiculo_id']=$vehiculo['vehiculo_id'];
		// INGRESAR REGISTRO DE VEHÍCULO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// CIERRE DE TRANSACCIÓN
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZACIÓN DE PROCESO PARA PERMISO DE TRANSPORTE DE GLP
	 */ 
	public function updateUnit(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR VEHÍCULO O ACTUALIZAR DATOS DEL MISMO
		// $vehiculo=$this->testedInsertVehicule($post);
		// $post['fk_vehiculo_id']=$vehiculo['vehiculo_id'];
		// INGRESAR REGISTRO DE VEHÍCULO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// CIERRE DE TRANSACCIÓN
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * CONSULTAR UNIDADES
	 */
	public function requestUnit(){
		// VERIFICAR 
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}