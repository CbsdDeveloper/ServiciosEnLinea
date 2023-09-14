<?php namespace controller;
use api as app;
/**
 * Description of uploadFile
 * controller.php permite heredar parte de los métodos a controladores como usuario
 * conexión de datos,los cuales realizan las consultas respectivas
 */
class uploaderController implements app\myJSON {
	
	/*
	 * VARIABLES DE ENTORNO
	 */
	public $pathFile;
	public $RealName;
	public $config;
	public $separator=DS;
	private $errorInfo=array(
		0=>UPLOAD_E0,
		1=>UPLOAD_E1,
      	2=>UPLOAD_E2,
		3=>UPLOAD_E3,
		4=>UPLOAD_E4,
		6=>UPLOAD_E6
	); 
	
	/*
	 * MÉTODO CONSTRUCTOR
	 */
	public function __construct(){
		// PARÁMETROS DE UPLOADER
		$this->config=json_decode(file_get_contents(SRC_PATH.'config/uploader.json'),true);
	}
	
	/*
	 * ELIMINAR CARACTERES ESPECIALES
	 */
	public function cleanSpecialCharacters($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
		return strtolower(preg_replace('/-+/', '-', $string)); // Replaces multiple hyphens with single one.
	}
	
	/*
	 * FORMATEAR CONSULTA
	 */
	public function setJSON($msg,$est=false,$data=array()){
	    return array('mensaje'=>$msg,'estado'=>$est,'data'=>$data);
	}
	
	/*
	 * RETORNAR CONSULTA
	 */
	public function getJSON($json){
		echo json_encode($json,JSON_PRETTY_PRINT);
		exit;
	}
	
	/*
	 * CONVERTIR PATH
	 */
	public function getPath($folder){
		return (strtoupper(substr(PHP_OS,0,3))==='WIN')?str_replace('/','\\',$folder):str_replace('\\','/',$folder);
	}
	
	/*
	 * TEST - ERROR
	 */
	private function testError($errorId,$fileName=''){
		// MENSAJE
		$error="{$this->errorInfo[$errorId]}";
		// VALIDAR ERROR - 1
		if($errorId==1) $error="{$fileName} :: {$error} -> " . ini_get('upload_max_filesize');
		// RETORNAR MENSAJE
		$this->getJSON($error);
	}
	
