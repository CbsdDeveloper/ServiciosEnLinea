<?php namespace model;
/**
 * Description of personModel
 *
 * @author Lalytto
 * @mail apinango@lalytto.com
 */
use api as app;

class personModel extends app\controller {

	/*
	 * VARIABLES DE ENTORNO
	 */
	private $tbEntity='personas';

	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PARENT
		parent::__construct();
	}

	/*
	 * VALIDACIÓN DE DOCUMENTO DE IDENTIDAD
	 */
	protected function validateIdCard($type='',$document=''){
			
	    $request=$this->validateDocIdentity($document,$type);
	    
		// VALIDAR EN CASO DE SER UN CEDULA
		if($type=='CEDULA'){
			$request=$this->validateDocIdentity($document,$type);
			if(!$request['estado'])$this->getJSON($request);
		}
	}

	/*
	 * VERIFICAR INFORMACIÓN ACADÉMICA DE PERFIL
	 */
	protected function testAcademicInformation($post){
		// LISTA DE NIVELES ACADÉMICOS ACEPTADOS
		$levelList=array(null,'','BACHILLER','TECNICO');
		// TERCER NIVEL
		$type="TERCER NIVEL";
		// VALIDAR LISTADO DE TECER NIVEL
		if(in_array($post['formacion_nivel'],$levelList)) $this->getJSON("El nivel mínimo para el perfil es de <b>$type</b>");
		// RETORNAR CONSULTA
		return $post;
	}

	/*
	 * INSERTAR REGISTRO > TRANSACCIÓN
	 */
	protected function testedInsertPerson($post){
		return $this->db->executeTested($this->db->getSQLInsert($post,$this->tbEntity));
	}

	/*
	 * MODIFICAR REGISTRO > TRANSACCIÓN
	 */
	protected function testedUpdatePerson($post){
		return $this->db->executeTested($this->db->getSQLUpdate($post,$this->tbEntity));
	}

	/*
	 * VALIDAR SI EXISTE DATOS DE PERSONA
	 */
	protected function getPerson($cc){
		$str=$this->db->getSQLSelect($this->tbEntity,[],"WHERE UPPER(persona_doc_identidad)=UPPER('{$cc}')");
		if($this->db->numRows($str)>0) return $this->db->findOne($str);
		return null;
	}

	/*
	 * SET PERSON
	 */
	protected function setPerson($insert=true){
		$post=$this->requestPost();
		$this->validateIdCard($post['persona_tipo_doc'],$post['persona_doc_identidad']);
		return $this->uploaderMng->uploadFileService($post,$this->tbEntity,$insert);
	}

	/*
	 * INGRESAR O MODIFICAR PERSONA
	 */
	protected function requestPerson($post){
		// VALIDAR TIPOS DE DOCUMENTOS
		if(isset($post['persona_tipo_doc'])) $this->validateIdCard($post['persona_tipo_doc'],$post['persona_doc_identidad']);
		// MODELO DE PERSONA
		$person=array();
		// VERIFICAR SI ES UN USUARIO QUE CAMBIA DE DOCUMENTO DE IDENTIDAD
		if(isset($post['persona_id']) && is_int($post['persona_id']) && $post['persona_id']>0){
			// VALIDAR SI LA CÉDULA EXISTE PARA OBTENER LOS DATOS DE LA PERSONA
			if($post['persona_doc_identidad']!="" && $post['persona_doc_identidad']!=null){
				// OBTENER DATOS DE PERSONA CON EL DOCUMENTO DE IDENTIDAD
				$person=$this->getPerson($post['persona_doc_identidad']);
				// ELIMINAR REGISTRO NULL
				unset($post['persona_id']);
			}else{
				// OBTENER DATOS DE PERSONA CON EL DOCUMENTO DE IDENTIDAD
				$person=$this->db->findById($post['persona_id'],$this->tbEntity);
			}
		}else {
			// OBTENER DATOS DE PERSONA CON EL DOCUMENTO DE IDENTIDAD
			$person=$this->getPerson($post['persona_doc_identidad']);
			// ELIMINAR REGISTRO NULL
			unset($post['persona_id']);
		}
		// VALIDAR OBJETO DE PERSONA
		if($person!=null){
			$post=array_merge($person,$post);
			$this->testedUpdatePerson($post);
			return $post;
		} else {
			$this->testedInsertPerson($post);
			return $this->db->getLastEntity($this->tbEntity);
		}
	}
	
	/*
	 * INGRESAR O MODIFICAR REGISTRO DE PERFIL ACADÉMICO
	 */
	protected function requestAcademicTraining($post,$personId){
		// VALIDAR SI HA SIDO SETEADO EL NÚMERO DE REGISTRO DE TITULO
		$strWhere=isset($post['formacion_senescyt'])?" AND UPPER(formacion_senescyt)=UPPER('{$post['formacion_senescyt']}')":"";
		// GENERAR SENTENCIA DE CONSULTA DE REGISTRO
		$str=$this->db->selectFromView('formacionacademica',"WHERE fk_persona_id={$personId} {$strWhere}");
		// INGRESAR ID DE PERSONA
		$post['fk_persona_id']=$personId;
		// CONSULTAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			// ACTUALIZAR DATOS DE REGISTRO
			$str=$this->db->getSQLUpdate(array_merge($row,$post),'formacionacademica');
		}else{
			// REMOVER ID DE REGISTRO
			unset($post['formacion_id']);
			// GENERAR STRING DE CONSULTA
			$str=$this->db->getSQLInsert($post,'formacionacademica');
		}
		// EJECUTAR CONSULTA Y RETORNAR RESULTADO
		return $this->db->executeTested($str);
	}

	/*
	 * REGISTRO DE USUARIOS
	 */
	public function insertPerson($requestJSON=false){
		$sql=$this->testedInsertPerson($this->setPerson());
		if($requestJSON)$this->getJSON($sql);
		return $sql;
	}

	/*
	 * MODIFICACIÓN USUARIOS
	 */
	public function updatePerson($requestJSON=true){
		$sql=$this->testedUpdatePerson($this->setPerson(false));
		if($requestJSON)$this->getJSON($sql);
		return $sql;
	}
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 * -> requestTraining
	 */
	private function requestById($id){
		return $this->db->viewById($id,$this->tbEntity);
	}
	
	/*
	 * CONSULTAR PERSONA POR CEDULA
	 * -> requestTraining
	 */
	private function requestByIdentityCard($cc){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView($this->tbEntity,"WHERE UPPER(persona_doc_identidad)=UPPER('{$cc}')");
		// CONSULTAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0) return $this->db->findOne($str);
		// MENSAJE POR DEFECTO
		$this->getJSON("No se ha encontrado ningun registro relacionado con su dato ingresado!");
	}
	
	/*
	 * CONSULTAR PERSONA POR CEDULA
	 * -> requestTraining
	 */
	private function requestByAcademicTraining($cc){
		// CONSULTAR DATOS DE PERSONA
		$data=$this->requestByIdentityCard($cc);
		// CONSULTAR EL PERFIL ACADÉMICO
		$str=$this->db->selectFromView('formacionacademica',"WHERE fk_persona_id={$data['persona_id']} AND formacion_estado='FINALIZADO' ORDER BY formacion_fregistro DESC LIMIT 1");
		// CONSULTAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0) $data=array_merge($data,$this->db->findOne($str));
		// RETORNAR CONSULTA
		return $data;
	}
	
	/*
	 * REQUEST PERSONA BY
	 */
	public function requestPersonBy(){
		// RECIBIR FORMULARIO
		$post=$this->requestPost();
		// CONSULTAR POR ID DE REGISTRO
		if(isset($post['id'])) $json=$this->requestById($post['id']);
		// CONSULTAR POR CEDULA
		elseif(isset($post['identityCard'])) $json=$this->requestByIdentityCard($post['identityCard']);
		// CONSULTAR DATOS DE FORMACIÓN ACADÉMICA
		elseif(isset($post['academicTraining'])) $json=$this->requestByAcademicTraining($post['academicTraining']);
		// CONSULTAR POR FECHA - NUEVO REGISTRO
		else $this->getJSON("Especificar el recurso que requiere consumir!");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("ok",true,$json));
	}

}