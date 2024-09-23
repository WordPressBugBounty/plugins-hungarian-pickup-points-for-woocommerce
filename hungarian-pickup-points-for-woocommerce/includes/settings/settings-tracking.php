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
		'title' => __( 'Shipment tracking', 'vp-woo-pont' ),
		'type' => 'title',
		'desc' => __( 'Settings related to shipment tracking. Every function in this section will only work if you have the PRO version activated.', 'vp-woo-pont' ),
	),
	array(
		'type'     => 'checkbox',
		'title' => __( 'Sync tracking info', 'vp-woo-pont' ),
		'desc' => __( 'Fetch tracking information automatically', 'vp-woo-pont' ),
		'desc_tip' => __( 'If turned on, the tracking informations will be downloaded hourly from supported providers in the background. The tracking info is visible on the orders table(custom cell) and on the order details page.', 'vp-woo-pont' ),
		'id' => 'order_tracking',
	),
	array(
		'type' => 'select',
		'title' => __( 'Custom tracking page', 'vp-woo-pont' ),
		'class' => 'wc-enhanced-select',
		'options' => self::get_pages(),
		'desc' => __("If you want to use a custom order tracking page instead of redirecting the customer to the carrier's tracking website, create a new page with the following shortcode: [woocommerce_order_tracking]. The tracking links sent to customers via e-mail will redirect to this page instead, where the package status and all tracking informations are visible. If you use this option, make sure the auto tracking infomration fetching is enabled for the best experience.", 'vp-woo-pont'),
		'id' => 'custom_tracking_page',
	),
	array(
		'type' => 'multiselect',
		'title' => __( 'Tracking number in e-mails', 'vp-woo-pont' ),
		'class' => 'wc-enhanced-select',
		'default' => array('customer_completed_order'),
		'options' => self::get_email_ids(),
		'desc' => sprintf(
			__(
				'Select the e-mails where you want to include the tracking number and tracking link. You can also set up separate %1$s, %2$s, and %3$s e-mails.',
				'vp-woo-pont'
			),
			'<a href="'.esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=vp_woo_pont_email_shipped' ) ).'">' . __('shipped', 'vp-woo-pont') . '</a>',
			'<a href="'.esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=vp_woo_pont_email_delivery' ) ).'">' . __('out for delivery', 'vp-woo-pont') . '</a>',
			'<a href="'.esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=vp_woo_pont_email_pickup' ) ).'">' . __('ready for pickup', 'vp-woo-pont') . '</a>'
		),
		'id' => 'email_tracking_number',
	),
	array(
		'type' => 'select',
		'class' => 'wc-enhanced-select',
		'title' => __( 'E-mail text position', 'vp-woo-pont' ),
		'desc_tip' => __( 'Where should the tracking numbers be included in the emails?', 'vp-woo-pont' ),
		'default' => 'beginning',
		'options' => array(
			'beginning' => __( 'At the beginning', 'vp-woo-pont' ),
			'end' => __( 'At the end', 'vp-woo-pont' ),
		),
		'id' => 'email_tracking_number_pos',
	),
	array(
		'title' => __( 'E-mail text', 'vp-woo-pont' ),
		'type' => 'textarea',
		'placeholder' => __('You can track the order by clicking on the tracking number: {tracking_number}', 'vp-woo-pont'),
		'desc_tip' => __('This text will be included in the selected e-mails. Use the {tracking_number} replacement code for the actual tracking link. This will be replaced with the package number as a link, redirecting to the providers tracking page.', 'vp-woo-pont'),
		'id' => 'email_tracking_number_desc',
	),
	array(
		'type'     => 'checkbox',
		'title' => __( 'Show in my orders', 'vp-woo-pont' ),
		'desc' => __( 'Show tracking numbers in My Account / Orders', 'vp-woo-pont' ),
		'desc_tip' => __( 'If turned on, the tracking number and the tracking link will be visible in My Account / Order Details.', 'vp-woo-pont' ),
		'id' => 'tracking_my_account',
	),
	array(
		'type' => 'vp_tracking_automations',
		'title' => __( 'Tracking automations', 'vp-woo-pont' ),
		'id' => 'tracking_automations',
		'desc' => __('Change the order status automatically based on the tracking status', 'vp-woo-pont'),
	),
	array(
		'type' => 'sectionend'
	)
);

return apply_filters('vp_woo_pont_tracking_settings', $settings);
