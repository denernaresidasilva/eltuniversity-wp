<?php
/**
 *  Constants
 *
 * @package MagicLogin
 */

namespace MagicLogin\Constants;

const TOKEN_USER_META          = 'magic_login_token';
const SETTING_OPTION           = 'magic_login_settings';
const DB_VERSION_OPTION_NAME   = 'magic_login_db_version';
const LICENSE_KEY_OPTION       = 'magic_login_license_key'; // plugin license key
const CRON_HOOK_NAME           = 'magic_login_cleanup_expired_tokens';
const DISABLE_USER_META        = 'magic_login_disabled';
const PHONE_NUMBER_META        = 'magic_login_phone_number';
const USER_TTL_META            = 'magic_login_user_ttl';
const USER_TOKEN_VALIDITY_META = 'magic_login_user_token_validity';

// urls
const DOCS_URL    = 'https://handyplugins.co/docs-category/magic-login-pro/';
const BLOG_URL    = 'https://handyplugins.co/blog/';
const FAQ_URL     = 'https://handyplugins.co/magic-login-pro/#faq';
const SUPPORT_URL = 'https://wordpress.org/support/plugin/magic-login/';
const GITHUB_URL  = 'https://github.com/HandyPlugins';
const TWITTER_URL = 'https://x.com/HandyPlugins';


// endpoints
const UPDATE_ENDPOINT  = 'https://handyplugins.co/wp-json/paddlepress-api/v1/update';
const LICENSE_ENDPOINT = 'https://handyplugins.co/wp-json/paddlepress-api/v1/license';

// transient keys
const LICENSE_INFO_TRANSIENT = 'magic_login_license_info'; // license info API result
