<?php

if( ! defined( 'ABSPATH' ) )
	exit();

if( defined( 'RETS_Cloud_Adapter' ) )
	return;

class RETS_Cloud_Adapter {

	/**
	 * RETS Base Object
	 * @var object
	 */
	public $obj;

	/**
	 * Return Date Format
	 * @var string
	 */
	public $date_format = 'Y-m-d H:i:s';

	public $rooms = array();

	public $levels = array();

	public function __construct( $listing_obj ){
		$this->rooms = array(
			1 => 'LM_Char10_20',
			2 => 'LM_Char10_21',
			3 => 'LM_Char10_22',
			4 => 'LM_Char10_23',
			5 => 'LM_Char10_24',
			6 => 'LM_Char10_25',
			7 => 'LM_Char10_26',
			8 => 'LM_Char10_27',
			9 => 'LM_Char10_28',
			10 => 'LM_Char10_29',
			11 => 'LM_Char10_30',
			12 => 'LM_Char10_31',
			13 => 'LM_Char10_32',
			14 => 'LM_Char10_33',
			15 => 'LM_Char10_34',
		);

		$this->levels = array(
			1 => 'LM_char1_40',
			2 => 'LM_char1_41',
			3 => 'LM_char1_42',
			4 => 'LM_char1_43',
			5 => 'LM_char1_44',
			6 => 'LM_char1_45',
			7 => 'LM_char1_46',
			8 => 'LM_char1_47',
			9 => 'LM_char1_48',
			10 => 'LM_char1_49',
			11 => 'LM_char1_50',
			12 => 'LM_char1_51',
			13 => 'LM_char1_52',
			14 => 'LM_char1_53',
			15 => 'LM_char1_54',
		);

		$this->obj = $listing_obj;
	}

	private function _get( $attr, $prefix = 'field_' ){
		$property = $prefix . $attr;
		if( ! isset( $this->obj->$property ) )
			return false;
		return trim( $this->obj->$property );
	}

	private function _bool( $value ){
		if( $value == 'Y' || $value == 'Yes' )
			return true;
		return false;
	}

	private function _date( $value ){
		if( strtotime( $value ) === false )
			return false;

		return date_i18n( $this->date_format, strtotime( $value ) );
	}

	private function _get_bool( $attr, $prefix = 'field_' ){
		return $this->_bool( $this->_get( $attr, $prefix ) );
	}

	private function _get_date( $attr, $prefix = 'field_' ){
		return $this->_date( $this->_get( $attr, $prefix ) );
	}

	private function _get_most_recent_date( $dates = array() ){
		$most_recent = 0;
		foreach( $dates as $date ){
			$current = strtotime( $date );
			if ( $current > $most_recent ) {
				$most_recent = $current;
			}
		}
		return $most_recent;
	}

	public function allow_comments(){
		return $this->_get_bool( 'LV_vow_comment' );
	}

	public function display_address(){
		return $this->_get_bool( 'LV_vow_address' );
	}

	public function get_full_address(){
		$full_address = $this->get_address();

		if( ! $full_address )
			$full_address = trim( $this->get_address_number() . ' ' . $this->get_address_street() );

		$address2 = $this->get_address2() ? ' ' . $this->get_address2() : '';
		$sep1 = $full_address || $address2 ? ', ' : '';

		$full_address .= $address2 . $sep1 . $this->get_city_state_zip();

		return $full_address;
	}

	public function get_area(){
		return $this->_get( 'L_Area' );
	}

	public function get_subdivision(){
		return $this->_get( 'LM_char10_35' );
	}

	public function get_address(){
		$address = $this->_get( 'L_Address' );
		if( ! $address )
			$address = trim( $this->get_address_number() . ' ' . $this->get_address_street() );
		return $address;
	}

	public function get_address_direction(){
		$direction = $this->_get( 'L_AddressDirection' );
		if( ! $direction )
			$direction = $this->get_address_post_direction();
		return $direction;
	}

	public function get_address_post_direction(){
		return $this->_get( 'L_AddressPostDirection' );
	}

	public function get_address_number(){
		return $this->_get( 'L_AddressNumber' );
	}

	public function get_address2(){
		return $this->_get( 'L_Address2' );
	}

	public function get_address_street(){
		return $this->_get( 'L_AddressStreet' );
	}

	public function get_city(){
		return $this->_get( 'L_City' );
	}

	public function get_state(){
		return $this->_get( 'L_State' );
	}

	public function get_zip(){
		return $this->_get( 'L_Zip' );
	}

	public function get_city_state_zip(){
		$city = $this->get_city();
		$state = $this->get_state();
		$zip = $this->get_zip();
		$sep = $city && ( $state || $zip ) ? ', ' : '';

		return $city . $sep . trim( $state . ' ' . $zip );
	}

