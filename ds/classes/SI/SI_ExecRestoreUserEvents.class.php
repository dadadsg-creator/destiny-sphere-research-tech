<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2010/03/04 08:51:38 $';

/**
 * Востоновление ивентов пользхователя
 *
 */
class SI_ExecRestoreUserEvents extends SI_Base
{
    /**
     * Массив со списком ивентов которые сохраняються, но запрещенны к востонолению
     *
     * @var array
     */
    static private $evetsDepticateToRestore = array
    (
        'TRADING' => '_returnResourcesFromTrade',   
        'COLONIZE' => '',
        'SPY_MOVE_TO_RESEARCH' => '',
        'SPY_MOVE_FROM_RESEARCH' => ''
    );
    
    /**
     * Трансилируем имена параметров ивентов в именя словаря для трансляции ID сарых в новые
     *
     * @var array
     */
    static private $optionNameTranslate = array
    (       
        'ID_Cell' => 'ID_Cell',
        'ID_UserInstance' => 'ID_UserInstance',
        'ID_Comb' => 'ID_Comb',
        'ID_AttachedBuilding' => 'ID_AttachedBuilding',
        'ID_World' => 'ID_World',
        'ID_Seller' => 'ID_UserInstance',
        'ID_SellerComb' => 'ID_Comb',
        'ID_Customer' => 'ID_UserInstance',
        'ID_CustomerComb' => 'ID_Comb',
        'ID_CombFrom' => 'ID_Comb',
        'ID_CombTo' => 'ID_Comb'        
    );
    
    
    /**
     * объект с данными слхранения инстанса
     *
     * @var SI_ResultUserInstance
     */
    static private $userInstanceResult;
    
    /**
     * Новый ID пользователя
     *
     * @var integer
     */
    static private $newUIID;
    
    /**
     * Время турна в секундах
     *
     * @var integer
     */
    static private $turnTime;
    
    
    /**
     * Данные по параметрам ивенат которого восстоноваливаем
     *
     * @var array
     */
    static private $currentEventOptionsData = array();
    
    
    static private $worldToData = array();
    
    static private $worldID;
    
    /**
     * Соновной метод
     *
     * @param SI_ResultUserInstance $userInstanceResult
     * @param int $newUIID
     */
    static public function Exec(SI_ResultUserInstance $userInstanceResult, $newUIID, $worldID)
    {
        settype($newUIID, 'integer');
        assert($newUIID > 0);
        assert($worldID > 0);
        
        self::$userInstanceResult = $userInstanceResult;
        self::$newUIID = $newUIID;
        self::$worldID = $worldID;
        
        //Извлекаю турнтайм для масштабирования процессов с нужным масштабом времени
        $tmp = SI_SearchUserInstance::GetUserDataByUIID($newUIID);      
        self::$turnTime = (int)$tmp['turn_time'];
        assert(self::$turnTime > 0);
        // Затыка на случай черной дыры
        if (self::$turnTime > 10000) {
            self::$turnTime = 60;
        }
                
        self::$worldToData = SI_SearchUserInstance::GetWorldData(self::$worldID);
        
        //Перебираем список ивентов пользователя        
        $eventsData = $userInstanceResult->getEvents(); 
        self::_out('Start create events. Selected '.count($eventsData).' events', true);
        
        foreach ($eventsData as $k=>$eventData)
        {           
            assert(is_array($eventData));
                                            
            //Елси ивент запрещен к востоновлению то        
            if(key_exists($eventData['eventname'], self::$evetsDepticateToRestore))
            {
                
                if(!empty(self::$evetsDepticateToRestore[$eventData['eventname']]))
                {
                    self::_out("Event ".$eventData['eventname']." send in alternativ processing", true);
                    
                    $methodName = self::$evetsDepticateToRestore[$eventData['eventname']];
                    
                    self::$methodName($eventData);                  
                }else{
                    self::_out("Event ".$eventData['eventname']." depricated to restore", true);    
                }
                                            
                continue;
            }
            
            //Востонавливаем ивент          
            self::_restoreEvent($eventData);
        }   
            
    }
    
