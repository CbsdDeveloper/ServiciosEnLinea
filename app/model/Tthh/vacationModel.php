<?php namespace model\Tthh;
use api as app;
use controller as ctrl;

/**
 * @author Lalytto
 *
 */
class vacationModel extends breakModel {
	
	/*
	 * VARIABLES GLOBALES
	 */
	private $entity='vacaciones_solicitadas';
	private $insert=0;
	private $update=0;
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct($this->entity);
		// PARÁMETROS DE MODULO
		$this->config=$this->getConfig(true,'tthh');
	}
	
	
	/*
	 * PROCESAR VACACIONES GENERADAS
	 */
	private function processGeneratedHolidays($pathFile,$entity){
		// INSTANCIA XLS
		$myExcel=new ctrl\xlsController();
		// OBTENER DATOS DE EXCEL
		$data=$myExcel->getCellValue($this->post,$pathFile,$entity);
		// VALIDAR LONGITUD DE DATOS
		if(count($data)<1)$this->getJSON("Operación abortada, no se ha encontrado ningun registro...");
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// RECORRER LISTADO
		foreach($data as $v){
			// STRING PARA CONSULTAR REGISTRO DE PERSONAL
			$str=$this->db->selectFromView("vw_basic_personal","WHERE persona_doc_identidad='{$v['persona_doc_identidad']}'");
			// VALIDAR EXISTENCIA DE REGISTRO
			if($this->db->numRows($str)<1)$this->getJSON($this->db->closeTransaction($this->setJSON("No se ha encontrado ningún registro relacionado con el # de identificación <b>{$v['persona_doc_identidad']}</b>, favor revise los datos y vuelva a intentar.")));
			// OBTENER REGISTRO DE PERSONAL
			$staff=$this->db->findOne($str);
			// REGISTRAR ID DE PERSONAL
			$v['fk_personal_id']=$staff['personal_id'];
			// REGISTRAR DETALLE DE INGRESO
			$v['vacacion_detalle']="REGISTRO DE VACACIONES DESDE {$v['vacacion_periodo_inicio']} HASTA {$v['vacacion_periodo_cierre']}";
			// STRING PARA CONSULTAR REGISTRO DE VACACIONES
			$str=$this->db->selectFromView($entity,"WHERE fk_personal_id={$v['fk_personal_id']} AND (vacacion_periodo_inicio='{$v['vacacion_periodo_inicio']}' AND vacacion_periodo_cierre='{$v['vacacion_periodo_cierre']}')");
			// VALIDAR EXISTENCIA DEL REGISTRO
			if($this->db->numRows($str)>0){
				// SUMAR CONTADOR
				$this->update+=1;
			}else{
				// EJECUTAR SENTENCIA DE CONSULTA
				$sql=$this->db->executeTested($this->db->getSQLInsert($v,$entity));
				// SUMAR CONTADOR
				$this->insert+=1;
			}
		}
		
		// INGRESAR COMO RETORNO LOS DATOS QUE SE HAN INGRESADO
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: {$this->insert}  <br>Datos actualizados/ignorados: {$this->update}";
		// RETORNAR MENSAJE DE CONSULTA
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * PROCESAR VACACIONES SOLICITADAS
	 */
	private function processRequestedHolidays($pathFile,$entity){
		// INSTANCIA XLS
		$myExcel=new ctrl\xlsController();
		// OBTENER DATOS DE EXCEL
		$data=$myExcel->getCellValue($this->post,$pathFile,$entity);
		// VALIDAR LONGITUD DE DATOS
		if(count($data)<1)$this->getJSON("Operación abortada, no se ha encontrado ningun registro...");
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// RECORRER LISTADO
		foreach($data as $v){
			// STRING PARA CONSULTAR REGISTRO DE PERSONAL
			$str=$this->db->selectFromView("vw_basic_personal","WHERE persona_doc_identidad='{$v['persona_doc_identidad']}'");
			// VALIDAR EXISTENCIA DE REGISTRO
			if($this->db->numRows($str)<1)$this->getJSON($this->db->closeTransaction($this->setJSON("No se ha encontrado ningún registro relacionado con el # de identificación <b>{$v['persona_doc_identidad']}</b>, favor revise los datos y vuelva a intentar.")));
			// OBTENER REGISTRO DE PERSONAL
			$staff=$this->db->findOne($str);
			// REGISTRAR ID DE PERSONAL
			$v['fk_personal_id']=$staff['personal_id'];
			// INSERTAR ESTADO 
			$v['vacacion_estado']='VALIDADA';
			$v['fk_usuario']=$staff['personal_id'];
			$v['vacacion_registro']='2019-01-31';
			// STRING PARA CONSULTAR REGISTRO DE VACACIONES
			$str=$this->db->selectFromView($entity,"WHERE fk_personal_id={$v['fk_personal_id']} AND (vacacion_fecha_desde='{$v['vacacion_fecha_desde']}' AND vacacion_fecha_hasta='{$v['vacacion_fecha_hasta']}')");
			// VALIDAR EXISTENCIA DEL REGISTRO
			if($this->db->numRows($str)>0){
				// SUMAR CONTADOR
				$this->update+=1;
			}else{
				// EJECUTAR SENTENCIA DE CONSULTA
				$sql=$this->db->executeTested($this->db->getSQLInsert($v,$entity));
				// SUMAR CONTADOR
				$this->insert+=1;
			}
		}
		// INGRESAR COMO RETORNO LOS DATOS QUE SE HAN INGRESADO
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: {$this->insert}  <br>Datos actualizados/ignorados: {$this->update}";
		// RETORNAR MENSAJE DE CONSULTA
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * CARGAR LISTADO DE EXCEL
	 */
	public function uploadList(){
		// DETERMINAR ENTIDAD
		$params=$this->getURI();
		// SUBIR ARCHIVO
		$config=$this->uploaderMng->config['entityConfig'][$params['tb']];
		// PARAMETROS DE CONSULTA
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA DE ARCHIVO
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// OBTENER ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['vacaciones_name']}";
		// COMPROBAR SI EL ARCHIVO SE HA SUBIDO CORRECTAMENTE
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// LISTA A VALIDAR
		$sql=($params['tb']==$this->entity)?$this->processRequestedHolidays($pathFile,$params['tb']):$this->processGeneratedHolidays($pathFile,$params['tb']);
		// PROCEDER A INGRESAR DATOS
		$this->getJSON($sql);
	}
	
	
	/*
	 * CALCULO DE DIAS
	 */
	private function getDataToBreak($personal,$data){
		// CONTADOR DE DÍAS
		$data['vacacion_semana']=0;
		$data['vacacion_weekend']=0;
		$data['vacacion_horas']=0;
		
		// SETTEAR FORMATO DE FECHAS
		$data['vacacion_fecha_desde']=$this->setFormatDate($data['vacacion_fecha_desde'],'Y-m-d');
		$data['vacacion_fecha_hasta']=$this->setFormatDate($data['vacacion_fecha_hasta'],'Y-m-d');
		
		// RECORRER FECHAS PARA CONTABILIZAR LOS DÍAS
		for($i=strtotime($data['vacacion_fecha_desde']);$i<=strtotime($data['vacacion_fecha_hasta']);$i+=86400){
			// PARSE DE FECHA
			$auxDate=date('w',$i);
			// CONTAR NÚMEROS DE DIAS
			if($auxDate<1 || $auxDate>5) $data['vacacion_weekend']+=1;
			else $data['vacacion_semana']+=1;
		}
		
		// CALCULAR FINES DE SEMANA VALIDOS
		$data['weekend']=$data['vacacion_weekend']-(2*intval(($data['vacacion_semana']+$data['vacacion_weekend'])/7));
		// CALCULAR LOS DIAS VALIDOS
		$data['vacacion_dias']=($data['vacacion_semana']+$data['vacacion_weekend']);
		
		// RETORNAR DATOS RE CONSULTA
		return $data;
	}
	
	/*
	 * VALIDAR FECHA
	 * - PERMISOS DÍAS DE EVENTOS
	 * - PERMISOS EN HORAS LABORABLES
	 * - CALCULAR LAS HORAS Y DÍAS QUE SERÁN UTILIZADAS
	 */
	private function validateDate($personal,$data){
		// FECHA MÍNIMA PARA SOLICITAR PERMISO
		$minDate=$this->addDays($this->getFecha(),$this->varLocal['MIN_DAYS_TO_REQUEST']);
		// FECHA MÁXIMA PARA SOLICITAR PERMISO
		$maxDate=$this->addDays($this->getFecha(),$this->varLocal['MAX_DAYS_TO_REQUEST']);
		
		// SALDO DE VACACIONES DE PERSONAL
		$vacation=$this->db->findOne($this->db->selectFromView('vw_vacaciones',"WHERE fk_personal_id={$personal['personal_id']}"));
		$vacation['saldo_dias_correccion']=intval($vacation['saldo_dias']);
		
		// DATOS PARA VACACIONES
		$data=$this->getDataToBreak($personal,$data);
		
		// VALIDACIÓN DE FECHA DE PERMISO - DIAS
		if(strtotime($data['vacacion_fecha_desde']) < strtotime($minDate) && app\session::getKey()=='session.tthh') $this->getJSON($this->msgReplace($this->PARAMS_TO_REQUEST['MSG']['MIN_DAYS_TO_REQUEST'],$this->varLocal));
		if(strtotime($data['vacacion_fecha_desde']) >= strtotime($maxDate)) $this->getJSON($this->msgReplace($this->PARAMS_TO_REQUEST['MSG']['MAX_DAYS_TO_REQUEST'],$this->varLocal));
		
		// VALIDAR LONGITUD DE FECHAS
		if($data['vacacion_dias']<$this->varLocal['TOTAL_DAYS_MIN_REQUESTED']) $this->getJSON("Para acceder a sus vacaciones anuales deberá solicitar un mínimo de <b>{$this->varLocal['TOTAL_DAYS_MIN_REQUESTED']} días</b>!");
		// VALIDAR SI NO SE ESTAN SOLICITANDO MAS DIAS DE LAS ACUMULADAS
		if($data['vacacion_dias']>$vacation['saldo_dias_correccion']) $this->getJSON("¡No se puede completar esta operación!<br>Este error se debe a que usted está solicitando <b>{$data['vacacion_dias']} días de vacaciones</b> pero al momento solo cuenta con <b>{$vacation['saldo_dias_correccion']} días de vacaciones acumulados</b>");
		
		// DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * INGRESAR NUEVAS SOLICITUDES
	 */
	public function insertEntity(){
		// TIPO DE SESIÓN
		$sessionType=app\session::getKey();
		
		// VALIDAR MÓDULO DE PETICION
		if($sessionType=='session.system'){
			// OBTENER INFORMACION DEL PERSONAL
			$personal=$this->db->viewById($this->post['fk_personal_id'],'personal');
		}else{
			// OBTENER ID DE PERSONAL (SESSION)
			$personalId=app\session::get();
			// OBTENER INFORMACION DEL PERSONAL
			$personal=$this->db->viewById($personalId,'personal');
		}
		
		// VALIDAR EL HORARIO PARA EL PERMISO
		$this->post=$this->validateDate($personal,$this->post);
		
		// EMPEZAR TRANSACCIÓIN
		$this->db->begin();
		// GENERAR SENTENCIA DE INGRESO & EJECUTAR SENTENCIA SQL
		$json=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// INSERTAR DATOS
		$json['data']=$this->db->getLastEntity($this->entity);
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * ACTUALIZAR REGISTROS
	 */
	public function updateEntity(){
		// VALIDAR SI EL PROYECTO SE EJECUTA EN SYSTEM O DEL LADO DE TTHH Y OBTENER ID DE PERSONAL (SESSION)
		// $personalId=$this->getStaffIdBySession();
		$personalId=$this->post['fk_personal_id'];
		
		// OBTENER INFORMACION DEL PERSONAL
		$personal=$this->db->viewById($personalId,'personal');
		
		// OBTENER DATOS DE FORMULARIO Y VALIDAR EL HORARIO PARA EL PERMISO
		if($this->post['vacacion_estado']=='SOLICITUD GENERADA') $this->post=$this->validateDate($personal,$this->post);
		else $this->post=$this->getDataToBreak($personal,$this->post);
		
		// INGRESAR EL PERSONAL QUE REALIZA LA SOLICITUD
		$this->post['fk_usuario']=$this->getStaffIdBySession();
		
		// VERIFICAR EL ESTADO DE TRANSICIÓN
		if($this->post['vacacion_estado']=='SOLICITUD AUTORIZADA'){
			$this->post['vacacion_sumillado']=$this->getFecha('Y-m-d H:i:s');
		}elseif($this->post['vacacion_estado']=='SOLICITUD REGISTRADA'){
			// PERMISO EN BASE DE DATOS PARA APROBAR UNA SOLICITUD VENCIDA
			if(!in_array(802411,$this->getAccesRol())) $this->getJSON("No cuenta con permisos para aprobar los permisos del personal!");
			// REGISTRAR DATOS DE APROBACION
			$this->post['vacacion_aprobado']=$this->getFecha('Y-m-d H:i:s');
			if($this->post['vacacion_serie']==0) $this->post['vacacion_serie']=$this->db->getNextSerie($this->entity);
		}
		
		// EMPEZAR TRANSACCIÓIN
		$this->db->begin();
		// GENERAR SENTENCIA DE INGRESO & EJECUTAR SENTENCIA SQL
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	
	/*
	 * CALCULAR DIAS Y MESES ENTRE DOS FECHAS
	 */
	private function getHolidaysGeneratesFromDates($date,$cierre){
	    // FORMATO DE FECHAS
	    $fechainicial=new \DateTime($date);
	    // FECHA DE CORTE
	    $fechafinal=new \DateTime($cierre);
	    // REGISTRO ACTUAL
	    $today=$this->getFecha('Ymd');
	    
	    // DIFERENCIA DE FECHAS
	    $diferencia=$fechainicial->diff($fechafinal);
	    // CALCULAR MESES
	    $meses=($diferencia->y*12)+$diferencia->m;
	    // VALOR DE DIAS
	    $dias=$diferencia->d;
	    
	    // TRANSFORMAR MESES A DIAS DE VACACIONES
	    $mesesVac=$this->setFormatNumber($meses*2.5,2);
	    // TRANSFORMAR DIAS A VACACIONES DE
	    $diasVac=$this->setFormatNumber(($dias/30)*2.5,2);
	    
	    // RETORNAR MODELO DE VACACIONES
	    return array(
	        'vacacion_periodo_inicio'=>$date,
	        'vacacion_periodo_cierre'=>$cierre,
	        'vacacion_dias'=>$dias,
	        'vacacion_meses'=>$meses,
	        'vacacion_dias_generados'=>$diasVac,
	        'vacacion_meses_generados'=>$mesesVac,
	        'vacacion_detalle'=>"AJUSTE DE VACACIONES DEL PERIODO {$date} A {$cierre} [{$today}]"
	    );
	}
	
	/*
	 * CALCULAR DIAS DE VACACIONES DESDE UNA FECHA
	 */
	private function getDaysByDate($date,$personalId,$remuneration){
		// OBTENER DATOS PARA REGISTRO DE VACACIONES
	    $temp=$this->getHolidaysGeneratesFromDates($date,$this->getFecha('lastEndMonth'));
	    // CALCULO DE REMUNERACION
	    $remuneration=($remuneration/30)*($temp['vacacion_dias_generados']+$temp['vacacion_meses_generados']);
		// RETORNAR MODELO DE VACACIONES
	    return array_merge($temp,array('fk_personal_id'=>$personalId,'vacacion_remuneracion'=>$remuneration));
	}
	
	/*
	 * CALCULAR LAS VACACIONES DEL PERSONAL
	 */
	private function calculateVacations(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// MENSAJE POR DEFECTO
		$json=$this->setJSON("No se han procesado nuevos registros",true);
		
		// LISTADO DE PERSONAL ACTIVO
		$personal=$this->db->findAll($this->db->selectFromView('vw_personal_funciones',"WHERE definicion='TITULAR' AND contrato<>'CONTRATO CIVIL' AND estado='EN FUNCIONES'"));
		// RECORRER PERSONAL PARA CALCULAR LAS VACACIONES
		foreach($personal as $v){
			// STRING PARA BUSCAR REGISTRO DE PERSONAL EN VACACIONES GENERADAS
			$str=$this->db->selectFromView('vacaciones_generadas',"WHERE fk_personal_id={$v['personal_id']} ORDER BY vacacion_periodo_cierre DESC");
			// BUSCAR REGISTRO DE PERSONAL EN VACACIONES GENERADAS
			if($this->db->numRows($str)>0){
				// OBTENER REGISTRO DE VACACIONES 
				$row=$this->db->findOne($str);
				// VALIDAR FECHA DE ULTIMO INGRESO
				if($row['vacacion_periodo_cierre']==$this->getFecha('lastEndMonth')) continue;
				// CALCULAR FECHAS DESDE ULTIMO REGISTRO
				$vacation=$this->getDaysByDate($this->addDays($row['vacacion_periodo_cierre'],1),$v['personal_id'],$v['puesto_remuneracion']);
			}else{
				// CALCULAR MESES DESDE FECHA DE CONTRATO
			    $vacation=$this->getDaysByDate($v['fecha_ingreso'],$v['personal_id'],$v['puesto_remuneracion']);
			}
			// EJECUTAR SENTENCIA
			$json=$this->db->executeTested($this->db->getSQLInsert($vacation,'vacaciones_generadas'));
		}
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * CONSULTAR PERMISO POR CÓDIGO
	 */
	private function requestByCode($code){
		// MENSAJE POR DEFECTO
		$json="Código de solicitud no registrado!";
		// CONSULTAR DISPONIBILIDAD EN BASE DE DATOS
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(vacacion_codigo)=UPPER('$code')");
		// VALIDAR CODIGO DE SOLICITUD
		if($this->db->numRows($str)>0){
			// VALIDAR DATOS
			$row=$this->db->findOne($str);
			// VALIDAR EL ESTADO DE LA CAPACITACIÓN
			if(in_array($row['vacacion_estado'],['SOLICITUD AUTORIZADA'])){
				// VALIDAR PERMISO PARA APROBAR PERMISO
				if(!in_array(80233,$this->getAccesRol())) $this->getJSON("No cuenta con permisos para aprobar los permisos del personal!");
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['vacacion_estado']='SOLICITUD REGISTRADA';
				// MODAL A DESPLEGAR
				$row['modal']='Personal_vacaciones';
				$row['tb']=$this->entity;
				// DATOS DEL PERFIL Y DATOS DEL JEFE INMEDIATO
				$row=array_merge($row,$this->db->viewById($row['fk_personal_id'],'personal'));
				// DATOS DE RETORNO
				return $row;
			} elseif(in_array($row['vacacion_estado'],['SOLICITUD REGISTRADA'])){
				$this->getJSON("La solicitud <b>{$code}</b> ya ha sido aprobada por <b>{$row['usuario']}</b>!");
			} elseif(in_array($row['vacacion_estado'],['SOLICITUD GENERADA'])){
				// VALIDAR PERMISO PARA APROBAR PERMISO
				if(!in_array(802331,$this->getAccesRol())) $this->getJSON("No cuenta con permisos para aprobar las vacaciones del personal sin previa aprobación del jefe inmediato!");
				// EDICIÓN DE REGISTRO PARA MODAL
				$row['vacacion_estado']='SOLICITUD REGISTRADA';
				// MODAL A DESPLEGAR
				$row['modal']='Personal_vacaciones';
				$row['tb']=$this->entity;
				// DATOS DEL PERFIL Y DATOS DEL JEFE INMEDIATO
				$row=array_merge($row,$this->db->viewById($row['fk_personal_id'],'personal'));
				// DATOS DE RETORNO
				return $row;
			} else{
				$this->getJSON("Esta solicitud no procede para aprobación puesto que se encuentra como <b><b>{$row['vacacion_estado']}</b></b> por <b><b>{$row['usuario']}</b></b>.");
			}
		}else{
			// BUSCAR POR CEDULA DE PERSONAL
			$str=$this->db->selectFromView("vw_basic_personal","WHERE UPPER(persona_doc_identidad)=UPPER('{$code}')");
			// VALIDAR CODIGO DE SOLICITUD
			if($this->db->numRows($str)>0){
				// OBTENER REGISTRO DE PERSONAL
				$row=$this->db->findOne($str);
				// MODELO DE PERMISO
				$info=$this->requestByAccount($row['personal_id'],$this->entity);
				// UNIR MODELOS
				$row=array_merge($row,$info['frmParent']);
				// MODAL A DESPLEGAR
				$row['modal']='Personal_vacaciones';
				$row['tb']='personal_vacaciones';
				$row['edit']=false;
				$row['vacacion_estado']='SOLICITUD REGISTRADA';
				// DATOS DE RETORNO
				return $row;
			}
		}
		// RETORNAR CONSULTA POR DEFECTO
		$this->getJSON($json);
	}
	
	
	/*
	 * CONSULTAR REGISTRO DE VACACIONES
	 */
	private function requestById($id){
		// MODELO DE DATOS
		$data=array();
		// CONSULTAR DATOS DE VACACIONES
		$data['data']=$this->db->viewById($id,'vacaciones');
		// PERSONAL RELACIONADO
		$data['personal']=$this->db->viewById($data['data']['fk_personal_id'],'personal');
		// RETORNAR MODELO DE DATOS
		return $data;
	}
	
	
	/*
	 * CONSULTAR REGISTRO DE VACACIONES POR ID PERSONAL
	 */
	private function requestByStaffId($staffId){
		// MODELO DE DATOS
		$data=array();
		// CONSULTAR DATOS DE VACACIONES
		$data['data']=$this->db->findOne($this->db->selectFromView('vw_vacaciones',"WHERE personal_id={$staffId}"));
		// VACACIONES GENERADAS
		$data['vacations']=$this->db->findAll($this->db->selectFromView('vw_vacaciones_generadas',"WHERE fk_personal_id={$staffId} ORDER BY vacacion_periodo_inicio DESC"));
		// PERSONAL RELACIONADO
		$data['personal']=$this->db->findOne($this->db->selectFromView('vw_basic_personal',"WHERE personal_id={$staffId}"));
		// RETORNAR MODELO DE DATOS
		return $data;
	}
	
	
	/*
	 * CONSULTAR PERSONAL
	 */
	public function requestEntity(){
		// VERIFICAR EL RECURSO A CONSUMIR
	    if(isset($this->post['type']) && $this->post['type']=='calculator') $this->calculateVacations();
	    // CONSULTAR POR CÓDIGO
	    elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// CONSULTA POR ID
		elseif(isset($this->post['staffId']) && $this->post['staffId']>0) $json=$this->requestByStaffId($this->post['staffId']);
		// CONSULTAR POR SESSION DE USUARIO
		elseif(isset($this->post['account']) && $this->post['account']=='tthh') $json=$this->requestByAccount(app\session::get(),$this->entity);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * REAJUSTAR VACACIONES
	 */
	public function settingVacation(){
	    // CONSULTA QUE NO EXISTAN PERMISOS PENDIENTES HASTA LA FECHA DE CIERRE
	    $strWhr="WHERE fk_personal_id={$this->post['personal_id']} AND (permiso_fecha_desde<'{$this->post['ajuste_cierre']}' OR permiso_fecha_hasta<'{$this->post['ajuste_cierre']}') AND permiso_estado IN ('SOLICITUD AUTORIZADA','SOLICITUD GENERADA')";
	    $str=$this->db->selectFromView('permisos_solicitados',$strWhr);
	    $numRows=$this->db->numRows($str);
	    if($numRows>0) $this->getJSON("No se puede continuar con esta operación, <b>actualmente existen {$numRows} solicitudes de permisos con cargo a vacaciones</b> que no han sido registrados en Talento Humano. Revise esta información.");
	    
	    // CONSULTA QUE NO EXISTAN VACACIONES PENDIENTES HASTA LA FECHA DE CIERRE
	    $strWhr="WHERE fk_personal_id={$this->post['personal_id']} AND (vacacion_fecha_desde<'{$this->post['ajuste_cierre']}' OR vacacion_fecha_hasta<'{$this->post['ajuste_cierre']}') AND vacacion_estado IN ('SOLICITUD AUTORIZADA','SOLICITUD GENERADA')";
	    $str=$this->db->selectFromView('vacaciones_solicitadas',$strWhr);
	    $numRows=$this->db->numRows($str);
	    if($numRows>0) $this->getJSON("No se puede continuar con esta operación, <b>actualmente existen {$numRows} solicitudes de vacaciones</b> que no han sido registrados en Talento Humano. Revise esta información.");
	    
	    // INICIO DE TRANSACCION
	    $this->db->begin();
	    
	    // CONSULTAR SALDO DE VACACIONES GENERADO POSTERIOR A LA FECHA DE CIERRE INGRESADA
	    $strWhr="WHERE fk_personal_id={$this->post['personal_id']} AND ('{$this->post['ajuste_cierre']}' BETWEEN vacacion_periodo_inicio AND vacacion_periodo_cierre) AND vacacion_estado='ACTIVO'";
	    $str=$this->db->selectFromView('vacaciones_generadas',$strWhr);
	    $numRows=$this->db->numRows($str);
	    if($numRows>0){
	        // OBTENER REGISTRO
	        $row=$this->db->findOne($str);
	        // ACTUALIZAR REGISTRO
	        $temp=$this->getHolidaysGeneratesFromDates($row['vacacion_periodo_inicio'],$this->post['ajuste_cierre']);
	        // REGISTRO DE AJUSTE DE VACACIONES
	        $temp['vacacion_detalle']="{$temp['vacacion_detalle']} // {$this->post['ajuste_detalle']}";
	        // SENTENCIA DE ACTUALIZACION DE REGISTRO
	        $sql=$this->db->executeTested($this->db->getSQLUpdate(array_merge($row,$temp),'vacaciones_generadas'));
	    }
	    
	    $strWhr="WHERE fk_personal_id={$this->post['personal_id']} AND vacacion_periodo_inicio>'{$this->post['ajuste_cierre']}' AND vacacion_estado='ACTIVO'";
	    $str=$this->db->selectFromView('vacaciones_generadas',$strWhr);
	    $numRows=$this->db->numRows($str);
	    if($numRows>0){
	        // SENTENCIA DE ELIMINACIÓN DE REGISTROS
	        $sql=$this->db->executeTested($this->db->getSQLDelete('vacaciones_generadas',$strWhr));
	    }
	    
	    // TODO :: GENERAR SALDO EN CONTRA POR AJUSTE DE VACACIONES
	    
	    
	    // OBTENER DATOS DE PERSONAL
	    $personal=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE personal_id={$this->post['personal_id']}"));
	    // GENERAR VACACIONES NUEVAS A PARTIR DE FECHA INGRESADA HASTA LA FECHA ACTUAL
	    $temp=$this->getHolidaysGeneratesFromDates($this->post['ajuste_inicio'],$this->post['ajuste_cierre_nuevo']);
	    // REGISTRO DE AJUSTE DE VACACIONES
	    $temp['vacacion_detalle']="{$temp['vacacion_detalle']} // {$this->post['ajuste_detalle']}";
	    $temp['fk_personal_id']=$this->post['personal_id'];
	    // CALCULO DE REMUNERACION
	    $temp['vacacion_remuneracion']=($personal['puesto_remuneracion']/30)*($temp['vacacion_dias_generados']+$temp['vacacion_meses_generados']);
	    // REGISTRO DE AJUSTE DE VACACIONES
	    $sql=$this->db->executeTested($this->db->getSQLInsert($temp,'vacaciones_generadas'));
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// STRING PARA CONSULTAR VACACIONES
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE vacacion_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// CARGAR DATOS DE PERMISO
			$pdfCtrl->loadSetting($row);
			// *** CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printById($pdfCtrl,$id,$config){
	    // STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE vacacion_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// PARSE DE FECHAS
			$row['vacacion_solicitud']=$this->setFormatDate($row['vacacion_solicitud'],'complete');
			$row['vacacion_dias']=intval($row['vacacion_dias']);
			$row['vacacion_fecha_desde']=$this->setFormatDate($row['vacacion_fecha_desde'],'complete');
			$row['vacacion_fecha_hasta']=$this->setFormatDate($row['vacacion_fecha_hasta'],'complete');
			
			// VALIDAR SI EL PERSONAL ES OPERATIVO Y SI SE ENCUENTRA EN PELOTON
			$row=$this->signatureResponsible($row);
			
			// CARGAR DATOS DE PERMISO
			$pdfCtrl->loadSetting($row);
			// *** CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro.</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	

}