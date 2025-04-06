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
			'title' => __( 'Shipping in % calculation', 'vp-woo-pont' ),
			'desc' => __( 'Add the shipping cost to the cart total when calculating the % fee', 'vp-woo-pont' ),
			'type' => 'checkbox',
			'id' => 'cod_fee_include_shipping',
		),
		array(
			'title'    => __( 'Fee tax class', 'vp-woo-pont' ),
			'desc'     => __( 'Optionally control which tax class the COD fee gets, or leave it so tax is based on the cart items themselves.', 'vp-woo-pont' ),
			'id'       => 'cod_tax_class',
			'css'      => 'min-width:150px;',
			'default'  => '',
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'options'  => array( 'inherit' => __( 'Tax class based on cart items', 'vp-woo-pont' ) ) + wc_get_product_tax_class_options(),
			'desc_tip' => true,
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
