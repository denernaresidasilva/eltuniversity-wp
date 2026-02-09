<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;

/**
 * Class for Media Replacement module
 */
class JLT_Adminify_Media_Replacer
{

    /**
     * Constructor to initialize hooks.
     */
    public function __construct()
    {
        add_filter('media_row_actions', [$this, 'jltwp_adminify_modify_media_list_table_edit_link'], 10, 2);
        add_filter('attachment_fields_to_edit', [$this, 'jltwp_adminify_add_media_replacement_button'], 10, 2);
        add_action('wp_ajax_jltwp_adminify_replace_media', [$this, 'jltwp_adminify_replace_media']);
        // add_action('edit_attachment', [$this, 'jltwp_adminify_replace_media']);
        add_filter('wp_prepare_attachment_for_js', [$this, 'jltwp_adminify_append_cache_busting_param_to_attachment_for_js'], 10, 2);
        add_filter('wp_get_attachment_image_src', [$this, 'jltwp_adminify_append_cache_busting_param_to_attachment_image_src'], 10, 2);
        add_filter('wp_get_attachment_url', [$this, 'jltwp_adminify_append_cache_busting_param_to_attachment_url'], 10, 2);
        add_filter('wp_calculate_image_srcset', [$this, 'jltwp_adminify_append_cache_busting_param_to_image_srcset'], 10, 5);
        add_filter('post_updated_messages', [$this, 'jltwp_adminify_attachment_updated_custom_message']);

        add_action('admin_enqueue_scripts', array($this, 'jltwp_adminify_enqueue_scripts'));

        // Enable/Disable Infinite Scroll
        add_filter('media_library_infinite_scrolling', '__return_true');
    }

