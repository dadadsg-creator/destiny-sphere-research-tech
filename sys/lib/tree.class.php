<?

// ==================================================================================
class tree {
	// ==================================================================================
	var $tree;   // all node
	var $index;  // the order of an arrangement in a tree
	var $prefix; // graphic part of a tree

	// pictures
	var $pict_fork;
	var $pict_space;
	var $pict_line;
	var $pict_corner;
	var $pict_openopenfolder;
	var $pict_doc;


	// constructor
	function tree($id,$caption,$ref="") {
		$this->tree = array(
		$id => array("parent"=>0,"id"=>$id, "level"=>0,"caption"=>$caption,"ref"=>$ref)
		);
		$this->index[0] = $id;
		$this->pict_fork   = "<td><img align=top src='/Admin/img/tree/fork.gif' width=16 height=22 border=0></td>";
		$this->pict_fork2   = "<td><img align=top src='/Admin/img/tree/fork2.gif' width=16 height=60 border=0></td>";
		$this->pict_space  = "<td><img align=top src='/Admin/img/tree/space.gif' width=16 height=22 border=0></td>";
		$this->pict_line   = "<td><img align=top src='/Admin/img/tree/line.gif' width=16 height=22 border=0></td>";
		$this->pict_corner = "<td><img align=top src='/Admin/img/tree/corner.gif' width=16 height=22 border=0></td>";
		$this->pict_corner2 = "<td><img align=top src='/Admin/img/tree/corner2.gif' width=16 height=60 border=0></td>";
		$this->pict_openfolder = "<td><img align=top src='/Admin/img/tree/openfolder.gif' width=23 height=22 border=0></td>";
		$this->pict_openfolder2 = "<td><img align=middle src='/Admin/img/tree/openfolder2.gif' width=30 height=60 border=0></td>";
		$this->pict_openfolder_root = "<td><img align=top src='/Admin/img/tree/openfolderRoot2.gif' width=34 height=24 border=0></td>";
		$this->pict_closedfolder = "<td><img align=top src='/Admin/img/tree/closedfolder.gif' width=24 height=22 border=0></td>";
		$this->pict_doc	   = "<td><img align=top src='/Admin/img/tree/doc.gif' width=21 height=22 border=0></td>";

		$this->div_1 = ",";
		$this->div_2_open = "(";
		$this->div_2_close = ")";
	}

	// adition of new unit in a tree
	function addnode($parentID,$ID,$caption,$ref="", $additionalCaption="") {
		if ($this->tree[$ID]) {
			echo "Error: Node with id=$ID already exists ! <br>";
		} else if (!($this->tree[$parentID])) {
			echo "Error: Parent Node with id=$parentID not exists ! <br>";
		} else
		{
			$i=0;
			// definition of a position of parental node in a tree
			while (($this->tree[$this->index[$i]]['id'] != $parentID ) && ($i<sizeof($this->index))) $i++;
			// definition of levels of an investment of parental and included units
			$NodeLevel = $this->tree[$parentID][level]+1;
			// definition of a position of added node in a tree
			while ((($this->tree[$this->index[$i+1]]['parent'] == $parentID) || ($this->tree[$this->index[$i+1]][level]>$NodeLevel)) && ($i<=sizeof($this->index))) $i++;

			// include node into tree
			$this->tree[$ID]['id'] = $ID;
			$this->tree[$ID]['parent'] = $parentID;
			$this->tree[$ID]['caption'] = $caption;
			$this->tree[$ID]['ref'] = $ref;
			$this->tree[$ID]['level'] = $NodeLevel;
			$this->tree[$ID]['additionalCaption'] = $NodeLevel;

			// include node into index
			for ($j=sizeof($this->index);$j>$i+1;$j--) $this->index[$j] = $this->index[$j-1];
			$this->index[$i+1] = $ID;
		}
	}

