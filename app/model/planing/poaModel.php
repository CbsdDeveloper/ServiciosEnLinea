<?php namespace model\planing;
use api as app;
/**
 * Description of controller
 *
 * @author Lalytto
 * @mail apinango@lalytto.com
 */
class poaModel extends app\controller {

	private $entity='poa';
	private $entityProjects='poa_proyectos';
	
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
	    $this->db->executeTested($this->db->getSQLInsert($aux, 'poa_reformas'));
	}
	
	/*
	 * INGRESO DE REGISTROS
	 */
	public function insertEntity(){
	    
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // GENERAR SENTENCIA DE INGRESO
	    $sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
	    
	    // OBTENER ULTIMO ID DE POA
	    $poaId=$this->db->getLastID($this->entity);
	    
	    // GENERAR REGISTRO DE REFORMAS
	    $this->newReform($poaId,$this->post['poa_periodo_inicio'],'POA INICIAL');
	    
	    // CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
	    $this->getJSON($this->db->closeTransaction($sql));
	    
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
	 * CALCULAR PORCENTAJE
	 */
	private function getPercentages($data){
		// CONSULTAR SUMATORIA
		$total=$data['proyecto_trimestre_i_porcentaje']+$data['proyecto_trimestre_ii_porcentaje']+$data['proyecto_trimestre_iii_porcentaje']+$data['proyecto_trimestre_iv_porcentaje'];
		// VALIDAR SUMATORIA
		if($total<100) $this->getJSON("No se ha completado la totalidad del porcentaje de programaciòn trimestral!");
		
		// GENERAR VALORES DE TRIMESTRES
		$data['proyecto_trimestre_i']=($data['proyecto_trimestre_i_porcentaje']>0)?'SI':'NO';
		$data['proyecto_trimestre_ii']=($data['proyecto_trimestre_ii_porcentaje']>0)?'SI':'NO';
		$data['proyecto_trimestre_iii']=($data['proyecto_trimestre_iii_porcentaje']>0)?'SI':'NO';
		$data['proyecto_trimestre_iv']=($data['proyecto_trimestre_iv_porcentaje']>0)?'SI':'NO';
		
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * CALCULAR VALOR REFORMADO
	 */
	private function getReformedBudget($data){
	    
	    // VALOR POR DEFECTO PARA PRESUPUESTO REFORMADO
	    $data['proyecto_presupuesto_reformado']=0;
	    
	    // VALIDAR ESTADO DE PROYECTO
	    if(!isset($data['proyecto_estado']) || (isset($data['proyecto_estado']) && $data['proyecto_estado']=='INICIAL')){
	        
	        // SI EL PROYECTO ES NUEVO EL PRESUPUESTO TOTAL ES IGUAL AL PRESUPUETO INICIAL
	        $data['proyecto_presupuesto_total']=$data['proyecto_presupuesto'];
	        
	    }elseif(isset($data['proyecto_estado']) && $data['proyecto_estado']=='REFORMADO'){
	        
	        // CALCULAR PRESUPUESTO REFORMADO
	        $data['proyecto_presupuesto_reformado']=($data['proyecto_presupuesto_total'] - $data['proyecto_presupuesto']);
	        
	    }elseif(isset($data['proyecto_estado']) && $data['proyecto_estado']=='ELIMINADO'){
	        
	        // CALCULAR PRESUPUESTO REFORMADO
	        $data['proyecto_presupuesto_reformado']=((-1)*$data['proyecto_presupuesto_total']);
	        // ELIMINAR PRESUPUESTO TOTAL
	        $data['proyecto_presupuesto_total']=0;
	        
	    }
	
    	// RETORNAR CONSULTA
    	return $data;
	}
	
	
	
	/*
	 * REGISTRAR ENTIDADES
	 */
	public function insertProjects(){
	    
	    
	    $this->getJSON($this->post);
	    
	    
		// VALIDAR PORCENTAJES
		$this->post=$this->getPercentages($this->post);
		
		// OBTENER VALORES DE PRESUPUESTO 
		$this->post=$this->getReformedBudget($this->post);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// GENERAR SENTENCIA DE INGRESO
		$str=$this->db->getSQLInsert($this->post,$this->entityProjects);
		
		// EXECUTAR CONSULTA
		$sql=$this->db->executeTested($str);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * ACTUALIZAR ENTIDADES
	 */
	public function updateProjects(){
		// VALIDAR PORCENTAJES
		$this->post=$this->getPercentages($this->post);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// GENERAR SENTENCIA DE INGRESO
		$str=$this->db->getSQLUpdate($this->post,$this->entityProjects);
		
		// EXECUTAR CONSULTA
		$sql=$this->db->executeTested($str);
		
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