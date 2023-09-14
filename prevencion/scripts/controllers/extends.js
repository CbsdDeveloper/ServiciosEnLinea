/**
 * 
 */
app

/* *****************************************************
 * ACTIVIDADES ECONOMICAS
 * *****************************************************
 */

/* 
 * Modal: modalEntidades
 * Función: 
 */
.controller('profileExtendsCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('permits/entities/detail/entityId').save({entityId:$scope.frmParent.entityId},function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	}).$promise;
}])
/* 
 * Modal: modalLocales
 * Función: 
 */
.controller('localsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm = 'frmParent';
	console.log('Extends request -> localsExtendsCtrl -> Form to reference:: '+ frm);
	
	// LISTADO DE ACTIVIDADES ECONÓMICAS
	$scope.activitiesList={};
	myResource.requestData('permits/commercialActivities').json(function(json){
		$scope.activitiesList=json.data;
		$scope.getFKs('');
	}).$promise;
	
	// LISTADO DE CIIU
	var fk={name:'fk_ciiu',id:"ciiu_id",list:'FKList'};
	$scope.changeFK=function(index){$scope.frmParent[index]=$scope.frmParent[fk.name][fk.id];};
	$scope.getFKs=function(str){
		if($scope.frmParent.ciiu.taxe.fk_actividad_id>0){
			myResource.requestSelect('permits/ciiu',{filter:str,fkParent:$scope.frmParent.ciiu.taxe.fk_actividad_id},$scope,fk.list);
		}
	};
}])
.controller('ciiuExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm='frmLocales';
	console.log('Extends request -> ciiuExtendsCtrl -> Form to reference:: ',frm);
	$scope.frmParent=$scope.$parent[frm];
	// *** LISTADO DE ACTIVDADES CIIU PARA LOCALES COMERCIALES
	var fk={name:'fk_ciiu',id:"ciiu_id",list:'FKList'};
	$scope.changeFK=function(index){$scope[frm][index]=$scope[frm][fk.name][fk.id];};
	$scope.getFKs=function(str){myResource.fillSelect('getCiiu',str,$scope,fk.list);};
}])
.controller('agspExtendsCtrl',['$scope',function($scope){
	$scope.getArea=function(){
		$scope.frmLocal.local_area_construccion=parseFloat($scope.frmLocal.local_area_subsuelos)+parseFloat($scope.frmLocal.local_area_planta_baja);
		$scope.frmLocal.local_area=$scope.frmLocal.local_area_planta_baja;
	}
}])
.controller('balnExtendsCtrl',['$scope',function($scope){
	$scope.getArea=function(){
		$scope.frmLocal.local_area_construccion=parseFloat($scope.frmLocal.local_area_otros)+parseFloat($scope.frmLocal.local_area_subsuelos)+parseFloat($scope.frmLocal.local_area_planta_baja);
		$scope.frmLocal.local_area=parseFloat($scope.frmLocal.local_area_planta_baja)+parseFloat($scope.frmLocal.local_area_otros);
	}
}])
.controller('cglpExtendsCtrl',['$scope',function($scope){
	$scope.frmLocal.local_plantas=1;
}])
.controller('gassExtendsCtrl',['$scope',function($scope){
	$scope.frmLocal.local_area_planta_baja=0;
	$scope.getArea=function(){
		$scope.frmLocal.local_area=parseFloat($scope.frmLocal.local_area_otros)+parseFloat($scope.frmLocal.local_area_subsuelos)+parseFloat($scope.frmLocal.local_area_planta_baja);
	}
}])
/*
 * MODAL PARA NUEVA PLAN
 */
.controller('alertNewSelfprotectionsPlansCtrl',['$scope','myResource',function($scope,myResource){
	// BOTON PARA CONFIRMAR NUEVO PLAN DE AUTOPROTECCIÓN
	$scope.newSelfprotectionPlan=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.state.go('permits.selfProtection.step1',{localId:$scope.frmParent.local_id});
		// CERRAR MODAL
		myResource.myDialog.closeDialog();
	};
}])





/* *****************************************************
 * PLANES DE AUTOPROTECCION
 * *****************************************************
 */

/*
 * Modal: modalPlanes
 * Función: PARSE INPUT DATE
 */
