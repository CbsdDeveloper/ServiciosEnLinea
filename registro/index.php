<!DOCTYPE html>
<?php
 session_start();
?>
<html lang="es">
<head>
    <script>
    function numeros(e){
    key = e.keyCode || e.which;
    tecla = String.fromCharCode(key).toLowerCase();
    letras = " 0123456789";
    especiales = [8,37,39,46];
    tecla_especial = false;
    for(var i in especiales){
 if(key == especiales[i]){
     tecla_especial = true;
     break;
        } 
    }
    if(letras.indexOf(tecla)==-1 && !tecla_especial){
        return false;}
}
    </script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>PMIS - Registro de marcación</title>
  <!-- Bootstrap core CSS -->
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
  <!-- Custom fonts for this template -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,200i,300,300i,400,400i,600,600i,700,700i,900,900i" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Merriweather:300,300i,400,400i,700,700i,900,900i" rel="stylesheet">
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <!-- Custom styles for this template -->
  <link href="css/coming-soon.min.css" rel="stylesheet">
</head>
<script type="text/javascript">
        $(function(){

  $('.validanumericos').keypress(function(e) {
	if(isNaN(this.value + String.fromCharCode(e.charCode))) 
     return false;
  })
  .on("cut copy paste",function(e){
	e.preventDefault();
  });

});
    </script>
<body>
  <div class="overlay"></div>
  <video playsinline="playsinline" autoplay="autoplay" muted="muted" loop="loop">
    <source src="mp4/bg.mp4" type="video/mp4">
  </video>
  <div class="masthead">
    <div class="masthead-bg"></div>
    <div class="container h-100">
      <div class="row h-100">
        <div class="col-12 my-auto">
          <div class="masthead-content text-white py-5 py-md-0">
              <img class="center" src="./img/header.png" width="280"> <br> <br>
            <h1 class="mb-3">Bienvenidos</h1>
      	    <a href="admin/index.html" class="btn btn-info" target="_blank">Iniciar sesion</a> <br> <br>
            <p class="mb-5">Ingrese su numero de
              <strong>cédula:</strong>
                <form name="frmclave" method="post" action="class/verificar.php">
            <input name="ced" type="text" class="validanumericos form-control" placeholder="Cédula..." aria-label="Ingrese cédula..." aria-describedby="basic-addon" required>
              
            <select class="form-control" name="detalle" required>
                <option value="Inicio de jornada laboral">Inicio de jornada laboral</option>
                <option value="Inicio de almuerzo">Inicio de almuerzo</option>
                <option value="Fin de almuerzo">Fin de almuerzo</option>
                <option value="Fin de jornada laboral">Fin de jornada laboral</option>
            </select>    
              <div class="input-group-append">
                <button class="btn btn-success" type="submit">Registrar</button></div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="js/coming-soon.min.js"></script>

</body>
</html>