	// show of a tree on the screen
	function simpleshowtree() {
		$result .="<table cellpadding=0 cellspacing=0 border=0><tr><td>";
		for($i=1;$i < sizeof($this->index);$i++ )
		{
			for ($j=0;$j<($this->tree[$this->index[$i]][level]-1)*3;$j++) $result .="&nbsp;&nbsp;";
			$ref = $this->tree[$this->index[$i]][ref];
			if ($ref)
			{
				$result .= "<a href=$ref>".$this->tree[$this->index[$i]][caption]."</a><br>";
			}
			else
			{
				$result .= $this->tree[$this->index[$i]][caption]."<br>";
			}
		}
		$result .= "</td></tr></table>\n";
		return $result;
	}


	// show of a tree on the screen
	function showtree($mode=null) {
		$maxlevel="";
		if ($mode=="open_url_in_parent_window") {
			$result .= "<script>
					function OpenUrl(url) {
						opener.location.href = url;
					}
				</script>";
		}
		$result .="<table cellpadding=0 cellspacing=0 border=0>";
		for($i=0;$i < sizeof($this->index);$i++ )
		{
			$CurrentLevel = $this->tree[$this->index[$i]][level];
			$NextLevel = $this->tree[$this->index[$i+1]][level];
			$PrevLevel = $this->tree[$this->index[$i-1]][level];
			for($j=1;$j < $CurrentLevel-1;$j++) $this->prefix[$i][$j] = $this->prefix[$i-1][$j] ;
			if ($PrevLevel>=$CurrentLevel) 	for($j=1;$j < $CurrentLevel;$j++) $this->prefix[$i][$j] = $this->prefix[$i-1][$j] ;
			if ($CurrentLevel == $NextLevel)
			{
				$this->prefix[$i][$CurrentLevel] = ($CurrentLevel==1) ? $this->pict_fork2 : $this->pict_fork;
			}
			else
			{
				if ($CurrentLevel < $NextLevel)
				{
					if ($this->ExistNextIndexNode($i,$this->tree[$this->index[$i]][parent]))
					{
						$this->prefix[$i][$CurrentLevel] = ($CurrentLevel==1) ? $this->pict_fork2 : $this->pict_fork;
						$this->prefix[$i+1][$CurrentLevel] = $this->pict_line;
					}
					else
					{
						$this->prefix[$i][$CurrentLevel] = ($CurrentLevel==1) ? $this->pict_corner2 : $this->pict_corner;
						$this->prefix[$i+1][$CurrentLevel] = $this->pict_space;
					}
				}
				else
				{
					$this->prefix[$i][$CurrentLevel] = $this->pict_corner;
				}
			}
			if (empty($maxlevel) || $this->tree[$this->index[$i]][level] <=$maxlevel)
			{
				$result .= "<tr><td valign=middle nowrap><table cellpadding=0 cellspacing=0 border=0><tr>";
				for($j=1;$j < $CurrentLevel+1;$j++) $result .= $this->prefix[$i][$j];
				if ($this->CountChildren($this->tree[$this->index[$i]][id]))
				{
					if (empty($maxlevel) || $this->tree[$this->index[$i]][level] < $maxlevel)
					{
						if ($CurrentLevel == 0) {
							$result .= $this->pict_openfolder_root;
						} else {
							$result .= ($CurrentLevel == 1) ? $this->pict_openfolder2 : $this->pict_openfolder;
						}
					}
					else
					{
						$result .= $this->pict_closedfolder;
					}
				}
				else
				{
					$result .= $this->pict_doc;
				}
				$ref = $this->tree[$this->index[$i]][ref];
				if ($ref)
				{
					$url = ($mode=="open_url_in_parent_window") ? "javascript: OpenUrl('$ref')" : $ref;
					$result .= "<td valign=middle nowrap><a href=\"$url\" class=\"sitemapa\">".$this->tree[$this->index[$i]][caption]."</a></td>";
				}
				else
				{
					$result .= "<td valign=middle nowrap class=\"dir\">".$this->tree[$this->index[$i]][caption]."</td>";
				}
				$result .= "</tr></table></td></tr>\n";
			}
		}
		$result .= "</table>\n";
		return $result;
	}


