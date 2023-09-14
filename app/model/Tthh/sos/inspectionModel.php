<?php namespace model\Tthh\sos;
use api as app;
use controller as ctrl;

/**
 * @author Lalytto
 *
 */
class inspectionModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='inspeccionextintores';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	/*
	 * INGRESAR NUEVOS REGISTROS
	 */
	public function insertEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		// STRING DE INGRESO DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICACIÓN DE REGISTROS
	 */
	public function updateEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		// STRING DE INGRESO DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * CONSULTA DE REGISTROS
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['id'])) $json=$this->db->viewById($this->post['id'],$this->entity);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}