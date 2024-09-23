<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Pro', false ) ) :

	class VP_Woo_Pont_Pro {
		public static $activation_url;
		public static $name;
		public static $id;


		public static function init() {

			//Define plugin specific stuff
			self::$activation_url = 'https://visztpeter.me/wp-json/vp_woo_license/';
			self::$name = 'vp-woo-pont';
			self::$id = str_replace('-', '_', self::$name);

			//Check and save PRO version
			add_action( 'wp_ajax_'.self::$id.'_license_activate', array( __CLASS__, 'pro_activate' ) );
			add_action( 'wp_ajax_'.self::$id.'_license_deactivate', array( __CLASS__, 'pro_deactivate' ) );
			add_action( 'wp_ajax_'.self::$id.'_license_validate', array( __CLASS__, 'pro_validate' ) );

			//Scheduled action to check license activation
			add_action( self::$id.'_pro_key_check', array( __CLASS__, 'pro_validate' ) );

			//Count labels
			add_action( 'vp_woo_pont_label_created', array( __CLASS__, 'count_labels' ), 10, 3 );
			add_action( 'vp_woo_pont_label_removed', array( __CLASS__, 'reset_labels' ) );

		}

		public static function is_pro_enabled() {
			if ((isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], ".local") !== false) || get_option('vp_woo_pont_kvikk_courier_details')){
				return true;
			}

			return get_option('_'.self::$id.'_pro_enabled', false);
		}

		public static function get_license_key() {
			return get_option('_'.self::$id.'_pro_key', '');
		}

		public static function get_license_key_meta() {
			return get_option('_'.self::$id.'_pro_meta', array());
		}

		public static function pro_activate($pro_key = false) {
			check_ajax_referer( 'vp-woo-pont-settings', 'nonce' );

			//Get submitted key
			if(!$pro_key) {
				$pro_key = sanitize_text_field($_POST['key']);
			}

			//Execute request
			$response = wp_remote_get( self::$activation_url.'activate/'.$pro_key.'/'.self::$name );

			//Check for errors
			if( is_wp_error( $response ) ) {
				wp_send_json_error(array(
					'message' => __('Unable to activate the PRO version. Please make sure that the entered data is correct.', self::$name)
				));
			}

			//Get body
			$body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );

			//Try to convert into json
			$json = json_decode( $body, true );

			//If not 200, its an error
			if($response_code != 200 || isset($json['fail'])) {
				wp_send_json_error(array(
					'message' => __('Unable to activate the PRO version. Please make sure that the entered data is correct.', self::$name)
				));
			} else {
				update_option('_'.self::$id.'_pro_key', $pro_key);
				update_option('_'.self::$id.'_pro_enabled', true);
				update_option('_'.self::$id.'_pro_meta', $json);

				//Schedule an action to check key periodically to see if its still valid
				WC()->queue()->schedule_recurring( time()+WEEK_IN_SECONDS, WEEK_IN_SECONDS, self::$id.'_pro_key_check', array(), self::$id );

				//Return success
				wp_send_json_success();
			}

		}

		public static function pro_deactivate() {
			check_ajax_referer( 'vp-woo-pont-settings', 'nonce' );
			if ( !current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			//Get submitted key
			$pro_key = self::get_license_key();

			//Execute request
			$response = wp_remote_get( self::$activation_url.'deactivate/'.$pro_key );

			//Check for errors
			if( is_wp_error( $response ) ) {
				wp_send_json_error(array(
					'message' => __('Unable to activate the PRO version. Please make sure that the entered data is correct.', self::$name)
				));
			}

			//Delete from options
			delete_option('_'.self::$id.'_pro_key');
			delete_option('_'.self::$id.'_pro_meta');
			delete_option('_'.self::$id.'_pro_enabled');

			//Stop key checks
			WC()->queue()->cancel_all( self::$id.'_pro_key_check' );

			wp_send_json_success();
		}

		public static function pro_validate() {
			check_ajax_referer( 'vp-woo-pont-settings', 'nonce' );

			//Get submitted key
			$pro_key = self::get_license_key();
			if(!$pro_key) return false;

			//Check label count
			$labels = get_option('_'.self::$id.'_labels');
			$limit = 'below';
			if($labels && $labels['count'] > 5000) {
				$limit = 'above';
			}

			//Execute request
			$response = wp_remote_get( self::$activation_url.'validate/'.$pro_key.'&limit='.$limit );

			//Check for errors	
			if( is_wp_error( $response ) ) return false;

			//Get body
			$body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );

			//Try to convert into json
			$json = json_decode( $body, true );

			//If not 200, its an error
			if($response_code != 200) return false;

			//Else, check for error
			if(isset($json['fail'])) {
				delete_option('_'.self::$id.'_pro_enabled');
			} else {
				update_option('_'.self::$id.'_pro_enabled', true);
			}

			//Update meta
			update_option('_'.self::$id.'_pro_meta', $json);

			return true;
		}

		public static function count_labels($order, $label, $provider) {

			//Setup counter if not exists yet
			$labels = get_option('_'.self::$id.'_labels');
			if(!$labels) $labels = array(
				'start' => time(),
				'count' => 0
			);

			//If more than 1 year passed, reset
			if(time() > $labels['start']+YEAR_IN_SECONDS) {
				$labels['start'] = time();
				$labels['count'] = 0;
			}

			//Don't count in dev mode
			if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], ".local") !== false || VP_Woo_Pont_Helpers::get_option($provider.'_dev_mode', 'no') == 'yes'){
				return;
			}

			//Add one if a label was generated
			$labels['count'] = $labels['count']+1;

			//Save
			update_option('_'.self::$id.'_labels', $labels);

		}

		public static function reset_labels() {

			//Check if we have a counter and remove one when a label is removed
			$labels = get_option('_'.self::$id.'_labels');
			if($labels) {
				$labels['count'] = $labels['count']-1;
				
				//Save
				update_option('_'.self::$id.'_labels', $labels);
			
			}

		}

	}

	VP_Woo_Pont_Pro::init();

endif;
