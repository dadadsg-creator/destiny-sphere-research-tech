<?php


/**
 * Регистрация игрока в турнире
 * 
 * со стороны игры вызывается из игрового кабинета 
 *      TN_RegistrationRequest::Request()
 * 
 * со стороны турнира 
 *      TN_UserRegistration::Registrate()
 */
class TN_RegistrationRequest
{
    
    static private $turnirGate = '/ds/gate/RegGate.php';    
    
    static private $error;
    
    static private $details;
    
    /**
     * Отправить запрос гейту на регистрацию в турнире
     *
     * @return bool
     */
    static public function Request()
    {
        assert(key_exists('uid', $_SESSION));
        $user = DE_User::CreateFromID((int)$_SESSION['uid']);
        
        switch (constant('GAME_HOST'))
        {   
            case 'http://ds.rambler.ru':
                $version = 'CL';
                break;
            
            case 'http://ds-game.ru':
                $version = 'UN';
                break;
                
            default:
                $version = 'TS';
        } 
        
        $request = new SU_HTTPRequest();
        $request->setOption('method', 'POST');
        $request->addHeader('Content-type: application/x-www-form-urlencoded');
        
        $request->setVar('username', $user->getName());
        $request->setVar('email', $user->getEmail());
        $request->setVar('password', $user->getPassword());
        $request->setVar('version', $version);
        
        $res = $request->get(TN_TurnirController::GetHost().self::$turnirGate);
        
        if($res === false)
        {
            return false;
        }
        
        $arr = explode('|', $res);
        if(count($arr) < 2)
        {
            return false; 
        }
        
        if($arr[0] == '0')
        {
            self::$error = $arr[1];
            if(key_exists(2, $arr))
            {
                self::$details = $arr[2];
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Проверка нет ли регистрации в турнире
     *
     * @return bool
     */
    static public function Check()
    {
        assert(key_exists('uid', $_SESSION));
        $user = DE_User::CreateFromID((int)$_SESSION['uid']);
        
        
        
        return true;
    }
    
    static public function GetError()
    {
        return self::$error;
    }
    
    static public function GetDetails()
    {
        return self::$details;
    }
}

?>