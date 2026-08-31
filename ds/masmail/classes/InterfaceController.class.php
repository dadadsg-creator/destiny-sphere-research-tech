<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер управления интерфейсами
 *
 */
class InterfaceController
{
    /**
     * Список интерфейсов зарегистрированнх в сиситема
     *
     * @var array
     */
    static public $components = array
    (   
        'InterfaceMain' => 'InterfaceMain.class.php',
        'InterfaceSendControl' => 'InterfaceSendControl.class.php',
        'InterfaceAdminsSend' => 'InterfaceAdminsSend.class.php',       
        'InterfaceNewUsersWithoutInstances' => 'InterfaceNewUsersWithoutInstances.class.php',
        'InterfaceUsersInWorld' => 'InterfaceUsersInWorld.class.php',
        'InterfaceAllUsers'  => 'InterfaceAllUsers.class.php',
        'InterfaceAllExclRambler'  => 'InterfaceAllExclRambler.class.php',
        'InterfaceAllRambler'  => 'InterfaceAllRambler.class.php',
        'InterfaceAllGmail'  => 'InterfaceAllGmail.class.php',
        'InterfaceAllMailru'  => 'InterfaceAllMailru.class.php',
        'InterfaceUsersWithInstances' => 'InterfaceUsersWithInstances.class.php'
    );

    /**
     * Экземпляр текущего менеджера интерфейса
     *
     * @var unknown_type
     */
    private $currentInterface;
    
    /**
     * Имя интерфейса по умолчанию.
     *
     * @var unknown_type
     */
    private $defualtInterfaceName = 'InterfaceMain';
    
    public function __construct()
    {
        $this->_setCurrentInterfaceName();
        $this->currentInterface = $this->_getCurrentInterface();
    }
    
    public function exec()
    {
        $this->_outHeader();
    
        $this->currentInterface->exec();
        $this->_outFooter();
    }
        
    private function _outHeader()
    {
        header("Content-Type: text/html; charset=windows-1251");
        echo "<h2>MassMailInterfaces - ".$this->currentInterface->getName()."</h2>";
        
        if($this->_getCurentInterfaceName() != $this->defualtInterfaceName)
        {
            
            echo "
            <i>".$this->currentInterface->getOver()."</i><br><br>
            <a href = ".$_SERVER['PHP_SELF']."?interface=".$this->defualtInterfaceName.">Return to MainInterface</a><br><br>
            ";  
        }       
    }
    
    private function  _outFooter()
    {
        echo "<br><br>
                    <b>ВНИМАНИЕ!</b><br>
                    Вы можете использовать в тел письма следующие метки:<br>
                    %username% - Заменяеться на имя пользователя<br>
                    %email% - Заменяеться на email пользователя<br>
                    %unsubscribelink% - URL для отписки от рассылок
                    ";
    }
    
    private function _getCurentInterfaceName()
    {
        if(!key_exists('MASMAIL_INERFACE_NAME', $_SESSION))
        {
            $_SESSION['MASMAIL_INERFACE_NAME'] = '';
        }                       
        
        if(empty($_SESSION['MASMAIL_INERFACE_NAME']))   
        {
            $interfaceName = $this->defualtInterfaceName;
        }else{
            $interfaceName = $_SESSION['MASMAIL_INERFACE_NAME'];
        }
        
        return $interfaceName;
    }
    
    private function _getCurrentInterface()
    {
        $interfaceName = $this->_getCurentInterfaceName();
        
        return self::GetInterfaceInstance($interfaceName);
    }
    
    private function _setCurrentInterfaceName()
    {
        if(key_exists('interface', $_REQUEST))
        {
            if(!empty($_REQUEST['interface']))
            {
                $newInterfaceName = $_REQUEST['interface']; 
            }else{
                return;
            }           
        }else{
            return;
        }

        assert(key_exists($newInterfaceName, self::$components));
        $_SESSION['MASMAIL_INERFACE_NAME'] = $newInterfaceName;
    }
    
    static public function GetInterfaceInstance($interfaceName)
    {
        assert(key_exists($interfaceName, self::$components));
        require_once('./classes/'.self::$components[$interfaceName]);
        return new $interfaceName;
    }
}

?>