.controller('planExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm = 'frmParent';
	console.log('Extends request -> planExtendsCtrl -> Form to reference:: '+ frm);
	// CONSULTAR LOS DATOS DEL LOCAL
	$scope.frmParent.local={};
	myResource.sendData('planesemergencia/REQUEST').save({localId:$scope.frmParent.local_id},function(json){
		$scope.frmParent.local=json.data;
	});
	// GUARDAR REGISTRO E IMPRIMIR FORMULARIO
	$scope.submitFormPE=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.myDialog.swalConfirm('Por favor,revise detenidamente el formulario antes de realizar esta acción.',function(){
			// DEFINIR EL METODO DE ENVIO
			var method='';
			// CAMBIAR METODO DE ENVIO
			if($scope.frmParent.plan_id>0) method=($scope.toogleEditMode)?'/PUT':'';
			else delete($scope.frmParent.plan_id);
			// ENVIAR FORMULARIO
			myResource.sendData('planesemergencia'+method).save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'planesemergencia/?withDetail&id='+json.data.id,'_blank');
					myResource.state.go('permits.selfProtectionPlans');
					myResource.myDialog.closeDialog();
				}
			},myResource.setError);
		});
	};
}])
/* 
 * Modal: 
 * Función: 
 */
.controller('geoExtendsCtrl',['$scope','myResource','leafletData',function($scope,myResource,leafletData){
	
	// OPTIONS - LEAFLET
	$scope.drawnItems = new L.FeatureGroup();
	// INSERTAR LAYERS
	$scope.$watch('frmParent.coordenada_marks',function(){
		console.log($scope.frmParent.coordenada_marks);
		// INSERTAR CONJUNTO DE LAYERS AL MAPA
		for(var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
			L.geoJson($scope.frmParent.coordenada_marks[i].geoJSON,{
				onEachFeature: function (feature,layer){ $scope.drawnItems.addLayer(layer); }
			});
		}
	});
	// CREAR CONTROL PARA LAYER
	$scope.drawControl = new L.Control.Draw({
        edit:{
            featureGroup:$scope.drawnItems
        },
        draw:{
        	marker: true,
        	polyline: false,
            polygon:false,
            rectangle:false,
            circle:false/*{
                shapeOptions:{
                    stroke: true,
                    weight: 4,
                    color: 'blue',
                    opacity: 0.5,
                    fill: true,
                    fillColor: null,
                    fillOpacity: 0.2,
                    clickable: true
                }
            }*/
        },
        showRadius: true
    });
	// MAPA - CENTRAR EN SANTO DOMINGO
	angular.extend($scope, {
		myOSM:{
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
	    controls:{ 
	    	scale: true,
            fullscreen:{
                position: 'topleft'
            },
            custom: [$scope.drawControl]
        },
        markers:{},
        layers:{
        	baselayers:{
        		osm:{
        			name: 'OpenStreetMap',
        			url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        			type: 'xyz'
        		},
        		googleTerrain:{
        			name: 'Google Terrain',
        			layerType: 'TERRAIN',
        			type: 'google'
        		},
        		googleHybrid:{
        			name: 'Google Hybrid',
        			layerType: 'HYBRID',
        			type: 'google'
        		}
        	},
            overlays:{
                draw:{
                    name: 'draw',
                    type: 'group',
                    visible: true,
                    layerParams:{
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
        var printLayers = function (){
            console.log("After: ");
            map.eachLayer(function(layer){
                console.log(layer);
            });             
        };	printLayers();
		// CREAR LAYER
		map.on('draw:created', function (e){
			var type = e.layerType,
            layer = e.layer;
    		if (type === 'marker') layer.options.draggable = true;
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
            var layers = e.layers;
            layers.eachLayer(function(layer){
                for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks[i].geoJSON = layer.toGeoJSON();
                    }
                }
            });
        });
		// ELIMINAR LAYER
        map.on('draw:deleted', function(e){
            var layers = e.layers;
            layers.eachLayer(function(layer){
            	for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks.splice(i,1);
                    }
                }
            });
        });
	});
	
	// REINICIAR COORDENADAS DE MAPA
	$scope.resetCoordinates=function(){
		$scope.myOSM.lat=-0.2531997068768405;
		$scope.myOSM.lng=-79.16464805603027;
		$scope.myOSM.zoom=13;
	};
	
	// GUARDAR FORMULARIO DE ACTIVIDAD ECONOMICA
	$scope.goStep2=function(){
		// ACTUALIZAR CAMPOS DE LOCAL
		myResource.requestData('permits/locals').update($scope.frmParent,function(json){
			// LANZAR MENSAJE DE RETORNO
			myResource.myDialog.swalAlert(json);
			// VALIDAR ACTUALIZACION CORRECTA
			if(json.estado===true){
				// COORDENADAS DEL MAPA
				$scope.frmParent.coordenada_latitud=$scope.myOSM.lat;
				$scope.frmParent.coordenada_longitud=$scope.myOSM.lng;
				$scope.frmParent.coordenada_zoom=$scope.myOSM.zoom;
				$scope.frmParent.local_latitud=$scope.myOSM.lat;
				$scope.frmParent.local_longitud=$scope.myOSM.lng;
				// CONVERTIR MAPA A IMG
				html2canvas(document.querySelector("#myOSM"),{
					useCORS: true,
					logging: true
				}).then(canvas => {
				    // Export the canvas to its data URI representation
				    $scope.frmParent.coordenada_img=canvas.toDataURL("image/png");
				   // ENVIO DE FORMULARIO A BACKEND
					myResource.sendData('planesautoproteccion').update($scope.frmParent,function(json){
						if(json.estado===true) myResource.state.go('permits.selfProtection.step2',{localId:$scope.frmParent.local_id});
						myResource.myDialog.swalAlert(json);
					 },myResource.setError);
				});
			}
		},myResource.setError);
	};
	
	
	// ENVIAR FORMULARIO PARA OBTENER FORMULARIO DE AUTOINSPECCION
	$scope.getForm=function(){
		// INSERTAR ID DE ACTIVIDAD
		$scope.frmParent.fk_actividad_id=$scope.frmParent.ciiu.taxe.fk_actividad_id;
		// COORDENADAS DEL MAPA
		$scope.frmParent.coordenada_latitud=$scope.myOSM.lat;
		$scope.frmParent.coordenada_longitud=$scope.myOSM.lng;
		$scope.frmParent.coordenada_zoom=$scope.myOSM.zoom;
		$scope.frmParent.local_latitud=$scope.myOSM.lat;
		$scope.frmParent.local_longitud=$scope.myOSM.lng;
		// ACTUALIZAR CAMPOS DE LOCAL
		myResource.requestData('permits/locals').update($scope.frmParent,function(json){
			// LANZAR MENSAJE DE RETORNO
			myResource.myDialog.swalAlert(json);
			// VALIDAR ACTUALIZACION CORRECTA
			if(json.estado===true){
				// CONVERTIR MAPA A IMG
				html2canvas(document.querySelector("#myOSM"),{
					useCORS: true,
					logging: true
				}).then(canvas => {
				    // Export the canvas to its data URI representation
				    $scope.frmParent.coordenada_img=canvas.toDataURL("image/png");
				   // ENVIO DE FORMULARIO A BACKEND
					myResource.sendData('locales/PUT').update($scope.frmParent,function(json){
						// REDIRECCIONAR A SIGUIENTE PASO DE LA AUTOINSPECCION
						if(json.estado===true) myResource.state.go('permits.selfInspection.step3',{id:$scope.frmParent.local_id});
						// PRESENTAR MENSAJE DE BACKEND
						myResource.myDialog.swalAlert(json);
					 },myResource.setError);
				});
			}
		},myResource.setError);
	};
	
}])
/*
 * Modal: modalParticipantesLista
 * Función: PARA CARGAR LOS DATOS DE PARTICIPANTES DE UNA CAPACITACIÓN 
 */
.controller('participantsLisExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> participantsLisExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A2';
}])
/*
 * Modal: modalBrigadistas
 * Función: LISTADO DE EMPLEADOS PARA BRIGADAS  
 */
