<?php $CVS_Version = '$Revision: 1.17 $ ; $Date: 2008/05/23 11:01:06 $';

/* Гейт для общения с биллингом */

//Подключаем ядро от игрушки
$_SESSION["dbId"] = 0;
$_SESSION['lang'] = 3;
require_once($_SERVER['DOCUMENT_ROOT']."/ds/Main.php");

// SU_DebugTools::LogAll($_POST, 'BillingGate.log', 0);

empty($_POST['act']) ? $act = '' : $act = $_POST['act'];
$key_param_name = md5('gate_key');
empty($_POST[$key_param_name]) ? $key = '' : $key = $_POST[$key_param_name];
//$actionLogger = new BG_GateActionLogger($act, $_POST, $_SERVER['REMOTE_ADDR'], $key);

//Защита по IP
$gate_allowed_ip = array('127.0.0.1', '94.242.35.153', '94.242.35.154', '94.242.35.155', '94.242.60.30');
if(!in_array($_SERVER['REMOTE_ADDR'], $gate_allowed_ip)){
    $errorText = 'Wrong IP (' . $_SERVER['REMOTE_ADDR'] . ' instead of allowed )';
    SU_DebugTools::LogAll('Wrong IP (' . $_SERVER['REMOTE_ADDR'] . ' instead of allowed )', 'BillingGate.log', 0);
    $actionLogger->SetBadEnd($errorText);
    exit();
}


if (!array_key_exists('act', $_POST))
{
    $errorText = 'No action given';
    //$actionLogger->SetBadEnd($errorText);
    exit($errorText);
}


