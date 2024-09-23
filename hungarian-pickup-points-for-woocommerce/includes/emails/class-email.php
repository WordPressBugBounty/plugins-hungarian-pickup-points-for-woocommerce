<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'VP_Woo_Pont_Tracking_Email', false ) ) :

	class VP_Woo_Pont_Tracking_Email extends WC_Email {
        public function __construct($id, $title, $description, $template_html, $template_plain) {
            $this->id = $id;
            $this->customer_email = true;
            $this->title = $title;
            $this->description = $description;
            $this->template_html = $template_html;
            $this->template_plain = $template_plain;
            $this->template_base = VP_Woo_Pont::$plugin_path . 'templates/';
            $this->placeholders = array(
                '{order_date}'   => '',
				'{order_number}' => '',
				'{customer_name}' => '',
				'{tracking_number}' => ''
			);

			// Call parent constructor.
			parent::__construct();
		}

		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
					'content'			 => $this->get_body(),
                    'additional_content' => $this->get_additional_content(),
                    'tracking_link'      => $this->get_tracking_link(),
				),
				'',
				$this->template_base
			);
		}

		public function get_body() {
			return $this->format_string( $this->get_option( 'content', $this->get_default_content() ) );
		}

		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'vp-woo-pont' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'vp-woo-pont' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'vp-woo-pont' ),
					'default' => 'no',
				),
				'subject'            => array(
					'title'       => __( 'Subject', 'vp-woo-pont' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __( 'Email heading', 'vp-woo-pont' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'content' => array(
					'title'       => __( 'Content', 'vp-woo-pont' ),
					'description' => __( 'Text to appear as the main email content.', 'vp-woo-pont' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'type'        => 'textarea',
					'default'     => $this->get_default_content(),
					'desc_tip'    => true,
				),
				'notes' => array(
					'title'       => __( 'Provider specific note', 'vp-woo-pont' ),
					'description' => __( 'Set an extra note based on the selected shipping provider.', 'vp-woo-pont' ) . ' ' . $placeholder_text,
					'type'        => 'vp_woo_pont_settings_provider_email_text',
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'vp-woo-pont' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'vp-woo-pont' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
                    'sanitize_callback' => 'sanitize_text_field'
				),
			);
		}

        public function get_additional_content() {
            $additional_content = $this->get_option('notes');
            $order = $this->object;
            $note = '';
            if ( is_a( $order, 'WC_Order' ) ) {
                $provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
                if($provider_id && isset($additional_content[$provider_id])) {
                    $note = $additional_content[$provider_id];
                }
            }

            return $this->format_string( $note );
        }

        public function generate_vp_woo_pont_settings_provider_email_text_html( $key, $data) {
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
                'options' => array(),
            );
            $data = wp_parse_args( $data, $defaults );
            $template_name = str_replace('vp_woo_pont_settings_', '', $data['type']);
            ob_start();
            include( VP_Woo_Pont::$plugin_path . '/includes/views/html-admin-'.str_replace('_', '-', $template_name).'.php' );
            return ob_get_clean();    
        }

        public function validate_vp_woo_pont_settings_provider_email_text_field( $key, $value ) {
            $new_value = array();
            foreach ($value as $provider_id => $content) {
                $new_value[$provider_id] = wp_kses_post( trim( stripslashes( $content ) ) );
            }
            return $new_value;
        }

		public function get_tracking_link() {
			$order = $this->object;
			$tracking_link = '';
			if ( is_a( $order, 'WC_Order' ) ) {

				//If custom tracking page is enabled, we need a different link
				if(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false) && $order->get_meta('_vp_woo_pont_tracking_link')) {
					$tracking_link = add_query_arg( array(
						'orderid' => $order->get_id(),
						'x' => $order->get_meta('_vp_woo_pont_tracking_link'),
					), get_permalink(VP_Woo_Pont_Helpers::get_option('custom_tracking_page', false)) );
				} else {
					$tracking_link = VP_Woo_Pont()->tracking->get_tracking_link($order);
				}
			}

			return esc_url($tracking_link);
		}
	}

endif;