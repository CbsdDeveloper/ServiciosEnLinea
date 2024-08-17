/**
 * 
 */
app
/* 
 * Modal: modalPDFViewer
 * Función: 
 */
.controller('pdfViewerExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> pdfViewerExtendsCtrl -> Form to 1 reference:: ',$scope.frm);
	$scope.getIframeSrc=function(src){
		if(myResource.testNull($scope.frmParent.external)) return '/app/src/' + myResource.sce.trustAsResourceUrl(src);
		else return '/app/src/' + src;
	};
}])
/* 
 * Modal: modalFiles
 * Función: OBTENER LOS DATOS DE LA ENTIDAD RELACIONADA
 */
.controller('filesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// CONSULTAR DATOS DE ENTIDAD RELACIONADA
	myResource.sendData('resources/REQUEST').save({entity:'entityById',entityName:$scope.frmParent.fk_table,entityId:$scope.frmParent.fk_id},function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
}])
/* 
 * Modal: modalRecursos
 * Función: 
 */
.controller('resourcesListExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> resourcesListExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.options={mode:'tree'};
}])
/*
* Modal: modalParams
* Función: 
*/
.controller('paramsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> paramsExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.editorLoaded=function(jsonEditor){jsonEditor.expandAll();};
	$scope.options={mode:'tree'};
}])
/*
 * *******************************************
 * RECAUDACIÓN
 * *******************************************
 */
 /* 
  * Modal: modalOrdencobro
  * Función: CONSULTAR ENTIDAD RELACIONADA
  */
 .controller('ordersExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	console.log('Extends request -> ordersExtendsCtrl -> Form to reference:: ',$scope.frm);
 	var request={entity:$scope.frmParent.entity,entityId:$scope.frmParent.entityId};
 	myResource.sendData('ordenescobro/REQUEST').save(request,function(json){
 		if(json.estado===true) $scope.frmParent.order=json.data;
 		myResource.myDialog.showNotify(json);
 	},myResource.setError).$promise;
 	// REDIRECCIONAR A IMPRESIÓN DE ORDEN DE COBRO
 	$scope.$watch('jsonResource',function(newValue,oldValue){
 		if(myResource.testNull($scope.jsonResource.orden_id)) window.open(reportsURI+'ordenescobro/?id='+$scope.jsonResource.orden_id,'_blank');
 	},true);
 }])
 /* 
  * Modal: modalPoa_proyectos
  * Función: SETTEAR FORMULARIO
  */
 .controller('poaprojectsExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	console.log('Extends request -> poaprojectsExtendsCtrl -> Form to reference:: ',$scope.frm);
 	

	// CALCULO DE PORCENTAJES
	$scope.getPercentages=function(){
		$scope.frmParent.proyecto_porcentaje=parseInt($scope.frmParent.proyecto_trimestre_i_porcentaje)+
											 parseInt($scope.frmParent.proyecto_trimestre_ii_porcentaje)+
											 parseInt($scope.frmParent.proyecto_trimestre_iii_porcentaje)+
											 parseInt($scope.frmParent.proyecto_trimestre_iv_porcentaje);
	};
	// VALIDAR SI NUEVA O EDICION
	if(!$scope.toogleEditMode){
		$scope.frmParent.proyecto_trimestre_i_porcentaje=25;
		$scope.frmParent.proyecto_trimestre_ii_porcentaje=25;
		$scope.frmParent.proyecto_trimestre_iii_porcentaje=25;
		$scope.frmParent.proyecto_trimestre_iv_porcentaje=25;
	}
	
	// GENERAR CALCULO DE PROGRAMACION TRIMESTRAL
	$scope.getPercentages();
 	
 }])

/*
 * *******************************************
 * RESOURCES
 * *******************************************
 */
 /*
  * Modal: modalFormulariosevaluaciones_preguntas
  * Función: SECCIONES DE FORMULARIOS
  */
 .controller('surveysRatingSystemListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('resources/surveys/ratingsystem/list').json(function(json){$scope.jsonList=json.data;}).$promise;
 }])
/*
 * Modal: modalFormulariosevaluaciones_preguntas
 * Función: SISTEMAS DE CALIFICACION DE EVALUACIONES
 */
.controller('ratingSystemPsychosocialListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('resources/sos/psychosocial/forms/ratingsystem/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
 
 
 /*
  * *******************************************
  * TALENTO HUMANO
  * *******************************************
  */
 /* 
  * Modal: modalReglamentos
  * Función: LISTADO DE TÍTULOS Y CAPÍTULOS 
  */
 .controller('regulationsExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> regulationsExtendsCtrl -> Form to reference:: ',frm);
 	// EXTENSIONES
 	$scope.titlesList={};
 	$scope.chaptersList={};
 	// LISTADO DE TÍTULOS
 	myResource.sendData('reglamentos/REQUEST').save({type:'regulations'},function(json){
 		$scope.titlesList=json.data.titles;
 	 	$scope.chaptersList=json.data.chapters;
	},myResource.setError).$promise;
 }])
 /* 
  * Modal: modalBaselegal_losep
  * Función: LISTADO DE ARTÍCULOS DE LA LOSEP Y REGLAMENTO
  */
 .controller('typeactionsExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> typeactionsExtendsCtrl -> Form to reference:: ',frm);
 	// EXTENSIONES
 	$scope.jsonList={};
 	$scope.frmParent.selected={};
 	// LISTADO DE TÍTULOS
 	myResource.sendData('reglamentos/REQUEST').save({actionId:$scope.frmParent.tipoaccion_id},function(json){
 		$scope.jsonList=json.data.list;
 		$scope.frmParent.selected=json.data.selected;
	},myResource.setError).$promise;
 	// REGLAMENTOS PARA TIPOS DE ACCIONES
	myResource.requestData('tthh/regulations/actionType/list').save({actionTypeId:$scope.frmParent.tipoaccion_id},function(json){
		$scope.regulationsList=json.data;
	}).$promise;
}])
/* 
 * Modal: modalJornadas_trabajo
 * Función: LISTADO DE HORARIOS
 */
.controller('scheduleWorkdaysListCtrl',['$scope','myResource',function($scope,myResource){
	var frm='frmParent';
	console.log('Extends request -> scheduleWorkdaysListCtrl -> Form to reference:: '+ frm);
	// LISTADO DE PERSONAL
	$scope.frmParent.schedules=[];
	myResource.requestData('tthh/workdays/detail').save({entityId:$scope.frmParent.jornada_id},function(json){
		// TRANSFORMAR ARRAY SCHEDULES
		angular.forEach(json.data.schedules,function(val,key){ json.data.schedules[key].horario_dias_semana=angular.fromJson(val.horario_dias_semana); });
		// INSERTAR DATOS DESDE BACKEND A MODELO DE FRONTEND
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
}])
/* 
 * Modal: modalTiposcontratos
 * Función: LISTADO DE TIPOS DE ANTICIPOS
 */
.controller('typeAdvancesListCtrl',['$scope','myResource',function($scope,myResource){
	var frm='frmParent';
	console.log('Extends request -> typeAdvancesListCtrl -> Form to reference:: '+ frm);
	// LISTADO DE PERSONAL
	myResource.requestData('tthh/typeadvances/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * LISTADO DE FUNCIONES DE PERSONAL
 */
.controller('staffFunctionsListCtrl',['$scope','myResource',function($scope,myResource){
	var frm='frmParent';
	console.log('Extends request -> staffFunctionsListCtrl -> Form to reference:: '+ frm);
	// LISTADO DE PERSONAL
	myResource.requestData('tthh/staff/functions/list').json(function(json){$scope.FKList=json.data;}).$promise;
}])
/* 
 * LISTADO DE FUNCIONES DE PPERSONAL
 */
.controller('ppersonalFunctionsListCtrl',['$scope','myResource',function($scope,myResource){
	var frm='frmParent';
	console.log('Extends request -> staffFunctionsListCtrl -> Form to reference:: '+ frm);
	// LISTADO DE PERSONAL
	myResource.requestData('tthh/staff/ppersonal/functions/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Función: LISTADO DE PERSONAL EN FUNCIONES
 */
.controller('staffActiveListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/staff/active/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalOrdenesmantenimiento
 * Función: LISTADO DE PERSONAL DE LA INSTITUCIÓN
 * PERFILES: TODO EL PERSONAL
 */
.controller('staffListCtrl',['$scope','myResource',function($scope,myResource){
	var frm='frmParent';
	console.log('Extends request -> staffListCtrl -> Form to reference:: '+ frm);
	// LISTADO DE PERSONAL
	myResource.requestData('tthh/staffList').json(function(json){$scope.FKList=json.data;}).$promise;
}])
/* 
 * Modal: modalInformesdemantenimiento
 * Función: LISTADO DE PERSONAL DE LA INSTITUCIÓN - SERVICIOS GENERALES
 * PERFILES: TODO EL PERSONAL - SERVICIOS GENERALES
 */
.controller('staffToGServicesCtrl',['$scope','myResource',function($scope,myResource){
	// LISTADO DE PERSONAL
	myResource.requestData('tthh/staff/ppersonal/functions/gservices/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalAccionespersonal
 * Función: 
 */
.controller('staffactionsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> staffactionsExtendsCtrl -> Form to reference:: ',frm);
	// DEFINICION DE MODELOS
	$scope.frmParent.selected={};
	// VALIDAR SI NUEVA O EDICION
	if($scope.toogleEditMode){
		$scope.frmParent.situacion_propuesta_opcion='MISMA';
	}
	
	// ACTUALIZACION DE RESPONSABLE DE ACCION DE PERSONAL
	$scope.setResponsable=function(responsable){
		$scope.frmParent.fk_director_id=$scope.frmParent.fk_director.ppersonal_id;
		$scope.frmParent.accion_relacion=$scope.frmParent.fk_director.entidad_nombre;
		$scope.frmParent.accion_relacion_delegacion=$scope.frmParent.fk_director.entidad_id;
	};
	
	// REGLAMENTOS PARA TIPOS DE ACCIONES
	myResource.requestData('tthh/regulationsByActionType').save({actionTypeId:$scope.frmParent.fk_tipoaccion_id},function(json){
		$scope.regulationsList=json.data.regulations;
		$scope.frmParent.selected=json.data.selected;
	}).$promise;
	
	// REGLAMENTOS PARA ACCIONES
	if(myResource.testNull($scope.frmParent.accion_id)){
		myResource.requestData('tthh/regulationsByAction').save({actionId:$scope.frmParent.accion_id},function(json){
			$scope.frmParent.selected=json.data;
		}).$promise;
	}
	
	// REDIRECCIONAR A IMPRESIÓN de acción de personal
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.accion_id)) window.open(reportsURI+'accionespersonal/?withDetail&id='+$scope.jsonResource.accion_id,'_blank');
	},true);
}])
/* 
 * Modal: modalDelegaciones
 * Función: 
 */
.controller('delegationsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> delegationsExtendsCtrl -> Form to reference:: ',frm);
	// REDIRECCIONAR A IMPRESIÓN de acción de personal
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.delegacion_id)) window.open(reportsURI+'delegaciones/?withDetail&id='+$scope.jsonResource.delegacion_id,'_blank');
	},true);
}])
/* 
 * Modal: modalAccionesdepersonal
 * Función: AUTOCOMPLETAR EL FORMULARIO
 */
.controller('actionsofstaffExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> actionsofstaffExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A3';
}])
/* 
 * Modal: modalAnticipos
 * Función: AUTOCOMPLETAR EL FORMULARIO
 */
