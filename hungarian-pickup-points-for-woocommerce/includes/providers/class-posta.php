<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Posta {
	protected $api_url = 'https://core.api.posta.hu/';
	protected $api_key = '';
	protected $api_password = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();
	public $extra_services = array();

	public function __construct() {
		$this->api_key = VP_Woo_Pont_Helpers::get_option('posta_api_key');
		$this->api_password = VP_Woo_Pont_Helpers::get_option('posta_api_password');

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_posta', array($this, 'get_settings'));

		//If dev mode, use a different API url
		if(VP_Woo_Pont_Helpers::get_option('posta_dev_mode', 'no') == 'yes') {
			$this->api_url = 'https://sandbox.api.posta.hu/';
		}

		//Set supported statuses
		$this->package_statuses = array(
			1 => "Postára beszállítás folyamatban",
			2 => "A küldeményt a feladótól átvettük",
			3 => "A küldeményt a feladó előrejelezte, az átadást követően megkezdjük a feldolgozást",
			4 => "A küldemény feldolgozás alatt",
			5 => "A küldemény Csomagautomatából postára szállítva (műszaki hiba miatt)",
			6 => "A küldemény Csomagautomatából postára szállítva",
			7 => "A küldemény Csomagautomatából postára szállítva (lejárt őrzési idő miatt)",
			8 => "A küldemény szállítás alatt",
			9 => "A küldemény nem kézbesíthető (megőrzésre továbbítva)",
			10 => "A küldemény nem kézbesíthető (cég megszűnt)",
			11 => "A küldemény nem kézbesíthető (elköltözött)",
			12 => "A küldemény nem kézbesíthető (átvételt megtagadta)",
			13 => "A küldemény nem kézbesíthető (nincs jogosult átvevő)",
			14 => "A küldemény nem kézbesíthető (hibás vagy hiányos címzés)",
			15 => "A küldemény nem kézbesíthető (ismeretlen címzett)",
			16 => "A küldemény nem kézbesíthető (nem kereste)",
			17 => "Utánküldés új címre (megrendelés alapján)",
			18 => "Továbbítás másik kézbesítő postára (címzetti rendelkezés alapján)",
			19 => "A küldemény nem kézbesíthető (sérülés miatt)",
			20 => "A küldemény nem kézbesíthető (a feladó visszakérte)",
			21 => "A küldemény nem kézbesíthető (címzett és feladó ismeretlen), megőrzésre továbbítva",
			22 => "Ismételt kézbesítésre továbbítás új címre",
			23 => "A küldemény szállítás alatt (ismételt kézbesítésre)",
			24 => "A küldemény nem kézbesíthető (kézbesítés akadályozott)",
			25 => "A küldemény nem kézbesíthető (átvételt megtagadta)",
			26 => "A küldemény postán átvehető",
			27 => "Telefonos egyeztetés címzettel",
			28 => "A küldemény Csomagautomatából átvehető (az sms/email-ben kapott kóddal)",
			29 => "Sikertelen kézbesítés Csomagautomatából",
			30 => "A küldemény szállítás alatt",
			31 => "Másnapi kézbesítésre előkészítve (címzett kérésére)",
			32 => "Sikertelen kézbesítés",
			33 => "A küldemény a kézbesítőnél van",
			34 => "A küldemény PostaPonton 12:00 után átvehető",
			35 => "Sikeresen kézbesítve Csomagautomatából",
			36 => "Árufizetési összeg feladónak kifizetve",
			37 => "Feladónak visszakézbesítve",
			38 => "Sikeresen kézbesítve",
			39 => "Sikeresen kézbesítve háznál",
			40 => "Sikeresen kézbesítve Postahelyen",
			41 => "Sikeres kézbesítés rögzítése belső rendszerben",
			42 => "Sikeresen kézbesítve PostaPonton"
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array(1, 2, 4, 8, 17, 18, 22, 23, 30, 31),
			'delivery' => array(5, 6, 7, 26, 27, 28, 33, 34),
			'delivered' => array(35, 36, 38, 39, 40, 41, 42),
			'errors' => array(9, 10, 11, 12, 13, 14, 15, 16, 19, 20, 21, 24, 25, 29, 32)
		);

		$this->extra_services = array(
			'K_TOR' => __('Fragile handling', 'vp-woo-pont'),
			'K_MSZ' => __('Saturday delivery', 'vp-woo-pont'),
			'K_IDO' => __('One business day time guarantee', 'vp-woo-pont'),
			'K_ALA' => __('Delivered to an occasional recipient', 'vp-woo-pont'),
			'K_TER' => __('Bulky handling', 'vp-woo-pont'),
		);
	}

	public function get_settings($settings) {
		$posta_settings = array(
			array(
				'title' => __( 'Posta settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
				'desc' => __('The developer representing the company can access these APIs on the Developer Portal by selecting the Applications menu item after logging in.', 'vp-woo-pont'),
			),
			array(
				'title' => __('API key', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_api_key'
			),
			array(
				'title' => __('API password', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_api_password'
			),
			array(
				'title' => __('Customer code', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_customer_code'
			),
			array(
				'title' => __('Agreement code', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_agreement_code'
			),
			array(
				'title' => __('Internatinal agreement code', 'vp-woo-pont'),
				'type' => 'text',
				'desc_tip' => __('If you ship outside of Hungary, you can setup a different agreement code for international shipments', 'vp-woo-pont'),
				'id' => 'posta_agreement_code_int'
			),
			array(
				'title'    => __( 'Enable DEV mode', 'vp-woo-pont' ),
				'type'     => 'checkbox',
				'id' => 'posta_dev_mode',
			),
			array(
				'title' => __('Sender name', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_sender_name'
			),
			array(
				'title' => __('Sender address', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_sender_address'
			),
			array(
				'title' => __('Sender city', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_sender_city'
			),
			array(
				'title' => __('Sender postcode', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_sender_postcode'
			),
			array(
				'title' => __('Sender phone number', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'posta_sender_phone'
			),
			array(
				'title' => __('Sender email address', 'vp-woo-pont'),
				'type' => 'text',
				'default' => get_bloginfo('admin_email'),
				'id' => 'posta_sender_email'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A5',
				'options' => array(
					'A4' => __( 'A4', 'vp-woo-pont' ),
					'A5' => __( 'A5 at the top of an A4 page', 'vp-woo-pont' ),
					'A5inA4' => __( 'Two A5 landscape on an A4 page', 'vp-woo-pont' ),
					'A5E' => __( 'A5 landscape', 'vp-woo-pont' ),
					'A5E_EXTRA' => __( 'A5 landscape(with comments)', 'vp-woo-pont' ),
					'A5E_STAND' => __( 'A5 portrait', 'vp-woo-pont' ),
					'A6' => __( 'A6', 'vp-woo-pont' ),
					'A6inA4' => __( 'A6 on A4(recommended)', 'vp-woo-pont' ),
				),
				'id' => 'posta_sticker_size'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker format', 'vp-woo-pont' ),
				'default' => 'PDF',
				'options' => array(
					'PDF' => __( 'PDF document', 'vp-woo-pont' ),
					'ZPL' => __( 'ZPL document', 'vp-woo-pont' )
				),
				'id' => 'posta_sticker_format'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Retention period', 'vp-woo-pont' ),
				'default' => '5',
				'options' => array(
					'0' => __( '0 day', 'vp-woo-pont' ),
					'5' => __( '5 days', 'vp-woo-pont' ),
					'10' => __( '10 days', 'vp-woo-pont' ),
				),
				'id' => 'posta_retention'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Parcel size', 'vp-woo-pont' ),
				'default' => 'M',
				'options' => array(
					'S' => __( 'S', 'vp-woo-pont' ),
					'M' => __( 'M', 'vp-woo-pont' ),
					'L' => __( 'L', 'vp-woo-pont' ),
				),
				'id' => 'posta_size'
			),
			array(
				'type' => 'text',
				'title' => __( 'Default package weight(g)', 'vp-woo-pont' ),
				'default' => '1000',
				'desc_tip' => __('The weight is a required parameter. If it is missing, this value will be used instead. Enter a value in gramms.', 'vp-woo-pont'),
				'id' => 'posta_default_weight'
			),
			array(
				'type' => 'multiselect',
				'title' => __( 'Enabled services', 'vp-woo-pont' ),
				'class' => 'wc-enhanced-select',
				'default' => array(),
				'options' => array(
					'K_TOR' => __('Fragile handling', 'vp-woo-pont'),
					'K_MSZ' => __('Saturday delivery', 'vp-woo-pont'),
					'K_IDO' => __('One business day time guarantee', 'vp-woo-pont'),
					'K_ALA' => __('Delivered to an occasional recipient', 'vp-woo-pont'),
				),
				'id' => 'posta_extra_services'
			),
			array(
				'type' => 'text',
				'title' => __('Value insurance limit', 'vp-woo-pont'),
				'default' => 50000,
				'desc_tip' => __('If empty, it will be the order total up to 50.000 HUF. If you need a higher maximum limit, enter that here.', 'vp-woo-pont'),
				'id' => 'posta_insurance_limit'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'COD payment handling', 'vp-woo-pont' ),
				'default' => 'M',
				'options' => array(
					'UV_AT' => __( 'Wire transfer', 'vp-woo-pont' ),
					'UV_KP' => __( 'Cash', 'vp-woo-pont' ),
				),
				'id' => 'posta_payment_type'
			),
			array(
				'title' => __('Bank account number for payments', 'vp-woo-pont'),
				'type' => 'text',
				'default' => '',
				'id' => 'posta_payment_number'
			),
			array(
				'title' => __('Round cash on delivery amount', 'vp-woo-pont'),
				'type'     => 'checkbox',
				'desc' => __('Round the COD amount to 5.', 'vp-woo-pont'),
				'id' => 'posta_cod_rounding'
			),
			array(
				'title' => __('Service type to use for international deliveries', 'vp-woo-pont'),
				'class' => 'wc-enhanced-select',
				'default' => 'A_121_CSG',
				'type' => 'vp_posta_countries',
				'options' => array(
					'A_121_CSG' => __( 'International postal parcel', 'vp-woo-pont' ),
					'A_122_ECS' => __( 'International priority postal parcel', 'vp-woo-pont' ),
					'A_123_EUP' => __( 'Europe+ parcel', 'vp-woo-pont' ),
					'A_13_EMS' => __( 'International EMS express mail', 'vp-woo-pont' ),
					'A_125_HAR' => __( 'MPL Europe Standard', 'vp-woo-pont' ),
					'A_125_HAI' => __( 'Inverse MPL Europe Standard', 'vp-woo-pont' ),
				),
				'id' => 'posta_service_int'
			),
			'posta_fragile_products' => array(
				'title' => __('Fragile products', 'vp-woo-pont'),
				'type' => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => array(),
				'id' => 'posta_fragile_products',
				'desc_tip' => __('Select a product attribute or shipping class that relates to fragile products, so the generated label will be marked as fragile by default.', 'vp-woo-pont'),
			),
			'posta_oversized_products' => array(
				'title' => __('Oversized products', 'vp-woo-pont'),
				'type' => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => array(),
				'id' => 'posta_oversized_products',
				'desc_tip' => __('Select a product attribute or shipping class that relates to oversized shipments, so the generated label will be marked as oversized by default.', 'vp-woo-pont'),
			),
			array(
				'title' => __('Dispatch from Csomagautomata', 'vp-woo-pont'),
				'type'     => 'checkbox',
				'desc' => __('If checked, the shipment will be marked to be shipped from MPL Csomagautomata.', 'vp-woo-pont'),
				'id' => 'posta_dispatch_from_automata'
			),
			array(
				'type' => 'sectionend'
			)
		);

		//Load product categories on settings page
		if($this->is_settings_page()) {
			$posta_settings['posta_fragile_products']['options'] = VP_Woo_Pont_Helpers::get_product_tags()+VP_Woo_Pont_Helpers::get_shipping_classes();
			$posta_settings['posta_oversized_products']['options'] = VP_Woo_Pont_Helpers::get_product_tags()+VP_Woo_Pont_Helpers::get_shipping_classes();
		}

		return $settings+$posta_settings;
	}

	//Check if we are on the settings page
	public function is_settings_page() {
		return (isset($_GET['section']) && $_GET['section'] === 'vp_carriers');
	}

	public function create_label($data) {

		//Set delivery mode
		$deliveryMode = 'HA'; //Home delivrey

		//Get point info, in that case we need to change the delivery mode too
		$point = false;
		if($data['point_id']) {
			$provider_id = $data['order']->get_meta('_vp_woo_pont_provider');
			$point = VP_Woo_Pont()->find_point_info($provider_id, $data['point_id']);
			$point_type = $point['group'];
			$deliveryModes = array(
				10 => 'PM',
				20 => 'PP',
				30 => 'CS',
				50 => 'PP',
				70 => 'PP'
			);
			$deliveryMode = $deliveryModes[$point_type];
		}

		//Get package weight in gramms
		if(!$data['package']['weight_gramm']) {
			$data['package']['weight_gramm'] = (int)VP_Woo_Pont_Helpers::get_option('posta_default_weight', 1000);
		}

		//If manually generated, use submitted weight instead
		if(isset($data['options']) && isset($data['options']['package_weight']) && $data['options']['package_weight'] > 0) {
			$data['package']['weight_gramm'] = $data['options']['package_weight'];
		}

		//Set insurance value in HUF
		if($data['package']['currency'] != 'HUF') {
			$data['package']['total'] = round(VP_Woo_Pont_Helpers::convert_currency($data['package']['currency'], 'HUF', $data['package']['total']));
		}

		//Create packet data
		$parcel = array(
			'sender' => array(
				'agreement' => VP_Woo_Pont_Helpers::get_option('posta_agreement_code'),
				'accountNo' => VP_Woo_Pont_Helpers::get_option('posta_payment_number'),
				'contact' => array(
					'name' => VP_Woo_Pont_Helpers::get_option('posta_sender_name'),
					'email' => VP_Woo_Pont_Helpers::get_option('posta_sender_email'),
					'phone' => VP_Woo_Pont_Helpers::get_option('posta_sender_phone')
				),
				'address' => array(
					'postCode' => VP_Woo_Pont_Helpers::get_option('posta_sender_postcode'),
					'city' => VP_Woo_Pont_Helpers::get_option('posta_sender_city'),
					'address' => VP_Woo_Pont_Helpers::get_option('posta_sender_address'),
					'remark' => ''
				)
			),
			'orderId' => strval($data['order_id']),
			'webshopId' => $data['reference_number'],
			'developer' => 'vp-woo-pont',
			'labelType' => VP_Woo_Pont_Helpers::get_option('posta_sticker_size', 'A5'),
			'labelFormat' => VP_Woo_Pont_Helpers::get_option('posta_sticker_format', 'PDF'),
			'item' => array(
				array(
					'customData1' => $data['reference_number'],
					'customData2' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'posta', 40),
					'weight' => array(
						'value' => $data['package']['weight_gramm'],
						'unit' => 'g'
					),
					'services' => array(
						'basic' => 'A_175_UZL',
						'extra' => array(),
						'deliveryMode' => $deliveryMode,
						'value' => ($data['package']['total']) ? round($data['package']['total']) : 1
					)
				)
			),
			'recipient' => array(
				'contact' => array(
					'name' => $data['customer']['name'],
					'email' => $data['customer']['email'],
					'phone' => $data['customer']['phone']
				)
			),
			'paymentMode' => VP_Woo_Pont_Helpers::get_option('posta_payment_type', 'UV_AT'),
			'packageRetention' => VP_Woo_Pont_Helpers::get_option('posta_retention', '5')
		);

		//If its a pont shipping method
		if($data['point_id']) {
			$parcel['recipient']['address'] = array(
				'postCode' => $point['zip'],
				'city' => $point['city'],
				'address' => $point['addr'],
				'remark' => '',
				'parcelPickupSite' => $point['id']
			);

			$parcel['item'][0]['size'] = VP_Woo_Pont_Helpers::get_option('posta_size', 'M');
		}

		//If its home delivery
		$order = $data['order'];
		if(!$data['point_id']) {
			$parcel['recipient']['address'] = array(
				'postCode' => $order->get_shipping_postcode(),
				'city' => $order->get_shipping_city(),
				'address' => $order->get_shipping_address_1(),
				'remark' => $order->get_shipping_address_2(),
				'countryCode' => $order->get_shipping_country()
			);

			//If International
			if($order->get_shipping_country() != 'HU') {

				//Get service type based on country
				$parcel['item'][0]['services']['basic'] = $this->get_service_type($order->get_shipping_country());

			}
		}

		//Check for COD
		if($data['package']['cod']) {
			$parcel['item'][0]['services']['cod'] = $data['package']['total'];
			$parcel['item'][0]['services']['extra'][] = 'K_UVT';

			//If we need to round to cod amount
			if(VP_Woo_Pont_Helpers::get_option('posta_cod_rounding', 'no') == 'yes') {
				$parcel['item'][0]['services']['cod'] = round($data['package']['total']/5, 0) * 5;
			}
		}

		//Check for extra services
		$enabled_services = VP_Woo_Pont_Helpers::get_option('posta_extra_services', array());

		//If manually generated, use submitted services instead
		if($data['source'] == 'metabox') {
			$enabled_services = array();
			if(isset($data['options']) && isset($data['options']['extra_services'])) {
				$enabled_services = $data['options']['extra_services'];
			}
		} else {

			//Append auto fragile handling
			if($this->is_extra_service_needed($order, 'fragile')) {
				$enabled_services[] = 'K_TOR';
			}

			if($this->is_extra_service_needed($order, 'oversized')) {
				$enabled_services[] = 'K_TER';
			}

		}
		
		//Include services
		foreach ($enabled_services as $service) {
			$parcel['item'][0]['services']['extra'][] = $service;
		}

		//Set maximum insurance value
		if($insurance_limit = VP_Woo_Pont_Helpers::get_option('posta_insurance_limit', 50000)) {
			if($parcel['item'][0]['services']['value'] > $insurance_limit) {
				$parcel['item'][0]['services']['value'] = $insurance_limit;
			}
		}

		//Use different code for international shipments
		if($order->get_shipping_country() != 'HU' && VP_Woo_Pont_Helpers::get_option('posta_agreement_code_int', '') != '') {
			$parcel['sender']['agreement'] = VP_Woo_Pont_Helpers::get_option('posta_agreement_code_int');
		}

		//If package count set
		if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
			$parcel['groupTogether'] = true;
			$packages =  $data['options']['package_count'];
			$extra_packages = $packages-1;

			//Divide insurance and weight values
			$total_insurance = $parcel['item'][0]['services']['value'];
			$single_insurance = round($total_insurance/$packages);
			$single_weight = round($data['package']['weight_gramm']/$packages);

			//Set first packages weight and insurance value
			$parcel['item'][0]['weight']['value'] = $single_weight;
			$parcel['item'][0]['services']['value'] = $single_insurance;

			//Create extra items
			for ($i = 0; $i < $extra_packages; $i++){
				$new_item = $parcel['item'][0];
				if($data['package']['cod']) {
					$new_item['services']['cod'] = 0; //Reset COD, we only collect for the first package
					$cod_index = array_search('K_UVT', $new_item['services']['extra']);
					unset($new_item['services']['extra'][$cod_index]); //Reset COD extra service 
					$new_item['services']['extra'] = array_values($new_item['services']['extra']); //Reindex array
				}
				$parcel['item'][] = $new_item;
			}

		}

		//Check dispatch mode, if its automata and below 20kg, we can mark it as automata dispatch
		if(VP_Woo_Pont_Helpers::get_option('posta_dispatch_from_automata', 'no') == 'yes' && $data['package']['weight_gramm'] < 20000) {
			$parcel['sender']['parcelTerminal'] = true;

			//Set default size
			$parcel['item'][0]['size'] = VP_Woo_Pont_Helpers::get_option('posta_size', 'M');
			
			//Overwrite if we have a package size set
			if(isset($data['package']['size']) && isset($data['package']['size']['width']) && isset($data['package']['size']['height']) && isset($data['package']['size']['length'])) {

				//Calculate size
				$box_type = $this->get_box_size($data['package']['size']['width'], $data['package']['size']['height'], $data['package']['size']['length']);

				//Set size, of not, its not suitable to dispatch via automata
				if($box_type) {
					$parcel['item'][0]['size'] = $box_type;
				} else {
					$parcel['sender']['parcelTerminal'] = false;
				}

			}

		}

		//Use A6 size for A6inA4, because we will merge it into an A4 page
		if($parcel['labelType'] == 'A6inA4') {
			$parcel['labelType'] = 'A6';
		}

		//Get auth token
		$token = $this->get_access_token();

		//If no auth token, wrong api keys or sometihng like that
		if(is_wp_error($token)) {
			return $token;
		}

		//Logging
		VP_Woo_Pont()->log_debug_messages($parcel, 'posta-create-label');

		//Create request data
		$options = array($parcel);

		//So developers can modify
		$options = apply_filters('vp_woo_pont_posta_label', $options, $data);
		$auth_header = apply_filters('vp_woo_pont_posta_auth_header', 'Bearer ' . $token, $options);

		//Submit request
		$request_params = array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-Request-Id' => wp_generate_uuid4(),
				'X-Accounting-Code' => VP_Woo_Pont_Helpers::get_option('posta_customer_code', ''),
				'Authorization' => $auth_header
			),
			'timeout' => 60
		);

		$request = wp_remote_post( $this->api_url.'v2/mplapi/shipments', $request_params);

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//If 401 error, possible just the token is expired, so get a new one and try again just in case
		if(wp_remote_retrieve_response_code( $request ) == 401) {
			$token = $this->get_access_token(true);
			$request_params['headers']['Authorization'] = 'Bearer '.$token;
			$request = wp_remote_post( $this->api_url.'v2/mplapi/shipments', $request_params);

			//Check for errors again
			if(is_wp_error($request)) {
				return $request;
			}
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Logging
		VP_Woo_Pont()->log_debug_messages($response, 'posta-create-label-response');

		//Check for API errors
		if(isset($response['fault'])) {
			VP_Woo_Pont()->log_error_messages($response, 'posta-create-label');
			$error = $response['fault'];
			return new WP_Error( 'posta_error_'.$error['detail']['errorcode'], $error['faultstring'] );
		}

		//Check for package errors
		$parcel_data = $response[0];
		if($parcel_data && isset($parcel_data['errors']) && count($parcel_data['errors']) > 0) {
			$error = $parcel_data['errors'][0]['text'];
			return new WP_Error( 'posta_error_'.$parcel_data['errors'][0]['code'], $parcel_data['errors'][0]['text'] );
		}

		//Get PDF label
		$attachment = $parcel_data['label'];
		$pdf = base64_decode($attachment);

		//Get label file info
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('posta', $data['order_id']);

		//Change extensions if needed to zpl
		if(VP_Woo_Pont_Helpers::get_option('posta_sticker_format', 'PDF') == 'ZPL') {
			$pdf_file['name'] = str_replace('.pdf', '.zpl', $pdf_file['name']);
			$pdf_file['path'] = str_replace('.pdf', '.zpl', $pdf_file['path']);
		}

		//Save pdf(or zpl) file
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = $parcel_data['trackingNumber'];
		$label['number'] = $parcel_data['trackingNumber'];
		$label['pdf'] = $pdf_file['name'];
		$label['needs_closing'] = true;

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

		//So developers can modify
		$auth_header = apply_filters('vp_woo_pont_posta_auth_header', 'Bearer ' . $token, $data);

		//Submit request
		$request = wp_remote_request( $this->api_url.'v2/mplapi/shipments/'.$data['parcel_number'], array(
			'method' => 'DELETE',
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-Request-Id' => wp_generate_uuid4(),
				'X-Accounting-Code' => VP_Woo_Pont_Helpers::get_option('posta_customer_code', ''),
				'Authorization' => $auth_header
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		VP_Woo_Pont()->log_error_messages($request, 'posta-void-label');

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );
			VP_Woo_Pont()->log_error_messages($response, 'posta-delete-label');

			if(isset($response['fault'])) {
				$error = $response['fault'];
				return new WP_Error( 'posta_error_'.$error['detail']['errorcode'], $error['faultstring'] );
			} else {
				return new WP_Error( 'posta_error_unknown', __('Unknown error', 'vp-woo-pont') );
			}
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'posta-void-label-response');

		return $label;
	}

	public function close_shipments($packages = array(), $orders = array()) {

		//Get auth token
		$token = $this->get_access_token();

		//If no auth token, wrong api keys or sometihng like that
		if(is_wp_error($token)) {
			return $token;
		}

		//So developers can modify
		$auth_header = apply_filters('vp_woo_pont_posta_auth_header', 'Bearer ' . $token);

		//Set package numbers if needed
		$options = [];
		if(!empty($packages)) {
			$options['trackingNumbers'] = $packages;
		}

		//Close shipments
		$request = wp_remote_post( $this->api_url.'v2/mplapi/shipments/close', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-Request-Id' => wp_generate_uuid4(),
				'X-Accounting-Code' => VP_Woo_Pont_Helpers::get_option('posta_customer_code', ''),
				'Authorization' => $auth_header
			),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			VP_Woo_Pont()->log_error_messages($request, 'posta-close-shipments');
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );
			VP_Woo_Pont()->log_error_messages($response, 'posta-close-shipments');

			if(isset($response['fault'])) {
				$error = $response['fault'];
				return new WP_Error( 'posta_error_'.$error['detail']['errorcode'], $error['faultstring'] );
			} else {
				return new WP_Error( 'posta_error_unknown', __('Unknown error', 'vp-woo-pont') );
			}
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$response = $response[0];

		//Check for more errors
		if(!isset($response['manifest']) || !isset($response['trackingNrPrices'])) {
			VP_Woo_Pont()->log_error_messages($response, 'posta-close-shipments');
			return new WP_Error( 'posta_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		if((isset($response['manifest']) && empty($response['manifest'])) || (isset($response['trackingNrPrices']) && empty($response['trackingNrPrices']))) {
			VP_Woo_Pont()->log_error_messages($response, 'posta-close-shipments');
			return new WP_Error( 'posta_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Save manifest PDF
		$attachment = $response['manifest'];
		$pdf = base64_decode($attachment);

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('mpl-manifest', 0);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Mark the closing on the orders
		$order_ids = array();
		$orders = array();
		$shipments = array();
		foreach ($response['trackingNrPrices'] as $package) {
			$shipments[] = array(
				'tracking' => $package['trackingNumber'],
				'cost' => $package['price']
			);

			$order = wc_get_orders( array(
				'limit'        => 1,
				'orderby'      => 'date',
				'order'        => 'DESC',
				'meta_key'     => '_vp_woo_pont_parcel_number',
				'meta_value' 	 => substr($package['trackingNumber'], 0, 13),
			));

			if(!empty($order)) {
				$order = $order[0];
				$orders[] = $order;
				$order_ids[] = $order->get_id();
			}
		}

		//Return response in unified format
		return array(
			'shipments' => $shipments,
			'orders' => $order_ids,
			'pdf' => array(
				'mpl' => $pdf_file['name']
			)
		);
	}

	public function get_access_token($refresh = false) {
		$access_token = get_transient( '_vp_woo_pont_posta_access_token' );
		if(!$access_token || $refresh) {
			$access_token = false; //returns nothing on error
			$key = base64_encode( $this->api_key.':'.$this->api_password );
			$request = wp_remote_post($this->api_url.'oauth2/token', array(
				'headers' => array(
					'Authorization' => 'Basic ' . $key,
					'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
				),
				'body' => 'grant_type=client_credentials',
				'httpversion' => '1.1'
			));

			if(is_wp_error($request)) {
				VP_Woo_Pont()->log_error_messages($request, 'posta-auth');
				return $request;
			} else {
				$response = json_decode( wp_remote_retrieve_body( $request ) );
				$access_token = $response->access_token;
				set_transient( '_vp_woo_pont_posta_access_token', $access_token, $response->expires_in );
			}
		}

		return $access_token;
	}

	//Return tracking link
	public function get_tracking_link($parcel_number, $order = false) {
		return 'https://www.posta.hu/nyomkovetes/nyitooldal?searchvalue='.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_id = $order->get_meta('_vp_woo_pont_parcel_id');

		//Get auth token
		$token = $this->get_access_token();

		//If no auth token, wrong api keys or sometihng like that
		if(is_wp_error($token)) {
			return $token;
		}

		//So developers can modify
		$auth_header = apply_filters('vp_woo_pont_posta_auth_header', 'Bearer ' . $token, $order);

		//Create parameters
		$options = array(
			'language' => 'hu',
			'ids' => $parcel_id,
			'state' => 'all'
		);

		//Setup request
		$request_params = array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-Request-Id' => wp_generate_uuid4(),
				'X-Accounting-Code' => VP_Woo_Pont_Helpers::get_option('posta_customer_code', ''),
				'Authorization' => $auth_header
			),
		);

		//Run request
		$request = wp_remote_post( $this->api_url.'v2/nyomkovetes/registered', $request_params);

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			VP_Woo_Pont()->log_error_messages($request, 'posta-tracking-info-update');
			return new WP_Error( 'posta_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		$events = array();
		foreach ($response['trackAndTrace'] as $event) {
			$date = strtotime($event['c11'].' '.$event['c12']);
			$label = $event['c9'];
			$location = '';
			if(isset($event['c13'])) {
				$location = $event['c13'];
				$location = str_replace('|', ', ', $location);
			}
			$events[$date] = array(
				'date' => $date,
				'label' => $label,
				'comment' => $label,
				'location' => $location
			);
		}

		//Sort by keys
		krsort($events);

		//Create new array for status info
		$tracking_info = array();
		foreach ($events as $event) {
			$event_id = array_search($event['label'], $this->package_statuses);
			if($event_id) {
				$tracking_info[] = array(
					'date' => $event['date'],
					'event' => array_search($event['label'], $this->package_statuses),
					'label' => $event['label'],
					'location' => $event['location']
				);
			}
		}

		return $tracking_info;
	}

	public function get_service_type($shipping_country) {
		$default = VP_Woo_Pont_Helpers::get_option('posta_service_int', 'A_121_CSG');
		$service_types = get_option('vp_woo_pont_posta_countries');
		if(!$service_types || empty($service_types)) {
			return $default;
		}

		$service_type = $default;
		foreach ($service_types as $service_id => $countries) {
			if(in_array('default', $countries)) {
				$service_type = $service_id;
			}
		}

		foreach ($service_types as $service_id => $countries) {
			if(in_array($shipping_country, $countries)) {
				$service_type = $service_id;
			}
		}

		return $service_type;
	}

	public function is_extra_service_needed($order, $service_type) {
		$fragile_product_tags = VP_Woo_Pont_Helpers::get_option('posta_'.$service_type.'_products', array());
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

	public function get_box_size($width, $height, $length) {
		$box_sizes = array(
			array(
				'width' => 25,
				'height' => 7,
				'length' => 31,
				'type' => 'S'
			),
			array(
				'width' => 31,
				'height' => 16,
				'length' => 50,
				'type' => 'M'
			),
			array(
				'width' => 31,
				'height' => 35,
				'length' => 50,
				'type' => 'L'
			),
		);

		$product_dimensions = array($width, $height, $length);
		sort($product_dimensions);
	
		foreach ($box_sizes as $box) {
			$box_dimensions = array($box['width'], $box['height'], $box['length']);
			sort($box_dimensions);
	
			if (
				$product_dimensions[0] <= $box_dimensions[0] &&
				$product_dimensions[1] <= $box_dimensions[1] &&
				$product_dimensions[2] <= $box_dimensions[2]
			) {
				return $box['type'];
			}
		}
	
		return null;
	}

}
