<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class VP_Woo_Pont_Kvikk {
	protected $api_url = 'https://api.kvikk.hu/v1/';
	protected $api_key = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();

	public function __construct() {
		add_filter('vp_woo_pont_carrier_settings_kvikk', array($this, 'get_settings'));
        add_action('vp_woo_pont_update_kvikk_list', array( $this, 'get_kvikk_db' ));
        add_filter('vp_woo_pont_external_provider_groups', array($this, 'add_provider_group'));
        add_filter('vp_woo_pont_get_supported_providers_for_home_delivery', array($this, 'add_provider_group_home_delivery'));
		add_filter('vp_woo_pont_is_provider_configured', array($this, 'is_configured'), 10, 2);
        add_filter('vp_woo_pont_get_supported_providers', array($this, 'add_providers'));
        add_filter('vp_woo_pont_provider_subgroups', array($this, 'add_provider_subgroups'));
        add_filter('vp_woo_pont_import_kvikk_manually', array( $this, 'get_kvikk_db_manually'));
		add_action('wp_ajax_vp_woo_pont_validate_kvikk_api_key', array( $this, 'validate_api_key' ) );
		add_filter('vp_woo_pont_merged_pdf_parameters', array($this, 'get_merged_pdf_parameters'), 10, 3);
		add_filter('vp_woo_pont_tracking_page_variables', array($this, 'tracking_page_variables'), 10, 2);
		add_filter('vp_woo_pont_email_order_details_params', array($this, 'tracking_email_variables'), 10, 2);

		//Load custom admin JS
		add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));

		//Create custom metabox
		add_action('add_meta_boxes', array($this, 'add_metabox'), 10, 2);

		//Set supported statuses
		$this->package_statuses = array(
			'booked' => 'Csomag létrehozva',
			'sent' =>'Csomag átadva a futárnak',
			'in_transit' => 'Csomag szállítás alatt',
			'out_for_delivery' => 'Csoamag kiszállítás alatt(címzettnek vagy csomagpontra)',
			'ready_for_pickup' => 'Csomag kézbesítve csomagpontba vagy automatába',
			'delivered' => 'Csomag kézbesítve / átvéve',
			'failed' => 'Sikertelen kézbesítés',
			'returned' => 'Visszaküldve a feladónak',
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array('booked', 'sent', 'in_transit'),
			'delivery' => array('ready_for_pickup', 'out_for_delivery'),
			'delivered' => array('delivered'),
			'errors' => array('failed', 'returned')
		);

		$this->api_key = VP_Woo_Pont_Helpers::get_option('kvikk_api_key');
		//$this->api_url = 'http://localhost:3001/v1/';

		//Show promo
		add_action( 'wp_ajax_vp_woo_pont_hide_kvikk_promo', array( $this, 'hide_ad' ) );
		add_action('vp_woo_pont_metabox_before_content', array($this, 'display_ad'));
		add_action('vp_woo_pont_providers_before_table', array($this, 'display_ad'));

		//Process IPN request
		add_action( 'init', array( __CLASS__, 'ipn_process' ), 11 );

		//Handle QR scan
		add_action('rest_api_init', array(__CLASS__, 'register_api_endpoints'));

	}

	public function load_admin_scripts() {

		wp_enqueue_script('vp-woo-pont-kvikk-admin', VP_Woo_Pont::$plugin_url.'assets/js/kvikk-admin.min.js', array('jquery'), VP_Woo_Pont::$version, true);
		wp_localize_script('vp-woo-pont-kvikk-admin', 'vp_woo_pont_kvikk_params', array(
			'couriers' => get_option('vp_woo_pont_kvikk_courier_details', array())
		));
	}

	//Meta box on order page
	public function add_metabox( $post_type, $post_or_order_object ) {
		if ( class_exists( CustomOrdersTableController::class ) && function_exists( 'wc_get_container' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$screen = wc_get_page_screen_id( 'shop-order' );
		} else {
			$screen = 'shop_order';
		}
		$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if(is_a( $order, 'WC_Order' )) {
			if($order->get_meta('_vp_woo_pont_kvikk_accounting')) {
				add_meta_box('vp_woo_pont_metabox_kvikk', __('Kvikk', 'vp-woo-pont'), array( $this, 'render_metabox_content' ), $screen, 'side');
			}
		}
	}

	//Render metabox content
	public function render_metabox_content($post_or_order_object) {
		$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if(apply_filters('vp_woo_pont_show_label_metabox', $order->needs_processing(), $order)) {
			include( VP_Woo_Pont::$plugin_path . 'includes/views/html-metabox-kvikk.php' );
		}
	}

	public function get_settings($settings) {
		$kvikk_settings = array(
			array(
				'title' => __( 'Kvikk settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('API key', 'vp-woo-pont'),
				'type' => 'vp_kvikk_api',
				'desc' => __("Enter your account's API key.", 'vp-woo-pont'),
				'id' => 'kvikk_api_key',
			),
			array(
				'title' => __('Sender ID', 'vp-woo-pont'),
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'options' => $this->get_pickup_points(),
				'desc' => __("Select your sender ID.", 'vp-woo-pont'),
				'id' => 'kvikk_sender_id'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A6',
				'options' => array(
					'A6' => __( 'A6 on A4(recommended)', 'vp-woo-pont' ),
					'A6_SINGLE' => __( 'A6', 'vp-woo-pont' ),
					'A5_LANDSCAPE' => __( 'Two A5 on landscape A4', 'vp-woo-pont' ),
				),
				'id' => 'kvikk_sticker_size'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Packaged order status', 'vp-woo-pont' ),
				'options' => VP_Woo_Pont_Settings::get_order_statuses(__('None', 'Order status after scanning', 'vp-woo-pont')),
				'desc_tip' => __( 'If you scan the label in the Kvikk App, you can mark the order to be in this status.', 'vp-woo-pont' ),
				'id' => 'kvikk_packaged_order_status',
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$kvikk_settings;
	}

    public function get_kvikk_db() {

		//URL of the database
		$url = 'https://api.kvikk.hu/delivery-points/';

		//Get XML file
		$request = wp_remote_get($url);

		//Check for errors
		if( is_wp_error( $request ) ) {
			VP_Woo_Pont()->log_error_messages($request, 'kvikk-import-points');
			return false;
		}

		//Get body
		$body = wp_remote_retrieve_body( $request );

		//Try to convert into json
		$json = json_decode( $body, true );

		//Check if json exists
		if($json === null) {
			return false;
		}

		//Create a new json
		$results = array('packeta_zpont' => array(), 'packeta_zbox' => array(), 'foxpost' => array(), 'mpl_automata' => array(), 'mpl_postapont' => array(), 'mpl_posta' => array(), 'alzabox' => array(), 'expressone_omv' => array());

		//Loop through points
		foreach ($json['data'] as $point) {
			$point_type = $point['type'];
			if(!isset($results[$point_type])) {
				$point_type = $point['courier'].'_'.$point['type'];
			}
			if(isset($results[$point_type])) {
				$results[$point_type][] = $point;
			}
		}

		//Save stuff
		$saved_files = array();
		foreach ($results as $type => $points) {
			$saved_files['kvikk_'.$type] = VP_Woo_Pont_Import_Database::save_json_file('kvikk_'.$type, $points);
		}

		//Also update courier details
		$this->get_pickup_points_response(true);

        return $saved_files;
    }

    public function get_kvikk_db_manually() {
        return array( $this, 'get_kvikk_db' );
    }

    public function add_provider_group($groups) {
        $groups['kvikk'] = __('Kvikk', 'vp-woo-pont');
        return $groups;
    }

    public function add_provider_group_home_delivery($groups) {
        $groups['kvikk_mpl'] = __('Kvikk MPL', 'vp-woo-pont');
        $groups['kvikk_expressone'] = __('Kvikk Express One', 'vp-woo-pont');
        $groups['kvikk_famafutar'] = __('Kvikk FámaFutár', 'vp-woo-pont');
        return $groups;
    }

	public function is_configured($configured, $provider) {
		if(strpos($provider, 'kvikk') === 0 && VP_Woo_Pont_Helpers::get_option('kvikk_api_key')) {
			return true;
		}
		return $configured;
	}

    public function add_providers($providers) {
        $providers['kvikk_mpl_posta'] = 'Posta';
        $providers['kvikk_mpl_postapont'] = 'Postapont';
        $providers['kvikk_mpl_automata'] = 'MPL Csomagautomata';
        $providers['kvikk_packeta_zpont'] = 'Packeta Z-Pont';
        $providers['kvikk_packeta_zbox'] = 'Packeta Z-Box';
        $providers['kvikk_foxpost'] = 'Foxpost';
        $providers['kvikk_expressone_omv'] = 'OMV';
        $providers['kvikk_alzabox'] = 'Alzabox';
        return $providers;
    }

    public function add_provider_subgroups($subgroups) {
		$subgroups['kvikk'] = array('mpl_posta', 'mpl_postapont', 'mpl_automata', 'packeta_zpont', 'packeta_zbox', 'foxpost', 'alzabox', 'expressone_omv');
        return $subgroups;
    }

	public function create_label($data) {

		//Get courier
		$courier = $data['provider']; 

		//Fix for alzabox
		if($courier == 'kvikk_alzabox') {
			$courier = 'kvikk_expressone_alzabox';
		}

		//Fix for foxpost
		if($courier == 'kvikk_foxpost') {
			$courier = 'kvikk_packeta_foxpost';
		}

		//Parse courier details
		$courier = str_replace('kvikk_', '', $courier);
		$courier_details = explode('_', $courier);
		
		//Create item
		$order = wc_get_order($data['order_id']);
		$shipment = array(
			//Customer info
			'name' => ($data['point_id']) ? $data['customer']['name'] : $data['customer']['name_with_company'],
			'phone' => $data['customer']['phone'],
			'email' => $data['customer']['email'],

			//Courier info
			'courier' => $courier_details[0],
			'senderID' => VP_Woo_Pont_Helpers::get_option('kvikk_sender_id'),

			//Package details
			'orderID' => $order->get_order_number(),
			'weight' => $data['package']['weight_gramm'],
			'value' => $data['package']['total'],
			'note' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'kvikk', 100),
			'cod' => 0,
		);

		//If manually generated, use submitted weight instead
		if(isset($data['options']) && isset($data['options']['package_weight']) && $data['options']['package_weight'] > 0) {
			$shipment['weight'] = $data['options']['package_weight'];
		}

		//Check for COD
		if($data['package']['cod']) {
			$shipment['cod'] = round($data['package']['total']);
		}

		//Check for points
		if($data['point_id']) {
			$shipment['deliveryPointID'] = $data['point_id'];
			$shipment['deliveryPointType'] = $courier_details[1];
		}

		//Check for home delivery
		if(!$data['point_id']) {
			$shipment['address'] = implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2()));
			$shipment['city'] = $order->get_shipping_city();
			$shipment['postcode'] = $order->get_shipping_postcode();
			$shipment['country'] = 'HU';
			$shipment['note'] =VP_Woo_Pont()->labels->get_package_contents_label($data, 'kvikk', 100);
		}

		//If we have a package size set
		if(isset($data['package']['size']) && isset($data['package']['size']['width']) && isset($data['package']['size']['height']) && isset($data['package']['size']['length'])) {
			$shipment['width'] = $data['package']['width'];
			$shipment['height'] = $data['package']['height'];
			$shipment['length'] = $data['package']['length'];
		}
		
		//So developers can modify
		$shipment = apply_filters('vp_woo_pont_kvikk_label', $shipment, $data);
		
		//Build request params
		$remote_url = $this->api_url . 'shipment';

		//Logging
		VP_Woo_Pont()->log_debug_messages($shipment, 'kvikk-create-label');

		//Make request
		$request = wp_remote_post($remote_url, array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-API-KEY' => $this->api_key
			),
			'body' => json_encode($shipment),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check validation errors
		if(isset($response['validation'])) {
			$message = $response['message'];
			if(isset($response['validation']['body'])) {
				$message = $response['validation']['body']['message'];
			}
			return new WP_Error( $response['statusCode'], $message );
		}

		//Check for errors
		if(isset($response['status']) && $response['status'] == 'fail') {
			VP_Woo_Pont()->log_error_messages($response, 'kvikk-create-label');
			$message = isset($response['message']) ? $response['message'] : __('Unknown error', 'vp-woo-pont');
			if($response['detail']) {
				unset($response['message']);
				unset($response['status']);
				$message .= ': '.json_encode($response).'';
			}
			return new WP_Error( $response['code'], $message );
		}

		//Else, it was successful
		$parcel_number = $response['data']['trackingNumber'];
		$parcel_id = $response['data']['courierTrackingNumber'];
		$pdf = base64_decode($response['data']['label']);

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('kvikk', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = $parcel_id;
		$label['number'] = $parcel_number;
		$label['pdf'] = $pdf_file['name'];

		//Update kvikk related info
		$label['kvikk_accounting'] = $response['data']['accounting'];

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {

		//Create request data
		VP_Woo_Pont()->log_debug_messages($data, 'kvikk-void-label-request');

		//So developers can modify
		$options = apply_filters('vp_woo_pont_kvikk_void_label', $data, $data);

		//Submit request
		$request = wp_remote_request( $this->api_url.'shipment/'.$options['parcel_number'], array(
			'method' => 'DELETE',
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-API-KEY' => $this->api_key
			),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Get response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for errors
		if(isset($response['status']) && $response['status'] == 'fail') {
			VP_Woo_Pont()->log_error_messages($response, 'kvikk-void-label');
			if($response['code'] == 'shipment_not_found') {
				$label = array();
				$label['success'] = true;
				return $label;
			}
			return new WP_Error( 'bad_request', $response['message'] );
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'kvikk-void-label-response');

		return $label;
	}

    public function get_tracking_link($parcel_number, $order = false) {
		return 'https://tracking.kvikk.hu/#/'.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Use the refresh api if manually refreshed
		$api_url = $this->api_url.'shipment/'.$parcel_number;
		if(isset($_POST['action']) && $_POST['action'] == 'vp_woo_pont_update_tracking_info') {
			$api_url = $this->api_url.'tracking/'.$parcel_number;
		}

		//Submit request
		$request = wp_remote_get( $api_url, array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-API-KEY' => $this->api_key
			),
		));

		VP_Woo_Pont()->log_debug_messages($request, 'kvikk-get-tracking-info');

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			return new WP_Error( 'kvikk_error_not_found', __('Shipment not found', 'vp-woo-pont') );
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Collect events
		$tracking_info = array();
		foreach ($response['data']['tracking']['events'] as $event) {
			$tracking_info[] = array(
				'date' => strtotime($event['date']),
				'event' => $event['event'],
				'label' => $event['message'],
				'location' => $event['location']
			);
		}
		
		return $tracking_info;
	}

	public function get_pickup_points() {
		$blocks = array();
		//Needs this if so it won't load on every page load
		if(is_admin() && isset( $_GET['tab']) && $_GET['tab'] == 'shipping' && isset($_GET['section']) && $_GET['section'] == 'vp_carriers' && isset($_GET['carrier']) && $_GET['carrier'] == 'kvikk') {
			$blocks = $this->get_pickup_points_response(false);
		}
		return $blocks;
	}

	public function validate_api_key() {
		check_ajax_referer( 'vp-woo-pont-validate-kvikk-api', 'nonce' );
		$pickup_points = $this->get_pickup_points_response(true);

		//Check for errors
		if(!$pickup_points) {
			delete_option('vp_woo_pont_kvikk_courier_details');
			delete_transient('vp_woo_pont_kvikk_senders');
			wp_send_json_error(array());
		}

		//Return response
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
		$pickup_points = get_transient('vp_woo_pont_kvikk_senders');

		//Get API key
		if(isset($_POST['api_key'])) {
			$api_key = sanitize_text_field($_POST['api_key']);
		} elseif(isset($_POST['vp_woo_pont_kvikk_api_key'])) {
			$api_key = sanitize_text_field($_POST['vp_woo_pont_kvikk_api_key']); 
		} else {
			$api_key = $this->api_key;
		}

		if (!$pickup_points || $refresh) {

			//Make a remote request
			$request = wp_remote_get( $this->api_url.'account-details', array(
				'headers' => array(
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
					'X-API-KEY' => $api_key
				)
			));

			//Check for API errors
			if(is_wp_error($request) || wp_remote_retrieve_response_code( $request ) != 200) {
				return false;
			}

			//Parse response
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );
			$pickup_points = array();

			if(isset($response['data']) && isset($response['data']['senders']) && is_array($response['data']['senders'])) {
				foreach ($response['data']['senders'] as $pickup_point) {
					$pickup_points[$pickup_point['_id']] = $pickup_point['name'].' ('.$pickup_point['city'].', '.$pickup_point['address'].')';
				}

				//Store the rest
				update_option('vp_woo_pont_kvikk_courier_details', $response['data']);

			} else {
				return false;
			}

			//Save vat ids for a day
			set_transient('vp_woo_pont_kvikk_senders', $pickup_points, 60 * 60 * 24);
		}

		return $pickup_points;
	}

	public function close_shipments($packages = array(), $orders = array()) {

		//Get extra shipments parameter
		$shipments_in_request = $_POST['shipments'];
		
		//Sanitize shipments
		$shipments = array();
		foreach ($shipments_in_request as $shipment) {
			$order = intval($shipment['order']);
			$package = sanitize_text_field($shipment['package']);
			$shipments[$package] = $order;
		}

		//Create request data
		$options = array(
			'pickupDate' => '',
			'pickupFor' => array(),
			'shipments' => $packages,
		);

		//Set pickup date
		if(isset($_POST['pickup_date'])) {
			$options['pickupDate'] = sanitize_text_field($_POST['pickup_date']);
		}

		//Set couriers that needs pickup
		if(isset($_POST['pickup_for'])) {
			$options['pickupFor'] = $_POST['pickup_for'];
		}

		//Try to create label
		$request = wp_remote_post( $this->api_url.'delivery-note', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-API-KEY' => $this->api_key
			),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Get response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for errors
		if(isset($response['status']) && $response['status'] == 'fail') {
			return new WP_Error( 'bad_request', $response['message'] );
		}

		//Check for api errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			return new WP_Error( 'bad_request', __('Unknown error', 'vp-woo-pont') );
		}

		//Check for failed shipments
		$failedShipments = array();
		$errors = array();
		if(isset($response['data']['failedShipments']) && count($response['data']['failedShipments']) > 0) {
			$failedShipments = $response['data']['failedShipments'];
			foreach ($failedShipments as $failed_shipment) {
				$failed_order = $shipments[$failed_shipment];
				if(($key = array_search($failed_order, $orders)) !== false) {
					unset($orders[$key]);
				}
			}

			//Get errors
			if(isset($response['data']['errors']) && count($response['data']['errors']) > 0) {
				foreach($response['data']['errors'] as $error) {
					if(is_array($error)) {
						$errors[] = array(
							'code' => $error['code'],
							'msg' => $error['msg']
						);
					} else {
						$errors[] = array(
							'code' => 'unknown',
							'msg' => $error
						);
					}
				}
			}
		}

		//Check for successful shipments
		$successfulShipments = array();
		if(isset($response['data']['successfulShipments']) && count($response['data']['successfulShipments']) > 0) {
			$successfulShipments = $response['data']['successfulShipments'];

			//Save PDF files
			$pdf_files = array();
			if(isset($response['data']['deliveryNote']) && isset($response['data']['deliveryNote']['documents'])) {
				foreach($response['data']['deliveryNote']['documents'] as $document) {
					$pdf = base64_decode($document['pdf']);
					$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('kvikk', 'list-and-close');
					VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);
					$pdf_files[$document['courier']] = $pdf_file['name'];
				}
			}

		}

		//Return response in unified format
		return array(
			'shipments' => array(),
			'orders' => $orders,
			'failed' => $failedShipments,
			'errors' => $errors,
			'pdf' => $pdf_files,
		);

	}

	public function get_merged_pdf_parameters($positions, $provider, $label_size) {
		if($provider == 'kvikk' && $label_size == 'A6') {
			$positions['sections'] = 4;
			$positions['format'] = 'A4';
			$positions['x'] = array(0, 105, 0, 105);
			$positions['y'] = array(0, 0, 148, 148);
			$positions['layout'] = 'grid';
			$positions['sticker'] = 'A6';
		}

		if($provider == 'kvikk' && $label_size == 'A5_LANDSCAPE') {
			$positions['sections'] = 2;
			$positions['format'] = 'A4-L';
			$positions['x'] = array(20, 174);
			$positions['y'] = array(28, 28);
			$positions['layout'] = 'grid';
			$positions['sticker'] = 'A6';
		}

		return $positions;
	}

	public function tracking_page_variables($args, $order) {
		if($args['provider'] == 'kvikk') {
			$provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
			$provider_id = explode('_', $provider_id);
			$args['provider'] = 'kvikk_'.$provider_id[1];
			$args['carrier_name'] = str_replace('Kvikk - Kvikk', '', $args['carrier_name']);
		}
		return $args;
	}

	public function tracking_email_variables($args, $order) {
		$courier = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
		if($courier == 'kvikk') {
			$provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
			$provider_id = explode('_', $provider_id);
			$provider_id = $provider_id[1];
			$args['carrier_name'] = str_replace('Kvikk - ', '', $args['carrier_name']);
			$logo = VP_Woo_Pont()::$plugin_url.'assets/images/carriers/'.$provider_id.'.png';
			$args['carrier_logo'] = $logo;
		}
		return $args;
	}

	public function display_ad() {

		//If Kvikk already activated, or opted to hide the ad, return false
		if(VP_Woo_Pont_Helpers::get_option('kvikk_api_key') || get_option('_vp_woo_pont_hide_kvikk_info', false)) {
			return false;
		}

		//Only show to a small percentage of users
		if(!get_option('_vp_woo_pont_hide_kvikk_info_rnd')) {
			update_option('_vp_woo_pont_hide_kvikk_info_rnd', rand(1, 100));
		}

		//If not in the range, return false
		if(get_option('_vp_woo_pont_hide_kvikk_info_rnd') >= 100) {
			return false;
		}

		?>
		<div class="kvikk-promo" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_kvikk_promo" ); ?>">
			<div class="kvikk-promo-header">
				<div class="kvikk-promo-logo"></div>
				<a href="#" class="kvikk-promo-close"><span class="dashicons dashicons-no-alt"></span></a>
			</div>
			<p>A Kvikk rendszerével nettó 750 Ft-tól szállíthatsz a népszerű futárcégekkel egy egyszerű regisztráció után</p>
			<div class="kvikk-promo-buttons">
				<a href="https://kvikk.hu/?source=plugin" target="_blank" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_kvikk_promo" ); ?>" class="button kvikk-promo-cta">Bővebben</a>
				<a href="#" class="button kvikk-promo-hide">Nem érdekel</a>
			</div>
		</div>
		<?php
	}

	public function hide_ad() {
		check_ajax_referer( 'vp_woo_pont_kvikk_promo', 'nonce' );
		update_option('_vp_woo_pont_hide_kvikk_info', true);
		wp_send_json_success();
	}

	public static function ipn_process() {
		if(isset($_GET['vp_woo_pont_kvikk_ipn'])) {

			// Retrieve API key from the request header
			$headers = getallheaders();
			$normalized_headers = array_change_key_case($headers, CASE_LOWER);
			$api_key = isset($normalized_headers['x-api-key']) ? esc_html($normalized_headers['x-api-key']) : '';
	
			// Validate API key
			if ($api_key != VP_Woo_Pont_Helpers::get_option('kvikk_api_key')) {
				wp_send_json_error();
			}

			// Get the raw POST data
			$raw_post_data = file_get_contents('php://input');
			$post_data = json_decode($raw_post_data, true);

			// Setup parameters
			$ipn_parameters = array();
			if (isset($post_data['orderID'])) $ipn_parameters['order_number'] = esc_html($post_data['orderID']);
			if (isset($post_data['trackingNumber'])) $ipn_parameters['tracking_number'] = esc_html($post_data['trackingNumber']);

			//Get order based on the tracking number meta
			$args = array(
				'meta_key' => '_vp_woo_pont_parcel_number',
				'meta_value' => $ipn_parameters['tracking_number'],
			);
			
			//Get orders
			$orders = wc_get_orders( $args );
			
			//Check for orders
			if(count($orders) == 0) {
				wp_send_json_error();
			}

			//Get order
			$order = $orders[0];

			//Get order ID
			$order_id = $order->get_order_number();

			//Check for order ID
			if($order_id != $ipn_parameters['order_number']) {
				wp_send_json_error();
			}

			//Delete the label
			$order->delete_meta_data('_vp_woo_pont_parcel_id');
			$order->delete_meta_data('_vp_woo_pont_parcel_pdf');
			$order->delete_meta_data('_vp_woo_pont_parcel_number');
			$order->delete_meta_data('_vp_woo_pont_parcel_pending');
			$order->delete_meta_data('_vp_woo_pont_parcel_info');
			$order->delete_meta_data('_vp_woo_pont_parcel_count');
			$order->delete_meta_data('_vp_woo_pont_kvikk_accounting');
			$order->save();

			//Add note
			$order->add_order_note(sprintf(esc_html__("Shipping label removed from the order, because it was removed from Kvikk. Tracking number was: %s", 'vp-woo-pont'), $ipn_parameters['tracking_number']));

			//Return success
			wp_send_json_success();

			exit();
		}
	}

	public static function register_api_endpoints() {
		register_rest_route('kvikk', '/order-details', array(
			'methods' => 'POST',
			'callback' => array(__CLASS__, 'get_order_details'),
			'permission_callback' => '__return_true',
		));

		register_rest_route('kvikk', '/update-order', array(
			'methods' => 'POST',
			'callback' => array(__CLASS__, 'update_order_status'),
			'permission_callback' => '__return_true',
		));
	}

	public static function validate_api_endpoint(WP_REST_Request $request) {

		//Get api key from header
		$api_key = $request->get_header('authorization');

		//Check if valid
		if ($api_key !== VP_Woo_Pont_Helpers::get_option('kvikk_api_key')) {
			return new WP_Error('invalid_api_key', 'Invalid API Key', array('status' => 401));
		}

		//Get JSON body params
		$params = $request->get_json_params();
		$order_id = isset($params['orderID']) ? $params['orderID'] : null;
		$tracking_number = isset($params['trackingNumber']) ? $params['trackingNumber'] : null;
		$billing_email = isset($params['email']) ? $params['email'] : null;

		//Validate parameters
		if (empty($order_id) || empty($tracking_number) || empty($billing_email)) {
			return new WP_Error('missing_params', 'Missing parameters', array('status' => 400));
		}

		//Get order
		$order = wc_get_order($order_id);
		if (!$order) {
			return new WP_Error('order_not_found', 'Order not found', array('status' => 404));
		}

		//Verify order details
		if ($order->get_meta('_vp_woo_pont_parcel_number') !== $tracking_number || $order->get_billing_email() != $billing_email) {
			return new WP_Error('invalid_order', 'Invalid order details', array('status' => 404));
		}

		return $order;
	}

	public static function get_order_details(WP_REST_Request $request) {		

		//Validate request
		$order = self::validate_api_endpoint($request);

		//If its an error
		if(is_wp_error($order)) {
			return $order;
		}
		
		//Generate response
		$data = array(
			'id' => $order->get_id(),
			'order_number' => $order->get_order_number(),
			'note' => $order->get_customer_note(),
			'items' => array(),
			'first_order' => false,
			'target_status' => (VP_Woo_Pont_Helpers::get_option('kvikk_packaged_order_status', 'no') != 'no'),
			'meta' => array(),
		);

		//Check if this is the user's first order
		$customer_orders = wc_get_orders(array(
			'billing_email' => $order->get_billing_email(),
			'exclude' => array($order->get_id()),
			'limit' => 1, 
		));

		//If theres no more orders, it is the first order for the customer
		if (empty($customer_orders)) {
			$data['is_first_order'] = true;
		}

		//Setup line items
		foreach($order->get_items() as $order_item) {
			$item = array(
				'name' => $order_item->get_name(),
				'qty' => $order_item->get_quantity(),
				'thumbnail' => '',
				'meta' => array(),
			);

			//Get product
			$product_object = is_callable( array( $order_item, 'get_product' ) ) ? $order_item->get_product() : null;
			if ( $product_object ) {

				//Get product image
				if($product_object->get_image_id()) {
					$thumbnail = wp_get_attachment_image_src( $product_object->get_image_id(), 'woocommerce_thumbnail' )[0];
					$item['thumbnail'] = $thumbnail;
				}

				//Add sku to meta
				$item['meta'][] = array(
					'label' => 'SKU',
					'value' => $product_object->get_sku()
				);				
			}

			//Additional meta data
			$meta_data = $order_item->get_formatted_meta_data();
			foreach ( $meta_data as $meta_id => $meta ) {
				$item['meta'][] = array(
					'label' => wp_kses_post( $meta->display_key ),
					'value' => wp_kses_post( force_balance_tags( $meta->display_value ) )
				);
			}

			//Append product
			$data['items'][] = $item;

		}

		//Allow developers to modify the response
		$data = apply_filters('vp_woo_pont_kvikk_order_details', $data, $order);
	
		// Return order details
		return new WP_REST_Response($data, 200);
	}

	public static function update_order_status(WP_REST_Request $request) {		
		
		//Validate request
		$order = self::validate_api_endpoint($request);

		//If its an error
		if(is_wp_error($order)) {
			return $order;
		}

		//Change order status
		$target_status = VP_Woo_Pont_Helpers::get_option('kvikk_packaged_order_status', 'no');
		if($target_status != 'no') {
			$order->update_status($target_status, __( 'Order status updated, because it was marked as packaged in the Kvikk App.', 'vp_woo_pont' ));
			$order->save();
		} else {
			$order->add_order_note(esc_html__("Shipment was marked as packaged in the Kvikk App.", 'vp-woo-pont'));
		}
	
		// Return order details
		return new WP_REST_Response(array(), 200);
	}

}
