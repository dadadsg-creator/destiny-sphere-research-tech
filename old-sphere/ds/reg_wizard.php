<?php
require_once( 'Main.php' );
require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";
require_once('classes/Validator.class.php');

$deflang = array('G'=>1,'E'=>2,'R'=>3,'L'=>'5');

$lang = isset($_REQUEST['lang'])?intval($_REQUEST['lang']):$deflang[constant('DEFAULT_LANGUAGE_SHORT')];
$lchar = array(1=>'G',2=>'E',3=>'R', 5=>'L');
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

//include_once("./language/".$lchar[$lang]."/index_start_errors.lang");
include_once("./language/R/index_start_errors.lang");

//ini_set('display_errors', 1);
//error_reporting(E_ALL);
$charset = array('R' => 'windows-1251', 'G' => 'UTF-8', 'E' => 'windows-1251','L' => 'windows-1257');
header("Content-Type: text/html; charset=".$charset[$lchar['lang']]);
$metaua_registered = 0;
if (stripos($_SERVER['HTTP_HOST'], 'ds.klase.lt')!==false)
{
    require_once('config/klasecfg.php' );
    require_once("../sys/OAuth.php");
}
else if (stripos($_SERVER['HTTP_HOST'], 'ds.meta.ua')!==false)
{
    require '../sys/auth.class.php';
    //ini_set('display_errors', 1);
    //error_reporting(E_ALL);
    $metaua_user = new GlobalClassAuth("REDACTED_API_SECRET","destinysphere");
    //var_export($metaua_user);
    //echo $u->sUserName;
    if ($metaua_user->sUserName)
    $metaua_registered = 1;
}

function getParam($p)
{
    global $URL_Params;
    if (($p=='step') && (!array_key_exists($p, $URL_Params)))
    {
        if (in_array(@$_COOKIE[$p], array('1', '2','3')))
        return $_COOKIE[$p];
    }

    if (array_key_exists($p, $URL_Params))
    {
        if (($p=='step' && in_array($URL_Params[$p], array('1', '2','3'))) || ($p !='step'))
        {
            setcookie($p, $URL_Params[$p]);
        }
        return $URL_Params[$p];
    }
    else if (array_key_exists($p, $_COOKIE))
    {
        return $_COOKIE[$p];
    }
    return '';
}

$tpl = new Smarty;
$tpl->template_dir = TEMPLATES_PATH;
$tpl->compile_dir = constant("SMARTY_CACHE_PATH");
$tpl->compile_check = true;
$tpl->debugging = false;

if ( !isset ( $GLOBALS["common"] ) )
{
    $common = DS_Common::CreateForCurentDB(0);

}


SU_GameSessionWork::SessionStart();

/*if (!SU_GameSessionWork::SessionContinue())
{
SU_DebugTools::LogAll('SessionStart', 'regw.log');
    SU_GameSessionWork::SessionStart();
}*/
$URL_Params = array_merge($_GET, $_POST);

$act  = @$URL_Params['act'];
$mode = @$URL_Params['mode'];

$race = getParam('race');
$step = getParam('step');
$recomendUserId = getParam('ur');
$user = getParam('user');
$id_user = getParam('id_user');
$combname = getParam('combname');
$world = getParam('world');
$email = getParam('email');
$pass = getParam('pass');
$world_id = 6;
$base_id = 0;
$allWorlds = array();
$idp = intval(getParam('idp'));
$ids = intval(getParam('ids'));

//var_export($_SESSION);

if (!$step || ($step>3))
$step = 1;
$_SESSION["dbId"] = 0;


