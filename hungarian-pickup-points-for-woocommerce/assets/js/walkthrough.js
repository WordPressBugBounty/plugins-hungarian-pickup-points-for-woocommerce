jQuery(document).ready(function($) {

	//Settings page
  var vp_woo_pont_walkthrough = {
		steps: ['providers', 'cost', 'design', 'zone', 'pro', 'kvikk', 'success'],
		current_step: 0,
		$button_next: $('.vp-woo-pont-walkthrough-nav-next'),
		$button_previous: $('.vp-woo-pont-walkthrough-nav-previous'),
		$container: $('.vp-woo-pont-walkthrough'),
		$steps: $('.vp-woo-pont-walkthrough-steps'),
		$step: $('.vp-woo-pont-walkthrough-step'),
		init: function() {

			//Show first step
			$('.vp-woo-pont-walkthrough-step-'+this.steps[0]).addClass('active');
			this.update_step_class();

			//Handle next and back button
			this.$button_next.on('click', this.next_page);
			this.$button_previous.on('click', this.previous_page);

			//Check the first option
			$('input#provider-foxpost').prop('checked', true);

			//If packeta is checked, show/hide api key field
			$('input[data-carrier="expressone"]').on('change', this.on_expressone_select);
			$('input#provider-dpd').on('change', this.on_dpd_select);

			//Init color picker
			$('.vp-woo-pont-color-picker').wpColorPicker();

			//Cancel button
			$('.vp-woo-pont-walkthrough-nav-cancel').on('click', this.cancel);

		},
		next_page: function() {

			//Validate current step
			var valid = vp_woo_pont_walkthrough.validate();

			//If its valid, we can move to the next step
			if(valid) {

				//Get next step
				vp_woo_pont_walkthrough.current_step += 1;

				//Update parent class
				vp_woo_pont_walkthrough.update_step_class();

				//Run ajax call on success
				if(vp_woo_pont_walkthrough.steps.length-1 == vp_woo_pont_walkthrough.current_step) {
					vp_woo_pont_walkthrough.finish();
				}

			}

			return false;
		},
		previous_page: function() {

			//Get next step
			vp_woo_pont_walkthrough.current_step -= 1;

			//Update parent class
			vp_woo_pont_walkthrough.update_step_class();

			return false;
		},
		update_step_class: function() {

			//Hide all steps
			vp_woo_pont_walkthrough.$step.removeClass('active');

			//Get step id
			var next_step_id = vp_woo_pont_walkthrough.steps[vp_woo_pont_walkthrough.current_step];

			//Show next step
			$('.vp-woo-pont-walkthrough-step-'+next_step_id).addClass('active');

			//Set class to parent
			if(vp_woo_pont_walkthrough.current_step == 0) {
				vp_woo_pont_walkthrough.$container.attr('data-step', 'first');
			} else if (vp_woo_pont_walkthrough.steps.length-1 == vp_woo_pont_walkthrough.current_step) {
				vp_woo_pont_walkthrough.$container.attr('data-step', 'last');
			} else {
				vp_woo_pont_walkthrough.$container.attr('data-step', '');
			}

		},
		on_expressone_select: function() {
			if($('input[data-carrier="expressone"]:checked').length) {
				$('.step-expressone').show();
			} else {
				$('.step-expressone').hide();
			}
		},
		on_dpd_select: function() {
			if($(this).prop('checked')) {
				$('.step-dpd').show();
			} else {
				$('.step-dpd').hide();
			}
		},
		validate: function() {
			var step_id = vp_woo_pont_walkthrough.steps[vp_woo_pont_walkthrough.current_step];
			var $step = $('.vp-woo-pont-walkthrough-step-'+step_id);

			//Check each step for validation
			switch(step_id) {
				case 'providers':

					//At least 1 checkbox selected
					if($('input[name="providers[]"]:checked').length == 0) {
						vp_woo_pont_walkthrough.shake($step.find('ul'));
						return false;
					}

					//Username&password required if dpd selected
					if($('input#provider-dpd').prop('checked') && ($('input[name="dpd_username"]').val() == '' || $('input[name="dpd_password"]').val() == '')) {
						vp_woo_pont_walkthrough.shake($step.find('.step-dpd'));
						return false;
					}

					//Username&password required if dpd selected
					if($('input[data-carrier="expressone"]:checked').length && ($('input[name="expressone_company_id"]').val() == '' || $('input[name="expressone_username"]').val() == '' || $('input[name="expressone_password"]').val() == '')) {
						vp_woo_pont_walkthrough.shake($step.find('.step-dpd'));
						return false;
					}

					//Show and hide cost rows based on selected providers
					$('.vp-woo-pont-walkthrough-step-cost input').prop('disabled', true);
					$('.vp-woo-pont-walkthrough-step-cost li').hide();
					$step.find('input:checked').each(function(){
						var provider_id = $(this).val();
						$('li.cost-row-'+provider_id).show();
						$('li.cost-row-'+provider_id).find('input').prop('disabled', false);
					});

					//Looks valid
					return true;

					break;
			  case 'cost':

					//All fields required
					var has_error = false;
					$step.find('li:visible').each(function(){
						var input = $(this).find('input');
						if(input.val() == '') {
							vp_woo_pont_walkthrough.shake($(this));
							has_error = true;
						}
					});

					if(has_error) {
						return false;
					}

					return true;
					break;
				case 'design':

					$name_field = $step.find('input[name="method_name"]');
					console.log($name_field);
					if($name_field.val() == '') {
						vp_woo_pont_walkthrough.shake($name_field.parent());
						return false;
					}

					return true;
					break;
				case 'zone':

					//If no checkbox, that means theres no zones setup, so lets just continue instead
					if($('input[name="zones[]"]').length == 0) {
						return true;
					}

					//At least 1 checkbox selected
					if($('input[name="zones[]"]:checked').length == 0) {
						vp_woo_pont_walkthrough.shake($step.find('ul'));
						return false;
					}

					return true;
					break;
				default:
					return true;
			}
		},
		shake: function($element) {
			$element.addClass('fail');
			setTimeout(function(){
				$element.removeClass('fail');
			}, 1000);
		},
		finish: function() {
			var $form = $('.vp-woo-pont-walkthrough-form');
			var data = $form.serialize();

			$.post(ajaxurl, data, function(response) {
				console.log(response);
			});

		},
		cancel: function() {
			var nonce = $(this).data('nonce');
			var form = $(this).parent();
			var url = $(this).data('url');
			var data = {
				action: 'vp_woo_pont_cancel_setup_wizard',
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
		}
	}

	if($('.vp-woo-pont-walkthrough').length) {
		vp_woo_pont_walkthrough.init();
	}

});
