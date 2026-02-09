<?php
namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @module Tweaks
 * @package WP Adminify
 *
 * @author WP Adminify <support@wpadminify.com>
 */

class Users_Security extends AdminSettings{

    public function __construct()
    {
        // add_filter('authenticate', [$limit_login_attempts, 'maybe_allow_login'], 999, 3); // Very low priority so it is processed last
        // add_action('wp_login_errors', [$limit_login_attempts, 'login_error_handler'], 999, 2);
        // add_action('login_enqueue_scripts', [$limit_login_attempts, 'maybe_hide_login_form']);
        // add_filter('login_message', [$limit_login_attempts, 'add_failed_login_message']);
        // add_action('wp_login_failed', [$limit_login_attempts, 'log_failed_login'], 5); // Higher priority than one in Change Login URL
        // add_action('wp_login', [$limit_login_attempts, 'clear_failed_login_log']);

        //Track Login Attempts
        add_action('wp_login_failed', [$this, 'track_login_attempts'], 10, 2);

        // failed login message
        add_filter('login_message', [$this, 'failed_login_message']);

        //Reset Attempts on Successful Login
        add_action('wp_login', [$this, 'reset_login_attempts'], 10, 2);

        // Prevent Lockout for Admins
        add_filter('authenticate', [$this, 'bypass_limit_for_admin'], 9999, 3);

        // Display Remaining Attempts
        add_action('login_form', [$this, 'show_remaining_attempts']);

        // Change user ID from 3 to 10
        // change_user_id(3, 10);

        // Change Username
        // add_action('plugins_loaded', [$this, 'adminify_change_username']);
        add_action('admin_enqueue_scripts', [$this, 'adminify_change_username_enqueue_scripts']);
        add_action('wp_ajax_adminify_change_username', [$this, 'adminify_change_username']);
    }



    public function login_error_handler($errors, $redirect_to)
    {
        if (is_wp_error($errors)) {

            $error_codes = $errors->get_error_codes();

            foreach ($error_codes as $error_code) {

                if ($error_code == 'invalid_username' || $error_code == 'incorrect_password') {

                    // Remove default error messages that may give out valueable info to hackers

                    $errors->remove('invalid_username'); // Outputs info that says username does not exist. May encourage login attempt with a different username instead.

                    $errors->remove('incorrect_password'); // Outputs info that implies username exist. May encourage login attempt with a different password.

                    // Add a new error message that does not provide useful clues to hackers
                    $errors->add('invalid_username_or_incorrect_password', '<b>' . __('Error:', 'adminify') . '</b> ' . __('Invalid username/email or incorrect password.', 'adminify'));

                    // $errors->add( 'another_error_code', 'The error message.' );

                }
            }
        }

        return $errors;
    }


    function failed_login_message($message ){

        if (isset($_REQUEST['failed_login']) && $_REQUEST['failed_login'] == 'true') {

            // if (!is_null($limit_login) && isset($limit_login['within_lockout_period']) && !$limit_login['within_lockout_period']) {

                $message = '<div id="login_error" class="notice notice-error"><b>' . __('Error:', 'adminify') . '</b> ' . __('Invalid username/email or incorrect password.', 'adminify') . '</div>';
            // }
        }

        return $message;
    }

    function show_remaining_attempts() {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $failed_attempts = get_transient($user_ip);

        if ($failed_attempts === false) {
            $failed_attempts = 0;
        }

        $remaining_attempts = 3 - $failed_attempts;

        if ($remaining_attempts > 0) {
            echo '<p>You have ' . $remaining_attempts . ' login attempts remaining.</p>';
        } else {
            echo '<p>You have been temporarily locked out due to too many failed login attempts. Please try again later.</p>';
        }
    }


    function bypass_limit_for_admin($user, $username, $password) {
        if (is_a($user, 'WP_User') && $user->has_cap('manage_options')) {
            $user_ip = $_SERVER['REMOTE_ADDR'];
            delete_transient($user_ip);
        }
        return $user;
    }


    function reset_login_attempts($user_login, $user) {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        delete_transient($user_ip);
    }



    function track_login_attempts($user, $username) {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $failed_attempts = get_transient($user_ip);

        if ($failed_attempts === false) {
            $failed_attempts = 0;
        }

        $failed_attempts++;
        set_transient($user_ip, $failed_attempts, 60 * 5); // 5 minutes lockout

        if ($failed_attempts >= 3) {
            wp_die('Too many failed login attempts. Please try again later.');
        }
    }


