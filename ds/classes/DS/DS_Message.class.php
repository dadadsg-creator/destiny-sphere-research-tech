<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Запросы связаные с игровой почтой
 *
 */
class DS_Message extends DS_Base
{
    
    /**
     * Получение списка сообщений у пользователя
     *
     * @param DE_Userinstance $userInstance
     * @param bool $out флаг исходящие/входящие сообщения
     * @return array
     */
    static public function GetMessages(DE_Userinstance $userInstance, $out)
    {
        assert(is_bool($out));
        $common = self::getCommon();
        
        if($out)
        {
            $where = " AND is_sender";
        } else {
            $where = " AND (NOT is_sender OR is_sender IS NULL)";
        }
        
        $sql = "
            SELECT
                m.id_message,
                m.sender,
                m.reciever,
                m.subject,
                to_char(m.messagedate + '".$userInstance->getUser()->getTimeDelta()." hour'::interval, 'DD.MM HH24:MI') as messagedate,
                COALESCE(to_char(uhm.readdate + '".$userInstance->getUser()->getTimeDelta()." hour'::interval, 'DD.MM HH24:MI'), '---') as readdate,
                m.id_messagescope,
                ltl.text
            FROM
                ds_userhasmessage AS uhm
                INNER JOIN ds_message AS m ON (m.id_message = uhm.id_message)
                INNER JOIN ds_messagescope AS ms ON (ms.id_messagescope = m.id_messagescope)
                INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ms.id_localizedname)
            WHERE
                ltl.id_language = ".$userInstance->getUser()->getLanguageID()."
                AND
                uhm.id_userinstance = ".$userInstance->getID()."
                ".$where."
            ORDER BY m.messagedate DESC
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    /**
     * Есть ли данное сообщение у игрока
     *
     * @param integer $userInstanceID
     * @param integer $messageID
     * @return bool
     */
    static public function UserHaveMessage($userInstanceID, $messageID)
    {
        assert(is_int($messageID) && $messageID > 0);
        assert(is_int($userInstanceID) && $userInstanceID >= 0);
        $common = self::getCommon();
        
        $sql = "
            SELECT
                count(*)
            FROM
                ds_userhasmessage
            WHERE
                id_userinstance = $userInstanceID
                AND
                id_message = $messageID
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if($res[0]['count'] > 0)
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * Данные сообщения по его ID
     *
     * @param integer $messageID
     * @return array
     */
    static public function GetMessageByID($messageID)
    {
        assert(is_int($messageID) && $messageID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT
                sender,
                messagetext,
                subject,
                id_from_userinstance,
                id_messagescope
            FROM
                ds_message
            WHERE
                id_message = $messageID
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0];
        }
        
        return array();
    }
    
    /**
     * Отметить сообщение как прочтенное
     *
     * @param integer $userInstanceID
     * @param integer $messageID
     * @return GB_Date
     */
    static public function MarkMessageAsRead($userInstanceID, $messageID)
    {
        assert(is_int($messageID) && $messageID > 0);
        assert(is_int($userInstanceID) && $userInstanceID >= 0);
        $common = self::getCommon();
        
        $sql = "
            SELECT
                readdate, is_sender
            FROM
                ds_userhasmessage
            WHERE
                id_userinstance = $userInstanceID
                AND
                id_message = $messageID
            ORDER BY is_sender ASC
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        assert(count($res) > 0);

        if(!is_null($res[0]['readdate']) || $res[0]['is_sender'] == 't')
        {
            //$readDate = GB_TypeFactory::CreateBase('GB_Date', $res[0]['readdate']);
            return false;
        }
        
        $readDate = GB_TypeFactory::CreateBase('GB_Date', time());
        
        $sql = "
            UPDATE
                ds_userhasmessage
            SET
                readdate = '".$readDate->getPgFormat()."'
            WHERE
                id_userinstance = $userInstanceID
                AND
                id_message = $messageID
                AND
                readdate IS NULL
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        return $readDate;
    }
    
