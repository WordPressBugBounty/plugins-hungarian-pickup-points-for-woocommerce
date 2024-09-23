<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_automations');

//When to generate these documents
$trigger_types = array(
	'order_created' => _x('When order created', 'Automation trigger', 'vp-woo-pont'),
	'payment_complete' => _x('On successful payment', 'Automation trigger', 'vp-woo-pont')
);

//Merge with order status settings
foreach (self::get_order_statuses() as $key => $label) {
	$trigger_types[$key] = sprintf( esc_html__( 'after %1$s status', 'vp-woo-pont' ), $label);
}

//Apply filters
$conditions = VP_Woo_Pont_Conditions::get_conditions('automations');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-automations">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="vp-woo-pont-settings-automation vp-woo-pont-settings-repeat-item">
						<div class="vp-woo-pont-settings-automation-title">
							<div class="select-field-label">
								<label>
									<i class="icon"></i>
									<span><?php esc_html_e('Shipping label', 'vp-woo-pont'); ?></span>
								</label>
							</div>
							<span class="text"><?php esc_html_e('when', 'vp-woo-pont'); ?></span>
							<div class="select-field">
								<label>
									<span>-</span>
								</label>
								<select class="vp-woo-pont-settings-automation-trigger vp-woo-pont-settings-repeat-select" data-name="vp_woo_pont_automation[X][trigger]">
									<?php foreach ($trigger_types as $value => $label): ?>
										<option value="<?php echo esc_attr($value); ?>" <?php if(isset($automation['trigger'])) selected( $automation['trigger'], $value ); ?>><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<label class="conditional-toggle">
								<input type="checkbox" data-name="vp_woo_pont_automation[X][condition_enabled]" <?php checked( $automation['conditional'] ); ?> class="condition" value="yes">
								<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
							</label>
							<a href="#" class="delete-automation"><?php _e('delete', 'vp-woo-pont'); ?></a>
						</div>
						<div class="vp-woo-pont-settings-automation-if" <?php if(!$automation['conditional']): ?>style="display:none"<?php endif; ?>>
							<div class="vp-woo-pont-settings-automation-if-header">
								<span><?php _e('Run this automation, if', 'vp-woo-pont'); ?></span>
								<select data-name="vp_woo_pont_automation[X][logic]">
									<option value="and" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'and' ); ?>><?php _e('All', 'vp-woo-pont'); ?></option>
									<option value="or" <?php if(isset($automation['logic'])) selected( $automation['logic'], 'or' ); ?>><?php _e('One', 'vp-woo-pont'); ?></option>
								</select>
								<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
							</div>
							<ul class="vp-woo-pont-settings-automation-if-options conditions" <?php if(isset($automation['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($automation['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-automation-add">
			<a href="#" class="add button"><?php _e('Add new automation', 'vp-woo-pont'); ?></a>
		</div>
		<p class="description"><?php echo esc_html($data['desc']); ?></p>
	</td>
</tr>

<script type="text/html" id="vp_woo_pont_automation_sample_row">
	<div class="vp-woo-pont-settings-automation vp-woo-pont-settings-repeat-item">
		<div class="vp-woo-pont-settings-automation-title">
			<div class="select-field-label">
				<label>
					<i class="icon"></i>
					<span><?php esc_html_e('Generate shipping label', 'vp-woo-pont'); ?></span>
				</label>
			</div>
			<span class="text"><?php esc_html_e('when', 'vp-woo-pont'); ?></span>
			<div class="select-field">
				<label>
					<span>-</span>
				</label>
				<select class="vp-woo-pont-settings-automation-trigger vp-woo-pont-settings-repeat-select" data-name="vp_woo_pont_automation[X][trigger]">
					<?php foreach ($trigger_types as $value => $label): ?>
						<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<label class="conditional-toggle">
				<input type="checkbox" data-name="vp_woo_pont_automation[X][condition_enabled]" class="condition" value="yes">
				<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
			</label>
			<a href="#" class="delete-automation"><?php _e('delete', 'vp-woo-pont'); ?></a>
		</div>
		<div class="vp-woo-pont-settings-automation-if" style="display:none">
			<div class="vp-woo-pont-settings-automation-if-header">
				<span><?php _e('Run this automation, if', 'vp-woo-pont'); ?></span>
				<select data-name="vp_woo_pont_automation[X][logic]">
					<option value="and"><?php _e('All', 'vp-woo-pont'); ?></option>
					<option value="or"><?php _e('One', 'vp-woo-pont'); ?></option>
				</select>
				<span><?php _e('of the following match', 'vp-woo-pont'); ?></span>
			</div>
			<ul class="vp-woo-pont-settings-automation-if-options conditions"></ul>
		</div>
	</div>
</script>

<?php echo VP_Woo_Pont_Conditions::get_sample_row('automations'); ?>
