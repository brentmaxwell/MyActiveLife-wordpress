<?php
/*
Plugin Name: theBrent - MyActiveLife plugins
Description: Plugins for dashboard.thebrent.net
Author: Brent Maxwell
Version: 0.1
*/

class MyActiveLife{
	public function __construct(){
		$activities = new MyActiveLife_Activities();
		$myActiveLifeStrava = new MyActiveLife_Strava();
	}
}

include('post-types/activities.php');
include('importers/strava.php');

$myActiveLife = new MyActiveLife();