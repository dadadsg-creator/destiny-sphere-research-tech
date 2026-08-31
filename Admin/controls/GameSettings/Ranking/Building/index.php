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

		echo "<H1>Ranking Building</H1>";

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

			case 'Recount':
				if (!$this->recount()) {
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
		print Forms::submitButton('Экспортировать в CVS','next','','',' class=submit');
		print Forms::closeForm();

		echo "<br><br>";

		print Forms::openForm('',$_SERVER['PHP_SELF'],'post','',true);
		print Forms::hidden('action', 'ImportCsv');
		print Forms::fileField('file');
		echo "<br><br>";
		print Forms::submitButton('Импортировать из CVS','next','','',' class=submit');
		print Forms::closeForm();


		echo "<br><br>";

		print Forms::openForm('',$_SERVER["PHP_SELF"],'post');
		print Forms::hidden('action', 'Recount');
		print Forms::submitButton('Пересчитать строительную состовляющую','next','','',' class=submit');
		print Forms::closeForm();
		return true;
	}	//Это запчасть от первой формы



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
							ds_building
						WHERE
							id_building != 0
                    ";

		$res = $common->getResultArray("", $sql);
		if(!$res)
		{
			$this->errormsg = "ERROR - Failed get buildings coast!";
			return false;
		}

		$array = "";

		foreach ($res as $key => $value)
		{
			$a['buildingname'] = $value['buildingname'];
			$a['building_score'] = number_format($value['building_score'], 0, ".", "");

			// Локализация названия
			$sql = "
            		SELECT 
            			l.text 
            		FROM 
            			ds_buildingcanbuildbyrace br
					LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = br.id_localizedname AND l.id_language = 3)
					WHERE
						br.id_building = ".$value['id_building']."
                    ";


			$res2 = $common->getResultArray("", $sql);
			if(!$res2)
			{
				$this->errormsg = "ERROR - Failed get localization building ".$value['buildingname'];
				return false;
			}

			$local = "";
			$comma = "";
			foreach ($res2 as $key2 => $data2)
			{
				// Если название не одинаковое зля всех трех рас
 				if ($local != $data2['text']) {
					$local .= $comma.$data2['text'];
					$comma = ", ";
				}
			}
			
			$a['localname'] = $local;

			$array .= implode(';',$a ).";\r\n";
		}

		$title = Array
		(
			0 => 'buildingname',			
			1 => 'building_score',
			2 => 'localname'
		);

		$output = implode(';',$title).";\r\n".$array;

		/* Пишем в файл */
		$fileName = "../../Localization/Export_CVS/data/ranking_building.csv";
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

			$buildingName = $explode[0];
			$buildingCoast = $explode[1];

			assert(!empty($buildingName));
			assert($buildingCoast >= 0);
			
			$buildingName = str_replace('"', '', $buildingName);

			$sql = "
            			UPDATE
            				ds_building
            			SET
            				building_score = $buildingCoast
						WHERE
							buildingname = '$buildingName'
                    ";

			$res = $common->executeQuery($sql);
			if ( !$res ) {
				$this->errormsg = "ERROR - Failed INSERT building value! ($buildingName, $buildingCoast)";
				return false;
			}
		}

		$this->errormsg = "OK";
		return false;
	}
}

$export = new RankingBuilding();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>