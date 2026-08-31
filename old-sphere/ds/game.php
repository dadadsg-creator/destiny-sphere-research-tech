<?

require_once ('a_bounds.php');

if (!isset($_SESSION["game"]) )
{
	die("Обнаружена ошибка. Проверьте, что ваш броузер поддерживает IFRAME. Вы также можете обратиться к службе поддержки игры по адресу contact@ru.destinysphere.com. Спасибо.<br> Мы рекомендуем использовать броузер Microsoft Internet Explorer 6.0.");
}else{
	unset($_SESSION["game"]);
}

if(isset($_SESSION['celltype'])) 
{
	$common->setDbId(0);
	
	$sql = "SELECT 
				celltypename
			FROM 
				ds_celltype
			WHERE 
				id_celltype = ".$_SESSION['celltype'];
	$res = $common->getResultArray ( "celltypename_id_celltype_".$_SESSION['celltype'], $sql, "", "", "GLOBAL_CACHE" );
	if(!$res) 
	{
		print "SQL/database - error1 ";
		exit;
	}

	$location = $res[0]['celltypename'];
}

$comb = @$_SESSION['comb'];

	
// этот блок нужет для того что-бы дописать в строку вызова swf файла
// правильные данные о текущей локации ( для построения локал-даты )

if(isset($_SESSION['s_xmldata'])) 
{

	$common->setDbId($_SESSION['dbId']);
	$xml = $_SESSION['s_xmldata'];
	$xml = decode($xml);

	if ( ereg ( "<navigate", $xml ) ) {

		$x = stripcslashes ( $xml );
		$x = str_replace ( "&", "", $x );
		$x = '<?xml version="1.0" encoding="ISO-8859-1"'."?".">".$x;
		$t = domxml_open_mem ( $x );

		if ( !$t ) {
			print "XML error1 ";
			exit;
		}

		$p = $t->document_element ();

		$c = $p->get_attribute ( "dest" );

		if ( $c == 'HOME' || $c == 'CENTRAL' || $c == 'NEUTRAL' ) {

			$location = $c;
			$comb = $_SESSION['comb'];

		} else {

			$dest = explode ( ".", $c );

			$dest_ring = $dest[0];
			$dest_combcomplex = $dest[1];
			$dest_comb = $dest[2];

			$sql = sprintf ( "select co.id_comb,
					co.id_combcomplex,
					co.combnumber,
					ri.id_ring
					from ds_comb co,
					ds_combcomplex cc,
					ds_ring ri
					where co.id_combcomplex = cc.id_combcomplex
					and cc.id_ring = ri.id_ring
					and ri.id_world = %d
					and co.combnumber = %d
					and cc.complexnumber = %d
					and ri.ringnumber = %d",
					$_SESSION['world'],
					$dest_comb,
					$dest_combcomplex,
					$dest_ring );

			$res = $common->getResultArray ( "coord", $sql, "", "", "" );
			if ( !$res ) {
				print "SQL/database - error2 ";
				exit;
			}

			$comb = $res[0]['id_comb'];
		}
	}
}

$width = "840";
$height = "500";
$mode = "0";

if(empty($_SESSION["uid"]))
{
	die('UserID Error!');
}

if( isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true))
{
	require ('flash_code.php');
}else{
	if(!isset($_SESSION['BannerShows']))
	{
		$_SESSION['BannerShows'] = 1;
		require ('a_rem.php');
	}else{
		if($_SESSION['BannerShows'] < 50)
		{
			$_SESSION['BannerShows']++;
			require ('flash_code.php');
		}else{
			$_SESSION['BannerShows'] = 0;
			require ('a_rem.php');
		}
	}
}


//Это стары счетчик показа баннеров. Пока убран.
/*if (!empty ( $_SESSION["uid"] )) 
{ 

	$user = $_SESSION["uid"];

	$sql = "select 
				remainingviews, 
				nextstep, 
				lastclick 
			from 
				ds_useradsstatistics 
			where id_user = " . $user;

	$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
	if($res === false) 
	{
		print "SQL/database - error 3";
		exit;
	}

	$current = time();

	if ( sizeof ( $res ) == 0 ) 
	{
		$common->executeQuery ( "begin" );

		$sql = sprintf ("insert into
			ds_useradsstatistics (id_user, views, clicks, remainingviews, nextstep, lastclick, lastview)
			values (" . $user . ", 0, 0, " . $first_views . ", " . $first_step . ", now(), now())");

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			print "SQL/database - error 4";
			exit;
		}

		$remaining = $max_views;
		$next = $first_step;
		$lastclicktimestamp = time ();

		$common->executeQuery ( "commit" );

	} else {
		$remaining = $res[0]['remainingviews'];
		$next = $res[0]['nextstep'];
		$lastclick = $res[0]['lastclick'];
		$clickdate = explode('-', $lastclick);
		$temp = explode(' ', @$clickdate[2]);
		$clickday = $temp[0];
		$clicktime = explode(':', @$temp[1]);
		$clickminute = explode('.', @$clicktime[2]);
		$lastclicktimestamp = mktime (@$clicktime[0],@$clicktime[1], @$clickminute[0], @$clickdate[1], $clickday, @$clickdate[0]);
	}

	/// PREVENT SPONSOR BANNER
	$lastclicktimestamp = $current;

	if ( ($lastclicktimestamp + $show_time) < $current )
	{
		if (isset($_SESSION["interstitial"]))
		{
			require ('a_rem.php');
		}
		else
		{

			if ($next <= $min_step && (!isset($_SESSION["first_banner"])) && $remaining <= $max_views)
			{
			   $_SESSION["first_banner"] = "shown";
			   require ('a_rem.php');
			}
			elseif ($remaining == 0) {

				$nexttemp = floor ($next / 2);

			  	if ($nexttemp < $min_step) {
			  		$nexttemp = $min_step;
			  	}

				$common->executeQuery ( "begin" );

				$sql = sprintf ( "update ds_useradsstatistics
						set views = views + 1, remainingviews = " . $next . ", nextstep = " . $nexttemp . ", lastview = now()
			  			where id_user = " . $user );

				$res = $common->executeQuery ( $sql );
				if ( $res === false ) {
					print "SQL/database - error 5";
					exit;
				}

				$common->executeQuery ( "commit" );

				require ('a_rem.php');
			} else {

				$common->executeQuery ( "begin" );

				$sql = sprintf ( "update ds_useradsstatistics
					set views = views + 1, remainingviews = remainingviews - 1, lastview = now()
				where id_user = " . $user );

				$res = $common->executeQuery ( $sql );
				if ( $res === false ) {
					print "SQL/database - error 6";
					exit;
				}

				$common->executeQuery ( "commit" );
				$common->disconnect();

				require ('flash_code.php');
			}
		}

	} else {

		$common->executeQuery ( "begin" );

		$remaining = ( isset($remaining) ? $remaining : 0 );

		if ( $remaining <= 0 )
		{
		  $newremaining = 0;
		}
		else
		{
		  $newremaining = $remaining - 1;
		}

		$sql = "update ds_useradsstatistics
			set views = views + 1, remainingviews = " . $newremaining . ", lastview = now()
			where id_user = " . $user;

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) 
		{
			print "SQL/database - error 7";
			exit;
		}

		$common->executeQuery ( "commit" );

		require ('flash_code.php');
	}
}else {

	require ('flash_code.php');
}*/


?>
