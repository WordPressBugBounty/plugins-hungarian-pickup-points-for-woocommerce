<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce Shipment Tracking Compatibility
class Vp_Woo_Pont_Shipment_Tracking_Compatibility {

	public static function init() {

		//Add custom providers
		add_filter( 'wc_shipment_tracking_get_providers' , array( __CLASS__, 'add_supported_providers') );

		//Save tracking info
		add_action( 'vo_woo_pont_label_created', array( __CLASS__, 'save_tracking_info'), 10, 3);

	}

	public static function add_supported_providers($providers) {
		$provider_classes = VP_Woo_Pont()->providers;
		$supported_providers = VP_Woo_Pont_Helpers::get_external_provider_groups();

		foreach ($supported_providers as $provider_id => $provider) {
			if(isset($provider_classes[$provider_id])) {
				$providers['Hungary'][$provider] = $provider_classes[$provider_id]->get_tracking_link('%1$s');
			}
		}

		return $providers;
	}

	public static function save_tracking_info($order, $label, $provider) {
		if ( function_exists( 'wc_st_add_tracking_number' ) ) {
			wc_st_add_tracking_number( $order->get_id(), $label['number'], $provider );
		}
	}

}

Vp_Woo_Pont_Shipment_Tracking_Compatibility::init();
