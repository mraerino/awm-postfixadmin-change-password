<?php

/*
 *
 * Distributed under the terms of the license described in COPYING
 *
 */

class_exists('CApi') or die();

CApi::Inc('common.plugins.change-password');

class CCustomChangePasswordPlugin extends AApiChangePasswordPlugin
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.1', $oPluginManager);
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function validateIfAccountCanChangePassword($oAccount)
	{
		$bResult = false;
		if ($oAccount instanceof CAccount)
		{
			$bResult = true;
		}

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function ChangePasswordProcess($oAccount)
	{
		$bResult = false;
		if (0 < strlen($oAccount->PreviousMailPassword) &&
			$oAccount->PreviousMailPassword !== $oAccount->IncomingMailPassword)
		{

			$aPostfixadmin = [
				"host" => CApi::GetConf('plugins.postfixadmin-change-password.config.host', '127.0.0.1'),
				"dbuser" => CApi::GetConf('plugins.postfixadmin-change-password.config.dbuser', 'root'),
				"dbpassword" => CApi::GetConf('plugins.postfixadmin-change-password.config.dbpassword', ''),
				"dbname" => CApi::GetConf('plugins.postfixadmin-change-password.config.dbname', 'postfixadmin'),
			];

			//connect to postfixadmin database
			$mysqlcon = mysqli_connect($aPostfixadmin['host'],$aPostfixadmin['dbuser'],$aPostfixadmin['dbpassword'],$aPostfixadmin['dbname']);

		 	if($mysqlcon){
				//check old pass is correct
				$username = $oAccount->IncomingMailLogin;
				$password = $oAccount->PreviousMailPassword;
				$new_password = $oAccount->IncomingMailPassword;

				$sql = "SELECT * FROM mailbox WHERE username='$username'";
				$result = mysqli_query($mysqlcon,$sql);
				$mailuser = mysqli_fetch_array($result);

				$saved_password = $mailuser['password'];

				// get salt
				$parts = explode("\$", $saved_password);
				$salt = "";
				if($parts[1] == '1' && count($parts) == 4) {
					$salt = $parts[2];
				}

				// load md5crypt
				include_once __DIR__.'/md5crypt.php';
				list(,$domain) = explode("@", $mailuser_id);


				//* Check if mailuser password is correct
				if(md5crypt(stripslashes($password), $salt) == $saved_password) {
					$mailuser_id = $mailuser['username'];

					$new_password = md5crypt($new_password);
					$sql = "UPDATE mailbox SET password='$new_password',modified=CURRENT_TIMESTAMP WHERE username='$mailuser_id'";
					$result = mysqli_query($mysqlcon,$sql);

					if (!$result){
						//add log into postfixadmin
						mysqli_query($mysqlcon, "INSERT INTO log VALUES(CURRENT_TIMESTAMP, '{$mailuser_id} ({$_SERVER["REMOTE_ADDR"]})', '{$domain}', 'edit_password', 'FAILURE: {$mailuser_id}, webmail')");
						//password update error
						throw new CApiManagerException(Errs::UserManager_AccountNewPasswordUpdateError);
					}

					//add log into postfixadmin
					mysqli_query($mysqlcon, "INSERT INTO log VALUES(CURRENT_TIMESTAMP, '{$mailuser_id} ({$_SERVER["REMOTE_ADDR"]})', '{$domain}', 'edit_password', '{$mailuser_id}, webmail')");
				} else {
					mysqli_query($mysqlcon, "INSERT INTO log VALUES(CURRENT_TIMESTAMP, '{$mailuser_id} ({$_SERVER["REMOTE_ADDR"]})', '{$domain}', 'edit_password', 'MATCH FAILURE: {$mailuser_id}, webmail')");
					//old and new passwords dont match
					throw new CApiManagerException(Errs::UserManager_AccountOldPasswordNotCorrect);
				}
				//disconnect from database
				mysqli_close($mysqlcon);

			} else {
				//could not connect to database
				throw new CApiManagerException(Errs::UserManager_AccountNewPasswordUpdateError);
			}

		}

		return $bResult;
	}
}

return new CCustomChangePasswordPlugin($this);
