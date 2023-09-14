<?php namespace controller;
use api as app;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Load Composer's autoloader
require VENDOR_PATH.'autoload.php';
class mailController extends app\controller {
	
	private $data=array();
	public $template;
	private $cc;
	public $webmail;
	public $destinatariosList;
	PUBLIC $attachmentList;
	
	// Iniciar 
	public function __construct($templateHTML='mail',$sender=102){
		// Método constructor
		parent::__construct();
		// Con copia
		$this->cc=false;
		// Cargar template
		$this->template=$templateHTML;
		// ENVÍO DE CORREO DESDE
		$this->webmail=$sender;
		// LISTA DE DESTINATARIOS
		$this->destinatariosList=array();
		// LISTA DE ADJUNTOS
		$this->attachmentList=array();
	}
	
	// Cargar datos para template
	public function mergeData($data){
	    $this->data=array_merge($this->data,$data);
	}
	
	// Obtener datos del template
	public function getData(){ return $this->data; }
	
	// **********************************************************************************************
	// ************************** MÉTODOS PARA ENVÍO DE CORREOS ELECTRÓNICOS ************************
	// **********************************************************************************************
	// Método para el envío de correos electrónicos
	// Método: Enviar correo vario
	// - Asunto del correo
	// - Mensaje para incluir en plantilla
	// - $para, destinatario
	// - $data, conjunto de datos
	// - plantilla, template
	public function prepareMail($asunto,$destinatario=''){
		$mail=$this->sendMail($asunto,$destinatario);
		if(is_bool($mail) && $mail){return true;}
		else $this->getJSON($mail);
	}
	
	/*
	 * ENVÍO DE CORREO DE MANERA ASÍNCREONA
	 */
	public function asyncMail($asunto,$destinatario=''){
		return $this->sendMail($asunto,$destinatario);
	}
	
	// Método para el envío de correos electrónicos
	private function sendMail($asunto,$destinatario){
		// Obtener plantilla de correo electrónico, en direcctorio
		$mail=file_get_contents(SRC_PATH . "views/mail/{$this->template}.html");
		// Obtiene datos del sistema, nombre, dirección, etc.
		$asunto="{$asunto} | ({$this->varGlobal['INSTITUTION_NAME']})";
		$this->mergeData(array('para'=>$destinatario,'asunto'=>$asunto));
		$this->mergeData($this->configSys());
		$this->mergeData($this->getConfParams());
		$this->mergeData($this->getCurrentPC());
		$this->mergeData($this->configDev());
		// Datos de envío
		$config=$this->db->findById($this->webmail,'webmail');
		$this->mergeData($config);
		// Reemplazar parámetros
		$mail=$this->recursiveReplace($this->data,$mail);
		extract($config);
		// REEMPLAZAR VARIABLES GLOBALES EN MENSAJE
		$mensaje=$this->recursiveReplace($this->data,$mail);
		// Intancia de PHPMailer
		$mail=new PHPMailer(true);
		try{
			// si es html o txt
			$mail->IsHTML(true); 
			$mail->CharSet='UTF-8';
			$mail->setLanguage('es');
			$mail->IsSMTP();
			// Configurar parámetros de correo
			$mail->SMTPAuth=true;
			$mail->SMTPSecure=$webmail_seguridad;
			$mail->Host=$webmail_host;
			$mail->Port=$webmail_puerto;
			$mail->Username=$webmail_mail;
			$mail->Password=$webmail_pass;
			// Parámetros de remitente
			$mail->FromName=$webmail_usuario;
			$mail->From=$webmail_mail;
			$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);
			// DESTINATARIOS
			$mail->addBcc($webmail_mail);
			//nuevo campo en db - quien recibe mensajes
			array_push($this->destinatariosList,$destinatario);
			foreach($this->destinatariosList as $to){
				// $mail->AddCC($to);
				$mail->AddAddress($to);
			}
			// VERIFICAR ARCHIVOS ADJUNTOS
			foreach($this->attachmentList as $file){
				$mail->AddAttachment($file);
			}
			// REALIZAR EL ENVÍO
			$mail->Subject=$asunto;
			$mail->CharSet='UTF-8';
			$mail->Body=$mensaje;
			$mail->WordWrap=100;
			$mail->MsgHTML($mensaje);
			if($mail->send()) return true;
			else return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		} catch (Exception $e) {
			return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}
	}
	
}