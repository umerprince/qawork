<?php


function get_base_url()
{
	/* First we need to get the protocol the website is using */
	$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? 'https://' : 'http://';

	/* returns /myproject/index.php */
	if(QA_URL_FORMAT_NEAT == 0 || strpos($_SERVER['PHP_SELF'],'/index.php/') !== false):
		$path = strstr($_SERVER['PHP_SELF'], '/index', true);
		$directory = $path;
	else:
		$path = $_SERVER['PHP_SELF'];
		$path_parts = pathinfo($path);
		$directory = $path_parts['dirname'];
		$directory = ($directory == "/") ? "" : $directory;
	endif;       
		
		$directory = ($directory == "\\") ? "" : $directory;
		
	/* Returns localhost OR mysite.com */
	$host = $_SERVER['HTTP_HOST'];

	return $protocol . $host . $directory;
}	

function cs_is_home(){
	if(qa_request() == '')
		return true;
		
	return false;
}

function cs_is_user(){
	$request = qa_request_parts(0);
	if( $request[0] == 'user')
		return true;
		
	return false;
}

function cs_is_state_edit(){
	$request = cs_request_text('state');
	if( $request == 'edit')
		return true;
		
	return false;
}

function cs_read_addons(){
	$addons = array();
	//load files from addons folder
	$files=glob(CS_CONTROL_DIR.'/addons/*/addon.php');
	//print_r($files);
	foreach ($files as $file){
		$data = cs_get_addon_data($file);
		$data['folder'] = basename(dirname($file));
		$data['file'] = basename($file);
		$addons[] = $data;
	}
	return $addons;
}
function cs_read_addons_ajax(){
	$addons = array();
	//load files from addons folder
	$files=glob(CS_CONTROL_DIR.'/addons/*/ajax.php');
	//print_r($files);
	foreach ($files as $file){
		$data['folder'] = basename(dirname($file));
		$data['file'] = basename($file);
		$addons[] = $data;
	}
	return $addons;
}

function cs_load_addons(){
	$addons = cs_read_addons();
	if(!empty($addons))
		foreach($addons as $addon){
			include_once CS_CONTROL_DIR.'/addons/'.$addon['folder'].'/'.$addon['file'];
		}
}
function cs_load_addons_ajax(){
	$addons = cs_read_addons_ajax();
	if(!empty($addons))
		foreach($addons as $addon){			
			require_once CS_CONTROL_DIR.'/addons/'.$addon['folder'].'/'.$addon['file'];			
		}
}


function cs_get_addon_data( $plugin_file) {
	$plugin_data = cs_get_file_data( $plugin_file);

	return $plugin_data;
}

function cs_get_file_data( $file) {
	// We don't need to write to the file, so just open for reading.
	$fp = fopen( $file, 'r' );

	// Pull only the first 8kiB of the file in.
	$file_data = fread( $fp, 1000 );

	// PHP will close file handle, but we are good citizens.
	fclose( $fp );

	$metadata=cs_addon_metadata($file_data, array(
		'theme_name' => 'Theme Name',
		'theme_version' => 'Theme Version',
		'class' => 'Class',
		'description' => 'Description',
		'version' => 'Version',
		'author' => 'Author',
		'author_uri' => 'Author URI'
	));

	return $metadata;
}

function cs_addon_metadata($contents, $fields){
	$metadata=array();

	foreach ($fields as $key => $field)
		if (preg_match('/'.str_replace(' ', '[ \t]*', preg_quote($field, '/')).':[ \t]*([^\n\f]*)[\n\f]/i', $contents, $matches))
			$metadata[$key]=trim($matches[1]);
	
	return $metadata;
}

function get_all_widgets()
{		
	$widgets = qa_db_read_all_assoc(qa_db_query_sub('SELECT * FROM ^ra_widgets ORDER BY widget_order'));
	foreach($widgets as $k => $w){
		$param = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $w['param']);
		$param = unserialize($param);
		$widgets[$k]['param'] = $param;
	}
	return $widgets;

}

function get_widgets_by_position($position)
{		
	$widgets = qa_db_read_all_assoc(qa_db_query_sub('SELECT * FROM ^ra_widgets WHERE position = $ ORDER BY widget_order', $position));
	foreach($widgets as $k => $w){
		$param = unserialize($w['param']);
		$widgets[$k]['param'] = $param;
	}
	return $widgets;

}
function widget_opt($name, $position=false, $order = false, $param = false, $id= false)
{		
	if($position && $param){
		return widget_opt_update($name, $position, $order, $param, $id);		
	}else{
		qa_db_read_one_value(qa_db_query_sub('SELECT * FROM ^ra_widgets WHERE name = $',$name ), true);		
	}
}