if ($step == 1)
{
    $referer = @$_SERVER['HTTP_REFERER'];
    //if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
    //SU_DebugTools::LogAll('referer='.$referer, 'regw.log');
    if ((stripos($referer, 'onlineguru.ru') !== false) || (stripos($referer, '177400') !== false))
    {
        //if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
        //SU_DebugTools::LogAll('ONLINEGURU', 'regw.log');

        setcookie("partner", 11, time()+3600*12, "/");//кука действует 12 часов
    }
    else if (stripos($referer, 'meta.ua') !== false)
    {
        setcookie("partner", 3, time()+3600*12, "/");//кука действует 12 часов
    }
    else
    {
        if ($idp > 0)
        {
            setcookie("partner", $idp, time()+3600*12, "/");//кука действует 12 часов
            setcookie("ids", $ids, time()+3600*12, "/");//кука действует 12 часов
        }
    }

    if (!isset($_COOKIE['ref']))
    {
        $refenc = urlencode($referer);
        setcookie("ref", $refenc, time()+3600*24*14, "/");
    }
}


//echo "step=$step<br>";
$status = 1;
$errorMsg = '';
$_SESSION['lang'] = $lang;
if ($act=='sbm')
{
    switch ($step)
    {
        case 1:

            {
                $email = trim($email);
                $v[0] = new ValidateUser($user);
                $v[1] = new ValidatePassword($_POST['pass'],$_POST['conf'], $_POST['user']);
                $v[2] = new ValidateEmail($email, $email);

                //$v[2] = new ValidateEmail($_POST['checkbox']);
                $errorMsg = "";

                //var_export($v);
                foreach($v as $validator)
                {
                    if($validator && !$validator->isValid())
                    {
                        $error = $validator->getError();

                        if(isset($error[1]))
                        {
                            if (constant('DEFAULT_LANGUAGE_SHORT')=='G')
                            {
                                $error[1] = iconv("ISO-8859-1", "UTF-8", $error[1]);
                            }
                            $errorMsg .= $error[1]."<br/>\n";
                        }
                    }
                }
                if (@$_POST['checkbox'] != 1)
                {
                    if ($lang==1)
                      $errorMsg .= "Sie sollen mit AGB zustimmen<br/>\n";
                    else if ($lang==2)
                      $errorMsg .= "You must confirm license agreement<br/>\n";
                    else if ($lang==3)
                      $errorMsg .= "Вы должны принять пользовательское соглашение<br/>\n";
                }


                if (!$errorMsg)
                {
                    $step++;
                    //$_SESSION['step'] = $step;
                    setcookie("step", $step);
                    $id_user = InsertUserData();
                    setcookie('id_user', $id_user);
                }
                break;
            }
        case 2:
            {
                $step++;
                setcookie("step", $step);
                break;
            }
        case 3:
            {
                if ($world) {
                    list($world_id, $base_id) = explode('_', $world);
                }
                $errorMsg = '';
                //echo "world_id=$world_id, base_id=$base_id<br>";
                //SU_DebugTools::LogAll("WORLD_SEL world_id=$world_id, base_id=$base_id", 'daniil.log');
                $common->setDbId($base_id);

                GE_SearchBase::SetCommon($common);
                if(!VD_CombName::Validate($combname, true, $common))
                {
                    $errorMsg = $lang_err[VD_CombName::FetchErrorCode()]."<br>";
                }
                // проверяем есть ли свободное место
                else if(GE_SearchStart::GetFreeCombs((int)$world_id, (int)$race) == 0)
                {
                    $errorMsg .= $lang_err['NO_FREE_COMBS']."<br>";
                }
                else if(GameDown($base_id, $world_id))
                {
                    $errorMsg .= $lang_err['WORLD_CLOSED']."<br>";
                }
                else
                {
                    //$step++;
                    setcookie("step", $step);
                }

                if (!$errorMsg)
                {

                   //$_SESSION['uid'] = $id_user;
                   //$_SESSION['profi_game'] = 0;
           $_SESSION["dbId"] = 0;

           $user = DE_User::CreateFromID((int)$id_user);

           $_SESSION["uid"] = (int)$user->getID();
           $_SESSION["lang"] = (int)$user->getLanguageID();
           $_SESSION["language"] = $user->getLangShortName();
           $_SESSION['time_delta'] = (int)($user->getTimeDelta() + constant('TIME_DELTA_DELTA'));
           $_SESSION["s_login"] = $user->getName();
           $_SESSION["s_pass"] = $user->getPassword();
           $_SESSION["partner"] = $user->getPartnerID();
           $_SESSION["LOGIN_ADM_ENTER"] = false;

           $res = $common->executeQuery("begin");
           assert(empty($common->errormsg));

           ME_LoginHistory::Exec($user->getID(), $common);

           if(DS_User::IsPremium($user->getID()))
           {
             $_SESSION['profi_game'] = 1;
           }
           else
           {
             if(GL_HaveTrialPremium::GetMode($user))
             {
                $_SESSION['profi_game'] = 1;
                $_SESSION['after_reg_trial'] = 1;
             }
             else
             {
                $_SESSION['profi_game'] = 0;
                $_SESSION['after_reg_trial'] = 0;
             }
          }

          $res = $common->executeQuery("commit");
          assert(empty($common->errormsg));

          $_SESSION["dbId"] = 0;

          SU_ClientKey::setKey(false);
          SU_ClientKey::setCheckGetVars(true);

          $_REQUEST['ck'] =

          // $_SESSION['client_key'] = GB_RandomPassword::Generate(10);
          //$_SESSION['client_key_check_get'] = 1;


          $data  = array();
          $data['world'] = $world_id.'_'.$base_id;
          $data['race'] = $race;
          $data['comb'] = $combname;

                setcookie('step', 0);

          GE_ExecStartCreateInstance::Exec($data, true);

          exit;

                    list($status, $errorMsg) =  CreateComb();

                    if ($status==1)
                    {
                        $_POST['login']  = $user;
                        $_POST['pass']  = $pass;
                        $_POST['enter'] = 'Login';
                        $log = new GE_Login;


            exit;
                    }
                }
                else
            {

                    $checkedstr = '';
                    if ($_POST['checkbox'])
                    $checkedstr = 'checked';
                }

                break;
            }
    }
}

