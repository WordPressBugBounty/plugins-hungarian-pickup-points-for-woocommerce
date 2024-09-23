<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce Woo Billingo Plus Compatibility
class Vp_Woo_Pont_Woo_Billingo_Plus_Compatibility {

	public static function init() {

		//Add condition for pickup point for order notes
		add_filter( 'wc_billingo_plus_notes_conditions', array( __CLASS__, 'add_condition') );

		//Check notes condition
		add_filter( 'wc_billingo_plus_notes_conditions_values', array( __CLASS__, 'set_condition'), 10, 2 );

		//Show provider name on shipping line item description
		add_filter( 'wc_billingo_plus_invoice_line_item', array( __CLASS__, 'show_provider_name_on_invoice'), 10, 4);

		//Add tracking number, pickup point name and stuff to note placeholders
		add_filter( 'wc_billingo_plus_get_order_note_placeholders', array( __CLASS__, 'add_placeholders'), 10, 2);

	}

	public static function add_condition($conditions) {
		$conditions['vp_woo_pont'] = array(
			'label' => _x( 'Pickup point', 'admin', 'vp-woo-pont' ),
			'options' => VP_Woo_Pont_Helpers::get_supported_providers()
		);
		return $conditions;
	}

	public static function set_condition($order_details, $order) {
		$order_details['vp_woo_pont'] = $order->get_meta('_vp_woo_pont_point_name');
		return $order_details;
	}

	public static function show_provider_name_on_invoice($product_item, $order_item, $order, $szamla) {
		if(is_a($order_item, 'WC_Order_Item_Shipping')) {

			//Check if order has a point provider
			if($order->get_meta('_vp_woo_pont_provider') && $order->get_meta('_vp_woo_pont_point_name')) {
				$item_name = VP_Woo_Pont_Helpers::get_option('name_on_invoice', false);
				$item_description = VP_Woo_Pont_Helpers::get_option('note_on_invoice', '{provider}, {point}');
				$provider_id = $order->get_meta('_vp_woo_pont_provider');
				$providers = VP_Woo_Pont_Helpers::get_supported_providers();

				//Setup replacements
				$note_replacements = array(
					'{provider}' => $providers[$provider_id],
					'{point}' => $order->get_meta('_vp_woo_pont_point_name')
				);

				if($item_name) {
					$product_item['name'] = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $item_name);
				}

				if($item_description) {
					$product_item['comment'] = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $item_description);
				}
			}

		}
		return $product_item;
	}

	public static function add_placeholders($placeholders, $order) {
		$placeholders['{vp_woo_pont_provider}'] = '';
		$placeholders['{vp_woo_pont_point_name}'] = '';
		$placeholders['{vp_woo_pont_tracking_number}'] = '';

		if($provider_id = $order->get_meta('_vp_woo_pont_provider')) {
			$provider_name = VP_Woo_Pont_Helpers::get_provider_name($provider_id, true);
			$placeholders['{vp_woo_pont_provider}'] = $provider_name;
		}

		if($pont_name = $order->get_meta('_vp_woo_pont_point_name')) {
			$placeholders['{vp_woo_pont_point_name}'] = $pont_name;
		}

		if($parcel_number = $order->get_meta('_vp_woo_pont_parcel_number')) {
			$placeholders['{vp_woo_pont_tracking_number}'] = $parcel_number;
		}

		return $placeholders;
	}

}

Vp_Woo_Pont_Woo_Billingo_Plus_Compatibility::init();
