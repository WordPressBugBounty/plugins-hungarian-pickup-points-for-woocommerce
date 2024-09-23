<?php

// If uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

//Check if we need to delete anything
$vp_woo_pont_settings = get_option( 'woocommerce_vp_pont_settings', null );
if($vp_woo_pont_settings['uninstall'] && $vp_woo_pont_settings['uninstall'] == 'yes') {

	// Delete admin notices
	delete_metadata( 'user', 0, 'vp_woo_pont_admin_notices', '', true );
	delete_option('vp_woo_pont_version_number');

	//Delete options
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'vp\_woo_pont\_%';");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_vp\_woo_pont\_%';");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_vp\_woo_pont_pro\_%';");
}
