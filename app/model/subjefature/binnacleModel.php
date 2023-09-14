<?php namespace model\subjefature;
use api as app;

class binnacleModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='libronovedades';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// METODO CONSTRUCTOR
		parent::__construct();
	}
	
	/*
	 * CONSULTAR SI ESTA EN UN PELOTON
	 */
	protected  function vaidateSsessionInPlatoon(){
	    // OBTENER SESION
	    $sessionId=$this->getStaffIdBySession();
	    // DATOS DE PELOTON
	    $str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$sessionId} AND tropa_estado='ACTIVO'");
	    // RETORNAR ESTADO DE CONSULTA
	    return $this->db->numRows($str)>0;
	}
	
	
	/*
	 * VALIDAR CONFIGURACIÓN DE PERSONAL
	 */
	private function testInsertGroups($guard,$time){
		// FECHA DE REGISTRO
		$date=$this->setFormatDate($time,'Y-m-d');
		// MENSAJE DEFAULT
		$msg="Operación cancelada, para continuar con el registro favor verifique primero la(s) siguiente(s) observacion(es):<br>";
		// VALIDAR INGRESO DE PERSONAL EN GUARDIAS
		if((!isset($guard['diurna']) || $guard['diurna']<1) && (strtotime($time)<strtotime($this->getFecha("{$date} 20:00")))) $this->getJSON($this->setJSON("{$msg} Distribuir el personal en la guardia diurna.",'info'));
		if(!isset($guard['nocturna']) || $guard['nocturna']<1 && (strtotime($time)>=strtotime($this->getFecha("{$date} 20:00")))) $this->getJSON($this->setJSON("{$msg} Distribuir el personal en la guardia nocturna.",'info'));
		if(!isset($guard['fk_responsable_guardia']) || $guard['fk_responsable_guardia']<1) $this->getJSON($this->setJSON("{$msg} Registrar el responsable de guardia.",'info'));
	}
	
	/*
	 * GENERAR CODIGO DE REGISTRO
	 * BUSCAR GUARDIA SEGUN LA FECHA DE REGISTRO
	 */
	protected function newCodeEntity($date){
		// FECHA DE REGISTRO
		$time=$date;
		$date=$this->setFormatDate($date,'Y-m-d');
		
		// ID DE SESION
		$sessionId=$this->getStaffIdBySession();
		
		// DATOS DE PELOTON
		$str=$this->db->selectFromView('vw_tropas',"WHERE fk_personal_id={$sessionId} AND tropa_estado='ACTIVO'");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("Al parecer usted no se encuentra registrado dentro del distributivo, si condidera que es un error favor contáctese con la Dirección de Talento Humano");
		// OBTENER REGISTRO
		$platoon=$this->db->findOne($str);
		
		// BUSCAR GUARDIA DE LA FECHA DEL REGISTRO
		$minDate=$this->addDays($date,1,'-');
		// VALIDAR SI SE HA SELECCIONADO UN PELOTON
		if(isset($this->post['fk_dist_pelo_id'])){
			$str=$this->db->selectFromView('vw_guardias',"WHERE fk_peloton_id={$this->post['fk_dist_pelo_id']} AND (guardia_fecha='{$date}'::date OR guardia_fecha='{$minDate}'::date)");
		}else{
			$str=$this->db->selectFromView('vw_guardias',"WHERE fk_peloton_id={$platoon['fk_dist_pelo_id']} AND (guardia_fecha='{$date}'::date OR guardia_fecha='{$minDate}'::date)");
		}
		
		// VALIDAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)<1) $this->getJSON("Esta operación no se puede registrar, al parecer no se ha aperturado la guardia para <b>{$platoon['estacion_nombre']} - {$platoon['peloton_nombre']}</b> en la fecha seleccionada <b>{$date}</b>.");
		// OBTENER REGISTRO DE GUARDIA
		$platoon['guard']=$this->db->findOne($str);
		
		// VALIDAR INGRESO DE DATOS
		$this->testInsertGroups($platoon['guard'],$time);
		
		// OBTENER NUMERO DE REGISTROS POR DIA Y ESTACION
		$serie=str_pad($this->db->numRows($this->db->selectFromView($this->entity,"WHERE fk_guardia_id={$platoon['guard']['guardia_id']}"))+1,3,"0",STR_PAD_LEFT);
		// INSERTAR CODIGO DE REGISTRO
		$platoon['bitacora_codigo']="{$platoon['guard']['guardia_codigo']}-{$serie}";
		
		// RETORNAR CODIGO DE REGISTRO
		return $platoon;
	}
	
	/*
	 * INGRESO HEREDADO
	 */
	protected function insertHierarchyBinnacle($data,$date){
		// REGISTRO DE PELOTON
		$platoon=$this->newCodeEntity($date);
		// INSERTAR DATOS DE PELOTON
		$data['fk_peloton_id']=$platoon['tropa_id'];
		$data['fk_guardia_id']=$platoon['guard']['guardia_id'];
		$data['bitacora_codigo']=$platoon['bitacora_codigo'];
		// RETORNAR CONSULTA
		return $this->db->executeTested($this->db->getSQLInsert($data,$this->entity));
	}
	
	/*
	 * INSERTAR NUEVO REGISTRO
	 */
	public function insertEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// VALIDAR FECHA DE REGISTRO
		if($this->post['bitacora_fecha_tipo']=='SISTEMA') $this->post['bitacora_fecha']=$this->getFecha('dateTime');
		
		// REGISTRO DE PELOTON
		$platoon=$this->newCodeEntity($this->post['bitacora_fecha']);
		
		// INSERTAR DATOS DE PELOTON
		$this->post['fk_guardia_id']=$platoon['guard']['guardia_id'];
		$this->post['fk_peloton_id']=$platoon['tropa_id'];
		$this->post['bitacora_codigo']=$platoon['bitacora_codigo'];
		
		// REGISTRAR MOVIMIENTO DE UNIDAD
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICAR REGISTRO
	 */ 
	public function updateEntity(){
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		// REGISTRAR MOVIMIENTO DE UNIDAD
		$sql=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		// VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR REGISTRO
	 */
	private function requestByType(){
		// OBTENER ID DE SESIÓN
		$sessionId=app\session::get();
		
		// CONSULTAR PELOTON SEGUN LA SESION
		$str=$this->db->selectFromView('vw_tropas',"WHERE fk_personal_id={$sessionId} AND tropa_estado='ACTIVO'");
		// CONSULTAR SI EXISTE EL REGISTRO
		if($this->db->findOne($str)<1) $this->getJSON("Lo sentimos, su cuenta no se encuentra asignada a ningún pelotón.");
		// LISTADO DE PELOTONES
		$distribution=$this->db->findAll($str);
		
		// GENERAR MODELO DE CONSULTA
		$model=array(
			'distribution'=>$distribution
		);
		
		// RETORNAR MODELO
		return $model;
	}
	
	/*
	 * CONSULTAR REGISTROS
	 */
	public function requestEntity(){
		// CONSUTAR ENTIDAD PARA REGISTRO NUEVO
		if(isset($this->post['type']) && $this->post['type']=='new') $json=$this->requestByType();
		// VERIFICAR 
		elseif(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// LISTA DE PASAJEROS
		elseif(isset($this->post['type']) && $this->post['type']=='passengers') $json=$this->requestPassengers();
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso a consumir");
		// RETORNAR CONSULTAS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}