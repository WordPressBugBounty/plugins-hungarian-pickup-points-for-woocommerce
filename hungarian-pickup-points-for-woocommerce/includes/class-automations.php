<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Automations', false ) ) :

	class VP_Woo_Pont_Automations {

		//Setup triggers
		public static function init() {

			//When order created(priority set to 12, so it will run after the invoice is generated)
			add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_order_created' ), 12, 3 );

			//On successful payment
			add_action( 'woocommerce_payment_complete', array( __CLASS__, 'on_payment_complete' ), 12 );

			add_action('init', function(){

				//On status change
				$statuses = self::get_order_statuses();
				foreach ($statuses as $status => $label) {
					$status = str_replace( 'wc-', '', $status );
					add_action( 'woocommerce_order_status_'.$status, function($order_id) use ($status) {
						self::on_status_change($order_id, $status);
					});
				}

			});

		}

		//Get order statues
		public static function get_order_statuses() {
			if(function_exists('wc_order_status_manager_get_order_status_posts')) {
				$filtered_statuses = array();
				$custom_statuses = wc_order_status_manager_get_order_status_posts();
				foreach ($custom_statuses as $status ) {
					$filtered_statuses[ 'wc-' . $status->post_name ] = $status->post_title;
				}
				return $filtered_statuses;
			} else {
				$statues = wc_get_order_statuses();
				if(VP_Woo_Pont_Helpers::get_option('custom_order_statues', '') != '') {
					$custom_statuses = VP_Woo_Pont_Helpers::get_option('custom_order_statues', '');
					$custom_statuses = explode(',', $custom_statuses); //Split at commas
					$custom_statuses = array_map('trim', $custom_statuses); //Remove whitespace

					foreach ($custom_statuses as $custom_status) {
						if(!isset($statues[$custom_status])) {
							$statues[$custom_status] = $custom_status;
						}
					}
				}

				return apply_filters('vp_woo_pont_get_order_statuses', $statues);
			}
		}

		public static function on_order_created($order_id, $posted_data, $order) {
			$automations = self::find_automations($order_id, 'order_created');
		}

		public static function on_payment_complete( $order_id ) {
			$automations = self::find_automations($order_id, 'payment_complete');

		}

		public static function on_status_change( $order_id, $new_status ) {
			$automations = self::find_automations($order_id, $new_status);
		}

		public static function find_automations($order_id, $trigger) {

			//Get main data
			$order = wc_get_order($order_id);
			$automations = get_option('vp_woo_pont_automations', array());
			$order_details = VP_Woo_Pont_Conditions::get_order_details($order, 'automations');

			//We will return the matched automations at the end
			$final_automations = array();

			//Loop through each automation
			foreach ($automations as $automation_id => $automation) {

				//Check if trigger is a match. If not, just skip
				if(str_replace( 'wc-', '', $automation['trigger'] ) != str_replace( 'wc-', '', $trigger )) {
					continue;
				}

				//If this is based on a condition
				if($automation['conditional']) {

					//Compare conditions with order details and see if we have a match
					$automation_is_a_match = VP_Woo_Pont_Conditions::match_conditions($automations, $automation_id, $order_details);

					//If its not a match, continue to next not
					if(!$automation_is_a_match) continue;

					//If its a match, add to found automations
					$final_automations[] = $automation;

				} else {
					$final_automations[] = $automation;
				}

			}

			//If we found some automations, try to generate documents
			if(count($final_automations) > 0) {

				//Get provider saved
				$provider = VP_Woo_Pont_Helpers::get_provider_from_order($order);

				//Loop through documents(usually it will be only one, but who knows)
				if($provider) {
					self::run_automations($order_id, $final_automations);
				}

			}

			return $final_automations;
		}

		public static function run_automations($order_id, $automations) {

			//Get data
			$order = wc_get_order($order_id);

			//If generate in the background
			$deferred = true;

			//Don't create deferred if we are in an admin page and only mark one order completed
			if(is_admin() && isset( $_GET['action']) && $_GET['action'] == 'woocommerce_mark_order_status') {
				$deferred = false;
			}

			//Don't defer if we are just changing one or two order status using bulk actions
			if(is_admin() && isset($_GET['_wp_http_referer']) && isset($_GET['post']) && count($_GET['post']) < 3) {
				$deferred = false;
			}

			//Don't defer if we are just changing one or two order status using bulk actions
			if(is_admin() && isset($_GET['id']) && $_GET['id'] == $order_id) {
				$deferred = false;
			}

			//Loop through automations
			foreach ($automations as $automation) {

				//Check if it was already generated
				$generated = ($order->get_meta('_vp_woo_pont_parcel_pdf'));

				//On order created, we will always generate the document
				if($automation['trigger'] == 'order_created') {
					$deferred = false;
				}

				//Skip, if already generated
				if($generated) continue;

				//Find the carrier
				$provider = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

				//If we are still here, we can generate the actual document
				if($deferred) {
					WC()->queue()->add( 'vp_woo_pont_generate_label_async', array('order_id' => $order_id, 'provider' => $provider), 'vp-woo-pont' );
				} else {
					$return_info = VP_Woo_Pont()->labels->generate_label($order_id, $provider);
				}
			}
		}
	}

	VP_Woo_Pont_Automations::init();

endif;
