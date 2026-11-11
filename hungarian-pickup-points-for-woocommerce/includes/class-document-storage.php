<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Document_Storage', false ) ) :

	/**
	 * Stores the shipping labels and manifests in a protected folder and serves them through
	 * a download endpoint, so links never point directly at the files.
	 */
	class VP_Woo_Pont_Document_Storage {

		const QUERY_VAR = 'vp-woo-pont-document';
		const SECRET_OPTION = '_vp_woo_pont_download_secret';
		const ACCESS_CHECK_TRANSIENT = '_vp_woo_pont_storage_public';

		public static function init() {

			//Serve documents
			add_action( 'wp_loaded', array( __CLASS__, 'maybe_serve_document' ) );

			//Make sure the folder is protected, so existing installs get protected after an update too
			add_action( 'admin_init', array( __CLASS__, 'protect' ) );

			//Warn if the folder is still publicly accessible(for example nginx ignores .htaccess)
			add_action( 'admin_init', array( __CLASS__, 'maybe_recheck_public_access' ) );
			add_action( 'admin_notices', array( __CLASS__, 'public_access_notice' ) );
			add_filter( 'site_status_tests', array( __CLASS__, 'add_site_health_test' ) );

		}

		//Folder where the labels and manifests are stored
		public static function get_base_dir() {
			$upload_dir = wp_upload_dir( null, false );
			return trailingslashit( $upload_dir['basedir'] ) . 'vp-woo-pont-labels/';
		}

		//Public url of the same folder. Only used to check if the folder is protected
		public static function get_base_url() {
			$upload_dir = wp_upload_dir( null, false );
			return trailingslashit( $upload_dir['baseurl'] ) . 'vp-woo-pont-labels/';
		}

		//Create the folder with an empty index.html and a .htaccess file that blocks direct access on Apache
		public static function protect() {
			$base_dir = self::get_base_dir();
			if ( ! wp_mkdir_p( $base_dir ) ) {
				return false;
			}

			$files = array(
				'index.html' => '',
				'.htaccess' => "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
			);

			foreach ( $files as $file_name => $content ) {
				$file_path = $base_dir . $file_name;
				if ( ! file_exists( $file_path ) ) {
					@file_put_contents( $file_path, $content ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}

			return true;
		}

		//Random part of the file names
		public static function generate_random_string() {
			return bin2hex( random_bytes( 16 ) );
		}

		//Get the absolute path of an order's label, or false if it doesn't exist
		public static function get_label_path( $order ) {
			if ( ! $order ) {
				return false;
			}
			return self::resolve_path( $order->get_meta( '_vp_woo_pont_parcel_pdf' ) );
		}

		//Get the absolute path from a file name relative to the folder(like 2026/01/gls-123-abc.pdf)
		public static function resolve_path( $pdf_name ) {
			if ( ! $pdf_name || ! is_string( $pdf_name ) || strpos( $pdf_name, '.pdf' ) === false ) {
				return false;
			}

			$base_dir = self::get_base_dir();
			$path = realpath( $base_dir . $pdf_name );

			//Make sure the file is inside our folder
			if ( ! $path || strpos( $path, realpath( $base_dir ) . DIRECTORY_SEPARATOR ) !== 0 || ! is_file( $path ) ) {
				return false;
			}

			return $path;
		}

		//Secret used to sign the download links
		private static function get_secret() {
			$secret = get_option( self::SECRET_OPTION );
			if ( ! $secret ) {
				$secret = bin2hex( random_bytes( 32 ) );
				update_option( self::SECRET_OPTION, $secret, false );
			}
			return $secret;
		}

		//The token includes the file name, so the link stops working if the label is regenerated
		public static function get_label_token( $order ) {
			$pdf_name = $order->get_meta( '_vp_woo_pont_parcel_pdf' );
			if ( ! $pdf_name ) {
				return false;
			}
			return substr( hash_hmac( 'sha256', $order->get_id() . '|label|' . $pdf_name, self::get_secret() ), 0, 32 );
		}

		//Download url of an order's label
		public static function get_label_url( $order ) {
			$token = self::get_label_token( $order );
			if ( ! $token ) {
				return false;
			}

			return add_query_arg(
				array(
					self::QUERY_VAR => 'label',
					'order' => $order->get_id(),
					'token' => $token,
				),
				trailingslashit( home_url() )
			);
		}

		//Base url for manifests(delivery notes), the file name is appended to the end. Only shop managers can open these
		public static function get_manifest_base_url() {
			return add_query_arg( self::QUERY_VAR, 'manifest', trailingslashit( home_url() ) ) . '&file=';
		}

		public static function is_shop_manager() {
			return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_shop_orders' );
		}

		//Download endpoint
		public static function maybe_serve_document() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Download links are bearer URLs, access is checked with the token or the user's capabilities.
			if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) {
				return;
			}

			$type = sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
			$token = isset( $_GET['token'] ) && is_scalar( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
			$order_id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
			$file = isset( $_GET['file'] ) && is_scalar( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			//Never cache this request
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}

			if ( $type === 'manifest' ) {
				if ( ! self::is_shop_manager() ) {
					self::download_error( __( 'You are not allowed to download this document.', 'vp-woo-pont' ), 403 );
				}
				$path = self::resolve_path( $file );
				if ( ! $path ) {
					self::download_error( __( 'Document not found.', 'vp-woo-pont' ), 404 );
				}
				self::send_file( $path, sanitize_file_name( basename( $path ) ) );
			}

			if ( $type !== 'label' ) {
				self::download_error( __( 'Document not found.', 'vp-woo-pont' ), 404 );
			}

			$order = $order_id ? wc_get_order( $order_id ) : false;
			if ( ! $order ) {
				self::download_error( __( 'Document not found.', 'vp-woo-pont' ), 404 );
			}

			//A valid token(used in emails and integrations), or a shop manager
			$valid_token = self::get_label_token( $order );
			$can_download = ( $token && $valid_token && hash_equals( $valid_token, $token ) ) || self::is_shop_manager();
			if ( ! apply_filters( 'vp_woo_pont_can_download_label', $can_download, $order ) ) {
				$message = __( 'This download link is invalid or has expired.', 'vp-woo-pont' );
				if ( ! is_user_logged_in() ) {
					$login_url = wp_login_url( add_query_arg( array( self::QUERY_VAR => 'label', 'order' => $order_id ), trailingslashit( home_url() ) ) );
					$message .= ' <a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log in to download the label.', 'vp-woo-pont' ) . '</a>';
				}
				self::download_error( $message, 403 );
			}

			$path = self::get_label_path( $order );
			if ( ! $path ) {
				self::download_error( __( 'Document not found.', 'vp-woo-pont' ), 404 );
			}

			//Use the parcel number as the file name if we have one
			$parcel_number = (string) $order->get_meta( '_vp_woo_pont_parcel_number' );
			$file_name = sanitize_file_name( $parcel_number ? $parcel_number . '.pdf' : basename( $path ) );
			$file_name = apply_filters( 'vp_woo_pont_label_download_file_name', $file_name, $order );

			self::send_file( $path, $file_name );
		}

		private static function send_file( $path, $file_name ) {

			//Clear output buffers, so nothing else gets mixed into the PDF
			while ( ob_get_level() ) {
				ob_end_clean();
			}

			nocache_headers();
			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: inline; filename="' . $file_name . '"' ); //Inline, so the browser can display and print it
			header( 'Content-Length: ' . filesize( $path ) );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'Referrer-Policy: no-referrer' );

			readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			exit;
		}

		private static function download_error( $message, $status ) {
			wp_die( wp_kses_post( $message ), esc_html__( 'Shipping label', 'vp-woo-pont' ), array( 'response' => absint( $status ) ) );
		}

		//Check if a file in the folder can be downloaded directly. Returns true, false, or null if we can't tell
		public static function check_public_access() {
			if ( ! self::protect() ) {
				return null;
			}

			//Create a temporary test file with random content
			$file_name = 'access-test-' . self::generate_random_string() . '.txt';
			$content = self::generate_random_string();
			$path = self::get_base_dir() . $file_name;
			if ( ! @file_put_contents( $path, $content ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return null;
			}

			//Try to download it
			$response = wp_remote_get( self::get_base_url() . $file_name, array(
				'timeout' => 5,
				'redirection' => 0,
				'sslverify' => false, //It's a request to our own site, which might use a self-signed certificate
			) );
			wp_delete_file( $path );

			if ( is_wp_error( $response ) ) {
				$result = null;
			} else {
				$result = ( 200 === wp_remote_retrieve_response_code( $response ) && trim( wp_remote_retrieve_body( $response ) ) === $content );
			}

			set_transient( self::ACCESS_CHECK_TRANSIENT, ( $result === null ) ? 'unknown' : ( $result ? 'yes' : 'no' ), ( $result === null ) ? DAY_IN_SECONDS : WEEK_IN_SECONDS );

			return $result;
		}

		//Cached result of the check above
		public static function is_publicly_accessible() {
			$cached = get_transient( self::ACCESS_CHECK_TRANSIENT );
			if ( $cached === false ) {
				return self::check_public_access();
			}
			return ( $cached === 'unknown' ) ? null : ( $cached === 'yes' );
		}

		//The plugin's settings are sections of the WooCommerce shipping tab, like section=vp_labels
		private static function is_settings_page() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return is_admin() && isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) && $_GET['page'] === 'wc-settings' && $_GET['tab'] === 'shipping' && strpos( sanitize_key( wp_unslash( $_GET['section'] ) ), 'vp_' ) === 0;
		}

		//Path of the folder relative to the domain, for the nginx rule
		private static function get_nginx_rule() {
			$path = wp_parse_url( self::get_base_url(), PHP_URL_PATH );
			return 'location ^~ ' . $path . " {\n    deny all;\n}";
		}

		private static function get_public_access_description() {
			return sprintf(
				/* translators: %s: folder path */
				__( 'The folder where shipping labels are stored (%s) can be accessed directly. Your web server ignores the .htaccess file that should block it, which is common with nginx. The files have random names and the plugin only shares protected download links, but you should also block the folder on the server. On nginx, add this rule to the server block and reload nginx:', 'vp-woo-pont' ),
				'<code>' . esc_html( str_replace( ABSPATH, '', self::get_base_dir() ) ) . '</code>'
			);
		}

		public static function maybe_recheck_public_access() {
			if ( isset( $_GET['vp_woo_pont_recheck_storage'] ) && current_user_can( 'manage_woocommerce' ) && check_admin_referer( 'vp_woo_pont_recheck_storage' ) ) {
				self::check_public_access();
				wp_safe_redirect( remove_query_arg( array( 'vp_woo_pont_recheck_storage', '_wpnonce' ) ) );
				exit;
			}
		}

		//Show a notice on the settings pages if the folder is accessible
		public static function public_access_notice() {
			if ( ! self::is_settings_page() || ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			if ( self::is_publicly_accessible() !== true ) {
				return;
			}

			$recheck_url = wp_nonce_url( add_query_arg( 'vp_woo_pont_recheck_storage', '1' ), 'vp_woo_pont_recheck_storage' );
			?>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Shipping labels folder is publicly accessible', 'vp-woo-pont' ); ?></strong></p>
				<p><?php echo wp_kses_post( self::get_public_access_description() ); ?></p>
				<pre style="background:#f6f7f7;padding:8px 12px;display:inline-block;"><?php echo esc_html( self::get_nginx_rule() ); ?></pre>
				<p><a href="<?php echo esc_url( $recheck_url ); ?>" class="button"><?php esc_html_e( 'Check again', 'vp-woo-pont' ); ?></a></p>
			</div>
			<?php
		}

		public static function add_site_health_test( $tests ) {
			$tests['direct']['vp_woo_pont_storage'] = array(
				'label' => __( 'Shipping labels folder', 'vp-woo-pont' ),
				'test' => array( __CLASS__, 'site_health_test' ),
			);
			return $tests;
		}

		public static function site_health_test() {
			$is_public = self::check_public_access();
			$result = array(
				'label' => __( 'Shipping labels are not publicly accessible', 'vp-woo-pont' ),
				'status' => 'good',
				'badge' => array(
					'label' => __( 'Security', 'vp-woo-pont' ),
					'color' => 'blue',
				),
				'description' => '<p>' . esc_html__( 'Direct access to the folder where shipping labels are stored is blocked.', 'vp-woo-pont' ) . '</p>',
				'test' => 'vp_woo_pont_storage',
			);

			if ( $is_public === true ) {
				$result['label'] = __( 'Shipping labels folder is publicly accessible', 'vp-woo-pont' );
				$result['status'] = 'recommended';
				$result['badge']['color'] = 'orange';
				$result['description'] = '<p>' . wp_kses_post( self::get_public_access_description() ) . '</p><pre>' . esc_html( self::get_nginx_rule() ) . '</pre>';
			} elseif ( $is_public === null ) {
				$result['label'] = __( 'Could not check if the shipping labels folder is publicly accessible', 'vp-woo-pont' );
				$result['status'] = 'recommended';
				$result['badge']['color'] = 'orange';
				$result['description'] = '<p>' . esc_html__( 'The site could not send a request to itself to test the folder. Make sure direct access to the folder is blocked on your server:', 'vp-woo-pont' ) . '</p><pre>' . esc_html( self::get_nginx_rule() ) . '</pre>';
			}

			return $result;
		}

	}

	VP_Woo_Pont_Document_Storage::init();

endif;
