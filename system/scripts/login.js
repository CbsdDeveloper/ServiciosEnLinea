// LOADING APP
setTimeout(function(){ $('.loader').fadeOut(); },2000);
// APP
var app=angular.module('systemApp',[
	'ngResource',
	'ui.router',
	'ngCookies',
	'angular-loading-bar',
	'pascalprecht.translate']);
app
.config(['$locationProvider','$stateProvider','$urlRouterProvider','$translateProvider',
    function($locationProvider,$stateProvider,$urlRouterProvider,$translateProvider){
	// Translate
	$translateProvider
	.translations('es',appLabels.es)
	.translations('en',appLabels.en)
	.determinePreferredLanguage(function (){return 'es';});
	// DEFINIR RUTAS PREVIAS A UTILIZAR
	$urlRouterProvider.otherwise('/');
	$stateProvider
	.state('main',{
		url: '/',
		templateUrl: pathUser+'login.html',
		controller: 'mainCtrl'
	})
	.state('restore',{
		url: '/restore',
		templateUrl: pathUser+'restore.html',
		controller: 'restoreCtrl'
	})
	;
	$locationProvider.html5Mode(true);
}])
.controller('mainCtrl',['$scope','$resource','$cookies','notify','principal',function($scope,$resource,$cookies,notify,principal){
	// DECLARACION DE OBJETOS DE LOS FORMULARIOS
	$scope.frmLogin = {};
	$scope.request = {};
	// ENVIAR PETICION DE LOGIN
	$scope.submitForm=function(){
		// CONSULTAR A WS
		$resource('/app/system/postLogin').save($scope.frmLogin,function(json){
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
		window.location.assign("./v3/");
	}
	// VER AYUDA - POST LOGIN
	$scope.openInstructions=function(){ myResource.myDialog.swalAlert($scope.request.info); };
}])

.controller('restoreCtrl',['$scope','$resource','$cookies','notify','principal',function($scope,$resource,$cookies,notify,principal){
	// DECLARACION DE OBJETOS DE LOS FORMULARIOS
	$scope.frmLogin = {};
	// REINICIO DE CONTRASEÑA
	$scope.resetPwd=function(){
		// PARAMETRO PARA RESTABLECER CONRASEÑA
		$scope.frmLogin.opt='resetPwd';
		// ENVIAR FORMULARIO
		$resource('/app/system/recovery/REQUEST').save($scope.frmLogin,function(json){
			// VALIDAR RESPUESTA DE BACKEND
			if(json.estado===true)$scope.frmLogin={};
			// PRESENTAR MENSAJE
			notify(json.data);
		},function(json){notify(json)});
	};
}])

.run(function($rootScope,$translate){
	$rootScope.changeLanguage = function(lang){
		$translate.use(lang);
	};
})
.directive('iFa',function(){
    return { template: '<i class="fa fa-{{i}} fa-{{s}} {{c}}"></i>',scope: { i:"@",s:"@",c:"@" } };
})
.service('notify',function(){
	return function(json){
		var mensaje = (angular.isArray(json)||angular.isObject(json))?json.mensaje:json;
		swal({
			type:(json.estado=='info'?'info':(json.estado===true?'success':'error')),
			title:(json.estado=='info'?'¡Atención!':(json.estado===true?'¡Correcto!':'Error')),
			text:mensaje,
			html:true
		});
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