<?php
/** НАстрока сиситем длогирования */
define ( "LOGING_DS_COMMON_FLAG", 0); //Логирование всех запросов и результатов
define ( "LOGING_PROTOCOL_FLAG", 0); //Логирование протокола
define ( "LOGING_SYSTEM_ERRORS_FLAG", 1); //Логирование сиситемных ошибок
define ( "LOGING_SYSTEM_STATISTIC_FLAG", 0); //Логирование ресурсозатрат сиситемы
define ( "LOGING_SYSTEM_LOGING_FLAG", 1); //Все логирование в сиситеме
define ( "LOGING_MAX_LOG_FILE_SIZE", 50000000); //Максимальный размер лога системы в байтах 
define ( "LOGING_BUG_TRACE_FLAG", 0); //Включение/выключение сиситемы экспорта данных об ошибках. !!!Временно вобще отключена и в коде!!!
define ( "LOGING_SORCE_CODES", 1); //Включать ли логирование сорцов в истемной ошибке

$GLOBALS['SYSTEM_STATISTIC_LOG_SECTIONS'] = array
(//Описание того что из сиситемно стаитики писать в лог
    'DS_COMMON' => true,
    'DB_CACHE' => true,
    'IMPORT_FILES' => true,
    'USERACTION' => true    
);

$GLOBALS['ACTIONS_FOR_LOG_SYSTEM_STAT'] = array('load', 'refresh', 'empty'); //НА какие действи яститистику записываем влог

$GLOBALS['USERS_FOR_LOG_PROTOCOL'] = array
(//Прользоватлеи для которохы логировать протоокл
    'Annita',
    'lewin11',
//'Again' 
);

$GLOBALS['USERS_FOR_ROLLBACK'] = array
(//П//ользвоатели действия которых не комитяться в БД
    //'Annita',
    //'Anna',
    //'Bursak',
    //'I_Warlock',
    //'Danae'
//'mimikri'
);

$GLOBALS['TEST_SYSTEM_REPORT_EMAILS'] = array
(//Пользвоатели, котрым отсылается отчет о работе Test System Function
    0 => 'annita@destinysphere.com',
    1 => 'nikita@destinysphere.com'
);

//Пользотtль для которого логирутьс ясиситемная информация по боям userForSystemlogingOfBattles
$GLOBALS['USER_FOR_BATTLE_SYSTEM_LOGING'] = '40Min';

//Пользотtль для которого логирутьс изменение атрибутов сот
//если там всего одно имя и это имя = 'ALL_USERS' то логируем для всех
$GLOBALS['USER_FOR_COMB_ATRIBUTES_CHANGE_LOGING'] = array
(
    '',
);

$GLOBALS['COMB_CREATE_FROM_TEMPLATES'] = array (
    1 => array( 
        //0 => 'Raila_1k_race1',
        0 => 'Raila_race1', 
    ),
    2 => array(
        //0 => 'Raila_1k_race2',
        0 => 'Raila2_race2',
    ),
    3 => array( 
        //0 => 'Raila_1k_race3', 
        0 => 'Raila3_race3', 
    )
);


//Список северов с которыми работает сиситема. Используеться для тестирования целостности
$GLOBALS['TEST_SYSTEM_SERVERS_LIST'] = array
(   
    //Сервер HTTP
    '81.19.71.117' => 'dsg',
    
    //Базы данных
    //Мастер
    '81.19.71.121' => 'gc05',
    //Телец 2-й
    '81.19.71.119' => 'gc03',
    //Телец 3-й
    '81.19.71.101' => 'g11',
    //Дракон 2-й
    //'81.19.71.120' => 'gc04',
    '81.19.71.92' => 'db17',
);

/** Колличество пакетов при пинге сервера для теста сиситемы */
define ('TEST_SYSTEM_PING_PACKETS_COUNT', 5);

//URL внешних систем для пероверки отзыва сиситемы проверки работоспособности.
$GLOBALS['TEST_SYSTEMS_OTHER_SYSTEMS_TO_CHECK'] = array
(
    'CL' => 'http://ds.rambler.ru/ds/ExternalTestOfSystem.php',
    'GE' => 'http://www.destinysphere.net/ds/ExternalTestOfSystem.php'

);

//Цвета сиситемных сообщений
$GLOBALS['SYSTEM_MESSAGES_COLORS'] = array
(
    'WHITE' => '222222',
    'RED' => 'A90101',
    'GREEN' => '014B01',
    'BLUE' => '01016D',
    'YELLOW' => 'BD7406',
    'BROWN' => '5C2E01'
);

//Шрифт всех сообщений и не спрашивайте почему это настраиваеться на сервере
define('FONT_NAME', 'Arial');
//потамушта кто-то клиенту html шлёт, ну и там наверно шрифт должен быть


//Миры которым дарим премиум DBID => WORLD_ID => PERIOD
$GLOBALS['WORLDS_WITH_PREMIUM'] = array
(
    0 => array("FROM" => "2008-07-15 15:00:00", "TO" => "2008-09-15 15:00:00"), 
    //10 => array("FROM" => "2006-08-10 10:00:00", "TO" => "2007-09-10 18:00:00"),  
);

$GLOBALS['WORLDS_UNION'] = array 
(
    //'master_2',
);

$GLOBALS['WORLDS_UNION_DBID'] = 99;
$GLOBALS['WORLDS_UNION_ID_WORLD'] = 99;

// из какого мира можно заказать бесплатный переезд
define('FREE_WORLD_TRANSFER_DB_FROM', 99);
define('FREE_WORLD_TRANSFER_ID', 99);

define('PREMIUM_PRESENT_DAYS', 1);              // сколько дней давать в подарок
define('PREMIUM_PRESENT_CAN_TAKE_AFTER', 1);    // через сколько месяцев можно взять подарок еще раз

// какие части страницы index_start показывать
$GLOBALS['INDEX_START_BLOCKS'] = array (
    // логин/мыло
    'ACCOUNT_INFO'      => 1,
    // вход в мир
    'WORLD_ENTER'       => 1,
    // регистрация
    'REGISTRATION'      => 1,
    // защитный код
    'PROTECTION_CODE'   => 0,
    // форма саппорта
    'SUPPORT_FORM'      => 1,
    // информация о премиуме
    'PREMIUM_INFO'      => 1,
    // список всех миров
    'WORLD_COLLECTION'  => 1,
    // переезд между мирами 
    'WORLD_TRANSFER'    => 1,
    // копирование на турнир
    'WORLD_COPY'        => 1,
    // объединение миров
    'WORLD_UNION'       => 0,
    // переезд сот
    'COMB_TRANSFER'     => 1,
    // 
    'DV_REQUEST'        => 1,
);
?>