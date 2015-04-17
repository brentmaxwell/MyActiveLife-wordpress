<?php
class MyActiveLife_PostStatus{
	function __construct(){
		add_action('init', array( $this, 'register_post_status'));
	}
	
	public function register_post_status(){
		register_post_status(
			'unattached',
			array(
				'label' => 'Unattached',
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => _n_noop('Unattached <span class="count"(%s)</span>','Unattached <span class="count">(%s)</span>')
			)
		);
	}
}

$myActiveLife_PostStatus = new MyActiveLife_PostStatus();