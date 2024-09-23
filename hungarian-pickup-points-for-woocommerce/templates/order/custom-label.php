<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Get related order data
$order = $data['order'];

?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
	body {
		font-family: Arial, sans-serif;
		padding: 0;
		margin: 0;
	}

	table {
		border: 1px solid black;
		border-collapse: collapse;
		width: 100%;
		margin: 0;
	}

	@page {
		margin: 5px;
	}

	table.footer {
		border-top: 0;
		text-align: center;
	}

	td {
		border-left: 1px solid black;
		border-right: 1px solid black;
		border-bottom: 1px solid black;
		padding: 10px;
	}

	th {
		border-left: 1px solid black;
		border-right: 1px solid black;
		padding: 10px 10px 0;
	}
</style>

<table width="100%">
	<tbody>
		<tr>
			<td colspan="2" align="center">
				<?php if($logo): ?>
					<img src="<?php echo esc_url($logo); ?>" width="200">
				<?php else: ?>
					<h3><?php echo get_bloginfo( 'name', 'display' ); ?></h3>
				<?php endif; ?>
			</td>
		</tr>
	</tbody>
	<thead>
		<tr>
			<th width="30%" align="left"><strong><?php esc_html_e('Sender', 'vp-woo-pont'); ?></strong></th>
			<th width="70%" align="left"><strong><?php esc_html_e('Recipient', 'vp-woo-pont'); ?></strong></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td valign="top">
				<p><?php echo nl2br(esc_html($sender)); ?></p>
			</td>
			<td valign="top">
				<?php echo esc_html($data['customer']['name']); ?><br>
				<?php echo esc_html(implode(' ', array($order->get_shipping_address_1(), $order->get_shipping_address_2()))); ?><br>
				<strong><?php echo esc_html($order->get_shipping_city()); ?></strong><br>
				<h3><?php echo esc_html($order->get_shipping_postcode()); ?></h3>
				<h4><?php echo esc_html($data['customer']['phone']); ?></h4>
			</td>
		</tr>
		<?php if($data['package']['cod']): ?>
			<tr>
				<td width="30%"><?php esc_html_e('COD', 'vp-woo-pont'); ?></td>
				<td width="70%"><h4><?php echo wp_strip_all_tags(wc_price($data['package']['total'])); ?></h4></td>
			</tr>
		<?php endif; ?>
		<tr>
			<td colspan="2" align="center">
				<barcode code="<?php echo esc_attr($data['order_id']); ?>" />
			</td>
		</tr>
	</tbody>
</table>
<table class="footer">
	<tr>
		<td width="33%"><?php esc_html_e('Order Number', 'vp-woo-pont'); ?>: <?php echo esc_html($data['order_number']); ?></td>
		<td width="33%"><?php esc_html_e('Weight', 'vp-woo-pont'); ?>: <?php echo esc_html($data['package']['weight_gramm']); ?>g</td>
		<td width="33%"><?php esc_html_e('Items', 'vp-woo-pont'); ?>: <?php echo esc_html($data['package']['qty']); ?></td>
		<?php if($data['invoice_number']): ?>
			<td width="33%"><?php esc_html_e('Invoice', 'vp-woo-pont'); ?>: <?php echo esc_html($data['invoice_number']); ?></td>
		<?php endif; ?>
	</tr>
</table>
<?php if($text || $contents): ?>
<table class="footer">
	<?php if($text): ?>
		<tr>
			<td>
				<p><?php echo nl2br(esc_html($text)); ?></p>
			</td>
		</tr>
	<?php endif; ?>
	<?php if($contents): ?>
		<tr>
			<td>
				<p><?php echo nl2br(esc_html($contents)); ?></p>
			</td>
		</tr>
	<?php endif; ?>
</table>
<?php endif; ?>
