<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php

//Get the parcel info
$parcel_statuses = $this->get_supported_tracking_statuses();
$parcel_info = $order->get_meta('_vp_woo_pont_parcel_info');

//Get provider
$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

?>

	<div class="vp-woo-pont-metabox-messages vp-woo-pont-metabox-messages-tracking vp-woo-pont-metabox-messages-success" style="display:none;">
		<div class="vp-woo-pont-metabox-messages-content">
			<ul></ul>
			<a href="#"><span class="dashicons dashicons-no-alt"></span></a>
		</div>
	</div>

	<ul class="order_notes" id="vp_woo_pont_tracking_info_list">
		<?php if(!empty($parcel_info) && count($parcel_info) > 0 && $parcel_info[0]['date']): ?>
			<?php foreach ($parcel_info as $event_id => $event): ?>
				<li class="note">
					<div class="note_content">
						<?php $label = (isset($event['label'])) ? $event['label'] : false; ?>
						<?php if(!$label) $label = $parcel_statuses[$provider_id][$event['event']]; ?>
						<p><?php echo esc_html__($label, 'vp-woo-pont'); ?></p>
					</div>
					<p class="meta">
						<abbr class="exact-date" title="<?php echo esc_attr( date_i18n('Y-m-d H:i:s',$event['date'])); ?>">
							<?php echo esc_html( sprintf( __( '%1$s at %2$s', 'vp-woo-pont' ), date_i18n( wc_date_format(), $event['date'] ), date_i18n( wc_time_format(), $event['date'] ) ) ); ?>
						</abbr>
					</p>
				</li>
			<?php endforeach; ?>
		<?php else: ?>
			<li class="note">
				<div class="note_content">
					<p><?php esc_html_e('Package created. Tracking number:', 'vp-woo-pont'); ?> <?php echo esc_html($order->get_meta('_vp_woo_pont_parcel_number')); ?></p>
				</div>
			</li>
		<?php endif; ?>
		<li class="note customer-note note-sample" style="display:none">
			<div class="note_content"><p></p></div>
			<p class="meta"><abbr class="exact-date"></abbr></p>
		</li>
	</ul>
	<div class="add_note">
		<button type="button" id="vp_woo_pont_update_tracking_info" class="button"><?php echo esc_html__('Reload', 'vp-woo-pont'); ?></button>
	</div>
