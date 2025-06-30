<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_TransSped {
	protected $api_url = 'https://minimo-logistics.com/api/';
	protected $api_username = '';
	protected $api_password = '';
	protected $api_client_code = '';
	public $package_statuses = array();
	public $package_statuses_tracking = array();
	public $packaging_types = array();

	public function __construct() {
		$this->api_username = VP_Woo_Pont_Helpers::get_option('trans_sped_username');
		$this->api_password = htmlspecialchars_decode(VP_Woo_Pont_Helpers::get_option('trans_sped_password'));
		$this->api_client_code = VP_Woo_Pont_Helpers::get_option('trans_sped_client_code');

		if(VP_Woo_Pont_Helpers::get_option('trans_sped_dev_mode', 'no') == 'yes') {
			$this->api_url = 'https://li1366-20.members.linode.com/api/';
		}

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_transsped', array($this, 'get_settings'));

		//Set supported statuses
		$this->package_statuses = array(
			"st000" => "Felrakodásra vár",
			"st001" => "Felrakodva",
			"st002" => "Beszállításra vár",
			"st200" => "Teljesítve",
			"st300" => "Sikertelen",
			"st301" => "Beszállítás nem sikerült",
			"re000" => "Cím nem található",
			"re001" => "Sérült áru, nem vette át",
			"re002" => "Hiányos áru, nem vette át",
			"re003" => "Nem jött le az áru",
			"re004" => "Nem rendelte",
			"re005" => "Zárva volt/Nem volt otthon",
			"re007" => "Nem volt pénze",
			"re009" => "Túl hosszú várakozási idő",
			"re010" => "Már elszállították",
			"re011" => "Nincs kész az áru",
			"re012" => "Nem erre a napra kérte",
			"re013" => "Lemondott rendelés",
			"re015" => "Árucsere, nem vette át",
			"re017" => "Sérült csomagolás, nem vette át",
			"re018" => "Rossz autóra került",
			"re019" => "Nincs küldendő áru",
			"re020" => "Egyéb, nem teljesítve",
			"re021" => "Nem a mi körzetünk",
			"re022" => "Hibás címzés",
			"re023" => "Raktárban maradt",
			"re025" => "Cím nem megközelíthető",
			"re026" => "Nincs áruátvétel",
			"re027" => "Emelőhátfalas autó szükséges",
			"re028" => "Szállítólevél hiányzik",
			"re029" => "Helyhiány miatt nem tudták átvenni az árut",
			"re030" => "ADR-okmányok hiánya",
			"re031" => "Tegnap visszaküldve",
			"re032" => "Vámokmányok hiányában",
			"re033" => "Kapacitás hiányában (saját)",
			"re034" => "Nincs listán",
			"re035" => "Leltározás miatt",
			"re036" => "Műszaki hiba miatt",
			"re037" => "Szállítólevél hiányos",
			"re038" => "Lemondva, megbízó által",
			"re039" => "Lemondva, címzett által",
			"re040" => "Későn érkezett a címre",
			"re041" => "Hibás megbízói méretadás",
			"re042" => "Rövid szavatossági idő",
			"re043" => "Címzett nem elérhető",
			"re044" => "Nem került szállításba",
			"re045" => "Vis maior",
			"re046" => "Hibás EAN/vonalkód",
			"re047" => "Rossz kiszerelés",
			"re048" => "Sofőr nem teljesítette",
			"re049" => "Késve jött le az áru",
			"no000" => "Sérült áru, átvette",
			"no001" => "Az áru/szállítmány egy részét vette át",
			"no002" => "Árucsere, átvette",
			"no003" => "Többlet áru érkezett",
			"no004" => "Sérült csomagolás, átvette",
			"no005" => "Egyéb, teljesítve",
			"no007" => "Korábban teljesítve",
			"no008" => "Részteljesítés",
			"no009" => "Hiányos áru, átvette",
			"no010" => "Feltételesen átvéve",
			"no011" => "Szállítólevél hiányzik",
			"no012" => "Egy vagy több sérült terméket nem vettek át",
			"no013" => "Sértetlen csomagolás alatt sérült áru",
			"no014" => "Egy vagy több árucserélt terméket nem vettek át",
			"no015" => "Hibás EAN/vonalkód",
			"no016" => "Egy vagy több sérült terméket nem vettek át",
			"no017" => "Hiányos áru, nem vette át",
			"no018" => "Nem rendelte, átvette",
			"no019" => "Egy vagy több árucserélt terméket nem vettek át",
			"no020" => "Egy vagy több csomagolás sérült terméket nem vettek át",
			"no021" => "Egy vagy több termék rossz autóra került",
			"no022" => "Egyéb, részben nem teljesítve",
			"no023" => "Egy vagy több termék raktárban maradt",
			"no024" => "Egy vagy több terméket helyhiány miatt nem vettek át",
			"no025" => "Egy vagy több termék nincs listán",
			"no026" => "Részben lemondott rendelés megbízó által",
			"no027" => "Részben lemondott rendelés címzett által",
			"no028" => "Egy vagy több termék rossz kiszerelésben érkezett",
			"no029" => "Egy vagy több termék rövid szavidővel érkezett"
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array('st001', 'st000'),
			'delivery' => array('st002'),
			'delivered' => array('st200', 'no000', 'no001', 'no002', 'no004', 'no005', 'no007', 'no008', 'no009', 'no010'),
			'errors' => array('no003', 'no011', 'no012', 'no013', 'no014', 'st300', 'st301', 're000', 're001', 're002', 're003', 're004', 're005', 're007', 're009', 're010', 're011', 're012', 're013', 're015', 're017', 're018', 're019', 're020', 're021', 're022', 're023', 're025', 're026', 're027', 're028', 're029', 're030', 're031', 're032', 're033', 're034', 're035', 're036', 're037', 're038', 're039', 're040', 're041', 're042', 're043', 're044', 're045', 're046', 're047', 're048', 're049')
		);

		$this->packaging_types = array(
			'full' => __('Full container', 'vp-woo-pont'),
			'half' => __('Half container', 'vp-woo-pont'),
			'mini' => __('Mini container', 'vp-woo-pont'),
			'custom' => __('Custom', 'vp-woo-pont'),
		);

	}

	public function get_settings($settings) {
		$transsped_settings = array(
			array(
				'title' => __( 'Trans-Sped ZERO settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title' => __('Trans-Sped Username', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_username'
			),
			array(
				'title' => __('Trans-Sped Password', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_password'
			),
			array(
				'title' => __('Short name', 'vp-woo-pont'),
				'desc' => __('Trans-Sped client short name or client code', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_client_code'
			),
			array(
				'title'    => __( 'Enable DEV mode', 'vp-woo-pont' ),
				'type'     => 'checkbox',
				'id' => 'trans_sped_dev_mode'
			),
			array(
				'title' => __('Sender name', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_sender_name'
			),
			array(
				'title' => __('Contact name', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_contact_name'
			),
			array(
				'title' => __('Sender address', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_sender_address'
			),
			array(
				'title' => __('Sender city', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_sender_city'
			),
			array(
				'title' => __('Sender postcode', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_sender_postcode'
			),
			array(
				'title' => __('Sender phone number', 'vp-woo-pont'),
				'type' => 'text',
				'desc' => __( 'Use the +36XXXXXXXXX format', 'vp-woo-pont' ),
				'id' => 'trans_sped_sender_phone'
			),
			array(
				'title' => __('Sender email address', 'vp-woo-pont'),
				'type' => 'text',
				'id' => 'trans_sped_sender_email'
			),
			array(
				'title' => __('Product categories containing food', 'vp-woo-pont'),
				'type' => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => array(),
				'id' => 'trans_sped_food_categories'
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'a5',
				'options' => array(
					'a5' => __( 'A5', 'vp-woo-pont' ),
					'25x10' => __( '25x10', 'vp-woo-pont' ),
					'85x85' => __( '85x85', 'vp-woo-pont' ),
				),
				'id' => 'trans_sped_sticker_size'
			),
			array(
				'title' => __('Default package type', 'vp-woo-pont'),
				'desc' => __( 'Default package type, used in case you generate labels with an automation. Can be changed manually on each order.', 'vp-woo-pont' ),
				'type' => 'select',
				'class'   => 'wc-enhanced-select',
				'options' => array(
					'full' => __('Full container', 'vp-woo-pont'),
					'half' => __('Half container', 'vp-woo-pont'),
					'custom' => __('Custom', 'vp-woo-pont'),
				),
				'id' => 'trans_sped_package_type'
			),
			array(
				'title' => __('Custom import date', 'vp-woo-pont'),
				'desc' => sprintf( 
					__( 'By default it will auto-select the next available day for pickup. Enter it in this format: year-month-day. Leave empty to use the default value: <strong>%s</strong>', 'vp-woo-pont' ),
					$this->get_next_workday(true)
				),
				'type' => 'text',
				'id' => 'trans_sped_import_date'
			),
			array(
				'type' => 'sectionend'
			)
		);

		//Load product categories on settings page
		if($this->is_settings_page()) {
			$transsped_settings['trans_sped_food_categories']['options'] = VP_Woo_Pont_Helpers::get_product_categories();
		}

		return $settings+$transsped_settings;
	}

	//Check if we are on the settings page
	public function is_settings_page() {
		global $current_section;
		return ($current_section && $current_section === 'vp_pont');
	}

	public function create_label($data) {

		//Create item
		$comment = VP_Woo_Pont()->labels->get_package_contents_label($data, 'transsped');
		$order = wc_get_order($data['order_id']);
		$log = array();

		//Validate packaging parameters
		if(!isset($data['options']['transsped_packaging'])) {
			return new WP_Error( 'transsped_error', __('Packaging quantity missing.', 'vp-woo-pont') );
		}
		
		$item = array(
			'waybill' => $data['order_number'],
			'speditorName' => $this->api_client_code,
			'type' => 'Delivery',
			'note' => $comment,
			'responsible' => VP_Woo_Pont_Helpers::get_option('trans_sped_contact_name'),
			'sender' => array(
				'name' => VP_Woo_Pont_Helpers::get_option('trans_sped_sender_name'),
				'address' => VP_Woo_Pont_Helpers::get_option('trans_sped_sender_address'),
				'zip' => intval(VP_Woo_Pont_Helpers::get_option('trans_sped_sender_postcode')),
				'city' => VP_Woo_Pont_Helpers::get_option('trans_sped_sender_city'),
				'phoneNumber' => VP_Woo_Pont_Helpers::get_option('trans_sped_sender_phone'),
				'smsNumber' => VP_Woo_Pont_Helpers::get_option('trans_sped_sender_phone'),
				'email' => VP_Woo_Pont_Helpers::get_option('trans_sped_sender_email'),
			),
			'customer' => array(
				'name' => $data['customer']['name_with_company'],
				'address' => $order->get_shipping_address_1().' '.$order->get_shipping_address_2(),
				'zip' => $order->get_shipping_postcode(),
				'city' => $order->get_shipping_city(),
				'phoneNumber' => $data['customer']['phone'],
				'smsNumber' => $data['customer']['phone'],
				'email' => $data['customer']['email']
			),
			'cargo' => array(
				'packageList' => array(
					array(
						'type' => VP_Woo_Pont_Helpers::get_option('trans_sped_package_type'),
						'quantity' => 1,
						'weight' => wc_get_weight($data['package']['weight'], 'kg')	
					)
				)
			)
		);

		//Logging
		$log['start'] = current_time('mysql');
		$log['req'] = $item;

		//If theres a closed shipment list from today, move the import date to tomorrow at least
		if($this->is_shipment_closed_today()) {
			$item['importDate'] = $this->get_next_workday();
		}

		//Check for COD
		if($data['package']['cod']) {
			$item['cashOnDelivery'] = round($data['package']['total']);
		}

		//Check if it has food product
		if($this->contains_food_contents($order)) {
			$item['isFood'] = true;
		}

		//If generated manually, setup custom package list
		if(isset($data['options']) && isset($data['options']['transsped_packaging'])) {
			$item['cargo']['packageList'] = array();
			$type_names = array('full' => 'láda', 'half' => 'fél', 'custom' => 'saját', 'mini' => 'mini');
			$total_qty = 0;
			foreach($data['options']['transsped_packaging'] as $packaging_type => $qty) {
				$total_quantity += intval($qty);
			}

			//Calculate weight
			$weight = wc_get_weight($data['package']['weight']/$total_quantity, 'kg');

			//Create items
			foreach($data['options']['transsped_packaging'] as $packaging_type => $qty) {
				$item['cargo']['packageList'][] = array(
					'type' => $type_names[$packaging_type],
					'quantity' => $qty,
					'weight' => $weight
				);
			}

		}

		//So developers can modify
		$options = apply_filters('vp_woo_pont_transsped_label', $item, $data);

		//Create request data
		$options = array($item);

		//Logging
		VP_Woo_Pont()->log_debug_messages($options, 'transsped-create-label');

		//Logging
		$log['shipments-api-start'] = current_time('mysql');

		//Submit request
		$request = wp_remote_post( $this->api_url.'shipments', array(
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

		//Logging
		$log['shipments-api-response'] = $response;
		$log['shipments-api-end'] = current_time('mysql');

		//Check for API errors
		if($response['failCount'] > 0) {
			VP_Woo_Pont()->log_error_messages($response, 'transsped-create-label');
			$error = $response['message'];
			return new WP_Error( 'transsped_error', $error );
		}

		//Create response	
		$label = array();
		$label['id'] = $response['shipments'][0]['_id'];
		$label['number'] = $response['shipments'][0]['_id'];

		//Try to store barcodes too
		if(isset($response['shipments'][0]['cargo']['itemList']) && !empty($response['shipments'][0]['cargo']['itemList'])) {
			$barcodes = array();
			foreach($response['shipments'][0]['cargo']['itemList'] as $item) {
				if(isset($item['stackId'])) {
					$barcodes[] = $item['stackId'];
				}
			}
			$barcodes = implode('|', $barcodes);
			$label['id'] = $barcodes;
		}

		//Get label
		$label_options = array(
			'type' => VP_Woo_Pont_Helpers::get_option('trans_sped_sticker_size', 'a5'),
			'items' => array(array(
				'shipmentId' => $response['shipments'][0]['_id']
			))
		);

		$log['stickers-api-start'] = current_time('mysql');
		$log['stickers-api-req'] = $label_options;

		$request = wp_remote_post($this->api_url.'download-stickers/', array(
			'body'    => json_encode($label_options),
			'headers' => $this->get_auth_header($data['order']),
			'timeout' => 60
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Parse response
		$response = wp_remote_retrieve_body( $request );

		$log['stickers-api-end'] = current_time('mysql');

		//Now we have the PDF as base64, save it
		$pdf = $response;

		$log['save-sticker-start'] = current_time('mysql');


		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('transsped', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		$log['save-sticker-end'] = current_time('mysql');

		//Create response
		$label['pdf'] = $pdf_file['name'];
		$label['needs_closing'] = true;

		$log['end'] = current_time('mysql');
		VP_Woo_Pont()->log_debug_messages($log, 'transsped-create-label-log');

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function get_auth_header($order, $type = '') {
		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->api_username . ':' . $this->api_password ),
			'Content-Type' => 'application/json',
		);
		return apply_filters('vp_woo_pont_trans_sped_auth_header', $headers, $order);
	}

	public function void_label($data) {

		//Submit request
		$request = wp_remote_request( $this->api_url.'shipments/id/'.$data['parcel_number'], array(
			'method' => 'DELETE',
			'headers' => $this->get_auth_header($data['order']),
		));

		//Check for errors
		if(is_wp_error($request)) {
			return $request;
		}

		//Check for API errors
		if(wp_remote_retrieve_response_code( $request ) != 200) {
			return new WP_Error( 'transsped_error_unknown', __('Unknown error', 'vp-woo-pont') );
		}

		$label = array();
		$label['success'] = true;

		return $label;
	}

	//Return tracking link
	public function get_tracking_link($parcel_number, $order = false) {
		return $this->api_url.'track-and-trace/?ids='.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Parcel number
		$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

		//Submit request
		$request = wp_remote_get( $this->get_tracking_link($parcel_number), array(
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
		if($response && count($response) > 0 && isset($response[0]['statuses']) && count($response[0]['statuses']) > 0) {
			$tracking_info = array();

			//Loop through events
			foreach ($response[0]['statuses'] as $event) {
				$tracking_info[] = array(
					'date' => strtotime($event['date']),
					'event' => $event['code'],
					'label' => $event['formattedStatus']
				);
			}

			//And return tracking info to store
			return $tracking_info;

		} else {
			return new WP_Error( 'gls_tracking_info_error', '' );
		}

	}

	public function contains_food_contents($order) {

		//Get product category ids
		$product_categories = array();
		$order_items = $order->get_items();
		foreach ($order_items as $order_item) {
			if($order_item->get_product() && $order_item->get_product()->get_category_ids()) {
				$product_categories = $product_categories+$order_item->get_product()->get_category_ids();
			}
		}

		//Check for food categories
		$food_category_ids = VP_Woo_Pont_Helpers::get_option('trans_sped_food_categories', array());
		$has_food_category = !empty(array_intersect($product_categories, $food_category_ids));
		return $has_food_category;

	}

	public function close_shipments($packages = array(), $orders = array()) {

		//Init mPDF
		require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

		// Create a new mPDF object
		$mpdf = new \Mpdf\Mpdf(['mode' => 'c', 'format' => 'A4', 'allow_charset_conversion' => true]);

		//Setup tempalte data
		$label_data = array(
			'orders' => $orders,
			'carrier' => 'transsped',
			'icon' => VP_Woo_Pont()::$plugin_url.'/assets/images/icon-transsped.svg'
		);
		$html = wc_get_template_html('shipments-table.php', $label_data, false, VP_Woo_Pont::$plugin_path . '/templates/');

		//Add the HTML content to the PDF document
		$mpdf->WriteHTML($html);

		//Output the PDF document
		$pdf = $mpdf->Output('', 'S');

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('transsped-manifest', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Return response in unified format
		return array(
			'shipments' => array(),
			'orders' => $orders,
			'pdf' => array(
				'transsped' => $pdf_file['name']
			)
		);
	}

	//Check if we have a shipment closed on the current day
	public function is_shipment_closed_today() {
		global $wpdb;
		$current_date = current_time('mysql');
		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}vp_woo_pont_mpl_shipments WHERE carrier = %s AND DATE(time) = DATE(%s)",
			'transsped',
			$current_date
		);
		$result = $wpdb->get_results($query);
		return ($result);
	}

	public function get_next_workday($default = false) {
		if(!$default && $custom_import_date = VP_Woo_Pont_Helpers::get_option('trans_sped_import_date')) {
			return $custom_import_date;
		}

		$tomorrow = date('Y-m-d', strtotime('+2 day'));
		$currentDayOfWeek = date('N');
		$dayOfWeek = date('N', strtotime($tomorrow));
		$nextMonday = date('Y-m-d', strtotime('next Tuesday', strtotime($tomorrow)));
		if ($dayOfWeek >= 6 || $currentDayOfWeek == 6 || $currentDayOfWeek == 7) {
			return $nextMonday;
		} else {
			return $tomorrow;
		}
	}

}