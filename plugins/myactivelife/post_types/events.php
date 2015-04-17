<?php

class MyActiveLife_PostTypes_Events extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'event';
		$this->options = array(
			'label'		=> 'Event',
			'labels'	=> array(
				'name'               => __('Events'),
				'singular_name'      => __('Event'),
				'menu_name'          => __('Events'),
				'name_admin_bar'     => __('Event'),
				'all_items'          => __('Events'),
				'add_new'            => __('Add New Event'),				
				'add_new_item'       => __('Add New Event'),
				'edit_item'          => __('Edit Event'),
				'new_item'           => __('New Event'),
				'view_item'          => __('View Event'),
				'search_items'       => __('Search Events'),
				'not_found'          => __('No Events found'),
				'not_found_in_trash' => __('No Events found in trash'),
				'parent_item_colon'  => __(':'),
			),
			'description'            => __("Events"),
			'public'                 => true,
			'exclude_from_search'    => false,
			'publicly_queryable'     => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_in_menu'           => true,
			'show_in_admin_bar'      => true,
			'menu_position'          => 13,
			'menu_icon'              => 'dashicons-calendar-alt',
			'capability_type'        => 'post',
			//'capabilities'           => array(),
			'map_meta_cap'           => null,
			'hierarchical'           => false,
			'supports'               => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'trackbacks',
				'custom-fields',
				'comments',
				//'revisions',
				//'page-attributes',
				//'post-formats'
			),
			//'register_meta_box_cb'   =>,
			'taxonomies'             => array(
				'location',
				'keyring_services',
     			'post_tag',
     			'people'
			),
			'has_archive'            => true,
			'rewrite'                => array(
				'slug'               => 'events',
				'with_front'         => true,
				'feeds'              => true,
				'pages'              => true,
				//'ep_mask'            => 
			),
			//'query_var'              =>,
			'can_export'             => true,
			
		);
		$this->meta_boxes = array(
			array(
				'id' => 'event_details',
				'title' => 'Event Details',
				'context' => 'normal',
				'priority' => 'core',
				'fields' => array(
					array(
						'id' => 'start_date_local',
						'title' => 'Start Date',
						'type'=>'datetime-local'
					),
					array(
						'id' => 'end_date_local',
						'title' => 'End Date',
						'type'=>'datetime-local'
					)
				)
			),
		);
		
		$this->columns = array(
			'start_date_local' => 'Start Date',
			'end_date_local' => 'End Date',
		);
		$this->parent_type = 'activity';
		parent::__construct();
	}
}

$myActiveLife_PostTypes_Events = new MyActiveLife_PostTypes_Events();