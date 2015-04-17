<?php
class CleanUnattachedPhotos{
	function __construct(){
		register_activation_hook(__FILE__,array($this,'activate'));
		//add_action('clean_unattached_photos',array($this,'do_clean_unattached_photos'));
	}
	
	public function activate(){
		wp_schedule_event(time(),'daily','clean_unattached_photos');
	}
	
	public function do_clean_unattached_photos(){
		$this->attach_photos();
		$this->clean_photos();
	}
	
	public function attach_photos(){
		$args = array(
			'post_type' => 'photo',
			'post_parent' => 0,
			'nopaging' => true,
		);
		$query = new WP_Query($args);
		foreach($query->posts as $post){
			$this->attach_photo($post);
		}
	}	
	
	public function attach_photo($post){
		$query_args = array(
			'post_type' => 'activity',
			'meta_query'=> array(
				'relation' => 'AND',
				array(
					'key'=>'start_timestamp',
					'value' => strtotime($post->post_date_gmt),
					'type' => 'numeric',
					'compare' => '<='
				),
				array(
					'key'=>'end_timestamp',
					'value' => strtotime($post->post_date_gmt),
					'type' => 'numeric',
					'compare' => '>='
				)
			)
		);
		$activity_query = new WP_Query($query_args);
		if($activity_query->post_count > 0){
			$post['post_parent'] = $activity_query->post->ID;
			wp_update_post($post);
		}
	}
	
	public function clean_photos(){
		$now = new DateTime("@".time());
		$now =  $now->sub(new DateInterval('P30D'));
		$before_date = $now->getTimestamp(); 
		$args = array(
			'post_type' => 'photo',
			'post_parent' => 0,
			'nopaging' => true,
			'date_query' => array(
				'before' => date('c',$before_date)
			)
		);
		$query = new WP_Query($args);
		foreach($query->posts as $post){
			wp_trash_post($post->ID);
		}
	}	
}

$cleanUnattachedPhotos = new CleanUnattachedPhotos();