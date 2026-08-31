<?php
require_once('../Main.php');
//error_reporting(E_ALL);
//ini_set('display_errors',1);

$act = $_REQUEST['act'];

$_REQUEST['name'] = stripslashes($_REQUEST['name']);
$_REQUEST['name'] = iconv("utf-8", "windows-1251", $_REQUEST['name']); 
$_REQUEST['name']  = str_replace(array('\"',"\'"),array('"', "'"), trim($_REQUEST['name']));
$_REQUEST['name']  = str_replace("'","\'", $_REQUEST['name']);

$_REQUEST['contact_person'] = iconv("utf-8", "windows-1251", $_REQUEST['contact_person']); 

$secretWord = 'partnerka_zaebala';
$correctsign = md5($secretWord.$_REQUEST['email']);

if ($correctsign != $_REQUEST['sign'])
{
  SU_DebugTools::LogAll('achtung corrsign='.$correctsign.",sign=".$_REQUEST['sign'], 'plog.log');  
  die('ACHTUNG');
}

$site_list2 = filter_arg(@$_REQUEST['site_list2']);
if ($site_list2)
{
  $site_list2 = unserialize($site_list2);
  SU_DebugTools::LogAll('site_list2='.site_list2, 'plog.log');
  $_REQUEST['site_list'] = $site_list2[0]['url'];
}
else 
  $_REQUEST['site_list'] = '';
  
SU_DebugTools::LogAll('_REQUEST='.var_export($_REQUEST, true),'plog.log');

if ($act=='del')
  DS_Partner::DeletePartner(intval($_REQUEST['id_partner']));
else if ($act=='upd')
  DS_Partner::UpdatePartner($_REQUEST);
else if ($act=='add')
{
  if (!DS_Partner::getPartner(intval($_REQUEST['id_partner'])))
    DS_Partner::AddPartner($_REQUEST);
}

function filter_arg($msg)
{
  $msg     = str_replace("\'","'",$msg);
  $msg     = str_replace('\"','"',$msg);
  return $msg;
}     
?>