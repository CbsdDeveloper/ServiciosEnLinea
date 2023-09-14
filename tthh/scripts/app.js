setTimeout(function(){$('.loader').fadeOut();},1500);
'use strict';
/**
 * CONFIGURACIÓN DEL MÓDULO AngularJS
 */
var app=angular.module('tthhApp',[
	'ngAnimate',
	'ngAria',
	'ngResource',
	'ngSanitize',
	'ngCookies',
	'ui.router',
	'ngMaterial',
	'materialCalendar',
	'moment-picker',
	'ui.select',
	'checklist-model',
	'md.data.table',
	'angular-growl',
	'oitozero.ngSweetAlert',
	'angular-loading-bar',
	'angular-media-preview',
	'angular-img-cropper',
	'pascalprecht.translate',
	'leaflet-directive',
	'angularUtils.directives.uiBreadcrumbs'
]);
app
.config(['$locationProvider','$urlRouterProvider','$stateProvider','$httpProvider','$mdThemingProvider','growlProvider','$translateProvider','momentPickerProvider','$compileProvider',
    function($locationProvider,$urlRouterProvider,$stateProvider,$httpProvider,$mdThemingProvider,growlProvider,$translateProvider,momentPickerProvider,$compileProvider){
	// When is false .scope() dont work causing the select to crash
	$compileProvider.debugInfoEnabled(true);
	// $compileProvider.aHrefSanitizationWhitelist(/^\s*(|blob|):/);
	// Notificaciones - growl
	growlProvider.onlyUniqueMessages(false).globalReversedOrder(true).globalTimeToLive(5000);
	// Angular Moment Picker
	momentPickerProvider.options({locale:'es',format:'YYYY-MM-DD HH:mm',today:true,startView:'month'});
	// Translate
	$translateProvider.translations('es',appLabels.es).translations('en',appLabels.en).determinePreferredLanguage(function(){return 'es';});
	// Extend the red theme with a few different colors
    var geoBlue=$mdThemingProvider.extendPalette('red',{'500':'dd4b39'});
    var geoTeal=$mdThemingProvider.extendPalette('teal',{'500':'00695C'});
    $mdThemingProvider.definePalette('geoBlue',geoBlue);
    $mdThemingProvider.definePalette('geoTeal',geoTeal);
    // CUSTOM THEME - ANGULAR MATERIAL
	$mdThemingProvider.theme('default').primaryPalette('geoBlue').accentPalette('red');
	// OBTENER ERRORES EN ENVÍO DE DATOS
	$httpProvider.interceptors.push(['$q',function($q){
		return {
			'requestError':function(rejection){console.log('rejection:'+rejection)},
	    	'responseError':function(response){
				var json={estado:false};
				if(response.status == 400) json.mensaje='Bad request::Custom';
				if(response.status == 401) json.mensaje='401::Custom';
				if(response.status == 500) json.mensaje='500 Error en el servidor::Custom';
				if(response.status == 404) json.mensaje='404 No found::Custom';
				response.customError=json;
				return $q.reject(response);
		    }
		  };
	}]);
	$httpProvider.defaults.headers.common["X-Requested-With"]='XMLHttpRequest';
	$httpProvider.defaults.useXDomain=true;
    delete $httpProvider.defaults.headers.common['X-Requested-With'];
	// DEFINIR RUTAS PREVIAS A UTILIZAR
	$urlRouterProvider.otherwise('/e404');
	$stateProvider
	.state('session',{
		url:'/',
		templateUrl:pathIncApp+'main/session.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_DASHBOARD' }
	})
	.state('main',{
		url:'/',
		templateUrl:pathIncApp+'main/tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_SUPPORT' }
	})
	.state('profile',{
		url:'/profile/',
		templateUrl:pathIncApp+'main/tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_MY_PROFILE' }
	})
	.state('leadership',{
		url:'/leadership',
		templateUrl:pathIncApp+'main/tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_MY_LEADERSHIP' }
	})
	.state('tthh',{
		url:'/tthh/',
		templateUrl:pathIncApp+'main/tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_TTHH_MANAGEMENT' }
	})
	.state('administrative',{
		url:'/administrative/',
		templateUrl:pathIncApp+'main/tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_LOGISTICS_MANAGEMENT' }
	})
	.state('financial',{
		url:'/financial/',
		templateUrl:pathIncApp+'main/tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_FINANCIAL_MANAGEMENT' }
	})
	
	.state('main.e404',{
		url:'e404',
		templateUrl:pathIncApp+'main/e404.html',
        data: { displayName:'LB_PAGE_NOT_FOUND' }
	})
	.state('main.e403',{
		url:'e403',
		templateUrl:pathIncApp+'main/e403.html',
        data: { displayName:'LB_UNAUTHORIZED_ACCESS' }
	})
	
	/*
	 * PERFIL PERSONAL
	 */
	.state('profile.profile',{
		url:'data',
		templateUrl:pathProfile+'profile.html',
		controller:'profile.profileCtrl',
        data: { displayName:'LB_PERSONAL_INFORMATION' }
	})
	.state('profile.titles',{
		url:'titles',
		templateUrl:pathProfile+'titles.html',
		controller:'profile.academicTitlesCtrl',
        data: { displayName:'LB_ACADEMIC_TRAINING' }
	})
	.state('profile.awards',{
		url:'awards',
		templateUrl:pathProfile+'awards.html',
		controller:'profile.awardsCtrl',
        data: { displayName:'LB_AWARDS' }
	})
	.state('profile.trainings',{
		url:'trainings',
		templateUrl:pathProfile+'trainings.html',
		controller:'profile.trainingsCtrl',
        data: { displayName:'LB_COURSES_COMPLETED' }
	})
	.state('profile.affidavits',{
		url:'affidavits',
		templateUrl:pathProfile+'affidavits.html',
		controller:'profile.affidavitsCtrl',
        data: { displayName:'LB_AFFIDAVITS' }
	})
	.state('profile.familiars',{
		url:'familiars',
		templateUrl:pathProfile+'familiars.html',
		controller:'profile.familiarsCtrl',
        data: { displayName:'LB_FAMILY_RESPONSIBILITIES' }
	})
	.state('profile.employments',{
		url:'employments',
		templateUrl:pathProfile+'employments.html',
		controller:'profile.employmentsCtrl',
        data: { displayName:'LB_EMPLOYMENT_HISTORY' }
	})
	.state('profile.driverslicenses',{
		url:'driverslicenses',
		templateUrl:pathProfile+'driverslicenses.html',
		controller:'profile.driverslicensesCtrl',
        data: { displayName:'LB_DRIVERS_LICENSES' }
	})
	.state('profile.banks',{
		url:'banks',
		templateUrl:pathProfile+'banks.html',
		controller:'profile.banksCtrl',
        data: { displayName:'LB_BANK_ACCOUNTS' }
	})
	
	/*
	 * TALENTO HUMANO
	 */
	.state('tthh.calendar',{
		url:'calendar',
		templateUrl:pathTthh+'calendar.html',
		controller:'tthh.calendarCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.sendData('schedule/REQUEST').save({type:'tthh'},function(json){
					return json
				},function(error){$state.go('main');}).$promise;
			}
		},
        data: { displayName:'LB_CALENDAR' }
	})
	.state('tthh.performance',{
		url:'performance',
		templateUrl:pathTthh+'performance.html',
		controller:'tthh.performanceCtrl',
        data: { displayName:'LB_PERFORMANCE' }
	})
	.state('tthh.permissions',{
		url:'permissions',
		templateUrl:pathTthh+'permissions.html',
		controller:'tthh.permissionsCtrl',
        data: { displayName:'LB_PERMISSIONS' }
	})
	.state('tthh.newPermission',{
		url:'new/permission',
		templateUrl:pathTthh+'newPermission.html',
		controller:'tthh.newPermissionCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('personal_permisos/REQUEST').save({account:'tthh'},function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.showNotify(json);
					myResource.state.go('tthh.permissions');
				},function(error){$state.go('tthh.permissions');}).$promise;
			}
		},
        data: { displayName:'LB_NEW_APPLICATION_FOR_PERMIT' }
	})
	.state('tthh.vacations',{
		url:'vacations',
		templateUrl:pathTthh+'vacations.html',
		controller:'tthh.vacationsCtrl',
        data: { displayName:'LB_VACATIONS' }
	})
	.state('tthh.newVacation',{
		url:'new/vacation',
		templateUrl:pathTthh+'newVacation.html',
		controller:'tthh.newVacationCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('personal_vacaciones/REQUEST').save({account:'tthh'},function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.showNotify(json);
					myResource.state.go('tthh.vacations');
				},function(error){$state.go('tthh.vacations');}).$promise;
			}
		},
        data: { displayName:'LB_NEW_VACATION_REQUEST' }
	})
	.state('tthh.sanctions',{
		url:'sanctions',
		templateUrl:pathTthh+'sanctions.html',
		controller:'tthh.sanctionsCtrl',
        data: { displayName:'LB_SANCTIONS' }
	})
	.state('tthh.medicalcertificates',{
		url:'medicalcertificates',
		templateUrl:pathTthh+'medicalcertificates.html',
		controller:'tthh.medicalcertificatesCtrl',
        data: { displayName:'MENU_MEDICALREST' }
	})
	.state('tthh.roadmap',{
		url:'roadmap',
		templateUrl:pathTthh+'roadmap.html',
		controller:'tthh.roadmapCtrl',
        data: { displayName:'LB_ROADMAP' }
	})
	.state('tthh.dailyactivities',{
		url:'dailyactivities',
		templateUrl:pathTthh+'dailyactivities.html',
		controller:'tthh.dailyActivitiesCtrl',
        data: { displayName:'LB_DAILY_ACTIVITIES' }
	})
	.state('tthh.psychosocialEvaluations',{
		url:'psychosocial/evaluations',
		templateUrl:pathTthh+'psychosocialEvaluations.html',
		controller:'tthh.psychosocialEvaluationsCtrl',
        data: { displayName:'MENU_PSYCHOSOCIAL_RISK' }
	})
	.state('tthh.psychosocialTest',{
		url:'psychosocial/evaluations/:evaluationId/test/:staffId/session',
		templateUrl:pathTthh+'psychosocialTest.html',
		controller:'tthh.psychosocialTestCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/psychosocial/questionnaire/questions').save($stateParams,function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.swalAlert(json);
					$state.go('tthh.psychosocialEvaluations');
				}, function(error){$state.go('tthh.psychosocialEvaluations');}).$promise;
			}
		},
        data: { displayName:'MENU_PSYCHOSOCIAL_RISK' }
	})
	.state('tthh.surveys',{
		url:'surveys',
		templateUrl:pathTthh+'surveys.html',
		controller:'tthh.surveysCtrl',
		resolve:{
			entity:function(myResource,$state){
				return myResource.sendData('evaluacionpersonal/REQUEST').save({account:'tthh'},function(json){
					myResource.myDialog.swalAlert(json);
					if(json.estado===true) return json;
				}, function(error){ myResource.myDialog.swalAlert({estado:'info',mensaje:'No se han encontrado nuevas evaluaciones.'}); }).$promise;
			}
		},
        data: { displayName:'LB_SURVEYS' }
	})
	.state('tthh.surveyTest',{
		url:'surveys/evaluation/:evaluationId/test/:testId/session/:staffId',
		templateUrl:pathTthh+'surveyTest.html',
		controller:'tthh.surveyTestCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/surveys/evaluations/questionnaire/questions').save($stateParams,function(json){
					if(json.estado===true) return json;
			    	else myResource.myDialog.swalAlert(json);
					$state.go('tthh.surveys');
				}, function(error){$state.go('tthh.surveys');}).$promise;
			}
		},
        data: { displayName:'MENU_PSYCHOSOCIAL_RISK' }
	})
	
	/*
	 * DIRECCIÓN ADMINISTRATIVA
	 */
	.state('administrative.edocumentation',{
		url:'edocumentation/inbox',
		templateUrl:pathAdministrative+'edocumentation.html',
		controller:'administrative.edocumentationCtrl',
        data: { displayName:'LB_ELECTRONIC_DOCUMENTATION' }
	})
	.state('administrative.edocumentationCompose',{
		url:'edocumentation/compose/new/:entityId/draft',
		templateUrl:pathAdministrative+'edocumentation.compose.html',
		controller:'administrative.edocumentationComposeCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('administrative/edocumentation/detail/byMessageId').save($stateParams,function(json){
					return json;
				}, function(error){$state.go('administrative.edocumentation');}).$promise;
			}
		},
        data: { displayName:'LB_ELECTRONIC_DOCUMENTATION' }
	})
	.state('administrative.edocumentationDetail',{
		url:'edocumentation/:entityId/detail',
		templateUrl:pathAdministrative+'detailEdoc.html',
		controller:'administrative.edocumentationDetailCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('administrative/edocumentation/detail/byMessageId').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('administrative.edocumentation');
				},function(error){$state.go('administrative.edocumentation');}).$promise;
			}
		},
		data:{ displayName:'LB_REGISTRATION_DETAIL' }
	})
	
	.state('administrative.archive',{
		url:'archive',
		templateUrl:pathAdministrative+'archive.html',
		controller:'administrative.archiveCtrl',
		data: { displayName:'LB_INSTITUTIONAL_ARCHIVE' }
	})
	.state('administrative.trainingroom',{
		url:'trainingroom',
		templateUrl:pathAdministrative+'trainingroom.html',
		controller:'administrative.trainingroomCtrl',
		data: { displayName:'LB_TRAINING_ROOM' }
	})
	.state('administrative.trainingroomCalendar',{
		url:'trainingroom/calendar',
		templateUrl:pathAdministrative+'trainingroom.calendar.html',
		controller:'administrative.trainingroomCalendarCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.getData('salacapacitaciones').get(function(json){
					return json;
				}, function(error){$state.go('administrative.trainingroom');}).$promise;
			}
		},
        data: { displayName:'LB_TRAINING_ROOM' }
	})
	.state('administrative.fuelvouchers',{
		url:'fuelvouchers',
		templateUrl:pathAdministrative+'fuelorder.html',
		controller:'administrative.fuelvouchersCtrl',
        data: { displayName:'LB_FUEL_VOUCHERS' }
	})
	
	/*
	 * DIRECCIÓN FINANCIERA
	 */
	.state('financial.advances',{
		url:'advances',
		templateUrl:pathTthh+'advances.html',
		controller:'financial.advancesCtrl',
        data: { displayName:'LB_ADVANCES_OF_REMUNERATION' }
	})
	.state('financial.processcontracts',{
		url:'processcontracts',
		templateUrl:pathModule+'processcontracts.html',
		controller:'financial.processcontractsCtrl',
        data: { displayName:'' }
	})
	.state('financial.detailProcesscontracts',{
		url:'processcontracts/:id',
		templateUrl:pathModule+'detailProcesscontracts.html',
		controller:'financial.detailProcesscontractsCtrl',
        data: { displayName:'' }
	})
	
	/*
	 * DIRECCION PERSONAL
	 */
	.state('leadership.requestedvacations',{
		url:'/request/vacations',
		templateUrl:pathLeadership+'requestedvacations.html',
		controller:'leadership.requestedvacationsCtrl'
	})
	.state('leadership.requestedpermissions',{
		url:'/request/permissions',
		templateUrl:pathLeadership+'requestedpermissions.html',
		controller:'leadership.requestedpermissionsCtrl'
	})
	.state('leadership.staff',{
		url:'/staff/:leadershipId',
		templateUrl:pathLeadership+'staff.html',
		controller:'leadership.staffCtrl'
	})
	.state('leadership.roadmap',{
		url:'/roadmap/:leadershipId',
		templateUrl:pathLeadership+'roadmap.html',
		controller:'leadership.roadmapCtrl'
	})
	.state('leadership.dailyactivities',{
		url:'/dailyactivities/:leadershipId',
		templateUrl:pathLeadership+'dailyactivities.html',
		controller:'leadership.dailyactivitiesCtrl'
	})
	.state('leadership.academictraining',{
		url:'/academictraining/:leadershipId',
		templateUrl:pathLeadership+'academictraining.html',
		controller:'leadership.academictrainingCtrl'
	})
	.state('leadership.driverslicenses',{
		url:'/driverslicenses/:leadershipId',
		templateUrl:pathLeadership+'driverslicenses.html',
		controller:'leadership.driverslicensesCtrl'
	})
	
	;
	$locationProvider.html5Mode(true);
}])
.run(['$rootScope','$cookies','$translate','$state','$stateParams','authorization','principal',function($rootScope,$cookies,$translate,$state,$stateParams,authorization,principal) {
	// SOLICITAR LOGIN O PERMISOS PARA CAMBIO DE ESTADOS
	$rootScope.changeLanguage=function(lang){
		$cookies.put('lang',lang);
		$translate.use(lang);
	};
	// VALIDAR CAMBIO DE RUTAS F
	$rootScope.$on('$stateChangeStart',function(event,toState,toStateParams){
        // track the state the user wants to go to; authorization service needs this
        $rootScope.toState = toState;
        $rootScope.toStateParams = toStateParams;
        // VALIDAR SESION
        if(typeof $cookies.get('userID')==='undefined' || $cookies.get('userID')===null){
			// ELIMINAR AUTENTICACION
			principal.authenticate(null);
        }
        // if the principal is resolved, do an authorization check immediately. otherwise, it'll be done when the state it resolved.
        if(principal.isIdentityResolved()) authorization.authorize();
	});
}]);