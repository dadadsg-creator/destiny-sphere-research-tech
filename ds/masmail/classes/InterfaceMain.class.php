<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:45:57 $';

require_once('./classes/InterfaceBase.class.php');

class InterfaceMain extends InterfaceBase 
{
	function exec()
	{		
		foreach(InterfaceController::$components as $interfaceName => $className)
		{
			if($interfaceName == get_class($this))
			{
				continue;
			}
			
			$interface = InterfaceController::GetInterfaceInstance($interfaceName);

			$name = $interface->getName();
			$over = $interface->getOver();
			$url = $_SERVER['PHP_SELF']."?interface=".$interfaceName;
							
			echo "<a href=$url>$name</a><br>$over<br><br>";
		}
	}
	
	function getName()
	{
		return "InterfacesList";
	}
	
	function getOver()
	{
		return "Здесь список сононых доступных рассылок";
	}
}


?>