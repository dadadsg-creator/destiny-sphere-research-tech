<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Reservcomb><font size=3><b>HELP</b></font></a></p>';

$common = & ClassFactory::getInstance ( "DS_Common" );
if ( !$common->connect() ) {
	die ( local( 'DS_DB_FAILED' ) );
}

if($_REQUEST['reserv']) 
{
	showSecond();
} else {
	showfirst($common->errormsg);
}

function showSecond () {
	global $common;
	
	if(@!$_REQUEST['reserv']) 
	{
		showfirst();
	} else {
		$world = explode('_', $_REQUEST['world']);
		$_REQUEST['WORLD'] = $world[0];
		$dbId = $world[1];
		$_REQUEST['DB_ID'] = $dbId;
		$common->setDbId( $dbId );
		
		IF(@$_REQUEST['test'] == 1)
		{
			$coords = trim(@$_REQUEST['coords']);
			$userName = trim(@$_REQUEST['username']);
			$worldID = (int)$world[0];
			assert($worldID > 0);
			
			
			if(!preg_match('/^[0-9]+\.[0-9]+\.[0-9\*]+$/', $coords) && !empty($coords)) 
			{
				showfirst( ShowCMSMessage(local( 'DS_USER_ERROR_WRONG_COORDS' ), 2));
				return false;		
			}
			
			if(!empty($userName))
			{
				$whereUser = "AND rc.username = '".$userName."'";
			}else{
				$whereUser = '';
			}
			
			if(!empty($coords))
			{
				$coorParts = explode(".", $coords);
				
				if($coorParts[2] != '*')
				{
					$whereCoords = "AND r.ringnumber = ".$coorParts[0]." AND cc.complexnumber = ".$coorParts[1]." AND c.combnumber = ".$coorParts[2]."";	
				}else{
					$whereCoords = "AND r.ringnumber = ".$coorParts[0]." AND cc.complexnumber = ".$coorParts[1]."";
				}
				
			}
			
			$sql = "SELECT
						rc.id_reservedcomb,
    					rc.username,
    					(
    						SELECT id_user
    						FROM ds_user
    						WHERE username = rc.username    					
    					) AS id_user,
    					w.worldname,
    					r.ringnumber,
    					cc.complexnumber,
    					c.combnumber,
    					rc.add_date,
    					rc.comment,
    					cn.combname
					FROM
    					ds_reservedcomb AS rc
    					INNER JOIN ds_comb AS c ON (c.id_comb = rc.id_comb)
    					LEFT JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
    					INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    					INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    					INNER JOIN ds_world AS w ON (w.id_world = r.id_world)
					WHERE
    					r.id_world = $worldID
						$whereUser
						$whereCoords
					ORDER BY rc.username, w.worldname, r.ringnumber, cc.complexnumber, c.combnumber, rc.add_date";
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			showfirst();
			
			
			if(!empty($coords))
			{
				$coorParts = explode(".", $coords);
				
				if($coorParts[2] != '*')
				{
					$sqlT = "				
					SELECT
    						u.username,
    						cn.combname,
    						co.id_comb
						 FROM
    						ds_comb AS co
    						
    						LEFT JOIN ds_userinstance AS ui ON (ui.id_userinstance = co.id_userinstance)
    						LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)    						
    						LEFT JOIN ds_combname AS cn ON (cn.id_comb = co.id_comb)
    						
    						INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = co.id_combcomplex)
    						INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    						INNER JOIN ds_world AS w ON (w.id_world = r.id_world)
						WHERE
    						w.id_world = $worldID		
							AND
    						r.ringnumber = ".$coorParts[0]."
    						AND
    						cc.complexnumber = ".$coorParts[1]."
    						AND
    						co.combnumber = ".$coorParts[2]."";		
					$resT = $common->getResultArray("", $sqlT, "", "", "");
					assert(empty($common->errormsg));

					if(count($resT) == 0)
					{
						echo "<b>ATTENTION!</b> <br> >>>>>>>> Comb: <b>($coords)</b> not exists <<<<<<<<<< <br><Br>";
					}else{
						if(empty($resT[0]['combname']))
						{
							echo " >>>>>>>>>>>> Comb: <b>".$resT[0]['combname']." ($coords)</b> is <b>FREEEEEEEEEE!!!!</b> <<<<<<<<<<<<<<<<<<<<<<<.<br><Br>";
						}else{
							echo " <b>ATTENTION!</b> <br> >>>>>>>> Comb: <b>".$resT[0]['combname']." ($coords)</b>  used by user: <b>".$resT[0]['username']."</b> <<<<<<<<<< </b><br><Br>";
						}
					}
				}else{
					$sqlT = "				
					SELECT
    						u.username,
    						cn.combname,
    						co.id_comb,
    						co.combnumber,
    						cc.complexnumber,
    						r.ringnumber
						 FROM
    						ds_comb AS co
    						
    						LEFT JOIN ds_userinstance AS ui ON (ui.id_userinstance = co.id_userinstance)
    						LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)    						
    						LEFT JOIN ds_combname AS cn ON (cn.id_comb = co.id_comb)
    						
    						INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = co.id_combcomplex)
    						INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    						INNER JOIN ds_world AS w ON (w.id_world = r.id_world)
						WHERE
    						w.id_world = $worldID		
							AND
    						r.ringnumber = ".$coorParts[0]."
    						AND
    						cc.complexnumber = ".$coorParts[1]."
    					ORDER BY r.ringnumber, cc.complexnumber, co.combnumber";		
					$resT = $common->getResultArray("", $sqlT, "", "", "");
					assert(empty($common->errormsg));
					
					if(count($resT) == 0)
					{
						echo "<b>ATTENTION!</b> <br> >>>>>>>> CombComplex: <b>($coords)</b> not exists <<<<<<<<<< <br>";
					}else{
						echo "CombComplex <b>($coords)</b> Info: <br>";					
						foreach($resT as $k=>$v)
						{
							echo $v['ringnumber'].".".$v['complexnumber'].".".$v['combnumber']." - ".$v['combname']."<br>";
						}					
						echo "<br>";
					}
							
				}
				
								
			}
			
			if(!empty($userName))
			{								
				$sqlT = "
				
					SELECT    					
    						cn.combname,
    						r.ringnumber,
    						cc.complexnumber,
    						co.combnumber
						 FROM
    						ds_comb AS co
    						
    						LEFT JOIN ds_userinstance AS ui ON (ui.id_userinstance = co.id_userinstance)
    						LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)    						
    						LEFT JOIN ds_combname AS cn ON (cn.id_comb = co.id_comb)
    						
    						INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = co.id_combcomplex)
    						INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    						INNER JOIN ds_world AS w ON (w.id_world = r.id_world)
						WHERE
    						w.id_world = $worldID		
							AND
    						u.username = '$userName'";		
				$resT = $common->getResultArray("", $sqlT, "", "", "");
				assert(empty($common->errormsg));

				if(count($resT) > 0)
				{
					echo "<b>ATTENTION!</b> <br> >>>>>>>> User <b>$userName</b> has combs in this world: <b>";
					
					foreach($resT as $k=>$v)
					{
						echo $v['combname']."(".$v['ringnumber'].".".$v['complexnumber'].".".$v['combnumber']."), ";
					}
					
					echo "<<<<<<<<<< </b><br><Br>";
				}				
			}
			
			echo "<form method=POST> <table border=1 cellpadding=4 >";
			echo "<tr>
					<td bgcolor='444E6C' class=th_2 align=center>User</th>
					<td bgcolor='444E6C' class=th_2 align=center>World</th>
					<td bgcolor='444E6C' class=th_2 align=center>Comb</th>
					<td bgcolor='444E6C' class=th_2 align=center>AddDate</th>
					<td bgcolor='444E6C' class=th_2 align=center>CombName</th>
					<td bgcolor='444E6C' class=th_2 align=center>Comment</th>
					<td bgcolor='444E6C' class=th_2 align=center>Delete</th>
				  </tr>";
			foreach($res as $k=>$v)
			{
				echo "<tr>";
				echo "<td >".$v['username']." (".$v['id_user'].")</td>";
				echo "<td >".$v['worldname']."</td>";
				echo "<td >".$v['ringnumber'].".".$v['complexnumber'].".".$v['combnumber']."</td>";
				echo "<td >".$v['add_date']."</td>";
				echo "<td >".$v['combname']."&nbsp;</td>";
				echo "<td >".$v['comment']."</td>";
				echo "<td align=center>&nbsp;&nbsp;&nbsp;<input type=checkbox name=for_delete[".$v['id_reservedcomb']."] value=1>&nbsp;&nbsp;&nbsp;</td>";
				echo "</tr>";
			} 
			
			
			
		
			echo "<tr><td colspan=7 align=right>";
			echo "<input type=hidden name=world value=".$_REQUEST['WORLD']."_".$dbId.">";
			echo  "<input type='submit' class=Submit name=\"reserv\" value='Delete'>";
			echo "<td></tr>";
			
			echo  "</table></form>";
			
		}elseif (@$_REQUEST['reserv'] == 'Delete'){
			
			showfirst();
			
			foreach($_REQUEST['for_delete'] as $id=>$v)
			{
				$sqlT = "DELETE FROM ds_reservedcomb WHERE id_reservedcomb = $id";
				$resT = $common->getResultArray("", $sqlT);
				assert(empty($common->errormsg));
				
				echo "<br>".$sqlT."<br>";
				
				echo "Recod with ID = $id is deleted<br>";
			}						
			echo "Deletion is complit<br>";
		}else{
			if ( @!$_REQUEST['username'] ) {
				showfirst( ShowCMSMessage(local( 'DS_USER_ERROR_ENTER_USERNAME' ), 2) );
				return false;
				//exit;
			}

			if ( preg_match ( '/[\'\"\`\!\@\#\$\%\^\*\(\)\;\:\{\}\[\]\<\>\?\ ]/', $_REQUEST['username'] ) ) {
				showfirst( ShowCMSMessage(local( 'DS_USER_ERROR_WRONG_SYMBOLS' ), 2) );
				return false;
				//exit;
			}

			if ( !preg_match ( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $_REQUEST['coords'] ) ) {
				showfirst( ShowCMSMessage(local( 'DS_USER_ERROR_WRONG_COORDS' ), 2) );
				return false;
				//exit;
			}
			
			$dest = explode ( ".", $_REQUEST['coords'] );

			$dest_ring = $dest[0];
			$dest_combcomplex = $dest[1];
			$dest_comb = $dest[2];

			$sql = sprintf ( "select co.id_comb,
			co.id_userinstance
			from ds_comb co,
			ds_combcomplex cc,
			ds_ring ri
			where co.id_combcomplex = cc.id_combcomplex
			and cc.id_ring = ri.id_ring
			and ri.id_world = %d
			and co.combnumber = %d
			and cc.complexnumber = %d
			and ri.ringnumber = %d",
			$world[0],
			$dest_comb,
			$dest_combcomplex,
			$dest_ring );
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			if ( sizeof ( $res ) == 0 ) 
			{
				showfirst( ShowCMSMessage(local( 'DS_USER_ERROR_NO_SUCH_COMB' ), 2) );
				return false;
				//exit;
			}
			if ( $res[0]['id_userinstance'] ) {
				showfirst( ShowCMSMessage(local( 'DS_RESERVCOMB_ERROR_BUSY_COMB', array( $dest_ring, $dest_combcomplex, $dest_comb, $world[0], $world[1], $res[0]['id_userinstance'] ) ), 2) );
				return false;
				//exit;
			}

			$id_comb_to = $res[0]['id_comb'];

			$sql = sprintf ( "select id_comb
			from ds_reservedcomb
			where id_comb = %d",
			$id_comb_to );

			$res = $common->getResultArray ( "reserved_comb", $sql, "", "", "" );
			if ( $res === false ) {
				showfirst( ShowCMSMessage(local( 'DS_RESERVCOMB_ERROR_RESERVED_COMB' ), 2) );
				return false;
				//exit;
			}

			if ( sizeof ( $res ) > 0 ) {
				showfirst( ShowCMSMessage(local( 'DS_RESERVCOMB_ERROR_RESERVED_COMB' ), 2) );
				return false;
				//exit;
			}

			// проверка на уже существующие зарезервированиые соты
			$sql = "
				SELECT 
					rc.id_comb
				FROM
					ds_reservedcomb AS rc
					INNER JOIN ds_comb AS c USING (id_comb)
					INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
					INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				WHERE
					username = '".$_REQUEST['username']."'
					AND
					r.ringnumber = $dest_ring
				";
			$res = $common->getResultArray ( "reserved_comb", $sql, "", "", "" );
			assert(empty($common->errormsg));
			if ( $res === false ) {
				showfirst( ShowCMSMessage(local( 'DS_RESERVCOMB_ERROR_RESERVED_USER' ), 2) );
				return false;
				//exit;
			}

			if(sizeof($res) > 0 && $dest_ring != 4) {
				showfirst( ShowCMSMessage(local( 'DS_RESERVCOMB_ERROR_RESERVED_USER' ), 2) );
				return false;
				//exit;
			}			

			$sql = "insert into ds_reservedcomb 
					(id_comb, username, comment)
					values 
					( $id_comb_to, '".$_REQUEST['username']."', E'".pg_escape_string($_REQUEST['comment'])."' )";
			$res = $common->executeQuery($sql);
			assert(empty($common->errormsg));
			
			if($res === false) 
			{
				showfirst( ShowCMSMessage(local( 'DS_RESERVCOMB_ERROR_RESERV' ), 2) );
				return false;				
			}else {
				//Получаем имя мира по его id
				$sql = "SELECT
								worldname
								FROM ds_world 
								WHERE id_world = ".$world[0]." ";
		
				$res = $common->getResultArray("", $sql, "", "", "");
				assert(empty($common->errormsg));
				$worldName = $res[0]['worldname'];
				actionLog( 'Reserv comb on DB:'.$dbId.' in World:'.$worldName.' for UserName:'.@$_REQUEST['username'].' by Coords:'.@$_REQUEST['coords']);
			}

			showfirst(ShowCMSMessage(local('DS_ACTION_SUCCEED'), 1));
		}
	}
}

