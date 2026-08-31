<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс для бана игроков
 */
class ME_BanUser
{   
    /**
     * Метод отзывает все армии игрока, находящиеся на защите на всех сотах игрока во всех его мирах
     */
    public static function ReturnUserArmyOnDefend($userId, DS_Common $common)
    {
        assert(is_int($userId));

        //Выбираем все миры игрока
        GE_SearchStart::SetCommon($common); 
        $userWords = GE_SearchStart::GetWorldsWhereUserIsRegistered($userId);
        //Проходимяся по всем мирам игрока
        foreach ($userWords as $key => $worldData)
        {           
            foreach($GLOBALS['dbWorldAccess'] AS $l => $db)         
            {
                if($db['server'] == $worldData['dbname'])
                {
                    $_SESSION['dbId'] = (int)$l;
                }
            }
            assert($_SESSION['dbId'] >=0);
            
            $worldId = (int)$worldData['id_world'];
            $_SESSION['world'] = $worldId;
            $common->setDbId($_SESSION['dbId']);
            $common->startTrans();

            // Переменная в сесии нужна для отправки сообщения в методе ME_ReturnArmysFromCollection
            $_SESSION['lang'] = 3;

            $userInstanceId = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($userId, $worldId);
            $userInstance = DE_UserInstance::CreateFromID($userInstanceId);
            $checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
            
            //Получаем все соты игрока
            $combsCollection = $userInstance->getCombsCollection();
            $combsCollection->resetNext();

            while($comb = $combsCollection->next())
            {
                // Отзываем все армии с этой соты, куда-либо летящие, либо стоящие на защите    
                ME_ReturnArmy::ME_ReturnAllArmysToComb($comb, $checkDate);
                // Отзываем все армии, летящие на эту соту, либо стоящие на ее защите
                ME_ReturnArmy::ME_ReturnAllArmysFromComb($comb, $checkDate);
                
                //Сбрасываем кеш вида комплекса, чтобы ворота стали красненькими
                $ring = DE_Ring::CreateFromNumber($comb->getRingNumber());
                $ringId = $ring->getID();
                $combComplex =  $comb->getComplexID();
                UA_ComplexView::ResetCache($worldId, $ringId, $combComplex);
            }
            
            // Коммитим
            GB_SingletonObjectStorage::CommitAllCommitable();
                        
            $common->closeTrans('commit');
            //$common->closeTrans('rollback');
            
            GB_SingletonObjectStorage::ResetAll();
        }
        
        //Возврашаем в сессию мастер
        $_SESSION['dbId'] = 0;
    }//END OF METHOD        
}


?>