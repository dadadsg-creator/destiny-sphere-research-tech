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


class RankingScience {

	private $dbId;

	private $wid;

	private $errormsg;

	private $langs;

	private $common;

	private $action = 'index';

	function RankingScience( ) {
		$this->getRequest ( $_POST );
		
		echo "<H1>Ranking Science</H1>";

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
		print Forms::submitButton('Ёкспортировать в CVS','next','','',' class=submit');
		print Forms::closeForm();

		echo "<br><br>";

		print Forms::openForm('',$_SERVER['PHP_SELF'],'post','',true);
		print Forms::hidden('action', 'ImportCsv');
		print Forms::fileField('file');
		echo "<br><br>";
		print Forms::submitButton('»мпортировать из CVS','next','','',' class=submit');
		print Forms::closeForm();


		echo "<br><br>";

		print Forms::openForm('',$_SERVER["PHP_SELF"],'post');
		print Forms::hidden('action', 'Recount');
		print Forms::submitButton('ѕересчитать научную состовл€ющую','next','','',' class=submit');
		print Forms::closeForm();
		return true;
	}	//Ёто запчасть от первой формы



	private function recount()
	{
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "
            			UPDATE ds_ranking
					    SET science_ranking =  
					    (
					    COALESCE((SELECT sum(s.science_score)
					    FROM  ds_userhasscience uhs
					    LEFT JOIN ds_science s ON (s.id_science = uhs.id_science)
					    where uhs.id_userinstance = ds_ranking.id_userinstance
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
							ds_science
                    ";

		$res = $common->getResultArray("", $sql);
		if(!$res)
		{
			$this->errormsg = "ERROR - Failed get science coast!";
			return false;
		}

		$array = "";

		foreach ($res as $key => $value)
		{
			$a['sciencename'] = $value['sciencename'];
			$a['level'] = $value['level'];
			$a['researchcosts'] = number_format($value['science_score'], 0, ".", "");

			$array .= implode(';',$a ).";\r\n";
		}

		$title = Array
		(
			0 => 'sciencename',
			1 => 'level',
			2 => 'researchcosts'
		);

		$output = implode(';',$title).";\r\n".$array;

		/* ѕишем в файл */
		$fileName = "../../Localization/Export_CVS/data/ranking_science.csv";
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

			$scienceName = $explode[0];
			$scienceCoast = $explode[2];

			assert(!empty($scienceName));
			assert($scienceCoast >= 0);
			
			$scienceName = str_replace('"', '', $scienceName);

			$sql = "
            			UPDATE
            				ds_science
            			SET
            				science_score = $scienceCoast
						WHERE
							sciencename = '$scienceName'
                    ";

			$res = $common->executeQuery($sql);
			if ( !$res ) {
				$this->errormsg = "ERROR - Failed INSERT science value! ($scienceName, $scienceCoast)";
				return false;
			}
		}

		$this->errormsg = "OK";
		return false;
	}
}

$export = new RankingScience();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>