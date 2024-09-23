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
								WC()->session->set('selected_vp_pont', $point);
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
				if(isset($data['payment_method'])) {
					WC()->session->set('vp_selected_payment_method', $data['payment_method']);
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