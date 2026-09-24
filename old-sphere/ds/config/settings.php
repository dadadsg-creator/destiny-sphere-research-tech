<?php

/** Пути  */
define ( "LOCK_PATH", "/home/www/ds/lockedevents/"); //Путь куда складываем лок фацлы
define ( "DB_CACHE_PATH", "/home/www/ds/db_cache/"); //Путь для файлов кеша БД
define ( "SESSION_PATH", "/tmp/" ); //Путь до файлов сессий
define ( "LOGING_BUG_TRACE_PATH", '/usr/local/www/htdocs/portal/ds/bug_trace/'); //Фалы  с данными для импорта в сиситему контроля ошибок

define ( "LOGING_DEBUG_LOGS_PATH", '/usr/local/www/htdocs/portal/logs/'); //Путь куда может класт логи сиситемы.
define ( "LOGING_SYSTEM_ERROR_LOGS_PATH", '/home/www/ds/httplogs/'); //Путь к лог файлам с ошибками сервера
define ( "LOGING_SMS_PATH", '/home/www/ds/gate/'); //Путь к лог файлам смсок
define ( "PHP_PATH", '/usr/local/bin/php'); //Путь до php

$GLOBALS['LOGING_DEBUG_LOGS_PATH'] = array(
    'MAIN' => '/home/www/ds/logs_1/', //Долждне быть обезательно
    'SLAVE' => '/home/www/ds/logs_2/', //Должен быть обезательно
    'BACKUP' => '/home/www/ds/logs_backup/', //Должен быть обезательно
    'CLASSIC' => '/home/www/ds/log_classic/'
);

$uid = posix_getuid();
if ($uid==201)
    $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'] = '/home/www/ds/log_unlim/';
else if ($uid==200)
    $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'] = '/home/www/ds/log_classic/';
else if ($uid==202)
    $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'] = '/home/www/ds/log_pulsar/';

