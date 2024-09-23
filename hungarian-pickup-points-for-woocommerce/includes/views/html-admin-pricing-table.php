<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_pricing');

//Apply filters
$conditions = VP_Woo_Pont_Conditions::get_conditions('pricings');

//Available pickup points
$pickup_providers = $this->get_available_providers();

//Supported countries for Packeta
$supported_countries = array();
$supported_countries['packeta'] = VP_Woo_Pont()->providers['packeta']->get_enabled_countries();

//Supported countroes for GLS
$gls_supported_countires = VP_Woo_Pont()->providers['gls']->get_enabled_countries();
$supported_countries['gls_'] = $gls_supported_countires;

//Supported countroes for DPD
$dpd_supported_countires = VP_Woo_Pont()->providers['dpd']->get_enabled_countries();
$supported_countries['dpd'] = $dpd_supported_countires;

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-pricings">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="vp-woo-pont-settings-pricing vp-woo-pont-settings-repeat-item">
						<div class="vp-woo-pont-settings-pricing-title">
							<div class="cost-field">
								<input placeholder="<?php _e('Shipping cost(net)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_pricing[X][cost]" value="<?php echo esc_attr($automation['cost']); ?>">
								<small><?php echo esc_html(get_woocommerce_currency_symbol()); ?></small>
							</div>
							<label class="conditional-toggle">
								<input type="checkbox" data-name="vp_woo_pont_pricing[X][condition_enabled]" <?php checked( $automation['conditional'] ); ?> class="condition" value="yes">
								<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
							</label>
							<a href="#" class="delete-pricing"><?php _e('delete', 'vp-woo-pont'); ?></a>
						</div>
						<div class="vp-woo-pont-settings-pricing-if" <?php if(!$automation['conditional']): ?>style="display:none"<?php endif; ?>>
							<div class="vp-woo-pont-settings-pricing-if-header">
								<span><?php _e('Apply this pricing, if', 'vp-woo-pont'); ?></span>
								<select data-name="vp_woo_pont_pricing[X][logic]">
									<option value="and" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'and' ); ?>><?php _e('All', 'vp-woo-pont'); ?></option>
									<option value="or" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'or' ); ?>><?php _e('One', 'vp-woo-pont'); ?></option>
								</select>
								<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
							</div>
							<ul class="vp-woo-pont-settings-pricing-if-options conditions" <?php if(isset($automation['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($automation['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
						<div class="vp-woo-pont-settings-pricing-points">
							<label><?php _e('Apply to the following pickup points:', 'vp-woo-pont'); ?></label>
							<ul>
								<?php foreach ($pickup_providers as $id => $label): ?>
									<li class="provider-<?php echo esc_attr($id); ?>">
										<label>
											<input type="checkbox" data-name="vp_woo_pont_pricing[X][providers][]" <?php checked( in_array($id, $automation['providers']) ); ?> value="<?php echo esc_attr($id); ?>">
											<span><?php echo esc_html($label); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="vp-woo-pont-settings-pricing-countries">
							<label><?php _e('Apply to the following pickup point countries:', 'vp-woo-pont'); ?></label>
							<ul>
								<?php foreach ($supported_countries as $courier => $countries): ?>
									<?php foreach ($countries as $id => $country): ?>
										<li class="country-<?php echo esc_attr($id); ?>" data-courier="<?php echo esc_attr($courier); ?>">
											<label>
												<input type="checkbox" data-name="vp_woo_pont_pricing[X][countries][]" <?php checked( (isset($automation['countries']) && in_array($id, $automation['countries'])) ); ?> value="<?php echo esc_attr($id); ?>">
												<span><?php echo esc_html($country); ?></span>
											</label>
										</li>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-pricing-add">
			<a href="#" class="add button"><?php _e('Add new cost', 'vp-woo-pont'); ?></a>
			<a href="#" class="import" data-type="pricing"><span class="dashicons dashicons-database-import"></span> <span><?php _e('Import', 'vp-woo-pont'); ?></span></a>
			<a href="#" class="export" data-type="pricing" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_settings_export" ); ?>"><span class="dashicons dashicons-database-export"></span> <span><?php _e('Export', 'vp-woo-pont'); ?></span></a>
		</div>
		<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>

		<script type="text/html" id="vp_woo_pont_pricing_sample_row">
			<div class="vp-woo-pont-settings-pricing vp-woo-pont-settings-repeat-item">
				<div class="vp-woo-pont-settings-pricing-title">
					<div class="cost-field">
						<input placeholder="<?php _e('Shipping cost(net)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_pricing[X][cost]">
						<small><?php echo esc_html(get_woocommerce_currency_symbol()); ?></small>
					</div>
					<label class="conditional-toggle">
						<input type="checkbox" data-name="vp_woo_pont_pricing[X][condition_enabled]" class="condition" value="yes">
						<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
					</label>
					<a href="#" class="delete-pricing"><?php _e('delete', 'vp-woo-pont'); ?></a>
				</div>
				<div class="vp-woo-pont-settings-pricing-if" style="display:none">
					<div class="vp-woo-pont-settings-pricing-if-header">
						<span><?php _e('Apply this pricing, if', 'vp-woo-pont'); ?></span>
						<select data-name="vp_woo_pont_pricing[X][logic]">
							<option value="and"><?php _e('All', 'vp-woo-pont'); ?></option>
							<option value="or"><?php _e('One', 'vp-woo-pont'); ?></option>
						</select>
						<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
					</div>
					<ul class="vp-woo-pont-settings-pricing-if-options conditions"></ul>
				</div>
				<div class="vp-woo-pont-settings-pricing-points">
					<label><?php _e('Apply to the following pickup points:', 'vp-woo-pont'); ?></label>
					<ul>
						<?php foreach ($pickup_providers as $id => $label): ?>
							<li class="provider-<?php echo esc_attr($id); ?>">
								<label>
									<input type="checkbox" data-name="vp_woo_pont_pricing[X][providers][]" value="<?php echo esc_attr($id); ?>">
									<span><?php echo esc_html($label); ?></span>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="vp-woo-pont-settings-pricing-countries">
					<label><?php _e('Apply to the following pickup point countries:', 'vp-woo-pont'); ?></label>
					<ul>
						<?php foreach ($supported_countries as $courier => $countries): ?>
							<?php foreach ($countries as $id => $country): ?>
								<li class="country-<?php echo esc_attr($id); ?>" data-courier="<?php echo esc_attr($courier); ?>">
									<label>
										<input type="checkbox" data-name="vp_woo_pont_pricing[X][countries][]" value="<?php echo esc_attr($id); ?>">
										<span><?php echo esc_html($country); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</script>

		<?php echo VP_Woo_Pont_Conditions::get_sample_row('pricings'); ?>

		<?php include( dirname( __FILE__ ) . '/html-modal-import.php' ); ?>

	</td>
</tr>

