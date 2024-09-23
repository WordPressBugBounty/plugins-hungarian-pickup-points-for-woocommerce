<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$days = array(
	__('Monday', 'vp-woo-pont'),
	__('Tuesday', 'vp-woo-pont'),
	__('Wednesday', 'vp-woo-pont'),
	__('Thursday', 'vp-woo-pont'),
	__('Friday', 'vp-woo-pont'),
	__('Saturday', 'vp-woo-pont'),
	__('Sunday', 'vp-woo-pont')
);

//Get saved values
$styles = get_theme_mods();
$prefix = 'vp_woo_pont_';
if(get_option('vp_woo_pont_styles')) {
	$styles = get_option('vp_woo_pont_styles');
	$prefix = '';
}

?>

<script type="text/template" id="tmpl-vp-woo-pont-modal-design">
	<div class="wc-backbone-modal vp-woo-pont-modal-design">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<article class="vp-woo-pont-modal-design-controls">
					<div class="vp-woo-pont-modal-design-form">
						<div class="vp-woo-pont-modal-design-form-step">
							<h2><?php esc_html_e('Colors', 'vp-woo-pont'); ?></h2>
							<?php foreach($options['colors'] as $key => $option): ?>
								<div class="vp-woo-pont-modal-design-form-color">
									<input type="color" id="vp_woo_pont_<?php echo esc_attr($key); ?>" name="vp_woo_pont_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((isset($styles[$prefix.$key]) ? $styles[$prefix.$key] : $option['default'])); ?>" data-default="<?php echo esc_attr($option['default']); ?>">
									<label for="vp_woo_pont_<?php echo esc_attr($key); ?>"><?php echo esc_html($option['label']); ?></label>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="vp-woo-pont-modal-design-form-step">
							<h2><?php esc_html_e('Font sizes', 'vp-woo-pont'); ?></h2>
							<?php foreach($options['fonts'] as $key => $option): ?>
								<div class="vp-woo-pont-modal-design-form-font">
									<input id="vp_woo_pont_<?php echo esc_attr($key); ?>" name="vp_woo_pont_<?php echo esc_attr($key); ?>" type="number" min="10" max="20" step="1" value="<?php echo esc_attr((isset($styles[$prefix.$key]) ? $styles[$prefix.$key] : $option['default'])); ?>" data-default="<?php echo esc_attr($option['default']); ?>">
									<label for="vp_woo_pont_<?php echo esc_attr($key); ?>"><?php echo esc_html($option['label']); ?></label>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="vp-woo-pont-modal-design-form-step">
							<h2><?php esc_html_e('Appearance', 'vp-woo-pont'); ?></h2>
							<?php foreach($options['appearance'] as $key => $option): ?>
								<div class="vp-woo-pont-modal-design-form-appearance">
									<input id="vp_woo_pont_<?php echo esc_attr($key); ?>" name="vp_woo_pont_<?php echo esc_attr($key); ?>" type="checkbox" <?php checked(get_option('vp_woo_pont_'.$key, $option['default']), 'yes'); ?>>
									<label for="vp_woo_pont_<?php echo esc_attr($key); ?>"><?php echo esc_html($option['label']); ?></label>
								</div>
							<?php endforeach; ?>
						</div>
						<hr>
						<a href="#" class="vp-woo-pont-modal-design-reset"><?php esc_html_e('Reset to default', 'vp-woo-pont'); ?></a>
					</div>

					<div class="vp-woo-pont-modal-design-preview">
						<?php wc_get_template('checkout/pont-map.php', array('days' => $days, 'show_checkboxes' => false, 'cod_available' => true), false, VP_Woo_Pont::$plugin_path . '/templates/'); ?>
						<a class="modal-close modal-close-link" href="#"></a>
					</div>
				</article>
				<footer>
					<div class="inner">
						<a class="button button-large vp-woo-pont-modal-design-cancel modal-close modal-close-link" href="#"><?php esc_html_e( 'Cancel', 'vp-woo-pont' ); ?></a>
						<a class="button button-primary button-large vp-woo-pont-modal-design-save" href="#"><?php esc_html_e( 'Save', 'vp-woo-pont' ); ?></a>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
