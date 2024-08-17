<?php namespace model;
use api as app;

class dashboardModel extends app\controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='dashboard';
	private $varLocal;
	private $session;
	private $entities;
	private $typeDashboard;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
		// CALENDARIO
		$this->calendar=$this->getConfig('calendar');
		// PARAMETRIZACION DE DASHBOARD
		$this->varLocal=$this->getConfig(true,'dashboard');
		// TIPOS DE DASHBOARD
		$this->typeDashboard=$this->string2JSON($this->varGlobal['DASHBOARD_TYPE_VIEW']);
		// DATOS DE SESSION
		$this->session=$this->getUserInformation();
		// LISTA DE ENTIDADES POR PERFIL
		$this->entities=$this->getEntitiesByProfile();
	}
	
	/*
	 * LISTA DE ENTIDADES
	 */
	private function getEntitiesByProfile(){
		// INFORMACION AUXILIAR
		$this->get['year']=$this->getFecha('Y');//#año
		$this->get['month']=$this->getFecha('n');//#mes
		$this->get['day']=$this->getFecha('N');//#día (semana)
		$this->get['week']=$this->getFecha('W');//#semana
		$this->get['lastweek']=$this->getFecha('W') - 1;//#semana
		$this->get['dayF']=$this->getFecha('d');//día (fecha)
		// MODELO DE ENTIDADES
		$entities=array(
			'active'=>array(),
			'list'=>array(),
			'model'=>array()
		);
		// MODELO DE ENTIDADES POR TIPO DE DASHBOARD
		foreach($this->typeDashboard as $v){
			// VERIFICAR LA LONGITUD DE ITEMS
			$str=$this->db->selectFromView($this->db->setCustomTable('perfil_dashboard','fk_dashboard_id'),"WHERE tipo='{$v}' AND fk_perfil_id={$this->session['perfil_id']}");
			// OBTENER NUMERO DE REGISTROS
			$count=$this->db->numRows($str);
			// CARGAR LOS ITEMS DE DASHBOARD
			if($count>0){
				// CONTADOR DE REGISTROS
				$entities['active'][$v]=$count;
				// LISTADO DE DASHBOARD
				foreach($this->db->findAll($str) as $v2){
					// MODELO DE DASHBOARD
					$entities['model'][$v][]=$v2['fk_dashboard_id'];
					// VERIFICAR DATOS DE ENTIDAD
					if(!isset($entities['list'][$v2['fk_dashboard_id']])) $entities['list'][$v2['fk_dashboard_id']]=$this->db->findById($v2['fk_dashboard_id'],'dashboard'); 
				}
			}
		}
		// RETORNAR DATOS
		return $entities;
	}
	
	/*
	 * GENERAR CONSULTA SQL DESDE dashboard.js
	 */
	private function createQuery($tb,$date){
		// OBTENER MODELO SQL DE CONSULTA
		$config=$this->varLocal['sql'][$tb];
		// INICIAR CONSULTA WHERE
		$where=$config['where'];
		// VALIDAR SI EL REGISTRO ENVIADO ES UN ARRAY O UN SOLO REGISTRO
		if(is_array($date)){
			// GENERARA SENTENCIA SQL
			foreach($date as $k=>$v){$where=str_replace('{{'.$k.'}}',$v,$where);}
		}else{
			$where=str_replace("{{date}}",$date,$where);
		}
		// RETORNAR CONSULTA
		// print_r($date);
		// print_r($where);
		return $this->db->findOne($where);
	}
	
	
	/*
	 * LISTADO DE ROLES POR MODULOS Y SUBMODULOS
	 */
	private function requestBySubmodules(){
		// MODELO DE DATOS
		$data=array();
		// LISTADO DE SUBMODULOS POR MODULOS
		foreach($this->db->findAll($this->db->selectFromView('modulos',"ORDER BY modulo_id")) as $val){
			// MODELO AUXILIAR PARA SUBMODULOS
			$aux=$this->db->findAll($this->db->selectFromView('dashboard',"WHERE fk_modulo_id={$val['modulo_id']} ORDER BY dashboard_nombre"));
			// CARGAR SUBMODULO
			if(sizeof($aux)>0){
				$data['modules'][]=$val;
				$data['dashboard'][$val['modulo_id']]=$aux;
			}
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR REPORTES PARA PERFIL
	 */
	private function requestByProfile($profileId){
		// MODELO DE DATOS
		$data=array(
			'resources'=>array(),
			'selected'=>array(),
			'types'=>$this->typeDashboard
		);
		// GENERAR MODELO POR TIPOS DE DASHBOARD
		foreach($data['types'] as $v){
			// VALIDAR SI EL MODELO HA SIDO CREADO
			if(!isset($data['selected'][$v])) $data['selected'][$v]=array();
		}
		// ROLES EN BASE A MODULOS
		$data['resources']=$this->requestBySubmodules();
		// OBTENER LISTADO DE PERMISOS INGRESADOR POR PERFIL
		foreach($this->db->findAll($this->db->selectFromView('perfil_dashboard',"WHERE fk_perfil_id={$profileId}")) as $v){
			$data['selected'][$v['tipo']][]=$v['fk_dashboard_id'];
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR DASHBOARD
	 */
	public function requestDashboard(){
		// OBTENER DATOS DE FORMULARIO
		$post=$this->requestPost();
		// CONSULTAR POR CÓDIGO - INGRESO EN CBSD
		if(isset($post['profileId'])) $json=$this->requestByProfile($post['profileId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * ACTUALIZAR ROLES DE USUARIOS
	 */
	public function setDashboard(){
		// OBTENER DATOS DE FORMULARIO
		$post=$this->requestPost();
		// STRING PARA CONSULTAR RELACION
		$strWhr="WHERE tipo='{$post['tipo']}' AND fk_dashboard_id={$post['fk_dashboard_id']} AND fk_perfil_id={$post['fk_perfil_id']}";
		// STRING PARA CONSULTAR SI EXISTE EL REGISTRO Y GENERAR STRING DE INGRESO O ACTUALIZACION DE REGISTRO
		$str=($this->db->numRows($this->db->selectFromView('perfil_dashboard',$strWhr))>0)?$this->db->getSQLDelete('perfil_dashboard',$strWhr):$this->db->getSQLInsert($post,'perfil_dashboard');
		// GENERAR CONSULTA Y RETORNAR MENSAJE
		$this->getJSON($this->db->executeSingle($str));
	}
	

	/*
	 * ESTADÍSTICA MENSUAL
	 */
	private function statByYear($tb,$year){
		// MODELO DE DATOS
		$datasets=array();
		// RECORRER MESES
		for($i=2016;$i<=$year;$i++){
			// RANGO DE FECHAS
			$rangesDate=array(
				'init'=>"{$i}-01-01",
				'final'=>$this->setFormatDate("$i-12",'Y-m-t')
			);
			// ALMACENAR DATOS
			$datasets[]=$this->createQuery($tb,$rangesDate)['total'];
		}
		// RETORNAR DATOS
		return $datasets;
	}
	
	/*
	 * ESTADÍSTICA MENSUAL
	 */
	private function statByMonth($tb,$year,$month){
		// MODELO DE DATOS
		$datasets=array();
		// CONTADOR DE MESES
		if($month==1)$month=2;
		// RECORRER MESES 
		for($i=1;$i<=$month;$i++){
			// PARSE MESES: 1 -> 01
			$x=str_pad($i,2,"0",STR_PAD_LEFT);
			
				// RANGO DE FECHAS
				$rangesDate=array(
					'init'=>"{$year}-{$x}-01",
					'final'=>$this->setFormatDate("$year-$x",'Y-m-t')
				);
				// ALMACENAR DATOS 
				$datasets[]=$this->createQuery($tb,$rangesDate)['total'];
			
		}	
		// RETORNAR DATOS
		return $datasets;
	}
	
	/*
	 * ESTADÍSTICA SEMANAL
	 */
	private function statByWeek($tb,$dates){
		// MODELO DE ESTADÍSTICAS
		$datasets=array();
		// RECORRER LISTA DE FECHAS
		foreach($dates as $val){
			// CONTADOR TOTAL
			$total=0;
				// VALIDAR FECHAS DE CONSULTA
				$rangesDate=array(
					'init'=> $val[0], // date("Y-m-d",strtotime($val[0]."- 7 days")), 
					'final'=>$val[count($val)-1]
				);
				// GENERAR CONTADOR
				$total=$this->createQuery($tb,$rangesDate)['total'];
			
			// AGREGAR CONTADOR
			$datasets[]=$total;
		}
		// RETORNAR MODELO
		return $datasets;
	} 
	
	/*
	 * ESTADÍSTICA DIARIA
	 */
	private function statByDay($tb,$year,$month,$day){
		// MODELO DE ESTADÍSTICAS
		$datasets=array();
		// PARSE FECHA
		$month=str_pad($month,2,"0",STR_PAD_LEFT);
		// RECORRER LISTA DE FECHAS
		for($i=1;$i<=$day;$i++){
			// PAD DIA
			$x=str_pad($i,2,"0",STR_PAD_LEFT);
				// VALIDAR FECHAS DE CONSULTA
				$rangesDate=array(
					'init'=>"$year-$month-$x",
					'final'=>"$year-$month-$x"
				);
				// AGREGAR CONTADOR
				$datasets[]=$this->createQuery($tb,$rangesDate)['total'];
		}
		// RETORNAR MODELO
		return $datasets;
	}
	
	
	/*
	 * DUPLICADOS Y PERMISOS DEL MES
	 */
	private function permisosLastMonths($year,$month){
		$tb=['permisos','duplicados'];
		// DATASET
		$data=array();
		$aux=array();
		$colors=$this->getConfig('colorPalette')['hex'];
		$backgroundColor=array();
		$hoverBackgroundColor=array();
		// TOTAL DE P Y D
		$total=1;
		for($i=1;$i<=$month;$i++){
			$count=0;
			$x=str_pad($i,2,"0",STR_PAD_LEFT);
			foreach($tb as $val){
				$count+=$this->db->numRows($this->db->getSQLSelect($val,[],"WHERE {$this->entities[$tb]['param']}::text LIKE '$year-$x%'"));
			}	$aux[$i]=$count;
			$total+=$count;
		}
		// PREPARE DATASET
		for($i=1;$i<=$month;$i++){
			$x=str_pad($i,2,"0",STR_PAD_LEFT);
			$data[]=number_format(($aux[$i]*100)/$total);
			$backgroundColor[]=$colors[$i-1];
			$hoverBackgroundColor[]=$colors[$i-1];
		}	return array('data'=>$data,'backgroundColor'=>$backgroundColor,'hoverBackgroundColor'=>$hoverBackgroundColor);
	}
	
	/*
	 * INSPECCIONES POR INSPECTOR
	 */
	private function inspectionByInspector($year,$week){
		$listDays=array();//actividades por días(semanal)
		$colors=$this->getConfig('colorPalette')['rgb'];//Lista de colores
		$count=0;
		$tb='inspecciones';
		foreach($this->db->findAll($this->db->getSQLSelect('vw_inspectores',[],"WHERE usuario_estado='Activo'")) as $val){
			$aux=array();
			for($i=1;$i<=5;$i++){
				$gendate = new \DateTime();
				$gendate->setISODate($year,$week,$i);
				$x=$gendate->format('Y-m-d');
				$aux[]=$this->db->numRows($this->db->getSQLSelect($tb,[],"WHERE fk_inspector_id={$val['usuario_id']} AND {$this->entities[$tb]['param']}::text LIKE '$x%' AND inspeccion_estado<>'Pendiente'"));
			}
			$listDays[]=chartModel::radarChart($val['persona_nombre'],$colors[$count],$aux);
			$count++;
		} return $listDays;
	}
	
	/*
	 * REPORTE DE ENTIDADES - CUADRADOS
	 */
	private function getStatsEntities($type){
		
		$year=$this->getFecha('Y');//#año
		$month=$this->getFecha('n');//#mes
		$day=$this->getFecha('N');//#día (semana)
		$week=$this->getFecha('W');//#semana
		$dayF=$this->getFecha('d');//día (fecha)
		
		$listMonths=array();//actividades por mes(anual)
		$listDays=array();//actividades por días(mensual)
		$count=0;
		
		// LISTA DE FECHAS
		$datesByMonth=$this->weeksByMonth($year,$month);
		
		// VALIDAR SI LA SOLICITUD SE LA REALIZA DESDE EL MODULO DE ESTADISTICAS
		if($type=='stats'){
			$datesByMonth=array('weeks'=>array(),'names'=>array(),'dates'=>array());
			for($i=1;$i<=$month;$i++){
				$aux=$this->weeksByMonth($year,$i);
				$datesByMonth['weeks']=array_merge($datesByMonth['weeks'],$aux['weeks']);
				$datesByMonth['names']=array_merge($datesByMonth['names'],$aux['names']);
				$datesByMonth['dates']=array_merge($datesByMonth['dates'],$aux['dates']);
			}
		}
		// GENERAR ESTADÍSTICAS SEGÚN ENTIDADES
		foreach($this->entities as $v){
			// PERMISOS Y DUPLICADOS POR MES
			$listMonths[]=chartModel::lineChart($v['title'],$v['rgba'],$this->statByMonth($v['entity'],$year,$month));
			// PERMISOS Y DUPLICADOS POR DÍA (SEMANA EN CURSO)
			$listWeeks[]=chartModel::lineChart($v['title'],$v['rgba'],$this->statByWeek($v['entity'],$datesByMonth['dates']));
			// PERMISOS Y DUPLICADOS POR DÍA (SEMANA EN CURSO)
			$listDays[]=chartModel::lineChart($v['title'],$v['rgba'],$this->statByDay($v['entity'],$year,$month,$dayF));
			$count++;
		}
		// AGREGAR ACTIVIDADES EN MODELO
		return array(
			'inspectionByInspector'=>chartModel::getModel($this->getDaysByWeek($week),$this->inspectionByInspector($year,$week)),
			'byDays'=>chartModel::getModel($this->daysByMonth($year,$month,$dayF),$listDays),
			'byWeeks'=>chartModel::getModel($datesByMonth['names'],$listWeeks),
			'byMonths'=>chartModel::getModel($this->getMonths($month),$listMonths)
		);
	}
	
	
	/*
	 * DASHBOARD ANUAL
	 */
	private function getAnnualStats(){
		// MODELO
		$dataMain=array();
		// FECHA PARA REPORTES
		$year=$this->getCurrentYear();
		// VALIDAR FECHAS DE CONSULTA
		$rangesDate=array(
			'init'=>"{$year}-01-01",
			'final'=>$this->setFormatDate("{$year}-12",'Y-m-t')
		);
		// MODELO DE ENTIDADES
		foreach($this->entities['model']['CONTADOR'] as $v){
			// MODELO TEMPORAL
			$temp=$this->entities['list'][$v];
			// GENERAR SQL
			$data=$this->createQuery($temp['dashboard_entidad'],$rangesDate);
			// MODELO DASHBOARD
			$dataMain[]=array(
				'name'=>$temp['dashboard_nombre'],
				'uri'=>$temp['dashboard_url'],
				'icon'=>$temp['dashboard_icono'],
				'color'=>$temp['dashboard_color'],
				'count'=>$data['total'],
				'desc'=>$data['total_costo']
			);
		}
		// RETORNAR MODELO DASHBOARD
		return $dataMain;
	}

	/*
	 * DASHBOARD DIARIO
	 */
	private function getDailyStats(){
		// VERIFICAR SI SE HA ENVIADO UNA FECHA, SINO GENERAR DATOS CON LA FECHA ACTUAL
		if(isset($this->get['date'])) $date=$this->get['date'];
		else $date=$this->getFecha();
		// MODELO DE ENTIDADES
		foreach($this->entities['model']['CONTADOR'] as $v){
			// MODELO TEMPORAL
			$temp=$this->entities['list'][$v];
			// GENERAR SQL
			$data=$this->createQuery($temp['dashboard_entidad'],array('init'=>$date,'final'=>$date));
			// MODELO DASHBOARD
			$temp=array(
				'name'=>$temp['dashboard_nombre'],
				'uri'=>$temp['dashboard_url'],
				'icon'=>$temp['dashboard_icono'],
				'color'=>$temp['dashboard_color'],
				'count'=>$data['total'],
				'desc'=>0
			);
			// VALIDAR INGRESOS
			if(isset($data['costo_total'])) $temp['desc']=$data['costo_total'];
			// INGRESAR DATOS
			$dataMain[]=$temp;
		}
		// RETORNAR DATOS
		return $dataMain;
	}
	
	
	
	/*
	 * REGISTROS - RECORDATORIOS
	 */
	private function getReminders(){
		return array('REMINDER');
	}
	
	/*
	 * 1.1. REGISTROS - CONTADOR DIARIO
	 */
	private function getRecordCounter(){
		return ($this->get['type']=='main')?$this->getDailyStats():$this->getAnnualStats();
	}
	
	/*
	 * 1.2. REGISTROS - DIARIOS
	 */
	private function getDailyRecords(){
		// MODELO
		$model=array();
		// GENERAR ESTADÍSTICAS SEGÚN ENTIDADES
		foreach($this->entities['model']['DIARIO'] as $v){
			// MODELO TEMPORAL
			$temp=$this->entities['list'][$v];
			// PERMISOS Y DUPLICADOS POR DÍA (SEMANA EN CURSO)
			$model[]=chartModel::lineChart($temp['dashboard_nombre'],$temp['dashboard_rgba'],$this->statByDay($temp['dashboard_entidad'],$this->get['year'],$this->get['month'],$this->get['dayF']));
		}
		// AGREGAR ACTIVIDADES EN MODELO
		return chartModel::getModel($this->daysByMonth($this->get['year'],$this->get['month'],$this->get['dayF']),$model);
	}
	
	/*
	 * 1.3. REGISTROS - SEMANALES
	 */
	private function getWeeklyRecords(){
		// MODELO
		$model=array();
		// LISTA DE FECHAS
		$datesByMonth=$this->weeksByMonth($this->get['year'],$this->get['month']);
		// VALIDAR SI LA SOLICITUD SE LA REALIZA DESDE EL MODULO DE ESTADISTICAS
		if($this->get['type']=='stats'){
			$datesByMonth=array('weeks'=>array(),'names'=>array(),'dates'=>array());
			for($i=1;$i<=$this->get['month'];$i++){
				$aux=$this->weeksByMonth($this->get['year'],$i);
				$datesByMonth['weeks']=array_merge($datesByMonth['weeks'],$aux['weeks']);
				$datesByMonth['names']=array_merge($datesByMonth['names'],$aux['names']);
				$datesByMonth['dates']=array_merge($datesByMonth['dates'],$aux['dates']);
			}
		}
		// GENERAR ESTADÍSTICAS SEGÚN ENTIDADES
		foreach($this->entities['model']['SEMANAL'] as $v){
			// MODELO TEMPORAL
			$temp=$this->entities['list'][$v];
			// PERMISOS Y DUPLICADOS POR DÍA (SEMANA EN CURSO)
			$model[]=chartModel::lineChart($temp['dashboard_nombre'],$temp['dashboard_rgba'],$this->statByWeek($temp['dashboard_entidad'],$datesByMonth['dates']));
		}
		// AGREGAR ACTIVIDADES EN MODELO
		return chartModel::getModel($datesByMonth['names'],$model);
	}
	
	/*
	 * 1.4. REGISTROS - MENSUALES
	 */
	private function getMonthlyRecords(){
		// MODELO
		$model=array();
		// GENERAR ESTADÍSTICAS SEGÚN ENTIDADES
		foreach($this->entities['model']['MENSUAL'] as $v){
			// MODELO TEMPORAL
			$temp=$this->entities['list'][$v];
			// PERMISOS Y DUPLICADOS POR DÍA (SEMANA EN CURSO)
			$model[]=chartModel::lineChart($temp['dashboard_nombre'],$temp['dashboard_rgba'],$this->statByMonth($temp['dashboard_entidad'],$this->get['year'],$this->get['month']));
		}
		// AGREGAR ACTIVIDADES EN MODELO
		return chartModel::getModel($this->getMonths($this->get['month']),$model);
	}
	
	/*
	 * 1.5. REGISTROS - ANUALES
	 */
	private function getAnnualRecords(){
		// MODELO
		$model=array();
		// GENERAR ESTADÍSTICAS SEGÚN ENTIDADES
		foreach($this->entities['model']['ANUAL'] as $v){
			// MODELO TEMPORAL
			$temp=$this->entities['list'][$v];
			// PERMISOS Y DUPLICADOS POR DÍA (SEMANA EN CURSO)
			$model[]=chartModel::lineChart($temp['dashboard_nombre'],$temp['dashboard_rgba'],$this->statByYear($temp['dashboard_entidad'],$this->get['year']));
		}
		// AGREGAR ACTIVIDADES EN MODELO
		return chartModel::getModel($this->getYears($this->get['year']),$model);
	}
	
	/*
	 * 1.6. ESTADÍSTICAS POR PERFIL - INSPECTORES
	 */
	private function getStatsPreventions($userId,$profile){
		// MODELO DE CONSULTA
		$data=array();
		// LISTADO DE INSPECCIONES PROGRAMADAS
		$str=$this->db->selectFromView("vw_inspecciones","WHERE reinspeccion::DATE>CURRENT_DATE ORDER BY reinspeccion ASC");
		if($profile==6) $str=$this->db->selectFromView("vw_inspecciones","WHERE inspector_id={$userId} ORDER BY reinspeccion ASC LIMIT 30");
		// CONSULTAR NÚMERO DE INSPECCIONES
		if($this->db->numRows($str)>0) $data=$this->db->findAll($str);
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * 1.6. ESTADÍSTICAS POR PERFIL - MEDICO INSTITUCIONAL
	 */
	private function getStatsMedical(){
		// MODELO DE CONSULTA
		$data=array();
		// LISTADO DE INSPECCIONES PROGRAMADAS
		$str=$this->db->selectFromView("vw_consultasmedicas","WHERE consulta_cita_subsecuente='SI' AND consulta_cita_notificado='AGENDADA' ORDER BY consulta_cita_fecha");
		// CONSULTAR NÚMERO DE INSPECCIONES
		if($this->db->numRows($str)>0) $data=$this->db->findAll($str);
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * 1. SELECCIONAR EL TIPO DE REPORTE
	 */
	public function getDashboard(){
		// MODELO
		$model=array(
			'medical'=>'',
			'prevention'=>'',
			'config'=>$this->entities,
			'chart'=>array()
		);
		// MÉTODOS DE CONSULTA
		$methods=$this->string2JSON($this->varGlobal['DASHBOARD_TYPE_METHODS']);
		// GENERAR MODELO DE REGISTROS
		foreach($this->entities['model'] as $k=>$v){
			// NOMBRE DE FUNCIÓN REQUERIDA
			$method=$methods[$k];
			// LLAMAR A FUNCIÓN REQUERIDA
			$model['chart'][$k]=$this->$method();
		}
		// VALIDAR EL PERFIL DE PREVENCIÓN
		// if(in_array($this->session['perfil_id'],[5,6])) $model['prevention']=$this->getStatsPreventions($this->session['usuario_id'],$this->session['perfil_id']);
		if(in_array($this->session['perfil_id'],[16])) $model['medical']=$this->getStatsMedical();
		// RETORNAR MODELO DE ESTADISTICA
		$this->getJSON($model);
	}
	
	/*
	 * LISTADO DE MODULOS Y ENTIDADES PARA REPORTES ESTADÍSTICOS
	 */
	public function getReports(){
		// MODELO DE DATOS Y FILTROS DE ENTIDADES
		$data=$this->getConfig(false,'exports');
		$data['modules']=array();
		$data['tables']=array();
		
		// LISTADO DE MÓDULOS
		foreach($this->db->findAll($this->db->getSQLSelect('modulos')) as $val){
			// STRING PARA CONSULTAR TABLAS DEL MODULO
			$str=$this->db->selectFromView('tablas',"WHERE fk_modulo_id={$val['modulo_id']}");
			// VALIDAR SI EXISTEN REGISTROS
			if($this->db->numRows($str)>0){
				// LISTADO DE TABLAS
				foreach($this->db->findAll($str) as $v){
					// REGISTRAR EN BASE A MODULO
					$data['tables'][$v['fk_modulo_id']][]=$v['tabla_name'];
				}
				// CARGAR DATOS SI EXISTEN TABLAS ASIGNADAS
				$data['modules'][]=$val;
			}
		}
		// LISTA DE USUARIO
		$data['users']=$this->db->findAll($this->db->selectFromView('vw_usuarios',"WHERE usuario_id>1 AND usuario_estado='Activo'"));
		// RETORNAR DATOS
		$this->getJSON($data);
	}
	
}