function showfirst($str="") {
	global $common;


	if ($str != "") echo $str."<br>";
	?>	
		<FORM method="POST" action="<?=$_SERVER['PHP_SELF']?>">
	<?
	$sql = "select ID_World, WorldName from DS_World";
	$res = $common->getMultySql($sql);
	if(!$res) 
	{
		showfirst("error4");
		exit;
	}

	print "
		<h2>COMB RESERVATION</h2>
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=4>
		<tr>
			<td bgcolor='444E6C' class=th_2>".local( 'DS_ACTION_SELECT_WORLD' )."</td>
			<td bgcolor='444E6C' class=th_2>".local( 'DS_USER_COORD' )."</td>
			<td bgcolor='444E6C' class=th_2>".local( 'DS_USER_USERNAME' )."</td>
		</tr>
		<tr>
			<td bgcolor='444E6C' valign=top>
				<select name='world' style='width:250px;'>";
					foreach ($res as $k=>$v ) 
					{
						if($v['id_world'] == $_REQUEST['WORLD'] && $v['dbId'] == $_REQUEST['DB_ID'])
						{
							$selected = "SELECTED";
						}else{
							$selected = '';
						}
			
						$opt = "<option value='".$v['id_world']."_".$v['dbId']."' $selected>".$v['worldname']."</option>";		
						print $opt;
					}
	
					?>
				</select><br>
			</td>
			<td bgcolor='444E6C' valign=top>
				<input type='text' name="coords" maxlength="20" value="<?=@$_REQUEST['coords'] ?>"></td>
			<td bgcolor='444E6C' valign=top>
				<input type='text' name="username" maxlength="30" value="<?=@$_REQUEST['username'] ?>"></td>
		</tr>
		
		<tr>
			
		</tr>
			<td bgcolor='444E6C' valign=top align="center" colspan=3>
				<b>Comment:</b><br>
				<textarea name="comment" cols="85" rows="6"><?=@$_REQUEST['comment'] ?></textarea>
			</td>
		<tr>
			<td  bgcolor='444E6C' valign=top align=right><input type='submit' class=Submit name="reserv" value='<? echo local( 'DS_ACTION_RESERV' ); ?>'></td>
			<td colspan=2 bgcolor='444E6C' valign=top align=left> <input type="checkbox" name="test" value="1" class="check" ><b>Check</b></td>
		</tr>
		</TABLE></td></tr></table>
		</FORM>
<?
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>