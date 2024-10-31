<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

include POST_PORTER_ADMIN_DIR . '/methods/post-porter-images.php';

// function to import and create post using api response
if (!function_exists('post_porter_import_posts_from_api')) {
    function post_porter_import_posts_from_api($page)   
    {
        $post_porter_website_url = get_option('post_porter_website_url');
        $post_porter_website_post_type = get_option('post_porter_website_post_type');
        $post_porter_post_type = get_option('post_porter_post_type');
        $post_types_api_url = $post_porter_website_url . '/wp-json/wp/v2/types/' . $post_porter_website_post_type;
        $rest_api_post_type = wp_safe_remote_get($post_types_api_url);

        if (!is_wp_error($rest_api_post_type)) {
            $rest_api_post_type_response = json_decode(wp_remote_retrieve_body($rest_api_post_type), true);
        }

        $api_url = $rest_api_post_type_response['_links']['wp:items'][0]['href'] . '?_embed&per_page=1&page=' . $page;
        $request_args = array(
            'timeout' => 20,
        );
        $response = wp_safe_remote_get($api_url, $request_args);

        if (is_wp_error($response)) {
            /* translators: %s: error message from api */
            $log_detail = sprintf(__('Error fetching data from the API: %s', 'post-porter'), $response->get_error_message());
            post_porter_add_logs($log_detail);
        }

        $data = mb_convert_encoding(json_decode(wp_remote_retrieve_body($response), true), 'HTML-ENTITIES', 'UTF-8');

        if (!is_array($data) || empty($data)) {
            $log_detail = __('No data found in the API response.', 'post-porter');
            post_porter_add_logs($log_detail);
        }

        foreach ($data as $item) {
            // get post title
            if (is_array($item) && isset($item['title']) && is_array($item['title']) && isset($item['title']['rendered'])) {
                $post_title = $item['title']['rendered'];
            } else {
                continue;
            }

            // get all terms of post
            if (isset($item['_embedded']['wp:term'])) {
                $taxonomy_terms = array();

                foreach ($item['_embedded']['wp:term'] as $term_group) {
                    foreach ($term_group as $term) {
                        $taxonomy_name = $term['taxonomy'];
                        $term_name = $term['name'];
                        $wp_term = get_term_by('name', $term_name, $taxonomy_name);

                        if ($wp_term) {
                            $taxonomy_terms[$taxonomy_name][] = $wp_term->term_id;
                        } else {
                            // Create a new term if it doesn't exist
                            $term_args = array(
                                'slug' => sanitize_title($term_name),
                            );

                            $new_term = wp_insert_term($term_name, $taxonomy_name, $term_args);

                            if (!is_wp_error($new_term) && isset($new_term['term_id'])) {
                                $taxonomy_terms[$taxonomy_name][] = $new_term['term_id'];
                            }
                        }
                    }
                }
            }

            // get author details availabe in api response
            $author_data = $item['_embedded']['author'][0];
            $author_name = !empty($author_data['name']) ? $author_data['name'] : get_userdata(1)->display_name;
            $author_slug = !empty($author_data['slug']) ? $author_data['slug'] : get_userdata(1)->user_nicename;
            $author_url = !empty($author_data['link']) ? $author_data['link'] : get_userdata(1)->user_url;
            $author_description = !empty($author_data['description']) ? $author_data['description'] : get_userdata(1)->description;
            $author_avatar_urls = !empty($author_data['avatar_urls']) ? $author_data['avatar_urls'] : get_avatar_data(1);

            // Check if the author exists by slug
            $existing_author = get_user_by('slug', $author_slug);

            if (!$existing_author) {
                // Create a new author
                $new_author = wp_insert_user(array(
                    'user_login' => $author_slug,
                    'user_nicename' => $author_slug,
                    'display_name' => $author_name,
                    'user_url' => $author_url,
                    'description' => $author_description,
                    'user_pass' => wp_generate_password(),
                    'role' => 'author',
                ));

                if (!is_wp_error($new_author)) {
                    // Set the author's avatar if available
                    if (!empty($author_avatar_urls)) {
                        foreach ($author_avatar_urls as $size => $avatar_url) {
                            update_user_meta($new_author, 'user_avatar_' . $size, $avatar_url);
                        }
                    }
                }
            }

            // Check if a post with the same title already exists
            $existing_post = get_page_by_title($post_title, 'OBJECT', $post_porter_post_type);
            if ($existing_post) {
                $post_id = $existing_post->ID;
                if (!has_post_thumbnail($post_id)) {
                    post_porter_images_handler($item, $post_id);
                }
                if (empty($existing_post->post_date)) {
                    $post_date = strtotime($item['date']);
                    if ($post_date !== false) {
                        $post_data = array(
                            'ID' => $post_id,
                            'post_date' => gmdate('Y-m-d H:i:s', $post_date),
                            'post_author' => get_user_by('login', $author_slug)->ID,
                        );
                        wp_update_post($post_data);
                    }
                }
                // Assign categories and tags
                foreach ($taxonomy_terms as $taxonomy_name => $term_ids) {
                    wp_set_post_terms($post_id, $term_ids, $taxonomy_name, false);
                }
                continue;
            }

            // get post content
            if (!empty($item['content']['rendered'])) {
                $post_content = post_porter_replace_images_from_content($item);
            }

            // get post status, slug, excerpt
            $post_status = $item['status'];
            $post_slug = $item['slug'];
            $post_excerpt = wp_strip_all_tags($item['excerpt']['rendered']);

            // post data to create or update post
            $post_data = array(
                'post_title' => $post_title,
                'post_content' => $post_content,
                'post_status' => $post_status,
                'post_type' => $post_porter_post_type,
                'post_excerpt' => $post_excerpt,
                'post_author' => get_user_by('login', $author_slug)->ID,
                'post_name' => $post_slug,
            );
            $post_date = strtotime($item['date']);
            if ($post_date !== false) {
                $post_data['post_date'] = gmdate('Y-m-d H:i:s', $post_date);
            }
            $post_id = wp_insert_post($post_data);
            $post_content = '';

            if (!is_wp_error($post_id)) {
                post_porter_images_handler($item, $post_id);
                foreach ($taxonomy_terms as $taxonomy_name => $term_ids) {
                    wp_set_post_terms($post_id, $term_ids, $taxonomy_name, false);
                }
            } else {
                $log_detail = $post_id . '->' . __('Error creating post:', 'post-porter') . ' ' . $post_id->get_error_message();
                post_porter_add_logs($log_detail);
            }
        }
    }
}