	/*
	 * SUBIR CUALQUIER ARCHIVO
	 * PARAMETROS DE ENTRADA: - NOMBRE O RUTA DE DESTINO, - NOMBRE DEL FILES
	 * COMO RESULTADO RETORNA EL NOMBRE CON EL QUE SE SUBE (Y-m-d	h:i:sa),O FALSE SI NO SE SUBE 
	 */
	public function uploadFile($data){
		// VALIDAR SI HA SIDO SETTEADO EL NOMBRE DEL ARCHIVO
		if(isset($_FILES[$data['fileName']]) && !empty($_FILES[$data['fileName']]['name'])){
			// NOMBRE DE ARCHIVO
			$file=$_FILES[$data['fileName']];
			// OBTENER EXTENSIONES DE ARCHIVOS
			$configFile=$this->config['extConfig'];
			// EXTENSIÓN DE ARCHIVO
			$temporary=explode(".",$file["name"]);
			$file_ext=strtolower(end($temporary));
			// NOMBRE TEMPORAL
			$tempName=date('Y-m-d-h-i-s').".{$file_ext}";
			// NOMBRE DE ARCHIVO DESPUES DE LA CARGA
			if($data['sufijoFile']!="") $data['fileName']="{$data['sufijoFile']}{$tempName}";
			else $data['fileName']=rand()."_{$tempName}";
			// ERROR
			if($file['error']>0) $this->getJSON($this->errorInfo[$file['error']]);
			// VALIDACIÓN DE TIPO DE ARCHIVO
			if(($data['extFile']=="all") || (in_array(strtolower($file['type']),$configFile['extMime'][$data['extFile']]) && in_array(strtolower($file_ext),$configFile['extEnd'][$data['extFile']]))){
				// VALIDAR ERRORES
				if($file['error']>0) return $this->setJSON($this->errorInfo[$file['error']]);
				else {
					// SETTEAR NOMBRE DE ARCHIVO
					$dir="{$this->pathFile}{$data['folder']}";
					// VALIDAR SI EL DIRECTORIO EXISTE
					if(!is_dir($this->getPath($dir))){
						// CREAR DIRECTORIO DE DESTINO
						if(!mkdir($this->getPath($dir), 0777, true)) $this->getJSON("No existe el directorio de destino...");
					}
					// VALIDAR SI EL DESTINO ES UN DIRECTORIO
					if(is_dir($this->getPath($dir))){
						// NOMBRE DEL ARCHIVO
						$fileName=$this->getPath("{$dir}{$this->separator}{$data['fileName']}");
						// COPIAR ARCHIVO A DESTINO
						if(move_uploaded_file($file['tmp_name'],$fileName)){
							if(isset($data['columnName'])) {
								// MENSAJE - CARGA CORRECTA PARA INGRESO DE BASE DE DATOS
								$json=$this->setJSON(array($data['columnName']=>$data['fileName']),true);
							}else{
								// MENSAJE - CARGA CORRECTA
								$json=$this->setJSON($data['fileName'],true);
							}
						}else{
							// MENSAJE - ERROR EN LA CARGA DE ARCHIVO
							$json=$this->setJSON(UPLOAD_ERROR);
						}
					}else{
						// MENSAJE - NO EXISTE DIRECTORIO DE DESTINO
						$json=$this->setJSON(DIRECTORY_404);
					}
				}
			}	else {
				// SETTEAR ERROR EN LA CARGA DEL ARCHIVO
				$json=$this->setJSON(FILE_FORMAT_ERROR." <b>{$configFile['extMsg'][$data['extFile']]}</b>");
			}
			// RETORNAR OPERACIÓN
			return $json;
		}	
		// RETORNAR MENSAJE - ARCHIVO VACIO 
		return $this->setJSON(FILE_EMPTY);
	}
	
	/*
	 * SUBIR ARCHIVOS SIN CAMBIAR NOMBRE
	 */
	public function uploadASimpleCopy($data){
	    // VALIDAR SI HA SIDO SETTEADO EL NOMBRE DEL ARCHIVO
	    if(isset($_FILES[$data['fileName']]) && !empty($_FILES[$data['fileName']]['name'])){
	        
	        // OBTENER EXTENSIONES DE ARCHIVOS
	        $configFile=$this->config['extConfig'];
	        
	        // NOMBRE DE ARCHIVO
	        $file=$_FILES[$data['fileName']];
	        
	        // NOMBRE DE ARCHIVO
	        $data['fileName']=$file["name"];
	        
	        // EXTENSIÓN DE ARCHIVO
	        $temporary=explode(".",$data['fileName']);
	        $file_ext=strtolower(end($temporary));
	        
	        
	        // VALIDACION DE POSIBLES ERROES
	        if($file['error']>0) $this->getJSON($this->errorInfo[$file['error']]);
	        
	        
	        // VALIDACIÓN DE TIPO DE ARCHIVO
	        if(($data['extFile']=="all") || (in_array(strtolower($file['type']),$configFile['extMime'][$data['extFile']]) && in_array(strtolower($file_ext),$configFile['extEnd'][$data['extFile']]))){
	            
	            
	                
	                // SETTEAR NOMBRE DE ARCHIVO
	                $dir="{$this->pathFile}{$data['folder']}";
	                
	                // VALIDAR SI EL DIRECTORIO EXISTE
	                if(!is_dir($this->getPath($dir))){
	                    // CREAR DIRECTORIO DE DESTINO
	                    if(!mkdir($this->getPath($dir), 0777, true)) $this->getJSON("No existe el directorio de destino...");
	                }
	                
	                // VALIDAR SI EL DESTINO ES UN DIRECTORIO
	                if(is_dir($this->getPath($dir))){
	                    
	                    // NOMBRE DEL ARCHIVO
	                    $fileName=$this->getPath("{$dir}{$this->separator}{$data['fileName']}");
	                    
	                    // COPIAR ARCHIVO A DESTINO
	                    if(move_uploaded_file($file['tmp_name'],$fileName)){
                        
                            // MENSAJE - CARGA CORRECTA
                            $json=$this->setJSON($data['fileName'],true);
                        
                           
	                    }else{
	                        // MENSAJE - ERROR EN LA CARGA DE ARCHIVO
	                        $json=$this->setJSON(UPLOAD_ERROR);
	                    }
	                    
	                }else{
	                    // MENSAJE - NO EXISTE DIRECTORIO DE DESTINO
	                    $json=$this->setJSON(DIRECTORY_404);
	                }
	            
	        }	else {
	            // SETTEAR ERROR EN LA CARGA DEL ARCHIVO
	            $json=$this->setJSON(FILE_FORMAT_ERROR." <b>{$configFile['extMsg'][$data['extFile']]}</b>");
	        }
	        
	        // RETORNAR OPERACIÓN
	        return $json;
	    }
	    // RETORNAR MENSAJE - ARCHIVO VACIO
	    return $this->setJSON(FILE_EMPTY);
	}
	
