/*
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
		
		$scope.today=moment().format('YYYY-MM-DD HH:mm');
		
		// DECLARACION DE VARIABLES
		$scope.allow={};
		// LISTAR PERMISOS
		angular.forEach([],function(val,key){$scope.allow['permiso'+val]=true;});
		
		// SESSION DE USUARIO
		$rootScope.changeLanguage(account.data.label_idioma);
		
		// DATOS DE SESSION
		$scope.session=account.data;
		$scope.setting=account.config;
		// URL DE ACCESO  
		$scope.setting.uri=angular.fromJson($scope.setting.SYSTEM_URI_ROUTES);
		// *** STRING TO JSON
		$scope.setting.notifications=angular.fromJson($scope.setting.MAIN_NOTIFICATIONS_HOME);
		
		// JSON CONFIG
		$scope.paramsConf=account.config;
		$scope.availableModulues=account.modules;
		
		// SET SESSION
		if(myResource.getTempData('sessionStatus')===true){
			myResource.requestData('permits/entities/session/summary').save({tokenId:myResource.cookies.get('userID')},function(data){
				$scope.session=angular.merge($scope.session,data.data);
			});
			myResource.setTempData('sessionStatus',false);
		}
		
		// SETTING CONFIG
		myResource.setTempData('allow',$scope.allow);
		myResource.setTempData('paramsConf',account.config);
		myResource.setTempData('paramsSession',account.data);
		
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
		
		// DIALOGS
		$scope.openParentModal=function(frm,row){$scope.openModal(frm,row);};
		$scope.openModal=function(frm,row){
			updateResource=frm; // frm -> nombre de entidad Ej. usuario
			var Tb=frm.toLowerCase(); // Producto -> producto
			// VARIABLE PARA DIRECTORIO DE MODAL
			var pathEntity=myResource.testNull(nativePathEntity[Tb])?nativePathEntity[Tb]:$scope.pathEntity;
			console.log('path local set to: '+pathEntity);
			// CREAR VARIABLES PARA ABRIR MODAL Y PASAR DATOS
			var ctrlTemp=myResource.testNull(customCtrl[Tb])?customCtrl[Tb]:customCtrl['default'];
			var modalTemp=(myResource.testNull(customModal[Tb])?customModal[Tb]:pathModal+pathEntity+'/modal'+frm)+'.html';
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
		
		// REDIRECCIONAMIENTO DE PAGINAS
		$scope.goUI=function(ui,parentId){myResource.state.go(ui,parentId);};
		$scope.exportById=function(entity,id){window.open($scope.setting.uri.reports+entity+'/?id='+id,'_blank');};
		$scope.exportWithDetail=function(entity,id){window.open($scope.setting.uri.reports+entity+'/?withDetail&id='+id,'_blank');};
		$scope.exportEntity=function(entity,entityId){window.open($scope.setting.uri.reports+entity+'/?withDetail&id='+entityId,'_blank');};
		// DEFINIR DIRECTORIO DE ENTIDAD
		$scope.setPathModal=function(path){
			$scope.pathEntity=path;
		};
		
		// RETORNA RESOURCE PARA ACTUALIZAR AFTER DIALOG
		function getResource(){
			console.log('request to update:',updateResource);
			$scope.updateResource=updateResource;
		}
		// CIERRE DE SESIÓN
		$scope.logoutSession=function(){
			// CERRAR SESION
			myResource.myDialog.swalConfirm('Está por salir del sistema.',function(){
				// SERVICIO PARA CERRAR SESION
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
		$('.content-wrapper').css('min-height',$(window).height()+50+'px');

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
	var bkParent;
	var params=$scope.$parent.tbParams;
	
	// DEFINIR DIRECTORIO DE ENTIDAD
	if(myResource.testNull(params.path)) $scope.$parent.$parent.setPathModal(params.path);
	
	// PERSONALIZAR EL TOOLBAR
	$scope.toolbarItem=params.toolbar;
	delete params.toolbar;
	$scope.filterParent={filter:'',order:params.order,limit:10,page:1};
	
	// ******************************** LLENAR TABLA DE - PARENT
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
	$scope.goEntity=function(parentId){$scope.goUI($scope.$parent.string2JSON('urlDetailEntity')[params.tb],parentId);};
	$scope.goUI=function(ui,parentId){myResource.state.go(ui,parentId);};
	if(myResource.testNull(params.custom))$scope.filterParent=angular.merge($scope.filterParent,params.custom);
	$scope.openModal=function(row){$scope.$parent.openModal(params.parent,row);};
	$scope.openParentModal=function(frm,row){$scope.$parent.openModal(frm,row);};
	$scope.exportData=function(row){$scope.$parent.openModal('Exportdata',angular.merge(row,{entity:params.tb,edit:false}));};
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
				 if(json.estado===true) $scope.getParent();
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
			// HABILITAR PARA REFRESCADO AUTOMATICO
			$scope.getParent();
			$scope.$parent.$parent.updateResource='';
		}
	},true);
	// IMPRESION MÚLTIPLE
	$scope.selected=[];
	$scope.printListDetail=function(list){window.open($scope.setting.uri.reports+params.tb+'/?withDetail&id='+list.toString(),'_blank');};
	$scope.printList=function(list){window.open($scope.setting.uri.reports+params.tb+'/?id='+list.toString(),'_blank'); };
	$scope.exportById=function(id){window.open($scope.setting.uri.reports+params.tb+'/?id='+id,'_blank');};
	$scope.exportWithDetail=function(id){window.open($scope.setting.uri.reports+params.tb+'/?withDetail&id='+id,'_blank');};
	$scope.exportEntityDetail=function(entityName,entityId){window.open($scope.setting.uri.reports+entityName+'/?withDetail&id='+entityId,'_blank');};
	$scope.exportEntity=function(entityName,entityId){window.open($scope.setting.uri.reports+entityName+'/?id='+entityId,'_blank');};
	$scope.exportEntityById=function(entityName,entityId){window.open(reportsURI+entityName+'/?id='+entityId,'_blank');};
	$scope.exportToPDF=function(detail){
		var filterData=paramsReports(detail,'PDF');
		myResource.printReport(params.tb,filterData);
	};
	$scope.exportToXLS=function(detail){
		var filterData=paramsReports(detail,'XLS');
		myResource.printReport(params.tb,filterData);
	};
	// DESCARGAR LISTADO DE CERTIFICADOS
	$scope.downloadAttachments=function(entityId){
		window.open(rootRequest+'files/DOWNLOAD?entityId='+entityId+'&entityName='+params.tb,'_blank');
	};
	function paramsReports(detail,type){
		var filterData={
			filter:$scope.filterParent.filter,
			order:$scope.filterParent.order,
			limit:$scope.filterParent.limit,
			page:$scope.filterParent.page,
			type:type
		};
		if($scope.filterParent.filter=='' && type=='XLS')filterData.limit='all';
		if(myResource.testNull(detail))filterData.withDetail='';
		return filterData;
	}
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