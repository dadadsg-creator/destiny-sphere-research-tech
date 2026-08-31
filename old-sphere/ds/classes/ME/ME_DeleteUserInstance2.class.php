<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_DeleteUserInstance2
{
	static private $common;
	
	static private $UIID;
	
	static public function Delete(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		$this->common = $common;	
		
			
	}	
	
	/**
	 * Удаление ивентов чувака и все что с ними связанно	
	 */
	static private function _deleteEvents()
	{
		//Здесь надо незабыть про армии которые летятя на этого чувака. Их надо корректно на базу вернуть.
		//Незабыть убить трейды этого чувака.
		//Убить ставки на аукуионах этого чувака если они есть.
	}
	
	/**
	 * Удаление архологических экспедиций чувака
	 */
	static private function _deleteArhiologists()
	{
		
	}
	
	/**
	 * Удаление сиситемнызх сообщений пользователя
	 *
	 */
	static private function _deleteMessages()
	{
		//Незабыть удалит мессадж скопы
	}
	
	/**
	 * Удаление армий чувака
	 *
	 */
	static private function _deleteArmys()
	{
		
	}
	
	/**
	 * Удаление артефактов пользователя
	 *
	 */
	static private function _deleteArtefacts()
	{
		
	}
	
	/**
	 * Сведений об альянсе пользователя
	 *
	 */
	static private function _deleteAlliance()
	{
		
	}
	
	/**
	 * Удаления наук пользователя
	 *
	 */
	static private function _deleteScience()
	{
		
	}
	
	/**
	 * Удаления наук пользователя
	 *
	 */
	static private function _deleteScience()
	{
		
	}
	
	/**
	 * Удаления сот пользователя
	 *
	 */
	static private function _deleteCombs()
	{
		
	}
	
	static private function _deleteUIIDTales()
	{
		
	}
	
	static private function _deleteBattles()
	{
		
	}
	
	static private function _out($msg)
	{
		$str = SU_DebugTools::GetInternalDate()." ".$msg."\n";				
		
		echo $str;
		flush();
	}	
}



?>