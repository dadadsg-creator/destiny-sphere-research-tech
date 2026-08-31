<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecOutInterfaceOld
{
	static public function SelectUserForm($worldsList)
	{
		
		$out = '
		<!-- form method="POST" enctype="multipart/form-data">		
			<b>NEW SEARCH BY USERNAME:</b> <input type="text" name="username">
			<input type="hidden" name="action" value="getuser">
			<input type="submit" value="Search">
		</form -->
		
		<form method="POST" enctype="multipart/form-data">		
			<b>FILE FOR MASS MOVEMENT:</b>&nbsp; <input type="file" name="listing" />		
			<input type="hidden" name="action" value="massmove">
			<input type="submit" value="MOVE">
		</form>';	
		
		return $out;
	}
	
	static public function MainForm($worldsList, $userData)
	{
		assert(is_array($worldsList));
		assert(is_array($userData));

		$out = '<h1> USER DATA TO MOVE</h1>';	
		
		$out .= self::SelectUserForm($worldsList);

		$out .= "<hr>";
		if(count($userData) == 0)
		{
			
			return $out;	
		}
		
		$out .= "<FORM action=".$_SERVER['PHP_SELF']." method=POST>";
		
		$options = '';
		foreach($worldsList as $k=>$worldData)
		{	
			if($worldData['world_identifier'] != 'HAM')	
			{
				continue;
			}
			
			$prefixName = $worldData["dbname"]."_";
			$prefixID = $worldData["dbId"]."_";
			
			$options .= '<option value="'.$prefixID.$worldData['id_world'].'">'.$prefixName.$worldData['worldname'].'</option>';
		}
		
		$out .= "<b>SELECT INSTANCE</b><table border=1>";
		$out .= "<tr><th>UserName</th><td>ServerName</td><th>WorldName</th><th>Race</th><th>ColonysCount</th><th>Action</th></tr>";
		
		$firstFlag = true;
		foreach($userData as $k=>$v)
		{

			if($firstFlag)
			{
				$chaked = 'checked';
				$firstFlag = false;
			}else{
				$chaked = '';
			}
			
			$colonysCount = $v['combscount'] - 1;			
			
			$radioButton = '<input type="radio" name="from_uiid" value="'.$v['dbId'].'_'.$v['id_userinstance'].'_'.$v['combscount'].'" checked="'.$chaked.'">';
			
			$out .= "<tr><td>".$v['username']."</td><td>".$v['dbname']."</td><td>".$v['worldname']."</td><td>".$v['racename']."</td><td>".$colonysCount."</td><td>$radioButton</td></tr>";			
		}
		$out .= "</table><br>";
		
		$out .= '<b>TO WORLD</b>: <select name="to_world_id">
				'.$options.'
				</select> ';
		
		$out .= '<input type="hidden" name="action" value="getmovedata">';	
		$out .= "<input type=submit value=Moove!>" ;
		$out .= "</FORM>" ;
		return $out;
	}
	
	static public function SecondForm($fromUIID, $toWorldID, $combsList, $fromDBID, $toDBID, $newCoordinates)
	{
		settype($fromUIID, 'integer');
		assert($fromUIID > 0);
		
		settype($toWorldID, 'integer');
		assert($toWorldID > 0);
		
		settype($fromDBID, 'integer');
		assert($fromDBID >= 0);
		
		settype($toDBID, 'integer');
		assert($toDBID >= 0);
		
		assert(is_array($combsList) && count($combsList) > 0);
		
		assert(is_array($newCoordinates) && count($newCoordinates) > 0);
				
		$out = '';		
		
		$out .= '<form method="POST">';	
		$out .= '<h2>INPUT NEW COMB COORDINATES:</h2>';		
		
		foreach($combsList as $k=>$v)
		{
			$common = SI_Base::GetCommon();					
			$common->setDbID($fromDBID);
			
			$ring = $newCoordinates[$v]['RING'];
			$complex = $newCoordinates[$v]['COMPLEX'];
			$comb = $newCoordinates[$v]['COMB'];
			
			$out .= '<b>COMB</b> '.$v.' ('.SI_SearchInterface::GetCombCoordsStringByName($v).'):<br> <input type=text size=1 name="ring['.$v.']" value='.$ring.'>.<input type=text size=3 name="complex['.$v.']" value='.$complex.'>.<input type=text size=1 name="comb['.$v.']" value='.$comb.'><br>';
		}
				
		$out .= '<input type="hidden" name="action" value="move">';	
		$out .= '<input type="hidden" name="from_uiid" value="'.$fromDBID.'_'.$fromUIID.'_'.count($combsList).'">';			
		$out .= '<input type="hidden" name="to_world_id" value="'.$toDBID.'_'.$toWorldID.'">';					
		$out .= '<input type="submit" value="START MOVEMENT!"></form>';	
		
		return $out;
	}
}

?>