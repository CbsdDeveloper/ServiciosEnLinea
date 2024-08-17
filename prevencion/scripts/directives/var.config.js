/*
 * 
 */
var sys_name='prevencion',
	host_name='/app/',
	pathIncApp='/app/src/views/',
	uriBreadcrumbs='/app/src/views/main/breadcrumbs.html';

var host_remote='http://localhost:81/api/';
// var host_remote='http://servicios.cbsd.gob.ec:8081/api/';

// CONFIGURACIÓN DE RUTA DE CONSULTAS
var Server;
var rootRequest=host_name+sys_name+'/',
	reportsURI=host_name+'report/',
	pathInc='/prevencion/views/include/',
	pathMenu='/prevencion/views/menu/',
	pathModal='/prevencion/views/modal/',
	pathMain='/prevencion/views/main/',
	pathModule='/prevencion/views/module/';
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
	usuarios:'admin',
	personas:'tthh',
	owner:'tthh',
	// EXTERNAL
	exportdata:''
};
/*
 * INSTANCIA DE MODALES PERSONALIZADOS
 */
var customModal={
	// GENERAL
	pdfviewer:'modalPDFViewer',
	gallery:pathIncApp+'modal/modalGallery',
	// CAPACITACIONES
	personas:pathModal+'Capacitaciones/modalParticipantes',
	coordinador:pathIncApp+'modal/prevention/modalCoordinador'
};
/*
 * PERSONALIZACIÓN DE DESTINO URI
 */
var customPath={};
/*
 * VARIABLES PARA CAMBIO DE NOMBRE DE ENTIDAD - CRUD BACKEND
 */
var customTb={
	// PERFIL
	contrasenia:'entidades',
	// PERMISOS OCASIONALES
	responsables:'responsablesOcasional',
	recursos:'recursosOcasional',
	// APROBACION DE PLANOS
	profesionales:'profesionalesVbp',
	proyecto_modificaciones:'vbp_modificaciones',
	proyecto_habitabilidad:'habitabilidad',
	proyecto_habitabilidad:'habitabilidad'
};
/*
 * PERSONALIZACIÓN PARA CONTROLADORES DE MENÚ INFERIOR (BOTTOM SHEET)
 */
var bsCtrl={
	default:'bsCtrl'
};
// TIPOS DE ENTIDADES
var entityType={
	fiscal:'U.E. Fiscal (AMIE)',
	CEDULA:'Persona Natural (C.C. Ecuador)'
};
// TIPOS DE IDENTIFICACION DE PERSONAS
var identificationType={
	CEDULA:'C.C Natural - Ecuador',
	OTRO:'Extranjero/Otro'
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