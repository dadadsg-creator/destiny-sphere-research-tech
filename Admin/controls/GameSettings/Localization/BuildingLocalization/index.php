<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=BuildingLocalization><font size=4><b>HELP</b></font></a></p>';

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

$obj = new BuildingLocalization();

class BuildingLocalization
{
	var $common = false;

	function BuildingLocalization()
	{
		$this->getPostData($_POST);
	}

	function getPostData($data)
	{
		$this->showRequestForm($data);

		if(isset($data['edit']))
		{
			$this->edit($data);
			$this->showLocalizeByRace($data);
		}

		if(isset($data['showlocalizeByRace']))
		{
			$this->showLocalizeByRace($data);
		}
		/*if(isset($data['showlocalizeByBuilding']))
		{
		$this->showLocalizeByBuilding($data);
		}*/
		return true;
	}

	function showLocalizeByRace($data)
	{
		assert(isset($data['id_race']));
		assert(is_array($data['languages']));

		$race = $data['id_race'];

		foreach ($data['languages'] as $k => $language)
		{
			$langArr[] = $language;
		}
//print_r($data['languages']);
//exit;		
		foreach ($data['languages'] as $k => $language)
		{
			$sql = sprintf("SELECT
							bcr.id_building,
               				ltl.id_localizedtext as id_loc_bname,
               				ltl.text as bname,
               				lt.id_localizedtext as id_loc_descr,
               				lt.text as descr
                        FROM
         					ds_buildingcanbuildbyrace as bcr
						LEFT JOIN ds_localizedtextlanguage AS ltl ON 
									(
									ltl.id_localizedtext = bcr.id_localizedname
									AND
									ltl.id_language = %d)
						LEFT JOIN ds_localizedtextlanguage AS lt ON 
									(
									lt.id_localizedtext = bcr.id_localizeddescription
									AND
									lt.id_language = %d)
         				WHERE
         					bcr.id_race = %d
         				ORDER BY bname",
			$language,
			$language,
			$race);

			$res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
			if($this->getDBConnection()->errormsg)
			{
				$this->outputResult("error in get localized text");
				return false;
			}
			
			echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
			<input type='submit' class='Submit' name='edit' value='Change'>";
			
			echo "<br>
				<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
					<input type='hidden' name='id_race' value='$race'>			
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2>
								Building
							</TD>
							<TD bgcolor='444E6C' class=th_2>
								Building description
							</TD>
						</TR>";	
//echo "<br> !!!!!!!!!!!!!! <br>";
			foreach ($langArr as $klangArr => $langName)
			{
//echo $langName;				
				echo "<input type='hidden' name='languages[]' value='$langName'>";
			}
			
			foreach($res as $key => $building)
			{
				echo "<TR>
				<TD bgcolor='444E6C' class=maintxt valign='top'>";
				echo "<input size=45 type='text' name='localiz[".$language."/".$building['id_loc_bname']."]' value='".$building['bname']."'>";
				echo "</TD>";

				echo "<TD bgcolor='444E6C' class=maintxt>";
				echo "<textarea name='localiz[".$language."/".$building['id_loc_descr']."]' rows='3' cols='95'>".$building['descr']."</textarea>";
				echo "</TD>";

				echo "</TR>";
			}
			echo "
							</TABLE>
						</td>
					</tr>
			</table>";
			echo "<input type='submit' class='Submit' name='edit' value='Change'></form>";
		}
		return true;
	}

