<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

$from = $_REQUEST['dateFrom'];
$to   = $_REQUEST['dateTo'];
$id_world = $_REQUEST['id_world'];
$dir     = $_REQUEST['dir'];

  list($userarr, $totalgood, $totalusers, $totalansw) = DS_Botlist::GetStat($from, $to, $id_world, $dir);
  $str = '"N";"ID";"Ник";"Всего ответов";"%НЕправильных"'."\n";

  for($i=0; $i<count($userarr);$i++)
  {
    $n = $i+1;
    $user = $userarr[$i];
    $proc = sprintf("%0.1f",$user['proc']*100);
    $proc = str_replace('.',',', $proc);
    $str .= "$n;{$user['id']};{$user['username']};{$user['total']};".$proc."\n";
  }
  header('Content-type: text/x-csv');
  header("Content-Disposition: attachment; filename=userstat.csv");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Pragma: public");

  echo $str;
?>