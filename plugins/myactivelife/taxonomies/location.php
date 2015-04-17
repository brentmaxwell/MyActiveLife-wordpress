<?php
class MyActiveLife_Taxonomies_Location extends CustomTaxonomy{
	public function __construct(){
		$this->taxonomy_type = 'location';
		$this->post_type = 'activity';
		$this->options = array(
			'label'		=> 'Location',
			'labels'	=> array(
				'name'               => __('Locations'),
				'singular_name'      => __('Location'),
				'menu_name'          => __('Locations'),
				'all_items'          => __('Locations'),
				'edit_item'          => __('Edit Location'),
				'view_item'          => __('View Location'),
				'update_item'        => __('Update Location'),
				'add_new_item'       => __('Add New Location'),
				'new_item_name'      => __('Location'),
				//'parent_item'        => __(),
				'parent_item_colon'  => __(':'),
				'search_items'       => __('Search Locations'),
				'popular_items'      => __('Popular Locations'),
				//'separate_items_with_commas' => ('')
				//'add_or_remove_items' => __(),
				//'choose_from_most_used' => __(),
				'not_found'          => __('No Locations found'),
			),
			'public'                 => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_tagcloud'          => true,
			//'meta_box_cb'            =>,
			'show_admin_column'      => true,
			'hierarchical'           => false,
			'rewrite'                => array(
				'slug'               => 'location',
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

$myActiveLife_Taxonomies_Location = new MyActiveLife_Taxonomies_Location();