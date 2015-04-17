<?php

class CustomTaxonomy{
	public function __construct(){
		register_activation_hook( __FILE__, array($this,'activate' ));
		add_action('init', array( $this, 'register_taxonomy'));
	}
	
	public function activate(){
		flush_rewrite_rules();
	}
	
	public function register_taxonomy(){
		register_taxonomy(
			$this->taxonomy_type,
			$this->post_type,
			$this->options
		);
	}
}