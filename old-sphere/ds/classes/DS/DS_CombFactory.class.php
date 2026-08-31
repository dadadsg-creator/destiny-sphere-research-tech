<?php $CVS_Version = '$Revision$ ; $Date$';


import ('DE_Comb');

/** 
 * Статитческий класс для производства объектов DE_Comb
 * Главное назначение этоне производить дублирующих объектов.
 * Для решения этой рпоблемы в глобальнйо области храниться список всех созданых комбов.
 */
class DS_CombFactory {
	
	
	/**
	* @return Возвращает экземпляр класса 
	* @param $id ID соты создаваемой.
	* @desc Внести описание здесь...
	*/
	function createFromId ( $id ){
		
		settype ( $id, 'integer' );
		assert ( $id > 0 );
		
		$rs = DS_CombFactory::isCreated ( $id );
		
		if ( !$rs ){
			$GLOBALS['CombsCreatedByFactory'][$id] = new DE_Comb ( $id );
			return $GLOBALS['CombsCreatedByFactory'][$id];
		}else{
			return $rs;
		}		
	}//END OF METHOD
	
	
	/**
	* @return unknown
	* @param $id коба дял проверки
	* @desc Проеряет был ли созда ужетакой комб, елси да, то возвращает ссылку на объект, 
	* если нет то возвращает фалс.
	*/
	function isCreated ( $id ) {
		
		if ( !key_exists ( 'CombsCreatedByFactory', $GLOBALS ) ) {
			return false;
		}
		
		if ( !key_exists ( $id, $GLOBALS['CombsCreatedByFactory'] ) ) {
			return false;
		}
		
		if ( is_object ( $GLOBALS['CombsCreatedByFactory'][$id] ) ){
			return $GLOBALS['CombsCreatedByFactory'][$id];
		}else{
			return false;
		}
	}//END OF METHOD
	
}//END O CLASS


?>