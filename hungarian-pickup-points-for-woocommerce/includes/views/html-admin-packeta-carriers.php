<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_packeta_carriers');

//Set default value if nothing saved yet
if(empty($saved_values)) {
	$saved_values = array('HU'=>0);
}

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp">
		<table class="vp-woo-pont-settings-inline-table vp-woo-pont-settings-inline-table-packeta-carriers">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Country', 'vp-woo-pont' ); ?></th>
					<th><?php esc_html_e( 'Carrier', 'vp-woo-pont' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($saved_values as $country => $carrier): ?>
					<tr>
						<td class="vp-woo-pont-packeta-carriers-table-country">
							<?php
							woocommerce_form_field( 'vp_woo_pont_packeta_carriers[][country]', array(
								'type' => 'country',
								'custom_attributes' => array(
									'data-name' => 'vp_woo_pont_packeta_carriers[X][country]'
								)
							), $country );
							?>
						</td>
						<td class="vp-woo-pont-packeta-carriers-table-carrier">
							<select data-name="vp_woo_pont_packeta_carriers[X][carrier]">
								<option value="">-</option>
								<?php foreach ($data['options'] as $country => $carriers): ?>
									<optgroup label="<?php echo esc_attr($country); ?>">
										<?php foreach ($carriers as $id => $label): ?>
											<option <?php selected($id, $carrier); ?> value="<?php echo esc_attr($id); ?>"><?php echo esc_attr($label); ?></option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<a href="#" class="delete-row"><span class="dashicons dashicons-dismiss"></span></a>
							<a href="#" class="add-row"><span class="dashicons dashicons-plus-alt"></span></a>
						</td>
					</tr>
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
