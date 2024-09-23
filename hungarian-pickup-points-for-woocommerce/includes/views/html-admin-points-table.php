<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_points');
$providers = VP_Woo_Pont_Helpers::get_supported_providers();
$days = array(
	__('Monday', 'vp-woo-pont'),
	__('Tuesday', 'vp-woo-pont'),
	__('Wednesday', 'vp-woo-pont'),
	__('Thursday', 'vp-woo-pont'),
	__('Friday', 'vp-woo-pont'),
	__('Saturday', 'vp-woo-pont'),
	__('Sunday', 'vp-woo-pont')
);

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="vp-woo-pont-settings vp-woo-pont-settings-points-list">
			<?php if($saved_values): ?>
				<?php foreach ($saved_values as $key => $point): ?>
					<div class="vp-woo-pont-settings-point">
						<div class="vp-woo-pont-settings-point-header">
							<div class="title">
								<span class="dashicons dashicons-arrow-up"></span>
								<span class="point-value-title"><?php echo esc_html($point['name']); ?> #<?php echo esc_html($point['id']); ?></span>
							</div>
							<a href="#" class="delete-point"><?php _e('delete', 'vp-woo-pont'); ?></a>
						</div>
						<ul class="vp-woo-pont-settings-point-options">
							<li>
								<label><?php esc_html_e('Unique ID', 'vp-woo-pont'); ?></label>
								<input type="text" class="point-value-id" data-name="vp_woo_pont_points[X][id]" value="<?php echo esc_attr($point['id']); ?>" <?php if($point['provider'] != 'custom'): ?>readonly<?php endif; ?>>
								<input type="hidden" class="point-value-provider" data-name="vp_woo_pont_points[X][provider]" value="<?php echo esc_attr($point['provider']); ?>">
							</li>
							<li>
								<label><?php esc_html_e('Pickup point name', 'vp-woo-pont'); ?></label>
								<input type="text" class="point-value-name" data-name="vp_woo_pont_points[X][name]" value="<?php echo esc_attr($point['name']); ?>">
							</li>
							<li class="coordinates">
								<label><?php esc_html_e('Coordinates', 'vp-woo-pont'); ?></label>
								<div class="field">
									<span class="dashicons dashicons-location"></span>
									<input type="text" class="point-value-coordinates" data-name="vp_woo_pont_points[X][coordinates]" value="<?php echo esc_attr($point['lat']); ?>;<?php echo esc_attr($point['lon']); ?>">
								</div>
							</li>
							<li>
								<label><?php esc_html_e('Postcode', 'vp-woo-pont'); ?></label>
								<input type="text" class="point-value-zip" data-name="vp_woo_pont_points[X][zip]" value="<?php echo esc_attr($point['zip']); ?>">
							</li>
							<li>
								<label><?php esc_html_e('Address', 'vp-woo-pont'); ?></label>
								<input type="text" class="point-value-addr" data-name="vp_woo_pont_points[X][addr]" value="<?php echo esc_attr($point['addr']); ?>">
							</li>
							<li>
								<label><?php esc_html_e('City', 'vp-woo-pont'); ?></label>
								<input type="text" class="point-value-city" data-name="vp_woo_pont_points[X][city]" value="<?php echo esc_attr($point['city']); ?>">
							</li>
							<li>
								<label><?php esc_html_e('E-Mail address', 'vp-woo-pont'); ?></label>
								<input type="text" class="point-value-email" data-name="vp_woo_pont_points[X][email]" value="<?php if(isset($point['email'])) echo esc_attr($point['email']); ?>">
							</li>
							<li class="note">
								<label><?php esc_html_e('Note', 'vp-woo-pont'); ?></label>
								<textarea class="point-value-comment" data-name="vp_woo_pont_points[X][comment]"><?php echo esc_textarea($point['comment']); ?></textarea>
							</li>
							<li class="openhours">
								<label><?php echo esc_html__('Opening hours', 'vp-woo-pont'); ?></label>
								<?php foreach($days as $index => $day): ?>
									<input type="text" class="point-value-openhours" data-name="vp_woo_pont_points[X][openhours][<?php echo esc_attr($index+1); ?>]" value="<?php if(isset($point['hours']) && isset($point['hours'][$index+1])) echo esc_attr($point['hours'][$index+1]); ?>" placeholder="<?php echo esc_attr($day); ?>">
								<?php endforeach; ?>
							</li>
							<li class="hide">
								<label>
									<input type="checkbox" class="point-value-hidden" data-name="vp_woo_pont_points[X][hidden]" <?php checked( $point['hidden'] ); ?>>
									<span><?php esc_html_e('Hide pickup point', 'vp-woo-pont'); ?></span>
								</label>
							</li>
						</ul>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="vp-woo-pont-settings-points-add">
			<a href="#" class="add button"><?php _e('Add custom pickup point', 'vp-woo-pont'); ?></a>
			<a href="#" class="import" data-type="points"><span class="dashicons dashicons-database-import"></span> <span><?php _e('Import', 'vp-woo-pont'); ?></span></a>
			<a href="#" class="export" data-type="points" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_settings_export" ); ?>"><span class="dashicons dashicons-database-export"></span> <span><?php _e('Export', 'vp-woo-pont'); ?></span></a>
		</div>
		<p class="description"><?php echo esc_html($data['desc']); ?></p>	

		<script type="text/html" id="vp_woo_pont_point_sample_row">
			<div class="vp-woo-pont-settings-point">
				<div class="vp-woo-pont-settings-point-header">
					<div class="title">
						<span class="dashicons dashicons-arrow-up"></span>
						<span class="point-value-title"></span>
					</div>
					<a href="#" class="delete-point"><?php _e('delete', 'vp-woo-pont'); ?></a>
				</div>
				<ul class="vp-woo-pont-settings-point-options">
					<li>
						<label>Egyedi azonosító</label>
						<input type="text" class="point-value-id" data-name="vp_woo_pont_points[X][id]">
						<input type="hidden" class="point-value-provider" data-name="vp_woo_pont_points[X][provider]">
					</li>
					<li>
						<label>Átvevőhely neve</label>
						<input type="text" class="point-value-name" data-name="vp_woo_pont_points[X][name]">
					</li>
					<li class="coordinates">
						<label>Koordináták</label>
						<div class="field">
							<span class="dashicons dashicons-location"></span>
							<input type="text" class="point-value-coordinates" data-name="vp_woo_pont_points[X][coordinates]">
						</div>
					</li>
					<li>
						<label>Irányítószám</label>
						<input type="text" class="point-value-zip" data-name="vp_woo_pont_points[X][zip]">
					</li>
					<li>
						<label>Cím</label>
						<input type="text" class="point-value-addr" data-name="vp_woo_pont_points[X][addr]">
					</li>
					<li>
						<label>Város</label>
						<input type="text" class="point-value-city" data-name="vp_woo_pont_points[X][city]">
					</li>
					<li>
						<label><?php esc_html_e('E-Mail address', 'vp-woo-pont'); ?></label>
						<input type="text" class="point-value-email" data-name="vp_woo_pont_points[X][email]">
					</li>
					<li class="note">
						<label>Megjegyzés</label>
						<textarea class="point-value-comment" data-name="vp_woo_pont_points[X][comment]"></textarea>
					</li>
					<li class="openhours">
						<label>Nyitvatartás</label>
						<?php foreach($days as $index => $day): ?>
						<input type="text" class="point-value-openhours" data-name="vp_woo_pont_points[X][openhours][<?php echo esc_attr($index); ?>]" placeholder="<?php echo esc_attr($day); ?>">
						<?php endforeach; ?>
					</li>
					<li class="hide">
						<label>
							<input type="checkbox" class="point-value-hidden" data-name="vp_woo_pont_points[X][hidden]">
							<span>Átvevőhely elrejtése</span>
						</label>
					</li>
				</ul>
			</div>
		</script>

		<?php include( dirname( __FILE__ ) . '/html-modal-coordinates.php' ); ?>
		<?php include( dirname( __FILE__ ) . '/html-modal-import.php' ); ?>

	</td>
</tr>

