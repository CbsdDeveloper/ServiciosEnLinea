/**
 * 
 */
app 
/*
 * *******************************************
 * DIRECCIÓN DE PLANIFICACION
 * *******************************************
 */
/*
 * POA - PROGRAMAS
 */
.controller('planing.programsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'planing',entity:'Programas_poa',uri:'planing/poa/programs/pagination',order:'programa_nombre',toolbar:{}};
}])
/*
 * POA - POA
 */
.controller('planing.poaCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'planing',entity:'Poa',uri:'planing/poa/pagination',order:'-poa_periodo',toolbar:{}};

	// $scope.tbParams={path:'planing',entity:'Poa',uri:'planing/poa/reforms/pagination',order:'-reforma_numero',toolbar:{}};
}])
/*
 * DETALLE DE POA - ULTIMA REFORMA
 */
.controller('planing.detailPoaCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE SESSION
	var account=myResource.getTempData('sessionInfo'); // session de usuario
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data.poa;
	$scope.poa=entity.data.poa;
	$scope.reform=entity.data.reform;
	
	// REFRESCAR REGISTROS
	$scope.reloadData=function(){
		myResource.requestData('planing/poa/detail/byId').save(myResource.stateParams,function(json){
			if(json.estado===true){
				$scope.dataEntity=json.data.poa;
				$scope.poa=json.data.poa;
				$scope.reform=json.data.reform;
			}
			myResource.myDialog.showNotify(json);
		});
	};
	
	// FINALIZAR CAMBIOS
	$scope.verifyPoa=function(data){
		// DATOS DE SESSION
		data.sessionId=account.session.usuario_id;
		
		// CERRAR REFORMA
		myResource.myDialog.swalConfirm('¿Está seguro que desea finalizar el registro?',function(){
			
			myResource.requestData('planing/poa/reform/byId').update(data,function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true) $scope.reloadData();
			},myResource.setError);
			
		});
		
	};
	
	// SET PATH MODAL
	$scope.$parent.pathEntity='planing';
	// PARÁMETROS PARA REGISTROS DE PERSONAS
	$scope.custom={fk_tabla:'fk_poa_id',fk_tabla_id:$scope.dataEntity.poa_id};
}])
/*
 * REFORMA - EXTENDS
 */
.controller('planing.reformPoaExtendsCtrl',['$scope','myResource',function($scope,myResource){
	
	myResource.requestData('planing/poa/projects/list/reformId').save({reformId:$scope.frmParent.reformId},function(json){
		
		$scope.projectList=json.data;
		
	});
	
	// GUARDAR FORMULARIO
	$scope.saveNewReform=function(){
		
	};
	
}])


/*
 * *******************************************
 * DIRECCIÓN FINANCIERA
 * *******************************************
 */
 /*
  * CLASIFICADOR PRESUPUESTARIO
  */
 .controller('financial.budgetclassifierCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'financial',entity:'Clasificadorpresupuestario',uri:'financial/budgetclassifier',order:'clasificador_codigo',toolbar:{}};
 }])
 /*
  * CATALOGO DE CUENTAS
  */
 .controller('financial.accountcatalogCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'financial',entity:'Plancuentas',uri:'financial/accountcatalog',order:'cuenta_codigo',toolbar:{}};
 }])
 /*
  * ENTIDADES FINANCIERAS
  */
 .controller('financial.financialentitiesCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'financial',entity:'Entidadesfinancieras',uri:'financial/entities',order:'entidad_nombre',toolbar:{}};
 }])
 /*
  * PROGRAMAS
  */
 .controller('financial.programsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'financial',entity:'Programasfinancieros',uri:'financial/programs',order:'programa_codigo',toolbar:{}};
 }])
 /*
  * SUBPROGRAMAS
  */
 .controller('financial.subprogramsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'financial',entity:'Subprogramasfinancieros',uri:'financial/subprograms',order:'subprograma_codigo',toolbar:{}};
 }])
 /*
  * PROYECTOS
  */
 .controller('financial.projectsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'financial',entity:'Proyectosfinancieros',uri:'financial/projects',order:'proyecto_codigo',toolbar:{}};
 }])
 /*
  * ACTIVIDADES
  */
 .controller('financial.activitiesCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'financial',entity:'Actividadesfinancieras',uri:'financial/activities',order:'actividad_codigo',toolbar:{}};
 }])
 /*
  * CLASIFICADOR DE RETNCIONES
  */
 .controller('financial.retentionclassifierCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'financial',entity:'Clasificadorretenciones',uri:'financial/retentionclassifier',order:'clasificador_codigo',toolbar:{}};
 }])
 /*
  * TIPO DE DOCUMENTOS
  */
 .controller('financial.typedocumentsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'financial',entity:'Documentosfinancieros',uri:'financial/typedocuments',order:'documento_nombre',toolbar:{}};
 }])










