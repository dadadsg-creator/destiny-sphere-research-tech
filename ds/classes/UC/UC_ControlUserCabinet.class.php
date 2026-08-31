<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 *  ласс осуществл€ющий работу игрового кабинета 
 *
 */
class UC_ControlUserCabinet
{
    /**
     * “екущее действие
     *
     * @var string
     */
    private $action;
    
    /**
     * ѕользователь
     *
     * @var DE_User
     */
    private $user;
    
    /**
     * ‘лаг включающий ввод парол€ на каждое дейсвие
     *
     * @var bool
     */
    static public $loginProtection = false;
    
    /**
     * —писок экшенов и классов, их выполн€ющих
     *
     * @var array
     */
    static private $controls = array(
            // вход в кабинет
            'login'                         => 'UC_ControlLogin',
            // главна€ страница
            'index'                         => 'UC_ExecUserCabinetIndex',
            // выход из кабинета
            'logout'                        => array('UC_ExecLogin', 'execLogout'),
            // подтверждение обмена кредитов
            'exchange_confirm'              => array('UC_ExecMoneyExchange', 'confirm'),
            // подтверждение перевода в мир
            'transfer_to_instance_confirm'  => array('UC_ExecMoneyTransfer', 'ConfirmTransferToInstance'),
            // подтверждение вывода из мира
            'transfer_to_user_confirm'      => array('UC_ExecMoneyTransfer', 'ConfirmTransferToUser'),
            // обмен
            'exchange'                      => 'UC_ControlExchange',
            // перевод из мира
            'transfer_to_user'              => 'UC_ControlTransferToUser',
            // перевод в мир
            'transfer_to_instance'          => 'UC_ControlTransferToInstance',
            // показ статистики
            'account_stat'                  => 'UC_ExecAccountStat',
            // формарование XML дл€ флешки в кабинете
            'get_xml'                       => 'UC_ExecGetXMLData',
            // показ страницы логина
            'login_page'                    => 'UC_ControlLoginPage',
            // добровольный перевод игре. подтверждение
            'donate_confirm'                => array('UC_ExecDonation', 'ConfirmDonation'),
            // добровольный перевод игре
            'donate'                        => array('UC_ExecDonation', 'Exec'),
            // управление глобальными аль€нсами
            'global_alliance'               => 'UC_ControlGlobalAlliance',
            // изменение профил€ игрока
            'user_profile'                  => 'UC_ControlUserProfile',
            // платные услуги
            'payment_services'              => 'UC_ControlPaymentServices',
        );
    
    /**
     *  онструктор, который все и делает
     *
     * @param array $data
     */
    function __construct(array $data)
    {
        $this->_validateSession();
        
        $this->_getParams($data);
        
        // чтоб при заходе из игры можно было продолжить играть, сохран€ем »ƒ базы и мира
        $oldDbId = $_SESSION['dbId'];
        $oldWorldId = $_SESSION['world'];

        // ѕри первом входе в кабинет, не нужно провер€ть параметры в сессии, которые сам кабинет и кладет
        if($this->action != 'login' && $this->action != 'login_page')
        {
            UC_ExecLogin::checkStartSessionParams();
        }
        
        // »з этого массива даные в шаблоны страниц передаютс€
        $data['login_protection'] = self::$loginProtection;

        // ¬ыбираем нужный класс дл€ выполнени€ дейсви€
        if(key_exists($this->action, self::$controls))
        {
            if(is_array(self::$controls[$this->action]))
            {
                assert(count(self::$controls[$this->action]) == 2);
                $class = self::$controls[$this->action][0];
                $method = self::$controls[$this->action][1];
            } else {
                assert(!empty(self::$controls[$this->action]));
                $class = self::$controls[$this->action];
                $method = 'Exec';   
            }
            $obj = new $class;
            $obj->$method($this->user, $data);
            
        } else {
            // если дейсвие не задано, то показываем индекную страницу
            UC_ExecUserCabinetIndex::Exec($this->user, 'INVALID_ACTION_NAME');
        }
        
        // восстанавливаем »ƒ базы и мира
        $_SESSION['dbId'] = $oldDbId;
        $_SESSION['world'] = $oldWorldId;
    }
    
    /**
     * ѕроверка текущей сессии
     *
     */
    private function _validateSession()
    {
        if (!key_exists('uid', $_SESSION)) {
            echo 'YOUR SESSION IS CLOSED. PLEASE, RELOGIN.';
            exit;
        }
        
        assert(key_exists("uid", $_SESSION));
        assert(key_exists("lang", $_SESSION));
        assert(key_exists("language", $_SESSION));
        assert(key_exists("time_delta", $_SESSION));
        assert(key_exists("s_login", $_SESSION));
        assert(key_exists("s_pass", $_SESSION));
        assert(key_exists("profi_game", $_SESSION));
        assert(key_exists("dbId", $_SESSION));
        
        /*
        SU_ClientKey::setCheckGetVars(true);
        SU_ClientKey::checkKey(false);
        SU_ClientKey::setKey(false);
        */
    }
    
    /**
     * ѕолучение параметров
     *
     * @param array $data
     */
    private function _getParams(array $data)
    {   
        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
        } else {
            $this->action = 'index';
        }

        // создаем юзера
        $this->user = DE_User::CreateFromID((int) $_SESSION['uid']);    
    }
}

?>