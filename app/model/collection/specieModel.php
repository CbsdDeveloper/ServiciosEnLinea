<?php namespace model\collection;
use api as app;

class specieModel extends app\controller {

	private $entity='especies';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
	 * SERIE POR TIPO
	 */
	private function getSerieArching($type){
		// STRING PARA CONSULTAR NUNMERO MAXIMO DE SERIE DEL AÑO
		$str=$this->db->getSQLSelect($this->db->setCustomTable('cierres','max(cierre_serie::int) serie'),[],"WHERE cierre_tipo='{$type}' AND date_part('year',cierre_fecha)=date_part('year',current_date)");
		// VALIDAR CONSULTA
		$serie=$this->db->findOne($str);
		// RETORNAR SERIE +1
		return intval($serie['serie'])+1;
	}
	
	/*
	 * REGISTRAR CIERRE DE CAJA
	 */
	public function updateEntity(){
		
		// VALIDAR SI PARA EL CIERRE SE HA INGRESADO LOS RESPONSABLES
		if(!isset($this->post['fk_tesorero']) || !isset($this->post['fk_recaudador'])) $this->getJSON("No se ha seleccionado los responsables del registro [RECAUDADOR, TESORERO O JEFES INMEDIATOS]");
		
		// INICIAR TRANSACCIÓN
		$this->db->begin();
		
		// REGISTRAR CUADRE DE CAJA
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,$this->entity));
		
		// GENERAR MODELO AUXILIAR
		$arching=array(
			'cierre_serie_valorada_costo'=>$this->post['especie_costo'],
			'cierre_serie_valorada_inicia'=>$this->post['especie_inicio'],
			'cierre_serie_valorada_termina'=>$this->post['especie_fin'],
			
			'cierre_serie_novalorada_costo'=>$this->post['especie_novalorada_costo'],
			'cierre_serie_novalorada_inicia'=>$this->post['especie_novalorada_inicio'],
			'cierre_serie_novalorada_termina'=>$this->post['especie_novalorada_fin'],
			'cierre_serie_novalorada_baja_total'=>$this->post['especie_novalorada_baja_total'],
			'cierre_serie_novalorada_baja'=>$this->post['especie_novalorada_baja'],
			
			'cierre_fecha'=>$this->post['fecha_registro'],
			'cierre_tipo'=>'ESPECIES',
			'cierre_total'=>$this->post['total_costo'],
			'fk_tesorero'=>$this->post['fk_tesorero'],
			'fk_recaudador'=>$this->post['fk_recaudador']
		);
		
		// STRING PARA CONSULTAR EXISTENCIA DE CIERRE DE CAJA DE ESA FECHA
		$str=$this->db->selectFromView('cierres',"WHERE cierre_tipo='ESPECIES' AND cierre_fecha='{$this->post['fecha_registro']}'::date");
		// VALIDAR CONSULTA
		if($this->db->numRows($str)>0){
			// CONSULTAR REGISTRO
			$row=$this->db->findOne($str);
			// STRING PARA CONSULTA 
			$str=$this->db->getSQLUpdate(array_merge($row,$arching),'cierres');
		}else{
			// ANEXAR DATOS A MODELO
			$arching['cierre_serie']=$this->getSerieArching('ESPECIES');
			// STRING PARA CONSULTA
			$str=$this->db->getSQLInsert($arching,'cierres');
		}
		// EJECUTAR CONSULTA DE CIERRE
		$json=$this->db->executeTested($str);
		
		// CERRAR TRANSACCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
		
	}
	
	
	
	
	/*
	 * RESPONSABLES:: RECAUDADOR Y TESORERO
	 */
	private function getResponsible(){
		// MODELO
		$data=array();
		
		// AUXILIARES
		$recaudador=array();
		
		// CONSULTAR RECAUDADOR
		$fk_recaudador=$this->getStaffIdBySession();
		$str=$this->db->selectFromView('vw_relations_personal',"WHERE personal_id={$fk_recaudador} AND ppersonal_estado='EN FUNCIONES'");
		// VALIDAR EXISTENCIA DE RECAUDADOR
		if($this->db->numRows($str)<1) $this->getJSON("No se ha podido registrar el perfil de <b>RECAUDADOR/A</b>, favor tome contacto con el administrador!");
		else{
			$recaudador=$this->db->findOne($str);
			$data['fk_recaudador']=$recaudador['ppersonal_id'];
		}
		
		// CONSULTAR PUESTOS SUPERIORES A RECAUDADOR
		// OBTENER DATOS DEL PERFIL DE RECAUDACION
		$recaudadorJob=$this->db->findById($recaudador['puesto_id'],'puestos');
		// SELECCIONAR ID DE PUESTOS
		$str=$this->db->setCustomTable('puestos','puesto_id');
		// SELECCIONAR ID DE PUESTOS SUPERIORES DE LA MISMA DIRECCIÓN
		$str=$this->db->selectFromView($str,"WHERE fk_direccion_id={$recaudadorJob['fk_direccion_id']} AND puesto_grado<={$recaudadorJob['puesto_grado']} ORDER BY puesto_grado");
		// LISTADO DE PERSONAL SUPERIOR AL PERFIL RECAUDADOR
		$data['fk_boss']=$this->db->findAll($this->db->selectFromView('vw_ppersonal',"WHERE fk_puesto_id IN ({$str}) AND ppersonal_estado='EN FUNCIONES' ORDER BY personal_nombre"));
		
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * APERTURA DE DIA
	 */
	private function openNewDay(){
		// MODELO
		$model=array(
			'fecha_registro'=>$this->getFecha(),
			'especie_costo'=>3,
			'especie_inicio'=>0,
			'especie_novalorada_costo'=>0,
			'especie_novalorada_inicio'=>0
		);
		
		// CONSULTAR REGISTRO DEL DIA ANTERIOR
		$lastRecord=$this->db->findOne($this->db->selectFromView($this->entity,"WHERE fecha_registro<'{$model['fecha_registro']}' ORDER BY fecha_registro DESC LIMIT 1"));
		
		// INGRESAR SIGUIENTE NUMERO DE ESPECIE VALORADA
		$model['especie_inicio']=$lastRecord['especie_fin']+1;
		// INGRESAR SIGUIENTE NUMERO DE ESPECIE NO VALORADA
		$model['especie_novalorada_inicio']=$lastRecord['especie_novalorada_fin']+1;
		
		// RETORNO DE MODELO
		return $model;
	}
	
	/*
	 * CONSULTAR CAJA PARA CIERRE
	 */
	private function requestById($recordId){
		// MODELO
		$model=$this->db->findById($recordId,$this->entity);
		
		// OBTENER DATOS DE RESPONSABLES
		$responsible=$this->getResponsible();
		
		// DATOS DE RESPONSABLE DE REGISTRO
		$model['fk_recaudador']=$responsible['fk_recaudador'];
		$model['fk_staff_leadership']=$responsible['fk_boss'];
		$model['especie_estado']=$this->post['especie_estado'];
		
		// RETORNO DE MODELO
		return $model;
	}
	
	/*
	 * CONSULTA DE CAJAS
	 */
	public function requestEntity(){
		// CONSULTAR SI SE HA ENVIADO EL PARAMETRO TYPE
		if(isset($this->post['type']) && $this->post['type']=='new') $json=$this->openNewDay();
		// CONSULTAR POR FECHA
		elseif(isset($this->post['especie_id'])) $json=$this->requestById($this->post['especie_id']);
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
}