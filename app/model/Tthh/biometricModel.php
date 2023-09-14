<?php namespace model\Tthh;
use api as app;

class biometricModel extends app\controller {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
    private $entity='biometrico_marcaciones';
	private $model=array('OPERATIVO'=>array(),'ADMINISTRATIVO'=>array());
	private $json=array();
	private $count=array();
	
	/*
	 * METODO CONSTRUCTOR
	 */
	public function __construct(){
	    // CONSTRUCTOR PARENT
		parent::__construct();
	}
	
	
	
	/*
	 * CONSULTAR SI ESTA EN UN PELOTON
	 */
	private  function validateStaffInPlatoon($personalId){
	    // DATOS DE PELOTON
	    $str=$this->db->selectFromView('vw_personal_operativo',"WHERE fk_personal_id={$personalId} AND tropa_estado='ACTIVO'");
	    // RETORNAR ESTADO DE CONSULTA
	    return array(
	        'status'=>$this->db->numRows($str)>0,
	        'data'=>$this->db->findOne($str)
	    );
	}
	
	/*
	 * PROCESAR ARCHIVO
	 */
	private function getInformationFile($srcFile){
	    // ABRIR ARCHIVO
	    $fil = fopen($srcFile,"r");
	    // CONTADOR AUXILIAR
	    $this->count=1;
	    
	    // INFORMACIÓN DE PERIODO
	    $periodInfo=$this->db->findById($this->post['fk_periodo_id'],'biometrico_periodos');
	    
	    // LISTADO DE BIOMETRICOS
	    $codesList=array();
	    // OBTENER INFORMACIÓN DE CÓDIGOS DE BIOMÉTRICO
	    foreach($this->db->findAll($this->db->selectFromView('personal',"WHERE biometrico_id IS NOT NULL")) as $personalInfo){
	        // INGRESAR INFORMACION DE PERSONAL Y FUNCIONES
	        $codesList[$personalInfo['biometrico_id']]=array(
	            'personal'=>$personalInfo,
	            'staffInfo'=>$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE personal_id={$personalInfo['personal_id']} AND entidad='PUESTOS'"))
	        );
	    }
	    
	    // RECORRER FICHERO
	    while($linje = fgets($fil)){
	        // LINE TO ARRAY
	        $tmpData = explode("\t",$linje);
	        // EXPLOIT DATA
	        $code = intval($tmpData[0]);
	        $date = $this->setFormatDate($tmpData[1]);
	        
	        // VALIDAR SI LA FECHA QUE SE VA A INGRESAR CORRESPONDE A LAS DEL PERIODO
	        if(strtotime($this->setFormatDate($date,'Y-m-d'))<strtotime($periodInfo['periodo_desde']) || strtotime($this->setFormatDate($date,'Y-m-d'))>strtotime($periodInfo['periodo_hasta'])) continue;
	        
	        // VALIDAR CONSULTA
	        if(!isset($codesList[$code])){
	            continue;
	            $this->getJSON("Error en la línea {$this->count} del archivo <b>{$srcFile}</b>.<br>No se ha podido generar el registro para el código de biométrico <b>{$code}</b>, complete este registro y vueva a intentar!");
	        }else{
	            
	            // INFORMACION DE PERSONAL
	            $staffInfo=$codesList[$code]['personal'];
	            
	            // INFORMACION DE PERFIL DE TRABAJO
	            $job=$codesList[$code]['staffInfo'];
	            
	            // CREACION DE MODELO DE BIOMETRICO SI NO EXISTE
	            if(!isset($this->model[$job['puesto_modalidad']][$code])){
	                
	                // GENERAR MODELO
	                $this->model[$job['puesto_modalidad']][$code]=array(
	                    'staff'=>$staffInfo,
	                    'job'=>$job,
	                    'platoon'=>$this->validateStaffInPlatoon($staffInfo['personal_id'])
	                );
	                
	            }
	            
    	        // ASIGNAR MARCACIONES
	            $this->model[$job['puesto_modalidad']][$code]['times'][]=$date;
	            
	            // VALIDAR EL REGISTRO
	            $tempDate=$this->setFormatDate($date,'Y-m-d');
	            $tempTime=$this->setFormatDate($date,'H:i');
	            if(!isset($this->model[$job['puesto_modalidad']][$code]['schedule'][$tempDate])) $this->model[$job['puesto_modalidad']][$code]['schedule'][$tempDate]=array();
	            if(!in_array($tempTime,$this->model[$job['puesto_modalidad']][$code]['schedule'][$tempDate])) $this->model[$job['puesto_modalidad']][$code]['schedule'][$tempDate][]=$tempTime;
    	        
	        }
	        
	        // AUMENTAR CONTADOR
	        $this->count++;
	        
	    }
	}
	
	
	/*
	 * OBTENER EL REGISTRO MAS CERCANO
	 */
	private function getClosest($val1, $val2, $target2){ 
	    return ($target2 - $val1 >= $val2 - $target2) ? $val2 : $val1; 
	}
	
