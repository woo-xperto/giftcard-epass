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
final class Epasscard_Giftcard_Extension
{

    public function __construct()
    {
        $this->define_constants();
        $this->include_files();
        $this->init_components();
    }

    private function define_constants()
    {
        define('EPASSCARD_EXTENSION_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('EPASSCARD_EXTENSION_PLUGIN_PATH', plugin_dir_path(__FILE__));
    }

    private function include_files()
    {
        require_once EPASSCARD_EXTENSION_PLUGIN_PATH . 'includes/class-epasscard-giftcard-admin.php';
        require_once EPASSCARD_EXTENSION_PLUGIN_PATH . 'includes/class-epasscard-giftcard-admin-ajax.php';
        require_once EPASSCARD_EXTENSION_PLUGIN_PATH . 'includes/class-epasscard-pass-create.php';
        require_once EPASSCARD_EXTENSION_PLUGIN_PATH . 'includes/hooks/hooks.php';
        require_once EPASSCARD_EXTENSION_PLUGIN_PATH . 'includes/filters/filters.php';
    }

    private function init_components()
    {
        $admin_menu = new Epasscard_Giftcard_Admin();
        $admin_menu->init_hooks();

        new Epasscard_Giftcard_Ajax();
        new Gift_To_Epasscard_Pass_Create();
    }
}

add_action('plugins_loaded', function () {
    new Epasscard_Giftcard_Extension();
});