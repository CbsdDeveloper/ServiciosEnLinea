<?php namespace model\Tthh;
use api as app;

class regulationModel extends app\controller {
	
	private $entity='reglamentos';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * CONSULTAR TÍTULOS Y CAPÍTULOS DE REGLAMENTOS
	 */
	private function requestChapters(){
		
		
		// MODELO DE DATOS
		$data=array(
			'type'=>$this->string2JSON($this->varGlobal['REGULATIONS_TYPE']),
			'titles'=>array(),
			'chapters'=>array(),
		);
		
		
		// RECORRER TIPO DE REGLAMENTOS
		foreach($data['type'] as $val){
			// LISTADO DE TÍTULOS
			$data['titles'][$val]=$this->db->findAll($this->db->selectFromView('secciones_reglamentos',"WHERE seccion_clasificacion='{$val}' AND seccion_tipo='TITULO' ORDER BY seccion_articulo"));
			// GENERAR MODELO
			foreach($data['titles'][$val] as $v){
				// CONSULTAR CAPÍTULOS
				$data['chapters'][$v['seccion_id']]=$this->db->findAll($this->db->selectFromView('secciones_reglamentos',"WHERE seccion_clasificacion='{$val}' AND seccion_tipo='CAPITULO' AND fk_seccion_id={$v['seccion_id']} ORDER BY seccion_articulo"));
			}
		
		}
		
		
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * LISTADO DE ARTÍCULOS
	 */
	private function requestArticles($actionId){
		// MODELO DE CONSULTA
		$data=array(
			'list'=>array(),
			'selected'=>array()
		);
		// CLASIFICACIÓN DE ARTICULOS
		$type=$this->string2JSON($this->varGlobal['REGULATIONS_TYPE']);
		// CLASIFICACIÓN DE ARTÍCULOS
		foreach($type as $v){
			// LISTADO DE ARTÍCULOS
			$data['list'][$v]=$this->db->findAll($this->db->selectFromView($this->entity,"WHERE reglamento_clasificacion='{$v}' ORDER BY reglamento_articulo"));
		}
		// MODELO DE SELECCIÓN
		foreach($this->db->findAll($this->db->selectFromView("tipoaccion_reglamento","WHERE fk_tipoaccion_id={$actionId}")) as $v){
			// LISTADO DE ARTÍCULOS
			$data['selected'][]=$v['fk_reglamento_id'];
		}
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestRegulation(){
		// CONSULTAR POR ID DE REGISTRO
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTAR TITULOS
		elseif(isset($this->post['type'])) $json=$this->requestChapters();
		// CONSULTAR BASE LEGAL
		elseif(isset($this->post['actionId'])) $json=$this->requestArticles($this->post['actionId']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("OK",true,$json));
	}
	
	/*
	 * INGRESO DE RELACIÓN DE BASE LEGAL CON ARTÍCULOS
	 */
	public function setBaseLegal(){
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// ELIMINAR REGISTRO DE BASE ENVIADA
		$sql=$this->db->executeTested($this->db->getSQLDelete("tipoaccion_reglamento","WHERE fk_tipoaccion_id={$this->post['tipoaccion_id']}"));
		// INGRESAR NUEVOS REGISTROS
		foreach($this->post['selected'] as $v){
			// GENERAR MODELO
			$model=array('fk_tipoaccion_id'=>$this->post['tipoaccion_id'],'fk_reglamento_id'=>$v);
			// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
			$sql=$this->db->executeTested($this->db->getSQLInsert($model,"tipoaccion_reglamento"));
		}
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
}