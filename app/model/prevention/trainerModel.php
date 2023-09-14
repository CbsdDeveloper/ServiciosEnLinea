<?php namespace model\prevention;
use api as app;

class trainerModel extends app\controller {
	
	/*
	 * VARIABLES GLOBALES
	 */
	private $entity='capacitadores';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR
		parent::__construct();
	}
	
	/*
	 * ASIGNAR/ELIMINAR PARTICIPANTES A UNA CAPACITACIÓN
	 */
	public function insertTrainers(){
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("No se ha seleccionado el personal para la asignación.");
		// VALIDAR QUE SE HAYAN ENVIADO+
		if(isset($this->post['selected']) && is_array($this->post['selected']) && count($this->post['selected'])>0){
			// INICIAR TRANSACCION
			$this->db->begin();
			// GENERAR STRING PARA ELIMINAR REGISTRO DE CAPACITADORES YA ASIGNADOS
			$sql=$this->db->executeTested($this->db->getSQLDelete($this->entity,"WHERE fk_entidad='{$this->post['entity']}' AND fk_entidad_id={$this->post['entityId']}"));
			// MODELO DE CAPACITADORES
			$modelPersonal=$this->post['model'];
			// RECORRER LOS DATOS
			foreach($this->post['selected'] as $trainerId){
				// VALIDAR MODELO DE ENVÍO
				if(!isset($modelPersonal[$trainerId]) || empty($modelPersonal[$trainerId]) || $modelPersonal[$trainerId]==null) $this->getJSON($this->db->closeTransaction($this->setJSON("Error al enviar el modelo de datos [entity={$this->post['entity']}]")));
				// DEFINIR MODELO DE INGRESO DE CAPACITADORES
				$model=$modelPersonal[$trainerId];
				// REGISTRO DE FUNCIONES
				$model['fk_personal_id']=$trainerId;
				$model['fk_entidad']=$this->post['entity'];
				$model['fk_entidad_id']=$this->post['entityId'];
				// GENERAR STRING DE INGRESO Y EJECUTAR SENTENCIA
				$sql=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
			}
			// CERRAR TRANSACCION
			$sql=$this->db->closeTransaction($sql);
		}
		// RETORNAR DATOS
		$this->getJSON($sql);
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID DE CAPACITACION
	 */
	private function requestByEntity($entity,$entityId){
		// MODELO DE DATOS
		$data=array(
			'activities'=>$this->string2JSON($this->varGlobal['TRAINERS_FUNCTIONS_LIST'])[$entity],
			'entity'=>$entity,
			'entityId'=>$entityId,
			'model'=>array(),
			'selected'=>array(),
			'list'=>array()
		);
		// LISTAR PERSONAL POR PERFIL
		$data['list']=$this->db->findAll($this->db->selectFromView("vw_trainers","WHERE trainer_estado='{$this->varGlobal['STATUS_ACTIVE']}' ORDER BY personal_nombre"));
		// LISTADO DE CAPACITADORES REGISTRADOS
		foreach($this->db->findAll($this->db->selectFromView("{$this->entity}","WHERE fk_entidad='{$entity}' AND fk_entidad_id={$entityId}")) as $val){
			// LISTAR PERSONAL POR PERFIL
			$data['selected'][]=$val['fk_personal_id'];
			$data['model'][$val['fk_personal_id']]=$val;
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTRO DE PERSONAL PARA ASIGNAR COMO CAPACITADOR
	 */
	private function requestByPersonal($code){
		// CONSULTAR SI EXISTE REGISTRO
		$str=$this->db->selectFromView('vw_relations_personal',"WHERE UPPER(persona_doc_identidad)=UPPER('{$code}') AND ppersonal_estado='EN FUNCIONES'");
		// CONSULTA SI EXISTE EL PERSONAL DESEADO
		if($this->db->numRows($str)<1) $this->getJSON("No existe ningun dato relacionado con su informacion ingresada.");
		// OBTENER DATOS DE PERSONAL
		$row=$this->db->findOne($str);
		// VALIDAR SI EL PERSONAL YA HA SIDO REGISTRADO COMO TRAINER
		if($this->db->numRows($this->db->selectFromView('trainers',"WHERE fk_personal_id={$row['personal_id']}"))>0) $this->getJSON("Acualmente <b>{$row['personal_nombre']}</b> ya se encuentra registrado como capacitador.");
		// DEFINIR MODAL PARA UI
		$row['fk_personal_id']=$row['personal_id'];
		$row['modal']='Trainers';
		$row['edit']=false;
		// RETORNAR DATOS
		return $row;
	}
	
	/*
	 * REQUEST CAPACITADORES
	 */
	public function requestTrainers(){
		// CONSULTAR POR ID DE REGISTRO
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR POR ID DE CAPACITACION
		elseif(isset($this->post['entity']) && isset($this->post['entityId'])) $json=$this->requestByEntity($this->post['entity'],$this->post['entityId']);
		// CONSULTAR POR ID DE CAPACITACION
		elseif(isset($this->post['code'])) $json=$this->requestByPersonal($this->post['code']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	/*
	 * EVALUAR CAPACITADOR - EVALUACIÓN DE SATISFACCIÓN
	 */
	public function evaluateTrainers(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// ELIMINAR REGISTRO DE ANTERIORES PREGUNTAS
		$this->db->executeTested($this->db->getSQLDelete('trainer_has_question',"WHERE fk_capacitador_id={$this->post['capacitador_id']}"));
		// INGRESAR PREGUNTAS
		foreach($this->post['questions'] as $k=>$v){
			// GENERAR MODELO DE EVALUACION DE PREGuNTA
			$data=array(
				'fk_capacitador_id'=>$this->post['capacitador_id'],
				'fk_pregunta_id'=>$k,
				'calificacion'=>$v
			);	
			// GENERAR SENTENCIA DE INGRESO DE EVALUACIÓN Y EJECUTAR SENTENCIA
			$sql=$this->db->executeTested($this->db->getSQLInsert($data,'trainer_has_question'));
		}
		// ACTUALIZAR DATOS DE TRAINER EN CAPACITACION
		$this->post['capacitador_evaluacion_fecha']=$this->getFecha('dateTime');
		$this->post['capacitador_estado']='EVALUADO';
		// GENERAR STRING DE ACTUALIZACION Y EJECUTAR SENTENCIA
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// CONTAR EL NÚMERO DE TRAINERS A EVALUAR PARA CAMBIAR EL ESTADO DE LA CAPACITACION
		$trainers=$this->db->numRows($this->db->selectFromView($this->entity,"WHERE fk_entidad='training' AND fk_entidad_id={$this->post['fk_entidad_id']} AND capacitador_actividad='CAPACITADOR'"));
		$evaluated=$this->db->numRows($this->db->selectFromView($this->entity,"WHERE fk_entidad='training' AND fk_entidad_id={$this->post['fk_entidad_id']} AND capacitador_actividad='CAPACITADOR' AND capacitador_estado='EVALUADO'"));
		// VALIDAR SI TODOS LOS CAPACITADORES HAN SIDO EVALUADOS PARA PONER COMO EVALUADA LA CAPACITACION 
		if($trainers==$evaluated){
			// OBTENER DATOS DE EVALUACION
			$row=$this->db->findById($this->post['fk_entidad_id'],'training');
			// ACTUALIZAR ESTADO DE CAPACITACION
			$row['capacitacion_estado']='EVALUADA';
			// GENERAR STRING DE ACTUALIZACION Y EJECUTAR SENTENCIA
			$sql=$this->db->executeTested($this->db->getSQLUpdate($row,'training'));
		}	
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * IMPRESIÓN DE CAPACITADOR
	 */
	public function printById($pdfCtrl,$id,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE capacitador_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// DATOS DE CAPACITADOR
			$row=$this->db->findOne($str);
			// DATOS DE CAPACITACIÓN
			$pdfCtrl->loadSetting($this->db->viewById($row['fk_entidad_id'],'training'));
			// DATOS DE EVALUACIÓN
			$row['tbScore']="";
			$index=1;
			foreach($this->getConfig(true,'prevention')['evaluationParams'] as $type){
				$row['tbScore'].="<tr class=subheader><td colspan=3 class=text-center>$type</td></tr>";
				// FORMAT DATOS EVALUACIÓN
				foreach($this->db->findAll($this->db->getSQLSelect('trainer_has_question',['questions'],"WHERE fk_capacitador_id=$id AND pregunta_tipo='$type'")) as $val){
					// LISTADO DE PERSONAL EN CAPACITACION
					$auxDetail=file_get_contents(REPORTS_PATH."detailQuestionsTrainer.html");
					$val['index']=$index;
					foreach($val as $k=>$v){
						$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);
					}	$row['tbScore'].=$auxDetail;
					$index++;
				}
			}
			$row['score']=$this->setFormatNumber($row['score']);
			$pdfCtrl->loadSetting($row);
		} else $this->getJSON('No se ha generado este registro');
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		return '';
	}
	
}