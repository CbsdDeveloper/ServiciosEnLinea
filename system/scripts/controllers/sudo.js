/*
 * 
 */
app 
/*
 * *******************************************
 * ADMINISTRACIÓN
 * *******************************************
 */
/*
 * PAISES
 */
.controller('countriesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Countries',tb:'countries',order:'country_name',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * ESTADOS - PROVINCIAS
 */
.controller('statesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'States',tb:'states',order:'country_name',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * CIUDADES
 */
.controller('citiesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Cities',tb:'cities',order:'country_name',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * CANTONES
 */
.controller('townsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'towns',tb:'towns',order:'country_name',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * PARROQUIAS
 */
.controller('parishesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Parishes',tb:'parishes',order:'country_name',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * MARCAS DE VEHÍCULOS
 */
.controller('brandsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Brands',tb:'brands',order:'brand_title',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * VEHÍCULOS
 */
.controller('vehiclesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'',parent:'Vehiculos',tb:'vehiculos',order:'vehiculo_placa',toolbar:{showPrint:false,showNew:true}};
}])
/*
 * PERSONAS
 */
.controller('peopleCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'',parent:'Personas',tb:'personas',order:'persona_apellidos',toolbar:{showPrint:false,showNew:true}};
}])

/*
 * *******************************************
 * SEGUNDA JEFATURA
 * *******************************************
 */
/*
 * CATEGORIAS DE LICENCIAS DE CONDUCIR
 */
.controller('driverslicensesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Licenciasdeconducir',tb:'licenciasdeconducir',order:'licencia_tipo',toolbar:{showPrint:false,showNew:true}};
}])

/*
 * *******************************************
 * SERVICIOS
 * *******************************************
 */
/*
 * DENUNCIAS
 */
.controller('slidesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Slides',tb:'slides',order:'-slide_titulo',toolbar:{}};
}])
/*
 * DENUNCIAS
 */
.controller('complaintsCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Denuncias',tb:'denuncias',order:'-mensaje_fecha',toolbar:{}};
}])
/*
 * MENU
 */
.controller('menuCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Menu',tb:'menu',order:'-menu_page',toolbar:{showNew:true}};
}])
/*
 * MENU
 */
.controller('pagesCtrl',['$scope','myResource',function($scope,myResource){
	$scope.tbParams={path:'sudo',parent:'Pages',tb:'pages',order:'-page_url',toolbar:{showNew:true}};
}])
;