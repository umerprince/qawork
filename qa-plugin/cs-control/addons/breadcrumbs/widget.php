<?php

class cs_breadcrumbs_widget {

      function cs_widget_form() {

            return array(
                'style' => 'wide',
                'fields' => array(
                    'cs_nu_count' => array(
						'label' => 'Numbers of user',
						'type'  => 'number',
						'tags'  => 'name="cs_nu_count" class="form-control"',
						'value' => '10',
                    ),
                    'cs_nu_avatar' => array(
						'label' => 'Avatar Size',
						'type'  => 'number',
						'tags'  => 'name="cs_nu_avatar" class="form-control"',
						'value' => '30',
                    ),
                ),
            );
      }

      function allow_template($template) {
            $allow = false;
            switch ($template) {
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
                        $allow = true;
                        break;
            }
            return $allow;
      }

      function allow_region($region) {
            $allow = false;
            switch ($region) {
                  case 'main':
                  case 'side':
                  case 'full':
                        $allow = true;
                        break;
            }
            return $allow;
      }

      function navigation() {
            $request = qa_request_parts(); // this will out put 0=>admin, 1=>sdhfhjsdf :D
            if (isset($request) && !empty($request) && is_array($request)) return $request;
      }

      function output_widget($region, $place, $themeobject, $template, $request, $qa_content) {
            $widget_opt = @$themeobject->current_widget['param']['options'];

            if (@$themeobject->current_widget['param']['locations']['show_title'] && isset($cat['title'])) $themeobject->output('<h3 class="widget-title">' . $cat['title'] . '</h3>');

            $themeobject->output('<div class="ra-breadcrumbs-widget">');
            // breadcrumb start
            $themeobject->output('<ol class="breadcrumb">');
            $themeobject->output($this->breadcrumb_part(array('type' => 'home')));
            $themeobject->output($this->create_breadcrumbs($this->navigation(), $qa_content));
            $themeobject->output('</ol>');
            // breadcrumb end 
            $themeobject->output('</div>');
      }

      function create_breadcrumbs($navs, $qa_content) {
            $br = "";
            $question_page = @$qa_content['q_view'];
            if (!!$question_page) {     //if it is a question page 
                  // category is the first priority 
                  $cat = @$question_page['where'];
                  $tags = @$question_page['q_tags'];
                  if (!!$cat) {
                        $categoryids = @$qa_content['categoryids'];
                        if (!!$categoryids) {
                              foreach ($categoryids as $categoryid) {
                                    $category_details = $this->get_cat($categoryid);
                                    if (is_array($category_details) && !empty($category_details)) {
											$backpath = $category_details['backpath'];
											$text     = $category_details['title'];
											$url      = qa_path(implode('/', array_reverse(explode('/', $backpath))));
                                          $data = array(
												'type' => 'cat',
												'text' => $text,
												'url'  => $url,
                                          );
                                          $br .=$this->breadcrumb_part($data);
                                    }
                              }
                        }
                  } else if (!!$tags) { //if question is asked with out any categories list all the tags 
                        foreach ($tags as $tag) {
                              $br .=$this->breadcrumb_part(array(
                                  'type' => 'q_tag',
                                  'text' => $tag,
                              ));
                        }
                  }
            } else {  //means non questions page 
                  if (count($navs) > 0) {
                        $link = "";
                        $type = $navs[0];
                        if (!$type) {
                        	return ; //if there is not a single part -- go back from here 
                        }
                        
                        foreach ($navs as $nav) {
								$link .= (!!$link) ? "/" . $nav : $nav;
								$br   .= $this->breadcrumb_part(array(
									'type' => $type,
									'url'  => qa_path($link),
									'text' => $nav,
                              ));
                        }

                        switch ($type) {
                              case 'unanswered':
                                    $by = qa_get('by');
                                    if (!$by) {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'no-ans',
												'url'  => qa_path($link),
												'text' => "No Answer",
                                          ));
                                    } else if ($by === 'selected') {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'no-selected',
												'url'  => qa_path($link) . '?by=selected',
												'text' => "No Selected Answer",
                                          ));
                                    } else if ($by === 'upvotes') {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'no-upvots',
												'url'  => qa_path($link) . '?by=upvotes',
												'text' => "No Upvoted Answer",
                                          ));
                                    }

                                    break;
                              case 'questions':
                                    $sort = qa_get('sort');
                                    if (!$sort) {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'q-sort-recent',
												'url'  => qa_path($link),
												'text' => "Recent Questions",
                                          ));
                                    } else if ($sort === 'hot') {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'q-sort-hot',
												'url'  => qa_path($link) . '?sort=hot',
												'text' => "Hot",
                                          ));
                                    } else if ($sort === 'votes') {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'q-sort-votes',
												'url'  => qa_path($link) . '?sort=votes',
												'text' => "Most Votes",
                                          ));
                                    } else if ($sort === 'answers') {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'q-sort-answers',
												'url'  => qa_path($link) . '?sort=answers',
												'text' => "Most Answers",
                                          ));
                                    } else if ($sort === 'views') {
                                          $br .= $this->breadcrumb_part(array(
												'type' => 'no-sort-views',
												'url'  => qa_path($link) . '?sort=views',
												'text' => "Most Views",
                                          ));
                                    }
                                    break;

                              default:
                                    break;
                        }
                  }
            }

            return $br;
      }

      function breadcrumb_part($data) {
            if (!$data) {
                  return;
            }
            $li_template = "<li ^class><a href='^url'>^icon^text</a></li>";
            $type = isset($data['type']) ? $data['type'] : "";
            $text = isset($data['text']) ? $data['text'] : "";
            $url = isset($data['url']) ? $data['url'] : "#";
            $icon = '';
            $class = "";
            // $text = qa_lang("breadcrumbs/$text");
            switch ($type) {
                  case 'home':
                        $url = qa_opt('site_url');
                        $text = "Home";
                        $class = "class='cs-breadcrumbs-home'";
                        $icon = "<i class='icon-home'></i> ";
                        break;
                  case 'cat':
                  case 'categories':
                        $class = "class='cs-breadcrumbs-cat'";
                        break;
                  case 'q_tag':
                        $li_template = "<li ^class>^text</li>";
                        $class = "class='cs-breadcrumbs-tag'";
                        break;
                  default:
                        $class = "class='cs-breadcrumbs-$type'";
                        break;
            }
            return strtr($li_template, array(
                '^class' => $class,
                '^url' => $url,
                '^icon' => $icon,
                '^text' => $text,
            ));
      }

      function get_cat($cat_id = "") {
            if (!$cat_id) return;

            require_once QA_INCLUDE_DIR . "/qa-db-selects.php";
            return (qa_db_select_with_pending(qa_db_full_category_selectspec($cat_id, true)));
      }

}

// qa_db_slugs_to_backpath
/*

	Omit PHP closing tag to help avoid accidental output

*/