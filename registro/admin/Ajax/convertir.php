<?php
session_start();
$hora = $_POST["time"];
$dia = $_POST["dia"];
$converdia=null;
$v_HorasPartes=null;
$minutosTotales=null;
$v_HorasPartes = explode(":",$hora);
if(isset($v_HorasPartes[0]) and isset($v_HorasPartes[1])){
$minutosTotales=($v_HorasPartes[0]*60) + $v_HorasPartes[1];}
$minutoslaborables=round(($minutosTotales*0.36)+$minutosTotales);
if($dia>0){
$converdia=round(($dia*8*60)*0.36+($dia*8*60));    }
$total=$minutoslaborables+$converdia;
echo "<table>";
echo "<tr>"    ;
echo "<th>Horas a minutos</th>";
echo "<th>Días a minutos</th>";
echo "<th>Total minutos</th>";
echo "</tr>";
echo "<th>".$minutoslaborables. "</th>";
echo "<th>".$converdia. "</th>";
echo "<th>" .$total."</th>";
$_SESSION['total']=$total;
?>