<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="vp-woo-pont-settings-submenu">
    <?php if(VP_Woo_Pont_Pro::is_pro_enabled() || (!VP_Woo_Pont_Pro::is_pro_enabled() && VP_Woo_Pont_Pro::get_license_key())): ?>
        <?php if(VP_Woo_Pont_Pro::is_pro_enabled()): ?>
            <a href="#" class="vp-woo-pont-settings-submenu-pro active">
                <span class="dashicons dashicons-yes-alt"></span> <?php _e('The PRO version is active', 'vp-woo-pont'); ?>
            </a>
        <?php else: ?>
            <a href="#" class="vp-woo-pont-settings-submenu-pro expired">
                <span class="dashicons dashicons-warning"></span> <?php _e('The PRO version is expired', 'vp-woo-pont'); ?>
            </a>
        <?php endif; ?>
    <?php else: ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_labels' ) ); ?>" class="vp-woo-pont-settings-submenu-pro-setup button">
            <span class="dashicons dashicons-cart"></span> <?php _e('Setup PRO version', 'vp-woo-pont'); ?>
        </a>
    <?php endif; ?>
    <a class="button" href="https://visztpeter.me/dokumentacio/" target="_blank" aria-label="<?php esc_attr_e( 'VP Woo Pont Documentation', 'vp-woo-pont' ); ?>"><?php esc_html_e( 'Documentation', 'vp-woo-pont' ); ?></a>

    <a class="button vp-woo-pont-appearance-editor" href="https://visztpeter.me/dokumentacio/" target="_blank">
        <span class="dashicons dashicons-admin-appearance"></span>
        <span><?php esc_html_e( 'Map design', 'vp-woo-pont' ); ?></span>
    </a>

    <a class="button vp-woo-pont-restart-setup-wizard" href="#" data-url="<?php echo esc_url(admin_url( 'options.php?page=vp-woo-pont-walkthrough' )); ?>" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-restart-setup-wizard' )?>">
        <span><?php esc_html_e( 'Setup wizard', 'vp-woo-pont' ); ?></span>
    </a>

    <?php include( dirname( __FILE__ ) . '/html-modal-pro-version.php' ); ?>

</div>

