<?php
/* don't allow this page to be requested directly from browser */
if (!defined('QA_VERSION')) {
      header('Location: /');
      exit;
}

function qw_social_get_saved_hauth_session($hauthSession , $userid)
{
   return qa_db_read_one_value(qa_db_query_sub("SELECT ^userprofile.content AS name from  ^userprofile WHERE ^userprofile.title =$ AND ^userprofile.userid = # " , $hauthSession , $userid ), true);
}

function qw_save_social_posting_settings($data , $userid)
{
	require_once QA_INCLUDE_DIR.'qa-db-users.php';
    foreach ($data as $key => $value) {
   		 qa_db_user_profile_set($userid, $key, $value);
    }
}

function qw_get_social_posting_settings($all_keys , $userid)
{
   $values = qa_db_read_all_assoc(qa_db_query_sub("SELECT ^userprofile.title , ^userprofile.content from  ^userprofile WHERE ^userprofile.title in (#) AND ^userprofile.userid = # " , $all_keys , $userid ));
   $result_arr = array();
   foreach ($values as $value) {
   		$result_arr[$value['title']] = $value['content'] ;
   }
   return $result_arr;
}

function qw_get_user_social_post_status_for_event($preferences , $event )
{
    $post_to = array() ; 

    switch ($event) {
        case 'q_post':
            if (isset($preferences['qw_facebook_q_post']) && !!$preferences['qw_facebook_q_post'] && !!qa_opt('qw_enable_fb_posting') ) {
               $post_to[] = "Facebook" ;
            }
            if (isset($preferences['qw_twitter_q_post']) && !!$preferences['qw_twitter_q_post'] && !!qa_opt('qw_enable_twitter_posting') ) {
               $post_to[] = "Twitter" ;
            }
            break;
        case 'a_post':
            if (isset($preferences['qw_facebook_a_post']) && !!$preferences['qw_facebook_a_post'] && !!qa_opt('qw_enable_fb_posting') ) {
               $post_to[] = "Facebook" ;
            }
            if (isset($preferences['qw_twitter_a_post']) && !!$preferences['qw_twitter_a_post'] && !!qa_opt('qw_enable_twitter_posting') ) {
               $post_to[] = "Twitter" ;
            }
            break;
        case 'c_post':
            if (isset($preferences['qw_facebook_c_post']) && !!$preferences['qw_facebook_c_post'] && !!qa_opt('qw_enable_fb_posting') ) {
               $post_to[] = "Facebook" ;
            }
            if (isset($preferences['qw_twitter_c_post']) && !!$preferences['qw_twitter_c_post'] && !!qa_opt('qw_enable_twitter_posting') ) {
               $post_to[] = "Twitter" ;
            }
            break;
        default:
            break;
    }
    return $post_to ;
}

/**
 * Generates a dynamic script for users so that they can post to facebook with some ajax calls 
 * and no need of reloading web pages 
 * @param  string $app_id [facebook application id ]
 * @param  string $name   [name of the logged in user]
 * @param  string $url    [url to be used for message ]
 * @return string         [script to be printed below the button]
 */
function qw_generate_facebook_invite_script($app_id, $data , $no_script = true ) {
      if (!$app_id || !is_array($data)) {
            return "";
      }
      $name    = qw_extract_parameter_val($data , 'name') ;
      $url     = qw_extract_parameter_val($data , 'url') ;
      $message = qw_extract_parameter_val($data , 'message') ;

      if (!$message) {
        // if message is not set then set it with a default value 
        $message = qa_lang("qw_social_posting/facebook_invite_msg_default") ;
      }

      $message = strtr( $message , array('^name'=> $name , '^site_url' => $url ));
      $object  = "message:'$message' ," ;
      ob_start();
      if (!$no_script) echo "<script>" ;
      ?>
     qw_invite_facebook_friends(<?php echo $app_id ?> , {<?php echo $object ?>})
      <?php
      if (!$no_script) echo "</script>" ;
      $output = ob_get_clean();
      return $output;
} //end of qw_generate_facebook_invite_script



/**
 * generate wall post for a user 
 * @return [string] [script to be printed below the button for posting to wall ]
 */
