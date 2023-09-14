<?php namespace controller;
use model as mdl;
use api as app;

class preventionController extends mdl\personModel {
	
	/*
	 * VARIABLES HEREDADAS
	 */
	protected $config;
	protected $varLocal;
	protected $localConfig;
	protected $times;
	protected $blocked;
	protected $statusParam;
	protected $statusIcon;
	protected $holidays;
	protected $scheduleTime;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */ 
	public function __construct(){
		// INICIAR CONSTRUCTOR PARENT
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'prevention');
	}
	
	/*
	 * INICAR VARIABLES PARA CAPACITACIONES CIUDADANAS
	 */
	protected function loadModuleData($entity){
		// VARIABLE LOCAL DEL MODULO - PREVENCION
		$this->varLocal=$this->getConfParams(app\session::$sysName);
		// PARAMETRIZACION GENERAL DEL SERVICIO
		$this->localConfig=$this->string2JSON($this->varGlobal['MAIN_DAYS_TO_REQUEST'])['prevencion'];
		
		// HORARIOS DISPONIBLES PARA REGISTRAR SOLICITUDES
		$this->times=$this->localConfig[$entity]['schedule'];
		// FECHAS NO HABILITADAS PARA REGISTRAR SOLICITUDES DEL MODULO
		$this->blocked=isset($this->varGlobal['BLOCKED_DAYS'])?$this->string2JSON($this->varGlobal['BLOCKED_DAYS']):[];
		
		// FORMATOS DE ESTADO - CSS
		$this->statusParam=$this->config['statusParam'];
		// FORMATOS DE ESTADO - ICONO
		$this->statusIcon=$this->config['statusIcon'];
		// ESTADO DE PROCESOS
		$this->trainingProcess=$this->config['trainingProcess'];
		// FERIADOS - DIAS NO LABORABLES
		$this->holidays=$this->getHolidays();
		
		// HORARIOS DISPONIBLES
		$this->scheduleTime=$this->string2JSON($this->varGlobal['RANGE_OF_TIMES']);
	}
	
	/*
	 * PARAMETRIZAR TIPO DE DÍAS
	 */
	protected function setCustomDays($entity,$data){
		// TEMPLATE HTML PARA PRESENTACIÓN
		$template=file_get_contents(VIEWS_PATH."prevention/notAvailableDayTraining.html");
		
		// TEMPLATE HTML PARA PRESENTACIÓN
		if(is_array($data)) $template=file_get_contents(VIEWS_PATH."prevention/availableDayTraining.html");
		
		// ESTADO DE REGISTRO
		$status=(is_array($data))?$data['estado']:$entity;
		
		// GENERAR NUEVO MODELO DE DATOS DE REGISTRO
		$data=(is_array($data))?array_merge($this->config['calendarEntity'][$entity],$data):array('descripcion'=>$data);
		
		// INGRESO DE DATOS ESPECIFICOS
		$data=array_merge($data,array('status'=>$this->statusParam[$status],'icon'=>$this->statusIcon[$status]));
		
		// IMPRESION DE DATOS
		foreach($data as $k=>$v){ $template=preg_replace('/{{'.$k.'}}/',$v,$template); }
		
		// RETORNAR PLANTILLA 
		return $template;
	}
	
	/*
	 * LISTADO DE STANDS DEL AÑO
	 */
	protected function getListApplicationsParent($entity){
		// HORARIOS DISPONIBLES
		$data=array();
		$info=array();
		$calendar=array();
		
		// IMPRESIÓN DE DIAS FESTIVOS
		foreach($this->holidays as $k=>$v){ $info[$k]=$this->setCustomDays('holiday',$v); }
		
		// VALIDAR SI EXISTEN FECHAS BLOQUEADAS E IMPRIMIRLAS
		/*if(!empty($this->blocked)){
			foreach($this->blocked as $dates){
				foreach($this->getDaysFromDates($dates[0],$dates[1]) as $date){ $info[$date]=$this->setCustomDays('blocked','NO DISPONIBLE'); }
			}
		}*/
		
		// INFORMACIÓN DEL CALENDARIO - STANDS AGENDADAS
		foreach($this->db->findAll($this->db->selectFromView("vw_capacitaciones_ciudadanas","WHERE (date_part('year',fecha)=date_part('year',CURRENT_DATE)) ORDER BY fecha, horario")) as $val){
			// CREAR ARRAY SI NO EXISTE
			if(!isset($calendar[$val['fecha']])) $calendar[$val['fecha']]=array();
			// INGRESAR REGISTRO
			$calendar[$val['fecha']][]=$val;
		}
		
		// IMPRESION DE EVENTOS - ENTIDADES
		foreach($calendar as $key=>$val){
			// CREAR REGISTRO
			if(!isset($info[$key])) $info[$key]="";
			// RECORRER LISTADO DE CADA DIA
			foreach($val as $v){
				// IMPRESION DE FECHAS
				$info[$key].=$this->setCustomDays($v['entidad'],$v);
			}
		}
		
		// PARSE MODAL
		foreach($info as $k=>$v){ $data[$k]=array(array('name'=>$v)); }
		
		// RETORNAR DATOS
		$this->getJSON(array('info'=>$data));
		
	}
	
	/*
	 * VALIDAR REGISTROS POR FECHA DE INGRESO
	 * - CONSULTAR SI EXISTE DISPONIBILIDAD PARA LA FECHA SELECCIONADA
	 */
	protected function validateDate($date,$entity='training'){
		// FECHA DE EVENTO
		$parse=date('w',strtotime($date));
		
		// DIAS NO APTOS PARA STAND
		$weekends=[0,6];
		// FECHA ACTUAL
		$today=$this->getFecha();
		// FECHA MÍNIMA HABILITADA
		$minDate=$this->addWorkDays($today,$this->varGlobal['TRY_MIN_DAY']);
		// FECHA MÁXIMO HABILITADA
		$maxDate=$this->addWorkDays($today,$this->varGlobal['TRY_MAX_DAY']);
		
		// VALIDACIÓN DE FECHA
		if(strtotime($date) < strtotime($minDate)) $this->getJSON("Debe seleccionar una fecha posterior o igual a: <b>{$this->setFormatDate($minDate,'complete')}</b>");
		elseif(strtotime($date) > strtotime($maxDate)) $this->getJSON("Debe seleccionar una fecha inferior o igual a: <b>{$this->setFormatDate($maxDate,'complete')}</b>");
		elseif(in_array($parse,$weekends)) $this->getJSON("El registro de solicitudes ha sido deshabilitado para los fines de semana.");
		elseif(isset($this->holidays[$date])) $this->getJSON("Lamentamos los inconvenientes, la fecha seleccionada está deshabilitada por motivo: <b>{$this->holidays[$date]}</b>");
		
		// VALIDAR SI EXISTEN FECHAS BLOQUEADAS
		/*if(!empty($this->blocked)){
			foreach($this->blocked as $dates){
				if(strtotime($date)>=strtotime($dates[0]) && strtotime($date)<=strtotime($dates[1])) $this->getJSON("Lamentamos los inconvenientes, la plataforma ha sido deshabilitada desde el <b>{$dates[0]}</b> al <b>{$dates[1]}</b>");
			}
		}*/
		
		// RETORNAR MENSAJE DE EXITO
		return $this->setJSON("ok",true);
	}
	
	/*
	 * VALIDAR REGISTROS POR FECHA DE INGRESO
	 * - CONSULTAR SI EXISTE DISPONIBILIDAD PARA LA FECHA SELECCIONADA
	 */
	protected function requestByDateAndEntity($date,$entity){
		// PARSE FECHA
		$date=$this->setFormatDate($date,'Y-m-d');
		
		// PARAMETROS LOCALES
		$localConfig=$this->string2JSON($this->varGlobal['MAIN_DAYS_TO_REQUEST'])['prevencion'];
		
		// PARAMETROS DE ENTIDAD
		$entityConfig=$localConfig[$entity];
		
		// NUMERO DE DIA DEL EVENTO
		$parse=date('w',strtotime($date));
		
		// FECHA ACTUAL
		$today=$this->getFecha();
		// FECHA MÍNIMA HABILITADA
		$minDate=$this->addWorkDays($today,$entityConfig['min']);
		// FECHA MÁXIMO HABILITADA
		$maxDate=$this->addWorkDays($today,$entityConfig['max']);
		
		// VALIDAR FECHA MINIMA PARA EL INGRESO DE REGISTROS
		if(strtotime($date) < strtotime($minDate)) $this->getJSON("Debe seleccionar una fecha posterior o igual a: <b>{$this->setFormatDate($minDate,'complete')}</b>");
		// VALIDAR FECHA MAXIMA PARA EL INGRESO DE REGISTROS
		elseif(strtotime($date) > strtotime($maxDate)) $this->getJSON("Debe seleccionar una fecha inferior o igual a: <b>{$this->setFormatDate($maxDate,'complete')}</b>");
		// VALIDAR LOS DIAS ACEPTADOS PARA REGISTROS
		elseif(!in_array($parse,$entityConfig['days'])) $this->getJSON("El registro de solicitudes ha sido deshabilitado para los fines de semana.");
		// VALIDAR DIAS FESTIVOS
		elseif(isset($this->holidays[$date])) $this->getJSON("Lamentamos los inconvenientes, la fecha seleccionada está deshabilitada por motivo: <b>{$this->holidays[$date]}</b>");
		
		// VALIDAR SI EXISTEN FECHAS BLOQUEADAS
		/*if(!empty($this->blocked)){
			// RECORRER FECHAS DESHABILITADAS PARA REGISTROS
			foreach($this->blocked as $dates){
				// VALIDAR LISTADO DE FECHAS DESHABILITADAS PARA REGISTROS
				if(strtotime($date)>=strtotime($dates[0]) && strtotime($date)<=strtotime($dates[1])) $this->getJSON("Lamentamos los inconvenientes, la plataforma ha sido deshabilitada desde el <b>{$dates[0]}</b> al <b>{$dates[1]}</b>");
			}
		}*/
		
		// RETORNAR MENSAJE DE EXITO
		return true;
	}
	
}