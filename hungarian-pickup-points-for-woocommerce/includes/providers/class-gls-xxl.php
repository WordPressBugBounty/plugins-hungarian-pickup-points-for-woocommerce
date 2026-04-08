<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_GLS_XXL {
	protected $api_url = 'https://api.mygls.hu/ParcelService.svc/json/';
	protected $api_username = '';
	protected $api_password = '';
	protected $api_client_number = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();
	public $extra_services = array();
	public $supported_countries = array();

	public function __construct() {
		$this->api_username = VP_Woo_Pont_Helpers::get_option('gls_xxl_username');
		$this->api_password = htmlspecialchars_decode(VP_Woo_Pont_Helpers::get_option('gls_xxl_password'));
		$this->api_client_number = VP_Woo_Pont_Helpers::get_option('gls_xxl_client_id');

		if(VP_Woo_Pont_Helpers::get_option('gls_xxl_dev_mode', 'no') == 'yes') {
			$this->api_url = 'https://api.test.mygls.hu/ParcelService.svc/json/';
		}

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_gls_xxl', array($this, 'get_settings'));

		//Set supported statuses
		$this->package_statuses = array(
			"1" => __("The parcel was handed over to GLS.", 'vp-woo-pont'),
			"2" => __("The parcel has left the parcel center.", 'vp-woo-pont'),
			"3" => __("The parcel has reached the parcel center(depo).", 'vp-woo-pont'),
			"4" => __("The parcel is expected to be delivered during the day.", 'vp-woo-pont'),
			"5" => __("The parcel has been delivered.", 'vp-woo-pont'),
			"6" => __("The parcel is stored in the parcel center.", 'vp-woo-pont'),
			"7" => __("The parcel is stored in the parcel center.", 'vp-woo-pont'),
			"8" => __("The parcel is stored in the GLS parcel center. The consignee has agreed to collect the goods himself.", 'vp-woo-pont'),
			"9" => __("The parcel is stored in the parcel center to be delivered at a new delivery date.", 'vp-woo-pont'),
			"11" => __("The parcel could not be delivered as the consignee is on holidays.", 'vp-woo-pont'),
			"12" => __("The parcel could not be delivered as the consignee was absent.", 'vp-woo-pont'),
			"13" => __("Sorting error at the depot.", 'vp-woo-pont'),
			"14" => __("The parcel could not be delivered as the reception was closed.", 'vp-woo-pont'),
			"15" => __("Not delivered lack of time", 'vp-woo-pont'),
			"16" => __("The parcel could not be delivered as the consignee had no cash available/suitable.", 'vp-woo-pont'),
			"17" => __("The parcel could not be delivered as the recipient refused acceptance.", 'vp-woo-pont'),
			"18" => __("The parcel could not be delivered as further address information is needed.", 'vp-woo-pont'),
			"19" => __("The parcel could not be delivered due to the weather condition.", 'vp-woo-pont'),
			"20" => __("The parcel could not be delivered due to wrong or incomplete address.", 'vp-woo-pont'),
			"21" => __("Forwarded sorting error", 'vp-woo-pont'),
			"22" => __("Parcel is sent from the depot to sorting center.", 'vp-woo-pont'),
			"23" => __("The parcel has been returned to sender.", 'vp-woo-pont'),
			"24" => __("The changed delivery option has been saved in the GLS system and will be implemented as requested.", 'vp-woo-pont'),
			"25" => __("Forwarded misrouted", 'vp-woo-pont'),
			"26" => __("The parcel has reached the parcel center(depo).", 'vp-woo-pont'),
			"27" => __("The parcel has reached the parcel center(depo).", 'vp-woo-pont'),
			"28" => __("Disposed", 'vp-woo-pont'),
			"29" => __("Parcel is under investigation.", 'vp-woo-pont'),
			"30" => __("Inbound damaged", 'vp-woo-pont'),
			"31" => __("Parcel was completely damaged.", 'vp-woo-pont'),
			"32" => __("The parcel will be delivered in the evening.", 'vp-woo-pont'),
			"33" => __("The parcel could not be delivered due to exceeded time frame.", 'vp-woo-pont'),
			"34" => __("The parcel could not be delivered as acceptance has been refused due to delayed delivery.", 'vp-woo-pont'),
			"35" => __("Parcel was refused because the goods was not ordered.", 'vp-woo-pont'),
			"36" => __("Consignee was not in, contact card couldn't be left.", 'vp-woo-pont'),
			"37" => __("Change delivery for shipper's request.", 'vp-woo-pont'),
			"38" => __("The parcel could not be delivered due to missing delivery note.", 'vp-woo-pont'),
			"39" => __("Delivery note not signed", 'vp-woo-pont'),
			"40" => __("The parcel has been returned to sender.", 'vp-woo-pont'),
			"41" => __("Forwarded normal", 'vp-woo-pont'),
			"42" => __("The parcel was disposed upon shipper's request.", 'vp-woo-pont'),
			"43" => __("Parcel is not to locate.", 'vp-woo-pont'),
			"44" => __("Parcel is excluded from General Terms and Conditions.", 'vp-woo-pont'),
			"46" => __("Change completed for Delivery address", 'vp-woo-pont'),
			"47" => __("The parcel has left the parcel center.", 'vp-woo-pont'),
			"51" => __("The parcel data was entered into the GLS IT system; the parcel was not yet handed over to GLS.", 'vp-woo-pont'),
			"52" => __("The COD data was entered into the GLS IT system.", 'vp-woo-pont'),
			"54" => __("The parcel has been delivered to the parcel box.", 'vp-woo-pont'),
			"55" => __("The parcel has been delivered at the ParcelShop (see ParcelShop information).", 'vp-woo-pont'),
			"56" => __("Parcel is stored in GLS ParcelShop.", 'vp-woo-pont'),
			"57" => __("The parcel has reached the maximum storage time in the ParcelShop.", 'vp-woo-pont'),
			"58" => __("The parcel has been delivered at the neighbour’s (see signature)", 'vp-woo-pont'),
			"60" => __("Customs clearance is delayed due to a missing invoice.", 'vp-woo-pont'),
			"61" => __("The customs documents are being prepared.", 'vp-woo-pont'),
			"62" => __("Customs clearance is delayed as the consignee's phone number is not available.", 'vp-woo-pont'),
			"64" => __("The parcel was released by customs.", 'vp-woo-pont'),
			"65" => __("The parcel was released by customs. Customs clearance is carried out by the consignee.", 'vp-woo-pont'),
			"66" => __("Customs clearance is delayed until the consignee's approval is available.", 'vp-woo-pont'),
			"67" => __("The customs documents are being prepared.", 'vp-woo-pont'),
			"68" => __("The parcel could not be delivered as the consignee refused to pay charges.", 'vp-woo-pont'),
			"69" => __("The parcel is stored in the parcel center. It cannot be delivered as the consignment is not complete.", 'vp-woo-pont'),
			"70" => __("Customs clearance is delayed due to incomplete documents.", 'vp-woo-pont'),
			"71" => __("Customs clearance is delayed due to missing or inaccurate customs documents.", 'vp-woo-pont'),
			"72" => __("Customs data must be recorded.", 'vp-woo-pont'),
			"73" => __("Customs parcel locked in origin country.", 'vp-woo-pont'),
			"74" => __("Customs clearance is delayed due to a customs inspection.", 'vp-woo-pont'),
			"75" => __("Parcel was confiscated by the Customs authorities.", 'vp-woo-pont'),
			"76" => __("Customs data recorded, parcel can be sent do final location.", 'vp-woo-pont'),
			"80" => __("The parcel has been forwarded to the desired address to be delivered there.", 'vp-woo-pont'),
			"83" => __("The parcel data for Pickup-Service was entered into the GLS system.", 'vp-woo-pont'),
			"84" => __("The parcel label for the pickup has been produced.", 'vp-woo-pont'),
			"85" => __("The driver has received the order to pick up the parcel during the day.", 'vp-woo-pont'),
			"86" => __("The parcel has reached the parcel center(pickup).", 'vp-woo-pont'),
			"87" => __("The pickup request has been cancelled as there were no goods to be picked up.", 'vp-woo-pont'),
			"88" => __("The parcel could not be picked up as the goods to be picked up were not packed.", 'vp-woo-pont'),
			"89" => __("The parcel could not be picked up as the customer was not informed about the pickup.", 'vp-woo-pont'),
			"90" => __("The pickup request has been cancelled as the goods were sent by other means.", 'vp-woo-pont'),
			"91" => __("Pick and Ship/Return cancelled", 'vp-woo-pont'),
			"92" => __("The parcel has been delivered.", 'vp-woo-pont'),
			"93" => __("Signature confirmed", 'vp-woo-pont'),
			"99" => __("Consignee contacted Email delivery notification", 'vp-woo-pont')
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array('1', '2', '3', '6', '7', '10', '22', '24', '26', '27', '37', '41', '46', '47', '86', '99'),
			'delivery' => array('4', '9', '32', '54', '55', '56'),
			'delivered' => array('5', '8', '40', '58', '92', '93'),
			'errors' => array('11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '23', '25', '28', '29', '30', '31', '33', '34', '35', '36', '38', '39', "42", '43', '44', '57', '68', '69', '75', '76', '87', '88', '89', '90', '91')
		);

		$this->extra_services = array(
			'CS1' => __('Contact Service (CS1)', 'vp-woo-pont'),
			'FDS' => __('FlexDelivery Service (FDS)', 'vp-woo-pont'),
			'FSS' => __('FlexDelivery SMS Service (FSS)', 'vp-woo-pont'),
			'SM2' => __('Preadvice Service (SM2)', 'vp-woo-pont'),
			'SM1' => __('SMS Service (SM1)', 'vp-woo-pont'),
			'24H' => __('Guaranteed delivery in 24 Hours', 'vp-woo-pont'),
			'SRS' => __('Shop Return Service', 'vp-woo-pont'),
			'XS' => __('Exchange Service', 'vp-woo-pont'),
			'INS' => __('Declared Value Insurance Service (INS)', 'vp-woo-pont')
		);

		//Small fix for tracking automation, since the return shipment has the same delivered status as the normal one
		add_filter('vp_woo_pont_tracking_automation_target_status', array($this, 'tracking_automation_target_status'), 10, 6);

		//Support multi-parcel shipments
		add_action('vp_woo_pont_metabox_after_generate_options', array( $this, 'add_additional_package_fields'));

		//Fix for _
		add_filter('vp_woo_pont_get_carrier_from_order', function($provider, $order){
			if($provider == 'gls' && VP_Woo_Pont_Helpers::get_provider_from_order($order) == 'gls_xxl') {
				return 'gls_xxl';
			}
			return $provider;
		}, 10, 2);

	}
	public function get_settings($settings) {
		$gls_settings = array(
			array(
				'title' => __( 'GLS XXL settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('GLS Username', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __("Enter your GLS account's e-mail address.", 'vp-woo-pont'),
				'id' => 'gls_xxl_username'
			),
			array(
				'title' => __('GLS Password', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __("Enter your GLS account's password.", 'vp-woo-pont'),
				'id' => 'gls_xxl_password'
			),
			array(
				'title' => __('GLS Customer Number', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __("Enter your GLS customer number.", 'vp-woo-pont'),
				'id' => 'gls_xxl_client_id'
			),
			array(
				'title'    => __( 'Enable sandbox mode', 'vp-woo-pont' ),
				'type'     => 'checkbox',
				'id' => 'gls_dev_mode',
			),
			array(
				'title' => __('Sender name', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_sender_name'
			),
			array(
				'title' => __('Sender address(street)', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_sender_street'
			),
			array(
				'title' => __('Sender address info(building, stairway, etc...)', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_sender_address_2'
			),
			array(
				'title' => __('Sender address(just the number)', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_sender_address'
			),
			array(
				'title' => __('Sender city', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_sender_city'
			),
			array(
				'title' => __('Sender postcode', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_sender_postcode'
			),
			array(
				'title' => __('Sender phone number', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_sender_phone'
			),
			array(
				'title' => __('Sender email address', 'vp-woo-pont'),
				'type' => 'text',
				'default' => get_bloginfo('admin_email'),
				'id' => 'gls_sender_email'
			),
			array(
				'title' => __('Contact name', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'gls_contact_name'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A6',
				'options' => array(
					'A4_2x2' => __( 'A4_2x2 (Landscape)', 'vp-woo-pont' ),
					'A6' => __( 'A6 on A4(recommended)', 'vp-woo-pont' ),
					'A4_4x1' => __( 'A4_4x1', 'vp-woo-pont' ),
					'Connect' => __( 'Connect', 'vp-woo-pont' ),
					'Thermo' => __( 'Thermo', 'vp-woo-pont' ),
				),
				'id' => 'gls_sticker_size'
			),
			array(
				'type' => 'multiselect',
				'title' => __( 'Enabled services for home delivery', 'vp-woo-pont' ),
				'class' => 'wc-enhanced-select',
				'default' => array(),
				'options' => $this->extra_services,
				'id' => 'gls_extra_services'
			),
			array(
				'title' => __('SMS text for the SMS Service (SM1)', 'vp-woo-pont'),
				'type' => 'textarea',
				'desc' => __('You can use the following shortcodes: #ParcelNr#, #COD#, #PickupDate#, #From_Name#, #ClientRef#.', 'vp-woo-pont'),
				'id' => 'gls_sm1_text'
			),
			array(
				'title' => __('Round cash on delivery amount', 'vp-woo-pont'),
				'type'     => 'checkbox',
				'desc' => __('Round the COD amount to 5.', 'vp-woo-pont'),
				'id' => 'gls_cod_rounding'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Default package type', 'vp-woo-pont' ),
				'default' => '1',
				'options' => array(
					'1' => 'Colli',
					'2' => 'Box',
					'3' => 'Roll',
					'4' => 'Can',
					'5' => 'Case',
					'6' => 'Reel',
					'7' => 'Sack',
				),
				'id' => 'gls_xxl_default_package_type'
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$gls_settings;
	}

	public function get_auth_data($order) {
		return apply_filters('vp_woo_pont_gls_auth_data', array(
			'Username' => $this->api_username,
			'Password' => array_values(unpack('C*', hash('sha512', $this->api_password, true))),
			'ClientNumber' => intval($this->api_client_number)
		), $order);
	}

	public function create_label($data) {

		//Get auth data
		$auth = $this->get_auth_data($data['order']);

		//Create packet data
		$parcel = array(
			'ClientNumber' => $auth['ClientNumber'],
			'ClientReference' => $data['reference_number'],
			'Content' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'gls'),
			'PickupAddress' => array(
				'Name' => VP_Woo_Pont_Helpers::get_option('gls_sender_name'),
				'Street' => VP_Woo_Pont_Helpers::get_option('gls_sender_street'),
				'HouseNumber' => VP_Woo_Pont_Helpers::get_option('gls_sender_address'),
				'HouseNumberInfo' => VP_Woo_Pont_Helpers::get_option('gls_sender_address_2'),
				'City' => VP_Woo_Pont_Helpers::get_option('gls_sender_city'),
				'ZipCode' => VP_Woo_Pont_Helpers::get_option('gls_sender_postcode'),
				'CountryIsoCode' => VP_Woo_Pont_Helpers::get_option('gls_sender_country', 'HU'),
				'ContactName' => VP_Woo_Pont_Helpers::get_option('gls_contact_name', '-'),
				'ContactPhone' => VP_Woo_Pont_Helpers::get_option('gls_sender_phone'),
				'ContactEmail' => VP_Woo_Pont_Helpers::get_option('gls_sender_email')
			),
			'ServiceList' => array(),
			'DeliveryAddress' => array(
				'ContactName' => $data['customer']['name'],
				'ContactPhone' => $data['customer']['phone'],
				'ContactEmail' => $data['customer']['email']
			),
			'ParcelPropertyList' => array()
		);

		//Define shipping address too
		$order = wc_get_order($data['order_id']);
		$parcel['DeliveryAddress']['Name'] = $data['customer']['name_with_company'];
		$parcel['DeliveryAddress']['Street'] = $order->get_shipping_address_1();
		$parcel['DeliveryAddress']['HouseNumberInfo'] = $order->get_shipping_address_2();
		$parcel['DeliveryAddress']['City'] = $order->get_shipping_city();
		$parcel['DeliveryAddress']['ZipCode'] = $order->get_shipping_postcode();
		$parcel['DeliveryAddress']['CountryIsoCode'] = $order->get_shipping_country();

		//Check for COD
		if($data['package']['cod']) {
			$parcel['CODAmount'] = $data['package']['total'];
			$parcel['CODReference'] = $data['cod_reference_number'];
			$order = wc_get_order($data['order_id']);

			//If we need to round to cod amount
			if(VP_Woo_Pont_Helpers::get_option('gls_cod_rounding', 'no') == 'yes') {
				$currency = $order->get_currency();
				if ($currency == 'HUF') {
					$parcel['CODAmount'] = round($data['package']['total'] / 5, 0) * 5;
				}
			}
		} else {
			$parcel['CODAmount'] = null;
		}

		//If package count set
		if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
			$parcel['Count'] = $data['options']['package_count'];
		}

		//Setup XXL parcel details
		if(isset($_POST['additional_package_data_gls_xxl'])) {

			$additional_package_data = json_decode(stripslashes($_POST['additional_package_data_gls_xxl']), true);
			foreach ($additional_package_data as $parcel_index => $parcel_data) {
				$weight = $parcel_data['weight'];
				$length = $parcel_data['length'];
				$width = $parcel_data['width'];
				$height = $parcel_data['height'];
				$type = $parcel_data['type'];
				$contents = $parcel_data['contents'];
				$item = array(
					'Content' => sanitize_text_field($contents),
					'Height' => intval($height),
					'Length' => intval($length),
					'Width' => intval($width),
					'Weight' => floatval($weight),
					'PackageType' => intval($type)
				);

				$parcel['ParcelPropertyList'][] = $item;
			}
		} else {

			//If not set, use default values
			$item = array(
				'Content' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'gls-xxl', 40),
				'Height' => 1,
				'Length' => 1,
				'Width' => 1,
				'Weight' => floatval($data['package']['weight']),
				'PackageType' => intval(VP_Woo_Pont_Helpers::get_option('gls_xxl_default_package_type', '1'))
			);
			
			//Overwrite if we have a package size set
			if(isset($data['package']['size']) && isset($data['package']['size']['width']) && isset($data['package']['size']['height']) && isset($data['package']['size']['length'])) {
				$item['Height'] = intval($data['package']['size']['height']);
				$item['Length'] = intval($data['package']['size']['length']);
				$item['Width'] = intval($data['package']['size']['width']);
			}

			//Append item to parcel
			$parcel['ParcelPropertyList'][] = $item;
		}

		//Check for extra services
		$enabled_services = VP_Woo_Pont_Helpers::get_option('gls_extra_services', array());

		//If manually generated, use submitted services instead
		if(isset($data['options']) && isset($data['options']['extra_services'])) {
			$enabled_services = $data['options']['extra_services'];
		}

		foreach ($enabled_services as $service_id) {
			$value = false;

			if($service_id == 'CS1') {
				$order = wc_get_order($data['order_id']);
				if($order->get_shipping_country() != 'HU') {
					continue;
				}
			}

			if($service_id == 'CS1') {
				$value = $data['customer']['phone'];
			}

			if($service_id == 'FDS') {
				$value = $data['customer']['email'];
			}

			if($service_id == 'FSS') {
				$value = $data['customer']['phone'];
			}

			if($service_id == 'SM1') {
				$value = $data['customer']['phone'].'|'.VP_Woo_Pont_Helpers::get_option('gls_sm1_text', '');
			}

			if($service_id == 'SM2') {
				$value = $data['customer']['phone'];
			}

			if($service_id == 'INS') {
				$value = $data['package']['total'];
			}

			if($value) {
				$parcel['ServiceList'][] = array(
					'Code' => $service_id,
					$service_id.'Parameter' => array(
						'Value' => $value
					)
				);
			}

			if($service_id == '24H' || $service_id == 'SRS' || $service_id == 'XS') {
				$parcel['ServiceList'][] = array(
					'Code' => $service_id
				);
			}
		}

		//Create request data
		$label_size = VP_Woo_Pont_Helpers::get_option('gls_sticker_size', 'A4_2x2');
		$options = array(
			'Username' => $auth['Username'],
			'Password' => $auth['Password'],
			'ParcelList' => array($parcel),
			'PrintPosition' => 1,
			'ShowPrintDialog' => 0,
			'TypeOfPrinter' => $label_size,
			'WebshopEngine' => 'WooCommerce'
		);

		//If its A6, use the Thermo one, which will give us a portrait label we can put on an A6
		if($label_size == 'A6') {
			$options['TypeOfPrinter'] = 'Thermo';
		}

		//So developers can modify
		$options = apply_filters('vp_woo_pont_gls_label', $options, $data);
		$api_url = $this->api_url.'PrintLabels';
		$error_field = 'PrintLabelsErrorList';

		//Check if we only need to prepare
		if(apply_filters('vp_woo_pont_gls_prepare_only', false)) {
			$api_url = $this->api_url.'PrepareLabels';
			$error_field = 'PrepareLabelsError';
			$options = array(
				'Username' => $this->api_username,
				'Password' => array_values(unpack('C*', hash('sha512', $this->api_password, true))),
				'ParcelList' => array($parcel),
				'WebshopEngine' => 'WooCommerce'
			);
		}

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'gls-create-label');
		
		//Submit request
		$request = wp_remote_post( $api_url, array(
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

		//Check for API errors
		if(count($response[$error_field]) > 0) {
			VP_Woo_Pont()->log_error_messages($response, 'gls-create-label');
			$error = $response[$error_field][0];
			return new WP_Error( 'gls_error_'.$error['ErrorCode'], $error['ErrorDescription'] );
		}
	
		//Create response
		$label = array();

		//Check if it was prepare only
		if(apply_filters('vp_woo_pont_gls_prepare_only', false)) {
			$parcel_data = $response['ParcelInfoList'][0];
			$label['id'] = $parcel_data['ParcelId'];
			$label['number'] = $parcel_data['ParcelId'];
			$label['pdf'] = '';
		} else {

			//Get PDF file
			$pdf = implode(array_map('chr', $response['Labels']));

			//Crop to A6 if needed, but only if it doesn't contain a return label
			if($label_size == 'A6') {
				$pdf = VP_Woo_Pont_Print::crop_to_a6($pdf);
			}

			//Try to save PDF file
			$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('gls', $data['order_id']);
			VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

			//Create response
			$parcel_data = $response['PrintLabelsInfoList'][0];
			$label['id'] = $parcel_data['ParcelId'];
			$label['number'] = $parcel_data['ParcelNumber'];
			$label['pdf'] = $pdf_file['name'];
			$label['needs_closing'] = true;
		}

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {

		//Get auth data
		$auth = $this->get_auth_data($data['order']);

		//Create request data
		$options = array(
			'Username' => $auth['Username'],
			'Password' => $auth['Password'],
			'ParcelIdList' => array($data['parcel_id'])
		);

		//Allow plugins to modify
		$options = apply_filters('vp_woo_pont_gls_void_label', $options, $data);

		//Submit request
		$request = wp_remote_post( $this->api_url.'DeleteLabels', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Check for API errors
		if(count($response['DeleteLabelsErrorList']) > 0) {
			$error = $response['DeleteLabelsErrorList'][0];

			//If the label does not exist in GLS system, we can delete it from WC too
			if($error['ErrorCode'] == 4) {
				return array('success' => true);
			} else {
				VP_Woo_Pont()->log_error_messages($response, 'gls-delete-label');
				return new WP_Error( 'gls_error_'.$error['ErrorCode'], $error['ErrorDescription'] );
			}

		}

		//Check for success
		$label = array();
		$label['success'] = true;

		return $label;
	}

	public function download_labels($data) {

		//Create request data
		$options = array(
			'Username' => $this->api_username,
			'Password' => array_values(unpack('C*', hash('sha512', $this->api_password, true))),
			'ParcelIdList' => $data['parcel_ids'],
			'PrintPosition' => 1,
			'ShowPrintDialog' => 0,
			'TypeOfPrinter' => VP_Woo_Pont_Helpers::get_option('gls_sticker_size', 'A4_2x2')
		);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'gls-download-label');

		//Submit request
		$request = wp_remote_post( $this->api_url.'GetPrintedLabels', array(
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

		//Check for API errors
		if(count($response['GetPrintedLabelsErrorList']) > 0) {
			VP_Woo_Pont()->log_error_messages($response, 'gls-download-label');
			$error = $response['PrintLabelsErrorList'][0];
			return new WP_Error( 'gls_error_'.$error['ErrorCode'], $error['ErrorDescription'] );
		}

		//Get PDF file
		$pdf = implode(array_map('chr', $response['Labels']));

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('gls', 0);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['pdf'] = $pdf_file['path'];

		//Return file name
		return $label;

	}

	//Return tracking link
	public function get_tracking_link($parcel_number, $order = false) {
		$country = VP_Woo_Pont_Helpers::get_option('gls_sender_country', 'HU');
		if($country == 'HU') {
			return 'https://gls-group.eu/HU/hu/csomagkovetes.html?match='.esc_attr($parcel_number);
		} else {
			return 'https://gls-group.eu/'.$country.'/en/parcel-tracking?match='.esc_attr($parcel_number);
		}
	}

	//Replace placeholder in shipping label conents string
	public function get_package_contents_label($data, $provider) {

		//Get order
		$order = $data['order'];
		$note = VP_Woo_Pont_Helpers::get_option('gls_package_contents', '');
		$order_items = $order->get_items();
		$order_items_strings = array();

		//Setup order items
		foreach( $order_items as $order_item ) {
			$order_item_string = $order_item->get_quantity().'x '.$order_item->get_name();
			if($order_item->get_product()) {
				$product = $order_item->get_product();
				if($product->get_sku()) {
					$order_item_string .= ' ('.$product->get_sku().')';
				}
			}
			$order_items_strings[] = $order_item_string;
		}

		//Setup replacements
		$note_replacements = apply_filters('vp_woo_pont_gls_label_placeholders', array(
			'{order_number}' => $order->get_order_number(),
			'{customer_note}' => $order->get_customer_note(),
			'{order_items}' => implode(', ', $order_items_strings)
		), $order, $data);

		//Replace stuff:
		$note = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $note);

		return $note;
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Get auth data
		$auth = $this->get_auth_data($order);

		//Create request data
		$options = array(
			'Username' => $auth['Username'],
			'Password' => $auth['Password'],
			'ParcelNumber' => $parcel_number,
			'ReturnPOD' => 0,
			'LanguageIsoCode' => 'HU'
		);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'gls-get-tracking-info');

		//Allow plugins to modify request
		$options = apply_filters('vp_woo_pont_gls_tracking', $options, $order);

		//Submit request
		$request = wp_remote_post( $this->api_url.'GetParcelStatuses', array(
			'body'    => json_encode($options),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		//Get tracking info
		if($response && isset($response['ParcelStatusList']) && count($response['ParcelStatusList']) > 0) {
			$tracking_info = array();

			//Loop through events
			foreach ($response['ParcelStatusList'] as $event) {

				//Skip if unknown status
				if(!isset($this->package_statuses[strval(intval($event['StatusCode']))])) {
					continue;
				}

				//First, convert the dotnet formatted date to normal datetime and setup the event
				if (preg_match('#^/Date\(([-]?[0-9]+)([+-][0-9]{4})\)/$#', $event['StatusDate'], $matches)) {
					$timestamp = intval($matches[1]) / 1000; // Convert milliseconds to seconds
					$timezone_offset = $matches[2];
	
					// Calculate the offset in seconds
					$offset_hours = intval(substr($timezone_offset, 0, 3));
					$offset_minutes = intval(substr($timezone_offset, 0, 1) . substr($timezone_offset, 3, 2));
					$offset_seconds = ($offset_hours * 3600) + ($offset_minutes * 60);
	
					// Adjust the timestamp with the offset
					$adjusted_timestamp = $timestamp + $offset_seconds;
	
					$datetime = new \DateTime("@$adjusted_timestamp");
					
					$tracking_info[] = array(
						'date' => $datetime->getTimestamp(),
						'event' => strval(intval($event['StatusCode'])),
						//'label' => $event['StatusDescription'],
						'location' => (isset($event['DepotCity'])) ? $event['DepotCity'] : ''
					);
				}

			}

			//And return tracking info to store
			return $tracking_info;

		} else {
			return new WP_Error( 'gls_tracking_info_error', '' );
		}

	}

	public function get_enabled_countries() {
		$enabled_countries = get_option('vp_woo_pont_gls_countries', array('HU'));
		return $enabled_countries;
	}

	public function tracking_automation_target_status($target_status, $order, $provider, $tracking_info, $automation, $event_status) {
		if($provider == 'gls' && (in_array('delivered', $automation[$provider]) && $event_status == 'delivered')) {
			$existing_tracking_info = $order->get_meta('_vp_woo_pont_parcel_info');
			if($existing_tracking_info && $this->has_shipment_returned($existing_tracking_info)) {
				return false;
			}
		}
		return $target_status;
	}

	private function has_shipment_returned($tracking_info) {
		foreach ($tracking_info as $info) {
			if (isset($info['event']) && $info['event'] == '23') {
				return true;
			}
		}
		return false;
	}

	public function close_shipments($packages = array(), $orders = array()) {

		//Create request data
		$options = array(
			'Username' => $this->api_username,
			'Password' => array_values(unpack('C*', hash('sha512', $this->api_password, true))),
			'ParcelIdList' => $packages,
			'ClientNumber' => intval($this->api_client_number),
		);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'gls-download-label');

		//Submit request
		$request = wp_remote_post( $this->api_url.'SetDispatchList', array(
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
			VP_Woo_Pont()->log_error_messages($response, 'gls-download-label');
		$response = json_decode( $response, true );

		//Check for API errors
		if(count($response['DispatchListErrorList']) > 0) {
			VP_Woo_Pont()->log_error_messages($response, 'gls-download-label');
			$error = $response['DispatchListErrorList'][0];
			return new WP_Error( 'gls_error_'.$error['ErrorCode'], $error['ErrorDescription'] );
		}

		//Get PDF file
		$pdf = implode(array_map('chr', $response['DispatchListReportPdf']));

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('gls_xxl', 0);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Return response in unified format
		return array(
			'shipments' => array(),
			'orders' => $orders,
			'pdf' => array(
				'gls_xxl' => $pdf_file['name']
			)
		);
		
	}

	public function add_additional_package_fields($order) {
		$packaging_types = get_option('vp_woo_pont_packagings');
		$default_package_type = VP_Woo_Pont_Helpers::get_option('gls_xxl_default_package_type', '1');
		$package_size = VP_Woo_Pont()->labels->get_package_size($order);
		$weight = round(VP_Woo_Pont_Helpers::get_package_weight_in_gramms($order)/1000);
		$contents = VP_Woo_Pont()->labels->get_package_contents_label(array('order' => $order), 'gls-xxl', 40);
			
		//Default package size
		if(!isset($package_size['width'])) {
			$package_size['width'] = '';
			$package_size['height'] = '';
			$package_size['length'] = '';
		}

		?>
			<li style="display:none" id="vp_woo_pont_gls_xxl_parcels">
				<label>Küldemények adatai</label>
				<ul class="vp-woo-pont-gls-xxl-parcels">
					<li class="vp-woo-pont-gls-xxl-parcel-sample" style="display:none;">
						<div class="vp-woo-pont-gls-xxl-parcel-main-fields">
							<div class="vp-woo-pont-gls-xxl-parcel-unit">
								<input type="text" name="vp_woo_pont_gls_xxl_package_weights[]" placeholder="" value="<?php echo esc_attr($weight); ?>" />
								<span>kg</span>
							</div>
							<div class="vp-woo-pont-gls-xxl-parcel-unit">
								<select name="vp_woo_pont_gls_xxl_package_types[]">
									<option value="1" <?php selected($default_package_type, '1'); ?>>Colli</option>
									<option value="2" <?php selected($default_package_type, '2'); ?>>Box</option>
									<option value="3" <?php selected($default_package_type, '3'); ?>>Roll</option>
									<option value="4" <?php selected($default_package_type, '4'); ?>>Can</option>
									<option value="5" <?php selected($default_package_type, '5'); ?>>Case</option>
									<option value="6" <?php selected($default_package_type, '6'); ?>>Reel</option>
									<option value="7" <?php selected($default_package_type, '7'); ?>>Sack</option>
								</select>
							</div>
						</div>
						<div class="vp-woo-pont-gls-xxl-parcel-unit">
							<input type="text" name="vp_woo_pont_gls_xxl_package_contents[]" placeholder="<?php esc_attr_e('Package contents', 'vp-woo-pont'); ?>" value="<?php echo esc_attr($contents); ?>" />
						</div>
						<?php if($packaging_types) { ?>
							<div class="vp-woo-pont-gls-xxl-parcel-package-size">
								<div class="vp-woo-pont-gls-xxl-parcel-unit">
									<input type="text" name="vp_woo_pont_gls_xxl_length" placeholder="" value="<?php echo esc_attr($package_size['length']); ?>" />
									<span>cm</span>
								</div>
								<div class="vp-woo-pont-gls-xxl-parcel-unit">
									<input type="text" name="vp_woo_pont_gls_xxl_width" placeholder="" value="<?php echo esc_attr($package_size['width']); ?>" />
									<span>cm</span>
								</div>
								<div class="vp-woo-pont-gls-xxl-parcel-unit">
									<input type="text" name="vp_woo_pont_gls_xxl_height" placeholder="" value="<?php echo esc_attr($package_size['height']); ?>" />
									<span>cm</span>
								</div>
								<a href="#" class="vp-woo-pont-gls-xxl-parcel-boxes-btn">
									<span class="dashicons dashicons-archive"></span>
								</a>
							</div>
							<ul class="vp-woo-pont-gls-xxl-parcel-packaging-types" style="display: none;">
								<?php foreach ( $packaging_types as $packaging_id => $packaging_type ): ?>
									<li>
										<input type="radio" name="vp_woo_pont_gls_xxl_packaging_type" data-length="<?php echo esc_attr($packaging_type['length']); ?>" data-width="<?php echo esc_attr($packaging_type['width']); ?>" data-height="<?php echo esc_attr($packaging_type['height']); ?>" id="vp_woo_pont_gls_xxl_packaging_type_<?php echo esc_attr($packaging_type['sku']); ?>" value="<?php echo esc_attr($packaging_type['sku']); ?>">
										<label for="vp_woo_pont_gls_xxl_packaging_type_<?php echo esc_attr($packaging_type['sku']); ?>">
											<?php echo esc_html($packaging_type['name']); ?>
											<small>
												<?php echo esc_html($packaging_type['length']); ?>x<?php echo esc_html($packaging_type['width']); ?>x<?php echo esc_html($packaging_type['height']); ?>cm
											</small>
										</label>
									</li>
								<?php endforeach; ?>
								<li>
									<input type="radio" name="vp_woo_pont_gls_xxl_packaging_type" id="vp_woo_pont_gls_xxl_packaging_type_custom" value="custom">
									<label for="vp_woo_pont_gls_xxl_packaging_type_custom">
										<?php esc_html_e('Custom packaging', 'vp-woo-pont'); ?>
									</label>
								</li>
							</ul>
						<?php } ?>

					</li>
				</ul>
			</li>

			<script>
			jQuery(document).ready(function($){
				function renderGlsXxlParcels(count) {
					var $list = $('.vp-woo-pont-gls-xxl-parcels');
					var $sample = $list.find('.vp-woo-pont-gls-xxl-parcel-sample');
					$list.find('.vp-woo-pont-gls-xxl-parcel:not(.vp-woo-pont-gls-xxl-parcel-sample)').remove();
					for (var i = 0; i < count; i++) {
						var $clone = $sample.clone(true, true).removeClass('vp-woo-pont-gls-xxl-parcel-sample').addClass('vp-woo-pont-gls-xxl-parcel').show();
						// Ensure unique name attributes for packaging_type radios
						$clone.find('input[type=radio][name=vp_woo_pont_gls_xxl_packaging_type]').each(function(){
							$(this).attr('name', 'vp_woo_pont_gls_xxl_packaging_type_' + i);
						});
						// Preselect custom if none selected
						var $radios = $clone.find('input[type=radio][name="vp_woo_pont_gls_xxl_packaging_type_' + i + '"]');
						if ($radios.filter(':checked').length === 0) {
							$radios.filter('[value="custom"]').prop('checked', true).trigger('change');
						}
						$list.append($clone);
					}
				}

				$(document).on('change', '#vp_woo_pont_package_count', function(){
					var package_count = parseInt($(this).val(), 10) || 0;
					var provider = $('.vp-woo-pont-metabox-content').data('provider_id');

					if(provider != 'gls_xxl') {
						return;
					}

					$('#vp_woo_pont_gls_xxl_parcels').show();
					renderGlsXxlParcels(package_count);
				});

				//Render on page load
				if($('.vp-woo-pont-metabox-content').data('provider_id') == 'gls_xxl') {
					$('#vp_woo_pont_gls_xxl_parcels').show();
					$('.vp-woo-pont-package-contents').hide();
					renderGlsXxlParcels(1);
				}

				// Show/hide packaging types list on "Boxes" button click
				$(document).on('click', '.vp-woo-pont-gls-xxl-parcel-boxes-btn', function(e){
					e.preventDefault();
					var $parcel = $(this).closest('.vp-woo-pont-gls-xxl-parcel, .vp-woo-pont-gls-xxl-parcel-sample');
					var $types = $parcel.find('.vp-woo-pont-gls-xxl-parcel-packaging-types');
					if ($types.is(':visible')) {
						$types.hide();
					} else {
						$types.show();
					}
				});

				// When a packaging type is selected, fill in the dimensions, set readonly, and hide the list
				$(document).on('change', '.vp-woo-pont-gls-xxl-parcel-packaging-types input[type=radio]', function(){
					var $li = $(this).closest('.vp-woo-pont-gls-xxl-parcel, .vp-woo-pont-gls-xxl-parcel-sample');
					var $length = $li.find('input[name="vp_woo_pont_gls_xxl_length"]');
					var $width = $li.find('input[name="vp_woo_pont_gls_xxl_width"]');
					var $height = $li.find('input[name="vp_woo_pont_gls_xxl_height"]');
					if($(this).val() !== 'custom') {
						$length.val($(this).data('length')).prop('readonly', true);
						$width.val($(this).data('width')).prop('readonly', true);
						$height.val($(this).data('height')).prop('readonly', true);
					} else {
						$length.val('').prop('readonly', false);
						$width.val('').prop('readonly', false);
						$height.val('').prop('readonly', false);
					}
					$li.find('.vp-woo-pont-gls-xxl-parcel-packaging-types').hide();
				});

				// Make clicking the label also select the radio button
				$(document).on('click', '.vp-woo-pont-gls-xxl-parcel-packaging-types label', function(e){
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
						console.log(package_count);
						if(package_count > 0) {
							var package_data = {};
							$('.vp-woo-pont-gls-xxl-parcel').each(function(index){
								var weight = $(this).find('input[name="vp_woo_pont_gls_xxl_package_weights[]"]').val();
								var length = $(this).find('input[name="vp_woo_pont_gls_xxl_length"]').val();
								var width = $(this).find('input[name="vp_woo_pont_gls_xxl_width"]').val();
								var height = $(this).find('input[name="vp_woo_pont_gls_xxl_height"]').val();
								var type = $(this).find('select[name="vp_woo_pont_gls_xxl_package_types[]"]').val();
								var contents = $(this).find('input[name="vp_woo_pont_gls_xxl_package_contents[]"]').val();

								// Store all package data with explicit index
								package_data[index] = {
									weight: weight || '',
									length: length || '',
									width: width || '',
									height: height || '',
									type: type || '1',
									contents: contents || ''
								};
							});
							
							// Send as JSON for easy server-side processing
							options.data += '&additional_package_data_gls_xxl='+encodeURIComponent(JSON.stringify(package_data));

						}
					}
				});
			});
			</script>
			<?php
	}

}
