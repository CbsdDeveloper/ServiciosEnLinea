<?php namespace controller;

class whatsMateController {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $WHATSAPP_GATEWAY;  // TODO: Replace it with your gateway instance ID here
	private $INSTANCE_ID;  // TODO: Replace it with your gateway instance ID here
	private $CLIENT_ID;  // TODO: Replace it with your Forever Green client ID here
	private $CLIENT_SECRET;   // TODO: Replace it with your Forever Green client secret here
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// VALORES INICIALES DE API
		$this->WHATSAPP_GATEWAY='+85254366577';
		$this->INSTANCE_ID='20';
		$this->CLIENT_ID='ilalytto@gmail.com';
		$this->CLIENT_SECRET='0db89253c64546388165623f7b801246';
	}
	
	public function sendMessage($msg,$number){
		
		
		$postData = array(
			'number' => $number,  // TODO: Specify the recipient's number here. NOT the gateway number
			'message' => $msg
		);
		$headers = array(
			'Content-Type: application/json',
			'X-WM-CLIENT-ID: '.$this->CLIENT_ID,
			'X-WM-CLIENT-SECRET: '.$this->CLIENT_SECRET
		);
		$url = 'http://api.whatsmate.net/v3/whatsapp/single/text/message/' . $this->INSTANCE_ID;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
		$response = curl_exec($ch);
		curl_close($ch);
		
		return "Response: ".$response;
		
	}
	
}