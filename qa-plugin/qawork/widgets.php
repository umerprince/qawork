<?php
/* don't allow this page to be requested directly from browser */	
if (!defined('QA_VERSION')) {
		header('Location: /');
		exit;
}


class qw_theme_widgets {
	var $directory;
	var $urltoroot;

	function load_module($directory, $urltoroot) {
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}

	function match_request($request)
	{
		if (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN && $request=='themewidgets')
			return true;

		return false;
	}
	function process_request($request)
	{
		$saved=false;
		if (qa_clicked('qw_remove_all_button')) {	
			qa_db_query_sub('TRUNCATE TABLE ^ra_widgets');
			$saved=true;
		}
		if (qa_clicked('qw_reset_widgets_button')) {	
			$handle = fopen(QW_CONTROL_DIR.'/inc/widget_reset.sql', 'r');
			$sql = '';
							

			if($handle) {
				while(($line = fgets($handle, 4096)) !== false) {
					$sql .= trim(' ' . trim($line));
					if(substr($sql, -strlen(';')) == ';') {
							qa_db_query_sub($sql);
							$sql = '';
					}
				}
				fclose($handle);
			}					
			$saved=true;
		}
		
		$qa_content=qa_content_prepare();		
		$qa_content['site_title']="Theme Widgets";
		$qa_content['title']="Theme Widgets";
		$qa_content['error']="";
		$qa_content['suggest_next']="";
		
		$qa_content['custom']= $this->opt_form();
		
		return $qa_content;	
	}
	
	function opt_form(){
		$output = '<div id="ra-widgets">
					<div class="widget-list col-sm-5">
						'. $this->qw_get_widgets() .'
					</div>
					<div class="widget-postions col-sm-7">
						'.$this->qw_get_widgets_positions().'
					</div>
				</div>
				<div class="form-widget-button-holder">
					<form class="form-horizontal" method="post">
						<input class="qa-form-tall-button btn-primary" type="submit" name="qw_remove_all_button" value="Remove All Widgets" title="">
						<input class="qa-form-tall-button btn-primary" type="submit" name="qw_reset_widgets_button" value="Reset All Widgets To Theme Default" title="">
					</form>
				</div>';
		
		return $output;
	}
	
	function qw_get_widgets(){
			ob_start();
			foreach(qa_load_modules_with('widget', 'allow_template') as $k => $widget){
				?>
				<div class="draggable-widget" data-name="<?php echo $k; ?>">					
					<div class="widget-title"><?php echo $k; ?> 
						<div class="drag-handle icon-move"></div>
						<div class="widget-delete icon-trash"></div>
						<div class="widget-template-to icon-th-menu"></div>
						<div class="widget-options icon-spanner"></div>
					</div>
					<div class="select-template">
						<label>
						<input type="checkbox" name="show_title" checked> Show widget title</label><br />
						<span>Select where you want to show</span>
						<?php
							echo $this->get_widget_template_checkbox();
						?>
					</div>
					<div class="widget-option">
						<form method="post">
						<?php $this->get_widget_form($k); ?>
						</form>
					</div>
				</div>
				<?php
			}
			
			return ob_get_clean();
		}	


