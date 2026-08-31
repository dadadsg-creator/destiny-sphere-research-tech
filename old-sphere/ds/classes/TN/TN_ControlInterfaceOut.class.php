<?php

class TN_ControlInterfaceOut
{
	static public function OutIndex($msg = '')
	{
		$str = <<<INDEX
			<a href="?action=template"><b>TURNIR TEMPLATE</b></a><span class="maintxt"> - редактирование шаблонов турниров </span><br/>
			<a href="?action=create"><b>CREATE TURNIR</b></a><span class="maintxt"> - создать новый турнир с выбранным шаблоном настроек </span><br/>
			<a href="?action=control"><b>CONTROL TURNIR</b></a><span class="maintxt"> - управление турниром: запуск, пауза, остановка </span><br/>
			<a href="?action=up_ranking"><b>UPDATE RANKING</b></a><span class="maintxt"> - обновление рейтинга, может занять пару минут </span><br/>
			<a href="?action=list"><b>TURNIR LIST</b></a><span class="maintxt"> - список текущих и прошедших турниров </span><br/>
			<a href="?action=clear"><b>CLEAR WORLD</b></a><span class="maintxt"> - чистка мира </span><br/>
			
			<br/><b>$msg</b><br/>
INDEX;
		
		echo $str;
	}
	
	static public function OutTurnirList(array $list, $msg = '')
	{
		$str = '<a href="?"><< назад</a><br/><br/>';
		$str .= "\n";
		
		if(count($list) == 0)
		{
			$str .= 'Турниров не найдено';
			echo $str;
			return;
		}

		$str .= '<table border=1 ><tr class=th_2>';
		$str .= '<td align=center class="mess_info"><b>тип</b></td>';
		$str .= '<td align=center class="mess_info"><b>начало</b></td>';
		$str .= '<td align=center class="mess_info"><b>окончание</b></td>';
		$str .= '<td align=center class="mess_info"><b>коментарий</b></td>';
		$str .= '<td align=center class="mess_info"><b>кол-во участников(инстансов в мире)</b></td>';
		$str .= '<td align=center class="mess_info">&nbsp;</td></tr>';
		
		foreach ($list as $k => $turnir)
		{
			$str .= '<tr class="maintxt">';
			$str .= '<td>'.$turnir['descr'].'</td>';
			$str .= '<td>'.$turnir['start_date'].'</td>';
			$str .= '<td>'.$turnir['end_date'].'</td>';
			$str .= '<td>'.$turnir['comments'].'</td>';
			$str .= '<td>'.$turnir['count_result'].'('.$turnir['count_ui'].')</td>';
			
			if(empty($turnir['start_date']))
			{
				$s = '<a href="?action=delete&id='.$turnir['id_turnir'].'">удалить >></a>';
			} else {
				$s = '<a href="/ds/ranking_turnir.php?id='.$turnir['id_turnir'].'" target="_blank">результаты >></a>';
			}
			
			$str .= '<td><b>'.$s.'</b></td>';
			$str .= '</tr>';
		}
		
		$str .= '</table>';
		
		echo $str;
	}
	
	static public function OutSettings(array $templates, $msg = '')
	{
		echo '<a href="?"><< назад</a><br/><br/><form action="" method="POST">';
		
		$msgLoc = '';
		
		switch ($msg)
		{
			case 'NOT_STARTED_TURNIR_FOUND':
				ShowCMSMessage('Внимание! Уже есть созданный, но не начавшийся турнир.&nbsp;<a href="?action=list" target="_blank" class="dir">список турниров >></a>', 2);
				break;
				
			case 'NOT_ENDED_TURNIR_FOUND':
				ShowCMSMessage("Внимание! Еще есть не закончившийся турнир. &nbsp;<a href=\"?action=list\" target=\"_blank\" class=\"dir\">список турниров >></a>", 2);
				break;
				
			case 'NOT_EMPTY_WORLD':
				ShowCMSMessage("Внимание! В турнирном мире еще есть игроки. &nbsp;<a href=\"?action=clear\" target=\"_blank\" class=\"dir\">удаление игроков >></a>", 2);
				$msgLoc = '';
				break;
		}
		
		$str .= $msgLoc."<br/><br/>";
		
		$str .= '<span class="mess_info">Тип турнира:</span> <select name="template">';
		foreach ($templates as $name => $params)
		{
			$str .= '<option value="'.$name.'">'.$params['descr'];
		}
		$str .= '</select>&nbsp;<br/><br/>';
		$str .= '<span class="mess_info">Коментарий:</span> <textarea name="comment" rows=5 cols=40></textarea><br/><br/>';
		$str .= '<input type="submit" value="создать турнир">';
		$str .= '<input type="hidden" name="action" value="create_new">';
		$str .= '</form>';
		
		echo $str;
	}
	
