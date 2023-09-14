/**
 * 
 */
app 

/*
 * *******************************************
 * PREVENCIÓN - INSPECCIONES
 * *******************************************
 */
/*
 * BARRIDOS
 */
.controller('sweepsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention/inspections',parent:'Barridos',tb:'barridos',order:'-inspeccion_fingreso',toolbar:{}};
}])
/*
 * CITACIONES
 */
.controller('citationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention/inspections',parent:'Citaciones',tb:'citaciones',order:'-citacion_fecha',toolbar:{}};
}])
/*
 * INSPECCIONES - MODELO CONTENEDOR PARA INSPECCIONES Y MODELO DE MIS TRABAJOS
 */
.controller('inspectionsModelCtrl',['$scope','myResource',function($scope,myResource){
	// IMPRESIÓN DE REPORTES
	$scope.printModel=function(id){window.open(reportsURI+'inspecciones/?id='+id,'_blank');};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.sendData('barridos/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				if(json.data.entity=='inspection') myResource.state.go('prevention.inspections.preinspection',{id:json.data.inspeccion_id});
				else $scope.$parent.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
/*
 * INSPECCIONES
 */
.controller('inspectionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention/inspections',entity:'Inspecciones',uri:'prevention/inspections/inspections',order:'-inspeccion_fecha',toolbar:{}};
}])
/*
 * REVISIÓN DE UNA INSPECCIÓN - INSPECTOR
 */
.controller('preinspectionCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// VARIABLES DE ENTORNO
	$scope.today=moment().format('YYYY-MM-DD');
	// CARD INFORMACTION
	$scope.dataEntity=entity.data;
	// GUARDAR INSPECCION
	$scope.submitForm=function(){
		// ENVIAR DATOS A SERVER
		myResource.sendData('inspecciones/PUT').save($scope.dataEntity,function(json){
			// NOTIFICAR EL ESTADO DE CONSULTA
			myResource.myDialog.swalAlert(json);
			if(json.estado===true){
				// VALIDAR SI CUMPLE CON LOS RECURSOS PARA REDIRECCIONAR A VISTA
				if($scope.dataEntity.i.inspeccion_estado=='APROBADO' || $scope.dataEntity.i.inspeccion_realiza_cambios=='NO' || $scope.dataEntity.i.inspeccion_realiza_inspeccion=='NO') myResource.state.go('prevention.inspections.inspections');
				else myResource.state.go('prevention.inspections.inspection',{id:$scope.dataEntity.i.inspeccion_id});
			}
		},myResource.setError);
	};
}])
/*
 * REVISIÓN DE UNA INSPECCIÓN - INSPECTOR
 */
.controller('inspectionCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// CARD INFORMACTION
	$scope.dataEntity=entity.data;
	// GUARDAR INSPECCION
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Por favor, revise detenidamente el formulario antes de realizar esta acción.',function(){
			myResource.sendData('inspecciones/PUT').save($scope.dataEntity,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) myResource.state.go('prevention.inspections.inspections');
			},myResource.setError);
		});
	};
}])
/*
 * DETALLE DE INSPECCIONES
 */
.controller('detailInspectionsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='prevention/inspections';
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data;
	// IMPRESIÓN DE REPORTES
	$scope.exportWithDetail=function(id){window.open(reportsURI+'inspecciones/?withDetail&id='+id,'_blank');};
	// RECORDS
	$scope.historyParams={order:'-inspeccion_fecha_inspeccion',
						  custom:{entity:'inspecciones',id:$scope.dataEntity.i.inspeccion_id}};
}])
/*
 * PLANES DE EMERGENCIA
 */
