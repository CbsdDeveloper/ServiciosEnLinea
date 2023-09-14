<?php namespace model\subjefature;
use api as app;

class natureModel extends app\controller {

	private $entity='naturalezaincidente';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESO DE NUEVO PARTE
	 */
	public function updateResources(){
		// INICIA TRANSACCIÓN
		$this->db->begin();
		// VALIDAR ENVÍO DE PARÁMETROS
		if(count($this->post['selectedList'])<1) $this->getJSON("No se ha seleccionado ningun código institucional para relacionarlos...");
		// ELIMINAR REGISTRO RELACIONADOS CON NATURALES
		$sql=$this->db->executeTested($this->db->getSQLDelete('naturaleza_claves',"WHERE fk_naturaleza_id={$this->post['naturaleza_id']}"));
		// RECORRER LISTADO DE RECURSOS
		foreach($this->post['selectedList'] as $v){
			// GENERAR MODELO AUXILIAR
			$aux=array(
				'fk_naturaleza_id'=>$this->post['naturaleza_id'],
				'fk_clave_id'=>$v,
			);
			// REGISTRAR CODIGOS INSTITUCIONALES
			$sql=$this->db->executeTested($this->db->getSQLInsert($aux,'naturaleza_claves'));
		}
		// RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
}