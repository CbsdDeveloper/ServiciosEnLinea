/**
 * 
 */
app

/* *****************************************************
 * ACTIVIDADES ECONOMICAS
 * *****************************************************
 */

/*
 * COVID 19
 */
.controller('biosecurity.covid19Ctrl',['resources','$scope','myResource',function(resources,$scope,myResource){
	// NOMBRE DE FORMULARIO
	$scope.toolbar='LB_REGISTRATION_FORM';
	
	// DATOS DE FORMULARIO
	$scope.frmSrc=resources.data.resources;
	$scope.frmParent=resources.data.model;
	
	// ENVIAR FORMULARIO DE AUTOINSPECCION
	$scope.submitForm=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.myDialog.swalConfirm('Por favor, revise detenidamente el formulario antes de realizar esta acción.',function(){
			// ENVIAR FORMULARIO
			myResource.sendData('bioseguridad_covid19').save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'covid19/?id='+json.data.id,'_blank');
					myResource.state.go('permits.economicActivities');
				}
			},myResource.setError);
		});
	};
	
}])

/*
 * AUTOINSPECCIÓN
 */
.controller('selfInspectionCtrl',['entity','data','$scope','myResource',function(entity,data,$scope,myResource){
	// DATOS DE ACTIVIDAD ECONOMICA
	$scope.entity=data.data;
	$scope.local=entity.data;
	// DATOS DE FORMULATIO
	$scope.frmLocales=entity.data;
	$scope.frmLocal=entity.data;
	$scope.toolbar='';
}])
/*
 * AUTOINSPECCIÓN - STEP 1
 */
.controller('selfInspection.step1Ctrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// NOMBRE DE TOOLABR
	$scope.$parent.toolbar='Paso 1. Verificar información';
	// DATOS DE FORMULARIOS
	$scope.frmLocal=entity.data;
	$scope.frmParent=entity.data;
	
	// ACTUALIZAR LISTADO DE ANEXOS
	$scope.$watch('$parent.$parent.updateResource',function(newValue,oldValue){
		// ACTUALIZAR LISTA DE ANEXOS
		if(myResource.testNull(newValue) && newValue==='Anexos'){
			myResource.requestData('permits/locals/localId').save({localId:$scope.frmParent.local_id},function(json){
				$scope.frmParent.local_certificadostablecimiento=json.data.local_certificadostablecimiento;
			}).$promise;
		}
		// ANULAR VARIABLE DE ACTUALIZACION
		$scope.$parent.$parent.updateResource='';
	},true);
	
	// ENVIAR FORMULARIO Y AVANZAR AL SIGUIENTE PASO
	$scope.goStep2=function(localId){
		// CONFIRMAR GUARDADO
		myResource.myDialog.swalConfirm('Estimado usuario, por favor confirme que todos sus datos son correctos antes de continuar.',function(){
			// INSERTAR ID DE ACTIVIDAD
			$scope.frmParent.fk_actividad_id=$scope.frmParent.ciiu.taxe.fk_actividad_id;
			// ACTUALIZAR DATOS DE ESTABLECIMIENTO
			myResource.sendData('locales/PUT').update($scope.frmLocal,function(json){
				// VALIDAR SI NO TIENE ESTABLECIMIENTO
				if(json.estado===true){
					// VALIDAR SI NO TIENE ESTABLECIMIENTO
					if($scope.frmLocal.local_establecimiento=='NO'){
						// AUTOINSPECCION SI ESTABLECIMIENTO
						myResource.sendData('emptyAI').update($scope.frmLocal,function(json){
							// PRESENTAR MENSAJE DE BACKEND
							myResource.myDialog.swalAlert(json);
							// VALIDAR CREACION DE AUTOINSPECCION
							if(json.estado===true){
								window.open(reportsURI+'autoinspecciones/?id='+json.id,'_blank');
								myResource.state.go('permits.economicActivities');
							}
						},myResource.setError);
					}else{
						// ENVIAR AL PASO 2
						myResource.state.go('permits.selfInspection.step2',{localId:localId});
						// PRESENTAR MENSAJE DE RETORNO
						myResource.myDialog.showNotify(json);
					}
				}else{
					// PRESENTAR MENSAJE DE RETORNO
					myResource.myDialog.showNotify(json);
				}
			},myResource.setError);
		});
	};
	
}])
/*
 * AUTOINSPECCIÓN - STEP 2
 */
.controller('selfInspection.step2Ctrl',['entity','geo','$scope','myResource',function(entity,geo,$scope,myResource){
	
	// DATOS DE FORMULARIOS
	$scope.frmParent=entity.data;
	$scope.activityData=entity.data.activity;
	$scope.pathRemoteTemplate='/app/src/views/selfinspection/';
	
	// COORDENADAS POR DEFECTO
	$scope.frmParent.coordenada_marks=[];
	
	// MARCADORES DE MAPA
	$scope.frmParent.coordenada_marks=angular.merge($scope.frmParent.coordenada_marks,geo.data);
	
	// VALIDAR COORDENADAS
	if(myResource.testNull($scope.frmParent.coordinates)){
		$scope.frmParent.coordenada_latitud=$scope.frmParent.coordinates.coordenada_latitud;
		$scope.frmParent.coordenada_longitud=$scope.frmParent.coordinates.coordenada_longitud;
		$scope.frmParent.coordenada_zoom=$scope.frmParent.coordinates.coordenada_zoom;
	}else{		
		$scope.frmParent.coordenada_latitud=-0.2531997068768405;
		$scope.frmParent.coordenada_longitud=-79.16464805603027;
		$scope.frmParent.coordenada_zoom=13;
	}
	
	// CALCULOS DE FORMULARIO
	$scope.getArea=function(){
		$scope.frmParent.local_area_construccion=parseFloat($scope.frmParent.local_area_subsuelos)+parseFloat($scope.frmParent.local_area_planta_baja);
	};
	// CALCULO DE PISOS
	$scope.getFloor=function(){
		if($scope.frmParent.local_subsuelos==0){
			$scope.frmParent.local_area_subsuelos=0;
			$scope.getArea();
		}
		$scope.frmParent.local_plantas=parseFloat($scope.frmParent.local_pisos)+parseFloat($scope.frmParent.local_subsuelos);
	};
	// CALCULO DE AFORO
	$scope.getAforo=function(){
		$scope.frmParent.local_aforo_planta=(parseInt($scope.frmParent.local_aforo_mujeres)+parseInt($scope.frmParent.local_aforo_hombres))-parseInt($scope.frmParent.local_aforo_campo);
		$scope.frmParent.local_aforo=parseInt($scope.frmParent.local_aforo_simultaneo)+parseInt($scope.frmParent.local_aforo_planta);
	};
	// AUTOCALCULAR
	$scope.getAforo();
	
}])
/*
 * AUTOINSPECCIÓN - STEP 3
 */
