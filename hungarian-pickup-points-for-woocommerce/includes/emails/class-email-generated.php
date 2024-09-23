<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Email_Label_Generated' ) ) :
	class VP_Woo_Pont_Email_Label_Generated extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'vp_woo_pont_label_generated';
			$this->title          = __( 'Label generated', 'vp-woo-pont' );
			$this->description    = __( 'Label generated emails are sent to chosen recipient(s) when a new shipping label is generated, and the file is attached as a PDF.', 'vp-woo-pont' );
			$this->template_html  = 'emails/admin-vp-woo-pont-label-generated.php';
			$this->template_plain = 'emails/plain/admin-vp-woo-pont-label-generated.php';
			$this->template_base = VP_Woo_Pont::$plugin_path . 'templates/';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
				'{tracking_number}' => ''
			);

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );

		}

		public function get_default_subject() {
			return __( '[{site_title}]: Shipping label created for #{order_number}', 'vp-woo-pont' );
		}

		public function get_default_heading() {
			return __( 'Shipping label generated!', 'vp-woo-pont' );
		}

		public function get_default_content() {
			return __("A new shipping label was generated for order {order_number}! Tracking number is {tracking_number}. The PDF file is attached to this e-mail.", 'vp-woo-pont');
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			$this->setup_locale();
			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
				$this->placeholders['{tracking_number}'] = $this->object->get_meta('_vp_woo_pont_parcel_number');
			}

			if ( $this->get_recipient() && $this->is_enabled() ) {
				$test = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'sent_to_admin'      => true,
					'plain_text'         => false,
					'email'              => $this,
					'content'			 => $this->get_body(),
				),
				'',
				$this->template_base
			);
		}

		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'sent_to_admin'      => true,
					'plain_text'         => true,
					'email'              => $this,
					'content'			 => $this->get_body(),
				)
			);
		}

		public function get_body() {
			return $this->format_string( $this->get_option( 'content', $this->get_default_content() ) );
		}

		public function init_form_fields() {
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'vp-woo-pont' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'vp-woo-pont' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'vp-woo-pont' ),
					'default' => 'no',
				),
				'recipient'          => array(
					'title'       => __( 'Recipient(s)', 'vp-woo-pont' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'vp-woo-pont' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
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
				'email_type'         => array(
					'title'       => __( 'Email type', 'vp-woo-pont' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'vp-woo-pont' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}

		public function get_attachments() {
			$attachments = array();
			$attachments[] = VP_Woo_Pont()->labels->generate_download_link($this->object, true);
			return apply_filters( 'woocommerce_email_attachments', $attachments, $this->id, $this->object, $this );
		}

	}

endif;
