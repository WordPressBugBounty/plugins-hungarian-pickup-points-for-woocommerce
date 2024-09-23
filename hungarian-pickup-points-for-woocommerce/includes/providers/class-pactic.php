<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VP_Woo_Pont_Pactic {
	protected $api_url = 'https://www.zasilkovna.cz/api/rest/';
	protected $api_key = '';
	protected $api_password = '';
	protected $sender_id = '';
	public $package_statuses = array();
	public $pactic_supported_providers = array();

	public function __construct() {
        $this->pactic_supported_providers = array(
			'sk_gls' => array(
				'name' => __('Slovakia GLS', 'vp-woo-pont'),
				'carrier_id' => 2
			),
			'hr_gls' => array(
				'name' => __('Croatia GLS', 'vp-woo-pont'),
				'carrier_id' => 2
			),
			'si_gls' => array(
				'name' => __('Slovenia GLS', 'vp-woo-pont'),
				'carrier_id' => 2
			),
			'ro_sameday' => array(
				'name' => __('Romania Sameday', 'vp-woo-pont'),
				'carrier_id' => 24
			),
			'sk_post' => array(
				'name' => __('Slovakia Post', 'vp-woo-pont'),
				'carrier_id' => 30
			),
			'cz_ppl' => array(
				'name' => __('Czech PPL', 'vp-woo-pont'),
				'carrier_id' => 35
			),
			'pl_inpost' => array(
				'name' => __('Poland Inpost', 'vp-woo-pont'),
				'carrier_id' => 20
			),
			'bg_econt' => array(
				'name' => __('Bulgaria Econt', 'vp-woo-pont'),
				'carrier_id' => 17
			),
        );

		add_filter('vp_woo_pont_carrier_settings_pactic', array($this, 'get_settings'));
        add_action('vp_woo_pont_update_pactic_list', array( $this, 'get_pactic_json' ));
        add_filter('vp_woo_pont_external_provider_groups', array($this, 'add_provider_group'));
        add_filter('vp_woo_pont_get_supported_providers', array($this, 'add_providers'));
        add_filter('vp_woo_pont_provider_subgroups', array($this, 'add_provider_subgroups'));
        add_filter('vp_woo_pont_import_pactic_manually', array( $this, 'get_pactic_json_manually'));
		add_action('vp_woo_pont_update_order_with_selected_point', array($this, 'save_pactic_info'), 10, 2);
		add_filter('vp_woo_pont_update_order_shipping_address', array($this, 'hide_company_name'), 10, 3);
	}

	public function get_provider_names() {
		$providers = $this->pactic_supported_providers;
		$options = array();
		foreach($providers as $provider_id => $provider) {
			$options[$provider_id] = $provider['name'];
		}
		return $options;
	}

	public function get_settings($settings) {
		$pactic_settings = array(
			array(
				'title' => __( 'Pactic settings', 'vp-woo-pont' ),
				'type' => 'vp_carrier_title',
			),
            array(
                'type' => 'vp_checkboxes',
                'title' => __( 'Enabled providers', 'vp-woo-pont' ),
				'options' => $this->get_provider_names(),
                'default' => array(),
				'id' => 'pactic_external_providers'
            )
		);

		//Append provider specific settings
		$enabled_providers = get_option('vp_woo_pont_pactic_external_providers', array());
		$provider_names = $this->get_provider_names();
		foreach($enabled_providers as $provider_id) {
			$pactic_settings[] = array(
				'title' => sprintf( __( '%s Service ID', 'vp-woo-pont' ), $provider_names[$provider_id] ),
				'type' => 'text',
				'id' => 'pactic_'.$provider_id.'_service_id'
			);
		}

		$pactic_settings[] = array(
			'type' => 'sectionend'
		);

		return $settings+$pactic_settings;
	}

    public function get_pactic_json() {

		//Get enabled providers
		$enabled_providers = get_option('vp_woo_pont_pactic_external_providers', array());

		//Collect points
		$results = array();

		//Get data for each provider
		foreach($enabled_providers as $provider_id) {

			//Get country code and name
			$provider_details = explode('_', $provider_id);
			$country_code = $provider_details[0];

			//Make request based on country code
			$url = 'https://api.pactic.com/webservices/shipment/parcelpoints_v2/downloadparcelpoints.ashx?cdCountry='.$country_code;
			$request = wp_remote_post($url, array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept' => 'application/json',
					'ApiKey' => 'jvbjzc7z-9d8g-uwrg-p3uj-f74v2nercahe',
					'Accept-Encoding' => 'gzip, deflate, br'
				),
				'timeout' => 60
			));
		
			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'gls-import-points');
				return false;
			}

			//Get body
			$body = wp_remote_retrieve_body( $request );

			//Try to convert into json
			$json = json_decode( $body, true );

			//Check if json exists
			if($json === null) {
				return false;
			}

			//Simplify json, so its smaller to store, faster to load
			foreach ($json as $carrier) {

				//Check for carrier ID
				if($carrier['CarrierId'] != $this->pactic_supported_providers[$provider_id]['carrier_id']) {
					continue;
				}

				//Get each point
				foreach($carrier['ParcelPoints'] as $place) {

					//Check for missing coordinates
					if(!$place['Location']['Coordinates']['Lat']) {
						continue;
					}

					$result = array(
						'id' => $place['Code'],
						'lat' => number_format(str_replace(',', '.', $place['Location']['Coordinates']['Lat']), 5, '.', ''),
						'lon' => number_format(str_replace(',', '.', $place['Location']['Coordinates']['Long']), 5, '.', ''),
						'zip' => $place['Location']['PostCode'],
						'addr' => $place['Location']['Address'],
						'city' => $place['Location']['City'],
						'country' =>  strtoupper($place['Location']['CountryCode']),
						'name' => $place['Name']
					);

					if(!isset($results[$provider_id])) {
						$results[$provider_id] = array();
					}
	
					$results[$provider_id][] = $result;	
				}
			}

		}

		//Save stuff
		$saved_files = array();
		foreach ($results as $type => $points) {
			$saved_files['pactic_'.$type] = VP_Woo_Pont_Import_Database::save_json_file('pactic_'.$type, $points);
		}

        return $saved_files;
    }

    public function get_pactic_json_manually() {
        return array( $this, 'get_pactic_json' );
    }

    public function add_provider_group($groups) {
        $groups['pactic'] = __('Pactic', 'vp-woo-pont');
        return $groups;
    }

    public function add_providers($providers) {
        foreach(get_option('vp_woo_pont_pactic_external_providers', array()) as $provider_id) {
            $providers['pactic_'.$provider_id] = $this->pactic_supported_providers[$provider_id]['name'];
        }
        return $providers;
    }

    public function add_provider_subgroups($subgroups) {
        $subgroups['pactic'] = get_option('vp_woo_pont_pactic_external_providers', array());
        return $subgroups;
    }

    public function get_tracking_link($parcel_number, $order = false) {
		return false;
	}

	//Save pactic related informations
	public function save_pactic_info($order, $point) {
		if (strpos($point['provider'], 'pactic') === 0) {
			$trimmed_provider = preg_replace('/^pactic_/', '', $point['provider']);
			$providers = $this->pactic_supported_providers;
			$carrier_id = $providers[$trimmed_provider]['carrier_id'];
			$order->update_meta_data('_pactic_carrier_id', $carrier_id);
			$order->update_meta_data('_pactic_service_id', VP_Woo_Pont_Helpers::get_option('pactic_'.$trimmed_provider.'_service_id', ''));
			$order->save();
		}
	}

	//For Pactic, we don't need the company name, because it causes some issues on their end
	public function hide_company_name($address, $order, $point) {
		if(strpos($point['provider'], 'pactic') !== false) {
			$address['company'] = '';
		}
		return $address;
	}

}
