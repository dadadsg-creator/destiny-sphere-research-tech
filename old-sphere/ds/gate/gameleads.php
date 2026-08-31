<?php
ini_set('memory_limit', '512M');
set_time_limit(0);
require_once('../Main.php');
//SU_DebugTools::LogAll('request='.var_export($_REQUEST, true),'sponsorpay.log');
$pkey = 'dsgstat';

$reqdata = simplexml_load_string($_REQUEST['xml']);
$rcvpkey = $_REQUEST['pkey'];
$rcvskey = $_REQUEST['skey'];

if ($rcvpkey!=$pkey )
{
  die('<?xml version="1.0"?>
<error>bad pkey</error>');
}

if (md5($pkey.$reqdata->date_from.$reqdata->date_to) != $rcvskey )
{
  die('<?xml version="1.0"?>
<error>bad signiture</error>');
}

MP_GameLeads::setCommon(DS_Common::CreateForCurentDB(0));
$userstat = MP_GameLeads::getAgregatedStat($reqdata->date_from, $reqdata->date_to);

$xml = '<?xml version="1.0"?>
<items>';
foreach($userstat as $u)
{
  $xml .= '<item>';
    $xml .="<id>{$u['id']}</id>\n";
    $xml .="<dl>{$u['dl']}</dl>\n";
    $xml .="<first_login>{$u['first_login']}</first_login>\n";
    $xml .="<eff_reg>{$u['eff_reg']}</eff_reg>\n";
    $xml .="<level>{$u['level']}</level>\n";
    $xml .="<auth_count>{$u['auth_count']}</auth_count>\n";
    $xml .="<time_game>{$u['time_game']}</time_game>\n";
    $xml .="<last_login>{$u['last_login']}</last_login>\n";
    $xml .="<auth_price>{$u['auth_price']}</auth_price>\n";
    $xml .="<currency>{$u['currency']}</currency>\n";
    $xml .="<date>{$u['date']}</date>\n";
  $xml .= '</item>';
}
$xml .='</items>';

echo $xml;
?>