<?php namespace controller;

interface crudController {
	
	// **********************************************************************************************
	// **************************** MÉTODO MAIN PARA INSTANCIA DE MÉTODOS ***************************
	// **********************************************************************************************
	public function executeQuery($opList);
	
	
	// **********************************************************************************************
	// ********************************* MÉTODO PARA EXTRAER REGSTROS *******************************
	// **********************************************************************************************
	public function GET();
	
	
	// **********************************************************************************************
	// ******************************* MÉTODO PARA EL INGRESO DE DATOS ******************************
	// **********************************************************************************************
	public function POST();
	
	
	// **********************************************************************************************
	// ********************************* MÉTODO PARA EDITAR REGISTROS *******************************
	// **********************************************************************************************
	public function PUT();
	
}