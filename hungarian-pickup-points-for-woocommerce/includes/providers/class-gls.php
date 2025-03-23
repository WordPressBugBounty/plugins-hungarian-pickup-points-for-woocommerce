<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_GLS {
	protected $api_url = 'https://api.mygls.hu/ParcelService.svc/json/';
	protected $api_username = '';
	protected $api_password = '';
	protected $api_client_number = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();
	public $extra_services = array();
	public $supported_countries = array();

	public function __construct() {
		$this->api_username = VP_Woo_Pont_Helpers::get_option('gls_username');
		$this->api_password = htmlspecialchars_decode(VP_Woo_Pont_Helpers::get_option('gls_password'));
		$this->api_client_number = VP_Woo_Pont_Helpers::get_option('gls_client_id');

		if(VP_Woo_Pont_Helpers::get_option('gls_dev_mode', 'no') == 'yes') {
			$this->api_url = 'https://api.test.mygls.hu/ParcelService.svc/json/';
		}

		//Set country for api url
		$country = VP_Woo_Pont_Helpers::get_option('gls_sender_country', 'HU');
		$domain = strtolower($country);
		$this->api_url = str_replace('.hu', '.'.$domain, $this->api_url);

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_gls', array($this, 'get_settings'));

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

		$this->supported_countries = array(
			'HU' => __( 'Hungary', 'vp-woo-pont' ),
			'RO' => __( 'Romania', 'vp-woo-pont' ),
			'HR' => __( 'Croatia', 'vp-woo-pont' ),
			'CZ' => __( 'Czechia', 'vp-woo-pont' ),
			'SI' => __( 'Slovenia', 'vp-woo-pont' ),
			'SK' => __( 'Slovakia', 'vp-woo-pont' )
		);

		//Smallf ix for tracking automation, since the return shipment has the same delivered status as the normal one
		add_filter('vp_woo_pont_tracking_automation_target_status', array($this, 'tracking_automation_target_status'), 10, 6);

	}

	public function get_settings($settings) {
		$point_services = array(
			'SM2' => __('Preadvice Service (SM2)', 'vp-woo-pont'),
			'SM1' => __('SMS Service (SM1)', 'vp-woo-pont'),
			'24H' => __('Guaranteed delivery in 24 Hours', 'vp-woo-pont'),
			'SRS' => __('Shop Return Service', 'vp-woo-pont')
		);

		$gls_settings = array(
			array(
				'title' => __( 'GLS settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('GLS Username', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __("Enter your GLS account's e-mail address.", 'vp-woo-pont'),
				'id' => 'gls_username'
			),
			array(
				'title' => __('GLS Password', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __("Enter your GLS account's password.", 'vp-woo-pont'),
				'id' => 'gls_password'
			),
			array(
				'title' => __('GLS Customer Number', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __("Enter your GLS customer number.", 'vp-woo-pont'),
				'id' => 'gls_client_id'
			),
            array(
                'type' => 'vp_checkboxes',
                'title' => __( 'Enabled countries', 'vp-woo-pont' ),
				'options' => $this->supported_countries,
                'default' => array('HU'),
				'desc' => __('Show pickup points in these countries as available options.', 'vp-woo-pont'),
				'id' => 'gls_countries'
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
				'title' => __('Sender country', 'vp-woo-pont'),
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'default' => 'HU',
				'options' => $this->supported_countries,
				'id' => 'gls_sender_country'
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
				'type' => 'multiselect',
				'title' => __( 'Enabled services for point delivery', 'vp-woo-pont' ),
				'class' => 'wc-enhanced-select',
				'default' => array(),
				'options' => $point_services,
				'id' => 'gls_extra_services_points'
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
				'type' => 'sectionend'
			)
		);

		return $settings+$gls_settings;
	}

	public function create_label($data) {

		//Create packet data
		$parcel = array(
			'ClientNumber' => intval($this->api_client_number),
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
			)
		);

		//If its a pont shipping method
		if($data['point_id']) {
			$parcel['ServiceList'][] = array(
				'Code' => 'PSD',
				'PSDParameter' => array(
					'StringValue' => $data['point_id']
				)
			);
		}

		//If package count set
		if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
			$parcel['Count'] = $data['options']['package_count'];
		}

		//If pickup date set
		if(isset($data['options']) && isset($data['options']['pickup_date']) && $data['options']['pickup_date'] != '') {
			$datetime = DateTime::createFromFormat('Y-m-d', $data['options']['pickup_date']);
			$parcel['PickupDate'] = '/Date(' . $datetime->format('UvO') . ')/';
		}

		//If its home delivery, define shipping address too
		if(!$data['point_id']) {
			$order = wc_get_order($data['order_id']);
			$parcel['DeliveryAddress']['Name'] = $data['customer']['name_with_company'];
			$parcel['DeliveryAddress']['Street'] = $order->get_shipping_address_1();
			$parcel['DeliveryAddress']['HouseNumberInfo'] = $order->get_shipping_address_2();
			$parcel['DeliveryAddress']['City'] = $order->get_shipping_city();
			$parcel['DeliveryAddress']['ZipCode'] = $order->get_shipping_postcode();
			$parcel['DeliveryAddress']['CountryIsoCode'] = $order->get_shipping_country();
		} else {
			$order = wc_get_order($data['order_id']);
			$parcel['DeliveryAddress']['Name'] = $data['customer']['name_with_company'];
			$parcel['DeliveryAddress']['Street'] = '';
			$parcel['DeliveryAddress']['HouseNumberInfo'] = '';
			$parcel['DeliveryAddress']['City'] = '';
			$parcel['DeliveryAddress']['ZipCode'] = '';
			$parcel['DeliveryAddress']['CountryIsoCode'] = ($order->get_shipping_country()) ? $order->get_shipping_country() : 'HU';
		}

		//Check for COD
		if($data['package']['cod']) {
			$parcel['CODAmount'] = $data['package']['total'];
			$parcel['CODReference'] = $data['cod_reference_number'];
			$order = wc_get_order($data['order_id']);

			if($order->get_currency() != 'HUF') {
				//$parcel['CODCurrency'] = $order->get_currency();
			}

			//If we need to round to cod amount
			if(VP_Woo_Pont_Helpers::get_option('gls_cod_rounding', 'no') == 'yes') {
				$currency = $order->get_currency();
				if ($currency == 'HUF') {
					$parcel['CODAmount'] = round($data['package']['total'] / 5, 0) * 5;
				} elseif ($currency == 'CZK') {
					$parcel['CODAmount'] = round($data['package']['total']); // Round to nearest 1 CZK
				} elseif ($currency == 'EUR') {
					$parcel['CODAmount'] = round($data['package']['total'] * 20) / 20; // Round to nearest 0.05 EUR
				}
			}
		} else {
			$parcel['CODAmount'] = null;
		}

		//Check for extra services
		$enabled_services = VP_Woo_Pont_Helpers::get_option('gls_extra_services', array());
		if($data['point_id']) {
			$enabled_services = VP_Woo_Pont_Helpers::get_option('gls_extra_services_points', array());
		}

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
			'Username' => $this->api_username,
			'Password' => array_values(unpack('C*', hash('sha512', $this->api_password, true))),
			'ParcelList' => array($parcel),
			'PrintPosition' => 1,
			'ShowPrintDialog' => 0,
			'TypeOfPrinter' => $label_size,
			'WebshopEngine' => 'WooCommerce'
		);

		//If its A6, use the Connect one, which will give us a landscape A6 that we can rotate
		if($label_size == 'A6') {
			$options['TypeOfPrinter'] = 'Connect';
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
		}

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {

		//Create request data
		$options = array(
			'Username' => $this->api_username,
			'Password' => array_values(unpack('C*', hash('sha512', $this->api_password, true))),
			'ParcelIdList' => array($data['parcel_id'])
		);

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

		//Create request data
		$options = array(
			'Username' => $this->api_username,
			'Password' => array_values(unpack('C*', hash('sha512', $this->api_password, true))),
			'ParcelNumber' => $parcel_number,
			'ReturnPOD' => 0,
			'LanguageIsoCode' => 'HU'
		);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'gls-get-tracking-info');

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
		//print_r($response);die();
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
		$enabled = array();
		$supported = $this->supported_countries;
		foreach ($enabled_countries as $enabled_country) {
			$enabled['gls_'.strtolower($enabled_country)] = $supported[$enabled_country].' (GLS)';
		}
		return $enabled;
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

}
