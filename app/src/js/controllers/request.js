/**
 * 
 */
app
.controller('submitCtrl',['$scope','myResource','findEntityService',function($scope,myResource,findEntityService) {
	// ***************************************** INICIO ****************************************
	// ******************************** DECLARACIÓN DE MODELOS
	var tb=myResource.getTempData('tb'); // nombre de tabla para guardar Ej. producto
	var frm='frm'+myResource.getTempData('Index'); // nombre de formulario Ej. frmProducto
	$scope.paramsConf=myResource.getTempData('paramsConf'); // VARIABLES GLOBALES
	$scope.sessionEntity=myResource.getTempData('sessionEntity'); // VARIABLES GLOBALES
	// nombre de formulario Para extends
	$scope.frm=frm;
	console.log('Controller instanceof -> '+frm);
	// DATOS PARA EL EVENTO SUBMIT
	$scope[frm]=myResource.getTempData(tb); // Datos desde tabla Ej. row
	$scope.frmParent=$scope[frm]; // Formulario para heredar en extends
	$scope.toogleEditMode=myResource.testModeEdit(tb); // Estado de edición o nuevo		
	// set real tb
	tb=myResource.testNull(customTb[tb])?customTb[tb]:tb;
	$scope.tb=tb; // Almacena index de tabla en minúscula Ej. producto
	if(myResource.testNull($scope[frm].edit)) $scope.toogleEditMode=false;
	if(myResource.testNull($scope[frm].edit)) console.log('PUT -> POST');
	$scope.estadoModo=$scope.toogleEditMode?'Edición':'Ingreso';
	// instancia date
	$scope.maxDate=new Date();
	$scope.today=moment().format('YYYY-MM-DD');
	// ********************************** MODALES ************************************************
	// ******************************** CERRAR MODAL
	$scope.closeDialog=function(){ myResource.myDialog.closeDialog(); };
	function getResource(){
		var Tb=myResource.getTempData('tbTemp'); // Producto -> producto
		myResource.setTempData('tb',Tb); // nombre de tabla que va a guardar Ej. producto
		myResource.setTempData(Tb,myResource.getTempData('rowTemp')); // almacenar row en tabla Ej. $scope.producto=row
		myResource.setTempData('Index',myResource.getTempData('frmTemp')); // index para formulario Ej. Producto
		myResource.myDialog.showModal(myResource.getTempData('ctrlTemp'),myResource.getTempData('modalTemp'));
	}
	// ************************* EVENTO SUBMIT FORMULARIOS ***************************************
	// ******************************** EVENTO
	$scope.submitRequestForm=function(){
		if($scope.toogleEditMode){
			myResource.requestData($scope[frm].entityURI).update($scope[frm],function(json){
				 myResource.myDialog.swalAlert(json);
				 if(json.estado===true)myResource.myDialog.closeDialog();
			 },myResource.setError);
		} else {
			myResource.requestData($scope[frm].entityURI).save($scope[frm],function(json){
				if(json.estado===true){
					$scope[frm]={};
					myResource.myDialog.closeDialog();
				}	myResource.myDialog.swalAlert(json);
			},myResource.setError);
		}
	};
	$scope.submitForm=function(){
		if($scope.toogleEditMode){
			myResource.sendData(tb+'/PUT').update($scope[frm],function(json){
				 myResource.myDialog.swalAlert(json);
				 if(json.estado===true)myResource.myDialog.closeDialog();
			 },myResource.setError);
		} else {
			myResource.sendData(tb).save($scope[frm],function(json){
				if(json.estado===true){
					$scope[frm]={};
					myResource.myDialog.closeDialog();
				}	myResource.myDialog.swalAlert(json);
			},myResource.setError);
		}
	};
	$scope.submitFormFile=function(){
		if($scope.toogleEditMode){
			myResource.uploadFile.uploadFile(rootRequest+tb+'/PUT',$scope,frm).then(function(json){
				if(json.data.estado)myResource.myDialog.closeDialog();
				myResource.myDialog.swalAlert(json.data);
			},myResource.setError);
		}else{
			myResource.uploadFile.uploadFile(rootRequest+tb,$scope,frm).then(function(json){
				if(json.data.estado)myResource.myDialog.closeDialog();
				myResource.myDialog.swalAlert(json.data);
			},myResource.setError);
		}
	};
	// *** STRING TO JSON
	$scope.string2JSON=function(string){
		return angular.fromJson($scope.paramsConf[string]);
	};
	// *** IMPRIMIR DATOS HTML
	$scope.trustAsHtml=function(html){return myResource.sce.trustAsHtml(html);};
	// *** CUSTOM PLACEHOLDER
	$scope.getPlaceholder=function(value,msg){return (myResource.testNull(value)&&value!=' ')?value:msg;};
	// *** BUSCAR
	$scope.highlight=function(text,search){
		if(!myResource.testNull(text)) return '-';
		else text=''+text;
		if (!search)return myResource.sce.trustAsHtml(text);
		if(myResource.testNull(text)) return myResource.sce.trustAsHtml(text.replace(new RegExp(search,'gi'),'<span class="highlightedText">$&</span>'));
	};
	// *** BUSCAR ENTIDADES POR URL
	$scope.searchEntityByURI=function(url,data,frm,index,index2){
		if(myResource.testNull(data)) findEntityService.requestEntity(url,data,$scope,frm,index,index2);
	};
	// *** BUSCAR DATOS DE PERSONAS
	$scope.searchPersonInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findPerson({identityCard:data},$scope,frm,index);
	};
	// *** BUSCAR DATOS DE RUC
	$scope.searchRucInformation=function(data,frm,index){
		if(myResource.testNull(data)) findEntityService.findEntity({ruc:data},$scope,frm,index);
	};
}])
;