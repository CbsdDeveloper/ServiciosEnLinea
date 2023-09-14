<?php namespace service;
use api as app;

class smartQuery extends app\controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $config;
	private $data;
	private $count=0;
	private $reportType=false;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct($JSON2Report=false){
		// CONSTRUCTOR
		parent::__construct();
		// VALIDAR LOS PERMISOS
		$this->config=$this->getConfig($JSON2Report,'smartQuery');
		// TIPOS DE REPORTES
		$this->reportType=$JSON2Report;
		// PREPARAR CONSULTA 
		$this->setData($this->prepareQuery());
		// INSTANCIAR RECORDS
		if(!$JSON2Report) $this->getJSON($this->getData());
	}
	
	/*
	 * ENVIAR DATOS A FRONT-END
	 */
	public function getData(){
		return $this->data;
	}
	
	/*
	 * SETEAR DATOS PARA BACKEND
	 */
	private function setData($data){
		$this->data=$data;
	}
	
	/*
	 * GENERADOR DE CONSULTAS - PARA TABLAS
	 */
	private function prepareQuery(){
		// EXTRAER PARÁMETROS GET
		$uriParams=$this->getURI();
		// VARIABLE PARA FILTRADO
		$filter=(isset($uriParams['filter']))?$uriParams['filter']:'';
		
		// LISTA DE VISTA DE ENTIDADES
		$listTb=array_merge($this->config['list'],$this->config['view_entity']);
		// VALIDAR LISTADO - VISTA DE ENTIDADES
		if(in_array($uriParams['tb'],$listTb)){
			// OBTENER SESION DE PERSONAL
			$sessionId=app\session::get();
			/*
			 * SELECCIÓN POR DEFECTO
			 */
			if(in_array($uriParams['tb'],$this->config['auto_select'])){
			    $relations=(isset($this->config['relations'][$uriParams['tb']]))?$this->config['relations'][$uriParams['tb']]:[];
			    $str=$this->joinAndSearch($uriParams['tb'],$this->db->getSQLSelect($uriParams['tb'],$relations));
			    if(isset($uriParams['fk_tabla']) && isset($uriParams['fk_tabla_id'])) $str.=(($this->count>0)?" AND ":" WHERE ")."{$uriParams['fk_tabla']}={$uriParams['fk_tabla_id']}";
			/*
			 * ADMINISTRADOR
			 */
			}elseif($uriParams['tb']=='access'){
				$str=$this->joinAndSearch("roles",$this->db->getSQLSelect("roles"));
			}elseif($uriParams['tb']=='usuarios'){
				$id=app\session::existe()?app\session::get():1;
				$str=$this->joinAndSearch("vw_{$uriParams['tb']}",$this->db->selectFromView("vw_{$uriParams['tb']}"));
				$str.=(($this->count>0)?" AND ":" WHERE ")."usuario_estado<>'ELIMINADO' AND fk_perfil_id>1";
				if($this->reportType || $id<2)$str.=" AND (usuario_id<>{$id}) ";
			}elseif($uriParams['tb']=='bitacora'){
				if(app\session::existe())$id=app\session::get();
				if(isset($_GET['id']))$id=$_GET['id'];
				$str=$this->joinAndSearch($uriParams['tb'],$this->db->getSQLSelect($uriParams['tb']));
				$str.=(($this->count>0)?" AND ":" WHERE ")."fk_id={$id}";
			/*
			 * PREVENCION
			 */
			}elseif(isset($this->config['view_prevencion'][$uriParams['tb']])){
				// DATOS DE VISTA
			    $info=$this->config['view_prevencion'][$uriParams['tb']];
				// FILTRAR DATOS
				$str=$this->joinAndSearch($info['vw'],$this->db->selectFromView($info['vw']));
				// VALIDAR EL TIPO DE BUSQUEDA
				if(isset($info['relation'])){
					$relation=$info['relation'];
					$strProfile=$this->db->selectFromView($this->db->setCustomTable($relation['entity'],"{$relation['fk']} id"),"WHERE {$relation['profile']}={$sessionId}");
					$strProfile="{$relation['pk']} IN ($strProfile)";
				}else{
					$strProfile="{$info['profile']}={$sessionId}";
				}
				// FILTRO POR RESPONSABLE
				$str.=(($this->count>0)?" AND ":" WHERE ").$strProfile;
			/*
			 * CONSULTAS PARA EXTERNOS
			 */
			}elseif(isset($this->config['view_custom_index'][$uriParams['tb']])){
			    $cfg=$this->config['view_custom_index'][$uriParams['tb']];
			    $str=$this->joinAndSearch($this->config['view_decode'][$uriParams['tb']],$this->db->selectFromView($this->config['view_decode'][$uriParams['tb']]));
				$str.=(($this->count>0)?" AND ":" WHERE ")."{$cfg['fk_entity']}={$sessionId}";
			
			}elseif(in_array($uriParams['tb'],$this->config['view_tthh_person'])){
				$session=$this->db->getById('personal',$sessionId); // OBTENER SESION DE PERSONAL
				$str=$this->joinAndSearch($this->config['view_decode'][$uriParams['tb']],$this->db->selectFromView($this->config['view_decode'][$uriParams['tb']]));
				$str.=(($this->count>0)?" AND ":" WHERE ")."fk_persona_id={$session['fk_persona_id']}";
			}elseif(in_array($uriParams['tb'],$this->config['view_tthh_staff'])){
			    $str=$this->joinAndSearch($this->config['view_decode'][$uriParams['tb']],$this->db->selectFromView($this->config['view_decode'][$uriParams['tb']]));
			    $str.=(($this->count>0)?" AND ":" WHERE ")."fk_personal_id={$sessionId}";
			}elseif(in_array($uriParams['tb'],$this->config['view_tthh_leadership'])){
			    $str=$this->joinAndSearch($this->config['view_decode'][$uriParams['tb']],$this->db->selectFromView($this->config['view_decode'][$uriParams['tb']]));
				if(in_array($uriParams['tb'],['getLeadershipStaff','getLeadershipRoadmap','getLeadershipDailyactivities','getLeadershipAcademictraining','getLeadershipDriverlicenses'])){
					$session=$this->db->findOne($this->db->selectFromView('vw_relations_personal',"WHERE personal_id={$sessionId} AND ppersonal_estado='EN FUNCIONES'"));
					$str.=(($this->count>0)?" AND ":" WHERE ")."direccion_id={$session['direccion_id']}";
				}else $str.=(($this->count>0)?" AND ":" WHERE ")."jf_personal={$sessionId}";
			}elseif(in_array($uriParams['tb'],$this->config['view_tthh_platoon'])){
			    $str=$this->joinAndSearch($this->config['view_decode'][$uriParams['tb']],$this->db->selectFromView($this->config['view_decode'][$uriParams['tb']]));
				$str.=(($this->count>0)?" AND ":" WHERE ")."es_personal={$sessionId}";
			}elseif(in_array($uriParams['tb'],$this->config['view_subjefature_platoon'])){
				// OBTENER ID DE ESTACION
				$staffId=$this->getStaffIdBySession();
				$stationId=$this->db->getSQLSelect($this->db->setCustomTable('personal','fk_estacion_id'),[],"WHERE personal_id={$staffId}");
				// CONSULTA DE ENTIDADES
				$str=$this->joinAndSearch($this->config['view_decode'][$uriParams['tb']],$this->db->selectFromView($this->config['view_decode'][$uriParams['tb']]));
				if($uriParams['tb']=='getTracking') $str.=(($this->count>0)?" AND ":" WHERE ")."flota_salida_estacion IN ({$stationId}) 	OR flota_arribo_estacion IN ({$stationId})";
				else $str.=(($this->count>0)?" AND ":" WHERE ")."estacion_id IN ({$stationId})";
			
			}elseif(in_array($uriParams['tb'],$this->config['view_by_entity'])){
			    $str=$this->joinAndSearch($this->config['view_decode'][$uriParams['tb']],$this->db->selectFromView($this->config['view_decode'][$uriParams['tb']]));
				$str.=(($this->count>0)?" AND ":" WHERE ")."fk_entidad_id={$sessionId}";
			/*
			 * FORMULARIOS - PERMISOS
			 */
			}elseif($uriParams['tb']=='formularios'){
			    $str=$this->db->selectFromView($uriParams['tb'],"WHERE fk_tabla='{$uriParams['fk_tabla']}' AND fk_tabla_id={$uriParams['fk_tabla_id']}");
			}elseif($uriParams['tb']=='frm_has_req'){
			    $str=$this->db->getSQLSelect($uriParams['tb'],['requerimientos'],"WHERE fk_formulario_id={$uriParams['fk_formulario_id']}");
			}elseif($uriParams['tb']=='items'){
				$innerForm=" INNER JOIN permisos.tb_formularios form ON form.formulario_id=items.fk_formulario_id ";
				$innerForm.=" INNER JOIN permisos.tb_requerimientos req ON req.requerimiento_id=preguntas.fk_requerimiento_id ";
				$str=$this->db->getSQLSelect($uriParams['tb'],['preguntas'],"$innerForm WHERE fk_formulario_id={$uriParams['fk_formulario_id']}");
				if(isset($filter) && $filter!='')$str="{$str} AND LOWER(pregunta_nombre) LIKE LOWER('%{$filter}%')";
			}elseif($uriParams['tb']=='threads'){
				$innerForm=" INNER JOIN permisos.tb_formularios form ON form.formulario_id=threads.fk_formulario_id ";
				$innerForm.=" INNER JOIN permisos.tb_requerimientos req ON req.requerimiento_id=preguntas.fk_requerimiento_id ";
				$str=$this->db->getSQLSelect($uriParams['tb'],['preguntas'],"$innerForm WHERE fk_formulario_id={$uriParams['fk_formulario_id']}");
				if(isset($filter) && $filter!='')$str="{$str} AND (LOWER(sinacentos(pregunta_nombre)) LIKE LOWER(sinacentos('%{$filter}%')) OR LOWER(sinacentos(requerimiento_nombre)) LIKE LOWER(sinacentos('%{$filter}%')))";
			/*
			 * CONSULTAS POR VISTAS SQL
			 */
			}elseif(in_array($uriParams['tb'],$this->config['view_entity'])){
				$str=$this->joinAndSearch("vw_{$uriParams['tb']}",$this->db->getSQLSelect("vw_{$uriParams['tb']}"));
				if(isset($uriParams['fk_tabla']) && isset($uriParams['fk_tabla_id'])) $str.=(($this->count>0)?" AND ":" WHERE ")."{$uriParams['fk_tabla']}={$uriParams['fk_tabla_id']}";
			/*
			 * HISTORIAL DE REGISTROS
			 */
			}elseif($uriParams['tb']=='records'){
			    // ENLAZAR A SCHEMA RECORDS
				$this->db->schemaList='record';
				// INFORMACION DE ENTIDAD
				$cfEntity=$this->db->getParams($uriParams['entity']);
				// INGRESAR A VISTA DE REGISTROS
				$str=$this->joinAndSearch("vw_{$uriParams['entity']}",$this->db->selectFromView("vw_{$uriParams['entity']}"));
				// INGRESAR FILTROS
				$str.=(($this->count>0)?" AND ":" WHERE ")."{$cfEntity['serial']}={$uriParams['id']}";
			// CONSULTA NO ENCONTRADA
			}else{
				$this->getJSON(QUERY_UNDEFINED."smartQuery->{$uriParams['tb']}");
			}
			
			// OBTIENE DATOS Y ORDENAMIENTO
			if(strpos($uriParams['order'],'-')!==FALSE) $uriParams['order']=substr($uriParams['order'], 1)." DESC";
			$total=$this->db->numRows($str);
			// ORDENAMIENTO DE CONSULTA
			$str.=" ORDER BY {$uriParams['order']} LIMIT {$uriParams['limit']} OFFSET ".(($uriParams['page']-1)*$uriParams['limit']);
			
			// FETCH DE DATOS
			$row=$this->db->findAll($str);
			$data=array(
				"type"=>"smart_query",
				"str"=>$str,
			    "tb"=>$uriParams['tb'],
				"filter"=>$filter,
			    "order"=>$uriParams['order'],
			    "limit"=>$uriParams['limit'],
			    "page"=>$uriParams['page'],
				"total"=>$total
			);
			// RESET DEFAULT SCHEMA
			$this->db->schemaList='main';
			return array_merge($data, array('data'=>$row));
		}	else return $this->setJSON(QUERY_UNDEFINED."smartQuery->{$uriParams['tb']}");
	}
	
	/*
	 * REALIZAR INNER JOIN DE ACUERDO A PARÁMETRO DE BÚSQUEDA
	 */
	private function joinAndSearch($tb,$str){
		
		// DATOS DE ENTIDAD
		extract($_GET);
		extract($this->db->getParams($tb));
		
		// SI BUSCA POR PARÁMETROS
		if(isset($filter) && $filter!=''){
			
			// JOIN COLUMNS Y CONFIG
			if(isset($this->config['customSearch'][$tb])) $tbColumns=array('columns'=>$this->config['customSearch'][$tb]);
			else $tbColumns=$this->db->getTbColumns($table_name);
			
			// BUSCAR EN COLUMNAS
			foreach($tbColumns['columns'] as $column){
				// Agregar o no WHERE
				if($this->count<1) $str.=" WHERE (";
				$str.=" LOWER(sinacentos({$tb}.{$column}::Text)) LIKE LOWER(sinacentos('%{$filter}%')) OR ";
				$this->count++;
			}
			
			$str=trim($str, " OR ").")";
			
		}
		
		// REPORTES PERSONALIZADOS POR FECHAS O USUARIOS
		return $str;
	}
	
}
