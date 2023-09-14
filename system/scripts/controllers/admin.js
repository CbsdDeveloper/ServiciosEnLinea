/**
 * 
 */
app
/*
 * *******************************************
 * PARAMETRIZACION DEL SISTEMA
 * *******************************************
 */
/*
 * MODULOS DEL SISTEMA
 */
.controller('modulesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',parent:'Modulos',tb:'modulos',order:'modulo_nombre',toolbar:{}};
}])
/*
 * SUBMODULOS DEL SISTEMA
 */
.controller('submodulesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',parent:'Submodulos',tb:'submodulos',order:'modulo_nombre',toolbar:{}};
}])
/*
 * TABLAS DEL SISTEMA
 */
.controller('tablesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',parent:'Tablas',tb:'tablas',order:'modulo_nombre',toolbar:{}};
}])

/*
 * *******************************************
 * ADMINISTRACIÓN DE USUARIOS
 * *******************************************
 */
/*
 * ROLES DE USUARIOS
 */
.controller('rolesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin/users',parent:'Roles',tb:'roles',order:'rol_id::text',toolbar:{}};
}])
/*
 * ADMINISTRACIÓN DE PERFILES DE USUARIO
 */
.controller('profilesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin/users',parent:'Perfiles',tb:'perfiles',order:'perfil_nombre',toolbar:{showNew:true}};
}])
/*
 * PERMISOS DE ACCESO - MODAL PARA OTORGAR/REVOCAR ROLES A LOS PERFILES DE USUARIO
 */
.controller('groupsCtrl',['$scope','myResource',function($scope,myResource){
	// LISTADO DE ROLES SELECCIONADOS
	$scope.selected={};
	// ******************************** DATOS DE RELACIÓN
	myResource.sendData('roles/REQUEST').save({type:'profiles',entityId:$scope.frmParent.perfil_id},function(json){
		$scope.selected=json.data.selected;
		$scope.jsonList=json.data.resources;
	}).$promise;
	// ******** METODOS PARA CAMBIAR DATOS
	$scope.toggle=function(item){
		var data={fk_perfil_id:$scope.frmParent.perfil_id,fk_rol_id:item};
		myResource.sendData('groups/PUT').update(data,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=$scope.selected.indexOf(item);
				(idx>-1)?$scope.selected.splice(idx,1):$scope.selected.push(item);
			}
		});
	};
	$scope.exists=function(item){return (myResource.testNull($scope.selected))?$scope.selected.indexOf(item)>-1:null;};
}])
/* 
 * Modal: modalDashboard
 * Función: LISTA DE PERFILES DE USARIOS 
 */
.controller('profileDashboardExtendsCtrl',['$scope','myResource',function($scope,myResource){
	// LISTADO DE ROLES SELECCIONADOS
	$scope.selected={};
	// ******************************** DATOS DE RELACIÓN
	myResource.sendData('dashboard/REQUEST').save({profileId:$scope.frmParent.perfil_id},function(json){
		$scope.selected=json.data.selected;
		$scope.jsonList=json.data.resources;
		$scope.dashboardType=json.data.types;
	}).$promise;
	// ******************************** GET DATA PARA RELACIÓN
	$scope.toggle=function(item){
		var data={fk_perfil_id:$scope.frmParent.perfil_id,fk_dashboard_id:item,tipo:$scope.frmParent.tipo};
		myResource.sendData('dashboard').update(data,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=$scope.selected[$scope.frmParent.tipo].indexOf(item);
				(idx>-1)?$scope.selected[$scope.frmParent.tipo].splice(idx,1):$scope.selected[$scope.frmParent.tipo].push(item);
			}
		});
	};
	$scope.exists=function(item){return (myResource.testNull($scope.selected[$scope.frmParent.tipo]))?$scope.selected[$scope.frmParent.tipo].indexOf(item)>-1:null;};
}])
/*
 * ROLES DE ACCESO - MODAL PARA OTORGAR/REVOCAR ROLES A LOS USUARIOS
 */
