<?php

class MyActiveLife_PostTypes_Activity extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'activity';
		$this->options = array(
			'label'		=> 'Activity',
			'labels'	=> array(
				'name'               => __('Activities'),
				'singular_name'      => __('Activity'),
				'menu_name'          => __('Activities'),
				'name_admin_bar'     => __('Activity'),
				'all_items'          => __('Activities'),
				'add_new'            => __('Add New Activity'),				
				'add_new_item'       => __('Add New Activity'),
				'edit_item'          => __('Edit Activity'),
				'new_item'           => __('New Activity'),
				'view_item'          => __('View Activity'),
				'search_items'       => __('Search Activities'),
				'not_found'          => __('No Activities found'),
				'not_found_in_trash' => __('No Activities found in trash'),
				'parent_item_colon'  => __(':'),
			),
			'description'            => __("Activities"),
			'public'                 => true,
			'exclude_from_search'    => false,
			'publicly_queryable'     => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_in_menu'           => true,
			'show_in_admin_bar'      => true,
			'menu_position'          => 10,
			'menu_icon'              => 'dashicons-chart-line',
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
				'activity_type',
				'location',
				'keyring_services',
     			'post_tag',
     			'people'
			),
			'has_archive'            => true,
			'rewrite'                => array(
				'slug'               => 'activities',
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
				'id' => 'activity_data',
				'title' => 'Activity Data',
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
					),
					array(
						'id' => 'distance',
						'title' => 'Distance',
						'label' => 'meters',
						'type' => 'number'
					),
					array(
						'id' => 'elapsed_time',
						'title' => 'Elapsed Time',
						'label' => 'seconds',
						'type' => 'number'
					),
					array(
						'id' => 'total_elevation_gain',
						'title' => 'Total Elevation Gain',
						'label' => 'meters',
						'type' => 'number'
					)
					
				)
			),
			array(
				'id' => 'map',
				'title' => 'Route',
				'context' => 'side',
				'fields' => array(
					array(
						'id' => 'map_polyline',
						'title' => 'Map',
						'type' => 'custom'
					)
				)
			),
			array(
				'id' => 'media',
				'title' => 'Attached Media',
				'context' => 'normal',
				'priority' => 'high'
			)
		);
		$this->columns = array(
			'start_end' => 'Start/End',
			'media' => 'Media' 
		);
		$this->geo = true;
		parent::__construct();
		add_action('admin_head-edit.php',array($this,'title_filter'));
	}
	
	public function render_meta_box($post,$metabox){
		switch($metabox['id']){
			case 'map':
				$raw = getPostJson('strava_raw',$post->ID);
				$map_polyline = $raw->map->summary_polyline;
				$start = implode(',',$raw->start_latlng);
				$end = implode(',',$raw->end_latlng);
				$shortcode = '[staticmap markers="color:green|'.$start.' color:red|'.$end.'" class="img-responsive center-block thumbnail" height="254" width="254" polyline="color:0xFF0000BF|weight:2|enc:'.urlencode($map_polyline).'"]';
				echo do_shortcode($shortcode);
				break;
			case 'media':
				$shortcode = '[mediagallery]';
				echo do_shortcode($shortcode);
				break;
			default:
				parent::render_meta_box($post,$metabox);
				break;
		}
	}
	
	public function add_columns($columns){
		unset($columns['tags']);
		unset($columns['taxonomy-activity_type']);
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
				$startdatetime = get_post_meta($id,'start_date_local',true);
				$startdatepart = date('Y-m-d',strtotime($startdatetime));
				$starttimepart = date('G:i',strtotime($startdatetime));
				echo '<time datetime="'.$startdatetime.'" style="text-align:right;">';
				echo $startdatepart; 
				echo '&nbsp;';
				echo $starttimepart;
				echo '</time>';
				echo '<br/>';
				$enddatetime = get_post_meta($id,'end_date_local',true);
				$enddatepart = date('Y-m-d',strtotime($enddatetime));
				$endtimepart = date('G:i',strtotime($enddatetime));
				echo '<time datetime="'.$enddatetime.'" style="text-align:right;">';
				echo $enddatepart; 
				echo '&nbsp;';
				echo $endtimepart;
				echo '</time>';
				break;
	    	default:
				parent::display_columns($column_name,$id);
		        break;
    	} // end switch
	}
	
	function title_filter(){
		global $post_type;
		if($this->post_type == $post_type)
			add_filter('the_title',array($this,'title_column_filter'),100,2);
	}
	
	function title_column_filter($title,$id){
		$activity_types = wp_get_post_terms( $id, 'activity_type');
		if($activity_types){
			foreach($activity_types as $activity_type){
				$activity_type_title .= '<span class="myactivelifeicon myactivelife-activitytype-'.$activity_type->slug.'"></span>&nbsp;';
			}
		}
		$title = $activity_type_title.$title;
		return $title;
	}   
}

$myActiveLife_PostTypes_Activity = new MyActiveLife_PostTypes_Activity();