.controller('advancedExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> advancedExtendsCtrl -> Form to reference:: ',$scope.frm);
	// CONSULTAR REGISTRO DE ANTICIPO
	myResource.sendData('anticipos/REQUEST').save({advanceId:$scope.frmParent.advanceId},function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
	// CALCULAR LAS CUOTAS MENSUALES
	$scope.getFee=function(){
		$scope.frmParent.model.anticipo_cuotas=parseFloat($scope.frmParent.model.anticipo_monto/$scope.frmParent.model.anticipo_meses).toFixed(2);
	};
}])
/* 
 * Modal: modalConsultasmedicas
 * Función: ACTUALIZAR CITA SUBSECUENTE Y GENERAR NUEVA CITA
 */
.controller('medicalConsultationsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> medicalConsultationsExtendsCtrl -> Form to reference:: ',$scope.frm);
	// GUARDAR FORMULARIO
	$scope.submitCustomForm=function(){
		myResource.sendData('consultasmedicas/PUT').update($scope.frmParent,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				myResource.myDialog.closeDialog();
				if($scope.frmParent.consulta_cita_nuevaconsulta=='SI') myResource.state.go('tthh.newMedicalConsultation',{historyId:$scope.frmParent.historia_id});
			}
		},myResource.setError);
	};
}])
/* 
 * Modal: modalCertificadosmedicos
 * Función: CERTIFICADOS MEDICOS
 */
.controller('medicalcertificatesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// VALIDAR SI ES APERTURA O CIERRE
	if($scope.toogleEditMode){
		$scope.frmParent.certificado_fecha_emision=$scope.frmParent.fecha_emision;
		$scope.frmParent.certificado_descripcion=$scope.frmParent.descripcion;
		$scope.frmParent.certificado_indicaciones=$scope.frmParent.indicaciones;
		$scope.frmParent.certificado_reposomedico=$scope.frmParent.reposo;
		$scope.frmParent.certificado_reposomedico_desde=$scope.frmParent.reposo_desde;
		$scope.frmParent.certificado_reposomedico_hasta=$scope.frmParent.reposo_hasta;
	}
}])
/* 
 * Modal: modalPersonal_vacaciones
 * Función: AUTOCOMPLETAR EL FORMULARIO
 */
.controller('vacationsofstaffExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> vacationsofstaffExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A2';
}])
/* 
 * Modal: modalPersonal
 * Función: LISTADO DE ESTACIONES PARA PERSONAL
 */
.controller('stationsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/stations').json(function(json){$scope.list=json.data;}).$promise;
}])
/* 
 * Modal: modalPersonal
 * Función: LISTADO DE BODEGAS
 */
.controller('wineriesListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/wineries').json(function(json){$scope.list=json.data;}).$promise;
}])
/* 
 * Modal: modalPartidas
 * Función: AUTOCOMPLETAR EL FORMULARIO
 */
.controller('partidasExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> partidasExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A3';
}])
/* 
 * Modal: modalPuestos_actividades
 * Función: LISTA DE ACTIVIDADES DE CADA PUESTO
 */
.controller('functionsJobsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> functionsJobsExtendsCtrl -> Form to reference:: ',frm);
	// VARIABLE PARA LA SELECCIÓN DE PUESTOS [SUBORDINADOS]
	$scope.frmParent.selected={};
	// CONSULTAR DATOS
	myResource.sendData('puestos/REQUEST').save($scope.frmParent,function(json){
		$scope.jsonList=json.data.list;
		$scope.frmParent.selected=json.data.selected;
	}).$promise;
}])

/* 
 * Modal: modalFormacionAcademica
 * Función: AUTOCOMPLETAR EL FORMULARIO
 */
.controller('transparenciaExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> transparenciaExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A3';
}])
/* 
 * Modal: frmPersonas
 * Función: PARSE CAMPOS TIPO FECHAS 
 */
.controller('personsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> personsExtendsCtrl -> Form to reference:: ',frm);
}])
/* 
 * Modal: frmSubordinados
 * Función: METODO PARA LISTADO DE SUBORDINADOS DE ACUERDO AL PUESTO
 */
.controller('subordinatesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> subordinatesExtendsCtrl -> Form to reference:: ',frm);
	// VARIABLE PARA LA SELECCIÓN DE PUESTOS [SUBORDINADOS]
	$scope.frmParent.selected={};
	// CONSULTAR LISTADO DE PUESTOS
	var dataRequest=$scope.frmParent;
	dataRequest.type='subordinates';
	myResource.sendData('puestos/REQUEST').save(dataRequest,function(json){
		$scope.jsonList=json.data;
		$scope.frmParent.selected=json.data.selected;
	}).$promise;
}])
/* 
 * Modal: modal
 * Función:  
 */
.controller('responsiblesByLeadershipsListCtrl',['$scope','myResource',function($scope,myResource){
	// LISTDO DE RESPONSABLES
	myResource.requestData('tthh/responsiblesByLeaderships').json(function(json){
		$scope.FKListLeaderships=json.data;
	}).$promise;
}])
/* 
 * Modal: modalPersonal
 * Función: LISTADO DE JORNADAS DE TRABAJO 
 */
.controller('workdaysListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/workdays/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalPuestos
 * Función: LISTADO DE JEFATURAS - DIRECCIONES 
 */
.controller('leadershipsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/institution/leaderships/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalPersonal
 * Función: LISTADO DE PUESTOS LABORALES PARA PERSONAL
 */
.controller('jobsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/jobs/leaderships').json(function(json){$scope.jobsList=json.data;}).$promise;
}])
/* 
 * Modal: modalDistribucionpersonal
 * Función: LISTA DE PELOTONES ACTIVOS
 */
.controller('personalDistributionExtendsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.frmParent.selected=[];
	myResource.sendData('pelotones/REQUEST').save($scope.frmParent,function(json){
		$scope.frmParent.edit=json.data.edit;
		$scope.frmParent.selected=json.data.selected;
		$scope.frmParent.distribution=json.data.distribution;
		$scope.list=json.data.list;
	}).$promise;
}])
/* 
 * Modal: modalPeloton_personal
 * Función: LISTA DE PERSONAL PARA PELOTONES - POR RANGOS
 */