.controller('selfInspection.step3Ctrl',['form','inspectionForm','$scope','myResource',function(form,inspectionForm,$scope,myResource){
	// VARIABLES DE AUTOINSPECCION
	$scope.formulario=form.data;
	$scope.instructions=form.data.instructions;
	$scope.locked=form.data.locked;
	$scope.declaracion=form.data.selfInspection;
	$scope.declaracion.form={local:form.data.local.local_id,rule:form.data.rule};
	$scope.$parent.toolbar='Paso 3. Completar AutoInspección';
	myResource.myDialog.swalAlert({estado:'info',mensaje:form.data.config.AI_INFO_LAST_STEP});
	// IMPRIMIR AUTOINSPECCION
	$scope.printForm=function(){
		window.open(reportsURI+'formularios/?withDetail&id='+$scope.formulario.rule.formulario_id+'&local='+$scope.formulario.local.local_id,'_blank');
	};
	// ENVIAR FORMULARIO DE AUTOINSPECCION
	$scope.submitForm=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.myDialog.swalConfirm('Por favor,revise detenidamente el formulario antes de realizar esta acción.',function(){
			// ENVIAR FORMULARIO
			myResource.sendData('selfInspections').save($scope.declaracion,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'autoinspecciones/?id='+json.data.id,'_blank');
					myResource.state.go('permits.economicActivities');
				}
			},myResource.setError);
		});
	};
	
	
	
	// CARD INFORMACTION
	$scope.dataEntity=inspectionForm.data;
	
	
	
}])


/* *****************************************************
 * PLANES DE AUTOPROTECCION
 * *****************************************************
 */

/*
 * REGISTRAR NUEVO PLAN
 */
.controller('selfProtectionCtrl',['local','$scope','myResource',function(local,$scope,myResource){
	// DECLARACION DE MODELOS
	// $scope.entity=angular.merge(local.data,$scope.entity);
	$scope.entity=angular.merge(local.data,myResource.getTempData('paramsSession'));
	// DECLARACION DE FORMULARIO
	$scope.frmParent=local.data;
	$scope.frmParent.fk_local_id=myResource.stateParams.localId;
	// TOOLBAR
	$scope.toolbar='';
}])
/*
 * REGISTRAR NUEVO PLAN - STEP1
 */
.controller('selfProtection.step1Ctrl',['geo','$scope','myResource','findEntityService',function(geo,$scope,myResource,findEntityService){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_PROPERTY_INFORMATION';
	
	// COORDENADAS POR DEFECTO
	$scope.frmParent.coordenada_marks=[];
	
	// MARCADORES DE MAPA
	$scope.frmParent.coordenada_marks=angular.merge($scope.frmParent.coordenada_marks,geo.data);
	
	// *** BUSCAR DATOS DE PERSONAS
	$scope.searchPersonInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findPerson({identityCard:data},$scope,frm,index);
	};
	
	// VALIDAR COORDENADAS
	if(myResource.testNull($scope.frmParent.coordinates)){
		$scope.frmParent.coordenada_latitud=$scope.frmParent.coordinates.coordenada_latitud;
		$scope.frmParent.coordenada_longitud=$scope.frmParent.coordinates.coordenada_longitud;
		$scope.frmParent.coordenada_zoom=$scope.frmParent.coordinates.coordenada_zoom;
	}else{		
		$scope.frmParent.coordenada_latitud=-0.2531997068768405;
		$scope.frmParent.coordenada_longitud=-79.16464805603027;
		$scope.frmParent.coordenada_zoom=13;
	}
	
	// CALCULO DE AFORO
	$scope.getAforo=function(){
		$scope.frmParent.local_aforo_planta=(parseInt($scope.frmParent.local_aforo_mujeres)+parseInt($scope.frmParent.local_aforo_hombres))-parseInt($scope.frmParent.local_aforo_campo);
		$scope.frmParent.local_aforo=parseInt($scope.frmParent.local_aforo_simultaneo)+parseInt($scope.frmParent.local_aforo_planta);
	};
	
	// AUTOCALCULAR
	$scope.getAforo();
	
}])
/*
 * REGISTRAR NUEVO PLAN - STEP2
 */
.controller('selfProtection.step2Ctrl',['plans','$scope','myResource',function(plans,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='TOOLBAR_PLANS';
	// INFORMACION DE PLANES
	$scope.plansList=plans.data;
	// EXPORTAR PLAN
	$scope.exportPlan=function(entityId){window.open(reportsURI+'planautoproteccion/?id='+entityId,'_blank');};
}])
/*
 * REGISTRAR NUEVO PLAN
 */
.controller('editSelfProtectionCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// DECLARACION DE FORMULARIO
	$scope.frmParent=entity.data;
	$scope.entity=myResource.getTempData('paramsSession');
	// TOOLBAR
	$scope.toolbar='';
	
	// CARGAR ARCHIVOS ADJUNTOS
	$scope.uploadAnnexes=function(entityName){
		$scope.frmParent.annexeEntity=entityName;
		myResource.uploadFile.uploadFile(rootRequest+'planesautoproteccion_anexos',$scope,'frmParent').then(function(json){ myResource.myDialog.swalAlert(json.data); },myResource.setError);
	};
	
}])
/*
 * REGISTRAR NUEVO PLAN - STEP10
 */
