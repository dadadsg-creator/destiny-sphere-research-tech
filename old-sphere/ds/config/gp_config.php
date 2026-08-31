<?php
require_once($_SERVER['DOCUMENT_ROOT']."/ds/Main.php");

/**
 * Основные настройки для клиента
 */
define('GATE_KEY_DB_TYPE',	'PostgreSQL'); 	// Тип используемой базы данных
define('GATE_KEY_DB_HOST',	$GLOBALS['dbWorldAccess'][0]['host']);// Хост базы
define('GATE_KEY_DB_NAME',	$GLOBALS['dbWorldAccess'][0]['dbname']);	// имя базы
define('GATE_KEY_DB_USER',	$GLOBALS['dbWorldAccess'][0]['user']); 		// имя пользоватедя
define('GATE_KEY_DB_PASS',	$GLOBALS['dbWorldAccess'][0]['password']); // пароль
define('GATE_KEY_DB_TABLE',	'ds_gate_keys');// название таблицы, в которой храняться ключи
define('GATE_BAD_KEY_DB_TABLE',	'ds_gate_bad_keys');// название таблицы, в которую будут записываться ошибочные ключи
													// 		если пустая строка, то не используется
define('GATE_INTERLOCUTOR', 'ds_gate_slave'); // таблица объектов, с которыми гейт общается
define('GATE_KEY_DB_PORT',	'5432'); // порт базы данных
									 // 		если пустая строка, то используется порт по умолчанию
define('GATE_KEY_MAGIC',	'DS2'); // Название клиента
define('GATE_KEY_COUNT', 100);		 // Сколько ключей генерировать
define('GATE_KEY_ONLY_POST_DATA', true); // использовать только данные передаваемые через POST
define('GATE_KEY_LOG_FILE', $_SERVER['DOCUMENT_ROOT'].'/ds/logs_1/GATE_KEYS_Exception.log');	// файл для логирования ощибок
define('GATE_KEY_DEBUG', false);	 // режим отладки (будет выводиться полная информация об exception)

define('GATE_KEY_EXPIRE_TIME',	7200); // Сколько по времени будут действовать ключи ( в секундах )
?>