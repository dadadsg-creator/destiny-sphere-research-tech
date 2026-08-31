<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Хоть и завется этот класс Old, он используется чаще чем новый вариант
 * SI_ControlMainInterface - это интерфейс для отправки юзера в отпуск, которые превратился в интерфейс 
 * ручного перевоза игрока между мирами из админки
 * 
 * Этот класс, SI_ControlMainInterfaceOld, используется для автоматических переездов
 *
 */
class SI_ControlMainInterfaceOld extends SI_Base
{   
    /**
     * Имя экшена текущего.
     *
     * @var string
     */
    private $action = '';   
    
    /**
     * Имя пользователя которого ищем.
     *
     * @var string
     */
    private $userName = ''; 
    
    /**
     * ID БД откуда переносим.
     *
     * @var integer
     */
    private $fromDBID = null;
    
    /**
     * ID БД откуда копируем
     *
     * @var integer
     */
    private $toDBID = null;
    
    /**
     * Колличество сот у игрока в выбраном мире
     *
     * @var integer
     */
    private $combsCount = 0;
    
    /**
     * Список с новыми адрессами сот 
     *
     * @var array
     */
    private $newCombAdresses = array();
        
    /**
     * ID мира куда копируем
     *
     * @var string
     */
    private $toWorldID = 0; 
    
    /**
     * UIID откуда копируем
     *
     * @var integer
     */
    private $fromUIID = 0;
    
    /**
     * UIID которые получился
     *
     * @var integer
     */
    private $toUIID = 0;
    
    /**
     * Enter description here...
     *
     * @var SI_ResultUserInstance
     */
    private $userInstanceResult;
    
    /**
     * Какая колония после переезда станет временной (равно 0, если никакая)
     *
     * @var int
     */
    private $tempColony;
        
    public function __construct()
    {
        SI_Base::SetCommon(DS_Common::CreateForCurentDB());
    }
    
    public function main($data)
    {   
        $this->_getParams($data);
        
        $out = '';
        
        switch ($this->action)
        {
            case 'move':
                $this->_execMove();
                break;
            case 'massmove':
                $this->_massMove();
                break;
            default:
                $out = $this->_outMainInterface();
        }           

        return $out;
    }   
    
    /**
     * Выполнить перезд игрока
     *
     */
    private function _execMove()
    {
        assert($this->fromUIID > 0);
        assert($this->toWorldID > 0);
        assert(count($this->newCombAdresses) > 0);
        
        $this->_validateCombMoveAbility();
        
        self::_out("<h2>START MOVE INSTANCE</h2>", true);
        self::_out($this->_formDirectionString());
        self::_out("<xmp>", true);
        
        $this->_storeUser();
        
        $this->_saveUserInDB();
        
        $this->_restoreUser();
        
        self::_out("</xmp>", true);
    }
    
    /**
     * Сбор всех данных
     *
     */
    private function _storeUser()
    {
        $common = self::GetCommon();
        
        // Сохраняем пользователя
        assert(!is_null($this->fromDBID));
        $common->setDbID($this->fromDBID);
        $_SESSION["dbId"] = $this->fromDBID;
        $common->executeQuery("set STATEMENT_TIMEOUT to 0");
        $common->startTrans();
        
        assert(empty($common->errormsg));
        
        $this->userInstanceResult = SI_ControlStoreUserInstance::Exec($this->fromUIID, $this->tempColony);
        $common->closeTrans('COMMIT');
        
        // 
        $this->_lockUser($this->fromDBID, $this->fromUIID);
    }
    
    /**
     * Сохранение данных в базе
     *
     */
    private function _saveUserInDB()
    {
        
    }
    
    /**
     * Восстановление игрока
     *
     */
    private function _restoreUser()
    {
        $common = self::GetCommon();
        
        // Разворачиваем в новом мире
        assert(!is_null($this->toDBID));
        $common->setDbID($this->toDBID);
        $_SESSION["dbId"] = $this->toDBID;
        $common->executeQuery("set STATEMENT_TIMEOUT to 0");
        $common->startTrans();
        
        assert(empty($common->errormsg));
        
        $this->toUIID = SI_ControlRestoreUserInstance::Restore($this->toWorldID, $this->newCombAdresses, $this->userInstanceResult);
        $common->closeTrans('COMMIT');
        
        // 
        $this->_lockUser($this->toDBID, $this->toUIID);
    }
    
