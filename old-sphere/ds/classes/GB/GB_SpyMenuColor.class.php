<?php

/**
 * Так как кое-кто из клиентских программистов не хочет хранить цвета во флеше,
 * будем их держать на сервере, в этом классе
 *
 */
class GB_SpyMenuColor
{
	/**
	 * Каким цветом обозначать шпионские зонды
	 *
	 * @var unknown_type
	 */
	static public $color = array(
			'SPY_ZOND_SCOUT' => '0x660000',
			'SPY_ZOND_EXPLORER' => '0x006600',
		);
}

?>