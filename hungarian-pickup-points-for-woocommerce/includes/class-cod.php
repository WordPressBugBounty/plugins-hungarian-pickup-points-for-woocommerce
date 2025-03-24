<?php

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_COD', false ) ) :

	class VP_Woo_Pont_COD {

		public static function init() {

			//Load template based on get parameter
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_cod_fee') );
			add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'hide_cod') );

		}

		public static function hide_cod($available_gateways) {
			if ( is_admin() ) return $available_gateways;
			if(!WC()->session) return $available_gateways;
			if(!class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) || !CartCheckoutUtils::is_checkout_block_default()){
				if ( ! is_checkout() ) return $available_gateways;
			}

			//Get selected shipping methd
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
			$cod_id = VP_Woo_Pont_Helpers::get_option('cod_method', 'cod');

			//If vp_pont is chosen
			$is_vp_pont_selected = false;
			if($chosen_methods) {
				foreach ($chosen_methods as $chosen_method) {
					if(strpos($chosen_method, 'vp_pont') !== false) {
						$is_vp_pont_selected = true;
					}
				}
			}

			//If pont selected and is a match
			$selected_pont = WC()->session->get( 'selected_vp_pont' );
			$disabled_providers = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_cod_disabled', array());
			if($is_vp_pont_selected && $selected_pont && in_array($selected_pont['provider'], $disabled_providers)) {
				if($cod_id && $cod_id != 'none' && isset($available_gateways[$cod_id])) {
					unset( $available_gateways[$cod_id] );
				}
			}

			return $available_gateways;
		}

		public static function add_cod_fee($cart) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) )
				return;

			//Process if selected gateway is COD
			$chosen_gateway = WC()->session->get( 'chosen_payment_method' );

			//Check for block version too
			if(class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) && CartCheckoutUtils::is_checkout_block_default() && WC()->session->get( 'vp_selected_payment_method')){
				$chosen_gateway = WC()->session->get( 'vp_selected_payment_method');
			}
			
			//Check if selected payment method is cod
			$cod_id = VP_Woo_Pont_Helpers::get_option('cod_method', 'cod');
			if ( $chosen_gateway != $cod_id )
				return;

			//Check if COD available
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( ! isset( $available_gateways[ $cod_id ] ) ) {
				return;
			}

			//Get saved fees
			$cod_fees = get_option('vp_woo_pont_cod_fees');
			if(!$cod_fees)
				return;

			//Get cart details
			$cart_details = VP_Woo_Pont_Conditions::get_cart_details('cod_fees');

			//Get selected shipping methd
			$cart_details['shipping_method'] = '';
			$chosen_methods = WC()->session->chosen_shipping_methods;
			if($chosen_methods) {
				$cart_details['shipping_method'] = $chosen_methods[0];
			}

			//Set cod fee
			$calculated_fee = 0;
			foreach ($cod_fees as $cod_fee_id => $cod_fee) {

				//Check for conditions if needed
				if($cod_fee['conditional']) {

					//Loop through each condition and see if its a match
					$condition_is_a_match = VP_Woo_Pont_Conditions::match_conditions($cod_fees, $cod_fee_id, $cart_details);

					//If no match, skip to the next one
					if(!$condition_is_a_match) continue;

				}

				//Get the price
				$calculated_fee = $cod_fee['cost'];

				//If price is a percentage, calculate it based on cart total
				if($cod_fee['type'] == 'percentage') {
					$calculated_fee = $cart_details['cart_total_net'] * ((float)$cod_fee['cost']/100);
				} elseif($cod_fee['type'] == 'mixed') {
					$split = explode('+', $cod_fee['cost']);
					$split = array_map('trim', $split);
					if(count($split) == 2) {
						$calculated_fee = (float)$split[0] + $cart_details['cart_total_net'] * ((float)$split[1]/100);
					} else {
						$calculated_fee = (float)$calculated_fee;
					}
				} else {
					$calculated_fee = (float)$calculated_fee;
				}

			}

			//If we found a fee, add it
			if($calculated_fee) {

				//Allow plugins to customize the fee
				$calculated_fee = apply_filters('vp_woo_pont_cod_fee', $calculated_fee, $cart_details);

				//Set tax class
				$shipping_tax_rate = WC()->cart->get_cart_item_tax_classes_for_shipping();
				$cod_tax_rate = VP_Woo_Pont_Helpers::get_option('cod_tax_class', '');
				if($cod_tax_rate == 'inherit' && $shipping_tax_rate && is_array($shipping_tax_rate) && count($shipping_tax_rate) > 0) {
					$cod_tax_rate = $shipping_tax_rate[0];
				}
				
				//And create the fee
				$cart->add_fee(
					VP_Woo_Pont_Helpers::get_option('cod_fee_name', __('COD Fee', 'vp-woo-pont')),
					$calculated_fee,
					(VP_Woo_Pont_Helpers::get_option('tax_status', 'taxable') == 'taxable'),
					$cod_tax_rate
				);
			}
		}

	}

	VP_Woo_Pont_COD::init();

endif;
