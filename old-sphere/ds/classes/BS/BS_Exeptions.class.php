<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */
class BS_Exeption extends Exeption {
	
	/** Текст ошибки  */
	protected $msg ='';
	
	/** Метод и класс откуда бросили исклбчение  */
	protected $fromMethod = '';
	
	protected $debug = '';
	
	function __construct ( $msg, $debug = '' )
	{
		assert ( !empty ( $msg ) );
		assert ( is_array ( $debug ) );	
		
	}
}//END OF CLASS BS_Exeption

class BS_ErrorExeption extends BS_Exeption {
}//END OF CLASS BS_ErrorExeption

class BS_WarningExeption extends BS_Exeption {
}//END OF CLASS BS_WarningExeption

class BS_NoticeExeption extends BS_Exeption {
}//END OF CLASS BS_NoticeExeption

class BS_NoticeExeption extends BS_Exeption {
}//END OF CLASS BS_NoticeExeption


class BS_AssertExeption extends BS_Exeption {
}//END OF CLASS BS_NoticeExeption

?>