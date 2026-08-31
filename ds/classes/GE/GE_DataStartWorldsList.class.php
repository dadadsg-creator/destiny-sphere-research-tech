<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2009/01/27 11:09:18 $';


/**
 * Класс - хранилище данных для отображения страницы входа (index_start.php)
 *
 */
class GE_DataStartWorldsList
{
    /**
     * Список миров в которые пользователь может войти
     *
     * @var unknown_type
     */
    private $worldsToEnter;
    
    /**
     * Список миров в которых пользователь может зарегистрироваться
     *
     * @var unknown_type
     */
    private $worldsToReg;
    
    /**
     * Список с информацией по всеммирам
     *
     * @var unknown_type
     */
    private $allWorlds;
    
    /**
     * Список рас
     *
     * @var unknown_type
     */
    private $allRaces;
    
    private $userId;
    
    private $premiumDays;
    
    private $worldsDown;
    
    private $hasWorldToEnter;
    
    private $premiumPresent;
    
    private $userBanned;
    
    private $userBanReason;
    
    private $mailActivation;
    
    private $mailActivationDaysLeft;
    
    private $mailActivationRequire;
    
    private $regWorld = false;
    
    private $regRace = 3;
    
    private $regCombname = '';
    
    private $userName;
    
    private $userEmail;
    
    private $haveInstanceInWorldUnion;
    
    private $hasInstanceInWorldWithFreeTransfer;
    
    private $clientKey;
    
    private $canChangeMail;
    
    private $language;
    
    private $allowUserCabinet;
    
    private $showNewUserHelp = false;
    
    private $pollQuestion;
    
    private $pollAnswers;
    
    private $pollResult;
    
    private $banner;
    
    public function SetWorldsToEnter($worldsToEnter)
    {
        assert(is_array($worldsToEnter));
        $this->worldsToEnter = $worldsToEnter;
    }
    
    public function GetWorldsToEnter()
    {
        return $this->worldsToEnter;
    }
    
    
    public function SetLanguage($language)
    {
        assert(preg_match('/^[A-Z]{1}$/', $language));
        $this->language = $language;
    }
    
    public function GetLanguage()
    {
        return $this->language;
    }
    
    public function SetWorldsToReg($worldsToReg)
    {
        assert(is_array($worldsToReg));
        $this->worldsToReg = $worldsToReg;
    }
    
    public function GetWorldsToReg()
    {
        return $this->worldsToReg;
    }
    
    
    public function SetAllWorlds($allWorlds)
    {
        assert(is_array($allWorlds));
        $this->allWorlds = $allWorlds;
    }
    
    public function GetAllWorlds()
    {
        return $this->allWorlds;
    }
    
    public function SetAllRaces($allRaces)
    {
        assert($allRaces && is_array($allRaces));
        $this->allRaces = $allRaces;
    }
    
    public function GetAllRaces()
    {
        return $this->allRaces;
    }
    
    public function SetUserId($userId)
    {
        assert(is_int($userId) && $userId > 0);
        $this->userId = $userId;
    }
    
    public function GetUserId()
    {
        return $this->userId;
    }
    
    public function SetUserName($userName)
    {
        assert(!empty($userName));
        $this->userName = $userName;
    }
    
    public function GetUserName()
    {
        return $this->userName;
    }
    
    public function SetUserEmail($userEmail)
    {
        assert(!empty($userEmail));
        $this->userEmail = $userEmail;
    }
    
    public function GetUserEmail()
    {
        return $this->userEmail;
    }
    
    public function SetPremiumDays($days)
    {
        assert(is_array($days));
        $this->premiumDays = $days;
    }
    
    public function GetPremiumDays()
    {
        return $this->premiumDays; 
    }
    
    public function SetWorldsDown($worldsDown)
    {
        $this->worldsDown = $worldsDown;
    }
    
    public function GetWorldsDown()
    {
        return $this->worldsDown; 
    }
    
    public function SetHasWorldToEnter($hasWorldToEnter)
    {
        assert(is_bool($hasWorldToEnter));
        $this->hasWorldToEnter = $hasWorldToEnter;
    }
    
    public function GetHasWorldToEnter()
    {
        return $this->hasWorldToEnter; 
    }
    
    public function SetPremiumPresent($premiumPresent)
    {
        $this->premiumPresent = $premiumPresent;
    }
    
    public function GetPremiumPresent()
    {
        return $this->premiumPresent;
    }
    
    public function SetUserBan($userBan)
    {
        assert(is_bool($userBan));
        $this->userBanned = $userBan;
    }
    
    public function SetUserBanReason($userBanReason)
    {
        assert(is_array($userBanReason));
        $this->userBanReason = $userBanReason;
    }
    
