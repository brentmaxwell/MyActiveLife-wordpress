<?php

class MyActiveLife_Taxonomies_ActivityType extends CustomTaxonomy{
	public function __construct(){
		$this->taxonomy_type = 'activity_type';
		$this->post_type = 'activity';
		$this->options = array(
			'label'		=> 'Activity Type',
			'labels'	=> array(
				'name'               => __('Activity Types'),
				'singular_name'      => __('Activity Type'),
				'menu_name'          => __('Activity Types'),
				'all_items'          => __('Activity Types'),
				'edit_item'          => __('Edit Activity Type'),
				'view_item'          => __('View Activity Type'),
				'update_item'        => __('Update Activity Type'),
				'add_new_item'       => __('Add New Activity Type'),
				'new_item_name'      => __('Activity Type'),
				//'parent_item'        => __(),
				'parent_item_colon'  => __(':'),
				'search_items'       => __('Search Activity Types'),
				'popular_items'      => __('Popular Activity Types'),
				//'separate_items_with_commas' => ('')
				//'add_or_remove_items' => __(),
				//'choose_from_most_used' => __(),
				'not_found'          => __('No Activity Types found'),
			),
			'public'                 => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_tagcloud'          => true,
			//'meta_box_cb'            =>,
			'show_admin_column'      => true,
			'hierarchical'           => false,
			'rewrite'                => array(
				'slug'               => 'activitytype',
				'with_front'         => true,
				'hierarchical'       => false,
				//'ep_mask'            =>
			),
			//'capabilities'           =>,
			'sort'                   => false, 
		);
		parent::__construct();
	}
}

$myActiveLife_Taxonomies_ActivityType = new MyActiveLife_Taxonomies_ActivityType();