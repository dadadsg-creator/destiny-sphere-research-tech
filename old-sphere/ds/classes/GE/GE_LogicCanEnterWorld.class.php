<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_LogicCanEnterWorld
{
    static public function Test(DE_User $user, $isProfy, $DBID, $worldID)
    {
        $worldsToEnter = GE_LogicWorldsList::GetWorldsToEnter($user, $isProfy);
        foreach($worldsToEnter as $k => $world)
        {
            if($world['dbId'] == $DBID && $world['id_world'] == $worldID && !$world['game_down'])
            {
                return true;
            }
        }
        
        return false;
    }
}   
?>