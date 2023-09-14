/*
 * Main
 */
app
/*
 * INICIO DE SESION
 */
.controller('loginCtrl',['$rootScope','$scope','principal','myResource',function($rootScope,$scope,principal,myResource){
	// ENVIAR PETICION DE LOGIN
	$scope.submitForm=function(){
		// CONSULTAR A WS
		myResource.sendData('postLogin').save($scope.frmLogin,function(json){
			// VALIDAR LOGIN CORRECTO
			if(json.estado===true){
				// REGISTRAR ID DE SESION
				setSession(json.sessionId);
				// here, we fake authenticating and give a fake user
		        principal.authenticate(json);
		        // DIRIGIR A RUTA ANTES DE LOGIN
				myResource.state.go($rootScope.toStateAfterLogin.name,$rootScope.toStateParamsAfterLogin);
			}
			// MENSAJE DE WS
			myResource.myDialog.showNotify(json);
		},function(json){myResource.myDialog.showNotify(json)});
	};
	// REGISTRAR SESION
	function setSession(id){
		myResource.cookies.put('userID',id);
		myResource.cookies.put(sys_name,id);
	}
}])
/*
 * ADMINISTRACIÓN DE PERFIL DE USUARIO
 */
.controller('profileCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.tbParent='Perfil';
	$scope.dataEntity=entity.session;
	// CUSTOM TOOLBAR FILTER - BITÁCORA
	$scope.filterInTab={mdIcon:'timer',label:'LB_ACTIVITY_LOG',search:true};
	// myTableCtrl
	$scope.tbParams={parent:'Bitacora',tb:'bitacora',order:'-fecha',toolbar:{}};
}])
/*
 * DASHBOARD EN PÁGINA PRINCIPAL
 */
.controller('mainDashboardCtrl',['$scope','myResource',function($scope,myResource){
	var date;
	// OBTENER ESTADÍSTICAS
	$scope.refreshStats=function(){
		myResource.getData('dashboard').get({type:'main'},function(json){
			$scope.configDashboard=json.config.active;
			$scope.chartViewer=json.chart;
			$scope.chartPrevention=json.prevention;
			$scope.chartMedical=json.medical;
		});
	};	$scope.refreshStats();
	// EXPORTAR ESTADÍSTICAS
	$scope.exportStats=function(format){
		date=new Date();
		myResource.printReport('dailyReport',{date:myResource.filter('date')(date,format)});
	};
}])
/*
 * MÓDULO DE ESTADÍSTICAS - GENERAR ESTADÍSTICAS
 */
.controller('dashboardCtrl',['$scope','myResource',function($scope,myResource){
	// OBTENER ESTADÍSTICAS
	$scope.refreshStats=function(){
		myResource.getData('dashboard').get({type:'stats'},function(json){
			$scope.configDashboard=json.config.active;
			$scope.chartViewer=json.chart;
		});
	};	$scope.refreshStats();
	// EXPORTAR ESTADÍSTICAS
	$scope.exportStats=function(){
		date=new Date();
		myResource.printReport('dailyReport',{date:myResource.filter('date')(date,'yyyy')});
	};
}])
/*
 * MÓDULO DE REPORTES - IMPRIMIR REPORTES
 */
.controller('exportsCtrl',['exports','$scope','$filter','myResource',function(exports,$scope,$filter,myResource){
	// VARIABLES GLOBALES
	$scope.exports=exports;
	$scope.today=moment().format('YYYY-MM-DD');
	// MODELO DE DATOS
	$scope.frmParent={
		limit:0,
		user:0,
		date1:$scope.today,
		date2:$scope.today,
		entity:0,
		orderType:'asc',
		type:'xls'
	};
	// ENVIAR PARAMETROS PARA REPORTE
	$scope.exportReport=function(){
		myResource.printReport('export',$scope.frmParent);
	};
}])
/*
 * CONTROLADOR PRINCIPAL - SESIONES
 */
