<?php namespace model\logistics\archive;
use api as app;

class serieModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='archivo_seriesdocumentales';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
	 * NUEVO REGISTRO
	 */
	public function insertGallery(){
	    // RECIBIR DATOS DE FORMULARIO
	    $post=$this->requestPost();
	    
	    
	    
	    $this->getJSON($post);
	    
	}
	
}