<?php

class EGE_Admin {

    public function ege_init_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'ege_admin_assets' ] );
    }
    public function ege_admin_assets( $hook ) {
        // Only load on our plugin's admin page
        // if ( $hook !== 'toplevel_page_epasscard-giftcard' ) {
        //     return;
        // }

        // Enqueue CSS
        wp_enqueue_style(
            'ege-admin-style',
            EGE_PLUGIN_URL . 'assets/admin-style.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'ege-admin-script',
            EGE_PLUGIN_URL . 'assets/admin-script.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        //Localize for ajax
        wp_localize_script('ege-admin-script', 'ege_obj', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('ege_nonce_action'),
                ]);

    }
}
