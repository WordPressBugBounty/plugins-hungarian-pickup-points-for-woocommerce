<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_values = get_option('vp_woo_pont_packagings');

?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-packaging">
	<div class="wc-backbone-modal vp-woo-pont-modal-packaging">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Size & weight', 'vp-woo-pont'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
					<div class="vp-woo-pont-modal-packaging-dimensions">
						<p>
							<strong><?php esc_html_e('Packaging', 'vp-woo-pont'); ?></strong>
						</p>
						<ul class="vp-woo-pont-modal-packaging-types">
							<?php if($saved_values): ?>
								<?php foreach ( $saved_values as $packaging_id => $packaging ): ?>
									<li>
										<input type="radio" id="packaging-type-<?php echo esc_attr($packaging['sku']); ?>" name="packaging_type" value="<?php echo esc_attr($packaging['sku']); ?>">
										<label for="packaging-type-<?php echo esc_attr($packaging['sku']); ?>">
											<?php echo esc_html($packaging['name']); ?>
										</label>
									</li>
								<?php endforeach; ?>
								<li>
									<input type="radio" id="packaging-type-custom" name="packaging_type" value="custom">
									<label for="packaging-type-custom">
										<?php esc_html_e('Custom packaging', 'vp-woo-pont'); ?>
									</label>
								</li>
							<?php endif; ?>
						</ul>
						<div class="vp-woo-pont-modal-packaging-custom">
							<div>
								<label><?php esc_html_e('Length', 'vp-woo-pont'); ?></label>
								<input type="text" name="packaging_length" value="">
							</div>
							<div>
								<label><?php esc_html_e('Width', 'vp-woo-pont'); ?></label>
								<input type="text" name="packaging_width" value="">
							</div>
							<div>
								<label><?php esc_html_e('Height', 'vp-woo-pont'); ?></label>
								<input type="text" name="packaging_height" value="">
							</div>
						</div>
					</div>
					<div class="vp-woo-pont-modal-packaging-weight">
						<p><strong><?php esc_html_e('Weight', 'vp-woo-pont'); ?></strong></p>
						<div>
							<input type="text" name="packaging_weight" value="">
							<em><?php esc_html_e('gramms', 'vp-woo-pont'); ?></em>
						</div>
					</div>
				</article>
				<footer>
					<div class="inner">
						<a class="button button-primary button-large vp-woo-pont-modal-packaging-submit" target="_blank" href="#"><?php esc_html_e( 'Save', 'vp-woo-pont' ); ?></a>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script>

	jQuery(document).ready(function($){

		var vp_woo_pont_packaging_modal = {
			nonce: '',
			order_id: '',
			init: function() {

				//Store nonce
				this.nonce = vp_woo_pont_params.nonces.tracking;

				//Show all button
				$(document).on( 'click', '.vp-woo-pont-order-column-packaging', this.show_modal);
				$(document).on( 'change', '.vp-woo-pont-modal-packaging input[name="packaging_type"]', this.toggle_custom_packaging);
				$(document).on( 'click', '.vp-woo-pont-modal-packaging-submit', this.save_packaging);

			},
			show_modal: function() {
				vp_woo_pont_packaging_modal.order_id = $(this).parents('.vp-woo-pont-order-column').data('order');
				var packaging = $(this).data('packaging');
				var weight = $(this).data('weight');

				$(this).WCBackboneModal({
					template: 'vp-woo-pont-modal-packaging',
					variable : {}
				});

				//Append form data
				$('.vp-woo-pont-modal-packaging').find('input[name="packaging_type"][value="' + packaging.sku + '"]').prop('checked', true);
				$('.vp-woo-pont-modal-packaging').find('input[name="packaging_length"]').val(packaging.length);
				$('.vp-woo-pont-modal-packaging').find('input[name="packaging_width"]').val(packaging.width);
				$('.vp-woo-pont-modal-packaging').find('input[name="packaging_height"]').val(packaging.height);
				$('.vp-woo-pont-modal-packaging').find('input[name="packaging_weight"]').val(weight);

				//Toggle custom packaging
				if(packaging.sku == 'custom') {
					$('.vp-woo-pont-modal-packaging-custom').show();
				} else {
					$('.vp-woo-pont-modal-packaging-custom').hide();
				}

				return false;
			},
			toggle_custom_packaging: function() {
				if($(this).val() == 'custom') {
					$('.vp-woo-pont-modal-packaging-custom').show();
				} else {
					$('.vp-woo-pont-modal-packaging-custom').hide();
				}
			},
			save_packaging: function() {

				//Make ajax request
				var data = {
					action: 'vp_woo_pont_update_package_details',
					nonce: vp_woo_pont_params.nonces.generate,
					order: vp_woo_pont_packaging_modal.order_id,
					weight: $('.vp-woo-pont-modal-packaging').find('input[name="packaging_weight"]').val(),
					packaging_name: $('.vp-woo-pont-modal-packaging').find('input[name="packaging_type"]:checked').next().text(),
					packaging_sku: $('.vp-woo-pont-modal-packaging').find('input[name="packaging_type"]:checked').val(),
					packaging_length: $('.vp-woo-pont-modal-packaging').find('input[name="packaging_length"]').val(),
					packaging_width: $('.vp-woo-pont-modal-packaging').find('input[name="packaging_width"]').val(),
					packaging_height: $('.vp-woo-pont-modal-packaging').find('input[name="packaging_height"]').val(),
				};

				//Make request
				$.post(ajaxurl, data, function(response) {

					//Update the column
					var $column = $('.vp-woo-pont-order-column[data-order="' + vp_woo_pont_packaging_modal.order_id + '"]').find('.vp-woo-pont-order-column-packaging');

					//Update data attributes and text values
					if(response.data && response.data.weight && response.data.packaging) {
						$column.data('weight', response.data.weight);
						$column.data('packaging', response.data.packaging);
						$column.find('.vp-woo-pont-order-column-packaging-label').text(response.data.packaging.name+', '+response.data.weight+'g');
					}

					//Close modal
					$('.modal-close').trigger('click');

				});

				return false;
			}
		}

		vp_woo_pont_packaging_modal.init();

	});

</script>
