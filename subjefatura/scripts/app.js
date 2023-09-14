setTimeout(function(){$('.loader').fadeOut();},1500);
'use strict';
/**
 * CONFIGURACIÓN DEL MÓDULO AngularJS
 */
var app=angular.module('subjefaturaApp',[
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
	// Notificaciones - growl
	growlProvider.onlyUniqueMessages(false).globalReversedOrder(true).globalTimeToLive(5000);
	// Angular Moment Picker
	momentPickerProvider.options({locale:'es',format:'YYYY-MM-DD HH:mm',today:true,startView:'month'});
	// Translate
	$translateProvider.translations('es',appLabels.es).translations('en',appLabels.en).determinePreferredLanguage(function(){return 'es';});
	// Extend the red theme with a few different colors
    var geoBlue=$mdThemingProvider.extendPalette('red',{'500':'f39c12'});
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
        data: { displayName:'MENU_SUBJEFATURE' }
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
	
	.state('main.guards',{
		url:'guards',
		templateUrl:pathModule+'guards.html',
		controller:'guardsCtrl',
        data: { displayName:'LB_GUARDS' }
	})
	.state('main.binnacle',{
		url:'binnacle',
		templateUrl:pathIncApp+'subjefature/binnacle.html',
		controller:'binnacleCtrl',
        data: { displayName:'LB_NEWS_BOOK' }
	})
	.state('main.tracking',{
		url:'tracking',
		templateUrl:pathIncApp+'subjefature/tracking.html',
		controller:'trackingCtrl',
        data: { displayName:'LB_VEHICULAR_FLEETS' }
	})
	.state('main.parts',{
		url:'parts',
		templateUrl:pathIncApp+'subjefature/parts.html',
		controller:'partsCtrl',
        data: { displayName:'LB_PARTS' }
	})
	.state('main.newPart',{
		url:'parts/new/:key',
		templateUrl:pathModule+'newPart.html',
		controller:'newPartCtrl',
        data: { displayName:'LB_PARTS' }
	})
	.state('main.newPart.draft',{
		url:'/draft',
		templateUrl:pathModule+'newPart.step1.html',
		controller:'newPart.step1Ctrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('partes/REQUEST').save($stateParams,function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('main.parts');
				},function(error){$state.go('main.parts');}).$promise;
			}
		},
        data: { displayName:'LB_NEWPART_STEP1' }
	})
	.state('main.newPart.step1',{
		url:'/step1/:entityId',
		templateUrl:pathModule+'newPart.step1.html',
		controller:'newPart.step1Ctrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('partes/REQUEST').save({entityId:$stateParams.entityId,step:1},function(json){
					if(json.estado===true) return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('main.parts');
				},function(error){$state.go('main.parts');}).$promise;
			}
		},
        data: { displayName:'LB_NEWPART_STEP1' }
	})
	.state('main.newPart.step2',{
		url:'/step2/:entityId',
		templateUrl:pathModule+'newPart.step2.html',
		controller:'newPart.step2Ctrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('partes/REQUEST').save({entityId:$stateParams.entityId,step:2},function(json){
					if(json.estado) return json;
					myResource.myDialog.showNotify(json);
					$state.go('main.parts');
				},function(error){$state.go('main.parts');}).$promise;
			}
		},
        data: { displayName:'LB_NEWPART_STEP2' }
	})
	.state('main.newPart.step3',{
		url:'/step3/:entityId',
		templateUrl:pathModule+'newPart.step3.html',
		controller:'newPart.step3Ctrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('partes/REQUEST').save({entityId:$stateParams.entityId,step:3},function(json){
					if(json.estado) return json;
					myResource.myDialog.showNotify(json);
					$state.go('main.parts');
				},function(error){$state.go('main.parts');}).$promise;
			}
		},
        data: { displayName:'LB_NEWPART_STEP3' }
	})
	.state('main.detailPart',{
		url:'part/:id/view',
		templateUrl:pathModule+'newPart.html',
		controller:'newPartCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('partes/REQUEST').save($stateParams,function(json){
					if(json.estado) return json;
					myResource.myDialog.showNotify(json);
					$state.go('main.parts');
				},function(error){$state.go('main.parts');}).$promise;
			}
		},
        data: { displayName:'LB_DETAIL' }
	})
	.state('main.aphsupplies',{
		url:'aph/supplies',
		templateUrl:pathModule+'aphsupplies.html',
		controller:'aph.aphsuppliesCtrl',
        data: { displayName:'LB_PREHOSPITAL_MATERIALS' }
	})
	.state('main.fuelvouchers',{
		url:'fuelvouchers',
		templateUrl:pathModule+'fuelorder.html',
		controller:'fuelvouchersCtrl',
        data: { displayName:'LB_FUEL_VOUCHERS' }
	})
	
	// OTRAS ESTACIONES
	.state('records',{
		url:'/records/',
		templateUrl:pathIncApp+'main/tps.html',
		controller:'mainCtrl',
		resolve:{ auth:['authorization',function(authorization){ return authorization.authorize(); }] }
	})
	.state('records.calendar',{
		url:'calendar',
		templateUrl:pathModule+'calendar.html',
		controller:'calendarCtrl',
		resolve:{
			calendar:function(myResource,$stateParams,$state){
				return myResource.sendData('schedule/REQUEST').save({type:'tthh'},function(json){
					return json
				},function(error){$state.go('main');}).$promise;
			}
		},
        data: { displayName:'LB_CALENDAR' }
	})
	
	.state('records.stations',{
		url:'stations',
		templateUrl:pathModule+'stations.html',
		controller:'stationsCtrl',
        data: { displayName:'LB_STATIONS' }
	})
	.state('records.detailStations',{
		url:'/stations/:id/detail',
		templateUrl:pathModule+'detailStations.html',
		controller:'detailStationsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('estaciones/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json;
					myResource.myDialog.showNotify(json);
					$state.go('records.stations');
				},function(error){$state.go('records.stations');}).$promise;
			}
		},
		data:{ displayName:'LB_STATIONS' }
	})
	
	.state('records.distribution',{
		url:'distribution',
		templateUrl:pathModule+'distribution.html',
		controller:'distributionCtrl',
        data: { displayName:'LB_DISTRIBUTIVE' }
	})
	
	.state('records.units',{
		url:'units',
		templateUrl:pathModule+'units.html',
		controller:'unitsCtrl',
        data: { displayName:'LB_AUTOMOTIVE_PARK' }
	})
	.state('records.detailUnits',{
		url:'/units/:id/detail',
		templateUrl:pathModule+'detailUnits.html',
		controller:'detailUnitsCtrl',
		resolve:{
			entity:function(myResource,$stateParams,$state){
				return myResource.sendData('unidades/REQUEST').save($stateParams,function(json){
					if(json.estado===true)return json.data;
					myResource.myDialog.showNotify(json);
					$state.go('records.units');
				},function(error){$state.go('records.units');}).$promise;
			}
		},
        data: { displayName:'LB_AUTOMOTIVE_PARK' }
	})
	
	.state('records.fuelvouchers',{
		url:'fuelvouchers',
		templateUrl:pathModule+'fuelorder.html',
		controller:'records.fuelvouchersCtrl'
	})
	.state('records.guards',{
		url:'guards',
		templateUrl:pathModule+'guards.html',
		controller:'records.guardsCtrl'
	})
	.state('records.binnacle',{
		url:'binnacle',
		templateUrl:pathIncApp+'subjefature/binnacle.html',
		controller:'records.binnacleCtrl'
	})
	.state('records.tracking',{
		url:'tracking',
		templateUrl:pathIncApp+'subjefature/tracking.html',
		controller:'records.trackingCtrl'
	})
	.state('records.parts',{
		url:'parts',
		templateUrl:pathIncApp+'subjefature/parts.html',
		controller:'records.partsCtrl'
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