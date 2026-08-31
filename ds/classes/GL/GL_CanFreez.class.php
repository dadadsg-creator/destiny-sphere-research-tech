<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс игровой логики определющий можно ли начать заморозку.
 *
 */

class GL_CanFreez
{       
    
    /**
     * Код ошибки 
     *
     * @var string
     */
    static private $errorCode;
        
    static public function Test (DE_UserInstance $userInstance)
    {       
        /*//Проверяем, нет ли у игрока выставленых лотов, со сделанными ставками на них
        $userTradesCollection = DC_TradeUserLots::CreateFromUIID($userInstance->getID());
        $userTradesCollection->resetNext();
        while($tradeLot = $userTradesCollection->next())
        {
            $key = $userTradesCollection->getCurrentPosition(); 
            if ($tradeLot->haveCurrentCustomer === true) {
                self::$errorCode = 'HAVE_LOTS';
                return false;
            }
            $userTradesCollection->setPointerToPosition($key);
        }

        $res1 = DS_Trade::GetAllUserLots($userInstance->getID()); 
        
        if(count($res1) > 0)
        {
            self::$errorCode = 'HAVE_LOTS';
            return false;
        }
        
        //Проверяем, нет ли у игрока ставок на лоты
        $res2 = DS_Trade::GetAllUserBids($userInstance->getID()); 
        
        if(count($res2) > 0)
        {
            self::$errorCode = 'HAVE_BIDS';
            return false;
        }*/
        
        //Проверям нет ли боев с участием этого инстанса
        $res3 = DS_Freez::GetNotEndedBattles($userInstance);
        
        //Проверям нет ли уже занкченных экшенов двиающихся на соту данного игрока и они что буже небыли закончившимися
        $res4 = DS_Freez::GetListOfEndedMooveToAttackArmys($userInstance, GB_TypeFactory::CreateBase('GB_Date', time()));

        if(count($res3) > 0 || count($res4) > 0)
        {
            self::$errorCode = 'HAVE_BATTLES';
            return false;
        }
        
        return true;        
    }
    
    static public function FetchErrorCode()
    {
        
        $out = self::$errorCode;
        self::$errorCode = $out;
        
        return $out;
    }
}


?>