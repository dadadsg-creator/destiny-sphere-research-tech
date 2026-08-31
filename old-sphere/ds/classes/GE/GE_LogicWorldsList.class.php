<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс логики опредляющий у каких мирах пользователь может 
 * регистриролваться и в каике входить.
 *
 */
class GE_LogicWorldsList
{
    /**
     * Список мирок в которых игрок может зарегистрироваться
     *
     * @param DE_User $user
     * @param bool $isProfy
     */
    static public function GetWorldsToReg(DE_User $user, $isProfy, $userHasInstances)
    {
        //      assert(is_bool($isProfy));

        $worldsToReg = array();

        //Берем список миров в которых пользователь не зарегистрирован.
        $worldsToReg = GE_SearchStart::GetWorldsWhereUserNotRegistered($user->getID());

        //Если нет миров где ещ ене зарегистрировался то стопим
        if(count($worldsToReg) == 0)
        {
            return $worldsToReg;
        }
        //Помечаем миры закрытые на GAME_DOWN.
        foreach($worldsToReg as $k=>$worldData)
        {
            //$worldsToReg[$k]['dbId'] = 0;
            foreach($GLOBALS['dbWorldAccess'] AS $l => $db)
            {
                if($db['server'] == $worldData['dbname'])
                {
                    $worldsToReg[$k]['dbId'] = $l;
                }
            }

            $worldStatus = GE_SearchStart::GetWorldStatus((int)$worldData['id_world'], $worldsToReg[$k]['dbId']);
            if(GameDown($worldsToReg[$k]['dbId'], $worldData['id_world']) || $worldStatus == 'CLOSE' || $worldStatus == 'REGISTRATION_CLOSE' || $worldStatus == 'TURNIR_STARTED')
            {
                $worldsToReg[$k]['game_down'] = 1;
            } else {
                $worldsToReg[$k]['game_down'] = 0;
            }
        }

        GE_SearchBase::SetDBID(0);

        //Если пользователь админ то фильрация закончена.
        if($user->getIsAdmin() == 't')
        {
            return $worldsToReg;
        }

        //Если человек не премиум то убираем из списка премиумные миры.
        //Заисключением тех миров ге прописанн userasallowed
        foreach($worldsToReg as $k=>$worldData)
        {
            if($worldData['profi'] == 't' && !$isProfy && !$worldData['allowed'])
            {
                unset($worldsToReg[$k]);
            }
        }
        //Если в конфиге включен параметр ONE_WORLD_REGISTRATION_ONLY и
        //пользователь не имеет флага MULTY_WORLD_ACCESS, то из списка
        //выбрасываем все не премиумные миры и кроме меров укда прописанно userasallowed
        foreach($worldsToReg as $k=>$worldData)
        {
            if($userHasInstances &&
                (
                $worldData['world_identifier'] != 'EDN' &&
                $worldData['world_identifier'] != 'ORN' &&
                $worldData['world_identifier'] != 'ZME' &&
                $worldData['world_identifier'] != 'VSV' &&
                $worldData['world_identifier'] != 'TRN' &&
                constant('ONE_WORLD_REGISTRATION_ONLY') &&
                $worldData['profi'] == 'f' &&
                !$worldData['allowed'] &&
                !GE_SearchStart::GetMultiWorldAccessForUID($user->getID())  &&
                !GE_SearchStart::GetUserAllowed($user->getID(), $worldData['id_world'], $worldData['dbname']) &&
                !$worldData['multy_access']
                || $worldData['for_new_players'] == 't'
                )
            )
            {
            unset($worldsToReg[$k]);
            }
        }

        //Фильтруем миры в которых нет сводобных мест
        foreach($worldsToReg as $k=>$worldData)
        {
            if($worldData['max_users'] <= 0 && !GE_SearchStart::GetUserAllowed($user->getID(), $worldData['id_world'], $worldData['dbname']) && !GE_SearchStart::GetMultiWorldAccessForUID($user->getID()))
            {
                unset($worldsToReg[$k]);
            }
        }

        return $worldsToReg;
    }

    static public function GetWorldsToEnter(DE_User $user, $isProfy)
    {
        //      assert(is_bool($isProfy));
        $worldsToEnter = array();

        //Извлекаем список миров в которых зарегистрирован пользователь.
        $worldsToEnter = GE_SearchStart::GetWorldsWhereUserIsRegistered($user->getID());

        //Помечаем миры закрытые на GAME_DOWN.
        foreach($worldsToEnter as $k=>$worldData)
        {
            //$worldsToEnter[$k]['dbId'] = 0;
            foreach($GLOBALS['dbWorldAccess'] AS $l => $db)
            {
                if($db['server'] == $worldData['dbname'])
                {
                    $worldsToEnter[$k]['dbId'] = $l;
                }
            }

            $worldStatus = GE_SearchStart::GetWorldStatus((int)$worldData['id_world'], $worldsToEnter[$k]['dbId']);
            if(GameDown($worldsToEnter[$k]['dbId'], $worldData['id_world']) || $worldStatus == 'CLOSE')
            {
                $worldsToEnter[$k]['game_down'] = 1;
            } else {
                $worldsToEnter[$k]['game_down'] = 0;
            }
            if ($worldStatus == 'CLOSE' && VERSION_NAME=='LIMITED')
                unset($worldsToEnter[$k]);
        }

        GE_SearchBase::SetDBID(0);

        //Если пользователь админ то фильрация закончена.
        if($user->getIsAdmin() == 't')
        {
            return $worldsToEnter;
        }

        //Если человек не премиум то убираем из списка премиумные миры.
        foreach($worldsToEnter as $k=>$worldData)
        {
            if($worldData['profi'] == 't' && !$isProfy && !$worldData['allowed'])
            {
                unset($worldsToEnter[$k]);
            }
        }

        return $worldsToEnter;
    }
}
?>