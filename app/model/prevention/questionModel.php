<?php namespace model\prevention;
use api as app;

class questionModel extends app\controller {
	
	private $entity='questions';
	private $profilesList=array(6,9);
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * LISTADO DE PREGUNTAS CLASIFICADAS POR TIPO
	 */
	public function requestQuestions(){
		$orderByType=$this->getConfig(true,'prevention')['evaluationParams'];
		// LISTADO DE PREGUNTAS
		$i=1;
		foreach($orderByType as $type){
			$rows=$this->db->findAll($this->db->getSQLSelect($this->entity,[],"WHERE pregunta_estado='ACTIVO' AND pregunta_tipo='$type'"));
			// ORDENAMIENTO EN MODELO JSON
			foreach($rows as $k=>$v){
				$aux[$type][]=array_merge(array('index'=>$i),$v);
				$i++;
			}
		}	$this->getJSON($this->setJSON("OK",true,$aux));
	}
	
}