.controller('selfprotectionsplansCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/inspections',entity:'Planesemergencia',uri:'prevention/inspections/selfprotections', tb: 'planesemergencia' ,order:'-plan_codigo',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.getData('planesemergencia/REQUEST').save($scope.filterBarCode,function(json){
			if(json.estado===true){
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}else{
				// VALIDAR SI ES UNA ENTIDAD EXENTA DE PAGO
				if(myResource.testNull(json.data['exemptPayment']) && json.data['exemptPayment']>0){
					// PRESENTAR CONFIRMACIÓN DE EXENTICIDAD DE PAGO
					myResource.myDialog.swalConfirm(json.mensaje,function(){
						window.open(reportsURI+$scope.tbParams.tb+'/?exemptPayment&id='+json.data['exemptPayment'],'_blank');
						$scope.filterBarCode.code='';
					});
				}else myResource.myDialog.showNotify(json);
			}
		},myResource.setError);
	};
}])
/*
 * DETALLE DE PLANES DE EMERGENCIA
 */
.controller('detailSelfprotectionsplansCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='prevention';
	// DATOS DE ENTIDAD
	$scope.dataEntity=angular.merge(entity.data,entity.data.local,entity.data.local.entity);
	// RECORDS
	$scope.historyParams={order:'-plan_registro',
						  custom:{entity:'planesemergencia',id:$scope.dataEntity.plan_id}};

	// VALIDAR QUE LOS ADJUNTOS SEAN LEGIBLES
	$scope.isSetFile=function(data){ return (data!=null && data!='null' && data!='NO' && data!='NA' && data!=''); };
	
	// CSS PARA ARCHIVOS
	$scope.getIFaCss=function(data){ return (data)?'fa-check text-success':'fa-close text-danger'; };
	
	// RELOAD DATA
	$scope.reloadData=function(){
		myResource.requestData('prevention/selfprotections/detail/byId').save({id:$scope.dataEntity.plan_id},function(json){
			$scope.dataEntity=angular.merge(json.data,json.data.local,json.data.local.entity);
		});
	};
	
	// DIALOG PARA ELIMINAR
	$scope.deleteItem=function(entity,data){
		myResource.myDialog.swalConfirm('Está seguro que desea eliminar este registro?',function(){
			myResource.sendData(entity+'/DELETE').save(data,function(json){
				 myResource.myDialog.showNotify(json);
				 $scope.reloadData();
			},myResource.setError);
		});
	};
	
	// ELIMINAR ANEXO
	$scope.deleteAnnexes=function(id){
		// CONFIRMAR PARA ELIMINAR ANEXO
		myResource.myDialog.swalConfirm('Está seguro que desea eliminar este registro?',function(){
			// ENVIAR CONSULTA AL BACKEND
			myResource.sendData('autoproteccion_anexos/DELETE').save({annexeId:id},function(json){
				// VALIDAR RESULTADO DE BACKEND
				if(json.estado===true) $scope.reloadData();
				// PRESENTAR RESPUESTA DE BACKEND
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
	
}])
/*
 * PRÓRROGAS
 */
.controller('extensionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention/inspections',entity:'Prorrogas',uri:'prevention/inspections/extensions',order:'-prorroga_codigo',toolbar:{}};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.sendData('prorrogas/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				if(json.data.entity=='inspeccion') myResource.state.go('prevention.inspections.newExtension',{inspectionId:json.data.inspeccion_id});
				else $scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
/*
 * REVISIÓN DE UNA INSPECCIÓN - INSPECTOR
 */
.controller('newExtensionCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// CARD INFORMACTION
	$scope.dataEntity=entity.data;
	// GUARDAR INSPECCION
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Por favor, revise detenidamente el formulario antes de realizar esta acción.',function(){
			myResource.sendData('prorrogas/PUT').save($scope.dataEntity,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'prorrogas/?withDetail&id='+json.data.extensionId,'_blank');
					myResource.state.go('prevention.inspections.extensions');
				}
			},myResource.setError);
		});
	};
}])

/*
 * *******************************************
 * PREVENCIÓN - PERMISOS DE USO DE GLP
 * *******************************************
 */
/*
 * VEHÍCULOS DE TRANSPORTE DE GLP
 */
