<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class VP_Woo_Pont_ExpressOne {
	protected $api_url = 'https://webcas.expressone.hu/webservice/';
	protected $company_id = '';
	protected $username = '';
	protected $password = '';
	protected $api_key = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();

	public function __construct() {
		$this->company_id = VP_Woo_Pont_Helpers::get_option('expressone_company_id');
		$this->username = VP_Woo_Pont_Helpers::get_option('expressone_username');
		$this->password = VP_Woo_Pont_Helpers::get_option('expressone_password');

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_expressone', array($this, 'get_settings'));

		//Set supported statuses
		$this->package_statuses = array(
			'D00' => 'Sikeres kézbesítés',
			'OFD' => 'Kiadva futárnak',
			'DEP' => 'Depóba érkezett',
			'OUB' => 'Központi raktárat elhagyta',
			'INB' => 'Központi raktárba érkezett',
			'DLS' => 'Adat bekerült a rendszerbe',
			'E40' => 'Depó kiszállítási kapacitási hiánya miatt',
			'VV' => 'Vissza a feladónak',
			'E92' => 'Kézbesítési nap módosítása Rugalmas kézbesítés felületen',
			'V96' => 'Címzett visszautasította'
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array('DEP', 'OUB', 'INB', 'DLS', 'E92'),
			'delivery' => array('0FD', 'OFD'),
			'delivered' => array('D00'),
			'errors' => array('E40', 'VV', 'V96')
		);

	}

	public function get_settings($settings) {
		$expressone_settings = array(
			array(
				'title' => __( 'Express One settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('Company ID', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'expressone_company_id'
			),
			array(
				'title' => __('Username', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'expressone_username'
			),
			array(
				'title' => __('Password', 'vp-woo-pont'),
				'type' => 'password',
				'id' => 'expressone_password'
			),
			array(
				'title'    => __( 'Customer SMS Notification', 'vp-woo-pont' ),
				'type'     => 'checkbox',
				'desc' => __('Send an SMS notification to the customer(extra service).', 'vp-woo-pont'),
				'id' => 'expressone_customer_sms'
			),
			array(
				'type' => 'select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'class'    => 'wc-enhanced-select',
				'options' => array(
					'A6' => __('A6 on A4(recommended)', 'vp-woo-pont'),
					'100x150' => __('100x150mm', 'vp-woo-pont')
				),
				'default' => 'A6',
				'id' => 'expressone_sticker_size'
			),
			array(
				'type' => 'sectionend',
			),
		);

		return $settings+$expressone_settings;
	}

	public function create_label($data) {

		//Get order details
		$order = wc_get_order($data['order_id']);

		//Set package weight
		$weight = ($data['package']['weight'] == 0) ? 1 : wc_get_weight($data['package']['weight'], 'kg');
		$weight = ceil($weight);

		//Setup basic data
		$item = array(
			'auth' => $this->get_auth(),
			'deliveries' => array(
				array(
					'post_date' => date_i18n('Y-m-d'),
					'consig' => array(
						'name' => $data['customer']['name'],
						'contact_name' => $data['customer']['name_with_company'],
						'city' => $order->get_shipping_city(),
						'street' => $order->get_shipping_address_1().' '.$order->get_shipping_address_2(),
						'country' => $order->get_shipping_country(),
						'post_code' => $order->get_shipping_postcode(),
						'phone' => $data['customer']['phone'],
					),
					'parcels' => array(
						'type' => 0,
						'qty' => 1,
						'weight' => $weight,
					),
					'services' => array(
						'delivery_type' => ($data['point_id']) ? 'D2S' : '24H',
						'insurance' => array(
							'enable' => true,
							'parcel_price' => (double) $order->get_total() //Net order total
						),
						'notification' => array(
							'email' => $data['customer']['email'],
							'sms' => (VP_Woo_Pont_Helpers::get_option('expressone_customer_sms', 'no') == 'yes') ? $data['customer']['phone'] : ''
						)
					),
					'ref_number' => $data['reference_number'],
					'note' => '',
					'invoice_number' => $data['invoice_number'],
				)
			),
			'labels' => array(
				'data_type' => 'PDF',
				'size' => (VP_Woo_Pont_Helpers::get_option('expressone_sticker_size', 'A6') == 'A6') ? 'A4' : '100x150',
				'pdf_etiket_position' => 0
			)
		);

		//If package count set
		if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
			$item['deliveries'][0]['parcels']['qty'] = $data['options']['package_count'];
		}

		//If its a pont shipping method
		if($data['point_id']) {
			$item['deliveries'][0]['services']['eone_shop'] = $data['point_id'];
		}

		//Check for COD
		if($data['package']['cod']) {
			$item['deliveries'][0]['services']['cod'] = array(
				'amount' => round($data['package']['total']/5, 0) * 5
			);
		}

		//So developers can modify
		$options = apply_filters('vp_woo_pont_expressone_label', $item, $data);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'expressone-create-label');

		//Try to create label
		$request = wp_remote_post( $this->api_url.'parcel/create_labels/response_format/json', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
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

		//Check fo API errors
		if(!$response['successfull']) {
			VP_Woo_Pont()->log_error_messages($response, 'expressone-create-label');
			return new WP_Error( 'expressone_error_'.$response['error_code'], $response['error_messages'] );
		}

		//Check for other errors
		if(isset($response['response']['deliveries'][0]['message']) && $response['response']['deliveries'][0]['message'] != 'OK') {
			VP_Woo_Pont()->log_error_messages($response, 'expressone-create-label');
			return new WP_Error( 'expressone_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Get parcel number
		$parcel = $response['response']['deliveries'][0]['data'];
		$parcel_number = $parcel['parcel_numbers'][0];

		//Try to save PDF file
		$pdf = base64_decode($response['response']['labels']['data']);
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('expressone', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = $parcel_number;
		$label['number'] = $parcel_number;
		$label['pdf'] = $pdf_file['name'];
		$label['needs_closing'] = true;

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {

		//Create request data
		$options = array(
			'auth' => $this->get_auth(),
			'parcel_number' => $data['parcel_number']
		);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'expressone-void-label');

		//Try to create label
		$request = wp_remote_post( $this->api_url.'parcel/delete_labels/response_format/json', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
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

		//Check fo API errors
		if(!$response['successfull']) {
			//If INVALID_PARCEL_ID, that means it was deleted on webcas already, return true
			if($response['error_code'] == 20304) {
				$label = array();
				$label['success'] = true;
				return $label;
			} else {
				VP_Woo_Pont()->log_error_messages($response, 'expressone-create-label');
				return new WP_Error( 'expressone_error_'.$response['error_code'], $response['error_messages'] );
			}
		}

		//Check for success
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'expressone-void-label-response');

		return $label;
	}

	//Return tracking link
	public function get_tracking_link($parcel_number, $order = false) {
		return 'https://tracking.expressone.hu/?plc_number='.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Create request data
		$options = array(
			'auth' => $this->get_auth(),
			'parcel_number' => $parcel_number
		);

		//Try to create label
		$request = wp_remote_post( $this->api_url.'tracking/get_parcel_history/response_format/json', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
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

		//Check fo API errors
		if(!$response['successfull']) {
			VP_Woo_Pont()->log_error_messages($response, 'expressone-create-label');
			return new WP_Error( 'expressone_error_'.$response['error_code'], $response['error_messages'] );
		}

		//Loop through and create standard events
		$tracking_info = array();
		foreach ($response['response']['history'] as $event) {
			$location = '';
			if(isset($event['event_location'])) {
				$location = $event['event_location'];
			}
			$tracking_info[] = array(
				'date' => strtotime($event['created_at']),
				'event' => $event['event_code'],
				'label' => $event['event_name'],
				'comment' => $event['event_name'].' '.$event['event_location']
			);
		}

		return array_reverse($tracking_info);
	}

	public function get_auth() {
		return array(
			'company_id' => $this->company_id,
			'user_name' => $this->username,
			'password' => $this->password
		);
	}

	public function close_shipments($packages = array(), $orders = array()) {

		//Create request data
		$options = array(
			'auth' => $this->get_auth(),
			'date' => date_i18n('Y-m-d'),
			'options' => array(
				'closing' => true
			)
		);

		//Try to create label
		$request = wp_remote_post( $this->api_url.'parcel_list/create_list/response_format/json', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
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

		//Check fo API errors
		if(!$response['successfull']) {
			VP_Woo_Pont()->log_error_messages($response, 'expressone-create-label');
			return new WP_Error( 'expressone_error_'.$response['error_code'], $response['error_messages'] );
		}

		//Save PDF
		$pdf = base64_decode($response['response']['pdf']);
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('expressone', 'list-and-close');
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);
		$pdf_file_url = $pdf_file['baseurl'].$pdf_file['name'];

		//Return response in unified format
		return array(
			'shipments' => array(),
			'orders' => $orders,
			'pdf' => array(
				'expressone' => $pdf_file['name']
			)
		);

	}

}
