<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_ReturnArmyFromDefend
{
	public function Exec(DE_Army $army)
	{
		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
		$uiid = $army->getUserInstance()->getID();
		
		ME_CancelDefend::Cancel($army, $checkDate);
		
		$messparams[0] = "{b} ".ME_CancelDefend::$combTo->getFullName()."{/b}";
		$messparams[1] = "{b} ".ME_CancelDefend::$combTo->getUserInstance()->getUser()->getName()."{/b}";
		$messparams[2] = "{b} ".$army->getName()."{/b}";
		$messparams[3] = "{b} ".ME_CancelDefend::$combFrom->getName()."{/b}";
		$messparams[4] = "{b} ".ME_CancelDefend::$travelTime->getDSFormat()."{/b}";

		// системное сообщение
		DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $uiid, 'CANCEL_DEFEND_MESS2', $messparams, 1, "GREEN");
	}
}


?>