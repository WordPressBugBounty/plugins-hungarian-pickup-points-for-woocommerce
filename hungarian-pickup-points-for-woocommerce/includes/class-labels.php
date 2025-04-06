<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Load PDF API
use iio\libmergepdf\Merger;
use iio\libmergepdf\Driver\TcpdiDriver;
use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

if ( ! class_exists( 'VP_Woo_Pont_Labels', false ) ) :

	class VP_Woo_Pont_Labels {
		public $bulk_actions = array();
		public $supported_providers = array(
			'foxpost',
			'packeta',
			'gls',
			'postapont_10',
			'postapont_20',
			'postapont_30',
			'postapont_50',
			'postapont_70',
			'posta',
			'dpd',
			'dpd_alzabox',
			'dpd_parcelshop',
			'sameday',
			'expressone',
			'custom',
			'gls_shop',
			'gls_locker',
			'packeta_shop',
			'packeta_zbox',
			'packeta_mpl_postapont',
			'packeta_mpl_automata',
			'packeta_foxpost',
			'expressone_omv',
			'expressone_alzabox',
			'expressone_packeta',
			'postapont_posta',
			'postapont_coop',
			'postapont_mediamarkt',
			'postapont_automata',
			'postapont_mol',
			'postapont_postapont',
			'transsped',
			'csomagpiac',
			'csomagpiac_sameday',
			'csomagpiac_dpd',
			'csomagpiac_mpl_postapont',
			'csomagpiac_mpl_posta',
			'csomagpiac_mpl_automata',
			'kvikk',
			'kvikk_mpl_postapont',
			'kvikk_mpl_posta',
			'kvikk_mpl_automata',
			'kvikk_packeta_zbox',
			'kvikk_packeta_zpont',
			'kvikk_packeta_foxpost',
			'kvikk_foxpost',
			'kvikk_mpl',
			'kvikk_famafutar',
			'kvikk_gls',
			'kvikk_gls_shop',
			'kvikk_gls_locker',
			'kvikk_dpd',
			'kvikk_dpd_parcelshop',
			'kvikk_dpd_alzabox',
		);
		public $supports_bulk_printing = array();

		public function __construct() {

			$is_pro = VP_Woo_Pont_Pro::is_pro_enabled();

			//Set bulk action values
			add_action('init', function(){
				$this->bulk_actions = array(
					'vp_woo_pont_generate_labels' => __( 'Generate shipping labels', 'vp-woo-pont' ),
					'vp_woo_pont_print_labels' => __( 'Print shipping labels', 'vp-woo-pont' ),
					'vp_woo_pont_download_labels' => __( 'Download shipping labels', 'vp-woo-pont' ),
				);
			});

			//Add bulk action to print labels
			if($is_pro) {
				add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_options'), 20, 1);
				add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_options'), 20, 1 );
				add_action( 'admin_footer', array( $this, 'layout_selector_modal' ) );

				//Add some additional order attributes to the order number column
				add_action('manage_shop_order_posts_custom_column', array( $this, 'order_details_for_bulk_generate'), 10, 2 );
				add_action('woocommerce_shop_order_list_table_custom_column', array( $this, 'order_details_for_bulk_generate'), 10, 2 );

			}

			//Create metabox for labels on order edit page
			add_action( 'add_meta_boxes', array( $this, 'add_metabox' ), 10, 2 );

			//Add label and package number to orders table
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_listing_column' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_listing_actions' ), 20, 2 );
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_listing_column' ) );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'add_listing_actions' ), 20, 2 );
			add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );

			//Ajax function to generate and remove labels
			add_action( 'wp_ajax_vp_woo_pont_generate_label', array( $this, 'ajax_generate_label' ) );
			add_action( 'wp_ajax_vp_woo_pont_update_package_details', array( $this, 'ajax_update_package_details' ) );
			add_action( 'wp_ajax_vp_woo_pont_void_label', array( $this, 'ajax_void_label' ) );
			add_action( 'wp_ajax_vp_woo_pont_generate_quick_label', array( $this, 'ajax_generate_quick_label' ) );

			//Custom email when label generated
			add_filter( 'woocommerce_email_classes', array( $this, 'register_email' ), 90, 1 );
			add_action( 'vp_woo_pont_label_created', array( $this, 'trigger_email' ) );

			//In the free version, we can export label data to import in shipping provider API
			//add_action( 'woocommerce_order_actions_start', array( $this, 'single_order_button' ) );
			//add_action( 'admin_init',array( $this, 'generate_export' ));

		}

		public function add_bulk_options( $actions ) {
			$actions['vp_woo_pont_generate_labels'] = __( 'Generate shipping labels', 'vp-woo-pont' );
			$actions['vp_woo_pont_print_labels'] = __( 'Print shipping labels', 'vp-woo-pont' );
			$actions['vp_woo_pont_download_labels'] = __( 'Download shipping labels', 'vp-woo-pont' );
			return $actions;
		}

		public function layout_selector_modal() {
			global $typenow;
			global $current_screen;
			if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) || 'woocommerce_page_wc-orders' == $current_screen->id ) {
				include( dirname( __FILE__ ) . '/views/html-modal-layout-selector.php' );
				include( dirname( __FILE__ ) . '/views/html-modal-generate.php' );
				include( dirname( __FILE__ ) . '/views/html-modal-packaging.php' );
			}
		}

		public function generate_label($order_id, $provider = false) {

			//Create response object
			$response = array();
			$response['error'] = false;
			$response['messages'] = array();

			//Get order data
			$order = wc_get_order($order_id);

			//If provider not set, find it
			if(!$provider) {
				$provider = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
			}

			//If still not set, check for paramter
			if(isset($_POST['provider'])) {
				$provider = sanitize_text_field($_POST['provider']);
			}

			//Set label data
			$data = $this->prepare_order_data($order);

			//Fix missing provider
			if(!$data['provider']) $data['provider'] = $provider;

			//Allow developers to modify the data
			$data = apply_filters('vp_woo_pont_prepare_order_data_for_label', $data, $order, $provider);

			/*
			//Simulate response for testing
			$random_parcel_number = 'PB71U69851'.rand(10000, 99999);
			$target_status = VP_Woo_Pont_Helpers::get_option('auto_order_status', 'no');
			return array(
				'error' => false,
				'number' => $random_parcel_number,
				'pdf' => 'teszt',
				'order_status' => array(
					'name' => wc_get_order_status_name($target_status),
					'status' => $target_status
				)
			);
			*/

			//If provider contains kvikk, use that
			$api_provider = $provider;
			if(strpos($provider, 'kvikk') !== false) {
				$api_provider = 'kvikk';
			}

			//Check for PRO verson
			$is_localhost = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], ".local") !== false);
			if($api_provider != 'kvikk' && !get_option('_vp_woo_pont_pro_enabled', false) && !$is_localhost) {
				$response['error'] = true;
				$response['messages'][] = __('Upgrade to the PRO version to generate shipping labels and to track shipments.', 'vp-woo-pont');
				return $response;
			}

			//Check if already generated a label
			if($this->is_label_generated($order)) {
				$response['error'] = true;
				$response['messages'][] = __('A shipping label was already generated for this order.', 'vp-woo-pont');

				//So developers can hook in here
				do_action('vp_woo_pont_label_generate_error', $order, 'duplicated_order', $api_provider);

				return $response;
			}

			//Try to generate the label with the provider class
			$label = VP_Woo_Pont()->providers[$api_provider]->create_label($data);

			//Check for errors
			if(is_wp_error($label)) {
				$response['error'] = true;
				$response['messages'][] = $label->get_error_message();

				//Log error message
				$order->add_order_note(sprintf(esc_html__('Unable to create shipping label. Error code: %s', 'vp-woo-pont'), urldecode($label->get_error_code())));

				//So developers can hook in here
				do_action('vp_woo_pont_label_generate_error', $order, $label, $api_provider);

				return $response;
			}

			//Save provider if not saved yet
			if(!$order->get_meta('_vp_woo_pont_provider')) {
				$order->update_meta_data('_vp_woo_pont_provider', $provider);
			}

			//Save MPL closing info
			if(isset($label['mpl'])) {
				$order->update_meta_data('_vp_woo_pont_mpl_closed', 'no');
			}

			//Save closing info for Foxpost, Expressone, DPD and MPL(introduced in v3)
			if(isset($label['needs_closing'])) {
				$order->update_meta_data('_vp_woo_pont_closed', 'no');
			}

			//Check if this was a multi package label
			$parcel_count = 1;
			if(isset($data['options']) && isset($data['options']['package_count']) && $data['options']['package_count'] > 1) {
				$parcel_count = $data['options']['package_count'];
				$order->update_meta_data('_vp_woo_pont_parcel_count', $parcel_count);
			}

			//If all went good, store the PDF file and shipment info
			$order->update_meta_data('_vp_woo_pont_parcel_id', $label['id']);
			$order->update_meta_data('_vp_woo_pont_parcel_pdf', $label['pdf']);
			$order->update_meta_data('_vp_woo_pont_parcel_number', $label['number']);

			//Save Kvikk related info
			if(isset($label['kvikk_accounting'])) {
				$order->update_meta_data('_vp_woo_pont_kvikk_accounting', $label['kvikk_accounting']);
			}

			//Save parcel info
			$order->save();

			//Save order note
			$order->add_order_note(sprintf(esc_html__('Shipping label generated successfully. ID number: %s', 'vp-woo-pont'), $label['number']));

			//Create response
			$response['number'] = $label['number'];
			$response['pdf'] = $this->generate_download_link($order);
			$response['messages'][] = esc_html__('Shipping label generated successfully.','vp-woo-pont');
			$response['parcel_count'] = $parcel_count;

			//So developers can hook in here
			do_action('vp_woo_pont_label_created', $order, $label, $api_provider);

			//Change order status if needed based on settings
			if(!$response['error']) {
				$target_status = VP_Woo_Pont_Helpers::get_option('auto_order_status', 'no');
				if($target_status != 'no') {
					$order->update_status($target_status, __( 'Order status updated, because a shipping label was generated.', 'vp_woo_pont' ));
					$order->save();
					$response['order_status'] = array(
						'name' => wc_get_order_status_name($target_status),
						'status' => $target_status
					);
				}
			}

			//Return response
			return $response;

		}

		public function void_label($order_id, $provider = false) {

			//Create response object
			$response = array();
			$response['error'] = false;
			$response['messages'] = array();

			//Get order data
			$order = wc_get_order($order_id);

			//If provider not set, find it
			if(!$provider) $provider = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

			//Set label data
			$data = $this->prepare_order_data($order);
			$data['parcel_number'] = $order->get_meta('_vp_woo_pont_parcel_number');
			$data['parcel_id'] = $order->get_meta('_vp_woo_pont_parcel_id');
			$data = apply_filters('vp_woo_pont_prepare_order_data_for_void_label', $data, $order, $provider);

			//Try to generate the label with the provider class
			$label = VP_Woo_Pont()->providers[$provider]->void_label($data);

			//Check for errors
			if(is_wp_error($label)) {
				$response['error'] = true;
				$response['messages'][] = $label->get_error_message();

				//Log error message
				$order->add_order_note(sprintf(esc_html__('Unable to void the shipping label. Error code: %s', 'vp-woo-pont'), urldecode($label->get_error_code())));

				return $response;
			}

			//If all went good, store the PDF file and shipment info
			$order->delete_meta_data('_vp_woo_pont_parcel_id');
			$order->delete_meta_data('_vp_woo_pont_parcel_pdf');
			$order->delete_meta_data('_vp_woo_pont_parcel_number');
			$order->delete_meta_data('_vp_woo_pont_parcel_pending');
			$order->delete_meta_data('_vp_woo_pont_parcel_info');
			$order->delete_meta_data('_vp_woo_pont_parcel_count');
			$order->delete_meta_data('_vp_woo_pont_kvikk_accounting');
			$order->save();

			if(isset($label['deleted_locally'])) {

				//Save order note
				$order->add_order_note(sprintf(esc_html__("Shipping label removed from the order only. You still need to remove it in the provider's system. ID number: %s", 'vp-woo-pont'), $data['parcel_id']));

				//Create response
				$response['messages'][] = esc_html__("Shipping label removed from WooCommerce. You still need to remove it in the provider's system.",'vp-woo-pont');

			} else {

				//Save order note
				$order->add_order_note(sprintf(esc_html__('Shipping label removed successfully. ID number: %s', 'vp-woo-pont'), $data['parcel_id']));

				//Create response
				$response['messages'][] = esc_html__('Shipping label removed successfully.','vp-woo-pont');

			}

			//So developers can hook in here
			do_action('vp_woo_pont_label_removed', $order, $data, $provider);

			//Return response
			return $response;

		}

		//Function to download labels in bulk straight from the provider(not all supported yet, set in $supports_bulk_printing)
		public function download_labels($order_ids, $provider) {

			//Create response object
			$response = array();
			$response['error'] = false;
			$response['messages'] = array();

			//Setup data, both for ids and numbers, different data might be required by different providers
			$data = array(
				'parcel_ids' => array(),
				'parcel_numbers' => array()
			);

			//Get package ids and numbers
			foreach ($order_ids as $order_id) {
				$order = wc_get_order($order_id);
				$data['parcel_ids'][] = $order->get_meta('_vp_woo_pont_parcel_id');
				$data['parcel_numbers'][] = $order->get_meta('_vp_woo_pont_parcel_number');
			}

			//Try to download the labels with the provider class
			$labels = VP_Woo_Pont()->providers[$provider]->download_labels($data);

			//Validate
			if(is_wp_error($labels)) {
				$response['error'] = true;
				$response['messages'][] = $labels->get_error_message();
				return $response;
			}

			//Otherwise, return the PDF file created
			return $response['labels'] = $labels['pdf'];

		}

		//Function to create an array that can be used by all providers to generate labels
		public function prepare_order_data($order) {

			//Calculate weight
			$total_weight = 0;
			$total_qty = 0;
			$order_items = $order->get_items();
			$order_total = $order->get_total();
			$total_refunded = $order->get_total_refunded();
			$package_total = $order_total - $total_refunded;
			foreach ( $order_items as $item_id => $product_item ) {
				$product = $product_item->get_product();
				$total_qty = $total_qty + $product_item->get_quantity();
				if($product) {
					$product_weight = $product->get_weight();
					$quantity = $product_item->get_quantity();
					if($product_weight) {
						$total_weight += floatval( $product_weight * $quantity );
					}
				}
			}

			//Compile data required for labels
			$data = array(
				'order_id' => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'reference_number' => $this->get_reference_number($order),
				'cod_reference_number' => $this->get_reference_number($order, 'cod_reference_number'),
				'invoice_number' => $this->get_invoice_number($order),
				'order' => $order,
				'customer' => array(
					'name' => $order->get_formatted_shipping_full_name(),
					'phone' => $this->format_phone_number($order),
					'email' => $order->get_billing_email(),
					'first_name' => $order->get_shipping_first_name(),
					'last_name' => $order->get_shipping_last_name(),
					'company' => $order->get_shipping_company(),
					'name_with_company' => $order->get_formatted_shipping_full_name()
				),
				'package' => array(
					'total' => $package_total,
					'total_rounded' => 5 * round($package_total / 5),
					'weight' => $total_weight,
					'weight_gramm' => VP_Woo_Pont_Helpers::get_package_weight_in_gramms($order),
					'cod' => ($order->get_payment_method() == VP_Woo_Pont_Helpers::get_option('cod_method', 'cod')),
					'currency' => $order->get_currency(),
					'qty' => $total_qty,
					'size' => $this->get_package_size($order),
				),
				'point_id' => $order->get_meta('_vp_woo_pont_point_id'),
				'provider' => $order->get_meta('_vp_woo_pont_provider'),
				'source' => 'orders_table'
			);

			//If empty, try to set it to billing
			if(!$data['customer']['name'] || $data['customer']['name'] == ' ') $data['customer']['name'] = $order->get_formatted_billing_full_name();
			if(!$data['customer']['first_name']) $data['customer']['first_name'] = $order->get_billing_first_name();
			if(!$data['customer']['last_name']) $data['customer']['last_name'] = $order->get_billing_last_name();
			if(!$data['customer']['company']) $data['customer']['company'] = $order->get_billing_company();
			if(!$data['customer']['name_with_company']) $data['customer']['name_with_company'] = $order->get_formatted_billing_full_name();

			if($data['customer']['company']) {
				$data['customer']['name_with_company'] .= ' ('.$data['customer']['company'].')';
			}

			//If custom options set(manual label generate)
			$data['options'] = array();
			if(isset($_POST['package_count'])) {
				$package_count = sanitize_text_field($_POST['package_count']);
				$data['options']['package_count'] = intval($package_count);
			}
			
			if($order->get_meta('_vp_woo_pont_package_count')) {
				$package_count = $order->get_meta('_vp_woo_pont_package_count');
				$data['options']['package_count'] = intval($package_count);
			}

			if(isset($_POST['pickup_date'])) {
				$pickup_date = sanitize_text_field($_POST['pickup_date']);
				$data['options']['pickup_date'] = $pickup_date;
			}

			if(isset($_POST['package_contents'])) {
				$package_contents = sanitize_text_field($_POST['package_contents']);
				$data['options']['package_contents'] = $package_contents;
			}

			if(isset($_POST['package_weight'])) {
				$package_weight = sanitize_text_field($_POST['package_weight']);
				$data['options']['package_weight'] = $package_weight;
			}

			if(isset($_POST['extra_services'])) {
				$extra_services = array_map( 'sanitize_text_field', $_POST['extra_services'] );
				$data['options']['extra_services'] = $extra_services;
			}

			if(isset($_POST['transsped_packaging'])) {
				$data['options']['transsped_packaging'] = array();
				if (isset($_POST['transsped_packaging'])) {
				  foreach ($_POST['transsped_packaging'] as $package_type => $quantity) {
					$package_type = sanitize_text_field($package_type);
					$quantity = absint($quantity);
				
					if ($quantity > 0) {
					  $data['options']['transsped_packaging'][$package_type] = $quantity;
					}
				  }
				}
			}

			//Set if it was generated using the metabox inside order details
			if(isset($_POST['source']) && $_POST['source'] == 'metabox') {
				$data['source'] = 'metabox';
			}

			return apply_filters('vp_woo_pont_prepare_label_data', $data, $order);
		}

		//Create download link for the PDF file
		public function generate_download_link( $order, $absolute = false) {
			if($order) {
				$pdf_name = '';
				$pdf_name = $order->get_meta('_vp_woo_pont_parcel_pdf');
				if($pdf_name) {
					$paths = $this->get_pdf_file_path();
					if($absolute) {
						$pdf_file_url = $paths['basedir'].$pdf_name;
					} else {
						$pdf_file_url = $paths['baseurl'].$pdf_name;
					}
					return $pdf_file_url;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		//Get file path for pdf files
		public static function get_pdf_file_path($provider = 'postapont', $order_id = 0) {
			$upload_dir = wp_upload_dir( null, false );
			$basedir = $upload_dir['basedir'] . '/vp-woo-pont-labels/';
			$baseurl = $upload_dir['baseurl'] . '/vp-woo-pont-labels/';
			$random_file_name = substr(md5(rand()),5);
			$pdf_file_name = implode( '-', array( $provider, $order_id, $random_file_name ) ).'.pdf';
			$file_dir = $basedir;

			//Group by year and month if needed
			if (get_option('uploads_use_yearmonth_folders') ) {
				$time = current_time( 'mysql' );
				$y = substr( $time, 0, 4 );
				$m = substr( $time, 5, 2 );
				$subdir = "/$y/$m";
				$pdf_file_name = $y.'/'.$m.'/'.$pdf_file_name;
				$file_dir = $basedir.$y.'/'.$m.'/';
			}

			return array('name' => $pdf_file_name, 'filedir' => $file_dir, 'path' => $basedir.$pdf_file_name, 'baseurl' => $baseurl, 'basedir' => $basedir);
		}

		//Meta box on order page
		public function add_metabox( $post_type, $post ) {
			if ( class_exists( CustomOrdersTableController::class ) && function_exists( 'wc_get_container' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
				$screen = wc_get_page_screen_id( 'shop-order' );
			} else {
				$screen = 'shop_order';
			}

			add_meta_box('vp_woo_pont_metabox', __('Shipping informations', 'vp-woo-pont'), array( $this, 'render_metabox_content' ), $screen, 'side');
		}

		//Render metabox content
		public function render_metabox_content($post_or_order_object) {
			$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
			if(apply_filters('vp_woo_pont_show_label_metabox', $order->needs_processing(), $order)) {
				include( dirname( __FILE__ ) . '/views/html-metabox.php' );
			} else {
				echo '<p class="vp-woo-pont-metabox-no-shipping">'.__('This order doesn\'t need shipping. Add a shipping line item to generate a label.', 'vp-woo-pont');'</p>';
			}
		}

		//Ajax function to create label
		public function ajax_generate_label() {
			check_ajax_referer( 'vp_woo_pont_manage', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
			}
			$order_id = intval($_POST['order']);
			$response = $this->generate_label($order_id);
			wp_send_json_success($response);
		}

		//Ajax function to create label
		public function ajax_generate_quick_label() {
			check_ajax_referer( 'vp-woo-pont-generate', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
			}
			$order_id = intval($_POST['order']);
			$response = $this->generate_label($order_id);
			wp_send_json_success($response);
		}

		//Ajax function to create label
		public function ajax_void_label() {
			check_ajax_referer( 'vp_woo_pont_manage', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
			}
			$order_id = intval($_POST['order']);
			$response = $this->void_label($order_id);
			wp_send_json_success($response);
		}

		//Ajax function to update package
		public function ajax_update_package_details() {
			check_ajax_referer( 'vp-woo-pont-generate', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
			}
			$order_id = intval($_POST['order']);
			$order = wc_get_order($order_id);

			//For now, this function only updates/stores the package weight
			if(isset($_POST['weight'])) {
				$weight = intval($_POST['weight']);
				$order->update_meta_data('_vp_woo_pont_package_weight', $weight);
				$order->save();
			}

			//Update packaging sizes too
			$packaging = array();
			if(isset($_POST['packaging_name'])) {

				//Get the packaging data
				$packaging = array(
					'name' => sanitize_text_field($_POST['packaging_name']),
					'sku' => sanitize_text_field($_POST['packaging_sku'])
				);

				//If its not a custom size, replace it with the default one
				if($packaging['sku'] != 'custom') {
					$packagings = get_option('vp_woo_pont_packagings');
					foreach ($packagings as $key => $packagings_type) {
						if($packaging['sku'] == $packagings_type['sku']) {
							$packaging = $packagings_type;
						}
					}
				} else {
					$packaging['length'] = intval($_POST['packaging_length']);
					$packaging['width'] = intval($_POST['packaging_width']);
					$packaging['height'] = intval($_POST['packaging_height']);
				}

				$order->update_meta_data('_vp_woo_pont_packaging', $packaging);
				$order->save();
			}

			wp_send_json_success(array('weight' => $weight, 'packaging' => $packaging));
		}

		//Save PDF file
		public static function save_pdf_file($pdf, $pdf_file) {

			//If upload folder doesn't exists, create it with an empty index.html file
			$file = array(
				'base' 		=> $pdf_file['filedir'],
				'file' 		=> 'index.html',
				'content' 	=> ''
			);

			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}

			//Make a GET request and store the pdf file
			file_put_contents($pdf_file['path'], $pdf);

		}

		//Helper function to force correct phone number format for FoxPost API
		public function format_phone_number($order) {
			$number = $order->get_billing_phone();
			$country = $order->get_shipping_country();

			//Fix hungarian format
			if(!$country || $country == 'HU') {
				$number = preg_replace( '/[^0-9]/', '', $number );
				$number = str_replace('+36', '', $number);
				$prefixes = array('+36', '36', '06', '0036');
				foreach ($prefixes as $prefix) {
					if (substr($number, 0, strlen($prefix)) == $prefix && strlen($number) > 7) {
						$number = substr($number, strlen($prefix));
					}
				}
				if($number) {
					$number = '+36'.$number;
				}
			}

			return $number;
		}

		//Check if it was already generated or not
		public function is_label_generated( $order) {
			if(is_int($order)) $order = wc_get_order($order);
			return ($order->get_meta('_vp_woo_pont_parcel_pdf'));
		}

		//Hide columns by default
		public function default_hidden_columns($hidden, $screen) {
			$hide_columns = array('vp_woo_pont', 'vp_woo_pont_shipment');
			if ( isset( $screen->id ) && 'edit-shop_order' === $screen->id ) {
				$hidden = array_merge( $hidden, $hide_columns );
			}
			return $hidden;
		}

		//Column on orders page
		public function add_listing_column($columns) {
			$new_columns = array();
			foreach ($columns as $column_name => $column_info ) {
				$new_columns[ $column_name ] = $column_info;
				if ( 'order_total' === $column_name ) {
					$new_columns['vp_woo_pont_shipment'] = __( 'Shipment', 'vp-woo-pont' );
				}
			}
			return $new_columns;
		}

		//Add icon to order list to show invoice
		public function add_listing_actions( $column, $post_or_order_object ) {
			$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
			if ( ! is_object( $order ) && is_numeric( $order ) ) {
				$order = wc_get_order( absint( $order ) );
			}

			if ( class_exists( CustomOrdersTableController::class ) && function_exists( 'wc_get_container' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
				$screen = wc_get_page_screen_id( 'edit-shop_order' );
			} else {
				$screen = 'edit-shop_order';
			}

			$hidden_columns = get_hidden_columns($screen);
			if ( (in_array('vp_woo_pont_shipment', $hidden_columns) && 'shipping_address' === $column) || $column === 'vp_woo_pont_shipment') {
				if($provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order)) {
					$provider_name = VP_Woo_Pont_Helpers::get_provider_name($provider_id);
					?>
					<div class="vp-woo-pont-order-column" data-provider="<?php echo esc_attr($provider_id); ?>" data-order="<?php echo esc_attr($order->get_id()); ?>">
						<?php if($order->get_meta('shipping_parcel_number')): ?>
							<a target="_blank" href="<?php echo VP_Woo_Pont()->tracking->get_tracking_link($order); ?>" class="vp-woo-pont-order-column-tracking help_tip" data-tip="<?php esc_attr_e('Tracking number', 'vp-woo-pont'); ?>">
								<span><?php echo esc_html($order->get_meta('shipping_parcel_number')); ?></span>
							</a>
						<?php endif; ?>
						<?php if($this->is_label_generated($order)): ?>
							<div class="vp-woo-pont-order-column-provider">
								<a target="_blank" href="<?php echo VP_Woo_Pont()->tracking->get_tracking_link($order); ?>" class="vp-woo-pont-order-column-tracking help_tip" data-tip="<?php esc_attr_e('Tracking number', 'vp-woo-pont'); ?>">
									<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
									<span><?php echo esc_html($order->get_meta('_vp_woo_pont_parcel_number')); ?></span>
								</a>
								<?php if($closed = $order->get_meta('_vp_woo_pont_closed')): ?>
									<?php if($closed == 'no'): ?><em class="vp-woo-pont-shipment-status dashicons dashicons-yes-alt pending help_tip" data-tip="<?php esc_html_e('To be closed', 'vp-woo-pont', 'mpl shipment status'); ?>"></em><?php endif; ?>
									<?php if($closed != 'no'): ?><em class="vp-woo-pont-shipment-status dashicons dashicons-yes-alt closed help_tip" data-tip="<?php esc_html_e('Closed', 'vp-woo-pont', 'mpl shipment status'); ?>"></em><?php endif; ?>
								<?php endif; ?>
							</div>
							<?php if($this->generate_download_link($order)): ?>
								<div class="vp-woo-pont-order-column-printing">
									<a target="_blank" href="<?php echo $this->generate_download_link($order); ?>" class="vp-woo-pont-order-column-pdf">
										<i></i>
										<span><?php esc_html_e('Download', 'vp-woo-pont'); ?></span>
									</a>
									<div class="vp-woo-pont-order-column-print" tabindex="0">
										<div class="vp-woo-pont-order-column-print-button <?php if($order->get_meta('_vp_woo_pont_parcel_count')): ?>multiple_parcels<?php endif; ?>">
											<span class="dashicons dashicons-printer"></span>
											<span class="label"><?php esc_html_e('Print', 'vp-woo-pont'); ?></span>
										</div>
										<div class="vp-woo-pont-order-column-print-layout"></div>
									</div>
								</div>
							<?php endif; ?>
						<?php else: ?>
							<div class="vp-woo-pont-order-column-provider">
								<span class="vp-woo-pont-order-column-label">
									<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
									<span><?php echo esc_html($provider_name); ?></span>
								</span>
							</div>
							<div class="vp-woo-pont-order-column-printing vp-woo-pont-order-column-quick" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_quick_generate" ); ?>">
								<a target="_blank" href="#" class="vp-woo-pont-order-column-pdf" style="display:none;">
									<i></i>
									<span><?php esc_html_e('Download', 'vp-woo-pont'); ?></span>
								</a>
								<?php if(in_array($provider_id, $this->supported_providers)): ?>
								<div class="vp-woo-pont-order-column-print" tabindex="0">
									<div class="vp-woo-pont-order-column-print-button" data-alt-label="<?php esc_html_e('Print', 'vp-woo-pont'); ?>">
										<span class="dashicons dashicons-printer"></span>
										<span class="label"><?php esc_html_e('Generate & print label', 'vp-woo-pont'); ?></span>
									</div>
									<div class="vp-woo-pont-order-column-print-layout"></div>
								</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						
						<?php $weight = VP_Woo_Pont_Helpers::get_package_weight_in_gramms($order); ?>
						<?php $packaging = $this->get_best_box_for_order($order); ?>
						<?php if($order->get_meta('_vp_woo_pont_packaging')): ?>
							<?php $packaging = $order->get_meta('_vp_woo_pont_packaging'); ?>
						<?php endif; ?>
						<?php if($packaging): ?>
							<?php $json_packaging_details = json_encode($packaging); ?>
							<a href="#" class="vp-woo-pont-order-column-packaging" data-weight="<?php echo esc_attr($weight); ?>" data-packaging="<?php echo esc_attr(htmlspecialchars($json_packaging_details, ENT_QUOTES, 'UTF-8')); ?>">
								<span class="dashicons dashicons-archive"></span>
								<span class="vp-woo-pont-order-column-packaging-label">
									<?php if($packaging): ?>
										<?php echo esc_html($packaging['name']); ?>, 
									<?php endif; ?>
									<?php echo esc_html($weight); ?>g
								</span>
							</a>
						<?php endif; ?>

					</div>
				<?php
				}
			}
		}

		//Replace placeholder in shipping label conents string
		public function get_package_contents_label($data, $provider, $limit = false) {

			//Get order
			$order = $data['order'];
			$note = VP_Woo_Pont_Helpers::get_option($provider.'_package_contents', ''); //Backward compatibility, this option was moved to global instead of provider specific
			if(VP_Woo_Pont_Helpers::get_option('package_contents', '')) {
				$note = VP_Woo_Pont_Helpers::get_option('package_contents', '');
			}
			$order_items = $order->get_items();
			$order_items_strings = array();
			$order_items_skus = array();

			//Setup order items
			foreach( $order_items as $order_item ) {
				$order_item_string = $order_item->get_quantity().'x '.$order_item->get_name();
				if($order_item->get_product()) {
					$product = $order_item->get_product();
					if($product->get_sku()) {
						$order_item_string .= ' ('.$product->get_sku().')';
						$order_items_skus[] = $order_item->get_quantity().'x '.$product->get_sku();
					}
				}
				$order_items_strings[] = $order_item_string;
			}

			//Setup replacements
			$note_replacements = apply_filters('vp_woo_pont_'.$provider.'_label_placeholders', array(
				'{order_number}' => $order->get_order_number(),
				'{customer_note}' => $order->get_customer_note(),
				'{order_items}' => implode(', ', $order_items_strings),
				'{products_sku}' => implode(', ', $order_items_skus),
				'{invoice_number}' => $this->get_invoice_number($order)
			), $order, $data);

			//Replace stuff:
			$note = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $note);

			//If custom option set(manual generate)
			if(isset($data['options']) && isset($data['options']['package_contents']) && $data['options']['package_contents'] != '') {
				$note = $data['options']['package_contents'];
			}

			//Remove line breaks
			$note = str_replace(array("\r", "\n"), ' ', $note);

			//Set character limit
			if($limit) {
				$note = mb_substr($note, 0, $limit);
			}

			return $note;
		}

		//Get reference number based on settings
		public function get_reference_number($order, $field = 'label_reference_number') {
			$ref = '';
			$number_type = VP_Woo_Pont_Helpers::get_option($field, 'order_number');
			$order_number = $order->get_order_number();
			$order_id = $order->get_id();
			$invoice_number_szamlazz = $order->get_meta('_wc_szamlazz_invoice');
			$invoice_number_billingo = $order->get_meta('_wc_billingo_plus_invoice_name');

			if($number_type == 'order_number') {
				return $order_number;
			}

			if($number_type == 'order_id') {
				return $order_id;
			}

			//For számlázz.hu
			if($number_type == 'invoice_number' && $invoice_number_szamlazz) {
				return $invoice_number_szamlazz;
			}

			//For woo billingo plus
			if($number_type == 'invoice_number' && $invoice_number_billingo) {
				return $invoice_number_billingo;
			}

			//If we need to check for a custom meta
			if($custom_meta = VP_Woo_Pont_Helpers::get_option($field.'_custom')) {
				return $order->get_meta($custom_meta);
			}

			//If invoice is missing, return the order number as a default
			return $order_number;
		}

		//Get invoice number
		public function get_invoice_number($order) {
			$invoice_number_szamlazz = $order->get_meta('_wc_szamlazz_invoice');
			$invoice_number_billingo = $order->get_meta('_wc_billingo_plus_invoice_name');

			//For számlázz.hu
			if($invoice_number_szamlazz) {
				return $invoice_number_szamlazz;
			}

			//For woo billingo plus
			if($invoice_number_billingo) {
				return $invoice_number_billingo;
			}

			//If invoice is missing, return empty by default
			return '';
		}

		public function order_details_for_bulk_generate($column_name, $post_or_order_object) {
			$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
			if ( ! is_object( $order ) && is_numeric( $order ) ) {
				$order = wc_get_order( absint( $order ) );
			}

			if($column_name == 'order_number') {
				$order_details = array();
				$order_details['order_id'] = $order->get_id();
				$order_details['order_number'] = $order->get_order_number();
				$order_details['customer_name'] = $order->get_formatted_billing_full_name();
				$order_details['provider_id'] = VP_Woo_Pont_Helpers::get_provider_from_order($order);
				$order_details['carrier_id'] = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
				$shipping_address = $order->get_shipping_city().', '.$order->get_shipping_address_1();
				$order_details['shipping_address'] = ($order->get_meta('_vp_woo_pont_point_name')) ? $order->get_meta('_vp_woo_pont_point_name') : $shipping_address;
				$order_details['parcel_number'] = $order->get_meta('_vp_woo_pont_parcel_number');
				$order_details['download_link'] = $this->generate_download_link($order);
				
				//Convert to JSON and add as data attribute
				$json_order_details = json_encode($order_details);
				echo '<span class="vp-woo-pont-order-details" style="display:none;" data-order-details=\'' . htmlspecialchars($json_order_details, ENT_QUOTES, 'UTF-8') . '\'></span>';
			}
		}

		public function register_email($emails) {
			if(VP_Woo_Pont_Pro::is_pro_enabled()) {
				require_once 'emails/class-email-generated.php';
				$emails['VP_Woo_Pont_Email_Label_Generated'] = new VP_Woo_Pont_Email_Label_Generated();
			}
			return $emails;	
		}

		public function trigger_email($order) {
			$email = WC()->mailer()->emails['VP_Woo_Pont_Email_Label_Generated'];
			$email->trigger($order);
		}

		public function single_order_button($order_id) {
			?>
			<li class="wide dpd-big-single-button">
				<label><?php _e('DPD Weblabel','wc-szamlazz'); ?></label> <a href="<?php echo admin_url( "?vp_label_export=1&order_id=$order_id" ); ?>" class="button" target="_blank" alt="" data-tip="<?php _e('DPD Weblabel Export','wc-szamlazz'); ?>"><?php _e('Letöltés','wc-szamlazz'); ?></a>
			</li>
			<?php
		}

		public function generate_export() {
			if(isset($_GET['vp_label_export']) && $_GET['vp_label_export'] == 1 && isset($_GET['order_id'])) {
				
				//Get order data
				$order_id = intval($_GET['order_id']);
				$order = wc_get_order($order_id);

				//If provider not set, find it
				$provider = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
				$provider = 'foxpost'; //TODO: remove this line

				//Set label data
				$data = $this->prepare_order_data($order);
				$data = apply_filters('vp_woo_pont_prepare_order_data_for_label', $data, $order, $provider);

				//Try to get the label with the provider class
				$export = VP_Woo_Pont()->providers[$provider]->export_label($data);

				//Set file name
				$filename = $provider.date('-Y-m-d-H:i').'-'.$order_id;

				//Check separator
				$separator = ';';
				if(isset($export['separator'])) $separator = $export['separator'];

				//Return downloadable CSV file
				header('Content-Type: text/csv; charset=utf-8');
				header('Content-Disposition: attachment; filename='.$filename.'.csv');
				$output = fopen('php://output', 'w');
				foreach($export['data'] as $row) {
					fputcsv($output, $row, $separator);
				}
				fclose($output);
				exit;
			}
		}

		//Get the best box for the order
		public function get_best_box_for_order($order) {

			//Get available box sizes from settings
			$boxes = get_option('vp_woo_pont_packagings');
			if(!$boxes) return null;
			//Get dimensions of all products in the order
			$items = $order->get_items();
			$total_volume = 0;
			$max_length = 0;
			$max_width = 0;
			$max_height = 0;
		
			//Calculate total volume and find the largest product
			foreach ($items as $item) {
				$product = $item->get_product();
				if(!$product) continue;

				$length = $product->get_length();
				$width = $product->get_width();
				$height = $product->get_height();
				$quantity = $item->get_quantity();
				if(!$length || !$width || !$height) continue;

				$total_volume += $length * $width * $height * $quantity;
				$max_length = max($max_length, $length);
				$max_width = max($max_width, $width);
				$max_height = max($max_height, $height);
			}
		
			//Find the smallest box that can fit the order if we have volume
			$best_box = null;
			if($total_volume) {
				foreach ($boxes as $box) {
					if ($box['volume'] >= $total_volume &&
						$box['length'] >= $max_length &&
						$box['width'] >= $max_width &&
						$box['height'] >= $max_height) {
						if (is_null($best_box) || $box['volume'] < $best_box['volume']) {
							$best_box = $box;
						}
					}
				}
			} else {
				//Check if we have a default box
				foreach ($boxes as $box) {
					if($box['default']) {
						$best_box = $box;
					}
				}
			}
		
			return $best_box;
		}

		public function get_package_size($order) {
			$packaging = $this->get_best_box_for_order($order);
			if($order->get_meta('_vp_woo_pont_packaging')) {
				$packaging = $order->get_meta('_vp_woo_pont_packaging');
			}
			
			if($packaging) {
				return array(
					'length' => $packaging['length'],
					'width' => $packaging['width'],
					'height' => $packaging['height']
				);
			} else {
				return array();
			}

		}

	}

endif;
