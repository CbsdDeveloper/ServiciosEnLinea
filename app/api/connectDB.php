<?php namespace api;
/**
 * Description of connectDB
 *
 * @author Lalytto
 * @mail apinango@lalytto.com
 */
class connectDB {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $db;
	public $estado=false;
	public $mensaje='No se pudo completar esta operación.. <br>';
	// LISTA DE ESQUEMAS DE TABLAS
	public $schemaList='main';
	// PARÁMETROS DE MODELO DE BASE DE DATOS
	public $entityModel;
	// PREFIJO DE TABLAS
	public $sufijoTb='tb_';
	// MANTENER LARELACIÓN FK_USUARIO_ID
	public $keepFKUser=false;
	// PARÁMETROS CRUD
	public $crudConfig;
	// CONTROLLER
	private $ctrl;
	
	// **********************************************************************************************
	// **************************************** CONSTRUCTOR *****************************************
	// **********************************************************************************************
	/*
	 * Método: Constructor,crea instancia de PDO
	 */
	public function __construct($ctrl) {
		try {
			// OBTENER CONTROLADOR
			$this->ctrl=$ctrl;
			// EXTRAER PARÁMETROS DE CONEXIÓN
			$cfg=$this->ctrl->getConfig('db')[session::$sysName];
			// CREAR INSTANCIA DE CONEXIÓN A LA BASE DE DATOS
			$this->db=new \PDO("{$cfg['driver']}:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};user={$cfg['user']};password={$cfg['pass']}");
			// MODELO DE ENTIDADES
			$this->entityModel=$this->ctrl->getConfig(false,'model');
			// ARCHIVOS DE CONFIGURACIÓN CRUD
			$this->crudConfig=$this->ctrl->getConfig(false,'crud');
		} catch(PDOException $e) {
			echo json_encode(array('mensaje'=>$e->getMessage(),'estado'=>false));
			exit;
		}
	}
	
	
	// **********************************************************************************************
	// **************************************** CONSULTAS DB ****************************************
	// **********************************************************************************************
	/*
	 * Método: Restablecer los mensaje de respuesta de conexión
	 */
	private function resetMessage(){
		$this->estado=false;
		$this->mensaje='No se pudo completar la transacción.. <br><br>';
	}
	/*
	 * Realiza la consulta comprobando si existen errores
	 */
	private function testErrorPDO($sql){
		$this->resetMessage();
		$sql=$this->prepare($sql);
		$sql->execute();
		$myError=$sql->errorInfo();
		if($myError[0]!=0){
			echo json_encode(array('mensaje'=>$this->mensaje.$myError[2],'estado'=>false));
			exit;
		}	return $sql;
	}
	/*
	 * Ejecuta las consultas sql
	 */
	public function consulta($sql){
		return $this->testErrorPDO($sql);
	}
	/*
	 * Test de consulta activando pruebas de error (mensajes db)
	 */
	public function prepare($sql){
		$this->db->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_WARNING);
		$this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES,false);
		return $this->db->prepare($sql);
	}
	/*
	 * ENCONTRAR TODOS LOS REGISTROS POR UNA CADENA DE CONSULTA
	 */
	public function findAll($str){
		$sql=$this->testErrorPDO($str);
		return $sql->fetchAll(\PDO::FETCH_ASSOC);
	}
	/*
	 * ENCONTRAR UN REGISTRO POR UNA CADENA DE CONSULTA
	 */
	public function findOne($str){
		$sql=$this->testErrorPDO($str);
		return $sql->fetch(\PDO::FETCH_ASSOC);
	}
	/*
	 * ENCONTRAR REGISTRO POR ID
	 */
	public function findById($id,$tb){
		if($id==null || $id=='') return null;
		extract($this->getParams($tb));
		$str=$this->getSQLSelect($tb,[],"WHERE {$serial}={$id}");
		$sql=$this->testErrorPDO($str);
		return $sql->fetch(\PDO::FETCH_ASSOC);
	}
	/*
	 * USAR SOLO CUANDO NO HAY TRANSACCIONES
	 */
	public function viewById($id,$tb){
		if($id==null || $id=='') $this->ctrl->getJSON("No se ha enviado ningún ID - [entity={$tb}]!");
		extract($this->getParams($tb));
		$str=$this->selectFromView("vw_{$tb}","WHERE {$serial}={$id}");
		if($this->numRows($str)>0){
			$sql=$this->testErrorPDO($str);
			return $sql->fetch(\PDO::FETCH_ASSOC);
		}else $this->ctrl->getJSON("No se ha encontrado el registro...!");
	}
	/*
	 * RETORNA EL LISTADO DE REGISTRO A PARTIR DE UNA CONSULTA SQL ANTERIOR
	 */
	public function fetchSql($sql,$type='fetch'){
		return $sql->$type(\PDO::FETCH_ASSOC);
	}
	/*
	 * RETORNA EL NUEMERO DE REGISTROS DE UNA  CONSULTA SQL ANTERIOR
	 */
	public function numRows($sql,$auto=true){
		if($auto)$sql=$this->consulta($sql);
		return $sql->rowCount();
	}
	/*
	 * REALIZA LA CONSULTA SQL, PREPARANDO LOS MENSAAJE QUE SERÁN PRESENTADOS CON LA RESPUESTA
	 */
	public function execute($str){
		$this->resetMessage();
		$sql=$this->prepare($str);
		$exec=$sql->execute();
		$this->estado=($exec > 0 ? true : false);
		$this->mensaje=($exec< 1 ? $this->mensaje.$sql->errorInfo()[2] : '¡Datos guardados correctamente!');
	}
	/*
	 * REALIZAR CONSULTAS UNITARIAS
	 */
	public function executeSingle($str){
		// EJECUTAR SENTENCIA
		$this->execute($str);
		// RETORNAR CONSULTA
		return $this->ctrl->setJSON($this->mensaje,$this->estado);
	}
	/*
	 * REALIZAR OPERACIÓN CON VALIDACIÓN DE ERROR
	 */
	public function executeTested($str){
		// EJECUTAR SENTENCIA
		$sql=$this->executeSingle($str);
		// VALIDAR RETORNO DE CONSULTA
		if(!$sql['estado']){
			// ADJUNTAR STRING DE ERROR
			$data=array_merge($sql,array('str'=>$str));
			// CERRAR TRANSACCIÓN EN CASO DE EXISTIR
			if($this->inTransaction())$data=$this->closeTransaction($data);
			// PRESENTAR RESULTADO
			$this->ctrl->getJSON($data);
		}
		// RETORNAR CONSULTA
		return $sql;
	}
	/*
	 * OBTENER ÚLTIMO ID DE ENTIDAD REGISTRADA
	 */
	public function getLastID($tb){
		// EXTRAER DATOS DE ENTIDAD
		$cfg=$this->getParams($tb);
		// RETORNAR NOMBRE DE SECUENCIA
		return $this->db->lastInsertId($cfg['sequence']);
	}
	/*
	 * OBTENER SIGUIENTE ID DE ENTIDAD
	 */
	public function getNextId($tb){
		// PARÁMETROS DE ENTIDAD
		$cfg=$this->getParams($tb);
		// GENERAR QUERY
		$cfg=$this->findOne($this->getSQLSelect($this->setCustomTable($tb,"MAX({$cfg['serial']}) id",$cfg['table_schema'])));
		return ($cfg['id']==null?0:$cfg['id'])+1;
	}
	/*
	 * GENERAR SERIE DE TRÁMITE DESPACHADO DEL AÑO EN CURSO
	 */
	public function getNextSerie($tb){
		// PARÁMETROS PARA GENERACIÓN DE SERIE
		$this->nxt=$this->crudConfig['nextSerieProject'][$tb];
		// PARÁMETROS DE ENTIDAD
		$cfg=$this->getParams($tb);
		// VALIDACIÓN DE AÑO EN CURSO
		$strWhere="WHERE (date_part('year',{$this->nxt['dateField']})=date_part('year',CURRENT_DATE))";
		// VALIDAR SI SE REQUIEE ESTADO --> $statusKey FROM CRUD
		if(isset($this->nxt['statusKey'])) $strWhere.=" AND {$this->nxt['statusKey']} {$this->nxt['statusVal']}";
		// GENERAR QUERY
		$cfg=$this->findOne($this->selectFromView($this->setCustomTable($tb,"max({$this->nxt['numberField']}) id",$cfg['table_schema']),$strWhere));
		return ($cfg['id']==null?0:$cfg['id'])+1;
	}
	/*
	 * GENERADOR DE CÓDIGO DE PROYECTOS
	 */
	public function nextCodeProject($entity){
		// DEFINIR PREFIJO DE SERIE
		$pref="CBSD";
		// DEFINIR AÑO EN CURSO
		$year=date('y');
		// DEFINIR SIGUIENTE ID DE PROYECTO
		$entityId=$this->getNextId($entity);
		// OBTENER PARÁMETROS PARA GENERACIÒN - CÒDIGO DE PROYECTO
		$config=$this->crudConfig['nextCodeProject'][$entity];
		// OBTENER PREFIJO DE PROYECTO
		$project=$config['project'];
		// CONSULTAR ÚLTIMO ID DE CÓDIGO GENERADO DEL AÑO
		$strWhr="WHERE (date_part('year',barcode_fecha)=date_part('year',CURRENT_DATE)) AND barcode_proyecto='{$project}'";
		$str=$this->selectFromView($this->setCustomTable('barcodes',"MAX(barcode_serie) id",'resources'),$strWhr);
		// EXTRAER ID DE CONSULTA E INCREMENTAR 1
		$row=$this->findOne($str);
		$id=($row['id']==null?0:$row['id'])+1;
		// FORMATEAR A 5 NÚMEROS
		$serie=str_pad($id,5,"0",STR_PAD_LEFT);
		// GENERAR MODELO DE SERIE
		$barcode=array(
				'barcode_serie'=>$id,
				'barcode_proyecto'=>$project,
				'barcode_proyecto_id'=>$entityId
		);
		// REGISTRAR CÓDIGO DE PROYECTOS
		$this->executeTested($this->getSQLInsert($barcode,'barcodes'));
		// RETORNAR CÓDIGO DE PROYECTO GENERADO
		return array('nextId'=>$entityId,'code'=>"{$pref}{$year}{$project}{$serie}",'codeField'=>$config['codeField']);
	}
	/*
	 * RETORNA LA ÚLTIMA ENTIDAD INGRESADA
	 */
	public function getLastEntity($tb){
		return $this->findById($this->getLastID($tb),$tb);
	}
	/*
	 * Retorna el ID del último registro ingresado en una consulta
	 */
	public function getById($tb,$id){
		$cfg=$this->getParams($tb);
		return $this->findOne($this->getSQLSelect($tb,[],"WHERE {$cfg['serial']}={$id}"));
	}
	
	// **********************************************************************************************
	// **************************************** TRANSACTIONS ****************************************
	// **********************************************************************************************
	/*
	 * INICIAR TRANSACCIÓN
	 */
	public function begin(){
		$this->db->beginTransaction();
	}
	/*
	 * CONSULTAR SI EXISTE UNA TRANSACCIÓN ABIERTA
	 */
	public function inTransaction(){
		return $this->db->inTransaction();
	}
	/*
	 * FINALIZA CON ÉXITO UNA TRANSACCIÓN
	 */
	public function commit(){
		$this->db->commit();
	}
	/*
	 * REVIERTE OPERACIONES DE UNA TRANSACCIÓN
	 */
	public function rollback(){
		$this->db->rollback();
	}
	/*
	 * CIERRA TRANSACCIONES
	 */
	public function closeTransaction($str,$sql=true){
		$sql=($sql)?$str:$this->executeSingle($str);
		$sql['estado']?$this->commit():$this->rollback();
		return $sql;
	}
	
	// **********************************************************************************************
	// ************************** GENERADOR MODELO .json DE CONFIGURACIÓN ***************************
	// **********************************************************************************************
	// *** MÉTODOS PARA EXTRAER CAMPOS DE TABLAS Y TABLAS DE SCHEMAS ***
	/*
	 * Método auxiliar: Utilizado para reemplazar estándar de las tablas de almacenamiento "tb_" y obtener el id
	 */
	public function getTbAttr($schema,$tb){
		$sql=$this->findOne("SELECT
				i.relname as index_name,
				a.attname as serial,
				d.adsrc   as sequence
				FROM
				pg_class t
				JOIN pg_attribute a ON a.attrelid=t.oid
				JOIN pg_index ix    ON t.oid=ix.indrelid AND a.attnum=ANY(ix.indkey)
				JOIN pg_class i     ON i.oid=ix.indexrelid
				LEFT JOIN pg_attrdef d ON d.adrelid=t.oid and d.adnum=a.attnum
				WHERE
				t.relkind='r'
				AND t.relname in('$tb')
				ORDER BY
				t.relname,
				i.relname,
				a.attnum");
		$sql['sequence']=str_replace("nextval('",'',$sql['sequence']);
		$sql['sequence']=str_replace("'::regclass)",'',$sql['sequence']);
		if(strpos($sql['sequence'],"{$schema}.")===FALSE)$sql['sequence']="{$schema}.{$sql['sequence']}";
		return $sql;
	}
	/*
	 * Método: Retorna los las columnas de una tabla
	 */
	public function getCampos($tb){
		return $this->getFromSchema('column_name',$tb);
	}
	/*
	 * Método:Realiza la consulta para los metodos "getTabla" y "getCampos"
	 */
	private function getFromSchema($op='table_name',$tb='',$schema='public'){
		$str=($op=='column_name'
				?"SELECT $op FROM INFORMATION_SCHEMA.columns WHERE table_name='$tb'"
				:"SELECT $op FROM INFORMATION_SCHEMA.tables WHERE table_schema='$schema'");
		$sql=$this->consulta($str);
		$return=array();
		while($row=$this->fetchSql($sql)){
			$return[]=$row[$op];
		}	return $return;
	}
	/*
	 * Método: Retorna el nombre de la tabla que desea hacer alguna transacción,desde el nombre de una columna
	 */
	public function getTabla($campo){
		// RECORRE CADA UNO DE LOS SCHEMAS DECLARADOS
		foreach($this->ctrl->getConfig('schemaList') as $val){
			$tbls=$this->getFromSchema('table_name','',$val);
			foreach($tbls as $tb){ if(in_array($campo,$this->getCampos($tb))) return $tb; }
		}	return "";
	}
	/*
	 * Método: Retorna las propiedades de columnas de una tabla
	 */
	public function getCamposAttr($schema,$tb,$byField=false){
		if($byField!=false) $tb=$this->getTabla($byField);
		$sql=$this->consulta("SELECT * FROM INFORMATION_SCHEMA.columns WHERE table_name ='$tb' AND table_schema='$schema'");
		$config=array();
		$columns=array();
		while($row=$this->fetchSql($sql)){
			$config[$row['column_name']]=array(
					'type'=>$row['data_type'],
					'isNull'=>$row['is_nullable']
			);
			$columns[]=$row['column_name'];
		}	return array('columns'=>$columns,'config'=>$config);
	}
	/*
	 * Método: Retorna la lista de tablas de un schema y sus propiedades
	 */
	public function getTablas($schema,$type='tables'){
		return $this->findAll("SELECT * FROM INFORMATION_SCHEMA.$type where table_schema='$schema' ORDER BY table_name");
	}
	/*
	 * Retorna las fks
	 */
	public function getTbRelations($tb){
		// ENTIDADES A LAS QUE PRESTA FK
		/*select R.*
		 from INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE u
		 inner join INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS FK
		 on U.CONSTRAINT_CATALOG = FK.UNIQUE_CONSTRAINT_CATALOG
		 and U.CONSTRAINT_SCHEMA = FK.UNIQUE_CONSTRAINT_SCHEMA
		 and U.CONSTRAINT_NAME = FK.UNIQUE_CONSTRAINT_NAME
		 inner join INFORMATION_SCHEMA.KEY_COLUMN_USAGE R
		 ON R.CONSTRAINT_CATALOG = FK.CONSTRAINT_CATALOG
		 AND R.CONSTRAINT_SCHEMA = FK.CONSTRAINT_SCHEMA
		 AND R.CONSTRAINT_NAME = FK.CONSTRAINT_NAME
		 WHERE U.TABLE_NAME = 'tb_entidades'
		 AND U.TABLE_SCHEMA = 'permisos'*/
		$sql=$this->findAll("
				SELECT
				tc.constraint_name,
				kcu.column_name,
				ccu.table_schema foreign_table_schema,
				ccu.table_name foreign_table_name,
				ccu.column_name foreign_column_name
				FROM
				INFORMATION_SCHEMA.table_constraints AS tc
				JOIN INFORMATION_SCHEMA.key_column_usage kcu USING (constraint_schema,constraint_name)
				JOIN INFORMATION_SCHEMA.constraint_column_usage ccu USING (constraint_schema,constraint_name)
				WHERE constraint_type='FOREIGN KEY' AND tc.table_name='$tb'
				AND tc.table_schema<>'records'
				ORDER BY column_name");
		$fks=array();
		$list=array();
		foreach($sql as $val){
			$fks[$val['foreign_table_name']]=$val;
			$list[]=$val['column_name'];
		}	return array('fkRef'=>$fks,'fkList'=>$list);
	}
	/*
	 * Retorna todas las tablas de los schemas declarados en config
	 */
	public function getTbList(){
		// Tiempo de carga - segundos
		set_time_limit(1200);
		// modelo de datos
		$tbArrayList=array();
		$tbArrayList['inSchema']=array();
		$tbArrayList['viewList']=array();
		$tbArrayList['tbList']=array();
		$tbArrayList['tbDecode']=array();
		$tbArrayList['tbConfig']=array();
		$tbArrayList['tbColumns']=array();
		foreach($this->ctrl->getConfig('schemaList') as $key=>$schemaList){
			foreach($schemaList as $schema){
				// CREACIÓN DE ESQUEMAS
				$tbArrayList['inSchema'][$schema]=array();
				// LISTADO DE VISTAS
				foreach($this->getTablas($schema,'views') as $val){
					if(!in_array($val['table_name'],$tbArrayList['viewList']))
						$tbArrayList['viewList'][]=$val['table_name'];
				}
				// LISTADO DE TABLAS
				foreach($this->getTablas($schema) as $val2){
					// TABLAS EN ESQUEMAS
					if(!in_array($val2['table_name'],$tbArrayList['inSchema'][$schema]))
						$tbArrayList['inSchema'][$schema][]=$val2['table_name'];
						// LISTADO DE TABLAS
						if(!in_array($val2['table_name'],$tbArrayList['tbList']))
							$tbArrayList['tbList'][]=$val2['table_name'];
							// TABLAS -> VISTAS
							if(!isset($tbArrayList['tbDecode'][str_replace('tb_','',$val2['table_name'])]))
								$tbArrayList['tbDecode'][str_replace('tb_','',$val2['table_name'])]=$val2['table_name'];
								
								// TIPOS DE DATOS,LISTA DE COLUMNAS Y RELACIONES
								$fkList=$this->getTbRelations($val2['table_name']);
								$tbConfig=array_merge($val2,$this->getTbAttr($schema,$val2['table_name']),$fkList);
								// ATRIBUTOS DE TABLAS
								if(!isset($tbArrayList['tbConfig'][$key][$val2['table_name']]))
									$tbArrayList['tbConfig'][$key][$val2['table_name']]=$tbConfig;
									// LISTADO DE COLUMNAS
									if(!isset($tbArrayList['tbColumns'][$key][$val2['table_name']]))
										$tbArrayList['tbColumns'][$key][$val2['table_name']]=$this->getCamposAttr($schema,$val2['table_name']);
				}
			}
		}	return $tbArrayList;
	}
	
	// **********************************************************************************************
	// ********************************** GENERADORES DE CONSULTAS **********************************
	// **********************************************************************************************
	/*
	 * Retorna los json de configuración
	 */
	public function getTbColumns($tb){
		return $this->entityModel['tbColumns'][$this->schemaList][$tb];
	}
	/*
	 * Retorna los parámetros de las tablas
	 */
	public function getParams($tb,$scan=false){
		$config=$this->entityModel;
		if(empty($config))$config=$this->ctrl->setConfig($this->getTbList());
		// print_r($config['tbConfig'][$this->schemaList][$tb]);
		// return;
		// print_r($config);
		// CONSULTAR EN LISTADO DE VISTAS
		if(in_array($tb,$config['viewList']))return $config['tbConfig'][$this->schemaList][$tb];
		// CONSULTAR EN LISTADO DE TABLAS
		$tb=$this->decodeTable($tb);
		if(in_array($tb,$config['tbList']))return $config['tbConfig'][$this->schemaList][$tb];
		// RECURSIVIDAD
		if($scan)$this->ctrl->getJSON("La entidad ->$tb<- no existe en la base de datos!");
		else return $this->getParams($tb,true);
	}
	/*
	 * parse tb -> array
	 */
	public function setCustomTable($tb,$params='*',$tableSchema='admin'){
	    $cfg=$this->getParams($tb);
	    if($cfg['table_schema']!=$tableSchema) $tableSchema=$cfg['table_schema'];
		return array('tb'=>$tb,'table_name'=>$cfg['table_name'],'table_schema'=>$tableSchema,'params'=>$params,'fkRef'=>$cfg['fkRef']);
	}
	/*
	 * Método: Prepara la sentencia sql: SELECT
	 */
	public function getSQLSelect($tb,$inner=array(),$where=''){
	    $cfg=(is_array($tb))?$tb:$this->getParams($tb);
	    $cfg['tb']=(isset($cfg['tb'])?$cfg['tb']:$tb);
	    $params=(isset($cfg['params']))?$cfg['params']:"*";
	    $fkReal=$cfg['fkRef'];
		$str="SELECT {$params} FROM {$cfg['table_schema']}.{$cfg['table_name']} {$cfg['tb']}";
		if(count($inner)>0){
			foreach($inner as $val){
			    $cfg=$this->getParams($val);
				$str.=" INNER JOIN {$cfg['table_schema']}.{$cfg['table_name']} {$val} ON {$val}.{$cfg['serial']}={$fkReal[$cfg['table_name']]['column_name']}";
			}
		}	
		return "{$str} {$where}";
	}
	/*
	 * Método: Prepara la sentencia sql: SELECT - VISTAS
	 */
	public function selectFromView($tb,$where=''){
		$cfg=(is_array($tb))?$tb:$this->getParams($tb);
		$cfg['tb']=(isset($cfg['tb'])?$cfg['tb']:$tb);
		$params=(isset($cfg['params']))?$cfg['params']:"*";
		return str_replace("&#39;","'","SELECT {$params} FROM {$cfg['table_schema']}.{$cfg['table_name']} {$cfg['tb']} {$where}");
	}
	
	/*
	 * INGRESO DE AUDITORIA DE PERSONAL 
	 */
	private function getSessionResponsible($type,$tb){
		// DATOS DE SESION
		$data=session::setResource($type,$tb);
		
		// VALIDAR AUDITORIA TALENTO HUMANO
		if(in_array($tb,$this->crudConfig['auditTTHH'])){
			// TIPO DE SESIÓN
			$sessionType=session::getKey();
			// VALIDAR MÓDULO DE SESIÓN
			if($sessionType=='session.system'){
				// ID DE USUARIO
				$staffId=session::getSessionAdmin();
				// STRING PARA OBTENER ID DE PERSONA
				$strWhr=$this->getSQLSelect($this->setCustomTable('usuarios','fk_persona_id'),[],"WHERE usuario_id={$staffId}");
				// VALIDAR EXISTENCIA DE USUARIO
				$str=$this->selectFromView('personal',"WHERE fk_persona_id=({$strWhr})");
				// VALIDAR SI EXISTE SESSION
				if($this->numRows($str)==1) $data['fk_personal_id']=$this->findOne($str)['personal_id'];
				// RETORNAR MENSAJE POR DEFECTO
				else $this->ctrl->getJSON("No se ha encontrado ningún registro relacionado con su sesión:: << {$sessionType} >>.<BR><BR>Favor, verifique si su cuenta está asociada al registro activo de TTHH.");
			}elseif(in_array($sessionType,['session.tthh','session.subjefatura'])){
				// DATOS DE USUARIO QUE REGISTRA LA SALIDA
				$data['fk_personal_id']=session::get();
			}
		}
	
		// SESION POR USUARIO DEL SISTEMA
		return $data;
	}
	
	/*
	 * Método: Prepara la sentencia sql: INSERT
	 */
	public function getSQLInsert($post,$tb){
		// PARÁMETROS DE LA ENTIDAD
		$tbConfig=$this->getParams($tb);
		// VERIFICAR SI LA ENTIDAD REQUIERE CÓDIGO DE PROYECTO
		if(isset($this->crudConfig['nextCodeProject'][$tb])){
			// GENERAR CÓDIGO DE PROYECTO
			$codeEnty=$this->nextCodeProject($tb);
			// INSERTAR CÓDIGO DE PROYECTO EN FORMULARIO DE INGRESO
			$post[$codeEnty['codeField']]=$codeEnty['code'];
		}
		// OBTENER EL TIPO DE DATO DE LAS COLUMNAS
		$dataType=$this->getCamposAttr($tbConfig['table_schema'],$tbConfig['table_name']);
		// LISTADO DE COLUMNAS DE UNA ENTIDAD
		$tbColumns=$dataType['columns'];
		// TIPOS DE DATOS DE UNA ENTIDAD
		$columnConf=$dataType['config'];
		// VARIABLE PARA SABER SI MANTENER REGISTRO DE USUARIO O NO
		session::$keepFKUser=$this->keepFKUser;
		// INGRESAR DATOS DE AUDITORIA
		$post=array_merge($post,$this->getSessionResponsible('Ingreso',$tb));
		// OBTENER TIPOS DE DATOS ACEPTADOS
		$dataTypeColumns=$this->ctrl->getConfig('dataTypeColumns');
		// GENERAR CONSULTA INSERT
		$k=""; $v="";
		foreach($post as $key=>$val){
			// VALIDAR ATRIBUTOS DE ENTIDADES
			if(in_array(strtolower($key),$tbColumns)){
				// VALIDAR SI EL ATRIBUTO DE LA TABLA PUEDE SER NULO
				if(is_null($val)){
					if($columnConf[$key]['isNull']=='NO')$this->ctrl->getJSON("Falta completar el campo [$tb] -> [$key]");
					else continue;
				}
				// OBTENER TIPO DE DATOS DE ATRIBUTO
				$columnType=$columnConf[$key]['type'];
				// VALIDAR LOS TIPOS DE DATOS
				if(in_array($columnType,$dataTypeColumns['number'])){
					
				}elseif(in_array($columnType,$dataTypeColumns['boolean'])){
					// PARSE DATA
					$val=$this->ctrl->getBool($val);
				}elseif(in_array($columnType,$dataTypeColumns['date'])){
					// PARSE DATA
					$val="'".$this->ctrl->setFormatDate($val,'Y-m-d')."'";
				}elseif(in_array($columnType,$dataTypeColumns['dateTime'])){
					// PARSE DATA
					$val="'".$this->ctrl->setFormatDate($val,'Y-m-d H:i:s')."'";
				}else{
					// PARSE DATA
					$val="'".str_replace("'","&#39;",$val)."'";
				}
				// JOIN DATA
				$v.="{$val},";
				// JOIN ATRIBUTO DE TABLA
				$k.="{$key},";
			}
		}
		// ELIMINAR COMAS (,) DEMÁS EN PARÁMETROS
		$k=trim($k,",");
		$v=trim($v,",");
		// RETORNAR CONSULTA
		return "INSERT INTO {$tbConfig['table_schema']}.{$tbConfig['table_name']} ({$k}) VALUES({$v})";
	}
	/*
	 * Método: Prepara la sentencia sql: UPDATE
	 */
	public function getSQLUpdate($post,$tb,$customId=''){
		// PARÁMETROS DE LA ENTIDAD
		$tbConfig=$this->getParams($tb);
		// OBTENER EL TIPO DE DATO DE LAS COLUMNAS
		$dataType=$this->getCamposAttr($tbConfig['table_schema'],$tbConfig['table_name']);
		// LISTADO DE COLUMNAS DE UNA ENTIDAD
		$tbColumns=$dataType['columns'];
		// TIPOS DE DATOS DE UNA ENTIDAD
		$columnConf=$dataType['config'];
		// VARIABLE PARA SABER SI MANTENER REGISTRO DE USUARIO O NO
		session::$keepFKUser=$this->keepFKUser;
		// INGRESAR DATOS DE AUDITORIA
		$post=array_merge($post,$this->getSessionResponsible('Edición',$tb));
		// OBTENER TIPOS DE DATOS ACEPTADOS
		$dataTypeColumns=$this->ctrl->getConfig('dataTypeColumns');
		// STRING DE ACTUALIZACIÓN
		$aux=" WHERE "; $str="UPDATE {$tbConfig['table_schema']}.{$tbConfig['table_name']} SET ";
		foreach($post as $key=>$val){
			// VALIDAR ATRIBUTOS DE ENTIDADES
			if(in_array(strtolower($key),$tbColumns)){
				// VALIDAR SI EL ATRIBUTO DE LA TABLA PUEDE SER NULO
				if(is_null($val)){
					if($columnConf[$key]['isNull']=='NO')$this->ctrl->getJSON("Falta completar el campo $tb -> $key");
					else continue;
				}
				// OBTENER TIPO DE DATOS DE ATRIBUTO
				$columnType=$columnConf[$key]['type'];
				// VALIDAR LOS TIPOS DE DATOS
				if(isset($tbConfig['serial']) && $key==$tbConfig['serial']){
					$aux.="$key=$val";
				}elseif(in_array($columnType,$dataTypeColumns['number'])){
					
				}elseif(in_array($columnType,$dataTypeColumns['boolean'])){
					// PARSE DATA
					$val=$this->ctrl->getBool($val);
				}elseif(in_array($columnType,$dataTypeColumns['date'])){
					// PARSE DATA
					$val="'".$this->ctrl->setFormatDate($val,'Y-m-d')."'";
				}elseif(in_array($columnType,$dataTypeColumns['dateTime'])){
					// PARSE DATA
					$val="'".$this->ctrl->setFormatDate($val,'Y-m-d H:i:s')."'";
				}else{
					// PARSE DATA
					$val="'".str_replace("'","&#39;",$val)."'";
				}
				// JOIN DATA
				$str.="{$key}={$val},";
			}
		}
		// VALIDAR SENTENCIA DE CONSULTA - CUSTOM WHERE
		if($customId!='') $aux=$customId;
		// ELIMINAR COMAS (,) DEMÁS EN PARÁMETROS
		$str=trim($str,",");
		// RETORNAR CONSULTA
		return "{$str} {$aux}";
	}
	/*
	 * Método: Prepara la sentencia sql: DELETE
	 */
	public function getSQLDelete($tb,$where=''){
		// OBTENER PARÁMETROS DE ENTIDAD
		$cfg=$this->getParams($tb);
		// GENERAR STRING DE CONSULTA
		$str="DELETE FROM {$cfg['table_schema']}.{$cfg['table_name']} {$where}";
		// RETORNAR STRING DE CONSULTA
		return $str;
	}
	/*
	 * Método: Prepara la sentencia sql: DELETE
	 */
	public function deleteById($tb,$id){
		// VALIDAR LA EXISTENCIA DEL ID DE REGISTRO
		if(!is_int($id) && $id<1) $this->ctrl->getJSON("Operación interrumpida porque no ha sido enviado un parámetro de identificación");
		// DECODIFICAR ENTIDADES
		$crudParams=$this->ctrl->string2JSON($this->ctrl->varGlobal['crud']);
		// OBTENER PARÁMETROS DE ENTIDAD
		$cfg=$this->getParams(isset($crudParams['decodeEntity'][$tb])?$crudParams['decodeEntity'][$tb]:$tb);
		// GENERAR STRING DE CONSULTA
		$str="DELETE FROM {$cfg['table_schema']}.{$cfg['table_name']} WHERE {$cfg['serial']}={$id}";
		// RETORNAR STRING DE CONSULTA
		return $str;
	}
	/*
	 * Método:Devuelve un mensaje de no haber declaro la consulta de acceso y cancela cualquier proceso pendiente
	 */
	public function noConsulta($get=''){
		if($get=='') $this->ctrl->getJSON($this->ctrl->setJSON('Sentencia no declarada'));
		else $this->ctrl->getJSON($this->ctrl->setJSON('Error: Usted no cuenta con los permisos para acceder a la bade de datos!'));
	}
	/*
	 * Buscar en listado de tablas - antes agregando sufijo
	 */
	public function decodeTable($tb){
		$decode=$this->entityModel['tbDecode'];
		return (isset($decode[$tb]))?$decode[$tb]:$tb;
	}
	
}