<?php

if( ! defined( 'ABSPATH' ) )
	exit;

if( defined( 'RETS_Sync_Listing_Job' ) )
	return;

class RETS_Sync_Listing_Job extends WP_Job {

	public $errors = array();

	public $id;

	public $mls_number;

	public $post_id;

	public $listing;

	public $gallery;

	public $rets_sync_db;

	function __construct( $id = NULL ){
		if( $id )
			$this->id = $id;
	}

	public function handle(){
		if( ! $this->init() )
			return false;

		if( ! $listing = $this->get_rets_listing() )
			return false;

		$this->listing = new RETS_Cloud_Adapter( $listing );

		if( ! $this->mls_number )
			$this->mls_number = $this->listing->get_mls_number();
		if( ! $this->id )
			$this->id = $this->listing->_get( 'id', '' );

		$this->sync_listing();
		$this->sync_photos();

		return true;
	}

	public function get_errors(){
		return $this->errors;
	}

	public function get_last_error(){
		return end( $this->errors );
	}

	public function init(){
		global $rets_sync_db;

		$this->prevent_from_stopping();

		if( ! $rets_sync_db )
			$rets_sync_db = new RETS_Sync_DB();

		if( ! $rets_sync_db ){
			$this->errors[] = 'Unable to initialize database.';
			return false;
		}

		$this->rets_sync_db = $rets_sync_db;

		return true;
	}

	public function get_rets_listing(){
		$listing = false;

		if( ! $this->id && ! $this->mls_number ){
			$this->errors[] = 'No lookup values exist.';
			return false;
		}

		if( $this->id )
			$listing = $this->rets_sync_db->get_listing( $this->id );

		if( ! $listing && $this->mls_number )
			$listing = $this->rets_sync_db->get_listing_by_mls( $this->mls_number );

		if( ! $listing )
			$this->errors[] = 'Could not find RETS listing.';

		return $listing;
	}

	public function sync_listing(){
		if( ! is_a( $this->listing, 'RETS_Cloud_Adapter' ) ){
			$this->errors[] = 'Listing is not a valid adapter.';
			return false;
		}

		$this->mls_number = $this->listing->get_mls_number();
		if( ! $this->id )
			$this->id = $this->listing->_get( 'id', '' );

		$the_listing_id = $this->locate_listing();
		$the_listing_meta = $this->fetch_meta();
		$the_listing_tax = array(); // coming soon...

		// Setup the Data for Insert or Update
		$the_listing_data = array(
			'post_type' => 'listing',
			'post_title' => $this->listing->get_full_address(),
			'post_status' => 'publish',
			'post_content' => $this->listing->get_remarks(),
			'post_excerpt' => $this->listing->get_directions(),
			'meta_input' => $the_listing_meta,
			//'tax_input' => $the_listing_tax
		);

		if( $post_date = $this->listing->get_listing_date() )
			$the_listing_data['post_date'] = $post_date;
		if( $post_modified = $this->listing->get_last_modified_date() )
			$the_listing_data['post_modified'] = $post_modified;

		if( ! $the_listing_id ){
			$the_listing_data['meta_input'][ RETSSYNC_META_PREFIX . 'db-id' ] = $this->id;

			// create a new listing
			$the_listing_id = wp_insert_post( $the_listing_data );
		} else {
			$the_listing_data['ID'] = $the_listing_id;
			// add price change check here.
			wp_update_post( $the_listing_data );
		}

		$this->post_id = $the_listing_id;

		return true;
	}

