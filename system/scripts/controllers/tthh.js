/**
 * 
 */
app
/*
 * *******************************************
 * TALENTO HUMANO - PARAMETRIZACIÓN
 * *******************************************
 */
/*
 * ACTIVIDADES DE LOS PUESTOS DE TRABAJO
 */
.controller('activitiesjobsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',parent:'Actividadeslaborales',tb:'actividadeslaborales',order:'actividad_tipo',toolbar:{}};
}])
/*
 * COMPETENCIAS DE LOS PUESTOS DE TRABAJO
 */
.controller('competencesjobsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',parent:'Competenciaslaborales',tb:'competenciaslaborales',order:'competencia_tipo',toolbar:{}};
}])
/*
 * REGLAMENTOS
 */
.controller('regulationsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',parent:'Reglamentos',tb:'reglamentos',order:'reglamento_articulo',toolbar:{showNew:true}};
}])
/*
 * TIPOS DE ACCIONES DE PERSONAL
 */
.controller('typeactionsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',parent:'Tipoacciones',tb:'tipoacciones',order:'tipoaccion_nombre',toolbar:{}};
}])
/*
 * JORNADAS DE TRABAJO
 */
.controller('workdaysCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',entity:'Jornadas_trabajo',uri:'tthh/workdays',order:'jornada_nombre',toolbar:{}};
}])
/*
 * TIPOS DE ANTICIPOS
 */
.controller('typeadvancesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',entity:'Tiposanticipos',uri:'tthh/typeadvances',order:'tanticipo_nombre',toolbar:{}};
}])
/*
 * TIPOS DE CONTRATOS
 */
.controller('typecontractsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',entity:'Tiposcontratos',uri:'tthh/typecontracts',order:'tcontrato_nombre',toolbar:{}};
}])
/*
 * GRUPOS OCUPACIONALES
 */
.controller('occupationalgroupsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/settings',entity:'Gruposocupacionales',uri:'tthh/occupationalgroups',order:'grupo_nombre',toolbar:{}};
}])
/*
 * SISTEMAS DE CALIFICACION
 */
.controller('settings.ratingsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'admin/resources',entity:'Ratingsystem',uri:'resources/resources/tthh/surveys/ratingsystem',order:'recurso_nombre',toolbar:{}};
}])

/*
 * *******************************************
 * TALENTO HUMANO - CALENDARIO
 * *******************************************
 */
/*
 * CALENDARIO DE TALENTO HUMANO
 */
.controller('scheduleCtrl',['calendar','$scope','myResource',function(calendar,$scope,myResource){
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
 * ADMINISTRACIÓN DE ESTADOS DE EMERGENCIA
 */
.controller('emergencyCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh',parent:'Estadosemergencia',tb:'estadosemergencia',order:'-eemergencia_fecha',toolbar:{}};
}])
/*
 * ADMINISTRACIÓN DE EVENTOS DE LA INSTITUCIÓN
 */
.controller('eventsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh',parent:'Eventos',tb:'eventos',order:'-evento_fecha',toolbar:{}};
}])
/*
 * ADMINISTRACIÓN DE DÍAS FESTIVOS
 */
.controller('holidaysCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh',parent:'Feriado',tb:'feriado',order:'-feriado_fecha',toolbar:{showNew:true,showPrint:true}};
	// CREACIÓN DE ARCHIVO DE CONFIGURACIÓN .json
	$scope.createFile=function(){
		myResource.myDialog.swalConfirm('Está seguro de realizar esta operación?.',function(){
			myResource.sendData('feriado/REQUEST').save({data:'12345'},function(json){
				myResource.myDialog.showNotify(json);
			},myResource.setError).$promise;
		});
	};
}])

/*
 * ********************************************************
 * TALENTO HUMANO - INSTITUCIÓN
 * ********************************************************
 */
 /*
  * ESTACIONES
  */
 .controller('stationsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/institution',entity:'Estaciones',uri:'tthh/institution/stations',order:'estacion_nombre',toolbar:{}};
 }])
 /*
  * DETALLE DE ESTACIONES
  */
 .controller('detailStationsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
  	$scope.tbParent='Estaciones';
  	$scope.dataEntity=entity.data;
  	// SET PATH MODAL
  	$scope.$parent.pathEntity='tthh';
 }])
 /*
  * ADMINISTRACIÓN DE BODEGAS
  */
 .controller('wineriesCtrl',['$scope','myResource',function($scope,myResource){
 	// myTableCtrl
 	$scope.tbParams={path:'tthh/institution',entity:'Bodegas',uri:'tthh/institution/wineries/list',order:'bodega_nombre',toolbar:{}};
 }])
 /*
  * DIRECCIONES
  */
.controller('leadershipsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/institution',entity:'Direcciones',uri:'tthh/institution/leaderships',order:'direccion_nombre',toolbar:{}};
}])
/*
 * PUESTOS DE TRABAJO
 */
.controller('jobsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/institution',parent:'Puestos',tb:'puestos',order:'direccion_nombre',toolbar:{showPrint:true,showXLS:true,showNew:true}};
}])
/*
 * ADMINISTRACIÓN DE PELOTONES - NOMBRES
 */
.controller('platoonsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh',parent:'Pelotones',tb:'pelotones',order:'estacion_nombre',toolbar:{showPrint:true,showXLS:true,showNew:true}};
}])
/*
 * CONFIGURACIÓN DE GRADOS DE PERSONAL
 */
.controller('gradesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh',parent:'Grados',tb:'grados',order:'grado_nombre',toolbar:{}};
	// CUSTOM TOOLBAR FILTER - LOCALES
	$scope.filterInTab={mdIcon:'grade',label:'TOOLBAR_UTILS',search:true,showNew:true,showPrint:false};
}])

