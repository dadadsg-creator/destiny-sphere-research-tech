<?php


$charset = array ('RU' => 'windows-1251', 'DE' => 'iso-8859-1', 'EN' => 'windows-1251');

//require_once "./conf.php";
require_once "./sys/define.php";
require_once "./sys/lib/PageCache.class.php";
require_once "./sys/tpl.php";
require_once "./sys/lib/PageCache.class.php";

header("Content-Type: text/html; charset=".$charset[strtoupper(constant('DEFAULT_LANG'))]." ");
header("Last-Modified: ".date("D, d M Y H:i:s")." GMT");

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

$conn_id = getMysqlConnection();

$vars = array();
$r  = intval($_REQUEST['r']);
if (!$r)
 $r = 1;
$parents = array();

if ($r >=1)
{
  $parents = DS_PayDescr::getAllParents($r);
  $parents = array_reverse($parents);
}
//array_unshift($parents, array('id'=>'0', 'title'=>'Все'));
$cnt = count($parents);
$str = "";

$vitr = DS_PayDescr::getText($r);

for($i=0; $i < $cnt;$i++)
{
  $el = $parents[$i];
  if ($i<$cnt-1)
    $delim = '->';
  else
    $delim = '';
  $str .= "<a href='?r={$el['id']}'>{$el['title']}</a>{$delim}";
}

//$pageCache = new PageCache('vitrina'.$default_page);
//$strOut = $pageCache->get();
$strOut = '';
if (!$strOut)
{
  $vars['menu'] = DS_PayDescr::getMenu(1, 1);
  $vars['titleref'] = $str;
  $text = DS_PayDescr::getText($r);

   
  $vars['title'] = $vars['vitr_text'] = '';
  if ($text)
  {
    $vars['title'] = $str;
    $vars['vitr_text'] = $text['vitr_text'];
  }
  $expand = 0;
  if ($cnt>1)
    $expand = $parents[1]['id'];
  //echo "expand=$expand<br>";
  $vars['expand'] = $expand;
  $strOut = TPL::render('Publication/vitrina.php', $vars);
}
else
{
  echo "<!--cache!-->";
}
echo $strOut;
?>