.controller('glp.transportCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/glp',entity:'Transporteglp',uri:'prevention/glp/transport',order:'-transporte_codigo',toolbar:{}};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.sendData('transporteglp/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				if(myResource.testNull(json.data.modal)) $scope.$parent.openModal(json.data.modal,json.data);
				if(myResource.testNull(json.data.goUI)) myResource.state.go(json.data.goUI.ui,json.data.goUI.data);
			}
		},myResource.setError);
	};
}])
/*
 * DETALLE DE VEHÍCULO DE TRANSPORTE DE GLP
 */
.controller('glp.detailTransportCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='prevention/glp';
	// DATOS DE ENTIDAD
	$scope.dataEntity=angular.merge(entity.data,entity.data.permit,entity.data.permit,entity.data.permit.selfInspection.local);
	// RECORDS
	$scope.historyParams={order:'-transporte_registro',
						  custom:{entity:'transporteglp',id:$scope.dataEntity.transporte_id}};
}])
/*
 * REVISIÓN DE VEHÍCULO DE TRANSPORTE DE GLP
 */
.controller('glp.reviewTransportCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data.data;
	$scope.dataEntity.model=entity.data.model;
	$scope.dataEntity.resources=entity.data.resources;
	// FUNCIÓN PARA GUARDAR REVISIÓN
	$scope.customSubmitForm=function(){
		myResource.myDialog.swalConfirm('Por favor, revise detenidamente el formulario antes de realizar esta acción.',function(){
			myResource.sendData('transporteglpresource').update($scope.dataEntity,function(json){
				if(json.estado===true){
					myResource.state.go('prevention.glp.transport');
					if(myResource.testNull(json.data.status) && json.data.status===true) window.open(reportsURI+'transporteglp/?id='+json.data.id, '_blank');
				}
				myResource.myDialog.showNotify(json);
			},myResource.setError);
		});
	};
}])
/*
 * PREVENCIÓN - FACTIBILIDAD DE GLP
 */
.controller('glp.feasibilityCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/glp',parent:'Factibilidadglp',tb:'factibilidadglp',order:'-factibilidad_aprobado',toolbar:{cssClass:'md-default'}};
	// OPEN MODAL - INGRESO DE PROYECTO (VBP)
	$scope.scanBarCode=function(){
		myResource.sendData('factibilidadglp/REQUEST').save($scope.filterBarCode,function(json){
			if(json.estado===true){
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}else{
				// VALIDAR SI ES UNA ENTIDAD EXENTA DE PAGO
				if(myResource.testNull(json.data['exemptPayment']) && json.data['exemptPayment']>0){
					// PRESENTAR CONFIRMACIÓN DE EXENTICIDAD DE PAGO
					myResource.myDialog.swalConfirm(json.mensaje,function(){
						window.open(reportsURI+$scope.tbParams.tb+'/?exemptPayment&id='+json.data['exemptPayment'],'_blank');
						$scope.filterBarCode.code='';
					});
				}else myResource.myDialog.showNotify(json);
			}
		},myResource.setError);
	};
}])
/*
 * DEFINITIVO DE GLP
 */
.controller('glp.definitiveCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/glp',parent:'Definitivoglp',tb:'definitivoglp',order:'-definitivo_aprobado',toolbar:{cssClass:'md-default'}};
	// OPEN MODAL - INGRESO DE PROYECTO (VBP)
	$scope.scanBarCode=function(){
		myResource.sendData('definitivoglp/REQUEST').save($scope.filterBarCode,function(json){
			if(json.estado===true){
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}else{
				// VALIDAR SI ES UNA ENTIDAD EXENTA DE PAGO
				if(myResource.testNull(json.data['exemptPayment']) && json.data['exemptPayment']>0){
					// PRESENTAR CONFIRMACIÓN DE EXENTICIDAD DE PAGO
					myResource.myDialog.swalConfirm(json.mensaje,function(){
						window.open(reportsURI+$scope.tbParams.tb+'/?exemptPayment&id='+json.data['exemptPayment'],'_blank');
						$scope.filterBarCode.code='';
					});
				}else myResource.myDialog.showNotify(json);
			}
		},myResource.setError);
	};
}])


