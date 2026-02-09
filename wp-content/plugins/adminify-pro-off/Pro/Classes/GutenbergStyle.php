<?php

namespace WPAdminify\Pro;

if (!class_exists('GutenbergStyle')) {
    class GutenbergStyle
    {

        private static $instance;
        /* Construction Function */
        public function __construct()
        {
            add_action('enqueue_block_editor_assets', [$this, 'fix_gutenberg_style']);
        }

        public function fix_gutenberg_style()
        {
            $editor_fix_css  = '';
            $editor_fix_css .= '.postbox {
                    border: 1px solid #dfdfdf !important;
                    margin: 10px 0;
                }

                .postbox-container {
                    width: 100%;
                    background: #f1f1f1;
                }

                .postbox-header {
                    border-bottom: 0;
                }

                .components-panel__body {
                    border: 1px solid #dfdfdf;
                    border-top: none!important;
                    margin: 10px 0;
                    background: #fff;
                }

                .components-panel {
                    background: #f1f1f1;
                }

                .edit-post-meta-boxes-area .postbox .handle-order-higher,
                .edit-post-meta-boxes-area .postbox .handle-order-lower {
                    width: 22px;
                    height: 22px;
                }

                .hndle.ui-sortable-handle {
                    border-bottom: 0 !important;
                }

                .components-button[aria-expanded=true] {
                    border-bottom: 1px solid var(--wp-admin-theme-color) !important;
                    border-radius: 0 !important;
                }

                .components-panel__body {
                    margin: 10px -1px;
                }

                .components-panel__body.is-opened {
                    border: 1px solid var(--wp-admin-theme-color) !important;
                    margin: 10px 0;
                }';

            $editor_fix_css = preg_replace('#/\*.*?\*/#s', '', $editor_fix_css);
            $editor_fix_css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $editor_fix_css);
            $editor_fix_css = preg_replace('/\s\s+(.*)/', '$1', $editor_fix_css);
            wp_add_inline_style('wp-editor', wp_strip_all_tags($editor_fix_css));
        }


        public static function get_instance()
        {
            if (!isset(self::$instance) && !(self::$instance instanceof GutenbergStyle)) {
                self::$instance = new GutenbergStyle();
            }

            return self::$instance;
        }
    }
    GutenbergStyle::get_instance();
}