.controller('selfProtection.stepSingleCtrl',['$scope','myResource','findEntityService',function($scope,myResource,findEntityService){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP_SINGLE';
	
	// DATOS DE SOS
	if(myResource.testNull($scope.frmParent.billing) && $scope.frmParent.billing!=null){
		$scope.frmParent.plan_facturacion=($scope.frmParent.local.fk_entidad_id === $scope.frmParent.billing.entidad_id) ? 'MISMA' : 'OTRA';
	}else{
		$scope.frmParent.plan_facturacion='MISMA';
	}
	// *** BUSCAR DATOS DE RUC
	$scope.searchRucInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findEntity({ruc:data},$scope,frm,index);
	};
	// *** BUSCAR ENTIDADES POR URL
	$scope.searchEntityByURI=function(url,data,frm,index,index2){
		if(myResource.testNull(data)) findEntityService.requestEntity(url,data,$scope,frm,index,index2);
	};
	// *** BUSCAR ENTIDADES POR URL
	$scope.findSOS=function(data){
		// CONSULTAR EN BACKEND
		myResource.requestData('resources/academicTraining/identificationPerson').save(data,function(json){
			// PRESENTAR MENSAJE DE BACKEND
			if(json.estado===false) myResource.myDialog.showNotify(json);
			else{
				// INGRESAR DATOS DE PROFESIONAL
				$scope.frmParent.sos=angular.merge($scope.frmParent.sos,json.data);
				// VALIDAR DATOS DE SENESCYT
				if(myResource.testNull(json.data.training)) $scope.frmParent.training=json.data.training[0];
			}
		}).$promise;
	};
	
	// GUARDAR CAMBIOS
	$scope.submitFormSingle=function(){
		// CONFIRMACIÓN DE ENVIO DE FORMULARIO A BACKEND
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			// ENVIAR FORMULARIO A BACKEND
			myResource.requestData('prevention/plans/selfproteccion/relations').update($scope.frmParent,function(json){
				// VALIDAR ESTADO DE BACKEND
				if(json.estado===true){
					// ENVIAR AL PASO SIGUIENTE
					myResource.state.go('permits.editSelfProtection.stepSingleAnnexxes',{planId:$scope.frmParent.plan_id});
					// PRESENTAR MENSAJE DE BACKEND
				} else myResource.myDialog.swalAlert(json);
				// MENSAJE DE ERROR
			},myResource.setError);
		});
	};
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		// CONFIRMACIÓN DE ENVIO DE FORMULARIO A BACKEND
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			// ENVIAR FORMULARIO A BACKEND
			myResource.requestData('prevention/plans/selfproteccion/relations').update($scope.frmParent,function(json){
				// VALIDAR ESTADO DE BACKEND
				if(json.estado===true){
					
					// ENVIAR A IMPRIMIR SOLICITUD
					window.open($scope.setting.uri.reports+'planesemergencia/?withDetail&id='+$scope.frmParent.plan_id,'_blank');
					
					// ENVIAR AL PASO SIGUIENTE
					myResource.state.go('permits.selfProtectionPlans');
					
					// PRESENTAR MENSAJE DE BACKEND
				} else myResource.myDialog.swalAlert(json);
				// MENSAJE DE ERROR
			},myResource.setError);
		});
	};
	
	// ACTUALIZAR LISTADO DE EMPLEADOS
	$scope.getAnnexes=function(){
		// CONSULTA A BACKEND
		myResource.requestData('prevention/plans/selfProtectionAnnexesByPlan').save({planId:$scope.frmParent.plan_id},function(json){
			// ACTUALIZAR LISTA DE ANEXOS
			$scope.annexesList=json.data;
		}).$promise;
	};	
	
	// ACTUALIZAR LISTADO DE ANEXOS
	$scope.reloadEntity=function(){
		// ACTUALIZAR DATOS DE PLAN
		myResource.requestData('prevention/plans/planById').save(myResource.stateParams,function(json){ $scope.frmParent=json.data; }).$promise;
	};
	
	// ACTUALIZAR LISTADO DE ANEXOS
	$scope.$watch('$parent.$parent.updateResource',function(newValue,oldValue){
		// ACTUALIZAR LISTA DE ANEXOS
		if(myResource.testNull(newValue) && newValue==='Anexos') $scope.reloadEntity();
		if(myResource.testNull(newValue) && newValue==='Autoproteccion_anexos') $scope.getAnnexes();
		// ANULAR VARIABLE DE ACTUALIZACION
		$scope.$parent.$parent.updateResource='';
	},true);
	
	// ACTUALIZAR ANEXOS
	$scope.getAnnexes();
	
}])
/*
 * REGISTRAR NUEVO PLAN - STEP3
 */
.controller('selfProtection.step3Ctrl',['entity','resources','$scope','myResource',function(entity,resources,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP3';
	
	// MODELO DE DATOS
	$scope.frmParent=entity.data;
	$scope.frmParent.resources=resources.data.resources;
	$scope.frmParent.model=resources.data.model;
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			myResource.sendData('autoproteccion_recursos').save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) myResource.state.go('permits.editSelfProtection.step4',{localId:json.data.fk_local_id,planId:json.data.plan_id});
			},myResource.setError);
		});
	};
}])
/*
 * REGISTRAR NUEVO PLAN - STEP4
 */
