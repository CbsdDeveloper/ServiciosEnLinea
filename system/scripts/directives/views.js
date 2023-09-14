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
.directive('navUriText',function(){
    return {
    	template:'<a class="nav-link" ui-sref-active="active" ui-sref={{uri}}><i class="material-icons nav-icon {{c}}">{{i}}</i><p><span translate>{{lbl}}</span></p></a>',
    	scope:{
    		i:"@",
    		c:"@",
    		lbl:"@",
    		uri:"@"
    	}
    };
})
.directive('navIconText',function(){
	return {
		template:'<i class="material-icons nav-icon {{c}}">{{i}}</i><p><span translate>{{lbl}}</span></p>',
		scope:{
			i:"@",
			c:"@",
			lbl:"@"
		}
	};
})
.directive('navTextTreeView',function(){
    return {
    	template:'<i class="material-icons nav-icon {{c}}">{{i}}</i> <p><span translate>{{lbl}}</span><i class="fa fa-angle-left right"></i></p>',
    	scope:{
    		i:"@",
    		c:"@",
    		lbl:"@"
    	}
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

// VISTAS GENERALES
.directive('viewHome',function(){
    return {templateUrl:pathUser+'home.html' };
})
// VISTAS ACCESOS DIRECTOS EN FORMULARIOS
.directive('viewMenu',function(){
    return {templateUrl:pathMenu+'menu.html' };
})
.directive('menuMiniDatatable',function(){
    return {templateUrl:pathMenu+'menuMiniDatatable.html'};
})
.directive('menuMini',function(){
	return {templateUrl:pathMenu+'menuMini.html'};
})
.directive('menuSingle',function(){
	return {templateUrl:pathMenu+'menuSingle.html'};
})
.directive('menuReports',function(){
	return {templateUrl:pathMenu+'menuReports.html'};
})
// ADMIN
.directive('menuSurveyForms',function(){
	return {templateUrl:pathMenu+'menuSurveyForms.html'};
})
.directive('menuSurveyFormsSections',function(){
	return {templateUrl:pathMenu+'menuSurveyFormsSections.html'};
})
// TALENTO HUMANO
.directive('menuPersons',function(){
    return {templateUrl:pathMenu+'menuPersons.html'};
})
.directive('menuHolidays',function(){
    return {templateUrl:pathMenu+'menuHolidays.html'};
})
.directive('menuTypeactions',function(){
    return {templateUrl:pathMenu+'menuTypeactions.html'};
})
.directive('menuStations',function(){
    return {templateUrl:pathMenu+'menuStations.html'};
})
.directive('menuLeaderships',function(){
	return {templateUrl:pathMenu+'menuLeaderships.html'};
})
.directive('menuJobs',function(){
	return {templateUrl:pathMenu+'menuJobs.html'};
})
.directive('menuDistributions',function(){
	return {templateUrl:pathMenu+'menuDistributions.html'};
})
.directive('menuStaff',function(){
	return {templateUrl:pathMenu+'menuStaff.html'};
})
.directive('menuBiometricPeriods',function(){
	return {templateUrl:pathMenu+'menuBiometricPeriods.html'};
})
.directive('menuRescueHistory',function(){
	return {templateUrl:pathMenu+'menuRescueHistory.html'};
})
.directive('menuRescueEquipments',function(){
	return {templateUrl:pathMenu+'menuRescueEquipments.html'};
})
.directive('menuDamageForms',function(){
	return {templateUrl:pathMenu+'menuDamageForms.html'};
})
.directive('menuInspectionFextinguisher',function(){
	return {templateUrl:pathMenu+'menuInspectionFextinguisher.html'};
})
.directive('menuMedicines',function(){
	return {templateUrl:pathMenu+'menuMedicines.html'};
})
.directive('menuMedicalHistory',function(){
	return {templateUrl:pathMenu+'menuMedicalHistory.html'};
})
.directive('menuMedicalConsultation',function(){
	return {templateUrl:pathMenu+'menuMedicalConsultation.html'};
})
.directive('menuMedicalrest',function(){
	return {templateUrl:pathMenu+'menuMedicalrest.html'};
})
.directive('menuAbsences',function(){
	return {templateUrl:pathMenu+'menuAbsences.html'};
})
.directive('menuSupplycontrol',function(){
	return {templateUrl:pathMenu+'menuSupplycontrol.html'};
})
.directive('menuSupplyStationsControl',function(){
	return {templateUrl:pathMenu+'menuSupplyStationsControl.html'};
})
.directive('menuFilters',function(){
	return {templateUrl:pathMenu+'menuFilters.html'};
})
.directive('menuSurveyEvaluations',function(){
	return {templateUrl:pathMenu+'menuSurveyEvaluations.html'};
})
.directive('menuPsychosocialForms',function(){
	return {templateUrl:pathMenu+'menuPsychosocialForms.html'};
})
.directive('menuPsychosocialFormsSections',function(){
	return {templateUrl:pathMenu+'menuPsychosocialFormsSections.html'};
})
.directive('menuPsychosocialEvaluations',function(){
	return {templateUrl:pathMenu+'menuPsychosocialEvaluations.html'};
})
.directive('menuStaffActions',function(){
	return {templateUrl:pathMenu+'menuStaffActions.html'};
})
.directive('menuDelegations',function(){
	return {templateUrl:pathMenu+'menuDelegations.html'};
})
.directive('menuVacations',function(){
	return {templateUrl:pathMenu+'menuVacations.html'};
})
.directive('menuRequestedVacations',function(){
	return {templateUrl:pathMenu+'menuRequestedVacations.html'};
})
.directive('menuRequestedPermissions',function(){
	return {templateUrl:pathMenu+'menuRequestedPermissions.html'};
})
.directive('menuAdvances',function(){
	return {templateUrl:pathMenu+'menuAdvances.html'};
})
.directive('menuRoadmap',function(){
	return {templateUrl:pathMenu+'menuRoadmap.html'};
})
.directive('menuDailyActivities',function(){
	return {templateUrl:pathMenu+'menuDailyActivities.html'};
})
.directive('menuCandidates',function(){
	return {templateUrl:pathMenu+'menuCandidates.html'};
})
// ADMINISTRACIÓN
.directive('menuComplaints',function(){
    return {templateUrl:pathMenu+'menuComplaints.html'};
})
// ADMINISTRACIÓN
.directive('menuProfiles',function(){
    return {templateUrl:pathMenu+'menuProfiles.html'};
})
.directive('menuUsers',function(){
	return {templateUrl:pathMenu+'menuUsers.html'};
})
.directive('menuModules',function(){
	return {templateUrl:pathMenu+'menuModules.html'};
})
.directive('menuTrainingroom',function(){
	return {templateUrl:pathMenu+'menuTrainingroom.html'};
})
.directive('menuFuelorder',function(){
	return {templateUrl:pathMenu+'menuFuelorder.html'};
})
.directive('menuUnits',function(){
	return {templateUrl:pathMenu+'menuUnits.html'};
})
.directive('menuPlanAbc',function(){
	return {templateUrl:pathMenu+'menuPlanAbc.html'};
})
// DIRECCIÓN ADMINISTRATIVA - DOCUENTACION ELECTRONICA
.directive('menuEdoc',function(){
	return {templateUrl:pathMenu+'menuEdoc.html'};
})
// DIRECCIÓN ADMINISTRATIVA - ARCHIVO
.directive('menuArchivePeriods',function(){
	return {templateUrl:pathMenu+'menuArchivePeriods.html'};
})
.directive('menuArchiveReviews',function(){
	return {templateUrl:pathMenu+'menuArchiveReviews.html'};
})

// SEGUNDA JEFATURA
.directive('menuTracking',function(){
	return {templateUrl:pathMenu+'menuTracking.html'};
})
.directive('menuSupplying',function(){
	return {templateUrl:pathMenu+'menuSupplying.html'};
})
.directive('menuMaintenances',function(){
	return {templateUrl:pathMenu+'menuMaintenances.html'};
})
.directive('menuMinorMaintenances',function(){
	return {templateUrl:pathMenu+'menuMinorMaintenances.html'};
})
.directive('menuMaintenanceorder',function(){
	return {templateUrl:pathMenu+'menuMaintenanceorder.html'};
})
.directive('menuMobilizationorder',function(){
	return {templateUrl:pathMenu+'menuMobilizationorder.html'};
})
.directive('menuReportMaintenance',function(){
	return {templateUrl:pathMenu+'menuReportMaintenance.html'};
})
.directive('menuWorkorder',function(){
	return {templateUrl:pathMenu+'menuWorkorder.html'};
})
.directive('menuNature',function(){
	return {templateUrl:pathMenu+'menuNature.html'};
})
.directive('menuParts',function(){
	return {templateUrl:pathMenu+'menuParts.html'};
})
// PERMISOS DE FUNCIONAMIENTO
.directive('menuActivities',function(){
    return {templateUrl:pathMenu+'menuActivities.html'};
})
.directive('menuCiiu',function(){
    return {templateUrl:pathMenu+'menuCiiu.html'};
})
.directive('menuTaxes',function(){
    return {templateUrl:pathMenu+'menuTaxes.html'};
})
.directive('menuForms',function(){
    return {templateUrl:pathMenu+'menuForms.html'};
})
.directive('menuRequirements',function(){
    return {templateUrl:pathMenu+'menuRequirements.html'};
})
.directive('menuEntities',function(){
    return {templateUrl:pathMenu+'menuEntities.html'};
})
.directive('menuLocals',function(){
    return {templateUrl:pathMenu+'menuLocals.html'};
})
.directive('menuSelfInspections',function(){
    return {templateUrl:pathMenu+'menuSelfInspections.html'};
})
.directive('menuPermits',function(){
    return {templateUrl:pathMenu+'menuPermits.html'};
})
// PREVENCION
.directive('menuInspections',function(){
    return {templateUrl:pathMenu+'menuInspections.html'};
})
.directive('menuExtensions',function(){
	return {templateUrl:pathMenu+'menuExtensions.html'};
})
.directive('menuVbp',function(){
    return {templateUrl:pathMenu+'menuVbp.html'};
})
.directive('menuHabitability',function(){
	return {templateUrl:pathMenu+'menuHabitability.html'};
})
.directive('menuModification',function(){
	return {templateUrl:pathMenu+'menuModification.html'};
})
.directive('menuFeasibility',function(){
	return {templateUrl:pathMenu+'menuFeasibility.html'};
})
.directive('menuDefinitive',function(){
	return {templateUrl:pathMenu+'menuDefinitive.html'};
})
.directive('menuTransporteglp',function(){
    return {templateUrl:pathMenu+'menuTransporteglp.html'};
})
.directive('menuPlans',function(){
	return {templateUrl:pathMenu+'menuPlans.html'};
})
.directive('menuTopics',function(){
	return {templateUrl:pathMenu+'menuTopics.html'};
})
.directive('menuTrainings',function(){
	return {templateUrl:pathMenu+'menuTrainings.html'};
})
.directive('menuDetailTrainings',function(){
	return {templateUrl:pathMenu+'menuDetailTrainings.html'};
})
.directive('menuSimulations',function(){
	return {templateUrl:pathMenu+'menuSimulations.html'};
})
.directive('menuDetailSimulations',function(){
	return {templateUrl:pathMenu+'menuDetailSimulations.html'};
})
.directive('menuStands',function(){
	return {templateUrl:pathMenu+'menuStands.html'};
})
.directive('menuDetailStands',function(){
	return {templateUrl:pathMenu+'menuDetailStands.html'};
})
.directive('menuVisits',function(){
	return {templateUrl:pathMenu+'menuVisits.html'};
})
.directive('menuDetailVisits',function(){
	return {templateUrl:pathMenu+'menuDetailVisits.html'};
})
.directive('menuOccasionals',function(){
	return {templateUrl:pathMenu+'menuOccasionals.html'};
})
// RECAUDACIÓN
.directive('menuInvoices',function(){
	return {templateUrl:pathMenu+'menuInvoices.html'};
})
.directive('menuSpecies',function(){
	return {templateUrl:pathMenu+'menuSpecies.html'};
})
.directive('menuArching',function(){
	return {templateUrl:pathMenu+'menuArching.html'};
})
// DIRECCION FINANCIERA - CONTROL PREVIO
.directive('menuContractingProcedures',function(){
	return {templateUrl:pathMenu+'menuContractingProcedures.html'};
})
.directive('menuJustificationRequirements',function(){
	return {templateUrl:pathMenu+'menuJustificationRequirements.html'};
})
//DIRECCION DE PLANIFICACION
.directive('menuPoa',function(){
	return {templateUrl:pathMenu+'menuPoa.html'};
})

// DATATABLE - NODEJS SEQUELIZE
.directive('datatableToolbar',function(){
    return {templateUrl:pathInc+'datatableToolbar.html' };
})
.directive('datatableFooter',function(){
    return {templateUrl:pathInc+'datatableFooter.html' };
})

// TEMPLATES
.directive('toolbarFilterInTab',function(){
	return {templateUrl:pathInc+'toolbarFilterInTab.html' };
})
.directive('toolbarFilter',function(){
    return {templateUrl:pathInc+'toolbarFilter.html' };
})
.directive('footerTable',function(){
    return {templateUrl:pathInc+'footerTable.html' };
})
.directive('frmLocationCountries',function(){
	return {templateUrl:pathInc+'frmLocationCountries.html'};
})
.directive('frmLocationStates',function(){
	return {templateUrl:pathInc+'frmLocationStates.html'};
})
.directive('frmLocationTowns',function(){
	return {templateUrl:pathInc+'frmLocationTowns.html'};
})
.directive('frmPersonasCustom',function(){
	return {templateUrl:pathInc+'frmPersonasCustom.html'};
})
.directive('frmPersonasSingle',function(){
	return {templateUrl:pathInc+'frmPersonasSingle.html'};
})
.directive('frmPersonasBasic',function(){
	return {templateUrl:pathInc+'frmPersonasBasic.html'};
})
.directive('frmPersonas',function(){
    return {templateUrl:pathInc+'frmPersonas.html'};
})
.directive('frmPersonasAdvanced',function(){
	return {templateUrl:pathInc+'frmPersonasAdvanced.html'};
})
.directive('frmVehiculos',function(){
    return {templateUrl:pathInc+'frmVehiculos.html'};
})
.directive('frmVehiculosAdvanced',function(){
	return {templateUrl:pathInc+'frmVehiculosAdvanced.html'};
})
.directive('frmVehiculosSingle',function(){
	return {templateUrl:pathInc+'frmVehiculosSingle.html'};
})
.directive('frmLocals',function(){
    return {templateUrl:pathInc+'frmLocals.html'};
})
// DIRECTIAS -> ADMIN
.directive('cardSurveyForms',function(){
	return {templateUrl:pathInc+'admin/resources/cardSurveyForms.html'};
})
.directive('cardSurveyFormSection',function(){
	return {templateUrl:pathInc+'admin/resources/cardSurveyFormSection.html'};
})

// DIRECTIAS -> TALENTO HUMANO
.directive('cardJobInformation',function(){
	return {templateUrl:pathInc+'cardJobInformation.html'};
})
.directive('cardStation',function(){
	return {templateUrl:pathInc+'cardStation.html'};
})
.directive('cardStationInformation',function(){
	return {templateUrl:pathInc+'cardStationInformation.html'};
})
.directive('cardStaff',function(){
	return {templateUrl:pathInc+'cardStaff.html'};
})
.directive('cardStaffInformation',function(){
	return {templateUrl:pathInc+'cardStaffInformation.html'};
})
.directive('cardStaffInformationAlt',function(){
	return {templateUrl:pathInc+'tthh/cardStaffInformationAlt.html'};
})
.directive('cardMedicalHistory',function(){
	return {templateUrl:pathInc+'cardMedicalHistory.html'};
})
.directive('cardPlatoon',function(){
	return {templateUrl:pathInc+'cardPlatoon.html'};
})
.directive('cardPlatoonInformation',function(){
	return {templateUrl:pathInc+'cardPlatoonInformation.html'};
})
// DIRECTIAS -> USUARIOS
.directive('cardReportInformation',function(){
	return {templateUrl:pathInc+'cardReportInformation.html'};
})
.directive('cardUserInformation',function(){
	return {templateUrl:pathInc+'cardUserInformation.html'};
})
.directive('cardPerson',function(){
	return {templateUrl:pathInc+'cardPerson.html'};
})
.directive('cardPersonInformation',function(){
    return {templateUrl:pathInc+'cardPersonInformation.html'};
})
.directive('cardProfile',function(){
	return {templateUrl:pathInc+'cardProfile.html'};
})
//DIRECTIAS -> FLOTAS
.directive('cardVehicle',function(){
	return {templateUrl:pathInc+'cardVehicle.html'};
})
.directive('cardVehicleInformation',function(){
	return {templateUrl:pathInc+'cardVehicleInformation.html'};
})
.directive('cardUnit',function(){
	return {templateUrl:pathInc+'cardUnit.html'};
})
.directive('cardUnitInformation',function(){
	return {templateUrl:pathInc+'cardUnitInformation.html'};
})
.directive('cardTracking',function(){
	return {templateUrl:pathInc+'cardTracking.html'};
})
.directive('cardMaintenanceorder',function(){
	return {templateUrl:pathInc+'cardMaintenanceorder.html'};
})

//DIRECTIAS -> PARTES
.directive('cardPart',function(){
	return {templateUrl:pathInc+'cardPart.html'};
})

//DIRECTIAS -> PERMISOS DE FUNCIONAMIENTO
.directive('cardEntity',function(){
	return {templateUrl:pathInc+'permits/cardEntity.html'};
})
.directive('cardEntityInformation',function(){
    return {templateUrl:pathInc+'permits/cardEntityInformation.html'};
})
.directive('cardAgent',function(){
	return {templateUrl:pathInc+'permits/cardAgent.html' };
})
.directive('cardAdopted',function(){
	return {templateUrl:pathInc+'permits/cardAdopted.html' };
})
.directive('cardLocal',function(){
	return {templateUrl:pathInc+'permits/cardLocal.html'};
})
.directive('cardLocalInformation',function(){
    return {templateUrl:pathInc+'permits/cardLocalInformation.html'};
})

.directive('cardActivity',function(){
	return {templateUrl:pathInc+'cardActivity.html'};
})
.directive('cardCiiu',function(){
	return {templateUrl:pathInc+'cardCiiu.html'};
})
.directive('cardForm',function(){
	return {templateUrl:pathInc+'cardForm.html'};
})
.directive('cardPermit',function(){
	return {templateUrl:pathInc+'cardPermit.html'};
})
.directive('cardPermitInformation',function(){
    return {templateUrl:pathInc+'cardPermitInformation.html'};
})
.directive('cardInspection',function(){
	return {templateUrl:pathInc+'cardInspection.html'};
})
.directive('cardInspectionInformation',function(){
	return {templateUrl:pathInc+'cardInspectionInformation.html'};
})
.directive('cardExtension',function(){
	return {templateUrl:pathInc+'cardExtension.html'};
})
.directive('cardExtensionInformation',function(){
	return {templateUrl:pathInc+'cardExtensionInformation.html'};
})
.directive('cardCitation',function(){
	return {templateUrl:pathInc+'cardCitation.html'};
})
.directive('cardPlan',function(){
    return {templateUrl:pathInc+'cardPlan.html'};
})
.directive('cardPlanInformation',function(){
	return {templateUrl:pathInc+'cardPlanInformation.html'};
})
// DIRECTIAS -> VISTO BUENO DE PLANOS
.directive('cardPeople',function(){
	return {templateUrl:pathIncApp+'system/cardPeople.html' };
})
.directive('cardVbp',function(){
	return {templateUrl:pathIncApp+'prevention/cardVbp.html' };
})
.directive('cardVbpInformation',function(){
    return {templateUrl:pathInc+'cardVbpInformation.html'};
})
.directive('cardFeasibility',function(){
	return {templateUrl:pathIncApp+'prevention/cardFeasibility.html' };
})
.directive('cardFeasibilityInformation',function(){
	return {templateUrl:pathInc+'cardFeasibilityInformation.html'};
})
.directive('cardDefinitive',function(){
	return {templateUrl:pathIncApp+'prevention/cardDefinitive.html' };
})
.directive('cardDefinitiveInformation',function(){
	return {templateUrl:pathInc+'cardDefinitiveInformation.html'};
})
.directive('cardHabitability',function(){
	return {templateUrl:pathIncApp+'prevention/cardHabitability.html' };
})
.directive('cardHabitabilityInformation',function(){
	return {templateUrl:pathInc+'cardHabitabilityInformation.html'};
})
.directive('cardModification',function(){
	return {templateUrl:pathIncApp+'prevention/cardModification.html' };
})
.directive('cardModificationInformation',function(){
	return {templateUrl:pathInc+'cardModificationInformation.html'};
})
// DIRECTIAS -> PERMISO DE TRANSPORTE DE COMBUSTIBLE
.directive('cardGlpTransport',function(){
	return {templateUrl:pathInc+'cardGlpTransport.html'};
})
.directive('cardGlpTransportInformation',function(){
	return {templateUrl:pathInc+'cardGlpTransportInformation.html'};
})
// DIRECTIAS -> CAPACITACIONES
.directive('cardTraining',function(){
	return {templateUrl:pathIncApp+'prevention/cardTraining.html'};
})
.directive('cardTrainingInformation',function(){
	return {templateUrl:pathInc+'cardTrainingInformation.html'};
})
// DIRECTIAS -> SIMULACROS
.directive('cardSimulation',function(){
	return {templateUrl:pathIncApp+'prevention/cardSimulation.html'};
})
.directive('cardSimulationInformation',function(){
	return {templateUrl:pathInc+'cardSimulationInformation.html'};
})
// DIRECTIAS -> CASAS ABIERTAS
.directive('cardStand',function(){
	return { templateUrl:pathIncApp+'prevention/cardStand.html' };
})
.directive('cardStandInformation',function(){
	return {templateUrl:pathInc+'cardStandInformation.html'};
})
// DIRECTIAS -> VISITAS
.directive('cardVisit',function(){
	return {templateUrl:pathIncApp+'prevention/cardVisit.html'};
})
.directive('cardVisitInformation',function(){
	return {templateUrl:pathInc+'cardVisitInformation.html'};
})
// DIRECTIAS -> PERMISOS OCASIONALES
.directive('cardProject',function(){
	return { templateUrl:pathIncApp+'prevention/cardProject.html' };
})
// DIRECTIAS -> PERMISOS OCASIONALES
.directive('cardOccasional',function(){
	return { templateUrl:pathIncApp+'prevention/cardOccasional.html' };
})
.directive('cardOccasionalInformation',function(){
	return {templateUrl:pathInc+'cardOccasionalInformation.html'};
})
.directive('cardAdvanceInformation',function(){
	return {templateUrl:pathInc+'cardAdvanceInformation.html' };
})

// DIRECCION DE PLANIFICACION -> POA
.directive('cardPoa',function(){
	return {templateUrl:pathInc+'cardPoa.html'};
})

// DIRECTIAS -> DIRECCION GENERAL 
.directive('cardComplaintInformation',function(){
	return {templateUrl:pathInc+'cardComplaintInformation.html'};
})

// SEGURIDAD Y SALUD OCUPACIONAL
.directive('cardPsychosocialForm',function(){
	return {templateUrl:pathInc+'cardPsychosocialForm.html'};
})
.directive('cardPsychosocialFormSection',function(){
	return {templateUrl:pathInc+'cardPsychosocialFormSection.html'};
})
.directive('cardPsychosocialEvaluation',function(){
	return {templateUrl:pathInc+'cardPsychosocialEvaluation.html'};
})

;