.controller('platoonPersonalExtendsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.frmParent.model={};
	myResource.sendData('pelotones/REQUEST').save($scope.frmParent,function(json){
		if(json.estado===true){
			// MODELO DE DISTRIBUTIVO
			$scope.frmParent.model=json.data.model;
			// LISTADOS AUXILIARES
			$scope.staffList=json.data.personal;
			$scope.stationsList=json.data.stations;
			$scope.platoonsList=json.data.platoons;
			$scope.functionsList=json.data.functions;
		}else{
			myResource.myDialog.closeDialog();
			myResource.myDialog.swalAlert(json);
		}
	}).$promise;
}])
/* 
 * Modal: modalConductores
 * Función: CONSULTAS AUXILIARES DE CONDUCTORES
 */
.controller('driversLicensesListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.sendData('resources/REQUEST').save({type:'driverslicenses'},function(json){$scope.list=json.data;}).$promise;
}])
/* 
 * Modal: modalEvaluacionesdesempenio
 * Función: CALCULAR EL TOTAL DE PORCENTAJES
 */
.controller('performanceExtendsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.getScore=function(){
		$scope.frmParent.evaluacion_total=(parseInt($scope.frmParent.actividades_porcentaje) + 
										   parseInt($scope.frmParent.conocimientos_porcentaje) + 
										   parseInt($scope.frmParent.competencias_tecnicas_porcentaje) + 
										   parseInt($scope.frmParent.competencias_universales_porcentaje) + 
										   parseInt($scope.frmParent.trabajo_equipo_porcentaje));
	};
}])
/*
 * Modal: modalFichacascada
 * Función: LISTADO 
 */
.controller('filtersWaterfallListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('tthh/filtersWaterfall').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalFichacascada
 * Función: CONSULTAS PELOTONES DE ESTACIONES
 */
.controller('waterfallExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// LISTADO DE PELOTONES
	myResource.sendData('resources/REQUEST').save({entity:'platoons'},function(json){$scope.platoonsList=json.data;}).$promise;
	// CONSULTAR LISTA D ETANQUES 
	var filter={param:'filters'};
	if(myResource.testNull($scope.frmParent.cascada_id)) filter.param=$scope.frmParent.cascada_id;
	myResource.sendData('fichacascada/REQUEST').save(filter,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
}])
/* 
 * Modal: modalFormulario_rescatevertical_danios
 * Función: LISTADO PREGUNTAS PARA FROMULARIOS DE REPORTE DE DAÑOS DE EQUIPOS DE RESCATE VERTICAL 
 */
.controller('questionsDamageFormsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> questionsDamageFormsExtendsCtrl -> Form to reference:: ',frm);
	
	// EXTENSIONES
	$scope.jsonList={};
	$scope.frmParent.selected=[];
	
	// LISTADO DE PREGUNTAS PARA FROMULARIO DE REGISTRO DE DAÑOS
	myResource.requestData('tthh/sos/vrescue/damageforms/questions').save({formId:$scope.frmParent.formulario_id},function(json){
		$scope.questionsList=json.data.questions;
		$scope.frmParent.selected=json.data.selected;
	}).$promise;
	
}])
/*
 * Modal: modalEquiposrescatevertical
 * Función: LISTADO 
 */
.controller('vrescueCategoriesEquipmentsListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('tthh/sos/vrescue/categories/equipments/list/active').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalEquiposrescatevertical
 * Función: LISTADO 
 */
.controller('vrescueFormsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('resources/tthh/sos/vrescue/forms/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])

/*
 * Modal: 
 * Función: LISTADO 
 */
.controller('platoonsListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('tthh/stations/platoons').json(function(json){$scope.jsonList=json.data;}).$promise;
}])

/*
 * Modal: modalFormulariosriesgopsicosocial_preguntas
 * Función: LISTADO DE PREGUNTAS PARA FORMULARIOS DE EVALUACION DE RIESGO PSICOSOCIAL 
 */
.controller('formPsychosocialListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('tthh/sos/psychosocial/forms/list/active').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalFormulariosriesgopsicosocial_preguntas
 * Función: LISTADO DE PREGUNTAS PARA FORMULARIOS DE EVALUACION DE RIESGO PSICOSOCIAL 
 */
.controller('questionsFormPsychosocialListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('resources/sos/psychosocial/forms/questions/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalFormulariosriesgopsicosocial_preguntas
 * Función: SISTEMA DE CALIFICACION PARA FORMULARIOS DE EVALUACION DE RIESGO PSICOSOCIAL
 */
.controller('ratingSystemPsychosocialListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('resources/sos/psychosocial/forms/ratingsystem/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalEvaluacionesriesgopsicosocial_preguntas
 * Función: LISTADO DE PREGUNTAS DE UN FORMULARIO
 */
.controller('evaluationQuestionsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/sos/psychosocial/evaluation/questions/list').save({evaluationId:$scope.frmParent.evaluationId},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalEvaluacionesriesgopsicosocial_preguntas
 * Función: GUARDAR FORMULARIO
 */
.controller('evaluationQuestionsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// GENERAR MODELO PARA INGRESAR LISTA DE PREGUNTAS SELECCIONADAS
	$scope.frmParent.selected=[];
	// CONSULTAR PREGUNTAS SELECCIONADAS DEL FORMULARIO
	myResource.requestData('tthh/sos/psychosocial/evaluation/questions/selected').save({evaluationId:$scope.frmParent.evaluationId},function(json){$scope.frmParent.selected=json.data;}).$promise;
}])

/*
 * *******************************************
 * ADMINISTRACIÓN DE USUARIOS
 * *******************************************
 */
/* 
 * Modal: modalSubmodulos
 * Función: LISTA DE MODULOS DEL SISTEMA 
 */
.controller('progrmsPoaListExtendsCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('planing/poa/programs/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])

/*
 * *******************************************
 * ADMINISTRACIÓN DE USUARIOS
 * *******************************************
 */
/* 
 * Modal: modalSubmodulos
 * Función: LISTA DE MODULOS DEL SISTEMA 
 */
.controller('modulesListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.getData('modulos').json(function(json){$scope.list=json;}).$promise;
}])
/* 
 * Modal: modalRoles + modalRutas
 * Función: LISTA DE SUBMODULOS DEL SISTEMA 
 */
.controller('submodulesListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('roles/REQUEST').save({type:'submodules'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalUsuarios
 * Función: LISTA DE PERFILES DE USARIOS 
 */
.controller('profilesListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('resources/REQUEST').save({type:'profiles'},function(json){$scope.jsonList=json.data;}).$promise;
}])

/*
 * *******************************************
 * ADMINISTRACIÓN DEL SISTEMA
 * *******************************************
 */
/* 
 * Modal: modalTablas
 * Función: LISTADO DE TABLAS DEL SISTEMA
 */
.controller('tablesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.frmParent.model={};
	// ******************************** DATOS DE RELACIÓN
	myResource.sendData('tablas/REQUEST').save($scope.frmParent,function(json){
		$scope.info=json.data;
		$scope.frmParent.modules=json.data.modules;
		$scope.frmParent.resources=json.data.resources;
		$scope.frmParent.model=json.data.model;
	}).$promise;
}])
/* 
 * Modal: modalRequisitos
 * Función: LISTADO DE RECURSOS QUE SEAN DE TIPO REPORTE
 */
.controller('requirementsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// ******************************** DATOS DE RELACIÓN
	myResource.sendData('reportes/REQUEST').save({reportId:$scope.frmParent.reporte_id},function(json){
		$scope.frmParent.resources=json.data.resources;
		$scope.frmParent.model=json.data.model;
	}).$promise;
}])
/* 
 * Modal: modalRecursos_entidades
 * Función: LISTADO DE RECURSOS
 */
.controller('resourcesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// LISTA DE RECURSOS
	$scope.frmParent.resources={};
	// ******************************** DATOS DE RELACIÓN
	myResource.sendData('recursos_entidades/REQUEST').save({types:''},function(json){
		$scope.frmParent.resources=json.data;
	}).$promise;
}])
/* 
 * Modal: modalVehiculos - modalTransporteglp
 * Función: LISTADO DE MARCAS 
 */
