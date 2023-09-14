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
.controller('profileCtrl',['account','$scope','myResource',function(account,$scope,myResource){
	$scope.tbParent='Personal';
	$scope.dataEntity=account.session;
	$scope.permisos=account.permisos;
	// CUSTOM TOOLBAR FILTER - BITÁCORA
	$scope.filterInTab={mdIcon:'timer',label:'LB_ACTIVITY_LOG',search:true};
	// myTableCtrl
	$scope.tbParams={parent:'Bitacora',tb:'bitacora',order:'-fecha',toolbar:{}};
	// IMPRIMIR CV
	$scope.exportToPDF=function(){
		myResource.printReport('personal',{type:'PDF',account:'tthh',withDetail:true,id:account.session.personal_id});
	};
}])
/*
 * CALENDARIO DE EVENTOS
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
 * UNIDADES
 */
.controller('unitsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={path:'tthh/institution',entity:'Unidades',uri:'administrative/units/units',order:'unidad_nombre',toolbar:{}};
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
 * DISTRIBUTIVO
 */
.controller('distributionCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={parent:'Distributivo',tb:'personal_operativo',order:'personal_nombre',toolbar:{}};
}])
/* 
 * GUARDIAS DEL PERSONAL OPERATIVO
 */
.controller('guardsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Guardias',tb:'getGuards',order:'-guardia_fecha',toolbar:{}};
}])
/* 
 * VALES DE COMBUSTIBLE
 */
.controller('fuelvouchersCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Ordenescombustible',tb:'getFuelorders',order:'-orden_codigo',toolbar:{}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.filterBarCode={options:{debounce:500},code:'',module:'subjefature'};
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
  * FLOTAS VEHICULARES
  */
.controller('trackingCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={parent:'Flotasvehiculares',tb:'getTracking',order:'-flota_salida_hora',toolbar:{}};
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
  * LIBRO DE NOVEDADES
  */
.controller('binnacleCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={parent:'Libronovedades',tb:'getBinnacle',order:'-bitacora_fecha',toolbar:{}};
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
  * PARTES
  */
.controller('partsCtrl',['$scope','myResource',function($scope,myResource){
	 $scope.tbParams={parent:'Partes',tb:'getParts',order:'-parte_fecha',toolbar:{}};
	// OPEN MODAL - NUEVO REGISTRO
	$scope.filterBarCode={options:{debounce:500},code:''};
	$scope.scanBarCode=function(){
		myResource.getData('partes/REQUEST').save($scope.filterBarCode,function(json){
			if(json.estado!==true) myResource.myDialog.swalAlert(json);
			if(json.estado===true) myResource.state.go('main.newPart.draft',{key:$scope.filterBarCode.code});
		},myResource.setError);
	};
}])

/*
 * NUEVO PARTE
 */
.controller('newPartCtrl',['$scope','myResource',function($scope,myResource){
	// TOOLBAR
	$scope.toolbar='';
}])
/*
 * PARTE - PASO 1
 */
.controller('newPart.step1Ctrl',['entity','$scope','myResource','leafletData',function(entity,$scope,myResource,leafletData){
	// TOOLBAR
	$scope.$parent.toolbar='LB_NEWPART_STEP1';
	
	// DATOS GENERALES
	$scope.today=moment().format('YYYY-MM-DD');
	// DATOS DE PERFIL
	$scope.dataEntity=entity.data;
	// VARIABLES PARA FORMULARIOS
	$scope.frmParent=entity.data;
	// EDITOR
	$scope.toogleEditMode=$scope.frmParent.toogleEditMode;
	
	// PARSE FECHAS
	$scope.frmParent.parte_aviso_hora=new Date($scope.frmParent.parte_aviso_hora);
	$scope.frmParent.parte_aviso_salida_personal=new Date($scope.frmParent.parte_aviso_salida_personal);
	$scope.frmParent.parte_aviso_llegada_personal=new Date($scope.frmParent.parte_aviso_llegada_personal);
	$scope.frmParent.parte_aviso_retorno=new Date($scope.frmParent.parte_aviso_retorno);
	$scope.frmParent.parte_aviso_ingreso_cuartel=new Date($scope.frmParent.parte_aviso_ingreso_cuartel);
	
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
	$scope.submitForm=function(){
		
		// CONFIRMAR DIALOGO
		myResource.myDialog.swalConfirm('¿Confirma que los datos ingresados son correctos?',function(){
			
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
					$scope.frmParent.step=1;
					myResource.sendData('partesAsync/PUT').update($scope.frmParent,function(json){
						if(json.estado===true) myResource.state.go('main.newPart.step2',{entityId:json.data.entityId});
						myResource.myDialog.showNotify(json);
					 },myResource.setError);
				} else {
					myResource.sendData('partesAsync').save($scope.frmParent,function(json){
						if(json.estado===true) myResource.state.go('main.newPart.step2',{entityId:json.data.entityId});
						myResource.myDialog.showNotify(json);
					},myResource.setError);
				}
			});
			
		});
		
		
	};
	
}])
/*
 * PARTE - PASO 2
 */
