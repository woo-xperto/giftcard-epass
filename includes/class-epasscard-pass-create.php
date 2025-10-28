<?php
class Gift_To_Epasscard_Pass_Create
{
    public function __construct()
    {
        //Pass generate
        add_action('wodgc_giftcard_created', [$this, 'wodgc_generate_pass'], 10, 2);
        add_action('wodgc_giftcard_created_manual', [$this, 'wodgc_generate_pass'], 10, 2);

        //Pass create by admin
        add_action('wp_ajax_wodgc_pass_create_by_admin', [$this, 'wodgc_pass_create_by_admin']);
        add_action('wp_ajax_nopriv_wodgc_pass_create_by_admin', [$this, 'wodgc_pass_create_by_admin']);
    }

    //Pass create in Epasscard
    public function wodgc_generate_pass($giftcard_id, $identifier) {
        global $wpdb;
        $mapping = get_option('giftcard_field_mapping', []);
        
        $giftcard_table = $wpdb->prefix . 'wx_gift_cards';
        //phpcs:ignore
        $query = $wpdb->prepare("SELECT * FROM {$giftcard_table} WHERE id = %d", $giftcard_id);
        //phpcs:ignore
        $giftcard = $wpdb->get_row($query, ARRAY_A);

        if (!$giftcard) {
            return;
        }

        $uid = get_option('mapped_template_uid', '');
        $api_key = get_option('epasscard_api_key', '');
        $api_url = 'https://api.epasscard.com/api/pass-template/template-details/' . $uid;


        $response = wp_remote_get($api_url, [
            'headers' => ['x-api-key' => $api_key],
        ]);

        if (is_wp_error($response)) {
            return;
        }

        $passDetails = json_decode(wp_remote_retrieve_body($response), true);

        if (
            empty($passDetails) ||
            !isset($passDetails['additionFields']) ||
            !is_array($passDetails['additionFields']) ||
            empty($passDetails['additionFields'])
        ) {
            return;
        }

        $templateName = $passDetails['template_name'] ?? '';
        $passLimit = $passDetails['templateInformation']['pass_limit'] ?? 0;
        $total_pass = $passDetails["total_pass"] ?? 0;

        // CREATE
        if ($identifier === "create_giftcard" && $passLimit > $total_pass) {
            $mappedData = $this->wodgc_map_giftcard_fields($giftcard, $mapping);

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

            $api_url = 'https://api.epasscard.com/api/wallet-pass/save-field-value/' . $uid;
            $body = ['additionalFieldsValue' => $modifiedFields];

            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $api_key,
                ],
                'body' => wp_json_encode($body),
                'timeout' => 30,
            ];

            $response = wp_remote_post($api_url, $args);
            $response_body = json_decode($response['body'], true);

            $epassData = [
                'fields' => $modifiedFields,
                'passUid' => $response_body['passUid'],
                'passLink' => $response_body['passLink'],
            ];
        }

        // UPDATE
        if ($identifier === "update_giftcard") {
            
            $mappedData = $this->wodgc_map_giftcard_fields($giftcard, $mapping);

            $epassDataJson = $giftcard['epass_data'];
            $epassData = json_decode($epassDataJson, true);
            $singlePassUid = $epassData['passUid'] ?? null;

            $fieldsForPut = [];
            foreach ($passDetails['additionFields'] as $field) {
                $fieldName = $field['field_name'];
                $fieldsForPut[] = [
                    'field_name' => $field['field_name'],
                    'field_value' => $mappedData[$fieldName] ?? '',
                    'uid' => $field['uid'],
                    'field_type' => $field['field_type'],
                    'required' => $field['required'],
                ];
            }

            $putBody = [
                'fields' => $fieldsForPut,
                'passUid' => $singlePassUid,
            ];

            $args = [
                'method' => 'PUT',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $api_key,
                ],
                'body' => wp_json_encode($putBody),
                'timeout' => 30,
            ];

            $response = wp_remote_request('https://api.epasscard.com/api/wallet-pass/update-pass/', $args);
            $response_body = json_decode($response['body'], true);

            $epassData = [
                'fields' => $fieldsForPut,
                'passUid' => $singlePassUid,
                'passLink' => $response_body['passLink'],
            ];
        }

        // Final DB update
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            //phpcs:ignore
            $wpdb->update(
                $giftcard_table,
                ['epass_data' => wp_json_encode($epassData)],
                ['id' => $giftcard_id],
                ['%s'],
                ['%d']
            );
        }
    }

    // AJAX handler for admin creation
    public function wodgc_pass_create_by_admin() {
        check_ajax_referer('wodgc_nonce_action', 'nonce');

        $giftcard_id = isset($_POST['giftcard_id']) ? sanitize_text_field(wp_unslash($_POST['giftcard_id'])) : '';
        if ($giftcard_id) {
            $this->wodgc_generate_pass($giftcard_id, "create_giftcard");
        }
    }

    // Reusable field mapping function
    private function wodgc_map_giftcard_fields($giftcard, $mapping) {
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

        return $mappedData;
    }

}