    /**
     * Востонволение ивента игрока.
     *
     * @param array $eventData
     */
    static private function _restoreEvent($eventData)
    {
        assert(is_array($eventData));
        
        self::_out("Start create event ".$eventData['eventname']."", true);

        //Берем сведения о параметрах ивента
        assert(key_exists('options', $eventData));
        self::$currentEventOptionsData = $eventData['options'];
        assert(is_array(self::$currentEventOptionsData));
        
        // режем юнитов иной расы, которые могут выползти из трансфера или из доставки покупки, если мир раса против расы
        if($eventData['eventname'] == 'TRANSFER_RESOURCE' || $eventData['eventname'] == 'TRADING_DELIVERY_GOOD')
        {
            if(self::$currentEventOptionsData['ID_Category']['VALUE'] == 2)
            {
                if((self::$worldToData['worldtype'] == 'RACE_VS_RACE') && !SI_SearchComb::TestSameRaceOfUserAndUnit(self::$newUIID, self::$currentEventOptionsData['ID_Article']['VALUE']))
                {
                    self::_out("Skip event. Units and user aren't same race", true);
                    return;
                }
            }
        }
        
        //Рассчитываем новое время начала и окончания ивента
        $startTimeObject = GB_TypeFactory::CreateBase('GB_Date', time() + round($eventData['startdelta'] * self::$turnTime));
        $endTimeObject = GB_TypeFactory::CreateBase('GB_Date', time() + round($eventData['enddelta'] * self::$turnTime));   
        
        self::_out(" - Time delta START:".$eventData['startdelta']." END:".$eventData['enddelta']." TURN:".self::$turnTime, true);              
        
        //Установили новое время начала и окончания ивента
        $eventData['eventstarttime'] = $startTimeObject->getPgFormat();
        $eventData['eventendtime'] = $endTimeObject->getPgFormat();
        self::_out(" - Event dates FROM: ".$eventData['eventstarttime']." TO: ".$eventData['eventendtime'], true);          
        
        //Создади и вент        
        $id = SI_SearchEvents::InsertEventQueue($eventData);
        assert($id > 0);
            
        //Присобачили хозяина
        SI_SearchEvents::InsertEventParticipant($id, self::$newUIID);
            

        //Добавление новой опции 'TrainedCountAccumulator' для ивентов строительства юнитов
        if($eventData['eventname'] == 'TRAIN_UNIT' || $eventData['eventname'] == 'TRAIN_WAR_UNIT')
        {
            if(!key_exists('TrainedCountAccumulator', self::$currentEventOptionsData))
            {
                if($eventData['eventname'] == 'TRAIN_UNIT')
                {
                    $id_option = 1000006;
                } else {
                    $id_option = 1000004;
                }
                
                self::$currentEventOptionsData['TrainedCountAccumulator'] = array('ID' => $id_option, 'VALUE' => 0);
            }
        }
        
        //Создаем ивенты по новому
        foreach(self::$currentEventOptionsData as $optionName => $optionData)
        {               
            self::_restoreOption($id, $optionName, $optionData, $eventData);                        
        }
        
        //Если надо прикреплять ивент к зданию то прилепляем    
        if((int)$eventData['attachebuilding'] > 0)
        {
            $newAttachedBuildingID = self::$idDictionary['ID_AttachedBuilding'][$eventData['attachebuilding']];
            SI_SearchEvents::AttachEventToBuilding($id, $newAttachedBuildingID);
        }
            
        //Если надо прилеплять ивент к соте то прилдепляем
        if((int)$eventData['attachedcomb'] > 0)
        {
            $newCombID = self::$idDictionary['ID_Comb'][$eventData['attachedcomb']];
            SI_SearchEvents::AttachEventToComb($id, $newCombID);
        }
            
        //Если для восстоновлени яивента надо произвести дополнительные действия то делаем
        switch ($eventData['eventname'])
        {
            case 'TRAIN_WAR_UNIT':
                SI_SearchEvents::AttachUnitProduction(self::$currentEventOptionsData['ID_AttachedBuilding']['NEW_VALUE'], 
                    self::$currentEventOptionsData['ID_Unit']['NEW_VALUE'], $eventData['eventstarttime'], $eventData['eventendtime'], 
                    self::$currentEventOptionsData['CountToTrain']['NEW_VALUE']);
                break;
            case 'SEND_ARCHEOLOGY_TEAM':
                if(key_exists('MinSearchArtSize', self::$currentEventOptionsData))
                {
                    $minArtSize = self::$currentEventOptionsData['MinSearchArtSize']['NEW_VALUE'];
                }else{
                    $minArtSize = 0;
                }

                SI_SearchEvents::AttachArchiologyTeam(self::$currentEventOptionsData['ID_Comb']['NEW_VALUE'], 
                    self::$currentEventOptionsData['TeamNumber']['NEW_VALUE'], self::$currentEventOptionsData['Destination']['NEW_VALUE'], 
                    $eventData['eventstarttime'], $eventData['eventendtime'], self::$currentEventOptionsData['CountOfArcheologist']['NEW_VALUE'], 
                    $id, $minArtSize);
                break;
            case 'TERRAFORMING':
                SI_SearchEvents::AttachToTerraforming(self::$currentEventOptionsData['ID_Cell']['NEW_VALUE'], $id);
                break;
            case 'NEW_RESEARCH':
                SI_SearchUserInstance::UpdateEndOfUserScience(self::$newUIID, self::$currentEventOptionsData['ID_Science']['NEW_VALUE'], 
                    $eventData['eventstarttime'], $eventData['eventendtime']);
                    
                SI_SearchEvents::AttachEventToScience(self::$newUIID, self::$currentEventOptionsData['ID_Science']['NEW_VALUE'], $id, 
                self::$currentEventOptionsData['ID_Comb']['NEW_VALUE']);
                break;
        }
        
        // Если этот ивент ускорен
        //if (SI_SearchEvents::GetIsEventAccelerated($eventData['id_eventqueue'])) {
        if (key_exists('id_eventqueue', $eventData)) {
            SI_SearchEvents::SetEventAcceleration($id);
        }
    }

