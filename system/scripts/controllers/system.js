/**
 * 
 */
app 
/*
 * *******************************************
 * FORMULARIOS 
 * *******************************************
 */
/*
 * REQUERIMIENTOS -> MACROREQUERIMIENTOS
 */
.controller('reqCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',parent:'Req',tb:'req',order:'req_nombre',toolbar:{cssClass:'md-default'}};
}])
/*
 * REQUERIMIENTOS BÁSICOS
 */
.controller('requirementsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',parent:'Requerimientos',tb:'requerimientos',order:'req_nombre',toolbar:{showNew:true,cssClass:'md-default'}};
}])
/*
 * REQUERIMIENTOS OBLIGATORIOS
 */
.controller('mandatoriesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',parent:'Requeridos',tb:'requeridos',order:'requerido_id',toolbar:{showNew:true,cssClass:'md-default'}};
}])
/*
 * PREGUNTAS DE FORMULARIOS
 */
.controller('questionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',parent:'Preguntas',tb:'preguntas',order:'requerimiento_nombre',toolbar:{showNew:true,cssClass:'md-default'}};
}])
/*
 * MACROACTIVIDADES
 */
.controller('activitiesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',entity:'Actividades',uri:'permits/aconomic/activities',order:'actividad_nombre',toolbar:{}};
}])
/*
 * TASAS PRESUPUESTARIAS
 */
.controller('taxesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',entity:'Tasas',uri:'permits/taxes',order:'tasa_nombre',toolbar:{}};
}])
/*
 * CIIU V4
 */
.controller('ciiuCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',entity:'Ciiu',uri:'permits/ciiu',order:'ciiu_codigo',toolbar:{}};
}])
/*
 * DETALLES DE ACTIVIDADES - FORMULARIOS
 */
.controller('detailActivitiesCtrl',['activity','$scope','myResource',function(activity,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='permits';
	// DATOS DE ENTIDAD
	$scope.dataEntity=activity.data;
	$scope.custom={fk_tabla:'tb_actividades',fk_tabla_id:$scope.dataEntity.actividad_id};
	$scope.tbParams={parent:'Formularios',tb:'formularios',order:'formulario_id',
					 toolbar:{},
					 custom:$scope.custom};
}])
/*
 * DETALLES DE CIIU V4 - FORMULARIOS
 */
.controller('detailCiiuCtrl',['ciiu','$scope','myResource',function(ciiu,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='permits';
	// DATOS DE ENTIDAD
	$scope.dataEntity=ciiu.data;
	$scope.custom={fk_tabla:'tb_ciiu',fk_tabla_id:$scope.dataEntity.ciiu_id};
	$scope.tbParams={parent:'Formularios',tb:'formularios',order:'formulario_id',
					 toolbar:{},
					 custom:$scope.custom};
}])
/*
 * FORMULARIOS
 */
.controller('formsCtrl',['form','$scope','myResource',function(form,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='permits';
	// DATOS DE ENTIDAD
	$scope.form=form.data.data;
	$scope.requirements=$scope.form;
	$scope.dataEntity=$scope.form; // CARD INFORMACTION
	$scope.custom={fk_formulario_id:$scope.form.formulario_id,fk_tabla:$scope.form.fk_tabla};
	$scope.disableThread=function(row,threadId){
		var status={Activo:'Suspendido',Suspendido:'Activo'};
		myResource.myDialog.swalConfirm('Está seguro que desea desactivar este requerimiento?',function(){
			myResource.sendData('threads/DELETE')
			.update({id:threadId},function(json){
				if(json.estado===true)row['thread_estado']=status[row['thread_estado']];
				 myResource.myDialog.showNotify(json);
			},myResource.setError);
		});
	};
}])
/*
 * FORMULARIOS - REQUERIMIENTOS
 */
.controller('requirementsFormCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'FormRequerimientos',tb:'frm_has_req',order:'requerimiento_nombre',
					 toolbar:{},
					 custom:$scope.custom};
}])
/*
 * FORMULARIOS - ITEMS 
 */
