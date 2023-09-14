<?php namespace model\Tthh;
use api as app;
use model as mdl;
use controller as ctrl;

class candidateModel extends mdl\personModel {
	
	private $entity='aspirantes';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESO DE PERSONAL AL CBSD
	 */
	public function insertCandidate(){
		$post=$this->requestPost();
		// VALIDAR CONTRASEÑAS
		if($post['aspirante_clave_alt']!=$post['aspirante_clave']) $this->getJSON("Por favor, verifique que las contraseñas coincidan!");
		// VALIDACIÓN DE EDADES - MESES
		$min=18*12;
		$max=(23*12);
		$fechainicial = new \DateTime($post['persona_fnacimiento']);
		$fechafinal = new \DateTime($this->getFecha());
		$diferencia = $fechainicial->diff($fechafinal);
		$years=$diferencia->y;
		$months=$diferencia->m;
		$days=$diferencia->d;
		// VALIDAR EDAD MÍNIMA
		if((($years*12)+$months)<$min) $this->getJSON("No cumple con el requisito:<br> Edad mínima <b>18 años</b>.");
		//if((($years*12)+$months)>$max) $this->getJSON("No cumple con el requisito:<br> Edad máxima <b>24 años 11 meses</b>.");
		// INCIAR TRANSACCIÓN
		$this->db->begin();
		// INGRESAR O ACTUALIZAR REGISTROS DE PERSONA EXISTENTE
		$person=$this->requestPerson($post);
		// ADD INDEX TO POST
		$post['fk_persona_id']=$person['persona_id'];
		// BUSCAR SI EXISTE ASPIRANTE
		$strAspirante=$this->db->getSQLSelect($this->entity,[],"WHERE fk_persona_id={$post['fk_persona_id']}");
		if($this->db->numRows($strAspirante)>0){
			$post=array_merge($post,$this->db->findOne($strAspirante));
			$post=array_merge($post,$this->db->viewById($post['fk_persona_id'],$this->entity));
			$sql=$this->setJSON("Transacción completa.",true);
		}else{
			// GENERAR CONTRASEÑA
			//$post['aspirante_clave']=$this->generatePassword(6);
			// REGISTRAR ASPIRANTE
			$sql=$this->db->executeTested($this->db->getSQLInsert($post,$this->entity));
			$post['persona_fingreso']=$this->getFecha('full');
		}
		$sql=$this->db->closeTransaction($sql);
		// ENVÍO DE CORREO ELECTRÓNICO CON DATOS DE REGISTRO
		$post['perfil_nombre']='ASPIRANTE A BOMBERO DE LINEA';
		$post['usuario_login']=$person['persona_doc_identidad'];
		// COMPROBAR ESTADO PARA REALIZAR ENVÍO DE MAIL
		$mailCtrl=new ctrl\mailController('candidate');
		$mailCtrl->mergeData($post);
		$mailCtrl->asyncMail("Registro de aspirante",$person['persona_correo']);
		$sql['mensaje'].="<br>Por favor, ahora verifica los siguientes pasos a realizar para completar el proceso. Las instrucciones han sido enviadas a la siguiente direccion <b>{$person['persona_correo']}</b>";
		$this->getJSON($sql);
	}
	
