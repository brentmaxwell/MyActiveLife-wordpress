<?php
class AllowEmptyPost{
	public function __construct(){
		add_action('allow_empty_post',array($this,'allow_empty_post'));
		add_action('disallow_empty_post',array($this,'disallow_empty_post'));
	}
	
	public function allow_empty_post(){
		add_filter('pre_post_title', array($this,'pre_post_mask_empty'));
		add_filter('pre_post_content', array($this,'pre_post_mask_empty'));
		add_filter('pre_post_excerpt', array($this,'pre_post_mask_empty'));
		add_filter('wp_insert_post_data', array($this,'wp_insert_post_data_unmask_empty'));	
	}
	
	public function disallow_empty_post(){
		remove_filter('pre_post_title', array($this,'pre_post_mask_empty'));
		remove_filter('pre_post_content', array($this,'pre_post_mask_empty'));
		remove_filter('pre_post_excerpt', array($this,'pre_post_mask_empty'));
		remove_filter('wp_insert_post_data', array($this,'wp_insert_post_data_unmask_empty'));	
	}
	
	function pre_post_mask_empty($value) {
  		if ( empty($value) ) { return ' '; }
  		return $value;
	}

	function wp_insert_post_data_unmask_empty($data){
  		if ( ' ' == $data['post_title'] ) {
    		$data['post_title'] = '';
  		}
  		if ( ' ' == $data['post_content'] ) {
    		$data['post_content'] = '';
  		}
  		return $data;
	}
}

$allowEmptyPost = new AllowEmptyPost();