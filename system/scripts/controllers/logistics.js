/**
 * 
 */
app
/*
 * *****************************************************************************
 * EDOCUMENTACIÓN ELECTRÓNICA
 * *****************************************************************************
 */
/*
 * DOCUMENTACIÓN ELECTRÓNICA
 */
.controller('logistics.edocInboxCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/edoc',entity:'Documentacion_electronica',uri:'administrative/edocumentation/inbox/all',order:'-delectronica_fecha',toolbar:{}};
}])
/*
 * DOCUMENTACIÓN ELECTRÓNICA
 */
.controller('logistics.edocDeletedCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/edoc',entity:'Documentacion_electronica',uri:'administrative/edocumentation/deleted/all',order:'-delectronica_fecha',toolbar:{}};
}])
/*
 * DETALLE DE DOCUMENTACIÓN ELECTRÓNICA
 */
.controller('logistics.detailEdocCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// INFORMACION DE ENTIDAD
	$scope.dataEntity = entity.data;
	
	// DATOS DE SESSION
 	var account=myResource.getTempData('sessionInfo');
	// ELIMINAR REGISTRO
	$scope.toggleEntity=function(opt){
		route= (opt=='delete')?'logistics.edoc.inbox':'logistics.edoc.deleted';
		myResource.myDialog.swalConfirm('¿Está seguro que desea eliminar este registro?',function(){
			// REALIZAR CONSULTA A SERVER
			myResource.requestData('administrative/edocumentation/'+opt+'/byeDocId').remove({entityId:entity.data.delectronica_id,sessionId:account.session.usuario_id},function(json){
	 			myResource.myDialog.showNotify(json);
	 			if(json.estado===true) myResource.state.go(route);
	 		}).$promise;
 		});
	};
}])

/*
 * *****************************************************************************
 * ARCHIVO Y GESTION DOCUMENTAL
 * *****************************************************************************
 */
/*
 * SERIES
 */
.controller('archive.seriesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/archive',entity:'Archivo_seriesdocumentales',uri:'administrative/archive/series',order:'codigo_archivo',toolbar:{}};
}])
/*
 * PERIODOS
 */
.controller('archive.periodsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/archive',entity:'Archivo_periodos',uri:'administrative/archive/periods',order:'-periodo_nombre',toolbar:{}};
}])
/*
 * CONFIGURACIÓN DE PERIODOS
 */
.controller('archive.configPeriodsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// FORMULARIO DE REGISTRO
	$scope.frmParent = {
		model: entity.data.model,
		periodId: entity.data.data.periodo_id
	};
	// INFORMACION DE ENTIDAD
	$scope.dataInfo = entity.data;
	// GUARDAR INSPECCION
	$scope.submitForm=function(){
		// ENVIAR DATOS A SERVER
		myResource.sendData('archivo_series_periodos').save($scope.frmParent,function(json){
			// NOTIFICAR EL ESTADO DE CONSULTA
			myResource.myDialog.swalAlert(json);
			if(json.estado===true) myResource.state.go('logistics.archive.periods');
		},myResource.setError);
	};
}])
/*
 * DETALLE DE PERIODO
 */
.controller('archive.detailPeriodsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={
			path:'logistics/archive',entity:'Revisiones',uri:'administrative/archive/reviews/periods/details',order:'pserie_id',toolbar:{},
			custom:myResource.stateParams
	};
}])


/*
 * REVISIONES
 */
.controller('archive.reviewsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/archive',entity:'Revisiones',uri:'administrative/archive/reviews/periods',order:'pserie_id',toolbar:{}};
}])
/*
 * CONFIGURACIÓN DE PERIODOS
 */
.controller('archive.detailReviewsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	
	// FORMULARIO DE REGISTRO
	$scope.frmParent = { };
	
	console.log(entity.data);

	// INFORMACION DE ENTIDAD
	$scope.dataInfo = entity.data;
	
}])

/*
 * ESTANTERIAS
 */
.controller('archive.shelvingsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/archive',entity:'Direcciones',uri:'administrative/archive/shelvings',order:'estanteria_nombre',toolbar:{}};
}])
/*
 * CAJAS
 */
.controller('archive.boxesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/archive',entity:'Direcciones',uri:'administrative/archive/boxes',order:'caja_nombre',toolbar:{}};
}])
/*
 * FOLDERS
 */
.controller('archive.foldersCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
 	$scope.tbParams={path:'logistics/archive',entity:'Direcciones',uri:'administrative/archive/folders',order:'folder_seriedocumental',toolbar:{}};
}])
/*
 * DOCUMENTACION
 */