	/*
	 * CARGAR MULTIPLES ARCHIVOS AL SERVIDOR
	 */
	private function uploadFolder($data,$config){
		// VALIDAR SI HA SIDO SETTEADO EL NOMBRE DEL ARCHIVO
		if(isset($_FILES['file']) && !empty($_FILES['file']['name'])){
			// LISTA DE ARCHIVOS
			$list=array();
			// RECORRER ARCHIVOS
			foreach($_FILES['file']['name'] as $idx=>$fileName){
				// SETTEAR NOMBRE DE ARCHIVO
				$folder="{$this->pathFile}{$config['folder']}{$data[$config['folderName']]}";
				// VALIDAR SI EL DIRECTORIO EXISTE
				if(!is_dir($this->getPath($folder))){
					// CREAR DIRECTORIO DE DESTINO
					if(!mkdir($this->getPath($folder), 0777, true)) $this->getJSON("No existe el directorio de destino...");
				}
				// ERROR
				if($_FILES['file']['error'][$idx]>0) $this->testError($_FILES['file']['error'][$idx],"{$idx}.- {$fileName}");
				// NOMBRE DEL ARCHIVO
				$file=$this->getPath("{$folder}{$this->separator}{$fileName}");
				// COPIAR ARCHIVO A DESTINO
				if(move_uploaded_file($_FILES['file']['tmp_name'][$idx],$file)){
					// AGREGAR ARCHIVO A LA LISTA
					$list[]=$fileName;
				}else{
					// MENSAJE - ERROR EN LA CARGA DE ARCHIVO
					$this->getJSON(UPLOAD_ERROR."<br> {$fileName}");
				}
			}
			// RETURN CONSULTA
			return $list;
		}
		// RETORNAR MENSAJE - ARCHIVO VACIO
		$this->getJSON(FILE_EMPTY);
	}
	
	/*
	 * ELIMINAR ARCHIVOS
	 */
	public function deleteFile($folder){
		$file=$this->getPath($this->pathFile."{$folder}{$this->separator}{$_GET['id']}");
		return (unlink($file))?$this->setJSON(DELETE_OK,true):$this->setJSON(DELETE_ERROR);
	}
	
	/*
	 * ELIMINAR ARCHIVO CON NOMBRE
	 */
	public function deleteItem($id,$folder='usuarios'){
		$file=$this->getPath($this->pathFile."{$folder}{$this->separator}{$id}");
		return ($id=='default.png')?false:unlink($file);
	}
	
