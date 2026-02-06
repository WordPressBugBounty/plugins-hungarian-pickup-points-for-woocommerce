<?php
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;

//Extend Store API
class VP_Woo_Pont_Block_Extend_Store_Endpoint {
	//Stores Rest Extending instance.
	private static $extend;

	//Plugin Identifier, unique to each plugin.
	const IDENTIFIER = 'vp-woo-pont-picker';

	//Bootstraps the class and hooks required data.
	public static function init() {
		self::extend_store();
	}

	//Registers the actual data into each endpoint.
	public static function extend_store() {

		woocommerce_store_api_register_endpoint_data([
			'endpoint'        => CartSchema::IDENTIFIER,
			'namespace' 	  => 'vp-woo-pont-picker',
			'schema_callback' => [ 'VP_Woo_Pont_Block_Extend_Store_Endpoint', 'extend_cart_schema' ],
			'schema_type'     => ARRAY_A,
			'data_callback'   => function () {
				$shipping_costs = false;
				$selected_pont = false;
				if(!is_admin()) {
					$shipping_costs = VP_Woo_Pont_Helpers::calculate_shipping_costs();
					$selected_pont = WC()->session->get( 'selected_vp_pont' );
					$shipping_cost = VP_Woo_Pont_Helpers::get_price_display($shipping_costs);
					$shipping_cost = html_entity_decode(wp_strip_all_tags($shipping_cost));

					//Check for logged in user data
					if(is_user_logged_in()) {
						$customer_id = get_current_user_id();
						$point_info = get_user_meta( $customer_id, '_vp_woo_pont_point_id', true );
						
						//If we don't have a selected pont yet, but usermeta does, select it					
						if($point_info && !$selected_pont) {
							$point_info = explode('|', $point_info);
							$provider = $point_info[0];
							$point_id = $point_info[1];
							
							//Get point data and store it in session
							$point = VP_Woo_Pont()->find_point_info($provider, $point_id);
							if($point) {
								//WC()->session->set('selected_vp_pont', $point);
							}

						}

					}

				}

				return [
					'shipping_costs' => $shipping_costs,
					'selected_pont' => $selected_pont
				];
			},
		]);
		
		woocommerce_store_api_register_update_callback([
			'namespace' => 'vp-woo-pont-picker',
			'callback'  => function( $data ) {

				//Runs when a payment method is selected
				if(isset($data['payment_method'])) {
					WC()->session->set('vp_selected_payment_method', $data['payment_method']);
				}

				//If we need to select a point
				if(isset($data['selected_point'])) {

					//If ID is empty, we clear the selected point
					if(isset($data['selected_point']['reset'])) {
						WC()->session->set('selected_vp_pont', null);
						return;
					}

					//Get submitted data
					$provider = sanitize_text_field($data['selected_point']['provider']);
					$provider = str_replace('_custom', '', $provider); //Remove custom suffix if any
					$id = sanitize_text_field($data['selected_point']['id']);
					$country = sanitize_text_field($data['selected_point']['country']);
					$point = false;

					//Get point data
					$point = VP_Woo_Pont()->find_point_info($provider, $id, $country);

					//Check if we have a point found
					if($point) {

						//Reset shipping cost cache
						$packages = WC()->cart->get_shipping_packages();
						foreach ($packages as $key => $value) {
							$shipping_session = "shipping_for_package_$key";
							unset(WC()->session->$shipping_session);
						}

						//Store it in the checkout session. Use session, because it will remember the selected point if the checkout page is reloaded
						WC()->session->set('selected_vp_pont', $point);

						//Allow plugins to hook in
						do_action('vp_woo_pont_point_selected', $point);

					}

				}
			}
		]);
		
	}

	//Register schema into the endpoint.
	public static function extend_cart_schema() {
        return [
            'shipping_costs'   => [
            	'description' => 'Available providers and shipping costs',
                'type'        => 'object',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
				'optional'    => true,
            ],
            'selected_pont'   => [
            	'description' => 'The selected shipping location',
                'type'        => 'object',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
				'optional'    => true,
            ]
        ];
    }

}