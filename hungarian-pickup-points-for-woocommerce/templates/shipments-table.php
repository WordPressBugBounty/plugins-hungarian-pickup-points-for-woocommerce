<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$date_format = get_option('date_format');
$time_format = get_option('time_format');
$current_date_time = date($date_format . ' ' . $time_format);
$total_weight = 0;

foreach ($orders as $order_id) {
	$order = wc_get_order($order_id);
	if(!$order) continue;
	$total_weight += VP_Woo_Pont_Helpers::get_package_weight_in_gramms($order);
}

?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
	body {
		font-family: Arial, sans-serif;
		padding: 0;
		margin: 0;
	}

	table.orders {
		border: 1px solid black;
		border-collapse: collapse;
		width: 100%;
		margin: 0;
	}

	@page {
		margin: 15px;
	}

	table.orders td {
		border-left: 1px solid black;
		border-right: 1px solid black;
		border-bottom: 1px solid black;
		padding: 10px;
	}

	table.orders th {
		border-left: 1px solid black;
		border-right: 1px solid black;
		border-bottom: 1px solid black;
		padding: 10px;
	}

	table.header {
		margin: 0 0 20px 0;
		line-height: 1.5;
	}

	h2, h3 {
		margin: 0;
	}

	h3 {
		font-size: 15px;
		color: #333;
		font-weight: normal;
	}

	img {
		width: 48px;
		height: 48px;
	}

	strong {
		width: 100px;
	}
</style>

<table width="100%" class="header">
	<tr>
		<td>
			<img src="<?php echo esc_url($icon); ?>" />
			<h2><?php esc_html_e('Shipment Summary', 'vp-woo-pont'); ?></h2>
		</td>
		<td align="right">
			<?php echo esc_html($current_date_time); ?><br>
			<strong><?php esc_html_e('Packages', 'vp-woo-pont'); ?></strong>: <?php echo count($orders); ?><br>
			<strong><?php esc_html_e('Weight', 'vp-woo-pont'); ?></strong>: <?php echo esc_html(wc_get_weight($total_weight, 'kg', 'g')); ?> kg
		</td>
	</tr>
</table>

<table width="100%" class="orders">
	<thead>
		<tr>
			<th align="left"><strong><?php esc_html_e('Order Number', 'vp-woo-pont'); ?></strong></th>
			<th align="left"><strong><?php esc_html_e('Shipping Address', 'vp-woo-pont'); ?></strong></th>
			<th align="right"><strong><?php esc_html_e('Shipment', 'vp-woo-pont'); ?></strong></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($orders as $order_id): ?>
			<?php $order = wc_get_order($order_id); ?>
			<?php if(!$order) continue; ?>
			<?php $total_weight += VP_Woo_Pont_Helpers::get_package_weight_in_gramms($order); ?>
			<tr>
				<td valign="top">
					<?php echo esc_html($order->get_order_number()); ?>
				</td>
				<td valign="top">
					<?php echo $order->get_formatted_shipping_address(); ?>
				</td>
				<td valign="top" align="right">
					<?php echo esc_html($order->get_meta('_vp_woo_pont_parcel_number')); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