showStep($step, $errorMsg);

function CreateComb()
{
    global $id_user, $race, $world_id, $base_id, $combname, $allWorlds, $world;
    $data = array();
    $data['world'] = $world;
    $data['race']  = $race;
    $data['comb']  = $combname;
    $_SESSION['profi_game'] = 1;
    $_SESSION['uid'] = $id_user;
    $_SESSION['dbId'] = 0;
    //Занесём  в user_instance инфу о расе и сфере

  //if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
    //SU_DebugTools::LogAll("CreateComb world=$world, race=$race, combname=$combname","reg.log");

    if (!GE_ExecStartCreateInstance::Exec($data, false))
    {
        if (GE_ExecStartCreateInstance::FetchErrorCode())
        {
          return array(0, GE_ExecStartCreateInstance::FetchErrorCode());
        }
    }

    return array(1,'');
}

function InsertUserData()
{
    global $tpl, $user, $email, $checkedstr, $recomendUserId, $charset, $metaua_registered, $metaua_user;
    global $common;
    $common->setDbId(0);

    GE_SearchBase::SetCommon($common);

    $language = constant("DEFAULT_LANGUAGE_SHORT");

    $klasereg = 0;
    if ( stripos($_SERVER['HTTP_HOST'],'ds.klase.lt') !==false )
    $klasereg = 1;

    $enabled = constant("ACTIVATION_EMAIL_ENABLED");
    $mailActivate = 0;

    if (($language  == 'L') ||  $klasereg  || $metaua_registered)
      $mailActivate = 1;

    if (@$_POST['lang'])
    $reg_lang = $_POST['lang'];
    else
    {
        $l = constant("DEFAULT_LANGUAGE_SHORT");
        if ($l=='L')
        $reg_lang = 5;
        else if (($l=='G') or ($l=='E'))
        $reg_lang = 1;
        else
        $reg_lang = 3;
    }

    if (isset($_COOKIE["partner"]))
      $partner = intval($_COOKIE["partner"]);
    else if (isset($_COOKIE["idp"]))
      $partner = intval($_COOKIE["idp"]);
    else
    $partner = 2;

    if (isset($_COOKIE["ids"]))
      $ids = intval($_COOKIE["ids"]);
    else
      $ids = 0;

    if (stripos($_SERVER['HTTP_HOST'],'ds.klase.lt'))
    {
        $partner = 21;
    }
    else if (stripos($_SERVER['HTTP_HOST'], 'ds.games.tut.by') !== false)
    {
        // ds.games.tut.by и ds.games.tut.by один и тот же партнерs
        $partner = 41;
    }

    if ($metaua_registered)
    {
        $partner = 3;
    }


    if (isset($_COOKIE["ref"]))
    $reg_referer = pg_escape_string(substr(urldecode($_COOKIE["ref"]), 0, 255));
    else
      $reg_referer = '';

    //$common->executeQuery("begin");

    $sql = "
        INSERT INTO DS_User
        (
            ID_Language,
            UserName,
            Password,
            Email,
            registrationdate,
            lastrefresh,
            vacationmode,
            accountactive,
            admin,
        time_delta,
            activate_mail,
            from_partner,
      reg_referer,
      ip_addr,
      from_site_id
    )
        VALUES
        (
            $reg_lang,
            '".$user."',
            '".md5($_POST['pass'])."',
            '".$email."',
            'now()',
            'now()',
            false,
            ".constant("REGISTRATION_ENABLED").",
            false,
            ".constant("DEFAULT_TIMEDELTA").",
            ".$mailActivate.",
            ".$partner.",
      '".$reg_referer."',
      '".$_SERVER['REMOTE_ADDR']."',
      $ids
        )
        ";

    $res = $common->executeQuery($sql);
// SU_DebugTools::LogAll("$sql, err=".$common->errormsg.",res=".var_export($res, true),'regwizard.log');
    if($res === false)
    {
        //echo "$sql\n";
        //echo $common->errormsg;
        return array(0,"SYSTEM ERROR - Registration::getPostData1 ");
    }

    $sql = sprintf("select id_user
                    from ds_user
                        where username = '%s'
                        and password = '%s'",
    $user,
    md5 ( $_POST['pass']));

    $res = $common->getQueryArray($sql);
    if(empty($res))
    {
        echo $common->errormsg;
        die("SYSTEM ERROR - Registration::getPostData2");
    }

    $id_user = (int)$res[0]['id_user'];

    if ($klasereg || $metaua_registered)
    {
        $open_key    = $_COOKIE['open_key'];
        $secret_key  = $_COOKIE['secret_key'];
        if ($klasereg)
        {
            if (!$open_key || !$secret_key)
            {
                header("Location: /");
                exit;
            }
            $token_username = $_COOKIE['klaselt_name'];
        }
        if ($metaua_registered)
        {
            $open_key    = md5(time().rand(0, 1000));
            $secret_key  = 'metaua';

            $token_username = $metaua_user->sUserName;
        }


        $sql = "INSERT INTO ds_oauthinfo(open_key, secret_key, token_username, game_uid)
                  VALUES('{$open_key}', '{$secret_key}', '{$token_username}', {$id_user})";
        $res = $common->executeQuery($sql);
        if($res === false)
        {
            //echo "$sql\n";
            //echo $common->errormsg;
            return array(0,"SYSTEM ERROR - Registration::ds_oauthinfo ");
        }
    }

    //$common->executeQuery("commit");
    //////////////////////////// ПОДАРОЧНЫЙ ПРЕМИУМ ПРИ ПЕРЕХОДЕ ПО ССЫЛКЕ ОТ ПАРТНЕРА /////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////// Рекомендованный игрок //////////////////////////////////////////////////////////////////////
    if (isset($_POST['ur']) && !empty($_POST['ur']) && $partner==2)
    {

        if (!DS_User::GetByID($_POST['ur']))
        $recomendUserId = 0;
        else
        $recomendUserId = $_POST['ur'];
    }
    else
    {
        $recomendUserId = 0;
    }

    addPremiumPresentFromPartner($id_user);

    if($recomendUserId)
    {
        addRecomendUser($id_user, $recomendUserId);
    }

    //////////////////////////// Отправка оповещения по почте ////////////////////////////////////////////////////////////////////


    //$enabled = true;
    if( $enabled )
    {
        //SU_DebugTools::LogAll("IN SEND LETTER", 'daniil.log');

        include_once("language/".$language."/reg_latter_".VERSION_NAME.".lang");

        require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = TEMPLATES_PATH;
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;

        $tpl->assign('lang', $lang);


        $tpl->assign('LOGIN', $_POST['user']);
        if ($metaua_registered)
        $tpl->assign('PASSWORD', $_POST['pass'].' (или используйте авторизацию через meta.ua)');
        else if (!$metaua_registered && !$klasereg)
          $tpl->assign('PASSWORD', $_POST['pass']);
        else
          $tpl->assign('PASSWORD', 'Use your klase.lt authorization');
        //игроки из klase.lt могут входить только будучи
        //авторизоваными в klase.lt, поэтому им пароль не
        //высылается

        if(!$metaua_registered)
        {
            $tpl->assign('URL', GAME_HOST.'/ds/index_registration.php?action=activate&idp='.$partner.'&ids='.$ids.'&key='.md5($_POST['user'].$id_user));
        } else {
            $tpl->assign('URL', '');
        }

        //$tpl->assign('GAMEURL', constant('GAME_HOST').'/ds/index_login.php');
    $tpl->assign('GAMEURL', 'http://'.$_SERVER['HTTP_HOST'].'/ds/index_login.php');

        if (constant("DEFAULT_LANGUAGE_SHORT")=='L')
        {
            $latter = $tpl->fetch('reg_letter_litva.tpl');
        }
        else
        {
            $latter = $tpl->fetch('reg_latter.tpl');
        }

        mail_utf8($_POST['email'], $lang['LATTER_SUBJECT'], $latter, $charset[constant("DEFAULT_LANGUAGE_SHORT")]);
    }

    return $id_user;
}


