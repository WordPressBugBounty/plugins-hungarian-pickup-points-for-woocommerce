<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prograss_bar_text = array(
    'total' => array(
        'singular' => __( 'One in total', 'vp-woo-pont' ),
        'plural' => __( '%d in total', 'vp-woo-pont' ),    
    ),
    'current' => array(
        'default' => __( '1 label generating', 'vp-woo-pont' ),
        'singular' => __( '1 label generated', 'vp-woo-pont' ),
        'plural' => __( '%d labels generated', 'vp-woo-pont' ),    
    ),
);

$prograss_bar_text_json = wp_json_encode( $prograss_bar_text );
$prograss_bar_text_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $prograss_bar_text_json ) : _wp_specialchars( $prograss_bar_text_json, ENT_QUOTES, 'UTF-8', true );

?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-generate">
	<div class="wc-backbone-modal vp-woo-pont-modal-generate">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Generate labels', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'vp-woo-pont' ); ?></span>
					</button>
				</header>
				<article>
                    <?php do_action( 'vp_woo_pont_modal_generate_before_table' ); ?>
					<table>
                        <thead>
                            <tr>
                                <th class="cell-checkbox"><input class="vp-woo-pont-modal-generate-selectall" type="checkbox" checked></th>
                                <th><?php esc_html_e('Order', 'vp-woo-pont'); ?></th>
                                <th><?php esc_html_e('Shipping address', 'vp-woo-pont'); ?></th>
                                <th><?php esc_html_e('Label', 'vp-woo-pont'); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
				</article>
				<footer>
					<div class="inner">
                        <div class="vp-woo-pont-modal-generate-progress-pending">
                            <p><?php esc_html_e('Labels are currently generating, just a sec...', 'vp-woo-pont'); ?></p>
                            <div class="vp-woo-pont-modal-generate-progress">
                                <div class="vp-woo-pont-modal-generate-progress-bar">
                                    <div class="vp-woo-pont-modal-generate-progress-bar-inner"></div>
                                </div>
                                <div class="vp-woo-pont-modal-generate-progress-bar-text" data-labels="<?php echo $prograss_bar_text_attr; ?>">
                                    <span class="vp-woo-pont-modal-generate-progress-bar-text-current"></span>
                                    <span class="vp-woo-pont-modal-generate-progress-bar-text-total"></span>
                                </div>
                            </div>
                        </div>
                        <div class="vp-woo-pont-modal-generate-progress-buttons">
                            <a href="#" class="button vp-woo-pont-modal-generate-button-download"><?php esc_html_e('Download', 'vp-woo-pont'); ?></a>
                            <a href="#" class="button button-primary vp-woo-pont-modal-generate-button-print"><?php esc_html_e('Print', 'vp-woo-pont'); ?></a>
                        </div>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/html" id="vp_woo_pont_modal_generate_sample_row">
    <tr>
        <td class="cell-checkbox"><input type="checkbox" name="order_ids[]" checked></td>
        <td class="cell-order-number">
            <strong>#123</strong> <span>Teszt Teszt</span>
        </td>
        <td class="cell-address">
            <div class="cell-address-inside">
                <i class="vp-woo-pont-provider-icon"></i>
                <span>Kerékvár Kerékpárbolt BUDAPEST I. KER.</span>
            </div>
        </td>
        <td class="cell-label">
            <span class="vp-woo-pont-modal-generate-label-error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Unable to generate label', 'vp-woo-pont'); ?></span>
            <span class="vp-woo-pont-modal-generate-loading-indicator"><?php esc_html_e('Generating...', 'vp-woo-pont'); ?></span>
            <a href="#" class="vp-woo-pont-modal-generate-label" target="_blank">PB71U69851706</a>
            <a href="#" class="vp-woo-pont-modal-generate-label-print">
                <span class="dashicons dashicons-printer"></span>
            </a>
        </td>
    </tr>
</script>