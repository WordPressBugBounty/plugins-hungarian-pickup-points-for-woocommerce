<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-pro-version">
	<div class="wc-backbone-modal vp-woo-pont-modal-pro-version">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1>
						<?php if(VP_Woo_Pont_Pro::is_pro_enabled()): ?>
							<span class="dashicons dashicons-yes-alt"></span> <?php _e('The PRO version is active', 'vp-woo-pont'); ?>
						<?php else: ?>
							<span class="dashicons dashicons-warning"></span> <?php _e('The PRO version is expired', 'vp-woo-pont'); ?>
						<?php endif; ?>
					</h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'vp-woo-pont' ); ?></span>
					</button>
				</header>
				<?php if(VP_Woo_Pont_Pro::is_pro_enabled() || (!VP_Woo_Pont_Pro::is_pro_enabled() && VP_Woo_Pont_Pro::get_license_key())): ?>
					<article class="vp-woo-pont-modal-pro-version-content vp-woo-pont-settings-widget-pro-<?php if(VP_Woo_Pont_Pro::is_pro_enabled()): ?>state-active<?php else: ?>state-expired<?php endif; ?>">
						<p>
							<span class="vp-woo-pont-settings-widget-pro-label"><?php _e('License key', 'vp-woo-pont'); ?></span><br>
							<?php echo esc_html(VP_Woo_Pont_Pro::get_license_key()); ?>
						</p>
						<?php $license = VP_Woo_Pont_Pro::get_license_key_meta(); ?>
						<?php if(isset($license['type'])): ?>
						<p class="single-license-info">
							<span class="vp-woo-pont-settings-widget-pro-label"><?php _e('License type', 'vp-woo-pont'); ?></span><br>
							<?php if ( $license['type'] == 'unlimited' ): ?>
								<?php _e( 'Unlimited', 'vp-woo-pont' ); ?>
							<?php else: ?>
								<?php _e( 'Subscription', 'vp-woo-pont' ); ?>
							<?php endif; ?>
						</p>
						<?php endif; ?>
						<?php if(isset($license['next_payment'])): ?>
						<p class="single-license-info">
							<span class="vp-woo-pont-settings-widget-pro-label"><?php _e('Next payment', 'vp-woo-pont'); ?></span><br>
							<?php echo esc_html($license['next_payment']); ?>
						</p>
						<?php endif; ?>
						<p><?php esc_html_e( 'If you want to activate the license on another website, you must first deactivate it on this website.', 'vp-woo-pont' ); ?></p>
					</article>
				<?php endif; ?>
				<footer>
					<div class="inner">
						<a class="button-secondary" id="vp_woo_pont_deactivate_pro"><?php esc_html_e( 'Deactivate license', 'vp-woo-pont' ); ?></a>
						<a class="button-secondary" id="vp_woo_pont_validate_pro"><?php esc_html_e( 'Reload license', 'vp-woo-pont' ); ?></a>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
