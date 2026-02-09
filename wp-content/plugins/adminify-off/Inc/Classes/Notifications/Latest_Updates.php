<?php

namespace WPAdminify\Inc\Classes\Notifications;

use WPAdminify\Inc\Classes\Notifications\Model\Notice;

if (!class_exists('Latest_Updates')) {
	/**
	 * Latest Pugin Updates Notice Class
	 *
	 * Jewel Theme <support@jeweltheme.com>
	 */
	class Latest_Updates extends Notice
	{

		/**
		 * Latest Updates Notice
		 *
		 * @return void
		 */
		public function __construct()
		{
			parent::__construct();
			if(is_admin()){
                add_action( 'admin_footer', array( $this, 'jltwp_handle_plugin_update_notice_dismiss' ),99999 );
				add_action( 'wp_ajax_jltwp_plugin_update_info', array( $this, 'jltwp_plugin_update_info' ) );
            }
		}

        /**
         * Handles the AJAX request for the plugin update info notice dismissal.
         *
         * Triggered when the user clicks the Dismiss button in the notice.
         *
         * @since 1.0
         *
         * @return void
         */
		public function jltwp_plugin_update_info() {
			if (!current_user_can('install_plugins')) {
                return;
            }

            // Verify nonce for security.
            check_ajax_referer( 'dismiss_notice_nonce', 'nonce' );
            wp_send_json_success( array( 'message' => 'Notice dismissed.', 'data' => update_option('_wpadminify_plugin_update_info_notice', "dismissed" ) ) );
		}


		/**
		 * Notice Content
		 *
		 * @author Jewel Theme <support@jeweltheme.com>
		 */
		public function notice_content()
		{
			if("dismissed" !== get_option('_wpadminify_plugin_update_info_notice', true )){
				$jltwp_adminify_changelog_message = sprintf(
					__('%3$s %4$s %5$s %6$s <br> <strong>Check Changelogs for </strong> <a href="%1$s" target="__blank">%2$s</a>', 'adminify'),
					esc_url_raw('https://wpadminify.com/changelogs'),
					__('More about Updates ', 'adminify'),
					/** Changelog Items
					 * Starts from: %3$s
					 */

					'<h3 class="adminify-update-head">' . WP_ADMINIFY . ' <span><small><em>v' . esc_html(WP_ADMINIFY_VER) . '</em></small>' . __(' has some updates..', 'adminify') . '</span></h3><br>', // %3$s
					__('<span class="dashicons dashicons-yes"></span> <span class="adminify-changes-list">Fixed upgrader issue </span><br>', 'adminify'),
					__('<span class="dashicons dashicons-yes"></span> <span class="adminify-changes-list"> Metabox Plugin Compatibility Issue fixed </span><br>', 'adminify'),
					__('<span class="dashicons dashicons-yes"></span> <span class="adminify-changes-list"> Added Plugin compatibility support for "Email Template Customizer for WooCommerce" plugin. </span><br>', 'adminify'),
				);

				printf(wp_kses_post($jltwp_adminify_changelog_message));
			}
		}

		/**
		 * Notice Header
		 *
		 * @author Jewel Theme <support@jeweltheme.com>
		 */
		public function notice_header() {
			if("dismissed" !== get_option('_wpadminify_plugin_update_info_notice', true )){ ?>
				<div class="hide-notice--ignored notice notice-wp-adminify is-dismissible notice-<?php echo esc_attr( $this->color ); ?> wp-adminify-notice-<?php echo esc_attr( $this->get_id() ); ?> notice-plugin-update-info">
					<button type="button" class="notice-dismiss wp-adminify-notice-dismiss" data-notice-type="plugin_update_notice"></button>
					<div class="notice-content-box">
				<?php
			}else{ echo '<div class="hide-notice--ignored customDiv" ><div>';}
		}

		public function jltwp_handle_plugin_update_notice_dismiss() { ?>

			<script>

				function jltwp_adminify_update_plugin_info_notice_action(evt, $this, action_type) {
					if (evt) evt.preventDefault();
					$this.closest('.notice-plugin-update-info').slideUp(200);

					jQuery.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
						action: 'jltwp_plugin_update_info',
						_wpnonce: '<?php echo esc_js( wp_create_nonce( 'dismiss_notice_nonce' ) ); ?>',
						action_type: action_type,
						plugin_name: $this.data('noticetype')
					}).then(function(response) {
						console.log(response);
					});
				}

				// Notice Dismiss
				jQuery('body').on('click', '.notice-wp-adminify.notice-plugin-update-info .wp-adminify-notice-dismiss', function(evt) {
					jltwp_adminify_update_plugin_info_notice_action(evt, jQuery(this), 'dismiss');
				});
			</script>

		<?php
	}

		/**
		 * Intervals
		 *
		 * @author Jewel Theme <support@jeweltheme.com>
		 */
		public function intervals()
		{
			return array(0);
		}
	}
}
