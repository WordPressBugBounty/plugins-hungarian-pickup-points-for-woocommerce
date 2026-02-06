<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_value = get_option($data['id'], array());
$courier_details = get_option('vp_woo_pont_kvikk_courier_details', array('couriers' => array()));

// Get EU countries
$eu_countries = WC()->countries->get_european_union_countries('eu_vat');

// Get country names for EU countries
$country_names = array();
foreach($eu_countries as $country_code) {
    $country_names[$country_code] = WC()->countries->countries[$country_code];
}

//Sort ascending
$unaccented = array_map('remove_accents', array_values($country_names));
array_multisort($unaccented, SORT_ASC, SORT_STRING | SORT_FLAG_CASE, $country_names);
$sorted_country_names = $country_names;

//Move HU to the top
$sorted_country_names = array_merge(['HU' => $sorted_country_names['HU']], $sorted_country_names);

// Collect all delivery point types and their supported countries
$delivery_point_types = array();
$country_support = array();
if(count($courier_details['couriers']) > 0) {
    foreach($courier_details['couriers'] as $courier) {
        if($courier['deliveryPointTypes']) {
            foreach($courier['deliveryPointTypes'] as $deliveryPointType) {
                if($deliveryPointType['supportedCountries'] && count($deliveryPointType['supportedCountries']) > 1) {
                    $type_key = $deliveryPointType['slug'];
                    $delivery_point_types[$type_key] = $deliveryPointType;
                    
                    // Store which countries support this delivery point type
                    foreach($deliveryPointType['supportedCountries'] as $country_code) {
                        if(!isset($country_support[$country_code])) {
                            $country_support[$country_code] = array();
                        }
                        $country_support[$country_code][] = $type_key;
                    }
                }
            }
        }
    }
}

?>

<tr class="<?php echo esc_attr( $data['row_class'] ); ?>">
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $data['id'] ); ?>"><?php echo esc_html( $data['title'] ); ?></label>
    </th>
    <td class="forminp forminp-text vp-woo-pont-kvikk-countries">
        <?php if(count($delivery_point_types) > 0): ?>
                <table class="vp-woo-pont-settings-inline-table vp-woo-pont-kvikk-countries-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Country', 'vp-woo-pont'); ?></th>
                            <?php foreach($delivery_point_types as $type_key => $type_data): ?>
                                <th>
                                    <label>
                                        <input type="checkbox" class="select-all-column" data-column="<?php echo esc_attr($type_key); ?>">
                                        <i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-<?php echo esc_attr($type_key); ?>"></i>
                                        <span><?php echo esc_html(VP_Woo_Pont_Helpers::get_provider_name($type_key, true)); ?></span>
                                    </label>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>                        
                        <?php foreach($sorted_country_names as $country_code => $country_name): ?>
                            <?php if(!isset($country_support[$country_code])) continue; ?>
                            <tr>
                                <th>
                                    <?php echo esc_html($country_name); ?>
                                </th>
                                <?php foreach($delivery_point_types as $type_key => $type_data): ?>
                                    <td>
                                        <?php 
                                        $is_supported = isset($country_support[$country_code]) && in_array($type_key, $country_support[$country_code]);
                                        $is_hungary = $country_code === 'HU';
                                        $field_name = $data['id'] . '[' . $country_code . '][' . sanitize_key($type_key) . ']';
                                        $is_checked = (isset($saved_value[$country_code][$type_key]) && $saved_value[$country_code][$type_key]);
                                        if(!$saved_value && $is_hungary) {
                                            $is_checked = true;
                                        }
                                        ?>
                                        
                                        <?php if($is_supported): ?>
                                            <input 
                                                type="checkbox" 
                                                name="<?php echo esc_attr($field_name); ?>" 
                                                id="<?php echo esc_attr($field_name); ?>" 
                                                class="country-checkbox column-<?php echo esc_attr($type_key); ?>"
                                                value="1"
                                                <?php checked($is_checked, true); ?>
                                            />
                                            <label for="<?php echo esc_attr($field_name); ?>"></label>
                                        <?php else: ?>
                                            <span style="color: #ccc;">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="description">
                    <?php esc_html_e('Show pickup points in these countries as available options.', 'vp-woo-pont'); ?>
                </p>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Handle "select all" checkboxes in headers
                $('.select-all-column').on('change', function() {
                    var column = $(this).data('column');
                    var isChecked = $(this).is(':checked');
                    
                    // Find all checkboxes in this column (excluding HU row which is always checked)
                    $('.column-' + column).not('[name*="[HU]["]').prop('checked', isChecked);
                    
                    // Update the header checkbox state based on actual column checkboxes
                    updateHeaderCheckboxState(column);
                });
                
                // Handle individual checkbox changes to update header state
                $('.country-checkbox').on('change', function() {
                    var classes = $(this).attr('class').split(' ');
                    var columnClass = classes.find(cls => cls.startsWith('column-'));
                    if (columnClass) {
                        var column = columnClass.replace('column-', '');
                        updateHeaderCheckboxState(column);
                    }
                });
                
                // Function to update header checkbox state
                function updateHeaderCheckboxState(column) {
                    var $headerCheckbox = $('.select-all-column[data-column="' + column + '"]');
                    var $columnCheckboxes = $('.column-' + column).not('[name*="[HU]["]');
                    var checkedCount = $columnCheckboxes.filter(':checked').length;
                    var totalCount = $columnCheckboxes.length;
                    
                    if (checkedCount === 0) {
                        $headerCheckbox.prop('checked', false).prop('indeterminate', false);
                    } else if (checkedCount === totalCount) {
                        $headerCheckbox.prop('checked', true).prop('indeterminate', false);
                    } else {
                        $headerCheckbox.prop('checked', false).prop('indeterminate', true);
                    }
                }
                
                // Initialize header checkbox states on page load
                $('.select-all-column').each(function() {
                    var column = $(this).data('column');
                    updateHeaderCheckboxState(column);
                });
            });
            </script>
            
        <?php else: ?>
            <p class="description">
                <?php esc_html_e('Enter your API key first to see these options.', 'vp-woo-pont'); ?>
            </p>
        <?php endif; ?>
    </td>
</tr>