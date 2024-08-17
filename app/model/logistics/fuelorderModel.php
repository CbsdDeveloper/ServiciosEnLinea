<?php namespace model\logistics;
use api as app;
use controller as ctrl;

class fuelorderModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='ordenescombustible';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */ 
	public function insertEntity(){

		
		// INICIAR TRANSACCIÓN
		$this->db->begin();

		$sqlPendientes= "SELECT count(orden_id) as ordenes_generadas FROM logistica.tb_ordenescombustible WHERE fk_unidad = {$this->post['fk_unidad']} AND orden_estado='EMITIDA'";
		$ordenesPendientes=$this->db->findOne($sqlPendientes);

		$sql=$this->setJSON("La unidad tiene {$ordenesPendientes["ordenes_generadas"]} ordenes pendientes de validar!");
		$this->getJSON($this->db->closeTransaction($sql));
		// DATOS DE SESSION
		$sessionId=$this->getStaffIdBySession();
		// DATOS DE PERSONAL - PUESTOS
		$staff=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE personal_id={$sessionId}"));
		// ENCARGADO DE ESTACION
		$stationManager=0;
		
		// DATOS DEL SOLICITANTE
		$requested=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$this->post['personal_solicita']}"));
		// VALIDAR SI EL PERSONAL QUE RETIRNA ESTA EN DISTRIBUTIVO
		$str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$requested['personal_id']} AND tropa_estado='ACTIVO'");
		// CONSULTAR Y OBTENER REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			// OBTENER ID DE ENCARGADO DE ESTACION
			$manager=$this->db->findOne($this->db->selectFromView('tropas',"WHERE fk_dist_pelo_id={$row['fk_dist_pelo_id']} AND tropa_cargo='ENCARGADO DE ESTACION'"));
			// ASIGNAR ID DE ENCARGADO DE ESTACION
			$stationManager=$manager['tropa_id'];
		}
		
		// GENERAR ORDENES
		for($i=1;$i<=$this->post['cantidad_vouchers'];$i++){
			// MODELO DE DATOS
			$model=array(
				'orden_estacionservicio_codigo'=>$this->post['orden_estacionservicio_codigo'],
				'orden_codigo'=>$this->post['orden_codigo'],
				'fk_unidad'=>$this->post['fk_unidad'],
				'tipo_caneca'=>$this->post['tipo_caneca'],
				'fk_solicitante_id'=>$this->post['personal_solicita'],
				'fk_personal_id'=>$sessionId,
				'fk_autoriza_id'=>$staff['ppersonal_id']
			);
			// VALIDAR ENCARGADO DE ESTACION
			if($stationManager>0) $model['fk_encargadoestacion_id']=$stationManager;
			// INGRESAR ORDEN
			$sql=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
		}
		
		// OBTENER NOMBRE DE SOLICITANTE
		$this->post['responsable']=$requested['personal_nombre'];
		// ENVIAR NOTIFICACIONES
		$this->sendWhatsAppNotification($this->entity,$this->post);
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * REGISTRO DE CANECAS Y UNIDADES
	 */
	private function setFillingOrder($orderId,$list,$type){
		// VARIABLE DE SALIDA
		$sql=$this->setJSON("No se ha registrado valores para la validación de la orden!");
		// ELIMINAR REGISTROS INGRESADOS
		$sql=$this->db->executeTested($this->db->getSQLDelete('ordenescombustible_llenado',"WHERE llenado_tipo='{$type}' AND fk_orden_id={$orderId}"));
		// RECORRER LISTADO
		foreach($list as $v){
			// MODELO
			$v['fk_orden_id']=$orderId;
			$v['llenado_tipo']=$type;
			$v['orden_precio']=($v['orden_total']/$v['orden_galones']);
			// GENERAR Y EJECUTAR CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($v,'ordenescombustible_llenado'));
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO
	 */
	public function updateEntity(){
		// VALIDAR LONGITUD DE LLENADO
		if((count($this->post['units']) + count($this->post['canecas']))<1) $this->getJSON("Operación cancelada, asegúrese que al menos haya seleccionado una UNIDAD/CANECA para la validación!");
		
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		
		// INSERTAR ID DE SESION
		$this->post['fk_personal_id']=$this->getStaffIdBySession();
		
		// ACTUALIZAR DATOS DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		if($this->post['orden_estado']=='VALIDADA'){
			// INGRESO DE UNIDADES 
			$this->setFillingOrder($this->post['orden_id'],$this->post['units'],'UNIDAD');
			// INGRESO DE CANECAS
			$this->setFillingOrder($this->post['orden_id'],$this->post['canecas'],'CANECA');
			
			// RESPONSABLE
			$responsible=$this->db->findOne($this->db->selectFromView('vw_basic_personal',"WHERE personal_id={$this->post['fk_personal_id']}"));
			
			/*
			// INSTANCIA DE WHATSAPP - TWILIO
			$ws=new ctrl\whatsappController();
			// MENSAJE
			$msg="[*ORDENES DE COMBUSTIBLE*]: La solicitud *{$this->post['orden_codigo']}* ha sido *{$this->post['orden_estado']}* por *{$responsible['personal_nombre']}*";
			// DESTINATARIOS
			$numberList=$this->string2JSON($this->varGlobal['WHATSAPP_NOTIFICATIONS'])['list'][$this->entity];
			// VIRTUALIZACION DEL SERVICIO
			$ws->sendCustomMessage($numberList,$msg);
			*/
			
		}
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * BUSCAR REGISTROS POR CODIGO
	 */
	private function requestByCode($code){
		// ID DE SESSION
		$sessionId=$this->getStaffIdBySession();
		
		// STRING PARA CONSULTAR REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(orden_codigo)=UPPER('{$code}')");
		// CONSULTAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("El código registrado no existe dentro de los vales de combustible registrados!");
		// OBTENER REGISTRO
		$fuelOrder=$this->db->findOne($str);
		
		// VALIDAR EL MÓDULO DE CONSULTA
		if($this->post['module']=='tthh' && $fuelOrder['orden_estado']=='EMITIDA'){
			// VALIDAR ID DE SESION CON REGISTRO DEL SOLICITANTE
			if($sessionId<>$fuelOrder['solicitante_personal_id']) $this->getJSON("Lamentamos los inconvenientes, este registro no se encuentra relacionado con su perfil.");
			
		}elseif($this->post['module']=='subjefature' && $fuelOrder['orden_estado']=='EMITIDA'){
			// STRING PARA CONSULTAR SI LA PERSONA QUE SOLICITO SE ENCUENTRA EN PELOTON
			$str=$this->db->selectFromView('tropas',"WHERE fk_personal_id={$fuelOrder['solicitante_personal_id']} ORDER BY tropa_id DESC");
			// VALIDAR CONSULTA
			if($this->db->numRows($str)<1) $this->getJSON("[E1001] Lamentamos los inconvenientes, este registro no se encuentra relacionado con su perfil.");
			// OBTENER REGISTRO DEL PELOTON DEL RESPONSABLE
			$platoon=$this->db->findOne($str);
			
			// HABILITAR A ENCARGADOS DE ESTACION QUE VALIDEN CUALQUIER VALE
			if($this->db->numRows($this->db->selectFromView('tropas',"WHERE fk_personal_id={$sessionId} AND tropa_cargo='ENCARGADO DE ESTACION'"))>0){
				
			}else{
				// STRING PARA CONSULTAR SI LA SESSION SE ENCUENTRA EN PELOTON
				$str=$this->db->selectFromView('tropas',"WHERE fk_personal_id={$sessionId} AND fk_dist_pelo_id={$platoon['fk_dist_pelo_id']} AND tropa_cargo='ENCARGADO DE ESTACION'");
				// VALIDAR CONSULTA
				if($this->db->numRows($str)<1) $this->getJSON("[E2001] - Su cuenta no se encuentra relacionada al distributivo, intente desde la plataforma de TTHH");
				// OBTENER ENCARGADO DE ESTACION - SEGUN ID DE SOLICITANTE
				$stationManager=$this->db->findOne($str);
				
				// VALIDAR SI HA SIDO GENERADO EL ENCARGADO DE ESTACION
				if($fuelOrder['fk_encargadoestacion_id']>0 && $fuelOrder['fk_encargadoestacion_id']<>$stationManager['tropa_id']){ $this->getJSON("[E3001] Lamentamos los inconvenientes, este registro no se encuentra relacionado con su perfil."); }
				elseif($fuelOrder['fk_encargadoestacion_id']>0 && $fuelOrder['fk_encargadoestacion_id']==$stationManager['tropa_id']){ }
				else{ $fuelOrder['fk_encargadoestacion_id']=$stationManager['tropa_id']; }
			}
			
		}elseif($this->post['module']=='system' && $fuelOrder['orden_estado']=='EMITIDA'){
			
			
		}else{
			$this->getJSON("<b>[E0001]</b><br>Lamentamos los inconvenientes, esta solicitud ya ha sido validada!");
		}
		
		// VALIDAR ESTADO EMITIDO
		if($fuelOrder['orden_estado']=='EMITIDA'){
			// VALORES POR DEFECTO
			$fuelOrder['orden_estacionservicio']='TERPEL'; // TODO:  CAMBIAR VALOR FIJO POR UNA VARIABLE EN PARÁMETROS
			$fuelOrder['orden_valdacion']=$this->getFecha();
			$fuelOrder['orden_estado']='VALIDADA';
			$fuelOrder['units']=array();
			$fuelOrder['canecas']=array();
		}
		
		// RETORNAR CONSULTA
		return array(
			'modal'=>'Ordenescombustible',
			'data'=>$fuelOrder,
		);
	}
	
	/*
	 * CONSULTAR PUESTOS Y DIRECCIONES
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// MODELO PARA CONSULTAR POR VEHÍCULO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO - ANULAR ORDEN
	 */
	public function anularEntity(){
		
		// GENERAR MODELO TEMPORAL
		$temp=array(
		    'orden_id'=>$this->post['id'],
		    'orden_estado'=>'ANULADA'
		);
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->executeTested($this->db->getSQLUpdate($temp,$this->entity)));
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// BUSCAR REGISTRO POR ID
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// LISTADO DE ID
		$listId=explode(',',$this->get['id']);
		// PLANTILLA DE REPORTE
		$templateReport="";
		// RECORRER LISTA
		foreach($listId as $ordenId){
			// GENERAR STRING DE CONSULTA
			$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id={$ordenId}");
			// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
			if($this->db->numRows($str)>0){
				// ADJUNTAR PLANTILLA
				$aux=$this->varGlobal['REPORT_FUEL_ORDEN_TABLE_TEMPLATE'];
				// OBTENER DATOS DE REGISTRO
				$row=$this->db->findOne($str);
				// INERTAR REGISTROS EN ORDEN
				foreach($row as $k=>$v){ $aux=preg_replace('/{{'.$k.'}}/',$v,$aux); }
				// INSERTAR REGISTRO DE ORDEN
				$templateReport.=preg_replace('/{{tableTemplate}}/',$aux,$this->varGlobal['REPORT_FUEL_ORDEN_TEMPLATE']);
				$templateReport=preg_replace('/{{orden_codigo}}/',$row['orden_codigo'],$templateReport);
			}
		}
		// IMPRIMIR REPORTE
		$row['templateReport']=$templateReport;
		// CARGAR DATOS DE REGISTRO
		$pdfCtrl->loadSetting($row);
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		return '';
	}
	
}