    public function GetUserBan()
    {
        return $this->userBanned;
    }
    
    public function GetUserBanReason()
    {
        return $this->userBanReason;
    }
    
    public function SetMailActivation($mailActivation)
    {
        assert(is_bool($mailActivation));
        $this->mailActivation = $mailActivation;
    }
    
    public function GetMailActivation()
    {
        return $this->mailActivation;
    }
    
    public function SetMailActivationDaysLeft($mailActivationDaysLeft)
    {
        assert(!empty($mailActivationDaysLeft));
        $this->mailActivationDaysLeft = $mailActivationDaysLeft;
    }
    
    public function GetMailactivationDaysLeft()
    {
        return $this->mailActivationDaysLeft;
    }
    
    public function SetMailactivationRequire($mailActivationRequire)
    {
        assert(is_bool($mailActivationRequire));
        $this->mailActivationRequire = $mailActivationRequire;
    }
    
    public function GetMailactivationRequire()
    {
        return $this->mailActivationRequire;
    }
    
    public function SetRegWorld($regWorld)
    {
        $this->regWorld = $regWorld;
    }
    
    public function SetRegRace($regRace)
    {
        $this->regRace = $regRace;
    }
    
    public function SetRegCombname($regCombname)
    {
        $this->regCombname = $regCombname;
    }
    
    public function GetRegWorld()
    {
        return $this->regWorld;
    }
    
    public function GetRegRace()
    {
        return $this->regRace;
    }
    
    public function GetRegCombname()
    {
        return $this->regCombname;
    }
    
    public function SetInstanceInWorldUnion($haveInstanceInWorldUnion)
    {
        assert(is_bool($haveInstanceInWorldUnion));
        $this->haveInstanceInWorldUnion = $haveInstanceInWorldUnion;
    }
    
    public function HaveInstanceInWorldUnion()
    {
        return $this->haveInstanceInWorldUnion;
    }
    
    public function SetInstanceInWorldWithFreeTransfer($hasInstanceInWorldWithFreeTransfer)
    {
        assert(is_bool($hasInstanceInWorldWithFreeTransfer));
        $this->hasInstanceInWorldWithFreeTransfer = $hasInstanceInWorldWithFreeTransfer;
    }
    
    public function HaveInstanceInWorldWithFreeTransfer()
    {
        return $this->hasInstanceInWorldWithFreeTransfer;
    }
    
    public function SetClientKey($clientKey)
    {
        $this->clientKey = $clientKey;
    }
    
    public function GetClientKey()
    {
        return $this->clientKey;
    }
    
    public function SetChangeMail($canChangeMail)
    {
        assert(is_bool($canChangeMail));
        $this->canChangeMail = $canChangeMail;
    }
    
    public function CanChangeMail()
    {
        return $this->canChangeMail;
    }
    
    public function SetAllowUserCabinet($allowUserCabinet)
    {
        assert(is_int($allowUserCabinet));
        $this->allowUserCabinet = $allowUserCabinet;
    }
    
    public function GetAllowUserCabinet()
    {
        return $this->allowUserCabinet;
    }
    
    public function SetShowUserHelp($show)
    {
        assert(is_bool($show));
        $this->showNewUserHelp = $show;
    }
    
    public function GetShowUserHelp()
    {
        return $this->showNewUserHelp;
    }
    
    public function SetPollQuestion($pollQuestion)
    {   
        // Удаляем лишние слеши
        $pollQuestion['question'] = stripcslashes($pollQuestion['question']);
    
        $this->pollQuestion = $pollQuestion;
    }
    
    public function SetPollAnswers($pollAnswers)
    {
        // Удаляем лишние слеши
        foreach ($pollAnswers as $key => $data)
        {
            $pollAnswers[$key]['answer'] = stripcslashes($data['answer']);
        }
        $this->pollAnswers = $pollAnswers;
    }
    
    public function GetPollQuestion()
    {
        return $this->pollQuestion;
    }
    
    public function GetPollAnswers()
    {
        return $this->pollAnswers;
    }   
    
    public function SetPollResult($pollResult)
    {
        // Удаляем лишние слеши
        foreach ($pollResult as $key => $data)
        {
            $pollResult[$key]['question'] = stripcslashes($data['question']);
            $pollResult[$key]['answer'] = stripcslashes($data['answer']);
        }
            
        $this->pollResult = $pollResult;
    }
    
    public function GetPollResult()
    {
        return $this->pollResult;
    }
    
    public function SetBannerData($data)
    {
        $this->banner = $data;
    }
    
    public function GetBannerData()
    {
        return $this->banner;
    }
}

?>