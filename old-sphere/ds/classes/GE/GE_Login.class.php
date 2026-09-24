<?php $CVS_Version = '$Revision: 1.9 $ ; $Date: 2008/07/17 13:10:01 $';

require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

class GE_Login
{

    /**
     * ТЕкст сообщения об ошибке
     *
     * @var string
     */
    public $errormsg = '';

    /**
     * ОБект для работы с БД
     *
     * @var DS_Common
     */
    private $common;

    /**
     * Логин пользователя
     *
     * @var string
     */
    private $login;

    /**
     * Пароль игрока
     *
     * @var string
     */
    private $password;

    /**
     * Флаг действия
     *
     * @var string
     */
    private $action;


    /**
     * ID пользователя
     *
     * @var unknown_type
     */
    private $uid = 0;

    private $disable = false;

    private $welcomePage = false;

    private $language;

    public function __construct($welcomePage = false, $language = false)
    {
        global $metaua_user;
        assert(is_bool($welcomePage));

        $this->welcomePage = $welcomePage;

        if($language !== false)
        {
            $this->language = $language;
        } else {
            $this->language = constant('DEFAULT_LANGUAGE_SHORT');
        }

        $this->common = DS_Common::CreateForCurentDB(0);

        $this->_reciveParams(array_merge($_POST, $_GET));
        //$this->_reciveParams($_REQUEST);
        if (isset($_REQUEST['cb']))
        {
            $this->_checkKlaseLogin();
            exit;
        }

        if  ((stripos($_SERVER['HTTP_HOST'], 'ds.meta.ua')!==false) && ($this->action != 'logout'))
        {
            if ($this->_checkMetaUALogin())
            {
                SU_GameSessionWork::SessionStart();
                return;
            }
        }


        //if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
        //{
        //die(var_export($_REQUEST, true));
        //}

        switch($this->action)
        {
            case 'Login':
                $this->_getUIDByLoginAndPassword();
                $this->_execLogin();
                break;
            case 'logout':
                SU_GameSessionWork::SessionContinue();
                GE_Login::execLogout();
                break;
            default:
                GE_Login::execLogout(false);
                $this->selectAction();
        }
    }

    /**
     * Проверка введеного логина и пароля и получение ID юзера
     *
     */
    private function _getUIDByLoginAndPassword()
    {
        if (stripos($_SERVER['HTTP_HOST'], 'ds.klase.lt')!==false) //для литовца - авторизация через
        //klase.lt
        {
            $this->_checkKlaseLogin();
            SU_GameSessionWork::SessionStart();
            return;
        }
        else if (stripos($_SERVER['HTTP_HOST'], 'ds.meta.ua')!==false)
        {
            if ($this->_checkMetaUALogin())
            {
                SU_GameSessionWork::SessionStart();
                return;
            }
        }

        $this->uid = VD_User::Validate($this->login, $this->password, $this->common);
        if (VD_User::FetchErrorCode()) {
            $this->selectAction(VD_User::FetchErrorCode());
            exit;
        }
        SU_GameSessionWork::SessionStart();
    }


    private function _checkKlaseLogin()
    {

        if (!isset($_REQUEST['cb']))
        {
            $callback_url = 'http://'.$_SERVER['HTTP_HOST'].'/ds/index_login.php?cb=1';
            GetToken($callback_url);
        }
        else
        {
            list($userdata,$token) = GetUserData();
            setcookie('oauth_token', '');
            setcookie('oauth_token_secret', '');
            setcookie('klaselt_name',$userdata->response->Name, null, '/');
        }

        if(!$userdata->response)
        {

            sleep(1);
            $this->selectAction('5_WRONG_USER_NAME_OR_PASSWORD');
            exit;
        } else {

            $klaseLogin = new GE_KlaseLogin($token);

            $user = $klaseLogin->execLogin();

            if(!(is_object($user) && $user instanceof DE_User))
            {
                setcookie('open_key',$token->key, null, '/');
                setcookie('secret_key',$token->secret, null, '/');
                //header("Location: reg_wizard.php?step=1");
                header("Location: index_registration.php");
                exit;
            }

            $this->_getUIDByKlaseLogin();
            $this->_execLogin();
            exit;
        }
    }

