<?php namespace service;
use api as app;
use controller as ctrl;

// error_reporting(E_ALL);
// ini_set('display_errors', 'On');

class reportService extends app\controller {
	
	private $entity='reportes';
	private $varSystem;
	private $pdfCtrl;
	private $xlsCtrl;
	private $config;
	private $reportEntityList;
	// datos de reporte
	private $localConfig=array(
		'cssTemplatePDF'=>'',
		'fieldsTemplatePDF'=>'',
		'headTemplatePDF'=>''
	);
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// CONSTRUCTOR PADRE
		parent::__construct();
		// CONFIGURACIÓN JSON PARA REPORTES
		$this->config=$this->getConfig(true,'reports');
		// DATOS DEL SISTEMA Y PARÁMETROS
		$this->varSystem=$this->getParamsModule('ALL');
		// PARAMETRIZACION DE REPORTE
		$this->reportEntityList=$this->string2JSON($this->varGlobal['reportEntityList']);
	}
	
	/*
	 * ************************************************************* PDF
	 */
	/*
	 * CARGAR PARÁMETROS EXTRAS
	 */
	private function loadParams(){
		// VARIABLES DE LOS DATOS DEL SISTEMA Y PARÁMETROS
		$this->pdfCtrl->loadSetting($this->varSystem);
		// DATOS DE FECHAS ACTUALES
		$this->pdfCtrl->loadSetting(array('current_date'=>$this->getFecha(),'current_year'=>$this->getFecha('Y')));
		// CARGAR DATOS DEL SISTEMA Y PARÁMETROS PARA REEMPLAZAR LOS DATOS PREIMPRESOS
		$this->pdfCtrl->varParams=$this->varSystem;
	}
	
	/*
	 * MODELOS PARA ESTADÍSTICAS
	 */
	private function parseModelStats($model){
		$model['precio']=($model['precio']==0 || $model['precio']=='' || $model['precio']==null)?0:$model['precio'];
		$model['precio']=number_format($model['precio'], 2, '.', '');
		$model['count']=($model['count']==0 || $model['count']=='' || $model['count']==null)?0:$model['count'];
		$model['count']=intval($model['count']);
		$model['desc']=($model['desc']==0 || $model['desc']=='' || $model['desc']==null)?0:$model['desc'];
		$model['desc']=number_format($model['desc'], 2, '.', '');
		return $model;
	}
	
	/*
	 * REPORTES - IMPRIMIR HEADER
	 */
	private function setHeaderReport($config){
		// MODELO DE RETORNO
		$aux="";
		// VALIDAR SI LOS HEADERS HAN SIDO ENVIADOS
		if(!empty($config['reporte_headers_pdf'])){
			// GENERAR ARRAY DE HEADERS
			$config['fields']=explode(',',$config['reporte_headers_pdf']);
			// ABRIR ETIQUETA - FILA
			$aux="<tr>";
			// IMPRIMIR FILAS DE REPORTE -> SOLO PLANTILLA
			foreach($config['fields'] as $val){
				// GENERAR CELDA
				$aux.="<th>{$val}</th>";
			}
			// CERRAR ETIQUETA - FILA
			$aux.="</tr>";
		}
		// RETORNAR HEADER
		return $aux;
	}
	
	/*
	 * REPORTES - IMPRIMIR HEADER
	 */
	private function setCssPDF($config){
		// MODELO DE RETORNO
		$aux="";
		// VALIDAR SI LOS HEADERS HAN SIDO ENVIADOS
		if(!empty($config['reporte_css_pdf'])){
			// GENERAR ARRAY DE HEADERS
			$config['fields']=explode(',',$config['reporte_css_pdf']);
			// IMPRIMIR FILAS DE REPORTE -> SOLO PLANTILLA
			foreach($config['fields'] as $val){$aux.="<col style='{$val}'>";}
		}
		// RETORNAR HEADER
		return $aux;
	}
	
	/*
	 * 
	 */
	private function setCustomFormatJSON($config){
		// VALIDAR SI LOS CAMPOS A IMPRIMIR VIENEN DESDE LA BASE DE DATOS O DESDE EL .json
		if(!isset($config['fields']) || empty($config['fields'])) $config['fields']=$config['reporte_fields_pdf'];
		// FORMATO JSON {}
		if(is_array(json_decode($config['fields'],true))){
			// PARSE MODEL
			$fields=json_decode($config['fields'],true);
			foreach($fields as $k=>$v){$fields[$k]=explode(',',$v);}
		}
		// FORMATO STRING [field,fields,fields]
		else{$fields=explode(',',$config['fields']);}
		// LISTA DE CAMPOS
		$this->localConfig['fieldsTemplatePDF']=$fields;
		// IMPRIMIR HEADER
		if(!empty($config['reporte_headers_pdf'])){
			$this->localConfig['cssTemplatePDF']=$this->setCssPDF($config);
			$this->localConfig['headTemplatePDF']=$this->setHeaderReport($config);
		}
	}
	
	/*
	 * REPORTES POR DEFECTO -> LISTADOS
	 */
	private function setDefautlReporte($config){
		// OBTENER DATOS DE CONSULTA
		$data=new smartQuery(true);
		// VARIABLE DE RELLENO DE CUERPO - REPORTE
		$body='';
		// PARSE FIELDS
		$this->setCustomFormatJSON($config);
		// RECORRES FILAS DE DATOS
		foreach($data->getData()['data'] as $index=>$row){
			// ABRIR ETIQUETA - FILA
			$aux="<tr>";
			// IMPRIMIR FILAS DE REPORTE -> SOLO PLANTILLA
			foreach($this->localConfig['fieldsTemplatePDF'] as $key){
				// SEPARAR PARÁMETROS ADJUNTOS - CONCATENADOS
				$params=(is_array($key))?$key:explode(',',$key);
				// OBTENER VALOR DE CELDAS
				$valStr="";
				foreach($params as $key2){
					// IMPRIMIR NÚMERO DE REGISTRO - INDEX
					if($key2=='index')$valStr.=($index+1);
					// IMPRIMIR VALOR DE CELDA - VALOR DE CONSULTA DB
					else $valStr.="{$row[$key2]} ";
				}
				// GENERAR CELDA
				$aux.="<td>$valStr</td>";
			}
			// CERRAR ETIQUETA - FILA
			$aux.="</tr>";
			// CONCATENAR CON REGISTRO ANTERIOR	
			$body.=$aux;
		}
		// SET TEMPLATE
		$template=file_get_contents(REPORTS_PATH."{$config['reporte_template']}.html");
		
		// IMPRIMIR HEADER
		$template=preg_replace('/{{thead}}/',$this->localConfig['headTemplatePDF'],$template);
		$template=preg_replace('/{{thcss}}/',$this->localConfig['cssTemplatePDF'],$template);
		
		// RETORNAR PLANTILLA CON DATOS
		return preg_replace('/{{tbody}}/',$body,$template);
	}
	
	/*
	 * FUNCIÓN PARA LLAMAR AL MÉTODO DE IMPRESIÓN DE REPORTE PDF
	 */
	private function preparePDF($config,$body,$data=array()){
		// REEMPLAZAR REQUIRIMIENTOS
		if(sizeof($config['requisitos'])>0 && $config['reporte_requisitos']!='NO'){
			// PLANTILLA DE REQUISITOS
			$data['REQUIREMETS_LIST']=file_get_contents(REPORTS_PATH."requirementsList.html");
			// IMPRIMIR LISTA DE REQUISITOS
			$auxDetail="";
			foreach($config['requisitos'] as $key=>$val){
				// LISTADO DE PERSONAL EN CAPACITACION
				$auxDetail.=file_get_contents(REPORTS_PATH."requirementsItem.html");
				$val['index']=$key+1;
				foreach($val as $k=>$v){$auxDetail=preg_replace('/{{'.$k.'}}/',$v,$auxDetail);}
			}	$data['bodyRequirements']=$auxDetail;
		}else{
			$data['REQUIREMETS_LIST']='';
		}
		// ELIMINAR REGISTROS DE CONFIGURACIÓN
		if(isset($config['requisitos'])) unset($config['requisitos']);
		if(isset($config['relations'])) unset($config['relations']);
		if(isset($config['fields'])) unset($config['fields']);
		// LOAD DATA LOCAL
		$this->pdfCtrl->loadSetting(array_merge($data,$config));
		// PRINT PDF
		$this->pdfCtrl->printReport($config,$body);
	}
	
	/*
	 * VALDIAR PERMISO PARA REPORTE
	 */
	private function testCustomReport($config,$rolId){
		$msg="<li>NO CUENTA CON LOS PRIVILEGIOS PARA REALIZAR ESTA ACCIÓN [PERMISO={$rolId}]</li>";
		// PLANTILLA PARA REPORTE NO ENCONTRADO
		$this->pdfCtrl->loadSetting(array('listEmptyReport'=>$msg,'usuario'=>$user));
		$this->pdfCtrl->status=$msg;
		$this->pdfCtrl->template='401Report';
		// RENDERIZAR HTML TO PDF
		$this->preparePDF($config,'');
		exit();
	}
	
	/*
	 * LISTADO DE REQUETIMIENTOS
	 */
	private function getReportInfo($reportId){
		// DATOS DE REPORTE
		$data=$this->db->findById($reportId,$this->entity);
		// VALIDAR LOS PERMISOS PARA IMPRESIÓN DE REPORTES
		if($data['reporte_permiso']>0){
			if(!in_array($data['reporte_permiso'],$this->getAccesRol())) $this->testCustomReport($data,$data['reporte_permiso']);
		}
		// LISTA DE REQUERIMIENTOS
		$data['requisitos']=array();
		foreach($this->db->findAll($this->db->getSQLSelect('requisitos',['recursos'],"WHERE fk_reporte_id={$reportId} ORDER BY recurso_id")) as $v){
			$data['requisitos'][]=array('recurso_nombre'=>$v['recurso_nombre']);
		}
		// RETORNAR DATOS
		return $data;
	}
	
	/*
	 * ************************************************************* INSTANCIA DE MÉTODOS
	 */
	public function executeQuery($opList){
		extract($this->getURI());
		// VALIDAR SI EL REPORTE ES DEL USUARIO
		if(isset($this->config['view_decode'][$tb])) $tb=$this->config['view_decode'][$tb];
		// VERIFICAR TIPO DE REPORT -> XLS - PDF
		if(isset($type) && $type=='XLS') $this->prepareXLS($tb);
		else{
			// VALIDAR SI LA PETICIÓN PROVIENE DESDE MÓDULO DE REPORTES
			if($tb=='export') $this->exportEntity();
			// INSTANCIA DE PDF
			$this->pdfCtrl=new ctrl\pdfController;
			// ASIGNAR VARIABLE DE NOMBRES ALTERNOS
			$this->pdfCtrl->customNameParams=$this->config['nameSaveReport'];
			$this->pdfCtrl->customFieldsHeader=$this->config['fieldsHeader'];
			// CARGAR PARÁMETROS DE REPORTE
			$this->loadParams();
			// VERIFICAR TIPO DE REPORT - CON ID - DETALLES - OTROS
			if(in_array($tb,$this->config['list'])){
				// CONFIGURACIÓN DE ENTIDAD PARA REPORTE
				$localConfig=$this->config['entities'][$tb];
				// IMPRIMIR REPORTE POR ID - CON DETALLES
				if(isset($id) && !isset($withDetail)){
					// DATOS DE REPORTE
					$config=$this->getReportInfo($localConfig['templateSingle']);
					// IMPRIMIR DATOS DE ENTIDAD
					if(in_array($tb,$this->config['listPrintById'])){
						// NOMBRE DE INSTANCIA DE ENTIDAD
						$adapter=new $localConfig['model'];
						// OBTENER NOMBRE DE FUNCIÓN QUE SE NECESITA
						$method=$localConfig['printById'];
						// LLAMAR AL FUNCIÓN REQUERIDA
						$bodyHTML=$adapter->$method($this->pdfCtrl,$id,array_merge($config,$localConfig));
					}
				}
				// IMPRIMIR REPORTE CON DETALLES
				elseif(isset($withDetail)){
					// DATOS DE REPORTE
					$config=$this->getReportInfo($localConfig['templateRequest']);
					// NOMBRE DE INSTANCIA DE ENTIDAD
					$adapter=new $localConfig['model'];
					// OBTENER NOMBRE DE FUNCIÓN QUE SE NECESITA
					$method=$localConfig['printDetail'];
					// LLAMAR AL FUNCIÓN REQUERIDA
					$bodyHTML=$adapter->$method($this->pdfCtrl,$config);
				}
				// IMPRESION GENERAL
				else{
				    
					// DATOS DE REPORTE
					$config=$this->getReportInfo($localConfig['templateList']);
					// IMPRIMIR DATOS DE ENTIDAD
					$bodyHTML=$this->setDefautlReporte($config);
					
				}
				// RENDERIZAR HTML TO PDF
				$this->preparePDF($config,$bodyHTML);
			}elseif($tb=='dailyReport'){
				// REPORTES DIARIOS
				$this->dailyReport();
			}
		}
		$this->getJSON(QUERY_UNDEFINED."executeQueryReports->{$tb}");
	}
	
	/*
	 * ************************************************************* XLS
	 */
	/*
	 * REPORTES POR DEFECTO -> LISTADOS
	 */
	private function setDefautlReporteXls($config,$data,$objPHPExcel){
		// DEFINIR PRIMERA FILA PARA HEAD
		$this->xlsCtrl->firstHeadCell=1;
		// DEFINIR FILA A CONTINUACIÓN PARA IMPRIMIR NOMBRE DE COLUMNAS
		$idxInit=$this->xlsCtrl->firstHeadCell+1;
		// IMPRIMIR CELDAS DE ENCABEZADO
		foreach($config['headers'] as $idx=>$key){
			// DEFINIR LA ÚLTIMA COLUMNA A TRABAJAR
			$this->xlsCtrl->lastBodyColumn=$this->config['cell'][$idx];
			// DEFINIR CELDA FINAL DE HEADER
			$this->xlsCtrl->lastHeadCell="{$this->xlsCtrl->lastBodyColumn}{$idxInit}";
			// IMPRMIR HEADER
			$objPHPExcel->getActiveSheet()->setCellValue($this->xlsCtrl->lastHeadCell,$key);
		}
		// DEFINIR PRIMERA FILA PARA BODY
		$this->xlsCtrl->firstBodyCell=3;
		// DEFINIR FILA A CONTINUACIÓN PARA IMPRIMIR DATOS DE REPORTE
		$idxInit=$this->xlsCtrl->firstBodyCell;
		// RECORRER DATOS DE CONSULTA
		foreach($data as $index=>$row){
			// INDEX
			$row['index']=($index+1);
			// IMPRIMIR FILAS DE REPORTE -> SOLO PLANTILLA
			foreach($config['fields'] as $idx=>$key){
				// CALCULAR NUEVO INDEX - FILA
				$auxInit=$idxInit+$index;
				// CELDA PARA IMRPIMIR DATO - CELDA
				$this->xlsCtrl->lastBodyCell="{$this->config['cell'][$idx]}{$auxInit}";
				// IMPRIMIR DATO - CON FORMATO TEXTO
				$objPHPExcel->getActiveSheet()->setCellValueExplicit($this->xlsCtrl->lastBodyCell,$row[$key],\PHPExcel_Cell_DataType::TYPE_STRING);
				// IMPRIMIR DATO - SIN FORMATO
				// $objPHPExcel->getActiveSheet()->setCellValue($this->xlsCtrl->lastBodyCell,$row[$key]);
			}
		}
		// RETORNAR OBJETO EXCEL
		return $objPHPExcel;
	}
	
	/*
	 * FUNCIÓN PARA LLAMAR AL MÉTODO DE IMPRESIÓN DE REPORTE XLS
	 */
	private function prepareXLS($tb){
		// DATOS DE CONFIGURACIÓN
		$this->config=$this->getConfig(true,'xls');
		// CONFIGURACIÓN LOCAL - XLS REPORTE
		$config=$this->config['report'];
		// VALIDAR SI ES UN REPORTE LISTADO
		if(in_array($tb,$config['list'])){
			// OBTENER DATOS DE CONSULTA
			$data=new smartQuery(true);
			// CONFIGURACIÓN DE ENTIDAD PARA REPORTE
			$localConfig=$config[$tb];
			// DATOS DE REPORTE
			$config=$this->db->findById($localConfig['list'],$this->entity);
			// DESCOMPRIMIR HEADER Y COLUMNAS
			$config['headers']=explode(',',$config['reporte_headers_xls']);
			$config['fields']=explode(',',$config['reporte_fields_xls']);
			
			// INSTANCIAR OBJETO XLS
			$this->xlsCtrl=new ctrl\xlsController();
			// CARGAR VARIABLES DEL SISTEMA Y PARÁMETROS
			$this->xlsCtrl->loadSetting($config);
			// CARGAR VARIABLES DEL SISTEMA Y PARÁMETROS
			$this->xlsCtrl->varParams=$this->varSystem;
			
			// DEFINIR HOJA DE REPORTE
			$this->xlsCtrl->spreadsheet->setActiveSheetIndex(0)->setTitle($config['reporte_header']);
			// SET DATA
			$this->xlsCtrl->createBodyExcel($config,$data->getData()['data']);
			// IMPRIMIR REPORTE
			$this->xlsCtrl->printExcel();
		}else{
			$this->getJSON("Este reporte no existe");
		}
	}
	
	
	/*
	 * ************************************************************* MÓDULO DE REPORTES
	 */
	/*
	 * REPORTE INDIVIDUAL
	 * REPORTES CON MODELOS DEFINIDOS
	 */
	private function exportEntity(){
		// RECIBIR DATOS DE USUARIO
		$get=$this->requestGet();
		
		// NOMBRE ALTERNO DE ENTIDAD
		$tb=str_replace('vw_','',$get['entity']);
		// MODELO JSON DE REPORTES
		$info=$this->getConfig(true,'exports');
		
		// VALIDAR EL REGISTRO INTERNO - reports.json
		if(isset($info['filters'][$get['entity']])){
			// FILTRO PARA BÚSQUEDA DE FECHAS
			$param=$info['filters'][$get['entity']];
			// VERIFICAR TIPO DE FILTRADO
			$strWhere="{$param}::DATE>='{$get['date1']}'::DATE AND {$param}::DATE<='{$get['date2']}'::DATE";
			// VERIFICAR LÍMITE
			$strLimit=($get['limit']>0)?" LIMIT {$get['limit']}":"";
		}else{
			// VERIFICAR TIPO DE FILTRADO
			$strWhere="{$get['orderBy']}::DATE>='{$get['date1']}'::DATE AND {$get['orderBy']}::DATE<='{$get['date2']}'::DATE";
		}
		
		// ARMAR CONSULTA
		$str=$this->db->selectFromView("vw_{$tb}","WHERE ($strWhere) ORDER BY {$get['orderBy']} {$get['orderType']}");
		// echo $str;
		// return;
		
		// CONSULTAR TOTAL DE REGISTROS
		$numRows=$this->db->numRows($str);
		// VERIFICAR SI EXISTEN DATOS
		if($numRows>0){
			// VALIDAR QUE NO SE EXCEDA EL LIMITE
			if($numRows>1000 && $get['type']!='xls') $this->getJSON("Consulta abortada, existe un desbordamiento de memoria [LIMIT={$numRows}]");
			// DATOS DE CONSULTA
			$rows=$this->db->findAll($str);
			
			// CONFIGURACIÓN DE REPORTE
			$localConfig=$this->config['entities'][$tb];
			
			// VALIDAR TIPO DE REPORTE
			if($get['type']=='xls'){
				// DATOS DE CONFIGURACIÓN
				$xlsConfig=$this->config['xls'];
				// CONFIGURACIÓN DE ENTIDAD PARA REPORTE
				$localConfig=$xlsConfig[$tb];
				
				// DATOS DE REPORTE
				$config=$this->db->findById($localConfig['list'],$this->entity);
				// print_r($config);
				// DESCOMPRIMIR HEADER Y COLUMNAS
				$config['headers']=explode(',',$config['reporte_headers_xls']);
				$config['fields']=explode(',',$config['reporte_fields_xls']);
				
				// INSTANCIAR OBJETO XLS
				$this->xlsCtrl=new ctrl\xlsController();
				// CARGAR VARIABLES DEL SISTEMA Y PARÁMETROS
				$this->xlsCtrl->loadSetting($config);
				// DEFINIR HOJA DE REPORTE
				$this->xlsCtrl->spreadsheet->setActiveSheetIndex(0)->setTitle($config['reporte_header']);
				// SET DATA
				$this->xlsCtrl->createBodyExcel($config,$rows);
				// IMPRIMIR REPORTE
				$this->xlsCtrl->printExcel();
				
			}else{
				// DATOS DE REPORTE
				$config=$this->getReportInfo($localConfig['list']);
				// PLANTILLA DE REPORTE
				$template=file_get_contents(REPORTS_PATH."{$config['reporte_template']}.html");
				
				// CUERPO DE REPORTE
				$body='';
				// RECORRES FILAS DE DATOS
				foreach($rows as $index=>$row){
					$aux="<tr>";
					// IMPRIMIR FILAS DE REPORTE -> SOLO PLANTILLA
					foreach($localConfig['fields'] as $key){
						// CONCATENADOS
						$params=explode(',',$key);
						$valStr="";
						foreach($params as $key2){
							if($key=='index')$valStr.=($index+1);
							else $valStr.="{$row[$key2]} ";
						}
						$aux.="<td>{$valStr}</td>";
					}	
					$aux.="</tr>";
					$body.=$aux;
				}
				
				// INSTANCIA DE PDF
				$this->pdfCtrl=new ctrl\pdfController;
				
				// IMPRIMIR REPORTE
				$bodyHTML=preg_replace('/{{tbody}}/',$body,$template);
				// MONTAR REPORTE
				$this->preparePDF($config,$bodyHTML,$this->getConfParams());
			}
		}else{
			$this->getJSON("NO SE HAN ENCONTRADO REGISTROS");
		}
		// EXIT
		exit;
	}
	
	
	/*
	 * ************************************************************* REPORTE DIARIO
	 */
	/*
	 * REPORTE INDIVIDUAL
	 * IMPRIMIR REPORTE DIARIA SEGUN PERFIL
	 */
	private function dailyReport(){
		// DATOS DE ENTRADA
		$date['date']=isset($this->get['date'])?$this->get['date']:$this->getFecha();
		// VARIABLES - FECHAS
		$date['init']=isset($this->get['init'])?$this->get['init']:$date['date'];
		$date['final']=isset($this->get['final'])?$this->get['final']:$date['date'];
		
		/*1;"SU"
		2;"Cliente"
		3;"Administrador"
		4;"Recaudador(a)"
		5;"Jefe de Inspección"
		6;"Inspector Técnico"
		7;"Primer Jefe"*/
		$userAdmin=[1,3,5,7];
		
		$userInspector=[6];
		
		$userRecaudator=[4];
		
		$user=$this->db->findOne($this->db->selectFromView('usuarios',"WHERE usuario_id=".app\session::getSessionAdmin()));
		
		$userPerfil=$user['fk_perfil_id'];
		
		$joinStr=($user['fk_perfil_id']==6)?"AND fk_usuario_id={$userPerfil}":"";

		// VALIDAR PERMISOS PARA IMPRESIÓN DE REPORTE
		$dashboardModel=$this->getConfig(true,'dashboard');
		
		// OBTENER CONFIGURACIÓN DE REPORTE
		$config=$this->db->findOne($this->db->selectFromView('reportes',"WHERE reporte_id=18"));
		
		// BODY
		$body="";
		
		// OBTENER MODELO SQL DE CONSULTA
		foreach($dashboardModel['schemas'] as $module=>$entities){
			
			// REGISTRO DE PLANTILLA
			$temp=$this->varGlobal['REPORT_STAT_DIARIO_TEMPLATE'];
			$temp=preg_replace('/{{key}}/',$module,$temp);
			$tBody="";
			
			// REGISTRO DE MODULOS
			foreach($entities as $entity){
				// GENERACION SQL
				$sql=$dashboardModel['sql'][$entity];
				
				// GENERARA SENTENCIA SQL
				$strWhr=$sql['where'];
				foreach($date as $k=>$v){ $strWhr=str_replace('{{'.$k.'}}',$v,$strWhr); }
				
				// CALCULAR EL TOTAL
				$row=$this->db->findOne($strWhr);
				
				// SETTEAR DATOS
				$row['icon']=$entity;
				$row['name']=$sql['name'];
				$row['desc']=$row['total_costo'];
				$row['count']=$row['total'];
				
				$detail=$this->varGlobal['REPORT_STAT_DIARIO_TEMPLATE_TD'];
				foreach($this->parseModelStats($row) as $k=>$v){ $detail=preg_replace("/{{".$k."}}/",$v,$detail); }
				$tBody.=$detail;
				
			}
			
			$body.=preg_replace('/{{tbody}}/',$tBody,$temp);
			
		}
		
		// SETTEAR NOMBRE DE REPORTE
		$config['reporte_nombre']="{$config['reporte_nombre']} {$date['init']} A {$date['final']}";
		// PLANTILLA DE REPORTE
		$template=file_get_contents(REPORTS_PATH."{$config['reporte_template']}.html");
		// CUERPO DE REPORTE
		$bodyHTML=preg_replace('/{{templateReport}}/',$body,$template);
		// CUSTOM TEMPLATE
		$pdfCtrl->template=$config['reporte_template'];
		// IMPRIMIR REPORTE
		$this->preparePDF($config,$bodyHTML);
	}
	
}