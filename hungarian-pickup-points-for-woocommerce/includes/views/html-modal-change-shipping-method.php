<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shipping_methods = VP_Woo_Pont_Helpers::get_available_shipping_methods(false);
$currency = $order->get_currency();

//Get current shipping method and cost from order
$current_shipping_method = '';
$current_shipping_cost = '';
$order_shipping_methods = $order->get_shipping_methods();
if ( !empty($order_shipping_methods) ) {
	$first_shipping = reset($order_shipping_methods);
	$current_shipping_method = $first_shipping->get_method_id() . ':' . $first_shipping->get_instance_id();
	$current_shipping_cost = $order->get_shipping_total();
	if ( floatval($order->get_shipping_tax()) > 0 ) {
		$current_shipping_cost = floatval($current_shipping_cost) + floatval($order->get_shipping_tax());
	}
	$current_shipping_cost = wc_format_localized_price($current_shipping_cost);
}

?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-change-shipping-method">
	<div class="wc-backbone-modal vp-woo-pont-modal-change-shipping-method">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Change shipping method', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'vp-woo-pont' ); ?></span>
					</button>
				</header>
				<article>
					<div class="vp-woo-pont-modal-change-shipping-method-form">
						<p>
							<strong><?php esc_html_e('Shipping method', 'vp-woo-pont'); ?></strong>
						</p>
						<ul class="vp-woo-pont-modal-change-shipping-method-methods">
							<?php foreach ( $shipping_methods as $method_id => $method_name ): ?>
								<li>
									<input type="radio" id="shipping-method-<?php echo esc_attr($method_id); ?>" name="shipping_method" value="<?php echo esc_attr($method_id); ?>" <?php checked($method_id, $current_shipping_method); ?>>
									<label for="shipping-method-<?php echo esc_attr($method_id); ?>">
										<?php echo esc_html($method_name); ?>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="vp-woo-pont-modal-packaging-weight">
						<p><strong><?php esc_html_e('Shipping cost (gross)', 'vp-woo-pont'); ?></strong></p>
						<div>
							<input type="text" name="shipping_cost" value="<?php echo esc_attr($current_shipping_cost); ?>">
							<em><?php echo esc_html($currency); ?></em>
						</div>
					</div>
				</article>
				<footer>
					<div class="inner">
						<a class="button button-primary button-large vp-woo-pont-modal-change-shipping-method-submit" href="#"><?php esc_html_e( 'Save', 'vp-woo-pont' ); ?></a>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>