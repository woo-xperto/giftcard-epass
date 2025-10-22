<?php
class Epasscard_Giftcard_Ajax
{
    public function __construct()
    {
        // Handle AJAX request to field map with epasscard
        add_action('wp_ajax_wodgc_generate_epasscard_map', [$this, 'wodgc_generate_epasscard_map']);
        add_action('wp_ajax_nopriv_wodgc_generate_epasscard_map', [$this, 'wodgc_generate_epasscard_map']);
    }
    public function wodgc_generate_epasscard_map()
    {
        check_ajax_referer('wodgc_nonce_action', 'nonce');
        
        // phpcs:ignore
        $mapping = isset($_POST['mapping']) ? json_decode(stripslashes($_POST['mapping']), true) : [];
        $template_id = isset($_POST['template_id']) ? sanitize_text_field(wp_unslash($_POST['template_id'])) : '';
        $template_uid = isset($_POST['template_uid']) ? sanitize_text_field(wp_unslash($_POST['template_uid'])) : '';


        if (!empty($mapping)) {
            update_option('giftcard_field_mapping', $mapping);
            update_option('giftcard_template_id', $template_id);
            update_option('mapped_template_uid', $template_uid);

            wp_send_json_success('Mapping saved');
        } else {
            wp_send_json_error('No data received');
        }

        wp_die();
    }

}