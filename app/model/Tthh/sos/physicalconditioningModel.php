<?php namespace model\Tthh\sos;
use api as app;
use controller as ctrl;

/**
 * @author Lalytto
 *
 */
class vrescueModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='acondicionamientofisico';
	private $config;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * REGISTRO DE CILINDROS LLENADOS
	 */
	private function insertTest($test,$entityId){
	    // MENSAJE POR DEFECTO
	    $sql=$this->setJSON("ok",true);
	    
	    // ELIMINAR REGISTRO DE RESPUESTAS
	    $sql=$this->db->executeTested($this->db->getSQLDelete('rvertical_respuestas',"WHERE fk_registro_id={$entityId}"));
	    
	    // RECORRER LISTADO DE USUARIOS
	    foreach($test as $k=>$v){
	        // REGISTRO DE MODELO
	        $v['fk_registro_id']=$entityId;
	        $v['fk_pregunta_id']=$k;
            // GENERAR STRING DE CONSULTA
	        $str=$this->db->getSQLInsert($v,'rvertical_respuestas');
	        // GENERAR STRING DE INGRESO Y EJECUCIÓN DE CONSULTA
	        $sql=$this->db->executeTested($str);
	    }
	    // RETORNAR CONSULTA
	    return $sql;
	}
	
	/*
	 * INGRESAR NUEVOS REGISTROS
	 */
	public function insertEntity(){
		// INICIAR TRANSACCION
		$this->db->begin();
		// STRING DE INGRESO DE REGISTRO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER IDE DE NUEVO REGISTRO
		$entityId=$this->db->getLastID($this->entity);
		// ACTUALIZAR TANQUES USADOS
		$sql=$this->insertTest($this->post['test'],$entityId);
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
		// ACTUALIZAR TANQUES USADOS
		$sql=$this->insertTest($this->post['test'],$this->post['registro_id']);
		// RETORNAR RESULTADO DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
    public function requestEntity(){}
	
}