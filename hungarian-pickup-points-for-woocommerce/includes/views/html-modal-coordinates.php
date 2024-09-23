<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-coordinates">
	<div class="wc-backbone-modal vp-woo-pont-modal-coordinates">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Select the coordinates', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'vp-woo-pont' ); ?></span>
					</button>
				</header>
				<article>
					<div class="vp-woo-pont-modal-coordinates-map">
						<div id="map-coordinates"></div>
						<span class="dashicons dashicons-location"></span>
					</div>
					<p><?php _e('Position the location to the center of the map.', 'vp-woo-pont'); ?>
				</article>
				<footer>
					<div class="inner">
						<a class="button button-primary button-large" href="#" id="save_coordinates"><?php esc_html_e( 'Save coordinates', 'vp-woo-pont' ); ?></a>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
