<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_values = get_option('vp_woo_pont_enabled_providers');
$carrier_labels = VP_Woo_Pont_Helpers::get_external_provider_groups();

//Backward compat
if(!$saved_values) {
	$saved_values = $data['default'];
}

//Get all available providers
$providers = $data['options'];
$all_provider_types = array_keys($providers);

//Get a list of all providers that are not in the saved values
$disabled_providers = array_diff($all_provider_types, $saved_values);

//Get 5 random providers from the disabled providers
$random_disabled_providers = array_rand($disabled_providers, min(5, count($disabled_providers)));

//Limit the all providers to 20 items
$all_provider_types = array_slice($all_provider_types, 0, 20);

//To show json links
$download_folders = VP_Woo_Pont_Helpers::get_download_folder();

//Get free shipping, coupon and cod options
$free_shipping_coupon = get_option('vp_woo_pont_free_shipping_coupon', array());
$free_shipping = get_option('vp_woo_pont_free_shipping', array());
$cod_disabled = get_option('vp_woo_pont_cod_disabled', array());
$custom_points = get_option('vp_woo_pont_points', array());

//Get a list of all providers grouped by carrier
$provider_groups = VP_Woo_Pont_Helpers::get_external_provider_groups();
$provider_groups['custom'] = get_option('vp_woo_pont_custom_title', __('Store Pickup', 'vp-woo-pont'));
$supported_providers = VP_Woo_Pont_Helpers::get_supported_providers();
$provider_subgroups = apply_filters('vp_woo_pont_provider_subgroups',array(
	'gls' => array('locker', 'shop'),
	'packeta' => array('zbox', 'zpont'),
	'postapont' => array('posta', 'automata', 'postapont'),
	'expressone' => array('alzabox', 'omv', 'packeta', 'exobox'),
	'dpd' => array('alzabox', 'parcelshop'),
	'sameday' => array('easybox', 'pick-pack-pont'),
	'foxpost' => array('foxpost'),
));

$all_available_providers = array();
foreach ($provider_groups as $provider_id => $provider_label) {
	$all_available_providers[$provider_id] = array(
		'label' => $provider_label,
		'options' => array(),
		'id' => $provider_id
	);
	if(isset($provider_subgroups[$provider_id])) {
		foreach ($provider_subgroups[$provider_id] as $group) {
			$all_available_providers[$provider_id]['options'][] = array(
				'id' => $provider_id.'_'.$group,
				'name' => $supported_providers[$provider_id.'_'.$group]
			);
		}
	} else {
		$all_available_providers[$provider_id]['options'][] = array(
			'id' => $provider_id,
			'name' => $provider_label
		);
	}
}

//Sort ascending by label
uasort($all_available_providers, function($a, $b) {
	return strcmp($a['label'], $b['label']);
});

//Move kvikk to the top
if(isset($all_available_providers['kvikk'])) {
	$kvikk = $all_available_providers['kvikk'];
	unset($all_available_providers['kvikk']);
	$all_available_providers = array('kvikk' => $kvikk) + $all_available_providers;
}

//Convert to a data attribute
$all_available_providers = wp_json_encode(array_values($all_available_providers));
$providers_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $all_available_providers ) : _wp_specialchars( $all_available_providers, ENT_QUOTES, 'UTF-8', true );