	/*
	 * ELIMINAR UN FICHERO ESPECIFICO
	 */
	public function deleteFileFormPath($path){
		// OBTENER PATH REAL
		$file=$this->getPath("{$this->pathFile}{$path}");
		// VALIDAR SI EXISTE EL FICHERO
		if(!file_exists($file)) return $this->setJSON("Error Ex001, el directorio o fichero que desea eliminar no existe o ya ha sido eliminado!<br>({$file})",false); 
		// ELIMINAR REGISTRO
		$status=unlink($file);
		// RETORNAR CONSULTA
		return $this->setJSON(($status?"Registro eliminado correctamente.":"Error desconocido Ex002. No se pudo eliminar su registro."),$status);
	}

	
	/*
	 * DECODE BASE64 TO IMG
	 */
	public function decodeBase64ToImg($post,$tb,$insert=true){
		// MODELO FILE
		$fileModel=array();
		// PARÁMETROS DE ENTIDAD
		$config=$this->config['entityConfig'][$tb];
		// VALIDAR SI SE HA ENVIADO UN ARCHIVO
		if(isset($post[$config['fileName']]) && strlen($post[$config['fileName']])>0){
			
			// QUITAR PREFIJOS
			$img=str_replace('data:image/png;base64,','',$post[$config['fileName']]);
			$img=str_replace('data:image/jpeg;base64,','',$img);
			// QUITAR PREFIJOS
			$img=str_replace(' ','+',$img);
			// DECODE IMG
			$img=base64_decode($img);
			
			// NOMBRE TEMPORAL
			$tempName=date('Y-m-d-h-i-s').".png";
			
			// NOMBRE DE ARCHIVO DESPUES DE LA CARGA
			if(isset($config['fileToName'])) $tempName="{$post[$config['fileToName']]}.png";
			elseif(isset($config['updateName']) && !$insert && !in_array($post[$config['columnName']],[null,''])) $tempName="{$post[$config['columnName']]}";
			
			// SUFIJO DE NOMBRE
			if(isset($config['sufijoFile']) && !in_array($config['sufijoFile'],[null,''])) $tempName="{$config['sufijoFile']}{$tempName}";
			else $tempName=rand()."_{$tempName}";
			
			// SETTEAR NOMBRE DE ARCHIVO
			$folder="{$this->pathFile}{$config['folder']}";
			// VALIDAR SI EL DIRECTORIO EXISTE
			if(!is_dir($this->getPath($folder))){
				// CREAR DIRECTORIO DE DESTINO
				if(!mkdir($folder, 0777, true)) $this->getJSON("No existe el directorio de destino...");
			}
			
			// NOMBRE DEL ARCHIVO
			$file=$this->getPath("{$folder}{$this->separator}{$tempName}");
			
			// SETTEAR NOMBRE DE ARCHIVO Y GUADAR IMAGEN
			if(file_put_contents($file,$img)){
				// VALIDAR TIPO DE RETORNO
				if(isset($config['columnName'])) {
					// MENSAJE - CARGA CORRECTA PARA INGRESO DE BASE DE DATOS
					$json=$this->setJSON(array($config['columnName']=>$tempName),true);
				} else {
					// MENSAJE - CARGA CORRECTA
					$json=$this->setJSON($tempName,true);
				}
			}else{
				// MENSAJE - ERROR EN LA CARGA DE ARCHIVO
				$json=$this->setJSON(UPLOAD_ERROR);
			}
			
		}else{
			// RETORNAR MENSAJE - ARCHIVO VACIO
			$json=$this->setJSON(FILE_EMPTY);
		}
		// VALIDAR LA CARGA DEL ARCHIVO FUE CORRECTA
		if($json['estado']){
			$fileModel=$json['mensaje'];
		}else{
			// VALIDAR SI ES UN INSERT Y EXISTE UN NOMBRE POR DEFECTO
			if($insert && isset($config['default'])) $fileModel=array($config['columnName']=>$config['default']);
			// VALIDAR SI ES UN INSERT Y NO EXISTE UN NOMBRE POR DEFECTO
			if($insert && !isset($config['default'])) $this->getJSON($json);
		}
		// RETORNAR MODELO
		return array_merge($post,$fileModel);
	}
	
	
	/*
	 * DECODE BASE64 TO IMG
	 */
	public function decodeSimpleBase64ToImg($post,$tb,$insert=true){
	    // MODELO FILE
	    $fileModel=array();
	    // PARÁMETROS DE ENTIDAD
	    $config=$this->config['entityConfig'][$tb];
	    // VALIDAR SI SE HA ENVIADO UN ARCHIVO
	    if(isset($post[$config['columnName']]) && strlen($post[$config['columnName']])>0){
	        
	        // QUITAR PREFIJOS
	        $img=str_replace('data:image/png;base64,','',$post[$config['columnName']]);
	        $img=str_replace('data:image/jpeg;base64,','',$img);
	        // QUITAR PREFIJOS
	        $img=str_replace(' ','+',$img);
	        // DECODE IMG
	        $img=base64_decode($img);
	        
	        // NOMBRE TEMPORAL
	        $tempName=date('Y-m-d-h-i-s').".png";
	        
	        // NOMBRE DE ARCHIVO DESPUES DE LA CARGA
	        if(isset($config['fileToName'])) $tempName="{$post[$config['fileToName']]}.png";
	        
	        // SUFIJO DE NOMBRE
	        if(isset($config['sufijoFile']) && !in_array($config['sufijoFile'],[null,''])) $tempName="{$config['sufijoFile']}{$tempName}";
	        
	        // SETTEAR NOMBRE DE ARCHIVO
	        $folder="{$this->pathFile}{$config['folder']}";
	        // VALIDAR SI EL DIRECTORIO EXISTE
	        if(!is_dir($this->getPath($folder))){
	            // CREAR DIRECTORIO DE DESTINO
	            if(!mkdir($folder, 0777, true)) $this->getJSON("No existe el directorio de destino...");
	        }
	        
	        // NOMBRE DEL ARCHIVO
	        $file=$this->getPath("{$folder}{$this->separator}{$tempName}");
	        
	        // SETTEAR NOMBRE DE ARCHIVO Y GUADAR IMAGEN
	        if(file_put_contents($file,$img)){
	            // VALIDAR TIPO DE RETORNO
	            if(isset($config['columnName'])) {
	                // MENSAJE - CARGA CORRECTA PARA INGRESO DE BASE DE DATOS
	                $json=$this->setJSON(array($config['columnName']=>$tempName),true);
	            } else {
	                // MENSAJE - CARGA CORRECTA
	                $json=$this->setJSON($tempName,true);
	            }
	        }else{
	            // MENSAJE - ERROR EN LA CARGA DE ARCHIVO
	            $json=$this->setJSON(UPLOAD_ERROR);
	        }
	        
	    }else{
	        // RETORNAR MENSAJE - ARCHIVO VACIO
	        $json=$this->setJSON(FILE_EMPTY);
	    }
	    // VALIDAR LA CARGA DEL ARCHIVO FUE CORRECTA
	    if($json['estado']){
	        $fileModel=$json['mensaje'];
	    }else{
	        // VALIDAR SI ES UN INSERT Y EXISTE UN NOMBRE POR DEFECTO
	        if($insert && isset($config['default'])) $fileModel=array($config['columnName']=>$config['default']);
	        // VALIDAR SI ES UN INSERT Y NO EXISTE UN NOMBRE POR DEFECTO
	        if($insert && !isset($config['default'])) $this->getJSON($json);
	    }
	    // RETORNAR MODELO
	    return array_merge($post,$fileModel);
	}
	
	
	// *********************************************************************************************
	// MÉTODOS PARA MANEJO DE ARCHIVOS *************************************************************
	// *********************************************************************************************
	/* uploadFileService: cargar archivos al sistema
	 * post: datos de entrada (formulario json)
	 * tb: entidad relacionada. Ej. usuarios
	 * insert: declarar si es insert o update
	 * return: post + {entidadFile: nombreFile}
	 */
	public function uploadFileService($post,$tb,$insert=true){
		// MODELO FILE
		$fileModel=array();
		// PARÁMETROS DE ENTIDAD
		$config=$this->config['entityConfig'][$tb];
		// VALIDACIÓN DE EXTENSIONES ACEPTADAS
		if($config['extFile']=='custom')$config['extFile']=strtolower($post[$config['column_type']]);
		// SUBIR ARCHIVO CON SERVICIO
		$sql=$this->uploadFile($config);
		// VALIDAR LA CARGA DEL ARCHIVO FUE CORRECTA
		if($sql['estado'])$fileModel=$sql['mensaje'];
		else{
			// VALIDAR SI ES UN INSERT Y EXISTE UN NOMBRE POR DEFECTO
			if($insert && isset($config['default']))$fileModel=array($config['columnName']=>$config['default']);
			// VALIDAR SI ES UN INSERT Y NO EXISTE UN NOMBRE POR DEFECTO
			if($insert && !isset($config['default']))$this->getJSON($sql['mensaje']);
		}
		// RETORNAR MODELO
		return array_merge($post,$fileModel);
	}
	
