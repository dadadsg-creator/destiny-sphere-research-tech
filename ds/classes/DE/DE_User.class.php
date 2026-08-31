<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2008/07/01 08:46:26 $';

/**
  * Класс "пользоваетля"
  */
class DE_User extends DE_Base{

    /** Ник пользователя */
    private $name;

    /** ID Язфка пользователя */
    private $languageID;


    private $langShortName;

    /** emial пользователя */
    private $email;

    /** Дата регшистрации */
    private $regDate;

    /** Игрок активный или забаненый */
    private $active;

    /** Администратор ли пользователь */
    private $isAdmin;

    private $accountactive;

    private $timeDelta;

    private $password;

    private $activateMail;

    private $banReason;

    /** Аватарка пользователя */
    private $image;

    /**
     * Являеться ли игрок премиумом или нет
     *
     * @var boolean
     */
    private $isProfy;

    /**
     * Премиум-баня
     *
     * @var GB_DatePeriod
     */
    private $premiumBan;

    /**
     * Комментаий к премиум-бану
     *
     * @var text
     */
    private $premiumBanComment;

    /**
     * Разрешение торговли при премиум-бане
     *
     * @var text (t or f)
     */
    private $premiumBanAllowTrade;

    /**
     * ИД партера, от которого пользователь зарегистрировался
     *   в русской версии - при регистрации от пары провайдеров дается премиум
     *   в итальяской версии - показываются разные логотипы и банеры
     *
     * @var int
     */
    private $partnerID;
    
    /**
     * 
     *  НПС - сота (бот)
     *
     * @var int
     */
    private $npc;