.controller('brigadistsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	
	console.log('Extends request -> brigadistsExtendsCtrl -> Form to reference:: ',$scope.frm);
	
	$scope.filterData="";
	$scope.employeesList=[];
	
	myResource.requestData('prevention/brigadists/brigadeId').save($scope.frmParent,function(json){
		$scope.employeesList=json.data.employeesList;
		$scope.frmParent.selected=json.data.brigadistsList;
	}).$promise;
	
}])





/* *****************************************************
 * PERMISOS DE TRANSPORTE DE COMBUSTIBLE
 * *****************************************************
 */

/* 
 * Modal: modalTransporteglp
 * Función: parsear datos de representante legal como nombre de propietario de vehículo
 */
.controller('glpTransportExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> glpTransportExtendsCtrl -> Form to reference:: ',frm);
	// GUARDAR REGISTRO E IMPRIMIR FORMULARIO
	$scope.submitFormTGLP=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.myDialog.swalConfirm('Por favor,revise detenidamente el formulario antes de realizar esta acción.',function(){
			// DEFINIR EL METODO DE ENVIO
			var method='';
			// CAMBIAR METODO DE ENVIO
			if($scope.frmParent.transporte_id>0) method='/PUT';
			else delete ($scope.frmParent.transporte_id);
			// ENVIAR FORMULARIO
			myResource.sendData('transporteglp'+method).save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'transporteglp/?withDetail&id='+json.data.id,'_blank');
					myResource.myDialog.closeDialog();
					myResource.state.go('permits.glpTransport');
				}
			},myResource.setError);
		});
	};
}])
/* 
 * Modal: modalUnidades - modalVehículos - modalTGLP
 * Función: LISTADO DE TIPOS DE VEHÍCULOS
 * TIPO: TIPOS DE VEHÍCULOS
 */
