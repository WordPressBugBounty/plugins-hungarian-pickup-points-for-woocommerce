<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if($order->get_payment_method() == 'cod'): ?>
	<h3><?php esc_html_e('Payment method', 'vp-woo-pont'); ?></h3>
	<p>
		<?php echo esc_html($order->get_payment_method_title()); ?> - <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
	</p>
<?php endif; ?>

<?php if($order->get_meta('_vp_woo_pont_point_id')): ?>
	<?php
	$provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
	$pickup_point = array(
		'coordinates' => $order->get_meta('_vp_woo_pont_point_coordinates'),
		'name' => $order->get_meta('_vp_woo_pont_point_name'),
		'data' => VP_Woo_Pont()->find_point_info($provider_id, $order->get_meta('_vp_woo_pont_point_id'))
	); ?>
	<h3><?php echo esc_html_x('Pickup point', 'frontend', 'vp-woo-pont'); ?></h3>
	<p>
		<strong><?php echo esc_html($pickup_point['name']); ?></strong>
		<?php if($pickup_point['data'] && isset($pickup_point['data']['zip']) && isset($pickup_point['data']['city']) && isset($pickup_point['data']['addr'])): ?>
			<br><?php echo esc_html($pickup_point['data']['zip']); ?> <?php echo esc_html($pickup_point['data']['city']); ?>, <?php echo esc_html($pickup_point['data']['addr']); ?>
		<?php endif; ?>
		<?php if($pickup_point['data'] && isset($pickup_point['data']['comment'])): ?>
		<br><?php echo esc_html($pickup_point['data']['comment']); ?>
		<?php endif; ?>
	</p>
<?php else: ?>
	<h3><?php esc_html_e('Shipping address', 'vp-woo-pont'); ?></h3>
	<p>
		<?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?>
	</p>
<?php endif; ?>

<h3><?php esc_html_e('Shipment Carrier', 'vp-woo-pont'); ?></h3>
<table>
	<tr>
		<td width="32" style="padding:0">
			<img src="<?php echo esc_url($carrier_logo); ?>" width="32" height="32" alt="<?php echo esc_attr($carrier_name); ?>">
		</td>
		<td>
			<?php echo esc_attr($carrier_name); ?>
		</td>
	</tr>
</table>

<h3><?php esc_html_e('Your order', 'vp-woo-pont'); ?></h3>
<table>
	<?php foreach($order->get_items() as $item): ?>
	<?php if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) continue; ?>
	<tr>
		<td style="padding:0">
			<?php
			$product = $item->get_product();
			$image = '';

			if ( is_object( $product ) ) {
				$image = $product->get_image( array( 100, 100 ) );
			}

			echo wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', $image, $item ) );
			?>
		</td>
		<td>
			<strong><?php echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) ); ?></strong><br>
			<?php echo wp_kses_post( $item->get_quantity() ); ?> x <?php echo wp_kses_post( $order->get_formatted_line_subtotal($item) ); ?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>