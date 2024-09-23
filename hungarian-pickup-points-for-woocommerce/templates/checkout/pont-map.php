<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="vp-woo-pont-modal-bg"></div>
<div class="vp-woo-pont-modal">
	<div class="vp-woo-pont-modal-sidebar">
		<div class="vp-woo-pont-modal-sidebar-search">
			<span class="vp-woo-pont-modal-sidebar-search-icon"></span>
			<input class="vp-woo-pont-modal-sidebar-search-field search" type="text" placeholder="<?php esc_attr_e('Search for an address', 'vp-woo-pont'); ?>" inputmode="search">
			<div class="vp-woo-pont-modal-sidebar-search-field-focus"></div>
			<a href="#" class="vp-woo-pont-modal-sidebar-search-clear"></a>
		</div>
		<ul class="vp-woo-pont-modal-sidebar-filters <?php if($show_checkboxes): ?>show-checkbox<?php endif; ?>"></ul>
		<div style="display:none">
			<li class="vp-woo-pont-modal-sidebar-result" id="vp-woo-pont-modal-list-item-sample" data-provider="" data-id="">
				<div class="vp-woo-pont-modal-sidebar-result-info">
					<i class="vp-woo-pont-modal-sidebar-result-info-icon icon"></i>
					<div class="vp-woo-pont-modal-sidebar-result-info-text">
						<strong class="name"></strong>
						<span class="addr"></span>
						<span class="cost"></span>
					</div>
				</div>
				<div class="vp-woo-pont-modal-sidebar-result-info-comment comment"></div>
				<div class="vp-woo-pont-modal-sidebar-result-info-open-hours open-hours">
					<a href="#" class="open-hours-toggle">
						<strong><?php echo esc_html__('Opening hours', 'vp-woo-pont'); ?></strong>
						<span class="icon-chevron"></span>
					</a>
					<ul>
						<?php $today_number = date('N'); ?>
						<?php foreach ($days as $day_number => $day): ?>
							<li <?php if($today_number-1 == $day_number): ?>class="today"<?php endif; ?>>
								<span class="day">
									<?php echo esc_html($day); ?>
									<?php if($today_number-1 == $day_number): ?>
										<?php echo esc_html__('(today)', 'vp-woo-pont'); ?>
									<?php endif; ?>:
								</span>
								<em class="value">-</em>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php if($cod_available): ?>
					<div class="vp-woo-pont-modal-sidebar-result-info-cod cod-notice"><?php esc_html_e('Cash on delivery is not available on this pickup point!', 'vp-woo-pont'); ?></div>
				<?php endif; ?>
				<a href="#" class="vp-woo-pont-modal-sidebar-result-select"><?php esc_attr_e('Set as my pick-up point', 'vp-woo-pont'); ?></a>
			</li>
			<li class="vp-woo-pont-modal-sidebar-no-result" id="vp-woo-pont-modal-no-result-sample">
				<p><?php esc_html_e('Sadly there are no results for this keyword. Please try to look for another place, or select a nearby pickup point on the map.', 'vp-woo-pont'); ?></p>
			</li>
		</div>
		<ul class="vp-woo-pont-modal-sidebar-results"></ul>
	</div>
	<div class="vp-woo-pont-modal-map">
		<div id="vp-woo-pont-modal-map"></div>
		<a href="#" class="vp-woo-pont-modal-map-close"><span></span></a>
	</div>
</div>
