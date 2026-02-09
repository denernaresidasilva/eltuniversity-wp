<?php
namespace WPAdminify\Pro;

use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Columns: Featured Image and ID
 *
 * @package WP Adminify
 *
 * @author WP Adminify <support@wpadminify.com>
 */

class CustomAdminColumns_Pro extends AdminSettingsModel
{
    private $custom_columns;
    public function __construct()
    {
        $this->options = (array) AdminSettings::get_instance()->get();
        $this->custom_columns = $this->options['custom_admin_columns']['columns_data'];
        add_action('admin_init', [$this, 'adminify_custom_admin_columns']);

        add_action( 'wp_login', [$this, 'adminify_wp_last_login'] );
    }

    /**
     * Set Specific User Last Login Time
     *
     * @return void
     */
    public function adminify_wp_last_login( $user_login ) {
        $user = get_user_by( 'login', $user_login );
        update_user_meta( $user->ID, '_adminify_last_login', time() );
    }

    /**
     * Custom Admin Columns
     *
     * @return void
     */
    public function adminify_custom_admin_columns(){

        if (!empty($this->custom_columns) && in_array('last_login_column', $this->custom_columns)) {
            $this->last_login_custom_column();
        }

        if (!empty($this->custom_columns) && in_array('post_page_id_column', $this->custom_columns)) {
            $this->adminify_post_page_id_column();
        }

        if ( !empty($this->custom_columns ) && in_array('post_thumb_column', $this->custom_columns)) {
            $this->post_thumnails_column();
        }

        if ( !empty($this->custom_columns ) && in_array('comment_id_column', $this->custom_columns)) {
            $this->adminify_comments_id_column();
        }

        if ( !empty($this->custom_columns ) && in_array('taxonomy_id_column', $this->custom_columns)) {
            $this->adminify_taxonomy_id_column();
        }

        if (!empty($this->custom_columns) && in_array('posts_slug_column', $this->custom_columns)) {
            $this->adminify_admin_column_slug();
        }
    }


    /**
     * Custom Column Slug
     *
     * @return void
     */
    public function adminify_admin_column_slug(){

        $post_types = [];
        if (!empty($this->options['custom_admin_columns']['slug_column_post_types'])) {
            $post_types = $this->options['custom_admin_columns']['slug_column_post_types'];
        }
        $all_post_types = get_post_types();


        if (empty($post_types) || empty(array_intersect($all_post_types, $post_types))) {
            return;
        }

        foreach ($post_types as $key => $post_type) {
            if (in_array($post_type, $all_post_types)) {
                add_filter('manage_' . $post_type . '_posts_columns',       array($this, 'admin_column_slug_posts'));
                add_action('manage_' . $post_type . '_posts_custom_column', array($this, 'admin_column_slug_posts_data'), 10, 2);
            }
        }
    }

    /**
     * Adds Slug column to Posts list column
     *
     * @param array $defaults An array of column names
     */
    public function admin_column_slug_posts( $defaults ){
        $defaults['adminify-posts-column-slug'] = __('URL Path', 'adminify');
        return $defaults;
    }

    /**
     * Retrieves post details using the get_post function and displays the slug and/or URL path.
     *
     * @param string $column_name Name of the column
     * @param int    $id          post id
     *
     * @see https://developer.wordpress.org/reference/functions/get_post/
     */
    public function admin_column_slug_posts_data( $column_name, $id ){
        if ($column_name == 'adminify-posts-column-slug') {
            // Retrieve post details and status
            $adminify_post_info   = get_post($id, 'string', 'display');
            $adminify_post_status = $adminify_post_info->post_status;

            // Define placeholders used in permalink generation for drafts
            $adminify_draft_slug_placeholders = array('%pagename%', '%postname%');

            // Handle drafts and other unpublished statuses
            if (in_array($adminify_post_status, array('draft', 'pending', 'future'))) {
                // Get a sample permalink for the draft post
                $adminify_post_draft_url_array = get_sample_permalink($id);

                // Extract the URL path and remove the host part
                $adminify_post_draft_url_path = str_replace(get_home_url(), '', $adminify_post_draft_url_array[0]);

                // Replace placeholders (%pagename%, %postname%) with actual slug
                $adminify_post_slug = str_replace($adminify_draft_slug_placeholders, $adminify_post_draft_url_array[1], $adminify_post_draft_url_path);

                // Style the slug in gray to indicate it's a draft
                $adminify_post_slug = '<span style="color: #9c9c9c;">' . $adminify_post_slug . '</span>';
            } else {
                // For published or other statuses, get the actual permalink and remove host
                $adminify_post_slug = str_replace(get_home_url(), '', get_permalink($id));

                // Decode the slug to handle multibyte characters
                $adminify_post_slug = esc_html(urldecode($adminify_post_slug));
            }

            // Display the final slug
            echo $adminify_post_slug;
        }
    }

