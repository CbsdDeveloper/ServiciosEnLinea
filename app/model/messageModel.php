<?php namespace model;
use controller as ctrl;
use model as mdl;

class messageModel extends mdl\personModel {
    
	/*
	 * DECLARACION DE VARIABLES
	 */
	private $entity='denuncias';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	// LISTA DE DESTINATARIOS
	private function getRecipients(){
		return $this->string2JSON($this->varGlobal['COMPLAINT_RECIPIENTS_LIST']);
	}
	
	// SERIE DE MENSAJE
	private function getNextMessage(){
		// PARÁMETROS DE ENTIDAD
	    $this->conf=$this->db->getParams($this->entity);
		// GENERAR QUERY
		$strWhr="WHERE (date_part('year',mensaje_fecha)=date_part('year',CURRENT_DATE))";
		$str=$this->db->getSQLSelect($this->db->setCustomTable($this->entity,"MAX(mensaje_serie) id",$this->conf['table_schema']),[],$strWhr);
		// EXTRAER INFORMACION DE ROW
		$this->row=$this->db->findOne($str);
		// RETOORNAR ID
		return ( (!isset($this->row['id']) || $this->row['id']==null) ) ? 0 : ($this->row['id']+1);
	}
	
	/*
	 * REGISTRAR NUEVO MENSAJE
	 */
	public function insertMessage(){
		// SUBIR ARCHIVO ADJUNTO
		$this->post=$this->uploaderMng->uploadFileService($this->post,$this->entity,false);
		// VALIDAR TIPO DE MENSAJE
		if($this->post['mensaje_tipo']=='MENSAJE ANONIMO'){
			$this->post['mensaje_email']='apinango@lalytto.com';
			$this->post['mensaje_nombre']='ANONIMO';
			$this->post['mensaje_telefono']='ANONIMO';
		}
		// COMPLETAR PARÁMETROS DE ENVÍO
		$conf=$this->getCurrentPC();
		// INSERTAR PARÁMETROS DE ENVÍO
		$this->post['mensaje_fecha']=$this->getFecha('dateTime');
		$this->post['mensaje_serie']=$this->getNextMessage();
		$this->post['mensaje_ticket']=str_pad($this->post['mensaje_serie'],3,"0",STR_PAD_LEFT);;
		// ENVÍO DE DATO
		$this->post['mensaje_ubicacion']="{$conf['country']} - {$conf['city']}";
		$this->post['mensaje_ip']=$conf['ipaddress'];
		$this->post['mensaje_equipo']=$conf['equipo'];
		$this->post['mensaje_asunto']=strtoupper($this->post['mensaje_asunto']);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// INSERTAR DATO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// ENVIAR CORREO ELECTRÓNICO
		if($sql['estado']){
			// NOTIFICACIÓN MEDIANTE CORREO ELECTRÓNICO
			$mailCtrl=new ctrl\mailController('complaintMessage');
			// LISTA DE DESTINATARIOS DEL CORREO
			$mailCtrl->destinatariosList=$this->getRecipients();
			// DATOS DE PERMISO OCASIONAL
			$mailCtrl->mergeData($this->post);
			// VALIDAR EXISTENCIA DE ARCHIVO ADJUNTOS
			if(isset($this->post['mensaje_adjunto'])) $mailCtrl->attachmentList=array("src/tthh/denuncias/{$this->post['mensaje_adjunto']}");
			// DATOS DE PRESIDENTE
			if(isset($this->post['mensaje_email'])) $mailCtrl->prepareMail($this->post['mensaje_asunto'],$this->post['mensaje_email']);
			else $mailCtrl->prepareMail($this->post['mensaje_asunto']);
		}else{
			// MENSAJE EN CASO DE NO ENVIARSE EL MAIL
			$sql=$this->setJSON("No se ha podido registrar el mensaje");
		}
		// CERRAR TRANSACCIÓN Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	/*
	 * REGISTRAR NUEVO MENSAJE
	 */
	public function insertComplaint(){
	    
		// GENERAR MODELO DE PERSONA
		$personModel=array(
			'persona_tipo_doc'=>'CEDULA',
			'persona_doc_identidad'=>$this->post['idcard'],
			'persona_apellidos'=>$this->post['lastname'],
			'persona_nombres'=>$this->post['name'],
			'persona_telefono'=>$this->post['phone'],
			'persona_celular'=>$this->post['mobile'],
			'persona_correo'=>$this->post['email']
		);
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// REGISTRAR A DENUNCIANTE
		$person=$this->requestPerson($personModel);
		
		// GENERAR MODELO DE REGISTRO
		$this->post['mensaje_asunto']='DENUNCIAS EN LINEA';
		$this->post['mensaje_tipo']='DENUNCIAS EN LINEA';
		$this->post['mensaje_nombre']="{$person['persona_apellidos']} {$person['persona_nombres']}";
		$this->post['mensaje_telefono']="{$person['persona_telefono']} - {$person['persona_celular']}";
		$this->post['mensaje_email']="{$person['persona_correo']}";
		$this->post['mensaje_detalle']=$this->post['message'];
		$this->post['fk_persona_id']=$person['persona_id'];
		
		// COMPLETAR PARÁMETROS DE ENVÍO
		$conf=$this->getCurrentPC();
		// INSERTAR PARÁMETROS DE ENVÍO
		$this->post['mensaje_fecha']=$this->getFecha('dateTime');
		$this->post['mensaje_serie']=$this->getNextMessage();
		$this->post['mensaje_ticket']=str_pad($this->post['mensaje_serie'],3,"0",STR_PAD_LEFT);;
		// ENVÍO DE DATOS
		$this->post['mensaje_ubicacion']="{$conf['country']} - {$conf['city']}";
		$this->post['mensaje_ip']=$conf['ipaddress'];
		$this->post['mensaje_equipo']=$conf['equipo'];
		
		// INSERTAR DATO
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		
		// DATOS DE CORREO
		$mailData=$this->db->getLastEntity($this->entity);
		
		// ENVIAR CORREO ELECTRÓNICO
		$mailCtrl=new ctrl\mailController('complaintMessage');
		// LISTA DE DESTINATARIOS DEL CORREO
		$mailCtrl->destinatariosList=$this->getRecipients();
		// DATOS DE PERMISO OCASIONAL
		$mailCtrl->mergeData($this->post);
		$mailCtrl->mergeData($mailData);
		
		// ENVIAR MAIL
		$mailCtrl->asyncMail($this->post['mensaje_asunto'],$this->post['mensaje_email']);
		
		// RETORNO DE MODELO
		$sql['mensaje']="Su mensaje ha sido enviado correctamente.<br>¡Gracias por ayudarnos a construir una institución mejor!";
		$sql['data']=array(
			'mail'=>$mailData,
			'post'=>$this->post,
		    'destinatarios'=>$this->getRecipients()
		);
		
		// CERRAR TRANSACCIÓN Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}

	
	
	/*
	 * VALIDAR ASIGNACION
	 */
	private function assignResponsible($old,$new){
		
		// VALIDAR ESTADO ACTUAL
		if($old['mensaje_estado']=='DENUNCIA RECIBIDA' && $new['mensaje_estado']=='ASIGNADO'){
			$old['mensaje_estado']='ASIGNADO';
		}
		
		// RETORNAR MODELO PARA ACTUALIZACION
		return $new;
		
	}
	
	/*
	 * ACTUALIZAR REGISTRO
	 */
	public function updateComplaint(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// OBTENER REGISTRO ANTIGUO
		$old=$this->db->findById($this->post['mensaje_id'],$this->entity);
		
		// REGISTRO ACTUALIZADO
		$this->post=$this->assignResponsible($old,$this->post);
		
		// GENERAR STRING DE ACTUALIZACIÓN
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// CERRAR TRANSACCIÓN Y RETORNAR DATOS
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	
	/*
	 * CONSULTAR PROYECTO POR ID
	 */
	private function requestById($id){
		// OBTENER DATOS DE PROYECTO
		$json=$this->db->findOne($this->db->selectFromView('vw_denunciasenlinea',"WHERE mensaje_id={$id}"));
		
		// CONSULTAR ARCHIVOS ADJUNTOS
		$json['attached']=array(
			'list'=>$this->db->findAll($this->db->selectFromView('gallery',"WHERE fk_table='{$this->entity}' AND fk_id={$json['mensaje_id']}")),
			'url'=>"/app/src/attached/complaints/{$json['denuncia_codigo']}/"
		);
		
		// RETORNAR DATOS DE CONSULTA
		return $json;
	}
	
	/*
	 * CONSULTAR PRESENTACIONES
	 */
	public function requestEntity(){
		// VALIDAR TIPO DE CONSULTA
		if(isset($this->post['complaintId'])) $json=$this->requestById($this->post['complaintId']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
		// STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_denunciasenlinea","WHERE mensaje_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
			// OBTENER REGISTRO
			$row=$this->db->findOne($str);
			
			// CARGAR DATOS DE RESPONSABLE
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['denuncia_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
}