	/*
	 * CALCULO DE HORARIO MAS CERCANO
	 */
	private function findClosestMarking($arr,$target){
        // Corner cases
        if ($target <= $arr[0]) return $arr[0];
        if ($target >= end($arr)) return end($arr);
        // Doing binary search
        $n = sizeof($arr);
        $i = 0;
        $j = $n;
        $mid = 0;
        while ($i < $j){
            $mid = ($i + $j) / 2;
            if ($arr[$mid] == $target) return $arr[$mid];
            // If target is less than array element, then search in left
            if ($target < $arr[$mid]){
                // If target is greater than previous to mid, return closest of two
                if ($mid > 0 && $target > $arr[$mid - 1]) return $this->getClosest($arr[$mid - 1], $arr[$mid], $target);
                // Repeat for left half
                $j = $mid;
            } else {
                // If target is greater than mid
                if ($mid < $n - 1 && $target < $arr[$mid + 1]) return $this->getClosest($arr[$mid], $arr[$mid + 1], $target);
                // update i
                $i = $mid + 1;
            }
        }
        // Only single element left after search
        return $arr[$mid];
	}
	
	/*
	 * VALIDAR REGISTROS OPERATIVOS
	 */
	private function validateDataAdministrativo($code,$schedule,$v){
	    
	    // REGISTROS ELIMINADOS
	    $trash=array();
	    
	    // RECORRER LISTADO DE MARCACIONES
	    foreach($v['schedule'] as $date=>$times){
	        
	        // VALIDAR QUE LOS REGISTRAOS NO ESTEN EN LA PAELERA
	        if(in_array($date,$trash)) continue;
	        
	        // GENERACIÓN DEMODELO TEMPORAL
	        $temp=array(
	            'fk_biometrico_id'=>$code,
	            'fk_personal_id'=>$v['staff']['personal_id'],
	            'fk_jornada_id'=>$v['staff']['fk_jornada_id'],
	            'fk_periodo_id'=>$this->post['fk_periodo_id'],
	            'fk_estacion_id'=>$this->post['fk_estacion_id'],
	            'marcacion_registro'=>$this->getFecha('dateTime')
            );
	        
	        // OBTENER DIA DE REGISTRO
	        $dia=$this->setFormatDate($date,'w');
	        // PARAMETROS DEL DIA DE REGISTRO
	        $scheduleDay=$schedule['times'][$dia];
	        
	        // ORDENAR REGISTROS
	        sort($times);
	        
	        // VALORES POR DEFECTO DE INGRESO
	        $inSystem="{$date} {$scheduleDay['horario_entrada']}";
	        // FECHA Y HORA DE PRIMERA MARCACION [HORA DE INGRESO]
	        $in=$this->setFormatDate("{$date} {$times[0]}",'Y-m-d H:i');
	        // CALCULAR EL TIEMPO ANTES O DESPUES DE LA MARCACION
	        $inExtra=$this->getExtraTime($in,$inSystem);
	        
	        // AGREGAR HORAS DE TRABAJO PARA EL CÁLCULO DE LA MARCACIÓN DE SALIDA
	        $nextDate=$this->setFormatDate($this->addHoursTodate($inSystem, $scheduleDay['horario_horas_dia']),'Y-m-d');
	        // LAST ITEM ARRAY
	        $end=end($v['schedule'][$this->setFormatDate($nextDate,'Y-m-d')]);
	        
	        // VALORES POR DEFECTO DE SALIDA
	        $outSystem="{$nextDate} {$scheduleDay['horario_salida']}";
            // REGISTRO DE SIGUIENTE MARCACION :: SALIDA
            $out=$this->setFormatDate("{$nextDate} {$end}",'Y-m-d H:i');
            // CALCULAR EL TIEMPO ANTES O DESPUES DE LA MARCACION
            $outExtra=$this->getExtraTime($outSystem,$out);
            
            // VALIDAR HORARIO DE ALMUERZO
            if($scheduleDay['horario_break']=='SI'){
                
                // TIMES TO INT
                $timesInt=array();
                // DATES TO INT
                foreach($times as $val){ $timesInt[]=strtotime("{$date} {$val}"); }
                
                // VALORES POR DEFECTO DE SALIDA A BREAK
                $inBreakSystem="{$date} {$scheduleDay['horario_entrada_break']}";
                // FECHA Y HORA DE PRIMERA MARCACION [HORA DE INGRESO]
                $inBreak=date('Y-m-d H:i',$this->findClosestMarking($timesInt, strtotime($inBreakSystem)));
                // CALCULAR EL TIEMPO ANTES O DESPUES DE LA MARCACION
                $inBreakExtra=$this->getExtraTime($inBreakSystem,$inBreak);
                
                // VALORES POR DEFECTO DE SALIDA
                $outBreakSystem="{$date} {$scheduleDay['horario_salida_break']}";
                // REGISTRO DE SIGUIENTE MARCACION :: SALIDA
                $outBreak=date('Y-m-d H:i',$this->findClosestMarking($timesInt, strtotime($outBreakSystem)));
                // CALCULAR EL TIEMPO ANTES O DESPUES DE LA MARCACION
                $outBreakExtra=$this->getExtraTime($outBreak,$outBreakSystem);
                
                // VALIDAR MARCACIONES DE ALMUERZO
                if($inBreakExtra<0){}
                if($outBreakExtra<0){}
                
                if( ($inBreak != $outBreak) && (strtotime($inBreak) > strtotime('+10 minute',strtotime($outBreak))) ){}
                
                // REGISTRO DE MARCACIONES DE ALMUERZO
                $temp['marcacion_salida_break_sistema']=$inBreakSystem;
                $temp['marcacion_salida_break']=$inBreak;
                $temp['marcacion_salida_break_extras']=$inBreakExtra;
                
                $temp['marcacion_ingreso_break_sistema']=$outBreakSystem;
                $temp['marcacion_ingreso_break']=$outBreak;
                $temp['marcacion_ingreso_break_extras']=$outBreakExtra;
                
            }
            
            // VALIDAR REGISTROS DE INGRESO Y SALIDA
            
            // REGISTRO DE MARCACIONES DE INGRESO
            $temp['marcacion_ingreso_sistema']=$inSystem;
            $temp['marcacion_ingreso']=$in;
            $temp['marcacion_ingreso_extras']=$inExtra;
            
            // REGISTRO DE MARCACIONES DE SALIDA
            $temp['marcacion_salida_sistema']=$outSystem;
            $temp['marcacion_salida']=$out;
            $temp['marcacion_salida_extras']=$outExtra;
            
            
            // REGISTRO DE OBSERVACIONES Y RANCHOS
            $temp['marcacion_estado']=($out==null)?'ERROR':'REGISTRO CORRECTO';
            $temp['marcacion_rancho']=($out==null)?0:$scheduleDay['horario_rancho'];
            $temp['marcacion_observacion']=($out==null)?'No registra marcacion de entrada o salida':'';
            
	        // CONSULTAR SI EXISTE REGISTRO
	        $str=$this->db->selectFromView($this->entity,"WHERE fk_personal_id={$temp['fk_personal_id']} AND marcacion_ingreso='{$temp['marcacion_ingreso']}'");
	        if($this->db->numRows($str)>0){
	            // OBTENER REGISTRO
	            $row=array_merge($this->db->findOne($str),$temp);
	            // GENERAR SENTENCIA SQL PARA EL REGISTRO DE MARCACIONES
	            $strQry=$this->db->getSQLUpdate($row,'biometrico_marcaciones');
	        }else{
	            // GENERAR SENTENCIA SQL PARA EL REGISTRO DE MARCACIONES
	            $strQry=$this->db->getSQLInsert($temp,'biometrico_marcaciones');
	        }
	        
	        // EJECUTAR SENTENCIA
	        $this->db->executeTested($strQry);
	        
	        // INGRESAR MODELO
	        $this->json[$code][]=$temp;
	        
	        // DESVINCULAR SIGUIENTE MARCACION
	        $trash[]=$date;
	        $trash[]=$nextDate;
	    }
	    
	}
	
