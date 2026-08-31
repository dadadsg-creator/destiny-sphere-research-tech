<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Менеджер разщморозки пользователя
 *
 */

class ME_UnFreezUser
{
    /**
     * ИНстанс поьзователя
     *
     * @var DE_UserInstance
     */
    static private $userInstance;

    /**
     * Дата заморозки
     *
     * @var GB_Date
     */
    static private $freezDate;

    /**
     * Дата заморозки
     *
     * @var GB_Date
     */
    static private $unFreezDate;


    static public function Exec(DE_UserInstance $userInstance, GB_Date $freezDate, GB_Date $unFreezDate)
    {
        self::$userInstance = $userInstance;
        self::$freezDate = $freezDate;
        self::$unFreezDate = $unFreezDate;

        self::_unfreezLastRefresh();
        self::_unfreezEvents();
        self::_unfreezBindEventTables();
    }

    static private function _unfreezLastRefresh()
    {
        self::$userInstance->setLastRefresh(self::$unFreezDate);
    }

    static private function _unfreezEvents()
    {
        $eventsCollection = DC_EventInstance::CreateFromUserInstanceID(self::$userInstance->getID());

        while($event = $eventsCollection->fetchNextEvent())
        {
            // Торговые ивенты пропускаем
            if ($event->getEventName() == 'TRADING') {
                continue;
            }
            
            //Не задерживаем ивенты полетов на игрока, так как жто полеты на захватываемую колонию!
            if ($event->getEventName() == 'BATTLE_ROUND' || 
            $event->getEventName() == 'ARMY_ROUND' ||
            $event->getEventName() == 'ARMY_MOVEMENT_TO' ||
            $event->getEventName() == 'MOVE_TO_DEFEND') {
                continue;
            }
            
            $startDate = $event->getStartDate();
            self::_recountDate($startDate);
            $event->setStartDate($startDate);

            $endDate = $event->getEndDate();
            self::_recountDate($endDate);
            $event->setEndDate($endDate);

            // Если прятание армии, передвигаем время окончания в ds_unitcontaineraction
            if ($event->getEventName() == 'HIDE_ARMY') {
                // Дествие армии
                $armyId = $event->getAttributes()->getAttribute('ID_Army');
                if (!$armyId) continue;
                $army = DE_Army::CreateFromID($armyId);
                $armyAction = $army->getArmyAction();
                
                // Меняем время
                $armyAction->setStartDate($startDate);
                $armyAction->setEndDate($endDate);
            }
        }
    }

    static private function _unfreezBindEventTables()
    {
        $UIID = self::$userInstance->getID();

        $delta = (int)(self::$unFreezDate->value - self::$freezDate->value);
        assert($delta > 0);

        DS_Freez::MoveUnitProductionInTime($UIID, $delta);

        DS_Freez::MoveScienceInTime($UIID, $delta);

        DS_Freez::MoveArchiologyInTime($UIID, $delta);

        DS_Freez::MoveAttachedBuildingsInTime($UIID, $delta);

        DS_Freez::MoveSpyZondAction($UIID, $delta);

        //Пододвигаем время возврашения армий на соту игрока
        DS_Freez::MoveArmyReturnInTime($UIID, $delta);
    }

    static private function _recountDate(GB_Date $date)
    {
        $date->value = self::$unFreezDate->value + ($date->value - self::$freezDate->value);
    }
}


?>