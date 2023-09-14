// LOADING APP
setTimeout(function(){$('.loader').fadeOut();},2000);
//APP
var app=angular.module('subjefaturaApp',[
	'ngAnimate',
	'ngAria',
	'ngResource',
	'ngSanitize',
	'ngCookies',
	'ui.router',
	'ngMaterial',
	'angular-growl',
	'oitozero.ngSweetAlert',
	'angular-loading-bar',
	'pascalprecht.translate']);
app
.config(['$locationProvider','$urlRouterProvider','$stateProvider','$httpProvider','$translateProvider','cfpLoadingBarProvider','growlProvider',
    function($locationProvider,$urlRouterProvider,$stateProvider,$httpProvider,$translateProvider,cfpLoadingBarProvider,growlProvider){
	// Loading bar
	cfpLoadingBarProvider.latencyThreshold=0;
	// Notificaciones - growl
	growlProvider.onlyUniqueMessages(false).globalReversedOrder(true).globalTimeToLive(5000);
	// Translate
	$translateProvider
	.translations('es',appLabels.es).translations('en',appLabels.en)
	.determinePreferredLanguage(function(){return 'es';});
	// DEFINIR RUTAS PREVIAS A UTILIZAR
	$urlRouterProvider.otherwise('/');
	$stateProvider
	.state('main',{
		url:'/',
		templateUrl:pathMain+'login.html',
		controller:'mainCtrl'
	})
	.state('msg',{
		url:'/:msg',
		templateUrl:pathMain+'login.html',
		controller:'mainCtrl'
	});
	$locationProvider.html5Mode(true);
}])
.run(['$rootScope','$translate','$cookies',function($rootScope,$translate,$cookies){
	$rootScope.changeLanguage=function(lang){
		$cookies.put('lang',lang);
		$translate.use(lang);
	};
}])
.controller('mainCtrl',['$scope','$resource','$cookies','notify','principal',function($scope,$resource,$cookies,notify,principal){
	// SLIDES
	$scope.slides={};
	$resource(host_remote+'resources/slides/subjefatura').get(function(json){$scope.slides=json.data;}).$promise;
	// DATOS DE REGISTRO - BACKEND
	$scope.frmLogin={mod:'subjefatura'};
	$scope.request={};
	$scope.submitForm=function(){
		$scope.frmLogin.opt='LogIn';
		$resource(host_name+sys_name+'/personaloperativo/REQUEST').save($scope.frmLogin,function(json){
			// VALIDAR LOGIN CORRECTO
			if(json.estado===true){
				// REGISTRAR ID DE SESION
				setSession(json.sessionId);
				// here, we fake authenticating and give a fake user
		        principal.authenticate(json);
			} else notify(json);
			// VALIDAR SI ES NECESARIO EL CAMBIO DE CONTRASEÑA
			if(json.pass) $scope.request=json;
		},function(json){notify(json)});
	};
	// REGISTRAR SESION
	function setSession(id){
		$cookies.put('userID',id);
		$cookies.put(sys_name,id);
		window.location.assign("./");
	}
}])
.service('notify',function(){
	return function(json){
		var mensaje = (angular.isArray(json)||angular.isObject(json))?json.mensaje:json;
		swal({type:"info",title:json.estado?"Correcto":"Error",text:mensaje,html:true});
	}
})
.factory('principal',['$q','$http','$timeout',function($q,$http,$timeout){
    var _identity = undefined, _authenticated = false;
    return {
    	isIdentityResolved: function() {
    		return angular.isDefined(_identity);
    	},
    	isAuthenticated: function() {
    		return _authenticated;
    	},
    	authenticate: function(identity) {
    		_identity = identity;
    		_authenticated = identity != null;
    	}
    };
}])
;