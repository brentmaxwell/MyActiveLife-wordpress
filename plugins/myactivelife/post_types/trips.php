<?php

class MyActiveLife_PostTypes_Trips extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'trip';
		$this->options = array(
			'label'		=> 'Trip',
			'labels'	=> array(
				'name'               => __('Trips'),
				'singular_name'      => __('Trip'),
				'menu_name'          => __('Trips'),
				'name_admin_bar'     => __('Trip'),
				'all_items'          => __('Trips'),
				'add_new'            => __('Add New Trip'),				
				'add_new_item'       => __('Add New Trip'),
				'edit_item'          => __('Edit Trip'),
				'new_item'           => __('New Trip'),
				'view_item'          => __('View Trip'),
				'search_items'       => __('Search Trips'),
				'not_found'          => __('No Trips found'),
				'not_found_in_trash' => __('No Trips found in trash'),
				'parent_item_colon'  => __(':'),
			),
			'description'            => __("Trips"),
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
     			'people',
         'location'
			),
			'has_archive'            => true,
			'rewrite'                => array(
				'slug'               => 'trips',
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
				'id' => 'Trip_details',
				'title' => 'Trip Details',
				'context' => 'normal',
				'priority' => 'core',
				'fields' => array(
					array(
						'id' => 'start_date',
						'title' => 'Start Date',
						'type'=>'date'
					),
					array(
						'id' => 'end_date',
						'title' => 'End Date',
						'type'=>'date'
					)
				)
			),
		);
		
		$this->columns = array(
			'start_end' => 'Start/End',
			'media' => 'Media'
		);
		$this->geo = true;
		parent::__construct();
	}
	
	public function add_columns($columns){
		unset($columns['tags']);
		unset($columns['date']);
		return parent::add_columns($columns);
	}
	
	function display_columns($column_name, $id) {
    	switch ($column_name) {
	    	case 'media':
				$children = get_children(array('post_parent'=>$id));
				echo count($children);
				break;
			case 'start_end':
				$startdate = get_post_meta($id,'start_date',true);
				echo '<time datetime="'.$startdate.'" style="text-align:right;">';
				echo $startdate; 
				echo '</time>';
				echo '<br/>';
				$enddate = get_post_meta($id,'end_date',true);
				echo '<time datetime="'.$enddate.'" style="text-align:right;">';
				echo $enddate; 
				echo '</time>';
				break;
	    	default:
				parent::display_columns($column_name,$id);
		        break;
    	} // end switch
	}   
}

$myActiveLife_PostTypes_Trips = new MyActiveLife_PostTypes_Trips();