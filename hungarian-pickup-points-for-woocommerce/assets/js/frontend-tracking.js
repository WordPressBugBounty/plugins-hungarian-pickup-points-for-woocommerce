var L = require('leaflet');
jQuery(document).ready(function($) {

	//Settings page
	var vp_woo_pont_frontend_tracking = {
		$map: false,
		$map_div: $('#vp-woo-pont-tracking-map-view'),
		lat: 0,
		lon: 0,
		provider: '',
		init: function() {

			//Initialize a map
			this.$map = L.map('vp-woo-pont-tracking-map-view',{
				renderer: L.canvas()
			});

			//Get marker details
			var coordinates = this.$map_div.data('coordinates');
			coordinates = coordinates.split(';');
			this.lat = coordinates[0];
			this.lon = coordinates[1];
			this.provider = this.$map_div.data('provider');

			//Init map
			this.init_map();

		},

		init_map: function() {

			//Get default parameters
			var mapParameters = {
				maxZoom: 19,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
				detectRetina: true,
				tileLayer: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
			};

			//Load image layers and set attribution
			L.tileLayer(mapParameters.tileLayer, mapParameters).addTo(this.$map);

			//Create marker
			var marker = L.marker(new L.LatLng(vp_woo_pont_frontend_tracking.lat, vp_woo_pont_frontend_tracking.lon), { });
			var icon = L.divIcon({html: '<div><i class="vp-woo-pont-provider-icon-'+vp_woo_pont_frontend_tracking.provider+'"></i></div>', className: 'vp-woo-pont-marker '+vp_woo_pont_frontend_tracking.provider, iconSize: [48, 55], iconAnchor: [24, 52]});
			marker.setIcon(icon);
			marker.addTo(this.$map);

			//Set view
			vp_woo_pont_frontend_tracking.$map.setView(new L.LatLng(vp_woo_pont_frontend_tracking.lat, vp_woo_pont_frontend_tracking.lon), 13);

		}
	}

	if($('.vp-woo-pont-tracking-map-view').length) {
		vp_woo_pont_frontend_tracking.init();
	}
});