	public function get_county(){
		return $this->_get( 'LM_Char10_16' );
	}

	public function get_class(){
		$class = $this->_get( 'Class', '' );
		if( ! $class )
			$class = $this->_get( 'L_Class' );
		return $class;
	}

	/** Underscore after Type is on purpose */
	public function get_type(){
		return $this->_get( 'L_Type_' );
	}

	public function get_property_type(){
		return $this->get_type();
	}

	public function get_listing_date(){
		$listing_date = $this->_get_date( 'L_InputDate' );
		if( ! $listing_date )
			$listing_date = $this->_get_date( 'L_UpdateDate' );
		if( ! $listing_date )
			$listing_date = $this->_get_date( 'ModifiedTime', 'rc_' );

		return $listing_date;
	}

	public function get_closing_date(){
		return $this->_get_date( 'L_ClosingDate' );
	}

	public function get_last_modified_date(){
		$dates = array(
			'updated' => $this->_get_date( 'L_UpdateDate' ),
			'photo_updated' => $this->_get_date( 'L_Last_Photo_updt' ),
			'modified' => $this->_get_date( 'ModifiedTime', 'rc_' )
		);

		return $this->_get_most_recent_date( $dates );
	}

	public function get_picture_count(){
		return $this->_get( 'L_PictureCount' );
	}

	public function get_remarks(){
		return $this->_get( 'LR_remarks77' );
	}

	public function get_notes(){
		return $this->get_remarks();
	}

	public function get_directions(){
		return $this->_get( 'LR_remarks33' );
	}

	public function get_mls_number(){
		$mls_number = $this->_get( 'L_ListingID' );
		if( ! $mls_number )
			$mls_number = $this->_get( 'L_DisplayId' );
		return $mls_number;
	}

	public function get_listing_id(){
		return $this->get_mls_number();
	}

	public function get_price(){
		return $this->_get( 'L_AskingPrice' );
	}

	public function get_price_date(){
		return $this->_get_date( 'L_PriceDate' );
	}

	public function get_list_price(){
		return $this->get_price();
	}

	public function get_sold_price(){
		return $this->_get( 'L_SoldPrice' );
	}

	public function get_status(){
		return $this->_get( 'L_Status' );
	}

	public function get_status_category(){
		return $this->_get( 'L_StatusCatID' );
	}

	/*public function get_sqft(){
		return $this->_get( '' );
	}*/

	public function get_acres(){
		return $this->_get( 'L_NumAcres' );
	}

	public function get_listing_agent_id(){
		$agent_id = $this->_get( 'L_ListAgent1' );
		if( ! $agent_id )
			$agent_id = $this->_get( 'LA1_AgentID' );
		return $agent_id;
	}

	public function get_listing_agent_first_name(){
		return $this->_get( 'LA1_UserFirstName' );
	}

	public function get_listing_agent_last_name(){
		return $this->_get( 'LA1_UserLastName' );
	}

	public function get_listing_agent_mi(){
		return $this->_get( 'LA1_UserMI' );
	}

	public function get_listing_agent_email(){
		return strtolower( $this->_get( 'LA1_Email' ) );
	}

	public function get_listing_agent_phone(){
		return $this->_get( 'LA1_PhoneNumber1' );
	}

	public function get_listing_agent_phone_type(){
		return $this->_get( 'LA1_PhoneNumber1Desc' );
	}

	public function get_listing_agent_phone_country_code(){
		return $this->_get( 'LA1_PhoneNumber1CountryCodeId' );
	}

	public function get_listing_agent_phone_extension(){
		return $this->_get( 'LA1_PhoneNumber1Ext' );
	}

	public function get_listing_office_id(){
		return $this->_get( 'L_ListOffice1' );
	}

	public function get_listing_office_organization_id(){
		return $this->_get( 'LO1_HiddenOrgID' );
	}

	public function get_listing_office_shortname(){
		return $this->_get( 'LO1_ShortName' );
	}

	public function get_listing_office_organization_name(){
		return $this->_get( 'LO1_OrganizationName' );
	}

	public function get_listing_office_organization_phone(){
		return $this->_get( 'LO1_PhoneNumber1' );
	}

	public function get_listing_office_organization_email(){
		return strtolower( $this->_get( 'LO1_EMail' ) );
	}

	public function get_selling_agent_id(){
		return $this->_get( 'L_SellingAgent1' );
	}

	public function get_selling_agent_first_name(){
		return $this->_get( 'SA1_UserFirstName' );
	}

	public function get_selling_agent_last_name(){
		return $this->_get( 'SA1_UserLastName' );
	}

	public function get_selling_office_id(){
		return $this->_get( 'L_SellingOffice1' );
	}

	public function get_selling_office_shortname(){
		return $this->_get( 'SO1_ShortName' );
	}

