# shortmail
A fast email client plugin for the AtomJump Loop Server. Email as quickly as you chat. 


## Installation

Download the .zip file or git clone the repository into the directory loop-server/plugins/shortmail

You must have the PHP extension php_imap enabled.

E.g. on Ubuntu:

sudo apt-get install php5-imap
sudo php5enmod imap
sudo service apache2 restart


Put into your crontab file:

sudo crontab -e
	*/5 * * * *	/usr/bin/php /your_server_path/plugins/shortmail/index.php 5
    0 * * * *	/usr/bin/php /your_server_path/plugins/shortmail/index.php 60
	0 0 * * *	/usr/bin/php /your_server_path/plugins/shortmail/index.php 1440


Copy config/configORIGINAL.json to config/config.json

Edit the config file to match your own email accounts.

Note: the group file


## Configuring GMAIL accounts

Go into Settings->Forwarding and POP/IMAP->Enable IMAP
Access for Less secure apps->turn on

## Using the email client

Include an email address in the body of your message to email a new user. Otherwise, click on a user to email them and suggest
they join you on the forum to chat further.

Note: if the tbl_layer.enm_access = 'public', the users will be invited to chat, but iff tbl_layer.enm_access = 'private', there will be a direct email sent. In future versions, we will enable an interface to this configuration. For now, please use SQL in MySQL. 

We also want to CC your inbox for any outgoing emails, so that you have a record of all outgoings in your ordinary mailbox. This is yet to be completed. 
