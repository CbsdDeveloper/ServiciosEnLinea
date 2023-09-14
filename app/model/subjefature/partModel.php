<?php namespace model\subjefature;
use api as app;

class partModel extends app\controller {

	private $entity='partes';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	

	/*
	 * GENERAR CODIGO DE REGISTRO
	 */
	private function nextCodePart($platoonId,$staffId){
		// ALMACENAMIENTO DE AÑO EN CURSO
		$year=$this->getFecha('y');
		// DATOS DE PELOTON
		$str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$staffId} AND fk_peloton_id={$platoonId} AND tropa_estado='ACTIVO'");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("Al parecer usted no se encuentra registrado dentro del distributivo, si condidera que es un error favor contáctese con la Dirección de Talento Humano");
		// OBTENER REGISTRO
		$platoon=$this->db->findOne($str);
		// NÚMERO DE MOVIMIENTO
		$number=$this->db->numRows($this->db->selectFromView("vw_{$this->entity}","WHERE fk_estacion_id={$platoon['fk_estacion_id']} AND (date_part('year',parte_fecha)=date_part('year',CURRENT_DATE))"))+1;
		// PARSE DE NÚMERO DE SERIE
		$number=str_pad($number,3,'0',STR_PAD_LEFT);
		// DATOS DE OFICIAL DE GUARDIA ENCARGADO
		$official=$this->db->findOne($this->db->selectFromView('vw_encargado_estacion',"WHERE fk_dist_pelo_id={$platoon['fk_dist_pelo_id']}"));
		// RETORNAR NUMERO DE PARTE
		return array(
			'code'=>"{$year}{$platoon['estacion_nombre']}-{$number}",
			'staff'=>$platoon['tropa_id'],
			'official'=>$official['tropa_id']
		);
	}
	
	
	/*
	 * INGRESO DE NUEVO PARTE - ASYNC
	 */
	public function insertEntityAsync(){
	    
	    // INICIA TRANSACCIÓN
	    $this->db->begin();
	    
	    // DATOS DE REPONSABLES
	    $this->post['elaborado_por']=$this->getStaffIdBySession();
	    
	    // GENERAR CÓDIGO DE PARTE
	    $code=$this->nextCodePart($this->post['fk_peloton_id'],$this->post['elaborado_por']);
	    $this->post['parte_codigo']=$code['code'];
	    $this->post['fk_tropa_id']=$code['staff'];
	    $this->post['fk_guardia_id']=$code['official'];
	    
	    // PARSE FECHAS
	    $this->post['parte_aviso_hora']=$this->setFormatDate($this->post['parte_aviso_hora'],'Y-m-d H:i');
	    $this->post['parte_aviso_salida_personal']=$this->setFormatDate($this->post['parte_aviso_salida_personal'],'Y-m-d H:i');
	    $this->post['parte_aviso_llegada_personal']=$this->setFormatDate($this->post['parte_aviso_llegada_personal'],'Y-m-d H:i');
	    $this->post['parte_aviso_retorno']=$this->setFormatDate($this->post['parte_aviso_retorno'],'Y-m-d H:i');
	    $this->post['parte_aviso_ingreso_cuartel']=$this->setFormatDate($this->post['parte_aviso_ingreso_cuartel'],'Y-m-d H:i');
	    
	    // LISTA DE APOYO ADICIONAL
	    if(isset($this->post['parte_apoyo_adicional'])) $this->post['parte_apoyo_adicional']=json_encode($this->post['parte_apoyo_adicional']);
	    
	    // REALIZAR INGRESO DE PARTE
	    $sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
	    // ID DE REGISTRO
	    $partId=$this->db->getLastID($this->entity);
	    
	    // SET MAPA DE COORDENADAS
	    $this->setMapCoordinates($this->entity,$partId,$this->post);
	    // SET GEOJSON
	    $this->setGeoJSON($this->entity,$partId,$this->post['coordenada_marks']);
	    
	    $sql['data']=array('entityId'=>$partId);
	    
	    // RETORNAR DATOS
	    $this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICACIÓN DE REGISTRO
	 */
	public function updateEntityAsync(){
	    // INICIA TRANSACCIÓN
	    $this->db->begin();
	    
	    // ID DE REGISTRO
	    $partId=$this->post['parte_id'];
	    
	    // VALIDAR EL PASO QUE DESEA ACTUALIZAR
	    if($this->post['step']==1){
	        
	        // PARSE FECHAS
	        $this->post['parte_aviso_hora']=$this->setFormatDate($this->post['parte_aviso_hora'],'Y-m-d H:i');
	        $this->post['parte_aviso_salida_personal']=$this->setFormatDate($this->post['parte_aviso_salida_personal'],'Y-m-d H:i');
	        $this->post['parte_aviso_llegada_personal']=$this->setFormatDate($this->post['parte_aviso_llegada_personal'],'Y-m-d H:i');
	        $this->post['parte_aviso_retorno']=$this->setFormatDate($this->post['parte_aviso_retorno'],'Y-m-d H:i');
	        $this->post['parte_aviso_ingreso_cuartel']=$this->setFormatDate($this->post['parte_aviso_ingreso_cuartel'],'Y-m-d H:i');
	        // LISTA DE APOYO ADICIONAL
	        $this->post['parte_apoyo_adicional']=json_encode($this->post['parte_apoyo_adicional']);
	        
	        // VALIDAR Y CARGAR ARCHIVO
	        $this->post=$this->uploaderMng->decodeBase64ToImg($this->post,$this->entity);
	        // SET MAPA DE COORDENADAS
	        $this->setMapCoordinates($this->entity,$partId,$this->post,false);
	        // SET GEOJSON
	        $this->setGeoJSON($this->entity,$partId,$this->post['coordenada_marks']);
	        
	    }elseif($this->post['step']==2){
	        
	        // LISTA DE SELECCION DEL INCIDENTE
	        $this->post['parte_causa']=json_encode($this->post['causesSelected']);
	        $this->post['parte_lugarinicio']=json_encode($this->post['homeIncidentSelected']);
	        
	    }
	    
	    // REALIZAR INGRESO DE PARTE
	    $sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
	    
	    // DATOS DE ACTUALIZACION
	    $sql['data']=array('entityId'=>$this->post['parte_id']);
	    
	    // RETORNAR DATOS
	    $this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * RECURSOS PARA PARTES
	 */
	private function getResourcesPart($codeId,$codeType){
		// MODELO DE PARTE
		$data=array();
		// FECHA POR DEFECTO
		$date=$this->getFecha('Y-m-d H:i');
		
		// DATOS DE GEOREFERENCIACION
		$data['coordenada_latitud']=-0.2531997068768405;
		$data['coordenada_longitud']=-79.16464805603027;
		$data['coordenada_zoom']=13;
		$data['coordenada_marks']=array();
		
		// DATOS ADICIONALES DEL MODELO
		$data['parte_fecha']=$date;
		$data['parte_aviso_hora']=$this->setFormatDate($date,'Y-m-d 0:0');
		$data['parte_aviso_salida_personal']=$this->setFormatDate($date,'Y-m-d 00:00');
		$data['parte_aviso_llegada_personal']=$this->setFormatDate($date,'Y-m-d 00:00');
		$data['parte_aviso_retorno']=$this->setFormatDate($date,'Y-m-d 0:0');
		$data['parte_aviso_ingreso_cuartel']=$this->setFormatDate($date,'Y-m-d 0:0');
		
		$data['parte_clasedia']=$this->getTypeDayFromDate($data['parte_fecha']);
		$data['parte_apoyo_requerido']='NO';
		$data['fk_state_id']=4121;
		$data['fk_town_id']=2301;
		
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestByKey($code){
		// ID DE SESION
		$sessionId=$this->getStaffIdBySession();
		// OBTENER INFORMACIÓN DE PERSONA
		$str=$this->db->selectFromView('codigosinstitucionales',"WHERE UPPER(codigo_clave)=UPPER('{$code}')");
		// INGRESAR DATOS SI EXISTE REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("No se ha registrado una clave válida...");
		// MODELO DE CONSULTA
		$data=$this->db->findOne($str);
		// ID DE CODIGO DE CONSULTA
		$data['fk_codigo_id']=$data['codigo_id'];
		$data['toogleEditMode']=false;
		
		// DATOS DE PELOTON
		$str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$sessionId}");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// DATOS DEL REGISTRO
			$platoon=$this->db->findOne($str);
			// REGISTRO DE PELOTÓN
			$data['fk_estacion_id']=$platoon['fk_estacion_id'];
			$data['fk_peloton_id']=$platoon['fk_peloton_id'];
			$data['fk_tropa_id']=$platoon['tropa_id'];
		}
		
		// RECURSOS PARA EL PARTE
		$data=array_merge($this->getResourcesPart($data['codigo_id'],$data['codigo_tipo']),$data);
		
		// RETORNO DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR LA CLAVE DEL PARTE PARA PROCEDER AL APARTADO - new/part/:clave
	 */
	private function requestByCode($code){
		// OBTENER INFORMACIÓN DE PERSONA
		$str=$this->db->selectFromView('codigosinstitucionales',"WHERE UPPER(codigo_clave)=UPPER('{$code}')");
		// INGRESAR DATOS SI EXISTE REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("No se ha registrado una clave válida...");
		// RETORNO DE CONSULTA
		return $this->db->findOne($str);
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestById($id){
	    // OBTENER DATOS DE REGISTRO
	    $data=$this->db->viewById($id,$this->entity);
	    $data['toogleEditMode']=true;
	    
	    // PARSE DATOS - STRING2JSON
	    $data['causesSelected']=$this->string2JSON($data['parte_causa']);
	    $data['homeIncidentSelected']=$this->string2JSON($data['parte_lugarinicio']);
	    
	    // LISTA DE LAYERS
	    $data['coordenada_marks']=$this->getGeoJSON($this->entity,$data['parte_id']);
	    
	    // PARSE FECHAS
	    $data['parte_aviso_hora']=$this->setFormatDate($data['parte_aviso_hora']);
	    $data['parte_aviso_salida_personal']=$this->setFormatDate($data['parte_aviso_salida_personal']);
	    $data['parte_aviso_llegada_personal']=$this->setFormatDate($data['parte_aviso_llegada_personal']);
	    $data['parte_aviso_retorno']=$this->setFormatDate($data['parte_aviso_retorno']);
	    $data['parte_aviso_ingreso_cuartel']=$this->setFormatDate($data['parte_aviso_ingreso_cuartel']);
	    // RETORNAR CONSULTA
	    return $data;
	}
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestByIdAndStep($id,$step){
	    // OBTENER DATOS DE REGISTRO
	    $data=$this->db->viewById($id,$this->entity);
	    
	    $data['toogleEditMode']=true;
	    
	    // VALIDAR CONSULTA 
	    if($step==1){
	        
	        $data['parte_apoyo_adicional']=$this->string2JSON($data['parte_apoyo_adicional']);
	        
	    }elseif($step==2){
	        
	        // DATOS ADICIONALES DEL MODELO
	        $data['natureSelected']=array();
	        
	        // PARSE DATOS - STRING2JSON
	        $data['causesSelected']=$this->string2JSON($data['parte_causa']);
	        $data['homeIncidentSelected']=$this->string2JSON($data['parte_lugarinicio']);
	        
	        // NATURALEZA DEL INCIDENTE
	        $data['natureList']=array();
	        // CONSULTAR LISTA DE NATURALEZA DE INCIDENTE
	        $strCodes=$this->db->getSQLSelect($this->db->setCustomTable('naturaleza_claves','fk_naturaleza_id'),[],"WHERE fk_clave_id={$data['fk_codigo_id']}");
	        foreach($this->db->findAll($this->db->selectFromView('naturalezaincidente',"WHERE naturaleza_id in ({$strCodes})")) as $v){
	            // PARSE ATRIBUTO
	            $v['naturaleza_recursos']=$this->string2JSON($v['naturaleza_recursos']);
	            // INSERTAR RECURSO A LISTADO
	            $data['natureList'][$v['naturaleza_id']]=$v;
	        }
	        
	        // VALIDAR SI SE HA ENCONTRADO RELACION
	        if(empty($data['natureList'])){
	            foreach($this->db->findAll($this->db->selectFromView('naturalezaincidente')) as $v){
	                $v['naturaleza_recursos']=$this->string2JSON($v['naturaleza_recursos']);
	                $data['natureList'][$v['naturaleza_id']]=$v;
	            }
	        }
	        
	        // CAUSAS DEL INCIDENTE
	        $data['causesList']=array();
	        // INSERTAR CAUSAS SEGÚN LA CLASIFICACIÓN
	        foreach($this->db->findAll($this->db->selectFromView('vw_naturaleza_recursos',"WHERE recurso_tipo='CAUSAS DEL INCIDENTE' ORDER BY recurso_nombre")) as $v){
	            // VALIDAR LA EXISTENCIA DE LA CLASIFICACIÓN
	            if(!isset($data['causesList'][$v['fk_naturaleza_id']])) $data['causesList'][$v['fk_naturaleza_id']]=array();
	            // REGISTRAR RECURSOS
	            $data['causesList'][$v['fk_naturaleza_id']][]=$v;
	        }
	        
	        // LUGAR DEL POSIBLE INICIO
	        $data['homeIncidentList']=array();
	        // INSERTAR CAUSAS SEGÚN LA CLASIFICACIÓN
	        foreach($this->db->findAll($this->db->selectFromView('vw_naturaleza_recursos',"WHERE recurso_tipo='LUGAR DEL POSIBLE INICIO' ORDER BY recurso_nombre")) as $v){
	            // VALIDAR LA EXISTENCIA DE LA CLASIFICACIÓN
	            if(!isset($data['homeIncidentList'][$v['fk_naturaleza_id']])) $data['homeIncidentList'][$v['fk_naturaleza_id']]=array();
	            // REGISTRAR RECURSOS
	            $data['homeIncidentList'][$v['fk_naturaleza_id']][]=$v;
	        }
	        
	    }elseif($step==3){
	        
	        // LUGAR DEL POSIBLE INICIO
	        $data['suppliesaphtList']=array();
	        // INSERTAR CAUSAS SEGÚN LA CLASIFICACIÓN
	        foreach($this->db->findAll($this->db->selectFromView('vw_insumosaph_stock_estaciones',"WHERE bodegaId<>9 AND estacionId={$data['fk_estacion_id']} AND stock>0 ORDER BY insumo_nombre")) as $v){
	            // VALIDAR LA EXISTENCIA DE LA CLASIFICACIÓN
	            $v['stock']=intval($v['stock']);
	            $data['suppliesaphtList'][$v['insumoid']]=$v;
	        }
	        
	    }
	    
	    // RETORNAR CONSULTA
	    return $data;
	}
	
	/*
	 * CONSULTA DE PROYECTOS
	 */
	public function requestEntity(){
		// VERIFICAR
	    if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
	    // ID DE ENTIDAD
	    elseif(isset($this->post['entityId']) && isset($this->post['step'])) $json=$this->requestByIdAndStep($this->post['entityId'],$this->post['step']);
	    // CLAVE DE PARTE
	    elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CLAVE DE PARTE
		elseif(isset($this->post['key'])) $json=$this->requestByKey($this->post['key']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * INSERTAR RELACION CON FLOTAS VEHICULARES
	 */
	public function setTracking(){
		// INICIA TRANSACCIÓN
		$this->db->begin();
		
		// ELIMINAR REGISTROS PASADO
		$sql=$this->db->executeTested($this->db->getSQLDelete('partes_flotas',"WHERE fk_parte_id={$this->post['partId']}"));
		
		// RECORRER LISTADO DE FLOTAS SELECCIONADAS PARA INGRESAR 
		foreach($this->post['selected'] as $v){
			// GENERAR MODELO AUXILIAR PARA INGRESO
			$aux=array(
				'fk_parte_id'=>$this->post['partId'],
				'fk_flota_id'=>$v
			);
			// GENERAR SENTENCIA SQL Y EJECUTAR CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($aux,'partes_flotas'));
		}
		
		// RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
		
	}



	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE parte_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// PARSE FECHAS
			$row['parte_fecha_alt']=$this->setFormatDate($row['parte_fecha'],'complete');
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}

}
