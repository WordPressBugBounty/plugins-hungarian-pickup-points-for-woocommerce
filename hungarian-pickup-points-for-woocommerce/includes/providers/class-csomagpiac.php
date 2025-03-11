<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Csomagpiac {
	protected $api_url = 'https://bestr.csomagpiac.hu/api/v1/';
	protected $api_token = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();

	public function __construct() {
		add_filter('vp_woo_pont_carrier_settings_csomagpiac', array($this, 'get_settings'));
        add_action('vp_woo_pont_update_csomagpiac_list', array( $this, 'get_csomagpiac_db' ));
        add_filter('vp_woo_pont_external_provider_groups', array($this, 'add_provider_group'));
        add_filter('vp_woo_pont_get_supported_providers_for_home_delivery', array($this, 'add_provider_group'));
        add_filter('vp_woo_pont_get_supported_providers', array($this, 'add_providers'));
        add_filter('vp_woo_pont_provider_subgroups', array($this, 'add_provider_subgroups'));
        add_filter('vp_woo_pont_import_csomagpiac_manually', array( $this, 'get_csomagpiac_db_manually'));
		add_action( 'wp_ajax_vp_woo_pont_reload_csomagpiac_pickup_point', array( $this, 'reload_pickup_points' ) );

		if(VP_Woo_Pont_Helpers::get_option('csomagpiac_dev_mode', 'no') == 'yes') {
			$this->api_url = 'https://demo.csomagpiac.hu/api/v1/';
		}

		//Set supported statuses
		$this->package_statuses = array(
			'1' => 'Rögzítve',
			'2' => 'Címke nyomtatva',
			'4' => 'Feladótól átvéve',
			'5' => 'Szállítás alatt',
			'6' => 'Depóban',
			'8' => 'Kiszállítás alatt',
			'9' => 'Depóban várakozás',
			'10' => 'Sikertelen kézbesítés',
			'11' => 'Átadva partner futárnak',
			'98' => 'Visszaküldve',
			'99' => 'Kézbesítve',
			'100' => 'DPD kezeli',
			'101' => 'Törlésre',
			'103' => 'Sameday kezeli'
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array('4', '5', '6', '9', '11', '100', '103'),
			'delivery' => array('8'),
			'delivered' => array('99'),
			'errors' => array('101', '10', '98')
		);

		$this->api_token = VP_Woo_Pont_Helpers::get_option('csomagpiac_api_token');
	}

	public function get_settings($settings) {
		$csomagpiac_settings = array(
			array(
				'title' => __( 'Csomagpiac settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('API Token', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __("Enter your account's API token.", 'vp-woo-pont'),
				'id' => 'csomagpiac_api_token'
			),
			array(
				'title' => __('Shop ID', 'vp-woo-pont'),
				'type' => 'text',
				'default'   => trim( str_replace( array( 'http://', 'https://' ), '', get_bloginfo('url') ), '/' ),
				'desc' => __("Enter if you have multiple stores connected to Csomagpiac.", 'vp-woo-pont'),
				'id' => 'csomagpiac_shop_id'
			),
			array(
				'title' => __('Pickup Point', 'vp-woo-pont'),
				'type' => 'select',
				'class' => 'chosen_select test',
				'options' => $this->get_pickup_points(),
				'desc' => __("Select your shipment's pickup point(available after you enter your API token)", 'vp-woo-pont' ),
				'id' => 'csomagpiac_pickup_point'
			),
			array(
				'title' => __('Developer mode', 'vp-woo-pont'),
				'type' => 'checkbox',
				'id' => 'csomagpiac_dev_mode'
			),
			array(
				'type' => 'multiselect',
				'title' => __( 'Enabled services', 'vp-woo-pont' ),
				'class' => 'wc-enhanced-select',
				'default' => array(),
				'options' => array(
					'email' => 'E-Mail notification',
					'sms' => 'SMS notification'
				),
				'id' => 'csomagpiac_extra_services'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A6',
				'options' => array(
					'A6' => __( 'A6 on A4(recommended)', 'vp-woo-pont' ),
					'a6' => __( 'A6', 'vp-woo-pont' ),
				),
				'id' => 'csomagpiac_sticker_size'
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$csomagpiac_settings;
	}

    public function get_csomagpiac_db() {

		//URL of the database
		$xml_url = 'https://bestr.csomagpiac.hu/pdpoints.xml';

		//Get XML file
		$response = wp_remote_get($xml_url);

		//Check for errors
		if( is_wp_error( $response ) || !is_array($response) ) {
			VP_Woo_Pont()->log_error_messages($response, 'csomagpiac-import-points');
			return false;
		}

		//Create a new json
		$results = array('dpd' => array(), 'sameday' => array(), 'mpl_postapont' => array(), 'mpl_posta' => array(), 'mpl_automata' => array());

		// Get the XML content from the response
		$xml_content = wp_remote_retrieve_body($response);

		// Parse the XML content into a SimpleXML object
		$xml = simplexml_load_string($xml_content);

		//Validate XML
		if ($xml === false) {
			VP_Woo_Pont()->log_error_messages($response, 'csomagpiac-import-points');
			return false;
		}

		foreach ($xml->pdpoint as $pdpoint) {

			//Provider(dpd, sameday, etc...)
			$handler = (string)$pdpoint->handler;

			if($handler == 'mpl') {
				if (in_array($pdpoint->typeId, [4,6,7])) $handler = 'mpl_postapont';
				if (in_array($pdpoint->typeId, [3])) $handler = 'mpl_posta';
				if (in_array($pdpoint->typeId, [5])) $handler = 'mpl_automata';		
			}

			//Create result
			$result = array(
				'id' => (string)$pdpoint->id,
				'lat' => number_format((string)$pdpoint->lat, 5, '.', ''),
				'lon' => number_format((string)$pdpoint->long, 5, '.', ''),
				'zip' => (string)$pdpoint->zip,
				'addr' => (string)$pdpoint->address,
				'city' => (string)$pdpoint->city,
				'name' => (string)$pdpoint->name
			);

			//Save the ones we support
			if(isset($results[$handler])) {
				$results[$handler][] = $result;
			}

		}

		//Save stuff
		$saved_files = array();
		foreach ($results as $type => $points) {
			$saved_files['csomagpiac_'.$type] = VP_Woo_Pont_Import_Database::save_json_file('csomagpiac_'.$type, $points);
		}

        return $saved_files;
    }

    public function get_csomagpiac_db_manually() {
        return array( $this, 'get_csomagpiac_db' );
    }

    public function add_provider_group($groups) {
        $groups['csomagpiac'] = __('Csomagpiac', 'vp-woo-pont');
        return $groups;
    }

    public function add_providers($providers) {
        $providers['csomagpiac_dpd'] = 'DPD';
        $providers['csomagpiac_sameday'] = 'Easybox';
        $providers['csomagpiac_mpl_postapont'] = 'MPL Postapont';
        $providers['csomagpiac_mpl_posta'] = 'MPL Posta';
        $providers['csomagpiac_mpl_automata'] = 'MPL Csomagautomata';
        return $providers;
    }

    public function add_provider_subgroups($subgroups) {
        $subgroups['csomagpiac'] = array('dpd', 'sameday', 'mpl_postapont', 'mpl_posta', 'mpl_automata');
        return $subgroups;
    }

	public function create_label($data) {

		//Create item
		$order = wc_get_order($data['order_id']);
		$item = array(
			'pickupPointId' => VP_Woo_Pont_Helpers::get_option('csomagpiac_pickup_point'),
			'recipientName' => mb_substr($data['customer']['name_with_company'], 0, 40),
			'recipientCountryCode' => $order->get_shipping_country(),
			'recipientZip' => $order->get_shipping_postcode(),
			'recipientCity' => mb_substr($order->get_shipping_city(), 0, 40),
			'recipientAddress' => mb_substr(implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2())), 0, 40),
			'recipientPhone' => $data['customer']['phone'],
			'recipientEmail' => $data['customer']['email'],
			'packageCount' => 1,
			'weights' => array($data['package']['weight_gramm']),
			'weightUnit' =>  'g',
			'observation' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'csomagpiac', 100),
			'reference' => $data['reference_number'],
			'parcelValue' => '',
			'sourceId' => 3,
			'services' => array(),
			'sourceKey'=> trim(VP_Woo_Pont_Helpers::get_option('csomagpiac_shop_id') .'_'. $order->get_order_number()),
		);

		//Check for COD
		if($data['package']['cod']) {
			$item['cashOnDelivery'] = round($data['package']['total']);
		}

		//Check for points
		if($data['point_id']) {
			$services = array();
			if($data['provider'] == 'csomagpiac_sameday') {
				$services[] = array(
					'name' => 'sameday_easybox',
					'value' => $data['point_id']
				);
			}

			if($data['provider'] == 'csomagpiac_dpd') {
				$services[] = array(
					'name' => 'csomagpontra_szallitas',
					'value' => $data['point_id']
				);
			}

			if($data['provider'] == 'csomagpiac_sameday') {
				$services[] = array(
					'name' => 'sameday_easybox',
					'value' => $data['point_id']
				);
			}

			if($data['provider'] == 'csomagpiac_mpl_postapont') {
				$services[] = array(
					'name' => 'mpl_csomagpont',
					'value' => $data['point_id']
				);
			}

			if($data['provider'] == 'csomagpiac_mpl_posta') {
				$services[] = array(
					'name' => 'mpl_posta',
					'value' => $data['point_id']
				);
			}

			if($data['provider'] == 'csomagpiac_mpl_automata') {
				$services[] = array(
					'name' => 'mpl_automata',
					'value' => $data['point_id']
				);
			}

			$item['services'] = $services;
		}

		//Extra services
		if($extra_services = VP_Woo_Pont_Helpers::get_option('csomagpiac_extra_services')) {
			foreach($extra_services as $extra_service) {
				$item['services'][] = array(
					'name' => $extra_service
				);
			}
		}

		//So developers can modify
		$options = apply_filters('vp_woo_pont_csomagpiac_label', $item, $data);

		//Build request params
		$remote_url = $this->api_url . 'shipment/new' . '?' . http_build_query($options, '', '&');

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'csomagpiac-create-label');

		//Make request
		$request = wp_remote_post($remote_url, array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '.$this->api_token
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

		//Check for errors
		if(isset($response['error']) && $response['error']) {
			VP_Woo_Pont()->log_error_messages($response, 'csomagpiac-create-label');
			$error_messages = array();
			if(isset($response['errors'])) {
				foreach ($response['errors'] as $fault) {
					if(isset($fault['message'])) {
						$error_messages[] = $fault['message'];
					} else {
						$error_messages[] = $fault[0];
					}
				}
			} elseif(isset($response['message'])) {
				$error_messages[] = $response['message'];
			}
			return new WP_Error( 'bad_request', implode('; ', $error_messages) );
		}
		
		//Check for HTTP errors just in case
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			VP_Woo_Pont()->log_error_messages($response, 'csomagpiac-create-label');
			if(isset($response['message'])) {
				return new WP_Error( 'csomagpiac_error_unknown', $response['message'] );
			}
		}

		//Else, it was successful
		$parcel_number = $response['cspIdentifier'];
		$parcel_id = $response['cspIdentifier'];

		if(isset($response['handlerIdentifier'])) {
			$parcel_id = $response['handlerIdentifier'];
		}

		//Next, generate the PDF label
		$label_size = 'a6';
		$request = wp_remote_get( $this->api_url.'shipment/download/'.$parcel_number.'/'.$label_size, array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '.$this->api_token
			),
			'timeout' => 60
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
			VP_Woo_Pont()->log_error_messages($response, 'csomagpiac-download-label');
			return new WP_Error( $response['status'], $response['message'] );
		}

		//Now we have the PDF as base64, save it
		$pdf = $response;

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('csomagpiac', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = $parcel_id;
		$label['number'] = $parcel_number;
		$label['pdf'] = $pdf_file['name'];

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {

		//Create request data
		VP_Woo_Pont()->log_debug_messages($data, 'csomagpiac-void-label-request');

		//So developers can modify
		$options = apply_filters('vp_woo_pont_csomagpiac_void_label', $data, $data);

		//Submit request
		$request = wp_remote_request( $this->api_url.'shipment/delete/'.$options['parcel_number'], array(
			'method' => 'DELETE',
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '.$this->api_token
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );

			VP_Woo_Pont()->log_error_messages($response, 'csomagpiac-delete-label');

			if($response && $response['message']) {
				return new WP_Error( 'csomagpiac_error_'.$response['status'], $response['message'] );
			} else {
				return new WP_Error( 'csomagpiac_error_unknown', __('Unknown error', 'vp-woo-pont') );
			}
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'csomagpiac-void-label-response');

		return $label;
	}

    public function get_tracking_link($parcel_number, $order = false) {
		return 'https://bestr.csomagpiac.hu/kuldemenykovetes?ident='.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Submit request
		$request = wp_remote_get( $this->api_url.'shipment/history/'.$parcel_number, array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '.$this->api_token
			),
		));

		VP_Woo_Pont()->log_debug_messages($request, 'csomagpiac-get-tracking-info');

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {

			//If INVALID_PARCEL_ID, that means it was deleted on foxpost already
			return new WP_Error( 'csomagpiac_error_unknown', __('Unknown error', 'vp-woo-pont') );

		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check if empty response
		if(empty($response)) {
			return new WP_Error( 'csomagpiac_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Collect events
		$tracking_info = array();
		foreach ($response['packages'][0]['history'] as $event) {
			$tracking_info[] = array(
				'date' => strtotime($event['created_at']),
				'event' => $event['statusId'],
				'label' => $event['msg'],
			);
		}
		
		return $tracking_info;
	}

	public function get_pickup_points() {
		$blocks = array();
		//Needs this if so it won't load on every page load
		if(is_admin() && isset( $_GET['tab']) && $_GET['tab'] == 'shipping' && isset($_GET['section']) && $_GET['section'] == 'vp_carriers' && isset($_GET['carrier']) && $_GET['carrier'] == 'csomagpiac') {
			$blocks = $this->get_pickup_points_response();
		}
		return $blocks;
	}

	public function reload_pickup_points() {
		check_ajax_referer( 'vp-woo-pont-settings', 'nonce' );
		$pickup_points = $this->get_pickup_points_response(true);
		$pickup_points_response = array();
		foreach ($pickup_points as $id => $name) {
			$pickup_points_response[] = array(
				'id' => $id,
				'name' => $name
			);
		}
		wp_send_json_success($pickup_points_response);
	}

	public function get_pickup_points_response($refresh = false) {
		$pickup_points = get_transient('vp_woo_pont_csomagpiac_pickup_points');

		if (!$pickup_points || $refresh) {

			$request = wp_remote_get($this->api_url.'pickuppoints', array(
				'headers' => array(
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer '.$this->api_token
				)
			));

			//Check for errors
			if(is_wp_error($request)) {
				VP_Woo_Pont()->log_error_messages($request, 'csomagpiac-get-pickup-points');
				return array();
			}

			//Parse response
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );
			$pickup_points = array();

			
			if(isset($response['data']) && is_array($response['data'])) {
				foreach ($response['data'] as $pickup_point) {
					$pickup_points[$pickup_point['id']] = !empty($pickup_point['name']) ? $pickup_point['name'] : (!empty($pickup_point['title']) ? $pickup_point['title'] : $pickup_point['address']);
				}
			}

			//Save vat ids for a day
			set_transient('vp_woo_pont_csomagpiac_pickup_points', $pickup_points, 60 * 60 * 24);
		}

		return $pickup_points;
	}

}
