<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_DPD {
	protected $api_type = '';
	protected $api_url = 'https://weblabel.dpd.hu/dpd_wow/';
	protected $api_url_v2 = 'https://shipping.dpdgroup.com/api/v1.1/';
	protected $username = '';
	protected $password = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();
	public $supported_countries = array();

	public function __construct() {
		$this->username = VP_Woo_Pont_Helpers::get_option('dpd_username');
		$this->password = htmlspecialchars_decode(VP_Woo_Pont_Helpers::get_option('dpd_password'));
		$this->api_type = VP_Woo_Pont_Helpers::get_option('dpd_api_type');

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_dpd', array($this, 'get_settings'));

		//If dev mode, use a different API url
		if(VP_Woo_Pont_Helpers::get_option('dpd_dev_mode_shipping_api', 'no') == 'yes') {
			$this->api_url_v2 = 'https://nst-preprod.dpsin.dpdgroup.com/api/v1.1/';
		}

		//Set supported statuses
		$this->package_statuses = array(
			"Shipped" => __("Shipped", 'vp-woo-pont'),
			"Delivered" => __("Delivered", 'vp-woo-pont'),
			"Out for delivery" => __("Out for delivery", 'vp-woo-pont'),
			"Pickup scan" => __('Received by DPD from consignor(Pickup scan).', 'vp-woo-pont'),
			"System return" => __('Return to sender(System return)', 'vp-woo-pont'),
			"Outbound" => __('In transit (Outbound).', 'vo-woo-pont'),
			"HUB-scan" => __('In transit (HUB-scan).', 'vo-woo-pont'),
			"Info-scan" => __('In transit (Info-scan).', 'vo-woo-pont'),
			"Delivered to PUDO" => __('Delivered to pick-up point', 'vp-woo-pont'),
			"Collection request" => __('Order information has been transmitted to DPD.', 'vp-woo-pont'),
			"Delivered scan" => __('In transit (Delivered scan).', 'vo-woo-pont'),
			"Drivers pickup" => __('Received by DPD from consignor (Drivers pickup).', 'vp-woo-pont'),
			"Infoscan" => __('In transit (Infoscan).', 'vo-woo-pont'),
			"Warehouse" => __('At parcel delivery centre.', 'vp-woo-pont'),
			"Loading to consolidated" => __('In transit (Loading to consolidated).', 'vo-woo-pont'),
			"Loading" => __('In transit (Loading).', 'vo-woo-pont'),
			"Inbound exception" => __('In transit (Inbound exception).', 'vo-woo-pont'),
			"Loading to storage unit" => __('In transit (Loading to storage unit).', 'vo-woo-pont'),
			"Inbound scan" => __('In transit (Inbound scan).', 'vo-woo-pont'),
			"Driver's return" => __('Back at parcel delivery center after an unsuccessful delivery attempt', 'vp-woo-pont'),
			"Consolidation" => __('In transit (Consolidation)', 'vp-woo-pont'),
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array('Shipped', 'Pickup scan', 'Drivers pickup', 'Collection request', 'Outbound', 'HUB-scan', 'Info-scan', 'Infoscan', 'Warehouse', 'Loading to consolidated', 'Loading', 'Inbound exception', 'Inbound scan', 'Consolidation'),
			'delivery' => array('Out for delivery', 'Loading to storage unit'),
			'delivered' => array('Delivered', 'Delivered to PUDO', 'Delivered scan'),
			'errors' => array('System return', "Driver's return")
		);

		if($this->api_type == 'shipping-api') {
			$this->package_statuses = array(
				"SA02" => __('In transit (Inbound scan).', 'vo-woo-pont'),
				"SA03" => __("Out for delivery", 'vp-woo-pont'),
				"SA04" => __('Back at parcel delivery center after an unsuccessful delivery attempt', 'vp-woo-pont'),
				"SA05" => __('Received by DPD from consignor(Pickup scan).', 'vp-woo-pont'),
				"SA06" => __('Return to sender(System return)', 'vp-woo-pont'),
				"SA07" => __('In transit (Outbound).', 'vo-woo-pont'),
				"SA08" => __('At parcel delivery centre.', 'vp-woo-pont'),
				"SA09" => __('In transit (Inbound exception).', 'vo-woo-pont'),
				"SA10" => __('In transit (HUB-scan).', 'vo-woo-pont'),
				"SA12" => __('Custom clearance process', 'vo-woo-pont'),
				"SA13" => __('Delivered', 'vo-woo-pont'),
				"SA14" => __('Back at parcel delivery center after an unsuccessful delivery attempt', 'vp-woo-pont'),
				"SA15" => __('Received by DPD from consignor (Drivers pickup).', 'vp-woo-pont'),
				"SA17" => __('Export / Import cleared', 'vp-woo-pont'),
				"SA18" => __('Additional information', 'vp-woo-pont'),
				"SA20" => __('In transit (Loading).', 'vo-woo-pont'),
				"SA23" => __('Delivered to pick-up point', 'vp-woo-pont'),
			);

			$this->package_statuses_tracking = array(
				'shipped' => array('SA02', 'SA05', 'SA07', 'SA08', 'SA09', 'SA10', 'SA12', 'SA13', 'SA15', 'SA17', 'SA20'),
				'delivery' => array('SA03'),
				'delivered' => array('SA23', 'SA13', 'SA18'),
				'errors' => array('SA04', 'SA06', 'SA14')
			);
		}

		$this->supported_countries = array(
			'HU' => __( 'Hungary', 'vp-woo-pont' ),
			'RO' => __( 'Romania', 'vp-woo-pont' ),
			'HR' => __( 'Croatia', 'vp-woo-pont' ),
			'CZ' => __( 'Czechia', 'vp-woo-pont' ),
			'SI' => __( 'Slovenia', 'vp-woo-pont' ),
			'SK' => __( 'Slovakia', 'vp-woo-pont' ),
			'DE' => __( 'Germany', 'vp-woo-pont' ),
			'AT' => __( 'Austria', 'vp-woo-pont' ),
			'PL' => __( 'Poland', 'vp-woo-pont' ),
			'GR' => __( 'Greece', 'vp-woo-pont' ),
		);

	}

	public function get_settings($settings) {
		$dpd_settings = array(
			array(
				'title' => __( 'DPD settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('Connection type', 'vp-woo-pont'),
				'type' => 'select',
				'options' => array(
					'weblabel' => __('Weblabel', 'vp-woo-pont'),
					'shipping-api' => __('Shipping API', 'vp-woo-pont')
				),
				'default' => 'weblabel',
				'desc' => __('Select if you are using the old Weblabel system or the new Shipping API.', 'vp-woo-pont'),
				'class' => 'vp-woo-pont-select-group-dpd',
				'id' => 'dpd_api_type'
			),
			array(
				'title' => __('Weblabel Username', 'vp-woo-pont'),
				'type' => 'text',
				'class' => 'vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-weblabel',
				'desc' => __("Enter your DPD account's Weblabel username.", 'vp-woo-pont'),
				'id' => 'dpd_username'
			),
			array(
				'title' => __('Weblabel Password', 'vp-woo-pont'),
				'type' => 'text',
				'class' => 'vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-weblabel',
				'desc' => __("Enter your DPD account's Weblabel password.", 'vp-woo-pont'),
				'id' => 'dpd_password'
			),
            array(
                'type' => 'vp_checkboxes',
				'class' => 'vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-weblabel',
                'title' => __( 'Enabled countries', 'vp-woo-pont' ),
				'options' => $this->supported_countries,
                'default' => array('HU'),
				'desc' => __('Show pickup points in these countries as available options.', 'vp-woo-pont'),
				'id' => 'dpd_countries'
            ),
			array(
				'type' => 'select',
				'title' => __( 'Default service', 'vp-woo-pont' ),
				'class'    => 'wc-enhanced-select vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-weblabel',
				'options' => array(
					'D' => __('DPD Classic', 'vp-woo-pont'),
					'D-PREDICT' => __('DPD Classic with Predict', 'vp-woo-pont')
				),
				'default' => 'D',
				'id' => 'dpd_default_service'
			),
			array(
				'title' => __('JWT Token', 'vp-woo-pont'),
				'type' => 'text',
				'class' => 'vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-shipping-api',
				'desc' => __("Request the token from DPD Support.", 'vp-woo-pont'),
				'id' => 'dpd_jwt_token'
			),
			array(
				'title' => __('Customer ID', 'vp-woo-pont'),
				'type' => 'text',
				'class' => 'vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-shipping-api',
				'desc' => __("You can find this in into your DPD Shipping dashboard.", 'vp-woo-pont'),
				'id' => 'dpd_customer_id'
			),
			array(
				'title' => __('Sender Address ID', 'vp-woo-pont'),
				'type' => 'text',
				'class' => 'vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-shipping-api',
				'desc' => __("You can find this in into your DPD Shipping dashboard.", 'vp-woo-pont'),
				'id' => 'dpd_sender_id'
			),
			array(
				'title'    => __( 'Enable DEV mode', 'vp-woo-pont' ),
				'type'     => 'checkbox',
				'id' => 'dpd_dev_mode_shipping_api',
				'class' => 'vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-shipping-api',
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select vp-woo-pont-select-group-dpd-item vp-woo-pont-select-group-dpd-item-weblabel vp-woo-pont-select-group-dpd-item-shipping-api',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A6',
				'options' => array(
					'A6' => __( 'A6 on A4(recommended)', 'vp-woo-pont' ),
					'A6_SINGLE' => __( 'A6', 'vp-woo-pont' ),
				),
				'id' => 'dpd_sticker_size'
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$dpd_settings;
	}

	public function create_label($data) {

		//If we are using the new API
		if($this->api_type == 'shipping-api') {
			return $this->create_label_with_shipping_api($data);
		}

		//Create item
		$comment = VP_Woo_Pont()->labels->get_package_contents_label($data, 'dpd');
		$order = $data['order'];
		$item = array(
			'username' => $this->username,
			'password' => $this->password,
			'name1' => substr($data['customer']['name'],0,40),
			'name2' => substr($data['customer']['company'],0,40),
			'street' => implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2())),
			'city' => $order->get_shipping_city(),
			'country' => $order->get_shipping_country(),
			'pcode' => $order->get_shipping_postcode(),
			'phone' => $data['customer']['phone'],
			'email' => $data['customer']['email'],
			'remark' => $comment,
			'weight' => wc_get_weight($data['package']['weight'], 'kg'),
			'num_of_parcel' => 1,
			'order_number' => $data['reference_number'],
			'parcel_type' => VP_Woo_Pont_Helpers::get_option('dpd_default_service', 'D')
		);

		//If its a point order
		if($data['point_id']) {
			$item['parcelshop_id'] = $data['point_id'];
			$item['parcel_type'] = 'PS';
		}

		//Check for COD
		if($data['package']['cod']) {
			$item['parcel_type'] = 'D-COD';
			if(VP_Woo_Pont_Helpers::get_option('dpd_default_service', 'D') == 'D-PREDICT') {
				$item['parcel_type'] = 'D-COD-PREDICT';
			}

			$item['parcel_cod_type'] = 'firstonly';
			$item['cod_amount'] = $data['package']['total'];
			$item['cod_purpose'] = $data['cod_reference_number'];
			if($data['point_id']) {
				$item['parcel_type'] = 'PSCOD';
			} else {
				if($order->get_shipping_country() != 'HU') {
					$item['parcel_type'] = 'CODCB';
				}
			}
		}

		//If package count set
		if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
			$item['num_of_parcel'] = $data['options']['package_count'];
			$item['weight'] = wc_get_weight($data['package']['weight']/$data['options']['package_count'], 'kg');
		}

		//So developers can modify
		$item = apply_filters('vp_woo_pont_dpd_label', $item, $data);

		//Logging
		VP_Woo_Pont()->log_debug_messages($item, 'dpd-create-label');

		//Submit request
		$request = wp_remote_post( $this->api_url.'parcel_import.php', array(
			'body'    => $item,
			'headers'  => array(
				'Content-Type: application/x-www-form-urlencoded'
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

		//Logging
		VP_Woo_Pont()->log_debug_messages($response, 'dpd-create-label-response');

		//Check for errors
		if (is_null($response) || !isset($response['status'])) {
            VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
            return new WP_Error('dpd_error', 'Invalid response from DPD API');
        }
		
		if($response['status'] == 'err') {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $response['errlog'] );
		}

		//Else, it was successful
		$parcel_number = $response['pl_number'][0];
		$parcel_numbers = $response['pl_number'];

		//Next, generate the PDF label
		$request = wp_remote_post( $this->api_url.'parcel_print.php', array(
			'body'    => array(
				'username' => $this->username,
				'password' => $this->password,
				'parcels' => implode('|', $response['pl_number'])
			),
			'headers' => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );

		//Check if its json, if so, thats an error
		$json = json_decode( $response );
		if($json !== null) {
			return new WP_Error( 'dpd_error', $json['errlog'] );
		}

		//Now we have the PDF as base64, save it
		$pdf = $response;

		//Crop to A6 if needed, but only if it doesn't contain a return label
		$label_size = VP_Woo_Pont_Helpers::get_option('dpd_sticker_size', 'A6');
		if($label_size == 'A6_SINGLE' && $item['num_of_parcel'] == 1) {
			$pdf = VP_Woo_Pont_Print::fit_to_a6($pdf);
		}

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('dpd', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = implode('|', $parcel_numbers);
		$label['number'] = $parcel_number;
		$label['pdf'] = $pdf_file['name'];
		$label['needs_closing'] = true;

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function create_label_with_shipping_api($data) {

		//Create item
		$comment = VP_Woo_Pont()->labels->get_package_contents_label($data, 'dpd');
		$order = $data['order'];
		$options = array(
			'customerId' => VP_Woo_Pont_Helpers::get_option('dpd_customer_id'),
			'shipments' => array(
				array(
					'numOrder' => 1,
					'senderAddressId' => VP_Woo_Pont_Helpers::get_option('dpd_sender_id'),
					'receiver' => array(
						"city" => $order->get_shipping_city(),
						'contactEmail' => $data['customer']['email'],
						'contactName' => $data['customer']['name'],
						'contactPhone' => $data['customer']['phone'],
						'contactMobile' => substr($data['customer']['phone'], 1),
						'countryCode' => $order->get_shipping_country(),
						'name' => $data['customer']['name'],
						'street' => implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2())),
						'zipCode' => $order->get_shipping_postcode(),
		
					),
					'parcels' => array(
						array(
							'weight' => wc_get_weight($data['package']['weight'], 'kg')
						)
					),
					'service' => array(
						'mainServiceCode' => '327', //B2C Domestic csomag
						'additionalService' => array(
							'predicts' => array(
								array(
									'type' => 'EMAIL',
									'destination' => $data['customer']['email']
								)
							)
						)
					),
					'reference1' => $data['order_id'],
					'reference2' => $data['reference_number'],
					'reference3' => $comment,
					'saveMode' => "printed",
					'printFormat' => "PDF",
					'labelSize' => "A6"
				)
			)
		);

		//If its a point order
		if($data['point_id']) {
			$options['shipments'][0]['service']['mainServiceCode'] = '337'; //B2C Domestic csomag, COD,
			$options['shipments'][0]['service']['additionalService']['pudoId'] = $data['point_id'];
			$options['shipments'][0]['parcels'][0]['dimensionWidth'] = 1;
			$options['shipments'][0]['parcels'][0]['dimensionHeight'] = 1;
			$options['shipments'][0]['parcels'][0]['dimensionLength'] = 1;
		}

		//Check for COD
		if($data['package']['cod']) {
			$options['shipments'][0]['service']['additionalService']['cod'] = array(
				'currency' => $order->get_currency(),
				'paymentType' => 'cash',
				'reference' => $data['cod_reference_number'],
				'amount' => $data['package']['total'],
				'split' => 'First parcel'
			);
		}

		//If package count set
		if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
			$options['shipments'][0]['parcels'] = array();
			for ($i = 0; $i < $data['options']['package_count']; $i++) {
				$weight = wc_get_weight($data['package']['weight'] / $data['options']['package_count'], 'kg');
				$options['shipments'][0]['parcels'][] = array(
					'weight' => $weight,
				);
			}
		}

		//So developers can modify
		$options = apply_filters('vp_woo_pont_dpd_label', $options, $data);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'dpd-create-label');

		//Submit request
		$request = wp_remote_post( $this->api_url_v2.'shipments', array(
			'body'    => wp_json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '.VP_Woo_Pont_Helpers::get_option('dpd_jwt_token')
			)
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		
		//Logging
		VP_Woo_Pont()->log_debug_messages($response, 'dpd-create-label-response');

		//Check for errors
		if(!isset($response['shipmentResults'])) {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error',  __('Unknown error', 'vp-woo-pont') );
		}

		//Check for results
		$result = $response['shipmentResults'][0];

		//Check for result errors
		if(isset($result['errors'])) {
			VP_Woo_Pont()->log_error_messages($result, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $result['errors'][0]['errorCode'].' - '.$result['errors'][0]['errorContent']);
		}

		//Else, it was successful
		$parcel_id = $result['shipmentId'];
		$parcel_number = $result['parcelResults'][0]['parcelNumber'];

		//Try to save PDF file
		$pdf = substr($result['labelFile'], strpos($result['labelFile'], ',') + 1);
		$pdf = base64_decode($pdf);
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('dpd', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = $parcel_id;
		$label['number'] = $parcel_number;
		$label['pdf'] = $pdf_file['name'];
		$label['needs_closing'] = false;

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {

		//If we are using the new API
		if($this->api_type == 'shipping-api') {
			return $this->void_label_with_shipping_api($data);
		}

		//Create request data
		VP_Woo_Pont()->log_debug_messages($data, 'dpd-void-label-request');

		//Submit request
		$request = wp_remote_post( $this->api_url.'parcel_delete.php', array(
			'body'    => array(
				'username' => $this->username,
				'password' => $this->password,
				'parcels' => $data['parcel_id']
			),
			'headers' => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for errors
		if($response['status'] == 'err') {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $response['errlog'] );
		}

		//Check for success
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'dpd-void-label-response');

		return $label;
	}

	public function void_label_with_shipping_api($data) {

		//Create request data
		VP_Woo_Pont()->log_debug_messages($data, 'dpd-void-label-request');

		//Submit request
		$request = wp_remote_post( $this->api_url_v2.'shipments/cancellation', array(
			'body'    => wp_json_encode(array(
				'customerId' => VP_Woo_Pont_Helpers::get_option('dpd_customer_id'),
				'shipmentIdList' => array($data['parcel_id'])
			)),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '.VP_Woo_Pont_Helpers::get_option('dpd_jwt_token')
			)
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for errors
		if($response['status'] == 'err') {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $response['errlog'] );
		}

		//Check for success
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'dpd-void-label-response');

		return $label;
	}

	//Return tracking link
	public function get_tracking_link($parcel_number, $order = false) {
		return 'https://www.dpdgroup.com/hu/mydpd/my-parcels/track?lang=hu&parcelNumber='.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//If we are using the new API
		if($this->api_type == 'shipping-api') {
			return $this->get_tracking_info_with_shipping_api($order);
		}

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Submit request
		$request = wp_remote_post( $this->api_url.'parcel_status.php', array(
			'body'    => array(
				'secret' => 'FcJyN7vU7WKPtUh7m1bx', //Fixed secret based on the Weblabel documentation
				'parcel_number' => $parcel_number
			),
			'headers' => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for errors
		if($response['status'] == 'err') {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $response['errlog'] );
		}

		//Check for errors
		if(isset($response['errmsg']) && $response['errmsg'] != '') {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $response['errmsg'] );
		}

		//Get existing status updates, since this API only returns the current status of the package, not all
		$tracking_info = $order->get_meta('_vp_woo_pont_parcel_info');
		if(!$tracking_info) $tracking_info = array();

		//Check if requested status already saved
		$status_saved = false;
		foreach ($tracking_info as $event) {
			if($event['event'] == $response['parcel_status']) {
				$status_saved = true;
			}
		}

		//If already saved, just return the current tracking info
		if($status_saved) {
			return $tracking_info;
		}

		//Prepend the new event at the beginning
		$event = array(
			'date' => strtotime($response['event_date']),
			'event' => $response['parcel_status'],
			'label' => ''
		);
		array_unshift($tracking_info, $event);

		return $tracking_info;
	}

	public function get_tracking_info_with_shipping_api($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Submit request
		$request = wp_remote_post( 'https://middleware.dpd.hu/api/parcel-status', array(
			'body'    => array(
				'secret' => 'FcJyN7vU7WKPtUh7m1bx', //Fixed secret based on the Weblabel documentation
				'parcel_number' => $parcel_number,
				'lang' => 'en',
				'history' => 'true',
				'code' => 'true'
			),
			'headers' => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for errors
		if($response['status'] == 'error') {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $response['errlog'] );
		}

		//Collect events
		$tracking_info = array();
		foreach ($response['history'] as $event) {
			$tracking_info[] = array(
				'date' => strtotime($event['event_date']),
				'event' => $event['parcel_status'],
				'label' => '',
			);
		}

		return $tracking_info;
	}

	public function close_shipments($packages = array(), $orders = array()) {

		//Submit request
		$request = wp_remote_post( $this->api_url.'parceldatasend.php', array(
			'body'    => array(
				'username' => $this->username,
				'password' => $this->password
			),
			'headers' => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		/*
		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for errors
		if($response['status'] == 'err') {
			VP_Woo_Pont()->log_error_messages($response, 'dpd-create-label');
			return new WP_Error( 'dpd_error', $response['errlog'] );
		}
		*/

		//Init mPDF
		require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

		// Create a new mPDF object
		$mpdf = new \Mpdf\Mpdf(['mode' => 'c', 'format' => 'A4', 'allow_charset_conversion' => true]);

		//Setup tempalte data
		$label_data = array(
			'orders' => $orders,
			'carrier' => 'dpd',
			'icon' => VP_Woo_Pont()::$plugin_url.'/assets/images/icon-dpd.svg'
		);
		$html = wc_get_template_html('shipments-table.php', $label_data, false, VP_Woo_Pont::$plugin_path . '/templates/');

		//Add the HTML content to the PDF document
		$mpdf->WriteHTML($html);

		//Output the PDF document
		$pdf = $mpdf->Output('', 'S');

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('dpd-manifest', 0);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Return response in unified format
		return array(
			'shipments' => array(),
			'orders' => $orders,
			'pdf' => array(
				'dpd' => $pdf_file['name']
			)
		);
	}

	public function export_label($data) {
		$dpd_country_codes = array('AT' => 'A', 'BA' => 'BIH', 'DE' => 'D', 'ES' => 'E', 'FI' => 'FIN', 'FR' => 'F', 'HU' => 'H', 'IE' => 'IRL', 'IT' => 'I', 'LU' => 'L', 'NO' => 'N', 'PT' => 'P', 'RU' => 'GUS', 'SE' => 'S', 'SI' => 'SLO');
		$order = $data['order'];

		$csv_row = array();
		$csv_row[0] = ($data['package']['cod']) ? 'D-COD' : 'D';
		$csv_row[1] = wc_get_weight($data['package']['weight'], 'kg');
		$csv_row[2] = ($data['package']['cod']) ? $data['package']['total'] : '';
		$csv_row[3] = ($data['package']['cod']) ? $data['cod_reference_number'] : '';
		$csv_row[4] = $order->get_order_number(); //Referencia szám(rendelés szám)
		$csv_row[5] = $data['reference_number'];
		$csv_row[6] = $data['customer']['name'];
		$csv_row[7] = '';
		$csv_row[8] = $order->get_shipping_address_1();
		$csv_row[9] = $order->get_shipping_address_2();
		if(array_key_exists($order->get_shipping_country(), $dpd_country_codes)) {
			$csv_row[10] = $dpd_country_codes[$order->get_shipping_country()];
		} else {
			$csv_row[10] = $order->get_shipping_country();
		}
		$csv_row[11] = $order->get_shipping_postcode();
		$csv_row[12] = $order->get_shipping_city();
		$csv_row[13] = $data['customer']['phone'];
		$csv_row[14] = ''; //Fax
		$csv_row[15] = $data['customer']['email'];
		$csv_row[16] = '';

		if($data['point_id']) {
			for ($i = 16; $i < 39; $i++) {
				$csv_row[] = '';
			}
			$csv_row[39] = $data['point_id'];
		}

		return array(
			'data' => array($csv_row)
		);
	}

	public function get_enabled_countries() {
		$enabled_countries = get_option('vp_woo_pont_dpd_countries', array('HU'));
		$enabled = array();
		$supported = $this->supported_countries;
		foreach ($enabled_countries as $enabled_country) {
			$enabled['dpd_'.strtolower($enabled_country)] = $supported[$enabled_country].' (DPD)';
		}
		return $enabled;
	}

}