.controller('selfProtection.step4Ctrl',['entity','resources','$scope','myResource',function(entity,resources,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP4';
	
	// MODELO DE DATOS
	$scope.frmParent=entity.data;
	$scope.frmParent.resources=resources.data.resources;
	$scope.frmParent.model=resources.data.model;
	$scope.frmParent.temp={};
	// CALCULO DEL TOTAL
	$scope.frmParent.total={
		'FACTORES PROPIOS DE LAS INSTALACIONES (X)':$scope.frmParent.meseri_valor_x,
		'FACTORES DE PROTECCION (Y)':$scope.frmParent.meseri_valor_y
	};
	
	// CALCULAR VALORES PREVIOS
	if(Object.keys(resources.data.model).length<1){
		$scope.frmParent.model={
			'FACTORES PROPIOS DE LAS INSTALACIONES (X)':[],
			'FACTORES DE PROTECCION (Y)':[]
		};
	}
	
	// CALCULO DE TOTALES
	$scope.getTotal=function(type){
		// SUMATORIA DE FACTORES MESERI
		$scope.frmParent.total[type]=0;
		// SUMA DE RECURSOS DE FACTORES
		angular.forEach($scope.frmParent.model[type], function(value, key){
			// CREAR MODELO DE DATOS SI NO EXISTE
			if(!myResource.testNull($scope.frmParent.temp[type])) $scope.frmParent.temp[type]={};
			// ASIGNAR VALORES A MODELO
			$scope.frmParent.temp[type][key]=value;
			// SUMAR VALORES
			$scope.frmParent.total[type]+=parseFloat(value.meseri_coeficiente.val);
		});
		// IGUALAR CALCULOS
		$scope.frmParent.meseri_valor_x=$scope.frmParent.total['FACTORES PROPIOS DE LAS INSTALACIONES (X)'];
		$scope.frmParent.meseri_valor_y=$scope.frmParent.total['FACTORES DE PROTECCION (Y)'];
		// SUMATORIA DE MODELOS
		pVal=parseFloat(parseFloat(5/129)*parseFloat($scope.frmParent.meseri_valor_x))+parseFloat(parseFloat(5/30)*parseFloat($scope.frmParent.meseri_valor_y));
		$scope.frmParent.meseri_valor_p=pVal.toFixed(2);
	};
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			// ENVIAR FORMULARIO
			myResource.sendData('autoproteccion_meseri').save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) myResource.state.go('permits.editSelfProtection.step5',{localId:json.data.fk_local_id,planId:json.data.plan_id});
			},myResource.setError);
		});
	};
	
	// ACTUALIZAR VALORES
	$scope.getTotal('FACTORES PROPIOS DE LAS INSTALACIONES (X)');
	$scope.getTotal('FACTORES DE PROTECCION (Y)');
}])
/*
 * REGISTRAR NUEVO PLAN - STEP5
 */
.controller('selfProtection.step5Ctrl',['entity','resources','$scope','myResource',function(entity,resources,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP5';
	
	// MODELO DE DATOS
	$scope.frmParent=entity.data;
	$scope.frmParent.resources=resources.data.resources;
	$scope.frmParent.model=resources.data.model;
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			myResource.sendData('autoproteccion_prevencion').save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) myResource.state.go('permits.editSelfProtection.step6',{localId:json.data.fk_local_id,planId:json.data.plan_id});
			},myResource.setError);
		});
	};
}])
/*
 * REGISTRAR NUEVO PLAN - STEP6
 */
 .controller('selfProtection.step6Ctrl',['entity','resources','$scope','myResource','findEntityService','$http','$q',function(entity,resources,$scope,myResource,findEntityService,$http,$q){
		// NOMBRE DE TOOLBAR
		$scope.$parent.toolbar='LB_SELFPROTECTION_STEP6';
		
		// MODELO DE DATOS
		$scope.frmParent=entity.data;
		$scope.frmParent.resources=resources.data.resources;
		$scope.frmParent.model=resources.data.model;

		// *** BUSCAR DATOS DE RUC
		$scope.searchRucInformation=function(data,idx){
			if(myResource.testNull(data)){
				myResource.requestData('permits/entities/enitiyByRUC').save({ruc:data},function(json){
					$scope.frmParent.model[idx].professional=json.data;
					$scope.frmParent.model[idx].mantenimiento_responsable_id=json.data.entidad_id;
				}).$promise;
			}
		};
		
		// GUARDAR CAMBIOS
		$scope.submitForm=function(){
			// MODAL DE CONFIRMACIÓN
			myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
				// ENVIAR FORMULARIO DE MANTENIMIENTOS
				myResource.sendData('autoproteccion_mantenimiento').save($scope.frmParent,function(json){
					// VALIDAR SI SE HAN GUARDADO LOS DATOS
					if(json.estado===true){
						/*
						// RECCORRER EL LISTADO PARA SUBIR ANEXOS
						var deferred=$q.defer();
						var formData;
						// SUBIR LISTADO DE ANEXOS
						angular.forEach($scope.frmParent.model,function(value,key){
							// VAERIFICAR SI SE HA ENVIADO EL ANEXO
							if( typeof value.media_file !== 'undefined' ){
								// GENERAR MODELO TEMPORAL
								formData=new FormData();
								formData.append('media_file',value.media_file);
								formData.append('recurso_id',value.fk_recurso_id);
								// RETORNAR CONSULTA
								return $http.post(rootRequest+'autoproteccion_mantenimiento_files/?planId='+$scope.frmParent.plan_id,formData,{
									transformRequest:angular.identity,
									headers:{'Content-Type':undefined}
								})
								.success(function(res){ deferred.resolve(res); })
								.error(function(msg,code){ deferred.reject(msg); });
							}
						});
						*/
					}
					// PRESENTAR MENSAJE DE BACKEND
					myResource.myDialog.swalAlert(json);
					// REDIRECCIONAR A PASO 7
					myResource.state.go('permits.editSelfProtection.step7',{localId:json.data.fk_local_id,planId:json.data.plan_id});
				},myResource.setError);
				
			});
		};
	}])
/*
 * REGISTRAR NUEVO PLAN - STEP7
 */
