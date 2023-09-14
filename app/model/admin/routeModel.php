<?php namespace model\admin;
use api as app;

class routeModel extends app\controller {
	
	private $entity='rutas';
	
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
		foreach($this->db->findAll($this->db->getSQLSelect("vw_{$this->entity}",[],"WHERE ruta_estado='{$this->varGlobal['STATUS_ACTIVE']}' ORDER BY submodulo_nombre")) as $key=>$val){
			$data['url'][]=$val['ruta_url'];
			$data['permit'][$val['ruta_url']]="permiso{$val['ruta_permiso']}";
		}
		// NOMBRE DEL ARCHIVO
		$routes=SRC_PATH."js/routes.txt";
		// CREAR ARCHIVO DE CONFIGURACIÓN - 
		$fh=fopen($routes,'w');
		if($fh){
			$dataTxt="var privateURI=".json_encode($data['url'])."; var rutasPrivadas=".json_encode($data['permit']);
			fwrite($fh, $dataTxt);
			fclose($fh);
		}
		// UNIR DATOS DE VARIABLES
		$font=file_get_contents(SRC_PATH."js/fontawesome.txt");
		$labels=file_get_contents(SRC_PATH."js/labels.txt");
		$routes=file_get_contents($routes);
		// MERGE VARIABLES
		$fh=fopen(SRC_PATH."js/lang-app.js",'w');
		if($fh){
			$dataTxt="$font;$labels;$routes;";
			fwrite($fh, $dataTxt);
			fclose($fh);
		}
		// DATOS DE RETORNO
		$this->getJSON($this->setJSON("Archivo de configuración actualizado con éxito..!",true,$data));
	}
	
}