	public function fetch_meta(){
		if( ! is_a( $this->listing, 'RETS_Cloud_Adapter' ) )
			return false;

		$the_listing_meta = array();

		$meta = array(
			'mls' => $this->mls_number,
			'listing_class' => $this->listing->get_class(),
			'price' => $this->listing->get_price(),
			'sold_price' => $this->listing->get_sold_price(),
			'year_built' => $this->listing->get_year_built(),
			'building_description' => $this->listing->get_built_description(),
			'address_number' => $this->listing->get_address_number(),
			'address_street' => $this->listing->get_address_street(),
			'address_direction' => $this->listing->get_address_direction(),
			'address' => $this->listing->get_address(),
			'address2' => $this->listing->get_address2(),
			'city' => $this->listing->get_city(),
			'state' => $this->listing->get_state(),
			'zip' => $this->listing->get_zip(),
			'county' => $this->listing->get_county(),
			'subdivision' => $this->listing->get_subdivision(),
			'area' => $this->listing->get_area(),
			'status' => $this->listing->get_status(),
			'acres' => $this->listing->get_acres(),
			//'sqft' => $this->listing->get_sqft(),
			'number_bedrooms' => $this->listing->get_number_bedrooms(),
			'number_levels' => $this->listing->get_number_levels(),
			'rooms' => $this->listing->get_rooms(),
			'levels' => $this->listing->get_levels(),
			'number_total_bathrooms' => $this->listing->get_number_bathrooms(),
			'number_full_bathrooms' => $this->listing->get_number_full_bathrooms(),
			'number_half_bathrooms' => $this->listing->get_number_half_bathrooms(),
			'number_garage_spaces' => $this->listing->get_number_garage_spaces(),
			'number_main_garage_spaces' => $this->listing->get_number_main_garage_spaces(),
			'number_basement_garage_spaces' => $this->listing->get_number_basement_garage_spaces(),
			'number_carport_garage_spaces' => $this->listing->get_number_carport_spaces(),
			'latitude' => $this->listing->get_latitude(),
			'longitude' => $this->listing->get_longitude(),
			'listing_agent_id' => $this->listing->get_listing_agent_id(),
			'listing_agent_first_name' => $this->listing->get_listing_agent_first_name(),
			'listing_agent_last_name' => $this->listing->get_listing_agent_last_name(),
			'listing_agent_email' => $this->listing->get_listing_agent_email(),
			'listing_agent_phone' => $this->listing->get_listing_agent_phone(),
			'listing_agent_phone_type' => $this->listing->get_listing_agent_phone_type(),
			'listing_agent_phone_ext' => $this->listing->get_listing_agent_phone_extension(),
			'listing_office_id' => $this->listing->get_listing_office_id(),
			'listing_office' => $this->listing->get_listing_office_organization_name(),
			'listing_office_phone' => $this->listing->get_listing_office_organization_phone(),
			'listing_office_email' => $this->listing->get_listing_office_organization_email(),
			'has_split_foyer' => $this->listing->has_split_foyer(),
			'is_split_level' => $this->listing->is_split_level(),
			'is_tri_level' => $this->listing->is_tri_level(),
			'has_pool' => $this->listing->has_pool(),
			'pool' => $this->listing->get_pool(),
			'virtual_tour' => $this->listing->get_virtual_tour(),
			'has_attic' => $this->listing->has_attic(),
			'has_decks' => $this->listing->has_decks(),
			'has_patio' => $this->listing->has_patio(),
			'has_garden_patio' => $this->listing->has_garden_patio(),
			'is_historic' => $this->listing->is_historic(),
			'is_loft' => $this->listing->is_loft(),
			'is_log_home' => $this->listing->is_log_home(),
			'has_view' => $this->listing->has_view(),
			'is_waterfront' => $this->listing->is_waterfront(),
			'waterfront' => $this->listing->get_waterfront(),
			'water_heater' => $this->listing->get_water_heater(),
			'elementary_school' => $this->listing->get_elementary_school(),
			'intermediate_school' => $this->listing->get_intermediate_school(),
			'middle_school' => $this->listing->get_middle_school(),
			'high_school' => $this->listing->get_high_school(),
			'display_address' => $this->listing->display_address(),
		);

		foreach( $meta as $key => $val ){
			if( $val !== '' && is_bool( $val ) )
				$val = $val ? 'Yes' : 'No';
			$the_listing_meta[ RETSSYNC_META_PREFIX . $key ] = $val;
		}

		return $the_listing_meta;
	}

	public function prevent_from_stopping(){
		set_time_limit( MINUTE_IN_SECONDS * 10 );
		ini_set( 'memory_limit', '256M' );
	}

	public function sync_photos(){
		if( ! $this->mls_number )
			return;

		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$synced = 0;
		$first = false;

		if( in_array( 'ftp', $this->rets_sync_db->images ) ){
			$photos = $this->rets_sync_db->get_photos_from_ftp( $this->mls_number );
			if( ! count( $photos ) ){
				$this->errors[] = 'No photos to sync.';
				if( $this->rets_sync_db->errors ){
					$this->errors = $this->errors + $this->rets_sync_db->errors;
				}
				return false;
			}

			foreach( $photos as $photo ){
				$photo_id = $this->locate_image_id( $photo['url'] );

				if( ! $photo_id )
					$photo_id = $this->lookup_image_by_original( $photo['source'] );

				if( ! $photo_id ){
					$photo_id = $this->handle_media( $photo['url'], $photo['path'], $this->post_id );
					update_post_meta( $photo_id, '_original_source', $photo['source'] );
				}

				// set Featured Image
				if( ! $first ){
					$first = $photo_id;
					set_post_thumbnail( $this->post_id, $photo_id );
					$synced++;
					continue;
				}

				// add to meta field
				if( $this->add_media_to_gallery( $photo_id ) ){
					$synced++;
				}
			}
		}

		if( in_array( 'db', $this->rets_sync_db->images ) ){
			$photos = $this->rets_sync_db->get_photos_from_db( $this->mls_number );
			$synced += $this->handle_photos_by_url( $photos );
		}

		if( in_array( 'url', $this->rets_sync_db->images ) ){
			$photos = $this->rets_sync_db->get_photos_from_url( $this->mls_number );
			$synced += $this->handle_photos_by_url( $photos );
		}

		return $synced;
	}

	public function handle_media( $url, $path, $post_id ){
		$image_meta = @wp_read_image_metadata( $path );

		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
			$title = $image_meta['title'];
		}

		if ( trim( $image_meta['caption'] ) ) {
			$excerpt = $image_meta['caption'];
		}

