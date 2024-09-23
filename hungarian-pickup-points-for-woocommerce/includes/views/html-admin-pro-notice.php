<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


<div class="vp-woo-pont-settings vp-woo-pont-pro-widget">
    <p><?php esc_html_e('Upgrade to the PRO version to generate shipping labels and to track shipments.', 'vp-woo-pont'); ?></p>
    <div class="vp-woo-pont-pro-widget-cta">
		<a class="button button-primary button-hero" href="https://visztpeter.me/woocommerce-csomagpont-integracio/">
            <span class="dashicons dashicons-cart"></span> 
            <span><?php esc_html_e( 'Purchase PRO version', 'vp-woo-pont' ); ?></span>
        </a>
		<span>
			<strong><small><?php esc_html_e('from', 'vp-woo-pont'); ?></small> <span><?php esc_html_e( '35 EUR / year', 'vp-woo-pont' ); ?></span></strong>
		</span>
	</div>
    
	<div class="vp-woo-pont-pro-widget-activate">
		<input class="input-text regular-input" type="text" name="woocommerce_vp_woo_pont_pro_key" id="woocommerce_vp_woo_pont_pro_key" value="" placeholder="<?php esc_html_e( 'License key', 'vp-woo-pont' ); ?>">
		<button class="button" type="button" id="vp_woo_pont_activate_pro"><?php _e('Activate', 'vp-woo-pont'); ?></button>
    </div>
    <div class="vp-woo-pont-pro-widget-notice" style="display:none">
		<span class="dashicons dashicons-warning"></span>
		<p></p>
	</div>
    
</div>
