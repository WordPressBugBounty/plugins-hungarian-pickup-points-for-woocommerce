<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vp_Woo_Pont_Extra_Fees_Compatibility {
    
	public static function init() {

		//Add custom provider filters
		add_filter( 'vp_woo_extra_fees_conditions' , array( __CLASS__, 'add_provider_condition') );
		add_filter( 'vp_woo_extra_fees_conditions_values' , array( __CLASS__, 'add_provider_condition_values') );

	}

	public static function add_provider_condition($conditions) {
        $pont_conditions = VP_Woo_Pont_Conditions::get_conditions('notes');
        $providers = $pont_conditions['provider']['options'];
        $conditions['vp_woo_pont'] = array(
            "label" => __('Pickup point method', 'vp-woo-pont'),
            "options" => $providers
        );

		return $conditions;
	}

	public static function add_provider_condition_values($values) {
		if(WC()->session->get( 'chosen_shipping_methods' )) {
            $selected_pont = WC()->session->get( 'selected_vp_pont' );
            $method = WC()->session->get( 'chosen_shipping_methods' )[0];
            if (strpos($method, 'vp_pont') !== false && $selected_pont) {
				$values['vp_woo_pont'] = $selected_pont['provider'];
			} else {
				$values['vp_woo_pont'] = '';
			}
		}
		return $values;
	}

}

Vp_Woo_Pont_Extra_Fees_Compatibility::init();
