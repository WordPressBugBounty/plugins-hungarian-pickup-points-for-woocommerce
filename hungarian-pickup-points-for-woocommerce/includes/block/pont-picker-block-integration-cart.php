<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\StoreApi\Exceptions\InvalidCartException;

/**
 * Class for integrating with WooCommerce Blocks
 */
    class VP_Woo_Pont_Block_Integration_Cart implements IntegrationInterface {

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
	}

	//Returns an array of script handles to enqueue in the frontend context.
	public function get_script_handles() {
		return [ 'vp-woo-pont-picker-block-frontend-test' ];
	}

	//Returns an array of script handles to enqueue in the editor context.
	public function get_editor_script_handles() {
		return [ ];
	}

	//An array of key, value pairs of data made available to the block on the client side, easy to translate
	public function get_script_data() {
		return [];
	}

	//Load frontend assets
	public function register_block_frontend_scripts() {
		$script_path       = '/build/pont-picker-cart-slot-fill.js';
		$script_url        = VP_Woo_Pont()::$plugin_url.$script_path;
		$script_asset_path = VP_Woo_Pont()::$plugin_path . '/build/pont-picker-cart-slot-fill.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];


		wp_register_script(
			'vp-woo-pont-picker-block-frontend-test',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
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