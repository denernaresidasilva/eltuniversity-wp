<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Modules\DismissNotices\Dismiss_Admin_Notices;

// no direct access allowed
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Package: Dismiss Notice
 *
 * @package WP Adminify
 *
 * @author WP Adminify <support@wpadminify.com>
 */

class Filters
{
  public function __construct()
  {
    if (jltwp_adminify()->can_use_premium_code__premium_only()) {

      // Remove Adminify Upgrade Now link
      add_filter('adminify_upgrade_now_link', '__return_null');

      add_filter('adminify/menu_editor', [$this, 'add_menu_item']);
      add_filter('adminify_settings/color_presets', [$this, 'color_presets']);

      // AdminSettings
      add_filter('adminify/admin_settings/network', [$this, 'admin_settings_network']);
      add_filter('adminify/menu_editor/add_separator', [$this, 'admin_menu_editor_separator'], 99, 2);

      // Admin Favicon
      add_filter('adminify/frame/favicon', [$this, 'admin_favicon']);
    }
  }

  /**
   * Admin Favicon
   *
   * @return void
   */
  public function admin_favicon($favicon){

      $options = (array) AdminSettings::get_instance()->get();
      if (!empty($options['admin_favicon_logo'])) {
        $favicon = $options['admin_favicon_logo']['url'];
      }
      return $favicon;
  }

  /**
   * Separator Content
   *
   * @param string $separator_content
   * @param [type] $name_attr
   * @param [type] $separator
   *
   * @return void
   */
  public function admin_menu_editor_separator($name_attr, $separator)
  {
    $checked = "";
    if( $name_attr === 'index.php' && $separator === "" ) {
        $checked = 'checked';
    }else if( $name_attr === 'edit-comments.php' && $separator === "" ) {
        $checked = 'checked';
    }
    ?>
      <input class="menu_setting" id="seperator-<?php echo esc_attr($name_attr); ?>" name="separator" type="checkbox" value="1" <?php checked($separator, 1); ?> <?php echo esc_attr($checked); ?>>
      <?php esc_html_e('Add Separator', 'adminify'); ?>

    <?php
  }

  public function admin_settings_network()
  {
    $sites = AdminSettings::get_instance()->get_sites();

    AdminSettings::get_instance()->maybe_display_message();

  ?>

    <div class="container wp-clone-sites-options">
      <form method="post" action="<?php echo esc_url(network_admin_url('admin.php?page=wp-adminify-settings')); ?>">

        <h1><?php esc_html_e('Network Settings', 'adminify'); ?>
          <p><?php esc_html_e('Clone Site Option\'s. You can copy a site settings to another. Also, you can Copy and "Apply to All Sites", exclude sites settings etc', 'adminify'); ?></p>
        </h1>


        <div class="line-single--wrapper copy_from-field-wrapper">
          <div class="line-single--title"><?php esc_html_e('Copy From', 'adminify'); ?></div>
          <div class="line-single--content">
            <select class="select-field copy_from" name="copy_from">
              <?php echo Utils::wp_kses_custom(AdminSettings::get_instance()->get_sites_option($sites, true)); ?>
            </select>
          </div>
        </div>

        <div class="line-single--wrapper copy_to-field-wrapper">
          <div class="line-single--title"><?php esc_html_e('Copy To', 'adminify'); ?></div>
          <div class="line-single--content">
            <select class="select-field copy_to" name="copy_to">
              <?php echo Utils::wp_kses_custom(AdminSettings::get_instance()->get_sites_option_empty()); ?>
              <option value="copy_to_all"><?php esc_html_e('Copy to All Sites', 'adminify'); ?></option>
              <?php echo Utils::wp_kses_custom(AdminSettings::get_instance()->get_sites_option($sites)); ?>
            </select>
          </div>
        </div>

        <div class="line-single--wrapper copy_exclude-field-wrapper" style="display: none;">
          <div class="line-single--title"><?php esc_html_e('Exclude', 'adminify'); ?></div>
          <div class="line-single--content">
            <select class="select-field copy_exclude" name="copy_exclude[]" multiple>
              <?php echo Utils::wp_kses_custom(AdminSettings::get_instance()->get_sites_option($sites, false)); ?>
            </select>
          </div>
        </div>

        <div class="line-single--wrapper option_modules-field-wrapper" style="display: block;">
          <div class="line-single--title"><?php esc_html_e('Options', 'adminify'); ?></div>
          <div class="line-single--content">

            <?php foreach (AdminSettings::get_instance()->option_modules() as $option_module_key => $option_module) : ?>
              <div>
                <label for="<?php echo 'adminify_clone_' . esc_attr($option_module_key); ?>">
                  <input id="<?php echo 'adminify_clone_' . esc_attr($option_module_key); ?>" type="checkbox" name="option_modules[]" value="<?php echo esc_attr($option_module_key); ?>" checked />
                  <span><?php echo esc_html($option_module); ?></span>
                </label>
              </div>
            <?php endforeach; ?>

            <button id="option_modules_toggle" type="none"><?php esc_html_e('Toggle Options', 'adminify'); ?></button>
          </div>
        </div>

        <div>
          <input type="hidden" name="action" value="adminify_site_option_clone">
          <?php wp_nonce_field('adminify_site_option_clone', '_wpnonce'); ?>
          <input type="submit" class="button button-small" value="<?php esc_attr_e('Clone Adminify Options', 'adminify'); ?>" />
        </div>

      </form>
    </div>

<?php
  }