		function qw_get_widgets_positions(){

			$widget_positions = qw_widget_position();

			ob_start();
			if(is_array($widget_positions)){
				foreach($widget_positions as $name => $description){
				
					?>
					<div class="widget-canvas" data-name="<?php echo $name; ?>">		
						<div  class="position-header">		
							<?php echo $name; ?><span class="position-description"><?php echo $description; ?></span>							
							<i class="position-toggler icon-cog"></i>
							<div class="widget-save icon-ok btn"> Save</div>
						</div>
						<div class="position-canvas" data-name="<?php echo $name; ?>">
							<?php
								$pos_widgets = get_widgets_by_position($name);
								if(isset($pos_widgets) && !empty($pos_widgets))
									foreach($pos_widgets as $w){ ?>
										<div class="draggable-widget" data-name="<?php echo $w['name']; ?>" data-id="<?php echo $w['id']; ?>">	
											<div class="widget-title"><?php echo $w['name']; ?> 
												<div class="drag-handle icon-move"></div>
												<div class="widget-delete icon-trash"></div>
												<div class="widget-template-to icon-th-menu"></div>		
												<div class="widget-options icon-spanner"></div>		
											</div>
											<div class="select-template">
											<input type="checkbox" name="show_title" <?php echo (@$w['param']['locations']['show_title'] ? 'checked' : ''); ?>> Show widget title</label><br />
												<span>Select pages where you want to show</span>
												<?php
													foreach(qw_get_template_array() as $k => $t){
														$checked = @$w['param']['locations'][$k] ? 'checked' : '';
														echo '												
															<div class="checkbox">
																<label>
																	<input type="checkbox" name="'.$k.'" '.$checked.'> '.$t.'
																</label>
															</div>
														';
													}
												?>
											</div>
											<div class="widget-option">
												<form method="post">
												<?php 
													if ( isset($w['param']['options']['qw_t_text']) ){
														$w['type']='textarea';
													}
													$this->get_widget_form($w['name'], $w['param']['options']);
												?>
												</form>
											</div>
											
										</div>									
									<?php
									}
									
							?>
						</div>
					</div>
					<?php
				}
			}
			return ob_get_clean();
		}
		
		function get_widget_template_checkbox(){
			$output = '';
			foreach(qw_get_template_array() as $t_name => $t)
				$output .='												
					<div class="checkbox">
						<label>
							<input type="checkbox" name="'.$t_name.'" checked> '.$t.'
						</label>
					</div>
				';
			return $output;
		}
		function get_widget_form($name, $options = false){
			
			$module	=	qa_load_module('widget', $name);
			
			if(is_object($module) && method_exists($module, 'qw_widget_form')){
				$fields = $module->qw_widget_form();
				if($options){
					foreach($options as $k => $opt){
						if(isset($fields['fields'][$k])){
							$fields['fields'][$k]['value'] = urldecode($opt);
						}
					}
				}
				echo $this->widget_options_form($fields); 
			}
		}
		
		function widget_options_form($fields){
			$output = '';
			if(isset($fields) && is_array($fields)){
				foreach($fields['fields'] as $k => $field){
					
					if($field['type'] == 'select'):
						$output .= $this->widget_options_form_label($k, $field);
						$output .= $this->widget_options_form_select($field);
					
					elseif($field['type'] == 'textarea'):
						$output .= $this->widget_options_form_label($k, $field);
						$output .= $this->widget_options_form_textarea($field);
					
					elseif($field['type'] == 'checkbox'):
						$output .= $this->widget_options_form_checkbox($k ,$field);
					
					elseif($field['type'] == 'text'):
						$output .= $this->widget_options_form_label($k, $field);
						$output .= $this->widget_options_form_text($field);
						
					else:
						$output .= $this->widget_options_form_label($k, $field);
						$output .= $this->widget_options_form_input($field);
					endif;
				}
			}
			return $output;
		}
		
		function widget_options_form_select($field){
			$option = '';
			foreach($field['options'] as $k => $opt)
				$option .= '<option value="'.$k.'"'.(@$field['value']== $k ? ' selected ' : '' ).'>'.$opt.'</option>';
				
			return '<select '.$field['tags'].'>'.$option.'</select>';
		}
		
		function widget_options_form_textarea($field){
			return '<textarea '.$field['tags'].'>'.@$field['value'].'</textarea>';
		}
		
		function widget_options_form_text($field){
			return '<input type="text" '.$field['tags'].' value="'.@$field['value'].'" />';
		}
		
		function widget_options_form_input($field){
			return '<input '.$field['tags'].' value="'.@$field['value'].'" />';
		}

		function widget_options_form_checkbox($k, $field){
			return '<div class="checkbox">
						<label for="'.$k.'">'.@$field['label'].'
							<input type="checkbox" '.$field['tags'] .($field['value'] ? ' checked' : '').'  >
						</label>
					</div>
				';
		}
		
		function widget_options_form_label($k, $field){
			return '<label for="'.$k.'">'.@$field['label'].'</label>';
		}
	
	
}