.controller('selfProtection.step7Ctrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP7';
	
	// MODELO DE DATOS
	$scope.frmParent=angular.merge($scope.frmParent,entity.data);
	
	// MODULO DE EDICION
	$scope.editorLoaded=function(jsonEditor){jsonEditor.expandAll();};
	$scope.options={mode:'tree'};
	

	// ACTUALIZAR PLAN - STEP 7.2
	$scope.updateStep7_1=function(){ $scope.updateStep7_21(); };
	// ACTUALIZAR PLAN - STEP 7.2
	$scope.updateStep7_21=function(){
		// VALIDAR SI SE ESCOGE LOS DATOS DE CBSD
		if($scope.frmParent.alarma_aplicacion_tipo=='PROPUESTA POR EL CUERPO DE BOMBEROS'){
			if($scope.frmParent.alarma_deteccion=='DETECCIÓN HUMANA'){
				$scope.frmParent.alarma_aplicacion=$scope.paramsConf.SELFPROTECTION_72_APLICACION_ALARMA_HUMANA;
			}else{
				$scope.frmParent.alarma_aplicacion=$scope.paramsConf.SELFPROTECTION_72_APLICACION_ALARMA_MIXTA;
			}
		}else{
			$scope.frmParent.alarma_aplicacion='Detalle aquí...';
		}
	};
	// ACTUALIZAR PLAN - STEP 7.3
	$scope.updateStep7_31=function(){
		// VALIDAR SI SE ESCOGE LOS DATOS DE CBSD
		if($scope.frmParent.alarma_grado_i=='GRADOS DE EMERGENCIA PROPUESTOS POR EL CUERPO DE BOMBEROS'){
			$scope.frmParent.alarma_grado_ii=$scope.paramsConf.SELFPROTECTION_73_NEVEL_EMERGENCY_PROPUESTO;
		}else{
			$scope.frmParent.alarma_grado_ii='Detalle aquí...';
		}
	};
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			myResource.requestData('prevention/plans/selfproteccion').update($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) myResource.state.go('permits.editSelfProtection.step8',{localId:json.data.fk_local_id,planId:json.data.plan_id});
			},myResource.setError);
		});
	};
}])
/*
 * REGISTRAR NUEVO PLAN - STEP8
 */
.controller('selfProtection.step8Ctrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP8';
	
	// MODELO DE DATOS
	$scope.frmParent=angular.merge($scope.frmParent,entity.data);
	
	// MODULO DE EDICION
	$scope.editorLoaded=function(jsonEditor){jsonEditor.expandAll();};
	$scope.options={mode:'tree'};
	
	// ACTUALIZAR LISTADO DE EMPLEADOS
	$scope.getEmployees=function(){
		myResource.requestData('permits/employees/localId').save({localId:entity.data.fk_local_id},function(json){
			$scope.employesList=json.data;
		}).$promise;
	};	$scope.getEmployees();
	
	// ACTUALIZAR LISTADO DE EMPLEADOS
	$scope.getBrigades=function(){
		myResource.requestData('prevention/brigades/localId').save({localId:entity.data.fk_local_id},function(json){
			$scope.brigadesList=json.data;
		}).$promise;
	};	$scope.getBrigades();
	
	// ELIMINAR LISTADO DE EMPLEADOS
	$scope.deleteEmployees=function(){
		myResource.myDialog.swalConfirm('Confirma que desea eliminar el listado de empleados? Esta acción eliminará la asignación de brigadistas y responsables de brigadas.',function(){
			myResource.requestData('permits/employees/localId/delete/:localId').delete({localId:entity.data.fk_local_id},function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					$scope.getEmployees();
					$scope.getBrigades();
				}
			},myResource.setError);
		});
	};

	// ELIMINAR EMPLEADO
	$scope.deleteEmployee=function(employeeId){
		myResource.myDialog.swalConfirm('Confirma que desea eliminar este registro?<br>Esta acción eliminará la asignación de brigadistas y responsables de brigadas.',function(){
			myResource.requestData('permits/employees/:employeeId').delete({employeeId:employeeId},function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					$scope.getEmployees();
					$scope.getBrigades();
				}
			},myResource.setError);
		});
	};
	
	// ELIMINAR BRIGADA 
	$scope.deleteBrigade=function(brigadeId){
		myResource.myDialog.swalConfirm('Confirma que desea eliminar este registro?<br>Esta acción eliminará la asignación de brigadistas y responsables de brigadas.',function(){
			myResource.requestData('prevention/brigades/:brigadeId').delete({brigadeId:brigadeId},function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) $scope.getBrigades();
			},myResource.setError);
		});
	};
	
	// ACTUALIZACIÓN DE LISTADO
	$scope.$watch('$parent.$parent.updateResource',function(newValue,oldValue){
		// ACTUALIZAR LISTA DE ANEXOS
		if(myResource.testNull(newValue) && (newValue==='EmpleadosLista' || newValue==='Empleados')) $scope.getEmployees();
		if(myResource.testNull(newValue) && (newValue==='Brigadas' || newValue==='Brigadistas')) $scope.getBrigades();
		// ANULAR VARIABLE DE ACTUALIZACION
		$scope.$parent.$parent.updateResource='';
	},true);
	
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			myResource.requestData('prevention/plans/selfproteccion').update($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) myResource.state.go('permits.editSelfProtection.step9',{localId:json.data.fk_local_id,planId:json.data.plan_id});
			},myResource.setError);
		});
	};
}])
/*
 * REGISTRAR NUEVO PLAN - STEP9
 */
.controller('selfProtection.step9Ctrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP9';
	
	// MODELO DE DATOS
	$scope.frmParent=angular.merge($scope.frmParent,entity.data);
	
	// MODULO DE EDICION
	$scope.editorLoaded=function(jsonEditor){jsonEditor.expandAll();};
	$scope.options={mode:'tree'};
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			myResource.requestData('prevention/plans/selfproteccion').update($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true) myResource.state.go('permits.editSelfProtection.step10',{localId:json.data.fk_local_id,planId:json.data.plan_id});
			},myResource.setError);
		});
	};
	
	// ACTUALIZACION DE HISTORIAL
	myResource.requestData('permits/permits/list/localId').save({localId:$scope.frmParent.fk_local_id},function(json){ $scope.historyList=json.data; }).$promise;
	myResource.requestData('prevention/training/history/entityId').save({entityId:$scope.frmParent.local.fk_entidad_id},function(json){ $scope.historyTrainingList=json.data; }).$promise;
	
}])
/*
 * REGISTRAR NUEVO PLAN - STEP10
 */
