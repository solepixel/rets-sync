<?php

if( ! defined( 'ABSPATH' ) )
	exit;

if( defined( 'RETS_Sync_Listing_Details_Job' ) )
	return;

class RETS_Sync_Listing_Details_Job extends RETS_Sync_Listing_Job {

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

		return $this->sync_listing();
	}

}