    private function _checkMetaUALogin()
    {
        global $metaua_user;

        if (!$metaua_user)
        {
            return false;
        }
        else
        {
            $metauaLogin = new GE_MetaUALogin($metaua_user);

            $user = $metauaLogin->execLogin();

            if(!(is_object($user) && $user instanceof DE_User))
            {
                if ((!$_POST && (@$_GET['mode']!=='login')) || (isset($_POST['from_main'])))
                {
                    header("Location: reg_wizard.php?step=1");
                    exit;
                }
                return false;
            }

            $this->_getUIDByMetaUALogin();
            $this->_execLogin();
            exit;
        }

        return true;

    }


    /**
     * Проверяем наличие ID пользователя, если логинимся через Klase.lt ID
     *
     */
    private function _getUIDByKlaseLogin()
    {
        SU_GameSessionWork::SessionContinue();
        assert(key_exists('uid', $_SESSION));
        $this->uid = (int) $_SESSION['uid'];
        assert($this->uid > 0);
    }

    /**
     * Проверяем наличие ID пользователя, если логинимся через meta.ua
     *
     */
    private function _getUIDByMetaUALogin()
    {
        SU_GameSessionWork::SessionContinue();
        assert(key_exists('uid', $_SESSION));
        $this->uid = (int) $_SESSION['uid'];
        assert($this->uid > 0);
    }


    /**
     * Вход в игру
     *
     */
    private function _execLogin()
    {
        $_SESSION["dbId"] = 0;

        $user = DE_User::CreateFromID($this->uid);

        $_SESSION["uid"] = (int)$user->getID();
        $_SESSION["lang"] = (int)$user->getLanguageID();
        $_SESSION["language"] = $user->getLangShortName();
        $_SESSION['time_delta'] = (int)($user->getTimeDelta() + constant('TIME_DELTA_DELTA'));
        $_SESSION["s_login"] = $user->getName();
        $_SESSION["s_pass"] = $user->getPassword();
        $_SESSION["partner"] = $user->getPartnerID();
        $_SESSION["LOGIN_ADM_ENTER"] = false;

        $res = $this->common->executeQuery("begin");
        assert(empty($this->common->errormsg));

        ME_LoginHistory::Exec($user->getID(), $this->common);
        TMP_NewYear::givePremiumPresent($user->getID());

        if(DS_User::IsPremium($user->getID()))
        {
            $_SESSION['profi_game'] = 1;
        } else {
            if(GL_HaveTrialPremium::GetMode($user))
            {
                $_SESSION['profi_game'] = 1;
                $_SESSION['after_reg_trial'] = 1;
            }else{
                $_SESSION['profi_game'] = 0;
                $_SESSION['after_reg_trial'] = 0;
            }
        }


        $res = $this->common->executeQuery("commit");
        assert(empty($this->common->errormsg));

        SU_ClientKey::setKey(false);
        SU_ClientKey::setCheckGetVars(true);
        $str = "Location: index_start.php?ck=".$_SESSION['client_key'];
        if(!isset($_COOKIE[session_name()]))
        {
            $str .= "&".session_name()."=".session_id();
        }
        //die(var_export($str, true));

        Header($str);
        exit;
    }

    static private function _redirect()
    {
        $back = constant("GAME_HOST");
        $langstr = '';
        if(constant('LOCAL_LOGIN_ENABLE'))
        {
            if (isset($_REQUEST['lang']))
              $langstr = '?lang='.$_REQUEST['lang'];
            $back .= '/ds/index_login.php'.$langstr;
        }
        $str = "Location: ".$back;
        Header($str);
        exit;
    }

    public static function execLogout($redirect = true)
    {
        if(session_id() === '')
        {
            if($redirect)
            {
                GE_Login::_redirect();
            }

            return;
        }

        SU_GameSessionWork::SessionDestroy();

        if (stripos($_SERVER['HTTP_HOST'],'ds.klase.lt') !== false)
        {
            header('Location: /');
            exit;
        }
        else if (stripos($_SERVER['HTTP_HOST'], 'ds.meta.ua')!==false)
        {
            //die(var_export($_POST, true));
            header('Location: http://meta.ua');
            exit;
        }

        if($redirect)
        {
            GE_Login::_redirect();
        }
    }

