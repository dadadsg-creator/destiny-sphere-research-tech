<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2007/12/28 16:34:30 $';
/*
* transfer_type
*   0 - free
*   1 - admin
*   2 - billing
*   5 - reciever auto
*/
require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

class SU_FreeWorldTransfer
{
    private $dbIdFrom;

    private $dbIdTo = -1;

    private $worldIdFrom;

    private $worldIdTo = -1;

    private $common;

    private $uid;

    private $action;

    private $error;

    private $criticalError = true;

    private $profi = false;

    private $worlds = false;

    private $worldFromSelected = false;

    private $combCoordsError = '';

    private $reserveComb = false;

    private $reserveCombMels = array();

    private $transferOrdered = false;

    // Ўаблон
    private $tpl = false;

    //  олони€, котора€ в результате переезда станет захватываемой
    private $tempColony = false;

    // через сколько часов возможен следующий бесплатный переезд
    private $freeTransferPeriod = 168;

    public function __construct($data)
    {
        $this->_getParams($data);

        switch($this->action)
        {
            case 'add':
                $this->_transfer();
                break;
            case 'cancel':
                $this->_cancelTransfer();
                break;
            default:
                $worldsToEnter = $this->_getUserWorlds();

                if(count($worldsToEnter) == 1)
                {
                    $this->worldIdFrom = (int)$worldsToEnter[0]['id_world'];
                    $this->dbIdFrom = (int)$worldsToEnter[0]['dbId'];
                } elseif(!$this->worldFromSelected) {
                    $this->worlds = $worldsToEnter;
                }
        }

        $this->_out();
    }

    private function _getParams($data)
    {
        assert(is_array($data));
    
        if(key_exists('world_from', $data))
        {
            $arr = explode('_', $data['world_from']);
            assert(count($arr) == 2);

            $this->worldIdFrom = (int)$arr[0];
            $this->dbIdFrom = (int)$arr[1];

            assert($this->worldIdFrom > 0);
            assert($this->dbIdFrom >= 0);

            $this->worldFromSelected = true;
        }

        $this->common = DS_Common::CreateForCurentDB(0);

        assert(key_exists('uid', $_SESSION));
        $this->uid = (int) $_SESSION['uid'];

        $this->profi = DS_User::IsPremium($this->uid);

        if(key_exists('world', $data))
        {
            $arr = explode("_", $data['world']);
            assert(count($arr) == 2);

            $this->dbIdTo = (int)$arr[0];
            assert($this->dbIdTo >= 0);

            $this->worldIdTo = (int)$arr[1];
            assert($this->worldIdTo > 0);

            $this->action = 'add';
        }

        if(key_exists('coorld_mels', $data))
        {
            $oldDbId = $_SESSION['dbId'];
            $_SESSION['dbId'] = $this->dbIdTo;
            
            foreach ($data['coorld_mels'] as $idComb => $coords)
            {
                $idComb = (int)$idComb;
                assert($idComb > 0);
                
                if (!empty($coords['complex']) && !empty($coords['comb'])) {
                    $ring = 4;
                    $complex = (int)$coords['complex'];
                    $comb = (int)$coords['comb'];

                    if($comb > 6 || $comb < 1)
                    {
                        $this->error = 'NO_COMB';
                        $this->criticalError = false;
                        $this->action = '';
                        $_SESSION['dbId'] = $oldDbId;
                        return;
                    }
                    
                    $targetComb = DE_Comb::CreateFromCoordinates($this->worldIdTo, $ring, $complex, $comb);

                    if($targetComb instanceof DE_CombNull)
                    {
                        $this->error = 'NO_COMB';
                        $this->criticalError = false;
                        $_SESSION['dbId'] = $oldDbId;
                        $this->action = '';
                        return;
                    }

                    if($targetComb->getUserInstanceId() != 0)
                    {
                        $this->error = 'NOT_EMPTY';
                        $this->criticalError = false;
                        $_SESSION['dbId'] = $oldDbId;
                        $this->action = '';
                        return;
                    }

                    if(!DS_WorldTransfer::checkCombReservation($this->dbIdTo, $targetComb->getID()))
                    {
                        $this->error = 'RESERVED';
                        $this->criticalError = false;
                        $_SESSION['dbId'] = $oldDbId;
                        $this->action = '';
                        return;
                    }
                    $this->reserveCombMels[$idComb] = $targetComb;
                }
            }
            $_SESSION['dbId'] = $oldDbId;
        }

        if(key_exists('complex', $data) && key_exists('comb', $data))
        {
            if(!empty($data['complex']) || !empty($data['comb']))
            {
                $ring = DS_WorldTransfer::getUserBaseRing($this->dbIdFrom, $this->uid);
                $complex = (int)$data['complex'];
                $comb = (int)$data['comb'];

                if($comb > 6 || $comb < 1)
                {
                    $this->error = 'NO_COMB';
                    $this->criticalError = false;
                    $this->action = '';
                    return;
                }

                $oldDbId = $_SESSION['dbId'];
                $_SESSION['dbId'] = $this->dbIdTo;
                $targetComb = DE_Comb::CreateFromCoordinates($this->worldIdTo, $ring, $complex, $comb);

                if($targetComb instanceof DE_CombNull)
                {
                    $this->error = 'NO_COMB';
                    $this->criticalError = false;
                    $_SESSION['dbId'] = $oldDbId;
                    $this->action = '';
                    return;
                }

                if($targetComb->getUserInstanceId() != 0)
                {
                    $this->error = 'NOT_EMPTY';
                    $this->criticalError = false;
                    $_SESSION['dbId'] = $oldDbId;
                    $this->action = '';
                    return;
                }

                if(!DS_WorldTransfer::checkCombReservation($this->dbIdTo, $targetComb->getID()))
                {
                    $this->error = 'RESERVED';
                    $this->criticalError = false;
                    $_SESSION['dbId'] = $oldDbId;
                    $this->action = '';
                    return;
                }

                $this->reserveComb = $targetComb;
                $_SESSION['dbId'] = $oldDbId;
            }
        }

        if(key_exists('temp_colony', $data))
        {
            $this->tempColony = (int)$data['temp_colony'];
            assert($this->tempColony) > 0;
        }

        if(key_exists('cancel', $data))
        {
            $this->action = 'cancel';
        }

    }

