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
    return {template:'<i class="fa fa-{{i}} fa-{{s}} {{c}}"></i>',scope:{i:"@",s:"@",c:"@" } };
})
.directive('iError',function(){
    return {
    	template:'<span class="help-block"><i-fa i=times-circle-o s=fw></i-fa>{{t}}</span>',
    	scope:{t:"@" }
    };
})
/*
 * VALIDACION DE CAMPOS DE FORMULARIOS
 */
.directive('fieldValidation', function ( $compile ) {
	return {
		scope: { field:'=', min:"@", max:"@", minlgth:"@", maxlgth:"@", mail:"@" },
		templateUrl: pathInc+'frmFieldValidation.html'
	};
})
/* *** Material Ícon ***
 * @i ícono
 * @s size 
 * @c color
 * @d drección
 */
.directive('mdIco',function(){
    return {template:'<i class="material-icons md-{{s}} {{c}} {{d}}">{{i}}</i>',scope:{i:"@",s:"@",c:"@",d:"@" } };
})
.directive('dateInput',function(){
    return {
        restrict:'A',
        scope:{ngModel:'='},
        link:function(scope){
            if(scope.ngModel)scope.ngModel=new Date(scope.ngModel);
        }
    }
})
.directive('parseFloat',function() {
  return {
	 priority:1,
	 restrict:'A',
	 require:'ngModel',
	 link:function(scope,element,attr,ngModel) {
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
.factory('focus',function($timeout,$window){
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
.filter('removeAcentos',function(){
    return function (source) {
        if(!angular.isDefined(source)){return; }
        var accent = [
            /[\300-\306]/g,/[\340-\346]/g,// A,a
            /[\310-\313]/g,/[\350-\353]/g,// E,e
            /[\314-\317]/g,/[\354-\357]/g,// I,i
            /[\322-\330]/g,/[\362-\370]/g,// O,o
            /[\331-\334]/g,/[\371-\374]/g,// U,u
            /[\321]/g,/[\361]/g,// N,n
            /[\307]/g,/[\347]/g,// C,c
        ],
        noaccent = ['A','a','E','e','I','i','O','o','U','u','N','n','C','c'];

        for (var i = 0; i < accent.length; i++){
            source = source.replace(accent[i],noaccent[i]);
        }

        return source;
    };
})
.filter('lengthObj', function() {
	return function(object) {
		return Object.keys(object).length;
	}
})
.directive('uploaderModel',["$parse",function($parse){
	return {
		restrict:'A',
		link:function(scope,iElement,iAttrs){
			iElement.on("change",function(e){
					$parse(iAttrs.uploaderModel).assign(scope,iElement[0].files[0]);
			});
		}
	};
}])
.directive('ngFile',['$parse',function ($parse) {
	return {
		restrict: 'A',
		link: function(scope,element,attrs) {
			element.bind('change',function(){
				$parse(attrs.ngFile).assign(scope,element[0].files)
				scope.$apply();
			});
		}
	};
}])
.directive('ngFileModel',['$parse',function ($parse) {
    return {
        restrict: 'A',
        link: function (scope,element,attrs) {
            var model = $parse(attrs.ngFileModel);
            var isMultiple = attrs.multiple;
            var modelSetter = model.assign;
            element.bind('change',function () {
                var values = [];
                angular.forEach(element[0].files,function (item) {
                    var value = {
                       // File Name 
                        name: item.name,
                        //File Size 
                        size: item.size,
                        //File URL to view 
                        url: URL.createObjectURL(item),
                        // File Input Value 
                        _file: item
                    };
                    values.push(value);
                });
                scope.$apply(function () {
                    if (isMultiple) {
                        modelSetter(scope,values);
                    } else {
                        modelSetter(scope,values[0]);
                    }
                });
            });
        }
    };
}])
.directive('draggable', function($document) {
  return function(scope, element, attr) {
    var startX = 0,
      startY = 0,
      x = 0,
      y = 0; 
      var container = null; 
      
    element.css({
      position: 'relative',
      cursor: 'pointer'
    });
    
    element.on('mousedown', function(event) {
      // Prevent default dragging of selected content
      event.preventDefault();
      startX = event.screenX - x;
      startY = event.screenY - y;
      $document.on('mousemove', mousemove);
      $document.on('mouseup', mouseup); 
      container = attr.$$element.parent();
      /*console.log('container',container);*/
    });

    function mousemove(event) {
      y = event.screenY - startY;
      x = event.screenX - startX;
      /*console.log("x: " + x + " y: " + y)*/
      container.css({
        top: y + 'px',
        left: x + 'px'
      });
    }

    function mouseup() {
      $document.unbind('mousemove', mousemove);
      $document.unbind('mouseup', mouseup);
    }
  }
})

/*
 * VISTAS GENERALES
 */
.directive('viewHome',function(){
    return {templateUrl:pathMain+'home.html' };
})



/*
 * MENUS - GENERAL
 */ 
.directive('viewMenu',function(){
    return {templateUrl:pathMenu+'menu.html' };
})
.directive('menuMini',function(){
    return {templateUrl:pathMenu+'menuMini.html'};
})
.directive('menuSingle',function(){
	return {templateUrl:pathMenu+'menuSingle.html'};
})



/*
 * MENU - ENTIDADES
 */
.directive('menuEconomicActivities',function(){
	return {templateUrl:pathMenu+'menuEconomicActivities.html'};
})
// PERMISOS DE FUNCIONAMIENTO
.directive('menuSelfProtectionPlans',function(){
	return {templateUrl:pathMenu+'menuSelfProtectionPlans.html'};
})
.directive('menuDetailSelfProtection',function(){
	return {templateUrl:pathMenu+'menuDetailSelfProtection.html'};
})
.directive('menuGlpTransport',function(){
	return {templateUrl:pathMenu+'menuGlpTransport.html'};
})
.directive('menuOccasionals',function(){
	return {templateUrl:pathMenu+'menuOccasionals.html'};
})
// PERMISOS DE CONSTRUCCION
.directive('menuVbp',function(){
	return {templateUrl:pathMenu+'menuVbp.html'};
})
.directive('menuModification',function(){
	return {templateUrl:pathMenu+'menuModification.html'};
})
.directive('menuHabitability',function(){
	return {templateUrl:pathMenu+'menuHabitability.html'};
})
// USO DE GLP
.directive('menuFeasibility',function(){
	return {templateUrl:pathMenu+'menuFeasibility.html'};
})
.directive('menuDefinitive',function(){
	return {templateUrl:pathMenu+'menuDefinitive.html'};
})



/*
 * CARDS
 */
.directive('cardEntity',function(){
	return {templateUrl:pathIncApp+'main/cardEntity.html'};
})
.directive('cardEntityComplete',function(){
	return {templateUrl:pathIncApp+'main/cardEntityComplete.html'};
})
.directive('cardLocal',function(){
	return {templateUrl:pathIncApp+'main/cardLocal.html'};
})
.directive('cardAgent',function(){
	return {templateUrl:pathIncApp+'main/cardAgent.html'};
})
.directive('cardAdopted',function(){
	return {templateUrl:pathIncApp+'main/cardAdopted.html'};
})
.directive('cardLocalInformation',function(){
	return {templateUrl:pathInc+'cardLocalInformation.html'};
})
.directive('cardVehicleglpInformation',function(){
	return {templateUrl:pathInc+'cardVehicleglpInformation.html'};
})
// CAPACITACIONES CIUDADANAS
.directive('cardTraining',function(){
	return {templateUrl:pathIncApp+'prevention/cardTraining.html'};
})
.directive('cardTrainingInformation',function(){
	return {templateUrl:pathIncApp+'prevention/cardTrainingInformation.html'};
})



/*
 * FORMULARIOS
 */
.directive('frmEconomicActivities',function(){
	return {templateUrl:pathInc+'autoInspeccion/frmEconomicActivities.html'};
})
.directive('frmSIFloor',function(){
	return {templateUrl:pathInc+'autoInspeccion/frmSIFloor.html'};
})
.directive('frmSIAforo',function(){
	return {templateUrl:pathInc+'autoInspeccion/frmSIAforo.html'};
})
.directive('frmPersonasSingle',function(){
	return {templateUrl:pathIncApp+'main/frmPersonasSingle.html'};
})
.directive('frmPersonasBasic',function(){
	return {templateUrl:pathIncApp+'main/frmPersonasBasic.html'};
})


/*
 * TABLA DE DATOS
 */
.directive('toolbarFilterInTab',function(){
	return {templateUrl:pathIncApp+'main/toolbarFilterInTab.html' };
})
.directive('toolbarFilter',function(){
    return {templateUrl:pathIncApp+'main/toolbarFilter.html' };
})
.directive('footerTable',function(){
    return {templateUrl:pathIncApp+'main/footerTable.html' };
})

;