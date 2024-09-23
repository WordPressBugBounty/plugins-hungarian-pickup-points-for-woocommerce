<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$providers = $this->get_available_providers();
$all_provider_types = array_keys($providers);

?>

<div class="vp-woo-pont-instance-blank-state">
	<p class="vp-woo-pont-instance-blank-state-copy"><?php esc_html_e('You can setup this shipping method in the Pickup Points menu.', 'vp-woo-pont'); ?></p>
	<div class="vp-woo-pont-instance-blank-state-icons">
		<?php foreach($all_provider_types as $provider_id): ?>
			<i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
		<?php endforeach; ?>
	</div>
	<a href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_pont' )); ?>" class="vp-woo-pont-instance-blank-state-settings button button-primary"><?php esc_html_e('Go to settings', 'vp-woo-pont'); ?></a>
</div>