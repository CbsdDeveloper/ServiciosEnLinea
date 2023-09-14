/**
 * 
 */
app
.service('myResource',['$resource','$http','$filter','$sce','$state','$stateParams','$cookies','$httpParamSerializer','$window','myDialog','upload','focus',
	function ($resource,$http,$filter,$sce,$state,$stateParams,$cookies,$httpParamSerializer,$window,myDialog,upload,focus){
    // urlPath -> host.com/datamain/
	var resource={
		// PARA INSTANCIA DE TODA LA CONEXIÓN
		setResource:function(url){
			return $resource(url);
		},
		// PARA INSTANCIA DE MÉTODOS GET
		getData:function(tb){
			return $resource(rootRequest+tb,{},{ query:{isArray:false},json:{isArray:true} });
		},
		sendData:function(tb){
			return $resource(rootRequest+tb+':id',{},{ save:{method:'POST'},update:{method:'POST'} });
		},
		requestData:function(tb){
			return $resource(host_remote+tb,{
				
			},{
				query:{isArray:false},
				update:{method:'PUT'},
				remove:{method:'DELETE'},
				json:{isArray:false},
				get:{isArray:true}
			});
		},
		
		getDelete:function(tb,id){
          return $resource(dbDelete+tb+'&id='+id,{},{ query: { isArray: false } });
		},
		postData:function(tb){
          return $resource(rootRequest+tb,{},{ save: {method: 'POST' } });
		},
		postUpdate:function(tb){
          return $resource(rootRequest+tb,{},{ update: {method: 'PUT' } });
		},
		setError:function(error){ // NOTIFICAR ERROR EN CONEXIÓN - HTTP
			console.log(error);
			console.log(JSON.stringify(error.data));
			console.log(error.customError);
			myDialog.showNotify(error.customError);
		},
		doQuery:function(op){
			return $resource(qRequest+op,{},{post:{method:'POST'},get:{method:'GET',isArray:false},json:{method:'GET',isArray:true}});
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
		setFormatDate:function($scope,frm,list){
			var me=this;
			angular.forEach(list,function(key){
				if(me.testNull($scope[frm][key])){
					var year=$filter('date')($scope[frm][key],'yyyy');
					var month=$filter('date')($scope[frm][key],'MM') - 1;
					var day=$filter('date')($scope[frm][key],'dd');
					$scope[frm][key]= new Date(year, month, day);
				}
			});
		},
		setFormatDateTime:function($scope,frm,list){
			var me=this;
			angular.forEach(list,function(key){
				var aux=(me.testNull($scope[frm][key])?$scope[frm][key]:new Date());
				$scope[frm][key]=$filter('date')(aux,'yyyy-MM-dd HH:mm','-0500');
			});
		},
		setFormatBool:function($scope,frm,list){
			angular.forEach(list,function(key){
				$scope[frm][key]=$scope[frm][key]?'true':'false';
			});
		},
		translate:function(label){
			return $filter('translate')(label);
		},
		myDialog: myDialog,
		uploadFile: upload,
		focus: focus,
		window:$window,
		sce:$sce,
		state:$state,
		stateParams:$stateParams,
		filter:$filter,
		http:$http,
		cookies:$cookies
    };  return resource;
}])
.service('myDialog',['$mdDialog','$mdBottomSheet','growl','SweetAlert',function ($mdDialog,$mdBottomSheet,growl,SweetAlert){
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
				$mdDialog.cancel();
				this.stateModal=false;
			},
			openBottomSheet:function(ctrl,url){
				return $mdBottomSheet.show({
					templateUrl: url,
					controller: ctrl,
					clickOutsideToClose: false
				});
			},
			closeBottomSheet:function($data){// CERRAR DIALOG
				$mdBottomSheet.hide(data);
			},
			
			testNull:function(data){// VALIDAR NULO
				return (typeof data!=='undefined' && data!==null && data!=={})?true:false;
			},
			showNotify:function(json){// PRESENTAR NOTIFICACION
				var mensaje=(angular.isArray(json)||angular.isObject(json))?json.mensaje:json;
				if(this.testNull(json.estado)){ 
					if(json.estado===true) growl.success(mensaje);
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
    		      method: 'POST',
    		      url: host_name+sys_name+'/personal/REQUEST',
    		      data: {account:'tthh'}
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
      },
      myResource: myResource
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
            var session=principal.myResource.getTempData('sessionInfo');
            // VALIDAR QUE LAS RUTAS ESTÉN HABILITADAS
            if ((typeof (session.modules[$rootScope.toState.name])!=='undefined' && (session.modules[$rootScope.toState.name])!==null) && !session.modules[$rootScope.toState.name]){
            	// VALIDAR SESION Y PERMISOS
            	if (isAuthenticated) {
            		// user is signed in but not authorized for desired state
            		$state.go('main.e403');
            	}else{
            		// user is not authenticated. Stow the state they wanted before you send them to the sign-in state, so you can return them when you're done
            		$rootScope.returnToState = $rootScope.toState;
            		$rootScope.returnToStateParams = $rootScope.toStateParams;
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
			formData.append(key,value);
		});
		return $http.post(url,formData,{
			transformRequest: angular.identity,
			headers: {'Content-Type': undefined,// 'multipart/form-data'
				 	  'Process-Data': false}
		})
		.success(function(res){
			deferred.resolve(res);
		})
		.error(function(msg,code){
			deferred.reject(msg);
		});
		return deferred.promise;
	}	
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