.controller('selfProtection.step10Ctrl',['$scope','myResource','findEntityService',function($scope,myResource,findEntityService){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP10';
	
	// DATOS DE SOS
	if(myResource.testNull($scope.frmParent.billing) && $scope.frmParent.billing!=null){
		$scope.frmParent.plan_facturacion=($scope.frmParent.local.fk_entidad_id === $scope.frmParent.billing.entidad_id) ? 'MISMA' : 'OTRA';
	}else{
		$scope.frmParent.plan_facturacion='MISMA';
	}
	// *** BUSCAR DATOS DE RUC
	$scope.searchRucInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findEntity({ruc:data},$scope,frm,index);
	};
	// *** BUSCAR ENTIDADES POR URL
	$scope.searchEntityByURI=function(url,data,frm,index,index2){
		if(myResource.testNull(data)) findEntityService.requestEntity(url,data,$scope,frm,index,index2);
	};
	
	// *** BUSCAR ENTIDADES POR URL
	$scope.findSOS=function(data){
		// CONSULTAR EN BACKEND
		myResource.requestData('resources/academicTraining/identificationPerson').save(data,function(json){
			// PRESENTAR MENSAJE DE BACKEND
			if(json.estado===false) myResource.myDialog.showNotify(json);
			else{
				// INGRESAR DATOS DE PROFESIONAL
				$scope.frmParent.sos=angular.merge($scope.frmParent.sos,json.data);
				// VALIDAR DATOS DE SENESCYT
				if(myResource.testNull(json.data.training)) $scope.frmParent.training=json.data.training[0];
			}
		}).$promise;
	};
	
	// GUARDAR CAMBIOS
	$scope.submitForm=function(){
		// CONFIRMACIÓN DE ENVIO DE FORMULARIO A BACKEND
		myResource.myDialog.swalConfirm('Confirma que los datos ingresados están correctos?',function(){
			// ENVIAR FORMULARIO A BACKEND
			myResource.requestData('prevention/plans/selfproteccion/relations').update($scope.frmParent,function(json){
				// VALIDAR ESTADO DE BACKEND
				if(json.estado===true){
					// VALIDAR CARGA DE ANEXO
					if( typeof $scope.frmParent.formacion_file !== 'undefined' ) $scope.uploadAnnexes('formacionacademica');
					// ENVIAR AL PASO SIGUIENTE
					myResource.state.go('permits.editSelfProtection.step11',{localId:json.data.fk_local_id,planId:json.data.plan_id});
					// PRESENTAR MENSAJE DE BACKEND
				} else myResource.myDialog.swalAlert(json);
				// MENSAJE DE ERROR
			},myResource.setError);
		});
	};
	
}])
/*
 * REGISTRAR NUEVO PLAN - STEP11
 */
.controller('selfProtection.step11Ctrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// NOMBRE DE TOOLBAR
	$scope.$parent.toolbar='LB_SELFPROTECTION_STEP11';
	
	// ACTUALIZAR LISTADO DE EMPLEADOS
	$scope.getAnnexes=function(){
		// CONSULTA A BACKEND
		myResource.requestData('prevention/plans/selfProtectionAnnexesByPlan').save({planId:$scope.frmParent.plan_id},function(json){
			// ACTUALIZAR LISTA DE ANEXOS
			$scope.annexesList=json.data;
		}).$promise;
	};	
	
	// ACTUALIZAR LISTADO DE EMPLEADOS
	$scope.getAnnexesMaintenance=function(){
		// CONSULTA A BACKEND
		myResource.requestData('prevention/plans/findSelfProtectionMaintenanceApplyByPlan').save({planId:$scope.frmParent.plan_id},function(json){
			// ACTUALIZAR LISTA DE ANEXOS
			$scope.annexesMaintenanceList=json.data;
		}).$promise;
	};
	
	// ACTUALIZAR LISTADO DE ANEXOS
	$scope.reloadEntity=function(){
		// ACTUALIZAR DATOS DE PLAN
		myResource.requestData('prevention/plans/planById').save(myResource.stateParams,function(json){ $scope.frmParent=json.data; }).$promise;
		// ACTUALIZAR ANEXOS DE MANTENIMIENTO
		$scope.getAnnexesMaintenance();
	};
	
	// ACTUALIZAR LISTADO DE ANEXOS
	$scope.$watch('$parent.$parent.updateResource',function(newValue,oldValue){
		// ACTUALIZAR LISTA DE ANEXOS
		if(myResource.testNull(newValue) && newValue==='Anexos') $scope.reloadEntity();
		if(myResource.testNull(newValue) && newValue==='Autoproteccion_anexos') $scope.getAnnexes();
		// ANULAR VARIABLE DE ACTUALIZACION
		$scope.$parent.$parent.updateResource='';
	},true);
	
	// ELIMINAR ANEXO
	$scope.deleteAnnexes=function(id){
		// CONFIRMAR PARA ELIMINAR ANEXO
		myResource.myDialog.swalConfirm('Está seguro que desea eliminar este registro?',function(){
			// ENVIAR CONSULTA AL BACKEND
			myResource.sendData('autoproteccion_anexos/DELETE').save({annexeId:id},function(json){
				// VALIDAR RESULTADO DE BACKEND
				if(json.estado===true) $scope.getAnnexes();
				// PRESENTAR RESPUESTA DE BACKEND
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
	
	// ACTUALIZAR ANEXOS
	$scope.getAnnexes();
	// ACTUALIZAR ANEXOS DE MANTENIMIENTO
	$scope.getAnnexesMaintenance();
	
}])
/*
 * REGISTRAR NUEVO PLAN - STEP11 - ANEXOS
 */
.controller('selfProtectionAnexesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// CARGAR ARCHIVOS ADJUNTOS
	$scope.uploadAnnexes=function(){
		// INSERTAR NOMBRE DE ARCHIVO A ACTUALIZAR
		$scope.frmParent.annexeEntity=$scope.frmParent.fileNameUpload;
		// ENVIAR DATOS A BACKEND
		myResource.uploadFile.uploadFile(rootRequest+$scope.frmParent.tbEntity,$scope,'frmParent').then(function(json){
			// PRESENTAR MENSAJE DE BACKEND
			myResource.myDialog.swalAlert(json.data);
			// CERRAR VENTANA
			myResource.myDialog.closeDialog();
		},myResource.setError);
	};
}])




/* *****************************************************
 * PERMISOS DE TRANSPORTE DE COMBUSTIBLE
 * *****************************************************
 */





/* *****************************************************
 * PERMISOS OCASIONALES
 * *****************************************************
 */










/* *****************************************************
 * APROBACIÓN DE PLANOS
 * *****************************************************
 */







/* *****************************************************
 * FACTIBILIDAD DE GLP
 * *****************************************************
 */


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
					myResource.state.go('main.extensions');
				}
			},myResource.setError);
		});
	};
}])