function widget_opt_update($name, $position, $order, $param, $id = false){

	if($id){
		qa_db_query_sub(
			'UPDATE ^ra_widgets SET position = $, widget_order = #, param = $ WHERE id=#',
			$position, $order, $param, $id
		);
		return $id;
	}else{
		qa_db_query_sub(
			'INSERT ^ra_widgets (name, position, widget_order, param) VALUES ($, $, #, $)',
			$name, $position, $order, $param
		);
		return qa_db_last_insert_id();
	}
}
function widget_opt_delete($id ){
	qa_db_query_sub('DELETE FROM ^ra_widgets WHERE id=#', $id);
}

function cs_user_data($handle){
	$userid = qa_handle_to_userid($handle);
	$identifier=QA_FINAL_EXTERNAL_USERS ? $userid : $handle;
	$user = array();
	if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
		$u_rank = qa_db_select_with_pending(qa_db_user_rank_selectspec($userid,true));
		$u_points = qa_db_select_with_pending(qa_db_user_points_selectspec($userid,true));
		
		$userinfo = array();
		$user_info = get_userdata( $userid );
		$userinfo['userid'] = $userid;
		$userinfo['handle'] = $handle;
		$userinfo['email'] = $user_info->user_email;
		
		$user[0] = $userinfo;
		$user[1]['rank'] = $u_rank;
		$user[2] = $u_points;
		$user = ($user[0]+ $user[1]+ $user[2]);
	}else{
		$user['account'] = qa_db_select_with_pending( qa_db_user_account_selectspec($userid, true) );
		$user['rank'] = qa_db_select_with_pending( qa_db_user_rank_selectspec($handle) );
		$user['points'] = qa_db_select_with_pending( qa_db_user_points_selectspec($identifier) );
		
		$user['followers'] = qa_db_read_one_value( qa_db_query_sub('SELECT count(*) FROM ^userfavorites WHERE ^userfavorites.entityid = # and ^userfavorites.entitytype = "U" ', $userid), true );
		
		$user['following'] = qa_db_read_one_value( qa_db_query_sub('SELECT count(*) FROM ^userfavorites WHERE ^userfavorites.userid = # and ^userfavorites.entitytype = "U" ', $userid), true );
	}

	return $user;
}	

function cs_get_avatar($handle, $size = 40, $html =true){
	$userid = qa_handle_to_userid($handle);
	if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
		$img_html = get_avatar( qa_get_user_email($userid), $size);
	}else if(QA_FINAL_EXTERNAL_USERS){
		$img_html = qa_get_external_avatar_html($userid, $size, false);
	}else{
		if (!isset($handle)){
			if (qa_opt('avatar_allow_gravatar'))
				$img_html = qa_get_gravatar_html(qa_get_user_email($userid), $size);
			else if ( qa_opt('avatar_allow_upload') && qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid')) )
				$img_html = qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), $size);
			else
				$img_html = '';
		}else{
			$f = cs_user_data($handle);
			if(empty($f['account']['avatarblobid'])){
				if (qa_opt('avatar_allow_gravatar'))
					$img_html = qa_get_gravatar_html(qa_get_user_email($userid), $size);
				else if ( qa_opt('avatar_allow_upload') && qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid')) )
					$img_html = qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), $size);
				else
					$img_html = '';
			} else
				$img_html = qa_get_user_avatar_html($f['account']['flags'], $f['account']['email'], $handle, $f['account']['avatarblobid'], $size, $size, $size, true);
		}
	}
	if (empty($img_html))
		return;
		
	preg_match( '@src="([^"]+)"@' , $img_html , $match );
	if($html)
		return '<a href="'.qa_path_html('user/'.$handle).'">'.(!defined('QA_WORDPRESS_INTEGRATE_PATH') ?  '<img src="'.$match[1].'" />':$img_html).'</a>';		
	elseif(isset($match[1]))
		return $match[1];
}
function cs_get_post_avatar($post, $size = 40, $html=false){
	if(!isset($post['raw'])){
		$post['raw']['userid'] 			= $post['userid'];
		$post['raw']['flags'] 			= $post['flags'];
		$post['raw']['email'] 			= $post['email'];
		$post['raw']['handle'] 			= $post['handle'];
		$post['raw']['avatarblobid'] 	= $post['avatarblobid'];
		$post['raw']['avatarwidth'] 	= $post['avatarwidth'];
		$post['raw']['avatarheight'] 	= $post['avatarheight'];
	}

	if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
		$avatar = get_avatar( qa_get_user_email($userid), $size);
	}if (QA_FINAL_EXTERNAL_USERS)
		$avatar = qa_get_external_avatar_html($post['raw']['userid'], $size, false);
	else
		$avatar = qa_get_user_avatar_html($post['raw']['flags'], $post['raw']['email'], $post['raw']['handle'],
			$post['raw']['avatarblobid'], $post['raw']['avatarwidth'], $post['raw']['avatarheight'], $size);
	if($html)
		return '<div class="avatar" data-id="'.$post['raw']['userid'].'" data-handle="'.$post['raw']['handle'].'">'.$avatar.'</div>';
	
	return $avatar;
}

