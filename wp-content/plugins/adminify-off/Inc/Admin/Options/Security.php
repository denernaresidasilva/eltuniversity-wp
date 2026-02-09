<?php

namespace WPAdminify\Inc\Admin\Options;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettingsModel;


if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

if ( ! class_exists( 'Security' ) ) {
	class Security extends AdminSettingsModel {

		public $defaults = [];

		public function __construct() {
			$this->tweak_settings();
		}

		protected function get_defaults() {
			return [
				'security_head'		=>	[
					'enable_security_head' => false,
					'security_head_data'   => [
						'generator_wp_version'
					],
					'self_ping_sites' => ''
				],
				'security_rest_api' => [
					'security_rest_api_enable' => false,
					'security_rest_api_data'   => []
				],
				'disable_comments'  => [
					'enable_disable_comments' => false,
					'post_types'              => [],
					'apply_for'               => [],
				],
				'custom_gravatar'		=> [
					'enable',
					'image'       => [
						[
							'avatar_image' => '',
							'avatar_name'  => 'Avatar Name',
						],
					],
				],
				'post_archives'        => [
					'post_archives_enable' => false,
					'post_archives_data'   => [],
				],
				'security_feed'        => false,
				'users_security'       => [
					'limit_logins'    => false,
					'change_username' => false,
				],
				'disable_automatic_emails'        => false,
				'disable_language_switcher_login' => false,

				// Redirect URLs
				'redirect_urls_fields'	=> [
					'enable_redirect_urls' => false,
					'redirect_urls_options' => [
						'redirect_urls_tabs' => [
							'new_login_url'        => '',
							'redirect_admin_url'   => '',
							'new_register_url'     => '',
							'new_logout_url'       => '',
							'login_redirects'      => [
								'user_types' 		=> 'user_role',
								'redirect_user' 	=> 'user_role',
								'redirect_role' 	=> '',
								'redirect_cap' 		=> '',
								'redirect_url' 		=> '',
								// 'redirect_order' 	=> 10,
							],
							'logout_redirects'	 => [
								'user_types' 		=> 'user_role',
								'redirect_user' 	=> 'user_role',
								'redirect_role' 	=> '',
								'redirect_cap' 		=> '',
								'redirect_url' 		=> '',
								// 'redirect_order' 	=> 10,
							]
						],
					],
				]
			];
		}


		  /**
			* Security: Head Fields
		 *
		 * @return void
		 */
		public function security_head_fields( &$head_fields ) {
			$security_head = [
				'xmlrpc'               => __( 'Disable XML-RPC', 'adminify' ),
				'generator_wp_version' => __( 'Remove WordPress Generator Version from front end', 'adminify' ),
				'rsd'                  => __( 'Remove "<link rel=\'EditURI\'..." from head section', 'adminify' ),
				'shortlink'            => __( 'Remove "<link rel=\'shortlink\'..." from head section', 'adminify' ),
				'canonical'            => __( 'Remove &lt;link rel="canonical" href="https://www.site.com/some-url" /&gt; from head section', 'adminify' ),
				'self_ping'            => __('Disable self-ping, i.e., from your site to your site when writing posts.', 'adminify'),
			];

			$third_party_security_head = [];
			if ( Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				$third_party_security_head['wc_generator'] = __( 'Remove WooCommerce Generator Meta', 'adminify' );
			}

			if ( Utils::is_plugin_active( 'revslider/revslider.php' ) ) {
				$third_party_security_head['revslider_generator'] = __( 'Remove Revolution Slider Generator Meta', 'adminify' );
			}

			if ( Utils::is_plugin_active( 'js_composer/js_composer.php' ) ) {
				$third_party_security_head['js_composer_generator'] = __( 'Remove Visual Composer Generator Meta', 'adminify' );
			}

			if ( Utils::is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
				$third_party_security_head['wpml_generator'] = __( 'Remove WPML Generator Meta', 'adminify' );
			}

			if ( Utils::is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
				$third_party_security_head['yoast_generator'] = __( 'Remove All Yoast HTML Comments', 'adminify' );
			}

			$security_head = array_merge($security_head, $third_party_security_head);


			$head_fields[] = [
				'id'       => 'security_head',
				'title'    => __('Header Security', 'adminify'),
				'subtitle' => __('WordPress Frontend Head Tags Security ', 'adminify'),
				'type'     => 'fieldset',
				'fields'   => [
					[
						'id'         => 'enable_security_head',
						'type'       => 'switcher',
						'title'      => __('', 'adminify'),
						'class'      => 'adminify-pl-0 adminify-pt-0',
						'text_on'    => __('Show', 'adminify'),
						'text_off'   => __('Hide', 'adminify'),
						'text_width' => 80,
						'default'    => $this->get_default_field('security_head')['enable_security_head'],
					],
					[
						'id'         => 'security_head_data',
						'type'       => 'checkbox',
						'class'      => 'adminify-one-col',
						'title'      => __('', 'adminify'),
						'options'    => $security_head,
						'default'    => $this->get_default_field('security_head')['security_head_data'],
						'dependency' => ['enable_security_head', '==', 'true', 'true']
					],
					[
						'id'          => 'self_ping_sites',
						'type'        => 'textarea',
						'subtitle'    => __('Additional URLs to disable Self Ping', 'adminify'),
						'placeholder' => __('Each URLs in new line', 'adminify'),
						'title'       => 'Additional Self-Ping URLs',
						'default'     => $this->get_default_field('self_ping_sites'),
						'dependency'  => ['security_head_data|enable_security_head', 'any|==', 'self_ping|true', 'true'],
					]
				]
			];

		}


		  /**
			* Security: Feed Fields
		 *
		 * @param [type] $security_feed
		 *
		 * @return void
		 */
		public function security_feed_fields( &$security_feed  )  {
			$security_feed[] = [
				'id'         => 'security_feed',
				'type'       => 'switcher',
				'title'      => __('Feed Links', 'adminify'),
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'subtitle'   => __('Disable all RSS, Atom, and RDF feeds, including posts, categories, tags, comments, authors, and search. Also redirect all feed URLs.', 'adminify'),
				'default'    => $this->get_default_field('security_feed'),
            ];
		}

		  /**
		 * Change Username Module
		 */
		public function security_redirect_urls(&$redirect_urls){
			$settings_fields     = [];
			$login_redirects = [];
			// $logout_redirects = [];
			$this->login_register_url_fields($settings_fields);
			$this->roles_redirect_tabs($login_redirects);

			$redirect_urls = [
				[
					'id'       => 'redirect_urls_fields',
					'title'    => sprintf(__('Redirect URLs %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
					'class'		 => 'adminify-pro-fieldset',
					'subtitle'   => __('Login and logout redirects based on user roles. Customize URLs for administrators, editors, or subscribers etc.', 'adminify'),
					'type'     => 'fieldset',
					'fields'   => [
						[
							'id'         => 'enable_redirect_urls',
							'type'       => 'switcher',
							'class'		=> 'adminify-pl-0',
							'text_on'    => __('Show', 'adminify'),
							'text_off'   => __('Hide', 'adminify'),
							'text_width' => 80,
							'default'    => $this->get_default_field('redirect_urls_fields')['enable_redirect_urls'],
						],
						[
							'id'         => 'redirect_urls_options',
							'type'       => 'fieldset',
							'title'      => __(' ', 'adminify'),
							'class'      => 'adminify-pt-0 adminify-pl-0 adminify-one-col adminify-tabs-content adminify-pro-notice',
							'fields'     => [
								[
									'id'       => 'redirect_urls_tabs',
									'type'     => 'tabbed',
									'title'    => '',
									'tabs'     => [
										[
											'title'  => __('Login/Register URL', 'adminify'),
											'fields' => $settings_fields,
										],
										[
											'title'  => __('Roles Redirect', 'adminify'),
											'fields' => $login_redirects,
										],
									],
								]
							],
							'default'    => $this->get_default_field('redirect_urls_fields')['redirect_urls_options'],
							'dependency' => ['enable_redirect_urls', '==', 'true', true],
						]
					]
				]
			];
		}



		public function login_register_url_fields(&$settings_fields)
		{
			$settings_fields[] = [
				'id'          => 'new_login_url',
				'type'        => 'text',
				'class'       => 'new-login-url',
				'title'       => __('New Login URL', 'adminify'),
				'desc'        => __('Change the login URL and prevent access to the wp-admin and <code>' . wp_login_url() . '</code> page directly.', 'adminify'),
				'placeholder' => 'login',
				'before'      => \get_site_url() . '/',
				// 'after'       => '/',
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['new_login_url'],
			];

			$settings_fields[] = [
				'id'          => 'redirect_admin_url',
				'type'        => 'text',
				'class'       => 'new-login-url redirect-admin-url',
				'title'       => __('Redirect Admin', 'adminify'),
				'desc'        => __('Redirect users those are not logged in and trying to access <code>' . get_admin_url() . '</code>', 'adminify'),
				'placeholder' => '404',
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['redirect_admin_url'],
				'before'      => \get_site_url() . '/',
				// 'after'       => '/',
			];

			$settings_fields[] = [
				'id'          => 'new_register_url',
				'type'        => 'text',
				'class'       => 'new-login-url new-register-url',
				'title'       => __('New Register URL', 'adminify'),
				'subtitle'    => __('Enable <a href="' . admin_url('options-general.php#users_can_register') . '"><b>Membership: "Anyone can register"</b></a> checkbox from Settings.', 'adminify'),
				'desc'        => __('Change the Register URL, to setup the custom designed registration page.', 'adminify'),
				'placeholder' => 'wp-login.php?action=register',
				'before'      => \get_site_url() . '/',
				// 'after'       => '/',
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['new_register_url'],
			];
		}

		/**
		 * Settings Fields
		 *
		 * @return void
		 */
		public function roles_redirect_tabs(&$roles_redirect)
		{
			$login_redirect_fields = [];
			$logout_redirect_fields = [];
			$this->login_redirect_tab_fields($login_redirect_fields);
			$this->logout_redirect_tab_fields($logout_redirect_fields);

			$roles_redirect[] = [
				'id'      => 'roles_redirect_tabs',
				'type'    => 'button_set',
				'title'   => __('Redirect Rules Type', 'adminify'),
				'options' => array(
					'login'  => __('Login Redirect ', 'adminify'),
					'logout' => __('Logout Redirect ', 'adminify'),
				),
				'default'      => 'login',
			];

			// Heading
			$roles_redirect[] = [
				'type'       => 'submessage',
				'style'      => 'info',
				'class'      => 'adminify-one-col',
				'content'    => __('Add Login conditions to redirect users to different pages based on their user names, roles & capabilities', 'adminify'),
				'dependency' => ['roles_redirect_tabs', '==', 'login'],
			];
			$roles_redirect[] = [
				'id'                     => 'login_redirects',
				'type'                   => 'group',
				'title'                  => '',
				'accordion_title_prefix' => __('Login Redirect: ', 'adminify'),
				'accordion_title_number' => true,
				'accordion_title_auto'   => true,
				'button_title'           => __('Add New Login Redirect', 'adminify'),
				'fields'                 => $login_redirect_fields,
				'dependency'             => ['roles_redirect_tabs', '==', 'login'],
			];



			// Logout Heading
			$roles_redirect[] = [
				'type'       => 'submessage',
				'style'      => 'info',
				'class'      => 'adminify-one-col',
				'content'    => __('Add Logout conditions to redirect users to different pages based on their user names, roles & capabilities', 'adminify'),
				'dependency' => ['roles_redirect_tabs', '==', 'logout'],
			];
			$roles_redirect[] = [
				'id'                     => 'logout_redirects',
				'type'                   => 'group',
				'title'                  => '',
				'accordion_title_prefix' => __('Logout Redirect: ', 'adminify'),
				'accordion_title_number' => true,
				'accordion_title_auto'   => true,
				'button_title'           => __('Add New Logout Redirect', 'adminify'),
				'fields'                 => $logout_redirect_fields,
				'dependency'  => ['roles_redirect_tabs', '==', 'logout'],
			];
		}


		/**
		 * Login Redirect Fields
		 *
		 * @param [type] $login_redirect_fields
		 *
		 * @return void
		 */
		public function login_redirect_tab_fields(&$login_redirect_fields)
		{
			$login_redirect_fields[] = [
				'id'          => 'user_types',
				'type'        => 'button_set',
				'title'       => __('User Types', 'adminify'),
				'placeholder' => __('Select User Types', 'adminify'),
				'options'     => array(
					'user_role'  => __('User Role', 'adminify'),
					'user_name'  => __('User Name', 'adminify'),
					'user_cap'  => __('User Capability', 'adminify'),
				),
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['login_redirects']['user_types'],
			];

			// Select User Names
			$login_redirect_fields[] = [
				'id'          => 'redirect_user',
				'type'        => 'select',
				'title'       => __('User', 'adminify'),
				'placeholder' => __('Select a user', 'adminify'),
				'options'     => 'users',
				'dependency'  => ['user_types', '==', 'user_name'],
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['login_redirects']['redirect_user'],
			];

			// Select User Roles
			$login_redirect_fields[] = [
				'id'          => 'redirect_role',
				'type'        => 'select',
				'title'       => __('Role', 'adminify'),
				'placeholder' => __('Select a Role', 'adminify'),
				'options'     => 'roles',
				'dependency'  => ['user_types', '==', 'user_role'],
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['login_redirects']['redirect_role'],
			];

			// Select User Capability
			$login_redirect_fields[] = [
				'id'          => 'redirect_cap',
				'type'        => 'select',
				'title'       => __('Capability', 'adminify'),
				'placeholder' => __('Select a Capability', 'adminify'),
				'options'     => '\WPAdminify\Inc\Classes\Helper::get_capability_options',
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['login_redirects']['redirect_cap'],
				'dependency'  => ['user_types', '==', 'user_cap'],
			];


			// Redirect URL
			$login_redirect_fields[] = [
				'id'          => 'redirect_url',
				'type'        => 'text',
				'class'       => 'new-login-url',
				'title'       => __('Redirect URL', 'adminify'),
				// 'before'      => \get_site_url() . '/',
				'desc'        => __('Note: Full URL here. Change the URL for a User or User Roles.', 'adminify'),
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['login_redirects']['redirect_url'],
			];

			// // Redirect Order
			// $login_redirect_fields[] = [
			// 	'id'          => 'redirect_order',
			// 	'type'    	  => 'number',
			// 	'title'   	  => __('Order', 'adminify'),
			// 	'default'     => $this->get_default_field('redirect_urls_options')['login_redirects']['redirect_order'],
			// ];
		}




		/**
		 * Logout Settings Fields
		 *
		 * @return void
		 */
		public function logout_redirect_tab_fields(&$logout_redirect_fields)
		{
			$logout_redirect_fields[] = [
				'id'          => 'user_types',
				'type'        => 'button_set',
				'title'       => __('User Types', 'adminify'),
				'placeholder' => __('Select User Types', 'adminify'),
				'options'     => array(
					'user_role'  => __('User Role', 'adminify'),
					'user_name'  => __('User Name', 'adminify'),
					'user_cap'  => __('User Capability', 'adminify'),
				),
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['logout_redirects']['user_types'],
			];

			// Select User Names
			$logout_redirect_fields[] = [
				'id'          => 'redirect_user',
				'type'        => 'select',
				'title'       => __('User', 'adminify'),
				'placeholder' => __('Select a user', 'adminify'),
				'options'     => 'users',
				'dependency'  => ['user_types', '==', 'user_name'],
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['logout_redirects']['redirect_user'],
			];

			// Select User Roles
			$logout_redirect_fields[] = [
				'id'          => 'redirect_role',
				'type'        => 'select',
				'title'       => __('Role', 'adminify'),
				'placeholder' => __('Select a Role', 'adminify'),
				'options'     => 'roles',
				'dependency'  => ['user_types', '==', 'user_role'],
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['logout_redirects']['redirect_role'],
			];

			// Select User Capability
			$logout_redirect_fields[] = [
				'id'          => 'redirect_cap',
				'type'        => 'select',
				'title'       => __('Capability', 'adminify'),
				'placeholder' => __('Select a Capability', 'adminify'),
				'options'     => '\WPAdminify\Inc\Classes\Helper::get_capability_options',
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['logout_redirects']['redirect_cap'],
				'dependency'  => ['user_types', '==', 'user_cap'],
			];


			// Redirect URL
			$logout_redirect_fields[] = [
				'id'          => 'redirect_url',
				'type'        => 'text',
				'class'       => 'new-login-url',
				'title'       => __('Redirect URL', 'adminify'),
				'before'      => \get_site_url() . '/',
				'desc'        => __('Change the URL for a User or User Roles.', 'adminify'),
				'default'     => $this->get_default_field('redirect_urls_fields')['redirect_urls_options']['redirect_urls_tabs']['logout_redirects']['redirect_url'],
			];

			// Redirect Order
			// $logout_redirect_fields[] = [
			// 	'id'          => 'redirect_order',
			// 	'type'    	  => 'number',
			// 	'title'   	  => __('Order', 'adminify'),
			// 	'default'     => $this->get_default_field('redirect_urls_options')['logout_redirects']['redirect_order'],
			// ];
		}


		  /**
		 * Change Username Module
		 */
		public function security_user_security(&$users){

			$users_settings[] = [
				'id'         => 'change_username',
				'type'       => 'switcher',
				'text_on'    => __('Show', 'adminify'),
				'text_off'   => __('Hide', 'adminify'),
				'text_width' => 80,
				'title'      => __('Username Change', 'adminify'),
				'subtitle'   => sprintf(__('Remove Comments for Media attachment Template <a href="%s">More Details</a> ', 'adminify'), esc_url('https://wpadminify.com/docs')),
				'default'    => $this->get_default_field('users_security')['change_username'],
			];
			$users_settings[] = [
				'id'         => 'limit_logins',
				'type'       => 'switcher',
				'text_on'    => __('Show', 'adminify'),
				'text_off'   => __('Hide', 'adminify'),
				'text_width' => 80,
				'title'      => __('Limit Login Attempts', 'adminify'),
				'subtitle'   => sprintf(__('Prevent brute force attacks by limiting the number of failed login attempts per IP address. <a href="%s">More Details</a> ', 'adminify'), esc_url('https://wpadminify.com/docs')),
				'default'    => $this->get_default_field('users_security')['limit_logins'],
			];

			$users[] = array(
				'id'       => 'users_security',
				'type'     => 'fieldset',
				'title'    => __('Users Security', 'adminify'),
				'subtitle' => __('Security for Users, Login/Logout etc.', 'adminify'),
				'fields'   => $users_settings,
				'default'  => $this->get_default_field('users_security'),
			);
		}

		  /**
		 * Security: WP JSON API's
		 *
		 * @param  [type] $json_api_fields
		 *
		 * @return void
		 */
		public function security_rest_api_fields( &$json_api_fieldset ) {

			$json_api_fields_data = [
				'rest_api' => __('Disable REST API', 'adminify'),
				'powered'  => __('Remove "X-Powered-By:..." from Server Response HTTP headers', 'adminify'),
			];

			$json_api_fields = [
				[
					'id'         => 'security_rest_api_enable',
					'type'       => 'switcher',
					'class'      => 'adminify-pl-0 adminify-pt-0',
					'title'      => __('', 'adminify'),
					'text_on'    => __('Show', 'adminify'),
					'text_off'   => __('Hide', 'adminify'),
					'text_width' => 80,
					'default'    => $this->get_default_field('security_rest_api')['security_rest_api_enable'],
				],
				[
					'id'         => 'security_rest_api_data',
					'type'       => 'checkbox',
					'title'      => __('', 'adminify'),
					'class'      => 'adminify-one-col',
					'options'    => $json_api_fields_data,
					'default'    => $this->get_default_field('security_rest_api')['security_rest_api_data'],
					'dependency' => ['security_rest_api_enable', '==', 'true', true],
				]
			];

			$json_api_fieldset[] = array(
				'id'       => 'security_rest_api',
				'type'     => 'fieldset',
				'title'    => __('REST API', 'adminify'),
				'subtitle' => __('Disable REST API access for non-authenticated users and remove URL traces from &lt;head&gt;, HTTP headers and WP RSD endpoint.', 'adminify'),
				'fields'   => $json_api_fields,
				'default'  => $this->get_default_field('security_rest_api'),
			);

		}


		  /**
			* Security: Post & Archives
		 */
		public function security_archive_fields( &$archive_fields ) {
			$archive_fields_data = [
				'last_modified_date'  => __('Display Last Post Updated Date', 'adminify' ),
				'capital_p_dangit'    => __('Remove Capital "P" Dangit', 'adminify' ),
				'archives_date'       => __('Redirect "Date Archives" Template to Homepage', 'adminify' ),
				'archives_author'     => __('Redirect "Author Archives" Template to Homepage', 'adminify' ),
				'archives_tag'        => __('Redirect "Tag Archives" Template  to Homepage', 'adminify' ),
				'archives_category'   => __('Redirect "Category Archives" Template to Homepage', 'adminify' ),
				'archives_postformat' => __('Redirect "Post Format Archives" Template to Homepage', 'adminify' ),
				'archives_search'     => __('Redirect "Search Template" to Homepage', 'adminify' ),
			];

			$archive_fields[] = [
				'id'       => 'post_archives',
				'title'    => __('Post & Archives', 'adminify'),
				'subtitle' => __('Redirect unused Archives pages to homepage', 'adminify'),
				'type'     => 'fieldset',
				'fields'   => [
					[
						'id'         => 'post_archives_enable',
						'type'       => 'switcher',
						'title'      => __('', 'adminify'),
						'text_on'    => __('Show', 'adminify'),
						'text_off'   => __('Hide', 'adminify'),
						'text_width' => 80,
						'class'      => 'adminify-pl-0 adminify-pt-0',
						'default'    => $this->get_default_field('post_archives')['post_archives_enable'],
					],
					[
						'id'         => 'post_archives_data',
						'type'       => 'checkbox',
						'class'      => 'adminify-one-col',
						'title'      => __('', 'adminify'),
						'options'    => $archive_fields_data,
						'default'    => $this->get_default_field('post_archives')['post_archives_data'],
						'dependency' => ['post_archives_enable', '==', 'true', true],
					],
				]
			];

		}


		  /**
		 * Custom Gravatar Image
		 *
		 * @param [type] $fields
		 *
		 * @return void
		 */
		public function security_custom_gravatar( &$gravatar_image ){

			  // Comments Avatar
			$custom_gravatar_settings = [
				[
					'id'         => 'enable',
					'type'       => 'switcher',
					'title'      => '',
					'class'		 => 'adminify-pt-0 adminify-pl-0 adminify-col-fit',
					'label'      => sprintf(__('Add your Custom Gravatar Images for Comments. Select Avatar Image from  <a href="%s">Settings>Discussion>Default Avatar</a>.', 'adminify'), esc_url(admin_url('options-discussion.php'))),
					'text_on'    => __('Yes', 'adminify'),
					'text_off'   => __('No', 'adminify'),
					'text_width' => 80,
					'default'    => $this->get_default_field('enable'),
				],
				[
					'id'     => 'image',
					'type'   => 'repeater',
					'title'  => __('Add Avatar Image', 'adminify'),
					'fields' => [
						[
							'id'      => 'avatar_image',
							'type'    => 'media',
							'title'   => __('Image', 'adminify'),
							'library' => 'image',
						],
						[
							'id'    => 'avatar_name',
							'type'  => 'text',
							'title' => __('Name', 'adminify'),
						],
					],
					'default'    => $this->get_default_field('image'),
					'dependency' => ['enable', '==', 'true'],
				]
			];

			$gravatar_image[] = [
				'id'     => 'custom_gravatar',
				'type'   => 'fieldset',
				'title'  => __('Custom Gravatar Images', 'adminify'),
				'fields' => $custom_gravatar_settings,
			];
		}


		  /**
		 * Disable Comments
		 *
		 * @param [type] $fields
		 *
		 * @return void
		 */
		public function security_others( &$fields ){
			$fields[] = [
				'id'         => 'disable_automatic_emails',
				'type'       => 'switcher',
				'class'		=> 'adminify-pro-fieldset adminify-pro-notice',
				'title'      => sprintf(__('Disable Automatic Updates Emails %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'   => __('Stop getting emails about automatic updates on your WordPress site.', 'adminify'),
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('disable_automatic_emails'),
			];

			$fields[] = [
				'id'         => 'disable_language_switcher_login',
				'type'       => 'switcher',
				'class'		=> 'adminify-pro-fieldset adminify-pro-notice',
				'title'      => sprintf(__('Disable Login Screen Language Switcher %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'   => __('Hide the Language Switcher on the default WordPress login screen.', 'adminify'),
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('disable_language_switcher_login'),
			];
		}

		  /**
		 * Disable Comments
		 *
		 * @param [type] $fields
		 *
		 * @return void
		 */
		public function security_disable_comments( &$fields ){

			  // Disable Comments
			$disable_comments_for = [
				'admin_bar'            => __('Remove "Comments" from Admin Bar', 'adminify'),
				'menu_redirect'        => __('Admin Menu "Comments" Redirect to "wp-admin"', 'adminify'),
				'admin_menu'           => __('Remove Admin Menu "Comments"', 'adminify'),
				'discussion_menu'      => __('Remove Discussion Menu from "Settings>Discussion" Sub Menu', 'adminify'),
				'close_front'          => __('Close Comments from front-end', 'adminify'),
				'comments_notes'       => __('Remove "Your email address will not be published..." from comment form template', 'adminify'),
				'comments_url_field'   => __('Remove website Field (URL)  from comment form template', 'adminify'),
				'replace_author_link'  => sprintf(__('Remove Link from comment "Author Name" & replace to JavaScript? %s'), Utils::adminify_upgrade_pro_class()),
				'replace_comment_link' => sprintf(__('Comments Content disable auto linking, display comments links as plain text, replace Comment Links to JavaScript? %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'hide_existing'        => sprintf(__('Hide Existing Comments from Frontend? %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
			];

			// check if WP_Widget_Recent_Comments is used
			// $recentcomments = [];
			if ( !is_active_widget(false, false, 'WP_Widget_Recent_Comments', true) ) {
				$disable_comments_for['recentcomments'] = __('Remove Hardcoded ".recentcomments" CSS for default "Recent Comments" widget. i.e ', 'adminify');
				$disable_comments_for = array_merge($disable_comments_for, $disable_comments_for);
			}

			$fields[] = [
				'id'       => 'disable_comments',
				'title'    => __('Disable Comments', 'adminify'),
				'subtitle' => __('Disable comments for some or all public post types. When disabled, existing comments will also be hidden on the frontend.', 'adminify'),
				'type'     => 'fieldset',
				'fields'   => [
					[
						'id'         => 'enable_disable_comments',
						'type'       => 'switcher',
						'title'      => __('', 'adminify'),
						'class'      => 'adminify-pl-0 adminify-pt-0',
						'text_on'    => __('Show', 'adminify'),
						'text_off'   => __('Hide', 'adminify'),
						'text_width' => 80,
						'default'    => $this->get_default_field('disable_comments')['enable_disable_comments'],
					],
					[
						'id'         => 'post_types',
						'type'       => 'checkbox',
						'title'      => __('for Post Types', 'adminify'),
						'subtitle'   => __('Check for enable Post Types', 'adminify'),
						'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_post_types',
						'default'    => $this->get_default_field('disable_comments')['post_types'],
						'dependency' => ['enable_disable_comments', '==', 'true', true],
					],
					// [
					// 	'id'         => 'disable_comments_post_types',
					// 	'type'       => 'notice',
					// 	'style'      => 'warning',
					// 	'content'    => Utils::adminify_upgrade_pro(),
					// 	'dependency' => [
					// 		[ 'post_types', 'not-any', 'post,page', 'true' ],
					// 		[ 'post_types', '!=', '', 'true' ],
					// 	],
					// ],
					[
						'id'         => 'apply_for',
						'type'       => 'checkbox',
						'title'      => __('', 'adminify'),
						'class'      => 'adminify-one-col',
						'options'    => $disable_comments_for,
						'default'    => $this->get_default_field('disable_comments')['apply_for'],
						'dependency' => ['enable_disable_comments', '==', 'true', true],
					]
				]
			];
		}


		public function tweak_settings() {
			if ( ! class_exists( 'ADMINIFY' ) ) {
				return;
			}


			$fields = [];

			$fields[] = [
				'id'      => 'security_subheading',
				'type'    => 'subheading',
				'content' => Utils::adminfiy_help_urls(
					__('"WordPress" White Label Settings', 'adminify'),
					'https://wpadminify.com/docs/security/',
					'',
					'https://www.facebook.com/groups/jeweltheme',
					\WPAdminify\Inc\Admin\AdminSettings::support_url()
				)
			];

			$this->security_redirect_urls( $fields );
			  // $this->security_user_security( $fields );
			$this->security_head_fields( $fields );
            $this->security_feed_fields( $fields );
			$this->security_rest_api_fields( $fields );
			$this->security_disable_comments( $fields );
			$this->security_archive_fields( $fields );
			// $this->security_others( $fields );

			$this->security_custom_gravatar( $fields );

			$fields = apply_filters('adminify_settings/security', $fields, $this);

			  // Tweaks Section
			\ADMINIFY::createSection(
				$this->prefix,
				[
					'title'  => __( 'Security', 'adminify' ),
					'id'     => 'security',
					'icon'   => 'fas fa-shield-alt',
					'fields' => $fields,
				]
			);
		}
	}
}
