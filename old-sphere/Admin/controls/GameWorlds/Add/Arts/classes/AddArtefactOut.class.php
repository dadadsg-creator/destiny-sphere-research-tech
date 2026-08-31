<?php

class AddArtefactOut
{
	static public function ShowIndex(ArtFilter $filter)
	{
		$str = "<form action='' method='GET'>\n";
		$str .= "<input type='hidden' name='action' value='art_list'>\n";
		$str .= "<b>Scope</b>:&nbsp;".$filter->scopeHTML."&nbsp;&nbsp;<b>Bonus</b>:&nbsp;".$filter->bonusHTML."&nbsp;&nbsp;<b>Size</b>:&nbsp;".$filter->sizeHTML."\n&nbsp;&nbsp;&nbsp;";
		$str .= "<input type='submit' value='Select'>";
		$str .= "</form>";
		
		echo $str;
	}
	
	static public function ShowArtList(WorldList $worlds, ArtFilter $filter, ArtList $list, $userName, $comment, $lifeTime = 0)
	{
		self::ShowIndex($filter);
		
		// Выводим скрипт для проверки ввода комментария
		echo '
		 <script type="text/javascript" language="JavaScript"> 
         function check(buttons,message){ 
            if ( buttons == "" ){
            alert(message); 
            return false; 
            }
            return true;
         } 
            </script> ';
		
		
		$str = "<form name='add_art' action='' method='POST' onsubmit=\"javascript: return check(document.add_art.comment.value,'Empty comment!');\">\n";
		$str .= "<input type='hidden' name='action' value='add'>\n";
		
		$str .= "<br><table border=1>";
		foreach ($list->getFullArtList() as $k => $art)
		{
			$str .= "<tr>";
			$str .= "<td class='maintxt'>".$art['artefact_name']."</td>";
			$str .= "<td>";
			$str .= "<input type='hidden' name='art_list[$k][id_artefact]' value='".$art['id_artefact']."'>";
			$str .= "<input type='hidden' name='art_list[$k][artefact_name]' value='".$art['artefact_name']."'>";
			$str .= "<input type='text' size=3 name='art_list[$k][count]' value='0'>";
			$str .= "</td>";
			$str .= "</tr>";
		}
		$str .= "</table><br>";
		
		$str .= "<table border=0>
					<tr>
						<td>
							<b>World:</b>
						</td>
						<td>
							".$worlds->getInHTML()."
						</td>
					</tr>
					<tr>
						<td>
							<b>User</b>:
						</td>
						<td>
							<input type='text' name='user' value='$userName'>
						</td>
					</tr>
					<tr>	
						<td>
							<b>Life time:</b>
						</td>
						<td>
							<input type='text' name='lifeTime' value='$lifeTime' size='10'> month (0 - immortal)
						</td>
					</tr>
					<tr>	
						<td>
							<b>Comment:</b>
						</td>
						<td>
							<input type='text' name='comment' value='$comment' size='50'>
						</td>
					</tr>
				</table>";
		$str .= "<input type='submit' value='ADD'>";
		$str .= "</form>";
		
		echo $str;
	}
}

?>