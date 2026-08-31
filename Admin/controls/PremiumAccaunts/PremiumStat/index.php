<? $CVS_Version = "$Revision$ ; $Date$";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

$_SESSION["dbId"] = 0;

$ac = new PremiumStat();
echo $ac->main($_REQUEST);

class PremiumStat
{
	private $common;

	private $userName = null;

	private $action;

	public function __construct()
	{
		$this->common = DS_Common::CreateForCurentDB();
	}

	public function main($data)
	{
		$this->_getParams($data);

		$out = '';

		$this->common->setDbID($this->DBID);

		switch ($this->action)
		{
			case 'index':
				$out .= $this->_viewIndex();
				break;
			case 'showStat':
				$out .= $this->_viewIndex();
				$out .= $this->_showStat();
				break;
			default:
				$out .= $this->_viewIndex();
		}
		return $out;
	}

	private function _viewIndex()
	{
		$out = "<h2><font color=#F8F8FF>Premium Stat</h2>";
		$out .= "<form method=POST>";
		$out .= "Show count users with premium<br><br>";

		/*$out .= "<table border=0>";
		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>User name:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="24" name="userName" value="'.$this->userName.'">';
		$out .= '</td></tr>';
		$out .= '</table>';*/

		$out .= '<input type="hidden" name="action" value="showStat">';
		$out .= '<input type="submit" class=Submit value="&nbsp;&nbsp;&nbsp;View&nbsp;&nbsp;&nbsp;">';
		$out .= "</form>";

		$out .= '</font>';

		return $out;
	}

	private function _showStat()
	{
		// Выбираем все миры
		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$this->common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $this->common->getResultArray('', $sql, '', '', '');
			assert(empty($this->common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbid'] = $dbid;
				$worldArr[] = $v;
			}
		}
/*echo "<pre>";
print_r($out);
exit;	*/	
		// Проходимся по всем мирам
		foreach ($worldArr as $key => $worldData)
		{
			$this->common->setDbId((int)$worldData['dbid']);
			
			// Выбираем количество премиум-игроков
			$sql = "
				SELECT 
					count(*) as count
				FROM
					ds_userinstance ui 
				LEFT JOIN ds_userprofidata upd ON (upd.id_user = ui.id_user)
				WHERE
                	upd.active = TRUE
                	 AND
                	upd.profi = TRUE
                	 AND
                	upd.enddate > now()
                	 AND
                	ui.id_world = ".$worldData['id_world'];
			
			$res = $this->common->getResultArray('', $sql, '', '', '');
			assert(empty($this->common->errormsg));
			
			$premStat[$worldData['worldname']] = $res[0]['count'];
		}
		 
/*echo "<pre>";
print_r($premStat);
exit;*/		
		if (count($premStat)==0)
		{
			$out .= "<h2><font color=red>No statistic!</font></h2>";
			return $out;
		}

		$out = "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>
							<tr>
								<th class=th_3 >
									&nbsp;&nbsp;&nbsp;World&nbsp;&nbsp;&nbsp;
								</th>
								<th class=th_3 >
									&nbsp;&nbsp;&nbsp;Users&nbsp;&nbsp;&nbsp;
								</th>
							</tr>";

		foreach ($premStat as $worldName => $count)
		{
			$out .= '
							<tr>
								<td bgcolor="#444E6C"><font color="#F8F8FF">
									'.$worldName.'
								</td>
								<td align="right" bgcolor="#444E6C">
									<font color="#F8F8FF">&nbsp;'.$count.'</font>
								</td>
							</tr>';
		}

		$out .= "		</table>
					</td>
				</tr>
			</table>";
		return $out;
	}


	private function _getParams($data)
	{
		/*if(key_exists('userName', $data))
		{
			$this->userName = $data['userName'];
			assert(!empty($this->userName));
		}*/

		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>