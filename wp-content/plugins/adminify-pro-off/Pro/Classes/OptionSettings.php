<?php

namespace WPAdminify\Pro;


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

class OptionSettings {
    public function __construct()
    {
        $this->options_settings();
        add_action('admin_init', [$this, 'maybe_clone_blog_options']);
        add_action('admin_enqueue_scripts', [$this, 'jltwp_adminify_admin_scripts'], 9999);
    }

    public function options_settings(){
        new Customize_Pro();
        new MenuLayout_Pro();
        new Productivity_Pro();
        new Security_Pro();
        new Performance_Pro();
        new White_Label_Pro();
        new DashboardWidgets_Pro();
    }

    /**
     * Admin Settings CSS
     *
     * @return void
     */
    public function jltwp_adminify_admin_scripts()
    {
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            wp_enqueue_style('wp-adminify-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
            wp_enqueue_script('wp-adminify-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], false, true);

            ob_start();

            ?>

            jQuery(function($) {

            $('.select-field').select2({width: '100%'});

            $('.copy_to').on('change', function() {
            if ( $(this).val() == 'copy_to_all' ) {
            $(this).closest('.wp-clone-sites-options').find('.copy_exclude-field-wrapper').show();
            } else {
            $(this).closest('.wp-clone-sites-options').find('.copy_exclude-field-wrapper').hide();
            }
            });

            $('#option_modules_toggle').on('click', function(e) {
            e.preventDefault();
            $checkboxes = $(this).closest('.line-single--content').find('input[type="checkbox"]');
            $checked = $checkboxes.filter(':checked');
            $status = true;
            if ( $checked.length == $checkboxes.length ) $status = false;
            $checkboxes.each(function(){ $(this).prop('checked', $status) });
            });

            });

            <?php

            $script = ob_get_clean();

            wp_add_inline_script('wp-adminify-select2', $script);

            $output_css = '.wp-adminify-settings .dashicons,.wp-adminify-settings .dashicons-before:before{vertical-align:middle}.adminify-status{background:#fff;padding:12px 10px;margin:30px 0;-webkit-border-radius:4px;border-radius:4px;-webkit-box-shadow:0 0 8px rgba(139,148,169,.15);box-shadow:0 0 8px rgba(139,148,169,.15)}.adminify-status.adminify-status--success{border-left:4px solid #48cf5b}.adminify-status.adminify-status--error{border-left:4px solid #f16b6b}.adminify-status p{margin:0}.adminify-status p:not(:last-child){margin-bottom:10px}.wp-clone-sites-options h1{margin:10px 0 30px}.wp-clone-sites-options{max-width:800px; margin: 50px auto;}.container.wp-clone-sites-options form{padding:30px;background:#fff;margin:20px 0;-webkit-border-radius:4px;border-radius:4px;-webkit-box-shadow:0 0 24px rgba(108,111,120,.15);box-shadow:0 0 24px rgba(108,111,120,.15)}.wp-clone-sites-options .select-field{width:100%}.line-single--wrapper{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;margin-bottom:24px;-webkit-flex-wrap:wrap;-ms-flex-wrap:wrap;flex-wrap:wrap;background:#eff0f3;padding:24px 20px;-webkit-border-radius:4px;border-radius:4px}.line-single--title{width:100%;margin-bottom:10px;font-weight:700}.line-single--content{width:100%;display:-webkit-inline-box;display:-webkit-inline-flex;display:-ms-inline-flexbox;display:inline-flex;-webkit-flex-wrap:wrap;-ms-flex-wrap:wrap;flex-wrap:wrap}.line-single--content>div{width:33.333333%;margin-bottom:8px}button#option_modules_toggle{padding:8px 10px;line-height:1;margin-top:5px;border:none;background:#fff;-webkit-border-radius:4px;border-radius:4px;cursor:pointer;-webkit-box-shadow:0 0 4px #ddd;box-shadow:0 0 4px #ddd}';

            printf('<style>body.toplevel_page_wp-adminify-settings.network-admin{%s}</style>', wp_strip_all_tags($output_css));

            $select2_css = '.wp-adminify .select2-container .select2-selection--single .select2-selection__rendered {
                color: #000;
                line-height: 34px;
            }

            .select2-container--default .select2-selection--single, .select2-dropdown, .select2-container--default .select2-search--dropdown .select2-search__field {
                border: 1px solid #d1d1d1;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 34px;
            }

            .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                line-height: 1.6;
            }

            .select2-container .select2-selection--multiple .select2-selection__rendered {
                vertical-align: sub;
            }

            span.select2-search.select2-search--inline {
                vertical-align: super;
            }

            .select2-container--default .select2-search--inline .select2-search__field {
                background: none !important;
                padding: 0 !important;
            }';

            // Combine the values from above and minifiy them.
            $select2_css = preg_replace('#/\*.*?\*/#s', '', $select2_css);
            $select2_css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $select2_css);
            $select2_css = preg_replace('/\s\s+(.*)/', '$1', $select2_css);
            wp_add_inline_style('wp-adminify-select2', $select2_css);
        }
    }

    public function maybe_clone_blog_options()
		{
			if (jltwp_adminify()->can_use_premium_code__premium_only()) {
				if (empty($_POST)) {
					return;
				}
				if (!is_multisite() || !is_network_admin()) {
					return;
				}
				if (empty($_POST['action']) || $_POST['action'] !== 'adminify_site_option_clone') {
					return;
				}

				check_admin_referer('adminify_site_option_clone');

				if (empty($copy_from = $_POST['copy_from']) || empty($copy_to = $_POST['copy_to'])) {
					return;
				}

				if ($copy_to == 'copy_to_all') {
					$copy_exclude = empty($_POST['copy_exclude']) ? [] : (array) sanitize_text_field(wp_unslash($_POST['copy_exclude']));
				} else {
					$copy_exclude = [];
				}

				if ($copy_from == $copy_to) {
					$this->message = [
						'type'    => 'error',
						'message' => __('Source Site and Target Site should not be same.', 'adminify'),
					];
					return;
				}

				$option_modules = empty($_POST['option_modules']) ? [] : (array) sanitize_text_field(wp_unslash($_POST['option_modules']));

				$options_to_copy = (array) apply_filters('adminify_clone_blog_options', $option_modules, $copy_from, $copy_to, $copy_exclude);

				if ($copy_to == 'copy_to_all') {
					$sites = $this->get_sites();

					foreach ($sites as $site) {
						if ($site->id == $copy_from) {
							continue;
						}
						if (!empty($copy_exclude) && in_array($site->id, $copy_exclude)) {
							continue;
						}
						foreach ($options_to_copy as $option) {
							$data = get_blog_option($copy_from, $option);
							update_blog_option($site->id, $option, $data);
						}
					}
				} else {
					foreach ($options_to_copy as $option) {
						$data = get_blog_option($copy_from, $option);
						update_blog_option($copy_to, $option, $data);
					}
				}

				if (empty($this->message)) {
					$this->message = [
						'type'    => 'success',
						'message' => __('Options are successfully copied to target site.', 'adminify'),
					];
				}
			}
		}

}