    function __construct($id, $data = '')
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($id) && $id>=0);

        parent::__construct($id);

        if(!is_array($data))
        {
            $data = DS_User::getByID($id);
        }

        if (!is_array($data) || count($data) <= 0)
        {
            //debug_print_backtrace();
            DS_Error("Cant get user data .(".$id.")");
        }

        assert(is_array($data));
        assert(count($data)>0);

        assert(key_exists('username', $data));
        $this->name = $data['username'];
        assert(!empty($this->name));

        if (constant('VERSION_NAME') != 'VKONTAKTE') {
            assert(key_exists('email', $data));
            $this->email = $data['email'];
            assert(!empty($this->email));       
        }

        assert(key_exists('time_delta', $data));
        $this->timeDelta = (float)$data['time_delta'];

        assert(key_exists('id_language', $data));
        $this->languageID = (int)$data['id_language'];

        assert(key_exists('shortname', $data));
        $this->langShortName = $data['shortname'];

        assert(key_exists('password', $data));
        $this->password = $data['password'];

        assert(key_exists('registrationdate', $data));
        if(empty($data['registrationdate']))
        {
            $this->regDate = GB_TypeFactory::CreateBase('GB_Date', time());
        }else{
            $this->regDate = GB_TypeFactory::CreateBase('GB_Date', $data['registrationdate']);
        }
        assert(!empty($this->regDate));

        assert(key_exists('admin', $data));
        $this->isAdmin = $data['admin'];

        assert(key_exists('accountactive', $data));
        $this->accountactive = $data['accountactive'];
        assert(($this->accountactive == 't') || ($this->accountactive == 'f'));

        assert(key_exists('activate_mail', $data));
        $this->activateMail = $data['activate_mail'];

        assert(key_exists('image_url', $data));
        $this->image = $data['image_url'];

        assert(key_exists('id_partner', $data));
        $this->partnerID = (int)$data['id_partner'];

        assert(key_exists('npc', $data));
        $this->npc = $data['npc'];
        if (empty($this->npc)) {
            $this->npc = 'f';
        }
        assert(($this->npc == 't') || ($this->npc == 'f'));

        $this->_invariant('construct');
    }//END OF METHOD

    /**
     * Enter description here...
     *
     * @return DE_User
     */
    static public function CreateFromID($id)
    {
        if (!is_int($id) || $id<0) exit;

        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }

    /**
     * Enter description here...
     *
     * @return DE_User
     */
    public static function CreateFromName($name)
    {
        assert(!empty($name));

        $userID = DS_User::GetUserIDByName($name);

        assert($userID >= 0);

        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $userID);
    }

    /**
     * Enter description here...
     *
     * @return DE_User
     */
    public static function CreateForCurrentUser()
    {
        $userID = (int)$_SESSION['uid'];

        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $userID);
    }

    function getName()
    {

        return $this->name;
    }

    function getEmail()
    {

        return $this->email;
    }

    function getIsAdmin()
    {

        return $this->isAdmin;
    }

    function getTimeDelta($real = false)
    {
        if ($real == false)
        {
            return $this->timeDelta  + constant('TIME_DELTA_DELTA');
        } else {
            return $this->timeDelta;
        }
    }

    function getPassword()
    {

        return $this->password;
    }


    function getLanguageID()
    {
        return $this->languageID;
    }

    function getLangShortName()
    {
        return $this->langShortName;
    }

    public function getImage()
    {
        return $this->image;
    }

    function setTimeDelta($delta)
    {
        assert(is_numeric($delta) && $delta <= 24 && $delta >= -24);

        $this->timeDelta = $delta;

        $this->isModifyed = true;

        return true;
    }

    function setNewPassword($password)
    {
        assert(isset($password));
        $this->password = $password;

        $this->isModifyed = true;

        return true;
    }

    function setLanguageID($id_language)
    {
        assert((int)$id_language > 0);

        $this->languageID = $id_language;

        $this->isModifyed = true;

        return true;
    }

    function setName($name)
    {
        $this->name = $name;

        $this->isModifyed = true;

        return true;
    }

    function getRegDate()
    {
        return $this->regDate;
    }

    function getAccountActive()
    {
        if($this->accountactive == 'f' || count($this->getBanReason()) > 0)
        {
            return 'f';
        }

        return 't';
    }

    function getIsUserNpc()
    {
        return $this->npc;
    }

    function setAccountActive($active)
    {
        assert(is_bool($active));

        if($active)
        {
            $this->accountactive = 't';
        } else {
            $this->accountactive = 'f';
        }
    }

    function isActive()
    {
        if($this->accountactive == 't')
        {
            return true;
        }
        return false;
    }

    function getBanReason()
    {
        if(is_null($this->banReason))
        {
            $this->banReason = DS_User::GetBanReason($this);
        }

        return $this->banReason;
    }

    /**
     * Enter description here...
     *
     * @return GB_DatePeriod
     */
    function getPremiumBan()
    {
        if(is_null($this->premiumBan))
        {
            $banData = DS_User::GetPremiumBan($this);
            if(count($banData) > 0)
            {
                $startDate = GB_TypeFactory::CreateBase('GB_Date', $banData['start_date']);
                $endDate = GB_TypeFactory::CreateBase('GB_Date', $banData['end_date']);
                $this->premiumBanComment = $banData['comments'];
                $this->premiumBanAllowTrade = $banData['allow_trade'];
            } else {
                $time = time();
                $startDate = GB_TypeFactory::CreateBase('GB_Date', $time);
                $endDate = GB_TypeFactory::CreateBase('GB_Date', $time - 1);
                $this->premiumBanComment = '';
                $this->premiumBanAllowTrade = 'f';
            }

            $this->premiumBan = new GB_DatePeriod($startDate, $endDate);
        }

        return $this->premiumBan;
    }

    /**
     * Enter description here...
     *
     * @return GB_DatePeriod
     */
    function getPremiumBanComment()
    {
        return $this->premiumBanComment;
    }

    /**
     * Enter description here...
     *
     * @return int
     */
    function getPremiumBanAllowTrade()
    {
        return $this->premiumBanAllowTrade;
    }

    /**
     * Enter description here...
     *
     * @param GB_DatePeriod $period
     */
    function setPremiumBan(GB_DatePeriod $period, $comments = '', $allowTrade = 'f')
    {
        $this->premiumBan = $period;
        $this->premiumBanComment = $comments;
        $this->premiumBanAllowTrade = $allowTrade;
        $this->isModifyed = true;
    }

    function setBanWithReason($banReason, $startDate, $time = false)
    {
        assert(!empty($banReason));

        $this->banReason['reasonname'] = $banReason;
        $this->banReason['startdate'] = $startDate;
        $this->banReason['time'] = $time;

        $this->isModifyed = true;
        return true;
    }

    function getPartnerID()
    {
        return $this->partnerID;
    }

    function isMailActivated()
    {

        if($this->activateMail == 1)
        {
            return true;
        }
        return false;
    }

    function isProfy()
    {
        if(is_null($this->isProfy))
        {
            $this->isProfy = (DS_User::IsPremium($this->getID()) || DS_User::IsTrialPremium($this->getID()));
        }

        return $this->isProfy;
    }

    public function commit()
    {
        parent::commit();

        if($this->isModifyed == true)
        {
            if(DS_User::UpdateUser($this))
            {
                $this->isModifyed = false;
            }
        }
    }
}//END OF CLASS


?>