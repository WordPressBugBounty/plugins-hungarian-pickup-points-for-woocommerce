<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Webshippy Compatibility
class Vp_Woo_Pont_Woo_iLogistics_Compatibility {

	public static function init() {

		//Add condition for pickup point for order notes
		add_filter( 'http_request_args', array( __CLASS__, 'add_point_data'), 10, 2 );

	}

	public static function add_point_data($parsed_args, $url) {

		//Continue if not ilogistic request
		if(strpos($url, 'https://api.ilogistic.eu/orders/order') === false) return $parsed_args;
		if($parsed_args['method'] != 'POST' && $parsed_args['method'] != 'PATCH') return $parsed_args;

		//Get body parameters
		$body = json_decode($parsed_args['body'], true);

		//Get order
		$order = wc_get_order($body['foreignId']);
		if($order && $order->get_meta('_vp_woo_pont_point_id') && $order->get_meta('_vp_woo_pont_provider')) {
			$carrier = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
			$point_id = $order->get_meta('_vp_woo_pont_point_id');
			$provider = '';
			if($carrier == 'foxpost') $provider = 'Foxpost';
			if($carrier == 'gls') $provider = 'GLS';
			if($carrier == 'packeta') $provider = 'Packeta';
			if($provider) {
				$body['delivery']['company'] = $provider;
			}
			$body['delivery']['name'] = $order->get_formatted_billing_full_name();
		} else {
			return $parsed_args;
		}

		//Set body again
		$parsed_args['body'] = json_encode($body);

		return $parsed_args;
	}

}

Vp_Woo_Pont_Woo_iLogistics_Compatibility::init();
