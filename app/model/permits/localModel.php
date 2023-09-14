<?php namespace model\permits;
use api as app;
use api;
/**
 * Description of controller
 *
 * @author Lalytto
 * @mail apinango@lalytto.com
 */
class localModel extends app\controller {
	
	private $entity='locales';
	private $limitSync = 50001;
	private $offsetSync = 75000;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * VALIDAR INSPECCIÓN
	 */
	private function vaidateInspection($entityId,$localId=0){
		// ESTADO DE VALIDACION
		$status=false;
		// VARIABLE PARA ADJUNTR MENSAJE DE RESPUESTA
		$msg="";
		
		// VALIDAR LA CONSULTA - STRING PARA CONSULTAR INSPECCIONES PENDIENTES
		if($localId>0){
			// OBTENER INFORMACION DE ESTABLECIMIENTO
			$local=$this->db->viewById($localId,$this->entity);
			// VALIDAR EXISTENCIA DE PRORROGA
			if($local['prorroga_id']>0) return true;
			// VALIDAR SI SE CANCELA INSPECCION  
			if(($local['plan_id']>0 && !in_array($local['plan_status'],['SIN INSPECCION','SUSPENDIDO','APROBADO'])) || ($local['inspeccion_id']>0 && !in_array($local['inspeccion_status'],['SIN INSPECCION','APROBADO']))) $status=true;
			// VALIDAR ESTADO DE INSPECCION  
			if($local['inspeccion_id']>0 && !in_array($local['inspeccion_status'],['SIN INSPECCION','APROBADO'])) $msg.="<br>INSPECCION {$local['inspeccion_code']}<br><b>{$local['inspeccion_status']}</b>";
			// VALIDAR ESTADO DE PLAN DE EMERGENCIA
			if($local['plan_id']>0 && !in_array($local['plan_status'],['SIN INSPECCION','SUSPENDIDO','APROBADO'])) $msg.="<br>PLAN DE EMERGENCIA {$local['plan_code']}<br><b>{$local['plan_status']}</b>";
		} else {
			// BUSCAR ESTABLECIMIENTOS QUE TENGA INSPECCIONES PENDIENTES Y NO TENGAN PRORROGA
			$str=$this->db->selectFromView('vw_inspecciones',"WHERE fk_entidad_id={$entityId} AND inspeccion_estado NOT IN ('APROBADO')");
		}
		
		// VALIDAR ESTADO DE INSPECCIONES
		if($status) $this->getJSON($this->varGlobal['LOCAL_INSPECTIONS_REQUIRED']."{$msg}");
	}
	
