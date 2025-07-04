<?php

use Automattic\WooCommerce\StoreApi\Exceptions\InvalidCartException;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

//Include the dependencies needed to instantiate the block.
add_action('woocommerce_blocks_loaded', function() {
    require_once __DIR__ . '/pont-picker-block-integration.php';
	add_action(
		'woocommerce_blocks_checkout_block_registration',
		function( $integration_registry ) {
			$integration_registry->register( new VP_Woo_Pont_Block_Integration() );
		}
	);
	
	//Extends the cart schema to include the vat number values
	if(function_exists('woocommerce_store_api_register_endpoint_data')) {
		require_once __DIR__ . '/pont-picker-block-endpoints.php';
		VP_Woo_Pont_Block_Extend_Store_Endpoint::init();
	}

	//Add custom inline CSS on cart and checkout page to fix price display
	add_action( 'wp_enqueue_scripts', function(){

		//Only if the checkout block is used
		if(!class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) || !CartCheckoutUtils::is_checkout_block_default()){
			return;
		}

		if(!is_cart() && !is_checkout()) {
			return;
		}

		//Get shipping costs
		$shipping_cost = VP_Woo_Pont_Helpers::calculate_shipping_costs();
		$min_cost_label = VP_Woo_Pont_Helpers::get_price_display($shipping_cost);
		$min_cost_label = html_entity_decode(wp_strip_all_tags($min_cost_label));
		$local_pickup_method_ids  = LocalPickupUtils::get_local_pickup_method_ids();
		$local_pickup_locations = get_option( 'pickup_location_pickup_locations', [] );

		$custom_css = '
			.wc-block-cart__sidebar span.wc-block-components-radio-control__description[id*="vp_pont"] span,
			#shipping-method .wc-block-checkout__shipping-method-option:nth-child(2) .wc-block-checkout__shipping-method-option-price,
			#shipping-method .wc-block-checkout__shipping-method-option:nth-child(2) .wc-block-checkout__shipping-method-option-price span,
			.wc-block-components-local-pickup-select .wc-block-components-radio-control__secondary-label[id*="vp_pont"] .wc-block-formatted-money-amount,
			.wc-block-components-local-pickup-select .wc-block-components-radio-control__secondary-label[id*="vp_pont"] em {
				font-size:0;
			}
			
			.wc-block-cart__sidebar span.wc-block-components-radio-control__description[id*="vp_pont"]:after,
			#shipping-method .wc-block-checkout__shipping-method-option:nth-child(2) .wc-block-checkout__shipping-method-option-price:after,
			.wc-block-components-local-pickup-select .wc-block-components-radio-control__secondary-label[id*="vp_pont"]:after {
				content: "'.$min_cost_label.'";
			}

			#shipping-method .wc-block-checkout__shipping-method-option:nth-child(2) .wc-block-checkout__shipping-method-option-price:after {
				font-size: .875rem;
			}
			
			.wp-block-woocommerce-cart-order-summary-shipping-block .wc-block-components-totals-item__value:not(.wc-block-formatted-money-amount) strong {
				display: none;
			}

			.wp-block-woocommerce-checkout[data--vp-shipping-method*="vp_pont"][data--vp-selected-point=""] .wp-block-woocommerce-checkout-order-summary-shipping-block .wc-block-components-totals-shipping .wc-block-components-totals-item__value:not(.wc-block-formatted-money-amount) strong {
				display: none;
			}
		';

		//If we only have one local pickup method, we can hide the shipping method selection
		if(count($local_pickup_method_ids) == 2 && count($local_pickup_locations) == 0) {
			$custom_css .= '
				.wc-block-components-local-pickup-rates-control {
					display: none;
				}
			';
		}

		//Add custom CSS
		wp_add_inline_style( 'vp-woo-pont-picker-block', $custom_css );
	});

	//Validate checkout for point selection
	add_action('woocommerce_store_api_checkout_order_processed', function($order){

		//If its the vp_pont shippign method
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$selected_pont = WC()->session->get( 'selected_vp_pont' );
		$chosen_method = $chosen_methods[0];

		//Check if a a vp_pont is selected
		if(strpos($chosen_method, 'vp_pont') !== false && !$selected_pont && WC()->cart->needs_shipping()) {
			$error = new WP_Error('vp_woo_pont_missing_point', apply_filters('vp_woo_pont_required_pont_message', esc_html__( 'Please select a pick-up point or choose a different shipping method.', 'vp-woo-pont')));
			throw new InvalidCartException(
				'woocommerce_cart_error',
				$error,
				409
			);
		}

		//Validate phone number too(for hungarian numbers only)
		$phone_number = $order->get_billing_phone();
		$country = $order->get_billing_country();
		$is_phone_valid = VP_Woo_Pont_Helpers::validate_phone_number($country, $phone_number);
			
		//If it's a hungarian number
		if(!$is_phone_valid) {
			$error = new WP_Error('vp_woo_pont_wrong_phone_number', apply_filters('vp_woo_pont_wrong_phone_number', esc_html__( 'Please enter a valid phone number!', 'vp-woo-pont')));
			throw new InvalidCartException(
				'woocommerce_cart_error',
				$error,
				409
			);
		}

		//Save tracking link
		$order->update_meta_data('_vp_woo_pont_tracking_link', wc_rand_hash() );
		$order->save();
	});

	//Save order meta
    add_action('woocommerce_store_api_checkout_update_order_from_request', function( \WC_Order $order, \WP_REST_Request $request ) {
		$selected_pont = WC()->session->get( 'selected_vp_pont' );
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$selected_pont = WC()->session->get( 'selected_vp_pont' );
		$chosen_method = $chosen_methods[0];

        if(strpos($chosen_method, 'vp_pont') !== false && $selected_pont) {
			VP_Woo_Pont()->update_order_with_selected_point($order, $selected_pont);
            $order->save();

			// Get the customer id
			$customer_id = $order->get_customer_id();

			//Save user meta if the customer was signed in
			if( ! empty($customer_id) && $customer_id != 0) {
				update_user_meta( $customer_id, '_vp_woo_pont_point_id', $selected_pont['provider'].'|'.$selected_pont['id'] );
			}

        } else {
			$provider = VP_Woo_Pont_Helpers::get_paired_provider($order, false);
			if($provider) {
				$order->update_meta_data('_vp_woo_pont_provider', $provider);
				$order->save();
			}
		}
    }, 10, 2);

	//Store pickup point name and location in shipping rate meta, so it shows up in the order details sidebar like the built-in local pickup method
	add_filter('woocommerce_package_rates', function($rates){
		foreach ( $rates as $rate_id => $rate ) {
			if ( 'vp_pont' === $rate->get_method_id() ) {

				//Only save rate meta if the checkout block is used
				if(!class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) || !CartCheckoutUtils::is_checkout_block_default()){
					continue;
				}

				//Get shipping costs
				$shipping_cost = VP_Woo_Pont_Helpers::calculate_shipping_costs();
				$min_cost_label = VP_Woo_Pont_Helpers::get_price_display($shipping_cost);

				//Check if we have a selected pickup point
				$selected_pont = WC()->session->get( 'selected_vp_pont' );
				if($selected_pont) {
					$rate->add_meta_data('pickup_location', $rate->label);
					$rate->label = VP_Woo_Pont_Helpers::get_provider_name($selected_pont['provider'], true);

					if(is_checkout()) {
						//$rate->label = VP_Woo_Pont_Helpers::get_provider_name($selected_pont['provider'], true).', '.$selected_pont['name'].', '.$selected_pont['zip'].' '.$selected_pont['city'].', '.$selected_pont['addr'];
					}
				}

				//And if empty, remove option
				if(empty($shipping_cost)) {
					unset($rates[$rate_id]);
				}

			}
		}
		return $rates;
	});

	//Reset payment method in session
	add_action('woocommerce_checkout_init', function(){
		if ( is_admin() && ! defined( 'DOING_AJAX' ) )
		return;

		if( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || !WC() || !WC()->session)
		return;

		//Get available paymetn methods
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		//Get first payment method and store it
		//Since payment method selection is not persistent, on page load always the first one will be selected
		$first_payment_method = key($available_gateways);
		WC()->session->set('vp_selected_payment_method', $first_payment_method);
		
	});

	//Reset cache on shipping method change
	add_action('woocommerce_order_before_calculate_totals', function($and_taxes, $order){

		//Check if we have data
		if(!WC() || !WC()->session || !WC()->session->get('chosen_shipping_methods') || !WC()->shipping() || !WC()->checkout) {
			return;
		}

		//Only if the checkout block is used
		if(!class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) || !CartCheckoutUtils::is_checkout_block_default()){
			return;
		}

		//Get selected shipping methd
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		//If vp_pont is chosen
		$is_vp_pont_selected = false;
		if($chosen_methods) {
			foreach ($chosen_methods as $chosen_method) {
				if(strpos($chosen_method, 'vp_pont') !== false) {
					$is_vp_pont_selected = true;
				}
			}
		}

		//Regenerate the shipping lines item if vp_pont is selected
		if($is_vp_pont_selected) {
			$order->remove_order_items( 'shipping' );
			WC()->checkout->create_order_shipping_lines( $order, $chosen_methods, WC()->shipping()->get_packages() );
		}

	}, 10, 2);

});