/* *****************************************************
 * CAPACITACIONES CIUDADANAS
 * *****************************************************
 */

/*
 * CALENDARIO DE CAPACITACIONES CIUDADANAS
 */
.controller('training.calendarCtrl',['calendar','$scope','myResource',function(calendar,$scope,myResource){
	// DIAS FESTIVOS
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
    
	// URL DE REGISTRO DE ENTIDADES
	var urlDetailEntity={
		training:'training.newTraining',
		stands:'training.newStand',
		visitas:'training.newVisit',
		simulacros:'training.newSimulation'
	};
	var tempData={};
	
    // EVENTO ON CLICK
    $scope.dayClick=function(date) {
    	// FECHA PARA CONSULTAR EL REGISTRO
    	date=myResource.filter("date")(date,"y-MM-dd");
    	// DATOS A CONSULTAR
    	var tempData={
			entity:myResource.stateParams.entity,
			date:date
    	};
    	// CONSULTAR DISPONIBILIDAD EN BACKEND
    	myResource.sendData(tempData.entity+'/REQUEST').save(tempData,function(json){
    		// VALIDAR RESPUESTA DE BACKEND
    		if(json.estado===true) myResource.state.go(urlDetailEntity[tempData.entity],tempData);
    		// PRESENTAR MENSAJE DE BACKEND
    		myResource.myDialog.showNotify(json);
    	}).$promise;
    	
    };
}])
/*
 * REGISTROO PARA NUEVA CAPACITACION
 */
.controller('newTrainingCtrl',['entity','$scope','myResource','findEntityService','leafletData',function(entity,$scope,myResource,findEntityService,leafletData){
	
	// FECHAS DISPONIBLES
	$scope.dataEntity=entity.data.date;
	$scope.topics=entity.data.topics;
	// VARIABLES
	$scope.statusLabel=statusLabel;
	// OBJETO DE RELACIÓN
	$scope.frmParent={
		topic:{},
		date:{},
		coordinator:{},
		training:{}
	};
	// ESTADOS DE DATOS
	$scope.toggle={
		topic:false,
		date:false
	};
	// SELECCIONAR HORARIO
	$scope.setDate=function(date){
		$scope.frmParent.date=date;
		$scope.toggle.date=true;
	};
	// SELECCIONAR TEMA
	$scope.setTopic=function(topic){
		$scope.frmParent.topic=topic;
		$scope.toggle.topic=true;
	};
	// CANCELAR PROCESO
	$scope.exitEntity=function(){
		myResource.myDialog.swalConfirm('Realmente desea realizar esta acción?',function(){
			myResource.state.go('main.trainings');
		});
	};
	// GUARDAR PROCESO
	$scope.saveEntity=function(){
		// CONFIRMAR ENVIO DE FORMULARIO
		myResource.myDialog.swalConfirm('Realmente desea realizar esta acción?',function(){
			// COORDENADAS DEL MAPA
			$scope.frmParent.coordenada_latitud=$scope.myOSM.lat;
			$scope.frmParent.coordenada_longitud=$scope.myOSM.lng;
			$scope.frmParent.coordenada_zoom=$scope.myOSM.zoom;
			// CONVERTIR MAPA A IMG
			html2canvas(document.querySelector("#myOSM"),{
				useCORS: true,
				logging: true
			}).then(canvas => {
			    // Export the canvas to its data URI representation
			    $scope.frmParent.coordenada_img=canvas.toDataURL("image/png");
				// ENVIAR FORMULARIO
				myResource.sendData('training').save($scope.frmParent,function(json){
					if(json.estado===true) myResource.state.go('training.trainings');
					myResource.myDialog.swalAlert(json);
				},myResource.setError);
			});
		});
	};
	// BUSCAR PERSONA
	$scope.searchPersonInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findPerson({identityCard:data},$scope,frm,index);
	};
	
	// REGISTRO DE COORDENADAS
	$scope.frmParent.coordenada_marks=[];
	$scope.frmParent.coordenada_latitud=-0.2531997068768405;
	$scope.frmParent.coordenada_longitud=-79.16464805603027;
	$scope.frmParent.coordenada_zoom=13;
	
	// OPTIONS - LEAFLET
	$scope.drawnItems=new L.FeatureGroup();
	// INSERTAR LAYERS
	$scope.$watch('frmParent.coordenada_marks',function() {
		// INSERTAR CONJUNTO DE LAYERS AL MAPA
		for(var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
			L.geoJson($scope.frmParent.coordenada_marks[i].geoJSON,{
				onEachFeature: function (feature, layer){
					$scope.drawnItems.addLayer(layer);
				}
			});
		}
	});
	// CREAR CONTROL PARA LAYER
	$scope.drawControl=new L.Control.Draw({
        edit: {
            featureGroup: $scope.drawnItems
        },
        draw: {
        	marker: true,
            polyline: {
            	shapeOptions: {
            		color: 'red'
            	}
            },
            polygon: {
            	shapeOptions: {
            		color: 'purple'
            	}
            },
            circle: {
                shapeOptions: {
                    stroke: true,
                    weight: 4,
                    color: 'blue',
                    opacity: 0.5,
                    fill: true,
                    fillColor: null,
                    fillOpacity: 0.2,
                    clickable: true
                }
            },
            rectangle: {
                shapeOptions: {
                    clickable: false,
                    color: 'green'
                }
            }
        },
        showRadius: true
    });
	// MAPA - CENTRAR EN SANTO DOMINGO
	angular.extend($scope, {
		myOSM: {
	        lat: parseFloat($scope.frmParent.coordenada_latitud),
	        lng: parseFloat($scope.frmParent.coordenada_longitud),
	        zoom: parseInt($scope.frmParent.coordenada_zoom)
	    },
        defaults: {
            tileLayer: "http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png",
            tileLayerOptions: {
                opacity: 0.9,
                detectRetina: true,
                reuseTiles: true,
            },
            scrollWheelZoom: false
        },
	    controls: { 
	    	scale: true,
            fullscreen: {
                position: 'topleft'
            },
            custom: [$scope.drawControl]
        },
        markers: {},
        layers: {
        	baselayers: {
        		osm: {
        			name: 'OpenStreetMap',
        			url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        			type: 'xyz'
        		},
        		googleTerrain: {
        			name: 'Google Terrain',
        			layerType: 'TERRAIN',
        			type: 'google'
        		},
        		googleHybrid: {
        			name: 'Google Hybrid',
        			layerType: 'HYBRID',
        			type: 'google'
        		}
        	},
            overlays: {
                draw: {
                    name: 'draw',
                    type: 'group',
                    visible: true,
                    layerParams: {
                        showOnSelector: true
                    }
                }
            }            
        }
	});
	// CAMBIOS EN EL MAPA
	leafletData.getMap('myOSM').then(function(map){
		// DIBUJAR LAYERS
		map.addLayer($scope.drawnItems);
		// Init the map with the saved elements
        var printLayers=function (){
            console.log("After: ");
            map.eachLayer(function(layer){
                console.log(layer);
            });             
        };	printLayers();
		// CREAR LAYER
		map.on('draw:created', function (e){
			var type=e.layerType,
            layer=e.layer;
    		if (type === 'marker') layer.options.draggable=true;
    		else if(type === 'polyline' || type === 'polygon') {}
    		else layer.editing.enable();
    		$scope.drawnItems.addLayer(layer);
            $scope.frmParent.coordenada_marks.push({
                id: layer._leaflet_id,
                geoJSON: layer.toGeoJSON()
            });
		});
		// EDITAR LAYER
		map.on('draw:edited', function(e){
            var layers=e.layers;
            layers.eachLayer(function(layer){
                for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks[i].geoJSON=layer.toGeoJSON();
                    }
                }
            });
        });
		// ELIMINAR LAYER
        map.on('draw:deleted', function(e){
            var layers=e.layers;
            layers.eachLayer(function(layer){
            	for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks.splice(i,1);
                    }
                }
            });
        });
	});
}])
/*
 * FORMULARIO DE NUEVA CASA ABIERTA
 */
