<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2008/07/01 08:47:35 $';

class GE_ControlStart
{
    public $errormsg;
    
    public $errorname;
    
    /**
     * ОБъект для работы с бд
     *
     * @var DS_Common
     */
    private $common;
    
    /**
     * пользователь с которым работаем
     *
     * @var DE_User
     */
    private $user;
    
    function __construct()
    {   
        $this->_validSession();
        $this->common = DS_Common::CreateForCurentDB();
        //$common = new DS_Common();    
        GE_SearchStart::SetCommon($this->common);
        $this->user = DE_User::CreateFromID($_SESSION['uid']);
        if(!$this->getPostData($_REQUEST))
        {
            echo $this->errormsg;
        }
    }

    private function _validSession()
    {
        if (!key_exists("uid", $_SESSION)) exit;
        assert(key_exists("lang", $_SESSION));
        assert(key_exists("language", $_SESSION));
        assert(key_exists("time_delta", $_SESSION));
        assert(key_exists("s_login", $_SESSION));
        assert(key_exists("s_pass", $_SESSION));
        assert(key_exists("profi_game", $_SESSION));
//      assert(key_exists("after_reg_trial", $_SESSION));
        assert(key_exists("dbId", $_SESSION));

        SU_ClientKey::checkKey(false);
        SU_ClientKey::setKey(false);
    }
    
    function getPostData($data)
    {
        include_once($_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/index_start_errors.lang");

        $mes = '';
        $idQuestion = false;
        
        if(isset($data["start"])) {
        
            // пробуем зайти в игру
            if(!GE_ExecStartGameEnter::Exec($_REQUEST))
            {
                $mes = $lang_err[GE_ExecStartGameEnter::FetchErrorCode()];
            } else {
                exit;
            }
        } elseif(isset($data["add"])) {
            // пробуем создать инстанс
            if(!GE_ExecStartCreateInstance::Exec($_REQUEST))
            {
                $mes = $lang_err[GE_ExecStartCreateInstance::FetchErrorCode()];
            } else {
                exit;
            }
        } elseif(isset($data["add_premium_present"])) {
            // добавляем подарок
            GE_ExecGetPremiumPresent::Get($this->user);
            if($_SESSION['profi_game'])
            {
                $mes = $lang_err['PREMIUM_PRESENT_ADDED'];
            } else {
                $mes = $lang_err['PREMIUM_PRESENT_RELOGIN'];
            }
        } elseif(isset($data["support"])) {
            $res = GE_ExecSupport::Exec($this->user, $data);
            $mes = $lang_err[$res];
        } elseif(isset($data["changemail"])) {
            $res = GE_ExecChangeMail::Exec($this->user, $data);
            $mes = $lang_err[$res];
        } elseif(isset($data["activationemail"])) {
            GE_ExecChangeMail::SendActivationMail($this->user);
            $mes = $lang_err['ACTIVATION_MAIL_SEND'];
        } elseif(isset($data["submitpoll"])) {
            //Смотрим, не ответил ли игрок уже на этот вопрос
            $isAnswered = GE_SearchStart::IsUserAnswered($this->user->getID(), (int)$data['id_question']);
            if ($isAnswered === false)
            {
                //Если игрок нажал "воздержаться"
                if (key_exists('abstain', $data)) {
                    //Записываем, что игрок воздержался
                    $data['id_answer'] = 0;
                    GE_SearchStart::SubmitPoll($this->user, $data);
                } else {
                    //Смотрим, выбран ли ответ
                    if (!isset($data['id_answer'])) {
                        $mes = $lang_err['ANSWER_NON_CHOOSEN'];
                    } else {
                        //Засчитываем ответ
                        GE_SearchStart::SubmitPoll($this->user, $data);
                        $mes = $lang_err['YOU_VOICE_WAS_CONSIDERED'];

                        $idQuestion = (int)$data['id_question'];
                    }
                }
            } else {
                if (!key_exists('abstain', $data))
                {
                    $idQuestion = (int)$data['id_question'];
                }
            }
        }

        GE_ExecStartWorldsList::Exec($mes, $idQuestion);

        return true;
    }
}

/*
Дорогие игроки! 
В последнее время в рунете участились случаи воровства аккаунтов.
Убедительно просим вас сменить ваш игровой пароль. 
Большое спасибо и извините за неудобства.
*/
?>