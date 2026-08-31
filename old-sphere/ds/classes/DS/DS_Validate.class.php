<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Статический класс-контейнер SQL запросов для пакета VD
 *
 */
class DS_Validate extends DS_Base
{

    /**
     * Проверка есть ли юзер с таким именем
     *
     * @param unknown_type $userName
     * @param unknown_type $common
     * @return unknown
     */
    static public function GetUserIdByUserName($userName, $common = false)
    {
        assert(!empty($userName));

        if(!$common)
        {
            $common = self::getCommon();
        } else {
            assert(is_a($common, "DS_Common"));
        }

        $sql = "
            SELECT
                u.id_user
            FROM
                ds_user AS u
            WHERE
                upper(u.username) = upper('".pg_escape_string($userName)."')
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        } else {
            return $res[0]['id_user'];
        }
    }


    /**
     * Проверка верный ли логин и пароль
     *
     * @param unknown_type $userName
     * @param unknown_type $password
     * @param unknown_type $common
     * @return unknown
     */
    static public function GetUserIdByUserNameAndPassword($userName, $password, $common = false)
    {
        assert(is_string($userName));
        assert(is_string($password));

        if(!$common)
        {
            $common = self::getCommon();
        } else {
            assert(is_a($common, "DS_Common"));
        }

        $sql = sprintf("
            SELECT
                u.id_user
            FROM
                ds_user AS u
            WHERE
                u.username = '%s'
                AND password = '%s'",
            $userName,
            md5($password) );

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        } else {
            return $res[0]['id_user'];
        }
    }

    static public function GetUIID($UIID, $UID, $common = false)
    {
        assert(is_int($UIID) && $UIID>0);
        assert(is_int($UID) && $UID>0);

        if(!($common instanceof DS_Common))
        {
            $common = self::getCommon();
        }

        $sql = "
            SELECT
                ui.id_userinstance
            FROM
                ds_userinstance AS ui
            WHERE
                ui.id_userinstance = $UIID
                AND
                ui.id_user = $UID";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        } else {
            return $res[0];
        }
    }

    static public function GetCombName($combName, $common = false)
    {
        assert(!empty($combName));

        if(!$common)
        {
            $common = self::getCommon();
        } else {
            assert(is_a($common, "DS_Common"));
        }

        $sql = "
            SELECT
                cn.id_combname
            FROM
                ds_combname AS cn
            WHERE
                upper(cn.combname) = upper('$combName')
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        } else {
            return $res[0];
        }
    }

    static public function GetEmail($email, DS_Common $common)
    {
        assert(!empty($email));

        $sql = "
            SELECT
                count(*)
            FROM
                ds_user
            WHERE
                upper(email) = upper('".$email."')
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if($res[0]['count'] > 0)
        {
            return true;
        } else {
            return false;
        }
    }
}

?>