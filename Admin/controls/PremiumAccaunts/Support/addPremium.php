<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";


class addPremium
{
	private $user;
	
	private $action = 'no';
	
	private $daysDelta;
	
	private $comments = '';
	
	public function __construct($data)
	{
		$this->_getParams($data);
		
		switch ($this->action) {
			case 'index':
				$this->_showIndex();
				break;
		
			case 'change':
				$this->_changePremiumDays();
				break;	
				
			case 'log':
				$this->_showChangeLog();
				break;
				
			default:
				echo ShowCMSMessage("NO ACTION", 2);
				break;
		}
	}
	
	private function _getParams($data)
	{
		if(key_exists('id_user', $data))
		{
			$uid = (int)$data['id_user'];
			assert($uid > 0);
			$this->user = DE_User::CreateFromID($uid);
			$this->action = 'index';
		}
		
		if(key_exists('days', $data))
		{
			$this->daysDelta = (int)$data['days'];
			if($this->daysDelta != 0)
			{
				$this->action = 'change';
			}
		}
		
		if(key_exists('comments', $data))
		{
			$this->comments = pg_escape_string($data['comments']);
		}
		
		if(key_exists('log', $data))
		{
			$this->action = 'log';
		}
	}
	
	private function _showIndex()
	{
		echo '
<script language="JavaScript" type="text/javascript">
			function formSubmit()
			{
				if(window.document.forms[\'premForm\'].comments.value != \'\')
				{
					window.document.forms[\'premForm\'].submit();
				} else {
					document.getElementById(\'warningTag\').innerHTML = \'<table width=100% border=0 cellpadding=0 cellspacing=0><tr><td width=5 height=5><img src=/Admin/img/message_wnd/b_t_l.gif border=0></td><td width=100% height=5 background=/Admin/img/message_wnd/b_t.gif></td><td width=5 height=5><img src=/Admin/img/message_wnd/b_t_r.gif border=0></td></tr><tr><td width=5 height=100% background=/Admin/img/message_wnd/b_l.gif></td><td width=100% height=100% bgcolor=#2D354E><table width=100% border=0 cellpadding=0 cellspacing=0><tr><td><img src=/Admin/img/message_wnd/error_ico.gif border=0></td><td>&nbsp;&nbsp;&nbsp;</td><td width=100%><span class=mess_error>Enter comments</span></td></tr></table></td><td width=5 height=100% background=/Admin/img/message_wnd/b_r.gif></td></tr><tr><td width=5 height=5><img src=/Admin/img/message_wnd/b_b_l.gif border=0></td><td width=100% height=5 background=/Admin/img/message_wnd/b_b.gif></td><td width=5 height=5><img src=/Admin/img/message_wnd/b_b_r.gif border=0></td></tr></table>\';
				}
			}
		</script>		
		
		<form action="" method="POST" name="premForm">
			<input type="hidden" name="id_user" value="'.$this->user->GetID().'">
			'.local( 'DS_USER_DATE_DURATION_DAYS' ).': <input type="text" name="days" lass=input_small size="4" maxlength="5"><BR>
			Комментарий: <br>
			<textarea name=comments rows=4 cols=60></textarea><br>
			<input type="button" value="изменить" class="submit" onclick="formSubmit()">
		</form>
		<div id="warningTag" name="warningTag">&nbsp;</div>
		
		<div align=left><a href="javascript:window.close()">закрыть окно</a></div>
		';
	}
	
	private function _changePremiumDays()
	{
		global $UserManager;
		
		$login = $UserManager->User['Login'];
		
		if(PU_ExecChangePremiumDays::Exec($this->user, $this->daysDelta, $login, $this->comments))
		{
			echo ShowCMSMessage("OK", 1);
		} else {
			echo ShowCMSMessage("ERROR", 2);
		}
		
		$userName = $this->user->getName();
		actionLog("Change premium for user $userName on  $this->daysDelta days", $this->comments);

		
		$params = '';
		
		if(isset($_REQUEST['search']))
		{
			$params .= '&search='.$_REQUEST['search'];
		}
		
		if(isset($_REQUEST['find_username']))
		{
			$params .= '&find_username='.$_REQUEST['find_username'];
		}
		
		if(isset($_REQUEST['find_surname']))
		{
			$params .= '&find_surname='.$_REQUEST['find_surname'];
		}
		
		if(isset($_REQUEST['find_id_payment']))
		{
			$params .= '&find_id_payment='.$_REQUEST['find_id_payment'];
		}
		
		if(isset($_REQUEST['find_id_user']))
		{
			$params .= '&find_id_user='.$_REQUEST['find_id_user'];
		}
		
		if(isset($_REQUEST['find_email']))
		{
			$params .= '&find_email='.$_REQUEST['find_email'];
		}
		
		if(isset($_REQUEST['address']))
		{
			$params .= '&address='.$_REQUEST['address'];
		}
		
		if(isset($_REQUEST['mode']))
		{
			$params .= '&mode='.$_REQUEST['mode'];
		}
		
		if(isset($_REQUEST['sort']))
		{
			$params .= '&sort='.$_REQUEST['sort'];
		}
		
		if(isset($_REQUEST['offset']))
		{
			$offset = (int)$_REQUEST['offset'];
		} else {
			$offset = 0;
		}
		
		echo '
		<script language="javascript">
			topWin = window.opener;
			if(topWin != null)
			{
				window.opener.location = \'index.php?offset='.$offset.$params.'\';
			}
		</script>
		<br><div align=left><a href="javascript:window.close()">закрыть окно</a></div>';
	}
}

$p = new addPremium($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>