<?php
	class qw_user_posts_widget {

		function qw_widget_form()
		{
			
			return array(
				'style' => 'wide',
				'fields' => array(
					'qw_up_count' => array(
						'label' => 'Numbers of post',
						'type' => 'number',
						'tags' => 'name="qw_up_count" class="form-control"',
						'value' => '10',
					),
					'qw_up_type' => array(
						'label' => 'Numbers of Questions',
						'type' => 'select',
						'tags' => 'name="qw_up_type" class="form-control"',
						'value' => array('Q' => 'Questions'),
						'options' => array(
							'Q' => 'Questions',
							'A' => 'Answers',
							'C' => 'Comments',
						)
					),
				),

			);
		}

		
		function allow_template($template)
		{
			$allow=false;
			
			switch ($template)
			{
				case 'activity':
				case 'qa':
				case 'questions':
				case 'hot':
				case 'ask':
				case 'categories':
				case 'question':
				case 'tag':
				case 'tags':
				case 'unanswered':
				case 'user':
				case 'users':
				case 'search':
				case 'admin':
				case 'custom':
					$allow=true;
					break;
			}
			
			return $allow;
		}

		
		function allow_region($region)
		{
			$allow=false;
			
			switch ($region)
			{
				case 'main':
				case 'side':
				case 'full':
					$allow=true;
					break;
			}
			
			return $allow;
		}

		// output the list of selected post type
		function qw_user_post_list($handle, $type, $limit){
			$userid = qa_handle_to_userid($handle);
			require_once QA_INCLUDE_DIR.'qa-app-posts.php';
			if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
				global $wpdb;
				$post = qw_get_cache('SELECT * FROM ^posts INNER JOIN '.$wpdb->base_prefix.'users ON ^posts.userid='.$wpdb->base_prefix.'users.ID WHERE ^posts.type=$ and ^posts.userid=# ORDER BY ^posts.created DESC LIMIT #', 10 ,$type, $userid, $limit);
			}else
				$post = qw_get_cache('SELECT * FROM ^posts INNER JOIN ^users ON ^posts.userid=^users.userid WHERE ^posts.type=$ and ^posts.userid=# ORDER BY ^posts.created DESC LIMIT #', 10 ,$type, $userid, $limit);	

			$output = '<ul class="question-list users-post-widget post-type-'.$type.'">';
			
			if(count($post) > 0){
				foreach($post as $p){
			
					$handle = $p['handle'];

					$output .= '<li id="q-list-'.$p['postid'].'" class="question-item">';
					if ($type=='Q'){
						$output .= '<div class="big-ans-count pull-left">'.$p['acount'].'<span></span></div>';
					}
					$output .= '<div class="list-right">';
					$timeCode = qa_when_to_html(  strtotime( $p['created'] ) ,7);
					$when = @$timeCode['prefix'] . @$timeCode['data'] . @$timeCode['suffix'];
					if($type=='Q'){
						$output .= '<a href="'. qa_q_path_html($p['postid'], $p['title']) .'" title="'. $p['title'] .'">'.qa_html($p['title']).'</a>';
					}elseif($type=='A'){
						$output .= '<a href="'.qw_post_link($p['parentid']).'#a'.$p['postid'].'">'. qw_truncate(strip_tags($p['content']), 100).'</a>';
					}else{
						$output .= '<a href="'.qw_post_link($p['parentid']).'#c'.$p['postid'].'">'. qw_truncate(strip_tags($p['content']), 100).'</a>';
					}
					
					$output .= '<div class="list-date"><span class="icon-calendar">'.$when.'</span>';	
					$output .= '<span class="icon-thumbs-up">'.qa_lang_sub('cleanstrap/x_votes', $p['netvotes']).'</span></div>';	
					$output .= '</div>';	
					$output .= '</li>';
				}
			}else{
				if($type=='Q'){
					$what = 'comments';
				}elseif($type=='A'){
					$what = 'answers';
				}elseif('C'){
					$what = 'comments';
				}
				$output .= '<li class="no-post-found">No '.$what.' posted yet! </li>';
			}
			$output .= '</ul>';
			echo $output;
		}

		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			$widget_opt = @$themeobject->current_widget['param']['options'];
			if(defined('QA_WORDPRESS_INTEGRATE_PATH')){
				$userid = $qa_content['raw']['userid'];
				$user_info = get_userdata( $userid );
				$handle = $user_info->user_login;
				
			}else
				$handle = $qa_content['raw']['account']['handle'];
			if($widget_opt['qw_up_type'] == 'Q')
				$type_title = 'questions';
			elseif($widget_opt['qw_up_type'] == 'A')
				$type_title = 'answers';
			else
				$type_title = 'comments';
			
			if($widget_opt['qw_up_type'] != 'C')
				$type_link = '<a class="see-all" href="'.qa_path_html('user/'.$handle.'/'.$type_title).'">Show all</a>';
			
			if(@$themeobject->current_widget['param']['locations']['show_title'])
				$themeobject->output('<h3 class="user-post-title">'.$handle.'\'s '.$type_title.@$type_link.'</h3>');

			$themeobject->output('<div class="ra-ua-widget">');
			$themeobject->output($this->qw_user_post_list($handle, @$widget_opt['qw_up_type'],  (int)$widget_opt['qw_up_count']));
			$themeobject->output('</div>');
		}
	
	}
/*
	Omit PHP closing tag to help avoid accidental output
*/