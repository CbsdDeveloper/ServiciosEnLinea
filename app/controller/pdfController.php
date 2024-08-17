<?php namespace controller;
use api as app;
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
// Load Composer's autoloader
require VENDOR_PATH.'autoload.php';
class pdfController extends app\view {
	
	// Variables para instancia de reporte
	private $ctrl;
	public $varParams=array();
	public $setting=array();
	public $template='template';
	public $extraHTML="";
	public $titleExtra;
	private $customName;
	public $customNameParams;
	public $customFieldsHeader;
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// INSTANCIA DE CONTROLLER
		$this->ctrl = new app\controller;
		// PARÁMETROS PARA REPORTE
		$this->setting['report_date']=$this->ctrl->getFecha('complete');
		$this->setting['BARCODE']="";
		// SETTEAR VARIABLE PARA NOMBRE DE ARCHIVO
		$this->customName='';
	}
	
	/*
	 * INGRESAR VARIABLES PARA IMPRESIÓN
	 */
	public function loadSetting($data){
		// UNIR PARÁMETROS DE CONFIGURACIÓN CON DATOS CONSULTADOS
		$this->setting=array_merge($this->setting,$data);
	}
	
	/*
	 * INSERTAR CÓDIGO DE BARRAS
	 */
	public function setBQCode($type,$val,$label="",$height='48px',$width='248px'){
		// TEST DE LABEL
		if($label=="") $label=$val;
		// TIPOS DE CODIGO
		$typeInsert=array(
			'barcode'=>"<barcode dimension='1D' type='C39' value='{$val}' label='{$label}' style='height:{$height};width:{$width};font-size:12px;font-weight:600;'></barcode>",
			'qrcode'=>"<qrcode value='{$val}' ec='H' style='width:50mm;background-color:white;color:black;'></qrcode>"
		);
		// INSERTAR CODIGO
		$this->setting['BARCODE']=$typeInsert[$type];
	}
	
	/*
	 * PARSE TIPO FECHA
	 */
	private function validateDate($date,$format='Y-m-d H:i:s'){
		$d = \DateTime::createFromFormat($format,$date);
		return $d && $d->format($format)==$date;
	}
	
	/*
	 * GET HEADERS
	 */
	private function getHeaders($pagina){
		// REEMPLAZAR DATOS PREIMPRESOS
		foreach($this->setting as $key=>$val){$pagina=preg_replace('/{{'.$key.'}}/',$val,$pagina);}
		foreach($this->varParams as $key=>$val){$pagina=preg_replace('/{{'.$key.'}}/',$val,$pagina);}
		foreach($this->setting as $key=>$val){$pagina=preg_replace('/{{'.$key.'}}/',$val,$pagina);}
		foreach($this->setting as $key=>$val){$pagina=preg_replace('/{{'.$key.'}}/',$val,$pagina);}
		// RETORNAR DATOS IMPRESOS
		return $pagina;
	}
	
	/*
	 * OBTENER PLANTILLA HTML DE REPORTE
	 */
	private function getPlantilla($name,$orientacion,$extra=true){
		// EXTRAER VARIABLES DE CONFIGURACIÓN
		extract($this->setting);
		// BUSCAR PLANTILLA - VALIDAR SI EXISTE HTML
		$pagina=file_get_contents(REPORTS_PATH."{$this->template}.html");
		// MEMBRETAR HOJA
		$pagina=$this->getHeaders($pagina);
		// REEMPLAZAR NOMBRE DE REPORTE - ADJUNTAR SERIES Y CÓDIGOS PARA HACER REPORTE ÚNICO
		$pagina=$this->replace_div('/{{informe}}/',strtoupper($name),$pagina);
		// INGRESAR CSS PERSONALIZADO DE BOOTSTRAP
		$pagina=$this->replace_div('/{{css}}/',file_get_contents(REPORTS_PATH.'bootstrap.min.css'),$pagina);
		// RETORNAR HTML
		$pagina=$this->replace_div('/{{report_date}}/',$this->setting['report_date'],$pagina);
		// REEMPLAZAR NOMBRE DE REPORTE - ADJUNTAR SERIES Y CÓDIGOS PARA HACER REPORTE ÚNICO
		$pagina=$this->replace_div('/{{informe}}/',strtoupper($name),$pagina);
		// MEMBRETAR HOJA
		$pagina=$this->getHeaders($pagina);
		// RETORNAR DATOS IMPRESOS
		return $pagina;
	}
	
	/*
	 * INSTANCIA DE CLASE HTML2PDF
	 */
	private function cargarHTML2PDF($orientacion,$hoja){
		$this->html2pdf=new Html2Pdf($orientacion,$hoja,'es',true,'UTF-8',array(0, 0, 0, 0));
		// SETTING DE PDF
		//$this->html2pdf->pdf->SetDisplayMode('fullpage');
		$this->html2pdf->pdf->SetDisplayMode('real');
	}
	
	/*
	 * IMORIMIR REPORTE VACIO
	 */
	public function printCustomEmptyReport($body,$data){
		// CARGAR DATOS ESPECIALES
		if(!empty($data)) $this->loadSetting($data);
		// CARGAR TEXTO DE PLANTILLA HTML
		$this->loadSetting(array('CUSTOM_TEMPLATE_REPORT'=>$body));
		// CARGAR PLANTILLA - REPORTE VACIO
		$this->template='emptyReport';
	}
	
	/*
	 * IMORIMIR REPORTE VACIO
	 */
	public function printCustomReport($msg,$user,$body,$data=array()){
		// CARGAR DATOS ESPECIALES
		if(!empty($data)) $this->loadSetting($data);
		// PLANTILLA PARA REPORTE NO ENCONTRADO
		$this->loadSetting(array('listEmptyReport'=>"<ul>{$msg}</ul>",'_usuario'=>strtoupper(is_null($user) || in_array($user,[null,''])?'usuario':$user)));
		// CARGAR TEXTO DE PLANTILLA HTML
		$this->loadSetting(array('CUSTOM_TEMPLATE_REPORT'=>$body));
		// CARGAR PLANTILLA - REPORTE VACIO
		$this->template='emptyReport';
	}
	
	/*
	 * SETTEAR DATOS DE CABECERA
	 */
	public function setCustomHeader($data,$entity,$type='application'){
		// VALIDAR SI LA ENTIDAD PERTENECE A NOMBRE ALTERNO
		if(isset($this->customNameParams[$entity])){
			// VARIABLE PARA CAMPOS DE HEADER
			$fields=$this->customNameParams[$entity];
			// VARIABLE PARA CAMPOS DE HEADER
			$temp=is_array($fields)?$fields[$type]:$fields;
			// VARIALE NOMBRE ALTERNO
			$this->customName=$temp;
			// RECORRER DATOS DE ENTIDAD
			foreach($data as $key=>$val){ $this->customName=$this->replace_div('/{{'.$key.'}}/',$val,$this->customName); }
		}
		// VALIDAR SI LA ENTIDAD PERTENECE A NOMBRE ALTERNO
		if(isset($this->customFieldsHeader[$entity])){
			// VARIABLE PARA CAMPOS DE HEADER
			$fields=$this->customFieldsHeader[$entity];
			// VARIABLE PARA CAMPOS DE HEADER
			$temp=is_array($fields)?$fields[$type]:$fields;
			// RECORRER DATOS DE ENTIDAD
			foreach($data as $key=>$val){ $temp=$this->replace_div('/{{'.$key.'}}/',$val,$temp); }
			// CARGAR DATOS DE REPORT
			$this->loadSetting(array('report_info_entity'=>$temp));
		}
	}
	
	/*
	 * IMPRESIÓN DE REPORTE GENERAL
	 */
	public function printReport($config,$body){
		try{
			// VALIDAR NOMBRE DE REPORTE
			$fileName=($this->customName!='')?$this->customName:$config['reporte_nombre'];
			// print_r("***");
			// print_r($fileName);
			// print_r("***");
			// print_r($config);
			// ORIENTACIÓN DE HOJA
			$this->cargarHTML2PDF($config['reporte_orientacion'],$config['reporte_hoja']);
			// SETTING DE PDF
			$this->html2pdf->pdf->SetCreator('Lalytto');
			$this->html2pdf->pdf->SetAuthor('Lalytto');
			$this->html2pdf->pdf->SetTitle($fileName);
			$this->html2pdf->pdf->SetSubject('AutoInspection');
			$this->html2pdf->pdf->SetKeywords('HTML2PDF, TCPDF, Lalytto');
			// TEMPLATE
			$pagina="";
			if(!is_array($body)){$body=array($body);}
			foreach($body as $val){
				$pagina.=$this->getPlantilla($config['reporte_nombre'],$config['reporte_orientacion']);
				$pagina=$this->replace_div('/{{BODY}}/',$val,$pagina);
			}
			if($this->extraHTML!=""){
				$pagina=$this->replace_div('/{{titleExtra}}/',$this->titleExtra,$pagina);
				$pagina=$this->replace_div('/{{extraHTML}}/',$this->extraHTML,$pagina);
			}

			// print_r("***");
			// print_r($pagina);print_r("***");
			// ESCRIBIR E IMPRIMIR PDF
			$this->html2pdf->WriteHTML($pagina);
			// PRESENTAR IMPRESIÓN DIRECTA
			if(isset($config['reporte_printmode']) && $config['reporte_printmode']=='SI') $this->html2pdf->pdf->IncludeJS("print(true);");
			// LIMPIAR CACHÉ DE REPORTE
			// ob_end_clean();
			/*
			 * Send the document to a given destination: string, local file or browser.
			 * Dest can be :
			 *  I : send the file inline to the browser (default). The plug-in is used if available. The name given by name is used when one selects the "Save as" option on the link generating the PDF.
			 *  D : send to the browser and force a file download with the name given by name.
			 *  F : save to a local server file with the name given by name.
			 *  S : return the document as a string. name is ignored.
			 *  FI: equivalent to F + I option
			 *  FD: equivalent to F + D option
			 *  true  => I
			 *  false => S
			 */
			// DESCARGAR/GUARDAR REPORTE
			// $this->html2pdf->Output("{$fileName}.pdf",$config['reporte_autodownload']);
			$this->html2pdf->Output("{$fileName}.pdf","D");
			// GUARDAR PDF EN DIRECTORIO
			// $this->html2pdf->Output('file.pdf');
			exit;
		}catch(Html2PdfException $e) {
			$this->html2pdf->clean();
			$formatter = new ExceptionFormatter($e);
			echo $formatter->getHtmlMessage();
			exit;
		}
	}
	
}