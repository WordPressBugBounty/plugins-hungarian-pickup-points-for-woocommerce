<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-tracking">
	<div class="wc-backbone-modal vp-woo-pont-modal-tracking">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Tracking informations', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article class="vp-woo-pont-modal-tracking-content">
					<ul class="order_notes">
						<li class="note customer-note note-sample" style="display:none">
							<div class="note_content"><p></p></div>
							<p class="meta"><abbr class="exact-date"></abbr></p>
						</li>
					</ul>
				</article>
				<footer>
					<div class="inner">
						<div class="vp-woo-pont-modal-tracking-info">
							<span><?php echo esc_html_e('Tracking number', 'vp-woo-pont'); ?>: <strong class="vp-woo-pont-modal-tracking-number"></strong></span>
							<span><?php echo esc_html_e('Last updated', 'vp-woo-pont'); ?>: <strong class="vp-woo-pont-modal-tracking-date" data-now="<?php esc_attr_e('Now', 'vp-woo-pont'); ?>"></strong></span>
						</div>	
						<div class="vp-woo-pont-modal-tracking-buttons">	
							<a class="button button-large vp-woo-pont-modal-tracking-reload" data-order="" target="_blank" href="#"><?php esc_html_e( 'Reload', 'vp-woo-pont' ); ?></a>
							<a class="button button-primary button-large vp-woo-pont-modal-tracking-link" target="_blank" href="#"><?php esc_html_e( 'Tracking externally', 'vp-woo-pont' ); ?></a>
						</div>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
