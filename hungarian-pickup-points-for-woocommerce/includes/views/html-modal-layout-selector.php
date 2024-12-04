<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-layout-selector">
	<div class="wc-backbone-modal vp-woo-pont-modal-layout-selector">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Label position', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'vp-woo-pont' ); ?></span>
					</button>
				</header>
				<article>
					<p><?php esc_html_e('Select the starting position of your sticker', 'vp-woo-pont'); ?></p>
					<div class="vp-woo-pont-modal-layout-selector-paper"></div>
				</article>
				<footer>
					<div class="inner">
						<a class="button button-primary button-large" target="_blank" href="#" class="vp-woo-pont-modal-layout-selector-submit"><?php esc_html_e( 'Print shipping labels', 'vp-woo-pont' ); ?></a>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