	// show of a tree on the screen
	function showtree_old($maxlevel="") {
		$result .="<table cellpadding=0 cellspacing=0 border=0>";
		for($i=0;$i < sizeof($this->index);$i++ )
		{
			$CurrentLevel = $this->tree[$this->index[$i]][level];
			$NextLevel = $this->tree[$this->index[$i+1]][level];
			$PrevLevel = $this->tree[$this->index[$i-1]][level];
			for($j=1;$j < $CurrentLevel-1;$j++) $this->prefix[$i][$j] = $this->prefix[$i-1][$j] ;
			if ($PrevLevel>=$CurrentLevel) 	for($j=1;$j < $CurrentLevel;$j++) $this->prefix[$i][$j] = $this->prefix[$i-1][$j] ;
			if ($CurrentLevel == $NextLevel)
			{
				$this->prefix[$i][$CurrentLevel] = $this->pict_fork;
			}
			else
			{
				if ($CurrentLevel < $NextLevel)
				{
					if ($this->ExistNextIndexNode($i,$this->tree[$this->index[$i]][parent]))
					{
						$this->prefix[$i][$CurrentLevel] = ($CurrentLevel==1) ? $this->pict_fork2 : $this->pict_fork;
						$this->prefix[$i+1][$CurrentLevel] = $this->pict_line;
					}
					else
					{
						$this->prefix[$i][$CurrentLevel] = ($CurrentLevel==1) ? $this->pict_corner2 : $this->pict_corner;
						$this->prefix[$i+1][$CurrentLevel] = $this->pict_space;
					}
				}
				else
				{
					$this->prefix[$i][$CurrentLevel] = $this->pict_corner;
				}
			}
			if (empty($maxlevel) || $this->tree[$this->index[$i]][level] <=$maxlevel)
			{
				$result .= "<tr><td valign=middle nowrap>";
				for($j=1;$j < $CurrentLevel+1;$j++) $result .= $this->prefix[$i][$j];
				if ($this->CountChildren($this->tree[$this->index[$i]][id]))
				{
					if (empty($maxlevel) || $this->tree[$this->index[$i]][level] < $maxlevel)
					{
						if ($CurrentLevel == 0) {
							$result .= $this->pict_openfolder_root;
						} else {
							$result .= ($CurrentLevel == 1) ? $this->pict_openfolder2 : $this->pict_openfolder;
						}
					}
					else
					{
						$result .= $this->pict_closedfolder;
					}
				}
				else
				{
					$result .= $this->pict_doc;
				}
				$ref = $this->tree[$this->index[$i]][ref];
				if ($ref)
				{
					$result .= "<a href=$ref class=\"".(($CurrentLevel==1) ? "sitemapha" : "sitemapa")."\">".$this->tree[$this->index[$i]][caption]."</a>";
				}
				else
				{
					$result .= $this->tree[$this->index[$i]][caption];
				}
				$result .= "</td></tr>\n";
			}
		}
		$result .= "</table>\n";
		return $result;
	}


	// Представление 1-го и 2-го и 3-го уровней древовидной структуры в виде таблицы в две колонки.

