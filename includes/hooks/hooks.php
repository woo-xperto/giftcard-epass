<?php
add_action('wodgc_render_epasscard_tab', function ($tab) { ?>
    <div class="wodgc-epasscard-tab">


        <div class="wodgc_card wodgc_epasscard_card" id="wodgc_epasscard" style="display:<?php
        if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ege_nonce_action')) {
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
                                $per_page = 5;
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

                                $api_url = EPASSCARD_API_URL . 'get-pass-templates?page=' . $current_page;

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
                                    $api_url = EGE_API_URL.'template-details/' . $uid;
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
                            if (isset($data) && !empty($data['templates']) && is_array($data['templates'])) {
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
                                    
                                    $total_items = $data['total']['total_templates'] ?? 0;
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
 * Gift Card Actions Table Data
 */
// phpcs:ignore
function ege_giftcard_table_buitton($passLink, $gift_card_id)
{
    if ($passLink) {
        echo '<a href="' . esc_url($passLink) . '" target="_blank"><span class="dashicons dashicons-download"></span></a>';
    } else {
        echo '<a href="#" class="pass_create" data-id="' . esc_attr($gift_card_id) . '"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 64 64"><path fill="currentColor" d="M60.333 22.575L41.421 3.66C40.367 2.605 38.968 2 37.582 2c-1.005 0-1.903.317-2.602.918L3.768 29.77a5 5 0 0 0-1.764 3.643c-.056 1.494.522 2.926 1.628 4.033l22.921 22.923C27.606 61.421 28.969 62 30.391 62a5.06 5.06 0 0 0 3.836-1.768l26.848-31.215c1.479-1.718 1.153-4.547-.742-6.442M3.821 33.481a3.2 3.2 0 0 1 1.132-2.333L36.167 4.297c.362-.313.852-.479 1.415-.479c.894 0 1.849.422 2.554 1.128l1.639 1.638L6.971 38.213l-2.053-2.054c-.745-.745-1.135-1.696-1.097-2.678m55.875-5.649L32.85 59.047a3.2 3.2 0 0 1-2.459 1.135c-.937 0-1.844-.391-2.554-1.1L15.034 46.277l33.441-32.99L59.048 23.86c1.204 1.205 1.496 2.988.648 3.972"/><path fill="currentColor" d="M47.837 18.607L22.449 44.521l7.528 7.529l1.141-1.246c.764.174 1.655-.063 2.297-.719c.959-.981 1.538-2.388 1.9-3.871l19.172-20.956zm-.416 9.081c-.704.313-1.897.662-.74-.689l.779.605zm4.433-3.094c.01-.009.005-.021.011-.03l.736.736l-13.78 15.064c.541-1.106.945-2.254 1.336-3.417c.174-.522.623-.402.98-.762c.727-.734 1.429-1.481 2.137-2.211c.896-.922 1.204-2.145 2.029-3.072c.887-.994 1.738-1.564 2.302-2.679c1.658-.719 3.134-2.566 4.249-3.629M24.368 44.512l23.479-23.967l3.753 3.754c-.011.006-.022 0-.032.01c-.652.62-1.32 1.235-2.013 1.833a21 21 0 0 1-1.625 1.261c.088-.41.044-1.128-.538-1.171c-1.236-.097-2.32 2.518-.672 2.267c.119-.019.236-.065.354-.098c-.175.32-.38.624-.594.913c-.482.657-1.248 1.04-1.795 1.654c-.695.778-.967 1.894-1.711 2.705c-.329.358-2.124 2.652-2.762 2.382c-.079-.031-.191.012-.218.088q-.308.89-.612 1.789c-.035.105-.249.55-.459.991c.211-.97.238-2.018.131-2.513c-.038-.175-.246-.188-.345-.109c-.869.695-1.126 2.347-.658 3.602c-.261.268-.59.458-1.011.547c-.245-.135-.424.229-.178.365c.08.043.152.064.227.092c-.352.285-.754.564-1.186.844c.109-1.748.232-5.166-1.748-5.618c-.098-.022-.182.005-.228.088c-.866 1.522-2.105 4.713-.313 6.368c.211.195.489-.103.278-.298c-1.375-1.27-.557-3.647-.057-4.946c.775-2.006 1.593 1.85 1.635 2.443c.053.742.044 1.489.005 2.236c-2.484 1.556-5.655 3.23-5.898 6.357c-.054.706.105 1.256.389 1.66l-.033.037zm14.18-7.405q.367.745.109 1.446a2.7 2.7 0 0 1-.299.913c-.186-.802-.074-1.702.19-2.359M30.454 49.51c-.171-.408-.308-.924-.402-1.572a3.7 3.7 0 0 1 .987-1.898c1.185-1.47 2.841-2.441 4.41-3.444a19 19 0 0 1-.214 1.689zm3.357-.814a4.6 4.6 0 0 1-1.261 1.556c-.443.269-.827.344-1.162.256l3.111-3.398c-.2.54-.428 1.072-.688 1.586m2.054-6.369c.565-.37 1.111-.751 1.611-1.168c.062-.052.063-.114.044-.175c.32-.024.585-.226.803-.529c.018.029.029.064.05.092a.24.24 0 0 0 .18.109L35.74 43.73c.054-.483.097-.957.125-1.403"/></svg><span class="loading-spinner"></span></a>';
    }
}
add_action('epass_generate_button', 'ege_giftcard_table_buitton', 10, 2);