.controller('brandsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('resources/REQUEST').save({type:'brands'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalX
 * Función: LISTADO DE PAISES 
 */
.controller('countriesListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('resources/REQUEST').save({type:'countries'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalX
 * Función: LISTADO DE PROVINCIAS 
 */
.controller('statesListCtrl',['$scope','myResource',function($scope,myResource){
	$scope.refreshStates=function(){
		myResource.sendData('resources/REQUEST').save({type:'states',country:$scope.frmParent.fk_country_id},function(json){$scope.statesList=json.data;}).$promise;
	};	$scope.refreshStates();
}])
/* 
 * Modal: modalX
 * Función: LISTADO DE CANTONES 
 */
.controller('townsListCtrl',['$scope','myResource',function($scope,myResource){
	$scope.refreshTowns=function(){
		if(myResource.testNull($scope.frmParent.fk_state_id))
			myResource.sendData('resources/REQUEST').save({type:'towns',state:$scope.frmParent.fk_state_id},function(json){$scope.townsList=json.data;}).$promise;
	};	$scope.refreshTowns();
}])
/* 
 * Modal: modalX
 * Función: LISTADO DE PARROQUIAS 
 */
.controller('parishesListCtrl',['$scope','myResource',function($scope,myResource){
	$scope.refreshParishes=function(){
		if(myResource.testNull($scope.frmParent.fk_town_id))
			myResource.sendData('resources/REQUEST').save({type:'parishes',town:$scope.frmParent.fk_town_id},function(json){$scope.parishesList=json.data;}).$promise;
	};	$scope.refreshParishes();
}])

/*
 * **************************************************
 * RECAUDACIÓN
 * **************************************************
 */
/* 
 * Modal: modalEspecies  
 * Función: Registro de venta de especies valoradas
 */
.controller('speciesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> speciesExtendsCtrl -> Form to reference:: ',frm);
	// VALIDAR SI ES APERTURA O CIERRE
	if(!$scope.toogleEditMode){
		$scope.frmParent.especie_costo=3;
		$scope.frmParent.fecha_registro=$scope.today;
		$scope.frmParent.type='new';
	}
	
	// SELECCIÓN DE JEFES INMEDIATOS
	myResource.sendData('especies/REQUEST').save($scope.frmParent,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
	
	// ACTUALIZAR LOS DATOS
	$scope.change=function(){
		if(!$scope.toogleEditMode){
			$scope.frmParent.especie_fin=$scope.frmParent.especie_inicio;
			$scope.frmParent.especie_total=0;
			$scope.frmParent.especie_total=0;
		}else{
			$scope.frmParent.especie_total=parseFloat($scope.frmParent.especie_fin)-parseFloat($scope.frmParent.especie_inicio)+parseFloat(1);
		}
		$scope.frmParent.especie_costo_total=parseFloat($scope.frmParent.especie_total)*parseFloat($scope.frmParent.especie_costo);
	};	$scope.change();
}])
/* 
 * Modal: modalCierres
 * Función: Registro de cajas
 */
.controller('archingExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> archingExtendsCtrl -> Form to reference:: ',frm);
	// CONSULTAR MODELO Y SERIE DE CIERRE
	/*
	myResource.sendData('cierres/REQUEST').save($scope.frmParent,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
	*/
	// REDIRECCIONAR A IMPRESIÓN DE ORDEN DE COBRO
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.cierre_id)) window.open(reportsURI+'cierre_ordenes/?withDetail&id='+$scope.jsonResource.cierre_id,'_blank');
	},true);
}])
 /* 
  * Modal: modalDocumentos_procedimientoscontratacion
  * Función: LISTADO DE DOCUMENTOS (REQUISITOS PARA CADA PROCEDIMIENTO DE CONTRATACION)
  */
 .controller('requirementsContractingProceduresExtndsCtrl',['$scope','myResource',function($scope,myResource){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> requirementsContractingProceduresExtndsCtrl -> Form to reference:: ',frm);
 	// EXTENSIONES
 	$scope.jsonList={};
 	$scope.frmParent.selected=[];
 	// LISTADO DE TÍTULOS
 	myResource.sendData('procedimientoscontratacion/REQUEST').save({procedureId:$scope.frmParent.procedimiento_id},function(json){
 		$scope.frmParent.selected=json.data.selected;
	},myResource.setError).$promise;
 	
 	// REGLAMENTOS PARA TIPOS DE ACCIONES
	myResource.requestData('financial/priorcontrol/requirements/list').json(function(json){
		$scope.requirementList=json.data;
	}).$promise;
	
	// AGREGAR TRABAJO A REALIZAR
	$scope.addJobToDo=function(idx){
		var aux={documento_index:1};
		$scope.frmParent[idx].push(aux);
	};
	// REMOVER TRABAJO A REALIZAR
	$scope.removeItem=function(item,idx){
		var index = $scope.frmParent[idx].indexOf(item);
		$scope.frmParent[idx].splice(index, 1);
	};
}])

/*
 * **********************************************************
 * PERMISOS DE FUNCIONAMIENTO - CONFIGURACIÓN DE FORMULARIOS
 * **********************************************************
 */
/* 
 * Modal: modalRequerimientos
 * Función: LISTA DE REQUERIMIENTOS DE PRIMER NIVEL PARA LA CREACIÓN DE UN NUEVO REQUERIMIENTO 2 NIVEL 
 */
.controller('reqListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.getData('req').json(function(json){$scope.list=json;}).$promise;
}])
/* 
 * Modal: modalPreguntas
 * Función: LISTA DE REQUERIMIENTOS PARA LA CREACIÓN DE PREGUNTAS 
 */
.controller('requerimientosListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.getData('requerimientos').json(function(json){$scope.list=json;}).$promise;
}])
/* 
 * Modal: modalTasas - modalCiiu
 * Función: LISTA DE MACRO-ACTIVIDADES 
 */
.controller('actividadesListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.getData('actividades').json(function(json){$scope.list=json;}).$promise;
}])
/* 
 * Modal: frmLocals
 * Función: LISTA CIIU
 */
.controller('ciiuListCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> ciiuExtendsCtrl -> Form to reference:: ');
	// *** LISTADO DE ACTIVDADES CIIU PARA LOCALES COMERCIALES
	$scope.frmParent=$scope.$parent.frmParent;
	var fk={name:'fk_ciiu',id:"ciiu_id",list:'FKList'};
	$scope.changeFK=function(index){$scope.frmParent.fk_ciiu_id=$scope.frmParent.fk_ciiu[fk.id];};
	$scope.getFKs=function(str){myResource.fillSelect('getCiiu',str,$scope,fk.list);};
}])
/* 
 * Modal: modalCiiu - modalLocales
 * Función: LISTA TASAS; LISTADI CIIU DE REFERENCIA EN LOCALES
 */
.controller('ciiuExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.frm;
	console.log('Extends request -> ciiuExtendsCtrl -> Form to reference:: ',frm);
	// LISTA
	var customQuery={};
	$scope.tasasList={};
	$scope.refreshTaxes=function(){
		if(myResource.testNull($scope.frmParent.fk_actividad_id))customQuery={customQuery:'searchByParent',parentName:'fk_actividad_id',parentId:$scope.frmParent.fk_actividad_id};
		myResource.getData('tasas').json(customQuery,function(json){$scope.tasasList=json;}).$promise;
	};	$scope.refreshTaxes();
	
	// *** LISTADO DE ACTIVDADES CIIU PARA LOCALES COMERCIALES
	$scope.frmParent=$scope.$parent[frm];
	var fk={name:'fk_ciiu',id:"ciiu_id",list:'FKList'};
	$scope.changeFK=function(index){$scope[frm][index]=$scope[frm][fk.name][fk.id];};
	$scope.getFKs=function(str){myResource.fillSelect('getCiiu',str,$scope,fk.list);};
}])
/* 
 * Modal: modalUploadCiiu
 * Función: AUTOCOMPLETADO DE PARÁMETROS INPUT 
 */
.controller('uploadCiiuExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.frm;
	console.log('Extends request -> uploadCiiuExtendsCtrl -> Form to reference:: ',frm);
	$scope.frmParent.codigoTasa='A';
	$scope.frmParent.codigo='B';
	$scope.frmParent.nombre='C';
	$scope.frmParent.nivel='D';
	$scope.frmParent.fila_inicio='A3';
}])
/* 
 * Modal: modalItmes
 * Función: LISTA PARA AGREGAR COMO ITEM EN EL MODAL ITEM -> CANTIDADES MÍNIMAS 
 */
.controller('requirementsListCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> requirementsListCtrl -> Form to reference:: ',frm);
	var data={fk_formulario_id:$scope.frmParent.fk_formulario_id,customQuery:'searchRequirementByClass'};
	myResource.getData('requerimientos').json(data,function(json){$scope.list=json;}).$promise;
}])
/* 
 * Modal: modalRequeridos
 * Función: 
 */
.controller('requirementsListaCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> requirementsListaCtrl -> Form to reference:: ',frm);
	myResource.getData('requerimientos').json(function(json){$scope.list=json;}).$promise;
}])

/*
 * **************************************************
 * PERMISOS DE FUNCIONAMIENTO - PERMISOS - ENTIDADES
 * **************************************************
 */
/* 
 * Modal: modalLocales
 * Función: PARSE INPUT DATE 
 */
.controller('localsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	
	// LISTADO DE CIIU
	$scope.FKList=[];
	var fk={name:'fk_ciiu',id:"ciiu_id",list:'FKList'};
	$scope.changeFK=function(index){$scope.frmParent[index]=$scope.frmParent[fk.name][fk.id];};
	$scope.getFKs=function(str){
		myResource.fillSelect('getCiiu',{filter:str},$scope,fk.list);
	};
	
}])
/* 
 * Modal: modalPermisos
 * Función: PARA GENERAR PERMISO DE OPERACIÓN E IMPRIMIR DIRECTAMENTE EL PERMISO 
 */
