<?php namespace model\Tthh;
use model as mdl;

class performanceModel extends mdl\personModel {
	
	private $entity='evaluacionesdesempenio';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INFORMACIÓN DE EVALUACIÓN EN CURSO
	 */
	private function getEvaluationActive($date){
		// VALIDAR SI EXISTE UNA EVALUACIÓN HABILITADA
		$str=$this->db->selectFromView($this->entity,"WHERE ('{$date}'::date>evaluacion_desde AND '{$date}'::date<evaluacion_hasta) AND evaluacion_estado='{$this->varGlobal['STATUS_ACTIVE']}'");
		// VALIDAR SI EXISTE EVALUACIÓN
		if($this->db->numRows($str)<1) $this->getJSON("No existe ninguna evaluación habilitada a la fecha [<b>{$date}</b>]");
		// SI EXISTE RETORNAR INFORMACIÓN DE EVALUACIÓN
		return $this->db->findOne($str);
	}
	
	/*
	 * REGISTRAR PREGUNTAS DE EVALUACIÓN
	 */
	private function setItemsByEvaluation($evaluation,$model){
		// DATOS DE EVALUACION DE DESEMMPEÑO
		$evaluationMain=$this->db->findById($evaluation['fk_evaluacion_id'],$this->entity);
		
		// ELIMINAR REGISTROS EXISTENTES - ACTIVIDADES
		$sql=$this->db->executeTested($this->db->getSQLDelete('evaluacion_actividades',"WHERE fk_desempenio_id={$evaluation['desempenio_id']}"));
		// ELIMINAR REGISTROS EXISTENTES - COMPETENCIAS
		$sql=$this->db->executeTested($this->db->getSQLDelete('evaluacion_competencias',"WHERE fk_desempenio_id={$evaluation['desempenio_id']}"));
		
		// VARIABLE PARA NOMBRE DE CLASES
		$class=$this->string2JSON($this->varGlobal['CLASS_FOR_PERFORMANCES']);
		// INDEX PARA OBTENER PORCENTAJE DE CALIFICACIÓN POR CADA CATEGORIA DE PREGUNTAS
		$evaluationIndex=$this->string2JSON($this->varGlobal['EVALUATION_SCORES_INDEX']);
		// INDEX PARA OBTENER PUNTUACION DE PREGUNTAS
		$counterIndex=$this->string2JSON($this->varGlobal['COUNTER_FOR_PERFORMANCES']);
		// INDICE PARA SUMAR PUNTUACION DE PREGUNTAS - EVALUACION DE PERSONAL 
		$scoresIndex=$this->string2JSON($this->varGlobal['PERFORMANCES_SCORES_INDEX']);
		// PONDERACIÓN  
		$scoresIndex=$this->string2JSON($this->varGlobal['PERFORMANCES_SCORES_INDEX']);
		// RECORRER LISTADO DE TIPOS DE PREGUNTAS
		foreach($model as $type=>$list){
			// DECLARAR MODELO DE CALIFICACIONES
			$evaluation[$scoresIndex[$type]]=0;
			// CONTADOR ESPECIAL PARA ACTIVIDADES
			$auxActivities=0;
			// RECORRER PREGUNTAS
			foreach($list as $k=>$v){
				// REGISTRAR EL ID DE EVALUACION DEL PERSONAL
				$v['fk_desempenio_id']=$evaluation['desempenio_id'];
				// VALIDAR EL TIPO DE PREGUNTA & SI APLICA O NO
				if($class[$type]=='evaluacion_actividades'){
					// VALIDAR SI ACTIVIDAD APLICA O NO
					if(($v['actividad_tipo']=='ACTIVIDADES' && $v['actividad_aplica']=='SI') || $v['actividad_tipo']!='ACTIVIDADES'){
						// INCREMENTAR CONTADOR
						$auxActivities+=1;
						// SUMAR PUNTUACION DE PREGUNTAS
						$evaluation[$scoresIndex[$type]]+=$v[$counterIndex[$type]];
					}
				}else{
					// REGISTRAR LA COMPETENCIA COMO DESTREZA
					$v['competencia_destreza']=$v['competencia_descripcion'];
					$v['competencia_comportamiento']=$v[$v['competencia_relevancia']];
					// INCREMENTAR CONTADOR
					$auxActivities+=1;
					// SUMAR PUNTUACION DE PREGUNTAS
					$evaluation[$scoresIndex[$type]]+=$v[$counterIndex[$type]];
				}
				// REGISTRAR ACTYIVIDADES Y COMPETENCIAS
				$sql=$this->db->executeTested($this->db->getSQLInsert($v,$class[$type]));
			}
			// VALIDAR DIVIDENDO
			$auxActivities=($auxActivities<1)?1:$auxActivities;
			// SUMA TOTAL / ITEMS INGRESADOS
			$evaluation[$scoresIndex[$type]]=$this->setFormatNumber(($evaluation[$scoresIndex[$type]]*$evaluationMain[$evaluationIndex[$type]])/($auxActivities*5),2);
		}
		// ACTUALIZAR CALIFICACIÓN DE PREGUNTAS
		$sql=$this->db->executeTested($this->db->getSQLUpdate($evaluation,'evaluaciones_personal'));
		// RETORNAR CONSULTA
		return array_merge($sql,array('data'=>$evaluation));
	}
	
