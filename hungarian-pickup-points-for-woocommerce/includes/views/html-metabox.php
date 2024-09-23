<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php

//Get some default options
$provider_id = VP_Woo_Pont_Helpers::get_provider_from_order($order);
$carrier_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
$provider_name = VP_Woo_Pont_Helpers::get_provider_name($provider_id);
$has_label = $order->get_meta( '_vp_woo_pont_parcel_pdf' );
$pending = $order->get_meta('_vp_woo_pont_parcel_pending');
$providers_for_home_delivery = VP_Woo_Pont_Helpers::get_supported_providers_for_home_delivery();
$shipping_method = $order->get_shipping_methods();
if(!empty($shipping_method)) {
	$shipping_method = reset($shipping_method)->get_method_id();
} else {
	$shipping_method = false;
}

//Get COD details
$cod = ($order->get_payment_method() == 'cod') ? $order->get_total() : 0;

?>

	<?php if(!in_array($provider_id, $this->supported_providers) && $order->get_meta('_vp_woo_pont_point_name')): //TODO: check for api keys for labels ?>
		<p class="vp-woo-pont-metabox-unsupported">A kiválasztott szolgáltatóhoz(<?php echo esc_html($provider_name); ?>) jelenleg nem érhető el a címkenyomtatás funkció.</p>
	<?php endif; ?>

	<div class="vp-woo-pont-metabox-content" data-order="<?php echo $order->get_id(); ?>" data-nonce="<?php echo wp_create_nonce( "vp_woo_pont_manage" ); ?>" data-provider_id="<?php echo $carrier_id; ?>" data-cod="<?php echo esc_attr($cod); ?>">
	
		<?php do_action('vp_woo_pont_metabox_before_content', $order); ?>
	
		<div class="vp-woo-pont-metabox-messages vp-woo-pont-metabox-messages-label vp-woo-pont-metabox-messages-success" style="display:none;">
			<div class="vp-woo-pont-metabox-messages-content">
				<ul></ul>
				<a href="#"><span class="dashicons dashicons-no-alt"></span></a>
			</div>
		</div>

		<?php if(!VP_Woo_Pont_Pro::is_pro_enabled()): ?>
			<div class="vp-woo-pont-metabox-pro-cta">
				<p><?php esc_html_e('Upgrade to the PRO version to generate shipping labels and to track shipments.', 'vp-woo-pont'); ?></p>
				<div class="vp-woo-pont-metabox-pro-cta-buttons">
					<a href="https://visztpeter.me/woocommerce-csomagpont-integracio/" class="button button-primary"><?php esc_html_e('Learn More', 'vp-woo-pont'); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_labels' ) ); ?>"><?php esc_html_e('Activate PRO version', 'vp-woo-pont'); ?></a>
				</div>
			</div>
		<?php endif; ?>

		<ul class="vp-woo-pont-metabox-rows">

			<?php //If its normal shipping ?>
			<?php if(!$order->get_meta('_vp_woo_pont_point_id') && $shipping_method != 'vp_pont' && VP_Woo_Pont_Pro::is_pro_enabled()): ?>
			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-provider <?php if($provider_id): ?>show<?php endif; ?>">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
					<strong><?php echo $provider_name; ?></strong>
					<?php if(!$has_label): ?>
						<a href="#" id="vp_woo_pont_modify_provider"><?php echo esc_html__('Modify', 'vp-woo-pont'); ?></a>
					<?php endif; ?>
				</div>
			</li>
			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-home-delivery-providers <?php if(!$provider_id): ?>show<?php endif; ?>">
				<ul>
					<?php if(!$provider_id) $provider_id = ''; ?>
					<?php foreach ($providers_for_home_delivery as $id => $name): ?>
						<?php if(!VP_Woo_Pont_Helpers::is_provider_configured($id)) continue; ?>
						<li data-provider="<?php echo esc_attr($id); ?>">
							<input type="radio" name="home_delivery_provider" data-label="<?php echo esc_attr($name); ?>" id="home_delivery_provider_<?php echo esc_attr($id); ?>" <?php checked($id, $provider_id); ?> value="<?php echo esc_attr($id); ?>">
							<label for="home_delivery_provider_<?php echo esc_attr($id); ?>">
								<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($id); ?>"></i>
								<?php echo esc_html($name); ?>
							</label>
							<?php if(strpos($id, 'kvikk') !== false): ?>
								<div class="shipping-cost" data-label="<?php esc_attr_e('Calculated cost(net)', 'vp-woo-pont'); ?>"></div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<p class="vp-woo-pont-metabox-rows-data-home-delivery-providers-info <?php if(!$provider_id): ?>show<?php endif; ?>">
					<?php printf( __( 'Theres no carrier paired with the order\'s shipping method. You can select one here for the order, or <a href="%s" target="_blank">pair it in settings</a>, so you don\'t have to do this the next time.', 'vp-woo-pont' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_labels' ) ) ); ?>
				</p>
			</li>
			<?php endif; ?>

			<?php //If its pickup point shipping ?>
			<?php if($order->get_meta('_vp_woo_pont_point_id')): ?>
			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-provider show">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
					<strong><?php echo $provider_name; ?></strong>
					<span title="<?php echo esc_attr($order->get_meta('_vp_woo_pont_point_id')); ?>"><?php echo $order->get_meta('_vp_woo_pont_point_name'); ?></span>
				</div>
			</li>
			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-remove <?php if(!$has_label && !$pending): ?>show<?php endif; ?>">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<a href="#" data-trigger-value="<?php esc_attr_e('Remove selected point','vp-woo-pont'); ?>" data-question="<?php echo esc_attr_x('Are you sure?', 'Delete point', 'vp-woo-pont'); ?>" class="delete"><?php esc_html_e('Remove selected point','vp-woo-pont'); ?></a>
				</div>
			</li>
			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-replace <?php if(!$has_label && !$pending): ?>show<?php endif; ?>">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<a href="#" data-provider_id="<?php echo esc_attr($provider_id); ?>"><?php esc_html_e('Replace selected point','vp-woo-pont'); ?></a>
				</div>
			</li>
			<?php endif; ?>

			<?php if(!$order->get_meta('_vp_woo_pont_point_id') && $shipping_method == 'vp_pont'): ?>
			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-provider">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<i class="vp-woo-pont-provider-icon-"></i>
					<strong></strong>
					<span></span>
				</div>
			</li>
			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-replace <?php if(!$has_label && !$pending): ?>show<?php endif; ?>">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<a href="#"><?php esc_html_e('Select a pickup point','vp-woo-pont'); ?></a>
				</div>
			</li>
			<?php endif; ?>

			<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-kvikk-price">
				<div class="vp-woo-pont-metabox-rows-data-inside">
					<span><?php esc_html_e('Calculated cost(net)', 'vp-woo-pont'); ?></span>
					<strong></strong>
				</div>
			</li>

			<?php //Shipping label data, tracking number, delete button ?>
			<?php if(VP_Woo_Pont_Pro::is_pro_enabled()): ?>
				<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-label <?php if($has_label): ?>show<?php endif; ?>">
					<div class="vp-woo-pont-metabox-rows-data-inside">
						<div class="vp-woo-pont-metabox-rows-label-download">
							<span><?php esc_html_e('Shipping label', 'vp-woo-pont'); ?></span>
							<a target="_blank" class="vp-woo-pont-download-label" href="<?php echo $this->generate_download_link($order); ?>">
								<span class="dashicons dashicons-download"></span>
								<strong><?php esc_html_e('Download','vp-woo-pont'); ?></strong>
							</a>
						</div>
						<div class="vp-woo-pont-metabox-rows-label-print <?php if($order->get_meta('_vp_woo_pont_parcel_count')): ?>multiple_parcels<?php endif; ?>">
							<a target="_blank" class="vp-woo-pont-print-label" href="<?php echo $this->generate_download_link($order); ?>">
								<span class="dashicons dashicons-printer"></span>
							</a>
						</div>
					</div>
				</li>
				<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-parcel-count <?php if($has_label && $order->get_meta('_vp_woo_pont_parcel_count')): ?>show<?php endif; ?>">
					<div class="vp-woo-pont-metabox-rows-data-inside">
						<span><?php esc_html_e('Packaging Quantity', 'vp-woo-pont'); ?></span>
						<strong data-qty="<?php esc_attr_e('pcs', 'vp-woo-pont'); ?>"><?php echo esc_html($order->get_meta('_vp_woo_pont_parcel_count')); ?></strong>
					</div>
				</li>
				<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-link-tracking <?php if($has_label): ?>show<?php endif; ?>">
					<div class="vp-woo-pont-metabox-rows-data-inside">
						<span><?php esc_html_e('Tracking number', 'vp-woo-pont'); ?></span>
						<strong><a href="<?php echo VP_Woo_Pont()->tracking->get_tracking_link($order); ?>" target="_blank"><?php echo esc_html($order->get_meta('_vp_woo_pont_parcel_number')); ?></a></strong>
					</div>
				</li>
				<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-void <?php if($has_label): ?>show<?php endif; ?>">
					<div class="vp-woo-pont-metabox-rows-data-inside">
						<a href="#" data-trigger-value="<?php esc_attr_e('Delete label','vp-woo-pont'); ?>" data-question="<?php echo esc_attr_x('Are you sure?', 'Delete label', 'vp-woo-pont'); ?>" class="delete"><?php esc_html_e('Delete label','vp-woo-pont'); ?></a>
					</div>
				</li>
				<?php if($order->get_meta('_vp_woo_pont_mpl_closed') || $order->get_meta('_vp_woo_pont_closed')): ?>
					<?php $shipment = $order->get_meta('_vp_woo_pont_mpl_closed'); ?>
					<?php if($order->get_meta('_vp_woo_pont_closed')) $shipment = $order->get_meta('_vp_woo_pont_closed'); ?>
					<?php if($shipment != 'no'): ?>
						<li class="vp-woo-pont-metabox-rows-data vp-woo-pont-metabox-rows-data-shipment show">
							<div class="vp-woo-pont-metabox-rows-data-inside">
								<?php if($shipment == 'yes'): ?>
									<span><?php esc_html_e('Closed manually', 'vp-woo-pont'); ?></span>
									<a href="#" data-trigger-value="<?php esc_attr_e('Undo','vp-woo-pont'); ?>" data-question="<?php echo esc_attr_x('Are you sure?', 'Delete label', 'vp-woo-pont'); ?>" class="undo"><?php esc_html_e('Undo','vp-woo-pont'); ?></a>
								<?php else: ?>
									<span><?php esc_html_e('Delivery note ID', 'vp-woo-pont'); ?></span>
									<strong><?php echo esc_html($shipment); ?></strong>
								<?php endif; ?>
							</div>
						</li>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>

		</ul>

		<?php if(VP_Woo_Pont_Pro::is_pro_enabled() && !$pending): ?>
		<div class="vp-woo-pont-metabox-generate <?php if(!$has_label && in_array($provider_id, $this->supported_providers)): ?>show<?php endif; ?>">
			<div class="vp-woo-pont-metabox-generate-buttons">
				<a href="#" id="vp_woo_pont_label_options"><span class="dashicons dashicons-admin-generic"></span><span><?php esc_html_e('Options','vp-woo-pont'); ?></span></a>
				<a href="#" id="vp_woo_pont_label_generate" class="button button-primary" target="_blank" data-question="<?php echo esc_attr_x('Are you sure?', 'Generate label', 'vp-woo-pont'); ?>">
					<?php esc_html_e('Generate label', 'vp-woo-pont'); ?>
				</a>
			</div>

			<ul class="vp-woo-pont-metabox-generate-options" style="display:none">
				<li class="vp-woo-pont-metabox-generate-options-item" data-providers="[gls, dpd, posta, postapont, expressone, sameday]">
					<label for="vp_woo_pont_package_count"><?php esc_html_e('Number of packages','vp-woo-pont'); ?></label>
					<input type="number" id="vp_woo_pont_package_count" value="1" />
				</li>
				<li class="vp-woo-pont-metabox-generate-options-item" data-providers="[gls]">
					<label for="vp_woo_pont_pickup_date"><?php esc_html_e('Pickup date','vp-woo-pont'); ?></label>
					<input type="text" class="date-picker" id="vp_woo_pont_pickup_date" maxlength="10" value="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
				</li>
				<li class="vp-woo-pont-metabox-generate-options-item" data-providers="[gls]">
					<label><?php esc_html_e('Extra services','vp-woo-pont'); ?></label>
					<ul>
						<?php foreach (VP_Woo_Pont()->providers['gls']->extra_services as $service_id => $service): ?>
							<?php
							$checked = false;
							$saved_options_field = ($order->get_meta('_vp_woo_pont_point_id')) ? 'gls_extra_services_points' : 'gls_extra_services';
							$saved_options = VP_Woo_Pont_Helpers::get_option($saved_options_field, array());
							?>
							<li>
								<label for="vp_woo_pont_extra_service_<?php echo esc_attr($service_id); ?>">
									<input type="checkbox" name="vp_woo_pont_extra_services" id="vp_woo_pont_extra_service_<?php echo esc_attr($service_id); ?>" value="<?php echo esc_attr($service_id); ?>" <?php checked(in_array($service_id, $saved_options)); ?> />
									<span><?php echo esc_html($service); ?></span>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</li>
				<li data-providers="[posta, postapont]" class="vp-woo-pont-metabox-generate-options-item">
					<label><?php esc_html_e('Extra services','vp-woo-pont'); ?></label>
					<ul>
						<?php foreach (VP_Woo_Pont()->providers['posta']->extra_services as $service_id => $service): ?>
							<?php
							$checked = false;
							$saved_options_field = 'posta_extra_services';
							$saved_options = VP_Woo_Pont_Helpers::get_option($saved_options_field, array());
							$is_fragile = VP_Woo_Pont()->providers['posta']->is_fragile($order);
							if($is_fragile) {
								$saved_options[] = 'K_TOR';
							}
							?>
							<li>
								<label for="vp_woo_pont_extra_service_<?php echo esc_attr($service_id); ?>">
									<input type="checkbox" name="vp_woo_pont_extra_services" id="vp_woo_pont_extra_service_<?php echo esc_attr($service_id); ?>" value="<?php echo esc_attr($service_id); ?>" <?php checked(in_array($service_id, $saved_options)); ?> />
									<span><?php echo esc_html__($service, 'vp-woo-pont'); ?></span>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</li>
				<li class="vp-woo-pont-package-weight vp-woo-pont-metabox-generate-options-item" data-providers="[posta, postapont, kvikk, sameday]">
					<label for="vp_woo_pont_package_weight"><?php esc_html_e('Package weight','vp-woo-pont'); ?></label>
					<input type="text" id="vp_woo_pont_package_weight" value="<?php echo VP_Woo_Pont_Helpers::get_package_weight_in_gramms($order); ?>">
					<em><?php esc_html_e('gramms', 'vp-woo-pont'); ?></em>
				</li>
				<li data-providers="[transsped]" class="vp-woo-pont-transsped-packaging vp-woo-pont-metabox-generate-options-item">
					<label><?php esc_html_e('Packaging Quantity', 'vp-woo-pont'); ?> <i class="total-qty">1</i></label>
					<ul>
						<?php foreach (VP_Woo_Pont()->providers['transsped']->packaging_types as $package_type => $package_name): ?>
						<li>
							<label><?php echo esc_html__($package_name, 'vp-woo-pont'); ?></label>
							<div class="qty">
								<a href="#" class="minus"><span class="dashicons dashicons-minus"></span></a>
								<span class="value">0</span>
								<a href="#" class="plus"><span class="dashicons dashicons-plus"></span></a>
								<input name="vp_woo_pont_transsped_packaging[<?php echo esc_html($package_type); ?>]" type="number" value="<?php if($package_type == VP_Woo_Pont_Helpers::get_option('trans_sped_package_type')): ?>1<?php else: ?>0<?php endif; ?>">
							</div>
						</li>
						<?php endforeach; ?>
					</ul>
				</li>
				<li>
					<label for="vp_woo_pont_package_contents"><?php esc_html_e('Package contents','vp-woo-pont'); ?></label>
					<input type="text" id="vp_woo_pont_package_contents" value="<?php echo VP_Woo_Pont()->labels->get_package_contents_label(array('order' => $order), $provider_id); ?>">
				</li>
				<?php do_action('vp_woo_pont_metabox_after_generate_options', $order); ?>
			</ul>

		</div>
		<?php endif; ?>
	</div>

	<?php include( dirname( __FILE__ ) . '/html-modal-replace-point.php' ); ?>