.controller('permitsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> permitsExtendsCtrl -> Form to reference:: ',$scope.frm);
	 $scope.customForm=function(){
		 myResource.sendData('permisos/PUT').save($scope.frmParent,function(json){
			if(json.estado===true){
				$scope.frmParent={};
				window.open(reportsURI+'permisos/?id='+json.data.id, '_blank');
				myResource.myDialog.closeDialog();
			}	myResource.myDialog.swalAlert(json);
		},myResource.setError);
	 };
}])

/*
 * *******************************************
 * DIRECCION ADMINISTRATIVA - SERVICIOS GENERALES
 * *******************************************
 */
 /*
  * Función: LISTADO DE CATEGORIA DE HERRAMIENTAS MENORES
  */
 .controller('typeMinorToolsListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('administrative/gservices/minortools/types/list').json(function(json){$scope.jsonList=json.data;}).$promise;
 }])
 /*
  * Función: LISTADO DE CATEGORIA DE HERRAMIENTAS MENORES
  */
 .controller('toolsListExtendsCtrl',['$scope','myResource',function($scope,myResource){
	 myResource.requestData('administrative/gservices/minortools/list').json(function(json){$scope.jsonList=json.data;}).$promise;
 }])
 
/* 
 * Modal: modalMantenimientos_menores
 * Función: Redireccionar a UI de edición
 */
.controller('minorMaintenanceExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// REDIRECCIONAR A EDICION DE ENTIDAD
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.entityId)) myResource.state.go('logistics.gservices.engineeringEdit',{entityId:$scope.jsonResource.entityId});
	},true);
}])


/*
 * *******************************************
 * LOGÍSTICA - COUNTER
 * *******************************************
 */
 /* 
  * Modal: modalChecking_externos
  * Función: 
  */
 .controller('checkingExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> checkingExtendsCtrl -> Form to reference:: ',frm);
 	// SELECCIÓN DE PARÁMETROS
 	$scope.frmParent.label='LB_VISITOR_INFORMATION';
 }])

 /*
  * *******************************************
  * SEGUNDA JEFATURA - FLOTAS
  * *******************************************
  */
 /* 
  * Modal: modalUnidadesLista
  * Función: AUTOCOMPLETAR EL FORMULARIO CON LOS DATOS PARA CAGAR ARCHIVO EXCEL
  */
.controller('unidadesListaExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> unidadesListaExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A3';
}])
/* 
 * Modal: modalUnidades
 * Función: 
 */
.controller('unitsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> unitsExtendsCtrl -> Form to reference:: ',frm);
	// SELECCIÓN DE PARÁMETROS
	$scope.frmParent.label='LB_OWNER_INFORMATION';
}])
/* 
 * Modal: modalUnidades
 * Función: PARSE DE FECHAS 
 */
.controller('planMaintenanceListCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> planMaintenanceListCtrl -> Form to reference:: ',frm);
	// SELECCIÓN DE PARÁMETROS
	myResource.sendData('reglasmantenimiento/REQUEST').save({type:'plansList'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalTracking
 * Función: PARSE DE FECHAS 
 */
.controller('trackingExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> trackingExtendsCtrl -> Form to reference:: ',frm);
	// SELECCIÓN DE PARÁMETROS
	if(!$scope.toogleEditMode) $scope.frmParent.flota_salida_tipo='SISTEMA';
	// LISTADO DE PASAJEROS
	$scope.frmParent.passengers={};
	myResource.sendData('flotasvehiculares/REQUEST').save({type:'passengers'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalOrdenescombustible
 * Función: LISTADO DE UNIDADES PARA ORDENES DE ABASTECIMIENTO
 */
.controller('unitsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('administrative/units').json(function(json){$scope.jsonList=json.data;}).$promise;
}])

/*
 * Modal: modalOrdenescombustible
 * Función: 
 */
.controller('fuelOrderExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> fuelOrderExtendsCtrl -> Form to reference:: ',frm);
	// AGREGAR Y ELIMINAR ITEMS
	$scope.addItem=function(entity){ $scope.frmParent[entity].push({}); };
	$scope.removeItem=function(entity,itemKey){ $scope.frmParent[entity].splice(itemKey,1); };
	
}])
/* 
 * Modal: modalOrdenesMantenimiento
 * Función: 
 */
.controller('maintenanceordersExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> maintenanceordersExtendsCtrl -> Form to reference:: ',frm);
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	if($scope.toogleEditMode){
		myResource.sendData('ordenesmantenimiento/REQUEST').save({id:$scope.frmParent.orden_id},function(json){
			$scope.frmParent=angular.merge($scope.frmParent,json.data.data);
		});
	}
	// SELECCIÓN DE PARÁMETROS
	if(!$scope.toogleEditMode) $scope.frmParent.orden_fecha_mantenimiento_tipo='SISTEMA';
	// AGREGAR TRABAJO A REALIZAR
	$scope.addJobToDo=function(idx){
		var aux={trabajo_descripcion:''};
		$scope.frmParent[idx].push(aux);
	};
	// REMOVER TRABAJO A REALIZAR
	$scope.removeItem=function(item,idx){
		var index = $scope.frmParent[idx].indexOf(item);
		$scope.frmParent[idx].splice(index, 1);
	};
	// CALCULAR PROXIMO MANTENIMIENTO
	$scope.updateNextMaintenance=function(){
		$scope.frmParent.orden_proximomantenimiento=parseInt($scope.frmParent.orden_km_mantenimiento)+parseInt(angular.fromJson($scope.paramsConf.MAINTENANCE_ORDER_TYPE)[$scope.frmParent.orden_tipomantenimiento]);
	};
	// REDIRECCIONAR A IMPRESIÓN DE ORDEN DE COBRO
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.orden_id)) window.open(reportsURI+'ordenesmantenimiento/?id='+$scope.jsonResource.orden_id,'_blank');
	},true);
}])
/* 
 * Modal: modalOrdenesMovilizacion
 * Función: 
 */
.controller('mobilizationordersExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> mobilizationordersExtendsCtrl -> Form to reference:: ',frm);
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	if($scope.toogleEditMode){
		$scope.frmParent.administrative={};
		$scope.frmParent.professional={};
		myResource.sendData('ordenesmovilizacion/REQUEST').save({id:$scope.frmParent.orden_id},function(json){
			$scope.frmParent.administrative=json.data.data.administrative;
			$scope.frmParent.professional=json.data.data.professional;
		});
	}
	// SELECCIÓN DE PARÁMETROS
	if(!$scope.toogleEditMode) $scope.frmParent.orden_hora_salida_tipo='SISTEMA';
	
	// REDIRECCIONAR A IMPRESIÓN DE ORDEN DE COBRO
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.orden_id)) window.open(reportsURI+'ordenesmovilizacion/?id='+$scope.jsonResource.orden_id,'_blank');
	},true);
}])
/* 
 * Modal: modalOrdenesTrabajo
 * Función: 
 */
.controller('workordersExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> workordersExtendsCtrl -> Form to reference:: ',frm);
	// TIPO DE SUBMIT
	var option='POST';
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	if($scope.toogleEditMode){
		$scope.frmParent.administrative={};
		$scope.frmParent.professional={};
		myResource.sendData('ordenestrabajo/REQUEST').save({id:$scope.frmParent.orden_id},function(json){
			$scope.frmParent.administrative=json.data.data.administrative;
			$scope.frmParent.professional=json.data.data.professional;
		});
		option='PUT';
	};
	// REDIRECCIONAR A IMPRESIÓN DE ORDEN DE COBRO
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.orden_id)) window.open(reportsURI+'ordenestrabajo/?id='+$scope.jsonResource.orden_id,'_blank');
	},true);
}])
/* 
 * Modal: modalOrdenesTrabajoLista
 * Función: 
 */
.controller('workordersListExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> workordersListExtendsCtrl -> Form to reference:: ',frm);
	
	// INFORMACION PARA ORDEN
	myResource.sendData('ordenestrabajo/REQUEST').save({getInfo:'info'},function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	});

	// LISTDO DE RESPONSABLES
	myResource.requestData('administrative/units').json(function(json){ $scope.FKListUnits=json.data; }).$promise;
	
}])
/* 
 * Modal: modalMantenimiento_facturas
 * Función: 
 */
.controller('maintenanceordersInvoicesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> maintenanceordersInvoicesExtendsCtrl -> Form to reference:: ',frm);
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	$scope.frmParent.invoicesList=[];
	myResource.sendData('ordenesmantenimiento/REQUEST').save({getInvoicesFrom:$scope.frmParent.orden_id},function(json){
		$scope.frmParent.invoicesList=json.data.invoicesList;
	});
	// AGREGAR TRABAJO A REALIZAR
	$scope.addJobToDo=function(){
		$scope.frmParent.invoicesList.push({});
	};
	// REMOVER TRABAJO A REALIZAR
	$scope.removeItem=function(item){
		var index = $scope.frmParent.invoicesList.indexOf(item);
		$scope.frmParent.invoicesList.splice(index, 1);
	};
}])
/* 
 * Modal: modalAbastecimiento
 * Función: PARSE DE FECHAS 
 */
