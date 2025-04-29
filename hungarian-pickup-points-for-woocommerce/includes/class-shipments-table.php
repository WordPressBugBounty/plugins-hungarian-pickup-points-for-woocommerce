<?php

class VP_Woo_Pont_Shipments_Table extends WP_List_Table {
	public static $carrier_id = 'posta';
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Delivery note', 'vp-woo-pont' ),
				'plural'   => __( 'Delivery notes', 'vp-woo-pont' ),
				'ajax'     => false
			)
		);
	}

	//Get data for the table
	public function get_shipments() {
		if(self::$carrier_id == 'kvikk') {
			return array();
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'vp_woo_pont_mpl_shipments';
		$sql = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE carrier = %s ORDER BY time DESC", self::$carrier_id );
		if(self::$carrier_id == 'posta') {
			$sql = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE carrier = %s OR carrier IS NULL ORDER BY time DESC", self::$carrier_id );
		}
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return apply_filters('vp_woo_pont_shipments_table_data', $result);
	}

	//Shipment number column
	function column_id( $item ) {
		return $item['id'];
	}

	//Payment amount column
	function column_time( $item ) {
		return $item['time'];
	}

	//Upload date column
	function column_packages( $item ) {
		$orders = json_decode($item['orders']);
		$packages = json_decode($item['packages']);
		$limit = 5;
		$count = 1;
		if($packages && is_array($packages)) {
			?>
			<ul>
				<?php foreach ($packages as $package): ?>
					<li><?php echo esc_html($package->tracking); ?> - <?php echo wc_price($package->cost); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php
		} else if(is_array($orders)) {
			?>
			<ul>
				<?php foreach ($orders as $order_id): ?>
					<?php $order = wc_get_order($order_id); ?>
					<?php if($order): ?>
						<li <?php if($count > $limit): ?>class="hidden"<?php endif; ?>>
							<a target="_blank" href="<?php echo VP_Woo_Pont()->labels->generate_download_link($order); ?>" target="_blank" class="vp-woo-pont-order-column-pdf">
								<i></i>
								<span><?php echo esc_html($order->get_meta('_vp_woo_pont_parcel_number')); ?></span>
							</a>
						</li>
					<?php $count++; endif; ?>
				<?php endforeach; ?>
			</ul>
			<?php if(count($orders) > $limit): ?>
				<a href="#" class="vp-woo-pont-shipments-show-all"><span class="dashicons dashicons-arrow-down-alt2"></span><?php printf( esc_html__( 'Show %d more', 'vp-woo-pont' ), count($orders)-$limit ); ?></a>
			<?php endif; ?>
			<?php
		}
  	}

	//Order number column
	function column_orders( $item ) {
		$orders = json_decode($item['orders']);
		if(is_array($orders)) {
		$limit = 5;
		$count = 1;
		?>
		<ul>
			<?php foreach ($orders as $order_id): ?>
				<?php $order = wc_get_order($order_id); ?>
				<?php if($order): ?>
					<li <?php if($count > $limit): ?>class="hidden"<?php endif; ?>>
						<?php $buyer = $order->get_billing_first_name(). ' ' .$order->get_billing_last_name(); ?>
						<?php echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>'; ?>
					</li>
				<?php $count++; endif; ?>
			<?php endforeach; ?>
		</ul>
		<?php if(count($orders) > $limit): ?>
				<a href="#" class="vp-woo-pont-shipments-show-all"><span class="dashicons dashicons-arrow-down-alt2"></span><?php printf( esc_html__( 'Show %d more', 'vp-woo-pont' ), count($orders)-$limit ); ?></a>
			<?php endif; ?>
		<?php
		}
	}

	//Invoice number column
	function column_pdf( $item ) {
		$paths = VP_Woo_Pont()->labels->get_pdf_file_path();
		$documents = $item['pdf'];
		$documents = array();

		//If starts with { its a json
		if(strpos($item['pdf'], '{') !== false) {
			$multiple_documents = json_decode($item['pdf'], true);
			foreach($multiple_documents as $courier => $document) {
				$documents[$courier] = $document;
			}
		} else {
			$documents[$item['carrier']] = $item['pdf'];
		}

		foreach($documents as $carrier => $document) {
			$pdf_file_url = $paths['baseurl'].$document;
			?>
				<p class="vp-woo-pont-admin-shipments-download-link">
					<a href="<?php echo esc_url($pdf_file_url); ?>" target="_blank">
						<span class="vp-woo-pont-admin-shipments-download-link-label">
							<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($carrier); ?>"></i>
							<span><?php esc_html_e('Download', 'vp-woo-pont'); ?></span>
						</span>
					</a>
				</p>
			<?php
		}
	}

	//Setup columns
	function get_columns() {
		$columns = array(
			'id' => __( 'ID', 'vp-woo-pont' ),
			'time' => __( 'Uploaded', 'vp-woo-pont' ),
			'orders' => __( 'Orders', 'vp-woo-pont' ),
			'pdf' => __( 'Delivery note', 'vp-woo-pont' )
		);
		return $columns;
	}

	//Setup items to display
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();

		$per_page = 10;
		$current_page = $this->get_pagenum();
		$data = $this->get_shipments();
		$total_items = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->_column_headers = array( $columns, $hidden, array() );
		$this->items = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
	}

}