	static public function OutControlPanel($msg = '')
	{
		$date = date('Y-m-d-H-i-s');
		$date = explode('-', $date);
		
		$str = <<<PANEL
		<a href="?"><< назад </a><br/><br/>
		<b>$msg</b><br>
		<table border=1>
			<tr>
				<td>
					<form action="" method="GET">
						<input type="hidden" name="action" value="cp">
						<input type="hidden" name="cp" value="start">
						<input type="submit" value="start">
					</form>
				</td>
				<td class="maintxt">
					запуск турнирного мира. со всех сот в мире снимается режим отпуска.
				</td>
			</tr>
			<tr>
				<td>
					<form action="" method="GET">
						<input type="hidden" name="action" value="cp">
						<input type="hidden" name="cp" value="pause">
						<input type="submit" value="pause">
				</td>
				<td class="maintxt">
					<input type="text" name="enddate[]" size=3 value="{$date[0]}">&nbsp;:
					<input type="text" name="enddate[]" size=2 value="{$date[1]}">&nbsp;:
					<input type="text" name="enddate[]" size=2 value="{$date[2]}">&nbsp;&nbsp;
					<input type="text" name="enddate[]" size=2 value="{$date[3]}">&nbsp;-
					<input type="text" name="enddate[]" size=2 value="{$date[4]}">&nbsp;-
					<input type="text" name="enddate[]" size=2 value="{$date[5]}">&nbsp;&nbsp;
					</form>
					турнир автоматически продолжится с указанного времени, <span class=dir>отменить паузу нельзя! </span>
				</td>
			</tr>
			<tr>
				<td>
					<form action="" method="GET">
						<input type="hidden" name="action" value="cp">
						<input type="hidden" name="cp" value="stop">
						<input type="submit" value="stop"> 
					</form>
				</td>
				<td class="maintxt">
					завершение турнира, все соты будут заморожены, регистрация закрыта
				</td>
			</tr>
		</table>
PANEL;
		
		echo $str;
	}
	
	
	static public function OutClearWorld()
	{
		$str = <<<OUT
		<a href="?"><< назад </a><br/><br/>
		<span class="mess_info">Удаление всех игроков в мире может занять много времени. <br/>
		Если выплонение скрипта прекратится, подождите несколько минут и запустите еще раз. </span>
		
		<form action="" method="POST">
			<input type="hidden" name="cp" value="clear">
			<input type="hidden" name="action" value="clear">
			<input type="submit" value="ЗАПУСК УДАЛЕНИЯ" >
		</form>
OUT;
		
		echo $str;
	}
	
	
	static public function OutTemplateEdit(array $templates, $error = false)
	{
		$str = <<<OUT
		<a href="?"><< назад </a><br/><br/>
		
		<script type="text/javascript">
				
			function switchSection(name)
			{
				if(document.getElementById(name).style.display != "none")
				{
					document.getElementById(name).style.display = "none";
				} else {
					document.getElementById(name).style.display = "block";
				}	
			}
			
		</script>
OUT;
		if($error)
		{
			$str .= '>>> ERROR <<< <br><br>';
		}
		
		
		foreach ($templates as $name => $params)
		{
			$str .= '<span class="maintxt"><b>'.$params['template_name'].'</b><span><a href="javascript:switchSection(\''.$name.'\')"> (редактировать) </a>';
			$str .= '<a href="javascript: if (confirm(\'Удалить шаблон '.$params['template_name'].' ?\')) document.location.href=\'?action=template&cp=del_template&id_t='.$name.'\'">(удалить)</a><br>';
			$str .= '<div id="'.$name.'" style="display: none;">';
			$str .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="?action=template&cp=edit_m&id_t='.$name.'">Редактирование модифаеров >></a><br/>';
			$str .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="?action=template&cp=edit_b&id_t='.$name.'">Редактирование списка запрещенных зданий >></a><br/>';
			$str .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="?action=template&cp=edit_t&id_t='.$name.'">Выбор стартового шаблона соты >></a><br/><br/>';
			$str .= '</div>';
		}
		
		$str .= <<<OUT
		<br>
		<a href="javascript:switchSection('NEW_T_FORM')">создать новый шаблон >></a><br/>
		
		<form action="" method="POST" id="NEW_T_FORM" style="display: none;">
			<input type="hidden" name="action" value="template">
			<input type="hidden" name="cp" value="add_new">
			<span class="maintxt">Название: </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="name" value=""><br>
			<span class="maintxt">Краткое описание: </span><input type="text" name="descr" value="" size="80"><br><br>
			
			<input type="submit" value="добавить ">
		</form>
OUT;
		
		echo $str;
	}
	
	
	static public function OutTemplateBuildingsEditForm($id, array $allowed, array $disabled)
	{
		assert(is_int($id) && $id > 0);
		
		$str = <<<OUT
		<a href="?action=template"><< назад </a><br/><br/>
		<span class="maintxt">РАЗРЕШЕННЫЕ ЗДАНИЯ: </span>
		<form action="index.php" method="POST">
			<input type="hidden" name="id_t" value="$id">
			<input type="hidden" name="action" value="template">
			<input type="hidden" name="cp" value="do_edit_b">
			<select name='disable'>
OUT;
		foreach ($allowed as $k => $b)
		{
			$str .= "<option value=".$b['id_building'].">".$b['name'];
		}
		
		$str .= <<<OUT
			</select>&nbsp;&nbsp;&nbsp;
			<input type="submit" value="запретить ">
		</form>
		<br><br><br>
		<span class="maintxt">ЗАПРЕЩЕННЫЕ ЗДАНИЯ: </span>
		<form action="index.php" method="POST">
			<input type="hidden" name="id_t" value="$id">
			<input type="hidden" name="action" value="template">
			<input type="hidden" name="cp" value="do_edit_b">	
			<select name='enable'>
OUT;
		foreach ($disabled as $k => $b)
		{
			$str .= "<option value=".$b['id_building'].">".$b['name'];
		}
		
		$str .= '</select>&nbsp;&nbsp;&nbsp;<input type="submit" value="разрешить "></form>';
		
		echo $str;
	}
	