.controller('supplyingExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> supplyingExtendsCtrl -> Form to reference:: ',frm);
	// SELECCIÓN DE PARÁMETROS
	if(!$scope.toogleEditMode) $scope.frmParent.flota_salida_tipo='SISTEMA';
	// LISTADO DE PASAJEROS
	$scope.frmParent.passengers={};
	myResource.sendData('flotasvehiculares/REQUEST').save({type:'passengers'},function(json){$scope.jsonList=json.data;}).$promise;
	// CALCULAR EL PRECIO DE COMBUSTIBLE
	$scope.calculatePrice=function(){
		$scope.frmParent.abastecimiento_precio=$scope.frmParent.gasPrice[$scope.frmParent.abastecimiento_combustible];
	};
}])
/* 
 * Modal: modalMantenimientos
 * Función: 
 */
.controller('maintenancesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> maintenancesExtendsCtrl -> Form to reference:: ',frm);
	// SELECCIÓN DE PARÁMETROS
	if(!$scope.toogleEditMode) $scope.frmParent.flota_salida_tipo='SISTEMA';
	// LISTADO DE RECURSOS PARA MANTENIMIENTOS
	$scope.refreshResources=function(){
		myResource.sendData('mantenimientosvehiculares/REQUEST').save({type:'unitId',unitId:$scope.frmParent.unidad_id},function(json){
			$scope.frmExtends=json.data.resources;
			$scope.frmParent.maintenance=json.data.maintenance;
		}).$promise;
	}; $scope.refreshResources();
}])
/* 
 * Modal: modalRevisionvehicular
 * Función: 
 */
.controller('vehicularreviewExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> vehicularreviewExtendsCtrl -> Form to reference:: ',frm);
	// SELECCIÓN DE PARÁMETROS
	if(!$scope.toogleEditMode) $scope.frmParent.flota_salida_tipo='SISTEMA';
	// LISTADO DE PASAJEROS
	$scope.frmParent.passengers={};
	myResource.sendData('flotasvehiculares/REQUEST').save({type:'passengers'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalTracking
 * Función: LISTADO DE PERSONAL BAJO PERFIL DE SEGUNDA JEFATURA
 * PERFILES: CONDUCTOR
 */
.controller('driversListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/drivers').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalTracking
 * Función: LISTADO DE CÓDIGOS PARA FLOTAS
 * TIPO: FLOTAS
 */
.controller('codesTrackingListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('resources/REQUEST').save({type:'institutionalcodes',option:'tracking'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalRevisiones_procesos
 * Función: CONSULTAR SI EXISTE LA REVISION DE UN PROCESO
 * TIPO: 
 */
.controller('reviewProcessExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> reviewProcessExtendsCtrl -> Form to reference:: ',frm);
	
	// CONSULTAR REVISIONES
	myResource.sendData('revisiones_procesos/REQUEST').save($scope.frmParent,function(json){
		// CONSULTAR ESTADO DE CAMBIOS
		if(json.estado) $scope.frmParent=angular.merge($scope.frmParent,json.data);
		myResource.myDialog.showNotify(json);
		
		console.log($scope.frmParent);
		
	}).$promise;
}])

/*
 * *******************************************
 * SEGUNDA JEFATURA - MANTENIMIENTO 
 * *******************************************
 */
/* 
 * Modal: modalReglasmantenimiento
 * Función: LISTA DE PLANES DE MANTENIMIENTO
 */
.controller('rulesMaintenancesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> rulesMaintenancesExtendsCtrl -> Form to reference:: ',frm);
	// VARIABLE PARA LA SELECCIÓN DE REPUESTOS
	$scope.frmParent.selected={};
	// CONSULTAR LOS REPUESTOS Y MANO DE OBRA DISPONIBLES PARA EL PLAN
	var dataRequest=$scope.frmParent;
	dataRequest.type='repuestos';
	myResource.sendData('reglasmantenimiento/REQUEST').save(dataRequest,function(json){
		$scope.list=json;
		$scope.frmParent.selected=json.data.selected;
	}).$promise;
}])

/*
 * *******************************************
 * SEGUNDA JEFATURA - PARTES 
 * *******************************************
 */
 /* 
  * Modal: modalNaturalezaincidente
  * Función: 
  */
.controller('natureExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> natureExtendsCtrl -> Form to reference:: ',$scope.frm);
 	$scope.editorLoaded=function(jsonEditor){jsonEditor.expandAll();};
 	$scope.options={mode:'tree'};
}])
/* 
 * Modal: modalNaturaleza_claves
 * Función: 
 */
.controller('natureKeysExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log({natureId:$scope.frmParent.naturaleza_id});
	myResource.requestData('subjefature/codesByNature').save({natureId:$scope.frmParent.naturaleza_id},function(json){
		$scope.jsonList=json.data.codesList;
		$scope.frmParent.selectedList=json.data.selectedList;
	}).$promise;
}])
/* 
 * Modal: modalPartes
 * Función: PARSE DE FECHAS 
 */
.controller('partsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	
}])


/*
 * **************************************************
 * PREVENCIÓN - OTRAS ACTIVIDADES
 * **************************************************
 */
/*
 * EXTENDS PARA SUBIR ANEXOS A PLANES DE AUTOPROTECCION
 */
.controller('selfProtectionAnexesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// CARGAR ARCHIVOS ADJUNTOS
	$scope.uploadAnnexes=function(){
		// INSERTAR NOMBRE DE ARCHIVO A ACTUALIZAR
		$scope.frmParent.annexeEntity=$scope.frmParent.fileNameUpload;
		// ENVIAR DATOS A BACKEND
		myResource.uploadFile.uploadFile(rootRequest+$scope.frmParent.tbEntity,$scope,'frmParent').then(function(json){
			// PRESENTAR MENSAJE DE BACKEND
			myResource.myDialog.swalAlert(json);
			// CERRAR VENTANA
			myResource.myDialog.closeDialog();
		},myResource.setError);
	};
}])
/* 
 * Modal: modalTrabajoficina
 * Función: PARSE DE FECHAS 
 */
.controller('officeworkExtendsCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('personal/REQUEST').save({userId:$scope.frmParent.fk_inspector_id},function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
}])
/* 
 * Modal: modalAtencionusuario
 * Función: PARSE DE FECHAS 
 */
.controller('supportExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	// SELECCIÓN DE PARÁMETROS
 	$scope.frmParent.label='LB_VISITOR_INFORMATION';
}])

/*
 * **************************************************
 * PREVENCIÓN - INSPECCIONES
 * **************************************************
 */
/* 
 * Modal: modalParticipantesLista
 * Función: PARA CARGAR LOS DATOS DE PARTICIPANTES DE UNA CAPACITACIÓN 
 */
.controller('participantsLisExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> participantsLisExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.doc_identidad='A';
	$scope.frmParent.apellidos='B';
	$scope.frmParent.nombres='C';
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A2';
}])
/* 
 * Modal: modalInspecciones_inspectores
 * Función: LISTAR INSPECTORES PARA ASIGNAR A INSPECCION 
 */
.controller('inspeccionInspectorExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> inspeccionInspectorExtendsCtrl -> Form to reference:: ',$scope.frm);
	// LISTADO DE INSPECTORES
	$scope.frmParent.selected={};
	// LISTAR INSPECTORES PARA CAPACITACION
	var dataRequest=$scope.frmParent;
	dataRequest.type='getInspectors';
	myResource.sendData('inspecciones/REQUEST').save(dataRequest,function(json){$scope.frmParent=json.data;}).$promise;
	// SUBMIT PERSONALIZADO
	$scope.customSubmitForm=function(){
		// REALIZAR PETICION A SERVER
		myResource.sendData('inspectores/PUT').update($scope.frmParent,function(json){
			// PRESENTAR RESULTADO DE PETICION
			 myResource.myDialog.showNotify(json);
			 if(json.estado===true) myResource.myDialog.closeDialog();
		},myResource.setError);
	};
}])
/* 
 * Modal: modalInspecciones_inspectores
 * Función: LISTAR INSPECTORES PARA ASIGNAR A INSPECCION 
 */
.controller('inspectorsStaftList',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> inspectorsStaftList -> Form to reference:: ',$scope.frm);
	// LISTAR INSPECTORES PARA CAPACITACION
	myResource.requestData('admin/users/prevention').json(function(json){$scope.list=json.data;}).$promise;
}])
/* 
 * Modal: modalAsignacion
 * Función: LISTAR INSPECTORES PARA ASIGNAR A INSPECCION 
 */
