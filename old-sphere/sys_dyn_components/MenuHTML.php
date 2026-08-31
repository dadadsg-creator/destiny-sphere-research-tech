	<script language="JavaScript1.2">mmLoadMenus();</script>
	<table border="0" cellpadding="0" cellspacing="0" align="center" valign="middle">
		<tr>
			
<?
$mainMenuItems = $this->getMainMenu();

$i = 0;

$punkt[$i]['code'] = '';
$punkt[$i]['status'] = 'separator';

$i++;

foreach ($mainMenuItems  as $cat_id=>$menu){
			
	if($cat_id == $this->Page['category_id']){
		$button = 'top_menu_select_c';
		$status = 'active';
		$color_class = 'menu_select_txt';
	}else{
		$button = 'top_menu_bg';
		$status = 'passive';
		$color_class = 'menu_txt';
	}
	
	$punkt[$i]['code']  = '<!--button 05-->
						<td width="95" height="36" align=center valign="middle" background="img/v4/but/'.$button.'.gif" class="'.$color_class.'">
							<div name="link_'.$cat_id.'" id="link_'.$cat_id.'" style="width:95px; height:100%; cursor: pointer;" onMouseOver="MM_showMenu(window.mm_menu_'.$cat_id.',-35,34,null,\'link_'.$cat_id.'\');" onMouseOut="MM_startTimeout();" onclick="openURL(\''.$menu['URL'].'\');" valign=middle class="'.$color_class.'"><span style="position: relative; top: 10px;">'.$menu['Title'].'</span></div>
						</td>';
	$punkt[$i]['status'] = $status;	
	
	$i++;
	
	$punkt[$i]['code'] = '';
	$punkt[$i]['status'] = 'separator';
	
	$i++;
	
}//END OF FOREACH


/*echo "<hr>";
echo "<pre>";

var_dump($punkt);

echo "</pre>";
echo "<hr>";*/


for ( $i = 0; $i < count($punkt); $i++ ) {	
	
	if ( $punkt[$i]['status'] != 'separator') {
		continue;
	}
	
	if ( $punkt[$i-1]['status'] == 'active') {
		$button = 'top_menu_select_right';	
	}elseif ( $punkt[$i+1]['status'] == 'active') {
		$button = 'top_menu_select_left';	
	}else{
		$button = 'top_menu_delimiter';
	}
	
	
	$punkt[$i]['code'] = '<td height="36" align="center" valign="middle"><img src="img/v4/but/'.$button.'.gif" border=0></td>';
	
}//END OF FOREACH

foreach ( $punkt as $k=>$v ) {
	echo $v['code'];
}
?>
			
		</tr>
	</table>