	/*
	 * CALCULO DE TIEMPO EXTRA
	 */
	private function getExtraTime($date1,$date2){
	    $date1 = new \DateTime($date1);
	    $date2 = new \DateTime($date2);
	    $diff = $date1->diff($date2);
	    return ((int)$diff->format("%r%h") * 60) + (int)$diff->format("%r%i");
	    return ($diff->h * 60) + $diff->i;
	}
	
	/*
	 * VALIDAR REGISTROS ADMINISTRATIVOS 
	 */
	private function validateDataOperativo($code,$schedule,$v){
	    
	    // REGISTROS ELIMINADOS
	    $trash=array();
	    
	    // RECORRER LISTADO DE MARCACIONES
	    foreach($v['schedule'] as $date=>$times){
	        
	        // VALIDAR QUE LOS REGISTRAOS NO ESTEN EN LA PAELERA
	        if(in_array($date,$trash)) continue;
	        
	        // OBTENER DIA DE REGISTRO
	        $dia=$this->setFormatDate($date,'w');
	        // PARAMETROS DEL DIA DE REGISTRO
	        $scheduleDay=$schedule['times'][$dia];
	        
	        // ORDENAR REGISTROS
	        sort($times);
	        
	        // VALORES POR DEFECTO DE INGRESO Y SALIDA
	        $inSystem="{$date} {$scheduleDay['horario_entrada']}";
	        // FECHA Y HORA DE PRIMERA MARCACION
	        $in=$this->setFormatDate("{$date} {$times[0]}",'Y-m-d H:i');
	        // CALCULAR EL TIEMPO ANTES O DESPUES DE LA MARCACION
	        $inExtra=$this->getExtraTime($in,$inSystem);
	        
	        // AGREGAR HORAS DE TRABAJO
	        $nextDate=$this->setFormatDate($this->addHoursTodate($in, $scheduleDay['horario_horas_dia']),'Y-m-d');
	        // VALORES POR DEFECTO DE INGRESO Y SALIDA
	        $outSystem="{$nextDate} {$scheduleDay['horario_salida']}";
	        
	        // ELIMINAR REGISTRO DE SIGUIENTE FECHA
	        $out=null;
	        // CALCULO DE HORAS EXTRAS O SALIDAS ANTES DE HORA
	        $outExtra=null;
	        
	        // VALIDAR SI EXISTE REGISTRO DE SIGUIENTE MARCACION
	        if(!isset($v['schedule'][$this->setFormatDate($nextDate,'Y-m-d')])){
	            
	        }else{
	            
	            // LAST ITEM ARRAY 
	            $end=end($v['schedule'][$this->setFormatDate($nextDate,'Y-m-d')]);
	            // VALIDAR QUE EL REGISTRO DE INGRESO NO SEA EL MISMO DE SALIDA
	            if(($this->setFormatDate($in,'Y-m-d H') != $this->setFormatDate("{$nextDate} {$end}",'Y-m-d H')) && ($in != $this->setFormatDate("{$nextDate} {$end}",'Y-m-d H:i'))){
    	            // REGISTRO DE SIGUIENTE MARCACION :: SALIDA
    	            $out=$this->setFormatDate("{$nextDate} {$end}",'Y-m-d H:i');
                    // CALCULAR EL TIEMPO ANTES O DESPUES DE LA MARCACION
    	            $outExtra=$this->getExtraTime($outSystem,$out);
	            }
	            
	        }
	        
	        // MODELO TEMPORAL
	        $temp=array(
	            'fk_biometrico_id'=>$code,
	            
	            'fk_personal_id'=>$v['staff']['personal_id'],
	            'fk_jornada_id'=>$v['staff']['fk_jornada_id'],
	            'fk_periodo_id'=>$this->post['fk_periodo_id'],
	            'fk_estacion_id'=>$this->post['fk_estacion_id'],
	            
	            'marcacion_registro'=>$this->getFecha('dateTime'),
	            'marcacion_estado'=>($out==null)?'ERROR':'REGISTRO CORRECTO',
	            
	            'marcacion_ingreso_sistema'=>$inSystem,
	            'marcacion_ingreso'=>$in,
	            'marcacion_ingreso_extras'=>$inExtra,
	            
	            'marcacion_salida_sistema'=>$outSystem,
	            'marcacion_salida'=>$out,
	            'marcacion_salida_extras'=>$outExtra,
	            
	            'marcacion_rancho'=>($out==null)?0:$scheduleDay['horario_rancho'],
	            
	            'marcacion_observacion'=>($out==null)?'No registra marcacion de entrada o salida':''
	        );
	        
	        // CONSULTAR SI EXISTE REGISTRO
	        $str=$this->db->selectFromView($this->entity,"WHERE fk_personal_id={$temp['fk_personal_id']} AND marcacion_ingreso='{$temp['marcacion_ingreso']}'");
	        if($this->db->numRows($str)>0){
	            // OBTENER REGISTRO
	            $row=array_merge($this->db->findOne($str),$temp);
    	        // GENERAR SENTENCIA SQL PARA EL REGISTRO DE MARCACIONES
	            $strQry=$this->db->getSQLUpdate($row,'biometrico_marcaciones');
	        }else{
    	        // GENERAR SENTENCIA SQL PARA EL REGISTRO DE MARCACIONES
	            $strQry=$this->db->getSQLInsert($temp,'biometrico_marcaciones');
	        }
	        
	        // EJECUTAR SENTENCIA
	        $this->db->executeTested($strQry);
	        
	        // INGRESAR MODELO
	        $this->json[$code][]=$temp;
	        
	        // DESVINCULAR SIGUIENTE MARCACION
	        $trash[]=$date;
	        $trash[]=$nextDate;
	        
	    }
	    
	}
	
