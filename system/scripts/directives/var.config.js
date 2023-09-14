/*
 * 
 */
var sys_name='system',
	host_name='/app/',
	pathIncApp='/app/src/views/',
	uriBreadcrumbs='/app/src/views/main/breadcrumbs.sys.html';

//var host_remote='http://localhost:81/api/';
var host_remote='http://servicios.cbsd.gob.ec:8081/api/';

// CONFIGURACIÓN DE RUTA DE CONSULTAS
var Server;
var rootRequest=host_name+sys_name+'/',
	reportsURI=host_name+'report/',
	pathMenu='views/menu/',
	pathInc='views/include/',
	pathModal='views/modal/',
	pathUser='views/user/',
	pathAdmin='views/admin/',
	pathSys='views/permits/',
	pathPrev='views/prevention/',
	pathTthh='views/tthh/',
	pathColl='views/collection/',
	pathFinc='views/financial/',
	pathPlan='views/planing/',
	pathLogi='views/logistics/',
	pathMngt='views/management/',
	pathSubj='views/subjefature/';
/*
 * PARAMETRIZACIÓN PARA MD-TABLE
 */
var smart_query='?smart_query';
var limitOptions=[5,10,15,25,50,75,100,150,200];
var labelFooter={page:'Página:',rowsPerPage:'Filas por página:',of:'de'};
/*
 * PERSONALIZACIÓN DE MODALES (TEMPLATES) - CONSTRALADORES (FUNCIONES) - ENTIDADES (BACKEND - CRUD)
 * ***************************************************
 * INSTANCIA DE CONTROLADORES PERSONALIZADOS
 */
var customCtrl={
	default:'submitCtrl'
};
/*
 * DIRECTORIO NATIVO PARA MODALES, ESTOS NO CAMBIARÁN DESDE CUALQUIER LADO DEL PROYECTO
 */
var nativePathEntity={
	files:'',
	gallery:'',
	pdfviewer:'',
	vehiculos:'',
//	usuarios:'admin',
	personas:'tthh',
	owner:'tthh',
	designer:'tthh',
	rpmci:'tthh',
	// PERMISOS
	duplicadosinput:'permits',
	// PREVENCION - GENERAL
	input:'prevention',
	asignacion:'prevention',
	anexos:'prevention',
	autoproteccion_anexos:'prevention',
	// PREVENCIÓN
	asignar:'prevention',
	tglpinspector:'prevention',
	planinspector:'prevention',
	vbpinspector:'prevention',
	transporteglpinspector:'prevention',
	planesemergenciainspector:'prevention',
	habitabilidadinspector:'prevention',
	modificacionesinspector:'prevention',
	factibilidadinspector:'prevention',
	definitivoinspector:'prevention',
	barridos:'prevention',
	inspecciones:'prevention',
	planesemergencia:'prevention',
	generarordenescobro:'collection',
	ordenescobro:'collection',
	// EXTERNAL
	exportdata:''
};
/*
 * INSTANCIA DE MODALES PERSONALIZADOS
 */
var customModal={
	pdfviewer:'modalPDFViewer',
	owner:'modalPersonas',
	designer:'modalPersonas',
	rpmci:'modalPersonas',
	vbpstep1:'modalVBPIngreso',
	tglpinspector:'modalAsignar',
	planinspector:'modalAsignar',
	vbpinspector:'modalAsignacion',
	transporteglpinspector:'modalAsignacion',
	planesemergenciainspector:'modalAsignacion',
	habitabilidadinspector:'modalAsignacion',
	modificacionesinspector:'modalAsignacion',
	factibilidadinspector:'modalAsignacion',
	definitivoinspector:'modalAsignacion'
};
/*
 * PERSONALIZACIÓN DE DESTINO URI
 */
var customPath={};
/*
 * VARIABLES PARA CAMBIO DE NOMBRE DE ENTIDAD - CRUD BACKEND
 */
var customTb={
	perfil:'usuarios',
	tramites:'modulos',
	restore:'usuarios',
	uploadciiu:'uploadCiiu',
	asignar:"inspecciones",
	vbpstep1:'vbp',
	revisiones:'vbp',
	// ADMINISTRATIVO
	ordenescombustiblelista:'ordenescombustible',
	ordenestrabajolista:'ordenestrabajo',
	// PERMISOS
	duplicadosinput:'duplicados',
	// TALENTO HUMANO
	personal_credenciales:'personal',
	// PERMISOS
	entidades_credenciales:'entidades',
	suspender_locales:'locales',
	// PREVENCIÓN
	tglpinspector:'transporteglp',
	planinspector:'planesemergencia',
	vbpinspector:'vbp',
	transporteglpinspector:'transporteglp',
	planesemergenciainspector:'planesemergencia',
	habitabilidadinspector:'habitabilidad',
	modificacionesinspector:'vbp_modificaciones',
	factibilidadinspector:'factibilidadglp',
	definitivoinspector:'definitivoglp',
	profesionales:'profesionalesVbp',
	planesemergenciainput:'planesemergencia',
	traininginput:'training',
	standsinput:'stands',
	ocasionalesinput:'ocasionales',
	// RECAUDACIÓN
	generarordenescobro:'ordenescobro'
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