    /**
     * Разбор входных данных
     *
     * @param unknown_type $data
     */
    private function _reciveParams($data)
    {
        assert(is_array($data));

        if(key_exists('login', $data))
        {
            $this->login = $data['login'];
        }

        if(key_exists('version', $data))
        {
            $this->login .= '__'.$data['version'];
        }

        if(key_exists('pass', $data))
        {
            $this->password = $data['pass'];
        }

        if(key_exists('enter', $data))
        {
            $this->action = $data['enter'];
        }

        if(key_exists('logout', $data))
        {
            $this->action = 'logout';
        }
    }

    function selectAction($str = '')
    {
        if( constant('LOCAL_LOGIN_ENABLE'))
        {
            $this->showLoginForm($str);
        } else {
            if(!empty($_SERVER['HTTP_REFERER']))
            {
                if(ereg($_SERVER['SCRIPT_NAME'], $_SERVER['HTTP_REFERER']))
                {
                    if(!empty($str[1]))
                    {
                        $str = $str[1];
                    } else {
                        $str = "";
                    }

                    $this->showLoginForm($str);
                } else {
                    $this->redirect2MainPage($str);
                }
            } else {
                if(!empty($str[1]))
                {
                    $str = "<p><b>".$str[1]."</b></p>";
                } else {
                    $str = "";
                }
                $this->redirect2MainPage($str);
            }
        }
    }

    function redirect2MainPage($str)
    {
        $url = "Location: /index.php";
        if($str)
        {
            $url .= "?p=login_error&error=".$str[0];
        }
        Header($url);
        exit;
    }


    function showLoginForm($str = "")
    {
        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = TEMPLATES_PATH;
        $tpl->compile_dir = constant("SMARTY_CACHE_PATH");
        $tpl->compile_check = true;
        $tpl->debugging = false;

        $language = constant('DEFAULT_LANGUAGE_SHORT');
        $lang = array();
        if($this->welcomePage)
        {
            include_once ( "./language/".$language."/index_welcome.lang" );
        } else {
            include_once ( "./language/".$this->language."/index_login.lang" );
        }
        $tpl->assign("lang", $lang);
        if (strstr($_SERVER['SERVER_NAME'], 'ds-game.su')) {
            $tpl->assign("version_postfix", '_pulsar');
            $tpl->assign("forum_link", "http://forum.ds-game.ru/viewforum.php?f=78");
        }
        else {
            $tpl->assign("forum_link", "https://forum.destinysphere.ru");
        }

        if($str != "")
        {
            $tpl->assign("ERROR_TEXT", $str);
        }

        //if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
       // {     
          //ini_set('display_errors', 1);
          //error_reporting(E_ALL);
          
          //$news = SU_PortalNews::Get('top_news');
          //$tpl->assign("news", $news['top_news']);
       // }
        // Рамблер ID

        include("./UsersOnline.php");
        $online = UsersOnline::Get(false);
        $tpl->assign("USERS_ONLINE", $online);


    $lpath  = array(1=>'index_login_de/',2=>'index_login_en/', 3=>'');
    $deflang = array('G'=>1,'E'=>2,'R'=>3,'L'=>'5');
    $showlang = isset($_REQUEST['lang'])?intval($_REQUEST['lang']):$deflang[constant('DEFAULT_LANGUAGE_SHORT')];
    $tpl->assign("showlang", $showlang);
        $tpl->assign("lang_path", $lpath[$showlang]);

        if (constant('BILLING_PARTNER_ID')==10)
        {
          global $cfg;
          require_once("../sys/cfg.php");
        
          require_once("../sys/qqmod_txt.php");
          require_once("../sys/qqpdo.php");     
          $txtm = new qqmod_txt();
      list($news, $cnt)  = $txtm->getList(7, 1, $cfg['txt_count_on_page']);     
      if ($news)
      {
       $lastnews = $news[0];
       $lastnews['vvodka'] = iconv('UTF-8','ISO-8859-1',$lastnews['vvodka']);
      } 
      else   
        $lastnews = array();
          $tpl->assign("lastnews", $lastnews);
        }  
        if($this->welcomePage)
        {
            $tpl->display("index_welcome.tpl");
        } else
        {
            if (stripos($_SERVER['PHP_SELF'], 'index_login_de.php') !== false)
            $tpl->display("index_login_de.tpl");
            else
            $tpl->display("index_login.tpl");
        }
    }//END OF function showLoginForm
}//END OF CLASS

?>