	/*
	 * VALIDAR REGISTROS
	 */
	private function insertTimes(){
	    // JORNADAS DE TRABAJO
	    $scheduleType=array();
	    
	    // OBENER HORARIOS DE JORNADAS DE TRABAJO
	    foreach($this->db->findAll($this->db->selectFromView('jornadas_trabajo')) as $v){
	        // CONSULTAR HORARIOS
	        $times=$this->db->findAll($this->db->selectFromView('jornadashorario',"WHERE fk_jornada_id={$v['jornada_id']}"));
	        // CARGAR MODELO TEMPORAL
	        $scheduleType[$v['jornada_id']]=array(
	            'data'=>$v,
	            'times'=>array()
	        );
	        // GENERAR REGISTRO INDIVIDUAL DE HORARIOS DIARIOS
	        foreach($times as $t){
	            // RECORRER DIAS
	            foreach($this->string2JSON($t['horario_dias_semana']) as $i){
	                // HORARIO DE TRABAJO
	                $scheduleType[$v['jornada_id']]['times'][$i]=array(
	                   'horario_rancho'=>$t['horario_rancho'],
	                   'horario_horas_dia'=>$t['horario_horas_dia'],
	                   'horario_entrada'=>$t['horario_entrada'],
	                   'horario_salida'=>$t['horario_salida'],
	                   'horario_break'=>$t['horario_break'],
	                   'horario_entrada_break'=>$t['horario_entrada_break'],
	                   'horario_salida_break'=>$t['horario_salida_break']
	               );
	            }
	        }
	    }
	    
	    // RECORRER MODELO GENERADO :: MODALIDAD DE TRABAJO
	    foreach($this->model as $type=>$data){
	        
	        // RECORRER REGISTROS DE MODALIDAD DE TRABAJO
	        foreach($data as $code=>$v){
	            
	            // JORNADA DE TRABAJO
	            $schedule=$scheduleType[$v['staff']['fk_jornada_id']];
	            
	            // VALIDAR TIPO DE REGISTRO
	            if($type=='OPERATIVO') $this->validateDataOperativo($code,$schedule,$v);
	            elseif($type=='ADMINISTRATIVO') $this->validateDataAdministrativo($code,$schedule,$v);
	            
	        }
	        
	    }
	    
	}
	
