<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Link_Tracker {

    public static function init() {
        add_rewrite_rule('^r/([a-zA-Z0-9]+)/?$', 'index.php?zapwa_track=$matches[1]', 'top');
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_action('template_redirect', [__CLASS__, 'handle_redirect']);
    }

    public static function add_query_vars($vars) {
        $vars[] = 'zapwa_track';
        return $vars;
    }

    public static function create_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'zapwa_tracked_links';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hash VARCHAR(16) NOT NULL,
            url TEXT NOT NULL,
            contact_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY hash (hash),
            KEY contact_id (contact_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function create_tracked_link($url, $contact_id = 0) {
        global $wpdb;

        $url        = esc_url_raw($url);
        $contact_id = absint($contact_id);

        if (empty($url) || !wp_http_validate_url($url)) {
            return false;
        }

        $hash  = wp_generate_password(16, false);
        $table = $wpdb->prefix . 'zapwa_tracked_links';

        $inserted = $wpdb->insert(
            $table,
            [
                'hash'       => $hash,
                'url'        => $url,
                'contact_id' => $contact_id,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s']
        );

        if (!$inserted) {
            return false;
        }

        return home_url('/r/' . $hash);
    }

    public static function handle_redirect() {
        $hash = get_query_var('zapwa_track');

        if (empty($hash)) {
            return;
        }

        global $wpdb;

        $hash  = sanitize_text_field($hash);
        $table = $wpdb->prefix . 'zapwa_tracked_links';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT url, contact_id FROM $table WHERE hash = %s LIMIT 1",
                $hash
            )
        );

        if (!$row || empty($row->url)) {
            wp_die(esc_html__('Link not found.', 'zap-whatsapp-automation'), '', ['response' => 404]);
        }

        $url = $row->url;

        if (!wp_http_validate_url($url)) {
            wp_die(esc_html__('Invalid redirect target.', 'zap-whatsapp-automation'), '', ['response' => 400]);
        }

        if (class_exists('\ZapWA\Event_Tracker')) {
            Event_Tracker::record(
                (int) $row->contact_id,
                'link_clicked',
                $url,
                'link_tracker',
                ['hash' => $hash]
            );
        }

        wp_safe_redirect($url, 302);
        exit;
    }

    public static function convert_links_in_text($text, $contact_id) {
        $contact_id = absint($contact_id);

        $text = preg_replace_callback(
            '#https?://[^\s<>"\']+#i',
            function ($matches) use ($contact_id) {
                // Trim trailing punctuation that is unlikely to be part of the URL.
                $original_url = rtrim($matches[0], '.,:;!?)\'">');
                $tracked_url  = self::create_tracked_link($original_url, $contact_id);
                return $tracked_url ?: $original_url;
            },
            $text
        );

        return $text;
    }
}
