<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once "../classes/MoneyLogSearch.class.php";

$_SESSION['dbId'] = 0;

class EnterMoney
{
	static private $userName = '';

	static private $sum;

	static private $credit;

	static private $comment = '';

	static private $action = 'index';

	static public function Exec(array $data)
	{
		self::_showIndex();
		if (self::_getParams($data))
		{
			switch(self::$action)
			{
				case 'add_credit':
					self::_addCredits();
					self::_showUserData();
					break;
				case 'user_data':
					self::_showUserForm();
					self::_showUserData();
					break;
			}
		} else {
			if (self::$action != 'index') {
				self::_showUserForm();
				self::_showUserData();
			}
		}
	}

	static private function _addCredits()
	{
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName(self::$userName), $world);
		$summ = new MU_EntityMoneySumm(self::$sum, self::$credit);
		$actionType = DE_MoneyActionType::CreateFromName('ENTER_MONEY');

		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);

		$additionalID = 777;
		$gateKey = GB_RandomPassword::Generate(5);

		$operation->setAdditionalID($additionalID);
		$operation->setGateKey($gateKey);

		MU_ControlExecOperations::ExecOperation($operation);

		$str = self::$sum." ".self::$credit." added!";
		ShowCMSMessage($str, 1);
		

		SU_Locker::UnlockAllInAllObjects();

		actionLog('Enter money for user '.self::$userName.'<br>Comment: '.self::$comment, 'Summ: '.self::$sum.' '.self::$credit);
	}

	static private function _showIndex()
	{
		echo "<h1>Enter Money to User</h1>
		<form action='' method='get'>
			<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
			<tr><td bgcolor='444E6C' class=th_2><span class=maintxt><b>".local( 'DS_USER_USERNAME' )." : </b></span></td></tr>
			<tr><td bgcolor='444E6C' class=maintxt align=center>";
		echo Forms::textField($_GET['user'], 'user');
		echo "</td></tr>
			<tr>
				<td bgcolor='444E6C' class=maintxt>";
		echo Forms::submitButton(local( 'DS_ACTION_SEARCH' ),'next','','',' class=submit');
		echo "</td></tr></table></td></tr></table>
		<input type='hidden' name='action' value='user_data'>
		</form>";
}

static private function _showUserData()
	{
		$_SESSION['user'] = self::$userName;
		
		$currentAccount = MoneyLogSearch::getCurrentAccount();
		
		if (!empty($currentAccount))
		{
			$out = "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                                <tr>
                                        <td>
                                                <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";
			$out .= "<tr><th class=th_3 colspan=3>";
			$out .= "Current state";
			$out .= "</th></tr>";

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
			$out .= number_format($currentAccount['USER']['GC'], 0, ',', ' ');
			$Sum['GC'] = $currentAccount['USER']['GC'];
			$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= number_format($currentAccount['USER']['SC'], 0, ',', ' ');
			$Sum['SC'] = $currentAccount['USER']['SC'];
			$out .= "</font></td>";
			$out .= "</tr>";

			foreach ($currentAccount['USER_INSTANCE'] as $worldName => $d)
			{
				$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
				$out .= $worldName;
				$out .= "</td><td bgcolor='#444E6C'><font color=#F8F8FF>";
				$out .= number_format($d['GC'], null, null, ' ');
				$Sum['GC'] += $d['GC'];
				$out .= "</font></td>";
				$out .= "<td bgcolor='#444E6C'><font color=#F8F8FF>";
				$out .= number_format($d['SC'], null, null, ' ');
				$Sum['SC'] += $d['SC'];
				$out .= "</font></td></tr>";
			}

			$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= "<b>Sum</b>";
			$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= "<b>".number_format($Sum['GC'], null, null, ' ')."</b>";
			$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= "<b>".number_format($Sum['SC'], null, null, ' ')."</b>";
			$out .= "</font></td>";
			$out .= "</tr>";
			$out .= "</table>
                                        </td>
                                </tr>
                        </table>";

			$out .= "<br>";
		}
		echo $out;
}

static private function _showUserForm()
	{
		$userName = self::$userName;
		$comment = self::$comment;
		$sum = self::$sum;		

		$out = <<<OUT
		
			 <script type="text/javascript" language="JavaScript"> 
	         function check(buttons,message){ 
	            if ( buttons == "" ){
	            alert(message); 
	            return false; 
	            }
	            return true;
	         } 
            </script>
            
			<form name='add_money' action="" method="POST" onsubmit="javascript: return check(document.add_money.comment.value,'Empty comment!');">
			<table>
			<tr>
				<td class='maintxt'>
					<b>Comment:</b>
				</td>
				<td>
					<input type="text" name="comment" size="25" value="$comment">
				</td>
			</tr>
			<tr>
				<td class='maintxt'>
					<b>Summ:</b>
				</td>
				<td>
					<input type="text" name="sum" size="25" value="$sum">
				</td>
			</tr>
			<tr>
				<td class='maintxt'>
					<b>Credit:</b>
				</td>
				<td>
					<select name="credit">
						<option value="SC">SILVER
						<option value="GC">GOLD
					</select>
				</td>
			</tr>
			</table>
			<input type='hidden' name='user' value="$userName">
			<input type='hidden' name='action' value='add_credit'>
			<input type="submit" name="submit" class="submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;">
			</form>
OUT;
		echo $out;
}

static private function _getParams(array $data)
{
	if(key_exists('user', $data))
	{
		self::$userName = trim($data['user']);

		if(!VD_UserName::Validate(self::$userName) || (int)VD_UserName::CheckIfExists(self::$userName, DS_Common::CreateForCurentDB(0)) == 0)
		{
			ShowCMSMessage('<b>WRONG USER!!</b>', 2);
			return false;
		}
	}
		
	
	if(key_exists('action', $data))
	{				
		self::$action = $data['action'];
	}

	if(key_exists('sum', $data) && key_exists('credit', $data))
	{
		self::$comment = pg_escape_string(trim($data['comment']));
		self::$sum = (int)$data['sum'];
		self::$credit = $data['credit'];

		if(self::$sum <= 0 || self::$sum > 1000000)
		{
			ShowCMSMessage('<b>WRONG SUMM!!</b>', 2);
			return false;
		}

		if(self::$credit != 'SC' && self::$credit != 'GC')
		{
			ShowCMSMessage('<b>WRONG CREDIT!!</b>', 2);
			return false;
		}

		if (empty($data['comment'])) {
			ShowCMSMessage('<b>WRONG COMMENT!!</b>', 2);
			return false;
		}
	}
	return true;
}
}

EnterMoney::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>