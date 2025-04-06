var L = require('leaflet');

jQuery(document).ready(function($) {

	//Settings page
	var vp_woo_pont_settings = {
		select_groups: ['dpd'],
		$pricing_table: $('.vp-woo-pont-settings-pricings'),
		$automations_table: $('.vp-woo-pont-settings-automations'),
		$tracking_automations_table: $('.vp-woo-pont-settings-tracking-automations'),
		$cod_fees_table: $('.vp-woo-pont-settings-cod-fees'),
		$weight_corrections_table: $('.vp-woo-pont-settings-weight-corrections'),
		$packagings_table: $('.vp-woo-pont-settings-packagings'),
		$notes_table: $('.vp-woo-pont-settings-notes'),
		$packeta_carriers_table: $('.vp-woo-pont-settings-inline-table-packeta-carriers'),
		$enabled_providers: $('.vp-woo-pont-providers'),
		json_data_points: [],
		activation_nonce: '',
		init: function() {
			this.init_select_groups();
			this.index_packeta_carrier_fields();

			//Packeta carriers buttons
			$('.reload-packeta-carriers').on('click', this.get_packeta_carriers);
			this.$packeta_carriers_table.on('click', '.add-row', this.packeta_carrier_add);
			this.$packeta_carriers_table.on('click', '.delete-row', this.packeta_carrier_remove);
			this.$packeta_carriers_table.on('change', 'select', this.index_packeta_carrier_fields);

			//When enabled providers changed
			this.$enabled_providers.on('change', 'input[name="vp_woo_pont_enabled_providers[]"]', this.on_provider_change);
			this.$pricing_table.on('change', '.vp-woo-pont-settings-pricing-points input', function(){vp_woo_pont_settings.reindex_x_rows('pricings')});
			this.on_provider_change(); //also run on page load

			//Pro version actions
			this.activation_nonce = vp_woo_pont_params.nonces.settings;
			$('#woocommerce_vp_woo_pont_pro_email').keypress(this.submit_pro_on_enter);
			$('#vp_woo_pont_activate_pro').on('click', this.submit_activate_form);
			$('body').on('click', '#vp_woo_pont_deactivate_pro', this.submit_deactivate_form);
			$('body').on('click', '#vp_woo_pont_validate_pro', this.submit_validate_form);
		
			//PRO version modal
			$( '.vp-woo-pont-settings-submenu' ).on('click', '.vp-woo-pont-settings-submenu-pro', function () {
				$(this).WCBackboneModal({
					template: 'vp-woo-pont-modal-pro-version'
				});
				return false;
			});
			
			//Conditional logic controls
			var conditional_fields = [this.$pricing_table, this.$automations_table, this.$tracking_automations_table, this.$cod_fees_table, this.$notes_table, this.$weight_corrections_table, this.$packagings_table];
			var conditional_fields_ids = ['pricings', 'automations', 'tracking_automations', 'cod_fees', 'notes', 'weight_corrections', 'packagings'];

			//Setup conditional fields for pricing
			conditional_fields.forEach(function(table, index){
				var id = conditional_fields_ids[index];
				var singular = id.slice(0, -1);
				singular = singular.replace('_', '-');
				table.on('change', 'select.condition', {group: id}, vp_woo_pont_settings.change_x_condition);
				table.on('change', 'select.vp-woo-pont-settings-repeat-select', function(){vp_woo_pont_settings.reindex_x_rows(id)});
				table.on('click', '.add-row', {group: id}, vp_woo_pont_settings.add_new_x_condition_row);
				table.on('click', '.delete-row', {group: id}, vp_woo_pont_settings.delete_x_condition_row);
				table.on('change', 'input.condition', {group: id}, vp_woo_pont_settings.toggle_x_condition);
				table.on('click', '.delete-'+singular, {group: id}, vp_woo_pont_settings.delete_x_row);
				$('.vp-woo-pont-settings-'+singular+'-add a.add:not([data-disabled]').on('click', {group: id, table: table}, vp_woo_pont_settings.add_new_x_row);

				//If we already have some notes, append the conditional logics
				table.find('ul.conditions[data-options]').each(function(){
					var saved_conditions = $(this).data('options');
					var ul = $(this);

					saved_conditions.forEach(function(condition){
						var sample_row = $('#vp_woo_pont_'+id+'_condition_sample_row').html();
						sample_row = $(sample_row);
						sample_row.find('select.condition').val(condition.category);
						sample_row.find('select.comparison').val(condition.comparison);
						sample_row.find('.value').removeClass('selected');
						sample_row.find('.value[data-condition="'+condition.category+'"]').val(condition.value).addClass('selected').attr('disabled', false);
						ul.append(sample_row);
					});
				});

				if(table.find('.vp-woo-pont-settings-'+singular).length < 1) {
					//$('.vp-woo-pont-settings-'+singular+'-add a:not([data-disabled]').trigger('click');
				}

				//Reindex the fields
				vp_woo_pont_settings.reindex_x_rows(id);

			});

			//Click function for points
			$('.vp-woo-pont-settings-points-list').on('click', '.vp-woo-pont-settings-point-header', vp_woo_pont_settings.toggle_point);
			$('.vp-woo-pont-settings-points-add').on('click', '.add', vp_woo_pont_settings.add_custom_point);
			$('.vp-woo-pont-settings-points-list').on('click', '.delete-point', vp_woo_pont_settings.delete_point);
			$('.vp-woo-pont-settings-points-list').on('click', '.point-value-coordinates', vp_woo_pont_settings.show_coordinates_modal);
			$(document).on( 'click', '#save_coordinates', this.save_coordinates );
			this.reindex_point_rows();

			//Import export pricing & points options
			$('.vp-woo-pont-settings-pricing-add .import, .vp-woo-pont-settings-points-add .import').click(this.import_modal);
			$('.vp-woo-pont-settings-pricing-add .export, .vp-woo-pont-settings-points-add .export').click(this.export_settings);
			$(document).on( 'click', '#vp-woo-pont-modal-import-button', this.import_settings );

			//Trigger JSON import manually
			$('.vp-woo-pont-provider-row').on('click', 'a.import', this.trigger_json_import);

			//Reload buttons
			var reloadableFields = ['sameday_pickup_point', 'csomagpiac_pickup_point'];
			reloadableFields.forEach(function(field){
				var $field = $('#vp_woo_pont_'+field);
				var self = this;
				if($field.length) {
					$field.parent().find('p.description').before('<a href="#" id="vp_woo_pont_'+field+'_reload"><span class="dashicons dashicons-update"></span></a>');
					$field.parent().on('click', '#vp_woo_pont_'+field+'_reload', function(){
						var $button = $(this);
						vp_woo_pont_settings.refresh_field(field, $button);
						return false;
					});
				}

			});

			//Allow sortable provider list
			$( '.vp-woo-pont-providers-rows' ).sortable( {
				items: 'tr',
				cursor: 'move',
				axis: 'y',
				handle: 'td.sort',
				scrollSensitivity: 40,
				helper: function ( event, ui ) {
					ui.children().each( function () {
						$( this ).width( $( this ).width() );
					} );
					ui.css( 'left', '0' );
					return ui;
				},
			});

			//On page load, check for provider selection
			this.check_if_provider_selected();

			//Show edit provider modal
			$( '.vp-woo-pont-providers-wrapper' ).on('click', '.vp-woo-pont-providers-add-button', this.show_edit_provider_modal);
			$( '.vp-woo-pont-providers' ).on('click', '.vp-woo-pont-provider-delete', this.delete_provider);
			$('body').on('click', '.vp-woo-pont-modal-add-provider #vp-woo-pont-modal-add-provider-save', this.select_provider);

			//Restart setup wizard
			$('.vp-woo-pont-restart-setup-wizard').on('click', this.restart_setup_wizard);

			//Validate kvikk API key
			$('.vp-woo-pont-kvikk-api-key-test').on('click', this.validate_kvikk_api_key);

			//Toggle field based on select value
			$('.vp-woo-pont-toggle-select-field').on('change', function(){
				var selected = $(this).val();
				var id = $(this).attr('id');
				var $field = $("[id^='" + id + "_']");
				var $selectedField = $('#'+id+'_'+selected);
				$field.parents('tr').hide();
				if($selectedField) {
					$selectedField.parents('tr').show();
				}
			}).trigger('change');

		},
		check_if_provider_selected: function() {
			$( '.vp-woo-pont-providers-wrapper' ).addClass('provider-selected');
			if( $( '.vp-woo-pont-providers input[name="vp_woo_pont_enabled_providers[]"]:checked' ).length == 0) {
				$( '.vp-woo-pont-providers-wrapper' ).removeClass('provider-selected');
			}
		},
		show_edit_provider_modal: function() {
			//Get currently selected providers by looking for inputs in .vp-woo-pont-providers table, input is checked with name vp_woo_pont_enabled_providers[]
			var selectedProviders = [];
			$( '.vp-woo-pont-providers input[name="vp_woo_pont_enabled_providers[]"]:checked' ).each(function(){
				selectedProviders.push($(this).val());
			});

			var providers = $(this).data('providers');
			var $list = $('<ul class="vp-woo-pont-modal-add-provider-groups"></ul>');
			providers.forEach(function(group){
				var $item = $('<li class="vp-woo-pont-modal-add-provider-group" data-provider="'+group.id+'"><span class="vp-woo-pont-modal-add-provider-group-title"><i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-'+group.id+'"></i><strong>' + group.label + '</strong></span></li>');
				var $groupItem = $('<ul></ul>');
				group.options.forEach(function(provider){
					var checked = '';
					if (selectedProviders.indexOf(provider.id) !== -1) {
						checked = 'checked="checked"';
					}
					$groupItem.append('<li><label><input type="checkbox" name="add_provider" '+checked+' value="'+provider.id+'"><span>' + provider.name + '</span></label></li>');
				});
				$item.append($groupItem);
				$list.append($item);
			});

			$(this).WCBackboneModal({
				template: 'vp-woo-pont-modal-add-provider',
				variable : {
					list: $list.prop('outerHTML')
				}
			});
			return false;
		},
		delete_provider: function() {
			var $row = $(this).parents('tr');
			var provider_id = $row.data('provider');
			var $input = $('.vp-woo-pont-providers input[name="vp_woo_pont_enabled_providers[]"][value="'+provider_id+'"]' );
			$input.attr('checked', false);
			$row.removeClass('selected');
			vp_woo_pont_settings.check_if_provider_selected();
			vp_woo_pont_settings.on_provider_change();
			return false;
		},
		select_provider: function() {
			//Get selected providers
			$( '.vp-woo-pont-providers input[name="vp_woo_pont_enabled_providers"]:checked' ).attr('checked', false);
			$( '.vp-woo-pont-modal-add-provider input[name="add_provider"]:checked' ).each(function(){
				var value = $(this).val();
				var $input = $( '.vp-woo-pont-providers input[name="vp_woo_pont_enabled_providers[]"][value="'+value+'"]' );
				$input.attr('checked', true);
				$input.parents('tr').addClass('selected');
			});
	
			//Hide modal
			$('.modal-close-link').trigger('click');
			vp_woo_pont_settings.check_if_provider_selected();
			vp_woo_pont_settings.on_provider_change();
	
			return false;	
		},
		init_select_groups: function() {
			$.each(vp_woo_pont_settings.select_groups, function( index, value ) {
				var checkbox = $('.vp-woo-pont-select-group-'+value);
				var group_items = $('.vp-woo-pont-select-group-'+value+'-item').parents('tr');
				var selected = checkbox.val();
				group_items.hide();
				$('.vp-woo-pont-select-group-'+value+'-item-'+selected).parents('tr').show();

				checkbox.change(function(e){
					e.preventDefault();
					var selected = $(this).val();
					group_items.hide();
					$('.vp-woo-pont-select-group-'+value+'-item-'+selected).parents('tr').show();
				});
			});
		},
		submit_pro_on_enter: function(e) {
			if (e.which == 13) {
				$('#vp_woo_pont_activate_pro').click();
				return false;
			}
		},
		submit_activate_form: function() {
			var key = $('#woocommerce_vp_woo_pont_pro_key').val();
			var button = $(this);
			var $form = button.parent();
			var $container = button.parents('.vp-woo-pont-pro-widget');

			var data = {
				action: 'vp_woo_pont_license_activate',
				key: key,
				nonce: vp_woo_pont_settings.activation_nonce
			};

			$form.block({
				message: null,
				overlayCSS: {
					background: '#f0f0f1 url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			$container.find('.vp-woo-pont-pro-widget-notice').hide();

			$.post(ajaxurl, data, function(response) {
				//Remove old messages
				if(response.success) {
					window.location.reload();
					return;
				} else {
					$container.find('.vp-woo-pont-pro-widget-notice p').html(response.data.message);
					$container.find('.vp-woo-pont-pro-widget-notice').show();

					$form.addClass('fail');
					setTimeout(function() {
						$form.removeClass('fail');
					}, 1000);
				}
				$form.unblock();
			});

			return false;
		},
		submit_deactivate_form: function() {
			var button = $(this);
			var form = $('.vp-woo-pont-modal-pro-version-content');

			var data = {
				action: 'vp_woo_pont_license_deactivate',
				nonce: vp_woo_pont_settings.activation_nonce
			};

			form.block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			form.find('.notice').hide();

			$.post(ajaxurl, data, function(response) {
				//Remove old messages
				if(response.success) {
					window.location.reload();
					return;
				} else {
					form.find('.notice p').html(response.data.message);
					form.find('.notice').show();
				}
				form.unblock();
			});
			return false;
		},
		submit_validate_form: function() {
			var button = $(this);
			var form = $('.vp-woo-pont-modal-pro-version-content');

			var data = {
				action: 'vp_woo_pont_license_validate',
				nonce: vp_woo_pont_settings.activation_nonce
			};

			form.block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			form.find('.notice').hide();

			$.post(ajaxurl, data, function(response) {
				window.location.reload();
			});
			return false;
		},
		change_x_condition: function(event) {
			var condition = $(this).val();

			//Hide all selects and make them disabled(so it won't be in $_POST)
			$(this).parent().find('.value').removeClass('selected').prop('disabled', true);
			$(this).parent().find('.value[data-condition="'+condition+'"]').addClass('selected').prop('disabled', false);
		},
		add_new_x_condition_row: function(event) {
			var sample_row = $('#vp_woo_pont_'+event.data.group+'_condition_sample_row').html();
			$(this).closest('ul.conditions').append(sample_row);
			vp_woo_pont_settings.reindex_x_rows(event.data.group);
			return false;
		},
		delete_x_condition_row: function(event) {
			$(this).parent().remove();
			vp_woo_pont_settings.reindex_x_rows(event.data.group);
			return false;
		},
		reindex_x_rows: function(group) {
			var group = group.replace('_', '-');
			$('.vp-woo-pont-settings-'+group).find('.vp-woo-pont-settings-repeat-item').each(function(index){
				$(this).find('textarea, select, input').each(function(){
					var name = $(this).data('name');
					if(name) {
						name = name.replace('X', index);
						$(this).attr('name', name);
					}
				});

				//Reindex conditions too
				$(this).find('li').each(function(index_child){
					$(this).find('select, input').each(function(){
						var name = $(this).data('name');
						if(name) {
							name = name.replace('Y', index_child);
							name = name.replace('X', index);
							$(this).attr('name', name);
						}
					});
				});

				$(this).find('.vp-woo-pont-settings-repeat-select').each(function(){
					var val = $(this).val();
					if($(this).hasClass('vp-woo-pont-settings-advanced-option-property')) {
						$('.vp-woo-pont-settings-advanced-option-value option').hide();
						$('.vp-woo-pont-settings-advanced-option-value option[value^="'+val+'"]').show();

						if(!$('.vp-woo-pont-settings-advanced-option-value').val().includes(val)) {
							$('.vp-woo-pont-settings-advanced-option-value option[value^="'+val+'"]').first().prop('selected', true);
						}
					}

					var label = $(this).find('option:selected').text();
					$(this).parent().find('label span').text(label);
					$(this).parent().find('label span').text(label);
					$(this).parent().find('label i').removeClass().addClass(val);
				});

				if(group == 'pricings') {
					vp_woo_pont_settings.toggle_countries_checkboxes($(this));
				}

			});

			if(group == 'pricings') {
				vp_woo_pont_settings.on_provider_change();
			}

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			return false;
		},
		add_new_x_row: function(event) {
			var group = event.data.group;
			var table = event.data.table;
			var singular = group.slice(0, -1);
			var sample_row = $('#vp_woo_pont_'+singular+'_sample_row').html();
			var sample_row_conditon = $('#vp_woo_pont_'+group+'_condition_sample_row').html();
			sample_row = $(sample_row);
			sample_row.find('ul.conditions').append(sample_row_conditon);
			table.append(sample_row);
			vp_woo_pont_settings.reindex_x_rows(group);
			$( document.body ).trigger( 'wc-enhanced-select-init' );
			return false;
		},
		toggle_x_condition: function(event) {
			var group = event.data.group;
			var checked = $(this).is(":checked");
			var note = $(this).closest('.vp-woo-pont-settings-repeat-item').find('ul.conditions');
			if(checked) {
				//Add empty row if no condtions exists
				if(note.find('li').length < 1) {
					var sample_row = $('#vp_woo_pont_'+group+'_condition_sample_row').html();
					note.append(sample_row);
				}
				note.show();
			} else {
				note.hide();
			}

			//Slightly different for automations
			if(group == 'pricings' ) {
				var automation = $(this).closest('.vp-woo-pont-settings-pricing').find('.vp-woo-pont-settings-pricing-if');
				if(checked) {
					automation.show();
				} else {
					automation.hide();
				}
			}

			//Slightly different for automations
			if(group == 'automations' ) {
				var automation = $(this).closest('.vp-woo-pont-settings-automation').find('.vp-woo-pont-settings-automation-if');
				if(checked) {
					automation.show();
				} else {
					automation.hide();
				}
			}

			//Slightly different for notes
			if(group == 'notes' ) {
				var automation = $(this).closest('.vp-woo-pont-settings-note').find('.vp-woo-pont-settings-note-if');
				if(checked) {
					automation.show();
				} else {
					automation.hide();
				}
			}

			//Slightly different for cod fee
			if(group == 'cod_fees' ) {
				var automation = $(this).closest('.vp-woo-pont-settings-cod-fee').find('.vp-woo-pont-settings-cod-fee-if');
				if(checked) {
					automation.show();
				} else {
					automation.hide();
				}
			}

			//Slightly different for weight corrections
			if(group == 'weight_corrections' ) {
				var automation = $(this).closest('.vp-woo-pont-settings-weight-correction').find('.vp-woo-pont-settings-weight-correction-if');
				if(checked) {
					automation.show();
				} else {
					automation.hide();
				}
			}

			//Slightly different for weight corrections
			if(group == 'packagings' ) {
				var automation = $(this).closest('.vp-woo-pont-settings-packaging').find('.vp-woo-pont-settings-packaging-if');
				if(checked) {
					automation.show();
				} else {
					automation.hide();
				}
			}
			
			vp_woo_pont_settings.reindex_x_rows(event.data.group);
		},
		delete_x_row: function(event) {
			$(this).closest('.vp-woo-pont-settings-repeat-item').remove();
			vp_woo_pont_settings.reindex_x_rows(event.data.group);
			return false;
		},
		toggle_countries_checkboxes: function($row) {
			var couriers = ['packeta', 'gls_', 'dpd'];
			if($row.find('input[value*="packeta"]:checked').length || $row.find('input[value*="gls_"]:checked').length || $row.find('input[value*="dpd"]:checked').length) {
				$row.find('.vp-woo-pont-settings-pricing-countries').show();
				$row.find('.vp-woo-pont-settings-pricing-countries').find('li').hide();
				couriers.forEach(function(courier){
					if($row.find('input[value*="'+courier+'"]:checked').length) {
						$row.find('.vp-woo-pont-settings-pricing-countries').find('li[data-courier="'+courier+'"]').show();
					}
				});
			} else {
				$row.find('.vp-woo-pont-settings-pricing-countries').hide();
			}
		},
		load_json_files: function(callback) {
			var providers = vp_woo_pont_params.providers;
			providers['postapont'] = 'Postapont';

			var calls = vp_woo_pont_params.files.map(function(file) {
				console.log(file);
				return $.getJSON( file.url, function( data ) {
					vp_woo_pont_settings.json_data_points.push({provider: file.type, title: providers[file.type], data: data});
				});
			});

			$.when.apply($, calls).fail(function (jqXhr, status, error) {

			}).always(function (test) {
				callback();
			});
		},
		create_point: function(provider, item) {
			var $table = $('.vp-woo-pont-settings-points-list');
			var sample_row = $('#vp_woo_pont_point_sample_row').html();
			var $sample_row = $(sample_row);

			$sample_row.addClass('open');

			$sample_row.find('.point-value-title').text(item.name+' #'+item.id);
			$sample_row.find('.point-value-id').val(item.id);
			$sample_row.find('.point-value-provider').val(provider);
			$sample_row.find('.point-value-name').val(item.name);
			$sample_row.find('.point-value-coordinates').val(item.lat+';'+item.lon);
			$sample_row.find('.point-value-city').val(item.city);
			$sample_row.find('.point-value-zip').val(item.zip);
			$sample_row.find('.point-value-addr').val(item.addr);
			$sample_row.find('.point-value-comment').val(item.comment);

			//If its not a custom item, disable ID change
			if(provider != 'custom') {
				$sample_row.find('.point-value-id').attr('readonly', true);
			}

			$table.append($sample_row);
			vp_woo_pont_settings.reindex_point_rows();
		},
		reindex_point_rows: function() {
			$('.vp-woo-pont-settings-points-list').find('.vp-woo-pont-settings-point').each(function(index){
				$(this).find('textarea, select, input').each(function(){
					var name = $(this).data('name');
					name = name.replace('X', index);
					$(this).attr('name', name);
				});
			});
		},
		toggle_point: function() {
			$(this).parents('.vp-woo-pont-settings-point').toggleClass('open');
			return false;
		},
		add_custom_point: function() {
			var points = $('.vp-woo-pont-settings-point').length;
			vp_woo_pont_settings.create_point('custom', {
				name: 'Pickup point '+parseInt(points+1),
				id: 'point_'+parseInt(points+1),
				lat: 0,
				lon: 0
			});

			return false;
		},
		delete_point: function(event) {
			$(this).closest('.vp-woo-pont-settings-point').remove();
			vp_woo_pont_settings.reindex_point_rows();
			return false;
		},
		show_coordinates_modal: function() {
			$(this).WCBackboneModal({
				template: 'vp-woo-pont-modal-coordinates',
				variable : {}
			});

			//Get existing coordinates
			var coordinates = $(this).val();
			coordinates = coordinates.split(';');
			var $field = $(this);

			//Create a map
			var mymap = L.map('map-coordinates')

			//Set coordinates
			if(coordinates[0] != '0') {
				mymap.setView(coordinates, 17);
			} else {
				mymap.setView([47.25525656277509, 19.54590752720833], 5); //Just to center in Hungary
			}

			//Load images into map
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
			}).addTo(mymap);

			//Store coordinates
			mymap.on('moveend', function(ev) {
				var latlng = mymap.getCenter();
				$('#save_coordinates').data('coordinates', latlng.lat+';'+latlng.lng)
				$('#save_coordinates').data('field', $field);
			});

			return false;
		},
		save_coordinates: function() {
			var $field = $(this).data('field');
			var coordinates = $(this).data('coordinates');
			$field.val(coordinates);
			$('.modal-close-link').trigger('click');
			return false;
		},
		export_settings: function() {
			var nonce = $(this).data('nonce');
			var type = $(this).data('type');

			//Create request
			var data = {
				action: 'vp_woo_pont_export_settings',
				type: type,
				nonce: nonce,
			};

			//Make request
			$.post(ajaxurl, data, function(response) {

				//Convert JSON Array to string.
				var json = JSON.stringify(response.data);

				//Convert JSON string to BLOB.
				json = [json];
				var blob1 = new Blob(json, { type: "text/plain;charset=utf-8" });

				//Check the Browser.
				var isIE = false || !!document.documentMode;
				if (isIE) {
						window.navigator.msSaveBlob(blob1, 'vp-woo-pont-'+type+'.json');
				} else {
						var url = window.URL || window.webkitURL;
						link = url.createObjectURL(blob1);
						var a = $("<a />");
						a.attr("download", 'vp-woo-pont-'+type+'.json');
						a.attr("href", link);
						$("body").append(a);
						a[0].click();
						$("body").remove(a);
				}

			});

			return false;
		},
		import_modal: function() {
			var type = $(this).data('type');

			$(this).WCBackboneModal({
				template: 'vp-woo-pont-modal-import',
				variable : {
					type: type
				}
			});
			return false;
		},
		import_settings: function() {

			//Setup form
			var formData = new FormData($(".vp-woo-pont-modal-import form")[0]);

			//Make request
			$.ajax({
				url: ajaxurl,
				type: "POST",
				data : formData,
				processData: false,
				contentType: false,
				success: function(data){
					window.location.reload();
				},
				error: function(xhr, ajaxOptions, thrownError) {
				}
			});

			return false;

		},
		trigger_json_import: function() {
			var provider_id = $(this).data('provider');
			var $form = $(this).parent();
			var data = {
				action: 'vp_woo_pont_import_json_manually',
				nonce: vp_woo_pont_params.nonces.settings,
				provider: provider_id
			};

			$form.addClass('loading');
			$.post(ajaxurl, data, function(response) {
				$form.removeClass('loading');
				$form.removeClass('has-file');
				if(response.success) {
					$form.addClass('has-file');
					$form.find('.download-link').attr('href', response.data.url+response.data.files[provider_id]);
					$form.find('.download-link').attr('data-qty', response.data.qty);
					console.log(response.data.qty);
				} else {
					$form.addClass('fail');
					setTimeout(function(){
						$form.removeClass('fail');
					}, 1000);
				}
			});

			return false;
		},
		get_packeta_carriers: function() {
			var form = $(this).parents('.forminp');
			var nonce = $(this).data('nonce');
			var $message = form.find('.vp-woo-pont-packeta-carriers-notice');
			var $button = $(this);

			//Setup request parameters
			var data = {
				action: 'vp_woo_pont_packeta_get_carriers',
				nonce: nonce
			};

			//Show loading indicator
			$button.addClass('loading');

			//Make ajax call
			$.post(ajaxurl, data, function(response) {
				$button.removeClass('loading');
				if(response.success) {

					//Append results
					Object.entries(response.data.pickup_point_carriers).forEach(([key, value]) => {
						if(!$('.vp-woo-pont-settings-checkbox-packeta_countries-'+key).length) {
							var item = $('<li><label><input/><span></span></label></li>');
							item.addClass('vp-woo-pont-settings-checkbox-packeta_countries-'+key);
							item.find('input').val(key);
							item.find('input').attr('value', key);
							item.find('input').attr('type', 'checkbox');
							item.find('input').attr('name', 'vp_woo_pont_packeta_countries[]');
							item.find('span').text(value);
							$('.vp-woo-pont-settings-checkbox-group-packeta_countries').append(item);
						}
					});

					$('.vp-woo-pont-settings-inline-table-packeta-carriers .vp-woo-pont-packeta-carriers-table-carrier select').each(function(){
						var $select = $(this);
						Object.entries(response.data.home_delivery_carriers).forEach(([country, carriers]) => {
							if(!$select.find('optgroup[label='+country+']').length) {
								$select.append('<optgroup label="'+country+'"></optgroup>');
							}
							Object.entries(carriers).forEach(([carrier_id, carrier]) => {
								if(!$select.find('option[value='+carrier_id+']').length) {
									$select.find('optgroup[label='+country+']').append('<option value="'+carrier_id+'">'+carrier+'</option>')
								}
							});
						});

					});

				} else {
					$button.addClass('fail');
					setTimeout(function() {
						$button.removeClass('fail');
					}, 1000);
				}
			});

			return false;
		},
		packeta_carrier_add: function() {
			var table = $(this).closest('tbody');
			var row = $(this).closest('tr');
			table.append(row.clone());
			vp_woo_pont_settings.index_packeta_carrier_fields();
			return false;
		},
		packeta_carrier_remove: function() {
			var row = $(this).closest('tr');
			row.remove();
			vp_woo_pont_settings.index_packeta_carrier_fields();
			return false;
		},
		index_packeta_carrier_fields: function() {
			vp_woo_pont_settings.$packeta_carriers_table.find('tbody tr').each(function(index, row){
				$(this).find('select, input[type="hidden"]').each(function(){
					var name = $(this).data('name');
					name = name.replace('X', index);
					$(this).attr('name', name);
				});

				var selected_country = $(this).find('.vp-woo-pont-packeta-carriers-table-country select').val();
				if(!$(this).find('.vp-woo-pont-packeta-carriers-table-country select').length) {
					selected_country = $(this).find('.vp-woo-pont-packeta-carriers-table-country input').val();
				}

				var $carrier_select = $(this).find('.vp-woo-pont-packeta-carriers-table-carrier select');
				var selected_carrier = $carrier_select.val();

				$carrier_select.find('optgroup').each(function(){
					if($(this).attr('label') == selected_country) {
						$(this).removeAttr('hidden');

						if(!$(this).find('option:selected').length) {
							$carrier_select.find('option').removeAttr('selected');
							$(this).find('option').first().attr('selected', true);
						}
					} else {
						$(this).attr('hidden', true);
						$(this).find('option').removeAttr('selected');
					}
				});

				if(!$carrier_select.find('option:selected').length) {
					$carrier_select.find('option').first().attr('selected', true);
				}

			});
		},
		on_provider_change: function() {
			var enabled_providers = vp_woo_pont_settings.$enabled_providers.find('input[name="vp_woo_pont_enabled_providers[]"]:checked');
			var enabled_provider_values = [];
			$('.vp-woo-pont-settings-pricing-points li').hide();

			enabled_providers.each(function(){
				var provider = $(this).val();
				enabled_provider_values.push(provider);
				$('.vp-woo-pont-settings-pricing-points').find('li.provider-'+provider).show();
			});

			$('.vp-woo-pont-settings-pricing-points li input').each(function(){
				var provider = $(this).val();
				if(!enabled_provider_values.includes(provider)) {
					$(this).prop('checked', false);
				}
			});

			$( '.woocommerce-save-button' ).removeAttr( 'disabled' );
		},
		refresh_field: function(field, button) {
			var $this = button;
			var data = {
				action: 'vp_woo_pont_reload_'+field,
				nonce: vp_woo_pont_params.nonces.settings
			};

			if(!$this.hasClass('loading')) {
				$this.addClass('loading');
				$.post(ajaxurl, data, function(response) {
					$this.removeClass('loading');
					$this.addClass('loaded');
					setTimeout(function() {
						$this.removeClass('loaded');
					}, 1000);
					if(response.data) {
						var $select = $('#vp_woo_pont_'+field);
						var currentOption = $select.val();
						$select.val(null).empty();
						response.data.forEach(function(block){
							var newOption = new Option(block.name, block.id, true, true);
							$select.append(newOption).trigger('change');
						});
						$select.val(currentOption);
						$select.trigger('change');
					}
				});
			}

			return false;
		},
		restart_setup_wizard: function() {
			var nonce = $(this).data('nonce');
			var form = $(this).parents('.vp-woo-pont-settings-widget');
			var url = $(this).data('url');
			var data = {
				action: 'vp_woo_pont_restart_setup_wizard',
				nonce: nonce
			};

			form.block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			$.post(ajaxurl, data, function(response) {
				window.location.href = url;
			});

			return false;
		},
		validate_kvikk_api_key: function() {

			//Setup ajax request
			var nonce = $(this).data('nonce');
			var form = $('.vp-woo-pont-kvikk-api-key');
			var api_key = form.find('#vp_woo_pont_kvikk_api_key').val();
			var data = {
				action: 'vp_woo_pont_validate_kvikk_api_key',
				nonce: nonce,
				api_key: api_key
			};

			//Show loading indicator
			form.block({
				message: null,
				overlayCSS: {
					background: '#f0f0f1 url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			//Reset state
			form.find('.vp-woo-pont-kvikk-api-key-results').removeClass('success');
			form.find('.vp-woo-pont-kvikk-api-key-results').removeClass('fail');
			form.find('.vp-woo-pont-kvikk-api-key-results-couriers').slideUp();

			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				form.unblock();

				//Add class to show result
				if(response.success) {

					//Append results to select
					var senders = response.data;
					var $select = $('#vp_woo_pont_kvikk_sender_id');

					//Clear select
					$select.val(null).empty();

					//Add new options
					senders.forEach(function(sender){
						var newOption = new Option(sender.name, sender.id, true, true);
						$select.append(newOption).trigger('change');
					});

					//Add class
					form.find('.vp-woo-pont-kvikk-api-key-results').addClass('success');
				} else {
					form.find('.vp-woo-pont-kvikk-api-key-results').addClass('fail');
				}
				
			});
		},
	}

	//Init settings page
	if($('.vp-woo-pont-settings').length || $('.vp-woo-pont-carriers').length) {
		vp_woo_pont_settings.init();
	}

	//Order management
	$('.vp-woo-pont-remove-from-order').click(function(){
		$this = $(this);
		var r = confirm($this.data('question'));
		var order = $this.data('order');
		var security = $this.data('nonce');

		//Check for question
		if (r != true) {
			return false;
		}

		//Make request
		$.post( ajaxurl, {
				action: 'vp_woo_pont_remove_from_order',
				security: security,
				order: order
		}, function(){
			$this.parent().hide();
		});

		return false;
	});

	//Bulk actions
	var vp_woo_pont_bulk_actions = {
		$dpd_button: $('.vp-woo-pont-dpd-start-sync a'),
		$expressone_button: $('.vp-woo-pont-expressone-start-sync a'),
		$print_button: $('#vp-woo-pont-bulk-print-generate'),
		needs_label: [],
        total_labels: 0,
        labels_generated: 0,
		init: function() {

			//This will sync labels with DPD
			this.$dpd_button.on('click', this.dpd_sync);
			this.$expressone_button.on('click', this.expressone_sync);

			//Print button after the labels generated notice
			this.$print_button.on('click', this.print_generate);

			//Function related to the tracking info modal
			$('.vp-woo-pont-orders-tracking-event-label').on('click', this.show_tracking_modal);
			$( 'body' ).on('click', '.vp-woo-pont-modal-tracking-reload', this.reload_tracking_modal);

			//Print labels on click
			$('.vp-woo-pont-order-column').on('click', '.vp-woo-pont-order-column-print', this.show_print_layout_tooltip);
			$('.vp-woo-pont-order-column').on('blur', '.vp-woo-pont-order-column-print', this.hide_print_layout_tooltip);
			$('.vp-woo-pont-order-column-print-layout').on('click', 'div', this.print_layout_label);

			//Handle bulk print action
			$( '#wpbody' ).on( 'change', '#bulk-action-selector-top', function() {
				if($('#bulk-action-selector-top').val() == 'vp_woo_pont_print_labels') {
					vp_woo_pont_bulk_actions.show_bulk_print_extra();
				} else {
					vp_woo_pont_bulk_actions.hide_bulk_print_extra();
				}
			});

			//Handle bulk download action
			$( '#wpbody' ).on( 'click', '#doaction', function() {
				if($('#bulk-action-selector-top').val() == 'vp_woo_pont_print_labels') {
					vp_woo_pont_bulk_actions.handle_bulk_print();
					return false;
				}

				if($('#bulk-action-selector-top').val() == 'vp_woo_pont_download_labels') {					
					vp_woo_pont_bulk_actions.handle_bulk_download();
					return false;
				}
			});

			//Handle bulk generate action
			$( '#wpbody' ).on( 'click', '#doaction', function() {
                if($('#bulk-action-selector-top').val() == 'vp_woo_pont_generate_labels') {
                    vp_woo_pont_bulk_actions.show_bulk_generate_modal();
                    return false;
                }
            });

            //Select all checkbox in table header
            $('body').on('change', '.vp-woo-pont-modal-generate-selectall', function(){ 
                var checked = $(this).is(':checked');
                $('.vp-woo-pont-modal-generate table input[type="checkbox"]').attr('checked', checked);
            });

            //When the modal closes, cancel all ajax requests
            $('body').on( 'wc_backbone_modal_removed', this.on_modal_close );

            //Print button
            $('body').on( 'click', '.vp-woo-pont-modal-generate-button-download', this.download_in_bulk );
            $('body').on( 'click', '.vp-woo-pont-modal-generate-button-print', this.print_in_bulk );
            $('body').on( 'click', '.vp-woo-pont-modal-generate-label-print', this.print_single_label );


		},
		show_print_layout_tooltip: function() {

			//Get sticker details
			var provider_id = $(this).parents('.vp-woo-pont-order-column').data('provider');
			provider_id = provider_id.split('_')[0];
			if(provider_id.includes('posta')) provider_id = 'posta';
			var sticker_parameters = vp_woo_pont_params.sticker_parameters[provider_id];
			var $button = $(this).find('.vp-woo-pont-order-column-print-button');

			if(sticker_parameters && sticker_parameters.format && !$button.hasClass('multiple_parcels')) {

				//Get paper and reset it
				var $paper = $(this).find('.vp-woo-pont-order-column-print-layout');
				$paper.html('');
				$paper.addClass(sticker_parameters.format);
				$paper.attr('data-sections', sticker_parameters.sections);
				$paper.attr('data-layout', sticker_parameters.layout);
				$paper.attr('data-format', sticker_parameters.format);

				//Create stickers on paper
				for (let i of Array(sticker_parameters.sections).keys()) {
					var $sticker = $('<div>');
					$sticker.data('page', i+1);
					if(i == 0) {
						$sticker.addClass('selected');
					}
					$paper.append($sticker);
				}

				//Show paper
				$(this).toggleClass('active');

			} else {

				//Create label url
				var order = $(this).parents('.vp-woo-pont-order-column').data('order');
				var index = 0;
				var pdf_url = vp_woo_pont_params.print_url;
				var $column = $(this).parents('.vp-woo-pont-order-column');
				pdf_url = pdf_url.replace('X', order);
				pdf_url = pdf_url.replace('Y', index);

				//Get loading indicator color
				var is_even = $(this).parents('tr').is(':even');
				var color = '#f6f7f7';
				if(is_even) {
					color = '#fff';
				}

				//Show loading indicator
				vp_woo_pont_metabox.loading_indicator($column, color);

				//Just print if there are no layout options
				if($(this).parents('.vp-woo-pont-order-column-printing').hasClass('vp-woo-pont-order-column-quick')) {
					var $button = $(this).parents('.vp-woo-pont-order-column-quick');

					//Make ajax request
					var data = {
						action: vp_woo_pont_metabox.prefix+'generate_quick_label',
						nonce: vp_woo_pont_params.nonces.generate,
						order: order,
					};

					//Make request
					$.post(ajaxurl, data, function(response) {

						//On success and error
						if(!response.data.error) {

							//Show download button
							$button.removeClass('vp-woo-pont-order-column-quick');
							$button.find('.vp-woo-pont-order-column-pdf').show();
							$button.find('.vp-woo-pont-order-column-pdf').attr('href', response.data.pdf);

							//Change button label
							var label = $button.find('.vp-woo-pont-order-column-print-button').data('alt-label');
							$button.find('.vp-woo-pont-order-column-print-button .label').text(label);

							//Print PDF
							printJS({printable: pdf_url, onLoadingEnd: function(){

								//Hide loading indicator
								$column.unblock();

							}});

						} else {

							//Hide loading indicator
							$column.unblock();

							//Add fail class to shake briefly
							$button.addClass('fail');
							setTimeout(function(){
								$button.removeClass('fail');
							}, 1000);

						}

					});

				} else {

					//Open print modal
					printJS({printable: pdf_url, onLoadingEnd: function(){

						//Hide loading indicator
						$column.unblock();

					}});

				}


			}

			return false;
		},
		hide_print_layout_tooltip: function() {
			$('.vp-woo-pont-order-column-print').removeClass('active');
		},
		print_layout_label: function() {
			var index = $(this).data('page');
			var $popup = $(this).parent();
			var order = $(this).parents('.vp-woo-pont-order-column').data('order');
			$('.vp-woo-pont-order-column-print-layout div').removeClass('selected');
			$(this).addClass('selected');

			//Generate print URL
			var pdf_url = vp_woo_pont_params.print_url;
			pdf_url = pdf_url.replace('X', order);
			pdf_url = pdf_url.replace('Y', index);

			//Trigger quick label generator
			if($(this).parents('.vp-woo-pont-order-column-printing').hasClass('vp-woo-pont-order-column-quick')) {

				var $button = $(this).parents('.vp-woo-pont-order-column-quick');
				var $parent = $button.parents('.vp-woo-pont-order-column');

				//Show loading indicator
				$parent.block({
					message: null,
					overlayCSS: {
						background: '#F5F5F5 url(' + vp_woo_pont_params.loading + ') no-repeat center',
						backgroundSize: '16px 16px',
						opacity: 0.6
					}
				});

				//Make ajax request
				var data = {
					action: vp_woo_pont_metabox.prefix+'generate_quick_label',
					nonce: vp_woo_pont_params.nonces.generate,
					order: order,
				};

				//Make request
				$.post(ajaxurl, data, function(response) {

					//Hide loading indicator
					$parent.unblock();

					//On success and error
					if(!response.data.error) {

						//Show download button
						$button.removeClass('vp-woo-pont-order-column-quick');
						$button.find('.vp-woo-pont-order-column-pdf').show();
						$button.find('.vp-woo-pont-order-column-pdf').attr('href', response.data.pdf);

						//Change button label
						var label = $button.find('.vp-woo-pont-order-column-print-button').data('alt-label');
						$button.find('.vp-woo-pont-order-column-print-button .label').text(label);

						//Print PDF
						printJS(pdf_url);

					} else {

						//Add fail class to shake briefly
						$button.addClass('fail');
						setTimeout(function(){
							$button.removeClass('fail');
						}, 1000);

					}

				});

			} else {

				//Show loading indicator
				vp_woo_pont_metabox.loading_indicator($popup, '#fff');

				//Open print modal
				printJS({printable: pdf_url, onLoadingEnd: function(){

					//Hide loading indicator
					$popup.unblock();

				}});

			}

			return false;
		},
		show_bulk_print_extra: function() {

			//First, hide it, if needed, it will be created again
			vp_woo_pont_bulk_actions.hide_bulk_print_extra();

			//Check if selected is from a single provider
			var checkedOrders = $("#the-list .check-column input:checked");
			var providers = [];

			//Collect selected items
			$(checkedOrders).each(function(i) {
				var order_details = $(checkedOrders[i]).parents('tr').find('.vp-woo-pont-order-details').data('order-details');
				var provider = order_details.carrier_id;
				providers.push(provider);
			});

			//Check if all using the same sticker size
			var enable_sticker_position = true;
			providers.forEach(provider_id => {
				var sticker_parameters = vp_woo_pont_params.sticker_parameters[provider_id];
				if(!sticker_parameters.sticker || sticker_parameters.sticker != 'A6') {
					enable_sticker_position = false;
				}
			});

			//If theres only one type of provider, we can generate a bulk PDF with custom position
			if (enable_sticker_position) {

				//Check if provider supports this
				var provider_id = providers[0];
				var sticker_parameters = vp_woo_pont_params.sticker_parameters[provider_id];
				if(sticker_parameters.format) {

					//Create a new select element for sticker position
					var select = $('<select>').attr('id', 'vp-woo-pont-bulk-print-position');
					var placeholder = $('<option>').val(0).text('Címke pozíció');
					select.append(placeholder);
					for (var i = 0; i < sticker_parameters.sections; i++) {
						if(i != 0) {
							var option = $('<option>').val(i).text(i + ' címke átugrása');
							select.append(option);
						}
					}

					// Insert the select element after the bulk action selector
					$('#bulk-action-selector-top').after(select);

				}

			}

		},
		hide_bulk_print_extra: function() {
			$('#vp-woo-pont-bulk-print-position').remove();
		},
		handle_bulk_print: function() {

			//Check if selected is from a single provider
			var pdf_url = vp_woo_pont_bulk_actions.get_bulk_pdf_url();

			//Show loading indicator
			vp_woo_pont_metabox.loading_indicator($('.bulkactions'), '#F0F0F1');

			//Open print modal
			printJS({printable: pdf_url, onLoadingEnd: function(){

				//Hide loading indicator
				$('.bulkactions').unblock();

			}});

			return false;
		},
		handle_bulk_download: function() {
			var pdf = vp_woo_pont_bulk_actions.get_bulk_pdf_url();
			if(vp_woo_pont_params.bulk_download_zip) {
				pdf += '&format=zip';
			}

			//Download it
			window.open(pdf);

			return false;
		},
		get_bulk_pdf_url: function() {
			//Check if selected is from a single provider
			var checkedOrders = $("#the-list .check-column input:checked");
			var orderIds = [];

			//Collect selected items
			$(checkedOrders).each(function(i) {
				var order_id = $(checkedOrders[i]).val();
				orderIds.push(order_id);
			});

			//Generate print URL
			var pdf_url = vp_woo_pont_params.print_url;
			var skip_section = 0;
			pdf_url = pdf_url.replace('X', orderIds.join());
			if($('#vp-woo-pont-bulk-print-position').val()) {
				skip_section = $('#vp-woo-pont-bulk-print-position').val();
			}
			pdf_url = pdf_url.replace('Y', skip_section);
			return pdf_url;
		},
		dpd_sync: function() {
			var nonce = $(this).data('nonce');
			var $button = $(this);

			//Create request
			var data = {
				action: vp_woo_pont_metabox.prefix+'dpd_run_sync',
				nonce: nonce
			};

			//Only one click
			if($button.hasClass('loading')) return false;

			//Loading indicator
			$button.addClass('loading');

			//Make an ajax call in the background. No error handling, since this usually works just fine
			$.post(ajaxurl, data, function(response) {
				$button.addClass('success');
				setTimeout(function(){
					$button.removeClass('loading');
					$button.removeClass('success');
				}, 3000);
			});

			return false;
		},
		expressone_sync: function() {
			var nonce = $(this).data('nonce');
			var $button = $(this);

			//Create request
			var data = {
				action: vp_woo_pont_metabox.prefix+'expressone_run_sync',
				nonce: nonce
			};

			//Only one click
			if($button.hasClass('loading')) return false;

			//Loading indicator
			$button.addClass('loading');

			//Make an ajax call in the background. No error handling, since this usually works just fine
			$.post(ajaxurl, data, function(response) {
				$button.addClass('success');

				if(response.data.pdf) {
					window.open(response.data.pdf, '_blank');
				}

				setTimeout(function(){
					$button.removeClass('loading');
					$button.removeClass('success');
				}, 3000);
			});

			return false;
		},
		print_generate: function() {
			var $button = $(this);
			var orders = $(this).data('orders');

			//Generate print URL
			var pdf_url = vp_woo_pont_params.print_url;
			pdf_url = pdf_url.replace('X', orders.join());
			pdf_url = pdf_url.replace('Y', 0);

			//Print the PDF
			printJS(pdf_url);

			return false;
		},
		show_tracking_modal: function() {
			var events = $(this).data('events');
			var order_id = $(this).data('order_id');

			//Create request
			var data = {
				action: vp_woo_pont_metabox.prefix+'get_tracking_info',
				nonce: vp_woo_pont_params.nonces.tracking,
				order: $(this).data('order_id'),
			};

			//Show modal
			$(this).WCBackboneModal({
				template: 'vp-woo-pont-modal-tracking'
			});

			//Shlow loading indicator
			$('.vp-woo-pont-modal-tracking article').block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			//Get fresh
			$.post(ajaxurl, data, function(response) {

				//Get tracking number and link
				var link = response.data.link;
				var parcel_number = response.data.tracking_number;

				//Hide loading indicator
				$('.vp-woo-pont-modal-tracking article').unblock();

				//Create a list of events
				var $ul = $('.vp-woo-pont-modal-tracking article ul');
				$(response.data.events).each(function(i) {
					var location = '';
					if(response.data.events[i]['location']) {
						location = '- <span class="location">'+response.data.events[i]['location']+'</span>';
					}
					$ul.append('<li class="note"><div class="note_content"><p>'+response.data.events[i]['label']+'</p></div><p class="meta"><span class="date">'+response.data.events[i]['date']+'</span> '+location+'</p></li>');
				});

				//Append stuff into modal
				$('.vp-woo-pont-modal-tracking-date').text(response.data.updated);
				$('.vp-woo-pont-modal-tracking-number').text(parcel_number);
				$('.vp-woo-pont-modal-tracking-reload').data('order', order_id);
				$('.vp-woo-pont-modal-tracking-link').attr('href', link);
			});

			return false;
		},
		reload_tracking_modal: function() {
			var $this = $(this);
			var $container = $('.vp-woo-pont-modal-tracking-content');
	
			//Show loading indicator
			$container.block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});
	
			//Create request
			var data = {
				action: 'vp_woo_pont_update_tracking_info',
				nonce: vp_woo_pont_params.nonces.tracking,
				order: $this.data('order'),
			};
	
			$.post(ajaxurl, data, function(response) {
	
				//Hide loading indicator
				$container.unblock();
	
				//On success
				if(!response.data.error) {
	
					//Set updated date
					var $date = $('.vp-woo-pont-modal-tracking-date')
					$date.text($date.data('now'));
	
					//If we have a new item, append it
					if(response.data.tracking_info.length > $container.find('ul').find('li').length-1) {
	
						//Show the latest item
						var latest = response.data.tracking_info[0];
	
						//Create new item
						var $event = $container.find('.note-sample').clone();
						$event.removeClass('note-sample');
						$event.find('.note_content p').text(latest.label);
						$event.find('.exact-date').text(latest.date);
						$event.show();
	
						setTimeout(function() {
							$event.removeClass('customer-note');
						}, 3000);
	
						//Prepend to list
						$container.find('ul').prepend($event);
	
					}
				}
	
			});
	
			return false;
		},
		show_bulk_generate_modal: function() {

            //Get selected order details, input named id[] and checked
            var selected_orders = [];
            $("#the-list .check-column input:checked").each(function(){
                var $row = $(this).parents('tr');
                var order_details = $row.find('.vp-woo-pont-order-details').data('order-details');
                if(order_details.provider_id) {
                    order_details.table_row = $row;
                    selected_orders.push(order_details);
                }
            });

            //Show modal
            $(this).WCBackboneModal({
                template: 'vp-woo-pont-modal-generate'
            });

			//Call action
			$( document.body ).trigger( 'vp_woo_pont_generate_modal_shown' );

            //Reset content
            var $table = $('.vp-woo-pont-modal-generate table');
            vp_woo_pont_bulk_actions.reset_modal_data();

            //Set total labels
            vp_woo_pont_bulk_actions.total_labels = selected_orders.length;

            //Create an array that needs a label
            vp_woo_pont_bulk_actions.needs_label = [];

            //Add selected orders to table
            selected_orders.forEach(function(order){
                var sample_row = $('#vp_woo_pont_modal_generate_sample_row').html();
                sample_row = $(sample_row);
                sample_row.find('.cell-order-number strong').text('#'+order.order_number);
                sample_row.find('.cell-order-number span').text(order.customer_name);
                sample_row.find('.vp-woo-pont-provider-icon').addClass('vp-woo-pont-provider-icon-'+order.provider_id);
                sample_row.find('.cell-address span').text(order.shipping_address);
                sample_row.find('.vp-woo-pont-modal-generate-label').text(order.parcel_number);
                sample_row.find('.vp-woo-pont-modal-generate-label').attr('href', order.download_link);
                sample_row.find('.vp-woo-pont-modal-generate-label-print').attr('href', order.download_link);
                sample_row.data('order-details', order);
                sample_row.find('input').val(order.order_id);

                //If we have a parcel number, hide the loading indicator
                if(order.parcel_number) {
                    sample_row.addClass('has-label');
                } else {
                    vp_woo_pont_bulk_actions.needs_label.push(sample_row);
                }

                //Append to the table
                $table.find('tbody').append(sample_row);
            });

            //Set already generated labels
            vp_woo_pont_bulk_actions.labels_generated = vp_woo_pont_bulk_actions.total_labels - vp_woo_pont_bulk_actions.needs_label.length;

            //Start to generate labels
            vp_woo_pont_bulk_actions.update_counter();
            vp_woo_pont_bulk_actions.generate_label();
                
            return false;
        },
        generate_label: function() {

            //If we have no more orders that need a label, stop
            if(vp_woo_pont_bulk_actions.needs_label.length == 0) {
                return false;
            }
                
            //Get order details
            var $row = vp_woo_pont_bulk_actions.needs_label[0];
            var order_details = $row.data('order-details');

            //Make ajax request
            var data = {
                action: 'vp_woo_pont_generate_quick_label',
                nonce: vp_woo_pont_params.nonces.generate,
                order: order_details.order_id,
            };

            //Make request
            $.post(ajaxurl, data, function(response) {

                //On success and error
                if(!response.data.error) {
                    $row.find('.vp-woo-pont-modal-generate-label').text(response.data.number);
                    $row.find('.vp-woo-pont-modal-generate-label').attr('href', response.data.pdf);
                    $row.find('.vp-woo-pont-modal-generate-label-print').attr('href', response.data.pdf);
                    $row.addClass('has-label');

                    //Update order details in table too
                    var $table_row = order_details.table_row;
                    if(response.data.order_status) {
						//remove wc- prefix
						var status_class = response.data.order_status.status;
						status_class = status_class.replace('wc-', '');
                        $table_row.find('td.order_status mark').removeClass(function(index, className) {
                            return (className.match(/(^|\s)status-\S+/g) || []).join(' ');
                        }).addClass('status-'+status_class);
                        $table_row.find('td.order_status span').text(response.data.order_status.name);

						//If we are filtering orders by status, we can hide the row from the table if status changes
						//Check for current status filter, url param status value
						var url = new URL(window.location.href);
						var status = url.searchParams.get("status"); //HPOS
						var post_status = url.searchParams.get("post_status"); //Legacy posts table
						if(status || post_status) {
							if(post_status || status != 'all') {

								//Remove checkbox and hide row
								$table_row.slideUp();
								$table_row.find('.check-column input').prop('checked', false);

								//Get selected status filter
								var $selected_filter = $('ul.subsubsub a.current').parent();
								var $target_filter = $('ul.subsubsub li.'+response.data.order_status.status);

								//And set new counters
								var selected_count = parseInt($selected_filter.find('.count').text().replace(/[^0-9]/g, ''));
								var target_count = parseInt($target_filter.find('.count').text().replace(/[^0-9]/g, ''));
								$selected_filter.find('.count').text('('+(selected_count-1)+')');
								$target_filter.find('.count').text('('+(target_count+1)+')');

							}
						}

                    }

                    //Update tracking info column
                    var tracking_info = '<div class="order-status vp-woo-pont-orders-tracking-event"><a class="vp-woo-pont-orders-tracking-event-external" target="_blank" href="#"><i class="vp-woo-pont-provider-icon-'+order_details.provider_id+'"></i></a><a class="vp-woo-pont-orders-tracking-event-label" href="#" data-order_id="'+order_details.order_id+'"><span>'+response.data.number+'</span></a></div>';
                    $table_row.find('td.vp_woo_pont_tracking').append(tracking_info);

                    //Update shipment info column
                    var $button = $table_row.find('.vp-woo-pont-order-column-quick');
					$button.removeClass('vp-woo-pont-order-column-quick');
					$button.find('.vp-woo-pont-order-column-pdf').show();
					$button.find('.vp-woo-pont-order-column-pdf').attr('href', response.data.pdf);

					//Change button label
					var label = $button.find('.vp-woo-pont-order-column-print-button').data('alt-label');
					$button.find('.vp-woo-pont-order-column-print-button .label').text(label);
                    $table_row.find('.vp-woo-pont-order-column-label span').text(response.data.number);

                    //Update data attributes too, so if it triggered again int he same session, it will not generate a new label
                    order_details.parcel_number = response.data.number;
                    order_details.download_link = response.data.pdf;
                    $table_row.find('.vp-woo-pont-order-details').data('order-details', order_details);

                } else {
                    $row.addClass('has-error');
                    $row.find('input[type="checkbox"]').attr('disabled', true);
                }

                //Remove from needs label array
                vp_woo_pont_bulk_actions.needs_label.shift();

                //Run again to generate next label
                vp_woo_pont_bulk_actions.generate_label();

                //And update progress bar
                vp_woo_pont_bulk_actions.update_counter();

            });

        },
        update_counter: function() {
            vp_woo_pont_bulk_actions.labels_generated = vp_woo_pont_bulk_actions.total_labels - vp_woo_pont_bulk_actions.needs_label.length;
            var labels = $('.vp-woo-pont-modal-generate-progress-bar-text').data('labels');
            
            //Stop if cancelled
            if(!labels) return false;

            //Get total label
            var total_label = labels.total.singular;
            if(vp_woo_pont_bulk_actions.total_labels > 1) {
                total_label = labels.total.plural;
                total_label = total_label.replace('%d', vp_woo_pont_bulk_actions.total_labels);
            }

            //Get progress text
            var progress_label = labels.current.default;
            if(vp_woo_pont_bulk_actions.labels_generated == 1) progress_label = labels.current.singular;
            if(vp_woo_pont_bulk_actions.labels_generated > 1) {
                progress_label = labels.current.plural;
                progress_label = progress_label.replace('%d', vp_woo_pont_bulk_actions.labels_generated);
            }

            //Set width percentage
            var percentage = vp_woo_pont_bulk_actions.labels_generated / vp_woo_pont_bulk_actions.total_labels * 100;
            $('.vp-woo-pont-modal-generate-progress-bar-inner').css('width', percentage+'%');

            //Set text labels
            $('.vp-woo-pont-modal-generate-progress-bar-text-current').text(progress_label);
            $('.vp-woo-pont-modal-generate-progress-bar-text-total').text(total_label);

            //If we have no more labels to generate, show print button
            if(vp_woo_pont_bulk_actions.needs_label.length == 0) {
                setTimeout(function(){
                    $('.vp-woo-pont-modal-generate').addClass('done');
                }, 500);

                setTimeout(function(){
                    $('.vp-woo-pont-modal-generate').removeClass('done');
                    $('.vp-woo-pont-modal-generate').addClass('finished');
                }, 500);
            }

        },
        on_modal_close: function(_, modal_id) {
            if(modal_id == 'vp-woo-pont-modal-generate') {
                vp_woo_pont_bulk_actions.reset_modal_data();
            }
        },
        reset_modal_data: function() {
            var $table = $('.vp-woo-pont-modal-generate table');
            $table.find('tbody').html('');
            $('.vp-woo-pont-modal-generate').removeClass('done');
            $('.vp-woo-pont-modal-generate').removeClass('finished');
            vp_woo_pont_bulk_actions.needs_label = [];
            vp_woo_pont_bulk_actions.total_labels = 0;
            vp_woo_pont_bulk_actions.labels_generated = 0;
        },
        print_in_bulk: function() {

			//Generate print URL
			var pdf_url = vp_woo_pont_bulk_actions.generate_pdf_url();

            //Show loading indicator
            var $buttons = $(this).parents('footer');
            $buttons.block({
				message: null,
				overlayCSS: {
					background: '#fcfcfc url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			//Open print modal
			printJS({printable: pdf_url, onLoadingEnd: function(){

				//Hide loading indicator
				$buttons.unblock();

			}});

            return false;
        },
        download_in_bulk: function() {

			//Generate print URL
			var pdf_url = vp_woo_pont_bulk_actions.generate_pdf_url();

            //Download it
			window.open(pdf_url);

            return false;
        },
        generate_pdf_url: function() {
			var checkedOrders = $(".vp-woo-pont-modal-generate table tr.has-label input:checked");
			var orderIds = [];

            //Collect selected items
			$(checkedOrders).each(function(i) {
				var order_id = $(checkedOrders[i]).val();
				orderIds.push(order_id);
			});

            //Generate print URL
			var pdf_url = vp_woo_pont_params.print_url;
			var skip_section = 0;
			pdf_url = pdf_url.replace('X', orderIds.join());
			if($('#vp-woo-pont-bulk-print-position').val()) {
				skip_section = $('#vp-woo-pont-bulk-print-position').val();
			}
			pdf_url = pdf_url.replace('Y', skip_section);
    
            return pdf_url;
        },
        print_single_label: function() {
            var pdf_url = $(this).attr('href');
            printJS(pdf_url);
            return false;
        }
	}

	//Init bulk actions
	if($('.vp-woo-pont-order-column').length || $('.vp-woo-pont-dpd-start-sync').length || $('#vp-woo-pont-bulk-print-generate').length || vp_woo_pont_params.print_link) {
		vp_woo_pont_bulk_actions.init();
	}

	//Background generate actions
	var vp_woo_pont_background_actions = {
		$menu_bar_item: $('#wp-admin-bar-vp-woo-pont-bg-generate-loading'),
		$link_stop: $('#vp-woo-pont-bg-generate-stop'),
		$link_refresh: $('#vp-woo-pont-bg-generate-refresh'),
		finished: false,
		nonce: '',
		init: function() {
			this.$link_stop.on( 'click', this.stop );
			this.$link_refresh.on( 'click', this.reload_page );

			//Store nonce
			this.nonce = this.$link_stop.data('nonce');

			//Refresh status every 5 second
			var refresh_action = this.refresh;
			setTimeout(refresh_action, 5000);

		},
		reload_page: function() {
			location.reload();
			return false;
		},
		stop: function() {
			var data = {
				action: 'vp_woo_pont_bg_generate_stop',
				nonce: vp_woo_pont_background_actions.nonce,
			}

			$.post(ajaxurl, data, function(response) {
				vp_woo_pont_background_actions.mark_stopped();
			});
			return false;
		},
		refresh: function() {
			var data = {
				action: 'vp_woo_pont_bg_generate_status',
				nonce: vp_woo_pont_background_actions.nonce,
			}

			if(!vp_woo_pont_background_actions.finished) {
				$.post(ajaxurl, data, function(response) {
					if(response.data.finished) {
						vp_woo_pont_background_actions.mark_finished();
					} else {
						//Repeat after 5 seconds
						setTimeout(vp_woo_pont_background_actions.refresh, 5000);
					}

				});
			}
		},
		mark_finished: function() {
			this.finished = true;
			this.$menu_bar_item.addClass('finished');
		},
		mark_stopped: function() {
			this.mark_finished();
			this.$menu_bar_item.addClass('stopped');
		}
	}

	//Init background generate loading indicator
	if($('#wp-admin-bar-vp-woo-pont-bg-generate-loading').length) {
		vp_woo_pont_background_actions.init();
	}

	//Background generate actions
	var vp_woo_pont_shipments_actions = {
		$button: $('.vp_woo_pont_close_shipments'),
		$button_alt: $('.vp_woo_pont_close_orders'),
		$table: $('.vp-woo-pont-admin-shipments-table-pending'),
		$error: $('.vp-woo-pont-admin-shipments-notice'),
		$sampleRow: $('#tmpl-vp-woo-pont-shipment-result').html(),
		$results: $('.vp-woo-pont-admin-shipments-closed-packages'),
		provider: '',
		nonce: '',
		init: function() {

			//Store nonce
			this.nonce = this.$button.data('nonce');
			this.provider = this.$button.data('provider');

			//Refresh status every 5 second
			this.$button.on('click', this.close_shipments);
			this.$button_alt.on('click', this.close_orders);

			//Show all button
			$(document).on( 'click', '.vp-woo-pont-shipments-show-all', function() {
				$(this).parent().find('li.hidden').removeClass('hidden');
				$(this).hide();
				return false;
			});

			//Tooltips
			$( '#tiptip_holder' ).removeAttr( 'style' );
			$( '#tiptip_arrow' ).removeAttr( 'style' );
			$( '.tips' ).tipTip({ 'attribute': 'data-tip', 'fadeIn': 50, 'fadeOut': 50, 'delay': 50 });

		},
		reload_page: function() {
			location.reload();
			return false;
		},
		close_orders: function() {

			//Setup request data
			var data = {
				action: 'vp_woo_pont_close_orders',
				nonce: vp_woo_pont_shipments_actions.nonce,
				orders: [],
				provider: vp_woo_pont_shipments_actions.provider
			}

			//Get checked orders
			vp_woo_pont_shipments_actions.$table.find('input[name="selected_packages"]:checked').each(function(){
				var package_number = $(this).data('order');
				data.orders.push(package_number);
			});

			//Show loading indicator
			vp_woo_pont_shipments_actions.$table.block({
				message: null,
				overlayCSS: {
					background: '#F5F5F5 url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			//Make ajax request
			$.post(ajaxurl, data, function(response) {

				//Check for errors
				if(response.success) {
					vp_woo_pont_shipments_actions.reload_page();
				} else {
					vp_woo_pont_shipments_actions.$error.find('p').text(response.data.message);
					vp_woo_pont_shipments_actions.$error.show();
				}

				//Hide loading indicator
				vp_woo_pont_shipments_actions.$table.unblock();

			});
			return false;
		},
		close_shipments: function(action) {

			//Setup request data
			var data = {
				action: 'vp_woo_pont_close_shipments',
				nonce: vp_woo_pont_shipments_actions.nonce,
				packages: [],
				orders: [],
				provider: vp_woo_pont_shipments_actions.provider,
				shipments: []
			}

			//Get checked orders
			vp_woo_pont_shipments_actions.$table.find('input[name="selected_packages"]:checked').each(function(){
				var package_number = $(this).val();
				var order_number = $(this).data('order');
				data.packages.push(package_number);
				data.orders.push(order_number);
				data.shipments.push({
					order: order_number,
					package: package_number
				})
			});

			//Show loading indicator
			vp_woo_pont_shipments_actions.$table.block({
				message: null,
				overlayCSS: {
					background: '#F5F5F5 url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			//Make ajax request
			$.post(ajaxurl, data, function(response) {

				//Check for errors
				if(!response.data.error) {

					//Check for failed packages
					if(response.data.failed) {
						vp_woo_pont_shipments_actions.$error.find('p').text(response.data.message);
						vp_woo_pont_shipments_actions.$error.append('<p>'+response.data.failed.join(', ')+'</p>');
						vp_woo_pont_shipments_actions.$error.append('<p>Hibaüzenet:</p>');
						vp_woo_pont_shipments_actions.$error.append('<p>'+JSON.stringify(response.data.errors)+'</p>');
						vp_woo_pont_shipments_actions.$error.show();
					}

					//Hide processed rows
					for (const [key, order] of Object.entries(response.data.processed)) {
						vp_woo_pont_shipments_actions.$table.find('input[data-order="'+order+'"]').parents('tr').remove();
					}

					//If we don't have any rows left, hide the whole table
					if(vp_woo_pont_shipments_actions.$table.find('tbody tr').length == 0) {
						$('.vp-woo-pont-admin-shipments-pending-container').hide();
					}

					//Create a new table row
					var $row = $(vp_woo_pont_shipments_actions.$sampleRow);

					//Set values
					$row.find('.column-id').text(response.data.shipment_id);
					$row.find('.column-time').text(new Date().toLocaleString());

					var $orders_link = $('<a href="#">').text(Object.entries(response.data.processed).length + ' rendelés');
					var download_path =response.data.download_path;
					$row.find('.column-orders').html($orders_link);

					var $sample_link = $row.find('.vp-woo-pont-admin-shipments-download-link').clone();
					$row.find('.column-pdf').html('');
					for (var key in response.data.documents) {
						if (response.data.documents.hasOwnProperty(key)) {
							var $download_link = $sample_link.clone();
							$download_link.find('a').attr('href', download_path+response.data.documents[key]);
							$download_link.find('.vp-woo-pont-provider-icon').addClass('vp-woo-pont-provider-icon-'+key)
							$row.find('.column-pdf').append($download_link);
						}
					}
					
					//Append row
					vp_woo_pont_shipments_actions.$results.find('tbody').prepend($row);

					//Show table
					vp_woo_pont_shipments_actions.$results.show();

					//Update counters
					//vp_woo_pont_shipments_actions.update_counters();

				}

				//Check if we have some errors ir failed packages
				if(response.data.error) {

					//Show error
					vp_woo_pont_shipments_actions.$error.html('<p>');
					vp_woo_pont_shipments_actions.$error.find('p').text(response.data.message);
					if(response.data.errors) {
						vp_woo_pont_shipments_actions.$error.append('<p>Hibaüzenet:</p>');
						vp_woo_pont_shipments_actions.$error.append('<p>'+JSON.stringify(response.data.errors)+'</p>');
					}
					vp_woo_pont_shipments_actions.$error.show();

					//Scroll to top
					$('html, body').animate({
						scrollTop: 0
					}, 500);

				}

				//Hide loading indicator
				vp_woo_pont_shipments_actions.$table.unblock();

			});
			return false;
		},
	}

	//Init background generate loading indicator
	if($('.vp_woo_pont_close_shipments').length || $('.vp-woo-pont-admin-shipments-table').length) {
		vp_woo_pont_shipments_actions.init();
	}

	//Metabox functions
	var vp_woo_pont_metabox = {
		prefix: 'vp_woo_pont_',
		prefix_id: '#vp_woo_pont_',
		prefix_class: '.vp-woo-pont-',
		selected_provider: '',
		$metaboxContent: $('#vp_woo_pont_metabox .inside'),
		$optionsContent: $('.vp-woo-pont-metabox-generate-options'),
		$generateContent: $('.vp-woo-pont-metabox-generate'),
		$optionsButton: $('#vp_woo_pont_label_options'),
		$generateButtonLabel: $('#vp_woo_pont_label_generate'),
		$pointRow: $('.vp-woo-pont-metabox-rows-data-provider'),
		$labelRow: $('.vp-woo-pont-metabox-rows-label'),
		$trackingRow: $('.vp-woo-pont-metabox-rows-link-tracking'),
		$parcelCountRow: $('.vp-woo-pont-metabox-rows-parcel-count'),
		$voidRow: $('.vp-woo-pont-metabox-rows-data-void'),
		$removeRow: $('.vp-woo-pont-metabox-rows-data-remove'),
		$replaceRow: $('.vp-woo-pont-metabox-rows-data-replace'),
		$messages: $('.vp-woo-pont-metabox-messages-label'),
		$providerRow: $('.vp-woo-pont-metabox-rows-data-provider'),
		$homeDeliveryProviders: $('.vp-woo-pont-metabox-rows-data-home-delivery-providers'),
		$modifyProviderButton: $('#vp_woo_pont_modify_provider'),
		$homeDeliveryProviderInput: $('input[name="home_delivery_provider"]'),
		nonce: $('#vp_woo_pont_metabox .vp-woo-pont-metabox-content').data('nonce'),
		order: $('#vp_woo_pont_metabox .vp-woo-pont-metabox-content').data('order'),
		$updateTrackingInfoButton: $('#vp_woo_pont_update_tracking_info'),
		$trackingInfoList: $('#vp_woo_pont_tracking_info_list'),
		$trackingMessages: $('.vp-woo-pont-metabox-messages-tracking'),
		$weightField: $('#vp_woo_pont_package_weight'),
		$shipmentRow: $('.vp-woo-pont-metabox-rows-data-shipment'),
		$transspedPackages: $('.vp-woo-pont-transsped-packaging'),
		selected_replacement: false,
		init: function() {
			this.$optionsButton.on( 'click', this.show_options );
			this.$generateButtonLabel.on( 'click', this.generate_label );
			this.$removeRow.find('a').on( 'click', this.remove_point );
			this.$replaceRow.find('a').on( 'click', this.replace_point );
			this.$voidRow.find('a').on( 'click', this.void_label );
			this.$messages.find('a').on( 'click', this.hide_message );
			this.$trackingMessages.find('a').on( 'click', this.hide_message );
			this.$modifyProviderButton.on( 'click', this.show_provider_options );
			this.$homeDeliveryProviderInput.on( 'change', this.on_provider_change );
			this.$updateTrackingInfoButton.on( 'click', this.update_tracking_info );
			this.$shipmentRow.find('a.undo').on( 'click', this.undo_shipment );

			//Handling the replace point modal
			$(document).on( 'change', '.vp-woo-pont-modal-replace input[name="replacement_point_provider"]', this.on_replacement_provider_change);
			$(document).on( 'keyup', '.vp-woo-pont-modal-replace #vp-woo-pont-modal-replace-search', this.on_replacement_search);
			$(document).on( 'click', '.vp-woo-pont-modal-replace-results li.result', this.save_replacement_point );

			//Set provider id
			this.selected_provider = $('#vp_woo_pont_metabox .vp-woo-pont-metabox-content').data('provider_id');
			vp_woo_pont_metabox.toggle_options();

			//Show options by default
			if(vp_woo_pont_params.show_settings_metabox) {
				vp_woo_pont_metabox.show_options();
			}

			//Save weight on enter
			this.$weightField.keypress(function(e) {
				switch (e.keyCode) {
					case 13:
						vp_woo_pont_metabox.save_weight();
						return false;
					default:
						return true;
				}
			});

			//Show print position options
			this.generate_label_print_layout();
			$(document).on('click', '.vp-woo-pont-metabox-rows-label-print div', this.print_label);

			//Setup transsped packagings options
			this.$transspedPackages.find('.qty a').on('click', this.transsped.qty_change);
			this.transsped.set_global_qty();

			//Setup packaging options
			if($('.vp-woo-pont-package-size').length) {
				this.packaging.init();
			}

		},
		packaging: {
			init: function() {

				//Show by default
				if($('#vp_woo_pont_packaging_type_custom').is(':checked')) {
					$('.vp-woo-pont-package-size-custom').show();
				}

				//Handle package size change
				$(document).on( 'change', '.vp-woo-pont-package-size input[name="vp_woo_pont_packaging_type"]', this.on_change);

				//Handle custom size change
				$(document).on( 'blur', '.vp-woo-pont-package-size-custom input', this.on_custom_size_change);

			},
			toggle_custom: function(packagingType) {
				if(packagingType == 'custom') {
					$('.vp-woo-pont-package-size-custom').show();
				} else {
					$('.vp-woo-pont-package-size-custom').hide();
				}
			},
			on_change: function() {
				var packagingType = $(this).val();

				//Hide custom packaging
				vp_woo_pont_metabox.packaging.toggle_custom(packagingType);
				
				//If its not custom, save it
				if(packagingType != 'custom') {
					vp_woo_pont_metabox.packaging.update_packaging_details();
				} else {
					vp_woo_pont_metabox.packaging.on_custom_size_change();
				}

			},
			on_custom_size_change: function() {
				var $container = $('.vp-woo-pont-package-size-custom');
				var length = $container.find('input[name="vp_woo_pont_packaging_length"]').val();
				var width = $container.find('input[name="vp_woo_pont_packaging_width"]').val();
				var height = $container.find('input[name="vp_woo_pont_packaging_height"]').val();

				if(length && width && height) {
					vp_woo_pont_metabox.packaging.update_packaging_details();
				}
			},
			update_packaging_details: function() {

				//Show loading indicator
				var $container = $('.vp-woo-pont-package-size');
				vp_woo_pont_metabox.loading_indicator($container, '#fff');
				
				//Get selected packaging type
				var $selectedPackaging = $container.find('input[name="vp_woo_pont_packaging_type"]:checked');

				//Get data
				var data = {
					action: vp_woo_pont_metabox.prefix+'update_package_details',
					nonce: vp_woo_pont_params.nonces.generate,
					order: vp_woo_pont_metabox.order,
					packaging_name: $selectedPackaging.data('name'),
					packaging_sku: $selectedPackaging.val(),

					//Custom packaging
					packaging_length: $container.find('input[name="vp_woo_pont_packaging_length"]').val(),
					packaging_width: $container.find('input[name="vp_woo_pont_packaging_width"]').val(),
					packaging_height: $container.find('input[name="vp_woo_pont_packaging_height"]').val(),
				};

				//Make request
				$.post(ajaxurl, data, function(response) {

					//Hide loading indicator
					$container.unblock();

				});
			}
		},
		transsped: {
			qty_change: function() {
				var $input = $(this).parent().find('input');
				var value = parseInt($input.val());
				var $text = $(this).parent().find('.value');

				if($(this).hasClass('minus')) {
					value = value - 1;
				} else {
					value = value + 1;
				}

				if(value < 0) {
					value = 0;
				}

				$input.val(value);
				vp_woo_pont_metabox.transsped.set_global_qty();

				return false;
			},
			set_global_qty: function() {
				var $indicator = vp_woo_pont_metabox.$transspedPackages.find('.total-qty');
				var qty = 0;

				vp_woo_pont_metabox.$transspedPackages.find('li').each(function(){
					var $input = $(this).find('input');
					var $text = $(this).find('.value');
					var value = parseInt($input.val());
					$text.text(value);
					qty += value;

					if(value > 0) {
						$(this).addClass('active');
					} else {
						$(this).removeClass('active');
					}
				});

				$indicator.text(qty);
			}
		},
		loading_indicator: function(button, color) {
			vp_woo_pont_metabox.hide_message();
			button.block({
				message: null,
				overlayCSS: {
					background: color+' url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});
		},
		save_weight: function() {
			var data = {
				action: vp_woo_pont_metabox.prefix+'update_package_details',
				nonce: vp_woo_pont_params.nonces.generate,
				order: vp_woo_pont_metabox.order,
				weight: vp_woo_pont_metabox.$weightField.val(),
			};

			//Show checkmark
			vp_woo_pont_metabox.$weightField.parent().addClass('saved');
			setTimeout(function(){
				vp_woo_pont_metabox.$weightField.parent().removeClass('saved');
			}, 1500);

			//Make request
			$.post(ajaxurl, data, function(response) {

			});
		},
		show_options: function() {
			vp_woo_pont_metabox.$optionsButton.toggleClass('active');
			vp_woo_pont_metabox.$optionsContent.slideToggle();
			return false;
		},
		toggle_options: function() {
			vp_woo_pont_metabox.$optionsContent.find('.vp-woo-pont-metabox-generate-options-item').each(function(){
				var $item = $(this);
				var supported_providers = $item.data('providers');
				var selected_provider = vp_woo_pont_metabox.selected_provider;
				
				//Show all
				$item.show();
				if(supported_providers) {
					$item.addClass('selected');
				}

				//Hide if not supported
				if(supported_providers && !supported_providers.includes(selected_provider)) {
					$item.hide();
					$item.removeClass('selected');
				}

			});
		},
		generate_label: function() {
			var $this = $(this);

			//Set custom options
			var package_count = $('#vp_woo_pont_package_count').val();
			var pickup_date = $('#vp_woo_pont_pickup_date').val();
			var package_contents = $('#vp_woo_pont_package_contents').val();
			var package_weight = $('#vp_woo_pont_package_weight').val();
			var extra_services = $('.selected input[name="vp_woo_pont_extra_services"]:checked').map(function () {
				return this.value;
			}).get();
			var transsped_packaging = {};

			vp_woo_pont_metabox.$transspedPackages.find('li').each(function(){
				var $input = $(this).find('input');
				var package_type = $input.attr('name').match(/\[(.*?)\]/)[1];
				var value = parseInt($input.val());
				if(value > 0) {
					transsped_packaging[package_type] = value;
				}
			});

			//Create request
			var data = {
				action: vp_woo_pont_metabox.prefix+'generate_label',
				nonce: vp_woo_pont_metabox.nonce,
				order: vp_woo_pont_metabox.order,
				package_count: package_count,
				pickup_date: pickup_date,
				package_contents: package_contents,
				package_weight: package_weight,
				extra_services: extra_services,
				transsped_packaging: transsped_packaging,
				source: 'metabox'
			};

			//If a custom provider is set
			if($('input[name="home_delivery_provider"]:checked').val()) {
				data.provider = $('input[name="home_delivery_provider"]:checked').val();
			}

			//Show loading indicator
			vp_woo_pont_metabox.loading_indicator(vp_woo_pont_metabox.$metaboxContent, '#fff');

			//Make request
			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				vp_woo_pont_metabox.$metaboxContent.unblock();

				//Show success/error messages
				vp_woo_pont_metabox.show_messages(response);

				//On success and error
				if(!response.data.error) {
					vp_woo_pont_metabox.$labelRow.slideDown();
					vp_woo_pont_metabox.$labelRow.find()
					vp_woo_pont_metabox.$labelRow.find('a').attr('href', response.data.pdf);
					vp_woo_pont_metabox.$trackingRow.find('strong').text(response.data.number);
					vp_woo_pont_metabox.$generateContent.slideUp();

					if(data.package_count > 1) {
						vp_woo_pont_metabox.$parcelCountRow.slideDown();
						var qty = vp_woo_pont_metabox.$parcelCountRow.find('strong').data('qty');
						vp_woo_pont_metabox.$parcelCountRow.find('strong').text(data.package_count+' '+qty);
					}

					//Regenerate print layout
					vp_woo_pont_metabox.generate_label_print_layout();

					if(response.data.pending) {
						vp_woo_pont_metabox.$labelRow.addClass('pending');
						vp_woo_pont_metabox.add_to_heartbeat('label');
					} else {
						vp_woo_pont_metabox.$trackingRow.slideDown();
						vp_woo_pont_metabox.$voidRow.slideDown();
					}

				}

			});

			return false;
		},
		void_label_timeout: false,
		void_label: function() {
			var $this = $(this);

			//Do nothing if already marked completed
			if($this.hasClass('confirm')) {

				//Reset timeout
				clearTimeout(vp_woo_pont_metabox.void_label_timeout);

				//Show loading indicator
				vp_woo_pont_metabox.loading_indicator(vp_woo_pont_metabox.$voidRow, '#fff');

				//Create request
				var data = {
					action: vp_woo_pont_metabox.prefix+'void_label',
					nonce: vp_woo_pont_metabox.nonce,
					order: vp_woo_pont_metabox.order,
				};

				$.post(ajaxurl, data, function(response) {

					//Hide loading indicator
					vp_woo_pont_metabox.$voidRow.unblock();

					//Show success/error messages
					vp_woo_pont_metabox.show_messages(response);

					//On success and error
					if(!response.data.error) {
						vp_woo_pont_metabox.$labelRow.slideUp();
						vp_woo_pont_metabox.$trackingRow.slideUp();
						vp_woo_pont_metabox.$parcelCountRow.slideUp();
						vp_woo_pont_metabox.$voidRow.slideUp(function(){
							$this.text(response.data.completed);
							$this.removeClass('confirm');
						});

						$('#vp_woo_pont_metabox_tracking').slideUp();
						vp_woo_pont_metabox.$generateContent.slideDown();
						$('#vp_woo_pont_metabox_kvikk').slideUp();

					}

					//On success and error
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});

				});

			} else {
				vp_woo_pont_metabox.void_invoice_timeout = setTimeout(function(){
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});
				}, 5000);

				$this.addClass('confirm');
				$this.fadeOut(function(){
					$this.text($this.data('question'))
					$this.fadeIn();
				});
			}

			return false;
		},
		remove_point_timeout: false,
		remove_point: function() {
			var $this = $(this);

			//Do nothing if already marked completed
			if($this.hasClass('confirm')) {

				//Reset timeout
				clearTimeout(vp_woo_pont_metabox.remove_point_timeout);

				//Show loading indicator
				vp_woo_pont_metabox.loading_indicator(vp_woo_pont_metabox.$removeRow, '#fff');

				//Create request
				var data = {
					action: vp_woo_pont_metabox.prefix+'remove_point',
					nonce: vp_woo_pont_metabox.nonce,
					order: vp_woo_pont_metabox.order,
				};

				$.post(ajaxurl, data, function(response) {

					//Hide loading indicator
					vp_woo_pont_metabox.$removeRow.unblock();

					//Show success/error messages
					//vp_woo_pont_metabox.show_messages(response);

					//On success and error
					if(response.success) {
						vp_woo_pont_metabox.$pointRow.slideUp();
						vp_woo_pont_metabox.$removeRow.slideUp(function(){
							$this.removeClass('confirm');
						});
					}

					//On success and error
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});

				});

			} else {
				vp_woo_pont_metabox.void_invoice_timeout = setTimeout(function(){
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});
				}, 5000);

				$this.addClass('confirm');
				$this.fadeOut(function(){
					$this.text($this.data('question'))
					$this.fadeIn();
				});
			}

			return false;
		},
		show_messages: function(response) {
			var $messages = this.$messages;
			if(response.container) {
				$messages = response.container;
			}

			if(response.data.messages && response.data.messages.length > 0) {
				$messages.removeClass('vp-woo-pont-metabox-messages-success');
				$messages.removeClass('vp-woo-pont-metabox-messages-error');

				if(response.data.error) {
					$messages.addClass('vp-woo-pont-metabox-messages-error');
				} else {
					$messages.addClass('vp-woo-pont-metabox-messages-success');
				}

				$ul = $messages.find('ul');
				$ul.html('');

				$.each(response.data.messages, function(i, value) {
					var li = $('<li>')
					li.append(value);
					$ul.append(li);
				});
				$messages.slideDown();
			}
		},
		hide_message: function() {
			vp_woo_pont_metabox.$messages.slideUp();
			vp_woo_pont_metabox.$trackingMessages.slideUp();
			return false;
		},
		on_replacement_provider_change: function() {
			$('.vp-woo-pont-modal-replace-providers li').removeClass('selected');
			$(this).parents('li').addClass('selected');
			$('.vp-woo-pont-modal-replace-options li.category').removeClass('selected');
			var provider_id = $(this).val();
			if(!$(this).parents('li').hasClass('loaded')) {
				vp_woo_pont_metabox.load_replacement_points(provider_id);
				$(this).parents('li').addClass('loaded');
			} else {
				$('.vp-woo-pont-modal-replace-options li.category#'+provider_id).addClass('selected');
			}
			$('#vp-woo-pont-modal-replace-search').trigger('keyup');
		},
		load_replacement_points: function(provider_id) {
			vp_woo_pont_settings.json_data_points.forEach(function(data_points){
				if(data_points.provider == provider_id && !$('.vp-woo-pont-modal-replace-results li#'+provider_id).length) {
					var providerLi = $('<li>').attr('id', data_points.provider);
					providerLi.addClass('category');
					providerLi.addClass('selected');
					var dataUl = $('<ul>');
					data_points.data.forEach(function(data) {
						var nameStrong = $('<strong>').text(data.name);
			      var addressSpan = $('<span>').text(data.zip + ' ' + data.city + ' ' + data.addr);
			      var dataLi = $('<li>').addClass('result').data('point_id', data.id).data('provider', provider_id).append(nameStrong, addressSpan);
						dataUl.append(dataLi);
					});
					providerLi.append(dataUl);
					$('.vp-woo-pont-modal-replace-results').append(providerLi);
				}
			});
		},
		replace_point: function() {
			$(this).WCBackboneModal({
				template: 'vp-woo-pont-modal-replace',
				variable : {}
			});

			//Preselect current provider
			var provider_id = $(this).data('provider_id');
			var $selected = $('.vp-woo-pont-modal-replace-providers').find('input[value="'+provider_id+'"]');
			if(!$selected.length) {
				$selected = $('.vp-woo-pont-modal-replace-providers li:first input');
			}
			$selected.prop('checked', true);
			$selected.parents('li').addClass('selected');

			//Get enabled providers
			var enabled_providers = $('.vp-woo-pont-modal-replace-providers input').map(function() {
				return this.value;
			}).get();

			//Load JSON files
			vp_woo_pont_settings.load_json_files(function(){

				//Load selected values
				$selected.trigger('change');

			});

			return false;

		},
		on_replacement_search: function() {
			var searchText = $(this).val().toLowerCase();
			var listItems = $('.vp-woo-pont-modal-replace-results li.selected li');
			listItems.each(function() {
				var itemText = $(this).text().toLowerCase();
				if (itemText.indexOf(searchText) !== -1) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		},
		save_replacement_point: function() {
			var $modal = $('.vp-woo-pont-modal-replace');

			//Show loading indicator
			vp_woo_pont_metabox.loading_indicator($modal, '#fff');

			//Make AJAX request to replace point data
			var data = {
				action: vp_woo_pont_metabox.prefix+'replace_point',
				nonce: vp_woo_pont_metabox.nonce,
				order: vp_woo_pont_metabox.order,
				provider: $(this).data('provider'),
				point_id: $(this).data('point_id')
			};

			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				$modal.unblock();

				//Hide modal
				$('.modal-close-link').trigger('click');

				//Show success/error messages
				vp_woo_pont_metabox.show_messages(response);

				//On success and error
				if(response.success) {
					vp_woo_pont_metabox.$pointRow.slideDown();
					vp_woo_pont_metabox.$pointRow.find('strong').text(response.data.provider_label);
					vp_woo_pont_metabox.$pointRow.find('span').text(response.data.point_name);
					vp_woo_pont_metabox.$pointRow.find('i').removeAttr('class').addClass('vp-woo-pont-provider-icon-'+response.data.provider);

					//Set selected provider id
					vp_woo_pont_metabox.selected_provider = response.data.carrier;
					vp_woo_pont_metabox.toggle_options();
					vp_woo_pont_metabox.$replaceRow.find('a').data('provider_id', response.data.provider);

					//Trigger event
					$( document.body ).trigger( 'vp_woo_pont_metabox_pickup_point_changed' );

				}

			});

			return false;
		},
		add_to_heartbeat: function() {
			$( document ).on( 'heartbeat-send', function ( event, data ) {
				data.vp_woo_pont_label_generate = true;
				data.vp_woo_pont_order_id = vp_woo_pont_metabox.order;
			});
		},
		show_provider_options: function() {
			//Hide the currently selected provider and show a list instead of available options
			$('.vp-woo-pont-metabox-rows-data-home-delivery-providers-info').removeClass('show');
			vp_woo_pont_metabox.$providerRow.slideUp();
			vp_woo_pont_metabox.$homeDeliveryProviders.slideDown();
			return false;
		},
		on_provider_change: function() {

			//Get selected value
			var label = $('input[name="home_delivery_provider"]:checked').data('label');
			var id = $('input[name="home_delivery_provider"]:checked').val();

			//Save it with ajax
			var data = {
				action: vp_woo_pont_metabox.prefix+'save_provider',
				nonce: vp_woo_pont_metabox.nonce,
				order: vp_woo_pont_metabox.order,
				provider: id
			};

			//Set selected provider id
			vp_woo_pont_metabox.selected_provider = id;
			vp_woo_pont_metabox.toggle_options();

			//Not that important, so just do it in the background without any loading indicator
			$.post(ajaxurl, data);

			//Replace selected provider and show just that
			vp_woo_pont_metabox.$providerRow.find('strong').text(label);
			vp_woo_pont_metabox.$providerRow.find('i').attr('class', 'vp-woo-pont-provider-icon-'+id);
			vp_woo_pont_metabox.$providerRow.slideDown();
			vp_woo_pont_metabox.$homeDeliveryProviders.slideUp();
			vp_woo_pont_metabox.$generateContent.addClass('show');

		},
		update_tracking_info: function() {
			var $this = $(this);

			//Show loading indicator
			vp_woo_pont_metabox.loading_indicator(vp_woo_pont_metabox.$trackingInfoList, '#fff');

			//Create request
			var data = {
				action: vp_woo_pont_metabox.prefix+'update_tracking_info',
				nonce: vp_woo_pont_params.nonces.tracking,
				order: vp_woo_pont_metabox.order,
			};

			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				vp_woo_pont_metabox.$trackingInfoList.unblock();

				//Show success/error messages
				response.container = vp_woo_pont_metabox.$trackingMessages;
				vp_woo_pont_metabox.show_messages(response);

				//On success
				if(!response.data.error) {

					//If we have a new item, append it
					if(response.data.tracking_info.length > vp_woo_pont_metabox.$trackingInfoList.find('li').length-1) {

						//Show the latest item
						var latest = response.data.tracking_info[0];

						//Create new item
						var $event = vp_woo_pont_metabox.$trackingInfoList.find('.note-sample').clone();
						$event.removeClass('note-sample');
						$event.find('.note_content p').text(latest.label);
						$event.find('.exact-date').text(latest.date);
						$event.show();

						setTimeout(function() {
							$event.removeClass('customer-note');
						}, 3000);

						//Prepend to list
						vp_woo_pont_metabox.$trackingInfoList.prepend($event);

					}
				}

			});

			return false;
		},
		generate_label_print_layout: function() {

			//Get sticker details
			var provider_id = vp_woo_pont_metabox.selected_provider;
			if(provider_id.includes('posta')) provider_id = 'posta';
			var sticker_parameters = vp_woo_pont_params.sticker_parameters[provider_id]

			if(sticker_parameters && sticker_parameters.format) {

				//Get paper and reset it
				var $paper = $('.vp-woo-pont-metabox-rows-label-print');
				$paper.html('');
				$paper.addClass(sticker_parameters.format);
				$paper.attr('data-sections', sticker_parameters.sections);
				$paper.attr('data-layout', sticker_parameters.layout);
				$paper.attr('data-format', sticker_parameters.format);

				//Create stickers on paper
				for (let i of Array(sticker_parameters.sections).keys()) {
					var $sticker = $('<div>');
					$sticker.data('page', i+1);
					if(i == 0) {
						$sticker.addClass('selected');
					}
					$paper.append($sticker);
				}

			}

		},
		print_label: function() {
			var index = $(this).data('page');
			$('.vp-woo-pont-metabox-rows-label-print div').removeClass('selected');
			$(this).addClass('selected');

			var pdf_url = vp_woo_pont_params.print_url;
			pdf_url = pdf_url.replace('X', vp_woo_pont_metabox.order);
			pdf_url = pdf_url.replace('Y', index);

			//Show loading indicator
			vp_woo_pont_metabox.loading_indicator(vp_woo_pont_metabox.$labelRow, '#fff');

			//Open print modal
			printJS({printable: pdf_url, onLoadingEnd: function(){

				//Hide loading indicator
				vp_woo_pont_metabox.$labelRow.unblock();

			}});

			return false;
		},
		undo_shipment_timeout: false,
		undo_shipment: function() {
			var $this = $(this);

			//Do nothing if already marked completed
			if($this.hasClass('confirm')) {

				//Reset timeout
				clearTimeout(vp_woo_pont_metabox.undo_shipment_timeout);

				//Show loading indicator
				vp_woo_pont_metabox.loading_indicator(vp_woo_pont_metabox.$shipmentRow, '#fff');

				//Create request
				var data = {
					action: vp_woo_pont_metabox.prefix+'undo_shipment',
					nonce: vp_woo_pont_metabox.nonce,
					order: vp_woo_pont_metabox.order,
				};

				$.post(ajaxurl, data, function(response) {

					//Hide loading indicator
					vp_woo_pont_metabox.$shipmentRow.unblock();

					//Show success/error messages
					vp_woo_pont_metabox.show_messages(response);

					//On success and error
					if(!response.data.error) {
						vp_woo_pont_metabox.$shipmentRow.slideUp();
					}

					//On success and error
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});

				});

			} else {
				vp_woo_pont_metabox.undo_shipment_timeout = setTimeout(function(){
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});
				}, 5000);

				$this.addClass('confirm');
				$this.fadeOut(function(){
					$this.text($this.data('question'))
					$this.fadeIn();
				});
			}

			return false;
		},
	}

	//Metabox
	if($('#vp_woo_pont_metabox').length) {
		vp_woo_pont_metabox.init();
	}

	var vp_woo_pont_customizer = {
		$map: false,
		$filters: $('.vp-woo-pont-modal-sidebar-filters'),
		$list: $('.vp-woo-pont-modal-sidebar-results'),
		saved_values: false,
		sample_providers: [
			{
				'id': 'foxpost',
				'label': 'Foxpost',
			},
			{
				'id': 'gls',
				'label': 'GLS',
			},
			{
				'id': 'mpl',
				'label': 'Postapont',
			}
		],
		sample_points: [
			{"provider":"foxpost","id":1,"lat":"47.94137","lon":"21.71191","name":"Nyíregyháza ALDI Móricz Zsigmond utca","zip":"4400","addr":"Móricz Zsigmond utca 25.","city":"Nyíregyháza"},
			{"provider":"gls","id":2,"lat":"47.58137","lon":"19.04877","zip":"1039","addr":"Szentendrei út 255.","city":"BUDAPEST III. KER.","country":"hu","name":"GLS Automata MOL Szentendrei út","hours":"00:00 - 24:00","comment":"HU460 számú kültéri automatánk az ALDI bejáratától jobbra, a Szarvas utca felől található."},
			{"provider":"mpl","id":3,"group":10,"lat":"46.59523","lon":"17.17556","name":"Balatonmagyaród postapartner","zip":"8753","addr":"Petőfi utca 135.","city":"Balatonmagyaród"},
			{"provider":"mpl","id":4,"group":10,"lat":"46.91638","lon":"19.91863","name":"Szentkirály postapartner","zip":"6031","addr":"Kossuth Lajos utca 25/C.","city":"Szentkirály"},
			{"provider":"foxpost","id":5,"lat":"46.07529","lon":"18.24244","name":"Pécs Mixvill Zsolnay utca","zip":"7630","addr":"Zsolnay utca 8.","city":"Pécs"}
		],
		init: function() {

			$(document).on('click', '.vp-woo-pont-appearance-editor', function () {
				$(this).WCBackboneModal({
					template: 'vp-woo-pont-modal-design'
				});

				vp_woo_pont_customizer.generateSampleMap();
				vp_woo_pont_customizer.update_inline_css();
				return false;
			});

			$(document).on('change', '.vp-woo-pont-modal-design-form input', this.update_inline_css);
			$(document).on('input', '.vp-woo-pont-modal-design-form input', this.update_inline_css);
			$(document).on('click', '.vp-woo-pont-modal-design-save', this.save_design);
			$(document).on('click', '.vp-woo-pont-modal-design-reset', this.reset_design);

		},
		generateSampleMap: function() {

			//Create a map
			vp_woo_pont_customizer.$map = L.map('vp-woo-pont-modal-map')

			//Just to center in Hungary
			vp_woo_pont_customizer.$map.setView([47.25525656277509, 19.54590752720833], 7); 
			vp_woo_pont_customizer.$map.zoomControl.setPosition('bottomright');

			//Load images into map
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
			}).addTo(vp_woo_pont_customizer.$map);

			//Generate sample filters
			$('.vp-woo-pont-modal-sidebar-filters').html('');
			vp_woo_pont_customizer.sample_providers.forEach(function(provider){
				console.log(provider);
				var item = $('<li data-provider="'+provider.id+'"><input type="checkbox" checked id="provider-'+provider.id+'"><label for="provider-'+provider.id+'"><i class="vp-woo-pont-provider-icon-'+provider.id+'"></i><strong>'+provider.label+'</strong><em>990 Ft</em></label></li>');
				$('.vp-woo-pont-modal-sidebar-filters').append(item);
			});

			//Generate sample points
			vp_woo_pont_customizer.sample_points.forEach(function(point){
				var marker = L.marker([point.lat, point.lon]);
				var icon_class_name = point.provider;
				if(point.provider == 'gls') {
					icon_class_name += ' selected';
				}
				var icon = L.divIcon({html: '<div><i class="vp-woo-pont-provider-icon-'+point.provider+'"></i></div>', className: 'vp-woo-pont-marker '+icon_class_name, iconSize: [48, 55], iconAnchor: [24, 52]});
				marker.setIcon(icon);
				marker.addTo(vp_woo_pont_customizer.$map);

			});

			//Generate sample list in sidebar
			vp_woo_pont_customizer.sample_points.forEach(function(point){
				var list_item = $('#vp-woo-pont-modal-list-item-sample').clone();
				list_item.removeAttr('id');
				list_item.find('.name').text(point.name);
				list_item.find('.addr').text(point.addr+', '+point.zip+' '+point.city);
				list_item.find('.cost').html('990 Ft');
				list_item.find('.comment').text(point.comment);
				list_item.attr('data-provider', point.provider);
				list_item.attr('data-id', point.id);
				list_item.find('.icon').addClass('vp-woo-pont-provider-icon-'+point.provider);

				//Setup opening hours
				if(point.hours) {
					list_item.find('.open-hours .value').text(point.hours);
					list_item.find('.open-hours').addClass('has-hours');
				}

				//Check if need to be selected
				if(point.provider == 'gls') {
					list_item.addClass('selected');
				}

				//If its the selected one, move that to the top of the list
				$('.vp-woo-pont-modal-sidebar-results').append(list_item);
			});

			//Update input fields
			if(vp_woo_pont_customizer.saved_values) {
				$('.vp-woo-pont-modal-design-form input').each(function(){
					var name = $(this).attr('name');
					name = name.replace('vp_woo_pont_', '');
					if($(this).attr('type') == 'checkbox') {
						if(vp_woo_pont_customizer.saved_values[name]) {
							$(this).prop('checked', true);
						} else {
							$(this).prop('checked', false);
						}
					} else {
						if(vp_woo_pont_customizer.saved_values[name]) {
							$(this).val(vp_woo_pont_customizer.saved_values[name]);
						}
					}
				});
			}

		},
		update_inline_css: function() {

			//Get all values
			var values = {};
			var hex_to_rgba = vp_woo_pont_customizer.hex_to_rgba;
			$('.vp-woo-pont-modal-design-form input').each(function(){
				var name = $(this).attr('name');
				name = name.replace('vp_woo_pont_', '');
				if($(this).attr('type') == 'checkbox') {
					values[name] = $(this).is(':checked');
				} else {
					values[name] = $(this).val();
				}
			});

			//Change colors
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color', values.primary_color);
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color-alpha-20', hex_to_rgba(values.primary_color, 0.2));
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color-alpha-10', hex_to_rgba(values.primary_color, 0.1));
			document.documentElement.style.setProperty('--vp-woo-pont-primary-color-alpha-05', hex_to_rgba(values.primary_color, 0.05));
			document.documentElement.style.setProperty('--vp-woo-pont-text-color', values.text_color);
			document.documentElement.style.setProperty('--vp-woo-pont-price-color', values.price_color);
			document.documentElement.style.setProperty('--vp-woo-pont-cluster-large-color', hex_to_rgba(values.cluster_large_color, 0.9));
			document.documentElement.style.setProperty('--vp-woo-pont-cluster-medium-color', hex_to_rgba(values.cluster_medium_color, 0.9));
			document.documentElement.style.setProperty('--vp-woo-pont-cluster-small-color', hex_to_rgba(values.cluster_small_color, 0.9));
			document.documentElement.style.setProperty('--vp-woo-pont-title-font-size', values.title_font_size+'px');
			document.documentElement.style.setProperty('--vp-woo-pont-text-font-size', values.text_font_size+'px');
			document.documentElement.style.setProperty('--vp-woo-pont-price-font-size', values.price_font_size+'px');

			//Toggle couple of classes
			$('.vp-woo-pont-modal-sidebar-results .open-hours').toggleClass('has-hours', values.show_open_hours);
			$('.vp-woo-pont-modal-sidebar-filters').toggleClass('show-checkbox', values.filter_checkbox);

		},
		hex_to_rgba: function(hex, opacity) {
			return 'rgba(' + (hex = hex.replace('#', '')).match(new RegExp('(.{' + hex.length/3 + '})', 'g')).map(function(l) { return parseInt(hex.length%2 ? l+l : l, 16) }).concat(isFinite(opacity) ? opacity : 1).join(',') + ')';
		},
		save_design: function() {

			//Elements
			var $button = $(this);
			var $form = $button.parent();

			//Setup form data
			var data = {
				action: 'vp_woo_pont_save_design',
				nonce: vp_woo_pont_params.nonces.settings,
				values: {}
			};

			//Get all values
			$('.vp-woo-pont-modal-design-form input').each(function(){
				var name = $(this).attr('name');
				name = name.replace('vp_woo_pont_', '');
				if($(this).attr('type') == 'checkbox') {
					data.values[name] = $(this).is(':checked');
				} else {
					data.values[name] = $(this).val();
				}
			});

			//Show loading indicaotr
			$form.block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + vp_woo_pont_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			//Make request
			$.post(ajaxurl, data, function(response) {
				vp_woo_pont_customizer.saved_values = data.values;
				$form.unblock();
			});
		},
		reset_design: function() {
			$('.vp-woo-pont-modal-design-form input').each(function(){
				if($(this).data('default')) {
					$(this).val($(this).data('default'));
				}
			});
			vp_woo_pont_customizer.update_inline_css();
			return false;
		}
	}

	if($('.vp-woo-pont-appearance-editor').length) {
		vp_woo_pont_customizer.init();
	}

});

