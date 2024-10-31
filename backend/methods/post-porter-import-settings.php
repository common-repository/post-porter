<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// validate importing website using export key
if (isset($_POST['post_porter_validate']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['post_porter_validate_nonce'])), 'post_porter_validate_nonce')) {
    update_option('post_porter_website_url', sanitize_text_field($_POST['post_porter_website_url']));
    $authorized_key_response = post_porter_is_authorized(sanitize_text_field($_POST['post_porter_export_key']));
    // show message based on api response status code
    if ($authorized_key_response === true) {
        update_option('post_porter_is_authorized', sanitize_text_field($authorized_key_response));
    } elseif ($authorized_key_response === 401) {
        echo sprintf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__('Missing authorization header, if you have any security plugin for authorization of rest api please deactivate it.', 'post-porter'));
    } elseif ($authorized_key_response === false) {
        echo sprintf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__('Your website URL or export key is incorrect.', 'post-porter'));
    }
}

// change importing website (source website)
if (isset($_POST['post_porter_change_website']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['post_porter_change_website_nonce'])), 'post_porter_change_website_nonce')) {
    delete_option('post_porter_is_authorized');
    echo '<script>window.location.href = window.location.href;</script>';
}

$post_porter_website_url = get_option('post_porter_website_url');
$post_porter_post_type = get_option('post_porter_post_type');

$settings_saved = isset($_POST['post_porter_save']);

// save settings data and validate it
if ($settings_saved || (isset($_POST['post_porter_validate']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['post_porter_save_nonce'])), 'post_porter_save_nonce'))) {

    update_option('post_porter_post_type', sanitize_text_field($_POST['post_porter_post_type']));
    update_option('post_porter_website_post_type', sanitize_text_field($_POST['post_porter_website_post_type']));

    $response = post_porter_check_post_type_has_data($_POST['post_porter_website_post_type']);
    if ($response === true) {
        echo sprintf('<div class="notice notice-info is-dismissible"><p>%s</p></div>', esc_html__('Author\'s email, first name, last name, and nickname cannot be imported. You need to set them manually.', 'post-porter'));
        $total_pages = post_porter_get_total_pages_from_api('totalpages');
        if ($total_pages > 0) {
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(
                /* translators: %s: post count */
                esc_html__('Total %s posts will be imported. ', 'post-porter'),
                esc_html($total_pages)
            ) . '<br> ' . esc_html__('Click on Start Import button to start importing posts.', 'post-porter') . '</p></div>';
        }
        $missing_texonomy = post_porter_get_missing_taxonomies(sanitize_text_field($_POST['post_porter_website_post_type']));
        if ($missing_texonomy) {
            echo '<div class="notice notice-warning is-dismissible">';
            printf(
                /* translators: %1$s: Missing taxonomy, %2$s: Post type */
                esc_html__('Your website doesn\'t have %1$s as a registered taxonomy with post type %2$s. Please register it before importing data to avoid data loss.', 'post-porter'),
                esc_html($missing_texonomy),
                esc_html(sanitize_text_field($_POST['post_porter_post_type']))
            );
            echo '</div>';
        }
        $post_porter_website_url = get_option('post_porter_website_url');
        $post_porter_post_type = sanitize_text_field($_POST['post_porter_post_type']);
    } elseif ($response === 401) {
        echo sprintf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__('Missing authorization header, if you have any security plugin for authorization of rest api please deactivate it.', 'post-porter'));
    } elseif ($response === 204) {
        echo sprintf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__('Your website has no data to import for selected post type.', 'post-porter'));
    }
}

// show message on click of import button
if (isset($_POST['post_porter_import']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['post_porter_import_nonce'])), 'post_porter_import_nonce')) {
    $log_detail = esc_html__('Import process started.', 'post-porter');
    post_porter_add_logs($log_detail);
?>
    <script>
        window.setTimeout(function() {
            window.location.href = "<?php echo esc_url(wp_json_encode(get_bloginfo('url') . "/wp-admin/admin.php?page=post-porter-import-logs")); ?>";
        }, 10);
    </script>
<?php
}

