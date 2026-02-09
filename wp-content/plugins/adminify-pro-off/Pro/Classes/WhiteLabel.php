<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Admin\AdminSettings;

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WhiteLabel extends AdminSettings {

	public $options;

	public function __construct() {

		$this->options = (array) AdminSettings::get_instance()->get();

		// WP Adminify White Label Settings
		add_action( 'all_plugins', [ $this, 'jltwp_adminify_save_white_label_settings_update' ] );


        if(!empty($this->options['white_label']['adminify']['plugin_option'])){
			add_action( 'activated_plugin', [ $this, 'jltwp_adminify_activated_plugin' ], 10 );
		}

		add_action('adminify_options_before', [$this, 'update_the_branding_info']);

	}

	public function update_the_branding_info( $class ) {

		// Logo Options
		if (!empty($this->options['white_label']['adminify']['plugin_logo']['url'])) {
			$light_logo_url = $this->options['white_label']['adminify']['plugin_logo']['url'];
			$light_logo_image_url = WP_ADMINIFY_ASSETS_IMAGE . 'logos/logo-text-light.svg';
			$class->args['framework_title'] = str_replace( $light_logo_image_url, $light_logo_url, $class->args['framework_title']);
		}

		if (!empty($this->options['white_label']['adminify']['plugin_logo_dark']['url'])) {
			$dark_logo_url = $this->options['white_label']['adminify']['plugin_logo_dark']['url'];
			$dark_logo_image_url = WP_ADMINIFY_ASSETS_IMAGE . 'logos/logo-text-dark.svg';
			$class->args['framework_title'] = str_replace( $dark_logo_image_url, $dark_logo_url, $class->args['framework_title']);
		}

		// Author Name
		if (!empty($this->options['white_label']['adminify']['author_name'])) {
			$author_name = $this->options['white_label']['adminify']['author_name'];
			$plugin_author_name = WP_ADMINIFY_AUTHOR;
			$class->args['framework_title'] = str_replace( $plugin_author_name, $author_name, $class->args['framework_title']);
		}

	}



	public function jltwp_adminify_activated_plugin( $plugin ) {

        $activate_wp_adminify_white_label = $this->options['white_label']['adminify'];

		if ( ! empty( $activate_wp_adminify_white_label['plugin_option'] ) ) {
            if ( $plugin == WP_ADMINIFY_BASE ) {
                $activate_wp_adminify_white_label['plugin_option'] = '';
				update_option( '_wpadminify', $activate_wp_adminify_white_label['plugin_option'] );
			}
		}
	}

	/*
	* Update Plugin Settings
	*/
	public function jltwp_adminify_save_white_label_settings_update( $all_plugins ) {
		$adminify_plugins = ['adminify/adminify.php', 'adminify-pro/adminify.php'];

		foreach ($adminify_plugins as $plugin_base_name) {
			if ((array_key_exists($plugin_base_name, $all_plugins)) && ! empty($all_plugins[$plugin_base_name]) && is_array($all_plugins[$plugin_base_name])) {
				if('adminify-pro/adminify.php' == $plugin_base_name){
					$all_plugins[$plugin_base_name]['Name']        = ! empty($this->options['white_label']['adminify']['plugin_name']) ? esc_html($this->options['white_label']['adminify']['plugin_name']) . __(' Premium', 'adminify') : esc_html($all_plugins[$plugin_base_name]['Name']) . __(' Premium', 'adminify');
				} elseif('adminify/adminify.php' == $plugin_base_name){
					$all_plugins[$plugin_base_name]['Name']        = ! empty($this->options['white_label']['adminify']['plugin_name']) ? esc_html($this->options['white_label']['adminify']['plugin_name']) : esc_html($all_plugins[$plugin_base_name]['Name']);
				}
				$all_plugins[$plugin_base_name]['PluginURI']   = ! empty($this->options['white_label']['adminify']['plugin_url']) ? esc_url($this->options['white_label']['adminify']['plugin_url']) : esc_url($all_plugins[$plugin_base_name]['PluginURI']);
				$all_plugins[$plugin_base_name]['Description'] = ! empty($this->options['white_label']['adminify']['plugin_desc']) ? esc_html($this->options['white_label']['adminify']['plugin_desc']) : esc_html($all_plugins[$plugin_base_name]['Description']);
				$all_plugins[$plugin_base_name]['Author']      = ! empty($this->options['white_label']['adminify']['author_name']) ? esc_html($this->options['white_label']['adminify']['author_name']) : esc_html($all_plugins[$plugin_base_name]['Author']);
				$all_plugins[$plugin_base_name]['AuthorURI']   = ! empty($this->options['white_label']['adminify']['plugin_url']) ? esc_url($this->options['white_label']['adminify']['plugin_url']) : esc_url($all_plugins[$plugin_base_name]['AuthorURI']);
				$all_plugins[$plugin_base_name]['Title']       = ! empty($this->options['white_label']['adminify']['plugin_name']) ? esc_html($this->options['white_label']['adminify']['plugin_name']) : esc_html($all_plugins[$plugin_base_name]['Title']);
				$all_plugins[$plugin_base_name]['AuthorName']  = ! empty($this->options['white_label']['adminify']['author_name']) ? esc_html($this->options['white_label']['adminify']['author_name']) : esc_attr($all_plugins[$plugin_base_name]['AuthorName']);
			}
		}
		return $all_plugins;
		// if ( ! empty( $all_plugins[ WP_ADMINIFY_BASE ] ) && is_array( $all_plugins[ WP_ADMINIFY_BASE ] ) ) {
		// 	$all_plugins[ WP_ADMINIFY_BASE ]['Name']        = ! empty( $this->options['white_label']['adminify']['plugin_name'] ) ? esc_html( $this->options['white_label']['adminify']['plugin_name'] ) : esc_html( $all_plugins[ WP_ADMINIFY_BASE ]['Name'] );
		// 	$all_plugins[ WP_ADMINIFY_BASE ]['PluginURI']   = ! empty( $this->options['white_label']['adminify']['plugin_url'] ) ? esc_url( $this->options['white_label']['adminify']['plugin_url'] ) : esc_url( $all_plugins[ WP_ADMINIFY_BASE ]['PluginURI'] );
		// 	$all_plugins[ WP_ADMINIFY_BASE ]['Description'] = ! empty( $this->options['white_label']['adminify']['plugin_desc'] ) ? esc_html( $this->options['white_label']['adminify']['plugin_desc'] ) : esc_html( $all_plugins[ WP_ADMINIFY_BASE ]['Description'] );
		// 	$all_plugins[ WP_ADMINIFY_BASE ]['Author']      = ! empty( $this->options['white_label']['adminify']['author_name'] ) ? esc_html( $this->options['white_label']['adminify']['author_name'] ) : esc_html( $all_plugins[ WP_ADMINIFY_BASE ]['Author'] );
		// 	$all_plugins[ WP_ADMINIFY_BASE ]['AuthorURI']   = ! empty( $this->options['white_label']['adminify']['plugin_url'] ) ? esc_url( $this->options['white_label']['adminify']['plugin_url'] ) : esc_url( $all_plugins[ WP_ADMINIFY_BASE ]['AuthorURI'] );
		// 	$all_plugins[ WP_ADMINIFY_BASE ]['Title']       = ! empty( $this->options['white_label']['adminify']['plugin_name'] ) ? esc_html( $this->options['white_label']['adminify']['plugin_name'] ) : esc_html( $all_plugins[ WP_ADMINIFY_BASE ]['Title'] );
		// 	$all_plugins[ WP_ADMINIFY_BASE ]['AuthorName']  = ! empty( $this->options['white_label']['adminify']['author_name'] ) ? esc_html( $this->options['white_label']['adminify']['author_name'] ) : esc_attr( $all_plugins[ WP_ADMINIFY_BASE ]['AuthorName'] );

		// 	return $all_plugins;
		// }
	}
}
