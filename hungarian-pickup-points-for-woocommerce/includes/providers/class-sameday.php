<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Sameday {
	public $api_url = 'https://api.sameday.hu/api/';
	public $username = '';
	public $password = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();

	public function __construct() {
		$this->username = VP_Woo_Pont_Helpers::get_option('sameday_username');
		$this->password = VP_Woo_Pont_Helpers::get_option('samdeday_password');

		if(VP_Woo_Pont_Helpers::get_option('sameday_dev_mode', 'no') == 'yes') {
			$this->api_url = 'https://sameday-api-hu.demo.zitec.com/api/';
		}

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_sameday', array($this, 'get_settings'));

		//Ajax function on the settings page
		add_action( 'wp_ajax_vp_woo_pont_reload_sameday_pickup_point', array( $this, 'reload_pickup_points' ) );

		//Set supported statuses
		$this->package_statuses = array(
			1 => __("Kiállított fuvarokmány", 'vp-woo-pont'),
			4 => __("Beérkezett futárrendelés", 'vp-woo-pont'),
			6 => __("Sameday raktárában", 'vp-woo-pont'),
			7 => __("Tranzitban a raktár felé", 'vp-woo-pont'),
			9 => __("Sikeresen kézbesítve", 'vp-woo-pont'),
			33 => __("Szállítási folyamatban a futárnál", 'vp-woo-pont'),
			37 => __("A központi raktárban", 'vp-woo-pont'),
			56 => __("Tranzitban a raktár felé(Kilépés a hubból)", 'vp-woo-pont'),
			78 => __("Szekrénybe behelyezve", 'vp-woo-pont'),
			84 => __("Csomagok a raktárban", 'vp-woo-pont'),
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array(6, 7, 37, 56, 84),
			'delivery' => array(33, 78),
			'delivered' => array(9),
			'errors' => array()
		);

	}

	public function get_settings($settings) {
		$sameday_settings = array(
			array(
				'title' => __( 'Sameday settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('Username', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'sameday_username',
			),
			array(
				'title' => __('Password', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'samdeday_password',
			),
			array(
				'title' => __( 'Pickup point', 'vp-woo-pont' ),
				'type' => 'select',
				'class' => 'chosen_select test',
				'options' => $this->get_pickup_points(),
				'desc' => __("Sign in on eawb.sameday.hu to create a new pickup point. This is where the carrier will collect your shipments.", 'vp-woo-pont' ),
				'id' => 'sameday_pickup_point',
			),
			array(
				'title' => __( 'Personal drop-off', 'vp-woo-pont' ),
				'type' => 'checkbox',
				'default' => 'no',
				'desc' => __("Check this if you bring your packages to a pickup point in person.", 'vp-woo-pont' ),
				'id' => 'sameday_personal_dropoff',
			),
			array(
				'title'    => __( 'Enable DEV mode', 'vp-woo-pont' ),
				'type'     => 'checkbox',
				'id' 	 => 'sameday_dev_mode',
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Package type', 'vp-woo-pont' ),
				'default' => 'XS',
				'options' => array(
					0 => __( 'Package', 'vp-woo-pont' ),
					1 => __( 'Small package', 'vp-woo-pont' ),
					2 => __( 'Large package', 'vp-woo-pont' ),
				),
				'id' => 'sameday_package_type',
			),
			array(
				'type' => 'text',
				'title' => __( 'Default package weight(kg)', 'vp-woo-pont' ),
				'default' => '1',
				'desc' => __('The weight is a required parameter. If it is missing, this value will be used instead. Enter a value in kg.', 'vp-woo-pont'),
				'id' => 'sameday_default_weight',
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A6',
				'options' => array(
					'A4' => __( 'A4', 'vp-woo-pont' ),
					'A6' => __( 'A6(recommended)', 'vp-woo-pont' ),
				),
				'id' => 'sameday_sticker_size',
			),
			array(
				'type' => 'text',
				'title' => __('Insurance maximum limit', 'vp-woo-pont'),
				'desc_tip' => __('Leave it empty if theres no max limit(it will use the order total).', 'vp-woo-pont'),
				'default' => '',
				'id' => 'sameday_insurance_limit'
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$sameday_settings;
	}

	public function get_city_info($token, $country, $postcode, $city) {
		$params = '?countryCode='.$country.'&postalCode='.$postcode;
		if($city) $params = '?countryCode='.$country.'&name='.$city;

		$cityrequest = wp_remote_get($this->api_url.'geolocation/city'.$params, array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'X-AUTH-TOKEN' => $token
			)
		));

		if( is_wp_error( $cityrequest ) ) {
			return $cityrequest;
		}

		//Get body
		$citydata = wp_remote_retrieve_body( $cityrequest );

		//Try to convert into json
		$citydata = json_decode( $citydata, true );

		//Check if json exists
		if($citydata === null) {
			return new WP_Error( 'sameday_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		return $citydata;
	}

	public function create_label($data) {

		//Get package weight in gramms
		if(!$data['package']['weight']) {
			$data['package']['weight'] = VP_Woo_Pont_Helpers::get_option('sameday_default_weight', 1);
		} else {
			$data['package']['weight'] = wc_get_weight($data['package']['weight'], 'kg');
		}

		//If manually generated, use submitted weight instead
		if(isset($data['options']) && isset($data['options']['package_weight']) && $data['options']['package_weight'] > 0) {
			$data['package']['weight'] = $data['options']['package_weight']/1000;
		}

		//Get order
		$order = $data['order'];

		//Get auth token
		$token = $this->get_access_token();

		//If no auth token, wrong api keys or sometihng like that
		if(is_wp_error($token)) {
			return $token;
		}

		//Get city info
		$country = $order->get_shipping_country();
		$citydata = $this->get_city_info($token, $country, $order->get_shipping_postcode(), false);

		//Check for errors
		if( is_wp_error( $citydata ) ) {
			VP_Woo_Pont()->log_error_messages($citydata, 'sameday-geolocate');
			return $citydata;
		}

		//Get city info if not present yet, try with the city name instead of the postcode
		if(isset($citydata['data']) && empty($citydata['data'])) {
			$citydata = $this->get_city_info($token, $country, $order->get_shipping_postcode(), $order->get_shipping_city());
			if( is_wp_error( $citydata ) ) {
				VP_Woo_Pont()->log_error_messages($citydata, 'sameday-geolocate');
				return $citydata;
			}
		}

		//Get city info
		$city = $citydata['data'][0];

		//Setup label data
		$item = array(
			'pickupPoint' => VP_Woo_Pont_Helpers::get_option('sameday_pickup_point'),
			'packageType' => VP_Woo_Pont_Helpers::get_option('sameday_package_type', 0),
			'packageNumber' => 1,
			'packageWeight' => $data['package']['weight'],
			'service' => 7, //NextDay 24H service, TODO support other services
			'awbPayment' => 1,
			'cashOnDelivery' => 0,
			'insuredValue' => $data['package']['total'],
			'thirdPartyPickup' => 0,
			'awbRecipient' => array(
				'name' => $data['customer']['name'],
				'phoneNumber' => $data['customer']['phone'],
				'personType' => 0,
				'postalCode' => $order->get_shipping_postcode(),
				'address' => implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2())),
				'county' => $city['county']['id'],
				'city' => $city['id'],
				'email' => $data['customer']['email']
			),
			'clientInternalReference' => $data['reference_number'],
			'observation' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'sameday', 40),
			'parcels' => array(
				array(
					'weight' => $data['package']['weight'],
					//'isLast' => 0
				)
			),
			'currency' => $data['package']['currency'],
			'geniusOrder' => 0,
			'orderNumber' => $data['order_number']
		);

		//Insurance limit
		if(VP_Woo_Pont_Helpers::get_option('sameday_insurance_limit') && $data['package']['total'] > VP_Woo_Pont_Helpers::get_option('sameday_insurance_limit')) {
			$item['insuredValue'] = VP_Woo_Pont_Helpers::get_option('sameday_insurance_limit');

			if(VP_Woo_Pont_Helpers::get_option('sameday_insurance_limit') == 1) {
				$item['insuredValue'] = 0;
			}
		}

		//If package count set
		if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
			$packages =  $data['options']['package_count'];
			$item['parcels'] = array();
			$item['packageNumber'] = $packages;
			
			//Divide insurance and weight values
			$single_weight = $data['package']['weight']/$packages;

			//Create extra items
			for ($i = 0; $i < $packages; $i++){
				$item['parcels'][] = array(
					'weight' => $single_weight
				);
			}

		}

		//For pickup points
		if($data['point_id']) {
			$item['lockerId'] = $data['point_id'];
			$item['service'] = 15; //Simple locker service, TODO support other services
		}

		//Check for COD
		if($data['package']['cod']) {
			$item['cashOnDelivery'] = $data['package']['total'];
		}

		//Check for personal drop off
		if(VP_Woo_Pont_Helpers::get_option('sameday_personal_dropoff', 'no') == 'yes') {
			$item['serviceTaxes'] = array('PDO');
		}

		//So developers can modify
		$options = apply_filters('vp_woo_pont_sameday_label', $item, $data);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'sameday-create-label');

		//Submit request
		$request = wp_remote_post( $this->api_url.'awb', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-AUTH-TOKEN' => $token
			),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for HTTP errors
		if(wp_remote_retrieve_response_code( $request ) != 201) {
			VP_Woo_Pont()->log_error_messages($response, 'sameday-create-label');
			if(isset($response['message'])) {
				return new WP_Error( 'sameday_error_unknown', $response['message'] );
			} else {
				return new WP_Error( 'sameday_error_unknown', __('Unknown error', 'vp-woo-pont') );
			}
		}

		//Check for awbNumber, of exists, it was a success
		if(!isset($response['awbNumber'])) {
			VP_Woo_Pont()->log_error_messages($response, 'sameday-create-label');
			return new WP_Error( 'sameday_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Else, it was successful
		$parcel_number = $response['awbNumber'];

		//Next, generate the PDF label
		$label_size = VP_Woo_Pont_Helpers::get_option('sameday_sticker_size', 'A6');
		$request = wp_remote_get( $response['pdfLink'].'/'.$label_size, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-AUTH-TOKEN' => $token
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );

		//Check for errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			$response = json_decode( $response, true );
			VP_Woo_Pont()->log_error_messages($response, 'sameday-download-label');
			return new WP_Error( $response['code'], $response['message'] );
		}

		//Now we have the PDF as base64, save it
		$pdf = $response;

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('sameday', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = $parcel_number;
		$label['number'] = $parcel_number;
		$label['pdf'] = $pdf_file['name'];

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {

		//Get auth token
		$token = $this->get_access_token();

		//If no auth token, wrong api keys or sometihng like that
		if(is_wp_error($token)) {
			return $token;
		}

		//Create request data
		VP_Woo_Pont()->log_debug_messages($data, 'sameday-void-label-request');

		//Submit request
		$request = wp_remote_request( $this->api_url.'awb/'.$data['parcel_number'], array(
			'method' => 'DELETE',
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-AUTH-TOKEN' => $token
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 204) {
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );

			//If INVALID_PARCEL_ID, that means it was deleted on foxpost already
			if($response['error']['code'] == 404) {
				$label = array();
				$label['success'] = true;
				return $label;
			} else {
				VP_Woo_Pont()->log_error_messages($response, 'sameday-delete-label');
				if($response && $response['error']) {
					return new WP_Error( 'sameday_error_'.$response['error']['code'], $response['error']['message'] );
				} else {
					return new WP_Error( 'sameday_error_unknown', __('Unknown error', 'vp-woo-pont') );
				}
			}
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'sameday-void-label-response');

		return $label;
	}

	public function get_tracking_link($parcel_number, $order = false) {
		return 'https://sameday.hu/#awb='.esc_attr($parcel_number);
	}

	public function get_access_token($refresh = false) {
		$access_token = get_transient( '_vp_woo_pont_sameday_access_token' );
		if(!$access_token || $refresh) {
			$access_token = false; //returns nothing on error
			$request = wp_remote_post($this->api_url.'authenticate', array(
				'headers' => array(
					'X-Auth-Username' => $this->username,
					'X-Auth-Password' => $this->password,
					'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
				),
				'body' => 'remember_me=1',
				'httpversion' => '1.1'
			));

			if(is_wp_error($request)) {
				VP_Woo_Pont()->log_error_messages($request, 'sameday-auth');
				return $request;
			} else {
				$response = json_decode( wp_remote_retrieve_body( $request ), true );
				if(!isset($response['token'])) {
					VP_Woo_Pont()->log_error_messages($request, 'sameday-auth');
					return new WP_Error( 'sameday_error_unknown', $response['error']['message'] );
				} else {
					$access_token = $response['token'];
					$expiration = strtotime($response['expire_at'])-time();
					set_transient( '_vp_woo_pont_sameday_access_token', $access_token, $expiration );
				}
			}
		}

		return $access_token;
	}

	public function get_pickup_points_api($refresh = false) {
		$pickup_points = get_transient('vp_woo_pont_sameday_pickup_points');

		if (!$pickup_points || $refresh) {

			if(!$this->password) {
				return array();
			}

			//Get auth token
			$token = $this->get_access_token(true);

			//If no auth token, wrong username/password or sometihng like that
			if(is_wp_error($token)) {
				return array();
			}

			//Get zip code ids - sameday stores the poscodes in a different format, so we need to convert that later into actual postcodes
			$request = wp_remote_get($this->api_url.'client/pickup-points', array(
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
					'X-AUTH-TOKEN' => $token
				)
			));

			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'sameday-get-pickup-points');
				return array();
			}

			//Get body
			$body = wp_remote_retrieve_body( $request );

			//Try to convert into json
			$json = json_decode( $body, true );

			//Check if json exists
			if($json === null) {
				return array();
			}

			//Create a simple array
			$pickup_points = array();
			if(isset($json['data']) && is_array($json['data'])) {
				foreach ($json['data'] as $pickup_point) {
					$pickup_points[$pickup_point['id']] = $pickup_point['alias'];
				}
			}

			//Save vat ids for a day
			set_transient('vp_woo_pont_sameday_pickup_points', $pickup_points, 60 * 60 * 24);
		}

		return $pickup_points;
	}

	//Get invoice blocks
	public function get_pickup_points() {
		$pickup_points = array();
		if(is_admin() && isset( $_GET['tab']) && $_GET['tab'] == 'shipping' && isset($_GET['section']) && $_GET['section'] == 'vp_carriers' && isset($_GET['carrier']) && $_GET['carrier'] == 'sameday') {
			$pickup_points = $this->get_pickup_points_api();
		}
		return $pickup_points;
	}

	//Refresh pickup points with ajax
	public function reload_pickup_points() {
		check_ajax_referer( 'vp-woo-pont-settings', 'nonce' );

		//True parameter, so it will refresh the data, won't return the stored one
		$pickup_points = $this->get_pickup_points_api(true);

		$blocks_array = array();
		foreach ($pickup_points as $block_id => $block_name) {
			$blocks_array[] = array('id' => $block_id, 'name' => $block_name);
		}

		//And return them as json
		wp_send_json_success($blocks_array);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Get auth token
		$token = $this->get_access_token();

		//If no auth token, wrong api keys or sometihng like that
		if(is_wp_error($token)) {
			return $token;
		}

		//Submit request
		$request = wp_remote_request( $this->api_url.'client/awb/'.$parcel_number.'/status', array(
			'method' => 'GET',
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-AUTH-TOKEN' => $token
			),
		));

		VP_Woo_Pont()->log_debug_messages($request, 'sameday-get-tracking-info');

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {

			//If INVALID_PARCEL_ID, that means it was deleted on foxpost already
			return new WP_Error( 'sameday_error_unknown', __('Unknown error', 'vp-woo-pont') );

		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$label = array();
		$label['success'] = true;

		//Check if empty response
		if(empty($response)) {
			return new WP_Error( 'sameday_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Collect events
		$tracking_info = array();
		foreach ($response['expeditionHistory'] as $event) {
			$comment = array($event['statusState'], $event['statusLabel'], $event['transitLocation'], $event['county']);
			$comment = implode( ', ', array_filter( $comment ) );
			$tracking_info[] = array(
				'date' => strtotime($event['statusDate']),
				'event' => $event['statusId'],
				'label' => $event['statusLabel'],
				'comment' => $comment
			);
		}

		return $tracking_info;
	}

}