.controller('newStandCtrl',['entity','$scope','myResource','findEntityService',function(entity,$scope,myResource,findEntityService){
	// OBJETO DE RELACIÓN
	$scope.frmParent={};
	// FECHAS DISPONIBLES
	$scope.dataEntity=entity.data.date;
	// VARIABLES
	$scope.statusLabel=statusLabel;
	// CANCELAR PROCESO
	$scope.exitEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.state.go('training.stands');
		});
	};
	// GUARDAR PROCESO
	$scope.saveEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.sendData('stands').save($scope.frmParent,function(json){
				if(json.estado===true) myResource.state.go('training.stands');
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
	// BUSCAR PERSONA
	$scope.searchPersonInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findPerson({identityCard:data},$scope,frm,index);
	};
}])
/*
 * FORMULARIO DE NUEVA VISITA
 */
.controller('newVisitCtrl',['entity','$scope','myResource','findEntityService',function(entity,$scope,myResource,findEntityService){
	// OBJETO DE RELACIÓN
	$scope.frmParent={};
	// FECHAS DISPONIBLES
	$scope.dataEntity=entity.data.date;
	// VARIABLES
	$scope.statusLabel=statusLabel;
	// CANCELAR PROCESO
	$scope.exitTraining=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.state.go('training.visits');
		});
	};
	// GUARDAR PROCESO
	$scope.saveTraining=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.sendData('visitas').save($scope.frmParent,function(json){
				if(json.estado===true) myResource.state.go('training.visits');
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
	// BUSCAR PERSONA
	$scope.searchPersonInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findPerson({identityCard:data},$scope,frm,index);
	};
}])
/*
 * FORMULARIO DE NUEVO SIMULACRO
 */
.controller('newSimulationCtrl',['entity','$scope','myResource','findEntityService',function(entity,$scope,myResource,findEntityService){
	// OBJETO DE RELACIÓN
	$scope.frmParent={};
	// FECHAS DISPONIBLES
	$scope.dataEntity=entity.data.date;
	// VARIABLES
	$scope.statusLabel=statusLabel;
	// CANCELAR PROCESO
	$scope.exitEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.state.go('training.simulations');
		});
	};
	// GUARDAR PROCESO
	$scope.saveEntity=function(){
		myResource.myDialog.swalConfirm('Realmente deseas realizar esta acción?',function(){
			myResource.sendData('simulacros').save($scope.frmParent,function(json){
				if(json.estado===true) myResource.state.go('training.simulations');
				myResource.myDialog.swalAlert(json);
			},myResource.setError);
		});
	};
	// BUSCAR PERSONA
	$scope.searchPersonInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findPerson({identityCard:data},$scope,frm,index);
	};
}])












;