<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Webshippy Compatibility
class Vp_Woo_Pont_Woo_Webshippy_Compatibility {

	public static function init() {

		//Add condition for pickup point for order notes
		add_filter( 'http_request_args', array( __CLASS__, 'add_point_data'), 10, 2 );

	}

	public static function add_point_data($parsed_args, $url) {

		//Continue if not webshippy request
		if(strpos($url, 'app.webshippy.com/sync_orders_woocommerce') === false) return $parsed_args;

		//Get body parameters
		$body = json_decode($parsed_args['body'], true);

		//Get order
		$order = wc_get_order($body['order']['order_id']);
		if($order && $order->get_meta('_vp_woo_pont_point_id')) {
			$carrier = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
			$point_id = $order->get_meta('_vp_woo_pont_point_id');
			$provider = '';
			if($carrier == 'foxpost') $provider = 'FoxPost';
			if($carrier == 'gls') $provider = 'GLS CsomagPont';
			if($carrier == 'posta') $provider = 'PostaPont';
			if($carrier == 'packeta') $provider = 'Packeta';
			if($carrier == 'sprinter') $provider = 'Sprinter';
			if($carrier == 'dpd') $provider = 'DPD';

			//Fill wc_selected_pont attribute with some custom data
			$body['order']['wc_selected_pont'] = sprintf("%s %s %s|%s|%s",
				$order->get_shipping_postcode(),
				$order->get_shipping_city(),
				$order->get_shipping_address_1(),
				$provider,
				$point_id
			);

			//Hide shipping company name if it was a point order
			$body['order']['shipping_address_company'] = '';

			//Use the billing name as the shipping name if it was a point order
			$body['order']['shipping_address_name'] = $order->get_formatted_billing_full_name();
			
		}


		//Set body again
		$parsed_args['body'] = json_encode($body);

		return $parsed_args;
	}

}

Vp_Woo_Pont_Woo_Webshippy_Compatibility::init();
