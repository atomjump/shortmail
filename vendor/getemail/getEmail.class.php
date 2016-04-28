<?php
/**
 * Class processEmail
 * Connect to a mailbox and retrieve all the emails into an object.
 * @author Andy Dixon <andy@dixon.io>
 */
class processEmail
{

    //example for server, gmail: '{imap.gmail.com:993/imap/ssl/novalidate-cert}'
    var $server = false;
    var $login = false;
    var $password = false;
    var $deleteAfterRetr = false;
    var $messages = array();
    var $count = false;
    var $optimise = false;
    private $connection;

    /**
     * function connect
     * @return boolean if successful, true else false
     * @throws processEmailException
     */
    function connect()
    {
        if ($this->server && $this->login && $this->password):
            $this->connection = imap_open($this->server, $this->login, $this->password);
            $this->count = imap_num_msg($this->connection); else:
            throw new processEmailException('Missing Connection Details');
        endif;
        return (bool)$this->connection; // Returns boolean true or false
    }

    /**
     * function testConnection
     * @return bool Test result - true / fail
     * @throws processEmailException on error
     */
    function testConnection()
    {
        if (!$this->server)
            throw new processEmailException('No server defined');
        if (!$this->login)
            throw new processEmailException('No Username defined');
        if (!$this->password)
            throw new processEmailException('No Password defined');
        $temp = imap_open($this->server, $this->login, $this->password);
        if (!$temp):
            throw new processEmailException(imap_last_error());
        endif;
        imap_close($temp);
        return true;
    }
    
    
    
    
    function get_mime_type(&$structure) {
		$primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
		if($structure->subtype) {
		     return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype;
		 }
		 return "TEXT/PLAIN";
	}