function cs_post_type($id){
	$result = qa_db_read_one_value(qa_db_query_sub('SELECT type FROM ^posts WHERE postid=#', $id ),true);
	return $result;
}

function cs_post_status($item){
	$notice = '';
	if (@$item['answer_selected'] || @$item['raw']['selchildid']){	
		$notice =   '<span class="post-status selected ra-tip" title="'.qa_lang_html('cleanstrap/marked_as_solved').'">'.qa_lang_html('cleanstrap/solved').'</span>' ;

	}elseif(@$item['raw']['closedbyid']){
		$type = cs_post_type(@$item['raw']['closedbyid']);
		if($type == 'Q'){
			$notice =   '<span class="post-status duplicate ra-tip" title="'.qa_lang_html('cleanstrap/marked_as_duplicate').'">'.qa_lang_html('cleanstrap/duplicate').'</span>' ;		
		}else{
			$notice =   '<span class="post-status closed ra-tip" title="'.qa_lang_html('cleanstrap/marked_as_closed').'">'.qa_lang_html('cleanstrap/closed').'</span>' ;
		}
	}else{
		$notice =   '<span class="post-status open ra-tip" title="'.qa_lang_html('cleanstrap/marked_as_open').'">'.qa_lang_html('cleanstrap/open').'</span>' ;
	}
	return $notice;
}
function cs_get_post_status($item, $description = false){
	// this will return question status whether question is open, closed, duplicate or solved
	
	if (@$item['answer_selected'] || @$item['raw']['selchildid']){	
		$status =   'solved' ;
	}elseif(@$item['raw']['closedbyid']){
		$type = cs_post_type(@$item['raw']['closedbyid']);
		if($type == 'Q')
			$status =   'duplicate' ;	
		else
			$status =   'closed' ;	
	}else{
		$status =   'open' ;	
	}
	return $status;
}
function cs_get_excerpt($id){
	$result = qa_db_read_one_value(qa_db_query_sub('SELECT content FROM ^posts WHERE postid=#', $id ),true);
	return strip_tags($result);
}
function cs_truncate($string, $limit, $pad="...") {
	if(strlen($string) <= $limit) 
		return $string; 
	else{ 
		//preg_match('/^.{1,'.$limit.'}\b/s', $string, $match);
		//return $match[0].$pad;
		$text = $string.' ';
		$text = substr($text,0,$limit);
		$text = substr($text,0,strrpos($text,' '));
		return $text.$pad;
	} 
}
		
function cs_user_profile($handle, $field =NULL){
	$userid = qa_handle_to_userid($handle);
	if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
		return get_user_meta( $userid );
	}else{
		$query = qa_db_select_with_pending(qa_db_user_profile_selectspec($userid, true));
		
		if(!$field) return $query;
		if (isset($query[$field]))
			return $query[$field];
	}
	
	return false;
}	

function cs_user_badge($handle) {
	if(qa_opt('badge_active')){
	$userids = qa_handles_to_userids(array($handle));
	$userid = $userids[$handle];

	
	// displays small badge widget, suitable for meta
	
	$result = qa_db_read_all_values(
		qa_db_query_sub(
			'SELECT badge_slug FROM ^userbadges WHERE user_id=#',
			$userid
		)
	);

	if(count($result) == 0) return;
	
	$badges = qa_get_badge_list();
	foreach($result as $slug) {
		$bcount[$badges[$slug]['type']] = isset($bcount[$badges[$slug]['type']])?$bcount[$badges[$slug]['type']]+1:1; 
	}
	$output='<ul class="user-badge clearfix">';
	for($x = 2; $x >= 0; $x--) {
		if(!isset($bcount[$x])) continue;
		$count = $bcount[$x];
		if($count == 0) continue;

		$type = qa_get_badge_type($x);
		$types = $type['slug'];
		$typed = $type['name'];

		$output.='<li class="badge-medal '.$types.'"><i class="icon-badge" title="'.$count.' '.$typed.'"></i><span class="badge-pointer badge-'.$types.'-count" title="'.$count.' '.$typed.'"> '.$count.'</span></li>';
	}
	$output = substr($output,0,-1);  // lazy remove space
	$output.='</ul>';
	return($output);
	}
}
function cs_name($handle){
	if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
		$userdata = cs_user_profile($handle, 'name');
		$name = $userdata['nickname'][0];
	}else
		$name = cs_user_profile($handle, 'name');
	return strlen($name) ? $name : $handle;
}



function cs_post_link($id){
	$type = mysql_result(qa_db_query_sub('SELECT type FROM ^posts WHERE postid = "'.$id.'"'), 0);
	
	if($type == 'A')
		$id = mysql_result(qa_db_query_sub('SELECT parentid FROM ^posts WHERE postid = "'.$id.'"'),0);
	
	$post = qa_db_query_sub('SELECT title FROM ^posts WHERE postid = "'.$id.'"');
	return qa_q_path_html($id, mysql_result($post,0));
}	

