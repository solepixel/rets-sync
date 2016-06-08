<?php

if( ! defined( 'ABSPATH' ) )
	exit;

if( defined( 'RETS_Sync_Listings_CPT' ) )
	return;

class RETS_Sync_Listings_CPT {

	function __construct(){

	}

	function init(){
		add_action( 'init', array( $this, '_init' ) );
		add_action( 'init', array( $this, 'create_post_type' ) );
	}

	function _init(){
		// do init stuff here.
		add_filter( 'cac/column/meta/value', array( $this, 'format_price_admin_column' ), 10, 3 );
		add_filter( 'searchwp_in_admin', '__return_true' );
		add_filter( 'pre_get_posts', array( $this, 'archive_adjustments' ) );
	}

	// Register Custom Post Type
	function create_post_type() {

		$labels = array(
			'name'                  => _x( 'Listings', 'Post Type General Name', 'rets-sync' ),
			'singular_name'         => _x( 'Listing', 'Post Type Singular Name', 'rets-sync' ),
			'menu_name'             => __( 'Listings', 'rets-sync' ),
			'name_admin_bar'        => __( 'Listings', 'rets-sync' ),
			'archives'              => __( 'Listings', 'rets-sync' ),
			'parent_item_colon'     => __( 'Parent Listing:', 'rets-sync' ),
			'all_items'             => __( 'All Listings', 'rets-sync' ),
			'add_new_item'          => __( 'Add New Listing', 'rets-sync' ),
			'add_new'               => __( 'Add New', 'rets-sync' ),
			'new_item'              => __( 'New Listing', 'rets-sync' ),
			'edit_item'             => __( 'Edit Listing', 'rets-sync' ),
			'update_item'           => __( 'Update Listing', 'rets-sync' ),
			'view_item'             => __( 'View Listing', 'rets-sync' ),
			'search_items'          => __( 'Search Listing', 'rets-sync' ),
			'not_found'             => __( 'Not found', 'rets-sync' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'rets-sync' ),
			'featured_image'        => __( 'Featured Image', 'rets-sync' ),
			'set_featured_image'    => __( 'Set featured image', 'rets-sync' ),
			'remove_featured_image' => __( 'Remove featured image', 'rets-sync' ),
			'use_featured_image'    => __( 'Use as featured image', 'rets-sync' ),
			'insert_into_item'      => __( 'Insert into listing', 'rets-sync' ),
			'uploaded_to_this_item' => __( 'Uploaded to this listing', 'rets-sync' ),
			'items_list'            => __( 'Listings list', 'rets-sync' ),
			'items_list_navigation' => __( 'Listings list navigation', 'rets-sync' ),
			'filter_items_list'     => __( 'Filter listings list', 'rets-sync' ),
		);
		$rewrite = array(
			'slug'                  => 'listing',
			'with_front'            => false,
			'pages'                 => true,
			'feeds'                 => true,
		);
		$args = array(
			'label'                 => __( 'Listing', 'rets-sync' ),
			'description'           => __( 'Ray & Poynor Listing', 'rets-sync' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'excerpt', 'thumbnail', 'trackbacks', 'revisions', ),
			'taxonomies'            => array( 'rap-category', 'rap-community' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-admin-home',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => 'listings',
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'rewrite'               => $rewrite,
			'capability_type'       => 'page',
		);
		register_post_type( 'listing', $args );
	}

	function format_price_admin_column( $value, $id, $column ){
		$custom_field_key = '_listing_price';
		if ( $custom_field_key == $column->get_field() ) {
			$value = number_format( $value );
		}
		return $value;
	}

	public function archive_adjustments( $q ){
		if( is_admin() || ! $q->is_post_type_archive( 'listing' ) )
			return $q;

		$q->set( 'posts_per_page', '50' );

		return $q;
	}

}
