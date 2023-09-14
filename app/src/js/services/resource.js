/**
 * 
 */
var host_remote='http://servicios.cbsd.gob.ec:8081/api/';
//var host_remote='http://localhost:8081/api/';
app
.service('myResource',['$cookies','$resource','$state','$sce','$http','$httpParamSerializer','$filter','$stateParams','myDialog','upload','focus',
	function($cookies,$resource,$state,$sce,$http,$httpParamSerializer,$filter,$stateParams,myDialog,upload,focus){
	var resource={ 
		// PARA INSTANCIA DE TODA LA CONEXIÓN
		setResource:function(url){
			return $resource(url);
		},
		// PARA INSTANCIA DE MÉTODOS GET
		getData:function(tb){
			return $resource(rootRequest+tb,{},{ query:{isArray:false},json:{isArray:true}});
		},
		sendData:function(tb){
			return $resource(rootRequest+tb+':id',{},{ save:{method:'POST'},update:{method:'POST'}});
		},
		// REQUEST WITH NODEJS
		requestData:function(tb){
			return $resource(host_remote+tb,{ },{
				query:{isArray:false},
				update:{method:'PUT'},
				json:{isArray:false},
				get:{isArray:true}
			});
		},
		requestSelect:function(tb,val,$scope,index){
			var filter={filter:val};
			if(angular.isObject(val)) filter=val;
			$resource(host_remote+tb).save(filter,function(json){ $scope[index]=json.data; });
		},
		
		postData:function(tb){
          return $resource(rootRequest+tb,{},{ save:{method:'POST'}});
		},
		postUpdate:function(tb){
          return $resource(rootRequest+tb,{},{ update:{method:'PUT'}});
		},
		setError:function(error){ // NOTIFICAR ERROR EN CONEXIÓN - HTTP
			console.log(error);
			console.log(JSON.stringify(error.data));
			console.log(error.customError);
			alert(error.customError);
		},
		doQuery:function(op){
			return $resource(qRequest+op,{},{post:{method:'POST'},get:{method:'GET',isArray:false},json:{method:'GET',isArray:true}});
		},
		fillSelect:function(tb,val,$scope,index){
			var filter={filter:val};
			if(angular.isObject(val)) filter=val;
			$resource(rootRequest+tb).get(filter,function(json){ $scope[index]=json.data; });
		},
		
		testNull:function(data){ // VALIDAR DATO NULO
			return (typeof data!=='undefined' && data!==null && data!=={} && data!=='')?true:false;
		},
		testModeEdit:function(index){ // VERIFICAR ESTADO DE CONTROLADOR
			return this.testNull(this.tempData[index]);
		},
			
		printReport:function(tb,params){
			var qs=$httpParamSerializer(params);
			window.open(rView+tb+'&'+qs,"popupWindow","width=600,height=600,scrollbars=yes");
		},
		setFormatDate:function($scope,frm,list){
			var me=this;
			angular.forEach(list,function(key){
				$scope[frm][key]=new Date(me.testNull($scope[frm][key])?$filter('date')($scope[frm][key],'MM-dd-yyyy')+' 12:00:00 GMT-0500':new Date);
			});
		},
		
		tempData:{}, // INSTANCIA DE DATOS TEMPORALES ENTRE CONTROLADORES
		setTempData:function(index,val){ // SETEAR DATOS TEMPORALES ENTRE CONTROLADORES
			this.tempData[index]=val;
		},
		getTempData:function(index){ // EXTRAER DATOS TEMPORALES ENTRE CONTROLADORES
			if(this.tempData[index]===null||typeof this.tempData[index]==='undefined') return {};
			return this.tempData[index];
		},
		myDialog:myDialog,
		uploadFile:upload,
		focus:focus,
		state:$state,
		stateParams:$stateParams,
		filter:$filter,
		http:$http,
		sce:$sce,
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
				  this.setModal(ctrl,url).then(function(){ success();},function(){ cancel();} );
			},
			stateModal:false,
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
					templateUrl:url,
					controller:ctrl,
					clickOutsideToClose:false
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
		  
			swalCustom:SweetAlert,
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
					title:"Panel de confirmación",
					text:msg+'. <br>¿Desea continuar?',
					type:"warning",
					html:true,
					showCancelButton:true,
					confirmButtonColor:"#DD6B55",
					confirmButtonText:"Sí!",
					cancelButtonText:"No",
					closeOnConfirm:true},
				function(isConfirm){
					if(isConfirm)funcion();
				});
			}
	};
}])
.service('mySession',['$cookies','myResource','$state','$location',function($cookies,myResource,$state,$location){
	var session={
		// DEFINICIÓN DE RUTAS DE ACCESO 
		testBegin:function($rootScope,$trasnlate){
			var session=this;
			$rootScope.$on("$stateChangeStart",function(event,toState,toParams,fromState,fromParams){
				console.log(fromState.name,' -> ',toState.name);
				if(myResource.testNull($cookies.get(sys_name))){
					console.log($location.path())
					var rutasPrivadas={};
					var urlAllow=[];
					var estado=false;
					console.log(toState.name)
					if(session.in_array(toState.name,urlAllow)){
						myResource.getData('getSession').query(function(json){
							if(!myResource.testNull(json.permisos[rutasPrivadas[toState.name]])){
								$location.path('/');
								alert('Permisos insuficientes para acceder a la ruta');
								//event.preventDefault();
							}
						});
					}
				}
			});
		},
		set:function(id){ // INICIAR LA SESIÓN CON COOKIES
			var now=new Date();     
			var exp=new Date(now.getFullYear(),now.getMonth(),now.getDate());
			console.log('date::',exp);
			$cookies.put('userID',id);
			$cookies.put(sys_name,id);
			window.location.assign("./");
		},
		in_array :function(needle,haystack){
			var key='';
			for(key in haystack){ if(haystack[key] === needle) return true}
			return false;
		},
		get:function(){ // OBTENER DATOS DE SESIÓN 
			var cookie=$cookies.get('userID');
			//console.log('userID',cookie);
			return $cookies.get(sys_name);
		},
		test:function(){ // VERIFICAR SESIÓN EXISTENTE
			//console.log('test')
			return myResource.testNull($cookies.get(sys_name));
			//return myResource.testNull($cookies.get('userID'));
		},
		destroy:function(){ // DESTRUIR SESIÓN
			var me=this;
			myResource.getData('').get({qry:'logout'},function(json){
				console.log($cookies);
				if(json.estado===true){
					$cookies.remove('sessionByLalytto');
					$cookies.remove('userID');
					$cookies.remove(sys_name);
					$cookies.remove('es');
					window.location.assign("./");
				} 
			}).$promise;
		},
		log:function(data){
			alert(data);
		}
	};	return session;
}])
.service('upload',["$http","$q",function($http,$q){
	this.uploadFile=function(url,$scope,index){
		var deferred=$q.defer();
		var formData=new FormData();
		angular.forEach($scope[index],function(value,key){
			formData.append(key,value);
		});
		return $http.post(url,formData,{
			transformRequest:angular.identity,
			headers:{'Content-Type':undefined}
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
.service('findEntityService',['myResource',function(myResource){
	var entity={
		requestEntity:function(url,data,$scope,frm,index,index2){
			// REALIZAR CONSULTA
			myResource.requestData(url).save(data,function(json){
				if(json.estado===false) myResource.myDialog.showNotify(json);
				else{
					if(!myResource.testNull(index)) $scope[frm]=angular.merge($scope[frm],json.data);
					else{
						if(!myResource.testNull(index2)) $scope[frm][index]=angular.merge($scope[frm][index],json.data);
						else $scope[frm][index][index2]=angular.merge($scope[frm][index][index2],json.data);
					}
				}
				// CONSULTA
				console.log('requestEntity',url,data,frm,index,index2,json);
			}).$promise;
		},
		findPerson:function(data,$scope,frm,index,index2){
			// REALIZAR CONSULTA
			myResource.requestData('resources/persons/personByCC').save(data,function(json){
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
			myResource.requestData('permits/entities/enitiyByRUC').save(data,function(json){
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