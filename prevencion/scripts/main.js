// LOADING APP
setTimeout(function(){
	$('.loader').fadeOut();
	var margin=$('#footer').outerHeight(true);
	$('body').css('margin-bottom',margin);
},2000);

// APP
var app=angular.module('prevencionApp',[
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

// LISTADO DE RUTAS ACCESIBLES
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
		url: '/',
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
	.state('signup',{
		url: '/signup/',
		templateUrl:pathMain+'signup.html',
		controller:'signupCtrl'
	})
	.state('signup_new',{
		url: '/signup_new/',
		templateUrl:pathMain+'signup_new.html',
		controller:'signup_newCtrl'
	})
	;
	$locationProvider.html5Mode(true);
}])
.controller('mainCtrl',['$scope','$stateParams','myResource','principal',function($scope,$stateParams,myResource,principal){
	
	// SLIDES
	$scope.slides={};
	myResource.requestData('resources/slides/ai').json(function(json){$scope.slides=json.data;}).$promise;
	
	// VAERIFICAR MENSAJE DESDE BACKEND
	if((typeof $stateParams.msgId!=='undefined' && $stateParams.msgId!==null && $stateParams.msgId!=={})){
		myResource.myDialog.swalAlert({mensaje:$stateParams.msgId,estado:"info"});
	}

	// DATOS DE REGISTRO - BACKEND
	$scope.frmLogin={};
	$scope.request={};
	$scope.submitForm=function(){
		// CONSULTAR A WS
		myResource.sendData('postEntity').save($scope.frmLogin,function(json){
			// VALIDAR LOGIN CORRECTO
			if(json.estado===true){
				// REGISTRAR ID DE SESION
				setSession(json.sessionId);
				// here, we fake authenticating and give a fake user
		        principal.authenticate(json);
			}else{
				// MENSAJE DE SALIDA 
				myResource.myDialog.swalAlert(json);
			}
			// VALIDAR SI ES NECESARIO EL CAMBIO DE CONTRASEÑA
			if(json.pass===true) $scope.request=json;
		},function(json){myResource.myDialog.swalAlert(json);});
	};

	// REGISTRAR SESION
	function setSession(id){
		myResource.cookies.put('userID',id);
		myResource.cookies.put(sys_name,id);
		window.location.assign("./");
	}
	
}])
.controller('restoreCtrl',['$scope','myResource',function($scope,myResource){
	
	// SLIDES
	$scope.slides={};
	myResource.requestData('resources/slides/ai').json(function(json){$scope.slides=json.data;}).$promise;
	
	// DECLARACION DE FORMULARIO
	$scope.frmParent={};
	
	// REINICIO DE CONTRASEÑA
	$scope.submitForm=function(){
		// PARAMETRO PARA RESTABLECER CONRASEÑA
		$scope.frmLogin.opt='resetPwd';
		// ENVIAR FORMULARIO
		myResource.sendData('recovery/REQUEST').save($scope.frmLogin,function(json){
			// VALIDAR RESPUESTA DE BACKEND
			if(json.data.estado===true)$scope.frmLogin={};
			// PRESENTAR MENSAJE
			myResource.myDialog.swalAlert(json.data);
		},myResource.setError);
	};
	
}])
.controller('signupCtrl',['$scope','myResource',function($scope,myResource){
	
	// SLIDES
	$scope.slides={};
	myResource.requestData('resources/slides/ai').json(function(json){$scope.slides=json.data;}).$promise;
	
	// DECLARACION DE FORMULARIO
	$scope.frmParent={};
	// PRESENTAR FORMULARIO DE REGISTRO
	$scope.registrationForm=false;
	
	// REGISTRO DE USUARIO
	$scope.searchEntity=function(type){
		// ENVIO DE FORMULARIO
		myResource.sendData('entidades/REQUEST').save({type:'registre',ruc:$scope.frmParent.ruc},function(json){
			// VALIDAR SI EXISTE REGISTRO
			if(json.estado===true){
				// HABILITAR FORMULARIO DE REGISTRO
				$scope.registrationForm=true;
				// CARGAR DATOS DE ENTIDAD
				$scope.frmParent=json.data;
			}
			// PENSENTAR MENSAJE DE RESPUESTA
			myResource.myDialog.swalAlert(json);
		},myResource.setError);
	};
	
	// REGISTRO DE USUARIO
	$scope.submitForm=function(type){
		// CERRAR SESION
		myResource.myDialog.swalConfirm('Por favor, antes de continuar asegúrese que toda la información ingresada en estos formularios es completamente correcta.',function(){
			// ENVIO DE FORMULARIO
			myResource.sendData('entidades/REQUEST').save(angular.merge({type:'createuser'},$scope.frmParent),function(json){
				// PRESENTAR MENSAJE DE RESPUESTA
				myResource.myDialog.swalAlert(json);
				// VALIDAR SI EXISTE REGISTRO Y REDIRECCIONAR A INICIO
				if(json.estado===true) myResource.state.go('main');
			},myResource.setError);
		});
	};
	
}])
.controller('signup_newCtrl',['$scope','myResource',function($scope,myResource){
	
	// SLIDES
	$scope.slides={};
	myResource.requestData('resources/slides/ai').json(function(json){$scope.slides=json.data;}).$promise;
	
	// VARIABLES DE ENTORNO
	$scope.entityType=entityType;
	$scope.identificationType=identificationType;
	
	// DECLARACION DE FORMULARIO
	$scope.frmParent={};
	// PRESENTAR FORMULARIO DE REGISTRO
	$scope.registrationForm=false;
	
	// DATOS DE REGISTRO - BACKEND
	$scope.submitFormSignUp=function(){
		myResource.myDialog.swalConfirm('Por favor, antes de realizar ersta acción asegúrese que todos los datos esté correctos!',function(){
			myResource.sendData('entidades').save($scope.frmParent,function(json){
				if(json.estado===true){
					$scope.frmParent={};
					myResource.state.go('main');
				}	myResource.myDialog.showNotify(json);
			},myResource.setError);
		});
	};
	
}])
.run(function($rootScope,$translate){
	$rootScope.changeLanguage = function(lang){
		$translate.use(lang);
	};
})
;