    /**
     * Taxonomy ID Column
     *
     * @return void
     */
    public function adminify_taxonomy_id_column()
    {
        // If not true then return
        if (empty($this->options['taxonomy_id_column'])) {
            return;
        }
        // Restrict the custom column to specific tax_types
        $tax_types = [
            'category',
            'post_tag',
            'product_cat',
            'product_tag',
            'page_category',
            'page_tag',
            'recipe_category',
            'recipe_tag',
            'recipe_ingredient',
            'recipe_feature',
            'recipe_cuisine',
            'portfolio_category',
            'portfolio_tag',
            'portfolio_client',
        ];

        if (empty($tax_types)) {
            return;
        }

        // Add custom column filter and action
        foreach ($tax_types as $taxonomy) {
            add_action("manage_edit-{$taxonomy}_columns", [$this, 'adminify_taxonomy_id_column_head']);
            add_filter("manage_edit-{$taxonomy}_sortable_columns", [$this, 'adminify_taxonomy_id_column_head']);
            add_filter("manage_{$taxonomy}_custom_column", [$this, 'adminify_taxonomy_id_column_content'], 11, 3);
        }
    }

    /**
     * Taxonomy ID Head
     *
     * @param [type] $column
     *
     * @return void
     */
    public function adminify_taxonomy_id_column_head($column)
    {
        $column['tax_id'] = esc_html__('ID', 'adminify-admin-columns');
        return $column;
    }

    /**
     * Taxonomy Column Content
     *
     * @param [type] $value
     * @param [type] $name
     * @param [type] $id
     *
     * @return void
     */
    function adminify_taxonomy_id_column_content($value, $name, $id)
    {
        return 'tax_id' === $name ? $id : $value;
    }


    /**
     * Comment ID Columns
     *
     * @return void
     */
    public function adminify_comments_id_column()
    {
        // If not true then return
        add_filter('manage_edit-comments_columns', [$this, 'adminify_add_comments_columns']);
        add_action('manage_comments_custom_column', [$this, 'adminify_add_comment_columns_content'], 10, 2);
    }



    public function adminify_add_comments_columns($columns)
    {
        $comment_columns = [
            'adminify_comment_id' => __('ID', 'adminify-admin-columns'),
            'adminify_parent_id'  => __('Parent ID', 'adminify-admin-columns'),
        ];
        $columns         = array_slice($columns, 0, 3, true) + $comment_columns + array_slice($columns, 3, null, true);
        // return the result
        return $columns;
    }

    public function adminify_add_comment_columns_content($column, $comment_ID)
    {
        global $comment;
        switch ($column):
            case 'adminify_comment_id':
                echo esc_html($comment_ID); // or echo $comment->comment_ID.
                break;
            case 'adminify_parent_id':
                echo esc_html($comment->comment_parent); // this will be printed inside the column
                break;
        endswitch;
    }


    /**
     * Last Login Custom Admin Column
     *
     * @return void
     */
    public function last_login_custom_column(){
        add_filter('manage_users_columns', [$this, 'custom_users_column_head'], 15);
        add_action('wp_login', [$this, 'update_last_login'], 10, 2);
        add_filter('manage_users_custom_column', [$this, 'custom_users_column_content'], 10, 3);
    }

    function custom_users_column_head($defaults) {
        $defaults['_adminify_last_login'] = __('Last Login', 'adminify');
        return $defaults;
    }

    // Display the custom column content
    public function custom_users_column_content($value, $column_name, $user_id) {
        // $current_user = wp_get_current_user();
        if ($column_name === '_adminify_last_login') {
            $last_login = get_user_meta($user_id, '_adminify_last_login', true);
            if ($last_login) {
                $value = date('Y-m-d H:i:s', $last_login);
            } else {
                $value = __('Never logged in', 'adminify');
            }
        }
        return $value;
    }