	/*
	 * LLAMADO AL METODO 
	 */
	public function processFile(){
	    
	    // CRAGAR ARCHIVO
	    $config=$this->uploaderMng->config['entityConfig'][$this->entity];
	    // PARAMETROS DE CONSULTA
	    $info=$this->uploaderMng->uploadASimpleCopy($config);
	    // VALIDAR CARGA DE ARCHIVO
	    if(!$info['estado']) $this->getJSON($info);
	    // NOMBRE DE ARCHIVO
	    $fileName=$info['mensaje'];
	    // OBTENER ARCHIVO
	    $pathFile=$this->uploaderMng->pathFile."{$config['folder']}/{$fileName}";
	    // COMPROBAR SI EL ARCHIVO SE HA SUBIDO CORRECTAMENTE
	    if(!file_exists($pathFile)) $this->getJSON($this->setJSON("No se pudo encontrar el archivo!"));
	    
	    // LEER INFORMACIÓN DE ARCHIVOS
	    $this->getInformationFile($pathFile);
	    
	    // INICIAR TRANSACCIÓN
	    $this->db->begin();
	    
	    // VALIDAR REGISTROS
	    $this->insertTimes();
	    
	    // CERRAR TRANSACCIÓN
	    $sql=$this->db->closeTransaction($this->setJSON("Operación realizada con éxito",true));
	    
	    // RETORNAR CONSULTA
	    $this->getJSON($sql);
		
	}
	
	
	/*
	 * IMPRIMIR REPORTE DE RANCHO POR ID DE PERIODO
	 */
	public function printRanchesByPeriodId($pdfCtrl,$id,$config){
	    // STRING DE CONSULTA
	    $str=$this->db->selectFromView('biometrico_periodos',"WHERE periodo_id={$id}");
	    // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        
	        // OBTENER DATOS DE PERMISO
	        $row=$this->db->findOne($str);
	        
	        // VARIABLE PARA ALMACENAMIENTO DE FILAS
	        $row['ranchoList']="";
	        $row['ranchoTotal']=0;
	        
	        // CONSULTAR VALORES POR RANCHO
	        foreach($this->db->findAll($this->db->selectFromView('vw_periodos_rancho',"WHERE fk_periodo_id={$id}")) as $k=>$v){
	            $i=$k+1;
	            // IMPRESION DE REGISTROS
	            $row['ranchoList'].="<tr><td>{$i}</td><td>{$v['identificacion']}</td><td>{$v['personal']}</td><td>$ {$v['rancho']}</td></tr>";
	            // SUMATORIA DE RANCHO
	            $row['ranchoTotal']+=$v['rancho'];
	        }
	        
	        // PARSE DATA
	        $row['ranchoTotal_alt']=$this->setNumberToLetter($row['ranchoTotal'],true);
	        $row['periodo_desde_alt']=$this->setFormatDate($row['periodo_desde'],'complete');
	        $row['periodo_hasta_alt']=$this->setFormatDate($row['periodo_hasta'],'complete');
	        
	        // DATOS DE RESPONSABLE DE REGISTRO
	        $fkRegistra=explode('+',$row['fk_registra']);
	        $temp=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad='{$fkRegistra[0]}' AND entidad_id={$fkRegistra[1]}"));
	        $row['registra_nombre']=$temp['personal_titulo'];
	        $row['registra_puesto']=$temp['puesto_definicion'];
	        
	        // DATOS DE RESPONSABLE DE APROBACIÓN
	        $fkAprueba=explode('+',$row['fk_aprueba']);
	        $temp=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad='{$fkAprueba[0]}' AND entidad_id={$fkAprueba[1]}"));
	        $row['aprueba_nombre']=$temp['personal_titulo'];
	        $row['aprueba_puesto']=$temp['puesto_definicion'];
	        
	        // PARSE DATA
	        $row['ranchoTotal']=$this->setFormatNumber($row['ranchoTotal']);
	        
	        // CARGAR DATOS DE OCASIONAL
	        $pdfCtrl->loadSetting($row);
	        
	        // CUSTOM TEMPLATE
	        $pdfCtrl->template=$config['reporte_template'];
	        // SETTEAR NOMBRE ALTERNO
	        $pdfCtrl->setCustomHeader($row,'periodos_rancho');
	        
	    } else {
	        // RETORNAR CONSULTA
	        return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
	    }
	    
