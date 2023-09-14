/**
 * 
 */
app 
/*
 * *******************************************
 * PERFIL
 * *******************************************
 */
/*
 * ADMINISTRACIÓN DE PERFIL DE USUARIO
 */
.controller('profile.profileCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParent='Personal';
	var account=myResource.getTempData('sessionInfo');
	$scope.dataEntity=account.session;
	$scope.pathEntity='profile';
	// CUSTOM TOOLBAR FILTER - BITÁCORA
	$scope.filterInTab={mdIcon:'timer',label:'LB_ACTIVITY_LOG',search:true};
	
	$scope.$watch('$parent.updateResource',function(newValue,oldValue){
		if(myResource.testNull(newValue) && newValue!==''){
			myResource.sendData('personal/REQUEST').save({account:'tthh'},function(json){
				myResource.setTempData('sessionInfo',json);
				account=myResource.getTempData('sessionInfo');
				$scope.dataEntity=account.session;
				$scope.dataEntity.job=account.job;
			});
			$scope.$parent.updateResource='';
		}
	},true);
	
	// myTableCtrl
	$scope.tbParams={parent:'Bitacora',tb:'bitacora',order:'-fecha',toolbar:{}};
	// IMPRIMIR CV
	$scope.exportToPDF=function(){
		myResource.printReport('personal',{type:'PDF',account:'tthh',withDetail:true,id:account.session.personal_id});
	};
}])
/*
 * FORMACIÓN ACADÉMICA
 */
.controller('profile.academicTitlesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Formacionacademica',tb:'getTitles',order:'-formacion_fingreso',toolbar:{}};
}])
/*
 * RECONOCIMIENTOS
 */
.controller('profile.awardsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Reconocimientos',tb:'getAwards',order:'-reconocimiento_fecha_recepcion',toolbar:{}};
}])
/*
 * CAPACITACIONES REALIZADAS
 */
.controller('profile.trainingsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Cursos_realizados',tb:'getCourses',order:'-capacitacion_fecha',toolbar:{}};
}])
/*
 * DECLARACIONES JURAMENTADAS
 */
.controller('profile.affidavitsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Declaraciones_juramentadas',tb:'getAffidavits',order:'-declaracion_ftthh',toolbar:{}};
}])
/*
 * CARGAS FAMILIARES
 */
.controller('profile.familiarsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Cargas_familiares',tb:'getFamiliars',order:'persona_fnacimiento',toolbar:{}};
}])
/*
 * EXPERIENCIAS LABORABLES
 */
.controller('profile.employmentsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Experiencias_laborales',tb:'getEmployments',order:'-experiencia_fingreso',toolbar:{}};
}])
/*
 * LICENCIAS DE CONDUCIR
 */
.controller('profile.driverslicensesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Conductores',tb:'getDriverLicenses',order:'-conductor_licencia_emision',toolbar:{}};
}])
/*
 * CUENTAS BANCARIAS
 */
.controller('profile.banksCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'profile',parent:'Cuentasbancarias',tb:'getBanks',order:'banco_nombre',toolbar:{}};
}])

/*
 * *******************************************
 * TALENTO HUMANO
 * *******************************************
 */
 /*
  * CALENDARIO DE EVENTOS
  */
 .controller('tthh.calendarCtrl',['calendar','$scope','myResource',function(calendar,$scope,myResource){
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
 * EXPERIENCIAS LABORALES - CBSD
 */
.controller('tthh.performanceCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Personal_cargos',tb:'getPerformance',order:'-personal_fecha_ingreso',toolbar:{}};
}])
/*
 * SANCIONES
 */
.controller('tthh.sanctionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Sanciones',tb:'getSanctions',order:'-sancion_fecha',toolbar:{}};
}])
/*
 * SOLICITUDES DE PERMISOS
 */
.controller('tthh.permissionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Permisos',tb:'getPermissions',order:'-permiso_fecha_desde',toolbar:{}};
}])
/*
 * NUEVOS PERMISOS
 */
