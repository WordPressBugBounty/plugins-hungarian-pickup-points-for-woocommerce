<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_notes');

//Apply filters
$conditions = VP_Woo_Pont_Conditions::get_conditions('notes');
$providers = $conditions['provider']['options'];

?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label>
			<?php echo esc_html( $data['title'] ); ?>
			<?php echo wp_kses_post( wc_help_tip( $data['description'] ) ); ?>
		</label>
	</th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-notes">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $note_id => $note ): ?>
					<div class="vp-woo-pont-settings-note vp-woo-pont-settings-repeat-item">
						<textarea placeholder="<?php esc_attr_e('Note', 'vp-woo-pont'); ?>" data-name="vp_woo_pont_note[X][note]"><?php echo esc_textarea($note['comment']); ?></textarea>
					
						<div class="vp-woo-pont-settings-note-title">
							<div class="select-field-label">
								<label>
									<span><?php esc_html_e('Add note to this provider:', 'vp-woo-pont'); ?></span>
								</label>
							</div>
							<select data-name="vp_woo_pont_note[X][provider]">
								<option value=""><?php esc_html_e('Select provider', 'vp-woo-pont'); ?></option>
								<?php foreach ($providers as $provider_id => $provider): ?>
									<option value="<?php echo esc_attr($provider_id); ?>" <?php selected( $note['provider'], $provider_id ); ?>><?php echo esc_html($provider); ?></option>
								<?php endforeach; ?>
							</select>
							<label class="conditional-toggle">
								<input type="checkbox" data-name="vp_woo_pont_note[X][condition_enabled]" <?php checked( $note['conditional'] ); ?> class="condition" value="yes">
								<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
							</label>
							<a href="#" class="delete-note"><?php esc_html_e('delete', 'vp-woo-pont'); ?></a>
						</div>

						<div class="vp-woo-pont-settings-note-if" <?php if(!$note['conditional']): ?>style="display:none"<?php endif; ?>>
							<div class="vp-woo-pont-settings-note-if-header">
								<label>
									<span><?php esc_html_e('Add note, if', 'vp-woo-pont'); ?></span>
								</label>
								<select data-name="vp_woo_pont_note[X][logic]">
									<option value="and" <?php if(isset($note['logic'])) selected( $note['logic'], 'and' ); ?>><?php esc_html_e('All', 'vp-woo-pont'); ?></option>
									<option value="or" <?php if(isset($note['logic'])) selected( $note['logic'], 'or' ); ?>><?php esc_html_e('One', 'vp-woo-pont'); ?></option>
								</select>
								<span><?php esc_html_e('of the following match', 'vp-woo-pont'); ?></span>
							</div>
							<ul class="vp-woo-pont-settings-note-if-options conditions" <?php if(isset($note['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($note['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-note-add">
			<a href="#" class="add button"><?php esc_html_e('Add new note', 'vp-woo-pont'); ?></a>
		</div>
	</td>
</tr>

<script type="text/html" id="vp_woo_pont_note_sample_row">
	<div class="vp-woo-pont-settings-note vp-woo-pont-settings-repeat-item">
		<textarea placeholder="<?php esc_attr_e('Note', 'vp-woo-pont'); ?>" data-name="vp_woo_pont_note[X][note]"></textarea>

		<div class="vp-woo-pont-settings-note-title">
			<div class="select-field-label">
				<label>
					<span><?php esc_html_e('Add note to this provider:', 'vp-woo-pont'); ?></span>
				</label>
			</div>
			<select data-name="vp_woo_pont_note[X][provider]">
				<option value=""><?php esc_html_e('Select provider', 'vp-woo-pont'); ?></option>
				<?php foreach ($providers as $provider_id => $provider): ?>
					<option value="<?php echo esc_attr($provider_id); ?>"><?php echo esc_html($provider); ?></option>
				<?php endforeach; ?>
			</select>
			<label class="conditional-toggle">
				<input type="checkbox" data-name="vp_woo_pont_note[X][condition_enabled]" class="condition" value="yes">
				<span><?php esc_html_e('Conditional logic', 'vp-woo-pont'); ?></span>
			</label>
			<a href="#" class="delete-note"><?php esc_html_e('delete', 'vp-woo-pont'); ?></a>
		</div>

		<div class="vp-woo-pont-settings-note-if" style="display:none">
			<div class="vp-woo-pont-settings-note-if-header">
				<label>
					<span><?php esc_html_e('Add note, if', 'vp-woo-pont'); ?></span>
				</label>
				<select data-name="vp_woo_pont_note[X][logic]">
					<option value="and"><?php esc_html_e('All', 'vp-woo-pont'); ?></option>
					<option value="or"><?php esc_html_e('One', 'vp-woo-pont'); ?></option>
				</select>
				<span><?php esc_html_e('of the following match', 'vp-woo-pont'); ?></span>
			</div>
			<ul class="vp-woo-pont-settings-note-if-options conditions"></ul>
		</div>
	</div>
</script>

<?php echo VP_Woo_Pont_Conditions::get_sample_row('notes'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML template, escaped in get_sample_row(). ?>