function cs_tag_list($limit = 20){
	$populartags=qa_db_single_select(qa_db_popular_tags_selectspec(0, $limit));
			
	$i= 1;
	foreach ($populartags as $tag => $count) {							
		echo '<li><a class="icon-tag" href="'.qa_path_html('tag/'.$tag).'">'.qa_html($tag).'<span>'.filter_var($count, FILTER_SANITIZE_NUMBER_INT).'</span></a></li>';
	}
}

function cs_url_grabber($str) {
	preg_match_all(
	  '#<a\s
		(?:(?= [^>]* href="   (?P<href>  [^"]*) ")|)
		(?:(?= [^>]* title="  (?P<title> [^"]*) ")|)
		(?:(?= [^>]* target=" (?P<target>[^"]*) ")|)
		[^>]*>
		(?P<text>[^<]*)
		</a>
	  #xi',
	  $str,
	  $matches,
	  PREG_SET_ORDER
	);
	

	foreach($matches as $match) {
	 return '<a href="'.$match['href'].'" title="'.$match['title'].'">'.$match['text'].'</a>';
	}	
}


function cs_widget_position(){
	return cs_apply_filter('widget_positions', array());
}



function cs_get_template_array(){
	return cs_apply_filter('template_array', array());

}

function cs_social_icons(){
	$icon =  array(
		'icon-social-facebook' 		=> 'Facebook',
		'icon-social-twitter' 		=> 'Twitter',
		'icon-social-googleplus' 	=> 'Google',
		'icon-social-pinterest' 	=> 'Pinterest',
		'icon-social-linkedin' 		=> 'Linkedin',
		'icon-social-github' 		=> 'Github',
		'icon-social-stumbleupon' 	=> 'Stumbleupon',
	);
	
	return cs_apply_filter('social_icon', $icon);
}

function is_featured($postid){
	require_once QA_INCLUDE_DIR.'qa-db-metas.php';
	return (bool)qa_db_postmeta_get($postid, 'featured_question');
}
function get_featured_thumb($postid){
	require_once QA_INCLUDE_DIR.'qa-db-metas.php';
	$img =  qa_db_postmeta_get($postid, 'featured_image');

	if (!empty($img)){
		$thumb_img = preg_replace('/(\.[^.]+)$/', sprintf('%s$1', '_s'), $img);
		return '<img class="featured-image" src="'.Q_THEME_URL . '/uploads/' . $thumb_img .'" />';
	}
	return false;
}
function get_featured_image($postid){
	require_once QA_INCLUDE_DIR.'qa-db-metas.php';
	$img =  qa_db_postmeta_get($postid, 'featured_image');

	if (!empty($img))
		return '<img class="image-preview" id="image-preview" src="'.Q_THEME_URL . '/uploads/' . $img.'" />';
		
	return false;
}
function cs_cat_path($categorybackpath){
	return qa_path_html(implode('/', array_reverse(explode('/', $categorybackpath))));
}

/**
 * multi_array_key_exists function.
 *
 * @param mixed $needle The key you want to check for
 * @param mixed $haystack The array you want to search
 * @return bool
 */
function multi_array_key_exists( $needle, $haystack ) {
	if(isset($haystack) && is_array($haystack))
    foreach ( $haystack as $key => $value ) :

        if ( $needle == $key )
            return true;
       
        if ( is_array( $value ) ) :
             if ( multi_array_key_exists( $needle, $value ) == true )
                return true;
             else
                 continue;
        endif;
       
    endforeach;
   
    return false;
}
function make_array_utf8( $arr ) {
    foreach ( $arr as $key => $value )
        if ( is_array( $value ) ) 
            $arr[$key] = make_array_utf8( $value );
        else
			$arr[$key] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($value));
	return $arr;
}

function cs_get_site_cache(){
	global $cache;
	$cache = json_decode( qa_db_cache_get('cs_cache', 0),true );
}

function cs_get_cache_popular_tags($to_show){
	global $cache;
	$age = 3600; // 1 hour

	if (isset($cache['tags'])){
		if ( ((int)$cache['tags']['age'] + $age) > time()) {
			$populartags = $cache['tags'];
			unset($populartags['age']);
			return $populartags;
		}
	}
	$populartags=qa_db_single_select(qa_db_popular_tags_selectspec(0, (!empty($to_show) ? $to_show : 20)));
	$cache['tags'] =  $populartags;
	$cache['tags']['age'] = time();
	$cache['changed'] = true;	
	return $populartags;
}