.controller('tthh.newPermissionCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE PERFIL
	$scope.entity=entity.data;
	$scope.dataEntity=entity.data.solicita;
	// VARIABLES PARA FORMULARIOS
	$scope.frmParent=entity.data.frmParent;
	// CANCELAR PROCESO
	$scope.exitEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.state.go('tthh.permissions');
		});
	};
	// FECHAS
	$scope.today=moment().format('YYYY-MM-DD');
	$scope.tomorrow=moment().add(1,'day').format('YYYY-MM-DD');
	// GUARDAR PROCESO
	$scope.saveEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.sendData('personal_permisos').save($scope.frmParent,function(json){
				if(json.estado){
					myResource.state.go('tthh.permissions');
					window.open(reportsURI+'getPermissions/?withDetail&id='+json.data.permiso_id,'_blank');
				}
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
}])
/*
 * SOLICITUDES DE VACACIONES
 */
.controller('tthh.vacationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Vacaciones',tb:'getVacations',order:'-vacacion_fecha_desde',toolbar:{}};
}])
/*
 * NUEVOS PERMISOS
 */
.controller('tthh.newVacationCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE PERFIL
	$scope.entity=entity.data;
	$scope.dataEntity=entity.data.solicita;
	// VARIABLES PARA FORMULARIOS
	$scope.frmParent=entity.data.frmParent;
	// CANCELAR PROCESO
	$scope.exitEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.state.go('tthh.vacations');
		});
	};
	// FECHAS
	$scope.today=moment().format('YYYY-MM-DD');
	$scope.tomorrow=moment().add(1,'day').format('YYYY-MM-DD');
	// GUARDAR PROCESO
	$scope.saveEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.sendData('personal_vacaciones').save($scope.frmParent,function(json){
				if(json.estado){
					myResource.state.go('tthh.vacations');
					window.open(reportsURI+'getVacations/?id='+json.data.vacacion_id,'_blank');
				}
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
}])

/*
 * CERTIFICADOS MEDICOS
 */
.controller('tthh.medicalcertificatesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Certificadosmedicos',tb:'getMedicalcertificates',order:'-fecha_registro',toolbar:{}};
}])

/*
 * HOJA DE RUTA
 */
.controller('tthh.roadmapCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Hojaruta',tb:'getRoadmap',order:'-ruta_salida',toolbar:{}};
}])
/*
 * ACTIVIDADES DIARIAS
 */
.controller('tthh.dailyActivitiesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Actividadesdiarias',tb:'getDailyActivities',order:'-actividad_fecha_inicio',toolbar:{}};
}])
/*
 * EVALUACIONES DE RIESGO PSICOSOCIAL
 */
.controller('tthh.psychosocialEvaluationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Evaluacionesriesgopsicosocial',tb:'evaluacionesriesgopsicosocial',order:'-evaluacion_inicio',toolbar:{}};
}])
/*
 * EVALUACIONES DE RIESGO PSICOSOCIAL
 */
.controller('tthh.psychosocialTestCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE PERFIL
	$scope.questionnaire=entity.data;
	// DATOS DE SESION
	$scope.session=$scope.$parent.session;
	// CREACION DE FORMULARIO
	$scope.frmParent={
		fk_personal_id: $scope.session.personal_id,
		fk_evaluacion_id: myResource.stateParams.evaluationId
	};
	// DATOS DE EVALUACION
	$scope.frmParent.test={};
	
	// ENVIAR FORMULARIO
	$scope.submitForm=function(){
		// DIALOGO DE CONFIRMACION
		myResource.myDialog.swalConfirm('Esta evaluación solo se podrá realizar una vez, ¿Está seguro que sus sus datos son correctos?',function(){
			// ENVIAR FORMULARIO
			myResource.requestData('tthh/sos/psychosocial/test').save($scope.frmParent,function(json){
				// PRESENTAR RESPUESTA DESDE BACKEND
				myResource.myDialog.swalAlert(json);
				// REDIRECCIONAR A LISTADO SI LA PRUEBA HA SIDO GUARDADA CORRECTAMENTE
				if(json.estado===true) myResource.state.go('tthh.psychosocialEvaluations');
			}).$promise;
		});
	};
	
}])
/*
 * EVALUACIONES - ENCUESTAS
 */
