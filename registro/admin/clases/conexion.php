<?php
class conectar{
		public function conexion(){
			$conexion=mysqli_connect('localhost','patronat','Pmis.admin2020@','patronat_justificacion');
			return $conexion;
		}
	}
$conexion=mysqli_connect("localhost","patronat","Pmis.admin2020@","patronat_justificacion");
?>