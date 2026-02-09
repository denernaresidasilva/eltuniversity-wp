<?php

namespace WPAdminify\Pro;

use \WPAdminify\Inc\Classes\MenuStyle;
use WPAdminify\Inc\Classes\MenuStyles\MenuStyleBase;
use \WPAdminify\Inc\Classes\MenuStyles\VerticalMainMenu;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

class MenuStylePro extends MenuStyleBase {

    public function __construct()
    {
        parent::__construct();
        new HorizontalMenu();
    }

}
