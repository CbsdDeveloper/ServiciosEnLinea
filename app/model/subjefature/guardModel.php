<?php namespace model\subjefature;
use api as app;

class guardModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='guardias';
	private $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
		// CONFIGURACIÓN DE SUBJEFATURA
		$this->config=$this->getConfig(true,'subjefatura');
	}
	
	
	/*
	 * GENERAR CODIGO DE REGISTRO
	 */
	protected function newCodeEntity($date,$platoonId){
		// FECHA DE REGISTRO
		$fecha=$this->setFormatDate($date,'ymd');
		// ID DE SESION
		$sessionId=$this->getStaffIdBySession();
		
		// DATOS DE PELOTON
		$str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$sessionId} AND fk_dist_pelo_id={$platoonId}");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("Al parecer usted no se encuentra registrado dentro del distributivo, si condidera que es un error favor contáctese con la Dirección de Talento Humano");
		// OBTENER REGISTRO
		$platoon=$this->db->findOne($str);
		
		// NOMBRE DE PELOTON
		$platoonName=str_replace('RUPO ','',$platoon['peloton_nombre']);
		// INSERTAR CODIGO DE REGISTRO
		$platoon['guardia_codigo']="{$platoon['estacion_nombre']}{$platoonName}-{$fecha}";
		
		// RETORNAR CODIGO DE REGISTRO
		return $platoon;
	}
	
	/*
	 * INSERTAR NUEVO REGISTRO
	 */ 
	public function insertEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDAR FECHA DE REGISTRO
		if($this->post['guardia_fecha_tipo']=='SISTEMA') $this->post['guardia_fecha']=$this->getFecha('dateTime');
		
		// REGISTRO DE PELOTON
		$platoon=$this->newCodeEntity($this->post['guardia_fecha'],$this->post['fk_peloton_id']);
		
		// INSERTAR DATOS DE PELOTON
		$this->post['fk_peloton_id']=$platoon['fk_dist_pelo_id'];
		$this->post['guardia_codigo']=$platoon['guardia_codigo'];
		
		// REGISTRAR MOVIMIENTO DE UNIDAD
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICAR REGISTRO
	 */ 
	public function updateEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRAR MOVIMIENTO DE UNIDAD
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR PERSONAL DEL DISTRIBUTIVO
	 */
	private function staffByDistribution($distributionId){
		// MODELO DE LISTADO
		$list=array();
		
		// RECORRER LISTADO
		foreach($this->db->findAll($this->db->selectFromView('vw_personal_operativo',"WHERE fk_dist_pelo_id={$distributionId} AND tropa_cargo NOT IN ('ENCARGADO DE ESTACION') ORDER BY personal_nombre")) as $v){
			$list[$v['tropa_id']]=$v;
		}
		
		// RETORNAR LISTADO
		return $list;
	}
	
	/*
	 * CONSULTAR DATOS PARA REGISTRO DE GUARDIA
	 */
	private function requestByType(){
		// OBTENER ID DE SESIÓN
		$sessionId=app\session::get();
		
		// CONSULTAR PELOTON SEGUN LA SESION
		$str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$sessionId} AND tropa_estado='ACTIVO'");
		// CONSULTAR SI EXISTE EL REGISTRO
		if($this->db->findOne($str)<1) $this->getJSON("Lo sentimos, su cuenta no se encuentra asignada a ningún pelotón.");
		// LISTADO DE PELOTONES
		$distribution=$this->db->findAll($str);
		
		// ENCARGADO DE ESTACION
		$station=$this->db->findOne($this->db->selectFromView('vw_encargado_estacion',"WHERE fk_dist_pelo_id={$distribution[0]['fk_dist_pelo_id']}"));
		
		// CONSULTAR PERSONAL DE PELOTON PARA RELACIONES
		$staff=array();
		foreach($distribution as $v){
			$staff[$v['fk_dist_pelo_id']]=$this->db->findAll($this->db->selectFromView('vw_tropas',"WHERE fk_dist_pelo_id={$v['fk_dist_pelo_id']}"));
		}
		
		// GENERAR MODELO DE CONSULTA
		$model=array(
			'distribution'=>$distribution,
			'staff'=>$staff,
			'guardia_fecha_tipo'=>'SISTEMA',
			'fk_j1'=>$this->config['responsible']['j1'],
			'fk_j2'=>$this->config['responsible']['j2'],
			'fk_encargado_estacion'=>$station['ppersonal_id']
		);
		
		// RETORNAR MODELO
		return $model;
	}
	
	/*
	 * JORNADAS DE GUARDIA
	 */
	private function personalByTurn($guardId,$turnType){
		// OBTENER REGISTRO DE GUARDIA
		$guard=$this->db->findById($guardId,$this->entity);
		
		// MODELO DE CONSULTA
		$model=array(
			'entity'=>$guard,
			'staffList'=>$this->staffByDistribution($guard['fk_peloton_id']),
			'turns'=>array()
		);
		
		// OBTENER EL PERSONAL DE DISTRIBUTIVO
		foreach($model['staffList'] as $k=>$v){
			// GENERAR MODELO DE INGRESO
			$model['turns'][$k]=array(
				'turno_estado'=>'ACTIVO',
				'fk_guardia_id'=>$guardId,
				'fk_tropa_id'=>$k,
				'turno_jornada'=>$turnType
			);
		}
		
		// CONSULTAR REGISTRO INGRESADO
		foreach($this->db->findAll($this->db->selectFromView('guardia_turnos',"WHERE turno_jornada='{$turnType}' AND fk_guardia_id={$guardId}")) AS $v){
			$model['turns'][$v['fk_tropa_id']]=$v;
		}
		
		// RETORNAR CONSULTA
		return $model;
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
		// CONSUTAR ENTIDAD PARA REGISTRO NUEVO
		if(isset($this->post['type']) && $this->post['type']=='new') $json=$this->requestByType();
		// CONSULTAR REGISTRO POR ID 
		elseif(isset($this->post['guardia_id']) && !isset($this->post['turno_jornada'])) $json=$this->requestById($this->post['guardia_id']);
		// CONSULTAR REGISTRO POR TURNO O ID
		elseif(isset($this->post['guardia_id']) && isset($this->post['turno_jornada'])) $json=$this->personalByTurn($this->post['guardia_id'],$this->post['turno_jornada']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * REGISTRO DE TURNOS DE GUARDIAS
	 */
	public function setTurns(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// GENERAR SALIDA POR DEFECTO
		$sql=$this->setJSON("No ha registrado ningún usuario");
		
		// RECORRER LISTADO
		foreach($this->post['turns'] as $v){
			// STRING PARA CONSUTAR REGISTROS ANTERIORES
			$str=$this->db->selectFromView('guardia_turnos',"WHERE fk_guardia_id={$v['fk_guardia_id']} AND fk_tropa_id={$v['fk_tropa_id']} AND turno_jornada='{$this->post['turno_jornada']}'");
			// CONSULTAR SI EXISTE
			if($this->db->numRows($str)>0){
				// OBTENER REGISTRO Y ACTUALIZAR
				$aux=array_merge($this->db->findOne($str),$v);
				// GENERAR SENTENCIA SQL
				$str=$this->db->getSQLUpdate($aux,'guardia_turnos');
			}else{
				// GENERAR SENTENCIA SQL
				$str=$this->db->getSQLInsert($v,'guardia_turnos');
			}
			// EJECUTAR SENTENCIA SQL
			$sql=$this->db->executeTested($str);
		}
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// EXTRAER DATOS GET
		extract($this->requestGet());
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE guardia_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			
			
			$this->getJSON($row);
			
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['guardia_codigo']);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	
	/*
	 * VALIDAR REPORTE
	 */
	private function testCustomReport($pdfCtrl,$user,$row){
		$guard=$this->db->viewById($row['guardia_id'],$this->entity);
		$msg="";
		if($guard['diurna']==null || $guard['diurna']<1) $msg.="<li>Distribuir el personal en la guardia diurna.</li>";
		if($guard['nocturna']==null || $guard['nocturna']<1) $msg.="<li>Distribuir el personal en la guardia nocturna.</li>";
		if($guard['fk_responsable_guardia']==null || $guard['fk_responsable_guardia']<1) $msg.="<li>Registrar el responsable de guardia</li>";
		// VALIDAR MENSAJE
		if($msg=="") return true;
		// RETORNAR CONSULTA
		return $pdfCtrl->printCustomReport($msg,$user,$this->msgReplace($this->varGlobal['EMPTY_REPORT_DESCRIPTION'],$row));
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_informacion_guardias","WHERE guardia_id={$id}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// VALIDAR SI LOS DATOS HAN SIDO COMPLETADOS
			if(!$this->testCustomReport($pdfCtrl,$row['rg_nombre'],$row)) return '';
			
			// PARSE FECHAS
			$row['guardia_fecha_alt']=$this->setFormatDate($row['guardia_fecha'],'complete');
			$row['guardia_novedades']=nl2br($row['guardia_novedades']);
			
			// STRING PARA CONSULTAR OPERATIVOS DE UNA GUARDIA
			$staff=$this->db->findAll($this->db->selectFromView('vw_personal_operativo',"WHERE fk_dist_pelo_id={$row['fk_peloton_id']} AND tropa_cargo='OPERADOR'"));
			
			// IMPRESION DE OPERADORES
			$row['operadoresLista']="";
			// IMPRESION DE OPERADORES
			foreach($staff as $v){
				$row['operadoresLista'].="<tr><td>{$v['tropa_cargo']}</td><td>{$v['personal_nombre']}<br><small>{$v['puesto_definicion']}</small></td></tr>";
			}
			
			// DISTRIBUCION DE JORNADAS
			$dateBinnable="{$row['guardia_fecha']} 20:00";
			// RECORRES CONFIGURACIÓN DE SUBJEFATURA - GUARDIAS
			foreach($this->config['guards'] as $key=>$val){
				// DISTRIBUCION DE TURNOS
				$row[$val['turno']]="";
				foreach($this->db->findAll($this->db->selectFromView('vw_guardias_turnos',"WHERE fk_guardia_id={$id} AND turno_jornada='{$key}' ORDER BY turno_desde")) as $v){
					// VALIDAR EL ESTADO EN LA GUARDIA
					$turno=($v['turno_estado']!='ACTIVO')?"{$v['turno_estado']}":"{$v['turno_desde']} - {$v['turno_hasta']}";
					// IMPRESION DE REGISTRO
					$row[$val['turno']].="<tr><td>{$v['personal_nombre']}<br>{$v['puesto_definicion']}</td><td>{$turno}</td></tr>";
				}
				// REGISTRO DE LIBRO DE NOVEDADES
				$row[$val['libro']]="";
				foreach($this->db->findAll($this->db->selectFromView('libronovedades',"WHERE fk_guardia_id={$id} AND bitacora_fecha {$val['time']} '{$dateBinnable}'::timestamp ORDER BY bitacora_fecha")) as $v){
					$v['bitacora_fecha']=$this->setFormatDate($v['bitacora_fecha'],'H:i');
					$v['bitacora_detalle']=nl2br($v['bitacora_detalle']);
					$row[$val['libro']].="<tr><td>{$v['bitacora_fecha']}</td><td>{$v['bitacora_detalle']}</td></tr>";
				}
			}
			
			// CARGAR DATOS DE REGISTRO
			$pdfCtrl->loadSetting($row);
			
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}