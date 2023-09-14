<?php namespace model\planing;
use api as app;
/**
 * Description of controller
 *
 * @author Lalytto
 * @mail apinango@lalytto.com
 */
class reformModel extends app\controller {

	private $entity='poa_reformas';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * OBTENER ULTIMA REFORMA
	 */
	private function newReform($poaId,$date,$description,$reformNumber=1){
	    // GENERAR MODELO TEMPORAL DE REFROAM
	    $aux=array(
	        'fk_poa_id'=>$poaId,
	        'reforma_numero'=>$reformNumber,
	        'reforma_nombre'=>$this->setNumberToOrdinal($reformNumber),
	        'reforma_fecha'=>$date,
	        'reforma_descripcion'=>$description
	    );
	    // SENTENCIA DE SQL
	    $this->db->executeTested($this->db->getSQLInsert($aux, $this->entity));
	}
	
	/*
	 * INGRESO DE REGISTROS
	 */
	public function insertEntity(){
	    
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // FECHA ACTUAL
	    $currentDate=$this->getFecha('dateTime');
	    
	    /*
	     * REGISTRAR NUEVA REFORMA
	     */
	    // GENERAR REGISTRO DE NUEVA REFORMA
	    $this->newReform($this->post['poaId'],$this->post['reforma_fecha'],$this->post['reforma_descripcion'],$this->post['reforma_numero']);
	    // OBTENER ID DE NUEVA REFORMA
	    $reform=$this->db->getLastEntity($this->entity);
	    
	    /*
	     * ACTUALIZAR REFORMA PASADA
	     */
	    // DATOS PARA ACTUALIZAR REFORMA PASADA
	    $closeReform=array(
	        'reforma_id'=>$this->post['reformId'],
	        'reforma_estado'=>'DEROGADA',
	        'reforma_descripcion'=>"ACTUALIZADA EN {$reform['reforma_nombre']}/A REFORMA"
	    );
	    // DAR DE BAJA REFORMA PASADA
	    $this->db->executeTested($this->db->getSQLUpdate($closeReform, $this->entity));
	    
	    /*
	     * RECORRER MODELO PARA CREACION DE NUEVOS PROYECTOS
	     */
	    foreach($this->post['model'] as $id=>$project){
	        
	        // COPIAR MODELO Y ACTAULIZAR ESTADO
	        $tmp=array(
	            'fk_reforma_id'=>$reform['reforma_id'],
	            'proyecto_id'=>$id,
	            'proyecto_estado'=>$project['proyecto_nuevo_estado']
	        );
	        // REGISTRAR ESTADO
	        $this->db->executeTested($this->db->getSQLUpdate($tmp, 'poa_proyectos'));
	        
	    }
	    
	    
	    /*
	     * REGISTRAR REFORMA DE POA
	     */
	    // DATOS PARA ACTUALIZACION DE POA
	    $temp=array(
	        'poa_id'=>$this->post['poaId'],
	        'poa_estado'=>'REFORMADO'
	    );
	    // ACTUALIZAR POA
	    $this->db->executeTested($this->db->getSQLUpdate($temp, 'poa'));
	    
	    
	    // CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
	    $this->getJSON($this->db->closeTransaction($this->setJSON("¡Nueva reforma creada con éxito, por favor, actualice la página para visualizar todos los cambios!",true)));
	    
	}
	
	
	
	/*
	 * ACTUALIZACION DE REGISTROS
	 */
	public function updateEntity(){
	    
	    $this->getJSON($this->post);
	    
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // GENERAR SENTENCIA DE INGRESO
	    $sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
	    
	    // CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
	    $this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR ENTIDADES
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['poaId'])) $json=$this->db->viewById($this->post['poaId'],$this->entity);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
}