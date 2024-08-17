'use strict';
/**
 * CONFIGURACIÓN DEL MÓDULO AngularJS
 */
var app=angular.module('systemApp',[
	'ngAnimate',
	'ngResource',
	'ngCookies',
	'ui.router',
	'ngMaterial',
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
	'tc.chartjs',
	'angular-media-preview',
	'angular-img-cropper',
	'ng.jsoneditor',
	'dndLists',
	'angularUtils.directives.uiBreadcrumbs'
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
    var geoBlue=$mdThemingProvider.extendPalette('red',{'500':'343a40'}); // 343a40 - 3c8dbc  
    $mdThemingProvider.definePalette('geoBlue',geoBlue);
    // CUSTOM THEME - ANGULAR MATERIAL
	$mdThemingProvider.theme('default').primaryPalette('geoBlue').accentPalette('red');
	
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
	$urlRouterProvider.otherwise('/v3/e404');
	$stateProvider
	// SESIÓN DE USUARIO
	.state('login',{
		url:'/login',
		templateUrl:pathUser+'loginSession.html',
		controller:'loginCtrl'
	})
	.state('session',{
		url:'/v3/',
		templateUrl:pathUser+'session.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'LB_DASHBOARD' }
	})
	.state('session.e403',{
		url:'e403',
		templateUrl:pathInc+'main/e403.html',
        data: { displayName:'LB_UNAUTHORIZED_ACCESS' }
	})
	.state('session.e404',{
		url:'e404',
		templateUrl:pathInc+'main/e404.html',
        data: { displayName:'LB_PAGE_NOT_FOUND' }
	})
	// SESIÓN DE USUARIO
	.state('control',{
		url:'/control/',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] }
	})
	.state('control.stats',{
		url:'stats',
		templateUrl:pathUser+'stats.html'
	})
	.state('control.reports',{
		url:'reports',
		templateUrl:pathUser+'reports.html',
		controller:'exportsCtrl',
		resolve:{
			exports:function(myResource,$stateParams,$state){
				return myResource.getData('exports').query(function(json){
					return json;
				}, function(error){$state.go('session');}).$promise;
			}
		}
	})
	.state('control.profile',{
		url:'profile',
		templateUrl:pathUser+'profile.html',
		controller:'profileCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.getData('getSession').query(function(json){
					return json
				}, function(error){$state.go('session');}).$promise;
			}
		}
	})
	.state('control.messages',{
		url:'messages',
		templateUrl:pathAdmin+'messages.html',
		controller:'messagesCtrl'
	})
	.state('control.detailMessage',{
		url:'messages/:id',
		templateUrl:pathAdmin+'detailMessage.html',
		controller:'detailMessageCtrl',
		resolve:{
			message:function(myResource,$stateParams,$state){
				return myResource.getData('mensajes').get({id:$stateParams.id,customQuery:'searchById'},function(json){
					return json;
				},function(error){$state.go('mensajes');}).$promise;
			}
		}
	})
	// MODULÓ - ADMINISTRACIÓN
	.state('admin',{
		url:'/v3/admin',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_ADMIN' }
	})
	// ADMINISTRACION - PARAMETRIZACION
	.state('admin.parametrization',{
		url:'/parametrization',
		template:'<div ui-view/>',
		data:{ displayName:'LB_PARAMETRIZATION' }
	})
	.state('admin.parametrization.labels',{
		url:'/labels',
		templateUrl:pathAdmin+'parametrization/labels.html',
		controller:'labelsCtrl',
		data:{ displayName:'LB_LABELS' }
	})
	.state('admin.parametrization.parameters',{
		url:'/parameters',
		templateUrl:pathAdmin+'parametrization/parameters.html',
		controller:'parametersCtrl',
		data:{ displayName:'LB_PARAMETERS' }
	})
	.state('admin.parametrization.reports',{
		url:'/reports',
		templateUrl:pathAdmin+'parametrization/reports.html',
		controller:'reportsCtrl',
		data:{ displayName:'LB_REPORTERS' }
	})
	.state('admin.parametrization.dashboard',{
		url:'/dashboard',
		templateUrl:pathAdmin+'parametrization/dashboard.html',
		controller:'adminDashboardCtrl',
		data:{ displayName:'LB_DASHBOARD' }
	})
	.state('admin.parametrization.webmail',{
		url:'/webmail',
		templateUrl:pathAdmin+'parametrization/webmail.html',
		controller:'webmailCtrl',
		data:{ displayName:'LB_WEBMAIL' }
	})
	.state('admin.parametrization.trash',{
		url:'/trash',
		templateUrl:pathAdmin+'parametrization/trash.html',
		controller:'trashCtrl',
		resolve:{
			trash:function(myResource){
				return myResource.getData('getTrash').json(function(json){
					return json;
				},function(error){$state.go('session');}).$promise;
			}
		},
		data:{ displayName:'LB_TRASH' }
	})
	
	// ADMINISTRACIÓN DE USUARIOS
	.state('admin.users',{
		url:'/users',
		template:'<div ui-view/>',
		data:{ displayName:'LB_USERS' }
	})
	.state('admin.users.roles',{
		url:'/roles',
		templateUrl:pathAdmin+'users/roles.html',
		controller:'rolesCtrl'
	})
	.state('admin.users.profiles',{
		url:'/profiles',
		templateUrl:pathAdmin+'users/profiles.html',
		controller:'profilesCtrl'
	})
	.state('admin.users.list',{
		url:'/users',
		templateUrl:pathAdmin+'users/users.html',
		controller:'usersCtrl',
        data: {
            displayName:'LB_USERS'
        }
	})
	.state('admin.users.detailUsers',{
		url:'/users/:id',
		templateUrl:pathAdmin+'users/detailUsers.html',
		controller:'detailUsersCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('usuarios/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('admin.users');
				},function(error){$state.go('admin.users');}).$promise;
			}
		}
	})
	.state('admin.users.detailPersons',{
		url:'/person/:id',
		templateUrl:pathAdmin+'users/detailPersons.html',
		controller:'detailPersonsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('personas/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('admin.persons');
				},function(error){$state.go('admin.persons');}).$promise;
			}
		}
	})
	// ADMINISTRACION - MODULOS
	.state('admin.modules',{
		url:'/modules',
		template:'<div ui-view/>',
		data:{ displayName:'LB_MODULES' }
	})
	.state('admin.modules.modules',{
		url:'/modules',
		templateUrl:pathAdmin+'modules/modules.html',
		controller:'modulesCtrl',
		data:{ displayName:'LB_MODULES' }
	})
	.state('admin.modules.submodules',{
		url:'/submodules',
		templateUrl:pathAdmin+'modules/submodules.html',
		controller:'submodulesCtrl',
		data:{ displayName:'LB_SUBMODULES' }
	})
	.state('admin.modules.tables',{
		url:'/tables',
		templateUrl:pathAdmin+'modules/tables.html',
		controller:'tablesCtrl',
		data:{ displayName:'LB_TABLES_DB' }
	})
	.state('admin.modules.routes',{
		url:'/routes',
		templateUrl:pathAdmin+'modules/routes.html',
		controller:'routesCtrl',
		data:{ displayName:'LB_ROUTES' }
	})
	.state('admin.modules.resources',{
		url:'/resources',
		templateUrl:pathAdmin+'modules/resources.html',
		controller:'resourcesCtrl',
		data:{ displayName:'LB_RESOURCES' }
	})
	// PARAMETRIZACION DE RECURSOS
	.state('admin.resources',{
		url:'/resources',
		template:'<div ui-view/>',
		data:{ displayName:'LB_RESOURCES' }
	})
	.state('admin.resources.countries',{
		url:'/countries',
		templateUrl:pathAdmin+'resources/countries.html',
		data:{ displayName:'LB_COUNTRIES' }
	})
	.state('admin.resources.brands',{
		url:'/brands',
		templateUrl:pathAdmin+'resources/brands.html',
		controller:'brandsCtrl',
		data:{ displayName:'LB_BRANDS' }
	})
	.state('admin.resources.vehicles',{
		url:'/vehicles',
		templateUrl:pathAdmin+'resources/vehicles.html',
		controller:'vehiclesCtrl',
		data:{ displayName:'LB_VEHICLES' }
	})
	.state('admin.resources.people',{
		url:'/people',
		templateUrl:pathAdmin+'resources/people.html',
		controller:'peopleCtrl',
		data:{ displayName:'LB_PEOPLE' }
	})
	.state('admin.resources.driverslicenses',{
		url:'/driverslicenses',
		templateUrl:pathAdmin+'resources/driverslicenses.html',
		controller:'driverslicensesCtrl',
		data:{ displayName:'LB_DRIVERS_LICENSES' }
	})
	// ADMINISTRACIÓN DE USUARIOS
	.state('admin.services',{
		url:'/services',
		template:'<div ui-view/>',
		data:{ displayName:'LB_SERVICES' }
	})
	.state('admin.services.menu',{
		url:'/menu',
		templateUrl:pathAdmin+'services/menu.html',
		controller:'menuCtrl',
		data:{ displayName:'LB_MENU' }
	})
	.state('admin.services.pages',{
		url:'/pages',
		templateUrl:pathAdmin+'services/pages.html',
		controller:'pagesCtrl',
		data:{ displayName:'LB_PAGES' }
	})
	.state('admin.services.complaints',{
		url:'/complaints',
		templateUrl:pathAdmin+'services/complaints.html',
		controller:'complaintsCtrl',
		data:{ displayName:'LB_COMPLAINTS' }
	})	
	.state('admin.services.slides',{
		url:'/slides',
		templateUrl:pathAdmin+'services/slides.html',
		controller:'slidesCtrl',
		data:{ displayName:'LB_SLIDES' }
	})
	
	/*
	 * DIRECCIÓN GENERAL
	 */
	.state('management',{
		url:'/management',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_GENERAL_MANAGEMENT' }
	}) 
	// BALCÓN DE INFORMACION
	.state('management.information',{
		url:'/information',
		template:'<div ui-view/>',
		data:{ displayName:'LB_INFORMATION' }
	})
	.state('management.information.checking',{
		url:'/checking',
		templateUrl:pathMngt+'checking.html',
		controller:'management.checkingCtrl',
		data:{ displayName:'LB_ACTIVITY_LOG' }
	})
	.state('management.information.documents',{
		url:'/documents',
		templateUrl:pathMngt+'documents.html',
		controller:'management.documentsCtrl',
		data:{ displayName:'LB_DOCUMENTS' }
	})
	// ASUNTOS INTERNOS
	.state('management.iaffairs',{
		url:'/iaffairs',
		template:'<div ui-view/>',
		data:{ displayName:'LB_INTERNAL_AFFAIRS_UNIT' }
	})
	.state('management.iaffairs.complaints',{
		url:'/complaints',
		templateUrl:pathMngt+'complaints.html',
		controller:'management.complaintsCtrl',
		data:{ displayName:'LB_COMPLAINTS_ONLINE' }
	}) 
	.state('management.iaffairs.detailComplaints',{
		url:'/complaints/:complaintId/detail',
		templateUrl:pathMngt+'detailComplaints.html',
		controller:'management.detailComplaintsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('denuncias/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('management.iaffairs.complaints');
				},function(error){$state.go('management.iaffairs.complaints');}).$promise;
			}
		},
		data:{ displayName:'LB_DETAIL' }
	}) 
	
	// PARTES - SEGUNDA JEFATURA
	.state('subjefature',{
		url:'/subjefature',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_SUBJEFATURE' }
	}) 
	
	.state('subjefature.institucionalcodes',{
		url:'/institucionalcodes',
		templateUrl:pathSubj+'institucionalcodes.html',
		controller:'institucionalcodesCtrl',
		data:{ displayName:'LB_INSTITUTIONAL_CODES' }
	})
	.state('subjefature.tracking',{
		url:'/tracking',
		templateUrl:pathSubj+'tracking.html',
		controller:'trackingCtrl',
		data:{ displayName:'LB_VEHICULAR_FLEETS' }
	})
	.state('subjefature.detailTracking',{
		url:'/tracking/:id/detail',
		templateUrl:pathSubj+'detailTracking.html',
		controller:'detailTrackingCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('flotasvehiculares/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('logistics.tracking');
				},function(error){$state.go('logistics.tracking');}).$promise;
			}
		},
		data:{ displayName:'LB_VEHICULAR_FLEETS' }
	})
	
	.state('subjefature.causes',{
		url:'/causes',
		templateUrl:pathSubj+'causes.html',
		controller:'causesCtrl'
	})
	.state('subjefature.nature',{
		url:'/nature',
		templateUrl:pathSubj+'nature.html',
		controller:'natureCtrl'
	})
	.state('subjefature.parts',{
		url:'/parts',
		templateUrl:pathSubj+'parts.html',
		controller:'partsCtrl'
	})
	.state('subjefature.detailParts',{
		url:'/parts/:id',
		templateUrl:pathSubj+'detailParts.html',
		controller:'detailPartsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('partes/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('subjefature.parts');
				},function(error){$state.go('subjefature.parts');}).$promise;
			}
		}
	}) 
	.state('subjefature.guards',{
		url:'/guards',
		templateUrl:pathSubj+'guards.html',
		controller:'guardsCtrl'
	})
	.state('subjefature.binnacle',{
		url:'/binnacle',
		templateUrl:pathSubj+'binnacle.html',
		controller:'binnacleCtrl'
	})
	// MODULO - APH
	.state('subjefature.aph',{
		url:'/aph',
		template:'<div ui-view/>',
		data:{ displayName:'LB_UNIDAD_APH' }
	})
	.state('subjefature.aph.supplies',{
		url:'/supplies',
		templateUrl:pathSubj+'aph/supplies.html',
		controller:'aph.suppliesCtrl',
		data:{ displayName:'TOOLBAR_APH_SUPPLIES' }
	})
	.state('subjefature.aph.supplycontrol',{
		url:'/supplycontrol',
		templateUrl:pathSubj+'aph/supplycontrol.html',
		controller:'aph.supplycontrolCtrl',
		data:{ displayName:'TOOLBAR_APH_SUPPLIES_CONTROL' }
	})
	.state('subjefature.aph.inventorysupplycontrol',{
		url:'/supplycontrol/:id/inventory',
		templateUrl:pathSubj+'aph/inventorySupply.html',
		controller:'aph.inventorysupplycontrolCtrl',
		resolve:{
			supplies:function(myResource,$stateParams,$state){
				return myResource.requestData('subjefature/aph/suppliesinventory/movements/list').save({inventoryId:$stateParams.id},function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('subjefature.aph.supplycontrol');
				},function(error){$state.go('subjefature.aph.supplycontrol');}).$promise;
			}
		},
		data:{ displayName:'LB_SUPPLY_REGISTRY' }
	})
	.state('subjefature.aph.suppliesstock',{
		url:'/suppliesstock',
		templateUrl:pathSubj+'aph/suppliesstock.html',
		controller:'aph.suppliesstockCtrl',
		data:{ displayName:'LB_SUPPLIES_STOCK' }
	})
	.state('subjefature.aph.suppliesstations',{
		url:'/suppliesstations',
		templateUrl:pathSubj+'aph/suppliesstations.html',
		controller:'aph.suppliesstationsCtrl',
		data:{ displayName:'LB_STATION_CONTROL' }
	})
	.state('subjefature.aph.suppliesstockstations',{
		url:'/suppliesstations/:id/stock',
		templateUrl:pathSubj+'aph/suppliesstockstations.html',
		controller:'aph.suppliesstockstationsCtrl',
		data:{ displayName:'LB_STATION_CONTROL' }
	})
	.state('subjefature.aph.rechargeorder',{
		url:'/rechargeorder',
		templateUrl:pathSubj+'aph/rechargeorder.html',
		controller:'aph.rechargeorderCtrl',
		data:{ displayName:'TOOLBAR_APH_RECHARGE_ORDER' }
	})
	
	// TALENTO HUMANO
	.state('tthh',{
		url:'/tthh',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_TTHH_MANAGEMENT' }
	}) 
	// TALENTO HUMANO - PARAMETRIZACIÓN
	.state('tthh.settings',{
		url:'/settings',
		template:'<div ui-view/>',
		data:{ displayName:'LB_PARAMETRIZATION' }
	}) 
	.state('tthh.settings.activities',{
		url:'/activities',
		templateUrl:pathTthh+'settings/activities.html',
		controller:'activitiesjobsCtrl',
		data:{ displayName:'LB_ACTIVITIES_JOBS' }
	})
	.state('tthh.settings.competences',{
		url:'/competences',
		templateUrl:pathTthh+'settings/competences.html',
		controller:'competencesjobsCtrl',
		data:{ displayName:'LB_COMPETENCES_JOBS' }
	})
	.state('tthh.settings.regulations',{
		url:'/regulations',
		templateUrl:pathTthh+'settings/regulations.html',
		controller:'regulationsCtrl',
		data:{ displayName:'LB_REGULATIONS' }
	}) 
	.state('tthh.settings.typeactions',{
		url:'/typeactions',
		templateUrl:pathTthh+'settings/typeactions.html',
		controller:'typeactionsCtrl',
		data:{ displayName:'LB_TYPES_ACTIONS' }
	}) 
	.state('tthh.settings.workdays',{
		url:'/workdays',
		templateUrl:pathTthh+'settings/workdays.html',
		controller:'workdaysCtrl',
		data:{ displayName:'MENU_WORKDAYS' }
	})
	.state('tthh.settings.typeadvances',{
		url:'/typeadvances',
		templateUrl:pathTthh+'settings/typeadvances.html',
		controller:'typeadvancesCtrl',
		data:{ displayName:'MENU_TYPES_ADVANCES' }
	})
	.state('tthh.settings.typecontracts',{
		url:'/typecontracts',
		templateUrl:pathTthh+'settings/typecontracts.html',
		controller:'typecontractsCtrl',
		data:{ displayName:'MENU_TYPES_CONTRACTS' }
	})
	.state('tthh.settings.occupationalgroups',{
		url:'/occupationalgroups',
		templateUrl:pathTthh+'settings/occupationalgroups.html',
		controller:'occupationalgroupsCtrl',
		data:{ displayName:'MENU_OCCUPATIONAL_GROUPS' }
	})
	.state('tthh.settings.ratings',{
		url:'/ratings',
		templateUrl:pathAdmin+'resources/ratings.html',
		controller:'settings.ratingsCtrl',
		data:{ displayName:'LB_RATING_SYSTEM' }
	})
	// TALENTO HUMANO - INSTITUCION
	.state('tthh.institution',{
		url:'/institution',
		template:'<div ui-view/>',
		data:{ displayName:'LB_INSTITUTION' }
	}) 
	.state('tthh.institution.calendar',{
		url:'/calendar',
		templateUrl:pathTthh+'calendar.html',
		controller:'scheduleCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.sendData('schedule/REQUEST').save({type:'system'},function(json){
					return json
				},function(error){$state.go('main');}).$promise;
			}
		},
		data:{ displayName:'LB_CALENDAR' }
	})
	.state('tthh.institution.emergency',{
		url:'/emergency',
		templateUrl:pathTthh+'emergency.html',
		controller:'emergencyCtrl',
		data:{ displayName:'LB_STATE_OF_EMERGENCY' }
	})
	.state('tthh.institution.events',{
		url:'/events',
		templateUrl:pathTthh+'events.html',
		controller:'eventsCtrl',
		data:{ displayName:'LB_EVENTS' }
	})
	.state('tthh.institution.holidays',{
		url:'/holidays',
		templateUrl:pathTthh+'holidays.html',
		controller:'holidaysCtrl',
		data:{ displayName:'LB_HOLIDAYS' }
	}) 
	.state('tthh.institution.system',{
		url:'/system',
		templateUrl:pathTthh+'institution/system.html',
		controller:'systemCtrl',
		resolve:{
			sistema:function(myResource){
				return myResource.getData('sistema').json(function(json){
					return json;
				},function(error){$state.go('session');}).$promise;
			}
		},
		data:{ displayName:'LB_SYSTEM' }
	})
	.state('tthh.institution.stations',{
		url:'/stations',
		templateUrl:pathTthh+'institution/stations.html',
		controller:'stationsCtrl',
		data:{ displayName:'LB_STATIONS' }
	})
	.state('tthh.institution.detailStations',{
		url:'/stations/:id',
		templateUrl:pathTthh+'institution/detailStations.html',
		controller:'detailStationsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('estaciones/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.stations');
				},function(error){$state.go('tthh.stations');}).$promise;
			}
		},
		data:{ displayName:'LB_STATIONS' }
	})
	.state('tthh.institution.wineries',{
		url:'/wineries',
		templateUrl:pathTthh+'institution/wineries.html',
		controller:'wineriesCtrl',
		data:{ displayName:'LB_WINERIES' }
	})
	.state('tthh.institution.leaderships',{
		url:'/leaderships',
		templateUrl:pathTthh+'institution/leaderships.html',
		controller:'leadershipsCtrl',
		data:{ displayName:'LB_LEADERSHIPS' }
	})
	.state('tthh.institution.jobs',{
		url:'/jobs',
		templateUrl:pathTthh+'institution/jobs.html',
		controller:'jobsCtrl',
		data:{ displayName:'LB_JOBS' }
	})
	
	// TALENTO HUMANO - PERSONAL
	.state('tthh.staff',{
		url:'/staff',
		template:'<div ui-view/>',
		data:{ displayName:'MENU_STAFF' }
	}) 
	.state('tthh.staff.staff',{
		url:'/staff',
		templateUrl:pathTthh+'staff.html',
		controller:'staffCtrl',
		data:{ displayName:'LB_STAFF' }
	})
	.state('tthh.staff.detailStaff',{
		url:'/staff/:id/detail',
		templateUrl:pathTthh+'detailStaff.html',
		controller:'detailStaffCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('personal/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.staff');
				},function(error){$state.go('tthh.staff');}).$promise;
			}
		},
		data:{ displayName:'LB_STAFF' }
	})
	.state('tthh.staff.operators',{
		url:'/operators',
		templateUrl:pathTthh+'operators.html',
		controller:'operatorsCtrl',
		data:{ displayName:'LB_OPERATORS' }
	}) 
	.state('tthh.staff.performances',{
		url:'/performances',
		templateUrl:pathTthh+'performances.html',
		controller:'performancesCtrl',
		data:{ displayName:'LB_PERFORMANCE_EVALUATION' }
	}) 
	.state('tthh.staff.detailPerformances',{
		url:'/performances/:id',
		templateUrl:pathTthh+'detailPerformances.html',
		controller:'detailPerformancesCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('evaluacionesdesempenio/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.performances');
				},function(error){$state.go('tthh.performances');}).$promise;
			}
		},
		data:{ displayName:'LB_PERFORMANCE_EVALUATION' }
	})
	.state('tthh.staff.performancesPersonal',{
		url:'/staff/performances/:personalId',
		templateUrl:pathTthh+'performancesPersonal.html',
		controller:'performancesPersonalCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('evaluacionesdesempenio/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.performances');
				},function(error){$state.go('tthh.performances');}).$promise;
			}
		},
		data:{ displayName:'LB_PERFORMANCE_EVALUATION' }
	}) 
	.state('tthh.staff.staffactions',{
		url:'/staffactions',
		templateUrl:pathTthh+'staffactions.html',
		controller:'staffactionsCtrl',
		data:{ displayName:'LB_STAFF_ACTIONS' }
	}) 
	.state('tthh.staff.delegations',{
		url:'/delegations',
		templateUrl:pathTthh+'delegations.html',
		controller:'delegationsCtrl',
		data:{ displayName:'LB_DELEGATIONS' }
	})
	.state('tthh.staff.sanctions',{
		url:'/sanctions',
		templateUrl:pathTthh+'sanctions.html',
		controller:'sanctionsCtrl',
		data:{ displayName:'LB_SANCTIONS' }
	}) 
	
	// TALENTO HUMANO - PERSONAL OPERATIVO
	.state('tthh.operationalStaff',{
		url:'/operational/staff',
		template:'<div ui-view/>',
		data:{ displayName:'LB_OPERATIONAL_STAFF' }
	}) 
	.state('tthh.operationalStaff.platoons',{
		url:'/platoons',
		templateUrl:pathTthh+'platoons.html',
		controller:'platoonsCtrl'
	})
	.state('tthh.operationalStaff.platoonsStaff',{
		url:'/platoons/staff',
		templateUrl:pathTthh+'platoonsStaff.html',
		controller:'platoonsStaffCtrl'
	})
	.state('tthh.operationalStaff.distribution',{
		url:'/distribution',
		templateUrl:pathTthh+'distribution.html',
		controller:'distributionCtrl'
	})
	.state('tthh.operationalStaff.distributionSetting',{
		url:'/distribution/:distributionId/setting',
		templateUrl:pathTthh+'distribution.setting.html',
		controller:'distribution.settingCtrl',
		resolve:{
			data:function(myResource,$stateParams,$state){
				return myResource.sendData('pelotones/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.distribution');
				},function(error){$state.go('tthh.distribution');}).$promise;
			}
		}
	})
	
	// MODULO - CONTROL DE ASISTENCIA
	.state('tthh.attendance',{
		url:'/attendance',
		template:'<div ui-view/>',
		data:{ displayName:'LB_ASSIST_CONTROL' }
	}) 
	.state('tthh.attendance.biometriccodes',{
		url:'/biometriccodes',
		templateUrl:pathTthh+'attendance/biometriccodes.html',
		controller:'attendance.biometriccodesCtrl',
		data:{ displayName:'LB_USER_CODES' }
	}) 
	.state('tthh.attendance.biometricperiods',{
		url:'/biometricperiods',
		templateUrl:pathTthh+'attendance/biometricperiods.html',
		controller:'attendance.biometricperiodsCtrl',
		data:{ displayName:'LB_MONTHLY_RECORD' }
	}) 
	.state('tthh.attendance.biometricmarkings',{
		url:'/biometricperiod/:id/markings',
		templateUrl:pathTthh+'attendance/biometricmarkings.html',
		controller:'attendance.biometricmarkingsCtrl',
		data:{ displayName:'LB_DIAL_REGISTER' }
	})
	.state('tthh.attendance.biometricnomarkings',{
		url:'/biometricperiod/:id/nomarkings',
		templateUrl:pathTthh+'attendance/biometricnomarkings.html',
		controller:'attendance.biometricnomarkingsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/attendance/biometricperiods/findById').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.attendance.biometricperiods');
				},function(error){$state.go('tthh.attendance.biometricperiods');}).$promise;
			}
		},
		data:{ displayName:'LB_JUSTIFY_NO_MARKINGS' }
	})
	.state('tthh.attendance.staffnomarkings',{
		url:'/biometricperiod/:id/staff/nomarkings',
		templateUrl:pathTthh+'attendance/staffnomarkings.html',
		controller:'attendance.staffnomarkingsCtrl',
		data:{ displayName:'LB_STAFF_NOMARKINGS' }
	})
	.state('tthh.attendance.biometricstaffnomarkings',{
		url:'/biometricperiod/:id/staff/:staffId/nomarkings',
		templateUrl:pathTthh+'attendance/biometricstaffnomarkings.html',
		controller:'attendance.biometricstaffnomarkingsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/attendance/biometric/staff/markings/list').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.attendance.biometricperiods');
				},function(error){$state.go('tthh.attendance.biometricperiods');}).$promise;
			}
		},
		data:{ displayName:'LB_JUSTIFY_NO_MARKINGS' }
	}) 
	.state('tthh.attendance.arrears',{
		url:'/arrears',
		templateUrl:pathTthh+'attendance/arrears.html',
		controller:'attendance.arrearsCtrl',
		data:{ displayName:'LB_ARREARS' }
	})
	.state('tthh.attendance.ranches',{
		url:'/ranches',
		templateUrl:pathTthh+'attendance/ranches.html',
		controller:'attendance.ranchesCtrl',
		data:{ displayName:'LB_RANCHES' }
	})
	.state('tthh.attendance.absences',{
		url:'/absences',
		templateUrl:pathTthh+'attendance/absences.html',
		controller:'attendance.absencesCtrl',
		data:{ displayName:'LB_ABSENCE_CONTROL' }
	})
	
	// TALENTO HUMANO - VACACIONES
	.state('tthh.vacations',{
		url:'/vacations',
		template:'<div ui-view/>',
		data:{ displayName:'TOOLBAR_VACATIONS' }
	}) 
	.state('tthh.vacations.generated',{
		url:'/generated',
		templateUrl:pathTthh+'vacations/vacations.html',
		controller:'vacationsCtrl',
		data:{ displayName:'LB_VACATION_BALANCES' }
	})
	.state('tthh.vacations.detailVacations',{
		url:'/vacations/:staffId/detail',
		templateUrl:pathTthh+'vacations/detailVacations.html',
		controller:'detailVacationsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('vacaciones/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.vacations');
				},function(error){$state.go('tthh.vacations');}).$promise;
			}
		},
		data:{ displayName:'LB_VACATION_BALANCES' }
	})
	.state('tthh.vacations.requestedvacations',{
		url:'/requestedvacations',
		templateUrl:pathTthh+'vacations/requestedvacations.html',
		controller:'requestedvacationsCtrl',
		data:{ displayName:'LB_REQUESTED_HOLIDAYS' }
	})
	.state('tthh.vacations.requestedpermissions',{
		url:'/requestedpermissions',
		templateUrl:pathTthh+'vacations/requestedpermissions.html',
		controller:'requestedpermissionsCtrl',
		data:{ displayName:'LB_REQUESTED_PERMITS' }
	})
	
	// TALENTO HUMANO - ANTICIPOS DE REMUNERACIONES
	.state('tthh.advances',{
		url:'/advances',
		template:'<div ui-view/>',
		data:{ displayName:'LB_ADVANCES_OF_REMUNERATION' }
	}) 
	.state('tthh.advances.parametrization',{
		url:'/parametrization',
		templateUrl:pathTthh+'advances/parametrization.html',
		controller:'advances.parametrizationCtrl',
		data:{ displayName:'LB_PARAMETRIZATION' }
	})
	.state('tthh.advances.banks',{
		url:'/banks',
		templateUrl:pathTthh+'advances/banks.html',
		controller:'advances.banksCtrl',
		data:{ displayName:'LB_BANK_ACCOUNTS' }
	})
	.state('tthh.advances.garants',{
		url:'/garants',
		templateUrl:pathTthh+'advances/garants.html',
		controller:'advances.garantsCtrl',
		data:{ displayName:'LB_GARANTS' }
	})
	.state('tthh.advances.advances',{
		url:'/advances',
		templateUrl:pathTthh+'advances/advances.html',
		controller:'advances.advancesCtrl',
		data:{ displayName:'LB_APPLICATIONS_FOR_ADVANCES' }
	})
	
	// TALENTO HUMANO - ANALISTA DE TALENTO HUMANO
	.state('tthh.staffInfo',{
		url:'/staffInfo',
		template:'<div ui-view/>',
		data:{ displayName:'MENU_STAFF_INFO' }
	}) 
	.state('tthh.staffInfo.roadmap',{
		url:'/roadmap',
		templateUrl:pathTthh+'roadmap.html',
		controller:'roadmapCtrl'
	}) 
	.state('tthh.staffInfo.dailyactivities',{
		url:'/dailyactivities',
		templateUrl:pathTthh+'dailyactivities.html',
		controller:'dailyactivitiesCtrl'
	}) 
	.state('tthh.staffInfo.academicTraining',{
		url:'/academicTraining',
		templateUrl:pathTthh+'academicTraining.html',
		controller:'academicTrainingCtrl'
	}) 
	.state('tthh.staffInfo.awards',{
		url:'/awards',
		templateUrl:pathTthh+'awards.html',
		controller:'awardsCtrl'
	}) 
	.state('tthh.staffInfo.coursescompleted',{
		url:'/coursescompleted',
		templateUrl:pathTthh+'coursescompleted.html',
		controller:'coursescompletedCtrl'
	}) 
	.state('tthh.staffInfo.employments',{
		url:'/employments',
		templateUrl:pathTthh+'employments.html',
		controller:'employmentsCtrl'
	}) 
	.state('tthh.staffInfo.affidavits',{
		url:'/affidavits',
		templateUrl:pathTthh+'affidavits.html',
		controller:'affidavitsCtrl'
	}) 
	.state('tthh.staffInfo.familiars',{
		url:'/familiars',
		templateUrl:pathTthh+'familiars.html',
		controller:'familiarsCtrl'
	})
	
	// TALENTO HUMANO - ENCUESTAS
	.state('tthh.surveys',{
		url:'/surveys',
		template:'<div ui-view/>',
		data:{ displayName:'LB_SURVEYS' }
	}) 
	.state('tthh.surveys.forms',{
		url:'/forms',
		templateUrl:pathAdmin+'resources/forms.html',
		controller:'surveys.formsCtrl',
		data:{ displayName:'LB_FORMS_FOR_EVALUATION' }
	})
	.state('tthh.surveys.sectionsForms',{
		url:'/forms/:entityId/sections',
		templateUrl:pathAdmin+'resources/forms.sections.html',
		controller:'surveys.detailFormsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('resources/surveys/forms/entityById').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.surveys.forms');
				},function(error){$state.go('tthh.surveys.forms');}).$promise;
			}
		},
		data:{ displayName:'LB_FORMS_FOR_EVALUATION' }
	})
	.state('tthh.surveys.evaluations',{
		url:'/evaluations',
		templateUrl:pathTthh+'surveys/evaluations.html',
		controller:'surveys.evaluationsCtrl',
		data:{ displayName:'MENU_SURVEYS' }
	})
	.state('tthh.surveys.detailEvaluations',{
		url:'/evaluations/:id/detail',
		templateUrl:pathTthh+'surveys/detailEvaluations.html',
		controller:'surveys.detailEvaluationsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/surveys/evaluations/entityById').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.surveys.evaluations');
				},function(error){$state.go('tthh.surveys.evaluations');}).$promise;
			}
		},
		data:{ displayName:'MENU_SURVEYS' }
	})
	
	// TALENTO HUMANO - UNIDAD DE SEGURIDAD Y SALUD OCUPACIONES
	.state('tthh.sos',{
		url:'/sos',
		template:'<div ui-view/>',
		data:{ displayName:'LB_UNIDAD_SEGURIDAD' }
	})
	.state('tthh.sos.waterfall',{
		url:'/waterfall',
		template:'<div ui-view/>',
		data:{ displayName:'TOOLBAR_WATERFALL' }
	})
	.state('tthh.sos.waterfall.filters',{
		url:'/filters',
		templateUrl:pathTthh+'sos/waterfall/filters.html',
		controller:'sos.filtersCtrl',
        data: { displayName:'LB_FILTERS_WATERFALL' }
	})
	.state('tthh.sos.waterfall.waterfall',{
		url:'/usage',
		templateUrl:pathTthh+'sos/waterfall/waterfall.html',
		controller:'sos.waterfallCtrl',
        data: { displayName:'LB_USE_WATERFALL' }
	})
	
	.state('tthh.sos.fireextinguisher',{
		url:'/fireextinguisher',
		template:'<div ui-view/>',
		data:{ displayName:'LB_FIRE_EXTINGUISHER_INSPECTION' }
	})
	.state('tthh.sos.fireextinguisher.list',{
		url:'/list',
		templateUrl:pathTthh+'sos/fireextinguisher/list.html',
		controller:'sos.fireextinguisherCtrl',
        data: { displayName:'LB_FIRE_EXTINGUISHERS' }
	})
	.state('tthh.sos.fireextinguisher.inspections',{
		url:'/inspections',
		templateUrl:pathTthh+'sos/fireextinguisher/inspections.html',
		controller:'sos.fireextinguisherInspectionsCtrl',
		data: { displayName:'LB_INSPECTIONS' }
	})
	.state('tthh.sos.fireextinguisher.reviews',{
		url:'/inspection/:inspectionId/reviews/edition',
		templateUrl:pathTthh+'sos/fireextinguisher/reviews.html',
		controller:'sos.reviewsFextinguisherCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/fextinguisher/inspections/detail/entityId').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.fireextinguisher.inspections');
				},function(error){$state.go('tthh.sos.fireextinguisher.inspections');}).$promise;
			}
		},
		data: { displayName:'LB_INSPECTIONS' }
	})
	
	.state('tthh.sos.vrescue',{
		url:'/vrescue',
		template:'<div ui-view/>',
		data:{ displayName:'LB_VERTICAL_RESCUE' }
	})
	.state('tthh.sos.vrescue.damageforms',{
		url:'/damageforms',
		templateUrl:pathTthh+'sos/vrescue/damageforms.html',
		controller:'sos.vrescueDamageformsCtrl',
        data: { displayName:'LB_DAMAGE_FORMS' }
	})
	.state('tthh.sos.vrescue.sectionsForms',{
		url:'/damageforms/:entityId/sections',
		templateUrl:pathTthh+'sos/vrescue/forms.sections.html',
		controller:'sos.vrescueDetailFormsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('resources/surveys/forms/entityById').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.vrescue.damageforms');
				},function(error){$state.go('tthh.sos.vrescue.damageforms');}).$promise;
			}
		},
		data:{ displayName:'LB_DAMAGE_FORMS' }
	})
	.state('tthh.sos.vrescue.equipmentcategories',{
		url:'/categories/equipments',
		templateUrl:pathTthh+'sos/vrescue/equipmentcategories.html',
		controller:'sos.equipmentcategoriesCtrl',
        data: { displayName:'LB_EQUIPMENT_CATEGORIES' }
	})
	.state('tthh.sos.vrescue.equipments',{
		url:'/equipments',
		templateUrl:pathTthh+'sos/vrescue/equipments.html',
		controller:'sos.equipmentsCtrl',
		data: { displayName:'LB_VERTICAL_RESCUE_EQUIPMENTS' }
	})
	.state('tthh.sos.vrescue.detailEquipments',{
		url:'/equipments/:equipmentId/detail',
		templateUrl:pathTthh+'sos/vrescue/detailEquipments.html',
		controller:'sos.detailEquipmentsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/vrescue/equipments/detailById').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.vrescue.equipments');
				},function(error){$state.go('tthh.sos.vrescue.equipments');}).$promise;
			},
			usage:function(myResource,$stateParams){
				return myResource.requestData('tthh/sos/vrescue/equipments/usage/byEquipmentId/list').save($stateParams,function(json){ return json.data; }).$promise;
			},
		},
		data: { displayName:'LB_VERTICAL_RESCUE_EQUIPMENTS' }
	})
	.state('tthh.sos.vrescue.equipmentUsage',{
		url:'/equipments/:equipmentId/form/:formId/usage',
		templateUrl:pathTthh+'sos/vrescue/equipmentusage.html',
		controller:'sos.equipmentUsageCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/vrescue/equipments/formByEquipment').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.vrescue.equipments');
				},function(error){$state.go('tthh.sos.vrescue.equipments');}).$promise;
			}
		},
		data: { displayName:'LB_VERTICAL_RESCUE_EQUIPMENTS' }
	})
	.state('tthh.sos.vrescue.equipmentUsagedition',{
		url:'/equipments/:equipmentId/form/:formId/usage/:entityId/edition',
		templateUrl:pathTthh+'sos/vrescue/equipmentusage.html',
		controller:'sos.equipmentUsageCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/vrescue/equipments/usage/formByHistoryId').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.vrescue.verticalrescue');
				},function(error){$state.go('tthh.sos.vrescue.verticalrescue');}).$promise;
			}
		},
		data: { displayName:'LB_VERTICAL_RESCUE_EQUIPMENTS' }
	})
	.state('tthh.sos.vrescue.verticalrescue',{
		url:'/verticalrecue',
		templateUrl:pathTthh+'sos/vrescue/verticalrescue.html',
		controller:'sos.verticalrecueCtrl',
        data: { displayName:'LB_VERTICAL_RESCUE' }
	})
	
	.state('tthh.sos.psychosocial',{
		url:'/psychosocial',
		template:'<div ui-view/>',
		data:{ displayName:'LB_PSYCHOSOCIAL_RISK' }
	})
	.state('tthh.sos.psychosocial.ratings',{
		url:'/ratings',
		templateUrl:pathTthh+'sos/psychosocial/ratings.html',
		controller:'sos.psychosocialratingsCtrl',
		data: { displayName:'LB_RATING_SYSTEM' }
	})
	.state('tthh.sos.psychosocial.questions',{
		url:'/questions',
		templateUrl:pathTthh+'sos/psychosocial/questions.html',
		controller:'sos.psychosocialquestionsCtrl',
		data: { displayName:'LB_LIST_OF_QUESTIONS' }
	})
	.state('tthh.sos.psychosocial.forms',{
		url:'/forms',
		templateUrl:pathTthh+'sos/psychosocial/forms.html',
		controller:'sos.psychosocialformsCtrl',
		data: { displayName:'LB_FORMS_FOR_EVALUATION' }
	})
	.state('tthh.sos.psychosocial.sectionsForms',{
		url:'/forms/:entityId/sections',
		templateUrl:pathTthh+'sos/psychosocial/forms.sections.html',
		controller:'sos.detailPsychosocialformsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/psychosocial/forms/entityById').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.psychosocial.forms');
				},function(error){$state.go('tthh.sos.psychosocial.forms');}).$promise;
			}
		},
		data:{ displayName:'LB_FORMS_FOR_EVALUATION' }
	})
	.state('tthh.sos.psychosocial.questionsForms',{
		url:'/forms/:entityId/questions',
		templateUrl:pathTthh+'sos/psychosocial/forms.questions.html',
		controller:'sos.detailPsychosocialformsSectionsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/psychosocial/forms/sections/entityById').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.psychosocial.forms');
				},function(error){$state.go('tthh.sos.psychosocial.forms');}).$promise;
			}
		},
		data:{ displayName:'LB_FORMS_FOR_EVALUATION' }
	})
	.state('tthh.sos.psychosocial.evaluations',{
		url:'/evaluations',
		templateUrl:pathTthh+'sos/psychosocial/evaluations.html',
		controller:'sos.psychosocialevaluationsCtrl',
		data: { displayName:'LB_EVALUATIONS_CARRIED_OUT' }
	})
	.state('tthh.sos.psychosocial.detailEvaluations',{
		url:'/evaluations/:entityId/detail',
		templateUrl:pathTthh+'sos/psychosocial/detailEvaluations.html',
		controller:'sos.psychosocialDetailEvaluationsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/sos/psychosocial/evaluations/entityById').save($stateParams,function(json){
					if(json.estado===true)return json;
					$state.go('tthh.sos.psychosocial.evaluations');
				},function(error){$state.go('tthh.sos.psychosocial.evaluations');}).$promise;
			}
		},
		data:{ displayName:'LB_FORMS_FOR_EVALUATION' }
	})
	
	// TALENTO HUMANO - DEPARTAMENTO MEDICO
	.state('tthh.medical',{
		url:'/md',
		template:'<div ui-view/>',
		data:{ displayName:'LB_UNIDAD_ENFERMERIA' }
	})
	.state('tthh.medical.cie',{
		url:'/cie',
		templateUrl:pathTthh+'md/cie.html',
		controller:'cieCtrl',
		data:{ displayName:'LB_CIE_FULLNAME' }
	})
	.state('tthh.medical.supplies',{
		url:'/supplies',
		templateUrl:pathTthh+'md/supplies.html',
		controller:'suppliesCtrl',
		data:{ displayName:'TOOLBAR_MEDICINES' }
	})
	.state('tthh.medical.inventoryMedicines',{
		url:'/supplies/inventory',
		templateUrl:pathTthh+'md/inventoryMedicines.html',
		controller:'inventoryMedicinesCtrl',
		resolve:{
			medicines:function(myResource,$stateParams,$state){
				return myResource.requestData('tthh/md/pharmacy/supplies/inventory').query(function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.medical.medicines');
				},function(error){$state.go('tthh.medical.medicines');}).$promise;
			}
		},
		data:{ displayName:'TOOLBAR_MEDICINES' }
	})
	.state('tthh.medical.medicalhistories',{
		url:'/medicalhistories',
		templateUrl:pathTthh+'md/medicalhistories.html',
		controller:'medicalhistoriesCtrl',
		data:{ displayName:'TOOLBAR_MEDICAL_HISTORIES' }
	})
	.state('tthh.medical.detailMedicalhistories',{
		url:'/medicalhistories/:id',
		templateUrl:pathTthh+'md/detailMedicalhistories.html',
		controller:'detailMedicalhistoriesCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('historiasclinicas/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.medical.medicalhistories');
				},function(error){$state.go('tthh.medical.medicalhistories');}).$promise;
			}
		},
		data:{ displayName:'TOOLBAR_MEDICAL_HISTORIES' }
	})
	.state('tthh.medical.medicalconsultations',{
		url:'/medicalconsultations',
		templateUrl:pathTthh+'md/medicalconsultations.html',
		controller:'medicalconsultationsCtrl',
		data:{ displayName:'TOOLBAR_MEDICAL_CONSULTATIONS' }
	})
	.state('tthh.medical.newMedicalConsultation',{
		url:'/medicalconsultations/new/:historyId',
		templateUrl:pathTthh+'md/newMedicalConsultation.html',
		controller:'newMedicalConsultationCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('consultasmedicas/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.medical.medicalconsultations');
				},function(error){$state.go('tthh.medical.medicalconsultations');}).$promise;
			}
		},
		data:{ displayName:'TOOLBAR_MEDICAL_CONSULTATIONS' }
	})
	.state('tthh.medical.editMedicalConsultation',{
		url:'/medicalconsultations/edit/:entityId',
		templateUrl:pathTthh+'md/newMedicalConsultation.html',
		controller:'newMedicalConsultationCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('consultasmedicas/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('tthh.medical.medicalconsultations');
				},function(error){$state.go('tthh.medical.medicalconsultations');}).$promise;
			}
		},
		data:{ displayName:'TOOLBAR_MEDICAL_CONSULTATIONS' }
	})
	.state('tthh.medical.medicalprescriptions',{
		url:'/medicalprescriptions',
		templateUrl:pathTthh+'md/medicalprescriptions.html',
		controller:'medicalprescriptionsCtrl',
		data:{ displayName:'TOOLBAR_MEDICALPRESCRIPTION' }
	})
	.state('tthh.medical.medicalrest',{
		url:'/medicalrest',
		templateUrl:pathTthh+'md/medicalrest.html',
		controller:'medicalrestCtrl',
		data:{ displayName:'TOOLBAR_MEDICALREST' }
	})
	.state('tthh.medical.recipientsrest',{
		url:'/medicalrest/recipients',
		templateUrl:pathTthh+'md/medicalrest.recipients.html',
		controller:'medicalrest.recipientsCtrl',
		data:{ displayName:'LB_CERTIFICATE_RECIPIENTS' }
	})
	
	// TALENTO HUMANO - REGISTRO DE CANDIDATOS
	.state('tthh.candidates',{
		url:'/candidates',
		templateUrl:pathTthh+'candidates.html',
		controller:'candidatesCtrl'
	})
	.state('tthh.detailCandidates',{
		url:'/candidates/:id',
		templateUrl:pathTthh+'detailCandidates.html',
		controller:'detailCandidatesCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.getData('getCandidate').query({candidateId:$stateParams.id},function(json){
					return json;
				},function(error){$state.go('tthh.candidates');}).$promise;
			}
		}
	})
	
	// DIRECCION ADMINISTRATIVA
	.state('logistics',{
		url:'/logistics',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_LOGISTICS_MANAGEMENT' }
	})
	
	// DIRECCION ADMINISTRATIVA - ARCHIVO
	.state('logistics.edoc',{
		url:'/edoc',
		template:'<div ui-view/>',
		data:{ displayName:'LB_ELECTRONIC_DOCUMENTATION' }
	})
	.state('logistics.edoc.inbox',{
		url:'/inbox',
		templateUrl:pathLogi+'edoc/edoc.html',
		controller:'logistics.edocInboxCtrl',
		data:{ displayName:'LB_INBOX' }
	})
	.state('logistics.edoc.deleted',{
		url:'/deleted',
		templateUrl:pathLogi+'edoc/edoc.html',
		controller:'logistics.edocDeletedCtrl',
		data:{ displayName:'LB_REMOVED' }
	})
	.state('logistics.edoc.detailEdoc',{
		url:'/:entityId/detail',
		templateUrl:pathLogi+'edoc/detailEdoc.html',
		controller:'logistics.detailEdocCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('administrative/edocumentation/detail/byMessageId').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('logistics.edoc.inbox');
				},function(error){$state.go('logistics.edoc.inbox');}).$promise;
			}
		},
		data:{ displayName:'LB_REGISTRATION_DETAIL' }
	})
	
	// DIRECCION ADMINISTRATIVA - ARCHIVO
	.state('logistics.archive',{
		url:'/archive',
		template:'<div ui-view/>',
		data:{ displayName:'MENU_ARCHIVE' }
	})
	.state('logistics.archive.series',{
		url:'/series',
		templateUrl:pathLogi+'archive/series.html',
		controller:'archive.seriesCtrl',
		data:{ displayName:'LB_DOCUMENTARY_SERIES' }
	})
	.state('logistics.archive.periods',{
		url:'/periods',
		templateUrl:pathLogi+'archive/periods.html',
		controller:'archive.periodsCtrl',
		data:{ displayName:'LB_DOCUMENTARY_MANAGEMENT' }
	})
	.state('logistics.archive.configPeriods',{
		url:'/periods/:periodId/series/config',
		templateUrl:pathLogi+'archive/configPeriods.html',
		controller:'archive.configPeriodsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('administrative/archive/periods/series/byperiodid').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('logistics.archive.periods');
				},function(error){$state.go('logistics.archive.periods');}).$promise;
			}
		}
	})
	.state('logistics.archive.detailPeriods',{
		url:'/periods/:periodId/reviews/detail',
		templateUrl:pathLogi+'archive/detailPeriods.html',
		controller:'archive.detailPeriodsCtrl',
		data:{ displayName:'LB_ARCHIVE_FILE_REVIEWS' }
	})
	
	
	
	
	.state('logistics.archive.reviews',{
		url:'/reviews',
		templateUrl:pathLogi+'archive/reviews.html',
		controller:'archive.reviewsCtrl',
		data:{ displayName:'LB_ARCHIVE_FILE_REVIEWS' }
	})
	.state('logistics.archive.detailReviews',{
		url:'/reviews/:pserieId/periods/series/info',
		templateUrl:pathLogi+'archive/detailReviews.html',
		controller:'archive.detailReviewsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('administrative/archive/review/detail/pserieId').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('logistics.archive.periods');
				},function(error){$state.go('logistics.archive.periods');}).$promise;
			}
		}
	})
	
	
	
	.state('logistics.archive.shelvings',{
		url:'/shelvings',
		templateUrl:pathLogi+'archive/shelvings.html',
		controller:'archive.shelvingsCtrl',
		data:{ displayName:'LB_SHELVINGS' }
	})
	.state('logistics.archive.boxes',{
		url:'/boxes',
		templateUrl:pathLogi+'archive/boxes.html',
		controller:'archive.boxesCtrl',
		data:{ displayName:'LB_ARCHIVE_BOXES' }
	})
	.state('logistics.archive.folders',{
		url:'/folders',
		templateUrl:pathLogi+'archive/folders.html',
		controller:'archive.foldersCtrl',
		data:{ displayName:'LB_ARCHIVE_FOLDERS' }
	})
	.state('logistics.archive.documentation',{
		url:'/folders/documentation',
		templateUrl:pathLogi+'archive/documentation.html',
		controller:'archive.documentationCtrl',
		data:{ displayName:'LB_ARCHIVE_DOCUMENTS' }
	})
	
	// SALA DE CAPACITACIONES
	.state('logistics.trainingroom',{
		url:'/trainingroom',
		templateUrl:pathLogi+'trainingroom.html',
		controller:'trainingroomCtrl'
	})
	.state('logistics.calendarTrainingroom',{
		url:'/calendar/trainingroom',
		templateUrl:pathPrev+'calendar.html',
		controller:'scheduleCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.getData('salacapacitaciones').query({type:'trainingroom'},function(json){
					return json
				},function(error){$state.go('main');}).$promise;
			}
		}
	})
	
	// SERVICIOS GENERALES
	.state('logistics.gservices',{
		url:'/gservices',
		template:'<div ui-view/>',
		data:{ displayName:'LB_GENERAL_SERVICES' }
	})
	// MANTENIMIENTO DE HERRAMEINTAS
	.state('logistics.gservices.typetools',{
		url:'/minortools/types',
		templateUrl:pathLogi+'gservices/typetools.html',
		controller:'typetoolsCtrl',
		data: { displayName:'LB_TYPES_OF_TOOLS' }
	})
	.state('logistics.gservices.minortools',{
		url:'/minortools',
		templateUrl:pathLogi+'gservices/minortools.html',
		controller:'minortoolsCtrl',
		data: { displayName:'LB_MINOR_TOOLS' }
	})
	.state('logistics.gservices.engineering',{
		url:'/minortools/maintenances',
		templateUrl:pathLogi+'gservices/minortools.maintenance.html',
		controller:'engineeringCtrl',
        data: { displayName:'LB_TOOL_MAINTENANCE' }
	})
	.state('logistics.gservices.engineeringEdit',{
		url:'/minortools/maintenances/:entityId/edit',
		templateUrl:pathLogi+'gservices/minortools.maintenance.edit.html',
		controller:'engineeringEditCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('administrative/gservices/minortools/maintenances/detailById').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					$state.go('logistics.gservices.engineering');
				},function(error){$state.go('logistics.gservices.engineering');}).$promise;
			}
		},
        data: { displayName:'LB_TOOL_MAINTENANCE' }
	})
	// UNIDADES
	.state('logistics.gservices.units',{
		url:'/units',
		templateUrl:pathLogi+'gservices/units.html',
		controller:'unitsCtrl',
        data: { displayName:'LB_AUTOMOTIVE_PARK' }
	})
	.state('logistics.gservices.detailUnits',{
		url:'/units/:id/detail',
		templateUrl:pathLogi+'gservices/detailUnits.html',
		controller:'detailUnitsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('unidades/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('logistics.units');
				},function(error){$state.go('logistics.units');}).$promise;
			}
		},
        data: { displayName:'LB_AUTOMOTIVE_PARK' }
	})
	.state('logistics.gservices.mobilizationorder',{
		url:'/mobilization/order',
		templateUrl:pathLogi+'gservices/mobilizationorder.html',
		controller:'mobilizationorderCtrl',
        data: { displayName:'LB_MOBILIZATION_ORDER' }
	})
	.state('logistics.gservices.maintenanceorder',{
		url:'/maintenances/order',
		templateUrl:pathLogi+'gservices/maintenanceorder.html',
		controller:'maintenanceorderCtrl',
        data: { displayName:'LB_MAINTENANCE_ORDERS' }
	})
	.state('logistics.gservices.detailMaintenanceorder',{
		url:'/maintenances/order/:entityId/detail',
		templateUrl:pathLogi+'gservices/detailMaintenanceorder.html',
		controller:'detailMaintenanceorderCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('administrative/gservices/maintenances/order/detailById').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('logistics.gservices.maintenanceorder');
				},function(error){$state.go('logistics.gservices.maintenanceorder');}).$promise;
			}
		},
        data: { displayName:'LB_MAINTENANCE_ORDERS' }
	})
	.state('logistics.gservices.reportmaintenance',{
		url:'/maintenances/reports',
		templateUrl:pathLogi+'gservices/reportmaintenance.html',
		controller:'reportmaintenanceCtrl',
        data: { displayName:'LB_MAINTENANCE_REPORTS' }
	})
	.state('logistics.gservices.fuelorder',{
		url:'/fuelorder',
		templateUrl:pathLogi+'gservices/fuelorder.html',
		controller:'fuelorderCtrl',
        data: { displayName:'LB_FUEL_ORDER' }
	})
	.state('logistics.gservices.workorder',{
		url:'/work/order',
		templateUrl:pathLogi+'gservices/workorder.html',
		controller:'workorderCtrl',
        data: { displayName:'LB_WORK_ORDER' }
	})
	
	// MANTENIMIENTOS VEHICULARES
	.state('logistics.maintenance',{
		url:'/maintenances',
		template:'<div ui-view/>',
		data:{ displayName:'LB_VEHICULAR_MAINTENANCES' }
	})
	.state('logistics.maintenance.resourcesabc',{
		url:'/resourcesabc',
		templateUrl:pathLogi+'gservices/resourcesabc.html',
		controller:'resourcesabcCtrl',
        data: { displayName:'LB_SPARE_PARTS_AND_LABOR' }
	})
	
	.state('logistics.maintenance.plansabc',{
		url:'/plansabc',
		templateUrl:pathLogi+'gservices/plansabc.html',
		controller:'plansabcCtrl',
		data: { displayName:'LB_MAINTENANCE_MODELS' }
	})
	.state('logistics.maintenance.rulesabc',{
		url:'/rulesabc',
		templateUrl:pathLogi+'gservices/rulesabc.html',
		controller:'rulesabcCtrl',
		data: { displayName:'LB_PARAMETRIZATION' }
	})
	.state('logistics.maintenance.maintenances',{
		url:'/maintenances',
		templateUrl:pathLogi+'gservices/maintenances.html',
		controller:'maintenancesCtrl'
	})
	.state('logistics.maintenance.detailMaintenances',{
		url:'/maintenances/:id',
		templateUrl:pathLogi+'gservices/detailMaintenances.html',
		controller:'detailMaintenancesCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('mantenimientosvehiculares/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('logistics.maintenances');
				},function(error){$state.go('logistics.maintenances');}).$promise;
			}
		}
	})
	.state('logistics.maintenance.premaintenances',{
		url:'/premaintenances',
		templateUrl:pathLogi+'gservices/premaintenances.html',
		controller:'premaintenancesCtrl'
	})
	.state('logistics.maintenance.vehicularreview',{
		url:'/vehicularreview',
		templateUrl:pathLogi+'gservices/vehicularreview.html',
		controller:'vehicularreviewCtrl'
	})
	.state('logistics.maintenance.supplying',{
		url:'/supplying',
		templateUrl:pathLogi+'gservices/supplying.html',
		controller:'supplyingCtrl',
        data: { displayName:'LB_VEHICLES' }
	}) 
	
	// DIRECCION FINANCIERA
	.state('financial',{
		url:'/financial',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
		data: { displayName:'MENU_FINANCIAL_MANAGEMENT' }
	})
	// EXENTOS DE PAGO
	.state('financial.exemptpayment',{
		url:'/exemptpayment',
		templateUrl:pathFinc+'exemptpayment.html',
		controller:'exemptpaymentCtrl',
        data: { displayName:'LB_EXEMPT_FROM_PAYMENT' }
	})
	// RECAUDACIÓN
	.state('financial.collection',{
		url:'/collection',
		template:'<div ui-view/>',
        data: { displayName:'MENU_RECAUDATION' }
	})
	.state('financial.collection.species',{
		url:'/species',
		templateUrl:pathFinc+'collection/species.html',
		controller:'collection.speciesCtrl',
        data: { displayName:'LB_SPECIES' }
	})
	.state('financial.collection.invoices',{
		url:'/invoices',
		templateUrl:pathFinc+'collection/invoices.html',
		controller:'collection.invoicesCtrl',
        data: { displayName:'LB_ORDERS' }
	})
	.state('financial.collection.dailyforms',{
		url:'/dailyforms',
		templateUrl:pathFinc+'collection/dailyforms.html',
		controller:'collection.dailyformsCtrl',
        data: { displayName:'LB_FORMS' }
	})
	// CIERRES DE CAJA
	.state('financial.collection.arching',{
		url:'/arching',
		template:'<div ui-view/>',
        data: { displayName:'LB_ARCHING' }
	})
	.state('financial.collection.arching.archingspecies',{
		url:'/arching/species',
		templateUrl:pathFinc+'collection/arching.species.html',
		controller:'arching.speciesCtrl',
        data: { displayName:'LB_SPECIES' }
	})
	.state('financial.collection.arching.archinginvoices',{
		url:'/arching/invoices',
		templateUrl:pathFinc+'collection/arching.invoices.html',
		controller:'arching.invoicesCtrl',
        data: { displayName:'LB_ORDERS' }
	})
	// PARAMETRIZACION
	.state('financial.budgetclassifier',{
		url:'/budgetclassifier',
		templateUrl:pathFinc+'budgetclassifier.html',
		controller:'financial.budgetclassifierCtrl'
	})
	.state('financial.accountcatalog',{
		url:'/accountcatalog',
		templateUrl:pathFinc+'accountcatalog.html',
		controller:'financial.accountcatalogCtrl'
	})
	
	.state('financial.financialentities',{
		url:'/financialentities',
		templateUrl:pathFinc+'financialentities.html',
		controller:'financial.financialentitiesCtrl'
	})
	
	.state('financial.programs',{
		url:'/programs',
		templateUrl:pathFinc+'programs.html',
		controller:'financial.programsCtrl'
	})
	.state('financial.subprograms',{
		url:'/subprograms',
		templateUrl:pathFinc+'subprograms.html',
		controller:'financial.subprogramsCtrl'
	})
	.state('financial.projects',{
		url:'/projects',
		templateUrl:pathFinc+'projects.html',
		controller:'financial.projectsCtrl'
	})
	.state('financial.activities',{
		url:'/activities',
		templateUrl:pathFinc+'activities.html',
		controller:'financial.activitiesCtrl'
	})
	
	.state('financial.retentionclassifier',{
		url:'/retentionclassifier',
		templateUrl:pathFinc+'retentionclassifier.html',
		controller:'financial.retentionclassifierCtrl'
	})
	
	.state('financial.typedocuments',{
		url:'/typedocuments',
		templateUrl:pathFinc+'typedocuments.html',
		controller:'financial.typedocumentsCtrl'
	})
	// CONTROL PREVIO
	.state('financial.contractingprocedures',{
		url:'/contractingprocedures',
		templateUrl:pathColl+'contractingprocedures.html',
		controller:'financial.contractingproceduresCtrl'
	})
	.state('financial.justificationrequirements',{
		url:'/justificationrequirements',
		templateUrl:pathColl+'justificationrequirements.html',
		controller:'financial.justificationrequirementsCtrl'
	})
	.state('financial.processcontracts',{
		url:'/processcontracts',
		templateUrl:pathColl+'processcontracts.html',
		controller:'financial.processcontractsCtrl'
	})
	
	// DIRECCION DE PLANIFICACION
	.state('planing',{
		url:'/planing',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
		data: { displayName:'MENU_PLANING_DIRECTION' }
	})
	.state('planing.programs',{
		url:'/programs',
		templateUrl:pathPlan+'programs.html',
		controller:'planing.programsCtrl',
        data: { displayName:'TOOLBAR_POA_PROGRAMS' }
	})
	.state('planing.poa',{
		url:'/poa',
		templateUrl:pathPlan+'poa.html',
		controller:'planing.poaCtrl',
        data: { displayName:'LB_POA' }
	})
	.state('planing.detailPoa',{
		url:'/poa/:poaId/reforms/detail',
		templateUrl:pathPlan+'detailPoa.html',
		controller:'planing.detailPoaCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('planing/poa/detail/byId').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('planing.poa');
				},function(error){$state.go('planing.poa');}).$promise;
			}
		},
        data: { displayName:'LB_POA_PROJECTS' }
	})
	
	/*
	 * UNIDAD DE PREVENCION
	 */
	.state('prevention',{
		url:'/prevention',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_PREVENTION' }
	})
	// INSPECCIONES
	.state('prevention.inspections',{
		url:'/inspections',
		template:'<div ui-view/>',
		data:{ displayName:'LB_INSPECTIONS' }
	})
	.state('prevention.inspections.forms',{
		url:'/forms',
		templateUrl:pathPrev+'inspections/forms.html',
		controller:'prevention.formsCtrl',
			data:{ displayName:'LB_INSPECTION_FORMS' }
	})
	.state('prevention.inspections.sweeps',{
		url:'/sweeps',
		templateUrl:pathPrev+'inspections/sweeps.html',
		controller:'sweepsCtrl',
		data:{ displayName:'LB_SWEEPS' }
	})
	.state('prevention.inspections.inspections',{
		url:'/inspections',
		templateUrl:pathPrev+'inspections/inspections.html',
		controller:'inspectionsCtrl',
		data:{ displayName:'TOOLBAR_INSPECTIONS' }
	})
	.state('prevention.inspections.detailInspections',{
		url:'/inspections/:id/detail',
		templateUrl:pathPrev+'inspections/detailInspections.html',
		controller:'detailInspectionsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				$stateParams.type='DETAIL';
				return myResource.sendData('inspecciones/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.inspections.inspections');
				},function(error){$state.go('prevention.inspections.inspections');}).$promise;
			}
		},
		data:{ displayName:'LB_INSPECTIONS' }
	})
	.state('prevention.inspections.preinspection',{
		url:'/inspections/:id/preinspection',
		templateUrl:pathPrev+'inspections/preinspection.html',
		controller:'preinspectionCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				$stateParams.type='PREINSPECCION';
				return myResource.sendData('inspecciones/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.inspections.inspections');
				},function(error){$state.go('prevention.inspections.inspections');}).$promise;
			}
		},
		data:{ displayName:'LB_DO_INSPECTION' }
	})
	.state('prevention.inspections.inspection',{
		url:'/inspections/:id/inspection',
		templateUrl:pathPrev+'inspections/inspection.html',
		controller:'inspectionCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				$stateParams.type='INSPECCION';
				return myResource.sendData('inspecciones/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.inspections.inspections');
				},function(error){$state.go('prevention.inspections.inspections');}).$promise;
			}
		},
		data:{ displayName:'LB_DO_INSPECTION' }
	})
	.state('prevention.inspections.citations',{
		url:'/citations',
		templateUrl:pathPrev+'inspections/citations.html',
		controller:'citationsCtrl',
		data:{ displayName:'LB_CITATIONS' }
	}) 
	.state('prevention.inspections.selfprotectionsplans',{
		url:'/selfprotectionsplans',
		templateUrl:pathPrev+'inspections/selfprotectionsplans.html',
		controller:'selfprotectionsplansCtrl',
		data:{ displayName:'LB_EMERGENCY_PLANS' }
	}) 
	.state('prevention.inspections.detailSelfprotectionsplans',{
		url:'/selfprotectionsplans/:id/detail',
		templateUrl:pathPrev+'inspections/detailSelfprotectionsplans.html',
		controller:'detailSelfprotectionsplansCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/selfprotections/detail/byId').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.inspections.selfprotectionsplans');
				},function(error){$state.go('prevention.inspections.selfprotectionsplans');}).$promise;
			}
		},
		data:{ displayName:'LB_EMERGENCY_PLANS' }
	})
	// PRÓRROGAS
	.state('prevention.inspections.extensions',{
		url:'/extensions',
		templateUrl:pathPrev+'inspections/extensions.html',
		controller:'extensionsCtrl',
		data:{ displayName:'LB_EXTENSIONS' }
	}) 
	.state('prevention.inspections.newExtension',{
		url:'/:inspectionId/extensions',
		templateUrl:pathPrev+'inspections/newExtension.html',
		controller:'newExtensionCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('prorrogas/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.inspections.extensions');
				},function(error){$state.go('prevention.inspections.extensions');}).$promise;
			}
		},
		data:{ displayName:'LB_EXTENSIONS' }
	})
	// PERMISOS OCASIONALES DE FUNCIONAMIENTO
	.state('prevention.occasionals',{
		url:'/occasionals',
		template:'<div ui-view/>',
		data:{ displayName:'LB_OCCASIONALS' }
	})
	.state('prevention.occasionals.resourcespof',{
		url:'/resourcespof',
		templateUrl:pathPrev+'occasionals/resourcespof.html',
		controller:'resourcespofCtrl'
	})
	.state('prevention.occasionals.rulespof',{
		url:'/rulespof',
		templateUrl:pathPrev+'occasionals/rulespof.html',
		controller:'rulespofCtrl'
	})
	.state('prevention.occasionals.occasionals',{
		url:'/occasionals',
		templateUrl:pathPrev+'occasionals/occasionals.html',
		controller:'occasionalsCtrl'
	})
	.state('prevention.occasionals.detailOccasionals',{
		url:'/occasionals/:id/detail',
		templateUrl:pathPrev+'occasionals/detailOccasionals.html',
		controller:'detailOccasionalsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('ocasionales/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.occasionals.occasionals');
				},function(error){$state.go('prevention.occasionals.occasionals');}).$promise;
			}
		}
	})
	.state('prevention.occasionals.calendarOccasionals',{
		url:'/calendar/occasionals',
		templateUrl:pathPrev+'calendar.html',
		controller:'calendarCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.sendData('calendar/REQUEST').save({type:'occasionals'},function(json){
					return json
				},function(error){$state.go('main');}).$promise;
			}
		}
	})
	// VISTO BUENO DE PLANOS
	.state('prevention.vbp',{
		url:'/vbp',
		template:'<div ui-view/>',
		data:{ displayName:'LB_CONSTRUCTION_PERMITS' }
	})
	.state('prevention.vbp.vbp',{
		url:'/approval',
		templateUrl:pathPrev+'vbp/vbp.html',
		controller:'vbpCtrl',
		data:{ displayName:'LB_VBP' }
	})
	.state('prevention.vbp.detailVbp',{
		url:'/approval/:id',
		templateUrl:pathPrev+'vbp/detailVbp.html',
		controller:'detailVbpCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('vbp/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.vbp.vbp');
				},function(error){$state.go('prevention.vbp.vbp');}).$promise;
			}
		},
		data:{ displayName:'LB_VBP' }
	})
	.state('prevention.vbp.modifications',{
		url:'/modifications',
		templateUrl:pathPrev+'vbp/modifications.html',
		controller:'modificationsCtrl',
		data:{ displayName:'LB_MODIFICATION_PLANS' }
	})
	.state('prevention.vbp.habitability',{
		url:'/habitability',
		templateUrl:pathPrev+'vbp/habitability.html',
		controller:'habitabilityCtrl',
		data:{ displayName:'LB_HABITABILITY' }
	})
	
	// PERMISOS DE USO DE GLP
	.state('prevention.glp',{
		url:'/glp',
		template:'<div ui-view/>',
		data:{ displayName:'LB_GLP_USE_PERMITS' }
	})
	.state('prevention.glp.resourcesglpt',{
		url:'/resourcesglpt',
		templateUrl:pathPrev+'glp/resourcesglpt.html',
		controller:'resourcesglptCtrl'
	})
	.state('prevention.glp.transport',{
		url:'/transport/permits',
		templateUrl:pathPrev+'glp/transport.html',
		controller:'glp.transportCtrl',
		data:{ displayName:'LB_GLP_TRANSPORT' }
	})
	.state('prevention.glp.detailTransport',{
		url:'/transport/:transportId/detail',
		templateUrl:pathPrev+'glp/detailTransport.html',
		controller:'glp.detailTransportCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/glp/transport/detail/byId').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.glp.transport');
				},function(error){$state.go('prevention.glp.transport');}).$promise;
			}
		},
		data:{ displayName:'LB_GLP_TRANSPORT' }
	})
	.state('prevention.glp.reviewTransport',{
		url:'/transport/:transportId/review',
		templateUrl:pathPrev+'glp/reviewTransport.html',
		controller:'glp.reviewTransportCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('prevention/glp/transport/review/byId').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.glp.transport');
				},function(error){$state.go('prevention.glp.transport');}).$promise;
			}
		},
		data:{ displayName:'LB_GLP_TRANSPORT' }
	}) 
	.state('prevention.glp.feasibility',{
		url:'/feasibility',
		templateUrl:pathPrev+'glp/feasibility.html',
		controller:'glp.feasibilityCtrl',
		data:{ displayName:'LB_GLP_FEASIBILITY' }
	}) 
	.state('prevention.glp.definitive',{
		url:'/definitive',
		templateUrl:pathPrev+'glp/definitive.html',
		controller:'glp.definitiveCtrl',
		data:{ displayName:'LB_GLP_DEFINITIVE' }
	})
	
	// CAPACITACIONES
	.state('prevention.trainings',{
		url:'/trainings',
		template:'<div ui-view/>',
		data:{ displayName:'LB_CITIZENSHIP_TRAINING' }
	})
	.state('prevention.trainings.poll',{
		url:'/questions',
		templateUrl:pathPrev+'trainings/questions.html',
		controller:'pollCtrl',
		data:{ displayName:'LB_POLL' }
	})
	.state('prevention.trainings.calendarTrainings',{
		url:'/calendar',
		templateUrl:pathPrev+'calendar.html',
		controller:'calendarCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.sendData('calendar/REQUEST').save({type:'trainings'},function(json){
					return json
				},function(error){$state.go('main');}).$promise;
			}
		},
		data:{ displayName:'LB_CALENDAR' }
	})
	.state('prevention.trainings.topics',{
		url:'/topics',
		templateUrl:pathPrev+'trainings/topics.html',
		controller:'topicsCtrl',
		data:{ displayName:'LB_TOPICS' }
	})
	.state('prevention.trainings.trainers',{
		url:'/trainers',
		templateUrl:pathPrev+'trainings/trainers.html',
		controller:'trainersCtrl',
		data:{ displayName:'LB_TRAINERS' }
	})
	.state('prevention.trainings.trainings',{
		url:'/trainings',
		templateUrl:pathPrev+'trainings/trainings.html',
		controller:'trainingsCtrl',
		data:{ displayName:'LB_TRAININGS' }
	})
	.state('prevention.trainings.detailTraining',{
		url:'/trainings/:id',
		templateUrl:pathPrev+'trainings/detailTraining.html',
		controller:'detailTrainingCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('training/REQUEST').save({id:$stateParams.id},function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.trainings.trainings');
				},function(error){$state.go('prevention.trainings.trainings');}).$promise;
			}
		},
		data:{ displayName:'LB_TRAININGS' }
	})
	// SIMULACROS
	.state('prevention.trainings.simulations',{
		url:'/simulations',
		templateUrl:pathPrev+'trainings/simulations.html',
		controller:'simulationsCtrl',
		data:{ displayName:'LB_SIMULATIONS' }
	})
	.state('prevention.trainings.detailSimulations',{
		url:'/simulations/:id',
		templateUrl:pathPrev+'trainings/detailSimulations.html',
		controller:'detailSimulationsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('simulacros/REQUEST').save({id:$stateParams.id},function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.trainings.simulations');
				},function(error){$state.go('prevention.trainings.simulations');}).$promise;
			}
		},
		data:{ displayName:'LB_SIMULATIONS' }
	})
	// CASAS ABIERTAS
	.state('prevention.trainings.stands',{
		url:'/stands',
		templateUrl:pathPrev+'trainings/stands.html',
		controller:'standsCtrl',
		data:{ displayName:'LB_STANDS' }
	})
	.state('prevention.trainings.detailStands',{
		url:'/stands/:id',
		templateUrl:pathPrev+'trainings/detailStands.html',
		controller:'detailStandsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('stands/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.trainings.stands');
				},function(error){$state.go('prevention.trainings.stands');}).$promise;
			}
		},
		data:{ displayName:'LB_STANDS' }
	})
	// VISITAS AL CBSD
	.state('prevention.trainings.visits',{
		url:'/visits',
		templateUrl:pathPrev+'trainings/visits.html',
		controller:'visitsCtrl',
		data:{ displayName:'LB_VISITS' }
	})
	.state('prevention.trainings.detailVisits',{
		url:'/visits/:id',
		templateUrl:pathPrev+'trainings/detailVisits.html',
		controller:'detailVisitsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('visitas/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('prevention.trainings.visits');
				},function(error){$state.go('prevention.trainings.visits');}).$promise;
			}
		},
		data:{ displayName:'LB_VISITS' }
	})
	
	// REGISTROS MIGRADOS
	.state('prevention.migratedvbp',{
		url:'/migrated/vbp',
		templateUrl:pathPrev+'migrated.vbp.html',
		controller:'migrated.vbpCtrl'
	})
	.state('prevention.migratedfeasibility',{
		url:'/migrated/feasibility',
		templateUrl:pathPrev+'migrated.feasibility.html',
		controller:'migrated.feasibilityCtrl'
	})
	.state('prevention.migrateddefinitive',{
		url:'/migrated/definitive',
		templateUrl:pathPrev+'migrated.definitive.html',
		controller:'migrated.definitiveCtrl'
	})
	// REGISTRO PERSONAL
	.state('prevention.myinspections',{
		url:'/profile/inspections',
		templateUrl:pathPrev+'profile.inspections.html',
		controller:'myinspectionsCtrl'
	})
	.state('prevention.mycitations',{
		url:'/profile/citations',
		templateUrl:pathPrev+'profile.citations.html',
		controller:'mycitationsCtrl'
	})
	.state('prevention.myglpTransport',{
		url:'/profile/glpTransport',
		templateUrl:pathPrev+'glpTransport.html',
		controller:'myglpTransportCtrl'
	})
	.state('prevention.myplans',{
		url:'/profile/plans',
		templateUrl:pathPrev+'plans.html',
		controller:'myplansCtrl'
	})
	.state('prevention.mytrainings',{
		url:'/profile/trainings',
		templateUrl:pathPrev+'trainings.html',
		controller:'mytrainingsCtrl'
	})
	// OTRAS ACTIVIDADES
	.state('prevention.officework',{
		url:'/officework',
		templateUrl:pathPrev+'officework.html',
		controller:'prevention.officeworkCtrl'
	})
	.state('prevention.usersupport',{
		url:'/usersupport',
		templateUrl:pathPrev+'usersupport.html',
		controller:'prevention.usersupportCtrl'
	})
	
	// MÓDULO - ADMINISTRACIÓN DE PERMISOS
	.state('src',{
		url:'/tps',
		templateUrl:pathUser+'tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] },
        data: { displayName:'MENU_PERMITS' }
	})
	.state('src.requirements',{
		url:'/requirements',
		templateUrl:pathSys+'requirements.html'
	})
	.state('src.mandatories',{
		url:'/mandatories',
		templateUrl:pathSys+'mandatories.html',
		controller:'mandatoriesCtrl'
	})
	.state('src.activities',{
		url:'/activities',
		templateUrl:pathSys+'activities.html',
		controller:'activitiesCtrl'
	})
	.state('src.detailActivities',{
		url:'/activities/:id',
		templateUrl:pathSys+'forms.html',
		controller:'detailActivitiesCtrl',
		resolve:{
			activity:function(myResource,$stateParams,$state){
				return myResource.sendData('actividades/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('src.activities');
				},function(error){$state.go('src.activities');}).$promise;
			}
		}
	})
	.state('src.taxes',{
		url:'/taxes',
		templateUrl:pathSys+'taxes.html',
		controller:'taxesCtrl'
	})
	.state('src.ciiu',{
		url:'/ciiu',
		templateUrl:pathSys+'ciiu.html',
		controller:'ciiuCtrl'
	})
	.state('src.detailCiiu',{
		url:'/ciiu/:id',
		templateUrl:pathSys+'forms.html',
		controller:'detailCiiuCtrl',
		resolve:{
			ciiu:function(myResource,$stateParams,$state){
				return myResource.sendData('ciiu/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('src.ciiu');
				},function(error){$state.go('src.ciiu');}).$promise;
			}
		}
	})
	.state('src.forms',{
		url:'/forms/:id',
		templateUrl:pathSys+'detailForms.html',
		controller:'formsCtrl',
		resolve:{
			form:function(myResource,$stateParams,$state){
				return myResource.getData('formularios').get({id:$stateParams.id,customQuery:'searchById'},function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('src.activities');
				},function(error){$state.go('src.activities');}).$promise;
			}
		}
	})
	.state('src.entities',{
		url:'/entities',
		templateUrl:pathSys+'entities.html',
		controller:'entitiesCtrl',
        data: { displayName:'TOOLBAR_ENTITIES' }
	})
	.state('src.detailEntities',{
		url:'/entities/:entityId/detail',
		templateUrl:pathSys+'detailEntities.html',
		controller:'detailEntitiesCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('permits/entities/detail/entityId').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('src.entities');
				},function(error){$state.go('src.entities');}).$promise;
			},
			locals:function(myResource,$stateParams){
				return myResource.requestData('permits/locals/list/entityId').save($stateParams,function(json){ return json.data; }).$promise;
			},
			trainings:function(myResource,$stateParams){
				return myResource.requestData('prevention/trainings/list/entityId').save($stateParams,function(json){ return json.data; }).$promise;
			},
			stands:function(myResource,$stateParams){
				return myResource.requestData('prevention/stands/list/entityId').save($stateParams,function(json){ return json.data; }).$promise;
			},
			visits:function(myResource,$stateParams){
				return myResource.requestData('prevention/visits/list/entityId').save($stateParams,function(json){ return json.data; }).$promise;
			},
			simulations:function(myResource,$stateParams){
				return myResource.requestData('prevention/simulations/list/entityId').save($stateParams,function(json){ return json.data; }).$promise;
			}
		},
        data: { displayName:'TOOLBAR_ENTITIES' }
	})
	.state('src.locals',{
		url:'/locals',
		templateUrl:pathSys+'locals.html',
		controller:'localsCtrl',
        data: { displayName:'TOOLBAR_LOCALS' }
	})
	.state('src.detailLocals',{
		url:'/locals/:localId/detail',
		templateUrl:pathSys+'detailLocals.html',
		controller:'detailLocalsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.requestData('permits/locals/detail/localId').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('src.locals');
				},function(error){$state.go('src.locals');}).$promise;
			},
			permits:function(myResource,$stateParams){
				return myResource.requestData('permits/permits/list/localId').save($stateParams,function(json){ return json.data; }).$promise;
			},
			inspections:function(myResource,$stateParams){
				return myResource.requestData('prevention/inspections/list/localId').save($stateParams,function(json){ return json; }).$promise;
			},
			plans:function(myResource,$stateParams){
				return myResource.requestData('prevention/plans/list/localId').save($stateParams,function(json){ return json.data; }).$promise;
			},
			extensions:function(myResource,$stateParams){
				return myResource.requestData('prevention/extensions/list/localId').save($stateParams,function(json){ return json.data; }).$promise;
			}
		},
        data: { displayName:'TOOLBAR_LOCALS' }
	})
	.state('src.permits',{
		url:'/permits',
		templateUrl:pathSys+'permits.html',
		controller:'permitsCtrl',
        data: { displayName:'TOOLBAR_PERMITS' }
	})
	.state('src.newPermit',{
		url:'/selfinspection/:code/new/permit',
		templateUrl:pathSys+'newPermit.html',
		controller:'newPermitCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('selfInspections/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('src.permits');
				},function(error){$state.go('src.permits');}).$promise;
			},
			permits:['myResource','$stateParams','$state',function(myResource,$stateParams,$state){
				return myResource.requestData('permits/permits/list/selfInspectionId').save($stateParams,function(json){
					return (json.estado===true)?json:{};
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}],
			info:['myResource','$stateParams','$state',function(myResource,$stateParams,$state){
				return myResource.requestData('permits/entity/detail/selfInspectionId').save($stateParams,function(json){
					return (json.estado===true)?json:{};
				},function(error){$state.go('permits.economicActivities');}).$promise;
			}]
		},
        data: { displayName:'LB_PERMIT_INFORMATION' }
	})
	.state('src.detailPermits',{
		url:'/permits/:id/detail',
		templateUrl:pathSys+'detailPermits.html',
		controller:'detailPermitsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('permisos/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('src.permits');
				},function(error){$state.go('src.permits');}).$promise;
			}
		},
        data: { displayName:'TOOLBAR_PERMITS' }
	})
	.state('src.duplicates',{
		url:'/duplicates',
		templateUrl:pathSys+'duplicates.html',
		controller:'duplicatesCtrl',
        data: { displayName:'TOOLBAR_DUPLICATES' }
	})
	.state('src.selfinspections',{
		url:'/selfinspections',
		templateUrl:pathSys+'selfinspections.html',
		controller:'selfinspectionsCtrl',
        data: { displayName:'TOOLBAR_SELFINSPECTIONS' }
	})
	;
	$locationProvider.html5Mode(true);
	$locationProvider.hashPrefix('!').html5Mode(true);
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
}])
;