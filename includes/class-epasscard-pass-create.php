<?php
class Gift_To_Epasscard_Pass_Create
{
    public function __construct()
    {
        //Pass generate
        add_action('wodgc_giftcard_created', [$this, 'wodgc_generate_pass']);
        add_action('wodgc_giftcard_created_manual', [$this, 'wodgc_generate_pass']);
    }

    //Pass create in Epasscard
    public function wodgc_generate_pass($giftcard_id)
    {
        global $wpdb;
        $mapping = get_option('giftcard_field_mapping', []);
        $giftcard_table = esc_sql($wpdb->prefix . 'wx_gift_cards');
        $query = $wpdb->prepare(
            // phpcs:ignore
            "SELECT * FROM {$giftcard_table} WHERE id = %d",
            $giftcard_id
        );
        // phpcs:ignore
        $giftcard = $wpdb->get_row($query, ARRAY_A);

        if (!$giftcard) {
            return; // Exit if giftcard not found
        }

        $fieldMapping = [
            1 => 'giftcard_number',
            2 => 'product_id',
            3 => 'initial_amount',
            4 => 'current_amount',
            5 => 'expiry_date',
            6 => 'receipent_email',
            7 => 'receipent_name',
            8 => 'sender_name',
            9 => 'message',
            10 => 'preferred_date_time',
        ];

        $mappedData = [];
        foreach ($mapping as $key => $selectValue) {
            $mappedData[$key] = isset($fieldMapping[$selectValue], $giftcard[$fieldMapping[$selectValue]])
                ? $giftcard[$fieldMapping[$selectValue]]
                : '';
        }

        $uid = get_option('mapped_template_uid', '');
        $api_key = get_option('epasscard_api_key', '');
        $api_url = 'https://api.epasscard.com/api/pass-template/template-details/' . $uid;

        $response = wp_remote_get($api_url, [
            'headers' => ['x-api-key' => $api_key],
        ]);

        if (is_wp_error($response)) {
            return; // Exit silently if API fails
        }

        $passDetails = json_decode(wp_remote_retrieve_body($response), true);

        // Validate required data before proceeding
        if (
            empty($passDetails) ||
            !isset($passDetails['additionFields']) ||
            !is_array($passDetails['additionFields']) ||
            empty($passDetails['additionFields'])
        ) {
            return; // Exit if required data is missing
        }

        $templateName = $passDetails['template_name'] ?? '';
        $passLimit = $passDetails['templateInformation']['pass_limit'] ?? 0;
        $total_pass = $passDetails["total_pass"] ?? 0;

        if (isset($passLimit) && $passLimit > $total_pass) {

            $modifiedFields = [];
            foreach ($passDetails['additionFields'] as $field) {
                $fieldName = $field['field_name'];
                $modifiedFields[] = [
                    'id' => $field['pass_id'],
                    'uid' => $field['uid'],
                    'field_name' => $field['field_name'],
                    'field_type' => $field['field_type'],
                    'required' => $field['required'],
                    'name' => $templateName,
                    'pass_limit' => $passLimit,
                    'org_id' => $field['org_id'],
                    'fieldValue' => $mappedData[$fieldName] ?? '',
                ];
            }

            $passDetails['additionFields'] = $modifiedFields;

            $save_url = 'https://api.epasscard.com/api/wallet-pass/save-field-value/' . $uid;
            $body = ['additionalFieldsValue' => $modifiedFields];

            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $api_key,
                ],
                'body' => wp_json_encode($body),
                'timeout' => 30,
            ];

            $response = wp_remote_post($save_url, $args);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                // phpcs:ignore
                $wpdb->update(
                    $giftcard_table,
                    ['epass_data' => wp_json_encode($modifiedFields)],
                    ['id' => $giftcard_id],
                    ['%s'],
                    ['%d']
                );
            }
        } else {
            return;
        }

    }

}