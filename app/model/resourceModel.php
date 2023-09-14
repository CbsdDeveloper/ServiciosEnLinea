<?php namespace model;
use api as app;

class resourceModel extends app\controller {

	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * CONSULTAR PROJECTOS POR CÓDIGO
	 */
	private function requestProjectByCode($code){
		// MENSAJE POR DEFECTO
		$json=$this->setJSON("Proyecto no encontrado");
		// VALIDAR QUE SEA UN CÓDIGO VÁLIDO
		if(strlen($code)>=3){
			// LISTADO DE ENTIDADES A BUSCAR
			$entities=$this->getConfig('searchEntity');
			// LISTADO DE PROYECTOS A BUSCAR
			$projects=$this->getConfig('nextCodeProject');
			// VERIICAR POR PROYECTOS 
			foreach(array_merge($entities,$projects) as $key=>$val){
				// VALIDAR SI EL PROYECTO CUENTA CON CON ATRIBUTO PARA EL CÓDIGO
				if(isset($val['codeField'])){
					// OBTENER PARÁMETROS DE TABLE
					$attr=$this->getParams($key);
					// CUSTOM TB ID
					$tb=$this->db->setCustomTable($key,"{$attr['serial']} as id");
					// VERIFICAR SI REQUIERE UNA VISTA
					if(isset($val['requireView'])) $tb['table_name']=$val['requireView'];
					// GENERAR SQL PARA CONSULTA
					$str=$this->db->selectFromView($tb,"WHERE UPPER({$val['codeField']})=UPPER('$code')");
					// CONSULTAR SI EXISTE PROYECTO
					if($this->db->numRows($str)>0){
						// OBTENER PROYECTO
						return $this->setJSON("Ok",true,array_merge(array('entity'=>$key),$this->db->findOne($str)));
					}
				}
			}
		} 
		// RETORNAR DATOS
		return $json;
	}

