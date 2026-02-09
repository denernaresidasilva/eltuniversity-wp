<?php
namespace WPAdminify\Inc\Classes\Notifications;

use WPAdminify\Inc\Classes\Notifications\Base\User_Data;
use WPAdminify\Inc\Classes\Notifications\Model\Notice;

if ( ! class_exists( 'Module_Conflicts' ) ) {
	/**
	 * Module_Conflicts Class
	 *
	 * Jewel Theme <support@jeweltheme.com>
	 */
	class Module_Conflicts extends Notice {

        public $color = 'warning';

        public function __construct() {
            add_action('admin_notices', [$this, 'maybe_show_folder_module_notice'], -9999999);
            add_action('admin_footer', array($this, 'enqueue_conflict_plugin_admin_scripts'), 99999);
            add_action('wp_ajax_jltwp_adminify_module_conflicts', [$this, 'jltwp_adminify_module_conflicts']);
        }

        public function jltwp_adminify_check_folder_module_conflict(){
            $plugins = [
                'folders/folders.php',
                'filebird/filebird.php',
                'real-media-library-lite/index.php',
                'wicked-folders/wicked-folders.php',
                'real-category-library-lite/index.php',
                'wp-media-folders/wp-media-folders.php',
                'media-library-plus/maxgalleria-media-library.php',
            ];
            $result = $this->maybe_conflicted_plugins_active( $plugins );

            if (!$result) {
                return false;
            }
            return $result;
        }

        /**
         * Dismiss module conflict notice via AJAX.
         *
         * Stores the conflict notice dismissal state in the options table.
         *
         * Handle AJAX request to dismiss the admin notice.
         *
         * @since 1.0.0
         */
        public function jltwp_adminify_module_conflicts() {

            if (!current_user_can('install_plugins')) {
                return;
            }

            // Verify nonce for security.
            check_ajax_referer( 'dismiss_notice_nonce', 'nonce' );
            $conflicted_plugins = [];
            $plugin_exists = get_option('_wpadminify_plugin_conflict');
            $plugin_exists = is_array($plugin_exists) ? $plugin_exists : [];
            $conflicted_plugins = array_merge( $plugin_exists, [$_POST['plugin_name']] );

            // Update user meta to store dismissal state.
            update_option('_wpadminify_plugin_conflict', $conflicted_plugins );

            $this->jltwp_force_disable_module('folders');
            wp_send_json_success( array( 'message' => 'Notice dismissed.' ) );
        }

        /**
         * Disable WP Adminify Module.
         *
         * @param string $module_name Module name to disable.
         */
        public function jltwp_force_disable_module( $module_name) {
            // Disable WP Adminify Folder Module
            $adminSettings = \WPAdminify\Inc\Admin\AdminSettings::get_instance();
            $options       = get_option($adminSettings->get_prefix());

            $options[$module_name] = false; // force disable
            update_option('_wpadminify', $options);
        }


        /**
         * Checks if any of the given plugins are active.
         *
         * @param string[] $plugins The list of plugins to check.
         *
         * @return array|false The plugin data if found, otherwise false.
         */
        public function maybe_conflicted_plugins_active( $plugins )
        {
            // $options = (array) AdminSettings::get_instance()->get();
            // if (!Utils::check_modules($options[$module_name])) {
            // 	return false;
            // }

            $active_plugins = get_option('active_plugins', []);

            $is_found = false;

            $_plugin = null;

            foreach ($active_plugins as $plugin) {
                if (in_array($plugin, $plugins)) {
                    $is_found = true;
                    $_plugin  = $plugin;
                }
            }

            if (!$is_found) {
                return false;
            }

            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            $all_plugins = get_plugins();

            return $all_plugins[$_plugin];
        }



        /**
         * Shows an admin notice about the conflict with other folder plugins.
         *
         * If the user has installed one of the conflicting plugins, this notice will be displayed.
         * The notice will be dismissed if the user clicks on the dismiss button.
         * The notice will also be dismissed if the user has already dismissed the notice before.
         * The folder module will be disabled if the notice is shown.
         *
         * @since 1.0.0
         */
        public function maybe_show_folder_module_notice()
        {
            $result = $this->jltwp_adminify_check_folder_module_conflict();
            if(! $result){
                return;
            }
            $this->jltwp_force_disable_module('folders');
            // Check if the notice has already been dismissed.
            $plugin_exists = get_option('_wpadminify_plugin_conflict');
            if(!empty($plugin_exists)){
                if(in_array( $result['Name'], $plugin_exists)){
                    // $this->jltwp_force_disable_module('folders');
                    return;
                }
            }

            ?>
            <div class="notice conflict-notice-wp-adminify is-dismissible notice-conflict-plugins notice-<?php echo esc_attr( $this->color ); ?> wp-adminify-notice-<?php echo esc_attr( $this->get_id() ); ?>">
                <button type="button" class="notice-dismiss wp-adminify-notice-dismiss" data-plugin-name="<?php echo esc_html($result['Name']);?>"></button>
                <?php echo sprintf( wp_kses_post('<p>You are using <strong>%s</strong> plugin, which serve the same purpose as our folder module.</p><p>We have Disabled our <strong>Folder</strong> module, to avoid conflicts.</p>' ), esc_html($result['Name'])); ?>
            </div>
            <?php


            // if (is_admin()) {
                // add_action('admin_footer', array($this, 'enqueue_conflict_plugin_admin_scripts'), 99999);
                // add_action('wp_ajax_jltwp_adminify_module_conflicts', array($this, 'jltwp_adminify_module_conflicts'));
            // }
        }

        /**
         * Enqueue JavaScript for conflict plugins notice.
         *
         * Enqueue JavaScript that handles the conflict notice dismissal.
         */
        public function enqueue_conflict_plugin_admin_scripts() { 
            if( ! $this->jltwp_adminify_check_folder_module_conflict())  return;
            
            ?>
                <script>

                    function jltwp_adminify_notice_action(evt, $this, action_type) {
                        if (evt) evt.preventDefault();
                        $this.closest('.conflict-notice-wp-adminify').slideUp(200);

                        jQuery.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                            action: 'jltwp_adminify_module_conflicts',
                            _wpnonce: '<?php echo esc_js( wp_create_nonce( 'dismiss_notice_nonce' ) ); ?>',
                            action_type: action_type,
                            plugin_name: $this.data('pluginName')
                        });
                    }

                    // Notice Dismiss
                    jQuery('body').on('click', '.conflict-notice-wp-adminify .wp-adminify-notice-dismiss', function(evt) {
                        jltwp_adminify_notice_action(evt, jQuery(this), 'dismiss');
                    });
                </script>

            <?php
        }


        /**
         * Gets the available intervals.
         *
         * @since 1.0.0
         * @access public
         *
         * @return array An array of available intervals.
         */
        public function intervals()
		{
			return array(0);
		}

    }
}