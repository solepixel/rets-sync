<?php

if( ! defined( 'ABSPATH' ) )
	exit;

if( defined( 'RETS_Sync_DB' ) )
	return;

class RETS_Sync_DB {

	const SALT = '5B#HVJA!DnBQUI3FQLaP*41YcQdXJq3N';

	const SLUG = 'rets-sync-db';

	public $db;

	public $credentials;

	public $hostname;
	public $username;
	public $password;
	public $database;

	public $data_table;
	public $unique_data;
	public $mls_field;

	public $images;

	public $image_table;
	public $unique_image;

	public $image_host;
	public $image_port;
	public $image_user;
	public $image_pass;
	public $image_path;
	public $image_prefix;
	public $image_url;
	public $image_url_user;
	public $image_url_pass;

	public $enabled_sync;

	public $disabled = false;

	public $filters = array();

	public $errors = array();

	function __construct(){
		$this->credentials = $this->get_setting( 'credentials', 'wpdb' );
		$this->hostname = $this->get_setting( 'hostname' );
		$this->username = $this->get_setting( 'username' );
		$this->password = $this->decrypt( $this->get_setting( 'password' ) );
		$this->database = $this->get_setting( 'database' );

		$this->data_table = $this->get_setting( 'data_table' );
		$this->unique_data = $this->get_setting( 'unique_data' );
		$this->mls_field = $this->get_setting( 'mls_field' );

		$this->images = $this->get_setting( 'images', array( 'db' ) );
		$this->image_table = $this->get_setting( 'image_table' );
		$this->unique_image = $this->get_setting( 'unique_image' );
		$this->image_host = $this->get_setting( 'image_host' );
		$this->image_port = $this->get_setting( 'image_port', '21' );
		$this->image_user = $this->get_setting( 'image_user' );
		$this->image_pass = $this->decrypt( $this->get_setting( 'image_pass' ) );
		$this->image_path = trailingslashit( $this->get_setting( 'image_path' ) );
		$this->image_prefix = $this->get_setting( 'image_prefix' );
		$this->image_url = $this->get_setting( 'image_url' );
		$this->image_url_user = $this->get_setting( 'image_url_user' );
		$this->image_url_pass = $this->decrypt( $this->get_setting( 'image_url_pass' ) );

		$this->disabled = $this->get_setting( 'disabled' );
		$this->enabled_sync = $this->get_setting( 'enabled_sync' );
		if( ! $this->enabled_sync )
			$this->enabled_sync = 'new_and_updated';
	}

	function _init(){
		add_action( 'admin_menu', array( $this, 'admin_menu_settings_page' ) );
	}

	function get_setting( $setting = false, $default = null ){
		$settings = get_option( '_' . self::SLUG . '-settings' );
		if( ! $setting )
			return $settings;

		if( ! isset( $settings[ $setting ] ) )
			return $default !== null ? $default : false;

		return $settings[ $setting ];
	}

