<?php namespace model\Tthh;
use api as app;

class calendarModel extends app\controller {
	
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'tthh');
	}
	
	/*
	 * FORMATO DE CAPACITACIONES Y CASAS ABIERTAS
	 */
	private function splitData($rows,$filterDate){
		$aux=array();
		foreach($rows as $val){
			$date=explode(" ",$val[$filterDate]);
			$fecha=$date[0];
			$hora=(count($date)>1)?$date[1]:'00:00';
			if(!isset($aux[$fecha])) $aux[$fecha]=array('time'=>array());
			unset($aux[$fecha]['info'][$hora]);
			$val['time']=$hora;
			$aux[$fecha]['time'][]=$val;
		}	return $aux;
	}
	
	/*
	 * PARAMETRIZAR TIPO DE DÍAS
	 */
	public function setCustomDays($data,$model,$info){
		
		// PERSONALIZAR BACKGROUND DE CUMPLEAÑOS
		if($info=='birthday'){
			$bg=$model[$data['personal_modalidad']];
		}else{
			$bg=$model['bg'];
		}
	
		// TEMPLATE HTML PARA PRESENTACIÓN
		$template=file_get_contents(VIEWS_PATH."tthh/calendarDay.html");
		// ÍCONO DE MODELO
		$template=preg_replace('/{{bg}}/',$bg,$template);
		$template=preg_replace('/{{icon}}/',$model['icon'],$template);
		
		// REEMPLAZAR OTROS PARÁMETROS
		foreach($model as $k=>$v){
			// VALIDAR EXISTENCIA DE DATOS EN MODELO
			if(isset($data[$v])) $template=preg_replace('/{{'.$k.'}}/',$data[$v],$template);
			// IMPRIMIR REGISTROS DE PLANTILLA
			else $template=preg_replace('/{{'.$k.'}}/',$v,$template);
		}	
		// RETORNAR PLANTILLA
		return $template;
	}
	
	/*
	 * LISTA DE FECHAS DE CUMPLEAÑOS - ANUAL
	 */
	private function getBirthdayByPersonal(){
		// GENERAR CONSULTA SQL
		$customTb=$this->db->setCustomTable('vw_cumpleanios',"*, to_char(persona_fnacimiento::date,'MM-DD') fnacimiento, persona_fnacimiento, date_part('year',persona_fnacimiento) anio");
		$rows=$this->db->findAll($this->db->selectFromView($customTb,"WHERE persona_fnacimiento IS NOT NULL"));
		// DECLARAR AÑO ACTUAL
		$year=$this->getFecha('Y');
		// MODELO DE CUMPLEAÑOS
		$birthday=array();
		// RECORRER DATOS PARA GENERAR FECHAS
		foreach($rows as $k=>$v){
			// BUCLE DE UN AÑO MENOS Y UN AÑO MAS
			for($i=($year-1);$i<=($year+1);$i++){
				// MODELO AUXILIAR
				$aux=$v;
				$aux['fnacimiento']="{$i}-{$aux['fnacimiento']}";
				// EDAD
				$edad=($i-$aux['anio']);
				$aux['persona_fnacimiento'].=" <b>({$edad} años)</b>";				
				// CARGAR DATOS
				$birthday[]=$aux;
			}
		}
		// RETORNAR DATOS
		return $birthday;
	}
	
	/*
	 * PARSE ESTADOS DE EMERGENCIA
	 */
	private function parseEmergencyStatus(){
		// LISTA DE ESTADOS DE EMERGENCIA
		$rows=$this->db->findAll($this->db->selectFromView("vw_estadosemergencia","ORDER BY eemergencia_fecha"));
		// MODELO DE RETORNO
		$emergency=array();
		// RECORRER ESTADOS DE EMERGENCIA
		foreach($rows as $k=>$v){
			// VERIFICAR SI YA HA SIDO SUSPENDIDO EL ESTADO DE EMERGENCIA - GENERAR FECHAS DE ESTADO DE EMERGENCIA
			$dates=$this->getDaysFromDates($v['eemergencia_fecha'],($v['eemergencia_estado']=='SUSPENDIDO')?$v['eemergencia_cierre']:$this->getFecha());
			// RECORRER POR FECHAS HASTA CERRAR EL EVENTO
			foreach($dates as $date){
				// MODELO AUXILIAR
				$aux=$v;
				$aux['eemergencia_fecha']=$date;
				// CARGAR DATOS
				$emergency[]=$aux;
			}
		}
		// RETORNAR DATOS
		return $emergency;
	}
	
	/*
	 * CONSULTAR CALENDARIO
	 */
	public function requestCalendar(){
		// MODELO DE CALENDARIO
		$calendar=array();
		
		// CUMPLEAÑOS DEL PERSONAL
		$calendar['birthday']=$this->splitData($this->getBirthdayByPersonal(),'fnacimiento');
		
		// EVENTOS
		$calendar['events']=$this->splitData($this->db->findAll($this->db->selectFromView("vw_eventos")),'evento_fecha');
		
		// FERIADOS
		$calendar['holiday']=$this->splitData($this->db->findAll($this->db->selectFromView("vw_feriado")),'feriado_fecha');
		
		// ESTADOS DE EMERGENCIA
		$calendar['emergency']=$this->splitData($this->parseEmergencyStatus(),'eemergencia_fecha');
		
		// PERMISOS DE PERSONAL
		// $calendar['permission']=$this->splitData($this->db->findAll($this->db->selectFromView("vw_personal_permisos","WHERE fk_personal_id=".app\session::get())),'permiso_desde');
		
		// MODELO DE CALENDARIO
		$model=$this->config['calendarEntity'];
		// INFORMACION DE CALENDARIO
		$info=array();
		// RECORRER LISTA DE EVENTOS
		foreach($calendar as $type=>$data){
			// IMPRESION DE EVENTOS
			foreach($data as $date=>$val){
				// CREAR REGISTRO 
				if(!isset($info[$date])) $info[$date]="";
				// IMPRESION DE REGISTRO DE EVENTOS DE UN DIA
				foreach($val['time'] as $v){
					// IMPRESION DE REGISTRO
					$info[$date].=$this->setCustomDays($v,$model[$type],$type);
				}
			}
		}
		
		// PARSE MODAL
		$data=array();
		foreach($info as $k=>$v){$data[$k]=array(array('name'=>$v));}
		
		// RETORNAR DATOS
		$this->getJSON(array('info'=>$data));
	}
	
}