try
{
    switch ($_POST['act'])
    {
        // проверка есть ли игрок с таким логином
        case 'user_auth':

            $ud = new BG_UserData($_POST);
            $ud->getUserDataByLoginAndPassword();

            break;
        // проверка есть ли игрок с таким id и хешем
        case 'user_auth_by_hash':
            $ud = new BG_UserData($_POST);
            $ud->getUserDataByIDAndHash();

            break;
        // доставка премиума в игру
        case 'premium':
            $p = new BG_Premium2($_POST);
            $p->proceed();

            break;
        case 'balance':
            $b = new BG_Balance($_POST);
            $b->proceed();

            break;
        case 'login_check_uniq':
            $b = new BG_CheckLoginUniq($_POST);
            $b->proceed();

            break;
        case 'change_login':
            $b = new BG_ChangeLogin($_POST);
            $b->proceed();

            break;
        case 'check_new_login_validity':
            $b = new BG_CheckNewLoginValidity($_POST);
            $b->proceed();

            break;
        case 'check_new_combname':
            $b = new BG_CheckNewCombName($_POST);
            $b->proceed();

            break;
        case 'change_combname':
            $b = new BG_ChangeCombname($_POST);
            $b->proceed();

            break;
        case 'check_moving1':
            $b = new BG_CheckCombMovingInsideRing($_POST);
            $b->proceed();

            break;
        case 'move_comb1':
            $b = new BG_CombMovingInsideRing($_POST);
            $b->proceed();

            break;
        case 'check_comb_reserv_ability':
            $b = new BG_CombReserv($_POST);
            $b->check();

            break;
        case 'reserv_comb':
            $b = new BG_CombReserv($_POST);
            $b->reserv();

            break;
        case 'check_change_world_ability':
            $b = new BG_ChangeWorld($_POST);
            $b->check();

            break;
        case 'move_user_to_another_world':
            $b = new BG_ChangeWorld($_POST);
            $b->move();

            break;

        case 'check_add_world_ability':
            $b = new BG_AddWorldAbility($_POST);
            $b->check();

            break;
        case 'add_world_ability':
            $b = new BG_AddWorldAbility($_POST);
            $b->add();

            break;
        case 'check_add_all_world_ability':
            $b = new BG_AddAllWorldAbility($_POST);
            $b->check();

            break;
        case 'add_all_world_ability':
            $b = new BG_AddAllWorldAbility($_POST);
            $b->add();

            break;
        // получение игроков, у которых действует премиум
        case 'get_premium_players':
            $b = new BG_PremiumPlayers($_POST);
            $b->getPlayers();

            break;

        // проверить, присутствует ли экземпляр игрока в данном мире
        // пока это нужно для строительства дома отдыха
        case 'check_user_presence_in_given_world':
            $u = new BG_AllowResthomeCreation($_POST);
            $u->checkPresence();

            break;
        // проверить, есть ли уже у игрока возможность строить дом отдыха
        // эта услуга предоставления уникальной услуги, второй раз нельзя
        case 'check_user_already_given_resthome_ability':
            $r = new BG_AllowResthomeCreation($_POST);
            $r->checkAlreadyGiven();
            break;

        // разрешить строительство дома отдыха
        case 'allow_rest_home_creation':
            $r = new BG_AllowResthomeCreation($_POST);
            $r->proceed();
            break;

        // проверить возможность переезда в другой мир
        case 'is_user_able_to_move_to':
            $m = new BG_MoveToAnotherWorld($_POST);
            $m->check();
            break;

        // переместить в другой мир
        case 'move_to_another_world':
            $m = new BG_MoveToAnotherWorld($_POST);
            $m->move();
            break;

        // получить статистику по посещениям и торговле
        case 'user_trade_and_visit_statistics':
            $info = new BG_UserStatistics($_POST);
            $info->getTradeAndVisitStatistics();
            break;

        // проверка можно ли перевести деньги игроку
        case 'check_money_enter':
            $enter = new BG_MoneyEnter($_POST);
            $enter->check();
            break;

        // перевод денег игроку
        case 'money_enter':
            $enter = new BG_MoneyEnter($_POST);
            $enter->exec();
            break;

        // Перевод денег за оплату в биллинге
        case 'drawout_credits':
            $pay = new BG_CreditsBillingPayment($_POST);
            $pay->Exec();
            break;

        // Проверка получения триального премиума
        case 'trial_ability':
            $p = new BG_CheckPremiumPresent($_POST);
            $p->proceed();
            break;

        // получение уида по логину (снятие порчи по фото...)
        case 'get_uid':
            $g = new BG_GetUIDByLogin($_POST);
            $g->proceed();
            break;

        // получение логина по уиду
        case 'get_login':
            $g = new BG_GetLoginByUID($_POST);
            $g->proceed();
            break;

        // Проверка пририсовка ресурсов за бабло
        case 'check_buy_resource':
            $b = new BG_BuyResources($_POST);
            $b->check();
            break;

        // пририсовка ресурсов за бабло
        case 'buy_resource':
            $b = new BG_BuyResources($_POST);
            $b->proceed();
            break;

        // доставка премиума в анализаторе
        case 'analize_premium':
            $p = new BG_PremiumAnalizer($_POST);
            $p->proceed();
            break;

        // доставка премиума и доступа в анализатор
        case 'premium_and_anal':
            $p = new BG_PremiumAndAnaliz($_POST);
            $p->proceed();
            break;
        // получение списка рефералов
        case 'get_referals':
            $g = new BG_UserReferals($_POST);
            $g->proceed();
            break;
        // получение игрровых данных группы игроков
        case 'get_user_data':

            $g = new BG_UserDataPack($_POST);
            $g->proceed();
            break;
        // реферальные выплаты игрокам
        case 'pay_to_referals':
            $g = new BG_ReferalsPay($_POST);
            $g->proceed();
            break;
        case 'check_back_from_ass':
            $g = new BG_BackFromAss($_POST);
            $g->check();
            break;
        case 'back_from_ass':
            $g = new BG_BackFromAss($_POST);
            $g->proceed();
            break;
        case 'check_artefact_ability':
      //SU_DebugTools::LogAll("check_artefact_ability. POST=".var_export($_POST, true), 'daniil.log', 0);
            $g = new BG_Artefact($_POST);
            $g->check();
            break;
        case 'add_artefact':
      //SU_DebugTools::LogAll("add_artefact. POST=".var_export($_POST, true), 'daniil.log', 0);

      $g = new BG_Artefact($_POST);
            $g->proceed();
            break;
        case 'get_user_cnt':
      //SU_DebugTools::LogAll("get_user_cnt. POST=".var_export($_POST, true), 'daniil.log', 0);

      $g = new BG_UserStat($_POST);
            $g->proceed();
            break;
    case 'get_premium_stat':
      $g = new BG_PremiumStat($_POST);
            $g->proceed();

            break;
    case 'get_partner_users':
      $g = new BG_PartnerStat($_POST);
            $g->proceed();
            break;
    case 'partner_reg_stat':
      $g = new BG_PartnerRegStat($_POST);
            $g->proceed();
      break;
    case 'total_partner_reg_stat':
      $g = new BG_PartnerTotalRegStat($_POST);
            $g->proceed();
      break;
        default:
            exit('unknown action');
    }
} catch (Exception $e) {
    //$actionLogger->SetBadEnd('Error: ' . $e->getMessage());
    SU_DebugTools::LogAll($e->getMessage(), 'BillingGate.log', 0);
    exit($e->getMessage());
}

$out = BG_Common::$out;
//$actionLogger->SetGoodEnd($out);

echo $out;

SU_DebugTools::LogAll($out, 'BillingGate.log', 0);
?>