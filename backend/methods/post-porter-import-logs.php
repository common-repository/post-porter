<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// get data from log table
global $wpdb;
$table_name = $wpdb->prefix . POST_PORTER_LOG_TABLE;

// Nonce verification
if (isset($_POST['post_porter_clear_logs']) && wp_verify_nonce(sanitize_text_field( wp_unslash ($_POST['post_porter_clear_logs_nonce'])), 'post_porter_clear_logs_action')) {
    $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i ",$table_name));
    echo sprintf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__('Logs cleared', 'post-porter'));
}

$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM  %i ",$table_name));
echo '<div class="wrap">';

echo '<div class="post_porter_customheader"><h2>' . esc_html__('Post Import Logs', 'post-porter') . '</h2></div>';

if (!empty($results)) {
    echo '<table border="1" class="post-porter-log-table wp-list-table widefat fixed striped table-view-list posts">';
    echo '<tr><th>' . esc_html__('ID', 'post-porter') . '</th><th>' . esc_html__('Date & Time', 'post-porter') . '</th><th>' . esc_html__('Log Details', 'post-porter') . '</th></tr>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->timestamp) . '</td>';
        echo '<td>' . esc_html($row->post_porter_import_logs) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
    echo '<form method="post" action="">';
    wp_nonce_field('post_porter_clear_logs_action', 'post_porter_clear_logs_nonce');
    echo '<button type="submit" name="post_porter_clear_logs" class="button button-primary post-porter-btn">' . esc_html__('Clear Logs', 'post-porter') . '</button>';
    echo '</form>';
} else {
    echo '<div class="post-porter-import-wrap"><p>' . esc_html__('No logs found.', 'post-porter') . '</p></div>';
}

echo '</div>';
?>
