<?php

namespace WPAdminify\Inc\Classes;

class Upgrade_v4_0 extends Upgrade{
  private static $instance = null;

  public function __construct() {
    add_action('admin_notices', array($this, 'check_for_upgrade'));
    add_action('wp_ajax_jltwp_adminify_upgrade_v4_0_db', array($this, 'upgrade_database'));
  }



  public function check_for_upgrade(){
    $current_version = get_option($this->option_name);

    if ($current_version !== WP_ADMINIFY_VER) {
      $this->show_admin_notice();
    }
  }

  public function show_admin_notice(){ ?>
        <div class="hide-notice--ignored notice notice-warning">
            <p><?php _e('A database upgrade is required due to the latest plugin version. Please click the button below to upgrade.', 'adminify'); ?></p>
            <p>
                <button id="upgrade-db" class="button button-primary"><?php _e('Update Adminify Database', 'adminify'); ?></button>
                <a href="https://wpadminify.com/what-is-new-in-wp-adminify-v4-0/" target="_blank" class="button button-secondary"><?php _e('Learn more about updates', 'adminify'); ?></a>
            </p>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#upgrade-db').on('click', function(e) {
                    e.preventDefault();

                    var $button = $(this);
                    $button.prop('disabled', true);

                    $.post(ajaxurl, {
                        action: 'jltwp_adminify_upgrade_v4_0_db',
                        security: '<?php echo wp_create_nonce('jltwp_adminify_upgrade_v4_0_db_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                            $button.prop('disabled', false);
                        }
                    });
                });
            });
        </script>
        <?php
  }

  public function upgrade_database()
  {
    check_ajax_referer('jltwp_adminify_upgrade_v4_0_db_nonce', 'security');

    $old_data = get_option('_wpadminify_backup', []);

    // Google Page Speed Backup
    if ( !empty($old_data['google_pagepseed_api_key']) ) {
      update_option('google_pagepseed_api_key', $old_data['google_pagepseed_api_key']);
    }

    $this->update_data_migration($old_data);

    // Plugin Version Update
    update_option($this->option_name, WP_ADMINIFY_VER);

    wp_send_json_success();
  }

  public static function get_instance()
  {
    if (!isset(self::$instance) && !(self::$instance instanceof Upgrade_v4_0)) {
      self::$instance = new Upgrade_v4_0();
    }

    return self::$instance;
  }

  public function update_data_migration($jltwp_adminify_old_data) {
    
    
    function jltwp_adminify_upgrade_v4_data($old_data) {
    
        $data = [];
    
        // MOVE KEYS
        $move_array_keys = [
            // Customize
            'admin_bar_settings.admin_bar_comments'     => 'admin_bar_notif',
            'admin_bar_settings.admin_bar_search'       => 'admin_bar_search',
    
            'admin_bar_settings.admin_bar_dark_light_btn' => 'admin_bar_dark_light_btn',
            'adminify_theme_custom_colors.--adminify-btn-bg' => 'adminify_theme_custom_colors.--adminify-primary',
            'admin_general_bg'                               => 'body_fields.admin_general_bg',
            'admin_general_bg_gradient'                      => 'body_fields.admin_general_bg_gradient',
            'admin_general_bg_image'                         => 'body_fields.admin_general_bg_image',
            'admin_bar_mode'                                 => 'light_dark_mode.admin_ui_mode',
            'admin_bar_logo_type'                            => 'light_dark_mode.admin_ui_logo_type',
            // 'admin_bar_light_mode'                           => 'light_dark_mode.admin_ui_light_mode',
    
            // light logo
            'admin_bar_light_mode.admin_bar_light_logo_text'      => 'light_dark_mode.admin_ui_light_mode.admin_ui_light_logo_text',
            'admin_bar_light_mode.admin_bar_light_logo_text_typo' => 'light_dark_mode.admin_ui_light_mode.admin_ui_light_logo_text_typo',
            'admin_bar_light_mode.admin_bar_light_logo'           => 'light_dark_mode.admin_ui_light_mode.admin_ui_light_logo',
            'admin_bar_light_mode.mini_admin_bar_light_logo'      => 'light_dark_mode.admin_ui_light_mode.mini_admin_ui_light_logo',
            'admin_bar_light_mode.light_logo_size'                => 'light_dark_mode.admin_ui_light_mode.light_logo_size',
            // Dark logo
            'admin_bar_dark_mode.admin_bar_dark_logo_text'      => 'light_dark_mode.admin_ui_dark_mode.admin_ui_dark_logo_text',
            'admin_bar_dark_mode.admin_bar_dark_logo_text_typo' => 'light_dark_mode.admin_ui_dark_mode.admin_ui_dark_logo_text_typo',
            'admin_bar_dark_mode.admin_bar_dark_logo'           => 'light_dark_mode.admin_ui_dark_mode.admin_ui_dark_logo',
            'admin_bar_dark_mode.mini_admin_bar_dark_logo'      => 'light_dark_mode.admin_ui_dark_mode.mini_admin_ui_dark_logo',
            'admin_bar_dark_mode.dark_logo_size'                => 'light_dark_mode.admin_ui_dark_mode.dark_logo_size',
    
            'enable_schedule_dark_mode'     => 'light_dark_mode.admin_ui_dark_mode.schedule_dark_mode.enable_schedule_dark_mode',
            'schedule_dark_mode_type'       => 'light_dark_mode.admin_ui_dark_mode.schedule_dark_mode.schedule_dark_mode_type',
            'schedule_dark_mode_start_time' => 'light_dark_mode.admin_ui_dark_mode.schedule_dark_mode.schedule_dark_mode_start_time',
            'schedule_dark_mode_end_time'   => 'light_dark_mode.admin_ui_dark_mode.schedule_dark_mode.schedule_dark_mode_end_time',
    
            // Admin Menu
            'menu_layout_settings.user_info'         => 'menu_layout_settings.user_info_fields.enable_user_info',
            'menu_layout_settings.user_info_content' => 'menu_layout_settings.user_info_fields.user_info_content',
            'menu_layout_settings.user_info_avatar'  => 'menu_layout_settings.user_info_fields.user_info_avatar',
    
            // Productivity
            'folders'                                       => 'folders.enable_folders',
            'folders_enable_for'                            => 'folders.enable_for',
            'folders_media'                                 => 'folders.media',
    
            'admin_columns' => 'custom_admin_columns.enable',
            'quick_menu'    => 'quick_menus_data.quick_menus_enable',
            'quick_menus'    => 'quick_menus_data.quick_menus',
    
            'post_types_order'          => 'post_types_order.enable_pto', //TODO: Spacial Check
            'pto_taxonomies'            => 'post_types_order.pto_taxonomies', //TODO: Spacial Check
            'pto_posts'                 => 'post_types_order.pto_posts', //TODO: Spacial Check
            'pto_media'                 => 'post_types_order.pto_media', //TODO: Spacial Check
    
            'post_duplicator'                   => 'post_duplicator.enable_post_duplicator', //TODO: Spacial Check
            'adminify_clone_post_posts'         => 'post_duplicator.post_types', //TODO: Spacial Check
            'adminify_clone_post_taxonomies'    => 'post_duplicator.taxonomies', //TODO: Spacial Check TODO: not exits v_3
    
            'post_page_column_thumb_image'      => 'custom_admin_columns.post_page_column_thumb_image',
    
            'sidebar_widgets_list'                                      => 'remove_widgets.sidebar_widgets_list',
            'sidebar_widgets_disable_gutenberg_editor'                  => 'remove_widgets.disable_gutenberg_editor',
            'dashboard_widgets_list'                                    => 'remove_widgets.dashboard_widgets_list',
    
            // Security
            'disable_comments'            => 'disable_comments.enable_disable_comments',
            'disable_comments_post_types' => 'disable_comments.post_types',
    
            'enable_custom_gravatar' => 'custom_gravatar.enable',
            'custom_gravatar_image'  => 'custom_gravatar.image',
    
            'redirect_urls' => 'redirect_urls_fields.enable_redirect_urls',
    
            'remove_feed'               => 'security_feed',
            'redirect_feed'             => 'security_feed',
    
            // Performance
            'control_heartbeat_api'     => 'heartbeat_api.enabled',
    
            // Code Snippets
            'custom_css' => 'backend.custom_css',
            'custom_js'  => 'backend.custom_js',
    
            // White Label
            'admin_bar_settings.admin_bar_howdy_text' => 'white_label.wordpress.change_howdy_text',
            'footer_text'                             => 'white_label.wordpress.footer_text',
    
            'jltwp_adminify_wl_plugin_logo'            => 'white_label.adminify.plugin_logo',
            'jltwp_adminify_wl_plugin_name'            => 'white_label.adminify.plugin_name',
            'jltwp_adminify_wl_plugin_desc'            => 'white_label.adminify.plugin_desc',
            'jltwp_adminify_wl_plugin_author_name'     => 'white_label.adminify.author_name',
            'jltwp_adminify_wl_plugin_menu_label'      => 'white_label.adminify.menu_label',
            'jltwp_adminify_wl_plugin_url'             => 'white_label.adminify.plugin_url',
            'jltwp_adminify_wl_plugin_row_links'       => 'white_label.adminify.row_links',
    
        ];
        // $data = \WPAdminify\Inc\Utils::moveNestedKeys($old_data, $move_array_keys);
    
    
        //REPLACE KEYS
        $replace_array_keys = [
            // Productivity
            'admin_notices'    => 'hide_notices',
        ];
        // $data = \WPAdminify\Inc\Utils::replace_keys($data, $replace_array_keys);
    
        // CHECKBOX KEYS
        $checkbox_keys = [
            //Productivity
            'post_thumb_column'            => 'custom_admin_columns.columns_data.post_thumb_column',
            'post_page_id_column'          => 'custom_admin_columns.columns_data.post_page_id_column',
            'comment_id_column'            => 'custom_admin_columns.columns_data.comment_id_column',
            'taxonomy_id_column'           => 'custom_admin_columns.columns_data.taxonomy_id_column',
    
            'remove_welcome_panel'                      => 'other_notices.welcome_panel',
            'remove_php_update_required_nag'            => 'other_notices.php_nag',
            'core_update_notice'                        => 'other_notices.core_update_notice',
            'plugin_update_notice'                      => 'other_notices.plugin_update_notice',
            'theme_update_notice'                       => 'other_notices.theme_update_notice',
    
            // Security
            'disable_xmlrpc'                   => 'security_head.security_head_data.xmlrpc',
            'generator_wp_version'             => 'security_head.security_head_data.generator_wp_version',
            'remove_rsd'                       => 'security_head.security_head_data.rsd',
            'remove_shortlink'                 => 'security_head.security_head_data.shortlink',
            'remove_canonical'                 => 'security_head.security_head_data.canonical',
            'self_ping'                        => 'security_head.security_head_data.self_ping',
    
            'remove_wc_generator'              => 'security_head.security_head_data.wc_generator',
            'remove_revslider_generator'       => 'security_head.security_head_data.revslider_generator',
            'remove_visual_composer_generator' => 'security_head.security_head_data.js_composer_generator',
            'remove_wpml_generator'            => 'security_head.security_head_data.wpml_generator',
            'remove_yoast_generator'           => 'security_head.security_head_data.yoast_generator',
    
            'disable_rest_api'          => 'security_rest_api.security_rest_api_data.rest_api',
            'remove_powered'            => 'security_rest_api.security_rest_api_data.powered',
            'remove_api_head'           => 'security_rest_api.security_rest_api_data.api_head',
            'remove_api_server'         => 'security_rest_api.security_rest_aremove_gutenberg_scriptspi_data.api_server',
            'remove_powered'            => 'security_head.security_rest_api_data.powered',
    
            'disable_comments_admin_bar'            => 'disable_comments.apply_for.admin_bar',
            'disable_comments_menu_redirect'        => 'disable_comments.apply_for.menu_redirect',
            'disable_comments_admin_menu'           => 'disable_comments.apply_for.admin_menu',
            'disable_comments_discussion_menu'      => 'disable_comments.apply_for.discussion_menu',
            'disable_comments_close_front'          => 'disable_comments.apply_for.close_front',
            'remove_comments_notes'                 => 'disable_comments.apply_for.comments_notes',
            'disable_comments_url_field'            => 'disable_comments.apply_for.comments_url_field',
            'disable_comments_replace_author_link'  => 'disable_comments.apply_for.replace_author_link',
            'disable_comments_replace_comment_link' => 'disable_comments.apply_for.replace_comment_link',
            'remove_recentcomments'                 => 'disable_comments.apply_for.recentcomments',
            'disable_comments_hide_existing'        => 'disable_comments.apply_for.hide_existing',
    
            'display_last_modified_date' => 'post_archives.post_archives_data.last_modified_date',
            'remove_capital_p_dangit'    => 'post_archives.post_archives_data.capital_p_dangit',
            'remove_archives_date'       => 'post_archives.post_archives_data.archives_date',
            'remove_archives_author'     => 'post_archives.post_archives_data.archives_author',
            'remove_archives_tag'        => 'post_archives.post_archives_data.archives_tag',
            'remove_archives_category'   => 'post_archives.post_archives_data.archives_category',
            'remove_archives_postformat' => 'post_archives.post_archives_data.archives_postformat',
            'remove_archives_search'     => 'post_archives.post_archives_data.archives_search',
    
            // Performance
            'remove_dashicons'                  => 'performance.performance_data.dashicons',
            'remove_version_strings'            => 'performance.performance_data.version_strings',
            'gravatar_query_strings'            => 'performance.performance_data.gravatar_query_strings',
            'remove_emoji'                      => 'performance.performance_data.emoji',
            'remove_jquery_migrate'             => 'performance.performance_data.jquery_migrate_front',
            'defer_parsing_js_footer'           => 'performance.performance_data.defer_parsing_js_footer',
            'cache_gzip_compression'            => 'performance.performance_data.cache_gzip_compression',
    
            'remove_gutenberg_scripts'          => 'disable_gutenberg.disable_for.remove_gutenberg_scripts',
    
            // White Label
            // WordPress
            // 'admin_footer_ip_address'       => 'white_label.wordpress.admin_footer.ip_address',
            // 'admin_footer_php_version'      => 'white_label.wordpress.admin_footer.php_version',
            // 'admin_footer_wp_version'       => 'white_label.wordpress.admin_footer.wp_version',
            // 'admin_footer_memory_usage'     => 'white_label.wordpress.admin_footer.memory_usage',
            // 'admin_footer_memory_limit'     => 'white_label.wordpress.admin_footer.memory_limit',
            // 'admin_footer_memory_available' => 'white_label.wordpress.admin_footer.memory_available',
            // // Adminify
            // 'jltwp_adminify_remove_action_links'       => 'white_label.adminify.remove_action_links',
            // 'jltwp_adminify_wl_plugin_tab_system_info' => 'white_label.adminify.tab_system_info',
            // 'jltwp_adminify_wl_plugin_option'          => 'white_label.adminify.plugin_option',
        ];
        // $data = \WPAdminify\Inc\Utils::checkboxes($data, $checkbox_keys);
    
    
        // REMOVE KEYSmoveNestedKeys
        $removeArrayKeys = [
            // Admin Menu
            'menu_layout_settings.legacy_menu',
            'menu_layout_settings.show_bloglink',
    
            //Productivity
            'remove_try_gutenberg_panel',
    
            //Security
            'rest_api',
            'disable_comments_dashboard_widget',
            'remove_comments_author_link',
            'remove_comments_autolinking',
            'disable_comments_media',
    
            // Performance
            'remove_pingback',
            'remove_link_url',
            'remove_wlwmanifest',
            'remove_dns_prefetch',
            'remove_prev_next',
            'remove_http_shortlink',
    
            'thumbnails_rss_feed',
            'remove_image_link',
            'remove_attachment',
            'disable_pdf_thumbnail',
    
           'login_customizer',
            'menu_editor',
            'dashboard_widgets',
            'pagespeed_insights',
            'custom_css_js',
            'activity_logs',
            'notification_bar',
            'server_info',
            'sidebar_generator',
            'admin_general_bg_color',
            'admin_general_bg_slideshow',
            'admin_general_bg_video_type',
            'admin_general_bg_video_self_hosted',
            'admin_general_bg_video_youtube',
            'admin_general_bg_video_loop',
            'admin_general_bg_video_poster',
            'admin_glass_effect',
            'admin_danger_button_color',
            // Google pagespeed
            'google_pagepseed_user_roles',
            'google_pagepseed_api_key'
        ];
        // $data = \WPAdminify\Inc\Utils::removeKeys($data, $removeArrayKeys);
    
    
    
        $data = \WPAdminify\Inc\Utils::replace_keys($old_data, $replace_array_keys);
        $data = \WPAdminify\Inc\Utils::removeKeys($data, $removeArrayKeys);
        $data = \WPAdminify\Inc\Utils::moveNestedKeys($data, $move_array_keys);
        $data = \WPAdminify\Inc\Utils::checkboxes($data, $checkbox_keys);
    
        // Redirect Urls data Mirgrate
        $redirect_urls_data = get_option('_wpadminify_redirect_urls');
      
        $migrate_redirect_data = [
          'redirect_urls_fields' => [
            'enable_redirect_urls' => !empty($data['redirect_urls_fields']['enable_redirect_urls']) ? 1 : 0,
            'redirect_urls_options' => [
              'redirect_urls_tabs' => !empty($redirect_urls_data['redirect_urls_options']) ? $redirect_urls_data['redirect_urls_options'] : []
            ]
          ]
        ];
    
        return array_merge($data, $migrate_redirect_data) ;
    }
    
    
    $new_data = jltwp_adminify_upgrade_v4_data($jltwp_adminify_old_data);
    update_option( '_wpadminify', $new_data );
 
    
  }
}

Upgrade_v4_0::get_instance();
