<?php

namespace WPAdminify\Inc\Modules\PostDuplicator;

use WPAdminify\Inc\Admin\AdminSettings;

if (!defined('ABSPATH')) {
    exit; // No direct access allowed.
}

class TaxonomyDuplicator {

    private $clone_title;
    public $options = [];

    public function __construct() {
        $this->options = (array) AdminSettings::get_instance()->get('post_duplicator');
        if( empty($this->options['taxonomies'])){
            return;
        }
        $this->clone_title = AdminSettings::get_instance()->get('jltwp_adminify_wl_plugin_menu_label');

        // Initialize taxonomy duplicator
        add_action('admin_init', [$this, 'adminify_taxonomy_duplicator_init']);
    }

    public function adminify_taxonomy_duplicator_init() {
        $taxonomies = [];
        if (!empty($this->options['taxonomies'])) {
            $taxonomies = $this->options['taxonomies'];
        }
        $all_taxonomies = get_taxonomies();

        if (empty($taxonomies) || empty(array_intersect($all_taxonomies, $taxonomies))) {
            return;
        }

        add_action('admin_action_adminify_duplicate_taxonomy', [$this, 'adminify_duplicate_taxonomy_as_draft']);

        foreach ($taxonomies as $taxonomy) {
            if (in_array($taxonomy, $all_taxonomies)) {
                add_filter($taxonomy . '_row_actions', [$this, 'jltwp_adminify_duplicator_row_actions'], 10, 2);
            }
        }
    }

    /**
     * Add the duplicate link to action list for taxonomy_row_actions
     */
    public function jltwp_adminify_duplicator_row_actions($actions, $term) {
        $adminify_duplicate_link = admin_url('admin.php?action=adminify_duplicate_taxonomy&term=' . $term->term_id);
        $adminify_duplicate_link = wp_nonce_url($adminify_duplicate_link, 'jltwp_adminify_taxonomy_duplicator_nonce');

        $clone_title = !empty($this->clone_title) ? $this->clone_title : 'Adminify Clone';
        $duplicate_title = 'Duplicate this taxonomy term: ' . $term->name;
        $actions['adminify_duplicate'] = sprintf(__('<a href="%1$s" title="%2$s" rel="permalink">%3$s</a>', 'adminify'), $adminify_duplicate_link, $duplicate_title, $clone_title);

        return $actions;
    }

    /**
     * Duplicate taxonomy term as a draft
     */
    public function adminify_duplicate_taxonomy_as_draft() {
        if (!(isset($_GET['term']) || isset($_POST['term']) || (isset($_REQUEST['action']) && 'adminify_duplicate_taxonomy' == $_REQUEST['action']))) {
            wp_die('No taxonomy term to duplicate has been supplied!');
        }


        // Nonce verification
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'jltwp_adminify_taxonomy_duplicator_nonce')) {
            return;
        }

        $term_id = (isset($_GET['term']) ? absint($_GET['term']) : absint($_POST['term']));
        $term = get_term($term_id);

        if( isset( $_SERVER['HTTP_REFERER']) && str_contains( $_SERVER['HTTP_REFERER'], 'action=adminify_duplicate_taxonomy' )) {
			// Redirect to the taxonomy edit page
            $redirect_url = admin_url('edit-tags.php?taxonomy=' . $term->taxonomy);
            wp_safe_redirect($redirect_url);
            exit;
		}
        if (is_wp_error($term) || !$term) {
            wp_die('Failed to retrieve the original taxonomy term: ' . $term_id);
        }

        // Duplicate the taxonomy term
        $new_term_args = [
            'description' => $term->description,
            'slug' => $term->slug . '-duplicate',
            'parent' => $term->parent,
        ];

        $new_term = wp_insert_term($term->name . ' (Duplicate)', $term->taxonomy, $new_term_args);

        if (is_wp_error($new_term)) {
            wp_die('Failed to duplicate taxonomy term: ' . $new_term->get_error_message());
        }

        // Redirect to the taxonomy edit page
        $redirect_url = admin_url('edit-tags.php?taxonomy=' . $term->taxonomy);
        wp_safe_redirect($redirect_url);
        exit;
    }
}