/*
 * *******************************************
 * DIRECCIÓN FINANCIERA
 * *******************************************
 */
/*
 * CIERRE DE CAJA - PROCEDIMIENTOS DE CONTRATACION
 */
.controller('financial.contractingproceduresCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Procedimientoscontratacion',tb:'procedimientoscontratacion',order:'procedimiento_contratacion',toolbar:{showNew:true}};
}])
/*
 * CIERRE DE CAJA - DOCUMENTOS DE JUSTIFICACION
 */
.controller('financial.justificationrequirementsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Requerimientoscontrolprevio',tb:'requerimientoscontrolprevio',order:'recurso_nombre',toolbar:{showNew:true}};
}])
/*
 * CONTROL PREVIO - PROCESOS Y CONTRATACIONES
 */
.controller('financial.processcontractsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Procesocontratacion',tb:'procesocontratacion',order:'-proceso_codigo',toolbar:{showNew:true}};
}])




/*
 * *******************************************
 * PARAMETRIZACION
 * *******************************************
 */
/*
 * ENTIDADES EXENTAS DE PAGO
 */
.controller('exemptpaymentCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Exentaspago',tb:'exentaspago',order:'-entidad_razonsocial',toolbar:{showNew:true}};
}])

/*
 * *******************************************
 * RECAUDACIÓN
 * *******************************************
 */
/*
 * CIERRE DE CAJA - ESPECIES VALORADAS
 */
.controller('arching.speciesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Cierres',tb:'cierre_especies',order:'-cierre_fecha',toolbar:{showNew:true}};
}])
/*
 * CIERRE DE CAJA - ÓRDENES DE COBRO
 */
.controller('arching.invoicesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Cierres',tb:'cierre_ordenes',order:'-cierre_fecha',toolbar:{}};
	
	// ESCANEAR CODIGO
	$scope.scanBarCode=function(){
		myResource.getData('cierres/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal('Cierres',json.data);
			}
		},myResource.setError);
	};
	
}])
/*
 * ESPECIES VALORADAS
 */
.controller('collection.speciesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Especies',tb:'especies',order:'-fecha_registro',toolbar:{showNew:true}};
}])
/*
 * ESPECIES VALORADAS - DIARIAS
 */
.controller('collection.dailyformsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Solicitudes',tb:'solicitudes',order:'-solicitud_fecha',toolbar:{showNew:true}};
	// IR A DETALLES DE ENTIDAD RELACIONADA CON LA SOLICITUD
	$scope.goDetail=function(entity,entityId){
		var url=myResource.state.href(urlDetailEntity[entity],{id:entityId});
		window.open(url,'_blank');
	};
}])
/*
 * ÓRDENES DE COBRO
 */
.controller('collection.invoicesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'collection',parent:'Ordenescobro',tb:'ordenescobro',order:'-orden_registro',toolbar:{}};
	// ESCANEAR CODIGO
	$scope.scanBarCode=function(){
		myResource.getData('ordenescobro/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal('Ordenescobro',json.data);
			}
		},myResource.setError);
	};
}])
;