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
	public $extra_services = array();

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
			'out_for_delivery' => 'Csomag kiszállítás alatt(címzettnek vagy csomagpontra)',
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
		add_action('vp_woo_pont_modal_generate_before_table', array($this, 'display_ad'));

		//Process IPN request
		add_action( 'init', array( __CLASS__, 'ipn_process' ), 11 );

		//Handle QR scan
		add_action('rest_api_init', array(__CLASS__, 'register_api_endpoints'));

		//Show Foxpost & Packeta notice
		add_action( 'woocommerce_update_options_shipping', array( $this, 'save_settings') );
		add_action('admin_notices', array($this, 'show_foxpost_packeta_notice'));
		add_action( 'wp_ajax_vp_woo_pont_kvikk_foxpost_packeta_notice', array( $this, 'hide_foxpost_packeta_notice' ) );

		//Support multi-parcel shipments
		add_action('vp_woo_pont_metabox_after_generate_options', array( $this, 'add_additional_package_fields'));
		add_action('vp_woo_pont_metabox_after_generate_options', array( $this, 'add_extra_services_fields'));

		$this->extra_services = array(
			'insurance' => __('Value insurance', 'vp-woo-pont'),
			'oversized' => __('Bulky handling', 'vp-woo-pont'),
			'amorf' => __('Amorf shaped package', 'vp-woo-pont'),
			'fragile' => __('Fragile handling', 'vp-woo-pont'),
			'nextday' => __('One business day time guarantee', 'vp-woo-pont'),
		);

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
				'class' => 'kvikk'
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
				'desc' => __( 'If you scan the label in the Kvikk App, you can mark the order to be in this status.', 'vp-woo-pont' ),
				'id' => 'kvikk_packaged_order_status',
			),
			array(
				'type' => 'text',
				'title' => __('Value insurance limit', 'vp-woo-pont'),
				'default' => 50000,
				'desc_tip' => __('If empty, it will be the order total up to 50.000 HUF. If you need a higher maximum limit, enter that here. In this case the value insurance service will be added to the shipment automatically.', 'vp-woo-pont'),
				'id' => 'kvikk_insurance_limit'
			),
			'kvikk_fragile_products' => array(
				'title' => __('Fragile products', 'vp-woo-pont'),
				'type' => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => array(),
				'id' => 'kvikk_fragile_products',
				'desc_tip' => __('Select a product attribute or shipping class that relates to fragile shipments, so the generated label will be marked as fragile by default.', 'vp-woo-pont'),
			),
			'kvikk_oversized_products' => array(
				'title' => __('Oversized products', 'vp-woo-pont'),
				'type' => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => array(),
				'id' => 'kvikk_oversized_products',
				'desc_tip' => __('Select a product attribute or shipping class that relates to oversized shipments, so the generated label will be marked as oversized by default.', 'vp-woo-pont'),
			),
			array(
				'type' => 'sectionend'
			)
		);

		//Load product categories on settings page
		if($this->is_settings_page()) {
			$kvikk_settings['kvikk_fragile_products']['options'] = VP_Woo_Pont_Helpers::get_product_tags()+VP_Woo_Pont_Helpers::get_shipping_classes();
			$kvikk_settings['kvikk_oversized_products']['options'] = VP_Woo_Pont_Helpers::get_product_tags()+VP_Woo_Pont_Helpers::get_shipping_classes();
		}

		return $settings+$kvikk_settings;
	}

	//Check if we are on the settings page
	public function is_settings_page() {
		return (isset($_GET['section']) && $_GET['section'] === 'vp_carriers');
	}

    public function get_kvikk_db() {

		//URL of the database
		$url = 'https://points-api.kvikk.hu/points?search=packeta,mpl,foxpost,gls,dpd&country=HU';

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
		$results = array('packeta_zpont' => array(), 'packeta_zbox' => array(), 'foxpost' => array(), 'mpl_automata' => array(), 'mpl_postapont' => array(), 'mpl_posta' => array(), 'gls_locker' => array(), 'gls_shop' => array(), 'dpd_parcelshop' => array(), 'dpd_alzabox' => array());

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
        //$groups['kvikk_expressone'] = __('Kvikk Express One', 'vp-woo-pont');
        $groups['kvikk_famafutar'] = __('Kvikk FámaFutár', 'vp-woo-pont');
        $groups['kvikk_gls'] = __('Kvikk GLS', 'vp-woo-pont');
        $groups['kvikk_dpd'] = __('Kvikk DPD', 'vp-woo-pont');
        //$groups['kvikk_dhl'] = __('Kvikk DHL', 'vp-woo-pont');
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
        $providers['kvikk_gls_locker'] = 'GLS Automata';
        $providers['kvikk_gls_shop'] = 'GLS Csomagpont';
        $providers['kvikk_dpd_parcelshop'] = 'DPD Csomagpont';
        $providers['kvikk_dpd_alzabox'] = 'DPD AlzaBox';
        return $providers;
    }

    public function add_provider_subgroups($subgroups) {
		$subgroups['kvikk'] = array('mpl_posta', 'mpl_postapont', 'mpl_automata', 'packeta_zpont', 'packeta_zbox', 'foxpost', 'gls_shop', 'gls_locker', 'dpd_parcelshop', 'dpd_alzabox');
        return $subgroups;
    }

	public function create_label($data) {

		//Get courier
		$courier = $data['provider']; 

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
			'note' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'kvikk', 100),
			'cod' => 0,
			'parcels' => array(
				array(
					'weight' => $data['package']['weight_gramm'],
					'value' => $data['package']['total'],
				)
			)
		);

		//If manually generated, use submitted weight instead
		if(isset($data['options']) && isset($data['options']['package_weight']) && $data['options']['package_weight'] > 0) {
			$shipment['parcels'][0]['weight'] = $data['options']['package_weight'];
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
			$shipment['parcels'][0]['width'] = $data['package']['size']['width'];
			$shipment['parcels'][0]['height'] = $data['package']['size']['height'];
			$shipment['parcels'][0]['length'] = $data['package']['size']['length'];
		}

		//Set maximum insurance value
		if($insurance_limit = VP_Woo_Pont_Helpers::get_option('kvikk_insurance_limit', 50000)) {
			if($shipment['parcels'][0]['value'] > $insurance_limit) {
				$shipment['parcels'][0]['value'] = $insurance_limit;
			}
		}

		//Add insurance service if needed
		$enabled_services = array();
		if($shipment['parcels'][0]['value'] > 50000) {
			$enabled_services[] = 'insurance';
		}

		//If manually generated, check submitted extra services
		if($data['source'] == 'metabox') {
			if(isset($data['options']) && isset($data['options']['extra_services'])) {
				$enabled_services = $data['options']['extra_services'];
			}
		} else {
			//Else, check for extra services
			$services_check = array('fragile', 'oversized');
			foreach ($services_check as $check_service) {
				if ($this->is_extra_service_needed($order, $check_service)) {
					$enabled_services[] = $check_service;
				}
			}
		}

		//Add extra services
		if(!empty($enabled_services)) {
			$shipment['parcels'][0]['services'] = $enabled_services;
		}

		//Support for multiple packages
		if(isset($_POST['package_count']) && $_POST['package_count'] > 1 && isset($_POST['additional_package_data'])) {
			$shipment['parcels'] = array();
			$additional_package_data = json_decode(stripslashes($_POST['additional_package_data']), true);

			foreach ($additional_package_data as $parcel_index => $parcel_data) {
				$weight = $parcel_data['weight'];
				$cost = $parcel_data['cost'];
				$length = $parcel_data['length'];
				$width = $parcel_data['width'];
				$height = $parcel_data['height'];
				$services = $parcel_data['services'];
				
				// Process this specific parcel with all its data
				$parcel = array(
					'weight' => intval($weight),
					'value' => intval($cost),
					'services' => $services
				);

				if($length && $width && $height) {
					$parcel['length'] = intval($length);
					$parcel['width'] = intval($width);
					$parcel['height'] = intval($height);
				}

				$shipment['parcels'][] = $parcel;
			}
		}

		//Remove the oversized and fragile services if the courier is not mpl or famafutar from all parcels
		if(!in_array($courier_details[0], array('mpl', 'famafutar'))) {
			foreach ($shipment['parcels'] as $index => $parcel) {
				if(isset($parcel['services']) && is_array($parcel['services'])) {
					$shipment['parcels'][$index]['services'] = array_diff($parcel['services'], array('oversized', 'fragile'));
				}
			}
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
		$request = wp_remote_get( $api_url, apply_filters('vp_woo_pont_kvikk_tracking_request', array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-API-KEY' => $this->api_key
			),
		), $order));

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
		if(VP_Woo_Pont_Helpers::get_option('kvikk_api_key') || get_option('_vp_woo_pont_hide_kvikk_info_v2', false)) {
			return false;
		}

		?>
		<div class="kvikk-promo" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_kvikk_promo" ); ?>">
			<div class="kvikk-promo-header">
				<div class="kvikk-promo-logo"></div>
				<a href="#" class="kvikk-promo-close"><span class="dashicons dashicons-no-alt"></span></a>
			</div>
			<p>A Kvikk-en keresztül ennyibe kerülne egy 1kg-os csomagnak a házhozszállítása:</p>
			<ul class="kvikk-promo-pricing">
				<li><i class="vp-woo-pont-provider-icon-posta"></i> MPL <strong data-kvikk-promo-price="mpl"> </strong></li>
				<li><i class="vp-woo-pont-provider-icon-gls"></i> GLS <strong data-kvikk-promo-price="gls"> </strong></li>
				<li><i class="vp-woo-pont-provider-icon-dpd"></i> DPD <strong data-kvikk-promo-price="dpd"> </strong></li>
				<li><i class="vp-woo-pont-provider-icon-famafutar"></i> FámaFutár <strong data-kvikk-promo-price="famafutar"> </strong></li>
			</ul>
			<p class="kvikk-promo-footnote">Nettó árak, <strong>tartalmazza az útdíjat és az üzemanyagköltséget</strong>.</p>
			<div class="kvikk-promo-buttons">
				<a href="https://kvikk.hu/?source=plugin" target="_blank" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_kvikk_promo" ); ?>" class="button kvikk-promo-cta">Érdekel</a>
				<a href="#" class="button kvikk-promo-hide">Elrejtés</a>
			</div>
		</div>
		<?php
	}

	public function hide_ad() {
		check_ajax_referer( 'vp_woo_pont_kvikk_promo', 'nonce' );
		update_option('_vp_woo_pont_hide_kvikk_info_v2', true);
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

	public static function save_settings() {
		if(isset($_GET['carrier']) && $_GET['carrier'] == 'kvikk') {
			update_option('vp_woo_pont_kvikk_foxpost_type_selected', true);
		}
	}

	public static function show_foxpost_packeta_notice() {

		//Get current screen
		$screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		//Check if we are on the right screen
		if(in_array($screen_id, wc_get_screen_ids())) {

			//Check if we need to show the notice
			if(VP_Woo_Pont_Helpers::get_option('kvikk_api_key') && !VP_Woo_Pont_Helpers::get_option('kvikk_foxpost_type_selected')) {

				//Get enabled providers
				$providers = get_option('vp_woo_pont_enabled_providers');

				//Check if we have either kvikk_packeta_zpont, kvikk_packeta_zbox or kvikk_foxpost enabled
				$packeta_providers = array('kvikk_packeta_zpont', 'kvikk_packeta_zbox', 'kvikk_foxpost');
				$packeta_enabled = false;
				foreach ($providers as $provider) {
					if(in_array($provider, $packeta_providers)) {
						$packeta_enabled = true;
						break;
					}
				}

				//If we have packeta enabled, show the notice
				if(!$packeta_enabled) {
					return;
				}

				//Only show after 2025 may 1st
				if(strtotime('2025-05-01') > time()) {
					return;
				}				

			?>
			<div class="notice notice-warning vp-woo-pont-kvikk-foxpost-packeta-notice is-dismissible">
				<div class="vp-woo-pont-kvikk-foxpost-packeta-notice-logo"></div>

				<p>Mostantól a <strong>Foxpost és Packeta csomagokat</strong> is feladhatod <strong>Foxpost automatában</strong>, nem csak Packeta Z-Pont átvevőhelyen.</p>
				<p>Ha szeretnéd ezt használni, lépj be a <a href="https://app.kvikk.hu" target="_blank">Kvikk fiókodba</a>, és a <strong>Fiók beállításoknál</strong> válaszd ki, hogy melyik feladási módot szeretnéd használni: továbbra is Z-Pont átvevőhelyen adod fel, vagy váltasz Foxpost automatában történő feladásra.</p>
				<p><a href="https://support.kvikk.hu/docs/shipments/couriers/foxpost-information/" target="_blank">További információ és segítség a beállításhoz &rarr;</a></p>
				
				<div class="vp-woo-pont-kvikk-foxpost-packeta-notice-buttons">
					<a href="https://app.kvikk.hu" target="_blank" class="button button-primary" id="vp-woo-pont-foxpost-packeta-notice-close">Fiók beállítások</a>
					<a href="https://support.kvikk.hu/docs/shipments/couriers/foxpost-information/" target="_blank" class="button" id="vp-woo-pont-foxpost-packeta-notice-close">Bővebb infó</a>
				</div>
			</div>

			<script>
				jQuery(document).ready(function($) {
					$( document ).on( 'click', '.vp-woo-pont-kvikk-foxpost-packeta-notice a, .vp-woo-pont-kvikk-foxpost-packeta-notice .notice-dismiss', function () {
						$('.vp-woo-pont-kvikk-foxpost-packeta-notice').addClass('loading');
						var data = {
							action: 'vp_woo_pont_kvikk_foxpost_packeta_notice',
							nonce: '<?php echo wp_create_nonce( "vp_woo_pont_kvikk_foxpost_packeta_notice" ); ?>'
						};
						$.post(ajaxurl, data, function(response) {
							$('.vp-woo-pont-kvikk-foxpost-packeta-notice').slideUp();
						});
					});
				});

			</script>
			<?php
			}
		}

	}

	public function hide_foxpost_packeta_notice() {
		check_ajax_referer( 'vp_woo_pont_kvikk_foxpost_packeta_notice', 'nonce' );
		update_option('vp_woo_pont_kvikk_foxpost_type_selected', true);
		wp_send_json_success();
	}

	public function add_extra_services_fields($order) {
		?>
			<li data-providers="[kvikk]" class="vp-woo-pont-metabox-generate-options-item vp-woo-pont-package-services">
				<label><?php esc_html_e('Extra services','vp-woo-pont'); ?></label>
				<ul>
					<?php foreach ($this->extra_services as $service_id => $service): ?>
						<?php
						$saved_options = [];
						$order_total = $order->get_total();
						$insurance_max = VP_Woo_Pont_Helpers::get_option('kvikk_insurance_limit', 50000);
						if ($order_total > $insurance_max) {
							$saved_options[] = 'insurance';
						}
						$services_check = array('fragile', 'oversized');
						foreach ($services_check as $check_service) {
							if ($this->is_extra_service_needed($order, $check_service)) {
								$saved_options[] = $check_service;
							}
						}
						?>
						<li>
							<label for="vp_woo_pont_extra_service_<?php echo esc_attr($service_id); ?>">
								<input type="checkbox" name="vp_woo_pont_extra_services" id="vp_woo_pont_extra_service_<?php echo esc_attr($service_id); ?>" value="<?php echo esc_attr($service_id); ?>" <?php checked(in_array($service_id, $saved_options)); ?> />
								<span><?php echo esc_html__($service, 'vp-woo-pont'); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</li>
		<?php
	}
	public function add_additional_package_fields($order) {
		$packaging_types = get_option('vp_woo_pont_packagings');

		?>
			<li style="display:none" id="vp_woo_pont_kvikk_parcels">
				<label>Küldemények adatai</label>
				<ul class="vp-woo-pont-kvikk-parcels">
					<li class="vp-woo-pont-kvikk-parcel-sample" style="display:none;">
						<div class="vp-woo-pont-kvikk-parcel-main-fields">
							<div class="vp-woo-pont-kvikk-parcel-unit">
								<input type="text" name="vp_woo_pont_kvikk_package_weights[]" placeholder="" value="" />
								<span>gramm</span>
							</div>
							<div class="vp-woo-pont-kvikk-parcel-unit">
								<input type="text" name="vp_woo_pont_kvikk_package_costs[]" placeholder="" value="" />
								<span>Ft</span>
							</div>
						</div>
						<?php if($packaging_types) { ?>
							<div class="vp-woo-pont-kvikk-parcel-package-size">
								<div class="vp-woo-pont-kvikk-parcel-unit">
									<input type="text" name="vp_woo_pont_kvikk_length" placeholder="" value="" />
									<span>cm</span>
								</div>
								<div class="vp-woo-pont-kvikk-parcel-unit">
									<input type="text" name="vp_woo_pont_kvikk_width" placeholder="" value="" />
									<span>cm</span>
								</div>
								<div class="vp-woo-pont-kvikk-parcel-unit">
									<input type="text" name="vp_woo_pont_kvikk_height" placeholder="" value="" />
									<span>cm</span>
								</div>
								<a href="#" class="vp-woo-pont-kvikk-parcel-boxes-btn">
									<span class="dashicons dashicons-archive"></span>
								</a>
							</div>
							<ul class="vp-woo-pont-kvikk-parcel-packaging-types" style="display: none;">
								<?php foreach ( $packaging_types as $packaging_id => $packaging_type ): ?>
									<li>
										<input type="radio" name="vp_woo_pont_kvikk_packaging_type" data-length="<?php echo esc_attr($packaging_type['length']); ?>" data-width="<?php echo esc_attr($packaging_type['width']); ?>" data-height="<?php echo esc_attr($packaging_type['height']); ?>" id="vp_woo_pont_kvikk_packaging_type_<?php echo esc_attr($packaging_type['sku']); ?>" value="<?php echo esc_attr($packaging_type['sku']); ?>">
										<label for="vp_woo_pont_kvikk_packaging_type_<?php echo esc_attr($packaging_type['sku']); ?>">
											<?php echo esc_html($packaging_type['name']); ?>
											<small>
												<?php echo esc_html($packaging_type['length']); ?>x<?php echo esc_html($packaging_type['width']); ?>x<?php echo esc_html($packaging_type['height']); ?>cm
											</small>
										</label>
									</li>
								<?php endforeach; ?>
								<li>
									<input type="radio" name="vp_woo_pont_kvikk_packaging_type" id="vp_woo_pont_kvikk_packaging_type_custom" value="custom">
									<label for="vp_woo_pont_kvikk_packaging_type_custom">
										<?php esc_html_e('Custom packaging', 'vp-woo-pont'); ?>
									</label>
								</li>
							</ul>
						<?php } ?>

						<ul>
							<?php foreach (VP_Woo_Pont()->providers['kvikk']->extra_services as $service_id => $service): ?>
								<?php
								if($service_id == 'nextday') {
									continue;
								}
								$saved_options = [];
								$services_check = array('fragile', 'oversized');
								foreach ($services_check as $check_service) {
									if ($this->is_extra_service_needed($order, $check_service)) {
										$saved_options[] = $check_service;
									}
								}
								?>
								<li>
									<label for="vp_woo_pont_extra_service_<?php echo esc_attr($service_id); ?>">
										<input type="checkbox" name="vp_woo_pont_kvikk_services" id="vp_woo_pont_kvikk_service_<?php echo esc_attr($service_id); ?>" value="<?php echo esc_attr($service_id); ?>" <?php checked(in_array($service_id, $saved_options)); ?> />
										<span><?php echo esc_html__($service, 'vp-woo-pont'); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>

					</li>
				</ul>
			</li>

			<script>
			jQuery(document).ready(function($){
				function renderKvikkParcels(count) {
					var $list = $('.vp-woo-pont-kvikk-parcels');
					var $sample = $list.find('.vp-woo-pont-kvikk-parcel-sample');
					$list.find('.vp-woo-pont-kvikk-parcel:not(.vp-woo-pont-kvikk-parcel-sample)').remove();
					for (var i = 0; i < count; i++) {
						var $clone = $sample.clone(true, true).removeClass('vp-woo-pont-kvikk-parcel-sample').addClass('vp-woo-pont-kvikk-parcel').show();
						// Ensure unique name attributes for packaging_type radios
						$clone.find('input[type=radio][name=vp_woo_pont_kvikk_packaging_type]').each(function(){
							$(this).attr('name', 'vp_woo_pont_kvikk_packaging_type_' + i);
						});
						// Preselect custom if none selected
						var $radios = $clone.find('input[type=radio][name="vp_woo_pont_kvikk_packaging_type_' + i + '"]');
						if ($radios.filter(':checked').length === 0) {
							$radios.filter('[value="custom"]').prop('checked', true).trigger('change');
						}
						$list.append($clone);
					}
				}

				$(document).on('change', '#vp_woo_pont_package_count', function(){
					var package_count = parseInt($(this).val(), 10) || 0;
					var provider = $('.vp-woo-pont-metabox-content').data('provider_id');
					$('#vp_woo_pont_kvikk_parcels').hide();
					$('.vp-woo-pont-package-size').show();
					$('.vp-woo-pont-package-weight').show();
					$('.vp-woo-pont-package-services').show();

					if(provider != 'kvikk') {
						return;
					}

					if(package_count > 1) {
						$('#vp_woo_pont_kvikk_parcels').show();
						$('.vp-woo-pont-package-weight').hide();
						$('.vp-woo-pont-package-size').hide();
						$('.vp-woo-pont-package-services').hide();
						renderKvikkParcels(package_count);
					}
				});

				// Show/hide packaging types list on "Boxes" button click
				$(document).on('click', '.vp-woo-pont-kvikk-parcel-boxes-btn', function(e){
					e.preventDefault();
					var $parcel = $(this).closest('.vp-woo-pont-kvikk-parcel, .vp-woo-pont-kvikk-parcel-sample');
					var $types = $parcel.find('.vp-woo-pont-kvikk-parcel-packaging-types');
					if ($types.is(':visible')) {
						$types.hide();
					} else {
						$types.show();
					}
				});

				// When a packaging type is selected, fill in the dimensions, set readonly, and hide the list
				$(document).on('change', '.vp-woo-pont-kvikk-parcel-packaging-types input[type=radio]', function(){
					var $li = $(this).closest('.vp-woo-pont-kvikk-parcel, .vp-woo-pont-kvikk-parcel-sample');
					var $length = $li.find('input[name="vp_woo_pont_kvikk_length"]');
					var $width = $li.find('input[name="vp_woo_pont_kvikk_width"]');
					var $height = $li.find('input[name="vp_woo_pont_kvikk_height"]');
					if($(this).val() !== 'custom') {
						$length.val($(this).data('length')).prop('readonly', true);
						$width.val($(this).data('width')).prop('readonly', true);
						$height.val($(this).data('height')).prop('readonly', true);
					} else {
						$length.val('').prop('readonly', false);
						$width.val('').prop('readonly', false);
						$height.val('').prop('readonly', false);
					}
					$li.find('.vp-woo-pont-kvikk-parcel-packaging-types').hide();
				});

				// Make clicking the label also select the radio button
				$(document).on('click', '.vp-woo-pont-kvikk-parcel-packaging-types label', function(e){
					var $radio = $(this).closest('li').find('input[type=radio]');
					if (!$radio.prop('checked')) {
						$radio.prop('checked', true).trigger('change');
					}
					// Prevent default label click to avoid double event
					e.preventDefault();
				});

				// AJAX Prefilter: collect all parcel data and append to request
				$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
					if(options && typeof options.data === 'string' && options.data.includes('vp_woo_pont_generate_label')) {
						var package_count = parseInt($('input#vp_woo_pont_package_count').val(), 10) || 0;
						if(package_count > 1) {
							var package_data = {};
							$('.vp-woo-pont-kvikk-parcel').each(function(index){
								var weight = $(this).find('input[name="vp_woo_pont_kvikk_package_weights[]"]').val();
								var cost = $(this).find('input[name="vp_woo_pont_kvikk_package_costs[]"]').val();
								var length = $(this).find('input[name="vp_woo_pont_kvikk_length"]').val();
								var width = $(this).find('input[name="vp_woo_pont_kvikk_width"]').val();
								var height = $(this).find('input[name="vp_woo_pont_kvikk_height"]').val();

								var package_services_array = $(this).find('input[name="vp_woo_pont_kvikk_services"]:checked')
									.map(function (){
									return $(this).val();
								}).toArray();

								// Store all package data with explicit index
								package_data[index] = {
									weight: weight || '',
									cost: cost || '',
									length: length || '',
									width: width || '',
									height: height || '',
									services: package_services_array
								};
							});
							
							// Send as JSON for easy server-side processing
							options.data += '&additional_package_data='+encodeURIComponent(JSON.stringify(package_data));

						}
					}
				});
			});
			</script>
			<?php
	}

	public function is_extra_service_needed($order, $service_type) {
		$fragile_product_tags = VP_Woo_Pont_Helpers::get_option('kvikk_'.$service_type.'_products', array());
		if(empty($fragile_product_tags)) {
			return false;
		}

		$order_items = $order->get_items();
		foreach ($order_items as $item) {
			$product = $item->get_product();
			if($product) {
				$tags = $product->get_tag_ids();
				$shipping_class = $product->get_shipping_class();
				if(in_array($shipping_class, $fragile_product_tags)) {
					return true;
				}

				foreach ($tags as $tag) {
					if(in_array($tag, $fragile_product_tags)) {
						return true;
					}
				}
			}
		}

		return false;
	}

}