    /**
     * Локинг игрока, для того чтобы рефреш демон случайно не обновил в момент переезда и можно было бы 
     * сравнить исходное и конечное состояния
     *
     * @param int $dbID база
     * @param int $UIID инстанс
     */
    private function _lockUser($dbID, $UIID)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($UIID) && $UIID > 0);
        
        $locker = SU_Locker::Create('USERACTION', $dbID);
        if($locker->isLocked($UIID))
        {
            $locker->unlock($UIID);
        }
        $locker->lock($UIID);
    }
    
    /**
     * Проверка возможности восстановления
     *
     */
    private function _validateCombMoveAbility()
    {
        assert($this->fromUIID > 0);
        assert($this->toWorldID > 0);
        assert(count($this->newCombAdresses) > 0);
        
        $common = self::GetCommon();

        assert(!is_null($this->fromDBID));
        $common->setDbID($this->fromDBID);
        $_SESSION["dbId"] = $this->fromDBID;
                
        $userData = SI_SearchUserInstance::GetUserDataByUIID($this->fromUIID);
            
        assert(!is_null($this->toDBID));
        $common->setDbID($this->toDBID);
        $_SESSION["dbId"] = $this->toDBID;
        
        SI_TestRestoreUserInWorld::Test($this->toWorldID, $this->newCombAdresses, $userData['username'], $userData['email']);
    }
    
    /**
     * Сбор данных о пользователе по его нику
     *
     * @return array
     */
    private function _getUserData()
    {
        assert(!empty($this->userName));        
        
        $userData = SI_SearchInterfaceOld::GetInstacesByUserName($this->userName);      
        
        return $userData;
    }
    
    private function _outMainInterface($userData = '')
    {
        $worldsList = SI_SearchInterface::GetWorldsList();
        
        if(empty($userData))
        {
            $userData = array();
        }
        
        return SI_ExecOutInterfaceOld::MainForm($worldsList, $userData);    
    }
    
    private function _getParams($data)
    {       
        if($sapi != "apache")
        {
            // $this->fromDBID." ".$this->fromUIID." ".$this->toDBID." ".$this->toWorldID." "

            assert(key_exists('1', $_SERVER['argv']));
            $this->fromDBID = (int)$_SERVER['argv'][1];
            assert($this->fromDBID >= 0);
            
            assert(key_exists('2', $_SERVER['argv']));
            $this->fromUIID = (int)$_SERVER['argv'][2];
            assert($this->fromUIID > 0);
            
            assert(key_exists('3', $_SERVER['argv']));
            $this->toDBID = (int)$_SERVER['argv'][3];
            assert($this->toDBID >= 0);
            
            assert(key_exists('4', $_SERVER['argv']));
            $this->toWorldID = (int)$_SERVER['argv'][4];
            assert($this->toWorldID >= 0);
            
            assert(key_exists('5', $_SERVER['argv']));
            $this->tempColony = (int)$_SERVER['argv'][5];
            assert($this->tempColony >= 0);

            for($i = 6; $i < count($_SERVER['argv']); $i++)
            {                               
                $tmpArray = explode("|", $_SERVER['argv'][$i]);

                assert(key_exists(0, $tmpArray));
                $combName = $tmpArray[0];
                assert(!empty($combName));
                
                assert(key_exists(1, $tmpArray));
                $ringNumber = $tmpArray[1];
                assert($ringNumber > 0 && $ringNumber < 5);
                
                assert(key_exists(2, $tmpArray));
                $complexNumber = $tmpArray[2];
                assert($complexNumber > 0 && $complexNumber < 100000);
                
                assert(key_exists(3, $tmpArray));
                $combNumber = $tmpArray[3];
                assert($combNumber > 0 && $combNumber < 7);             
                   
                $this->newCombAdresses[$combName] = array('RING'=> $ringNumber, 'COMPLEX' => $complexNumber, 'COMB' => $combNumber);
            }
            
            $this->action = 'move';

            return;
        }
        
        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
            assert(!empty($this->action));
        }   
    }
    
    private function _formDirectionString()
    {
        assert($this->fromDBID >= 0);
        assert($this->fromUIID > 0);
        assert($this->toDBID >= 0);
        assert($this->toWorldID > 0);
        
        SI_Base::GetCommon()->setDBID($this->fromDBID);
        $_SESSION["dbId"] = $this->fromDBID;
        $userData = SI_SearchUserInstance::GetUserDataByUIID($this->fromUIID);
        
        SI_Base::GetCommon()->setDBID($this->toDBID);
        $_SESSION["dbId"] = $this->toDBID;
        $worldData = SI_SearchInterface::GetWorldsByID($this->toWorldID);
        
        return 'Move user <b>"'.$userData['username'].'"</b> direction <b>'.$userData['worldname'].' => '.$worldData['worldname'].'</b><br>';
    }
    
    private function _massMove()
    {                       
        $a = new SI_ExecMassMove($_FILES['listing']['tmp_name']);
        
        echo "<xmp>";
        $a->move();
        echo "</xmp>";
    }
        
}
?>