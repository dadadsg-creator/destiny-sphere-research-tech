<? 
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ActivationRecuirring><font size=3><b>HELP</b></font></a></p>';

if(!empty( $_POST['user'])) 
{
	if(!isset($GLOBALS["common"])) 
	{		
		$common = & ClassFactory::getInstance ( "DS_Common" );		
		if ( !$common->setDbId(0) ) 
		{
			echo local( 'DS_DB_FAILED' );
			exit;
		}
	}
	
	if(isset($_POST['immediately']))
	{
		$sql = "select 
						id_user
					from
						ds_user
					where
						username = '".$_POST['user']."'";
		
		$res = $common->getResultArray ("", $sql, "", "", "" );

		if($res === false) 
		{		
			echo "Error: ".local( 'DS_ACTIVATE_ERROR_ACTIVATE' )." :".pg_last_error();
			exit();
		}
		
		if(!isset($res[0]['id_user']))
		{
			echo "Error: ".local( 'DS_ACTIVATE_ERROR_ACTIVATE' );
		}
		else 
		{ 		
			$sql = "update
							ds_user
						set
							activate_mail = 1
						where
							id_user = ".$res[0]['id_user'];
			
			$common->executeQuery($sql);
			if($common->errormsg)
			{
				echo "Error: ".local( 'DS_ACTIVATE_ERROR_ACTIVATE' )." :".pg_last_error();
			}
			else 
			{
				print ShowCMSMessage("User ".$_POST['user']." activated", 1);	
			}
		}
						
	}
	else 
	{

		$sql = "
				SELECT
						email 
				FROM 
						ds_user 
				WHERE 
						id_language = 3 
						and username = '".$_POST['user']."'";

		$res_email = $common->getResultArray ( "act_email".$_POST['user'], $sql, "", "", "" );

		if ( $res_email === false ) 
		{		
			echo "Error: ".local( 'DS_ACTIVATE_ERROR_ACTIVATE' )." :".pg_last_error();
		} 
		else 
		{ 		
			if(!empty( $res_email[0]['email'] ) ) 
			{
				//actionLog("Send Recuring Activation. User '".$_POST['user']."' DbId:".$common->dbId);
				actionLog("Send Recuring Activation. User '".$_POST['user']."'");
 				print ShowCMSMessage(local( 'DS_ACTIVATE_SENDED', array( $res_email[0]['email'] ) ), 1); 			
      			$URL = "http://www.destinysphere.ru/ds/index_registration.php".'?action=activate&key='.md5($_POST['user']);
      		
				mail( $res_email[0]["email"],
	     			"Регистрация аккаунта в DestinySphere",
	     			"Для активации аккаунта пройдите по ссылке: \n".$URL,
	     			"From: nobody@destinysphere.net\n"); 
			} 
		}
	}
} 
    
?>

<FORM method=post action="<?=$_SERVER["PHP_SELF"];?>">
<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
<tr>
	<td>
		<table width=100% border=0 cellspacing=1 cellpadding=3>
<tr>
	<td class=th_2 align=center>
		<? echo local('DS_USER_USERNAME'); ?>
	</td>
</tr>
<tr>
	<td bgcolor="#444E6C">
		<input type=text name="user" value="" size=25>
	</td>
</tr>
<TR>
	<TD bgcolor="#444E6C" class=maintxt>
		<INPUT type="checkbox" class="check" name="immediately">
		Activate immediately
	</TD>
</TR>
<tr>
	<td bgcolor="#444E6C">
		<input type=submit class=Submit value="<? echo local( 'DS_ACTIVATE_ACTIVATE' ); ?>">
	</td>
</tr>
</TABLE></td></tr></table>
</form>

<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php"; 
?>