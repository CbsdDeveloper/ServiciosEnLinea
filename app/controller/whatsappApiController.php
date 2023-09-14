<?php namespace controller;
set_time_limit(300);
use Registration;
use WhatsProt;
use MyEvents;

// Load Composer's autoloader
require VENDOR_PATH.'whatsapp/chat-api/src/Registration.php';
require VENDOR_PATH.'whatsapp/chat-api/src/whatsprot.class.php';
require VENDOR_PATH.'whatsapp/chat-api/src/events/MyEvents.php';

class whatsappApiController {
	
	/*
	 * VARIABLES DE ENETORNO
	 */
	private $pwd='EnhqXKeu5Cy23oDB33rOBGjsvlk=';	// Your number with country code, ie: 34123456789
	private $username;	// Your number with country code, ie: 34123456789
	private $nickname;	// Your nickname, it will appear in push notifications
	private $debug;		// Shows debug log, this is set to false if not specified
	private $log;		// Enables log file, this is set to false if not specified
	private $w;
	private $r;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// DATOS DE SESION
		$this->username='593991737549';
		$this->nickname='Lalytto';
		$this->debug=true;
		$this->log=true;
	}
	
	/*
	 * WHATSPROT
	 */
	public function sendMessage($number,$msg){
		// Create a instance of WhatsProt class.
		$this->w=new WhatsProt($this->username, $this->nickname, $this->debug, $this->log);
		
		$events = new MyEvents($this->w);
		$events->setEventsToListenFor($events->activeEvents); //You can also pass in your own array with a list of events to listen too instead.
		
		$this->w->connect(); // Connect to WhatsApp network
		try{
			$this->w->loginWithPassword($this->pwd); // logging in with the password we got!
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
		
		if(strlen($number)==9) $number = "593{$number}";
		elseif(strlen($number)==10)  $number = "593" . substr($number, 1);
		
		$this->w->SendPresenceSubscription($number);
		return $this->w->sendMessage($number, $msg);
	}
	
	/*
	 * REGISTRATION
	 */
	public function setRegistration(){
		// Create a instance of Registration class.
		$this->r = new Registration($this->username, $this->debug);
		// could be 'voice' too
		$this->r->codeRequest('sms');
	}
	
	/*
	 * REGISTRATION
	 */
	public function setCodeRegistration($code){
		// Create a instance of Registration class.
		$this->r = new Registration($this->username, $this->debug);
		// enter the code
		$this->r->codeRegister($code);
	}
	
	/*
	 * CHECK CREDENTIALS
	 */
	public function checkCredentials(){
		// Create a instance of Registration class.
		$this->w = new Registration($this->username, $this->debug);
		// STATUS
		return $this->w->checkCredentials();
	}
	
}