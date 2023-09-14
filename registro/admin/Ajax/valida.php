<?php
session_start();
function min_a_dhms($min) { 
  $d = abs(round($min / 480)); 
  $h = abs(round(($min-($d*480))/60));
  $m = abs(round(($min - ($d*480))-($h * 60))); 
  return "$d Días, $h horas y $m minutos";
}

include '../conexion.php';
$nombre=null;
$consulta="SELECT * FROM user where ced_usu like '%".$_POST["texto"]."%' or nom_usu like '%".$_POST["texto"]."%'";
$resultado=mysqli_query($conexion,$consulta);
$fila=mysqli_num_rows($resultado);
if($fila>0){
    while($row=mysqli_fetch_array($resultado)){
    $codigo = $row['cod_usu'];
    $nombre = $row['nom_usu'];
    $_SESSION['codi'] = $codigo;
}}else{echo "No existe funcionario";
       $codigo=null;
      $_SESSION['codi'] = null;
      }
echo $nombre; 


$consul="SELECT * from permiso WHERE cod_usu='$codigo'";
$result=mysqli_query($conexion,$consul);
$filas=mysqli_num_rows($result);
if($filas>0){
echo "<table borde='2'>";
echo "<tr>";
echo "<th>FECHA DE PERMISO</th>";
echo "<th>HORA SALIDA</th>";
echo "<th>HORA LLEGADA</th>";
echo "<th>FECHA DESDE</th>";
echo "<th>FECHA HASTA</th>";
echo "<th>DETALLE</th>";
echo "</tr>";
 while($row= mysqli_fetch_row($result)){
 echo "<tr>";
 echo "<td>" . $row[1] . "</td><td>" . $row[2] . "</td><td>" . $row[3] . "</td><td>" . $row[4] . "</td>
       <td>" . $row[5] . "</td><td>" . $row[6] . "</td>";
 echo "</tr>";
}
echo "</table>";
}

$vacacion="SELECT SUM(min_vac) as tot_vac FROM calculo where cod_usu='$codigo'";
$res=mysqli_query($conexion,$vacacion);
$fi=mysqli_num_rows($res);
echo "<h3>DÍAS DISPONIBLES</h3>";
echo "<table borde='2'>";
if($fi>0){
while($r=mysqli_fetch_array($res)){
$min=$r['tot_vac'];
echo "<tr>";
echo "<td>". min_a_dhms($min) ."</td>";
echo "</tr>";
}
echo "</table>";
}else{ echo null;}

?>  