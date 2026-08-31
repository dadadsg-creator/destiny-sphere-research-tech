<?php $CVS_Version = '$Revision: 1.12 $ ; $Date: 2009/06/08 14:03:14 $';
class SU_DeleteDeadUsers
{
    /**
     * Класс для работы с БД
     *
     * @var DS_Common
     */
    private $common;

    /**
     * Объект для удаления интанса игрока
     *
     * @var DU_ControllerDeleteUserInstance
     */
    private $deleteUserInstance;

    /**
     * ID базы с коротой работем
     *
     * @var unknown_type
     */
    private $DBID;

    /**
     * Количество дней, за которые шлется письмо перед удалением 
     *
     * @var unknown_type
     */
    private $noticePeriod = 7;

    /**
     * Массив с именами игроков, которым необходимо выслать предупреждение
     *
     * @var unknown_type
     */
    private $usersToSendNotice = array();

    /**
     * Массив с локализацией письма
     *
     * @var unknown_type
     */
    private $lang = array();

    /**
     * Счетчик удаления текущей партии
     *
     * @var integer
     */
    private $deleteCounter = 0;

    /**
     * Класс для работы со статитикой. ТОчне отмечаем что удлен пользователь из мира
     *
     * @var DU_AddDeletionToStatistic
     */
    private $statWork;

    /**
     * ID мира, который чиститься
     *
     * @var unknown_type
     */
    private $worldId;

    private $maxExecutionTime = 18000; // 6 часов

    private $maxSQLTime = 1200000; // 20 минут

    private $startTime;

    public function __construct($DBID)
    {
        assert(is_int($DBID) && $DBID >= 0);
        $this->DBID = $DBID;

        $this->startTime = time();
        $this->_checkEnd();
        $this->common = DS_Common::CreateForCurentDB($this->DBID);
        $this->deleteUserInstance = new DU_ControllerDeleteUserInstance($this->common);
        $this->statWork = new DU_AddDeletionToStatistic(0);
    }

    private function _out($msg = '')
    {
        $str = date('Y-m-d H:i:s')." ".$msg."\n";

        if(php_sapi_name() == 'apache2handler')
        {
            echo $str;
            flush();
        }

        SU_DebugTools::WriteToLog($str, 'DeleteDeadUsers_'.$GLOBALS['dbWorldAccess'][$this->DBID]['server'].'.log');
    }

    /** Main method */
    public function Scan()
    {
        require_once($_SERVER['DOCUMENT_ROOT']."/ds/language/".constant("DEFAULT_LANGUAGE_SHORT")."/notice_latter.lang");
        require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";
        $this->lang = $lang;


        $this->_checkEnd();

        $sql = "
            SELECT
                id_world
            FROM
                ds_world
            ";
        $worlds = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        // Проходимся по каждому миру
        foreach ($worlds as $k => $world)
        {
            $this->worldId = (int)$world['id_world'];

            $_SESSION['world'] = $this->worldId;
            $_SESSION['dbId'] = $this->DBID;

            $this->nonEvolutionPeriod = DE_WorldSettings::CreateForCurentWorld()->get('DELETE_DEAD_NO_BUILDINGS_PERIOD');       
            // Модифаеры больше не действуют. Срок жизни задается в таблице с уровнями для каждого уровня отдельно
            // $this->notRefreshPeriod = DE_WorldSettings::CreateForCurentWorld()->get('DELETE_DEAD_NO_REFRESH_PERIOD');
            // $this->notLogInPeriod = DE_WorldSettings::CreateForCurentWorld()->get('DELETE_DEAD_NO_LOGIN_PERIOD');

            $this->_scanWorld();
        }
    }