	/*
	 * INGRESO DE PERSONAL AL CBSD
	 */
	public function insertPerformance(){
		// RECIBIR FORMULARIO
		$post=$this->requestPost();
		// VALIDAR SI EXISTE UNA EVALUACIÓN HABILITADA
		$evaluation=array_merge($post['evaluation'],$this->getEvaluationActive($post['evaluation']['evaluacion_fecha']));
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// STRING PARA VALIDAR SI EXISTE UNA EVALUACIÓN PARA EL PERSONAL
		$str=$this->db->selectFromView("evaluaciones_personal","WHERE fk_evaluado_id={$post['personal_id']} AND fk_evaluacion_id={$evaluation['evaluacion_id']}");
		// VERIFICAR SI EXISTE EVALUACIÓN
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE EVALUACIÓN
			$evaluation=array_merge($this->db->findOne($str),$evaluation);
			// ACTUALIZAR DATOS DE EVALUACIÓN 
			$sql=$this->db->executeTested($this->db->getSQLUpdate($evaluation,'evaluaciones_personal'));
		}else{
			// REGISTRAR RELACION CON EVALUACIÓN
			$evaluation['fk_evaluacion_id']=$evaluation['evaluacion_id'];
			// RELACIÓN CON PELOTON + RELACIÓN CON PERSONAL QUE REALIZA LA EVALUACIÓN
			$evaluation['fk_peloton_id']=1;
			$evaluation['fk_evaluador_id']=12;
			// REGISTRAR NUEVA EVALUACIÓN
			$sql=$this->db->executeTested($this->db->getSQLInsert($evaluation,'evaluaciones_personal'));
			// OBTENER ID DE EVALUACIÓN DE PERSONAL
			$evaluation=$this->db->getLastEntity('evaluaciones_personal');
		}
		// REGISTRAR PREGUNTAS DE EVALUACIÓN
		$sql=$this->setItemsByEvaluation($evaluation,$post['model']);
		// NOTIFICAR MEDIANTE EMAIL LA CALIFICACION AL PERSONAL
		
