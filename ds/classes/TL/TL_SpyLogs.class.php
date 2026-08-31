<?php $CVS_Version = '$Revision$ ; $Date$';

class TL_SpyLogs extends TL_BaseMenu 
{
	static public function FormMenu(TI_SpyLogs $incoming, $hideTabs = false)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?><spylog/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		// Если нам надо скрыть панельки
		if ($hideTabs == true) {
			$root->set_attribute('filtermenu', 0);
		}

		foreach ($incoming->logs as $k => $log)
		{
			$ch = $root->new_child('object');
			$ch->set_attribute('id', $log->getID());
			$ch->set_attribute('action', $log->getAction()->getLocalizedName());
			
			if($log->getAction()->getName() == 'RESEARCH')
			{
				$ch->set_attribute('addrfrom', $log->getCombFromFormated());
				$ch->set_attribute('addrto', $log->getCombToFormated());
			} else {
				$ch->set_attribute('addrfrom', $log->getCombToFormated());
				$ch->set_attribute('addrto', $log->getCombFromFormated());
			}
			
			//$ch->set_attribute('namefrom', '');
			//$ch->set_attribute('nameto', '');
			$ch->set_attribute('date', $log->getDate()->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta()));
			$ch->set_attribute('typelog', '');
			if($log->getUnit() instanceof DE_Unit)
			{
				$ch->set_attribute('zname', $log->getUnit()->getLocalizedName());
				$ch->set_attribute('zcolor', GB_SpyMenuColor::$color[$log->getUnit()->getName()]);
			} else {
				$ch->set_attribute('zname', '');
				$ch->set_attribute('zcolor', '#FFFFFF');
			}
		}
		
		$ch = $root->new_child('filter');
		
		$ch2 = $ch->new_child('types');
		foreach ($incoming->actions as $k => $action) 
		{
			$ch3 = $ch2->new_child('type');
			$ch3->set_attribute('id', $action->getName());
			$ch3->set_attribute('name', $action->getLocalizedName());
			if(in_array($action->getID(), $incoming->selectedActions))
			{
				$ch3->set_attribute('cur', 1);
			}
		}
		
		$ch2 = $ch->new_child('time');
		$ch2->set_attribute('val', $incoming->time);
		$ch2->set_attribute('min', $incoming->timeMin);
		$ch2->set_attribute('max', $incoming->timeMax);
		
		$ch2 = $ch->new_child('name');
		$ch2->set_attribute('def', $incoming->userName);
		
		
		$ch2 = $ch->new_child('colonys');
		foreach ($incoming->combs as $k => $comb) 
		{
			$ch3 = $ch2->new_child('colony');
			$ch3->set_attribute('name', $comb->getName());
			$ch3->set_attribute('addr', $comb->getFormatedAddress());
			$ch3->set_attribute('id',   $comb->getCombID());
			if(in_array($comb->getCombID(), $incoming->selectedCombs))
			{
				$ch3->set_attribute('cur', 1);
			}
		}
		
		// Если нам НЕ надо скрыть панельки
		if ($hideTabs == false) {
			// закладки
			self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());	
		}
		
		return $doc->dump_mem();
	}
}

?>