.controller('accessCtrl',['$rootScope','$scope','myResource',function($rootScope,$scope,myResource){
	// LISTADO DE ROLES SELECCIONADOS
	$scope.selected={};
	// ******************************** DATOS DE RELACIÓN
	myResource.sendData('roles/REQUEST').save({type:'users',entityId:$scope.frmParent.usuario_id},function(json){
		$scope.selected=json.data.selected;
		$scope.jsonList=json.data.resources;
	}).$promise;
	// ******************************** GET DATA PARA RELACIÓN
	$scope.toggle=function(item){
		var data={fk_usuario_id:$scope.frmParent.usuario_id,fk_rol_id:item};
		myResource.sendData('roles').update(data,function(json){
			myResource.myDialog.showNotify(json);
			if(json.estado===true){
				var idx=$scope.selected.indexOf(item);
				(idx>-1)?$scope.selected.splice(idx,1):$scope.selected.push(item);
			}
		});
	};
	$scope.exists=function(item){return (myResource.testNull($scope.selected))?$scope.selected.indexOf(item)>-1:null;};
}])
/*
 * RESTAURAR CREDENCIALES
 */
.controller('restoreAccountCtrl',['$rootScope','$scope','myResource',function($rootScope,$scope,myResource){
	// ******************************** RESTAURAR DATOS
	var optionResult={user:'Usuario de sesión',pass:'Contraseña de acceso',roles:'Roles de acceso'};
	$scope.restoreData=function(option,data){
		myResource.myDialog.swalConfirm('Está seguro de restablecer el/la <b>'+optionResult[option]+'</b>',function(){
			myResource.sendData('usuarios/PUT').update({id:data.usuario_id,option:option},function(json){
				myResource.myDialog.showNotify(json);
			});
		});
	};
}])
/*
 * ADMINISTRACIÓN DE USUARIOS
 */
.controller('usersCtrl',['$scope','$state','myResource',function($scope,$state,myResource){
	$scope.tbParams={path:'admin/users',parent:'Usuarios',tb:'usuarios',order:'persona_apellidos',toolbar:{}};
}])
/*
 * INFORMACIÓN DE USUARIOS
 */
.controller('detailUsersCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	$scope.tbParent='Usuarios';
	$scope.dataEntity=entity.data;
	// CUSTOM TOOLBAR FILTER - HISTORIAL
	$scope.filterInTab={mdIcon:'timer',label:'LB_ACTIVITY_LOG',search:true};
	// myTableCtrl
	$scope.tbParams={parent:'Bitacora',tb:'bitacora',order:'-fecha',toolbar:{},custom:{id:entity.data.usuario_id}};
}])


/*
 * ADMINISTRACIÓN DEL SISTEMA
 */
.controller('systemCtrl',['sistema','$scope','myResource',function(sistema,$scope,myResource){
	$scope.sistema=sistema[0];
	$scope.submitForm=function(){
		myResource.sendData('sistema/PUT')
		.update($scope.sistema,function(json){
			 myResource.myDialog.showNotify(json);
			 if(json.estado===true)myResource.myDialog.closeDialog();
		 },myResource.setError);
	};	
}])
/*
 * LABELS DEL SISTEMA
 */
.controller('labelsCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'admin',entity:'Labels',uri:'admin/labels',order:'label_module',toolbar:{showNew:true}};
	// CREACIÓN DE ARCHIVO DE CONFIGURACIÓN .json
	$scope.createFile=function(){
		myResource.myDialog.swalConfirm('Está seguro de realizar esta operación?.',function(){
			myResource.sendData('label/REQUEST').save({data:'12345'},function(json){
				myResource.myDialog.showNotify(json);
			},myResource.setError).$promise;
		});
	};
}])
/*
 * RUTAS DEL SISTEMA
 */
