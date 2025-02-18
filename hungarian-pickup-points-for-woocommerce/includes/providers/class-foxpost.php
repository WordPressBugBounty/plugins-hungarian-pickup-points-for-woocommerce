<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Foxpost {
	protected $api_url = 'https://webapi.foxpost.hu/api/';
	protected $username = '';
	protected $password = '';
	protected $api_key = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();

	public function __construct() {
		$this->username = VP_Woo_Pont_Helpers::get_option('foxpost_username');
		$this->password = VP_Woo_Pont_Helpers::get_option('foxpost_password');
		$this->api_key = VP_Woo_Pont_Helpers::get_option('foxpost_api_key');

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_foxpost', array($this, 'get_settings'));

		//Set supported statuses
		$this->package_statuses = array(
			'BACKLOGINFAIL' => 'Csomagodat a célautomata műszaki hibája miatt egyelőre nem tudjuk kézbesíteni, amint a hibát javítottuk, csomagodat behelyezzük.',
			'BACKLOGINFULL' => 'Csomagodat a célautomata túltelítettsége miatt egyelőre nem tudjuk kézbesíteni, amint szabadul fel hely, csomagodat behelyezzük.',
			'BACKTOSENDER' => 'Csomagot a címzett nem vette át, a csomag vissza lett küldve a feladónak.',
			'C2BIN' => 'Csomagot a feladó (eredeti csomag címzettje) visszaküldte, a csomagautomatában elhelyezte.',
			'C2CIN' => 'Csomagod fel lett adva, a csomagautomatában el lett helyezve. A neked címzett (feladott/visszaküldött) csomagot hamarosan kézbesítjük.',
			'COLLECTED' => 'Csomagodat partnerünk átadta részünkre, már úton van a raktárunkba.',
			'COLLECTSENT' => 'Visszaküldött csomagod előkészítettük átadásra.',
			'CREATE' => 'Csomagod létrejött a rendszerünkben, a feladó még nem adta át azt a FOXPOST részére.',
			'DESTROYED' => 'DESTROYED',
			'EMPTYSLOT' => 'Futárunk csomag nélküli üres rekeszt talált.',
			'HDCOURIER' => 'Csomagod kiszállítás alatt van, a házhozszállítást végző partnerünk futára hamarosan kézbesíti.',
			'HDDEPO' => 'Csomagod megérkezett a házhozszállítást végző partnerünk kiszállító depójába.',
			'HDHUBIN' => 'HDHUBIN',
			'HDHUBOUT' => 'Csomagod a házhozszállítást végző partnerünk raktárát elhagyta. Partnerünk a csomagot hamarosan kézbesíti.',
			'HDINTRANSIT' => 'HDINTRANSIT',
			'HDRECEIVE' => 'Csomagod házhozszállítással kézbesítve lett, a címzett átvette.',
			'HDRETURN' => 'Csomagodat a címzett a házhozszállítás során nem vette át (oka: < info_field >), a csomag visszaszállításra került a FoxPost raktárába.',
			'HDSENT' => 'Csomagod beérkezett a FoxPost raktárába, hamarosan átadjuk a házhozszállítást végző partnerünknek.',
			'HDUNDELIVERABLE' => 'Csomagod a házhozszállítás során nem lett átvéve, kézbesítése egyelőre sikertelen (oka: < info_field >).',
			'INWAREHOUSE' => 'Címzett által át nem vett / visszaküldött csomagod visszaszállításra került a FoxPost raktárába.',
			'MISSORT' => 'A csomag nem megfelelő automatára lett szortolva.',
			'MPSIN' => 'MPSIN',
			'OPERIN' => 'Csomagod megérkezett az általad választott (vissza)kézbesítési automatába, most már átveheted!',
			'OPEROUT' => 'Csomagodat futárunk kivette a csomagautomatából.',
			'OVERTIMED' => 'OVERTIMED',
			'OVERTIMEOUT' => 'Csomagod nem került átvételre a tárolási idő alatt, ezért a küldeményt visszaküldjük a feladónak.',
			'PREPAREDFORPD' => 'Csomagodat az automata telítettsége / műszaki hibája miatt személyesen veheted át munkatársunktól. < delivery_date >',
			'PREREDIRECT' => 'PREREDIRECT',
			'RECEIVE' => 'Csomagod (vissza)kézbesítve lett, a címzett/feladó átvette.',
			'REDIRECT' => 'Csomagodat az automata telítettsége / műszaki hibája miatt átírányítottuk egy másik csomagautomatába < destination_apm >',
			'RESENT' => 'Az át nem vett csomagod újraküldésre került.',
			'RETURN' => 'RETURN',
			'RETURNCOURIER' => 'Visszaküldött csomagod a futárunknál van, hamarosan átadja üzleti partnerünk részére.',
			'RETURNDELIVERED' => 'Visszaküldött csomagodat üzleti partnerünk átvette.',
			'RETURNED' => 'Átvevő által visszaküldött csomag.',
			'SLOTCHANGE' => 'SLOTCHANGE',
			'SORTIN' => 'Csomagod beérkezett a FoxPost raktárába. A feladott/visszaküldött csomagot hamarosan kézbesítjük.',
			'SORTOUT' => 'Csomag úton (kiszállítás/visszaszállítás alatt) van az általad választott csomagautomatához.',
			'WBXREDIRECT' => 'WBXREDIRECT',
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array('SORTIN', 'OPEROUT', 'SORTOUT', 'C2BIN', 'HDDEPO', 'COLLECTED', 'REDIRECT', 'RESENT', 'PREPAREDFORPD', 'INWAREHOUSE', 'HDSENT'),
			'delivery' => array('OPERIN', 'HDCOURIER'),
			'delivered' => array('RECEIVE', 'COLLECTSENT', 'HDRECEIVE'),
			'errors' => array('BACKLOGINFAIL', 'BACKLOGINFULL', 'MISSORT', 'EMPTYSLOT', 'HDUNDELIVERABLE', 'BACKTOSENDER', 'HDRETURN', 'OVERTIMEOUT', 'RETURNED')
		);
	}

	public function get_settings($settings) {
		$foxpost_settings = array(
			array(
				'title' => __( 'Foxpost settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('API username', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'foxpost_username'
			),
			array(
				'title' => __('API password', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'foxpost_password'
			),
			array(
				'title' => __('API key', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'foxpost_api_key'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Parcel size', 'vp-woo-pont' ),
				'default' => 'XS',
				'options' => array(
					'XS' => __( 'XS', 'vp-woo-pont' ),
					'S' => __( 'S', 'vp-woo-pont' ),
					'M' => __( 'M', 'vp-woo-pont' ),
					'L' => __( 'L', 'vp-woo-pont' ),
					'XL' => __( 'XL', 'vp-woo-pont' ),
				),
				'id' => 'foxpost_parcel_size'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A7',
				'options' => array(
					'a7' => __( 'A7', 'vp-woo-pont' ),
					'a5' => __( 'A5', 'vp-woo-pont' ),
					'A6' => __( 'A6 on A4(recommended)', 'vp-woo-pont' ),
					'a6' => __( 'A6', 'vp-woo-pont' ),
					'_85X85' => __( '_85X85', 'vp-woo-pont' )
				),
				'id' => 'foxpost_sticker_size'
			),
			array(
				'title' => __('Sender name', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __( 'If you generate a delivery note, this name will be on it as sender.', 'vp-woo-pont' ),
				'id' => 'foxpost_sender'
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$foxpost_settings;
	}

	public function create_label($data) {

		//Create item
		$comment = VP_Woo_Pont()->labels->get_package_contents_label($data, 'foxpost');
		$item = array(
			'comment' => mb_substr($comment,0,50), //50 character limit
			'deliveryNote' => mb_substr($data['order']->get_customer_note(),0,50),
			'orderId' => $data['order_id'],
			'recipientName' => $data['customer']['name'],
			'recipientPhone' => $data['customer']['phone'],
			'recipientEmail' => $data['customer']['email'],
			'size' => strtolower(VP_Woo_Pont_Helpers::get_option('foxpost_parcel_size', 'XS')),
			'refCode' => $data['reference_number']
		);

		//If its a point order
		if($data['point_id']) {
			$item['destination'] = $data['point_id'];
			$item['sendType'] = 'APM';
		}

		//If its home delivery
		if(!$data['point_id']) {
			$order = $data['order'];
			$item['sendType'] = 'HD';
			$item['recipientAddress'] = implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2()));
			$item['recipientCity'] = $order->get_shipping_city();
			$item['recipientZip'] = $order->get_shipping_postcode();
		}

		//Check for COD
		if($data['package']['cod']) {
			$item['cod'] = round($data['package']['total']);
		}

		//Create request data
		$options = array($item);

		//So developers can modify
		$options = apply_filters('vp_woo_pont_foxpost_label', $options, $data);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'foxpost-create-label');

		//Submit request
		$request = wp_remote_post( $this->api_url.'parcel', array(
			'body'    => json_encode($options),
			'headers' => $this->get_auth_header($data['order']),
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
			VP_Woo_Pont()->log_error_messages($response, 'foxpost-create-label');
			if(isset($response['error'])) {
				return new WP_Error( 'foxpost_error_unknown', $response['error'] );
			}
		}

		//Check for errors
		if(!$response['valid']) {
			VP_Woo_Pont()->log_error_messages($response, 'foxpost-create-label');
			$package = $response['parcels'][0];
			$error_messages = array();
			if(isset($package['errors'])) {
				foreach ($package['errors'] as $fault) {
					$error_messages[] = $fault['message'];
					$error_messages[] = $fault['field'];
				}
			}
			return new WP_Error( 'bad_request', implode('; ', $error_messages) );
		}

		//Else, it was successful
		$parcel_number = $response['parcels'][0]['clFoxId'];

		//Allow plugins to hook in
		do_action('vp_woo_pont_after_foxpost_label_created', $response, $data);

		//Next, generate the PDF label
		$label_size = VP_Woo_Pont_Helpers::get_option('foxpost_sticker_size', 'A6');
		if($label_size == 'A6') $label_size = 'a6';
		$request = wp_remote_post( $this->api_url.'label/'.strtoupper($label_size), array(
			'body'    => json_encode(array($parcel_number)),
			'headers' => $this->get_auth_header($data['order'], 'label'),
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
			VP_Woo_Pont()->log_error_messages($response, 'foxpost-download-label');
			return new WP_Error( $response['code'], $response['message'] );
		}

		//Now we have the PDF as base64, save it
		$pdf = trim($response);

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('foxpost', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Crop to A6 portrait if needed
		if(VP_Woo_Pont_Helpers::get_option('foxpost_sticker_size', 'A6') == 'A6') {
			$rotated_pdf = VP_Woo_Pont_Print::rotate_to_a6($pdf_file['path']);
			VP_Woo_Pont_Labels::save_pdf_file($rotated_pdf, $pdf_file);
		}

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
		VP_Woo_Pont()->log_debug_messages($data, 'foxpost-void-label-request');

		//So developers can modify
		$options = apply_filters('vp_woo_pont_foxpost_void_label', $data, $data);

		//Submit request
		$request = wp_remote_request( $this->api_url.'parcel/'.$options['parcel_number'], array(
			'method' => 'DELETE',
			'headers' => $this->get_auth_header($data['order']),
		));

		//Continue if error 500
		if(wp_remote_retrieve_response_code( $request ) == 500) {
			$label = array();
			$label['success'] = true;
			return $label;
		}

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 204) {
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );

			//If INVALID_PARCEL_ID, that means it was deleted on foxpost already
			if($response['error'] == 'INVALID_PARCEL_ID' || $response['error'] == 'PARCEL_LAST_STATUS_NOT_CREATED') {
				$label = array();
				$label['success'] = true;
				return $label;
			} else {
				VP_Woo_Pont()->log_error_messages($response, 'foxpost-delete-label');
				if($response && $response['error']) {
					return new WP_Error( 'foxpost_error_'.$response['status'], $response['error'] );
				} else {
					return new WP_Error( 'foxpost_error_unknown', __('Unknown error', 'vp-woo-pont') );
				}
			}
		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$label = array();
		$label['success'] = true;

		VP_Woo_Pont()->log_debug_messages($response, 'foxpost-void-label-response');

		return $label;
	}

	//Return tracking link
	public function get_tracking_link($parcel_number, $order = false) {
		return 'https://foxpost.hu/csomagkovetes/?code='.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Submit request
		$request = wp_remote_request( $this->api_url.'tracking/'.$parcel_number, array(
			'method' => 'GET',
			'headers' => $this->get_auth_header($order),
		));

		VP_Woo_Pont()->log_debug_messages($request, 'foxpost-get-tracking-info');

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {

			//If INVALID_PARCEL_ID, that means it was deleted on foxpost already
			return new WP_Error( 'foxpost_error_unknown', __('Unknown error', 'vp-woo-pont') );

		}

		//Check for success
		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );
		$label = array();
		$label['success'] = true;

		//Check if empty response
		if(empty($response)) {
			return new WP_Error( 'foxpost_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Collect events
		$tracking_info = array();
		foreach ($response['traces'] as $event) {
			$tracking_info[] = array(
				'date' => strtotime($event['statusDate']),
				'event' => $event['status'],
				'label' => $event['shortName'],
				'comment' => $event['longName']
			);
		}

		return $tracking_info;
	}

	public function get_auth_header($order, $type = '') {
		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
			'Content-Type' => 'application/json',
			'api-key' => $this->api_key
		);

		if($type != 'label') {
			$headers['Accept'] = 'application/json';
		}

		if($type == 'deliveryNote') {
			$headers['Accept'] = 'application/pdf';
		}

		return apply_filters('vp_woo_pont_foxpost_auth_header', $headers, $order);
	}

	public function close_shipments($packages = array(), $orders = array()) {

		//Set package numbers
		$options = array(
			'clFoxCodes' => $packages,
			'sender' => VP_Woo_Pont_Helpers::get_option('foxpost_sender')
		);

		//Submit request
		$request = wp_remote_post( $this->api_url.'label/deliveryNote', array(
			'body'    => json_encode($options),
			'headers' => $this->get_auth_header(false, 'deliveryNote'),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			return new WP_Error( 'foxpost_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		//Parse response
		$pdf = wp_remote_retrieve_body( $request );

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('foxpost-manifest', 0);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Return response in unified format
		return array(
			'shipments' => array(),
			'orders' => $orders,
			'pdf' => array(
				'foxpost' => $pdf_file['name']
			)
		);
	}

	public function export_label($data) {
		$order = $data['order'];
		$comment = VP_Woo_Pont()->labels->get_package_contents_label($data, 'foxpost');

		//Setup CSV file
		$csv_row = array();
		$csv_row[0] = $data['customer']['name'];
		$csv_row[1] = $data['customer']['phone'];
		$csv_row[2] = $data['customer']['email'];
		$csv_row[3] = $data['point_id'];
		$csv_row[4] = '';
		$csv_row[5] = '';
		$csv_row[6] = '';
		$csv_row[7] = ($data['package']['cod']) ? round($data['package']['total']) : '';
		$csv_row[8] = strtolower(VP_Woo_Pont_Helpers::get_option('foxpost_parcel_size', 'XS'));
		$csv_row[9] = mb_substr($data['order']->get_customer_note(),0,50);
		$csv_row[10] = substr($comment,0,50);
		$csv_row[11] = '';
		$csv_row[12] = '';
		$csv_row[13] = $data['order_id'];
		$csv_row[14] = $data['reference_number'];

		//For home delivery
		if(!$data['point_id']) {
			$csv_row[4] = $order->get_shipping_city();
			$csv_row[5] = $order->get_shipping_postcode();
			$csv_row[6] = implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2()));
		}

		//Required header
		$header = array("Címzett neve","Címzett telefonszáma","Címzett email címe","Átvételi automata","Település","Irányítószám","Utca, házszám","Utánvételi összeg","Csomag méret","Futár információk","Saját adatok","Címkenyomtatás","Törékeny","Egyedi vonalkód","Referencia kód");

		return array(
			'data' => array($header, $csv_row),
			'separator' => ',',
		);
	}

}