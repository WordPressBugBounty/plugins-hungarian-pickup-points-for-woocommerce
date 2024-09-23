<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_value = get_option($data['id']);
$courier_details = get_option('vp_woo_pont_kvikk_courier_details', array('couriers' => array()));
?>

<tr class="<?php echo esc_attr( $data['row_class'] ); ?>">
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $data['id'] ); ?>"><?php echo esc_html( $data['title'] ); ?></label>
    </th>
    <td class="forminp forminp-text vp-woo-pont-kvikk-api-key">
        <input
            name="<?php echo esc_attr( $data['field_name'] ); ?>"
            id="<?php echo esc_attr( $data['id'] ); ?>"
            type="text"
            value="<?php echo esc_attr( $saved_value ); ?>"
            class="<?php echo esc_attr( $data['class'] ); ?>"
        />
        <a href="#" class="button vp-woo-pont-kvikk-api-key-test" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-validate-kvikk-api' )?>"><?php esc_html_e('Test', 'vp-woo-pont'); ?></a>
        <a href="https://app.kvikk.hu" target="_blank" class="button"><?php esc_html_e('Create API key', 'vp-woo-pont'); ?></a>

        <div class="vp-woo-pont-kvikk-api-key-results">
            <div class="vp-woo-pont-kvikk-api-key-results-success">
                <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('API key is valid', 'vp-woo-pont'); ?>
            </div>
            <div class="vp-woo-pont-kvikk-api-key-results-error">
                <span class="dashicons dashicons-warning"></span> <?php esc_html_e('API key is invalid', 'vp-woo-pont'); ?>
            </div>
            <?php if(count($courier_details['couriers']) > 0): ?>
            <div class="vp-woo-pont-kvikk-api-key-results-couriers">
                <h4><?php esc_html_e('Supported couriers', 'vp-woo-pont'); ?></h4>
                <?php foreach($courier_details['couriers'] as $courier): ?>
                    <div class="vp-woo-pont-kvikk-api-key-results-courier <?php if($courier['status'] == 'active'): ?>active<?php endif; ?>">
                        <strong><?php echo esc_html($courier['name']); ?></strong>
                        <span><?php echo esc_html($courier['note']); ?></span>
                        <?php if($courier['status'] == 'active'): ?><span class="dashicons dashicons-yes-alt"></span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </td>
</tr>