.controller('newPart.step2Ctrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// TOOLBAR
	$scope.$parent.toolbar='LB_NEWPART_STEP2';
	
	// DATO DEL FORMULARIO
	$scope.dataEntity=entity.data;
	$scope.frmParent=entity.data;
	
	// SUBMIT FORMULARIO
	$scope.submitForm=function(){
		$scope.frmParent.step=2;
		// ENVIO DE FORMULARIO A BACKEND
		myResource.sendData('partesAsync/PUT').update($scope.frmParent,function(json){
			if(json.estado===true) myResource.state.go('main.newPart.step3',{entityId:$scope.frmParent.parte_id});
			myResource.myDialog.showNotify(json);
		 },myResource.setError);
	};
	
}])
/*
 * PARTE - PASO 3
 */
.controller('newPart.step3Ctrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// TOOLBAR
	$scope.$parent.toolbar='LB_NEWPART_STEP3';
	
	// DATO DEL FORMULARIO
	$scope.dataEntity=entity.data;
	$scope.frmParent=entity.data;
	$scope.sourceList={ };
	$scope.newToggleForm = { wounded:false, attended:false, supplies:false };
	$scope.newFormData = { supplies: {}, attended: {}, wounded: {} };
	$scope.tempModel={};
	
	// CONSULTAR ATENDIDOS Y MATERIALES DE UN PARTE
	$scope.loadSourcesList=function(){
		// LISTA ATENDIDOS
		myResource.requestData('subjefature/parts/attended/partId').json({partId:$scope.frmParent.parte_id},function(json){
			$scope.sourceList.wounded=json.data.wounded;
			$scope.sourceList.attended=json.data.attended;
		},myResource.setError);
		// LISTA INSUMOS UTILIZADOS
		myResource.requestData('subjefature/parts/supplies/partId').json({partId:$scope.frmParent.parte_id},function(json){
			$scope.sourceList.supplies = json.data;
		},myResource.setError);
	};	$scope.loadSourcesList();
	
	// GUARDAR REGISTROS
	$scope.saveItemPart=function(frm,entity){
		// INGRESO DE MODELO
		$scope.tempModel = angular.merge({fk_parte_id:$scope.frmParent.parte_id},$scope.newFormData[frm]);
		// ENVIAR DATOS
		myResource.requestData('subjefature/parts/' + entity + '/partId/new').save($scope.tempModel,function(json){
			if(json.estado===false) myResource.myDialog.showNotify(json);
			else { $scope.newFormData[frm]={}; $scope.loadSourcesList(); $scope.newToggleForm[frm]=false; } 
		},myResource.setError);
		
	};
	
	// REMOVER ITEM DE LA LISTA
	$scope.removeItemPart=function(entity,idx){
		myResource.requestData('subjefature/parts/' + entity + '/partId/remove').remove({entityId:idx},function(json){
			if(json.estado===false) myResource.myDialog.showNotify(json);
			else $scope.loadSourcesList();
		},myResource.setError);
	};
	
	// SUBMIT FORMULARIO
	$scope.submitForm=function(){
		$scope.frmParent.step=3;
		// ENVIO DE FORMULARIO A BACKEND
		myResource.sendData('partesAsync/PUT').update($scope.frmParent,function(json){
			if(json.estado===true) myResource.state.go('main.parts');
			myResource.myDialog.showNotify(json);
		 },myResource.setError);
	};
	
}])

 /*
  * NUEVO PARTE
  */
.controller('newPartOldCtrl',['entity','$scope','myResource','leafletData',function(entity,$scope,myResource,leafletData){
	// DATOS GENERALES
	$scope.today=moment().format('YYYY-MM-DD');
	// DATOS DE PERFIL
	$scope.dataEntity=entity.data;
	// VARIABLES PARA FORMULARIOS
	$scope.frmParent=entity.data;
	// EDITOR
	$scope.toogleEditMode=$scope.frmParent.toogleEditMode;
	
	// AGREGAR ITEM A LA LISTA
	$scope.addItemList=function(idx){
		$scope.frmParent[idx].push({});
	};
	// REMOVER ITEM DE LA LISTA
	$scope.removeItemList=function(item,idx){
		var index=$scope.frmParent[idx].indexOf(item);
		$scope.frmParent[idx].splice(index,1);
	};
	
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
	$scope.submitForm=function(){
		// DATOS DE BACKEND
		var tb='partes';
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
					if(json.estado===true) myResource.state.go('main.parts');
					myResource.myDialog.showNotify(json);
				 },myResource.setError);
			} else {
				myResource.sendData(tb).save($scope.frmParent,function(json){
					if(json.estado===true) myResource.state.go('main.parts');
					myResource.myDialog.showNotify(json);
				},myResource.setError);
			}
		});
	};
}])
/* 
 * MATERIALES PREHOSPITALARIOS
 */