class VP_Woo_Pont_Pending_Table extends WP_List_Table {
	public static $carrier_id = 'posta';
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Package', 'vp-woo-pont' ),
				'plural'   => __( 'Packages', 'vp-woo-pont' ),
				'ajax'     => false
			)
		);
	}

	//Get data for the table
	public function get_packages() {
		if(self::$carrier_id == 'kvikk') {
			return array();
		}

		if(get_option( 'woocommerce_feature_custom_order_tables_enabled', 'no' ) == 'yes' || get_option( 'woocommerce_custom_orders_table_enabled', 'no' ) == 'yes') {

			$meta_query = apply_filters('vp_woo_pont_shipments_table_meta_query', array(
				'relation' => 'AND',
				array(
					'key'     => '_vp_woo_pont_closed',
					'value'   => 'no',
				),
				array(
					'key'     => '_vp_woo_pont_provider',
					'value'   => self::$carrier_id,
					'compare' => 'LIKE',
				),
			), self::$carrier_id);

			$orders = wc_get_orders( array(
				'limit' => -1,
				'orderby' => 'date',
				'order' => 'DESC',
				'meta_query' => $meta_query
			));

		} else {

			$orders = wc_get_orders( array(
				'limit' => -1,
				'orderby' => 'date',
				'order' => 'DESC',
				'date_created' => '>' . ( time() - 10*MONTH_IN_SECONDS ),
				'vp_woo_pont_shipments' => array(self::$carrier_id, 'no')
			));

		}
		return $orders;
	}

	//Add class to row
	public function single_row( $order ) {
		$carrier_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
        echo '<tr data-courier="'.esc_attr($carrier_id).'">';
        	$this->single_row_columns( $order );
        echo '</tr>';
    }

	//Shipment number column
	function column_id( $order ) {
		$buyer = $order->get_billing_first_name(). ' ' .$order->get_billing_last_name();
		echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
	}

	//Billing address column
	function column_billing($order) {
		$address = $order->get_formatted_billing_address();
		echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
		echo '<span class="description">' . esc_html( $order->get_payment_method_title() ) . '</span>';
	}

	//Billing address column
	function column_total($order) {
		$payment_method = $order->get_payment_method();
		$payment_method_title = $order->get_payment_method_title();
		echo '<span data-tip="'.$payment_method_title.'" class="tips vp-woo-pont-admin-shipments-order-total payment-method-'.$payment_method.'">' . $order->get_formatted_order_total() . '</span>';
	}

  //Shipping address column
  function column_shipping( $order ) {
	if($provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order)) {
		$provider_name = VP_Woo_Pont_Helpers::get_provider_name($provider_id);
		$courier_id = $provider_id;
		?>
			<div class="vp-woo-pont-order-column" data-courier="<?php echo esc_attr($courier_id); ?>">
				<span class="vp-woo-pont-order-column-label">
					<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
					<span><?php echo esc_html($provider_name); ?></span>
				</span>
			</div>
		<?php
	}
	return $order->get_formatted_shipping_address();
  }

  //Package info column
  function column_tracking( $order ) {
		?>
		<div class="vp-woo-pont-order-column">
			<a target="_blank" href="<?php echo VP_Woo_Pont()->labels->generate_download_link($order); ?>" class="vp-woo-pont-order-column-pdf">
				<i></i>
				<span><?php echo esc_html($order->get_meta('_vp_woo_pont_parcel_number')); ?></span>
			</a>
		</div>
		<?php
  }

	//Checkbox column
	function column_cb( $order ) {
		return '<input type="checkbox" value="'.esc_attr($order->get_meta('_vp_woo_pont_parcel_number')).'" data-order="'.esc_attr($order->get_id()).'" checked name="selected_packages" />';
	}

	//Setup columns
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" checked />',
			'id' => __( 'Order number', 'vp-woo-pont' ),
			'billing' => __( 'Billing address', 'vp-woo-pont' ),
			'shipping' => __( 'Shipping address', 'vp-woo-pont' ),
			'total' => __( 'Order total', 'vp-woo-pont' ),
			'tracking' => __( 'Shipping label', 'vp-woo-pont' ),
		);
		return $columns;
	}

	//Setup items to display
	function prepare_items() {
		$columns = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden = array();

		$per_page = 1000;
		$data = $this->get_packages();
		$orders = array();
		$total_items = count( $data );
		$current_page = 0;

		//Filter out items with deleted labels
		foreach ($data as $order) {
			if($order->get_meta('_vp_woo_pont_parcel_id') || $order->get_meta('_vp_woo_pont_parcel_number')) {
				$orders[] = $order;
			}
		}

		$this->_column_headers = array( $columns, $hidden, $hidden );
		$this->items = $orders;
	}

}
