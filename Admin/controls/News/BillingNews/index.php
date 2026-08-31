<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
?>
<script language="Javascript">
function checkAdd()
{
  newMenu = document.getElementById('newitem').value;

  if (newMenu.replace(/^\s+|\s+$/g, '') == '')
  {
    alert('Вы не ввели название пункта меню');
    return false;
  }
  return confirm("Вы уверены, что хотите добавить этот пункт меню?");
}

function checkRename()
{
  newMenu = document.getElementById('newitem').value;

  if (newMenu.replace(/^\s+|\s+$/g, '') == '')
  {
    alert('Вы не ввели название пункта меню');
    return false;
  }
  return confirm("Вы уверены, что хотите обновить этот пункт меню?");
}

function checkDel()
{
  Menu = document.getElementById('menu').value;
  if (Menu==1)
  {
     alert('Корневой раздел нельзя удалять');
     return false;
  }
  return confirm("Вы уверены, что хотите удалить этот пункт меню со всеми его подразделами?");
}

function getSelMenu(frm)
{
   document.getElementById('newitem').value=
   document.getElementById('menu').options[frm.selectedIndex].text.replace(/(^\-+)/g, '');
   vsbl = vislist[document.getElementById('menu').value];
   if (vsbl)
     document.getElementById('visiblestate').checked = true;
   else
     document.getElementById('visiblestate').checked = false;
}

</script>
<?

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

$act  = @$_REQUEST['act'];
$r    = @$_REQUEST['menu'];
$delmenu = @$_REQUEST['delmenu'];
$gotomenu = @$_REQUEST['gotomenu'];
$addmenu = @$_REQUEST['addmenu'];
$renmenu = @$_REQUEST['renmenu'];
$act     = @$_REQUEST['act'];

if (!$r) 
  $r = 0;
$conn_id = getMysqlConnection();

if ($addmenu)
{
  $newitem = @$_REQUEST['newitem'];
  DS_PayDescr::addMenu($newitem, $r);
}
else if ($renmenu)
{
  $newitem = @$_REQUEST['newitem'];
  $visiblestate = @$_REQUEST['visiblestate'];
  if (!$visiblestate)
     $visiblestate = 0;
  DS_PayDescr::updateMenu($newitem, $r, $visiblestate);
}
else if ($delmenu)
{
  DS_PayDescr::delMenu($r);
}
else if ($act)
{
  if ($act=='sbmedit')
  {
    $r = $menu_id = @$_REQUEST['menu_id'];
    $text_id = @$_REQUEST['text_id'];
    $title   = trim(@$_REQUEST['vtitle']);
    $text    = trim(@$_REQUEST['vtext']);

    $title = str_replace('\"','"', $title);
    $title = str_replace("\'","'", $title);

    $text  = str_replace('\"','"', $text);
    $text  = str_replace("\'","'", $text);

    if ($text_id)
      DS_PayDescr::updateText($text_id, $title, $text);
    else
      DS_PayDescr::insertText($menu_id, $title, $text);
  }
}
showIndex();


function showIndex()
{
  global $r;

  $menu = DS_PayDescr::getMenu(0, 1, 1);
  $vislist = DS_PayDescr::getMenuVisibleList();

  echo "<script language='Javascript'>var vislist=$vislist</script>";
  echo "<table width=100% cellspacing=0 cellpadding=0><tr><td style='padding-left:0px;width:205px'>";
  echo "<form name='selfrm' method=POST><select id='menu' name='menu' onChange=\"return getSelMenu(this)\">";
  //echo "<option value='0'>------------</option>";
  echo showDropList($menu, $r, '')."</select>
  <br> 
  <input type='submit' name='gotomenu' value='Перейти'>
  <br>
  <input type='submit' name='delmenu' value='Удалить' onClick='return checkDel()'>
  <br>
  <span style='color:white'>
  Добавить к выбранному подпункту<br> новый пункт/переименовать:<br>
  <input type='text' value='' id='newitem' name='newitem'><br>
  <input type='submit' id='addmenu' name='addmenu' value='Добавить' onClick='return checkAdd()'><br>
  <input type='submit' id='renmenu' name='renmenu' value='Обновить' onClick='return checkRename()'>
  <br>Видимость:
  <input type=checkbox id='visiblestate' name='visiblestate' value=1>
  </span>
  </form>
  </td>";
  echo "<td>".ShowRichForm($r)."</td>";
  echo "</tr></table>";
}

function showRichForm($r)
{
   $data = DS_PayDescr::getText($r);
   $vitr_text = $data['vitr_text'];
   $vitr_text = str_replace('\"','"',$vitr_text);
   $vitr_text = str_replace("\'","'",$vitr_text);
   //$text = addslashes($vitr_text);
   $text  = str_replace("'","&prime;",$vitr_text);
   //$text = $vitr_text;
   $str = "
  <form name='txt' method='POST'>
  <input type='hidden' name='act' value='sbmedit'>
  <input type='hidden' name='menu_id' value='{$r}'>
  <input type='hidden' name='text_id' value='{$data['id']}'>

  <span style='color:white'>
  Заголовок(необязателен):<br><input type=text name='vtitle' value='{$data['title']}' size=80></span><br>
  
   <span style='color:white'>Текст:</span><br>
<div><input type='hidden' id='vtext' name='vtext' value='{$text}'><input type='hidden' id='vtext___Config' value=''/><iframe id='vtext___Frame' src='/fck_editor/editor/fckeditor.html?InstanceName=vtext&amp;Toolbar=Default' width='100%' height='400' frameborder='0' scrolling='no'></iframe></div>
<br>
<input type=submit value='Применить' class='submit'>
</form>";
return $str;
}

function showDropList($menu, $sel, $s)
{
   $str = '';
   foreach ($menu as $el)
   {
     $selectedstr = $el['val'][0]==$sel?'selected':'';

     $str .= "<option value='{$el['val'][0]}' $selectedstr>$s{$el['val'][1]}</option>";
     if ($el['children'])
     {
       $olds = $s;
       $s .='-';
       $str .= showDropList($el['children'], $sel, $s);
       $s = $olds;
     }
   }
   return $str;
}

function showTree($menu)
{
   echo "<ul style='color:white'>";
   foreach ($menu as $el)
   {
     echo "<li><a href='?r={$el['val'][0]}'>".$el['val'][1].'</a>';
     if ($el['children'])
       showTree($el['children']);
   }
   echo "</ul>\n";
}


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>