.controller('mainCtrl',['$rootScope','$scope','myResource','mySession',function($rootScope,$scope,myResource,mySession){
		// STATE TO ROUTES CSS
		$scope.$state=myResource.state;
		// OBTENER SESION DESDE COOKIES
		var account=myResource.getTempData('sessionInfo');
		// DECLARACION DE VARIABLES
		$scope.allow={};
		// LISTAR PERMISOS
		angular.forEach(account.roles,function(val,key){$scope.allow['permiso'+val]=true;});
		// SESSION DE USUARIO
		$rootScope.changeLanguage(account.session.usuario_idioma);
		// DATOS DE SESSION
		$scope.session=account.session;
		$scope.messages=account.messages;
		$scope.setting=account.setting;
		$scope.setting.uri=angular.fromJson($scope.setting.SYSTEM_URI_ROUTES);
		// JSON CONFIG
		$scope.paramsConf=account.setting;
		// SETTING CONFIG
		myResource.setTempData('allow',$scope.allow);
		myResource.setTempData('paramsConf',$scope.setting);
		myResource.setTempData('paramsSession',$scope.session);
		// BUSCAR PROYECTOS POR CÓDIGOS
		$scope.projectModel={};
		$scope.searchProjectCode=function(){
			myResource.sendData('resources/REQUEST').save($scope.projectModel,function(json){
				if(json.estado===true){
					// REDIRIGIR A DETALLE DE PROYECTO
					myResource.state.go(string2JSON('urlDetailEntity')[json.data.entity],{id:json.data.id});
					// LIMPIRA FORMULARIO
					$scope.projectModel={};
				} else myResource.myDialog.showNotify(json);
			});
		};
		// SIMPLE TOOLBAR
		$scope.showNewHistory=false;
		$scope.toogleBar=function(){ $scope.showNewHistory=!$scope.showNewHistory; };
		// MAIN FUNTIONS - UNIR OBJETOS
		$scope.mergeData=function(data1,data2){return angular.merge(data1,data2);};
		// SETEAR NOMBRE DE TOOLBAR
		$scope.setTitle=function(title){$scope.setting.title=title};
		// PARÁMETROS TFOOT
		$scope.labelFooter=labelFooter;
		$scope.limitOptions=limitOptions;
		// VARIABLES HTML
		$scope.statusLabel=statusLabel;
		// VARIABLES DE CODIGO DE BARRAS
		$scope.filterBarCode={options:{debounce:500},code:''};
		// NOMBRE DE RESOURCE (MÉTODO GET) A EJECUTAR CUANDO SE CIERRE UN DIALOG
		$scope.updateResource='';
		var updateResource='';
		// DETALLES DE ENTIDADES - CARD
		$scope.detailConfig={show:true,icon:'zoom_out_map',label:'LB_EXPAND'};
		$scope.toogleShowDetail=function(){
			$scope.detailConfig.show=!$scope.detailConfig.show;
			$scope.detailConfig.icon=($scope.detailConfig.show?'zoom_out_map':'view_compact');
			$scope.detailConfig.label=($scope.detailConfig.show?'LB_EXPAND':'LB_COMPRESS');
		};
		
		// CURRENT DATE
		$scope.currentYear=new Date();
		$scope.currentDate=new Date();
		// DIRECTORIO PARA MODALES
		$scope.pathEntity='';

		// ACTUALIZAR RSC
	    $scope.getResourcesParent=function(){ $scope.$broadcast('someEvent'); }
		// DIALOGS
		$scope.openModal=function(frm,row){
			updateResource=frm; // frm -> nombre de entidad Ej. usuario
			var Tb=frm.toLowerCase(); // Producto -> producto
			
			// VARIABLE PARA DIRECTORIO DE MODAL
			var pathEntity=myResource.testNull(nativePathEntity[Tb])?nativePathEntity[Tb]:$scope.pathEntity;
			
			// CREAR VARIABLES PARA ABRIR MODAL Y PASAR DATOS
			var ctrlTemp=myResource.testNull(customCtrl[Tb])?customCtrl[Tb]:customCtrl['default'];
			var modalTemp=pathModal+pathEntity+'/'+(myResource.testNull(customModal[Tb])?customModal[Tb]:'modal'+frm)+'.html';
			
			// TABLAS
			myResource.setTempData('tb',Tb); // nombre de tabla que va a guardar Ej. producto
			myResource.setTempData(Tb,row); // almacenar row en tabla Ej. $scope.producto=row
			myResource.setTempData('Index',frm); // index para formulario Ej. Producto
			myResource.setTempData('tbTemp',Tb); // equals to tb in modal extends
			myResource.setTempData('frmTemp',frm); // equals to frm in modal extends
			myResource.setTempData('rowTemp',row); // equals to row in modal extends
			myResource.setTempData('ctrlTemp',ctrlTemp); // ctrl in modal extends
			myResource.setTempData('modalTemp',modalTemp); // modal in modal extends
			myResource.myDialog.showModalFN(ctrlTemp,modalTemp,getResource,getResource);
		};
		$scope.openCustomModal=function(path,frm,row){ $scope.pathEntity=path; $scope.openModal(frm,row); };
		// DIALOGS
		$scope.openRequestedModal=function(frm,uri,row){
			updateResource=frm; // frm -> nombre de entidad Ej. usuario
			var Tb=frm.toLowerCase(); // Producto -> producto
			console.log('Call to mainCtrl.openRequestedModal','frm',frm,'uri',uri);
			
			// VARIABLE PARA DIRECTORIO DE MODAL
			var pathEntity=myResource.testNull(nativePathEntity[Tb])?nativePathEntity[Tb]:$scope.pathEntity;
			
			// CREAR VARIABLES PARA ABRIR MODAL Y PASAR DATOS
			var ctrlTemp=myResource.testNull(customCtrl[Tb])?customCtrl[Tb]:customCtrl['default'];
			var modalTemp=pathModal+pathEntity+'/'+(myResource.testNull(customModal[Tb])?customModal[Tb]:'modal'+frm)+'.html';
			
			// TABLAS
			myResource.setTempData('uri',uri); // nombre de tabla que va a guardar Ej. tthh/typeAdvances
			myResource.setTempData('tb',Tb); // nombre de tabla que va a guardar Ej. producto
			myResource.setTempData(Tb,row); // almacenar row en tabla Ej. $scope.producto=row
			myResource.setTempData('Index',frm); // index para formulario Ej. Producto
			myResource.setTempData('tbTemp',Tb); // equals to tb in modal extends
			myResource.setTempData('frmTemp',frm); // equals to frm in modal extends
			myResource.setTempData('rowTemp',row); // equals to row in modal extends
			myResource.setTempData('ctrlTemp',ctrlTemp); // ctrl in modal extends
			myResource.setTempData('modalTemp',modalTemp); // modal in modal extends
			myResource.myDialog.showModalFN(ctrlTemp,modalTemp,getResource,getResource);
		};
		
		// EXPORTAR DATOS
		$scope.goUI=function(ui,parentId){myResource.state.go(ui,parentId);};
		$scope.exportList=function(entity){window.open($scope.setting.uri.reports+entity,'_blank');};
		$scope.exportById=function(entity,id){window.open($scope.setting.uri.reports+entity+'/?id='+id,'_blank');};
		$scope.exportWithDetail=function(entity,id){window.open($scope.setting.uri.reports+entity+'/?withDetail&id='+id,'_blank');};
		$scope.exportEntity=function(entityName,entityId){window.open($scope.setting.uri.reports+entityName+'/?withDetail&id='+entityId,'_blank');};
		$scope.exportEntityById=function(entityName,entityId){window.open($scope.setting.uri.reports+entityName+'/?id='+entityId,'_blank');};
		
		// DESCARGAR LISTADO DE CERTIFICADOS
		$scope.downloadAttachmentsFolder=function(entityName,entityId){
			window.open(rootRequest+'files/DOWNLOAD?entityId='+entityId+'&entityName='+entityName,'_blank');
		};
		// DEFINIR DIRECTORIO DE ENTIDAD
		$scope.setPathModal=function(path){ $scope.pathEntity=path; };
		// RETORNA RESOURCE PARA ACTUALIZAR AFTER DIALOG
		function getResource(){ $scope.updateResource=updateResource; }
		// CIERRE DE SESIÓN
		$scope.logoutSession=function(){
			// CERRAR SESION
			myResource.myDialog.swalConfirm('Está por salir del sistema.',function(){
				// SERVICIO PARA CERRAR SESION
				mySession.destroy();
			});
		};
		// *** DIRECCIONAR A ORDEN DE COBRO
		$scope.goInvoice=function(model){myResource.state.go('collection.newInvoice',model);};
		// *** IMPRIMIRFORMATO DE TEXTO BUSCADO 
		$scope.highlight=function(text,search){
			if(!myResource.testNull(text)) return '-';
			else text=''+text;
			if (!search)return myResource.sce.trustAsHtml(text);
			if(myResource.testNull(text)) return myResource.sce.trustAsHtml(text.replace(new RegExp(search,'gi'),'<span class="highlightedText">$&</span>'));
		};
		// *** CUSTOM PLACEHOLDER
		$scope.getPlaceholder=function(value,msg){
			return (myResource.testNull(value)&&value!=' ')?value:msg;
		};
		$scope.trustAsHtml=function(html){
			return myResource.sce.trustAsHtml(html);
		};
		// *** STRING TO JSON
		$scope.string2JSON=function(string){
			return angular.fromJson($scope.paramsConf[string]);
		};
		// *** STRING TO JSON
		$scope.string2JSONExternal=function(string){
			return angular.fromJson(string);
		};
		$scope.externalLinks=angular.fromJson($scope.paramsConf['MENU_TEMPLATE_EXTRA_LINKS'])[sys_name];
		$('.content-wrapper').css('min-height',$(window).height()+'px');

		// VALIDAR QUE LOS ADJUNTOS SEAN LEGIBLES
		$scope.isSetFile=function(data){ return (data!=null && data!='null' && data!='NO' && data!='NA' && data!=''); };
		// CSS PARA ARCHIVOS
		$scope.getIFaCss=function(data){ return (data)?'fa-check text-success':'fa-close text-danger'; };
	
}])
/*
 * CONTROLADOR PARA EL MANEJO DE TABLAS
 */