.controller('tthh.surveysCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Evaluacionespersonal',tb:'getSurveys',order:'-evaluacion_inicio',toolbar:{}};
}])
/*
 * EVALUACIONES DE RIESGO PSICOSOCIAL
 */
.controller('tthh.surveyTestCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	
	// DATOS DE PERFIL
	$scope.evaluation=entity.data.evaluation;
	$scope.questionnaire=entity.data.sectionsList;
	
	// DATOS DE SESION
	$scope.session=$scope.$parent.session;
	
	// CREACION DE FORMULARIO
	$scope.frmParent={
		test_id: myResource.stateParams.testId,
		fk_personal_id: $scope.session.personal_id,
		fk_evaluacion_id: myResource.stateParams.evaluationId
	};
	
	// DATOS DE EVALUACION
	$scope.frmParent.test={};
	
	// ENVIAR FORMULARIO
	$scope.submitForm=function(){
		// DIALOGO DE CONFIRMACION
		myResource.myDialog.swalConfirm('Esta evaluación solo se podrá realizar una vez, ¿Está seguro que sus sus datos son correctos?',function(){
			// ENVIAR FORMULARIO
			myResource.requestData('tthh/surveys/evaluations/new/staff').save($scope.frmParent,function(json){
				// PRESENTAR RESPUESTA DESDE BACKEND
				myResource.myDialog.swalAlert(json);
				// REDIRECCIONAR A LISTADO SI LA PRUEBA HA SIDO GUARDADA CORRECTAMENTE
				if(json.estado===true) {
					myResource.state.go('tthh.surveys');
					window.open(reportsURI+'getSurveys/?withDetail&id='+myResource.stateParams.testId,'_blank');
				}
			}).$promise;
		});
	};
	
}])

/*
 * *******************************************
 * DIRECCION ADMINISTRATIVA
 * *******************************************
 */
 /* 
  * DOCUMENTACION ELECTONICA
  */
 .controller('administrative.edocumentationCtrl',['$scope','myResource',function($scope,myResource){
	 // VARIABLES DE SESSION
	 sessionInfo=myResource.getTempData('sessionInfo');
	 // VARIABLES PARA PAGINACION DE REGISTROS
	 $scope.tbParams={path:'administrative',entity:'Documentacion_electronica',uri:'administrative/edocumentation/inbox',order:'',toolbar:{},custom:{sessionId:sessionInfo.session.personal_id}};
	 
	 // CREAR NUEVO REGISTRO
	 $scope.composeMessage=function(data){
		 myResource.requestData('administrative/edocumentation/compose/draft').save(data,function(json){
			 if(json.estado===true) myResource.state.go('administrative.edocumentationCompose',{entityId:json.data.entityId});
			 myResource.myDialog.swalAlert(json);
		},myResource.setError);
	 };
 }])
 /* 
  * DOCUMENTACION ELECTONICA - NUEVO
  */
