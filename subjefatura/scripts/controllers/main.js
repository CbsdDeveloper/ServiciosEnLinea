/**
 * Main
 */
app
/*
 * CONTROLADOR PRINCIPAL - SESIONES
 */
.controller('mainCtrl',['$rootScope','$scope','myResource','mySession',function($rootScope,$scope,myResource,mySession){
	
	// STATE TO ROUTES CSS
	$scope.$state=myResource.state;
	// OBTENER SESION DESDE COOKIES
	var account=myResource.getTempData('sessionInfo');
	
	// DATOS DE SESSION
	$scope.session=account.session;
	$scope.dataEntity=account;
	
	// JSON CONFIG
	$scope.paramsConf=account.setting;
	$scope.availableModulues=account.modules;
	// DATOS ESTADÍSTICOS
	$scope.dashboard=account.dashboard;
	$scope.setting={};
	$scope.setting.uri=angular.fromJson(account.setting.SYSTEM_URI_ROUTES);
	
	// SETTING CONFIG
	myResource.setTempData('paramsConf',account.setting);
	myResource.setTempData('paramsSession',account.session);
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
	// DIALOGS
	$scope.openParentModal=function(frm,row){$scope.openModal(frm,row);};
	$scope.openModal=function(frm,row){
		updateResource=frm; // frm -> nombre de entidad Ej. usuario
		var Tb=frm.toLowerCase(); // Producto -> producto
		// VARIABLE PARA DIRECTORIO DE MODAL
		var pathEntity=myResource.testNull(nativePathEntity[Tb])?nativePathEntity[Tb]:(pathModal);
		console.log('path local set to: '+pathEntity);
		// CREAR VARIABLES PARA ABRIR MODAL Y PASAR DATOS
		var ctrlTemp=myResource.testNull(customCtrl[Tb])?customCtrl[Tb]:customCtrl['default'];
		var modalTemp=pathEntity+(myResource.testNull(customModal[Tb])?customModal[Tb]:'modal'+frm)+'.html';
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
	$scope.goUI=function(ui,parentId){myResource.state.go(ui,parentId);};
	$scope.exportById=function(entity,id){window.open(reportsURI+entity+'/?id='+id,'_blank');};
	$scope.exportWithDetail=function(entity,id){window.open(reportsURI+entity+'/?withDetail&id='+id,'_blank');};
	$scope.exportEntity=function(entity,entityId){window.open(reportsURI+entity+'/?withDetail&id='+entityId,'_blank');};
	// DEFINIR DIRECTORIO DE ENTIDAD
	$scope.setPathModal=function(path){
		$scope.pathEntity=path;
	};
	// BOTTOMSHEETS
	$scope.openBottomSheet=function(frm){
		// frm -> nombre de entidad Ej. usuario
		updateResource=frm;
		var Tb=frm.toLowerCase(); // Producto -> producto
		myResource.myDialog.openBottomSheet(
			myResource.testNull(customCtrl[Tb])?customCtrl[Tb]:customCtrl['default'],pathInc+'bs'+frm+'.html'
		).then(function(clickedItem){
			myResource.showNotify(clickedItem);
		});
	};
	// DIALOG PARA ELIMINAR :: A HEREDAR
	$scope.deleteItem=function(frm,name,id){
		myResource.myDialog.showConfirm('Está seguro que desea eliminar "'+name+'" de los registros?',function(){
			var tb=frm.toLowerCase();
			tb=myResource.testNull(customTb[tb])?customTb[tb]:tb;
			console.log('Real delete: ',tb);
			myResource.setData(tb,customPath[tb])
			.remove({id:id},function(json){
				 myResource.myDialog.showNotify({estado:true,mensaje:'Dato eliminado'});
				 updateResource=frm;
				 getResource();
			},myResource.setError);
		});
	};
	// RETORNA RESOURCE PARA ACTUALIZAR AFTER DIALOG
	function getResource(){
		console.log('request to update:',updateResource);
		$scope.updateResource=updateResource;
	}
	// CIERRE DE SESIÓN
	$scope.logoutSession=function(){
		myResource.myDialog.swalConfirm('Está por salir del sistema.',function(){
			mySession.destroy();
		});
	};
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
}])
/*
 * CONTROLADOR PARA EL MANEJO DE TABLAS
 */
.controller('myTableCtrl',['$scope','myResource',function($scope,myResource){
	// ******************************** LLENAR TABLA DE - PARENT
	var bkParent;
	var params=$scope.$parent.tbParams;
	// DEFINIR DIRECTORIO DE ENTIDAD
	if(myResource.testNull(params.path)) $scope.$parent.$parent.setPathModal(params.path);
	// PERSONALIZAR EL TOOLBAR
	$scope.toolbarItem=params.toolbar;
	delete params.toolbar;
	$scope.filterParent={filter:'',order:params.order,limit:12,page:1};
	// ******************************** LLENAR TABLA DE - PARENT
	$scope.filter={options:{debounce:500}};
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
	if(myResource.testNull(params.custom))$scope.filterParent=angular.merge($scope.filterParent,params.custom);
	$scope.openModal=function(row){$scope.$parent.openModal(params.parent,row);};
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	$scope.getParent=function(){
		$scope.deferred=myResource.getData(params.tb+smart_query).get($scope.filterParent,function(json){
			$scope['rowsParent']=json;
		}).$promise;
	};
	// DIALOG PARA ELIMINAR
	$scope.deleteItem=function(id){
		myResource.myDialog.swalConfirm('Está seguro que desea eliminar este registro?',function(){
			myResource.sendData(params.tb+'/DELETE').save({id:id},function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado) $scope.getParent();
			},myResource.setError);
		});
	};
	// ELIMINAR DESDE CONFIGURACIÓN DE IDS
	$scope.deleteAuto=function(row){
		$scope.deleteItem(row[indexEntity[params.tb]]);
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
	$scope.exportById=function(id){window.open(reportsURI+params.tb+'/?id='+id,'_blank');};
	$scope.exportWithDetail=function(id){window.open(reportsURI+params.tb+'/?withDetail&id='+id,'_blank');};
	$scope.exportToPDF=function(detail){
		var filterData=paramsReports(detail);
		filterData.type='PDF';
		myResource.printReport(params.tb,filterData);
	};
	$scope.exportToXLS=function(detail){
		var filterData=paramsReports(detail);
		filterData.type='XLS';
		myResource.printReport(params.tb,filterData);
	};
	function paramsReports(detail){
		var filterData={
			filter:$scope.filterParent.filter,
			order:$scope.filterParent.order,
			limit:$scope.filterParent.limit,
			page:$scope.filterParent.page
		};
		if($scope.filterParent.filter=='')filterData.limit='all';
		if(myResource.testNull(detail))filterData.withDetail='';
		return filterData;
	}
}])
/*
 * CONTROLADOR PARA EL MANEJO DE TABLAS
 */
