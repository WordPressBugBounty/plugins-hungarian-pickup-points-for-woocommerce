<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Yith WooCommerce Order Tracking compatibility
class Vp_Woo_Pont_Yith_Tracking_Compatibility {

	public static function init() {

		//Add custom providers
		add_filter( 'yith_ywot_carrier_list' , array( __CLASS__, 'add_supported_providers') );

		//Save tracking info
		add_action( 'vo_woo_pont_label_created', array( __CLASS__, 'save_tracking_info'), 10, 3);

	}

	public static function add_supported_providers($providers) {
		$provider_classes = VP_Woo_Pont()->providers;
		$supported_providers = VP_Woo_Pont_Helpers::get_external_provider_groups();

		foreach ($supported_providers as $provider_id => $provider) {
			if(isset($provider_classes[$provider_id])) {
				$providers['vp_woo_pont_'.$provider_id] = array(
					'name' => $provider,
					'track_url' => $provider_classes[$provider_id]->get_tracking_link('[TRACK_CODE]'),
				);
			}
		}

		return $providers;
	}

	public static function save_tracking_info($order, $label, $provider) {
		$track_data = new YITH_Tracking_Data( $order );
		$track_data->set(array(
			'ywot_tracking_code' => $label['number'],
			'ywot_carrier_id'    => 'vp_woo_pont_'.$provider,
		));
		$track_data->save();
	}

}

Vp_Woo_Pont_Yith_Tracking_Compatibility::init();
