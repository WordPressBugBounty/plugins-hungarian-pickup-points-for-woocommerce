<?php
defined( 'ABSPATH' ) || exit;

$pro_required = false;
$pro_icon = false;
if(!VP_Woo_Pont_Pro::is_pro_enabled()) {
	$pro_required = true;
	$pro_icon = '<i class="vp_woo_pont_pro_label">PRO</i>';
}

$settings = array(
	array(
		'title' => __( 'Carriers', 'vp-woo-pont' ),
		'type' => 'title',
        'id' => 'carriers',
		'desc' => __( 'Enable and configure your shipping carriers. You can use these to show parcel lockers and pickup points during checkout and use them to generate shipping labels.', 'vp-woo-pont' ),
	),
	array(
		'type' => 'vp_carriers',
	),
    array(
		'type' => 'sectionend',
        'id' => 'carriers'
	),
);

return apply_filters('vp_woo_pont_carriers_settings', $settings);
