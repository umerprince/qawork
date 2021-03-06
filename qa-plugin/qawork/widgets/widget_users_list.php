<?php
	class qw_users_list_widget {

		function qw_widget_form()
		{
			
			return array(
				'style' => 'wide',
				'fields' => array(
					'count' => array(
						'label' => 'Numbers of user',
						'type' => 'number',
						'tags' => 'name="count" class="form-control"',
						'value' => '10',
					),
					'avatar_size' => array(
						'label' => 'Avatar Size',
						'type' => 'number',
						'tags' => 'name="avatar_size" class="form-control"',
						'value' => '30',
					),
					'type' => array(
						'label' => 'List type',
						'type' => 'select',
						'tags' => 'name="type" class="form-control"',
						'options' => array('new' => 'New Users', 'top_users' => 'Top Users'),
					),
					'inline' => array(
						'label' => 'Inline',
						'type' => 'checkbox',
						'tags' => 'name="inline"',
						'value' => '1',
					),
					'scroll' => array(
						'label' => 'Scroll',
						'type' => 'checkbox',
						'tags' => 'name="scroll"',
						'value' => '1',
					),
					'slide_width' => array(
						'label' => 'Slide width',
						'type' => 'number',
						'tags' => 'name="slide_width" class="form-control"',
						'value' => '1400',
					),
					'min_slides' => array(
						'label' => 'Min. Slide',
						'type' => 'number',
						'tags' => 'name="min_slide" class="form-control"',
						'value' => '10',
					),
					'max_slides' => array(
						'label' => 'Max. Slide',
						'type' => 'number',
						'tags' => 'name="max_slide" class="form-control"',
						'value' => '10',
					),
					'slide_margin' => array(
						'label' => 'Slide margin',
						'type' => 'number',
						'tags' => 'name="slide_margin" class="form-control"',
						'value' => '10',
					),
					'auto' => array(
						'label' => 'Auto transition',
						'type' => 'checkbox',
						'tags' => 'name="auto"',
						'value' => '1',
					)
				)

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
		function qw_new_users($limit, $size){
			$output = '<div class="users-list clearfix">';			
			
			$users = qa_db_read_all_assoc(qa_db_query_sub("SELECT * FROM ^users ORDER BY created DESC LIMIT #", $limit));
			
			foreach($users as $u){
				if (isset($u['handle'])){
					$handle = $u['handle'];
					$avatar = qw_get_post_avatar($u, $size, false);
					if (isset($u['useid']))	$id = $u['useid']; else $id = qa_handle_to_userid($handle);
					$output .= '<div class="item slide user">';
					if (!empty($avatar))
						$output .= '<div class="avatar" data-handle="'. $handle .'" data-id="'. $id .'">'.$avatar.'</div>';
					$output .= '</div>';
				}
			}

			$output .= '</div>';
			echo $output;
		}
		
		function qw_top_users($limit, $size){
			$output = '<div class="users-list clearfix">';			
			
			$users = qa_db_read_all_assoc(qa_db_query_sub("SELECT * FROM ^users JOIN ^userpoints ON ^users.userid=^userpoints.userid ORDER BY ^userpoints.points DESC LIMIT #", $limit));
			
			foreach($users as $u){
				if (isset($u['handle'])){
					$handle = $u['handle'];
					$avatar = qw_get_post_avatar($u, $size, false);
					if (isset($u['useid']))	$id = $u['useid']; else $id = qa_handle_to_userid($handle);
					$output .= '<div class="item slide user">';
					if (!empty($avatar))
						$output .= '<div class="avatar" data-handle="'. $handle .'" data-id="'. $id .'">'.$avatar.'</div>';
					$output .= '</div>';
				}
			}

			$output .= '</div>';
			echo $output;
		}

		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			$widget_opt = $themeobject->current_widget['param']['options'];

			if(@$themeobject->current_widget['param']['locations']['show_title'])
				$themeobject->output('<h3 class="widget-title">'.($widget_opt['type'] =='new' ? 'New' : 'Top').' Users</h3>');
			
			$opt_array = htmlspecialchars(json_encode(array('slideWidth' => $widget_opt['slide_width'], 'slideMargin' => $widget_opt['slide_margin'], 'auto' => ($widget_opt['slide_width'] ? 'true' : 'false'), 'minSlides' => $widget_opt['min_slide'], 'maxSlides' => $widget_opt['max_slide'], 'prevText' => '<i class="icon-chevron-left"></i>', 'nextText' => '<i class="icon-chevron-right"></i>' )), ENT_QUOTES, 'UTF-8');
			
			$slider	= ($widget_opt['scroll'] ? 'data-action="slider" data-opt="'.$opt_array.'"' : '');
			$themeobject->output('<div class="ra-users-list-widget'.($widget_opt['type'] ? ' inline' : '').'" '.$slider.'>');
			
			if($widget_opt['type'] == 'new')
				$themeobject->output($this->qw_new_users((int)@$widget_opt['count'], (int)@$widget_opt['avatar_size']));
			
			elseif($widget_opt['type'] == 'top_users')
				$themeobject->output($this->qw_top_users((int)@$widget_opt['count'], (int)@$widget_opt['avatar_size']));
				
			$themeobject->output('</div>');
		}
	
	}
/*
	Omit PHP closing tag to help avoid accidental output
*/