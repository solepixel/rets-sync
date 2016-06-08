<?php

if( ! defined( 'ABSPATH' ) )
	exit;

if( defined( 'RETS_Sync' ) )
	return;

class RETS_Sync {

	const SLUG = 'rets-sync';

	protected $rets_sync_db;

	public function __construct(){
		add_action( 'plugins_loaded', array( $this, '_init' ) );
	}

	public function _init() {
		// Enable Basic Authentication for Dev Site
		add_filter( 'http_request_args', array( $this, 'http_request_args' ), 10, 2);
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

		global $rets_sync_db;

		if( ! $rets_sync_db )
			$rets_sync_db = new RETS_Sync_DB();

		$this->rets_sync_db = $rets_sync_db;

		add_action( 'init', array( $this->rets_sync_db, '_init' ) );

		if( ! $this->rets_sync_db->disabled ){
			$sync = $this->rets_sync_db->enabled_sync;

			if( ! in_array( $sync, array( 'all', 'active', 'new_and_updated', 'images_only', 'details_only' ) ) )
				return;

			add_action( 'admin_init', array( $this, 'sync_' . $sync ) );
		}

		add_action( 'add_meta_boxes_listing', array( $this, 'add_sync_images_button' ) );
		add_action( 'wp_ajax_rets_sync_image', array( $this, 'ajax_sync_images' ) );
		add_filter( 'acf/get_field_groups', array( $this, 'add_sync_details_button' ) );
		add_action( 'wp_ajax_rets_sync_details', array( $this, 'ajax_sync_details' ) );
		add_action( 'wp_ajax_rets_sync_get_queue_total', array( $this, 'ajax_queue_total' ) );
	}

	function prevent_from_stopping(){
		set_time_limit( MINUTE_IN_SECONDS * 10 );
		ini_set( 'memory_limit', '256M' );
	}


	function http_request_args( $r, $url ) {
		if( strpos( $url, 'de.velop.in' ) !== false ){
			$r['headers']['Authorization'] = 'Basic ' . base64_encode( 'dev:#bXl$)&=09-5' );
		}

		return $r;
	}

	public function queue_exists(){
		$queue = WP_Queue::get_instance();

		return $queue->available_jobs();
	}


	public function sync_all() {
		if( get_transient( 'rets_data_sync_all' ) )
			return;

		// don't add more to an existing queue
		if( $this->queue_exists() )
			return;

		update_option( 'rets_sync_last_sync_timestamp', current_time( 'mysql' ) );
		set_transient( 'rets_data_sync_all', 'PAUSE', DAY_IN_SECONDS ); // * 30

		$this->prevent_from_stopping();

		$listings = $this->rets_sync_db->get_listings();
		$counter = 0;

		foreach ( $listings as $listing ) {
			$job = new RETS_Sync_Listing_Job( $listing->id );
			wp_queue( $job );
			$counter++;
		}

		return $counter;
	}

	public function sync_active() {
		if( get_transient( 'rets_data_sync_active' ) )
			return;

		// don't add more to an existing queue
		if( $this->queue_exists() )
			return;

		update_option( 'rets_sync_last_sync_timestamp', current_time( 'mysql' ) );
		set_transient( 'rets_data_sync_active', 'PAUSE', HOUR_IN_SECONDS * 4 );

		$this->prevent_from_stopping();

		$this->rets_sync_db->add_filter( 'L_Status', 'Active' );
		$listings = $this->rets_sync_db->get_listings();
		$counter = 0;

		foreach ( $listings as $listing ) {
			$job = new RETS_Sync_Listing_Job( $listing->id );
			wp_queue( $job );
			$counter++;
		}

		return $counter;
	}

	public function sync_new_and_updated() {
		if( get_transient( 'rets_data_sync_new_updated' ) )
			return;

		// don't add more to an existing queue
		if( $this->queue_exists() )
			return;

		$last_sync = get_option( 'rets_sync_last_sync_timestamp' );
		update_option( 'rets_sync_last_sync_timestamp', current_time( 'mysql' ) );

		if( $last_sync ){
			$this->rets_sync_db->add_filter( 'ModifiedTime', $last_sync, '>', 'rc_' );
			set_transient( 'rets_data_sync_new_updated', 'PAUSE', MINUTE_IN_SECONDS * 30 );
		} else {
			// let's go ahead and just grab the Active listings and make sure this doesn't run for 4 hours
			$this->rets_sync_db->add_filter( 'L_Status', 'Active' );
			set_transient( 'rets_data_sync_new_updated', 'PAUSE', HOUR_IN_SECONDS * 4 );
		}

		$this->prevent_from_stopping();

		$listings = $this->rets_sync_db->get_listings();
		$counter = 0;

		foreach ( $listings as $listing ) {
			$job = new RETS_Sync_Listing_Job( $listing->id );
			wp_queue( $job );
			$counter++;
		}

		return $counter;
	}

	public function sync_images_only() {
		if( get_transient( 'rets_data_sync_images_only' ) )
			return;

		// don't add more to an existing queue
		if( $this->queue_exists() )
			return;

		update_option( 'rets_sync_last_sync_timestamp', current_time( 'mysql' ) );
		set_transient( 'rets_data_sync_images_only', 'PAUSE', HOUR_IN_SECONDS );

		$this->prevent_from_stopping();

		$this->rets_sync_db->add_filter( 'L_Status', 'Active' );
		$listings = $this->rets_sync_db->get_listings();
		$counter = 0;

		foreach ( $listings as $listing ) {
			$job = new RETS_Sync_Listing_Images_Job( $listing->id );
			wp_queue( $job );
			$counter++;
		}

		return $counter;
	}