	static public function OutTemplateModifierEdit(array $modifiers)
	{
		$str = <<<OUT
		<a href="?action=template"><< назад </a><br/><br/>
		
		<form action="" method="POST">
			<input type="hidden" name="action" value="template">
			<input type="hidden" name="cp" value="do_edit_m">
			
			<script type="text/javascript">
				
			function switchSection(name)
			{
				if(document.getElementById(name).style.display != "none")
				{
					document.getElementById(name).style.display = "none";
				} else {
					document.getElementById(name).style.display = "block";
				}	
			}
			
			</script>
OUT;
	
		
		$type = '';
				
		foreach ($modifiers as $k => $m)
		{
			if($m['typename'] != $type)
			{
				if($k > 0)
				{
					$str .= '</table><br>';
				}
				
				$str .= '<a href="javascript:switchSection(\''.$m['typename'].'\');"><b>'.$m['typename'].'</b></a><br>'."\n";
				$str .= '<table id="'.$m['typename'].'" style="display:none;">'."\n";
				
				$type = $m['typename'];
			}			
			
			if($m['ttm_value'] != '-')
			{
				$s = '<b>&nbsp;&nbsp;>>>&nbsp;&nbsp;'.$m['modifiername'].'&nbsp;&nbsp;<<<&nbsp;&nbsp;</b>';
			} else {
				$s = ''.$m['modifiername'].'';
			}
			
			$val = $m['value'];
			if(strpos($val, 'e') !== false)
			{
				$val = number_format($val, 10, '.', '');
			}

			$str .= '<tr class="maintxt"><td>'.$s."</td><td><input type='text' align='right' name='m[".$m['modifiername']."]' value='".$val."'></td><td>".$m['description'].'</td></tr>'."\n";	

		}
		
		$str .= '</table>'."\n";
		
		
		$str .= <<<OUT
					
			<br><br><input type="submit" value="применить ">
		</form>
OUT;
		echo $str;
	}
}

?>