.controller('dataTableNodeCtrl',['$scope','myResource',function($scope,myResource){
	// ******************************** LLENAR TABLA DE - PARENT
	var params=$scope.$parent.tbParams;
	// DEFINIR DIRECTORIO DE ENTIDAD
	$scope.$parent.$parent.setPathModal(params.path);
	$scope.filterParent={textFilter:'',sortData:params.order,pageLimit:10,currentPage:1};
	
	// ******************************** LLENAR TABLA DE - PARENT
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
	$scope.openModal=function(row){$scope.$parent.openRequestedModal(params.entity,params.uri,row);};
	$scope.openParentModal=function(frm,uri,row){$scope.$parent.openRequestedModal(frm,uri,row);};
	$scope.exportEntityById=function(entityName,entityId){window.open(reportsURI+entityName+'/?id='+entityId,'_blank');};
	
	$scope.getParent=function(){
		if(myResource.testNull(params.custom))$scope.filterParent=angular.merge($scope.filterParent,params.custom);
		$scope.deferred=myResource.requestData('paginate/'+params.uri).json($scope.filterParent,function(json){
			$scope['rowsParent']=json.data;
		}).$promise;
	};
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
}])
/*
 * CONTROLADOR PARA HISTORIAL DE ENTIDADES
 */
.controller('historyEntityCtrl',['$scope','myResource',function($scope,myResource){
	// CUSTOM TOOLBAR FILTER - HISTORIAL
	$scope.filterInTab={mdIcon:'history',label:'LB_CHANGE_LOG',search:true};
	// myTableCtrl
	var toolbar={showNew:false,showPrint:false,cssClass:'md-default'};
	var tbParams=angular.merge($scope.$parent.historyParams,{parent:'Records',tb:'records'});
	// myTableCtrl
	$scope.tbParams=angular.merge(tbParams,{toolbar:toolbar});
}])
;