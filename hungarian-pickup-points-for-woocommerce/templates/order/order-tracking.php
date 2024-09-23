<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="vp-woo-pont-tracking-page">

	<ul class="vp-woo-pont-tracking-steps">
		<?php foreach ($tracking_steps as $step): ?>
			<li class="vp-woo-pont-tracking-step step-<?php echo esc_attr($step['id']); ?> <?php if($step['id'] == $latest_active_step): ?>current<?php endif; ?> <?php if(isset($step['status'])): ?>status-<?php echo esc_attr($step['status']); ?><?php endif; ?>">
				<i class="vp-woo-pont-tracking-step-icon vp-woo-pont-tracking-step-icon-<?php echo esc_attr($step['id']); ?>"></i>
				<span class="vp-woo-pont-tracking-step-progress"></span>
				<strong class="vp-woo-pont-tracking-step-label"><?php echo esc_html($step['label']); ?></strong>
				<?php if(isset($step['date'])): ?>
					<span class="vp-woo-pont-tracking-step-date"><?php echo esc_html(wc_format_datetime( $step['date'], 'F j. H:i' )); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="vp-woo-pont-tracking-info">
		<div class="vp-woo-pont-tracking-events-container">
			<?php do_action( 'vp_woo_pont_tracking_page_before_tracking_events', $order ); ?>
			<ul class="vp-woo-pont-tracking-events">
				<li class="vp-woo-pont-tracking-events-title">
					<strong><?php esc_html_e('Tracking details', 'vp-woo-pont'); ?></strong>
					<?php if($order->get_meta('_vp_woo_pont_parcel_info_time')): ?>
						<span>
							<?php esc_html_e('Refreshed:', 'vp-woo-pont'); ?>
							<?php echo esc_html( sprintf( __( '%1$s at %2$s', 'vp-woo-pont' ), date_i18n( wc_date_format(), $order->get_meta('_vp_woo_pont_parcel_info_time') ), date_i18n( wc_time_format(), $order->get_meta('_vp_woo_pont_parcel_info_time') ) ) ); ?>
						</span>
					<?php endif; ?>
				</li>
				<?php if($parcel_number): ?>
					<?php if(!empty($parcel_info) && count($parcel_info) > 0): ?>
						<?php foreach ($parcel_info as $event_id => $event): ?>
							<li class="vp-woo-pont-tracking-event">
								<span class="vp-woo-pont-tracking-event-date">
									<abbr class="exact-date" title="<?php echo esc_attr( date_i18n('Y-m-d H:i:s',$event['date'])); ?>">
										<?php echo esc_html( sprintf( __( '%1$s at %2$s', 'vp-woo-pont' ), date_i18n( wc_date_format(), $event['date'] ), date_i18n( wc_time_format(), $event['date'] ) ) ); ?>
									</abbr>
								</span>
								<?php $label = (isset($event['label'])) ? $event['label'] : false; ?>
								<?php $custom_label = (isset($parcel_statuses[$tracking_provider][$event['event']])) ? $parcel_statuses[$tracking_provider][$event['event']] : false; ?>
								<?php $label = (strlen($custom_label) > $label) ? $custom_label : $label; ?>
								<?php if(isset($event['comment']) && $event['comment']): ?>
									<p class="vp-woo-pont-tracking-event-label"><?php echo esc_html($event['comment']); ?></p>
								<?php else: ?>
									<p class="vp-woo-pont-tracking-event-label">
										<?php echo esc_html__($label, 'vp-woo-pont'); ?>
										<?php if(isset($event['location']) && $event['location']): ?>
											<br><em class="vp-woo-pont-tracking-event-label-location"><?php echo esc_html($event['location']); ?></em>
										<?php endif; ?>
									</p>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					<?php else: ?>
						<li class="vp-woo-pont-tracking-event">
							<p class="vp-woo-pont-tracking-event-label"><?php esc_html_e('Package created. Tracking number:', 'vp-woo-pont'); ?> <?php echo esc_html($parcel_number); ?></p>
						</li>
					<?php endif; ?>
				<?php else: ?>
					<li class="vp-woo-pont-tracking-event vp-woo-pont-tracking-event-empty">
						<p class="vp-woo-pont-tracking-event-label"><?php esc_html_e('Package not shipped yet, waiting for tracking number.', 'vp-woo-pont'); ?></p>
					</li>
				<?php endif; ?>
			</ul>
			<?php do_action( 'vp_woo_pont_tracking_page_after_tracking_events', $order ); ?>
		</div>

		<div class="vp-woo-pont-tracking-sidebar">
			<?php if($pickup_point): ?>
				<div class="vp-woo-pont-tracking-map">
					<strong class="vp-woo-pont-tracking-map-title"><?php echo esc_html($pickup_point['name']); ?></strong>
					<div class="vp-woo-pont-tracking-map-view" id="vp-woo-pont-tracking-map-view" data-coordinates="<?php echo esc_attr($pickup_point['coordinates']); ?>" data-provider="<?php echo esc_attr($provider); ?>"></div>
					<ul class="vp-woo-pont-tracking-map-info">
						<?php if($pickup_point['data'] && isset($pickup_point['data']['zip']) && isset($pickup_point['data']['city']) && isset($pickup_point['data']['addr'])): ?>
							<li class="vp-woo-pont-tracking-map-info-address">
								<?php echo esc_html($pickup_point['data']['zip']); ?> <?php echo esc_html($pickup_point['data']['city']); ?>, <?php echo esc_html($pickup_point['data']['addr']); ?>
							</li>
						<?php endif; ?>
						<?php if($pickup_point['data'] && isset($pickup_point['data']['comment'])): ?>
							<li class="vp-woo-pont-tracking-map-info-comment">
								<?php echo esc_html($pickup_point['data']['comment']); ?>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>

			<ul class="vp-woo-pont-tracking-order">
				<?php do_action( 'vp_woo_pont_tracking_page_before_order_info', $order ); ?>
				<li class="vp-woo-pont-tracking-order-title">
					<strong><?php esc_html_e('Order information', 'vp-woo-pont'); ?></strong>
				</li>
				<li class="vp-woo-pont-tracking-order-number">
					<strong><?php esc_html_e('Order Number', 'vp-woo-pont'); ?></strong>
					<span><?php echo esc_html($order->get_order_number()); ?></span>
				</li>
				<li class="vp-woo-pont-tracking-order-carrier">
					<strong><?php esc_html_e('Shipment Carrier', 'vp-woo-pont'); ?></strong>
					<span>
						<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider); ?>"></i>
						<?php echo esc_html($carrier_name); ?>
					</span>
				</li>
				<?php if($parcel_number): ?>
					<li class="vp-woo-pont-tracking-order-parcel-number">
						<strong><?php esc_html_e('Tracking Number', 'vp-woo-pont'); ?></strong>
						<a href="<?php echo esc_url($tracking_url); ?>" target="_blank"><?php echo esc_html($parcel_number); ?></a>
					</li>
				<?php endif; ?>
				<?php if($logged_in): ?>
					<?php foreach ($invoices as $invoice_type => $invoice_data): ?>
						<li class="vp-woo-pont-tracking-order-invoice">
							<strong><?php echo esc_html($invoice_data['label']); ?></strong>
							<a href="<?php echo esc_url($invoice_data['url']); ?>" target="_blank"><?php echo esc_html($invoice_data['name']); ?></a>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
				<li class="vp-woo-pont-tracking-order-status">
					<strong><?php esc_html_e('Order Status', 'vp-woo-pont'); ?></strong>
					<span><?php echo esc_html(wc_get_order_status_name( $order->get_status() )); ?>
				</li>
				<?php do_action( 'vp_woo_pont_tracking_page_after_order_info', $order ); ?>
			</ul>
		</div>
	</div>
</div>