<?php
defined( 'ABSPATH' ) || exit;

$pro_required = false;
$pro_icon = false;
if(!VP_Woo_Pont_Pro::is_pro_enabled()) {
	$pro_required = true;
	$pro_icon = '<i class="vp_woo_pont_pro_label">PRO</i>';

	return array(
		array(
			'type' => 'vp_pro_notice',
		)
	);
}

$settings = array(
	array(
		'title' => __( 'Label generator', 'vp-woo-pont' ),
		'type' => 'title',
		'desc' => __( 'Settings related to automations and labels. Every function in this section will only work if you have the PRO version activated.', 'vp-woo-pont' ),
	),
	array(
		'type' => 'vp_home_delivery',
		'title' => __('Home delivery providers', 'vp-woo-pont'),
		'id' => 'home_delivery_providers',
		'desc' => __( 'Pair your existing shipping methods with home delivery providers. If you are using the automated label generation, it will use this provider to generate the label if the shipping method is a match.', 'vp-woo-pont' ),
	),
	array(
		'type' => 'vp_automations',
		'title' => __('Automations', 'vp-woo-pont'),
		'id' => 'automations',
	),
	array(
		'type' => 'select',
		'class' => 'wc-enhanced-select vp-woo-pont-toggle-select-field',
		'title' => __( 'Reference number', 'vp-woo-pont' ),
		'desc_tip' => __( 'With most providers, theres an extra field where you can store a reference number to connect the label with an order. You can select what value to use in this field.', 'vp-woo-pont' ),
		'default' => 'order_number',
		'options' => array(
			'order_number' => __( 'Order number', 'vp-woo-pont' ),
			'order_id' => __( 'Order ID', 'vp-woo-pont' ),
			'invoice_number' => __( 'Invoice number', 'vp-woo-pont' ),
			'custom' => __( 'Custom meta', 'vp-woo-pont' ),
		),
		'id' => 'label_reference_number',
	),
	array(
		'title' => __( 'Reference number meta key', 'vp-woo-pont' ),
		'type' => 'text',
		'id' => 'label_reference_number_custom',
		'desc' => __( "Enter a custom order meta key and it will use that value as the reference number.", 'vp-woo-pont' ),
	),
	array(
		'title' => __('Package contents text', 'vp-woo-pont'),
		'type' => 'textarea',
		'desc_tip' => __('You can use the following shortcodes: {order_number}, {order_items}, {customer_note}, {products_sku}, {invoice_number}', 'vp-woo-pont'),
		'id' => 'package_contents',
	),
	array(
		'type' => 'select',
		'class' => 'wc-enhanced-select',
		'title' => __( 'Set order status', 'vp-woo-pont' ),
		'options' => self::get_order_statuses(__('None', 'Order status after label generated', 'vp-woo-pont')),
		'desc_tip' => __( 'If a label was generated for the order, change the order status automatically.', 'vp-woo-pont' ),
		'id' => 'auto_order_status',
	),
	array(
		'type' => 'select',
		'class' => 'wc-enhanced-select vp-woo-pont-toggle-select-field',
		'title' => __( 'COD Reference number', 'vp-woo-pont' ),
		'desc_tip' => __( 'With DPD and GLS, theres an extra field where you can store a reference number specific to a COD order. You can select what value to use in this field.', 'vp-woo-pont' ),
		'default' => 'order_number',
		'options' => array(
			'order_number' => __( 'Order number', 'vp-woo-pont' ),
			'order_id' => __( 'Order ID', 'vp-woo-pont' ),
			'invoice_number' => __( 'Invoice number', 'vp-woo-pont' ),
			'custom' => __( 'Custom meta', 'vp-woo-pont' ),
		),
		'id' => 'cod_reference_number'
	),
	array(
		'title' => __( 'COD Reference number meta key', 'vp-woo-pont' ),
		'type' => 'text',
		'id' => 'cod_reference_number_custom',
		'desc' => __( "Enter a custom order meta key and it will use that value as the COD reference number.", 'vp-woo-pont' ),
	),
	array(
		'type' => 'vp_weight_corrections',
		'title' => __('Weight correction', 'vp-woo-pont'),
		'id' => 'weight_corrections',
	),
	
	array(
		'type' => 'vp_packagings',
		'title' => __('Packaging', 'vp-woo-pont'),
		'id' => 'packagings',
	),
	
	array(
		'title' => __( 'Show settings on load', 'vp-woo-pont' ),
		'type' => 'checkbox',
		'desc' => __('Enable', 'vp-woo-pont'),
		'desc_tip' => __('If turned on, the settings panel for generating the label manually will be visible by default.', 'vp-woo-pont'),
		'id' => 'show_settings_metabox'
	),
	array(
		'title' => __( 'Developer mode', 'vp-woo-pont' ),
		'type' => 'checkbox',
		'id' => 'debug',
		'desc' => __('Enable', 'vp-woo-pont'),
		'desc_tip' => __( 'If turned on, the data sent to the provider during when createing a label will be logged in WooCommerce / Status / Logs. Can be used to debug issues.', 'vp-woo-pont' ),
	),
	array(
		'title'    => __( 'Create a ZIP file', 'vp-woo-pont' ),
		'type'     => 'checkbox',
		'disabled' => (!class_exists('ZipArchive')),
		'desc_tip' => __( 'If you want to download multiple shipping labels at once, this option will create a ZIP file with separate PDF files(the default option will merge all invoices into a single PDF).', 'vp-woo-pont' ),
		'desc' => self::get_bulk_zip_error(__('Enable', 'vp-woo-pont')),
		'id' => 'bulk_download_zip',
	),
	array(
		'title' => __( 'Custom order statuses', 'vp-woo-pont' ),
		'type' => 'text',
		'id' => 'custom_order_statues',
		'desc' => __( "If you are using a custom order status extension and the automation you setup for that status won't trigger, try to add the slug of your custom status. You can add multiple, separated with a comma.", 'vp-woo-pont' ),
	),
    array(
		'type' => 'sectionend',
	),
);

return apply_filters('vp_woo_pont_labels_settings', $settings);
