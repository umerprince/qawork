<?php

/*
  Name:QW Blog Post
  Version:1.0
  Author: Amiya Sahu
  Description:For enabling sites to publish blogs
 */

/* don't allow this page to be requested directly from browser */
if (!defined('QA_VERSION')) {
      header('Location: /');
      exit;
}

class Qw_Blog_Post_Addon {

      function __construct() {
            qw_event_hook('register_language', NULL, array($this, 'language'));
            qw_event_hook('enqueue_css', NULL, array($this, 'css'));
            qw_event_hook('enqueue_scripts', NULL, array($this, 'script'));
            qw_add_action('qw_theme_option_tab', array($this, 'option_tab'));
            qw_add_action('qw_theme_option_tab_content', array($this, 'option_tab_content'));
            qw_add_action('qw_reset_theme_options', array($this, 'reset_theme_options'));
      }

      public function language($lang_arr) {
		    $lang_arr['qw_blog_post'] = QW_CONTROL_DIR .'/addons/blog-post/language-*.php';
		    return $lang_arr;
      }
      public function css($css_src) {
            $css_src['qw_blog_post'] = QW_CONTROL_URL . '/addons/blog-post/styles.css';
            return $css_src;
      }
      
      public function script($script_src) {
            $script_src['qw_blog_post'] = QW_CONTROL_URL . '/addons/blog-post/script.js';
            // $script_src['qw_blog_post_facebook'] = "http://connect.facebook.net/en_US/all.js";
            return $script_src;
      }
      public function navigation($themeclass) {
		      // put all your links here 
          
      }

      public function reset_theme_options() {
            if (qa_clicked('qw_reset_button')) {
              
            }
      }

      function option_tab(){
          $saved=false;
          if(qa_clicked('qw_save_button')){   
             
              $saved=true;
            }
          
          return '<li>
              <a href="#" data-toggle=".qa-part-form-blog-post">Blog Post</a>
            </li>';
    }
    function option_tab_content(){
          $output = '<div class="qa-part-form-blog-post">
            <h3>Choose Your social Sharing Options</h3>
            <table class="qa-form-tall-table options-table">';
              
              $output .= '
                <tbody>
                <tr>
                  <th class="qa-form-tall-label">Enable Faebook Posting</th>
                  <td class="qa-form-tall-data">
                    <input type="checkbox"' . (qa_opt('qw_enable_fb_posting') ? ' checked=""' : '') . ' id="qw_styling_rtl" name="qw_enable_fb_posting" data-opts="qw_enable_fb_posting_fields">
                  </td>
                </tr>
                </tbody>
              ';
              $output .= '
                <tbody>
                <tr>
                  <th class="qa-form-tall-label">Enable Twitter Posting</th>
                  <td class="qa-form-tall-data">
                    <input type="checkbox"' . (qa_opt('qw_enable_twitter_posting') ? ' checked=""' : '') . ' id="qw_styling_rtl" name="qw_enable_twitter_posting" data-opts="qw_enable_twitter_posting_fields">
                  </td>
                </tr>
                </tbody>
              ';
              $output .= '
                <tbody>
                <tr>
                  <th class="qa-form-tall-label">Facebook Invite template 
                      <span class="description">Set the template for facebook invite message ({site_url} will be replaced by your website url )</span>
                  </th>
                  <td class="qa-form-tall-data">
                  <textarea id="qw_styling_rtl" rows=5 name="qw_fb_invite_message_field" data-opts="qw_enable_twitter_posting_fields">'.qa_opt('qw_fb_invite_message').'</textarea>
                  </td>
                </tr>
                </tbody>
              ';

            $output .= '</table></div>';
            return $output;
    }


} //class

$qw_blog_post_addon = new Qw_Blog_Post_Addon;