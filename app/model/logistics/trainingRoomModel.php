<?php namespace model\logistics;
use api as app;

class trainingRoomModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='salacapacitaciones';
	private $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// MODELO CONSTRUCTOR
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'logistics');
	}
	
	
	/*
	 * PARAMETRIZAR TIPO DE DÍAS
	 */
	public function setCustomDays($data){
		// MODEL
		$model=$this->config['calendarEntity'][$this->entity];
		// TEMPLATE HTML PARA PRESENTACIÓN
		$template=file_get_contents(VIEWS_PATH."logistics/trainingroom.html");
		// ESTADO DEL MODELO
		$status=$data[$model['status']];
		// COLOR DE MODELO
		$template=preg_replace('/{{status}}/',$this->config['statusParam'][$status],$template);
		// ÍCONO DE MODELO
		$template=preg_replace('/{{iconStatus}}/',$this->config['statusIcon'][$status],$template);
		// ÍCONO DE MODELO
		$template=preg_replace('/{{icon}}/',$model['icon'],$template);
		// REEMPLAZAR OTROS PARÁMETROS
		foreach($model as $k=>$v){
			if(isset($data[$v])) $template=preg_replace('/{{'.$k.'}}/',$data[$v],$template);
			else $template=preg_replace('/{{'.$k.'}}/',$v,$template);
		}	return $template;
	}
	
	/*
	 * LISTADO DE REGISTROS DEL AÑO
	 */
	public function getListApplications(){
		// LISTA DE CAPACITACIONES AGENDADAS
		$lis=$this->db->findAll($this->db->selectFromView("vw_{$this->entity}","ORDER BY reservacion_fecha_inicio"));
		
		// MODELO AUXILIAR
		$info=array();
		// RECORRER LISTADO DE RESERVACIONES
		foreach($lis as $k=>$v){
			// PASE DATE
			$date=$this->setFormatDate($v['reservacion_fecha_inicio'],'Y-m-d');
			// FECHA DE REGISTRO
			$v['reservacion_fecha']=$date;
			$v['responsable']='sada';
			// VALIDAR REGISTRO DE FECHAS
			if(!isset($info[$date])) $info[$date]="";
			// INGRESAR REGISTRO
			$info[$date].=$this->setCustomDays($v);
		}
		
		// PARSE MODAL
		$data=array();
		foreach($info as $k=>$v){$data[$k]=array(array('name'=>$v));}
		// RETORNAR DATOS
		$this->getJSON(array('info'=>$data));
	}
	
	
	/*
	 * CONSULTAR DISPONIBILIDAD POR FECHA
	 */
	private function requestByDate($date){
		// FECHA ACTUAL
		$today=$this->getFecha();
		// VALIDAR POR FECHAS PASADAS
		if(strtotime($date) < strtotime($today)) $this->getJSON("Horario seleccionado inválido, favor seleccione un horario posterior a <b>{$today}</b>");
		
		// INSTANCIA DE WHATSAPP
// 		$ws= new ctrl\whatsappApiController();
// 		$sql=$ws->setRegistration();
// 		$sql=$ws->setCodeRegistration('');
// 		$sql=$ws->checkCredentials();
// 		$sql=$ws->sendMessage("593986865422","Horario seleccionado inválido, favor seleccione un horario posterior a *{$today}*");
// 		$this->getJSON($this->setJSON("msg",false,$sql));
		
		
		// INSTANCIA DE WHATSAPP - WHATSMATE
// 		$ws= new ctrl\whatsMateController();
// 		$sql=$ws->sendMessage('Holassssssssss','593999047145');
// 		$this->getJSON($this->setJSON("msg",false,$sql));
		
		
		// INSTANCIA DE WHATSAPP - TWILIO
// 		$ws= new ctrl\whatsappController();
// 		$sql=$ws->sendMessage("+593984281329","Envía una nueva captura!");
// 		$this->getJSON($this->setJSON("msg",false,$sql));
		
		
		// MODELO DE CONSULTA
		$model=array(
			'edit'=>true,
			'reservacion_fecha_inicio'=>$date,
			'reservacion_fecha_cierre'=>$date,
			'minDate'=>$this->getFecha('Y-m-d H:i')
		);
		// OBTENER INFORMACIÓN DE DIRECTOR ADMINISTRATIVO: 4=DIRECTOR ADMINISTRATIVO
		$strAdministrative=$this->db->selectFromView('vw_personal',"WHERE puesto_id=4 AND ppersonal_estado='EN FUNCIONES'");
		// VALIDAR EXISTENCIA DE DIRECTOR ADMINISTRATIVO
		if($this->db->numRows($strAdministrative)<1) $this->getJSON("No se puede continuar con la transacción debido a que no se ha registrado al responsable de la Dirección Administrativa.");
		// OBTENER DATOS DEL DIRECTOR ADMINISTRATIVO
		$model['administrative']=$this->db->findOne($strAdministrative);
		$model['director_administrativo']=$model['administrative']['ppersonal_id'];
		
		// RETORNAR DATOS DE CONSULTA
		return $model;
	}
	
	/*
	 * CONSULTAR DISPONIBILIDAD POR FECHA
	 */
	private function requestByCode($code){
		// GENERAR STRING DE CONSULTA DE SOLICITUD POR CODIGO
		$str=$this->db->selectFromView($this->entity,"WHERE UPPER(reservacion_codigo)=UPPER('{$code}')");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// FECHA ACTUAL
			$today=$this->getFecha('dateTime');
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			
			// VALIDAR ESTADO DE LA SOLICITUD
			if($row['reservacion_estado']=='APROBADO') $this->getJSON('Procedimiento no permitido, esta solicitud ya ha sido aprobada!');
			
			// VALIDAR SI LA FECHA DE SOLICITUD NO HA SIDO PASADA
			// if(strtotime($row['reservacion_fecha_inicio']) < strtotime($today)) $this->getJSON("Esta solicitud no se puede validar puesto que la fecha solicitada es inferior a la actual.");
			
			// VALIDAR FECHA DE CONFIRMACION
			if($row['reservacion_confirmacion']<$today || $row['reservacion_estado']=='ANULADA'){
				// VALIDAR ROL PARA CONFIRMAR SOLICITUD VENCIDA
				if(!in_array(72151,$this->getAccesRol())) $this->getJSON("No se puede validar la confirmación, ha superado su fecha de confirmación <b>{$row['reservacion_confirmacion']}</b>, favor comuníquese con el administrador!");
			}else{
				// VALIDAR ROL PARA CONFIRMAR SOLICITUD
				if(!in_array(7215,$this->getAccesRol())) $this->getJSON("No se puede confirmar la solicitud, usted no cuenta con el privilegio para confirmar la solicitud!");
			}
			
			// CONFIRMAR REGISTRO
			$row['reservacion_estado']='APROBADO';
			// RETORNAR DATOS DE CONSULTA
			return $row;
			
		}else{
			$this->getJSON($this->varGlobal['CODE_NOFOUND_MSG']);
		}
	}
	
	/*
	 * CONSULTAR REGISTRO
	 */
	public function requestEntity(){
		// CONSULTAR REGISTRO POR FECHA
		if(isset($this->post['date'])) $json=$this->requestByDate($this->post['date']);
		// CONSULTAR REGISTRO POR CODIGO
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * INSERTAR REGISTROS
	 */
	public function insertEntity(){
		// FECHA ACTUAL
		$today=$this->getFecha('dateTime');
		// VALIDAR POR FECHAS PASADAS
		if(strtotime($this->post['reservacion_fecha_inicio']) < strtotime($today)) $this->getJSON("Horario seleccionado inválido, favor seleccione un horario posterior a <b>{$today}</b>");
		
		// EMPEZAR TRANSACCIÓIN
		$this->db->begin();
		// GENERAR FECHA DE CONFIRMACIÓN
		$this->post['reservacion_confirmacion']=$this->addWorkDays($this->getFecha(),1);
		// AUDITORIA TTHH
		$this->post['fk_personal_id']=$this->getStaffIdBySession();
		// GENERAR SENTENCIA DE INGRESO & EJECUTAR SENTENCIA SQL
		$json=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// INSERTAR DATOS
		$json['data']=$this->db->viewById($this->db->getLastID($this->entity),$this->entity);
		
		// ENVIAR NOTIFICACIONES
		$this->sendWhatsAppNotification($this->entity,$json['data']);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * ACTULIZAR REGISTROS
	 */
	public function updateEntity(){
		// EMPEZAR TRANSACCIÓIN
		$this->db->begin();
		// VALIDAR APROBACIÓN DE SOLICITUD
		if($this->post['reservacion_estado']=='APROBADO'){
			// INGRESAR FECHA DE APROBACIÓN
			$this->post['reservacion_fecha_aprobacion']=$this->getFecha('dateTime');
		}
		// AUDITORIA TTHH
		$this->post['fk_personal_id']=$this->getStaffIdBySession();
		// GENERAR SENTENCIA DE INGRESO & EJECUTAR SENTENCIA SQL
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// INSERTAR DATOS
		$json['data']=$this->post;
		
		// ENVIAR NOTIFICACIONES
		$this->sendWhatsAppNotification($this->entity,$json['data']);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// STRING PARA CONTAR EL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE reservacion_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			
			// HORAS DE RESERVACION
			$row['reservacion_cantidad']=((strtotime("{$row['reservacion_fecha_cierre']}")-strtotime("{$row['reservacion_fecha_inicio']}"))/3600);
			// PARSE FECHAS
			$row['reservacion_confirmacion']=$this->setFormatDate($row['reservacion_confirmacion'],'complete');
			$row['reservacion_fecha_inicio']=$this->setFormatDate($row['reservacion_fecha_inicio'],'full');
			$row['reservacion_fecha_cierre']=$this->setFormatDate($row['reservacion_fecha_cierre'],'full');
			
			// INSERTAR REGISTROS
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