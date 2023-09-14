<?php
session_start();
include '../conexion.php';
$nombre=null;
$consulta="SELECT * FROM user where ced_usu like '%".$_POST["texto"]."%' or nom_usu like '%".$_POST["texto"]."%'";
$resultado=mysqli_query($conexion,$consulta);
$fila=mysqli_num_rows($resultado);
if($fila>0){
echo "<table borde='2'>";
echo "<tr>";
echo "<th>CÉDULA</th>";
echo "<th>NOMBRE</th>";
echo "<th>CARGO</th>";
echo "<th>FECHA INGRESO</th>";
echo "<th>SUELDO</th>";
echo "<th>TITULO</th>";
echo "<th>GRADO DE SERVIDOR</th>";
echo "</tr>";

    while($row=mysqli_fetch_array($resultado)){
        $codigo=$row['cod_usu'];
 echo "<tr>";
 echo "<td>" . $row['ced_usu'] . "</td><td>" . $row['nom_usu'] . "</td><td>" . $row['car_usu'] . "</td><td>" . $row['fec_usu'] . "</td>
       <td>" . $row['sue_usu'] . "</td><td>" . $row['tit_usu'] . "</td><td>". $row['gra_usu'] . "</td>";
 echo "</tr>";
}
echo "</table>";} 
$_SESSION['codi'] = $codigo;

?>