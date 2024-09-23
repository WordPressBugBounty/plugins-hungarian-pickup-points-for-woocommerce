<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-replace">
	<div class="wc-backbone-modal vp-woo-pont-modal-replace">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Select a new pick-up point', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'vp-woo-pont' ); ?></span>
					</button>
				</header>
				<article>
					<div class="vp-woo-pont-modal-replace-container">
						<div class="vp-woo-pont-modal-replace-options-search">
							<span class="dashicons dashicons-search"></span>
							<input type="text" id="vp-woo-pont-modal-replace-search" placeholder="<?php esc_attr_e('Search for a pickup point', 'vp-woo-pont'); ?>">
						</div>
						<ul class="vp-woo-pont-modal-replace-providers">
							<?php $enabled_providers = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers'); ?>
							<?php foreach ($enabled_providers as $provider_id): ?>
								<li>
									<label>
										<input type="radio" name="replacement_point_provider" value="<?php echo esc_attr($provider_id); ?>">
										<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
										<span><?php echo esc_html(VP_Woo_Pont_Helpers::get_provider_name($provider_id)); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
						<div class="vp-woo-pont-modal-replace-options">
							<ul class="vp-woo-pont-modal-replace-results"></ul>
						</div>
					</div>
				</article>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
