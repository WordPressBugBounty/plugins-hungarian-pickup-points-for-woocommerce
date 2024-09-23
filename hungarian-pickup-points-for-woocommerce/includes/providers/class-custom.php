<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Custom {
	public $package_statuses = array();
	public $package_statuses_tracking = array();

	public function __construct() {

		//Load settings
		add_filter('vp_woo_pont_carrier_settings_custom', array($this, 'get_settings'));

		//Set supported statuses
		$this->package_statuses = array(
			1 => __( "We have received the packet data. Freshly created packet.", 'vp-woo-pont'),
			4 => __( "Packet is on the way.", 'vp-woo-pont'),
			7 => __( "Packet was picked up by the customer.", 'vp-woo-pont'),
		);

		//Categorized for tracking page
		$this->package_statuses_tracking = array(
			'shipped' => array(1),
			'delivery' => array(4),
			'delivered' => array(7)
		);

		//On status change, update tracking info
		add_action('woocommerce_order_status_changed', array($this, 'update_tracking_info'), 10, 3);

		//Preview template
		add_action( 'admin_init', array( $this, 'load_preview_template') );

	}

	public function get_settings($settings) {
		$gls_settings = array(
			array(
				'title' => __( 'Custom Label', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
			array(
				'title'   => __( 'Enable/Disable', 'vp-woo-pont' ),
				'type'    => 'checkbox',
				'desc'   => __( 'Enable carrier', 'vp-woo-pont' ),
				'default' => 'no',
				'id' => 'custom_enabled'
			),
			array(
				'title' => __('Sender Details', 'vp-woo-pont'),
				'type' => 'textarea',
				'id' => 'custom_sender',
			),
			array(
				'title' => __('Logo on label', 'vp-woo-pont'),
				'type' => 'text',
				'desc_tip' => __( 'Enter an image URL', 'vp-woo-pont' ),
				'id' => 'custom_logo',
			),
			array(
				'title' => __('Additional details', 'vp-woo-pont'),
				'type' => 'textarea',
				'desc_tip' => __('Additional text that appears below the barcode', 'vp-woo-pont'),
				'id' => 'custom_text',
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Sticker size', 'vp-woo-pont' ),
				'default' => 'A5',
				'options' => array(
					'A5' => __( 'A5', 'vp-woo-pont' ),
					'A6' => __( 'A6 on A4(recommended)', 'vp-woo-pont' )
				),
				'id' => 'custom_sticker_size',
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Package in delivery order status', 'vp-woo-pont' ),
				'options' => $this->get_order_statuses(__('None', 'Order status after label generated', 'vp-woo-pont')),
				'desc_tip' => __( 'Mark order tracking status "in delivery", if the order status changes to this.', 'vp-woo-pont' ),
				'id' => 'custom_delivery_status',
			),
			array(
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'title' => __( 'Package delivered order status', 'vp-woo-pont' ),
				'options' => $this->get_order_statuses(__('None', 'Order status after label generated', 'vp-woo-pont')),
				'desc_tip' => __( 'Mark order tracking status "delivered", if the order status changes to this.', 'vp-woo-pont' ),
				'id' => 'custom_delivered_status',
			),
			array(
				'type' => 'vp_custom_label_template',
				'id' => 'custom_template',
				'title' => __('Template', 'vp-woo-pont')
			),
			array(
				'type' => 'sectionend'
			)
		);

		return $settings+$gls_settings;
	}

	public function create_label($data) {

		//Init mPDF
		require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

		// Create a new mPDF object
		$mpdf = new \Mpdf\Mpdf(['useSubstitutions' => true, 'format' => VP_Woo_Pont_Helpers::get_option('custom_sticker_size', 'A5'), 'allow_charset_conversion' => true]);

		//Setup tempalte data
		$label_data = array(
			'sender' => VP_Woo_Pont_Helpers::get_option('custom_sender', ''),
			'logo' => VP_Woo_Pont_Helpers::get_option('custom_logo', ''),
			'contents' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'custom'),
			'text' => VP_Woo_Pont_Helpers::get_option('custom_text', ''),
			'data' => $data
		);
		$html = wc_get_template_html('order/custom-label.php', $label_data, false, VP_Woo_Pont::$plugin_path . '/templates/');

		//Add the HTML content to the PDF document
		$mpdf->WriteHTML($html);


		//Output the PDF document
		$pdf = $mpdf->Output('', 'S');

		//Try to save PDF file
		$pdf_file = VP_Woo_Pont_Labels::get_pdf_file_path('custom', $data['order_id']);
		VP_Woo_Pont_Labels::save_pdf_file($pdf, $pdf_file);

		//Create response
		$label = array();
		$label['id'] = $data['order_id'];
		$label['number'] = $data['order_id'];
		$label['pdf'] = $pdf_file['name'];

		//Return file name, package ID, tracking number which will be stored in order meta
		return $label;
	}

	public function void_label($data) {
		$label = array();
		$label['success'] = true;
		return $label;
	}

	public function get_tracking_link($parcel_number, $order = false) {
		return 'https://sameday.hu/#awb='.esc_attr($parcel_number);
	}

	//Function to get tracking informations using API
	public function get_tracking_info($order) {

		//Get existing status updates
		$tracking_info = $order->get_meta('_vp_woo_pont_parcel_info');
		if(!$tracking_info) $tracking_info = array();

		//Check for created event
		$events_created = array();
		foreach ($tracking_info as $tracking_item) {
			$events_created[] = $tracking_item['event'];
		}

		//Check for created event
		if(!in_array(1, $events_created)) {
			$tracking_info[] = array(
				'date' => time(),
				'event' => 1,
				'label' => ''
			);
		}

		return $tracking_info;
	}

	public function save_tracking_info($order, $event) {

		//Get existing status updates
		$tracking_info = $order->get_meta('_vp_woo_pont_parcel_info');
		if(!$tracking_info) $tracking_info = array();

		//Check for created event
		$events_created = array();
		foreach ($tracking_info as $tracking_item) {
			$events_created[] = $tracking_item['event'];
		}

		//Check for created event
		if(!in_array($event, $events_created)) {
			$tracking_info[] = array(
				'date' => time(),
				'event' => $event,
				'label' => ''
			);
		}

		//Reverse array, so newest is the first
		$tracking_info = array_reverse($tracking_info);

		//Update order meta
		$order->update_meta_data('_vp_woo_pont_parcel_info', $tracking_info);
		$order->update_meta_data('_vp_woo_pont_parcel_info_time', time());
		$order->save();
	}

	public function get_order_statuses($empty = false) {
		$statuses = array();
		if(function_exists('wc_order_status_manager_get_order_status_posts')) {
			$filtered_statuses = array();
			$custom_statuses = wc_order_status_manager_get_order_status_posts();
			foreach ($custom_statuses as $status ) {
				$filtered_statuses[ 'wc-' . $status->post_name ] = $status->post_title;
			}
			$statuses = $filtered_statuses;
		} else {
			$statuses = wc_get_order_statuses();
		}

		if($empty) {
			$statuses = array('' => $empty) + $statuses;
		}

		return $statuses;
	}

	public function update_tracking_info($order_id, $old_status, $new_status) {
		$order = wc_get_order($order_id);
		if($order->get_meta('_vp_woo_pont_parcel_pdf') && ($order->get_meta('_vp_woo_pont_provider') == 'custom')) {
			if($new_status == str_replace( 'wc-', '', VP_Woo_Pont_Helpers::get_option('custom_delivery_status', '') )) {
				$this->save_tracking_info($order, 4);
			}

			if($new_status == str_replace( 'wc-', '', VP_Woo_Pont_Helpers::get_option('custom_delivered_status', '') )) {
				$this->save_tracking_info($order, 7);
			}
		}
	}

	public function load_preview_template() {
		if(!isset( $_GET['vp_woo_pont_custom_label_preview'] )) {
			return;
		}

		//Check for user role
		if ( !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this action.', 'vp-woo-pont' ) );
		}
	
		//Init mPDF
		require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

		// Create a new mPDF object
		$mpdf = new \Mpdf\Mpdf(['mode' => 'c', 'format' => VP_Woo_Pont_Helpers::get_option('custom_sticker_size', 'A5'), 'allow_charset_conversion' => true]);

		//Setup tempalte data
		$order_id = intval($_GET['vp_woo_pont_custom_label_preview']);
		$order = wc_get_order($order_id);
		if(!$order) wp_die( __( 'Order not found', 'vp-woo-pont' ) );
		$data = VP_Woo_Pont()->labels->prepare_order_data($order);
		$label_data = array(
			'sender' => VP_Woo_Pont_Helpers::get_option('custom_sender', ''),
			'logo' => VP_Woo_Pont_Helpers::get_option('custom_logo', ''),
			'contents' => VP_Woo_Pont()->labels->get_package_contents_label($data, 'custom'),
			'text' => VP_Woo_Pont_Helpers::get_option('custom_text', ''),
			'data' => $data
		);
		$html = wc_get_template_html('order/custom-label.php', $label_data, false, VP_Woo_Pont::$plugin_path . '/templates/');

		//Add the HTML content to the PDF document
		$mpdf->WriteHTML($html);

		//Output the PDF document
		$pdf = $mpdf->Output();

		exit();
	}

}
