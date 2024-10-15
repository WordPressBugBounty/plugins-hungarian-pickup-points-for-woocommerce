<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_packagings');

//Apply filters
$conditions = VP_Woo_Pont_Conditions::get_conditions('packagings');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-packagings">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="vp-woo-pont-settings-packaging vp-woo-pont-settings-repeat-item">
						<div class="vp-woo-pont-settings-packaging-type">
							<div class="packaging-field packaging-field-name" data-placeholder="<?php _e('Name', 'vp-woo-pont'); ?>">
								<input placeholder="<?php _e('Name', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][name]" value="<?php echo esc_attr($automation['name']); ?>">
							</div>
							<div class="packaging-field packaging-field-sku" data-placeholder="<?php _e('SKU', 'vp-woo-pont'); ?>">
								<input placeholder="<?php _e('SKU', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][sku]" value="<?php echo esc_attr($automation['sku']); ?>">
							</div>
							<div class="packaging-field" data-placeholder="<?php _e('Length(cm)', 'vp-woo-pont'); ?>">
								<input placeholder="<?php _e('Length(cm)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][length]" value="<?php echo esc_attr($automation['length']); ?>">
							</div>
							<div class="packaging-field" data-placeholder="<?php _e('Width(cm)', 'vp-woo-pont'); ?>">
								<input placeholder="<?php _e('Width(cm)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][width]" value="<?php echo esc_attr($automation['width']); ?>">
							</div>
							<div class="packaging-field" data-placeholder="<?php _e('Height(cm)', 'vp-woo-pont'); ?>">
								<input placeholder="<?php _e('Height(cm)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][height]" value="<?php echo esc_attr($automation['height']); ?>">
							</div>
							<div class="packaging-field packaging-field-default">
								<label>
									<input type="checkbox" data-name="vp_woo_pont_packaging[X][default]" <?php checked( $automation['default'] ); ?> value="yes">
									<span><?php esc_html_e('Default', 'vp-woo-pont'); ?></span>
								</label>
							</div>
							<div class="packaging-field packaging-field-delete">
								<a href="#" class="delete-packaging"><?php _e('delete', 'vp-woo-pont'); ?></a>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-packaging-add">
			<a href="#" class="add button"><?php _e('Add new packaging', 'vp-woo-pont'); ?></a>
		</div>
		<p class="description">
			<?php esc_html_e('Enter your shipping box sizes that you usually use. Based on the volume of the order, it will pair with a packaging type automatically(some couriers require to have dimensions specified).', 'vp-woo-pont'); ?>
		</p>
	</td>
</tr>

<script type="text/html" id="vp_woo_pont_packaging_sample_row">
	<div class="vp-woo-pont-settings-packaging vp-woo-pont-settings-repeat-item">
		<div class="vp-woo-pont-settings-packaging-type">
			<div class="packaging-field packaging-field-name" data-placeholder="<?php _e('Name', 'vp-woo-pont'); ?>">
				<input placeholder="<?php _e('Name', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][name]" value="">
			</div>
			<div class="packaging-field" data-placeholder="<?php _e('SKU', 'vp-woo-pont'); ?>">
				<input placeholder="<?php _e('SKU', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][sku]" value="">
			</div>
			<div class="packaging-field" data-placeholder="<?php _e('Length(cm)', 'vp-woo-pont'); ?>">
				<input placeholder="<?php _e('Length(cm)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][length]" value="">
			</div>
			<div class="packaging-field" data-placeholder="<?php _e('Width(cm)', 'vp-woo-pont'); ?>">
				<input placeholder="<?php _e('Width(cm)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][width]" value="">
			</div>
			<div class="packaging-field" data-placeholder="<?php _e('Height(cm)', 'vp-woo-pont'); ?>">
				<input placeholder="<?php _e('Height(cm)', 'vp-woo-pont'); ?>" type="text" data-name="vp_woo_pont_packaging[X][height]" value="">
			</div>
			<div class="packaging-field packaging-field-default">
				<label>
					<input type="checkbox" data-name="vp_woo_pont_packaging[X][default]" value="yes">
					<span><?php esc_html_e('Default', 'vp-woo-pont'); ?></span>
				</label>
			</div>
			<div class="packaging-field packaging-field-delete">
				<a href="#" class="delete-packaging"><?php _e('delete', 'vp-woo-pont'); ?></a>
			</div>
		</div>
	</div>
</script>

<?php echo VP_Woo_Pont_Conditions::get_sample_row('packagings'); ?>
