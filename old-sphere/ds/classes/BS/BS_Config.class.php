<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Статический класс для хранения конфигурационных параметров
 * Все параметы хроняться в виде статического масива params
 * 
 * Он имеет  следующую струтуру:
 * 
 * var $params = array (
 *    'SECTION_NAME' => array (  //Параметры разбиты на секции
 *        'PARAM_NAME' => PARAM_VALUE,
 *    )
 * )
 * 
 * На данный момент туда заносяться следжующие параметры:
 * 
 * array(
 *    'ERROR' => array (
 *        'HANDLING' = > array (
 *            'ERROR' => 'STOP',
 *            'WARNING' => 'MESSAGE'
 *        )
 *    )
 * )
 * @version 1.0
 * @created 24-сен-2004 12:36:56
 */
class BS_Config
{

	/**
	 * Массив которы йи содержит в сбее все конфигурационные параметры. Пордробнее в
	 * опсиание клсаа.
	 */
	private static $params=array (
				   'ERROR' => array (
				        'HANDLING' => array (
					           'ERROR' => 'STOP',
					           'WARNING' => 'MESSAGE'
					       	 	)
						),
					'LOG'=>array(
						   'MAX_SIZE' => 8196,
						   'PATH' => ''
							)
 				);

	/**
	 * Метод возвращяет значение требуемого конфигурационного параметра
	 * @param section    Имя секции
	 * @param param    Имя параметра
	 * 
	 */
	public static function get($section, $param)
	{
		//Получет два обязательных параметра,
		//проверят на существование эти два
		//параметра и выозвращает х значение
 		if (isset(BS_Config::$params[$section][$param]))
		{
			return BS_Config::$params[$section][$param];
		}
		return false;
	}

}
?>