.controller('archive.documentationCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'logistics/archive',entity:'Documentacion',uri:'administrative/archive/folders',order:'folder_seriedocumental',toolbar:{}};
}])


/*
 * *****************************************************************************
 * SERVICIOS GENERALES - MANTENIMIENTO DE HERRAMIENTAS 
 * *****************************************************************************
 */
  /*
   * TIPOS DE HERRAMIENTAS
   */
 .controller('typetoolsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/gservices',entity:'Categoria_herramientas',uri:'administrative/gservices/minortools/types',order:'categoria_nombre',toolbar:{}};
 }])
  /*
   * HERRAMIENTAS
   */
 .controller('minortoolsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/gservices',entity:'Herramientasmenores',uri:'administrative/gservices/minortools',order:'herramienta_codigo',toolbar:{}};
 }])
  /*
   * MANTENIMIENTOS DE HERRAMIENTAS
   */
 .controller('engineeringCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/gservices',entity:'Mantenimientos_menores',uri:'administrative/gservices/minortools/maintenances',order:'-mantenimiento_codigo',toolbar:{}};
	// IMPRIMIR RECIBI CONFORME 
	$scope.printReceptionReport=function(id){window.open(reportsURI+'mantenimientos_menores/?withDetail&opt=reception&id='+id,'_blank');};
 }])
  /*
   * MANTENIMIENTOS DE HERRAMEINTAS - EDICION
   */
 .controller('engineeringEditCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	// FORMULARIO DE DATOS
 	$scope.dataEntity=entity.data;
 	$scope.frmParent = $scope.dataEntity;
 	// DATOS DE SESSION
 	var account=myResource.getTempData('sessionInfo');
 	// AGREGAR NUEVA HERRAMIENTA
 	$scope.addTools=function(){
 		myResource.requestData('administrative/gservices/minortools/maintenances/insert/tools').save({sessionId:account.session.usuario_id,toolId:$scope.frmParent.fk_tools,maintenanceId:$scope.frmParent.mantenimiento_id},function(json){
 			myResource.myDialog.showNotify(json);
 			$scope.frmParent.tools = json.data;
 		}).$promise;
 	};
 	// ELIMINAR HERRAMIENTA 
 	$scope.removeTool=function(itemId){
 		myResource.requestData('administrative/gservices/minortools/maintenances/delete/tools').remove({entityId:itemId,maintenanceId:$scope.frmParent.mantenimiento_id},function(json){
 			myResource.myDialog.showNotify(json);
 			$scope.frmParent.tools = json.data;
 		}).$promise;
 	};
 	// GUARDAR DATOS
 	$scope.submitForm=function(){
 		myResource.myDialog.swalConfirm('¿Está seguro que los datos son correctos?',function(){
 			myResource.sendData('mantenimientos_menores/PUT').save($scope.frmParent,function(json){
 				myResource.myDialog.showNotify(json);
 				if(json.estado === true){
 					window.open(reportsURI+'mantenimientos_menores/?id='+json.data.entityId,'_blank');
 					myResource.state.go('logistics.gservices.engineering',{});
 				}
 			}).$promise;
 		});
 	};
 }])
 
/*
 * *****************************************************************************
 * SERVICIOS GENERALES - UNIDADES 
 * *****************************************************************************
 */
/*
 * LISTADO DE UNIDADES (VEHÍCULOS) DE LA INSTITUCIÓN
 */
.controller('unitsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Unidades',tb:'unidades',order:'unidad_nombre',toolbar:{}};
}])
/*
 * DETALLE DE UNIDADES
 */
.controller('detailUnitsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.entity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='logistics/gservices';
	// PARÁMETROS PARA LISTADO DE ATENDIDOS
	$scope.custom={fk_tabla:'fk_unidad_id',fk_tabla_id:$scope.entity.unidad_id};
	// CUSTOM TOOLBAR FILTER - LOCALES
	$scope.historyParams={order:'-unidad_registro',
						  custom:{entity:'unidades',id:$scope.entity.unidad_id}};
}])
/*
 * MOVIMIENTOS DE UNIDADES
 */
.controller('unitTrackingCtrl',['$scope','myResource',function($scope,myResource){
	$scope.filterInTab={mdIcon:'gps_fixed',label:'TOOLBAR_TRACKING',search:true,showNew:true,showPrint:false};
	// MYTABLECTRL
	$scope.tbParams={parent:'Flotasvehiculares',tb:'flotasvehiculares',order:'-flota_salida_hora',
					 toolbar:{},
					 custom:$scope.custom};
}])
/*
 * ABASTECIMIENTO DE UNIDADES
 */