/*
 * *******************************************
 * PREVENCIÓN - PERMISOS OCASIONALES DE FUNCIONAMIENTO
 * *******************************************
 */
/*
 * LISTADO DE RECURSOS PARA EL PLAN DE CONTINGENCIA
 */
.controller('resourcespofCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention',parent:'Recursosocasionales',tb:'recursosocasionales',order:'recurso_tipo',toolbar:{}};
}])
/*
 * LISTADO DE RECURSOS PARA EL PLAN DE CONTINGENCIA
 */
.controller('rulespofCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention',parent:'Reglasocasionales',tb:'reglasocasionales',order:'regla_parametro',toolbar:{}};
}])
/*
 * LISTADO DE RECURSOS PARA EL PLAN DE CONTINGENCIA
 */
.controller('occasionalsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/occasionals',parent:'Ocasionales',tb:'prevencion_ocasionales',order:'-ocasional_fecha_inicio',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		// ENVIAR CONSULTA
		myResource.sendData('ocasionales/REQUEST').save($scope.filterBarCode,function(json){
			if(json.estado===true){
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}else{
				// VALIDAR SI ES UNA ENTIDAD EXENTA DE PAGO
				if(myResource.testNull(json.data['exemptPayment']) && json.data['exemptPayment']>0){
					// PRESENTAR CONFIRMACIÓN DE EXENTICIDAD DE PAGO
					myResource.myDialog.swalConfirm(json.mensaje,function(){
						window.open(reportsURI+$scope.tbParams.tb+'/?exemptPayment&id='+json.data['exemptPayment'],'_blank');
						$scope.filterBarCode.code='';
					});
				}else myResource.myDialog.showNotify(json);
			}
		},myResource.setError);
	};
}])
/*
 * LISTADO DE RECURSOS PARA EL PLAN DE CONTINGENCIA
 */
.controller('detailOccasionalsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// myTableCtrl
	$scope.entity=entity.data;
	// IMPRESIÓN DE REPORTES
	$scope.printAI=function(id){window.open(reportsURI+'ocasionales/?id='+id,'_blank');};
	// RECORDS
	$scope.historyParams={order:'-ocasional_registro',
						  custom:{entity:'ocasionales',id:$scope.entity.ocasional_id}};
	// IMPRESIÓN DE REPORTES - ID
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getOccasionals/?withDetail&id='+id,'_blank');};
}])


/*
 * *******************************************
 * PREVENCIÓN - CALENDARIO
 * *******************************************
 */
/*
 * CALENDARIO DE PREVENCIÓN
 */
.controller('calendarCtrl',['calendar','$scope','myResource',function(calendar,$scope,myResource){
	var holidays=calendar.info;
	// VARIABLES
	$scope.dayFormat="d";
    // To select a single date, make sure the ngModel is not an array.
    $scope.selectedDate=new Date();
    // First day of the week, 0 for Sunday, 1 for Monday, etc.
    $scope.firstDayOfWeek=0; 
    $scope.setDirection=function(direction) {
      $scope.direction=direction;
      $scope.dayFormat=direction === "vertical" ? "EEEE, MMMM d" : "d";
    };
    $scope.tooltips=false;
    // You would inject any HTML you wanted for
    // that particular date here.
    var numFmt=function(num) {
        num=num.toString();
        return (num.length<2?"0":"")+num;
    };
    $scope.setDayContent=function(date) {
        var key=[date.getFullYear(), numFmt(date.getMonth()+1), numFmt(date.getDate())].join("-");
        var data=(holidays[key]||[{ name: ""}])[0].name;
        return data;
    };
}])


/*
 * *******************************************
 * PREVENCIÓN - CAPACITACIONES
 * *******************************************
 */
/*
 * PREGUNTAS PARA EVALUACIÓN DE SATISFACCIÓN
 */
.controller('pollCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/trainings',parent:'Questions',tb:'questions',order:'pregunta_tipo',toolbar:{}};
}])
/*
 * TEMAS PARA CAPACITACIONES
 */