.controller('assignmentInspectorExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> assignmentInspectorExtendsCtrl -> Form to reference:: ',$scope.frm);
	// LISTADO DE INSPECTORES
	$scope.frmParent.selected=[];
	// LISTAR INSPECTORES PARA CAPACITACION
	var dataRequest=$scope.frmParent;
	dataRequest.type='getInspectors';
	myResource.sendData($scope.frmParent.tb+'/REQUEST').save(dataRequest,function(json){$scope.frmParent=json.data;}).$promise;
	// SUBMIT PERSONALIZADO
	$scope.customSubmitForm=function(){
		// REALIZAR PETICION A SERVER
		myResource.sendData($scope.frmParent.tb+'/PUT').update($scope.frmParent,function(json){
			// PRESENTAR RESULTADO DE PETICION
			 myResource.myDialog.showNotify(json);
			 if(json.estado===true) myResource.myDialog.closeDialog();
		},myResource.setError);
	};
}])
/* 
 * Modal: modalBarridos
 * Función:  
 */
.controller('sweepExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> sweepExtendsCtrl -> Form to reference:: ',$scope.frm);
	// DATOS PRECARGADOS
	$scope.frmParent.inspeccion_fecha_inspeccion=$scope.today;
	// CARGAR EL ID DE INSPECTOR - SESSION DE INSPECTOR
	if($scope.paramsSession.fk_perfil_id==6) $scope.frmParent.fk_inspector_id=$scope.paramsSession.usuario_id;
	// SUBMIT PERSONALIZADO
	$scope.customSubmitForm=function(){
		// DIALOGO DE CONFIRMACION
		myResource.myDialog.swalConfirm('Confirmar datos!',function(){
			// REALIZAR PETICION A SERVER
			myResource.sendData('barridos/PUT').update($scope.frmParent,function(json){
				// PRESENTAR RESULTADO DE PETICION
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true){
					 // CERRAR VENTANA MODAL
					 myResource.myDialog.closeDialog();
					 // REDIRIGIR A LA INSPECCION
					 myResource.state.go('prevention.inspections.preinspection',{id:json.data.id});
				 }
			},myResource.setError);
		});
	};
	// ******************************** GET DATA PARA RELACIÓN - sucursal o permisos
	$scope.toggle=function(item){
		var idx=$scope.frmParent.selected.indexOf(item);
		(idx>-1)?$scope.frmParent.selected.splice(idx,1):$scope.frmParent.selected.push(item);
	};
	$scope.exists=function(item){return(myResource.testNull($scope.frmParent.selected))?$scope.frmParent.selected.indexOf(item)>-1:null;};
}])
/* 
 * Modal: modalPermisos
 * Función: PARA GENERAR PERMISO DE OPERACIÓN E IMPRIMIR DIRECTAMENTE EL PERMISO 
 */
.controller('inspectorsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('usuarios/REQUEST').save({profile:'inspector'},function(json){
		$scope.list=json.data;
	}).$promise;
}])
/* 
 * Modal: modalPermisos
 * Función: PARA GENERAR PERMISO DE OPERACIÓN E IMPRIMIR DIRECTAMENTE EL PERMISO 
 */
.controller('citationsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> citationsExtendsCtrl -> Form to reference:: ',frm);
	var list=['citacion_fecha_reinspeccion'];
	// myResource.setFormatDate($scope,frm,list);
}])
/* 
 * Form: step2 - formularios de Inspección Paso 2
 * Función: CALCULAR LOS TOTALES DE CARACTERÍSTICAS DE LOCALES 
 */
.controller('agspExtendsCtrl',['$scope',function($scope){
	$scope.getArea=function(){
		$scope.frmLocal.local_area_construccion=parseFloat($scope.frmLocal.local_area_subsuelos)+parseFloat($scope.frmLocal.local_area_planta_baja);
		$scope.frmLocal.local_area=$scope.frmLocal.local_area_planta_baja;
	}
}])
.controller('balnExtendsCtrl',['$scope',function($scope){
	$scope.getArea=function(){
		$scope.frmLocal.local_area_construccion=parseFloat($scope.frmLocal.local_area_otros)+parseFloat($scope.frmLocal.local_area_subsuelos)+parseFloat($scope.frmLocal.local_area_planta_baja);
		$scope.frmLocal.local_area=parseFloat($scope.frmLocal.local_area_planta_baja)+parseFloat($scope.frmLocal.local_area_otros);
	}
}])
.controller('cglpExtendsCtrl',['$scope',function($scope){
	$scope.frmLocal.local_plantas=1;
}])
.controller('gassExtendsCtrl',['$scope',function($scope){
	$scope.frmLocal.local_area_planta_baja=0;
	$scope.getArea=function(){
		$scope.frmLocal.local_area=parseFloat($scope.frmLocal.local_area_otros)+parseFloat($scope.frmLocal.local_area_subsuelos)+parseFloat($scope.frmLocal.local_area_planta_baja);
	}
}])


/*
 * *******************************************
 * PREVENCIÓN - PERMISO DE TRANSPORTE DE GLP
 * *******************************************
 */
/* 
 * Modal: modalTransporteglp
 * Función: parsear datos de representante legal como nombre de propietario de vehículo
 */
.controller('glpTransportExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> glpTransportExtendsCtrl -> Form to reference:: ',frm);
	// LABEL PARA FORMULARIO DE PERSONAS
	$scope.frmParent.label='LB_OWNER_INFORMATION';
}])
/* 
 * Modal: modalVehiculos
 * Función: 
 */
.controller('vehiclesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> vehiclesExtendsCtrl -> Form to reference:: ',frm);
	// LABEL PARA FORMULARIO DE PERSONAS
	$scope.frmParent.label='LB_OWNER_INFORMATION';
}])
/* 
 * ui: reviewGlpTransport
 * Función: LISTA DE RECURSOS PARA TRANSPORTE GLP
 */
.controller('GLPTransportListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.getData('recursosglp').json(function(json){$scope.list=json;}).$promise;
}])


/*
 * *******************************************
 * PREVENCIÓN - PLANES DE EMERGENCIA
 * *******************************************
 */
/*
 * Modal: modalPlanesemergencia
 * Función: PARSE INPUT DATE
 */
.controller('planExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm='frmParent';
	console.log('Extends request -> planExtendsCtrl -> Form to reference:: '+ frm);
	$scope.frmParent.local={};
	if($scope.frmParent.plan_id>0){
		myResource.sendData('planesemergencia/REQUEST').save({id:$scope.frmParent.plan_id},function(json){
			$scope.frmParent.local=json.data.local;
		}).$promise;
	}else{
		myResource.sendData('resources/REQUEST').save({entity:'entityById',entityName:'locales',entityId:$scope.frmParent.local_id},function(json){
			$scope.frmParent.local=json.data;
		}).$promise;
	}
}])

/*
 * *******************************************
 * PREVENCIÓN - PERMISO OCASIONAL DE FUNCIONAMIENTO
 * *******************************************
 */
/*
 * Modal: modalReglasocasionales
 * Función: PARSE INPUT DATE
 */
.controller('resourcesPOFList',['$scope','myResource',function($scope,myResource){
	myResource.getData('recursosocasionales').json(function(json){$scope.list=json;}).$promise;
}])
/* 
 * Modal: modalOcasionales
 * Función: PARSE CAMPOS TIPO FECHAS, INICIAR LA VARIABLES
 */
.controller('occasionalExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> occasionalExtendsCtrl -> Form to reference:: ',frm);
	// EDICIÓN DE FECHAS
	if(!$scope.toogleEditMode) $scope.frmParent.ocasional_lugar='VIA PUBLICA';
}])

/*
 * *******************************************
 * PREVENCIÓN - CAPACITACIONES
 * *******************************************
 */
/* 
 * Modal: modalParticipantes
 * Función: LISTADO DE PERSONAL PARA CAPACITACIONES
 * PERFILES: TOMAR ASISTENCIA DE PERSONAL
 */
.controller('participantsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.selected={};
	var training={trainingId:$scope.frmParent.capacitacion_id};
	myResource.sendData('participantes/REQUEST').save(training,function(json){
		$scope.requestList=json.participants;
		$scope.selected=json.attendance;
	}).$promise;
	// ******************************** GET DATA PARA RELACIÓN
	$scope.toggle=function(item){
		training.fk_participante_id=item;
		myResource.sendData('participantes/PUT').update(training,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=$scope.selected.indexOf(item);
				(idx>-1)?$scope.selected.splice(idx,1):$scope.selected.push(item);
			}
		});
	};
	$scope.exists=function(item){return(myResource.testNull($scope.selected))?$scope.selected.indexOf(item)>-1:null;};
}])
/*
 * SELECCIÓN DE PERSONAL PARA CAPACITACIÓN
 */
