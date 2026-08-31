<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ControlMainInterface
{
    /**
     * Имя юзера
     *
     * @var unknown_type
     */
    private $userName = false;
    
    private $userBan = false;
    
    //private $UIID = false;
    
    /**
     * ИД юзера
     *
     * @var unknown_type
     */
    private $UID = false;
    
    /**
     * Пользователь админ(для CollectWorld)
     *
     * @var unknown_type
     */
    private $isAdmin = false;
    
    /**
     * Какое действие выполняем
     *
     * @var unknown_type
     */
    private $action = 'show_index';
    
    /**
     * Оюъект для работы с БД
     *
     * @var unknown_type
     */
    private $common = false;
    
    /**
     * Объект Smarty
     *
     * @var unknown_type
     */
    private $tpl = false;
    
    /**
     * Удалять после сохранения
     *
     * @var unknown_type
     */
    private $is_deleted = 0;
    
    /**
     * Зарезервировать соты
     *
     * @var unknown_type
     */
    private $is_reserved = 0;
    
    /**
     * По какой причине сохраняем инфу
     *
     * @var unknown_type
     */
    private $storeReason = 1;
    
    /**
     * Комментарии, которые добавятся к сохраненным данным юзера
     *
     * @var unknown_type
     */
    private $comments = '';
    
    /**
     * UIID с которым работаем
     *
     * @var unknown_type
     */
    private $UIIDToStore = 0;
    
    /**
     * Из какого мира переезжаем
     *
     * @var unknown_type
     */
    private $moveFromWorld = 0;
    
    /**
     * В какой мир переезжаем
     *
     * @var unknown_type
     */
    private $moveToWorld = false;
    
    /**
     * Пожелание на какие соты переезжать
     *
     * @var unknown_type
     */
    private $moveToCombCoords = false;
    
    /**
     * ИД базы из которой берем юзера
     *
     * @var unknown_type
     */
    private $dbIdFrom = 0;
    
    /**
     * ИД базы в которую переносим
     *
     * @var unknown_type
     */
    private $dbIdTo = false;
    
    /**
     * ИД сохрененных данных юзера
     *
     * @var unknown_type
     */
    private $id_userdata = 0;
    
    /**
     * Логин админа
     *
     * @var unknown_type
     */
    private $adminName = 'admin';
    
    
    /**
     * Удалять гондолу
     *
     * @var unknown_type
     */
    private $deleteGondola = false;
    
    private $out;
    
    private $worldToStr = '';
    
    private $storeTill = '0000-00-00 00:00:00';
    
    // mode - какаие темплейты использовать
    // admin - для страницы в админке
    // portal - для отображения на локале.  
    public function __construct($mode = 'portal')
    {
        $this->common = new DS_Common();//DS_Common::CreateForCurentDB(0);      
        $this->common->setDbId(0);
        SI_SearchInterface::SetCommon($this->common);
        
        $this->out = new SI_ExecOutInterface($mode);
    }
    
    public function main($request)
    {
        $this->_getParams($request);

        
        switch($this->action)
        {
            case 'show_index':
                $this->out->UserSearch();
                break;
            case 'show_user_menu':
                $this->out->UserMenu($this->userName, $this->userBan, $this->UIIDs, $this->_getCombNameList($this->UIIDs), SI_SearchInterface::GetWorldList());
                break;
            case 'store_user':
                $this->storeUser();
                break;
            case 'transfer_user':
                $this->id_userdata = $this->storeUser();
                assert($this->id_userdata);
                
                $oldCombs = SI_SearchInterface::GetStoreUserCombs($this->id_userdata);

                foreach($oldCombs as $k => $comb)
                {
                    $combCoords[$comb['name']] = $comb['coord'];
                }
                $this->common->setDbId($this->dbIdTo);
                $_SESSION["dbId"] = $this->dbIdTo;
                SI_ExecRestoreUserCombs::SetCommon($this->common);
                $combs = SI_ExecRestoreUserCombs::GetNewCombCoords($this->id_userdata, $this->dbIdTo, $this->moveToWorld, false);
                $world = SI_SearchInterface::GetDbNameAndWorldNameByWorldId($this->moveFromWorld, $this->dbIdFrom);
                $worldTo = SI_SearchInterface::GetDbNameAndWorldNameByWorldId($this->moveToWorld, $this->dbIdTo);
                                
                $this->out->UserTransfer($this->userName, $world['worldname'], $this->worldToStr, $combs, $this->id_userdata, $combCoords, $worldTo['worldname']);
                break;
            case 'restore_user':
                $this->restoreUser();
                break;
            default:
                $this->out->UserSearch();
        }
    }
    
    /**
     * Разбор входных параметров
     *
     * @param unknown_type $data
     */
    private function _getParams($data)
    {   
        if(isset($data['user_name']))
        {       
            $this->userName = trim($data['user_name']);
            if($this->userName)
            {
                $this->UIIDs = $this->_getUIIDsByUserName($this->userName);
                
                $this->action = 'show_user_menu';
            }
        }
        
        if(isset($data['delete']))
        {
            $this->is_deleted = 1;
        }
        
        if(isset($data['reserve']))
        {
            $this->is_reserved = 1;
        }
        
        if(isset($data['admin']))
        {
            $this->adminName = $data['admin'];
        }
        
        if(isset($data['reason']))
        {
            $this->storeReason = (int) $data['reason'];
            switch($this->storeReason)
            {
                case 1:
                    $this->action = 'store_user';
                    break;
                case 2:
                    $this->action = 'transfer_user';
                    break;
                case 3:
                    $this->action = 'store_user';
                    break;
            }
        }
        
        if(isset($data['UIID']))
        {
            list($this->UIIDToStore, $this->moveFromWorld, $this->dbIdFrom) = explode(":", $data['UIID']);
            settype($this->dbIdFrom, 'int');
            settype($this->moveFromWorld, 'int');
            settype($this->UIIDToStore, 'int');

            assert($this->UIIDToStore > 0);
            assert($this->dbIdFrom > -1);
            assert($this->moveFromWorld > 0);           
        }
        
        if(isset($data['comments']))
        {
            $this->comments = trim($data['comments']);
        }
        
        if(isset($data['id_storeuserdata']))
        {
            $this->action = 'restore_user';
            $this->id_userdata = $data['id_storeuserdata']; 
            
            $storeUser = SI_SearchInterface::GetUserDataByStoreUserId($this->id_userdata);
            
            $this->UID = $storeUser['id_user']; 
            $this->isAdmin = $storeUser['admin'];
            
            if(isset($data['worldFrom']))
            {
                list($this->dbIdTo, $this->moveToWorld) = explode(":", $data['worldFrom']);
                
                settype($this->dbIdTo, 'int');
                assert($this->dbIdTo >= 0);
                settype($this->moveToWorld, 'int');
                assert($this->moveToWorld > 0);
                
                // restore from vacation mode
                $this->moveFromWorld = $this->moveToWorld;
                $this->dbIdFrom = $this->dbIdTo;
            }
        }
        
        if(isset($data['worldTo']))
        {
            list($this->dbIdTo, $this->moveToWorld) = explode(":", $data['worldTo']);
            settype($this->dbIdTo, 'int');
            assert($this->dbIdTo >= 0);
            settype($this->moveToWorld, 'int');
            assert($this->moveToWorld > 0);
        }
        
        if(isset($data['combCoord']))
        {
            $this->moveToCombCoords = $data['combCoord'];
            assert(is_array($this->moveToCombCoords));
            
            foreach($this->moveToCombCoords as $name => $coords)
            {
                $ring = (int) $coords['RING'];
                $complex = (int) $coords['COMPLEX'];
                $comb = (int) $coords['COMB'];
                
                assert($ring > 0 && $ring < 5);
                assert($complex > 0);
                assert($comb > 0 && $comb < 7);
                
                $this->moveToCombCoords[$name] = $ring.".".$complex.".".$comb;
            }
        }
        
        if(isset($data['deleteGondola']))
        {
            $this->deleteGondola = ($data['deleteGondola'] == 'on');
        }
        
        if(isset($data['storeTill']))
        {
            $this->storeTill = $data['storeTill'];          
        }
        
        if(isset($data['worldId']))
        {
            // получаем список возможных координат
            $this->worldToStr = $data['worldId'];
            list($this->dbIdTo, $this->moveToWorld) = explode(":", $data['worldId']);
            settype($this->dbIdTo, 'int');
            settype($this->moveToWorld, 'int');
            assert($this->dbIdTo > -1);
            assert($this->moveToWorld > 0);
        }
    }
    
    private function _getUIIDsByUserName()
    {
        $user = SI_SearchInterface::GetUserInfoByUserName($this->userName); 
        
        if(!$user)
        {
            return array();
        }
        
        $this->UID = $user[0]['id_user'];
        $this->isAdmin = $user[0]['admin'];
        if($user[0]['accountactive'] == 'f')
        {
            $this->userBan = true;
        }
    
        return $user;
    }
    
    private function _getCombNameList($UIIDs)
    {
        assert(is_array($UIIDs));
        $nameList = array();
        
        foreach($UIIDs as $k => $UIID)
        {
            $nameList[$k] = implode("\", \"", SI_SearchInterface::GetCombsNameByUIID($UIID['id_userinstance'], $UIID['dbId']));
        }
        return $nameList;
    }
    
    /**
     * Удаление инстанса
     *
     * @param unknown_type $UIID
     */
    function deleteUserInstance($UIID)
    {
        assert($UIID);
        $this->common->setDbId($this->dbIdFrom);
        $_SESSION["dbId"] = $this->dbIdFrom;
        $deleteControl = new DU_ControllerDeleteUserInstance($this->common);
        echo "<xmp>";
        $deleteControl->drop((int)$UIID);
        echo "</xmp>";
        $collectWorlds = new SU_CollectWorlds();
        $collectWorlds->deleteFromUserInstanceCollection($UIID);
    }
    
    /**
     * Сохранение инстанса
     *
     * @return unknown
     */
    function storeUser()
    {
        echo '<A href="index.php">&laquo;&laquo;&nbsp;Back To Main</A><br>';
        echo '<h1>START SAVING USER</h1>';
        flush();
        assert($this->dbIdFrom !== false);
        $reserve = $this->is_reserved && ($this->action == 'store_user') && $this->is_deleted;
        
        //ob_start();
        
        $this->common->setDbId($this->dbIdFrom);
        $_SESSION["dbId"] = $this->dbIdFrom;
        SI_ExecStoreUser::SetCommon($this->common);     
        $storeUserInstance = SI_ExecStoreUser::Exec($this->dbIdFrom, $this->UIIDToStore, $this->moveFromWorld, $this->storeReason, $this->adminName, $this->comments, $this->storeTill, $reserve, $this->userName);
        $this->moveFromWorld = $storeUserInstance->getWorldID();

        $id_storeuserdata = SI_ExecStoreUser::GetUserDataId();
        
        //ob_end_clean();
        
        if($this->is_deleted)
        {
            ob_start();
            $this->deleteUserInstance($this->UIIDToStore);
            ob_end_clean();
            echo '<h1>user deleted</h1>';
        }
        
        echo '<h1>USER STORED</h1>';
        return $id_storeuserdata;
    }

    /**
     * Восстановление инстанса
     *
     */
    function restoreUser()
    {
        echo '<A href="index.php">&laquo;&laquo;&nbsp;Back To Main</A><br>';
        echo '<h1>START RESTORE USER</h1>';
        echo '<!--'.$this->dbIdTo.', '.$this->moveToWorld.', '.$this->id_userdata.'-->';
        assert($this->dbIdTo !== false);
        assert($this->id_userdata);
        $this->common->setDbId($this->dbIdTo);
        $_SESSION["dbId"] = $this->dbIdTo;
        
        ob_start();
        
        SI_ExecRestoreUser::SetCommon($this->common);
        SI_ExecRestoreUser::Exec($this->id_userdata, $this->dbIdTo, $this->moveToWorld, $this->deleteGondola, $this->adminName, $this->moveToCombCoords);
        
        ob_end_clean();
        
        echo '<h1>USER RESTORED</h1>';
    }
}
?>