<?

function addUserToForum($username, $password, $email)
{	
	global $common;
	$forum_prefix = 'forum';
	
	if (constant("FORUM_REGISTRATION_ENABLED") == true) 
	{				
		if ( !$common->connect ( 'forum' ) ) 
		{								
			return false;						
		}
	
		$res = $common->executeQuery ( 'begin' );
	
		$sql = sprintf ( "insert into ".$forum_prefix."_groups 
							(group_name, 
							group_description, 
							group_single_user, 
							group_moderator)
							values ('', 'Personal User', 1, 0)" );
	
		$res = $common->executeQuery ( $sql );
			
		if ( $res === false ) 
		{			
			return false;
		}
	
		$sql = "select max(group_id) as gid from ".$forum_prefix."_groups";
	
		$res = $common->getResultArray ( "max_group_id", $sql, "", "", "" );
			
		if ( !$res ) 
		{		
			return false;
		}
	
		$gid = $res[0]['gid'];
	
		$sql = sprintf ( "insert into ".$forum_prefix."_users
						(user_id, username, user_regdate, user_password, user_email, user_icq, user_website,
						user_occ, user_from, user_interests, user_sig, user_sig_bbcode_uid, user_avatar,
						user_avatar_type, user_viewemail, user_aim, user_yim, user_msnm, user_attachsig,
						user_allowsmile, user_allowhtml, user_allowbbcode, user_allow_viewonline, user_notify,
						user_notify_pm, user_popup_pm, user_timezone, user_dateformat, user_lang, user_style,
						user_level, user_allow_pm, user_active, user_actkey)
						values ( DEFAULT, '%s', '%s', '%s', '%s', '', '', '', '', '', '', '', '', 0, 0, '', '', '',
						0, 1, 0, 1, 1, 0, 1, 1, %s, 'D M d, Y G:i', '%s', 7, 0, 1, 1, '')",
						$username, time(), $password, $email, constant("FORUM_TIMEZONE"), constant("FORUM_LANGUAGE") );
	
		$res = $common->executeQuery ( $sql );
			
		if ( $res === false ) 
		{		
			return false;
		}
	
		$sql = "select max(user_id) as uid from ".$forum_prefix."_users";
	
		$res = $common->getResultArray ( "max_user_id", $sql, "", "", "" );
			
		if ( !$res ) 
		{
			return false;
		}
	
		$uid = $res[0]['uid'];
	
		$sql = sprintf ( "insert into ".$forum_prefix."_user_group
							(user_id, group_id, user_pending)
							values (%d, %d, 0)",
							$uid, $gid );
	
		$res = $common->executeQuery ( $sql );
			
		if ( $res === false ) 
		{		
			return false;
		}
	
		$res = $common->executeQuery ( 'commit' );
	}

	return true;
	
}


if (count($_REQUEST)>0)
{
	if (!empty($_REQUEST['username'])) {$username = $_REQUEST['username'];} else {$username = '';}
	if (!empty($_REQUEST['username'])) {$password = md5 ( $_REQUEST['password']);} else {$password = '';}
	if (!empty($_REQUEST['email'])) {$email = urldecode($_REQUEST['email']);} else {$email = '';}

	if (!empty($username) || !empty($password) || !empty($email))
	{
		
		if ( !isset ( $GLOBALS["common"] ) )
		{
			$common = & ClassFactory::getInstance ( "DS_Common" );
		}
		$common->connect();


			$sql = sprintf ("
						SELECT 
							ID_User, username, email
						FROM
							DS_User
						WHERE
							UserName ILIKE '%s'
							OR email ILIKE '%s'",
						$username,
						$email);
			$res = $common->getResultArray ( "", $sql, "", "", "" );

			if (count($res)>0)
			{				
				if (($res[0]['username'] == $username) && ($res[0]['email'] == $email))
				{
					//Выходим, если в базе уже есть такой логин и email
					echo "-2";
					die();
				}
				if ($res[0]['username'] == $username)
				{
					//Выходим если в базе уже есть той логин
					echo "0";
					die();
				}
				if ($res[0]['email'] == $email)
				{
					//Выходим если в базе уже есть такое мыло
					echo "-1";
					die();
				}
			}
			else 	
			{
				$sql = sprintf ("
							INSERT INTO 
								ds_user 
									(
									id_language, 
									username, 
									email, 
									lastrefresh, 
									registrationdate, 
									vacationmode, 
									admin, 
									vacationstarttime, 
									accountactive, 
									selfdescription, 
									password, 
									time_delta, 
									activate_mail
									)
								VALUES
									(
									1,
									'%s',
									'%s',
									NULL,
									now(),
									False,
									False,
									NULL,
									True,
									NULL,
									'%s',
									1,
									1
									)"
							,$username,$email,$password);

				$res = $common->executeQuery($sql);
				if ($res)
				{
					$sql = sprintf ("
								SELECT 
									ID_User
								FROM
									DS_User
								WHERE
									UserName = '%s'
									and Password = '%s'",
								$username,
								$password);
					$res = $common->getResultArray ( "", $sql, "", "", "" );
					
					if (sizeof($res) >0)
					{
						if(addUserToForum($username, $password, $email))
						{
							$log_forum = ' succesfully added to forum';
						}
						else 
						{
							$log_forum = ' mistakes at accompaniment to forum';
						}
						$buffer = strftime('%Y-%m-%d %H:%M:%S')." :: Registered new user \"".$username."\" with email \"".$email."\" and id_user ".$res[0]['id_user'].".".$log_forum;
						$fp = fopen("gamigo.log", "a+");
						fwrite($fp, $buffer);

						echo $res[0]['id_user'];
						die(); //Для удачного завершения скрипта
					}
				}
			}//END IF ELSE
	}

}
	echo 0;
?>