.controller('topicsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/trainings',parent:'Temario',tb:'temario',order:'tema_nombre',toolbar:{cssClass:'md-default'}};
}])
/*
 * CAPACITADORES
 */
.controller('trainersCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/trainings',parent:'Trainers',tb:'trainers',order:'personal_nombre',toolbar:{cssClass:'md-default'}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.getData('trainers/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
	
}])
/*
 * CAPACITACIONES
 */
.controller('trainingsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/trainings',parent:'Training',tb:'training',order:'-capacitacion_codigo',toolbar:{cssClass:'md-default'}};
	// OPEN MODAL - INGRESO DE PROYECTO
	$scope.scanBarCode=function(){
		myResource.sendData('training/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
/*
 * DETALLE DE CAPACITACIONES
 */
.controller('detailTrainingCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// SET PATH MODAL
	// $scope.$parent.pathEntity='prevention/trainings';
	$scope.$parent.$parent.setPathModal('prevention/trainings');
	// DATOS DE ENTIDAD
	$scope.entity=entity.data;
	// DATOS DE ENTIDAD
	$scope.dataEntity=entity.data.entity;
	// RELOAD CAPACITACIÓN
	$scope.reloadEntity=function(){
		myResource.sendData('training/REQUEST').save({id:$scope.entity.capacitacion_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('prevention.trainings.trainings');}).$promise;
	};
	$scope.openParentModal=function(modal,row){
		$scope.openModal(modal,row);
	};
	// PARÁMETROS PARA DETALLES
	$scope.custom={fk_tabla:'fk_capacitacion_id',fk_capacitacion_id:$scope.entity.capacitacion_id};
	// RECORDS
	$scope.historyParams={order:'-capacitacion_registro',
						  custom:{entity:'training',id:$scope.entity.capacitacion_id}};
	// EXPORTAR LISTADO DE PERSONAL
	$scope.exportById=function(id){window.open(reportsURI+'getTrainings/?withDetail&id='+id,'_blank');};
	// EXPORTAR LISTADO DE PERSONAL
	$scope.downloadCertificate=function(capacitacionId){
		window.open(reportsURI+'training/?id='+capacitacionId,'_blank');
	};
	// CALIFICACIÓN DE CAPACITADOR
	$scope.reviewByTrainer=function(id){
		window.open(reportsURI+'capacitadores/?id='+id,'_blank');
	};
	// IMPRIMIR CERTIFICADO
	$scope.downloadCertificateSingle=function(participanteId,capacitacionId){
		window.open(reportsURI+'getTrainings/?participanteId='+participanteId+'&id='+capacitacionId,'_blank');
	};
	// ELIMINAR LISTADO DE PARTICIPANTES
	$scope.truncateParticipants=function(trainingId){
		myResource.myDialog.swalConfirm("Está seguro que desea eliminar el listado completo de participantes?<br>Esta acción no se podrá deshacer!",function(){
			myResource.sendData('training/PUT').save({trainingId:trainingId,opt:'truncate'},function(json){
				if(json.estado===true) $scope.reloadEntity();
				myResource.myDialog.showNotify(json);
			},function(error){myResource.state.go('prevention.trainings.trainings');}).$promise;
		});
	}
}])

/*
 * *******************************************
 * PREVENCIÓN - SIMULACROS
 * *******************************************
 */
/*
 * SIMULACROS
 */
.controller('simulationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention/trainings',parent:'Simulacros',tb:'simulacros',order:'-simulacro_fecha',toolbar:{showPrint:false,showNew:true}};
	// OPEN MODAL - INGRESO DE PROYECTO
	$scope.scanBarCode=function(){
		myResource.sendData('simulacros/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
	// IMPRIMIR EVALUACIÓN DE SATISFACCIÓN
	$scope.downloadEvaluation=function(entityId){
		window.open(reportsURI+'evaluacionsatisfaccion/?id='+entityId,'_blank');
	};
}])
/*
 * DETALLE DE SIMULACROS
 */