	/*
	 * CONTROLADOR DE RECURSOS
	 */
	public function requestResources(){
		// VALIDACIÓN DE CONSULTAS - CUADRO BUSCAR
		if(isset($this->post['projectCode']) && !empty($this->post['projectCode'])){
			$this->getJSON($this->requestProjectByCode($this->post['projectCode']));
		}
		// CONSULTAR POR ENTIDADES
		if(isset($this->post['entity'])){
			if($this->post['entity']=='estaciones') $json=$this->getStationsList();
			elseif($this->post['entity']=='entityById') $json=$this->db->viewById($this->post['entityId'],$this->post['entityName']);
			elseif($this->post['entity']=='platoons') $json=$this->getPlantoonsList();
		}elseif(isset($this->post['mod'])){
			if($this->post['list']=='activities') $json=$this->getActivitiesList();
			elseif($this->post['list']=='slides') $json=$this->getSlidesList($this->post['mod']);
		}elseif(isset($this->post['type'])){
			if($this->post['type']=='countries') $json=$this->getCountries();
			elseif($this->post['type']=='states') $json=$this->getStates($this->post['country']=63);
			elseif($this->post['type']=='towns') $json=$this->getTowns($this->post['state']);
			elseif($this->post['type']=='parishes') $json=$this->getParishes($this->post['town']);
			elseif($this->post['type']=='workdays') $json=$this->getWorkDays();
			elseif($this->post['type']=='leaderships') $json=$this->getLeaderShips();
			elseif($this->post['type']=='jobs') $json=$this->getJobs();
			elseif($this->post['type']=='params') $json=$this->getParamsModule($this->post['module']);
			elseif($this->post['type']=='brands') $json=$this->getBrands();
			elseif($this->post['type']=='institutionalcodes') $json=$this->getInstitutionalCodes($this->post['option']);
			elseif($this->post['type']=='transportType') $json=$this->getTransportType();
			elseif($this->post['type']=='projectCode') $json=$this->getTransportType();
			elseif($this->post['type']=='driverslicenses') $json=$this->db->findAll($this->db->selectFromView('licenciasdeconducir'));
			elseif($this->post['type']=='profiles') $json=$this->getProfiles();
			elseif($this->post['type']=='staff') $json=$this->getStaff();
		}else{
			$this->getJSON('Definir el recurso a consumir');
		}
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON('Ok',true,$json));
	}
	
	/*
	 * LISTADO DE PERSONAL
	 */
	private function getStaff(){
		return $this->db->findAll($this->db->selectFromView('vw_personal',"WHERE ppersonal_estado='EN FUNCIONES' ORDER BY personal_nombre"));
	}
	
	/*
	 * LISTADO DE ESTACIONES
	 */
	private function getStationsList(){
		return $this->db->findAll($this->db->selectFromView('estaciones',"WHERE estacion_estado='ACTIVO' ORDER BY estacion_nombre"));
	}
	
	/*
	 * LISTADO DE PELOTONES
	 */
	private function getPlantoonsList(){
		$data=array(
			'stations'=>array(),
			'platoons'=>array()
		);
		foreach($this->db->findAll($this->db->selectFromView('estaciones')) as $v){
			$data['stations'][]=$v;
			$data['platoons'][$v['estacion_id']]=$this->db->findAll($this->db->selectFromView('pelotones',"WHERE fk_estacion_id={$v['estacion_id']}"));
		}
		return $data;
	}
	
	/*
	 * LISTADO DE ACTIVIDIDADES COMERCIALES
	 */
	private function getActivitiesList(){
		return $this->db->findAll($this->db->selectFromView('actividades',"WHERE actividad_estado='Activo'"));
	}
	
	/*
	 * LISTADO DE SLIDES CLASIFICADAS POR MODULO
	 */
	private function getSlidesList($mod){
		return $this->db->findAll($this->db->selectFromView('slides',"WHERE slide_estado='ACTIVO' AND UPPER(slide_modulo) IN (UPPER('{$mod}'),'MAIN') ORDER BY slide_id"));
	}
	
	/*
	 * REQUIERE: modalUsuarios
	 * FUNCION: LISTADO DE PERFILES DE USUARIO
	 */
	private function getProfiles(){
		return $this->db->findAll($this->db->selectFromView("perfiles","WHERE perfil_estado='{$this->varGlobal['STATUS_ACTIVE']}' AND perfil_id>1 ORDER BY perfil_nombre"));
	}
	
	/*
	 * LISTADO DE JORNADAS DE TRABAJO 
	 */
	private function getWorkDays(){
		return $this->db->findAll($this->db->selectFromView('jornadas_trabajo',"WHERE jornada_estado='ACTIVO' ORDER BY jornada_nombre"));
	}
	
	/*
	 * LISTA DE DIRECCIONES O JEFATURAS
	 */
	private function getLeaderShips(){
		return $this->db->findAll($this->db->selectFromView('direcciones',"WHERE direccion_estado='ACTIVO' ORDER BY direccion_nombre"));
	}
	
	/*
	 * LISTA DE PUESTOS CLASIFICADAS POR DIRECCIONES
	 */
	private function getJobs(){
		// MODELO DE PUESTOS
		$model=array(
			'leaderships'=>$this->getLeaderShips(),
			'jobs'=>array()
		);
		// RECORRER LISTADO DE DIRECCIONES
		foreach($model['leaderships'] as $k=>$v){
			$model['jobs'][$v['direccion_id']]=$this->db->findAll($this->db->selectFromView('puestos',"WHERE fk_direccion_id={$v['direccion_id']} AND puesto_estado='ACTIVO'"));
		}
		// RETORNAR MODELO 
		return $model;
	}
	
	/*
	 * LISTADO DE TIPOS DE VEHICULOS
	 */
	private function getTransportType(){
		return explode(',',$this->varGlobal['VEHICLES_TYPE_LIST']);
	}
	
	/*
	 * LISTADO DE CÓDIGOS INSTITUCIONALES
	 */
	private function getInstitutionalCodes($option){
		$type=array('tracking'=>'codigo_flota','part'=>'codigo_parte');
		return $this->db->findAll($this->db->selectFromView($this->db->setCustomTable('codigosinstitucionales',"*,codigo_clave||' '||codigo_detalle codigo"),"WHERE {$type[$option]}='SI' ORDER BY codigo_clave"));
	}
	
	/*
	 * LISTADO DE MARCAS DE VEHÍCULOS
	 */
	private function getBrands(){
		return $this->db->findAll($this->db->selectFromView('brands',"ORDER BY brand_title"));
	}
	
	/*
	 * LISTADO DE PAISES
	 */
	private function getCountries(){
		return $this->db->findAll($this->db->getSQLSelect('countries'));
	}
	
	/*
	 * LISTADO DE PROVINCIAS/ESTADOS
	 */
	private function getStates($countryId=63){
		return $this->db->findAll($this->db->selectFromView('states',"WHERE fk_country_id={$countryId}"));
	}
	
	/*
	 * LISTADO DE CANTONES
	 */
	private function getTowns($stateId){
		return $this->db->findAll($this->db->selectFromView('towns',"WHERE fk_state_id={$stateId}"));
	}
	
	/*
	 * LISTADO DE PARROQUIAS
	 */
	private function getParishes($townId){
		return $this->db->findAll($this->db->selectFromView('parishes',"WHERE fk_town_id={$townId}"));
	}
	
	
}