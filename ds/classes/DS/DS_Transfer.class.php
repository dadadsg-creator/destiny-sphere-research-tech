<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Transfer extends DS_Base
{
	static public function LogTransfer($idCombFrom, $idCombTo, $amount, $id_article, $categoryname, DE_EventQueue $event)
	{
		assert(is_int($idCombFrom) && $idCombFrom > 0);
		assert(is_int($idCombTo) && $idCombTo > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(!empty($categoryname));
		
		$common = self::getCommon();
		
		$sql = "
			INSERT INTO ds_transfer_log
				(id_comb_from, id_comb_to, amount, id_article, categoryname, id_eventqueue)
			VALUES
				($idCombFrom, $idCombTo, $amount, $id_article, '$categoryname', ".$event->getID().")
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function LogTransferDeliver($eventId)
	{
		assert(is_int($eventId) && $eventId > 0);
		
		$common = self::getCommon();
		
		$sql = "
			UPDATE 
				ds_transfer_log
			SET
				is_delivered = 't',
				end_date = now()
			WHERE
			   	id_eventqueue = $eventId
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
}//END OF CLASS

?>