// Sort the providers array based on the selected providers
$sortedProviders = [];
foreach ($saved_values as $provider) {
	if (isset($providers[$provider])) {
		$sortedProviders[$provider] = $providers[$provider];
		unset($providers[$provider]);
	}
}
$sortedProviders += $providers;

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html($data['title']); ?></th>
	<td class="vp-woo-pont-providers-wrapper">

		<?php do_action('vp_woo_pont_providers_before_table'); ?>

		<?php if($this->is_local_pickup_feature_needed()): ?>
		<div class="notice notice-info notice-alt inline vp-woo-pont-providers-block-notice">
			<p><?php printf( esc_html__( 'Since you are using the block based checkout page, you need to enable Woo\'s local pickup feature to show these options during checkout. To learn more about this, %1$sclick here%2$s.', 'vp-woo-pont' ), '<a href="https://visztpeter.me/kb-article/csomagpontok-es-cimkek/penztar-blokk-kompatibilitas/" target="_blank">', '</a>' ); ?></p>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pickup_location' ) ); ?>" class="button button-primary"><?php esc_html_e('Enable local pickup', 'vp-woo-pont'); ?></a></p>
		</div>
		<?php endif; ?>

		<div class="vp-woo-pont-providers-blank-state">
			<p class="vp-woo-pont-providers-blank-state-copy"><?php esc_html_e('Select which providers you want to show to your customers.', 'vp-woo-pont'); ?></p>
			<div class="vp-woo-pont-providers-blank-state-icons">
				<?php foreach($all_provider_types as $provider_id): ?>
					<?php if(!in_array($provider_id, $saved_values)): ?>
						<i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<a href="#" class="vp-woo-pont-providers-add-button button button-primary" data-providers="<?php echo $providers_attr; ?>"><?php esc_html_e('Select providers', 'vp-woo-pont'); ?></a>
		</div>

		<table class="vp-woo-pont-providers wc_gateways widefat" cellspacing="0">
            <thead>
                <tr>
					<th></th>
					<th></th>
					<th><?php esc_html_e('Name', 'vp-woo-pont'); ?></th>
					<th><?php esc_html_e('Database', 'vp-woo-pont'); ?></th>
					<th><?php esc_html_e('Free with coupon', 'vp-woo-pont'); ?> <?php echo wc_help_tip(__('Make this option free if the cart contains a free shipping coupon.', 'vp-woo-pont')); ?></th>
					<th><?php esc_html_e('Disable COD', 'vp-woo-pont'); ?> <?php echo wc_help_tip(__('Hide the COD payment method if the provider is selected.', 'vp-woo-pont')); ?></th>
					<th></th>
                </tr>
            </thead>
			<tbody class="vp-woo-pont-providers-rows">
                <?php foreach ( $sortedProviders as $provider_id => $provider_name ): ?>
				<tr class="vp-woo-pont-provider-row <?php if(in_array($provider_id, $saved_values)): ?> selected<?php endif; ?>" data-provider="<?php echo esc_attr($provider_id); ?>">
					<td class="vp-woo-pont-providers-cell-order sort">
						<a href="#"><span class="dashicons dashicons-menu"></span></a>
					</td>
					<td class="vp-woo-pont-providers-cell-icon">
						<i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
					</td>
					<td class="vp-woo-pont-providers-cell-name">
						<input type="checkbox" name="vp_woo_pont_enabled_providers[]" value="<?php echo esc_attr($provider_id); ?>" <?php checked(in_array($provider_id, $saved_values)); ?>>
						<?php echo $provider_name; ?>
					</td>
					<td class="vp-woo-pont-providers-cell-database">

						<?php $files = get_option('_vp_woo_pont_db_'.$provider_id); ?>
						<?php if($provider_id == 'custom'): ?>
						<div class="vp-woo-pont-providers-cell-database-custom">
							<?php if(count($custom_points) > 0): ?>
								<span class="dashicons dashicons-yes-alt"></span>
							<?php else: ?>
								<span class="dashicons dashicons-warning"></span>
							<?php endif; ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=points' ) ); ?>">
								<?php if(count($custom_points) == 0): ?>
									<?php esc_html_e('Setup custom points', 'vp-woo-pont'); ?>
								<?php else: ?>
									<?php echo sprintf( _n( '%s custom point', '%s custom point', count($custom_points), 'vp-woo-pont' ), count($custom_points) ); ?>
								<?php endif; ?>
							</a>
						</div>
						<?php else: ?>
						<div data-provider="<?php echo esc_attr($provider_id); ?>">
							<ul>
								<?php if($files && count($files) > 0): ?>
									<li><span class="dashicons dashicons-yes-alt"></span></li>
									<?php foreach($files as $file): ?>
										<?php if(isset($file['file'])): ?>
											<li>
												<?php $url = $download_folders['url'] . $file['file']; ?>
												<a target="_blank" class="download-link" href="<?php echo esc_url($url); ?>" <?php if(isset($file['count'])): ?>data-qty="<?php echo esc_attr($file['count']); ?>"<?php endif; ?>><?php echo esc_html($file['file']); ?></a>
											</li>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
								<?php if(!$files || count($files) == 0): ?>
									<li><span class="dashicons dashicons-warning"></span></li>
									<li><span class="no-file"><?php esc_html_e('No database found', 'vp-woo-pont'); ?></span></li>
								<?php endif; ?>
							</ul>
							
							<a href="#" data-provider="<?php echo esc_attr($provider_id); ?>" class="import">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e('Refresh', 'vp-woo-pont'); ?>
							</a>
						</div>
						<?php endif; ?>
					</td>
					<td class="vp-woo-pont-providers-cell-coupon">
						<input type="checkbox" name="vp_woo_pont_free_shipping[]" value="<?php echo esc_attr($provider_id); ?>" <?php checked(in_array($provider_id, $free_shipping)); ?>>						
					</td>
					<td class="vp-woo-pont-providers-cell-cod">
						<input type="checkbox" name="vp_woo_pont_cod_disabled[]" value="<?php echo esc_attr($provider_id); ?>" <?php checked(in_array($provider_id, $cod_disabled)); ?>>
					</td>
					<td class="vp-woo-pont-providers-cell-action">
						<div class="vp-woo-pont-providers-cell-action-buttons">
							<?php if($provider_id == 'custom'): ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=points' ) ); ?>" class="button vp-woo-pont-provider-edit button-edit"><?php esc_html_e('Edit', 'vp-woo-pont'); ?></a>
							<?php endif; ?>
							<a href="#" class="button vp-woo-pont-provider-delete button-delete"><?php esc_html_e('Delete', 'vp-woo-pont'); ?></a>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="7">
						<div class="vp-woo-pont-providers-add">
							<a href="#" class="vp-woo-pont-providers-add-button button button-primary" data-providers="<?php echo $providers_attr; ?>"><?php esc_html_e('Enable more providers', 'vp-woo-pont'); ?></a>
							<a href="https://visztpeter.me/kb-article/csomagpontok-es-cimkek/mukodik-hazhozszallitassal-is/" target="_blank" class="vp-woo-pont-providers-add-button button button-secondary"><?php esc_html_e('Setup home delivery', 'vp-woo-pont'); ?></a>
							<div class="vp-woo-pont-providers-add-list">
								<?php if(is_array($random_disabled_providers)): ?>
									<?php foreach ( $random_disabled_providers as $provider_id ): ?>
										<i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-<?php echo esc_attr($disabled_providers[$provider_id]); ?>"></i>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>	

		<?php include( dirname( __FILE__ ) . '/html-modal-add-provider.php' ); ?>
    </td>
</tr>

