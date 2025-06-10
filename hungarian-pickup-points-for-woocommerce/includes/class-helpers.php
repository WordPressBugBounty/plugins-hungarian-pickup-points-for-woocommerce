<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Helpers', false ) ) :

	class VP_Woo_Pont_Helpers {

		//Get options stored
		public static function get_option($key, $default = '') {
			$settings = get_option( 'woocommerce_vp_pont_settings', null );
			$value = $default;

			if(get_option('vp_woo_pont_'.$key)) {
				$value = get_option('vp_woo_pont_'.$key);
			} else if($settings && isset($settings[$key]) && !empty($settings[$key])) {
				$value = $settings[$key];
			} else if(get_option($key)) {
				$value = get_option($key);
			}

			return apply_filters('vp_woo_pont_get_option', $value, $key);
		}

		//Get IPN url
		public function get_webhook_url($provider) {
			$ipn_id = add_option( '_vp_woo_pont_'.$provider.'_webhook_url', substr(md5(rand()),5)); //this will only store it if doesn't exists yet
			$url = get_admin_url().'admin-post.php?action=vp_woo_pont_webhook_'.$provider.'&id='.get_option('_vp_woo_pont_'.$provider.'_webhook_url').'&provider='.$provider;
			return $url;
		}

		//Helper function to query supported providers
		public static function get_external_provider_groups() {
			return apply_filters('vp_woo_pont_external_provider_groups', array(
				'foxpost' => __('Foxpost', 'vp-woo-pont'),
				'postapont' => __('Postapont', 'vp-woo-pont'),
				'packeta' => __('Packeta', 'vp-woo-pont'),
				'sprinter' => __('Pick Pack Pont', 'vp-woo-pont'),
				'expressone' => __('Express One', 'vp-woo-pont'),
				'gls' => __('GLS', 'vp-woo-pont'),
				'dpd' => __('DPD', 'vp-woo-pont'),
				'sameday' => __('Sameday', 'vp-woo-pont')
			));
		}

		//Helper function to query supported providers
		public static function get_supported_providers() {
			return apply_filters('vp_woo_pont_get_supported_providers', array(
				'postapont_posta' => __('Posta', 'vp-woo-pont'),
				'postapont_automata' => __('Csomagautomata', 'vp-woo-pont'),
				'postapont_postapont' => __('Postapontok', 'vp-woo-pont'),
				'foxpost' => __('Foxpost', 'vp-woo-pont'),
				'packeta_shop' => __('Z-Pont', 'vp-woo-pont'),
				'packeta_zbox' => __('Z-BOX', 'vp-woo-pont'),
				'packeta_mpl_automata' => __('MPL Csomagautomata', 'vp-woo-pont'),
				'packeta_mpl_postapont' => __('MPL Postapont', 'vp-woo-pont'),
				'packeta_foxpost' => __('Foxpost', 'vp-woo-pont'),
				'sprinter' => __('Pick Pack Pont', 'vp-woo-pont'),
				'expressone_omv' => __('OMV', 'vp-woo-pont'),
				'expressone_alzabox' => __('AlzaBox', 'vp-woo-pont'),
				'expressone_packeta' => __('Packeta', 'vp-woo-pont'),
				'expressone_exobox' => __('Automata', 'vp-woo-pont'),
				'gls_shop' => __('ParcelShop', 'vp-woo-pont'),
				'gls_locker' => __('ParcelLocker', 'vp-woo-pont'),
				'dpd_parcelshop' => __('Csomagpont', 'vp-woo-pont'),
				'dpd_alzabox' => __('AlzaBox', 'vp-woo-pont'),
				'sameday' => __('Easybox', 'vp-woo-pont'),
				'custom' => self::get_option('custom_title', __( 'Store Pickup', 'vp-woo-pont' ))
			));
		}

		//Returns providers that supports home delivery
		public static function get_supported_providers_for_home_delivery() {
			return apply_filters('vp_woo_pont_get_supported_providers_for_home_delivery', array(
				'gls' => __('GLS', 'vp-woo-pont'),
				'posta' => __('MPL', 'vp-woo-pont'),
				'packeta' => __('Packeta', 'vp-woo-pont'),
				'foxpost' => __('Foxpost', 'vp-woo-pont'),
				'dpd' => __('DPD', 'vp-woo-pont'),
				'sameday' => __('Sameday', 'vp-woo-pont'),
				'expressone' => __('Express One', 'vp-woo-pont'),
				'custom' => __( 'Custom Labels', 'vp-woo-pont' ),
				'transsped' => __('Trans-Sped ZERO', 'vp-woo-pont')
			));
		}

		public static function get_provider_name($provider_id, $prefix = false) {
			$provider_name = '';
			$old_labels = array(
				'postapont_10' => __('Posta', 'vp-woo-pont'),
				'postapont_20' => __('Mol', 'vp-woo-pont'),
				'postapont_30' => __('Csomagautomata', 'vp-woo-pont'),
				'postapont_50' => __('Coop', 'vp-woo-pont'),
				'postapont_70' => __('Mediamarkt', 'vp-woo-pont'),
				'postapont_mol' => __('Mol', 'vp-woo-pont'),
				'postapont_coop' => __('Coop', 'vp-woo-pont'),
				'postapont_mediamarkt' => __('Mediamarkt', 'vp-woo-pont'),
				'dpd' => __('DPD', 'vp-woo-pont'),
			);
			$labels = $old_labels+self::get_external_provider_groups()+self::get_supported_providers()+self::get_supported_providers_for_home_delivery();

			if(isset($labels[$provider_id])) {
				$provider_name = $labels[$provider_id];
			}

			if($prefix) {
				$carrier_labels = self::get_external_provider_groups();
				$carrier = explode('_', $provider_id)[0];
				if(in_array($carrier, array_keys($carrier_labels)) && strpos($provider_id, '_') !== false) {
					$provider_name = $carrier_labels[$carrier].' - '.$provider_name;
				}
			}

			return $provider_name;
		}

		//Helper function to get download folder paths and filenames
		public static function get_download_folder($type = 'postapont') {
			$upload_dir = wp_upload_dir( null, false );
			$basedir = $upload_dir['basedir'] . '/vp-woo-pont-db/';
			$baseurl = set_url_scheme($upload_dir['baseurl']).'/vp-woo-pont-db/';
			$random_file_name = substr(md5(rand()),5);
			$json_file_name = implode( '-', array( $type, $random_file_name ) ).'.json';
			return array('name' => $json_file_name, 'dir' => $basedir, 'path' => $basedir.$json_file_name, 'url' => $baseurl);
		}

		//Returns all json file url-s
		public static function get_json_files() {
			$paths = array();

			//Get a download folder for the urls
			$download_folders = self::get_download_folder();

			//Only load enabled providers
			$enabled_providers = self::get_option('vp_woo_pont_enabled_providers', array());

			//Loop through each pont types
			foreach ($enabled_providers as $pont_type) {

				//Check if a file name is stored
				$filename = get_option('_vp_woo_pont_file_'.$pont_type);

				//If file name exists, append to results
				if($filename) {
					$paths[] = array(
						'type' => $pont_type,
						'url' => $download_folders['url'].$filename.'?v1',
						'filename' => $filename
					);
				}

			}

			return $paths;
		}

		public static function get_cart_volume() {
			// Initializing variables
			$volume = $rate = 0;

			// Get the dimetion unit set in Woocommerce
			$dimension_unit = get_option( 'woocommerce_dimension_unit' );

			// Calculate the rate to be applied for volume in m3
			if ( $dimension_unit == 'mm' ) {
				$rate = pow(10, 9);
			} elseif ( $dimension_unit == 'cm' ) {
				$rate = pow(10, 6);
			} elseif ( $dimension_unit == 'm' ) {
				$rate = 1;
			}

			if( $rate == 0 ) return false; // Exit

			// Loop through cart items
			foreach(WC()->cart->get_cart() as $cart_item) {
				// Get an instance of the WC_Product object and cart quantity
				$product = $cart_item['data'];
				$qty     = $cart_item['quantity'];

				// Get product dimensions
				$length = $product->get_length();
				$width  = $product->get_width();
				$height = $product->get_height();

				// Calculations a item level
				if($length && $width && $height) {
					$volume += $length * $width * $height * $qty;
				}
			}

			return $volume / $rate;
		}

		public static function get_order_volume($order) {
			// Initializing variables
			$volume = $rate = 0;

			// Get the dimetion unit set in Woocommerce
			$dimension_unit = get_option( 'woocommerce_dimension_unit' );

			// Calculate the rate to be applied for volume in m3
			if ( $dimension_unit == 'mm' ) {
				$rate = pow(10, 9);
			} elseif ( $dimension_unit == 'cm' ) {
				$rate = pow(10, 6);
			} elseif ( $dimension_unit == 'm' ) {
				$rate = 1;
			}

			if( $rate == 0 ) return false; // Exit

			// Loop through cart items
			foreach($order->get_items() as $order_item) {
				// Get an instance of the WC_Product object and cart quantity
				$product = $order_item->get_product();
				$qty     = $order_item->get_quantity();

				// Get product dimensions
				if($product) {
					$length = $product->get_length();
					$width  = $product->get_width();
					$height = $product->get_height();

					// Calculations a item level
					if($length && $width && $height) {
						$volume += $length * $width * $height * $qty;
					}
				}
			}

			return $volume / $rate;
		}

		public static function get_cart_volume_longest_side() {
			$sides = array();
			$max = 0;
			foreach(WC()->cart->get_cart() as $cart_item) {
				$product = $cart_item['data'];
				$length = $product->get_length();
				$width  = $product->get_width();
				$height = $product->get_height();

				// Calculations a item level
				if($length && $width && $height) {
					$sides[] = $length;
					$sides[] = $width;
					$sides[] = $height;
				}
			}

			if($sides) {
				$max = max($sides);
			}

			return $max;
		}

		//Calculate shipping costs based on settings
		public static function calculate_shipping_costs($cart_details = false) {

			//Get weight
			if(!$cart_details) {
				$cart_details = VP_Woo_Pont_Conditions::get_cart_details('pricings');
			}
			
			//Get default cost
			$default_cost = self::get_option('cost', 0);
			$default_cost = str_replace(',','.',$default_cost);
			$default_cost = (float)$default_cost;

			//Get available providers
			$enabled_providers = get_option('vp_woo_pont_enabled_providers');

			//Get supported providers for labels
			$supported_providers = self::get_supported_providers();

			//Get custom costs table
			$costs = get_option('vp_woo_pont_pricing');
			if(!$costs) $costs = array();

			//Loop through each cost setup and see if theres a match
			$matched_provider_prices = array();
			foreach ($costs as $cost_id => $cost) {

				//Get the price
				$price = $cost['cost'];

				//Check for conditions if needed
				if($cost['conditional']) {

					//Loop through each condition and see if its a match
					$condition_is_a_match = VP_Woo_Pont_Conditions::match_conditions($costs, $cost_id, $cart_details);

					//If no match, skip to the next one
					if(!$condition_is_a_match) continue;

				}

				//If its not conditional, or there is a conditional match, just simply append provider prices
				foreach ($cost['providers'] as $provider) {

					//Make it an array, so if multiple prices are matched, we can later decide which one to use
					if(!isset($matched_provider_prices[$provider])) {
						$matched_provider_prices[$provider] = array();
					}

					//Append to matched prices array grouped by provider
					$matched_provider_prices[$provider][] = $price;

					//Create categories based on target countires(packeta for now)
					if($provider == 'packeta_shop' && isset($cost['countries']) && count($cost['countries']) > 0) {
						foreach ($cost['countries'] as $country) {
							$matched_provider_prices[$provider.'_'.$country][] = $price;
						}
					}

					//Create categories based on target countires(packeta for now)
					if($provider == 'packeta_zbox' && isset($cost['countries']) && count($cost['countries']) > 0) {
						foreach ($cost['countries'] as $country) {
							$matched_provider_prices[$provider.'_'.$country][] = $price;
						}
					}

					//Create categories based on target countires(for gls)
					if(($provider == 'gls_shop' || $provider == 'gls_locker') && isset($cost['countries']) && count($cost['countries']) > 0) {
						foreach ($cost['countries'] as $country) {
							if(substr( $country, 0, 4 ) === "gls_") {
								$country_code = substr($country, 4);
								$matched_provider_prices[$provider.'_'.$country_code][] = $price;
							}
						}
					}

					//Create categories based on target countires(for dpd)
					if(($provider == 'dpd_parcelshop' && isset($cost['countries']) && count($cost['countries']) > 0)) {
						foreach ($cost['countries'] as $country) {
							if(substr( $country, 0, 4 ) === "dpd_") {
								$country_code = substr($country, 4);
								$matched_provider_prices[$provider.'_'.$country_code][] = $price;
							}
						}
					}

				}

			}

			//Get available providers, and if one is missing from the matched prices, use the default price instead.
			//If theres no default price, that means the specific shipping provider is unavailable
			foreach ($enabled_providers as $enabled_provider) {
				if(!isset($matched_provider_prices[$enabled_provider])) {
					$matched_provider_prices[$enabled_provider] = array($default_cost);
				}
			}

			//Get info if we need lowest or highest price if theres multiple matches
			$cost_logic = self::get_option('cost_logic', 'low');

			//Loop through options and create a new array with single values, including taxes
			$provider_costs = array();
			foreach ($matched_provider_prices as $provider_id => $costs) {
				$cost = min($costs);
				if($cost_logic != 'low') {
					$cost = max($costs);
				}

				//Allow plugins to customize
				$cost = apply_filters( 'vp_woo_pont_shipping_cost', $cost, $matched_provider_prices, $provider_id);

				//Calculate based on gross total
				if(apply_filters('vp_woo_pont_shipping_cost_based_on_gross_total', false)) {
					$taxes = WC_Tax::calc_inclusive_tax( $cost, WC_Tax::get_shipping_tax_rates() );
					$cost -= reset($taxes);	
				}

				//Calculate taxes, if enabled
				$tax = array();
				if(wc_tax_enabled() && VP_Woo_Pont_Helpers::get_option('tax_status', 'taxable') == 'taxable') {
					$tax = WC_Tax::calc_shipping_tax( $cost, WC_Tax::get_shipping_tax_rates() );
				}
				if(!$tax) $tax = array();

				//Check if a free coupon is used and can overwrite the cost
				if(self::order_has_free_shipping_coupon()) {
					$provider_to_check = $provider_id;
					if (strpos($provider_id, 'packeta') !== false) {
						//$provider_to_check = 'packeta_shop';
					}

					if(in_array($provider_to_check, self::get_option('vp_woo_pont_free_shipping', array()))) {
						$cost = 0;
						$tax = array(0);
					}
				}

				//If lower than 0, hide the provider, or if $costs contains -1, hide the provider
				if($cost < 0 || in_array(-1, $costs)) continue;

				//Check if free shipping is available
				if((VP_Woo_Pont_Helpers::get_option('free_shipping_overwrite', 'no') == 'yes') && self::is_free_shipping_available()) {
					$cost = 0;
					$tax = array(0);
				}

				//Switch currency
				$formatted_cost = self::exchange_currency($cost);
				$formatted_tax = self::exchange_currency(array_sum($tax));

				//Fix for packeta name
				$label = '';
				if(isset($supported_providers[$provider_id])) {
					$label = $supported_providers[$provider_id];
				}

				//Calculate taxes too
				$provider_costs[$provider_id] = array(
					'formatted_net' => wc_price($formatted_cost),
					'formatted_gross' => wc_price($formatted_cost+$formatted_tax),
					'net' => $cost,
					'tax' => $tax,
					'label' => $label
				);

			}

			//Sort based on settings
			$sortedProviders = [];
			foreach ($enabled_providers as $provider) {
				if (isset($provider_costs[$provider])) {
					$sortedProviders[$provider] = $provider_costs[$provider];
					unset($provider_costs[$provider]);
				}
			}
			$sortedProviders += $provider_costs;
			$provider_costs = $sortedProviders;

			return apply_filters( 'vp_woo_pont_provider_costs', $provider_costs);
		}

		public static function get_shipping_cost() {
			$shipping_cost = array();

			//Get selected point
			$selected_pont = WC()->session->get( 'selected_vp_pont' );

			//If a point is selected, query the prices
			if($selected_pont) {

				//Find the prices
				$shipping_costs = self::calculate_shipping_costs();

				//Find the related provider to the selected point
				if(isset($shipping_costs[$selected_pont['provider']])) {
					$shipping_cost = $shipping_costs[$selected_pont['provider']];
					$country = (isset($selected_pont['country'])) ? $selected_pont['country'] : '';
					$country = strtolower($country);

					//Check for country pricing for packeta
					if(($selected_pont['provider'] == 'packeta_shop' || $selected_pont['provider'] == 'gls_shop' || $selected_pont['provider'] == 'gls_locker' || $selected_pont['provider'] == 'dpd_parcelshop') && $country && isset($shipping_costs[$selected_pont['provider'].'_'.$country])) {
						$shipping_cost = $shipping_costs[$selected_pont['provider'].'_'.$country];
					}

					//Check for country pricing for packeta
					if($selected_pont['provider'] == 'packeta_shop' && isset($selected_pont['carrier']) && isset($shipping_costs[$selected_pont['provider'].'_'.$selected_pont['carrier']])) {
						$shipping_cost = $shipping_costs[$selected_pont['provider'].'_'.$selected_pont['carrier']];
					}
				}

			} else {

				//If point is not selected yet, and only one price set, use that
				$shipping_costs = self::calculate_shipping_costs();

				if(count($shipping_costs) == 1) {
					$shipping_cost = current($shipping_costs);
				}

			}

			return $shipping_cost;
		}

		public static function get_payment_methods() {
			$available_gateways = WC()->payment_gateways->payment_gateways();
			$payment_methods = array();
			foreach ($available_gateways as $available_gateway) {
				if($available_gateway->enabled == 'yes') {
					$payment_methods[$available_gateway->id] = $available_gateway->title;
				}
			}
			return $payment_methods;
		}

		public static function is_cod_payment_method_available() {
			$payment_methods = self::get_payment_methods();
			$cod_method = self::get_option('cod_method', 'cod');
			return (isset($payment_methods[$cod_method]));
		}

		public static function pricing_has_payment_method_condition() {
			$costs = get_option('vp_woo_pont_pricing');
			$has_payment_method_condition = false;
			foreach ($costs as $cost_id => $cost) {

				//Check for conditions if needed
				if($cost['conditional']) {
					foreach ($cost['conditions'] as $condition_id => $condition) {
						if($condition['category'] == 'payment_method') {
							$has_payment_method_condition = true;
							break;
						}
					}
				}
			}

			//Check for COD too
			if(get_option('vp_woo_pont_cod_fees')) {
				$has_payment_method_condition = true;
			}

			return $has_payment_method_condition;
		}

		//Get shipping methods, except pont methods
		public static function get_available_shipping_methods($skip_vp_pont = true) {
			$active_methods = array();
			$custom_zones = WC_Shipping_Zones::get_zones();
			$worldwide_zone = new WC_Shipping_Zone( 0 );
			$worldwide_methods = $worldwide_zone->get_shipping_methods();
			foreach ( $custom_zones as $zone ) {
				$shipping_methods = $zone['shipping_methods'];
				foreach ($shipping_methods as $shipping_method) {
					if ( isset( $shipping_method->enabled ) && 'yes' === $shipping_method->enabled ) {
						$method_title = $shipping_method->title;
						if ($skip_vp_pont && strpos($shipping_method->id, 'vp_pont') === 0) {
							continue; // Skip this shipping method
						}
						$active_methods[$shipping_method->id.':'.$shipping_method->instance_id] = $method_title.' ('.$zone['zone_name'].' zóna)';
					}
				}
			}

			foreach ($worldwide_methods as $shipping_method_id => $shipping_method) {
				if ( isset( $shipping_method->enabled ) && 'yes' === $shipping_method->enabled ) {
					$method_title = $shipping_method->title;
					if ($skip_vp_pont && strpos($shipping_method->id, 'vp_pont') === 0) {
						continue; // Skip this shipping method
					}
					$active_methods[$shipping_method->id.':'.$shipping_method->instance_id] = $method_title.' (Worldwide)';
				}
			}

			return $active_methods;
		}

		public static function get_product_categories() {
			$categories = array();
			foreach (get_terms(array('taxonomy' => 'product_cat')) as $category) {
				$categories[$category->term_id] = $category->name;
			}
			return $categories;
		}

		public static function get_product_tags() {
			$categories = array();
			foreach (get_terms(array('taxonomy' => 'product_tag')) as $category) {
				$categories[$category->term_id] = $category->name;
			}
			return $categories;
		}

		//Currency converter
		//Query MNB for new rates
		public static function convert_currency($from, $to, $amount) {
			$transient_name = 'vp_woo_pont_currency_rate_'.strtolower($to);
			$exchange_rate = get_transient( $transient_name );
			if(!$exchange_rate) {
				$client = new SoapClient("http://www.mnb.hu/arfolyamok.asmx?wsdl");
				$soap_response = $client->GetCurrentExchangeRates()->GetCurrentExchangeRatesResult;
				$xml = simplexml_load_string($soap_response);

				$compare = $to;
				if($from != 'HUF') $compare = $from;
				foreach($xml->Day->Rate as $rate) {
					$attributes = $rate->attributes();
					if((string)$attributes->curr == $compare) {
						$exchange_rate = (string) $rate;
						$exchange_rate = str_replace(',','.',$exchange_rate);
						set_transient( $transient_name, $exchange_rate, 60*60*12 );
					}
				}
			}

			//Just to revent possible errors
			if(empty($exchange_rate)) $exchange_rate = 1;

			if($from != 'HUF') {
				return $amount*$exchange_rate;
			}

			return $amount/$exchange_rate;
		}

		//Get provider from order
		public static function get_provider_from_order($order) {

			//Check if an order id was submitted instead of an order
			//if(is_int($order)) $order = wc_get_order($order);

			//Check for the meta first, this is stored on checkout when the user selects a pickup point provider
			$provider = $order->get_meta('_vp_woo_pont_provider');

			//If no provider set, use default one set based on shipping method
			if(!$provider) $provider = self::get_paired_provider($order, false);

			//And return it. This is false if no provider has been set
			return apply_filters('vp_woo_pont_get_provider_from_order', $provider, $order);
		}

		//Get provider from order
		public static function get_carrier_from_order($order) {
			$provider = self::get_provider_from_order($order);
			if($provider) {
				$provider = explode('_', $provider)[0];
			}

			//Fix for postaponts... provider id
			if(strpos($provider, 'postapont') !== false) {
				$provider = 'posta';
			}

			//And return it. This is false if no provider has been set
			return apply_filters('vp_woo_pont_get_carrier_from_order', $provider, $order);
		}

		//Helper function to get paired provider for shipping method
		public static function get_paired_provider($order, $default = 'gls') {

			//Get saved settings
			$home_delivery_pairs = get_option('vp_woo_pont_home_delivery', array());

			//Get shipping method id
			$shipping_method = '';
			$shipping_methods = $order->get_shipping_methods();
			if($shipping_methods) {
				foreach( $shipping_methods as $shipping_method_obj ){
					$shipping_method = $shipping_method_obj->get_method_id().':'.$shipping_method_obj->get_instance_id();
				}
			}

			//Check if we have a provider set for the shipping method
			if($shipping_method != '' && isset($home_delivery_pairs[$shipping_method]) && $home_delivery_pairs[$shipping_method] != '') {
				return $home_delivery_pairs[$shipping_method];
			} else {
				return $default;
			}

		}

		public static function order_has_free_shipping_coupon() {
			$has_free_shipping = false;
			if (!WC()->cart) return $has_free_shipping;
			$applied_coupons = WC()->cart->get_applied_coupons();
			foreach( $applied_coupons as $coupon_code ){
				$coupon = new WC_Coupon($coupon_code);
				if($coupon->get_free_shipping()){
					$has_free_shipping = true;
					break;
				}
			}
			return $has_free_shipping;
		}

		public static function is_provider_configured($provider) {
			$configured = false;
			$field_to_check = '';

			if($provider == 'dpd') $field_to_check = 'dpd_password';
			if($provider == 'foxpost') $field_to_check = 'foxpost_password';
			if($provider == 'gls') $field_to_check = 'gls_password';
			if($provider == 'packeta') $field_to_check = 'packeta_api_password';
			if($provider == 'posta' || $provider == 'mpl') $field_to_check = 'posta_api_password';
			if($provider == 'expressone') $field_to_check = 'expressone_password';
			if($provider == 'sameday') $field_to_check = 'samdeday_password';
			if($provider == 'transsped') $field_to_check = 'trans_sped_password';
			if($provider == 'csomagpiac') $field_to_check = 'csomagpiac_api_token';

			if($provider == 'custom') {
				return (self::get_option('custom_enabled', 'no') == 'yes');
			}

			if(self::get_option($field_to_check)) {
				$configured = true;
			}

			if(!$configured && $provider == 'dpd' && self::get_option('dpd_jwt_token')) {
				$configured = true;
			}

			return apply_filters('vp_woo_pont_is_provider_configured', $configured, $provider);
		}

		public static function get_pdf_label_positions($provider) {

			//Setup PDF details
			$positions = array(
				'format' => false,
				'sections' => 0,
				'x' => array(),
				'y' => array(),
				'width' => 0,
				'height' => 0,
				'sticker' => false
			);

			//Get label size from settings
			$label_size = self::get_option($provider.'_sticker_size');

			//For GLS
			if($provider == 'gls') {
				$positions['sections'] = 4;

				//Portrait with 4 labels below each other
				if($label_size == 'A4_4x1') {
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 0, 0, 0);
					$positions['y'] = array(0, 74, 148, 222);
					$positions['layout'] = 'row';
				}

				//Landscape with 4 labels in a grid
				if($label_size == 'A4_2x2') {
					$positions['format'] = 'A4-L';
					$positions['x'] = array(0, 146, 0, 146);
					$positions['y'] = array(0, 0, 105, 105);
					$positions['layout'] = 'grid';
				}

				//Landscape with 4 labels in a grid
				if($label_size == 'Connect') {
					$positions['format'] = 'A4-L';
					$positions['x'] = array(0, 0, 148, 148);
					$positions['y'] = array(0, 105, 0, 105);
					$positions['layout'] = 'grid';
				}

				//Portrait with 4 labels on a grid
				if($label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

			}

			//For Foxpost
			if($provider == 'foxpost') {

				//Portrait with 8 labels on a grid
				if($label_size == 'a7') {
					$positions['sections'] = 8;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105, 0, 105, 0, 105);
					$positions['y'] = array(0, 0, 74, 74, 148, 148, 222, 222);
					$positions['layout'] = 'grid';
				}

				//Portrait with 4 labels in a grid
				if($label_size == 'a6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4-L';
					$positions['x'] = array(0, 148, 0, 148);
					$positions['y'] = array(0, 0, 105, 105);
					$positions['layout'] = 'grid';
				}

				//Portrait with 2 labels below each other
				if($label_size == 'a5') {
					$positions['sections'] = 2;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 0);
					$positions['y'] = array(0, 148);
					$positions['layout'] = 'row';
				}

				//Portrait with 4 labels on a grid
				if($label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

			}

			//For Packeta
			if($provider == 'packeta') {

				//Portrait with 8 labels on a grid
				if($label_size == 'A7 on A4') {
					$positions['sections'] = 8;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105, 0, 105, 0, 105);
					$positions['y'] = array(0, 0, 74, 74, 148, 148, 222, 222);
					$positions['layout'] = 'grid';
				}

				//Portrait with 4 labels on a grid
				if($label_size == 'A6 on A4' || $label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

				//Portrait with 16 labels on a grid
				if($label_size == '105x35mm on A4') {
					$positions['sections'] = 16;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105, 0, 105, 0, 105, 0, 105, 0, 105, 0, 105, 0, 105);
					$positions['y'] = array(0, 0, 37, 37, 74, 74, 111, 111, 148, 148, 185, 185, 222, 222, 259, 259);
					$positions['layout'] = 'grid';
				}

			}

			//For Sameday
			if($provider == 'sameday') {

				//Portrait with 4 labels on a grid
				if($label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

			}

			//For DPD
			if($provider == 'dpd') {

				//Portrait with 4 labels on a grid
				if($label_size == '' || $label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

			}

			//For Posta
			if($provider == 'posta') {

				//A5 at the top of an A4 page
				if($label_size == 'A5') {
					$positions['sections'] = 2;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 0);
					$positions['y'] = array(0, 148);
					$positions['layout'] = 'row';
				}

				//Two A5 landscape on an A4 page
				if($label_size == 'A5inA4') {
					$positions['sections'] = 2;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 0);
					$positions['y'] = array(0, 148);
					$positions['layout'] = 'row';
				}

				//Portrait with 4 labels on a grid
				if($label_size == 'A6inA4') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

			}

			//For ExpressOne
			if($provider == 'expressone') {

				//Portrait with 4 labels on a grid
				if($label_size == 4 || $label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

			}

			//For custom labels
			if($provider == 'custom') {

				//Portrait with 4 labels in a grid
				if($label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

				//Portrait with 2 labels below each other
				if($label_size == 'A5') {
					$positions['sections'] = 2;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 0);
					$positions['y'] = array(0, 148);
					$positions['layout'] = 'row';
				}

			}

			if($provider == 'csomagpiac') {

				//Portrait with 4 labels in a grid
				if($label_size == 'A6') {
					$positions['sections'] = 4;
					$positions['format'] = 'A4';
					$positions['x'] = array(0, 105, 0, 105);
					$positions['y'] = array(0, 0, 148, 148);
					$positions['layout'] = 'grid';
					$positions['sticker'] = 'A6';
				}

			}

			return apply_filters('vp_woo_pont_merged_pdf_parameters', $positions, $provider, $label_size);

		}

		public static function is_free_shipping_available() {
			$is_free_shipping_available = false;
			$packages = WC()->shipping()->get_packages();
			foreach ( $packages as $package_id => $package ) {
				if ( WC()->session->__isset( 'shipping_for_package_'.$package_id ) && isset(WC()->session->get( 'shipping_for_package_'.$package_id )['rates']) ) {
					foreach ( WC()->session->get( 'shipping_for_package_'.$package_id )['rates'] as $shipping_rate_id => $shipping_rate ) {
						$cost = (float)$shipping_rate->get_cost();
						if($cost == 0 && $shipping_rate->get_method_id() != 'vp_pont' && $shipping_rate->get_method_id() != 'local_pickup') {
							$is_free_shipping_available = true;
						}
					}
				}
			}
			return $is_free_shipping_available;
		}

		public static function get_shipping_classes() {
			$shipping_classes = WC()->shipping()->get_shipping_classes();
			$available_classes = array();
			foreach ($shipping_classes as $shipping_class) {
				$available_classes[$shipping_class->slug] = $shipping_class->name;
			}
			return $available_classes;
		}

		public static function get_package_weight_in_gramms($order) {
			$total_weight = 0;
			$order_items = $order->get_items();
			foreach ( $order_items as $item_id => $product_item ) {
				$product = $product_item->get_product();
				if($product) {
					$product_weight = $product->get_weight();
					$quantity = $product_item->get_quantity();
					if($product_weight) {
						$total_weight += floatval( $product_weight * $quantity );
					}
				}
			}

			//Check if we need to correct the weight
			$weight_corrections = get_option('vp_woo_pont_weight_corrections');
			if(!$weight_corrections) $weight_corrections = array();

			//Get order details
			$order_details = VP_Woo_Pont_Conditions::get_order_details($order, 'weight_corrections');
			foreach ($weight_corrections as $weight_correction_id => $weight_correction) {

				//Check for conditions if needed
				if($weight_correction['conditional']) {

					//Loop through each condition and see if its a match
					$condition_is_a_match = VP_Woo_Pont_Conditions::match_conditions($weight_corrections, $weight_correction_id, $order_details);

					//If no match, skip to the next one
					if(!$condition_is_a_match) continue;

				}

				//Get the price
				$correction = str_replace(' ', '', $weight_correction['correction']);

        		//Apply the correction
				if (preg_match('/^\+(\d+(\.\d+)?)(kg|g)?$/', $correction, $matches)) {
					//Add weight
					$value = floatval($matches[1]);
					$unit = isset($matches[3]) ? $matches[3] : 'kg';
					$total_weight += ($unit == 'g') ? $value / 1000 : $value;
				} elseif (preg_match('/^-(\d+(\.\d+)?)(kg|g)?$/', $correction, $matches)) {
					//Subtract weight
					$value = floatval($matches[1]);
					$unit = isset($matches[3]) ? $matches[3] : 'kg';
					$total_weight -= ($unit == 'g') ? $value / 1000 : $value;
				} elseif (preg_match('/^(\d+(\.\d+)?)(kg|g)?$/', $correction, $matches)) {
					//Set fixed weight
					$value = floatval($matches[1]);
					$unit = isset($matches[3]) ? $matches[3] : 'kg';
					$total_weight = ($unit == 'g') ? $value / 1000 : $value;
				} elseif (preg_match('/^([+-]\d+(\.\d+)?)%$/', $correction, $matches)) {
					//Increase or decrease weight by percentage
					$percentage = floatval($matches[1]);
					$total_weight += ($total_weight * $percentage / 100);
				}

			}

			//If a custom value is saved
			if($order->get_meta('_vp_woo_pont_package_weight')) {
				$total_weight = $order->get_meta('_vp_woo_pont_package_weight');
				return wc_get_weight($total_weight, 'g', 'g');
			}

			return round(wc_get_weight($total_weight, 'g', get_option('woocommerce_weight_unit')));
		}

		public static function get_carrier_logo($order) {
			$carrier = self::get_carrier_from_order($order);
			$logo = VP_Woo_Pont()::$plugin_url.'assets/images/carriers/'.$carrier.'.png';
			if($carrier == 'custom') {
				$logo = self::get_option('custom_logo');
			}
			return esc_url($logo);
		}

		//Validate hungarian phone numbers
		public static function validate_phone_number($country_code, $phone_number) {
			$valid = true;

			//Check if we need to validate phone numbers at all
			if(self::get_option('validate_phone_numbers', 'no') != 'yes') {
				return true;
			}

			//Only check for hungarian numbers
			if($country_code != 'HU') {
				return true;
			}

			//Get the phone number with only numbers
			$phone_number = preg_replace( '/[^0-9]/', '', $phone_number );
	
			//Check for unwanted prefixes
			$prefixes = array('36', '06', '0036', '036');
	
			//If it has a prefix, remove it
			foreach($prefixes as $prefix) {
				if(substr($phone_number, 0, strlen($prefix)) == $prefix) {
					$phone_number = substr($phone_number, strlen($prefix));
					break;
				}
			}
	
			//If its not 9 digits long, its invalid(2 digits for the area code, 7 digits for the number)
			if(strlen($phone_number) != 9) {
				$valid = false;
			}

			//Also validate the area code
			$area_code = substr($phone_number, 0, 2);
			$valid_area_codes = array('20', '30', '31', '50', '70');
			if(!in_array($area_code, $valid_area_codes)) {
				$valid = false;
			}

			return $valid;
		}
		public static function exchange_currency($value) {

			//Compatibility with Curcy
			if(function_exists('wmc_get_price')) {
				$value = wmc_get_price($value);
			} else {
				//Compatibility with WOOCS currency switcher
				$value = apply_filters('woocs_exchange_value', $value);
			}

			//Compatibility with WooCommerce Multilingual & Multicurrency with WPML
			if(function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on()) {
				$value = apply_filters('wcml_raw_price_amount', $value);
			}

			//Currency Switcher for WooCommerce
			if(function_exists('alg_get_product_price_by_currency')) {
				$value = alg_get_product_price_by_currency( $value, alg_get_current_currency_code() );
			}

			//Compatibility with WooPayments
			if (function_exists('WC_Payments_Multi_Currency')) {
				$multi_currency = WCPay\MultiCurrency\MultiCurrency::instance();					
				$value = $multi_currency->get_price( $value, 'shipping' );
			}

			//Compatibility with X-Currency
			if(function_exists('x_currency_exchange')) {
				$value = x_currency_exchange($value);
			}

			//Compatibility with YayCurrency
			if(defined( 'YAY_CURRENCY_VERSION' ) && class_exists('Yay_Currency\Helpers\YayCurrencyHelper')) {
				$currency = Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();		
				$value = Yay_Currency\Helpers\YayCurrencyHelper::calculate_price_by_currency( $value, true, $currency );
			}
			
			return apply_filters('vp_woo_pont_exchange_currency', $value);
		}

		//Calculate shipping costs based on settings
		public static function get_map_notes() {

			//Get weight
			$cart_details = VP_Woo_Pont_Conditions::get_cart_details('notes');

			//Get custom notes
			$notes = get_option('vp_woo_pont_notes');
			if(!$notes) $notes = array();

			//Loop through each cost setup and see if theres a match
			$matched_notes = array();
			foreach ($notes as $note_id => $note) {

				//Check for conditions if needed
				if($note['conditional']) {

					//Loop through each condition and see if its a match
					$condition_is_a_match = VP_Woo_Pont_Conditions::match_conditions($notes, $note_id, $cart_details);

					//If no match, skip to the next one
					if(!$condition_is_a_match) continue;

				}

				$matched_notes[$note['provider']] = $note['comment'];

			}

			return apply_filters( 'vp_woo_pont_notes', $matched_notes);
		}
		
		public static function get_price_display($shipping_cost) {

			//Find the smallest cost
			$minimum_cost = false;
			$minimum_cost_count = array();
			$has_free_shipping = false;
			$min_cost_formatted = '';
			$min_cost_label = '';
			foreach ($shipping_cost as $provider => $array) {
				if($array['net'] == 0) {
					$has_free_shipping = true;
				} else {
					$minimum_cost_count[] = $array['net'];
					if (!$minimum_cost) {
						$minimum_cost = $array;
					} elseif ($array['net'] < $minimum_cost['net']) {
						$minimum_cost = $array;
					}
				}
			}

			//Check how many different prices we have
			$minimum_cost_count = array_unique($minimum_cost_count);
			$minimum_cost_count = count($minimum_cost_count);

			//Minimum cost label
			if($minimum_cost) {
				if ( WC()->cart->display_prices_including_tax() ) {
					$min_cost_formatted = $minimum_cost['formatted_gross'];
				} else {
					$min_cost_formatted = $minimum_cost['formatted_net'];
				}
			}

			//Minimum cost label, only free shipping
			if($has_free_shipping && $minimum_cost_count == 0) {
				$min_cost_label = esc_html_x( 'free', 'shipping cost summary on cart & checkout', 'vp-woo-pont' );
			}

			//Minimum cost label, only 1 paid shipping
			if(!$has_free_shipping && $minimum_cost_count == 1) {
				$min_cost_label = sprintf( esc_html_x( '%s', 'shipping cost summary on cart & checkout(one shipping cost only)', 'vp-woo-pont' ), '<span class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</span>' );
			}

			//Minimum cost label, multiple paid shipping
			if(!$has_free_shipping && $minimum_cost_count > 1) {
				$min_cost_label = sprintf( esc_html_x( 'from %s', 'shipping cost summary on cart & checkout(multiple shipping costs)', 'vp-woo-pont' ) . ' ', '<span class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</span>' );
			}

			//Minimum cost label, free shipping + paid shipping
			if($has_free_shipping && $minimum_cost_count == 1) {
				$min_cost_label = sprintf( esc_html_x( 'free or %s', 'shipping cost summary on cart & checkout(free & 1 shipping cost)', 'vp-woo-pont' ) . ' ', '<span class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</span>' );
			}

			//Minimum cost label, free shipping + paid shipping
			if($has_free_shipping && $minimum_cost_count > 1) {
				$min_cost_label = sprintf( esc_html_x( 'free & from %s', 'shipping cost summary on cart & checkout(free & 1+ shipping cost)', 'vp-woo-pont' ) . ' ', '<span class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</span>' );
			}

			return $min_cost_label;
		}

		public static function get_next_workday() {
			$next_workday = date('Y-m-d', strtotime('tomorrow'));
			if(date('N', strtotime($next_workday)) >= 6) {
				$next_workday = date('Y-m-d', strtotime('next monday'));
			}
			return $next_workday;
		}

		public static function get_enabled_couriers_and_providers() {
			$providers_for_home_delivery = self::get_supported_providers_for_home_delivery();
			$enabled_point_providers = self::get_option('vp_woo_pont_enabled_providers');
			$enabled_hd_providers = array();
			foreach($providers_for_home_delivery as $provider_id => $label) {
				if(self::is_provider_configured($provider_id)) {
					$enabled_hd_providers[] = $provider_id;
				}
			}

			foreach($enabled_point_providers as $provider_id) {
				$courier = explode('_', $provider_id)[0];
				$enabled_hd_providers[] = $courier;
			}

			//Remove duplicates
			$enabled_hd_providers = array_unique($enabled_hd_providers);
			
			$results = array();
			foreach($enabled_hd_providers as $provider_id) {
				$results[$provider_id] = array(
					'label' => self::get_provider_name($provider_id),
					'id' => $provider_id,
					'points' => array()
				);

				//Add point providers
				foreach($enabled_point_providers as $point_provider_id) {
					$courier = explode('_', $point_provider_id)[0];
					if($courier == $provider_id) {
						$results[$provider_id]['points'][] = array(
							'id' => $point_provider_id,
							'label' => self::get_provider_name($point_provider_id)
						);
					}
				}
			}

			return $results;
		}

		public static function get_user_roles() {
			$roles = get_editable_roles();
			$available_roles = array();
			foreach ($roles as $role_id => $role) {
				$available_roles[$role_id] = $role['name'];
			}
			return $available_roles;
		}

	}

endif;
