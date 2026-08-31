<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CanMoveArmyToComb
{
	static private $errorCode;
	
	static public function GetErrorCode()
	{
		return self::$errorCode;
	}
	
	static public function CanMove(DE_Comb $combFrom, DE_CombInterface $combTo)
	{
		//Корректные коррдинаты цели
		if($combTo->isNull())
		{
			self::$errorCode = 'WRONG_DESTINATION';
			return false;
		}

		$combName = $combTo->getName();
		if(empty($combName))
		{
			self::$errorCode = 'EMPTY_COMB';
			return false;
		}

		//Открыты центральные ворота у отправителя
		if(!$combFrom->haveOpenCentralGate())
		{
			self::$errorCode = 'NO_FROM_CENTRAL_GATE';
			return false;
		}

		//Етсь ли гондонотранспортер у отправителя
		if(!$combFrom->haveGandolaTransporter())
		{
			self::$errorCode = 'NO_GONDOLA_TRANSPORTER';
			return false;
		}

		//Если сота получаеля на другом кольце то есть межкольцевые ворота
		if(($combTo->getRingNumber() != $combFrom->getRingNumber()) && !$combFrom->haveOpenRingGate())
		{
			self::$errorCode = 'NO_RING_GATE';
			return false;
		}

		//Попытка ставить на защиту своей же соты
		if($combTo->getID() == $combFrom->getID())
		{
			self::$errorCode = 'SAME_USER_COMB';
			return false;
		}

		//Открыты центральные ворота у получателя
		if(!$combTo->haveOpenCentralGate())
		{
			self::$errorCode = 'NO_TO_CENTRAL_GATE';
			return false;
		}
		
		if(GL_CanMooveArmysAfterFreez::Test($combFrom->getUserInstance(), GB_TypeFactory::CreateBase('GB_Date', time())))
		{			
			self::$errorCode = "CANT_MOOVE_ARMY_AFTER_FREEZ";
			return false;
		}
		
		if($combFrom->getUserInstance()->getID() != $combTo->getUserInstance()->getID())
		{
			self::$errorCode = 'NOT_USER_COMB_TO';
			return false;
		}
		
		return true;
	}
}

?>