.controller('detailSimulationsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.entity=entity.data;
	// SET DATA ENTITY
	$scope.dataEntity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='prevention/trainings';
	// RECORDS
	$scope.historyParams={order:'-simulacro_registro',
						  custom:{entity:'simulacros',id:$scope.entity.simulacro_id}};

	// IMPRIMIR EVALUACIÓN DE SATISFACCIÓN
	$scope.downloadEvaluation=function(entityId){
		window.open(reportsURI+'evaluacionsatisfaccion/?id='+entityId,'_blank');
	};
	// RELOAD CAPACITACIÓN
	$scope.reloadEntity=function(){
		myResource.sendData('simulacros/REQUEST').save({id:$scope.entity.simulacro_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('prevention.trainings.simulations');}).$promise;
	};
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getSimulations/?withDetail&id='+id,'_blank');};
}])

/*
 * *******************************************
 * PREVENCIÓN - CASAS ABIERTAS
 * *******************************************
 */
/*
 * CASAS ABIERTAS
 */
.controller('standsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention/trainings',parent:'Stands',tb:'stands',order:'-stand_fecha',toolbar:{showPrint:false,showNew:true}};
	// OPEN MODAL - INGRESO DE PROYECTO
	$scope.scanBarCode=function(){
		myResource.sendData('stands/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
	// IMPRIMIR EVALUACIÓN DE SATISFACCIÓN
	$scope.downloadEvaluation=function(entityId){
		window.open(reportsURI+'evaluacionsatisfaccion/?id='+entityId,'_blank');
	};
}])
/*
 * DETALLE DE CASAS ABIERTAS
 */
.controller('detailStandsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.entity=entity.data;
	// SET DATA ENTITY
	$scope.dataEntity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='prevention/trainings';
	// RECORDS
	$scope.historyParams={order:'-stand_registro',
						  custom:{entity:'stands',id:$scope.entity.stand_id}};
	// IMPRIMIR EVALUACIÓN DE SATISFACCIÓN
	$scope.downloadEvaluation=function(entityId){
		window.open(reportsURI+'evaluacionsatisfaccion/?id='+entityId,'_blank');
	};
	// RELOAD CAPACITACIÓN
	$scope.reloadEntity=function(){
		myResource.sendData('stands/REQUEST').save({id:$scope.entity.stand_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('prevention.trainings.stands');}).$promise;
	};
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getStands/?withDetail&id='+id,'_blank');};
}])

/*
 * *******************************************
 * PREVENCIÓN - VISITAS AL CBSD
 * *******************************************
 */
/*
 * VISITAS
 */
.controller('visitsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention/trainings',parent:'Visitas',tb:'visitas',order:'-visita_fecha',toolbar:{showPrint:false,showNew:true}};
	// OPEN MODAL - INGRESO DE PROYECTO
	$scope.scanBarCode=function(){
		myResource.sendData('visitas/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
	// IMPRIMIR EVALUACIÓN DE SATISFACCIÓN
	$scope.downloadEvaluation=function(entityId){
		window.open(reportsURI+'evaluacionsatisfaccion/?id='+entityId,'_blank');
	};
}])
/*
 * DETALLE DE CASAS ABIERTAS
 */
.controller('detailVisitsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.entity=entity.data;
	// SET DATA ENTITY
	$scope.dataEntity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='prevention/trainings';
	// RECORDS
	$scope.historyParams={order:'-visita_registro',
			custom:{entity:'visitas',id:$scope.entity.visita_id}};
	// IMPRIMIR EVALUACIÓN DE SATISFACCIÓN
	$scope.downloadEvaluation=function(entityId){
		window.open(reportsURI+'evaluacionsatisfaccion/?id='+entityId,'_blank');
	};
	// RELOAD CAPACITACIÓN
	$scope.reloadEntity=function(){
		myResource.sendData('visitas/REQUEST').save({id:$scope.entity.visita_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('prevention.trainings.visits');}).$promise;
	};
	$scope.exportWithDetail=function(id){window.open(reportsURI+'getVisits/?withDetail&id='+id,'_blank');};
}])