	/*
	 * ACTUALIZAR DATOS DE DE PERSONAL AL CBSD
	 */
	public function updateCandidate(){
		$post=$this->requestPost();
		$post['fk_table']='aspirantes';
		$post['fk_id']=app\session::get();
		$post['media_tipo']='PDF';
		$post['media_titulo']=$post['type'];
		// DATOS DE ASPIRANTE
		$row=$this->db->viewById($post['fk_id'],$this->entity);
		$post['customName']="{$row['persona_doc_identidad']}_{$post['type']}";
		// VALIDAR Y CARGAR ARCHIVO
		$post=$this->uploaderMng->uploadFileService($post,$this->entity);
		// BUSCAR SI YA SE HA SUBIDO EL ARCHIVO
		$strWhr="WHERE fk_table='aspirantes' AND fk_id={$post['fk_id']} AND media_titulo='{$post['media_titulo']}'";
		$str=$this->db->getSQLSelect('gallery',[],$strWhr);
		if($this->db->numRows($str)>0){
			$post=array_merge($post,$this->db->findOne($str));
			$str=$this->db->getSQLUpdate($post,'gallery');
		}else{
			$str=$this->db->getSQLInsert($post,'gallery');
		}
		// INSERTAR - ACTUALIZAR EN BASE DE DATOS
		$sql=$this->db->executeSingle($str);
		// RETORNAR DATOS
		$this->getJSON($sql);
	}
	
	/*
	 * CONSULTAR ASPIRANTE - LOGIN 
	 */
	public function requestCandidate(){
		extract($this->requestPost());
		$str=$this->db->selectFromView("vw_$this->entity","WHERE aspirante_clave='$usuario_password' AND persona_doc_identidad='$usuario_login'");
		if($this->db->numRows($str)>0){
			$row=$this->db->findOne($str);
			if($this->setSession($row['aspirante_id'],'aspirantes')['estado']){
				$json=array_merge($this->setJSON("Bienvenid@ {$row['persona_apellidos']} {$row['persona_nombres']}",true),array('sessionId'=>$row['aspirante_id']));
			} else $json=$this->setJSON($this->varGlobal['SESSION_INIT_ERROR']);
		} else $json=$this->setJSON("Error en las credenciales");
		// REGISTRAR USUARIO
		$this->getJSON($json);
	}
	
	/*
	 * SESSION
	 */
	public function getCandidate(){
		$get=$this->requestGet();
		if(isset($get['candidateId'])) $sessionId=$get['candidateId'];
		else $sessionId=app\session::get();
		// PARÁMETROS DEL SISTEMA
		$config=array_merge($this->getConfParams(),$this->configSys(),$this->configDev());
		// DATOS DE ENTIDAD
		$session=$this->db->viewById($sessionId,$this->entity);
		// CERTIFICADOS
		$certificados=$this->db->findAll($this->db->getSQLSelect('gallery',[],"WHERE fk_table='aspirantes' AND fk_id=$sessionId"));
		$session['listCertificados']=array();
		$session['hasCertificados']=array(
			'DATOS PERSONALES'=>false,
			'TITULO BACHILLER'=>false,
			'CERTIFICADO NO TENER ANTECEDENTES PENALES'=>false,
			'CERTIFICADO NO TENER IMPEDIMENTOS PARA CARGOS PUBLICOS'=>false
		);
		foreach($certificados as $val){
			$session['listCertificados'][$val['media_titulo']]=$val;
			$session['hasCertificados'][$val['media_titulo']]=true;
		}
		// REGISTRAR USUARIO
		$this->getJSON(array('data'=>$session,'config'=>$config));
	}
	
