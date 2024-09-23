<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enabled_providers = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers', array());
$providers_for_home_delivery = VP_Woo_Pont_Helpers::get_supported_providers_for_home_delivery();
foreach ($providers_for_home_delivery as $id => $name) {
    if(!VP_Woo_Pont_Helpers::is_provider_configured($id)) continue;
    $enabled_providers[] = $id;
}

?>

<tr valign="top">
	<th scope="row" class="titledesc">
        <label><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
    </th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
        <ul class="vp-woo-pont-settings-email-content">
            <?php foreach ($enabled_providers as $provider_id): ?>
                <li>
                    <label>
                        <i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
                        <span><?php echo esc_html(VP_Woo_Pont_Helpers::get_provider_name($provider_id, true)); ?></span>
                        <?php if(in_array($provider_id, array_keys($providers_for_home_delivery))): ?>
                            <span> - <?php echo esc_html__('Home delivery', 'vp-woo-pont'); ?></span>
                        <?php endif; ?>
                    </label>
                    <?php $value = $this->get_option( $key ); ?>
                    <textarea rows="3" cols="20" class="input-text wide-input" type="textarea" name="<?php echo esc_attr($field_key); ?>[<?php echo esc_attr($provider_id); ?>]" style="width:400px; height: 75px;"><?php if(isset($value[$provider_id])) echo esc_textarea( $this->get_option( $key )[$provider_id] ); ?></textarea>
                </li>
            <?php endforeach; ?>
        </ul>
    </td>
</tr>   

