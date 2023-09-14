<?php namespace controller;


class zipController {
	
	/*
	 * DESCARGAR CARPETA DE ARCHIVOS
	 */
	public function downloadFolder($files,$folder,$nameZip){
		# FILE NAME
		$zipname=SRC_PATH."temp/{$nameZip}.zip";
		# create new zip opbject
		$zip=new \ZipArchive;
		// VALIDAR SI LA INSTANCIA HA SIDO CREADA
		if($zip->open($zipname,\ZIPARCHIVE::CREATE ) === TRUE){
			// RECORRER LISTADO DE ARCHIVOS
			foreach($files as $file) {
				// NOMBRE DE ARCHIVO
				$fileName="{$folder}/{$file}";
				// HEADERS
				$file_headers=@get_headers($fileName);
				// BUSCAR ARCHIVO
				if ($file_headers[0]=='HTTP/1.0 404 Not Found'){
					// ERROR - NO EXISTE EL ARCHIVO
					return array('estado'=>false,'mensaje'=>"Error al generar el archivo: {$file}");
				} else {
					// ADD FILE TO ZIP
					$zip->addFile($fileName,$file);
				}
			}
			// close zip
			$zip->close();
			// send the file to the browser as a download
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: public" );
			header( "Content-Description: File Transfer" );
			header( "Content-type: application/zip" );
			header( "Content-Disposition: attachment; filename={$nameZip}.zip" );
			header( "Content-Transfer-Encoding: binary" );
			// DESCARGAR ARCHIVO
			readfile($zipname);
			// ELIMINAR ARCHIVO TEMPORAL
			unlink($zipname);
			// RETORNAR CONSULTA
			return array('estado'=>true,'Archivo generado con éxtio!');
		} else {
			// ERROR AL GENERAR ARCHIVO
			return array('estado'=>false,'mensaje'=>'Error al generar el archivo!');
		}
	}

}