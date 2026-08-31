<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 *  ласс контейнер содержащий некие глобальные экземпл€ры классов
 *  ласс создает экземпл€ры всех этих классов, хранит в себе, так жезаписывает в GLOBALS
 * под старыми именами, дл€ совместимости оставоленно. 
 */

class GB_GlobalContainer 
{
	
	/** ID мира с корым работаем */
	public $worldId = 0;
	
	/** ID Ѕƒ с которой работаем */
	public $dbId = 0;
	
	/**ID языка с которым работаем  */
	public $langId = 0;
		
	/**
	* @param $dbId - ID базы с которой работаем
	* @param $worldId - ID мира с коорым работаем
	* @desc конструтор класса
	*/
	function GB_GlobalContainer($dbId, $worldId, $langId)
	{
		
		settype ( $dbId, 'integer' );		
		$this->dbId = $dbId;
		
		settype ( $worldId, 'integer' );
		assert ( $worldId > 0 );
		$this->worldId = $worldId;
		
		settype ( $langId, 'integer' );
		assert ( $langId > 0 );
		$this->langId = $langId;					
						
		//—оздаем экземпл€р класс дл€ работы с Ѕƒ
		$this->createCommon();
						
	}//END OF METHOD
	
	
   /**
	* @return Ёкземпл€р ds_common подключенной к необходимой Ѕƒ
	* @desc ¬озвращаетссылку на экземпл€р DS_Common подключенный уже к нужной Ѕƒ
	*/
	function getCommon(){
		
		if ( !key_exists ( 'common', $GLOBALS ) ){
			return $this->createCommon ();
		}
		
		if(!($GLOBALS['common'] instanceof  DS_Common))
		{
			return $this->createCommon ();
		}
		
		return $GLOBALS['common'];
		
	}//END OF METHOD	       
		
	/**
	* @return Ёкземпл€р ds_common подключенной к необходимой Ѕƒ
	* @desc —озадет экземпл€р ds_Common и помещает его в GLOBALS
	*/
	function createCommon ()
	{		
			
		$GLOBALS['common'] = DS_Common::CreateForCurentDB();	
		return $GLOBALS['common'];
		
	}//END OF METHOD				
}//END OF CLASS

?>