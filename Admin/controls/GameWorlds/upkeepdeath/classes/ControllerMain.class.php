<?php 

class ControllerMain
{
	static public function Show()
	{
		$params = EntityInputParams::Singleton();		

		switch($params->get('ACTION'))
		{
			case 'showlist':		
				OutNewForm::InForm($params);
				break;
			default:
				OutNewForm::InForm($params);

		}
	}
}

?> 
