/**
 * 
 */
app
.controller('submitCtrl',['$rootScope','$scope','myResource',function($rootScope,$scope,myResource) {
	// ***************************************** INICIO ****************************************
	// ******************************** DECLARACIÓN DE MODELOS
	var tb=myResource.getTempData('tb'); // nombre de tabla para guardar Ej. producto
	var frm='frm'+myResource.getTempData('Index'); // nombre de formulario Ej. frmProducto
	$scope.paramsConf=myResource.getTempData('paramsConf'); // VARIABLES GLOBALES
	$scope.paramsSession=myResource.getTempData('paramsSession'); // VARIABLES GLOBALES
	// nombre de formulario Para extends
	$scope.frm=frm;
	console.log('Controller instanceof -> '+frm);
	// ******************************************** *** DATOS PARA EL EVENTO SUBMIT
	$scope[frm]=myResource.getTempData(tb); // Datos desde tabla Ej. row
	$scope.frmParent=$scope[frm]; // Formulario para heredar en extends
	$scope.toogleEditMode=myResource.testModeEdit(tb); // Estado de edición o nuevo		
	// ******************************************** *** NOMBRE DE ENTIDAD
	tb=myResource.testNull(customTb[tb])?customTb[tb]:tb;
	tb=myResource.testNull($scope.frmParent.tb)?$scope.frmParent.tb:tb;
	$scope.tb=tb; // Almacena index de tabla en minúscula Ej. producto
	if(myResource.testNull($scope[frm].edit)) $scope.toogleEditMode=false;
	if(myResource.testNull($scope[frm].edit)) console.log('PUT -> POST');
	$scope.estadoModo=$scope.toogleEditMode?'Edición':'Ingreso';
	$scope.maxDate=new Date();
	$scope.today=moment().format('YYYY-MM-DD HH:mm');
	$scope.tomorrow=moment().add(1,'day').format('YYYY-MM-DD');
	// ********************************** VARIABLES **********************************************
	$scope.statusLabel=statusLabel;
	// ********************************** MODALES ************************************************
	// ******************************** MODALES AUXILIARES
	$scope.openModalData=function(frm,row){
		var Tb=frm.toLowerCase();
		myResource.setTempData('tb',Tb);
		myResource.setTempData(Tb,myResource.testNull(row)?myResource.getTempData('rowTemp'):{edit:false});
		myResource.setTempData('Index',frm);
		myResource.myDialog.showModalFN(
				myResource.testNull(customCtrl[Tb])?customCtrl[Tb]:customCtrl['default'],
				(myResource.testNull(customModal[Tb])?customModal[Tb]:pathModal+'modal'+frm)+'.html',
				getResource, getResource);
	};
	function getResource(){
		var Tb=myResource.getTempData('tbTemp'); // Producto -> producto
		myResource.setTempData('tb',Tb); // nombre de tabla que va a guardar Ej. producto
		myResource.setTempData(Tb,myResource.getTempData('rowTemp')); // almacenar row en tabla Ej. $scope.producto=row
		myResource.setTempData('Index',myResource.getTempData('frmTemp')); // index para formulario Ej. Producto
		myResource.myDialog.showModal(myResource.getTempData('ctrlTemp'),myResource.getTempData('modalTemp'));
	}
	// ******************************** CERRAR MODAL
	$scope.closeDialog=function(){ myResource.myDialog.closeDialog(); };
	// ************************* EVENTO SUBMIT FORMULARIOS ***************************************
	// ******************************** EVENTO
	$scope.jsonResource={};
	$scope.submitForm=function(){
		if($scope.toogleEditMode){
			myResource.sendData(tb+'/PUT').update($scope[frm],function(json){
				myResource.myDialog.showNotify(json);
				if(json.estado===true)myResource.myDialog.closeDialog();
				// DATOS DE RETORNO DE BACKEND
				if(myResource.testNull(json.data)) $scope.jsonResource=json.data;
			 },myResource.setError);
		} else {
			myResource.sendData(tb).save($scope[frm],function(json){
				if(json.estado===true){
					$scope[frm]={};
					myResource.myDialog.closeDialog();
				}	myResource.myDialog.showNotify(json);
				// DATOS DE RETORNO DE BACKEND
				if(myResource.testNull(json.data)) $scope.jsonResource=json.data;
			},myResource.setError);
		}
	};
	$scope.submitFormFile=function(){
		if($scope.toogleEditMode){
			myResource.uploadFile.uploadFile(rootRequest+tb+'/PUT',$scope,frm).then(function(json){
				if(json.data.estado)myResource.myDialog.closeDialog();
				myResource.myDialog.showNotify(json.data);
			},myResource.setError);
		}else{
			myResource.uploadFile.uploadFile(rootRequest+tb,$scope,frm).then(function(json){
				if(json.data.estado)myResource.myDialog.closeDialog();
				myResource.myDialog.showNotify(json.data);
			},myResource.setError);
		}
	};
	$scope.submitFormPDF=function(){
		$scope.content=null;
		myResource.http.post(rootRequest+tb,$scope[frm],{responseType:'arraybuffer'}).success(function (json) {
			
			var file = new Blob([json], {type:'application/pdf'});
		    var fileURL = URL.createObjectURL(file);
		    $scope.content = myResource.sce.trustAsResourceUrl(fileURL);
		    
		    url = myResource.window.URL || myResource.window.webkitURL;
		    $scope.fileUrl = url.createObjectURL(file);
			
		},myResource.setError);
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
 }])
;