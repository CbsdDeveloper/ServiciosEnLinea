<?php namespace model\logistics\gservices;
use api as app;

class minorToolsMaintenanceModel extends app\controller {
	
	/*
	 * VARIBALES DE ENTORNO
	 */
	private $entity='mantenimientos_menores';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */
	public function insertEntity(){
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // GENERAR STRING DE REGISTRO
	    $str=$this->db->getSQLInsert($this->post, $this->entity);
	    
	    // EJECUTAR SENTENCIA
	    $sql=$this->db->executeTested($str);
	    
	    // CONSULTAR ID DE REGISTRO
	    $sql['data']=array(
	        'entityId'=>$this->db->getLastID($this->entity)
	    );
	    
	    // VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
	    $this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * ACTUALIZAR REGISTROS
	 */
	public function updateEntity(){
	    
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // GENERAR STRING DE REGISTRO
	    $str=$this->db->getSQLUpdate($this->post, $this->entity);
	    
	    // EJECUTAR SENTENCIA
	    $sql=$this->db->executeTested($str);
	    
	    // ACTUALIZAR LOS DATOS DE HERRAMIENTAS
	    foreach($this->post['tools'] as $v){
	        // GENERAR STRING DE CONSULTA
	        $_str=$this->db->getSQLUpdate($v,'mantenimiento_herramientas');
	        // EJECUTAR CONSULTA
	        $sql=$this->db->executeTested($_str);
	    }
	    
	    // CONSULTAR ID DE REGISTRO
	    $sql['data']=array( 'entityId'=>$this->post['mantenimiento_id'] );
	    
	    // VALIDAR TRANSACCIÓN Y RETORNAR DATOS DE CONSULTA
	    $this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	
	
	/*
	 * DATOS DE RESONSABLES DE PROCCES
	 */
	private function getResponsibleByMaintenanceId($row){
	    
	    // INFORMACION DE PERSONAL QUE REGISTRA - SERVICIOS GENERALES
	    $gservices=$this->db->viewById($row['tecnico_servicios'],'personal');
	    $row['psg_nombre']="{$gservices['persona_titulo']} {$gservices['personal_nombre']}";
	    $row['psg_puesto']=$gservices['puesto_definicion'];
	    
	    // DIRECTOR ADMINISTRATIVO
	    $da=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['director_administrativo']}"));
	    $row['da_nombre']="{$da['persona_titulo']} {$da['personal_nombre']}";
	    $row['da_puesto']=$da['puesto_definicion'];
	    
	    // DIRECTOR ADMINISTRATIVO
	    $ga=$this->db->findOne($this->db->selectFromView('vw_personal',"WHERE ppersonal_id={$row['mantenimiento_guardalmacen']}"));
	    $row['ga_nombre']="{$ga['persona_titulo']} {$ga['personal_nombre']}";
	    $row['ga_puesto']=$ga['puesto_definicion'];
	    
	    // PARSE NÚMERO DE ORDEN
	    $row['mantenimiento_serie']=str_pad($row['mantenimiento_serie'],3,"0",STR_PAD_LEFT);
	    $row['mantenimiento_costo_alt']=$this->setNumberToLetter($row['mantenimiento_costo'],true);
	    $row['mantenimiento_fregistro']=$this->setFormatDate($row['mantenimiento_fregistro'],'complete');
	    $row['mantenimiento_fecha_mantenimiento']=$this->setFormatDate($row['mantenimiento_fecha_mantenimiento'],'complete');
	    $row['mantenimiento_fecha_recepcion']=$this->setFormatDate($row['mantenimiento_fecha_recepcion'],'complete');
	    
	    // REGRESAR DATOS COMPLETOS
	    return $row;
	    
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
	    // STRING DE CONSULTA
	    $str=$this->db->selectFromView("{$this->entity}","WHERE mantenimiento_id={$id}");
	    // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        
	        // OBTENER DATOS DE PERMISO
	        $row=$this->db->findOne($str);
	        
	        // ROW PARA DETALLE
	        $row['toolsList']='';
	        // LISTADO DE HERRAMIENTAS
	        $str=$this->db->getSQLSelect('mantenimiento_herramientas',['herramientasmenores'],"WHERE fk_mantenimiento_id={$row['mantenimiento_id']}");
	        // INSERTAR LISTADO
	        foreach($this->db->findAll($str) as $k=>$v){
	            $ix=$k+1;
	            $row['toolsList'].="<tr><td>{$ix}</td><td>Código: <b>{$v['herramienta_codigo']}</b><br>{$v['herramienta_descripcion']}</td><td>{$v['maintenance_tipo']}</td><td>{$v['maintenance_descripcion']}</td></tr>";
	        }
	        
	        // DATOS DE RESPONSABLES DEL REGISTRO
	        $row=$this->getResponsibleByMaintenanceId($row);
	        
	        
	        // CARGAR DATOS DE REGISTRO
	        $pdfCtrl->loadSetting($row);
	        
	        // CUSTOM TEMPLATE
	        $pdfCtrl->template=$config['reporte_template'];
	        // SETTEAR NOMBRE ALTERNO
	        $pdfCtrl->setCustomHeader($row,$this->entity);
	    } else {
	        // RETORNAR CONSULTA
	        return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
	    }	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO CON DETALLE
	 */
	public function printDetail($pdfCtrl,$config){
	    // GENERAR STRING DE CONSULTA
	    $str=$this->db->selectFromView("{$this->entity}","WHERE mantenimiento_id={$this->get['id']}");
	    // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        // OBTENER DATOS DE REGISTRO
	        $row=$this->db->findOne($str);
	        
	        // DATOS DE RESPONSABLES DEL REGISTRO
	        $row=$this->getResponsibleByMaintenanceId($row);
	        
	        // VALIDAR EL TIPO DE REPORTE
	        if(isset($this->get['opt'])){
	            
	            // ROW PARA DETALLE
	            $row['toolsList']='';
	            // LISTADO DE HERRAMIENTAS
	            $str=$this->db->getSQLSelect('mantenimiento_herramientas',['herramientasmenores'],"WHERE fk_mantenimiento_id={$row['mantenimiento_id']}");
	            // INSERTAR LISTADO
	            foreach($this->db->findAll($str) as $k=>$v){
	                $ix=$k+1;
	                $row['toolsList'].="<tr><td>{$ix}</td><td>Código: <b>{$v['herramienta_codigo']}</b><br>{$v['herramienta_descripcion']}</td><td>{$v['maintenance_tipo']}</td></tr>";
	            }
	            
	            // CUSTOM TEMPLATE
	            $pdfCtrl->template='mantenimientosmenoresrecepcionById';
	            
	        }else{
	            
	            // ROW PARA DETALLE
	            $row['toolsList']='';
	            // LISTADO DE HERRAMIENTAS
	            $str=$this->db->getSQLSelect('mantenimiento_herramientas',['herramientasmenores'],"WHERE fk_mantenimiento_id={$row['mantenimiento_id']}");
	            // INSERTAR LISTADO
	            foreach($this->db->findAll($str) as $k=>$v){
	                $ix=$k+1;
	                $row['toolsList'].="<tr><td>{$ix}</td><td>Código: <b>{$v['herramienta_codigo']}</b><br>{$v['herramienta_descripcion']}</td></tr>";
	            }
	            
	            // CUSTOM TEMPLATE
	            $pdfCtrl->template=$config['reporte_template'];
	            
	        }
	        
	        // CARGAR DATOS DE REGISTRO
	        $pdfCtrl->loadSetting($row);
	        // SETTEAR NOMBRE ALTERNO
	        $pdfCtrl->setCustomHeader($row,$this->entity);
	    } else {
	        // RETORNAR CONSULTA
	        return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
	    }	return '';
	}
	
	
}