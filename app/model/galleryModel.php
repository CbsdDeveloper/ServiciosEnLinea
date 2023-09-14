<?php namespace model;
use api as app;

class galleryModel extends app\controller {
	
	private $entity='gallery';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * REGISTRAR NUEVO ARCHIVO
	 */
	public function insertGallery(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// VALIDAR Y CARGAR ARCHIVO
		$post=$this->uploaderMng->uploadFileService($post,$this->entity);
		// INSERTAR EN BASE DE DATOS
		$sql=$this->db->executeSingle($this->db->getSQLInsert($post,$this->entity));
		// RETORNAR DATOS
		$this->getJSON($sql);
	}
	
	/*
	 * ACTUALIZAR REGISTRO
	 */
	public function updateGallery(){
		// RECIBIR DATOS DE FORMULARIO
		$post=$this->requestPost();
		// VALIDAR Y CARGAR ARCHIVO
		$post=$this->uploaderMng->uploadFileService($post,$this->entity,false);
		// INSERTAR EN BASE DE DATOS
		$sql=$this->db->executeSingle($this->db->getSQLUpdate($post,$this->entity));
		// RETORNAR DATOS
		$this->getJSON($sql);
	}
	
	/*
	 * REGISTRAR MULTIPLES ARCHIVOS
	 */
	public function insertFiles(){
		// VALIDAR Y CARGAR ARCHIVO
		$list=$this->uploaderMng->uploadFolderService($this->post,$this->post['fk_table']);
		// COMENZAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR REGISTROS DE ARCHIVOS
		foreach($list as $file){
			// MODELO DE INGRESO
			$aux=array(
				'fk_table'=>$this->post['fk_table'],
				'fk_id'=>$this->post['fk_id'],
				'media_nombre'=>$file
			);
			// REGISTRAR ARCHIVOS
			$sql=$this->db->executeTested($this->db->getSQLInsert($aux,$this->entity));
		}
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * CARGAR ARCHIVOS PERSONALIZADOS
	 */
	public function uploadAttachments(){
		// CARGAR ARCHIVO Y OBTENER NOMBRE PARA SU REGISTRO
		$file=$this->uploaderMng->uploadSingleRequiredFile($this->post,$this->post['fk_table']);
		
		// DATOS DE ACTUALIZACION
		$params=$this->db->getParams($this->post['fk_table']);
		
		// GENERAR MODELO DE CARGA
		$model=array();
		// INSERTAR NOMBRE DE ARCHIVO CON ATRIBUTO
		$model[$this->post['object']]=$file['mensaje'];
		// INSERTAR PK DE ENTIDAD
		$model[$params['serial']]=$this->post['fk_id'];
		
		// GENERAR STRING DE ACTUALIZACION/INGRESO DE ARCHIVO
		$str=$this->db->getSQLUpdate($model,$this->post['fk_table']);
		// EJECUTAR CONSULTA
		$sql=$this->db->executeTested($str);
		// ANEXAR MENSAJE COMPLEMENTARIO
		$sql['mensaje'].="<br><br>Favor refrescar/recargar/actualizar la pestaña del navegador para observar los cambios.";
		// RETORNAR CONSULTA
		$this->getJSON($sql);
	}
	
	/*
	 * DESCARGAR ARCHIVOS ADJUNTOS
	 */
	public function downloadFiles(){
		// RECIBIR DATOS DE FORMULARIO
		$get=$this->requestGet();
		// NOMBRE DE ENTIDAD
		if(isset($this->uploaderMng->config['decodeView'][$get['entityName']])) $get['entityName']=$this->uploaderMng->config['decodeView'][$get['entityName']];
		// LISTA DE ARCHIVOS
		$files=array();
		// LISTA DE ARCHIVOS A DESCARGAR
		foreach($this->db->findAll($this->db->selectFromView($this->entity,"WHERE fk_table='{$get['entityName']}' AND fk_id={$get['entityId']}")) as $file){
			// LISTAR ARCHIVOS PARA DESCARGAR
			$files[]=$file['media_nombre'];
		}
		// DATOS DE ENTIDAD
		$entity=$this->db->findById($get['entityId'],$get['entityName']);
		// DESCARGAR ARCHIVOS
		$this->uploaderMng->downloadFolder($files,$entity,$get['entityName']);
	}
	
}