.controller('aph.aphsuppliesCtrl',['$scope','myResource',function($scope,myResource){
	
	$scope.tbParams={path:'tthh',entity:'Stock',uri:'tthh/aph/supplies/stock/stations/list',order:'insumo_nombre',toolbar:{},custom:{stationId:$scope.session.fk_estacion_id}};
	
}])


/*
 * *******************************************
 * PERFIL
 * *******************************************
 */
/* 
 * VALES DE COMBUSTIBLE
 */
.controller('records.fuelvouchersCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Ordenescombustible',tb:'ordenescombustible',order:'-orden_codigo',toolbar:{}};
}])
/*
 * GUARDIAS DEL PERSONAL OPERATIVO
 */
 .controller('records.guardsCtrl',['$scope','myResource',function($scope,myResource){
 	$scope.tbParams={parent:'Guardias',tb:'guardias',order:'-guardia_fecha',toolbar:{}};
}])
/* 
 * FLOTAS VEHICULARES
 */
.controller('records.trackingCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Flotasvehiculares',tb:'flotasvehiculares',order:'-flota_salida_hora',toolbar:{}};
}])
/*
 * LIBRO DE NOVEDADES
 */
.controller('records.binnacleCtrl',['$scope','myResource',function($scope,myResource){
 	 $scope.tbParams={parent:'Libronovedades',tb:'libronovedades',order:'-bitacora_registro',toolbar:{}};
}])
/*
 * PARTES
 */
.controller('records.partsCtrl',['$scope','myResource',function($scope,myResource){
 	 $scope.tbParams={parent:'Partes',tb:'partes',order:'-parte_fecha',toolbar:{}};
}])

 
 /*
  * ************************************ EXTENDS ************************************
  */
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
 		if(myResource.testNull($scope.frmParent.fk_town_id)) myResource.requestData('resources/parishes/'+$scope.frmParent.fk_town_id).json(function(json){$scope.parishesList=json.data;}).$promise;
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
  * Modal: modalFlotasvehiculares
  * Función: PARSE DE FECHAS 
  */
.controller('trackingExtendsCtrl',['$scope','myResource',function($scope,myResource){
 	var frm=$scope.$parent.frm;
 	console.log('Extends request -> trackingExtendsCtrl -> Form to reference:: ',frm);
 	// SELECCIÓN DE PARÁMETROS
 	if(!$scope.toogleEditMode) $scope.frmParent.flota_salida_tipo='SISTEMA';
}])
/* 
 * Modal: modalLibronovedades
 * Función: PARSE DE FECHAS 
 */
.controller('binnacleExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> binnacleExtendsCtrl -> Form to reference:: ',frm);
	// SELECCIÓN DE PARÁMETROS
	if(!$scope.toogleEditMode) $scope.frmParent.bitacora_fecha_tipo='SISTEMA';
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	myResource.sendData('libronovedades/REQUEST').save($scope.frmParent,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	});
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
.controller('platoonsListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('tthh/platoons').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalFlotasvehiculares
 * Función: LISTADO DE ESTACIONES
 */
.controller('stationsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('tthh/stations').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalFlotasvehiculares
 * Función: LISTADO DE CÓDIGOS PARA FLOTAS VEHICULARES
 */
.controller('codesTrackingListCtrl',['$scope','myResource',function($scope,myResource){
 	myResource.requestData('resources/institutionalcodes/tracking').json(function(json){$scope.jsonList=json.data;}).$promise;
}])
/*
 * Modal: modalOrdenescombustible
 * Función: LISTADO DE UNIDADES PARA ORDENES DE ABASTECIMIENTO
 */
.controller('unitsListCtrl',['$scope','myResource',function($scope,myResource){
	myResource.requestData('administrative/units').json(function(json){$scope.jsonList=json.data;}).$promise;
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
 * Modal: modalGuardias
 * Función: 
 */
.controller('guardsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> guardsExtendsCtrl -> Form to reference:: ',frm);
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	myResource.sendData('guardias/REQUEST').save($scope.frmParent,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	});
}])
/*
 * Modal: modalGuardia_turnos
 * Función: 
 */
.controller('guardsTurnsExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> guardsTurnsExtendsCtrl -> Form to reference:: ',frm);
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	$scope.frmParent.staffList={};
	myResource.sendData('guardias/REQUEST').save($scope.frmParent,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	});
}])
/*
 * Modal: modalPartes_flotas
 * Función: 
 */
.controller('partsTrackingExtendsCtrl',['$scope','myResource',function($scope,myResource){
	var frm=$scope.$parent.frm;
	console.log('Extends request -> partsTrackingExtendsCtrl -> Form to reference:: ',frm);
	
	// VALIDAR SI ES UN INGRESO O EDICIÓN
	$scope.frmParent.trackingList={};
	myResource.sendData('flotasvehiculares/REQUEST').save($scope.frmParent,function(json){
		$scope.frmParent=angular.merge($scope.frmParent,json.data);
	});
}])
;