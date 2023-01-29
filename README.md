<img src="https://atomjump.com/images/logo80.png">

__WARNING: this project has now moved to https://src.atomjump.com/atomjump/shortmail.git__

# shortmail
A fast email client plugin for the AtomJump Messaging Server. Email as quickly as you chat.

 
## Requirements

AtomJump Messaging Server >= 1.0.4


## Installation

Find the server at https://src.atomjump.com/atomjump/loop-server. Download and install.

Download the .zip file or git clone the repository into the directory messaging-server/plugins/shortmail

You must have the PHP extension php_imap enabled.

E.g. on Ubuntu:

sudo apt-get install php5-imap
sudo php5enmod imap
sudo service apache2 restart


Put into your crontab file:

sudo crontab -e
*/5 * * * *	/usr/bin/php /your_server_path/plugins/shortmail/index.php 5  



Copy config/configORIGINAL.json to config/config.json

Edit the config file to match your own email accounts.

Note: the group file


## Configuring GMAIL accounts

Go into Settings->Forwarding and POP/IMAP->Enable IMAP  
Access for Less secure apps->turn on

## Using the email client

Configure your popup settings with your email address.

After editing the config file, you can go into the AtomJump popup.  To read the full expanded email, you must be an owner of the forum: Click settings->Advanced->This forum's private owners, and copy and paste your myMachineUser value e.g. "192.100.101.53:420".

Click on a user's name, write a message and click 'Send to [their name]'  to email them. You will also be BCC'ed into any outgoing emails, so that you have a record of this outgoing message in your ordinary inbox.

To email a new user: include an email address in the body of your message. For AtomJump Messaging Server >= 1.6.2, you need the word 'email:' before it. 

Your popup will be auto-updated with new incoming email messages every five minutes.

