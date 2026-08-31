<?
/**
 * Вызывается в любом случае только этот файл, при приходе смс в smx.com
 *
 */
require_once('../Main.php');
require_once('classes/File.class.php');
require_once('classes/SMSPayBillingLogger.class.php');

$log = print_r($_REQUEST, true);
$log .= "time=".date("Y-m-d H:i:s")."\n";
$fp = fopen(constant('LOGING_SMS_PATH').'messages.log','a+');
fwrite($fp,$log);
fclose($fp);

require_once('classes/getContent.class.php');
require_once('classes/baseDSMessage.class.php');
require_once('classes/getDSArmy.class.php');
require_once('classes/Properties.class.php');
require_once('./smspay.php');
//require_once('classes/GB_Date.class.php');
//require_once('../settings.php');
//require_once('DS_Common.php');

// Если запрос к биллингу
if (key_exists('xml_data', $_REQUEST) && preg_match('/TransferStatusChanged/si', $_REQUEST['xml_data'])) {
    // УРЛ
    $url = "https://billing.destinysphere.ru/rambler_gate.php";
    echo "OK"; //ответ рамблеру выдаём до инициации вызова скрипта в Rambler+
               //Вызов происходит  в http://billing/Core/utils/rambler_gate.php
    ob_flush();
    
    $params = 'xml_data='.urlencode($_REQUEST['xml_data']);
    $ch = curl_init ($url.'?'.$params);

    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
    ob_start();
    $result = curl_exec($ch);
    ob_end_clean();
    curl_close($ch);
    exit;
}

if (key_exists('debug_mode', $_REQUEST) && $_REQUEST['debug_mode'] == 1)
{
    echo "DEBUG_MODE:\n";
    echo $_SERVER['SERVER_NAME']."<br>\n";
    echo $_SERVER['SERVER_ADDR']."<br>\n";

    ini_set('display_errors', true);
    error_reporting(E_ALL);
}

class DSMessage extends BaseDSMessage
{
    /* Переменные внутреннего назначения */

    /**
     * Класс для вызова скриптов посредством GEt или POST запросов.
     *
     * @var GetContent
     */
    var $getContent;
    var $smsText='';
    // Тип товара
    private $good='';
    // Стоимость смс в рублях (и в евро для немецких кредитов)
    private $sum='';
    // Количество покупаемых кредитов
    private $amount ='';
    // Ник игрока
    private $username='';

    /* Обьявленные типы сообщений, которые могут быть обработаны. Для отключения поставте "!" перед кодом типа сообщения*/
    var $basePriceShortCode = array(
        //Plastic Media
        '1005'  => '250',   // По строму 2.5$ - по новому 3$
        '294'   => '50',    // По строму 0,5$ - по новому 1$
        '2404'  => '250',   // Украина  24,996 грн.
        '7504'  => '250',   // Казахстан 300 KZT
        '7099'  => '50',    // Украина 6.000 grn
        '7502'  => '50',    // Казахстан 150 KZT
        '2332'  => '250',   // Запасной вариант на 3 $ для ООО "Екатеринбург - 2000" (Мотив)
    
        // Зарубеж
        '83094' => '50',    // Германия 0.99 €
        '83090' => '150',   // Германия 1.99 €
        '86669' => '200',   // Германия 2.99 €
        '81130' => '250',   // Германия 4.99 €
        
        '40701' => '250',   // Израиль
        '40705' => '250',   // Израиль
        
        '13015' => '250',   // Эстония
        
        '18971' => '250',   // Латвия
        '18972' => '250',   // Латвия
        '18973' => '250',   // Латвия
        
        '13371' => '250',   // Литва
        '13372' => '250',   // Литва
        '13373' => '250',   // Литва
        
        '0900506506' => '250',  // Австрия 2 €
        '9292' => '250',        // Швейцария 3,6 SFr
        
    
    );

    // временно отключаем СНГ из-за проблем у партнеров
    private $disabledShortCodes = array(/*'+1045', '+9684', '+4280', '+9685', '+3335', '+3355'*/);

    /* НАДО БУДЕТ РЕАЛИЗОВАТЬ В БУДУЩЕМ !
    private $sms_prefix = array(
        'DS', // Классика
        'UKRDS', // Классика Украина
        'DSU', // DS Unlimited префикс DSU
        'UKRDSU', // Unlimited Украина
        'SD', // Казахстан
        'SDU', // DS Unlimited префикс DSU
        'DSL', // DS LIMITED (ds-game.su)
        'UKRDSL' // DS LIMITED (ds-game.su)
    );*/
    
    var $sms_functions = array(
    '!DSMSGG',
    '!DSGLOBAL',
    '!DSMSGR',

    // сообщения в игровую почту
    'DSMSGA', // альянсовое
    'DSMSG',  // личное

    // отправка СМС сообщения игроку
    'DSSMS',

    // для викторины
    'DSPLAY',   'DSPLAYXXX',

    // регистрация с системе SMXCOM
    'ON DSREG',

    // информация о вращении колец
    'DS RING',  'DSRING',

    // пара временных сообщений
    'DS OTVET',
    'DS VOTE',

    // премиум
    'DSPREMIUM', 'DS PREMIUM', 'DSR PREMIUM', 'UKRDS PREMIUM',
    // информация об атаках на соты
    'DS ARMY',   'DSARMY', 'DSR ARMY', 'UKRDS ARMY',
    // инфа о кол-ве свободных археологов
    'DS AINF',   'DSAINF', 'DSR AINF', 'UKRDS AINF',
    // отправка археологов
    'DS ARCH',   'DSARCH', 'DSR ARCH', 'UKRDS ARCH',
    // сортировка артефактов на соте
    'DS ART',    'DSART', 'DSR ART', 'UKRDS ART',
    // конкурс красоты. пока он работает напрямую
    'DS BEAUTY', 'DSBEAUTY', 'DSR BEAUTY', 'UKRDS BEAUTY',
    // покупка игровых кредитов
    'DS CREDIT', 'DSCREDIT', 'DSR CREDIT', 'UKRDS CREDIT',
    'DS 500',    'DS500',     'DSR 500', 'UKRDS 500',

    // Для Казахстана и Украины префикс SD
    'SD PREMIUM', 'SDPREMIUM',
    'SD ART',     'SDART',
    'SD ARMY',    'SDARMY',
    'SD AINF',    'SDAINF',
    'SD ARCH',    'SDARCH',
    'SD BEAUTY',  'SDBEAUTY',

    // DS LIMITED (ds-game.su) префикс DSL
    'DSLPREMIUM',   'DSL PREMIUM',  'UKRDSL PREMIUM', 
    'DSLARMY',      'DSL ARMY',     'UKRDSL ARMY',
    'DSLAINF',      'DSL AINF',     'UKRDSL AINF',
    'DSLARCH',      'DSL ARCH',     'UKRDSL ARCH',
    'DSLART',       'DSL ART',      'UKRDSL ART',
    'DSLBEAUTY',    'DSL BEAUTY',   'UKRDSL BEAUTY',
    'DSLCREDIT',    'DSL CREDIT',   'UKRDSL CREDIT',
    'DSL500',       'DSL 500',      'UKRDSL 500',

    // DS Unlimited префикс DSU
    'DSU PREMIUM',  'DSUPREMIUM', 'DSRU PREMIUM', 'UKRDSU PREMIUM',
    'DSU ART',      'DSUART',   'DSRU ART', 'UKRDSU ART',
    'DSU ARMY',     'DSUARMY',  'DSRU ARMY', 'UKRDSU ARMY',
    'DSU AINF',     'DSUAINF',  'DSRU AINF', 'UKRDSU AINF',
    'DSU ARCH',     'DSUARCH',  'DSRU ARCH', 'UKRDSU ARCH',
    // покупка игровых кредитов
    'DSU CREDIT',   'DSUCREDIT', 'DSRU CREDIT', 'UKRDSU CREDIT',
    'DSU 500',      'DSU500',    'DSRU 500', 'UKRDSU 500',

    // DS Unlimited для Казахстана и Украины префикс SDU
    'SDU PREMIUM',  'SDUPREMIUM',
    'SDU ART',      'SDUART',
    'SDU ARMY',     'SDUARMY',
    'SDU AINF',     'SDUAINF',
    'SDU ARCH',     'SDUARCH',

    'SDU CREDIT',   'SDUCREDIT',

    /*********************** Зарубеж ***********************/
    // Германия
    'PPDS PREMIUM',     'PPDSPREMIUM',  'PPDSU PREMIUM',    'PPDSUPREMIUM',     'PPDSL PREMIUM',    'PPDSLPREMIUM', 'PPDSG PREMIUM',    'PPDSGPREMIUM',
    'PPDS CREDIT',      'PPDSCREDIT',   'PPDSU CREDIT',     'PPDSUCREDIT',      'PPDSL CREDIT',     'PPDSLCREDIT', 
    'PPDS ARMY',        'PPDSARMY',     'PPDSU ARMY',       'PPDSUARMY',        'PPDSL ARMY',       'PPDSLARMY',    'PPDSG ARMY',       'PPDSGARMY',
    'PPDS AINF',        'PPDSAINF',     'PPDSU AINF',       'PPDSUAINF',        'PPDSL AINF',       'PPDSLAINF',    'PPDSG AINF',       'PPDSGAINF',
    'PPDS ARCH',        'PPDSARCH',     'PPDSU ARCH',       'PPDSUARCH',        'PPDSL ARCH',       'PPDSLARCH',    'PPDSG ARCH',       'PPDSGARCH',
    'PPDS ART',         'PPDSART',      'PPDSU ART',        'PPDSUART',         'PPDSL ART',        'PPDSLART',     'PPDSG ART',        'PPDSGART',
    'PPDS 500',         'PPDS500',      'PPDSU 500',        'PPDSU500',         'PPDSL 500',        'PPDSL500', 
    
    // Германия золото для версии de
    'PPDSG CREDIT1',        'PPDSGCREDIT1', 
    'PPDSG CREDIT2',        'PPDSGCREDIT2', 
    'PPDSG CREDIT3',        'PPDSGCREDIT3', 
    
    // Эстония
    'PMDDS PREMIUM',    'PMDDSPREMIUM', 'PMDDSU PREMIUM',   'PMDDSUPREMIUM',    'PMDDSL PREMIUM',   'PMDDSLPREMIUM',
    'PMDDS CREDIT',     'PMDDSCREDIT',  'PMDDSU CREDIT',    'PMDDSUCREDIT',     'PMDDSL CREDIT',    'PMDDSLCREDIT',
    'PMDDS ARMY',       'PMDDSARMY',    'PMDDSU ARMY',      'PMDDSUARMY',       'PMDDSL ARMY',      'PMDDSLARMY',
    'PMDDS AINF',       'PMDDSAINF',    'PMDDSU AINF',      'PMDDSUAINF',       'PMDDSL AINF',      'PMDDSLAINF',
    'PMDDS ARCH',       'PMDDSARCH',    'PMDDSU ARCH',      'PMDDSUARCH',       'PMDDSL ARCH',      'PMDDSLARCH',
    'PMDDS ART',        'PMDDSART',     'PMDDSU ART',       'PMDDSUART',        'PMDDSL ART',       'PMDDSLART',
    'PMDDS 500',        'PMDDS500',     'PMDDSU 500',       'PMDDSU500',        'PMDDSL 500',       'PMDDSL500',
    // Латвия, Литва
    '5PMDS PREMIUM',    '5PMDSPREMIUM', '5PMDSU PREMIUM',   '5PMDSUPREMIUM',    '5PMDSL PREMIUM',   '5PMDSLPREMIUM',
    '5PMDS CREDIT',     '5PMDSCREDIT',  '5PMDSU CREDIT',    '5PMDSUCREDIT',     '5PMDSL CREDIT',    '5PMDSLCREDIT',
    '1PMDS ARMY',       '1PMDSARMY',    '1PMDSU ARMY',      '1PMDSUARMY',       '5PMDSL ARMY',      '5PMDSLARMY',
    '1PMDS AINF',       '1PMDSAINF',    '1PMDSU AINF',      '1PMDSUAINF',       '5PMDSL AINF',      '5PMDSLAINF',
    '1PMDS ARCH',       '1PMDSARCH',    '1PMDSU ARCH',      '1PMDSUARCH',       '5PMDSL ARCH',      '5PMDSLARCH',
    '5PMDS ART',        '5PMDSART',     '5PMDSU ART',       '5PMDSUART',        '5PMDSL ART',       '5PMDSLART',
    '1PMDS 500',        '1PMDS500',     '1PMDSU 500',       '1PMDSU500',        '5PMDSL 500',       '5PMDSL500',
    // Израиль
    'PMD5DS PREMIUM',   'PMD5DSPREMIUM','PMD5DSU PREMIUM',  'PMD5DSUPREMIUM',   'PMD5DSL PREMIUM',  'PMD5DSLPREMIUM',
    'PMD5DS CREDIT',    'PMD5DSCREDIT', 'PMD5DSU CREDIT',   'PMD5DSUCREDIT',    'PMD5DSL CREDIT',   'PMD5DSLCREDIT',
    'PMD1DS ARMY',      'PMD1DSARMY',   'PMD1DSU ARMY',     'PMD1DSUARMY',      'PMD1DSL ARMY',     'PMD1DSLARMY',
    'PMD1DS AINF',      'PMD1DSAINF',   'PMD1DSU AINF',     'PMD1DSUAINF',      'PMD1DSL AINF',     'PMD1DSLAINF',
    'PMD1DS ARCH',      'PMD1DSARCH',   'PMD1DSU ARCH',     'PMD1DSUARCH',      'PMD1DSL ARCH',     'PMD1DSLARCH',
    'PMD5DS ART',       'PMD5DSART',    'PMD5DSU ART',      'PMD5DSUART',       'PMD5DSL ART',      'PMD5DSLART',
    'PMD1DS 500',       'PMD1DS500',    'PMD1DSU 500',      'PMD1DSU500',       'PMD1DSL 500',      'PMD1DSL500',
    
    // Австрия и Шаейцария (Префиксы только для версии DE)
    'PMDDSG PREMIUM',   'PMDDSGPREMIUM', 
    'PMDDSG CREDIT1',   'PMDDSGCREDIT1', // Действует только в Австрии!
    'PMDDSG CREDIT2',   'PMDDSGCREDIT2', // Действует только в Швейцарии!
    'PMDDSG ARMY',      'PMDDSGARMY',   
    'PMDDSG AINF',      'PMDDSGAINF',   
    'PMDDSG ARCH',      'PMDDSGARCH',
    'PMDDSG ART',       'PMDDSGART',    
    );