function addRecomendUser($id_user, $recomendUserId)
{
    global $common;
    $common->setDbId(0);

    $sql = "
            INSERT INTO ds_user_registration_recomend
                (id_user, id_user_recomend)
            VALUES
                ($id_user, $recomendUserId)
            ";
    $common->executeQuery($sql);
    if(!empty($common->errormsg))
    {
        //$errorMsg = "SYSTEM ERROR - Registration::AddUserRecomend";
        SU_DebugTools::LogAll("sql={$sql}, err=".$common->errormsg, 'reg.log');

        return false;
    }

    return true;
}

function showStep($step, $errorMsg)
{
    global $tpl, $race, $pass, $user, $id_user, $metaua_user, $metaua_registered, $world_id, $base_id, $combname, $allWorlds, $world, $email, $checkedstr, $recomendUserId, $idp, $ids;
    global $common;

    $common->setDbId(0);
    $voranchecked = $lienchecked = $psolaochecked = '';

    if ($race==1) $voranchecked  = 'checked';
    else if ($race==2) $lienchecked = 'checked';
    else if ($race==3) $psolaochecked = 'checked';
    else $lienchecked  = 'checked';



    //$language = constant("DEFAULT_LANGUAGE_SHORT");
    //include_once("./language/".constant("DEFAULT_LANGUAGE_SHORT")."/index_registration.lang");

    switch ($step)
    {
        case 1:
            {
                $klase = 0;

                if (stripos($_SERVER['HTTP_HOST'], 'ds.klase.lt')!==false)
                {
                    if (isset($_COOKIE['klaselt_name']))
                    {
                        $email = $_COOKIE['klaselt_email'];
                        if (!$user)
                        $user = $_COOKIE['klaselt_name'];
                        //$tok = addslashes($_GET['oauth_token']);
                        //$user = GE_SearchStart::GetUserByToken($tok);
                        //die(var_export());
                        $klase = 1;

                        $tpl->assign('password',GB_RandomPassword::Generate());
                        $tpl->assign('KLASE_REGISTRATION', true);
                        //echo "klaselt_email=".$_COOKIE['klaselt_email'];
                    }
                    else
                    {
                        header("Location: /");
                        die;
                    }
                }

                if ($metaua_registered)
                {
                    $email = $metaua_user->sUserName.'@meta.ua';
                    $tpl->assign('USERNAME', $metaua_user->sUserName);
                    $tpl->assign('password', GB_RandomPassword::Generate());
                    $tpl->assign('METAUA_REGISTRATION', true);
                }

                if ($_POST)
                {
                    if (@$_POST['checkbox'] == 1)
                    {
                        $tpl->assign('checkedstr', 'checked');
                    }
                }

                break;
            }
          case 3:
            {
                GE_SearchBase::SetCommon($common);
                $_SESSION['uid'] = (int)$id_user;
                $_SESSION['profi_game'] = 0;

                $allWorlds = GE_LogicWorldsList::GetWorldsToReg(DE_User::CreateFromID((int)$id_user), false, false);

                $newAllWorlds = array();
                foreach($allWorlds as &$aw)
                {
                    $aw['id_base'] = getDBIdByName($aw['dbname']);

                    //$worldStatus = GE_SearchStart::GetWorldStatusDirect((int)$aw['id_world'], (int)$aw['id_base']);
                    $worldStatus = GE_SearchStart::GetWorldStatus((int)$aw['id_world'], (int)$aw['id_base']);
                    if(GameDown($aw['id_base'], $aw['id_world']) || $worldStatus == 'CLOSE' || $worldStatus == 'REGISTRATION_CLOSE' || $worldStatus == 'TURNIR_STARTED')
                    {
                        continue;
                    }

                    if (!$world_id) $world_id = $aw['id_world'];
                    if ($base_id==-1) $base_id  = $aw['id_base'];
                    if ($aw['id_base']==3) $aw['checked'] = 'checked';

                    $newAllWorlds[] = $aw;
                }
                if ($errorMsg)
                {
                    $errorMsg = 'Error: '.$errorMsg;
                }
        $partnercounter = '';
        if ($idp==109)
        {
            $partnercounter = '<img src="http://pix.gameleads.ru/'.$id_user.'/q1/'.md5('114'.$id_user).'">';
        }
        else if ($idp==113)
        {
            $partnercounter = '<img src="http://n.actionpay.ru/ok/112.png" width="1" height="1" />';
        }

        $tpl->assign('partnercountercode',$partnercounter);
        $tpl->assign('ALL_WORLDS',$newAllWorlds);
                break;
            }
          case '_news':
            {
                header("Location: /");
                exit;
                global $cfg;
                require($_SERVER['DOCUMENT_ROOT'].'/sys/cfg.php');
                require($_SERVER['DOCUMENT_ROOT'].'/sys/qqpdo.php');
                require($_SERVER['DOCUMENT_ROOT'].'/sys/qqmod_txt.php');

                $txtm  = new qqmod_txt();

                $cfg['txt_count_on_page']  = 3;
                $pg = isset($_REQUEST['pg'])?intval($_REQUEST['pg']):1;

                list($news, $cnt) = $txtm->getTexts(7, 1, $pg, 0);

                for($i=0; $i< count($news);$i++)
                {

                    //$news[$i]['Title']  = mb_convert_encoding($news[$i]['Title'], "windows-1251", "UTF-8");
                    //$news[$i]['vvodka'] = mb_convert_encoding($news[$i]['vvodka'], "windows-1251", "UTF-8");
                    //$news[$i]['arttxt'] = mb_convert_encoding($news[$i]['arttxt'], "windows-1251", "UTF-8");

                    $news[$i]['Title']  = iconv("UTF-8", "windows-1251", $news[$i]['Title']);
                    $news[$i]['vvodka'] = iconv("UTF-8", "windows-1251", $news[$i]['vvodka']);
                    $news[$i]['arttxt'] = iconv("UTF-8", "windows-1251", $news[$i]['arttxt']);

                }
                $cnt = ceil($cnt / $cfg['txt_count_on_page']);

                $PageList  = $txtm->PrintNumPages($pg, 5, $cnt, 0, 'reg_wizard.php','','&','','cgi','&step=_news', 'style="color:yellow"');
                $PageList = mb_convert_encoding($PageList, "windows-1251", "UTF-8");
                $tpl->assign('PageList', $PageList);
                $tpl->assign('news', $news);
            }
    }
    include("./UsersOnline.php");
    $online = UsersOnline::Get(false);

    $claseltcode = '';
    if (stripos($_SERVER['HTTP_HOST'], 'ds.klase.lt')!==false)
    {
        /*
        $claseltcode = '<script type="text/javascript">
        document.domain=\'klase.lt\';
        </script>
        <iframe src="http://www.klase.lt/API/iframe/light" width="100%" height="160"></iframe>';
        */
    }

    if (constant('BILLING_PARTNER_ID')==4)
    $forumlink = 'http://forum.destinysphere.ru';
    else if (constant('BILLING_PARTNER_ID')==6)
      $forumlink = 'http://forum.ds-game.ru';
    else if (constant('BILLING_PARTNER_ID')==9)
    $forumlink = 'http://forum.ds-game.ru/viewforum.php?f=78';

    $tpl->assign('forumlink', $forumlink);

    //img/layout/bg_main_center_clear.jpg
    $bgfile = 'bg_main_center_clear.jpg';
    if ($step==1)
      $bgfile = 'bg_main_center1.jpg';
    else if ($step==2)
      $bgfile = 'bg_main_center2.jpg';
    else if ($step==3)
      $bgfile = 'bg_main_center.jpg';

    global $lang;

    if (stripos($_SERVER['HTTP_HOST'],'ds.meta.ua')!==false)
       $backgroundurl = 'img/layout/meta/'.$bgfile;
    elseif (stripos($_SERVER['HTTP_HOST'], 'ds.games.tut.by')!==false)
        $backgroundurl = 'img/layout/gametut/'.$bgfile;
    elseif (stripos($_SERVER['HTTP_HOST'], 'ds-game.su')!==false)
        $backgroundurl = 'img/layout/limited/'.$bgfile;
    else
       $backgroundurl = 'img/layout/'.CACHING_PREFIX.$bgfile;

//     $backgroundurl = 'img/layout/meta/'.$bgfile;
    if ($lang==1)
    {
    $backgroundurl = 'img/reg_wiz_de/layout/'.$bgfile;
    }
    //echo 'backgroundurl='.$backgroundurl.'<br>';
    //echo "lang=$lang";
    $path = array(1 => 'reg_wiz_de/', 2 => 'reg_wiz_eng/', 3=>'');
    $path = $path[$lang];
    //die("path=$path, lang=$lang");
    $tpl->assign('backgroundurl', $backgroundurl);
    $tpl->assign('claseltcode', $claseltcode);

    $tpl->assign('claseltcode', $claseltcode);
    $tpl->assign("USERS_ONLINE", $online);
    $tpl->assign('step', $step);
    $tpl->assign('race', $race);
    $tpl->assign('idp', $idp);
    $tpl->assign('ids', $ids);
    $tpl->assign('user', $user);
    $tpl->assign('ur', $recomendUserId);
    $tpl->assign('pass', $pass);
    $tpl->assign('id_user',$id_user);
    $tpl->assign('world', $world);
    $tpl->assign('email', $email);
    $tpl->assign('checkedstr',$checkedstr);
    $tpl->assign('combname', $combname);
    $tpl->assign('voranchecked', $voranchecked);
    $tpl->assign('lienchecked', $lienchecked);
    $tpl->assign('psolaochecked', $psolaochecked);
    $tpl->assign('errors', $errorMsg);

    $tpl->display("{$path}reg_wizard{$step}.tpl");
}


