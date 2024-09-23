<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Convert shippings costs so it works as a data attribute
$shipping_costs_json = wp_json_encode( $shipping_costs );
$shipping_costs_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $shipping_costs_json ) : _wp_specialchars( $shipping_costs_json, ENT_QUOTES, 'UTF-8', true );
$button_label = __('Select a pick-up point', 'vp-woo-pont');
if(get_option('vp_woo_pont_custom_button_label', '') != '') {
	$button_label = get_option('vp_woo_pont_custom_button_label');
}

//Convert notes to attributes
$notes_json = wp_json_encode( $notes );
$notes_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $notes_json ) : _wp_specialchars( $notes_json, ENT_QUOTES, 'UTF-8', true );

?>
<?php if($is_vp_pont_selected): ?>
<tr class="vp-woo-pont-review-order">
	<th><?php echo esc_html_x('Pickup point', 'frontend', 'vp-woo-pont'); ?></th>
	<td data-title="<?php echo esc_attr_x('Pickup point', 'frontend', 'vp-woo-pont'); ?>">
		<?php if(!$selected_vp_pont || empty($shipping_cost)): ?>
			<a href="#" id="vp-woo-pont-show-map" data-shipping-costs="<?php echo $shipping_costs_attr; ?>" data-notes="<?php echo $notes_attr; ?>"><?php echo esc_html($button_label); ?></a>
		<?php else: ?>
			<div class="vp-woo-pont-review-order-selected">
				<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($selected_vp_pont['provider']); ?>"></i>
				<div class="vp-woo-pont-review-order-selected-info">
					<strong class="vp-woo-pont-review-order-selected-provider"><?php echo esc_html($selected_vp_pont['name']); ?>:</strong>
					<?php if ( WC()->cart->display_prices_including_tax() ): ?>
						<strong class="vp-woo-pont-review-order-selected-cost"><?php echo $shipping_cost['formatted_gross']; ?></strong><br>
					<?php else: ?>
						<strong class="vp-woo-pont-review-order-selected-cost"><?php echo $shipping_cost['formatted_net']; ?></strong><br>
					<?php endif; ?>
					<span class="vp-woo-pont-review-order-selected-address"><?php echo esc_html($selected_vp_pont['addr']); ?>, <?php echo esc_html($selected_vp_pont['zip']); ?> <?php echo esc_html($selected_vp_pont['city']); ?></span> - <a href="#" id="vp-woo-pont-show-map" data-shipping-costs="<?php echo $shipping_costs_attr; ?>" data-notes="<?php echo $notes_attr; ?>"><?php esc_html_e('Modify', 'vp-woo-pont'); ?></a>
				</div>
			</div>
		<?php endif; ?>
	</td>
</tr>
<?php endif; ?>