    var $priceFunction = array(
    'DSMSGA'=>40,
    'DSMSG'=>15,
    'DSSMS'=>15,

    'ON DSREG'=>15,
    'DS RING'=>15,      'DSRING'=>15,
    'DSPLAY'=>15,       'DSPLAYXXX'=>15,

    'DSPREMIUM'=>250,   'DS PREMIUM' => 250,    'DSR PREMIUM' => 250, 'UKRDS PREMIUM' => 250,
    'DS ARMY'=>40,      'DSARMY'=>40,           'DSR ARMY'=>40,       'UKRDS ARMY' => 40,
    'DS AINF'=>15,      'DSAINF'=>15,           'DSR AINF'=>15,       'UKRDS AINF' => 15,
    'DS ARCH'=>40,      'DSARCH'=>40,           'DSR ARCH'=>40,       'UKRDS ARCH' => 40,
    'DS ART'=>250,      'DSART'=>250,           'DSR ART'=>250,       'UKRDS ART' => 250,
    'DS BEAUTY'=>40,    'DSBEAUTY'=>40,         'DSR BEAUTY'=>40,     'UKRDS BEAUTY' => 40,

    'DS CREDIT'=>250,   'DSCREDIT'=>250,        'DSR CREDIT'=>250,    'UKRDS CREDIT' => 250,
    'DS 500'=>50,       'DS500'=>50,            'DSR 500'=>50,        'UKRDS 500' => 50,

    'DS OTVET'=>100,
    'DS VOTE'=>15,

    // Казахские смс-ки
    'SDPREMIUM'=>250,   'SD PREMIUM'=>250,
    'SDART'=>250,       'SD ART'=>250,
    'SDARMY'=>40,       'SD ARMY'=>40,
    'SDAINF'=>15,       'SD AINF'=>15,
    'SDARCH'=>40,       'SD ARCH'=>40,
    'SD BEAUTY'=>40,    'SDBEAUTY'=>40,

    // Лимитед
    'DSLPREMIUM'=>250,  'DSL PREMIUM' => 250,   'UKRDSL PREMIUM' => 250,
    'DSL ARMY'=>40,     'DSLARMY'=>40,          'UKRDSL ARMY'=>40,
    'DSL AINF'=>15,     'DSLAINF'=>15,          'UKRDSL AINF'=>15,
    'DSL ARCH'=>40,     'DSLARCH'=>40,          'UKRDSL ARCH'=>40,
    'DSL ART'=>250,     'DSLART'=>250,          'UKRDSL ART'=>250,
    'DSL CREDIT'=>250,  'DSLCREDIT'=>250,       'UKRDSL CREDIT'=>250,
    'DSL 500'=>50,      'DSL500'=>50,           'UKRDSL 500'=>50,

    // Анлимитед
    'DSUPREMIUM'=>250,  'DSU PREMIUM' => 250,   'DSRU PREMIUM' => 250, 'UKRDSU PREMIUM' => 250,
    'DSU ARMY'=>40,     'DSUARMY'=>40,          'DSRU ARMY'=>40,       'UKRDSU ARMY'=>40,
    'DSU AINF'=>15,     'DSUAINF'=>15,          'DSRU AINF'=>15,       'UKRDSU AINF'=>15,
    'DSU ARCH'=>40,     'DSUARCH'=>40,          'DSRU ARCH'=>40,       'UKRDSU ARCH'=>40,
    'DSU ART'=>250,     'DSUART'=>250,          'DSRU ART'=>250,       'UKRDSU ART'=>250,

    'DSU CREDIT'=>250,  'DSUCREDIT'=>250,       'DSRU CREDIT'=>250,    'UKRDSU CREDIT'=>250,
    'DSU 500'=>50,      'DSU500'=>50,           'DSRU 500'=>50,        'UKRDSU 500'=>50,

    // Казахские смс-ки анлимитед
    'SDUPREMIUM'=>250,  'SDU PREMIUM'=>250,
    'SDUART'=>250,      'SDU ART'=>250,
    'SDUARMY'=>40,      'SDU ARMY'=>40,
    'SDUAINF'=>15,      'SDU AINF'=>15,
    'SDUARCH'=>40,      'SDU ARCH'=>40,

    'SDU CREDIT'=>250,  'SDUCREDIT'=>250,

    'LT_DS PREMIUM'=>'8.00',    'LT_DSPREMIUM' => '8.00',
    'LT_DS ARMY'=>'2.00',       'LT_DSARMY'=>'2.00',
    'LT_DS AINF'=>'1.00',       'LT_DSAINF'=>'1.00',
    'LT_DS ARCH'=>'2.00',       'LT_DSARCH'=>'2.00',
    'LT_DS ART'=>'2.00',        'LT_DSART'=>'2.00',
    'LT_DS CREDIT'=>'5.00',     'LT_DSCREDIT'=>'5.00',

    /*********************** Зарубеж ***********************/
    // Германия
    'PPDS PREMIUM'=>250,    'PPDSPREMIUM'=>250, 'PPDSU PREMIUM'=>250,   'PPDSUPREMIUM'=>250,    'PPDSL PREMIUM'=>250,   'PPDSLPREMIUM'=>250,'PPDSG PREMIUM'=>250,   'PPDSGPREMIUM'=>250,
    'PPDS CREDIT'=>250,     'PPDSCREDIT'=>250,  'PPDSU CREDIT'=>250,    'PPDSUCREDIT'=>250,     'PPDSL CREDIT'=>250,    'PPDSLCREDIT'=>250, 
    'PPDS ARMY'=>50,        'PPDSARMY'=>50,     'PPDSU ARMY'=>50,       'PPDSUARMY'=>50,        'PPDSL ARMY'=>50,       'PPDSLARMY'=>50,    'PPDSG ARMY'=>50,       'PPDSGARMY'=>50,
    'PPDS AINF'=>50,        'PPDSAINF'=>50,     'PPDSU AINF'=>50,       'PPDSUAINF'=>50,        'PPDSL AINF'=>50,       'PPDSLAINF'=>50,    'PPDSG AINF'=>50,       'PPDSGAINF'=>50,
    'PPDS ARCH'=>50,        'PPDSARCH'=>50,     'PPDSU ARCH'=>50,       'PPDSUARCH'=>50,        'PPDSL ARCH'=>50,       'PPDSLARCH'=>50,    'PPDSG ARCH'=>50,       'PPDSGARCH'=>50,
    'PPDS ART'=>250,        'PPDSART'=>250,     'PPDSU ART'=>250,       'PPDSUART'=>250,        'PPDSL ART'=>250,       'PPDSLART'=>250,    'PPDSG ART'=>250,       'PPDSGART'=>250,
    'PPDS 500'=>50,         'PPDS500'=>50,      'PPDSU 500'=>50,        'PPDSU500'=>50,         'PPDSL 500'=>50,        'PPDSL500'=>50,
    
    // Германия золото для версии de
    'PPDSG CREDIT1'=>150,       'PPDSGCREDIT1'=>150, 
    'PPDSG CREDIT2'=>200,       'PPDSGCREDIT2'=>200, 
    'PPDSG CREDIT3'=>250,       'PPDSGCREDIT3'=>250, 
    
    
    // Эстония
    'PMDDS PREMIUM'=>250,   'PMDDSPREMIUM'=>250,'PMDDSU PREMIUM'=>250,  'PMDDSUPREMIUM'=>250,   'PMDDSL PREMIUM'=>250,  'PMDDSLPREMIUM'=>250,
    'PMDDS CREDIT'=>250,    'PMDDSCREDIT'=>250, 'PMDDSU CREDIT'=>250,   'PMDDSUCREDIT'=>250,    'PMDDSL CREDIT'=>250,   'PMDDSLCREDIT'=>250,
    'PMDDS ARMY'=>250,      'PMDDSARMY'=>250,   'PMDDSU ARMY'=>250,     'PMDDSUARMY'=>250,      'PMDDSL ARMY'=>250,     'PMDDSLARMY'=>250,
    'PMDDS AINF'=>250,      'PMDDSAINF'=>250,   'PMDDSU AINF'=>250,     'PMDDSUAINF'=>250,      'PMDDSL AINF'=>250,     'PMDDSLAINF'=>250,
    'PMDDS ARCH'=>250,      'PMDDSARCH'=>250,   'PMDDSU ARCH'=>250,     'PMDDSUARCH'=>250,      'PMDDSL ARCH'=>250,     'PMDDSLARCH'=>250,
    'PMDDS ART'=>250,       'PMDDSART'=>250,    'PMDDSU ART'=>250,      'PMDDSUART'=>250,       'PMDDSL ART'=>250,      'PMDDSLART'=>250,
    'PMDDS 500'=>250,       'PMDDS500'=>250,    'PMDDSU 500'=>250,      'PMDDSU500'=>250,       'PMDDSL 500'=>250,      'PMDDSL500'=>250,
    // Латвия, Литва
    '5PMDS PREMIUM'=>250,   '5PMDSPREMIUM'=>250,'5PMDSU PREMIUM'=>250,  '5PMDSUPREMIUM'=>250,   '5PMDSL PREMIUM'=>250,  '5PMDSLPREMIUM'=>250,
    '5PMDS CREDIT'=>250,    '5PMDSCREDIT'=>250, '5PMDSU CREDIT'=>250,   '5PMDSUCREDIT'=>250,    '5PMDSL CREDIT'=>250,   '5PMDSLCREDIT'=>250,
    '1PMDS ARMY'=>40,       '1PMDSARMY'=>40,    '1PMDSU ARMY'=>40,      '1PMDSUARMY'=>40,       '1PMDSL ARMY'=>40,      '1PMDSLARMY'=>40,
    '1PMDS AINF'=>40,       '1PMDSAINF'=>40,    '1PMDSU AINF'=>40,      '1PMDSUAINF'=>40,       '1PMDSL AINF'=>40,      '1PMDSLAINF'=>40,
    '1PMDS ARCH'=>40,       '1PMDSARCH'=>40,    '1PMDSU ARCH'=>40,      '1PMDSUARCH'=>40,       '1PMDSL ARCH'=>40,      '1PMDSLARCH'=>40,
    '5PMDS ART'=>250,       '5PMDSART'=>250,    '5PMDSU ART'=>250,      '5PMDSUART'=>250,       '1PMDSL ART'=>40,       '1PMDSLART'=>40,
    '1PMDS 500'=>40,        '1PMDS500'=>40,     '1PMDSU 500'=>40,       '1PMDSU500'=>40,        '1PMDSL 500'=>40,       '1PMDSL500'=>40,
    // Израиль
    'PMD5DS PREMIUM'=>250,  'PMD5DSPREMIUM'=>250,'PMD5DSU PREMIUM'=>250,'PMD5DSUPREMIUM'=>250,  'PMD5DSL PREMIUM'=>250,     'PMD5DSLPREMIUM'=>250,
    'PMD5DS CREDIT'=>250,   'PMD5DSCREDIT'=>250, 'PMD5DSU CREDIT'=>250, 'PMD5DSUCREDIT'=>250,   'PMD5DSL CREDIT'=>250,      'PMD5DSLCREDIT'=>250,
    'PMD1DS ARMY'=>250,     'PMD1DSARMY'=>250,  'PMD1DSU ARMY'=>250,    'PMD1DSUARMY'=>250,     'PMD1DSL ARMY'=>250,        'PMD1DSLARMY'=>250,
    'PMD1DS AINF'=>250,     'PMD1DSAINF'=>250,  'PMD1DSU AINF'=>250,    'PMD1DSUAINF'=>250,     'PMD1DSL AINF'=>250,        'PMD1DSLAINF'=>250,
    'PMD1DS ARCH'=>250,     'PMD1DSARCH'=>250,  'PMD1DSU ARCH'=>250,    'PMD1DSUARCH'=>250,     'PMD1DSL ARCH'=>250,        'PMD1DSLARCH'=>250,
    'PMD5DS ART'=>250,      'PMD5DSART'=>250,   'PMD5DSU ART'=>250,     'PMD5DSUART'=>250,      'PMD5DSL ART'=>250,         'PMD5DSLART'=>250,
    'PMD1DS 500'=>250,      'PMD1DS500'=>250,   'PMD1DSU 500'=>250,     'PMD1DSU500'=>250,      'PMD1DSL 500'=>250,         'PMD1DSL500'=>250,
    
    // Австрия и Шаейцария (Префиксы только для версии DE)
    'PMDDSG PREMIUM'=>250,  'PMDDSGPREMIUM'=>250,
    'PMDDSG CREDIT1'=>250,  'PMDDSG CREDIT1'=>250, // Действует только в Австрии!
    'PMDDSG CREDIT2'=>250,  'PMDDSGCREDIT2'=>250, // Действует только в Швейцарии!
    'PMDDSG ARMY'=>250,     'PMDDSGARMY'=>250,  
    'PMDDSG AINF'=>250,     'PMDDSGAINF'=>250,
    'PMDDSG ARCH'=>250,     'PMDDSGARCH'=>250,
    'PMDDSG ART'=>250,      'PMDDSGART'=>250,
    
    );

