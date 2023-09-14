<?php namespace app;
/**
 * Description of connectDB
 * connectDB.php hereda la conexión de la base de datos junto con los métodos de acceso a los registros del sistema, 
 * este proceso se ha realizado de forma semi-automatizada ya que se ha realizado procesos con recursividad 
 * con la finalidad de optimizar recursos (memoria en sistema en el caso de generar variables auxiliares)
 * @author Lalytto
 * @mail lalytto@live.com - apinango@lalytto.com
 */
 // Clase hija, Hereda métodos de clase de model, lo que le permite acceder a los métodos aún cuando estos no sean
 // declarados en la presente clase
class connectDB {
	private $db;
	public $estado=false;
	public $mensaje='No se pudo completar la transacción.. <br>';
	protected $schemaList='main';
	protected $entityModel;
	protected $sufijoTb='tb_';
	// Método: Constructor, crea instancia de PDO
	public function __construct() {
		try {
			extract($this->getConfig('db')[session::$sysName]);
			$this->db=new \PDO("$driver:host=$host;port=$port;dbname=$db;user=$user;password=$pass");
			$this->entityModel=$this->getConfig(false,'model');
		} catch(PDOException $e) {
			echo json_encode(array('mensaje'=>$e->getMessage(), 'estado'=>false));
			exit;
		}
	}
	// Método: Restablecer los mensaje de respuesta de conexión
	private function resetMessage(){
		$this->estado=false;
		$this->mensaje='No se pudo completar la transacción.. <br>';
	}
	// Realiza la consulta comprobando si existen errores
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
	// Ejecuta las consultas sql
	public function consulta($sql){
		return $this->testErrorPDO($sql);
	}
	// Test de consulta activando pruebas de error (mensajes db)
	public function prepare($sql){
		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
		$this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		return $this->db->prepare($sql);
	}
	// ************************************* findAll
	protected function findAll($str){
		$sql=$this->testErrorPDO($str);
		return $sql->fetchAll(\PDO::FETCH_ASSOC);
	}
	// ************************************* findOne
	protected function findOne($str){
		$sql=$this->testErrorPDO($str);
		return $sql->fetch(\PDO::FETCH_ASSOC);
	}
	protected function findById($id,$tb){
		extract($this->getParams($tb));
		$str=$this->getSQLSelect($tb,[],"WHERE $serial=$id");
		$sql=$this->testErrorPDO($str);
		return $sql->fetch(\PDO::FETCH_ASSOC);
	}
	// Retorna el listado de registro a partir de una consulta sql anterior
	public function fetch_sql($sql, $type='fetch'){
		return $sql->$type(\PDO::FETCH_ASSOC);
	}
	// Retorna el nuemero de registros de una  consulta sql anterior
	public function numRows($sql,$auto=false){
		return $this->num_rows($sql,$auto);
	}
	public function num_rows($sql, $auto=false){
		if($auto) $sql=$this->consulta($sql);
		return $sql->rowCount();
	}
	// Realiza la consulta sql, preparando los mensaaje que serán presentados con la respuesta
	public function execute($sql){
		$this->resetMessage();
		$sql=$this->prepare($sql);
		$exec=$sql->execute();
		$this->estado=($exec > 0 ? true : false);
		$this->mensaje=($exec< 1 ? $this->mensaje.$sql->errorInfo()[2] : 'Transacción completa!');
	}
	// Método: Realiza consultar unitarias (principalmente para ejecutar en una transacción)
	public function executeSingle($sql){
		$this->execute($sql);
		return $this->setJSON($this->mensaje, $this->estado);
	}
	public function executeTested($str){
		$sql=$this->executeSingle($str);
		if(!$sql['estado'])$this->getJSON($this->closeTransaction(array_merge($sql,array('str'=>$str))));
		return $sql;
	}
	// Retorna el ID del último registro ingresado en una consulta
	public function getLastID($tb){
		extract($this->getParams($tb));
		return $this->db->lastInsertId($sequence);
	}
	// Retorna el ID del último registro ingresado en una consulta
	public function getLastEntity($tb){
		return $this->getById($tb,$this->getLastID($tb));
	}
	// Retorna el ID del último registro ingresado en una consulta
	public function getById($tb,$id){
		extract($this->getParams($tb));
		return $this->findOne($this->getSQLSelect($tb,[],"WHERE $serial=$id"));
	}
	// **********************************************************************************************
	// **************************************** TRANSACTIONS ****************************************
	// **********************************************************************************************
	// Inicia una transacción
	public function begin(){
		$this->db->beginTransaction();
	}
	// Finaliza con exito una transaccion
	public function commit(){
		$this->db->commit();
	}
	// Revierte la transaccion
	public function rollback(){
		$this->db->rollback();
	}
	// Finaliza transacciones
	public function closeTransaction($sql){
		$sql['estado']?$this->commit():$this->rollback();
		return $sql;
	}
	// **********************************************************************************************
	// **************************** MÉTODOS EXTRAS PARA OPTIMIZAR TIEMPO ****************************
	// **********************************************************************************************
	// *** MÉTODOS PARA EXTRAER CAMPOS DE TABLAS Y TABLAS DE SCHEMAS ***
	// Método auxiliar: Utilizado para reemplazar estándar de las tablas de almacenamiento "tb_" y obtener el id
	protected function getTbAttr($schema,$tb){
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
	// Método: Retorna los las columnas de una tabla
	protected function getCampos($tb){
		return $this->getFromSchema('column_name',$tb);
	}
	// Método:Realiza la consulta para los metodos "getTabla" y "getCampos"
	private function getFromSchema($op='table_name',$tb='',$schema='public'){
		$str=($op=='column_name'
				?"SELECT $op FROM INFORMATION_SCHEMA.columns WHERE table_name='$tb'"
				:"SELECT $op FROM INFORMATION_SCHEMA.tables WHERE table_schema='$schema'");
		$sql=$this->consulta($str);
		$return=array();
		while($row=$this->fetch_sql($sql)){
			$return[]=$row[$op];
		}	return $return;
	}
	// Método: Retorna el nombre de la tabla que desea hacer alguna transacción, desde el nombre de una columna
	protected function getTabla($campo){
		// RECORRE CADA UNO DE LOS SCHEMAS DECLARADOS
		foreach($this->getConfig('schemaList') as $val){
			$tbls=$this->getFromSchema('table_name','',$val);
			foreach($tbls as $tb){ if(in_array($campo, $this->getCampos($tb))) return $tb; }
		}	return "";
	}
	// Método: Retorna las propiedades de columnas de una tabla
	protected function getCamposAttr($schema,$tb,$byField=false){
		if($byField!=false) $tb=$this->getTabla($byField);
		$sql=$this->consulta("SELECT * FROM INFORMATION_SCHEMA.columns WHERE table_name ='$tb' AND table_schema='$schema'");
		$config=array();
		$columns=array();
		while($row=$this->fetch_sql($sql)){
			$config[$row['column_name']]=array(
					'type'=>$row['data_type'],
					'isNull'=>$row['is_nullable']
			);
			$columns[]=$row['column_name'];
		}	return array('columns'=>$columns,'config'=>$config);
	}
	// Método: Retorna la lista de tablas de un schema y sus propiedades
	protected function getTablas($schema, $type='tables'){
		return $this->findAll("SELECT * FROM INFORMATION_SCHEMA.$type where table_schema='$schema' ORDER BY table_name");
	}
	// Retorna las fks
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
						JOIN INFORMATION_SCHEMA.key_column_usage kcu USING (constraint_schema, constraint_name)
						JOIN INFORMATION_SCHEMA.constraint_column_usage ccu USING (constraint_schema, constraint_name)
				WHERE constraint_type='FOREIGN KEY' AND tc.table_name='$tb' 
							AND tc.table_schema<>'records'
				ORDER BY column_name");
		$fks=array();
		$list=array();
		foreach($sql as $key=>$val){
			$fks[$val['foreign_table_name']]=$val;
			$list[]=$val['column_name'];
		}	return array('fkRef'=>$fks,'fkList'=>$list);
	}
	// Retorna todas las tablas de los schemas declarados en config
	protected function getTbList(){
		$tbArrayList=array();
		$tbArrayList['inSchema']=array();
		$tbArrayList['viewList']=array();
		$tbArrayList['tbList']=array();
		$tbArrayList['tbDecode']=array();
		$tbArrayList['tbConfig']=array();
		$tbArrayList['tbColumns']=array();
		foreach($this->getConfig('schemaList') as $key=>$schemaList){
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
					// TIPOS DE DATOS, LISTA DE COLUMNAS Y RELACIONES
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
	// ******************************* MÉTODOS PARA GENERAR CONSULTAS *******************************
	// **********************************************************************************************
	// Retorna los json de configuración
	public function getTbColumns($tb){
		return $this->entityModel['tbColumns'][$this->schemaList][$tb];
	}
	// Retorna los parámetros de las tablas
	public function getParams($tb,$scan=false){
		$config=$this->entityModel;
		if(empty($config))$config=$this->setConfig($this->getTbList());
		// CONSULTAR EN LISTADO DE VISTAS
		if(in_array($tb,$config['viewList']))return $config['tbConfig'][$this->schemaList][$tb];
		// CONSULTAR EN LISTADO DE TABLAS
		$tb=$this->decodeTable($tb);
		if(in_array($tb,$config['tbList']))return $config['tbConfig'][$this->schemaList][$tb];
		// RECURSIVIDAD
		if($scan)$this->getJSON("La entidad ->$tb<- no existe en la base de datos!");
		else return $this->getParams($tb,true);
	}
	// parse tb -> array
	public function setCustomTable($tb,$params='*',$table_schema='admin'){
		extract($this->getParams($tb));
		return array('tb'=>$tb,'table_name'=>$table_name,'table_schema'=>$table_schema,'params'=>$params,'fkRef'=>$fkRef);
	}
	// Método: Prepara la sentencia sql: SELECT
	public function getSQLSelect($tb,$inner=array(),$where=''){
		$params="*";
		if(is_array($tb))extract($tb);
		else extract($this->getParams($tb));
		$fkReal=$fkRef;
		$str="SELECT $params FROM $table_schema.$table_name $tb";
		if(count($inner)>0){
			foreach($inner as $val){
				extract($this->getParams($val));
				$str.=" INNER JOIN $table_schema.$table_name $val ON $val.$serial={$fkReal[$table_name]['column_name']}";
			}
		}	return "$str $where";
	}
	// Método: Prepara la sentencia sql: SELECT - VISTAS
	public function selectFromView($tb,$where=''){
		extract($this->getParams($tb));
		$fkReal=$fkRef;
		$str="SELECT * FROM $table_schema.$table_name $tb";
		return "$str $where";
	}
	// Método: Prepara la sentencia sql: INSERT
	public function getSQLInsert($post,$tb,$insertId=false){
		// Obtener los verdaderos datos de la tabla
		extract($this->getParams($tb));
		// Eliminar pkey del insert principal
		if(!$insertId) unset($post[$serial]);
		// Obtener el tipo de datos de lascolumnas :: INGRESAR EN getParams
		$dataType=$this->getCamposAttr($table_schema,$table_name);
		$tbColumns=$dataType['columns'];
		$columnConf=$dataType['config'];
		// settear datos de auditoria
		$post=array_merge(session::setResource('Ingreso',$tb),$post);
		// GET DATA TYPE COLUMNS VALUES
		$dataTypeColumns=$this->getConfig('dataTypeColumns');
		// Init datos para validar campos y valores
		$k=""; $v="";
		foreach($post as $key=>$val){
			// VALIDAR CAMPOS EN TABLAS
			if(in_array(strtolower($key),$tbColumns)){
				// VALIDAR CAMPOS NULOS
				if(($val == null || strlen($val)==0) && $columnConf[$key]['isNull']=='NO')$this->getJSON("Falta completar el campo $key");
				// TIPO DE DATOS
				$columnType=$columnConf[$key]['type'];
				$k.="$key,";
				if(in_array($columnType,$dataTypeColumns['number']))$v.="$val,";
				elseif(in_array($columnType,$dataTypeColumns['boolean']))$v.="$val,";
				elseif(in_array($columnType,$dataTypeColumns['date']))$v.="'".date_format(date_create($val),'Y-m-d')."',";
				elseif(in_array($columnType,$dataTypeColumns['dateTime']))$v.="'".date_format(date_create($val),'Y-m-d H:i:s')."',";
				else {
					$val=str_replace("'","&#39;",$val);
					$v.="'$val',";
				}
			}
		}	return "INSERT INTO $table_schema.$table_name (".trim($k, ",").") VALUES(".trim($v, ",").")";
	}
	// Método: Prepara la sentencia sql: UPDATE
	public function getSQLUpdate($post,$tb){
		// Obtener los verdaderos datos de la tabla
		extract($this->getParams($tb));
		// Obtener el tipo de datos de lascolumnas :: INGRESAR EN getParams
		$dataType=$this->getCamposAttr($table_schema,$table_name);
		$tbColumns=$dataType['columns'];
		$columnConf=$dataType['config'];
		// settear datos de auditoria
		$post=array_merge($post,session::setResource('Edición',$tb));
		// GET DATA TYPE COLUMNS VALUES
		$dataTypeColumns=$this->getConfig('dataTypeColumns');
		// init de consulta sql
		$aux=" WHERE "; $str="UPDATE $table_schema.$table_name SET ";
		foreach($post as $key=>$val){
			// VALIDAR CAMPOS EN TABLAS
			if(in_array(strtolower($key),$tbColumns)){
				// VALIDAR CAMPOS NULOS
				if(($val == null || strlen($val)==0) && $columnConf[$key]['isNull']=='NO')$this->getJSON("Falta completar el campo $key");
				// TIPO DE DATOS
				$columnType=$columnConf[$key]['type'];
				if($key==$serial) $aux.="$key=$val";
				elseif(in_array($columnType,$dataTypeColumns['number']))$str.="$key=$val,";
				elseif(in_array($columnType,$dataTypeColumns['boolean'])){
					if($val===1)$str.= "$key=true,";
					elseif($val===0)$str.= "$key=false,";
					else $str.="$key='$val',";
				}elseif(in_array($columnType,$dataTypeColumns['date']))$str.="$key='".date_format(date_create($val),'Y-m-d')."',";
				elseif(in_array($columnType,$dataTypeColumns['dateTime']))$str.="$key='".date_format(date_create($val),'Y-m-d H:i:s')."',";
				else {
					$val=str_replace("'","&#39;",$val);
					$str.="$key='$val',";
				}
			}
		}	return trim($str, ",").$aux;
	}
	// Método: Prepara la sentencia sql: DELETE
	public function getSQLDelete($tb,$where=''){
		// Obtener los verdaderos datos de la tabla
		extract($this->getParams($tb));
		$str="DELETE FROM $table_schema.$table_name $where";
		return $str;
	}
	// Método:Devuelve un mensaje de no haber ddeclaro la consulta de acceso y cancela cualquier proceso pendiente
	protected function noConsulta($get=''){
		if($get=='') $this->getJSON($this->setJSON('Sentencia no declarada'));
		else $this->getJSON($this->setJSON('Error: Usted no cuenta con los permisos para acceder a la bade de datos!'));
	}
	// Parse tb -> vw
	public function decodeViewSQL($tb){
		$decode=$this->getConfig('decodeView');
		return (isset($decode[$tb]))?$decode[$tb]:$tb;
	}
	// Buscar en listado de tablas - antes agregando sufijo
	public function decodeTable($tb){
		$decode=$this->entityModel['tbDecode'];
		return (isset($decode[$tb]))?$decode[$tb]:$tb;
	}
	
}