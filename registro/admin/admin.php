<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="assets/css/main.css" />
	<title></title>
	<?php require_once "scripts.php";  ?>
	
<script>function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'excel_data.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
}</script>
</head>
<body>

	<header id="header">
				<a class="logo" href="index.html"></a>
				</header>
		<!-- Nav -->
			
		<!-- Heading -->
			<div id="heading" >
				<h1>TALENTO HUMANO</h1>
			</div>

		<!-- Main -->
			


	<div class="container">
		<div class="row">
			<div class="col-sm-12">
                
				<div class="card text-left">
					<div class="card-header">
						Registro de marcaciones
					</div>
					<div class="card-body">
						<div id="tablaDatatable"></div>
					</div>
					<div class="card-footer text-muted">
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Modal -->
	
	<!-- Modal -->
	
    
</body>
    
</html>

<script type="text/javascript">
	$(document).ready(function(){
		$('#tablaDatatable').load('tabla.php');
	});
</script>

