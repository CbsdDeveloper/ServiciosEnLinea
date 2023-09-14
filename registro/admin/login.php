<?php
 session_start();
include '../class/conexion.php';
$usuario = $_POST['user'];
$contra=$_POST['pass'];

//Variable para llenado SESSION
//$_SESSION['cedula'] = $cedula;
//$query="SELECT * FROM user WHERE ced_usu='$cedula'";
//$result=mysqli_query($conexion,$query);
//$consulta="SELECT * FROM user where ced_usu='$cedula'";
//$resultado=mysqli_query($conexion,$consulta);

$consulta="SELECT * FROM login where use_log='$usuario' and pas_log=SHA('$contra')";
$resultado=mysqli_query($conexion,$consulta);
$filas=mysqli_num_rows($resultado);
if($filas>0){
    while($row=mysqli_fetch_array($resultado)){
       $nombre = $row['nom_log'];
header("location:admin.php");
}}
else{
    echo '<script>
        alert("Usuario o Clave Incorrecta, Intente de nuevo");
        window.history.go(-1);
        </script>';
}
$_SESSION['nombre'] = $nombre;

mysqli_free_result($resultado);
mysqli_close($conexion);
    

?>