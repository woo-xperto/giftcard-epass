<?php

class Epasscard_Giftcard_Admin {

    public function init_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }
    public function enqueue_admin_assets( $hook ) {
        // Only load on our plugin's admin page
        // if ( $hook !== 'toplevel_page_epasscard-giftcard' ) {
        //     return;
        // }

        // Enqueue CSS
        wp_enqueue_style(
            'epasscard-admin-style',
            EPASSCARD_EXTENSION_PLUGIN_URL . 'assets/admin-style.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'epasscard-admin-script',
            EPASSCARD_EXTENSION_PLUGIN_URL . 'assets/admin-script.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        //Localize for ajax
        wp_localize_script('epasscard-admin-script', 'egw_obj', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('wodgc_nonce_action'),
                ]);

    }
}
