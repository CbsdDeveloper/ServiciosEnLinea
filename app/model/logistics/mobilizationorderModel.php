<?php namespace model\logistics;
use model\subjefature as sub;

class mobilizationorderModel extends sub\trackingModel {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='ordenesmovilizacion';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
	 * PARSE DATA - INSERTAR REGISTRO FLOTA VEHICULAR
	 */
	private function setTrackingRecord($post){
		// GENERAR MODELO DE FLOTA VEHICULAR
		$model=array(
			'flota_estado'=>'SALIDA',
			'fk_personal_id'=>$this->getStaffIdBySession(),
			'fk_codigo_id'=>47,
			'fk_unidad_id'=>$post['fk_unidad_id'],
			'unidad_id'=>$post['fk_unidad_id'],
			'unidad_nombre'=>$post['unidad_nombre'],
			'estacion_nombre'=>$post['estacion_nombre'],
			'flota_salida_estacion'=>1,
			'flota_salida_conductor'=>$post['operador_id'],
			'flota_salida_tipo'=>$post['orden_hora_salida_tipo'],
			'flota_salida_hora'=>$post['orden_hora_salida'],
			'flota_salida_km'=>$post['orden_kilometraje_salida'],
			'flota_destino'=>$post['orden_destino'],
			'flota_observacion'=>"REGISTRO DESDE ORDEN DE MOVILIZACION {$post['orden_serie']}",
			'passengers'=>array()
		);
		// RETORNAR CONSULTA
		return $this->insertData($model);
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */ 
	public function insertEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRO DE SALIDA - HORARIO
		if($this->post['orden_hora_salida_tipo']=='SISTEMA') $this->post['orden_hora_salida']=$this->getFecha('dateTime');
		// VERIFICAR/GENERAR NÚMERO DE ORDEN DE MANTENIMIENTO
		if($this->post['orden_serie']==0) $this->post['orden_serie']=$this->db->getNextSerie($this->entity);
		// INSERTAR DATOS DE FLOTAS
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// RETORNAR DATOS DE REGISTRO
		$sql['data']=$this->db->getLastEntity($this->entity);
		
		// INSERTAR REGISTRO DE FLOTA VEHICULAR
		$sql=$this->setTrackingRecord($this->post);
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * PARSE DATA - INSERTAR REGISTRO FLOTA VEHICULAR
	 */
	private function closeTrackingRecord($data){
	    
	    // DATOS DEL ÚLTIMO MIVIMIENTO REGISTRADO
	    $strOld=$this->db->selectFromView('flotasvehiculares',"WHERE fk_unidad_id={$data['fk_unidad_id']} ORDER BY flota_id DESC");
	    // SI EXISTE ALGÚN REGISTRO OBTENER EL KM DE ENTRADA, SINO INICIAR EN 0
	    if($this->db->numRows($strOld)>0){
	        
	        // OBTENER DATOS DE MOVIMIENTO
	        $info=$this->db->findOne($strOld);
	        
	        // VALIDAR ESTADO
	        if($info['flota_estado']=='SALIDA'){
	            
	            // GENERAR MODELO DE FLOTA VEHICULAR
	            $info['flota_estado']='ESTACION';
	            $info['flota_arribo_estacion']=1;
	            
	            $info['flota_arribo_conductor']=$info['flota_salida_conductor'];
	            
	            $info['flota_arribo_tipo']=$data['orden_hora_entrada_tipo'];
	            $info['flota_arribo_hora']=$data['orden_hora_entrada'];
	            $info['flota_arribo_km']=$data['orden_kilometraje_entrada'];
	            
	            // RETORNAR CONSULTA
	            return $this->db->executeTested($this->db->getSQLUpdate($info,'flotasvehiculares'));
	            
	        }
	        
	    }
	    
	    // RETORNAR CONSULTA POR DEFECTO
	    return $this->setJSON("Ok",true);
	    
	}
	
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO
	 */
	public function updateEntity(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRO DE ARRIBO - HORARIO
		if($this->post['orden_hora_entrada_tipo']=='SISTEMA') $this->post['orden_hora_entrada']=$this->getFecha('dateTime');
		// ACTUALIZAR DATOS DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// INSERTAR REGISTRO DE FLOTA VEHICULAR
		$sql=$this->closeTrackingRecord($this->post);
		
		// RETORNAR DATOS DE REGISTRO
		$sql['data']=$this->post;
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZACIÓN DE REGISTRO - ANULAR ORDEN
	 */
	public function deleteEntity(){
		
		// GENERAR MODELO TEMPORAL
		$temp=array(
		    'orden_id'=>$this->post['id'],
		    'orden_estado'=>'ORDEN ANULADA'
		);
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->executeTested($this->db->getSQLUpdate($temp,$this->entity)));
	}
	
	
	/*
	 * OBTENER RESPONSABLES
	 */
	private function getCompleteData($optionNew=true,$orderId=0){
		// VALIDAR OPCIÓN DE NUEVO REGISTRO
		if($optionNew){
			// OBTENER INFORMACIÓN DE DIRECTOR ADMINISTRATIVO: 4=DIRECTOR ADMINISTRATIVO
			$strAdministrative=$this->db->selectFromView('vw_personal',"WHERE puesto_id=4 AND ppersonal_estado='EN FUNCIONES'");
			// VALIDAR EXISTENCIA DE DIRECTOR ADMINISTRATIVO
			if($this->db->numRows($strAdministrative)<1) $this->getJSON("No se puede continuar con la transacción debido a que no se ha registrado al responsable de la Dirección Administrativa.");
			// OBTENER INFORMACIÓN DE PROFESIONAL DE SERVICIOS GENERALES
			$strProfessional=$this->db->selectFromView('vw_personal',"WHERE puesto_id=60 AND ppersonal_estado='EN FUNCIONES'");
			// VALIDAR EXISTENCIA DE PROFESIONAL DE SERVICIOS GENERALES
			if($this->db->numRows($strProfessional)<1) $this->getJSON("No se puede continuar con la transacción debido a que no se ha registrado al Analista de Servicios Generales.");
		}else{
			// DATOS DE REGISTRO
			$row=$this->db->findById($orderId,$this->entity);
			// OBTENER INFORMACIÓN DE DIRECTOR ADMINISTRATIVO: 4=DIRECTOR ADMINISTRATIVO
			$strAdministrative=$this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['director_administrativo']}");
			// OBTENER INFORMACIÓN DE PROFESIONAL DE SERVICIOS GENERALES
			$strProfessional=$this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['tecnico_servicios']}");
		}
		// RETORNAR DATOS
		return array(
			'administrative'=>$this->db->findOne($strAdministrative),
			'professional'=>$this->db->findOne($strProfessional),
			'orden_kilometraje_salida'=>$this->getLastRecords($this->unitModel['unidad_id'])
		);
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 */
	private function requestById($id){
		// DATOS DE REGISTRO
		$info=$this->db->viewById($id,$this->entity);
		// VALIDAR DATOS DE UNIDAD
		$this->validateUnitByCode($info['unidad_nombre']);
		// OBTENER DATOS DE RESPONSABLES
		$info=array_merge($info,$this->getCompleteData(false,$id));
		// RETORNAR DATOS DE CONSULTA
		return array('data'=>$info);
	}
	