	/*
	 * VALIDAR EL INGRESO DE DATOS
	 */
	public function interceptor($tb='',$method=''){
	    // VALIDAR MODULO DE DESARROLLO
	    if(api\session::$sysName=='prevencion'){
    		// VALIDAR SI HA SIDO SELECCIONADO LA MACROACTIVIDAD
    		if(!isset($this->post['fk_actividad_id']) || $this->post['fk_actividad_id']<1) $this->getJSON('Para continuar con el registro debe seleccionar una <b>actividad económica</b> del listado del formulario.');
    		// VALIDAR SI HA SIDO SELECCIONADO LA ACTIVIDAD CIIU
    		if(!isset($this->post['fk_ciiu_id']) || $this->post['fk_ciiu_id']<1) $this->getJSON('Para continuar con el registro debe seleccionar una <b>Actividad comercial (CIIU v4)</b> del listado del formulario.');
    		// VALIDAR QUE LA ACTIVIDAD CIIU ESTÁ DENTRO DE LA MACRO-ACTIVIDAD
    		if($this->db->numRows($this->db->selectFromView('vw_ciiu',"WHERE ciiu_id={$this->post['fk_ciiu_id']} AND fk_actividad_id={$this->post['fk_actividad_id']}"))<1){
    			$this->getJSON("La <b>Actividad comercial (CIIU v4)</b> seleccionada no corresponde al listado de las <b>actividades económicas</b> del formulario, por favor selecciona nuevamente!");
    		}
	    }
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */
	public function insertLocal(){
		// VALIDAR LA EXITENCIA DE UNA INSPECCIÓN EN PROCESO
		$this->vaidateInspection($this->post['fk_entidad_id']);
		// INTERCEPTOR
		$this->interceptor();
		// GENERAR STRING DE CONSULTA
		$json=$this->db->executeSingle($this->db->getSQLInsert($this->post,$this->entity));
		// OBTENER ID DE REGISTRO
		$json['data']=array('localId'=>$this->db->getLastID($this->entity));
		// RETORNAR CONSULTA
		$this->getJSON($json);
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */
	public function updateLocal(){
		// VALIDAR SUSPENSION DE ACTIVIDAD && VALIDAR LA EXITENCIA DE UNA INSPECCIÓN EN PROCESO
	    if(api\session::$sysName=='prevencion' && $this->post['local_estado']!='SUSPENDIDO') $this->vaidateInspection($this->post['fk_entidad_id'],$this->post['local_id']);
		
		// INTERCEPTOR
		$this->interceptor();
		
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// VALIDAR Y CARGAR ARCHIVO
		$this->post=$this->uploaderMng->decodeBase64ToImg($this->post,'locales',false,$this->post['local_id']);
		// GENERAR STRING DE ACTUALIZACIÓN Y EJECUTAR SENTENCIA
		$json=$this->db->executeTested($this->db->getSQLUpdate($this->post,'locales'));
		
		// VALIDAR ENVIO DE COORDENADAS
		if(isset($this->post['coordenada_marks'])){
			// SET MAPA DE COORDENADAS
			$json=$this->setMapCoordinates('locales',$this->post['local_id'],$this->post);
			// SET GEOJSON
			$json=$this->setGeoJSON('locales',$this->post['local_id'],$this->post['coordenada_marks']);
		}
		
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($json));
	}
	
	
	
	/*
	 * ACTUALIZAR RECURSOS DEL ESTABLECIMIENTO
	 */
	public function uploadAnnexes(){
	    // MENSAJE POR DEFECTO
	    $sql=$this->setJSON("¡No se ha recibido ningun archivo adjunto!",true);
	    $model=array();
	    
	    // DATOS DE REGISTRO
	    $entity=$this->db->viewById($this->post['local_id'],$this->entity);
	    
	    // CARGAR REGISTRO SENESCYT
	    if($this->post['annexeEntity']=='establecimiento'){
	        
	        // PARAMETROS DE CARGA
	        $uploadVars=$this->uploaderMng->setParamsToUploadToFolder("{$this->post['local_id']}","local-{$this->post['local_id']}",'local_file',0);
	        
	        // CARGAR ARCHIVO
	        $file=$this->uploaderMng->uploadFileToFolder($this->post,$this->post['annexeEntity'],$uploadVars);
	        // GENERAR MODELO TEMPORAL
	        $model=array(
	            'local_id'=>$entity['local_id'],
	            'local_certificadostablecimiento'=>$file
	        );
	        // ACTUALIZAR DATOS DE ENTIDAD
	        $sql=$this->db->executeTested($this->db->getSQLUpdate($model,'locales'));
	        
	    }
	    
	    // ADJUNTAR DATOS DE ARCHIVO ADJUNTO
	    if($sql['estado']) $sql['data']=$model;
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
	    
	}
	
	
	
	/*
	 * CONSULTAR REGISTRO POR ID
	 */
	private function requestById($id){
		// INFORMACIÓN DE REGISTRO
		$row=$this->db->viewById($id,$this->entity);
		// INFORMACIÓN DE CIIU
		$row=array_merge($row,$this->db->viewById($row['fk_ciiu_id'],'ciiu'));
		// INFORMACIÓN DE MACROACTIVIDAD / TASAS PRESUPUESTARIAS
		$row['activity']=$this->db->findOne($this->db->selectFromView('vw_tasas',"WHERE tasa_id={$row['fk_tasa_id']}"));
		// RETORNAR CONSULTA
		return $row;
	} 
	
	/*
	 * BUSCAR POR ACTIVIDAD ECTIVIDAD ECONÓMICA
	 */
	private function requestByActivity($activityId){
		$tb=$this->db->setCustomTable("vw_ciiu",'ciiu_id','permisos');
		$strActivity=$this->db->selectFromView($tb,"WHERE fk_actividad_id={$activityId}");
		// BUSCAR LOCALES POR ACTIVIDAD
		$str=$this->db->selectFromView("vw_permisos","WHERE (date_part('year',permiso_fecha)=date_part('year',CURRENT_DATE)) AND fk_ciiu_id IN ({$strActivity}) AND fk_entidad_id=".app\session::get());
		return $this->db->findAll($str);
	}
	
	/*
	 * CONSULTAR REGISTRO
	 * + ai: detalle de local y paso 1 de AI
	 */
	public function requestLocal(){
		// CONSULTAR POR ID
		if(isset($this->post['id'])) $json=$this->requestById($this->post['id']);
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("ok",true,$json));
	}
	
	
	/*
	 * GUARDAR SINCRONIZACIÓN EN BASE DE DATOS
	 */
	private function processSync($rows){
		set_time_limit(2000);
		$queryTotal=0;
		$forTotal=0;
		$insertEntity=0;
		$updateEntity=0;
		$tb='locales';
		$this->db->begin();
		$sql=$this->setJSON("ERRORES EN SINCRONIZACIÓN");
		foreach($rows as $val){
			//$strEntidad=$this->db->getSQLSelect('entidades',[],"where entidad_id={$val['fk_entidad_id']}");
			$strEntidad="SELECT entidad_id FROM permisos.tb_entidades WHERE entidad_migracion_id={$val['fk_entidad_id']}";
			if($this->db->numRows($strEntidad)>0){
				// OBTENER ID DE ENTIDAD DESDE RECORDS -> CAMBIOS EFECTUADOS -> POR SUPUESTA ACTUALIZACIÓN DE DATOS -> DUPLICADOS EN SISWEB
				$idEntidad=$this->db->findOne($strEntidad);
				$val['fk_entidad_id']=$idEntidad['entidad_id'];
				// SETTEAR DATOS IMPORTANTES
				if($val['local_principal']=='') $val['local_principal']='S/N';
				if($val['local_nombrecomercial']=='') $val['local_nombrecomercial']='S/N';
				if($val['local_actividad_economica']=='') $val['local_actividad_economica']='S/N';
				// CONVERTIR CIIU v3 -> CIIU v4
				if($val['fk_ciiu_id']!=''){
					$str=$this->db->getSQLSelect('ciiu',[],"where ciiu_codigo_v3='{$val['fk_ciiu_id']}'");
					if($this->db->numRows($str)>0){
						$ciiu=$this->db->findOne($str);
						$val['fk_ciiu_id']=$ciiu['ciiu_id'];
					} else $val['fk_ciiu_id']='25';
				}	extract($val);
				// GENERAR INSERT - UPDATE
				$str=$this->db->getSQLSelect($tb,[],"where local_id={$val['local_id']}");
				if($this->db->numRows($str)>0){
					$row=$this->db->findAll($str);
					$str2=$this->db->getSQLUpdate(array_merge($row,$val),$tb);
					$updateEntity++;
				} else {
					$str2=$this->db->getSQLInsert($val,$tb);
					$insertEntity++;
				}	$sql=$this->db->executeSingle($str2);
				if(!$sql['estado']){
					$sql['mensaje'].="<br>Error provocado en registro con código -> {$val['local_id']}";
					$this->getJSON($this->db->closeTransaction($sql));
				} $queryTotal++;
			} $forTotal++;
		}	$this->db->closeTransaction($sql);
		$result="Consulta total: $queryTotal, <br> Total ingresados: $insertEntity, <br>Total actualizados: $updateEntity, <br> Total no encontrados: ".($forTotal-$queryTotal);
		$date=$this->getFecha('Y.m.d-H.i.s');
		$id=app\session::get();
		$rows['result']=$result;
		$this->setConfig($rows,"locales-by-$id-on-$date-{$this->limitSync}-{$this->offsetSync}");
		$this->getJSON($result);
	}
	
