<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";


/*if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}*/

class GameModifiersGroup {
	
	var $action = 'index';
	var $newModifierNames;
	var $modifierTypeId;
	var $moveToGroup;
	var $modifiersToMove = array();
	var $oldMofifierName;
	var $newModifiergroupName;
	var $dbId = 0;
	
	function GameModifiersGroup () 
	{ 	
		$this->getRequest ( $_REQUEST );

		switch ($this->action)
		{
			case 'edit_group':
				$this->EditGroup();
				$this->showModifiers();
				break;
				
			case 'addModifier':
				$this->addModifiers();
				$this->showModifiers();
				break;
				
			case 'rename_group_index':
				$this->RenameGroupIndex();
				//$this->showModifiers();
				break;
				
			case 'rename_group':
				$this->RenameGroup();
				$this->showModifiers();
				break;
				
			case 'delete_group':
				$this->DeleteGroup();
				$this->showModifiers();
				break;
				
			default:
				$this->showModifiers();
				break;
		}
	}	

	function showModifiers()
	{
		//???? ?????????? ?????? ?????????
		echo "
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<input type='hidden' name='action' value='addModifier'>
		
		<table border=1 cellspacing=1 bordercolor='black' cellpadding=5 bgcolor=222233>";

		echo "<tr>
				<th class=th_3 colspan='2'><font color=#F8F8FF>Add new modifier group</font></th>
			</tr>";
		
		echo "<tr>
				<td bgcolor='#2A3E66' align=right><input type='text' size ='60' name='new_modifier'></td>
				<td bgcolor='#2A3E66' align=right><input type='submit' class='Submit' value='Add'></td>
			</tr>";
		echo "
		</table></form>";
		//????? ?????


		$common = DS_Common::CreateForCurentDB($this->dbId);

		
		$sql = 
			"
			SELECT 
				*
			FROM 
				ds_gamemodifiertype 
			ORDER BY
				typename
			";
			
		$modifierTypes = $common->getResultArray( "", $sql, "", "", "" );
		
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't get modifier types: $common->errormsg</font><br>";	
			return;	
		}
		
		$resArr = array();
		
		if (!empty($modifierTypes)) 
		{
			foreach ($modifierTypes as $key => $data)
			{
				$modifiers = array();
				
				$sql = 
						"
						SELECT 
							count(modifiername) as modifier_count
						FROM 
							ds_gamemodifiertemplate
						WHERE
							id_modifiertype = ".$data['id_modifiertype']."
					";

				$res = $common->getResultArray ( "", $sql, "", "", "" );


				if (!empty($common->errormsg))
				{
					echo "<br><font color='red'>Can't get count modifiers of group '".$modifierTypes['id_modifiertype']."': $common->errormsg</font><br>";
					return;
				}

				$resArr[$data['typename']]['modifier_count'] = $res[0]['modifier_count'];

				$sql =
				"
						SELECT 
							modifiername
						FROM 
							ds_gamemodifiertemplate
						WHERE
							id_modifiertype = ".$data['id_modifiertype']."
						ORDER BY
							modifiername
					";

				$modifiers = $common->getResultArray ( "", $sql, "", "", "" );

				if (!empty($common->errormsg))
				{
					echo "<br><font color='red'>Can't get modifiers of group '".$modifierTypes['id_modifiertype']."': $common->errormsg</font><br>";
					return;
				}
				
				$resArr[$data['typename']]['id_modifiertype'] = $data['id_modifiertype'];
				
				
				foreach ($modifiers as $modifierKey => $modifierdata)
				{
					$resArr[$data['typename']]['modifiers'][] = $modifierdata['modifiername'];
				}
			}
		}
		
		$modifiers = array();
		
		//???????? ????????? ??? ??????
		$sql = 
						"
						SELECT 
							modifiername
						FROM 
							ds_gamemodifiertemplate
						WHERE
							id_modifiertype IS NULL
						ORDER BY
							modifiername
					";
						
