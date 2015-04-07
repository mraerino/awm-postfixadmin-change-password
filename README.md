# AfterLogic Webmail Lite password change plugin for postfixadmin
## Installation
Simply put the files into a directory `postfixadmin-password-change` inside your `data/plugins` folder.

And set the following config directives in `settings/config.php`:
```php
'plugins.postfixadmin-change-password' => true,

'plugins.postfixadmin-change-password.config.host' => '127.0.0.1',
'plugins.postfixadmin-change-password.config.dbuser' => '<postfixadmin db user>',
'plugins.postfixadmin-change-password.config.dbpassword' => "<postfixadmin db password>",
'plugins.postfixadmin-change-password.config.dbname' => '<postfixadmin db name>'
```

You should be good to go!

## Credits
Based on **ispconfig-change-password** plugin by AfterLogic.

MD5Crypt lib from https://github.com/RainLoop/rainloop-webmail/tree/master/plugins/postfixadmin-change-password
