<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Classes\ServerInfo;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;


// no direct access allowed.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin footer text
 *
 * @package WP Adminify
 * @author: Jewel Theme<support@jeweltheme.com>
 */
class AdminFooterText extends AdminSettingsModel
{
	/**
	 * Server Info
	 *
	 * @var $server_info
	 */
	public $server_info;

	/**
	 * Constructor
	 */
	public function __construct()
	{

        $this->options = (array) AdminSettings::get_instance()->get();
        $this->options = !empty($this->options['white_label']['wordpress']['admin_footer']) ? $this->options['white_label']['wordpress']['admin_footer'] : '';

		// Remove Admin Footer Version Number
		if (!empty($this->options) && in_array('wp_version', $this->options)) {
			add_action('admin_menu', [$this, 'remove_admin_footer_version']);
		}

		add_action('admin_menu', [$this, 'jltwp_adminify_footer_version_remove']);
		add_action('network_admin_menu', [$this, 'jltwp_adminify_footer_version_remove']);
		/** Admin Footer */
		add_filter('update_footer', [$this, 'jltwp_adminify_change_admin_footer'], 10, 3);

		add_filter( 'admin_footer_text', '__return_false', 999 );
		add_filter( 'update_footer', '__return_false', 999 );

		$this->server_info = new ServerInfo();
	}



	/**
	 * Remove WordPress version
	 *
	 * @return void
	 */
	public function remove_admin_footer_version()
	{
		// Remove WordPress Version except Admin
		if (!current_user_can('manage_options')) {
			remove_filter('update_footer', 'core_update_footer');
		}
	}


	// function jltwp_adminify_footer_get_options()
	// {
	// 	$objects = isset($this->options) && is_array($this->options) ? $this->options : [];
	// 	return $objects;
	// }

	public function jltwp_adminify_footer_version_remove()
	{
		remove_filter('update_footer', 'core_update_footer');
	}

	/** Footer Credits */
	public function jltwp_adminify_footer_credits()
	{    ?>
		<div class="adminify-copyright">
			<?php
			echo sprintf( __('<p>Developed by <a href="%1$s" target="_blank" title="WP Adminify by Jewel Theme" target="_blank">%2$s</a></p> <p>Powered by <a target="_blank" href="%3$s">WordPress</a></p>', 'adminify'),
				esc_url('https://wpadminify.com/'),
				__('WP Adminify', 'adminify'),
				esc_url( 'https://wordpress.org/' )
			);
			?>
		</div>
		<?php
	}


	public function change_admin_footer_text()
	{
		$footer_text = (array) AdminSettings::get_instance()->get();

		if (!empty($footer_text['white_label']['wordpress']['footer_text'])) { ?>
			<div class="adminify-footer-left">
				<?php echo wp_kses_post($footer_text['white_label']['wordpress']['footer_text']); ?>
			</div>
			<?php
			return;
		}
		// Change the content of the left admin footer text.
		apply_filters('jltwp_adminify_footer_credits', $this->jltwp_adminify_footer_credits());
	}


	/**
	 * IP Address
	 */
	public function adminify_ip_address() { ?>
		<div class="adminify-system-info">
			<?php
				if (is_rtl()) {
					echo sprintf(
						wp_kses_post('<span>%2$s</span><span>%1$s</span>'),
						esc_html__('IP: ', 'adminify'),
						esc_html($this->server_info->get_ip_address())
					);
				} else {
					echo sprintf(
						wp_kses_post('<span>%1$s</span><span>%2$s</span>'),
						esc_html__('IP: ', 'adminify'),
						esc_html($this->server_info->get_ip_address())
					);
				}
			?>
		</div>
	<?php
	}


	/**
	 * PHP Version
	 */
	public function adminify_php_version() { ?>
		<div class="adminify-system-info">
			<?php
				if (is_rtl()) {
					echo sprintf(
						'<span>%2$s</span><span>%1$s</span>',
						esc_html__('PHP: ', 'adminify'),
						esc_html($this->server_info->get_php_version_lite())
					);
				} else {
					echo sprintf(
						'<span>%1$s</span><span>%2$s</span>',
						esc_html__('PHP: ', 'adminify'),
						esc_html($this->server_info->get_php_version_lite())
					);
				}
			?>
		</div>
	<?php
	}

	/**
	 * WordPress Version
	 */
	public function adminify_wp_version() { ?>
		<div class="adminify-system-info">
			<?php
				if (is_rtl()) {
					echo sprintf(
						'<span>%2$s</span><span>%1$s</span>',
						esc_html__('WordPress: v', 'adminify'),
						esc_html($this->server_info->get_wp_version())
					);
				} else {
					echo sprintf(
						'<span>%1$s</span><span>%2$s</span>',
						esc_html__('WordPress: v', 'adminify'),
						esc_html($this->server_info->get_wp_version())
					);
				}
			?>
		</div>
	<?php
	}

