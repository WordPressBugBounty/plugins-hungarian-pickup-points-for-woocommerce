<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Customizer', false ) ) :

	class VP_Woo_Pont_Customizer {

		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_customizer_preview_js' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_inline_styles' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_inline_styles' ), 20 );
			add_action( 'woocommerce_after_settings_shipping', array( $this, 'load_design_modal'));
			add_action( 'wp_ajax_vp_woo_pont_save_design', array( $this, 'save_design' ) );
		}

		//To do live refresh while customizing
		public function load_customizer_preview_js() {
			if((isset( $_GET['section']) && ($_GET['section'] == 'vp_pont'))) {
				wp_enqueue_style( 'vp_woo_pont_frontend_css', VP_Woo_Pont()::$plugin_url.'/assets/css/frontend.css', array(), VP_Woo_Pont::$version );
			}
		}

		public function load_design_modal() {
			if((isset( $_GET['section']) && ($_GET['section'] == 'vp_pont'))) {
				$options = $this->get_options();
				include( dirname( __FILE__ ) . '/views/html-modal-design.php' );
			}
		}

		public function get_options() {
			$color_options = array(
				'primary_color' => array(
					'label' => __( 'Primary color', 'vp-woo-pont' ),
					'default' => '#2471B1'
				),
				'text_color' => array(
					'label' => __( 'Text color', 'vp-woo-pont' ),
					'default' => '#838383'
				),
				'price_color' => array(
					'label' => __( 'Price color', 'vp-woo-pont' ),
					'default' => '#49B553'
				),
				'cluster_large_color' => array(
					'label' => __( 'Large cluster color', 'vp-woo-pont' ),
					'default' => '#E86100'
				),
				'cluster_medium_color' => array(
					'label' => __( 'Medium cluster color', 'vp-woo-pont' ),
					'default' => '#F7CB1E'
				),
				'cluster_small_color' => array(
					'label' => __( 'Small cluster color', 'vp-woo-pont' ),
					'default' => '#49B654'
				),
				'border_color' => array(
					'label' => __( 'Border color', 'vp-woo-pont' ),
					'default' => '#DBDBDB'
				)
			);

			$font_options = array(
				'title_font_size' => array(
					'label' => __( 'Title font size', 'vp-woo-pont' ),
					'default' => 14
				),
				'text_font_size' => array(
					'label' => __( 'Text font size', 'vp-woo-pont' ),
					'default' => 12
				),
				'price_font_size' => array(
					'label' => __( 'Price font size', 'vp-woo-pont' ),
					'default' => 12
				),
			);

			$appearance_options = array(
				'filter_checkbox' => array(
					'label' => __( 'Show checkboxes on provider filters', 'vp-woo-pont' ),
					'default' => 'no'
				),
				'small_icons' => array(
					'label' => __( 'Show provider icons below the shipping option', 'vp-woo-pont' ),
					'default' => 'yes'
				),
				'show_on_cart' => array(
					'label' => __( 'Show the selector on the cart page too', 'vp-woo-pont' ),
					'default' => 'yes'
				),
				'show_open_hours' => array(
					'label' => __( 'Show open hours on the map', 'vp-woo-pont' ),
					'default' => 'no'
				),
				'show_on_change' => array(
					'label' => __( 'Show the map automatically on shipping method change', 'vp-woo-pont' ),
					'default' => 'yes'
				),
			);

			return array(
				'colors' => $color_options,
				'fonts' => $font_options,
				'appearance' => $appearance_options,
			);
		}

		//Function to generate inline styles for colors and other stuff
		public function load_inline_styles() {

			//Get saved values
			if(get_option('vp_woo_pont_styles')) {
				$styles = get_option('vp_woo_pont_styles');
			} else {
				$styles = get_theme_mods();
			}

			//Values to get
			$options = $this->get_options();

			//Get color values from DB(backward compatiblity with theme customizer)
			$colors = array();
			foreach($options['colors'] as $key => $option) {
				$color = $option['default'];
				if(isset($styles[$key])) {
					$color = $styles[$key];
				} else if(isset($styles['vp_woo_pont_'.$key])) {
					$color = $styles['vp_woo_pont_'.$key];
				}

				if($color == '' || is_array($color)) {
					$color = $option['default'];
				}

				$colors[$key] = $color;
				$colors[$key.'_rgb'] = $this->hex_to_rgb($color);
			}

			//Get font values from DB(backward compatiblity with theme customizer)
			$fonts = array();
			foreach($options['fonts'] as $key => $option) {
				$fonts[$key] = $option['default'];
				if(isset($styles[$key])) {
					$fonts[$key] = $styles[$key];
				} else if(isset($styles['vp_woo_pont_'.$key])) {
					$fonts[$key] = $styles['vp_woo_pont_'.$key];
				}
			}
			
			//Load customizer CSS
			$custom_css = ':root{';
			$custom_css .= '--vp-woo-pont-primary-color: '.$colors['primary_color'].';';
			$custom_css .= '--vp-woo-pont-primary-color-alpha-20: rgba('.$colors['primary_color_rgb'][0].','.$colors['primary_color_rgb'][1].','.$colors['primary_color_rgb'][2].',0.2);';
			$custom_css .= '--vp-woo-pont-primary-color-alpha-10: rgba('.$colors['primary_color_rgb'][0].','.$colors['primary_color_rgb'][1].','.$colors['primary_color_rgb'][2].',0.1);';
			$custom_css .= '--vp-woo-pont-primary-color-alpha-05: rgba('.$colors['primary_color_rgb'][0].','.$colors['primary_color_rgb'][1].','.$colors['primary_color_rgb'][2].',0.05);';
			$custom_css .= '--vp-woo-pont-text-color: '.$colors['text_color'].';';
			$custom_css .= '--vp-woo-pont-price-color: '.$colors['price_color'].';';
			$custom_css .= '--vp-woo-pont-border-color: '.$colors['border_color'].';';
			$custom_css .= '--vp-woo-pont-border-color-alpha-30: rgba('.$colors['border_color_rgb'][0].','.$colors['border_color_rgb'][1].','.$colors['border_color_rgb'][2].',0.3);';
			$custom_css .= '--vp-woo-pont-cluster-large-color: rgba('.$colors['cluster_large_color_rgb'][0].','.$colors['cluster_large_color_rgb'][1].','.$colors['cluster_large_color_rgb'][2].',0.9);';
			$custom_css .= '--vp-woo-pont-cluster-medium-color: rgba('.$colors['cluster_medium_color_rgb'][0].','.$colors['cluster_medium_color_rgb'][1].','.$colors['cluster_medium_color_rgb'][2].',0.9);';
			$custom_css .= '--vp-woo-pont-cluster-small-color: rgba('.$colors['cluster_small_color_rgb'][0].','.$colors['cluster_small_color_rgb'][1].','.$colors['cluster_small_color_rgb'][2].',0.9);';
			$custom_css .= '--vp-woo-pont-title-font-size: '.$fonts['title_font_size'].'px;';
			$custom_css .= '--vp-woo-pont-text-font-size: '.$fonts['text_font_size'].'px;';
			$custom_css .= '--vp-woo-pont-price-font-size: '.$fonts['price_font_size'].'px;';
			$custom_css .= '}';

			//If a custom icon is set
			if(get_option('vp_woo_pont_custom_icon')) {
				$custom_css .= '.vp-woo-pont-provider-icon-custom{background-image:url('.get_option('vp_woo_pont_custom_icon').')}';
			}

			//Load the inline styles
			wp_add_inline_style( 'vp_woo_pont_frontend_css', $custom_css );
			wp_add_inline_style( 'vp_woo_pont_frontend_tracking', $custom_css );

		}

		//Helper function to convert hex to to rgb(we need rgba for a couple of colors)
		public function hex_to_rgb($hex) {
			// Remove the "#" symbol from the beginning of the color.
			$hex = ltrim( $hex, '#' );

			// Make sure there are 6 digits for the below calculations.
			if ( 3 === strlen( $hex ) ) {
				$hex = substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) . substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) . substr( $hex, 2, 1 ) . substr( $hex, 2, 1 );
			}

			// Get red, green, blue.
			$red   = hexdec( substr( $hex, 0, 2 ) );
			$green = hexdec( substr( $hex, 2, 2 ) );
			$blue  = hexdec( substr( $hex, 4, 2 ) );

			return [$red, $green, $blue];
		}

		public function save_design() {
			check_ajax_referer( 'vp-woo-pont-settings', 'nonce' );

			//Options to save
			$options = $this->get_options();
			$new_values = array();

			//Check for parameters
			if(!isset($_POST['values']) || !is_array($_POST['values'])) {
				wp_send_json_error();
			}

			//Get submitted values, we will sanitize later
			$values = $_POST['values'];

			//Get color values
			foreach($options['colors'] as $key => $color) {
				if(isset($values[$key])) {
					$new_values[$key] = sanitize_hex_color($values[$key]);
				} else {
					$new_values[$key] = $color['default'];
				}
			}

			//Get font settings
			foreach($options['fonts'] as $key => $font) {
				if(isset($values[$key])) {
					$new_values[$key] = intval($values[$key]);
				} else {
					$new_values[$key] = $font['default'];
				}
			}

			//Save the values
			update_option('vp_woo_pont_styles', $new_values);

			//And also save appearance options as separate values
			foreach($options['appearance'] as $key => $appearance) {
				if(isset($values[$key])) {
					update_option('vp_woo_pont_'.$key, wc_bool_to_string($values[$key]));
				}
			}

			//Return success
			wp_send_json_success();

		}

	}

	new VP_Woo_Pont_Customizer();

endif;
