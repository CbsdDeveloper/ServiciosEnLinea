/**
 * 
 */
app

/*
 * PERFIL DE ENTIDAD COMERCIAL
 */
.controller('profileCtrl',['data','locals','$scope','myResource',function(data,locals,$scope,myResource){
	// DATOS DE ENTIDAD
	$scope.entity=data.data;
	
	// LISTADO DE ACTIVIDADES ECONOMICAS
	$scope.econocmicActivitiesList=locals.data;

	// LISTAR ACTIVIDADES ECONOMICAS
	$scope.loadActivitiesList=function(){
		// ACTUALIZAR LISTA DE ANEXOS
		myResource.requestData('permits/locals/list/entityId').save({entityId:myResource.cookies.get('userID')},function(json){
			$scope.econocmicActivitiesList=json.data;
		}).$promise;
	};
	
	// INFORMACION DE ENTIDAD
	$scope.loadEntityInformation=function(){
		// ACTUALIZAR LISTA DE ANEXOS
		myResource.requestData('permits/entities/detail/entityId').save({data:'account',entityId:myResource.cookies.get('userID')},function(json){
			$scope.entity=json.data;
		}).$promise;
	};
	
	// ACTUALIZAR LISTADO DE ANEXOS
	$scope.$watch('$parent.updateResource',function(newValue,oldValue){
		if($scope.$parent.updateResource=='Entidades'){
			myResource.timeout(function(){ window.location.assign("./entity/profile/"); }, 5000);
		}else{
			$scope.loadActivitiesList();
			$scope.loadEntityInformation();
		}
		// ANULAR VARIABLE DE ACTUALIZACION
		$scope.$parent.updateResource='';
	},true);
	
	// ACEPTAR TERMINOS
	$scope.submitFormEntity=function(){
		myResource.requestData('permits/entities/terms/accept').update({data:'terms',entityId:myResource.cookies.get('userID')},function(json){
			// PRESENTAR MENSAJE DE BACKEND
			myResource.myDialog.swalAlert(json);
			if(json.estado===true) $scope.entity=json.data;
			// if(json.estado===true){ myResource.timeout(function(){ window.location.assign("./entity/profile/"); }, 5000); }
		}).$promise;
	};
	
}])
/*
 * TERMINOS Y CONDICIONES
 */
.controller('termsCtrl',['data','$scope','myResource',function(data,$scope,myResource){
	// DATOS DE ENTIDAD
	$scope.entity=data.data;
	
	// ACTUALIZAR LISTADO DE ANEXOS
	$scope.$watch('$parent.updateResource',function(newValue,oldValue){
		// ACTUALIZAR LISTA DE ANEXOS
		myResource.requestData('permits/entities/detail/entityId').save({data:'account',entityId:myResource.cookies.get('userID')},function(json){
			$scope.entity=json.data;
		}).$promise;
		// ANULAR VARIABLE DE ACTUALIZACION
		$scope.$parent.updateResource='';
	},true);
	
	$scope.submitFormEntity=function(){
		myResource.requestData('permits/entities/terms/accept').update({data:'terms',entityId:myResource.cookies.get('userID')},function(json){
			// PRESENTAR MENSAJE DE BACKEND
			myResource.myDialog.swalAlert(json);
			// VALIDAR LA REDIRECCION DE LA PAGINA PARA COMPLETAR LOS DATOS
			if(json.estado===true){ 
				$scope.entity=json.data;
				myResource.timeout(function(){ window.location.assign("./entity/profile/"); }, 5000); 
			}
		}).$promise;
	};
	
}])
/*
 * LISTADO DE PERSONAL DE LA INSTITUCION
 */
.controller('staffCtrl',['$scope','$rootScope','myResource',function($scope,$rootScope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Capacitaciones',parent:'Participantes',tb:'getParticipants',order:'persona_apellidos',toolbar:{}};
}])

/* *****************************************************
 * ACTIVIDADES ECONOMICAS
 * *****************************************************
 */
 /*
  * LOCALES - ACTIVIDADES COMERCIALES
  */
 .controller('economicActivitiesCtrl',['$scope','myResource',function($scope,myResource){
 	// PARÁMETROS PARA LISTADO DE REGISTROS
 	$scope.tbParams={parent:'Locales',tb:'getLocales',order:'-local_nombrecomercial',toolbar:{}};
 	// BOTON PARA CONFIRMAR NUEVO PLAN DE AUTOPROTECCIÓN
 	$scope.newSelfprotectionPlan=function(localId){
 		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
 		myResource.myDialog.swalConfirm($scope.paramsConf['PREVENCION_ALERT_NEW_PLAN'],function(){
 			myResource.state.go('permits.selfProtection.step1',{localId:localId});
 		});
 	};
 }])
/*
 * DETALLE DE LOCALES COMERCIALES
 */
