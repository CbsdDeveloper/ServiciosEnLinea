<?php namespace model\Tthh;
use api as app;
use controller as ctrl;

class actionModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABLES LOCALES
	 */
	private $entity='accionespersonal';
	private $varLocal;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PARENT
		parent::__construct();
		// VARIABLES LOCALES
		$this->varLocal=$this->getConfParams('tthh');
	}
	
	/*
	 * PROCESAR DATOS SUBIDOS
	 */
	private function processList($pathFile){
		// INSTANCIA XLS
		$myExcel=new ctrl\xlsController();
		// OBTENER DATOS DE EXCEL
		$data=$myExcel->getCellValue($this->post,$pathFile,$this->entity);
		
		
		$this->getJSON($data);
		
		
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("No se han procecsado nuevos registros");
		// LISTAR ESTACIONES
		$stations=array();
		foreach($this->db->findAll($this->db->getSQLSelect('estaciones')) as $val){$stations[strtoupper($val['estacion_nombre'])]=$val['estacion_id'];}
		// LISTAR PUESTOS
		$departures=array();
		foreach($this->db->findAll($this->db->getSQLSelect('puestos')) as $val){$departures[strtoupper($val['puesto_nombre'])]=$val['puesto_id'];}
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		// RECORRER INFORMACIÓN
		foreach($data as $val){
			// VALIDAR QUE LAS ESTACIONES Y LOS PUESTOS (PARTIDAS) ESTÉN CREADAS
			if(isset($stations[strtoupper($val['estacion_nombre'])]) && isset($departures[strtoupper($val['puesto_nombre'])])){
				// CONTROL DE DATOS DE PERSONAS
				$person=$this->requestPerson($val);
				// INGRESAR ID DE REFERENCIA A PERSONA
				$val['fk_persona_id']=$person['persona_id'];
				$val['fk_estacion_id']=$stations[$val['estacion_nombre']];
				$val['fk_puesto_id']=$departures[$val['puesto_nombre']];
				// CONSULTAR SI EXISTE REGISTRO CON ID DE PERSONA
				$str=$this->db->selectFromView($this->entity,"WHERE fk_persona_id={$person['persona_id']}");
				// VALIDAR TIPOS DE CONTRATO
				if(!in_array($val['personal_contrato'],$this->string2JSON($this->varGlobal['contractType']))){
					// MENSAJE DE VALIDACIÓN
					return $this->db->closeTransaction($this->setJSON("El tipo de contrato asignado a <b>{$val['persona_apellidos']}</b> <<<b>{$val['personal_contrato']}</b>>> no corresponde a los permitidos!"));
				}
				// VALIDAR TIPO DE CONTRATO Y ESTADO DE PERSONAL
				if($val['personal_estado']=='PASIVO' || $val['personal_contrato']=='CONTRATO OCASIONAL'){
					// VALIDAR QUE LA FECHA DE SALIDA SEA INGRESADA
					if(strlen($val['personal_fecha_salida'])<10) return $this->db->closeTransaction($this->setJSON("No se ha ingresado la fecha de salida para <b>{$val['puesto_nombre']}</b>"));
				}
				// VALIDAR SI EXISTE EL REGISTRO - INGRESAR O ACTUALIZAR
				if($this->db->numRows($str)>0){
					// OBTENER INFORMACIÓN DE REGISTRO
					$row=$this->db->findOne($str);
					// GENERAR CONTRASEÑA DE ACCESO
					if(in_array($val['personal_contrasenia'],array('',null))) $val['personal_contrasenia']=md5($val['persona_doc_identidad']);
					// ACTUALIZAR REGISTRO Y GENERAR STRING DE ACTUALIZACIÓN
					$str2=$this->db->getSQLUpdate(array_merge($row,$val),$this->entity);
					// INCREMENTAR CONTADOR DE REGISTROS ACTUALIZADOS
					$this->update+=1;
				}else{
					// GENERAR CONTRASEÑA DE ACCESO
					$val['personal_contrasenia']=md5($val['persona_doc_identidad']);
					// GENERAR STRING DE INGRESO DE REGISTRO
					$str2=$this->db->getSQLInsert($val,$this->entity);
					// INCREMENTAR CONTADOR DE REGISTROS INGRESADOS
					$this->insert+=1;
				}
				// EJECUTAR SENTENCIA
				$sql=$this->db->executeSingle($str2);
				// VERIFICAR ESTADO DE CONSULTA
				if(!$sql['estado']){
					// PRESENTAR DONDE SE PROVOCÓ EL ERROR
					$sql['mensaje'].="<br>Error provocado en registro con código -> {$person['persona_doc_identidad']}";
					// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
					return $this->db->closeTransaction($sql);
				}
			}else{
				// PRESENTAR MENSAJE QUE NO SE HA INGRESADO UNA ESTACIÓN O PARTIDA
				return $this->db->closeTransaction($this->setJSON("No existe la partida <b>{$val['puesto_nombre']}</b> ó estación <b>{$val['estacion_nombre']}</b> para el usuario <b>{$val['persona_apellidos']} {$val['persona_nombres']}</b>"));
			}
		}
		// INGRESAR COMO RETORNO LOS DATOS QUE SE HAN INGRESADO
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: {$this->insert}  <br>Datos actualizados: {$this->update}";
		// RETORNAR MENSAJE DE CONSULTA
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * CARGAR LISTADO DE EXCEL
	 */
	public function uploadList(){
		// SUBIR ARCHIVO
		$config=$this->uploaderMng->config['entityConfig'][$this->entity];
		// PARAMETROS DE CONSULTA
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA DE ARCHIVO
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// OBTENER ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['personal_name']}";
		// COMPROBAR SI EL ARCHIVO SE HA SUBIDO CORRECTAMENTE
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// PROCEDER A INGRESAR DATOS
		$this->getJSON($this->processList($pathFile));
	}
	
	
	/*
	 * REGISTRAR BASE LEGAL DE ACCIÓN
	 */
	private function setLegalBase($actionId,$list){
		// VALIDAR LONGITUD DE BASE LEGAL
		if(sizeof($list)<1 || empty($list)) return $this->setJSON("Transacción abortada, es necesario registrar la base legal para la presente acción!");
		// ELIMINAR REGISTROS EXISTENTES
		$sql=$this->db->executeTested($this->db->getSQLDelete('baselegal',"WHERE fk_accion_id={$actionId}"));
		// RECORRER LISTADO DE BASE LEGAL
		foreach($list as $baseId){
			// GENERAR MODELO DE REGISTRO
			$model=array(
				'fk_accion_id'=>$actionId,
				'fk_reglamento_id'=>$baseId
			);
			// REGISTRAR INGRESO DE BASE LEGAL
			$sql=$this->db->executeTested($this->db->getSQLInsert($model,'baselegal'));
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	
	/*
	 * DETALLE DE REGISTROS
	 */
	private function getFormInformation(){
		// MODELO DE CONSULTA
		$data=array();
		// REGISTRO DE DIRECCIONES Y UNIDADES
		$data['leadership']=$this->db->findAll($this->db->selectFromView('vw_direcciones',"ORDER BY grado, puesto"));
		
		// INSERTAR UNIDADES
		$data['leadership']=array_merge($data['leadership'],$this->db->findAll($this->db->selectFromView("vw_personal_puestos","WHERE fk_puesto_id IN (44,50) AND ppersonal_estado='EN FUNCIONES'")));
		
		// RETORNAR MODELO DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR PERMISO POR CÓDIGO
	 */
	private function requestByCode($code){
		// DECODIFICAR CODIGO
		$params=explode('+',$code);
		
		// GENERAR STRING PARA CONSUTAR DIRECCIÓN - PRIMER PARÁMETRO
		$str=$this->db->selectFromView('tipoacciones',"WHERE UPPER(tipoaccion_codigo)=UPPER('{$params[0]}')");
		// VERIFICAR EL REGISTRO DE BASE LEGAL
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado el registro <b>{$params[0]}</b>.");
		// OBTENER INFORMACIÓN DE DIRECCIÓN
		$action=$this->db->findOne($str);
		// OBTENER INFORMACIÓN DE PERSONA
		$str=$this->db->selectFromView("vw_personal_funciones","WHERE UPPER(persona_doc_identidad)=UPPER('{$params[1]}')");
		// VERIFICAR REGISTRO DE CÉDULA
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado el registro <b>{$params[1]}</b>.");
		// DATOS DE PERSONA
		$model=$this->db->findOne($str);
		// VALIDAR EL ESTADO DE PERSONAL - COMENTAR PARA REGISTROS
		if($model['ppersonal_estado']=='PASIVO') $this->getJSON("El registro relacionado a <b></b> se encuentra en modo pasivo.");
		// OBTENER REGISTRO DE RELACION CON HISTORIAL DE CARGOS - COMENTAR PARA REGISTROS
		$str=$this->db->selectFromView('personal_puestos',"WHERE fk_personal_id={$model['personal_id']} AND ppersonal_estado='EN FUNCIONES'");
		// VALIDAR SI EXISTE EL CARGO
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado ningún registro relacionado con <b>{$params[1]}</b> en el historial de cargos");
		// INFORMACIÓN DE CARGO
		$cargo=$this->db->findOne($str);
		
		// OBTENER DATOS DE DIRECTOR DE TALENTO HUMANO
		$model['tthh']=$this->db->viewById(6,'direcciones');
		$model['fk_tthh_id']=$model['tthh']['personal_id'];
		// PARAMETRIZACION
		$model['accion_explicacion']=$action['tipoaccion_descripcion'];
		// INSERTAR FK DE RELACION
		$model['accion_serie']=0;
		$model['typeaction']=$action;
		$model['fk_tipoaccion_id']=$action['tipoaccion_id'];
		$model['situacion_actual']=$cargo['ppersonal_id'];
		// MODAL A DESPLEGAR
		$model['modal']='Accionespersonal';
		$model['tb']=$this->entity;
		
		// DATOS DE RETORNO
		return $model;
	}
	
	/*
	 * CONSULTA DE REGISTROS
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR CÓDIGO DE ACCIÓN O CÉDULA DE PERSONAL
		elseif(isset($this->post['code'])) $json=$this->requestByCode($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * INGRESAR NUEVOS REGISTROS
	 */
	public function insertEntity(){
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON("Empty");
	}
	
	/*
	 * MODIFICACIÓN DE REGISTROS
	 */
	public function updateEntity(){
		
		// INICIAR TRANSACCIÓN
		$this->db->begin(); 
		
		// VALIDAR SITUACION ACTUAL
		if(isset($this->post['sa_id'])){
			// INSERTAR A FORMULARIO
			$this->post['situacion_actual']=$this->post['sa_id'];
		}else{
			// REGISTRO DE PUESTO
			$staff=$this->db->findOne($this->db->selectFromView('personal_puestos',"WHERE fk_personal_id={$this->post['personal_id']} ORDER BY ppersonal_id DESC"));
			// ID DE SITUACIÓN ACTUAL
			$this->post['situacion_actual']=$staff['ppersonal_id'];
		}
		
		// GENERAR MODELO DE ACCIÓN  DEPERSONAL
		$action=$this->post;
		
		// VALIDAR SITUACIÓN PROPUESTA 
		if($this->post['situacion_propuesta_opcion']=='NUEVA'){
			// MODELO DE NUEVO PUESTO
			$model=array(
				'fk_personal_id'=>$this->post['personal_id'],
				'fk_puesto_id'=>$this->post['fk_puesto_id'],
				'personal_definicion'=>$this->post['personal_definicion'],
				'personal_contrato'=>$this->post['personal_contrato'],
				'personal_fecha_ingreso'=>$this->post['personal_fecha_ingreso'],
				'personal_fecha_salida'=>$this->post['personal_fecha_salida'],
				'personal_fecha_registro'=>$this->post['personal_fecha_registro'],
				'personal_motivo_salida'=>$this->post['personal_motivo_salida'],
				'personal_baselegal'=>$this->post['personal_baselegal'],
				'personal_regimen_laboral'=>$this->post['personal_regimen_laboral']
			);
			// STRING PARA COMPROBAR EXISTENCIA DE PUESTO
			$str=$this->db->selectFromView('personal_puestos',"WHERE fk_personal_id={$model['fk_personal_id']} AND fk_puesto_id={$model['fk_puesto_id']}");
			// VALIDAR LA EXISTENCIA DEL PUESTO
			if($this->db->numRows($str)>0){
				// OBTENER DATOS DE REGISTRO
				$model=array_merge($this->db->findOne($str),$model);
				// ACTUALIZACIÓN DE REGISTRO
				$sql=$this->db->executeTested($this->db->getSQLUpdate($model,'personal_puestos'));
			}else{
				// INGRESO DE REGISTRO
				$sql=$this->db->executeTested($this->db->getSQLInsert($model,'personal_puestos'));
			}
			// REGISTRO DE PUESTO
			$propuesta=$this->db->findOne($this->db->selectFromView('personal_puestos',"WHERE fk_personal_id={$model['fk_personal_id']} AND fk_puesto_id={$model['fk_puesto_id']} AND ppersonal_estado='EN FUNCIONES'"));
			// ID DE NUEVO REGISTRO
			$action['situacion_propuesta']=$propuesta['ppersonal_id'];
		}
		
		// VALIDAR SI LA ACCION EXISTE
		if(isset($action['accion_id'])){
			// INGRESAR ACCION DE PERSONAL
			$sql=$this->db->executeTested($this->db->getSQLUpdate($action,$this->entity));
		}else{
			// VALIDAR CAMPOS DE DIRECTOR DE TALENTO HUMANO
			if($action['fk_tthh_id']>0){
				// REGISTRO DE PUESTO
				$tthh=$this->db->findOne($this->db->selectFromView('personal_puestos',"WHERE fk_personal_id={$this->post['fk_tthh_id']} AND ppersonal_estado='EN FUNCIONES' ORDER BY ppersonal_id DESC"));
				// OBTENER ID DE REGISTRO
				$action['fk_tthh_id']=$tthh['ppersonal_id'];
			}
			// GENERAR SERIE DE ACCION
			$action['accion_serie']=$this->db->getNextSerie($this->entity);
			// INGRESAR ACCION DE PERSONAL
			$sql=$this->db->executeTested($this->db->getSQLInsert($action,$this->entity));
			// OBTENER ID DE NUEVA ACCIÓN
			$action['accion_id']=$this->db->getLastID($this->entity);
		}
		
		// INGRESAR BASE LEGAL Y VALIDAR RETORNO DE CONSULTA
		$sql=$this->setLegalBase($action['accion_id'],$this->post['selected']);
		
		// INGRESAR ID DE REGISTRO PARA IMPRESIÓN
		$sql['data']=$action;
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// EXTRAER ID DE REGISTRO
		$id=$this->get['id'];
		// STRING DE CONSULTA DEL REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE accion_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// PARSE DE FECHAS
			$row['accion_serie']=str_pad($row['accion_serie'],4,"0",STR_PAD_LEFT);
			$row['accion_fecha']=$this->setFormatDate($row['accion_fecha'],'complete');
			$row['accion_segun_fecha']=$this->setFormatDate($row['accion_segun_fecha'],'complete');
			$row['accion_rige_desde']=$this->setFormatDate($row['accion_rige_desde'],'complete');
			// PARSE TEXTAREA
			$row['accion_referencia']=nl2br($row['accion_referencia']);
			
			// INFORMACION
			$row['accionBaselegal']="";
			foreach($this->db->findAll($this->db->getSQLSelect('baselegal',['reglamentos'],"WHERE fk_accion_id={$row['accion_id']}")) as $v){
				$row['accionBaselegal'].="- {$v['reglamento_articulo']}. {$v['reglamento_descripcion']} / <i>{$v['reglamento_clasificacion']}</i><br>";
			}
			
			// INGRESO DE DATOS
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['accion_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
}