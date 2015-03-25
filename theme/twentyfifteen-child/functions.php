<?php
class theBrentTheme {
	
	public function __construct(){
		
		add_action( 'after_setup_theme', array($this,'setup' ));
		add_action( 'wp_enqueue_scripts', array($this,'enqueue_scripts' ));
		add_action( 'wp_enqueue_scripts', array($this,'enqueue_styles' ));
		add_action( 'widgets_init', array($this,'register_sidebars'),11 );
		add_filter( 'oembed_fetch_url', array($this,'oembedFilters'));
		add_filter( 'get_the_archive_title', array($this,'archive_title'));
	}
	
	public function setup(){
		$this->register_menus();
	}
	
	public function register_menus(){
	}
	
	public function enqueue_scripts(){
		
	}
	
	public function enqueue_styles(){
		wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 'child-style' , get_stylesheet_directory_uri() . '/style.css' );
		wp_enqueue_style( 'strava', get_stylesheet_directory_uri() . '/css/strava.css');
	}
	
	public function register_sidebars(){
		unregister_sidebar( 'sidebar-1' ); 
		register_sidebar( array(
			'name'          => __( 'Sidebar - top', 'twentyfifteen-theBrent' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Add widgets here to appear in the your sidebar above the menus.', 'twentyfifteen-theBrent' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		) );
		register_sidebar( array(
			'name'          => __( 'Sidebar-bottom', 'twentyfifteen-theBrent' ),
			'id'            => 'sidebar-2',
			'description'   => __( 'Add widgets here to appear in your sidebar below the menus.', 'twentyfifteen-theBrent' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		) );
		register_sidebar( array(
			'name'          => __( 'Footer', 'twentyfifteen-theBrent' ),
			'id'            => 'footer-1',
			'description'   => __( 'Add widgets here to appear in your footer.', 'twentyfifteen-theBrent' ),
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		) );
	}
	
	public function oembedFilters($provider){
		if(strpos($provider,"twitter"))
		{
			return $provider . "&align=center";		
		}
		return $provider;
	}
	
	public function archive_title($title){
        $title = single_cat_title( '', false );
	    return $title;
	}
}
$tbTheme = new theBrentTheme();