	/*
	 * CONSULTAR MODELO POR UNIDAD
	 */
	private function requestByUnit($code){
	    
		// VALIDAR DATOS DE UNIDAD
		$unit=$this->validateUnitByCode($code);
		
		// MODELO TEMPORAL DE INFORMACIÓN
		$info=array(
		    'actionsList'=>array(),
		    'orden_estado'=>'SALIDA',
		    'orden_serie'=>0,
		    'edit'=>false,
		    'fk_unidad_id'=>$unit['unidad_id']
		);
		
		// STRING PARA CONSULTAR EXISTENCIA DE MANTENIMIENTO EN PROCESO
		$str=$this->db->selectFromView('ordenesmovilizacion',"WHERE fk_unidad_id={$unit['unidad_id']} ORDER BY orden_id DESC");
		// VALIDAR LA EXISTENCIA DE UN MANTENIMIENTO EN PROCESO
		if($this->db->numRows($str)>0){
			// OBTENER EL REGISTRO
		    $record=$this->db->findOne($str);
		    // VALIDAR EL ESTADO DEL REGISTRO
		    if($record['orden_estado']=='SALIDA'){
    			// OBTENER DATOS DE MANTENIMIENTO
		        $info=array_merge($info,$record);
    			// OBTENER DATOS DE RESPONSABLES
    			$info=array_merge($info,$this->getCompleteData(false,$info['orden_id']));
    			// ACTUALIZAR ESTADO DEL REGISTRO
    			if($info['orden_estado']=='SALIDA') $info['orden_estado']='ESTACION';
    			// DATOS DE FORMULARIO
    			unset($info['edit']);
		    }else{
		        // OBTENER DATOS DE RESPONSABLES
		        $info=array_merge($info,$this->getCompleteData());
		        // DATOS COMPLEMENTARIOS DEL FORMULARIO
		        $info['fk_unidad_id']=$unit['unidad_id'];
		        $info['tecnico_servicios']=$info['professional']['ppersonal_id'];
		        $info['director_administrativo']=$info['administrative']['ppersonal_id'];
		    }
		}else{
		    // OBTENER DATOS DE RESPONSABLES
		    $info=array_merge($info,$this->getCompleteData());
		    // DATOS COMPLEMENTARIOS DEL FORMULARIO
		    $info['fk_unidad_id']=$unit['unidad_id'];
		    $info['tecnico_servicios']=$info['professional']['ppersonal_id'];
		    $info['director_administrativo']=$info['administrative']['ppersonal_id'];
		}
		
		// MODELO DE RETORNO 
		return array(
			'data'=>array_merge($unit,$info),
			'modal'=>'Ordenesmovilizacion'
		);
	}
	
	/*
	 * CONSULTAR PUESTOS Y DIRECCIONES
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// MODELO PARA CONSULTAR POR VEHÍCULO
		elseif(isset($this->post['code'])) $json=$this->requestByUnit($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			// PARSE NÚMERO DE ORDEN
			$row['orden_serie']=str_pad($row['orden_serie'],3,"0",STR_PAD_LEFT);
			// PARSE FECHA
			$row['orden_hora_salida']=$this->setFormatDate($row['orden_hora_salida'],'full');
			
			// OBTENER DATOS DEL PERSONAL
			$operator=$this->db->findById($row['operador_id'],'conductores');
			// PUESTO DE OPERADOR
			$personalOperator=$this->db->findOne($this->db->selectFromView("vw_personal","WHERE personal_id={$operator['fk_personal_id']} LIMIT 1"));
			$row['o_puesto']=$personalOperator['puesto_definicion'];
			
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
	
}