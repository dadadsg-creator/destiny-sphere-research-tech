<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_LogDeletingGameObjects extends DS_Base
{
    static public function GetDeleteObjectTypeIDByName($objectType)
    {
        assert(!empty($objectType));
        
        $common = self::getCommon();
        
        $sql = "SELECT * FROM ds_log_delete_game_object_types WHERE object_name = '".$objectType."'";
        $res = $common->getResultArray (self::FormMark(__METHOD__, $objectType), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);   
        
        return (int)$res[0]['id_object_type'];
    }
    
    static public function InsertDeleteGameObjectLog(DE_World $world, DE_Comb $comb, $objectType, $objectID, $cellID, $artDeathTime = '')
    {
        assert(!empty($objectType));
        assert(is_int($objectID) && $objectID > 0);
        assert(is_int($cellID) && $cellID >= 0);

        $objectTypeID = self::GetDeleteObjectTypeIDByName($objectType);
        assert($objectTypeID > 0);
        
        if (!empty($artDeathTime)) {
            $artDeathTime = "'$artDeathTime'";
        } else {
            $artDeathTime = 'null';
        }
            
        $common = self::getCommon();
        
        $sql = "INSERT INTO ds_log_delete_game_objects
                (id_world, id_comb, id_object_type, id_object, id_cell, artefact_death_time)
                VALUES
                (".$world->getID().", ".$comb->getID().", ".$objectTypeID.", ".$objectID.", ".$cellID.", $artDeathTime)";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));   
    }   
}

?>