	/*
	 * CARGA DE MÚLTIPLES ARCHIVOS
	 */
	public function uploadFolderService($post,$tb){
		// PARÁMETROS DE ENTIDAD
		$config=$this->config['folderConfig'][$tb];
		// VALIDAR CAMPO IDENTIFICADOR PARA CARPETA DE ARCHIVO
		if(!isset($post[$config['folderName']])) $this->getJSON("No se ha configurado el nombre del directorio...");
		// SETTEAR NUEVO TAMAÑO PARA SUBIR ARCHIVO
		if(isset($config['upload_max_filesize'])) ini_set('upload_max_filesize',$config['upload_max_filesize']);
		// INVOCAR AL MÉTODO Y RETORNAR CONSULTA
		return $this->uploadFolder($post,$config);
	}
	
	/*
	 * DESCARGAR ARCHIVOS
	 */
	public function downloadFolder($files,$entity,$tb){
	    // PARÁMETROS DE ENTIDAD
	    $config=$this->config['folderConfig'][$tb];
	    // INSTANCIA DE ARCHIVOS
	    $zip= new zipController;
	    // CÓDIGO DE PROYECTO
	    $code=$entity[$config['folderName']];
	    // FOLDER
	    $folder=$this->getPath("{$this->pathFile}{$config['folder']}{$this->separator}{$code}");
	    // ESTADO DE ARCHIVOS
	    $this->getJSON($zip->downloadFolder($files,$folder,$code));
	}
	