		$name = basename( $file );
		$name_parts = pathinfo( $name );
		$title = trim( substr( $name, 0, -( 1 + strlen( $name_parts['extension'] ) ) ) );

		$attachment = array(
			'post_mime_type' => mime_content_type( $name ),
			'guid' => $url,
			'post_parent' => $post_id,
			'post_title' => $title
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $path, $post_id );
		if ( ! is_wp_error( $id ) )
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $path ) );

		return $id;
	}

	public function handle_photos_by_url( $photos ){
		foreach( $photos as $index => $photo ){
			if( ! $this->check_remote_image( $photo ) ){
				unset( $photos[ $index ] );
			}
		}

		if( count( $photos ) ){

			$synced = 0;
			$first = false;

			foreach( $photos as $photo ){
				// check if the image is already in our system
				$photo_id = $this->lookup_image_by_original( $photo );

				if( ! $photo_id ){ // import into media library
					// this will return the media's full URL
					$src = media_sideload_image( $photo, $this->post_id, NULL, 'src' );

					if( is_wp_error( $src ) )
						continue;

					$photo_id = $this->locate_image_id( $src );

					if( ! $photo_id )
						continue;

					// store the source
					update_post_meta( $photo_id, '_original_source', $photo );
				}

				// set Featured Image
				if( ! $first ){
					$first = $photo_id;
					set_post_thumbnail( $this->post_id, $photo_id );
					$synced++;
					continue;
				}

				// add to meta field
				if( $this->add_media_to_gallery( $photo_id ) ){
					$synced++;
				}
			}
		}

		return $synced;
	}

	public function locate_listing(){
		$query = false;

		// check for a matching DB-ID first
		if( $this->id ){
			$query_args = array(
				'post_type' => 'listing',
				'posts_per_page' => 1,
				'post_status' => 'publish',
				'meta_query' => array(
					array(
						'key' => RETSSYNC_META_PREFIX . 'db-id',
						'value' => $this->id,
					)
				)
			);

			$query = new WP_Query( $query_args );
		}

		// if DB-ID check failed, lookup by MLS number to prevent duplicates
		if( ( ! $query || ! $query->have_posts() ) && $this->mls_number ){
			$query_args['meta_query'] = array(
				array(
					'key' => RETSSYNC_META_PREFIX . 'mls',
					'value' => $this->mls_number,
				)
			);
			$query = new WP_Query( $query_args );
		}

		if( $query && $query->have_posts() )
			return $query->posts[0]->ID;

		$this->errors[] = 'Could not match listing to a post.';

		return false;
	}

	public function locate_image_id( $src ){
		if( $src_id = url_to_postid( $src ) )
			return $src_id;

		global $wpdb;
		$lookup_id = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s;", $src );

		if( $src_id = $wpdb->get_var( $lookup_id ) )
			return $src_id;

		$cloud_base_url = 'https://storage.googleapis.com/ray-poynor-website-media/';
		$cloud_src = str_replace( WP_HOME . '/site/uploads/', $cloud_base_url, $src );
		$lookup_id = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s;", $cloud_src );

		if( $src_id = $wpdb->get_var( $lookup_id ) )
			return $src_id;

		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'number_posts' => 1,
			'post_status' => 'inherit',
			'post_parent' => $this->post_id,
			'orderby' => 'post_date',
			'order' => 'DESC',
		));

		// returns the id of the image
		return $attachments[0]->ID;
	}

	public function lookup_image_by_original( $original_url ){
		$lookup = new WP_Query( array(
			'post_type' => 'attachment',
			'posts_per_page' => 1,
			'post_status' => 'inherit',
			'meta_query' => array(
				array(
					'key' => '_original_source',
					'value' => $original_url
				)
			)
		));

		if( ! $lookup->have_posts() )
			return false;

		return $lookup->posts[0]->ID;
	}

	public function add_media_to_gallery( $media_id ){

		$media_id = (int) $media_id;

		if( ! $this->gallery ){
			$this->gallery = get_field( '_listing_photos', $this->post_id  );
			$new_gallery = is_array( $this->gallery ) ? wp_list_pluck( $this->gallery, 'ID' ) : array();
		} else {
			$new_gallery = $this->gallery;
		}

		if( in_array( $media_id, $new_gallery ) )
			return false;

		$new_gallery[] = $media_id;

		update_field( 'field_56dd8ab24d47c', $new_gallery, $this->post_id );
		$this->gallery = $new_gallery;

		return true;
	}

	public function check_remote_image( $url ){
		return getimagesize( $url );

		// ALL CURL attempts return 0, not working...
		$valid = false;

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		// don't download content
		curl_setopt( $ch, CURLOPT_NOBODY, 1 );
		curl_setopt( $ch, CURLOPT_FAILONERROR, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		if( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) == 200 ){
		    $valid = true;
		}
		curl_close( $ch );

		return $valid;

		# Old method
		//return ( curl_exec($ch) !== FALSE );

		# Old method
		// return @file_get_contents( $url, 0, NULL, 0, 1 );
	}

}
