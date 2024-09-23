jQuery(document).ready(function($) {
	
});

/**
 * This file adds some LIVE to the Theme Customizer live preview. To leverage
 * this, set your custom settings to 'postMessage' and then add your handling
 * here. Your javascript should grab settings from customizer controls, and
 * then make any necessary changes to the page using jQuery.
 */
/*
( function( $ ) {

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_primary_color', function( value ) {
		console.log(value);
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color', newval);
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color-alpha-20', vp_woo_pont_hex_to_rgba(newval, 0.2));
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color-alpha-10', vp_woo_pont_hex_to_rgba(newval, 0.1));
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color-alpha-05', vp_woo_pont_hex_to_rgba(newval, 0.05));
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_text_color', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-text-color', newval);
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_price_color', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-price-color', newval);
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_cluster_large_color', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-cluster-large-color', vp_woo_pont_hex_to_rgba(newval, 0.9));
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_cluster_medium_color', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-cluster-medium-color', vp_woo_pont_hex_to_rgba(newval, 0.9));
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_cluster_small_color', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-cluster-small-color', vp_woo_pont_hex_to_rgba(newval, 0.9));
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_title_font_size', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-title-font-size', newval+'px');
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_text_font_size', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-text-font-size', newval+'px');
		});
	});

	//Update CSS in real time
	wp.customize( 'vp_woo_pont_price_font_size', function( value ) {
		value.bind( function( newval ) {
			document.documentElement.style.setProperty('--vp-woo-pont-price-font-size', newval+'px');
		});
	});

	//Update icon in real time
	wp.customize( 'vp_woo_pont_custom_icon', function( value ) {
		value.bind( function( newval ) {
			if(newval != '') {
				$('.vp-woo-pont-provider-icon-custom').css('background-image', 'url(' + newval + ')');
				$('label[for="provider-custom"]').css('padding-left', '44px !important');
			} else {
				$('.vp-woo-pont-provider-icon-custom').css('background-image', 'none');
				$('label[for="provider-custom"]').css('padding-left', '10px !important');
			}
		});
	});

	//Show and hide shipping method icons
	wp.customize( 'vp_woo_pont_small_icons', function( value ) {
		value.bind( function( newval ) {
			if(newval) {
				$('.vp-woo-pont-shipping-method-icons').css('display', 'flex');
			} else {
				$('.vp-woo-pont-shipping-method-icons').hide();
			}
		});
	});

	//Helper function to convert hex to rgba
	function vp_woo_pont_hex_to_rgba(hex, opacity) {
		return 'rgba(' + (hex = hex.replace('#', '')).match(new RegExp('(.{' + hex.length/3 + '})', 'g')).map(function(l) { return parseInt(hex.length%2 ? l+l : l, 16) }).concat(isFinite(opacity) ? opacity : 1).join(',') + ')';
	}

	//Update label in real time
	wp.customize( 'vp_woo_pont_custom_button_label', function( value ) {
		value.bind( function( newval ) {
			$('a#vp-woo-pont-show-map').text(newval);
		});
	});

} )( jQuery );
*/