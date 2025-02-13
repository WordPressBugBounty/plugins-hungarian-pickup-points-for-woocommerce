<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Conditions', false ) ) :

	class VP_Woo_Pont_Conditions {

		//Get possible conditional values
		public static function get_conditions($group = 'pricings') {

			//Get country list
			$countries_obj = new WC_Countries();
			$countries = $countries_obj->__get('countries');

			//Setup conditions
			$conditions = array(
				'payment_method' => array(
					"label" => __('Payment method', 'vp-woo-pont'),
					'options' => VP_Woo_Pont_Helpers::get_payment_methods()
				),
				'type' => array(
					"label" => __('Order type', 'vp-woo-pont'),
					'options' => array(
						'individual' => __('Individual', 'vp-woo-pont'),
						'company' => __('Company', 'vp-woo-pont'),
					)
				),
				'product_category' => array(
					'label' => __('Product category', 'vp-woo-pont'),
					'options' => array()
				),
				'billing_country' => array(
					"label" => __('Billing country', 'vp-woo-pont'),
					'options' => $countries
				),
				'cart_total' => array(
					'label' => __('Cart Total', 'vp-woo-pont'),
					'options' => array()
				),
				'cart_total_discount' => array(
					'label' => __('Cart Total(with discount)', 'vp-woo-pont'),
					'options' => array()
				),
				'weight' => array(
					"label" => __('Package weight', 'vp-woo-pont'),
					'options' => array()
				),
				'volume' => array(
					'label' => __('Package volume', 'vp-woo-pont'),
					'options' => array()
				),
				'longest_side' => array(
					'label' => __('Package longest side', 'vp-woo-pont'),
					'options' => array()
				)
			);

			if($group == 'pricings') {

				$conditions['shipping_class'] = array(
					'label' => __('Shipping class', 'vp-woo-pont'),
					'options' => VP_Woo_Pont_Helpers::get_shipping_classes()
				);

				$conditions['cart_count'] = array(
					'label' => __('Items in cart', 'vp-woo-pont'),
					'options' => array()
				);

				$conditions['current_date'] = array(
					'label' => __('Current date', 'vp-woo-pont'),
					'options' => array()
				);

				$conditions['current_time'] = array(
					'label' => __('Current time', 'vp-woo-pont'),
					'options' => array()
				);

				$conditions['current_day'] = array(
					'label' => __('Current day', 'vp-woo-pont'),
					'options' => array(
						1 => __('Monday', 'vp-woo-pont'),
						2 => __('Tuesday', 'vp-woo-pont'),
						3 => __('Wednesday', 'vp-woo-pont'),
						4 => __('Thursday', 'vp-woo-pont'),
						5 => __('Friday', 'vp-woo-pont'),
						6 => __('Saturday', 'vp-woo-pont'),
						7 => __('Sunday', 'vp-woo-pont'),
					)
				);

				$conditions['user_logged_in'] = array(
					'label' => __('User logged in', 'vp-woo-pont'),
					'options' => array(
						'yes' => __('Yes', 'vp-woo-pont'),
						'no' => __('No', 'vp-woo-pont'),
					)
				);

				$conditions['user_role'] = array(
					'label' => __('User role', 'vp-woo-pont'),
					'options' => VP_Woo_Pont_Helpers::get_user_roles()
				);

			}

			if($group != 'pricings') {
				$providers = VP_Woo_Pont_Helpers::get_supported_providers();
				$carrier_labels = VP_Woo_Pont_Helpers::get_external_provider_groups();
				foreach($providers as $provider_id => $label) {
					$carrier = explode('_', $provider_id)[0];
					if(in_array($carrier, array_keys($carrier_labels)) && strpos($provider_id, '_') !== false) {
						$label = $carrier_labels[$carrier].' - '.$label;
						$providers[$provider_id] = $label;
					}
				}

				$conditions['provider'] = array(
					'label' => _x( 'Pickup point', 'admin', 'vp-woo-pont' ),
					'options' => $providers
				);

				$conditions['billing_address'] = array(
					"label" => __('Billing address', 'vp-woo-pont'),
					'options' => array(
						'eu' => __('Inside the EU', 'vp-woo-pont'),
						'world' => __('Outside of the EU', 'vp-woo-pont'),
					)
				);
			}

			if($group == 'automations') {
				$conditions['shipping_class'] = array(
					'label' => __('Shipping class', 'vp-woo-pont'),
					'options' => VP_Woo_Pont_Helpers::get_shipping_classes()
				);

				$conditions['shipping_method'] = array(
					"label" => __('Shipping method', 'vp-woo-pont'),
					'options' => VP_Woo_Pont_Helpers::get_available_shipping_methods()
				);

				$conditions['products_quantity'] = array(
					'label' => __('Items in order(qty)', 'vp-woo-pont'),
					'options' => array()
				);
			}

			if($group == 'cod_fees') {
				$conditions['shipping_class'] = array(
					'label' => __('Shipping class', 'vp-woo-pont'),
					'options' => VP_Woo_Pont_Helpers::get_shipping_classes()
				);

				$conditions['shipping_method'] = array(
					"label" => __('Shipping method', 'vp-woo-pont'),
					'options' => VP_Woo_Pont_Helpers::get_available_shipping_methods(false)
				);
			}

			//Add category options
			foreach (get_terms(array('taxonomy' => 'product_cat')) as $category) {
				$conditions['product_category']['options'][$category->term_id] = $category->name;
			}

			//Apply filters
			$conditions = apply_filters('vp_woo_pont_'.$group.'_conditions', $conditions);

			return $conditions;
		}

		public static function get_sample_row($group = 'pricings') {
			$conditions = self::get_conditions($group);
			ob_start();
			?>
			<script type="text/html" id="vp_woo_pont_<?php echo $group; ?>_condition_sample_row">
				<li>
					<select class="condition" data-name="vp_woo_pont_<?php echo substr($group, 0, -1); ?>[X][conditions][Y][category]">
						<?php foreach ($conditions as $condition_id => $condition): ?>
							<option value="<?php echo esc_attr($condition_id); ?>"><?php echo esc_html($condition['label']); ?></option>
						<?php endforeach; ?>
					</select>
					<select class="comparison" data-name="vp_woo_pont_<?php echo substr($group, 0, -1); ?>[X][conditions][Y][comparison]">
						<option value="equal"><?php _e('equal', 'vp-woo-pont'); ?></option>
						<option value="not_equal"><?php _e('not equal', 'vp-woo-pont'); ?></option>
						<option value="greater"><?php _e('greater than', 'vp-woo-pont'); ?></option>
						<option value="greater_or_equal"><?php _e('greater or equal', 'vp-woo-pont'); ?></option>
						<option value="less"><?php _e('less than', 'vp-woo-pont'); ?></option>
						<option value="less_or_equal"><?php _e('less or equal', 'vp-woo-pont'); ?></option>
					</select>
					<?php foreach ($conditions as $condition_id => $condition): ?>
						<?php if($condition['options']): ?>
							<select class="value <?php if($condition_id == 'payment_method'): ?>selected<?php endif; ?>" data-condition="<?php echo esc_attr($condition_id); ?>" data-name="vp_woo_pont_<?php echo substr($group, 0, -1); ?>[X][conditions][Y][<?php echo esc_attr($condition_id); ?>]" <?php if($condition_id != 'payment_method'): ?>disabled="disabled"<?php endif; ?>>
								<?php foreach ($condition['options'] as $option_id => $option_name): ?>
									<option value="<?php echo esc_attr($option_id); ?>"><?php echo esc_html($option_name); ?></option>
								<?php endforeach; ?>
							</select>
						<?php else: ?>
							<input type="text" data-condition="<?php echo esc_attr($condition_id); ?>" data-name="vp_woo_pont_<?php echo substr($group, 0, -1); ?>[X][conditions][Y][<?php echo esc_attr($condition_id); ?>]" class="value" <?php if($condition_id != 'cart_total'): ?>disabled="disabled"<?php endif; ?>>
						<?php endif; ?>
					<?php endforeach; ?>
					<a href="#" class="add-row"><span class="dashicons dashicons-plus-alt"></span></a>
					<a href="#" class="delete-row"><span class="dashicons dashicons-dismiss"></span></a>
				</li>
			</script>
			<?php
			return ob_get_clean();
		}

		public static function get_order_details($order, $group) {

			//Get order type
			$order_type = ($order->get_billing_company()) ? 'company' : 'individual';

			//Get billing address location
			$eu_countries = WC()->countries->get_european_union_countries('eu_vat');
			$billing_address = 'world';
			if(in_array($order->get_billing_country(), $eu_countries)) {
				$billing_address = 'eu';
			}

			//Get payment method id
			$payment_method = $order->get_payment_method();

			//Get product category ids and shipping classes
			$product_categories = array();
			$shipping_classes = array();
			$order_items = $order->get_items();
			foreach ($order_items as $order_item) {
				if($order_item->get_product() && $order_item->get_product()->get_category_ids()) {
					$product_categories = $product_categories+$order_item->get_product()->get_category_ids();
				}

				if($order_item->get_product() && $order_item->get_product()->get_shipping_class()) {
					$shipping_classes[] = $order_item->get_product()->get_shipping_class();
				}
			}

			//Get shipping method id
			$shipping_method = '';
			$shipping_methods = $order->get_shipping_methods();
			if($shipping_methods) {
				foreach( $shipping_methods as $shipping_method_obj ){
					$shipping_method = $shipping_method_obj->get_method_id().':'.$shipping_method_obj->get_instance_id();
				}
			}

			//Setup parameters for conditional check
			$order_details = array(
				'payment_method' => $payment_method,
				'type' => $order_type,
				'billing_address' => $billing_address,
				'billing_country' => $order->get_billing_country(),
				'product_categories' => $product_categories,
				'provider' => $order->get_meta('_vp_woo_pont_provider'),
				'shipping_classes' => $shipping_classes,
				'shipping_method' => $shipping_method,
				'products_quantity' => $order->get_item_count()
			);

			//Custom conditions
			$order_details = apply_filters('vp_woo_pont_'.$group.'_conditions_values', $order_details, $order);
			return apply_filters('vp_woo_pont_'.$group.'_conditions_values_order', $order_details, $order);

		}

		public static function get_cart_details($group) {

			//Get weight
			$cart_weight = WC()->cart->get_cart_contents_weight();

			//Get volume
			$cart_volume = VP_Woo_Pont_Helpers::get_cart_volume();
			$longest_side = VP_Woo_Pont_Helpers::get_cart_volume_longest_side();

			//Get cart total
			$cart_total = WC()->cart->get_displayed_subtotal();
			$cart_total_discount = $cart_total-(WC()->cart->get_discount_total());
			if ( WC()->cart->display_prices_including_tax() ) {
				$cart_total_discount = $cart_total_discount - WC()->cart->get_discount_tax();
			}

			//Get net values too
			$cart_total_net = WC()->cart->get_subtotal();
			$cart_total_discount_net = $cart_total_net-(WC()->cart->get_discount_total());

			//Get cart categories
			$cart_categories = array();

			//Get shipping classes
			$shipping_classes = array();

			//Loop through all products in the Cart
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$terms = get_the_terms ( $cart_item['product_id'], 'product_cat' );
				if($terms) {
					foreach ( $terms as $term ) {
						$cart_categories[] = $term->term_id;
					}
				}
				if($cart_item['data']->get_shipping_class()) {
					$shipping_classes[] = $cart_item['data']->get_shipping_class();
				}
			}

			//Get payment method
			$payment_method = WC()->session->get('chosen_payment_method');

			//Get billing details
			$customer = WC()->cart->get_customer();
			$order_type = ($customer->get_billing_company()) ? 'company' : 'individual';

			//Get billing address location
			$eu_countries = WC()->countries->get_european_union_countries('eu_vat');
			$billing_address = 'world';
			if(in_array($customer->get_billing_country(), $eu_countries)) {
				$billing_address = 'eu';
			}

			//If its pont shipping, get the selected provider
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
			$selected_pont = WC()->session->get( 'selected_vp_pont' );
			$chosen_method = '';
			$provider = '';

			if (!empty($chosen_methods) && $chosen_methods[0]) {
				$chosen_method = $chosen_methods[0];
			}

			//If shipping is vp_pont and a pont was selected
			if(strpos($chosen_method, 'vp_pont') !== false && $selected_pont) {
				$provider = $selected_pont['provider'];
			}

			//Setup an array to match conditions
			$cart_details = array(
				'cart_total' => $cart_total,
				'cart_total_discount' => $cart_total_discount,
				'cart_total_net' => $cart_total_net,
				'cart_total_discount_net' => $cart_total_discount_net,
				'product_categories' => $cart_categories,
				'weight' => $cart_weight,
				'volume' => $cart_volume,
				'payment_method' => $payment_method,
				'billing_country' => $customer->get_billing_country(),
				'billing_address' => $billing_address,
				'type' => $order_type,
				'shipping_classes' => $shipping_classes,
				'cart_count' => WC()->cart->get_cart_contents_count(),
				'longest_side' => $longest_side,
				'provider' => $provider,
				'current_date' => strtotime( wp_date( 'Y-m-d' ) ),
				'current_time' => strtotime( wp_date( 'H:i' ) ),
				'current_day' => wp_date('N'),
				'user_logged_in' => (is_user_logged_in()) ? 'yes' : 'no',
				'user_role' => (is_user_logged_in()) ? wp_get_current_user()->roles[0] : '',
			);

			//Custom conditions
			$cart_details = apply_filters('vp_woo_pont_'.$group.'_conditions_values', $cart_details);
			return apply_filters('vp_woo_pont_'.$group.'_conditions_values_cart', $cart_details);

		}

		public static function match_conditions($items, $item_id, $order_details) {
			$item = $items[$item_id];

			//Check if the conditions match
			foreach ($item['conditions'] as $condition_id => $condition) {
				$comparison = ($condition['comparison'] == 'equal');
				$items[$item_id]['conditions'][$condition_id]['match'] = false;

				//Convert date to time
				if($condition['category'] == 'current_date') {
					$condition['value'] = strtotime( wp_date( 'Y-m-d', strtotime($condition['value']) ) );
				}

				//Convert currency
				if($condition['category'] == 'cart_total' || $condition['category'] == 'cart_total_discount') {
					$condition['value'] = VP_Woo_Pont_Helpers::exchange_currency($condition['value']);
				}

				switch ($condition['category']) {
					case 'product_category':
						if(in_array($condition['value'], $order_details['product_categories'])) {
							$items[$item_id]['conditions'][$condition_id]['match'] = $comparison;
						} else {
							$items[$item_id]['conditions'][$condition_id]['match'] = !$comparison;
						}
						break;
					case 'shipping_class':
						if(in_array($condition['value'], $order_details['shipping_classes'])) {
							$items[$item_id]['conditions'][$condition_id]['match'] = $comparison;
						} else {
							$items[$item_id]['conditions'][$condition_id]['match'] = !$comparison;
						}
						break;
					default:
						switch ($condition['comparison']) {
							case 'equal':
								if($condition['value'] == $order_details[$condition['category']]) {
									$items[$item_id]['conditions'][$condition_id]['match'] = true;
								}
								break;
							case 'not_equal':
								if($condition['value'] != $order_details[$condition['category']]) {
									$items[$item_id]['conditions'][$condition_id]['match'] = true;
								}
								break;
							case 'greater':
								if((float)$condition['value'] < $order_details[$condition['category']]) {
									$items[$item_id]['conditions'][$condition_id]['match'] = true;
								}
								break;
							case 'greater_or_equal':
								if((float)$condition['value'] <= $order_details[$condition['category']]) {
									$items[$item_id]['conditions'][$condition_id]['match'] = true;
								}
								break;
							case 'less':
								if((float)$condition['value'] > $order_details[$condition['category']]) {
									$items[$item_id]['conditions'][$condition_id]['match'] = true;
								}
								break;
							case 'less_or_equal':
								if((float)$condition['value'] >= $order_details[$condition['category']]) {
									$items[$item_id]['conditions'][$condition_id]['match'] = true;
								}
								break;		
							default:
								if((float)$condition['value'] > $order_details[$condition['category']]) {
									$items[$item_id]['conditions'][$condition_id]['match'] = true;
								}
								break;
						}
						break;
				}
			}

			//Count how many matches we have
			$matched = 0;
			foreach ($items[$item_id]['conditions'] as $condition) {
				if($condition['match']) $matched++;
			}

			//Check if we need to match all or just one
			$condition_is_a_match = false;
			if($item['logic'] == 'and' && $matched == count($item['conditions'])) $condition_is_a_match = true;
			if($item['logic'] == 'or' && $matched > 0) $condition_is_a_match = true;

			return $condition_is_a_match;
		}

	}

endif;
