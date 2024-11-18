<?php
/**
 * Database updates
 * These functions are invoked when WooCommerce is updated from a previous version,
 * but NOT when WooCommerce is newly installed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Update_Database', false ) ) :

	class VP_Woo_Pont_Update_Database {

		public static function init() {

			//Store and check version number and schedule update actions if needed
			add_action( 'admin_init', array( __CLASS__, 'check_version' ) );

			//These functions are called by scheduled actions
			add_action( 'vp_woo_pont_update_200_packeta', array( __CLASS__, 'vp_woo_pont_update_200_packeta' ) );

		}

		public static function check_version() {
			$existing_version = get_option('vp_woo_pont_version_number');
			$new_version = VP_Woo_Pont()::$version;

			//If plugin is updated, schedule imports(maybe a new provider was added for example)
			if(!$existing_version || ($existing_version != $new_version)) {
				update_option('vp_woo_pont_version_number', $new_version);

				//Run db updates if needed
				if(version_compare('2.0', $existing_version, '>')) {
					WC()->queue()->add( 'vp_woo_pont_update_200_packeta', array(), 'vp_woo_pont' );
				}

				//Run db updates if needed
				if(version_compare('2.5', $existing_version, '>')) {
					self::vp_woo_pont_update_250();
				}

				//Run db updates if needed
				if(version_compare('3.0', $existing_version, '>')) {
					self::vp_woo_pont_update_300();
				}

				//Run db updates if needed
				if(version_compare('3.4', $existing_version, '>')) {
					self::vp_woo_pont_update_340();
				}

				//Run db updates if needed
				if(version_compare('3.4.0.1', $existing_version, '>')) {
					$enabled_providers = get_option('vp_woo_pont_enabled_providers');
					update_option('vp_woo_pont_enabled_providers', array_values($enabled_providers));
					WC()->queue()->add( 'vp_woo_pont_update_postapont_list', array(), 'vp_woo_pont' );
				}

				//Re-init scheduled actions, because Kvikk was missing from the list due to a priority bug
				if(version_compare('3.4.6.1', $existing_version, '>')) {
					VP_Woo_Pont_Import_Database::schedule_actions();
				}

			}
			
		}

		public static function vp_woo_pont_update_200_packeta() {

			//Update carriers and home delivery providers
			$test = VP_Woo_Pont()->providers['packeta']->get_carriers();

			//Migrate PL to ID in pricing options
			$saved_pricing_values = get_option('vp_woo_pont_pricing');
			$saved_pricing_values_changed = false;
			if($saved_pricing_values) {
				foreach ($saved_pricing_values as $key => $saved_pricing_value) {
					if(isset($saved_pricing_value['countries']) && in_array('PL', $saved_pricing_value['countries'])) {
						$saved_pricing_values[$key]['countries'][] = 3060;
						$saved_pricing_values_changed = true;
					}
				}

				if($saved_pricing_values_changed) {
					update_option('vp_woo_pont_pricing', $saved_pricing_values);
				}
			}

			//Migrate enabled countries
			$saved_enabled_countires = get_option('vp_woo_pont_packeta_countries');
			$saved_enabled_countires_changed = false;
			if($saved_enabled_countires) {
				if(in_array('PL', $saved_enabled_countires)) {
					$saved_enabled_countires[] = 3060;
					$saved_enabled_countires_changed = true;
				}

				if(in_array('SI', $saved_enabled_countires)) {
					$saved_enabled_countires[] = 4950;
					$saved_enabled_countires[] = 19516;
					$saved_enabled_countires[] = 19517;
					$saved_enabled_countires_changed = true;
				}

				if($saved_enabled_countires_changed) {
					update_option('vp_woo_pont_packeta_countries', $saved_enabled_countires);
				}
			}

			return true;
		}

		public static function vp_woo_pont_update_250() {

			//Get saved data
			$pricings = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_pricing', array());
			$enabled_providers = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers', array());
			$existing_enabled_providers = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers', array());
			$free_shipping = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_free_shipping', array());

			//Setup a new helper array for converted new names
			$new_names = array();

			//New names for postapont
			$postapont_new_names = array(
				'postapont_10' => 'postapont_posta',
				'postapont_20' => 'postapont_mol',
				'postapont_30' => 'postapont_automata',
				'postapont_50' => 'postapont_coop',
				'postapont_70' => 'postapont_mediamarkt'
			);

			//Replace old postapont names with new
			foreach ($enabled_providers as &$provider) {
				if (isset($postapont_new_names[$provider])) {
					$new_names[$provider] = array($postapont_new_names[$provider]);
					$provider = $postapont_new_names[$provider];
				}
			}
			unset($provider);

			//Check if GLS is enabled. If it is, based on the old settings, enable parcellocker and/or parcelpoint
			if(in_array('gls', $enabled_providers)) {
				$hide_parcel_lockers = (VP_Woo_Pont_Helpers::get_option('gls_hide_parcellocker', 'no') == 'yes');
				$hide_parcel_points = (VP_Woo_Pont_Helpers::get_option('gls_hide_parcelpoint', 'no') == 'yes');

				//Remove old GLS option
				$gls_key = array_search('gls', $enabled_providers);
				$new_names['gls'] = array();
				unset($enabled_providers[$gls_key]);

				//And add gls_shop and/or gls_locker
				if (!$hide_parcel_points) {
						$enabled_providers[] = 'gls_shop';
						$new_names['gls'][] = 'gls_shop';
				}
				if (!$hide_parcel_lockers) {
						$enabled_providers[] = 'gls_locker';
						$new_names['gls'][] = 'gls_locker';
				}
			}

			//For packeta
			if(in_array('packeta', $enabled_providers)) {
				$skip_zbox = (VP_Woo_Pont_Helpers::get_option('packeta_hide_zbox', 'no') == 'yes');

				//Remove old GLS option
				$packeta_key = array_search('packeta', $enabled_providers);
				$new_names['packeta'] = array();
				unset($enabled_providers[$packeta_key]);

				//Add the new packeta id for sure
				$enabled_providers[] = 'packeta_shop';
				$new_names['packeta'][] = 'packeta_shop';

				//And if z-boxes are not hidden, add them too
				if(!$skip_zbox) {
					$enabled_providers[] = 'packeta_zbox';
					$new_names['packeta'][] = 'packeta_zbox';
				}

			}

			//For expressone
			if(in_array('expressone', $enabled_providers)) {
				$enabled_groups = VP_Woo_Pont_Helpers::get_option('expressone_groups', array('omv', 'alzabox', 'packeta'));

				//Remove old key
				$expressone_key = array_search('expressone', $enabled_providers);
				$new_names['expressone'] = array();
				unset($enabled_providers[$expressone_key]);

				//Add the new keys
				foreach($enabled_groups as $group_id) {
					$enabled_providers[] = 'expressone_'.$group_id;
					$new_names['expressone'][] = 'expressone_'.$group_id;
				}

			}

			//Fix pricing
			foreach ($pricings as $pricing_id => $pricing) {
				$providers = $pricing['providers'];
				$updated_providers = array();
				foreach ($providers as $provider) {
					if(isset($new_names[$provider])) {
						foreach ($new_names[$provider] as $new_name) {
							$updated_providers[] = $new_name;
						}
					} else {
						if(in_array($provider, $existing_enabled_providers)) {
							$updated_providers[] = $provider;
						}
					}
				}
				$pricings[$pricing_id]['providers'] = $updated_providers;
			}

			//Fix free shipping options
			$updated_free_shipping = array();
			foreach ($free_shipping as $provider) {
				if(isset($new_names[$provider])) {
					foreach ($new_names[$provider] as $new_name) {
						$updated_free_shipping[] = $new_name;
					}
				} else {
					if(in_array($provider, $existing_enabled_providers)) {
						$updated_free_shipping[] = $provider;
					}
				}
			}

			//Save new values
			update_option('vp_woo_pont_pricing', $pricings);
			update_option('vp_woo_pont_enabled_providers', array_values($enabled_providers));
			update_option('vp_woo_pont_free_shipping', $updated_free_shipping);

			//Add new database column
			global $wpdb;
			$table_name = $wpdb->prefix . 'vp_woo_pont_mpl_shipments';
			$sql = "ALTER TABLE $table_name ADD COLUMN carrier varchar(50) DEFAULT NULL";
			$wpdb->query( $sql ); //This will return an error if already exists, but doesn't really matter

			return true;
		}

		public static function vp_woo_pont_update_300() {

			//Get existing settings
			$settings = get_option( 'woocommerce_vp_pont_settings', null );

			//Keys to keep in current settings
			$settings_to_keep = array('title', 'tax_status', 'cost', 'cost_logic', 'free_shipping_overwrite', 'name_on_invoice', 'note_on_invoice');

			//Settings to update for sure
			$settings_to_update = array('custom_title', 'debug', 'show_settings_metabox', 'cod_fee_name', 'cod_reference_number', 'bulk_download_zip', 'label_reference_number', 'package_contents', 'auto_order_status', 'custom_tracking_page', 'email_tracking_number', 'email_tracking_number_pos', 'email_tracking_number_desc', 'tracking_my_account', 'order_tracking');

			//Provider related settings. We only need to migrate these if they are actually used
			$carrier_settings = array(
				'csomagpiac' => array('api_token', 'shop_id', 'pickup_point', 'dev_mode', 'extra_services'),
				'custom' => array('sender', 'logo', 'text', 'sticker_size', 'delivery_status', 'delivered_status'),
				'dpd' => array('api_type', 'username', 'password', 'jwt_token', 'customer_id', 'sender_id'),
				'expressone' => array('company_id', 'username', 'password', 'customer_sms', 'sticker_size'),
				'foxpost' => array('username', 'password', 'api_key', 'parcel_size', 'sticker_size', 'sender'),
				'gls' => array('username', 'password', 'client_id', 'dev_mode', 'sender_name', 'sender_street', 'sender_address_2', 'sender_address', 'sender_city', 'sender_postcode', 'sender_phone', 'sender_email', 'sender_country', 'contact_name', 'sticker_size', 'extra_services', 'extra_services_points', 'sm1_text', 'cod_rounding'),
				'packeta' => array('api_key', 'api_password', 'sender', 'sticker_size'),
				'posta' => array('api_key', 'api_password', 'customer_code', 'agreement_code', 'agreement_code_int', 'dev_mode', 'sender_name', 'sender_address', 'sender_city', 'sender_postcode', 'sender_phone', 'sender_email', 'sticker_size', 'sticker_format', 'retention', 'size', 'default_weight', 'extra_services', 'insurance_limit', 'payment_type', 'payment_number', 'cod_rounding'),
				'sameday' => array('username', 'pickup_point', 'personal_dropoff', 'dev_mode', 'package_type', 'default_weight', 'sticker_size'),
				'samdeday' => array('password'),
				'pactic' => array('sk_gls_service_id', 'hr_gls_service_id', 'si_gls_service_id', 'ro_sameday_service_id', 'sk_post_service_id', 'cz_ppl_service_id', 'pl_inpost_service_id', 'bg_econt_service_id'),
				'trans_sped' => array('username', 'password', 'client_code', 'dev_mode', 'sender_name', 'contact_name', 'sender_address', 'sender_city', 'sender_postcode', 'sender_phone', 'sender_email', 'food_categories', 'sticker_size', 'package_type'),
			);

			//Move carrier settings to separate options, but only if they are actually used
			foreach ($carrier_settings as $carrier => $carrier_settings) {
				if(isset($settings[$carrier.'_'.$carrier_settings[0]]) && $settings[$carrier.'_'.$carrier_settings[0]] != '') {
					foreach ($carrier_settings as $carrier_setting) {
						if(isset($settings[$carrier.'_'.$carrier_setting])) {
							update_option('vp_woo_pont_'.$carrier.'_'.$carrier_setting, $settings[$carrier.'_'.$carrier_setting]);
						}
					}
				}
			}

			//Move settings to new options
			foreach ($settings_to_update as $setting) {
				if(isset($settings[$setting])) {
					update_option('vp_woo_pont_'.$setting, $settings[$setting]);
				}
			}

			//Remove old settings
			$new_settings = array();
			foreach ($settings_to_keep as $setting) {
				if(isset($settings[$setting])) {
					$new_settings[$setting] = $settings[$setting];
				}
			}

			//And save new settings
			update_option('woocommerce_vp_pont_settings', $new_settings);

			//Just in case save the old one as backup
			update_option('woocommerce_vp_pont_settings_old', $settings);

			return true;
		}

		public static function vp_woo_pont_update_340() {
			$enabled_providers = get_option('vp_woo_pont_enabled_providers');
			$free_shipping_coupon = get_option('vp_woo_pont_free_shipping', array());
			$cod_disabled = get_option('vp_woo_pont_cod_disabled', array());
		
			// Providers to remove
			$old_providers = array('postapont_mol', 'postapont_mediamarkt', 'postapont_coop');
			$new_provider = 'postapont_postapont';
		
			// Replace old providers with new one in enabled providers, free shipping, and cod disabled
			$enabled_providers = self::replace_providers($enabled_providers, $old_providers, $new_provider);
			$free_shipping_coupon = self::replace_providers($free_shipping_coupon, $old_providers, $new_provider);
			$cod_disabled = self::replace_providers($cod_disabled, $old_providers, $new_provider);
		
			// Check for DPD and if exists, split that to two
			$dpd_replacements = array('dpd_parcelshop', 'dpd_alzabox');
			$enabled_providers = self::replace_dpd($enabled_providers, $dpd_replacements);
			$free_shipping_coupon = self::replace_dpd($free_shipping_coupon, $dpd_replacements);
			$cod_disabled = self::replace_dpd($cod_disabled, $dpd_replacements);
		
			//Replace in pricing options
			$pricings = get_option('vp_woo_pont_pricing');
			foreach($pricings as $pricing_id => $pricing) {

				//If contains old providers, replace them
				$providers = $pricing['providers'];
				$updated_providers = self::replace_providers($providers, $old_providers, $new_provider);
				$updated_providers = self::replace_dpd($updated_providers, $dpd_replacements);

				//Update the pricing
				$pricings[$pricing_id]['providers'] = $updated_providers;
			}

			//Save new values
			update_option('vp_woo_pont_enabled_providers', $enabled_providers);
			update_option('vp_woo_pont_free_shipping', $free_shipping_coupon);
			update_option('vp_woo_pont_cod_disabled', $cod_disabled);
			update_option('vp_woo_pont_pricing', $pricings);

			return true;
		}

		private static function replace_providers($array, $old_providers, $new_provider) {
			foreach ($old_providers as $old_provider) {
				$key = array_search($old_provider, $array);
				if ($key !== false) {
					$array[$key] = $new_provider;
				}
			}
			return array_unique($array);
		}
		
		private static function replace_dpd($array, $replacements) {
			if (($key = array_search('dpd', $array)) !== false) {
				unset($array[$key]);
				$array = array_merge($array, $replacements);
			}
			return array_unique($array);
		}

	}

 VP_Woo_Pont_Update_Database::init();

endif;
