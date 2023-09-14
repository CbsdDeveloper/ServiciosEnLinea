<?php namespace model\logistics;
use model\subjefature as sub;

class workorderModel extends sub\trackingModel {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='ordenestrabajo';
	
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
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("No se ha registrado ningún dato!");
		
		// VALIDAR SI ES UN INGRESO MULTIPLE
		if(isset($this->post['selected'])){
			// GENERAR MODELO TEMPORAL
			$model=array(
				'director_administrativo'=>$this->post['director_administrativo'],
				'tecnico_servicios'=>$this->post['tecnico_servicios'],
				'tecnico_puesto'=>$this->post['tecnico_puesto'],
				'orden_tipo'=>$this->post['orden_tipo'],
				'orden_fecha_emision'=>$this->post['orden_fecha_emision'],
				'orden_fecha_validez'=>$this->post['orden_fecha_validez'],
				'orden_destino'=>$this->post['orden_destino'],
				'orden_estado'=>$this->post['orden_estado']
			);
			// RECORRER LISTADO DE UNIDADES
			foreach($this->post['selected'] as $u){
				// ESTACION DE LA UNIDAD
				$unit=$this->db->findById($u,'unidades');
				// ESTACION DE SALIDA
				$model['fk_unidad_id']=$unit['unidad_id'];
				$model['fk_estacion_id']=$unit['fk_estacion_id'];
				// SERIE DE ORDEN
				$model['orden_serie']=$this->db->getNextSerie($this->entity);
				// INSERTAR DATOS DE FLOTAS
				$sql=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
			}
		}else{
			// VERIFICAR/GENERAR NÚMERO DE ORDEN DE MANTENIMIENTO
			if($this->post['orden_serie']==0) $this->post['orden_serie']=$this->db->getNextSerie($this->entity);
			// INSERTAR DATOS DE FLOTAS
			$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
			// VALIDAR ESTADO DE INGRESO DE NUEVO REGISTRO
			if($sql['estado']) $sql['data']=$this->db->getLastEntity($this->entity);
		}
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
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
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * OBTENER RESPONSABLES
	 */
	private function getCompleteData($optionNew=true,$orderId=0){
		// TRABAJOS A REALIZAR
		$actionsList=array();
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
			'professional'=>$this->db->findOne($strProfessional)/*,
			'orden_kilometraje_salida'=>$this->getLastRecords($this->unitModel['unidad_id'])*/
		);
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 */
	private function requestById($id){
		// DATOS DE REGISTRO
		$info=$this->db->viewById($id,$this->entity);
		// OBTENER DATOS DE RESPONSABLES
		$info=array_merge($info,$this->getCompleteData(false,$id));
		// RETORNAR DATOS DE CONSULTA
		return array('data'=>$info);
	}
	
	/*
	 * CONSULTAR MODELO POR UNIDAD
	 */
	private function requestByUnit($code){
		// MODELO TEMPORAL DE INFORMACIÓN
		$info=array(
			'orden_estado'=>'EMITIDA',
			'orden_destino'=>'LAVADORA CEDEÑO',
			'orden_tipo'=>'LAVADA COMPLETA',
			'orden_fecha_emision'=>$this->getFecha('Y-m-d H:i'),
			'orden_fecha_validez'=>$this->setFormatDate($this->getFecha(),'Y-m-t'),
			'orden_serie'=>0
		);
		
		// VALIDAR DATOS DE UNIDAD
		$unit=$this->validateUnitByCode($code);
		// STRING PARA CONSULTAR EXISTENCIA DE MANTENIMIENTO EN PROCESO
		// $str=$this->db->selectFromView('ordenestrabajo',"WHERE fk_unidad_id={$unit['unidad_id']} AND orden_estado IN ('EMITIDA')");
		// VALIDAR LA EXISTENCIA DE UN MANTENIMIENTO EN PROCESO
		/*
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE MANTENIMIENTO
			$info=array_merge($info,$this->db->findOne($str));
			// OBTENER DATOS DE RESPONSABLES
			$info=array_merge($info,$this->getCompleteData(false,$info['orden_id']));
			// ACTUALIZAR ESTADO DEL REGISTRO
			if($info['orden_estado']=='EMITIDA') $info['orden_estado']='VALIDADA';
		}else{
			*/
			// DATOS DE FORMULARIO
			$info['edit']=false;
			// OBTENER DATOS DE RESPONSABLES
			$info=array_merge($info,$this->getCompleteData());
			// DATOS COMPLEMENTARIOS DEL FORMULARIO
			$info['fk_unidad_id']=$unit['unidad_id'];
			$info['tecnico_servicios']=$info['professional']['ppersonal_id'];
			$info['director_administrativo']=$info['administrative']['ppersonal_id'];
		// }
		// MODELO DE RETORNO 
		return array(
			'data'=>array_merge($unit,$info),
			'modal'=>'Ordenestrabajo'
		);
	}
	
