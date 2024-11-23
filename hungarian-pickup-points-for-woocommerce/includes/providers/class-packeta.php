<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Packeta {
	protected $api_url = 'https://www.zasilkovna.cz/api/rest/';
	protected $api_key = '';
	protected $api_password = '';
	protected $sender_id = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();

	public function __construct() {
		$this->api_key = VP_Woo_Pont_Helpers::get_option('packeta_api_key');
		$this->api_password = VP_Woo_Pont_Helpers::get_option('packeta_api_password');
		$this->sender_id = VP_Woo_Pont_Helpers::get_option('packeta_sender');

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_packeta', array($this, 'get_settings'));

		//Set supported statuses
		$this->package_statuses = array(
			1 => __( "We have received the packet data. Freshly created packet.", 'vp-woo-pont'),
			2 => __( "Packet has been accepted at our branch.", 'vp-woo-pont'),
			3 => __( "Packet is waiting to be dispatched.", 'vp-woo-pont'),
			4 => __( "Packet is on the way.", 'vp-woo-pont'),
			5 => __( "Packet has been delivered to its destination, the customer has been informed via SMS.", 'vp-woo-pont'),
			6 => __( "Packet has been handed over to an external carrier for delivery.", 'vp-woo-pont'),
			7 => __( "Packet was picked up by the customer.", 'vp-woo-pont'),
			9 => __( "Packet is on the way back to the sender.", 'vp-woo-pont'),
			10 => __( "Packet has been returned to the sender.", 'vp-woo-pont'),
			11 => __( "Packet has been cancelled.", 'vp-woo-pont'),
			12 => __( "Packet has been collected and is on its way.", 'vp-woo-pont'),
			14 => __( "Customs declaration process.", 'vp-woo-pont'),
			15 => __( "Reverse packet has been accepted at our branch.", 'vp-woo-pont'),
			999 => __( "Unknown packet status.", 'vp-woo-pont')
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'ready' => array(1),
			'shipped' => array(2, 3, 6, 14),
			'delivery' => array(4, 5, 6, 12),
			'delivered' => array(7),
			'errors' => array(9, 10, 11, 999, 15)
		);

		//Ajax functions to get some stuff
		add_action( 'wp_ajax_vp_woo_pont_packeta_get_carriers', array( $this, 'get_carriers_with_ajax' ) );

		//Add age verification checkbox on product settings, only if its actually in use
		if(VP_Woo_Pont_Helpers::is_provider_configured('packeta')) {
			add_action('woocommerce_product_options_advanced', array( $this, 'product_options_fields'));
			add_action('woocommerce_admin_process_product_object', array( $this, 'save_product_options_fields'), 10, 2);
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variable_options_fields'), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'save_variable_options_fields'), 10, 2 );
		}

	}

	public function get_settings($settings) {
		$packeta_settings = array(
			array(
				'title' => __( 'Packeta settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('Packeta API key', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'packeta_api_key',
				'desc' => __('Please enter your Packeta API key if you plan to use it. This is required to get an up to date list of pickup points. Sign in into your Packeta account, click on your name top right and you can find the API key there.', 'vp-woo-pont')
			),
			array(
				'title' => __('Packeta API password', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'packeta_api_password',
				'desc' => __('Please enter your Packeta API password. This is required to generate labels for packages. Sign in into your Packeta account, click on your name top right and you can find the API key password there.', 'vp-woo-pont')
			),
			array(
				'title' => __('Indication', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'packeta_sender',
				'desc' => __('Please enter your Indication that you can fund in your Packeta account under User Informations / Senders.', 'vp-woo-pont')
			),
			array(
				'type' => 'vp_packeta_countries',
				'title' => __( 'Supported pick up points', 'vp-woo-pont' ),
				'options' => $this->get_supported_countries(),
				'default' => array('HU'),
				'id' => 'packeta_countries',
				'desc' => __('The map will show pickup points from these providers.', 'vp-woo-pont')
			),
			array(
				'type' => 'vp_packeta_carriers',
				'title' => __( 'Supported address delivery options', 'vp-woo-pont' ),
				'options' => $this->get_supported_carriers(),
				'id' => 'packeta_carriers',
				'desc' => __('If you are planning to ship worldwide with home delivery, select the provider you want to use in each supported country.', 'vp-woo-pont')
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Label format', 'vp-woo-pont' ),
				'default' => VP_Woo_Pont_Helpers::get_option('packeta_label_format', 'A6'),
				'options' => array(
					'A6 on A6' => __( 'A6 on A6', 'vp-woo-pont' ),
					'A7 on A7' => __( 'A7 on A7', 'vp-woo-pont' ),
					'A6' => __( 'A6 on A4(recommended)', 'vp-woo-pont' ),
					'A7 on A4' => __( 'A7 on A4', 'vp-woo-pont' ),
					'105x35mm on A4' => __( '105x35mm on A4', 'vp-woo-pont' ),
					'A8 on A8' => __( 'A8', 'vp-woo-pont' ),
				),
				'id' => 'packeta_sticker_size'
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$packeta_settings;
	}

	public function create_label($data) {

		//Create a new XML object
		$packet = new SimpleXMLElement('<createPacket></createPacket>');

		//Set password
		$packet->addChild('apiPassword', $this->api_password);

		//Set packat attributes
		$attributes = $packet->addChild('packetAttributes');
		$attributes->addChild('number', $data['reference_number']);
		$attributes->addChild('name', $data['customer']['first_name']);
		$attributes->addChild('surname', $data['customer']['last_name']);
		if($data['customer']['company']) {
			$attributes->addChild('company', $data['customer']['company']);
		}
		$attributes->addChild('email', $data['customer']['email']);
		$attributes->addChild('phone', $data['customer']['phone']);
		$attributes->addChild('addressId', $data['point_id']);
		$attributes->addChild('value', $data['package']['total']);
		$attributes->addChild('eshop', $this->sender_id);
		$attributes->addChild('currency', $this->get_packeta_package_currency($data['package'], false));
		$attributes->addChild('adultContent', $this->contains_adult_contents($data['order']));

		//Set default weight if not exists
		if($data['package']['weight'] == 0) {
			$attributes->addChild('weight', 1);
		} else {
			$attributes->addChild('weight', wc_get_weight($data['package']['weight'], 'kg'));
		}

		//Check for COD
		if($data['package']['cod']) {
			//Rounded for HUF, normal for rest of the currencies
			if($data['package']['currency'] == 'HUF') {
				$attributes->addChild('cod', $data['package']['total_rounded']);
			} else {
				$attributes->addChild('cod', $data['package']['total']);
			}
		}

		//Use a different point id, if its home delivery and define shipping address too
		//ID in this case is the carrier ID set in settings
		if(!$data['point_id']) {
			$order = $data['order'];
			$shipping_address = $this->get_shipping_address($order);
			$carrier_id = $this->get_packeta_carrier_from_order($order);
			$attributes->addressId = $carrier_id;
			$attributes->addChild('note', implode(', ', $shipping_address['comment']));
			$attributes->addChild('street', $shipping_address['street']);
			$attributes->addChild('houseNumber', $shipping_address['number']);
			$attributes->addChild('city', $order->get_shipping_city());
			$attributes->addChild('zip', $order->get_shipping_postcode());
			$attributes->value = $this->get_packeta_package_value($data['package'], $carrier_id);
			$attributes->currency = $this->get_packeta_package_currency($data['package'], $carrier_id);
		} else {

			if($data['provider'] == 'packeta_mpl_postapont') {
				$attributes->addressId = 4539;
				$attributes->addChild('carrierPickupPoint', $data['point_id']);
			}

			if($data['provider'] == 'packeta_foxpost') {
				$attributes->addressId = 32970;
				$attributes->addChild('carrierPickupPoint', $data['point_id']);
			}

			if($data['provider'] == 'packeta_mpl_automata') {
				$attributes->addressId = 29760;
				$attributes->addChild('carrierPickupPoint', $data['point_id']);
				$size = $attributes->addChild('size');
				$size->addChild('height', 500);
				$size->addChild('length', 310);
				$size->addChild('width', 350);
			}

		}

		//Convert to xml string
		$packet = apply_filters('vp_woo_pont_packeta_label', $packet, $data);
		$xml = $packet->asXML();

		//Logging
		VP_Woo_Pont()->log_debug_messages($packet, 'packeta-create-label');

		//Create APi request
		$request = wp_remote_post( $this->api_url, array(
			'body' => $xml,
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_encode(simplexml_load_string($response));
		$response = json_decode($response,true);

		//Check for errors
		if($response['status'] == 'fault') {
			VP_Woo_Pont()->log_error_messages($response, 'packeta-create-label');
			$error_messages[] = $response['string'];
			if(isset($response['fault']) && $response['fault'] == 'PacketAttributesFault') {
				$error_messages[] = json_encode($response['detail']['attributes']);
			}
			return new WP_Error( $response['fault'], implode('; ', $error_messages) );
		}

		//Else, it was successful
		$parcel_number = $response['result']['id'];

		//Next, generate the PDF label
		$packet_label = new SimpleXMLElement('<packetLabelPdf></packetLabelPdf>');

		//Set password and packet id
		$label_size = VP_Woo_Pont_Helpers::get_option('packeta_sticker_size', VP_Woo_Pont_Helpers::get_option('packeta_label_format', 'A6'));
		if($label_size == 'A6') $label_size = 'A6 on A4';
		$packet_label->addChild('apiPassword', $this->api_password);
		$packet_label->addChild('packetId', $parcel_number);
		$packet_label->addChild('format', $label_size);
		$packet_label->addChild('offset', '0');

		//Convert to xml string
		$packet = apply_filters('vp_woo_pont_packeta_label_pdf', $packet_label, $data);
		$xml = $packet_label->asXML();

		//Create APi request
		$request = wp_remote_post( $this->api_url, array(
			'body' => $xml,
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			VP_Woo_Pont()->log_error_messages($request, 'packeta-download-label');
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_encode(simplexml_load_string($response));
		$response = json_decode($response,true);

		//Check for errors
		if($response['status'] == 'fault') {
			VP_Woo_Pont()->log_error_messages($response, 'packeta-download-label');
			return new WP_Error( $response['fault'], $response['string'] );
		}

		//Now we have the PDF as base64, save it
		$attachment = $response['result'];
		$pdf = base64_decode($attachment);

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('packeta', $data['order_id']);
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

		//Create a new XML object
		$packet = new SimpleXMLElement('<cancelPacket></cancelPacket>');

		//Set password
		$packet->addChild('apiPassword', $this->api_password);

		//Set package ID
		$packet->addChild('packetId', $data['parcel_id']);

		//Convert to xml string
		$packet = apply_filters('vp_woo_pont_packeta_void_label', $packet, $data);
		$xml = $packet->asXML();

		//Create APi request
		$request = wp_remote_post( $this->api_url, array(
			'body' => $xml,
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_encode(simplexml_load_string($response));
		$response = json_decode($response,true);

		//Check for errors
		if($response['status'] == 'fault') {
			VP_Woo_Pont()->log_error_messages($response, 'packeta-delete-label');
			//return new WP_Error( $response['fault'], $response['string'] );
		}

		//Check for success
		$label = array();
		$label['success'] = true;

		return $label;
	}

	//Return tracking link
	public function get_tracking_link($parcel_number, $order = false) {
		return 'https://tracking.packeta.com/hu/?id='.esc_attr($parcel_number);
	}

	//Get a list of availabe countries for pickup points
	public function get_supported_countries() {

		//Default Packeta pickup points in these countries
		$supported_countries = array(
			'HU' => __('Hungary (Packeta)', 'vp-woo-pont'),
			'MPL_POSTAPONT' => __('Postapont (MPL)', 'vp-woo-pont'),
			'MPL_AUTOMATA' => __('Csomagautomata (MPL)', 'vp-woo-pont'),
			'RO' => __('Romania (Packeta)', 'vp-woo-pont'),
			'SK' => __('Slovakia (Packeta)', 'vp-woo-pont'),
			'CZ' => __('Czech Republic (Packeta)', 'vp-woo-pont'),
			'FOXPOST' => __('Foxpost', 'vp-woo-pont'),
		);

		//Get the JSON file based on the provider type
		$saved_carriers = get_option('_packeta_pickup_point_carriers');

		//Check if file exists
		if($saved_carriers === false) {
			return $supported_countries;
		}

		//Sort the array by country code
		uasort($saved_carriers, function($a, $b) {
			return strcmp($a['name'], $b['name']);
		});

		//Group by countries
		if(WC()->countries) {
			foreach ($saved_carriers as $carrier_id => $carrier) {
				$supported_countries[$carrier_id] = WC()->countries->countries[ $carrier['country'] ].': '.$carrier['name'];
			}
		}

		return $supported_countries;
	}

	public function get_enabled_countries() {
		$supported_countries = $this->get_supported_countries();
		$enabled_countries = get_option('vp_woo_pont_packeta_countries', array('HU'));
		$enabled = array();
		foreach ($enabled_countries as $enabled_country) {
			if(isset($supported_countries[$enabled_country])) {
				$enabled[$enabled_country] = $supported_countries[$enabled_country];
			}
		}
		return $enabled;
	}

	public function get_supported_carriers($raw = false) {

		//Get the JSON file based on the provider type
		$saved_carriers = get_option('_packeta_home_delivery_carriers');

		//Check if file exists
		if($saved_carriers === false) {
			return array();
		}

		//Group by countries
		$carriers = array();
		foreach ($saved_carriers as $carrier_id => $carrier) {
			if(!isset($carriers[$carrier['country']])) {
				$carriers[$carrier['country']] = array();
			}
			$carriers[$carrier['country']][$carrier_id] = $carrier['name'];
		}

		if($raw) {
			return $saved_carriers;
		} else {
			return $carriers;
		}
	}

	public function get_packeta_carrier_from_order($order) {
		$supported_carriers = get_option('vp_woo_pont_packeta_carriers', array());
		$shipping_country = $order->get_shipping_country();
		$carrier = '';
		if(isset($supported_carriers[$shipping_country]) && $supported_carriers[$shipping_country] != '') {
			$carrier = $supported_carriers[$shipping_country];
		}

		return $carrier;
	}

	public function get_packeta_package_currency($package, $carrier_id) {
		$carriers = $this->get_supported_carriers(true);
		$carrier_currency = $package['currency'];
		foreach ($carriers as $id => $carrier) {
			if($id == $carrier_id) {
				$carrier_currency = $carrier['currency'];
			}
		}
		return $carrier_currency;
	}

	public function get_packeta_package_value($package, $carrier_id) {
		$carrier_currency = $this->get_packeta_package_currency($package, $carrier_id);

		//Check if we need to convert value
		if($carrier_currency != $package['currency']) {
			return VP_Woo_Pont_Helpers::convert_currency($package['currency'], $carrier_currency, $package['total']);
		} else {
			return $package['total'];
		}

	}

	public function get_shipping_address($order) {
		$shipping_addres_1 = $order->get_shipping_address_1();
		$shipping_addres_2 = $order->get_shipping_address_2();
		$shipping_address = array(
			'street' => $shipping_addres_1,
			'number' => '-',
			'comment' => array()
		);

		//Try to split the shipping address at the first number
		if (preg_match('/\d/', $shipping_addres_1)) {
			$components = preg_split('/(?=\d)/', $shipping_addres_1, 2);
			if(!empty($components[0])) $shipping_address['street'] = $components[0];
			if(!empty($components[1])) $shipping_address['number'] = $components[1];
		}

		//Add customer note too
		if(!empty($shipping_addres_2)) {
			$shipping_address['comment'][] = $shipping_addres_2;
		}

		if(!empty($order->get_customer_note())) {
			$shipping_address['comment'][] = $order->get_customer_note();
		}

		return $shipping_address;
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_id = $order->get_meta('_vp_woo_pont_parcel_id');

		//Create a new XML object
		$packet = new SimpleXMLElement('<packetTracking></packetTracking>');

		//Set password
		$packet->addChild('apiPassword', $this->api_password);

		//Set package ID
		$packet->addChild('packetId', $parcel_id);

		//Convert to xml string
		$packet = apply_filters('vp_woo_pont_packeta_get_tracking_info', $packet, $order);
		$xml = $packet->asXML();

		//Create APi request
		$request = wp_remote_post( $this->api_url, array(
			'body' => $xml
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_encode(simplexml_load_string($response));
		$response = json_decode($response,true);
		//Check for errors
		if($response['status'] == 'fault') {
			VP_Woo_Pont()->log_error_messages($response, 'packeta-delete-label');
			return new WP_Error( $response['fault'], $response['string'] );
		}

		//Different results for just a single item
		$tracking_info = array();
		$response_events = $response['result']['record'];
		if(isset($response_events['dateTime'])) {
			$response_events = $response['result'];
		}

		//Collect events
		foreach ($response_events as $event) {
			$tracking_info[] = array(
				'date' => strtotime($event['dateTime']),
				'event' => $event['statusCode'],
				'label' => ''
			);
		}

		//Reverse array, so newest is the first
		$tracking_info = array_reverse($tracking_info);

		return $tracking_info;
	}

	public function get_carriers() {

		//Get API key
		$api_key = VP_Woo_Pont_Helpers::get_option('packeta_api_key', false);

		//If not set, return error
		if(!$api_key) {
			return false;
		}

		//Make an ajax call to Packeta API to get supported branches
		$request = wp_remote_get('https://www.zasilkovna.cz/api/v4/'.$api_key.'/branch.json?address-delivery&lang=hu', array(
			'timeout' => 100,
		));

		//Check for errors
		if( is_wp_error( $request ) ) {
			return false;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode($response,true);

		//Check for carriers
		if(!isset($response['carriers'])) {
			return false;
		}

		//Parse carriers and get supported countries and home delivery options
		$home_delivery_carriers = array();
		$pickup_point_carriers = array();
		foreach ($response['carriers'] as $carrier) {
			if($carrier['pickupPoints'] == 'false') {

				//Translate hungarian names
				$name = $carrier['name'];
				if($carrier['id'] == 763) {
					$name = 'MPL';
				}

				$home_delivery_carriers[$carrier['id']] = array(
					'name' => $name,
					'country' => strtoupper($carrier['country']),
					'currency' => $carrier['currency']
				);
			} else {

				//Exclude MPL
				if($carrier['id'] == 29760 || $carrier['id'] == 4539 || $carrier['id'] == 32970) continue;

				$pickup_point_carriers[$carrier['id']] = array(
					'name' => $carrier['name'],
					'country' => strtoupper($carrier['country']),
				);
			}
		}

		//Save results
		update_option('_packeta_home_delivery_carriers', $home_delivery_carriers);
		update_option('_packeta_pickup_point_carriers', $pickup_point_carriers);
		$saved_carriers = $this->get_supported_carriers();
		$saved_countries = $this->get_supported_countries();

		//Return success response
		return array(
			'message' => __('Import run successfully.', 'vp-woo-pont'),
			'home_delivery_carriers' => $saved_carriers,
			'pickup_point_carriers' => $saved_countries
		);

	}

	public function get_carriers_with_ajax() {
		check_ajax_referer( 'vp-woo-pont-packeta-get-carriers', 'nonce' );
		if ( !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this action.', 'vp-woo-pont' ) );
		}

		//Get API key
		$response = $this->get_carriers();

		if(!$response) {
			wp_send_json_error(array('message' => __('Something went wrong', 'vp-woo-pont')));
		} else {
			wp_send_json_success($response);
		}

	}

	public function variable_options_fields($loop, $variation_data, $variation) {
		?>
		<div>
			<?php
			woocommerce_wp_checkbox(array(
				'id' => 'vp_woo_pont_packeta_age_verification[' . $loop . ']',
				'label' => esc_html__('Age verification 18+', 'vp-woo-pont'),
				'desc_tip' => true,
				'value' => esc_attr(get_post_meta( $variation->ID, 'vp_woo_pont_packeta_age_verification', true )),
				'description' => esc_html__('If checked, the packet will be handed over only to person older than 18 years(only for internal Packeta pickup points).', 'vp-woo-pont'),
				'wrapper_class' => 'vp-woo-pont-product-options-checkbox'
			));
			?>
		</div>
		<?php
	}

	public function product_options_fields() {
		global $post;
		?>
		<div class="options_group hide_if_variable hide_if_grouped">
			<?php
			woocommerce_wp_checkbox(array(
				'id' => 'vp_woo_pont_packeta_age_verification',
				'label' => esc_html__('Age verification 18+', 'vp-woo-pont'),
				'desc_tip' => true,
				'value' => esc_attr( $post->vp_woo_pont_packeta_age_verification ),
				'description' => esc_html__('If checked, the packet will be handed over only to person older than 18 years(only for internal Packeta pickup points).', 'vp-woo-pont')
			));
			?>
		</div>
		<?php
	}

	public function save_product_options_fields($product) {
		$fields = ['packeta_age_verification'];
		foreach ($fields as $field) {
			if(isset($_REQUEST['vp_woo_pont_'.$field])) {
				$posted_data = $_REQUEST['vp_woo_pont_'.$field];
				if(!empty($posted_data) && !is_array($posted_data)) {
					$posted_data = wp_kses_post( trim( wp_unslash($_REQUEST['vp_woo_pont_'.$field]) ) );
				} else {
					$posted_data = '';
				}
				$product->update_meta_data( 'vp_woo_pont_'.$field, $posted_data);
			} else {
				$product->delete_meta_data( 'vp_woo_pont_'.$field);
			}
		}
		$product->save_meta_data();
	}

	public function save_variable_options_fields($variation_id, $i) {
		$fields = ['packeta_age_verification'];
		$product_variation = wc_get_product_object( 'variation', $variation_id );
		foreach ($fields as $field) {
			if(isset($_POST['vp_woo_pont_'.$field][$i])) {
				$custom_field = $_POST['vp_woo_pont_'.$field][$i];
				if ( ! empty( $custom_field ) ) {
					$product_variation->update_meta_data('vp_woo_pont_'.$field, wp_kses_post( trim( wp_unslash($custom_field) ) ));
				}
			} else {
				$product_variation->delete_meta_data('vp_woo_pont_'.$field);
			}
		}
		$product_variation->save();
	}

	public function contains_adult_contents($order) {
		foreach ($order->get_items() as $order_item) {
			if($order_item->get_product() && $order_item->get_product()->get_meta('vp_woo_pont_packeta_age_verification') && $order_item->get_product()->get_meta('vp_woo_pont_packeta_age_verification') == 'yes') {
				return true;
			}
		}
		return false;
	}

	public function export_label($data) {
		$order = $data['order'];
		$csv_row = array();
		$csv_row[0] = '';
		$csv_row[1] = $data['reference_number'];
		$csv_row[2] = $data['customer']['first_name'];
		$csv_row[3] = $data['customer']['last_name'];
		$csv_row[4] = $data['customer']['company'];
		$csv_row[5] = $data['customer']['email'];
		$csv_row[6] = $data['customer']['phone'];
		$csv_row[7] = ($data['package']['cod']) ? $data['package']['total'] : '';
		$csv_row[8] = $this->get_packeta_package_currency($data['package'], false);
		$csv_row[9] = $data['package']['total'];
		if($data['package']['weight'] == 0) {
			$csv_row[10] = 1;
		} else {
			$csv_row[10] = wc_get_weight($data['package']['weight'], 'kg');
		}
		$csv_row[11] = $data['point_id'];
		$csv_row[12] = $this->sender_id;
		$csv_row[13] = $this->contains_adult_contents($data['order']);
		for ($i = 13; $i < 27; $i++) {
			$csv_row[] = '';
		}

		if(!$data['point_id']) {
			$shipping_address = $this->get_shipping_address($order);
			$csv_row[15] = $shipping_address['street'];
			$csv_row[16] = $shipping_address['number'];
			$csv_row[17] = $order->get_shipping_city();
			$csv_row[18] = $order->get_shipping_postcode();
			$carrier_id = $this->get_packeta_carrier_from_order($order);
			$csv_row[19] = $carrier_id;
			$csv_row[8] = $this->get_packeta_package_currency($data['package'], $carrier_id);
			$csv_row[9] = $this->get_packeta_package_value($data['package'], $carrier_id);
		}

		$first_row = array('version 6');
		$second_row = array();

		return array(
			'data' => array($first_row, $second_row, $csv_row)
		);
	}

}
