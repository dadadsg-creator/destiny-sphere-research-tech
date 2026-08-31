<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_ExecMoneyTransfer
{
    static private $dbId;

    static private $worldId;

    static private $uiid;


    static private function _getParams($data)
    {
        assert(is_array($data));
        assert(key_exists('world', $data));
        assert(key_exists('sc', $data));
        assert(key_exists('gc', $data));

        $arr = explode("_", $data['world']);
        assert(count($arr) == 3);

        self::$dbId = (int) $arr[0];
        assert(self::$dbId >= 0);

        self::$worldId = $arr[1];
        assert(ereg("[A-Z]{1,3}", self::$worldId));

        self::$uiid = (int) $arr[2];
        assert(self::$uiid >= 0);
    }


    private static function _checkTransferToUser(DE_User $user, $data)
    {
        self::_getParams($data);

        $world = DE_WorldFromCollection::CreateFromID(self::$worldId);
        $member = new MU_EntityOperationMember($user, $world);
        $instanceMoney = MU_SearchOperations::GetAccountData($member);

        if(count($instanceMoney) == 0)
        {
            return 'NO_ACCOUNT_IN_WORLD';
        }

        if(!$instanceMoney['IS_ACTIVE'])
        {
            return 'ACCOUNT_INACTIVE_IN_WORLD';
        }

        if((int)$data['gc'] < 0 || (int)$data['sc'] < 0)
        {
            return 'WRONG_CREDITS_COUNT';
        }

        if((int)$data['gc'] == 0 && (int)$data['sc'] == 0)
        {
            return 'WRONG_CREDITS_COUNT';
        }

        if($instanceMoney['GC'] < $data['gc'])
        {
            return 'NOT_ENOUGHT_GOLD_CREDITS';
        }

        if($instanceMoney['SC'] < $data['sc'])
        {
            return 'NOT_ENOUGHT_SILVER_CREDITS';
        }

        return 'OK';
    }


    static private function _checkTransferToInstance(DE_User $user, $data)
    {
        self::_getParams($data);

        $world = DE_WorldFromCollection::CreateFromID('UC');
        $member = new MU_EntityOperationMember($user, $world);
        $userMoney = MU_SearchOperations::GetAccountData($member);

        if(count($userMoney) == 0)
        {
            return 'NO_ACCOUNT';
        }

        if(!$userMoney['IS_ACTIVE'])
        {
            return 'ACCOUNT_INACTIVE';
        }

        if((int)$data['gc'] < 0 || (int)$data['sc'] < 0)
        {
            return 'WRONG_CREDITS_COUNT';
        }

        if((int)$data['gc'] == 0 && (int)$data['sc'] == 0)
        {
            return 'WRONG_CREDITS_COUNT';
        }

        if($userMoney['GC'] < $data['gc'])
        {
            return 'NOT_ENOUGHT_GOLD_CREDITS';
        }

        if($userMoney['SC'] < $data['sc'])
        {
            return 'NOT_ENOUGHT_SILVER_CREDITS';
        }

        return 'OK';
    }


    public function ConfirmTransferToUser(DE_User $user, $data)
    {
        $data['MSG'] = self::_checkTransferToUser($user, $data);
        if($data['MSG'] == 'OK')
        {
            $data['valid'] = 1;
            $data['sc'] = (int)$data['sc'];
            $data['gc'] = (int)$data['gc'];

            $world = DS_World::GetWorldFromCollectionByShortName(self::$worldId);
            $data['WORLD'] = $world['worldname'];
            
            $data['TRANSFER_TAX'] = 0;
            // Если переводим серу
            if ($data['sc'] > 0) {
                $dbOld = $_SESSION['dbId'];
                $worldOld = $_SESSION['world'];
                $_SESSION['dbId'] = self::$dbId;        
                $_SESSION['world'] = $world['id_world'];
                //Смотрим, есть ли налог на вывод серы из мира
                $tax = DE_WorldSettings::CreateForCurentWorld()->get('USER_CABINET_TRANSFER_TAX');
                if ($tax > 0) {
                    $data['TRANSFER_TAX'] = ceil($tax*$data['sc']/100);
                }

                $_SESSION['dbId'] = $dbOld;
                $_SESSION['world'] = $worldOld;
            }

            

            $_SESSION['transfered_to_user'] = 0;
            UC_OutUserCabinet::Out('TransferToUserConfirm', $data);
        } else {
            UC_ExecUserCabinetIndex::Exec($user, $data['MSG']);
        }
    }


    public function ConfirmTransferToInstance(DE_User $user, $data)
    {
        $data['MSG'] = self::_checkTransferToInstance($user, $data);
        if($data['MSG'] == 'OK')
        {
            $data['valid'] = 1;
            $data['sc'] = (int)$data['sc'];
            $data['gc'] = (int)$data['gc'];

            $world = DS_World::GetWorldFromCollectionByShortName(self::$worldId);
            $data['WORLD'] = $world['worldname'];

            $_SESSION['transfered_to_instance'] = 0;

            UC_OutUserCabinet::Out('TransferToInstanceConfirm', $data);
        } else {
            UC_ExecUserCabinetIndex::Exec($user, $data['MSG']);
        }
    }


    public static function ExecTransferToUser(DE_User $user, $data)
    {
        $MSG = '';

        // не нужно два раза делать дело
        if(key_exists('transfered_to_user', $_SESSION) && $_SESSION['transfered_to_user'] == 1)
        {
            UC_ExecUserCabinetIndex::Exec($user);
            return;
        }

        // если уже что-то изменилось со времени проверки, то ошибку выдаем и помераем
        $res = self::_checkTransferToUser($user, $data);
        if($res !== 'OK')
        {
            UC_ExecUserCabinetIndex::Exec($user, $res, 'ERR');
            return;
        }

        $memberFrom = new MU_EntityOperationMember($user, DE_WorldFromCollection::CreateFromID(self::$worldId));
        $memberTo = new MU_EntityOperationMember($user, DE_WorldFromCollection::CreateFromID('UC'));

        if((int)$data['gc'] > 0)
        {
            $summ = new MU_EntityMoneySumm((int)$data['gc'], 'GC');
            MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
        }

        if((int)$data['sc'] > 0)
        {
            // Снимаем налог
            $dbOld = $_SESSION['dbId'];
            $worldOld = $_SESSION['world'];
            $_SESSION['dbId'] = self::$dbId;
            $_SESSION['world'] = DE_WorldFromCollection::CreateFromID(self::$worldId)->getWorldID();
            //Смотрим, есть ли налог на вывод серы из мира
            $tax = DE_WorldSettings::CreateForCurentWorld()->get('USER_CABINET_TRANSFER_TAX');
            if ($tax > 0) {
                $taxSc = ceil($tax*$data['sc']/100);

                $summ = new MU_EntityMoneySumm((int)$taxSc, 'SC');
                $memberSystem = MU_EntityOperationMember::CreateSystemAccount(self::$worldId);
                $actionType = DE_MoneyActionType::CreateFromName('PAY_TRANSFER_TAX');
                $operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberSystem);
                $key = GB_RandomPassword::Generate(25);
                $operation->setAdditionalID($key);
                MU_ControlExecOperations::ExecOperation($operation);

                $data['sc'] = (int)($data['sc'] - $taxSc);

            }
            $_SESSION['dbId'] = $dbOld;
            $_SESSION['world'] = $worldOld;

            if ($data['sc'] != 0) {

                $summ = new MU_EntityMoneySumm((int)$data['sc'], 'SC');
                MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
            }
        }

        $data['done'] = 1;
        $_SESSION['transfered_to_user'] = 1;
        $MSG = 'TRANSFER_TO_USER_DONE';

        UC_ExecUserCabinetIndex::Exec($user, $MSG, 'OK');
    }


    public static function ExecTransferToInstance(DE_User $user, $data)
    {
        $MSG = '';

        // не нужно два раза делать дело
        if(key_exists('transfered_to_instance', $_SESSION) && $_SESSION['transfered_to_instance'] == 1)
        {
            UC_ExecUserCabinetIndex::Exec($user);
            exit;
        }

        // если уже что-то изменилось со времени проверки, то ошибку выдаем
        $res = self::_checkTransferToInstance($user, $data);
        if($res !== 'OK')
        {
            UC_ExecUserCabinetIndex::Exec($user, $res, 'ERR');
            exit;
        }

        $memberFrom = new MU_EntityOperationMember($user, DE_WorldFromCollection::CreateFromID('UC'));
        $memberTo = new MU_EntityOperationMember($user, DE_WorldFromCollection::CreateFromID(self::$worldId));

        if((int)$data['gc'] > 0)
        {
            $summ = new MU_EntityMoneySumm((int)$data['gc'], 'GC');
            MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
        }

        if((int)$data['sc'] > 0)
        {
            $summ = new MU_EntityMoneySumm((int)$data['sc'], 'SC');
            MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
        }

        $data['done'] = 1;
        $_SESSION['transfered_to_instance'] = 1;
        $MSG = 'TRANSFER_TO_INSTANCE_DONE';

        UC_ExecUserCabinetIndex::Exec($user, $MSG, 'OK');
    }
}

?>