<?php
defined( 'ABSPATH' ) || exit;

$settings = array(
	array(
		'title' => __( 'Fees', 'vp-woo-pont' ),
		'type' => 'title',
		'desc' => sprintf( __( 'Setup fees based on various conditions for this payment method. Available in the <a href="%s">PRO version</a>.', 'vp-woo-pont' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_labels' ) )),
	),
	array(
		'type' => 'sectionend',
	)
);

if(VP_Woo_Pont_Pro::is_pro_enabled()) {
	$settings = array(
		array(
			'title' => __( 'Fees', 'vp-woo-pont' ),
			'type' => 'title',
			'desc' => __( 'Setup fees based on various conditions for this payment method.', 'vp-woo-pont' ),
		),
		array(
			'title'       => __( 'Fee name', 'vp-woo-pont' ),
			'type'        => 'text',
			'default'     => __( 'COD Fee', 'vp-woo-pont' ),
			'id' => 'cod_fee_name'
		),
		array(
			'title' => __( 'Fees', 'vp-woo-pont' ),
			'type' => 'vp_cod_fees',
			'id' => 'cod_fees'
		),
		array(
			'type' => 'sectionend',
		)
	);
}

return apply_filters('vp_woo_pont_cod_settings', $settings);
