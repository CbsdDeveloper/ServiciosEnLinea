<?php namespace api;
use controller as ctrl;
/**
 * Description of controller
 * 
 * @author Lalytto
 * @mail apinango@lalytto.com
 */
class controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	// GESTOR DE ARCHIVOS
	public $uploaderMng;
	// PARÁMETROS DEL SISTEMA
	public $varGlobal=array();
	public $sysName;
	// TIMER
	public $countAux=1;
	// POST AND GET
	protected $post;
	protected $get;
	protected $db;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONSTRUCTOR PADRE
		$this->db=new connectDB($this);
		// MODELO DE RECURSOS
		$this->uploaderMng=new ctrl\uploaderController;
		$this->uploaderMng->pathFile="src/";
		// MENSAJE DEL SISTEMA PARSEADOS A ARRAY
		$this->varGlobal=$this->getConfParams();
		// PATH DE MODULO
		$this->sysName=session::$sysName;
		// CARAGAR DATOS DE FORMULARIO
		$this->post=$this->requestPost();
		$this->get=$this->requestGet();
	}
	

	/*
	 * VALIDACIÓN DE LOGIN POR MÓDULOS [ALL]
	 */
	protected function testLoginModule(){
		// VALIDAR SI EL MÓDULO SE ENCUENTRA ACTIVO
		if($this->varGlobal['STATUS_ON']!=$this->varGlobal['STATUS_ACTIVE']){
			// SI NO SE ENCUENTRA ACTIVO ENVIAR MENSAJE AL USUARIO
			$this->getJSON($this->setJSON($this->varGlobal['STATUS_ON_MSG'],'info'));
		}
	}
	/*
	 * VALIDAR SI SE REQUIERE ESPECIE VALORADA PARA LOS SERVICIOS
	 */
	protected function testCollectionRequestSpecies($entity){
		return $this->string2JSON($this->varGlobal['COLLECTION_REQUEST_SPECIES'])[$entity];
	}
	
	
	// *********************************************************************************************
	// PARSE - DATOS
	// *********************************************************************************************
	/*
	 * PARSE BOOLEAN
	 */
	protected function validateEntityExemptPayment($entityId,$process,$processId){
		// STRING PARA CONSULTAR ENTIDADES EXENTAS DE PAGO
		$str=$this->db->selectFromView('exentaspago',"WHERE fk_entidad_id={$entityId}");
		// VALIDAR SI PROCEDE A PAGO
		if($this->db->numRows($str)>0) return $this->setJSON($this->varGlobal['EXEMPT_FROM_PAYMENT_MSG'],'info',array('exemptPayment'=>$processId));
	}
	
	/*
	 * REGISTRAR APROBACION DE TRAMITE SIN PAGO 
	 */
	protected function insertExemptPayment($entityId,$process,$processId){
		// MODELO DE VALOR CERO
		$model=array(
			'fk_proceso'=>$process,
			'fk_proceso_id'=>$processId,
			'fk_entidad_id'=>$entityId
		);
		// STRING PARA CONSULTAR REGISTRO
		$str=$this->db->selectFromView('valorcero',"WHERE fk_proceso='{$process}' AND fk_proceso_id={$processId}");
		// CONSULTAR EXISTENCIA DE REGISTRO
		if($this->db->numRows($str)<1) return $this->db->executeTested($this->db->getSQLInsert($model,'valorcero'));
		// SALIDA POR DEFECTO
		return $this->setJSON("ok",true);
	}
	
	// **********************************************************************************************
	// ********************************** CONVERTIR NÚMERO A STRING
	// **********************************************************************************************
	// INGRESAR UN NÚMERO A STRING
	public function setNumberToLetter($number,$moneda=false){
	    $number=number_format($number,2);
	    return ($moneda)?numberToLetter::convertir($number,'DÓLARES','CENTAVOS'):numberToLetter::convertir($number);
	}
	public function setNumberToOrdinal($number){
	    return numberToOrdinal::convertir($number);
	}
	
	
	// **********************************************************************************************
	// ********************************** CONSULTAS DEL CALENDARIO **********************************
	// **********************************************************************************************
	// Retornar días no laborales
	public function getHolidays(){
		$data=array();
		foreach($this->db->findAll($this->db->selectFromView('feriado',"WHERE date_part('year',feriado_fecha)>=date_part('year',CURRENT_DATE)")) as $v){
			$data[$v['feriado_fecha']]=$v['feriado_nombre'];
		}
		return $data;
	}
	
	
	// **********************************************************************************************
	//****************** MÉTODOS PARA PARÁMETROS DEL SISTEMA - BASE DE DATOS ************************
	// **********************************************************************************************
	// Retornar los parámetros del sistema
	public function getSetting($module='MAIN'){
		// VARIABLES
		return $this->db->findAll($this->db->selectFromView('params',"WHERE param_modulo='MAIN' OR UPPER(param_modulo)=UPPER('{$module}')"));
	}
	// Desglozar parámetros en relación json Ej. [key, key, ...]
	public function getParamsKey(){
		$keyList=array();
		foreach($this->getSetting() as $val){$keyList[]=$val['param_key'];}
		return $keyList;
	}
	// Desglozar parámetros en relación json Ej. [{key: val, key: val}]
	public function getConfParams($module=''){
		// VERIFICAR SI SE REQUIERE PARÁMETROS ESPECÍFICOS DESDE HERENCIAS
		$module=($module=='')?session::$sysName:$module;
		// GENERAR MODELO DE VARIABLES GLOBALES
		$data=array();
		foreach($this->getSetting($module) as $val){$data[$val['param_key']]=$val['param_value'];}
		// OBTENER SALARIO BASICO
		$data['BASIC_SALARY']=$this->string2JSON($data['BASIC_SALARY'])[$this->getCurrentYear()];
		// LISTAR MÓDULOS Y FILTRAR NOMBRE
		$data['MODULE_NAME']=$this->string2JSON($data['MODULES_NAME'])[strtoupper(session::$sysName)];
		// RETORNAR VARIABLES GLOBALES
		return $data;
	}
	// CONSULTAR Y RETORNAR PARÁMETROS DEL SISTEMA - POR MÓDULO
	public function getParamsModule($module='MAIN'){
		// MODELO DE DATOS
		$data=array();
		// LISTADO DE PARÁMETROS SEGÚN MÓDULO
		$strWhr=($module=='ALL')?"":"WHERE param_modulo='MAIN' OR UPPER(param_modulo)=UPPER('$module')";
		$rows=$this->db->findAll($this->db->getSQLSelect('params',[],$strWhr));
		// GENERAR LISTADO DE PARÁMETROS
		foreach($rows as $val){$data[$val['param_key']]=$val['param_value'];}
		// ADJUNTAR VARIABLES DE SISTEMA Y RETORNAR
		return array_merge($data,$this->db->findOne($this->db->getSQLSelect('sistema')));
	}
	// Información del sistema. Ej. Nombre, title, header, etc
	public function configSys(){
		return $this->db->findOne($this->db->getSQLSelect('sistema'));
	}
	// PARÁMETROS DEV
	public function configDev(){
		$config=$this->getConfig(true,'dev');
		return array_merge($config['main'],$config[session::$sysName]);
	}
	
	
	
	// **********************************************************************************************
	// MÉTODOS PARA MANEJO DE SESIONES DE USUARIO ***************************************************
	// **********************************************************************************************
	/*
	 * RETORNAR PERMISOS DE USUARIO
	 */
	public function getAccesRol(){
		// OBTENER ID DE SESION
		$sessionId=session::getSessionAdmin();
		// GENERAR MODELO DE ROLES
		$roles=array();
		// LISTADO DE PERMISOS
		foreach($this->db->findAll($this->db->selectFromView('usuario_rol',"WHERE fk_usuario_id={$sessionId} ORDER BY fk_rol_id ASC")) as $v){
			// LISTAR ROLES
			$roles[]=$v['fk_rol_id']; 
		}
		// RETORNAR LISTADO
		return $roles;
	}
	/*
	 * OBTENER ID DE SESSION
	 */
	public function getSessionId(){
	    return session::getSessionAdmin();
	}
	/*
	 * OBTENER PERFIL DE USUARIO
	 */
	public function getProfile(){
		// ID DE SESIÓN DE ADMINISTRADOR
		$sessionId=session::getSessionAdmin();
		// RETORNAR DATOS DE USUARIO
		return $this->db->viewById($sessionId,'usuarios');
	}
	/*
	 * OBTENER DATOS DE USUARIO
	 */
	public function getUserInformation(){
		// ID DE SESIÓN DE ADMINISTRADOR
		$sessionId=session::getSessionAdmin();
		// RETORNAR INFORMACIÓN DE USUARIO
		return $this->db->findOne($this->db->selectFromView('vw_informacion_usuarios',"WHERE usuario_id={$sessionId}"));
	}
	/*
	 * OBTENER MODULO EN EL QUE SE ESTÁ EJECUTANDO LA FUNCION
	 */
	public function getModuleBySession(){
	    // TIPO DE SESIÓN
	    $sessionType=session::getKey();
		return $sessionType;
	}
	/*
	 * OBTENER DATOS DE USUARIO - DATOS DE PERSONAL
	 */
	public function getStaffIdBySession(){
	    // TIPO DE SESIÓN
	    $sessionType=session::getKey();
	    // VALIDAR MÓDULO DE SESIÓN
	    if($sessionType=='session.system'){
	        // ID DE USUARIO
	        $staffId=session::getSessionAdmin();
	        // STRING PARA OBTENER ID DE PERSONA
	        $strWhr=$this->db->getSQLSelect($this->db->setCustomTable('usuarios','fk_persona_id'),[],"WHERE usuario_id={$staffId}");
	        // VALIDAR EXISTENCIA DE USUARIO
	        $str=$this->db->selectFromView('personal',"WHERE fk_persona_id=({$strWhr})");
	        // VALIDAR SI EXISTE SESSION
	        if($this->db->numRows($str)==1) return $this->db->findOne($str)['personal_id'];
	    }elseif(in_array($sessionType,['session.tthh','session.subjefatura'])){
	        // DATOS DE USUARIO QUE REGISTRA LA SALIDA
	        return session::get();
	    }
	    // RETORNAR MENSAJE POR DEFECTO
	    $this->getJSON("No se ha encontrado ningún registro relacionado con su sesión:: << {$sessionType} >>.");
	}
	/*
	 * OBTENER DATOS DE USUARIO - DATOS DE 
	 */
	public function getPPersonalIdBySession(){
	    // ID DE PERSONAL
	    $staffId=$this->getStaffIdBySession();
	    // BUSCAR ID DE PPERSONAL
	    $data=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE estado='EN FUNCIONES' AND entidad='PUESTOS' AND personal_id={$staffId}"));
	    // ENVIAR ID DE SESION
	    return $data['entidad_id'];
	}
	/*
	 * OBTENER RESPONSABLE DE UNA DIRECCIÓN
	 */
	protected function getResponsibleByLeadership($indexName,$queryId='direccion_id',$returnId='ppersonal_id'){
		// LISTADO DE ID DE DIRECCIONES Y PUESTOS ESPECÍFICOS
		$listId=$this->string2JSON($this->varGlobal['LEADERSHIP_PERSONAL_ID']);
		// OBTENER DATOS DE DIRECCIÓN
		$str=$this->db->selectFromView("vw_direcciones_unidades","WHERE {$queryId}={$listId[$indexName]}");
		// RETORNAR CONSULTA
		return $this->db->findOne($str)[$returnId];
	}
	
	
	// **********************************************************************************************
	// MÉTODOS PARA MANEJO DE PARÁMETROS DEL SISTEMA - JSONs DEL PROYECTO ***************************
	// **********************************************************************************************
	/* getConfig: CONFIGURACIÓN JSON
	 * index: Nombre de objeto una vez transformado
 	 * 		  Ej. server, hosts, request_method
	 * file: personalizar nombre de json. 
	 * 		  Ej. uploader, smartQuery, model, crud, config
	 */
	public function getConfig($index,$file='config'){
		// CONVERTIR DATOS STRING TO JSON
		$json=$this->string2JSON(file_get_contents(SRC_PATH."config/{$file}.json"));
		// VALIDAR SI EXISTE VARIABLE GLOBAL CON NOMBRE DE INGRESO {$index}
		if(isset($this->varGlobal[$file])) $json=array_merge($json,$this->string2JSON($this->varGlobal[$file]));
		// VALIDAR INDEX
		$data=is_bool($index)?$json:$this->joinCrudMngr($index);
		// RETORNAR DATOS DE CONFIGURACION
		return $data;
	}
	// UNIÓN DE MODELO JSON Y CONFIG JSON
	private function joinCrudMngr($index){
		// MODELO
		$config=array();
		// VALIDAR SI EXISTE VARIABLE GLOBAL CON NOMBRE DE INGRESO {$index}
		if(isset($this->varGlobal[$index])) $config=$this->string2JSON($this->varGlobal[$index]);
		// VALIDAR SI YA SE HA CARGADO LAS VARIABLES DE CONFIGURACION
		if(isset($this->varGlobal['crud']) && $index!='crud') $config=array_merge($config,$this->string2JSON($this->varGlobal['crud']));
		// DATOS DE CONFIGURACIÓN
		$config=array_merge($config,$this->string2JSON(file_get_contents(SRC_PATH."config/config.json")));
		// DATOS DE MODELO CRUD
		$config=array_merge($config,$this->string2JSON(file_get_contents(SRC_PATH."config/crud.json")));
		// RETORNAR DATOS
		return $config[$index];
	}
	/* ACTUALIZAR DATOS DE JSON
	 * data: objetos json que se guardarán en el .json
	 * file: personalizar nombre de json. 
	 * 			 Ej. uploader, smartQuery, model, crud, config
	 */
	public function setConfig($data,$file='model',$type='json'){
		$myFile=SRC_PATH."config/$file.$type";
		$fh=fopen($myFile,'w');
		if($fh){
			fwrite($fh, json_encode($data,JSON_PRETTY_PRINT));
			fclose($fh);
		}	return $data;
	}
	/*
	 * CREAR CÓDIGO ÚNICO DE REGISTRO
	 */
	public function generateProjectCode($code,$project){
		$pref="CBSD";
		$code=str_pad($code,5,"0",STR_PAD_LEFT);
		$year=$this->getCurrentYear('y');
		return "{$pref}{$year}{$project}{$code}";
	}
	
	/*
	 * FORMAT NUMBERO
	 */
	public function setFormatNumber($número,$float=2,$separador='.'){
		return number_format($número, $float, $separador, '');;
	}
	
	/*
	 * PARSE TEXTAREA TO LI
	 */
	public function textarea2Li($txt,$style='li'){
		// PARSE TEXT TO ARRAY
		$bits = explode("\n", $txt);
		$newstring="";
		// RECORRER LISTADO DE TEXTO
		foreach($bits as $bit){ $newstring.="<li>{$bit}</li>"; }
		// RETORNAR TEXTO
		return $newstring;
	}
	
	/*
	 * REDONDEAR A 10
	 * IO: + PARA SUPEIOR - PARA INFERIOR
	 */
	public function roundTo10($valor,$io='+') {
		// Convertimos $valor a entero
		$valor = intval($valor);
		// Redondeamos al múltiplo de 10 más cercano
		$n = round($valor, -1);
		// Si el resultado $n es menor, quiere decir que redondeo hacia abajo
		// por lo tanto sumamos 10. Si no, lo devolvemos así.
		$n = ($n < $valor) ? ($n + 10) : $n;
		// VALIDAR RESULTADO - SUPERIOR
		if($io=='+') return $n; 
		// VALIDAR RESULTADO - INFERIOR
		return $n - 10;
	} 
	
	
	// **********************************************************************************************
	// MÉTODOS RELACIONADOS CON FECHAS **************************************************************
	// **********************************************************************************************
	/* getFecha: FECHA ACTUAL
	 * formato: formato de fecha
	 * DÍAS
	 * j: Día del mes sin ceros iniciales	1 a 31
	 * d: Día del mes, 2 dígitos con ceros iniciales
	 * D: Una representación textual de un día, tres letras: Mon hasta Sun
	 * l: Una representación textual completa del día de la semana:	Sunday hasta Saturday
	 * N: Representación numérica ISO-8601 del día de la semana: 1 (para lunes) hasta 7 (para domingo)
	 * w: Representación numérica del día de la semana:	0 (para domingo) hasta 6 (para sábado)
	 * S: Sufijo ordinal inglés para el día del mes, 2 caracteres: st, nd, rd o th
	 * z: El día del año (comenzando por 0)
	 * W: Número de la semana del año ISO-8601, las semanas comienzan en lunes
	 * MESES
	 * F: Una representación textual completa de un mes: January hasta December
	 * m: Representación numérica de una mes, con ceros iniciales: 01 hasta 12
	 * m: Una representación textual corta de un mes, tres letras: Jan hasta Dec
	 * n: Representación numérica de un mes, sin ceros iniciales	1 hasta 12
	 * t: Número de días del mes dado	28 hasta 31
	 * AÑO
	 * a:	Ante meridiem y Post meridiem en minúsculas	am o pm
	 * A:	Ante meridiem y Post meridiem en mayúsculas	AM o PM
	 * B:	Hora Internet	000 hasta 999
	 * g:	Formato de 12 horas de una hora sin ceros iniciales	1 hasta 12
	 * G:	Formato de 24 horas de una hora sin ceros iniciales	0 hasta 23
	 * h:	Formato de 12 horas de una hora con ceros iniciales	01 hasta 12
	 * H:	Formato de 24 horas de una hora con ceros iniciales	00 hasta 23
	 * i:	Minutos, con ceros iniciales	00 hasta 59
	 * s:	Segundos, con ceros iniciales	00 hasta 59
	 * ZONA HORARIA
	 * e:	Identificador de zona horaria (añadido en PHP 5.1.0)	Ejemplos: UTC, GMT, Atlantic/Azores
	 * I: (i mayúscula)	Si la fecha está en horario de verano o no	1 si está en horario de verano, 0 si no.
	 * O:	Diferencia de la hora de Greenwich (GMT) en horas	Ejemplo: +0200
	 * P:	Diferencia con la hora de Greenwich (GMT) con dos puntos entre horas y minutos (añadido en PHP 5.1.3)	Ejemplo: +02:00
	 * T:	Abreviatura de la zona horaria	Ejemplos: EST, MDT ...
	 * Z:	Índice de la zona horaria en segundos. El índice para zonas horarias al oeste de UTC siempre es negativo, y para aquellas al este de UTC es siempre positivo.	-43200 hasta 50400
	 */
	public function getCurrentYear($type='Y'){
		return $this->getFecha($type);
	}
	public function getFecha($formato='Y-m-d'){
		$calendar=$this->getConfig('calendar');
		$dias=$calendar['days'];
		$meses=$calendar['months'];
		$complete=$dias[date('w')].", ".date('d')." de ".$meses[date('n')-1]. " de ".date('Y');
		if($formato=='lastEndMonth')return date("Y-m-t",strtotime($this->getFecha()."- 1 month"));
		elseif($formato=='currentEndMonth')return date("Y-m-t",strtotime($this->getFecha()));
		elseif($formato=='complete')return $complete;
		elseif($formato=='full')return "{$complete} - ".date('h:i:s A');
		elseif($formato=='dateTime')return date('Y-m-d H:i:s');
		else return date($formato);
	}
	public function cleanSpecialCharacters($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
		return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
	}
	// DECIMALES A HORAS
	public function setNumbreToTime($numbre,$format='H:mm'){
		// CONVERTIR DECIMALES A HORAS
		// $horas=explode('.',$numbre);
		$horas=number_format($numbre,2);
		// DATOS
		$h=intval($horas);
		// $m=$this->setTimeToDecimal(($horas[1]*60/100),0);
		$m=intval(($horas-$h)*60);
		// VALIDAR PARAMETRO
		if($format=='H:mm') return "{$h}:{$m}";
		// PARSE HORAS
		if($format=='time'){
			// MENSAJE
			$time="";
			// PARSE HORAS
			if( $h > 0 ) $time.=" {$h} hora(s)";
			// PARSE MINUTOS
			if( $m > 0 ) $time.=" {$m} minuto(s)";
			// RETORNAR DATOS
			return $time;
		}
	}
	// SET FORMAT FLOAT TO TIME
	public function setDecimalToTime($dec,$inSeconds=false,$formato='H:i'){
		// verificar la entrada está en decimales(true) o segundos(false)
		if(!$inSeconds) $dec=($dec/3600);
		// start by converting to seconds
		$seconds=($dec * 3600);
		// we're given hours, so let's get those the easy way
		$hours=floor($dec);
		// since we've "calculated" hours, let's remove them from the seconds variable
		$seconds -= $hours * 3600;
		// calculate minutes left
		$minutes=floor($seconds / 60);
		// remove those from seconds as well
		$seconds -= $minutes * 60;
		// return the time formatted HH:MM:SS
		return $this->setFormatDate(str_pad($hours,2,"0",STR_PAD_LEFT).":".str_pad($minutes,2,"0",STR_PAD_LEFT).":".str_pad($seconds,2,"0",STR_PAD_LEFT),$formato);
	}
	// SET DATE TO DECIMAL
	public function setTimeToDecimal($time,$float=2){
		// SEPARAR FORMATO
		$hms = explode(":", $time);
		// HACER CALCULOS Y RETORNAR
		return number_format($hms[0] + ($hms[1]/60) + ($hms[2]/3600),$float);
	}
	// FORMAT DATE
	public function setFormatDate($date,$formato='Y-m-d H:i:s'){
		$calendar=$this->getConfig('calendar');
		$dias=$calendar['days'];
		$meses=$calendar['months'];
		$date=strtotime($date);
		// ESTRUCTURA DE LA FECHA
		$_date=date('d',$date);
		$_day=$dias[date('w',$date)];
		$_month=$meses[date('n',$date)-1];
		$_year=date('Y',$date);
		$_time=date('H:i:s',$date);
		// FORMATO
		$complete="{$_day}, {$_date} de {$_month} de {$_year}";
		if($formato=='m de Y') return "{$_month} de {$_year}";
		elseif($formato=='complete')return $complete;
		elseif($formato=='medium')return "{$_date} de {$_month} de {$_year}";
		elseif($formato=='short')return "{$_month} de {$_year}";
		elseif($formato=='full')return "{$complete} - {$_time}";
		else return date($formato,$date);
	}
	// FORMAT DATETIME
	public function setFormatDateTime($dateTime,$format='Y-m-d H:i:s',$area="UTC",$zone="America/Guayaquil"){
		// VALIDAR ZONA HORARIA
		// 
		// CAMBIOS DE ZONA HORARIA
		$UTC=new \DateTimeZone($area);
		$newTZ=new \DateTimeZone($zone);
		$date=new \DateTime($dateTime,$UTC);
		$date->setTimezone($newTZ);
		return $date->format($format);
	}
	// SUMAR HORAS
	public function addHoursTodate($date,$hours,$add='+'){
		$fecha=strtotime("{$add}{$hours} hours",strtotime($date));
		return date('Y-m-d H:i:s',$fecha);
	}
	// SUMAR DÍAS
	public function addDays($date,$days,$add='+'){
		$fecha=strtotime("$add$days days",strtotime($date));
		return date('Y-m-d',$fecha);
	}
	// SUMAR DÍAS
	public function addYears($date,$years,$add='+'){
		$fecha=strtotime("$add$years years",strtotime($date));
		return date('Y-m-d',$fecha);
	}
	// SUMAR DÍAS LABORABLES
	public function addWorkDays($date,$days,$add='+'){
		// DIAS NO LABORABLES
		$holidays=$this->getHolidays();
		// MODELO DE VALIDACIÓN
		$tempData=array(
			'from'=>$date,
			'type'=>"$add $days",
			'days'=>array()
		);
		// RECORRER DÍAS
		$i=0;
		$count=0;
		$newDate=$date;
		while($days>=$count){
			// GENERAR NUEVA
			$newDate=$this->addDays($date,$i,$add);
			// VARIABLE PARA VALIDACIÓN
			$strTemp="";
			// VALIDAR EN FERIADOS Y FINES DE SEMANA
			if(in_array(date('w',strtotime($newDate)),[0,6])){
				$strTemp="Fin de semana [$newDate] -> ".date('l',strtotime($newDate));
			}elseif(isset($holidays[$newDate])){
				$strTemp="Feriado [$newDate] -> $holidays[$newDate]";
			}else{
				$strTemp="Dia $count validado [$newDate]";
				$count+=1;
			}	$i+=1;
			// VALIDACIÓN DE DATO
			$tempData['days'][$newDate]=$strTemp;
		}	return $newDate;
	}
	/* getMonths: LISTADO DE AÑOS DESDE EL FUNCIONAMIENTO DEL SISTEMA
	 * Ej. [2016, 2017, ...]
	 */
	public function getYears($year=2015){
		$years=array();
		for($i=2016;$i<=$year;$i++){$years[]=$i;}
		return $years;
	}
	/* getMonths: LISTADO DE MESES
	 * month: mes en curso o todos
	 * op: palabra completa o abreviada
	 * Ej. [Enero, Febrero, ...]
	 */
	public function getMonths($month=0,$op='months'){
		$months=$this->getConfig('calendar')[$op];
		if($month==1)$month=2;
		if($month>0)$months=array_slice($months,0,$month);
		return $months;
	}
	/* getDays: LISTADO DE DÍAS DE LA SEMANA
	 * day: día en curso o todos
	 * op: palabra completa o abreviada
	 * Ej. [Lunes, Martes, Mie, Jue, ...]
	 */
	public function getDays($day=0,$op='days'){
		$days=$this->getConfig('calendar')[$op];
		if($day>0)$days=array_slice($days, 1, $day);
		return $days;
	}
	/* getDaysByWeek: LISTADO DE DÍAS CALENDARIO DE LA SEMANA
	 * week: semana en curso
	 * day: día en curso o todos. Ej. 1, 2, 4
	 * op: palabra completa o abreviada
	 * Ej. [Lunes 2, Martes 3, Mie 3, Jue, ...]
	 */
	public function getDaysByWeek($week,$day=5,$op='shortDays'){
		$datasets=array();
		$year=$this->getFecha('Y');//#año
		for($i=1;$i<=$day;$i++){
			$gendate=new \DateTime();
			$gendate->setISODate($year,$week,$i);
			$x=$gendate->format('Y-m-d');
			if($x==$this->getFecha())$x="$x (Hoy)";
			$datasets[]=$x;
		}	return $datasets;
	}
	/* daysByMonth: LISTADO DE DÍAS CALENDARIO DE UN MES
	 * year: año en curso
	 * month: mes en curso
	 * day: día en curso o todos
	 * op: palabra completa o abreviada
	 * Ej. [Lun 12, Mar 13, Jue 14]
	 */
	public function daysByMonth($year,$month,$day,$op='shortDays'){
		$dias=$this->getConfig('calendar')[$op];
		$datasets=array();
		for($i=1;$i<=$day;$i++){
			$datasets[]=$dias[date('w',strtotime("$year-$month-$i"))]." $i";
		}	return $datasets;
	}
	/* datesByWeek: LISTADO DE FECHAS A PARTIR DEL NÚMERO DE SEMANA Y AÑO
	 * week: # semana en curso
	 * year: año en curso
	 * limitDay: por defecto 5=>lunes a viernes
	 * format: formato de fecha
	 * Ej. [2017-01-12, 2017-01-13, 2017-01-14]
	 */
	public function datesByWeek($week,$year,$limitDay=5,$format='Y-m-d'){
		$dates=array();
		for($i=1;$i<=$limitDay;$i++){
			$dates[]=date($format,strtotime("{$year}-W{$week}-$i"));
		}	return $dates;
	}
	/* weeksByMonth: LISTADO DE FECHAS Y SEMANAS A PARTIR DEL AÑO Y MES EN CURSO
	 * year: año en curso
	 * month: mes en curso
	 * Ej. [week1, week2], [2017-01-12, 2017-01-13, 2017-01-14]
	 */
	public function weeksByMonth($year,$month){
		// GENERAR MODELO DE RETORNO
		$data=array('weeks'=>array(),'names'=>array(),'dates'=>array());
		// OBTENER NÚMERO DE DÍAS DEL MES
		$cantDias=date('t',strtotime("{$year}-{$month}-01"));
		// LISTAR SEMANAS DEL MES
		for($i=1;$i<=$cantDias;$i++){
			// VALIDAR SI LOS DIAS RECORRIDOS SON DOMINGOS Y GENERAR MODELO
			if(date('w',strtotime("{$year}-{$month}-{$i}"))==6){
				// AGREGAR SEMANAS DEL MES
				$data['weeks'][]=date('W',mktime(0,0,0,$month,$i,$year));
				// AGREGAR NOMBRE DE LA SEMANA
				$data['names'][]="S".date('W',mktime(0,0,0,$month,$i,$year))." [".date('m-d',mktime(0,0,0,$month,$i-5,$year))." ".date('m-d',mktime(0,0,0,$month,$i+1,$year))."]";
			}
		}
		// FECHAS POR SEMANA
		foreach($data['weeks'] as $week){
			// GENERAR FECHAS DE CADA SEMANA
			$data['dates'][$week]=$this->datesByWeek($week,$year,7);
		}	
		// RETORNAR MODELO
		return $data;
	}
	/* getDaysFromDates: LISTADO DE FECHAS A PARTIR DE UNA FECHA DE INICIO Y UNA DE FIN
	 * $dateIn: fecha de inicio de generación
	 * $dateOut: fecha hasta la de generar
	 * Ej. [2017-01-12, 2017-01-13, 2017-01-14]
	 */
	public function getDaysFromDates($dateIn,$dateOut){
		// MODELO DE FECHAS
		$dates=array();
		// RECORRER DÍAS
		$i=0;
		$newDate=$dateIn;
		while($newDate<$dateOut){
			// GENERAR NUEVA
			$newDate=$this->addDays($dateIn,$i);
			// AGREGAR FECHA
			$dates[]=$newDate;
			// AUMENTAR ITERACIÓN
			$i+=1;
		}
		// RETORNAR FECHAS PARA VACACIONES
		return $dates;
	}
	/*
	 * TIPO DE DIA POR FECHA
	 */
	public function getTypeDayFromDate($date){
		// VALIDAR ESTADO DE EMERGENCIA
		if($this->db->numRows($this->db->selectFromView("estadosemergencia","WHERE '{$date}'::DATE >= eemergencia_fecha::DATE AND '{$date}'::DATE <= eemergencia_cierre::DATE"))) return 'ESTADO DE EMERGENCIA';
		// VALIDAR DIAS DE FERIADOS
		if($this->db->numRows($this->db->selectFromView("feriado","WHERE '{$date}'::DATE = feriado_fecha::DATE"))) return 'FERIADO';
		// VALIDAR FIN DE SEMANA
		if(in_array(date('w',strtotime($date)),[0,6])) return 'FIN DE SEMANA';
		// VALIDAR DIA LABORABLE
		return 'LABORABLE';
	}
	/*
	 * PARSE 2 FECHAS A RANGOS
	 */
	public function setParseRangeDates($date1,$date2,$type='HORAS'){
	    // MODELO 
	    $row=array();
	    
	    // PARSE FECHAS
	    $row['desde_alt']=strtolower($this->setFormatDate($date1,($type=='HORAS')?'full':'complete'));
	    $row['hasta_alt']=strtolower($this->setFormatDate($date2,($type=='HORAS')?'full':'complete'));
	    $datePermission=strtolower($this->setFormatDate($date1,'complete'));
	    
	    // MENSAJE POR DEFECTO
	    $row['fecha_alt']="desde el día {$row['desde_alt']} hasta el día {$row['hasta_alt']}";
	    // VALIDAR DATOS
	    if($this->setFormatDate($date1,'Y-m-d')==$this->setFormatDate($date2,'Y-m-d')){
	        // PARAMETRIZACION DE PERMISO
	        if($type=='HORAS'){
	            // PARSE HORAS
	            $timeFrom=$this->setFormatDate($date1,'H:i');
	            $timeTo=$this->setFormatDate($date2,'H:i');
	            // IMPRESION DE FECHAS
	            $row['fecha_alt']="el día {$datePermission} desde la(s) {$timeFrom} hasta la(s) {$timeTo}";
	        }else{
	            // IMPRESION DE FECHAS
	            $row['fecha_alt']="el día {$datePermission}";
	        }
	    }
	    
	    // RETORNAR MODELO
	    return $row;
	}
	/*
	 * PASRSE DIAS ENTRE DOS FECHAS
	 */
	public function getNumDaysFromDates($date1,$date2){
	    $firstDate  = new \DateTime($date1);
	    $secondDate = new \DateTime($date2);
	    return $firstDate->diff($secondDate);
	}
	
	// *********************************************************************************************
	// PARSE - DATOS
	// *********************************************************************************************
	/*
	 * PARSE BOOLEAN
	 */
	public function getBool($val){
		return boolval($val)?'true':'false';
	}
	
	
	// **********************************************************************************************
	// GEOJSON
	// **********************************************************************************************
	/*
	 * REGISTRAR LAYERS DE MAPAS
	 */
	protected function setMapCoordinates($entity,$entityId,$post,$io=true){
		// VALIDAR Y CARGAR ARCHIVO
		$post=$this->uploaderMng->decodeBase64ToImg($post,'coordenadas',$io);
		// INSERTAR DATOS DE ENTIDAD RELACIONADA
		$post['coordenada_entidad']=$entity;
		$post['coordenada_entidad_id']=$entityId;
		// CONSULTAR REGISTRO DE LAYER
		$str=$this->db->selectFromView('coordenadas',"WHERE coordenada_entidad='{$entity}' AND coordenada_entidad_id={$entityId}");
		// CONSULTAR EXISTENCIA DE LAYER
		if($this->db->numRows($str)>0){
			// NUEVO MODELO DE LAYER
			$post=array_merge($this->db->findOne($str),$post);
			// GENERAR STRING DE ACTUALIZACION
			$str=$this->db->getSQLUpdate($post,'coordenadas');
		}else{
			// GENERAR STRING DE INGRESO
			$str=$this->db->getSQLInsert($post,'coordenadas');
		}
		// EXECUTAR CONSULTA
		return $this->db->executeTested($str);
	}
	
	/*
	 * REGISTRAR LAYERS DE MAPAS
	 */
	protected function setGeoJSON($entity,$entityId,$listLayer){
		// CONSULTA POR DEFECTO
		$sql=$this->setJSON("Sin layers ingresadas",true);
		// STRING PARA LISTA DE IDS
		$strIDs="";
		// RECORRER LISTADO DE LAYERS
		foreach($listLayer as $v){
			// GENERAR MODELO AUXILIAR
			$aux=array(
				'mapa_entidad'=>$entity,
				'mapa_entidad_id'=>$entityId,
				'mapa_geojson_id'=>$v['id'],
				'mapa_geojson'=>json_encode($v)
			);
			// CONSULTAR REGISTRO DE LAYER
			$str=$this->db->selectFromView('georeferenciacion',"WHERE mapa_entidad='{$entity}' AND mapa_entidad_id={$entityId} AND mapa_geojson_id={$v['id']}");
			// CONSULTAR EXISTENCIA DE LAYER
			if($this->db->numRows($str)>0){
				// NUEVO MODELO DE LAYER
				$aux=array_merge($this->db->findOne($str),$aux);
				// GENERAR STRING DE ACTUALIZACION
				$str=$this->db->getSQLUpdate($aux,'georeferenciacion');
			}else{
				// GENERAR STRING DE INGRESO
				$str=$this->db->getSQLInsert($aux,'georeferenciacion');
			}
			// EXECUTAR CONSULTA
			$sql=$this->db->executeTested($str);
			// LISTAR ID QUE NO SERÁ ELIMINARDO
			$strIDs.="{$v['id']},";
		}
		// VALIDAR ID DE LAYERS
		if($strIDs!=""){
			// ELIMINAR COMAS (,) DEMÁS EN PARÁMETROS
			$strIDs=trim($strIDs,",");
			// ELIMINAR REGISTROS
			$sql=$this->db->executeTested($this->db->getSQLDelete('georeferenciacion',"WHERE mapa_entidad='{$entity}' AND mapa_entidad_id={$entityId} AND mapa_geojson_id NOT IN ({$strIDs})"));
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * OBTENER REGISTRO DE GEOJSON
	 */
	protected function getGeoJSON($entity,$entityId){
		// MODELO POR DEFECTO 
		$data=array();
		// STRING DE CONSULTA DE LAYERS
		$str=$this->db->selectFromView('georeferenciacion',"WHERE mapa_entidad='{$entity}' AND mapa_entidad_id={$entityId}");
		// RECORRER LISTADO DE LAYERS
		foreach($this->db->findAll($str) as $v){
			// LISTAR LAYER
			$data[]=$this->string2JSON($v['mapa_geojson']);
		}
		// RETORNAR LISTADO DE LAYERS
		return $data;
	}
	
	
	// **********************************************************************************************
	// MÉTODOS PARA RETORNAR Y OBTENER DATOS: PHP - AJAX ********************************************
	// **********************************************************************************************
	// Extraer datos desde objeto AngularJS o Método POST PHP
	public function requestPost(){
		return array_merge((array)json_decode(file_get_contents("php://input"),true),
				(array)filter_input_array(INPUT_POST));
	}
	// Extraer datos GET PHP
	public function requestGet(){
		return filter_input_array(INPUT_GET);
	}
	/* Datos para interceptar modelo CRUD PHP
	 * SALIDA
	 * sys: nombre de sistema - permiso conexión
	 * db: nombre base de datos
	 * tb: nombre de entidad 
	 * id: id de entidad manejada
	 * Si el request supera los 4 campos db es alterada
	 * db: PUT - REQUEST - DELETE 
	 */ 
	public function getURI(){
		$request=array();
		$params=explode("/",$_GET['_url']);
		if(isset($_GET['r'])){
			$request=array(
				'sys'=>$_GET['sys'],
				'tb'=>$_GET['tb'],
				'db'=>getRequestMethod()
			);
		}
		$otherRequest=array('PUT','REQUEST','DELETE','DOWNLOAD');
		if(count($params)>=3){
			$request=array('sys'=>$params[1],'tb'=>$params[2],'db'=>getRequestMethod());
			if(count($params)>3 && in_array($params[3],$otherRequest))$request['db']=$params[3];
		}	return array_merge($_GET,$request);
	}
	// Mensajes de retorno - DB -> parse function
	public function msgReplace($msg,$data){
		foreach($data as $key=>$val){
			$msg=(is_array($val))?$this->recursiveReplace($msg,$val):preg_replace('/{{'.$key.'}}/',$val,$msg); 
		}	return $msg;
	}
	// Setear mensaje en formato array para retornar como json
	public function setJSON($msg,$estado=false,$data=array()){
		return array('mensaje'=>$msg,'estado'=>$estado,'data'=>$data);
	}
	// Retornar mensaje a ajax
	public function getJSON($json){
		// Json AutoDeclare - si no es array convertir primeto
		if(!is_array($json)) $json=$this->setJSON($json);
		// FINALIZAR EVENTO
		echo json_encode($json,JSON_PRETTY_PRINT);
		exit;
	}
	// PARSE STRING TO JSON
	public function string2JSON($string){
		return json_decode($string,true);
	}
	// Imprimir mensaje de no autorizado
	public function e301(){
		$this->getJSON('¡Lamentablemente no cuenta con permisos para realizar la operación!');
	}
	// Retornar mensaje: No se encuetra la petición que desea realizar
	public function failJSON($metodo){
		$this->getJSON("La acción que desea ralizar no se encuentra en los métodos: $metodo");
	}
	// Validar si un objecto es no es correcto
	public function testEmpty($data){
		return (empty($data)||$data==''||!isset($data))?false:true;
	}
	// Desvincular array
	public function recursiveReplace($row,$viewHTML){
		foreach($row as $key=>$val){
			$viewHTML=(is_array($val))?$this->recursiveReplace($val,$viewHTML):preg_replace('/{{'.$key.'}}/',$val,$viewHTML); 
		}	return $viewHTML;
	}
	// VALIDACIONES POR DEFECTO
	protected function interceptor($tb='',$method=''){
		// LISTADO DE METODOS EJECUTADAS
		$interceptor=$this->getConfig('interceptor');
		// VALIDAR SI EL MÉTODO REQUIERE DE UNA FUNCIÓN ESPECÍFICA
		// print_r('INTERCEPTOR');
		// print_r('<br>');
		// print_r($interceptor);
		// print_r($interceptor[$method][$tb]);
		if(isset($interceptor[$method][$tb])){
			// INSTANCIA DE MODELO
			$model=new $interceptor['model'][$tb];
			// OBTENER NOMBRE DE FUNCIÓN QUE SE NECESITA
			$method=$interceptor[$method][$tb];
			// LLAMAR AL FUNCIÓN REQUERIDA
			$request=$model->$method();
			// VALIDAR EL ESTADO DE CONSULTA 
			if(isset($request['estado']) && !$request['estado']) $this->getJSON($request);
			// RETORNAR CONSULTA 
			return $request;
		}
	}
	
	
	// **********************************************************************************************
	// MÉTODOS PARA VALIDACIONES DE FORMATOS
	// **********************************************************************************************
	// Verifica si el valor del campo ingresado es un email
	protected function is_mail($email){
		// Si el $email es email válido, return true
		return (!filter_var($email, FILTER_VALIDATE_EMAIL) === false)?true:false;
	}
	// Verifica si el valor del campo ingresado boolean
	protected function isBool($val){
		return (is_bool($val) && $val)?true:false;
	}
	// VALIDAR DOCUMENTO DE IDENTIDAD
	protected function validateDocIdentity($identification,$type){
	    
	    // INSTANCOA DE VALIDADOR
	    $this->validador = new ctrl\identityCardController;
	    
		// VALIDAR TIPO DE IDENTIFICACIÓN
		if($type=='CEDULA') $status=$this->validador->validarCedula($identification);
		
		elseif($type=='natural') $status=$this->validador->validarRucPersonaNatural($identification);
		
		elseif($type=='publica') $status=$this->validador->validarRucSociedadPublica($identification);
		
		elseif($type=='privada') $status=$this->validador->validarRucSociedadPrivada($identification);
		
		elseif($type=='fiscal') $status=$this->validador->validarCodigoAmie($identification);
		
		elseif(in_array($type,['otro','OTRO','extranjero'])) return $this->setJSON('',true);
		
		else return $this->setJSON("No ha ingresado un tipo de identificación válida",false);
		
		// RETORNAR CONSULTA
		return $this->setJSON($this->validador->getError(),$status);
	}
	
	
	// **********************************************************************************************
	// MANEJO DE CONTRASEÑAS
	// **********************************************************************************************
	/*
	 * VALIDACIÓN DE SEGURIDAD DE CONTRASEÑA
	 */
	protected function testLevelPwd($clave){
		$min=$this->varGlobal['NEW_PWD_MIN'];
		$max=$this->varGlobal['NEW_PWD_MAX'];
		if(strlen($clave) < $min) return $this->setJSON("La clave debe tener al menos $min caracteres");
		if(strlen($clave) > $max) return $this->setJSON("La clave no puede tener más de $max caracteres");
		if($this->varGlobal['NEW_PWD_UPPERCASE']=='SI' && !preg_match('`[A-Z]`',$clave)) return $this->setJSON("La clave debe tener al menos una letra mayúscula");
		if($this->varGlobal['NEW_PWD_LOWERCASE']=='SI' && !preg_match('`[a-z]`',$clave)) return $this->setJSON("La clave debe tener al menos una letra minúscula");
		if($this->varGlobal['NEW_PWD_NUMBER']=='SI' && !preg_match('`[0-9]`',$clave)) return $this->setJSON("La clave debe tener al menos un caracter numérico");
		return $this->setJSON("OK",true);
	}
	/*
	 * VALIDAR PARES DE CONTRASEÑAS
	 */
	protected function validatePairPassword($pass1,$pass2,$encrypt='md5'){
		// VALIDAR QUE LAS CONTRASEÑAS SEAN IGUALES
		if($pass1!==$pass2) $this->getJSON("Las contraseñas no coinciden");
		// VALIDAR QUE LA CONTRASELA CUMPLA CON LA PARAMETRIZACION
		$validate=$this->testLevelPwd($pass1);
		if(!$validate['estado']) $this->getJSON($validate);
		// RETORNAR CONTRASEÑA ENCRIPTADA
		return $this->encryptText($pass1,$encrypt);
	}
	/*
	 * ENCRIPTAR TEXTO
	 */
	protected function encryptText($text,$type='md5'){
		// VALIDAR LOS TIPOS DE ENCRIPTACION
		if($type=='md5') $text=hash('md5',$text);
		elseif($type!='md5') $text=hash($type,$text);
		// RETORNAR TEXTO ENCRIPTADO
		return $text;
	}
	/*
	 * Crea una contraseña temporal
	 */
	protected function generatePassword($length=13) {
		$chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		return substr(str_shuffle($chars),0,$length);
	}
	
	
	// **********************************************************************************************
	// MANEJO DE SESIONES USUARIO
	// **********************************************************************************************
	/*
	 * CREAR Y REGISTRAR SESION DE USUARIO
	 */
	public function setSession($entityId,$entity='usuarios'){
		// ENVIAR PARÁMETROS DE BITACORA
		$sql=$this->setBinnacle($entityId,$entity,'Inicio de sesión');
		// SETTEAR SESIÓN
		if($this->isBool($sql['estado']))session::set($entityId,$entity);
		// RETORNAR CONSULTA
		return $sql;
	}
	/*
	 * CERRAR SESIÓN Y REGISTRAR SALIDA DEL SISTEMA
	 */
	public function logout(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// OBTENER INFORMACIÓN DE USUARIO
		$session=session::decodeSession()['session'];
		// ENVIAR PARÁMETROS DE BITACORA
		$this->setBinnacle($session['value'],$session['entity'],'Salida del sistema');
		// DESTRUIT SESIÓN
		$estado=session::destroy();
		$request=$this->setJSON($estado?'Buen día!':'Error de comunicación, vuelva a intentar!',$estado);
		// RETORNAR MENSAJE DE SALIDA
		$this->getJSON($this->db->closeTransaction($request)); 
	}
	/*
	 * VERIFICAR SI EXISTE SESION DE USUARIO 
	 */
	public function testSession(){
		$this->getJSON(session::decodeSession()); 
	}
	/*
	 * REGISTRO DE BITACORA
	 */
	public function setBinnacle($entityId,$entity,$msg){
		// REGISTRAR ACCESO FALLIDO
		$data=$this->getCurrentPC();
		$data['descripcion']=$msg;
		$data['fk_id']=$entityId;
		$data['fk_tb']=$entity;
		// REGSITART BITACORA
		return $this->db->executeTested($this->db->getSQLInsert($data,'bitacora'));
	}
	/*
	 * Datos de información de equipo que accede el usuario
	 */
	protected function getCurrentPC(){
		extract($this->getConfig('defaultLocation'));
		$ip=$this->getIP();
		extract(json_decode(file_get_contents("http://ipinfo.io/{$_SERVER["REMOTE_ADDR"]}/json"),true));
		if($country!=''){
			$aux=$this->getConfig(false,'countries')['countries'];
			$country="{$aux[$country]['name']} ($country)";
		}
		return array(
			'date'=>$this->getFecha('full'),
			'os'=>$this->getOS(),
			'browser'=>$this->getBrowser(),
			'ipaddress'=>$ip,
			'hostname'=>$hostname,
			'isp'=>$org,
			'country'=>$country,
			'region'=>$region,
			'city'=>$city,
			'location'=>$loc,
			'equipo'=>"{$this->getOS()} en {$this->getBrowser()}"
		);
	}
	/*
	 * Método: obtener IP
	 */
	private function getIP(){
		if(getenv('HTTP_X_FORWARDED_FOR')){
			$pipaddress=getenv('HTTP_X_FORWARDED_FOR');
			$ipaddress=getenv('REMOTE_ADDR');
			return $pipaddress. "(via $ipaddress)" ;
		}else{
			$ipaddress=getenv('REMOTE_ADDR');
			return $ipaddress;
		}
	}
	// Obtener coordenadas
	private function getLocation(){
		return array('ipaddress'=>$this->getIP());
	}
	/*
	 * Método: obtner sistema operativo
	 */
	private function getOS(){
		$user_agent=$_SERVER['HTTP_USER_AGENT'];
		$os_platform="Unknown OS Platform";
		foreach($this->getConfig('osList') as $key=>$val){
			if(preg_match($key,$user_agent))$os_platform=$val;
		}	return $os_platform." (".gethostbyaddr($_SERVER['REMOTE_ADDR']).")";
	}
	/*
	 * Método: obtner navegar
	 */ 
	private function getBrowser(){
		$user_agent=$_SERVER['HTTP_USER_AGENT'];
		$browser="Unknown Browser";
		$version="";
		foreach($this->getConfig('browserList') as $regex=>$value){
			if(preg_match($regex,$user_agent))$browser=$value;
		}	$known=array('Version',$browser,'other');
		$pattern='#(?<browser>' . join('|', $known) .')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern,$user_agent,$matches)) {
			// we have no matching number just continue
		}
		$i=count($matches['browser']);
		if ($i != 1) {
			//we will have two since we are not using 'other' argument yet, see if version is before or after the name
			if(strripos($user_agent,"Version") < strripos($user_agent,$browser)) $version= $matches['version'][0];
			else $version=$matches['version'][1];
		} else $version=$matches['version'][0];
		if($version==null || $version=="")$version="?";
		if($browser=="Trident") $browser="Internet Explorer";
		return "$browser v$version";
	}
	
	
	// **********************************************************************************************
	// OTROS MÉTODOS
	// **********************************************************************************************
	/*
	 * VALIDACIONES EXTRAS
	 */
	public function testRequestNumber($entity,$numero_solicitud,$entityId=0){
		// VALIDAR NÚMERO DE SOLICITUD
		if(!is_numeric($numero_solicitud))$this->getJSON('Número de solicitud inválido');
		else{
			$numero_solicitud=intval($numero_solicitud);
			$requestNumber="$numero_solicitud";
			// VALIDAR ID DE SERIE
			if($this->db->numRows($this->db->getSQLSelect('solicitudes',[],"WHERE solicitud_serie={$requestNumber}"))>0)
				$this->getJSON("Número de serie <b>{$requestNumber}</b> ya registrado, verifique que se ha ingresado correctamente!");
			// ID DE ENTIDAD
			if($entityId==0) $entityId=$this->db->getNextId($entity);
			// VALIDAR SOLICITUDES YA REGISTRADAS EN ESPECIES
			$request=array(
				'solicitud_entidad'=>$entity,
				'solicitud_entidad_id'=>$entityId,
				'solicitud_serie'=>$requestNumber,
				'fk_usuario_id'=>session::get()
			);
			$this->db->executeTested($this->db->getSQLInsert($request,'solicitudes'));
			// VALIDAD 1RA ESPECIE DEL AÑO
			if(abs($numero_solicitud)<($this->varGlobal['SYS_SERIAL_SPECIE_INIT']))
				$this->getJSON('Número de Solicitud fuera de rango - PERIODO 2017');
		}	return $requestNumber;
	}
	/*
	 * ENVIAR NOTIFICACIONES WHATSAPP
	 */
	protected function sendWhatsAppNotification($entity,$data){
	    
	    return true;
	    
		// INSTANCIA DE WHATSAPP - TWILIO
		$ws=new ctrl\whatsappController();
		// VIRTUALIZACION DEL SERVICIO
		$ws->prepareNotifications($entity,$this->string2JSON($this->varGlobal['WHATSAPP_NOTIFICATIONS']),$data);
	}
	
}