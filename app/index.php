<?php
/**
 * Description of index.php
 * Referencia a la aplicación app.php, la cual inicia la funcionalidad
 * en su totalidad del sistema, este método ayuda a mantener un directorio base a la carpeta raiz 
 * de un servidor (directorio)
 * @author Lalytto
**/
// VALIDAR EXISTENCIA DE .HTACCESS
if(isset($_GET['_url'])){
	// VARIABLE ROOT DEL SISTEMA
	define('ROOT', __DIR__ . DIRECTORY_SEPARATOR);
	// INSTANCIA DE API DE APLICACIÓN
	require_once 'app.php';
	// LOADER DE APLICACIÓN
	app\app::AutoLoader();
} 
// EN CASO DE NO EXISTIR .HTACCESS
echo json_encode(array('mensaje'=>'Acesso no autorizado','estado'=>false));