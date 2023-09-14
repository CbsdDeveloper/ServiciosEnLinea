<?php namespace model\permits;
use api as app;

class permitModel extends app\controller {
	
	private $entity='permisos';
	
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
	        $local=$this->db->viewById($localId,'locales');
	        // VALIDAR EXISTENCIA DE PRORROGA
	        if($local['prorroga_id']>0) return true;
	        // VALIDAR SI SE CANCELA INSPECCION
	        if(($local['plan_id']>0 && !in_array($local['plan_status'],['SIN INSPECCION','SUSPENDIDO','APROBADO'])) || ($local['inspeccion_id']>0 && !in_array($local['inspeccion_status'],['SIN INSPECCION','APROBADO']))) $status=true;
	        // VALIDAR ESTADO DE INSPECCION
	        if($local['inspeccion_id']>0 && !in_array($local['inspeccion_status'],['SIN INSPECCION','APROBADO'])) $msg.="<br>INSPECCION {$local['inspeccion_code']}<br><b>{$local['inspeccion_status']}</b>";
	        // VALIDAR ESTADO DE PLAN DE EMERGENCIA
	        if($local['plan_id']>0 && !in_array($local['plan_status'],['SIN INSPECCION','SUSPENDIDO','APROBADO'])) $msg.="<br>PLAN DE EMERGENCIA {$local['plan_code']}<br><b>{$local['plan_status']}</b>";
	    }
	    
	    // VALIDAR ESTADO DE INSPECCIONES
	    if($status) $this->getJSON($this->varGlobal['LOCAL_INSPECTIONS_REQUIRED']."{$msg}");
	}
	
	/*
	 * GUARDAR E IMPRIMIR PERMISO DE FUNCIONAMIENTO
	 */
	public function insertPermit(){
		// VALIDAR SI YA SE HA INGRESADO LAS SECUENCIA DE ESPECIES VALORADAS
		if($this->db->numRows($this->db->selectFromView('especies',"WHERE fecha_registro=CURRENT_DATE"))<1) $this->getJSON($this->varGlobal['UNSET_SPECIE']);
		
		// COMENZAR TRANSACCION
		$this->db->begin();
		
		// VALIDAR NÚMERO DE SOLICITUD
		$this->post['numero_solicitud']=$this->testRequestNumber($this->entity,$this->post['numero_solicitud']);
		
		// STRING DE CONSULTA DE AUTOINSPECCION
		$str=$this->db->selectFromView("vw_autoinspecciones","WHERE UPPER(autoinspeccion_codigo)=UPPER('{$this->post['codigo_per']}') AND date_part('year',autoinspeccion_fecha)=date_part('year',CURRENT_DATE)");
		// CONSULTAR SI EXISTE AUTOINSPECCION 
		if($this->db->numRows($str)<1) $this->getJSON("La <b class='text-uppercase'>AutoInspeccion #{$this->post['codigo_per']}</b> no se encuentra registada en el sistema, intente nuevamente.");
		
		// OBTENER DATOS DE AUTOINSPECCION
		$row=$this->db->findOne($str);
		
		// VALIDAR INSPECCIONES
		$this->vaidateInspection($row['entidad_id'],$row['fk_local_id']);
		
		// DEFINIR COSTO DE PERMISOS
		$this->post['permiso_valor']=$this->varGlobal['SYS_VALUE_PERMIT'];
		// MODELO DE PERMISO
		$this->post=array_merge($this->post,array('fk_autoinspeccion_id'=>$row['autoinspeccion_id']));
		
		// GENERAR STRING DE INGRESO Y EJECUTAR CONSULTA
		$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		// PARAMETRIZAR ID DE PERMISO
		$sql['data']=array('id'=>$this->db->getLastID($this->entity));
		
		// CERRAR CONEXCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	/*
	 * MODIFICACIÓN DE ENTIDAD
	 */
	public function updateEntity(){
		// COMENZAR TRANSACCION
		$this->db->begin();
		// STRING PARA CONSULTAR SOLICITUDES DE REIMPRESIÓN
		$str=$this->db->selectFromView('reimpresiones',"WHERE fk_permiso_id={$this->post['permiso_id']} AND reimpresion_estado='GENERADO'");
		// VALIDAR SI EXISTE SOLICITUD DE REIMPRESIÓN
		if($this->db->numRows($str)<1){
			// INGRESAR SOLICITUD DE REIMPRESIÓN
			$this->post['fk_permiso_id']=$this->post['permiso_id'];
			$this->post['reimpresion_permiso']=$this->post['permiso_numero'];
			$sql=$this->db->executeTested($this->db->getSQLInsert($this->post,'reimpresiones'));
			// OBTENER DATOS DE PERMISO
			$permit=$this->db->findById($this->post['permiso_id'],$this->entity);
			// ACTUALIZAR CAMPOS DE PERMISO
			$permit['observacion']="REIMPRESIÓN DE PERMISO POR {$this->post['reimpresion_motivo']} : {$permit['permiso_numero']} -> {$this->post['reimpresion_formulario']}";
			$permit['permiso_numero']=$this->post['reimpresion_formulario'];
			// ACTUALIZAR DATOS DE PERMISO
			$sql=$this->db->executeTested($this->db->getSQLUpdate($permit,$this->entity));
		}else{
			// MENSAJE DE SOLICITUD YA INGRESADA 
			$sql=$this->setJSON("Ya existe una solicitud de reimpresión para este permiso!!",true);
		}
		// PARAMETRIZAR ID DE PERMISO
		$sql['data']=array('id'=>$this->post['permiso_id']);
		// CERRAR CONEXCIÓN Y RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * CONSULTAR POR ID
	 */
	private function requestById($id){
		// DATOS DE PERMISO
		$row=$this->db->viewById($id,$this->entity);
		// DATOS DE CIIU
		$ciiu=$this->db->viewById($row['fk_ciiu_id'],'ciiu');
		// RETORNAR CONSULTA
		return array_merge($row,$ciiu);
	}
	
	/*
	 * CONSULTAR POR CODIGO
	 */
	private function requestByCode($code){
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE UPPER(codigo_per)=UPPER('$code')");
		// VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
		if($this->db->numRows($str)<1) $this->getJSON('Al parecer no ha sido emitido este permiso de funcionamiento!');
		// OBTENER DATOS DEL PERMISO
		$row=$this->db->findOne($str);
		// VERIFICAR QUE EL PERMISO HAYA SIDO EMITIDO EN EL PRESENTE AÑO
		if($this->setFormatDate($row['permiso_fecha'],'Y')<>$this->getCurrentYear()) $this->getJSON("Solicitud no procesada, el <b>Permiso de Funcionamiento #{$row['codigo_per']}</b> ya no tiene vigencia para el presente periodo fiscal.");
		// VERIFICAR SI EXISTE UN DUPLICADO PENDIENTE
		$str=$this->db->selectFromView('vw_duplicados',"WHERE fk_permiso_id={$row['permiso_id']} AND (date_part('year',fecha_solicitado)=date_part('year',CURRENT_DATE)) ORDER BY fecha_solicitado DESC LIMIT 1");
		if($this->db->numRows($str)>0){
			// DATOS DE REGISTRO
			$row=array_merge($row,$this->db->findOne($str));
			// VERIFICAR SI LA SOLICITUD HA SIDO REGISTRADA
			if($row['duplicado_estado']=='PENDIENTE'){
				// VALIDAR PERMISO PARA APROBAR PERMISO Y GENERAR ORDEN DE COBRO
				if(!in_array(5092,$this->getAccesRol())) $this->getJSON('El proceso de duplicado ya se ha ingresado, por favor direccione el trámite con quien corresponda para que lo despache!');
				// DATOS PARA GENERAR ORDEN DE COBRO
				$row['entity']='duplicados';
				$row['entityId']=$row['duplicado_id'];
				// MODAL A DESPLEGAR
				$row['modal']='Generarordenescobro';
			}elseif($row['duplicado_estado']=='APROBADO'){
				// MENSAJE PARA NOTIFICAR QUE EL PROCESO NO HA SIDO DESPACHADO
				$this->getJSON("La solicitud ya se ha ingresado y se encuentra pendiente de impresión!");
			}else{
				// MODAL A DESPLEGAR
				$row['modal']='DuplicadosInput';
			}
		}else{
			// MODAL A DESPLEGAR
			$row['modal']='DuplicadosInput';
		}
		$row['edit']=false;
		// RETORNAR DATOS
		return $row;
	}
	
	/*
	 * OBTENER PERMISO DE FUNCIONAMIENTO POR CÓDIGO PER
	 */
	public function requestPermit(){
		// FORMULARIO DE ENVIO
		$post=$this->requestPost();
		// CONSULTAR POR ID
		if(isset($post['id'])) $json=$this->requestById($post['id']);
		// CONSULTAR POR CODIGO
		elseif(isset($post['code'])) $json=$this->requestByCode(strtoupper($post['code']));
		// CONSULTA POR DEFECTO
		else $this->getJSON("Especificar el recurso que requiere consumir.");
		// RETORNAR CONSULTA
		$this->getJSON($this->setJSON("ok",true,$json));
	}
	
	/*
	 * IMPRIMIR PERMISO DE FUNCIONAMIENTO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// VALIDAR SI EXISTE ID DE PERMISO
		if(!isset($id)) $this->getJSON('no reporte');
		// DATOS DE PERMISO
		$permiso=$this->db->viewById($id,$this->entity);
		// INICIAR TRANSACCION
		$this->db->begin();
		// RELACIÓN DE DUPLICADOS E IMPRESOS
		if(in_array($permiso['permiso_estado'],['IMPRESO','REIMPRESO','DUPLICADO'])){
			// DATOS DE SESIÓN
			$sessionId=app\session::getSessionAdmin();
			$user=$this->db->findById($sessionId,'usuarios');
			$today=$this->getFecha('dateTime');
			// CONSULTAR SI EXISTE UN DUPLICADO PENDIENTE
			$strDuplicado=$this->db->selectFromView('vw_duplicados',"WHERE fk_permiso_id={$id} AND duplicado_estado='APROBADO'");
			// BUSCAR REIMPRESIONES APROBADAS
			$strReprint=$this->db->selectFromView('reimpresiones',"WHERE fk_permiso_id={$id} AND reimpresion_estado='GENERADO'");
			// CONSULTAR ESTADO DE DUPLICADO
			if($this->db->numRows($strDuplicado)>0){
				// OBTENER DATOS DE DUPLICADO
				$duplicado=$this->db->findOne($strDuplicado);
				// ACTUALIZAR DATOS DE DUPLICADO
				$duplicado['duplicado_estado']='IMPRESO';
				$duplicado['fecha_impreso']=$today;
				$duplicado['fk_usuario_imprime']=$sessionId;
				// STRING DE ACTUALIZACIÓN Y EJECUTAR SENTENCIA PARA ACTUALIZAR REGISTRO DE DUPLICADO 
				$this->db->executeTested($this->db->getSQLUpdate($duplicado,'duplicados'));
				// ACTUALIZAR DATOS DE PERMISO - FECHA DE REIMPRESIÓN (DUPLICADO)
				$permiso['permiso_fecha']=$today;
				$permiso['usuario']=$user['usuario_login'];
				// ACTUALIZAR ESTADO DE PERMISO
				$permiso['permiso_estado']='DUPLICADO';
			}elseif($this->db->numRows($strReprint)>0){
				// OBTENER DATOS DE REIMPRESIÓN
				$reprint=$this->db->findOne($strReprint);
				// ACTUALIZAR DATOS DE DUPLICADO
				$reprint['reimpresion_estado']='IMPRESO';
				// STRING DE ACTUALIZACIÓN Y EJECUTAR SENTENCIA PARA ACTUALIZAR REGISTRO DE DUPLICADO
				$this->db->executeTested($this->db->getSQLUpdate($reprint,'reimpresiones'));
				// ACTUALIZAR DATOS DE PERMISO - FECHA DE REIMPRESIÓN (DUPLICADO)
				$permiso['permiso_fecha']=$today;
				$permiso['usuario']=$user['usuario_login'];
				// ACTUALIZAR ESTADO DE PERMISO
				$permiso['permiso_estado']='REIMPRESO';
			}else{
				// PERMISO EN BASE DE DATOS PARA REIMPRIMIR PERMISO
				if(!in_array(5082,$this->getAccesRol())) $this->getJSON('Ya se ha impreso este permiso, solicite un duplicado..');
				else { $permiso['permiso_estado']='REIMPRESO'; }
			}
		}else{ 
			$permiso['permiso_estado']='IMPRESO';
		} 
		// GENERAR STRING DE CONSULTA, EJECUTAR SENTENCIA Y CERRAR TRANSACCION
		$this->db->closeTransaction($this->db->executeTested($this->db->getSQLUpdate($permiso,$this->entity)));
		// DATOS DE LOCAL PARA IMPRIMIR
		$permiso['local_nombrecomercial']=substr($permiso['local_nombrecomercial'],0,250);
		$permiso['local_principal']=substr($permiso['local_principal'],0,35);
		$permiso['local_secundaria']=substr($permiso['local_secundaria'],0,35);
		$pdfCtrl->loadSetting($permiso);
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		// GET BARCODE
		$pdfCtrl->setBQCode('barcode',$permiso['codigo_per'],$permiso['codigo_per'],'40px');
		return '';
	}
	
}