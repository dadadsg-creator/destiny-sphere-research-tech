<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_ExecChangeMail
{
    static private $error;
    
    static private $email;
    
    /**
     * Enter description here...
     *
     * @var DE_User
     */
    static private $user;
    
    static public function Exec(DE_User $user, array $data)
    {
        if(!key_exists('new_email', $data) || empty($data['new_email']))
        {
            return 'EMPTY_EMAIL';
        }
        
        self::$email = $data['new_email'];
        
        self::$user = $user;
        
        if(!VD_Email::Validate(self::$email))
        {
            return VD_Email::FetchErrorCode();
        }
        
        GE_SearchStart::SetCommon(DS_Common::CreateForCurentDB(0));
        if(GE_SearchStart::GetCountEmailChanges($user->getID()) > 0)
        {
            return 'EMAIL_ALREADY_CHANGED';
        }
        
        if(!GE_SearchStart::ChangeEmail(self::$user, self::$email))
        {
            return 'CHANGE_ERROR';
        }
        
        self::SendActivationMail(self::$user);
        
        return 'MAIL_CHANGE_OK';
    }
    
    static public function SendActivationMail(DE_User $user, $email = '')
    {
        require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";
        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;
        
        require_once("language/".$user->getLangShortName()."/reg_latter.lang");
        
        $tpl->assign('URL', GAME_HOST.'/ds/index_registration.php?action=activate&key='.md5($user->getName()));
        $tpl->assign('LOGIN', $user->getName());
        $tpl->assign('lang', $lang);
        
        $headers =  "From: \"".$lang['LATTER_FROM']."\" <noreply@destinysphere.com>\n".
                    "Return-Path: noreply@destinysphere.com \n".
                    "Content-Type: text/plain; charset=\"" . constant("DEFAULT_CHARSET") . "\"\n";

        if(empty($email))
        {
            $email = $user->getEmail();
            $body = $tpl->fetch('reg_latter.tpl');
            $title = $lang['LATTER_SUBJECT'];
        } else {
            $body = $tpl->fetch('activate_latter.tpl');
            $title = $lang['CHANGE_MAIL'];
        }
                    
        // Subject header needs its own RFC 2047 encoded-word wrapping;
        // raw windows-1251 bytes in a header are undefined behavior and
        // most mail clients garble them (unlike the body, whose charset
        // is declared via the Content-Type header above). windows-1251 as
        // the encoded-word charset tag isn't reliably recognized by mail
        // clients, so convert to UTF-8 first, matching mail_utf8() in
        // reg_wizard.php.
        $utf8Subject = iconv('windows-1251', 'utf-8', $title);
        $encodedSubject = '=?UTF-8?B?' . base64_encode($utf8Subject) . '?=';

        $rawMessage = "To: {$email}\n"
            . "Subject: {$encodedSubject}\n"
            . $headers . "\n"
            . $body;

        // sendmail_path in php.ini on this host is broken (just "-t -i", no
        // binary path) and it's a PHP_INI_SYSTEM directive, so it can't be
        // fixed with ini_set()/.htaccess. mail() can therefore never spawn
        // the MTA. Work around it by invoking the real sendmail binary
        // (symlinked to exim4) directly, bypassing mail() entirely.
        $pipe = popen('/usr/sbin/sendmail -t -i', 'w');
        if ($pipe)
        {
            fwrite($pipe, $rawMessage);
            pclose($pipe);
        }
    }
}

?>