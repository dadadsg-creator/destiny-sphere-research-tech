<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_SearchOperations
{
    static public function DeltaAccount(MU_EntityOperationMember $member, MU_EntityMoneySumm $summ, $operationName)
    {
        assert($operationName == 'MINUS' || $operationName == 'PLUS');
        
        $common = DS_Common::CreateForCurentDB($member->getDBID());
        
        $fildName = $summ->getFieldName();
        assert(!empty($fildName));
        
        if($operationName == 'MINUS')
        {
            $deltaString = $fildName." = ".$fildName." - ".$summ->getAmount();
        }else{
            $deltaString = $fildName." = ".$fildName." + ".$summ->getAmount();
        }
        
        $sql = "UPDATE ds_money_account
                SET $deltaString
                WHERE 
                    id_user = ".$member->getUserID()."
                    AND
                    world_identifier = '".$member->getWorldShortName()."'";
        $res = $common->getResultArray("", $sql, "", "", "");       
        assert(empty($common->errormsg));       
    }
    
    static public function CreateAccount(DE_User $user, $worlIdentifaer)
    {
        assert(is_string($worlIdentifaer) && !empty($worlIdentifaer));
        
        $common = DS_Common::CreateForCurentDB();
                
        $sql = "INSERT INTO ds_money_account 
                (id_user, world_identifier)
                VALUES
                (".$user->getID().", '".$worlIdentifaer."')";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));       
    }
    
    static public function GetActionTypeDataByName($actionTypeName)
    {
        assert(!empty($actionTypeName));                
        
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "SELECT * 
                FROM 
                    ds_money_action_types AS at                 
                WHERE at.action_name = '".$actionTypeName."'";
        $res = $common->getResultArray("PAYMENT_SERVICES_".$actionTypeName, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);
                        
        return $res[0];
    }
    
    static public function GetActionPrice(DE_MoneyActionType $moneyAction)
    {   
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "SELECT * 
                FROM 
                    ds_money_payments_price AS mpp
                WHERE 
                    mpp.id_money_action = ".$moneyAction->getActionID()."
                    AND
                    mpp.is_current
                ";  

        $res = $common->getResultArray("PAYMENT_SERVICES_PRICE_".$moneyAction->getActionID(), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);
                        
        return $res[0];
    }
    
    static public function GetAccountData(MU_EntityOperationMember $member)
    {                   
        $common = DS_Common::CreateForCurentDB($member->getDBID());     
        
        $sql = "SELECT *
                FROM ds_money_account
                WHERE 
                    id_user = ".$member->getUserID()."
                    AND                 
                    world_identifier = '".$member->getWorldShortName()."'";     

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        // Временная затыка. Так как при переезде из мира в мир, ак не создается
        if(count($res) == 0)
        {
            // Создаем аккаунт
            $sql = "SELECT create_money_account(".$member->getUserID().", '".$member->getWorldShortName()."')";     
    
            $res = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));
            
            $sql = "SELECT *
                FROM ds_money_account
                WHERE 
                    id_user = ".$member->getUserID()."
                    AND                 
                    world_identifier = '".$member->getWorldShortName()."'";     

            $res = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));
            assert(count($res) != 0);
        }
        
        if('f' == $res[0]['is_active'])
        {
            $isActive = false;
        }else{
            $isActive = true;
        }
        
        $out = array(
            'IS_ACTIVE' => $isActive,
            'ID_ACCOUNT' => (int)$res[0]['id_accaunt'],
            'GC' => (integer)$res[0]['gc_amount'],
            'SC' => (integer)$res[0]['sc_amount']
        );          
        
        return $out;
    }
    
    static public function InsertPayment(MU_EntityOperationMember $member, MU_EntityMoneySumm $summ, $operationName, $listID)
    {           
        assert($operationName == 'MINUS' || $operationName == 'PLUS');
        assert(is_int($listID) && $listID > 0);
                        
        $common = DS_Common::CreateForCurentDB($member->getDBID());

        if('MINUS' == $operationName)
        {
            $prefix = "-";
        }else{
            $prefix = "";
        }
        
        if($summ->getCreditName() == 'GC')
        {
            $deltaGC = $prefix.$summ->getAmount();
            $deltaSC = 0;
        }else{
            $deltaGC = 0;
            $deltaSC = $prefix.$summ->getAmount();
        }

        $sql = "INSERT INTO ds_money_account_payments
                (
                    gc_delta,
                    sc_delta,
                    id_user,
                    world_identifier,                   
                    id_list,
                    id_account
                )
                SELECT 
                    ".$deltaGC.",
                    ".$deltaSC.",
                    ".$member->getUserID().",
                    '".$member->getWorldShortName()."',                 
                    ".$listID.",
                    id_accaunt
                FROM ds_money_account
                WHERE
                    id_user = ".$member->getUserID()."
                    AND
                    world_identifier = '".$member->getWorldShortName()."'";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }   
    
    public static function InsertOperationLog(MU_EntityOperationLogRecord $logData)
    {
        
        $common = DS_Common::CreateForCurentDB($logData->getDBID());
        
        $sql = "select nextval('ds_money_list_id_list_seq') as id";
        $res = $common->getResultArray ( "", $sql, "", "", "" );    
        assert(empty($common->errormsg) && count($res) == 1);       
        
        $id = (int)$res[0]['id'];
        assert($id > 0);    
        
        $logData->setListID($id);
        
        $sql = $logData->getSQLFirst();             
        $res = $common->getResultArray("", $sql, "", "", "" );  
        assert(empty($common->errormsg));
        
        $sql = $logData->getSQLSecond();    
                    
        $res = $common->getResultArray("", $sql, "", "", "" );  
        assert(empty($common->errormsg));
        
        return $id;
    }
    
    public function SetReservAsReturned($tradeID, $DBID)
    {
        assert(is_int($DBID) && $DBID >= 0);
        assert(is_int($tradeID) && $tradeID > 0);
        
        $common = DS_Common::CreateForCurentDB($DBID);
        
        $sql = "UPDATE ds_money_list_reserv SET return_date = now() WHERE id_trade = ".$tradeID;
        $res = $common->getResultArray("", $sql, "", "", "" );  
        assert(empty($common->errormsg));
    }
    
    public function GetReservRecordByTradeIDAndMember($tradeID, MU_EntityOperationMember $member)
    {       
        assert(is_int($tradeID) && $tradeID > 0);
        
        $common = DS_Common::CreateForCurentDB($member->getDBID());
        
        $sql = "SELECT * 
                FROM 
                    ds_money_list_reserv  AS r
                    INNER JOIN ds_money_list AS l ON (l.id_list = r.id_list)
                WHERE 
                    r.id_trade = ".$tradeID."
                    AND
                    l.id_user = ".$member->getUserID()."
                    AND
                    l.world_identifier = '".$member->getWorldShortName()."'";
        $res = $common->getResultArray("", $sql, "", "", "" );  
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0];
    }
    
    static public function GetReservReasonIDByName($reasonName)
    {
        assert(is_string($reasonName) && !empty($reasonName));
        
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "SELECT * FROM ds_money_reserv_reason WHERE  reason_name = '".$reasonName."'";
        $res = $common->getResultArray ( __METHOD__."(".$reasonName.")", $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE"); 
        assert(empty($common->errormsg) && count($res) == 1);       
        
        $id = (int)$res[0]['id_reason'];
        assert($id > 0);    
        
        return $id;
    }
    
    static public function GetAllowedPaymentServices()
    {
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "  SELECT
                    action_name
                FROM
                    ds_money_action_types AS at";
        $res = $common->getResultArray ('ALLOWED_PAYMENT_SERVICES', $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");  
        assert(empty($common->errormsg));       
        
        foreach ($res as $key => $val) {
            $result[] = $val['action_name'];
        }

        return $result;
    }
    
    static public function GetActionsStatHandler()
    {
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "    
                SELECT
                    action_name, stat_handler
                FROM
                    ds_money_action_types";
        
        $res = $common->getResultArray ('ACTION_TYPES_HANDLER', $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");  
        assert(empty($common->errormsg));       
        
        foreach ($res as $key => $val) {
            $result[$val['action_name']] = $val['stat_handler'];
        }

        return $result;
    }
        
}

?>