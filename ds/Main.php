<?PHP
/**
 * Файл подключаемый ковсем скриптам в сиситеме.
 * Делает все стартовые устанвоки.
 */
/******************** Секция с натройками PHP *********/
//Настройка работы  ASSERT
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);
assert_options(ASSERT_BAIL, 1);

$sapi = php_sapi_name();
if( $sapi != "apache" )
{
    if( !isset( $_SERVER['DOCUMENT_ROOT'] ) || empty( $_SERVER['DOCUMENT_ROOT'] ) )
    {
        $_SERVER['DOCUMENT_ROOT'] = '/home/www';
    }
}


/************ Секция с константами **********************/
define ( 'CLASS_PATH', $_SERVER['DOCUMENT_ROOT'].'/ds/classes/' );
define ( 'INCLUDE_PATH', $_SERVER['DOCUMENT_ROOT'].'/ds/include/' );
define ( 'CONFIG_PATH', $_SERVER['DOCUMENT_ROOT'].'/ds/config/' );


/************ Подключаем базовые классы некоторые ********************/
//Файл с настройками
require_once constant('CONFIG_PATH')."settings.php";
require_once constant('CONFIG_PATH')."settings_secondary.php";
require_once constant('CONFIG_PATH')."settings_errors.php";

//Класс бора сиситемной стаитики
require_once constant('CLASS_PATH')."/GP/GP_SystemStatistic.class.php";
//Базовые функции
require_once constant('INCLUDE_PATH')."/BaseFunctions.inc.php";
//Импор классов
require_once constant('INCLUDE_PATH')."/Import.inc.php";
//Функция старта сесии
require_once constant('INCLUDE_PATH')."/SessionContinue.inc.php";
//Кодирование и декодирование сесии
require_once constant('INCLUDE_PATH')."/DecodeInput.inc.php";
//Обработка завершения
require_once constant('INCLUDE_PATH')."/Finalizer.inc.php";
//Обработчики ошибок
require_once constant('INCLUDE_PATH').'/ErrorHandlers.inc.php';
//Проверка не закрыта ли игра
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';


//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

// error_reporting(E_ALL);

error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));
?>