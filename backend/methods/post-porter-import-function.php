<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

require_once POST_PORTER_ADMIN_DIR . '/wp-background-processing-master/wp-background-processing.php';
include POST_PORTER_ADMIN_DIR . '/methods/post-porter-import-from-api.php';

// WP Background Process library
class POST_PORTER_API_Request_Process extends POST_PORTER_Background_Process
{
    protected $action = 'post_porter_bg_processing';

    protected function task($page)
    {
        post_porter_import_posts_from_api($page);
        $page++;
        $total_page = post_porter_get_total_pages_from_api('totalpages');
        if ($page <= $total_page) {
            return $page;
        }
        if ($page > $total_page) {
            post_porter_add_logs('All posts have been imported.');
            $this->complete();
            $this->cancel();
        }
    }
}

// Create a global variable to hold an instance of the POST_PORTER_API_Request_Process class
global $api_process;
$api_process = new POST_PORTER_API_Request_Process();

// Function to initiate the API request processing
if (!function_exists('post_porter_start_api_request_processing')) {
    function post_porter_start_api_request_processing()
    {
        global $api_process;
        
        if (isset($_POST['post_porter_import']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['post_porter_import_nonce'])), 'post_porter_import_nonce')) {
            $initial_page = 1;
            $api_process->push_to_queue($initial_page);
            $api_process->save()->dispatch();
        }

        if (isset($_POST['post_porter_cancel']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['post_porter_cancel_nonce'])), 'post_porter_cancel_nonce')) {
            $api_process->cancel();
            post_porter_add_logs('You have cancelled importing data.');
        }
    }
    add_action('init', 'post_porter_start_api_request_processing');
}

// Function to check if an import process is in progress
if (!function_exists('post_porter_is_import_in_progress')) {
    function post_porter_is_import_in_progress()
    {
        global $api_process;
        return $api_process->is_processing();
    }
}

// Function to check if an import process has been canceled
if (!function_exists('post_porter_is_import_cancelled')) {
    function post_porter_is_import_cancelled()
    {
        global $api_process;
        return $api_process->is_cancelled();
    }
}
