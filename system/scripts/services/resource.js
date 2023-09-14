/**
 * 
 */
// URL DE PRUEBA
app
.service('myResource',['$resource','$http','$filter','$sce','$state','$cookies','$stateParams','$httpParamSerializer','myDialog','upload','focus',
	function ($resource,$http,$filter,$sce,$state,$cookies,$stateParams,$httpParamSerializer,myDialog,upload,focus){
    // urlPath -> host.com/datamain/
	var resource={
		// PARA INSTANCIA DE MÉTODOS GET
		getData:function(tb){
			return $resource(rootRequest+tb,{},{ query:{isArray:false},json:{isArray:true} });
		},
		sendData:function(tb){
			return $resource(rootRequest+tb+':id',{},{ save:{method:'POST'},update:{method:'POST'} });
		},
		requestData:function(tb){
			return $resource(host_remote+tb,{},{
				query:{isArray:false},
				json:{isArray:false},
				get:{isArray:true},
				save:{method:'POST'},
				update:{method:'PUT'}
			});
		},
		
		setError:function(error){ // NOTIFICAR ERROR EN CONEXIÓN - HTTP
			console.log(error);
			console.log(JSON.stringify(error.data));
			console.log(error.customError);
			myDialog.showNotify(error.customError);
		},
		fillSelect:function(tb,val,$scope,index){
			var filter={filter:val};
			if(angular.isObject(val)) filter=val;
			$resource(rootRequest+tb).get(filter,function(json){
				$scope[index]=json.data;
			});
		},
		
		testNull:function(data){ // VALIDAR DATO NULO
			return (typeof data!=='undefined' && data!==null && data!=={})?true:false;
		},
		testModeEdit:function(index){ // VERIFICAR ESTADO DE CONTROLADOR
			return this.testNull(this.tempData[index]);
		},
			
		printReport:function(tb,params){
			var qs=$httpParamSerializer(params);
			window.open(reportsURI+tb+'?'+qs,"popupWindow","width=600,height=600,scrollbars=yes");
		},
		
		tempData: { }, // INSTANCIA DE DATOS TEMPORALES ENTRE CONTROLADORES
		setTempData:function(index,val){ // SETEAR DATOS TEMPORALES ENTRE CONTROLADORES
			this.tempData[index]=val;
		},
		getTempData:function(index){ // EXTRAER DATOS TEMPORALES ENTRE CONTROLADORES
			if(this.tempData[index]===null||typeof this.tempData[index]==='undefined') return {};
			return this.tempData[index];
		},
		myDialog: myDialog,
		uploadFile: upload,
		focus: focus,
		sce:$sce,
		state:$state,
		filter:$filter,
		http:$http,
		stateParams:$stateParams,
		cookies:$cookies
    };  return resource;
}])
.service('myDialog',['$mdDialog','$mdMenu','growl','SweetAlert',function ($mdDialog,$mdMenu,growl,SweetAlert){
	return {
			showConfirm:function(msg,funcion){// DIÁLOGO CONFIRMAR 
				var confirm=$mdDialog.confirm()
              .title('Confirmación')
              .textContent(msg+'. ¿Desea continuar?')
              .ok('¡Continuar!')
              .cancel('Cancelar');
	          $mdDialog.show(confirm).then(funcion);
	        },
			showModalFN:function(ctrl,url,success,cancel){// DIALOG CON ACCIÓN
			  if(typeof cancel ==="undefined") var cancel=success;
			  this.setModal(ctrl,url).then(function(){ success(); },function(){ cancel(); } );
			},
			stateModal: false,
			setModal:function(ctrl,url){// INSTANCIA DE DIALOG
				this.stateModal=true;
				return $mdDialog.show({controller:ctrl,templateUrl:url,escapeToClose:false});
			},
			showModal:function(ctrl,url){// PRESENTAR DIALOG
				this.setModal(ctrl,url);
			},
			closeDialog:function(){// CERRAR DIALOG
				this.stateModal=false;
				$mdDialog.cancel();
			},
			
			testNull:function(data){// VALIDAR NULO
				return (typeof data!=='undefined' && data!==null && data!=={})?true:false;
			},
			showNotify:function(json){// PRESENTAR NOTIFICACION
				var mensaje=(angular.isArray(json)||angular.isObject(json))?json.mensaje:json;
				if(this.testNull(json.estado)){
					if(json.estado==='info') this.swalAlert(json);
					else if(json.estado===true) growl.success(mensaje);
					else growl.error(mensaje);
				} else growl.info(mensaje);
			},
		  
			swalAlert:function(json){		// ALERTAS - by SweetAlert y oitozero.ngSweetAlert
				SweetAlert.swal({
					title:(json.estado=='info'?'¡Atención!':(json.estado===true?'¡Correcto!':'Error')),
					text:json.mensaje,
					type:(json.estado=='info'?'info':(json.estado===true?'success':'error')),
					html:true
				});
		  },
		  swalConfirm:function(msg,funcion){
				SweetAlert.swal({
					title: "Panel de confirmación",
					text: msg+'. <br>¿Desea continuar?',
					type: "warning",
					html: true,
					showCancelButton: true,
					confirmButtonColor: "#DD6B55",
					confirmButtonText: "Sí!",
					cancelButtonText: "No",
					closeOnConfirm: true},
				function(isConfirm){
					if(isConfirm)funcion();
				});
		  }
	};
}])
.service('mySession',['$cookies','myResource','$state','principal',function ($cookies,myResource,$state,principal){
	var session={
		// DESTRUIR SESIÓN
		destroy:function(){ 
			var me=this;
			myResource.getData('').get({qry:'logout'},function(json){
				// PRESENTAR MENSAJE AL USUARIO
				myResource.myDialog.showNotify(json);
				// VALIDAR SI LA SESION FUE CERRADA
				if(json.estado===true){
					// ELIMINAR SESION
					$cookies.remove('sessionByLalytto');
					$cookies.remove('userID');
					$cookies.remove(sys_name);
					$cookies.remove('lang');
					// ELIMINAR AUTENTICACION
					principal.authenticate(null);
					// RECARGAR PAGINA
					window.location.assign("./");
				}
			}).$promise;
		}
	};	return session;
}])
.factory('principal',['$q','$http','myResource',function($q,$http,myResource){
    var _identity = undefined, _authenticated = false;
    return {
    	isIdentityResolved: function() {
    		return angular.isDefined(_identity);
    	},
    	isAuthenticated: function() {
    		return _authenticated;
    	},
    	isInRole: function(role) {
    		if (!_authenticated || !_identity.roles) return false;
    		return _identity.roles.indexOf(parseInt(role,10)) != -1;
    	},
    	isInAnyRole: function(roles) {
    		if (!_authenticated || !_identity.roles) return false;
    		for (var i = 0; i < roles.length; i++) {
    			if (this.isInRole(roles[i])) return true;
    		}
    		return false;
    	},
    	authenticate: function(identity) {
    		_identity = identity;
    		_authenticated = identity != null;
    	},
    	identity: function(force) {
    		var deferred = $q.defer();
    		// INICIAR VALIDACION DE PERMISO
    		if (force === true) _identity = undefined;
    		// check and see if we have retrieved the identity data from the server. if we have, reuse it by immediately resolving
    		if (angular.isDefined(_identity)) {
    			deferred.resolve(_identity);
    			return deferred.promise;
    		}
	        // otherwise, retrieve the identity data from the server, update the identity object, and then resolve.
    		$http({
    		      method: 'GET',
    		      url: '/app/system/getSession'
    		   }).then(function(data){
    			// VALIDAR SESION
    			if((typeof data!=='undefined' && data!==null) && (typeof data.estado!=='undefined' && data.estado!==null && data.estado===false)){
    				_identity = null;
    				_authenticated = false;
    				deferred.resolve(_identity);
    			}else{
    				myResource.setTempData('sessionInfo',data.data);
        			_identity = data.data;
        			_authenticated = true;
        			deferred.resolve(_identity);
    			}
    		},function(error){
    			_identity = null;
    			_authenticated = false;
    			deferred.resolve(_identity);
    		});
    		// RETORNAR PROMESA
        	return deferred.promise;
      }
    };
}])
.factory('authorization',['$rootScope','$state','principal',function($rootScope,$state,principal){
    return {
      authorize: function() {
        return principal.identity().then(function(){
            // VARIABLE PARA VALIDAR AUTENTICACION
        	var isAuthenticated = principal.isAuthenticated();
        	// LISTA DE RUTAS POR MODULOS
            var routes=appModulesPrivateRoutes[sys_name];    		
            // VALIDAR ROLES
            if ((typeof (routes.accessControl[$rootScope.toState.name])!=='undefined' && (routes.accessControl[$rootScope.toState.name])!==null) && !principal.isInRole(routes.accessControl[$rootScope.toState.name])){
            	// VALIDAR SESION Y PERMISOS
            	if (isAuthenticated) {
            		// user is signed in but not authorized for desired state
            		$state.go('session.e403');
            	}else{
            		// user is not authenticated. Stow the state they wanted before you send them to the sign-in state, so you can return them when you're done
            		$rootScope.returnToState = $rootScope.toState;
            		$rootScope.returnToStateParams = $rootScope.toStateParams;
            		// now, send them to the signin state so they can log in
//            		 $state.go('login');
					// RECARGAR PAGINA
					window.location.assign("./");
            	}
            	
            }
        });
      }
    };
}])
.service('upload',["$http","$q",function ($http,$q){
	this.uploadFile=function(url,$scope,index){
		
		var deferred=$q.defer();
		var formData=new FormData();
		
		angular.forEach($scope[index],function(value,key){
			if(key=='uploadfiles'){
				angular.forEach(value,function(file){ formData.append('file[]',file); });
			}else{
				formData.append(key,value);
			}
		});
		
		return $http({
			method:'POST',
			url:url,
			data:formData,
			transformRequest:angular.identity,
			headers: {
				'Content-Type': undefined, // 'multipart/form-data'
			 	'Process-Data': false
			}
		})
		.then(function onSuccess(response) {
			// Handle success
		    var data = response.data;
		    var status = response.status;
		    var statusText = response.statusText;
		    var headers = response.headers;
		    var config = response.config;
		    console.log('success');
		    return data;
		}).catch(function onError(response) {
		    // Handle error
		    var data = response.data;
		    var status = response.status;
		    var statusText = response.statusText;
		    var headers = response.headers;
		    var config = response.config;
		    console.log('error');
		    return data;
		});
		return deferred.promise;
	}	
}])
.service('findEntityService',['myResource',function(myResource){
	var entity={
		findPerson:function(data,$scope,frm,index,index2){
			// REALIZAR CONSULTA
			myResource.sendData('personas/REQUEST').save(data,function(json){
				if(!json.estado) myResource.myDialog.showNotify(json);
				else{
					if(index==null) $scope[frm]=angular.merge($scope[frm],json.data);
					else{
						if(index2==null) $scope[frm][index]=angular.merge($scope[frm][index],json.data);
						else $scope[frm][index][index2]=angular.merge($scope[frm][index][index2],json.data);
					}
				}
			}).$promise;
		},
		findEntity:function(data,$scope,frm,index,index2){
			// REALIZAR CONSULTA
			myResource.sendData('entidades/REQUEST').save(data,function(json){
				if(!json.estado){
					myResource.myDialog.showNotify(json);
					if(index==null) $scope[frm]={};
					else{
						if(index2==null) $scope[frm][index]={};
						else $scope[frm][index][index2]={};
					}
				}else{
					if(index==null) $scope[frm]=angular.merge($scope[frm],json.data);
					else{
						if(index2==null) $scope[frm][index]=angular.merge($scope[frm][index],json.data);
						else $scope[frm][index][index2]=angular.merge($scope[frm][index][index2],json.data);
					}
				}
			}).$promise;
		},
		myResource:myResource
	};	return entity;
}])
;
/*
 * VALIDACIÓN DE CARACTÉRES - SOLO LETRAS
 */
function lettersOnly(evt) {
	evt=(evt) ? evt : event;
	var charCode=(evt.charCode) ? evt.charCode : ((evt.keyCode) ? evt.keyCode : ((evt.which) ? evt.which : 0));
	if (charCode == 32) return true;
	if (charCode > 31 && (charCode < 65 || charCode > 90) && (charCode < 97 || charCode > 122)) {
		alert('ingresa solo letras');
		return false;
	}
    return true;
};
/*
 * VALIDACIÓN DE CARACTÉRES - SOLO NÚMEROS
 */
function onlyNumber(event) {
    var key=window.event ? event.keyCode : event.which;
    return ( key < 48 || key > 57 ) ? false : true;
}
/*
 * VALIDACIÓN DE CARACTÉRES - FLOTANTES
 */
function validateFloat(event) {
    var key=window.event ? event.keyCode : event.which;
    if (event.keyCode === 46) return true;
    else if ( key < 48 || key > 57 ) return false;
    else return true;
}