<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Compatibility {
	protected static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning this object is forbidden.', 'vp-woo-pont' ), '3.0.0' );
	}

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'vp-woo-pont' ), '3.0.0' );
	}

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_modules' ), 100 );
	}

	public function load_modules() {
		$module_paths = array();

		//Check if pro enabled
		$is_pro = VP_Woo_Pont_Pro::is_pro_enabled();

		if($is_pro) {

			//WooCommerce Számlázz.hu compatibility
			if ( class_exists( 'WC_Szamlazz' ) ) {
				$module_paths['wc_szamlazz'] = 'modules/class-vp-woo-pont-szamlazz.php';
			}

			//WooCommerce Woo Billingo Plus compatibility
			if ( class_exists( 'WC_Billingo_Plus' ) ) {
				$module_paths['woo_billingo_plus'] = 'modules/class-vp-woo-pont-woo-billingo.php';
			}

			//WooCommerce Shipment Tracking compatibility
			if ( class_exists( 'WC_Shipment_Tracking' ) ) {
				$module_paths['shipment_tracking'] = 'modules/class-vp-woo-pont-shipment-tracking.php';
			}

			//Yith WooCommerce Shipment Tracking compatibility
			if ( defined( 'YITH_YWOT_INIT' ) ) {
				$module_paths['yith_tracking'] = 'modules/class-vp-woo-pont-yith-tracking.php';
			}

		}

		//WooCommerce Webshippy compatibility
		if ( defined('WEBSHIPPY_ORDER_SYNC_VERSION') ) {
			$module_paths['webshippy'] = 'modules/class-vp-woo-pont-webshippy.php';
		}

		//WooCommerce iLogistic compatibility
		if ( class_exists('Ilogistic\\Ilogistic_Woo_App') ) {
			$module_paths['ilogistic'] = 'modules/class-vp-woo-pont-ilogistic.php';
		}

		//VP Extra Fees compatibility
		if ( class_exists('VP_Woo_Extra_Fees') ) {
			$module_paths['vp_extra_fees'] = 'modules/class-vp-woo-pont-extra-fees.php';
		}

		//Checkout WC compatibility
		if ( defined('CFW_NAME') ) {
			$module_paths['checkoutwc'] = 'modules/class-vp-woo-pont-checkoutwc.php';
		}

		$module_paths = apply_filters( 'vp_woo_pont_compatibility_modules', $module_paths );
		foreach ( $module_paths as $name => $path ) {
			require_once $path;
		}

	}

}