    private function _scanWorld()
    {
        $this->_out();
        $this->_out("========= START CLEAR DB ".$this->DBID." WORLD ".$this->worldId." =============");

        // Патчим игроков без даты последнего рефреша
        $this->_patchLastLoginDate();

        // Удаляем игроков с тремя домаками
        $this->_deleteNonEvolution();

        // Получаем список дней жизни по уровням
        $sql="  SELECT delete_date
                FROM ds_ranking_level
                GROUP BY delete_date";
        $days = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        foreach ($days as $value) {

            $period = (int)$value['delete_date'];
            // Где ничего не стоит или стоит ноль, не удаляем вообще
            if ($period == 0) {
                continue;
            }

            // Удаляем не рефрешившихся игроков
            $this->_deleteNonRefresh($period);

            // Удаляем не залогинившихся игроков
            $this->_deleteNonLogin($period);

            // Если не Вконтакте, высылаем оповещения
            if (constant('VERSION_NAME') != 'VKONTAKTE') {
                $this->_sendNotice($period-$this->noticePeriod);
            }
        }

        $this->_out();
        $this->_out("================== COMPLIT ======================");
    }//END OF METHOD

    private function _patchLastLoginDate()
    {
        $this->_checkEnd();

        $this->common->executeQuery("set STATEMENT_TIMEOUT to ".$this->maxSQLTime.";");
        assert(empty($this->common->errormsg));

        $this->_out("-------------------------------------------------------------------");
        $this->_out("PATCH USERS WITHOUT LAST LOGIN DATE");
        $this->_out("-------------------------------------------------------------------");

        $sql = "SELECT
                    ui.id_user
                FROM
                    ds_userinstance AS ui
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)             
                WHERE
                    u.admin = false
                    AND
                    ui.id_userinstance > 0              
                    AND NOT EXISTS                  
                    (
                        SELECT id_user
                        FROM  ds_loginhistory
                        WHERE  id_user = u.id_user
                    )
                    AND
                    ui.id_world = ".$this->worldId;
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        $this->_out("Find ".count($res)." users without last login date.");

