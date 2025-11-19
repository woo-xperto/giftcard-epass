<?php
/**
 * Plugin Name: Epasscard to Gift Card Extension
 * Description: Integrates Epasscard with WooCommerce Gift Cards.
 * Version: 1.0.0
 * Author: WebCartisan
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: epasscard-giftcard-extension
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
final class EGE_Giftcard_Extension
{

    public function __construct()
    {
        $this->ege_define_constants();
        $this->ege_include_files();
        $this->ege_init_components();
    }

    private function ege_define_constants()
    {
        define('EGE_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('EGE_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('EGE_API_URL', 'https://api.epasscard.com/api/public/v1/');
    }

    private function ege_include_files()
    {
        require_once EGE_PLUGIN_PATH . 'includes/class-epasscard-giftcard-admin.php';
        require_once EGE_PLUGIN_PATH . 'includes/class-epasscard-giftcard-admin-ajax.php';
        require_once EGE_PLUGIN_PATH . 'includes/class-epasscard-pass-create.php';
        require_once EGE_PLUGIN_PATH . 'includes/hooks/hooks.php';
        require_once EGE_PLUGIN_PATH . 'includes/filters/filters.php';
    }

    private function ege_init_components()
    {
        $admin_menu = new EGE_Admin();
        $admin_menu->ege_init_hooks();

        new EGE_Ajax();
        new EGE_Pass_Create();
    }
}

add_action('plugins_loaded', function () {
    new EGE_Giftcard_Extension();
});