.controller('myTableCtrl',['$scope','myResource',function($scope,myResource){
	// ******************************** LLENAR TABLA DE - PARENT
	var bkParent, params=$scope.$parent.tbParams;
	// DEFINIR DIRECTORIO DE ENTIDAD
	if(myResource.testNull(params.path)) $scope.$parent.$parent.setPathModal(params.path);
	
	// PERSONALIZAR EL TOOLBAR
	$scope.toolbarItem=params.toolbar;
	delete params.toolbar;
	$scope.filterParent={filter:'',order:params.order,limit:15,page:1};
	
	// BUSCAR EN LISTA DE REGISTROS
	$scope.filter={options:{debounce:5000}};
	$scope.removeFilter=function(){
	    $scope.filter.show=false;
	    $scope.filterParent.filter='';
	    if($scope.filter.form.$dirty)$scope.filter.form.$setPristine();
	};
	$scope.openSearch=function(){
		$scope.filter.show=true;
		myResource.focus('inputSearch');
	};
	
	// BUSCAR TEXTO
	$scope.highlight=function(text){return $scope.$parent.highlight(text,$scope.filterParent.filter);};
	$scope.highlight2JSON=function(index,text){return $scope.$parent.highlight($scope.$parent.string2JSON(index)[text],$scope.filterParent.filter);};
	
	// DETALLE DE REGISTRO
	$scope.goEntity=function(parentId){myResource.state.go($scope.$parent.string2JSON('urlDetailEntity')[params.tb],parentId);};
	$scope.goUI=function(ui,parentId){myResource.state.go(ui,parentId);};
	
	// REGISTRO PARA CONSULTAS PERSONALIZADAS
	if(myResource.testNull(params.custom))$scope.filterParent=angular.merge($scope.filterParent,params.custom);
	
	/* ABRIR MODALES */
	$scope.openModal=function(row){$scope.$parent.openModal(params.parent,row);};
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	$scope.openCustomModal=function(path,frm,row){ $scope.$parent.setPathModal(path); $scope.$parent.openModal(frm,row); };
	
	/* LISTAR REGISTROS */
	$scope.getParent=function(){
		$scope.deferred=myResource.getData(params.tb+smart_query).get($scope.filterParent,function(json){ $scope['rowsParent']=json; }).$promise;
	};
	$scope.onOrderParent=function(order){
		$scope.filterParent.order=order;
		$scope.getParent();
	};
	$scope.onPageParent=function(page,limit){
		$scope.filterParent.limit=limit;
		$scope.filterParent.page=page;
		$scope.getParent();
	};
	$scope.$watch('filterParent.filter',function(newValue,oldValue){
		if(!oldValue){bkParent=$scope.filterParent.page;}
		if(newValue!==oldValue){$scope.filterParent.page=1;}
		if(!newValue){$scope.filterParent.page=bkParent;}
		$scope.getParent();
	},true);
	$scope.$watch('$parent.$parent.updateResource',function(newValue,oldValue){
		if(myResource.testNull(newValue) && newValue!==''){
			$scope.getParent();
			$scope.$parent.$parent.updateResource='';
		}
	},true);

	// DIALOG PARA ELIMINAR
	$scope.deleteItem=function(id){
		myResource.myDialog.swalConfirm('Está seguro que desea eliminar este registro?',function(){
			myResource.sendData(params.tb+'/DELETE').save({id:id},function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true) $scope.getParent();
			},myResource.setError);
		});
	};

	// FORMULARIO PARA EXPORTAR DATOS
	$scope.exportData=function(row){$scope.$parent.openModal('Exportdata',angular.merge(row,{entity:params.tb,edit:false}));};
	
	// IMPRESION MÚLTIPLE
	$scope.selected=[];
	$scope.printListDetail=function(list){window.open($scope.setting.uri.reports+params.tb+'/?withDetail&id='+list.toString(),'_blank');};
	$scope.printList=function(list){window.open($scope.setting.uri.reports+params.tb+'/?id='+list.toString(),'_blank'); };
	$scope.exportById=function(id){ console.log($scope.setting.uri.reports+params.tb) ; window.open($scope.setting.uri.reports+params.tb+'/?id='+id,'_blank');};
	$scope.exportWithDetail=function(id){window.open($scope.setting.uri.reports+params.tb+'/?withDetail&id='+id,'_blank');};
	$scope.exportEntity=function(entityName,entityId){window.open($scope.setting.uri.reports+entityName+'/?withDetail&id='+entityId,'_blank');};
	$scope.exportEntityById=function(entityName,entityId){window.open($scope.setting.uri.reports+entityName+'/?id='+entityId,'_blank');};
	
	// DESCARGAR LISTADO DE CERTIFICADOS
	$scope.downloadAttachments=function(entityId){
		$scope.downloadAttachmentsFolder(params.tb,entityId);
	};
	
}])
/*
 * CONTROLADOR PARA EL MANEJO DE TABLAS
 */
