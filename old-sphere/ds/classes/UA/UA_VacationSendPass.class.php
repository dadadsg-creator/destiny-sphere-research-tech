<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Дейсвтие начала режима отпуска
 *
 */

class UA_VacationSendPass extends UA_Action
{   
    /**
     * Пароль для страта режим аотпуска
     *
     * @var string
     */
    private $password;
    
    /**
     * Объект инстанса игрока
     *
     * @var DE_UserInstance
     */
    private $userInstance;
        
    public function __construct()
    {
        UA_Action::UA_Action ();
        
        $this->userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
            
    }
    
    public function checkLocalParams() 
    {       
        return true;
    }
        
    public function checkAction ()
    {
        $vacationDate = GL_IsUserInVocationMode::Test($this->userInstance);
        
        //Проверка находиться ли сейчас в режиме отпуска        
        assert(count($vacationDate) > 0);
        if (substr($vacationDate['password'], 0, 7) == "pereezd") return false;
        $this->password = $vacationDate['password'];
                                
        return true;
    }
    
    public function performAction()
    {       
        $params = array(
            'USER_NAME' => $this->userInstance->getUser()->getName(),
            'PASSWORD' => $this->password       
        );
        
        $msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_SEND_PASS_LATTER',  $params);

        $subject = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_SEND_PASS_SUBJ',  $params);
        
        $headers = '';
        
        $email = $this->userInstance->getUser()->getEmail();
            
        assert(mail($email, $subject, $msg, $headers));     
        
        //Пишем мессагу
        $messparams = array( 0 => $email);
        DE_SystemMessages::AddMessageFromAction(__CLASS__, 'VACATION_SEND_PASS', $messparams, 0);   
        
        return true;
    }   
}

?>