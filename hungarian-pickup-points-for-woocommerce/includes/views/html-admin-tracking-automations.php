<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_tracking_automations');

//Order status settings
$trigger_types = array();
foreach (self::get_order_statuses() as $key => $label) {
	$trigger_types[$key] = sprintf( esc_html__( '%1$s', 'tracking automation status label', 'vp-woo-pont' ), $label);
}

//Allow plugins to customize
$trigger_types = apply_filters('vp_woo_pont_tracking_automation_order_statuses', $trigger_types);

//Get package statuses
if(VP_Woo_Pont()->tracking) {
	$packages_statuses = VP_Woo_Pont()->tracking->get_supported_tracking_statuses();
} else {
	$packages_statuses = array();
}
$providers = VP_Woo_Pont_Helpers::get_supported_providers_for_home_delivery();

//Remove the providers that starts with kvikk_
foreach ($providers as $provider_id => $provider) {
	if(strpos($provider_id, 'kvikk_') === 0) {
		unset($providers[$provider_id]);
	}
}

//Add kvikk providers to the end
$providers['kvikk'] = 'Kvikk';

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-tracking-automations">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="vp-woo-pont-settings-tracking-automation vp-woo-pont-settings-repeat-item">

						<div class="vp-woo-pont-settings-tracking-automation-title">
							<div class="vp-woo-pont-settings-tracking-automation-title-left">
								<div>
									<span class="text"><?php esc_html_e('Change order status to', 'vp-woo-pont'); ?></span>
									<div class="select-field">
										<label>
											<span>-</span>
										</label>
										<select class="vp-woo-pont-settings-tracking-automation-trigger vp-woo-pont-settings-repeat-select" data-name="vp_woo_pont_tracking_automation[X][order_status]">
											<?php foreach ($trigger_types as $value => $label): ?>
												<option value="<?php echo esc_attr($value); ?>" <?php if(isset($automation['order_status'])) selected( $automation['order_status'], $value ); ?>><?php echo esc_html($label); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<span class="text"><?php esc_html_e('When package status is one of the following', 'vp-woo-pont'); ?></span>
							</div>
							<a href="#" class="delete-tracking-automation"><?php _e('delete', 'vp-woo-pont'); ?></a>
						</div>
						<div class="vp-woo-pont-settings-tracking-automation-if">
							<?php foreach ($packages_statuses as $provider_id => $statuses): ?>
								<?php if(VP_Woo_Pont_Helpers::is_provider_configured($provider_id)): ?>
								<div class="vp-woo-pont-settings-tracking-automation-if-group">
									<label>
										<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
										<strong><?php echo esc_attr($providers[$provider_id]); ?></strong>
									</label>
									<select multiple="multiple" class="multiselect wc-enhanced-select" data-name="vp_woo_pont_tracking_automation[X][package_status][<?php echo esc_attr($provider_id); ?>][]">
										<option value="delivered" <?php if(isset($automation[$provider_id])) selected( in_array('delivered', $automation[$provider_id]) ); ?>><?php esc_html_e('Delivered', 'vp-woo-pont'); ?></option>
										<?php foreach ($statuses as $status_id => $status): ?>
											<option value="<?php echo esc_attr($status_id); ?>" <?php if(isset($automation[$provider_id])) selected( in_array($status_id, $automation[$provider_id]) ); ?>>
												<?php echo esc_html__($status, 'vp-woo-pont'); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-tracking-automation-add">
			<a href="#" class="add button"><?php _e('Add new automation', 'vp-woo-pont'); ?></a>
		</div>
		<p class="description"><?php echo esc_html($data['desc']); ?></p>
	</td>
</tr>

<script type="text/html" id="vp_woo_pont_tracking_automation_sample_row">
	<div class="vp-woo-pont-settings-tracking-automation vp-woo-pont-settings-repeat-item">
		<div class="vp-woo-pont-settings-tracking-automation-title">
			<div class="vp-woo-pont-settings-tracking-automation-title-left">
				<div>
					<span class="text"><?php esc_html_e('Change order status to', 'vp-woo-pont'); ?></span>
					<div class="select-field">
						<label>
							<span>-</span>
						</label>
						<select class="vp-woo-pont-settings-tracking-automation-trigger vp-woo-pont-settings-repeat-select" data-name="vp_woo_pont_tracking_automation[X][order_status]">
							<?php foreach ($trigger_types as $value => $label): ?>
								<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<span class="text"><?php esc_html_e('When package status is one of the following', 'vp-woo-pont'); ?></span>
			</div>
			<a href="#" class="delete-tracking-automation"><?php _e('delete', 'vp-woo-pont'); ?></a>
		</div>
		<div class="vp-woo-pont-settings-tracking-automation-if">
			<?php foreach ($packages_statuses as $provider_id => $statuses): ?>
				<?php if(VP_Woo_Pont_Helpers::is_provider_configured($provider_id)): ?>
				<div class="vp-woo-pont-settings-tracking-automation-if-group">
					<label>
						<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
						<strong><?php echo esc_attr($providers[$provider_id]); ?></strong>
					</label>
					<select multiple="multiple" class="multiselect wc-enhanced-select" data-name="vp_woo_pont_tracking_automation[X][package_status][<?php echo esc_attr($provider_id); ?>][]">
						<option value="delivered"><?php esc_html_e('Delivered', 'vp-woo-pont'); ?></option>
						<?php foreach ($statuses as $status_id => $status): ?>
							<option value="<?php echo esc_attr($status_id); ?>"><?php echo esc_html__($status, 'vp-woo-pont'); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</script>

<?php echo VP_Woo_Pont_Conditions::get_sample_row('automations'); ?>
