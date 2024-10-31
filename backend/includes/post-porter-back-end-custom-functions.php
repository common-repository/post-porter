<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add database on the plugin installation
 */


if (!function_exists('post_porter_create_tables')) {
    function post_porter_create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . POST_PORTER_LOG_TABLE;

        $query = $wpdb->prepare("CREATE TABLE %i (id mediumint(9) NOT NULL AUTO_INCREMENT,post_porter_import_logs text NOT NULL,timestamp timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,PRIMARY KEY (id)) $charset_collate;",$table_name); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($query);
    }
}


/**
 * Drop the plugin database along with options data while deleting the plugin
 */
if (!function_exists('post_porter_drop_tables_and_delete_options')) {
    function post_porter_drop_tables_and_delete_options()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . POST_PORTER_LOG_TABLE;

        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i ",$table_name));

        delete_option('post_porter_website_url');
        delete_option('post_porter_post_type');
        delete_option('post_porter_website_post_type');
        delete_option('post_porter_is_authorized');
    }
}
/**
 * Add Custom admin menu
 */
if (!function_exists('post_porter_add_custom_admin_menu')) {
    function post_porter_add_custom_admin_menu()
    {
        add_menu_page(esc_html__('Post Importer Settings', 'post-porter'), esc_html__('Post Porter', 'post-porter'), 'manage_options', 'post-porter-import-settings', 'post_porter_import_settings', 'dashicons-migrate',   5);
        add_submenu_page('post-porter-import-settings', esc_html__('Export Key', 'post-porter'), esc_html__('Export Key', 'post-porter'), "manage_options", "post-porter-export-settings", 'post_porter_export_settings');
        add_submenu_page('post-porter-import-settings', esc_html__('Import Logs', 'post-porter'), esc_html__('Import Logs', 'post-porter'), "manage_options", "post-porter-import-logs", "post_porter_import_logs");
    }
    add_action('admin_menu', 'post_porter_add_custom_admin_menu');
}
//Action to call the admin menu function

/**
 * on the submitting of import url, data will start to import
 */
include POST_PORTER_ADMIN_DIR . '/methods/post-porter-import-function.php';

// import page settings
if (!function_exists('post_porter_import_settings')) {
    function post_porter_import_settings()
    {
        include POST_PORTER_ADMIN_DIR . '/methods/post-porter-import-settings.php';
    }
}

// export page settings
if (!function_exists('post_porter_export_settings')) {
    function post_porter_export_settings()
    {
        include POST_PORTER_ADMIN_DIR . '/methods/post-porter-export-settings.php';
    }
}

// import logs page
if (!function_exists('post_porter_import_logs')) {
    function post_porter_import_logs()
    {
        include POST_PORTER_ADMIN_DIR . '/methods/post-porter-import-logs.php';
    }
}

// Generate random key on plugin activation used to validate import data from source website
if (!function_exists('post_porter_export_key_generate')) {
    function post_porter_export_key_generate()
    {
        $token = wp_generate_password(28, false);
        update_option('post_porter_export_key', $token);
    }
    add_action('rest_api_init', 'post_porter_register_custom_rest_route');
}

// Register route for rest api to validate import request with export key
if (!function_exists('post_porter_register_custom_rest_route')) {
    function post_porter_register_custom_rest_route()
    {
        register_rest_route('wp/v1', 'post-importer', array(
            'methods' => 'GET',
            'callback' => 'post_porter_validate_key_callback',
            'permission_callback' => '__return_true',
        ));
    }
}

// callback function of validate export key
if (!function_exists('post_porter_validate_key_callback')) {
    function post_porter_validate_key_callback(WP_REST_Request $request)
    {
        $key_param = $request->get_param('key');
        $response = array(
            'success' => false,
            'data' => array(
                'message' => ''
            )
        );

        if (empty($key_param)) {
            $response['success'] = false;
            $response['data']['message'] = 'Key parameter is missing';
        } else {
            $stored_export_key = get_option('post_porter_export_key');

            if ($stored_export_key && hash_equals($stored_export_key, $key_param)) {
                $response['success'] = true;
                $response['data']['message'] = 'Valid Key';
            } else {
                $response['success'] = false;
                $response['data']['message'] = 'Invalid Key';
            }
        }
        wp_send_json($response);
    }
}

// function to add logs to database 
if (!function_exists('post_porter_add_logs')) {
    function post_porter_add_logs($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . POST_PORTER_LOG_TABLE;
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        if ($wpdb->get_var($query) != $table_name) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            post_porter_create_tables(); // Call the create table function
        }

        // Insert data into the table
        $wpdb->insert(
            $table_name,
            array(
                'post_porter_import_logs' => $data,
            ),
            array('%s')
        );
    }
}


