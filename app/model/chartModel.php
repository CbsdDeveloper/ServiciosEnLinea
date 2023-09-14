<?php namespace model;

class chartModel {

	public static function getModel($labels=array(),$datasets=array()){
		return array(
			'labels'=>$labels,
			'datasets'=>$datasets
		);
	} 
	
	public static function barChart($label='Test',$backgroundColor=array(),$borderColor=array(),$data=array()){
		return array(
			'label'=>$label,
			'backgroundColor'=>$backgroundColor,
			'borderColor'=>$borderColor,
			'borderWidth'=>1,
			'data'=>$data
		);
	}

	public static function lineChart($label='Test',$colorRGB='0,0,0',$data=array()){
		return array(
			'label'=>$label,
			'fill'=>false,
			'lineTension'=>'0.1',
			'backgroundColor'=>"rgba($colorRGB,0.4)",
			'borderColor'=>"rgba($colorRGB,1)",
			'borderCapStyle'=>'butt',
			'borderDash'=>[],
			'borderDashOffset'=>'0.0',
			'borderJoinStyle'=>'miter',
			'pointBorderColor'=>"rgba($colorRGB,1)",
			'pointBackgroundColor'=>"#fff",
			'pointBorderWidth'=>1,
			'pointHoverRadius'=>5,
			'pointHoverBackgroundColor'=>"rgba($colorRGB,1)",
			'pointHoverBorderColor'=>"rgba(220,220,220,1)",
			'pointHoverBorderWidth'=>2,
			'pointRadius'=>1,
			'pointHitRadius'=>10,
			'data'=>$data,
			'spanGaps'=>false
		);
	}

	public static function radarChart($label='Test',$colorRGB='0,0,0',$data=array()){
		return array(
			'label'=>$label,
			'backgroundColor'=>"rgba($colorRGB,0.2)",
			'borderColor'=>"rgba($colorRGB,1)",
			'pointBackgroundColor'=>"rgba($colorRGB,1)",
			'pointBorderColor'=>"#fff",
			'pointHoverBackgroundColor'=>"#fff",
			'pointHoverBorderColor'=>"rgba($colorRGB,1)",
			'data'=>$data
		);
	}

	public static function pieChart($data=array(),$backgroundColor=array(),$hoverBackgroundColor=array()){
		return array(
			'data'=>$data,
			'backgroundColor'=>$backgroundColor,
			'hoverBackgroundColor'=>$hoverBackgroundColor
		);
	}
	
}