	/*
	 * CONSULTA POR LISTADO
	 */
	private function requestByList(){
		// MODELO TEMPORAL DE INFORMACIÓN
		$info=array(
			'orden_estado'=>'EMITIDA',
			'orden_destino'=>'LAVADORA CEDEÑO',
			'orden_tipo'=>'LAVADA COMPLETA',
			'orden_fecha_emision'=>$this->getFecha('Y-m-d H:i'),
			'orden_fecha_validez'=>$this->setFormatDate($this->getFecha(),'Y-m-t'),
			'selected'=>array()
		);
		
		// OBTENER DATOS DE RESPONSABLES
		$info=array_merge($info,$this->getCompleteData());
		// DATOS COMPLEMENTARIOS DEL FORMULARIO
		$info['tecnico_servicios']=$info['professional']['ppersonal_id'];
		$info['director_administrativo']=$info['administrative']['ppersonal_id'];
		
		// MODELO DE RETORNO
		return $info;
	}
	
	/*
	 * CONSULTAR PUESTOS Y DIRECCIONES
	 */
	public function requestEntity(){
		// CONSULTAR POR ID
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// MODELO PARA CONSULTAR CODIGO
		elseif(isset($this->post['code'])) $json=$this->requestByUnit($this->post['code']);
		// OBTENER INFORMACION DE PROCESOS
		elseif(isset($this->post['getInfo'])) $json=$this->requestByList();
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// PLANTILLA DE REPORTE
		$templateReport="";
		
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id IN ({$this->get['id']}) ORDER BY orden_id DESC");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// RECORRER LISTA
			foreach($this->db->findAll($str) as $row){
				// ADJUNTAR PLANTILLA
				$aux=$this->varGlobal['TEMPLATE_SOLICITUD_ORDENESTRABAJO_TEMPLATE'];
				// PARSE NÚMERO DE ORDEN
				$row['orden_serie']=str_pad($row['orden_serie'],3,"0",STR_PAD_LEFT);
				// PARSE FECHA
				$row['orden_fecha_emision']=$this->setFormatDate($row['orden_fecha_emision'],'full');
				$row['orden_fecha_validez']=$this->setFormatDate($row['orden_fecha_validez'],'complete');
				// INERTAR REGISTROS EN ORDEN
				foreach($row as $k=>$v){ $aux=preg_replace('/{{'.$k.'}}/',$v,$aux); }
				// TEMPLATE
				$auxTemp=preg_replace('/{{TEMPLATE_SOLICITUD_ORDENESTRABAJO_TEMPLATE}}/',$aux,$this->varGlobal['TEMPLATE_SOLICITUD_ORDENESTRABAJO_TD']);
				$auxTemp=preg_replace('/{{orden_codigo}}/',$row['orden_codigo'],$auxTemp);
				// INSERTAR REGISTRO DE ORDEN
				$templateReport.=preg_replace('/{{TEMPLATE_SOLICITUD_ORDENESTRABAJO_TD}}/',$auxTemp,$this->varGlobal['TEMPLATE_SOLICITUD_ORDENESTRABAJO']);
			}
		}
			
		// CARGAR DATOS DE REGISTRO
		$pdfCtrl->loadSetting(array('templateReport'=>$templateReport));
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			// PARSE NÚMERO DE ORDEN
			$row['orden_serie']=str_pad($row['orden_serie'],3,"0",STR_PAD_LEFT);
			// PARSE FECHA
			$row['orden_fecha_emision']=$this->setFormatDate($row['orden_fecha_emision'],'full');
			$row['orden_fecha_validez']=$this->setFormatDate($row['orden_fecha_validez'],'complete');
			
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
	 * IMPRIMIR REGISTRO POR ID A 2 REGISTROS POR HOJA
	 */
	public function printById1($pdfCtrl,$id,$config){
		// PLANTILLA DE REPORTE
		$templateReport="";
		
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE orden_id IN ({$id}) ORDER BY orden_id DESC");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// RECORRER LISTA
			foreach($this->db->findAll($str) as $row){
				// ADJUNTAR PLANTILLA
				$aux=$this->varGlobal['TEMPLATE_SOLICITUD_ORDENESTRABAJO_TEMPLATE'];
				// PARSE NÚMERO DE ORDEN
				$row['orden_serie']=str_pad($row['orden_serie'],3,"0",STR_PAD_LEFT);
				// PARSE FECHA
				$row['orden_fecha_emision']=$this->setFormatDate($row['orden_fecha_emision'],'full');
				$row['orden_fecha_validez']=$this->setFormatDate($row['orden_fecha_validez'],'complete');
				// INERTAR REGISTROS EN ORDEN
				foreach($row as $k=>$v){ $aux=preg_replace('/{{'.$k.'}}/',$v,$aux); }
				// TEMPLATE
				$auxTemp=preg_replace('/{{TEMPLATE_SOLICITUD_ORDENESTRABAJO_TEMPLATE}}/',$aux,$this->varGlobal['TEMPLATE_SOLICITUD_ORDENESTRABAJO_TD']);
				$auxTemp=preg_replace('/{{orden_codigo}}/',$row['orden_codigo'],$auxTemp);
				// INSERTAR REGISTRO DE ORDEN
				$templateReport.=preg_replace('/{{TEMPLATE_SOLICITUD_ORDENESTRABAJO_TD}}/',$auxTemp,$this->varGlobal['TEMPLATE_SOLICITUD_ORDENESTRABAJO']);
			}
		}
			
		// CARGAR DATOS DE REGISTRO
		$pdfCtrl->loadSetting(array('templateReport'=>$templateReport));
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		$pdfCtrl->setCustomHeader($row,$this->entity);
		return '';
	}
	
}