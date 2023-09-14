<?php
$url = "http://fenixerp.info/api/v1/730bf4bdb6ec369f235a8416e20c9c07/cedula_ec/?nui=22222222";
 
$ch = curl_init(); // Iniciar cURL sesion
curl_setopt($ch, CURLOPT_URL, $url); // specifica la URL para conectar
curl_setopt($ch, CURLOPT_HEADER, false); // No retorna el header
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Transfiere la salida a la variable
$content = curl_exec($ch); // Visita y recorre la página remota
curl_close($ch); // cierra cURL sesion
 
echo htmlspecialchars($content);
?>