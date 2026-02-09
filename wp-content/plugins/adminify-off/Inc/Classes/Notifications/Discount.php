<?php

namespace WPAdminify\Inc\Classes\Notifications;

use WPAdminify\Inc\Classes\Notifications\Base\User_Data;
use WPAdminify\Inc\Classes\Notifications\Model\Notice;


if (!class_exists('Discount')) {
    /**
     * Discount Class
     *
     * Jewel Theme <support@jeweltheme.com>
     */
    class Discount extends Notice
    {

        use User_Data;

        public $color       = 'none';
        public $coupon_code = 'LITEFLASH25';

        /**
         * Construct method
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function __construct()
        {
            parent::__construct();

            if (empty(get_option('wpadminify_notice_discount_code'))) {
                update_option('wpadminify_notice_discount_code', $this->coupon_code);
            }
            add_action('admin_enqueue_scripts', [$this, 'discount_coupon_scripts'], 999);
        }

        /**
         * Notice Content
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function notice_content()
        { ?>
            <div class="wp-adminify-discount-coupons-wrapper notice notice-jltma is-dismissible notice-plugin-discount notice-<?php echo esc_attr($this->color); ?> wp-adminify-notice-<?php echo esc_attr($this->get_id()); ?>">

                <!-- <?php //echo esc_url(JLTMA_IMAGE_DIR . 'promo-image.png'); ?>
				https://master-addons.com/pricing-lite/

				<button type="button" class="notice-dismiss wp-adminify-notice-dismiss"></button> -->
                <div class="wp-adminify-subscribe-content">
                    <h2>
                        Notice Content, Coupon Code: <?php echo esc_attr($this->coupon_code); ?>
                    </h2>
                    <h2>
                        <svg width="10" height="12" viewBox="0 0 10 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.5 1L0.5 7H5L4.5 11L9.5 5H5L5.5 1Z" stroke="#D92D20" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Flash Sale
                    </h2>
                    <h3>Notice Content Notice Content Notice Content Notice Content Notice Content Notice Content Notice Content Notice Content </h3>
                    <img width="70" height="70" src="<?php echo esc_url(WP_ADMINIFY_ASSETS_IMAGE . 'promo-image.png'); ?>" alt="<?php esc_attr_e('20% Discount Coupon', 'adminify'); ?>">
                </div>
            </div>
        <?php
        }


        public function discount_coupon_scripts()
        {
            $discount_css = '';
            $discount_css .= '.wp-adminify-discount-coupons-wrapper.notice-jltma{
			adding: 0 !important;
			border: 0;
			}';
            $discount_css .= '.wp-adminify-admin #wpbody-content .notice-jltma{
			adding: 0 !important;
			border: 0;
			background-image: url("")
			}';
            // $discount_css .= '.wp-adminify-discount-coupons-wrapper .wp-adminify-subscribe-content{
            // background: red;
            // }';

            wp_add_inline_style('master-addons-admin-sdk', $discount_css);
        }



        /**
         * Intervals
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function intervals()
        {
            return array(5, 4, 10, 20, 15, 25, 30, 45, 15, 30, 10, 6);
        }
    }
}