.controller('detailEconomicActivitiesCtrl',['entity','plans','inspections','permits','$scope','myResource',function(entity,plans,inspections,permits,$scope,myResource){
	// INFORMACIÓN DE ENTIDAD
	$scope.entity=entity.data;
	$scope.dataEntity=$scope.entity;
	// PLANES DE AUTOPROTECCIÓN DEL LOCAL
	$scope.plansList=plans.data;
	// INSPECCIONES DEL LOCAL
	$scope.inspectionsList=inspections.data;
	// PERMISOS DEL LOCAL
	$scope.permitsList=permits.data;
}])

/* *****************************************************
 * PLANES DE AUTOPROTECCION
 * *****************************************************
 */
/*
 * PLAN DE EMERGENCIA
 */
.controller('selfProtectionPlansCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={parent:'Planes',tb:'getPlanes',order:'-plan_aprobado',toolbar:{}};
	// IMPRIMIR MODELO DE PLAN
	$scope.prinrModelById=function(planId){
		window.open(reportsURI+'planautoproteccion/?printType=model&id='+planId,'_blank');
	};
}])

/* *****************************************************
 * PERMISOS DE TRANSPORTE DE COMBUSTIBLE
 * *****************************************************
 */
/*
 * PERMISO DE TRANSPORTE DE GLP
 */
.controller('glpTransportCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={parent:'Vehiculosglp',tb:'getVehiclesGLP',order:'-vehiculo_placa',toolbar:{}};
}])

/* *****************************************************
 * PERMISOS OCASIONALES
 * *****************************************************
 */
/*
 * PERMISOS OCASIONALES DE FUNCIONAMIENTO
 */
.controller('occasionalsCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Ocasionales',parent:'Ocasionales',tb:'getOccasionals',order:'-ocasional_fecha_inicio',toolbar:{}};
}])

/* *****************************************************
 * UNIDAD DE PREVENCIÓN E INGENIERÍA DEL FUEGO
 * *****************************************************
 */
 /*
  * INSPECCIONES
  */
 .controller('inspectionsCtrl',['$scope','myResource',function($scope,myResource){
 	// PARÁMETROS PARA LISTADO DE REGISTROS
 	$scope.tbParams={path:'Prevention',parent:'Inspecciones',tb:'getInspections',order:'-inspeccion_fecha_inspeccion',toolbar:{}};
 }])
 /*
  * PRORROGAS
  */
 .controller('extensionsCtrl',['$scope','myResource',function($scope,myResource){
 	// PARÁMETROS PARA LISTADO DE REGISTROS
 	$scope.tbParams={path:'Prevention',parent:'Prorrogas',tb:'getExtensions',order:'-prorroga_desde',toolbar:{}};
 }])

/* *****************************************************
 * APROBACIÓN DE PLANOS
 * *****************************************************
 */
/*
 * VISTO BUENO DE PLANOS
 */
.controller('vbpCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Vbp',parent:'Vbp',tb:'getVbp',order:'vbp_proyecto',toolbar:{}};
}])
/*
 * MODIFICACIÓN DE PLANOS
 */
.controller('modificationsCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Vbp',parent:'Modificaciones',tb:'getModifications',order:'vbp_proyecto',toolbar:{}};
}])
/*
 * OCUPACIÓN Y HABITABILIDAD
 */
.controller('habitabilityCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Vbp',parent:'Habitabilidad',tb:'getHabitability',order:'vbp_proyecto',toolbar:{}};
}])


/* *****************************************************
 * FACTIBILIDAD DE GLP
 * *****************************************************
 */
/*
 * FACTIBILIDAD DE GLP
 */
.controller('feasibilityCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Glp',parent:'Factibilidadglp',tb:'getFeasibility',order:'factibilidad_proyecto',toolbar:{}};
}])
/*
 * DEFINITIVO DE GLP
 */
.controller('definitiveCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Glp',parent:'Definitivoglp',tb:'getDefinitive',order:'factibilidad_proyecto',toolbar:{}};
}])


/*
 * TEMAS DE CAPACITACIÓN
 */
.controller('topicsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Temario',tb:'temario',order:'tema_nombre',toolbar:{}};
}])
/*
 * DETALLE DE TEMAS DE CAPACITACIÓN
 */
.controller('detailTopicsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.dataEntity=entity.data;
}])
/*
/*
 * CAPACITACIONES
 */
.controller('trainingsCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Capacitaciones',parent:'Training',tb:'getTrainings',order:'-capacitacion_fecha',toolbar:{}};
	// IMPRIMIR CERTIFICADOS DE ASISTENCIA
	$scope.downloadCertificate=function(capacitacionId){window.open(reportsURI+'training/?id='+capacitacionId,'_blank');};
}])
/*
 * DETALLE DE CAPACITACIÓN
 */
