<?php namespace model\Tthh;
use model as mdl;

class driverModel extends mdl\personModel {
	
	private $entity='conductores';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * FILTRAR PERSONAL
	 */
	private function uiSelect(){
		// MODELO DE DATOS
		$aux=array();
		// BANDERA PARA ACEPTAR LICENCIAS CADUCADAS
		$flag=(isset($this->varGlobal['ALLOW_EXPIRED_LICENSES']) && $this->varGlobal['ALLOW_EXPIRED_LICENSES']=='SI')?"":"AND conductor_estado='VIGENTE'";
		// RETORNAR DATOS
		return $this->db->findAll($this->db->selectFromView("vw_{$this->entity}","WHERE personal_estado='EN FUNCIONES' {$flag} ORDER BY personal_nombre, licencia_categoria"));
		
		// LISTADO DE PERSONAL
		foreach($this->db->findAll($this->db->selectFromView("vw_{$this->entity}","WHERE personal_estado='EN FUNCIONES' {$flag} ORDER BY personal_nombre, licencia_categoria")) as $k=>$v){
			// REGISTRAR ID DE PERSONAL
			if(!isset($aux[$v['fk_personal_id']])){
				$aux[$v['fk_personal_id']]=$v;
				$aux[$v['fk_personal_id']]['licencias']="{$v['licencia_categoria']}";
			}else{
				// REGISTRAR TIPOS DE LICENCIAS
				$aux[$v['fk_personal_id']]['licencias'].=" - {$v['licencia_categoria']}";
			}
		}
		// PARSE MODEL
		foreach($aux as $k=>$v){$data[]=$v;}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTRO POR PERSONAL
	 */
	private function requestByPersonal($info,$type){
		// INFORMACIÓN DE PERSONAL
		if($type=='id') $data=$this->db->viewById($info,'personal');
		else{
			// STRING PARA CONSULTAR REGISTRO
			$str=$this->db->selectFromView('vw_personal',"WHERE persona_doc_identidad='{$info}'");
			// VALIDAR SI EXISTE PERSONAL
			if($this->db->numRows($str)<1) $this->getJSON("No se ha encontrado ningún registro relacionado con <b>$info</b>!");
			// DATOS DE PERSONAL
			$data=$this->db->findOne($str);
		}
		// RELACION CON PERSONAL
		$data['edit']=false;
		$data['fk_personal_id']=$data['personal_id'];
		// MODAL DE APERTURA
		$data['modal']='Conductores';
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestDrivers(){
		// VERIFICAR
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// BUSCAR POR ID DE PERSONAL
		elseif(isset($this->post['personalId'])) $json=$this->requestByPersonal($this->post['personalId'],'id');
		// BUSCAR POR CEDULA DE PERSONAL
		elseif(isset($this->post['code'])) $json=$this->requestByPersonal($this->post['code'],'cc');
		// BUSCAR POR PARAMETROS - UI-SELECT
		elseif(isset($this->post['uiSelect'])) $json=$this->uiSelect();
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}

}