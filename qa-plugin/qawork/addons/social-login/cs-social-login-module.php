<?php

/*
  Credits : 
  Question2Answer (c) Gideon Greenspan
  Open Login Plugin (c) Alex Lixandru

  Some part of this code was taken from qa-openlogin plugin by alixandru
  https://github.com/alixandru/q2a-open-login
 */

class qw_open_login {

    var $directory;
    var $urltoroot;
    var $provider;

    function load_module($directory, $urltoroot, $type, $provider) {
        $this->directory = $directory;
        $this->urltoroot = $urltoroot;
        $this->provider = $provider;
    }

    function check_login() {

        $action = null;
        $key = null;
        if (!empty($_GET['hauth_start'])) {
            $key = trim(strip_tags($_GET['hauth_start']));
            $action = 'process';
        } else if (!empty($_GET['hauth_done'])) {
            $key = trim(strip_tags($_GET['hauth_done']));
            $action = 'process';
        } else if (!empty($_GET['login'])) {
            $key = trim(strip_tags($_GET['login']));
            $action = 'login';
        } else if (isset($_GET['fb_source']) && $_GET['fb_source'] == 'appcenter' &&
                isset($_SERVER['HTTP_REFERER']) && stristr($_SERVER['HTTP_REFERER'], 'www.facebook.com') !== false &&
                isset($_GET['fb_appcenter']) && $_GET['fb_appcenter'] == '1' && isset($_GET['code'])) {
            // allow AppCenter users to login directly
            $key = 'facebook';
            $action = 'login';
        }

        if ($key == null || strcasecmp($key, $this->provider) != 0) {
            return false;
        }

        if ($action == 'login') {
            // handle the login
            // after login come back to the same page
            $loginCallback = qa_path('', array(), QW_BASE_URL);

            require_once QW_CONTROL_DIR . '/inc/hybridauth/Hybrid/Auth.php';
            require_once QW_CONTROL_DIR . '/addons/social-login/cs-social-login-utils.php';

            // prepare the configuration of HybridAuth
            $config = $this->qw_social_get_config($loginCallback);

            $topath = qa_get('to');
            if (!isset($topath)) {
                $topath = ''; // redirect to front page
            }

            try {
                // try to login
                $hybridauth = new Hybrid_Auth($config);
                $adapter = $hybridauth->authenticate($this->provider);

                // if ok, create/refresh the user account
                $user = $adapter->getUserProfile();
                $duplicates = 0;
                if (!empty($user)) $duplicates = qa_log_in_external_user($key, $user->identifier, array(
                        'email' => @$user->email,
                        'handle' => @$user->displayName,
                        'confirmed' => !empty($user->emailVerified),
                        'name' => @$user->displayName,
                        'location' => @$user->region,
                        'website' => @$user->webSiteURL,
                        'about' => @$user->description,
                        'avatar' => strlen(@$user->photoURL) ? qa_retrieve_url($user->photoURL) : null,
                    ));

                if ($duplicates > 0) {
                    qa_redirect('logins', array('confirm' => '1', 'to' => $topath));
                } else {
                    if (!!$this->provider && strlen(@$hybridauth->getSessionData())) {
                        qw_save_user_hauth_session( qa_get_logged_in_userid() , $this->provider  , @$hybridauth->getSessionData() );
                    }
                    qa_redirect_raw(qa_opt('site_url') . $topath);
                }
            } catch (Exception $e) {
                // not really interested in the error message - for now
                // however, in case we have errors 6 or 7, then we have to call logout to clean everything up
                if ($e->getCode() == 6 || $e->getCode() == 7) {
                    if(isset($adapter) && !empty($adapter))
                        $adapter->logout();
                }

                $qry = 'provider=' . $this->provider . '&code=' . $e->getCode();
                if (strstr($topath, '?') === false) {
                    $topath .= '?' . $qry;
                } else {
                    $topath .= '&' . $qry;
                }

                // redirect
                qa_redirect_raw(qa_opt('site_url') . $topath);
            }
        }

        if ($action == 'process') {
            require_once QW_CONTROL_DIR . '/inc/hybridauth/Hybrid/Auth.php';
            require_once QW_CONTROL_DIR . '/inc/hybridauth/Hybrid/Endpoint.php';
            Hybrid_Endpoint::process();
        }

        return false;
    }