/*
 * *******************************************
 * TALENTO HUMANO - INFORMACIÓN DE PERSONAS
 * *******************************************
 */
 /*
  * DETALLE DE PERSONAS
  */
 .controller('detailPersonsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	$scope.tbParent='Personas';
 	$scope.dataEntity=entity.data;
 	// SET PATH MODAL
 	$scope.$parent.pathEntity='tthh';
 	// PARÁMETROS PARA TTHH
 	$scope.custom={fk_tabla:'fk_persona_id',fk_tabla_id:$scope.dataEntity.persona_id};
 }])
 /*
  * FORMACIÓN ACADÉMICA
  */
 .controller('academicTrainingPersonCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Formacionacademica',tb:'formacionacademica',order:'-formacion_fingreso',
 					 toolbar:{},
 					 custom:$scope.custom};
 }])
 /*
  * RECONOCIMIENTOS OBTENIDOS
  */
 .controller('awardsPersonCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={parent:'Reconocimientos',tb:'reconocimientos',order:'-reconocimiento_fecha_recepcion',
			 toolbar:{},
			 custom:$scope.custom};
 }])
 /*
  * CAPACITACIONES REALIZADAS
  */
 .controller('trainingsPersonCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Cursos_realizados',tb:'cursos_realizados',order:'-capacitacion_fecha',
 					 toolbar:{},
 					 custom:$scope.custom};
 }])
 /*
  * EXPERIENCIAS LABORALES
  */
 .controller('employmentsPersonCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Experiencias_laborales',tb:'experiencias_laborales',order:'-experiencia_fingreso',
 					 toolbar:{},
 					 custom:$scope.custom};
 }])
 /*
  * CARGAS FAMILIARES
  */
 .controller('familyResponsibilitiesPersonCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Cargas_familiares',tb:'cargas_familiares',order:'persona_fnacimiento',
 			toolbar:{},
 			custom:$scope.custom};
 }])
 /*
  * DECLARACIONES JURAMENTADAS
  */
 .controller('affidavitsPersonCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Declaraciones_juramentadas',tb:'declaraciones_juramentadas',order:'-declaracion_ftthh',
 			toolbar:{},
 			custom:$scope.custom};
 }])
 /*
  * FUNCIONES DEL PERSONAL
  */
 .controller('performancePersonCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Cargos',tb:'personal_cargos',order:'-cargo_fecha_ingreso',
 			toolbar:{},
 			custom:$scope.customStaff};
 }])
 /*
  * FUNCIONES DEL PERSONAL
  */
 .controller('driversPersonCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'conductores',tb:'conductores',order:'-conductor_licencia_validez',
 			toolbar:{},
 			custom:$scope.customStaff};
 }])
 
 /*
  * *******************************************
  * TALENTO HUMANO - PERSONAL
  * *******************************************
  */
/*
 * PERSONAL DE CBSD
 */
.controller('staffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Personal',tb:'personal',order:'personal_nombre',toolbar:{showNew:true}};
	// IMPRIMIR CV
	$scope.downloadCV=function(id){
		window.open($scope.setting.uri.reports+'personal/?withDetail&type=PDF&account=tthh&id='+id,'_blank');
	};
}])
/*
 * DETALLE DE PERSONAS
 */
.controller('detailStaffCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.tbParent='Personal';
	$scope.dataEntity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='tthh';
	// PARÁMETROS PARA REGISTROS DE PERSONAS
	$scope.custom={fk_tabla:'fk_persona_id',fk_tabla_id:$scope.dataEntity.fk_persona_id};
	// PARÁMETROS PARA REGISTROS DE PERSONAL
	$scope.customStaff={fk_tabla:'fk_personal_id',fk_tabla_id:$scope.dataEntity.personal_id};
	$scope.customStaffId={fk_tabla:'personal_id',fk_tabla_id:$scope.dataEntity.personal_id};
	// IMPRIMIR CV
	$scope.downloadCV=function(){
		window.open($scope.setting.uri.reports+'personal/?withDetail&type=PDF&account=tthh&id='+$scope.dataEntity.personal_id,'_blank');
	};
}])
/*
 * VACACIONES DEL PERSONAL
 */
.controller('vacationsStaffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Personal_vacaciones',tb:'vacaciones_solicitadas',order:'-vacacion_fecha_desde',
			toolbar:{},
			custom:$scope.customStaff};
}])
/*
 * PERMISOS DEL PERSONAL
 */
.controller('permissionsStaffCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={parent:'Personal_permisos',tb:'permisos_solicitados',order:'-permiso_fecha_desde',
			toolbar:{},
			custom:$scope.customStaff};
}])
/*
 * ACCIONES DE PERSONAL DEL PERSONAL
 */
.controller('staffActionsStaffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Accionespersonal',tb:'accionespersonal',order:'-accion_codigo',
			toolbar:{},
			custom:$scope.customStaffId};
}])
/*
 * SANCIONES DEL PERSONAL
 */
.controller('sanctionsStaffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Sanciones',tb:'sanciones',order:'-sancion_fecha',
			toolbar:{},
			custom:$scope.customStaff};
}])
/*
 * ANTICIPOS DE SUELDOS DEL PERSONAL
 */
.controller('advancesStaffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Anticipos',tb:'anticipos',order:'-anticipo_solicitado',
			toolbar:{},
			custom:$scope.customStaffId};
}])
/*
 * HOJA DE RUTA DEL PERSONAL
 */
.controller('roadmapStaffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Hojaruta',tb:'hojaruta',order:'-ruta_salida',
			toolbar:{},
			custom:$scope.customStaffId};
}])
/*
 * ACTIVIDADES DIARIAS DEL PERSONAL
 */
.controller('dailyActivitiesStaffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Actividadesdiarias',tb:'actividadesdiarias',order:'-actividad_fecha_inicio',
			toolbar:{},
			custom:$scope.customStaffId};
}])

/*
 * *******************************************
 * TALENTO HUMANO - HOJA DE VIDA
 * *******************************************
 */
 /*
  * FORMACIÓN ACADÉMICA
  */
 .controller('academicTrainingCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Formacionacademica',tb:'personal_formacionacademica',order:'-formacion_fingreso',toolbar:{}};
 }])
 /*
  * RECONOCIMIENTOS OBTENIDOS
  */
 .controller('awardsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={parent:'Reconocimientos',tb:'reconocimientos',order:'-reconocimiento_fecha_recepcion',toolbar:{}};
 }])
 /*
  * CAPACITACIONES REALIZADAS
  */
 .controller('coursescompletedCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Cursos_realizados',tb:'cursos_realizados',order:'-capacitacion_fecha',toolbar:{}};
 }])
 /*
  * EXPERIENCIAS LABORALES
  */
 .controller('employmentsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Experiencias_laborales',tb:'experiencias_laborales',order:'-experiencia_fingreso',toolbar:{}};
 }])
 /*
  * DECLARACIONES JURAMENTADAS
  */
 .controller('affidavitsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Declaraciones_juramentadas',tb:'declaraciones_juramentadas',order:'-declaracion_ftthh',toolbar:{}};
 }])
 /*
  * CARGAS FAMILIARES
  */
 .controller('familiarsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Cargas_familiares',tb:'cargas_familiares',order:'persona_fnacimiento',toolbar:{}};
 }])
 /*
  * HOJAS DE RUTA
  */
 .controller('roadmapCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Hojaruta',tb:'hojaruta',order:'-ruta_salida',toolbar:{}};
 }])
 /*
  * ACTIVIDADES DIARIAS DEL PERSONAL
  */
 .controller('dailyactivitiesCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Actividadesdiarias',tb:'actividadesdiarias',order:'-actividad_fecha_inicio',toolbar:{}};
 }])
 
/*
 * *******************************************
 * TALENTO HUMANO - CONTROL DE ASISTENCIA
 * *******************************************
 */
 /*
  * RELOJ BIOMETRICO
  */
.controller('attendance.biometriccodesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Personal',uri:'tthh/attendance/biometriccodes',order:null,toolbar:{}};
}])
 /*
  * REGISTRO DE PERIODOS
  */