    /**
     * Post Thumbnails Column
     *
     * @return void
     */
    public function post_thumnails_column()
    {
        // Get all the custom post types
        $post_types = get_post_types(['public' => true], 'names', 'and');

        // Create array of allowed post types
        $post_types_with_thumbnail = [];

        // Inlcude WP default post types
        $post_types_with_thumbnail[] = 'post';
        // $post_types_with_thumbnail[] = 'page';

        foreach ($post_types as $post_type) {
            // Check if the post type supports thumbnails
            if (post_type_supports($post_type, 'thumbnail')) {
                // The include this post type to allow the image column
                $post_types_with_thumbnail[] = $post_type;
            }
        }

        // Restrict the custom column to post_types with thumbnail support
        $post_types = $post_types_with_thumbnail;

        // Exclude product post type, because WooCommerce has own thumbnail column
        if ($post_types === 'product') {
            return;
        }

        // Add custom column filter and action
        foreach ($post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'adminify_admin_column_head']);
            add_filter("manage_{$post_type}_posts_columns", [$this, 'adminify_admin_column_move']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'adminify_admin_column_content'], 10, 2);
        }
    }

    /**
     * Column Head
     *
     * @param [type] $column
     *
     * @return void
     */
    function adminify_admin_column_head($column)
    {
        $column['featured_image'] = __('Thumbnail', 'adminify');
        return $column;
    }

    /**
     * Move Column: Before Title
     *
     * @param [type] $columns
     *
     * @return void
     */
    function adminify_admin_column_move($columns)
    {
        $new = [];
        foreach ($columns as $key => $title) {
            if ($key === 'title') {
                $new['featured_image'] = __('Thumbnail', 'adminify');
            }
            $new[$key] = $title;
        }
        return $new;
    }


    /**
     * Featured Image: By Post ID
     *
     * @param [type] $post_id
     *
     * @return void
     */
    function adminify_admin_featured_image($post_id)
    {
        $post_thumbnail_id = get_post_thumbnail_id($post_id);
        if (has_post_thumbnail($post_id)) {
            $image = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
            if(!empty($image)){
                return $image[0];
            }
        }
    }

    /**
     * Featured Image Content
     *
     * @param [featured_image] $column_name
     * @param [post_id]        $post_id
     *
     * @return void
     */
    function adminify_admin_column_content($column_name, $post_id)
    {
        if ($column_name === 'featured_image') {
            $post_featured_image = $this->adminify_admin_featured_image($post_id);
            if (has_post_thumbnail($post_id)) {
                echo '<img src="' . esc_url($post_featured_image) . '" />';
            } else {
                if ( !empty( $this->options['custom_admin_columns']['post_page_column_thumb_image']['url'] ) ) {
                    $image_url = $this->options['custom_admin_columns']['post_page_column_thumb_image']['url'];
                    echo '<img style="width:55px;height:55px" src="' . esc_url($image_url) . '"/>';
                } else {
                    $image_url = WP_ADMINIFY_ASSETS_IMAGE . 'no-thumb.svg';
                    echo '<img style="width:55px;height:55px" src="' . esc_url($image_url) . '" alt="' . esc_html__('No Thumbnail', 'adminify') . '"/>';
                }
            }
        }
    }


    /**
     * Post/Page ID Column
     *
     * @return void
     */
    public function adminify_post_page_id_column()
    {
        // Restrict the custom column to specific post_types
        $post_types = ['post', 'page', 'recipe', 'portfolio', 'product', 'team', 'service', 'testimonial', 'movie', 'book', 'download'];
        if (empty($post_types)) {
            return;
        }
        foreach ($post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'adminify_post_page_id_column_head']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'adminify_post_page_id_column_content'], 10, 2);
        }
    }

    public function adminify_post_page_id_column_head($defaults)
    {
        $defaults['adminify_post_id'] = esc_html__('ID', 'adminify');
        return $defaults;
    }

    public function adminify_post_page_id_column_content($column_name, $id)
    {
        if ($column_name === 'adminify_post_id') {
            echo esc_html($id);
        }
    }
}
