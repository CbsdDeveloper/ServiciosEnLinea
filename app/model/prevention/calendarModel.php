<?php namespace model\prevention;
use api as app;

class calendarModel extends app\controller {
	
	private $config;
	private $holidays;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
		// PARÁMETROS DE PREVENCIÓN
		$this->config=$this->getConfig(true,'prevention');
		// FERIADOS - DIAS NO LABORABLES
		$this->holidays=$this->getHolidays();
	}
	
	/*
	 * FORMATO DE CAPACITACIONES Y CASAS ABIERTAS
	 */
	private function splitData($rows,$filterDate){
		$aux=array();
		foreach($rows as $val){
			$date=explode(" ",$val[$filterDate]);
			$fecha=$date[0];
			$hora=$date[1];
			if(!isset($aux[$fecha])) $aux[$fecha]=array('time'=>array());
			unset($aux[$fecha]['info'][$hora]);
			$val['time']=$hora;
			$aux[$fecha]['time'][]=$val;
		}	return $aux;
	}
	
	/*
	 * PARAMETRIZAR TIPO DE DÍAS
	 */
	public function setHolidays($type,$data,$time=''){
		// TEMPLATE HTML PARA PRESENTACIÓN
		$template=file_get_contents(VIEWS_PATH."prevention/dayTraining.html");
		// IMPRIMIR POR DEFECTO LA HORA
		$template=preg_replace('/{{time}}/',$time,$template);
		// CSS DE DETALLE
		$template=preg_replace('/{{status}}/',$this->config['statusParam'][$type],$template);
		// IMPRIMIR ÍCONO
		$template=preg_replace('/{{icon}}/',$this->config['statusIcon'][$type],$template);
		// DETALLE DE LABEL
		$template=preg_replace('/{{estado}}/',$data,$template);
		return $template;
	}
	
	/*
	 * PARAMETRIZAR TIPO DE DÍAS
	 */
	public function setCustomDays($data,$model,$template='calendarDay'){
		// TEMPLATE HTML PARA PRESENTACIÓN
		$template=file_get_contents(VIEWS_PATH."prevention/{$template}.html");
		// IMPRIMIR POR DEFECTO LA HORA
		$template=preg_replace('/{{time}}/',$data['time'],$template);
		// COLOR DE MODELO
		$status=$data[$model['estado']];
		$template=preg_replace('/{{status}}/',$this->config['statusParam'][$status],$template);
		// ÍCONO DE MODELO
		$status=$data[$model['estado']];
		$template=preg_replace('/{{iconStatus}}/',$this->config['statusIcon'][$status],$template);
		// IMPRIMIR FECHA DE CONFIRMACIÓN EN ESTADO PENDIENTE
		if(isset($data[$model['estado']]) && in_array($data[$model['estado']],$this->config['statusToDelete'])){
			$template=preg_replace('/{{confirmacion}}/',"[{$data[$model['confirmacion']]}]",$template);
		}else $template=preg_replace('/{{confirmacion}}/','',$template);
		// REEMPLAZAR OTROS PARÁMETROS
		foreach($model as $k=>$v){
			if(isset($data[$v])) $template=preg_replace('/{{'.$k.'}}/',$data[$v],$template);
			else $template=preg_replace('/{{'.$k.'}}/',$v,$template);
		}	return $template;
	}
	
	
	/*
	 * CALENDAR - CAPACITACIONES CIUDADANAS
	 */
	private function getCalendarTrainings(){
		// MODELO AUX DEL CALENDARIO
		$calendar=array('training'=>array(),'stands'=>array());
		// CAPACITACIONES CIUDADANAS
		$rows=$this->db->findAll($this->db->selectFromView("vw_training","WHERE (date_part('year',capacitacion_fecha)=date_part('year',CURRENT_DATE)) ORDER BY capacitacion_fecha,capacitacion_estado"));
		$calendar['training']=$this->splitData($rows,'capacitacion_fecha');
		// CASAS ABIERTAS - STANDS
		$rows=$this->db->findAll($this->db->selectFromView("vw_stands","WHERE (date_part('year',stand_fecha)=date_part('year',CURRENT_DATE))"));
		$calendar['stands']=$this->splitData($rows,'stand_fecha');
		// VISITAS - RECORRIDOS POR LAS INSTALACIONES DEL CBSD
		$rows=$this->db->findAll($this->db->selectFromView("vw_visitas","WHERE (date_part('year',visita_fecha)=date_part('year',CURRENT_DATE))"));
		$calendar['visitas']=$this->splitData($rows,'visita_fecha');
		// SIMULACROS
		$rows=$this->db->findAll($this->db->selectFromView("vw_simulacros","WHERE (date_part('year',simulacro_fecha)=date_part('year',CURRENT_DATE))"));
		$calendar['simulacros']=$this->splitData($rows,'simulacro_fecha');
		// MODELO DE REGISTROS - UNIÓN DE CAPACITACIONES CON CASAS ABIERTAS
		$model=$this->config['calendarEntity'];
		$info=array();
		foreach($calendar as $type=>$data){
			foreach($data as $date=>$val){
				// CREAR REGISTRO
				if(!isset($info[$date])) $info[$date]="";
				foreach($val['time'] as $v){
					$v['time']=$v['time'];
					$info[$date].=$this->setCustomDays($v,$model[$type]);
				}
			}
		}
		// IMPRESIÓN DE DIAS FESTIVOS
		foreach($this->holidays as $k=>$v){ $info[$k]=$this->setHolidays('holiday',$v); }
		// PARSE INFO
		$data=array();
		foreach($info as $k=>$v){$data[$k]=array(array('name'=>$v));}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CALENDARIO - PERMISOS OCASIONALES
	 */
	private function getCalendarOccasionals(){
		// MODELO AUX DEL CALENDARIO
		$calendar=array('ocasionales'=>array());
		// CAPACITACIONES CIUDADANAS
		$rows=$this->db->findAll($this->db->selectFromView("vw_ocasionales","WHERE (date_part('year',ocasional_fecha_inicio)=date_part('year',CURRENT_DATE)) ORDER BY ocasional_fecha_inicio,ocasional_estado"));
		$calendar['ocasionales']=$this->splitData($rows,'ocasional_fecha_inicio');
		// MODELO DE REGISTROS - UNIÓN DE CAPACITACIONES CON CASAS ABIERTAS
		$model=$this->config['calendarEntity'];
		// MODELO DE RETORNO
		$info=array();
		// RECORRER CATEGORIAS
		foreach($calendar as $type=>$data){
			// RECORRER REGISTROS DE CATEGORIAS
			foreach($data as $date=>$val){
				// CREAR REGISTRO
				if(!isset($info[$date])) $info[$date]="";
				foreach($val['time'] as $v){
					$v['time']=$v['time'];
					$info[$date].=$this->setCustomDays($v,$model[$type],'occasionalDay');
				}
			}
		}
		// PARSE MODAL
		$data=array();
		foreach($info as $k=>$v){$data[$k]=array(array('name'=>$v));}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR CALENDARIO
	 */
	public function requestCalendar(){
		// CALENDARIO - CAPACITACIONES CIUDADANAS
		if(isset($this->post['type']) && $this->post['type']=='trainings') $json=$this->getCalendarTrainings();
		// CALENDARIO - PERMISOS OCASIONALES 
		elseif(isset($this->post['type']) && $this->post['type']=='occasionals') $json=$this->getCalendarOccasionals();
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON(array('info'=>$json));
	}
	
}