.controller('attendance.biometricperiodsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Biometrico_periodos',uri:'tthh/attendance/biometricperiods',order:'-periodo_desde',toolbar:{}};
	// ELIMINAR MARCACIONES DE UN PERIODO
	$scope.deleteAllMarkings=function(data){
		myResource.myDialog.swalConfirm('¿Relamente desea eliinar todas las marcaciones de este periodo?',function(){
			myResource.requestData('tthh/attendance/biometric/staff/markings/remove/periodId').remove(data,function(json){
				myResource.myDialog.showNotify(json);
			},myResource.setError);
		});
	};
}])
/*
 * REGISTRO DE MARCACIONES
 */
.controller('attendance.biometricmarkingsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Biometrico_marcaciones',uri:'tthh/attendance/biometricmarkings',order:'-marcacion_ingreso',toolbar:{},custom:{periodId:myResource.stateParams.id}};
	// ID DE REGISTRO DE PERIODO
	$scope.periodId=myResource.stateParams.id;
}])

/*
 * REGISTRO DE MARCACIONES CON ERRORES
 */
.controller('attendance.biometricnomarkingsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Biometrico_marcaciones',uri:'tthh/attendance/biometricperiods/nomarkings',order:'-marcacion_ingreso',toolbar:{},custom:{periodId:myResource.stateParams.id}};
	// ID DE REGISTRO DE PERIODO
	$scope.periodId=myResource.stateParams.id;
	$scope.entity=entity.data;
	
	// GUARDAR CAMBIOS
	$scope.saveNomarkingsList=function(data){
		myResource.myDialog.swalConfirm('Antes de continuar confirme que sus datos son correctos [<b>valor de rancho, jornada, marcaciones, etc</b>]',function(){
			myResource.requestData('tthh/attendance/biometric/staff/markings/list').update(data,function(json){
				if(json.estado===true){ setTimeout(function(){ $scope.getResourcesParent(); }, 1000); }
				myResource.myDialog.showNotify(json);
			}).$promise;
		});
	};

	// LISTADO DE PERSONAL
	$scope.workdaysList=[];
	myResource.requestData('tthh/workdays').json(function(json){ $scope.workdaysList=json.data; }).$promise;
}])

/*
 * REGISTRO DE PERSONAL QUE NO REGISTRA MARCACIONES
 */
.controller('attendance.staffnomarkingsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Biometrico_marcaciones',uri:'tthh/attendance/biometricperiods/staff',order:'persona_apellidos',toolbar:{},custom:{periodId:myResource.stateParams.id}};
	// ID DE REGISTRO DE PERIODO
	$scope.periodId=myResource.stateParams.id;
}])
/*
 * REGISTRO DE NO MARCACIONES
 */
.controller('attendance.biometricstaffnomarkingsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Biometrico_marcaciones',uri:'tthh/attendance/biometricperiods/staff/nomarkings',order:'-marcacion_ingreso',toolbar:{},custom:{periodId:myResource.stateParams.id,staffId:myResource.stateParams.staffId}};
	// ID DE REGISTRO DE PERIODO
	$scope.periodId=myResource.stateParams.id;
	$scope.entity=entity.data;
	
	// GUARDAR CAMBIOS
	$scope.saveNomarkingsList=function(data){
		myResource.myDialog.swalConfirm('Antes de continuar confirme que sus datos son correctos [<b>valor de rancho, jornada, marcaciones, etc</b>]',function(){
			myResource.requestData('tthh/attendance/biometric/staff/markings/list').update(data,function(json){
				if(json.estado===true){ setTimeout(function(){ $scope.getResourcesParent(); }, 1000); }
				myResource.myDialog.showNotify(json);
			}).$promise;
		});
	};

	// LISTADO DE PERSONAL
	$scope.workdaysList=[];
	myResource.requestData('tthh/workdays').json(function(json){ $scope.workdaysList=json.data; }).$promise;
}])
/*
 * ATRASOS
 */
.controller('attendance.arrearsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/attendance',entity:'Atrasos',uri:'tthh/attendance/arrears',order:'personal_nombre',toolbar:{}};
}])
/*
 * RANCHO - OPERATIVOS
 */
.controller('attendance.ranchesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Ranchos',uri:'tthh/attendance/ranches',order:'personal_nombre',toolbar:{}};
}])
/*
 * CONTROL DE AUSENCIAS
 */
.controller('attendance.absencesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/attendance',entity:'Inasistencias',uri:'tthh/attendance/absences',order:'-inasistencia_desde',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.requestData('tthh/staff/requestEntity').save({type:'personCC',data:$scope.filterBarCode.code},function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal('Inasistencias',angular.merge(json.data,{edit:false}));
			}
		},myResource.setError);
	};
}])


/*
 * *******************************************
 * TALENTO HUMANO - DEPARTAMENTO MEDICO
 * *******************************************
 */
/*
 * CIE
 */
.controller('cieCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/md',parent:'Cie',tb:'cie',order:'cie_codigo',toolbar:{showNew:true}};
}])
/*
 * MEDICAMENTOS
 */
.controller('suppliesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/md',parent:'Medicamentos',tb:'medicamentos',order:'medicamento_nombre',toolbar:{showNew:true}};
}])
/*
 * INVENTARIO DE MEDICAMENTOS
 */
.controller('inventoryMedicinesCtrl',['medicines','$scope','myResource',function(medicines,$scope,myResource){
	// LISTADO DE MEDICINAS
	$scope.medicinesList=medicines.data;
	// RECARGAR LISTADO DE MEDICAMENTOS
	$scope.reloadList=function(){
		myResource.requestData('tthh/md/pharmacy/supplies/inventory').query(function(json){
			myResource.myDialog.showNotify(json);
			$scope.medicinesList=json.data;
		});
	};
	// GUARDAR REGISTRO INDIVIDUAL
	$scope.saveItem=function(item){
		myResource.requestData('tthh/md/pharmacy/supplies/inventory').save(item,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true) $scope.reloadList();
		});
	};
	// ENVIAR FORMULARIO
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('¿Confirma que todos los datos están correctos?',function(){
			myResource.sendData('inventario_medicamentos').update($scope.medicinesList,function(json){
				myResource.myDialog.showNotify(json);
				if(json.estado===true) $scope.reloadList();
			},myResource.setError);
		});
	};
	
	// IMPRESION DE STOCK
	$scope.printStock=function(){
		$scope.$parent.exportById('stockfarmacia',666);
	};
}])
/*
 * HISTORIAS CLINICAS
 */
.controller('medicalhistoriesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/md',parent:'Historiasclinicas',tb:'historiasclinicas',order:'personal_nombre',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.getData('historiasclinicas/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
/*
 * DETALLE DE HISTORIAS CLINICAS
 */
.controller('detailMedicalhistoriesCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.tbParent='Historiasclinicas';
	$scope.dataEntity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='tthh/md';
	// PARÁMETROS PARA REGISTROS DE PERSONAS
	$scope.custom={fk_tabla:'fk_historiaclinica_id',fk_tabla_id:$scope.dataEntity.historia_id};
}])
/*
 * ANTECEDENTES DEL PACIENTE
 */
.controller('backgroundMedicalhistoryCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Antecedentesenfermedades',tb:'antecedentesenfermedades',order:'antecedente_enfermedad',
					 toolbar:{},
					 custom:$scope.custom};
}])
/*
 * CONSULTAS MÉDICAS RELACIONADAS CON LAS HISTORIAS CLINICAS
 */
.controller('consultationsMedicalhistoryCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Consultasmedicas',tb:'consultasmedicas',order:'-consulta_fecha_consulta',
			toolbar:{},
			custom:$scope.custom};
}])
/*
 * CONSULTAS MEDICAS
 */
.controller('medicalconsultationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/md',parent:'Consultasmedicas',tb:'consultasmedicas',order:'-consulta_fecha_consulta',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.getData('consultasmedicas/REQUEST').save($scope.filterBarCode,function(json){
			// CONSULTAR SI EXISTE EL REGISTRO PARA EL CODIGO PER
			if(json.estado===true) myResource.state.go('tthh.medical.newMedicalConsultation',{historyId:json.data.historia_id});
			else myResource.myDialog.showNotify(json);
		},myResource.setError);
	};
	// EDICIÓN DE CONSULTA
	$scope.goUpdateEntity=function(id){
		myResource.state.go('tthh.medical.editMedicalConsultation',{entityId:id});
	};
}])
/*
 * REGISTRO DE CONSULTAS MÉDICAS
 */
.controller('newMedicalConsultationCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.tbParent='Consultasmedicas';
	$scope.dataEntity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='tthh/md';
	// INICIALIZACIÓN DE FORMULARIO
	$scope.frmParent=$scope.dataEntity;
	// IMC
	$scope.calcIMC=function(){
		$scope.frmParent.consulta_imc=($scope.frmParent.consulta_peso/($scope.frmParent.consulta_estatura*$scope.frmParent.consulta_estatura)).toFixed(2);
	};	$scope.calcIMC();
	// ENVIAR FORMULARIO
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('¿Confirma que todos los datos están correctos?',function(){
			myResource.sendData('consultasmedicas').update($scope.frmParent,function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true){
					 // IMPRIMIR CONSULTA MEDICA
					 window.open(reportsURI+'consultasmedicas/?id='+json.data.id, '_blank');
					 // IMPRIMIR RECETA
					 if($scope.frmParent.consulta_medicamento=='SI')window.open(reportsURI+'prescripcionmedica/?id='+json.data.id, '_blank');
					 // IMPRIMIR DESCANSO MEDICO
					 if($scope.frmParent.consulta_descansomedico=='SI')window.open(reportsURI+'certificadosmedicos/?id='+json.data.id, '_blank');
					 // VOLVER A CONSULTAS MEDICAS
					 myResource.state.go('tthh.medical.medicalconsultations');
				 }
			},myResource.setError);
		});
	};

	// OBTENER LISTADO DE CIE10
	var fk={
		medicines: {name:'fk_medicines',list:'medicinesList',container:'dose'},
		cie: {name:'fk_cie',list:'cieList',container:'diagnosis'}
	};
	$scope.getExternalData=function(uri,entity,filter){
		myResource.requestData(uri).json({filter:filter},function(data){ $scope[fk[entity].list]=data.data; });
	};
	// ADJUNTAR LISTADO DE CIE
	$scope.loadExternalData=function(entity,val){
		// VALIDAR TIPO DE ITEM
		if(entity=='medicines'){
			val.receta_medicamento_tipo='INTERNO';
			val.receta_cantidad=1;
		} else {
			val.diagnostico_estado='PRESUNTIVO';
		}
		// INGRESAR NUEVO ITEM
		$scope.frmParent[fk[entity].container].push(val);
	};
	$scope.loadNewExternal=function(){
		// VALIDAR TIPO DE ITEM
		val={
			receta_medicamento_tipo: 'EXTERNO',
			receta_cantidad: 1,
			total_medicamento: 100
		};
		// INGRESAR NUEVO ITEM
		$scope.frmParent.dose.push(val);
	}
	// REMOVER TRABAJO A REALIZAR
	$scope.removeExternalData=function(entity,itemKey){
		 delete $scope.frmParent[fk[entity].container].splice(itemKey,1);
	};
	// REMOVER TRABAJO A REALIZAR
	$scope.removeItem=function(idx){
		 delete $scope.frmParent.fk_cie[idx];
	};
	// VISTA PREVIA PARA RECETAS Y CERTIFICADOS
	$scope.printPreviewModel=function(entityModel){
		myResource.myDialog.swalConfirm('Para realizar esta operación se guardarán los cambios de este registro...¿Desea continuar?',function(){
			myResource.sendData('consultasmedicas').update($scope.frmParent,function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true){
					 $scope.$parent.exportEntity(entityModel,json.data.id);
					 myResource.state.go('tthh.medical.editMedicalConsultation',{entityId:json.data.id});
				 }
			},myResource.setError);
		});
	};
}])
/*
 * RECETAS MEDICAS
 */
.controller('medicalprescriptionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/md',parent:'Recetasmedicas',tb:'recetasmedicas',order:'-consulta_fecha_consulta',toolbar:{}};
}])
/*
 * DESCANSOS MEDICOS
 */
.controller('medicalrestCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/md',parent:'Certificadosmedicos',tb:'certificadosmedicos',order:'-fecha_emision',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.requestData('tthh/staff/requestEntity').save({type:'personCC',data:$scope.filterBarCode.code},function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal('Certificadosmedicos',angular.merge(json.data,{edit:false}));
			}
		},myResource.setError);
	};
	// REENVIAR NOTIFICACION DE CORREO ELECTRONICO
	$scope.resendNotification=function(certificateId){
		myResource.myDialog.swalConfirm('¿Realmente confirma que los datos son correctos y desea reenviar la notificación?',function(){
			myResource.sendData('certificadosmedicos/REQUEST').save({type:'notification',entityId:certificateId},function(json){
				 myResource.myDialog.showNotify(json);
			},myResource.setError);
		});
	};
}])
.controller('medicalrest.recipientsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh/md',entity:'Certificadosmedicos_destinatarios',uri:'tthh/md/medicalrest/recipients/list',order:'-destinatario_registro',toolbar:{showNew:true}};
	
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.requestData('tthh/md/medicalrest/recipients/new').save(angular.merge($scope.filterBarCode,{fkId:$scope.session.persona_id}),function(json){
			// CONSULTAR SI EXISTE EL REGISTRO PARA EL CODIGO PER
			if(json.estado===true) $scope.filterBarCode.code='';
			else myResource.myDialog.showNotify(json);
		},myResource.setError);
	};
}])


