<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$key = str_replace('vp_woo_pont_', '', $data['id']);
$saved_values = get_option($data['id']);
$carrier_labels = VP_Woo_Pont_Helpers::get_external_provider_groups();

//Backward compat
if(!$saved_values) {
	$saved_values = $data['default'];
}

// Sort the providers array based on the selected providers
if($key == 'enabled_providers') {
	$sortedProviders = [];
	$providers = $data['options'];
	foreach ($saved_values as $provider) {
		if (isset($providers[$provider])) {
			$sortedProviders[$provider] = $providers[$provider];
			unset($providers[$provider]);
		}
	}
	$sortedProviders += $providers;
	$data['options'] = $sortedProviders;
}

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html($data['title']); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">

		<ul class="vp-woo-pont-settings-checkbox-group vp-woo-pont-settings-checkbox-group-<?php echo esc_attr($key); ?>">
		<?php foreach ($data['options'] as $option_id => $option): ?>
			<li class="vp-woo-pont-settings-checkbox-<?php echo esc_attr($key); ?>-<?php echo esc_attr($option_id); ?>">
				<label>
					<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($option_id); ?>"></i>
					<input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="vp_woo_pont_<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($option_id); ?>" <?php checked(in_array($option_id, $saved_values)); ?>  />
					<span><?php echo esc_html($option); ?></span>
				</label>
			</li>
		<?php endforeach; ?>
		</ul>

		<p class="description"><?php echo esc_html($data['desc']); ?></p>
	</td>
</tr>