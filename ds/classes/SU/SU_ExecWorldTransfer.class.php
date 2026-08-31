<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_ExecWorldTransfer
{
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    static private $fileName;
    
    static private $callBackScript = 'updateTransferInfo.php';
    
    static public function Exec()
    {
        self::$fileName = '../utils/files/'.VERSION_NAME.'.move';
        self::$common = DS_Common::CreateForCurentDB(0);
        
        $list = self::_getReadyToMoveList();
        self::_createMoveFile($list);
        
        $massMove = new SI_ExecMassMove(self::$fileName);
        $massMove->move();
        
        /*******************************************************/
        /*********** днаюбнвйю дкъ йбеярю **********************/
         
         TMP_TransferQuest::Exec();
         
         /******************************************************/
    }
    
    static private function _getReadyToMoveList()
    {
        $sql = "
            SELECT
                u.username,
                wt.*,
                w_from.world_identifier as w_from,
                w_to.world_identifier as w_to
            FROM
                ds_worldtransfer as wt
                INNER JOIN ds_user AS u ON (u.id_user = wt.id_user)
                INNER JOIN ds_worldcollection AS w_from ON (w_from.dbname = wt.db_from AND w_from.id_world = wt.id_world_from)
                INNER JOIN ds_worldcollection AS w_to ON (w_to.dbname = wt.db_to AND w_to.id_world = wt.id_world_to)
            WHERE
                status = 1
                -- AND
                -- u.username LIKE 'Stariy%'
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errorMsg));
        
        foreach($res as $k => $transfer)
        {
            $res[$k]['dbId_from'] = self::$common->getDbIdByName($transfer['db_from']);
            $res[$k]['dbId_to'] = self::$common->getDbIdByName($transfer['db_to']);
        }
            
        return $res;
    }
    
    static private function _createMoveFile($list)
    {
        $content = '';
        
        foreach($list as $k => $moveParams)
        {
            $content .= ''.$moveParams['username'].';'.$moveParams['dbId_from'].';'.$moveParams['w_from'].';'.$moveParams['dbId_to'].';'.$moveParams['w_to'].';RESERVE;'.(int)$moveParams['temp_colony'].';'.self::$callBackScript."\n";
        }   
        file_put_contents(self::$fileName, $content);
    }
}

?>