$post_types_api_url = $post_porter_website_url . '/wp-json/wp/v2/types';
$post_types_response = wp_safe_remote_get($post_types_api_url);

if (!is_wp_error($post_types_response)) {
    $post_types_data = json_decode(wp_remote_retrieve_body($post_types_response), true);
}

$is_authorized = get_option('post_porter_is_authorized');
$isImportInProgressAndNotCancelled = post_porter_is_import_in_progress() && !post_porter_is_import_cancelled();
//if importing website has been authorized then show form with import button along with select box to select post type
if ($is_authorized) {
    $excluded_post_types = array('page', 'feedback', 'attachment', 'nav_menu_item', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 'wp_font_family', 'wp_font_face');
?>
    <div class="post_porter_wrap">
        <div class="post_porter_customheader">
            <h2><?php esc_html_e('Post Import Settings', 'post-porter'); ?></h2>
        </div>
        <div class="post-porter-import-form">
            <form method="post" action="">
                <label for="website_url"><?php esc_html_e('Website URL:', 'post-porter'); ?></label>
                <input type="text" id="website_url" name="post_porter_website_url" value="<?php echo esc_attr($post_porter_website_url); ?>" class="post-porter-w33" disabled>
                <button button type="submit" name="post_porter_change_website" class="button button-secondary post-porter-change-website-btn" <?php echo ($isImportInProgressAndNotCancelled) ? "disabled" : " "; ?>><?php esc_html_e('Change Website', 'post-porter'); ?></button>
                <input type="hidden" name="post_porter_change_website_nonce" value="<?php echo esc_attr(wp_create_nonce('post_porter_change_website_nonce')); ?>">
                <!-- if source website has data to import then show select box -->
                <?php if (!empty($post_types_data) && post_porter_check_post_type_has_data('post') == true) { ?>
                    <label for="post_type"><?php esc_html_e('Select Post Type From You Want To Import Data:', 'post-porter'); ?></label>
                    <select id="post_type" class="checkPostType post-porter-w100" name="post_porter_website_post_type" <?php echo ($isImportInProgressAndNotCancelled) ? "disabled" : " "; ?>>
                        <?php
                        foreach ($post_types_data as $post_type_data) {
                            if ($post_type_data && !in_array($post_type_data['slug'], $excluded_post_types)) {
                                if (post_porter_check_post_type_has_data($post_type_data['slug']) == true) {
                                    $selected_post_type = get_option('post_porter_website_post_type');
                                    $selected = ($selected_post_type === $post_type_data['slug']) ? 'selected' : '';
                                    echo sprintf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($post_type_data['slug']),
                                        esc_attr($selected),
                                        esc_html($post_type_data['name'])
                                    );
                                }
                            }
                        }
                        ?>
                    </select>
                    <label for="post_type"><?php esc_html_e('Select Post Type In Which You Want To Import Data:', 'post-porter'); ?></label>
                    <?php
                    $post_types = get_post_types(array('public' => true), 'objects');
                    ?>
                    <!-- select box to show post type registered with destination website  -->
                    <select id="post_type" name="post_porter_post_type" class="post-porter-w100" required <?php echo ($isImportInProgressAndNotCancelled) ? "disabled" : " "; ?>>
                        <?php
                        foreach ($post_types as $post_type_obj) {
                            if (!in_array($post_type_obj->name, $excluded_post_types)) {
                                $selected = ($post_porter_post_type === $post_type_obj->name) ? 'selected' : '';
                                echo sprintf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($post_type_obj->name),
                                    esc_attr($selected),
                                    esc_html($post_type_obj->label)
                                );
                            }
                        }
                        ?>
                    </select>
                <?php
                }
                ?>
                <!-- show buttons of save settings, import and cancel import based on conditions -->
                <?php if (!$settings_saved) { ?>
                    <button type="submit" name="post_porter_save" class="button button-primary post-porter-btn" <?php echo ($isImportInProgressAndNotCancelled) ? "disabled" : " "; ?>><?php esc_html_e('Save Settings', 'post-porter'); ?></button>
                <?php } elseif ($settings_saved) {
                ?>
                    <input type="hidden" name="post_porter_save_nonce" value="<?php echo esc_attr(wp_create_nonce('post_porter_save_nonce')); ?>">
                    <button type="submit" name="post_porter_save" class="button button-primary post-porter-btn" <?php echo ($isImportInProgressAndNotCancelled) ? "disabled" : " "; ?>><?php esc_html_e('Save Settings', 'post-porter'); ?></button>
                    <?php if ($response === true) { ?>
                        <input type="hidden" name="post_porter_import_nonce" value="<?php echo esc_attr(wp_create_nonce('post_porter_import_nonce')); ?>">
                        <button type="submit" name="post_porter_import" class="button button-primary post-porter-btn post-porter-ml-20" <?php echo ($isImportInProgressAndNotCancelled) ? "disabled" : " "; ?>><?php esc_html_e('Start Import', 'post-porter'); ?></button>
                    <?php } ?>
                <?php }
                ?>
            </form>
        </div>
    </div>
    <?php if ($isImportInProgressAndNotCancelled) { ?>
        <div class="post-porter-import-wrap post-porter-overlay">
            <div class="post-porter-popup">
                <div class="post-porter-importing">
                    <p>&nbsp;<?php esc_html_e('Importing', 'post-porter'); ?><span></span></p>
                </div>
                <form action="" class="post-porter-cancel-form" method="post">
                    <input type="hidden" name="post_porter_cancel_nonce" value="<?php echo esc_attr(wp_create_nonce('post_porter_cancel_nonce')); ?>">
                    <button type="submit" name="post_porter_cancel" class="button button-secondary post-porter-btn"><?php esc_html_e('Cancel Import', 'post-porter') ?></button>
                </form>
            </div>
        </div>
        <!-- reload page every 10 sec to check latest value of $isImportInProgressAndNotCancelled to get status of background process  -->
        <script>
            function reloadPage() {
                location.reload();
            }
            setInterval(reloadPage, 10000);
        </script>
    <?php
    }
    //if importing website is not authorized then show authorization form with website url and export key text fields
} else {
    ?>
    <div class="post_porter_wrap">
        <div class="post_porter_customheader">
            <h2><?php esc_html_e('Post Import Settings', 'post-porter'); ?></h2>
        </div>
        <div class="post-porter-validate-form">
            <form method="post" action="">
                <label for="website_url"><?php esc_html_e('Website URL:', 'post-porter'); ?></label>
                <input type="text" id="website_url" name="post_porter_website_url" value="<?php echo esc_attr($post_porter_website_url); ?>" placeholder="<?php esc_html_e('Enter your website URL', 'post-porter'); ?>" required>
                <span id="url_validation_message" class="post-porter-red"></span>
                <label for="post_porter_export_key"><?php esc_html_e('Export key:', 'post-porter'); ?></label>
                <input type="text" id="post_porter_export_key" name="post_porter_export_key" placeholder="<?php esc_html_e('Enter your export key', 'post-porter'); ?>" required>
                <input type="hidden" name="post_porter_validate_nonce" value="<?php echo esc_attr(wp_create_nonce('post_porter_validate_nonce')); ?>">
                <button type="submit" name="post_porter_validate" class="button button-primary post-porter-btn"><?php esc_html_e('Submit', 'post-porter'); ?></button>
            </form>
        </div>
    </div>
    <script>
        //form validation for website input field
        document.querySelector('form').addEventListener('submit', function(event) {
            const urlInput = document.getElementById('website_url');
            const urlValidationMessage = document.getElementById('url_validation_message');
            const urlValue = urlInput.value;

            // Check if the URL starts with "https://" or "http://"
            if (!urlValue.startsWith('https://') && !urlValue.startsWith('http://')) {
                event.preventDefault(); // Prevent form submission
                urlValidationMessage.textContent = '<?php esc_html_e("Website URL must start with 'https://' or 'http://'", "post-porter"); ?>';
                urlInput.style.borderColor = 'red';
            } else {
                urlValidationMessage.textContent = ''; // Clear any previous error message
                urlInput.style.borderColor = ''; // Clear the red border
            }
        });
    </script>
<?php }
?>