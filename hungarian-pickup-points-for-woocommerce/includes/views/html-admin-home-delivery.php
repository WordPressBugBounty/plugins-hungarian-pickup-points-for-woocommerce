<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('vp_woo_pont_home_delivery', array());

//When to generate these documents
$shipping_methods = VP_Woo_Pont_Helpers::get_available_shipping_methods();
$providers = VP_Woo_Pont_Helpers::get_supported_providers_for_home_delivery();

//If theres no configured provider, we dont show this option
$show_option = false;
foreach ($providers as $provider_id => $label) {
	if(VP_Woo_Pont_Helpers::is_provider_configured($provider_id)){
		$show_option = true;
		break;
	}
}

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<table class="vp-woo-pont-settings-inline-table vp-woo-pont-settings-home-deliveries">
			<thead>
				<tr>
					<th></th>
					<?php if($show_option): ?>
						<th>
							<strong><?php esc_html_e('None', 'vp-woo-pont'); ?></strong>
						</th>
					<?php endif; ?>
					<?php foreach ($providers as $provider_id => $label): ?>
						<?php if(VP_Woo_Pont_Helpers::is_provider_configured($provider_id)): ?>
							<th>
								<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
								<?php echo esc_html($label); ?>
							</th>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if(!$show_option): ?>
						<th>
							<strong><?php echo esc_html__( 'Carriers', 'vp-woo-pont' ); ?></strong>
						</th>
					<?php endif; ?>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($shipping_methods as $shipping_method_id => $shipping_method_name): ?>
				<tr>
					<th>
						<strong><?php echo esc_html($shipping_method_name); ?></strong>
					</th>
					<?php if($show_option): ?>
						<td>
							<input type="radio" id="provider-<?php echo esc_attr($shipping_method_id); ?>-0" <?php if(isset($saved_values[$shipping_method_id])) checked($saved_values[$shipping_method_id],''); ?> name="vp_woo_pont_home_delivery[<?php echo esc_attr($shipping_method_id); ?>]" value="">
							<label class="indicator" for="provider-<?php echo esc_attr($shipping_method_id); ?>-0"></label>
						</td>
					<?php endif; ?>
					<?php foreach ($providers as $provider_id => $label): ?>
						<?php if(VP_Woo_Pont_Helpers::is_provider_configured($provider_id)): ?>
						<td>
							<input type="radio" id="provider-<?php echo esc_attr($shipping_method_id); ?>-<?php echo esc_attr($provider_id); ?>" <?php if(isset($saved_values[$shipping_method_id])) checked($saved_values[$shipping_method_id], $provider_id); ?> name="vp_woo_pont_home_delivery[<?php echo esc_attr($shipping_method_id); ?>]" value="<?php echo esc_attr($provider_id); ?>">
							<label class="indicator" for="provider-<?php echo esc_attr($shipping_method_id); ?>-<?php echo esc_attr($provider_id); ?>"></label>
						</td>
						<?php endif; ?>
					<?php endforeach; ?>

					<?php if(!$show_option && $shipping_method_id == key($shipping_methods)): ?>
						<td rowspan="2">
							<div class="vp-woo-pont-settings-home-deliveries-placeholder-icons">
								<?php foreach (array_rand($providers, 5) as $provider_id): ?>
									<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
								<?php endforeach; ?>
							</div>
							<p class="vp-woo-pont-settings-home-deliveries-placeholder-text"><?php printf( __( 'To pair your carriers, configure them first by setting up your authentication details in the <a href="%s">settings</a>.', 'vp-woo-pont' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_carriers' ) ) ); ?></p>						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php echo esc_html($data['desc']); ?></p>
	</td>
</tr>
