<?php namespace model\collection;
use api as app;

class archingModel extends app\controller {

	private $entity='cierres';
	private $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * INGRESAR CIERRE DE CAJA
	 */
	public function insertEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// STRING PARA CONSULTAR EXISTENCIA DE CIERRE DE CAJA DE ESA FECHA
		$str=$this->db->selectFromView($this->entity,"WHERE cierre_tipo='{$this->post['cierre_tipo']}' AND cierre_fecha='{$this->post['cierre_fecha']}'::date");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// CONSULTAR REGISTRO
			$row=$this->db->findOne($str);
			// STRING PARA CONSULTA
			$json=$this->db->executeTested($this->db->getSQLUpdate(array_merge($row,$this->post),$this->entity));
			// MODELO AUXILIAR
			$temp=$this->post;
		}else{
			// STRING PARA CONSULTA
			$json=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
			// MODELO AUXILIAR
			$temp=$this->db->getLastEntity($this->entity);
		}
		// OBTENER IDE DE CIERRE
		$json['data']=$temp;
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * ACTUALIZAR CIERRE DE CAJA
	 */
	public function updateEntity(){
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// STRING PARA CONSULTAR TOTAL DE ORDENES SELECCIONADAS
		$str=$this->db->getSQLSelect($this->db->setCustomTable('ordenescobro','SUM(orden_total) cierre_total'),[],"WHERE orden_id IN (".implode(",",$this->post['selected']).")");
		// EJECUTAR SENTENCIA
		$this->post=array_merge($this->post,$this->db->findOne($str));
		
		// STRING PARA CONSULTAR EXISTENCIA DE CIERRE DE CAJA DE ESA FECHA
		$str=$this->db->selectFromView($this->entity,"WHERE cierre_tipo='{$this->post['cierre_tipo']}' AND cierre_fecha='{$this->post['cierre_fecha']}'::date");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// CONSULTAR REGISTRO
			$row=$this->db->findOne($str);
			// STRING PARA CONSULTA
			$json=$this->db->executeTested($this->db->getSQLUpdate(array_merge($row,$this->post),$this->entity));
			// MODELO AUXILIAR
			$temp=$this->post;
		}else{
			// STRING PARA CONSULTA
			$json=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
			// MODELO AUXILIAR
			$temp=$this->db->getLastEntity($this->entity);
		}
		
		// ELIMINAR RELACION DE ORDENES DE COBRO
		$json=$this->db->executeTested($this->db->getSQLDelete('cierres_ordenescobro',"WHERE fk_cierre_id={$temp['cierre_id']}"));
		// REGISTRAR ORDENES DE COBRO
		foreach($this->post['selected'] as $order){
			// GENERAR MODELO TEMPORAL
			$model=array(
				'fk_cierre_id'=>$temp['cierre_id'],
				'fk_orden_id'=>$order
			);
			// EJECUTAR SENTENCIA
			$json=$this->db->executeTested($this->db->getSQLInsert($model,'cierres_ordenescobro'));
		}
		
		// OBTENER IDE DE CIERRE
		$json['data']=$temp;
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	
	/*
	 * RESPONSABLES:: RECAUDADOR Y TESORERO
	 */
	private function getResponsible(){
		// MODELO
		$data=array();
		
		// AUXILIARES
		$recaudador=array();
		
		// CONSULTAR RECAUDADOR
		$fk_recaudador=$this->getStaffIdBySession();
		$str=$this->db->selectFromView('vw_relations_personal',"WHERE personal_id={$fk_recaudador} AND ppersonal_estado='EN FUNCIONES'");
		// VALIDAR EXISTENCIA DE RECAUDADOR
		if($this->db->numRows($str)<1) $this->getJSON("No se ha podido registrar el perfil de <b>RECAUDADOR/A</b>, favor tome contacto con el administrador!");
		else{
			$recaudador=$this->db->findOne($str);
			$data['fk_recaudador']=$recaudador['ppersonal_id'];
		}
		
		// CONSULTAR PUESTOS SUPERIORES A RECAUDADOR
		// OBTENER DATOS DEL PERFIL DE RECAUDACION
		$recaudadorJob=$this->db->findById($recaudador['puesto_id'],'puestos');
		// SELECCIONAR ID DE PUESTOS
		$str=$this->db->setCustomTable('puestos','puesto_id');
		// SELECCIONAR ID DE PUESTOS SUPERIORES DE LA MISMA DIRECCIÓN
		$str=$this->db->selectFromView($str,"WHERE fk_direccion_id={$recaudadorJob['fk_direccion_id']} AND puesto_grado<={$recaudadorJob['puesto_grado']} OR fk_direccion_id=5 ORDER BY puesto_grado");
		// LISTADO DE PERSONAL SUPERIOR AL PERFIL RECAUDADOR
		$data['fk_boss']=$this->db->findAll($this->db->selectFromView('vw_ppersonal',"WHERE fk_puesto_id IN ({$str}) AND ppersonal_estado='EN FUNCIONES' ORDER BY personal_nombre"));
		
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * SERIE POR TIPO
	 */
	private function getSerieArching($type,$date){
		// CONSULTAR SI EXISTE REGISTRO CON LA FECHA
		$str=$this->db->selectFromView($this->entity,"WHERE cierre_tipo='{$type}' AND cierre_fecha='{$date}'::date");
		// CONSULTAR SI EXISTE CIERRE
		if($this->db->numRows($str)>0) return $this->db->findOne($str)['cierre_serie'];
		
		// STRING PARA CONSULTAR NUNMERO MAXIMO DE SERIE DEL AÑO
		$str=$this->db->getSQLSelect($this->db->setCustomTable('cierres','max(cierre_serie::int) serie'),[],"WHERE cierre_tipo='{$type}' AND date_part('year',cierre_fecha)=date_part('year',current_date)");
		// VALIDAR CONSULTA
		$serie=$this->db->findOne($str);
		// RETORNAR SERIE +1
		return intval($serie['serie'])+1;
	}
	
	/*
	 * CONSULTAR POR TIPO 
	 */
	private function requestByType($type){
		// RESPONSABLES
		$responsible=$this->getResponsible();
		// MODELO
		$data=array(
			'cierre_tipo'=>$type,
			'cierre_serie'=>$this->getSerieArching($type,$this->getFecha()),
			'cierre_fecha'=>$this->getFecha(),
			'fk_tesorero'=>$responsible['fk_tesorero'],
			'fk_recaudador'=>$responsible['fk_recaudador'],
			'types'=>array()
		);
		
		// CONSULTAR POR TIPO 
		if($type=='ORDENES DE COBRO'){
			// TOTAL RECAUDADO EN ORDENES
			$data['cierre_total']=$this->db->findOne($this->db->selectFromView('vw_caja_ordenes',"WHERE fecha='{$data['cierre_fecha']}'::date"))['cierre_total'];
			// CONSULTAR ORDENES DE COBRO POR FECHA
			$data['orders']=$this->getInvoicesByDate($data['cierre_fecha']);
			// TOTAL RECAUDADO
			foreach($data['orders'] as $k=>$invoices){
				$data['type'][$k]=0;
				foreach($invoices as $v){
					$data['type'][$k]+=$v['orden_total'];
				}
			}
		}
		
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR POR FECHA Y TIPO
	 */
	private function requestByDate($date,$type='ORDENES DE COBRO'){
		// STRING PARA CONSULTAR SI EXISTE LA CAJA LA FECHA
		$str=$this->db->selectFromView($this->entity,"WHERE cierre_fecha='{$date}' AND UPPER(cierre_tipo)=UPPER('{$type}')");
		
		// RESPONSABLES DE REGISTRO
		$responsible=$this->getResponsible();
		
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$data=$this->db->findOne($str);
		}else{
			// MODELO
			$data=array(
				'cierre_tipo'=>$type,
				'cierre_serie'=>$this->getSerieArching($type,$date),
				'cierre_fecha'=>$date,
				'fk_recaudador'=>$responsible['fk_recaudador'],
				'types'=>array()
			);
		}
		
		// LISTADO DE SUPERIORES DEL REGISTRO
		$data['fk_staff_leadership']=$responsible['fk_boss'];
		
		// MODELO DE ORDENES
		$data['orders']=array();
		$data['selected']=array();
		// CONSULTAR POR TIPO
		if($type=='ORDENES DE COBRO'){
			// STRING DE EXCLUSION DE ORDENES YA REGISTRADAS
			$strOrder=$this->db->getSQLSelect($this->db->setCustomTable('cierres_ordenescobro',"fk_orden_id"));
			// TIPOS DE PAGO
			foreach($this->string2JSON($this->varGlobal['COLLECTION_PAYMENT_METHODS_LIST']) as $k){
				// GENERAR STRING DE CONSULTA
				$str=$this->db->selectFromView('vw_ordenescobro',"WHERE UPPER(orden_forma_pado)=UPPER('{$k}') AND orden_id NOT IN ({$strOrder}) AND orden_estado='DESPACHADA' AND fecha_despachado>'2019-04-01' ORDER BY fecha_despachado DESC");
				// VALIDAR CONSULTA
				if($this->db->numRows($str)>0){
					// CONSULTAR ORDENES DE COBRO POR FECHA
					$data['orders'][$k]=$this->db->findAll($str);
				}
			}
			// CONSULTAR REGISTROS AGREGADOS
			if(isset($data['cierre_id'])){
				// TIPOS DE PAGO
				foreach($this->string2JSON($this->varGlobal['COLLECTION_PAYMENT_METHODS_LIST']) as $k){
					// STRING DE CONSULTA DE REGISTROS DE CIERRE REALIZADO
					$strOrder=$this->db->getSQLSelect($this->db->setCustomTable('cierres_ordenescobro',"fk_orden_id"),[],"WHERE fk_cierre_id={$data['cierre_id']}");
					// GENERAR STRING DE CONSULTA
					$str=$this->db->selectFromView('vw_ordenescobro',"WHERE UPPER(orden_forma_pado)=UPPER('{$k}') AND orden_estado='DESPACHADA' AND orden_id IN ({$strOrder}) ORDER BY fecha_despachado DESC");
					// VALIDAR CONSULTA
					if($this->db->numRows($str)>0){
						// LISTA TEMPORAL
						$aux=$this->db->findAll($str);
						// CONSULTAR ORDENES DE COBRO POR FECHA
						$data['orders'][$k]=array_merge($aux,$data['orders'][$k]);
						// INSERTAR LISTA INGRESADA
						foreach($aux as $v){ $data['selected'][]=$v['orden_id']; }
					}
				}
				
			}
		}
		
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTA DE CAJAS
	 */
	public function requestEntity(){
		// CONSULTAR POR ID
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// CONSULTAR POR EL TIPO DE CIERRE
		elseif(isset($this->post['type'])) $json=$this->requestByType($this->post['type']);
		// CONSULTAR POR FECHA
		elseif(isset($this->post['code'])) $json=$this->requestByDate($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * INFORMACION DE REPORTE
	 */
	private function getInvoicesByDate($date){
		// MODELO
		$data=array();
		// TIPOS DE PAGO
		foreach($this->string2JSON($this->varGlobal['COLLECTION_PAYMENT_METHODS_LIST']) as $k){
			// STRING PARA CONSULTAR ORDENES DE COBRO DE UNA FECHA
			$str=$this->db->selectFromView('vw_ordenescobro',"WHERE fecha_despachado::date='{$date}'::date AND orden_estado='DESPACHADA' AND UPPER(orden_forma_pado)=UPPER('{$k}')");
			// VALIDAR SI EXISTEN REGISTROS
			if($this->db->numRows($str)>0) $data[$k]=$this->db->findAll($str);
		}
		// RETORNAR MODELO
		return $data;
	}
	
	/*
	 * IMPRIMIR ORDENES
	 */
	private function printInvoicesByDate($date){
		// TEXTO
		$txt="";
		// TIPOS DE PAGO
		foreach($this->getInvoicesByDate($date) as $type=>$invoices){
			// PLANTILLA
			$template=$this->varGlobal['ARCHING_ORDERS_LIST_TEMPLATE'];
			// REGISTROS
			$td="";
			$total=0;
			// LISTADO DE ORDENES
			foreach($invoices as $v){
				// REGISTROS
				$td.=$this->varGlobal['ARCHING_ORDERS_LIST_TD'];
				$total+=$v['orden_total'];
				// REEMPLAZAR REGISTROS
				foreach($v as $k1=>$v1){ $td=preg_replace('/{{'.$k1.'}}/',$v1,$td); }
			}
			$template=preg_replace('/{{payType}}/',"{$type}",$template);
			$template=preg_replace('/{{total}}/',"{$total}",$template);
			$template=preg_replace('/{{total_text}}/',$this->setNumberToLetter($total,true),$template);
			$txt.=preg_replace('/{{ordersList}}/',$td,$template);
		}
		// RETORNAR TEXTO
		return $txt;
	}
	
	/*
	 * INFORMACION DE REPORTE
	 */
	private function getInvoicesByArchingId($archingId){
		// MODELO
		$data=array();
		// TIPOS DE PAGO
		foreach($this->string2JSON($this->varGlobal['COLLECTION_PAYMENT_METHODS_LIST']) as $k){
			// SENTENCIA DE CONSULTA CON ID DE CIERRE
			$str=$this->db->getSQLSelect($this->db->setCustomTable('cierres_ordenescobro',"fk_orden_id"),[],"WHERE fk_cierre_id={$archingId}");
			// STRING PARA CONSULTAR ORDENES DE COBRO DE UNA FECHA
			$str=$this->db->selectFromView('vw_ordenescobro',"WHERE orden_estado='DESPACHADA' AND UPPER(orden_forma_pado)=UPPER('{$k}') AND orden_id IN ($str) ORDER BY fecha_despachado");
			// VALIDAR SI EXISTEN REGISTROS
			if($this->db->numRows($str)>0) $data[$k]=$this->db->findAll($str);
		}
		// RETORNAR MODELO
		return $data;
	}
	
	/*
	 * IMPRIMIR ORDENES
	 */
	private function printInvoicesByArchingId($archingId){
		// TEXTO
		$txt="";
		// TIPOS DE PAGO
		foreach($this->getInvoicesByArchingId($archingId) as $type=>$invoices){
			// PLANTILLA
			$template=$this->varGlobal['ARCHING_ORDERS_LIST_TEMPLATE'];
			// REGISTROS
			$td="";
			$total=0;
			// LISTADO DE ORDENES
			foreach($invoices as $v){
				// REGISTROS
				$td.=$this->varGlobal['ARCHING_ORDERS_LIST_TD'];
				$total+=$v['orden_total'];
				// REEMPLAZAR REGISTROS
				foreach($v as $k1=>$v1){ $td=preg_replace('/{{'.$k1.'}}/',$v1,$td); }
			}
			$total=number_format($total, 2, '.','');
			$template=preg_replace('/{{payType}}/',"{$type}",$template);
			$template=preg_replace('/{{total}}/',"{$total}",$template);
			$template=preg_replace('/{{total_text}}/',$this->setNumberToLetter($total,true),$template);
			$txt.=preg_replace('/{{ordersList}}/',$td,$template);
		}
		// RETORNAR TEXTO
		return $txt;
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE cierre_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			
			// PARSE DATOS
			$row['cierre_serie']=str_pad($row['cierre_serie'],4,"0",STR_PAD_LEFT);
			$row['total_valorada_text']=$this->setNumberToLetter($row['total_valorada'],true);
			$row['total_novalorada_text']=$this->setNumberToLetter($row['total_novalorada'],true);
			$row['cierre_fecha_alt']=strtolower($this->setFormatDate($row['cierre_fecha'],'complete'));
			
			// CONSULTAR ORDENES DE COBRO POR FECHA
			// $row['orderLIstType']=($row['cierre_tipo']=='ORDENES DE COBRO')?$this->printInvoicesByDate($row['cierre_fecha']):'';
			// CONSULTAR ORDENES DE COBRO POR CIERRE
			$row['orderLIstType']=($row['cierre_tipo']=='ORDENES DE COBRO')?$this->printInvoicesByArchingId($row['cierre_id']):'';
			
			// CARGAR DATOS DE RESPONSABLE
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
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		$this->getJSON($config);
	}
	
}