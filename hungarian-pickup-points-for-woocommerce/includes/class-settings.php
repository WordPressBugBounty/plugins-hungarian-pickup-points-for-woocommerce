<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Settings', false ) ) :

	class VP_Woo_Pont_Settings {

        //Define sections as an array
        private static $sections = array();
        
		public static function init() {

            //Define sections as an array
            self::$sections = array(
                'vp_carriers' => __( 'Carriers', 'vp-woo-pont' ),
                'vp_labels' => __( 'Labels', 'vp-woo-pont' ),
                'vp_tracking' => __( 'Tracking', 'vp-woo-pont' ),
            );

            //Create custom field types
            add_action( 'woocommerce_admin_field_vp_carrier_title', array( __CLASS__, 'generate_carrier_title_html') );
            add_action( 'woocommerce_admin_field_vp_carriers', array( __CLASS__, 'generate_carriers_html') );

            // Define the actions that use the same method
            $field_types = array(
                'packeta_countries',
                'packeta_carriers',
                'checkboxes',
                'posta_countries',
                'home_delivery',
                'automations',
                'tracking_automations',
                'cod_fees',
                'points_table',
                'points_title',
                'pro_notice',
                'custom_label_template',
                'kvikk_api',
                'weight_corrections'
            );

            //Loop over the actions and add them
            foreach ($field_types as $field_type) {
                add_action('woocommerce_admin_field_vp_' . $field_type, array(__CLASS__, 'display_custom_field_html'));
            }
            
            //Create a settings screen under WooCommerce / Settings
            add_filter('woocommerce_get_sections_shipping', array( __CLASS__, 'add_settings') );

            //Render settings fields
            add_filter( 'woocommerce_get_settings_shipping', array( __CLASS__, 'display_settings'), 10, 2 );
            add_filter( 'woocommerce_get_settings_shipping', array( __CLASS__, 'display_points_manager_settings'), 10, 2 );

            //Ajax functions
            add_action( 'wp_ajax_vp_woo_pont_toggle_carrier_enabled', array( __CLASS__, 'toggle_carrier_enabled' ) );

            //Add COD settings
            add_filter( 'woocommerce_get_settings_checkout', array( __CLASS__, 'display_cod_settings'), 10, 2 );

            //Save setuff
            add_action( 'woocommerce_settings_save_shipping', array( __CLASS__, 'pre_save_settings') );
            add_action( 'woocommerce_update_options_shipping', array( __CLASS__, 'save_settings') );
            add_action( 'woocommerce_update_options_checkout', array( __CLASS__, 'save_cod_settings') );
 
            //Bugfix for WC 8.4+
            add_action( 'woocommerce_sections_shipping', function(){
                if(version_compare( WC()->version, '8.4.0', "=" ) && isset($_GET['section']) && $_GET['section'] == 'vp_pont') {
                    echo '<table class="form-table">';
                }
            }, 9 );

            //Bugfix for WC 8.4+
            add_action( 'woocommerce_settings_shipping', function(){
                if(version_compare( WC()->version, '8.4.0', "=" ) && isset($_GET['section']) && $_GET['section'] == 'vp_pont') {
                    echo '</table>';
                }
            }, 20);
            
		}

        //Append sections under the shipping tab
        public static function add_settings($tab) {
            foreach ( self::$sections as $section_id => $section_label ) {
                $tab[$section_id] = __($section_label, 'vp-woo-pont');
            }
            return $tab;
        }

        //Check if the current section is what we want
        public static function display_settings($settings, $current_section) {
            if(in_array($current_section, array_keys(self::$sections))) {
                //Remove the vp_ prefix from the section name
                $section_name = str_replace('vp_', '', $current_section);
                $settings = self::get_settings($section_name);
            }
            return $settings;
        }

        //Check if the current section is what we want
        public static function display_points_manager_settings($settings, $current_section) {
            if($current_section == 'points') {
                $settings = self::get_settings('points');
            }
            return $settings;
        }

        //Check if the current section is what we want
        public static function display_cod_settings($settings, $current_section) {
            if($current_section == 'cod') {
                $additional_settings = self::get_settings('cod');
                $settings = array_merge($settings, $additional_settings);
            }
            return $settings;
        }

        //Get settings array for the current section
        public static function get_settings($section_name) {
            $settings = include __DIR__ . '/settings/settings-'.$section_name.'.php';

            //If a single carrier is selected, show only the settings for that carrier
            if($section_name == 'carriers' && isset($_GET['carrier'])) {
                $settings = apply_filters('vp_woo_pont_carrier_settings_'.sanitize_text_field($_GET['carrier']), array());
            }

            //Prefix the settings fields
            foreach($settings as $key => $setting) {
                if(isset($setting['id'])) {
                    $settings[$key]['id'] = 'vp_woo_pont_'.$setting['id'];
                }
            }

            return $settings;
        }

        public static function get_available_providers($test = '') {
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

        public static function get_bulk_zip_error($default = '') {
            $message = $default;
            if(	!class_exists('ZipArchive')) {
                $message = '<span class="vp-woo-pont-settings-error"><span class="dashicons dashicons-warning"></span> '.__('This feature requires the ZipArchive function and by the looks of it, this is not enabled on your website. You can ask your hosting provider for help.', 'vp-woo-pont').'</span>';
            }
            return $message;
        }
    
        //Get order statues
        public static function get_order_statuses($empty = false) {
            $statuses = array();
            if(function_exists('wc_order_status_manager_get_order_status_posts')) {
                $filtered_statuses = array();
                $custom_statuses = wc_order_status_manager_get_order_status_posts();
                foreach ($custom_statuses as $status ) {
                    $filtered_statuses[ 'wc-' . $status->post_name ] = $status->post_title;
                }
                $statuses = $filtered_statuses;
            } else {
                $statuses = wc_get_order_statuses();
            }
    
            if($empty) {
                $statuses = array('' => $empty) + $statuses;
            }
    
            return $statuses;
        }

        public static function get_pages() {
            $pages = get_pages();
            $options = array();
            $options[''] = __("Don't use custom page", 'vp-woo-pont');
            foreach ( $pages as $page ) {
                $options[$page->ID] = $page->post_title;
            }
            return $options;
        }

        public static function generate_carrier_title_html($value) {           
            ?>
            <h2 class="vp-woo-pont-settings vp-woo-pont-settings-title-carrier">
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_carriers' ); ?>"><?php _e( 'Carriers', 'vp-woo-pont' ); ?></a> &gt;
                <?php echo esc_html( $value['title'] ); ?>
            </h2>
            <?php

            if ( ! empty( $value['desc'] ) ) {
                echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
                echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
                echo '</div>';
            }
            echo '<table class="form-table">' . "\n\n";
            if ( ! empty( $value['id'] ) ) {
                do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) );
            }
        }

        public static function generate_points_title_html($value) {           
            ?>
            <h2 class="vp-woo-pont-settings vp-woo-pont-settings-title-carrier">
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_pont' ); ?>"><?php _e( 'Pickup points', 'vp-woo-pont' ); ?></a> &gt;
                <?php echo esc_html( $value['title'] ); ?>
            </h2>
            <?php

            if ( ! empty( $value['desc'] ) ) {
                echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
                echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
                echo '</div>';
            }
            echo '<table class="form-table">' . "\n\n";
            if ( ! empty( $value['id'] ) ) {
                do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) );
            }
        }

        public static function get_carriers() {
            $carriers = array(
                'kvikk' => __( 'Kvikk', 'vp-woo-pont' ),
				'foxpost' => __('Foxpost', 'vp-woo-pont'),
				'packeta' => __('Packeta', 'vp-woo-pont'),
                'gls' => __('GLS', 'vp-woo-pont'),
				'posta' => __('MPL', 'vp-woo-pont'),
				'dpd' => __('DPD', 'vp-woo-pont'),
				'sameday' => __('Sameday', 'vp-woo-pont'),
				'expressone' => __('Express One', 'vp-woo-pont'),
				'transsped' => __('Trans-Sped ZERO', 'vp-woo-pont'),
				'pactic' => __( 'Pactic', 'vp-woo-pont' ),
				'csomagpiac' => __( 'Csomagpiac', 'vp-woo-pont' ),
				'custom' => __( 'Custom Labels', 'vp-woo-pont' )
            );
            return $carriers;
        }

        public static function generate_carriers_html($value) {
            include( dirname( __FILE__ ) . '/views/html-admin-carriers.php' );
        }

        public static function toggle_carrier_enabled() {
            if ( current_user_can( 'manage_woocommerce' ) && check_ajax_referer( 'vp-woo-pont-toggle-carrier-enabled', 'security' ) && isset( $_POST['carrier_id'] ) ) {
    
                // Get posted carrier
                $carrier_id = wc_clean( wp_unslash( $_POST['carrier_id'] ) );

                //Get current value and save opposite
                $current_value = get_option('vp_woo_pont_'.$carrier_id.'_enabled', 'no');
                $new_value = $current_value == 'yes' ? 'no' : 'yes';
                update_option('vp_woo_pont_'.$carrier_id.'_enabled', $new_value);

                //Return true or false, indicating the new value
                wp_send_json_success( wc_string_to_bool( $new_value ) );
                wp_die();

            }
    
            wp_send_json_error( 'invalid_carrier_id' );
            wp_die();
        }

        public static function display_custom_field_html($data) {
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
            $template_name = str_replace('vp_', '', $data['type']);
            ob_start();
            include( dirname( __FILE__ ) . '/views/html-admin-'.str_replace('_', '-', $template_name).'.php' );
            echo ob_get_clean();
        }

        //Related to Packeta settings update
        public static function pre_save_settings() {
            if(isset($_GET['carrier']) && $_GET['carrier'] == 'packeta') {

                //Schedule Packeta import if countries changed
                $packeta_countries = array();
                if ( isset( $_POST['vp_woo_pont_packeta_countries'] ) ) {
                    foreach ($_POST['vp_woo_pont_packeta_countries'] as $checkbox_value) {
                        $packeta_countries[] = wc_clean($checkbox_value);
                    }
                    if($packeta_countries != get_option('vp_woo_pont_packeta_countries')) {
                        WC()->queue()->add( 'vp_woo_pont_update_packeta_list', array(), 'vp_woo_pont' );
                    }
                }

                //If the Packeta API key was updated, run the importer
                if(VP_Woo_Pont_Helpers::get_option('packeta_api_key') != $_POST['vp_woo_pont_packeta_api_key']) {
                    WC()->queue()->add( 'vp_woo_pont_update_packeta_list', array(), 'vp_woo_pont' );
                }
            }
        }

        //Save custom field settings
        public static function save_settings() {
            
            //Save packeta home delivery carriers
            if(isset($_GET['carrier']) && $_GET['carrier'] == 'packeta') {
                $packeta_carriers = array();
                if ( isset( $_POST['vp_woo_pont_packeta_carriers'] ) ) {
                    foreach ($_POST['vp_woo_pont_packeta_carriers'] as $country_group => $info) {
                        $packeta_carriers[sanitize_text_field($info['country'])] = sanitize_text_field($info['carrier']);
                    }
                    update_option('vp_woo_pont_packeta_carriers', $packeta_carriers);
                }
            }

            //Save posta countries / services
            if(isset($_GET['carrier']) && $_GET['carrier'] == 'mpl') {
                $posta_countries = array();
                if(isset($_POST['vp_woo_pont_posta_countries'])) {
                    foreach ($_POST['vp_woo_pont_posta_countries'] as $service_id => $countries) {
                        $service_id = wc_clean($service_id);
                        $countries = array_map( 'esc_attr', $countries );
                        $posta_countries[$service_id] = $countries;
                    }
                }
                update_option( 'vp_woo_pont_posta_countries', $posta_countries );
            }

            //Save pactic external providers(this way it can be reset to default if needed)
            if(isset($_GET['carrier']) && $_GET['carrier'] == 'pactic') {
                $pactic_external_providers = array();
                if ( isset( $_POST['vp_woo_pont_pactic_external_providers'] ) ) {
                    foreach ($_POST['vp_woo_pont_pactic_external_providers'] as $checkbox_value) {
                        $pactic_external_providers[] = wc_clean($checkbox_value);
                    }
                }
                update_option('vp_woo_pont_pactic_external_providers', $pactic_external_providers);
            }

            //Save home delivery options
            if(isset($_GET['section']) && $_GET['section'] == 'vp_labels') {
                $home_delivery = array();
                if ( isset( $_POST['vp_woo_pont_home_delivery'] ) ) {
                    foreach ($_POST['vp_woo_pont_home_delivery'] as $shipping_method_id => $provider_id) {
                        $home_delivery[sanitize_text_field($shipping_method_id)] = sanitize_text_field($provider_id);
                    }
                }
                update_option('vp_woo_pont_home_delivery', $home_delivery);
            
                $weight_corrections = array();
                if ( isset( $_POST['vp_woo_pont_weight_correction'] ) ) {
                    foreach ($_POST['vp_woo_pont_weight_correction'] as $weight_correction_id => $weight_correction) {

                        $correction = wc_clean($weight_correction['correction']);
                        $weight_corrections[$weight_correction_id] = array(
                            'correction' => sanitize_text_field($correction),
                            'conditional' => false
                        );

                        //If theres conditions to setup
                        $condition_enabled = isset($weight_correction['condition_enabled']) ? true : false;
                        $conditions = (isset($weight_correction['conditions']) && count($weight_correction['conditions']) > 0);
                        if($condition_enabled && $conditions) {
                            $weight_corrections[$weight_correction_id]['conditional'] = true;
                            $weight_corrections[$weight_correction_id]['conditions'] = array();
                            $weight_corrections[$weight_correction_id]['logic'] = wc_clean($weight_correction['logic']);

                            foreach ($weight_correction['conditions'] as $condition) {
                                $condition_details = array(
                                    'category' => wc_clean($condition['category']),
                                    'comparison' => wc_clean($condition['comparison']),
                                    'value' => $condition[$condition['category']]
                                );

                                $weight_corrections[$weight_correction_id]['conditions'][] = $condition_details;
                            }
                        }

                    }
                }
                update_option( 'vp_woo_pont_weight_corrections', $weight_corrections );

            }

            //Save automations options
            if(isset($_GET['section']) && $_GET['section'] == 'vp_labels') {
                $automations = array();
                if ( isset( $_POST['vp_woo_pont_automation'] ) ) {
                    foreach ($_POST['vp_woo_pont_automation'] as $automation_id => $automation) {

                        $trigger = sanitize_text_field($automation['trigger']);
                        $automations[$automation_id] = array(
                            'trigger' => $trigger,
                            'conditional' => false
                        );

                        //If theres conditions to setup
                        $condition_enabled = isset($automation['condition_enabled']) ? true : false;
                        $conditions = (isset($automation['conditions']) && count($automation['conditions']) > 0);
                        if($condition_enabled && $conditions) {
                            $automations[$automation_id]['conditional'] = true;
                            $automations[$automation_id]['conditions'] = array();
                            $automations[$automation_id]['logic'] = wc_clean($automation['logic']);

                            foreach ($automation['conditions'] as $condition) {
                                $condition_details = array(
                                    'category' => wc_clean($condition['category']),
                                    'comparison' => wc_clean($condition['comparison']),
                                    'value' => $condition[$condition['category']]
                                );

                                $automations[$automation_id]['conditions'][] = $condition_details;
                            }
                        }

                    }
                }
                update_option( 'vp_woo_pont_automations', $automations );
            }

            //Save tracking automations
            if(isset($_GET['section']) && $_GET['section'] == 'vp_tracking') {
                $tracking_automations = array();
                if ( isset( $_POST['vp_woo_pont_tracking_automation'] ) ) {
                    foreach ($_POST['vp_woo_pont_tracking_automation'] as $automation_id => $automation) {

                        $trigger = sanitize_text_field($automation['order_status']);
                        $tracking_automations[$automation_id] = array(
                            'order_status' => $trigger
                        );

                        $package_statuses = (isset($automation['package_status']) && count($automation['package_status']) > 0);

                        if($package_statuses) {
                            foreach ($automation['package_status'] as $provider_id => $tracking_statuses) {
                                $provider_id = sanitize_text_field($provider_id);
                                $tracking_automations[$automation_id][$provider_id] = array();

                                foreach ($tracking_statuses as $tracking_status) {
                                    $tracking_status = sanitize_text_field($tracking_status);
                                    $tracking_automations[$automation_id][$provider_id][] = $tracking_status;
                                }
                            }
                        }
                    }
                }
                update_option( 'vp_woo_pont_tracking_automations', $tracking_automations );
            }

            //Save custom points
            if(isset($_GET['section']) && $_GET['section'] == 'points') {
                $points = array();
                if ( isset( $_POST['vp_woo_pont_points'] ) ) {
                    foreach ($_POST['vp_woo_pont_points'] as $point_id => $point) {
                        $name = wc_clean($point['name']);
                        $id = wc_clean($point['id']);
                        $provider = wc_clean($point['provider']);
                        $coordinates = wc_clean($point['coordinates']);
                        $zip = wc_clean($point['zip']);
                        $addr = wc_clean($point['addr']);
                        $city = wc_clean($point['city']);
                        $comment = wp_kses_post( trim( wp_unslash($point['comment']) ) );
                        $email = wc_clean($point['email']);
                        $hidden = isset($point['hidden']) ? true : false;

                        //Open hours
                        $openhours = array();
                        foreach($point['openhours'] as $day => $hours) {
                            $openhours[$day] = wc_clean($hours);
                        }
                        
                        //Convert coordinates
                        $coordinates = explode(';', $coordinates);

                        //Create new point
                        $points[$point_id] = array(
                            'name' => $name,
                            'id' => $id,
                            'provider' => $provider,
                            'lat' => $coordinates[0],
                            'lon' => $coordinates[1],
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

        //Save COD settings
        public static function save_cod_settings() {

            //Save cod fee options
            if(isset($_GET['section']) && $_GET['section'] == 'cod') {
                $cod_fees = array();
                if ( isset( $_POST['vp_woo_pont_cod_fee'] ) ) {
                    foreach ($_POST['vp_woo_pont_cod_fee'] as $cod_fee_id => $cod_fee) {

                        $cost = wc_clean($cod_fee['cost']);
                        $type = wc_clean($cod_fee['type']);
                        $cod_fees[$cod_fee_id] = array(
                            'cost' => $cost,
                            'type' => $type,
                            'conditional' => false
                        );

                        //If theres conditions to setup
                        $condition_enabled = isset($cod_fee['condition_enabled']) ? true : false;
                        $conditions = (isset($cod_fee['conditions']) && count($cod_fee['conditions']) > 0);
                        if($condition_enabled && $conditions) {
                            $cod_fees[$cod_fee_id]['conditional'] = true;
                            $cod_fees[$cod_fee_id]['conditions'] = array();
                            $cod_fees[$cod_fee_id]['logic'] = wc_clean($cod_fee['logic']);

                            foreach ($cod_fee['conditions'] as $condition) {
                                $condition_details = array(
                                    'category' => wc_clean($condition['category']),
                                    'comparison' => wc_clean($condition['comparison']),
                                    'value' => $condition[$condition['category']]
                                );

                                $cod_fees[$cod_fee_id]['conditions'][] = $condition_details;
                            }
                        }

                    }
                }
                update_option( 'vp_woo_pont_cod_fees', $cod_fees );
            }

        }

        public static function get_email_ids() {

            //Get registered emails
            $mailer = WC()->mailer();
            $email_templates = $mailer->get_emails();
            $emails = array();

            //Omit a few one thats not required at all
            $disabled = ['failed_order', 'customer_note', 'customer_reset_password', 'customer_new_account'];
            foreach ( $email_templates as $email ) {
                if(!in_array($email->id,$disabled)) {
                    $emails[$email->id] = $email->get_title();
                }
            }

            return $emails;
        }

	}

	VP_Woo_Pont_Settings::init();

endif;
