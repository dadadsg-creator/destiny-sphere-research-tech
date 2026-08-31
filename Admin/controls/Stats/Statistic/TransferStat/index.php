<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once "./classes/TransferStatController.class.php";
require_once "./classes/TransferStatOut.class.php";
require_once "./classes/TransferStatSearch.class.php";

echo '<p align="right">
		<a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TransferStat>
			<font size=3>
				<b>
					HELP
				</b>
			</font>
		</a>
	</p>';

$s = new TransferStat($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>