<?php namespace controller;

class smsController extends twilioController {
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// MODELO CONSTRUCTOR
		parent::__construct('whatsapp','dev');
	}
	
	/*
	 * ENVIAR SMS
	 */
	public function sendSMS($text,$numberTo='+593986865422'){
		// Use the client to do fun stuff like send text messages!
		$this->twilioSrv->messages->create(
			// the number you'd like to send the message to
			$numberTo,
			array(
				// A Twilio phone number you purchased at twilio.com/console
				'from' => $this->service['from'],
				// the body of the text message you'd like to send
				'body' => substr($text, 0, 160)
			)
		);
	}
	
}