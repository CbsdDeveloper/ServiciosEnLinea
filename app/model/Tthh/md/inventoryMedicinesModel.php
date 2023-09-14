<?php namespace model\Tthh\md;
use model as mdl;

class inventoryMedicinesModel extends mdl\personModel {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='inventario_medicamentos';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */
	public function insertEntity(){
		// MENSAAJE POR DEFECTOR
		$sql=$this->setJSON("No se han ingresado nuevos insumos!");
		
		// INICIAR TRANSACCION
		$this->db->begin();
		
		// RECORRER LISTADO DE INVENTARIO
		foreach($this->post as $v){
			
			if($v['inventario_cantidad']<>0){
				
				// GENERAR MODELO AUXILIAR
				$model=array(
					'fk_medicamento_id'=>$v['medicamento_id'],
					'inventario_transaccion'=>($v['inventario_cantidad'])>0?'INGRESO':'DESCARGO',
					'inventario_cantidad'=>abs($v['inventario_cantidad']),
					'inventario_descripcion'=>$v['inventario_descripcion']
				);
				
				// GENERAR STRING DE CONSULTA
				$sql=$this->db->executeTested($this->db->getSQLInsert($model,$this->entity));
				
			}
			
		}
		
		// RETORNAR CONSULTA
		$this->getJSON($this->db->closeTransaction($sql));
	}
	
	
	/*
	 * IMPRIMIR DE STOCK DE BODEGAS - ESTACIONES
	 */
	public function printStockTotal($pdfCtrl,$id,$config){
	    
	    // INVENTARIO DE MEDICAMENTOS
	    $inventory=$this->db->findAll($this->db->selectFromView('vw_medicamentos',"ORDER BY medicamento_nombre"));
	    
	    // INFORMACION DE DOCTOR
	    $row=$this->db->findOne($this->db->selectFromView('vw_ppersonal',"WHERE fk_puesto_id=39 AND ppersonal_estado='EN FUNCIONES' ORDER BY ppersonal_id DESC"));
	    // MODELO PARA IMPRESION DE DATOS
	    $row['listSinStock']="";
	    $row['listtock']="";
	    // INDEX DE LISTADO
	    $idx=0;
	    $ix=0;
	    
	    // IMPRESIÓN DE INSUMOS
	    foreach($inventory as $v){
	        // VALIDAR TIPO DE STOCK
	        if($v['total_medicamento']<10){
	            $idx+=1;
	            $row['listSinStock'].="<tr><td>{$idx}</td><td>{$v['medicamento_codigo']}. {$v['medicamento_nombre']}<br>{$v['medicamento_presentacion']}</td><td>{$v['total_medicamento']}</td></tr>";
	        }else{
	            $ix+=1;
    	        $row['listStock'].="<tr><td>{$ix}</td><td>{$v['medicamento_codigo']}. {$v['medicamento_nombre']}<br>{$v['medicamento_presentacion']}</td><td>{$v['total_medicamento']}</td></tr>";
	        }
        }
        
        
        // CARGAR DATOS DE OCASIONAL
        $pdfCtrl->loadSetting($row);
        
        // CUSTOM TEMPLATE
        $pdfCtrl->template=$config['reporte_template'];
        
        // SETTEAR NOMBRE ALTERNO
        $pdfCtrl->setCustomHeader($row,'stockfarmacia');
	        
	    
	    return '';
	    
	    
	}
	
	
}