.controller('itemsFormCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Items',tb:'items',order:'item_id',
					 toolbar:{},
					 custom:$scope.custom};
}])
/*
 * FORMULARIOS - PREGUNTAS
 */
.controller('threadsFormCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Threads',tb:'threads',order:'pregunta_nombre',
			  toolbar:{},
			  custom:$scope.custom};
}])
/*
 * CONTROLADORES PARA INTEGRAR CON -> detailActivitiesCtrl
 */
.controller('FormRequirementsCtrl',['$rootScope','$scope','myResource',function($rootScope,$scope,myResource) {
	// ******************************** GET DATA PARA RELACIÓN - sucursal o permisos
	myResource.getData('requirements').query(function(json){$scope.jsonData=json});
	$scope.selected=$scope.frmParent['requirements'];
	$scope.toggle=function(item,list){
		var data={fk_formulario_id:$scope.frmParent.formulario_id,fk_requerimiento_id:item};
		myResource.sendData('requirements/PUT').update(data,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=list.indexOf(item);
				(idx>-1)?list.splice(idx,1):list.push(item);
			}
		});
	};	$scope.exists=function(item,list){return (myResource.testNull(list))?list.indexOf(item)>-1:null;};
}])
/*
 * 
 */
.controller('FormThreadsCtrl',['$scope','myResource',function($scope,myResource){
	var form={fk_formulario_id:$scope.frmParent.formulario_id};
	// ******************************** GET DATA PARA RELACIÓN
	myResource.getData('threads').query(form,function(json){$scope.jsonData=json.threads;$scope.icons=json.icons;});
	$scope.selected=$scope.frmParent['threads'];
	$scope.toggle=function(item,list){
		myResource.sendData('threads/PUT').update(angular.merge({fk_pregunta_id:item},form),function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=list.indexOf(item);
				(idx>-1)?list.splice(idx,1):list.push(item);
			}
		});
	};	$scope.exists=function(item,list){return (myResource.testNull(list))?list.indexOf(item)>-1:null;};
}])

/*
 * ENTIDADES COMERCIALES
 */
.controller('entitiesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',entity:'Entidades',uri:'permits/entities',order:'entidad_razonsocial',toolbar:{}};
}])
/*
 * DETALLES DE ENTIDADES
 */
.controller('detailEntitiesCtrl',['entity','locals','trainings','stands','visits','simulations','$scope','myResource',function(entity,locals,trainings,stands,visits,simulations,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='permits';
	// DATOS DE ENTIDAD
	$scope.entity=entity.data;
	$scope.dataEntity=$scope.entity;
	// INFORMACION DE LOCALES
	$scope.dataInfo={
		locals: locals.data,
		trainings: trainings.data,
		stands: stands.data,
		visits: visits.data,
		simulations: simulations.data
	};
	
	// CUSTOM TOOLBAR FILTER - LOCALES
	$scope.filterInTab={mdIcon:'store_mall_directory',label:'TOOLBAR_UTILS',search:true,showNew:true,showPrint:false};
	$scope.historyParams={order:'-entidad_registro',
						  custom:{entity:'entidades',id:$scope.entity.entidad_id}};
	
	// BITÁCORA
	$scope.binnacleParams={id:$scope.entity.entidad_id,type:'entidades'};
	
 	// PARÁMETROS PARA FILTROS
 	$scope.custom={fk_tabla:'fk_entidad_id',fk_tabla_id:$scope.dataEntity.entidad_id};
}])
/*
 * BITÁCORA DE ENTIDADES
 */
.controller('binnacleEntityCtrl',['$scope','myResource',function($scope,myResource){
	// CUSTOM TOOLBAR FILTER - BITÁCORA
	$scope.filterInTab={mdIcon:'timer',label:'LB_ACTIVITY_LOG',search:true,showNew:false};
	// myTableCtrl
	$scope.tbParams={parent:'Bitacora',tb:'bitacora',order:'-fecha',
			 toolbar:{},
			 custom:$scope.binnacleParams};
}])

/*
 * LOCALES COMERCIALES - ACTIVIDADES ECONÓMICAS
 */
