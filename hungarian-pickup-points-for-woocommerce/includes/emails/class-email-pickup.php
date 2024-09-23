<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'VP_Woo_Pont_Email_Pickup', false ) ) :

	class VP_Woo_Pont_Email_Pickup extends VP_Woo_Pont_Tracking_Email {
		public function __construct() {
            parent::__construct(
                'vp_woo_pont_order_pickup',
                __('Ready for pickup', 'vp-woo-pont'),
                __('Send an e-mail to the customer when the package can be picked up from the selected pickup point.', 'vp-woo-pont'),
                'emails/customer-vp-woo-pont-pickup.php',
                'emails/plain/customer-vp-woo-pont-pickup.php'
            );
		}

		public function get_default_subject() {
			return __( 'Your {site_title} Order #{order_number} is ready for pickup!', 'vp-woo-pont' );
		}

		public function get_default_heading( ) {
			return __( "Ready for pickup!", 'vp-woo-pont' );
		}

		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
				$this->placeholders['{customer_name}'] = $this->object->get_billing_first_name();
				$this->placeholders['{tracking_number}'] = $this->object->get_meta('_vp_woo_pont_parcel_number');
			}

			if ( $this->get_recipient() && $this->is_enabled() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		public function get_default_content() {
			return __("Hello {customer_name}! We're happy to let you know that your order has been delivered to your selected pickup point!", 'vp-woo-pont');
		}

	}

endif;
