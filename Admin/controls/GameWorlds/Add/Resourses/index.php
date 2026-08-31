<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}

class Resources {
	
	var $dbId;
	var $id_world;
	var $username;
	var $combParams;
	var $errormsg;
	var $comment;

	function Resources()
	{
		$this->getRequest($_POST);
	}

	function getRequest($request)
	{
		if ( isset($_SESSION["username"])) 
		{
			$this->username = $_SESSION["username"];
		}

		if ( isset($_SESSION["id_world"])) 
		{
			$this->id_world = $_SESSION["id_world"];
		}

		if ( isset($_SESSION["dbId"])) 
		{
			$this->dbId = $_SESSION["dbId"];
		}
		
		if(key_exists('comment', $request))
		{
			$this->comment = pg_escape_string(trim($request['comment']));
		}
		
		if (isset($request["saveChanges"]))
		{
			list( $this->id_world, $this->dbId ) = explode('_', $request['id_world']);
			$this->combParams = $request['delta'];
			$this->username = $request['username'];
			
			list( $_SESSION['id_world'], $_SESSION['dbId'] ) = explode('_', $data['id_world']);
			$_SESSION['username'] = $data["username"];
			
			if ( !$this->updateCombResources() )
			{
				$this->showFirstMenu ( $this->errormsg );
			} else 
			{
				$this->showFirstMenu ( ShowCMSMessage(local( 'DS_ACTION_SUCCEED ' ), 1) );				
			}
			
			if(!$this->showSecondMenu())
			{
				echo $this->errormsg;
			}
			
		}
		elseif( isset ($request["showSecondMenu"]) )
		{
			
			list( $this->id_world, $this->dbId ) = explode('_', $request['id_world']);
			$this->username = $request['username'];
			if( !$this->showSecondMenu() )
			{
				$this->showFirstMenu ( "<div class='FormText'>".$this->errormsg."</div>" );
			}
			
		}
		else
		{
			$this->showFirstMenu ( "<div class=\"FormText\"></div>" );
		}
		
		return true;
	}

	
	function showFirstMenu ( $str = "" )
	{
		global $common;

		print $str."
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<input type='hidden' name='showSecondMenu' value='1'>
		
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
			<tr>
				<td class=th_2 bgcolor='444E6C'>
					<span class=maintxt><b>".local( 'DS_USER_WORLD' )."</b> : </span>
				</td>
				<td class=th_2 bgcolor='444E6C'>
					<span class=maintxt><b>".local( 'DS_USER_USERNAME' )."</b> : </span>
				</td>
			</tr>
			<tr>
				<td bgcolor='444E6C'><select name='id_world' style='width:250px;'>";
		
		$sql = sprintf ( "select * from ds_world" );
		$res = $common->getMultySql ($sql);

		if ( !$res )
		{
			$this->errormsg = "Resources::showFirstMenu !!! ".$sql;
			return false;
		}
		
		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{
			echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."'".($this->id_world."_".$this->dbId==$res[$i]['id_world']."_".$res[$i]['dbId'] ? " selected" : "").">".$res[$i]['worldname']."</option>";
			//echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."'>".$res[$i]['worldname']."</option>";
		}
		
		echo "</select><br>
				</td>
				<td bgcolor='444E6C'>
					<input type='text' name='username' size=\"30\" value='".$this->username."'>
				</td>
			</tr>
			<tr>
				<td bgcolor='444E6C' colspan=2><input type='submit' class=submit name=\"next\" value=\"".local( 'DS_ACTION_SEARCH' )."\"></td>
			</tr>
		</table></td></tr></table>
		</form>
		";
		
		return true;
	}
	
	
	function showSecondMenu( )
	{
		global $common;
		
		$common->setDbId( $this->dbId );

		if ( $this->id_world == "" )
		{
			$this->errormsg = "Select world";
			return false;	
		}
		
		if ( $this->username == "" )
		{
			$this->errormsg = "Enter Username";
			return false;	
		}
		
		$sql = sprintf (
			"
			SELECT
			      u.username,
			      ui.id_userinstance,
				  ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
				  co.id_comb,
			      cn.combname,
			      chre.id_combhasresource,
			      attrname.attributename,
			      chre.value
			FROM
			     ds_user u
			     LEFT JOIN ds_userinstance ui ON ( u.id_user = ui.id_user )
			     LEFT JOIN ds_comb co ON ( ui.id_userinstance = co.id_userinstance )
			     LEFT JOIN ds_combname cn ON ( co.id_comb = cn.id_comb )
			     LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
			     LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
			     LEFT JOIN ds_combhasresource chre ON ( co.id_comb = chre.id_comb )
			     LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
			     LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
			     LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
			WHERE
			      attrtype.typename = 'CURRENT_AMOUNT'
			      AND attrname.attributename IN ( 'WORKER', 'SCIENTIST', 'ARCHEOLOGIST', 'FOOD', 'KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'STASIS_ENERGY', 'SPY_ZOND_SCOUT', 'SPY_ZOND_EXPLORER' )
			      AND u.username = '%s'
			      AND ui.id_world = '%d'
			ORDER BY
				ri.ringnumber,
				cc.complexnumber,
				co.combnumber,
				attrname.id_attributename
				
			",
			pg_escape_string( $this->username ),
			$this->id_world
		);
		
		$res = $common->getResultArray ( "resources", $sql, "", "", "" );
		if ( !$res )
		{
//			$this->errormsg = " SYSTEM ERROR - Resources::showSecondMenu !!! ".$sql;
			$this->errormsg = "No such User";
			return false;
		}

		// Выводим скрипт для проверки ввода комментария
		echo '
		 <script type="text/javascript" language="JavaScript"> 
         function check(buttons,message){ 
            if ( buttons == "" ){
            alert(message); 
            return false; 
            }
            return true;
         } 
            </script> ';
		
		echo "
		<form name='add_res' action='".$_SERVER["PHP_SELF"]."' method='POST' onsubmit=\"javascript: return check(document.add_res.comment.value,'Empty comment!');\">
		<input type='hidden' name='saveChanges' value='1'>		
		<input type='hidden' name='username' value='".$this->username."'>
		<input type='hidden' name='id_world' value='". $this->id_world.'_'.$this->dbId."'>
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
		";
		
		$temp_id_comb = '';

		foreach ( $res as $row )
		{
			if( $temp_id_comb != $row['id_comb'] )
			{
				print "
			<tr>
				<th colspan=4 bgcolor='444E6C' class='th_2'>CombName ( Coords )</th>
			</tr>
			<tr>
				<td colspan=4 bgcolor='444E6C' class='maintxt'><strong>".$row['combname']." ( ".$row['coords']." )</strong></td>
			</tr>
			<tr>
				<th bgcolor='444E6C' class='th_2'>ResourceName</th>
				<th bgcolor='444E6C' class='th_2'>Max ammount</th>
				<th bgcolor='444E6C' class='th_2'>ResourceAmount</th>
				<th bgcolor='444E6C' class='th_2'>Value</th>
			</tr>
			";
				$temp_id_comb = $row['id_comb'];
			}
			
			print "
			<tr>
<!--				<td bgcolor='444E6C' class='maintxt'></td> -->
				<td bgcolor='444E6C' class='maintxt'>". local ( 'ADMIN_' . $row['attributename']) ."</td>
				<td bgcolor='444E6C' class='maintxt'>". $this->getMaxAmmount($row['id_combhasresource']) ."</td>
				<td bgcolor='444E6C' class='maintxt'>".$row['value']."</td>
				<td bgcolor='444E6C' class='maintxt'><input type='text' name='delta[".$row['id_combhasresource']."]' value='0'></td>
			</tr>";
		}
		
		echo "
		<tr>
			<td colspan=4 bgcolor='444E6C' class='maintxt'>Comment: <input type='text' value='' name='comment' size='50'></td>
		</tr>
		<tr>
			<td colspan=4 bgcolor='#444E6C'><input type='submit' class=Submit value='". local( 'DS_ACTION_SUBMIT' )."' name='submit'></td>
		</tr>
		</table></td></tr></table></form>";
		
		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $this->id_world";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		$worldName = $res[0]['worldname'];
		
		actionLog( "Show resources amount for User '".$this->username."' in World '$worldName'");
		
		return true;
	}

	function updateCombResources()
	{
		if (empty($this->comment))
		{
			ShowCMSMessage('<b>ENTER COMMENT!!</b>', 2);
			return false;
		}
		
		global $common;
		$detail = "";
		
		$common->setDbId( $this->dbId );
		
		if ( $this->combParams == "" || !is_array( $this->combParams ) )
		{
			$this->errormsg = "Wrong Params";
			return false;
		}

		$common->executeQuery ( 'begin' );

		foreach ( $this->combParams as $id_attribute => $value )
		{
			if( $value == '' || $value == 0 )
			{
				continue;
			}
			
			$cur = $this->getCurrentAmmount($id_attribute);
			$max = $this->getMaxAmmount($id_attribute);		
			$oldValue = $value;
			
			if(((double)$value + $cur) > $max)
			{
				$value = $max;
			} else {
				$value = $value + $cur;
			}
									
			
			$sql = 
			"
			UPDATE
				ds_combhasresource
			SET
				value = ".$value."
			WHERE
				id_combhasresource = ".$id_attribute;
			
			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg = " SYSTEM ERROR - Resources::updateCombResources !!! ".$sql;
				return false;
			}
			
			$sql = "SELECT
						 an.attributename
						 FROM
						 ds_attribute as a 
						  LEFT JOIN ds_attributename as an ON (an.id_attributename = a.id_attributename)
						 WHERE a.id_attribute = (SELECT
						 c.id_attribute
						 FROM
						 ds_combhasresource c
						 WHERE c.id_combhasresource = $id_attribute)";
			$common->executeQuery ( 'commit' );
			
			//Получаем имя ресурса
			$res = $common->getResultArray ( "", $sql, "", "", "" );
		
			if ($common->errormsg)
			{			
				echo "<h2><font color=red>Can't normal log thise action</font></h2>";
			}
			$resName = $res[0]['attributename'];
			
			$detail .= "Add $oldValue of $resName (was $cur)<br>";	
				
			}
			
		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $this->id_world";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		$worldName = $res[0]['worldname'];
		
		$str = "Changing Comb Resources to User:'".$this->username."' in World:'".$worldName."'<br>\nComment:.'".$this->comment."'";

		actionLog($str, $detail);

		return true;
	}
	
