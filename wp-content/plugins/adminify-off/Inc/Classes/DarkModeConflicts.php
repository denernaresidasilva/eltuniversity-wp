<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}
/**
 * WPAdminify
 * Dark Mode Conflicts with other plugins supports
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class DarkModeConflicts
{
    public $options;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'jltwp_adminify_darkmode_scripts'), 100);
    }

    public function jltwp_adminify_darkmode_scripts()
    {

        $this->options = (array) AdminSettings::get_instance()->get();
        $this->options = !empty($this->options['light_dark_mode']['admin_ui_mode']) ? $this->options['light_dark_mode']['admin_ui_mode'] : '';

        if ($this->options == 'light') {
            return;
        }


        $dark_mode_style = '.ReactModal__Content,
        .select2-selection,
        .select2-results__options,
        .adminify-dark-mode.toplevel_page_aio-contact-lite,
        .aio-contact-menu,
        .bsr-action-form input.regular-text,
        .select2-dropdown,
        .form-cancel-btn,
        .folders-tabs,
        .folder-tab-menu,
        .folder-tab-menu a,
        .custom-checkbox span,
        .preview-inner-box,
        .preview-inner-box .form-options,
        .media-buttons,
        .popup-form-content,
        .select2-results__option,
        div.tagsinput,
        .settings-tabs-list .nav-tab,
        input[type=number],
        .bulk-action-select .bulk-action-select__control,
        .bulk-action-select__menu,
        .wprf-section-fields,
        .wprf-section-title,
        .wprf-input-radio-option:not(.wprf-option-selected) .wprf-input-label,
        .wprf-control-field .components-button,
        .wprf-select__menu,
        .wprf-select__control,
        .wprf-tab-content,
        .squirrly-seo-settings #wpcontent .bg-light,
        .bootstrap-select,
        .bg-white,
        .wdt-addons-intro {
            background: white !important;
        }

        .el-checkbox.is-checked ~ div,
        .fluentcrm_header {
            background: none!important;
        }

        .folder-user-settings .pro-feature-popup {
            background: rgb(0, 0, 0, 0.4)
        }

        .big-pluspro-btn,
        .dokan-btn,
        .close_dp_help,
        .dup-btn,
        .folder-tab-menu a,
        .pro-feature-popup .pro-feature-content > a,
        .user-upgrade-inline-btn,
        .pro-feature-popup .pro-feature-content .pro-user-title,
        .pro-feature-popup .pro-feature-content .pro-user-desc,
        .folder-access,
        .add-new-folder,
        .media-select option,
        .popup-form-content,
        .nf-button:hover,
        .wp-react-form h1,
        .wp-react-form h2,
        .wp-react-form h3,
        .wp-react-form h4,
        .wprf-input-label,
        .wprf-tab-nav-item:not(:hover),
        .swift-btn,
        .wpcode-button,
        .wdt-constructor-type-selecter-block .card-body h4,
        .wdt-constructor-type-selecter-block .card-body h4 span,
        .yoast-button-upsell,
        .aioseo-button:hover,
        .el-button {
            color: white !important;
        }

        .folders-tabs .dashicons-editor-help,
        .folder-tab-menu a.active,
        .folder-list li a span,
        .select2-selection__rendered,
        .fp-item-name,
        .select2-results__option,
        .form-sample,
        input[type=number],
        .wprf-select__single-value,
        .wprf-code-viewer textarea,
        .wdt-constructor-type-selecter-block .card-body span,
        .mce-menu-item > span  {
            color: gray !important;
        }

        .select2-selection,
        .bsr-action-form input.regular-text,
        #bsr-table-select,
        .custom-checkbox input+span,
        .preview-inner-box,
        div.tagsinput,
        input[type=number],
        .bulk-action-select .bulk-action-select__control,
        .wprf-tab-nav-item,
        .wprf-control-field .components-button,
        .wprf-select__control,
        .wprf-control-field input[type="checkbox"],
        .bootstrap-select {
            border-color: #D0D5DD !important;
        }

        .dokan-admin-header-logo img,
        .dup-header img {
            filter: grayscale(1) brightness(5);
        }

        .jetpack-logo,
        .it-ui-list div[direction="horizontal"] > svg {
            fill: black !important;
        }

        .jp-form-block-fade {
            opacity: 0.5;
        }
        ';

        // Elementor Style issues
        // if (Utils::is_plugin_active('elementor/elementor.php')) {
        //     $dark_mode_style .= '.darkmode{
        //          background: white !important;
        //     }';
        // }

        // Better Links Style issues
        if (Utils::is_plugin_active('betterlinks/betterlinks.php')) {
            $dark_mode_style .= '#betterlinksbody .kpyoXL,
            .btl-react-select__menu,
            #betterlinksbody .jlDjrx {
                background: white !important;
            }
            #betterlinksbody .kpyoXL{
                color: white !important;
            }
            ';
        }

        // Forminator Style issues
        if (Utils::is_plugin_active('forminator/forminator.php')) {
            $dark_mode_style .= '.sui-box,
            .sui-icon-plugin-2,
            .sui-vertical-tab a.current,
            .sui-wrap .fui-multi-answers,
            .sui-wrap .fui-multi-answers .fui-answers>li,
            .sui-dropdown.open ul,
            .sui-notice-content,
            .sui-wrap .sui-box-selectors,
            .sui-select-dropdown,
            .sui-accordion-item,
            .sui-tabs-menu,
            .forminator-addon-card--footer,
            .sui-vertical-tab.current,
            .sui-form-control,
            select#forminator-field-user_role,
            .forminator-save-field-settings {
                background: white !important;
            }

            .sui-upgrade-page,
            .sui-upgrade-page-header,
            .sui-upgrade-page-cta {
                background: none!important;
            }

            .sui-header-title,
            .sui-summary-large,
            .sui-list-label,
            .sui-box-title,
            .sui-wrap h1,
            .sui-wrap h2,
            .sui-wrap h3,
            .sui-button,
            .sui-vertical-tab a.current,
            .sui-table thead>tr>th,
            .sui-trim-text,
            .sui-message-content h2,
            .sui-tab-item.active,
            .sui-vertical-tab.current a,
            select#forminator-field-user_role {
                color: white !important;
            }

            .sui-upsell-list li,
            .sui-table-item-title,
            .sui-vertical-tab a,
            .sui-form-control,
            .sui-settings-label {
                color: gray !important;
            }

            .sui-list li,
            .sui-box-header,
            #sui-cross-sell-footer>div,
            .sui-box-settings-row,
            .sui-dropdown.open ul,
            .sui-box-footer,
            .sui-status-changes,
            .sui-table thead>tr>th,
            .sui-select-dropdown,
            .sui-tab-content,
            .forminator-addon-card--footer,
            .sui-table.fui-table--apps,
            .sui-table tbody>tr>td,
            .sui-tabs-overflow,
            .sui-form-control,
            .sui-button,
            select#forminator-field-user_role {
                border-color: #D0D5DD !important;
            }

            .sui-dropdown.open ul::after,
            .sui-dropdown.open ul::before {
                border-color: #D0D5DD rgba(0, 0, 0, 0)!important;
            }

            .sui-box,
            .sui-accordion-item,
            .sui-tabs-menu {
                box-shadow: none!important;
            }

            .sui-chartjs-message--empty {
                background-blend-mode: exclusion;
            }

            .sui-tag,
            .sui-tabs-menu .sui-tab-item {
                background: #6f6f6f!important;
                color: #fff!important;
            }

            .sui-tab-item.active {
                background: black!important;
            }
            ';
        }

        // Loginpress
        if (Utils::is_plugin_active('loginpress/loginpress.php')) {
            $dark_mode_style .= '.loginpress-help-page pre textarea,
            .loginpress-import-export-page .upload-file,
            .loginpress-extension {
                background: white !important;
            }

            .toplevel_page_loginpress-settings #wpcontent,
            .loginpress_page_loginpress-help #wpcontent,
            .loginpress_page_loginpress-import-export #wpcontent,
            .loginpress-header-wrapper,
            .loginpress-settings {
                background: none!important;
            }

            .loginpress-help-page h2,
            .loginpress-import-export-page h2,
            .loginpress-addons-wrap h2,
            .loginpress-extension h3 span,
            .loginpress-settings h3{
                color: white !important;
            }

            .loginpress-help-page pre textarea,
            .loginpress-import-export-page > div,
            .loginpress-settings .form-table tr th,
            .loginpress-settings .form-table tr td fieldset label {
                color: gray !important
            }

            .loginpress-help-page pre textarea,
            .loginpress-import-export-page .upload-file,
            .loginpress-extension h3,
            .loginpress-extension {
                border-color: #D0D5DD !important;
            }

            .loginpress-header-logo img {
                filter: grayscale(1) brightness(5);
            }

            ';
        }

        // Notificationx Style issues
        if (Utils::is_plugin_active('notificationx/notificationx.php')) {
            $dark_mode_style .= '.nx-analytics-counter,
            #notificationx .notificationx-items .nx-admin-menu>ul li:not(.nx-active) a,
            .nx-admin-sidebar .sidebar-widget,
            .nx-settings-right,
            .nx-admin-block,
            .nx-sidebar,
            .nx-quick-builder-wrapper {
                 background: white !important;
            }

            .nx-button:not(:hover) {
                background: none!important;
            }

            .nx-counter-number,
            .nx-admin-title {
                color: white !important;
            }

            #notificationx .notificationx-items .nx-admin-menu>ul li:not(.nx-active) a,
            #nx-title,
            #notification-template,
            .nx-admin-sidebar-cta a:not(:hover) {
                color: gray !important;
            }

            #notificationx .notificationx-items .nx-admin-menu>ul li:not(.nx-active) a,
            #nx-title,
            .nx-widget-title,
            #notification-template {
                border-color: #D0D5DD !important;
            }

            .nx-admin-sidebar-logo img {
                filter: grayscale(1) brightness(5);
            }

            .nx-admin-header svg g {
                fill: #fff!important;
            }
            ';
        }

        // Wpdatatables Style issues
        if (Utils::is_plugin_active('wpdatatables/wpdatatables.php')) {
            $dark_mode_style .= '.wpdt-c.toplevel_page_wpdatatables-dashboard,
            .wpdt-c .wdt-datatables-admin-wrap .plugin-dashboard,
            .wpdt-c.wpdatatables_page_wpdatatables-administration,
            .card-header.wdt-admin-card-header.ch-alt,
            #wdt-datatables-browse-table,
            .manage-column,
            .wpdt-c.wpdatatables_page_wpdatatables-constructor,
            .wdt-constructor-type-selecter-block .card-header,
            .wpdt-c,
            .chart-wizard-breadcrumb,
            .wpdt-c .chart-name,
            .wpdt-c .render-engine {
                 background: white !important;
            }

            ';
        }



        $dark_mode_style = preg_replace('#/\*.*?\*/#s', '', $dark_mode_style);
        $dark_mode_style = preg_replace('/\s*([{}|:;,])\s+/', '$1', $dark_mode_style);
        $dark_mode_style = preg_replace('/\s\s+(.*)/', '$1', $dark_mode_style);
        wp_add_inline_style('wp-adminify-admin', wp_strip_all_tags($dark_mode_style));
    }
}
