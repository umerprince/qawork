<?php

	class cs_event_logger {
		
		function init_queries($tableslc)
		{
            require_once QA_INCLUDE_DIR.'qa-app-users.php';
            require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
                                                        
			if (qa_opt('event_logger_to_database')) {
				qa_opt('event_logger_to_database', 0);
			}
			$tablename=qa_db_add_table_prefix('eventlog');
			$quries = array();	
			if (!in_array($tablename, $tableslc)) {
				require_once QA_INCLUDE_DIR.'qa-app-users.php';
				require_once QA_INCLUDE_DIR.'qa-db-maxima.php';

				$quries[] = 'CREATE TABLE ^eventlog ('.
					'datetime DATETIME NOT NULL,'.
					'ipaddress VARCHAR (15) CHARACTER SET ascii,'.
					'userid INT(10),'.
					'handle VARCHAR('.QA_DB_MAX_HANDLE_LENGTH.'),'.
					'cookieid BIGINT UNSIGNED,'.
					'event VARCHAR (20) CHARACTER SET ascii NOT NULL,'.
					'params LONGTEXT NOT NULL,'.
					'KEY datetime (datetime),'.
					'KEY ipaddress (ipaddress),'.
					'KEY userid (userid),'.
					'KEY event (event)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8';
			}

			if (in_array($tablename, $tableslc) && !qa_opt('cs_eventlog_param_datatype')) {
				qa_opt('cs_eventlog_param_datatype', 1);
				$quries[] = 'ALTER TABLE ^eventlog MODIFY params LONGTEXT;';				
			}
			return $quries;
			
		}

		
		function value_to_text($value , $parent_key="")
		{
			$reject_list = array("hotness","created","notify","lastviewip" , "lastip" ,"createip","points","flags") ;
			if (is_array($value)){
				$text = "" ;
				foreach ($value as $key => $val) {
						if (in_array($key, $reject_list)) {
							continue ;
						}
						$new_key = (strlen($parent_key) ? $parent_key."_".$key : $key ) ;
						$text_value = $this->value_to_text($val, $new_key) ;

						$text_value = is_array($val) ? 'array('.count($val).')' ."\t" . $text_value : $text_value ;

						if (!!$text_value) {
							$text.=(strlen($text) ? "\t" : '').$new_key.'='.$text_value;
						} 
				}
			}
				
			elseif (strlen($value)>100)
				$text=substr($value, 0, 97).'...';
			else
				$text=$value;
				
			return strtr($text, "\n\r", '   ');
		}

		function process_event($event, $userid, $handle, $cookieid, $params){
			/*//This is just to test - to be removed in prod 
			cs_event_log_row_parser(cs_event_log_reader());
			return ;*/
			if (qa_opt('event_logger_to_database')) {
				$paramstring='';
				foreach ($params as $key => $value){
					$value_to_text = $this->value_to_text($value , $key) ;
					$value = is_array($value) ? 'array('.count($value).')' ."\t" . $value_to_text : $value_to_text ;
					$paramstring.=(strlen($paramstring) ? "\t" : '').$key.'='.$value;
				}

				$paramstring = strtr($paramstring, "\n\r", '   ');

				qa_db_query_sub(
					'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) '.
					'VALUES (NOW(), $, $, $, #, $, $)',
					qa_remote_ip_address(), $userid, $handle, $cookieid, $event, $paramstring
				);			
			}
			
			if (qa_opt('event_logger_to_files')) {

			//	Substitute some placeholders if certain information is missing
				
				if (!strlen($userid))
					$userid='no_userid';
				
				if (!strlen($handle))
					$handle='no_handle';
				
				if (!strlen($cookieid))
					$cookieid='no_cookieid';
					
				$ip=qa_remote_ip_address();
				if (!strlen($ip))
					$ip='no_ipaddress';
					
			//	Build the log file line to be written
				
				$fixedfields=array(
					'Date' => date('Y\-m\-d'),
					'Time' => date('H\:i\:s'),
					'IPaddress' => $ip,
					'UserID' => $userid,
					'Username' => $handle,
					'CookieID' => $cookieid,
					'Event' => $event,
				);
				
				$fields=$fixedfields;
				
				foreach ($params as $key => $value)
					$fields['param_'.$key]=$key.'='.$this->value_to_text($value , $key);
				
				$string=implode("\t", $fields);
			
			//	Build the full path and file name
			
				$directory=qa_opt('event_logger_directory');
			
				if (substr($directory, -1)!='/')
					$directory.='/';
					
				$filename=$directory.'q2a-log-'.date('Y\-m\-d').'.txt';
				
			//	Open, lock, write, unlock, close (to prevent interference between multiple writes)
				
				$exists=file_exists($filename);

				$file=@fopen($filename, 'a');
				
				if (is_resource($file)) {
					if (flock($file, LOCK_EX)) {
						if ( (!$exists) && (filesize($filename)===0) && !qa_opt('event_logger_hide_header') )
							$string="Question2Answer ".QA_VERSION." log file generated by Event Logger plugin.\n".
								"This file is formatted as tab-delimited text with UTF-8 encoding.\n\n".
								implode("\t", array_keys($fixedfields))."\textras...\n\n".$string;
						
						fwrite($file, $string."\n");
						flock($file, LOCK_UN);
					}

					fclose($file);
				}
			}
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/