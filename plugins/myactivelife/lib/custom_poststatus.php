<?php

class CustomPostStatus{
	public function __construct(){
		
	}
	
	public function register_post_status(){
		register_post_status(
			$this->name,
			$this->options
		);
	}
}