/*
 * *******************************************
 * PREVENCIÓN - VISTO BUENO DE PLANOS
 * *******************************************
 */
 /*
  * REGISTROS MIGRADOS - VISTO BUENO DE PLANOS
  */
 .controller('migrated.vbpCtrl',['$scope','myResource',function($scope,myResource){
 	// myTableCtrl
 	$scope.tbParams={path:'prevention',parent:'Vbp',tb:'vbp_migracion',order:'-vbp_aprobado',toolbar:{}};
 }])
 /*
  * REGISTROS MIGRADOS - FACTIBILIDAD DE GLP
  */
 .controller('migrated.definitiveCtrl',['$scope','myResource',function($scope,myResource){
 	// myTableCtrl
	 $scope.tbParams={path:'prevention',parent:'Definitivoglp',tb:'definitivoglp_migracion',order:'-definitivo_aprobado',toolbar:{}};
 }])
 /*
  * REGISTROS MIGRADOS - DEFINITIVO DE GLP
  */
 .controller('migrated.feasibilityCtrl',['$scope','myResource',function($scope,myResource){
 	// myTableCtrl
	$scope.tbParams={path:'prevention',parent:'Factibilidadglp',tb:'factibilidadglp_migracion',order:'-factibilidad_aprobado',toolbar:{}};
 }])
 /*
  * VISTO BUENO DE PLANOS
  */
 .controller('vbpCtrl',['$scope','myResource',function($scope,myResource){
 	// myTableCtrl
 	$scope.tbParams={path:'prevention/vbp',parent:'Vbp',tb:'vbp',order:'-vbp_codigo',toolbar:{}};
 	// OPEN MODAL - INGRESO DE PROYECTO (VBP)
 	$scope.scanBarCode=function(){
 		myResource.sendData('vbp/REQUEST').save($scope.filterBarCode,function(json){
 			if(json.estado===true){
 				$scope.filterBarCode.code='';
 				$scope.$parent.openModal(json.data.modal,json.data);
 			}else{
 				// VALIDAR SI ES UNA ENTIDAD EXENTA DE PAGO
 				if(myResource.testNull(json.data['exemptPayment']) && json.data['exemptPayment']>0){
 					// PRESENTAR CONFIRMACIÓN DE EXENTICIDAD DE PAGO
 					myResource.myDialog.swalConfirm(json.mensaje,function(){
 						window.open(reportsURI+$scope.tbParams.tb+'/?exemptPayment&id='+json.data['exemptPayment'],'_blank');
 						$scope.filterBarCode.code='';
 					});
 				}else myResource.myDialog.showNotify(json);
 			}
 		},myResource.setError);
 	};
 }])
/*
 * DETALLE DE VISTO BUENO DE PLANOS
 */
.controller('detailVbpCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// SET PATH MODAL
	$scope.$parent.pathEntity='prevention';
	// DATOS DE ENTIDAD
	$scope.entity=entity.data;
	myResource.setTempData('entity',$scope.entity);
	// CUSTOM TOOLBAR FILTER - HISTORIAL
	$scope.filterInTab={mdIcon:'timer',label:'LB_ACTIVITY_LOG',search:true};
	// IMPRIMIR SOLICITUD DE PROYECTO
	$scope.printProject=function(id){
		window.open(reportsURI+'proyectos/?id='+id,'_blank');
	};
	// RECORDS
	$scope.historyParams={order:'-vbp_registro',
						  custom:{entity:'vbp',id:$scope.entity.vbp_id}};
}])

/*
 * *******************************************
 * PREVENCIÓN - PERMISO DE OCUPACIÓN Y HABITABILIDAD
 * *******************************************
 */
