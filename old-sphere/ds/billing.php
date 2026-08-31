<?php
require_once "Main.php";

SU_GameSessionWork::SessionContinue();

$mode = '';
if (array_key_exists('mode',$_REQUEST))
{
  $mode = '&mode='.$_REQUEST['mode'];
}

if (array_key_exists('method',$_REQUEST))
{
  $mode .= '&method='.$_REQUEST['method'];
}
if (array_key_exists('quantity',$_REQUEST))
{
  $mode .= '&quantity='.$_REQUEST['quantity'];
}

$str = "Location: https://billing.destinysphere.ru/LoginOutside.php?partner_id=".constant('BILLING_PARTNER_ID')."&user_id=".$_SESSION['uid']."&hash=".md5(md5($_SESSION['s_pass']."REDACTED_API_SECRET")).$mode;
Header ( $str );
exit;

?>