/*
 * *******************************************
 * TALENTO HUMANO - ANTICIPOS DE REMUNERACION
 * *******************************************
 */
 /*
  * PARAMETRIZACION
  */
 .controller('advances.parametrizationCtrl',['$scope','myResource',function($scope,myResource){
 	
	 // myResource.getTempData('paramsConf')['ADVANCE_ALLOW_PARAMS']
	 $scope.configAdvances=angular.fromJson($scope.setting.ADVANCE_ALLOW_PARAMS);
	 
	 // ACTUALIZAR REGISTRO
	 $scope.submitForm=function(){
		 
		 myResource.requestData('admin/parameters/singleUpdate').update({entityId:865,data:$scope.configAdvances},function(json){
			myResource.myDialog.showNotify(json);
		},myResource.setError);
		 
	 };
	 
 }])
 /*
  * BANCOS
  */
 .controller('advances.banksCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh',parent:'Cuentasbancarias',tb:'cuentasbancarias',order:'titular',toolbar:{}};
 }])
 /*
  * GARANTES
  */
 .controller('advances.garantsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh',parent:'Garantes',tb:'garantes',order:'-anticipo_solicitado',toolbar:{}};
 }])
/*
 * SOLICITUD DE ANTICIPOS
 */
.controller('advances.advancesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Anticipos',tb:'anticipos',order:'-anticipo_solicitado',toolbar:{}};
	// VALIDAR CODIGO DE SOLICITUD
	$scope.scanBarCode=function(){
		myResource.sendData('anticipos/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])




/*
 * *******************************************
 * TALENTO HUMANO - PERSONAL OPERATIVO
 * *******************************************
 */
/*
 * DISTRIBUTIVO
 */
.controller('distributionCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Distributivo',tb:'distributivo',order:'-distributivo_periodo_inicio',toolbar:{}};
}])
/*
 * ASIGNACION DEL PERSONAL A DISTRIBUTIVO
 */
.controller('distribution.settingCtrl',['data','$scope','myResource',function(data,$scope,myResource){
	
	$scope.frmParent = data.data;
	$scope.filterData="";
	
	/**
	 * dnd-dragging determines what data gets serialized and send to the receiver
	 * of the drop. While we usually just send a single object, we send the array
	 * of all selected items here.
	 */
	$scope.getSelectedItemsIncluding = function(list, item) {
	  item.selected = true;
	  return list.items.filter(function(item) { return item.selected; });
	};
	
	/**
	 * We set the list into dragging state, meaning the items that are being
	 * dragged are hidden. We also use the HTML5 API directly to set a custom
	 * image, since otherwise only the one item that the user actually dragged
	 * would be shown as drag image.
	 */
	$scope.onDragstart = function(list, event) {
	   list.dragging = true;
	   if (event.dataTransfer.setDragImage) {
	     var img = new Image();
	     img.src = 'framework/vendor/ic_content_copy_black_24dp_2x.png';
	     event.dataTransfer.setDragImage(img, 0, 0);
	   }
	};
	
	/**
	 * In the dnd-drop callback, we now have to handle the data array that we
	 * sent above. We handle the insertion into the list ourselves. By returning
	 * true, the dnd-list directive won't do the insertion itself.
	 */
	$scope.onDrop = function(list, items, index) {
	  angular.forEach(items, function(item) { item.selected = false; });
	  list.items = list.items.slice(0, index)
	              .concat(items)
	              .concat(list.items.slice(index));
	  return true;
	}
	
	/**
	 * Last but not least, we have to remove the previously dragged items in the
	 * dnd-moved callback.
	 */
	$scope.onMoved = function(list) {
	  list.items = list.items.filter(function(item) { return !item.selected; });
	};
	
	// ENVIAR FORMULARIO
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('¿Confirma que todos los datos están correctos?',function(){
			myResource.sendData('peloton_personal').update($scope.frmParent,function(json){
				 myResource.myDialog.showNotify(json);
				 if(json.estado===true) myResource.state.go('tthh.operationalStaff.distribution');
			},myResource.setError);
		});
	};
	
}])
/*
 * PERSONAL OPERATIVO DEL CBSD
 */
.controller('platoonsStaffCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Peloton_personal',tb:'personal_operativo',order:'personal_nombre',toolbar:{}};
}])


/*
 * *******************************************
 * TALENTO HUMANO - VACACIONES
 * *******************************************
 */
/*
 * VACACIONES
 */
.controller('vacationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/vacations',parent:'Vacaciones',tb:'vacaciones',order:'personal_nombre',toolbar:{}};
	// CALCULAR LAS VACACIONES DEL PERSONAL
	$scope.calculateVacations=function(){
		myResource.myDialog.swalConfirm('Está seguro de realizar esta operación?.',function(){
			myResource.sendData('vacaciones/REQUEST').save({type:'calculator'},function(json){
				myResource.myDialog.showNotify(json);
			},myResource.setError).$promise;
		});
	};
}])
/*
 * DETALLE DE VACACIONES VACACIONES
 */
.controller('detailVacationsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.tbParent='Vacaciones';
	$scope.vacationsEntity=entity.data.vacations;
	$scope.dataEntity=$scope.mergeData(entity.data.data,entity.data.personal);
	// SET PATH MODAL
	$scope.$parent.pathEntity='tthh/vacations';
	// RECORDS
	$scope.historyParams={order:'-vacacion_registro',
						  custom:{entity:'vacaciones',id:$scope.dataEntity.vacacion_id}};
	// PARÁMETROS PARA REGISTROS DE PERSONAL
	$scope.customStaff={fk_tabla:'fk_personal_id',fk_tabla_id:$scope.dataEntity.personal_id};
}])
/*
 * SOLICITUDES DE VACACIONES
 */
.controller('requestedvacationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/vacations',parent:'Vacaciones',tb:'vacaciones_solicitadas',order:'-vacacion_fecha_desde',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.getData('personal_vacaciones/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
/*
 * SOLICITUDES PERMISOS DE PERSONAL
 */
.controller('requestedpermissionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/vacations',parent:'Personal_permisos',tb:'permisos_solicitados',order:'-permiso_fecha_desde',toolbar:{}};
	// OPEN MODAL - INGRESO DE TRAMITE A CBSD
	$scope.scanBarCode=function(){
		myResource.getData('personal_permisos/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])

/*
 * *******************************************
 * TALENTO HUMANO - PERSONAL
 * *******************************************
 */
/*
 * SANCIONES DE PERSONAL
 */
.controller('sanctionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Sanciones',tb:'sanciones',order:'-sancion_fecha',toolbar:{}};
}])
/*
 * SANCIONES DE PERSONAL
 */
.controller('operatorsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Conductores',tb:'conductores',order:'conductor',toolbar:{}};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.getData('conductores/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])

/*
 * *******************************************
 * TALENTO HUMANO - EVALUACIONES DE DESEMPEÑO
 * *******************************************
 */
/*
 * EVALUACIONES DE DESEMPEÑO
 */
.controller('performancesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Evaluacionesdesempenio',tb:'evaluacionesdesempenio',order:'-evaluacion_periodo_desde',toolbar:{}};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.getData('evaluacionesdesempenio/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				myResource.state.go('tthh.performancesPersonal',json.data);
			}
		},myResource.setError);
	};
}])
/*
 * DETALLE DE EVALUACIONES DE DESEMPEÑO
 */
