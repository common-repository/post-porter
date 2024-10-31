<?php
/*
Plugin Name:    Post Porter
Plugin URI:     https://wordpress.org/plugins/post-porter/
Description:    Import and Export any post type from one website to another WordPress website using rest api.
Version:        1.0.1
Requires at least: 6.2
Requires PHP: 7.0
License:        GPL v2 or later
License URI:    http://www.gnu.org/licenses/gpl-2.0.txt
Author:         WebOccult Technologies Pvt Ltd
Author URI:     https://www.weboccult.com
Text Domain:    post-porter
Domain Path:    /languages
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!defined('POST_PORTER_DIR')) {
    define('POST_PORTER_DIR', dirname(__FILE__)); // plugin dir
}
if (!defined('POST_PORTER_URL')) {
    define('POST_PORTER_URL', plugin_dir_url(__FILE__)); // plugin url
}
if (!defined('POST_PORTER_BASENAME')) {
    define('POST_PORTER_BASENAME', 'post-porter');  // plugin base name
}
if (!defined('POST_PORTER_ADMIN_DIR')) {
    define('POST_PORTER_ADMIN_DIR', POST_PORTER_DIR . '/backend'); // plugin admin dir
}
if (!defined('POST_PORTER_ADMIN_URL')) {
    define('POST_PORTER_ADMIN_URL', POST_PORTER_DIR . 'backend'); // plugin admin url
}
if (!defined('POST_PORTER_LOG_TABLE')) {
    define('POST_PORTER_LOG_TABLE', 'post_porter_logs'); // define the table name - to store logs details
}

//include custom function file for backend
include POST_PORTER_ADMIN_DIR . '/includes/post-porter-back-end-custom-functions.php';

if (!function_exists('post_porter_load_textdomain')) {
    function post_porter_load_textdomain()
    {

        load_plugin_textdomain('post-porter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    add_action('init', 'post_porter_load_textdomain');
}

if (!function_exists('post_porter_load_scripts')) {
    function post_porter_load_scripts()
    {
        wp_enqueue_style('post_porter_custom_css', POST_PORTER_URL . 'backend/assets/public-style.css', array(), '1.0.0');
    }
    add_action('admin_enqueue_scripts', 'post_porter_load_scripts');
}

/**
 * Activation Hook
 *
 * Register plugin activation hook.
 */
register_activation_hook(__FILE__, 'post_porter_install');
register_activation_hook(__FILE__, 'post_porter_export_key_generate');


/**
 * Uninstall Hook
 *
 * Register plugin deactivation hook.
 */
register_uninstall_hook(__FILE__, 'post_porter_uninstall');

/**
 * Plugin Setup (On Activation)
 *
 * Does the initial setup,
 * stest default values for the plugin options.
 */
if (!function_exists('post_porter_install')) {
    function post_porter_install()
    {

        //create custom table for plugin
        post_porter_create_tables();

        //Need to call when custom post type is being used in plugin
        flush_rewrite_rules();
    }
}

/**
 * Plugin Setup (On Uninstall)
 *
 * Delete plugin options.
 */
if (!function_exists('post_porter_uninstall')) {

    function post_porter_uninstall()
    {
        //drop custom table for plugin and delete option data of plugin
        post_porter_drop_tables_and_delete_options();
    }
}
