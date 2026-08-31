<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecStoreUser extends SI_Base
{
    /**
     * ID инстанса кторого переносим
     *
     * @var unknown_type
     */
    static private $UIID;
    
    /**
     * ID мира из которого переносим
     *
     * @var unknown_type
     */
    static private $IDWorldFrom;
    
    
    /**
     * Причина сохранения
     *
     * @var unknown_type
     */
    static private $IDStoreReason;
    
    
    /**
     * Кто сохранил
     *
     * @var unknown_type
     */
    static private $StoreAdminName;
    
    
    /**
     * Коментарий
     *
     * @var unknown_type
     */
    static private $comments;
    
    
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    static private $id_storeuserdata;
    
    static private $storeTill;
    
    static public function Exec($fromDBID, $UIID, $IDWorldFrom, $IDStoreReason, $adminName = 'admin', $comments = '', $storeTill = '0000-00-00 00:00:00', $reserve = false, $userName = '')
    {       
        assert($UIID);
        assert(is_int($IDWorldFrom));
        assert($IDStoreReason);
        
        self::$UIID = $UIID;
        self::$IDStoreReason = $IDStoreReason;
        self::$IDWorldFrom = $IDWorldFrom;
        self::$comments = $comments;
        self::$StoreAdminName = $adminName;
        self::$storeTill = $storeTill;

        $storeUserInstance = self::_storeUserInstance($fromDBID, true);

        $oid = pg_last_oid(self::$common->getRES());
        assert($oid);

        self::$common->setDbId(0);
        $sql = "SELECT id_storeuserdata FROM ds_storeuserdata WHERE oid = $oid";
        $res = self::$common->getResultArray("", $sql, "", "");
        assert(empty(self::$common->errormsg));
        
        $id_storeuserdata = $res[0]['id_storeuserdata'];
        assert($id_storeuserdata);
        
        self::$id_storeuserdata = $id_storeuserdata;
    
        $userInstanceData = serialize($storeUserInstance);
        $userInstanceData = str_replace("\0", "<{0}>", $userInstanceData);
        $userInstanceData = pg_escape_string($userInstanceData);
        
        self::_storeUserInstanceData($userInstanceData, $id_storeuserdata);
        
        SI_SearchInterface::SetCommon(self::$common);
        
        $combs = $storeUserInstance->getComb();
        self::$common->setDbId($fromDBID);
        SI_ExecStoreUserCombs::SetCommon(self::$common);
        SI_ExecStoreUserCombs::Exec($combs, $id_storeuserdata, $userName, $fromDBID, $reserve);
        
        return $storeUserInstance;
    }
    
    
    /**
     * Получение объекта с полной информацией об инстансе
     * 
     *
     * @return unknown
     */
    static private function _storeUserInstance($fromDBID, $fromOldWorld = false)
    {
        echo "<xmp>";       
        self::$common->setDbId($fromDBID);
        SI_ControlStoreUserInstance::SetCommon(self::$common);
        self::$common->startTrans();
        self::$common->executeQuery("set STATEMENT_TIMEOUT to 0;");
        $storeUserInstance = SI_ControlStoreUserInstance::Exec(self::$UIID, 0);
        self::$common->closeTrans('COMMIT');
        
        SI_SearchInterface::SetCommon(self::$common);
        $worldList = SI_SearchInterface::GetWorldList();
        foreach($worldList as $k => $world)
        {
            if($world['id'] == $storeUserInstance->getWorldID() && $world['dbId'] == $fromDBID)
            {
                $world_name = $world['name'];
                $db_name_from = $GLOBALS['dbWorldAccess'][$fromDBID]['server'];;
                break;
            }
        }
        
        self::$common->setDbId(0);
        //self::$common->startTrans();
        $sql = "
            INSERT INTO ds_storeuserdata
                (
                    id_user,
                    db_name_from,
                    id_world_from,
                    world_from_name, 
                    store_admin_name, 
                    store_date, 
                    id_storeuserreason, 
                    comments
                 )
            VALUES
                (".
                    $storeUserInstance->getUserID().", 
                    '$db_name_from', ".
                    $storeUserInstance->getWorldID().", 
                    '$world_name', '".
                    self::$StoreAdminName."', 
                    NOW(), ".                   
                    self::$IDStoreReason.", '".
                    self::$comments./*"', '".
                    self::$storeTill.*/"'
                )
            ";  

        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
        //self::$common->closeTrans('COMMIT');
        echo "</xmp>";

        return $storeUserInstance;
    }
    
    /**
     * Сохранение сериализованного объекта в базе
     *
     * @param unknown_type $userInstanceData
     * @param unknown_type $id_storeuserdata
     */
    static private function _storeUserInstanceData($userInstanceData, $id_storeuserdata)
    {
        assert($id_storeuserdata);      
        self::$common->setDbId(0);
//echo $id_storeuserdata;       
        //self::$common->startTrans();
        $sql = "
            INSERT INTO 
                ds_storeuserinstancedata
                (
                    id_storeuserdata,
                    userinstance_data
                )
            VALUES
                (
                    $id_storeuserdata,
                    '$userInstanceData'
                )
            ";

        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
        //self::$common->closeTrans('COMMIT');
    }
    
    static public function GetUserDataId()
    {
        return self::$id_storeuserdata;
    }
}
?>