	function showLocalizeByBuilding($data)
	{
		assert(isset($data['id_building']));
		assert(is_array($data['language']));

		$bildingArr = explode("/", $data['id_building']);

		$id_building = (int)$bildingArr[0];
		$id_race = (int)$bildingArr[1];

		assert(is_int($id_building) AND $id_building>=0);
		assert(is_int($id_race) AND $id_race>=0);
		//print_r($data['language']);
		//echo "<br>"	;
		foreach ($data['language'] as $k => $language)
		{

			$sql = sprintf("SELECT
               				bcr.id_building,
               				ltl.id_localizedtext,
               				ltl.text as bname,
               				lt.id_localizedtext,
               				lt.text as descr
                        FROM
         					ds_buildingcanbuildbyrace as bcr
						LEFT JOIN ds_localizedtextlanguage AS ltl ON 
									(
									ltl.id_localizedtext = bcr.id_localizedname
									AND
									ltl.id_language = %d)
						LEFT JOIN ds_localizedtextlanguage AS lt ON 
									(
									lt.id_localizedtext = bcr.id_localizeddescription
									AND
									lt.id_language = %d)
         				WHERE
         					bcr.id_race = %d
         					AND
         					bcr.id_building = %d
         				ORDER BY bname",
			$language,
			$language,
			$id_race,
			$id_building);
			/*echo "<xmp>";
			echo $sql;
			echo "</xmp>";
			exit;*/
			$res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
			if($this->getDBConnection()->errormsg)
			{
				$this->outputResult("error in get localized text");
				return false;
			}

			echo "<br>
				<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<INPUT type='hidden' name='attr_type' value='terraforming'>				
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2>
								Building
							</TD>
							<TD bgcolor='444E6C' class=th_2>
								Building description
							</TD>
							<TD bgcolor='444E6C' class=th_2>
								&nbsp;
							</TD>
						</TR>";	

			foreach($res as $key => $data)
			{
				echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>";
				echo "<TR>
				<TD bgcolor='444E6C' class=maintxt valign='top'>";
				echo "<input size=45 type='text' name='bname' value='".$data['bname']."'>";
				echo "</TD>";

				echo "<TD bgcolor='444E6C' class=maintxt>";
				echo "<textarea name='bname' rows='4' cols='95'>".$data['descr']."</textarea>";
				echo "</TD>";

				echo "<TD bgcolor='444E6C' class=maintxt>";
				echo "<input type='submit' class='Submit' name='edit' value='Change'>";
				echo "</TD>";
				echo "</TR>";
				echo "</form>";

			}

			echo "
						</TABLE>
					</td>
				</tr>
				</table>";
		}
		return true;
	}

	function outputResult($str)
	{
		echo $str;
		return true;
	}

	function getDBConnection()
	{
		if(!$this->common)
		{
			$_SESSION['dbId'] = 0;

			$this->common = DS_Common::CreateForCurentDB(0);
			if (!$this->common->connect())
			{
				$this->outputResult('DB connection failed');
				exit();
			}
		}
		$this->common->setDbId(0);

		return $this->common;
	}