	function showtable() {

		$CountShowNode = 0;
		for($i=0;$i < sizeof($this->index);$i++ )
		{
			if (($this->tree[$this->index[$i]][level] >= 1) && ($this->tree[$this->index[$i]][level] <= 3)) $CountShowNode++;
		}

		$ShowNode = 0;
		$result .="<table width=100% border=0 cellpadding=0 cellspacing=10><tr><td width=50% valign=top> \n";
		for($i=0;$i < sizeof($this->index);$i++ )
		{
			$ref = $this->tree[$this->index[$i]][ref];
			if ($ref)
			{
				$class = ($this->tree[$this->index[$i]][level] == 1) ? "class=section1" : "class=section2";
				$caption = "<a href=$ref $class>".str_replace(" ","&nbsp;",$this->tree[$this->index[$i]][caption])."</a>";
			}
			else
			{
				$caption = $this->tree[$this->index[$i]][caption];
			}
			switch ($this->tree[$this->index[$i]][level]) {
				case 1: $ShowNode++;
				if ($i !=0 && $this->tree[$this->index[$i-1]][level] > 1) $result .= "</td></table> \n";
				if ($ShowNode >= round(($CountShowNode)/2)+1)
				{
					$result .="</td><td valign=top> \n"; // for main column
					$ShowNode = 0;
				}
				$result .="<b>$caption</b><br> \n";
				if ($i != sizeof($this->index) && $this->tree[$this->index[$i+1]][level] > 1)	$result .= "<table border=0 cellpadding=0 cellspacing=0><tr><td width=10></td><td> \n";
				break;
				case 2: $ShowNode++;
				if ($i !=0 && $this->tree[$this->index[$i-1]][level] != 1) $result .= $this->div_1." ";
				$result .= $caption;
				break;
				case 3: $ShowNode++;
				if ($i !=0 && $this->tree[$this->index[$i-1]][level] == 2) {$result .= "<font size=-1>&nbsp;".$this->div_2_open."&nbsp;<i>";} else {$result .= ", ";};
				$result .=$caption;
				if (!$this->ExistNextIndexNode($i,$this->tree[$this->index[$i]][parent])) $result .= "</i>&nbsp;".$this->div_2_close."</font>";
				break;
			}
		}
		if ($this->tree[$this->index[$i-1]][level] > 1) $result .= "</td></table> \n";
		$result .="</td></tr></table>";
		return $result;
	}


	function show_table_1() {
		$result .="<table cellspacing=0 cellpadding=0 border=0>
			<tr valign=top>
			    <td width=50%>
					<table cellspacing=1 cellpadding=0 border=0 bgcolor=#c16400 width=100% height=100%>
					<tr>
					    <td>
							<table cellspacing=5 border=0 bgcolor=#fff2e2 width=100% height=100%>
							<tr valign=top>
							    <td align=center>";
		for($i=0;$i < sizeof($this->index);$i++ )
		{
			$caption = $this->tree[$this->index[$i]][caption];
			$ref = $this->tree[$this->index[$i]][ref];
			if ($ref) $caption = "<a href=$ref class=main_menu>".str_replace(" ","&nbsp;",$caption)."</a>";
			switch ($this->tree[$this->index[$i]][level]) {
				case 1: $id_NodeLevel1 = $this->tree[$this->index[$i]][id];  // запоминаем идентификатор категории уровня 1
				if (!$switched && ($i >= round((sizeof($this->index))/2)))
				{
					$switched = "yes";
					$result .="</td>
							</tr>
							</table>
						</td>
					</tr>
				</table>
				</td>
			    <td><img src=\"/Admin/img/x.gif\" width=4 height=1 alt=\"\"></td>
			    <td width=50%><table cellspacing=1 cellpadding=0 border=0 bgcolor=#c16400 width=100% height=100%>
			<tr>
				<td>
					<table cellspacing=5 border=0  bgcolor=#fff2e2 width=100% height=100%>
					<tr valign=top>
					    <td align=center>"; // делим таблицу на два столбца
				}
				$result .="$caption <br>\n";
				break;
			}
		}
		$result .="			</td>
					</tr>
				</table>
			</td>
		</tr>
	</table></td>
</tr>
</table>
";
		return $result;
	}


