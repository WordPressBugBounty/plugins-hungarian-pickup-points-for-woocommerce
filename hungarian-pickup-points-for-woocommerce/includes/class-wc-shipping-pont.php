<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Shipping_Pont extends WC_Shipping_Method {

	//Create instance
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'vp_pont';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = _x( 'Pickup points', 'method title', 'vp-woo-pont' );
		$this->method_description = __( 'Setup parcel lockers and pickup points with various providers and prices. ', 'vp-woo-pont' );
		$this->supports           = array(
			'shipping-zones',
			'settings',
			'local-pickup',
			'instance-settings-modal'
		);
		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'save_custom_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	}

	//Initialize settings.
	private function init() {
		$this->title                    = $this->get_option( 'title', $this->method_title );
		$this->tax_status               = $this->get_option( 'tax_status' );
		$this->form_fields              = include __DIR__ . '/settings/settings-global.php';
		$this->instance_form_fields    = include __DIR__ . '/settings/settings-instance.php';
	}

	//Calculate cost
	public function calculate_shipping( $package = array() ) {
		$rate = array(
			'id'      => $this->get_rate_id(),
			'label'   => $this->title,
			'cost'    => 0,
			'package' => $package,
		);

		//Find out shipping cost
		$shipping_cost = VP_Woo_Pont_Helpers::get_shipping_cost();

		//If a cost is found, set it
		if($shipping_cost && isset($shipping_cost['net'])) {
			$rate['cost'] = $shipping_cost['net'];
		}

		//Set rate
		$this->add_rate( $rate );
	}

	//Generate html for pro version details
	public function admin_options() {
		if ( ! $this->instance_id ) {
			echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		}
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		include( dirname( __FILE__ ) . '/views/html-admin-pro-version.php' );
		echo $this->get_admin_options_html(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	}

	//Generate html for custom settings fields
	public function generate_vp_woo_pont_settings_instance_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for custom settings fields
	public function generate_vp_woo_pont_settings_pricing_table_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for custom settings fields
	public function generate_vp_woo_pont_settings_notes_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for custom settings fields
	public function generate_vp_woo_pont_settings_enabled_providers_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for custom settings fields
	public function render_custom_setting_html($key, $data) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'desc' => '',
			'custom_attributes' => array(),
			'options' => array()
		);
		$data = wp_parse_args( $data, $defaults );
		$template_name = str_replace('vp_woo_pont_settings_', '', $data['type']);
		ob_start();
		include( dirname( __FILE__ ) . '/views/html-admin-'.str_replace('_', '-', $template_name).'.php' );
		return ob_get_clean();
	}

	//To save extra fields
	public function save_custom_options() {

		//Save enabled providers related data
		$checkbox_groups = array('enabled_providers', 'free_shipping', 'cod_disabled');
		foreach ($checkbox_groups as $checkbox_group) {
			$checkbox_values = array();
			if ( isset( $_POST['vp_woo_pont_'.$checkbox_group] ) ) {
				foreach ($_POST['vp_woo_pont_'.$checkbox_group] as $checkbox_value) {
					$checkbox_values[] = wc_clean($checkbox_value);
				}
			}
			update_option('vp_woo_pont_'.$checkbox_group, $checkbox_values);
		}

		//Save custom pricing options
		$prices = array();
		if ( isset( $_POST['vp_woo_pont_pricing'] ) ) {
			foreach ($_POST['vp_woo_pont_pricing'] as $pricing_id => $pricing) {

				$cost = wc_clean($pricing['cost']);
				$cost = str_replace(',','.',$cost);
				$prices[$pricing_id] = array(
					'cost' => (float)$cost,
					'conditional' => false,
					'providers' => array(),
					'countries' => array()
				);

				//If theres conditions to setup
				$condition_enabled = isset($pricing['condition_enabled']) ? true : false;
				$conditions = (isset($pricing['conditions']) && count($pricing['conditions']) > 0);
				if($condition_enabled && $conditions) {
					$prices[$pricing_id]['conditional'] = true;
					$prices[$pricing_id]['conditions'] = array();
					$prices[$pricing_id]['logic'] = wc_clean($pricing['logic']);

					foreach ($pricing['conditions'] as $condition) {
						$condition_details = array(
							'category' => wc_clean($condition['category']),
							'comparison' => wc_clean($condition['comparison']),
							'value' => $condition[$condition['category']]
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

				//Save countries
				$countries = (isset($pricing['countries']) && count($pricing['countries']) > 0);
				if($countries) {
					foreach ($pricing['countries'] as $country) {
						$prices[$pricing_id]['countries'][] = $country;
					}
				}

			}
		}

		update_option( 'vp_woo_pont_pricing', $prices );

		//Save custom pricing options
		$notes = array();
		if ( isset( $_POST['vp_woo_pont_note'] ) ) {
			foreach ($_POST['vp_woo_pont_note'] as $note_id => $note) {

				$comment = wp_kses_post( trim( wp_unslash($note['note']) ) );
				$provider = wc_clean($note['provider']);
				$notes[$note_id] = array(
					'comment' => $comment,
					'provider' => $provider,
					'conditional' => false
				);

				//If theres conditions to setup
				$condition_enabled = isset($note['condition_enabled']) ? true : false;
				$conditions = (isset($note['conditions']) && count($note['conditions']) > 0);

				if($condition_enabled && $conditions) {
					$notes[$note_id]['conditional'] = true;
					$notes[$note_id]['conditions'] = array();
					$notes[$note_id]['logic'] = wc_clean($note['logic']);

					foreach ($note['conditions'] as $condition) {
						if(isset($condition['category'])) {
							$condition_details = array(
								'category' => wc_clean($condition['category']),
								'comparison' => wc_clean($condition['comparison']),
								'value' => $condition[$condition['category']]
							);

							$notes[$note_id]['conditions'][] = $condition_details;
						}
					}
				}
			}
		}
		update_option( 'vp_woo_pont_notes', $notes );
	}

	public function get_available_providers($test = '') {
		$providers = VP_Woo_Pont_Helpers::get_supported_providers();
		$carrier_labels = VP_Woo_Pont_Helpers::get_external_provider_groups();
		foreach($providers as $provider_id => $label) {
			$carrier = explode('_', $provider_id)[0];
			if(in_array($carrier, array_keys($carrier_labels)) && strpos($provider_id, '_') !== false) {
				$label = $carrier_labels[$carrier].' - '.$label;
				$providers[$provider_id] = $label;
			}
		}
		return $providers;
	}

	//Check if we need to show a notice on the settings screen
	public function is_local_pickup_feature_needed() {
		$checkout_page = wc_get_page_id('checkout');
		if (!$checkout_page || !has_block('woocommerce/checkout', $checkout_page)) {
			return false;
		}

		$pickup_location_settings = get_option('woocommerce_pickup_location_settings', []);
		return !wc_string_to_bool($pickup_location_settings['enabled'] ?? 'no');
	}

}