.controller('detailPerformancesCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.entity=entity.data;
	// IMPRIMIR EVALUACIÓN DE DESEMPEÑO
	$scope.printEvaluation=function(id){
		window.open(reportsURI+'evaluacionespersonal/?id='+id,'_blank');
	};
}])
/*
 * EVALUACION DE DESEMPEÑO
 */
.controller('performancesPersonalCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DATOS DE REQUEST
	$scope.dataEntity=entity.data;
	$scope.model=entity.data.model;
	// CALCULOS - ACTIVIDADES
	$scope.activityLevel=function(model){
			 if(model.actividad_cumplimiento>=90.5) model.actividad_nivel=5;
		else if(model.actividad_cumplimiento>=80.5 && model.actividad_cumplimiento<=90.4) model.actividad_nivel=4;
		else if(model.actividad_cumplimiento>=70.5 && model.actividad_cumplimiento<=80.4) model.actividad_nivel=3;
		else if(model.actividad_cumplimiento>=60.5 && model.actividad_cumplimiento<=70.4) model.actividad_nivel=2;
		else if(model.actividad_cumplimiento<=60.41) model.actividad_nivel=1;
	};
	// ENVIAR EVALUACION
	$scope.savePerformance=function(){
		myResource.myDialog.swalConfirm('Por favor, revise detenidamente el formulario antes de realizar esta acción.',function(){
			myResource.sendData('evaluaciones_personal').save($scope.dataEntity,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					// REDIRIGIR A EVALUACIONES DE DESEMPEÑO
					myResource.state.go('tthh.performances');
					// IMPRIMIR CALIFICACION
					window.open(reportsURI+'evaluacionespersonal/?id='+json.data.desempenio_id,'_blank');
				}
			},myResource.setError);
		});
	};
}])

/*
 * *******************************************
 * TALENTO HUMANO - ACCIONES DE PERSONAL
 * *******************************************
 */
/*
 * ACCIONES DE PERSONAL
 */
.controller('staffactionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Accionespersonal',tb:'accionespersonal',order:'-accion_codigo',toolbar:{}};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.getData('accionespersonal/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])
/*
 * DELEGACIONES
 */
.controller('delegationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Delegaciones',tb:'delegaciones',order:'-delegacion_codigo',toolbar:{}};
	// OPEN MODAL - TRANSPORTE DE COMBUSTIBLE
	$scope.scanBarCode=function(){
		myResource.getData('delegaciones/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data);
			}
		},myResource.setError);
	};
}])

/*
 * *******************************************
 * TALENTO HUMANO - EVALUACIONES
 * *******************************************
 */
 /*
  * FORMULARIOS PARA EVALUACIONES
  */
 .controller('surveys.formsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'admin/resources',entity:'Formulariosevaluaciones',uri:'resources/resources/tthh/surveys/forms',order:'formulario_nombre',toolbar:{}};
 }])
 /*
  * DETALLE DE LOS FORMULARIOS - SECCIONES
  */
 .controller('surveys.detailFormsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	// INFORMACION DE ENTIDAD
 	$scope.dataEntity=entity.data;
	// PARÁMETROS PARA REGISTROS DE PERSONAS
	$scope.custom={formId:$scope.dataEntity.formulario_id};
 }])
 /*
  * SECCIONES DE LOS FORMULARIOS
  */
 .controller('surveys.formSectionsCtrl',['$scope','myResource',function($scope,myResource){
	 // myTableCtrl
	 $scope.tbParams={path:'admin/resources',entity:'Formulariosevaluaciones_secciones',uri:'resources/resources/tthh/surveys/forms/sections',order:'seccion_index',toolbar:{},custom:$scope.custom};
 }])
 /*
  * DETALLE DE LAS SECCIONES
  */
 .controller('surveys.detailFormSectionsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	// INFORMACION DE ENTIDAD
 	$scope.dataEntity=entity.data;
 }])
 
 /*
  * EVALUACIONES PROGRAMADAS
  */
.controller('surveys.evaluationsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'tthh/surveys',entity:'Evaluacionespersonal',uri:'resources/resources/tthh/surveys/evaluations',order:'-evaluacion_inicio',toolbar:{}};
}])
 /*
  * DETALLE DE EVALUACIONES PROGRAMADAS
  */
.controller('surveys.detailEvaluationsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	 
	 // INFORMACION DE ENTIDAD
	 $scope.dataEntity=entity.data.entity;
	 $scope.entityInfo={
		evaluations:$scope.dataEntity.evaluations,
		alerts:entity.data.alerts,
	 };
	 
}])
 

 /*
  * *******************************************
  * TALENTO HUMANO - SEGURIDAD Y SALUD OCUPACIONAL - USO DE LA CASCADA
  * *******************************************
  */
 /*
  * FILTROS PARA LA CASCADA
  */
 .controller('sos.filtersCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/sos/filters',parent:'Filtrocascada',tb:'filtrocascada',order:'-filtro_fecha_ingreso',toolbar:{}};
 }])
 /*
  * USO DE LA CASCADA
  */
 .controller('sos.waterfallCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/sos/filters',parent:'Fichacascada',tb:'fichacascada',order:'-cascada_hora_inicio',toolbar:{}};
 }])
 
 /*
  * *******************************************
  * TALENTO HUMANO - SEGURIDAD Y SALUD OCUPACIONAL - INSPECCION DE EXTINTORES
  * *******************************************
  */
 /*
  * LISTADO DE EXTINTORES
  */
 .controller('sos.fireextinguisherCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/sos/fextinguisher',entity:'Extintores',uri:'tthh/sos/fextinguisher/extinguishers/list',order:'extintor_serie',toolbar:{}};
 }])
 /*
  * INSPECCIONES REALIZADAS
  */
 .controller('sos.fireextinguisherInspectionsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/sos/fextinguisher',entity:'Inspeccionextintores',uri:'tthh/sos/fextinguisher/extinguishers/inspections/list',order:'-inspeccion_codigo',toolbar:{}};
 }])
 /*
  * NUEVO REGISTRO DE USO DE EQUIPOS DE RESCATE VERTICAL
  */
 .controller('sos.reviewsFextinguisherCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	 
 	// INFORMACION DE EQUIPO
	 $scope.dataEntity = entity.data;
	 
  	// SET PATH MODAL
  	$scope.$parent.setPathModal('tthh/sos/fextinguisher');
	 
 }])

/*
 * *******************************************
 * TALENTO HUMANO - SEGURIDAD Y SALUD OCUPACIONAL - RESCATE VERTICAL
 * *******************************************
 */
/*
 * FORMULARIOS DE DAÑOS
 */
