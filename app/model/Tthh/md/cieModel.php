<?php namespace model\Tthh\md;
use api as app;

class cieModel extends app\controller {
	
	private $entity='cie';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * LISTADO DE ACTIVIDADES CIIU
	 * ai: frmLocals.html
	 * system: frmLocals.html
	 */
	public function getCie(){
		// DEFINIR FILTRO POR DEFECTO
		$filter="";
		// EXTRAER VARIABLES DE ENVÍO
		extract($this->getURI());
		// GENERAR STRING DE CONSULTA Y CONDICION
		$strWhr="WHERE (LOWER(sinacentos(cie_descripcion)) LIKE LOWER(sinacentos('%$filter%')) OR LOWER(sinacentos(cie_codigo)) LIKE LOWER(sinacentos('%$filter%')))";
		$str=$this->db->selectFromView($this->db->setCustomTable('cie',"cie_id,cie_descripcion,cie_codigo"),$strWhr);
		// DEFINIR LIMITE DE RESULTADOS
		$rows=$this->db->findAll("$str LIMIT 50");
		// RETORNAR DATOS
		$this->getJSON(array('data'=>$rows));
	}
	
}