.controller('administrative.edocumentationComposeCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	 
	// MODELO DE DATOS
	$scope.dataEntity=entity.data;
	
	// MODELO DE DATOS
	$scope.frmParent=entity.data;
	 
	// LISTADO DE DESTINATARIOS
	$scope.subscribeTemp=null;
	$scope.recipientsTemp=null;
	$scope.subscribe=entity.data.subscribe;
	$scope.recipients=entity.data.recipients;
	 
	/*
	 * LISTAR REMITENTE Y DESTINATARIOS
	 */
	$scope.loadStaff=function(){
		myResource.requestData('administrative/edocumentation/detail/byMessageId').save({entityId:$scope.dataEntity.delectronica_id},function(json){
			$scope.subscribe=json.data.subscribe;
			$scope.recipients=json.data.recipients;
		},myResource.setError);
	};

	// SETTEAR MODELO DE REMITENTE Y DESTINATARIO
	$scope.setSender=function(data){
		myResource.requestData('administrative/edocumentation/setSender/byMessageId').update({entityId:$scope.dataEntity.delectronica_id,sender:data},function(json){
			$scope.loadStaff();
		},myResource.setError);
	};
	
	// ELIMINAR DESTINATARIOS
	$scope.deleteRecipient=function(recipientId){
		myResource.requestData('administrative/edocumentation/deleteRecipient/byId').remove({entityId:recipientId},function(json){
			$scope.loadStaff();
		},myResource.setError);
	};
	
	// SETTEAR MODELO DE REMITENTE Y DESTINATARIO
	$scope.setRecipients=function(data){
		myResource.requestData('administrative/edocumentation/setRecipients/byMessageId').update({entityId:$scope.dataEntity.delectronica_id,recipient:data},function(json){
			$scope.loadStaff();
		},myResource.setError);
	};
	
	// ENVIAR DOCUMENTACION
	$scope.submitForm=function(){
		
		myResource.myDialog.swalConfirm('¿Confirma que todos los datos se han ingresado correctamente?',function(){
			
			myResource.uploadFile.uploadFile(rootRequest+'documentacion_electronica/PUT',$scope,'frmParent').then(function(json){
				
				if(json.data.estado) myResource.state.go('administrative.edocumentation');
				
				myResource.myDialog.showNotify(json.data);
				
			},myResource.setError);
			
			
		});
		
	};
	 
 }])
 /*
  * DETALLE DE DOCUMENTACIÓN ELECTRÓNICA
  */
 .controller('administrative.edocumentationDetailCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	// INFORMACION DE ENTIDAD
 	$scope.dataEntity = entity.data;
 }])
 /* 
  * ARCHIVO INSTITUCIONAL
  */
 .controller('administrative.archiveCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'administrative',parent:'Archivo',tb:'archivo',order:'-periodo_nombre',toolbar:{}};
 }])
 /* 
  * SALA DE CAPACITACIONES
  */
 .controller('administrative.trainingroomCtrl',['$scope','myResource',function($scope,myResource){
 	 $scope.tbParams={path:'administrative',parent:'Salacapacitaciones',tb:'salacapacitaciones',order:'-reservacion_fecha_inicio',toolbar:{}};
 }])
/* 
 * SALA DE CAPACITACIONES: CALENDARIO
 */
.controller('administrative.trainingroomCalendarCtrl',['calendar','$scope','myResource',function(calendar,$scope,myResource){
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
    // EVENTO ON CLICK
    $scope.dayClick=function(date) {
    	// PARSE FECHAS
    	date=myResource.filter("date")(date,"y-MM-dd");
    	// CONSULTAR DISPONIBILIDAD POR FECHA
    	myResource.sendData('salacapacitaciones/REQUEST').save({date:date},function(json){
			// CONSULTAR SI EXISTE EL REGISTRO PARA EL CODIGO PER
			if(!json.estado) myResource.myDialog.showNotify(json);
			else $scope.openModal('Salacapacitaciones',json.data);
		},myResource.setError);
        
    };
}])
/* 
 * VALES DE COMBUSTIBLE
 */
.controller('administrative.fuelvouchersCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'administrative',parent:'Ordenescombustible',tb:'getFuelorder',order:'-orden_codigo',toolbar:{}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.filterBarCode={options:{debounce:500},code:'',module:'tthh'};
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
 * *******************************************
 * DIRECCION FINANCIERA
 * *******************************************
 */
 /*
  * ANTICIPOS DE REMUNERACION
  */
.controller('financial.advancesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Anticipos',tb:'getAdvances',order:'-anticipo_solicitado',toolbar:{}};
 	// SELECCIÓN DE PARÁMETROS - ABRIR FORMULARIO
  	$scope.requestEntity=function(data){
  		myResource.sendData('anticipos/REQUEST').save(data,function(json){
  			if(json.estado===true) $scope.openModal($scope.tbParams.parent,angular.merge({edit:false},json.data));
  			else myResource.myDialog.swalAlert(json);
  		}).$promise;
  	}
}])
/*
 * CONTROL PREVIO
 */
.controller('financial.processcontractsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Procesocontratacion',tb:'procesocontratacion',order:'-proceso_codigo',toolbar:{showNew:true}};
}])
 
