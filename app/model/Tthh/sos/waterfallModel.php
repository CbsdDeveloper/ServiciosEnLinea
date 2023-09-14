<?php namespace model\Tthh\sos;
use api as app;
use controller as ctrl;

/**
 * @author Lalytto
 *
 */
class waterfallModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='fichacascada';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
		// CORRECTION FACTORS
		$this->config=$this->string2JSON($this->varGlobal['WATERFALL_CORECCTION_FACTORS']);
	}
	
	
	/*
	 * OBTENER FACTOR DE CORRECCION
	 */
	private function getCorrectionFactor(){
		// SUMAR FACTOR PROPORCIONAR DE TEMPERATURA AMBIENTE
		$this->post['cascada_temperatura_variacion']=$this->config['factor'];
		
		// TEMPERATURA TOTAL
		$temp=$this->post['cascada_temperatura_variacion']+$this->post['cascada_temperatura'];
		
		// AGREGAR FACTOR DE CORRECCION POR DEFECTO
		$this->post['cascada_factor_correccion']=0;
		
		// FACTOR DE CORRECCION SUPERIOR
		$upper=$this->roundTo10($temp);
		$upperRelation=$this->config['limites'][$upper];
		// FACTOR DE CORRECCION INFERIOR
		$lower=$this->roundTo10($temp,'-');
		$lowerRelation=$this->config['limites'][$lower];
		
		// CALCULO DEL FACTOR DE CORRECCION
		$correction=$this->setFormatNumber(($lowerRelation+(($temp-$lower)/($upper-$lower))*($upperRelation-$lowerRelation)),2);
		
		// CALCULAR FACTOR DE CORRECCIÓN
		$this->post['cascada_factor_correccion']=$correction;
	}
	
	/*
	 * REGISTRO DE CILINDROS LLENADOS
	 */
	private function insertTanks($tanks,$entityId){
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("ok",true);
		// RECORRER LISTADO DE USUARIOS
		foreach($tanks as $v){
			// GENERAR MODELO
			$model=array(
				'fk_ficha_id'=>$entityId,
				'cilindro_capacidad'=>$v['cilindro_capacidad'],
				'cilindro_cantidad'=>$v['cilindro_cantidad']
			);
			// STRING PARA CONSULTAR TIPO DE 
			$str=$this->db->selectFromView('tanques_fichacascada',"WHERE fk_ficha_id={$entityId} AND cilindro_capacidad={$v['cilindro_capacidad']}");
			// CONSULTAR REGISTRO
			if($this->db->numRows($str)>0){
				// GENERAR STRING DE CONSULTA
				$str=$this->db->getSQLUpdate(array_merge($this->db->findOne($str),$model),'tanques_fichacascada');
			}else{
				// GENERAR STRING DE CONSULTA
				$str=$this->db->getSQLInsert($model,'tanques_fichacascada');
			}
			// GENERAR STRING DE INGRESO Y EJECUCIÓN DE CONSULTA
			$sql=$this->db->executeTested($str);
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * INGRESAR NUEVOS REGISTROS
	 */
	public function insertEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		// DATOS COMPLETOS
		$this->getCorrectionFactor();
		// STRING DE INGRESO DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER IDE DE NUEVO REGISTRO
		$entityId=$this->db->getLastID($this->entity);
		// ACTUALIZAR TANQUES USADOS
		$sql=$this->insertTanks($this->post['tanks'],$entityId);
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICACIÓN DE REGISTROS
	 */
	public function updateEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		// DATOS COMPLETOS
		$this->getCorrectionFactor();
		// STRING DE INGRESO DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// ACTUALIZAR TANQUES USADOS
		$sql=$this->insertTanks($this->post['tanks'],$this->post['cascada_id']);
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * LISTA DE TANQUES HABILITADOS
	 */
	private function getTanks(){
		// MODELO DE LISTADO
		$list=array();
		// RECORRER LISTA DE TANQUES PARA FORMAR EL MODELO
		foreach($this->string2JSON($this->varGlobal['WATERFALL_CYLINDER_CAPACITY']) as $k=>$v){
			// LISTADO DE TANQUES
			$list[$k]=array(
				'cilindro_capacidad'=>$k,
				'cilindro_cantidad'=>0
			);
		}
		// RETORNAR LISTADO
		return $list;
	}
	
	/*
	 * BUSCAR POR PARÁMETRO
	 */
	private function requestByParam($param){
		// VALIDAR SI ES ID DE REGISTRO
		if(is_int($param)){
			// OBTENER ENTIDAD
			$model=$this->db->findById($param,$this->entity);
			// ADJUNTAR LISTADO DE TANQUES
			$model['tanks']=$this->getTanks();
			// INGRESAR TANQUES
			foreach($this->db->findAll($this->db->selectFromView('tanques_fichacascada',"WHERE fk_ficha_id={$model['cascada_id']}")) AS $v){
				// MODELO DE TANQUE
				$aux=array(
					'cilindro_capacidad'=>$v['cilindro_capacidad'],
					'cilindro_cantidad'=>$v['cilindro_cantidad']
				);
				// UNIR MODELOS
				$model['tanks'][$v['cilindro_capacidad']]=$aux;
			}
			// RETORNAR MODELO
			return $model;
		}
		// MODELO
		return array(
			'tanks'=>$this->getTanks()
		);
	}
	
	/*
	 * CONSULTA DE REGISTROS
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// CONSULTAR POR FECHA - NUEVO REGISTRO
		elseif(isset($this->post['param'])) $json=$this->requestByParam($this->post['param']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * INGRESAR REGISTRO DE FILTRO PARA CASCADA
	 */
	public function insertFilter(){
		// SUMAR FACTOR PROPORCIONAR DE TEMPERATURA AMBIENTE
		$this->post['fk_personal_id']=$this->getStaffIdBySession();
		// STRING DE INGRESO DE REGISTRO
		$json=$this->db->executeTested($this->db->getSQLInsert($this->post,'filtrocascada'));
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($json);
	}
	
	/*
	 * INGRESAR REGISTRO DE FILTRO PARA CASCADA
	 */
	public function updateFilter(){
		// SUMAR FACTOR PROPORCIONAR DE TEMPERATURA AMBIENTE
		$this->post['fk_personal_id']=$this->getStaffIdBySession();
		// STRING DE INGRESO DE REGISTRO
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,'filtrocascada'));
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($json);
	}
	
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printFilterDetail($pdfCtrl,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("filtrocascada","WHERE filtro_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			// PLANTILLA PARA EL DETALLE DEL REGISTRO DE USO DE CASCADA
			$html="";
			// FACTOR DE CORRECCION
			$correctionFactor=0;
			$row['2216_total']=0;
			$row['4500_total']=0;
			$row['tanques_total']=0;
			$row['psi_total']=0;
			// RECORRER LISTADO DE CASCADA
			foreach($this->db->findAll($this->db->selectFromView("vw_{$this->entity}","WHERE fk_filtro_id={$row['filtro_id']} ORDER BY cascada_hora_inicio")) as $key=>$val){
				// INDEX
				$val['index']=$key+1;
				// PARSE
				$val['total_minutos']=$this->setTimeToDecimal($val['total_minutos']);
				// HORAS DE UTILIZACIÓN
				$val['cascada_utilizacion']=$this->setFormatDate($val['cascada_hora_inicio'],'Y-m-d');
				$val['hora_inicio']=$this->setFormatDate($val['cascada_hora_inicio'],'H:i');
				$val['hora_finalizacion']=$this->setFormatDate($val['cascada_hora_fin'],'H:i');
				// SUMAR TANQUES
				$val['total_tanques']=$val['psi_2216']+$val['psi_4500'];
				// SUMA DE FACTOR DE CORRECCION
				$val['cascada_correccion_parcial']=number_format($val['total_minutos']/$val['cascada_factor_correccion'],2);
				// INSERTAR FACTOR DE CORRECCION
				$correctionFactor+=$val['cascada_correccion_parcial'];
				$val['cascada_correccion_total']=$correctionFactor;
				// CONCATENAR NUEVO REGISTRO
				$html.=$this->varGlobal['WATERFALL_FILTER_DETAIL'];
				// TOTALES
				$row['2216_total']+=$val['psi_2216'];
				$row['4500_total']+=$val['psi_4500'];
				$row['tanques_total']+=$val['total_tanques'];
				$row['psi_total']+=$val['total_psi'];
				// IMPRIMIR REGISTRO
				foreach($val as $k=>$v){ $html=preg_replace('/{{'.$k.'}}/',$v,$html); }
			}
			// INSERTAR CUERPO DE REPORTE
			$row['tBody']=$html;
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * DETALLER DLE FLTRO
	 */
	private function getDetailByFilterId($filterId){
		// FACTOR DE CORRECCION
		$row=array();
		$row['correctionFactor']=0;
		$row['2216_total']=0;
		$row['4500_total']=0;
		$row['tanques_total']=0;
		$row['psi_total']=0;
		$row['stationsList']=array();
		// RECORRER LISTADO DE CASCADA
		foreach($this->db->findAll($this->db->selectFromView("vw_{$this->entity}","WHERE fk_filtro_id={$filterId} ORDER BY cascada_hora_inicio")) as $val){
			// PARSE
			$val['total_minutos']=$this->setTimeToDecimal($val['total_minutos']);
			// SUMAR TANQUES
			$val['total_tanques']=$val['psi_2216']+$val['psi_4500'];
			// SUMA DE FACTOR DE CORRECCION
			$val['cascada_correccion_parcial']=number_format($val['total_minutos']/$val['cascada_factor_correccion'],2);
			// INSERTAR FACTOR DE CORRECCION
			$row['correctionFactor']+=$val['cascada_correccion_parcial'];
			$val['cascada_correccion_total']=$row['correctionFactor'];
			// TOTALES
			$row['2216_total']+=$val['psi_2216'];
			$row['4500_total']+=$val['psi_4500'];
			$row['tanques_total']+=$val['total_tanques'];
			$row['psi_total']+=$val['total_psi'];
			
			// VALIDAR LA EXISTENCIA DE UNA ESTACION
			if(!isset($row['stationsList'][$val['peloton_estacion']])){
				$row['stationsList'][$val['peloton_estacion']]=array(
					'psi_2216'=>0,
					'psi_4500'=>0,
					'tanques_total'=>0,
					'psi_total'=>0
				);
			}
			// CARGAR TANQUES Y PSI
			$row['stationsList'][$val['peloton_estacion']]['psi_2216']+=$val['psi_2216'];
			$row['stationsList'][$val['peloton_estacion']]['psi_4500']+=$val['psi_4500'];
			$row['stationsList'][$val['peloton_estacion']]['tanques_total']+=$val['total_tanques'];
			$row['stationsList'][$val['peloton_estacion']]['psi_total']+=$val['total_psi'];
		}
		// PARSE HORAS
		$row['correctionFactor_horas']=$this->setNumbreToTime($row['correctionFactor']);
		// RETORNAR DETALLE
		return $row;
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printFilterById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_filtrocascada","WHERE filtro_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$row=$this->db->findOne($str);
			// UNIDAR DETALLES DE FILTRO
			$row=array_merge($row,$this->getDetailByFilterId($id));
			// PARSE
			$row['filtro_fecha_ingreso']=$this->setFormatDate($row['filtro_fecha_ingreso'],'complete');
			if(!in_array($row['filtro_fecha_cambio'],[null,''])) $row['filtro_fecha_cambio']=$this->setFormatDate($row['filtro_fecha_cambio'],'complete');
			// LISTA DE TANQUES UTILIZADOS
			$row['detailPSI']="";
			$row['total_psi']=0;
			$row['total_tanques']=0;
			// STRING PARA LISTAR TOTAL DE TIPO DE TANQUES LLENADOS
			$str=$this->db->selectFromView("vw_tanques_filtrocascada","WHERE fk_filtro_id={$id}");
			// RECORRER LISTADO
			foreach($this->db->findAll($str) as $v){
				// SUMATORIA DE PSI
				$row['total_psi']+=($v['cilindro_capacidad']*$v['tanques']);
				$row['total_tanques']+=$v['tanques'];
				// TEMPLATE
				$row['detailPSI'].="<tr><td>CILINDROS DE {$v['cilindro_capacidad']} PSI</td><td>{$v['tanques']}</td></tr>";
			}
			
			$html="";
			// REPORTE POR ESTACIONES
			foreach($row['stationsList'] as $key=>$val){
				// 
				$val['estacion']=$key;
				// CONCATENAR NUEVO REGISTRO
				$html.=$this->varGlobal['WATERFALL_FILTER_STATION_DETAIL'];
				// IMPRIMIR REGISTRO
				foreach($val as $k=>$v){ $html=preg_replace('/{{'.$k.'}}/',$v,$html); }
			}
			// INSERTAR CUERPO DE REPORTE
			$row['stationsList']=$html;
			
			// INFORMACION DE PERSONA QUE REALIZA EL CAMBIO
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_personal_id'],'personal'));
			
			// CARGAR DATOS DEL REGISTRO
			$pdfCtrl->loadSetting($row);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}