	function getMaxAmmount($id_combhasresource)
	{
		global $common;
		
		$sql = sprintf("SELECT
      							cohr.value
							FROM
    							ds_combhasresource cohr,
    							ds_attribute a,
    							ds_attributename an,
    							ds_attributetype at,
    							ds_comb c
							WHERE
     							c.id_comb = 
     							(
     								SELECT
      										id_comb
									FROM
    									ds_combhasresource
									WHERE
     									id_combhasresource = %d
     							)
     							AND c.id_comb = cohr.id_comb
     							AND cohr.id_attribute = a.id_attribute
     							AND a.id_attributename = an.id_attributename
     							AND an.attributename = 
     							(
     								SELECT
      										an2.attributename
										FROM
    										ds_combhasresource cohr2,
    										ds_attribute a2,
    										ds_attributename an2
										WHERE
     										cohr2.id_combhasresource = %d
     										AND cohr2.id_attribute = a2.id_attribute
     										AND a2.id_attributename = an2.id_attributename
     							)
     							AND a.id_attributetype = at.id_attributetype
     							AND at.typename = 'MAX_AMOUNT'",
							$id_combhasresource,
							$id_combhasresource);
						
		$res = $common->getResultArray ( "", $sql, "", "", "" );
		if ($common->errormsg)
		{			
			return false;
		}
		if(sizeof($res) === 0)
		{
			return 999999999999;
		}
		else 
		{
			return $res[0]['value'];
		}
		
	}
	
