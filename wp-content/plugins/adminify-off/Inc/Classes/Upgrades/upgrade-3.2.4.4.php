<?php
function jltadminify_redirect_urls_home_replace( $redirect_url){
    $updated_with_home_url = home_url('/');
    $replaced_url = str_replace($updated_with_home_url, '', $redirect_url);
    $replaced_url =  rtrim(preg_replace("(^https?://)", "", $replaced_url), '/');
    return $replaced_url;
}

// Redirect URLs
function jltadminify_update_redirect_urls()
{
    $redirect_urls_options_settings = get_option('_wpadminify_redirect_urls', '');

    if(!empty($redirect_urls_options_settings)){
        $redirect_urls_options_settings = $redirect_urls_options_settings['redirect_urls_options'];
        // Login URL
        if(!empty($redirect_urls_options_settings['new_login_url']['url'])){
            $updated_new_login_url = $redirect_urls_options_settings['new_login_url']['url'];
            $updated_replaced_login_url = jltadminify_redirect_urls_home_replace($updated_new_login_url);   // Updated Login URL
            $updated_redirect_new_login_url_options_settings['redirect_urls_options']['new_login_url'] = $updated_replaced_login_url;
            update_option('_wpadminify_redirect_urls', $updated_redirect_new_login_url_options_settings);
        }

        // Roles Redirect: Login URL
        if(!empty($redirect_urls_options_settings['login_redirects'])){
            foreach($redirect_urls_options_settings['login_redirects'] as $key=>$login_value){
                if(!empty($login_value['redirect_url']['url'])){
                    $roles_redirect_urls = $login_value['redirect_url']['url'];
                    $updated_replaced_login_url = jltadminify_redirect_urls_home_replace($roles_redirect_urls); // Updated Login URL
                    $updated_roles_redirect_new_login_url_options_settings['redirect_urls_options']['login_redirects'][$key]['redirect_url'] = $updated_replaced_login_url;
                    update_option('_wpadminify_redirect_urls', $updated_roles_redirect_new_login_url_options_settings);
                }
            }
        }

        // Roles Redirect: Logout URL
        if(!empty($redirect_urls_options_settings['logout_redirects'])){
            foreach($redirect_urls_options_settings['logout_redirects'] as $key=>$logout_value){
                if(!empty($logout_value['redirect_url']['url'])){
                    $roles_redirect_urls = $logout_value['redirect_url']['url'];
                    $updated_replaced_login_url = jltadminify_redirect_urls_home_replace($roles_redirect_urls); // Updated Login URL
                    $updated_roles_redirect_new_logout_redirects_options_settings['redirect_urls_options']['logout_redirects'][$key]['redirect_url'] = $updated_replaced_login_url;
                    update_option('_wpadminify_redirect_urls', $updated_roles_redirect_new_logout_redirects_options_settings);
                }
            }
        }

        // Admin URL
        if(!empty($redirect_urls_options_settings['redirect_admin_url']['url'])){
            $jltadminify_redirect_admin_url = $redirect_urls_options_settings['redirect_admin_url']['url'];
            $updated_replaced_admin_url = jltadminify_redirect_urls_home_replace($jltadminify_redirect_admin_url ); // Updated Login URL
            $updated_redirect_redirect_admin_url_options_settings['redirect_urls_options']['redirect_admin_url'] = $updated_replaced_admin_url;
            update_option('_wpadminify_redirect_urls', $updated_redirect_redirect_admin_url_options_settings);
        }


        // Register URL
        if(!empty($redirect_urls_options_settings['new_register_url']['url'])){
            $jltadminify_new_register_url = $redirect_urls_options_settings['new_register_url']['url'];
            $updated_replaced_admin_url = jltadminify_redirect_urls_home_replace($jltadminify_new_register_url );   // Updated Login URL
            $updated_redirect_new_register_url_options_settings['redirect_urls_options']['new_register_url'] = $updated_replaced_admin_url;
            update_option('_wpadminify_redirect_urls', $updated_redirect_new_register_url_options_settings);
        }
    }
}

jltadminify_update_redirect_urls();