    public function jltwp_adminify_enqueue_scripts(){
        wp_register_style('wp-adminify-media-replace', WP_ADMINIFY_URL . 'Pro/assets/css/wp-adminify-media-replace' . Utils::assets_ext('.css'), false, WP_ADMINIFY_VER);

        wp_enqueue_script('wp-adminify-media-replace', WP_ADMINIFY_URL . 'Pro/assets/js/wp-adminify-media-replace' . Utils::assets_ext('.js'));
        wp_localize_script('wp-adminify-media-replace', 'jlt_adminify_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    /**
     * Modify the 'Edit' link to be 'Edit or Replace'.
     */
    public function jltwp_adminify_modify_media_list_table_edit_link($actions, $post)
    {
        foreach ($actions as $key => $value) {
            if ($key == 'edit') {
                $actions['edit'] = '<a href="' . get_edit_post_link($post) . '" aria-label="Edit or Replace">Edit or Replace</a>';
            }
        }
        return $actions;
    }

    /**
     * Add media replacement button in the edit screen of media/attachment.
     */
    public function jltwp_adminify_add_media_replacement_button($fields, $post)
    {
        global $pagenow, $typenow;

        // Avoid on post creation and editing screen to prevent layout issues
        if ($typenow == 'attachment' || ($typenow != 'attachment' && $pagenow != 'post-new.php' && $pagenow != 'post.php')) {
            $image_mime_type = is_object($post) && property_exists($post, 'post_mime_type') ? $post->post_mime_type : '';

            wp_enqueue_media();

            // Add new field for media replace functionality
            $fields['jltwp-adminify-media-replace'] = [
                'label' => '',
                'input' => 'html',
                'html'  => '
                    <div id="media-replace-div" class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle ui-sortable-handle">' . __('Replace Media', 'adminify') . '</h2>
                        </div>
                        <div class="inside">
                            <button type="button" id="jltwp-adminify-media-replace" class="button-secondary button-large jltwp-adminify-media-replace-button" data-old-image-mime-type="' . $image_mime_type . '" onclick="replaceMedia(\'' . $image_mime_type . '\');">' . __('Select New Media File', 'adminify') . '</button>
                            <input type="hidden" id="new-attachment-id" name="new-attachment-id" />
                            <div class="jltwp-adminify-media-replace-notes">
                                <p>' . __('The current file will be replaced with the uploaded/selected file (of the same type) while retaining the current ID, publish date, and file name. Thus, no existing links will break.', 'adminify') . '</p>
                            </div>
                        </div>
                    </div>
                ',
            ];
        }

        return $fields;
    }

    /**
     * Replace existing media with the newly updated file.
     */
    public function jltwp_adminify_replace_media()
    {
        if (!isset($_POST['new-attachment-id']) || empty($_POST['new-attachment-id'])) {
            return false;
        }

        $old_attachment_id = intval($_POST['attachment_id']);
        $new_attachment_id = intval(sanitize_text_field($_POST['new-attachment-id']));

        $old_post_meta = get_post($old_attachment_id, ARRAY_A);
        $old_post_mime = $old_post_meta['post_mime_type'];

        $new_post_meta = get_post($new_attachment_id, ARRAY_A);
        $new_post_mime = $new_post_meta['post_mime_type'];

        if ($old_post_mime === $new_post_mime) {
            $new_attachment_meta = wp_get_attachment_metadata($new_attachment_id);
            $new_media_file_path = array_key_exists('original_image', $new_attachment_meta) ? wp_get_original_image_path($new_attachment_id) : get_attached_file($new_attachment_id);

            if (!is_file($new_media_file_path)) {
                return false;
            }

            $this->jltwp_adminify_delete_media_files($old_attachment_id);

            $old_media_file_path = array_key_exists('original_image', wp_get_attachment_metadata($old_attachment_id)) ? wp_get_original_image_path($old_attachment_id) : get_attached_file($old_attachment_id);

            if (!file_exists(dirname($old_media_file_path))) {
                mkdir(dirname($old_media_file_path), 0755, true);
            }

            copy($new_media_file_path, $old_media_file_path);

            $old_media_post_meta_updated = wp_generate_attachment_metadata($old_attachment_id, $old_media_file_path);
            wp_update_attachment_metadata($old_attachment_id, $old_media_post_meta_updated);

            wp_delete_attachment($new_attachment_id, true);

            $options_extra = get_option('adminify_extra', []);
            $recently_replaced_media = $options_extra['recently_replaced_media'] ?? [];
            if (count($recently_replaced_media) >= 5) {
                array_shift($recently_replaced_media);
            }
            $recently_replaced_media[] = $old_attachment_id;
            $options_extra['recently_replaced_media'] = array_unique($recently_replaced_media);
            update_option('adminify_extra', $options_extra);
        }
    }

    /**
     * Delete the existing/old media files when performing media replacement.
     */
    public function jltwp_adminify_delete_media_files($post_id)
    {
        $attachment_meta = wp_get_attachment_metadata($post_id);
        $attachment_file_path = get_attached_file($post_id);

        if (isset($attachment_meta['sizes']) && is_array($attachment_meta['sizes'])) {
            foreach ($attachment_meta['sizes'] as $size_info) {
                $intermediate_file_path = str_replace(basename($attachment_file_path), $size_info['file'], $attachment_file_path);
                wp_delete_file($intermediate_file_path);
            }
        }

        wp_delete_file($attachment_file_path);

        if (array_key_exists('original_image', $attachment_meta)) {
            $original_file_path = wp_get_original_image_path($post_id);
            wp_delete_file($original_file_path);
        }
    }

    /**
     * Customize the attachment updated message.
     */
    public function jltwp_adminify_attachment_updated_custom_message($messages)
    {
        if (isset($messages['attachment'][4])) {
            $messages['attachment'][4] = 'Media file updated. You may need to <a href="https://fabricdigital.co.nz/blog/how-to-hard-refresh-your-browser-and-clear-cache" target="_blank">hard refresh</a> your browser to see the updated media preview image below.';
        }
        return $messages;
    }

    /**
     * Append cache busting parameter to the end of image srcset.
     */
    public function jltwp_adminify_append_cache_busting_param_to_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        $options_extra = get_option('adminify_extra', []);
        $recently_replaced_media = $options_extra['recently_replaced_media'] ?? [];

        if (in_array($attachment_id, $recently_replaced_media)) {
            foreach ($sources as $size => $source) {
                $source['url'] .= (false === strpos($source['url'], '?') ? '?' : '&') . 't=' . time();
                $sources[$size] = $source;
            }
        }
        return $sources;
    }

    /**
     * Append cache busting parameter to the end of image src.
     */
    public function jltwp_adminify_append_cache_busting_param_to_attachment_image_src($image, $attachment_id)
    {
        $options_extra = get_option('adminify_extra', []);
        $recently_replaced_media = $options_extra['recently_replaced_media'] ?? [];

        if (!empty($image[0]) && in_array($attachment_id, $recently_replaced_media)) {
            $image[0] .= (false === strpos($image[0], '?') ? '?' : '&') . 't=' . time();
        }

        return $image;
    }

    /**
     * Append cache busting parameter to image src for JS.
     */
    public function jltwp_adminify_append_cache_busting_param_to_attachment_for_js($response, $attachment)
    {
        $options_extra = get_option('adminify_extra', []);
        $recently_replaced_media = $options_extra['recently_replaced_media'] ?? [];

        if (in_array($attachment->ID, $recently_replaced_media)) {
            $response['url'] .= (false === strpos($response['url'], '?') ? '?' : '&') . 't=' . time();
            if (isset($response['sizes'])) {
                foreach ($response['sizes'] as $size_name => $size) {
                    $response['sizes'][$size_name]['url'] .= (false === strpos($size['url'], '?') ? '?' : '&') . 't=' . time();
                }
            }
        }

        return $response;
    }

    /**
     * Append cache busting parameter to attachment URL.
     */
    public function jltwp_adminify_append_cache_busting_param_to_attachment_url($url, $attachment_id)
    {
        $options_extra = get_option('adminify_extra', []);
        $recently_replaced_media = $options_extra['recently_replaced_media'] ?? [];

        if (in_array($attachment_id, $recently_replaced_media)) {
            $url .= (false === strpos($url, '?') ? '?' : '&') . 't=' . time();
        }

        return $url;
    }
}

// Initialize the class
new JLT_Adminify_Media_Replacer();