	/*
	 * ELIMNAR ARCHIVOS
	 */
	public function deletSingleFile($fileName,$entity,$tb){
	    // PARÁMETROS DE ENTIDAD
	    $config=$this->config['folderConfig'][$tb];
	    // CÓDIGO DE PROYECTO
	    $code=$entity[$config['folderName']];
	    // FOLDER
	    $temp=$this->getPath("{$this->pathFile}{$config['folder']}{$code}{$this->separator}{$fileName}");
	    // VALIDAR SI EXISTE EL ARCHIVO
	    $exist=(file_exists($temp));
	    // VALIDAR RESPUESTA
	    if($exist){
	        return (unlink($temp))?$this->setJSON(DELETE_OK,true):$this->setJSON(DELETE_ERROR);
	    }else{
	        return $this->setJSON("El archivo no existe.",true);
	    }
	}
	
	
	/*
	 * CARGAR ARCHIVO AL SERVIDOR
	 */
	public function uploadSingleRequiredFile($post,$entityName){
		// VALIDAR SI HA SIDO SETTEADO EL NOMBRE DEL ARCHIVO
		if(isset($_FILES['attachment_file']) && !empty($_FILES['attachment_file']['name'])){
			
			// PARÁMETROS DE ENTIDAD
			$config=$this->config['entityConfig'][$entityName];
			
			// EXTENSIÓN DE ARCHIVO
			$temporary=explode(".",$_FILES['attachment_file']['name']);
			$file_ext=strtolower(end($temporary));
			
			// NOMBRE TEMPORAL
			$tempName=date('Y-m-d-h-i-s').rand(1,100).".{$file_ext}";
			// VALIDAR CON SUFIJO
			$fileName=(isset($config['sufijoFile']))?"{$config['sufijoFile']}{$tempName}":$tempName;
			
			// SETTEAR NOMBRE DE ARCHIVO
			$folder="{$this->pathFile}{$config['folder']}";
			
			// VALIDAR SI EL DIRECTORIO EXISTE
			if(!is_dir($this->getPath($folder))){
				// CREAR DIRECTORIO DE DESTINO
				if(!mkdir($this->getPath($folder), 0777, true)) $this->getJSON("No existe el directorio de destino...");
			}
				
			// NOMBRE DEL ARCHIVO
			$file=$this->getPath("{$folder}{$this->separator}{$fileName}");
			
			// COPIAR ARCHIVO A DESTINO
			if(move_uploaded_file($_FILES['attachment_file']['tmp_name'],$file)){
				// AGREGAR ARCHIVO A LA LISTA
				return $this->setJSON($fileName,true);
			}else{
				// MENSAJE - ERROR EN LA CARGA DE ARCHIVO
				$this->getJSON(UPLOAD_ERROR."<br> {$fileName}");
			}
			
		}
		// RETORNAR MENSAJE - ARCHIVO VACIO
		$this->getJSON(FILE_EMPTY);
	}
	