	    return '';
	}
	
	/*
	 * IMPRIMIR REPORTE DE MARCACIONES POR ID DE PERIODO
	 */
	public function printMarkingByPeriodId($pdfCtrl,$id,$config){
	    // STRING DE CONSULTA
	    $str=$this->db->selectFromView('biometrico_periodos',"WHERE periodo_id={$id}");
	    // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        
	        // OBTENER DATOS DE PERMISO
	        $row=$this->db->findOne($str);
	        
	        // VARIABLE PARA ALMACENAMIENTO DE FILAS
	        $row['detailList']="";
	        // TEMPLATE
	        $tableTemplate=$this->varGlobal['BIOMETRICO_MARCACIONES_LIST_TEMPLATE'];
	        
	        // RECORRER LISTADO DE PERSONAL CON MARCACIONES
	        foreach($this->db->findAll($this->db->selectFromView('vw_biometrico_marcaciones_personal',"WHERE fk_periodo_id={$id} ORDER BY personal_nombre")) as $v){
	            // REGISTRAR TEMPLATE
	            $aux=$tableTemplate;
	            $v['rancho_alt']=$this->setNumberToLetter($v['totalrancho'],true);
	            // IMPRESION DE DATOS DE PERSONAL
	            foreach($v as $kk=>$vv){ $aux=preg_replace('/{{'.$kk.'}}/',$vv,$aux); }
	            $tr="";
	            // CONSULTAR E IMPRIMIR MARCACIONES DE PERSONAL
	            foreach($this->db->findAll($this->db->selectFromView('biometrico_marcaciones',"WHERE fk_periodo_id={$id} AND fk_personal_id={$v['fk_personal_id']}")) as $k=>$m){
	                $i=$k+1;
	                // IMPRESION DE REGISTROS
	                $tr.="<tr><td>{$i}</td>".
	   	                 "<td>{$m['marcacion_ingreso']}<br><small>Diferencia: {$m['marcacion_ingreso_extras']} minutos</small></td>".
	                     "<td>{$m['marcacion_salida']}<br><small>Diferencia: {$m['marcacion_salida_extras']} minutos</small></td>".
	                     "<td>{$m['marcacion_estado']}</td>".
	                     "<td>$ {$m['marcacion_rancho']}</td>".
	                     "</tr>";
	            }
	            $aux=preg_replace('/{{trMarkings}}/',$tr,$aux);
	            // IMPRESION
	            $row['detailList'].="{$aux}";
	            
	        }
	        
	        // PARSE DATES
	        $row['periodo_desde_alt']=$this->setFormatDate($row['periodo_desde'],'complete');
	        $row['periodo_hasta_alt']=$this->setFormatDate($row['periodo_hasta'],'complete');
	        
	        // DATOS DE RESPONSABLE DE REGISTRO
	        $fkRegistra=explode('+',$row['fk_registra']);
	        $temp=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad='{$fkRegistra[0]}' AND entidad_id={$fkRegistra[1]}"));
	        $row['registra_nombre']=$temp['personal_titulo'];
	        $row['registra_puesto']=$temp['puesto_definicion'];
	        
	        // DATOS DE RESPONSABLE DE APROBACIÓN
	        $fkAprueba=explode('+',$row['fk_aprueba']);
	        $temp=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad='{$fkAprueba[0]}' AND entidad_id={$fkAprueba[1]}"));
	        $row['aprueba_nombre']=$temp['personal_titulo'];
	        $row['aprueba_puesto']=$temp['puesto_definicion'];
	        
	        // CARGAR DATOS DE OCASIONAL
	        $pdfCtrl->loadSetting($row);
	        
	        // CUSTOM TEMPLATE
	        $pdfCtrl->template=$config['reporte_template'];
	        // SETTEAR NOMBRE ALTERNO
	        $pdfCtrl->setCustomHeader($row,'periodos_marcaciones');
	        
	    } else {
	        // RETORNAR CONSULTA
	        return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
	    }
	    
	    return '';
	}
	
	
	/*
	 * IMPRIMIR REPORTE DE RANCHO POR ID DE PERIODO
	 */
	public function printArrearsByPeriodId($pdfCtrl,$id,$config){
	    // STRING DE CONSULTA
	    $str=$this->db->selectFromView('biometrico_periodos',"WHERE periodo_id={$id}");
	    // VALIDAR SI EXISTE PERMISO DE FUNCIONAMIENTO
	    if($this->db->numRows($str)>0){
	        
	        // OBTENER DATOS DE PERMISO
	        $row=$this->db->findOne($str);
	        
	        // VARIABLE PARA ALMACENAMIENTO DE FILAS
	        $row['detailList']="";
	        
	        // CONSULTAR VALORES POR RANCHO
	        foreach($this->db->findAll($this->db->selectFromView('vw_periodos_atrasos',"WHERE fk_periodo_id={$id}")) as $k=>$v){
	            $i=$k+1;
	            // IMPRESION DE REGISTROS
	            $row['detailList'].="<tr>
        				<td>{$i}</td>
        				<td>{$v['personal_nombre']}<br>C.C.: {$v['persona_doc_identidad']}</td>
        				<td>{$v['atrasosingreso']}</td><td>{$v['atrasossalida']}</td>
        				<td>{$v['atrasosingresobreak']}</td><td>{$v['atrasossalidabreak']}</td>
        				<td>{$v['atrasototal']}</td>
        			</tr>";
	        }
	        
	        // PARSE DATES
	        $row['periodo_desde_alt']=$this->setFormatDate($row['periodo_desde'],'complete');
	        $row['periodo_hasta_alt']=$this->setFormatDate($row['periodo_hasta'],'complete');
	        
	        // DATOS DE RESPONSABLE DE REGISTRO
	        $fkRegistra=explode('+',$row['fk_registra']);
	        $temp=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad='{$fkRegistra[0]}' AND entidad_id={$fkRegistra[1]}"));
	        $row['registra_nombre']=$temp['personal_titulo'];
	        $row['registra_puesto']=$temp['puesto_definicion'];
	        
	        // DATOS DE RESPONSABLE DE APROBACIÓN
	        $fkAprueba=explode('+',$row['fk_aprueba']);
	        $temp=$this->db->findOne($this->db->selectFromView('vw_personal_funciones',"WHERE entidad='{$fkAprueba[0]}' AND entidad_id={$fkAprueba[1]}"));
	        $row['aprueba_nombre']=$temp['personal_titulo'];
	        $row['aprueba_puesto']=$temp['puesto_definicion'];
	        
	        // PARSE DATA
	        $row['ranchoTotal']=$this->setFormatNumber($row['ranchoTotal']);
	        
	        // CARGAR DATOS DE OCASIONAL
	        $pdfCtrl->loadSetting($row);
	        
	        // CUSTOM TEMPLATE
	        $pdfCtrl->template=$config['reporte_template'];
	        // SETTEAR NOMBRE ALTERNO
	        $pdfCtrl->setCustomHeader($row,'periodos_rancho');
	        
	    } else {
	        // RETORNAR CONSULTA
	        return $pdfCtrl->printCustomReport('<li>No existe el regstro</li>','usuario',$this->varGlobal['EMPTY_REPORT_DESCRIPTION']);
	    }
	    
	    return '';
	}
	
}