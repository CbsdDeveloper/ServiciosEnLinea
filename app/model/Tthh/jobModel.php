<?php namespace model\Tthh;
use api as app;
use controller as ctrl;

class jobModel extends app\controller {
	
	/*
	 * VARIABLES LOCALES
	 */
	private $entity='puestos';
	private $succes=0;
	private $insert=0;
	private $update=0;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PARENT
		parent::__construct();
	}
	
	
	/*
	 * PROCESAR DATOS
	 */
	private function processList($pathFile){
		// INSTANCIA XLS
		$myExcel=new ctrl\xlsController();
		// OBTENER DATOS DE EXCEL
		$data=$myExcel->getCellValue($this->post,$pathFile,$this->entity);
		// LISTAR direcciones - DIRECCIONES
		$direcciones=array();
		foreach($this->db->findAll($this->db->selectFromView('direcciones',"WHERE direccion_estado='ACTIVO'")) as $leaderShip){
			$direcciones[$leaderShip['direccion_nombre']]=$leaderShip['direccion_id'];
		}
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("No se han procecsado nuevos registros");
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// RECORRER LISTA DE REGISTROS
		foreach($data as $key=>$val){
			// VALIDAR ESTACIÓN
			if(isset($direcciones[$val['direccion_nombre']])){
				// INGRESAR ID DE REFERENCIA A PERSONA
				$val['fk_direccion_id']=$direcciones[$val['direccion_nombre']];
				// STRIING PARA CONSULTAR REGISTRO
				$str=$this->db->selectFromView($this->entity,"WHERE puesto_id={$val['puesto_id']} OR UPPER(puesto_nombre)=UPPER('{$val['puesto_nombre']}')");
				// CONSULTAR SI EXISTE REGISTRO
				if($this->db->numRows($str)>0){
					// OBTENER INFORMACIÓN DE REGISTRO
					$row=$this->db->findOne($str);
					// ACTUALIZAR REGISTRO Y GENERAR STRING DE ACTUALIZACIÓN
					$str2=$this->db->getSQLUpdate(array_merge($row,$val),$this->entity);
					// INCREMENTAR CONTADOR DE REGISTROS ACTUALIZADOS
					$this->update+=1;
				}else{
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
					$sql['mensaje'].="<br>Error provocado en registro con código -> {$val['puesto_nombre']}";
					// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
					return $this->db->closeTransaction($sql);
				}
			}
		}	
		// RETORNAR DATOS EXITOSOS
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: {$this->insert}  <br>Datos actualizados: {$this->update}";
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * CARGAR LISTADO DE EXCEL
	 */
	public function uploadList(){
		// PARAMETROS DE RECURSO
		$config=$this->uploaderMng->config['entityConfig'][$this->entity];
		// PARAMETROS DE CONSULTA
		$sql=$this->uploaderMng->uploadFile($config);
		// VALIDAR CARGA DE ARCHIVO
		if(!$sql['estado']) $this->getJSON($sql);
		// NOMBRE DE ARCHIVO
		$imgNew=$sql['mensaje'];
		// OBTENER ARCHIVO
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['puesto_name']}";
		// COMPROBAR SI EL ARCHIVO SE HA SUBIDO CORRECTAMENTE
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// PROCEDER A INGRESAR DATOS
		$this->getJSON($this->processList($pathFile));
	}
	
	
	/*
	 * LISTADO DE PUESTOS LABORABLES PARA EL PERSONAL
	 */
	public function getJobs(){
		$data=array();
		foreach($this->db->findAll($this->db->getSQLSelect('puestos',[],"WHERE puesto_estado='ACTIVO'")) as $val){
			$data[$val['puesto_departamento']][]=$val;
		}	$this->getJSON($this->setJSON("OK",true,$data));
	}
	
	/*
	 * INGRESO DE REGISTROS
	 */
	public function insertEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * LISTADO DE PUESTOS POR DIRECCIÓN
	 */
	private function requestJobsByLeadership($leadershipId){
		// OBTENER DATOS DE PUESTOS
		return $this->db->findAll($this->db->selectFromView('puestos',"WHERE fk_direccion_id=$leadershipId AND puesto_estado='ACTIVO' ORDER BY puesto_grado,puesto_nombre"));
	}
	
	/*
	 * LISTADO DE PUESTOS PERTENECIENTES A SUBORDINADOS
	 */
	private function requestSubordinatesByJob($jobId){
		// MODELO DE DATOS PARA PRESENTAR
		$data=array('selected'=>array(),'jobs'=>array());
		// OBTENER LISTADO DE SUBORDINADOS YA INGRESADOS POR PUESTO
		foreach($this->db->findAll($this->db->selectFromView('subordinados',"WHERE fk_superior_id={$jobId}")) as  $v){
			// INGRESAR EL LISTADO DE SUBORDINADOS
			$data['selected'][]=$v['fk_subordinado_id'];
		}
		// OBTENER LISTADO DE PUESTOS
		$rows=$this->db->findAll($this->db->selectFromView('vw_puestos',"WHERE puesto_id<>{$jobId} AND puesto_estado='ACTIVO' ORDER BY direccion_nombre, puesto_nombre"));
		foreach($rows as $k=>$v){
			// REGISTRAR EL MODELO DE DIRECCIONES
			if(!isset($data['jobs'][$v['direccion_nombre']])) $data['jobs'][$v['direccion_nombre']]=array();
			// GENERAR LISTADO DE PUESTOS
			$data['jobs'][$v['direccion_nombre']][]=$v;
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
		
	}
	
	/*
	 * ACTIVIDADES DE LOS PUESTOS DE TRABAJO
	 */
	private function requestActivitiesByJob($jobId){
		// DEFINIR MODELO
		$data=array(
			'list'=>array(),
			'selected'=>array()
		);
		// LISTADO DE ACTIVIDADES
		foreach($this->db->findAll($this->db->selectFromView("actividadeslaborales","WHERE actividad_estado='{$this->varGlobal['STATUS_ACTIVE']}'")) as $k=>$v){
			// VALIDAR SI EXISTE EL TIPO DE ACTIVIDAD
			if(!isset($data['list'][$v['actividad_tipo']])) $data['list'][$v['actividad_tipo']]=array();
			// VALIDAR SI EXISTE EL TIPO DE ACTIVIDAD
			if(!isset($data['selected'][$v['actividad_tipo']])) $data['selected'][$v['actividad_tipo']]=array();
			// INGRESAR LOS ITEMS DE CADA TIPO
			$data['list'][$v['actividad_tipo']][]=$v;
		}
		// LISTADO DE ACTIVIDADES
		foreach($this->db->findAll($this->db->getSQLSelect("puestos_actividades",['actividadeslaborales'],"WHERE fk_puesto_id={$jobId}")) as $k=>$v){
			// VALIDAR SI EXISTE EL TIPO DE ACTIVIDAD
			$data['selected'][$v['actividad_tipo']][]=$v['actividad_id'];
		}
		// RETORNAR DATOS
		return $data;
	} 
	
	/*
	 * ACTIVIDADES DE LOS PUESTOS DE TRABAJO
	 */
	private function requestCompetencesByJob($jobId){
		// DEFINIR MODELO
		$data=array(
			'list'=>array(),
			'selected'=>array()
		);
		// LISTADO DE ACTIVIDADES
		foreach($this->db->findAll($this->db->selectFromView("competenciaslaborales","WHERE competencia_estado='{$this->varGlobal['STATUS_ACTIVE']}' ORDER BY competencia_id")) as $k=>$v){
			// VALIDAR SI EXISTE EL TIPO DE ACTIVIDAD
			if(!isset($data['list'][$v['competencia_tipo']])) $data['list'][$v['competencia_tipo']]=array();
			// VALIDAR SI EXISTE EL TIPO DE ACTIVIDAD
			if(!isset($data['selected'][$v['competencia_tipo']])) $data['selected'][$v['competencia_tipo']]=array();
			// INGRESAR LOS ITEMS DE CADA TIPO
			$data['list'][$v['competencia_tipo']][]=$v;
		}
		// LISTADO DE ACTIVIDADES
		foreach($this->db->findAll($this->db->getSQLSelect("puestos_competencias",['competenciaslaborales'],"WHERE fk_puesto_id={$jobId}")) as $k=>$v){
			// VALIDAR SI EXISTE EL TIPO DE ACTIVIDAD
			$data['selected'][$v['competencia_tipo']][]=$v['competencia_id'];
		}
		// RETORNAR DATOS
		return $data;
	} 
	
	/*
	 * CONSULTAR PUESTOS Y DIRECCIONES
	 */
	public function requestJobs(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// CONSULTAR ID DE DIRECCION / JEFATURA
		elseif(isset($this->post['leadershipId'])) $json=$this->requestJobsByLeadership($this->post['leadershipId']);
		// CONSULTAR POR ID - ACTIVIDADES DE PUESTOS
		elseif(isset($this->post['activityId'])) $json=$this->requestActivitiesByJob($this->post['activityId']);
		// CONSULTAR POR ID - COMPETENCIAS DE PUESTOS
		elseif(isset($this->post['competenceId'])) $json=$this->requestCompetencesByJob($this->post['competenceId']);
		// CONSULTAR LISTADO DE PUESTOS - SUBORDINADOS
		elseif(isset($this->post['type']) && $this->post['type']=='subordinates') $json=$this->requestSubordinatesByJob($this->post['jobId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * INGRESO DE ACTIVIDADES DE PUESTO
	 */
	public function insertActivitiesJobs(){
		// RECIBIR FORMULARIO
		$post=$this->requestPost();
		// COMENZAR TRANSACICON
		$this->db->begin();
		// EIMINAR ACTIVIDADES YA REGISTRADAS
		$sql=$this->db->executeTested($this->db->getSQLDelete("puestos_actividades","WHERE fk_puesto_id={$post['puesto_id']}"));		
		// RECORRER REGISTROS DE ACTIVIDADES
		foreach($post['selected'] as $k=>$activities){
			// VALIDAR LA LONGITUD DE REGISTROS
			if(count($activities)<1) $this->getJSON($this->db->closeTransaction($this->setJSON("Transacción abortada, debe seleccionar al menos 1 ítem para {$k}")));
			// RECORRER E INGRESAR ACTIVIDADES
			foreach($activities as $activity){
				// MODELO TEMPORAL
				$model=array(
					'fk_puesto_id'=>$post['puesto_id'],
					'fk_actividad_id'=>$activity
				);
				// GENERAR STRING DE CONSULTA Y EJECUTAR SENTENCIA
				$sql=$this->db->executeTested($this->db->getSQLInsert($model,'puestos_actividades'));
			}
		}
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * INGRESO DE COMPETENCIAS DE PUESTO
	 */
	public function insertCompetencesJobs(){
		// RECIBIR FORMULARIO
		$post=$this->requestPost();
		// COMENZAR TRANSACICON
		$this->db->begin();
		// EIMINAR ACTIVIDADES YA REGISTRADAS
		$sql=$this->db->executeTested($this->db->getSQLDelete("puestos_competencias","WHERE fk_puesto_id={$post['puesto_id']}"));		
		// RECORRER REGISTROS DE ACTIVIDADES
		foreach($post['selected'] as $k=>$activities){
			// VALIDAR LA LONGITUD DE REGISTROS
			if(count($activities)<1) $this->getJSON($this->db->closeTransaction($this->setJSON("Transacción abortada, debe seleccionar al menos 1 ítem para {$k}")));
			// RECORRER E INGRESAR ACTIVIDADES
			foreach($activities as $activity){
				// MODELO TEMPORAL
				$model=array(
					'fk_puesto_id'=>$post['puesto_id'],
					'fk_competencia_id'=>$activity
				);
				// GENERAR STRING DE CONSULTA Y EJECUTAR SENTENCIA
				$sql=$this->db->executeTested($this->db->getSQLInsert($model,'puestos_competencias'));
			}
		}
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZAR LISTADO DE SUBORDINADOS - PUESTOS
	 */
	public function updateSubordinates(){
		// OBTENER FORMULARIO DE ENVÍO
		$post=$this->requestPost();
		// GENERAR RESPUESTA POR DEFECTO
		$sql=$this->setJSON("No se ha enviado ningun dato");
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// ELIMINAR LOS SUBORDINADOS INGRESADOS
		$sql=$this->db->executeTested($this->db->getSQLDelete('subordinados',"WHERE fk_superior_id={$post['jobId']}"));
		// RECORRER LISTADO DE SUBORDINADO DESDE FORMULARIO
		foreach($post['selected'] as $k=>$v){
			// MODELO AUXILIAR DE SUBORDINADOS 
			$auxModel=array('fk_subordinado_id'=>$v,'fk_superior_id'=>$post['jobId']);
			// GENERAR SENTENCIA SQL
			$str=$this->db->getSQLInsert($auxModel,'subordinados');
			// EJECUTAR Y VALIDAR CONSULTA SQL
			$sql=$this->db->executeTested($str);
		}
		// CERRAR TRANSACCIÓN Y RETORNAR RESPUESTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
}