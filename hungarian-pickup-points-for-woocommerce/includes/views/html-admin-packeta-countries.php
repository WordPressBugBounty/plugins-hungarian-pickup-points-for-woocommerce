<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_values = get_option('vp_woo_pont_packeta_countries', array());

//Backward compat
if(!$saved_values) {
	$saved_values = $data['default'];
}

$key = 'packeta_countries';

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html($data['title']); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<ul class="vp-woo-pont-settings-checkbox-group vp-woo-pont-settings-checkbox-group-<?php echo esc_attr($key); ?>">
		<?php foreach ($data['options'] as $option_id => $option): ?>
			<li class="vp-woo-pont-settings-checkbox-<?php echo esc_attr($key); ?>-<?php echo esc_attr($option_id); ?>">
				<label>
					<input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="vp_woo_pont_<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($option_id); ?>" <?php checked(in_array($option_id, $saved_values)); ?>  />
					<span><?php echo esc_html($option); ?></span>
				</label>
			</li>
		<?php endforeach; ?>
		</ul>
		<p class="description"><?php echo esc_html($data['desc']); ?></p>	
		<a href="#" class="reload-packeta-carriers" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-packeta-get-carriers' )?>">
			<span class="dashicons dashicons-update"></span>
			<span><?php esc_html_e('Refresh providers', 'vp-woo-pont'); ?></span>
		</a>
	</td>
</tr>
