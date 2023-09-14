<?php namespace controller;

class sessionController {
	
	
	
	// **********************************************************************************************
	// MANEJO DE SESIONES USUARIO
	// **********************************************************************************************
	/*
	 * REGISTRO DE BITACORA
	 */
	public function setBinnacle($entityId,$entity,$msg){
		// REGISTRAR ACCESO FALLIDO
		$data=$this->getCurrentPC();
		$data['descripcion']=$msg;
		$data['fk_id']=$entityId;
		$data['fk_tb']=$entity;
		// REGSITART BITACORA
		return $this->db->executeTested($this->db->getSQLInsert($data,'bitacora'));
	}
	/*
	 * CREAR Y REGISTRAR SESION DE USUARIO
	 */
	public static function setSession($entityId,$entity='usuarios'){
		// ENVIAR PARÁMETROS DE BITACORA
		$sql=$this->setBinnacle($entityId,$entity,'INICIO DE SESIÓN');
		// SETTEAR SESIÓN
		if($sql['estado']) session::set($entityId,$entity);
		// RETORNAR CONSULTA
		return $sql;
	}
	/*
	 * CERRAR SESIÓN Y REGISTRAR SALIDA DEL SISTEMA
	 */
	public function logout(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// OBTENER INFORMACIÓN DE USUARIO
		$session=session::decodeSession()['session'];
		// ENVIAR PARÁMETROS DE BITACORA
		$sql=$this->setBinnacle($session['value'],$session['entity'],'Salida del sistema');
		// DESTRUIT SESIÓN
		$estado=session::destroy();
		$request=$this->setJSON($estado?'Buen día!':'Error de comunicación, vuelva a intentar!',$estado);
		// RETORNAR MENSAJE DE SALIDA
		$this->getJSON($this->db->closeTransaction($request));
	}
	/*
	 * VERIFICAR SI EXISTE SESION DE USUARIO
	 */
	public function testSession(){
		$this->getJSON(session::decodeSession());
	}
	/*
	 * Datos de información de equipo que accede el usuario
	 */
	protected function getCurrentPC(){
		extract($this->getConfig('defaultLocation'));
		$ip=$this->getIP();
		extract(json_decode(file_get_contents("http://ipinfo.io/{$_SERVER["REMOTE_ADDR"]}/json"),true));
		if($country!=''){
			$aux=$this->getConfig(false,'countries')['countries'];
			$country="{$aux[$country]['name']} ($country)";
		}
		return array(
				'date'=>$this->getFecha('full'),
				'os'=>$this->getOS(),
				'browser'=>$this->getBrowser(),
				'ipaddress'=>$ip,
				'hostname'=>$hostname,
				'isp'=>$org,
				'country'=>$country,
				'region'=>$region,
				'city'=>$city,
				'location'=>$loc,
				'equipo'=>"{$this->getOS()} en {$this->getBrowser()}"
		);
	}
	/*
	 * Método: obtener IP
	 */
	private function getIP(){
		if(getenv('HTTP_X_FORWARDED_FOR')){
			$pipaddress=getenv('HTTP_X_FORWARDED_FOR');
			$ipaddress=getenv('REMOTE_ADDR');
			return $pipaddress. "(via $ipaddress)" ;
		}else{
			$ipaddress=getenv('REMOTE_ADDR');
			return $ipaddress;
		}
	}
	// Obtener coordenadas
	private function getLocation(){
		return array('ipaddress'=>$this->getIP());
	}
	/*
	 * Método: obtner sistema operativo
	 */
	private function getOS(){
		$user_agent=$_SERVER['HTTP_USER_AGENT'];
		$os_platform="Unknown OS Platform";
		foreach($this->getConfig('osList') as $key=>$val){
			if(preg_match($key,$user_agent))$os_platform=$val;
		}	return $os_platform." (".gethostbyaddr($_SERVER['REMOTE_ADDR']).")";
	}
	/*
	 * Método: obtner navegar
	 */
	private function getBrowser(){
		$user_agent=$_SERVER['HTTP_USER_AGENT'];
		$browser="Unknown Browser";
		$version="";
		foreach($this->getConfig('browserList') as $regex=>$value){
			if(preg_match($regex,$user_agent))$browser=$value;
		}	$known=array('Version',$browser,'other');
		$pattern='#(?<browser>' . join('|', $known) .')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern,$user_agent,$matches)) {
			// we have no matching number just continue
		}
		$i=count($matches['browser']);
		if ($i != 1) {
			//we will have two since we are not using 'other' argument yet, see if version is before or after the name
			if(strripos($user_agent,"Version") < strripos($user_agent,$browser)) $version= $matches['version'][0];
			else $version=$matches['version'][1];
		} else $version=$matches['version'][0];
		if($version==null || $version=="")$version="?";
		if($browser=="Trident") $browser="Internet Explorer";
		return "$browser v$version";
	}
	
	

	
}