function getDBIdByName($dbname)
{
    foreach  ($GLOBALS['dbWorldAccess'] as $id=>$db)
    {
        $dbn = $db['server'];
        //echo "$dbname, $dbn<br>";
        if ($dbname == $dbn)
        return $id;
    }
    return -1;
}

/**
     * Подарочный премиум
     *      дается при регистрации через партнеров (см. ds_partner)
     *      или
     *      можно давать всем игрокам (см. DEFAULT_PARTNER в settings.php)
     *
     * @param unknown_type $id_user
     * @return unknown
     */
function addPremiumPresentFromPartner($id_user)
{
    global $common;
    if(!is_int($id_user) || $id_user <= 0)
    {
        return false;
    }
    $_SESSION["dbId"] = 0;
    $parnterData = getPartnerPremiumPresent(getPartnerID());
    if(!is_array($parnterData) || $parnterData['premium_present'] == 0)
    {
        return;
    }

    $user = DE_User::CreateFromID($id_user);
    PU_ExecChangePremiumDays::Exec($user, (int)$parnterData['premium_present'], 'REGISTRATION', 'From partner: '.$parnterData['name']);

    $sql = "
            INSERT INTO ds_userprovidedbypartner
                (id_user, id_partner)
            VALUES
                (".$id_user.", ".$parnterData['id_partner'].")
            ";
    $res = $common->executeQuery($sql);
    if($res === false)
    {
        return false;
    }
}

