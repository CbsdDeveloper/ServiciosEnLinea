<?php namespace model\admin;
use api as app;

class labelModel extends app\controller {
	
	private $entity='labels';
	
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
		// LISTAR LABELS
		foreach($this->db->findAll($this->db->getSQLSelect($this->entity,[],"ORDER BY label_module")) as $key=>$val){
			$data['es'][$val['label_key']]=$val['label_es'];
			$data['en'][$val['label_key']]=$val['label_en'];
		}
		// GENERAR STRING DE LABELS
		$labels=json_encode($data);
		$labels="var appLabels={$labels}";
		
		// ICONOS
		$font=file_get_contents(SRC_PATH."js/fontawesome.txt");
		// VARIABLES DE LOS MODULOS
		$vars=file_get_contents(SRC_PATH."js/vars.js");
		// RUTAS DE ACCESOS A LOS MODULOS
		$routes="var appModulesPrivateRoutes={$this->varGlobal['SYSTEM_CONFIG_ROUTES_MODULES']}";
		
		// MERGE VARIABLES
		$fh=fopen(SRC_PATH."js/lang-app.js",'w');
		if($fh){
			$dataTxt="{$font};{$labels};{$routes};{$vars}";
			fwrite($fh, $dataTxt);
			fclose($fh);
		}
		// DATOS DE RETORNO
		$this->getJSON($this->setJSON("Archivo de configuración actualizado con éxito..!",true));
	}
	
}