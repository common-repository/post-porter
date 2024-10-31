<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Function to set post thumbnail
if (!function_exists('post_porter_images_handler')) {
    function post_porter_images_handler($item, $post_id)
    {
        $featured_image_url = $item['_embedded']['wp:featuredmedia']['0']['source_url'];
        if ($featured_image_url) {
            $filename = basename($featured_image_url);
            $existing_attachment = get_page_by_title($filename, OBJECT, 'attachment'); // Check for image existence in media by title

            if ($existing_attachment) {
                $attachment_id = $existing_attachment->ID;
            } else {
                $attachment_id = post_porter_image_uploader($featured_image_url, $item);
            }

            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
    }
}

// Function to replace images in content
if (!function_exists('post_porter_replace_images_from_content')) {
    function post_porter_replace_images_from_content($item)
    {
        $doc = new DOMDocument(); // Create a new DOMDocument to parse the HTML content
        $doc->loadHTML($item['content']['rendered'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD to avoid head body and doctype html from output
        $imgTags = $doc->getElementsByTagName('img'); // Get all image tags within the HTML content

        // Loop through each image tag
        foreach ($imgTags as $imgTag) {
            $imgSrc = $imgTag->getAttribute('src');
            if ($imgSrc) {
                $filename = basename($imgSrc);
                $existing_attachment = get_page_by_title($filename, OBJECT, 'attachment'); // Check if the image already exists in the media library

                if ($existing_attachment) {
                    $attachment_id = $existing_attachment->ID; // If the image already exists, set the attachment ID
                } else {
                    $attachment_id = post_porter_image_uploader($imgSrc, $item);
                }

                if ($attachment_id) {
                    $imgTag->setAttribute('src', wp_get_attachment_url($attachment_id));  // Update the image tag's 'src' attribute with the attachment URL
                }
            }
        }

        // Return the modified HTML content
        return $doc->saveHTML();
    }
}

// Function to download image and upload to media, return attachment id
if (!function_exists('post_porter_image_uploader')) {
    function post_porter_image_uploader($imgSrc, $item)
    {
        $post_title = $item['title']['rendered'];

        // WordPress HTTP API request to get image data from image URL
        $response = wp_safe_remote_get($imgSrc, array('timeout' => 30));
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $log_detail = sprintf(
                /* translators: %1$s: Post Title, %2$s: Error Message */
                esc_html__('Post Title: %1$s Error fetching image data: %2$s', 'post-porter'),
                esc_html($post_title),
                esc_html($error_message)
            );
            post_porter_add_logs($log_detail);
            return false;
        }

        $image_data = wp_remote_retrieve_body($response);

        $upload_dir = wp_upload_dir();
        $filename = basename($imgSrc);
        $upload_filename = wp_unique_filename($upload_dir['path'], $filename);
        $upload_file = wp_upload_bits($upload_filename, null, $image_data);

        if (!$upload_file['error']) {
            // Create attachment data for the uploaded image
            $attachment = array(
                'post_mime_type' => $upload_file['type'],
                'post_title' => sanitize_file_name($upload_filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attachment_id = wp_insert_attachment($attachment, $upload_file['file']);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
            } else {
                $log_detail = sprintf(
                    /* translators: %1$s: Post Title, %2$s: Image Link, %3$s: Error uploading image */
                    esc_html__('Post Title: %1$s Image Link: %2$s Error uploading image: %3$s', 'post-porter'),
                    esc_html($post_title),
                    esc_html($imgSrc),
                    esc_html($attachment_id->get_error_message())
                );
                post_porter_add_logs($log_detail);
            }
        }

        return $attachment_id; // Return the attachment ID
    }
}
