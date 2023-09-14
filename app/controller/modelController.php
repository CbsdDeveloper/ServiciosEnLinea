<?php namespace controller;

interface modelController {
	
	/*
	 * INGRESO DE NUEVOS REGISTROS
	 */
	public function insertEntity();
	
	
	/*
	 * MODIFICACIÓN DE REGISTROS
	 */
	public function updateEntity();
	
	
	/*
	 * CONSULTA DE REGISTROS
	 */
	public function requestEntity();
	
}