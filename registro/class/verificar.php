<?php
 session_start();
include 'conexion.php';
$cedula = $_POST['ced'];
$detalle = $_POST['detalle'];
$query="SELECT * FROM user WHERE ced_usu='$cedula'";
$codigo=null;
$result=mysqli_query($conexion,$query);
while($row=mysqli_fetch_array($result)){
    $codigo=$row['cod_usu'];}
if($codigo>0){
$insertar="INSERT INTO `registro`(`cod_usu`, `fec_reg`, `det_reg`) VALUES ('$codigo',NOW(),'$detalle')";
$query=mysqli_query($conexion,$insertar);
    echo '<script>
        alert("Registrado correctamente");
        window.history.go(-1);
        </script>';
}
else{
    echo '<script>
        alert("Cedula incorrecta");
        window.history.go(-1);
        </script>';}



mysqli_free_result($result);
mysqli_close($conexion);
    

?>