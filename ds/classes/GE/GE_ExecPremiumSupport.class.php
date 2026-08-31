<?php $CVS_Version = '$Revision$ ; $Date$';

define('MAX_SUPPORT_MESSAGES', 10);

class GE_ExecPremiumSupport
{
	static public function Exec(DE_User $user, $data)
	{
		assert(key_exists('profi_game', $_SESSION) && $_SESSION['profi_game']);
	
		assert(key_exists('world', $data));
		//$tmp = explode("_", $data['world']);
		//assert(count($tmp) == 4);
		$worldName = $data['world'];
			
		assert(key_exists('message', $data));
		$message = trim($data['message']);
		
		if(empty($message))
		{
			return 'EMPTY_SUPPORT_MESSAGE';
		}
		
		$mailBody = "Login:          ".$user->getName()." (".$user->getID().")\n".
					"World:          ".$worldName."\n".
					"E-Mail:         ".$user->getEmail()."\n".
					"Account active: ".$user->getAccountActive()."\n".
					"Reg date:       ".$user->getRegDate()->getPgFormat()."\n".
					"Time delta:     ".$user->getTimeDelta()."\n".
					"Lang:           ".$user->getLangShortName()."\n".
					"Request date:   ".date("Y-d-m")."\n".
					"IP:             ".@$_SERVER['REMOTE_ADDR']."\n".
					"HTTP_FORWARDED: ".@$_SERVER['HTTP_X_FORWARDED_FOR']."\n".
					"\n************MESSAGE*************\n".$message.
					"\n**************END***************";	
		
		
		if(key_exists('send_support_messages', $_SESSION))
		{
			if($_SESSION['send_support_messages'] >= constant('MAX_SUPPORT_MESSAGES'))
			{
				return 'MESSAGE_LIMIT';
			}			
		} else {
			$_SESSION['send_support_messages'] = 0;
		}
					
		if(mail(constant('PREMIUM_SUPPORT_EMAIL'), "PREMIUM SUPPORT REQUEST FROM INDEX_START", $mailBody, "Content-type: text/plain; charset=windows-1251\r\nfrom: ".$user->getEmail()."\r\n"))
		{
			$_SESSION['send_support_messages']++;
			return 'MESSAGE_SEND_OK';
		} else {
			return 'MESSAGE_SEND_ERROR';
		}
	}
}
?>