    function do_logout() {
        // after login come back to the same page
        $loginCallback = qa_path('', array(), qa_opt('site_url'));

        require_once QW_CONTROL_DIR . '/inc/hybridauth/Hybrid/Auth.php';

        // prepare the configuration of HybridAuth
        $config = $this->qw_social_get_config($loginCallback);

        try {
            // try to logout
            $hybridauth = new Hybrid_Auth($config);

            if ($hybridauth->isConnectedWith($this->provider)) {
                $adapter = $hybridauth->getAdapter($this->provider);
                $adapter->logout();
            }
        } catch (Exception $e) {
            // not really interested in the error message - for now
            // however, in case we have errors 6 or 7, then we have to call logout to clean everything up
            if ($e->getCode() == 6 || $e->getCode() == 7) {
                $adapter->logout();
            }
        }
    }

    function match_source($source) {
        // the session source will be in the format 'provider-xyx'
        $pos = strpos($source, '-');
        if ($pos === false) {
            $pos = strlen($source);
        }

        // identify the provider out of the session source
        $provider = substr($source, 0, $pos);

        // verify if the identified provider matches the current one
        return stripos($this->provider, $provider) !== false;
    }

    function login_html($tourl, $context) {
        qw_open_login::qw_social_print_code($this->provider, $tourl, $context, 'login');
    }

    function logout_html($tourl) {
        qw_open_login::qw_social_print_code($this->provider, $tourl, 'menu', 'logout');
    }

    static function qw_social_print_code($provider, $tourl, $context, $action = 'login', $print = true) {
        $css = $key = strtolower($provider);
        if ($key == 'live') {
            $css = 'windows'; // translate provider name to zocial css class
        }
        $showInHeader = qa_opt("{$key}_app_shortcut") ? true : false;

        if ($action == 'login' && !$showInHeader && $context == 'menu') {
            // do not show login button in the header for this
            return;
        }

        $zocial = qa_opt('open_login_zocial') == '1' ? 'zocial' : ''; // use zocial buttons
        if ($action == 'logout') {
            $url = $tourl;
            $classes = "$context action-logout $zocial $css";
            $title = qa_lang_html('main/nav_logout');
            $text = qa_lang_html('main/nav_logout');
        } else if ($action == 'login') {
            $topath = qa_get('to'); // lets user switch between login and register without losing destination page
            // clean GET parameters (not performed when to parameter is already passed) 
            $get = $_GET;
            unset($get['provider']);
            unset($get['code']);

            $tourl = isset($topath) ? $topath : qa_path(qa_request(), $get, ''); // build our own tourl
            $params = array(
                'login' => $key,
            );

            $url = qa_path('login', $params, qa_path_to_root());
            if (strlen($tourl) > 0) {
                $url .= '&amp;to=' . $tourl; // play nice with validators
            }
            $classes = "$context action-login $zocial $css";
            $title = qa_lang_html_sub('qw_social_login/login_using', $provider);
            $text = $provider . ' ' . qa_lang_html('main/nav_login');

            if ($context != 'menu') {
                $text = $title;
            } else {
                $text = "";
            }
        } else if ($action == 'link') {
            $url = qa_path('logins', array('link' => $key), qa_path_to_root()); // build our own url
            $classes = "$context action-link $zocial $css";
            $title = qa_lang_html_sub('qw_social_login/login_using', $provider);
            $text = qa_lang_html('main/nav_login');

            if ($context != 'menu') {
                $text = $title;
            } else {
                $text = "";
            }
        } else if ($action == 'view') {
            $url = 'javascript://';
            $classes = "$context action-link $zocial $css";
            $title = $provider;
            $text = $tourl;
        }

        $html = '<a class="open-login-button context-' . $classes . '" title="' . $title . '" href="' . $url . '" rel="nofollow">' . $text . '</a>';

        if ($print) {
            echo $html;
        } else {
            return $html;
        }
    }

    function qw_social_get_config($url) {
        $key = strtolower($this->provider);
        return array(
            'base_url' => $url,
            'providers' => array(
                $this->provider => array(
                    'enabled' => true,
                    'keys' => array(
                        'id' => qa_opt("{$key}_app_id"),
                        'key' => qa_opt("{$key}_app_id"),
                        'secret' => qa_opt("{$key}_app_secret")
                    ),
                    'scope' => $this->provider == 'Facebook' ? 'email,user_about_me,user_location,user_website' : null,
                )
            ),
            'debug_mode' => false,
            'debug_file' => ''
        );
    }

}
