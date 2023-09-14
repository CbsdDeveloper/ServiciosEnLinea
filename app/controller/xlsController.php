<?php namespace controller;
// Load Composer's autoloader
require VENDOR_PATH.'autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class xlsController {
	
	private $cellList;
	private $config;
	// VARIABLES PHP 7
	public $activeSheet;
	public $spreadsheet;
	public $writer;
	// VARIABLES DE CONFIGURACIÓN
	public $setting=array();
	public $varParams;
	// VARIABLE PARA SABER EL INICIO DEL ENCABEZADO &  CUERPO
	public $firstHeadCell;
	public $firstBodyCell;
	// VARIABLE PARA SABER LA ÚLTIMA CELDA DEL ENCABEZADO &  CUERPO
	public $lastHeadCell;
	public $lastBodyCell;
	// VARIABLE - DEFAULT EXCEL
	public $lastBodyColumn; // LETRA
	public $lastBodyRow; // NUMERO
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONFIGURACIÓN XLS
		$this->config=json_decode(file_get_contents(SRC_PATH."config/xls.json"),true);
		// CARGAR LISTA DE CELDAS ACEPTADAS
		$this->cellList=$this->config['cell'];
		// INSTANCIA PARA LECTURA
		$this->spreadsheet=new Spreadsheet();
	}
	
	/*
	 * CARGA DE DATOS PARA IMPRESIÓN
	 */
	public function loadSetting($data){
		$this->setting=array_merge($this->setting,$data);
	}
	
	/*
	 * *********************************************************************************************
	 * ******************************* MÉTODOS PARA LEER DE ARCHIVOS *******************************
	 * *********************************************************************************************
	 */
	/*
	 * RETORNAR VALOR ENTERO DE COLUMNAS
	 * Ej. C4 -> 4
	 */
	private function getCellNumber($s){
		return($a=preg_replace('/[^\-\d]*(\-?\d*).*/','$1',$s))?$a:'0';
	}
	
	/*
	 * RETORNAR DATOS DE CELDAS
	 */
	private function getValueCell($cell,$index){
		// VALIDAR EL TIPO DE DATO DE CADA ATRIBUTO DE UNA ENTIDAD - FECHA
		if(in_array($index,$this->config['columnsType']['date'])){
			$data=date('Y-m-d',strtotime("+1 day",\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($this->activeSheet->getCell($cell)->getValue())));
		}
		// VALIDAR EL TIPO DE DATO DE CADA ATRIBUTO DE UNA ENTIDAD - FECHA Y HORA
		elseif(in_array($index,$this->config['columnsType']['datetime'])){
			$data=date('Y-m-d H:i:s',strtotime("+1 day",\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($this->activeSheet->getCell($cell)->getValue())));
		}
		// VALIDAR EL TIPO DE DATO DE CADA ATRIBUTO DE UNA ENTIDAD - GENERAL
		else{
			$data=$this->activeSheet->getCell($cell)->getCalculatedValue();
		}
		// RETORNAR DATO
		return $data;
	}
	
	/*
	 * VALIDAR DATOS DE COLUMNAS
	 */
	private function entyVerify($row,$tb){
		// CONSULTAR SI LA ENTIDAD REQUIERE VALIDAR CELDAS
		if(isset($this->config['requiredFields'][$tb])){
			// OBTENER LISTA DE CELDAS A VALIDAR
			$list=$this->config['requiredFields'][$tb];
			// CONSULTAR SI LA CELDA HA SIDO CREADA Y SI ES DIFERENTE DE NULL
			if($this->issetVerify($row,$list) && $this->nullVerify($row,$list)){
				// VALIDACIONES EXTRAS - IDENTIFICAR TABLAS
				if($tb=='ciiu' && $row['nivel_ciiu']==7) return true;
			}
		}
		// APROBAR VALIDACIÓN POR DEFECTO
		return true;
	}
	
	// CONSULTAR SI ES NECESARIA LA CELDA
	private function issetVerify($row,$list){
		foreach($list as $idx){
			if(!isset($row[$idx])) return false;
		}	return true;
	}
	
	// VALIDAR QUE EL VALOR NO SEA NULO O VACÍO
	private function nullVerify($row,$list){
		foreach($list as $idx){
			if($row[$idx]==null || $row[$idx]=="") return false;
		}	return true;
	}
	
	/*
	 * RETORNAR RANGO DE COLUMNAS INGRESADAS PARA TRABAJAR
	 * ---------- MEJORAR MÉTODO PARA OBTENER COLUMNAS DE INICIO Y FIN DE DATOS
	 */
	private function getColumns($post){
		// COLUMNAS POR DEFECTO
		$model=array(
			'estado'=>true,
			'firstColumn'=>0,'lastColumn'=>0,
			'firstRow'=>0,'lastRow'=>0,
			'firstCell'=>$post['fila_inicio'],'lastCell'=>$post['fila_final']
		);
		// VARIABLE INGRESADAS EN UI
		$model['firstRow']=intval($this->getCellNumber($post['fila_inicio']));
		$model['lastRow']=intval($this->getCellNumber($post['fila_final']));
		// VALIDAR QUE EL ÚLTIMO REGISTRO DE EXCEL NO SEA MAYOR AL INGRESADO POR EL UI
		if($model['lastRow']>$this->lastBodyRow) return array('estado'=>false,'mensaje'=>"Erro: Inconsistencia en el ingreso de información, hemos detectado que el último registro disponible en el archivo es la fila <b>{$this->lastBodyRow}</b> mientras que usted ha ingresado {$post['fila_final']}");
		// RECORRER COLUMNAS HABILITADAS PARA TRABAJAR
		foreach($this->cellList as $idx=>$val){
			// OBTENER LA PRIMERA FILA
			if(strtolower($val)==strtolower(str_replace($model['firstRow'],"",$post['fila_inicio']))) $model['firstColumn']=$idx;
			// OBTENER LA ÚLTIMA FILA
			if(strtolower($val)==strtolower(str_replace($model['lastRow'],"",$post['fila_final']))) $model['lastColumn']=$idx;
		}
		// TOTAL DE FILAS Y COLUMNAS
		$model['rows']=($model['lastRow']-$model['firstRow']);
		$model['columns']=($model['lastColumn']-$model['firstColumn']);
		// RETORNAR MODELO DE COLUMNAS PARA TRABAJO
		return $model;
	}
	
	/*
	 * RETORNAR LOS DATOS DE CELDAS
	 * MÉTODO EMPLEADO DESDE LOS OBJETOS QUE LO USA
	 * EJ. biometrModel, ciiuModel, unitModel, participantModel, personalModel
	 */
	public function getCellValue($post,$archivo,$tb){
		// EXTRAER VARIABLES
		extract($post);
		
		/**  Identify the type of $inputFileName  **/
		$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($archivo);
		/**  Create a new Reader of the type that has been identified  **/
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
		/**  Load $inputFileName to a Spreadsheet Object  **/
		$this->spreadsheet = $reader->load($archivo);
		// SELECCIONAR HOJA
		$this->activeSheet = $this->spreadsheet->getSheet($post['hoja_numero']-1);
		
		// COLUMNAS USADAS
		$this->lastBodyColumn=$this->activeSheet->getHighestColumn(); // e.g 'F'
		// FILAS USADAS
		$this->lastBodyRow=$this->activeSheet->getHighestRow(); // e.g. 10
		// ÚLTIMA CELDA DE HOJA
		$this->lastBodyCell="{$this->lastBodyColumn}{$this->lastBodyRow}";
		
		// OBTENER DATOS INGRESADOS EN FORMULARIO
		$config=$this->getColumns($post);
		
		// VALIDAR QUE LAS COLUMNAS HAYAN SIDO BIEN INGRESADAS
		if(!$config['estado']) return $config['mensaje'];
		if($config['columns']<0 || $config['rows']<0) return "Al parecer el orden de las columnas o filas se encuentran mal.";
		// MODELO DE DATOS
		$data=array();
		// OBTENER LISTA DE CAMPOS DE ENTIDAD
		$columnsList=$this->config['columnsList'][$tb];
		// BUSCAR DATOS - RECORRER FILAS
		for($i=$config['firstRow'];$i<=$config['lastRow'];$i++){
			// MODELO PARA INGRESAR DATOS
			$data_cols=array();
			$n=0;
			// BUSCAR EN COLUMNAS
			for($j=0;$j<=$config['columns'];$j++){
				// CALCULAR LA COLUMNA A BUSCAR VALOR
				$y=$j+$config['firstColumn'];
				// TOMAR VALOR DE CELDA - VALIDAR TODOS LOS DISTINTO TIPOS DE DATOS
				$data_cols[$columnsList[$n]]=$this->getValueCell("{$this->cellList[$y]}{$i}",$columnsList[$n]);
				// INCREMENTAR ITERACIÓN
				$n++;
			}	
			// VALIDAR DATOS REQUERIDOS
			if($this->entyVerify($data_cols,$tb)) $data[]=$data_cols;
		}	
		// RETORNAR DATA
		return $data;
	}
	
	
	/*
	 * *********************************************************************************************
	 * ***************************** MÉTODOS PARA EXPORTAR DE ARCHIVOS *****************************
	 * *********************************************************************************************
	 */
	
	/*
	 * CELDA AUTOAJUSTABLE A CONTENIDO
	 */
	private function setAnchoColumnas($objPHPExcel,$x){
		for($i=0;$i<$x;$i++){
			$objPHPExcel->getActiveSheet()
						->getColumnDimension($this->cellList[$i])
						->setAutoSize(true);
		}
	}
	
	/*
	 * APLICAR ESTILO - TEXTO CENTRADO
	 */
	private function setCenter($celdas){
		$this->spreadsheet->getActiveSheet()
					->getStyle($celdas)
					->getAlignment()
					->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
	}
	
	/*
	 * APLICAR ESTILO - NEGRITA
	 */
	private function setBold($celdas){
		$this->spreadsheet->getActiveSheet()
					->getStyle($celdas)
					->getFont()
					->setBold(true);
	}
	
	/*
	 * DEFINIR ESTILO DE CUERPO
	 */
	public function setBorderStyle($celdas){
		// RANGO DE CELDAS PARA BORDER
		$params=explode(':',$celdas);
		$aux=array();
		$aux['firstRow']=intval($this->getCellNumber($params[0]));
		$aux['lastRow']=intval($this->getCellNumber($params[1]));
		$str="";
		// RECORRER FILAS
		for($j=$aux['firstRow'];$j<=$aux['lastRow'];$j++){
			// RECORRER COLUMNAS
			for($i=0;$i<=array_search($this->lastBodyColumn,$this->cellList);$i++){
				// CREAR MODELO PARA APLICAR ESTILO
				$sharedStyle1=array(
					'borders'=>[
						'bottom'=>[
							'borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						],
						'top'=>[
							'borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						],
						'left'=>[
							'borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						],
						'right'=>[
							'borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						]
					]
				);
				// APLICAR ESTILO
				$this->spreadsheet->getActiveSheet()->getStyle("{$this->cellList[$i]}{$j}")->applyFromArray($sharedStyle1);
				$str.="{$this->cellList[$i]}{$j}";
			}
		}
	}
	
	/*
	 * DEFINIR ESTILO DE ENCABEZADO
	 */
	public function setHeadStyle($celdas){
		// CREAR MODELO PARA APLICAR ESTILO
		$sharedStyle1=array(
			'font'=>[
				'bold'=>true,
			],
			'fill'=>[
				'fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				'rotation'=>90,
				'startColor'=>[
					'argb'=>'367b48',
				],
				'endColor'=>[
					'argb'=>'367b48',
				],
			],
			'alignment'=>[
				'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
			]
		);
		// APLICAR ESTILO
		$this->spreadsheet->getActiveSheet()->getStyle($celdas)->applyFromArray($sharedStyle1);
		$this->setBorderStyle($celdas);
	}

	/*
	 * DEFINIR NOMBRE DE HOJA
	 */
	public function setTitulo($objPHPExcel,$name){
		$objPHPExcel->getActiveSheet()->setTitle($name);
	}
	
	/*
	 * PROPIEDADES DEL REPORTE - XLS
	 */
	private function getPropiedades(){
		// PROPIEDADES DE DOCUMENTO
		$this->spreadsheet->getProperties()
			->setCreator("Lalytto")
			->setLastModifiedBy("Lalytto")
			->setTitle($this->setting['reporte_nombre'])
			->setSubject($this->setting['reporte_header'])
			->setDescription("Desarrollado por Lalytto.")
			->setKeywords("Lalytto")
			->setCategory($this->setting['reporte_departamento']);
	}
	
	/*
	 * DATOS DE ENCABEZADO
	 */
	private function setPresentacion(){
		// PROPIEDADES DE AUTOR
		$this->getPropiedades();
		// DEFINIR NOMBRE DE REPORTE
		$this->spreadsheet->getActiveSheet()->mergeCells("A1:{$this->lastBodyColumn}1")->setCellValue('A1',$this->setting['reporte_nombre']);
		// ESTILO DE ENCABEZADO
		$this->setHeadStyle("A1:{$this->lastHeadCell}");
		// ESTILO DE CUERPO
		$bodyIdx=$this->firstHeadCell+2;
		$this->setBorderStyle("{A}{$bodyIdx}:{$this->lastBodyCell}");
	}
	
	/*
	 * IMPRIMIR DATOS
	 */
	public function createBodyExcel($config,$data){
		// DEFINIR PRIMERA FILA PARA HEAD
		$this->firstHeadCell=1;
		// DEFINIR FILA A CONTINUACIÓN PARA IMPRIMIR NOMBRE DE COLUMNAS
		$idxInit=$this->firstHeadCell+1;
		// IMPRIMIR CELDAS DE ENCABEZADO
		foreach($config['headers'] as $idx=>$key){
			// DEFINIR LA ÚLTIMA COLUMNA A TRABAJAR
			$this->lastBodyColumn=$this->config['cell'][$idx];
			// DEFINIR CELDA FINAL DE HEADER
			$this->lastHeadCell="{$this->lastBodyColumn}{$idxInit}";
			// IMPRMIR HEADER
			$this->spreadsheet->getActiveSheet()->setCellValue($this->lastHeadCell,$key);
		}
		// DEFINIR PRIMERA FILA PARA BODY
		$this->firstBodyCell=3;
		// DEFINIR FILA A CONTINUACIÓN PARA IMPRIMIR DATOS DE REPORTE
		$idxInit=$this->firstBodyCell;
		// RECORRER DATOS DE CONSULTA
		foreach($data as $index=>$row){
			// INDEX
			$row['index']=($index+1);
			// IMPRIMIR FILAS DE REPORTE -> SOLO PLANTILLA
			foreach($config['fields'] as $idx=>$key){
				// CALCULAR NUEVO INDEX - FILA
				$auxInit=$idxInit+$index;
				// CELDA PARA IMRPIMIR DATO - CELDA
				$this->lastBodyCell="{$this->config['cell'][$idx]}{$auxInit}";
				// IMPRIMIR DATO - CON FORMATO TEXTO
				$this->spreadsheet->getActiveSheet()->setCellValueExplicit($this->lastBodyCell,$row[$key],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			}
		}
	}
	
	/*
	 * EXPORTAR REPORTE
	 */
	public function printExcel(){
		// IMPRIMIR PRESENTACIÓN
		$this->setPresentacion();
		// CREAR INSTANCIA DE ARCHIVO
		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->spreadsheet, "Xlsx");
		// CALCULAR FORMULAS
// 		$writer->setPreCalculateFormulas(false);
		// COMPATIBLE CON 2003
// 		$writer->setOffice2003Compatibility(true);
		// HEADER PARA DESCARGAR ARCHIVO
		header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
		header("Content-Disposition: attachment;filename={$this->setting['reporte_nombre']}.xlsx");
		header("Cache-Control: max-age=0");
		// GUARDAR ARCHIVO CON NOMBRE INGRESADO
		$writer->save("php://output");
		// EXIT	
		exit;
	}
	
}