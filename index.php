<?php
	//Cron job to add new emails every 5 minutes.
	//To install put the following line in after typing 
	//		sudo crontab -e
	//		*/5 * * * *	/usr/bin/php /your_server_path/plugins/shortmail/index.php 5
	//      0 * * * *	/usr/bin/php /your_server_path/plugins/shortmail/index.php 60
	//		0 0 * * *	/usr/bin/php /your_server_path/plugins/shortmail/index.php 1440
	
	
	if(!isset($shortmail_config)) {
        //Get global plugin config - but only once
		$data = file_get_contents (dirname(__FILE__) . "/config/config.json");
        if($data) {
            $shortmail_config = json_decode($data, true);
            if(!isset($shortmail_config)) {
                echo "Error: Shortmail config/config.json is not valid JSON.";
                exit(0);
            }
     
        } else {
            echo "Error: Missing config/config.json in shortmail plugin.";
            exit(0);
     
        }
  
  
    }
	
	
	
	function strip_html_tags( $text )
	{

	
		$text = preg_replace(
		    array(
		      // Remove invisible content
		        '@<head[^>]*?>.*?</head>@siu',
		        '@<style[^>]*?>.*?</style>@siu',
		        '@<script[^>]*?.*?</script>@siu',
		        '@<object[^>]*?.*?</object>@siu',
		        '@<embed[^>]*?.*?</embed>@siu',
		        '@<applet[^>]*?.*?</applet>@siu',
		        '@<noframes[^>]*?.*?</noframes>@siu',
		        '@<noscript[^>]*?.*?</noscript>@siu',
		        '@<noembed[^>]*?.*?</noembed>@siu',
		      // Add line breaks before and after blocks
		        '@</?((address)|(blockquote)|(center)|(del))@iu',
		        '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
		        '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
		        '@</?((table)|(th)|(td)|(caption))@iu',
		        '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
		        '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
		        '@</?((frameset)|(frame)|(iframe))@iu',
		    ),
		    array(
		        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
		        "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
		        "\n\$0", "\n\$0",
		    ),
		    $text );
		    
		    //Strip excess whitespaces
		    $text = preg_replace('/(\s)+/', ' ', $text);
		    
		return quoted_printable_decode(strip_tags($text));
	}
	
	
	$agent = $shortmail_config['agent'];
	ini_set("user_agent",$agent);
	$_SERVER['HTTP_USER_AGENT'] = $agent;
	$start_path = $shortmail_config['serverPath'];
	

	
	$notify = false;
	require_once('vendor/getemail/getEmail.class.php');
	include_once($start_path . 'config/db_connect.php');	
	
	$define_classes_path = $start_path;     //This flag ensures we have access to the typical classes, before the cls.pluginapi.php is included
	require($start_path . "classes/cls.pluginapi.php");
	
	
    $api = new cls_plugin_api();

	if($argc >= 1) {
		$freq = intval($argv[1]);
	} else {
		$freq = 5;
	}
	

	
	//Read the email feed file
    $feeds = $shortmail_config['mailAccounts'];

	
	$silent = false;
	if(isset($_REQUEST['refresh'])) {
		$silent = true;
		//This is a request from the client, not the server for a particular feed to be refreshed
		$freq = 10000;		//always refresh
		
		if(isset($_REQUEST['u'])) {
			//a custom user
			$feed = array("feed" => (string)$_REQUEST['fe'],
			               "user" => (string)$_REQUEST['u'],
			               "pass" => (string)$_REQUEST['p'],
			               "aj" => (string)$_REQUEST['refresh'],
			               "whisper" => (string)$_REQUEST['w'],
			               "freq" => $_REQUEST['fr'],
			               "mail_id" => $_REQUEST['mail_id']);
			$newfeeds = array($feed);
			$feeds = $newfeeds;
			
		} else {
		
			//Now limit the array of accounts to the one that points at.  Note - ideally this would be an indexed database check once we have outgrown our array structure.
			$newfeeds = array();
			foreach($feeds as $feed) {
				$forums = explode(",",$feed['aj']);
				foreach($forums as $forum) {
			
					if($forum == $_REQUEST['refresh']) {
						$newfeeds = array($feed);
					}
				}
		
			}
		
			$feeds = $newfeeds;
		}
	
	}
	
	if($silent == false) {
		echo "Frequency: $freq\n";
	}
	
	foreach($feeds as $feed) {
		
		if($silent == false) {
			echo "Checking " . $feed['feed'] . ".. ";
		}
		
		if($freq >= $feed['freq']) {		//Only call them if the freq in minutes of this request
			
			
			
			//Get the mail id to check against
			$mail_id = $feed['mail_id'];
			
			//Find the max last msg
			$last_msg_id = null;
			$sql = "SELECT MAX(int_uid_id) as lastmsg from tbl_feed WHERE int_mail_id = " . $mail_id;
			$result = $api->db_select($sql);
			if($row = mysql_fetch_array($result))
			{
				$last_msg_id = $row['lastmsg'];
				 if($silent == false) {
			  		echo "Last message id:" . $last_msg_id . "\n";
			  	}
			
			}
			
			
			$email = new processEmail();

			$email->server =  $feed['feed'];

			/**
			 * Your login details go here
			 */
			
			$email->login =  $feed['user'];
			$email->password =  $feed['pass'];
			$email->optimise = true;			//Only get last day's worth

			/**
			 * If you want to delete emails after getting them, set this to true - ideal for a ticketing system
			 */
			$email->deleteAfterRetr = false;

			/**
			 * Test the connection before hand
			 */
			try {
				$valid = $email->testConnection(); // Test the connection
			} catch (processEmailException $e) {
				die('ERROR: ' . $e->getMessage());
			}

			try {
				if ($valid) { // So everything is okay with the connection
					$email->connect();
					$email->getMessages($last_msg_id);
				}

			} catch (processEmailException $e) {
				die('ERROR: ' . $e->getMessage());
			}
			
			
			
			

			$feed_array_out = $email->messages; 
			if($silent == false) {
			}
	
			foreach ($feed_array_out as $message) {
			 
			  	$guid = (string) $message->messageId;
			  	$pubDate = (string) $message->date;
		  
			    if($guid != "") {
			  
			  	  
			  		
			  	
			  
				  //Check if this item has already been processed for this mailbox
				  $sql = "SELECT * FROM tbl_feed WHERE int_uid_id = '" . trim($guid) . "' AND int_mail_id = " . $mail_id;
				  $result = $api->db_select($sql);
					if($row = mysql_fetch_array($result))
					{
			
						//Already exists - fast skip
					} else {
						//We want to shout this
						
						//Process the message
						$raw_text = $message->message;
						preg_match( '@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s+charset=([^\s"]+))?@i',
							$raw_text, $matches );
						$encoding = $matches[3];
						 
						/* Convert to UTF-8 before doing anything else */
						$utf8_text = iconv( $encoding, "utf-8", $raw_text );
						 
						/* Strip HTML tags and invisible text */
						$utf8_text = strip_html_tags( $utf8_text );
						 
						/* Decode HTML entities */
						$utf8_text = trim(html_entity_decode( $utf8_text, ENT_QUOTES, "UTF-8" )); 
		
						$subject = imap_utf8(quoted_printable_decode($message->subject));
						$subject = str_ireplace("=?utf-8?Q?","", $subject);		//Remove this weird string
			
			
						//Now get rid of former replies
						$utf8_text = preg_replace('/On \d(.*?)\d\d\d\d[\,]? at(.*)/is', '', $utf8_text);		//The s allows for newlines in the match so it goes to the end of the string
			
						if($utf8_text != "") {
							if(strstr($utf8_text, $shortmail_config['sentByShortmail']) == false) {
						        //If not an AtomJump Shotmail message, append the subject to the body
								$utf8_text = $subject . " - " . $utf8_text;
							} else {
								//Simplify the message from other shortmail users
								$utf8_text = preg_replace($shortmail_config['simplifyShortmailMsgs'] , '$1', $utf8_text);
							
							}
						} else {
							$utf8_text = $subject;
							//AtomJump shortmail sent messages only repeat the subject in the body
		
						}
			
						$domain = array_pop($exploded = explode('@', $message->fromAddress));
						//Remove high level domains from the string
						$rem = array('.com','.co.uk','.net','.org');
						foreach($rem as $remove) {
							$domain = str_ireplace($remove, "", $domain);
			
						}
						$username = $domain;
		
			
						$user_only = array('gmail','googlemail','hotmail','live','ymail','yahoo');
						foreach($user_only as $user) {
							if($username == $user) {
								$username = $exploded[0];		//The username 
							}
						}
		
						$username = ucfirst($username);
    
						
						//Record the whole email in the db, and get a link to expand the email					
						$api->db_insert("tbl_email", "(var_layers, var_title, var_body, date_when_received, var_whisper)", 
						                            "(
													'" . clean_data($feed['aj']) . "',
													'". clean_data($subject) . "',
													'" . mysql_real_escape_string($raw_text) .  "', 
													NOW(), 
													'" . $feed['whisper'] . "') ");
						
                        echo "root_server_url:" . $root_server_url;

						$link = $root_server_url . "/plugins/shortmail/seemail.php?id=" . mysql_insert_id() . "&code=234789fhweififu3289sdgwe4t";
						echo "Link:" . $link;
						
						
						$summary_description = summary(strip_tags($description),140);
						if($summary_description != "") {
							$summary_description = " - " . $summary_description;
						
						}
						$shouted = summary($utf8_text, 300) . " " . $link;		//guid may not be url for some feeds, may need to have link
						$your_name = $username;
						$whisper_to = $feed['whisper'];
						$email = $message->fromAddress;

						$ip = "92.27.10.17";                                //must be something, anything
						
						if($silent == false) {
						  	echo "Message added:" . $shouted . "\n";
						}
						  
						  
						//Get the layer(s) info that we are sending this message onto - it can be more than one layer 
						$forum_array = explode(",", $feed['aj']);
				  
				 		foreach($forum_array as $forum_name) {
				 		    //Get the forum id
						 	$forum_info = $api->get_forum_id($forum_name);
						 	 
						    
						    //Ensure we are using a private forum, so that we can send emails out from it
						    $api->db_update("tbl_layer", "enm_access = 'private' WHERE int_layer_id = " . $forum_info['forum_id']);
						    
						    
                            //Send the message
							$api->new_message($your_name, $shouted, $whisper_to, $email, $ip, $forum_info['forum_id'], false);
						}
				
						//Now keep a record of this feed item for easy non duplication
						$api->db_insert("tbl_feed", "(int_uid_id, date_when_shouted, int_mail_id)",
						                    "('" . trim($guid) ."', 
										'" . date("Y-m-d H:i:s", strtotime($pubDate)) . "',
										" . $mail_id . ") ");
						
					}
				}
			
			  
			  
			}
		}
	}

	$json = array('Success');
	
	echo $_GET['callback'] . "(" . json_encode($json) . ")";
	
	session_destroy();  //remove session
	
	
?>

