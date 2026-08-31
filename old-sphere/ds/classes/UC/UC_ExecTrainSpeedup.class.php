<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2009/07/02 07:47:30 $';


class UC_ExecTrainSpeedup extends UC_PaymentServiceBase
{
    public function Exec()
    {
        self::_getParams();

        //получаем цену
        self::_GetPrice();

        switch(self::$action)
        {
            case 'buy_train_speedup':
                self::_BuyTrainSpeedup();
                break;
            default:
                self::_ShowIndex();
                break;
        }

        UC_OutUserCabinet::Out('PaymantServices', self::$data);
    }

    static private function _ShowIndex()
    {
        $_SESSION['dbId'] = 0;
        self::$data['USER_WORLDS'] = self::_GetUserWorlds(true);
        self::$data['MODE'] = 'TRAIN_SPEEDUP_INDEX';
        self::$data['EXEC_ACTION'] = 'buy_train_speedup';
        self::$data['PAID_TILL'] = array();
        $common = self::getCommon();
        foreach (self::$data['USER_WORLDS'] as $w) {
            $common->setDbId($w['dbId']);
            $sql = "select build_speedup_enddate, now() as t from ds_userinstance where id_user=".self::$user->getID()." and id_world=".$w['id_world'];
            $res = $common->getResultArray("", $sql);
            if ($res[0]['build_speedup_enddate'] > $res[0]['t'])
                self::$data['PAID_TILL'][$w['worldname']] = substr($res[0]['build_speedup_enddate'], 0, 16);
        }
    }

    static private function _BuyTrainSpeedup()
    {
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney('_'.self::$data['days'])) return false;
        $w = explode('_', self::$data['world']);
        $_SESSION['dbId'] = (int)$w[0];
        $common = self::getCommon();
        $common->setDbId((int)$w[0]);
        $sql = "select id_userinstance from ds_userinstance where id_user=".self::$user->getID()." and id_world=".(int)$w[1];
        $res = $common->getResultArray("", $sql);
        assert (count($res));

        $_SESSION['dbId'] = 0;
        UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_TRAIN_SPEEDUP_'.self::$data['days'], 1, '', 'GC');

        $actionType = DE_MoneyActionType::CreateFromName('PS_TRAIN_SPEEDUP_'.self::$data['days']);
        $MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
        self::$data['PRICE'] = $MoneyActionType->getAmount();
        self::$data['MODE'] = 'SUCCESSFUL';
        self::$data['COUNT_OF_DAYS'] = self::$data['days'];

        $_SESSION['dbId'] = (int)$w[0];
        $common->setDbId((int)$w[0]);
        DS_UserInstance::StartTrainSpeedup($res[0]['id_userinstance'], self::$data['days']);
        $sql = "select build_speedup_enddate, now() as t from ds_userinstance where id_user=".self::$user->getID()." and id_world=".(int)$w[1];
        $res = $common->getResultArray("", $sql);
        if ($res[0]['build_speedup_enddate'] > $res[0]['t'])
            self::$data['PAID_TILL'] = substr($res[0]['build_speedup_enddate'], 0, 16);

        return true;
    }

    static private function _getParams()
    {
        if(key_exists('exec_action', self::$data))
        {
            self::$action = self::$data['exec_action'];
        }
    }
}

?>