<?php

if( ! defined( 'ABSPATH' ) )
	exit;

if( defined( 'RETS_Sync_Listing_Images_Job' ) )
	return;

class RETS_Sync_Listing_Images_Job extends RETS_Sync_Listing_Job {

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

		if( ! $the_listing_id = $this->locate_listing() )
			return false;

		$this->post_id = $the_listing_id;

		return $this->sync_photos();
	}

}