		// CERRAR CONEXIÓN + RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR FUNCIONES DEL PUESTO
	 */
	private function getFunctionsByJob($jobId){
		// FORMULARIO
		$config=$this->getConfig(true,'tthh')['performances'];
		// MODELO DE FORMULARIO
		$data=array();
		// LISTAR ACTIVIDADES
		foreach($this->db->findAll($this->db->getSQLSelect('puestos_actividades',['actividadeslaborales'],"WHERE fk_puesto_id={$jobId} AND actividad_estado='ACTIVO' ORDER BY actividad_descripcion")) as $v){
			// VALIDAR SI EL MODELO HA SIDO CREADO
			if(!isset($data[$v['actividad_tipo']])) $data[$v['actividad_tipo']]=array();
			// MODELO DE PREGUNTA
			$v=array_merge($config['activities'],$v);
			// INGRESAR MODELO
			$data[$v['actividad_tipo']][$v['actividad_id']]=$v;
		}
		// LISTAR COMPETENCIAS
		foreach($this->db->findAll($this->db->getSQLSelect('puestos_competencias',['competenciaslaborales'],"WHERE fk_puesto_id={$jobId} AND competencia_estado='ACTIVO' ORDER BY competencia_descripcion")) as $v){
			// VALIDAR SI EL MODELO HA SIDO CREADO
			if(!isset($data[$v['competencia_tipo']])) $data[$v['competencia_tipo']]=array();
			// MODELO DE PREGUNTA
			$v=array_merge($config['skills'],$v);
			// COMPORTAMIENTO OBSERVABLE
			$v['ALTA']=$v['competencia_relevancia_alta'];
			$v['MEDIA']=$v['competencia_relevancia_media'];
			$v['BAJA']=$v['competencia_relevancia_baja'];
			// INGRESAR MODELO
			$data[$v['competencia_tipo']][$v['competencia_id']]=$v;
		}
		// RETORNAR FUNCIONES
		return $data;
	}