	// Представление 1-го и 2-го уровней древовидной структуры в виде таблицы элементов ввода CheckBox в две колонки.
	//  - $name_prefix[идентификатор узла] - имя элемента CheckBox
	//  - $checkboxes_array - массив идентификаторов узлов для которых CheckBox в состоянии checked
	function show_checkboxes($name_prefix, $checkboxes_array='') {

		if(!$checkboxes_array){
			$checkboxes_array = array();
		}

		$result .="<table width=100% border=0 cellpadding=0 cellspacing=10><tr><td width=50% valign=top> \n";
		for($i=0;$i < sizeof($this->index);$i++ )
		{
			$caption = $this->tree[$this->index[$i]][caption];
			switch ($this->tree[$this->index[$i]][level]) {
				case 0: //$result .="<input type=hidden name=$name_prefix"."[".$this->tree[$this->index[$i]][id]."] value='".$this->tree[$this->index[$i]][id]."'>\n";
				break;
				case 1: $id_NodeLevel1 = $this->tree[$this->index[$i]][id];  // запоминаем идентификатор категории уровня 1
				if (!$switched && ($i >= round((sizeof($this->index))/2)+1))
				{
					$switched = "yes";
					$result .="</td><td valign=top> \n"; // делим таблицу на два столбца
				}
				$result .="<b>$caption</b><br> \n";
				if (($this->tree[$this->index[$i+1]][level] == 1) || ($i == sizeof($this->index)-1))
				$result .= $this->print_CeckBox($name_prefix,$id_NodeLevel1,$checkboxes_array,"<i>Все в этой категории</i>");
				break;
				case 2:
				$result .= $this->print_CeckBox($name_prefix,$this->tree[$this->index[$i]][id],
				$checkboxes_array,$caption, $this->tree[$this->index[$i]][level],
				$this->CountChildren($this->tree[$this->index[$i]][id]));
				//if ((!$this->ExistNextIndexNode($i,$this->tree[$this->index[$i]][parent])) || ($i == sizeof($this->index)-1))
				//				if (($this->tree[$this->index[$i+1]][level] == 1) || ($i == sizeof($this->index)-1))
				//$result .= $this->print_CeckBox($name_prefix,$id_NodeLevel1,$checkboxes_array,"<i>Остальное в этой категории</i>");
				break;
				case 3:
				$result .= $this->print_CeckBox($name_prefix,$this->tree[$this->index[$i]][id],
				$checkboxes_array,$caption, $this->tree[$this->index[$i]][level],
				$this->CountChildren($this->tree[$this->index[$i]][id]));
				//if ((!$this->ExistNextIndexNode($i,$this->tree[$this->index[$i]][parent])) || ($i == sizeof($this->index)-1))
				//				if (($this->tree[$this->index[$i+1]][level] == 1) || ($i == sizeof($this->index)-1))
				//$result .= $this->print_CeckBox($name_prefix,$id_NodeLevel1,$checkboxes_array,"<i>Остальное в этой категории</i>");
				break;
			}
		}
		$result .="</td></tr></table>";
		return $result;
	}




	function showTreeEdit() {


		if(!$checkboxes_array){
			$checkboxes_array = array();
		}
		$url = 'page.php?action=new&Parent_ID=0';
		echo '<a href="'.$url.'"><img src="/Admin/img/button-add.gif" border=0></a> ';
		$result .="<table width=100% border=0 cellpadding=0 cellspacing=10><tr><td width=50% valign=top> \n";
		for($i=0;$i < sizeof($this->index);$i++ )
		{
			$caption = $this->tree[$this->index[$i]][caption];
			$id = $this->tree[$this->index[$i]][id];
			switch ($this->tree[$this->index[$i]][level]) {
				case 0:
				break;
				case 1: $id_NodeLevel1 = $this->tree[$this->index[$i]][id];  // запоминаем идентификатор категории уровня 1
				if (!$switched && ($i >= round((sizeof($this->index))/2)+1))
				{
					$switched = "yes";
					$result .="</td><td valign=top> \n"; // делим таблицу на два столбца
				}
				$url = 'page.php?action=new&Parent_ID='.$id;
				$actions = '<a href="'.$url.'"><img src="/Admin/img/knopka_edit.gif" border=0 alt=Add></a> ';

				$url2 = 'page.php?action=delete&ID='.$id;
				$url = JSConfirm ("Delete \'".$caption."\'?", $url2);
				$actions .= ' <a href="'.$url.'"><img src="/Admin/img/knopka_del.gif" border=0 alt=Remove></a>';

				$url = 'page.php?action=edit&ID='.$id;
				$result .='<a href="'.$url.'"><span class=dir>'.$caption.'</span></a>  '.$actions.'<br>';
				/*if (($this->tree[$this->index[$i+1]][level] == 1) || ($i == sizeof($this->index)-1))
				$result .= $this->print_EditView($name_prefix,$id_NodeLevel1,"<i>Все в этой категории</i>");*/
				break;
				case 2:
				$result .= $this->print_EditView($name_prefix,$this->tree[$this->index[$i]][id], $caption, $this->tree[$this->index[$i]][level],
				$this->CountChildren($this->tree[$this->index[$i]][id]));
				break;
				case 3:
				$result .= $this->print_EditView($name_prefix,$this->tree[$this->index[$i]][id], $caption, $this->tree[$this->index[$i]][level],
				$this->CountChildren($this->tree[$this->index[$i]][id]));
				break;
			}
		}
		$result .="</td></tr></table>";
		return $result;
	}

