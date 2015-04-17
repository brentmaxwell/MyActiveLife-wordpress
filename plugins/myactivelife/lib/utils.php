<?php

function getPostJson($key, $id = 0){
	$data = get_post_custom_values($key,$id);
	$output = json_decode(str_replace("\\","\\\\",utf8_encode($data[0])));
	return $output;
}
?>