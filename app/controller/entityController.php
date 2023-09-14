<?php namespace model;
use api as app;

class entityController extends app\controller {

	// Parámetros para consultas
	private $config;
	private $data;
	private $count=0;
	private $entityRelation;
	
	// Método 1: Constructor
	public function __construct(){
		parent::__construct();
		$this->config=$this->getConfig(true,'smartQuery');
		$this->getJSON($this->prepareQuery());
	}
	// OBTENER PARÁMETROS GENERALES DE ORDENAMIENTO
	private function getConfigDefault($tb){
		$order=array('order'=>isset($this->config['defaultOrder'][$tb])?
				$this->config['defaultOrder'][$tb]:$this->entityModel['tbConfig'][$tb]['serial']);
		return array_merge($this->config['defaultPagination'],$order);
	}
	// REALIZAR INNER JOIN DE ACUERDO A PARÁMETRO DE BÚSQUEDA
	private function joinAndSearch($tb,$str){
		extract($_GET);
		extract($this->getParams($tb));
		$campoAttr[$tb]=$this->getTbParams($tb);
		foreach($this->entityRelation as $val){$campoAttr[$val]=$this->getTbParams($val);}
		// SET FILTER
		if(isset($filter) && $filter!=''){
			foreach($campoAttr as $key=>$val){
				foreach($val as $column=>$type){
					if($column!='columns'){
						if($this->count<1) $str.=" WHERE (";
						if($column!=$serial){
							if($type=='text' || $type=='char') $str.=" LOWER($key.$column) LIKE LOWER('%$filter%') OR ";
							elseif($type=='boolean'){}
							else $str.=" LOWER($key.$column) LIKE LOWER('%$filter%') OR ";
						}	$this->count++;
					}
				}
			} $str=trim($str, " OR ").")";
		}	return $str;
	}
	// GENERADOR DE CONSULTAS - PARA ENTIDADES
	private function prepareQuery(){
		extract($this->getURI());
		if(isset($this->config['parseTb'][$tb]) || isset($this->config['parseView'][$tb]) || in_array($tb,$this->config['list'])){
			//  GET RELATIONS
			$this->entityRelation=(isset($this->config['relations'][$tb]))?$this->config['relations'][$tb]:$this->entityModel['tbConfig'][$tb]['fkList'];
			// EMPIEZA GENERACIÓN DE CONSULTA
			if($tb=='usuarios'){}
			elseif(in_array($tb,$this->config['autoSelect'])){// CONSULTA PARA ENTIDADES SIMPLES
				$str=$this->joinAndSearch($tb,$this->db->getSQLSelect($tb,$this->entityRelation));
			}
			// EXTRAER PARÁMETROS POR DEFECTO DE PAGINACIÓN
			if(!isset($order))extract($this->getConfigDefault($tb));
			// OBTIENE DATOS Y ORDENAMIENTO
			if(strpos($order,'-')!==FALSE)$order=substr($order,1)." DESC";
			// ORDENAMIENTO DE CONSULTA
			//$str.=" ORDER BY $order limit $limit offset ".(($page-1)*$limit);
			$str.=" ORDER BY $order ";
			$total=$this->db->numRows($str,false);
			// FETCH DE DATOS
			$row=$this->db->findAll($str);
			$data=array("type"=>"smart_query",
					"str"=>$str,
					"tb"=>$tb,
					"filter"=>$filter,
					"order"=>$order,
					"limit"=>$limit,
					"page"=>$page,
					"total"=>$total);
			// VERIFICA INGRESO DE DATOS PERSONALIZADOS
			return array_merge($data, array('data'=>$row));
		}	else return $this->setJSON(QUERY_UNDEFINED."smartQuery->$tb");
	}

}