	function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false) {
			if (!$structure) {
				 $structure = imap_fetchstructure($stream, $msg_number);
			 }
			if($structure) {
				 if($mime_type == $this->get_mime_type($structure)) {
				      if(!$part_number) {
				           $part_number = "1";
				       }
				      $text = imap_fetchbody($stream, $msg_number, $part_number);
				      if($structure->encoding == 3) {
				           return imap_base64($text);
				       } else if ($structure->encoding == 4) {
				           return imap_qprint($text);
				       } else {
				           return $text;
				    }
				}
				 if ($structure->type == 1) { /* multipart */
				      while (list($index, $sub_structure) = each($structure->parts)) {
				        if ($part_number) {
				            $prefix = $part_number . '.';
				        } else {
				        	$prefix = "";
				        }
				        $data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1));
				        if ($data) {
				            return $data;
				        }
				    }
				}
			}
			return false;
		}


    
    

    /**
     * function getMessages()
     *
     * @return array of messages and decoded (binary) attachments
     */
    function getMessages($start_msg = null)
    {
        $this->connect();
        if ($this->connection):
            $count = $this->count;
            if ($count > 0):

                //Optimisation trick - if a mailbox is not deleting items but is large, this should speed things up
                if ($this->optimise) {
                	//if(is_null($start_msg)) {
		                $msgs = imap_search($this->connection, "SINCE \"" . date('d-M-Y', strtotime("yesterday")) . "\"");  //,SUID??
		                //$msgs = range(1, $count);
		            //} else {
		            	//A faster request second time round
		            //	$msgs = imap_search($this->connection, "UID " . $start_msg . ":*");
		                
		            
		            //}
                    //Works but slow: $msgs = imap_search($this->connection, "UNDELETED SINCE " . date('d-M-Y', strtotime("yesterday"))); else:
                }
                
                //print_r($msgs);			//TEMP TESTING

                foreach ($msgs as $msgno) {
                
                	if($msgno > $start_msg) {		//Only process if it is a new message

		                $headers = imap_headerinfo($this->connection, $msgno);
		                $_msg = new mailboxItem();

		                $_msg->fromAddress = $headers->from[0]->mailbox . '@' . $headers->from[0]->host;
		                $_msg->fromName = @$headers->from[0]->personal;
		                $_msg->senderAddress = (isset($headers->senderaddress))?$headers->senderaddress:$headers->from[0]->mailbox.'@'. $headers->from[0]->host;
		                if (isset($headers->cc)) {
		                    foreach ($headers->cc as $cc) {
		                        $_msg->cc[] = $cc->mailbox . '@' . $cc->host;
		                    }
		                }
		                $_msg->toAddress = $headers->to[0]->mailbox . '@' . $headers->to[0]->host;
		                $_msg->replyTo = $headers->reply_to[0]->mailbox . '@' . $headers->reply_to[0]->host;
		                $_msg->date = $headers->date;
		                $_msg->subject = (isset($headers->subject))?$headers->subject:'';
		                $_msg->messageId = $msgno;		//imap_uid($this->connection,$msgno);  //OLD:$headers->message_id;
		                $_msg->headers = $headers;
		                $_msg->attachments = array();

		                $struct = imap_fetchstructure($this->connection, $msgno);
		                $parts = @$struct->parts;
		               // print_r($struct);
		                //print_r($parts);
		                $i = 0;

		                if (!$parts) { /* Simple message, only 1 piece */
		                    $attachment = array(); /* No attachments */
		                    $_msg->message = imap_body($this->connection, $msgno, FT_PEEK);
		                } else { /* Complicated message, multiple parts */


							//$_msg->message = "PETER MULTI:";
		                    $endwhile = false;

		                    $stack = array();
		                    $content = "";
		                    $attachment = array();

							$cnt = 0;
						
							// get plain text
							$data_plain = $this->get_part($this->connection, $msgno, "TEXT/PLAIN");
							// get HTML text
							$data_html = $this->get_part($this->connection, $msgno, "TEXT/HTML"); 
						
							if($data_html) {
								$_msg->message = $data_html;
						
							} else {
								$_msg->message = $data_plain;
						
							}
						
						
							/*foreach($parts as $part)  //@$parts[$i]->parts
							{
						
								$_msg->message .= imap_fetchbody($this->connection, $msgno, "1.1", FT_PEEK);
								$_msg->message .= imap_fetchbody($this->connection, $msgno, "1.2", FT_PEEK);
								//$cnt++;
							}*/
						
		                   /* while (!$endwhile) {
		                    
		                    	if (@$parts[$i]->parts) {
		                            $stack[] = array("p" => $parts, "i" => $i);
		                            
		                            //$parts = $parts[$i]->parts;
		                            //$i = 0;
		                        } else {
		                            $i++;
		                        }
		                        
		                        print_r($stack);
		                    
		                    
		                        if (isset($parts[$i])) {
		                            if (count($stack) > 0) {
		                                $parts = $stack[count($stack) - 1]["p"];
		                                $i = $stack[count($stack) - 1]["i"] + 1;
		                                array_pop($stack);
		                            } else {
		                                $endwhile = true;
		                            }
		                        }

		                        if (!$endwhile) {
		                            // Create message part first (example '1.2.3') 
		                            $partstring = "";
		                            foreach ($stack as $s) {
		                                $partstring .= ($s["i"] + 1) . ".";
		                            }
		                            $partstring .= ($i + 1);
		                            echo "PARTSTRING: . " . $partstring . "  ";		

		                            if (strtoupper(isset($parts[$i]->disposition)) && $parts[$i]->disposition == "ATTACHMENT") { // Attachment 
		                                if (is_array($parts[$i]->parameters)) {
		                                    $att = array("filename" => $parts[$i]->parameters[0]->value, "filedata" => imap_fetchbody($this->connection, $msgno, $partstring, FT_PEEK), "encoding" => $parts[$i]->encoding);
		                                    $_att = new emailAttachment();
		                                    $_att->fileData = $this->decode($att);
		                                    $_att->filename = $parts[$i]->parameters[0]->value;
		                                    $_msg->attachments[] = $att;
		                                } else { //Outlook
		                                    $att = array("filename" => '__UNKNOWN__', "filedata" => imap_fetchbody($this->connection, $msgno, $partstring, FT_PEEK), "encoding" => $parts[$i]->encoding);
		                                    $_att = new emailAttachment();
		                                    $_att->fileData = $this->decode($att);
		                                    if(is_array($parts[$i]->parameters)) {
		                                            $_att->filename = $parts[$i]->parameters[0]->value;
		                                    } else {
		                                             $_att->filename = $parts[$i]->dparameters[0]->value;
		                                    }
		                                    $_msg->attachments[] = $_att;
		                                    if (!isset($_msg->message) || strlen($_msg->message) < 1) $_msg->message = $att['filedata'] .  " File data multi Peter added";

		                                }
		                            } elseif (strtoupper($parts[$i]->subtype) == "PLAIN") { // Message
		                                $_msg->message = imap_fetchbody($this->connection, $msgno, $partstring, FT_PEEK) .  " PLAIN multi Peter added";
		                            }
		                        }
							
		                        //was in here
		                        
		                    } // while */
		                } /* complicated message */

		                if ($this->deleteAfterRetr):
		                    imap_delete($this->connection, $msgno);
		                    imap_delete($this->connection, $msgno . ':' . $msgno);
		                endif;
		                $this->messages[] = $_msg;
		                $_msg = null;
		    		}        
		    }
            endif;

            $this->disconnect();
        endif;
        return $this->messages;
    }

    function disconnect()
    {
        imap_expunge($this->connection);
        imap_close($this->connection);
    }

    /**
     * private function decode
     * @param array Attachment array from the main process function
     * @return string decoded attachment
     */
    private
    function decode($att)
    {
        if (is_array($att) && isset($att['encoding']) && isset($att['filedata'])) {
            $coding = $att['encoding'];
            if ($coding == 0) {
                $message = imap_8bit($att['filedata']);
            } elseif ($coding == 1) {
                $message = imap_8bit($att['filedata']);
            } elseif ($coding == 2) {
                $message = imap_binary($att['filedata']);
            } elseif ($coding == 3) {
                $message = imap_base64($att['filedata']);
            } elseif ($coding == 4) {
                $message = quoted_printable_decode($att['filedata']);
            } elseif ($coding == 5) {
                $message = $att['filedata'];
            }
            return $message;
        }
        return "";
    }
}

class processEmailException extends Exception
{

}

class mailboxItem
{
    public $fromAddress;
    public $fromName;
    public $senderAddress;
    public $toAddress;
    public $replyTo;
    public $date;
    public $subject;
    public $messageId;
    public $attachments;
    public $headers;
    public $message;
    public $cc;

}

class emailAttachment
{
    public $fileData;
    public $filename;
}