.controller('transportTypeListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('resources/REQUEST').save({type:'transportType'},function(json){$scope.jsonList=json.data;}).$promise;
}])
/* 
 * Modal: modalVehiculos - modalTransporteglp
 * Función: LISTADO DE MARCAS 
 */
.controller('brandsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('resources/REQUEST').save({type:'brands'},function(json){$scope.jsonList=json.data;}).$promise;
}])








/* *****************************************************
 * PERMISOS OCASIONALES
 * *****************************************************
 */

/* 
 * Modal: modalOcasionales
 * Función: PARSE CAMPOS TIPO FECHAS, INICIAR LA VARIABLES
 */
.controller('occasionalExtendsCtrl',['$scope','myResource','leafletData',function($scope,myResource,leafletData){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> occasionalExtendsCtrl -> Form to reference:: ',frm);
	
	// LABEL PARA OCASIONAL
	$scope.frmParent.label='LB_COORDINATOR';
	$scope.frmParent.coordenada_marks=[];
	
	// EDICIÓN DE FECHAS
	if(!$scope.toogleEditMode){
		$scope.frmParent.ocasional_lugar='VIA PUBLICA';
		$scope.frmParent.coordenada_latitud=-0.2531997068768405;
		$scope.frmParent.coordenada_longitud=-79.16464805603027;
		$scope.frmParent.coordenada_zoom=13;
	}else{
		// LISTADO DE RECURSOS
		myResource.sendData('ocasionales/REQUEST').save({geoJSON:$scope.frmParent.ocasional_id},function(json){
			$scope.frmParent.coordenada_marks=json.data;
		}).$promise;
	}
	
	// OPTIONS - LEAFLET
	$scope.drawnItems = new L.FeatureGroup();
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
	$scope.drawControl = new L.Control.Draw({
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
        var printLayers = function (){
            console.log("After: ");
            map.eachLayer(function(layer){
                console.log(layer);
            });             
        };	printLayers();
		// CREAR LAYER
		map.on('draw:created', function (e){
			var type = e.layerType,
            layer = e.layer;
    		if (type === 'marker') layer.options.draggable = true;
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
            var layers = e.layers;
            layers.eachLayer(function(layer){
                for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks[i].geoJSON = layer.toGeoJSON();
                    }
                }
            });
        });
		// ELIMINAR LAYER
        map.on('draw:deleted', function(e){
            var layers = e.layers;
            layers.eachLayer(function(layer){
            	for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks.splice(i,1);
                    }
                }
            });
        });
	});
	
	// SUBMIT FORMULARIO
	$scope.submitCustomForm=function(){
		// DATOS DE BACKEND
		var tb='ocasionales';
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
			// ENVIO DE FORMULARIO A BACKEND
			if($scope.toogleEditMode){
				myResource.sendData(tb+'/PUT').update($scope.frmParent,function(json){
					if(json.estado===true)myResource.myDialog.closeDialog();
					myResource.myDialog.showNotify(json);
				 },myResource.setError);
			} else {
				myResource.sendData(tb).save($scope.frmParent,function(json){
					if(json.estado===true)myResource.myDialog.closeDialog();
					myResource.myDialog.showNotify(json);
				},myResource.setError);
			}
		});
	};
}])
/* 
 * Modal: modalResponsables
 * Función: 
 */
.controller('responsibleExtendsCtrl',['$scope','findEntityService',function($scope,findEntityService){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> responsibleExtendsCtrl -> Form to reference:: ',frm);
	// LISTADO DE RECURSOS
	findEntityService.myResource.sendData('ocasionales/REQUEST').save({agents:true,occasionalId:$scope.frmParent.ocasional_id},function(json){
		$scope.frmParent.model=json.data.agents;
	}).$promise;
	// BUSCAR PERSONA
	$scope.searchPersonInformation=function(data,frm,model,index){
		if(findEntityService.myResource.testNull(data)) findEntityService.findPerson({identityCard:data},$scope,frm,model,index);
	};
}])
/* 
 * Modal: modalRecursos
 * Función: 
 */
