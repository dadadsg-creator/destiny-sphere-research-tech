<?php $CVS_Version = '$Revision$ ; $Date$';

require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

class SU_RemaindPassword
{
    /**
     * Enter description here...
     *
     * @var DE_User
     */
    static private $user;
    
    /**
     * Enter description here...
     *
     * @var string
     */
    static private $error = '';
    
    static private $action = 'index';
    
    static private $newPass;
    
    static private $id;
    
    static private $langc;
    
    static private $langn;  
    
    static public function Remaind(array $data)
    {
        if(!self::_getParams($data))
        {
            self::$action = 'index';
        }
        
        self::_performAction();
    }
    
    static private function _performAction()
    {
        switch (self::$action) 
        {
            case 'index':
                self::_outPage();
                break;
            
            case 'change_login':
                self::_generateNewPassword();
                self::_outPage();
                break;
        
            case 'activate':
                self::_activateNewPassword();
                self::_outPage();
                break;
        }
    }
    
    static private function _getParams(array $data)
    {
    
        $deflang = array('G'=>1,'E'=>2,'R'=>3,'L'=>'5');
        $lang = isset($_REQUEST['lang'])?intval($_REQUEST['lang']):$deflang[constant('DEFAULT_LANGUAGE_SHORT')];
        $lchar = array(1=>'G',2=>'E',3=>'R', 5=>'L');
        self::$langc = $lchar[$lang];  
        self::$langn = $lang;  
    
        if(key_exists('action', $data))
        {
            self::$action = $data['action'];
        
            switch (self::$action) {
                case 'change_login':
                    return self::_validateParamsSend($data);
                    break;
                
                case 'activate':
                    return self::_validateParamsActivate($data);
                    break;
            }
        }
        
        return false;
    }
    
    static private function _validateParamsActivate(array $data)
    {
        assert(key_exists('id', $data));
        assert(key_exists('user', $data));
        
        $id = (int)$data['id'];
        $username = $data['user'];
        
        if($id <= 0)
        {
            self::$error = 'WRONG_ID_FOR_ACTIVATE';
            return false;
        }
        
        if(empty($username))
        {
            self::$error = 'WRONG_USER_FOR_ACTIVATE';
            return false;
        }
        
        $common = DS_Common::CreateForCurentDB(0);
        
        $sql = "
            SELECT
                upc.*,
                md5(u.username) as username
            FROM
                ds_user_password_change AS upc
                INNER JOIN ds_user AS u USING (id_user)
            WHERE
                upc.id_password_change = ".$id."
                AND
                NOT upc.activated
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        if(!empty($common->errormsg))
        {
            self::$error = 'DB_ERROR';
            return false;
        }
        
        if(count($res) != 1)
        {
            self::$error = 'NO_PASSWORD_CHANGE';
            return false;
        }
        
        if($res[0]['username'] != $username)
        {
            self::$error = 'WRONG_USER_FOR_ACTIVATE';
            return false;
        }
        
        self::$user = DE_User::CreateFromId((int)$res[0]['id_user']);
        
        self::$newPass = $res[0]['new_password'];
        
        self::$id = $id;
        
        return true;
    }
    
    static private function _validateParamsSend(array $data)
    {
        $common = DS_Common::CreateForCurrentDB(0);
        if ($data['login']) {
            $sql = "select id_user from ds_user where username='{$data['login']}'";
            $res = $common->getResultArray ( "", $sql, "", "", "");
            assert(empty($common->errormsg));
            if (count($res)) $uid = $res[0]['id_user'];
            else {
                self::$error = 'WRON_LOGIN';
                return false;
            }
        }
        
        else if ($data['e_mail']) {
            $sql = "select id_user from ds_user where email='{$data['e_mail']}'";
            $res = $common->getResultArray ( "", $sql, "", "", "");
            assert(empty($common->errormsg));
            if (count($res)) $uid = $res[0]['id_user'];
            else {
                self::$error = 'WRONG_EMAIL';
                return false;
            }
        }
        else {
            self::$error = 'WRON_LOGIN';
            return false;
        }
        self::$user = DE_User::CreateFromId((int)$uid);

        return true;
    }
    
