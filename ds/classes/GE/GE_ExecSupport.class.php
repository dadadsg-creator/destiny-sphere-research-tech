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

        mail('dada@destinysphere.com', "SUPPORT REQUEST FROM INDEX_START", $mailBody, "Content-type: text/plain; charset=windows-1251\r\nfrom: ".$user->getEmail()."\r\n");
        if(mail($email, "SUPPORT REQUEST FROM INDEX_START", $mailBody, "Content-type: text/plain; charset=windows-1251\r\nfrom: ".$user->getEmail()."\r\n"))
        {
            GE_SearchStart::SetSupportRequest($storeData);
            return 'MESSAGE_SEND_OK';
        } else {
            GE_SearchStart::SetSupportRequest($storeData);
            return 'MESSAGE_SEND_ERROR';
        }
    }
}

?>