	function showRequestForm($data)
	{
		if (key_exists('id_race', $data))
		{
			$curRace = $data['id_race'];
		}else {
			$curRace = "No";
		}

		if (key_exists('languages', $data))
		{
			foreach ($data['languages'] as $k => $language)
			{
				$curLanguages[] = $language;
			}
		}else {
			$curLanguages[] = "3";
		}

		/*$sql = sprintf("SELECT
		b.id_building,
		(
		SELECT
		ltl.text
		FROM
		ds_localizedtext lt,
		ds_localizedtextlanguage ltl
		WHERE
		lt.id_localizedtext = bcbbbr.id_localizedname
		AND lt.id_localizedtext = ltl.id_localizedtext
		AND ltl.id_language = %d
		) as buildingname,
		r.id_race,
		(
		SELECT
		ltl.text
		FROM
		ds_localizedtext lt,
		ds_localizedtextlanguage ltl
		WHERE
		lt.id_localizedtext = r.id_localizedname
		AND lt.id_localizedtext = ltl.id_localizedtext
		AND ltl.id_language = %d
		) as racename,
		bt.type
		FROM
		ds_building b,
		ds_buildingcanbuildbyrace bcbbbr,
		ds_buildingtype bt,
		ds_race r
		WHERE
		b.id_building = bcbbbr.id_building
		AND b.id_buildingtype = bt.id_buildingtype
		AND bcbbbr.id_race = r.id_race
		ORDER BY bcbbbr.id_race, bt.id_buildingtype",
		constant('DEFAULT_LANG_ID'),
		constant('DEFAULT_LANG_ID')
		);
		//echo $sql;
		//exit;
		$res = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
		if($this->getDBConnection()->errormsg)
		{
		$this->outputResult("error in getting list of buildings");
		return false;
		}*/

		echo "<table border=0><tr><td valign='top'>";
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>			
				<tr>
					<td>";
		/*echo "<table width=100% border=0 cellspacing=1 cellpadding=3>
		<TR>
		<TD bgcolor='444E6C' class=th_3 width='140'>
		Choose building:
		</TD>

		</TR>
		<TR>
		<TD bgcolor='444E6C' class=th_2>
		<SELECT name='id_building'>";

		$current_race = '';
		$current_type = '';
		foreach($res as $row)
		{
		if(strcmp($current_race,$row['racename']) != 0)
		{
		echo "<OPTION disabled>----------".$row['racename']."---------</OPTION>";
		$current_race = $row['racename'];
		}
		if(strcmp($current_type,$row['type']) != 0)
		{
		echo "<OPTION disabled>-------------".$row['type']."------------</OPTION>";
		$current_type = $row['type'];
		}

		if(strcmp($id_building,$row['id_building']) === 0)
		{
		echo "<OPTION selected value='".$row['id_building']."/".$row['id_race']."'>".$row['buildingname']."</OPTION>";
		}
		else
		{
		echo "<OPTION value='".$row['id_building']."/".$row['id_race']."'>".$row['buildingname']."</OPTION>";
		}
		}
		echo "</SELECT>
		</TD>
		</TR>
		<TR>
		<TD bgcolor='#444E6C' align='right' class=maintxt>
		<input type='submit'  class='Submit' name='showlocalizeByBuilding' value='Get'>
		</TD>
		</TR>
		</TABLE>
		</td>
		</tr>*/

		echo "</table>";

		$sql = sprintf("SELECT
               							r.id_race,
               							ltl.text
         							FROM
                						ds_localizedtext lt,
                						ds_localizedtextlanguage ltl,
    							     	ds_race r
         							WHERE
         							 lt.id_localizedtext = r.id_localizedname
         							AND
        							 lt.id_localizedtext = ltl.id_localizedtext
         							AND
                                     ltl.id_language = %d
      							",
		constant('DEFAULT_LANG_ID')
		);

		$race = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
		if($this->getDBConnection()->errormsg)
		{
			$this->outputResult("error in getting list of buildings");
			return false;
		}

		echo "</td><td valign='top'>";
		echo "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<INPUT type='hidden' name='action' value='showlocalizeByRace'>				
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_3 width='140'>
							Choose rase:
							</TD>
							
						</TR>
						<TR>
							<TD bgcolor='444E6C' class=th_2>
								<SELECT name='id_race'>";	



		foreach($race as $key => $race)
		{
			if ($race['id_race'] == $curRace)
			{
				$selected = 'selected';
			}else {
				$selected = '';
			}
			echo "<OPTION value='".$race['id_race']."' $selected>".$race['text']."</OPTION>";
		}
		echo "</SELECT>
							</TD>
						</TR>						
						<TR>
							<TD bgcolor='#444E6C' align='right' class=maintxt>
								<input type='submit'  class='Submit' name='showlocalizeByRace' value='Get'>
							</TD>
						</TR>
						</TABLE>
					</td>
				</tr>
				
				</table>";
		$sql = sprintf("SELECT
								*
								FROM
								ds_language
						ORDER bY language DESC
      							"
      							);

      	$languages = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
      	
		if($this->getDBConnection()->errormsg)
		{
			$this->outputResult("can't get language list");
			return false;
		}
		echo "</td>";
		echo "<td valign='top' class='maintxt'>";
		echo "<b>Language:&nbsp;</b>";
		echo "</td>";
		echo "<td valign='top' class='maintxt'>";
		foreach ($languages as $key => $language)
		{
			if (in_array($language['id_language'] , $curLanguages))
			{
				$c = 'checked';
			}else {
				$c = '';
			}
			echo "<input type='checkbox' name='languages[]' value='".$language['id_language']."' $c>&nbsp;&nbsp;".$language['language']."<br>";
		}
		echo "</form></td>";
		echo "</td></tr></table>";
		return true;
	}

	function edit($data)
	{
		assert(isset($data['id_race']));
		assert(is_array($data['languages']));
		assert(is_array($data['localiz']));
		
		$log = '';
		
		foreach ($data['localiz'] as $arrData => $text)
		{
			$Arr = explode("/", $arrData);
			$id_language = $Arr[0];
			$id_localizedtext = $Arr[1];
			
			$sql = sprintf("SELECT 
					     	text
					     	FROM
					     	ds_localizedtextlanguage
					     	WHERE
					     	id_language = %d
					     	AND
					     	id_localizedtext = %d",
						$id_language,
						$id_localizedtext);

			$oldData = $this->getDBConnection()->getResultArray ( "",$sql, "", "", "" );
			
			if($this->getDBConnection()->errormsg)
			{
				$this->outputResult("error in get old localized text");
				return false;
			}
	
			//Убираем ненужные слеши
			$text = stripcslashes($text);
			
			if ($oldData[0]['text'] == $text)
			{
				continue;
			}
			
			$sql = sprintf("UPDATE
            			ds_localizedtextlanguage 
									SET
   										text = '%s'
									WHERE
									id_language = %d
									AND
									id_localizedtext = %d",
		$text,
		$id_language,
		$id_localizedtext);

		$this->getDBConnection()->executeQuery($sql);

		if($this->getDBConnection()->errormsg)
		{
			$this->outputResult("can't change building name".
			$id_terraforminghasattribute."<br>");
		}
		
		$log .= 'Old value: "'.$oldData[0]['text'].'"<br>
				New value: "'.$text.'"<br>';
		
		}
		//echo "$log";
		//Логируем действие
		if ($log != '')
		{
		actionLog( "Edit localization for builing", "$log");
		}
		return true;
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>