.controller('resourcesExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> resourcesExtendsCtrl -> Form to reference:: ',frm);
	// LISTADO DE RECURSOS
	myResource.sendData('ocasionales/REQUEST').save({type:['amenazas','internos','externos'],occasionalId:$scope.frmParent.ocasional_id},function(json){
		$scope.frmParent.model=json.data;
	}).$promise;
}])


/* 
 * Modal: modalAmenazas
 * Función: 
 */
.controller('threatsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> threatsExtendsCtrl -> Form to reference:: ',frm);
	// LISTADO DE RECURSOS
	myResource.sendData('ocasionales/REQUEST').save({type:['amenazas'],occasionalId:$scope.frmParent.ocasional_id},function(json){
		$scope.frmParent.model=json.data;
	}).$promise;
}])
/* 
 * Modal: modalInernos
 * Función: 
 */
.controller('internalExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> internalExtendsCtrl -> Form to reference:: ',frm);
	// LISTADO DE RECURSOS
	myResource.sendData('ocasionales/REQUEST').save({type:['internos'],occasionalId:$scope.frmParent.ocasional_id},function(json){
		$scope.frmParent.model=json.data;
	}).$promise;
}])
/* 
 * Modal: modalExternos
 * Función: 
 */
.controller('externalExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> externalExtendsCtrl -> Form to reference:: ',frm);
	// LISTADO DE RECURSOS
	myResource.sendData('ocasionales/REQUEST').save({type:['externos'],occasionalId:$scope.frmParent.ocasional_id},function(json){
		$scope.frmParent.model=json.data;
	}).$promise;
}])








/* *****************************************************
 * APROBACIÓN DE PLANOS
 * *****************************************************
 */
/* 
  * Modal: modalproyectos
  * Función: 
  */
.controller('vbpExtendsCtrl',['$scope','myResource','leafletData',function($scope,myResource,leafletData){
	// DATOS DE FACTURACIÓN
	$scope.frmParent.billing={};
	// LISTADO DE ACTIVIDADES ECONÓMICAS
	$scope.activitiesList={};
	myResource.requestData('permits/commercialActivities').json(function(json){$scope.activitiesList=json.data;}).$promise;
	// BUSCAR DATOS DE RUC
	if($scope.toogleEditMode && $scope.frmParent.vbp_facturacion=='OTRA'){
		// REALIZAR CONSULTA SI RUC NO ESTÁ VACIO
		if(myResource.testNull($scope.frmParent.facturacion_ruc)){
			myResource.requestData('permits/entities/enitiyByRUC').save({ruc:$scope.frmParent.facturacion_ruc},function(json){$scope.frmParent.billing=json.data;});
		}
	}
	// BUSCA EN SRI
	$scope.searchSRI=function(ruc){
		result = myResource.setResource('https://declaraciones.sri.gob.ec/sri-catastro-sujeto-servicio-internet/rest/ConsolidadoContribuyente/obtenerPorNumerosRuc').query({ruc:ruc});
	};
	
}])
 /* 
  * Modal: modalResponsables
  * Función: 
  */
 .controller('professionalVbpExtendsCtrl',['$scope','findEntityService',function($scope,findEntityService){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> responsibleExtendsCtrl -> Form to reference:: ',frm);
 	// LISTADO DE RECURSOS
 	findEntityService.myResource.sendData('vbp/REQUEST').save({profesionals:true,projectId:$scope.frmParent.vbp_id},function(json){
 		$scope.frmParent.model=json.data.professionals;
 		angular.forEach($scope.frmParent.model,function(val,key){
 			$scope.frmParent.model[key].formacion_fregistro=new Date($scope.frmParent.model[key].formacion_fregistro);
 		});
 	}).$promise;
}])
/* 
 * Modal: modalHabitabilidad
 * Función: 
 */
.controller('habitabilityExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> habitabilityExtendsCtrl -> Form to reference:: ',frm);
	// DATOS DE FACTURACIÓN
	$scope.frmParent.billing={};
	// LISTADO DE ACTIVIDADES ECONÓMICAS
	$scope.activitiesList={};
	myResource.requestData('permits/commercialActivities').json(function(json){$scope.activitiesList=json.data;}).$promise;
	// GUARDAR REGISTRO E IMPRIMIR FORMULARIO
	$scope.submitCustomForm=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.myDialog.swalConfirm('Por favor,revise detenidamente el formulario antes de realizar esta acción.',function(){
			// ENVIAR FORMULARIO
			var action=($scope.toogleEditMode)?'/PUT':'/POST';
			myResource.sendData('habitabilidad'+action).save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'habitabilidad/?withDetail&id='+json.data.id,'_blank');
					myResource.state.go('main.habitability');
					myResource.myDialog.closeDialog();
				}
			},myResource.setError);
		});
	};
}])
/* 
 * Modal: modalModificaciones
 * Función: 
 */
