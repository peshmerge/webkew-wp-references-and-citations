<?php
/*
Plugin Name: WebKew WP References and Citations
Description: Insert and use citations inside WordPress (custom) posts and Pages similar to doing that in LateX.
Version: 1.0.1
Text Domain: webkew-wp-references-and-citations
Author: Peshmerge Morad
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Author URI: https://peshmerge.io
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly


define('WEBKEWRC_REFERENCES_PATH', plugin_dir_path(__FILE__));

// Plugin directory URL
define('WEBKEWRC_REFERENCES_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WEBKEWRC_REFERENCES_PATH . 'includes/class-webkewrc-references-citations.php';
require_once WEBKEWRC_REFERENCES_PATH . 'admin/class-webkewrc-references-citations-admin.php';
require_once WEBKEWRC_REFERENCES_PATH . 'public/class-webkewrc-references-citations-public.php';

// Initialize the plugin
function webkewrc_references_init(): void
{
    $plugin = new Webkewrc_References_Citations();
    $plugin->run();
}

webkewrc_references_init();

// Register uninstall hook
register_uninstall_hook(__FILE__, 'webkewrc_references_uninstall');

function webkewrc_references_uninstall(): void
{
    // Uninstallation tasks
    $options = get_option('webkewrc_references_citations_options');
    if (isset($options['webkewrc_delete_data_on_uninstall']) && $options['webkewrc_delete_data_on_uninstall']) {
        delete_option('webkewrc_references_citations_options');
        delete_post_meta_by_key('webkewrc_references_citations_options');

    }
}