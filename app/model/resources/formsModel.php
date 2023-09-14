<?php namespace model\resources;
use api as app;

/**
 * @author Lalytto
 *
 */
class formsModel extends app\controller {
	
	/*
	 * VARIABLES GLOBALES
	 */
	private $entity='formulariosevaluaciones';
	private $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * REGISTRO DE ENTIDADDES
	 */
	public function insertEntity(){
	    // GENERAR CONSULTA SQL
	    $sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
	}
	
	/*
	 * ACTUALIZAR REGISTRO
	 */
	public function updateEntity(){
	    // GENERAR CONSULTA SQL
	    $sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
	}
	
}