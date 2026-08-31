<?php $CVS_Version = '$Revision: 1.11 $ ; $Date: 2008/12/18 10:58:46 $';

class UC_ExecUserCabinetIndex
{
    
    public static function Exec(DE_User $user, $msg = '', $msgType = 'ERR')
    {
        // get user info 
        $_SESSION['dbId'] = 0;
        
        $world = DE_WorldFromCollection::CreateFromID('UC');
        $member = new MU_EntityOperationMember($user, $world);
        $data['USER'] = MU_SearchOperations::GetAccountData($member);
        
        // если нет аккаунта в кабинете, то создаем
        if(count($data['USER']) == 0)
        {
            MU_SearchOperations::CreateAccount($user, 'UC');
            $data['USER'] = MU_SearchOperations::GetAccountData($member);
            assert(count($data['USER']) > 0);
        }
        
        if(is_array($msg))
        {
            $msg = '';
        }
        $data['MSG'] = $msg;
        $data['MSG_TYPE'] = $msgType;
    
        $data['USER_INSTANCE'] = self::GetUserMoneyAccounts($user);
        
        $_SESSION['dbId'] = 0;
        
        if(count($data['USER']))
        {
            $account['ID_ACCOUNT'] = $data['USER']['ID_ACCOUNT'];
            $account['id_user'] = $user->getID();
            $account['world'] = '0_UC';
            UC_ExecAccountStat::GetCommon();
            UC_ExecAccountStat::_getAccountOperations($account, 5);
        
            $data['ACCOUNT_OPS'] = UC_ExecAccountStat::$finalStat;
        } else {
            $data['ACCOUNT_OPS'] = array();
        }
        
        $data['GC_COURSE'] = constant('GC_COURSE');
        $data['USER_NAME'] = $user->getName();
        $data['USER_EMAIL'] = $user->getEmail();
        
        // out
        UC_OutUserCabinet::Out('Index', $data);
    }
    
    
    static public function GetUserMoneyAccounts(DE_User $user)
    {
        $common = DS_Common::CreateForCurentDB();
        GE_SearchStart::SetCommon($common);     
        $userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($user->getID());
        
        $result = array();
        
        foreach($userWorlds as $k => $instance) 
        {
            /*if ($instance['dbname'] != 'master') {
                continue;
            }*/
            
            $dbId = $common->getDBIdByName($instance['dbname']);
            
            $_SESSION['dbId'] = $dbId;
            $_SESSION['world'] = $instance['id_world'];
            
            //Смотрим, разрешены ли в этом мире операции с деньгами
            $allowCabinet = DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_MONEY_ACTION');

            if ($allowCabinet == 1) 
            {
                $world = DE_WorldFromCollection::CreateFromID($instance['world_identifier']);
                $member = new MU_EntityOperationMember($user, $world);
                $account = MU_SearchOperations::GetAccountData($member);
    
                if(count($account) > 0)
                {
                    $result[$instance['worldname']] = $account;
                    $result[$instance['worldname']]['instance'] = $dbId.'_'.$instance['world_identifier'].'_'.$instance['id_userinstance'];
                    $result[$instance['worldname']]['dbname'] = $instance['dbname']."_".$instance['id_world'];
                }
            }
        }
        
        return $result;
    }
    
}

?>