	//  **************************** Дополнительные функции. Используются только внутри класса **************************************

	function CountChildren($NodeID) {
		$count = 0;
		for ($i=0;$i<sizeof($this->index)-1;$i++)
		if ($this->tree[$this->index[$i]][parent] == $NodeID) $count++;
		return $count;
	}
	function ExistNextIndexNode($CurrentIndex,$CurrentParentNode) {
		$result = 0;
		for ($i=$CurrentIndex+1;$i<sizeof($this->index);$i++)
		if ($this->tree[$this->index[$i]][parent] == $CurrentParentNode) $result=1;
		return $result;
	}

	// $name_prefix[$checkbox_id] - имя элемента CheckBox
	// если $checkbox_id входит в массив $checkboxes_array CheckBox в состоянии checked
	// $caption - информация которая выводится рядом с checkbox - ом
	function print_CeckBox($name_prefix,$checkbox_id,$checkboxes_array,$caption, $level=1, $haveChilde=false) {
		$space = '';
		$level = $level - 1;
		for($i=0; $i<$level; $i++){
			$space .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}

		$result .= $space;
		if(!$haveChilde){
			$result .= "<input type=checkbox name=$name_prefix"."[$checkbox_id] value='$checkbox_id'";
			if (sizeof($checkboxes_array))
			if (in_array($checkbox_id,$checkboxes_array)) $result .=" checked";
			$result .= ">";
		}else{
			$result .= "<b> - </b>";
		}

		$result .= "$caption";

		$result .= "<br>";

		return $result;
	}

	function print_EditView($name_prefix,$id,$caption, $level=1, $haveChilde=false) {
		$space = '';
		$actions = '';
		$level = $level - 1;
		for($i=0; $i<$level; $i++){
			$space .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}

		$result .= $space;
		$result .= "<img src=\"/Admin/img/tree/doc.gif\" border=0>";
		if($level < 2){
			$url = 'page.php?action=new&Parent_ID='.$id;
			$actions .= ' <a href="'.$url.'"><img src="/Admin/img/knopka_edit.gif" border=0 alt=Add></a>';
		}

		$url2 = "page.php?action=delete&ID=".$id;
		$url = JSConfirm ("Delete \'".$caption."\'?", $url2);
		$actions .= ' <a href="'.$url.'"><img src="/Admin/img/knopka_del.gif" border=0 alt=Remove></a>';

		$url = 'page.php?action=edit&ID='.$id.'&Parent_ID='.$this->tree[$this->index[$i]]['parent'];
		$result .= '<a href="'.$url.'"class= maintxt><b>'.$caption.'</b></a> '.$actions;

		$result .= "<br>";

		return $result;
	}

} // end class


// use example
/*
$t = new tree(1,"Root folder","aaa");

$t->addnode(1,23,"name_23");
$t->addnode(1,38,"name_25");
$t->addnode(1,44,"name_67");
$t->addnode(1,24,"name_24");
$t->addnode(44,25,"name_25");
$t->addnode(25,67,"name_67");
$t->addnode(67,88,"name_88","http://aport.ru");
$t->addnode(25,55,"name_55");
$t->addnode(23,77,"name_77");
$t->addnode(88,888,"name_888");

$t->addnode(222,4,"name_24");

$t->addnode(44,33,"name_33");
$t->addnode(38,39,"name_39");
$t->addnode(44,12,"name_12");
$t->addnode(44,13,"name_13");
$t->addnode(12,22,"name_22");
$t->addnode(22,19,"name_19");
$t->addnode(22,20,"name_20");

// echo $t->simpleshowtree();
echo $t->showtree();
*/
?>