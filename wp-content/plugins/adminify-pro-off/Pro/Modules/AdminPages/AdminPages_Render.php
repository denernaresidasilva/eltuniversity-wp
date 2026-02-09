<?php

namespace WPAdminify\Pro;

// no direct access allowed
if (!defined('ABSPATH'))  exit;

/**
 * WPAdminify
 * @package Admin Pages
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */


class AdminPages_Render extends AdminPagesModel
{

    public function __construct($post, $from_multisite = false)
    {
        // global $pagenow;
        // if(!empty($post->remove_admin_notices)){
        //     remove_all_actions( 'user_admin_notices' );
        //     remove_all_actions( 'admin_notices' );
        // }

        $this->init_admin_page_content_new($post, $from_multisite);
    }

    public function init_admin_page_content_new($post, $from_multisite)
    {

        $remove_page_title  = $post->remove_page_title;
        $remove_page_margin = $post->remove_page_margin;

        $custom_css = $post->custom_css;

        $link = get_permalink($post);
        $link = add_query_arg('bknd', 1, $link);

        printf('<iframe class="wp-adminify--admin-page" src="%s"></iframe>', esc_url( $link ) );

    ?>

        <style>
            .wp-adminify #wpbody{
                overflow:hidden;
            }
            .wp-adminify #wpbody-content {
                position: relative;
                overflow: hidden;
            }

            iframe.wp-adminify--admin-page {
                width: 100%;
                height: 100%;
                position: relative;
            }

            <?php if ($remove_page_margin) : ?>#wpcontent {
                padding-left: 0;
            }

            .wrap {
                margin: 0;
            }

            <?php endif; ?>
        </style>
    <?php

    }
}
