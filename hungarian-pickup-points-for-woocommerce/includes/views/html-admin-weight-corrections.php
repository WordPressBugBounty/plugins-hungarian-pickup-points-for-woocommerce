<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_weight_corrections');

//Apply filters
$conditions = VP_Woo_Pont_Conditions::get_conditions('weight_corrections');

// Get the current weight unit from WooCommerce settings
$weight_unit = get_option('woocommerce_weight_unit');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-weight-corrections">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="vp-woo-pont-settings-weight-correction vp-woo-pont-settings-repeat-item">
						<div class="vp-woo-pont-settings-weight-correction-title">
							<div class="correction-field">
								<input placeholder="<?php _e('Weight correction', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_weight_correction[X][correction]" value="<?php echo esc_attr($automation['correction']); ?>">
							</div>
							<label class="conditional-toggle">
								<input type="checkbox" data-name="vp_woo_pont_weight_correction[X][condition_enabled]" <?php checked( $automation['conditional'] ); ?> class="condition" value="yes">
								<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
							</label>
							<a href="#" class="delete-weight-correction"><?php _e('delete', 'vp-woo-pont'); ?></a>
						</div>
						<div class="vp-woo-pont-settings-weight-correction-if" <?php if(!$automation['conditional']): ?>style="display:none"<?php endif; ?>>
							<div class="vp-woo-pont-settings-weight-correction-if-header">
								<span><?php _e('Apply this correction, if', 'vp-woo-pont'); ?></span>
								<select data-name="vp_woo_pont_weight_correction[X][logic]">
									<option value="and" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'and' ); ?>><?php _e('All', 'vp-woo-pont'); ?></option>
									<option value="or" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'or' ); ?>><?php _e('One', 'vp-woo-pont'); ?></option>
								</select>
								<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
							</div>
							<ul class="vp-woo-pont-settings-weight-correction-if-options conditions" <?php if(isset($automation['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($automation['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-weight-correction-add">
			<a href="#" class="add button"><?php _e('Add new correction', 'vp-woo-pont'); ?></a>
		</div>
		<p class="description">
			<?php printf(__('You can add, subtract or overwrite your package weight using this option. Your current unit of measurement is %s. Simply use math in the value, for example:', 'vp-woo-pont'), esc_html($weight_unit)); ?>
		</p>
		<ul class="vp-woo-pont-settings-weight-correction-description">
			<li><?php _e('To add 1kg to the package weight, use +1', 'vp-woo-pont'); ?></li>
			<li><?php _e('To subtract 1kg from the package weight, use -1', 'vp-woo-pont'); ?></li>
			<li><?php _e('To set the package weight to fixed 1kg, use 1', 'vp-woo-pont'); ?></li>
			<li><?php _e('To increase the weight by 20%, use +20%', 'vp-woo-pont'); ?></li>
		</ul>
	</td>
</tr>

<script type="text/html" id="vp_woo_pont_weight_correction_sample_row">
	<div class="vp-woo-pont-settings-weight-correction vp-woo-pont-settings-repeat-item">
		<div class="vp-woo-pont-settings-weight-correction-title">
			<div class="correction-field">
				<input placeholder="<?php _e('Weight correction', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_weight_correction[X][correction]">
			</div>
			<label class="conditional-toggle">
				<input type="checkbox" data-name="vp_woo_pont_weight_correction[X][condition_enabled]" class="condition" value="yes">
				<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
			</label>
			<a href="#" class="delete-weight-correction"><?php _e('delete', 'vp-woo-pont'); ?></a>
		</div>
		<div class="vp-woo-pont-settings-weight-correction-if" style="display:none">
			<div class="vp-woo-pont-settings-weight-correction-if-header">
				<span><?php _e('Apply this correction, if', 'vp-woo-pont'); ?></span>
				<select data-name="vp_woo_pont_weight_correction[X][logic]">
					<option value="and"><?php _e('All', 'vp-woo-pont'); ?></option>
					<option value="or"><?php _e('One', 'vp-woo-pont'); ?></option>
				</select>
				<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
			</div>
			<ul class="vp-woo-pont-settings-weight-correction-if-options conditions"></ul>
		</div>
	</div>
</script>

<?php echo VP_Woo_Pont_Conditions::get_sample_row('weight_corrections'); ?>