	function getCurrentAmmount($id_combhasresource)
	{
		global $common;
		
		$sql = sprintf("SELECT
      							cohr.value
							FROM
    							ds_combhasresource cohr,
    							ds_attribute a,
    							ds_attributename an,
    							ds_attributetype at,
    							ds_comb c
							WHERE
     							c.id_comb = 
     							(
     								SELECT
      										id_comb
									FROM
    									ds_combhasresource
									WHERE
     									id_combhasresource = %d
     							)
     							AND c.id_comb = cohr.id_comb
     							AND cohr.id_attribute = a.id_attribute
     							AND a.id_attributename = an.id_attributename
     							AND an.attributename = 
     							(
     								SELECT
      										an2.attributename
										FROM
    										ds_combhasresource cohr2,
    										ds_attribute a2,
    										ds_attributename an2
										WHERE
     										cohr2.id_combhasresource = %d
     										AND cohr2.id_attribute = a2.id_attribute
     										AND a2.id_attributename = an2.id_attributename
     							)
     							AND a.id_attributetype = at.id_attributetype
     							AND at.typename = 'CURRENT_AMOUNT'",
							$id_combhasresource,
							$id_combhasresource);
						
		$res = $common->getResultArray ( "", $sql, "", "", "" );
		if ($common->errormsg)
		{			
			return false;
		}
		return $res[0]['value'];
			
	}
}

$resources = new Resources();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>