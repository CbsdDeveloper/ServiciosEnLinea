/**
 * 
 */
// DIRECTIVAS PARA VISTAS EN TEMPLATES
app
/* *** Font Awesome ***
 * @i ícono
 * @s size 
 * @c other class
 */
.directive('iFa',function(){
    return {template:'<i class="fa fa-{{i}} fa-{{s}} {{c}}"></i>', scope:{i:"@", s:"@",c:"@" } };
})
.directive('iError',function(){
    return {
    	template:'<span class="help-block"><i-fa i=times-circle-o s=fw></i-fa>{{t}}</span>', 
    	scope:{t:"@" } 
    };
})
/* *** Material Ícon ***
 * @i ícono
 * @s size 
 * @c color
 * @d drección
 */
.directive('mdIco',function(){
    return {template:'<i class="material-icons md-{{s}} {{c}} {{d}}">{{i}}</i>', scope:{i:"@", s:"@",c:"@",d:"@" } };
})
.directive('dateInput', function(){
    return {
        restrict:'A',
        scope:{ngModel:'='},
        link:function (scope){
            if(scope.ngModel) scope.ngModel = new Date(scope.ngModel);
        }
    }
})
.directive('parseFloat', function() {
  return {
	 priority:1,
	 restrict:'A',
	 require:'ngModel',
	 link:function(scope, element, attr, ngModel) {
		function toModel(value) {
		  return "" + value; // convert to string
		}
		function toView(value) {
		  return parseFloat(value); // convert to number
			}
			ngModel.$formatters.push(toView);
			ngModel.$parsers.push(toModel);
		 }
	  };
})
.factory('focus', function($timeout,$window){
	return function(id){
		$timeout(function(){
			var element=$window.document.getElementById(id);
			if(element) element.focus();
		});
	};
})
// filtros
.filter('isEmpty',function(){
	var bar;
    return function(obj){
        for(bar in obj){if(obj.hasOwnProperty(bar)){return false;}}	
        return true;
    };
})
.filter('removeAcentos', function(){
    return function (source) {
        if(!angular.isDefined(source)){return; }
        var accent = [
            /[\300-\306]/g, /[\340-\346]/g, // A, a
            /[\310-\313]/g, /[\350-\353]/g, // E, e
            /[\314-\317]/g, /[\354-\357]/g, // I, i
            /[\322-\330]/g, /[\362-\370]/g, // O, o
            /[\331-\334]/g, /[\371-\374]/g, // U, u
            /[\321]/g, /[\361]/g, // N, n
            /[\307]/g, /[\347]/g, // C, c
        ],
        noaccent = ['A','a','E','e','I','i','O','o','U','u','N','n','C','c'];

        for (var i = 0; i < accent.length; i++){
            source = source.replace(accent[i], noaccent[i]);
        }

        return source;
    };
})
.filter('lengthObj', function() {
	return function(object) {
		return Object.keys(object).length;
	}
})
.directive('uploaderModel',["$parse", function($parse){
	return {
		restrict:'A',
		link:function(scope, iElement, iAttrs){
			iElement.on("change", function(e){
					$parse(iAttrs.uploaderModel).assign(scope, iElement[0].files[0]);
			});
		}
	};
}])

// VISTAS GENERALES
.directive('viewHome',function(){
    return {templateUrl:pathMain+'home.html' };
})
// VISTAS ACCESOS DIRECTOS EN FORMULARIOS
.directive('viewMenu',function(){
    return {templateUrl:pathMenu+'menu.html' };
})
.directive('menuSingle',function(){
    return {templateUrl:pathMenu+'menuSingle.html'};
})
.directive('menuParts',function(){
	return {templateUrl:pathMenu+'menuParts.html'};
})
//DATATABLE - NODEJS SEQUELIZE
.directive('datatableToolbar',function(){
 return {templateUrl:pathIncApp+'main/datatableToolbar.html' };
})
.directive('datatableFooter',function(){
 return {templateUrl:pathIncApp+'main/datatableFooter.html' };
})
// TOOLBAR
.directive('toolbarFilterInTab',function(){
	return {templateUrl:pathInc+'toolbarFilterInTab.html' };
})
.directive('toolbarFilter',function(){
    return {templateUrl:pathInc+'toolbarFilter.html' };
})
.directive('footerTable',function(){
    return {templateUrl:pathInc+'footerTable.html' };
})
// DIRECTIAS -> USUARIOS
.directive('cardPerson',function(){
	return {templateUrl:pathInc+'cardPerson.html'};
})
.directive('cardPersonInformation',function(){
    return {templateUrl:pathInc+'cardPersonInformation.html'};
})
.directive('cardProfile',function(){
	return {templateUrl:pathInc+'cardProfile.html'};
})
.directive('cardJob',function(){
	return {templateUrl:pathInc+'cardJob.html'};
})
.directive('cardStation',function(){
	return {templateUrl:pathIncApp+'tthh/cardStation.html'};
})
.directive('cardUnit',function(){
	return {templateUrl:pathIncApp+'logistics/cardUnit.html'};
})
.directive('cardUnitInformation',function(){
	return {templateUrl:pathIncApp+'logistics/cardUnitInformation.html'};
})
;