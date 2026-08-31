<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

function print_rr ($array) {
	echo "<xmp>";
	print_r ( $array );
	echo "</xmp>";
}


class RankingBuilding {

	private $dbId;

	private $wid;

	private $errormsg;

	private $langs;

	private $common;

	private $action = 'index';

	function RankingBuilding( ) {
		$this->getRequest ( $_POST );

		echo "<H1>Ranking Artefact</H1>";

		switch ($this->action) {
			case 'ImportCsv':
				if (!$this->importCsv()) {
					$this->showFirstMenu();
				}
				break;

			case 'ExportCsv':
				if (!$this->exportCsv()) {
					$this->showFirstMenu();
				}
				break;

			/*case 'Recount':
				if (!$this->recount()) {
					$this->showFirstMenu();
				}
				break;*/

			default:
				$this->showFirstMenu();
				break;
		}
	}

	function getRequest ( $request )
	{
		if (key_exists('action', $request)) {
			$this->action = $request['action'];
		}
	}

	function showFirstMenu ()
	{
		if ($this->errormsg){
			ShowCMSMessage($this->errormsg, 1);
			return true;
		}

		print Forms::openForm('',$_SERVER["PHP_SELF"],'post');
		print Forms::hidden('action', 'ExportCsv');
		print Forms::submitButton('Ёкспортировать в CVS','next','','',' class=submit');
		print Forms::closeForm();

		echo "<br><br>";

		print Forms::openForm('',$_SERVER['PHP_SELF'],'post','',true);
		print Forms::hidden('action', 'ImportCsv');
		print Forms::fileField('file');
		echo "<br><br>";
		print Forms::submitButton('»мпортировать из CVS','next','','',' class=submit');
		print Forms::closeForm();


		/*echo "<br><br>";

		print Forms::openForm('',$_SERVER["PHP_SELF"],'post');
		print Forms::hidden('action', 'Recount');
		print Forms::submitButton('ѕересчитать строительную состовл€ющую','next','','',' class=submit');
		print Forms::closeForm();*/
		return true;
	}	//Ёто запчасть от первой формы



	/*private function recount()
	{
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "
            			UPDATE ds_ranking
					    SET building_ranking =  
					    (
					    COALESCE((SELECT sum(b.building_score) 
					    FROM  ds_comb c
					   	LEFT JOIN ds_attachedbuilding ab ON (ab.id_comb = c.id_comb)  
                        LEFT JOIN ds_building b ON (b.id_building = ab.id_building)                      
					    where c.id_userinstance = ds_ranking.id_userinstance
					    ), 0))
                    ";

		$res = $common->executeQuery($sql);
		if ( !$res ) {
			$this->errormsg = "ERROR - Failed Recount science value!";
			return false;
		}

		$this->errormsg = "OK";
		return false;
	}*/

	private function exportCsv()
	{
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "
            			SELECT
							artefactname, id_localizedname, artefact_score
						FROM
							ds_artefact
						ORDER BY artefactname
                    ";

		$res = $common->getResultArray("", $sql);
		if(!$res)
		{
			$this->errormsg = "ERROR - Failed get artefact coast!";
			return false;
		}

		$array = "";

		foreach ($res as $key => $value)
		{
			$a['artefactname'] = $value['artefactname'];
			$a['artefact_score'] = number_format($value['artefact_score'], 0, ".", "");

			// Ћокализаци€ названи€
			$sql = "
            		SELECT 
            			l.text 
            		FROM 
            			ds_localizedtextlanguage l
					WHERE
						l.id_localizedtext = ".$value['id_localizedname']."
						AND
						l.id_language = 3
                    ";


			$res2 = $common->getResultArray("", $sql);
			if(!$res2)
			{
				$this->errormsg = "ERROR - Failed get localization arfetact ".$value['artefactname'];
				return false;
			}

			if (empty($res2[0]['text'])) {
				$a['localname'] = $value['artefactname'];
			} else {
				$a['localname'] = $res2[0]['text'];
			}

			$array .= implode(';',$a ).";\r\n";
		}

		$title = Array
		(
			0 => 'artefactname',			
			1 => 'artefact_score',
			2 => 'localname'
		);

		$output = implode(';',$title).";\r\n".$array;

		/* ѕишем в файл */
		$fileName = "../../Localization/Export_CVS/data/ranking_artefact.csv";
		$mode = 'w';                                                                  

		if(!file_exists($fileName))
		{
			$mode = 'w+';
		} else {
			unlink($fileName);
		}

		if(!$fh = fopen($fileName, $mode))
		{
			$this->errormsg="Can't open file... $fileName";
			return false;
		}
		fwrite($fh, $output);
		fclose($fh);

		if(file_exists($fileName))
		{
			ShowCMSMessage('<a href="'.$fileName.'"> Download CSV file </a>', 1);
			return true;
		}

		return false;
	}


	private function importCsv()
	{
		$fh = file($_FILES['file']['tmp_name']);

		$common = DS_Common::CreateForCurentDB(0);

		foreach ($fh as $key => $value)
		{
			if ($key == 0) {continue;}

			$explode = explode(';',$value);

			$artefactName = $explode[0];
			$artefactCoast = $explode[1];

			assert(!empty($artefactName));
			assert($artefactCoast >= 0);
			
			$artefactName = str_replace('"', '', $artefactName);

			$sql = "
            			UPDATE
            				ds_artefact
            			SET
            				artefact_score = $artefactCoast
						WHERE
							artefactname = '$artefactName'
                    ";
//echo "<pre>$sql";

			$res = $common->executeQuery($sql);
			if ( !$res ) {
				$this->errormsg = "ERROR - Failed INSERT artefact value! ($artefactName, $artefactCoast)";
				return false;
			}
			assert(empty($common->errormsg));
		}

		$this->errormsg = "OK";
		return false;
	}
}

$export = new RankingBuilding();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>