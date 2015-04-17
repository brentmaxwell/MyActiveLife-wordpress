<?php

class MyActiveLife_ShortCodes_GoogleStaticMap{
	const SHORTCODE = 'staticmap';
	public function __construct(){
		add_shortcode(self::SHORTCODE,array($this,'do_shortcode'));
	}
	
	public function do_shortcode( $args ){
		$args = shortcode_atts(array(
				'polyline' => null,
				'polyline_color' => '0xFF0000BF',
				'markers' => null,
				'height' => 240,
				'width' => 240,
				'maptype' => 'terrain',
				'scale' => 1,
				'class' => null,
			),
			$args,
			self::SHORTCODE
		);
		$baseUrl = 'https://maps.google.com/maps/api/staticmap?';
		$baseUrl .= 'maptype='.$args['maptype'];
		if($args['polyline'] != null){
			$baseUrl .= '&amp;path='.urldecode($args['polyline']);	
		}
		$baseUrl .= '&amp;scale='.$args['scale'];
		$baseUrl .= '&amp;size='.$args['width'].'x'.$args['height'];
		$baseUrl .= '&amp;sensor=false';
		$output = '<img';
		if($args['class'] != null){
			$output .= ' class="'.$args['class'].'"';
		}
		if($args['markers'] != null)
		{
			$markers = explode(' ',$args['markers']);
			
			foreach($markers as $marker)
			{
				$baseUrl .= '&amp;markers=';
				$baseUrl .= $marker .'|';
			}
		}
		$output .= ' src="'.$baseUrl.'"/>';
		return $output;
	}
}

$myActiveLife_ShortCodes_GoogleStaticMap = new MyActiveLife_ShortCodes_GoogleStaticMap();