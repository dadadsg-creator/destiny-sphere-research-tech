<?php $CVS_Version = '$Revision$ ; $Date$';

class VD_User extends VD_Base
{   
    static public function Validate($userName, $password, DS_Common $common)
    {           
        if(!VD_UserName::Validate($userName, false, $common))
        {           
            return false;
        }       
        
        if(!VD_Password::Validate($password))
        {
            return false;
        }

        $sql = sprintf("select id_user, activate_mail from ds_user
            where username = '%s' and password = '%s'",
            pg_escape_string($userName),
            md5($password));
        $res = $common->getResultArray ("", $sql, "", "", "" );
        if (!count($res)) {
            self::$errorCode = '5_WRONG_USER_NAME_OR_PASSWORD';
            return false;
        }

        if ($res[0]['activate_mail']==0 && $_COOKIE['partner']==139) return false;
        $userID = (int)$res[0]['id_user'];

        // $userID = (int)DS_Validate::GetUserIdByUserNameAndPassword($userName, $password, $common);
        
        return $userID;
    }
}

?>