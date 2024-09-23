<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-import">
	<div class="wc-backbone-modal vp-woo-pont-modal-import">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Import settings', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'vp-woo-pont' ); ?></span>
					</button>
				</header>
				<form enctype="multipart/form-data">
					<article>
						<p><?php esc_html_e('Select the file that you previously exported.', 'vp-woo-pont'); ?></p>
						<p><?php esc_html_e('Important: this action will replace your existing options!', 'vp-woo-pont'); ?></p>
						<p>
							<input type="file" name="file" accept=".json" id="vp-woo-pont-modal-import-file">
						</p>
					</article>
					<footer>
						<div class="inner">
							<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( "vp_woo_pont_settings_export" ); ?>">
							<input type="hidden" name="action" value="vp_woo_pont_import_settings">
							<input type="hidden" name="type" value="{{data.type}}">
							<a class="button button-primary button-large" href="#" id="vp-woo-pont-modal-import-button"><?php esc_html_e( 'Import', 'vp-woo-pont' ); ?></a>
						</div>
					</footer>
				</form>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
