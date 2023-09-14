<?php namespace api;
/**
 * Clase que implementa un coversor de números
 * a letras.
 *
 * Soporte para PHP >= 5.4
 * Para soportar PHP 5.3, declare los arreglos
 * con la función array.
 *
 * @author AxiaCore S.A.S
 *
 */
class numberToOrdinal {
	
    private static $UNIDADES = [
        "", 
        "PRIMERO", 
        "SEGUNDO", 
        "TERCERO",
        "CUARTO", 
        "QUINTO", 
        "SEXTO",
        "SEPTIMO", 
        "OCTAVO", 
        "NOVENO"
    ];
	
	private static $DECENAS = [
		'DECIMO',
		'VIGESIMO ',
		'TRIGESIMO ',
		'CUADRAGESIMO ',
		'QUINCUAGESIMO ',
		'SEXAGESIMO ',
		'SEPTUAGESIMA ',
		'OCTOGESIMO ',
		'NONAGESIMO '
	];
	
	private static $CENTENAS = [
		'CENTESIMO ',
		'DUCENTESIMO ',
		'TRICENTESIMO ',
		'CUADRIGENTESIMO ',
		'QUINGENTESIMO ',
		'SEXCENTESIMO ',
		'SEPTINGENTESIMO ',
		'OCTINGENTESIMO ',
		'NONINGENTESIMO '
	];
	
	public static function convertir($number){
	    
	    // MODELO DE NUMERO
	    $u= $number % 10;
	    $d = intval(round( $number / 10)) % 10;
	    $c = intval(round( $number / 100));
	    
	    // VARIABLE DE RETORNO 
	    $str = "";
	    
	    // VALIDAR NUMERO
	    if($number >= 100){ 
	        
	        $str = self::$CENTENAS[$c]." ".self::$DECENAS[$d]." ".self::$UNIDADES[$u];
	        
	    } else {
	        
	        if($number >= 10){ $str = self::$DECENAS[$d]." ".self::$UNIDADES[$u]; }
	        else $str = self::$UNIDADES[$u];
	        
	    }
	    
	    
	    // RETORNAR SALIDA
		return $str;
	}
	
}