    var $function_mask = array(
    'DSMSG'=>array('string', 'string', 'string', 'string', 'string'),
    'DSMSGR'=>array('string', 'string', 'string', 'string', 'string'),
    'DSMSGA'=>array('string', 'string', 'string', 'string', 'string'),
    'DSGLOBAL'=>array('string', 'string', 'string', 'string', 'string'),
    'DSMSGG'=>array('string', 'string', 'string', 'string', 'integer'),
    'DSSMS'=>array('string', 'string', 'string', 'string', 'string'),

    'ON DSREG'=>array('string', 'string', 'string'),
    'DS RING'=>array('string', 'string', 'integer', 'integer'),
    'UKRDS RING'=>array('string', 'string', 'integer', 'integer'),
    'DSRING'=>array('string', 'string', 'integer', 'integer'),

    'DSPLAY'=>array('string', 'string'),
    'DSPLAYXXX'=>array('string', 'string'), //тестовый триггер, для проведения викторины

    'DS ARMY'=>array('string', 'string', 'string', 'string'),
    'UKRDS ARMY'=>array('string', 'string', 'string', 'string'),
    'DSR ARMY'=>array('string', 'string', 'string', 'string'),
    'DSARMY'=>array('string', 'string', 'string', 'string'),
    'DSPREMIUM'=>array('string', 'string', 'string'),
    'DS PREMIUM'=>array('string', 'string', 'string'),
    'UKRDS PREMIUM'=>array('string', 'string', 'string'),
    'DSR PREMIUM'=>array('string', 'string', 'string'),
    'DS AINF'=>array('string', 'string', 'string', 'string'),
    'UKRDS AINF'=>array('string', 'string', 'string', 'string'),
    'DSR AINF'=>array('string', 'string', 'string', 'string'),
    'DSAINF'=>array('string', 'string', 'string', 'string'),
    'DS ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'UKRDS ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DSR ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DSARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DS ART'=>array('string', 'string', 'string', 'string', 'string'),
    'UKRDS ART'=>array('string', 'string', 'string', 'string', 'string'),
    'DSR ART'=>array('string', 'string', 'string', 'string', 'string'),
    'DSART'=>array('string', 'string', 'string', 'string', 'string'),

    'DSCREDIT'=>array('string', 'string'),
    'DS CREDIT'=>array('string', 'string'),
    'UKRDS CREDIT'=>array('string', 'string'),
    'DSR CREDIT'=>array('string', 'string'),

    'DS500'=>array('string', 'string'),
    'DS 500'=>array('string', 'string'),
    'UKRDS 500'=>array('string', 'string'),
    'DSR 500'=>array('string', 'string'),

    'DS BEAUTY'=>array('string', 'string'),
    'UKRDS BEAUTY'=>array('string', 'string'),
    'DSR BEAUTY'=>array('string', 'string'),
    'DSBEAUTY'=>array('string', 'string'),

    'DS OTVET'=>array('string', 'string', 'string'),
    'UKRDS OTVET'=>array('string', 'string', 'string'),
    'DS VOTE'=>array('string', 'string'),
    'UKRDS VOTE'=>array('string', 'string'),

    // Казахи
    'SD PREMIUM'=>array('string', 'string', 'string'),
    'SDPREMIUM'=>array('string', 'string', 'string'),
    'SD ART'=>array('string', 'string', 'string', 'string', 'string'),
    'SDART'=>array('string', 'string', 'string', 'string', 'string'),
    'SD ARMY'=>array('string', 'string', 'string', 'string'),
    'SDARMY'=>array('string', 'string', 'string', 'string'),
    'SD AINF'=>array('string', 'string', 'string', 'string'),
    'SDAINF'=>array('string', 'string', 'string', 'string'),
    'SD ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'SDARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),

