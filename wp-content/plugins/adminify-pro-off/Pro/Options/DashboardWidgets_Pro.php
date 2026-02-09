<?php

namespace WPAdminify\Pro;

use \WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Modules\DashboardWidget\DashboardWidgetModel;
use WPAdminify\Inc\Modules\DashboardWidget\DashboardWidget_Setttings;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @package WPAdminify
 * Quick Menu
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class DashboardWidgets_Pro extends DashboardWidgetModel{

    public $options;

    public function __construct()
    {

        add_filter('adminify_settings/dashboar_widgets', [$this, 'dashboard_widgets_pro_settings'], 9999, 2);

        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            add_filter('dashboard_widgets/welcome_css', [$this, 'welcome_widget_css'], 9999);
            add_action('dashboard_widgets/render_icon', [$this, 'dashboard_widgets_render_icon'], 999 );
            add_action('dashboard_widgets/render_video', [$this, 'dashboard_widgets_render_video'], 999 );
            add_action('dashboard_widgets/render_script', [$this, 'dashboard_widgets_render_script'], 999 );
            add_action('dashboard_widgets/render_rss_feed', [$this, 'dashboard_widgets_render_rss_feed'], 999 );
            add_action('dashboard_widgets/render_shortcode', [$this, 'dashboard_widgets_render_shortcode'], 999 );
        }
    }

    public function dashboard_widgets_render_rss_feed( $value ){
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            // Render RSS Widget Content
            echo '<div class="wp-adminify-rss-widget">';
            wp_widget_rss_output(
                $value['args']['dashw_type_rss_feed'],
                [
                    'items'        => isset($value['args']['dashw_type_rss_count']) ? $value['args']['dashw_type_rss_count'] : 5,
                    'show_summary' => isset($value['args']['dashw_type_rss_excerpt']) ? $value['args']['dashw_type_rss_excerpt'] : 1,
                    'show_author'  => isset($value['args']['dashw_type_rss_author']) ? $value['args']['dashw_type_rss_author'] : 1,
                    'show_date'    => isset($value['args']['dashw_type_rss_date']) ? $value['args']['dashw_type_rss_date'] : 1,
                ]
            );
            echo '</div>';
        }
    }

    public function dashboard_widgets_render_script( $value ){
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            if (! empty($value['args']['dashw_type_script'])) {
                $stat_tag     = '/<script>/m';
                $end_tag      = '#</script>#m';
                $result_start = preg_match($stat_tag, $value['args']['dashw_type_script']);
                $result_end   = preg_match($end_tag, $value['args']['dashw_type_script']);
                echo "\n<!-- Start of WP Adminify - Dashboard Widget Custom CSS -->\n";
                echo (! $result_start && ! $result_end) ? '<script>' : '';
                echo esc_js("\n{$value['args']['dashw_type_script']}\n");
                echo (! $result_start && ! $result_end) ? '</script>' : '';
                echo "\n<!-- /End of WP Adminify - Dashboard Widget Custom CSS -->\n";
            }
        }
    }

    public function dashboard_widgets_render_shortcode($value)
    {
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            echo do_shortcode($value['args']['dashw_type_shortcode']);
        }
    }


    public function dashboard_widgets_render_video( $value ){
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            $video_type  = $value['args']['dashw_video']['dashw_type_video_type'];
            $video_title = isset($value['args']['dashw_video']['dashw_type_video_title']) ? $value['args']['dashw_video']['dashw_type_video_title'] : '';
            if ($video_type === 'self_hosted') {
                echo '<video width="640" height="280" src="' . esc_url($value['args']['dashw_video']['dashw_type_video_type_self_hosted']['url']) . '" controls>
                        Sorry, your browser doesn\'t support HTML5 <code>video</code>, but you can download this video from the
                        <a href="https://archive.org/details/Popeye_forPresident" target="_blank">Internet Archive</a>. </video>';

            } elseif ($video_type === 'youtube') {

                $embed_url = $this->get_youtube_video_embed_url(esc_url($value['args']['dashw_video']['dashw_type_video_type_youtube']));
                echo '<iframe width="420" height="280" style="height: auto; width: 100%; aspect-ratio: 16 / 9;" src="' . $embed_url . '?controls=0&autoplay=1"></iframe>';

            } elseif ($video_type === 'vimeo') {

                $video_id = $this->get_vimeo_video_id(esc_attr($value['args']['dashw_video']['dashw_type_video_type_vimeo']));
                echo '<iframe style="height: auto; width: 100%; aspect-ratio: 16 / 9;" src="https://player.vimeo.com/video/' . esc_attr($video_id) . '?title=0&byline=0&portrait=0" width="640" height="280" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';

            } elseif ($video_type === 'iframe') {
                echo '<iframe style="height: auto; width: 100%; aspect-ratio: 16 / 9;" src="' . esc_url( $value['args']['dashw_video']['dashw_type_video_type_iframe'] ) . '" width="640" height="280" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
            }
        }
    }

    public function dashboard_widgets_render_icon( $value ){
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            if (! empty($value['args']['dashw_type_icon'])) {
                echo '<div class="adminify-dash-icon">
                        <div class="icon">
                            <i class="' . esc_attr($value['args']['dashw_type_icon']) . '"></i>
                        </div>
                        <div>
                        <a href="' . esc_url($value['args']['dashw_type_icon_link']['url']) . '" target="' . esc_attr($value['args']['dashw_type_icon_link']['target']) . '">
                        ' . esc_html($value['args']['dashw_type_icon_tooltip']) . '</a></div>
                    </div>';
            }
        }
    }


    public function welcome_widget_css($css)
    {
        $this->options = (new DashboardWidget_Setttings())->get();
        $this->options = !empty($this->options['dashboard_widget_types']['welcome_dash_widget']) ? $this->options['dashboard_widget_types']['welcome_dash_widget'] : '';

        // Apply a filter for the dismissible part
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            $dismissible = !empty($this->options['dismissible']) ? true : false;
            if ($dismissible) {
                $css .= '.adminify-welcome-panel .welcome-panel-close {
                    display: block;
                    position: absolute;
                    top: -13px;
                    right: 10px;
                    padding: 10px 15px 10px 24px;
                    font-size: 13px;
                    line-height: 1.23076923;
                    text-decoration: none;
                    z-index: 1;
                    color:rgb(143, 15, 143);
                    margin-top: 20px;
                }
                .adminify-welcome-panel .welcome-panel-close:hover{
                    color: #72aee6;
                }';
            } else {
                $css .= '.adminify-welcome-panel .welcome-panel-close { display: none; }';
            }
        }

        // Apply filters for further modification of $css
        $css = str_replace(["\r\n", "\n", "\r\t", "\t", "\r"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);

        // Allow other functions to modify $css before returning
        return $css;
    }


    public function dashboard_widgets_pro_settings($fields, $class) {
        // Dashboard Widget Group
        $index                       = array_search('dashboard_widget_types', array_column($fields, 'id'));
        $dashboard_widgets_tab_index = array_search('dashboard_widgets_tab', array_column($fields[$index]['tabs'], 'id'));
        $dashboard_widgets_index     = array_search('dashboard_widgets', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'], 'id'));
        unset( $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['max'] ) ;
        unset( $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['max_text'] ) ;

        // Icon
        $dashw_type_icon_index     = array_search('dashw_type_icon', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_icon_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_icon_index]['title'] = __('Icon', 'adminify');


        // Tooltip Text
        $dashw_type_icon_tooltip_index     = array_search('dashw_type_icon_tooltip', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_icon_tooltip_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_icon_tooltip_index]['title'] = __('Tooltip Text', 'adminify');

        // Tooltip Text
        $dashw_type_icon_link_index     = array_search('dashw_type_icon_link', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_icon_link_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_icon_link_index]['title'] = __('Link', 'adminify');

        // Shortcode Text
        $dashw_type_shortcode_index     = array_search('dashw_type_shortcode', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_shortcode_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_shortcode_index]['title'] = __('Shortcode', 'adminify');

        // Script
        $dashw_type_script_index     = array_search('dashw_type_script', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_script_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_script_index]['title'] = __('Script', 'adminify');

        // RSS Feed URL
        $dashw_type_rss_feed_index     = array_search('dashw_type_rss_feed', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_feed_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_feed_index]['title'] = __('RSS Feed URL', 'adminify');

        // No. of Feed Posts
        $dashw_type_rss_count_index     = array_search('dashw_type_rss_count', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_count_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_count_index]['title'] = __('No. of Feed Posts', 'adminify');

        // Show Excerpt?
        $dashw_type_rss_excerpt_index     = array_search('dashw_type_rss_excerpt', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_excerpt_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_excerpt_index]['title'] = __('Show Excerpt?', 'adminify');

        // Show Date
        $dashw_type_rss_date_index = array_search('dashw_type_rss_date', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_date_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_date_index]['title'] = __('Show Date?', 'adminify');

        // Show Author
        $dashw_type_rss_author_index = array_search('dashw_type_rss_author', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_author_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_type_rss_author_index]['title'] = __('Show Author?', 'adminify');

        // Video Index
        $dashw_video_index = array_search('dashw_video', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'], 'id'));

        //dashw_type_video_title
        $dashw_type_video_title_index = array_search('dashw_type_video_title', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_title_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_title_index]['title'] = __('Text', 'adminify');


        //dashw_type_video_type_self_hosted
        $dashw_type_video_type_self_hosted_index = array_search('dashw_type_video_type_self_hosted', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_self_hosted_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_self_hosted_index]['title'] = __('Upload Video', 'adminify');


        //dashw_type_video_type_youtube
        $dashw_type_video_type_youtube_index = array_search('dashw_type_video_type_youtube', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_youtube_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_youtube_index]['title'] = __('Youtube URL', 'adminify');

        //dashw_type_video_type_vimeo
        $dashw_type_video_type_vimeo_index = array_search('dashw_type_video_type_vimeo', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_vimeo_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_vimeo_index]['title'] = __('Vimeo URL', 'adminify');

        //dashw_type_video_type_iframe
        $dashw_type_video_type_iframe_index = array_search('dashw_type_video_type_iframe', array_column($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'], 'id'));
        unset($fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_iframe_index]['class']);
        $fields[$index]['tabs'][$dashboard_widgets_tab_index]['fields'][$dashboard_widgets_index]['fields'][$dashw_video_index]['fields'][$dashw_type_video_type_iframe_index]['title'] = __('iFrame Video URL', 'adminify');

        // Dismissible Checkbox
        $index                                                                                                                           = array_search('dashboard_widget_types', array_column($fields, 'id'));
        $welcome_widgets_tab_index                                                                                                       = array_search('welcome_widgets_tab', array_column($fields[$index]['tabs'], 'id'));
        $welcome_dash_widget_index                                                                                                       = array_search('welcome_dash_widget', array_column($fields[$index]['tabs'][$welcome_widgets_tab_index]['fields'], 'id'));
        $dismissible_index                                                                                                               = array_search('dismissible', array_column($fields[$index]['tabs'][$welcome_widgets_tab_index]['fields'][$welcome_dash_widget_index]['fields'], 'id'));
        $fields[$index]['tabs'][$welcome_widgets_tab_index]['fields'][$welcome_dash_widget_index]['fields'][$dismissible_index]['title'] = __('Dismissible', 'adminify');
        unset($fields[$index]['tabs'][$welcome_widgets_tab_index]['fields'][$welcome_dash_widget_index]['fields'][$dismissible_index]['class']);


        return $fields;
    }

    public function get_youtube_video_embed_url($url) {
        $pattern = '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        preg_match($pattern, $url, $matches);

        if (!empty($matches[1])) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        return false; // Invalid URL
    }

    public function get_vimeo_video_id($url) {
        preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url, $matches);
        return isset($matches[1]) ? $matches[1] : $url;
      }
}