.controller('modificationsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> modificationsExtendsCtrl -> Form to reference:: ',frm);
	// GUARDAR REGISTRO E IMPRIMIR FORMULARIO
	$scope.submitCustomForm=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.myDialog.swalConfirm('Por favor,revise detenidamente el formulario antes de realizar esta acción.',function(){
			// DEFINIR EL METODO DE ENVIO
			var method='';
			// CAMBIAR METODO DE ENVIO
			if($scope.frmParent.vbp_id>0) method=($scope.toogleEditMode)?'/PUT':'';
			else delete($scope.frmParent.vbp_id);
			// ENVIAR FORMULARIO
			myResource.sendData('modificaciones'+method).save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'modificaciones/?withDetail&id='+json.data.id,'_blank');
					myResource.state.go('main.modifications');
					myResource.myDialog.closeDialog();
				}
			},myResource.setError);
		});
	};
}])


/* 
 * Modal: modalProyecto_modificaciones
 * Función: 
 */
.controller('vbpModificationsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	
	// DATOS DE FACTURACIÓN
	$scope.frmParent.billing={};
	// LISTADO DE ACTIVIDADES ECONÓMICAS
	$scope.activitiesList={};
	myResource.requestData('permits/commercialActivities').json(function(json){$scope.activitiesList=json.data;}).$promise;
	// BUSCAR DATOS DE RUC
	if($scope.toogleEditMode && $scope.frmParent.vbp_facturacion=='OTRA'){
		// REALIZAR CONSULTA SI RUC NO ESTÁ VACIO
		if(myResource.testNull($scope.frmParent.facturacion_ruc)){
			myResource.requestData('permits/entities').save({ruc:$scope.frmParent.facturacion_ruc},function(json){$scope.frmParent.billing=json.data;});
		}
	}
	
}])
/* 
 * Modal: modalProyecto_modificaciones
 * Función: 
 */