.controller('routesCtrl',['$scope','myResource',function($scope,myResource){
	// myTableCtrl
	$scope.tbParams={path:'admin',parent:'Rutas',tb:'rutas',order:'submodulo_nombre',toolbar:{showNew:true}};
	// CREACIÓN DE ARCHIVO DE CONFIGURACIÓN .json
	$scope.createFile=function(){
		myResource.myDialog.swalConfirm('Está seguro de realizar esta operación?.',function(){
			myResource.sendData('rutas/REQUEST').save({data:'12345'},function(json){
				myResource.myDialog.showNotify(json);
			},myResource.setError).$promise;
		});
	};
}])
/*
 * RECURSOS DEL SISTEMA
 */
.controller('resourcesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',parent:'Recursos',tb:'recursos',order:'recurso_nombre',toolbar:{showNew:true}};
}])
/*
 * VARIABLES DEL SISTEMA
 */
.controller('parametersCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',entity:'Params',uri:'admin/parameters',order:'param_key',toolbar:{showNew:true}};
}])
/*
 * CONFIGURACIÓN DE WEBMAIL
 */
.controller('webmailCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',entity:'Webmail',uri:'admin/webmail',order:'webmail_name',toolbar:{}};
}])
/*
 * REPORTES DEL SISTEMA
 */
.controller('reportsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',entity:'Reportes',uri:'admin/reports',order:'reporte_nombre',toolbar:{}};
}])
/*
 * PARAMETRIZACIÓN DE DASHBOARD
 */
.controller('adminDashboardCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'admin',parent:'Dashboard',tb:'dashboard',order:'dashboard_nombre',toolbar:{}};
}])
/*
 * PAPELERA DEL SISTEMA
 */
.controller('trashCtrl',['trash','$scope','myResource',function(trash,$scope,myResource){
	$scope.trash=trash;
	$scope.getTrash=function(){
		myResource.getData('getTrash').json(function(json){
			$scope.trash=json;
		},myResource.setError);
	};
	$scope.restoreItem=function(row,enty){
		myResource.myDialog.swalConfirm('Seguro desea restaurar este item',function(){
			var data={};
			data[enty.state]='Activo';
			data[enty.id]=row.id;
			data.restoreFromTrash='';
			myResource.sendData(enty.tb+'/PUT')
			.update(data,function(json){
				if(json.estado===true)$scope.getTrash();
				 myResource.myDialog.showNotify(json);
			},myResource.setError);
		});
	}
}])
/*
 * MENSAJE DEL SISTEMA
 */
.controller('messagesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={parent:'Mensajes',tb:'mensajes',order:'mensaje_enviado',toolbar:{showNew:true}};
}])
/*
 * DETALLE DE MENSAJE
 */
.controller('detailMessageCtrl',['message','$scope','myResource',function(message,$scope,myResource){
	$scope.message=message;
}])

/*
 * *******************************************
 * DIRECCIÓN GENERAL
 * *******************************************
 */
/*
 * CHECKING DE PERSONAS
 */
.controller('management.checkingCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'management',parent:'Checking_externos',tb:'checking_externos',order:'-checking_ingreso',toolbar:{showPrint:false}};
	// OPEN MODAL - INGRESO DE PROYECTO
	$scope.scanBarCode=function(){
		myResource.getData('checking_externos/REQUEST').save($scope.filterBarCode,function(json){
			if(!json.estado) myResource.myDialog.showNotify(json);
			else {
				$scope.filterBarCode.code='';
				$scope.$parent.openModal('Checking_externos',json.data);
			}
		},myResource.setError);
	};
}])
/*
 * CHECKING DE PERSONAS
 */
.controller('management.documentsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'management',parent:'documents',tb:'documentos_recibidos',order:'-documento_ingreso',toolbar:{}};
}])
/*
 * DENUNCIAS EN LÍNEA
 */
.controller('management.complaintsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'management',parent:'Denuncias',tb:'denunciasenlinea',order:'-mensaje_fecha',toolbar:{}};
}])
/*
 * DETALLE DE DENUNCIAS EN LÍNEA
 */
.controller('management.detailComplaintsCtrl',['entity','$scope','myResource',function(entity,$scope,myResource){
	// INFORMACIÓN DE REGISTRO
	$scope.dataEntity=entity.data;
	// SET PATH MODAL
	$scope.$parent.pathEntity='management';
}])
;