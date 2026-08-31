<?php $CVS_Version = '$Revision$ ; $Date$';

import ("Base");

/** 
 * Базовый класс, для всех классов игры.
 * Содержит основные базовые методы и атрибуты
 */

class GameBase extends Base{
	
	/** Ссылка на глобальный контенер с тулами */
	public $tools = null;
	
	/** Коснтуртор класса */
	function GameBase ()
	{		
		
		$this->Base ();
		
		if(key_exists ( 'GC', $GLOBALS ))
		{
			assert($GLOBALS['GC'] instanceof GB_GlobalContainer);				
			$this->tools = &$GLOBALS['GC'];
		}		
		
		return true;
	}

}//END OF CLASS
?>
