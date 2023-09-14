'use strict';
/**
 * CONFIGURACIÓN DEL MÓDULO AngularJS
 */
var app=angular.module('prevencionApp',[
	'ngAnimate',
	'ngAria',
	'ngResource',
	'ngSanitize',
	'ngCookies',
	'ui.router',
	'ngMaterial',
	'ngMessages',
	'md.data.table',
	'materialCalendar',
	'moment-picker',
	'ui.select',
	'checklist-model',
	'angular-growl',
	'ngCkeditor',
	'oitozero.ngSweetAlert',
	'angular-loading-bar',
	'pascalprecht.translate',
	'angular-media-preview',
	'angular-img-cropper',
	'angularUtils.directives.uiBreadcrumbs',
	'leaflet-directive'
]);
app
.config(['$locationProvider','$urlRouterProvider','$stateProvider','$httpProvider','$mdThemingProvider','growlProvider','$translateProvider','momentPickerProvider','$compileProvider',
    function($locationProvider,$urlRouterProvider,$stateProvider,$httpProvider,$mdThemingProvider,growlProvider,$translateProvider,momentPickerProvider,$compileProvider){
	
	// When is false .scope() dont work causing the select to crash
	$compileProvider.debugInfoEnabled(true);
	
	// Notificaciones - growl
	growlProvider.onlyUniqueMessages(false).globalReversedOrder(true).globalTimeToLive(5000);
	
	// Angular Moment Picker
	momentPickerProvider.options({locale:'es',format:'YYYY-MM-DD HH:mm',today:true,startView:'month'});
	
	// Translate
	$translateProvider.translations('es',appLabels.es).translations('en',appLabels.en).determinePreferredLanguage(function(){return 'es';});
	
	// Extend the red theme with a few different colors
	var geoBlue=$mdThemingProvider.extendPalette('red',{'500':'00a65a'});
    var geoTeal=$mdThemingProvider.extendPalette('teal',{'500':'00695C'});
    $mdThemingProvider.definePalette('geoBlue',geoBlue);
    $mdThemingProvider.definePalette('geoTeal',geoTeal);

	// CUSTOM THEME - ANGULAR MATERIAL
	$mdThemingProvider.theme('default').primaryPalette('geoBlue').accentPalette('red');
	
	// themes are still defined in config, but the css is not generated
	$mdThemingProvider.theme('infoTheme').primaryPalette('green').accentPalette('orange').warnPalette('red');
	
	// OBTENER ERRORES EN ENVÍO DE DATOS
	$httpProvider.interceptors.push(function($q){
		return {
			'requestError':function(rejection){console.log('rejection:'+rejection)},
	    	'responseError':function(response){
				var json={estado:false};
				if(response.status==400) json.mensaje='Bad request::Custom';
				if(response.status==401) json.mensaje='401::Custom';
				if(response.status==500) json.mensaje='500 Error en el servidor::Custom';
				if(response.status==404) json.mensaje='404 No found::Custom';
				response.customError=json;
				return $q.reject(response);
		    }
		  };
	});
	$httpProvider.defaults.headers.common["X-Requested-With"]='XMLHttpRequest';
	$httpProvider.defaults.useXDomain=true;
    delete $httpProvider.defaults.headers.common['X-Requested-With'];
	
    // Definición de rutas permitidas en el sistema
	$urlRouterProvider.otherwise('/');
	$stateProvider
	// DECLARACION DE MOÓDULOS PRINCIPALES
	.state('session',{
		url:'/',
		templateUrl:pathMain+'session.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_DASHBOARD' }
	})
	.state('info',{
		url:'/info/',
		templateUrl:pathMain+'session.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
		data: { displayName:'LB_INFORMATION' }
	})
	.state('entity',{
		url:'/entity/',
		templateUrl:pathMain+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_DATA_OF_MY_RUC' }
	})
	.state('permits',{
		url:'/permits/',
		templateUrl:pathMain+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_PERMITS' }
	})
	.state('prevention',{
		url:'/prevention/',
		templateUrl:pathMain+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_PREVENTION' }
	})
	.state('vbp',{
		url:'/building/',
		templateUrl:pathMain+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
		data: { displayName:'LB_CONSTRUCTION_PERMITS' }
	})
	.state('glp',{
		url:'/lpg/',
		templateUrl:pathMain+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
		data: { displayName:'LB_USE_LPG' }
	})
	.state('training',{
		url:'/trainings/',
		templateUrl:pathMain+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
		data: { displayName:'LB_CITIZENSHIP_TRAINING' }
	})
	
	// MODULOS - SESSION
	.state('session.e401',{
		url:'/e401',
		templateUrl:pathMain+'e401.html'
	})
	.state('info.terms',{
		url:'terms/',
		templateUrl:pathModule+'profile/terms.html',
		controller:'termsCtrl',
		resolve:{
			data:['myResource','$state',function(myResource,$state){
				return myResource.requestData('permits/entities/detail/entityId').save({data:'account',entityId:myResource.cookies.get('userID')},function(json){return json;}).$promise;
			}]
		},
        data: { displayName:'LB_TERMS_AND_CONDITIONS' }
	})
	
	// MODULOS - DATOS DEL RUC
	.state('entity.profile',{
		url:'profile/',
		templateUrl:pathModule+'profile/profile.html',
		controller:'profileCtrl',
		resolve:{
			data:['myResource','$state',function(myResource){
				return myResource.requestData('permits/entities/detail/entityId').save({data:'account',entityId:myResource.cookies.get('userID')},function(json){return json;}).$promise;
			}],
			locals:['myResource','$state',function(myResource){
				return myResource.requestData('permits/locals/list/entityId').save({entityId:myResource.cookies.get('userID')},function(json){return json;}).$promise;
			}]
		},
        data: { displayName:'LB_MY_DATA' }
	})
	.state('entity.staff',{
		url:'staff/',
		templateUrl:pathModule+'profile/staff.html',
		controller:'staffCtrl',
        data: { displayName:'LB_STAFF_LIST' }
	})
	
	/*
	 * MODULOS - PERMISOS DE FUNCIONAMIENTO
	 */
	.state('permits.economicActivities',{
		url:'economicActivities/',
		templateUrl:pathModule+'permits/economicActivities.html',
		controller:'economicActivitiesCtrl',
        data: { displayName:'LB_COMERTIAL_ACTIVITY' }
	})
	.state('permits.detailEconomicActivities',{
		url:'economicActivities/:localId/',
		templateUrl:pathModule+'permits/detailEconomicActivities.html',
		controller:'detailEconomicActivitiesCtrl',
		resolve:{
			entity:['myResource','$stateParams','$state',function(myResource,$stateParams,$state){
				return myResource.requestData('permits/locals/localId').save($stateParams,function(json){
					if(json.estado===true) return json;
					myResource.myDialog.showNotify(json);
					$state.go('permits.economicActivities');
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}],
			plans:['myResource','$stateParams','$state',function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/plans/list/localId').save($stateParams,function(json){
					return (json.estado===true)?json:{};
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}],
			inspections:['myResource','$stateParams','$state',function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/inspections/list/localId').save($stateParams,function(json){
					return (json.estado===true)?json:{};
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}],
			permits:['myResource','$stateParams','$state',function(myResource,$stateParams,$state){
				return myResource.requestData('permits/permitsByLocal').save($stateParams,function(json){
					return (json.estado===true)?json:{};
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}]
		},
		data: { displayName:'LB_ACTIVITY_DETAIL' }
	})
	/*
	 * PROTOCOLO DE BIO SEGURIDAD
	 */
	.state('permits.covid19',{
        url:'economicActivities/:id/biosecurity/covid19/',
        templateUrl:pathModule+'biosecurity/covid19.html',
		controller:'biosecurity.covid19Ctrl',
		resolve:{
			resources:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/biosecurity/covid19/forms').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
		data: { displayName:'LB_SELFINSPECTION_STEP1' }
    })
	/*
	 * AUTOINSPECCION
	 */
	.state('permits.selfInspection',{
		url:'economicActivities/:localId/',
		templateUrl:pathModule+'permits/selfInspection.html',
		controller:'selfInspectionCtrl',
		resolve:{
			entity:['myResource','$stateParams',function(myResource,$stateParams){
				return myResource.requestData('permits/locals/localId').save($stateParams,function(json){
					if(!json.estado)myResource.state.go('permits.economicActivities');
					return json;
				},function(error){myResource.state.go('permits.economicActivities');}).$promise;
			}],
			data:['myResource',function(myResource){
				return myResource.requestData('permits/entities/detail/entityId').save({data:'account',entityId:myResource.cookies.get('userID')},function(json){return json;}).$promise;
			}]
		},
		data: { displayName:'LB_SELFINSPECTION' }
	})
	.state('permits.selfInspection.step1',{
        url:'step1/account/',
        templateUrl:pathInc+'autoInspeccion/selfInspection.step1.html',
		controller:'selfInspection.step1Ctrl',
		data: { displayName:'LB_SELFINSPECTION_STEP1' }
    })
	.state('permits.selfInspection.step2',{
        url:'step2/information/',
        templateUrl:pathInc+'autoInspeccion/selfInspection.step2.html',
		controller:'selfInspection.step2Ctrl',
		resolve:{
			entity:['myResource','$stateParams',function(myResource,$stateParams){
				return myResource.requestData('permits/locals/localId').save($stateParams,function(json){ return json; }).$promise;
			}],
			geo:function(myResource,$stateParams,$state){
				return myResource.requestData('resources/geojson/entityId').save({entity:'locales',entityId:$stateParams.localId},function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.economicActivities');
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}
		},
		data: { displayName:'LB_SELFINSPECTION_STEP2' }
    })
	.state('permits.selfInspection.step3',{
        url:'step3/requirements/',
        templateUrl:pathInc+'autoInspeccion/selfInspection.step3.html',
		controller:'selfInspection.step3Ctrl',
		resolve:{
			form:['myResource','$stateParams','$state',function(myResource,$stateParams,$state){
				return myResource.sendData('getForm').save($stateParams,function(json){
					if(!json.estado){
						myResource.myDialog.showNotify(json);
						$state.go('permits.selfInspection.step2',$stateParams);
					}	return json.data;
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}],
			inspectionForm:function(myResource,$stateParams,$state){
				$stateParams.type='selfInspection';
				return myResource.sendData('inspecciones/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}
		},
		data: { displayName:'LB_SELFINSPECTION_STEP3' }
    })
    /*
     * AUTOPROTECCION
     */
	.state('permits.selfProtectionPlans',{
		url:'selfProtectionPlans/',
		templateUrl:pathModule+'permits/selfProtectionPlans.html',
		controller:'selfProtectionPlansCtrl',
        data: { displayName:'LB_EMERGENCY_PLANS' }
	})
	.state('permits.selfProtection',{
		url:'selfProtection/economicActivities/:localId/',
		templateUrl:pathModule+'permits/selfProtection.html',
		controller:'selfProtectionCtrl',
		resolve:{
			local:function(myResource,$stateParams,$state){
				return myResource.requestData('permits/locals/localId').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
        data: { displayName:'LB_EMERGENCY_PLANS' }
	})
	.state('permits.selfProtection.step1',{
		url:'edit/step1/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step1.html',
		controller:'selfProtection.step1Ctrl',
		resolve:{
			geo:function(myResource,$stateParams,$state){
				return myResource.requestData('resources/geojson/entityId').save({entity:'locales',entityId:$stateParams.localId},function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
        data: { displayName:'LB_SELFPROTECTION_STEP1' }
	})
	.state('permits.selfProtection.step2',{
		url:'plans/step2/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step2.html',
		controller:'selfProtection.step2Ctrl',
		resolve:{
			plans:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/plans/list/localId').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
        data: { displayName:'LB_SELFPROTECTION_STEP2' }
	})
	.state('permits.editSelfProtection',{
		url:'selfProtection/plans/:planId/',
		templateUrl:pathModule+'permits/editSelfProtection.html',
		controller:'editSelfProtectionCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/plans/planById').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		}
	})
	.state('permits.editSelfProtection.stepSingle',{
		url:'plan/:planId/step/complete/',
		templateUrl:pathInc+'autoProteccion/selfProtection.stepSingle.html',
		controller:'selfProtection.stepSingleCtrl',
        data: { displayName:'LB_SELFPROTECTION_STEP10' }
	})
	.state('permits.editSelfProtection.stepSingleAnnexxes',{
		url:'plan/:planId/step/annexxes/complete/',
		templateUrl:pathInc+'autoProteccion/selfProtection.stepSingle.annexxes.html',
		controller:'selfProtection.stepSingleCtrl',
		data: { displayName:'LB_SELFPROTECTION_STEP10' }
	})
	.state('permits.editSelfProtection.step3',{
		url:'step3/factors/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step3.html',
		controller:'selfProtection.step3Ctrl',
		resolve:{
			resources:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/plans/selfProtectionFactorsByPlan').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
        data: { displayName:'LB_SELFPROTECTION_STEP3' }
	})
	.state('permits.editSelfProtection.step4',{
		url:'plan/:planId/step4/meseri/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step4.html',
		controller:'selfProtection.step4Ctrl',
		resolve:{
			resources:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/plans/selfProtectionMeseriByPlan').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
        data: { displayName:'LB_SELFPROTECTION_STEP4' }
	})
	.state('permits.editSelfProtection.step5',{
		url:'plan/:planId/step5/prevention/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step5.html',
		controller:'selfProtection.step5Ctrl',
		resolve:{
			resources:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/plans/selfProtectionPreventionByPlan').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
        data: { displayName:'LB_SELFPROTECTION_STEP5' }
	})
	.state('permits.editSelfProtection.step6',{
		url:'plan/:planId/step6/maintenance/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step6.html',
		controller:'selfProtection.step6Ctrl',
		resolve:{
			resources:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/plans/selfProtectionMaintenanceByPlan').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('permits.selfProtectionPlans');
				},function(error){$state.go('permits.selfProtectionPlans');}).$promise;
			}
		},
        data: { displayName:'LB_SELFPROTECTION_STEP6' }
	})
	.state('permits.editSelfProtection.step7',{
		url:'plan/:planId/step7/protocol/alert/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step7.html',
		controller:'selfProtection.step7Ctrl',
        data: { displayName:'LB_SELFPROTECTION_STEP7' }
	})
	.state('permits.editSelfProtection.step8',{
		url:'plan/:planId/step8/protocol/action/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step8.html',
		controller:'selfProtection.step8Ctrl',
        data: { displayName:'LB_SELFPROTECTION_STEP8' }
	})
	.state('permits.editSelfProtection.step9',{
		url:'plan/:planId/step9/evacuation/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step9.html',
		controller:'selfProtection.step9Ctrl',
        data: { displayName:'LB_SELFPROTECTION_STEP9' }
	})
	.state('permits.editSelfProtection.step10',{
		url:'plan/:planId/step10/responsible/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step10.html',
		controller:'selfProtection.step10Ctrl',
        data: { displayName:'LB_SELFPROTECTION_STEP10' }
	})
	.state('permits.editSelfProtection.step11',{
		url:'plan/:planId/step11/emergency/',
		templateUrl:pathInc+'autoProteccion/selfProtection.step11.html',
		controller:'selfProtection.step11Ctrl',
        data: { displayName:'LB_SELFPROTECTION_STEP11' }
	})
	/*
	 * PERMISOS OCASIONALES
	 */
	.state('permits.occasionals',{
		url:'occasionals/',
		templateUrl:pathModule+'permits/occasionals.html',
		controller:'occasionalsCtrl',
		data: { displayName:'LB_OCCASIONALS' }
	})
	
	// MODULOS - UNIDAD DE PREVENCIÓN E INGENIERÍA DEL FUEGO
	.state('prevention.inspections',{
		url:'inspections/',
		templateUrl:pathModule+'prevention/inspections.html',
		controller:'inspectionsCtrl',
		data: { displayName:'LB_INSPECTIONS' }
	})
	.state('prevention.extensions',{
		url:'extensions/',
		templateUrl:pathModule+'prevention/extensions.html',
		controller:'extensionsCtrl',
		data: { displayName:'LB_EXTENSIONS' }
	})
	
	// MODULOS - PERMISOS DE CONSTRUCCIÓN
	.state('vbp.plans',{
		url:'vbp/',
		templateUrl:pathModule+'building/vbp.html',
		controller:'vbpCtrl',
		data: { displayName:'LB_APPROVAL_OF_PLANS' }
	})
	.state('vbp.modifications',{
		url:'modifications/',
		templateUrl:pathModule+'building/modifications.html',
		controller:'modificationsCtrl',
		data: { displayName:'LB_MODIFICATION_PLANS' }
	})
	.state('vbp.habitability',{
		url:'habitability/',
		templateUrl:pathModule+'building/habitability.html',
		controller:'habitabilityCtrl',
		data: { displayName:'LB_HABITABILITY' }
	})
	// MODULOS - USO DE GLP
	.state('glp.glpTransport',{
		url:'glpTransport/',
		templateUrl:pathModule+'glp/glpTransport.html',
		controller:'glpTransportCtrl',
        data: { displayName:'LB_GLP_TRANSPORT' }
	})
	.state('glp.feasibility',{
		url:'feasibility/',
		templateUrl:pathModule+'glp/feasibility.html',
		controller:'feasibilityCtrl',
		data: { displayName:'LB_GLP_FEASIBILITY' }
	})
	.state('glp.definitive',{
		url:'definitive/',
		templateUrl:pathModule+'glp/definitive.html',
		controller:'definitiveCtrl',
		data: { displayName:'LB_GLP_DEFINITIVE' }
	})
	// MODULOS - CAPACITACIONES CIUDADANAS
	.state('training.calendar',{
		url:'calendar/:entity/',
		templateUrl:pathModule+'trainings/calendar.html',
		controller:'training.calendarCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.getData($stateParams.entity).get(function(json){
					return json
				}, function(error){$state.go('main');}).$promise;
			}
		},
		data: { displayName:'LB_CALENDAR' }
	})
	.state('training.trainings',{
		url:'trainings/',
		templateUrl:pathModule+'trainings/trainings.html',
		controller:'trainingsCtrl',
		data: { displayName:'LB_TRAININGS' }
	})
	.state('training.detailTraining',{
		url:'trainings/:id/',
		templateUrl:pathModule+'trainings/detailTraining.html',
		controller:'detailTrainingCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('training/REQUEST').save($stateParams,function(json){
					return json
				}, function(error){$state.go('training.trainings');}).$promise;
			}
		},
		data: { displayName:'LB_TRAINING_DETAIL' }
	})
	.state('training.stands',{
		url:'stands/',
		templateUrl:pathModule+'trainings/stands.html',
		controller:'standsCtrl',
		data: { displayName:'LB_STANDS' }
	})
	.state('training.visits',{
		url:'visits/',
		templateUrl:pathModule+'trainings/visits.html',
		controller:'visitsCtrl',
		data: { displayName:'LB_VISITS' }
	})
	.state('training.simulations',{
		url:'simulations/',
		templateUrl:pathModule+'trainings/simulations.html',
		controller:'simulationsCtrl',
		data: { displayName:'LB_SIMULATIONS' }
	})
	.state('training.newTraining',{
		url:'calendar/training/:date/',
		templateUrl:pathModule+'trainings/newTraining.html',
		controller:'newTrainingCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('training/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.showNotify(json);
					myResource.state.go('training.calendar',$stateParams);
				}, function(error){myResource.state.go('training.calendar',$stateParams);}).$promise;
			}
		},
		data: { displayName:'LB_TRAININGS' }
	})
	.state('training.newStand',{
		url:'calendar/stands/:date/',
		templateUrl:pathModule+'trainings/newStand.html',
		controller:'newStandCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('stands/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.showNotify(json);
					myResource.state.go('training.calendar',$stateParams);
				}, function(error){myResource.state.go('training.calendar',$stateParams);}).$promise;
			}
		},
		data: { displayName:'LB_STANDS' }
	})
	.state('training.newVisit',{
		url:'calendar/visit/:date/',
		templateUrl:pathModule+'trainings/newVisit.html',
		controller:'newVisitCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('visitas/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.showNotify(json);
					myResource.state.go('training.calendar',$stateParams);
				}, function(error){myResource.state.go('training.calendar',$stateParams);}).$promise;
			}
		},
		data: { displayName:'LB_VISITS' }
	})
	.state('training.newSimulation',{
		url:'calendar/simulation/:date/',
		templateUrl:pathModule+'trainings/newSimulation.html',
		controller:'newSimulationCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('simulacros/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.showNotify(json);
					myResource.state.go('training.calendar',$stateParams);
				}, function(error){myResource.state.go('training.calendar',$stateParams);}).$promise;
			}
		},
		data: { displayName:'LB_SIMULATIONS' }
	})
	;
	$locationProvider.html5Mode(true);
	
}])
.run(['$rootScope','$cookies','$translate','$state','$stateParams','authorization','principal',function($rootScope,$cookies,$translate,$state,$stateParams,authorization) {
	
	// SOLICITAR LOGIN O PERMISOS PARA CAMBIO DE ESTADOS
	$rootScope.changeLanguage=function(lang){
		$cookies.put('lang',lang);
		$translate.use(lang);
	}; 
	
	// VALIDAR CAMBIO DE RUTAS F
	$rootScope.$on('$stateChangeStart',function(event,toState,toStateParams){
		
		// track the state the user wants to go to; authorization service needs this
        $rootScope.toState=toState;
        $rootScope.toStateParams=toStateParams;
        
        // VALIDAR SESION
        if(typeof $cookies.get('userID')==='undefined' || $cookies.get('userID')===null){
			// ELIMINAR AUTENTICACION
        	authorization.principal.authenticate(null);
        }
        
        // if the principal is resolved, do an authorization check immediately. otherwise, it'll be done when the state it resolved.
        if(authorization.principal.isIdentityResolved()) authorization.authorize();
        
	});
	
}])
;