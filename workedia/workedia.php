<?php
/**
 * Plugin Name: Workedia
 * Description: نظام شامل لإدارة الأعضاء، الخدمات الرقمية، واستطلاعات الرأي الخاصة بـ Workedia.
 * Version: 97.3.0
 * Author: Workedia
 * Language: ar
 * Text Domain: workedia
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WORKEDIA_VERSION', '97.3.0');
define('WORKEDIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WORKEDIA_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_workedia() {
    require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-activator.php';
    Workedia_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_workedia() {
    require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-deactivator.php';
    Workedia_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_workedia');
register_deactivation_hook(__FILE__, 'deactivate_workedia');

/**
 * Core class used to maintain the plugin.
 */
require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia.php';

function run_workedia() {
    $plugin = new Workedia();
    $plugin->run();
}

run_workedia();
