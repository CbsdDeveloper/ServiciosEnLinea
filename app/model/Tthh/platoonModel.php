<?php namespace model\Tthh;
use api as app;

class platoonModel extends app\controller {
	
	private $entity='tropas';
	private $insert=0;
	private $update=0;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
	 * INFORMACIÓN ORIGINAL DEL PELOTÓN
	 */
	private function infoPlatoon($platoonId){
		// MODELO - PELOTON
		$data=array();
		// INFORMACIÓN DE PELOTON
		$data['info']=$this->db->findById($platoonId,'pelotones');
		// MODELO PARA ALMACENAR PERSONAL INGRESADO EN EL PELOTON
		$data['staff']=array();
		// SENTENCIA SQL PARA CONSULTAR PERSONAL EN PELOTONES - PERSONAL INGRESADO EN EL PELOTON
		$data['staff']=$this->db->findAll($this->db->selectFromView($this->entity,"WHERE fk_peloton_id={$platoonId} AND ppersonal_estado='ACTIVO'"));
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * PARSE DISTRIBUTIVO
	 */
	private function getDistributionName($distributionId){
		$distribution=$this->db->viewById($distributionId,'distributivo_peloton');
		return str_replace('RUPO ','',"{$distribution['estacion_nombre']}{$distribution['peloton_nombre']}");
	}
	
	/*
	 * ASIGNACION DE PERSONAL A DISTRIBUTIVO
	 */
	public function insertPlatoon(){
		
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// MENSAJE POR DEFECTO
		$sql=$this->setJSON("¡Operación realizada con éxito!",true);
		
		// RECORRER MODELO DE DISTRIBUTIVO - LISTA DE ESTACIONES
		foreach($this->post['platoons'] as $platoonsList){
			
			// RECORRER LISTADO DE PELOTONES
			foreach($platoonsList as $platoonInfo){
				
				// OBTENER NOMBRE DE GRUPO
				$distributionName=str_replace(' GRUPO ','G',"{$platoonInfo['info']['nombre']}");
				
				// RECORRER LISTADO DE FUNCIONES DE GRUPOS
				foreach($platoonInfo['functions'] as $functionName=>$staffList){
					
					// PERSONAL ASIGNADO A UNA FUNCION
					$staffTotal=count($staffList['items']);
					
					// VALIDAR LONGITUD DE ENCARGADOS DE ESTACION
					//if($functionName=='ENCARGADO DE ESTACION' && $staffTotal>1) $this->getJSON($this->db->closeTransaction("¡Operación cancelada, se debe seleccionar un solo <b>{$functionName}</b> para <b>{$distributionName}</b>!"));
					
					// VALIDAR SI SE HAN REGISTRADO PERSONAS AL DISTRIBUTIVO
					if($staffTotal>0){
					
						// RECORRER LISTADO DE PERSONAL
						foreach($staffList['items'] as $staffInfo){
							
							
							// VALIDAR SI ES ENCARGADO DE ESTACION
							if($functionName=='ENCARGADO DE ESTACION'){
								
								// STRING PARA CONSULTAR SI EXISTE ENCARGADO DE ESTACION
								$str=$this->db->selectFromView("vw_{$this->entity}","WHERE tropa_cargo='ENCARGADO DE ESTACION' AND tropa_estado='ACTIVO' AND fk_dist_pelo_id={$platoonInfo['info']['id']}");
								// VALIDAR SI EXISTE ENCARGADO DE ESACION
								if($this->db->numRows($str)>0){
									// OBTENER REGISTRO 
									$row=$this->db->findOne($str);
									// VALIDAR SI ES EL MISMO
									if($row['fk_personal_id']==$staffInfo['id']) continue;
									// INHABILITAR REGISTRO
									$row['tropa_estado']='SUSPENDIDO';
									$this->db->executeTested($this->db->getSQLUpdate($row,$this->entity));
								}
									
								// MODELO AUXILIAR
								$aux=array(
									'fk_dist_pelo_id'=>$platoonInfo['info']['id'],
									'fk_personal_id'=>$staffInfo['id'],
									'tropa_cargo'=>$functionName,
									'tropa_detalle'=>"INGRESO A PELOTON: [{$distributionName}]"
								);
								// GENERAR MODELO AUXILIAR
								$str=$this->db->getSQLInsert($aux,$this->entity);
								$this->insert+=1;
								
							}else{
								
								// GENERAR CONSULTA SQL - BUSCAR SI EL PERSONAL YA HA SIDO REGISTRADO ANTERIORMENTE
								$str=$this->db->selectFromView("vw_{$this->entity}","WHERE fk_personal_id={$staffInfo['id']}");
								
								// EJECUTAR CONSULTA
								if($this->db->numRows($str)>0){
									
									// FORMULARIO DE REGISTROS
									$row=$this->db->findOne($str);
									
									// VALIDAR SI NO EXISTEN CAMBIOS EN PERSONAL PARA SALTAR REGISTRO
									if($row['fk_dist_pelo_id']==$platoonInfo['info']['id'] && $row['tropa_cargo']==$functionName) continue;
									
									// DATOS DE DISTRIBUTIVO ACTUAL
									$oldDistributionName=$this->getDistributionName($row['fk_dist_pelo_id']);
									
									// MODIFICAR CON DATOS ENVIADOS
									$row['tropa_estado']='ACTIVO';
									$row['tropa_cargo']=$functionName;
									if($row['fk_dist_pelo_id']<>$platoonInfo['info']['id']){
										// CAMBIO DE PELOTON
										$row['fk_dist_pelo_id']=$platoonInfo['info']['id'];
										// REGISTRAR EL CAMBIO DE PELOTON
										$row['tropa_detalle']="CAMBIO DE PELOTÓN: [ {$oldDistributionName} => {$distributionName} ]";
									}
									// ACTUALIZAR DATOS DE PERSONAL
									$str=$this->db->getSQLUpdate($row,$this->entity);
									$this->update+=1;
									
									
								}else{
									
									// MODELO AUXILIAR
									$aux=array(
										'fk_dist_pelo_id'=>$platoonInfo['info']['id'],
										'fk_personal_id'=>$staffInfo['id'],
										'tropa_cargo'=>$functionName,
										'tropa_detalle'=>"INGRESO A PELOTON: [{$distributionName}]"
									);
									// GENERAR MODELO AUXILIAR
									$str=$this->db->getSQLInsert($aux,$this->entity);
									$this->insert+=1;
									
								}
								
							}
							
							// EJECUTAR CONSULTA DE INGRESO/MODIFICACIÓN DE PERSONAL
							$sql=$this->db->executeTested($str);
							
						}
					
					}
					
				}
				
			}
			
		}
		
		// INFORMACIÓN DE DATOS ACTUALIZADOS E INGRESADOS
		$sql['mensaje'].="<br>A continuación se detalla el resultado de la operación<br>Datos ingresados: {$this->insert}<br>Datos actualizados: {$this->update}";
		
		// REGISTRAR USUARIO
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZAR / INGRESAR PERSONAL PELOTONES
	 */
	public function updatePlatoon(){
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// CONSULTAR SI EXISTE EL REGISTRO EN PELOTONES
		$str=$this->db->selectFromView($this->entity,"WHERE fk_personal_id={$this->post['personal_id']}");
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			// ACTUALIZAR DATOS DE REGISTRO
			$str=$this->db->getSQLUpdate(array_merge($row,$this->post),$this->entity);
		}else{
			// ELIMINAR ID DE PELOTON
			unset($this->post['ppersonal_id']);
			// ID DE PERSONAL
			$this->post['fk_personal_id']=$this->post['personal_id'];
			// INGRESAR DATOS DE REGISTRO
			$str=$this->db->getSQLInsert($this->post,$this->entity);
		}
		// EJECUTAR CONSULTA SQL
		$sql=$this->db->executeTested($str);
		// REGISTRAR USUARIO
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR LOS PELOTONES PARA LA DISTRIBUCIÓN DE PERSONAL
	 */
	private function requestAllPlatoon(){
		// MODELO DE CONSULTA
	    $data=array('edit'=>false);
	    
	    // INSERTAR DATOS DE DISTRIBUTIVO ACTIVO
	    $data['distribution']=$this->db->findById($this->post['distributionId'],'distributivo');
	    // VALIDAR ESTADO DE EDICIÓN
	    $data['edit']=true;
	    
		// LISTAR PELOTONES ACTIVOS
		$data['list']=$this->db->findAll($this->db->selectFromView('vw_pelotones',"WHERE peloton_estado='ACTIVO' ORDER BY estacion_nombre, peloton_nombre"));
		// MODELO DE CONSULTA
		$data['selected']=array();
		// STRING PARA BUSCAR SI EXISTE ABIERTO UN PERIODO
		$str=$this->db->selectFromView('vw_distributivo_peloton',"WHERE fk_distributivo_id={$this->post['distributionId']}");			
		// VALIDAR SI EXISTE UN PERIODO EN CURSO
		if($this->db->numRows($str)>0){
			// RECORRER LOS PELOTONES Y REGISTRAR LOS SELECCIONADOS
			foreach($this->db->findAll($str) as $v){
				// INGRESAR ID DE PELOTON RELACIONADO
				$data['selected'][]=$v['fk_peloton_id'];
			}
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * LISTADO DE PERSONAL POR PELOTONES PARA DISTRIBUTIVO
	 */
	private function requestByPlatoonId($platoonId){
		// STRING PARA CONSULTAR LA EXISTENCIA DE UN PELOTÓN EN UN DISTRIBUTIVO ACTIVO
		$str=$this->db->selectFromView('vw_distributivo_peloton',"WHERE fk_peloton_id={$platoonId} AND distributivo_estado='ACTIVO'");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)<1) $this->getJSON("El pelotón seleccionado no ha sido asignado a un distributivo <B>ACTIVO</B>, favor verifique que haya sido asignado e intente nuevamente.");
		// MODELO DE DATOS
		$data=array(
			'model'=>array(),
			'functions'=>$this->string2JSON($this->varGlobal['TTHH_OFFICIAL_FUNCTIONS_LIST']),
			'entity'=>$this->db->findOne($str),
			'platoons'=>[],
			'jobs'=>[]
		);
		// GENERAL MODELO PARA SELECCION DE PERSONAL
		foreach($data['functions'] as $key){ $data['model'][$key]=array(); }
		// CONSULTAR PELOTONES DE UNA ESTACIÓN
		foreach($this->db->findAll($this->db->selectFromView("vw_personal","WHERE ppersonal_estado='EN FUNCIONES' AND puesto_modalidad='OPERATIVO' ORDER BY personal_nombre")) as $v){
			// VALIDAR SI SE HA CREADO EL MODELO POR ESTACIONES
			if(!isset($data['personal'][$v['puesto_nombre']])){
				$data['personal'][$v['puesto_nombre']]=array();
				$data['jobs'][]=$v['puesto_nombre'];
			}
			// INGRESAR LOS PELOTONES
			$data['personal'][$v['puesto_nombre']][]=$v;
		}
		// CONSULTAR PELOTONES DE UNA ESTACIÓN
		foreach($this->db->findAll($this->db->selectFromView($this->entity,"WHERE fk_dist_pelo_id={$data['entity']['dist_pelo_id']}")) as $v){
			// VALIDAR SI SE HA CREADO EL MODELO POR ESTACIONES
			if(!isset($data['model'])) $data['platoons'][$v['tropa_cargo']]=array();
			// INGRESAR LOS PELOTONES
			$data['model'][$v['tropa_cargo']][]=$v['fk_personal_id'];
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * LISTADO DE PERSONAL POR ESTACION PARA DISTRIBUTIVO
	 */
	private function requestByStation($stationId){
		// MODELO DE DATOS
		$data=array(
			'jobs'=>[],
			'model'=>array(),
			'functions'=>$this->string2JSON($this->varGlobal['TTHH_OFFICIAL_FUNCTIONS_LIST']),
			'personal'=>[]
		);
		// GENERAL MODELO PARA SELECCION DE PERSONAL
		foreach($data['functions'] AS $key){
			$data['model'][$key]=array();
		}
		// CONSULTAR PELOTONES DE UNA ESTACIÓN
		foreach($this->db->findAll($this->db->selectFromView("vw_personal","WHERE personal_estado='ACTIVO' AND puesto_modalidad='OPERATIVO' ORDER BY personal_nombre")) as $v){
			// VALIDAR SI SE HA CREADO EL MODELO POR ESTACIONES
			if(!isset($data['personal'][$v['puesto_nombre']])){
				$data['personal'][$v['puesto_nombre']]=array();
				$data['jobs'][]=$v['puesto_nombre'];
			}
			// INGRESAR LOS PELOTONES
			$data['personal'][$v['puesto_nombre']][]=$v;
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * LISTADO DE PERSONAL PARA DISTRIBUTIVO
	 */
	private function requestByDistributionId($distributionId){
		// BUSCAR SI EXISTEN PELOTONES ASIGNADOS
		$str=$this->db->selectFromView("distributivo_peloton","WHERE fk_distributivo_id={$distributionId}");
		// VALIDAR REGISTROS ACTIVOS
		if($this->db->numRows($str)<1) $this->getJSON("No existen pelotones asignados!");
		
		// MODELO DE DATOS
		$data=array(
			'platoons'=>array(),
			'personal'=>array(
				'listName'=>'PERSONAL',
				'items'=>array(),
				'dragging'=>false
			)
		);
		
		// GENERAL MODELO PARA SELECCION DE PERSONAL
		foreach($this->string2JSON($this->varGlobal['TTHH_OFFICIAL_FUNCTIONS_LIST']) as $key){
			$functionsList[$key]=array(
				'listName'=>$key,
				'items'=>array(),
				'dragging'=>false
			);
		}
		
		// CONSULTAR PELOTONES DE UNA ESTACIÓN
		$staffList=$this->db->findAll($this->db->getSQLSelect($this->db->setCustomTable("vw_personal","personal_id id,personal_nombre nombre,puesto_definicion puesto"),[],"WHERE ppersonal_estado='EN FUNCIONES' AND puesto_modalidad='OPERATIVO'"));
		$data['personal']['items']=$staffList;
		$data['staff']=$data['personal'];
		
		// LISTADO DE PELOTONES ACTIVOS EN EL DISTRIBUTIVO
		foreach($this->db->findAll($this->db->selectFromView('vw_distributivo_peloton',"WHERE fk_distributivo_id={$distributionId} ORDER BY estacion_nombre, peloton_nombre")) as $v){
			
			// MODELO TEMPORAL DE LISTADO DE FUNCIONES
			$tempFunctionsList=$functionsList;
			
			// LISTADO DE PELOTONES ACTIVOS EN EL DISTRIBUTIVO
			foreach($this->db->findAll($this->db->selectFromView('vw_tropas',"WHERE fk_dist_pelo_id={$v['dist_pelo_id']} AND tropa_estado='ACTIVO'")) as $val){
				// DATOS DE PERSONAL
				$staff=array(
					'id'=>$val['fk_personal_id'],
					'nombre'=>$val['personal_nombre'],
					'puesto'=>$val['puesto_definicion']
				);
				// ASIGNAR PERSONAL A DISTRIBUTIVO
				$tempFunctionsList[$val['tropa_cargo']]['items'][]=$staff;
				
				// ELIMINAR PERSONAL EN DISTRIBUTIVO
				if(($key = array_search($staff,$data['personal']['items'])) !== false) unset($data['personal']['items'][$key]);
				
			}
			
			$data['personal']['items']=array_values($data['personal']['items']);
			
			// CARGAR MODELO TEMPORAL
			$data['platoons'][$v['estacion']][]=array(
				'info'=>array(
					'station'=>$v['estacion_id'],
					'id'=>$v['dist_pelo_id'],
					'nombre'=>"{$v['estacion_nombre']} {$v['peloton_nombre']}"
				),
				'functions'=>$tempFunctionsList
			);
			
		}
		
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTA DE PELOTONES
	 */
	public function requestPlatoon(){
		// DATOS DE REGISTRO POR ID
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],'pelotones');
		// CONSULTAR TODOS LOS PELOTONOES - DISTRIBUCIÓN DE PERSONAL
		elseif(isset($this->post['platoonAll'])) $json=$this->requestAllPlatoon();
		// LISTADO DE PELOTON
		elseif(isset($this->post['platoonId'])) $json=$this->requestByPlatoonId($this->post['platoonId']);
		// LISTADO DE PELOTON
		elseif(isset($this->post['distributionId'])) $json=$this->requestByDistributionId($this->post['distributionId']);
		// LISTADO DE PELOTON POR ESTACIONES
		elseif(isset($this->post['stationId'])) $json=$this->requestByStation($this->post['stationId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * INGRESO DE DISTRIBUCIÓN DE PERSONAL
	 */
	public function insertDistribution(){
		// CONSULTAR SI EXISTE UN DISTRIBUTIVO ABIERTO
		$str=$this->db->selectFromView('distributivo',"WHERE distributivo_periodo_inicio='{$this->post['distribution']['distributivo_periodo_inicio']}'");
		// VALIDAR SI ESTÁ REGISTRADO
		if($this->db->numRows($str)>0) $row=$this->db->findOne($str);
		else $this->getJSON("No se ha encontrado el registro!");
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		
		// COSULTAR DISTRIBUTIBO EN TROPAS
		$strPlatoon=$this->db->getSQLSelect($this->db->setCustomTable('tropas',"DISTINCT(fk_dist_pelo_id)"));
		// ELIMINAR REGISTROS PREVIOS
		$json=$this->db->executeTested($this->db->getSQLDelete('distributivo_peloton',"WHERE fk_distributivo_id={$row['distributivo_id']} AND dist_pelo_id NOT IN ({$strPlatoon})"));
		// REGISTRAR GRUPOS [PELOTONES] A DISTRIBUCIÓN
		foreach($this->post['selected'] as $pelotonId){
			// GENERAR MODELO DE INGRESO
			$model=array(
				'fk_peloton_id'=>$pelotonId,
				'fk_distributivo_id'=>$row['distributivo_id']
			);
			// VALIDAR EXISTENCIA DE REGISTRO
			$str=$this->db->selectFromView('distributivo_peloton',"WHERE fk_peloton_id={$pelotonId} AND fk_distributivo_id={$row['distributivo_id']}");
			// VALIDAR EL REGISTRO REPETIDO
			if($this->db->numRows($str)<1) $json=$this->db->executeTested($this->db->getSQLInsert($model,'distributivo_peloton'));
		}
		
		// CERRAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}

	
}