<?php
class MyActiveLifeKeyringSocialImporterHooks{
	public function __construct(){
		add_action( 'keyring_post_imported', array($this,'keyringImportHook'));
	}
	
	public function keyringImportHook($post_id){
		if(has_term('tripit','keyring_services',$post_id)){$this->tripit_hook($post_id);}
		if(has_term('strava','keyring_services',$post_id)){$this->strava_hook($post_id);}
	}
	
	public function tripit_hook($post_id){
		$start_date = get_post_meta($post_id,'start_date',true);
		$end_date = get_post_meta($post_id,'end_date',true);
		$query_args = array(
			'post_type' => 'media',
			'post_parent' => 0,
			'date_query' => array(
				'after' => $start_date,
				'before' => $end_date
			),
		);
		$media_query = new WP_Query($query_args);
		foreach($media_query->posts as $post){
				$post->post_parent = $post_id;
				$post->post_status = get_post_field('post_status',$post_id);
				wp_update_post($post);
		}
	}

	
	public function strava_hook($post_id){
		$start_date = get_post_meta($post_id,'start_date_local',true);
		$end_date = get_post_meta($post_id,'end_date_local',true);
		$query_args = array(
			'post_type' => 'media',
			'post_parent' => 0,
			'date_query' => array(
				'after' => $start_date,
				'before' => $end_date
			),
		);
		$media_query = new WP_Query($query_args);
		foreach($media_query->posts as $post){
			$post->post_parent = $post_id;
			$post->post_status = get_post_field('post_status',$post_id);
			wp_update_post($post);
		}
	}
}

$myActiveLifeKeyringSocialImporterHooks = new MyActiveLifeKeyringSocialImporterHooks();