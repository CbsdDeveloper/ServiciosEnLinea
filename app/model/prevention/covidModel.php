<?php namespace model\prevention;
use model as mdl;

class covidModel extends mdl\personModel {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	private $entity='bioseguridad_covid19';
	
	/*
	 * CONSTRUCTOR
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	
	/*
	 * REGISTRAR RECURSOS DEL FORMULARIO
	 */
	private function setResources($entityId,$resources){
	    // ELIMINAR RECURSOS EXISTENTES
	    $this->db->executeTested($this->db->getSQLDelete('covid19_recursos',"WHERE fk_bioseguridad_id={$entityId}"));
	    
	    // RECORRER LISTADO DE RECURSOS
	    foreach($resources as $k=>$v){
	        // GENERAR MODELO TEMPORAL
	        $v['fk_bioseguridad_id']=$entityId;
	        $v['fk_recurso_id']=$k;
	        // REALIZAR REGISTRO
	        $this->db->executeTested($this->db->getSQLInsert($v,'covid19_recursos'));
	    }
	}
	
	/*
	 * INGRESO DE PLANES DE EMERGENCIA PARA LOCALES
	 */
	public function insertEntity(){
	    
		// EMPEZAR TRANSACCIÓN
		$this->db->begin();
		
		// STRING PARA CONSULTAR SI YA SE HA REGISTRADO EL PLAN
		$str=$this->db->selectFromView($this->entity,"WHERE fk_local_id={$this->post['fk_local_id']} ORDER BY bioseguridad_id DESC LIMIT 1");
		// VERIFICAR SI EL REGISTRO EXISTE
		if($this->db->numRows($str)>0){
			
			// OBTENER DATOS DE PLAN
			$row=$this->db->findOne($str);
			// ACTUALIZAR MODELO
			$sql=$this->db->executeTested($this->db->getSQLUpdate(array_merge($row,$this->post),$this->entity));
			// ID DE ENTIDAD
			$entityId=$row['bioseguridad_id'];
			
		}else{
		    
		    // REGISTRAR MODELO
		    $sql=$this->db->executeTested($this->db->getSQLInsert($this->post,$this->entity));
		    // ID DE ENTIDAD
		    $entityId=$this->db->getLastID($this->entity);
		    
		}
		
		// REGISTRO DE ID DE ENTIDAD
		$sql['data']=array('id'=>$entityId);
		
		// INSERTAR RECURSOS
		$this->setResources($entityId, $this->post['resources']);
		
		// CERRAR TRANSACCIÓN y RETORNAR DATOS A UI
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printDetail($pdfCtrl,$config){
		// RECIBIR DATOS GET - URL
		$get=$this->requestGet();
		// STRING DE CONSULTA PARA REGISTRO
		$str=$this->db->selectFromView("vw_{$this->entity}","WHERE plan_id={$get['id']}");
		// VALIDAR SI EXISTE REGISTRO
		if($this->db->numRows($str)>0){
			// OBTENER DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['plan_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity);
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe la solicitud</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
	/*
	 * IMPRIMIR REGISTRO POR ID
	 */
	public function printById($pdfCtrl,$id,$config){
		// GENERAR STRING DE CONSULTA
		$str=$this->db->selectFromView("{$this->entity}","WHERE bioseguridad_id={$id}");
		// VALIDAR SI EXISTE EL REGISTRO
		if($this->db->numRows($str)>0){
			
			// DATOS DE REGISTRO
			$row=$this->db->findOne($str);
			
			$row['bioseguridad_fregistro_alt']=$this->setFormatDate($row['bioseguridad_fregistro'],'complete');
			
			// INFORMACION DE ENTIDAD Y ESTABLECIMIENTO
			$local=$this->db->findOne($this->db->selectFromView('vw_locales',"WHERE local_id={$row['fk_local_id']}"));
			$row['entidad_ruc']=$local['entidad_ruc'];
			
			// INFORMACON DE LOCAL
			$pdfCtrl->loadSetting($local);
			
			// IMRPESION DE RECURSOS
			$str=$this->db->selectFromView('vw_covid19_recursos',"WHERE fk_bioseguridad_id={$id} AND formulario_aplicacion='SI' ORDER BY recurso_nombre");
			$row['protocolList']=($this->db->numRows($str)>0)?"":"<tr><td colspan=2>NINGUNO</td></tr>";
			foreach($this->db->findAll($str) as $v){
			    $row['protocolList'].=
			        "<tr>
                        <td><img src='src/img/requirements/{$v['recurso_imagen']}' style='width:64px;height:64px;' /></td>
    			         <td style='vertical-align:top;'><span>{$v['recurso_nombre']}</span><BR><b>Ubicación: </b>{$v['formulario_descripcion']}</td>
    			    </tr>";
			}
			
			$str=$this->db->selectFromView('vw_covid19_recursos',"WHERE fk_bioseguridad_id={$id} AND formulario_aplicacion NOT IN ('SI') ORDER BY recurso_nombre");
			$row['protocolAvoidList']=($this->db->numRows($str)>0)?"":"<tr><td colspan=2>NINGUNO</td></tr>";
			foreach($this->db->findAll($str) as $v){
			    $row['protocolAvoidList'].=
			        "<tr>
                        <td><img src='src/img/requirements/{$v['recurso_imagen']}' style='width:64px;height:64px;' /></td>
    			         <td style='vertical-align:top;'><span>{$v['recurso_nombre']}</span><BR><b>Justificación: </b>{$v['formulario_descripcion']}</td>
    			    </tr>";
			}
			
			// IMPRESION DE INFORMACION
			$pdfCtrl->loadSetting($row);
			
			// CUSTOM TEMPLATE
			$pdfCtrl->template=$config['reporte_template'];
			// GET BARCODE
			$pdfCtrl->setBQCode('barcode',$row['bioseguridad_codigo']);
			// SETTEAR NOMBRE ALTERNO
			$pdfCtrl->setCustomHeader($row,$this->entity,'bioseguridad_covid19');
		} else {
			// PLANTILLA PARA REPORTE NO ENCONTRADO
			$pdfCtrl->loadSetting(array('listEmptyReport'=>'<li>No existe el registro</li>','usuario'=>'usuario'));
			$pdfCtrl->template='emptyReport';
		}	return '';
	}
	
}