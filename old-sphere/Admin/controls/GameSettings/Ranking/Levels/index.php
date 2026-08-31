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


class RankingLevels {

	private $dbId;

	private $wid;

	private $errormsg;

	private $langs;

	private $common;
	
	private $levels;
	
	private $liveDays = array();

	private $action = 'index';

	function RankingLevels( ) {
		$this->getRequest ( $_POST );

		echo "<H1>LEVELS</H1>
					<SCRIPT LANGUAGE='JavaScript'>   
				   <!-- 
				   /**
					* Mark/unmark checklist
					* id = ID of parent container, name = name prefix, state = state [true/false]
					*/
					function marklist(id, name, state)
					{
						var parent = document.getElementById(id);
							if (!parent)
							{
								eval('parent = document.' + id);
							}
							
							if (!parent)
							{
								return;
							}
							
							var rb = parent.getElementsByTagName('input');
							
							for (var r = 0; r < rb.length; r++)
							{
								if (rb[r].name.substr(0, name.length) == name)
								{
								rb[r].checked = state;
								}
							}
					}
					//--> 
				 </SCRIPT> 
				 ";

		switch ($this->action) {
			case 'edit':
				$this->edit();
				$this->editLive();
				$this->showFirstMenu();
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
		
		if (key_exists('levels', $request)) {
			$this->levels = $request['levels'];
		}
	
		// Если мы хотим править группу 
		if (!empty($request['live_group']) && key_exists('liveDays', $request)) {
			foreach ($request['liveDays'] as $k => $d)
			{
				$this->liveDays[$k] = (int)$request['live_group'];
			}
		} else {
			// Мы правим все
			$this->liveDays = $request['live'];
		}
	}

	function showFirstMenu ()
	{
		if ($this->errormsg){
			ShowCMSMessage($this->errormsg, 1);
			return true;
		}
		
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "
            			SELECT
							*
						FROM
							ds_ranking_level
						ORDER BY id_level
                    ";

		$res = $common->getResultArray("", $sql);
		if(!$res)
		{
			$this->errormsg = "ERROR - Failed get buildings coast!";
			return false;
		}
		
		echo '
		 <form name="moneylogform" id="moneylogform" action="'.$_SERVER["PHP_SELF"].'" method="post">
		 ';
		print Forms::hidden('action', 'edit');
		print Forms::submitButton('&nbsp;&nbsp;Edit&nbsp;&nbsp;','next','','',' class=submit');
		
		echo '<br><br>
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table width=100% border=0 cellspacing=1 cellpadding=3>
							<tr>
								<th class=th_2>
									Уровень
								</th>
								<th class=th_2>
									От
								</th>
								<th class=th_2>
									До
								</th>
								<th class=th_2>
									Жизнь
								</th>
							</tr>';
		$scoreFrom = 0;
		foreach ($res as $key => $data)
		{
			echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.$data['id_level'].'
								</td>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.$scoreFrom.'
								</td>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									<input name="levels['.$data['id_level'].']" type="text" value="'.$data['score_to'].'">
								</td>
								<td align="left" class=maintxt_med bgcolor=#444E6C>';
			if (key_exists('liveDays', $_REQUEST)) {
				if (key_exists($data['id_level'], $_REQUEST['liveDays'])) {
					$checked = 'checked';
				} else {
					$checked = '';
				}
			} else {
				$checked = 'checked';
			}
			
			echo'
									<input type="text" size="3" name="live['.$data['id_level'].']" value="'.$data['delete_date'].' "> <input type="checkbox" name="liveDays['.$data['id_level'].']" '.$checked.'>
								</td>
							</tr>';
			$lastLevel = $data['id_level'];
			$scoreFrom = $data['score_to'] + 1;
		}

		
		for ($i = 1; $i < 5; $i ++)
		{
			echo '
							<tr>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.($lastLevel+$i).'
								</td>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									'.$scoreFrom.'
								</td>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									<input name="levels['.($lastLevel+$i).']" type="text">
								</td>
								<td align="left" class=maintxt_med bgcolor=#444E6C>
									<input type="text" size="3" name="live['.($lastLevel+$i).']" value="'.$res[$lastLevel-1]['delete_date'].' ">
								</td>
							</tr>';
			$scoreFrom = '&nbsp;';
		}

		echo "
						<tr>
								<td align='left' valign='top' class=maintxt_med bgcolor=#444E6C colspan='3'>
									Выставить время жизни уровней, отмеченных галочками:<br>
									(время жизни - количество дней, в течении которых<br>
									игрок может не заходить на соту и его не удалят)
								</td>
								<td align='left' class=maintxt_med bgcolor=#444E6C>
									<input type='text' name='live_group' size='3'><br>
									<INPUT TYPE='button' class='Submit' onClick=\"marklist('moneylogform','liveDays',true)\" VALUE='&nbsp;&nbsp;Mark All&nbsp;&nbsp;&nbsp;'><br>
									<INPUT TYPE='button' class='Submit' onClick=\"marklist('moneylogform','liveDays',false)\" VALUE='UnMark All'><br>
								</td>
							</tr>
						</table>
					</td>
				</tr>
		</table><br>
		";
				
		print Forms::submitButton('&nbsp;&nbsp;Edit&nbsp;&nbsp;','next','','',' class=submit');
		print Forms::closeForm();
	}

	private function edit()
	{
		$common = DS_Common::CreateForCurentDB(0);
		$common->startTrans();
		
		$scoreFrom = 0;
		foreach ($this->levels as $level => $scoreTo)
		{
			if (empty($scoreTo)) {
				$common->executeQuery('COMMIT');
				ShowCMSMessage('OK', 1);
				return;
			}
			
			if ($scoreFrom >= $scoreTo) {
				ShowCMSMessage('Error data! Level '.$level, 2);
				return;
			}
			
			$sql = "
            			SELECT 
            				* 
            			FROM 
            				ds_ranking_level
					    WHERE
					    	id_level = $level";
			$res = $common->getResultArray("", $sql);
			assert(empty($common->errormsg));
			
			if (empty($res)) {
				$sql = "
            			INSERT INTO
            				ds_ranking_level
            			(id_level, score_from, score_to)
            			VALUES
            			($level, $scoreFrom, $scoreTo)";	
			} else {
				$sql = "
            			UPDATE ds_ranking_level
					    SET 
						    score_from = $scoreFrom,
						    score_to =  $scoreTo
					    WHERE
					    	id_level = $level";
			}
			
			
			$res = $common->executeQuery($sql);
			if ( !$res ) {
				$common->executeQuery('ROLLBACK');
				ShowCMSMessage('Error', 1);
				return;
			}

			$scoreFrom = $scoreTo+1;
		}
		
		$common->executeQuery('COMMIT');
		ShowCMSMessage('OK', 1);
		return;
	}
	
	private function editLive()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		foreach ($this->liveDays as $level => $days)
		{
			if (empty($days)) {
				continue;
			}
			
			$sql = "
            			UPDATE ds_ranking_level
					    SET 
						    delete_date = $days
					    WHERE
					    	id_level = $level";
		
			$res = $common->executeQuery($sql);
			if ( !$res ) {
				$common->executeQuery('ROLLBACK');
				ShowCMSMessage('Error', 1);
				return;
			}
		}
		ShowCMSMessage('OK', 1);
		return;
	}
}

$export = new RankingLevels();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>