.controller('detailTrainingCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE ENTIDAD
	$scope.entity=entity.data;
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data.entity;
	// MODAL PADRE
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	// RELOAD CAPACITACIÓN
	$scope.reloadTraining=function(){
		myResource.sendData('training/REQUEST').save({id:$scope.entity.capacitacion_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('main');}).$promise;
	};
	// PARÁMETROS PARA DETALLES
	$scope.custom={fk_tabla:'fk_capacitacion_id',fk_capacitacion_id:$scope.entity.capacitacion_id};
	// RECORDS
	$scope.historyParams={order:'-capacitacion_registro',
						  custom:{entity:'training',id:$scope.entity.capacitacion_id}};
	// IMPRIMIR CERTIFICADO
	$scope.downloadCertificateSingle=function(participanteId,capacitacionId){
		window.open(reportsURI+'getTrainings/?participanteId='+participanteId+'&id='+capacitacionId,'_blank');
	};
	// IMPRIMIR CERTIFICADO
	$scope.downloadCertificate=function(capacitacionId){
		window.open(reportsURI+'training/?id='+capacitacionId,'_blank');
	};
	// IMPRIMIR SOLICITUD
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getTrainings/?withDetail&id='+id,'_blank');};
	// BUSCADOR
	$scope.filter={options:{debounce:500}};
	$scope.filterParent={};
	$scope.removeFilter=function(){
	    $scope.filter.show=false;
	    $scope.filterParent.filter='';
	    if($scope.filter.form.$dirty)$scope.filter.form.$setPristine();
	};
	$scope.openSearch=function(){
		$scope.filter.show=true;
		myResource.focus('inputSearch');
	};
}])

/*
 * STANDS
 */
.controller('standsCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Capacitaciones',parent:'Stands',tb:'getStands',order:'stand_fecha',toolbar:{}};
}])
/*
 * DETALLE DE CAPACITACIÓN
 */
.controller('detailStandsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE ENTIDAD
	$scope.entity=entity.data;
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data.entity;
	// MODAL PADRE
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	// RELOAD CAPACITACIÓN
	$scope.reloadEntity=function(){
		myResource.sendData('stands/REQUEST').save({id:$scope.entity.stand_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('main');}).$promise;
	};
	// PARÁMETROS PARA DETALLES
	$scope.custom={fk_tabla:'fk_stand_id',fk_stand_id:$scope.entity.stand_id};
	// RECORDS
	$scope.historyParams={order:'-stand_registro',
						  custom:{entity:'stands',id:$scope.entity.stand_id}};
	// IMPRIMIR SOLICITUD
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getStands/?withDetail&id='+id,'_blank');};
}])
/*
 * VISITAS
 */
.controller('visitsCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Capacitaciones',parent:'Visitas',tb:'getVisits',order:'-visita_fecha',toolbar:{}};
}])
/*
 * DETALLE DE VISITAS
 */
.controller('detailVisitsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE ENTIDAD
	$scope.entity=entity.data;
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data.entity;
	// MODAL PADRE
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	// RELOAD CAPACITACIÓN
	$scope.reloadEntity=function(){
		myResource.sendData('visitas/REQUEST').save({id:$scope.entity.visita_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('main.visits');}).$promise;
	};
	// PARÁMETROS PARA DETALLES
	$scope.custom={fk_tabla:'fk_visita_id',fk_visita_id:$scope.entity.visita_id};
	// RECORDS
	$scope.historyParams={order:'-visita_registro',
						  custom:{entity:'visitas',id:$scope.entity.visita_id}};
	// IMPRIMIR SOLICITUD
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getVisits/?withDetail&id='+id,'_blank');};
}])
/*
 * SIMULACROS
 */
.controller('simulationsCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS PARA LISTADO DE REGISTROS
	$scope.tbParams={path:'Capacitaciones',parent:'Simulacros',tb:'getSimulations',order:'-simulacro_fecha',toolbar:{}};
}])
/*
 * DETALLE DE SIMULACROS
 */
.controller('detailSimulationsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE ENTIDAD
	$scope.entity=entity.data;
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data;
	// MODAL PADRE
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	// RELOAD SIMULACROS
	$scope.reloadSimulation=function(){
		myResource.sendData('simulacros/REQUEST').save({id:$scope.entity.simulacro_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('main');}).$promise;
	};
	// PARÁMETROS PARA DETALLES
	$scope.custom={fk_tabla:'fk_simulacro_id',fk_simulacro_id:$scope.entity.simulacro_id};
	// RECORDS
	$scope.historyParams={order:'-simulacro_registro',
						  custom:{entity:'simulacros',id:$scope.entity.simulacro_id}};
	// IMPRIMIR SOLICITUD
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getSimulations/?withDetail&id='+id,'_blank');};
	// BUSCADOR
	$scope.filter={options:{debounce:500}};
	$scope.filterParent={};
	$scope.removeFilter=function(){
	    $scope.filter.show=false;
	    $scope.filterParent.filter='';
	    if($scope.filter.form.$dirty)$scope.filter.form.$setPristine();
	};
	$scope.openSearch=function(){
		$scope.filter.show=true;
		myResource.focus('inputSearch');
	};
}])
;