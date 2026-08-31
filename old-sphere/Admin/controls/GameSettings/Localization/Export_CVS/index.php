<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

function print_rr ($array) {
	echo "<xmp>";
	print_r ( $array );
	echo "</xmp>";
}


class ExportCVS {

	var $dbId;
	var $wid;
	var $errormsg;
	var $request;
	var $langs;
	var $common;

	function ExportCVS( ) {
		if ( !isset ( $GLOBALS["common"] ) ) {
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

	function showFirstMenu ()
	{
		if ($this->errormsg){
			ShowCMSMessage($this->errormsg, 1);
			return true;
		}
		
		$sql = "SELECT 
					id_language, language
				FROM
					ds_language
				";
		$res = $this->common->getQueryArray($sql);
		if ( !$res ) {
			$this->errormsg = "ERROR - Failed get language list!".$sql;
			return false;
		}
		
		foreach ($res as $key => $value) $langs[$value[id_language]] = $value[language];
		
		print Forms::openForm('',$_SERVER["PHP_SELF"],'post');
		print Forms::hidden('showSecondMenu',1);
		print "
		<table cellSpacing='0' cellPadding='0'>
			<tr>
				<td rowspan =2 bgcolor='444E6C' valign=top><span class=maintxt><b>".local( 'ADMIN_LANGUAGE' )." :&nbsp;&nbsp;</b></span></td>
				<td rowspan =2 bgcolor='444E6C'>".Forms::selectBox($langs,$this->request[langs],'langs[]',count ( $langs ),true)."&nbsp;&nbsp;</td>
				<td bgcolor='444E6C' valign=top><input type='checkbox' name='not_exist'> <span class=maintxt>Export not exist row</span></td>
			</tr>
			<tr>
				<td bgcolor='444E6C' valign=top><input type='checkbox' name='old'> <span class=maintxt>Export old row</span></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>";
			
		print Forms::submitButton(local( 'DS_ACTION_SUBMIT' ),'next','','',' class=submit');
		print Forms::closeForm();

		return true;
	}	//Это запчасть от первой формы


	
	function showSecondMenu ( $str = "" ) 
	{
		if($this->request[showSecondMenu] != 1) 
		{
			return false;
		}

		$this->langs = $this->request[langs];
		
		if (empty($this->langs)) {
			echo "<font color='Red'>Choose language!</font><br>";
			return false;			
		}
		
		$where = '';
		$join = '';
		$select = 'lt.id_localizedtext';
		$or = '';
		
		//Если мы хотим получить устаревшую инфу, то присоединяем русский в любом случае
		if (key_exists('old', $this->request) && !in_array(3, $this->langs)) 
		{
			$join = ' LEFT JOIN ds_localizedtextlanguage ltl3 ON
                    (ltl3.id_localizedtext = lt.id_localizedtext AND ltl3.id_language = 3) ';
		}
		
		//Если мы получаем всю локализацию, то нам не требуется никаких исловий, иначе:
		if (key_exists('old', $this->request) || key_exists('not_exist', $this->request)) 
		{
			$where = ' WHERE ';
		}
		
		foreach ($this->langs as $key => $idLanguage)
		{
			$select .= ",
							ltl".$idLanguage.".text as text".$idLanguage."";
			
			$join .= "
					LEFT JOIN ds_localizedtextlanguage ltl".$idLanguage." ON
                    (ltl".$idLanguage.".id_localizedtext = lt.id_localizedtext AND ltl".$idLanguage.".id_language = ".$idLanguage.") ";
			
			if (key_exists('not_exist', $this->request))
			{
				$where .= $or."
								ltl".$idLanguage.".text IN ('', ' ')
			                    OR
			                    ltl".$idLanguage.".text is null 
			                    ";
				$or = " OR ";
			} 
			
			if (key_exists('old', $this->request)) 
			{
				$where .= 		$or." 
								ltl".$idLanguage.".last_change_date <  ltl3.last_change_date
			                    ";
				$or = " OR ";
			}
			
			$or = " OR ";
		}
	
		$sql .= "
            	SELECT
            	    $select 
            	FROM
                    ds_localizedtext lt 
                $join
            	$where
                    ";
		
		$res = $this->common->getQueryArray($sql);
		if(!$res)
		{
			$this->errormsg = "ERROR - Failed get localization list!".$sql;
			return false;
		}

		foreach ($res as $key => $value) 
		{
			//Проходимся по всем языкам
			foreach ($this->request['langs'] as $key => $idLanguage)
			{
				$a['id_language'] = $idLanguage;
				$a['id_localizedtext'] = $value['id_localizedtext'];
				
				$text = stripslashes($value['text'.$idLanguage]);
				$text = str_replace("\r\n","<br />", $text);
				$a['text'] = $text;
				
				$array .= implode(';',$a ).";\r\n";
			}
		}

		$title = Array
				(
					0 => 'id_language',
					1 => 'id_localizedtext',
					2 => 'text'
				);
			
		$output = implode(';',$title).";\r\n".$array;
		
		/* Пишем в файл */
		//$date = date('dmYHis');
		$fileName = "data/localize.csv";
		$mode = 'w';
		
		if(!file_exists($filename))
		{
			$mode = 'w+';
		} else {
			unlink($fileName);
		}
		
		//$mode = 'w';

		if(!$fh = fopen($fileName, $mode)) 
		{
			$this->errormsg="Can't open file...";
			return false;
		}
		
		if(!chmod($fileName, 0666))
		{
			$this->errormsg="Can't set chmod...";
			return false;
		}
				
		fwrite($fh, $output);
		fclose($fh);
		
		if(file_exists($fileName))
		{
			ShowCMSMessage('<a href="'.$fileName.'"> Download CSV file </a>', 1);
			//$this->ShowUrl ($fileName);
			return true;
		}
		
		return false;
	}
}

$export = new ExportCVS();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>