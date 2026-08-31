<?
require_once ('Main.php');
require_once 'include/Smarty/Smarty.class.php';
include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/index_account_activated.lang");

        /* ТУТ Собственно место вызова темплейта */
        $tpl = new Smarty;
        $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;

        /* блок переменных */
        $tpl->assign("lang", $lang);

        /* Показать сформированный темплейт */

        $partnercounter = '';

        if (key_exists('idp', $_GET) && key_exists('order_id', $_GET))
        {
            if ($_GET['idp']==59)
            {
                $partnercounter = '<script src="http://www.ad.admitad.com/register/5d68bb1fe5"></script>';
            }
          else if ($_GET['idp']==109)
            {
              //заглушка
            }
          else if ($_GET['idp']==112)
            {
              $partnercounter = '<iframe style="border:none;width:0px;height:0px;"
src="http://adanalytics.ru/track/confirm.php?id=KiOdHjAPGBrA"></iframe>';
            }
          else if ($_GET['idp']==113)
            {
              //$partnercounter = '<img src="http://n.actionpay.ru/ok/112.png" width="1" height="1" />';
            }
            else if ($_GET['idp']==139) {
                $partnercounter = '<img src="http://tracking.djumbo.ru/SLVK" width="1" height="1" />';
            }
            else if ($_GET['idp']==154)
            {
                $partnercounter = '<img src="http://tracking.actionads.ru/SL1sG" width="1" height="1" />';
            }
            else
            {
              $codearr = array(4=>array(38=>'119', 49 => '138'), 6=>array(38=>'120', 49=>'146'));
              $code  = $codearr[constant('BILLING_PARTNER_ID')][$_GET['idp']];
              $partnercounter = '<img src="http://a75.troywell.ru/pixel.php?id='.$_GET['order_id'].'&key='.md5($code.$_GET['order_id']).'&target=t1">';
            }
        }

        $tpl->assign("partnercounter", $partnercounter);
        $tpl->display('index_account_activated.tpl');
?>