<?php namespace model\permits;
use controller as ctrl;
use api as app;

class duplicatedModel extends app\controller {
	
	private $entity='duplicados';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESO DE NUEVOS REGISTROS
	 */
	public function insertDuplicated(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// VALIDAR NÚMERO DE SOLICITUD
		if(!isset($this->post['duplicado_estado']) && $this->varGlobal['REQUIRED_FORM_DUPLICATES']=='SI') $data['duplicado_solicitud']=$this->testRequestNumber($this->entity,$this->post['duplicado_solicitud']);
		// STRING PARA CONSULTAR DUPLICADOS PENDIENTES
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE fk_permiso_id={$this->post['permiso_id']} AND duplicado_estado='PENDIENTE'");
		// COMPROBAR SI PERMISO PENDIENTE SINO GENERAR LA SOLICITUD
		if($this->db->numRows($str)>0){
			// OBTENER DUPLICADO
			$data=$this->db->findOne($str);
			// ACTUALIZAR ESTADO DE DUPLICADO
			$data['duplicado_estado']='APROBADO';
			// ACTUALIZAR USUARIO QUE APRUEBA DUPLICADO
			$data['fk_usuario_aprueba']=app\session::get();
			// ACTUALIZAR FECHA QUE SE APRUEBA
			$data['fecha_aprobado']=$this->getFecha('Y-m-d H:i:s');
			
			// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
			$data['jtp_aprueba']=$this->varGlobal['RUPIF_PPERSONAL_ID'];
			
			// GENERAR STRING DE ACTUALIZACION Y EJECUTAR CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLUpdate($data,$this->entity));
		}else{
			// DECLARAR MODELO DE DUPLICADO
			$data=array(
				'fk_permiso_id'=>$this->post['permiso_id'],
				'duplicado_detalle'=>$this->post['duplicado_detalle'],
				'duplicado_solicitud'=>$this->post['duplicado_solicitud'],
				'fk_usuario_solicita'=>app\session::get(),
				'duplicado_precio'=>$this->varGlobal['SYS_VALUE_DUPLICATES'],
				// INSERTAR RESPONSABLE DE PREVENCION - SOLICITUD
				'jtp_solicitud'=>$this->varGlobal['RUPIF_PPERSONAL_ID']
			);
			// GENERAR STRING DE ACTUALIZACION Y EJECUTAR CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($data,$this->entity));
		}	
		// CERRAR TRANSACCION Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 * -- SOLICITUD DE DUPLICADOS
	 */
	public function printDetail($pdfCtrl,$config){
		// RECIBIR DATOS GET - URL
		$get=$this->requestGet();
		// STRING DE CONSULTA PARA DUPLICADOS
		$str=$this->db->selectFromView("vw_permisos","WHERE fk_autoinspeccion_id={$get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO MEDIANTE ID DE AUTOINSPECCION
		if($this->db->numRows($str)<1) $this->getJSON("Aun no se ha emitido este permiso de funcionamiento!");
		// DATOS DE PERMISO
		$row=$this->db->findOne($str);
		// CARGAR DATOS A REPORTE
		$pdfCtrl->loadSetting($row);
		// CARGAR DATOS DE ACTIVIDAD ECONOMICA
		$pdfCtrl->loadSetting($this->db->viewById($row['local_id'],'locales'));
		// CARGAR DATOS DE CIIU
		$pdfCtrl->loadSetting($this->db->viewById($row['fk_ciiu_id'],'ciiu'));
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		// GET BARCODE
		$pdfCtrl->setBQCode('barcode',$row['codigo_per']);
		return '';
	}
	
}