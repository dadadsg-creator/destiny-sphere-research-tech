<?php
/**
 * ќсновные настройки дл€ мастера
 *
 */
define('GATE_KEY_DB_TYPE',	'PostgreSQL'); 	// “ип используемой базы данных
define('GATE_KEY_DB_HOST',	'gamedb14.destinysphere.ru');// ’ост базы
define('GATE_KEY_DB_NAME',	'destiny3');	// им€ базы
define('GATE_KEY_DB_USER',	'dsgame'); 		// им€ пользоватед€
define('GATE_KEY_DB_PASS',	'REDACTED_DB_PASSWORD'); // пароль
define('GATE_KEY_DB_TABLE',	'ds_gate_keys'); // название таблицы, в которой хран€тьс€ ключи
define('GATE_BAD_KEY_DB_TABLE',	'ds_gate_bad_keys');// название таблицы, в которую будут записыватьс€ ошибочные ключи
													// 		если пуста€ строка, то не используетс€
define('GATE_KEY_DB_PORT',	'5432'); // порт базы данных
									 // 		если пуста€ строка, то используетс€ порт по умолчанию
define('GATE_KEY_MAGIC',	'TEST'); // Ќазвание клиента
define('GATE_KEY_EXPIRE_TIME',	7200); // —колько по времени будут действовать ключи ( в секундах )

define('GATE_KEY_COUNT', 100);

define('GATE_KEY_ONLY_POST_DATA', true);

define('GATE_KEY_LOG_FILE', '/home/www/htdocs/test/ds/logs_1/GATE_KEYS_Exception.log');

define('GATE_KEY_DEBUG', true);
?>