const zipCodes = require('./zip-codes.js');
var L = require('leaflet');
require('leaflet.markercluster');
require("leaflet.featuregroup.subgroup");
var bodyScrollLock = require('body-scroll-lock');
var JsSearch = require('js-search');

jQuery(document).ready(function($) {

	//Settings page
	var vp_woo_pont_frontend = {
		$overlay: $('.vp-woo-pont-modal-bg'),
		$modal: $('.vp-woo-pont-modal'),
		$close_button: $('.vp-woo-pont-modal-map-close'),
		$filters: $('.vp-woo-pont-modal-sidebar-filters'),
		$list: $('.vp-woo-pont-modal-sidebar-results'),
		$search_field: $('.vp-woo-pont-modal-sidebar-search-field'),
		$search_field_clear: $('.vp-woo-pont-modal-sidebar-search-clear'),
		$map: false,
		map_loaded: false,
		markerClusters: false,
		groups: [],
		list: false,
		enabled_filters: [],
		search: false,
		selected_point: false,
		selected_pickup_point: false,
		providers: false,
		markerIcons: [],
		method_changed: false,
		clickedMarker: false,
		checkout_needs_reload: false,
		is_checkoutwc: false,
		notes: false,
		init: function() {

			//Initialize a map
			this.$map = L.map('vp-woo-pont-modal-map',{
				renderer: L.canvas(),
			});

			//this.$map.attributionControl.setPrefix('');

			//Move zoom controls to bottom right
			this.$map.zoomControl.setPosition('bottomright');

			//Get default parameters for markerClusters
			var markerClusterParams = {
			  spiderfyOnMaxZoom: true,
			  showCoverageOnHover: false,
			  zoomToBoundsOnClick: true,
			  disableClusteringAtZoom: 16,
				removeOutsideVisibleBounds: true,
				chunkedLoading: true,
				maxClusterRadius: 100
			};

			//If custom parameters set
			if(vp_woo_pont_frontend_params.markerClusterParams) {
				markerClusterParams = vp_woo_pont_frontend_params.markerClusterParams;
			}

			//Create cluster layer
			this.markerClusters = new L.MarkerClusterGroup(markerClusterParams);

			//Initialize search engine, which will search for name, city, zip code and address
			this.search = new JsSearch.Search('name');
			this.search.addIndex('name');
			this.search.addIndex('city_nfd');
			this.search.addIndex('zip');
			this.search.addIndex('addr_nfd');
			this.search.addIndex('keywords');

			//When map is moved, update sidebar results
			this.$map.on("moveend", function (e) {

				//Only, if a point is not selected yet
				//In that case, clicking on the map will update the sidebar only
				if(!vp_woo_pont_frontend.selected_point) {
					vp_woo_pont_frontend.syncSidebar();
				}

			});

			//When map is moved, update sidebar results
			this.$map.on("movestart", function (e) {
				vp_woo_pont_frontend.$modal.removeClass('search-focused');
			});

			//Click action to show the modal
			$(document).on( 'click', '#vp-woo-pont-show-map', this.show_modal );
			$(document).on( 'click', '.vp-woo-pont-show-map', this.show_modal );

			//When filters changed, update stuff
			$(document).on( 'change', '.vp-woo-pont-modal-sidebar-filters input', this.toggle_layers );

			//Highlight search box text on click
			this.$search_field.click(function () {
				vp_woo_pont_frontend.$modal.addClass('search-focused');
			});

			//Prevent hitting enter from refreshing the page
			this.$search_field.keypress(function (e) {
				if (e.which == 13) {
					e.preventDefault();
					vp_woo_pont_frontend.$search_field.blur();
				}
			});

			//Fix iOS jumping
			if(this.isiOS()) {
				var searchFieldElement = this.$search_field[0];
				searchFieldElement.addEventListener('touchstart', (event) => {
				    event.stopPropagation();
				    searchFieldElement.style.transform = 'TranslateY(-10000px)'
				    searchFieldElement.focus();
				    setTimeout(function () { searchFieldElement.style.transform = 'none' }, 100);
				});
			}

			//When search field typed, start search with a delay
			var timeout = null;
			this.$search_field.keyup(function() {
				var keyword = $(this).val();

				clearTimeout(timeout);
				timeout = setTimeout(() => {
					//Only if at least 2 characters
					if(keyword.length > 2) {
						vp_woo_pont_frontend.show_search_results(keyword);
					}
				}, 200);

				//Show clear button
				if(keyword.length > 0) {
					vp_woo_pont_frontend.$search_field_clear.show();
				} else {
					vp_woo_pont_frontend.$search_field_clear.hide();
				}

			});

			//Hide modal when esc pressed
			$(document).keyup(function(event){
				if(event.which=='27'){

					//If we are in the search field, just clear that first
					if(vp_woo_pont_frontend.$search_field.is(":focus")) {
						vp_woo_pont_frontend.$search_field.blur();
						vp_woo_pont_frontend.$modal.removeClass('search-focused');
					} else {
						vp_woo_pont_frontend.hide_modal();
					}
				}
			});

			//Select a point in the sidebar
			this.$list.on( 'click', '.vp-woo-pont-modal-sidebar-result', this.select_in_sidebar );

			//Select a pickup point for checkout
			this.$list.on( 'click', '.vp-woo-pont-modal-sidebar-result-select', this.select_pickup_point );

			//Search clear button function
			this.$search_field_clear.click(function(){
				vp_woo_pont_frontend.$search_field.val('');
				vp_woo_pont_frontend.$modal.removeClass('point-selected');
				vp_woo_pont_frontend.$modal.removeClass('search-focused');
				vp_woo_pont_frontend.$modal.removeClass('no-search-result');
				vp_woo_pont_frontend.$modal.removeClass('has-search-result');
				vp_woo_pont_frontend.selected_point = false;
				vp_woo_pont_frontend.$list.find('li.selected').removeClass('selected');
				$('.leaflet-marker-icon.selected').removeClass('selected');
				$(this).hide();
				return false;
			});

			//Hide modal
			this.$close_button.click(function(){
				vp_woo_pont_frontend.hide_modal();
				return false;
			});

			//Check if its a custom CheckoutWC page
			if($('body').hasClass('checkout-wc')) {
				vp_woo_pont_frontend.is_checkoutwc = true;
			}

			//When shipping method changed to vp pont
			$('form.checkout').on( 'change', 'input[name^="shipping_method"]', this.on_shipping_method_change );
			$( document.body ).on( 'updated_checkout', this.on_checkout_updated );
			$( document.body ).on( 'updated_shipping_method', this.on_cart_updated );

			//On page load of shipping fields not visible, we need to reload the checkout page
			var method = $("#shipping_method input:checked").val();
			if(method) {
				vp_woo_pont_frontend.show_and_hide_shipping_address(method.includes('vp_pont'));
			} else {
				var method = $('#shipping_method .shipping_method').val();
				if(method) {
					vp_woo_pont_frontend.show_and_hide_shipping_address(method.includes('vp_pont'));
				}
			}

			//Run on page load too
			this.on_cart_updated();

			//If theres a payment method condition set for the pricing, update the checkout if payment method changed
			if(vp_woo_pont_frontend_params.refresh_payment_methods) {
				$( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() {
					$('body').trigger('update_checkout');
				});
			}

			//Toggle open hours in the sidebar
			this.$list.on( 'click', '.vp-woo-pont-modal-sidebar-result .open-hours', this.toggle_open_hours );

		},
		toggle_open_hours: function() {
			$(this).toggleClass('open');
		},
		on_shipping_method_change: function() {
			var method = $(this).val();
			vp_woo_pont_frontend.method_changed = true;
			vp_woo_pont_frontend.show_and_hide_shipping_address(method.includes('vp_pont'));
		},
		show_and_hide_shipping_address: function(hide) {
			//Don't do anything for CheckoutWC
			if(!vp_woo_pont_frontend.is_checkoutwc) {
				if(hide) {
					$('.woocommerce-shipping-fields').hide();
					$('#ship-to-different-address-checkbox').prop('checked', false);
					$('#ship-to-different-address-checkbox').trigger( 'change' )
				} else {
					$('.woocommerce-shipping-fields').show();
				}
			}
		},
		on_checkout_updated: function() {
			if(!$('.vp-woo-pont-review-order-selected').length && vp_woo_pont_frontend.method_changed && vp_woo_pont_frontend_params.show_on_change) {
				$('#vp-woo-pont-show-map').trigger('click');
			}
		},
		show_modal: function() {

			//Show the overlay
			vp_woo_pont_frontend.$overlay.addClass('show');

			//Show the modal
			setTimeout(function() {
				vp_woo_pont_frontend.$modal.addClass('show');
			}, 1);

			//Get available providers
			var shipping_costs = $(this).data('shipping-costs');
			if(!shipping_costs) shipping_costs = $(this).parent().data('shipping-costs');
			vp_woo_pont_frontend.providers = shipping_costs;

			//Get notes
			var notes = $(this).data('notes');
			if(!notes) notes = $(this).parent().data('notes');
			vp_woo_pont_frontend.notes = notes;

			//Setup provider filter
			vp_woo_pont_frontend.setup_provider_filters(vp_woo_pont_frontend.providers);

			//Init the map, but only once, when the modal is opened
			if(!vp_woo_pont_frontend.map_loaded) {
				vp_woo_pont_frontend.map_loaded = true;
				vp_woo_pont_frontend.init_map();
			} else {
				vp_woo_pont_frontend.geolocate();
			}

			//Remove points from map
			vp_woo_pont_frontend.hide_unwanted_providers();

			//Disable scrolling
			bodyScrollLock.disableBodyScroll(vp_woo_pont_frontend.$modal[0], {
				allowTouchMove: vp_woo_pont_frontend.$list
			});

			//For developers to run extra stuff
			$( document.body ).trigger( 'vp_woo_pont_modal_shown' );
			$( document.body ).addClass('vp-woo-pont-modal-visible');

			return false;
		},
		hide_modal: function() {

			//Hide the modal
			vp_woo_pont_frontend.$modal.removeClass('show');

			//Hide the overlay
			setTimeout(function() {
				vp_woo_pont_frontend.$overlay.removeClass('show');
			}, 100);

			//Enable scrolling
			bodyScrollLock.clearAllBodyScrollLocks();

			$( document.body ).removeClass('vp-woo-pont-modal-visible');

		},
		init_map: function() {

			//Center map
			this.geolocate();

			//Get default parameters
			//Get default parameters
			var mapParameters = {
				maxZoom: 19,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
				detectRetina: false,
				tileLayer: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
			};

			//Check if custom params set
			if(vp_woo_pont_frontend_params.mapParameters) {
				mapParameters = vp_woo_pont_frontend_params.mapParameters;
			}

			//Load image layers and set attribution
			L.tileLayer(mapParameters.tileLayer, mapParameters).addTo(this.$map);

			//Setup markers
			vp_woo_pont_frontend.markerClusters.addTo(vp_woo_pont_frontend.$map);
			vp_woo_pont_frontend.markerClusters._getExpandedVisibleBounds = function () {
				return vp_woo_pont_frontend.markerClusters._map.getBounds();
			};

			//Create groups
			vp_woo_pont_frontend_params.enabled_providers.forEach(function(provider){
				vp_woo_pont_frontend.groups[provider] = L.featureGroup.subGroup(vp_woo_pont_frontend.markerClusters);
				vp_woo_pont_frontend.markerIcons[provider] = L.divIcon({html: '<div><i class="vp-woo-pont-provider-icon-'+provider+'"></i></div>', className: 'vp-woo-pont-marker '+provider, iconSize: [48, 55], iconAnchor: [24, 52]});
			});

			//Loop through json files
			vp_woo_pont_frontend_params.files.forEach(function(json){
				$.getJSON(json.url, function (data) {
					vp_woo_pont_frontend.process_provider(json.type, data);

					//Refresh sidebar
					vp_woo_pont_frontend.syncSidebar();

				});
			});

			//Check for custom points
			if(vp_woo_pont_frontend_params.custom_points) {

				//Only custom providers
				var custom_points = vp_woo_pont_frontend_params.custom_points.filter(obj => {
					return obj.provider === 'custom' && !obj.hidden
				});
				vp_woo_pont_frontend.process_provider('custom', custom_points);

				//Refresh sidebar
				vp_woo_pont_frontend.syncSidebar();
			}

		},
		find_custom_point_override: function(data, provider) {
			var custom_points = vp_woo_pont_frontend_params.custom_points;
			if(custom_points) {
				custom_points.forEach((item, i) => {
					if(item.provider == provider && item.id == data.id) {
						data = item;

						if(item.hidden) {
							data = false;
						}
					}
				});
			}
			return data;
		},
		process_provider: function(provider, data) {
			var allDocuments = [];

			console.log(data);


			//Loop through json results and create a marker on the map
			for (var i = 0; i < data.length; i++) {
				var a = vp_woo_pont_frontend.find_custom_point_override(data[i], provider);
				if(!a) continue;

				var marker = L.marker(new L.LatLng(a.lat, a.lon), { data: a });
				a.marker_id = L.stamp(marker);

				//Hook up click events
				marker.on('click', vp_woo_pont_frontend.select_in_map);

				//Remove accents from place name
				a.city_nfd = a.city.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
				a.addr_nfd = a.addr.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

				//Create markers and create local db for autocomplete search
				if(provider == 'postapont') {
					a.provider = provider+'_'+a.group
					if(vp_woo_pont_frontend.groups[provider+'_'+a.group]) {
						marker.setIcon(vp_woo_pont_frontend.markerIcons[a.provider]);
						marker.addTo(vp_woo_pont_frontend.groups[provider+'_'+a.group]);
						allDocuments.push(a);
					}
				} else {
					a.provider = provider
					if(vp_woo_pont_frontend.groups[provider]) {
						marker.setIcon(vp_woo_pont_frontend.markerIcons[provider]);
						marker.addTo(vp_woo_pont_frontend.groups[provider]);
						allDocuments.push(a);
					}
				}
			}

			//Add the group to the map
			if(provider == 'postapont') {
				var groups = ['10', '20', '30', '50', '70'];
				groups.forEach(function(group){
					if(vp_woo_pont_frontend.groups[provider+'_'+group]) vp_woo_pont_frontend.groups[provider+'_'+group].addTo(vp_woo_pont_frontend.$map);
				});
			} else {
				if(vp_woo_pont_frontend.groups[provider]) {
					vp_woo_pont_frontend.groups[provider].addTo(vp_woo_pont_frontend.$map);
				}
			}

			//Setup search
			vp_woo_pont_frontend.search.addDocuments(allDocuments);
			vp_woo_pont_frontend.hide_unwanted_providers();

		},
		toggle_layers: function() {
			var provider = $(this).attr('id');
			provider = provider.replace('provider-', '');
			if($(this).prop('checked')) {
				vp_woo_pont_frontend.$map.addLayer(vp_woo_pont_frontend.groups[provider]);
			} else {
				vp_woo_pont_frontend.$map.removeLayer(vp_woo_pont_frontend.groups[provider]);
			}

			//Refresh enabled provider list
			vp_woo_pont_frontend.enabled_filters = [];
			vp_woo_pont_frontend.$filters.find('input:checked').each(function(){
				var provider = $(this).parent().data('provider');
				vp_woo_pont_frontend.enabled_filters.push(provider);
			});

			//Refresh sidebar
			vp_woo_pont_frontend.syncSidebar();
		},
		setup_provider_filters: function(providers) {
			vp_woo_pont_frontend.$filters.html(''); //reset content
			vp_woo_pont_frontend.enabled_filters = [];

			//Loop throguh providers and create list items
			Object.keys(providers).forEach(function(provider_id){
				var provider = providers[provider_id];
				if(provider_id.indexOf('packeta_shop_') !== -1 || provider_id.indexOf('packeta_zbox_') !== -1 || provider_id.indexOf('gls_shop_') !== -1 || provider_id.indexOf('gls_locker_') !== -1 || !provider.formatted_net) {
					return;
				}
				var cost = provider.formatted_net;
				if(vp_woo_pont_frontend_params.prices_including_tax) cost = provider.formatted_gross
				var test = $('<li data-provider="'+provider_id+'"><input type="checkbox" checked id="provider-'+provider_id+'"><label for="provider-'+provider_id+'"><i class="vp-woo-pont-provider-icon-'+provider_id+'"></i><strong>'+provider.label+'</strong><em>'+cost+'</em></label></li>');
				vp_woo_pont_frontend.enabled_filters.push(provider_id);
				vp_woo_pont_frontend.$filters.append(test);
			});

			//If theres only one provider, just hide it, no need for the filters
			if(Object.keys(providers).length == 1) {
				vp_woo_pont_frontend.$filters.hide();
			} else {
				vp_woo_pont_frontend.$filters.show();
			}

		},
		hide_unwanted_providers: function() {
			Object.keys(vp_woo_pont_frontend.groups).forEach(function(group){
				if(!Object.keys(vp_woo_pont_frontend.providers).includes(group)) {
					vp_woo_pont_frontend.$map.removeLayer(vp_woo_pont_frontend.groups[group]);
				} else {
					vp_woo_pont_frontend.$map.addLayer(vp_woo_pont_frontend.groups[group]);
				}
			});	
		},
		syncSidebar: function() {

			//Clear the list
			vp_woo_pont_frontend.$list.html('');

			//Create new array
			//TODO: sort ascending

			//Get checked filters
			vp_woo_pont_frontend.$filters.find('input:checked').each(function(){
				var provider = $(this).parent().data('provider');
				var provider_limit = 0;

				//Loop through layers(only the ones that are checked in the filter) and in the viewport and append to the list.
				//Only append 10 / group for performance reasons, i think its still usable this way
				//Only change the sidebar, if a point is not selected already
				if(vp_woo_pont_frontend.groups && vp_woo_pont_frontend.groups[provider]) {
					vp_woo_pont_frontend.groups[provider].eachLayer(function (layer) {
						if (vp_woo_pont_frontend.$map.getBounds().contains(layer.getLatLng())) {
							if(provider_limit < 10) {
								layer.options.data.marker_id = L.stamp(layer);
								vp_woo_pont_frontend.add_to_sidebar(layer.options.data, provider);
							}
							provider_limit++;
						}
				  });
				}

			});

		},
		show_search_results: function(keyword) {

			//Remove accents for more accurate results
			keyword = keyword.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
			var search_results = vp_woo_pont_frontend.search.search(keyword);

			//On search, remove selected point
			vp_woo_pont_frontend.selected_point = false;

			//Set helper classes for styling and mobile
			vp_woo_pont_frontend.$modal.removeClass('point-selected');

			//Remove if provider not selected
			if(vp_woo_pont_frontend.enabled_filters) {
				search_results = search_results.filter(result => vp_woo_pont_frontend.enabled_filters.includes(result.provider));
			}

			if(search_results.length == 0) {

				//Add helper classes
				vp_woo_pont_frontend.$modal.addClass('no-search-result');
				vp_woo_pont_frontend.$modal.removeClass('has-search-result');

				//Add no results message
				var list_item = $('#vp-woo-pont-modal-no-result-sample').clone();
				vp_woo_pont_frontend.$list.find('.vp-woo-pont-modal-sidebar-no-result').remove();
				vp_woo_pont_frontend.$list.prepend(list_item);

			} else {

				//Add helper classes
				vp_woo_pont_frontend.$modal.removeClass('no-search-result');
				vp_woo_pont_frontend.$modal.addClass('has-search-result');

				//Clear the list
				vp_woo_pont_frontend.$list.html('');

				//Limit to max 100 search results
				if (search_results.length > 100) search_results.length = 100;

				//Add results one by one
				//// TODO: do this with vanilla js, so its a lit faster
				search_results.forEach(function(result){
					vp_woo_pont_frontend.add_to_sidebar(result, result.provider);
				});

			}
		},
		add_to_sidebar(item, provider) {

			//// TODO: do this with vanilla js, so its a little faster
			var list_item = $('#vp-woo-pont-modal-list-item-sample').clone();
			var provider_price = vp_woo_pont_frontend.providers[provider];

			console.log(provider);

			//Change price for packeta and gls based on countries
			if((provider == 'packeta_shop' || provider == 'packeta_zbox' || provider == 'gls_shop' || provider == 'gls_locker') && item.country && vp_woo_pont_frontend.providers[provider+'_'+item.country]) {
				provider_price = vp_woo_pont_frontend.providers[provider+'_'+item.country]
			}

			//Change price for packeta
			if(provider == 'packeta_shop' && item.carrier && vp_woo_pont_frontend.providers[provider+'_'+item.carrier]) {
				provider_price = vp_woo_pont_frontend.providers[provider+'_'+item.carrier]
			}

			if(!provider_price) {
				return;
			}

			list_item.removeAttr('id');
			list_item.find('.name').text(item.name);
			list_item.find('.addr').text(item.addr+', '+item.zip+' '+item.city);
			if(vp_woo_pont_frontend_params.prices_including_tax) {
				list_item.find('.cost').html(provider_price.formatted_gross);
			} else {
				list_item.find('.cost').html(provider_price.formatted_net);
			}

			list_item.find('.comment').text(item.comment);
			list_item.attr('data-provider', provider);
			list_item.attr('data-id', item.id);
			list_item.attr('data-marker-id', item.marker_id);
			list_item.find('.icon').addClass('vp-woo-pont-provider-icon-'+provider);
			if(item.hasOwnProperty('cod') && !item.cod) {
				list_item.find('.cod-notice').addClass("show");
			}

			//Show extra note if needed
			if(vp_woo_pont_frontend.notes && vp_woo_pont_frontend.notes[provider]) {
				list_item.find('.comment').prepend('<div class="extra-note">'+vp_woo_pont_frontend.notes[provider]+'</div>');
			}

			//Setup opening hours
			if(vp_woo_pont_frontend_params.open_hours && item.hours) {
				if(typeof item.hours === 'string') {
					list_item.find('.open-hours .value').text(item.hours);
				} else {
					for (var key in item.hours) {
						var index = key-1;
						if (item.hours.hasOwnProperty(key)) {
							if(item.hours[key]) {
								list_item.find('.open-hours li:eq('+index+') .value').text(item.hours[key]);
							}
						}
					}
				}
				list_item.find('.open-hours').addClass('has-hours');
			}

			//Check if need to be selected
			var selected = false;
			if(vp_woo_pont_frontend.selected_point && vp_woo_pont_frontend.selected_point.id == item.id && vp_woo_pont_frontend.selected_point.provider == provider) {
				list_item.addClass('selected');
				selected = true;
			}

			//If its the selected one, move that to the top of the list
			if(selected) {
				//Check if already on the list
				if(vp_woo_pont_frontend.$list.find('li.selected').length < 1) {
					vp_woo_pont_frontend.$list.prepend(list_item);
				}
			} else {
				vp_woo_pont_frontend.$list.append(list_item);
			}

		},
		select_in_sidebar: function() {

			//If filter is not turned on, turn it on so the layer is visible on the map
			var provider = $(this).data('provider');
			if(!vp_woo_pont_frontend.$filters.find('input#provider-'+provider).is(':checked')) {
				vp_woo_pont_frontend.$filters.find('input#provider-'+provider).prop('checked', true);
				vp_woo_pont_frontend.$filters.find('input#provider-'+provider).trigger('change');
			}

			//Add selected class
			vp_woo_pont_frontend.$list.find('li.selected').removeClass('selected');
			$(this).addClass('selected');

			//Remove active marker class
			$('.leaflet-marker-icon.selected').removeClass('selected');

			//Store selected value
			vp_woo_pont_frontend.selected_point = {
				provider: provider,
				id: $(this).data('id'),
				marker_id: parseInt($(this).data('marker-id'), 10)
			}

			//For developers to run extra stuff
			$( document.body ).trigger( 'vp_woo_pont_modal_point_picked' );

			//Set helper classes for styling and mobile
			vp_woo_pont_frontend.$modal.addClass('point-selected');
			vp_woo_pont_frontend.$modal.removeClass('no-search-result');
			vp_woo_pont_frontend.$modal.removeClass('has-search-result');

			//Focus on map
			var layer = vp_woo_pont_frontend.markerClusters.getLayer(vp_woo_pont_frontend.selected_point.marker_id);
  			vp_woo_pont_frontend.$map.setView([layer.getLatLng().lat, layer.getLatLng().lng], 17);
			$(layer._icon).addClass('selected');

			setTimeout(function() {
				$(layer._icon).addClass('selected');
			}, 300);

			vp_woo_pont_frontend.clickedMarker = layer;

			return false;
		},
		select_in_map: function(e) {

			//Remove active marker class
			$('.leaflet-marker-icon.selected').removeClass('selected');

			//Add selected class
			var layer = vp_woo_pont_frontend.markerClusters.getLayer(e.target.options.data.marker_id);
			$(layer._icon).addClass('selected');

			//Store selected value
			vp_woo_pont_frontend.selected_point = {
				provider: e.target.options.data.provider,
				id: e.target.options.data.id,
				marker_id: e.target.options.data.marker_id
			}

			//For developers to run extra stuff
			$( document.body ).trigger( 'vp_woo_pont_modal_point_picked' );

			//Set helper classes for styling and mobile
			vp_woo_pont_frontend.$modal.addClass('point-selected');
			vp_woo_pont_frontend.$modal.removeClass('no-search-result');
			vp_woo_pont_frontend.$modal.removeClass('has-search-result');

			//Move map to center
			vp_woo_pont_frontend.$map.setView(e.latlng);

			//Sync sidebar, which wills how results from the viewport from the map, which includes the selected point anyway
			vp_woo_pont_frontend.syncSidebar();

			//Make sure the selected point is always in the sidebar, at the beginning
			vp_woo_pont_frontend.add_to_sidebar(e.target.options.data, e.target.options.data.provider);

			//Scroll to the top of the list, so the selected point is visible
			vp_woo_pont_frontend.$list.animate({scrollTop: 0});

		},
		select_pickup_point: function() {

			//Collect data
			var $item = $(this).parent();
			var provider = $item.data('provider');
			var id = $item.data('id');
			var page = 'checkout';
			var security = ''
			var ajax_url = '';
			if($('body').hasClass('woocommerce-cart')) page = 'cart';

			//Set based on page
			ajax_url = vp_woo_pont_frontend_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'vp_woo_pont_select' );
			security = vp_woo_pont_frontend_params.nonce;

			//Create reuqest
			var data = {
				action: 'vp_woo_pont_select',
				security: security,
				provider: provider,
				id: id,
				//page: page
			};

			//If its a my account page
			if($('.vp-woo-pont-show-map-my-account').length) {
				data.account = true;
			}

			//Loading indicator animaiton
			$item.addClass( 'processing' );	
			if ($.fn.block) {	
				$item.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
			}

			//Make ajax request
			$.post(ajax_url, data, function(response) {

				//Remove loading indicator
				if ($.fn.block) {
					$item.unblock();
				}
				$item.removeClass('processing');

				//TODO handle error
				if(response.success) {

					// Refresh the review order section, so the selected point is visible automatically
					$('body').trigger('update_checkout');

					//Update cart
					if($('.cart_totals input.shipping_method:checked').length) {
						$('.cart_totals input.shipping_method:checked').trigger( 'change' );
					} else {
						$('.cart_totals input.shipping_method').trigger( 'change' ); //This is when the only available option is the pont shipping one
					}

					//Hide the modal window
					vp_woo_pont_frontend.hide_modal();

					//Store it locally too
					vp_woo_pont_frontend.selected_pickup_point = response.data.point;

					//For the checkout block
					localStorage.setItem('selectedMapDetails', JSON.stringify(response.data.point));

					//For developers to run extra stuff
					$( document.body ).trigger( 'vp_woo_pont_modal_point_selected' );

					//If its a my account page
					if($('.vp-woo-pont-show-map-my-account').length) {
						$('.vp-woo-pont-review-order-selected').show();
						$('.vp-woo-pont-review-order-selected strong').text(response.data.point.name);
						$('.vp-woo-pont-review-order-selected i').addClass('vp-woo-pont-provider-icon-'+response.data.point.provider);
					}

				} else {

				}

			});

			return false;
		},
		on_cart_updated: function() {
			var selected_method = $('.cart_totals .shipping_method:checked').val();

			//Means theres only one shipping method, so its not a checkbox, but check if its the pont one
			if(!selected_method) selected_method = $('.cart_totals .shipping_method').val();

			if(selected_method) {
				if(selected_method.includes('vp_pont')) {
					$('.woocommerce-shipping-destination').hide();
					$('.shipping-calculator-button').hide();
				} else {
					$('.woocommerce-shipping-destination').show();
					$('.shipping-calculator-button').show();
				}
			}
		},
		geolocate: function() {
			var selected_postcode = $('#billing_postcode').val();
			var selected_country = $('#billing_country').val();
			var selected_state = '';
			if(selected_postcode) {
				selected_postcode = parseInt(selected_postcode);
				$.each(zipCodes.vp_woo_pont_state_postcodes, function(state, postcodes) {
					if(postcodes.includes(selected_postcode)) {
						selected_state = state;
						return false;
					}
				});
			}

			if(selected_state && zipCodes.vp_woo_pont_state_coordinates[selected_state] !== undefined) {
				var selected_state_coordinates = zipCodes.vp_woo_pont_state_coordinates[selected_state];
				vp_woo_pont_frontend.$map.setView(selected_state_coordinates, 10);
			} else {

				var default_center_position = vp_woo_pont_frontend_params.default_center_position;

				//Center billing country
				if(selected_country && zipCodes.vp_woo_pont_country_coordinates[selected_country] !== undefined) {
					default_center_position = zipCodes.vp_woo_pont_country_coordinates[selected_country];
					console.log(default_center_position);
				}

				vp_woo_pont_frontend.$map.setView(default_center_position, 8);
			}
		},
		isiOS: function() {
			return [
				'iPad Simulator',
				'iPhone Simulator',
				'iPod Simulator',
				'iPad',
				'iPhone',
				'iPod'
			].includes(navigator.platform)
			// iPad on iOS 13 detection
			|| (navigator.userAgent.includes("Mac") && "ontouchend" in document)
		}
	}

	if($('.vp-woo-pont-modal').length) {
		vp_woo_pont_frontend.init();

		//Init as a global variable
		window.vp_woo_pont_frontend = vp_woo_pont_frontend;

	}
});
