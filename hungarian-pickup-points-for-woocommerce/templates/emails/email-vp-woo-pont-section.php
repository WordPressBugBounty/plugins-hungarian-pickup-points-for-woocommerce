<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div style="margin-bottom: 40px;">
	<h2><?php esc_html_e('Tracking Information', 'vp-woo-pont'); ?></h2>
	<p><?php echo str_replace('{tracking_number}', $tracking_link, esc_html($tracking_text)); ?></p>
</div>
