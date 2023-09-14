<?php namespace model\logistics\archive;
use api as app;

class edocumentationModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='documentacion_electronica';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	
	
	/*
	 * NUEVO REGISTRO
	 */
	public function updateEntity(){
	    
	    // OBTENER REGISTRO
	    $draft=$this->db->findById($this->post['delectronica_id'],$this->entity);
	    
	    // VALIDAR LOS DESTINATARIOS Y REMITENTE
	    if(!isset($draft['fk_remitente_id']) && $draft['fk_remitente_id']<1){
	        $this->getJSON("Para continuar primero debe seleccionar el campo de la persona quien suscribe el documento.");
	    }
	    
	    // CONTAR DESTINATARIOS
	    if( $this->db->numRows($this->db->selectFromView('documentacion_electronica_destinatarios',"WHERE fk_delectronica_id={$this->post['delectronica_id']}")) < 1 ){
	        $this->getJSON("Para continuar primero debe seleccionar al menos un destinatario para enviar el documento.");
	    }
	    
	    // VALIDAR QUE NO EXISTA OTRO ARCHIVO CON EL MISMO NOMBRE
	    if( $this->db->numRows($this->db->selectFromView($this->entity,"WHERE UPPER(delectronica_nombre)=UPPER('{$this->post['delectronica_nombre']}') AND delectronica_id<>{$this->post['delectronica_id']}")) > 0 ){
	        $this->getJSON("No se puede cargar este documento, al parecer alguien más ha ingresado este documento (nombre de documento duplicado o similar)");
	    }
	    
	    
	    // FOLDER NAME
	    $pathDate=$this->setFormatDate($this->post['delectronica_fecha'],'Y-m');
	    // DIRECCION DE FICHERO
	    $this->post['delectronica_path']="{$pathDate}";
	    
	    // DATOS AUXILIARES
	    $paramUpload=$this->uploaderMng->setParamsToUploadToFolder($this->post['delectronica_path'],$this->post['delectronica_nombre'],'delectronica_file',true);
	    // CARGAR ANEXOS DE MANTENIMIENTO
	    $this->post['delectronica_documento_anexo']=$this->uploaderMng->uploadFileToFolder($this->post,$this->entity,$paramUpload);
	    
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // REGISTRAR UNA COPIA PARA REMITENTE CON FECHA DE ENTREGA
	    
	    
	    
	    // REGISTRAR UNA COPIA PARA CADA DESTINATARIO Y ACTUALIZAR FECHA DE ENTREGA DE DOCUMENTO
	    
	    
	    
	    // OBTENER CORREO DE DESTINATARIOS
	    
	    
	    // ACTUALIZAR FECHA DE REGISTRO DE DOCUMENTO Y ACTUALIZAR ESTADO DE ENTREGADO
	    $this->post['delectronica_estado']='ENTREGADO';
	    $this->post['delectronica_entregado']=$this->getFecha('dateTime');
	    unset($this->post['fk_remitente_id']);
	    
	    // ACTUALIZAR REGISTRO DE DOCUMENTO
	    $json=$this->db->executeTested($this->db->getSQLUpdate($this->post, $this->entity));
	    
	    
	    // CERRAR TRANSACCIÓN Y ENVIAR RESPUESTA A CLIENTE
	    $this->getJSON($this->db->closeTransaction($json));
	    
	    
	}
	
}