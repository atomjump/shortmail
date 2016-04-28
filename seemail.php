<?php
  //See email
  
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
  

    $start_path = $shortmail_config['serverPath'];

  	require_once('vendor/getemail/getEmail.class.php');
	include_once($start_path . 'config/db_connect.php');	
	
	$define_classes_path = $start_path;     //This flag ensures we have access to the typical classes, before the cls.pluginapi.php is included
	require($start_path . "classes/cls.pluginapi.php");
    
    $api = new cls_plugin_api();
 
	
	
	
	
	
	//Read the email
	$sql = "SELECT * FROM tbl_email WHERE int_email_id = " . clean_data($_REQUEST['id']);
	$result = $api->db_select($sql);
	if($row = mysql_fetch_array($result))
	{
	
		//Check we have permissions on this layer
		
		$forum_array = explode(",", $row['var_layers']);
		$authenticated = false;	
		
		
		if($_SESSION['logged-user']) {	
		    
	 		foreach($forum_array as $forum) {
			 	$forum_info = $api->get_forum_id($forum);
			
				$sql = "SELECT * FROM tbl_layer_subscription WHERE int_layer_id = " . clean_data($forum_info['forum_id']) . " AND int_user_id = " . $_SESSION['logged-user'] . " AND enm_active = 'active'";
				$resultb = $api->db_select($sql);
				if($rowb = mysql_fetch_array($resultb))	
				{
					$authenticated = true;
			
					//TODO - check whisper_to_ status also
				}		
			}
		}
		
		if($authenticated == true) {
	
			?>
			<!DOCTYPE html>
			<html lang="en" id="fullscreen">
	  		<head>
	  	    	<meta charset="utf-8">
			 	<meta name="viewport" content="width=device-width, user-scalable=no">
			 	<title>AtomJump Shortmail - <?php echo $row['var_title'] ?></title>
			 
				 <meta name="description" content="Offer your customers a smart feedback form, with live chat, public & private posts across any mobile or desktop device.">
				 
				 <meta name="keywords" content="Feedback Form, Live Chat, Customer Chat">
			
			</head>
			<body>
				<?php
				echo $row['var_body'];		//Display the email body
				?>
			
			</body>
			</html>
			
			
		
				<?php
		}
	
	}
	


?>

