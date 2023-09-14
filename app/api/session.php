<?php namespace api;
/**
 * Description of session
 * session.php utiliza como métodos principales las sesiones de PHP
 * La función de la clase es gestionar las sesiones en el sistema, crear,
 * almacenar y eliminar sesiones del navegar
 * @author Lalytto
 */
class session {
	private static $sessionKey="session.";
	public static $sysName;
	public static $methodSession;
	public static $valueSession;
	public static $keepFKUser;
	
	// Método: Inicia las sesiones de PHP
	public static function start(){
		if(!isset($_SESSION)){
			session_name("sessionByLalytto");
			session_start();
		}
	}
	// Settear datos de sessión - multiusuario
	public static function setKey($url){self::$sessionKey.=$url;self::$sysName=$url;}
	public static function getKey(){return self::$sessionKey;}
	// Método: Establece el inicio de una nueva sesión en php
	public static function set($valor,$entity='usuarios'){
		$sessionLocal=array(
			'type'=>self::$sysName,
			'key'=>self::getKey(),
			'value'=>$valor,
			'entity'=>$entity,
			'authenticated'=>true,
			'dateLoggin'=>date("Y-m-j H:i:s"),
			'lastSeen'=>date("Y-m-j H:i:s")
		);
		$_SESSION[self::getKey()]=$sessionLocal;
	}
	// Retorna id de sessión generado
	public static function get(){
		$key=self::getKey();
		if(isset($_SESSION[$key])){
			$session=$_SESSION[$key];
			self::$valueSession=$session;
			self::$methodSession = 'PHP SESSION';
			return $session['value'];
		}
		if(isset($_COOKIE[self::$sysName]) && $_GET['sys']==self::$sysName){
			self::$valueSession=$_COOKIE;
			self::$methodSession = 'PHP COOKIE - COOKIE';
			return $_COOKIE[self::$sysName];
		}
		if(isset($_GET[self::$sysName]) && $_GET['sys']==self::$sysName){
			self::$valueSession=array();;
			self::$methodSession = 'PHP GET';
			return $_GET[self::$sysName];
		}
	}
	// MÉTODO PARA EJECUTAR CUANDO NECESITA SOLO SESIÓN ADMIN -> REPORTES -> smartQuery
	public static function getSessionAdmin(){
		$session=$_SESSION["session.system"];
		self::$valueSession=$session;
		self::$methodSession = 'PHP SESSION';
		return $session['value'];
	}
	// RETORNA SESIÓN ACTIVA
	public static function decodeSession(){
		return array(
			'value'=>session::get(),
			'method'=>session::$methodSession,
			'session'=>session::$valueSession
		);
	}
	// Método: Destruye la sesión del usuario
	public static function destroy(){
		unset($_SESSION[self::getKey()]);
		unset($_COOKIE[self::$sysName]);
		unset($_COOKIE[self::$sessionKey]);
		return (isset($_SESSION[self::getKey()])||isset($_COOKIE[self::$sysName]))?false:true;
	}
	// **************************** VALIDAR SESSIÓN ACTIVA ******************************************
	public static function existe(){
		$key=self::getKey();
		if(isset($_SESSION[$key])){
			/*
			$session=$_SESSION[$key];
			if(isset($session['authenticated']) && (is_bool($session['authenticated']) && $session['authenticated'])){
				// Tiempo transcurrido
				$fechaGuardada=$session["lastSeen"];
				$ahora=date("Y-m-j H:i:s"); 
				$tiempo_transcurrido=(strtotime($ahora)-strtotime($fechaGuardada));
				// Comparar el tiempo transcurrido
				if($tiempo_transcurrido >= 600){
					//si pasaron 10 minutos o más
					self::destroy(); // Destruye la sesión
				} else {
					$session["lastSeen"]=$ahora;
					$session["autoDestroy"]=date('Y-m-j H:i:s',strtotime('+10 minutes',strtotime($session['lastSeen'])));
					$_SESSION[$key]=$session;
				}
			}
			*/
			return true;
		}
		if((isset($_COOKIE[self::$sysName]) || isset($_GET[self::$sysName])) && (isset($_GET['sys']) && $_GET['sys']==self::$sysName)) return true;
		return false;
	}
	// UNIÓN CON DATOS DE AUDITORIA
	public static function setResource($evento='Edición',$tb){
		// MODELO DE AUDITORIA
		$data=array();
		// OBTENER PARÁMETROS DE ENTIDAD
		$crud=json_decode(file_get_contents(SRC_PATH."config/crud.json"),true);
		// ENTIDADES DE EDICIÓN
		$temp=$crud['entityPrefix'];
		// NOMBRE DE ENTIDAD
		$_tb=$tb;
		// VALIDAR SI LA ENTIDAD HA SIDO DECLARADA
		if(isset($temp[$tb])) $_tb=$temp[$tb];
		// VALIDAR SI DEBE MANTENER EL ID DE USUARIO
		if(!self::$keepFKUser){
			$user=self::getCustomUser();
			$data['fk_usuario_id']=($user==null?3:$user);
		}
		// DEFINIR EVENTO PARA DB
		$data["{$_tb}_evento"]=$evento;
		$data["{$_tb}_registro"]=date("Y-m-j H:i:s");
		// VALIDAR AUDITORIA TALENTO HUMANO
		if(in_array($tb,$crud['auditTTHH'])) $data['fk_personal_id']=self::get();
		// RETORNAR MODELO
		return $data;
	}
	
	private static function getCustomUser(){
		$adminList=array('session.system','session.report');
		return (in_array(self::getKey(),$adminList))?self::getSessionAdmin():2;
	}
	
}