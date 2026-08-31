<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";


include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";


$act  = @$_REQUEST['act'];
$lang = @$_REQUEST['lang'];
$pg   = @$_REQUEST['pg'];
if (!$lang)
  $lang = 3;
if (!$act)
  $act = 'show';
if (!$pg)
  $pg = 1;

global $filter;
if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}
$_SESSION["dbId"] = 0;
//var_export($_REQUEST);
switch ($act)
{
  case 'show':    showForm(); break;
  case 'sbmedit': submitForm(); break;
  case 'stat':
  case 'sbmstatedit':
    showStat();
    break;
	default: showForm(); break;
}

function showStat()
{
  global $filter;
  echo "<script src='/Admin/js/pager.js'></script>";
  echo "<link href='/Admin/styles/pager.css' rel='stylesheet' type='text/css' />";

  list($id_world, $id_base) = showSelectWorldMenu();

  $_SESSION["dbId"] = $id_base;
  $from = @$_REQUEST['from'];
  $to   = @$_REQUEST['to'];
  $pg   = @$_REQUEST['pg'];
  $filter = @$_REQUEST['flt'];
  $dirs = @$_REQUEST['dir'];

  if (!$dirs)
     $dirs = 'DESC';

  if (!$filter)
    $filter = 'proc';

  if (!$pg)
    $pg = 1;

  if (!$from)
  {
    $to = date("Y-m-d");
    list($year, $mon, $date) = explode('-',$to);
	  $from = "$year-$mon-1";
  }

  echo "<script language=\"Javascript\">
function getCSV()
{
  dateFrom = document.getElementById('from').value;
  dateTo = document.getElementById('to').value;
  URL = 'csv.php?action=csv&dateFrom='+dateFrom+'&dateTo='+dateTo+'&filter={$filter}'+
  '&id_world={$id_world}'+'&dir={$dirs}';
  window.open(URL);
  return false;
}
</script>
";
  list($userarr, $totalgood, $totalusers, $totalansw) = DS_Botlist::GetStat($from, $to, $id_world, $dirs);

  $items_on_page = 50;
  $cnt_pages = ceil($totalusers / $items_on_page);

  $PaginatorURL = "index.php?act=stat&from=$from&to=$to&id_world=".$id_world.'_'.$id_base."&flt=$filter&pg=";
  $userarr = array_slice($userarr, ($pg-1)*$items_on_page, $items_on_page);
  $dirs2 = $dirs=='ASC'?'DESC':'ASC';

  $pageURLs = "index.php?act=stat&from=$from&to=$to&id_world=".$id_world.'_'.$id_base."&pg=1&dir=$dirs2&flt=";
  if ($totalansw > 0)
    $bad = sprintf("%0.1f", (($totalansw - $totalgood)/$totalansw)*100);
  else
    $bad = 0;

  $bad *= 1;
  echo "<a href='?' style='color:white'>Редактировать список вопросов</a><p class=maintxt>";
  echo "<b>Статистика антибота</b><p class=maintxt>
     Смерть роботам!
	</p>
  <b style='color:white'>Средний процент НЕправильных ответов за указанный период: </b><span style='color:red'>{$bad}% ($totalusers игроков, $totalansw ответов)</span>
	<form action='' method=POST>

От: <input type='text' size='12' name='from' id='from' value='$from'><input type='button' name='Button' value='...' onClick=\"bscal.show('from');\">&nbsp;&nbsp;
До: <input type='text' size='12' name='to' id='to' value='$to'><input type='button' name='Button' value='...' onClick=\"bscal.show('to');\">
<input type=submit value='OK'>
<input type=submit value='CSV' onClick='return getCSV();'>
  <input type='hidden' name='act' value='sbmstatedit'>
	<input type='hidden' name='cnt' value='$totalansw'>
	<table>
	<tr>
	<td bgcolor='444E6C' class=th_2 align='center'>N</td>
  <td bgcolor='444E6C' class=th_2 align='center'><a href='{$pageURLs}id'>ID юзера</a></td>
	<td bgcolor='444E6C' class=th_2 align='center'><a href='{$pageURLs}username'>Ник</a></td>
	<td bgcolor='444E6C' class=th_2 align='center'><a href='{$pageURLs}total'>всего ответов</a></td>
  <td bgcolor='444E6C' class=th_2 align='center'><a href='{$pageURLs}proc'>% НЕправильных</a></td>
	</tr>
  ";
  $n = ($pg-1)*$items_on_page + 1;
  for($i=0; $i<count($userarr);$i++)
  {
    $user = $userarr[$i];
    echo "<tr style='color:white'><td>$n</td>
    <td>{$user['id']}</td>
    <td><span style='color:white'>{$user['username']}</span></td>
    <td>{$user['total']}</td>";
    echo "<td>".sprintf("%0.1f",$user['proc']*100)."</td>";
    echo "</tr>";
    $n++;
  }

  echo "</table>
<div class='paginator' id='paginator_users'></div>

<script type='text/javascript'>
	p = new Paginator('paginator_users', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>
 </form>";
}

function submitForm()
{
  $cnt = intval(@$_REQUEST['cnt']);
	for($i = 0;$i < $cnt; $i++)
  {
	  $qid      = $_REQUEST['qid'.$i];
		$acnt     = $_REQUEST['acnt'.$i];
	  $qtxt     = trim($_REQUEST['qtxt'.$i]);
		$trueansw = $_REQUEST['trueansw'.$i];

		$answarr = array();
    for($k=0;$k < 4; $k++)
	  {
		  if ($k < $acnt)
			  $aid = $_REQUEST["answid{$i}_{$k}"];
      else
				$aid = 0;

			$answtxt = $_REQUEST["answtxt{$i}_{$k}"];
			$answarr[] = array('id'=>$aid, 'answtxt'=>$answtxt);
		}
		//echo "qid=$qid, acnt=$acnt, qtxt=$qtxt, answarr=".var_export($answarr, true)."<br>";
    if ($qid)
			DS_Question::UpdateQuestion($qid, $qtxt, $trueansw,  $answarr);
		else if (!$qid && $qtxt)
			DS_Question::InsertQuestion($qtxt, $trueansw,  $answarr);
	}
	showForm();
}

function showForm()
{
  global $lang, $act, $pg;
	$questions = DS_Question::GetAllQuestions($lang, $pg);
  $questions[] = array('id'=>0,'answer_id'=>0,
		                   'answ'=>array()
		                     );
  if ($questions)
	  $cnt = count($questions);
  else
    $cnt = 0;

	echo "
  <a href=\"?act=stat\"><b>Статистика антибота</b></a><br>
	<p class=maintxt>
	  Для удаления вопроса из базы достаточно удалить текст вопроса.
		Вопросы, где все ответы пустые, также будут удаляться из базы. Радиобаттоном помечайте
    правильный вариант ответа.
	</p>
	<form action='' method=POST>
	<input type='hidden' name='act' value='sbmedit'>
	<input type='hidden' name='cnt' value='$cnt'>
	<table>
	<tr>
	<td bgcolor='444E6C' class=th_2 align='center'>ID</td>
	<td bgcolor='444E6C' class=th_2 align='center'>Вопрос</td>
	<td bgcolor='444E6C' class=th_2 align='center'>Ответы</td>
	</tr>
	";

 for ($i=0; $i<$cnt;$i++)
 {
  $q = $questions[$i];
	$acnt = count($q['answ']);
	$trueansw  = $q['answer_id'];

	 if ($i==$cnt-1)
	 {
     echo "<tr><td  bgcolor='444E6C' class=maintxt colspan=3><b>Добавить вопрос</b></td></tr>";
	 }
	 echo "
   <input type='hidden' name='qid{$i}' value='{$q['id']}'>
   <input type='hidden' name='acnt{$i}' value='{$acnt}'>

	 <tr>
	 <td bgcolor='444E6C' class=maintxt>{$q['id']}</td>
   <td bgcolor='444E6C' class=maintxt  valign=center><input type='text' name='qtxt{$i}' value='{$q['question']}' size=50 ></td>
	 <td bgcolor='444E6C' class=maintxt valign=center>";

   for($k=0;$k < 4; $k++)
	 {
		 $checkedstr = '';
		 $answtxt = '';
		 if ($k < $acnt)
		 {
	  	 $answ = $q['answ'][$k];
  		 $aid = $answ['id'];

			 echo "<input type='hidden' name='answid{$i}_{$k}' value='{$aid}'>";
			 $answtxt = $answ['answer'];
       $checkedstr = ($aid == $trueansw)?'checked':'';
		 }
  	 if (($k==0) && ($q['id']==0))
			  $checkedstr  = 'checked';
		 echo "<input type='text' name='answtxt{$i}_{$k}' value='{$answtxt}' size=10 >";
		 echo "<input type='radio' name='trueansw{$i}' value='{$k}' $checkedstr>&nbsp;&nbsp;&nbsp;&nbsp;";
	 }
	 echo "</td></tr>";
 }
	echo "</table>";
	echo "<input type='submit'  class='Submit' value='ОК'></form>";
}

	function showSelectWorldMenu ()
	{
    global $common;

		print "
		<table>
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<input type=hidden name='act' value='stat'>
		<table cellSpacing='0' cellPadding='0'>
			<tr>
				<td bgcolor='444E6C'>

		 			<div class='FormText'>";

		echo "<strong>".local( 'DS_USER_WORLD' ).":&nbsp;&nbsp;</strong></td><td><select name='id_world'>";

		$sql = sprintf ( "select * from ds_world" );
		$res = $common->getMultySql ($sql);

		assert($res);

		$id_base  = $def_base  = $res[0]['dbId'];
		$id_world = $def_world = $res[0]['id_world'];

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			if ($_REQUEST['id_world'] == $res[$i]['id_world']."_".$res[$i]['dbId']) {
        $id_world = $res[$i]['id_world'];
        $id_base  = $res[$i]['dbId'];
				$selected = 'selected';
			} else {
				$selected = '';
			}
			echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."' $selected>".$res[$i]['worldname']."</option>";
		}

		echo "</select></div></td>
			<td>&nbsp;&nbsp;<input type='submit' class='Submit' value='". local( 'DS_ACTION_SUBMIT' )."' name='next'></td>
		</tr>
		</form>
		</table><br>
		";

		return array($id_world, $id_base);
	}
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>