	/**
	 * Memory Usage
	 */
	public function adminify_memory_usage() {
		$memory_usage        = $this->server_info->get_wp_memory_usage();
		$memory_limit        = $memory_usage['MemLimitFormat'];
		$memory_usage_format = $memory_usage['MemUsageFormat'];
		// $memory_usage_percentage = $memory_usage['MemUsageCalc'];
		$memory_usage_percentage = ServerInfo::wp_memory_usage_percentage();

		if ($memory_usage_percentage <= 65) {
			$memory_status = '#00BA88';
		} elseif ($memory_usage_percentage > 65 && $memory_usage_percentage < 85) {
			$memory_status = '#ffe08a';
		} elseif ($memory_usage_percentage > 85) {
			$memory_status = '#f14668';
		} ?>
		<div class="adminify-system-info">
			<?php
				if (is_rtl()) {
					echo sprintf(
						wp_kses_post('<span>%2$s of %3$s <span class="adminify-info-status" style="background:%4$s">%5$s</span></span><span>%1$s</span>'),
						esc_html__('WP Memory Usage: ', 'adminify'),
						esc_html($memory_usage_format),
						esc_html($memory_limit),
						esc_html($memory_status),
						esc_html($memory_usage_percentage) . '%'
					);
				} else {
					echo sprintf(
						wp_kses_post('<span>%1$s</span><span>%2$s of %3$s <span class="adminify-info-status" style="background:%4$s">%5$s</span></span>'),
						esc_html__('WP Memory Usage: ', 'adminify'),
						esc_html($memory_usage_format),
						esc_html($memory_limit),
						esc_html($memory_status),
						esc_html($memory_usage_percentage) . '%'
					);
				}
			?>
			</span>
		</div>
	<?php
	}

	/**
	 * Memory Limit
	 */
	public function adminify_memory_limit() {
		$memory_limit = $this->server_info->get_wp_memory_usage();

		$memory_limit = $memory_limit['MemLimitFormat']; ?>

		<div class="adminify-system-info">
			<?php
			if (is_rtl()) {
				echo sprintf(
					'<span>%2$s</span><span>%1$s</span>',
					esc_html__('WP Memory Limit: ', 'adminify'),
					esc_html($memory_limit)
				);
			} else {
				echo sprintf(
					'<span>%1$s</span><span>%2$s</span>',
					esc_html__('WP Memory Limit: ', 'adminify'),
					esc_html($memory_limit)
				);
			}
			?>
		</div>
	<?php
	}


	/**
	 * Memory Limit
	 */
	public function adminify_memory_available() {
		$memory_available = $this->server_info->get_wp_memory_usage();
		$memory_available = rtrim($memory_available['MemLimitGet'], 'MB') - rtrim($memory_available['MemUsageFormat'], 'MB'); ?>

		<div class="adminify-system-info">
			<?php
			if (is_rtl()) {
				echo sprintf(
					'<span>%2$s</span><span>%1$s</span>',
					esc_html__('WP Memory Available: ', 'adminify'),
					esc_html($memory_available)
				);
			} else {
				echo sprintf(
					'<span>%1$s</span><span>%2$s</span>',
					esc_html__('WP Memory Available: ', 'adminify'),
					esc_html($memory_available)
				);
			}
			?>MB
		</div>
	<?php
	}


	/** Admin Footer Text **/
	public function jltwp_adminify_change_admin_footer($footer_text) { ?>

		<div class="adminify--footer">
			<?php echo $this->change_admin_footer_text();
			if ( !empty($this->options) ) { ?>

				<div class="adminify-footer-right">
					<div class="adminify-system-info-col">
						<?php
							if ( in_array('ip_address', $this->options ) ) {
								$this->adminify_ip_address();
							}

							if ( in_array('php_version', $this->options ) ) {
								$this->adminify_php_version();
							}

							if ( in_array('wp_version', $this->options ) ) {
								$this->adminify_wp_version();
							}
						?>
					</div>
					<div class="adminify-system-info-col">
						<?php
							if ( in_array('memory_usage', $this->options ) ) {
								$this->adminify_memory_usage();
							}

							if ( in_array('memory_limit', $this->options ) ) {
								$this->adminify_memory_limit();
							}

							if ( in_array('memory_available', $this->options ) ) {
								$this->adminify_memory_available();
							}
						?>
					</div>
				</div>

				<?php return $footer_text;
			} ?>
		</div>
		<?php
		// Nothing, return blank
		return '';
	}
}