.controller('unitSupplyingCtrl',['$scope','myResource',function($scope,myResource){
	$scope.filterInTab={mdIcon:'local_gas_station',label:'TOOLBAR_SUPPLYING',search:true,showNew:true,showPrint:false};
	// MYTABLECTRL
	$scope.tbParams={parent:'Abastecimiento',tb:'abastecimiento',order:'-flota_salida_hora',
					 toolbar:{},
					 custom:$scope.custom};
}])

/*
 * *****************************************************************************
 * SERVICIOS GENERALES - UNIDADES 
 * *****************************************************************************
 */
/*
 * ÓRDENES DE MANTENIMIENTO
 */
.controller('maintenanceorderCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Ordenesmantenimiento',tb:'ordenesmantenimiento',order:'-orden_fecha_mantenimiento',toolbar:{}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.scanBarCode=function(){
		myResource.getData('ordenesmantenimiento/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.swalAlert(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data.data);
			}
		},myResource.setError);
	};
	// IMPRIMIR RECIBI CONFORME 
	$scope.printReceptionReport=function(id){window.open(reportsURI+$scope.tbParams.tb+'/?withDetail&opt=reception&id='+id,'_blank');};
}])
/*
 * DETALLE DE MANTENIMIENTO DE UNIDADES
 */
.controller('detailMaintenanceorderCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){

	$scope.entity=entity.data.maintenance;
	$scope.entity.gallery=entity.data.gallery;
	// SET PATH MODAL
	$scope.$parent.pathEntity='logistics/gservices';
	
	// RELOAD ENTITY
	$scope.reloadEntity=function(){
	 	myResource.requestData('administrative/gservices/maintenances/order/detailById').save({entityId:$scope.entity.orden_id},function(json){
	 		$scope.entity=entity.data.maintenance;
	 		$scope.entity.gallery=entity.data.gallery;
		}).$promise;
	};
	
}])
/*
 * INFORMES DE MANTENIMIENTO
 */
.controller('reportmaintenanceCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Informemantenimiento',tb:'informemantenimiento',order:'-informe_fecha',toolbar:{}};
}])
/*
 * ÓRDENES DE MOVILIZACIÓN
 */
.controller('mobilizationorderCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Ordenesmovilizacion',tb:'ordenesmovilizacion',order:'-orden_hora_salida',toolbar:{}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.scanBarCode=function(){
		myResource.getData('ordenesmovilizacion/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.swalAlert(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data.data);
			}
		},myResource.setError);
	};
}])
/*
 * ÓRDENES DE COMBUSTIBLE
 */
.controller('fuelorderCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Ordenescombustible',tb:'ordenescombustible',order:'-orden_codigo',toolbar:{showPrint:true,showNew:true}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.filterBarCode={options:{debounce:500},code:'',module:'system'};
	$scope.scanBarCode=function(){
		myResource.getData('ordenescombustible/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.swalAlert(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data.data);
			}
		},myResource.setError);
	};
}])
/*
 * ÓRDENES DE TRABAJO
 */
.controller('workorderCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Ordenestrabajo',tb:'ordenestrabajo',order:'-orden_fecha_emision',toolbar:{}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.scanBarCode=function(){
		myResource.getData('ordenestrabajo/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.swalAlert(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data.data);
			}
		},myResource.setError);
	};
}])




