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

class Leagues {

	private $dbId;

	private $wid;

	private $errormsg;

	private $langs;

	private $common;

	private $action = 'index';

	function Leagues( ) {
		$this->getRequest ( $_POST );

		echo "<H1>Leagues</H1>";

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
		return true;
	}	//Ёто запчасть от первой формы



	private function recount()
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
                        AND ab.underconstruction = 'f'
					    ), 0))
                    ";

		$res = $common->executeQuery($sql);
		if ( !$res ) {
			$this->errormsg = "ERROR - Failed Recount science value!";
			return false;
		}

		$this->errormsg = "OK";
		return false;
	}

	private function exportCsv()
	{
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "
            			SELECT
							*
						FROM
							ds_league
                    ";

		$res = $common->getResultArray("", $sql);
		if(!$res)
		{
			$this->errormsg = "ERROR - Failed get leagues!";
			return false;
		}

		$array = "";

		foreach ($res as $key => $value)
		{
			//$a['buildingname'] = $value['buildingname'];
			//$a['building_score'] = number_format($value['building_score'], 0, ".", "");
			
			$array .= implode(';',$value ).";\r\n";
		}

		$title = Array
		(
			0 => 'id_league',			
			1 => 'league_name',
			2 => 'min_level',
			3 => 'min_life_time',
			4 => 'min_armies_weight',
			5 => 'min_one_army_stars'
		);

		$output = implode(';',$title).";\r\n".$array;

		/* ѕишем в файл */
		//$fileName = "../../Localization/Export_CVS/data/ranking_building.csv";
		//$fileurl = "/ds/utils/files/leagues.csv";
		//$fileName = $_SERVER['DOCUMENT_ROOT'].$fileurl;
		
		$fileurl = "../../Localization/Export_CVS/data/leagues.csv";
		$fileName = $fileurl;
		
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
			ShowCMSMessage('<a href="'.$fileurl.'"> Download CSV file </a>', 1);
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

			$id_league = $explode[0];
			$league_name = str_replace('"', '', $explode[1]);
			$min_level = $explode[2];
			$min_life_time = $explode[3];
			$min_armies_weight = $explode[4];
			$min_one_army_weight = $explode[5];
			
			assert(!empty($league_name));
			assert($min_level >= 0);
			assert($min_life_time >= 0);
			assert($min_armies_weight >= 0);
			assert($min_one_army_weight >= 0);
			
			// —мотрим, есть ли така€ лига
			$sql = "
            			SELECT
            				*
            			FROM
            				ds_league
						WHERE
							id_league = $id_league";
			$league = $common->getResultArray("", $sql);
			
			if (count($league) > 0) {
				
				$sql = "
            			UPDATE
            				ds_league
            			SET
            				league_name = '$league_name',
            				min_level = $min_level,
            				min_life_time = $min_life_time,
            				min_armies_weight = $min_armies_weight,
            				min_one_army_stars = $min_one_army_weight
            				
						WHERE
							id_league = $id_league
                    ";
			} else {
				$sql = "
            			INSERT INTO
            				ds_league
            			(id_league, league_name, min_level, min_life_time, min_armies_weight, min_one_army_stars)
            			VALUES
            			($id_league, '$league_name', $min_level, $min_life_time, $min_armies_weight, $min_one_army_weight)
                    ";
			}

			$res = $common->executeQuery($sql);
			if ( !$res ) {
				$this->errormsg = "ERROR - Failed id_league value! ($id_league) <br><pre>$sql";
				return false;
			}
		}

		$this->errormsg = "OK";
		return false;
	}
}

$export = new Leagues();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>