    public function adminify_change_username_enqueue_scripts()
    {

        global $pagenow;

        if (!in_array($pagenow, array('profile.php', 'user-edit.php'))) {
            return;
        }

        if (!current_user_can('edit_users')) {
            return;
        }

        wp_enqueue_script('adminify-username-change', WP_ADMINIFY_ASSETS . 'admin/js/change-username' .  Utils::assets_ext('.js'), array(), WP_ADMINIFY_VER, true);
        wp_localize_script('adminify-username-change', 'adminify_change_username', array(
            'nonce' => wp_create_nonce('adminify_change_username'),
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
    }

    public function adminify_change_username()
    {
        $response = array(
            'success'   => false,
            'new_nonce' => wp_create_nonce('adminify_change_username')
        );

        // Check capabilities
        if (!current_user_can('edit_users')) {
            $response['message'] = 'You do not have the required capability to do that.';
            wp_send_json($response);
        }

        // Validate nonce
        check_ajax_referer('adminify_change_username');

        // Validate request
        if (empty($_POST['new_username']) || empty($_POST['current_username'])) {
            $response['message'] = 'Invalid request.';
            wp_send_json($response);
        }

        // Validate new username
        $new_username = trim(strip_tags($_POST['new_username']));
        if (!validate_username($new_username)) {
            $response['message'] = __('This username is invalid because it uses illegal characters. Please enter a valid username.');
            wp_send_json($response);
        }

        // Check if username is not in list of illegal logins
        $illegal_user_logins = array_map('strtolower', (array) apply_filters('illegal_user_logins', array()));
        if (in_array(strtolower($new_username), $illegal_user_logins)) {
            $response['message'] = __('Sorry, that username is not allowed.');
            wp_send_json($response);
        }

        // Check if new username is already in use
        if (username_exists($new_username)) {
            $response['message'] = sprintf('<strong>%s</strong> is already in use.', $new_username);
            wp_send_json($response);
        }

        // Change the username
        $old_username = trim(strip_tags($_POST['current_username']));
        $this->change_old_new_username($old_username, $new_username);

        // Success response
        $response['success'] = true;
        $response['message'] = sprintf('Username successfully changed to <strong>%s</strong>.', $new_username);
        wp_send_json($response);
    }

    function change_old_new_username($old_username, $new_username)
    {
        global $wpdb;

        // do nothing if old username does not exist.
        $user_id = username_exists($old_username);
        if (!$user_id) {
            return false;
        }

        // change username
        $q  = $wpdb->prepare("UPDATE $wpdb->users SET user_login = %s WHERE user_login = %s", $new_username, $old_username);
        $wpdb->query($q);

        // change nicename if needed
        $q = $wpdb->prepare("UPDATE $wpdb->users SET user_nicename = %s WHERE user_login = %s AND user_nicename = %s", $new_username, $new_username, $old_username);
        $wpdb->query($q);

        // change display name if needed
        $q  = $wpdb->prepare("UPDATE $wpdb->users SET display_name = %s WHERE user_login = %s AND display_name = %s", $new_username, $new_username, $old_username);
        $wpdb->query($q);

        // when on multisite, check if old username is in the `site_admins` options array. if so, replace with new username to retain superadmin rights.
        if (is_multisite()) {
            $super_admins = (array) get_site_option('site_admins', array('admin'));
            $array_key = array_search($old_username, $super_admins);
            if ($array_key) {
                $super_admins[$array_key] = $new_username;
            }

            update_site_option('site_admins', $super_admins);
        }

        /**
         * Fires right after a username is changed.
         *
         * @param string $old_username
         * @param string $new_username
         */
        do_action('change_username.username_changed', $old_username, $new_username);

        return true;
    }

    function change_user_id($old_user_id, $new_user_id)
    {
        global $wpdb;

        // Update user ID in users table
        $wpdb->update(
            $wpdb->users,
            array('ID' => $new_user_id),
            array('ID' => $old_user_id)
        );

        // Update user ID in usermeta table
        $wpdb->update(
            $wpdb->usermeta,
            array('user_id' => $new_user_id),
            array('user_id' => $old_user_id)
        );

        // Update user ID in posts table
        $wpdb->update(
            $wpdb->posts,
            array('post_author' => $new_user_id),
            array('post_author' => $old_user_id)
        );

        // Update user ID in comments table
        $wpdb->update(
            $wpdb->comments,
            array('user_id' => $new_user_id),
            array('user_id' => $old_user_id)
        );

        // Add any other tables that may reference user IDs if necessary

        // Reassign capabilities
        $capabilities = get_user_meta($old_user_id, $wpdb->prefix . 'capabilities', true);
        if ($capabilities) {
            add_user_meta($new_user_id, $wpdb->prefix . 'capabilities', $capabilities);
            delete_user_meta($old_user_id, $wpdb->prefix . 'capabilities');
        }

        // Delete the old user
        wp_delete_user($old_user_id);
    }

}