        if(count($res) > 0)
        {
            $this->common->executeQuery("set STATEMENT_TIMEOUT to ".$this->maxSQLTime.";");
            assert(empty($this->common->errormsg));

            $sql = "INSERT INTO ds_loginhistory
                (id_user, lastvisit, ip, ip_proxy, id_cookie)       
                SELECT
                    ui.id_user,
                    now() - interval '20 days',
                    '1.1.1.1',
                    '1.1.1.1',
                    (
                     SELECT min(id_cookie)
                     FROM ds_logincookie AS e
                    )
                FROM
                    ds_userinstance AS ui
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)             
                WHERE
                    u.admin = false
                    AND
                    ui.id_userinstance > 0              
                    AND NOT EXISTS                  
                    (
                        SELECT id_user
                        FROM  ds_loginhistory
                        WHERE  id_user = u.id_user
                    )
                AND
                    ui.id_world = ".$this->worldId."            
                ORDER BY ui.lastrefresh ASC";
            $this->common->executeQuery($sql);
            assert(empty($this->common->errormsg));
            $this->_out("Users patched.");
        }
    }

    private function _deleteNonEvolution()
    {
        $this->_checkEnd();

        if($this->nonEvolutionPeriod == 0)
        {
            return;
        }

        $this->_out("--------------------------------------------------------------------");
        $this->_out("DELETING USERS WITHOUT BUILDINGS AND INACTIVE FOR > ".$this->nonEvolutionPeriod." DAYS");
        $this->_out("--------------------------------------------------------------------");

        $this->common->executeQuery("set STATEMENT_TIMEOUT to ".$this->maxSQLTime.";");
        assert(empty($this->common->errormsg));

        $sql = "
                SELECT DISTINCT
                    ui.id_user, ui.id_userinstance
                FROM
                    ds_userinstance ui                          
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
                    INNER JOIN ds_comb AS c ON (c.id_userinstance = ui.id_userinstance)
                    INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)                   
                WHERE
                    u.admin = false
                    AND
                    cc.id_complextype != 2 --COLONIZEABLE
                    AND
                    ui.id_userinstance > 0
                    AND
                    (
                        SELECT count(id_attachedbuilding)
                        FROM ds_attachedbuilding
                        WHERE id_comb = c.id_comb
                        AND underconstruction = 'f'
                    ) < 4
                    AND
                    (
                        ui.lastrefresh < now() - interval '".$this->nonEvolutionPeriod." days'
                    )
                    AND NOT EXISTS
                    (
                        SELECT * FROM ds_npc_user AS nu WHERE nu.id_user = u.id_user
                    )
                    AND (u.npc is null or u.npc = 'f')
                    AND
                    ui.id_world = ".$this->worldId;
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));

        $this->_deleteSet($res);
    }

    private function _deleteNonRefresh($notRefreshPeriod)
    {
        $this->_checkEnd();

        if($notRefreshPeriod == 0)
        {
            return;
        }

        $this->_out("-------------------------------------------------------------------");
        $this->_out("DELETING NOT REFRESHED USERS FOR > ".$notRefreshPeriod." DAYS");
        $this->_out("-------------------------------------------------------------------");

        $this->common->executeQuery("set STATEMENT_TIMEOUT to ".$this->maxSQLTime.";");
        assert(empty($this->common->errormsg));

        $sql="
                SELECT DISTINCT ui.id_user, ui.id_userinstance
                FROM
                    ds_userinstance ui                          
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
                    LEFT JOIN ds_ranking AS r ON (r.id_userinstance = ui.id_userinstance)
                WHERE   
                    u.admin = false
                    AND
                    ui.id_userinstance > 0
                    AND 
                    (ui.lastrefresh < now() - interval '".$notRefreshPeriod." days')    
                    AND NOT EXISTS
                    (
                        SELECT id_user
                        FROM ds_userprofidata
                        WHERE
                            id_user = ui.id_user
                            AND
                            active = true
                            AND
                            enddate > now()
                    )
                    AND NOT EXISTS
                    (
                        SELECT * FROM ds_vocationmode WHERE enddate IS NULL AND id_userinstance = ui.id_userinstance
                    )
                    AND NOT EXISTS
                    (
                        SELECT * FROM ds_npc_user AS nu WHERE nu.id_user = u.id_user
                    )                   
                    AND r.id_level IN
                    (
                        SELECT id_level FROM ds_ranking_level AS rl WHERE rl.delete_date = $notRefreshPeriod
                    )
                    AND (u.npc is null or u.npc = 'f')
                    AND ui.id_world = ".$this->worldId;
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        $this->_deleteSet($res);

    }

    private function _getNonLogin($delta)
    {
        assert(is_int($delta));

        $sql = "
                SELECT
                    ui.id_userinstance,
                    u.id_user
                FROM
                    ds_userinstance AS ui
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user) 
                    LEFT JOIN ds_ranking AS r ON (r.id_userinstance = ui.id_userinstance)               
                WHERE
                    u.admin = false
                    AND
                    ui.id_userinstance > 0
                    AND NOT EXISTS
                    (
                        SELECT id_user
                        FROM ds_userprofidata
                        WHERE
                            id_user = ui.id_user
                            AND
                            active = true
                            AND
                            enddate > now()
                    )
                    AND
                    ui.id_user IN
                    (
                        SELECT DISTINCT id_user
                        FROM  ds_loginhistory
                        WHERE  id_user > 0
                        GROUP BY id_user
                        HAVING max(lastvisit) < (now() - interval '".$delta." days') OR max(lastvisit) IS NULL
                    )
                    AND NOT EXISTS
                    (
                        SELECT * FROM ds_vocationmode WHERE enddate IS NULL AND id_userinstance = ui.id_userinstance
                    )
                    AND NOT EXISTS
                    (
                        SELECT * FROM ds_npc_user AS nu WHERE nu.id_user = u.id_user
                    )
                    AND r.id_level IN
                    (
                        SELECT id_level FROM ds_ranking_level AS rl WHERE rl.delete_date = $delta
                    )
                    AND (u.npc is null or u.npc = 'f')
                    AND
                    ui.id_world = ".$this->worldId."
                ORDER BY ui.lastrefresh ASC";

        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));
        return $res;
    }

    private function _sendNotice($noticeSendPeriod)
    {
        $this->_checkEnd();

        // Получаем список игроков, которые будут удалены через 7 дней, если не залогиняться
        $res = $this->_getNonLogin($noticeSendPeriod);
        if($res == 0)
        {
            return;
        }

        // Переключаемся к мастеру
        $_SESSION['dbId'] = 0;

        $this->_out("-------------------------------------------------------------------");
        $this->_out("SEND NOTICE TO NOT LOGGED USERS FOR > ".($noticeSendPeriod)." DAYS");
        $this->_out("-------------------------------------------------------------------");

        foreach($res as $k => $userData)
        {
            $user = DE_User::CreateFromID((int)$userData['id_user']);

            if (!in_array($user->getName(), $this->usersToSendNotice)) {
                $this->_sendNoticeMailToUser($user);

                $str = "Send mail to user : ".$user->getName();
                $this->_out($str);
                $this->usersToSendNotice[] = $user->getName();
            }
        }

    }

    private function _sendNoticeMailToUser(DE_User $user)
    {
        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;

        $tpl->assign('lang', $this->lang);

        $tpl->assign('USERNAME', $user->getName());
        $tpl->assign('PREM_DAYS', $this->noticePeriod);
        $tpl->assign('INDEX_LINK', constant('GAME_HOST')."/ds/index_login.php");
        $tpl->assign('REMAIND_PASS_LINK', constant('GAME_HOST')."/ds/remaind_pass.php");

        $letter = $tpl->fetch('notice_latter.tpl');

        $headers = 'MIME-Version: 1.0' . "\n" . "Content-type: text/plain; charset=UTF-8"
        . "\n" . "From: DestinySphere <noreply@destinysphere.com>\n";

        // Добавляем игроку прем
        if (PU_ExecChangePremiumDays::Exec($user, $this->noticePeriod, 'ACCOUNT_PROLONGED', 'notice befor delete user', true)) {
            //Высылаем на мыло
            mail($user->getEmail(), $this->lang['LATTER_SUBJECT'], $letter, $headers);
        }
    }

    private function _deleteNonLogin($notLogInPeriod)
    {
        $this->_checkEnd();

        if($notLogInPeriod == 0)
        {
            return;
        }

        $this->_out("-------------------------------------------------------------------");
        $this->_out("DELETING NOT LOGGED IN USERS FOR > ".$notLogInPeriod." DAYS");
        $this->_out("-------------------------------------------------------------------");

        $this->common->executeQuery("set STATEMENT_TIMEOUT to ".$this->maxSQLTime.";");
        assert(empty($this->common->errormsg));

        $res = $this->_getNonLogin($notLogInPeriod);

        $this->_deleteSet($res);
    }


    private function _checkEnd()
    {
        $res = true;

        if(GameDown($this->DBID, $this->worldId))
        {
            $res = false;
            $msg = 'GAME DOWN';
        }

        if((time() - $this->startTime) > $this->maxExecutionTime)
        {
            $res = false;
            $msg = 'TIME LIMIT';
        }

        if(!$res)
        {
            $this->_out('------------------- STOP: '.$msg.' -------------------');
            $this->_out('');
            exit;
        }

        return $res;
    }

    private function _deleteSet($res)
    {
        assert(is_array($res));

        // Не удаляем платящих хоть что-то
        $ids = array();
        foreach ($res as $row) $ids[] = $row['id_user'];
        $payments = json_decode(file_get_contents("https://billing.destinysphere.ru/gate/payers.php?&v=".VERSION_NAME."&ids=".implode(',',$ids)));
        for ($i=count($res)-1; $i>=0; $i--) {
            if (isset($payments->$res[$i]['id_user'])) unset($res[$i]);
        }

        $this->_out("Selected ".count($res)." users to delete");
        $this->deleteCounter = count($res);

        foreach($res as $k => $userData)
        {
            if(!$this->_checkEnd())
            {
                return;
            }
            $this->_deleteInstance((int)$userData['id_userinstance']);
        }
    }

    private function _deleteInstance($UIID)
    {
        assert(is_int($UIID) && $UIID > 0);

        $userData = $this->_getUserData($UIID);
        assert(is_array($userData) && count($userData) > 0);

        $str = "№".$this->deleteCounter." User: [".$userData['username']."] World: [".$userData['worldname']."] UIID: ".$UIID." Buildings: ".$userData['buildings_count']." Profi: ".$userData['profi'];
        $this->_out($str);

        $str = "RegDate: ".$userData['registrationdate']." LastRefresh: ".$userData['lastrefresh']." LastVisit: ".$userData['lastvisit'];
        $this->_out($str);

        $this->common->executeQuery("begin");
        assert(empty($this->common->errormsg));

        $this->deleteUserInstance->drop($UIID);

        $this->common->executeQuery("commit");
        assert(empty($this->common->errormsg));

        $this->statWork->add($this->DBID, $userData['worldname'], (int)$userData['id_world'], (int)$userData['profi']);

        // Логируем удаление игрока
        $this->_logDeleteUser($userData);

        $this->deleteCounter--;
    }

    private function _getUserData($UIID)
    {
        $this->common->executeQuery("set STATEMENT_TIMEOUT to ".$this->maxSQLTime.";");
        assert(empty($this->common->errormsg));

        $sql = "
            SELECT
                u.id_user,
                w.worldname,
                w.id_world,
                w.world_identifier,
                u.username,
                u.registrationdate,
                ui.lastrefresh,
                (
                    SELECT max(lastvisit)
                    FROM  ds_loginhistory
                    WHERE  id_user = u.id_user
                ) AS lastvisit,
                (
                    SELECT count(ab.id_attachedbuilding)
                    FROM ds_attachedbuilding AS ab
                    WHERE
                        ab.id_comb IN
                        (
                            SELECT id_comb
                            FROM ds_comb
                            WHERE id_userinstance = $UIID
                        )
                ) AS buildings_count,
                COALESCE
                (
                    (
                        SELECT
                            1
                        FROM                        
                            ds_userprofidata
                        WHERE                       
                            id_user = u.id_user
                            AND 
                            active = 't'
                            AND 
                            enddate > now()
                            AND
                            profi = 't'
                
                    ), 0
                )AS profi
            FROM
                ds_userinstance AS ui
                INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
                INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
            WHERE ui.id_userinstance = $UIID";
        $res = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg) && count($res) > 0);

        return $res[0];
    }

    private function _logDeleteUser($userData)
    {
        assert(is_array($userData) && count($userData) > 0);

        $userId = $userData['id_user'];
        $worldId = $userData['world_identifier'];
        $buildingsCount = (int)$userData['buildings_count'];
        $profi = $userData['profi'];
        $lastrefresh = $userData['lastrefresh'];
        $lastvisit = $userData['lastvisit'];

        $field = '';
        $values = '';

        if (!empty($lastrefresh)) {
            $field .= ", lastrefresh";
            $values .= ", '$lastrefresh'";
        }

        if (!empty($lastvisit)) {
            $field .= ", lastvisit";
            $values .= ", '$lastvisit'";
        }

        $sql = "
        INSERT INTO ds_user_delete_log
        (id_user, world_identifier, buildings_count, profi $field)
        VALUES
        ($userId, '$worldId', $buildingsCount, '$profi' $values)";

        $this->common->executeQuery("$sql");
        assert(empty($this->common->errormsg));
    }
}//END OF CLASS

?>