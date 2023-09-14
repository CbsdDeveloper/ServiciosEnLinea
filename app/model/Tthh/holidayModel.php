<?php namespace model\Tthh;
use api as app;

class holidayModel extends app\controller {
	
	private $entity='feriado';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * PROCESAR DATOS
	 */
	public function createFileConfig(){
		$data=array();
		// LISTAR LOS AÑOS INGRESADOR
		$tb=$this->db->setCustomTable($this->entity,"DISTINCT(date_part('year',feriado_fecha)) as year",'tthh');
		foreach($this->db->findAll($this->db->getSQLSelect($tb)) as $val){
			$data[$val['year']]=array();
			// CONSULTAR FECHAS DE CADA AÑO
			foreach($this->db->findAll($this->db->getSQLSelect($this->entity,[],"WHERE date_part('year',feriado_fecha)='{$val['year']}'")) as $v){
				$data[$val['year']][$v['feriado_fecha']]=$v['feriado_nombre'];
			}
		}
		// RECORRER AÑOS Y CONSULTAR FECHAS
		$this->setConfig($data,'holidays');
		$this->getJSON($this->setJSON("Archivo de configuración actualizado con éxito..!",true));
	}
	
	/*
	 * ELIMINAT REGISTRO
	 */
	public function deleteHoliday(){
		extract($this->requestPost());
		$str=$this->db->deleteById($this->entity,$id);
		$this->getJSON($this->db->executeSingle($str));
	} 
	
}