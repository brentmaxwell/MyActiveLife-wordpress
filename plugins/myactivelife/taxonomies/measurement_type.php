<?php

class MyActiveLife_Taxonomies_MeasurementType extends CustomTaxonomy{
	public function __construct(){
		$this->taxonomy_type = 'measurement_type';
		$this->post_type = 'body';
		$this->options =array(
			'label'		=> 'Measurement Type',
			'labels'	=> array(
				'name'               => __('Measurement Types'),
				'singular_name'      => __('Measurement Type'),
				'menu_name'          => __('Measurement Types'),
				'all_items'          => __('Measurement Types'),
				'edit_item'          => __('Edit Measurement Type'),
				'view_item'          => __('View Measurement Type'),
				'update_item'        => __('Update Measurement Type'),
				'add_new_item'       => __('Add New Measurement Type'),
				'new_item_name'      => __('Measurement Type'),
				//'parent_item'        => __(),
				'parent_item_colon'  => __(':'),
				'search_items'       => __('Search Measurement Types'),
				'popular_items'      => __('Popular Measurement Types'),
				//'separate_items_with_commas' => ('')
				//'add_or_remove_items' => __(),
				//'choose_from_most_used' => __(),
				'not_found'          => __('No Measurement Types found'),
			),
			'public'                 => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_tagcloud'          => true,
			//'meta_box_cb'            =>,
			'show_admin_column'      => true,
			'hierarchical'           => false,
			'rewrite'                => array(
				'slug'               => 'measurement-type',
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

$myActiveLife_Taxonomies_MeasurementType = new MyActiveLife_Taxonomies_MeasurementType();