function cs_get_cache($query,$age = 10){
	global $cache;

	$funcargs=func_get_args();
	unset($funcargs[1]);
	$query =  qa_db_apply_sub($query, array_slice($funcargs, 1));
	$hash = md5($query);
	if (isset($cache[$hash])){
		if ( ((int)$cache[$hash]['age'] + $age) > time()) {
			$result = $cache[$hash]['result'];
			return $result;
		}
	}
	$result = qa_db_read_all_assoc( qa_db_query_raw($query) );
	$cache[$hash]['result'] =  $result;
	$cache[$hash]['age'] = time();
	$cache['changed'] = true;
	return $result ;	
}
function cs_set_site_cache(){
	global $cache;
	if (@$cache['changed']){
		unset($cache['changed']);
		$cache = make_array_utf8($cache);
		qa_db_cache_set('cs_cache', 0, json_encode($cache) );
	}
}

function cs_ajax_user_popover(){
	
	$handle_id= qa_post_text('handle');
	$handle= qa_post_text('handle');
	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	if(isset($handle)){
		$userid = qa_handle_to_userid($handle);
		//$badges = ra_user_badge($handle);
		
		if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
			$userid = qa_handle_to_userid($handle);
			$cover = get_user_meta( $userid, 'cover' );
			$cover = $cover[0];
		}else{
			$data = cs_user_data($handle);
			$profile = cs_user_profile($handle);
		}

		?>
		<div id="<?php echo $userid;?>_popover" class="user-popover">
			<div class="counts clearfix"<?php echo !empty($profile['cover']) ? ' style="background-image:url('.cs_upload_url().'/'.$profile['cover'].')"' : ''; ?>>
				<div class="bg-opacity clearfix">
					<div class="points">
						<?php echo '<span>'.$data['points']['points'] .'</span>Points'; ?>
					</div>
					<div class="qcount">
						<?php echo '<span>'.$data['points']['qposts'] .'</span>Questions'; ?>
					</div>
					<div class="acount">
						<?php echo '<span>'.$data['points']['aposts'] .'</span>Answers'; ?>
					</div>
					<div class="ccount">
						<?php echo '<span>'.$data['points']['cposts'] .'</span>Comments'; ?>
					</div>
				</div>
			</div>
			<div class="bottom">	
				<div class="avatar pull-left"><?php echo cs_get_post_avatar($data['account'], 30); ?></div>
				<span class="name"><?php echo cs_name($handle); ?></span>				
				<span class="level"><?php echo qa_user_level_string($data['account']['level']); ?></span>				
			</div>
		</div>	
		<?php
	}
	die();
}


function cs_ago($time)
{
   $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
   $lengths = array("60","60","24","7","4.35","12","10");

   $now = time();

       $difference     = $now - $time;
       $tense         = "ago";

   for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
       $difference /= $lengths[$j];
   }

   $difference = round($difference);

   if($difference != 1) {
       $periods[$j].= "s";
   }

   return "$difference $periods[$j] 'ago' ";
}

function stripslashes2($string) {
	str_replace('\\', '', $string);
    return $string;
}

function cs_count_followers($identifier, $id = false){
	if(!$id)
		$identifier= qa_handle_to_userid($identifier);
		
	return qa_db_read_one_value(qa_db_query_sub('SELECT count(*) FROM ^userfavorites WHERE ^userfavorites.entityid = # and ^userfavorites.entitytype = "U" ', $identifier));	
}

function cs_count_following($identifier, $id = false){
	if(!$id)
	 $identifier = qa_handle_to_userid($identifier);
	 
	return qa_db_read_one_value(qa_db_query_sub('SELECT count(*) FROM ^userfavorites WHERE ^userfavorites.userid = # and ^userfavorites.entitytype = "U" ', $identifier));	
}

