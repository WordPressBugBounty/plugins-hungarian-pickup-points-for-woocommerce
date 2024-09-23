<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Walkthrough', false ) ) :

	class VP_Woo_Pont_Walkthrough {

		public static function init() {

			//Only load this if needed
			if(!get_option('_vp_woo_pont_walkthrough_finished')) {

				//Admin assets
				add_action( 'admin_init', array( __CLASS__, 'assets' ) );

				//Render HTML
				add_action( 'admin_menu', array( __CLASS__, 'create_page') );

				//Process AJAX
				add_action( 'wp_ajax_vp_woo_pont_walkthrough_finish', array( __CLASS__, 'save' ) );
			}

			//Ajax function to restart and skip setup wizard
			add_action( 'wp_ajax_vp_woo_pont_restart_setup_wizard', array( __CLASS__, 'restart_setup_wizard' ) );
			add_action( 'wp_ajax_vp_woo_pont_cancel_setup_wizard', array( __CLASS__, 'cancel_setup_wizard' ) );

		}

		//Add Admin CSS & JS
		public static function assets() {
			wp_enqueue_script( 'vp_woo_pont_walkthrough_js', plugins_url( '../assets/js/walkthrough.min.js',__FILE__ ), array('jquery', 'jquery-blockui', 'wp-color-picker'), VP_Woo_Pont::$version, TRUE );
			wp_enqueue_style( 'vp_woo_pont_walkthrough_css', plugins_url( '../assets/css/walkthrough.css',__FILE__ ), array(), VP_Woo_Pont::$version );
			wp_enqueue_style( 'wp-color-picker' );
		}

		//Create a new page(not visible in menu)
		public static function create_page() {
			add_submenu_page(
				'options.php',
				__('Vp Woo Pont Walkthrough', 'vp-woo-pont'),
				__('Vp Woo Pont Walkthrough', 'vp-woo-pont'),
				'manage_options',
				'vp-woo-pont-walkthrough',
				array( __CLASS__, 'render_page' )
			);
		}

		//Display page content
		public static function render_page() {
			include( dirname( __FILE__ ) . '/views/html-admin-walkthrough.php' );
		}

		//Save the settings
		public static function save() {
			check_ajax_referer( 'vp-woo-pont-walkthrough', 'security' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Cheatin&#8217; huh?' ) );
			}

			//Get existing options
			$options = get_option('woocommerce_vp_pont_settings');
			$separate_options = array();

			//Get submitted providers
			$providers = array();
			if ( isset( $_POST['providers'] ) ) {
				foreach ($_POST['providers'] as $checkbox_value) {
					$providers[] = wc_clean($checkbox_value);
				}
			}

			//Set costs
			$costs = array();
			if ( isset( $_POST['shipping_cost'] ) ) {
				foreach ($_POST['shipping_cost'] as $provider_id => $shipping_cost) {
					$costs[] = array(
						'cost' => $shipping_cost,
						'conditional' => false,
						'providers' => array($provider_id)
					);
					$options['cost'] = $shipping_cost;
				}
			}

			//Packate api key, DPD Weblabel login
			$text_fields = ['dpd_username', 'dpd_password', 'expressone_company_id', 'expressone_username', 'expressone_password'];
			foreach ($text_fields as $field_id) {
				if(isset($_POST[$field_id]) && !empty($_POST[$field_id])) {
					$separate_options['vp_woo_pont_'.$field_id] = sanitize_text_field($_POST[$field_id]);
				}
			}

			//Title
			if(isset($_POST['method_name']) && !empty($_POST['method_name'])) {
				$options['title'] = sanitize_text_field($_POST['method_name']);
			}

			//Primary color
			if(isset($_POST['primary_color']) && !empty($_POST['primary_color'])) {
				set_theme_mod('vp_woo_pont_primary_color', sanitize_text_field($_POST['primary_color']));
			}

			//Try to activate the pro version
			if(!VP_Woo_Pont_Pro::is_pro_enabled() && isset($_POST['pro_key']) && !empty($_POST['pro_key'])) {
				VP_Woo_Pont_Pro::pro_activate(sanitize_text_field($_POST['pro_key']));
			}

			//Try to set shipping method in zone
			if(isset($_POST['zones'])) {
				foreach ($_POST['zones'] as $zone_id) {
					$zone = WC_Shipping_Zones::get_zone($zone_id);
					$methods = $zone->get_shipping_methods();

					//See if its arleady in the zone
					$has_method = false;
					foreach ($methods as $method) {
						if($method->id == 'vp_pont') {
							$has_method = true;
						}
					}

					//If not in zone, add it
					if(!$has_method) {
						$zone->add_shipping_method( 'vp_pont' );
					}
				}
			}

			//Save values
			update_option('vp_woo_pont_enabled_providers', $providers);
			update_option('vp_woo_pont_pricing', $costs );
			update_option('woocommerce_vp_pont_settings', $options );
			update_option('_vp_woo_pont_walkthrough_finished', true);

			//Save separate options
			foreach ($separate_options as $key => $value) {
				update_option($key, $value);
			}

			//Run import again
			VP_Woo_Pont_Import_Database::schedule_actions();

			//Return response
			wp_send_json_success();
		}

		//Save an option with ajax, so the rate request widget can be hidden
		public static function restart_setup_wizard() {
			check_ajax_referer( 'vp-woo-pont-restart-setup-wizard', 'nonce' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Cheatin&#8217; huh?' ) );
			}
			update_option('_vp_woo_pont_walkthrough_finished', false);
			wp_send_json_success();
		}

		//Save an option with ajax, so the rate request widget can be hidden
		public static function cancel_setup_wizard() {
			check_ajax_referer( 'vp-woo-pont-cancel-setup-wizard', 'nonce' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Cheatin&#8217; huh?' ) );
			}
			update_option('_vp_woo_pont_walkthrough_finished', true);
			wp_send_json_success();
		}

	}

	VP_Woo_Pont_Walkthrough::init();

endif;
