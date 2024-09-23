<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_cod_fees');

//Apply filters
$conditions = VP_Woo_Pont_Conditions::get_conditions('cod_fees');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-cod-fees">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="vp-woo-pont-settings-cod-fee vp-woo-pont-settings-repeat-item">
						<div class="vp-woo-pont-settings-cod-fee-title">
							<div class="fee-field">
								<input placeholder="<?php _e('COD fee(net)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_cod_fee[X][cost]" value="<?php echo esc_attr($automation['cost']); ?>">
								<label>
									<input type="radio" data-name="vp_woo_pont_cod_fee[X][type]" value="fixed" <?php checked( $automation['type'], 'fixed' ); ?>>
									<small><?php echo esc_html(get_woocommerce_currency_symbol()); ?></small>
								</label>
								<label>
									<input type="radio" data-name="vp_woo_pont_cod_fee[X][type]" value="percentage" <?php checked( $automation['type'], 'percentage' ); ?>>
									<small>%</small>
								</label>
							</div>
							<label class="conditional-toggle">
								<input type="checkbox" data-name="vp_woo_pont_cod_fee[X][condition_enabled]" <?php checked( $automation['conditional'] ); ?> class="condition" value="yes">
								<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
							</label>
							<a href="#" class="delete-cod-fee"><?php _e('delete', 'vp-woo-pont'); ?></a>
						</div>
						<div class="vp-woo-pont-settings-cod-fee-if" <?php if(!$automation['conditional']): ?>style="display:none"<?php endif; ?>>
							<div class="vp-woo-pont-settings-cod-fee-if-header">
								<span><?php _e('Apply this fee, if', 'vp-woo-pont'); ?></span>
								<select data-name="vp_woo_pont_cod_fee[X][logic]">
									<option value="and" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'and' ); ?>><?php _e('All', 'vp-woo-pont'); ?></option>
									<option value="or" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'or' ); ?>><?php _e('One', 'vp-woo-pont'); ?></option>
								</select>
								<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
							</div>
							<ul class="vp-woo-pont-settings-cod-fee-if-options conditions" <?php if(isset($automation['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($automation['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-cod-fee-add">
			<a href="#" <?php if($data['disabled']): ?>data-disabled="true" class="add-disabled" title="<?php _e('You can add fees in the PRO version', 'vp-woo-pont'); ?>"<?php else: ?>class="add button"<?php endif; ?>><?php _e('Add new fee', 'vp-woo-pont'); ?></a>
		</div>
	</td>
</tr>

<script type="text/html" id="vp_woo_pont_cod_fee_sample_row">
	<div class="vp-woo-pont-settings-cod-fee vp-woo-pont-settings-repeat-item">
		<div class="vp-woo-pont-settings-cod-fee-title">
			<div class="fee-field">
				<input placeholder="<?php _e('COD fee(net)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_cod_fee[X][cost]">
				<label>
					<input type="radio" data-name="vp_woo_pont_cod_fee[X][type]" value="fixed" checked>
					<small><?php echo esc_html(get_woocommerce_currency_symbol()); ?></small>
				</label>
				<label>
					<input type="radio" data-name="vp_woo_pont_cod_fee[X][type]" value="percentage">
					<small>%</small>
				</label>
			</div>
			<label class="conditional-toggle">
				<input type="checkbox" data-name="vp_woo_pont_cod_fee[X][condition_enabled]" class="condition" value="yes">
				<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
			</label>
			<a href="#" class="delete-cod-fee"><?php _e('delete', 'vp-woo-pont'); ?></a>
		</div>
		<div class="vp-woo-pont-settings-cod-fee-if" style="display:none">
			<div class="vp-woo-pont-settings-cod-fee-if-header">
				<span><?php _e('Apply this fee, if', 'vp-woo-pont'); ?></span>
				<select data-name="vp_woo_pont_cod_fee[X][logic]">
					<option value="and"><?php _e('All', 'vp-woo-pont'); ?></option>
					<option value="or"><?php _e('One', 'vp-woo-pont'); ?></option>
				</select>
				<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
			</div>
			<ul class="vp-woo-pont-settings-cod-fee-if-options conditions"></ul>
		</div>
	</div>
</script>

<?php echo VP_Woo_Pont_Conditions::get_sample_row('cod_fees'); ?>

<?php include( dirname( __FILE__ ) . '/html-modal-import.php' ); ?>