function getPartnerID()
{
    if(!key_exists('pid', $_REQUEST) || strlen($_REQUEST['pid']) !== 32)
    {
        return constant('DEFAULT_PARTNER');
    }

    return $_REQUEST['pid'];
}



function getPartnerPremiumPresent($partnerID)
{
    global $common;

    // дефолтный партер, задается в settings.php
    if($partnerID == constant('DEFAULT_PARTNER'))
    {
        $sql = "
                select
                    p.name,
                    p.id_partner,
                    p.premium_present
                from
                    ds_partner as p
                WHERE
                    p.id_partner = ".$partnerID;
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));

        if(count($res) < 1)
        {
            return 0;
        }

        return $res[0];
    }

    if(!key_exists('REMOTE_ADDR', $_SERVER))
    {
        return 0;
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    if(!preg_match('/^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$/', $ip))
    {
        return 0;
    }

    $sql = "
            select
                p.name,
                p.id_partner,
                p.premium_present
            from
                ds_partner_has_ip_range as p_ip
                inner join ds_partner as p using (id_partner)
            where
                '$ip' >= p_ip.from_ip
                and
                '$ip' <= p_ip.to_ip
                and
                (
                    p.public_id = '".pg_escape_string($partnerID)."'
                    OR
                    p.public_id IS NULL
                )
            ";
    $res = $common->getResultArray("", $sql);
    assert(empty($common->errormsg));

    if(count($res) < 1)
    {
        return 0;
    }

    return $res[0];
}

