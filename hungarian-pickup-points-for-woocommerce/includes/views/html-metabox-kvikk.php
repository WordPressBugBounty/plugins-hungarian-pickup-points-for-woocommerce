<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php
$accounting_info = $order->get_meta('_vp_woo_pont_kvikk_accounting');
$services_labels = VP_Woo_Pont()->providers['kvikk']->extra_services;

?>

	<div class="vp-woo-pont-metabox-content">
		<ul class="vp-woo-pont-metabox-rows">
			<?php if(isset($accounting_info['courierTrackingNumber'])): ?>
			<li class="vp-woo-pont-metabox-rows-data show">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<span><?php esc_html_e('Courier tracking #', 'vp-woo-pont'); ?></span>
					<strong><?php echo esc_html($accounting_info['courierTrackingNumber']); ?></strong>
				</div>
			</li>
			<?php endif; ?>
			<?php if(isset($accounting_info['weight'])): ?>
			<li class="vp-woo-pont-metabox-rows-data show">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<span><?php esc_html_e('Weight', 'vp-woo-pont'); ?></span>
					<strong><?php echo esc_html($accounting_info['weight']); ?>g</strong>
				</div>
			</li>
			<?php endif; ?>
			<?php if(isset($accounting_info['shipping'])): ?>
			<li class="vp-woo-pont-metabox-rows-data show">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<span><?php esc_html_e('Shipping cost(net)', 'vp-woo-pont'); ?></span>
					<strong><?php echo wc_price($accounting_info['shipping']); ?></strong>
				</div>
			</li>
			<?php endif; ?>

			<?php if($order->get_payment_method() == 'cod'): ?>
				<?php if(isset($accounting_info['codFee'])): ?>
				<li class="vp-woo-pont-metabox-rows-data show">
					<div class="vp-woo-pont-metabox-rows-data-inside">
						<span><?php esc_html_e('COD fixed fee(net)', 'vp-woo-pont'); ?></span>
						<strong><?php echo wc_price($accounting_info['codFee']); ?></strong>
					</div>
				</li>
				<?php endif; ?>

				<?php if(isset($accounting_info['codPercentage'])): ?>
				<li class="vp-woo-pont-metabox-rows-data show">
					<div class="vp-woo-pont-metabox-rows-data-inside">
						<span><?php esc_html_e('COD % fee', 'vp-woo-pont'); ?></span>
						<strong><?php echo $accounting_info['codPercentage']; ?>% - <?php echo wc_price($order->get_total() * $accounting_info['codPercentage'] / 100); ?></strong>
					</div>
				</li>
				<?php endif; ?>
			<?php endif; ?>


			<?php if(isset($accounting_info['services'])): ?>
				<?php foreach($accounting_info['services'] as $service): ?>
					<li class="vp-woo-pont-metabox-rows-data show">
						<div class="vp-woo-pont-metabox-rows-data-inside">
							<span><?php echo $services_labels[$service['type']]; ?></span>
							<strong><?php echo wc_price($service['cost']); ?></strong>
						</div>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if(isset($accounting_info['link'])): ?>
			<li class="vp-woo-pont-metabox-rows-data show">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<a href="<?php echo esc_url($accounting_info['link']); ?>"><?php esc_html_e('Open in Kvikk', 'vp-woo-pont'); ?></a>
				</div>
			</li>
			<?php endif; ?>
		</ul>
	</div>