    'SD BEAUTY'=>array('string', 'string'),
    'SDBEAUTY'=>array('string', 'string'),

    // Лимитед
    'DSL ARMY'=>array('string', 'string', 'string', 'string'),
    'UKRDSL ARMY'=>array('string', 'string', 'string', 'string'),
    'DSLARMY'=>array('string', 'string', 'string', 'string'),
    'DSLPREMIUM'=>array('string', 'string', 'string'),
    'DSL PREMIUM'=>array('string', 'string', 'string'),
    'UKRDSL PREMIUM'=>array('string', 'string', 'string'),
    'DSLAINF'=>array('string', 'string', 'string', 'string'),
    'DSL AINF'=>array('string', 'string', 'string', 'string'),
    'UKRDSL AINF'=>array('string', 'string', 'string', 'string'),
    'DSLARCH'=>array('striog', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DSL ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'UKRDSL ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DSLART'=>array('string', 'string', 'string', 'string', 'string'),
    'DSL ART'=>array('string', 'string', 'string', 'string', 'string'),
    'UKRDSL ART'=>array('string', 'string', 'string', 'string', 'string'),
    'DSLCREDIT'=>array('string', 'string'),
    'DSL CREDIT'=>array('string', 'string'),
    'UKRDSL CREDIT'=>array('string', 'string'),
    'DSL500'=>array('string', 'string'),
    'DSL 500'=>array('string', 'string'),
    'UKRDSL 500'=>array('string', 'string'),

    // Анлимитед
    'DSU ARMY'=>array('string', 'string', 'string', 'string'),
    'UKRDSU ARMY'=>array('string', 'string', 'string', 'string'),
    'DSRU ARMY'=>array('string', 'string', 'string', 'string'),
    'DSUARMY'=>array('string', 'string', 'string', 'string'),
    'DSUPREMIUM'=>array('string', 'string', 'string'),
    'DSU PREMIUM'=>array('string', 'string', 'string'),
    'UKRDSU PREMIUM'=>array('string', 'string', 'string'),
    'DSRU PREMIUM'=>array('string', 'string', 'string'),
    'DSU AINF'=>array('string', 'string', 'string', 'string'),
    'UKRDSU AINF'=>array('string', 'string', 'string', 'string'),
    'DSRU AINF'=>array('string', 'string', 'string', 'string'),
    'DSUAINF'=>array('string', 'string', 'string', 'string'),
    'DSU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'UKRDSU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DSRU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DSUARCH'=>array('striog', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'DSU ART'=>array('string', 'string', 'string', 'string', 'string'),
    'UKRDSU ART'=>array('string', 'string', 'string', 'string', 'string'),
    'DSRU ART'=>array('string', 'string', 'string', 'string', 'string'),
    'DSUART'=>array('string', 'string', 'string', 'string', 'string'),
    'DSUCREDIT'=>array('string', 'string'),
    'DSU CREDIT'=>array('string', 'string'),
    'UKRDSU CREDIT'=>array('string', 'string'),
    'DSRU CREDIT'=>array('string', 'string'),
    'DSU500'=>array('string', 'string'),
    'DSU 500'=>array('string', 'string'),
    'UKRDSU 500'=>array('string', 'string'),
    'DSRU 500'=>array('string', 'string'),

    // Казахи-анлимитед
    'SDU PREMIUM'=>array('string', 'string', 'string'),
    'SDUPREMIUM'=>array('string', 'string', 'string'),
    'SDU ART'=>array('string', 'string', 'string', 'string', 'string'),
    'SDUART'=>array('string', 'string', 'string', 'string', 'string'),
    'SDU ARMY'=>array('string', 'string', 'string', 'string'),
    'SDUARMY'=>array('string', 'string', 'string', 'string'),
    'SDU AINF'=>array('string', 'string', 'string', 'string'),
    'SDUAINF'=>array('string', 'string', 'string', 'string'),
    'SDU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'SDUARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),

    'SDUCREDIT'=>array('string', 'string'),
    'SDU CREDIT'=>array('string', 'string'),

    /*********************** Зарубеж ***********************/
    'PPDS PREMIUM'=>array('string', 'string', 'string'),    'PPDSPREMIUM'=>array('string', 'string', 'string'),     'PPDSU PREMIUM'=>array('string', 'string', 'string'),   'PPDSUPREMIUM'=>array('string', 'string', 'string'),'PPDSL PREMIUM'=>array('string', 'string', 'string'),   'PPDSLPREMIUM'=>array('string', 'string', 'string'),
    'PMDDS PREMIUM'=>array('string', 'string', 'string'),   'PMDDSPREMIUM'=>array('string', 'string', 'string'), 'PMDDSU PREMIUM'=>array('string', 'string', 'string'),     'PMDDSUPREMIUM'=>array('string', 'string', 'string'),'PMDDSL PREMIUM'=>array('string', 'string', 'string'),     'PMDDSLPREMIUM'=>array('string', 'string', 'string'),
    '5PMDS PREMIUM'=>array('string', 'string', 'string'),   '5PMDSPREMIUM'=>array('string', 'string', 'string'), '5PMDSU PREMIUM'=>array('string', 'string', 'string'),     '5PMDSUPREMIUM'=>array('string', 'string', 'string'),'5PMDSL PREMIUM'=>array('string', 'string', 'string'),     '5PMDSLPREMIUM'=>array('string', 'string', 'string'),
    'PMD5DS PREMIUM'=>array('string', 'string', 'string'),  'PMD5DSPREMIUM'=>array('string', 'string', 'string'), 'PMD5DSU PREMIUM'=>array('string', 'string', 'string'), 'PMD5DSUPREMIUM'=>array('string', 'string', 'string'),'PMD5DSL PREMIUM'=>array('string', 'string', 'string'), 'PMD5DSLPREMIUM'=>array('string', 'string', 'string'),

    'PPDSG PREMIUM'=>array('string', 'string', 'string'),
    'PPDSGPREMIUM'=>array('string', 'string', 'string'),
    'PMDDSG PREMIUM'=>array('string', 'string', 'string'),
    'PMDDSGPREMIUM'=>array('string', 'string', 'string'),               
                
    'PPDS CREDIT'=>array('string', 'string'),       'PPDSCREDIT'=>array('string', 'string'),    'PPDSU CREDIT'=>array('string', 'string'),  'PPDSUCREDIT'=>array('string', 'string'),   'PPDSL CREDIT'=>array('string', 'string'),  'PPDSLCREDIT'=>array('string', 'string'),
    'PMDDS CREDIT'=>array('string', 'string'),      'PMDDSCREDIT'=>array('string', 'string'),   'PMDDSU CREDIT'=>array('string', 'string'),     'PMDDSUCREDIT'=>array('string', 'string'),'PMDDSL CREDIT'=>array('string', 'string'),   'PMDDSLCREDIT'=>array('string', 'string'),
    '5PMDS CREDIT'=>array('string', 'string'),      '5PMDSCREDIT'=>array('string', 'string'),   '5PMDSU CREDIT'=>array('string', 'string'),     '5PMDSUCREDIT'=>array('string', 'string'),  '5PMDSL CREDIT'=>array('string', 'string'),     '5PMDSLCREDIT'=>array('string', 'string'),
    'PMD5DS CREDIT'=>array('string', 'string'),     'PMD5DSCREDIT'=>array('string', 'string'), 'PMD5DSU CREDIT'=>array('string', 'string'),     'PMD5DSUCREDIT'=>array('string', 'string'),'PMD5DSL CREDIT'=>array('string', 'string'),     'PMD5DSLCREDIT'=>array('string', 'string'),

    'PPDSG CREDIT1'=>array('string', 'string'),
    'PPDSGCREDIT1'=>array('string', 'string'),
    'PMDDSG CREDIT1'=>array('string', 'string'),
    'PMDDSGCREDIT1'=>array('string', 'string'),
    
    'PPDSG CREDIT2'=>array('string', 'string'),
    'PPDSGCREDIT2'=>array('string', 'string'),
    'PMDDSG CREDIT2'=>array('string', 'string'),
    'PMDDSGCREDIT2'=>array('string', 'string'),
    
    'PPDSG CREDIT3'=>array('string', 'string'),
    'PPDSGCREDIT3'=>array('string', 'string'),
    
    'PPDS ARMY'=>array('string', 'string', 'string', 'string'),         'PPDSARMY'=>array('string', 'string', 'string', 'string'),  'PPDSU ARMY'=>array('string', 'string', 'string', 'string'),        'PPDSUARMY'=>array('string', 'string', 'string', 'string'),     'PPDSL ARMY'=>array('string', 'string', 'string', 'string'),        'PPDSLARMY'=>array('string', 'string', 'string', 'string'),
    'PMDDS ARMY'=>array('string', 'string', 'string', 'string'),        'PMDDSARMY'=>array('string', 'string', 'string', 'string'),     'PMDDSU ARMY'=>array('string', 'string', 'string', 'string'),       'PMDDSUARMY'=>array('string', 'string', 'string', 'string'),    'PMDDSL ARMY'=>array('string', 'string', 'string', 'string'),       'PMDDSLARMY'=>array('string', 'string', 'string', 'string'),
    '1PMDS ARMY'=>array('string', 'string', 'string', 'string'),        '1PMDSARMY'=>array('string', 'string', 'string', 'string'),     '1PMDSU ARMY'=>array('string', 'string', 'string', 'string'),       '1PMDSUARMY'=>array('string', 'string', 'string', 'string'),    '1PMDSL ARMY'=>array('string', 'string', 'string', 'string'),       '1PMDSLARMY'=>array('string', 'string', 'string', 'string'),
    'PMD1DS ARMY'=>array('string', 'string', 'string', 'string'),       'PMD1DSARMY'=>array('string', 'string', 'string', 'string'),    'PMD1DSU ARMY'=>array('string', 'string', 'string', 'string'),  'PMD1DSUARMY'=>array('string', 'string', 'string', 'string'),   'PMD1DSL ARMY'=>array('string', 'string', 'string', 'string'),  'PMD1DSLARMY'=>array('string', 'string', 'string', 'string'),

