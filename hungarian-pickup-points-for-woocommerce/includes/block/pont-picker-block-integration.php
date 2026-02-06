<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\StoreApi\Exceptions\InvalidCartException;

/**
 * Class for integrating with WooCommerce Blocks
 */
    class VP_Woo_Pont_Block_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vp-woo-pont-picker';
	}

	//When called invokes any initialization/setup for the integration.
	public function initialize() {
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
		$this->register_block_editor_styles();
		$this->register_main_integration();
		$this->add_attributes_to_frontend_blocks();
	}

	//Customizable text and labels
	public function add_attributes_to_frontend_blocks() {
		add_filter( '__experimental_woocommerce_blocks_add_data_attributes_to_block', function($allowed_blocks){
			if (!is_array($allowed_blocks)) {
				$allowed_blocks = (array) $allowed_blocks;
			}
			$allowed_blocks[] = 'vp-woo-pont/pont-picker-block';
			return $allowed_blocks;	
		});
	}

	//Registers the main JS file required to add filters and Slot/Fills.
	private function register_main_integration() {
		$script_path = 'build/index.js';
		$style_path  = 'build/style-index.css';

		$script_url = VP_Woo_Pont()::$plugin_url.$script_path;
		$style_url = VP_Woo_Pont()::$plugin_url.$style_path;
		$script_asset_path = VP_Woo_Pont()::$plugin_path . '/build/index.asset.php';

		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_path ),
			];

		/*
		wp_enqueue_style(
			'vp-woo-pont-picker-block-integration',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);
		*/

		wp_register_script(
			'vp-woo-pont-picker-block-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'vp-woo-pont-picker-block-integration',
			'vp-woo-pont',
			VP_Woo_Pont()::$plugin_path . '/languages'
		);

	}

	//Returns an array of script handles to enqueue in the frontend context.
	public function get_script_handles() {
		return [ 'vp-woo-pont-picker-block-integration', 'vp-woo-pont-picker-block-frontend' ];
	}

	//Returns an array of script handles to enqueue in the editor context.
	public function get_editor_script_handles() {
		return [ 'vp-woo-pont-picker-block-integration', 'vp-woo-pont-picker-block-editor' ];
	}

	//An array of key, value pairs of data made available to the block on the client side, easy to translate
	public function get_script_data() {
		$styles = VP_Woo_Pont_Helpers::get_option('vp_woo_pont_styles', array());
		$data = [
			'defaultText' => __('Select a pickup point', 'vp-woo-pont'),
			'enabledProviders' => VP_Woo_Pont_Helpers::get_option('vp_woo_pont_enabled_providers', array('foxpost', 'gls', 'dpd')),
			'codFeesEnabled' => (get_option('vp_woo_pont_cod_fees')),
			'kvikkMapApiKey' => VP_Woo_Pont_Helpers::get_option('kvikk_map_api_key', ''),
			'primaryColor' => isset($styles['primary_color']) ? $styles['primary_color'] : '#2471B1',
			'textColor' => isset($styles['text_color']) ? $styles['text_color'] : '#838383'
		];

		return $data;
	}

	//Load editor styles
	public function register_block_editor_styles() {
		$style_path = 'build/style-pont-picker-block.css';
		$style_url = VP_Woo_Pont()::$plugin_url.$style_path;

		wp_enqueue_style(
			'vp-woo-pont-picker-block',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);

		if(is_admin()) {
			wp_enqueue_style( 'vp_woo_pont_admin_css', VP_Woo_Pont()::$plugin_url. 'assets/css/admin.css', array(), VP_Woo_Pont::$version );
		}
	}

	//Load editor assets
	public function register_block_editor_scripts() {
		$script_path       = 'build/pont-picker-block.js';
		$script_url        = VP_Woo_Pont()::$plugin_url.$script_path;

		$script_asset_path = VP_Woo_Pont()::$plugin_path . '/build/pont-picker-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'vp-woo-pont-picker-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'vp-woo-pont-picker-block-editor',
			'vp-woo-pont',
			VP_Woo_Pont()::$plugin_path . '/languages'
		);

	}

	//Load frontend assets
	public function register_block_frontend_scripts() {
		$script_path       = 'build/pont-picker-block-frontend.js';
		$script_url        = VP_Woo_Pont()::$plugin_url.$script_path;
		$script_asset_path = VP_Woo_Pont()::$plugin_path . '/build/pont-picker-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'vp-woo-pont-picker-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		$test = wp_set_script_translations(
			'vp-woo-pont-picker-block-frontend',
			'vp-woo-pont',
			VP_Woo_Pont()::$plugin_path . '/languages'
		);

	}

	//Get the file modified time as a cache buster if we're in dev mode
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return VP_Woo_Pont()::$version;
	}

}