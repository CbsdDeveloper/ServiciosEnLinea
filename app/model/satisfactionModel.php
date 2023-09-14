<?php namespace model;
use api as app;

class satisfactionModel extends app\controller {

	/*
	 * VARIABLES LOCALES
	 */
	private $entity='evaluacionsatisfaccion';
	private $config;
	
	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
		// LEER VARIABLES DE MODELO
		$this->config=$this->getConfig(true,'evaluation');
	}
	
	/*
	 * VALIDAR SERVICIO EVALUADO
	 */
	private function validateServices($entity,$entityId){
		// VALIDAR EL TIPO DE SERVICIO EVALUADO
		if(in_array($entity,['stands','visitas','simulacros'])){
			// PREFIJO DE ENTIDAD
			$tb=$this->crudConfig['entityPrefix'][$entity];
			// MODELO AUXILIAR
			$aux=array(
				"{$tb}_estado"=>'EVALUADA',
				"{$tb}_id"=>$entityId
			);
			// ACTUALIZAR ESTADO DE ENTIDAD Y RETORNAR CONSULTA
			return $this->db->executeTested($this->db->getSQLUpdate($aux,$entity));
		}
		// RETORNAR POR DEFECTO
		return $this->setJSON("Ok",true);
	}
	
	/*
	 * INGRESAR EVALUACION DE SATISFACCION
	 */
	public function insertSatisfactionEvaluation(){
		// OBTENER DATOS DE FORMULARIO
		$post=$this->requestPost();
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR EVALUACION
		$model=array(
			'fk_entidad'=>$post['entity'],
			'fk_entidad_id'=>$post['entityId'],
			'evaluacion_sugerencias'=>$post['evaluacion_sugerencias']
		);
		// INGRESAR PREGUNTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
		// OBTENER ID DE EVALUACION
		$evaluationId=$this->db->getLastID($this->entity);
		// RECORRER PREGUNTAS
		foreach($post['questions'] as $k=>$v){
			// MODELO AUXILIAR
			$aux=array(
				'fk_evaluacion_id'=>$evaluationId,
				'fk_pregunta_id'=>$k,
				'calificacion'=>$v
			);
			// INGRESAR PREGUNTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($aux,'evaluacion_has_questions'));
		}
		// VALIDAR SERVICIO 
		$sql=$this->validateServices($post['entity'],$post['entityId']);
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView($this->entity,"WHERE evaluacion_id=$id");
		// VALIDAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS
			$row=$this->db->findOne($str);
			// OBTENER INFORMACION REAL DE ENTIDAD EVALUADA
			$entity=$this->db->viewById($row['fk_entidad_id'],$row['fk_entidad']);
			
			// CONSULTAR SI LA ENTIDAD HA SIDO DECLARADA
			if(isset($this->config['entities'][$row['fk_entidad']])){
				// LEER PARAMETROS
				$tbConfig=$this->config['entities'][$row['fk_entidad']];
				$entity['solicitud']=$tbConfig['solicitud'];
				// GENERAR VARIABLES DESDE CONFIGURACION
				foreach($tbConfig as $k=>$v){
					if(isset($entity[$v])) $entity[$k]=$entity[$v];
				}
				// GENERAR FECHAS
				if(isset($this->config['direccion'][$row['fk_entidad']])){
					$entity['direccion']="";
					foreach($this->config['direccion'][$row['fk_entidad']] as $index=>$v){
						$entity['direccion'].="{$entity[$v]} ".($index>0?", ":"");
					}
				}else $entity['direccion']="CUERPO DE BOMBEROS";
				
				// ESPACIO PARA PERSONAL ASIGNADO AL PROCESO
				$row['tbPersonal']="";
				// LISTA DE PERSONAL A CARGO
				foreach($this->db->findAll($this->db->selectFromView("vw_capacitadores","WHERE fk_entidad='{$row['fk_entidad']}' AND fk_entidad_id={$row['fk_entidad_id']}")) as $index=>$val){
					$val['index']=$index+1;
					$auxPersonal=file_get_contents(REPORTS_PATH."evaluacionCapacitador.html");
					foreach($val as $k=>$v){
						$auxPersonal=preg_replace('/{{'.$k.'}}/',$v,$auxPersonal);
					}	$row['tbPersonal'].=$auxPersonal;
				}
			}
			
			
			
			// CARAGAR DATOS DE ENTIDAD
			$entity['score']=$this->setFormatNumber($entity['score']);
			$pdfCtrl->loadSetting($entity);
			
			// DATOS DE EVALUACIÓN
			$row['tbScore']="";
			$index=1;
			foreach($this->getConfig(true,'prevention')['evaluationParams'] as $type){
				$row['tbScore'].="<tr class=subheader><td colspan=3 class=text-center>$type</td></tr>";
				// FORMAT DATOS EVALUACIÓN
				foreach($this->db->findAll($this->db->getSQLSelect('evaluacion_has_questions',['questions'],"WHERE fk_evaluacion_id={$id} AND pregunta_tipo='$type'")) as $val){
					// LISTADO DE PERSONAL EN CAPACITACION
					$auxDetail=file_get_contents(REPORTS_PATH."detailQuestionsTrainer.html");
					$val['index']=$index;
					foreach($val as $k=>$v){
						$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);
					}	$row['tbScore'].=$auxDetail;
					$index++;
				}
			}
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}