if ($uid==201 || $uid==33 && ($_SERVER['SERVER_ADDR'] == '94.242.35.154' || $_SERVER['SERVER_ADDR'] == '94.242.60.31')) {
    $GLOBALS['dbWorldAccess'] = array
    (//Доступ к игровым базам

        0 => array
        ( //База с нулевым инлдексом считаеться мастером
            'host' => '94.242.60.26',
            'dbname' => 'unlim',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'master',
            'dir' => '/ds/',
            'shortname' => 'gc05'
        ),

        1 => array
        (
            'host' => '94.242.60.26',
            'dbname' => 'orel',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'orel',
            'dir' => '/ds/',
            'shortname' => 'g11'
        ),

        2 => array
        (
            //'host' => 'gamec04',
            'host' => '94.242.60.26',
            'dbname' => 'drakon2',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'level_2_drk',
            'dir' => '/ds/',
            //'shortname' => 'db17'
            'shortname' => 'gc05'
        ),

        3 => array
        (
            'host' => '94.242.60.26',
            'dbname' => 'telez2',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'level_3_tlc',
            'dir' => '/ds/',
            'shortname' => 'g11'
        )
    );
    $GLOBALS['MySQLBases'] = array
    (
        'LOCKING' => array
        (
                //'host' => '77.232.232.194',
                'host' => '94.242.60.26',
                'dbname' => 'portal_unlim',
                //'host' => '192.168.8.2',
        'user' => 'ruportal',
                'password' => 'REDACTED_DB_PASSWORD'
        )
    );
    /** Параметры доступа к БД */

    $GLOBALS['dbForum'] = array
    (//ДОступ к форумной БД
        'host' => '94.242.60.26',
        'dbname' => 'forum',
        'user' => 'dsgame',
        'password' => 'REDACTED_DB_PASSWORD'
    );
    define ("GAME_HOST", 'http://ds-game.ru' ); //Игровой хост
    define ("VERSION_NAME", "RU_UNLIM" ); // название версии
    define ("CACHING_PREFIX", "unlim_" ); //Префикс к меткам кеша
    define ('BILLING_PARTNER_ID',  '6'); //ID партнера в биллинге 4 - классика, 6 - анлим
    define ('SPONSORPAY_ID',  '283'); //ID игры в Sponsorpay
    define ('FREETOPAY_ID',  '26'); //ID игры в Freetopay
    define ('TEMPLATES_PATH', '/home/www/ds/templates/');
    define ( "URL_FORUM_REGISTRATION_GATE", "http://forum.ds-game.ru/reg_gate.php"); //Путь гейту форума для регистрации
    define ( "CAN_ENTER_WITHOUT_ACTIVATION", 2); // Cколько дней можно заходить без активации мыла
}
else if ($uid==202 || $uid==33 && ($_SERVER['SERVER_NAME']=='ds-game.su' || $_SERVER['SERVER_NAME']=='www.ds-game.su')) {
    $GLOBALS['dbWorldAccess'] = array
    (//Доступ к игровым базам

        0 => array
        ( //База с нулевым инлдексом считаеться мастером
            'host' => '94.242.60.26',
            'dbname' => 'pulsar_otkat',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'pulsar',
            'dir' => '/ds/',
            'shortname' => 'pulsar'
        )
    );

    $GLOBALS['MySQLBases'] = array
    (
        'LOCKING' => array
        (
                'host' => '94.242.60.26',
                'dbname' => 'pulsar',
                'user' => 'ruportal',
                'password' => 'REDACTED_DB_PASSWORD'
        )
    );

    define ("GAME_HOST", 'http://ds-game.su' ); //Игровой хост
    define ("VERSION_NAME", "LIMITED" ); // название версии
    define ("CACHING_PREFIX", "pulsar_" ); //Префикс к меткам кеша
    define ('BILLING_PARTNER_ID',  '9'); //ID партнера в биллинге 4 - классика, 6 - анлим
    define ('SPONSORPAY_ID',  '284'); //ID игры в Sponsorpay
    define ('FREETOPAY_ID',  '28'); //ID игры в Freetopay
    define ('TEMPLATES_PATH', '/home/www/ds/templates/classic/');
    define ( "URL_FORUM_REGISTRATION_GATE", "http://forum.ds-game.ru/reg_gate.php"); //Путь гейту форума для регистрации
    define ( "CAN_ENTER_WITHOUT_ACTIVATION", 2); // Cколько дней можно заходить без активации мыла
}

