<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce Számlázz.hu Compatibility
class Vp_Woo_Pont_Szamlazz_Compatibility {

	public static function init() {

		//Add condition for pickup point for order notes
		add_filter( 'wc_szamlazz_notes_conditions', array( __CLASS__, 'add_condition') );

		//Check notes condition
		add_filter( 'wc_szamlazz_notes_conditions_values', array( __CLASS__, 'set_condition'), 10, 2 );

		//Show provider name on shipping line item description
		add_filter( 'wc_szamlazz_invoice_line_item', array( __CLASS__, 'show_provider_name_on_invoice'), 10, 4);

		//Add tracking number, pickup point name and stuff to note placeholders
		add_filter( 'wc_szamlazz_get_order_note_placeholders', array( __CLASS__, 'add_placeholders'), 10, 2);

		//Add mark as paid automation to tracking
		add_filter( 'vp_woo_pont_tracking_automation_order_statuses', array( __CLASS__, 'add_mark_as_paid_automation'));
		add_filter( 'vp_woo_pont_tracking_automation_target_status', array( __CLASS__, 'change_target_status'));
		add_action( 'vp_woo_pont_tracking_automation_after_status_change', array( __CLASS__, 'mark_as_paid'), 10, 4);

		//Add invoice to Kvikk order details
		add_filter( 'vp_woo_pont_kvikk_order_details', array( __CLASS__, 'add_invoice_to_kvikk_order_details'), 10, 2);

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

	public static function show_provider_name_on_invoice($tetel, $order_item, $order, $szamla) {
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

				//Replace name
				if($item_name) {
					$tetel->megnevezes = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $item_name);
				}

				//Replace description
				if($item_description) {
					$tetel->megjegyzes = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $item_description);
				}
			}

		}
		return $tetel;
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

	public static function add_mark_as_paid_automation($statuses) {
		$statuses['wc-szamlazz-mark-as-paid'] = _x( 'Mark as paid', 'admin', 'vp-woo-pont' );
		return $statuses;
	}

	public static function change_target_status($status) {
		if($status == 'wc-szamlazz-mark-as-paid') {
			return false;
		}
		return $status;
	}

	public static function mark_as_paid($order, $provider, $tracking_info, $automation) {
		if($automation['order_status'] == 'wc-szamlazz-mark-as-paid') {
			WC_Szamlazz()->generate_invoice_complete($order->get_id());
		}
	}

	public static function add_invoice_to_kvikk_order_details($data, $order) {
		$pdf_url = WC_Szamlazz()->generate_download_link($order);
		if($pdf_url) {
			$data['invoice'] = array(
				'number' =>$order->get_meta( '_wc_szamlazz_invoice' ),
				'pdf' => $pdf_url
			);
		}
		return $data;
	}

}

Vp_Woo_Pont_Szamlazz_Compatibility::init();
