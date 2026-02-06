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

//Supported countries
$supported_countries = $this->get_enabled_countries();

//Get enabled providers
$enabled_providers = $this->get_enabled_couriers();

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">

		<?php if(count($saved_values) > 2): ?>
			<div class="vp-woo-pont-settings-pricings-filter">
				<?php esc_html_e('Filter by:', 'vp-woo-pont'); ?>
				<select>
					<option value="all"><?php esc_html_e('All pickup points', 'vp-woo-pont'); ?></option>
					<?php foreach ($enabled_providers as $id => $label): ?>
						<option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>

				<select>
					<option value="all"><?php esc_html_e('All countries', 'vp-woo-pont'); ?></option>
					<?php foreach ($supported_countries as $country_code => $country): ?>
						<option value="<?php echo esc_attr($country_code); ?>"><?php echo esc_html($country['label']); ?></option>
					<?php endforeach; ?>
				</select>

				<a href="#" class="clear-filters"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Clear filters', 'vp-woo-pont'); ?></a>
			</div>
		<?php endif; ?>

		<div class="vp-woo-pont-settings vp-woo-pont-settings-pricings">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $pricing_id => $pricing ): ?>
					<div class="vp-woo-pont-settings-pricing vp-woo-pont-settings-repeat-item">
						<div class="vp-woo-pont-settings-pricing-title">
							<div class="cost-field">
								<input placeholder="<?php _e('Shipping cost(net)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_pricing[X][cost]" value="<?php echo esc_attr($pricing['cost']); ?>">
								<small><?php echo esc_html(get_woocommerce_currency_symbol()); ?></small>
							</div>
							<label class="weight-toggle">
								<input type="checkbox" data-name="vp_woo_pont_pricing[X][weight_based]" <?php checked( isset($pricing['weight_based']) && $pricing['weight_based'] ); ?> class="weight-based" value="yes">
								<span><?php esc_html_e('Weight based pricing', 'vp-woo-pont'); ?></span>
							</label>
							<label class="conditional-toggle">
								<input type="checkbox" data-name="vp_woo_pont_pricing[X][condition_enabled]" <?php checked( $pricing['conditional'] ); ?> class="condition" value="yes">
								<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
							</label>
							<div class="actions">
								<a href="#" class="move-up"><span class="dashicons dashicons-arrow-up-alt2"></span></a>
								<a href="#" class="move-down"><span class="dashicons dashicons-arrow-down-alt2"></span></a>
								<a href="#" class="duplicate-pricing"><?php _e('duplicate', 'vp-woo-pont'); ?></a>
								<a href="#" class="delete-pricing"><?php _e('delete', 'vp-woo-pont'); ?></a>
							</div>
						</div>
						<div class="vp-woo-pont-settings-pricing-weight" <?php if(isset($pricing['weight_based']) && $pricing['weight_based'] ): ?>style="display:block"<?php else: ?>style="display:none"<?php endif; ?>>
							<table>
								<thead>
									<tr>
										<th><?php esc_html_e('Min weight(kg)', 'vp-woo-pont'); ?></th>
										<th><?php esc_html_e('Max weight(kg)', 'vp-woo-pont'); ?></th>
										<th><?php esc_html_e('Shipping cost(net)', 'vp-woo-pont'); ?></th>
										<th></th>
									</tr>
								</thead>
								<tbody class="vp-woo-pont-settings-pricing-weight-options" <?php if(isset($pricing['weight_ranges'])): ?>data-options="<?php echo esc_attr(json_encode($pricing['weight_ranges'])); ?>"<?php endif; ?>></tbody>
							</table>
						</div>
						<div class="vp-woo-pont-settings-pricing-if" <?php if(!$pricing['conditional']): ?>style="display:none"<?php endif; ?>>
							<div class="vp-woo-pont-settings-pricing-if-header">
								<span><?php _e('Apply this pricing, if', 'vp-woo-pont'); ?></span>
								<select data-name="vp_woo_pont_pricing[X][logic]">
									<option value="and" <?php if(isset($pricing['logic'])) selected( $pricing['logic'], 'and' ); ?>><?php _e('All', 'vp-woo-pont'); ?></option>
									<option value="or" <?php if(isset($pricing['logic'])) selected( $pricing['logic'], 'or' ); ?>><?php _e('One', 'vp-woo-pont'); ?></option>
								</select>
								<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
							</div>
							<ul class="vp-woo-pont-settings-pricing-if-options conditions" <?php if(isset($pricing['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($pricing['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
						<div class="vp-woo-pont-settings-pricing-points">
							<label><?php _e('Apply to the following pickup points:', 'vp-woo-pont'); ?></label>
							<ul>
								<?php foreach ($pickup_providers as $id => $label): ?>
									<li class="provider-<?php echo esc_attr($id); ?>">
										<label>
											<input type="checkbox" data-courier="<?php echo esc_attr(explode('_', $id)[0]); ?>" data-name="vp_woo_pont_pricing[X][providers][]" <?php checked( in_array($id, $pricing['providers']) ); ?> value="<?php echo esc_attr($id); ?>">
											<span><?php echo esc_html($label); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="vp-woo-pont-settings-pricing-countries" <?php if(count($supported_countries) == 1 && isset($supported_countries['HU'])): ?>style="display:none"<?php endif; ?>>
							<label><?php _e('Apply to the following pickup point countries:', 'vp-woo-pont'); ?></label>
							<ul>
								<?php foreach ($supported_countries as $country_code => $country): ?>
									<?php
									//Skip if the only country is Hungary
									if(count($supported_countries) == 1 && isset($supported_countries['HU'])) {
										continue;
									}
									?>
									
									<li class="country-<?php echo esc_attr($country_code); ?>" data-couriers="<?php echo esc_attr( implode(',', $country['couriers']) ); ?>">
										<label>
											<input type="checkbox" data-name="vp_woo_pont_pricing[X][countries][]" <?php checked( (isset($pricing['countries']) && in_array($country_code, $pricing['countries'])) ); ?> value="<?php echo esc_attr($country_code); ?>">
											<span><?php echo esc_html($country['label']); ?></span>
										</label>
									</li>
									
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
					<label class="weight-toggle">
						<input type="checkbox" data-name="vp_woo_pont_pricing[X][weight_based]" class="weight-based" value="yes">
						<span><?php esc_html_e('Weight based pricing', 'vp-woo-pont'); ?></span>
					</label>
					<label class="conditional-toggle">
						<input type="checkbox" data-name="vp_woo_pont_pricing[X][condition_enabled]" class="condition" value="yes">
						<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
					</label>
					<div class="actions">
						<a href="#" class="move-up"><span class="dashicons dashicons-arrow-up-alt2"></span></a>
						<a href="#" class="move-down"><span class="dashicons dashicons-arrow-down-alt2"></span></a>
						<a href="#" class="duplicate-pricing"><?php _e('duplicate', 'vp-woo-pont'); ?></a>
						<a href="#" class="delete-pricing"><?php _e('delete', 'vp-woo-pont'); ?></a>
					</div>
				</div>
				<div class="vp-woo-pont-settings-pricing-weight" style="display:none">
					<table>
						<thead>
							<tr>
								<th><?php esc_html_e('Min weight(kg)', 'vp-woo-pont'); ?></th>
								<th><?php esc_html_e('Max weight(kg)', 'vp-woo-pont'); ?></th>
								<th><?php esc_html_e('Shipping cost(net)', 'vp-woo-pont'); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody class="vp-woo-pont-settings-pricing-weight-options"></tbody>
					</table>
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
						<?php foreach ($supported_countries as $country_code => $country_name): ?>
							<?php
								//Skip if the only country is Hungary
								if(count($supported_countries) == 1 && isset($supported_countries['HU'])) {
									continue;
								}
							?>
							<li class="country-<?php echo esc_attr($country_code); ?>">
								<label>
									<input type="checkbox" data-name="vp_woo_pont_pricing[X][countries][]" value="<?php echo esc_attr($country_code); ?>">
									<span><?php echo esc_html($country_name); ?></span>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</script>

		<?php echo VP_Woo_Pont_Conditions::get_sample_row('pricings'); ?>

		<script type="text/html" id="vp_woo_pont_weight_range_sample_row">
			<tr>
				<td>
					<input type="text" class="min" data-name="vp_woo_pont_pricing[X][weight_ranges][Y][min]" value="">
				</td>
				<td>
					<input type="text" class="max" data-name="vp_woo_pont_pricing[X][weight_ranges][Y][max]" value="">
				</td>
				<td>
					<div class="cost-field">
						<input type="text" class="cost" data-name="vp_woo_pont_pricing[X][weight_ranges][Y][cost]" value="">
						<small><?php echo esc_html(get_woocommerce_currency_symbol()); ?></small>
					</div>
				</td>
				<td>
					<div>
						<a href="#" class="add-weight-row"><span class="dashicons dashicons-plus-alt"></span></a>
						<a href="#" class="delete-weight-row"><span class="dashicons dashicons-dismiss"></span></a>
					</div>
				</td>
			</tr>
		</script>

		<?php include( dirname( __FILE__ ) . '/html-modal-import.php' ); ?>
	</td>
</tr>

