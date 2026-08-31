<?php $CVS_Version = '$Revision$ ; $Date$';

class TL_SpyAction extends TL_BaseMenu 
{
	static private $clientZondType = array(
			'SPY_ZOND_SCOUT' => 1,
			'SPY_ZOND_EXPLORER' => 2,
		);
	
	static public function FormMenu(TI_SpyAction $incoming)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><spying/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$root->set_attribute('turn', $incoming->turnTime);
		// номер текущего кольца
		$root->set_attribute('numring', $incoming->currentRing);
		
		$ch = $root->new_child('onbase');
		
		$id = array();
		$allow = array();
		$repair = array();
		$typeZond = array();
		foreach ($incoming->zondUnit as $k => $unit)
		{
			$id[] = $unit->getName();
			$allow[] = $incoming->freeZondsCount[$unit->getName()];
			$repair[] = $incoming->repairZondsCount[$unit->getName()];
			$typeZond[] = self::$clientZondType[$unit->getName()];
		}
		
		// данные о зондах
		$ch->set_attribute('id', implode(',', $id));
		$ch->set_attribute('allow', implode(',', $allow));
		$ch->set_attribute('repair', implode(',', $repair));
		$ch->set_attribute('type_zond', implode(',', $typeZond));
		
		// время защиты
		$ch->set_attribute('maxtime', $incoming->maxDefenceTime);		
		$ch->set_attribute('mintime', 1);
		$ch->set_attribute('deftime', 1);
		
		// куда можно летать
		$ch->set_attribute('rings', '1111');
		
		// скорость
		$ch->set_attribute('defs', $incoming->maxSpeed);
		$ch->set_attribute('maxs', $incoming->maxSpeed);
		$ch->set_attribute('mins', (float)DE_WorldSettings::CreateForCurentWorld()->get('MIN_ACCELERATION')*100);
		
		$ch->set_attribute('desallow', '1');


		// действия
		foreach ($incoming->actions as $k => $action)
		{
			$ch2 = $ch->new_child('action');
			$ch2->set_attribute('name', $action->getLocalizedName());
			$ch2->set_attribute('id', $action->getName());
			$ch2->set_attribute('coord', (int)$action->needCoordinates());
			$ch2->set_attribute('time', (int)$action->needTime());
		}
		
		// хрень какая-то, никто не знает зачем (в т.ч. и зачем это в клинете)
		$ch = $root->new_child('inaction');
		$ch->set_attribute('inact', 0);
		$ch->set_attribute('allallow', 0);
		
		// данные о летающих группах 
		foreach ($incoming->groups as $k => $group)
		{
			$ch2 = $ch->new_child('group');
			$ch2->set_attribute('id', $group->getLogID());
			$ch2->set_attribute('action', $group->getAction()->getLocalizedName());
			$ch2->set_attribute('count', $group->getAmount());
			$ch2->set_attribute('zname', $group->getUnit()->getLocalizedName());
			$ch2->set_attribute('zcolor', GB_SpyMenuColor::$color[$group->getUnit()->getName()]);
			if($group->getCombTo() instanceof DE_Comb)
			{
				$comb = $group->getCombTo();
			} else {
				$comb = $group->getCombFrom();
			}
			$ch2->set_attribute('addr', $comb->getFormatedAddress());
			$ch2->set_attribute('time_ret', $group->getEndDate()->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta()));
			$ch2->set_attribute('log', (int)$group->haveLog());
		}
		
		// закладки
		self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());	
		
		return $doc->dump_mem();
	}
}

?>