<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Статический класс для проверок пред и пост условий,а также для ассертов локальных
 *
 * Пример исопльзования класса выгляидт следущим образом
 * class Test {
 *   ....
 *   function ( $a, $b ) {
 *      $this->invariant();
 *		Assertion::preCondition ( $a > 0 && $b > 0, 'WARNING' );	
 *		Assertion::preCondition ( $a > $b );	
 *      ....
 *      Assertion::assert ( $d < 0 );
 *      ....
 *		Assertion::postCondition ( $c < 0 );
 *		$this->invariant();
 *		return $c
 *   }    
 *   .... 
 * }
 * 
 * В пример так же были включенны исопльзование invariant для того что 
 * бы лишний раз напомнит о его существование
 * 
 * В случае провала одного из assertion кидаетья исключения:
 * В зависимости от уровня ошибки это может быть помток 
 * WarningExeption
 * NoticeExeption
 * ErrorExeption
 * 
 * В зависимости от того какй из методов использовался это будет
 * PreConditionExeption
 * PostConditionExeption
 * AssertExeption
 *
 * Которые являються потомками 
 * AssertionWarningExeption
 * AssertionErrorExeption
 * AssertionNoticeExeption
 */

require_once ('BS_Error.class.php');

class BS_Assertion 
{
	/**
	 * Метод для проверки предусловий.
	 * Параметры: 
	 * $condition - условия срабатывания ассерта
	 * $errorLevel - уровень генерируемой ошибки. Необезательный параметр
	 */
	public static function preCondition ( $condition, $errorLevel = 'ERROR')
	{
		settype ( $condition, 'bool' );
		if(!$condition)
		{
			if ($errorLevel == 'ERROR')
			{
				BS_Error::castError('preCondition','BS_AssertionPreConditionException',6);
			}
			else
			if ($errorLevel == 'WARNING')
			{
				BS_Error::castWarning('preCondition','BS_AssertionPreConditionException',6);
			}
		}		
	}//END OF METHOD require()
	
	/**
	 * Метод для проверки постдусловий.
	 * Параметры: 
	 * $condition - условия срабатывания ассерта
	 * $errorLevel - уровень генерируемой ошибки. Необезательный параметр
	 */
	public static function postCondition ( $condition, $errorLevel = 'ERROR' )
	{
		settype ( $condition, 'bool' );
		if(!$condition)
		{
			if ($errorLevel == 'ERROR')
			{
				BS_Error::castError('postCondition','BS_AssertionPostConditionException',6);
			}
			else
			if ($errorLevel == 'WARNING')
			{
				BS_Error::castWarning('postCondition','BS_AssertionPostConditionException',6);
			}
		}	
	}//END OF METHOD ensure()
	
	/**
	 * Метод для локальных проверок.
	 * Параметры: 
	 * $condition - условия срабатывания ассерта
	 * $errorLevel - уровень генерируемой ошибки. Необезательный параметр
	 */
	public static function assert ( $condition, $errorLevel = 'ERROR' )
	{
		settype ( $condition, 'bool' );
		if(!$condition)
		{
			if ($errorLevel == 'ERROR')
			{
				BS_Error::castError('assert','BS_AssertionAssertException',6);
			}
			else
			if ($errorLevel == 'WARNING')
			{
				BS_Error::castWarning('assert','BS_AssertionAssertException',6);
			}
		}	
	}//END OF METHOD assert()
	
}//END OF CLASS Assertion


?>