function qw_generate_facebook_wall_post_script($app_id , $data , $no_script = true ){
     if (!$app_id || !is_array($data)) {
            return "";
      }
      $link        = qw_extract_parameter_val($data , 'link');
      $picture     = qw_extract_parameter_val($data , 'picture');
      $name        = qw_extract_parameter_val($data , 'name');
      $caption     = qw_extract_parameter_val($data , 'caption');
      $description = qw_extract_parameter_val($data , 'description');
      $message = qw_extract_parameter_val($data , 'message');
      $object      = "" ;

      if (!!$link) {
        $object .= "link: '" . $link . "' ," ;
      }
      if (!!$picture) {
        $object .= "picture: '" . $picture . "' ," ;
      }
      if (!!$name) {
        $object .= "name: '" . $name . "' ," ;
      }
      if (!!$caption) {
        $object .= "caption: '" . $caption . "' ," ;
      }
      if (!!$description) {
        $object .= "description: '" . $description . "' ," ;
      }
      
      if (!!$message) {
        $object .= "message: '" . $message . "' ," ;
      }

      ob_start();
      if (!$no_script) echo "<script>" ;
      ?>
            qw_post_to_facebook_wall(<?php echo $app_id ?> , {<?php echo $object ?>}); 
      <?php
      if (!$no_script) echo "</script>" ;
      $output = ob_get_clean();
      return $output;
}//qw_generate_facebook_wall_post_script

/**
 * Generate facebok link share button with the given parameters 
 * @param  string  $app_id     facebook application id 
 * @param  array   $data       array of data 
 * @param  boolean $no_script  pass false if the script tag is needed 
 * @return string              reutrns the function calling signature 
 */
function qw_generate_facebook_link_share_script($app_id , $data , $no_script = true){
     if (!$app_id) {
            return "";
      }

      $to   = qw_extract_parameter_val($data , 'to');
      $link   = qw_extract_parameter_val($data , 'link');

      $object = "" ;

      if (!!$to) {
        $object .= "to: '" . $to . "' ," ;
      }
      if (!!$link) {
        $object .= "link: '" . $link . "' ," ;
      }
     
      ob_start();
      if (!$no_script) echo "<script>" ;
      ?>
            qw_share_link_to_facebook(<?php echo $app_id ?> ,{<?php echo $object ?>});
      <?php
      if (!$no_script) echo "</script>" ;
      $output = ob_get_clean();
      return $output;
}//qw_generate_facebook_link_share_script

/**
 * Generate facebok login button with the given parameters 
 * @param  string  $app_id     facebook application id 
 * @param  array   $data       array of data (generally here is empty )
 * @param  boolean $no_script  pass false if the script tag is needed 
 * @return string              reutrns the function calling signature 
 */
function qw_generate_facebook_login_script($app_id , $data = array() , $no_script = true)
{
    if (!$app_id) {
        return "";
    }

    ob_start();
    if (!$no_script) echo "<script>" ;
    ?>
          qw_login_to_facebook(<?php echo $app_id ?>);
    <?php
    if (!$no_script) echo "</script>" ;
    $output = ob_get_clean();
    return $output;

}

/**
 * extracts the value from the associative array according to the name passed and empty string if the value is not set 
 * @param  [array]  $param
 * @param  [string] $name  
 * @return [type]   $value 
 */
function qw_extract_parameter_val($param , $name )
{
  return isset($param[$name]) ? $param[$name] : "" ;
}

function qw_update_facebook_status($app_id , $data = array() , $no_script = true)
{
  
}

function qw_get_fb_invite_button(){
	if (!!qa_opt("facebook_app_id")) {
    $message = qa_opt("qw_fb_invite_message") ;
    if (!$message) {
          $message = qa_lang_html('qw_social_posting/fb_invite_message_default');
    }
    $message = strtr($message , array('{site_url}' => QW_BASE_URL ));

		$on_click_event = qw_generate_facebook_invite_script(qa_opt("facebook_app_id"), array('url' => QW_BASE_URL , 'message' => $message))  ;
		$button = '<button class="btn btn-facebook" onclick="'.$on_click_event.'">'.qa_lang_html('qw_social_posting/invite_friends').'</button>' ;
		return $button ;
	}
}

function qw_get_fb_msg_button($link, $label){
	if (!!qa_opt("facebook_app_id") && !!$link) { /*generate this only if the facebook appid and link is set*/
		$on_click_event = qw_generate_facebook_link_share_script(qa_opt("facebook_app_id"), array('link' => $link))  ;
		$button = '<button class="btn btn-facebook" onclick="'.$on_click_event.'">'.$label.'</button>' ;
		return $button;
	}
}