function mail_utf8($to, $subject = '(No subject)', $message = '', $enc)
{
  if (strtolower($enc)!='utf-8')
  {
    $message = iconv($enc, 'utf-8', $message);
    $subject = iconv($enc, 'utf-8', $subject);
  }

  $header = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/plain; charset=UTF-8'
  . "\n" . 'From: Destiny Sphere <noreply@destinysphere.ru>\n';

  // sendmail_path in php.ini on this host is broken (just "-t -i", no
  // binary path) and it's a PHP_INI_SYSTEM directive, so it can't be
  // fixed with ini_set()/.htaccess. mail() can therefore never spawn
  // the MTA. Work around it by invoking the real sendmail binary
  // (symlinked to exim4) directly, bypassing mail() entirely.
  $rawMessage = "To: {$to}\n"
      . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\n"
      . $header . "\n"
      . $message;

  $pipe = popen('/usr/sbin/sendmail -t -i', 'w');
  if (!$pipe)
  {
      return false;
  }
  fwrite($pipe, $rawMessage);
  $exitCode = pclose($pipe);
  return ($exitCode === 0);
}

function remote_mail($to, $subj, $body, $enc)
{
    $to = urlencode($to);
    $subj = urlencode($subj);
    $body = urlencode($body);
    $headers = urlencode("Content-type: text/plain; charset=$enc\r\nFrom: noreply@destinysphere.ru");
    $queryString = "to=$to&subj=$subj&body=$body&headers=$headers";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://mail.destinysphere.ru/mail.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
    $output = curl_exec($ch);
    curl_close($ch);
}

?>