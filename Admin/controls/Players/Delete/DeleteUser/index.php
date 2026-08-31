<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=DeleteUser><font size=3><b>HELP</b></font></a></p>';

if (class_exists ( classfactory)) {
$common = & ClassFactory::getInstance ( "DS_Common" );
$common->setDbId(0);
}


?>

<form method='post' action='<?=$_SERVER["PHP_SELF"]?>'>		  
<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
<TR><TD bgcolor="444E6C" class=th_2><? echo local( 'DS_USER_EMAIL' ); ?></td></tr>
<tr><td bgcolor='#444E6C' class=maintxt><input name='email' type="text" size="50" value="<?=@$_REQUEST["email"]?>"></td></tr>
<tr><td bgcolor="444E6C" class=th_2><? echo local( 'DS_USER_USERNAME' ); ?></td></tr>
<tr><td bgcolor='#444E6C' class=maintxt><input name = 'username' type ='text' size = '50' value="<?=@$_REQUEST["username"]?>">
<input type="hidden" name="action" value="search">
</TD></TR>
<TR><TD bgcolor="#444E6C">
<input type="submit" class=Submit name="search" value="<? echo local( 'DS_ACTION_SEARCH' ); ?>">
<input type="checkbox" name="compliancesearch" value="FULL" class="check" <?if ($_REQUEST['compliancesearch'] == 'FULL') {echo 'checked="checked"';}?>><? echo local( 'DS_COMPLIANCE_SEARCH' ); ?>
</TD></TR>
</TABLE></td></tr></table>
</form>