	/*
	 * SINCRONIZAR BDs
	 */
	public function syncLocales(){
		$this->getJSON('Error 401');
		app\session::$sysName='sisweb';
		$siswebConnect=new app\controller;
		$str="SELECT  
					  l.id local_id,
					  l.idubicacion fk_ubicacion_id,
					  (SELECT codigo FROM ciiunivel7 WHERE id=ciu7 AND ciu7>0) fk_ciiu_id,
					  l.idempresa fk_entidad_id,
					  
					  ltrim(l.actividadeconomica,' ') local_actividad_economica,
					  ltrim(l.nombrecomercial,' ') local_nombrecomercial,
					  l.primaria local_principal,
					  l.secundaria local_secundaria,
					  l.referencia local_referencia,
					  l.noestablecimiento local_numero,
					  (l.telefono1||'  '||l.telefono2) local_telefono,
					
					  l.clavecatastral local_clave_catastral,
					  
					  l.area local_area 
					FROM locales l
					INNER JOIN empresas e ON e.id=l.idempresa 
					WHERE (l.ciu7>0 AND l.area>0 AND l.idubicacion>0) AND (l.id BETWEEN $this->limitSync AND $this->offsetSync)
					ORDER BY l.idempresa ASC, l.actividadeconomica ASC, l.nombrecomercial ASC";
		$this->processSync($siswebConnect->findAll($str));
	}
	

