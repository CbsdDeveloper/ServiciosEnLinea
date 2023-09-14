/*
 * 
 */
var sys_name='tthh',
	host_name='/app/',
	pathIncApp='/app/src/views/',
	uriBreadcrumbs='/app/src/views/main/breadcrumbs.html';

//var host_remote='http://localhost:81/api/';
var host_remote='http://servicios.cbsd.gob.ec:8081/api/';

// CONFIGURACIÓN DE RUTA DE CONSULTAS
var Server;
var rootRequest=host_name+sys_name+'/',
	reportsURI=host_name+'report/',
	pathMain='views/main/',
	pathModal='views/modal/',
	pathInc='views/include/',
	pathMenu='views/menu/',
	pathModule='views/module/',
	pathProfile='views/module/profile/',
	pathLeadership='views/module/leadership/',
	pathTthh='views/module/tthh/',
	pathAdministrative='views/module/administrative/',
	pathFinancial='views/module/financial/';
/*
 * PARAMETRIZACIÓN PARA MD-TABLE
 */
var smart_query='?smart_query';
var limitOptions=[5,10,15,25,50,75,100,150,200];
var labelFooter={page:'Página:',rowsPerPage:'Filas por página:',of:'de'};
/*
 * PERSONALIZACIÓN DE MODALES (TEMPLATES) - CONSTRALADORES (FUNCIONES) - ENTIDADES (BACKEND - CRUD)
 * ***************************************************
 */
/*
 * DIRECTORIO NATIVO PARA MODALES, ESTOS NO CAMBIARÁN DESDE CUALQUIER LADO DEL PROYECTO
 */
var nativePathEntity={
	personal:pathModal+'profile',
	filesperson:pathIncApp+'modal/',
	pdfviewer:pathIncApp+'modal/',
	exportdata:pathIncApp+'modal/',
	ordenescombustible:pathIncApp+'modal/',
	cuentasbancarias:pathIncApp+'modal/tthh/'
};
/*
 * INSTANCIA DE MODALES PERSONALIZADOS
 */
var customModal={
	filesperson:'modalFiles',
	pdfviewer:'modalPDFViewer'
};
/*
 * PERSONALIZACIÓN DE DESTINO URI
 */
var customPath={};
/*
 * VARIABLES PARA CAMBIO DE NOMBRE DE ENTIDAD - CRUD BACKEND
 */
var customTb={
	filesperson:'attachments',
	permisos:'personal_permisos',
	vacaciones:'personal_vacaciones'
};
/*
 * PERSONALIZACIÓN PARA CONTROLADORES DE MENÚ INFERIOR (BOTTOM SHEET)
 */
var bsCtrl={
	default:'bsCtrl'
};

/*
 * VALIDAR QUE SOLO SE PERMITAN CARACTERES NUMÉRICOS
 */
function isNumberKey(evt){
    var charCode = (evt.which) ? evt.which : event.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57)) return false;
    return true;
}
/*
 * VALIDAR QUE SOLO SE PERMITAN CARACTERES ALFANUMÉRICOS
 */
function alphaOnly(event) {
	var key = event.keyCode;
	return ((key == 241 || key == 209) || (key >= 65 && key <= 90) || (key >= 97 && key <= 122) || key == 8 || key == 32);
}