    /**
     * Имя скопа по его ID
     *
     * @param integer $messageScopeID
     * @return string
     */
    static public function GetMessageScopeNameByID($messageScopeID)
    {
        assert(is_int($messageScopeID) && $messageScopeID > 0);
        $common = self::getCommon();
        $sql = "SELECT messagescopename FROM ds_messagescope WHERE id_messagescope = $messageScopeID";
        $res = $common->getResultArray("messagescopename_id_messagescope_".$messageScopeID, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        assert(count($res) == 1);
        
        return $res[0]['messagescopename'];
    }
    
    static public function GetMessageScopeIDByName($scopeName)
    {
        assert(!empty($scopeName));
        $common = self::getCommon();
        $sql = "SELECT id_messagescope FROM ds_messagescope WHERE messagescopename = '$scopeName'";
        $res = $common->getResultArray("messagescopename_id_messagescope_".$scopeName, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        assert(count($res) == 1);
        
        return (int)$res[0]['id_messagescope'];
    }
    
    /**
     * Есть ли у игрока скоп
     *
     * @param integer $userInstanceID
     * @param integer $messageScopeID
     * @return bool
     */
    static public function UserHaveMessageScope($userInstanceID, $messageScopeID)
    {
        assert(is_int($messageScopeID) && $messageScopeID > 0);
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT 
                count(*)
            FROM 
                ds_userallowedmessagescope
            WHERE 
                id_userinstance = $userInstanceID
                AND
                id_messagescope = $messageScopeID
            ";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));
                
        if($res[0]['count'] > 0)
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * Удалить письмо у всех, кроме данного инстанса
     *
     * @param int $messageID
     * @param int $userInstanceID
     */
    static public function DeleteMessageFromAllExceptUser($messageID, $userInstanceID)
    {
        assert(is_int($messageID) && $messageID > 0);
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        $common = self::getCommon();
        
        $sql = "DELETE FROM ds_userhasmessage WHERE id_message = $messageID AND id_userinstance != $userInstanceID";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    /**
     * Список инстансов в комплексе
     * для отправки комплексного сообщения
     *
     * @param integer $complexID
     * @return array
     */
    static public function GetUserInstancesInComplex($complexID)
    {
        assert(is_int($complexID) && $complexID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT 
                DISTINCT id_userinstance
            FROM 
                ds_comb
            WHERE 
                id_combcomplex = $complexID
                AND 
                NOT id_userinstance IS NULL
            ";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));

        return $res;
    }
    
    /**
     * Добавление в базу нового сообшения
     *
     * @param string $text текст сообщения
     * @param string $subject тема
     * @param string $sender имя отправителя
     * @param string $reciever имя получателя
     * @param integer $messageScopeID
     * @param integer $fromUserInstanceID
     * @return integer ID созданного сообщения
     */
    static public function CreateMessage($text, $subject, $sender, $reciever, $messageScopeID, $fromUserInstanceID)
    {
        assert(!empty($text));
        assert(!empty($subject));
        assert(!empty($sender));
        assert(!empty($reciever));
        assert(is_int($messageScopeID) && $messageScopeID > 0);
        assert(is_int($fromUserInstanceID) && $fromUserInstanceID >= 0);
        
        $common = self::getCommon();
        
        $sql = "select nextval('ds_message_id_message_seq') as id";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) > 0);
        
        $messageID = (int)$res[0]['id'];
        assert($messageID > 0);
        
        $sql = "INSERT INTO ds_message
                (
                    id_message,
                    id_messagescope,
                    id_from_userinstance,
                    subject,
                    messagetext,
                    messagedate,
                    sender,
                    reciever
                ) VALUES (
                    ".$messageID.",
                    ".$messageScopeID.",
                    ".$fromUserInstanceID.",
                    '".pg_escape_string(urldecode($subject))."',
                    '".pg_escape_string(urldecode($text))."',
                    now(),
                    '".pg_escape_string($sender)."',
                    '".pg_escape_string($reciever)."'
                )";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        return $messageID;
    }
    
    /**
     * Приписка сообщения пользователю
     *
     * @param integer $messageID
     * @param integer $userInstanceID
     * @param bool $isSender является ли пользователь отправителем
     */
    static public function AddMessageToUser($messageID, $userInstanceID, $isSender = false)
    {
        assert(is_int($messageID) && $messageID > 0);
        assert(is_bool($isSender));
        
        $common = self::getCommon();

        if($isSender)
        {
            $isSender = 't';
        } else {
            $isSender = 'f';
        }
        
        $sql = "
            INSERT INTO ds_userhasmessage
                (id_message, id_userinstance, is_sender)
            VALUES
                ($messageID, $userInstanceID, '$isSender')
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    /**
     * Список гейм-хелперов в мире
     *
     * @param integer $worldID
     * @return array
     */
    static public function GetGameHelperList($worldID)
    {
        assert(is_int($worldID) && $worldID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT
                gh.id_userinstance
            FROM
                ds_gamehelper AS gh
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
            WHERE
                ui.id_world = $worldID
            ";
        $res = $common->getResultArray("game_helper_list_".$worldID, $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    /**
     * Список гейм-мастеров в мире
     *
     * @param integer $worldID
     * @return array
     */
    static public function GetGameMasterList($worldID)
    {
        assert(is_int($worldID) && $worldID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT
                gh.id_userinstance
            FROM
                ds_gamehelper AS gh
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
            WHERE
                ui.id_world = $worldID
            ";
        $res = $common->getResultArray("game_master_list_".$worldID, $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    /**
     * Удаление привязки сообщения к пользователю
     * само сообщение удаляется по тригеру, когда не останется на него ссылок
     * 
     * @param integer $userInstanceID
     * @param integer $messageID
     */
    static public function DeleteUserMessage($userInstanceID, $messageID)
    {
        assert(is_int($messageID) && $messageID > 0);
        assert(is_int($userInstanceID) && $userInstanceID >= 0);
        $common = self::getCommon();
        $sql = "DELETE FROM ds_userhasmessage WHERE id_userinstance = $userInstanceID AND id_message = $messageID";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    /**
     * Список разрешенных пользователю скопов
     *
     * @param integer $userInstanceID
     * @return array
     */
    static public function GetUserAllowedMessageScopes($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID >= 0);
        $common = self::getCommon();
        $sql = "
            SELECT 
                ms.id_messagescope,
                ms.messagescopename,
                ltl.text as localizedmessagescope
            FROM 
                ds_userallowedmessagescope AS uams
                INNER JOIN ds_messagescope AS ms ON (uams.id_messagescope = ms.id_messagescope)
                INNER JOIN ds_localizedtextlanguage AS ltl ON (ms.id_localizedname = ltl.id_localizedtext)
            WHERE 
                uams.id_userinstance = $userInstanceID
                AND 
                ltl.id_language = ".$_SESSION['lang']."
                AND 
                ms.messagescopename != 'EXTERNAL_ALLIANCE'
            ORDER BY ms.id_messagescope
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function UserHaveNewMessages(DE_Userinstance $userInstance)
    {
        $common = self::getCommon();
        $sql = "
                    SELECT 
                        count(id_message)
                    FROM 
                        ds_userhasmessage
                    WHERE 
                        id_userinstance = ".$userInstance->getID()."
                        AND readdate IS null
                        AND NOT is_sender = true
                    ";
        $res = $common->getResultArray("", $sql, "", "", "");
        return $res[0]['count'];
    }
}

?>