else if ($uid==200 || $uid==33 && ($_SERVER['SERVER_ADDR'] == '94.242.35.153' || $_SERVER['SERVER_ADDR'] == '94.242.60.30')) {

    $GLOBALS['dbWorldAccess'] = array
    (//Доступ к игровым базам

        0 => array
        ( //База с нулевым инлдексом считаеться мастером
            'host' => '94.242.60.26',
            'dbname' => 'classic',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'master',
            'dir' => '/ds/',
            'shortname' => 'gc05'
        ),

        1 => array
        (
            'host' => '94.242.60.26',
            'dbname' => 'parusa',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'phoenix',
            'dir' => '/ds/',
            'shortname' => 'g11'
        ),
        2 => array
        (
            'host' => '94.242.60.26',
            'dbname' => 'eridan',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'eridan',
            'dir' => '/ds/',
            'shortname' => 'g11'
        ),
        3 => array
        (
            'host' => '94.242.60.26',
            'dbname' => 'lion',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'lion',
            'dir' => '/ds/',
            'shortname' => 'g11'
        ),
        4 => array (
            'host' => '94.242.60.26',
            'dbname' => 'voron',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'voron',
            'dir' => '/ds/',
            'shortname' => 'db11'
        ),

        5 => array (//!!!!!!!!!
            'host' => '94.242.60.26',
            'dbname' => 'orion',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'orion',
            'dir' => '/ds/',
            'shortname' => 'db2_2'
        ),

        6 => array (
            'host' => '94.242.60.26',
            'dbname' => 'zmei',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'premium',
            'dir' => '/ds/',
            'shortname' => 'db8'
        ),

        7 => array (
            'host' => '94.242.60.26',
            'dbname' => 'vodolei',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'vodolei',
            'dir' => '/ds/',
            'shortname' => 'db7'
        ),

        8 => array (
            'host' => '94.242.60.26',
            'dbname' => 'strelez',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'beta',
            'dir' => '/ds/',
            'shortname' => 'db10'
        ),

        9 => array (
            'host' => '94.242.60.26',
            'dbname' => 'blackhole',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'hell',
            'dir' => '/ds/',
            'shortname' => 'db14'
        ),

        10 => array (
            'host' => '94.242.60.26',
            'dbname' => 'teranova',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'priemka',
            'dir' => '/ds/',
            'shortname' => 'g15'
        ),

        11 => array (
            'host' => '94.242.60.26',
            'dbname' => 'volk',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'volk',
            'dir' => '/ds/',
            'shortname' => 'g12'
        ),

        12 => array (
            'host' => '94.242.60.26',
            'dbname' => 'edinorog',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'edinorog',
            'dir' => '/ds/',
            'shortname' => 'gdb1'
        ),

        13 => array (
            'host' => '94.242.60.26',
            'dbname' => 'turnir',
            'user' => 'dsgame',
            'password' => 'REDACTED_DB_PASSWORD',
            'server' => 'turnir',
            'dir' => '/ds/',
            'shortname' => 'db17'
        )
    );

    $GLOBALS['MySQLBases'] = array
    (
        'LOCKING' => array
        (
                'host' => '94.242.60.26',
                'dbname' => 'portal_ru',
                'user' => 'ruportal',
                'password' => 'REDACTED_DB_PASSWORD'
        ),
    );

    $GLOBALS['dbForum'] = array
    (//ДОступ к форумной БД
        'host' => '94.242.60.26',
        'dbname' => 'forum_ru',
        'user' => 'dsgame',
        'password' => 'REDACTED_DB_PASSWORD'
    );

    define ( "GAME_HOST", 'http://destinysphere.ru' ); //Игровой хост
    define ( "VERSION_NAME", "RU_CLASSIC" ); // название версии
    define ( "CACHING_PREFIX", "classic_" ); //Префикс к меткам кеша
    define ('BILLING_PARTNER_ID',  '4'); //ID партнера в биллинге 4 - классика, 6 - анлим
    define ('SPONSORPAY_ID',  '284'); //ID игры в Sponsorpay
    define ('FREETOPAY_ID',  '28'); //ID игры в Freetopay
    define ('TEMPLATES_PATH', '/home/www/ds/templates/classic/');
    define ( "URL_FORUM_REGISTRATION_GATE", "https://forum.destinysphere.ru/reg_gate.php"); //Путь гейту форума для регистрации
    define ( "CAN_ENTER_WITHOUT_ACTIVATION", 10); // Cколько дней можно заходить без активации мыла
}

/** URLs */
define ( "URL_BEGINNERS_HELP", "http://test.destinysphere.ru/ds/help/index.php?js=1&name=INTERFACE_HELP"); //Путь к фацлу с хелпом для начинающих

/** НАтройки часовых поясов */
define ( "DEFAULT_TIMEDELTA", 3);
define ( "TIME_DELTA_DELTA", -3);
define ( "FORUM_TIMEZONE", 3);

/** Настройки работы форума */
define ( "FORUM_REGISTRATION_THROUGH_GATE", 1); // Регистрация на форуме через гейт или на прямую
define ( "FORUM_LANGUAGE", "ru"); //Язык орума
define ( "FORUM_PREFIX", 'phpbb'); //Префикс таблиц форума

