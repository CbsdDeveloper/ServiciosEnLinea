<?php namespace model\Tthh\surveys;
use api as app;

/**
 * @author Lalytto
 *
 */
class evaluationModel extends app\controller {
	
	/*
	 * VARIABLES GLOBALES
	 */
	private $entity='evaluacionpersonal';
	private $config;
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * CONSULTAR PERMISO POR CÓDIGO
	 */
	private function requestByStaff($staffId){
	    
	    // INFORMACIÓN DEL PERSONAL
	    $staff=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE personal_id={$staffId} AND estado='EN FUNCIONES'"));
	    $staffInfo=$this->db->findOne($this->db->selectFromView('vw_basic_personal',"WHERE personal_id={$staffId}"));
	    
	    // BUSCAR LAS EVALUACIONES MAYOR A LA FECHA ACTUAL QUE NO SE HAYA INSCRITO EL PERSONAL
	    $strTest=$this->db->selectFromView($this->db->setCustomTable($this->entity,'fk_evaluacion_id'),"WHERE fk_evaluado_id={$staffId}");
	    // BUSCAR LAS EVALUACIONES MAYOR A LA FECHA ACTUAL QUE NO SE HAYA INSCRITO EL PERSONAL
	    $str=$this->db->selectFromView("evaluacionespersonal","WHERE CURRENT_TIMESTAMP BETWEEN evaluacion_inicio AND evaluacion_cierre AND evaluacion_id NOT IN ({$strTest})");
    	
	    // CANTIDAD DE PRUEBAS
	    $count=$this->db->numRows($str);
	    
	    // RECORRER PRUEBAS PARA INSCRIBIRSE
	    foreach($this->db->findAll($str) as $v){
	        
	        // GENERAR MODELO DE INSCRIPCION
	        $temp=array(
	            'fk_evaluado_id'=>$staffId,
	            'fk_evaluacion_id'=>$v['evaluacion_id'],
	            'test_estado'=>'INSCRITO',
	            
	            'evaluado_fechainscripcion'=>$this->getFecha('dateTime'),
	            'evaluado_correo'=>$staffInfo['personal_correo_institucional'],
	            'evaluado_telefonos'=>"{$staffInfo['persona_telefono']} {$staffInfo['persona_celular']}",
	            'evaluado_edad'=>"{$staffInfo['edad']} ({$staffInfo['persona_fnacimiento']})",
	            'evaluado_cargo'=>$staff['puesto_definicion']
	        );
	        // INSERTAR REGISTRO
	        $this->db->executeTested($this->db->getSQLInsert($temp,$this->entity));
	    }
	    
	    // MENSAJE PARA RETORNO
	    $msg=($count>0)?"Se ha registrado exitosamente en {$count} nueva(s) evaluacion(es).":"No se han encontrado nuevas evaluaciones por el momento.";
			
		// RETORNAR CONSULTA POR DEFECTO
	    $this->getJSON($this->setJSON($msg,($count>0)?true:'info'));
	}
	
