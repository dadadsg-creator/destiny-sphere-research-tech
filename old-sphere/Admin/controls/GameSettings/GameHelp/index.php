<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

require_once $_SERVER['DOCUMENT_ROOT']."/fck_editor/fckeditor.php";

class GameHelpEditor
{
	private $action = 'help_list';
	
	private $id_help;
	
	private $lang;
	
	private $help_data;
	
	private $title;
	
	public function __construct($data)
	{
		$this->_getParams($data);
		
		switch($this->action)
		{
			case 'help_list':
				$this->_outHelpList();
				break;
		
			case 'edit_help':
				$this->_outHelpEditor();
				break;
				
			case 'post_edit_help':
				$this->_saveHelpData();
				break;
				
			default:
				break;
		}
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('id_help', $data))
		{
			$this->id_help = (int)$data['id_help'];
			assert($this->id_help > 0);
		}
		
		if(key_exists('lang', $data))
		{
			$this->lang = (int)$data['lang'];
			assert($this->lang > 0);
		}
		
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
		
		if(key_exists('help_data', $data))
		{
			$this->help_data = pg_escape_string($data['help_data']);
		}
		
		if(key_exists('title', $data))
		{
			$this->title = pg_escape_string($data['title']);
		}
	}
	
	private function _saveHelpData()
	{
		if(false !== $this->_checkHelpData($this->id_help, $this->lang))
		{
			$sql = "
				UPDATE
					ds_help
				SET
					help_title = E'".$this->title."',
					help_content = E'".$this->help_data."'
				WHERE
					id_language = ".$this->lang."
					AND
					id_help_section = ".$this->id_help."
				";
		} else {
			$sql = "
				INSERT INTO ds_help
					(id_help_section, id_language, help_title, help_content)
				VALUES
					(".$this->id_help.", ".$this->lang.", E'".$this->title."', E'".$this->help_data."')
				";
		}

		//Логируем
		$shotHelp = substr($this->help_data, 0, 100);
		actionLog( "Edit game help", "Title: '$this->title<br>'$shotHelp...'");

	
		$common = DS_Common::CreateForCurentDB(0);
		$common->executeQuery($sql);
		assert(empty($common->errormsg));	
		
		echo ShowCMSMessage( "DONE", 1 );
		
		echo "<br>";
		
		$this->_outHelpList();
	}
	
	private function _outHelpEditor()
	{
		$data = '&nbsp;';
		
		$title = '';
		
		if($this->_checkHelpData($this->id_help, $this->lang))
		{
			$helpData = $this->_getHelpData();
			$data = stripcslashes($helpData['help_content']);
			$title = $helpData['help_title'];
		}
		
		echo '
			<a href="index.php"><< BACK</a><br><br>
			<form action="index.php" method="POST">
				<input type="hidden" name="action" value="post_edit_help">
				<input type="hidden" name="id_help" value="'.$this->id_help.'">
				<input type="hidden" name="lang" value="'.$this->lang.'">
				<b>Title:</b> <input type="text" size="150" name="title" value="'.$title.'"><br><br>
			';
		
		$oFCKeditor = new FCKeditor('help_data') ;
		$oFCKeditor->BasePath = GAME_HOST."/fck_editor/";
		$oFCKeditor->Height = '500';
		$oFCKeditor->Value = $data;
		$oFCKeditor->Create();
		
		echo '<br><div align=center><input type="submit" value="SAVE" style="width: 150px"></div></form>';
	}
	
	private function _outHelpList()
	{
		$langs = $this->_getLangList();
		
		$helpSections = $this->_getHelpList();
		
		echo '<table width=100% border=1 cellPadding=2>';
		
		echo '
			<tr class=th_2>
				<td width=55%><b>Help section</b></td>
			';
		
		foreach ($langs as $k => $lang)
		{
			echo '<td width=15% align=center><b>'.$lang['language'].'</b></td>';
		}
		
		echo '</tr>';
		
		foreach ($helpSections as $k => $helpSection)
		{
			echo '<tr><td bgcolor="#646E8C"><b>'.$helpSection['comments'].'</b> - '.$helpSection['help_section_name'].'</td>';
			
			$langData = explode($helpSection['help']);

			foreach($langs as $k => $lang)
			{
				$l = $this->_checkHelpData($helpSection['id_help_section'], $lang['id_language']);
				
				$href = 'index.php?lang='.$lang['id_language'].'&id_help='.$helpSection['id_help_section'].'&action=edit_help';
				
				echo '<td align=center bgcolor="#646E8C">';
				if($l !== false)
				{
					echo '<b>'.$l.'</b> b';
					echo '&nbsp;&nbsp;<a href="'.$href.'">edit</a>';
				} else {
					echo 'no';
					echo '&nbsp;&nbsp;<a href="'.$href.'"><b>add</b></a>';
				}
				
				echo '</td>';
			}
			
			echo '</tr>';
		}
		
		echo '</tr></table>';	
	}
	
	private function _getHelpData()
	{
		$common = DS_Common::CreateForCurentDB(0);	
		
		$sql = "
			SELECT
				*
			FROM
				ds_help
			WHERE
				id_help_section = ".$this->id_help."
				AND
				id_language = ".$this->lang."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return $res[0];
		}
		
		return array();
	}
	
	private function _checkHelpData($id_help, $id_lang)
	{
		$common = DS_Common::CreateForCurentDB(0);	
		
		$sql = "
			SELECT
				COALESCE(char_length(help_content), -1) as length
			FROM
				ds_help
			WHERE
				id_help_section = ".$id_help."
				AND
				id_language = ".$id_lang."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if(count($res) == 0 || $res[0]['length'] == '-1')
		{
			return false;
		}
		
		return (int)$res[0]['length'];
	}
	
	private function _getLangList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				*
			FROM
				ds_language
			ORDER BY id_language
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	private function _getHelpList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				hs.*
			FROM
				ds_help_section AS hs
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
}

$h = new GameHelpEditor($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>