	/*
	 * CONSULTAR POR ID
	 */
	private function requestById($id){
		// MODELO DE DATOS
		$data=$this->db->viewById($id,$this->entity);
		// LISTADO DE PERSONAL RELACIONADOS CON LA EVALUACIÓN
		$data['personal']=$this->db->findAll($this->db->selectFromView('vw_evaluaciones_personal',"WHERE fk_evaluacion_id={$id}"));
		// RETORNAR  CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR POR PERSONAL
	 */
	private function requestByPersonal($code){
		// VALIDAR SI EXISTE UNA EVALUACIÓN HABILITADA
		$evaluation=$this->getEvaluationActive($this->getFecha());
		// STRING PARA VALIDAR SI EXISTE PERSONAL
		$str=$this->db->selectFromView('vw_personal',"WHERE UPPER(persona_doc_identidad)=UPPER('{$code}') AND personal_estado='{$this->varGlobal['STATUS_ACTIVE']}'");
		// CONSULTAR SI EXISTE PERSONAL
		if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado un registro con estos datos <b>{$code}</b>");
		// MODELO DE CONSULTA
		$data=array();
		// RECIBIR FORMULARIO
		$personal=$this->db->findOne($str);
		// VARIABLE PARA CONSULTA
		$data['personalId']=$personal['personal_id'];
		// ENVIAR DATOS DE PERSONAL
		$data['staff']=$personal;
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR POR PERSONAL ID
	 */
	private function requestByPersonalId($personalId){
		// VALIDAR SI EXISTE UNA EVALUACIÓN HABILITADA
		$evaluation=$this->getEvaluationActive($this->getFecha());
		// RECIBIR FORMULARIO
		$personal=$this->db->viewById($personalId,'personal');
		// MODELO DE CONSULTA [INFORMACIÓN DE PERSONAL + INFORMACIÓN DE EVALUACIÓN]
		$data=$personal;
		$data['evaluation']=$evaluation;
		// MODELO - FORMATO DE PREGUNTAS
		$data['model']=$this->getFunctionsByJob($data['puesto_id']);
		// STRING PARA VALIDAR SI YA EXISTE UNA EVALUACIÓN PARA EL PERSONAL
		$str=$this->db->selectFromView("evaluaciones_personal","WHERE fk_evaluado_id={$personalId} AND fk_evaluacion_id={$evaluation['evaluacion_id']}");
		// VERIFICAR SI EXISTE EVALUACIÓN
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE EVALUACIÓN
			$row=$this->db->findOne($str);
			// FECHA DE EVALUACIÓN
			$data['evaluation']=array_merge($data['evaluation'],$row);
			// OBTENER INFORMACIÓN DE PREGUNTAS ALMACENADAS
			foreach($this->db->findAll($this->db->getSQLSelect('evaluacion_actividades',['actividadeslaborales'],"WHERE fk_desempenio_id={$row['desempenio_id']}")) as $k=>$v){
				// VALIDAR SI EL MODELO HA SIDO CREADO
				if(!isset($data['model'][$v['actividad_tipo']])) $data['model'][$v['actividad_tipo']]=array();
				// INGRESAR MODELO
				$data['model'][$v['actividad_tipo']][$v['fk_actividad_id']]=array_merge($data['model'][$v['actividad_tipo']][$v['fk_actividad_id']],$v);
			}
			// ELIMINAR REGISTROS EXISTENTES - COMPETENCIAS
			foreach($this->db->findAll($this->db->getSQLSelect('evaluacion_competencias',['competenciaslaborales'],"WHERE fk_desempenio_id={$row['desempenio_id']}")) as $k=>$v){
				// VALIDAR SI EL MODELO HA SIDO CREADO
				if(!isset($data['model'][$v['competencia_tipo']])) $data['model'][$v['competencia_tipo']]=array();
				// INGRESAR MODELO
				$data['model'][$v['competencia_tipo']][$v['fk_competencia_id']]=array_merge($data['model'][$v['competencia_tipo']][$v['fk_competencia_id']],$v);
			}
		}else{
			// POR DEFECTO FECHA DE EVALUACIÓN - FECHA ACTUAL
			$data['evaluation']['evaluacion_fecha']=$this->getFecha();
			$data['evaluation']['fk_evaluado_id']=$personalId;
		}
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR EVALUACIONES DE DESEMPEÑO
	 */
	public function requestPerformance(){
		// RECIBIR FORMULARIO
		$post=$this->requestPost();
		// CONSULTAR POR ID
		if(isset($post['id'])) $json=$this->requestById($post['id']);
		// BUSCAR POR DOC IDENTIDAD
		elseif(isset($post['code'])) $json=$this->requestByPersonal($post['code']);
		// BUSCAR POR DOC IDENTIDAD
		elseif(isset($post['personalId'])) $json=$this->requestByPersonalId($post['personalId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * INTERPRETACIÓN DE CALIFICACIÓN
	 */
	private function getInterpretacion($evaluacion_calificacion){
		// VALIDAR RANGOS DE CAIFICACIÓN
			if($evaluacion_calificacion>=90.5 && $evaluacion_calificacion<=104) $msg="EXCELENTE: DESEMPEÑO ALTO";
		elseif($evaluacion_calificacion>=80.5 && $evaluacion_calificacion<=90.4) $msg="MUY BUENO: DESEMPEÑO MEJOR A LO ESPERADO";
		elseif($evaluacion_calificacion>=70.5 && $evaluacion_calificacion<=80.4) $msg="SATISFACTORIO: DESEMPEÑO ESPERADO";
		elseif($evaluacion_calificacion>=60.5 && $evaluacion_calificacion<=70.4) $msg="REGULAR: DESEMPEÑO BAJO LO ESPERADO";
		elseif($evaluacion_calificacion>0 && $evaluacion_calificacion<=60.4) $msg="INSUFICIENTE: DESEMPEÑO MUY BAJO A LO ESPERADO";
		else $msg="PROCESO INCORRECTO";
		// RETORNAR VALIDACIÓN
		return $msg;
	}
	
	/*
	 * IMPRIMIR DETALLE DE PREGUNTAS
	 */
	private function getReportInformation($evaluationId){
		// INDEX PARA OBTENER TABLA DE TIPO DE PREGUNTAS
		$tbsIndex=$this->string2JSON($this->varGlobal['TABLES_FOR_PERFORMANCES']);
		// INDEX PARA OBTENER CELDAS DE PREGUNTAS
		$rowIndex=$this->string2JSON($this->varGlobal['ROWS_FOR_PERFORMANCES']);
		// TEMPLATE
		$html="";
		// MOMDELO DE REPORTE
		$tables=array();
		// CELDAS
		$rows=array();
		// OBTENER INFORMACIÓN DE PREGUNTAS ALMACENADAS
		foreach($this->db->findAll($this->db->getSQLSelect('evaluacion_actividades',['actividadeslaborales'],"WHERE fk_desempenio_id={$evaluationId} ORDER BY actividadeslaborales.actividad_tipo, actividadeslaborales.actividad_descripcion")) as $k=>$v){
			// VALIDAR SI EL MODELO HA SIDO CREADO
			if(!isset($tables[$v['actividad_tipo']])){
				$tables[$v['actividad_tipo']]=$this->varGlobal[$tbsIndex[$v['actividad_tipo']]];
				$rows[$v['actividad_tipo']]="";
			}
			// GENERAR NUEVA CELDA
			$rowAux=$this->varGlobal[$rowIndex[$v['actividad_tipo']]];
			// INSERATR DATOS DE CELDAS
			foreach($v as $key=>$val){
				if(($v['actividad_tipo']=='ACTIVIDADES' && $v['actividad_aplica']=='NO') && in_array($key,['actividad_meta','actividad_cumplimiento','actividad_nivel'])) $rowAux=preg_replace('/{{'.$key.'}}/',"-",$rowAux);
				else $rowAux=preg_replace('/{{'.$key.'}}/',$val,$rowAux);
			}
			// CONCATENAR CELDAS
			$rows[$v['actividad_tipo']].=$rowAux;
		}
		// ELIMINAR REGISTROS EXISTENTES - COMPETENCIAS
		foreach($this->db->findAll($this->db->getSQLSelect('evaluacion_competencias',['competenciaslaborales'],"WHERE fk_desempenio_id={$evaluationId} ORDER BY competenciaslaborales.competencia_tipo, competencia_destreza")) as $k=>$v){
			// VALIDAR SI EL MODELO HA SIDO CREADO
			if(!isset($tables[$v['competencia_tipo']])){
				$tables[$v['competencia_tipo']]=$this->varGlobal[$tbsIndex[$v['competencia_tipo']]];
				$rows[$v['competencia_tipo']]="";
			}
			// GENERAR NUEVA CELDA
			$rowAux=$this->varGlobal[$rowIndex[$v['competencia_tipo']]];
			// INSERATR DATOS DE CELDAS
			foreach($v as $key=>$val){$rowAux=preg_replace('/{{'.$key.'}}/',$val,$rowAux);}
			// CONCATENAR CELDAS
			$rows[$v['competencia_tipo']].=$rowAux;
		}
		// INSERTAR CELDAS EN TABLAS
		foreach($tables as $k=>$v){$html.=preg_replace('/{{tbBody}}/',$rows[$k],$tables[$k]);}
		// RETORNAR MODELO
		return $html;
	}
	
	/*
	 * REPORTES
	 */
	public function printByPersonalId($pdfCtrl,$id,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView('vw_evaluaciones_personal',"WHERE desempenio_id={$id}");
		// VALIDAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS
			$row=$this->db->findOne($str);
			// PARSE FECHAS
			$row['evaluacion_fecha']=$this->setFormatDate($row['evaluacion_fecha'],'complete');
			// IMPRIMIR CUERPO DE PREGUNTAS
			$row['tbodyEvaluation']=$this->getReportInformation($id);
			// EVALUACIÓN CUALITATIVA
			$row['evaluacion_cualitativa']=$this->getInterpretacion($row['evaluacion_calificacion']);
			// CARGAR DATOS PARA REPORTE
			$pdfCtrl->loadSetting($row);
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}

}