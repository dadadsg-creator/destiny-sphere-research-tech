<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

showUsers();

function showUsers()
{
  echo "<script src='/Admin/js/pager.js'></script>";
  echo "<link href='/Admin/styles/pager.css' rel='stylesheet' type='text/css' />";

  $from = pg_escape_string(@$_REQUEST['from']);
  $to   = pg_escape_string(@$_REQUEST['to']);
  $pg   = @$_REQUEST['pg'];
  $PaginatorURL = "?from=$from&to=$to&pg=";
  if (!$pg)
    $pg = 1;

  if (!$from)
  {
    $to = date("Y-m-d");
    list($year, $mon, $date) = explode('-',$to);
	  $from = "$year-$mon-1";
  }
echo "<span class=select_txt >Пользователи, пришедшие с Rambler:</span><br><br>";
echo "
<b style='color:white'>От:</b> <input type='text' size='12' name='from' id='from' value='$from'><input type='button' name='Button' value='...' onClick=\"bscal.show('from');\">&nbsp;&nbsp;
<b style='color:white'>До:</b> <input type='text' size='12' name='to' id='to' value='$to'><input type='button' name='Button' value='...' onClick=\"bscal.show('to');\"><input type=submit value='OK'>";

  $items_num = 50;
  $partn = 6;
  list($users, $totalcnt) = GetUsers($from, $to, $pg, $items_num, $partn);
  $cnt_pages = ceil($totalcnt/$items_num);

  $out .= "<table border=1 width=500px>
        <tr class=th_2>
        <td>ID</td>
        <td>Ник</td>
        <td>Дата регистрации</td>
        </tr>
        ";

  $bgColor = '8888A0';
  foreach($users as $u)
  {
     list($d1, $d2) = explode('.', $u['registrationdate']);
     $out .= "<tr>
        <td bgcolor='#$bgColor'>{$u['id_user']}</td>
        <td bgcolor='#$bgColor'>{$u['username']}</td>
        <td bgcolor='#$bgColor'>{$d1}</td>
       </tr>";
   }
   $out .= "</table>";
   $out .= "<div class='paginator' id='paginator_users'></div>
<script type='text/javascript'>
	p = new Paginator('paginator_users', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>";
    echo $out;
}

  function GetUsers($from, $to, $pg, $items, $partn)
  {
		$common = DS_Common::CreateForCurentDB(0);
		$offset = ($pg - 1)*$items;

		$sql = "
			SELECT 
				*
			FROM 
				ds_user
			WHERE
				registrationdate > '".$from."'
				AND
				registrationdate < '".$to."'
        AND
        from_partner = $partn
			ORDER BY id_user
        LIMIT $items OFFSET $offset
			";		

    //echo $sql;
		$res = $common->getResultArray("", $sql, "", "", "");
    echo $common->errormsg;
		assert(empty($common->errormsg));
		
    $sql = "SELECT count(id_user) as cnt FROM ds_user
			WHERE
				registrationdate > '".$from."'
				AND
				registrationdate < '".$to."'
        AND
        from_partner = $partn
    ";
    $cnt = $common->getResultArray("", $sql);

		return array($res, $cnt[0]['cnt']);
  }

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>