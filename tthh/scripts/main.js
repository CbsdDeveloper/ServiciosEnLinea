// LOADING APP
setTimeout(function(){$('.loader').fadeOut();},2000);
//APP
var app=angular.module('tthhApp',[
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
	.determinePreferredLanguage(function(){return 'es';});
	// DEFINIR RUTAS PREVIAS A UTILIZAR
	$urlRouterProvider.otherwise('/');
	$stateProvider
	.state('main',{
		url:'/',
		templateUrl:pathMain+'login.html',
		controller:'mainCtrl'
	})
	.state('requested',{
		url:'/requested/:msgId/',
		templateUrl:pathMain+'login.html',
		controller:'mainCtrl'
	})
	.state('restore',{
		url: '/restore/',
		templateUrl:pathMain+'restore.html',
		controller:'restoreCtrl'
	})
	;
	$locationProvider.html5Mode(true);
}])
.controller('mainCtrl',['$scope','$resource','$cookies','notify','principal','$stateParams',function($scope,$resource,$cookies,notify,principal,$stateParams){
	
	// SLIDES
	$scope.slides={};
	$resource(host_remote+'resources/slides/tthh').get(function(json){$scope.slides=json.data;}).$promise;
	
	// VAERIFICAR MENSAJE DESDE BACKEND
	if((typeof $stateParams.msgId!=='undefined' && $stateParams.msgId!==null && $stateParams.msgId!=={})){
		notify({
			mensaje: $stateParams.msgId,
		   	estado: "info"
		});
	}
	
	// DATOS DE REGISTRO - BACKEND
	$scope.frmLogin={mod:'tthh'};
	$scope.request={};
	$scope.submitForm=function(){
		$scope.frmLogin.opt='LogIn';
		$resource(host_name+sys_name+'/personal/REQUEST').save($scope.frmLogin,function(json){
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
	
	// VER AYUDA
	$scope.openInstructions=function(){
		notify($scope.request.info);
	};
	
}])
.controller('restoreCtrl',['$scope','$resource','notify',function($scope,$resource,notify){
	// SLIDES
	$scope.slides={};
	$resource(host_remote+'resources/slides/tthh').get(function(json){$scope.slides=json.data;}).$promise;
	// DECLARACION DE OBJETOS DE LOS FORMULARIOS
	$scope.frmLogin = {};
	// REINICIO DE CONTRASEÑA
	$scope.submitForm=function(){
		// PARAMETRO PARA RESTABLECER CONRASEÑA
		$scope.frmLogin.opt='resetPwd';
		// ENVIAR FORMULARIO
		$resource(host_name+sys_name+'/recovery/REQUEST').save($scope.frmLogin,function(json){
			// VALIDAR RESPUESTA DE BACKEND
			if(json.data.estado===true)$scope.frmLogin={};
			// PRESENTAR MENSAJE
			notify(json.data);
		},function(json){notify(json)});
	};
	
}])

.run(['$rootScope','$translate','$cookies',function($rootScope,$translate,$cookies){
	$rootScope.changeLanguage=function(lang){
		$cookies.put('lang',lang);
		$translate.use(lang);
	};
}])
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