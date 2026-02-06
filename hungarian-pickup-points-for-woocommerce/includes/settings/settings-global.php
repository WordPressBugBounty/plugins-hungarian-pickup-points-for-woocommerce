<?php
defined( 'ABSPATH' ) || exit;

$pro_required = false;
$pro_icon = false;
if(!VP_Woo_Pont_Pro::is_pro_enabled()) {
	$pro_required = true;
	$pro_icon = '<i class="vp_woo_pont_pro_label">PRO</i>';
}

$settings = array(
	'title'      => array(
		'title'       => __( 'Method title', 'vp-woo-pont' ),
		'type'        => 'text',
		'desc' => __( 'This controls the title which the user sees during checkout.', 'vp-woo-pont' ),
		'default'     => _x( 'Pickup point', 'frontend', 'vp-woo-pont' ),
		'desc_tip'    => true,
	),
	'enabled_providers' => array(
		'type' => 'vp_woo_pont_settings_enabled_providers',
		'title' => __( 'Enabled providers', 'vp-woo-pont' ),
		'id' => 'enabled_providers',
		'options' => $this->get_available_providers(),
		'default' => array(),
	),
	'tax_status' => array(
		'title'   => __( 'Tax status', 'vp-woo-pont' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'taxable',
		'options' => array(
			'taxable' => __( 'Taxable', 'vp-woo-pont' ),
			'none'    => _x( 'None', 'Tax status', 'vp-woo-pont' ),
		),
	),
	'cost'       => array(
		'title'             => __( 'Default cost', 'vp-woo-pont' ),
		'type'              => 'text',
		'placeholder'       => '',
		'description'       => __('In case you leave it empty, only the pricing setup below with matching conditions will be used.', 'vp-shipping-rate'),
		'default'           => '0',
		'sanitize_callback' => array( $this, 'sanitize_cost' ),
	),
	'detailed_cost' => array(
		'title' => __('Detailed cost', 'vp-woo-pont'),
		'type' => 'vp_woo_pont_settings_pricing_table'
	),
	'cost_logic' => array(
		'title'   => __( 'Multiple cost logic', 'vp-woo-pont' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'low',
		'desc_tip'          => true,
		'description'       => __('If theres multiple matches for the shipping cost, use the lowest or the highest cost.', 'vp-woo-pont'),
		'options' => array(
			'low' => __( 'Lowest', 'vp-woo-pont' ),
			'high'    => _x( 'Highest', 'Tax status', 'vp-woo-pont' ),
		),
	),
	'free_shipping_overwrite' => array(
		'title' => __( 'Free shipping', 'vp-woo-pont' ),
		'label' => __( 'If free shipping is available, make pickup points free too', 'vp-woo-pont' ),
		'type' => 'checkbox',
	),
	'name_on_invoice' => array(
		'type' => 'text',
		'title' => __( 'Invoice line item name', 'vp-woo-pont' ),
		'desc_tip'          => true,
		'description' => __('This is the label that appears on the invoices for shipping. Default is the name of this shipping method. You can use the {provider} shortcode to display the selected pickup point provider.', 'vp-woo-pont'),
	),
	'note_on_invoice' => array(
		'type' => 'text',
		'title' => __( 'Invoice line item description', 'vp-woo-pont' ),
		'default' => '{provider}, {point}',
		'desc_tip'          => true,
		'description' => __('This is the label that appears on the invoices for shipping. Default is the name of the provider. You can use the {provider} and {point} shortcode to display the selected pickup point provider and pickup point name.', 'vp-woo-pont'),
	),
	'validate_phone_numbers' => array(
		'type' => 'checkbox',
		'title' => __( 'Validate phone numbers', 'vp-woo-pont' ),
		'label' => __( 'Validate phone numbers on checkout', 'vp-woo-pont' ),
		'desc_tip' => true,
		'description' => __('If enabled, the phone number field will be validated on checkout for Hungarian phone numbers only.', 'vp-woo-pont'),
	),
	'notes' => array(
		'type' => 'vp_woo_pont_settings_notes',
		'description' => __('These provider related notes appear when the customer selects a pickup point on the map.', 'vp-woo-pont'),
		'title' => __( 'Extra notes', 'vp-woo-pont' ),
	),
	'kvikk_map_api_key' => array(
		'type' => 'text',
		'title' => __( 'Kvikk Map API Key(beta)', 'vp-woo-pont' ),
		'label' => __( 'Enable', 'vp-woo-pont' ),
		'description' => __('Enter the Kvikk Map API key to use a modern, vectorized map. Only works with Checkout Blocks. <a target="_blank" href="https://support.kvikk.hu/docs/kvikk-map/introduction">More Info</a>', 'vp-woo-pont')
	),
	'hide_state_field' => array(
		'title'    => __( 'State field', 'vp-woo-pont' ),
		'label'     => __( 'Hide state field', 'vp-woo-pont' ),
		'desc_tip' => __( 'Hide state field for Hungary.', 'vp-woo-pont' ),
		'id'       => 'vp_woo_pont_hide_state_field',
		'default'  => 'no',
		'type'     => 'checkbox'
	)
);

return apply_filters('vp_woo_pont_global_settings', $settings);