function cs_followers_list($handle, $size = 40, $limit = 10, $order_by = 'rand'){
	$userid = qa_handle_to_userid($handle);
	
	if( $order_by == 'rand')
		$order_by = 'ORDER BY RAND()';
	
	$followers = qa_db_read_all_values(qa_db_query_sub('SELECT ^users.handle FROM ^userfavorites, ^users  WHERE (^userfavorites.userid = ^users.userid and ^userfavorites.entityid = #) and ^userfavorites.entitytype = "U" ORDER BY RAND() LIMIT #', $userid,  (int)$limit));	

	
	if(count($followers)){
		$output = '<div class="user-followers-inner">';
		$output .= '<ul class="user-followers clearfix">';
		foreach($followers as $user){
			$id = qa_handle_to_userid($user);
			$output .= '<li><div class="avatar" data-handle="'.$user.'" data-id="'.$id.'"><a href="'.qa_path_html('user/'.$user).'"><img src="'.cs_get_avatar($user, $size, false).'" /></a></div></li>';
		}
		$count = cs_user_followers_count($userid);
		
		if(($count - $limit) > 100)
			$count = '99+';
		else
			$count = ($count - $limit);
		
		if($count > 0)		
			$output .= '<li class="total-followers"><a href="'.qa_path_html('followers').'" style="height:'.$size.'px;width:'.$size.'px;"><span>'.$count.'</span></a></li>';
			
		$output .= '</ul>';
		$output .= '</div>';
		return $output;
	}
	return;
}
function cs_following_list($handle, $size = 40, $limit = 10, $order_by = 'rand'){
	$userid = qa_handle_to_userid($handle);
	
	if( $order_by == 'rand')
		$order_by = 'ORDER BY RAND()';
	
	$followers = qa_db_read_all_values(qa_db_query_sub('SELECT ^users.handle FROM ^userfavorites INNER JOIN ^users ON ^userfavorites.entityid = ^users.userid  WHERE ^userfavorites.userid = # and ^userfavorites.entitytype = "U" ORDER BY RAND() LIMIT #', $userid,  (int)$limit));	


	if(count($followers)){
		$output = '<div class="user-followers-inner">';
		$output .= '<ul class="user-followers clearfix">';
		foreach($followers as $user){
			$id = qa_handle_to_userid($user);
			$output .= '<li><div class="avatar" data-handle="'.$user.'" data-id="'.$id.'"><a href="'.qa_path_html('user/'.$user).'"><img src="'.cs_get_avatar($user, $size, false).'" /></a></div></li>';
		}
		$count = cs_count_following($userid);
		
		if(($count - $limit) > 100)
			$count = '99+';
		else
			$count = ($count - $limit);
		
		if($count > 0)		
			$output .= '<li class="total-followers"><a href="'.qa_path_html('followers').'" style="height:'.$size.'px;width:'.$size.'px;"><span>'.$count.'</span></a></li>';
			
		$output .= '</ul>';
		$output .= '</div>';
		return $output;
	}
	return;
}
function cs_user_followers_count($userid){
	$count =  qa_db_read_one_value(qa_db_query_sub('SELECT count(userid) FROM ^userfavorites  WHERE  entityid = # and entitytype = "U"', $userid), true);
	return $count;
}

function handle_url($handle){
	return qa_path_html('user/'.$handle);
}


function cs_event_hook($event, $value = NULL, $callback = NULL, $check = false, $filter = false, $order = 100){
    static $events;
	
    // Adding or removing a callback?
    if($callback !== NULL){
        if($callback){
            $events[$event][$order][] = $callback;
        }else{
            unset($events[$event]);
        }
    }elseif($filter) // filter
    {	
		if(!isset($events[$event]) )
			return $value[1];
			
		ksort($events[$event]);
        foreach($events[$event] as $order){		
			foreach($order as $function){
				$filtered = call_user_func_array($function, $value);
				
				if(isset($filtered))
					$value[1] = $filtered;
				else
					$value[1] = $value[1];
			}			
        }
	
        return $value[1];
    }
	elseif($check && isset($events[$event])) // check if hook exist
    {
		ksort($events[$event]);
        foreach($events[$event] as $key => $order)
        {
			
			foreach($order as $function){
				if(is_array($function))
					return method_exists($function[0], $function[1] );
				return function_exists($function);
			}	
        }        
    }
    elseif(isset($events[$event])) // Fire do_action
    {				
		ksort($events[$event]);
        foreach($events[$event] as $order){
			ob_start();
			foreach($order as $function){
				
				call_user_func_array($function, $value);				
			}
			$output = ob_get_clean();
        }
        return $output;
    }
	return false;
}

function cs_apply_filter(){
	$args = func_get_args();
	unset($args[0]);
	return cs_event_hook(func_get_arg(0), $args, NULL, false, true);
}
function cs_do_action(){
	$args = func_get_args();
	if(isset($args))
		unset($args[0]);

	return cs_event_hook(func_get_arg(0), $args, NULL);
}

function cs_add_filter(){
	$args = func_get_args();
	
	if(isset($args))
		$order = (count($args) > 2) ? end($args) : 100;
		
	cs_event_hook(func_get_arg(0), NULL, (isset($args[1]) ? $args[1] : ''), false, false, (isset($order) ? $order : 100));
}

function cs_add_action(){
	$args = func_get_args();
	
	if(isset($args))
		$order = (count($args) > 2) ? end($args) : 100;
		
	cs_event_hook(func_get_arg(0), NULL, (isset($args[1]) ? $args[1] : ''), false, false, (isset($order) ? $order : 100));
}

// an Alice for cs_event_hook 
function cs_hook_exist($event){
	return cs_event_hook($event, null, null, true);
}


function cs_combine_assets($assets, $css = true){
	$styles = '';
	$host_name = $_SERVER['HTTP_HOST'];
	if(is_array($assets)){

		foreach ($assets as $a){
			$parse = parse_url($a);
			
			if($parse['host'] == $host_name){
				$path = $_SERVER["DOCUMENT_ROOT"].ltrim ($parse['path'], '/');
				$content = file_get_contents($path);
				if($css)
					$styles .= cs_compress_css($content);
				else
					$styles .= cs_compress_js($content);
			}
		}
	}

	return $styles;
}



