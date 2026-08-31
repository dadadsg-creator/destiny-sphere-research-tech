<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_ExecMoneyExchange
{   
    static private function _check(DE_User $user, $data)
    {
        assert(is_array($data));
        assert(key_exists('gc', $data));
        
        if((int)$data['gc'] <= 0)
        {
            return 'WRONG_CREDITS_COUNT';
        }
    
        $world = DE_WorldFromCollection::CreateFromID('UC');
        $member = new MU_EntityOperationMember($user, $world);
        $account = MU_SearchOperations::GetAccountData($member);
        
        if(count($account) == 0)
        {
            return 'NO_ACCOUNT_IN_WORLD';
        }
        
        if($account['GC'] < $data['gc'])
        {
            return 'NOT_ENOUGHT_GOLD_CREDITS';
        }
        
        return 'OK';
    }
    
    
    public function Confirm(DE_User $user, $data)
    {
        $data['MSG'] = UC_ExecMoneyExchange::_check($user, $data);
        if($data['MSG'] == 'OK')
        {
            $data['valid'] = 1;
            $_SESSION['exchanged'] = 0;
            UC_OutUserCabinet::Out('ExchangeConfirm', $data);
        } else {
            UC_ExecUserCabinetIndex::Exec($user, $data['MSG']);
        }
    }
    
    public static function Exec(DE_User $user, array $data)
    {
        $MSG = '';
        
        // не нужно два раза делать дело
        if(key_exists('exchanged', $_SESSION) && $_SESSION['exchanged'] == 1)
        {
            UC_ExecUserCabinetIndex::Exec($user);
            return;
        }
        
        // если уже что-то изменилось со времени проверки, то ошибку выдаем
        $res = self::_check($user, $data);
        if($res !== 'OK')
        {
            UC_ExecUserCabinetIndex::Exec($user, $res, 'ERR');
            return;
        }
        
        //Обмен денег в мире
        $worldFrom = DE_WorldFromCollection::CreateFromID('UC');
        $worldTo = DE_WorldFromCollection::CreateFromID('UC');
        $memberFrom = new MU_EntityOperationMember($user, $worldFrom);
        $memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $worldTo);
        $summ = new MU_EntityMoneySumm((int)$data['gc'], 'GC');
        $actionType = DE_MoneyActionType::CreateFromName('EXCHANGE_CREDITS');
        $operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);

        MU_ControlExecOperations::ExecOperation($operation);
            
        $MSG = 'EXCHANGE_DONE';
        $_SESSION['exchanged'] = 1;
        
        UC_ExecUserCabinetIndex::Exec($user, $MSG, 'OK');
    }
}

?>