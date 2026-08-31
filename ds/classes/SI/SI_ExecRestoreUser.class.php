<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecRestoreUser extends SI_Base
{
    static private $dbIdTo;
    
    static private $id_userdata;
    
    static private $adminName;
    
    static public function Exec($id_userdata, $dbIdTo, $worldIdTo, $deleteGondola = false, $adminName = 'admin', $moveToCombCoords = false)
    {
        assert($id_userdata);
        settype($dbIdTo, 'integer');
        assert(is_int($dbIdTo));
        settype($worldIdTo, 'integer');
        assert($worldIdTo > 0);
        
        self::$id_userdata = $id_userdata;
        
        self::$dbIdTo = $dbIdTo;
        
        self::$adminName = $adminName;
        
        self::$common->setDbId(0);
        
        // 1 создаем объект с данными игрока
        // 2 воостанавливаем его
        $sql = "
            SELECT
                suid.userinstance_data,
                sud.is_reserved_combs,
                sud.id_user
            FROM 
                ds_storeuserinstancedata AS suid
                INNER JOIN ds_storeuserdata AS sud ON sud.id_storeuserdata = suid.id_storeuserdata
            WHERE
                suid.id_storeuserdata = ".$id_userdata."
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        $id_user = $res[0]['id_user'];
        $userInstanceData = unserialize( str_replace("<{0}>", "\0", $res[0]['userinstance_data']) );
          
        //$userInstanceData->setUserID(1327);
        //$userInstanceData->setUserName('Test26');

        self::$common->setDbId($dbIdTo);
        $common = self::GetCommon();
        $combCoords = SI_ExecRestoreUserCombs::GetNewCombCoords(self::$id_userdata, $dbIdTo, $worldIdTo, $res[0]['is_reserved_combs'], $moveToCombCoords);
            
        SI_ExecRestoreUserCombs::UpdateCombCoords($combCoords, self::$id_userdata);
        SI_ExecRestoreUserCombs::clearReserveComb(self::$id_userdata, $dbIdTo);

        $common->setDbId($dbIdTo);
        $common->startTrans();
        $common->executeQuery("set STATEMENT_TIMEOUT to 0;");
        
        SI_SearchComb::SetCommon($common);
        SI_SearchEvents::SetCommon($common);
        SI_SearchUserInstance::SetCommon($common);
        SI_SearchReturnResources::SetCommon($common);
        SI_ControlRestoreUserInstance::SetCommon($common);      
        
        echo "<xmp>";
        $newUIID = SI_ControlRestoreUserInstance::Restore($worldIdTo, $combCoords, $userInstanceData);
        assert($newUIID);
        echo "</xmp>";
        
        $common->closeTrans('COMMIT');      
        
        $sql = "SELECT u.admin FROM ds_user AS u WHERE u.id_user = ".$id_user;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        // сохраняем новые данные юзера
        SI_SearchInterface::SetCommon($common);
        $world = SI_SearchInterface::GetDbNameAndWorldNameByWorldId($worldIdTo, $dbIdTo);
        
        self::$common->setDbId(0);
        self::$common->startTrans();
        $sql = "
            UPDATE
                ds_storeuserdata
            SET 
                db_name_to = '".$world['dbname']."',
                id_world_to = ".$worldIdTo.",
                world_to_name = '".$world['worldname']."',
                restore_date = NOW(),
                restore_admin_name = '".self::$adminName."'
            WHERE
                id_storeuserdata = ".$id_userdata;
    
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));     
        self::$common->closeTrans('COMMIT');
        SI_ExecRestoreUserCombs::updateCombData($newUIID, $dbIdTo, $combCoords, $deleteGondola);
        
        $collectWorlds = new SU_CollectWorlds();
        
        $userData['id_user'] = $id_user;
        $userData['id_userinstance'] = $newUIID;
        $userData['id_world'] = $worldIdTo;
        $userData['admin'] = $res[0]['admin'];      
        $serverName = $GLOBALS['dbWorldAccess'][$dbIdTo]['server'];
        
        $collectWorlds->collectUserInstance($userData, $serverName);
    }
}
?>