	/*
	 * CONSULTAR PERSONAL PARA VERIFICAR DISPONIBILIDAD DE PERMISOS
	 */
	public function requestEntity(){
		// CONSULTAR POR SESSION DE USUARIO
	    if(isset($this->post['account']) && $this->post['account']=='tthh') $json=$this->requestByStaff($this->getStaffIdBySession());
		// RETORNO POR DEFECTO
		else $this->getJSON("No ha especificado el recurso que desea consumir..!");
		// RETORNAR DATOS
		$this->getJSON($this->setJSON("Ok",true,$json));
	}
	
	
	/*
	 * IMPRESION DE RESPUESTAS DE EVALUACION
	 */
	private function getAnswer($testId){
	    // RESULTADO
	    $list="";
	    // LISTADO DE RESPUESTAS
	    foreach($this->db->findAll($this->db->selectFromView('vw_evaluacionpersonal_respuestas',"WHERE fk_test_id={$testId} ORDER BY pregunta_index")) as $v){
    	    $list.="<tr><td><b>{$v['pregunta_index']}</b>. {$v['pregunta_descripcion']}</td><td>{$v['respuesta_resultado']}<br>{$v['respuesta_comentario']}</td></tr>";
	    }
	    // RETORNAR LISTADO
	    return $list;
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
	    // STRING DE CONSULTA
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE test_id={$this->get['id']}");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)>0){
		    
			// DATOS DE PERMISO
			$row=$this->db->findOne($str);
			
			// DATOS DE EVALUACION
			$pdfCtrl->loadSetting($this->db->findById($row['fk_evaluacion_id'],'evaluacionespersonal'));
			
			// IMPRIMIR DATOS DE EALUACION
			$row['answerList']=$this->getAnswer($row['test_id']);
			
			// DATOS DE ENTIDAD
			$pdfCtrl->loadSetting($row);
			
			// *** CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// RETORNAR CONSULTA
			return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
		}	return '';
	}
	
	/*
	 * LISTADO DE EVALUACIONES
	 */
	private function getStaffAlertList($evaluationId){
	    // LISTADO
	    $list="";
	    // RECORRRER LISTADO
	    foreach($this->db->findAll($this->db->selectFromView('vw_evaluacionpersonal_alerta',"WHERE fk_evaluacion_id={$evaluationId} ORDER BY evaluado_fechaevaluacion")) as $k=>$v){
	        $i=$k+1;
	        $list.="<tr>
				<td>{$i}</td>
				<td>{$v['evaluado']}<br><small>C.C.: {$v['evaluado_cc']}<br>{$v['evaluado_cargo']}<br>Tel: {$v['evaluado_telefonos']}<br>E-mail: {$v['evaluado_correo']}</small></td>
				<td>{$v['evaluado_fechainscripcion']}</td>
				<td>{$v['evaluado_fechaevaluacion']}</td>
			</tr>";
	    }
	    // RETURN
	    return $list;
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printAlerts($pdfCtrl,$config){
	    // STRING DE CONSULTA
	    $str=$this->db->selectFromView("vw_evaluacionespersonal","WHERE evaluacion_id={$this->get['id']}");
	    // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        
	        // DATOS DE PERMISO
	        $row=$this->db->findOne($str);
	        
	        // DATOS DE ENTIDAD
	        $pdfCtrl->loadSetting($row);
	        
	        // DATOS DE ENTIDAD
	        $pdfCtrl->loadSetting(array('staffList'=>$this->getStaffAlertList($row['evaluacion_id'])));
	        
	        // DATOS DE FORMULARIO
	        $pdfCtrl->loadSetting($this->db->findById($row['fk_formulario_id'],'formulariosevaluaciones'));
	        
	        // *** CUSTOM TEMPLATE
	        $pdfCtrl->template=$config['reporte_template'];
	        // SETTEAR NOMBRE ALTERNO
	        $pdfCtrl->setCustomHeader($row,'evaluacionespersonal');
	    } else {
	        // RETORNAR CONSULTA
	        return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
	    }	return '';
	}
	
	/*
	 * LISTADO DE EVALUACIONES
	 */
	private function getStaffList($evaluationId){
	    // LISTADO
	    $list="";
	    // RECORRRER LISTADO
	    foreach($this->db->findAll($this->db->selectFromView('vw_evaluacionpersonal',"WHERE fk_evaluacion_id={$evaluationId} ORDER BY evaluado_fechaevaluacion")) as $k=>$v){
	        $i=$k+1;
	        $list.="<tr>
				<td>{$i}</td>
				<td>{$v['evaluado']}<br><small>C.C.: {$v['evaluado_cc']}<br>{$v['evaluado_cargo']}</small></td>
				<td>{$v['evaluado_fechainscripcion']}</td>
				<td>{$v['evaluado_fechaevaluacion']}</td>
			</tr>";
	    }
	    // RETURN 
	    return $list;
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
	    // STRING DE CONSULTA
	    $str=$this->db->selectFromView("vw_evaluacionespersonal","WHERE evaluacion_id={$id}");
	    // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        // OBTENER DATOS DE PERMISO
	        $row=$this->db->findOne($str);
	        
	        // DATOS DE ENTIDAD
	        $pdfCtrl->loadSetting($row);
	        
	        // DATOS DE ENTIDAD
	        $pdfCtrl->loadSetting(array('staffList'=>$this->getStaffList($row['evaluacion_id'])));
	        
	        // *** CUSTOM TEMPLATE
	        $pdfCtrl->template=$config['reporte_template'];
	        // SETTEAR NOMBRE ALTERNO
	        $pdfCtrl->setCustomHeader($row,'evaluacionespersonal');
	    } else {
	        // RETORNAR CONSULTA
	        return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
	    }	return '';
	}
	
}