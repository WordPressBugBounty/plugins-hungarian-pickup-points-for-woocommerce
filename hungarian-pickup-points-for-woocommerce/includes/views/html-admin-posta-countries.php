<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_posta_countries');

//Set default value if nothing saved yet
if(empty($saved_values)) {
	$saved_values = array();
}

//Get country list
$shipping_countries = WC()->countries->get_shipping_countries();

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp">
		<table class="vp-woo-pont-settings-inline-table vp-woo-pont-settings-inline-table-posta-countries">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Service type', 'vp-woo-pont' ); ?></th>
					<th><?php esc_html_e( 'Supported countries', 'vp-woo-pont' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data['options'] as $service_id => $service_label): ?>
					<tr>
						<td class="vp-woo-pont-posta-table-service">
							<strong><?php echo esc_html($service_label); ?>(<?php echo esc_attr($service_id); ?>)</strong>
						</td>
						<td class="vp-woo-pont-posta-table-countries">
							<select name="vp_woo_pont_posta_countries[<?php echo esc_attr($service_id); ?>][]" class="wc-enhanced-select" multiple="multiple">
								<option <?php selected((isset($saved_values[$service_id]) && in_array('default', $saved_values[$service_id]))); ?> value="default"><?php echo esc_html('Default', 'vp-woo-pont'); ?></option>
								<?php foreach ( $shipping_countries as $country_code => $country ): ?>
									<option <?php selected((isset($saved_values[$service_id]) && in_array($country_code, $saved_values[$service_id]))); ?> value="<?php echo esc_attr( $country_code ); ?>"><?php echo esc_html( $country ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if($data['desc']): ?>
		<p class="description"><?php echo esc_html($data['desc']); ?></p>	
		<?php endif; ?>
	</td>
</tr>
