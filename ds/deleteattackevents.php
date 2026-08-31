<HTML>
<BODY>
<FORM action="deleteattackevents.php",method="POST">
<INPUT type="text" size="20"  name="username">
<INPUT type="submit" name="delete">
</FORM>
<?
require_once ('Main.php');

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	if ( !$common->setDbId(0) ) {
		echo "DB-connection failed";
		exit;
	}
}

if(isset($delete))
{
	if(preg_match('[^0-9_A-Za-z]+',$username) != 0 )
	{
		echo 'недопустимые символы в имени юзера';
		die();
	}
	$sql = sprintf ("SELECT
							ep.id_eventqueue, ui.id_userinstance
						FROM
    						ds_userinstance ui,
    						ds_user ur,
    						ds_eventparticipant ep,    						
    						ds_eventqueue eq
						WHERE
     						ur.username = '%s' AND
     						ur.id_user = ui.id_user AND
     						ep.id_userinstance = ui.id_userinstance AND
     						ep.id_eventqueue  = eq.id_eventqueue AND
     						eq.id_event in (22,32)",
							$username);

	$res0 = $common->getResultArray ( "", $sql, "", "", "" );

	if ( $res0 === false || sizeof($res0) === 0)
	{
		die("error1 или юзер не найден или у него нет евентов"); 
	}
	
	$count = 0;
	$count_events = sizeof($res0);
	$id_userinstance = $res0[0]['id_userinstance'];
	for ($i = 0; $i<$count_events; $i++)
	{
		$id_eventqueue = $res0[$i]['id_eventqueue'];
	
		$res = $common->executeQuery ( 'begin' );
		if(!$res)
		{
			die('не могу начать');
		}
		
		$res = $common->executeQuery ("DELETE FROM ds_eventhasoption WHERE id_eventqueue = $id_eventqueue" );
		if(!$res)
		{
			$common->executeQuery ( 'rollback' );
			echo ('не могу DELETE FROM ds_eventhasoption '.$id_eventqueue);
			continue;
		}
		
		$res = $common->executeQuery ("DELETE FROM ds_eventparticipant WHERE id_eventqueue = $id_eventqueue" );
		if(!$res)
		{
			$common->executeQuery ( 'rollback' );
			echo('не могу DELETE FROM ds_eventparticipant '.$id_eventqueue);
			continue;
		}
		
		$res = $common->executeQuery ("DELETE FROM ds_unitcontainerbindeventqueue WHERE id_eventqueue = $id_eventqueue" );
		if(!$res)
		{
			$common->executeQuery ( 'rollback' );
			echo('не могу DELETE FROM ds_unitcontainerbindeventqueue '.$id_eventqueue);
			continue;
		}
		
		$res = $common->executeQuery ("DELETE FROM ds_eventqueue WHERE id_eventqueue = $id_eventqueue" );
		if(!$res)
		{
			$common->executeQuery ( 'rollback' );
			echo('не могу DELETE FROM ds_eventqueue '.$id_eventqueue);
			continue;
		}
		
		$res = $common->executeQuery ("DELETE FROM 
													ds_unitcontaineraction 
											WHERE 
												id_unitcontainer 
											IN
												(
													SELECT
      														uc.id_unitcontainer
														FROM
    														ds_unitcontainer uc
														WHERE
     														uc.id_userinstance = $id_userinstance
												)"
										 );
		if(!$res)
		{
			$common->executeQuery ( 'rollback' );
			echo('не могу DELETE FROM ds_unitcontaineraction '.$id_userinstance);
			continue;
		}
		
		$res = $common->executeQuery ("UPDATE
      										ds_unitcontainer
										SET
   											inaction = 'f',
   											id_currentcomb = null
										WHERE
 											id_userinstance = $id_userinstance"
									 );
		if(!$res)
		{
			$common->executeQuery ( 'rollback' );
			echo('не могу UPDATE ds_unitcontainer for '.$id_userinstance);
			continue;
		}
		
		$res = $common->executeQuery ( 'commit' );
		if(!$res)
		{
			die('не могу кончить');
		}
		$count++;
	}
	echo '<br>удалено ' .$count . ' событий';
	
}

?>
</BODY>
</HTML>