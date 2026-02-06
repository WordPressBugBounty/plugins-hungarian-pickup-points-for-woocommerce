<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce CheckoutWC Compatibility
class Vp_Woo_Pont_CheckoutWC_Compatibility {

	public static function init() {

		//Add condition for pickup point for order notes
		add_filter( 'vp_woo_pont_is_vp_pont_selected_checkout_ui', array( __CLASS__, 'is_selected') );

		//Change the label for the shipping method
		add_filter('cfw_cart_totals_shipping_label', array( __CLASS__, 'change_shipping_label'), 11);
		add_filter('cfw_shipping_free_text', array( __CLASS__, 'change_free_shipping_label'));
		add_action('woocommerce_review_order_before_order_total', array( __CLASS__, 'change_sidebar'), 11);
		add_filter('cfw_get_review_pane_shipping_address', array( __CLASS__, 'change_header_label'), 11);
		add_filter('cfw_ship_to_label', array( __CLASS__, 'change_method_label'), 11);
		add_filter('cfw_event_data', array( __CLASS__, 'change_pickup_label'), 11);
	}

	public static function is_selected($selected) {
		if(isset($_POST['post_data'])) {
			parse_str( wp_unslash( $_POST['post_data'] ), $post_data );
			if(isset($post_data['cfw_delivery_method'])) {
				$selected = $post_data['cfw_delivery_method'] == 'pickup';
				if($selected && isset($post_data['shipping_method']) && is_array($post_data['shipping_method'])) {
					$method = $post_data['shipping_method'][0];
					if(strpos($method, 'vp_pont') === false) {
						$selected = false;
					}
				}
			}
		}
		return $selected;
	}

	public static function change_shipping_label($label) {
		return __('Shipping', 'vp-woo-pont'); //Szállítás
	}

	public static function is_vp_pont_selected() {
		//Get selected shipping methd
		$chosen_methods = WC()->session->chosen_shipping_methods;

		//If vp_pont is chosen
		$is_vp_pont_selected = false;
		if($chosen_methods) {
			foreach ($chosen_methods as $chosen_method) {
				if(strpos($chosen_method, 'vp_pont') !== false) {
					$is_vp_pont_selected = true;
				}
			}
		}
		
		return $is_vp_pont_selected;
	}

	public static function change_free_shipping_label($label) {
		$selected_pont = WC()->session->get( 'selected_vp_pont' );
		if(self::is_vp_pont_selected() && !$selected_pont) {
			return '-';
		}

		return $label;
	}

	public static function change_sidebar() {
		//Get selected pont
		$selected_pont = WC()->session->get( 'selected_vp_pont' );

		if(self::is_vp_pont_selected() && $selected_pont) {
			?>
			<tr class="vp-woo-pont-review-order-checkoutwc">
				<th><?php echo esc_html_x('Pickup point', 'frontend', 'vp-woo-pont'); ?></th>
				<td data-title="<?php echo esc_attr_x('Pickup point', 'frontend', 'vp-woo-pont'); ?>">
					<div class="vp-woo-pont-review-order-selected">
						<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($selected_pont['provider']); ?>"></i>
						<div class="vp-woo-pont-review-order-selected-info">
							<strong><?php echo esc_html($selected_pont['name']); ?>:</strong>
							<span><?php echo esc_html($selected_pont['addr']); ?>, <?php echo esc_html($selected_pont['zip']); ?> <?php echo esc_html($selected_pont['city']); ?></span>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	public static function change_header_label($label) {
		if(self::is_vp_pont_selected()) {
			$label = __('Pickup point shipping', 'vp-woo-pont'); //Csomagpontos szállítás
		}
		return $label;
	}

	public static function change_method_label($label) {
		if(self::is_vp_pont_selected()) {
			$label = __('Shipping', 'vp-woo-pont'); //Szállítás
		}
		return $label;
	}

	public static function change_pickup_label($data) {
		$data['messages']['pickup_label'] = _x( 'Pickup point', 'frontend', 'vp-woo-pont' ); //Csomagpont
		return $data;
	}

}

Vp_Woo_Pont_CheckoutWC_Compatibility::init();
