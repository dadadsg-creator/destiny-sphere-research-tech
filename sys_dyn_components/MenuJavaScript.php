<script language="JavaScript">
<!--
function mmLoadMenus() {
	   
<?
	$mainMenuItems = $this->getMainMenu();
	
	$firstStart = false;
	
	foreach ($mainMenuItems  as $cat_id=>$menu){	
		if ( !$firstStart ) {
?>
			if (window.mm_menu_<?=$cat_id?>) return;
<?			
			$firstStart = $cat_id;			
		}
		

		$L1 .= "window.mm_menu_".$cat_id." = new Menu(\"root\",150,18,\"\",10,\"#111111\",\"#ffffff\",\"#f8f8f8\",\"#3D3F6C\",\"left\",\"middle\",2,0,450,3,0,true,true,true,0,true,true);\n";		

		$MenuItems2 = $this->getMenuLevel2($cat_id);	

		foreach ( $MenuItems2 as $cat_id2 => $menu2 ){
			
			if($menu2['notInMenu']){
				continue;
			}
				
			$MenuItems3 = $this->getMenuLevel3($cat_id2);			

			if( count ( $MenuItems3 )  > 0 ){
				
				foreach ( $MenuItems3 as $cat_id3 => $menu3 ){
					break;
				}
				
				$menu3Parent[$menu2['Title']] = $MenuItems3;
				
										
  				$L1 .= 	"mm_menu_".$cat_id.".addMenuItem(mm_menu_".$cat_id3.");\n";
				
				
			}else{
				if ( $menu2['inNewWindow'] ) {
					$url = "window.open('".$menu2['URL']."','_blank');";
				}else{
					$url = "location='".$menu2['URL']."'";
				}
				//($menu2['Title'][0] == "-" ? $url : "")
  				$L1 .= "mm_menu_".$cat_id.".addMenuItem(\"".$menu2['Title']."\",\"".$url."\");\n";   			
				
			}			
			

		}//END OF FOREACH

		$L1 .= "mm_menu_".$cat_id.".hideOnMouseOut=true;\n";
   		$L1 .= "mm_menu_".$cat_id.".bgColor='#f8f8f8';\n";
   		$L1 .= "mm_menu_".$cat_id.".menuBorder=1;\n";
   		$L1 .= "mm_menu_".$cat_id.".menuLiteBgColor='#f8f8f8';\n";
   		$L1 .= "mm_menu_".$cat_id.".menuBorderBgColor='#B5BBC4';\n";		

	}//END OF FOREACH
	
	//Генерим менюху третьего уровня
	foreach ($menu3Parent as $parentName => $MenuItems3){
		
		foreach ( $MenuItems3 as $cat_id2 => $menu3 ){
			break;
		}
		
		$L2 .= "window.mm_menu_".$cat_id2." = new Menu(\"".$parentName."\",160,18,\"\",10,\"#111111\",\"#ffffff\",\"#f8f8f8\",\"#3D3F6C\",\"left\",\"middle\",1,0,450,-5,7,true,true,true,0,true,true);\n";
		
		foreach($MenuItems3 as $cat_id3 =>$menu3){
			if ( $menu3['inNewWindow'] ) {
				$url = "window.open('".$menu3['URL']."','_blank');";
			}else{
				$url = "location='".$menu3['URL']."'";
			}			
  			$L2 .= "mm_menu_".$cat_id2.".addMenuItem(\"".$menu3['Title']."\",\"".$url."\");\n";   						
		}
		
		$L2 .= "mm_menu_".$cat_id2.".hideOnMouseOut=true;\n";
   		$L2 .= "mm_menu_".$cat_id2.".bgColor='#f8f8f8';\n";
   		$L2 .= "mm_menu_".$cat_id2.".menuBorder=1;\n";
   		$L2 .= "mm_menu_".$cat_id2.".menuLiteBgColor='#f8f8f8';\n";
   		$L2 .= "mm_menu_".$cat_id2.".menuBorderBgColor='#B5BBC4';\n";
	}
	echo $L2;
	echo $L1;
?>
   
mm_menu_<?=$firstStart?>.writeMenus();
} // mmLoadMenus()
//-->
</script>
<script language="JavaScript" src="popup_menu.js"></script><!--end menu java script-->
