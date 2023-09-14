<?php
/**
 * Description of index
 * index.php hace referencia a una aplicación interna app/index.php la cual es la funcionalidad
 * en su totalidad del sistema, este método ayuda a mantener un directori base la carpeta reiz 
 * de un servidor (directorio) pero embeber una aplicación.
 * @author Lalytto
**/
include 'prevencion/index.php';
prevencion\app::index();