    'PPDSG ARMY'=>array('string', 'string', 'string', 'string'),
    'PPDSGARMY'=>array('string', 'string', 'string', 'string'),
    'PMDDSG ARMY'=>array('string', 'string', 'string', 'string'),
    'PMDDSGARMY'=>array('string', 'string', 'string', 'string'),
    
    'PPDS AINF'=>array('string', 'string', 'string', 'string'),         'PPDSAINF'=>array('string', 'string', 'string', 'string'),  'PPDSU AINF'=>array('string', 'string', 'string', 'string'),        'PPDSUAINF'=>array('string', 'string', 'string', 'string'),     'PPDSL AINF'=>array('string', 'string', 'string', 'string'),        'PPDSLAINF'=>array('string', 'string', 'string', 'string'),
    'PMDDS AINF'=>array('string', 'string', 'string', 'string'),        'PMDDSAINF'=>array('string', 'string', 'string', 'string'),     'PMDDSU AINF'=>array('string', 'string', 'string', 'string'),       'PMDDSUAINF'=>array('string', 'string', 'string', 'string'),    'PMDDSL AINF'=>array('string', 'string', 'string', 'string'),       'PMDDSLAINF'=>array('string', 'string', 'string', 'string'),
    '1PMDS AINF'=>array('string', 'string', 'string', 'string'),        '1PMDSAINF'=>array('string', 'string', 'string', 'string'),     '1PMDSU AINF'=>array('string', 'string', 'string', 'string'),       '1PMDSUAINF'=>array('string', 'string', 'string', 'string'),    '1PMDSL AINF'=>array('string', 'string', 'string', 'string'),       '1PMDSLAINF'=>array('string', 'string', 'string', 'string'),
    'PMD1DS AINF'=>array('string', 'string', 'string', 'string'),       'PMD1DSAINF'=>array('string', 'string', 'string', 'string'),    'PMD1DSU AINF'=>array('string', 'string', 'string', 'string'),  'PMD1DSLAINF'=>array('string', 'string', 'string', 'string'),   'PMD1DSU AINF'=>array('string', 'string', 'string', 'string'),  'PMD1DSLAINF'=>array('string', 'string', 'string', 'string'),

    'PPDSG AINF'=>array('string', 'string', 'string', 'string'),
    'PPDSGAINF'=>array('string', 'string', 'string', 'string'),
    'PMDDSG AINF'=>array('string', 'string', 'string', 'string'),
    'PMDDSGAINF'=>array('string', 'string', 'string', 'string'),    
    
    'PPDS ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),         'PPDSARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),  'PPDSU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),        'PPDSUARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'), 'PPDSL ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),        'PPDSLARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'PMDDS ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),        'PMDDSARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),     'PMDDSU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),       'PMDDSUARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'), 'PMDDSL ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),      'PMDDSLARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    '1PMDS ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),        '1PMDSARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),     '1PMDSU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),       '1PMDSUARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'), '1PMDSL ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),      '1PMDSLARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'PMD1DS ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),       'PMD1DSARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),    'PMD1DSU ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),  'PMD1DSUARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),   'PMD1DSL ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),  'PMD1DSLARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),

    'PPDSG ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'PPDSGARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'PMDDSG ARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    'PMDDSGARCH'=>array('string', 'string', 'string', 'string', 'string', 'integer', 'integer'),
    
    'PPDS ART'=>array('string', 'string', 'string', 'string', 'string'),        'PPDSART'=>array('string', 'string', 'string', 'string', 'string'),         'PPDSU ART'=>array('string', 'string', 'string', 'string', 'string'),       'PPDSUART'=>array('string', 'string', 'string', 'string', 'string'), 'PPDSL ART'=>array('string', 'string', 'string', 'string', 'string'),      'PPDSLART'=>array('string', 'string', 'string', 'string', 'string'),
    'PMDDS ART'=>array('string', 'string', 'string', 'string', 'string'),       'PMDDSART'=>array('string', 'string', 'string', 'string', 'string'),    'PMDDSU ART'=>array('string', 'string', 'string', 'string', 'string'),      'PMDDSUART'=>array('string', 'string', 'string', 'string', 'string'),   'PMDDSL ART'=>array('string', 'string', 'string', 'string', 'string'),      'PMDDSLART'=>array('string', 'string', 'string', 'string', 'string'),
    '5PMDS ART'=>array('string', 'string', 'string', 'string', 'string'),       '5PMDSART'=>array('string', 'string', 'string', 'string', 'string'),    '5PMDSU ART'=>array('string', 'string', 'string', 'string', 'string'),      '5PMDSUART'=>array('string', 'string', 'string', 'string', 'string'),   '5PMDSL ART'=>array('string', 'string', 'string', 'string', 'string'),      '5PMDSLART'=>array('string', 'string', 'string', 'string', 'string'),
    'PMD5DS ART'=>array('string', 'string', 'string', 'string', 'string'),      'PMD5DSART'=>array('string', 'string', 'string', 'string', 'string'),   'PMD5DSU ART'=>array('string', 'string', 'string', 'string', 'string'),         'PMD5DSUART'=>array('string', 'string', 'string', 'string', 'string'),  'PMD5DSL ART'=>array('string', 'string', 'string', 'string', 'string'),         'PMD5DSLART'=>array('string', 'string', 'string', 'string', 'string'),

    'PPDSG ART'=>array('string', 'string', 'string', 'string', 'string'),
    'PPDSGART'=>array('string', 'string', 'string', 'string', 'string'),
    'PMDDSG ART'=>array('string', 'string', 'string', 'string', 'string'),
    'PMDDSGART'=>array('string', 'string', 'string', 'string', 'string'),
    
    'PPDS 500'=>array('string', 'string'),          'PPDS500'=>array('string', 'string'),       'PPDSU 500'=>array('string', 'string'),         'PPDSU500'=>array('string', 'string'),  'PPDSL 500'=>array('string', 'string'),         'PPDSL500'=>array('string', 'string'),
    'PMDDS 500'=>array('string', 'string'),         'PMDDS500'=>array('string', 'string'),  'PMDDSU 500'=>array('string', 'string'),        'PMDDSU500'=>array('string', 'string'),     'PMDDSL 500'=>array('string', 'string'),        'PMDDSL500'=>array('string', 'string'),
    '1PMDS 500'=>array('string', 'string'),         '1PMDS500'=>array('string', 'string'),  '1PMDSU 500'=>array('string', 'string'),        '1PMDSU500'=>array('string', 'string'),     '1PMDSL 500'=>array('string', 'string'),        '1PMDSL500'=>array('string', 'string'),
    'PMD1DS 500'=>array('string', 'string'),        'PMD1DS500'=>array('string', 'string'),     'PMD1DSU 500'=>array('string', 'string'),       'PMD1DSU500'=>array('string', 'string'),    'PMD1DSL 500'=>array('string', 'string'),       'PMD1DSL500'=>array('string', 'string'),

    );