    /**
         * Создаем параметры ивенты в БД
         *
         * @param integer $eventQueueID
         * @param string_type $optionName
         * @param array $optionData
         * @param array $eventData
         */
    static private function _restoreOption($eventQueueID, $optionName, $optionData, $eventData)
    {
        assert($eventQueueID > 0);
        assert(!empty($eventQueueID));
        assert(is_array($optionData));
        assert($eventData);

        $optionOldValue = $optionData['VALUE'];
        $optionID = $optionData['ID'];

        //Сомтрим нуждаеться ли данный параметр ивента в трансляцие на новое значение ID
        if(key_exists($optionName, self::$optionNameTranslate))
        {
            $optionNameTranslated = self::$optionNameTranslate[$optionName];
            
            self::$currentEventOptionsData[$optionName]['NEW_VALUE'] = self::$idDictionary[$optionNameTranslated][$optionOldValue];
        }else{
            $optionNameTranslated = '';
            self::$currentEventOptionsData[$optionName]['NEW_VALUE'] = $optionOldValue;
        }
        //Затыка для ивентов занимающихся перемещним артов
        if(key_exists('ID_Artefact', $eventData) && $optionName == 'ID_Article')
        {
            /*$artefactIsnatnceID = SI_SearchComb::CreateArtInstance($eventData['ID_Artefact'], 't');
            assert($artefactIsnatnceID > 0);

            self::_out(" - - Start create artefact for event ID_Artefact = ".$eventData['ID_Artefact']." ".$artefactIsnatnceID, true);

            self::$currentEventOptionsData[$optionName]['NEW_VALUE'] = $artefactIsnatnceID;*/
        }

        //Вставили параметр ивент
        SI_SearchEvents::InsertEventOption($eventQueueID, $optionID, self::$currentEventOptionsData[$optionName]['NEW_VALUE']);

        self::_out(" - - $optionName ($optionNameTranslated) = ".$optionData['VALUE']." | ". self::$currentEventOptionsData[$optionName]['NEW_VALUE'], true);
    }
    
    
    /************************************ МЕТОДЫ АЛТЕРНАТИВНОЙ ОБРАБОТКИ ИВЕНТОВ *************************************/
    
    /**
     * Возвращение с торга ресурсов и ставок
     *
     * @param array $eventData
     */
    static private function _returnResourcesFromTrade($eventData)
    {
        assert(is_array($eventData));
        self::$currentEventOptionsData = $eventData['options'];
    
        if (!key_exists('ForReturn',$eventData)) return;
        switch($eventData['ForReturn'])
        {
            case 'BIND':
                self::_out("Start return BIND from TRADE", true);
                
                $categoryID = 1;                            
                assert(key_exists('ID_CustomerComb', $eventData));
                $combToID = self::$idDictionary['ID_Comb'][$eventData['ID_CustomerComb']];
                                
                assert(key_exists('CurrentPrice', $eventData));
                $amount = $eventData['CurrentPrice'];
                
                assert(key_exists('ID_TadeBy', $eventData));
                $articleID = $eventData['ID_TadeBy'];
                
                break;
                
            case 'TRADE':
                self::_out("Start return GOODS from TRADE", true);
                
                $combToID = self::$idDictionary['ID_Comb'][$eventData['ID_SellerComb']];
                $categoryID = self::$currentEventOptionsData['ID_Category']['VALUE'];               
                $amount = self::$currentEventOptionsData['Amount']['VALUE'];
                
                // лоты с юнитами другой расы не возвращаем, если мир раса против расы
                if($categoryID == 2)
                {
                    if((self::$worldToData['worldtype'] == 'RACE_VS_RACE') && !SI_SearchComb::TestSameRaceOfUserAndUnit(self::$newUIID, self::$currentEventOptionsData['ID_Article']['VALUE']))
                    {
                        return;
                    }
                }
                
                if(key_exists('ID_Artefact', $eventData))
                {
                    $articleID = $eventData['ID_Artefact'];
                }else{
                    $articleID = self::$currentEventOptionsData['ID_Article']['VALUE'];
                }
                
                break;  
            default:
                assert(false);
        }
        
        SI_ExecReturnResources::Exec($combToID, $categoryID, $articleID, $amount);
    }
    

}

?>