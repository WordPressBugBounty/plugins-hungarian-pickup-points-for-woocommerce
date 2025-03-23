jQuery(document).ready(function($) {

	//Settings page
	var vp_woo_pont_kvikk = {
		home_delivery_pricing: {},
        pickup_point_pricing: {},
        cod_pricing: {},
        $metabox: $('.vp-woo-pont-metabox-content'),
        $home_delivery_providers: $('.vp-woo-pont-metabox-rows-data-home-delivery-providers'),
        $metabox_weight_input: $('#vp_woo_pont_package_weight'), 
        $metabox_calculated_price: $('.vp-woo-pont-metabox-rows-kvikk-price'),
        $modifyProviderButton: $('#vp_woo_pont_modify_provider'),
        $enabled_providers: $('.vp-woo-pont-providers'),
        $homeDeliveryProviderInput: $('input[name="home_delivery_provider"]'),
        currency_formatter: new Intl.NumberFormat('hu-HU', { style: 'currency', currency: 'HUF', minimumFractionDigits: 0, maximumFractionDigits: 0}),
		init: function() {

            //Skip if no pricing
            if(!vp_woo_pont_kvikk_params.couriers || !vp_woo_pont_kvikk_params.couriers.pricing || !vp_woo_pont_kvikk_params.couriers.pricing.shipping) {
                return
            }
			
            //Set shipping and cod pricing
            this.shipping_pricing = vp_woo_pont_kvikk_params.couriers.pricing.shipping;
            this.cod_pricing = vp_woo_pont_kvikk_params.couriers.pricing.cod;
            
            //Set prices in metabox
            this.display_metabox_shipping_cost();

            //Refresh when weight changes
            this.$metabox_weight_input.on('keyup', this.display_metabox_shipping_cost);

            //Display single price
            this.display_single_metabox_shipping_cost();

            //Hide stuff when provider changes
            this.$modifyProviderButton.on( 'click', this.show_provider_options );
			this.$homeDeliveryProviderInput.on( 'change', this.on_provider_change );

            //Calculate shipping cost when pickup point changes
            $(document.body).on( 'vp_woo_pont_metabox_pickup_point_changed', this.on_provider_change );

		},
        show_provider_options: function() {
			vp_woo_pont_kvikk.$metabox_calculated_price.slideUp();
			return false;
		},
        on_provider_change: function() {
            setTimeout(function() {
                vp_woo_pont_kvikk.display_single_metabox_shipping_cost();
            }, 100);
		},
		check_if_provider_selected: function() {
			$( '.vp-woo-pont-providers-wrapper' ).addClass('provider-selected');
			if( $( '.vp-woo-pont-providers input[name="vp_woo_pont_enabled_providers[]"]:checked' ).length == 0) {
				$( '.vp-woo-pont-providers-wrapper' ).removeClass('provider-selected');
			}
		},
        display_metabox_shipping_cost: function() {
            vp_woo_pont_kvikk.$home_delivery_providers.find('li').each(function() {
                var provider = $(this).data('provider');

                //Check if starts with kvikk, if not, skip
                if(provider.indexOf('kvikk') !== 0) {
                    return;
                }

                //Remove prefix
                provider = provider.replace('kvikk_', '');

                //Get weight and cod value
                var weight = vp_woo_pont_kvikk.$metabox_weight_input.val();
                var cod = vp_woo_pont_kvikk.$metabox.data('cod');

                //Get pricing
                var price = vp_woo_pont_kvikk.calculate_shipping_cost(provider, weight, cod);
                var formatted_price = vp_woo_pont_kvikk.currency_formatter.format(price);
                var label = $(this).find('.shipping-cost').data('label');

                //Display price
                if(price) {
                    $(this).find('.shipping-cost').text(label+': '+formatted_price);
                } else {
                    $(this).find('.shipping-cost').text(label+': -');
                }
                
            });
        },
        calculate_shipping_cost: function(provider, weight, cod) {
            var shipping = 0;
            var cod_fee = 0;
            var cod_percentage_fee = 0;

            //Get courier
            const shipping_courier = vp_woo_pont_kvikk.shipping_pricing.find(c => c.courier === provider);
            const cod_courier = vp_woo_pont_kvikk.cod_pricing.find(c => c.courier === provider);
            if (!shipping_courier || !cod_courier) {
                return false;
            }
        
            //Get courier pricing
            const shipping_cost = shipping_courier.prices.find(p => weight >= p.min && weight <= p.max);
            if (shipping_cost) {
                shipping = shipping_cost.cost;
            } else {
                return false;
            }

            //Get cod pricing
            if(cod) {
                cod = parseInt(cod);
                const cod_costs = cod_courier.prices.find(p => cod >= p.min && cod <= p.max);
                if (cod_costs) {
                    console.log(cod_costs)
                    cod_fee = cod_costs.fee;
                    cod_percentage_fee = cod*cod_costs.percentage/100;
                }
            }

            //Return shipping cost
            return shipping + cod_fee + cod_percentage_fee;
            
        },
        display_single_metabox_shipping_cost: function() {

            //Hide if we have a label
            if($('#vp_woo_pont_metabox_kvikk').length) {
                vp_woo_pont_kvikk.$metabox_calculated_price.slideUp();
                return;
            }

            var courier = $('.vp-woo-pont-metabox-rows-data-provider').find('i').attr('class');
            if(!courier.includes('kvikk')) {
                vp_woo_pont_kvikk.$metabox_calculated_price.slideUp();
                return;
            } else {
                courier = courier.replace('vp-woo-pont-provider-icon-kvikk_', '');
            }


            //Get weight and cod value
            var weight = vp_woo_pont_kvikk.$metabox_weight_input.val();
            var cod = vp_woo_pont_kvikk.$metabox.data('cod');

            //Get pricing
            var price = vp_woo_pont_kvikk.calculate_shipping_cost(courier, weight, cod);
            
            //Display price
            if(price) {
                vp_woo_pont_kvikk.$metabox_calculated_price.slideDown();
                vp_woo_pont_kvikk.$metabox_calculated_price.find('strong').text(vp_woo_pont_kvikk.currency_formatter.format(price));
            } else {
                vp_woo_pont_kvikk.$metabox_calculated_price.slideUp();
            }

        }
	}

	//Init settings page
	if($('#vp_woo_pont_metabox').length) {
		vp_woo_pont_kvikk.init();
	}

    var vp_woo_pont_kvikk_promo = {
        apiUrl: 'https://api.kvikk.hu/public',
        init: function() {
            $(document).on('click', '.kvikk-promo-close', this.hide_promo.bind(this));
            $(document).on('click', '.kvikk-promo-hide', this.hide_promo.bind(this));
            $(document).on('click', '.kvikk-promo-cta', this.cta_click.bind(this));
            if($('.kvikk-promo').length) {
                this.get_pricing();
            } else {
                $(document.body).on('vp_woo_pont_generate_modal_shown', function() {
                    vp_woo_pont_kvikk_promo.get_pricing()
                });
            }
        },
        hide_promo: function() {
            $('.kvikk-promo').slideUp();
            var nonce = $('.kvikk-promo').data('nonce');
            $.post(ajaxurl, {
                action: 'vp_woo_pont_hide_kvikk_promo',
                nonce: nonce
            });
            return false;
        },
        cta_click: function() {
            vp_woo_pont_kvikk_promo.hide_promo();
        },
        get_pricing: function() {

            console.log('get_pricing');

            //Check if pricing is in local storage(stored for 30 days)
            var pricing = localStorage.getItem('vp_woo_pont_kvikk_promo_pricing');
            if(pricing) {

                //Parse and return pricing
                const kvikk_pricing_data = JSON.parse(pricing);

                //Check if we have data
                if(!kvikk_pricing_data) {
                    vp_woo_pont_kvikk_promo.fetch_pricing();
                    return;
                }

                //Check if data is still valid
                const expiration = new Date(kvikk_pricing_data.expiration);
                const now = new Date();
                if(now > expiration) {
                    vp_woo_pont_kvikk_promo.fetch_pricing();
                    return;
                }

                //Show the price list
                vp_woo_pont_kvikk_promo.display_pricing(kvikk_pricing_data.data);
                return;

            } else {

                //Fetch pricing
                vp_woo_pont_kvikk_promo.fetch_pricing();

            }
        },
        fetch_pricing: function() {

            //Fetch pricing
            fetch(vp_woo_pont_kvikk_promo.apiUrl+'/pricing?latest=1')
            .then(response => response.json())
            .then(data => {

                //Check if we have data
                if(!data.data.shipping) {
                    console.error('No pricing data found');
                    return;
                }

                //Get pricing data
                const kvikk_pricing_data = data.data.shipping;
                const expDate = new Date();
                expDate.setDate(expDate.getDate() + 30);

                //Create a new object with only the necessary data
                const kvikk_pricing_data_filtered = kvikk_pricing_data.map(courier => {
                    return {
                        courier: courier.courier,
                        price: courier.prices[0].cost
                    }
                });

                //Store pricing in local storage
                localStorage.setItem('vp_woo_pont_kvikk_promo_pricing', JSON.stringify({expiration: expDate, data: kvikk_pricing_data_filtered}));
                vp_woo_pont_kvikk_promo.get_pricing();
            })
            .catch(error => {
                console.error('Error fetching price list:', error);
            });

        },
        display_pricing: function(pricing) {
            const couriers = ['mpl', 'gls', 'dpd', 'famafutar'];
            $('.kvikk-promo').addClass('loaded');
            for (const courier of couriers) {
                const courier_data = pricing.find(c => c.courier === courier);
                if(courier_data) {
                    let price = courier_data.price;
                    price = new Intl.NumberFormat('hu-HU', { style: 'currency', currency: 'HUF', minimumFractionDigits: 0, maximumFractionDigits: 0}).format(price);
                    $(`strong[data-kvikk-promo-price="${courier}"]`).text(price);
                }
            }

        }
    }

    if($('.kvikk-promo').length || $('#tmpl-vp-woo-pont-modal-generate').length) {
        vp_woo_pont_kvikk_promo.init();
    }

});