.controller('sos.vrescueDamageformsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin/resources',entity:'Formulariosevaluaciones',uri:'resources/resources/tthh/vrescue/forms',order:'formulario_nombre',toolbar:{}};
}])
 /*
  * DETALLE DE LOS FORMULARIOS - SECCIONES
  */
 .controller('sos.vrescueDetailFormsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	// INFORMACION DE ENTIDAD
 	$scope.dataEntity=entity.data;
	// PARÁMETROS PARA REGISTROS DE PERSONAS
	$scope.custom={formId:$scope.dataEntity.formulario_id};
 }])
 
 /*
  * CATEGORIA DE EQUIPOS DE RESCATE VERTICAL
  */
 .controller('sos.equipmentcategoriesCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/sos/vrescue',entity:'Categoria_equiposrescatevertical',uri:'tthh/sos/vrescue/categories/equipments/list',order:'categoria_nombre',toolbar:{}};
 }])
 /*
  * ESQUIPOS DE RESCATE VERTICAL
  */
 .controller('sos.equipmentsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/sos/vrescue',entity:'Equiposrescatevertical',uri:'tthh/sos/vrescue/equipments/list',order:'equipo_serial',toolbar:{}};
 }])
 /*
  * DETALLE DE ESQUIPOS DE RESCATE VERTICAL
  */
 .controller('sos.detailEquipmentsCtrl',['entity','usage','$scope','myResource',function(entity,usage,$scope,myResource){
	 // INFORMACION DE ENTIDAD
	 $scope.dataEntity = entity.data;
	 $scope.dataEntity.usage = usage.data;
	 
	 console.log(entity.data);
	 console.log(usage.data);
	 
 }])
 /*
  * NUEVO REGISTRO DE USO DE EQUIPOS DE RESCATE VERTICAL
  */
 .controller('sos.equipmentUsageCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
 	// INFORMACION DE EQUIPO
	 $scope.dataEntity = entity.data;
	 
	 // FORMULARIO DE REGISTRO
	 if(myResource.testNull($scope.dataEntity.history)){
		 $scope.frmParent = $scope.dataEntity.history;
		 $scope.frmParent.test = $scope.dataEntity.test;
		 $scope.toogleEditMode=true;
	 }else{
		 $scope.frmParent = {
			fk_equipo_id: $scope.dataEntity.equipment.equipo_id,
			fk_formulario_id: $scope.dataEntity.equipment.fk_formulario_id,
			fk_peloton_id: $scope.dataEntity.equipment.fk_peloton_id
		 };
		 $scope.toogleEditMode=false;
	 }
	 
	 // GUARDAR FORULARIO
	 $scope.submitForm=function(){
			myResource.myDialog.swalConfirm('¿Confirma que todos los datos están correctos?',function(){
				myResource.sendData('registrosrescatevertical'+(($scope.toogleEditMode)?'/PUT':'')).save($scope.frmParent,function(json){
					myResource.myDialog.showNotify(json);
					if(json.estado===true) myResource.state.go('tthh.sos.vrescue.verticalrescue');
				},myResource.setError);
			});
	 };
	 
 }])
/*
 * RESCATE VERTICAL
 */
.controller('sos.verticalrecueCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={path:'tthh/sos/vrescue',entity:'Registro_rescatevertical',uri:'tthh/sos/vrescue/history/list',order:'-registro_codigo',toolbar:{}};
}])
/*
 * REGISTRO DE DAÑOS
 */
.controller('sos.damagereportCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'',tb:'',order:'-',toolbar:{}};
}])

/*
 * E. RIESGO PSICOSOCIAL - SISTEMA DE CALIFICACION
 */
.controller('sos.psychosocialratingsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/sos/psychosocial',parent:'Sistemacalificacion',tb:'sistemacalificacionpsicosocial',order:'recurso_nombre',toolbar:{}};
}])
/*
 * E. RIESGO PSICOSOCIAL - PREGUNTAS
 */
.controller('sos.psychosocialquestionsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/sos/psychosocial',parent:'Preguntas',tb:'preguntaspsicosocial',order:'recurso_nombre',toolbar:{}};
}])
/*
 * E. RIESGO PSICOSOCIAL - FORMULARIOS
 */
.controller('sos.psychosocialformsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/sos/psychosocial',parent:'Formulariosriesgopsicosocial',tb:'formulariosriesgopsicosocial',order:'formulario_nombre',toolbar:{}};
}])
/*
 * E. RIESGO PSICOSOCIAL - FORMULARIOS - SECCIONES
 */
.controller('sos.detailPsychosocialformsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// INFORMACION DE ENTIDAD
	$scope.dataEntity=entity.data;
	// MYTABLECTRL
	$scope.tbParams={
			path:'tthh/sos/psychosocial',
			parent:'Formulariosriesgopsicosocial_secciones',tb:'formulariosriesgopsicosocial_secciones',order:'seccion_index',
			toolbar:{},
			custom:{fk_tabla:'fk_formulario_id',fk_tabla_id:$scope.dataEntity.formulario_id}
	};
}])
/*
 * E. RIESGO PSICOSOCIAL - FORMULARIOS - SECCIONES - PREGUNTAS
 */
.controller('sos.detailPsychosocialformsSectionsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// INFORMACION DE ENTIDAD
	$scope.dataEntity=entity.data;
	// MYTABLECTRL
	$scope.tbParams={
			path:'tthh/sos/psychosocial',
			parent:'Formulariosriesgopsicosocial_preguntas',tb:'formulariosriesgopsicosocial_preguntas',order:'pregunta_index',
			toolbar:{},
			custom:{fk_tabla:'fk_seccion_id',fk_tabla_id:$scope.dataEntity.seccion_id}
	};
}])
/*
 * E. RIESGO PSICOSOCIAL - EVALUACIONES TOMADAS
 */
.controller('sos.psychosocialevaluationsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh/sos/psychosocial',parent:'Evaluacionesriesgopsicosocial',tb:'evaluacionesriesgopsicosocial',order:'-evaluacion_inicio',toolbar:{}};
}])
/*
 * E. RIESGO PSICOSOCIAL - FORMULARIOS - SECCIONES - PREGUNTAS
 */
.controller('sos.psychosocialDetailEvaluationsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// INFORMACION DE ENTIDAD
	$scope.dataEntity=entity.data;
	$scope.customTblCtrl={fk_tabla:'fk_evaluacion_id',fk_tabla_id:$scope.dataEntity.evaluacion_id};
}])
/*
 * VACACIONES DEL PERSONAL
 */
.controller('evaluations.questionsCtrl',['$scope','myResource',function($scope,myResource){
	// MYTABLECTRL
	$scope.tbParams={
			path:'tthh/sos/psychosocial',
			parent:'Evaluacionesriesgopsicosocial_preguntas',tb:'evaluacionesriesgopsicosocial_preguntas',order:'pregunta_index',
			toolbar:{},
			custom:$scope.customTblCtrl
	};
}])

/*
 * *******************************************
 * TALENTO HUMANO - CONCURSOS
 * *******************************************
 */
/*
 * PERSONAL POSTULANTE - ASPIRANTES 
 */
.controller('candidatesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'tthh',parent:'Aspirantes',tb:'aspirantes',order:'-aspirante_registro',toolbar:{showPrint:true,showNew:false}};
	// DESCARGAR LISTADO DE CERTIFICADOS
	$scope.donwloadCertificates=function(candidateId){
		window.open(rootRequest+$scope.tbParams.tb+'/DOWNLOAD?candidateId='+candidateId,'_blank');
	}
}])
/*
 * DETALLE DE PERSONAS
 */
