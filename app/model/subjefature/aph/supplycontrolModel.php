<?php namespace model\subjefature\aph;
use api as app;
use controller as ctrl;

class supplycontrolModel extends app\controller implements ctrl\modelController {
	
	/*
	 * VARIABES GLOBALES
	 */
	private $entity='inventario_insumosaph';
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INICIAR CONSTRUCTOR PADRE
		parent::__construct();
	}
	
	
	// GENERAR CÓDIGO DE INVENTARIO
	private function getNexCode($type,$date){
	    
	    // VARIABLE PARA CÓDIGO
	    $anio=$this->setFormatDate($date,'Y');
	    $mes=$this->setFormatDate($date,'m');
	    
	    // VALIDAR TIPO DE TRANSACCIÓN
	    if($type=='INGRESO DE INSUMOS'){ $code="ING"; }
	    elseif($type=='ENTREGA DE INSUMOS'){ $code="DSC"; }
	    elseif($type=='AJUSTE DE INVENTARIO'){ $code="AJS"; }
	    else $this->getJSON("No se ha seleccionado ningun tipo de transacción permitido!<br>Revise el formulario y vuelva a intentar.");
	    
	    // ANCLAR NUMERO DE MES
	    $code="{$anio}{$code}{$mes}-";
	    
	    // CONSULTAR EL NÚMERO DE REGISTRO SIGUIENTE
	    $num=str_pad($this->db->numRows($this->db->selectFromView($this->entity,"WHERE date_part('year',inventario_fecha_registro)='{$anio}'"))+1,2,"0",STR_PAD_LEFT);
	    
	    // RETORNAR NÚMERO DE REGISTRO
	    return "{$code}{$num}";
	}
	
	/*
	 * INGRESAR NUEVO REGISTRO
	 */
	public function insertEntity(){
		// GENERAR CÓDIGO DE REGISTRO
	    $this->post['inventario_codigo']=$this->getNexCode($this->post['inventario_tipo'],$this->post['inventario_fecha_registro']);
		// RETORNAR CONSULTA
		$this->getJSON($this->db->executeTested($this->db->getSQLInsert($this->post, $this->entity)));
	}
	
	/*
	 * ACTUALIZAR REGISTROS
	 */
	public function updateEntity(){
	    
	    // VALIDAR QUE SE HAYAN INGRESADO INSUMOS
	    if($this->post['inventario_estado']=='REGISTRO COMPLETO' && $this->db->numRows($this->db->selectFromView('movimientos_inventarioaph',"WHERE fk_inventario_id={$this->post['inventario_id']}"))<1){
	        $this->getJSON("Para realizar esta acción, primero debe registrar los insumos que se van a ingresar o descargar.");
	    }
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($this->db->executeTested($this->db->getSQLUpdate($this->post, $this->entity)));
    }

    
    public function requestEntity(){}

	
    
    
    /*
     * IMPRIMIR ACTA DE INGRESO O EGRESO
     */
    public function printById($pdfCtrl,$id,$config){
        // STRING DE CONSULTA
        $str=$this->db->getSQLSelect($this->entity,['bodegas'],"WHERE inventario_id={$id}");
        // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
        if($this->db->numRows($str)>0){
            
            // OBTENER DATOS DE PERMISO
            $row=$this->db->findOne($str);
            
            // PARSE FECHA
            $row['inventario_fecha_registro_alt']=$this->setFormatDate($row['inventario_fecha_registro'],'complete');
            $row['inventarioDia']=$this->setFormatDate($row['inventario_fecha_registro'],'d');
            $row['inventarioMesAnio']=$this->setFormatDate($row['inventario_fecha_registro'],'m de Y');
            
            // ENTREGA CONFORME
            $aux=$this->db->findOne($this->db->selectFromView('vw_ppersonal',"WHERE ppersonal_id={$row['fk_ppersonal_entrega']}"));
            $row['entrega']=$aux['personal_nombre'];
            $row['entrega_titulo']=$aux['personal_titulo'];
            $row['entrega_puesto']=$aux['puesto_definicion'];
            // RECIBE CONFORME
            $aux=$this->db->findOne($this->db->selectFromView('vw_ppersonal',"WHERE ppersonal_id={$row['fk_ppersonal_recibe']}"));
            $row['recibe']=$aux['personal_nombre'];
            $row['recibe_titulo']=$aux['personal_titulo'];
            $row['recibe_puesto']=$aux['puesto_definicion'];
            
            // VARIALE PARA IMPRESIÓN DE INSUMOS
            $row['suppliesList']="";
            
            // IMPRESIÓN DE INSUMOS
            foreach($this->db->findAll($this->db->selectFromView('vw_stock_inventario_insumosaph',"WHERE fk_inventario_id={$id} AND stock > 0 ORDER BY insumo_nombre")) as $k=>$v){
                $index=$k+1;
                $row['suppliesList'].="<tr><td>{$index}</td><td>{$v['insumo_nombre']}</td><td>{$v['insumo_presentacion']}</td><td>{$v['insumo_concentracion']}</td><td>{$v['stock']}</td></tr>";
            }
            
            // CARGAR DATOS DE OCASIONAL
            $pdfCtrl->loadSetting($row);
            
            // CUSTOM TEMPLATE
            $pdfCtrl->template=$config['reporte_template'];
            
            // SETTEAR NOMBRE ALTERNO
            $pdfCtrl->setCustomHeader($row,$this->entity);
            
        } else {
            // RETORNAR CONSULTA
            return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
        }
        
        return '';
    }
    
    /*
     * IMPRIMIR DE STOCK DE BODEGAS - ESTACIONES
     */
    public function printStockByWineryId($pdfCtrl,$id,$config){
        // STRING DE CONSULTA
        $str=$this->db->getSQLSelect('bodegas',['estaciones'],"WHERE bodega_id={$id}");
        // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
        if($this->db->numRows($str)>0){
            
            // OBTENER DATOS DE PERMISO
            $row=$this->db->findOne($str);
            
            // PARSE FECHA
            $row['stockDia']=$this->setFormatDate($this->getFecha(),'d');
            $row['stockMesAnio']=$this->setFormatDate($this->getFecha(),'m de Y');
            
            // STRING DE CONSULTA
            $str=$this->db->selectFromView('vw_insumosaph_stock_estaciones',"WHERE bodegaId={$id} ORDER BY insumo_nombre");
            
            // VARIALE PARA IMPRESIÓN DE INSUMOS
            $row['suppliesList']=$this->db->numRows($str)>0?"":"<tr><td colspan=5>NO SE HAN ASIGNADO MATERIALES</td></tr>";
            
            // IMPRESIÓN DE INSUMOS
            foreach($this->db->findAll($str) as $k=>$v){
                $index=$k+1;
                $status=($v['stock']<10)?'text-danger':'text-success';
                $row['suppliesList'].="<tr><td>{$index}</td><td>{$v['insumo_nombre']}</td><td>{$v['insumo_presentacion']}</td><td>{$v['insumo_concentracion']}</td><td class='text-bold {$status}'>{$v['stock']}</td></tr>";
            }
            
            // CARGAR DATOS DE OCASIONAL
            $pdfCtrl->loadSetting($row);
            
            // CUSTOM TEMPLATE
            $pdfCtrl->template=$config['reporte_template'];
            
            // SETTEAR NOMBRE ALTERNO
            $pdfCtrl->setCustomHeader($row,'bodegas');
            
        } else {
            // RETORNAR CONSULTA
            return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
        }
        
        return '';
    }
    
}