  public function color_presets()
  {
    $pro_presets = [

      'preset3' => [
        '--adminify-preset-background'        => '#F9F9F9',
        '--adminify-primary'                  => '#FF8811',
        '--adminify-notif-bg-color'           => '#3397C1',
        // '--adminify-text-color'           => '#ffffff',
        '--adminify-menu-border'              => '#615b5b',
        '--adminify-menu-bg'                  => '#3F3535',
        '--adminify-menu-hover-bg'            => '#FF8811',
        '--adminify-menu-text-color'          => '#ffffff',
        '--adminify-menu-text-hover-color'    => '#ffffff',
        '--adminify-menu-active-bg'           => '#FF8811',
        '--adminify-menu-active-color'        => '#ffffff',
        '--adminify-submenu-wrapper-bg'       => '#3F3535',
        '--adminify-submenu-hover-bg'         => 'transparent',
        '--adminify-submenu-text-color'       => '#ffffff',
        '--adminify-submenu-text-hover-color' => '#FF8811',
        '--adminify-submenu-active-bg'        => 'transparent',
        '--adminify-submenu-active-color'     => '#FF8811',
        '--adminify-notif-bg-color'           => '#FF8811',
        '--adminify-notif-color'              => '#ffffff',
      ],

      'preset4' => [
        '--adminify-preset-background'        => '#F9F9F9',
        '--adminify-primary'                  => '#2EB5E0',
        '--adminify-notif-bg-color'           => '#FF8811',
        // '--adminify-text-color'            => '#ffffff',
        '--adminify-menu-border'              => '#448c9e',
        '--adminify-menu-bg'                  => '#0e6980',
        '--adminify-menu-hover-bg'            => '#2EB5E0',
        '--adminify-menu-text-color'          => '#ffffff',
        '--adminify-menu-text-hover-color'    => '#ffffff',
        '--adminify-menu-active-bg'           => '#2EB5E0',
        '--adminify-menu-active-color'        => '#ffffff',
        '--adminify-submenu-wrapper-bg'       => '#0e6980',
        '--adminify-submenu-hover-bg'         => 'transparent',
        '--adminify-submenu-text-color'       => '#ffffff',
        '--adminify-submenu-text-hover-color' => '#2EB5E0',
        '--adminify-submenu-active-bg'        => 'transparent',
        '--adminify-submenu-active-color'     => '#2EB5E0',
        '--adminify-notif-bg-color'           => '#2EB5E0',
        '--adminify-notif-color'              => '#ffffff',
      ],

      'preset5' => [
        '--adminify-preset-background'        => '#F9F9F9',
        '--adminify-primary'                  => '#fd49a0',
        '--adminify-notif-bg-color'           => '#FD49A0',
        // '--adminify-text-color'           => '#ffffff',
        '--adminify-menu-border'              => '#846ba5',
        '--adminify-menu-bg'                  => '#603f8b',
        '--adminify-menu-hover-bg'            => '#fd49a0',
        '--adminify-menu-text-color'          => '#ffffff',
        '--adminify-menu-text-hover-color'    => '#ffffff',
        '--adminify-menu-active-bg'           => '#fd49a0',
        '--adminify-menu-active-color'        => '#ffffff',
        '--adminify-submenu-wrapper-bg'       => '#603f8b',
        '--adminify-submenu-hover-bg'         => 'transparent',
        '--adminify-submenu-text-color'       => '#ffffff',
        '--adminify-submenu-text-hover-color' => '#fd49a0',
        '--adminify-submenu-active-bg'        => 'transparent',
        '--adminify-submenu-active-color'     => '#fd49a0',
        '--adminify-notif-bg-color'           => '#fd49a0',
        '--adminify-notif-color'              => '#ffffff',
      ],

      'preset6' => [
        '--adminify-preset-background'        => '#F9F9F9',
        '--adminify-primary'                  => '#ffb705',
        '--adminify-notif-bg-color'           => '#90BE6D',
        // '--adminify-text-color'            => '#ffffff',
        '--adminify-menu-border'              => '#f77073',
        '--adminify-menu-bg'                  => '#eb3135',
        '--adminify-menu-hover-bg'            => '#ffb705',
        '--adminify-menu-text-color'          => '#ffffff',
        '--adminify-menu-text-hover-color'    => '#ffffff',
        '--adminify-menu-active-bg'           => '#ffb705',
        '--adminify-menu-active-color'        => '#ffffff',
        '--adminify-submenu-wrapper-bg'       => '#eb3135',
        '--adminify-submenu-hover-bg'         => 'transparent',
        '--adminify-submenu-text-color'       => '#ffffff',
        '--adminify-submenu-text-hover-color' => '#ffb705',
        '--adminify-submenu-active-bg'        => 'transparent',
        '--adminify-submenu-active-color'     => '#ffb705',
        '--adminify-notif-bg-color'           => '#ffb705',
        '--adminify-notif-color'              => '#ffffff',
      ],

      'preset7' => [
        '--adminify-preset-background'        => '#F9F9F9',
        '--adminify-primary'                  => '#588157',
        '--adminify-notif-bg-color'           => '#A3B18A',
        // '--adminify-text-color'            => '#ffffff',
        '--adminify-menu-border'              => '#5d776a',
        '--adminify-menu-bg'                  => '#344e41',
        '--adminify-menu-hover-bg'            => '#588157',
        '--adminify-menu-text-color'          => '#ffffff',
        '--adminify-menu-text-hover-color'    => '#ffffff',
        '--adminify-menu-active-bg'           => '#588157',
        '--adminify-menu-active-color'        => '#ffffff',
        '--adminify-submenu-wrapper-bg'       => '#344e41',
        '--adminify-submenu-hover-bg'         => 'transparent',
        '--adminify-submenu-text-color'       => '#ffffff',
        '--adminify-submenu-text-hover-color' => '#588157',
        '--adminify-submenu-active-bg'        => 'transparent',
        '--adminify-submenu-active-color'     => '#588157',
        '--adminify-notif-bg-color'           => '#588157',
        '--adminify-notif-color'              => '#ffffff',
      ],

      'preset8' => [
        '--adminify-preset-background'        => '#F9F9F9',
        '--adminify-primary'                  => '#3c95ff',
        '--adminify-notif-bg-color'           => '#FF961F',
        // '--adminify-text-color'            => '#ffffff',
        '--adminify-menu-border'              => '#506bbe',
        '--adminify-menu-bg'                  => '#2a4494',
        '--adminify-menu-hover-bg'            => '#3c95ff',
        '--adminify-menu-text-color'          => '#ffffff',
        '--adminify-menu-text-hover-color'    => '#ffffff',
        '--adminify-menu-active-bg'           => '#3c95ff',
        '--adminify-menu-active-color'        => '#ffffff',
        '--adminify-submenu-wrapper-bg'       => '#2a4494',
        '--adminify-submenu-hover-bg'         => 'transparent',
        '--adminify-submenu-text-color'       => '#ffffff',
        '--adminify-submenu-text-hover-color' => '#3c95ff',
        '--adminify-submenu-active-bg'        => 'transparent',
        '--adminify-submenu-active-color'     => '#3c95ff',
        '--adminify-notif-bg-color'           => '#3c95ff',
        '--adminify-notif-color'              => '#ffffff',
      ],

      'preset9' => [
        '--adminify-preset-background'        => '#F9F9F9',
        '--adminify-primary'                  => '#48c7fd',
        '--adminify-notif-bg-color'           => '#FD49A0',
        // '--adminify-text-color'            => '#ffffff',
        '--adminify-menu-border'              => '#6f60cf',
        '--adminify-menu-bg'                  => '#4738a6',
        '--adminify-menu-hover-bg'            => '#48c7fd',
        '--adminify-menu-text-color'          => '#ffffff',
        '--adminify-menu-text-hover-color'    => '#ffffff',
        '--adminify-menu-active-bg'           => '#48c7fd',
        '--adminify-menu-active-color'        => '#ffffff',
        '--adminify-submenu-wrapper-bg'       => '#4738a6',
        '--adminify-submenu-hover-bg'         => 'transparent',
        '--adminify-submenu-text-color'       => '#ffffff',
        '--adminify-submenu-text-hover-color' => '#48c7fd',
        '--adminify-submenu-active-bg'        => 'transparent',
        '--adminify-submenu-active-color'     => '#48c7fd',
        '--adminify-notif-bg-color'           => '#48c7fd',
        '--adminify-notif-color'              => '#ffffff',
      ],
    ];

    return $pro_presets;
  }

  public function add_menu_item($menu_data)
  {
    $menu_data = [];
    $menu_data['upgrade_pro'] = ' ';
    $menu_data['upgrade_class'] = ' ';
    $menu_data['sub_level'] = true;

    return $menu_data;
  }
}
