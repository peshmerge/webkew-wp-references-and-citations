<?php
/*
Plugin Name: WebKew WP References and Citations
Description: Insert and use citations inside WordPress (custom) posts and Pages similar to doing that in LateX.
Version: 1.0.0
Text Domain: webkew-wp-references-citations
Author: Peshmerge Morad
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Author URI: https://peshmerge.io
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly


define('WWRC_WEBKEW_WP_REFERENCES_PATH', plugin_dir_path(__FILE__));

// Plugin directory URL
define('WWRC_WEBKEW_WP_REFERENCES_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WWRC_WEBKEW_WP_REFERENCES_PATH . 'includes/class-webkew-wp-references-citations.php';
require_once WWRC_WEBKEW_WP_REFERENCES_PATH . 'admin/class-webkew-wp-references-citations-admin.php';
require_once WWRC_WEBKEW_WP_REFERENCES_PATH . 'public/class-webkew-wp-references-citations-public.php';

// Initialize the plugin
function wwrc_webkew_wp_references_init(): void
{
    $plugin = new WebKew_WP_References();
    $plugin->run();
}

wwrc_webkew_wp_references_init();

// Register uninstall hook
register_uninstall_hook(__FILE__, 'wwrc_webkew_wp_references_uninstall');

function wwrc_webkew_wp_references_uninstall(): void
{
    // Uninstallation tasks
    $options = get_option('webkew_wp_references_citations_options');
    if (isset($options['wwrc_delete_data_on_uninstall']) && $options['wwrc_delete_data_on_uninstall']) {
        delete_option('webkew_wp_references_citations_options');
        delete_post_meta_by_key('webkew_wp_references_citations_options');

    }
}