/*
81.19.68.242 dsbeauty.php
codesh сообщает:
 http://beauty.destinysphere.ru/gate/dsbeauty.php
*/

    /* данные об отправителе и его мобильном телефоне*/
    var $trans_id;
    var $phone;
    var $shortcode;
    var $group;
    var $header;
    var $body;
    var $trigger = false;
    var $emulator = false;

    var $worldName;
    var $worldId;
    var $dbName;
    var $dbId;
    var $messageScope;
    var $messageText;
    var $messageSubject;

    var $messageParcipiants = array();

    var $userInstanceFrom;
    var $userInstanceTo;

    // Параметр PAID  может принимать только 2 значения YES или NO
    // Значение YES означает, что запрос оплачен абонентом и партнеру следует его обработать.
    var $paid = 'YES';

    function DSMessage($offline = false)
    {
        $this->BaseDSMessage();
        if ($offline)
        {
            $this->offline = true;
            $data = $offline;
        } else {
            $this->offline = false;
            $data = $_REQUEST;
        }

//      $this->incomingData = $data;

        $this->log = print_r($data, true);

        $this->common = new DS_Common();

        $this->setCommon($this->common);
        $this->setDb(0);

        $this->getContent = new GetContent();

        $common = $this->getCommon();
        
        if (!empty($common->errormsg))
        {
            if ($this->offline == false)
            {
                if ($this->saveMessageInFile($data) )
                {
                    $this->response_status = 0;
                    $this->response_statusPM = -2;
                    $this->response_text = "Сообщение поставлено в очередь на обработку";
                    $this->sendResponse();
                    return;
                } else {
                    $this->response_status = 0;
                    $this->response_statusPM = -2;
                    $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE');
                    $this->sendResponse();
                    return;
                }
            }
        }

        if (!$this->ParseData($data) )
        {
            $this->errorParams = true;
            $this->response_statusPM = -1;
            $this->sendResponse();
            return;
        }

        if(in_array($this->shortcode, $this->disabledShortCodes))
        {
            $this->response_status = 0;
            $this->response_statusPM = -1;
            $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE');
            $this->sendResponse();
            return;
        }

        $res = $this->process();

        // Посылаем биллингу запросик(если смс уже оплачена)
        if (($this->paid == 'YES') && (stripos($this->trigger, 'BEAUTY')===false ))
        {
            $url = "https://billing.destinysphere.ru/gate/sms.php";

            // Версия игры
            if (key_exists('UNLIMITED', $this->incomingData)) {
                $params = 'id_partner=6';
            } elseif (key_exists('GERMANY', $this->incomingData)) {
                $params = 'id_partner=10';
            } elseif (key_exists('LIMITED', $this->incomingData)) {
                $params = 'id_partner=9';
            } else {
                $params = 'id_partner=4';
            }
            // Ник
            // Услуга
            $params .= '&good='.$this->good;
            // Стоимость в рублях
            $params .= '&sum='.$this->sum;
            // Дата регистрации
            $this->common->setDbId(0);
            $sql = "SELECT
                        username, to_char(registrationdate, 'YYYY-MM-DD HH24:MI:SS') as
                        registrationdate, email, id_user as uid, from_partner, from_site_id
                    FROM ds_user
                    WHERE
                        lower(username) = '".strtolower($this->username)."'";
            $regDate = $this->common->getResultArray("", $sql, "", "", "");
            if (!$regDate)
            return; 
        
            $params .= '&nick='.$regDate[0]['username'];
            $params .= '&reg_date='.$regDate[0]['registrationdate'].'&email='.$regDate[0]['email'].'&uid='.$regDate[0]['uid'].'&from_partner='.$regDate[0]['from_partner'].'&from_site_id='.$regDate[0]['from_site_id'];

        //Проверим, не является ли этот юзер чьим-нибудь реферралом.
        //Информацию о его "хозяине" также передаём в биллинг - на тот
        //случай, если её там вдруг нет - чтобы создался аккаунт и стало
        //возможным начисление денег
        $rec = DS_User::GetRecommendedUser($regDate[0]['uid']);

        if ($rec)
        {
          $params .= '&rec_login='.$rec['username'];
          $params .= '&rec_game_pass_hash='.$rec['password'];
          $params .= '&rec_email='.$rec['email'];
          $params .= '&rec_reg_date='.$rec['registrationdate'];
          $params .= '&rec_from_partner='.$rec['from_partner'];
            $params .= '&rec_id_user='.$rec['id_user_recomend'];
        }
        else
        {
            $params .= '&rec_id_user=0';
        }

            $ch = curl_init ($url);
            curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

            ob_start ();
            $result = curl_exec ($ch);
            ob_end_clean ();
            curl_close ($ch);

            return $res;
        }
    }

    function process()
    {
        $common = $this->getCommon();

        //var_dump($this->header);
        switch ($this->trigger)
        {
            case 'DSCREDIT':
            case 'DS CREDIT':
            case 'UKRDS CREDIT':
            case 'DSUCREDIT':
            case 'DSU CREDIT':
            case 'UKRDSU CREDIT':
            case 'DSLCREDIT':
            case 'DSL CREDIT':
            case 'UKRDSL CREDIT':
            case 'DSR CREDIT':
            case 'DSRU CREDIT':

            case 'PPDS CREDIT':
            case 'PPDSCREDIT':
            case 'PPDSU CREDIT':
            case 'PPDSUCREDIT':
            case 'PPDSL CREDIT':
            case 'PPDSLCREDIT':

            case 'PMDDS CREDIT':
            case 'PMDDSCREDIT':
            case 'PMDDSU CREDIT':
            case 'PMDDSUCREDIT':
            case 'PMDDSL CREDIT':
            case 'PMDDSLCREDIT':

            case '5PMDS CREDIT':
            case '5PMDSCREDIT':
            case '5PMDSU CREDIT':
            case '5PMDSUCREDIT':
            case '5PMDSL CREDIT':
            case '5PMDSLCREDIT':

            case 'PMD5DS CREDIT':
            case 'PMD5DSCREDIT':
            case 'PMD5DSU CREDIT':
            case 'PMD5DSUCREDIT':
            case 'PMD5DSL CREDIT':
            case 'PMD5DSLCREDIT':

            case 'SDUCREDIT':
            case 'SDU CREDIT':

                $username =  DSMessage::wordProcess($this->header[1]);

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $url = constant('GAME_HOST')."/ds/SMS_Credits.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("username", $username);
                    $this->getContent->setPostVar("credit", 'SC');
                    // Если смс украинские
                    if ($this->shortcode == '5373') {
                        $this->getContent->setPostVar("summ", '2500');
                    } else {
                        $this->getContent->setPostVar("summ", '3000');
                    }
                    $this->getContent->setPostVar("test", $this->testMode);
                    $this->getContent->Start();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2))
                    {
                        $this->response_statusPM = -2;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }

                    if($array[0] == 0)
                    {
                        $this->response_statusPM = -1;
                        $this->response_text = SMS_Localization::Localize($array[1]);
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = SMS_Localization::Localize('{CREDIT_MSG_ADDED}');
                }


                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'CREDIT';
                $this->sum = '136';
                $this->username = $username;

                break;
                
            case 'PPDSG CREDIT1':
            case 'PPDSGCREDIT1':
            case 'PMDDSG CREDIT1':
            case 'PMDDSGCREDIT1':   

                $username =  DSMessage::wordProcess($this->header[1]);

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $url = constant('GAME_HOST')."/ds/SMS_Credits.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("username", $username);
                    
                    $this->getContent->setPostVar("credit", 'GC');
                    $this->getContent->setPostVar("summ", '1');
                    
                    $this->getContent->setPostVar("test", $this->testMode);
                    $this->getContent->Start();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2))
                    {
                        $this->response_statusPM = -2;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }

                    if($array[0] == 0)
                    {
                        $this->response_statusPM = -1;
                        $this->response_text = SMS_Localization::Localize($array[1]);
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = SMS_Localization::Localize('{CREDIT_MSG_ADDED}');
                }


                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'DEGOLDCREDIT';
                $this->sum = '1.99';
                $this->amount = '1';
                $this->username = $username;

                break;
                
            case 'PPDSG CREDIT2':
            case 'PPDSGCREDIT2':
            case 'PMDDSG CREDIT2':
            case 'PMDDSGCREDIT2':   

                $username =  DSMessage::wordProcess($this->header[1]);

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $url = constant('GAME_HOST')."/ds/SMS_Credits.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("username", $username);
                    
                    $this->getContent->setPostVar("credit", 'GC');
                    $this->getContent->setPostVar("summ", '2');
                    
                    $this->getContent->setPostVar("test", $this->testMode);
                    $this->getContent->Start();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2))
                    {
                        $this->response_statusPM = -2;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }

                    if($array[0] == 0)
                    {
                        $this->response_statusPM = -1;
                        $this->response_text = SMS_Localization::Localize($array[1]);
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = SMS_Localization::Localize('{CREDIT_MSG_ADDED}');
                }


                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'DEGOLDCREDIT';
                $this->sum = '2.99';
                $this->amount = '2';
                $this->username = $username;

                break;
    
            case 'PPDSG CREDIT3':
            case 'PPDSGCREDIT3':

                $username =  DSMessage::wordProcess($this->header[1]);

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $url = constant('GAME_HOST')."/ds/SMS_Credits.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("username", $username);
                    
                    $this->getContent->setPostVar("credit", 'GC');
                    $this->getContent->setPostVar("summ", '3');
                    
                    $this->getContent->setPostVar("test", $this->testMode);
                    $this->getContent->Start();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2))
                    {
                        $this->response_statusPM = -2;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }

                    if($array[0] == 0)
                    {
                        $this->response_statusPM = -1;
                        $this->response_text = SMS_Localization::Localize($array[1]);
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = SMS_Localization::Localize('{CREDIT_MSG_ADDED}');
                }


                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'DEGOLDCREDIT';
                $this->sum = '4.99';
                $this->amount = '3';
                $this->username = $username;

                break;

            case 'DS500':
            case 'DS 500':
            case 'UKRDS 500':
            case 'DSR 500':
            case 'DSU500':
            case 'DSU 500':
            case 'UKRDSU 500':
            case 'DSRU 500':
            case 'DSL500':
            case 'DSL 500':
            case 'UKRDSL 500':

            case 'PPDS 500':
            case 'PPDS500':
            case 'PPDSU500':
            case 'PPDSU 500':
            case 'PPDSL500':
            case 'PPDSL 500':

            case 'PMDDS 500':
            case 'PMDDS500':
            case 'PMDDSU500':
            case 'PMDDSU 500':
            case 'PMDDSL500':
            case 'PMDDSL 500':

            case '1PMDS 500':
            case '1PMDS500':
            case '1PMDSU500':
            case '1PMDSU 500':
            case '1PMDSL500':
            case '1PMDSL 500':

            case 'PMD1DS 500':
            case 'PMD1DS500':
            case 'PMD1DSU500':
            case 'PMD1DSU 500':
            case 'PMD1DSL500':
            case 'PMD1DSL 500':
                $username =  DSMessage::wordProcess($this->header[1]);

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $url = constant('GAME_HOST')."/ds/SMS_Credits.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("username", $username);
                    $this->getContent->setPostVar("credit", 'SC');
                    $this->getContent->setPostVar("summ", '500');
                    $this->getContent->setPostVar("test", $this->testMode);
                    $this->getContent->Start();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2))
                    {
                        $this->response_statusPM = -2;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }

                    if($array[0] == 0)
                    {
                        $this->response_statusPM = -1;
                        $this->response_text = SMS_Localization::Localize($array[1]);
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = '{CREDIT_MSG_ADDED}';
                }

                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = '500';
                $this->sum = '35';
                $this->username = $username;


                break;


            case 'SDART':
            case 'SD ART':
            case 'DSART':
            case 'DS ART':
            case 'UKRDS ART':
            case 'DSR ART':
            case 'SDUART':
            case 'SDU ART':
            case 'DSUART':
            case 'DSU ART':
            case 'UKRDSU ART':
            case 'DSRU ART':
            case 'DSLART':
            case 'DSL ART':
            case 'UKRDSL ART':

            case 'PPDS ART':
            case 'PPDSART':
            case 'PPDSUART':
            case 'PPDSU ART':
            case 'PPDSLART':
            case 'PPDSL ART':

            case 'PMDDS ART':
            case 'PMDDSART':
            case 'PMDDSUART':
            case 'PMDDSU ART':
            case 'PMDDSLART':
            case 'PMDDSL ART':

            case '5PMDS ART':
            case '5PMDSART':
            case '5PMDSUART':
            case '5PMDSU ART':
            case '5PMDSLART':
            case '5PMDSL ART':

            case 'PMD5DS ART':
            case 'PMD5DSART':
            case 'PMD5DSUART':
            case 'PMD5DSU ART':
            case 'PMD5DSLART':
            case 'PMD5DSL ART':
                
            case 'PPDSG ART':
            case 'PPDSGART':
            case 'PMDDSG ART':
            case 'PMDDSGART':
                $username = DSMessage::wordProcess($this->header[1]);
                $password = $this->header[2];
                $comb = $this->header[3];
                $world = $this->header[4];

                if(!$this->checkWorldID($world) )
                {
                    return false;
                }

                if( !$this->getUserInstanceByLoginAndPassword($username, $password) )
                {
                    return false;
                }

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $url = constant('GAME_HOST')."/ds/SMS_Artefacts.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("comb", $comb);
                    $this->getContent->setPostVar("dbid", $this->dbId);
                    $this->getContent->setPostVar("uiid", $this->userInstanceFrom);
                    //$this->getContent->showHeaders();
                    $this->getContent->Start();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2))
                    {
                        $this->response_statusPM = -1;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }

                    if($array[0] == 0)
                    {
                        $this->response_statusPM = -1;
                        $this->response_text = SMS_Localization::Localize($array[1]);
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = '{PROCCESSED}';
                }

                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'ART';
                $this->sum = '136';
                $this->username = $username;

                break;


            case 'SDAINF':
            case 'SD AINF':
            case 'DSAINF':
            case 'DS AINF':
            case 'UKRDS AINF':
            case 'DSR AINF':
            case 'DSLAINF':
            case 'DSL AINF':
            case 'UKRDSL AINF':
            case 'SDUAINF':
            case 'SDU AINF':
            case 'DSUAINF':
            case 'DSU AINF':
            case 'UKRDSU AINF':
            case 'DSRU AINF':

            case 'PPDS AINF':
            case 'PPDSAINF':
            case 'PPDSUAINF':
            case 'PPDSU AINF':
            case 'PPDSLAINF':
            case 'PPDSL AINF':

            case 'PMDDS AINF':
            case 'PMDDSAINF':
            case 'PMDDSUAINF':
            case 'PMDDSU AINF':
            case 'PMDDSLAINF':
            case 'PMDDSL AINF':

            case '1PMDS AINF':
            case '1PMDSAINF':
            case '1PMDSUAINF':
            case '1PMDSU AINF':
            case '1PMDSLAINF':
            case '1PMDSL AINF':

            case 'PMD1DS AINF':
            case 'PMD1DSAINF':
            case 'PMD1DSUAINF':
            case 'PMD1DSU AINF':
            case 'PMD1DSLAINF':
            case 'PMD1DSL AINF':
                
            case 'PPDSG AINF':
            case 'PPDSGAINF':
            case 'PMDDSG AINF':
            case 'PMDDSGAINF':

                $username = DSMessage::wordProcess($this->header[1]);
                $password = $this->header[2];
                $world = $this->header[3];

                if(!$this->checkWorldID($world) )
                {
                    return false;
                }

                if( !$this->getUserInstanceByLoginAndPassword($username, $password) )
                {
                    return false;
                }

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    //$url = "http://portal/ds/SMS_Archiology.php";
                    $url = constant('GAME_HOST')."/ds/SMS_Archiology.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("action", 'info');
                    $this->getContent->setPostVar("dbid", $this->dbId);
                    $this->getContent->setPostVar("uiid", $this->userInstanceFrom);
                    //$this->getContent->showHeaders();
                    $this->getContent->Start();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2) || ($array[0] == 0))
                    {
                        $this->response_statusPM = -2;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = '{PROCCESSED}';
                }

                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'AINF';
                $this->sum = '35';
                $this->username = $username;

                break;



            case 'SDARCH':
            case 'SD ARCH':
            case 'DSARCH':
            case 'DS ARCH':
            case 'UKRDS ARCH':
            case 'DSR ARCH':
            case 'SDUARCH':
            case 'SDU ARCH':
            case 'DSUARCH':
            case 'DSU ARCH':
            case 'UKRDSU ARCH':
            case 'DSRU ARCH':
            case 'DSLARCH':
            case 'DSL ARCH':
            case 'UKRDSL ARCH':

            case 'PPDS ARCH':
            case 'PPDSARCH':
            case 'PPDSUARCH':
            case 'PPDSU ARCH':
            case 'PPDSLARCH':
            case 'PPDSL ARCH':

            case 'PMDDS ARCH':
            case 'PMDDSARCH':
            case 'PMDDSUARCH':
            case 'PMDDSU ARCH':
            case 'PMDDSLARCH':
            case 'PMDDSL ARCH':

            case '1PMDS ARCH':
            case '1PMDSARCH':
            case '1PMDSUARCH':
            case '1PMDSU ARCH':
            case '1PMDSLARCH':
            case '1PMDSL ARCH':

            case 'PMD1DS ARCH':
            case 'PMD1DSARCH':
            case 'PMD1DSUARCH':
            case 'PMD1DSU ARCH':
            case 'PMD1DSLARCH':
            case 'PMD1DSL ARCH':
                
            case 'PPDSG ARCH':
            case 'PPDSGARCH':
            case 'PMDDSG ARCH':
            case 'PMDDSGARCH':
                $minGroupSize = 1000;
                $strTimeLimit = array(1,2,3,5,8,12);
                $targetValues = array('H' => 'HOME', 'C' => 'CENTRAL', 'N' => 'NEUTRAL');
                $username = DSMessage::wordProcess($this->header[2]);
                $password = $this->header[3];
                $world = $this->header[4];
                $groupSize = $this->header[5];
                $time = $this->header[6];

                if(!$this->checkWorldID($world) )
                {
                    return false;
                }

                if( !$this->getUserInstanceByLoginAndPassword($username, $password) )
                {
                    return false;
                }

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {

                    if ($groupSize < $minGroupSize)
                    {
                        $groupSize = $minGroupSize;
                    }

                    $scope = strtoupper($this->header[1]);
                    if (!key_exists($scope, $targetValues))
                    {
                        $scope = 'H';
                    }

                    $scopeName = $targetValues[$scope];

                    // Определяем на какое время будут отправленны археологи, и если время не стандартное,
                    //выбираем время от указанного в ближайшую меньшую сторону
                    $last = null;
                    foreach ($strTimeLimit as $key => $value)
                    {
                        if (is_null($last))
                        {
                            $last = $value;
                            if ($time < $last)
                            {
                                $onTime = $last;
                                break;
                            }
                        }
                        $next = $value;

                        if ($time > $next)
                        {
                            $continue = true;
                        }

                        if ($time == $next)
                        {
                            $onTime = $next;
                            break;
                        }

                        if ($time > $last && $time < $next)
                        {
                            $onTime = $last;
                            break;
                        }

                        $last = $value;
                    }

                    /*
                    if ($GLOBALS['dbWorldAccess'][$this->dbId]['dir'] != '/ds/')
                    {
                        $this->response_text = 'Etot servis dostupen tolko dlya novih mirov.';
                        $this->sendResponse();
                    }
                    */

                    //$url = "http://portal/ds/SMS_Archiology.php";
                    $url = constant('GAME_HOST')."/ds/SMS_Archiology.php";
                    $this->getContent->setUrl($url);
                    $this->getContent->setMethod("GET");
                    $this->getContent->setPostVar("action", 'send');
                    $this->getContent->setPostVar("dbid", $this->dbId);
                    $this->getContent->setPostVar("uiid", $this->userInstanceFrom);
                    $this->getContent->setPostVar("arch_count", $groupSize);
                    $this->getContent->setPostVar("search_time", $onTime);
                    $this->getContent->setPostVar("target", $scopeName);
                    //$this->getContent->showHeaders();
                    $this->getContent->Start();
                    $this->response_text = $this->getContent->GetResponseContent();

                    $text = $this->getContent->GetResponseContent();

                    $array = explode("|", $text);
                    if((count($array) != 2) || ($array[0] == 0))
                    {
                        $this->response_statusPM = -2;
                        $this->response_text = SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
                        $this->errorParams = false;
                        $this->sendResponse();
                        return false;
                    }
                } else {
                    $array[1] = '{PROCCESSED}';
                }

                $this->response_statusPM = 1;
                $this->response_text = SMS_Localization::Localize($array[1]);
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'ARCH';
                $this->sum = '35';
                $this->username = $username;

                break;



            case 'SDPREMIUM':
            case 'SD PREMIUM':
            case 'DSPREMIUM':
            case 'DS PREMIUM':
            case 'UKRDS PREMIUM':
            case 'DSR PREMIUM':
            case 'SDUPREMIUM':
            case 'SDU PREMIUM':
            case 'DSUPREMIUM':
            case 'DSU PREMIUM':
            case 'UKRDSU PREMIUM':
            case 'DSRU PREMIUM':
            case 'DSLPREMIUM':
            case 'DSL PREMIUM':
            case 'UKRDSL PREMIUM':


            case 'PPDS PREMIUM':
            case 'PPDSPREMIUM':
            case 'PPDSUPREMIUM':
            case 'PPDSU PREMIUM':
            case 'PPDSLPREMIUM':
            case 'PPDSL PREMIUM':

            case 'PMDDS PREMIUM':
            case 'PMDDSPREMIUM':
            case 'PMDDSUPREMIUM':
            case 'PMDDSU PREMIUM':
            case 'PMDDSLPREMIUM':
            case 'PMDDSL PREMIUM':

            case '5PMDS PREMIUM':
            case '5PMDSPREMIUM':
            case '5PMDSUPREMIUM':
            case '5PMDSU PREMIUM':
            case '5PMDSLPREMIUM':
            case '5PMDSL PREMIUM':

            case 'PMD5DS PREMIUM':
            case 'PMD5DSPREMIUM':
            case 'PMD5DSUPREMIUM':
            case 'PMD5DSU PREMIUM':
            case 'PMD5DSLPREMIUM':
            case 'PMD5DSL PREMIUM':
                
            case 'PPDSG PREMIUM':
            case 'PPDSGPREMIUM':
            case 'PMDDSG PREMIUM':
            case 'PMDDSGPREMIUM':
                $username = DSMessage::wordProcess(trim($this->header[1]));
                //var_dump($this->header);

                $userID = $this->getUserIDByUserName($username);
                if( !$userID )
                {
                    $this->response_statusPM = -1;
                    return false;
                }

                // Если смс украинские
                if ($this->shortcode == '5373') {
                    $countOfDays = 8;
                } else {
                    $countOfDays = 10;
                }

                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $sms_pay = new SMSPay($userID, $this->phone, $this->smsText, $countOfDays, $this->testMode);

                    $this->messageText = false;

                    $this->response_text = SMS_Localization::Localize($sms_pay->getResult());
                } else {
                    $this->response_text = SMS_Localization::Localize('{PROCCESSED}');
                }
                $this->response_statusPM = 1;
                $this->errorParams = true;
                $this->resultOk = true;

                $this->sendResponse();

                // Для биллинга
                $this->good = 'PREMIUM';
                $this->sum = '136';
                $this->username = $username;

                break;



            case 'SDARMY':
            case 'SD ARMY':
            case 'DSARMY':
            case 'DS ARMY':
            case 'UKRDS ARMY':
            case 'DSR ARMY':
            case 'SDUARMY':
            case 'SDU ARMY':
            case 'DSUARMY':
            case 'DSU ARMY':
            case 'UKRDSU ARMY':
            case 'DSRU ARMY':
            case 'DSLARMY':
            case 'DSL ARMY':
            case 'UKRDSL ARMY':

            case 'PPDS ARMY':
            case 'PPDSARMY':
            case 'PPDSUARMY':
            case 'PPDSU ARMY':
            case 'PPDSLARMY':
            case 'PPDSL ARMY':

            case 'PMDDS ARMY':
            case 'PMDDSARMY':
            case 'PMDDSUARMY':
            case 'PMDDSU ARMY':
            case 'PMDDSLARMY':
            case 'PMDDSL ARMY':

            case '1PMDS ARMY':
            case '1PMDSARMY':
            case '1PMDSUARMY':
            case '1PMDSU ARMY':
            case '1PMDSLARMY':
            case '1PMDSL ARMY':

            case 'PMD1DS ARMY':
            case 'PMD1DSARMY':
            case 'PMD1DSUARMY':
            case 'PMD1DSU ARMY':
            case 'PMD1DSLARMY':
            case 'PMD1DSL ARMY':
                
            case 'PPDSG ARMY':
            case 'PPDSGARMY':
            case 'PMDDSG ARMY':
            case 'PMDDSGARMY':

                $username = DSMessage::wordProcess($this->header[1]);
                $password = $this->header[2];
                $world = $this->header[3];

                if(!$this->checkWorldID($world) )
                {
                    return false;
                }

                if( !$this->getUserInstanceByLoginAndPassword($username, $password) )
                {
                    return false;
                }
                /*
                if (strtoupper($this->worldName) == 'STR' || strtoupper($this->worldName) == 'VDL' || strtoupper($this->worldName) == 'EDR')
                {
                $dsarmy = new GetDSArmy('new');
                $this->response_text = $dsarmy->getText($this->userInstanceFrom);
                } else {

                }
                */
                // Обрабатываем смс (если смс уже оплачена)
                if ($this->paid == 'YES')
                {
                    $dsarmy = new GetDSArmy();
                    $this->response_text = SMS_Localization::Localize($dsarmy->getText($this->userInstanceFrom));
                } else {
                    $this->response_text = SMS_Localization::Localize('{PROCCESSED}');
                }

                $this->response_statusPM = 1;
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                // Для биллинга
                $this->good = 'ARMY';
                $this->sum = '35';
                $this->username = $username;

                break;



            case 'DS BEAUTY':
            case 'UKRDS BEAUTY':
            case 'DSR BEAUTY':
            case 'DSBEAUTY':
            case 'SD BEAUTY':
            case 'SDBEAUTY':

                $msg = trim(str_replace('TRIGGER', '', $this->msgText));
                $url = "http://beauty.destinysphere.ru/gate/dsbeauty.php?phone=".$this->incomingData['phone']."&msg=".$msg;

                $this->getContent->setUrl($url);
                $this->getContent->setMethod("GET");
                $this->getContent->Start();

                $this->response_statusPM = 1;
                $text = $this->getContent->GetResponseContent();

                $this->response_text = $text;
                $this->errorParams = true;
                $this->sendResponse();

                break;

            case 'DS VOTE':
            case 'UKRDS VOTE':
                //$world = $this->header[1];

                $this->response_statusPM = 1;
                $this->response_text = 'Спасибо. Ваш голос учтен.';
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                break;

            case 'DS OTVET':
            case 'UKRDS OTVET':
                //$username = $this->header[1];
                //$answer = $this->header[2];

                $this->response_statusPM = 1;
                $this->response_text = 'Vash otvet prinyat';
                $this->errorParams = true;
                $this->resultOk = true;
                $this->sendResponse();

                break;




            case 'DSMSGG':

                break;
            case 'DSGLOBAL':
                break;
            case 'DSMSGA':

                $usernameFrom = $this->header[1];
                $password = $this->header[2];
                $world = $this->header[3];
                $alliance = $this->header[4];
                $this->messageScope = 4;

                $this->messageText = $this->body;
                $this->messageSubject = 'Вам пришло альянсное СМС сообщение';
                if(!$this->checkWorldID($world) )
                {
                    return false;
                }
                $this->recipient = false;
                if( !$this->getUserInstanceByLoginAndPassword($usernameFrom, $password) )
                {
                    return false;
                }

                if( !$this->getUserInstanceFromUIIDFrom($alliance) )
                {
                    return false;
                }

                break;
            case 'DSMSGR':
                break;


            case 'DSSMS':
                $usernameFrom = $this->header[1];
                $password = $this->header[2];
                $world = $this->header[3];
                $usernameTo = $this->header[4];
                $this->messageScope = 1;

                $this->messageText = $this->body;
                $this->messageSubject = 'Вам SMS сообщение от игрока '.$usernameFrom;
                if(!$this->checkWorldID($world) )
                {
                    return false;
                }

                if( !$this->getUserInstanceByLoginAndPassword($usernameFrom, $password) )
                {

                    return false;
                }

                if( !$this->getUserInstanceByLogin($usernameTo) )
                {
                    return false;
                }

                $this->messageParcipiants[] = $this->userInstanceTo;
                $this->log .= "OFFLINE = ".var_export($this->offline,1)."\n";
                if ($this->offline == false)
                {
                    if (!$this->sendOnSMS($usernameFrom, $usernameTo))
                    {

                    }
                }
                $this->response_statusPM = 1;
                $this->errorParams = true;
                break;


            case 'DSMSG':

                $usernameFrom = $this->header[1];
                $password = $this->header[2];
                $world = $this->header[3];
                $usernameTo = $this->header[4];
                $this->messageScope = 1;

                $this->messageText = $this->body;
                $this->messageSubject = 'Вам SMS сообщение от игрока '.$usernameFrom;
                if(!$this->checkWorldID($world) )
                {
                    return false;
                }

                if( !$this->getUserInstanceByLoginAndPassword($usernameFrom, $password) )
                {

                    return false;
                }

                if( !$this->getUserInstanceByLogin($usernameTo) )
                {
                    return false;
                }

                $this->messageParcipiants[] = $this->userInstanceTo;
                $this->errorParams = true;
                break;


            case 'ON DSREG':
                $username = $this->header[1];
                $password = $this->header[2];

                require_once('./dsregistration.php');
                $dsregistration = new SMSRegistration();
                $this->response_status = $dsregistration->getResponseStatus();
                $this->response_text = $dsregistration->getResponseText();
                $this->errorParams = true;
                $this->sendResponse();

                break;


            case 'DS RING':
            case 'UKRDS RING':
            case 'DSRING':
                $world = $this->header[1];
                $ringFrom = $this->header[2];
                $complexFrom = $this->header[3];

                $url = "http://www.destinysphere.ru/ds2/ehoro.php?sms&world=".$world."&fromr=".$ringFrom."&fromc=".$complexFrom."&msk=2";
                $this->getContent->setUrl($url);
                $this->getContent->setMethod("GET");
                //$this->getContent->setPostVar("phone", 'A');
                //$this->getContent->ShowHeaders();
                $this->getContent->Start();

                $this->messageText = false;

                $this->response_statusPM = 1;

                $this->response_text = $this->getContent->GetResponseContent();
                $this->errorParams = true;
                $this->sendResponse();
                break;


            case 'DSPLAY':
            case 'DSPLAYXXX':
                $username = $this->header[1];

                $this->response_status = 0;
                if( !$this->getUserIDByUserName($username) )
                {
                    $this->response_statusPM = -1;
                    //$this->response_text = 'Для участия в викторине укажите свой логин!';
                    return false;
                } else {
                    $getContent = new GetContent();
                    /*
                    $message = "Podskazka: Pravilnie otveti na voprosi: 7 vopros: 2 a na 23 vopros: 3";
                    $getContent->setMethod("GET");
                    $getContent->setUrl($this->response_url."?phone=".urlencode($this->phone)."&shortcode=".urlencode($this->shortcode)."&group=".urlencode($this->group)."&message=".urlencode($message)."");
                    $getContent->Start();
                    */

                    $url = "http://sms.destinysphere.ru/gate/smscom.php?login=".$username."&phone=".$this->phone."&shortcode=".$this->shortcode."&group=".$this->group."&trans_id=".$this->trans_id."";
                    $this->getContent->setMethod("GET");
                    $this->getContent->setUrl($url);

                    $this->getContent->Start();

                    $this->response_status = false;

                    $this->response_text = $this->getContent->GetResponseContent();
                }

                $this->response_statusPM = 1;
                $this->errorParams = true;
                $this->sendResponse();
                break;

            default:
                $this->errorParams = true;
                $this->response_text = SMS_Localization::Get('UNKNOWN_TYPE');
                $this->sendResponse();
                return false;
                break;
        }

        $this->sendMSG();

        if(empty($username))
            $username = 'sms_default';

        if(!key_exists($this->trigger, $this->priceFunction))
            return false;

        /*
        try {
            new SMSPayBillingLogger($username, $this->priceFunction[$this->trigger], $this->phone);
        }catch (Exception $e){
            return false;
        }
        */


        return true;
    }

}


if(file_exists('../GAMEdown') || filesize('messages.cvs') > 50)
{
    if (filesize('messages.cvs') > 0)
    {
        $files = new File('messages.cvs', 'r');
        $files->lock();
        $file = $files->getInArray();
        $newFile = $file;

        foreach ($file as $key => $value)
        {
            //echo strlen($value)."<br>";
            if (strlen($value) > 1)
            {
                $z = new DSMessage(unserialize($value) );
                if ($z->getResult() == true)
                {
                    unset($newFile[$key]);
                }
            } else {
                unset($newFile[$key]);
            }
        }
        //echo count($newFile)."<br><br>".count($file)."<br><br><br>";
        if(count($newFile) < count($file))
        {
            //unlink('messages.cvs');
            //echo "unlink!";
            $fp = fopen('messages.cvs','w+');
            fwrite($fp,implode("",$newFile));
            fclose($fp);
        }

    }

    if (sizeof($_REQUEST) > 0)
    {
        $z = new DSMessage();
    }

    die();
} else {
    $z = new DSMessage();
}

?>