/*
 * *******************************************
 * DIRECCION PERSONAL - DIRECTORES Y ENCARGADOS
 * *******************************************
 */
 /*
  * SOLICITUDES DE PERMISOS
  */
 .controller('leadership.requestedpermissionsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh',parent:'Permisos',tb:'getRequestedPermissions',order:'-permiso_fecha_desde',toolbar:{}};
 }])
 /*
  * SOLICITUDES DE VACACIONES
  */
 .controller('leadership.requestedvacationsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh',parent:'Vacaciones',tb:'getRequestedVacations',order:'-vacacion_fecha_desde',toolbar:{}};
 }])
 /*
  * LISTADO DE PERSONAL DE LA DIRECCIÓN
  */
 .controller('leadership.staffCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'tthh',entity:'Personal',uri:'tthh/staff/byLeadership',order:'persona_apellidos',toolbar:{},custom:{leadershipId:myResource.stateParams.leadershipId}};
 }])
 /*
  * LICENCIAS DE CONDUCIR DE LA DIRECCION
  */
 .controller('leadership.driverslicensesCtrl',['$scope','myResource',function($scope,myResource){
 	// $scope.tbParams={parent:'Conductores',tb:'getLeadershipDriverlicenses',order:'-conductor_licencia_emision',toolbar:{}};
	 $scope.tbParams={path:'tthh',entity:'Conductores',uri:'tthh/operators/byLeadership',order:'persona_apellidos',toolbar:{},custom:{leadershipId:myResource.stateParams.leadershipId}};
 }])
 /*
  * HOJA DE RUTA DE LA DIRECCION
  */
 .controller('leadership.roadmapCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={parent:'Hojaruta',tb:'getLeadershipRoadmap',order:'-ruta_salida',toolbar:{}};
 }])
 /*
  * ACTIVIDADES DIARIAS DE LA DIRECCION
  */
.controller('leadership.dailyactivitiesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Actividadesdiarias',tb:'getLeadershipDailyactivities',order:'-actividad_fecha_inicio',toolbar:{}};
}])
 /*
  * CURSOS REALIZADOS DE LA DIRECCION
  */
.controller('leadership.academictrainingCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Cursos_realizados',tb:'getLeadershipAcademictraining',order:'-capacitacion_fecha',toolbar:{}};
}])

 




/*
 * *******************************************
 * EXTENDS
 * *******************************************
 */
/* 
 * EXTENDS - SALA DE CAPACITACIONES
 */
.controller('trainingroomExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// GUARDAR PROCESO
	$scope.saveEntity=function(){
		myResource.myDialog.swalConfirm('Realmente desea realizar esta acción?',function(){
			var entity=(!$scope.toogleEditMode)?'':'/PUT';
			myResource.sendData('salacapacitaciones'+entity).save($scope.frmParent,function(json){
				if(json.estado){
					myResource.myDialog.closeDialog();
					myResource.state.go('administrative.trainingroom');
					window.open(reportsURI+'salacapacitaciones/?withDetail&id='+json.data.reservacion_id,'_blank');
				}
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
}])
 /* 
  * Modal: modalPDFViewer
  * Función: 
  */
 .controller('pdfViewerExtendsCtrl',['$scope','myResource',function($scope,myResource){
	 console.log('Extends request -> pdfViewerExtendsCtrl -> Form to reference:: ',$scope.frm);
	 $scope.getIframeSrc = function (src) {
		 return '/app/src/' + src;
	 };
 }])
 /* 
  * Modal: modalExportdata
  * Función: 
  */
 .controller('exportDataExtendsCtrl',['$scope','myResource',function($scope,myResource){
	 console.log('Extends request -> exportDataExtendsCtrl -> Form to reference:: ',$scope.frm);
	 $scope.frmParent.fieldList=angular.fromJson($scope.paramsConf['EXPORTDATA_REPORTS_LIST_MODULES'])[$scope.frmParent.entity];
 }])
 
 /* 
  * Modal: modalX
  * Función: LISTADO DE PAISES 
  */
 .controller('countriesListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('resources/countries').json(function(json){$scope.jsonList=json.data;}).$promise;
 }])
 /* 
  * Modal: modalX
  * Función: LISTADO DE PROVINCIAS 
  */
 .controller('statesListCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.refreshStates=function(){
 		if(!myResource.testNull($scope.frmParent.fk_country_id)) $scope.frmParent.fk_country_id=63;
 		myResource.requestData('resources/states/'+$scope.frmParent.fk_country_id).json(function(json){$scope.statesList=json.data;}).$promise;
 	};	$scope.refreshStates();
 }])
 /* 
  * Modal: modalX
  * Función: LISTADO DE CANTONES 
  */
 .controller('townsListCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.refreshTowns=function(){
 		if(myResource.testNull($scope.frmParent.fk_state_id))
 			myResource.requestData('resources/towns/'+$scope.frmParent.fk_state_id).json(function(json){$scope.townsList=json.data;}).$promise;
 	};	$scope.refreshTowns();
 }])
 /* 
  * Modal: modalX
  * Función: LISTADO DE PARROQUIAS 
  */
 .controller('parishesListCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.refreshParishes=function(){
 		if(myResource.testNull($scope.frmParent.fk_town_id))
 			myResource.requestData('resources/parishes/'+$scope.frmParent.fk_town_id).json(function(json){$scope.parishesList=json.data;}).$promise;
 	};	$scope.refreshParishes();
 }])
 /* 
  * Modal: modalConductores
  * Función: CONSULTAS AUXILIARES DE CONDUCTORES
  */
 .controller('driversLicensesListCtrl',['$scope','myResource',function($scope,myResource){
  	myResource.sendData('resources/REQUEST').save({type:'driverslicenses'},function(json){$scope.list=json.data;}).$promise;
 }])
