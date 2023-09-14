<?php namespace model\collection;
use api as app;

class orderModel extends app\controller {

	private $entity='ordenescobro';
	private $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
		// PARÁMETROS DE RECAUDACIÓN
		$this->config=$this->getConfig(true,'collection');
		// PORCENTAJE DE TASAS
		$this->config['rates']=$this->string2JSON($this->varGlobal['COLLECTION_RATES_ENTITIES']);
	}
	
	
	/*
	 * CONSULTAR PRECIO DEL PROCESO
	 */
	private function getPriceOrder($tb,$entity){
		// DEFINIR PRECIO
		$price=array(
			'porcentaje'=>0,
			'subtotal'=>0
		);
		// VALIDAR SI EXISTE EL MODELO DE PORCENTAJES DE ENTIDADES
		if(isset($this->config['rates'][$tb]) && $tb!='definitivoglp'){
		    
			$price['porcentaje']=$this->config['rates'][$tb];
			
		}elseif($tb=='ocasionales'){
		    
				if($entity['ocasional_finesdelucro']=='SI' && $entity['ocasional_aforo']>5000) $price['porcentaje']=40;
			elseif($entity['ocasional_finesdelucro']=='SI' && $entity['ocasional_aforo']>1500) $price['porcentaje']=30;
			elseif($entity['ocasional_finesdelucro']=='SI' && $entity['ocasional_aforo']>500) $price['porcentaje']=25;
			elseif($entity['ocasional_finesdelucro']=='SI' && $entity['ocasional_aforo']>0) $price['porcentaje']=15;
			elseif($entity['ocasional_finesdelucro']=='NO' && $entity['ocasional_aforo']>5000) $price['porcentaje']=4;
			elseif($entity['ocasional_finesdelucro']=='NO' && $entity['ocasional_aforo']>1500) $price['porcentaje']=3;
			elseif($entity['ocasional_finesdelucro']=='NO' && $entity['ocasional_aforo']>500) $price['porcentaje']=2.5;
			elseif($entity['ocasional_finesdelucro']=='NO' && $entity['ocasional_aforo']>0) $price['porcentaje']=1.5;
			
		}elseif($tb=='definitivoglp'){
			
			// PORCENTAJE POR TANQUE
			$porcentaje=$this->config['rates'][$tb];
			// OBTENER EL NUMERO DE TANQUES
			$tanks=$this->db->findAll($this->db->selectFromView('factibilidad_tanques',"WHERE fk_factibilidad_id={$entity['fk_factibilidad_id']}"));
			// OBTENER EL PRECIO POR NUMERO DE TANQUES
			$price=count($tanks)*($this->varGlobal['BASIC_SALARY']*($porcentaje/100));
			
			return array(
				'porcentaje'=>0,
				'subtotal'=>number_format($price, 2, '.', '')
			);
			
		}elseif($tb=='factibilidadglp'){
			$price['capacidad_unidades']="";
			$tanks=$this->db->findAll($this->db->selectFromView('factibilidad_tanques',"WHERE fk_factibilidad_id={$entity['factibilidad_id']}"));
			$length=count($tanks);
			foreach($tanks as $k=>$v){
				if($v['tanque_capacidad']>=0 && $v['tanque_capacidad']<=0.5) $price['porcentaje']+=4;
				elseif($v['tanque_capacidad']>0.5 && $v['tanque_capacidad']<=2.5) $price['porcentaje']+=6;
				elseif($v['tanque_capacidad']>2.5 && $v['tanque_capacidad']<=5) $price['porcentaje']+=8;
				elseif($v['tanque_capacidad']>5 && $v['tanque_capacidad']<=20) $price['porcentaje']+=10;
				elseif($v['tanque_capacidad']>20) $price['porcentaje']+=12;
				// DETALLE DE CANTIDAD DE TANQUES 
				$price['capacidad_unidades'].="<b>{$v['tanque_capacidad']} <small>m3</small></b>";
				if(($length-1)==($k+1) && !$length<($k+1)) $price['capacidad_unidades'].=" y ";
				if($k<$length-2) $price['capacidad_unidades'].=", ";
			}
		}elseif(in_array($tb,['vbp'])){
			$tanques=number_format($entity['vbp_es_tanques']*($this->varGlobal['BASIC_SALARY']*0.08), 2, '.', '');
			$surtidores=number_format($entity['vbp_es_surtidores']*($this->varGlobal['BASIC_SALARY']*0.15), 2, '.', '');
			$subtotal=number_format($entity['area_construccion']*0.1, 2, '.', '');
			return array(
				'porcentaje'=>0,
				'subtotal'=>number_format($tanques+$surtidores+$subtotal,2,'.','')
			);
		}elseif(in_array($tb,['vbp_modificaciones'])){
			return array(
				'porcentaje'=>0,
			    'subtotal'=>abs(number_format($entity['modificacion_area_modificada']*0.1, 2, '.', ''))
			);
		}else{
			$this->getJSON("No se ha establecido la tasa de valores a cancelar para este trámite!");
		}
		// CALSULO DE TOTAL A PAGAR
		$price['subtotal']=number_format($this->varGlobal['BASIC_SALARY']*($price['porcentaje']/100), 2, '.', ',');
		// RETORNAR VALOR
		return $price;
	}
	
	/*
	 * CONSULTAR STAND POR CÓDIGO
	 * -> requestStans
	 */
	private function requestByCode($code){
		// MENSAJE POR DEFECTO DE RETORNO
		$json=$this->setJSON("Código de orden no registrado!");
		// GENERAR PARA SENTENCIA DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(orden_codigo)=UPPER('$code')");
		// CONSULTAR DISPONIBILIDAD EN BASE DE DATOS
		if($this->db->numRows($str)<1) $this->getJSON($json);
		// VALIDAR DATOS
		$order=$this->db->findOne($str);
		// VALIDAR EL ESTADO DE LA ORDEN DE COBRO
		if($order['orden_estado']=='DESPACHADA') $this->getJSON("Ya se ha despachado esta orden de cobro!");
		// RETORNAR DATOS DE ORDEN DE COBRO
		return $order;
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID Y ENTIDAD ASOCIADA
	 */
	private function requestByEntityId($processId,$process){
		// CONSULTAR ENTIDAD
		$row=$this->db->viewById($processId,$process);
		
		// VALIDACIONES EXTRAS
		if(isset($row['ocasional_finesdelucro'])){
			$row['ocasional_lucro']=($row['ocasional_finesdelucro']=='SI')?'CON FINES DE LUCRO':'SIN FINES DE LUCRO';
		}
		
		// GENERAR MODELO DE ORDEN DE COBRO
		$order=$this->config['entities'][$process];
		$params=$this->config['params'][$process];
		// PRESENTAR O NO SOLICITUD DE INGRESO
		// $order['orden_solicitud_requerida']=$this->varGlobal[$params['status_key']];
		// CONSULTAR VALORES
		$order['orden_serie']=0;
		// CALCULO DE PORCENTAJE Y TASAS A PAGAR
		$priceAux=$this->getPriceOrder($process,$row);
		$order['orden_porcentaje']=$priceAux['porcentaje'];
		$order['orden_subtotal']=$priceAux['subtotal'];
		$order['orden_total']=$order['orden_subtotal'];
		// PARÁMETROS EXTRAS
		if(isset($priceAux['capacidad_unidades'])) $row['tanques_capacidad']=$priceAux['capacidad_unidades'];
		// DATOS
		$order['orden_entidad']=$process;
		$order['orden_entidad_id']=$processId;
		$order['orden_codigo']=$row[$params['code']];
		$order['orden_solicitud']=$row[$params['solicitud']];
		$order['fk_genera']=app\session::getSessionAdmin();
		
		// DATOS DE PRINCIPAL ENTIDAD PARA FACTURACIÓN
		$process=$this->db->viewById($row[$params['fk_entity_id']],$params['entity']);
		// DATOS GENERALES
		$order['orden_telefono']=$process['persona_telefono'];
		$order['orden_email']=$process['persona_correo'];
		$order['orden_direccion']=(isset($process['persona_direccion']))?$process['persona_direccion']:'';
		
		// VALIDAR SI EXISTE RELACION CON ENTIDADES
		if($params['entity']=='entidades'){
			$order['orden_cliente_nombre']="{$process['entidad_razonsocial']}";
			$order['orden_doc_type']=$process['entidad_contribuyente'];
			$order['orden_doc_identidad']=$process['entidad_ruc'];
		}else{
			$order['orden_cliente_nombre']="{$process['persona_apellidos']} {$process['persona_nombres']}";
			$order['orden_doc_type']=$process['persona_tipo_doc'];
			$order['orden_doc_identidad']=$process['persona_doc_identidad'];
		}
		
		// DIRECCIÓN DE LA ORDEN
		if(in_array($order['orden_telefono'],['',null,' '])){
			if(isset($row['local_telefono'])) $order['orden_telefono']=$row['local_telefono'];
			else $order['orden_telefono']="";
		}
		if(in_array($order['orden_direccion'],['',null,' '])){
			if(isset($row['vbp_principal'])) $order['orden_direccion']="{$row['vbp_principal']} y {$row['vbp_secundaria']}";
			elseif(isset($row['factibilidad_principal'])) $order['orden_direccion']="{$row['factibilidad_principal']} y {$row['factibilidad_secundaria']}";
			elseif(isset($row['ocasional_principal'])) $order['orden_direccion']="{$row['ocasional_principal']} y {$row['ocasional_secundaria']}";
			elseif(isset($row['local_principal'])) $order['orden_direccion']="{$row['local_principal']} y {$row['local_secundaria']}";
			else $order['orden_direccion']="";
		}
		// REEMPLAZAR LOS DATOS DEL MODELO DE ORDEN DE COBRO
		foreach($row as $k=>$v){
			foreach($order as $key=>$val){
				$order[$key]=str_replace($k,$v,$val);
			}
		}
		// RETURN DATA
		return $order;
	}
	
	/*
	 * CONSULTAR DATOS DE LA ORDEN DE COBRO A DESPACHAR
	 */
	public function requestOrder(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['entityId'])) $json=$this->requestByEntityId($this->post['entityId'],$this->post['entity']);
		// CONSULTAR POR CÓDIGO - INGRESO EN CBSD
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * ACTUALIZAR REGISTRO
	 */
	public function updateOrder(){
		// PARÁMETROS DE DESPACHADA
		if($this->post['orden_estado']=='DESPACHADA'){
			$this->post['fecha_despachado']=$this->getFecha('dateTime');
			$this->post['fk_despacha']=app\session::getSessionAdmin();
		}
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// GENERAR CONSULTA DE INGRESO / INSERTAR REGISTRO DE PERMISO
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// ACTUALIZAR ESTADO DE PROCESO -> ORDEN DE COBRO
		if($this->post['orden_estado']=='DESPACHADA' && $json['estado']){
			// PARÁMETROS PARA ACTUALIZACIÓN DE ENTIDAD
			if($this->post['orden_entidad']=='ocasionales'){
				$this->post['ocasional_estado']='PAGADO';
				$this->post['ocasional_id']=$this->post['orden_entidad_id'];
			}elseif($this->post['orden_entidad']=='planesemergencia'){
				$this->post['plan_estado']='PAGADO';
				$this->post['plan_id']=$this->post['orden_entidad_id'];
			}elseif($this->post['orden_entidad']=='vbp'){
				$this->post['vbp_estado']='PAGADO';
				$this->post['vbp_id']=$this->post['orden_entidad_id'];
			}elseif($this->post['orden_entidad']=='vbp_modificaciones'){
				$this->post['modificacion_estado']='PAGADO';
				$this->post['modificacion_id']=$this->post['orden_entidad_id'];
			}elseif($this->post['orden_entidad']=='habitabilidad'){
				$this->post['habitabilidad_estado']='PAGADO';
				$this->post['habitabilidad_id']=$this->post['orden_entidad_id'];
			}elseif($this->post['orden_entidad']=='factibilidadglp'){
				$this->post['factibilidad_estado']='PAGADO';
				$this->post['factibilidad_id']=$this->post['orden_entidad_id'];
			}elseif($this->post['orden_entidad']=='definitivoglp'){
				$this->post['definitivo_estado']='PAGADO';
				$this->post['definitivo_id']=$this->post['orden_entidad_id'];
			}
			// INSERTAR RESPONSABLE DE PREVENCION - APRUEBA
			$this->post['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
			// VALIDAR ENTIDAD PARA REALIZAR CAMBIOS
			if(in_array($this->post['orden_entidad'],['ocasionales','planesemergencia','vbp','habitabilidad','vbp_modificaciones','factibilidadglp','definitivoglp'])){
				$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->post['orden_entidad']));
			}	
		}
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}

	/*
	 * INSERTAR NUEVA REGISTRO
	 */
	public function insertOrder(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		$order=$this->post['order'];
		if($order['orden_serie']==0) $order['orden_serie']=$this->db->getNextSerie($this->entity);
		
		// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
		$order['jtp_responsable']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
		
		// GENERAR CONSULTA DE INGRESO / INSERTAR REGISTRO DE PERMISO
		$json=$this->db->executeTested($this->db->getSQLInsert($order,$this->entity));
		// REGISTRO DE ORDEN DE COBRO
		$order=$this->db->getLastEntity($this->entity);
		
		// ACTUALIZAR ESTADO DE PROCESO -> ORDEN DE COBRO
		if($this->post['entity']=='ocasionales'){
			$this->post['ocasional_estado']='ORDEN DE COBRO GENERADA';
		}elseif($this->post['entity']=='duplicados'){
			$this->post['duplicado_estado']='APROBADO';
			$this->post['fecha_aprobado']=$this->getFecha('dateTime');
			$this->post['fk_usuario_aprueba']=app\session::getSessionAdmin();
		}elseif($this->post['entity']=='planesemergencia'){
			$this->post['plan_estado']='ORDEN DE COBRO GENERADA';
		}elseif($this->post['entity']=='vbp'){
			$this->post['vbp_estado']='ORDEN DE COBRO GENERADA';
		}elseif($this->post['entity']=='habitabilidad'){
			$this->post['habitabilidad_estado']='ORDEN DE COBRO GENERADA';
		}elseif($this->post['entity']=='vbp_modificaciones'){
			$this->post['modificacion_estado']='ORDEN DE COBRO GENERADA';
		}elseif($this->post['entity']=='factibilidadglp'){
			$this->post['factibilidad_estado']='ORDEN DE COBRO GENERADA';
		}elseif($this->post['entity']=='definitivoglp'){
			$this->post['definitivo_estado']='ORDEN DE COBRO GENERADA';
		}
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->post['entity']));
		// INSERTAR ORDEN DE COBRO PARA REPORTE
		$json['data']=$order;
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * ASOCIAR ENTIDAD
	 */
	private function getEntity($id,$entity){
		$aux=array();
		// DATOS DE ENTIDAD RELACIONADA
		$data=$this->db->viewById($id,$entity);
		// INGRESO DE NUEVAS ENTIDADES
		$aux['orden_lucro']=(isset($data['ocasional_finesdelucro']))?$data['ocasional_finesdelucro']:'';
		$aux['orden_aforo']=(isset($data['ocasional_aforo']))?"{$data['ocasional_aforo']} personas":'';
		// RETORNAR DATOS
		return $aux;
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_informacion_{$this->entity}","WHERE orden_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			// DATOS DE ORDEN
			$row['fecha_generado']=$this->setFormatDate($row['fecha_generado'],'full');
			$row['fecha_despachado']=$this->setFormatDate($row['fecha_despachado'],'full');
			$row['orden_serie']=str_pad($row['orden_serie'],4,"0",STR_PAD_LEFT);
			
			// ADJUNTAR JUSTIFICACIÓN DE PAGO
			$justificacion=$this->config['justificacion'][$row['orden_entidad']];
			$row['orden_justificacion']=$this->msgReplace($justificacion,array_merge($this->db->viewById($row['orden_entidad_id'],$row['orden_entidad']),$row,$this->varGlobal));
			
			// PARSE NUMBER TO LETTER
			$row['orden_total_text']=$this->setNumberToLetter($row['orden_total'],true);
			// CARGAR DATOS DE ORDEN PARA IMPRESIÓN
			$pdfCtrl->loadSetting($row);
			// ASOCIAR ENTIDAD
			$pdfCtrl->loadSetting($this->getEntity($row['orden_entidad_id'],$row['orden_entidad']));
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// MENSAJE POR DEFECTO QUE NO EXISTE LA ORDEN DE COBRO
			$this->getJSON('No se ha generado este registro');
		}
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		// GET BARCODE
		$pdfCtrl->setBQCode('barcode',$row['orden_codigo'],'none','64px','100%');
		// RETORNAR NULL
		return '';
	}
	
}