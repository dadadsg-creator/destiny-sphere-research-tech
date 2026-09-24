<?php $CVS_Version = '$Revision: 1.15 $ ; $Date: 2009/11/12 08:22:57 $';

require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

class GE_OutStartWorldsList
{
    /**
     *  
     * @param GE_DataStartWorldsList $startGameData
     * @param string $msg
     * @return string
     */
    static public function FormOut(GE_DataStartWorldsList $startGameData, $msg)
    {

        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = TEMPLATES_PATH;
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;

        // задаем прерменные
        // списки миров
        $tpl->assign('WORLDS_TO_ENTER', $startGameData->GetWorldsToEnter());
        $tpl->assign('WORLDS_TO_REG', $startGameData->GetWorldsToReg());
        $tpl->assign('ALL_WORLDS', $startGameData->GetAllWorlds());
        $tpl->assign('WORLDS_DOWN', $startGameData->GetWorldsDown());
        $tpl->assign('HAS_WORLD_TO_ENTER', $startGameData->GetHasWorldToEnter());

        // список рас
        $tpl->assign('RACES', $startGameData->GetAllRaces());

        // информация об игроке
        $tpl->assign('UID', $startGameData->GetUserId());
        $tpl->assign('LOGIN', $startGameData->GetUserName());
        $tpl->assign('EMAIL', $startGameData->GetUserEmail());
        $tpl->assign('PREMIUM_DAYS', $startGameData->GetPremiumDays());
        // подарочный премиум
        if (VERSION_NAME!='LIMITED')
            $tpl->assign('PREMIUM_PRESENT', $startGameData->GetPremiumPresent());

        $tpl->assign('MSG', $msg);

        // для защитного кода
        $tpl->assign('S_NAME', session_name());
        $tpl->assign('S_ID', session_id());

        // информация о бане пользователя
        $tpl->assign('BAN', $startGameData->GetUserBan());
        $tpl->assign('BAN_REASON', $startGameData->GetUserBanReason());

        // информация об активации мыла
        $tpl->assign('MAIL_ACTIVATION', $startGameData->GetMailactivation());
        $tpl->assign('MAIL_ACTIVATION_DAYS_LEFT', $startGameData->GetMailActivationDaysLeft());
        $tpl->assign('MAIL_ACTIVATION_REQUIRE', $startGameData->GetMailactivationRequire());

        // информация о неудачной регистрации
        $tpl->assign('REG_WORLD', $startGameData->GetRegWorld());
        $tpl->assign('REG_RACE', $startGameData->GetRegRace());
        $tpl->assign('REG_COMBNAME', $startGameData->GetRegCombname());


        $tpl->assign('HAVE_INSTANCE_IN_WORLD_UNION', $startGameData->haveInstanceInWorldUnion());
        $tpl->assign('HAVE_INSTANCE_IN_WORLD_WITH_FREE_TRANSFER', $startGameData->HaveInstanceInWorldWithFreeTransfer());

        $tpl->assign('CAN_CHANGE_MAIL', $startGameData->CanChangeMail());


        $tpl->assign('CLIENT_KEY', $startGameData->GetClientKey());

        $tpl->assign('BLOCKS', $GLOBALS['INDEX_START_BLOCKS']);

        $tpl->assign('IMG_RAND', rand(1000, 9999));

        $tpl->assign('CABINET_LOGIN', $startGameData->GetAllowUserCabinet());
        $tpl->assign('NEW_USER_HELP', $startGameData->GetShowUserHelp());


        $tpl->assign('POLL_QUESTION', $startGameData->GetPollQuestion());
        $tpl->assign('POLL_ANSWERS', $startGameData->GetPollAnswers());
        $tpl->assign('POLL_RESULT', $startGameData->GetPollResult());

        $tpl->assign('BANNER', $startGameData->GetBannerData());
        $reflink = 'http://'.$_SERVER['HTTP_HOST'].'/ds/reg_wizard.php?ur='.$startGameData->GetUserId();
        $tpl->assign('REFLINK',$reflink);   

        // подключение файла с локализацией
        include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".$startGameData->GetLanguage()."/index_start_new.lang";
        assert(is_array($lang));        
        $tpl->assign('lang', $lang);
        $tpl->assign('PREMIUM_LEFT_TEXT', self::_formatPremiumLeft($startGameData->GetPremiumDays(), $lang));
        unset($lang);

        /*
        // компилируем темплейт
        if (@$_REQUEST['mode']=='2')
        {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        $out = $tpl->fetch('index_start_new2.tpl');
        }
        else
        */
        $out = $tpl->fetch('index_start_new.tpl');
        return $out;
    }

    private static function _formatPremiumLeft($days, $lang)
    {
        if (!is_array($days))
        {
            return '';
        }

        $day = (int)$days['day'];
        $hour = (int)$days['hour'];

        if ($day <= 0 && $hour <= 0)
        {
            return $lang['LESS_THAN_HOUR'];
        }

        $out = '';
        if ($day > 0)
        {
            $out .= $day.' '.self::_declineNumber($day, $lang['DAY_FORMS']);
        }
        if ($hour > 0)
        {
            if ($out !== '')
            {
                $out .= ' ';
            }
            $out .= $hour.' '.self::_declineNumber($hour, $lang['HOUR_FORMS']);
        }

        return $out;
    }

    private static function _declineNumber($n, $forms)
    {
        $n = abs((int)$n);

        if (count($forms) < 3)
        {
            return $forms[$n == 1 ? 0 : 1];
        }

        $mod100 = $n % 100;
        $mod10 = $n % 10;

        if ($mod100 >= 11 && $mod100 <= 14)
        {
            return $forms[2];
        }
        if ($mod10 == 1)
        {
            return $forms[0];
        }
        if ($mod10 >= 2 && $mod10 <= 4)
        {
            return $forms[1];
        }

        return $forms[2];
    }
}

?>