    static private function _outPage()
    {
        $tpl = new Smarty;
        $tpl->template_dir = TEMPLATES_PATH;
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;
        
        $tpl->assign('MSG', self::$error);
        
        include_once($_SERVER['DOCUMENT_ROOT']."/ds/language/".self::$langc."/new_password.lang");
        if(!is_array($lang))
        {
            self::$error = 'NO_LANG';
            return false;
        }
        $tpl->assign('showlang', self::$langn); 
        $tpl->assign('lang', $lang);
        
        $tpl->display('new_password.tpl');
    }
    
    static private function _generateNewPassword()
    {
        $newPassword = GB_RandomPassword::Generate(8);
        
        $common = DS_Common::CreateForCurentDB(0);
        
        $seqId = DS_Base::getNextSeqVal($common, 'ds_user_password_change_id_password_change_seq');
        
        $sql = "
            INSERT INTO ds_user_password_change
                (id_user, new_password, old_password, activated, id_password_change)
            VALUES
                (".self::$user->getID().", '".md5($newPassword)."', '".self::$user->getPassword()."', 'f', $seqId)
            ";
        $common->executeQuery($sql);
        if(!empty($common->errormsg))
        {
            self::$error = 'DB_ERROR';
            return false;
        }
    
        if(!self::_sendEmail($newPassword, $seqId))
        {
            return false;
        }
        
        self::$error = 'NEW_PASSWORD_SEND';
        return true;
    }
    
    
    static private function _sendEmail($newPass, $id)
    {
        assert(!empty($newPass));
        assert(is_int($id) && $id > 0);
        
        $tpl = new Smarty;
        $tpl->template_dir = TEMPLATES_PATH;
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;
        
        include_once($_SERVER['DOCUMENT_ROOT']."/ds/language/".self::$langc."/new_password_mail.lang");
        if(!is_array($lang))
        {
            self::$error = 'NO_LANG';
            return false;
        }
        
        $tpl->assign('NEW_PASS', $newPass);
        $tpl->assign('LOGIN', self::$user->getName());
        $tpl->assign('lang', $lang);
        $tpl->assign('HREF', constant('GAME_HOST').'/ds/remaind_pass.php?id='.$id.'&user='.md5(self::$user->getName()).'&action=activate&lang='.self::$langn);
        
        $mailBody = $tpl->fetch('new_password_mail.tpl');

        # Common Headers
        $headers .= "From: Destiny Sphere <noreply@destinysphere.ru>\r\n".
        "Return-Path: noreply@destinysphere.ru\r\n".
        "Content-Type: text/html; charset=".constant("DEFAULT_CHARSET");
            
        if(!mail(self::$user->getEmail(), $lang['MAIL_SUBJECT'], $mailBody, $headers))
        {
            self::$error = 'MAIL_PROBLEMS';
            return false;
        }

        /*
        $to = urlencode(self::$user->getEmail());
        $subj = urlencode($lang['MAIL_SUBJECT']);
        $body = urlencode($mailBody);
        $headers = urlencode("From: noreply@dsga.me\r\nContent-type: text/html; charset=".constant("DEFAULT_CHARSET"));
        $queryString = "to=$to&subj=$subj&body=$body&headers=$headers";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://mail.dsga.me/mail.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
        $output = curl_exec($ch);
        curl_close($ch);
        */

        return true;
    }
    
    static private function _activateNewPassword()
    {
        $common = DS_Common::CreateForCurentDB(0);
        
        $sql = "    
            UPDATE 
                ds_user
            SET
                password = '".self::$newPass."'
            WHERE
                id_user = ".self::$user->getID()."
            ";
        $common->executeQuery($sql);
        if(!empty($common->errormsg))
        {
            self::$error = 'DB_ERROR';
            return false;
        }
        
        $sql = "    
            UPDATE 
                ds_user_password_change
            SET
                activated = 't'
            WHERE
                id_user = ".self::$user->getID()."
                AND
                id_password_change = ".self::$id."
            ";
        $common->executeQuery($sql);
        if(!empty($common->errormsg))
        {
            self::$error = 'DB_ERROR';
            return false;
        }
        
        self::$error = 'NEW_PASSWORD_ACTIVATED';
        return true;
    }
}

?>