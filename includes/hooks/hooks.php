<?php
add_action('wodgc_render_epasscard_tab', function ($tab) { ?>
    <div class="wodgc-epasscard-tab">


        <div class="wodgc_card wodgc_epasscard_card" id="wodgc_epasscard" style="display:<?php
        if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wodgc_nonce_action')) {
            echo !isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == 'wodgc_epasscard') ? 'block' : 'none';
        }
        ?>;">
            <div class="wodgc_card_header">
                <h3 class="wodgc_page_title"><?php esc_html_e('Epasscard Integration', 'epasscard-giftcard-extension'); ?>
                </h3>
                <p><?php esc_html_e('Manage your Epasscard settings, integrations, and usage from this panel.', 'epasscard-giftcard-extension'); ?>
                </p>
            </div>

            <div class="contents" style="padding:50px;">
                <div class="container-for-epasscard">

                    <?php include_once ABSPATH . 'wp-admin/includes/plugin.php';

                    if (is_plugin_active('epasscard/epasscard.php')) {

                        ?>


                        <div id="giftEpasss">
                            <div class="container">

                                <?php $mapping = get_option('giftcard_field_mapping', []);

                                // Plugin is active
                                $per_page = 10;
                                $current_page = isset($_GET['epasscard_page']) ? absint($_GET['epasscard_page']) : 1;
                                $offset = ($current_page - 1) * $per_page;
                                $organization_id = get_option('epasscard_organization_id', '');
                                $api_key = get_option('epasscard_api_key', '');

                                // Get current URL parameters
                                $current_url = add_query_arg(null, null);
                                $current_params = [];

                                $parsed_url = wp_parse_url($current_url);
                                if (isset($parsed_url['query'])) {
                                    parse_str($parsed_url['query'], $current_params);
                                }


                                //$api_url = 'https://api.epasscard.com/api/external-apis/all-templates/' . $organization_id . '?limit=' . $per_page . '&offset=' . $offset . '&search=';
                                $api_url = EPASSCARD_API_URL.'get-pass-templates?page=' . $current_page;

                                $response = wp_remote_get($api_url, [
                                    'headers' => [
                                        'x-api-key' => $api_key,
                                    ],
                                ]);

                                if (is_wp_error($response)) {
                                    return;
                                }

                                $data = json_decode(wp_remote_retrieve_body($response), true);

                                // Check if pass-uid parameter is set
                                // phpcs:ignore
                                $raw_uid = wp_unslash($_GET['pass-uid'] ?? '');
                                $uid = sanitize_text_field($raw_uid);

                                if (!empty($uid)) {
                                    $api_url = 'https://api.epasscard.com/api/pass-template/template-details/' . $uid;
                                    $api_key = get_option('epasscard_api_key', '');

                                    $response = wp_remote_get($api_url, [
                                        'headers' => [
                                            'x-api-key' => $api_key,
                                        ],
                                    ]);

                                    if (is_wp_error($response)) {
                                        wp_send_json_error('API Request Failed');
                                    }

                                    $passDetails = json_decode(wp_remote_retrieve_body($response), true);

                                    $fieldNames = [];
                                    if (!empty($passDetails['additionFields'])) {
                                        foreach ($passDetails['additionFields'] as $field) {
                                            $fieldNames[] = $field['field_name'];
                                        }
                                    }
                                    ?>

                                    <div class="content" pass-template-id="<?php echo esc_attr($passDetails['template_id']); ?>"
                                        mapped-pass-uid="<?php echo esc_attr($uid); ?>">
                                        <div class="fields-container">
                                            <h2>Field Names to Map</h2>
                                            <div class="field-list" id="fieldList">
                                                <?php
                                                $options = [
                                                    "Gift Card Code",
                                                    "Select Gift Card Product",
                                                    "Initial Amount",
                                                    "Current Balance",
                                                    "Expire Date",
                                                    "Recipient's Email",
                                                    "Recipient's Name",
                                                    "Sender Name",
                                                    "Message",
                                                    "Preferred date time",
                                                ];


                                                foreach ($fieldNames as $index => $fieldName) {
                                                    $fieldId = str_replace(' ', '_', strtolower($fieldName));

                                                    echo '<div class="field-item" data-field="' . esc_attr($fieldName) . '">';
                                                    echo '<span>' . esc_html($fieldName) . '</span>';
                                                    echo '<select name="' . esc_attr($fieldId) . '" id="' . esc_attr($fieldId) . '">';
                                                    echo '<option value="">' . esc_html('— Select One —') . '</option>';

                                                    foreach ($options as $value => $option) {
                                                        $optionValue = $value + 1;
                                                        $isSelected = (isset($mapping[$fieldName]) && $mapping[$fieldName] == $optionValue) ? ' selected' : '';
                                                        echo '<option value="' . esc_attr($optionValue) . '"' . esc_attr($isSelected) . '>' . esc_html($option) . '</option>';
                                                    }

                                                    echo '</select>';
                                                    echo '</div>';
                                                }


                                                ?>
                                            </div>
                                        </div>
                                        <div class="action-buttons">
                                            <button id="generateMapping" class="success">Generate Mapping <span
                                                    class="loading-spinner"></span></button>
                                        </div>

                                        <div class="status info" id="statusMessage">
                                            Select a field name to begin mapping
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                                } else { ?>
                            <div id="epasscard-template-wrap"><?php
                            if (isset($data) && ! empty($data['templates']) && is_array($data['templates'])) {
                                ?>
                                    <div id="epasscard-template-wrap"><?php
                                    foreach ($data['templates'] as $template) {
                                        // Create URL for map link
                                        $map_url = add_query_arg('pass-uid', $template['uid'], $current_url); ?>
                                            <div class="epasscard-template">
                                                <div class="top-part">
                                                    <h4 class="epasscard-title"><?php echo esc_html($template['name']); ?></h4>
                                                    <div>
                                                        <div class="epasscard-dropdown-container">
                                                            <a href="<?php echo esc_url($map_url); ?>"><span
                                                                    pass-id="<?php echo esc_attr($template['id']); ?>"
                                                                    pass-uid="<?php echo esc_attr($template['uid']); ?>">Map</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bottom-part">
                                                    <div class="left-part">
                                                        <span>Active versions:
                                                            <span class="app-logo">
                                                                <img src="<?php echo esc_url(EPASSCARD_PLUGIN_URL . 'assets/img/icons/apple-icon.svg'); ?>"
                                                                    class="w-6 h-6" alt="icon">
                                                                <img src="<?php echo esc_url(EPASSCARD_PLUGIN_URL . 'assets/img/icons/android-icon.svg'); ?>"
                                                                    class="w-6 h-6" alt="icon">
                                                            </span>
                                                        </span>
                                                        <div class="epasscard-status">
                                                            <span>PASSES: <?php echo esc_html($template['total_pass']); ?></span>
                                                            <span>ACTIVE: <?php echo esc_html($template['active']); ?></span>
                                                            <span>INACTIVE: <?php echo esc_html($template['in_active']); ?></span>
                                                        </div>
                                                        <div class="maximum-passes">Maximum number of passes:
                                                            <span><?php echo esc_html($template['pass_limit']); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <?php


                                    $total_items = $data['data']['count'] ?? 0;
                                    $total_pages = ceil($total_items / $per_page);

                                    // Show pagination only when needed
                                    $show_pagination = $total_items > $per_page; // Only show if items exceed per page limit
                    
                                    if ($show_pagination && $total_pages > 1) {
                                        echo '<div class="epasscard-pagination">';

                                        // Previous page link (show only if not on first page)
                                        if ($current_page > 1) {
                                            $prev_url = add_query_arg('epasscard_page', $current_page - 1);
                                            echo '<a href="' . esc_url($prev_url) . '" class="epasscard-page-link">&laquo; Previous</a>';
                                        }

                                        // Numbered page links
                                        $start_page = max(1, $current_page - 2);
                                        $end_page = min($total_pages, $current_page + 2);

                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $page_url = add_query_arg('epasscard_page', $i);
                                            $active_class = ($i === $current_page) ? ' active' : '';
                                            echo '<a href="' . esc_url($page_url) . '" class="epasscard-page-link' . esc_attr($active_class) . '">' . esc_html($i) . '</a>';
                                        }

                                        // Next page link (show only if not on last page)
                                        if ($current_page < $total_pages) {
                                            $next_url = add_query_arg('epasscard_page', $current_page + 1);
                                            echo '<a href="' . esc_url($next_url) . '" class="epasscard-page-link">Next &raquo;</a>';
                                        }

                                        echo '</div>';
                                    }

                            } else {
                                echo '<div class="notice notice-warning"><p>No templates found or API request failed.</p></div>';
                            }
                                }
                    } else { //phpcs:ignore
                        echo '<div class="epasscard-section" style="margin-bottom:20px;padding:20px;border:1px solid #ddd;background-color:#fff3cd;color:#856404;border-radius:5px;">
    <strong>' . esc_html__('Notice:', 'epasscard-giftcard-extension') . '</strong> ' . esc_html__('The Epasscard plugin is required for Epasscard integration. Please install and activate it to use this feature.', 'epasscard-giftcard-extension') . '
    </div>';
                    } ?>
                    </div>
                </div>
            </div>
        </div>
    </div><?php
});


/**
 * Gift Card Actions Table Heading
 */
function send_giftcard_table_heading()
{
    ?>
    <th scope="col">
        <?php 
        //phpcs:ignore
        esc_html_e('Epass Action', 'gift-card-wooxperto-llc'); ?>
    </th>
    <?php
}
add_action('epass_generate_button_heading', 'send_giftcard_table_heading');


/**
 * Gift Card Actions Table Data
 */
function send_giftcard_table_buitton($passLink, $gift_card_id)
{
    ?>
    <td>
        <?php if ($passLink) {
            echo '<a href="'.esc_url($passLink).'" target="_blank">Download Pass</a>';
        } else {
            echo '<a href="#" class="pass_create" data-id="'.esc_attr($gift_card_id).'">Create Pass <span class="loading-spinner"></span></a>';
        }
        ?>
    </td>
    <?php
}
add_action('epass_generate_button', 'send_giftcard_table_buitton', 10, 2);