/** Настройки работы входа и игры */
define ( "LOCAL_LOGIN_ENABLE", true); //Можно ли входить через индекс логин
define ( "TRIAL_AFTER_REGISTRATION", 180); //Премиумный триал в днях после регистрации
define ( "ONE_WORLD_REGISTRATION_ONLY", 1); // Режим работы игры когда можно регистрироваться только в одном мире
define ( "REGISTRATION_ENABLED", "true" ); //Включение регистрации
define ( "FORUM_REGISTRATION_ENABLED", true ); // Афтоматическая регистрация в форуме
define ( "ACTIVATION_EMAIL_ENABLED", true ); // ВЫсылаем активацию по почте
define ( "DEFAULT_WORLD", 2); //Мир по умолчания для расчета рейтинга
define ( "DEFAULT_LANGUAGE", 3 ); //язsк версии игры по умолчанию еще раз
define ( "DEFAULT_LANGUAGE_SHORT", 'R' ); //язsк версии игры по умолчанию
define ( "DEFAULT_PARTNER", 2); // --------- см в бд ds_userhaspartner
define ( "DEFAULT_CHARSET", "windows-1251"); //Кодировка контента по умолчанию
define ( "MIN_VACATION_TIME_HOURS", 3); //Минимальное время отпуска
define ( "PREMIUM_SUPPORT_EMAIL", "sup@destinysphere.com" ); // адрес премиум суппорта
define ( "SUPPORT_EMAIL", "sup@destinysphere.com" ); // адрес не-премиум суппорта
define ( "MAX_PREMIUM_SUPPORT_MESSAGES", 10 ); // лимит сообщений премиум суппорта
define ( "MAX_SUPPORT_MESSAGES", 3  ); // лимит сообщений не-премиум суппорта
define ( "DISABLE_DETECT_PLAYER", true ); // отключение детект плаера


/** Секция на сроект отвечает за настройки локинга */
define ( "USERACTION_LOCK_TIME_OUT", 600); // Врея усаревания лока
define ( "USER_LOCK_DELAY", 5); //Время лока пользователя после обнаружения локинга каког то

/** Настройки кеширования */
define ( "ZPS_INSTALLED", 1 ); //Включение или выключение кеширования
define ( "MEM_CACHE_HOST", "localhost"); //Хост для мемкеша
define ( "DB_CACHE_DEFAULT_TIME_OUT", 10800); //Время жизни записи в кеше по умолчанию
define ( "CACHE_SYSTEM_NAME", "MEM"); //Имя текущей исопльзуемой сиситемы кеширования (MEM, DBA, NULL)
define ( "SMARTY_CACHE_PATH",  $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/'); // куда складывать кеши смарти

/** Настройки работы самого PHP для useraction.php */
define ( "PHP_MEMORY_LIMIT", '512M' );//Ограничение памяти для скрипта
define ( "PHP_TIME_LIMIT", 4000); //Ограничение времени исполнения для срипта
define ( "DEBUG_USE_INVARIANT_IN_PUBLIC", 0); //проверку инаринта в паблик методах
define ( "DEBUG_USE_INVARIANT_IN_CONSTRUCT", 0); //проверку инаринта в паблик методах
define ( "DB_DEFAULT_TIMEOUT", 30000 ); //Тйм аут при работе нормальной с БД
define ( "DB_EXTENDED_TIMEOUT", 60000 );//Для отдельныхъ запросов которые подольше

define ( "DELETE_DEAD_NO_BUILDINGS_PERIOD", 3); //Сколько дней может не бть рефреша на соте на которй ничгео не построенно.
define ( "DELETE_DEAD_NO_REFRESH_PERIOD", 50); //Сколько дней может не рефрешиться сота
define ( "DELETE_DEAD_NO_LOGIN_PERIOD", 30); //Сколько дней може не логинться пользователь

/** Настройки защиты по IP для шлюза */
$gate_allowed_ip = array('81.19.71.94', '127.0.0.1', '81.19.71.110', '81.19.68.242', '81.19.68.244', '81.19.71.110', '10.0.3.59', '94.242.35.146');

define ( 'GC_COURSE', 1000);


/** Вкл/выкл Рамблер ИД */
define ( 'ENABLE_RAMBLER_ID', 0);

define ( 'DB_SLOW_QUERY_TIMELIMIT', 1); //Временное ограничение в секундах после которого запрос считаетьсы временным и не логируеться.

define ('SESSION_METHOD',  'file'); //Использование места для хранения сессий(MEM 'memC', MySQL 'db', files 'file')

define ('FREETOPAY_ApiSecretKeyA', 'REDACTED_API_SECRET');
define ('FREETOPAY_ApiSecretKeyB', 'REDACTED_API_SECRET');

?>