	public function get_latitude(){
		return $this->_get( 'LMD_MP_Latitude' );
	}

	public function get_longitude(){
		return $this->_get( 'LMD_MP_Longitude' );
	}

	public function has_pool(){
		return $this->_get_bool( 'LM_char1_38' );
	}

	public function get_pool(){
		return $this->_get( 'LM_Char10_19' );
	}

	public function get_virtual_tour(){
		return $this->_get( 'VT_VTourURL' );
	}

	public function has_attic(){
		return $this->_get_bool( 'LM_Char1_4' );
	}

	public function has_decks(){
		return $this->_get_bool( 'LM_Char1_13' );
	}

	public function get_number_levels(){
		return $this->_get_bool( 'LM_Char10_1' );
	}

	public function get_elementary_school(){
		return $this->_get( 'LM_Char10_5' );
	}

	public function get_intermediate_school(){
		return $this->_get( 'LM_Char10_10' );
	}

	public function get_middle_school(){
		return $this->_get( 'LM_Char10_11' );
	}

	public function get_jr_high_school(){
		return $this->get_middle_school();
	}

	public function get_high_school(){
		return $this->_get( 'LM_Char10_9' );
	}

	public function get_pool_type(){
		return $this->get_pool();
	}

	public function get_rooms(){
		$rooms = array();
		foreach( $this->rooms as $room ){
			if( $val = $this->_get( $room ) )
				$rooms[] = $val;
		}

		return $rooms;
	}

	public function get_room( $room = 1 ){
		if( ! isset( $this->rooms[ $room ] ) )
			return false;

		return $this->_get( $this->rooms[ $room ] );
	}

	public function get_levels(){
		$levels = array();
		foreach( $this->levels as $level ){
			if( $val = $this->_get( $level ) )
				$levels[] = $val;
		}

		return $levels;
	}

	public function get_room_level_array(){
		$rooms = $this->get_rooms();
		$levels = $this->get_levels();
		$room_levels = array();

		foreach( $rooms as $k => $val ){
			$item = array(
				'room' => $val
			);
			if( isset( $levels[ $k ] ) )
				$item['level'] = $levels[ $k ];
			$room_levels[] = $item;
		}
		return $room_levels;
	}

	public function get_room_level( $room = 1 ){
		if( ! isset( $this->levels[ $room ] ) )
			return false;

		return $this->_get( $this->levels[ $room ] );
	}

	public function get_number_bedrooms(){
		return $this->_get( 'LM_Int1_1' );
	}

	public function get_number_fireplaces(){
		return $this->_get( 'LM_Int1_3' );
	}

	public function get_number_full_bathrooms(){
		return $this->_get( 'LM_Int1_4' );
	}

	public function get_number_half_bathrooms(){
		return $this->_get( 'LM_Int1_6' );
	}

	public function get_number_bathrooms(){
		return $this->_get( 'LM_Int1_8' );
	}

	public function get_condo_level(){
		return $this->_get( 'LM_Int1_9' );
	}

	public function get_number_garage_spaces(){
		return $this->_get( 'LM_Int2_1' );
	}

	public function get_number_basement_garage_spaces(){
		return $this->_get( 'LM_Int2_11' );
	}

	public function get_number_main_garage_spaces(){
		return $this->_get( 'LM_Int2_12' );
	}

	public function get_number_carport_spaces(){
		return $this->_get( 'LM_Int2_10' );
	}

	public function get_year_built(){
		return $this->_get( 'LM_Int2_5' );
	}

	public function get_built_description(){
		return $this->_get( 'LM_char10_38' );
	}

	public function has_garden_patio(){
		return $this->_get_bool( 'LM_char1_21' );
	}

	public function is_historic(){
		return $this->_get_bool( 'LM_char1_23' );
	}

	public function is_loft(){
		return $this->_get_bool( 'LM_char1_29' );
	}

	public function is_log_home(){
		return $this->_get_bool( 'LM_char1_30' );
	}

	public function has_view(){
		return $this->_get_bool( 'LM_char1_31' );
	}

	public function has_patio(){
		return $this->_get_bool( 'LM_char1_35' );
	}

	public function has_split_foyer(){
		return $this->_get_bool( 'LM_char1_56' );
	}

	public function is_split_level(){
		return $this->_get_bool( 'LM_char5_11' );
	}

	public function is_tri_level(){
		return $this->_get_bool( 'LM_char1_59' );
	}

	public function is_waterfront(){
		return $this->_get_bool( 'LM_char5_7' );
	}

	public function get_waterfront(){
		return $this->_get_bool( 'LM_char10_37' );
	}

	public function get_water_heater(){
		return $this->_get( 'LFD_WATERHEATER_43' );;
	}
}