/* 
 * Modal: modalAnticipos
 * Función: CONSULTAR RECURSOS
 */
.controller('advancesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// CONSULTAR EL REGISTRO POR ID
	if($scope.toogleEditMode){
		myResource.sendData('anticipos/REQUEST').save({entityId:$scope.frmParent.anticipo_id},function(json){
			$scope.frmParent=angular.merge($scope.frmParent,json.data);
		}).$promise;
	}
	// CALCULAR LAS CUOTAS MENSUALES
	$scope.getFee=function(){
		$scope.frmParent.model.anticipo_cuotas=parseFloat($scope.frmParent.model.anticipo_monto/$scope.frmParent.model.anticipo_meses).toFixed(2);
	};
	// REDIRECCIONAR A IMPRESIÓN DE ORDEN DE COBRO
	$scope.$watch('jsonResource',function(newValue,oldValue){
		if(myResource.testNull($scope.jsonResource.anticipo_id)) window.open(reportsURI+'anticipos/?id='+$scope.jsonResource.anticipo_id,'_blank');
	},true);
}])
/*
 * Modal: modalOrdenescombustible
 * Función: 
 */
.controller('fuelOrderExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> fuelOrderExtendsCtrl -> Form to reference:: ',frm);
	// AGREGAR Y ELIMINAR ITEMS
	$scope.addItem=function(entity){ $scope.frmParent[entity].push({}); };
	$scope.removeItem=function(entity,itemKey){ $scope.frmParent[entity].splice(itemKey,1); };
	
}])

/*  
 * Modal: 
 * Función: LISTADO DE INFORMACIO DE PERSONAL ACTIVO
 */
.controller('staffActiveListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/staff/active/list').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
 
/*
 * Modal: modalOrdenescombustible
 * Función: LISTADO DE UNIDADES PARA ORDENES DE ABASTECIMIENTO
 */
.controller('unitsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('administrative/units').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*  
 * Modal: modalFlotasvehiculares
 * Función: LISTADO DE PERSONAL
 */
.controller('staffListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/staffList').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*  
 * Modal: modalFlotasvehiculares
 * Función: LISTADO DE OPERADORES
 */
.controller('driversListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/drivers').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalFlotasvehiculares
 * Función: LISTADO DE ESTACIONES
 */
.controller('stationsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/stations').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalProcesoscontratacion
 * Función: LISTADO 
 */
.controller('contractingproceduresListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('financial/priorcontrol/contractingprocedures').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
;