.controller('peopleTrainingExtendsCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('participantes/REQUEST').save({peopleTrainingId:$scope.frmParent.capacitacion_id},function(json){
		$scope.list=json.participants;
		$scope.selected=json.attendance;
	});
	// ******************************** GET DATA PARA RELACIÓN - sucursal o permisos
	$scope.toggle=function(item){
		var data={fk_capacitacion_id:$scope.frmParent.capacitacion_id,fk_participante_id:item};
		myResource.sendData('participantes/REQUEST').update(data,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=$scope.selected.indexOf(item);
				(idx>-1)?$scope.selected.splice(idx,1):$scope.selected.push(item);
			}
		});
	};
	$scope.exists=function(item){return (myResource.testNull($scope.selected))?$scope.selected.indexOf(item)>-1:null;};
	// ******************************** CERRAR MODAL
	$scope.closeDialog=function(){ myResource.myDialog.closeDialog(); };
}])

/*
 * *******************************************
 * PREVENCIÓN - CASAS ABIERTAS
 * *******************************************
 */
/* 
 * Modal: modalStands
 * Función: LISTADO DE PERSONAL PARA COORDINAR CASAS ABIERTAS
 * PERFILES: INSPECTOR - CAPACITADOR
 */
.controller('coordinatorsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('usuarios/REQUEST').save({profile:'coordinator'},function(json){
		$scope.list=json.data;
	}).$promise;
}])

/*
 * *******************************************
 * PREVENCIÓN - VISITAS
 * *******************************************
 */
/* 
  * Modal: modalVisitas
  * Función: PARSE DE FECHAS 
  */
.controller('visitExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> visitExtendsCtrl -> Form to reference:: ',frm);
 	$scope.frmParent.label='LB_COORDINATOR';
}])
/* 
 * Modal: modalVeedores
 * Función: ADMINISTRAR INFORMACIÓN DE LOS VEEDORES
 * PERFILES: VEEDORES
 */
.controller('trainersPreventionExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// VARIABLES PARA MODELO
	$scope.frmParent.selected=[];
	// CONSULTAR TRAINERS
	myResource.sendData('trainers/REQUEST').save($scope.frmParent,function(json){
		$scope.list=json.data.list;
		$scope.frmParent.selected=json.data.selected;
		$scope.frmParent.model=json.data.model;
		$scope.frmParent.entity=json.data.entity;
		$scope.functionsList=json.data.activities;
	}).$promise;
	// ******************************** GET DATA PARA RELACIÓN - sucursal o permisos
	$scope.toggle=function(item){
		var idx=$scope.frmParent.selected.indexOf(item);
		(idx>-1)?$scope.frmParent.selected.splice(idx,1):$scope.frmParent.selected.push(item);
	};
	$scope.exists=function(item){return(myResource.testNull($scope.frmParent.selected))?$scope.frmParent.selected.indexOf(item)>-1:null;};
}])

/*
 * *******************************************
 * PREVENCIÓN - VISTO BUENO DE PLANOS
 * *******************************************
 */
/* 
 * Modal: modalVbp
 * Función: 
 * PERFILES: 
 */
.controller('vbpExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// LISTADO DE ACTIVIDADES ECONÓMICAS
	$scope.activitiesList={};
	myResource.sendData('resources/REQUEST').save({mod:'vbp',list:'activities'},function(json){$scope.activitiesList=json.data;});
	// DATOS DE FACTURACIÓN
	$scope.frmParent.billing={};
	// BUSCAR DATOS DE RUC
	if($scope.toogleEditMode && $scope.frmParent.vbp_facturacion=='OTRA'){
		// REALIZAR CONSULTA SI RUC NO ESTÁ VACIO
		if(myResource.testNull($scope.frmParent.facturacion_ruc)){
			myResource.sendData('entidades/REQUEST').save({ruc:$scope.frmParent.facturacion_ruc},function(json){$scope.frmParent.billing=json.data;});
		}
	}
	$scope.editorLoaded=function(jsonEditor){jsonEditor.expandAll();};
	$scope.options={mode:'tree'};
}])
/* 
 * Modal: modalResponsables
 * Función: 
 */
.controller('professionalExtendsCtrl',['$scope','findEntityService',function($scope,findEntityService){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> responsibleExtendsCtrl -> Form to reference:: ',frm);
	// LISTADO DE RECURSOS
	findEntityService.myResource.sendData('vbp/REQUEST').save({profesionals:true,projectId:$scope.frmParent.vbp_id},function(json){
		$scope.frmParent.model=json.data.professionals;
	}).$promise;
	// BUSCAR PERSONA
	$scope.searchPersonInformation=function(data,frm,model,index){
		if(findEntityService.myResource.testNull(data)) findEntityService.findPerson({academicTraining:data},$scope,frm,model,index);
	};
}])

/*
 * *******************************************
 * PREVENCIÓN - FACTIBILIDAD DE GLP
 * *******************************************
 */
/* 
 * Modal: modalFactibilidad
 * Función: 
 * PERFILES: 
 */
.controller('feasibilityExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// DATOS DE FACTURACIÓN
	$scope.frmParent.billing={};
	$scope.frmParent.tanksList=[];
	$scope.frmParent.professionalsList=[];
	// BUSCAR DATOS DE RUC
	if($scope.toogleEditMode){
		// REALIZAR CONSULTA SI RUC NO ESTÁ VACIO
		myResource.sendData('factibilidadglp/REQUEST').save({feasibilityId:$scope.frmParent.factibilidad_id},function(json){
			// $scope.frmParent.tanksList=json.data.tanksList;
			// $scope.frmParent.billing=angular.merge($scope.frmParent.billing,json.data.billing);
			$scope.frmParent=angular.merge($scope.frmParent,json.data);
		});
	}
	// AGREGAR TRABAJO A REALIZAR
	$scope.addJobToDo=function(idx){
		var aux={tanque_capacidad:'0'};
		$scope.frmParent[idx].push(aux);
	};
	// REMOVER TRABAJO A REALIZAR
	$scope.removeItem=function(item,idx){
		var index = $scope.frmParent[idx].indexOf(item);
		$scope.frmParent[idx].splice(index, 1);
	};
}])
/* 
 * Modal: modalResponsables
 * Función: 
 */
.controller('professionalGlpExtendsCtrl',['$scope','findEntityService',function($scope,findEntityService){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> responsibleExtendsCtrl -> Form to reference:: ',frm);
	// LISTADO DE RECURSOS
	findEntityService.myResource.sendData('factibilidadglp/REQUEST').save({profesionals:true,projectId:$scope.frmParent.factibilidad_id},function(json){
		$scope.frmParent.professionalList=json.data.professionalList;
	}).$promise;
	// BUSCAR PERSONA
	$scope.searchPersonInformation=function(data,frm,model,index){
		if(findEntityService.myResource.testNull(data)) findEntityService.findPerson({academicTraining:data},$scope,frm,model,index);
	};
}])
/*
 * 
 * Modal: modalInformemantenimiento
 * funcion: listar mantenimientos
 */
 .controller('reportMaintenanceExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> reportMaintenanceExtendsCtrl -> Form to reference:: ',frm);
	// CONSULTAR LOS REPUESTOS Y MANO DE OBRA DISPONIBLES PARA EL PLAN
	var dataRequest=$scope.frmParent;
	dataRequest.type='informe';
	myResource.sendData('ordenesmantenimiento/REQUEST').save(dataRequest,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
	// REDIRECCIONAR A IMPRESIÓN de acción de personal
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.informe_id)) window.open(reportsURI+'informemantenimiento/?withDetail&id='+$scope.jsonResource.informe_id,'_blank');
	},true);
	
}])
/*
 * 
 * Modal: modalInspeccion_extintor
 * funcion: listar extintores de las unidades
 */
 .controller('reviewFextinguisherExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> reviewFextinguisherExtendsCtrl -> Form to reference:: ',frm);
	
	// CONSULTAR LOS REPUESTOS Y MANO DE OBRA DISPONIBLES PARA EL PLAN
	var dataRequest=$scope.frmParent;
	
	$scope.jsonList=[];
	
	myResource.requestData('tthh/sos/fextinguisher/list/stationId').save(dataRequest,function(json){
		
		$scope.jsonList=json.data.list;
		
		$scope.frmParent.selected=json.data.selected;
		
	}).$promise;
	
	// REDIRECCIONAR A IMPRESIÓN de acción de personal
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.informe_id)) window.open(reportsURI+'informemantenimiento/?withDetail&id='+$scope.jsonResource.informe_id,'_blank');
	},true);
	
}])
/* 
 * Modal: modalExportdata
 * Función: 
 */
.controller('exportDataExtendsCtrl',['$scope','myResource',function($scope,myResource){
	 console.log('Extends request -> exportDataExtendsCtrl -> Form to reference:: ',$scope.frm);
	 $scope.frmParent.fieldList=angular.fromJson($scope.paramsConf['EXPORTDATA_REPORTS_LIST_MODULES'])[$scope.frmParent.entity];
}])

;