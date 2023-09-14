<?php namespace model\admin;
use api as app;

class roleModel extends app\controller {

	private $entity='roles';
	
	public function __construct(){
		parent::__construct();
	}

	/*
	 * LISTADO DE ROLES DE ACCESO AL SISTEMA
	 */
	public function getRoles(){
		// MODELO DE DATOS
		$aux=array(
			'modules'=>$this->db->findAll($this->db->getSQLSelect('modulos')),
			'submodules'=>array()
		);
		
		// LISTADO DE MODULOS Y SUBMODULOS DEL SISTEMA
		$modules=$this->db->findAll($this->db->selectFromView('vw_submodulos'));
		
		$this->getJSON($modules);
		
	}
	
	/*
	 * ACTUALIZAR ROLES DE USUARIOS 
	 */
	public function setRoles($tb='usuario_rol'){
		extract($this->db->getParams($tb));
		extract($this->post);
		$where="WHERE fk_rol_id={$fk_rol_id} AND fk_usuario_id={$fk_usuario_id}";
		$_tb="$table_schema.$table_name";
		$str=($this->db->numRows($this->db->selectFromView($tb,$where))>0)
		?"DELETE FROM {$_tb} {$where}":"INSERT INTO {$_tb}(fk_rol_id,fk_usuario_id)VALUES({$fk_rol_id},{$fk_usuario_id})";
		$this->getJSON($this->db->executeSingle($str));
	}
	
	/*
	 * ACTUALIZAR ROLES DE PERFIL DE USUARIO
	 */
	public function setGroups($tb='groups'){
		// OBTENER DATOS DE FORMULARIO
		extract($this->db->getParams($tb));
		extract($this->post);
		$where="WHERE fk_rol_id={$fk_rol_id} AND fk_perfil_id={$fk_perfil_id}";
		$str=($this->db->numRows($this->db->selectFromView($tb,$where))>0)
		?"DELETE FROM {$table_schema}.{$table_name} {$where}":$this->db->getSQLInsert($this->post,$tb);
		$this->getJSON($this->db->executeSingle($str));
	}
	
	/*
	 * LISTADO DE MODULOS Y SUBMODULOS
	 */
	private function requestSubmodules(){
		// MODELO DE DATOS
		$data=array();
		// LISTADO DE MÓDULOS
		$data['modules']=$this->db->findAll($this->db->getSQLSelect('modulos',[],"ORDER BY modulo_id"));
		// LISTADO DE SUBMODULOS POR MODULOS
		foreach($data['modules'] as $v){
			// VERIFICAR GRUPO DE MODULO
			$data['submodules'][$v['modulo_id']]=$this->db->findAll($this->db->getSQLSelect('submodulos',[],"WHERE fk_modulo_id={$v['modulo_id']} ORDER BY submodulo_nombre"));
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * LISTADO DE ROLES POR MODULOS Y SUBMODULOS
	 */
	private function requestBySubmodules(){
		// MODELO DE DATOS
		$data=array();
		// LISTADO DE SUBMODULOS POR MODULOS
		foreach($this->db->findAll($this->db->getSQLSelect('modulos',[],"ORDER BY modulo_id")) as $val){
			// MODELO AUXILIAR PARA SUBMODULOS
			$aux=array();
			// LISTADO DE SUBMODULOS Y BUSCAR ROLES POR SUBMODULO
			foreach($this->db->findAll($this->db->getSQLSelect('submodulos',[],"WHERE fk_modulo_id={$val['modulo_id']} ORDER BY submodulo_nombre")) as $v){
				// ROLES DE SUBMODULO
				$roles=$this->db->findAll($this->db->getSQLSelect('roles',[],"WHERE fk_submodulo_id={$v['submodulo_id']} ORDER BY rol_id::text"));
				// CARGAR ROLES DEL SUBMODULO
				if(sizeof($roles)>0) $aux[$v['submodulo_nombre']]=$roles;
			}
			// CARGAR SUBMODULO
			if(sizeof($aux)>0){
				$data['modules'][]=$val;
				$data['submodules'][$val['modulo_id']]=$aux;
			}
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * LISTADO DE ROLES POR PERFIL
	 */
	private function requestRolesByProfile($id){
		// MODELO DE DATOS
		$data=array();
		// ROLES EN BASE A MODULOS
		$data['selected']=array();
		$data['resources']=$this->requestBySubmodules();
		// OBTENER LISTADO DE PERMISOS INGRESADOR POR PERFIL
		foreach($this->db->findAll($this->db->getSQLSelect($this->db->setCustomTable('groups','fk_rol_id'),[],"WHERE fk_perfil_id=$id")) as $v){
			$data['selected'][]=$v['fk_rol_id'];
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * LISTADO DE ROLES POR PERFIL
	 */
	private function requestRolesByUser($id){
		// MODELO DE DATOS
		$data=array();
		// ROLES EN BASE A MODULOS
		$data['resources']=$this->requestBySubmodules();
		// OBTENER LISTADO DE PERMISOS INGRESADOR POR PERFIL
		foreach($this->db->findAll($this->db->getSQLSelect($this->db->setCustomTable('usuario_rol','fk_rol_id'),[],"WHERE fk_usuario_id=$id")) as $v){
			$data['selected'][]=$v['fk_rol_id'];
		}
		// RETORNAR DATOS DE CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR ENTIDAD
	 */
	public function requestRole(){
		// OBTENER DATOS DE FORMULARIO
		$post=$this->requestPost();
		// VERIFICAR
		if(isset($post['id'])) $json=$this->db->viewById($post['id'],$this->entity);
		// LISTADO DE SUBMODULOS
		elseif(isset($post['type']) && $post['type']=='submodules') $json=$this->requestSubmodules();
		// BUSCAR ROLES PARA PERFILES
		elseif(isset($post['type']) && $post['type']=='profiles') $json=$this->requestRolesByProfile($post['entityId']);
		// BUSCAR ROLES PARA USUARIOS
		elseif(isset($post['type']) && $post['type']=='users') $json=$this->requestRolesByUser($post['entityId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	

}