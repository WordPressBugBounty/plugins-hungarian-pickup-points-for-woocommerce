<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<?php
$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : false;
$tabs        = array(
	'kvikk' => __( 'Kvikk', 'vp-woo-pont' ),
	'posta' => __( 'MPL', 'vp-woo-pont' ),
	'foxpost'  => __( 'Foxpost', 'vp-woo-pont' ),
	'expressone'   => __( 'Express One', 'vp-woo-pont' ),
	'dpd'   => __( 'DPD', 'vp-woo-pont' ),
	'transsped'   => __( 'Trans-Sped', 'vp-woo-pont' ),
);

//Add support for GLS if needed based on custom settings
if (VP_Woo_Pont_Helpers::get_option('gls_delivery_notes', 'no') == 'yes') {
	$tabs['gls'] = __( 'GLS', 'vp-woo-pont' );
}

?>

<div class="wrap woocommerce vp-woo-pont-admin-shipments">
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php foreach ( $tabs as $name => $label ): ?>
			<?php if(!VP_Woo_Pont_Helpers::is_provider_configured($name)) continue; ?>
			<?php if(!$current_tab) $current_tab = $name; ?>
			<a href="<?php echo esc_url(admin_url( 'admin.php?page=vp-woo-pont-shipments&tab=' . $name )); ?>" class="nav-tab <?php if($current_tab == $name): ?>nav-tab-active<?php endif; ?>">
				<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($name); ?>"></i>
				<span><?php echo esc_html($label); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<table>
	<?php

	//Get table data for pending orders
	$orders_table = new VP_Woo_Pont_Pending_Table();
	$orders_table::$carrier_id = $current_tab;
	$orders_table->prepare_items();

	//Get table data for closed shipments
	$packages_table = new VP_Woo_Pont_Shipments_Table();
	$packages_table::$carrier_id = $current_tab;
	$packages_table->prepare_items();

	?>
	</table>

	<?php	if ( empty( $orders_table->items ) && empty( $packages_table->items ) ): ?>
		<?php if($current_tab == 'kvikk'): ?>
			<div class="vp-woo-pont-admin-shipments-no-results vp-woo-pont-admin-shipments-no-results-kvikk">
				<p><?php esc_html_e('You can create delivery notes for Kvikk packages in the delivery notes menu of your app.kvikk.hu account.', 'vp-woo-pont'); ?></p>
				<a href="https://app.kvikk.hu/delivery-notes/new" target="_blank" class="button button-primary button-hero"><?php esc_html_e('Go to my Kvikk account', 'vp-woo-pont'); ?></a>
			</div>
		<?php else: ?>
			<div class="vp-woo-pont-admin-shipments-no-results">
				<p><?php esc_html_e('Generate at least one label, after that, you can generate the necessary delivery note for them.', 'vp-woo-pont'); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php	if ( ! empty( $orders_table->items ) ): ?>
		<div class="vp-woo-pont-admin-shipments-pending-container">
				<h2><?php esc_html_e('Waiting for closing', 'vp-woo-pont'); ?> <span class="vp-woo-pont-admin-shipments-count"><?php echo count($orders_table->items); ?><span></h2>
				<p><?php esc_html_e('The following packages are waiting for closing and a delivery note.', 'vp-woo-pont'); ?></p>
				<div class="vp-woo-pont-admin-shipments-notice notice notice-error" style="display:none"><p></p></div>
				<div class="vp-woo-pont-admin-shipments-table vp-woo-pont-admin-shipments-table-pending">
					<?php if(count($orders_table->items) > 10): ?>
					<p>
						<a class="button button-primary button-large vp_woo_pont_close_shipments" href="#" data-provider="<?php echo esc_attr($current_tab); ?>" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-close-shipments' )?>"><?php esc_html_e( 'Generate delivery note', 'vp-woo-pont' ); ?></a>
						<a class="button button-secondary button-large vp_woo_pont_close_orders" href="#" data-provider="<?php echo esc_attr($current_tab); ?>" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-close-shipments' )?>"><?php esc_html_e( 'Mark as shipped', 'vp-woo-pont' ); ?></a>
					</p>	
					<?php endif; ?>
					<?php $orders_table->display(); ?>
					<p>
						<a class="button button-primary button-large vp_woo_pont_close_shipments" href="#" data-provider="<?php echo esc_attr($current_tab); ?>" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-close-shipments' )?>"><?php esc_html_e( 'Generate delivery note', 'vp-woo-pont' ); ?></a>
						<a class="button button-secondary button-large vp_woo_pont_close_orders" href="#" data-provider="<?php echo esc_attr($current_tab); ?>" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-close-shipments' )?>"><?php esc_html_e( 'Mark as shipped', 'vp-woo-pont' ); ?></a>
					</p>
				</div>
				<hr>
		</div>
	<?php endif; ?>

	<?php if($current_tab != 'kvikk'): ?>
	<div class="vp-woo-pont-admin-shipments-closed-packages">
		<h2><?php esc_html_e('Closed packages', 'vp-woo-pont'); ?></h2>
		<p><?php esc_html_e('You can find all your closed packages and delivery notes here.', 'vp-woo-pont'); ?></p>
		<div class="vp-woo-pont-admin-shipments-table">
			<?php $packages_table->display(); ?>
		</div>
		<script type="text/template" id="tmpl-vp-woo-pont-shipment-result">
			<tr>
				<td class="column-id" data-colname="Azonosító">5</td>
				<td class="time column-time" data-colname="Beküldve">2024-06-14 12:57:34</td>
				<td class="orders column-orders" data-colname="Rendelések és csomagok">14 rendelés</td>
				<td class="pdf column-pdf" data-colname="Szállítólevél">
					<p class="vp-woo-pont-admin-shipments-download-link">
						<a href="#" target="_blank">
							<span class="vp-woo-pont-admin-shipments-download-link-label">
								<i class="vp-woo-pont-provider-icon"></i>
								<span><?php esc_html_e('Download', 'vp-woo-pont'); ?></span>
							</span>
						</a>
					</p>
				</td>
			</tr>
		</script>
	</div>
	<?php endif; ?>
</div>