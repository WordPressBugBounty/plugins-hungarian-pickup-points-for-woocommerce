<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Shipments', false ) ) :

	class VP_Woo_Pont_Shipments {
		public static function init() {

			//Ajax function to handle shipment closing
			add_action( 'wp_ajax_vp_woo_pont_close_shipments', array( __CLASS__, 'ajax_close_shipments' ) );
			add_action( 'wp_ajax_vp_woo_pont_close_orders', array( __CLASS__, 'ajax_close_orders' ) );
			add_action( 'wp_ajax_vp_woo_pont_undo_shipment', array( __CLASS__, 'ajax_undo_shipment' ) );

			//Admin menu
			add_action('admin_menu', array( __CLASS__, 'create_menu' ));

			//Custom parameter for wc_orders
			add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( __CLASS__, 'handle_custom_query_var' ), 10, 2 );

		}

		//Ajax function to close shipments
		public static function ajax_close_shipments() {
			global $wpdb;

			//Security check
			check_ajax_referer( 'vp-woo-pont-close-shipments', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'vp-woo-pont' ) );
			}

			//Get selected packages
			$packages = array();
			$orders = array();
			if(isset($_POST['packages'])) {
				foreach ($_POST['packages'] as $package) {
					$packages[] = sanitize_text_field($package);
				}
			}

			if(isset($_POST['orders'])) {
				foreach ($_POST['orders'] as $order_id) {
					$orders[] = intval($order_id);
				}
			}

			//Return error if we don't have packages set
			if(count($packages) == 0) {
				wp_send_json_error(array('error' => true, 'message' => esc_html__('No packages selected.', 'vp-woo-pont')));
			}

			//Create response object
			$response = array();
			$response['error'] = false;
			$response['message'] = '';

			//Get provider
			$provider = sanitize_text_field($_POST['provider']);

			//Run closing
			$results = VP_Woo_Pont()->providers[$provider]->close_shipments($packages, $orders);

			//Check for errors
			if(is_wp_error($results)) {
				$response['error'] = true;
				$response['message'] = $results->get_error_message();
				wp_send_json_success($response);
			}

			//Save stuff to custom database
			$mpl_shipment = array(
				'packages' => json_encode($results['shipments']),
				'orders' => json_encode($results['orders']),
				'pdf' => json_encode($results['pdf']),
				'time' => current_time( 'mysql' ),
				'carrier' => $provider
			);

			//Save to db
			$table_name = $wpdb->prefix . 'vp_woo_pont_mpl_shipments';
			$wpdb->insert($table_name, $mpl_shipment);
			$shipment_id = $wpdb->insert_id;

			//Update orders
			foreach ($results['orders'] as $order_id) {
				$order = wc_get_order($order_id);
				if($order) {
					$order->update_meta_data('_vp_woo_pont_closed', $shipment_id);
					$order->add_order_note(sprintf(esc_html__('Shipment closed. Generated delivery note ID: %s', 'vp-woo-pont'), $shipment_id));
					$order->save();
				}
			}

			//Setup response
			$paths = VP_Woo_Pont()->labels->get_pdf_file_path();
			$response = array(
				'shipment_id' => $shipment_id,
				'processed' => $results['orders'],
				'documents' => $results['pdf'],
				'download_path' => $paths['baseurl']
			);

			//Check for failed shipments
			if(isset($results['failed']) && count($results['failed']) > 0) {
				$response['message'] = esc_html__('Some shipments failed to close. Please check the list below.', 'vp-woo-pont');
				$response['failed'] = $results['failed'];
				if(isset($results['errors'])) {
					$response['errors'] = $results['errors'];
				}
			} else {
				$response['message'] = esc_html__('All shipments closed successfully.', 'vp-woo-pont');
			}

			//Plugins can add their own custom messages
			do_action('vp_woo_pont_after_close_shipments', $provider, $results, $response);

			//Success
			wp_send_json_success($response);

		}

		//Ajax function to close shipments
		public static function ajax_close_orders() {

			//Security check
			check_ajax_referer( 'vp-woo-pont-close-shipments', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'vp-woo-pont' ) );
			}

			//Get selected packages
			$selected = array();
			if(isset($_POST['orders'])) {
				foreach ($_POST['orders'] as $order_id) {
					$order_id = sanitize_text_field($order_id);
					$order = wc_get_order($order_id);
					$order->update_meta_data('_vp_woo_pont_closed', 'yes');
					if($_POST['provider'] == 'posta') {
						$order->update_meta_data('_vp_woo_pont_mpl_closed', 'yes');
					}
					$order->save();
				}
			}

			//Success
			wp_send_json_success();

		}

		//Ajax function to close shipments
		public static function ajax_undo_shipment() {

			//Security check
			check_ajax_referer( 'vp_woo_pont_manage', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'vp-woo-pont' ) );
			}

			//Get selected packages
			$order_id = intval($_POST['order']);
			$order = wc_get_order($order_id);
			if($order) {
				$order->update_meta_data('_vp_woo_pont_closed', 'no');
				$order->save();
				$response['error'] = false;
				$response['messages'] = array(esc_html__('Successfully marked as not shipped.','vp-woo-pont'));
			} else {
				$response['error'] = true;
				$response['messages'] = array(esc_html__('Order not found.','vp-woo-pont'));
			}

			//Success
			wp_send_json_success($response);

		}

		//Create submenu in WooCommerce
		public static function create_menu() {
			if(VP_Woo_Pont_Helpers::is_provider_configured('posta') || VP_Woo_Pont_Helpers::is_provider_configured('foxpost') || VP_Woo_Pont_Helpers::is_provider_configured('dpd') || VP_Woo_Pont_Helpers::is_provider_configured('expressone') || VP_Woo_Pont_Helpers::is_provider_configured('transsped') || VP_Woo_Pont_Helpers::is_provider_configured('kvikk')) {
				$menu_title = __('Shipments', 'vp-woo-pont');
				if(VP_Woo_Pont_Helpers::is_provider_configured('kvikk')) {
					$menu_title = __( 'Shipments', 'vp-woo-pont' );
				}
				$hook = add_submenu_page( 'woocommerce', __('Shipments', 'vp-woo-pont'), $menu_title, 'edit_shop_orders', 'vp-woo-pont-shipments', array( __CLASS__, 'generate_page_content' ) );
			}
		}

		//Render submenu content with a wp_list_table class
		public static function generate_page_content() {

			// Include WP_List_Table class.
			if ( ! class_exists( 'WP_List_Table' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
			}

			// Include the Form_Locations_Table class.
			if ( ! class_exists( 'VP_Woo_Pont_Shipments_Table' ) ) {
				require_once( plugin_dir_path( __FILE__ ) . 'class-shipments-table.php' );
			}

			?>
			<?php require_once plugin_dir_path( __FILE__ ) . 'views/html-admin-shipments.php'; ?>
			<?php
		}

		//This is a bit complicated due to provide backward compatibiltiy
		public static function handle_custom_query_var($query, $query_vars) {
			if ( ! empty( $query_vars['vp_woo_pont_shipments'] ) ) {
				$meta_query = array(
					'relation' => 'AND',
					array(
						'key'     => '_vp_woo_pont_closed',
						'value'   => esc_attr($query_vars['vp_woo_pont_shipments'][1]),
					),
					array(
						'key'     => '_vp_woo_pont_provider',
						'value'   => $query_vars['vp_woo_pont_shipments'][0],
						'compare' => 'LIKE',
					),
				);

				$query['meta_query'] = $meta_query;
			}

			return $query;
		}

	}

	VP_Woo_Pont_Shipments::init();

endif;