.controller('dataTableNodeCtrl',['$scope','myResource',function($scope,myResource){
	// ******************************** LLENAR TABLA DE - PARENT
	var params=$scope.$parent.tbParams;
	// DEFINIR DIRECTORIO DE ENTIDAD
	$scope.$parent.$parent.setPathModal(params.path);
	$scope.filterParent={textFilter:'',sortData:params.order,pageLimit:15,currentPage:1};
	
	// ******************************** LLENAR TABLA DE - PARENT
	$scope.rowsParent={};
	$scope.filter={options:{debounce:5000}};
	$scope.removeFilter=function(){
	    $scope.filter.show=false;
	    $scope.filterParent.textFilter='';
	    if($scope.filter.form.$dirty)$scope.filter.form.$setPristine();
	};
	$scope.openSearch=function(){
		$scope.filter.show=true;
		myResource.focus('inputSearch');
	};
	
	// BUSCAR TEXTO
	$scope.highlight=function(text){return $scope.$parent.highlight(text,$scope.filterParent.textFilter);};
	$scope.highlight2JSON=function(index,text){return $scope.$parent.highlight($scope.$parent.string2JSON(index)[text],$scope.filterParent.textFilter);};
	
	// DETALLE DE REGISTRO
	$scope.goUI=function(ui,parentId){myResource.state.go(ui,parentId);};

	/* ABRIR MODALES ANGULARJS- NODEJS */
	$scope.openModal=function(row){$scope.$parent.openRequestedModal(params.entity,params.uri,row);};
	$scope.openParentModal=function(frm,uri,row){$scope.$parent.openRequestedModal(frm,uri,row);};
	
	/* ABRIR MODALES - ANGULARJS - PHP */
	$scope.oModal=function(row){$scope.$parent.openModal(params.entity,row);};
	$scope.oParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	$scope.oCustomModal=function(path,frm,row){ $scope.$parent.setPathModal(path); $scope.$parent.openModal(frm,row); };
	
	$scope.getParent=function(){
		if(myResource.testNull(params.custom))$scope.filterParent=angular.merge($scope.filterParent,params.custom);
		$scope.deferred=myResource.requestData('paginate/'+params.uri).json($scope.filterParent,function(json){
			$scope.rowsParent=json.data;
		}).$promise;
	};
	$scope.$on('someEvent',function(e){ 
		$scope.getParent(); 
	});
	$scope.onOrderParent=function(order){
		$scope.filterParent.sortData=order;
		$scope.getParent();
	};
	$scope.onPageParent=function(page,limit){
		$scope.filterParent.pageLimit=limit;
		$scope.filterParent.currentPage=page;
		$scope.getParent();
	};
	$scope.$watch('filterParent.textFilter',function(newValue,oldValue){
		if(!oldValue){bkParent=$scope.filterParent.currentPage;}
		if(newValue!==oldValue){$scope.filterParent.currentPage=1;}
		if(!newValue){$scope.filterParent.currentPage=bkParent;}
		$scope.getParent();
	},true);
	$scope.$watch('$parent.$parent.updateResource',function(newValue,oldValue){
		if(myResource.testNull(newValue) && newValue!==''){
			$scope.getParent();
			$scope.$parent.$parent.updateResource='';
		}
	},true);

	// DIALOG PARA ELIMINAR
	$scope.deleteUriItem=function(uri,id){
		myResource.myDialog.swalConfirm('¿Está seguro que desea eliminar este registro?',function(){
			myResource.requestData(uri).delete({entityId:id},function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true) $scope.getParent();
			},myResource.setError);
		});
	};
	
}])
/*
 * CONTROLADOR PARA HISTORIAL DE ENTIDADES
 */
.controller('historyEntityCtrl',['$scope','myResource',function($scope,myResource){
	// CUSTOM TOOLBAR FILTER - HISTORIAL
	$scope.filterInTab={mdIcon:'history',label:'LB_CHANGE_LOG',search:false};
	// myTableCtrl
	var toolbar={showNew:false,showPrint:false,cssClass:'md-default'};
	var tbParams=angular.merge($scope.$parent.historyParams,{parent:'Records',tb:'records'});
	// myTableCtrl
	$scope.tbParams=angular.merge(tbParams,{toolbar:toolbar});
}])
;