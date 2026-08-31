<?php

class SU_ValidateDoubleCombsOut
{
	static private $mode = 'screen';
	
	static private $statusColors = array('NO_INSTANCE' => '#00ff00', 'FREEZ' => '#c3c600', 'SAME' => '#ff0000', 'ACTIVE' => '#ee9900', 'DELETE' => '#c3c600');
	
	static public function SetMode($newMode)
	{
		assert($newMode == 'screen' || $newMode == 'console');
		self::$mode = $newMode;
	}
	
	static public function OutIndex()
	{
		if(self::$mode == 'console')
		{
			return;
		}
		
		$str = "<a href='?action=validate'><b>VALIDATE (find users with 2 or more combs)</b></a>";
		
		$str = '<form action="" method="GET"><input type="hidden" name="action" value="validate"><input type=submit value="VALIDATE"></form>';
		$str .= '<hr width=30% align=left>';
		$str .= '<form action="" method="GET"><input type="text" name="username">';
		$str .= '<input type="hidden" name="action" value="show">';
		$str .= '<input type="submit" value="search user"></form>';
		
		echo $str;
	}
	
	static public function OutCheckResult(array $result)
	{
		if(self::$mode == 'screen')
		{
			$str = '<a href="?action=index"><< BACK TO START</a><br><br>';
		} else {
			$str = 'SHEDULED VALIDATION AT '.date('Y-m-d H:i:s');
		}
		
		$str .= '<table border=1>';
		$str .= '<tr><td align=center><b>USERNAME</b></td><td align=center><b>WORLDS</b></td><td>&nbsp;</td></tr>';
		
		foreach ($result as $userID => $data)
		{
			assert(key_exists('WORLDS', $data));
			assert(key_exists('USER', $data));
			assert(is_array($data['WORLDS']));
			assert($data['USER'] instanceof DE_User);

			$str .= '<tr>';
			$str .= '<td class=maintxt>'.$data['USER']->getName().'</td>';
			//$str .= '<td>'.implode(',', array_keys($data['WORLDS'])).'</td>';
			$str .= '<td class=maintxt>&nbsp;';
			foreach ($data['WORLDS'] as $world => $status)
			{
				$str .= $world.': <font color='.self::$statusColors[$status].'>'.$status.'</font>&nbsp;';
			}
			$str .= '</td>';
			if(self::$mode == 'screen')
			{
				$str .= '<td>&nbsp;&nbsp;<a href="?action=show&user_id='.$userID.'">info</a>&nbsp;&nbsp;</td>';
			} else {
				$str .= '<td>&nbsp;</td>';
			}
			
			$str .= '</tr>';
		}
		
		$str .= '</table>';
		
		$str .= '<br>';
		$str .= '<font color='.self::$statusColors['ACTIVE'].'>ACTIVE</font> - active account, no vocation mode, not in delete list<br>';
		$str .= '<font color='.self::$statusColors['SAME'].'>SAME</font> - ACTIVE + main combs on same ring and have same name<br>';
		$str .= '<font color='.self::$statusColors['NO_INSTANCE'].'>NO_INSTANCE</font> - no real instance, only old info in master DB<br>';
		$str .= '<font color='.self::$statusColors['FREEZ'].'>FREEZ</font> - account in vocation mode, all actions disabled<br>';
		$str .= '<font color='.self::$statusColors['DELETE'].'>DELETE</font> - account in delete list, we be deleted at next delete script launch<br>';
		
		if(self::$mode == 'screen')
		{
			echo $str;
		}
		
		return $str;
	}
	
	static public function OutUserInfo(array $data, DE_User $user)
	{
		$fields = array(
			'Creation date' => 'creation_date', 
			'Combs' => 'comb_names',
			'Trades' => 'count_trades',
			'Money trades' => 'count_trades_money', 
			'Last login' => 'last_login', 
			'Delete list' => 'in_delete_list',
			'Vocation' => 'startdate',
			);
			
		$str = '<a href="?action=index"><< BACK TO START</a><br><br>';
		
		$str .= '<span class=mess_info>User: <b>'.$user->getName().'</b></span>&nbsp;&nbsp;';
		$str .= '<form action="" method="GET"><input type="hidden" name="action" value="allow">';
		$str .= '<input type="hidden" name="user_id" value="'.$user->getID().'">';
		$str .= '<input type="text" size=2 name="count" value="'.count($data).'">&nbsp;';
		$str .= '<input type="submit" value="allow">';
		$str .= '</form>';
		
		$str .= '<table border=1>';
		
		$str .= '<tr>';
		$str .= '<td>&nbsp;</td>';
		foreach (array_keys($data) as $w)
		{
			$str .= '<td align=center><b>'.$w.'</b></td>';
		}
		$str .= '</tr>';
		
		foreach ($fields as $n => $f)
		{
			$str .= '<tr>';
			$str .= '<td class=maintxt>&nbsp;<b>'.$n.'</b>&nbsp;</td>';
			foreach ($data as $w => $d)
			{
				if(key_exists($f, $d))
				{
					$v = $d[$f];
				} else {
					$v = '';
				}
				$str .= '<td class=maintxt>&nbsp;'.$v.'&nbsp;</td>';
			}
			$str .= '</tr>';
		}
		
		$str .= '<tr><td>&nbsp;</td>';
		foreach ($data as $w => $d)
		{
			if(key_exists('id_user', $d))
			{
				$str .= '<td align=center><a href="?action=freez&user_id='.$d['id_user'].'&world='.$w.'">freez</a>&nbsp;/&nbsp;<a href="?action=delete&user_id='.$d['id_user'].'&world='.$w.'">delete</a></td>';
			} else {
				$str .= '<td>&nbsp;</td>';
			}
		}
		$str .= '</tr>';
		
		$str .= '</table>';
		
		if(self::$mode == 'screen')
		{
			echo $str;
		} 
	}
}

?>