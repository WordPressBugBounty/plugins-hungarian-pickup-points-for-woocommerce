<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "\n".esc_html( wc_strtoupper( __( 'Tracking Information', 'vp-woo-pont' ) ) ) . "\n";
echo str_replace('{tracking_number}', $tracking_url, esc_html($tracking_text))."\n\n";