	/*
	 * CARGAR ARCHIVO AL SERVIDOR
	 * data {field: nombre del campo de formulario que se envia desde html,
	 *       path: directorio para subir archivos,
	 *       prefix: nombre para archivo,
	 *       noTmpName: eliminar nombre real de archivo que se sube}
	 */
	public function uploadFileToFolder($post,$entityName,$data,$required=true){
	
	    // VALIDAR CAMPOS OBLIGATORIOS
	    if( !isset($data['field']) || !isset($data['path']) || !isset($data['prefix']) ) $this->getJSON($this->setJSON("No ha configurado completamente el servicio para cargar este fichero."));
	        
	    // PARAMETRIZACION DE VARIABLES
	    $postFileInput=$data['field'];
	    $folder=$data['path'];
	    $fileName=$data['prefix'];
	    
		// VALIDAR SI HA SIDO SETTEADO EL NOMBRE DEL ARCHIVO
		if(isset($_FILES[$postFileInput]) && !empty($_FILES[$postFileInput]['name'])){
			
			// PARÁMETROS DE ENTIDAD
			$config=$this->config['entityConfig'][$entityName];
			
			// EXTENSIÓN DE ARCHIVO
			$temporary=explode(".",$_FILES[$postFileInput]['name']); 
			$file_ext=strtolower(end($temporary));
			
			// QUITAR CARACTERES ESPECIALES Y DEFINIR NOMBRE DE FICHERO
			$tmpName=$this->cleanSpecialCharacters(str_replace(".{$file_ext}",'',$_FILES[$postFileInput]['name']));
			
			// SETTEAR NOMBRE DE ARCHIVO
			$folder="{$this->pathFile}{$config['folder']}{$this->separator}{$folder}";
			
			// VALIDAR SI EL DIRECTORIO EXISTE
			if(!is_dir($this->getPath($folder))){
				// CREAR DIRECTORIO DE DESTINO
				if(!mkdir($this->getPath($folder), 0777, true)) $this->getJSON("No existe el directorio de destino...");
			}
			
			// NOMBRE TEMPORAL
			// VALIDAR PARÁMETROS EXTRAS
			if(isset($data['noTmpName']) && $data['noTmpName']===true) $fileName="{$fileName}.{$file_ext}";
			if(isset($data['noTmpName']) && $data['noTmpName']===0) $fileName="{$fileName}-".date('ymd-his').".{$file_ext}";
			else $fileName="{$fileName}-{$tmpName}.{$file_ext}";
			
			// NOMBRE DEL ARCHIVO
			$file=$this->getPath("{$folder}{$this->separator}{$fileName}");
			
			// COPIAR ARCHIVO A DESTINO
			if(move_uploaded_file($_FILES[$postFileInput]['tmp_name'],$file)){
				// AGREGAR ARCHIVO A LA LISTA
				return $fileName;
			}else{
				// MENSAJE - ERROR EN LA CARGA DE ARCHIVO
				$this->getJSON(UPLOAD_ERROR."<br> {$fileName}");
			}
			
		}else{
			if($required) $this->getJSON($this->setJSON(FILE_EMPTY,false));
			else return null;
		}
		// RETORNAR MENSAJE - ARCHIVO VACIO
		$this->getJSON(FILE_EMPTY);
	}
	
	/*
	 * PARAMETRIZAR VARIABLES - CARGAR FICHEROS A CARPETAS
	 */
	public function setParamsToUploadToFolder($folder,$fileName,$postFileInput,$noTmpName=false){
	    return array(
	        'path'=>$folder,
	        'prefix'=>$fileName,
	        'field'=>$postFileInput,
	        
	        'noTmpName'=>$noTmpName
	    );
	}
	
}