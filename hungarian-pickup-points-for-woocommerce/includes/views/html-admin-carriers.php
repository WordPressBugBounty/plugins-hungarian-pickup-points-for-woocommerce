<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shipping_methods = VP_Woo_Pont_Helpers::get_available_shipping_methods();
$home_delivery_pairs = array_map(function($item) {
    $parts = explode('_', $item);
    return $parts[0];
}, get_option('vp_woo_pont_home_delivery', array()));

?>
            <tr valign="top">
                <td class="vp-woo-pont-carriers-wrapper" colspan="2">
                    <table class="vp-woo-pont-carriers wc_gateways widefat" cellspacing="0">
                        <thead>
                            <tr>
                                <?php
                                $columns = array(
                                    'icon'        => '',
                                    'name'        => __( 'Carrier', 'vp-woo-pont' ),
                                    'api_status' => __( 'Status', 'vp-woo-pont' ),
                                    'shipping_methods' => __( 'Shipping methods', 'vp-woo-pont' ),
                                    'action'      => '',
                                );
                                ?>
                                <th></th>
                                <th><?php esc_html_e('Carrier', 'vp-woo-pont'); ?></th>
                                <th><?php esc_html_e('Status', 'vp-woo-pont'); ?></th>
                                <th><?php esc_html_e('Shipping methods', 'vp-woo-pont'); ?> <?php echo wc_help_tip(__('If an order has this shipping method, it will use the selected carrier to generate a shipping label by default.', 'vp-woo-pont')); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( self::get_carriers() as $carrier_id => $carrier_label ) {
    
                                echo '<tr data-carrier_id="' . esc_attr( $carrier_id ) . '">';
    
                                foreach ( $columns as $key => $column ) {
                                    $width = '';    
                                    if ( in_array( $key, array( 'icon', 'enabled', 'action' ), true ) ) {
                                        $width = '1%';
                                    }
    
                                    echo '<td class="' . esc_attr( $key ) . '" width="' . esc_attr( $width ) . '">';
    
                                    switch ( $key ) {
                                        case 'icon':
                                            ?>
                                            <i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-<?php echo esc_attr( $carrier_id ); ?>"></i>
                                            <?php
                                            break;
                                        case 'name':
                                            echo '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_carriers&carrier=' . strtolower( $carrier_id ) ) ) . '">' . wp_kses_post( $carrier_label ) . '</a>';
                                            if($carrier_id == 'kvikk' && !get_option('vp_woo_pont_kvikk_courier_details')) {
                                                echo '<div><p>'.__('A Kvikk rendszerével kedvezményes áron szállíthatsz WooCommerce áruházadból a népszerű futárcégekkel egy egyszerű regisztráció után.', 'vp-woo-pont').' <a href="https://kvikk.hu/?source=plugin">Érdekel <span class="dashicons dashicons-arrow-right-alt"></span></a></p></div>';
                                            }
                                            break;
                                        case 'shipping_methods':
                                            $shipping_method_ids = array_keys($home_delivery_pairs, $carrier_id);
                                            if(count($shipping_method_ids) == 0) {
                                                echo '<span class="vp-woo-pont-shipping-method-none">' . esc_html__('Not paired', 'vp-woo-pont') . '</span>';
                                                break;
                                            } else {
                                                foreach ($shipping_method_ids as $shipping_method_id) {
                                                    if(isset($shipping_methods[$shipping_method_id])) {
                                                        echo '<span class="vp-woo-pont-shipping-method">' . esc_html($shipping_methods[$shipping_method_id]) . '</span>';
                                                    }
                                                }
                                            }
                                            break;
                                        case 'api_status':
                                            if(VP_Woo_Pont_Helpers::is_provider_configured($carrier_id)) {
                                                echo '<span class="vp-woo-pont-api-status vp-woo-pont-api-status-ok">'.__('Configured', 'vp-woo-pont').'</span>';
                                            } else {
                                                echo '<span class="vp-woo-pont-api-status vp-woo-pont-api-status-error">'.__('Inactive', 'vp-woo-pont').'</span>';
                                            }
                                            break;
                                        case 'action':
                                            echo '<a class="button help-link" aria-label="'.esc_attr(__('Find out more', 'vp-woo-pont')).'" href="https://visztpeter.me/kb-article/csomagpontok-es-cimkek/'.$carrier_id.'/" target="_blank">?</a>';
                                            echo '<a class="button" aria-label="' . esc_attr( sprintf( __( 'Manage the "%s" carrier', 'vp-woo-pont' ), $carrier_label ) ) . '" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_carriers&carrier=' . strtolower( $carrier_id ) ) ) . '">' . esc_html__( 'Manage', 'vp-woo-pont' ) . '</a>';
                                            break;
                                    }
    
                                    echo '</td>';
                                }
    
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </td>
            </tr>