	/*
	 * IMPRIMIR SOLICITUD - PREVIO A CERRAR ACIVIDAD
	 */
	public function downloadFormulario($pdfCtrl,$id,$config){
		if(!isset($id))$this->getJSON('no reporte');
		$permiso=$this->db->findOne($this->db->selectFromView("vw_{$this->entity}","WHERE local_id={$id}"));
		$permiso['local_principal']=strtoupper($permiso['local_principal']);
		$permiso['local_secundaria']=strtoupper($permiso['local_secundaria']);
		$permiso['local_referencia']=strtoupper($permiso['local_referencia']);
		$pdfCtrl->loadSetting($permiso);
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		// FECHA
		$pdfCtrl->loadSetting(array('today'=>$this->getFecha()));
		return '';
	}
	
	/*
	 * IMPRIMIR COMPROBANTE - ACIVIDAD CERRADA
	 */
	public function printById($pdfCtrl,$id,$config){
		if(!isset($id))$this->getJSON('no reporte');
		$row=$this->db->viewById($id,$this->entity);
		$row['local_estado']=($row['local_estado']=='SUSPENDIDO')?
			"<span style='color:red'>SUSPENDIDO - # SOLICITUD {$row['local_tipo']}</span>":
			"<span style='color:green'>ACTIVO</span>";
		$pdfCtrl->loadSetting($row);
		$pdfCtrl->loadSetting($this->db->findOne($this->db->selectFromView('vw_usuarios',"WHERE usuario_login='{$row['usuario']}'")));
		$row=$this->db->findOne($this->db->selectFromView('actividades',"WHERE actividad_id={$row['fk_actividad_id']}"));
		$pdfCtrl->loadSetting($row);
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		// FECHA
		$pdfCtrl->loadSetting(array('today'=>$this->getFecha()));
		return '';
	} 
	
}