// function to get total numbers of pages based on request and per_page parameters
if (!function_exists('post_porter_get_total_pages_from_api')) {
    function post_porter_get_total_pages_from_api($args)
    {
        $post_porter_website_url = get_option('post_porter_website_url'); // website url from which importing data (source website)
        $post_porter_website_post_type = get_option('post_porter_website_post_type'); // post type (source website)
        $post_types_api_url = $post_porter_website_url . '/wp-json/wp/v2/types/' . $post_porter_website_post_type; // api to check post type (source website)
        $rest_api_post_type = wp_safe_remote_get($post_types_api_url);

        if (!is_wp_error($rest_api_post_type)) {
            $rest_api_post_type_response = json_decode(wp_remote_retrieve_body($rest_api_post_type), true);
        }

        $api_url = $rest_api_post_type_response['_links']['wp:items'][0]['href']  . '?_embed&per_page=1';
        $request_args = array(
            'timeout' => 20,
        );
        $response = wp_safe_remote_get($api_url, $request_args);
        if (is_wp_error($response)) {
            return 0;
        }
        $headers = wp_remote_retrieve_headers($response);
        if ($args == "totalpages") {
            if (isset($headers['x-wp-totalpages'])) {
                $total_pages = intval($headers['x-wp-totalpages']);
                return $total_pages;
            }
        }
        return 0;
    }
}

// function to check wheather post type has data or not
if (!function_exists('post_porter_check_post_type_has_data')) {
    function post_porter_check_post_type_has_data($post_slug)
    {
        $post_porter_website_url = get_option('post_porter_website_url'); // website url from which importing data (source website)
        $post_types_api_url = $post_porter_website_url . '/wp-json/wp/v2/types/' . $post_slug; // api to check post type (source website)
        $rest_api_post_type = wp_safe_remote_get($post_types_api_url);
        if (!is_wp_error($rest_api_post_type)) {
            $rest_api_post_type_response = json_decode(wp_remote_retrieve_body($rest_api_post_type), true);
            $response_code = wp_remote_retrieve_response_code($rest_api_post_type);
            if ($response_code == 401) {
                return $response_code;
            } else {
                $api_url = $rest_api_post_type_response['_links']['wp:items'][0]['href'];
                $request_args = array(
                    'timeout' => 20,
                );
                $response = wp_safe_remote_get($api_url, $request_args);

                if (empty(json_decode(wp_remote_retrieve_body($response), true))) {
                    return $response_code = 204;
                } else {
                    return true;
                }
            }
        }
    }
}

// function to check if importing post type has any custom post type which is not registered with desintion websites post type
if (!function_exists('post_porter_get_missing_taxonomies')) {
    function post_porter_get_missing_taxonomies($post_slug)
    {
        $post_porter_website_url = get_option('post_porter_website_url'); // website url from which importing data (source website)
        $post_porter_post_type = get_option('post_porter_post_type'); // post type (destination website)
        $post_types_api_url = $post_porter_website_url . '/wp-json/wp/v2/types/' . $post_slug; // api to check post type (source website)
        $rest_api_post_type = wp_safe_remote_get($post_types_api_url);
        if (!is_wp_error($rest_api_post_type)) {
            $rest_api_post_type_response = json_decode(wp_remote_retrieve_body($rest_api_post_type), true);
            if (isset($rest_api_post_type_response['taxonomies'])) {
                $taxonomies = $rest_api_post_type_response['taxonomies'];
                $post_porter_post_type_texonomy = get_object_taxonomies($post_porter_post_type); // get all taxonimies associated with destination post type
                $missing_taxonomies = array();
                foreach ($taxonomies as $taxonomy) {
                    if (!in_array($taxonomy, $post_porter_post_type_texonomy)) {
                        $missing_taxonomies[] = $taxonomy;
                    }
                }
                $missing_taxonomies_string = implode(',', $missing_taxonomies);
                return empty($missing_taxonomies) ? false : $missing_taxonomies_string;
            }
        }
        return false;
    }
}

// function to check authorization to import data from souce website using export key
if (!function_exists('post_porter_is_authorized')) {
    function post_porter_is_authorized($key)
    {
        $post_porter_website_url = get_option('post_porter_website_url'); // website url from which importing data (source website)
        $full_api_url = $post_porter_website_url . '/wp-json/wp/v1/post-importer?key=' . $key; // api to check authorization by key to import data
        $rest_response = wp_safe_remote_get($full_api_url);
        if (!is_wp_error($rest_response)) {
            $response_code = wp_remote_retrieve_response_code($rest_response);
            if ($response_code == 401) {
                return $response_code;
            } elseif ($response_code == 200) {
                $json_response = json_decode(wp_remote_retrieve_body($rest_response), true);

                if (isset($json_response['success'])) {
                    return $json_response['success'];
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