.controller('detailCandidatesCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.tbParent='Personas';
	$scope.dataEntity=entity.data;
	$scope.paramsConf=entity.config;
	// SET PATH MODAL
	$scope.$parent.pathEntity='tthh';
	// PARÁMETROS PARA TTHH
	$scope.custom={fk_tabla:'fk_persona_id',fk_tabla_id:$scope.dataEntity.persona_id};
	// DESCARGAR LISTADO DE CERTIFICADOS
	$scope.donwloadCertificates=function(candidateId){
		myResource.getData('aspirantes/DOWNLOAD').json({candidateId:candidateId},function(json){
			myResource.myDialog.showNotify(json);
		},myResource.setError).$promise;
	}
}])







/*
 * *******************************************
 * SEGUNDA JEFATURA - GENERAL
 * *******************************************
 */
/*
 * CÓDIGOS INSTITUCIONALES
 */
.controller('institucionalcodesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'subjefature',parent:'Codigosinstitucionales',tb:'codigosinstitucionales',order:'codigo_clave',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * RESOURCES - CLASIFICACIÓN DE PARTES
 */
.controller('causesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'subjefature',parent:'Recursospartes',tb:'recursospartes',order:'recurso_nombre',toolbar:{}};
}])
.controller('natureCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'subjefature',parent:'Naturalezaincidente',tb:'naturalezaincidente',order:'naturaleza_clasificacion',toolbar:{}};
}])
/*
 * PARTES
 */
.controller('partsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'subjefature',parent:'Partes',tb:'partes',order:'parte_fecha',toolbar:{showPrint:true,showNew:true}};
}])
/*
 * DETALLE DE PARTES
 */
.controller('detailPartsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.entity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='subjefature';
	// PARÁMETROS PARA LISTADO DE ATENDIDOS
	$scope.custom={fk_tabla:'fk_parte_id',fk_tabla_id:$scope.entity.parte_id};
}])
/*
 * LISTADO DE ATENDIDOS DE UN PARTE
 */
.controller('caretedCtrl',['$scope','myResource',function($scope,myResource){
	// PARÁMETROS DE TABLAS
	$scope.tbParams={parent:'Atendidos',tb:'atendidos',order:'persona_apellidos',
			 toolbar:{},
			 custom:$scope.custom};
	// CUSTOM TOOLBAR FILTER - ATENDIDOS
	$scope.filterInTab={mdIcon:'group',label:'TOOLBAR_UTILS',search:true,showNew:true,showPrint:false};
}])
/*
 * LIBRO DE NOVEDADES
 */
.controller('binnacleCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'subjefature',parent:'Libronovedades',tb:'libronovedades',order:'-bitacora_fecha',toolbar:{showPrint:true,showNew:true}};
}])
/*
 * GUARDIAS
 */
.controller('guardsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'subjefature',parent:'Guardias',tb:'guardias',order:'-guardia_fecha',toolbar:{showPrint:true,showNew:true}};
}])
/*
 * FLOTAS
 */
.controller('trackingCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'subjefature',parent:'Flotasvehiculares',tb:'flotasvehiculares',order:'-flota_salida_hora',toolbar:{showPrint:true,showNew:true}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.filterBarCode={options:{debounce:500},code:'',type:'unit'};
	$scope.scanBarCode=function(){
		myResource.getData('flotasvehiculares/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.swalAlert(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal(json.data.modal,json.data.data);
			}
		},myResource.setError);
	};
}])
/*
 * DETALLE DE FLOTAS
 */
.controller('detailTrackingCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.entity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='subjefature';
	// RELOAD TRACKING
	$scope.reloadEntity=function(){
		myResource.sendData('flotasvehiculares/REQUEST').save({id:$scope.entity.flota_id},function(json){
			if(json.estado===true) $scope.entity=json.data;
		},function(error){myResource.state.go('subjefature.tracking');}).$promise;
	};
}])

/*
 * *******************************************
 * SUBJEFATURA - APH
 * *******************************************
 */
/*
 * ADMINISTRACIÓN DE INSUMOS
 */
.controller('aph.suppliesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'subjefature/aph',entity:'Insumosaph',uri:'subjefature/aph/supplies/list',order:'insumo_nombre',toolbar:{showNew:true}};
}])
/*
 * CONTROL DE INSUMOS
 */
.controller('aph.supplycontrolCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'subjefature/aph',entity:'Inventario_insumosaph',uri:'subjefature/aph/control/supplies',order:'-inventario_fecha_registro',toolbar:{showNew:true}};
}])
/*
 * REGISTRO DE INSUMOS EN INVENTARIO 
 */
.controller('aph.inventorysupplycontrolCtrl',['supplies','$scope','myResource',function(supplies,$scope,myResource){
		
	// LISTADO DE MEDICINAS
	$scope.sourcesModel=supplies.data;
	
	// RECARGAR LISTADO DE MEDICAMENTOS
	$scope.reloadList=function(){
		myResource.requestData('subjefature/aph/suppliesinventory/movements/list').save({inventoryId:$scope.sourcesModel.inventory.inventario_id},function(json){
			myResource.myDialog.showNotify(json);
			$scope.sourcesModel=json.data;
		});
	};
	
	// GUARDAR REGISTRO INDIVIDUAL
	$scope.saveItem=function(item){
		item.fk_inventario_id=$scope.sourcesModel.inventory.inventario_id;
		item.fk_personal_id=229;
		myResource.requestData('subjefature/aph/supplies/inventory/single').save(item,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true) $scope.reloadList();
		});
	};
	
	// ENVIAR FORMULARIO
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('¿Confirma que todos los datos están correctos?',function(){
			myResource.sendData('inventario_medicamentos').update($scope.medicinesList,function(json){
				myResource.myDialog.showNotify(json);
				// if(json.estado===true) myResource.state.go('tthh.medicines');
				if(json.estado===true) $scope.reloadList();
			},myResource.setError);
		});
	};
		
}])
/*
 * STOCK DE INSUMOS
 */
.controller('aph.suppliesstockCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'subjefature',entity:'Stock',uri:'subjefature/aph/supplies/all/stock/list',order:'insumo_nombre',toolbar:{}};
}])
/*
 * LISTADO DE ESTACIONES CON INSUMOS
 */
.controller('aph.suppliesstationsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'tthh',entity:'Bodegas',uri:'tthh/institution/wineries/list',order:'bodega_nombre',toolbar:{}};
}])
/*
 * STOCK DE INSUMOS EN ESTACIONES
 */
.controller('aph.suppliesstockstationsCtrl',['$scope','myResource',function($scope,myResource){
	// INFORMACIÓN DE ESTACIÓN
	$scope.stationId=myResource.stateParams.id;
	// myTableCtrl
	$scope.tbParams={path:'subjefature',entity:'Stock',uri:'subjefature/aph/supplies/stock/wineries/list',order:'insumo_nombre',toolbar:{},custom:{wineryId:$scope.stationId}};
}])
;