	public function sync_details_only() {
		if( get_transient( 'rets_data_sync_details_only' ) )
			return;

		// don't add more to an existing queue
		if( $this->queue_exists() )
			return;

		update_option( 'rets_sync_last_sync_timestamp', current_time( 'mysql' ) );
		set_transient( 'rets_data_sync_details_only', 'PAUSE', HOUR_IN_SECONDS );

		$this->prevent_from_stopping();

		$this->rets_sync_db->add_filter( 'L_Status', 'Active' );
		$listings = $this->rets_sync_db->get_listings();
		$counter = 0;

		foreach ( $listings as $listing ) {
			$job = new RETS_Sync_Listing_Details_Job( $listing->id );
			wp_queue( $job );
			$counter++;
		}

		return $counter;
	}

	public function add_sync_images_button(){
		if( ! is_admin() )
			return;

		$post_type = get_post_type();

		if( ! $post_type || $post_type != 'listing' )
			return;

		$post_type_object = get_post_type_object( $post_type );

		remove_meta_box( 'postimagediv', 'listing', 'side' );
		add_meta_box( 'postimagediv', esc_html( $post_type_object->labels->featured_image ), array( $this, 'listing_thumbnail_featured_image' ), null, 'side', 'low' );
	}

	public function listing_thumbnail_featured_image( $post ){
		$thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
		$original = _wp_post_thumbnail_html( $thumbnail_id, $post->ID );
		if( strpos( $original, 'Set featured image' ) !== false ){
			$original .= '<button class="button secondary rets-sync-images" type="button">' . __( 'Sync Images with RETS Sync', 'retssync' ) . '</button>';
		}

		echo $original;
	}

	public function admin_assets(){
		wp_register_script( 'rets-sync-admin', RETSSYNC_URL . '/assets/js/rets-sync-admin.js', array( 'jquery' ), RETSSYNC_VERSION );
		wp_register_style( 'rets-sync-admin', RETSSYNC_URL . '/assets/css/rets-sync-admin.css', array(), RETSSYNC_VERSION );

		wp_localize_script( 'rets-sync-admin', 'rets_sync_vars', array(
			'ajax_url' => admin_url() . 'admin-ajax.php'
		));

		$screen = get_current_screen();

		if( $screen->post_type == 'listing' || $screen->id == 'toplevel_page_rets-sync-db' ){
			wp_enqueue_script( 'rets-sync-admin');
			wp_enqueue_style( 'rets-sync-admin');
		}
	}

	public function ajax_sync_images(){
		if( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
			return;

		$post_id = isset( $_POST['post_id'] ) ? (int)sanitize_text_field( $_POST['post_id'] ) : false;
		$response['post_id'] = $post_id;
		if( ! $post_id ){
			wp_send_json( $response );
			exit();
		}

		$db_id = get_post_meta( $post_id, '_listing_db-id', true );
		$mls_number = get_post_meta( $post_id, '_listing_mls', true );
		$response['db-id'] = $db_id;
		$response['mls-number'] = $mls_number;

		if( ! $db_id && ! $mls_number ){
			wp_send_json( $response );
			exit();
		}

		$job = new RETS_Sync_Listing_Images_Job( $db_id );
		$job->mls_number = $mls_number;

		$response['result'] = $job->handle();
		$response['error'] = $job->get_last_error();
		$response['errors'] = $job->get_errors();

		wp_send_json( $response );
		exit();
	}

	public function add_sync_details_button( $groups ){
		$screen = get_current_screen();
		if( ! $screen || $screen->post_type != 'listing' )
			return $groups;

		foreach( $groups as &$group ){
			if( $group['title'] == 'Listing Details' ){
				$group['title'] .= ' <a href="#" class="rets-sync-details">Sync with RETS Sync</a>';
			}
		}

		return $groups;
	}

	public function ajax_sync_details(){
		if( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
			return;

		$post_id = isset( $_POST['post_id'] ) ? (int)sanitize_text_field( $_POST['post_id'] ) : false;
		$response['post_id'] = $post_id;
		if( ! $post_id ){
			wp_send_json( $response );
			exit();
		}

		$db_id = get_post_meta( $post_id, '_listing_db-id', true );
		$mls_number = get_post_meta( $post_id, '_listing_mls', true );
		$response['db-id'] = $db_id;
		$response['mls-number'] = $mls_number;

		if( ! $db_id && ! $mls_number ){
			wp_send_json( $response );
			exit();
		}

		$job = new RETS_Sync_Listing_Details_Job( $db_id );
		$job->mls_number = $mls_number;

		$response['result'] = $job->handle();
		$response['error'] = $job->get_last_error();
		$response['errors'] = $job->get_errors();

		wp_send_json( $response );
		exit();
	}

	public function ajax_queue_total(){
		$last_sync = get_option( 'rets_sync_last_sync_timestamp' );
		if( $last_sync && strtotime( $last_sync ) !== false )
			$last_sync = date( 'n/j/Y g:i a', strtotime( $last_sync ) );

		$response = array(
			'total' => number_format( $this->queue_exists() ),
			'last_sync' => $last_sync,
			'error' => false
		);
		wp_send_json( $response );
		exit();
	}
}
