<?php

function formatWeight($weight)
{
	return round($weight * 2.20462,2);	
}

function formatPercent($percent)
{
	return $percent . '%';
}

function formatDateString($date){
	$start_date = strtotime( $date );
	return date( 'Y-m-d H:i:s', $start_date );
}

function formatElapsedTime($total_seconds)
{
	$d1 = new DateTime();
	$d2 = new DateTime();
	$d2->add(new DateInterval('PT'.$total_seconds.'S'));
	$elapsed_time = $d2->diff($d1);
	return $elapsed_time->format('%H:%I:%S');
}

function formatDistance($distance)
{
	return round($distance * 0.000621371,2);	
}

function formatSpeed($speed)
{
	return round($speed * 2.23694,2);	
}

function formatElevation($elevation)
{
	return round($elevation * 3.28084,2);
}