.controller('habitabilityCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention',parent:'Habitabilidad',tb:'habitabilidad',order:'-vbp_codigo',toolbar:{cssClass:'md-default'}};
	// OPEN MODAL - INGRESO DE PROYECTO (VBP)
	$scope.scanBarCode=function(){
		myResource.sendData('habitabilidad/REQUEST').save($scope.filterBarCode,function(json){
			if(json.estado===true){
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}else{
				// VALIDAR SI ES UNA ENTIDAD EXENTA DE PAGO
				if(myResource.testNull(json.data['exemptPayment']) && json.data['exemptPayment']>0){
					// PRESENTAR CONFIRMACIÓN DE EXENTICIDAD DE PAGO
					myResource.myDialog.swalConfirm(json.mensaje,function(){
						window.open(reportsURI+$scope.tbParams.tb+'/?exemptPayment&id='+json.data['exemptPayment'],'_blank');
						$scope.filterBarCode.code='';
					});
				}else myResource.myDialog.showNotify(json);
			}
		},myResource.setError);
	};
}])
/*
 * MODIFICACIÓN DE PLANOS
 */
.controller('modificationsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'prevention/vbp',parent:'Vbp_modificaciones',tb:'vbp_modificaciones',order:'-vbp_codigo',toolbar:{cssClass:'md-default'}};
	// OPEN MODAL - INGRESO DE PROYECTO (VBP)
	$scope.scanBarCode=function(){
		myResource.sendData('vbp_modificaciones/REQUEST').save($scope.filterBarCode,function(json){
			if(json.estado===true){
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}else{
				// VALIDAR SI ES UNA ENTIDAD EXENTA DE PAGO
				if(myResource.testNull(json.data['exemptPayment']) && json.data['exemptPayment']>0){
					// PRESENTAR CONFIRMACIÓN DE EXENTICIDAD DE PAGO
					myResource.myDialog.swalConfirm(json.mensaje,function(){
						window.open(reportsURI+$scope.tbParams.tb+'/?exemptPayment&id='+json.data['exemptPayment'],'_blank');
						$scope.filterBarCode.code='';
					});
				}else myResource.myDialog.showNotify(json);
			}
		},myResource.setError);
	};
}])

/*
 * *******************************************
 * PREVENCIÓN - REGISTRO PERSONAL
 * *******************************************
 */
/*
 * VEHÍCULOS DE TRANSPORTE DE GLP
 */
.controller('myglpTransportCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Transporteglp',tb:'mistransporteglp',order:'-transporte_registro',toolbar:{showPrint:true}};
}])
/*
 * PLANES DE EMERGENCIA
 */
.controller('myplansCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Planesemergencia',tb:'misplanesemergencia',order:'entidad_razonsocial',toolbar:{cssClass:'md-default'}};
}])
/*
 * INSPECCIONES
 */
.controller('myinspectionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Inspecciones',tb:'misinspecciones',order:'-reinspeccion_fecha',toolbar:{showPrint:true}};
}])
/*
 * CITACIONES
 */
.controller('mycitationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Citaciones',tb:'miscitaciones',order:'-citacion_fecha',toolbar:{cssClass:'md-default'}};
}])
/*
 * CAPACITACIONES
 */
.controller('mytrainingsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Training',tb:'mistraining',order:'-capacitacion_codigo',toolbar:{cssClass:'md-default'}};
}])

/*
 * *******************************************
 * PREVENCIÓN - OTRAS ACTIVIDADES
 * *******************************************
 */
/*
 * REGISTRO DE FORMULARIO
 */
.controller('prevention.formsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Formulariosinspeccion',tb:'formulariosinspeccion',order:'-formulario_serie_inicio',toolbar:{showPrint:true}};
}])
/*
 * ACTIVIDADES DE OFICINA
 */
.controller('prevention.officeworkCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Trabajoficina',tb:'trabajoficina',order:'-trabajo_fecha_inicio',toolbar:{showPrint:true}};
}])
/*
 * ATENCIÓN AL USUARIO
 */
.controller('prevention.usersupportCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'prevention',parent:'Atencionusuario',tb:'atencionusuario',order:'-atencion_fecha',toolbar:{showPrint:true}};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.sendData('atencionusuario/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
;