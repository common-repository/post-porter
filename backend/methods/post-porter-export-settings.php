<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

$stored_export_key = get_option('post_porter_export_key');
?>


<!-- export page start -->

<div class="post_porter_customheader">
    <h2><?php esc_html_e('Post Export Settings', 'post-porter'); ?></h2><br>
</div>
<div class="post-porter-export-section">
    <label for="post_porter_copy_website_url"><?php esc_html_e('Copy Website URL here:', 'post-porter'); ?></label>
    <input type="text" id="post_porter_copy_website_url" value="<?php echo esc_url(get_bloginfo('url')); ?>" readonly>
    <button type="submit" id="post_porter_copyWebsite_btn" class="button button-secondary "><?php esc_html_e('Copy', 'post-porter'); ?></button>
    <label for="post_porter_copy_export_key"><?php esc_html_e('Copy Export key here:', 'post-porter'); ?></label>
    <div class="passparent">
        <input type="password" id="post_porter_copy_export_key" value="<?php echo esc_html($stored_export_key); ?>" readonly>
        <span id="post_porter_toggleVisibility" class="post-porter-eye post-porter-toggleVisibility"></span>
        <button type="submit" id="post_porter_copy_exportkey" class="button button-secondary"><?php esc_html_e('Copy', 'post-porter'); ?></button>
    </div>
</div>


<!-- text value copy script  -->
<script>
    const post_porter_copy_website_url = document.getElementById('post_porter_copy_website_url');
    const post_porter_copyWebsite_btn = document.getElementById('post_porter_copyWebsite_btn');
    const post_porter_copy_export_key = document.getElementById('post_porter_copy_export_key');
    const post_porter_toggleVisibility = document.getElementById('post_porter_toggleVisibility');
    const post_porter_copy_exportkey = document.getElementById('post_porter_copy_exportkey');
    let isHidden = true;

    post_porter_toggleVisibility.addEventListener('click', () => {
        if (isHidden) {
            post_porter_copy_export_key.type = 'text';
            post_porter_toggleVisibility.classList.remove('post-porter-eye');
            post_porter_toggleVisibility.classList.add('post-porter-eye-slash');
        } else {
            post_porter_copy_export_key.type = 'password';
            post_porter_toggleVisibility.classList.remove('post-porter-eye-slash');
            post_porter_toggleVisibility.classList.add('post-porter-eye');
        }
        isHidden = !isHidden;
    });

    post_porter_copy_exportkey.addEventListener('click', () => {
        post_porter_copy_export_key.type = 'text';
        post_porter_copy_export_key.select();
        document.execCommand('copy');
        post_porter_copy_export_key.type = 'password';
        post_porter_copy_exportkey.textContent = '<?php esc_html_e("Copied", "post-porter"); ?>';
        setTimeout(() => {
            post_porter_copy_exportkey.textContent = '<?php esc_html_e("Copy", "post-porter"); ?>';
        }, 1000);
    });

    post_porter_copyWebsite_btn.addEventListener('click', () => {
        post_porter_copy_website_url.select();
        document.execCommand('copy');
        post_porter_copyWebsite_btn.textContent = '<?php esc_html_e("Copied", "post-porter"); ?>';
        setTimeout(() => {
            post_porter_copyWebsite_btn.textContent = '<?php esc_html_e("Copy", "post-porter"); ?>';
        }, 1000);
    });
</script>