.controller('localsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',entity:'Locales',uri:'permits/economicActivities',order:'local_nombrecomercial',toolbar:{}};
}])
/*
 * DETALLE DE LOCALES - ACTIVIDADES ECONÓMICAS
 */
 .controller('detailLocalsCtrl',['entity','permits','inspections','plans','extensions','$scope','myResource',function(entity,permits,inspections,plans,extensions,$scope,myResource){
	// DETALLE DE LOCAL
	$scope.dataEntity=angular.merge(entity.data,entity.data.entity,entity.data.ciiu);
	$scope.entity=entity.data.entity;
	// INFORMACION DE LOCALES
	$scope.dataInfo={
		inspections: inspections.data,
		permits: permits.data,
		plans: plans.data,
		extensions: extensions.data
	};
	// REGISTRO HISTORICO
	$scope.historyParams={order:'-local_registro',
						  custom:{entity:'locales',id:$scope.dataEntity.local_id}};
}])

/*
 * *******************************************
 * PERMISOS DE FUNCIONAMIENTO
 * *******************************************
 */
/*
 * AUTOINSPECCIONES
 */
.controller('selfinspectionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'permits',entity:'AutoInspecciones',uri:'permits/selfInspections',order:'-autoinspeccion_fecha',toolbar:{}};
}])
/*
 * PERMISOS DE FUNCIONAMIENTO
 */
.controller('permitsCtrl',['$scope','$rootScope','myResource',function($scope,$rootScope,myResource){
	$scope.tbParams={path:'permits',entity:'Permisos',uri:'permits/anuals/permits',order:'-permiso_fecha',toolbar:{}};
	
	// Go detail - new permit
	$scope.scanBarCode=function(){
		
		myResource.sendData('selfInspections/REQUEST').save({code:$scope.filterBarCode.code},function(json){
			// VALIDAR RESPUESTA DE SERVIDOR
			if(json.estado === true){
				// VALIDAR SI EXISTE REGISTRO DE DUPLICADO
				if(myResource.testNull(json.data) && myResource.testNull(json.data.modal)){
					$scope.$parent.openModal(json.data.modal,json.data);
					$scope.filterBarCode.code='';
				}
				else myResource.state.go('src.newPermit',{code:$scope.filterBarCode.code});
			}
			else myResource.myDialog.showNotify(json);
		},myResource.setError);
		
	};
	
}])
/*
 * FORMULARIOS PARA CREAR/EMITIR PERMISO DE FUNCIONAMIENTO 
 */
.controller('newPermitCtrl',['entity','permits','info','$scope','myResource',function(entity,permits,info,$scope,myResource){
	
	$scope.dataEntity=angular.merge(entity.data,info.data);
	$scope.permitList=permits.data;
	
	$scope.frmParent={};
	$scope.frmParent.codigo_per=$scope.dataEntity.code;
	
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Confirmar datos!',function(){
			myResource.sendData('permisos').update($scope.frmParent,function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true){
					 myResource.state.go('src.permits');
					 window.open(reportsURI+'permisos/?id='+json.data.id, '_blank');
				 }
			},myResource.setError);
		});
	};
	
}])
/*
 * DETALLE DE PERMISOS DE FUNCIONAMIENTO
 */
.controller('detailPermitsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.dataEntity=entity.data;
	$scope.printAI=function(id){window.open(reportsURI+'autoinspecciones/?id='+id,'_blank');};
	// myTableCtrl
	$scope.tbParams={parent:'Duplicados',tb:'duplicados',order:'-fecha_solicitado',
					 toolbar:{},
					 custom:{smart_select:'duplicatesByPermits',id:$scope.dataEntity.permiso_id}};
}])
/*
 * DUPLICADOS
 */
.controller('duplicatesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'permits',entity:'Duplicados',uri:'permits/duplicates',order:'-fecha_solicitado',toolbar:{}};
	// OPEN MODAL - INGRESO DE PROYECTO
	$scope.scanBarCode=function(){
		myResource.getData('permisos/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
;