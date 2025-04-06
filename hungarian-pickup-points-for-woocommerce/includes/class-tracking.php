<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

if ( ! class_exists( 'VP_Woo_Pont_Tracking', false ) ) :

	class VP_Woo_Pont_Tracking {
		public $supports_tracking_automations = array('gls', 'dpd', 'foxpost', 'packeta', 'posta', 'sameday', 'expressone', 'custom', 'transsped', 'csomagpiac', 'kvikk');

		//Setup triggers
		public function __construct() {

			//Only of order tracking is enabled
			if(VP_Woo_Pont_Helpers::get_option('order_tracking', 'no') == 'yes') {

				//Runs when label created
				add_action( 'vp_woo_pont_label_created', array($this, 'init_scheduled_action'), 10, 3);

				//Create metabox for labels on order edit page
				add_action( 'add_meta_boxes', array( $this, 'add_metabox' ), 10, 2 );

				//Show tracking info in order admin table
				add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_listing_column' ) );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_listing_actions' ), 10, 2 );
				add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_listing_column' ) );
				add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'add_listing_actions' ), 20, 2 );
				add_action( 'admin_footer', array( $this, 'tracking_modal' ) );

				//Create custom tracking link when order created
				add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_order_created' ));

			}

			//Runs when tracking info needs to be updated for an order(scheduled action)
			add_action( 'vp_woo_pont_update_tracking_info', array($this, 'update_tracking_info'));

			//Ajax function to reload and get tracking info
			add_action( 'wp_ajax_vp_woo_pont_update_tracking_info', array( $this, 'ajax_update_tracking_info' ) );
			add_action( 'wp_ajax_vp_woo_pont_get_tracking_info', array( $this, 'ajax_get_tracking_info' ) );

			//Include invoices in emails
			if(VP_Woo_Pont_Helpers::get_option('email_tracking_number')) {
				if(VP_Woo_Pont_Helpers::get_option('email_tracking_number_pos', 'beginning') == 'beginning') {
					add_action('woocommerce_email_before_order_table', array( $this, 'email_attachment'), 10, 4);
					add_action('woocommerce_subscriptions_email_order_details', array( $this, 'email_attachment'), 10, 4);
				} else {
					add_action('woocommerce_email_customer_details', array( $this, 'email_attachment'), 30, 4);
				}
			}

			//Display tracking number in my account
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'woocommerce_get_order_item_totals' ), 10, 3 );

			//Modify the built-in tracking shortcode form to recognize extra URL parameter
			add_filter('do_shortcode_tag', array($this, 'modify_tracking_shortcode'), 10, 2);

			//Display custom tracking template if enabled
			if(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false)) {

				//Display custom tracking template
				add_action('woocommerce_track_order', array($this, 'display_tracking_template'));
				add_action('woocommerce_view_order', array($this, 'display_tracking_template_my_account') );

				//Add nofollow/noindex headers to custom tracking page
				add_filter( 'wp_robots', array($this, 'no_robots') );

			}

			//Custom emails regarding tracking info
			add_filter( 'woocommerce_email_classes', array( $this, 'register_email' ), 90, 1 );

			//Display order info in emails
			add_action( 'vp_woo_pont_tracking_email_order_details', array( $this, 'add_email_order_details' ), 10, 4 );

		}

		//Setup scheduled actions to update tracking number info, only if supported by the provider(and skip custom, because it is based on order status for now)
		public function init_scheduled_action($order, $label, $provider) {
			if(in_array($provider, $this->supports_tracking_automations) && $provider != 'custom') {
				WC()->queue()->schedule_recurring( time()+HOUR_IN_SECONDS*2, apply_filters('vp_woo_pont_tracking_sync_interval', HOUR_IN_SECONDS*2, $order, $provider), 'vp_woo_pont_update_tracking_info', array('order_id' => $order->get_id()), 'vp_woo_pont' );
			}
		}

		//Helper function to get all of the tracking statuses
		public function get_supported_tracking_statuses() {
			$statuses = array();

			foreach ($this->supports_tracking_automations as $provider_id) {
				$statuses[$provider_id] = VP_Woo_Pont()->providers[$provider_id]->package_statuses;
			}

			return apply_filters('vp_woo_pont_tracking_status_codes', $statuses);
		}

		public function cancel_tracking_update($order_id) {
			add_action( 'action_scheduler_after_process_queue', function() use(&$order_id) {
				WC()->queue()->cancel('vp_woo_pont_update_tracking_info', array('order_id' => $order_id), 'vp_woo_pont' );
			});
			return false;
		}

		//Fetch tracking updates from the tracking number
		public function update_tracking_info($order_id) {
			$order = wc_get_order($order_id);

			//If order not found(maybe deleted), cancel and delete scheduled actions too
			//Also if order exists, but the label was deleted, we can cancel the scheculed action
			if(!$order || ($order && !$order->get_meta('_vp_woo_pont_parcel_number'))) {
				return $this->cancel_tracking_update($order_id);
			}

			//If its been 2 weeks, cancel updating the tracking number
			$order_date = $order->get_date_created()->getTimestamp();
			$todays_date = time();
			if($todays_date-$order_date > apply_filters('vp_woo_pont_tracking_info_sync_deadline', WEEK_IN_SECONDS*2)) {
				return $this->cancel_tracking_update($order_id);
			}

			//Skip at night
			$hour = date('H');
			if($hour < 5 || $hour > 22) {
				return false;
			}

			//Get carried id from order
			$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

			//Stop traciking if the latest status is delivered
			$parcel_info = $order->get_meta('_vp_woo_pont_parcel_info');
			if(!empty($parcel_info) && count($parcel_info) > 0) {
				$latest_event = $parcel_info[0];
				$latest_event_class = $this->get_event_status($provider_id, $latest_event);
				if($latest_event_class == 'delivered') {

					//For posta, theres an additional check, because the delivered status is not always the last one, but only if the order is cash on delivery
					if($provider_id == 'posta' && $order->get_payment_method() == 'cod') {

						//Check if the last event is cash on delivery paid
						if($latest_event['event'] == 36) {
							return $this->cancel_tracking_update($order_id);
						}

					} else {
						return $this->cancel_tracking_update($order_id);
					}

				}
			}

			//Otherwise, run the API to get fresh tracking info
			$tracking_info = VP_Woo_Pont()->providers[$provider_id]->get_tracking_info($order);

			//Check for errors
			if(!is_wp_error($tracking_info)) {

				//Get existing tracking info
				$existing_tracking_info = $order->get_meta('_vp_woo_pont_parcel_info');

				//Update order meta
				$order->update_meta_data('_vp_woo_pont_parcel_info', $tracking_info);
				$order->update_meta_data('_vp_woo_pont_parcel_info_time', time());
				$order->save();

				//Check if order status needs to be updated
				if($tracking_info && count($tracking_info) > 0) {
					$this->run_tracking_automation($order, $provider_id, $existing_tracking_info, $tracking_info);
				}

				//Allow plugins to hook in
				do_action('vp_woo_pont_tracking_info_updated', $tracking_info, $order, $provider_id);

			}

			//Just so the scheduled action knows its the end
			return false;
		}

		//This will check if we need to change order status based on the tracking automation settings
		public function run_tracking_automation($order, $provider, $old_tracking_info, $new_tracking_info) {

			//Get all new events
			$new_events = $this->find_new_events($old_tracking_info,$new_tracking_info);

			//Get tracking autoamtions
			$automations = get_option('vp_woo_pont_tracking_automations', array());

			//Fix for kvikk, if providers contains the word kvikk, just use kvikk
			if(strpos($provider, 'kvikk') !== false) {
				$provider = 'kvikk';
			}

			//Loop through automations
			foreach($new_events as $tracking_info) {
				foreach ($automations as $automation) {

					//Check for saved automations
					if(!isset($automation[$provider]) || !is_array($automation[$provider])) {
						continue;
					}

					//If its an automation set for the provider and the event is set as an option
					$event_status = $this->get_event_status($provider, $tracking_info);
					if(in_array($tracking_info['event'], $automation[$provider]) || (in_array('delivered', $automation[$provider]) && $event_status == 'delivered')) {

							//Change order status
							$target_status = $automation['order_status'];
							$tracking_statuses = $this->get_supported_tracking_statuses();

							//Check if we want to change to completed status and skip if the order already has a refunded status
							$order_status = $order->get_status();
							$refunded_statuses = apply_filters('vp_woo_pont_tracking_automation_refunded_statuses', array('refunded', 'cancelled'));
							if($target_status == 'wc-completed' && in_array($order_status, $refunded_statuses)) {
								continue;
							}

							//Change order status otherwise
							$target_status = apply_filters('vp_woo_pont_tracking_automation_target_status', $target_status, $order, $provider, $tracking_info, $automation, $event_status);
							if($target_status) {
								$order->update_status($target_status, sprintf(__( 'Order status updated, because of the following tracking status event: %s', 'vp-woo-pont' ), $tracking_statuses[$provider][$tracking_info['event']]));
								$order->save();
							}

							//Allow plugins to hook in
							do_action('vp_woo_pont_tracking_automation_after_status_change', $order, $provider, $tracking_info, $automation);

					}

				}
			}

			//Also run e-mail automations
			if(apply_filters('vp_woo_pont_trigger_tracking_email_automation_enabled', true, $order, $provider, $new_events)) {
				$this->run_email_automation($order, $provider, $new_events);
			}

		}

		public function run_email_automation($order, $provider, $new_events) {
			$events = array();
			foreach($new_events as $event) {
				$event_status = $this->get_event_status($provider, $event);
				$events[] = $event_status;
			}

			//Remove duplicates
			$events = array_values(array_unique($events));

			//If delivered already, do nothing
			if(in_array('delivered', $events)) {
				return;
			}

			//Get order details
			$is_point_delivery = $order->get_meta('_vp_woo_pont_point_id');
			$emails_sent = $order->get_meta('_vp_woo_pont_tracking_emails_sent');
			if(!$emails_sent) $emails_sent = array();

			//Send delivery e-mail, for home delivery
			if(in_array('delivery', $events) && !$is_point_delivery && !in_array('delivery', $emails_sent)) {
				$this->trigger_tracking_email($order, 'Delivery', 'delivery', $emails_sent);
				return;
			}

			//Send pickup e-mail for point delivery
			if(in_array('delivery', $events) && $is_point_delivery && !in_array('pickup', $emails_sent)) {
				$this->trigger_tracking_email($order, 'Pickup', 'pickup', $emails_sent);
				return;
			}

			//Send shipped email for both
			if(in_array('shipped', $events) && !$emails_sent) {
				$this->trigger_tracking_email($order, 'Shipped', 'shipped', $emails_sent);
				return;
			}

		}

		public function trigger_tracking_email($order, $class, $name, $emails_sent) {
			$email = WC()->mailer()->emails['VP_Woo_Pont_Email_'.$class];
			if($email->is_enabled() && apply_filters('vp_woo_pont_trigger_tracking_email_'.$name, true, $order)) {
				$email_class_name = 'VP_Woo_Pont_Email_'.$class;
				$email = new $email_class_name();
				$email->trigger( $order->get_id() );
				$emails_sent[] = $name;
				$order->update_meta_data('_vp_woo_pont_tracking_emails_sent', $emails_sent);
				$order->add_order_note(sprintf(esc_html__('Tracking e-mail %s sent to the customer.', 'vp-woo-pont'), $email->get_title()));
				$order->save();	
			}
		}

		//Ajax function to update tracking data
		public function ajax_update_tracking_info() {

			//Validate nonce
			check_ajax_referer( 'vp-woo-pont-tracking', 'nonce' );

			//Gather data
			$order_id = intval($_POST['order']);
			$order = wc_get_order($order_id);
			$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

			//Create response object
			$response = array();
			$response['error'] = false;
			$response['messages'] = array();

			//Get tracking info
			$tracking_info = VP_Woo_Pont()->providers[$provider_id]->get_tracking_info($order);
			$parcel_statuses = $this->get_supported_tracking_statuses();

			//Check for errors
			if(is_wp_error($tracking_info)) {
				$response['error'] = true;
				$response['messages'][] = $tracking_info->get_error_message();
				wp_send_json_success($response);
			}

			//Return response
			$response['tracking_info'] = array();
			$response['messages'][] = __('Tracking info updated successfully', 'vp-woo-pont');

			//Get existing tracking info
			$existing_tracking_info = $order->get_meta('_vp_woo_pont_parcel_info');
			$new_tracking_info = $tracking_info;

			//Update order meta
			$order->update_meta_data('_vp_woo_pont_parcel_info', $tracking_info);
			$order->update_meta_data('_vp_woo_pont_parcel_info_time', current_time('timestamp'));
			$order->save();

			//Get label if needed
			if(!empty($tracking_info)) {
				foreach ($tracking_info as $event_id => $event) {
					$label = $event['label'];
					if(!$label) $label = $parcel_statuses[$provider_id][$event['event']];
					$response['tracking_info'][] = array(
						'date' => esc_html( sprintf( __( '%1$s at %2$s', 'vp-woo-pont' ), date_i18n( wc_date_format(), $event['date'] ), date_i18n( wc_time_format(), $event['date'] ) ) ),
						'label' => esc_html__($label, 'vp-woo-pont')
					);
				}
			}

			//Check if order status needs to be updated
			$this->run_tracking_automation($order, $provider_id, $existing_tracking_info, $new_tracking_info);

			//Allow plugins to hook in
			do_action('vp_woo_pont_tracking_info_updated', $tracking_info, $order, $provider_id);

			wp_send_json_success($response);
		}

		//Helper function to get only the new events
		public function find_new_events($old_values, $new_values) {
			if(!$old_values) {
				return $new_values;
			}
			$new_items = array_udiff($new_values, $old_values, function($a, $b) {
				if ($a['date'] === $b['date'] && $a['event'] === $b['event']) {
					return 0;
				}
				return ($a['date'] < $b['date'] || ($a['date'] === $b['date'] && $a['event'] < $b['event'])) ? -1 : 1;
			});
			return $new_items;
		}

		//Ajax function to update tracking data
		public function ajax_get_tracking_info() {

			//Validate nonce
			check_ajax_referer( 'vp-woo-pont-tracking', 'nonce' );

			//Gather data
			$order_id = intval($_POST['order']);
			$order = wc_get_order($order_id);
			$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

			//Create response object
			$response = array();
			$response['error'] = false;
			$response['messages'] = array();

			//Get the parcel info
			$parcel_statuses = $this->get_supported_tracking_statuses();
			$parcel_info = $order->get_meta('_vp_woo_pont_parcel_info');
			$updated_on = $order->get_meta('_vp_woo_pont_parcel_info_time');
			$events = array();

			if(!empty($parcel_info) && count($parcel_info) > 0) {
				foreach ($parcel_info as $event_id => $event) {
					$location = '';
					$label = $event['label'];
					if(!$label) $label = $parcel_statuses[$provider_id][$event['event']];
					if(isset($event['location']) && $event['location']) {
						$location = $event['location'];
					}
					$events[] = array(
						'date' => esc_html( sprintf( __( '%1$s at %2$s', 'vp-woo-pont' ), date_i18n( wc_date_format(), $event['date'] ), date_i18n( wc_time_format(), $event['date'] ) ) ),
						'label' => esc_html__($label, 'vp-woo-pont'),
						'location' => $location
					);
				}
			} else {
				$updated_on = $order->get_date_modified()->getTimestamp();
				$events[] = array(
					'date' => '',
					'label' => esc_html__('Package created. Tracking number:', 'vp-woo-pont').' '.esc_html($order->get_meta('_vp_woo_pont_parcel_number'))
				);
			}

			$tracking_link = $this->get_tracking_link($order);
			$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');
			$response['events'] = $events;
			$response['link'] = $tracking_link;
			$response['tracking_number'] = $parcel_number;
			$response['updated'] = sprintf(esc_html__('%s ago', 'vp-woo-pont'), human_time_diff($updated_on, current_time('timestamp')));

			wp_send_json_success($response);
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
				$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
				if(in_array($provider_id, $this->supports_tracking_automations) && $order->get_meta('_vp_woo_pont_parcel_number')) {
					add_meta_box('vp_woo_pont_metabox_tracking', __('Tracking informations', 'vp-woo-pont'), array( $this, 'render_tracking_metabox_content' ), $screen, 'side');
				}
			}
		}

		//Render metabox content
		public function render_tracking_metabox_content($post_or_order_object) {
			$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
			include( dirname( __FILE__ ) . '/views/html-metabox-tracking.php' );
		}

		//Email attachment
		public function email_attachment($order, $sent_to_admin, $plain_text, $email){
			$order_id = $order->get_id();
			$order = wc_get_order($order_id);
			$emails = VP_Woo_Pont_Helpers::get_option('email_tracking_number', array());
			if(isset($email->id) && !in_array($email->id, $emails)) return;

			if(isset($email->id) && is_a( $order, 'WC_Order' ) && VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false) && $order->get_meta('_vp_woo_pont_tracking_link')) {
				$tracking_url = $this->get_internal_tracking_link($order);
				$tracking_number = $order->get_meta('_vp_woo_pont_parcel_number');
				if(!$tracking_number) {
					$tracking_number = __( 'Shipment tracking', 'vp-woo-pont' );
				}
				$carrier = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
				$tracking_link = '<a target="_blank" href="'.esc_url($tracking_url).'">'.esc_html($tracking_number).'</a>';
				$text = VP_Woo_Pont_Helpers::get_option('email_tracking_number_desc', __('You can track the order by clicking on the tracking number: {tracking_number}', 'vp-woo-pont'));
				$params = array( 'order' => $order, 'tracking_url' => $tracking_url, 'tracking_link' => $tracking_link, 'tracking_number' => $tracking_number, 'tracking_text' => $text );
				if($plain_text) {
					wc_get_template( 'emails/plain/email-vp-woo-pont-section.php', $params, '', VP_Woo_Pont::$plugin_path . '/templates/' );
				} else {
					wc_get_template( 'emails/email-vp-woo-pont-section.php', $params, '', VP_Woo_Pont::$plugin_path . '/templates/' );
				}
				return;
			}

			if(isset($email->id) && is_a( $order, 'WC_Order' ) && VP_Woo_Pont()->labels->is_label_generated($order)) {
				$tracking_url = $this->get_tracking_link($order);
				$tracking_number = $order->get_meta('_vp_woo_pont_parcel_number');
				$tracking_link = '<a target="_blank" href="'.esc_url($tracking_url).'">'.esc_html($tracking_number).'</a>';
				$text = VP_Woo_Pont_Helpers::get_option('email_tracking_number_desc', __('You can track the order by clicking on the tracking number: {tracking_number}', 'vp-woo-pont'));
				$params = array( 'order' => $order, 'tracking_url' => $tracking_url, 'tracking_link' => $tracking_link, 'tracking_number' => $tracking_number, 'tracking_text' => $text );

				if($plain_text) {
					wc_get_template( 'emails/plain/email-vp-woo-pont-section.php', $params, '', VP_Woo_Pont::$plugin_path . '/templates/' );
				} else {
					wc_get_template( 'emails/email-vp-woo-pont-section.php', $params, '', VP_Woo_Pont::$plugin_path . '/templates/' );
				}
				return;
			}
		}

		//Function to get a tracking link URL
		public function get_tracking_link($order) {
			$link = '';

			if(VP_Woo_Pont()->labels->is_label_generated($order)) {
				$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
				$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');

				if(isset(VP_Woo_Pont()->providers[$provider_id])) {
					$link = VP_Woo_Pont()->providers[$provider_id]->get_tracking_link($parcel_number, $order);
				}
			}

			return esc_url($link);
		}

		//Get internal tracking link
		public function get_internal_tracking_link($order) {
			$link = '';

			//If custom tracking page is enabled, we need a different link
			if(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false) && $order->get_meta('_vp_woo_pont_tracking_link')) {
				$link = add_query_arg( array(
					'orderid' => $order->get_id(),
					'x' => $order->get_meta('_vp_woo_pont_tracking_link'),
				), get_permalink(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false)) );
			}

			return esc_url($link);
		}

		//Display tracking number and link in my account / orders
		public function woocommerce_get_order_item_totals($total_rows, $order, $tax_display) {

			//Only on my account / view order
			if(!is_wc_endpoint_url( 'view-order' )) return $total_rows;

			//Check if tracking link exists
			$tracking_link = $this->get_tracking_link($order);
			if($tracking_link) {
				$total_rows['vp_woo_pont'] = array(
					'label' => esc_html__('Tracking number', 'vp-woo-pont'),
					'value' => '<a class="vp-woo-pont-view-order-tracking-number" href="'.$tracking_link.'" target="_blank">'.$order->get_meta('_vp_woo_pont_parcel_number').'</a>'
				);
			}

			return $total_rows;
		}

		//Column on orders page
		public function add_listing_column($columns) {
			$new_columns = array();
			foreach ($columns as $column_name => $column_info ) {
				$new_columns[ $column_name ] = $column_info;
				if ( 'order_total' === $column_name ) {
					$new_columns['vp_woo_pont_tracking'] = __( 'Tracking informations', 'vp-woo-pont' );
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

			if ( 'vp_woo_pont_tracking' === $column ) {

				//Get the parcel info
				$parcel_statuses = $this->get_supported_tracking_statuses();
				$parcel_info = $order->get_meta('_vp_woo_pont_parcel_info');
				$parcel_number = $order->get_meta('_vp_woo_pont_parcel_number');
				$tracking_link = $this->get_tracking_link($order);

				//If theres no parcel number, return empty
				if(!$parcel_number) return '';

				//Get provider
				$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

				//Latest event
				$event = false;
				$label = '';
				$class = '';
				$classes = array();
				$event_type = 'created';
				if(!empty($parcel_info) && count($parcel_info) > 0) {
					$event = $parcel_info[0];
					$class = $this->get_event_status($provider_id, $event);
					foreach($parcel_info as $parcel_info_event) {
						$parcel_info_event_class = $this->get_event_status($provider_id, $parcel_info_event);
						if($parcel_info_event_class == 'delivered') {
							$class = $parcel_info_event_class;
							break;
						}
					}
				}

				if($event) {
					$label = (isset($event['label'])) ? $event['label'] : '';
					if(!$label && isset($parcel_statuses[$provider_id][$event['event']])) $label = $parcel_statuses[$provider_id][$event['event']];
					if(empty($label)) $label = '';
					$event_type = $event['event'];
				}

				if(!$label) {
					$label = esc_html__('Package created. Tracking number:', 'vp-woo-pont').' '.esc_html($parcel_number);
				}


				$updated_on = $order->get_meta('_vp_woo_pont_parcel_info_time');
				$tooltip = wc_sanitize_tooltip( $label );
				$updated_date = '';
				if($updated_on) {
					$updated_date = sprintf( __( '%1$s at %2$s', 'vp-woo-pont' ), date_i18n( wc_date_format(), $updated_on ), date_i18n( wc_time_format(), $updated_on ) );
					$tooltip = wc_sanitize_tooltip( $label . '<br/><small style="display:block">' . sprintf(esc_html__('Refreshed %s ago', 'vp-woo-pont'), human_time_diff($updated_on, current_time('timestamp'))) . '</small>' );
				}
				?>
				<div class="order-status vp-woo-pont-orders-tracking-event tips" data-updated="<?php echo esc_attr($updated_date); ?>" data-tip="<?php echo $tooltip; ?>" data-event-category="<?php echo esc_attr($class); ?>" data-event-type="<?php echo esc_attr($event_type); ?>">
					<a class="vp-woo-pont-orders-tracking-event-external" target="_blank" href="<?php echo $tracking_link; ?>"><i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i></a>
					<a class="vp-woo-pont-orders-tracking-event-label" href="#" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_manage" ); ?>" data-order_id="<?php echo $order->get_id(); ?>"><span><?php echo esc_html__($label, 'vp-woo-pont'); ?></span></a>
				</div>
				<?php do_action('vp_woo_pont_after_order_list_tracking_column', $order, $event, $event_type); ?>
				<?php

			}
		}

		public function tracking_modal() {
			global $typenow;
			global $current_screen;
			if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) || 'woocommerce_page_wc-orders' == $current_screen->id ) {
				include( dirname( __FILE__ ) . '/views/html-modal-tracking.php' );
			}
		}

		//Create a custom hash as a unique tracking ID for customers to check order status without the need to enter order id and e-mail address manually
		public static function on_order_created($order_id) {
			$order = wc_get_order($order_id);
			$order->update_meta_data('_vp_woo_pont_tracking_link', wc_rand_hash() );
			$order->save();
		}

		public function modify_tracking_shortcode($output, $tag) {
			if($tag == 'woocommerce_order_tracking') {

				//If we have a custom parameter set, check it and show tracking details
				$order_id = empty( $_REQUEST['orderid'] ) ? 0 : ltrim( wc_clean( wp_unslash( $_REQUEST['orderid'] ) ), '#' );
				$tracking_link = empty( $_REQUEST['x'] ) ? '' : sanitize_text_field( wp_unslash( $_REQUEST['x'] ) );

				//If we have both, check validity
				if ($order_id && $tracking_link) {
					$order = wc_get_order($order_id);

					//If order exists and the tracking link is the same
					if($order && ($order->get_meta('_vp_woo_pont_tracking_link') == $tracking_link || (is_user_logged_in() && current_user_can( 'manage_options' )))) {

						//Load the default tracking page results
						echo '<div class="woocommerce">';
							do_action( 'woocommerce_track_order', $order->get_id() );
							wc_get_template('order/tracking.php', array(
								'order' => $order,
							));
						echo '</div>';
						return;
					}

				}
			}

			//Return default content
			return $output;
		}

		public function display_tracking_template_my_account($order_id) {
			if(is_wc_endpoint_url( 'view-order' )) {
				$order = wc_get_order($order_id);
				if($order && $order->get_meta('_vp_woo_pont_provider')) {
					$this->display_tracking_template($order_id);
				}
			}
		}

		public function display_tracking_template($order_id) {

			//Get order
			$order = wc_get_order($order_id);

			//Check for tracking link in URL parameter and for logged in user
			$tracking_link = empty( $_REQUEST['x'] ) ? '' : sanitize_text_field( wp_unslash( $_REQUEST['x'] ) );
			$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();

			//Get provider ID, only show tracking page if this is set
			$provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
			$carrier_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

			//If tracking link is the same, we can load a custom template
			//If we don't have a tracking parameter, but the user is logged in, we can also show it
			if($provider_id) {

				//Setup breadcrumbs
				$tracking_steps = array(
					'ordered' => array(
						'id' => 'ordered',
						'label' => __('Ordered', 'vp-woo-pont'),
						'status' => 'active',
						'date' => $order->get_date_created()
					),
					'ready' => array(
						'id' => 'ready',
						'label' => __('Order Ready', 'vp-woo-pont')
					),
					'shipped' => array(
						'id' => 'shipped',
						'label' => __('Shipped', 'vp-woo-pont')
					),
					'delivery' => array(
						'id' => 'delivery',
						'label' => __('Out for delivery', 'vp-woo-pont')
					),
					'delivered' => array(
						'id' => 'delivered',
						'label' => __('Delivered', 'vp-woo-pont')
					),
				);

				//Get carrier name
				$provider_name = VP_Woo_Pont_Helpers::get_provider_name($provider_id, true);

				//Allow plugins to modify
				$args = array(
					'logged_in' => false,
					'order' => $order,
					'provider' => VP_Woo_Pont_Helpers::get_carrier_from_order($order),
					'parcel_statuses' => $this->get_supported_tracking_statuses(),
					'parcel_info' => $order->get_meta('_vp_woo_pont_parcel_info'),
					'parcel_number' => $order->get_meta('_vp_woo_pont_parcel_number'),
					'tracking_url' => $this->get_tracking_link($order),
					'carrier_name' => $provider_name,
					'invoices' => array(),
					'pickup_point' => false,
					'latest_active_step' => 'ordered'
				);

				//Pickup point details
				if($order->get_meta('_vp_woo_pont_point_id')) {
					$args['pickup_point'] = array(
						'coordinates' => $order->get_meta('_vp_woo_pont_point_coordinates'),
						'name' => $order->get_meta('_vp_woo_pont_point_name'),
						'data' => VP_Woo_Pont()->find_point_info($provider_id, $order->get_meta('_vp_woo_pont_point_id'))
					);

					//Change steps if its a pickup point
					$tracking_steps['delivery'] = array(
						'id' => 'pickup',
						'label' => __('Available for pickup', 'vp-woo-pont')
					);
					$tracking_steps['delivered']['label'] = __('Picked up', 'vp-woo-pont');
				}

				//If we have a label, that means ready is active
				if(VP_Woo_Pont()->labels->is_label_generated($order)) {
					$tracking_steps['ready']['status'] = 'active';
					$args['latest_active_step'] = 'ready';
				}

				//If we have tracking info, that means step "ready" is done
				if($order->get_meta('_vp_woo_pont_parcel_info')) {
					$events = $order->get_meta('_vp_woo_pont_parcel_info');
					$first_event = end($events);
					$timestamp = $first_event['date'];
					if($timestamp) {
						$tracking_steps['ready']['status'] = 'active';
						$tracking_steps['ready']['date'] = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );
						$args['latest_active_step'] = 'ready';

						//Loop through the events and see if the rest of the steps are matched
						$steps_to_check = array('shipped', 'delivery', 'delivered');
						$provider = $carrier_id;
						$args['tracking_provider'] = $provider;
						foreach ($steps_to_check as $step_to_check) {
							$event_codes = VP_Woo_Pont()->providers[$provider]->package_statuses_tracking[$step_to_check];
							foreach ($events as $event) {
								if(in_array($event['event'], $event_codes)) {
									$tracking_steps[$step_to_check]['status'] = 'active';
									$event_date = $event['date'];
									$tracking_steps[$step_to_check]['date'] = new WC_DateTime( "@{$event_date}", new DateTimeZone( 'UTC' ) );
									$args['latest_active_step'] = $step_to_check;
								}
							}
						}
					}

					//If we have a blank event(backward compat)
					if(isset($args['parcel_info'][0]) && $args['parcel_info'][0]['event'] == '') {
						$args['parcel_info'] = array();
					}
					
				}

				//Setup steps
				$args['tracking_steps'] = $tracking_steps;

				//Hide sensitive data for logged out users
				if(($show_customer_details || ($tracking_link && $order->get_meta('_vp_woo_pont_tracking_link') == $tracking_link))) {
					$args['logged_in'] = true;
				}

				//Display template
				$args = apply_filters('vp_woo_pont_tracking_page_variables', $args, $order);
				wc_get_template('order/order-tracking.php', $args, false, VP_Woo_Pont::$plugin_path . '/templates/');

			}

		}

		public function no_robots( $robots ) {
			if ( VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false) && is_page(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false) ) ) {
				return wp_robots_no_robots( $robots );
			}
			return $robots;
		}

		private function get_event_status($provider_id, $event) {
			$class_name = '';
			if(isset($event['event'])) {
				$event_codes = VP_Woo_Pont()->providers[$provider_id]->package_statuses_tracking;
				foreach ($event_codes as $key => $codes) {
					if (in_array($event['event'], $codes)) {
						$class_name = $key;
						break; // No need to continue checking once a match is found
					}
				}
			}
			return $class_name;
		}

		public function register_email($emails) {
			if(VP_Woo_Pont_Pro::is_pro_enabled()) {
				require_once 'emails/class-email.php';
				require_once 'emails/class-email-shipped.php';
				require_once 'emails/class-email-pickup.php';
				require_once 'emails/class-email-delivery.php';
				$emails['VP_Woo_Pont_Email_Shipped'] = new VP_Woo_Pont_Email_Shipped();
				$emails['VP_Woo_Pont_Email_Pickup'] = new VP_Woo_Pont_Email_Pickup();
				$emails['VP_Woo_Pont_Email_Delivery'] = new VP_Woo_Pont_Email_Delivery();
			}
			return $emails;	
		}

		public function add_email_order_details($order, $sent_to_admin = false, $plain_text = false, $email = '') {
			$carrier_logo = VP_Woo_Pont_Helpers::get_carrier_logo($order);
			$provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
			$provider_name = VP_Woo_Pont_Helpers::get_provider_name($provider_id, true);
			$email_params = apply_filters('vp_woo_pont_email_order_details_params', array(
				'order'         => $order,
				'sent_to_admin' => $sent_to_admin,
				'plain_text'    => $plain_text,
				'email'         => $email,
				'carrier_logo' => $carrier_logo,
				'carrier_name' => $provider_name
			), $order);

			if ( $plain_text ) {
				wc_get_template('emails/plain/email-vp-woo-pont-order-details.php', $email_params, false, VP_Woo_Pont::$plugin_path . '/templates/');
			} else {
				wc_get_template('emails/email-vp-woo-pont-order-details.php', $email_params, false, VP_Woo_Pont::$plugin_path . '/templates/');

			}
		}

	}

endif;