	function encrypt( $secret ) {
		if ( ! $secret )
			return false;

		return trim(
			base64_encode(
				mcrypt_encrypt(
					MCRYPT_RIJNDAEL_256,
					self::SALT,
					$secret,
					MCRYPT_MODE_ECB,
					mcrypt_create_iv(
						mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB ), MCRYPT_RAND
					)
				)
			)
		);
	}

	function decrypt( $encryption ){
		if ( ! $encryption )
			return false;

		return trim(
			mcrypt_decrypt(
				MCRYPT_RIJNDAEL_256,
				self::SALT,
				base64_decode( $encryption ),
				MCRYPT_MODE_ECB,
				mcrypt_create_iv(
					mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB ),
					MCRYPT_RAND
				)
			)
		);
	}

	public function admin_menu_settings_page(){
		$hook = add_menu_page(
			__( 'RETS Sync DB', 'rets-sync' ),		// page_title
			__( 'RETS Sync DB', 'rets-sync' ),		// menu_title
			'manage_options',						// capabilities
			self::SLUG,								// menu_slug
			array( $this, 'plugin_page' ),			// function
			'dashicons-cloud',						// icon_url
			37										// position
		);

		//add_action( "load-$hook", array( $this, 'screen_option' ) );
	}


	public function plugin_page(){
		$this->save_settings();

		global $rets_sync;

		$last_sync = get_option( 'rets_sync_last_sync_timestamp' );
		if( $last_sync && strtotime( $last_sync ) !== false )
			$last_sync = date( 'n/j/Y g:i a', strtotime( $last_sync ) );

		$view_vars = array(
			'plugin_base_url' => admin_url( 'admin.php?page=' . self::SLUG ),
			'credentials' => $this->get_setting( 'credentials', 'wpdb' ),
			'hostname' => $this->get_setting( 'hostname' ),
			'username' => $this->get_setting( 'username' ),
			'database' => $this->get_setting( 'database' ),

			'data_table' => $this->get_setting( 'data_table' ),
			'unique_data' => $this->get_setting( 'unique_data' ),
			'mls_field' => $this->get_setting( 'mls_field' ),

			'images' => $this->get_setting( 'images', array( 'db' ) ),
			'image_table' => $this->get_setting( 'image_table' ),
			'unique_image' => $this->get_setting( 'unique_image' ),
			'image_host' => $this->get_setting( 'image_host' ),
			'image_port' => $this->get_setting( 'image_port', '21' ),
			'image_user' => $this->get_setting( 'image_user' ),
			'image_path' => $this->get_setting( 'image_path' ),
			'image_prefix' => $this->get_setting( 'image_prefix' ),
			'image_url' => $this->get_setting( 'image_url' ),
			'image_url_user' => $this->get_setting( 'image_url_user' ),

			'disabled' => $this->get_setting( 'disabled' ),
			'enabled_sync' => $this->get_setting( 'enabled_sync' ),
			'nonce' => wp_nonce_field( self::SLUG . '-settings', '_wpnonce', true, false ),
			'key' => '_' . self::SLUG . '-settings',
			'queue_total' => $rets_sync->queue_exists(),
			'last_sync' => $last_sync
		);

		echo $this->load_view( 'rets-db-settings', $view_vars );
	}

	public function load_view( $_file, $_vars = array() ){
		// it must end in .php
		if( substr( $_file, -4 ) !== '.php' )
			$_file .= '.php';

		// force views into the "views/" folder
		if( substr( $_file, 0, 6 ) !== 'views/' )
			$_file = 'views/' . $_file;

		$_view = RETSSYNC_PATH  . $_file;
		if( ! file_exists( $_view ) )
			return false;

		if( is_array( $_vars ) )
			extract( $_vars );

		ob_start();
		include( $_view );
		return ob_get_clean();
	}

	function save_settings(){
		$key = '_' . self::SLUG . '-settings';

		if ( ! isset( $_POST[ $key ] ) || ! isset( $_POST['_wpnonce'] ) )
			return;

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], self::SLUG . '-settings' ) )
			return;

		$original_password = $this->get_setting( 'password' );
		$original_image_pass = $this->get_setting( 'image_pass' );

		$new_settings = $_POST[ $key ];
		if( ! trim( $new_settings['password'] ) ){
			$new_settings['password'] = $original_password;
		} else {
			$new_settings['password'] = $this->encrypt( $new_settings['password'] );
		}
		if( ! trim( $new_settings['image_pass'] ) ){
			$new_settings['image_pass'] = $original_image_pass;
		} else {
			$new_settings['image_pass'] = $this->encrypt( $new_settings['image_pass'] );
		}

		if( ! is_array( $new_settings['images'] ) )
			$new_settings['images'] = array( $new_settings['images'] );

		update_option( $key, $new_settings );

	}

	function init_db(){
		if( ! $this->data_table || ! $this->unique_data ){
			$this->errors[] = 'No Data Table or Unique Data column found. Check Settings.';
			return false;
		}

		if( $this->credentials == 'wpdb' ){
			global $wpdb;
			$this->db = $wpdb;
		} else {
			if( ! $this->hostname || ! $this->username || ! $this->password || ! $this->database ){
				$this->errors[] = 'DB Credentials not setup. Check Settings.';
				return false;
			}

			try {
				$this->db = new wpdb( $this->username, $this->password, $this->database, $this->hostname );
			} catch( Exception $e ){
				$this->errors[] = 'Could not connect to database.';
				return false;
			}
		}

		return true;
	}

	function add_filter( $key, $value, $operator = '=', $prefix = 'field_' ){
		$this->filters[ $prefix . $key ] = array( $value, $operator );
	}

	function clear_filters(){
		$this->filters = array();
	}

	function get_filters(){
		$filters = '';
		foreach( $this->filters as $key => $params ){
			$filter = $filters ? ' AND `%s` ' . $params[1] . ' %s' : '`%s` ' . $params[1] . ' %s';
			$filter = sprintf( $filter, $key, '%s' );
			$filters .= $this->db->prepare( $filter, $params[0] );
		}
		return $filters;
	}

	function get_listings( $limit = 100000, $offset = 0 ){
		if( ! $this->init_db() )
			return array();

		$select = "`{$this->unique_data}` AS `id`";
		$from = $this->data_table;
		$join = '';
		$where = '';
		$limit = "LIMIT {$offset}, {$limit}";

		if( $this->filters ){
			$where = 'WHERE ' . $this->get_filters();
			$this->clear_filters();
		}

		$query = "SELECT {$select} FROM {$from} {$join} {$where} {$limit};";

		return $this->db->get_results( $query );
	}

	function get_listing( $listing_id ){
		if( ! $this->init_db() )
			return array();

		return $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->data_table} WHERE `{$this->unique_data}` = %s LIMIT 0, 1;", $listing_id ) );
	}

	function get_listing_by_mls( $mls_number ){
		if( ! $this->init_db() )
			return array();

		if( ! $this->mls_field )
			return array();

		return $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->data_table} WHERE `{$this->mls_field}` = %s LIMIT 0, 1;", $mls_number ) );
	}

	function get_photos_from_db( $mls_number ){
		if( ! $this->init_db() )
			return array();

		$select = "`image`";
		$from = $this->image_table;
		$join = '';
		$where = 'WHERE `MLS_ID` = %s';
		$orderby = "ORDER BY `{$this->unique_image}` ASC";
		$limit = '';

		$query = "SELECT {$select} FROM {$from} {$join} {$where} {$orderby} {$limit};";

		return $this->db->get_col( $this->db->prepare( $query, $mls_number ) );
	}

	function get_photos_from_ftp( $mls_number ){
		if( ! $this->init_db() )
			return array();

		if( ! $this->image_host || ! $this->image_user || ! $this->image_pass || ! $this->image_path ){
			$this->errors[] = 'FTP not setup. See settings page.';
			return false;
		}

		$images = array();
		$count = $this->get_image_count( $mls_number );

		if( ! $count ){
			$this->errors[] = 'This listing has no images.';
			return $images;
		}

		for( $i = 1; $i <= $count; $i++ ){
			$images[] = $this->image_prefix . $mls_number . '_' . $i . '.jpg';
		}

		try {
			$ftp_conn = ftp_connect( $this->image_host, $this->image_port );
		} catch( Exception $e ){
			$this->errors[] = 'Could not connect to FTP host.';
			return false;
		}

		if( ! ftp_login( $ftp_conn, $this->image_user, $this->image_pass ) ){
			ftp_close( $ftp_conn );
			$this->errors[] = 'FTP Login failed.';
			return false;
		}

		$photos = array();

		$upload_dir = wp_upload_dir();
		$upload_path = trailingslashit( $upload_dir['path'] );
		$upload_url = trailingslashit( $upload_dir['url'] );
		$dir = ftp_nlist( $ftp_conn, $this->image_path );

		foreach( $images as $index => $image ){
			if( file_exists( $upload_path . $image ) ){
				$photos[] = $upload_url . $image;
				continue;
			}

			if( ! in_array( $dir, $image ) ){
				$this->errors[] = 'Image not found in FTP. Check image path.';
				continue;
			}

			if( ftp_get( $upload_path . $image, $this->image_path . $image, FTP_BINARY ) ){
				$photos[] = array(
					'url' => $upload_url . $image,
					'path' => $upload_path . $image,
					'source' => $this->image_path . $image
				);
			} else {
				$this->errors[] = 'There was a problem downloading the image from FTP.';
			}
		}

		ftp_close( $ftp_conn );

		return $photos;
	}

	function get_photos_from_url( $mls_number ){
		if( ! $this->init_db() )
			return array();

		if( ! $this->image_url ){
			$this->errors[] = 'Image URL not setup. See settings page.';
			return false;
		}

		$images = array();
		$count = $this->get_image_count( $mls_number );

		if( ! $count ){
			$this->errors[] = 'This listing has no images.';
			return $images;
		}

		$url = trailingslashit( $this->image_url );
		if( $this->image_url_user && $this->image_url_pass ){
			$protocol = strpos( $url, 'https://' ) !== false ? 'https://' : 'http://';
			$url = str_replace( $protocol, $protocol . urlencode( $this->image_url_user ) . ':' . urlencode( $this->image_url_pass ) . '@', $url );
		}

		for( $i = 1; $i <= $count; $i++ ){
			$images[] = $url . $this->image_prefix . $mls_number . '_' . $i . '.jpg';
		}

		return $images;
	}

	function get_image_count( $mls_number ){
		$select = "`field_L_PictureCount` AS `count`";
		$from = $this->data_table;
		$join = '';
		$where = "WHERE `{$this->mls_field}` = %s";
		$orderby = '';
		$limit = 'LIMIT 0, 1';

		$query = $this->db->prepare( "SELECT {$select} FROM {$from} {$join} {$where} {$orderby} {$limit};", $mls_number );
		$count = $this->db->get_col( $query );
		if( is_array( $count ) )
			return reset( $count );
		else
			return $count;
	}
}
