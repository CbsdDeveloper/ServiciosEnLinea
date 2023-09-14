<?php namespace model\logistics\archive;
use api as app;

class pserieModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='archivo_series_periodos';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
	 * INGRESAR SERIES EN PERIODOS
	 */
	private function setSeries($periodId,$seriesList){
	    
	    // ESTABLECER CONTADORES
	    $count=array(
	        'deleted'=>0,
	        'added'=>0,
	        'na'=>0,
	        'updated'=>0
	    );
	    
	    // ELIMINAR REGISTROS SIN 
	    $count['deleted']=$this->db->numRows($this->db->selectFromView($this->entity,"WHERE fk_periodo_id={$periodId}"));
	    $this->db->executeTested($this->db->getSQLDelete($this->entity,"WHERE fk_periodo_id={$periodId}"));
	    
	    // INFORMACION DE PERIODO
	    $period=$this->db->findById($periodId,'archivo_periodos');
	    
	    // RECORRER LISTADO DE SERIES
	    foreach($seriesList as $k=>$v){
	        
	        // CONSULTAR SI REGISTRO ES => NA
	        if($v['pserie_estado']=='NA'){
	            $count['na']+=1;
	            continue;
	        }
	        
	        // $str=$this->db->selectFromView($this->entity,"WHERE fk_serie_id={$k} AND fk_periodo_id={$periodId}");
	        
	        // GENERAR MODELO TEMPORAL
	        $v['pserie_inicio']=$period['periodo_inicio'];
	        $v['pserie_cierre']=$period['periodo_cierre'];
	        
	        $v['fk_periodo_id']=$periodId;
	        $v['fk_serie_id']=$k;
	        $v['fk_responsable_id']=$this->getStaffIdBySession();
	        
	        // GENERAR SENTENCIA DE REGISTRO
	        $str=$this->db->getSQLInsert($v,$this->entity);
	        // EJECUTR SENTENCIA
	        $this->db->executeTested($str);
	        
	        // CONTADOR DE SENTENCIA
	        $count['added']+=1;
	        
	    }
	    
	    // RETORNAR CONSULTA
	    return $count;
	    
	}
	
	/*
	 * NUEVO REGISTRO
	 */
	public function insertEntity(){
	    
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // CREAR BUCLE
	    $count=$this->setSeries($this->post['periodId'], $this->post['model']);
	    $msg="¡Operción completa!<br>Resumen de la operación: {$count['added']} registros ingresados, {$count['deleted']} registros eliminados, {$count['updated']} registros modificados, {$count['na']} registros ignorados";
	    
	    // GENERAR MENSAJE DE RETORNO
	    $sql=$this->setJSON($msg,true);
	    
	    
	    // CERRAR TRANSACCIÓN Y RETORNAR MENSAJE
	    $this->getJSON($this->db->closeTransaction($sql));
	    
	}
	
}