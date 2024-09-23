<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2 class="vp-woo-pont-settings vp-woo-pont-settings-title-carrier">
    <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_pont' ); ?>"><?php _e( 'Pickup points', 'vp-woo-pont' ); ?></a> &gt;
        <?php echo esc_html( $data['title'] ); ?>
    </h2>
<?php

if ( ! empty( $data['desc'] ) ) {
    echo '<div id="' . esc_attr( sanitize_title( $data['id'] ) ) . '-description">';
    echo wp_kses_post( wpautop( wptexturize( $data['desc'] ) ) );
    echo '</div>';
}
echo '<table class="form-table">' . "\n\n";
if ( ! empty( $data['id'] ) ) {
    do_action( 'woocommerce_settings_' . sanitize_title( $data['id'] ) );
}