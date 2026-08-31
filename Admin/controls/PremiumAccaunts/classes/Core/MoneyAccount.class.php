<?php $CVS_Version = "$Revision$ ; $Date$";

class MoneyAccount
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
			case 'viewAccount':
				$out .= $this->_viewIndex();
				$out .= $this->_viewAccount();
				break;
			default:
				$out .= $this->_viewIndex();
		}
		return $out;
	}

	private function _viewIndex()
	{
		$out = "<h2><font color=#F8F8FF>Money Account</h2>";
		
		$out .= '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=MoneyAccounts><font size=3><b>HELP</b></font></a></p>';

		$out .= "<form method=POST>";

		$out .= "<table border=0>";

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>User name:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="24" name="userName" value="'.$this->userName.'">';
		$out .= '</td></tr>';

		$out .= '</table>';

		$out .= '<input type="hidden" name="action" value="viewAccount">';
		$out .= '<input type="submit" class=Submit value="&nbsp;&nbsp;&nbsp;View&nbsp;&nbsp;&nbsp;">';
		$out .= "</form>";

		$out .= '</font>';

		return $out;
	}

	private function _viewAccount()
	{
		assert(!empty($this->userName));

		$this->common->setDbID($this->DBID);
		
		//Проверяем, есть ли пользователь с введенным ником
		$sql = "SELECT
				 u.id_user
				FROM ds_user as u
				WHERE
				 u.username = '$this->userName'";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if (count($res)==0)
			{
				$out .= "<h2><font color=red>User <b>$this->userName</b> not exist</font></h2>";
				return $out;
			}

		//$file = $this->fileLocation.$this->fileName;
		$IDuser = (int)$res[0]['id_user'];

		$world = DE_WorldFromCollection::CreateFromID('UC');
		
		$user = new DE_User($IDuser);
		$member = new MU_EntityOperationMember($user, $world);
		$data['USER'] = MU_SearchOperations::GetAccountData($member);
		
		//$common = DS_Common::CreateForCurentDB();
		GE_SearchStart::SetCommon($this->common);		
		$userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($IDuser);
		
		foreach($userWorlds as $k => $instance) 
		{
			foreach($GLOBALS['dbWorldAccess'] as $k => $db)
			{
				if($k['server'] == $instance['dbname'])
				{
					$dbId = $k;
					break;
				}
			}
			
			$world = DE_WorldFromCollection::CreateFromID($instance['world_identifier']);
			$member = new MU_EntityOperationMember($user, $world);
			$account = MU_SearchOperations::GetAccountData($member);
			
			if(count($account) > 0)
			{
				$data['USER_INSTANCE'][$instance['worldname']] = $account;
				$data['USER_INSTANCE'][$instance['worldname']]['instance'] = $dbId.'_'.$instance['world_identifier'].'_'.$instance['id_userinstance'];
			}
		}
		
		if(count($data['USER']))
		{
			$account['ID_ACCOUNT'] = $data['USER']['ID_ACCOUNT'];
			$account['id_user'] = $user->getID();
			$account['world'] = '0_UC';
			UC_ExecAccountStat::GetCommon();
			UC_ExecAccountStat::_getAccountOperations($account, 5);
		
			$data['ACCOUNT_OPS'] = UC_ExecAccountStat::$finalStat;
		} else {
			$data['ACCOUNT_OPS'] = array();
		}
		
		$data['GC_COURSE'] = constant('GC_COURSE');
		$data['USER_NAME'] = $user->getName();
		$data['USER_EMAIL'] = $user->getEmail();
		
		$kabGS = $data['USER']['GC'];
		$kabSC = $data['USER']['SC'];
		//$data = MU_SearchOperations::GetAccountData($member);

		$out = "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";
		$out .= "<tr><th class=th_3 >";
		$out .= "&nbsp;&nbsp;&nbsp;World&nbsp;&nbsp;&nbsp;";
		$out .= "</th><th class=th_3 >";
		$out .= "&nbsp;&nbsp;&nbsp;Gold credit&nbsp;&nbsp;&nbsp;";
		$out .= "</th>";
		$out .= "</th><th class=th_3 >";
		$out .= "&nbsp;&nbsp;&nbsp;Silver credit&nbsp;&nbsp;&nbsp;";
		$out .= "</th>";
		$out .= "</tr>";
		
		$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= "Kabinet";
		$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= number_format($kabGS);
		$Sum['GC'] = $kabGS;
		$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= number_format($kabSC);
		$Sum['SC'] = $kabSC;
		$out .= "</font></td>";
		$out .= "</tr>";

		foreach ($data['USER_INSTANCE'] as $worldName => $d)
		{
		$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= $worldName;
		$out .= "</td><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= number_format($d['GC']);
		$Sum['GC'] += $d['GC'];
		$out .= "</font></td>";
		$out .= "<td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= number_format($d['SC']);
		$Sum['SC'] += $d['SC'];
		$out .= "</font></td></tr>";
		}

		$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= "<b>Sum</b>";
		$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= "<b>".number_format($Sum['GC'])."</b>";
		$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= "<b>".number_format($Sum['SC'])."</b>";
		$out .= "</font></td>";
		$out .= "</tr>";
		$out .= "</table>
					</td>
				</tr>
			</table>";
		
		$out .= "<br>";
		$out .= "<br>";
		
		if(!empty($data['ACCOUNT_OPS']))
		{
		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";
		$out .= "<tr><th class=th_3 >";
		$out .= "&nbsp;&nbsp;&nbsp;Date&nbsp;&nbsp;&nbsp;";
		$out .= "</th><th class=th_3 >";
		$out .= "&nbsp;&nbsp;&nbsp;Action&nbsp;&nbsp;&nbsp;";
		$out .= "</th>";
		$out .= "</th><th class=th_3 >";
		$out .= "&nbsp;&nbsp;&nbsp;Info&nbsp;&nbsp;&nbsp;";
		$out .= "</th>";
		$out .= "</tr>";

		foreach ($data['ACCOUNT_OPS'] as $key => $d)
		{
		$out .= "<tr><td bgcolor='#444E6C'>";
		$out .= "<font color=#F8F8FF>".$d['DATE']."</font>";
		$out .= "</td><td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= $d['ACTION'];
		$out .= "</font></td>";
		$out .= "<td bgcolor='#444E6C'><font color=#F8F8FF>";
		$out .= $d['INFO'];
		$out .= "</font></td></tr>";
		}
		$out .= "</table>
					</td>
				</tr>
			</table>";
		}
		return $out;
	}


	private function _getParams($data)
	{
		if(key_exists('userName', $data))
		{
			$this->userName = $data['userName'];
			assert(!empty($this->userName));
		}

		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
	}
}

?>