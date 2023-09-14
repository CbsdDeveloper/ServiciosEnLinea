<?php namespace model\prevention;
use api as app;
use model as mdl;
use controller as ctrl;

class participantModel extends mdl\personModel {
	
	private $entity='participantes';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * PROCESAR DATOS
	 */
	private function processList($pathFile){
		// Instancia de librería y lectura de archivo
		$myExcel=new ctrl\xlsController();
		$data=$myExcel->getCellValue($this->post,$pathFile,$this->entity);
		
		// Empezar transacción
		$succes=0;
		$insert=0;
		$update=0;
		
		// INICIAR TRANSACCION
		$this->db->begin();
		// RECORRER LISTADO
		foreach($data as $val){
			
			// FLAG PARA INGRESO DE SOLO CEDULAS REALES
			$val['persona_tipo_doc']='CEDULA';
			// CONTROL DE DATOS DE PERSONAS
			$person=$this->requestPerson($val);
			
			// INGRESAR ID DE REFERENCIA A PERSONA
			$val['fk_persona_id']=$person['persona_id'];
			$val['fk_capacitacion_id']=$this->post['capacitacion_id'];
			$val['fk_entidad_id']=$this->post['fk_entidad_id'];
			
			// VALIDAR SI EXISTE PARTICIPANTES
			$str=$this->db->selectFromView($this->entity,"WHERE fk_persona_id={$person['persona_id']} AND fk_entidad_id={$this->post['fk_entidad_id']}");
			// VALIDAR SI EXISTE EL REGISTRO
			if($this->db->numRows($str)>0){
				// OBTENER REGISTRO
				$row=$this->db->findOne($str);
				// ID DE REGISTRO
				$val['fk_participante_id']=$row['participante_id'];
			}else{
				// INGRESAR REGISTRO
				$sql=$this->db->executeTested($this->db->getSQLInsert($val,$this->entity));
				// ID DE REGISTRO
				$val['fk_participante_id']=$this->db->getLastID($this->entity);
			}
				
			// CONSULTAR SI EXISTE REGISTRO CON ID DE PERSONA
			$strWhr="WHERE fk_participante_id={$val['fk_participante_id']} AND fk_capacitacion_id={$this->post['capacitacion_id']}";
			// CONSULTAR REGISTRO EN CAPACITACIONES
			$str=$this->db->selectFromView("training_has_people",$strWhr);
			// VALIDAR SI EXISTE EL REGISTRO
			if($this->db->numRows($str)>0){
				// GENERAR ACTUALIZACION DE REGISTRO
				$update++;
				// MENSAJE DE ACTUALIZACION
				$sql=$this->setJSON("Actualizado {$update}",true);
				/*
				// VALIDAR SI LA CAPACITACIÓN ESTÁ DESPACHADA PARA GENERAR LA REFRENDACIÓN
				if($this->post['capacitacion_estado']=='DESPACHADA'){
					// SETTER FECHA DE REFRENDACION DE CERTIFICADO DE CAPACITACION
					$val['refrendacion_fecha']=$this->setFormatDate($val['refrendacion_fecha'],'Y-m-d');
					// GENERAR STRING DE ACTUALIZACION DE REGISTRO
					$str="UPDATE prevencion.tb_training_has_people SET estado='REFRENDADO', refrendacion_serie='{$val['refrendacion_serie']}', refrendacion_fecha='{$val['refrendacion_fecha']}', refrendacion_entidad='{$val['refrendacion_entidad']}' $strWhr";
					// EJECUTAR CONSULTA SIMPLE
					$sql=$this->db->executeSingle($str);
				}
				*/
			} else {
				// GENERAR INGRESO DE REGISTRO
				$insert++;
				// INSERTAR REGISTRO EN CAPACITACIONES
				$sql=$this->db->executeSingle($this->db->getSQLInsert($val,'training_has_people'));
			}
				
			
			// VALIDAR TRANSACCION
			if(!$sql['estado']){
				$sql['mensaje'].="<br>Error provocado en registro con código -> {$person['persona_doc_identidad']}";
				return $this->db->closeTransaction($sql);
			}
			
		}	
		// VALIDAR CARGA DE DATOS
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: $insert  <br>Datos actualizados: $update";
		// RETORNAR CONSULTA Y CERRAR TRANSACCION
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * LISTADO DE PERSONAL
	 */
	public function uploadList(){
		// SUBIR ARCHIVO
		$config=$this->uploaderMng->config['entityConfig'][$this->entity];
		// OBTENER ARCHIVO
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// DIRECCION DE ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['participantes_name']}";
		// SI NO EXISTE EL ARCHIVO
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// PROCESAR DATOS
		$this->getJSON($this->processList($pathFile));
	}
	
	
	/*
	 * ASIGNAR/ELIMINAR PARTICIPANTES A UNA CAPACITACIÓN
	 * PROJECT: system
	 */
	private function getParticipantsByTraining($trainingId){
		$data=array();
		// MODELO PARA LISTA DE PARTICIPANTES MARCADOS COMO QUE SÍ ASISTIÓ A UNA CAPACITACIÓN
		$data['attendance']=array();
		// LISTADO DE PARTICIPANTES DE UNA CAPACITACIÓN
		$str=$this->db->selectFromView('vw_participantes_in_training',"WHERE fk_capacitacion_id={$trainingId} ORDER BY persona_apellidos,persona_nombres");
		foreach($this->db->findAll($str) as $val){
			$data['participants'][]=$val;
			if($val['estado']=='ASISTIO') $data['attendance'][]=$val['fk_participante_id'];
		}	
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * ASIGNAR/ELIMINAR PARTICIPANTES A UNA CAPACITACIÓN
	 * ACCIÓN REALIZADA EN EL LADO DEL CLIENTE
	 */
	public function requestParticipant(){
		// CONSULTAR POR ID DE CAPACITACIÓN - FROM SYSTEM
		if(isset($this->post['trainingId']) && app\session::getKey()=='session.system') $json=$this->getParticipantsByTraining($this->post['trainingId']);
		// CONSULTA POR DEFECTO
		else $json=$this->setJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($json);
	}
	
	
	/*
	 * SELECCION DE PARTICIPANTES
	 */
	public function setEntity(){
		
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// MENSAJE POR DEFECTO
		$json=$this->setJSON("No ha seleccionado ningún integrante para su capacitación.");
		
		// VALIDAR ENVIO DE LISTA DE PARTICIPANTES
		if(isset($this->post['selected']) && !empty($this->post['selected'])){
			
			// ELIMINAR LISTADO ACTUAL DE PARTICIPANTES
			$json=$this->db->executeTested($this->db->getSQLDelete('training_has_people',"WHERE fk_capacitacion_id={$this->post['capacitacion_id']} AND estado='PENDIENTE'"));
			
			// RECORRER LISTADO DE PARTICIPANTES
			foreach($this->post['selected'] as $v){
				// GENERAR MODELO TEMPORAL
				$model=array(
					'fk_participante_id'=>$v,
					'fk_capacitacion_id'=>$this->post['capacitacion_id']
				);
				// GENERAR Y EJECUTAR CONSULTA
				$json=$this->db->executeTested($this->db->getSQLInsert($model,'training_has_people'));
			}
		
		}
		
		// CERRAR TRANSACCION Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	/*
	 * DECLARAR ESTADO DE PARTICIPANTE EN CAPACITACIÓN
	 * ACCIÓN REALIZADA PARA DECLARAR QUE EL PARTICIPANTE ASISTIÓ O NO A LA CAPACITACIÓN
	 */
	public function updateEntity(){
		// LISTADO DE ESTADO PARA PARTICIPANTE
		$aux=array('PENDIENTE'=>'ASISTIO','ASISTIO'=>'AUSENTE','AUSENTE'=>'ASISTIO');
		// CONSULTA
		$strWhere="WHERE fk_participante_id={$this->post['fk_participante_id']} AND fk_capacitacion_id={$this->post['trainingId']}";
		// CUSTOM QUERY - ESTADO DE PARTICIPANTE
		$tb=$this->db->setCustomTable('training_has_people','estado','prevencion');
		// OBETENER REGISTRO
		$row=$this->db->findOne($this->db->getSQLSelect($tb,[],$strWhere));
		// ACTUALIZAR ESTADO
		$str="UPDATE prevencion.tb_training_has_people SET estado='{$aux[$row['estado']]}' {$strWhere}";
		$this->getJSON($this->db->executeSingle($str));
	}
	
	/*
	 * REGISTRO DE PERSONAL RELACIONADO CON LA ENTIDAD (RUC)
	 */
	public function insertEntity(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($this->post);
		// GENERAR MODELO TEMPORAL
		$participant=array(
			'fk_persona_id'=>$person['persona_id'],
			'fk_entidad_id'=>app\session::get()
		);
		// GENERAR Y EJECUTAR CONSULTA - RETORNAR RESPUESTA AL BACKEND
		$this->getJSON($this->db->closeTransaction($this->db->executeTested($this->db->getSQLInsert($participant,$this->entity))));
	}
	
}