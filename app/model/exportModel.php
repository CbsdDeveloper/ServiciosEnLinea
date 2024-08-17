<?php namespace model;
use api as app;
use controller as ctrl;

// error_reporting(E_ALL);
// ini_set('display_errors', 'On');

class exportModel extends app\controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $data;
	private $fieldList;
	private $config;
	private $pdfCtrl;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
		// CONFIGURACIÓN JSON PARA REPORTES
		$this->config=$this->getConfig(true,'reports');
	}
	
	
	/*
	 * CONSULTAR SI ESTA EN UN PELOTON
	 */
	private  function validateStaffInPlatoon($personalId){
	    // DATOS DE PELOTON
	    $str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$personalId} AND tropa_estado='ACTIVO'");
	    // RETORNAR ESTADO DE CONSULTA
	    return array(
	        'status'=>$this->db->numRows($str)>0,
	        'data'=>$this->db->findOne($str)
	    );
	}
	
	
	/*
	 * LISTADO DE MODULOS Y ENTIDADES PARA REPORTES ESTADÍSTICOS
	 */
	public function exportEntity(){
		
		// PARAMETROS
		$this->fieldList=$this->post['fieldList'];
		
		// VALIDAR CUATOM WHERE
		if(isset($this->fieldList['customWhere'])){
			// SENTENCIA POR DEFAULT
			$strWhr=$this->fieldList['customWhere'];
			// INSERTAR VALORES EN SENTENCIA
			foreach($this->fieldList['fields'] as $k=>$v){ 
				
				if($v['type']=='date') $data="'{$v['value']}'::date";
				elseif($v['type']=='numeric') $data="{$v['value']}";
				else $data="'{$v['value']}'";
				
				$strWhr=preg_replace('/{{'.$k.'}}/',$data,$strWhr);
			}
		}else{
			// STRING PARA CONSULTA
			$fields=array();
			$strWhr=$this->fieldList['where'];
			// CONDICIONAL
			foreach($this->fieldList['fields'] as $k=>$v){
				if($v['type']=='date') $fields[$k]="{$k}::date {$v['str']} '{$v['value']}'::date";
				else $fields[$k]="UPPER({$k}) {$v['str']} UPPER('{$v['value']}')";
			}
			// INSERTAR CAMPOS
			foreach($fields as $k=>$v){ $strWhr=preg_replace('/{{'.$k.'}}/',$v,$strWhr); }
		}
		
		// INGRESAR SESSION DE USUARIO
		if(isset($this->post['sessionName'])){
			$strWhr=preg_replace('/{{sessionName}}/',$this->post['sessionName'],$strWhr);
			$strWhr=preg_replace('/{{sessionId}}/',$this->post['sessionId'],$strWhr);
		}
		
		// DATOS DE CONSULTA
		$rows=$this->db->findAll($this->db->selectFromView($this->fieldList['view'],$strWhr));
		
		// CONFIGURACIÓN DE REPORTE
		$localConfig=$this->config['entities'][$this->fieldList['tb']];
		// DATOS DE REPORTE
		$config=$this->db->findById($localConfig['templateList'],'reportes');
		// RECORRES FILAS DE DATOS
		$config['tbody']="";
		foreach($rows as $idx=>$row){
			$row['index']=$idx+1;
			$aux="<tr>";
			foreach($this->fieldList['rows'] as $key){ $aux.="<td>{$row[$key]}</td>"; }
			$aux.="</tr>";
			$config['tbody'].=$aux;
		}
		
		// INSTANCIA DE PDF
		$this->pdfCtrl=new ctrl\pdfController;
		
		// IMPRESION DE PLANTILLA
		$config['TEMPLATE_REPORT_PRINT']=$this->varGlobal[$this->fieldList['template']];
		
		
		// DATOS AUXILIARES PARA IMPRESION DE REPORTE
		$auxData=array(
		    'FIRMAS_RESPONSABLES_HOJATURA'=>$this->varGlobal['EXPORTDATA_TEMPLATE_ROADMAP_ADMINISTRATIVOS'],
		    'jf_nombre'=>'',
		    'jf_definicion'=>''
		);
		
		// REGISTRO DE DATOS DE PERSONAL
		if(isset($this->post['ppersonalId'])){
		    
		    // INFROMACION DE PERSONAL
		    $personalData=$this->db->findOne($this->db->selectFromView('vw_ppersonal',"WHERE ppersonal_id={$this->post['ppersonalId']}"));
			
			// DATOS DE PERSONAL
			$staffData=$this->db->findOne($this->db->selectFromView('vw_ppersonal',"WHERE ppersonal_id={$this->post['ppersonalId']}"));
			// STRING PARA CONSULTAR DATOS DE PERSONAL
			$strLeader=$this->db->selectFromView('subordinados',"WHERE fk_subordinado_id={$staffData['fk_puesto_id']}");
			// VALIDAR SI HA SIDO ASIGNADO EL JEFE INMEDIATO
			if($this->db->numRows($strLeader)>0){
			    
			    
			    // DATOS PARA VERIFICAR PERSONAL OPERATIVO
			    $platoonStaff=$this->validateStaffInPlatoon($personalData['fk_personal_id']);
			    
			    
			    // VALIDAR PERSONAL EN PELOTONES
			    if($platoonStaff['status'] && $this->setFormatDate($this->fieldList['fields'][$this->fieldList['range']['fromDate']]['value'],'Y-m-d')>$this->setFormatDate('2020-01-06','Y-m-d')){
			        
			        // IMPRESION DE FIMRAS DE RESPONSABLES
			        $auxData['FIRMAS_RESPONSABLES_HOJATURA']=$this->varGlobal['EXPORTDATA_TEMPLATE_ROADMAP_OPERATIVOS'];
			        // IMPRESION DE UNIDAD DE OPERACIONES
			        $auxData['ee_nombre']='BRO 4. FARIAS BRIONES PAUL DIEGO';
			        $auxData['ee_definicion']='RESPONSABLE DE LA UNIDAD DE OPERACIONES, ENCARGADO';
			        
			    }
			    
			    
				// DATOS DEL SUBORDINADO
				$subordinado=$this->db->findOne($strLeader);
				// DATOS DE JEFE INMEDIATO
				if ($subordinado['fk_superior_id'] == 16){ //SOLO PARA BOMBEROS 4
					$subordinado=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE fk_puesto_id={$subordinado['fk_superior_id']} AND fk_estacion_id={$platoonStaff['data']['fk_estacion_id']} AND ppersonal_estado='EN FUNCIONES'"));
				} else {
					$subordinado=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE fk_puesto_id={$subordinado['fk_superior_id']} AND ppersonal_estado='EN FUNCIONES'"));
				}
				// INGRESO DE DATOS
				$auxData['jf_nombre']=$subordinado['personal_nombre'];
				$auxData['jf_definicion']=$subordinado['puesto_definicion'];

				// if ($staffData['personal_modalidad'] == 'OPERATIVO' ){
				// 	// IMPRESION DE FIMRAS DE RESPONSABLES
				// 	$auxData['FIRMAS_RESPONSABLES_HOJATURA']=$this->varGlobal['EXPORTDATA_TEMPLATE_ROADMAP_OPERATIVOS'];
				// 	// IMPRESION DE UNIDAD DE OPERACIONES
				// 	$auxData['ee_nombre']='BRO 4. FARIAS BRIONES PAUL DIEGO';
				// 	$auxData['ee_definicion']='RESPONSABLE DE LA UNIDAD DE OPERACIONES, ENCARGADO';

				// 	$auxData['jf_nombre']= 'SOLÓRZANO ZAMBRANO FÉLIX RAMÓN';
				// 	$auxData['jf_definicion']= 'SUBJEFE DE BOMBEROS, ENCARGADO';
				// }

// 				$auxData['jf_nombre']='ING. PARRA CHAVEZ HUGO JAVIER';
// 				$auxData['jf_definicion']='DIRECTOR GENERAL';

// 				$auxData['jf_nombre']='ING. RODRIGUEZ HINOJOSA IVAN JHESMANY';
// 				$auxData['jf_definicion']='RESPONSABLE DE LA UNIDAD DE PREVENCIÓN E INGENIERÍA DEL FUEGO';
				
			}
			
			// DATOS DE PERSONAL
			$this->pdfCtrl->loadSetting($personalData);
    		
		}
		
		
		// IMPRESIÓN DE DATOS AUXILIARES
		$this->pdfCtrl->loadSetting($auxData);
		
		
		// LOAD DATA LOCAL// DATOS DEL SISTEMA Y PARÁMETROS
		$this->pdfCtrl->loadSetting(array_merge($this->getConfParams(),$config));
		$this->pdfCtrl->loadSetting($this->getParamsModule('ALL'));
		// RANGO DE FECHAS
		$this->pdfCtrl->loadSetting(array(
			'fromDate'=>$this->fieldList['fields'][$this->fieldList['range']['fromDate']]['value'],
			'toDate'=>$this->fieldList['fields'][$this->fieldList['range']['toDate']]['value']
		));
		
		// PLANTILLA
		$this->pdfCtrl->template='templateExportData';
		// PRINT PDF
		$this->pdfCtrl->printReport($config,"");
		
	}
	
}