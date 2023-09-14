<?php namespace model\permits;
use api as app;
use controller as ctrl;

class ciiuModel extends app\controller {
	
	private $entity='ciiu';
	private $json;
	private $tasas;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * METODO PARA SUBIR ARCHIVO FUENTE DE CIIU [XLS]
	 */
	public function uploadCiiu(){
		// VALIDAR EXISTENCIA DE TASAS DE LA ACTIVIDAD SELECCIONADA
		$str=$this->db->getSQLSelect('tasas',[],"WHERE fk_actividad_id={$this->post['fk_actividad_id']}");
		if($this->db->numRows($str)<1)$this->getJSON("Para realizar esta acción primero debe ingresas las tasas correspondientes a la presente actividad");
		$this->tasas=$this->db->findAll($str);
		// SUBIR ARCHIVO
		$config=$this->uploaderMng->config['entityConfig']['ciiu'];
		$sql=$this->uploaderMng->uploadFile($config);
		if(!$sql['estado']) $this->getJSON($sql);
		$imgNew=$sql['mensaje'];
		$pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$imgNew['ciiu_name']}";
		// Comprobar si existe archivo y leer
		if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
		// Procesar excel
		return $this->processCiiu($pathFile);
	}
	
	/*
	 * PROCESAR DATOS DE [XLS]
	 */
	private function processCiiu($pathFile){
		$tb='ciiu';
		// Instancia de librería y lectura de archivo
		$myExcel=new ctrl\xlsController();
		$data=$myExcel->getCellValue($this->post,$pathFile,$tb);
		// LISTA DE TASAS
		$tasas=array();
		foreach($this->tasas as $key=>$val){$tasas[$val['tasa_codigo']]=$val['tasa_id'];}	
		// Empezar transacción
		$succes=0;
		$insert=0;
		$update=0;
		$this->db->begin();
			foreach($data as $key=>$val){
				// VALIDAR CORRESPONDENCIA CON ID DE TASA
				extract($val);
				if(isset($tasas[$fk_tasa_id])){
					$val=array_merge($val,array('fk_tasa_id'=>$tasas[$fk_tasa_id]));
					$str=$this->db->getSQLSelect($tb,[],"WHERE ciiu_codigo='$ciiu_codigo'");
					if($this->db->numRows($str)>0){
						$row=$this->fetch_str($str);
						$str2=$this->db->getSQLUpdate(array_merge($row,$val),$tb);
						$update++;
					} else {
						$str2=$this->db->getSQLInsert($val,$tb);
						$insert++;
					}	$sql=$this->db->executeSingle($str2);
					if(!$sql['estado']){
						$sql['mensaje'].="<br>Error provocado en registro con código -> $ciiu_codigo";
						return $this->db->closeTransaction($sql);
					}
				}else{
					$sql['mensaje'].="<br>Error provocado en registro con código -> $ciiu_codigo, el código de tasa <b>$fk_tasa_id</b> no corresponde a los del sistema";
					return $this->db->closeTransaction($sql);
				}
			}
		if($sql['estado']) $sql['mensaje'].="<br>Datos ingresados: $insert  <br>Datos actualizados: $update";
		return $this->db->closeTransaction($sql);
	}
	
	/*
	 * LISTADO DE ACTIVIDADES CIIU
	 * ai: frmLocals.html
	 * system: frmLocals.html
	 */
	public function getCiiu(){
		// DEFINIR FILTRO POR DEFECTO
		$filter="";
		// EXTRAER VARIABLES DE ENVÍO
		extract($this->getURI());
		// GENERAR STRING DE CONSULTA Y CONDICION
		$strWhr="WHERE (LOWER(sinacentos(ciiu_nombre)) LIKE LOWER(sinacentos('%$filter%')) OR LOWER(sinacentos(ciiu_codigo)) LIKE LOWER(sinacentos('%$filter%')))";
		$str=$this->db->selectFromView($this->db->setCustomTable('vw_ciiu',"fk_actividad_id actividad_id,fk_tasa_id tasa_id,ciiu_id,ciiu_nombre,ciiu_codigo"),$strWhr);
		// CONSICIONAR CON TASAS PRESUPUESTARIAS
		// if(isset($fkParent))$str="$str AND fk_tasa_id=$fkParent"; ACTIVAR EN TASAS
		if(isset($fkParent))$str="$str AND fk_actividad_id=$fkParent";
		// DEFINIR LIMITE DE RESULTADOS
		$rows=$this->db->findAll("$str LIMIT 25");
		// RETORNAR DATOS
		$this->getJSON(array('data'=>$rows));
	}
	
	/*
	 * CONSULTAR REGISTRO
	 * + showing: detalle de presentación
	 */
	public function requestCiiu(){
		// CONSULTAR POR ID
		if(isset($post['id'])) $json=$this->db->viewById($post['id'],$this->entity);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("ok",true,$json));
	}
	
}