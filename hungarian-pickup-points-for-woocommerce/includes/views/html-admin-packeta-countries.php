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
		<table class="vp-woo-pont-settings-inline-table vp-woo-pont-settings-inline-table-packeta-countries">
			<thead>
				<tr>
					<th><?php esc_html_e('Country', 'vp-woo-pont'); ?></th>
					<th><?php esc_html_e('Carriers', 'vp-woo-pont'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($data['options'] as $country_code => $providers): ?>
				<?php if (is_array($providers)): ?>
					<?php $provider_count = count($providers); ?>
					<?php $first_row = true; ?>
					<?php foreach ($providers as $provider_id => $provider_name): ?>
						<tr>
							<?php if ($first_row): ?>
								<td class="vp-woo-pont-settings-inline-table-packeta-countries-country" rowspan="<?php echo esc_attr($provider_count); ?>">
									<strong><?php echo esc_html(WC()->countries->countries[ $country_code ]); ?></strong>
								</td>
								<?php $first_row = false; ?>
							<?php endif; ?>
							<td class="vp-woo-pont-settings-inline-table-packeta-countries-option">
								<label>
									<input type="checkbox" name="vp_woo_pont_<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($country_code); ?>:<?php echo esc_attr($provider_id); ?>" <?php checked(in_array($country_code.':'.$provider_id, $saved_values)); ?>  />
									<span><?php echo esc_html($provider_name); ?></span>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php echo esc_html($data['desc']); ?></p>	
		<a href="#" class="reload-packeta-carriers" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-packeta-get-carriers' )?>">
			<span class="dashicons dashicons-update"></span>
			<span><?php esc_html_e('Refresh providers', 'vp-woo-pont'); ?></span>
		</a>
	</td>
</tr>