/*
 * *****************************************************************************
 * MANTENIMIENTO DE PARQUE AUTOMOTOR
 * *****************************************************************************
 */
 /*
  * MODELO DE MANTENIMIENTO DE VEHÍCULOS
  */
 .controller('plansabcCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/maintenance',parent:'Planesmantenimiento',tb:'planesmantenimiento',order:'plan_modelo',toolbar:{showPrint:true,showNew:true}};
 }])
 /*
  * RECURSOS PARA EL MANTENIMIENTO DE VEHÍCULOS
  */
 .controller('resourcesabcCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/maintenance',parent:'Recursosmantenimiento',tb:'recursosmantenimiento',order:'recurso_descripcion',toolbar:{showPrint:true,showNew:true}};
 }])
 /*
  * REGLAS PARA EL MANTENIMIENTO DE UIDADES
  */
 .controller('rulesabcCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/maintenance',parent:'Reglasmantenimiento',tb:'reglasmantenimiento',order:'plan_modelo',toolbar:{showPrint:true,showNew:true}};
 }])
 /*
  * MANTENIMIENTO DE UIDADES
  */
 .controller('maintenancesCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/maintenance',parent:'Mantenimientosvehiculares',tb:'mantenimientosvehiculares',order:'-flota_salida_hora',toolbar:{showPrint:true,showNew:true}};
 	// OPEN MODAL - NUEVO REGISTRO
 	$scope.filterBarCode={options:{debounce:500},code:'',type:'unit'};
 	$scope.scanBarCode=function(){
 		myResource.getData('mantenimientosvehiculares/REQUEST').save($scope.filterBarCode,function(json){
 			if(!json.estado) myResource.myDialog.swalAlert(json);
 			else {
 				$scope.filterBarCode.code='';
 				$scope.$parent.openModal(json.data.modal,json.data.data);
 			}
 		},myResource.setError);
 	};
 }])
 /*
  * DETALLE DE MANTENIMIENTO DE UNIDADES
  */
 .controller('detailMaintenancesCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	$scope.entity=entity.data;
 	// SET PATH MODAL
 	$scope.$parent.pathEntity='logistics/maintenance';
 	// RELOAD TRACKING
 	$scope.reloadEntity=function(){
 		myResource.sendData('mantenimientosvehiculares/REQUEST').save({id:$scope.entity.mantenimiento_id},function(json){
 			if(json.estado===true) $scope.entity=json.data;
 		},function(error){myResource.state.go('prevention.maintenances');}).$promise;
 	};
 }])
 /*
  * MANTENIMIENTO PROGRAMADO DE UIDADES
  */
 .controller('premaintenancesCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'logistics/maintenance',parent:'Mantenimientos',tb:'mantenimiento_unidades',order:'-vehiculo_kilometraje',toolbar:{showPrint:true,showNew:true}};
 	// OPEN MODAL - NUEVO REGISTRO
 	$scope.filterBarCode={options:{debounce:500},code:'',type:'unit'};
 	$scope.scanBarCode=function(){
 		myResource.getData('mantenimientosvehiculares/REQUEST').save($scope.filterBarCode,function(json){
 			if(!json.estado) myResource.myDialog.showNotify(json);
 			else {
 				$scope.filterBarCode.code='';
 				$scope.$parent.openModal(json.data.modal,json.data.data);
 			}
 		},myResource.setError);
 	};
 }])
 
 
 
/*
 * ABASTECIMIENTO DE COMBUSTIBLE
 */
.controller('supplyingCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Abastecimiento',tb:'abastecimiento',order:'-flota_salida_hora',toolbar:{showPrint:true,showNew:true}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.filterBarCode={options:{debounce:500},code:'',type:'unit'};
	$scope.scanBarCode=function(){
		myResource.getData('abastecimiento/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.swalAlert(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data.data);
			}
		},myResource.setError);
	};
}])
/*
 * REVISIÓN VEHICULAR DE UIDADES
 */
.controller('vehicularreviewCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics/gservices',parent:'Revisionvehicular',tb:'revisionvehicular',order:'-revision_fecha',toolbar:{showPrint:true,showNew:true}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.filterBarCode={options:{debounce:500},code:'',type:'unit'};
	$scope.scanBarCode=function(){
		myResource.getData('revisionvehicular/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.swalAlert(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data.data);
			}
		},myResource.setError);
	};
}])

/*
 * *****************************************************************************
 * SALA DE CAPACITACIONES
 * *****************************************************************************
 */
/*
 * SALA DE CAPACITACIONES
 */
.controller('trainingroomCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'logistics',parent:'Salacapacitaciones',tb:'salacapacitaciones',order:'-reservacion_fecha_inicio',toolbar:{}};
	// OPEN MODAL - INGRESO DE PROYECTO
	$scope.scanBarCode=function(){
		myResource.getData('salacapacitaciones/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal('Salacapacitaciones',json.data);
			}
		},myResource.setError);
	};
}])

/*
 * *****************************************************************************
 * REPORTES
 * *****************************************************************************
 */
/*
 * REPORTES DE LOGISTICA
 */
.controller('logistics.reportsExtetnds',['$scope','myResource',function($scope,myResource){
	
	$scope.reportEntityList=$scope.string2JSON('reportEntityList');
	
	$scope.entitiesList=$scope.reportEntityList['entities'][$scope.frmParent.module];
	
	// MODELO DE DATOS
	$scope.frmParent.orderType='asc';
	$scope.frmParent.type='xls';
	$scope.reloadEntity=function(entity){ $scope.frmParent.entity=entity.tb; }
	
	// ENVIAR PARAMETROS PARA REPORTE
	$scope.exportReport=function(){
		// window.open(reportsURI+$scope.frmParent.tb+'?'+jQuery.param(obj), '_blank');
		console.log('export',$scope.frmParent);
		myResource.printReport('export',$scope.frmParent);
	};
}])

;