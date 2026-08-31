<?php

/**
 * Локализация ответных СМС сообщений для всех мобильных сервисов
 * переводы храняться в файле /language/X/sms.lang
 *
 */
class SMS_Localization
{
	static private $currentPartner;
	
	static private $partnerLang = array(
			0 => 'R',
			1 => 'R',
			2 => 'L',
		);
		
	static private $lang = array();
		
	/**
	 * Инициализация, определения языка в зависимости от версии
	 *
	 */
	static public function Init()
	{
		switch (constant('VERSION_NAME'))
		{
			// локаль
			case 'LOCAL':				
			case 'RU_CLASSIC':
			case 'RU_UNLIM':
			case 'TEST':
				self::SetPartner(0);
				break;
				
			case 'ITALY':				
				self::SetPartner(0);
				break;
		
			case 'GERMANY':
				self::SetPartner(0);
				break;
				
			case 'LITHUANIA':
				self::SetPartner(2);
				break;
				
			default:
				self::SetPartner(0);
				break;
		}
	}
	
	static public function SetPartner($partner)
	{
		assert(is_int($partner) && $partner >= 0);
		assert(key_exists($partner, self::$partnerLang));
		
		self::$currentPartner = $partner;
		
		if(!key_exists(self::$currentPartner, self::$lang))
		{
			require_once($_SERVER['DOCUMENT_ROOT'].'/ds/language/'.self::$partnerLang[self::$currentPartner].'/sms.lang');
			assert(is_array($lang) && count($lang) > 0);
			self::$lang[self::$currentPartner] = $lang;
		}
	}
	
	/**
	 * Получить перевод по ключю, для сообщений без параметров
	 *
	 * @param string $tag ИД
	 * @param bool $exitOnError выход в случае отсутсвия данного ключа
	 * @return string
	 */
	static public function Get($tag, $exitOnError = false)
	{
		if(!key_exists($tag, self::$lang[self::$currentPartner]))
		{
			if($exitOnError)
			{
				die();
			}
			
			return $tag;
		}
		
		return self::$lang[self::$currentPartner][$tag];
	}
	
	
	/**
	 * Перевод строки, т.е. замена тегов вида {TEXT} на локализовааную фразу
	 *
	 * @param string $msg
	 * @return string
	 */
	static public function Localize($msg)
	{
		if(!preg_match_all('/\{([A-Z_0-9a-z]+)\}/', $msg, $res))
		{
			return $msg;
		}
		
		foreach ($res[1] as $k => $key)
		{
			if(!key_exists($key, self::$lang[self::$currentPartner]))
			{
				continue;
			}
			
			$msg = str_replace('{'.$key.'}', self::$lang[self::$currentPartner][strtoupper($key)], $msg);
		}
		
		return $msg;
	}
}

?>