<?
if ( @$_REQUEST["action"] == 'search' ) 
{
	if ($_REQUEST['compliancesearch'] == "FULL")
	{
		if (!empty($_REQUEST["username"])) {$likeusername = " AND u.username = '".$_REQUEST["username"]."'";}
		if (!$likeusername) {$separator = 'AND';} else {$separator = 'AND';}
		$likeemail = ''.(!empty($_REQUEST["email"]) ? " ".$separator." u.email = '".$_REQUEST["email"]."'" : " ").'';
	}else {
		$likeusername = ''.(!empty($_REQUEST["username"]) ? " AND u.username ilike '".trim('%%'.$_REQUEST["username"].'%%')."'" : " ").'';
		$likeemail = ''.(!empty($_REQUEST["email"]) ? " AND u.email ilike '".trim('%%'.$_REQUEST["email"].'%%')."'" : " ").'';
	}

	$sql = sprintf ( "
			SELECT 
				u.username, 
				u.email, 
				u.registrationdate, 
				ui.lastrefresh, 
				ui.id_userinstance, 
				w.worldname, 
				(
					SELECT
					      SUM( c.combscore ) as score
					FROM
					    ds_comb c
					WHERE
					     c.id_userinstance = ui.id_userinstance
					GROUP BY
					      c.id_userinstance 
				) as userscore
			FROM 
				ds_user u 
				left outer join ds_userinstance ui on (u.id_user = ui.id_user) 
				left outer join ds_world w on (w.id_world = ui.id_world)
			WHERE
				u.id_user >= 0
				 %s 
				 %s
			order by 
				u.username",
			$likeusername,
			$likeemail);
	$res = $common->getMultyArray ( "maxnuber", $sql, "", "", "" );

	if ( !$res ) {
		print "<br><br>".ShowCMSMessage(local( 'DS_USER_NO_FOUND' ), 2);
		
	} else {
		
		echo '
		<table width=100% border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
			<tr>
				<th width="10%" class=th_2>'.local( 'DS_DB_NAME' ).'</th>
				<th width="10%" class=th_2>'.local( 'DS_USER_USERNAME' ).'</th>
				<th width="16%" class=th_2>'.local( 'DS_USER_EMAIL' ).'</th>
				<th width="13%" class=th_2>'.local( 'DS_USER_REGDATE' ).'</th>
		 		<th width="10%" class=th_2>'.local( 'DS_USER_WORLD' ).'</th>
		 		<th width="13%" class=th_2>'.local( 'DS_USER_LAST_REFRESH' ).'</th>
				<th width="8%"  class=th_2>'.local( 'DS_USER_SCORE' ).'</th>
				<th colspan=2 width="10%" class=th_2>'.local('DS_ACTIONS').'</th>
<!--		 		<th width="16%" class=th_2>&nbsp;</th> -->
						    </tr>';
		 $output = '';
		 foreach ( $res as $k=>$v ) {			
		 	   
		 	
//			if ($v['worldname'] == '' && count($res) > 1) {continue;}
			
			   if($v['worldname']){
			     $worldname = $v['worldname'];
			   }else{
			   	 $worldname = '-';
			   }
			   
			   if($v['lastrefresh']){
			     $lastrefresh = $v['lastrefresh'];
			   }else{
			   	 $lastrefresh = '-';
			   }
			   
			   if($v['userscore']){
			     $userscore = $v['userscore'];
			   }else{
			   	 $userscore = '-';
			   }
			   
			   
			   $output .= "
			   	<tr>
			   		<td bgcolor='#444E6C' class=maintxt align=center>".$GLOBALS['dbWorldAccess'][$v['dbId']]['server']."</td>
						<td bgcolor='#444E6C' class=maintxt align=center>".$v['username']."</td>			
						<td bgcolor='#444E6C' class=maintxt align=center>".$v['email']."</td>			
						<td align=\"center\" bgcolor='#444E6C' class=maintxt>".$v['registrationdate']."</td>			
						<td align=\"center\" bgcolor='#444E6C' class=maintxt>".$worldname."</td>						   
			   		<td align=\"center\" bgcolor='#444E6C' class=maintxt>".$lastrefresh."</td>						   
						<td align=\"center\" bgcolor='#444E6C' class=maintxt>".$userscore."</td>			
						<td align=\"center\" bgcolor='#444E6C' class=maintxt><a href=\"".JSConfirm(local( 'DS_DELETE_USER_COMPLETE_DELETE_CONFIRM', array( $v['username'] ) ), $_SERVER['PHP_SELF']."?action=delete&scope=complete&name=".$v['username']."&dbId=".$v['dbId']."&username=".$_REQUEST['username']."&email=".$_REQUEST['email'])."\" >".local( 'DS_DELETE_USER_COMPLETE_DELETE' )."</a></td>			
			   		<td align=\"center\" bgcolor='#444E6C' class=maintxt>";
				if($v['worldname']){
					$output .= "<a href=\"".JSConfirm(local( 'DS_DELETE_USER_INSTANCE_DELETE_CONFIRM', array( $v['username'], $worldname ) ), $_SERVER['PHP_SELF']."?action=delete&scope=instance&id=".$v['id_userinstance']."&dbId=".$v['dbId']."&name=".$v['username']."&world=".$v['worldname']."&email=".$_REQUEST['email'])."\" >".local( 'DS_DELETE_USER_INSTANCE_DELETE' )."</a>";
				}else{
					$output .= "-";
				}
				
				$output .=  "</td></tr>";
		}
		$output .= '</table></td></tr></table>';
		echo $output;
	}
	
}elseif(@$_REQUEST["action"] == 'delete'){
	if($_REQUEST["scope"] == 'instance'){
		$dbId = $_REQUEST['dbId'];
		$uiid = $_REQUEST['id'];
		settype($dbId, 'integer');
		settype($uiid, 'integer');
		
		
		
		if(!$uiid){
			die('ERROR: '.local( 'DS_DELETE_USER_ERROR_UIID' ) );
		} 


		/*require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/DropUserInstance.class.php";
		
		$drop = new DropUserInstance;
		$drop->drop($dbId, $uiid);*/
		
		$commonDelete = new DS_Common();
		$commonDelete->setDBID($dbId);
		$deleteController = new DU_ControllerDeleteUserInstance($commonDelete);
		echo "<xmp>";		
		$deleteController->drop($uiid);
		echo "</xmp>";
		
		actionLog( "Delete User ".$_REQUEST['name']." with UserInstance: ".$uiid." in ".$_REQUEST['world']." on DbId:".$common->dbId);
		
	}elseif($_REQUEST["scope"] == 'complete')
	{
		die("Complete delete users is denied!");
		$dbId = $_REQUEST['dbId'];
		$name = $_REQUEST['name'];
		settype($dbId, 'integer');
		
		
		
		if(!$name){
			die('ERROR: '.local( 'DS_DELETE_USER_ERROR_NAME' ) );
		}
		
		require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/DropUserComplete.class.php";
		
		$drop = new DropUserComplete;
		//$drop->drop($name);
		actionLog( "Delete User : '".$name."' DbId:".$common->dbId);
	}
	
}
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>

	
