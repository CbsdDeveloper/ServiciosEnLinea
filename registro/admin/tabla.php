

<?php
session_start();
require_once "clases/conexion.php";
$obj= new conectar();
$conexion=$obj->conexion();
$sql="SELECT *
from registro";
$result=mysqli_query($conexion,$sql);
?>
<div>
						
						
						
					
	<table class="table table-hover table-condensed table-bordered" id="iddatatable">
		<thead style="background-color: #5882FA;color: white; font-weight: bold;">
			<tr>
				<td>Codigo</td>
				<td>Fecha</td>
                <td>Detalle</td>
                
			</tr>
		</thead>
		<tfoot style="background-color: #ccc;color: white; font-weight: bold;">
			<tr>
				<td>Codigo</td>
				<td>Fecha</td>
                <td>Detalle</td>
                
			</tr>
		</tfoot>
		<tbody >
			<?php 
			while ($mostrar=mysqli_fetch_row($result)) {
				?>
				<tr >
					<td><?php $user="SELECT nom_usu FROM user WHERE cod_usu='$mostrar[1]'";
                                               $query=mysqli_query($conexion,$user);
                                               while($res=mysqli_fetch_row($query)){
                                               echo $res[0];    
                                               }?></td>
					<td><?php echo $mostrar[2] ?></td>
                    <td><?php echo $mostrar[3] ?></td>
        
                    
				</tr>
				<?php 
			}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#iddatatable').DataTable();
	} );
</script>