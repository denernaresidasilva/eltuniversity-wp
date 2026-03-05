<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Tag_Manager {

    public static function create_tables() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $tags_table = $wpdb->prefix . 'zapwa_tags';
        $sql_tags   = "CREATE TABLE $tags_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset;";

        $contact_tags_table = $wpdb->prefix . 'zapwa_contact_tags';
        $sql_contact_tags   = "CREATE TABLE $contact_tags_table (
            contact_id BIGINT UNSIGNED NOT NULL,
            tag_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (contact_id, tag_id),
            KEY tag_id (tag_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_tags);
        dbDelta($sql_contact_tags);
    }

    public static function get_or_create_tag($name) {
        global $wpdb;

        $name = sanitize_text_field($name);
        $slug = sanitize_title($name);

        if (empty($slug)) {
            return false;
        }

        $table  = $wpdb->prefix . 'zapwa_tags';
        $tag_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug)
        );

        if ($tag_id) {
            return (int) $tag_id;
        }

        $result = $wpdb->insert(
            $table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );

        if ($result === false) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    public static function add_tag_to_contact($contact_id, $tag_slug) {
        global $wpdb;

        $contact_id = absint($contact_id);
        $tag_slug   = sanitize_title(sanitize_text_field($tag_slug));

        if (!$contact_id || empty($tag_slug)) {
            return false;
        }

        $tag_id = self::get_or_create_tag($tag_slug);
        if (!$tag_id) {
            return false;
        }

        $table = $wpdb->prefix . 'zapwa_contact_tags';

        $already_has = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE contact_id = %d AND tag_id = %d",
                $contact_id,
                $tag_id
            )
        );

        if ($already_has) {
            return true;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'contact_id' => $contact_id,
                'tag_id'     => $tag_id,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );

        if ($inserted) {
            if (class_exists('\ZapWA\Event_Tracker')) {
                Event_Tracker::record($contact_id, 'tag_added', $tag_slug, 'tag_manager');
            }
            return true;
        }

        return false;
    }

    public static function remove_tag_from_contact($contact_id, $tag_slug) {
        global $wpdb;

        $contact_id = absint($contact_id);
        $tag_slug   = sanitize_title(sanitize_text_field($tag_slug));

        if (!$contact_id || empty($tag_slug)) {
            return false;
        }

        $tags_table = $wpdb->prefix . 'zapwa_tags';
        $tag_id     = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $tags_table WHERE slug = %s", $tag_slug)
        );

        if (!$tag_id) {
            return false;
        }

        $contact_tags_table = $wpdb->prefix . 'zapwa_contact_tags';
        $deleted = $wpdb->delete(
            $contact_tags_table,
            ['contact_id' => $contact_id, 'tag_id' => $tag_id],
            ['%d', '%d']
        );

        if ($deleted) {
            if (class_exists('\ZapWA\Event_Tracker')) {
                Event_Tracker::record($contact_id, 'tag_removed', $tag_slug, 'tag_manager');
            }
            return true;
        }

        return false;
    }

    public static function contact_has_tag($contact_id, $tag_slug) {
        global $wpdb;

        $contact_id = absint($contact_id);
        $tag_slug   = sanitize_title(sanitize_text_field($tag_slug));

        if (!$contact_id || empty($tag_slug)) {
            return false;
        }

        $tags_table = $wpdb->prefix . 'zapwa_tags';
        $tag_id     = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $tags_table WHERE slug = %s", $tag_slug)
        );

        if (!$tag_id) {
            return false;
        }

        $contact_tags_table = $wpdb->prefix . 'zapwa_contact_tags';
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $contact_tags_table WHERE contact_id = %d AND tag_id = %d",
                $contact_id,
                $tag_id
            )
        );

        return (int) $count > 0;
    }

    public static function get_contact_tags($contact_id) {
        global $wpdb;

        $contact_id = absint($contact_id);

        if (!$contact_id) {
            return [];
        }

        $contact_tags_table = $wpdb->prefix . 'zapwa_contact_tags';
        $tags_table         = $wpdb->prefix . 'zapwa_tags';

        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT t.slug FROM $tags_table t
                 INNER JOIN $contact_tags_table ct ON ct.tag_id = t.id
                 WHERE ct.contact_id = %d
                 ORDER BY t.slug ASC",
                $contact_id
            )
        );

        return is_array($results) ? $results : [];
    }

    public static function get_all_tags() {
        global $wpdb;

        $table   = $wpdb->prefix . 'zapwa_tags';
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

        return is_array($results) ? $results : [];
    }
}
