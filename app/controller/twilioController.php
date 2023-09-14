<?php namespace controller;
// Load Composer's autoloader
require VENDOR_PATH.'autoload.php';
// Use the REST API Client to make requests to the Twilio REST API
use Twilio\Rest\Client;
class twilioController {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $auth;
	private $config;
	protected $service;
	protected $twilio;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct($srv='sms',$io='dev'){
		// CONFIGURACIÓN JSON
		$this->config=json_decode(file_get_contents(SRC_PATH."config/twilio.json"),true);
		// INSTANCIA DE AMBIENTE
		$this->auth=$this->config[$io];
		// INSTANCIA DE SERVICO
		$this->service=$this->config[$srv][$io];
		// INSTANCIA DE TWLIO
		$this->twilio = new Client($this->auth['sid'], $this->auth['token']);
	}
	
}