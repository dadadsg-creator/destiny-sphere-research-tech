<?php $CVS_Version = '$Revision$ ; $Date$';
class ControllerMain
{
	static public function Show()
	{
		$params = EntityInputParams::Singleton();	

		switch($params->get('ACTION'))
		{
			case 'showlist':		
				OutForm::OutNewForm($params);
				break;
			default:
				OutForm::OutNewForm($params);
		}
	}
}

?> 