    private function _transfer()
    {
        if($this->_checkSourceWorldForTransfer() && $this->_checkTargetWorldForTransfer($this->dbIdTo, $this->worldIdTo, true))
        {
            $this->_setTransferInfo();
        }
    }

    private function _checkSourceWorldForTransfer()
    {
        if ($this->dbIdTo==13) return true;

        // есть ли истанс в мире
        if(!DS_WorldTransfer::getPresenceInWorld($this->dbIdFrom, $this->worldIdFrom, $this->uid))
        {
            return false;
        }

        // нет ли уже переездов из выбранного мира
        $info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);
        if(count($info) > 0)
        {
            if($info[0]['status'] > 0)
            {
                return false;
            }
        }

        /**************** ƒЋя  ¬≈—“ј ******************/
        // —мотрим, не заказали ли мы уже переезд из этого мира в квестовый
        // „то бы не было того, что к тому времени, как будет осуществл€тьс€ копирование в квертовый мир,
        //нас уже не було в исходном
        $info = DS_WorldTransfer::getTransferQuestInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);
        if(count($info) > 0)
        {
            if($info[0]['status'] == 1)
            {
                return false;
            }
        }
        /**********************************************/

        if(DS_WorldTransfer::isTransferFromWorldDisabled($this->dbIdFrom, $this->worldIdFrom, $this->uid))
        {
            return false;
        }

        return true;
    }

    /**
     * ѕроверка можно ли переехать в определенный мир
     *
     * @param int $dbId
     * @param int $worldId
     * @return bool
     */
    private function _checkTargetWorldForTransfer($dbIdTo, $worldIdTo, $onlyChek)
    {
        // не заказал ли уже переезд в этот мир
        $res = DS_WorldTransfer::getUserTransferToWorld((int)$dbIdTo, (int)$worldIdTo, $this->uid);
        if(count($res) > 0)
        {
            return false;
        }

        if ($this->dbIdTo==13) return true;

        // есть ли истанс в мире
        if(DS_WorldTransfer::getPresenceInWorld((int)$dbIdTo, (int)$worldIdTo, $this->uid))
        {
            return false;
        }

        // разрешен ли переезд
        $res = DS_WorldTransfer::getInterWorldTransferInfo((int)$this->dbIdFrom, (int)$this->worldIdFrom, (int)$dbIdTo, (int)$worldIdTo);

        if(count($res) == 0)
        {
            return false;
        } else {
            // только дл€ премиумов, а игрок без премиума
            if($res[0]['only_for_premium'] == 't' && !$this->profi)
            {
                return false;
            }

            // преезд только за бабло или по разрешению
            if($res[0]['free'] == 'f' && !DS_WorldTransfer::isUserAllowedWorldTransfer((int)$this->dbIdFrom, $this->uid))
            {
                return false;
            }
        }

        // —мотрим разрешенную частоту преезда между мирами
        $this->freeTransferPeriod = DS_WorldTransfer::getTransferFrequency((int)$this->dbIdFrom, (int)$this->worldIdFrom,  (int)$dbIdTo, (int)$worldIdTo);
        assert(is_int($this->freeTransferPeriod) && $this->freeTransferPeriod >= 0);

        // ≈сли ограничение на частоту переездов есть
        if ($this->freeTransferPeriod != 0) {
            if (!DS_WorldTransfer::checkTransferFrequency($this->uid, $this->freeTransferPeriod, (int)$this->dbIdFrom, (int)$this->worldIdFrom,  (int)$dbIdTo, (int)$worldIdTo)) {
                return false;
            }
        }

        // провер€ем кол-во колоний
        $countCombs = DS_WorldTransfer::getCountUserCombs((int)$this->dbIdFrom, (int)$this->worldIdFrom, $this->uid);
        $oldDbId = $_SESSION['dbId'];
        $oldWorld = 0;
        if(key_exists('world', $_SESSION))
        {
            $oldWorld = $_SESSION['world'];
        }

        $_SESSION['dbId'] = $dbIdTo;
        $_SESSION['world'] = $worldIdTo;
        $countCombsAllowed = DE_WorldSettings::CreateForCurentWorld()->get('MAX_COLONIES') + 1;

        $_SESSION['dbId'] = $oldDbId;
        $_SESSION['world'] = $oldWorld;

        // ≈сли количество колоний в новаом мире меньше числа колоний игрока больше чем на 2
        // чтобы убрать запрет, надо сделать возможность игроку выбрать сказу несколько колоний, которые станут временными
        if(($countCombs - $countCombsAllowed) > 1)
        {
            return false;
        }

        // ¬место запрета переезда в мир, с меньшим числом колоний
        // даем выбрать ону колонии на выбор
        if($countCombs > $countCombsAllowed)
        {
            $_SESSION['dbId'] = (int)$this->dbIdFrom;
            $_SESSION['world'] = (int)$this->worldIdFrom;

            // ≈сли у нас проверка мира
            if ($onlyChek) {
                // ѕровер€ем, что у нас выбрана, кака€ колони€ станет временной
                assert($this->tempColony) > 0;
                // ѕровер€ем, что выбрана€ колони€ принадлежит данному игроку
                assert(DS_WorldTransfer::IsUserHasColony($this->uid, (int)$this->worldIdFrom, $this->tempColony));
            } else {
                // ≈сли у нас заказ переезда
                //return false;
                $this->tpl->assign('TEMP_COLONIES', 1);

                // ¬се колонии игрока
                $userColonies = DS_WorldTransfer::getUserColonies($this->uid, (int)$this->worldIdFrom);

                $this->tpl->assign('USER_COLONIES', $userColonies);
            }
            //SU_DebugTools::LogAll($userColonies, 'Annita.log');
        } else {
            $this->tempColony = false;
        }

        $_SESSION['dbId'] = $oldDbId;
        $_SESSION['world'] = $oldWorld;

        return true;
    }

    private function _getWorldList()
    {
        //GE_SearchStart::SetCommon($this->common);
        //$worlds = GE_SearchStart::GetWorldCollection(constant('DEFAULT_LANGUAGE'));
        $worlds = DS_WorldTransfer::getWorldCollection();

        foreach($worlds as $k => $world)
        {
            $dbId = $this->common->getDbIDByName($world['dbname']);
            $worlds[$k]['dbId'] = $dbId;

            $canTransfer = $this->_checkTargetWorldForTransfer($dbId, $world['id_world'], false);

            if(($this->dbIdFrom == $dbId && $this->worldIdFrom == $world['id_world']) ||
            !$canTransfer /*|| $world['max_users'] == 0*/)
            {
                unset($worlds[$k]);
            }
        }

        return $worlds;
    }

    private function _canCancelTransfer()
    {
        $info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);

        if(count($info > 0) && $info[0]['status'] == 1)
        {
            return (int)$info[0]['id_world_to'];
        }

        return 0;
    }

    private function _cancelTransfer()
    {
        $res = array();

        $info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);
        $id_worldtransfer = $info[0]['id_worldtransfer'];

        $idWorldTo = (int)$info[0]['id_world_to'];

        if(!$idWorldTo)
        {
            $this->error = 'NO_TRANSFER';
            return false;
        }

        $sql = "
            UPDATE 
                ds_worldtransfer
            SET
                status = 0
            WHERE
                id_user = ".$this->uid."
                AND
                transfer_type = 0 -- бесплатный переезд
                AND
                status = 1
                AND
                db_from = '".$GLOBALS['dbWorldAccess'][$this->dbIdFrom]['server']."'
                AND
                id_world_from = ".$this->worldIdFrom."
            ";
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
        
        
        // ”д€лаем св€зь заказанных координат
        $sql = "
                DELETE FROM 
                    ds_worldtransfercoordrelation
                WHERE
                    id_worldtransfer = $id_worldtransfer";
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));

        $dbIdTo = -1;

        foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
        {
            if($db['server'] == $info[0]['db_to'])
            {
                $dbIdTo = $dbId;
            }
        }

        if($dbIdTo == -1)
        {
            return;
        }

        // ”дал€ем резервирование соты
        $sql = "
            DELETE FROM
                ds_reservedcomb
            WHERE
                username = '".DE_User::CreateFromID($this->uid)->getName()."'
                AND
                (
                    SELECT
                        r.id_world
                    FROM
                        ds_combcomplex AS cc
                        INNER JOIN ds_comb AS c USING (id_combcomplex)
                        INNER JOIN ds_ring AS r USING (id_ring)
                    WHERE
                        c.id_comb = ds_reservedcomb.id_comb
                ) = ".$idWorldTo."
            ";
        $this->common->setDbId($dbIdTo);
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
    }

    private function _setTransferInfo()
    {
        $info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);

        if(count($info) > 0)
        {
            if($info[0]['status'] == 0)
            {
                $id_worldtransfer = $info[0]['id_worldtransfer'];
                
                $where = "";
                if (!empty($this->tempColony)) {
                    $where  = "
                        , temp_colony = ".$this->tempColony;

                }

                $sql = "
                    UPDATE
                        ds_worldtransfer
                    SET
                        db_to = '".$GLOBALS['dbWorldAccess'][$this->dbIdTo]['server']."',
                        id_world_to = ".$this->worldIdTo.",
                        status = 1,
                        transfer_date = now()
                        $where
                    WHERE
                        id_user = ".$this->uid."
                        AND
                        transfer_type = 0
                        AND
                        db_from = '".$GLOBALS['dbWorldAccess'][$this->dbIdFrom]['server']."'
                        AND
                        id_world_from = ".$this->worldIdFrom."
                ";
            } else {
                return false;
            }
        } else {
            
            $sql  = "SELECT nextval('ds_worldtransfer_id_worldtransfer_seq') as id";
            $res = $this->common->getResultArray('', $sql, '', '', 'NO_CACHE');
            assert(empty($this->common->errormsg));
            $id_worldtransfer = $res[0]['id'];

            if (!empty($this->tempColony)) {
                $sql = "
                INSERT INTO ds_worldtransfer
                    (id_worldtransfer, id_user, db_from, db_to, id_world_from, id_world_to, transfer_type, status, transfer_date, temp_colony)
                VALUES
                    ($id_worldtransfer, ".$this->uid.", '".$GLOBALS['dbWorldAccess'][$this->dbIdFrom]['server']."', '".$GLOBALS['dbWorldAccess'][$this->dbIdTo]['server']."', ".$this->worldIdFrom.", ".$this->worldIdTo.", 0, 1, now(), $this->tempColony)
            ";                  
            } else {
                $sql = "
                INSERT INTO ds_worldtransfer
                    (id_worldtransfer, id_user, db_from, db_to, id_world_from, id_world_to, transfer_type, status, transfer_date)
                VALUES
                    ($id_worldtransfer, ".$this->uid.", '".$GLOBALS['dbWorldAccess'][$this->dbIdFrom]['server']."', '".$GLOBALS['dbWorldAccess'][$this->dbIdTo]['server']."', ".$this->worldIdFrom.", ".$this->worldIdTo.", 0, 1, now())
            ";
            }
        }

        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));

        if($this->reserveComb !== false)
        {
            $sql = "
                INSERT INTO ds_reservedcomb
                    (id_comb, username, comment)
                VALUES
                    (".$this->reserveComb->getID().", '".DE_User::CreateFromID($this->uid)->getName()."', 'free world transfer')
                ";
            $this->common->setDbId($this->dbIdTo);
            $this->common->executeQuery($sql);
            assert(empty($this->common->errormsg));
        }
    
        
        
        if(count($this->reserveCombMels) > 0)
        {
            foreach ($this->reserveCombMels as $id_comb => $comb) {
                $sql = "
                INSERT INTO ds_reservedcomb
                    (id_comb, username, comment)
                VALUES
                    (".$comb->getID().", '".DE_User::CreateFromID($this->uid)->getName()."', 'free world transfer')
                ";
                $this->common->setDbId($this->dbIdTo);
                $this->common->executeQuery($sql);
                assert(empty($this->common->errormsg));
                
                $sql = "
                INSERT INTO ds_worldtransfercoordrelation
                    (id_worldtransfer, id_comb_from, id_cobm_to)
                VALUES
                    (".$id_worldtransfer.", ".$id_comb.", ".$comb->getID().")
                ";
                $this->common->setDbId(0);
                $this->common->executeQuery($sql);
                assert(empty($this->common->errormsg));
            }
        }
        
        $this->transferOrdered = true;

        return true;
    }

    private function _getWorldUserTransferTo()
    {
        $res = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);

        if(count($res) == 0)
        {
            return $res;
        }

        return $res[0];
    }

    private function _getUserWorlds()
    {
        $this->common->setDbId(0);
        GE_SearchBase::SetCommon($this->common);
        $worldsToEnter = GE_SearchStart::GetWorldsWhereUserIsRegistered((int)$this->uid);

        foreach ($worldsToEnter as $k => $world)
        {
            $worldsToEnter[$k]['dbId'] = $this->common->getDbIdByName($world['dbname']);
        }

        return $worldsToEnter;
    }

    private function _checkTimeLimit()
    {
        $count = DS_WorldTransfer::checkFreeTransferTime($this->uid, $this->freeTransferPeriod);
        if($count > 0)
        {
            return false;
        }

        return true;
    }

    private function _out()
    {
        // создаем объект смарти
        $this->tpl = new Smarty;
        $this->tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $this->tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $this->tpl->compile_check = true;
        $this->tpl->debugging = false;

        if($this->transferOrdered) {
            $this->error = 'TRANSFER_ORDERED';
        } elseif(!$this->_checkTimeLimit()) {
            $this->error = 'TRANSFER_TIME_LIMIT';
        } elseif($this->worlds !== false) {
            $this->tpl->assign('USER_WORLDS', $this->worlds);
        } elseif(DS_WorldTransfer::isTransferFromWorldDisabled($this->dbIdFrom, $this->worldIdFrom, $this->uid)) {
            $this->error = 'TRANSFER_DISABLED';
        } else {
            $this->tpl->assign('CAN_CANCEL', $this->_canCancelTransfer());

            $worlds = $this->_getWorldList();

            if(count($worlds) == 0 || !$this->_checkSourceWorldForTransfer())
            {
                $this->tpl->assign('CAN_TRANSFER', 0);
            } else {
                $this->tpl->assign('CAN_TRANSFER', 1);
            }

            $worldTo = $this->_getWorldUserTransferTo();

            if(count($worldTo) > 0 && $worldTo['status'] > 0)
            {
                $this->tpl->assign('WORLD_TO', $worldTo['worldname']);
            } else {
                $this->tpl->assign('WORLD_TO', '');
            }

            $this->tpl->assign('FROM_WORLD', $this->worldIdFrom.'_'.$this->dbIdFrom);

            $this->tpl->assign('RING_NUMBER', DS_WorldTransfer::getUserBaseRing($this->dbIdFrom, $this->uid));

            $this->tpl->assign('WORLDS', $worlds);

            $this->tpl->assign('DBID_TO', $this->dbIdTo);
            $this->tpl->assign('WORLDID_TO', $this->worldIdTo);

            $_SESSION['dbId'] = (int)$this->dbIdFrom;
            $_SESSION['world'] = (int)$this->worldIdFrom;
            
            // —писок колоний игрока
            $userColonies = DS_WorldTransfer::getUserColonies($this->uid, (int)$this->worldIdFrom);
            if ($userColonies > 1) {
                $this->tpl->assign('USER_COLONIES', $userColonies);
                $this->tpl->assign('CAN_RESERVE_MELS', 1);
            } else {
                $this->tpl->assign('CAN_RESERVE_MELS', 0);
            }
            
            $_SESSION['dbId'] = $oldDbId;
            $_SESSION['world'] = $oldWorld;
        }

        // подключение файла с локализацией
        include $_SERVER['DOCUMENT_ROOT'].'/ds/language/'.constant('DEFAULT_LANGUAGE_SHORT').'/worldtransfer.lang';
        assert(is_array($lang));
        $this->tpl->assign('lang', $lang);
        unset($lang);

        if(empty($this->error))
        {
            $this->criticalError = false;
        }

        $this->tpl->assign('MSG', $this->error);
        $this->tpl->assign('CRITICAL', $this->criticalError);

        if ($_REQUEST['mod']=='copy') $this->tpl->assign('mod', 'copy');
        $this->tpl->display('worldtransfer.tpl');
    }
}
?>