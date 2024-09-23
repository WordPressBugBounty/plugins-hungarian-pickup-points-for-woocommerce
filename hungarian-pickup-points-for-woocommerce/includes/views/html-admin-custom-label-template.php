<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get template info
$template = 'order/custom-label.php';
$local_file    = get_stylesheet_directory() . '/' . apply_filters( 'woocommerce_template_directory', 'woocommerce', $template ) . '/' . $template;
$core_file     = VP_Woo_Pont::$plugin_path . '/templates/' . $template;
$template_file = $core_file;
$template_dir  = apply_filters( 'woocommerce_template_directory', 'woocommerce', $template );
$latest_order_id = 0;
$latest_orders = wc_get_orders( array('limit' => 1, 'return' => 'ids') );
if($latest_orders) {
	$latest_order_id = $latest_orders[0];
}
$preview_url = add_query_arg('vp_woo_pont_custom_label_preview', $latest_order_id, get_admin_url() );

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html($data['title']); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<?php if ( file_exists( $local_file ) ) : ?>
			<p>
				<?php printf( esc_html__( 'This template has been overridden by your theme and can be found in: %s.', 'vp-woo-pont' ), '<code>' . esc_html( trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template ) . '</code>' ); ?>
			</p>
		<?php else: ?>
			<p>
				<?php
					$emails_dir    = get_stylesheet_directory() . '/' . $template_dir . '/order';
					$templates_dir = get_stylesheet_directory() . '/' . $template_dir;
					$theme_dir     = get_stylesheet_directory();

					if ( is_dir( $emails_dir ) ) {
						$target_dir = $emails_dir;
					} elseif ( is_dir( $templates_dir ) ) {
						$target_dir = $templates_dir;
					} else {
						$target_dir = $theme_dir;
					}
				?>
				<?php printf( esc_html__( 'To override and edit this label template copy %1$s to your theme folder: %2$s.', 'vp-woo-pont' ), '<code>' . esc_html( plugin_basename( $template_file ) ) . '</code>', '<code>' . esc_html( trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template ) . '</code>' ); ?>
			</p>
		<?php endif; ?>
		<p>
			<a href="<?php echo esc_url($preview_url); ?>" target="_blank" class="button"><?php esc_html_e('Preview template', 'vp-woo-pont'); ?></a>
		</p>
	</td>
</tr>