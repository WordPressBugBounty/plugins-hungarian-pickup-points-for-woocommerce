<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Color variables
$base      = get_option( 'woocommerce_email_base_color' );
$base_text = wc_light_or_dark( $base, '#202020', '#ffffff' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo wp_kses_post( wpautop( wptexturize( $content ) ) ); ?>

<?php if($tracking_link): ?>
	<p>
		<a href="<?php echo esc_url( $tracking_link ); ?>" target="_blank" style="background-color: <?php echo esc_attr( $base ); ?>; color: <?php echo esc_attr( $base_text ); ?>; display: inline-block; text-decoration: none; font-weight: bold; line-height: 1.4; text-align: center; cursor: pointer; border-radius: 3px; font-size: 16px; padding: 12px 20px; border-radius: 4px;"><?php _e( 'Track your order', 'vp-woo-pont' ); ?></a>
	</p>
<?php endif; ?>

<?php 
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'vp_woo_pont_tracking_email_order_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
 do_action( 'woocommerce_email_footer', $email );