.controller('geoVbpExtendsCtrl',['$scope','myResource','leafletData',function($scope,myResource,leafletData){
	
	$scope.frmParent.coordenada_marks=[];
	
	// EDICIÓN DE FECHAS
	if(!$scope.toogleEditMode){
		$scope.frmParent.coordenada_latitud=-0.2531997068768405;
		$scope.frmParent.coordenada_longitud=-79.16464805603027;
		$scope.frmParent.coordenada_zoom=13;
	}else{
		// LISTADO DE RECURSOS
		myResource.sendData('geoJSON/REQUEST').save({entity:'vbp',entityId:$scope.frmParent.vbp_id},function(json){
			$scope.frmParent.coordenada_marks=json.data;
		}).$promise;
	}
	
	// OPTIONS - LEAFLET
	$scope.drawnItems = new L.FeatureGroup();
	// INSERTAR LAYERS
	$scope.$watch('frmParent.coordenada_marks',function(){
		// INSERTAR CONJUNTO DE LAYERS AL MAPA
		for(var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
			L.geoJson($scope.frmParent.coordenada_marks[i].geoJSON,{
				onEachFeature: function (feature,layer){ $scope.drawnItems.addLayer(layer); }
			});
		}
	});
	// CREAR CONTROL PARA LAYER
	$scope.drawControl = new L.Control.Draw({
        edit:{
            featureGroup:$scope.drawnItems
        },
        draw:{
        	marker: true,
            polyline:{
            	shapeOptions:{
            		color: 'red'
            	}
            },
            polygon:{
            	shapeOptions:{
            		color: 'purple'
            	}
            },
            circle:{
                shapeOptions:{
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
            rectangle:{
                shapeOptions:{
                    clickable: false,
                    color: 'green'
                }
            }
        },
        showRadius: true
    });
	// MAPA - CENTRAR EN SANTO DOMINGO
	angular.extend($scope, {
		myOSM:{
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
	    controls:{ 
	    	scale: true,
            fullscreen:{
                position: 'topleft'
            },
            custom: [$scope.drawControl]
        },
        markers:{},
        layers:{
        	baselayers:{
        		osm:{
        			name: 'OpenStreetMap',
        			url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        			type: 'xyz'
        		},
        		googleTerrain:{
        			name: 'Google Terrain',
        			layerType: 'TERRAIN',
        			type: 'google'
        		},
        		googleHybrid:{
        			name: 'Google Hybrid',
        			layerType: 'HYBRID',
        			type: 'google'
        		}
        	},
            overlays:{
                draw:{
                    name: 'draw',
                    type: 'group',
                    visible: true,
                    layerParams:{
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
        var printLayers = function (){
            console.log("After: ");
            map.eachLayer(function(layer){
                console.log(layer);
            });             
        };	printLayers();
		// CREAR LAYER
		map.on('draw:created', function (e){
			var type = e.layerType,
            layer = e.layer;
    		if (type === 'marker') layer.options.draggable = true;
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
            var layers = e.layers;
            layers.eachLayer(function(layer){
                for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks[i].geoJSON = layer.toGeoJSON();
                    }
                }
            });
        });
		// ELIMINAR LAYER
        map.on('draw:deleted', function(e){
            var layers = e.layers;
            layers.eachLayer(function(layer){
            	for (var i=0;i<$scope.frmParent.coordenada_marks.length;i++){
                    if ($scope.frmParent.coordenada_marks[i].id == layer._leaflet_id){
                        $scope.frmParent.coordenada_marks.splice(i,1);
                    }
                }
            });
        });
	});
	
	// SUBMIT FORMULARIO
	$scope.submitCustomForm=function(){
		// COORDENADAS DEL MAPA
		$scope.frmParent.coordenada_latitud=$scope.myOSM.lat;
		$scope.frmParent.coordenada_longitud=$scope.myOSM.lng;
		$scope.frmParent.coordenada_zoom=$scope.myOSM.zoom;
		$scope.frmParent.vbp_latitud=$scope.myOSM.lat;
		$scope.frmParent.vbp_longitud=$scope.myOSM.lng;
		// CONVERTIR MAPA A IMG
		html2canvas(document.querySelector("#myOSM"),{
			useCORS: true,
			logging: true
		}).then(canvas => {
		    // Export the canvas to its data URI representation
		    $scope.frmParent.coordenada_img=canvas.toDataURL("image/png");
			// ENVIO DE FORMULARIO A BACKEND
			if($scope.toogleEditMode){
				myResource.sendData($scope.tb+'/PUT').update($scope.frmParent,function(json){
					if(json.estado===true)myResource.myDialog.closeDialog();
					myResource.myDialog.showNotify(json);
				 },myResource.setError);
			} else {
				myResource.sendData($scope.tb).save($scope.frmParent,function(json){
					if(json.estado===true)myResource.myDialog.closeDialog();
					myResource.myDialog.showNotify(json);
				},myResource.setError);
			}
		});
	};
	
}])






 /* *****************************************************
  * FACTIBILIDAD DE GLP
  * *****************************************************
  */

 /* 
  * Modal: modalFactibilidadglp
  * Función: 
  */
.controller('feasibilityExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// DATOS DE FACTURACIÓN
	$scope.frmParent.billing={};
	$scope.frmParent.tanksList=[];
	$scope.frmParent.professionalsList=[];
	// BUSCAR DATOS DE RUC
	if($scope.toogleEditMode){
		// REALIZAR CONSULTA SI RUC NO ESTÁ VACIO
		myResource.sendData('factibilidadglp/REQUEST').save({feasibilityId:$scope.frmParent.factibilidad_id},function(json){
			$scope.frmParent.tanksList=json.data.tanksList;
			$scope.frmParent.billing=json.data.billing;
		});
	}
	// AGREGAR TRABAJO A REALIZAR
	$scope.addJobToDo=function(idx){
		var aux={tanque_capacidad:'0'};
		$scope.frmParent[idx].push(aux);
	};
	// REMOVER TRABAJO A REALIZAR
	$scope.removeItem=function(item,idx){
		var index = $scope.frmParent[idx].indexOf(item);
		$scope.frmParent[idx].splice(index, 1);
	};
}])
 /* 
  * Modal: modalResponsables
  * Función: 
  */
 .controller('professionalExtendsCtrl',['$scope','findEntityService',function($scope,findEntityService){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> responsibleExtendsCtrl -> Form to reference:: ',frm);
 	// LISTADO DE PROFESIONALES
	$scope.frmParent.professionalList=[];
 	// LISTADO DE RECURSOS
 	findEntityService.myResource.sendData('factibilidadglp/REQUEST').save({profesionals:true,projectId:$scope.frmParent.factibilidad_id},function(json){
 		$scope.frmParent.professionalList=angular.merge($scope.frmParent.professionalList,json.data.professionalList);
 	}).$promise;
 	// BUSCAR PERSONA
 	$scope.searchPersonInformation=function(data,frm,model,index){
 		if(findEntityService.myResource.testNull(data)) findEntityService.findPerson({academicTraining:data},$scope,frm,model,index);
 	};
	// AGREGAR TRABAJO A REALIZAR
	$scope.addJobToDo=function(idx){
		var aux={};
		$scope.frmParent[idx].push(aux);
	};
	// REMOVER TRABAJO A REALIZAR
	$scope.removeItem=function(item,idx){
		var index = $scope.frmParent[idx].indexOf(item);
		$scope.frmParent[idx].splice(index, 1);
	};
}])
/* 
 * Modal: modalDefinitivoglp
 * Función: 
 */
.controller('definitiveExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> definitiveExtendsCtrl -> Form to reference:: ',frm);
	// GUARDAR REGISTRO E IMPRIMIR FORMULARIO
	$scope.submitCustomForm=function(){
		// PRESENTAR CONFIRMACIÓN DE FORMULARIO
		myResource.myDialog.swalConfirm('Por favor,revise detenidamente el formulario antes de realizar esta acción.',function(){
			// DEFINIR EL METODO DE ENVIO
			var method='';
			// ENVIAR FORMULARIO
			myResource.sendData('definitivoglp'+method).save($scope.frmParent,function(json){
				myResource.myDialog.swalAlert(json);
				if(json.estado===true){
					window.open(reportsURI+'definitivoglp/?withDetail&id='+json.data.id,'_blank');
					myResource.state.go('main.definitive');
					myResource.myDialog.closeDialog();
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
 * SELECCIÓN DE PERSONAL PARA CAPACITACIÓN
 */
.controller('trainingExtendsCtrl',['$scope','myResource',function($scope,myResource){

	console.log('Extends request -> brigadistsExtendsCtrl -> Form to reference:: ',$scope.frm);
	
	// CONSULTAR PERSONAL DE ENTIDADES
	myResource.requestData('prevention/training/participants/entityId').save({entityId:$scope.frmParent.entidad_id},function(json){
		$scope.list=json.data;
	}).$promise;
	// MODELO PARA SELECCION DE PARTICIPANTES
	$scope.frmParent.selected=[];
	// CONSULTAR PERSONAL DE ENTIDADES
	myResource.requestData('prevention/training/participants/trainingId').save({trainingId:$scope.frmParent.capacitacion_id},function(json){
		$scope.frmParent.selected=json.data;
	}).$promise;
	
}])
/*
 * LISTADO DE PREGUNTAS PARA EVALUACIÓN DE SATISFACCIÓN
 */
.controller('questionsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.getData('questions/REQUEST').get(function(json){$scope.list=json;}).$promise;
}])
/*
 * *******************************************
 * SUBIR LISTA DE PARTICIPANTES - XLS
 * *******************************************
 * Modal: modalParticipantesLista
 * Función: PARA CARGAR LOS DATOS DE PARTICIPANTES DE UNA CAPACITACIÓN 
 */
.controller('participantsLisExtendsCtrl',['$scope','myResource',function($scope,myResource){
	console.log('Extends request -> participantsLisExtendsCtrl -> Form to reference:: ',$scope.frm);
	$scope.frmParent.doc_identidad='A';
	$scope.frmParent.apellidos='B';
	$scope.frmParent.nombres='C';
	$scope.frmParent.hoja_numero='1';
	$scope.frmParent.fila_inicio='A2';
}])





/*
 * DETALLE DE VISITAS
 */
.controller('visitExtendsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.frmParent.label='LB_COORDINATOR';
}])
/*
 * SELECCIÓN DE PERSONAL PARA SIMULACROS
 */
.controller('simulationExtendsCtrl',['$scope','myResource',function($scope,myResource){
	myResource.sendData('participantes/REQUEST').save({simulationId:$scope.frmParent.simulacro_id},function(json){
		$scope.list=json.data;
		$scope.selected=json.participantList;
	});
	// ******************************** GET DATA PARA RELACIÓN - sucursal o permisos
	$scope.toggle=function(item){
		var data={fk_simulacro_id:$scope.frmParent.simulacro_id,fk_participante_id:item};
		myResource.sendData('participantes/REQUEST').update(data,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=$scope.selected.indexOf(item);
				(idx>-1)?$scope.selected.splice(idx,1):$scope.selected.push(item);
			}
		});
	};
	$scope.exists=function(item){return (myResource.testNull($scope.selected))?$scope.selected.indexOf(item)>-1:null;};
	// ******************************** CERRAR MODAL
	$scope.closeDialog=function(){ myResource.myDialog.closeDialog(); };
}])














;