function cs_compress_css($content) {

	// Normalize whitespace
	$content = preg_replace( '/\s+/', ' ', $content );

	// Remove comment blocks, everything between /* and */, unless
	// preserved with /*! ... */
	$content = preg_replace( '/\/\*[^\!](.*?)\*\//', '', $content );

	// Remove ; before }
	$content = preg_replace( '/;(?=\s*})/', '', $content );

	// Remove space after , : ; { } */ >
	$content = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $content );

	// Remove space before , ; { } ( ) >
	$content = preg_replace( '/ (,|;|\{|}|\(|\)|>)/', '$1', $content );

	// Strips leading 0 on decimal values (converts 0.5px into .5px)
	$content = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $content );

	// Strips units if value is 0 (converts 0px to 0)
	$content = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $content );

	// Converts all zeros value into short-hand
	$content = preg_replace( '/0 0 0 0/', '0', $content );

	// Shortern 6-character hex color codes to 3-character where possible
	$content = preg_replace( '/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $content );

	return trim( $content );
}

function cs_compress_js( $content ) {
	require_once CS_CONTROL_DIR.'/inc/jsmin.php';

	$content = JSMin::minify($content);
	return $content;
}

function cs_update_tags_meta($tag, $title, $content){

	qa_db_query_sub(
		'REPLACE ^tagmetas (tag, title, content) VALUES ($, $, $)',
		$tag, $title, $content
	);

	return $content;
	
}
function cs_what_icon($what){
	$icon = '';
	switch($what){
		case 'closed' :
			$icon = 'icon-times';
			break;
		case 'asked' :
			$icon = 'icon-question';
			break;
		case 'selected' :
			$icon = 'icon-tick';
			break;
		case 'edited' :
			$icon = 'icon-edit';
			break;
			
	}
	if(cs_hook_exist('cs_what_icon'))
		return cs_apply_filter('cs_what_icon', $what);
	else
		return $icon;
}


/*
  This function sets and invokes the timeout
  $time_out => always a positive value in seconds
 */

function cs_scheduler($function_name, $time_out = NULL, $params = NULL) {
 	  require_once QA_INCLUDE_DIR . 'qa-app-options.php';
      require_once QA_INCLUDE_DIR . 'qa-db.php';
      //first check $time_out == 0 , then check timeout and set the current rundate 
      if (!$function_name) {
            return;
      }

	  $time_out_opt_name      =  $function_name . '_time_out';
	  $last_run_date_opt_name =  $function_name . '_last_run_date';

      if ($time_out === NULL || !$time_out) {
            //the call is for invoke the timeout function 
            $time_out_val = qa_opt($time_out_opt_name);
            if (!!$time_out_val && is_numeric($time_out_val) && $time_out_val > 0) { //check if the $time_out_value for this function is set in the options or not 
                  $date_format = "d/m/Y H:i:s";
                  $last_run_date = qa_opt($last_run_date_opt_name);
                  if (!$last_run_date) {
                        // if the lastrun_date is not set then set with an default value 
                        $last_run_date = "01/01/2014 01:00:00";
                  }
                  $event_interval = "PT" . $time_out_val . "S";
                  // $last_run_date = new DateTime($last_run_date);
                  $last_run_date = date_create_from_format($date_format, $last_run_date);
                  $last_run_date->add(new DateInterval($event_interval));
                  $probable_run_date = $last_run_date;
                  //get the current time 
                  $current_time = new DateTime("now");

                  //if current time is grater than last_rundate + interval then 
                  if ($current_time > $probable_run_date) {
                        // call the callback function now 
                        $value = call_user_func($function_name, $params);
                        // update the last rundate 
                        qa_opt($last_run_date_opt_name, $current_time->format($date_format));
                        return $value;
                  }
            } else {
                  //this executes if the timeout is not set but it is invoked for the first time 
                  // then set with default timeout 
                  $time_out = 15 * 60; //15 mins 
                  qa_opt($time_out_opt_name, $time_out);
            }
      } else {
            //it is to set the timeout 
            if (!(is_numeric($time_out) && $time_out > 0 )) {
                  // if the $time_out is not a numeric value or not grater than 0 , then return 
                  return;
                  cs_log("function came here ");
            }
            qa_opt($time_out_opt_name, $time_out);
      }

      //first check the timeout for the function name 
}

function cs_check_scheduler($function_name, $params = NULL) {
      if ($params !== NULL) {
            cs_scheduler($function_name, NULL, $params);
      } else {
            cs_scheduler($function_name);
      }
}

function cs_scheduler_set($function_name, $time_out = NULL) {
      if ($time_out !== NULL && is_numeric($time_out) && $time_out > 0) {
            cs_scheduler($function_name, $time_out);
      }
}

