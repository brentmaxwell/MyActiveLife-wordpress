<?php
/*
Plugin Name: MyActiveLife
Description: MyActiveLife
Author: Brent Maxwell
Version: 0.1
*/

class MyActiveLife{
	public function __construct(){
	}
}

$includes = array(
	'lib',
	'admin_meta',
	'taxonomies',
	'post_types',
	'services',
	'widgets',
	'shortcodes'
);

include('lib/keyring/keyring.php');


foreach($includes as $include_dir){
	$dir = glob( dirname( __FILE__ ) . "/".$include_dir."/*.php" );
	foreach ( $dir as $file ){
		require $file;
	}
}


$myActiveLife = new MyActiveLife();