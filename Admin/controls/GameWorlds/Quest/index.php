<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

class QuestInfoViewer
{
	private $common;
	
	public function __construct($data)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->_getParams($data);	
		
		$this->_outInfo();
	}
	
	private function _getParams()
	{
				
	}
	
	private function _getQuestInfo()
	{
		$sql = "
			SELECT
				*
			FROM
				ds_questinfo
			ORDER BY date ASC
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errorMsg));
		
		return $res;
	}
	
	private function _outInfo()
	{
		echo '<table cellspacing="0" cellpadding="3" border="1">';
		echo '
				<tr class=th_2>
					<td class=maintxt>username</td>
					<td class=maintxt>world</td>
					<td class=maintxt>key_word</td>
					<td class=maintxt>info</td>
					<td class=maintxt>date</td>
				</tr>';
		$info = $this->_getQuestInfo();
		foreach($info as $k => $rec)
		{
			echo '
				<tr>
					<td class=maintxt align="center">'.$rec['username'].'</td>
					<td class=maintxt>'.$rec['world'].'</td>
					<td class=maintxt>'.$rec['key_word'].'</td>
					<td class=maintxt>&nbsp;'.$rec['info'].'&nbsp;</td>
					<td class=maintxt>'.$rec['date'].'</td>
				</tr>';
		}
		echo '</table>';
	}
	
}

$q = new QuestInfoViewer($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>