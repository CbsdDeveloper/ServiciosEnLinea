<?php namespace controller;

class whatsappController extends twilioController {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $receiverList;
	private $msg;
	private $data;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// MODELO CONSTRUCTOR
		parent::__construct('whatsapp','pro');
	}
	
	/*
	 * ENVIAR MENSAJE
	 */
	public function sendMessage($number,$msg){
		// FORMAT NUMBER
		if(strlen($number)==9) $number="+593{$number}";
		elseif(strlen($number)==10) $number="+593".substr($number,1);
		elseif(strlen($number)==12) $number="+{$number}";;
		
		// PARAMETRIZACION DEL MENSAJ
		$message = $this->twilio->messages->create(
			"whatsapp:{$number}",
			array(
				"body" => $msg,
				"from" => $this->service['from']
			)
		);
		// RETORNAR ID DE MENSAJE
		return $message->sid;
	}
	
	/*
	 * PREPARAR NOTIFICACIONES
	 */
	public function sendCustomMessage($receiverList,$msg){
		// ENVIAR MENSAJE
		foreach($receiverList as $num){ $this->sendMessage($num,$msg); }
	}
	
	/*
	 * PREPARAR NOTIFICACIONES
	 */
	public function prepareNotifications($entity,$config,$data){
		// INGRESAR LISTA DE DESTINATARIOS
		$this->receiverList=$config['list'][$entity];
		$this->msg=$config['msg'][$entity];
		$this->data=$data;
		
		// PARSE MENSAJE
		foreach($data as $k=>$v){$this->msg=preg_replace('/{{'.$k.'}}/',$v,$this->msg);}
		
		// ENVIAR MENSAJE
		foreach($this->receiverList as $num){ $this->sendMessage($num,$this->msg); }
	}
	
}