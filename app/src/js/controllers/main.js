/**
 * Main
 */
app
.controller('mainCtrl',['account','$scope','$rootScope','myResource','mySession',function(account,$scope,$rootScope,myResource,mySession){
	// DATOS DE SESSION
	$scope.entity=account.data;
	$scope.entityInfo=account.info;
	// JSON CONFIG
	$scope.paramsConf=account.config;
	// SETTING CONFIG
	myResource.setTempData('paramsConf',account.config);
	myResource.setTempData('sessionEntity',account.data);
	// VARIABLES GLOBALES
	$rootScope.mainAlert=false;
	// VALIDAR DATOS INCOMPLETOS DE REPRESENTANTE LEGAL
	if(!myResource.testNull($scope.entity.representantelegal_ruc) || !myResource.testNull($scope.entity.representantelegal_email)){
		$rootScope.mainAlert=true;
		myResource.myDialog.swalAlert({estado:'info',mensaje:'Por favor, para continuar es necesario que ingrese el número de cédula del representante legal.'});
	}
	// MAIN FUNTIONS
	$scope.mergeData=function(data1,data2){return angular.merge(data1,data2);};
	// Parámetros de tfooter
	$scope.labels=labelsFooter;
	$scope.limitOptions=optionLimits;
	// VARIABLES HTML
	$scope.statusLabel=statusLabel;
	// VARIABLES DE CODIGO DE BARRAS
	$scope.filterBarCode={options:{debounce:500},code:''};
	// DETALLES DE ENTIDADES - CARD
	$scope.detailConfig={show:true,icon:'expand',label:'LB_EXPAND'};
	$scope.toogleShowDetail=function(){
		$scope.detailConfig.show=!$scope.detailConfig.show;
		$scope.detailConfig.icon=($scope.detailConfig.show?'expand':'compress');
		$scope.detailConfig.label=($scope.detailConfig.show?'LB_EXPAND':'LB_COMPRESS');
	};
	// CURRENT DATE
	$scope.currentYear=new Date();
	$scope.currentDate=new Date();
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
	// RETORNA RESOURCE PARA ACTUALIZAR AFTER DIALOG
	function getResource(){
		console.log('request to update:',updateResource);
		$scope.updateResource=updateResource;
	}
	// CIERRE DE SESIÓN
	$scope.logoutSession=function(){
		myResource.myDialog.swalConfirm('Está por salir del sistema.',function(){mySession.destroy();});
	};
	$scope.highlight=function(text,search){
		if(!myResource.testNull(text)) return '-';
		else text=''+text;
		if (!search)return myResource.sce.trustAsHtml(text);
		if(myResource.testNull(text)) return myResource.sce.trustAsHtml(text.replace(new RegExp(search,'gi'),'<span class="highlightedText">$&</span>'));
	};
	// *** CUSTOM PLACEHOLDER
	$scope.getPlaceholder=function(value,msg){return (myResource.testNull(value)&&value!=' ')?value:msg;};
	$scope.trustAsHtml=function(html){return myResource.sce.trustAsHtml(html);};
	// *** STRING TO JSON
	$scope.string2JSON=function(string){return angular.fromJson($scope.paramsConf[string]);};
	$scope.string2JSONExternal=function(string){return angular.fromJson(string);};
	$scope.externalLinks=angular.fromJson($scope.paramsConf['MENU_TEMPLATE_EXTRA_LINKS'])[sys_name];
	$('.content-wrapper').css('padding-bottom',$('.main-footer').outerHeight(true)+110);
}])
.controller('myTableCtrl',['$scope','$state','myResource',function($scope,$state,myResource){
	// ******************************** LLENAR TABLA DE - PARENT
	var bkParent;
	var params=$scope.$parent.tbParams;
	$scope.toolbarItem=params.toolbar;
	delete params.toolbar;
	$scope.filterParent={filter:'',order:params.order,limit:12,page:1};
	// BUSCADOR
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
	// ******************************** LLENAR TABLA DE - PARENT
	$scope.highlight=function(text){return $scope.$parent.highlight(text,$scope.filterParent.filter);};
	$scope.goEntity=function(parentId){$state.go(urlDetailEntity[sys_name][params.tb],parentId)}
	$scope.goUI=function(ui,parentId){myResource.state.go(ui,parentId);};
	if(myResource.testNull(params.custom))$scope.filterParent=angular.merge($scope.filterParent,params.custom);
	$scope.openModal=function(row){$scope.$parent.openModal(params.parent,row);};
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	$scope.getParent=function(){
		if(myResource.testNull(params.entity)){
			// VALIDAR CONSULTA - NODE
			myResource.requestData(params.entity).save({entityId:myResource.cookies.get('userID')},function(json){
				$scope['rowsParent']=json;
				$scope.rowstotal=json.length;
			}).$promise;
		}else{
			// VAIDAR CONSULTA - PHP
			myResource.getData(params.tb+smart_query).get($scope.filterParent,function(json){
				$scope['rowsParent']=json;
				$scope.rowstotal=json.total;
			}).$promise;
		}
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
	$scope.exportEntity=function(entityName,entityId){window.open(reportsURI+entityName+'/?withDetail&id='+entityId,'_blank');};
	$scope.exportEntityById=function(entityName,entityId){window.open(reportsURI+entityName+'/?id='+entityId,'_blank');};
	// DESCARGAR LISTADO DE CERTIFICADOS
	$scope.downloadAttachments=function(entityId){
		window.open(rootRequest+'files/DOWNLOAD?entityId='+entityId+'&entityName='+params.tb,'_blank');
	};
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
/* 
 * Modal: modalPDFViewer
 * Función: 
 */
.controller('pdfViewerExtendsCtrl',['$scope','myResource',function($scope,myResource){
	 console.log('Extends request -> pdfViewerExtendsCtrl -> Form to reference:: ',$scope.frm);
	 $scope.getIframeSrc=function(src){
		 return '/app/src/' + src;
	 };
}])
;