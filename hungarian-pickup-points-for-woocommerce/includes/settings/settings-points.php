<?php
defined( 'ABSPATH' ) || exit;

$settings = array(
	array(
		'title' => __( 'Custom pickup points', 'vp-woo-pont' ),
		'type' => 'vp_points_title',
		'desc' => __( 'Setup your own pickup points, like store pickup.', 'vp-woo-pont' ),
	),
	array(
		'title'       => __( 'Custom provider name', 'vp-woo-pont' ),
		'type'        => 'text',
		'desc' => __( 'If you setup your own pickup points, this will be the name of that category.', 'vp-woo-pont' ),
		'default'     => __( 'Store Pickup', 'vp-woo-pont' ),
		'id' => 'custom_title'
	),
	array(
		'title'       => __( 'Store Pickup icon', 'vp-woo-pont' ),
		'type'        => 'text',
		'desc' => __('You can set a custom icon to be visible on the map markers for your own pickup points. For SVG files, please install the SVG Support extension first.', 'vp-woo-pont'),
		'default'     => '',
		'id' => 'custom_icon'
	),
	array(
		'title' => _x('Pickup points', 'admin', 'vp-woo-pont'),
		'type' => 'vp_points_table',
        'id' => 'points_manager',
		'desc' => __('You can setup your own pickup points too. Make sure you enter a unique ID(used internally only). These points will show up under the "Custom Provider" name.', 'vp-woo-pont')
	),
    array(
		'type' => 'sectionend',
	),
);

return apply_filters('vp_woo_pont_points_settings', $settings);
