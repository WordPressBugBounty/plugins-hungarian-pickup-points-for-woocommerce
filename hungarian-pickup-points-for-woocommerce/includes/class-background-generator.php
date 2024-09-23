<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VP_Woo_Pont_Background_Generator', false ) ) :

	class VP_Woo_Pont_Background_Generator {

		public static function init() {

			//Function to run for scheduled async jobs
			add_action('vp_woo_pont_generate_label_async', array(__CLASS__, 'generate_label_async'), 10, 3);

			//Add loading indicator to admin bar for background generation
			add_action('admin_bar_menu', array( __CLASS__, 'background_generator_loading_indicator'), 55);
			add_action('wp_ajax_vp_woo_pont_bg_generate_status', array( __CLASS__, 'background_generator_status' ) );
			add_action('wp_ajax_vp_woo_pont_bg_generate_stop', array( __CLASS__, 'background_generator_stop' ) );

		}

		//Called by WC Queue to generate documents in the background
		public static function generate_label_async($order_id, $provider) {
			$order = wc_get_order($order_id);
			if(!VP_Woo_Pont()->labels->is_label_generated($order)) {
				$return_info = VP_Woo_Pont()->labels->generate_label($order_id, $provider);
			}
		}

		//Check background generation status with ajax
		public static function background_generator_status() {
			check_ajax_referer( 'vp-woo-pont-bg-generator', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
			}
			$response = array();
			if(self::is_async_generate_running()) {
				$response['finished'] = false;
			} else {
				$response['finished'] = true;
			}
			wp_send_json_success($response);
			wp_die();
		}

		//Stop background generation with ajax
		public static function background_generator_stop() {
			check_ajax_referer( 'vp-woo-pont-bg-generator', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-szamlazz' ) );
			}
			WC()->queue()->cancel_all('vp_woo_pont_generate_label_async');
			wp_send_json_success();
			wp_die();
		}

		//Get bg generator status
		public static function is_async_generate_running() {
			$documents_pending = WC()->queue()->search(
				array(
					'status'   => 'pending',
					'hook'    => 'vp_woo_pont_generate_label_async',
					'per_page' => 1,
				)
			);
			return (bool) count( $documents_pending );
		}

		//Add loading indicator to menu bar
		public static function background_generator_loading_indicator($wp_admin_bar) {
			if(self::is_async_generate_running()) {
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'top-secondary',
						'id' => 'vp-woo-pont-bg-generate-loading',
						'title' => '<div class="loading"><em></em><strong>'.__('Generating labels...', 'vp-woo-pont').'</strong></div><div class="finished"><em></em><strong>'.__('Label generation was successful', 'vp-woo-pont').'</strong></div>',
						'href' => '',
					)
				);

				$text = __('Generating shipping labels in the background', 'vp-woo-pont');
				$text2 = __('Labels generated successfully. Reload the page to see the labels.', 'vp-woo-pont');
				$text_stop = __('Stop', 'vp-woo-pont');
				$text_refresh = __('Refresh', 'vp-woo-pont');
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'vp-woo-pont-bg-generate-loading',
						'id' => 'vp-woo-pont-bg-generate-loading-msg',
						'title' => '<div class="loading"><span>'.$text.'</span> <a href="#" id="vp-woo-pont-bg-generate-stop" data-nonce="'.wp_create_nonce( 'vp-woo-pont-bg-generator' ).'">'.$text_stop.'</a></div><div class="finished"><span>'.$text2.'</span> <a href="#" id="vp-woo-pont-bg-generate-refresh">'.$text_refresh.'</a></div>',
						'href' => '',
					)
				);
			}
		}

	}

	VP_Woo_Pont_Background_Generator::init();

endif;