// functions for testing of the cs_scheduler_set
function call_me() {
      $current_time = new DateTime("now");
      $date_format = "d/m/Y H:i:s";
}

function call_this_method() {
      // this way we can set the scheduler 
      // cs_scheduler_set('call_me', 20);
	  // execute the scheduler 
      cs_check_scheduler('call_me');
}

function cs_log($string) {
     // if (qa_opt('event_logger_to_files')) {
            //   Open, lock, write, unlock, close (to prevent interference between multiple writes)
            $directory = CS_CONTROL_DIR.'/logs/';

            if (substr($directory, -1) != '/') $directory.='/';

            $log_file_name = $directory . 'cs-log-' . date('Y\-m\-d') . '.txt';

            $log_file_exists = file_exists($log_file_name);

            $log_file = @fopen($log_file_name, 'a');
            if (is_resource($log_file) && (!!$log_file_exists)) {
                  if (flock($log_file, LOCK_EX)) {
                        fwrite($log_file, $string . PHP_EOL);
                        flock($log_file, LOCK_UN);
                  }
            }
            @fclose($log_file);
      //}
}

function cs_event_log_row_parser( $row ){
            $result = preg_split('/\t/', $row) ;
            $param = array();
            $embeded_arrays = array();

            foreach ( $result as $value ) {
                  $arr_elem = explode("=", $value ) ;
                  $param[$arr_elem[0]] = $arr_elem[1] ;
                  if (preg_match("/array(.)/", $arr_elem[1])) {
                       $embeded_arrays[] = $arr_elem[0]; 
                  }
            }
            $unset_keys = array();
            foreach ($embeded_arrays as $embeded_array) {
                  $param[$embeded_array] = array() ; 
                  foreach ($param as $key => $value) {
                        if (preg_match("/".$embeded_array."_./", $key)) {
                        	  $str = preg_split("/".$embeded_array."_/", $key ) ;
                              $new_key = $str[1] ;
                              $param[$embeded_array][$new_key] = $value ;
                              $unset_keys[] = $key ;
                        }
                  }
            }
            foreach ($unset_keys as $key) {
                  unset($param[$key]);
            }
            return $param ; 
}
//just a helper methos for Testing
function cs_event_log_reader()
{     
      return qa_db_read_one_value(qa_db_query_sub("SELECT ^eventlog.params from  ^eventlog WHERE ^eventlog.datetime = $ ", '2014-05-10 22:55:08'), true);
}

function cs_is_internal_link($link){
	$link_host = parse_url($link, PHP_URL_HOST);
	if( $link_host == $_SERVER['HTTP_HOST'])
		return true;
		
	return false;
}

function cs_array_insert_before($key, array &$array, $new_key, $new_value) {
  if (array_key_exists($key, $array)) {
    $new = array();
    foreach ($array as $k => $value) {
      if ($k === $key) {
        $new[$new_key] = $new_value;
      }
      $new[$k] = $value;
    }
    return $new;
  }
  return FALSE;
}

function cs_order_profile_fields($profile){
	 $keys = cs_apply_filter('order_profile_field', array('name', 'website', 'location', 'about'));
	 $hide = cs_apply_filter('hide_profile_field', array('cover' , 'cs_facebook_a_post', 'cs_facebook_q_post', 'cs_facebook_c_post', 
	 													 'cs_twitter_a_post', 'cs_twitter_q_post', 'cs_twitter_c_post', 
	 													 'aol_hauthSession', 'facebook_hauthSession', 'foursquare_hauthSession', 
	 													 'google_hauthSession', 'linkedin_hauthSession', 'live_hauthSession',
	 													 'myspace_hauthSession', 'openid_hauthSession', 'twitter_hauthSession', 
	 													 'yahoo_hauthSession'));
	 $hide = array_keys(array_flip( $hide ));
	 foreach ($profile as $key => $value) {
	 	if (in_array($key, $hide)) {
	 		unset($profile[$key]);
	 	}
	 }

	 $short = array_flip( $keys );
	 $short = array_merge($short, $profile);

	 return $short ; 
}

function cs_request_text($field)
/*
	Return string for incoming POST field, or null if it's not defined.
	While we're at it, trim() surrounding white space and converted to Unix line endings.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return isset($_REQUEST[$field]) ? preg_replace('/\r\n?/', "\n", trim(qa_gpc_to_string($_REQUEST[$field]))) : null;
	}

function cs_get_user_cover($profile, $small = false, $css = false){
	if(empty($profile['cover']))
		return false;
	
	$url = cs_upload_url().'/';
	$file = explode('.', $profile['cover']);
	
	if(!$small && !$css)
		return $url.$profile['cover'];
		
	elseif($small && !$css)
		return $url.$file[0].'_s.'.$file[1];
	
	elseif($small && $css)
		return ' style="background-image:url('. $url.$file[0].'_s.'.$file[1].')"';
}