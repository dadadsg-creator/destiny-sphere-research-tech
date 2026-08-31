<?php
class TradeCourseRecountOut
{
	static public function OutWorldList($worlds)
	{
//print_r($worlds);		
		echo "<h1>Trade Course Recount</h1>";
		echo "<font color=#F8F8FF><b>Select world for recount:</b></font><br>";
		
		echo "<form action='".$_SERVER['PHP_SELF']."' method='GET'>";
		foreach($worlds AS $k=>$worldData)
		{
			echo "<input type='checkbox' name='WORLD_S_NAME[]' value=".$worldData['dbid']."_".$worldData['id_world']."> <font color=#F8F8FF>".$worldData['worldname']."</font><br>";

			//echo "<a href=".$_SERVER['PHP_SELF']."?ACTION=check&WORLD_S_NAME=".$worldData['world_identifier'].">".$worldData['worldname']."</a><br>";
		}
		echo '<input type="hidden" name="action" value="recount">';
		echo "<br>";
		echo '<input type="submit" class="Submit" value="Recount">';
		echo "</form>";
	}
}
?>