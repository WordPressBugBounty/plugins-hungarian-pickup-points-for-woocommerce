<?php
/*
Plugin Name: Hungarian Pickup Points & Shipping Labels for WooCommerce
Plugin URI: http://visztpeter.me
Description: Pickup points map for WooCommerce stores in the hungarian market
Author: Viszt Péter
Author URI: https://visztpeter.me
Text Domain: vp-woo-pont
Domain Path: /languages/
Version: 3.6
WC requires at least: 7.0
WC tested up to: 9.9.3
Requires Plugins: woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! defined( 'VP_WOO_PONT_PLUGIN_FILE' ) ) {
	define( 'VP_WOO_PONT_PLUGIN_FILE', __FILE__ );
}

class VP_Woo_Pont {
	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basename;
	public static $version;
	public $template_base = null;
	public $settings = null;
	protected static $_instance = null;
	public static $sidebar_loaded = false;
	public $labels = null;
	public $tracking = null;
	public static $license;

	//Load providers
	public $providers = array();

	//Ensures only one instance of class is loaded or can be loaded
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	//Just for a little extra security
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning this object is forbidden.', 'vp-woo-pont' ) );
	}

	//Just for a little extra security
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'vp-woo-pont' ) );
	}

	//Construct
	public function __construct() {

		//Register activation hook
		register_activation_hook( __FILE__, array( $this, 'on_activate') );

		//Runs when plugin is successfully activated
		add_action( 'activated_plugin', array( $this, 'on_activate_success') );

		//Default variables
		self::$plugin_prefix = 'vp_woo_pont';
		self::$plugin_basename = plugin_basename(__FILE__);
		self::$plugin_path = trailingslashit(dirname(__FILE__));
		self::$version = '3.6';
		self::$plugin_url = plugin_dir_url(self::$plugin_basename);

		//Checkout Block Compat
		require_once( plugin_dir_path( __FILE__ ) . 'includes/block/pont-picker-block.php' );

		//Helper functions & classes
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-pro.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-helpers.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-conditions.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-import.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-customizer.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-walkthrough.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-update.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-cod.php' );

		//Check if pro enabled
		$is_pro = VP_Woo_Pont_Pro::is_pro_enabled();

		//Load shipment management, if pro is enabled
		if($is_pro) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-shipments.php' );
		}

		//Background label generator
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-background-generator.php' );

		//Load provider classes
		add_action('init', function(){

			//Load settings
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-settings.php' );
			$this->load_provider_classes();

		});

		//Init label generators
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-labels.php' );
		$this->labels = new VP_Woo_Pont_Labels();

		//Load tracking related stuff
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-tracking.php' );
		$this->tracking = new VP_Woo_Pont_Tracking();

		//Include compatibility modules if pro enabled
		require_once( plugin_dir_path( __FILE__ ) . 'includes/compatibility/class-compatibility.php' );
		VP_Woo_Pont_Compatibility::instance();

		//Plugin loaded
		add_action( 'plugins_loaded', array( $this, 'init' ), 11 );

		//HPOS compatibility
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatible' ) );

		//Admin assets
		//add_action( 'admin_init', array( $this, 'admin_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_js' ) );

		//Frontend assets
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_js' ));

		// Load admin notices
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-print.php' );

		//Plugin links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		//Create new shipping method
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_init' ) );
		add_filter( 'woocommerce_load_shipping_methods', array( $this, 'add_method' ), 11 );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ));

		//Add custom UI to checkout page
		add_action( 'woocommerce_review_order_after_shipping', array($this, 'checkout_ui'));
		add_action( 'wp_footer', array($this, 'checkout_map_ui'));

		//Add custom UI to cart page
		add_action( 'woocommerce_cart_totals_after_shipping', array($this, 'cart_ui'));

		//Function when a pont is selected
		add_action('wc_ajax_vp_woo_pont_select', array( $this, 'select_pont_with_ajax' ));

		//Change shipping option label on the checkout page
		add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'change_shipping_method_label' ), 10, 2);

		//Remove shipping address option if a point is selected
		add_filter( 'woocommerce_cart_needs_shipping_address', array( $this, 'hide_shipping_address' ));

		//Validate the vat number on checkout
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout' ), 10, 2);

		//Saves the value to order meta
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_checkout' ) );

		//Display the selected provider in the formatted shipping address
		add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'format_shipping_address'), 10, 3);
		add_filter( 'woocommerce_order_shipping_to_display', array( $this, 'display_cost_in_emails'), 10, 3);

		//Ajax function to remove selected point from order
		add_action( 'wp_ajax_vp_woo_pont_remove_point', array( $this, 'remove_pont_from_order' ) );
		add_action( 'wp_ajax_vp_woo_pont_replace_point', array( $this, 'replace_pont_in_order' ) );
		add_action( 'wp_ajax_vp_woo_pont_save_provider', array( $this, 'save_provider_in_order' ) );

		//Filter orders bassed on provider type
		add_action( 'restrict_manage_posts', array( $this, 'display_provider_filter' ) );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'display_provider_filter_hpos' ) );
		add_action( 'pre_get_posts', array( $this, 'process_provider_filter' ) );
		add_action( 'woocommerce_shop_order_list_table_prepare_items_query_args', array( $this, 'process_provider_filter_hpos' ) );

		//Import and export functions
		add_action( 'wp_ajax_vp_woo_pont_export_settings', array( $this, 'export_settings' ) );
		add_action( 'wp_ajax_vp_woo_pont_import_settings', array( $this, 'import_settings' ) );

		//Runs when a shipping method is selected
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'shipping_method_selected' ));

		//Show package and label details in order preview modal
		add_filter( 'woocommerce_admin_order_preview_get_order_details', array( $this, 'add_label_in_preview_modal'), 20, 2 );
		add_action( 'woocommerce_admin_order_preview_end', array( $this, 'show_label_in_preview_modal') );

		//Hide shipping method if theres no available pickup point to select
		add_filter( 'woocommerce_package_rates', array( $this, 'hide_shipping_method_if_no_points'), 100 );

		//Show selected pickup point in address edit
		add_action( 'woocommerce_after_edit_address_form_shipping', array( $this, 'pickup_point_selector_my_account'));

		//Free shipping if another free shipping method exists
		add_filter( 'woocommerce_package_rates', array($this, 'free_shipping_when_free_shipping_exists'), 10, 2);

		//Shows shipping address section even though this is a local pickup
		add_filter( 'woocommerce_order_hide_shipping_address', array($this, 'show_shipping_address'), 11);

	}

	//When plugin is activated
	public function on_activate() {
		$upload_dir = wp_upload_dir();

		$files = array(
			array(
				'base' => $upload_dir['basedir'] . '/vp-woo-pont-db',
				'file' => 'index.html',
				'content' => ''
			),
			array(
				'base' => $upload_dir['basedir'] . '/vp-woo-pont-labels',
				'file' => 'index.html',
				'content' => ''
			)
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}

		//Store and check version number
		$version = get_option('vp_woo_pont_version_number');

		//If plugin is reinstall while it was already installed once, re-run the import
		if($version && $version == self::$version) {
			VP_Woo_Pont_Import_Database::schedule_actions();
		}

		//Create table related to MPL
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'vp_woo_pont_mpl_shipments';

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			packages longtext NOT NULL,
			orders longtext NOT NULL,
			pdf varchar(255) NOT NULL,
			carrier varchar(50) DEFAULT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function on_activate_success($plugin) {
		//Redirect to setup wizard if not run already
		if(!get_option('_vp_woo_pont_walkthrough_finished') && $plugin == 'hungarian-pickup-points-for-woocommerce/index.php') {
			exit( wp_redirect( admin_url( 'options.php?page=vp-woo-pont-walkthrough' ) ) );
		}
	}

	//Load provider classes
	public function load_provider_classes() {
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-foxpost.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-packeta.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-gls.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-posta.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-dpd.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-sameday.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-expressone.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-custom.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-transsped.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-pactic.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-csomagpiac.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'includes/providers/class-kvikk.php' );
		$this->providers['foxpost'] = new VP_Woo_Pont_Foxpost();
		$this->providers['packeta'] = new VP_Woo_Pont_Packeta();
		$this->providers['gls'] = new VP_Woo_Pont_GLS();
		$this->providers['posta'] = new VP_Woo_Pont_Posta();
		$this->providers['dpd'] = new VP_Woo_Pont_DPD();
		$this->providers['sameday'] = new VP_Woo_Pont_Sameday();
		$this->providers['expressone'] = new VP_Woo_Pont_ExpressOne();
		$this->providers['custom'] = new VP_Woo_Pont_Custom();
		$this->providers['transsped'] = new VP_Woo_Pont_TransSped();
		$this->providers['pactic'] = new VP_Woo_Pont_Pactic();
		$this->providers['csomagpiac'] = new VP_Woo_Pont_Csomagpiac();
		$this->providers['kvikk'] = new VP_Woo_Pont_Kvikk();
	}

	//Loads when plugins initialized
	public function init() {

		//Load translations
		load_plugin_textdomain( 'vp-woo-pont', false, basename( dirname( __FILE__ ) ) . '/languages/' );

		//Check if pro enabled
		$is_pro = VP_Woo_Pont_Pro::is_pro_enabled();

		//Load automations only if pro enabled
		if($is_pro) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-automations.php' );
		}

	}

	//Declares WooCommerce HPOS compatibility.
	public function woocommerce_hpos_compatible() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}

	//Add Admin CSS & JS
	public function admin_js() {
		$print_js = 'print.min.js';
		if(apply_filters('vp_woo_pont_print_js_compat', false)) {
			$print_js = 'print.compat.min.js';
		}

		//Get current screen
		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';

		if(
			in_array($screen_id, wc_get_screen_ids()) || 
			$screen_id == 'woocommerce_page_vp-woo-pont-shipments' || 
			(isset( $_GET['page']) && ($_GET['page'] == 'vp-woo-pont-update' || $_GET['page'] == 'vp-woo-pont-walkthrough')) || 
			(isset( $_GET['section']) && ($_GET['section'] == 'vp_carriers' || $_GET['section'] == 'vp_labels' || $_GET['section'] == 'vp_tracking')) ||
			(isset( $_GET['tab']) && ($_GET['tab'] == 'shipping'))
		) {
			wp_enqueue_script( 'vp_woo_pont_print_js', plugins_url( '/assets/js/'.$print_js,__FILE__ ), array('jquery'), VP_Woo_Pont::$version, TRUE );
			wp_enqueue_script( 'vp_woo_pont_tiny_autocomplete', plugins_url( '/assets/js/tiny-autocomplete.js',__FILE__ ), array('jquery'), VP_Woo_Pont::$version, TRUE );
			wp_enqueue_script( 'vp_woo_pont_admin_js', plugins_url( '/assets/js/admin.min.js',__FILE__ ), array('jquery', 'wc-backbone-modal', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-tiptip'), VP_Woo_Pont::$version, TRUE );
			wp_enqueue_style( 'vp_woo_pont_admin_css', plugins_url( '/assets/css/admin.css',__FILE__ ), array(), VP_Woo_Pont::$version );
		}

		$vp_woo_pont_local = array(
			'loading' => plugins_url( '/assets/images/ajax-loader.gif',__FILE__ ),
			'providers' => VP_Woo_Pont_Helpers::get_supported_providers(),
			'print_link' => (VP_Woo_Pont_Helpers::get_option('print_link', 'no') == 'yes'),
			'sticker_parameters' => array(),
			'show_settings_metabox' => (VP_Woo_Pont_Helpers::get_option('show_settings_metabox', 'no') == 'yes'),
			'print_url' => esc_url(add_query_arg(array('vp_woo_pont_label_pdf' => 'X','position' => 'Y'), get_admin_url() )),
			'bulk_download_zip' => (VP_Woo_Pont_Helpers::get_option('bulk_download_zip', 'no') == 'yes'),
			'nonces' => array(
				'generate' => current_user_can( 'manage_woocommerce' ) ? wp_create_nonce( 'vp-woo-pont-generate' ) : null,
				'settings' => current_user_can( 'manage_woocommerce' ) ? wp_create_nonce( 'vp-woo-pont-settings' ) : null,
				'tracking' => current_user_can( 'manage_woocommerce' ) ? wp_create_nonce( 'vp-woo-pont-tracking' ) : null,
			),
		);

		//Check for merged printing
		foreach ($this->labels->supported_providers as $provider) {
			$vp_woo_pont_local['sticker_parameters'][$provider] = VP_Woo_Pont_Helpers::get_pdf_label_positions($provider);
		}

		//Load json file urls
		$json_files = VP_Woo_Pont_Helpers::get_json_files();
		$vp_woo_pont_local['files'] = $json_files;
		wp_localize_script( 'vp_woo_pont_admin_js', 'vp_woo_pont_params', $vp_woo_pont_local );
	}

	//Frontend CSS & JS
	public function frontend_js() {

		//Check if we need to show it on the cart page too
		$show_in_cart = false;
		if(is_cart() && get_option('vp_woo_pont_show_on_cart', 'yes') == 'yes') {
			$show_in_cart = true;
		}

		$show_in_cart = apply_filters('vp_woo_pont_load_frontend_js', $show_in_cart);

		//Only on the checkout page
		if(is_checkout() || $show_in_cart || is_account_page()) {
			wp_enqueue_style( 'vp_woo_pont_frontend_css', plugins_url( '/assets/css/frontend.css',__FILE__ ), array(), VP_Woo_Pont::$version );
			wp_enqueue_script( 'vp_woo_pont_frontend_js', plugins_url( '/assets/js/frontend.min.js',__FILE__ ), array('jquery'), VP_Woo_Pont::$version );
			$vp_woo_pont_local = array(
				'files' => VP_Woo_Pont_Helpers::get_json_files(),
				'custom_points' => VP_Woo_Pont_Helpers::get_option('vp_woo_pont_points'),
				'enabled_providers' => array_values(VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers', array())),
				'prices_including_tax' => WC()->cart->display_prices_including_tax(),
				'refresh_payment_methods' => VP_Woo_Pont_Helpers::pricing_has_payment_method_condition(),
				'default_center_position' => array(47.25525656277509, 19.54590752720833),
				'open_hours' => (get_option('vp_woo_pont_show_open_hours', 'no') == 'yes'),
				'show_on_change' => (get_option('vp_woo_pont_show_on_change', 'yes') == 'yes'),
				'nonce' => wp_create_nonce( 'vp-woo-pont-map' ),
				'ajax_url' => WC()->ajax_url(),
				'wc_ajax_url' => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			);

			wp_localize_script( 'vp_woo_pont_frontend_js', 'vp_woo_pont_frontend_params', apply_filters('vp_woo_pont_frontend_params', $vp_woo_pont_local) );
		}

		//If its the tracking page
		if(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false) && (is_page(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false)) || is_wc_endpoint_url( 'view-order' ))) {
			wp_enqueue_style( 'vp_woo_pont_frontend_css', plugins_url( '/assets/css/frontend.css',__FILE__ ), array(), VP_Woo_Pont::$version );
			wp_enqueue_style( 'vp_woo_pont_frontend_tracking', plugins_url( '/assets/css/tracking.css',__FILE__ ), array(), VP_Woo_Pont::$version );
			wp_enqueue_script( 'vp_woo_pont_frontend_tracking', plugins_url( '/assets/js/frontend-tracking.min.js',__FILE__ ), array('jquery'), VP_Woo_Pont::$version );
		}

	}

	//Plugin links
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . esc_url(admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_pont' )) . '" aria-label="' . esc_attr__( 'VP Woo Pont Settings', 'vp-woo-pont' ) . '">' . esc_html__( 'Settings', 'vp-woo-pont' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	public static function plugin_row_meta( $links, $file ) {
		$basename = plugin_basename( VP_WOO_PONT_PLUGIN_FILE );
		if ( $basename !== $file ) {
			return $links;
		}

		$row_meta = array(
			'documentation' => '<a href="https://visztpeter.me/dokumentacio/" target="_blank" aria-label="' . esc_attr__( 'VP Woo Pont Documentation', 'vp-woo-pont' ) . '">' . esc_html__( 'Documentation', 'vp-woo-pont' ) . '</a>'
		);

		if (!VP_Woo_Pont_Pro::is_pro_enabled() ) {
			$row_meta['get-pro'] = '<a target="_blank" rel="noopener noreferrer" style="color:#46b450;" href="https://visztpeter.me/woocommerce-csomagpont-integracio/" aria-label="' . esc_attr__( 'VP Woo Pont Pro version', 'vp-woo-pont' ) . '">' . esc_html__( 'Pro version', 'vp-woo-pont' ) . '</a>';
		}

		return array_merge( $links, $row_meta );
	}

	//Load gateway class
	public function shipping_init() {
		include_once __DIR__ . '/includes/class-wc-shipping-pont.php';
	}

	//Add shipping method
	public function add_shipping_method($methods) {
		$methods['vp_pont'] = 'WC_Shipping_Pont';
		return $methods;
	}

	//Add shipping method
	public function add_method() {
		if (WC()->shipping && class_exists('WC_Shipping_Pont')) {
			WC()->shipping->register_shipping_method( new WC_Shipping_Pont() );
		}
	}

	//Check if we are on the settings page
	public function is_settings_page() {
		global $current_section;
		$is_settings_page = false;
		if( isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && 'shipping' === $_GET['tab'] && 'vp_pont' === $_GET['section'] ) {
			$is_settings_page = true;
		}
		return $is_settings_page;
	}

	//Render custom html for the checkout page
	public function checkout_ui() {

		//Get selected shipping methd
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		//If vp_pont is chosen
		$is_vp_pont_selected = false;
		foreach ($chosen_methods as $chosen_method) {
			if(strpos($chosen_method, 'vp_pont') !== false) {
				$is_vp_pont_selected = true;
			}
		}

		//Allow plugins to customize
		$is_vp_pont_selected = apply_filters('vp_woo_pont_is_vp_pont_selected_checkout_ui', $is_vp_pont_selected);

		//Get selected pont
		$selected_pont = WC()->session->get( 'selected_vp_pont' );

		//Get shipping cost
		$shipping_costs = VP_Woo_Pont_Helpers::calculate_shipping_costs();
		$shipping_cost = VP_Woo_Pont_Helpers::get_shipping_cost();
		$notes = VP_Woo_Pont_Helpers::get_map_notes();

		//Use wc_get_template, so it can be overwritten from a theme if needed
		wc_get_template('checkout/pont.php', array(
			'is_vp_pont_selected' => $is_vp_pont_selected,
			'selected_vp_pont' => $selected_pont,
			'shipping_costs' => $shipping_costs,
			'shipping_cost' => $shipping_cost,
			'notes' => $notes
		), false, VP_Woo_Pont::$plugin_path . '/templates/');
	}

	//Render custom html for the map modal window on the checkout page and cart page
	public function checkout_map_ui() {
		$load_map = apply_filters('vp_woo_pont_load_pont_map', false);

		//Check if we need to show it on the cart page too
		$show_in_cart = false;
		if(is_cart() && get_option('vp_woo_pont_show_on_cart', 'yes') == 'yes') {
			$show_in_cart = true;
		}

		if($show_in_cart || is_checkout() || $load_map || is_account_page()) {
			$cod_available = VP_Woo_Pont_Helpers::is_cod_payment_method_available();
			$show_checkboxes = (get_option('vp_woo_pont_filter_checkbox', 'no') == 'yes');
			$days = array(
				__('Monday', 'vp-woo-pont'),
				__('Tuesday', 'vp-woo-pont'),
				__('Wednesday', 'vp-woo-pont'),
				__('Thursday', 'vp-woo-pont'),
				__('Friday', 'vp-woo-pont'),
				__('Saturday', 'vp-woo-pont'),
				__('Sunday', 'vp-woo-pont')
			);
			wc_get_template('checkout/pont-map.php', array('days' => $days, 'cod_available' => $cod_available, 'show_checkboxes' => $show_checkboxes), false, VP_Woo_Pont::$plugin_path . '/templates/');
		}
	}

	public function find_point_info($provider, $point_id) {

		//Use this as the provider for the json files
		$provider_json = $provider;

		//Backward compat
		$postapont_new_names = array(
			'postapont_10' => 'postapont_posta',
			'postapont_20' => 'postapont_mol',
			'postapont_30' => 'postapont_automata',
			'postapont_50' => 'postapont_coop',
			'postapont_70' => 'postapont_mediamarkt'
		);

		if(isset($postapont_new_names[$provider_json])) {
			$provider_json = $postapont_new_names[$provider_json];
		}

		//Get submitted data
		$points = array();
		$download_folders = VP_Woo_Pont_Helpers::get_download_folder();
		$point = false;

		//Get the JSON file based on the provider type
		if($provider_json != 'custom') {
			$filename = get_option('_vp_woo_pont_file_'.$provider_json);
			$filepath = $download_folders['dir'].$filename;
			$json_file = file_get_contents($filepath);

			//Check if file exists
			if($json_file === false) {
				return false;
			}

			//Check if its a valid json file
			$json = json_decode($json_file, true);
			if ($json === null) {
				return false;
			}

			//Set points to find by id
			$points = $json;

		} else {

			//For custom, just load the stored points
			$points = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_points');

		}

		//Find the point with the ID
		if(is_array($points)) {
			foreach ($points as $single_point) {
				if($single_point['id'] == $point_id) {
					$point = $single_point;
					break;
				}
			}
		}

		//Check if we have a point found
		if($point) {

			//Set provider ID just in case
			$point['provider'] = $provider;
			$point['provider_json'] = $provider_json;

		}

		//Return point or false
		return $point;

	}

	//Runs when a point is selected from the map
	public function select_pont_with_ajax() {

		//Use the already existing woocommerce nonce, so we don't need to create one just for this
		check_ajax_referer( 'vp-woo-pont-map', 'security' );

		//Get submitted data
		$provider = sanitize_text_field($_POST['provider']);
		$id = sanitize_text_field($_POST['id']);
		$points = array();
		$download_folders = VP_Woo_Pont_Helpers::get_download_folder();
		$point = false;

		//Get point data
		$point = $this->find_point_info($provider, $id);

		//Check if we have a point found
		if($point) {

			//Reset shipping cost cache
			$packages = WC()->cart->get_shipping_packages();
			foreach ($packages as $key => $value) {
				$shipping_session = "shipping_for_package_$key";
				unset(WC()->session->$shipping_session);
			}

			//Store it in the checkout session. Use session, because it will remember the selected point if the checkout page is reloaded
			WC()->session->set('selected_vp_pont', $point);
 
			//If it was on the my account page
			if(isset($_POST['account'])) {

				// Get the customer id
				$customer_id = get_current_user_id();

				//Save in meta
				update_user_meta( $customer_id, '_vp_woo_pont_point_id', $point['provider'].'|'.$point['id'] );

			}

			//Allow plugins to hook in
			do_action('vp_woo_pont_point_selected', $point);

			//And return the point, maybe processing is done in JS
			wp_send_json_success(array('point' => $point));

		} else {

			//Return error if it doesn't exists
			wp_send_json_error();

		}

	}

	//Change label
	public function change_shipping_method_label($label, $method) {

		//For our own method, include a custom label and icons
		if($method->method_id == 'vp_pont') {

			//Get shippign cost
			$shipping_cost = VP_Woo_Pont_Helpers::calculate_shipping_costs();

			//Create an array of providers, for icon classes
			$provider_icons = array();

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
				if(in_array($provider, array_keys(VP_Woo_Pont_Helpers::get_supported_providers()))) {
					$name = VP_Woo_Pont_Helpers::get_provider_name($provider);
					$provider_icons[] = '<i class="vp-woo-pont-provider-icon-'.$provider.'" data-name="'.esc_attr($name).'" data-shipping-cost="'.wp_strip_all_tags($array['formatted_gross']).'"></i>';
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

			//Create new labels with price and optional icons
			$label = '<span class="vp-woo-pont-shipping-method-label">'.$method->get_label().': <span class="vp-woo-pont-shipping-method-label-price">'.$min_cost_label.'</span></span>';

			//If we need to display small icons below the label(based on customizer option)
			if(get_option('vp_woo_pont_small_icons', 'yes') == 'yes' && (is_checkout() || is_cart())) {
				$label .= '<span class="vp-woo-pont-shipping-method-icons">'.implode(' ', $provider_icons).'</span>';
			}

			//For developers to modify
			$label = apply_filters('vp_woo_pont_shipping_method_label', $label, $shipping_cost);

		}

		return $label;
	}

	public function hide_shipping_address($needs_shipping_address) {

		//Get selected shipping method
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		//If vp_pont is chosen
		if(!empty($chosen_methods)) {
			foreach ($chosen_methods as $chosen_method) {
				if(strpos($chosen_method, 'vp_pont') !== false) {

					//WC Checkout compatibility
					if(!defined('CFW_NAME')) {
						//$needs_shipping_address = false;
					}

				}
			}
		}

		return $needs_shipping_address;
	}

	public function validate_checkout($fields, $errors) {

		//Skip if we don't need to ship
		if(!WC()->cart->needs_shipping_address()) {
			return;
		}

		//If its the vp_pont shippign method
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$selected_pont = WC()->session->get( 'selected_vp_pont' );
	  	$vp_pont_chosen = false;

		//Check if at least one of the shipping methods are vp_pont
		foreach ($chosen_methods as $method) {
			if (strpos($method, 'vp_pont') !== false) {
				$vp_pont_chosen = true;
				break;
			}
		}

		//Check if a a vp_pont is selected
		if($vp_pont_chosen && !$selected_pont) {
			$errors->add( 'validation', apply_filters('vp_woo_pont_required_pont_message', esc_html__( 'Please select a pick-up point or choose a different shipping method.', 'vp-woo-pont'), $fields) );
		} else {

			//Check payment method for Sameday
			if(isset($fields['payment_method']) && $fields['payment_method'] == 'cod' && isset($selected_pont['provider'])) {
				if( $selected_pont['provider'] == 'sameday' && isset($selected_pont['cod']) && !$selected_pont['cod']) {
					$errors->add( 'validation', apply_filters('vp_woo_pont_sameday_cod_message', esc_html__( 'Cash on delivery is not available on the selected pick-up point. Please select a different payment method or pick-up point!', 'vp-woo-pont'), $fields) );
				}

				//Check payment method for DPD
				if( $selected_pont['provider'] == 'dpd' && isset($selected_pont['cod']) && !$selected_pont['cod']) {
					$errors->add( 'validation', apply_filters('vp_woo_pont_dpd_cod_message', esc_html__( 'Cash on delivery is not available on the selected pick-up point. Please select a different payment method or pick-up point!', 'vp-woo-pont'), $fields) );
				}
			}

		}

		//Check if the point selected is valid
		if($vp_pont_chosen && $selected_pont) {
			$enabled_providers = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers');
			$provider = $selected_pont['provider'];
			if(!in_array($provider, $enabled_providers)) {
				$errors->add( 'validation', apply_filters('vp_woo_pont_required_pont_message', esc_html__( 'Please select a pick-up point or choose a different shipping method.', 'vp-woo-pont'), $fields) );
			}	
		}

		//Validate phone number
		if(isset($fields['billing_phone']) && !empty($fields['billing_phone'])) {
			$phone_number = $fields['billing_phone'];
			$country = isset($fields['billing_country']) ? $fields['billing_country'] : 'HU';
			$is_phone_valid = VP_Woo_Pont_Helpers::validate_phone_number($country, $phone_number);

			//Check if its a valid phone number
			if(!$is_phone_valid && apply_filters('vp_woo_pont_validate_phone_number', true, $vp_pont_chosen)) {
				$errors->add( 'validation', apply_filters('vp_woo_pont_wrong_phone_number', esc_html__( 'Please enter a valid phone number!', 'vp-woo-pont'), $fields) );
			}
		}

	}

	public function save_checkout( $order_id ) {

		//Skip if we don't need to ship
		$order = wc_get_order( $order_id );
		if(!$order->needs_shipping_address()) {
			return;
		}

		//If we have a pont selected, that means we need to save it
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$selected_pont = WC()->session->get( 'selected_vp_pont' );
		$chosen_method = $chosen_methods[0];

		//If shipping is vp_pont and a pont was selected, save its data as the shipping address(and some custom meta too)
		if(strpos($chosen_method, 'vp_pont') !== false && $selected_pont) {

			//Save custom meta and replace shipping address
			$this->update_order_with_selected_point($order, $selected_pont);

			// Get the customer id
			$customer_id = $order->get_customer_id();

			//Save user meta if the customer was signed in
			if( ! empty($customer_id) && $customer_id != 0) {
				update_user_meta( $customer_id, '_vp_woo_pont_point_id', $selected_pont['provider'].'|'.$selected_pont['id'] );
			}

		} else {

			//Check if we have provider paired with the shipping method, and if so, store it
			$provider = VP_Woo_Pont_Helpers::get_paired_provider($order, false);
			if($provider) {
				$order->update_meta_data('_vp_woo_pont_provider', $provider);
				$order->save();
			}

		}
	}

	public function update_order_with_selected_point($order, $point) {
		$address = array(
			'first_name' => $order->get_billing_first_name(),
			'last_name'  => $order->get_billing_last_name(),
			'company'    => $point['name'],
			'email'      => '',
			'phone'      => '',
			'address_1'  => $point['addr'],
			'address_2'  => '',
			'city'       => $point['city'],
			'state'      => '',
			'postcode'   => $point['zip'],
			'country'    => 'HU'
		);

		//If the point contains country info(at the moment packeta), update the address to include that too
		if(isset($point['country'])) {
			$address['country'] = strtoupper($point['country']);
		}

		//Allow plugins to customize
		$address = apply_filters('vp_woo_pont_update_order_shipping_address', $address, $order, $point);

		//Set shipping address to the same as the point's address
		if($address) {
			$order->set_address( $address, 'shipping' );
		}

		//Store provider and point ID as custom meta
		$order->update_meta_data( '_vp_woo_pont_provider', $point['provider'] );
		$order->update_meta_data( '_vp_woo_pont_point_id', $point['id'] );
		$order->update_meta_data( '_vp_woo_pont_point_name', $point['name'] );
		$order->update_meta_data( '_vp_woo_pont_point_coordinates', $point['lat'].';'.$point['lon'] );

		//And save the order
		$order->save();

		//Allow plugins to customize
		do_action('vp_woo_pont_update_order_with_selected_point', $order, $point);
	}
 
	public function format_shipping_address($address, $raw_address, $order) {
		$prepend = '';
		if($order->get_meta('_vp_woo_pont_point_id')) {
			$provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
			$provider_name = VP_Woo_Pont_Helpers::get_provider_name($provider_id, true);
			$address = $provider_name.'<br>'.$address;
		}
		return $address;
	}
 
	public function display_cost_in_emails( $shipping, $order, $tax_display) {
		if($order->get_meta('_vp_woo_pont_point_id') && $order->get_shipping_total() == 0) {
			$shipping = $shipping.' ('.esc_html_x( 'free', 'shipping cost summary on cart & checkout', 'vp-woo-pont' ).')';
		}
		return $shipping;
	}

	public function remove_pont_from_order() {

		//Security check
		check_ajax_referer( 'vp_woo_pont_manage', 'nonce' );
		if ( !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
		}

		//Get order id
		$order_id = intval($_POST['order']);
		$order = wc_get_order($order_id);

		//Remove from order
		$order->delete_meta_data( '_vp_woo_pont_provider' );
		$order->delete_meta_data( '_vp_woo_pont_point_id' );
		$order->delete_meta_data( '_vp_woo_pont_point_name' );
		$order->delete_meta_data( '_vp_woo_pont_point_coordinates' );
		$order->save();

		//Send success response
		wp_send_json_success();
	}

	public function replace_pont_in_order() {

		//Security check
		check_ajax_referer( 'vp_woo_pont_manage', 'nonce' );
		if ( !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
		}

		//Get order id
		$order_id = intval($_POST['order']);
		$order = wc_get_order($order_id);
		$provider = sanitize_text_field($_POST['provider']);
		$point_id = sanitize_text_field($_POST['point_id']);

		//Get point data
		$point = $this->find_point_info($provider, $point_id);

		//Save custom meta and replace shipping address
		$this->update_order_with_selected_point($order, $point);
		$carrier_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);

		//Create response
		$response = array();
		$providers = VP_Woo_Pont_Helpers::get_supported_providers();
		$response['point_id'] = $point['id'];
		$response['point_name'] = $point['name'];
		$response['provider_label'] = $providers[$point['provider']];
		$response['provider'] = $point['provider'];
		$response['carrier'] = $carrier_id;

		//Send success response
		wp_send_json_success($response);
	}

	public function save_provider_in_order() {

		//Security check
		check_ajax_referer( 'vp_woo_pont_manage', 'nonce' );
		if ( !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
		}

		//Get order id
		$order_id = intval($_POST['order']);
		$order = wc_get_order($order_id);
		$provider = sanitize_text_field($_POST['provider']);

		//Update provider
		$order->update_meta_data('_vp_woo_pont_provider', $provider);

		//And save the order
		$order->save();

		//Send success response
		wp_send_json_success();
	}

	public function display_provider_filter() {
		global $pagenow, $post_type;
		if( 'shop_order' === $post_type && 'edit.php' === $pagenow ) {
			return $this->render_provider_filter();
		}
	}

	public function display_provider_filter_hpos($order_type) {
		if ( 'shop_order' !== $order_type ) {
			return;
		}

		return $this->render_provider_filter();
	}

	public function render_provider_filter() {
		$providers_for_home_delivery = VP_Woo_Pont_Helpers::get_supported_providers_for_home_delivery();
		$enabled_providers = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers');
		$selected_filter = isset($_GET['vp_woo_pont_provider_filter']) ? sanitize_text_field($_GET['vp_woo_pont_provider_filter']) : '';
		?>
		<select name="vp_woo_pont_provider_filter">
			<option value=""><?php _e('Filter shipping providers', 'vp-woo-pont'); ?></option>
			<optgroup label="<?php esc_attr_e('Pickup points', 'vp-woo-pont'); ?>">
				<?php foreach ($enabled_providers as $provider_id): ?>
					<option value="point|<?php echo esc_attr($provider_id); ?>" <?php selected($provider_id, $selected_filter); ?>><?php echo esc_html(VP_Woo_Pont_Helpers::get_provider_name($provider_id, true)); ?></option>
				<?php endforeach; ?>
			</optgroup>

			<optgroup label="<?php esc_attr_e('Carriers', 'vp-woo-pont'); ?>">
				<?php foreach($providers_for_home_delivery as $provider_id => $label): ?>
					<?php if(VP_Woo_Pont_Helpers::is_provider_configured($provider_id)): ?>
						<option value="courier|<?php echo esc_attr($provider_id); ?>" <?php selected($provider_id, $selected_filter); ?>><?php echo esc_html($label); ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</optgroup>

		</select>
		<?php
	}

	public function process_provider_filter( $query ) {
		global $pagenow;
		if ( $query->is_admin && $pagenow == 'edit.php' && isset( $_GET['vp_woo_pont_provider_filter'] ) && $_GET['vp_woo_pont_provider_filter'] != '' && $_GET['post_type'] == 'shop_order' ) {
			$meta_query = $query->get( 'meta_query' );
			if(!$meta_query) $meta_query = array();

			$filter = esc_attr($_GET['vp_woo_pont_provider_filter']);
			$filter_type = explode('|', $filter)[0];
			$filter_string = explode('|', $filter)[1];
			$new_query = array(
				'meta_key' => '_vp_woo_pont_provider',
				'value' => $filter_string,
			);

			if($filter_type == 'courier') {
				$new_query['compare'] = 'LIKE';
			}
			
			$meta_query[] = $new_query;

			//Set meta-query
			$query->set( 'meta_query', $meta_query );
		}

		return $query;
	}


	public function process_provider_filter_hpos( $query ) {
		if (isset( $_GET['vp_woo_pont_provider_filter'] ) && $_GET['vp_woo_pont_provider_filter'] != '') {
			$query['meta_key'] = '_vp_woo_pont_provider';

			$filter = esc_attr($_GET['vp_woo_pont_provider_filter']);
			$filter_type = explode('|', $filter)[0];
			$filter_string = explode('|', $filter)[1];
			$query['meta_value'] = $filter_string;

			if($filter_type == 'courier') {
				$query['meta_compare'] = 'LIKE';
			}
		}
		return $query;
	}

	//Render custom html for the checkout page
	public function cart_ui() {

		//Get selected shipping methd
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		//If vp_pont is chosen
		$is_vp_pont_selected = false;
		if($chosen_methods) {
			foreach ($chosen_methods as $chosen_method) {
				if(strpos($chosen_method, 'vp_pont') !== false) {
					$is_vp_pont_selected = true;
				}
			}
		}

		//Get selected pont
		$selected_pont = WC()->session->get( 'selected_vp_pont' );

		//Get shipping cost
		$shipping_costs = VP_Woo_Pont_Helpers::calculate_shipping_costs();
		$shipping_cost = VP_Woo_Pont_Helpers::get_shipping_cost();
		$notes = VP_Woo_Pont_Helpers::get_map_notes();

		//Show only if option is enabled in customizer
		if(is_cart() && get_option('vp_woo_pont_show_on_cart', 'yes') == 'yes' && WC()->cart->needs_shipping()) {
			//Use wc_get_template, so it can be overwritten from a theme if needed
			wc_get_template('checkout/pont.php', array(
				'is_vp_pont_selected' => $is_vp_pont_selected,
				'selected_vp_pont' => $selected_pont,
				'shipping_costs' => $shipping_costs,
				'shipping_cost' => $shipping_cost,
				'notes' => $notes
			), false, VP_Woo_Pont::$plugin_path . '/templates/');
		}

	}

	public function export_settings() {

		//Security check
		check_ajax_referer( 'vp_woo_pont_settings_export', 'nonce' );
		if ( !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
		}

		//Get saved data and convert it to a json string
		$type = sanitize_text_field($_POST['type']);
		$saved_values = array();
		if($type == 'points') {
			$saved_values = get_option('vp_woo_pont_points');
		} else {
			$saved_values = get_option('vp_woo_pont_pricing');
		}

		//Return response
		wp_send_json_success($saved_values);

	}

	public function import_settings() {

		//Security check
		check_ajax_referer( 'vp_woo_pont_settings_export', 'nonce' );
		if ( !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
		}

		//Check type
		$type = sanitize_text_field($_POST['type']);

		//Check for file
		if (isset($_FILES['file'])){
			$file = $_FILES['file']['tmp_name'];
			$data = file_get_contents($file);
			$array = json_decode($data, true );

			if($type == 'pricing') {
				//Save pricing options
				$prices = array();
				if ( $array ) {
					foreach ($array as $pricing_id => $pricing) {

						$cost = wc_clean($pricing['cost']);
						$prices[$pricing_id] = array(
							'cost' => $cost,
							'conditional' => false,
							'providers' => array()
						);

						//If theres conditions to setup
						$condition_enabled = isset($pricing['conditional']) ? true : false;
						$conditions = (isset($pricing['conditions']) && count($pricing['conditions']) > 0);
						if($condition_enabled && $conditions) {
							$prices[$pricing_id]['conditional'] = true;
							$prices[$pricing_id]['conditions'] = array();
							$prices[$pricing_id]['logic'] = wc_clean($pricing['logic']);

							foreach ($pricing['conditions'] as $condition) {
								$condition_details = array(
									'category' => wc_clean($condition['category']),
									'comparison' => wc_clean($condition['comparison']),
									'value' => wc_clean($condition['value'])
								);

								$prices[$pricing_id]['conditions'][] = $condition_details;
							}
						}

						//Save providers
						$providers = (isset($pricing['providers']) && count($pricing['providers']) > 0);
						if($providers) {
							foreach ($pricing['providers'] as $provider) {
								$prices[$pricing_id]['providers'][] = $provider;
							}
						}

					}
				}
				update_option( 'vp_woo_pont_pricing', $prices );
			}

			if($type == 'points') {
				//Save pricing options
				$points = array();
				if ( $array ) {
					foreach ($array as $point_id => $point) {

						$name = wc_clean($point['name']);
						$id = wc_clean($point['id']);
						$provider = wc_clean($point['provider']);
						$lat = wc_clean($point['lat']);
						$lon = wc_clean($point['lon']);
						$zip = wc_clean($point['zip']);
						$addr = wc_clean($point['addr']);
						$city = wc_clean($point['city']);
						$comment = wp_kses_post( trim( wp_unslash($point['comment']) ) );
						$hidden = isset($point['hidden']) ? true : false;
						$email = '';
						if(isset($point['email'])) {
							$email = wc_clean($point['email']);
						}
						$openhours = array();
						if(isset($point['hours'])) {
							$openhours = $point['hours'];
						}

						//Create new point
						$points[$point_id] = array(
							'name' => $name,
							'id' => $id,
							'provider' => $provider,
							'lat' => $lat,
							'lon' => $lon,
							'zip' => $zip,
							'addr' => $addr,
							'city' => $city,
							'comment' => $comment,
							'hidden' => $hidden,
							'email' => $email,
							'hours' => $openhours
						);

					}
				}
				update_option( 'vp_woo_pont_points', $points );
			}
		}

		//Return response
		wp_send_json_success();

	}

	function shipping_method_selected( $post_data ) {

		//Check if the user is signed in
		if(is_user_logged_in()) {
			$customer_id = get_current_user_id();
			$point_info = get_user_meta( $customer_id, '_vp_woo_pont_point_id', true );
			$selected_pont = WC()->session->get( 'selected_vp_pont' );

			//If a point is stored
			if($point_info && !$selected_pont) {
				$point_info = explode('|', $point_info);
				$provider = $point_info[0];
				$point_id = $point_info[1];

				//Skip if it was an old saved data
				if(!in_array($provider, array('gls', 'packeta', 'postapont_10', 'postapont_20', 'postapont_30', 'postapont_50', 'postapont_70'))) {
			
					//Get point data
					$point = $this->find_point_info($provider, $point_id);

					//Check if we have a point found
					if($point) {

						//Store it in the checkout session.
						WC()->session->set('selected_vp_pont', $point);

					}

				}

			}

		}

		//Get selected shipping methd
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		//If vp_pont is chosen
		$is_vp_pont_selected = false;
		if($chosen_methods) {
			foreach ($chosen_methods as $chosen_method) {
				if(strpos($chosen_method, 'vp_pont') !== false) {
					$is_vp_pont_selected = true;
				}
			}
		}

		//Check if we are changeing to VP shipping
		if(!$is_vp_pont_selected && $post_data && strpos($post_data, 'vp_pont') !== false) {
			$is_vp_pont_selected = true;
		}

		//If Vp pont is selected, invalidate cache
		if($is_vp_pont_selected) {
			$packages = WC()->cart->get_shipping_packages();
			foreach ($packages as $key => $value) {
				$shipping_session = "shipping_for_package_$key";
				unset(WC()->session->$shipping_session);
			}
		}

	}

	//Log error message
	public function log_error_messages($error, $source) {
		$logger = wc_get_logger();
		$logger->error(
			$source.' - '.json_encode($error),
			array( 'source' => 'vp_woo_pont' )
		);
	}

	//Log debug messages
	public function log_debug_messages($data, $source, $force = false) {
		if(VP_Woo_Pont_Helpers::get_option('debug', 'no') == 'yes' || $force) {
			$logger = wc_get_logger();
			$logger->debug(
				$source.' - '.json_encode($data),
				array( 'source' => 'vp_woo_pont' )
			);
		}
	}

	public function add_label_in_preview_modal( $fields, $order ) {
		$data = false;
		if($provider_id = $order->get_meta('_vp_woo_pont_provider')) {
			$providers = VP_Woo_Pont_Helpers::get_supported_providers();
			$provider_name = '';
			if(isset($providers[$provider_id])) {
				$provider_name = $providers[$provider_id];
			}

			if(!$order->get_meta('_vp_woo_pont_point_id')) {
				$providers = VP_Woo_Pont_Helpers::get_supported_providers_for_home_delivery();
				$provider_name = $providers[$provider_id];
				if($provider_id == 'posta') {
					$provider_name = __('MPL', 'vo-woo-pont');
				}
			}

			if($this->labels->is_label_generated($order)) {
				$data['name'] = $provider_name;
				$data['tracking_link'] = $this->tracking->get_tracking_link($order);
				$data['tracking_number'] = $order->get_meta('_vp_woo_pont_parcel_number');
				$data['label_link'] = $this->labels->generate_download_link($order);
				$data['label_id'] = $order->get_meta('_vp_woo_pont_parcel_id');
			}

		}

		if($data) {
			$fields['vp_woo_pont'] = $data;
		}

		return $fields;
	}

	public function show_label_in_preview_modal() {
		?>
		<# if ( data.vp_woo_pont ) { #>
		<div class="wc-order-preview-addresses">
			<div class="wc-order-preview-address">
				<h2><?php esc_html_e( 'Package details', 'vp-woo-pont' ); ?></h2>
				<strong><?php esc_attr_e('Tracking number', 'vp-woo-pont'); ?></strong>
				<a href="{{ data.vp_woo_pont.tracking_link }}" target="_blank">{{ data.vp_woo_pont.tracking_number }}</a>
				<strong><?php esc_attr_e('Shipping label', 'vp-woo-pont'); ?></strong>
				<a href="{{ data.vp_woo_pont.label_link }}" target="_blank">{{ data.vp_woo_pont.label_id }}</a>
			</div>
		</div>
		<# } #>
		<?php
	}

	public function hide_shipping_method_if_no_points($rates) {
		foreach ( $rates as $rate_id => $rate ) {
			if ( 'vp_pont' === $rate->get_method_id() ) {

				//Get shipping costs
				$shipping_cost = VP_Woo_Pont_Helpers::calculate_shipping_costs();

				//And if empty, remove option
				if(empty($shipping_cost)) {
					unset($rates[$rate_id]);
				}

			}
		}
		return $rates;
	}

	public function pickup_point_selector_my_account() {
		$customer_id = get_current_user_id();
		$point_info = get_user_meta( $customer_id, '_vp_woo_pont_point_id', true );
		$selected_point = false;

		//If a point is stored
		if($point_info) {
			$point_info = explode('|', $point_info);
			$provider = $point_info[0];
			$point_id = $point_info[1];

			//Get point data
			$point = $this->find_point_info($provider, $point_id);

			//Check if we have a point found
			if($point) {
				$selected_point = $point;
			}
		}

		//Get shippign cost
		$shipping_costs = VP_Woo_Pont_Helpers::calculate_shipping_costs();
		$shipping_costs_json = wp_json_encode( $shipping_costs );
		$shipping_costs_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $shipping_costs_json ) : _wp_specialchars( $shipping_costs_json, ENT_QUOTES, 'UTF-8', true );

		?>
		<div class="form-row woocommerce-address-fields__field-wrapper">
			<label><?php echo esc_html_x('Pickup point', 'frontend', 'vp-woo-pont'); ?></label>
			<?php if($selected_point): ?>
				<div class="vp-woo-pont-review-order-selected vp-woo-pont-my-account-selected">
					<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($selected_point['provider']); ?>"></i>
					<div class="vp-woo-pont-review-order-selected-info">
						<strong><?php echo esc_html($selected_point['name']); ?></strong><br>
					</div>
				</div>
			<?php else: ?>
				<div class="vp-woo-pont-review-order-selected vp-woo-pont-my-account-selected" style="display:none">
					<i></i>
					<div class="vp-woo-pont-review-order-selected-info">
						<strong></strong><br>
					</div>
				</div>
			<?php endif; ?>
			<a href="#" id="vp-woo-pont-show-map" class="vp-woo-pont-show-map-my-account" data-shipping-costs="<?php echo $shipping_costs_attr; ?>">Csomagpont kiválasztása</a>
		</div>
		<?php
	}

	public function free_shipping_when_free_shipping_exists($rates, $package) {
		if(VP_Woo_Pont_Helpers::get_option('free_shipping_overwrite', 'no') == 'yes' && $rates) {
			$has_free_shipping = false;
			$vp_woo_pont_id = false;
			foreach ( $rates as $rate_id => $rate ) {
				if((float)$rate->get_cost() == 0 && $rate->get_method_id() != 'vp_pont' && $rate->get_method_id() != 'local_pickup') {
					$has_free_shipping = true;
				}
				if($rate->get_method_id() == 'vp_pont') {
					$vp_woo_pont_id = $rate_id;
				}
			}

			if($has_free_shipping && $vp_woo_pont_id) {
				$rates[$vp_woo_pont_id]->cost = 0;
				$rates[$vp_woo_pont_id]->taxes = array();
			}
		}
		return $rates;
	}

	//SHow shipping address section always, even though its local pickup
	public function show_shipping_address($methods){
		$methods = array_diff($methods, array('vp_pont'));
		return $methods;
	}

}

//WC Detection
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ;
	}
}

//Initialize, if woocommerce is active
if ( is_woocommerce_active() ) {
	function VP_Woo_Pont() {
		return VP_Woo_Pont::instance();
	}

	VP_Woo_Pont();
}