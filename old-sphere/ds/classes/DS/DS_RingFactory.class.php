<?php $CVS_Version = '$Revision$ ; $Date$';

/** 
 * Статитческий класс для производства объектов DE_Ring
 * Главное назначение этоне производить дублирующих объектов.
 * Для решения этой рпоблемы в глобальнйо области храниться список всех созданых комбов.
 */
class DS_RingFactory {
	
	
	/**
	* @return Возвращает экземпляр класса 
	* @param $id ID создаваемой.
	* @desc Внести описание здесь...
	*/
	function createFromId ( $id ){
		
		settype ( $id, 'integer' );
		assert ( $id > 0 );
		
		$rs = DS_CombFactory::isCreated ( $id );
		
		if ( !$rs ){
			$GLOBALS['RingsCreatedByFactory'][$id] = new DE_Ring ( $id );
			return $GLOBALS['RingsCreatedByFactory'][$id];
		}else{
			return $rs;
		}		
	}//END OF METHOD
	
	/**
	* @return Возвращает экземпляр класса 
	* @param $number номер создаваемой кольца
	* @desc Внести описание здесь...
	*/
	function createFromNumber ( $number ){
		
		settype ( $number, 'integer' );
		assert ( $number > 0 );
		
		$dsRing = new DS_Ring ();
		$id = $dsRing->getIdByNumber ( $number );
		
		if ( !$id ) {
			ds_error ('DS_RingFactory::createFromNumber - Cant get ID for ringnumber '.$number);
		}
				
		$rs = DS_RingFactory::isCreated ( $id );
		
		if ( !$rs ){
			$GLOBALS['RingsCreatedByFactory'][$id] = new DE_Ring ( $id );
			return $GLOBALS['RingsCreatedByFactory'][$id];
		}else{
			return $rs;
		}		
	}//END OF METHOD
	
	/**
	* @return unknown
	* @param $id кольца дял проверки
	* @desc Проеряет был ли созда ужетакой комб, елси да, то возвращает ссылку на объект, 
	* если нет то возвращает фалс.
	*/
	function isCreated ( $id ) {
logAll ( Dump($GLOBALS['RingCreatedByFactory']),'RingCreatedByFactoryClassPHP');
		if ( is_object ( $GLOBALS['RingCreatedByFactory'][$id] ) ){
			return $GLOBALS['RingCreatedByFactory'][$id];
		}else{
			return false;
		}
	}//END OF METHOD
	
}//END O CLASS


?>