		$modifiers = $common->getResultArray ( "", $sql, "", "", "" );
		
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't get count modifiers out of group: $common->errormsg</font><br>";
			return;
		}

		$sql = 
						"
						SELECT 
							count(modifiername) as modifier_count
						FROM 
							ds_gamemodifiertemplate
						WHERE
							id_modifiertype IS NULL
					";
		$res = $common->getResultArray ( "", $sql, "", "", "" );

		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't get modifiers out of group: $common->errormsg</font><br>";
			return;
		}
		
		$resArr['OUT_OF_GROUP']['id_modifiertype'] = 'NULL';
		$resArr['OUT_OF_GROUP']['modifier_count'] = $res[0]['modifier_count'];
				
		foreach ($modifiers as $modifierKey => $modifierdata)
		{
			$resArr['OUT_OF_GROUP']['modifiers'][] = $modifierdata['modifiername'];
		}

		/*echo "
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<input type='hidden' name='action' value=''>	*/	
	
		echo "<table cellSpacing='5' cellPadding='0' border='0'>";

		//?????????? ?? ???? ???????
		foreach ($resArr as $typename => $typedata)
		{
			echo '<tr>
					<td bgcolor="444E6C" class="FormText"><b>'.$typename.'</b> ('.$typedata['modifier_count'].')';
				
			if ($typename != 'OUT_OF_GROUP') {
				echo '
					 <a href="index.php?action=rename_group_index&old_mofifier_name='.$typename.'&id_modifiertype='.$typedata['id_modifiertype'].'">
					 	<img src="/Admin/img/knopka_edit.gif" title="Change group name"  border="0">
					 </a>  
					  <a href="javascript: if (confirm(\'Delete modigier group?\')) document.location.href=\'index.php?action=delete_group&id_modifiertype='.$typedata['id_modifiertype'].'\'">
					  <img src="/Admin/img/knopka_del.gif" title="Delete group" border="0">
					 </a>  
					 </td>
				</tr>';
			} else 
			{
				echo "</td></tr>";
			}
			
			echo "
					<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
					<input type='hidden' name='action' value='edit_group'>
					<input type='hidden' name='id_modifiertype' value='".$typedata['id_modifiertype']."'>";
			
			//???? ?????? ?? ??????
			if (count($typedata['modifiers']) > 0) 
			{
				foreach ($typedata['modifiers'] as $modifierKey => $modifierName)
				{
					echo "<tr><td>";
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
					echo "<input type='checkbox' name='modifiers_to_move[]' value='$modifierName'>$modifierName";
					echo "</td></tr>";
				}

				echo "<tr><td class='FormText'>";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				echo "Move to group: ";
				echo "<select name='move_to_group'>";
				foreach ($modifierTypes as $key => $data)
				{
					//???? ??? ??????? ??????, ?? ??????????
					if ($data['typename'] == $typename)
					{
						continue;
					}

					echo "<option value='".$data['id_modifiertype']."'>".$data['typename']."</option>";
				}
				echo "</select>";
				echo "&nbsp;&nbsp;<input type='submit' class='Submit' value='Move'";
				echo "</td></tr>";

				echo "</form>";
			}
			
			
			echo "<tr><td><hr></td><tr>";
			
			
		}		
		
		echo "
		</table>";
		
		return true;
	}

	function addModifiers() 
	{
		if (empty($this->newModifierName)) 
		{
			echo "Enter modifier name!!";
			return false;
		}
		
		assert($this->dbId>=0);
		$common = DS_Common::CreateForCurentDB($this->dbId);
		
		//???????, ??? ?? ? ???? ??? ?????? ?????????
		$sql = 
			"
				SELECT * 
				FROM 
					ds_gamemodifiertype 
				WHERE 
					typename = '$this->newModifierName' 
				ORDER BY
					typename
			";

		$res = $common->getResultArray ( "", $sql, "", "", "" );
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't get modifier type '$this->newModifierName': $common->errormsg</font><br>";		
			return;	
		}
		
		if (count($res) > 0) 
		{
			echo "<br><font color='red'>ERROR: Group with name '$this->newModifierName' already exist!</font><br>";
			return;			
		}	
		
		$sql = 
			"
				INSERT INTO ds_gamemodifiertype (typename) VALUES ('$this->newModifierName') 
			";
		
		$common->executeQuery($sql);
		
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't create modifier type '$this->newModifierName': $common->errormsg</font><br>";		
			return;	
		}
		
		return true;
	}
	
	function EditGroup()
	{
		assert($this->dbId>=0);
		$common = DS_Common::CreateForCurentDB($this->dbId);
		
		if (empty($this->moveToGroup)) 
		{
			
			echo "<br><font color='red'>ERROR: Not choosen group for move!</font><br>";		
			return;
		}
		
		assert($this->moveToGroup >= 0);
		
		if (count($this->modifiersToMove) == 0) 
		{
			
			echo "<br><font color='red'>ERROR: Not choosen modifiers to move!</font><br>";		
			return;
		}
		
		$sql = "UPDATE ds_gamemodifiertemplate SET id_modifiertype = $this->moveToGroup WHERE ";
		
		$or = '';
		
		foreach ($this->modifiersToMove as $key => $modifier)
		{
			$sql .= $or." modifiername = '$modifier' ";
			$or = ' OR ';
		}
		
		$common->executeQuery($sql);
		
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't insert modifiers to group '$this->newModifierName': $common->errormsg</font><br>";		
			return;	
		}
		
		return true;
	}
	
	function DeleteGroup()
	{
		assert($this->dbId>=0);
		$common = DS_Common::CreateForCurentDB($this->dbId);
		assert($this->modifierTypeId > 0);
		
		$sql = "UPDATE ds_gamemodifiertemplate SET id_modifiertype = NULL WHERE id_modifiertype = $this->modifierTypeId";

		$common->executeQuery($sql);
		
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can not delete group '$this->oldMofifierName': $common->errormsg</font><br>";		
			return;	
		}
		
		$sql = "DELETE FROM ds_gamemodifiertype WHERE id_modifiertype = $this->modifierTypeId";

		$common->executeQuery($sql);
		
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't delete group '$this->oldMofifierName': $common->errormsg</font><br>";		
			return;	
		}
		
		return true;
	}
	
	function RenameGroup()
	{
		if (empty($this->newModifiergroupName)) {
			
			echo "  <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
					<input type='hidden' name='action' value='rename_group_index'>
					<input type='hidden' name='old_mofifier_name' value='$this->oldMofifierName'>
					<input type='hidden' name='id_modifiertype' value='$this->modifierTypeId'>
					<input type='submit' class='Submit' value='Back'><br>
					</form>";
			echo "<font color=red>Empty Modifier Group Name!</font>";
			exit;
		}
		
		assert($this->dbId>=0);
		assert($this->modifierTypeId > 0);
		
		$common = DS_Common::CreateForCurentDB($this->dbId);
		
		$sql = "UPDATE ds_gamemodifiertype SET typename = '$this->newModifiergroupName' WHERE id_modifiertype=$this->modifierTypeId";
		
		$common->executeQuery($sql);
		
		if (!empty($common->errormsg))
		{
			echo "<br><font color='red'>Can't rename group '$this->oldMofifierName' to '$this->newModifiergroupName': $common->errormsg</font><br>";		
			return;	
		}
		
		return true;
	}
		
	function RenameGroupIndex()
	{
		assert($this->dbId>=0);
		assert($this->modifierTypeId > 0);
		
		$common = DS_Common::CreateForCurentDB($this->dbId);
		
		echo "  <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
					<input type='hidden' name='action' value='index'>
					<input type='submit' class='Submit' value='Back'><br>
					</form>";
		
		echo "
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<input type='hidden' name='action' value='rename_group'>
		<input type='hidden' name='old_mofifier_name' value='$this->oldMofifierName'>
		<input type='hidden' name='id_modifiertype' value='$this->modifierTypeId'>
		
		<table border=1 cellspacing=1 bordercolor='black' cellpadding=5 bgcolor=222233>";

		echo "<tr>
				<th class=th_3 colspan='3'><font color=#F8F8FF>Rename Modifier Group: <b>$this->oldMofifierName</b></font></th>
			</tr>";
		
		echo "<tr>
				<td bgcolor='#2A3E66' align=right><font color=#F8F8FF>New name: </font></td>
				<td bgcolor='#2A3E66' align=right><input type='text' name='new_modifiergroup_name' value='".$this->oldMofifierName."' size='60'></td>
				<td bgcolor='#2A3E66' align=right><input type='submit' class='Submit' value='Rename'></td>
			</tr>";
		echo "
		</table></form>";
		
		return true;
	}
		
	private function _getAllWorldsList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbid'] = $dbid;
				$out[] = $v;
			}
		}
		return $out;
	}
	
	function getRequest ($request) 
	{
		if (isset($request["action"]))
		{
			$this->action = $request["action"];
		}

		if (isset ($request["world"])) 
		{
			$dataTmp = explode('_', $request['world']);

			$this->dbId = $dataTmp[0];
			$this->worldId = $dataTmp[1];
		}
		
		if (isset ($request["worldId"])) 
		{
			$this->worldId = $request['worldId'];
		}
		
		if (isset ($request["dbId"])) 
		{
			$this->dbId = $request['dbId'];
		}
		
		if (isset ($request["new_modifier"])) 
		{
			$this->newModifierName = $request['new_modifier'];
		}
		
		if (isset ($request["move_to_group"])) 
		{
			$this->moveToGroup = (int)$request['move_to_group'];
		}
		
		if (isset ($request["modifiers_to_move"])) 
		{
			$this->modifiersToMove = $request['modifiers_to_move'];
		}
		
		if (isset ($request["id_modifiertype"])) 
		{
			$this->modifierTypeId = $request['id_modifiertype'];
		}	
		
		if (isset ($request["old_mofifier_name"])) 
		{
			$this->oldMofifierName = $request['old_mofifier_name'];
		}	
		
		if (isset ($request["new_modifiergroup_name"])) 
		{
			$this->newModifiergroupName = $request['new_modifiergroup_name'];
		}
	}
}

$nw = new GameModifiersGroup();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