	/*
	 * DESCARGAR CERTIFICADOS DE ASPIRANTE
	 */
	public function downloadCertificates(){
		
// 		$successMail="";
// 		$sql=$this->setJSON("empty");
// 		//foreach($this->getConfig(true,'segpro') as $v){
// 		$v=$this->getConfig(true,'segpro')[0];
// 			$mailCtrl=new ctrl\mailController('signUpSegpro');
// 			$mailCtrl->mergeData($v);
// 			$sql=$mailCtrl->prepareMail("SEGPRO - REGISTRO DE USUARIO",$v['correo']);
// 			if(!$sql){
// 				$sql=$this->setJSON("Error: {$v['nombre']}");
// 				$this->getJSON($sql);
// 			} else $successMail.=" ----- {$v['correo']}";
// 			unset($mailCtrl);
// 		//}	
// 		if($sql['estado']) $sql['mensaje']=$successMail;
// 		$this->getJSON($sql);
		
		
		
		//$mailCtrl=new ctrl\mailController('preseleccionCurso');
		//$mailCtrl=new ctrl\mailController('postulantesDos');
		//$mailCtrl=new ctrl\mailController('cursoBomberosPreseleccion2');
		//$mailCtrl=new ctrl\mailController('conogramaRecordatorio');
		//$mailCtrl=new ctrl\mailController('convocatoriaGrupo2-36');
		//$mailCtrl=new ctrl\mailController('convocatoriaListadoFinal');
		/*$mailCtrl=new ctrl\mailController('convocatoriaCasco');
		$mailCtrl->destinatariosList=array(
			"pablopatricio_alban@hotmail.com",
			"kevinbenavinner@hotmail.com",
			"coreima1997@gmail.com",
			"cangosebastian1998@hotmail.com",
			"elviscastellanos107@gmail.com",
			"byrondavid7000@hotmail.com",
			"jery_alexander@hotmail.com",
			"domenicabricio07@outlook.com",
			"david11-@hotmail.com",
			"briianciito12@gmail.com",
			"lisssol_22@hotmail.com",
			"ericuaman1997@hotmail.com",
			"fabianjaramillo2018@gmail.com",
			"evelyn_l1997@hotmail.com",
			"aveigaloor@hotmail.com",
			"samuel19963017@hotmail.com",
			"merap.pedro@hotmail.com",
			"jeffer94molina@gmail.com",
			"bay_julio1998@hotmail.com",
			"j-moreno3000@hotmail.com",
			"bellafirewoman@hotmail.com",
			"pardodavid97@gmail.com",
			"david_alej95@hotmail.com",
			"belen.1322@hotmail.com",
			"amps96@outlook.com",
			"ANDRES.PAZMINO1995@GMAIL.com",
			"soflex9792@gmail.com",
			"alexrose-98@outlook.es",
			"b-teran@hotmail.com",
			"BETTO21T@HOTMAIL.com",
			"jesuniandes_82@hotmail.com",
			"velozpablo100@gmail.com",
			"carlos0406_95@hotmail.es",
			"cristian_bradpitt@hotmail.com",
			"marco.v.rojas@hotmail.com",
			"vivero123@outlook.com",
			"carlitoss_jeam1994@hotmail.com",
			"DAXIDA2324@GMAIL.com",
			
			"ayanez@cbsd.gob.ec",
			"dluzuriaga@cbsd.gob.ec"
		);
		$mailCtrl->attachmentList=array(
			SRC_PATH."temp/INDICACIONES_E_INSUMOS_MEDICOS.docx",
			SRC_PATH."temp/FORMATO_DECLARACION_JURAMENTADA.docx",
			SRC_PATH."temp/LISTADO_APROBADOS.png",
			SRC_PATH."temp/PRENDAS_EQUIPOS.xlsx",
			SRC_PATH."temp/REGLAMENTO.pdf");
		$sql=$mailCtrl->prepareMail("COMINICADO","apinango@lalytto.com");
		$this->getJSON($sql);*/
		
		$get=$this->requestGet();
		// CONSULTAR LISTADO DE CERTIFICADOS
		$certificados=$this->db->findAll($this->db->getSQLSelect('gallery',[],"WHERE fk_table='aspirantes' AND fk_id={$get['candidateId']}"));
		foreach($certificados as $key=>$val){
			//$files[$val['media_nombre']]="http://cbsd.lalytto.com/app/src/img/gallery/{$val['media_nombre']}";
			$files[$val['media_nombre']]="src/img/gallery/{$val['media_nombre']}";
 			//$files[$key]="src/img/gallery/{$val['media_nombre']}";
		}
		$row=$this->db->viewById($get['candidateId'],$this->entity);
		// DESCARGAR CERTIFICADOS
		$zip= new ctrl\zipController();
		$state=$zip->prepareFile($files,"{$row['persona_doc_identidad']}");
		// REQUEST
		$this->getJSON($state);
	}
	
}