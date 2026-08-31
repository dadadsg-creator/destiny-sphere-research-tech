<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ImportCSV><font size=4><b>HELP</b></font></a></p>';

function print_rr ($array)
{
	echo "<xmp>";
	print_r ( $array );
	echo "</xmp>";
}


class ImportCVS
{

	var $dbId;
	var $wid;
	var $username;
	var $errormsg;
	var $request;
	var $userinstance;
	var $common;
	var $totalcount = 0;
	var $insertcount = 0;
	var $updatecount = 0;
	var $failedcount = 0;

	function ImportCVS()
	{
		if ( !isset ( $GLOBALS["common"] ) )
		{
			$this->common = & ClassFactory::getInstance ( "DS_Common" );
			$this->common->connect();
		}
		$this->common->setDbId(0);
///*DEBUG*/		print_rr ($_POST);
		$this->getRequest ( $_POST );
	}

	function getRequest ( $request ) {
		$this->request = $request;
		if ( !$this->showSecondMenu() ) {
			$this->showFirstMenu();
		}
		return true;
	}

	function showFirstMenu ( $str = "" ) {
		if ($this->errormsg){
			ShowCMSMessage($this->errormsg, 1);
			return false;
		}

		print Forms::openForm('',$_SERVER['PHP_SELF'],'post','',true);
		print Forms::hidden('showSecondMenu',1);
		print "
		<table cellSpacing='0' cellPadding='0'>
			<tr>
				<td colspan =2 bgcolor='444E6C'><span class=maintxt><b>Выберите файл : </b></span></td>
				<td colspan =2 bgcolor='444E6C'>";


		print Forms::fileField('file');
		print "</td>
			</tr>
			<tr><td colspan=2 height=5></td></tr>
			<tr>
				<td colspan=2 bgcolor='444E6C'></td>
				<td colspan=2 bgcolor='444E6C'>";
		print Forms::submitButton(local( 'DS_ACTION_SEARCH' ),'next','','',' class=submit');
		print "</td></tr></table>";
		print Forms::closeForm();

		return true;
	}	//Это запчасть от первой формы



	function showSecondMenu ()
	{
		if ($this->request[showSecondMenu] != 1) {return false;}
		$fh = file($_FILES['file']['tmp_name']);
		foreach ($fh as $key => $value)
		{
			if ($key == 0) {continue;}
///*DEBUG*/			if ($key >= 2) {continue;}

			if ($value[strlen($value)-1] == ";") {$value[strlen($value)-1] = "";}
			$explode = explode(';',$value);



			if (isset($explode[3]))
			{
				$explode[3] = str_replace("\r\n","",$explode[3]);
				if ($explode[3] == "") {unset ($explode[3]);}
			}

			if (!$this->Action($explode) )
			{
				$str .= $this->errormsg;
			}
		}

		$this->errormsg = ($str ? $str."<br>" : '')."Ошибочных строк - ".$this->failedcount." .<br>Обновлено строк - ".$this->updatecount." .<br>Новых строк - ".$this->insertcount.".<br>Всего обработано строк - ".$this->totalcount." .";
		return false;
	}// END METHOD showSecondMenu


	function Action ($parent)
	{
		$this->totalcount++;

		if (is_array($parent))
		{

			$sql = "
					SELECT
						id_language, id_localizedtext, text
					FROM
						ds_localizedtextlanguage
					WHERE
						id_language = ".$parent[0]."
						AND id_localizedtext = ".$parent[1]."

			";

			$res = $this->common->getQueryArray($sql);
//			if ( !$res ) {
//				$this->errormsg = "ERROR - Failed get localization list!".$sql;
//				return false;
//			}


			if ( empty($parent[1]) ) {return;}
			if ( !empty($parent[2]) )
			{
				$parent[2] = trim($parent[2]);
				$parent[2] = str_replace('<br />',"\r\n",$parent[2]);
				$parent[2] = stripslashes($parent[2]);


				while (ereg('""', $parent[2]))
				{
					$parent[2] = str_replace('""', '"', $parent[2]);
				}

				if (preg_match("/^\"/", $parent[2]))
				{
					$parent[2] = substr($parent[2], 1);
				}
	
				if (preg_match("/\"$/", $parent[2]))
				{
					$parent[2] = substr($parent[2], 0, strlen($parent[2])-1);
				}




			}


			if ( count($res) > 0 && $res == true)
			{
/* start update */
				//UPDATE
				if ( sizeof($res[0]) == sizeof($parent))
				{

//					if ( strcmp( $parent[2] , $res[0]['text'] ) != 0) {$a = true;} else {$a = false;}
					$a = true;
					if ($a)
					{
//DEBUG				print_rr ($res);
						if ( empty($parent[0]) || empty($parent[1]) || empty($parent[2]) )
						{
							$this->failedcount++;
							$this->errormsg = "Wrong parameters! ".implode(';',$parent);
							return false;
						}

						$sql = "
							UPDATE ds_localizedtextlanguage SET
								text = '".pg_escape_string($parent[2])."'
							WHERE
								id_language = ".$parent[0]."
								AND id_localizedtext = ".$parent[1]."
						";
//DEBUG echo "UPDATE<br><hr>".$sql."<hr><br><hr>".$res[0]['text']."<hr><br><br><br>";
						$ress = $this->common->executeQuery($sql);
						if ( !$ress ) {
							$this->errormsg = "ERROR - Failed UPDATE localization list! ".$sql;
							return false;
						}
						else
						{
							$this->updatecount++;
							$str = 'Change localization text "'.$res[0]['text'].'" on "'.$parent[2].'" id_language = '.$parent[0].' and id_localizedtext = '.$parent[1];
							//actionLog( addslashes($str) );
						}

					}
				} else {
					$this->failedcount++;
					return false;
				}
/* end update*/
			}
			else
			{
				//INSERT
				if ( empty($parent[0]) || empty($parent[1]) || empty($parent[2]) )
				{
					$this->errormsg = "ERROR - Failed INSERT NEW localization! <br>";
					$this->errormsg .= "id_language = ".$parent[0]."<br>";
					$this->errormsg .= "id_localizedtext = ".$parent[1]."<br>";
					return false;
				}

				$sql = "
						INSERT INTO ds_localizedtextlanguage
							(id_language, id_localizedtext, text)
						VALUES
						(".$parent[0].", ".$parent[1].", '".pg_escape_string($parent[2])."')
				";
//echo "INSERT<br><hr>".$sql."<hr><br><hr>".$res[0]['text']."<hr><br><br><br>";



				$res = $this->common->executeQuery($sql);
				if ( !$res ) {
					$this->errormsg = "ERROR - Failed INSERT localization list! ".$sql;
					return false;
				}
				else
				{
					$this->insertcount++;

					$str = 'Insert localization text "'.$res[0]['text'].'" on "'.$parent[2].'" id_language = '.$parent[0].' and id_localizedtext = '.$parent[1];
					//actionLog( addslashes($str) );
				}


			}




//			echo (!empty($parent[1]) ? $parent[1]."=>" : '').$parent[0].$parent[1].$parent[2]."<br>";
			//if ($parent[1])
		}




		return true;
	}// END METHOD Action

}

$import = new ImportCVS();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>