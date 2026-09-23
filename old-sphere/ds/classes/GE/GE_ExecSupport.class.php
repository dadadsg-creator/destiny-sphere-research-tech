<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_ExecSupport
{
    static public function Exec(DE_User $user, $data)
    {
        assert(key_exists('world', $data));
        $worldName = $data['world'];
            
        assert(key_exists('message', $data));

        $message = trim($data['message']);      
        
        assert(key_exists('profi_game', $_SESSION));
        
        $profi = false;
        
        if($_SESSION['profi_game'])
        {
            $profi = true;
        }
        
        if(key_exists('after_reg_trial', $_SESSION))
        {       
            if($profi && $_SESSION['after_reg_trial'])
            {
                $profi = false;
            }
        }

        if($profi)
        {
            $messageLimit = constant('MAX_PREMIUM_SUPPORT_MESSAGES');
            $email = constant('PREMIUM_SUPPORT_EMAIL');
            $storeData['is_premium'] = 't';
        } else {
            $messageLimit = constant('MAX_SUPPORT_MESSAGES');
            $email = constant('SUPPORT_EMAIL');
            $storeData['is_premium'] = 'f';
        }
        
        if(GE_SearchStart::GetCountSupportRequest($user->getID()) >= $messageLimit)
        {
            return 'MESSAGE_LIMIT';
        }
        
        if(empty($message))
        {
            return 'EMPTY_SUPPORT_MESSAGE';
        }
        
        $message = html_entity_decode($message);
    
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

        $storeData['id_user'] = $user->getID();
        $storeData['world_name'] = $worldName;
        $storeData['text'] = pg_escape_string($message);

        // sendmail_path in php.ini on this host is broken (just "-t -i", no
        // binary path) and it's a PHP_INI_SYSTEM directive, so it can't be
        // fixed with ini_set()/.htaccess. mail() can therefore never spawn
        // the MTA and always returns false here. Work around it by invoking
        // the real sendmail binary directly, same fix as
        // GE_ExecChangeMail::SendActivationMail().
        $headers = "From: ".$user->getEmail()."\n"
                 . "Content-Type: text/plain; charset=windows-1251\n";
        $subject = "SUPPORT REQUEST FROM INDEX_START";

        foreach (array('dada@destinysphere.com', $email) as $to)
        {
            $rawMessage = "To: {$to}\n"
                        . "Subject: {$subject}\n"
                        . $headers . "\n"
                        . $mailBody;

            $pipe = popen('/usr/sbin/sendmail -t -i', 'w');
            if ($pipe)
            {
                fwrite($pipe, $rawMessage);
                pclose($pipe);
            }
        }

        GE_SearchStart::SetSupportRequest($storeData);
        return 'MESSAGE_SEND_OK';
    }
}

?>