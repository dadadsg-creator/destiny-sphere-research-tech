<?
 /**
   Общий конфигурационный файл
 */

  	/** Лог файлы */
	define ("LOG_PATH", $_SERVER["DOCUMENT_ROOT"]."/logs/");
  	define ("ERROR_LOG_NAME", "error.log");
  	define ("DB_LOG_NAME", "db.log");

	define("SITE_URL", "http://www.seafood.su");
	define("SITE_NAME", "Seafood");
	define("SUBSCRIBE_MAIL", "info@belinea.rus.net" );
    define('SUBSCRIBE_NAME_FROM', "Seafood");
    define('SUBSCRIBE_TITLE', "Подписка на рассылку на новостей");
	
    define('DB_HOST', constant ("DBHostName") );
    define('DB_USER', constant ("DBUserName") );
    define('DB_PASS', constant ("DBPassword") );
    define('DB_NAME', constant ("DBName") );

	/** Описание параметров */
	global $systemParameters;
	$systemParameters = array(
        "pubId" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "pubMode" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "pubAction" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "yearPub" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "monthPub" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
   		"Action" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "N", "SessionRewrite" => "N", "PageOutReset" => "N"),
        "Id" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "product_id" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "page_id" => array("Type" => "string", "Default" => "1", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "order" => array("Type" => "mixed", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
        "classListAllias" => array("Type" => "string", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "N"),

        "searchWords" => array("Type" => "mixed", "Default" => "", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "N"),

        "productStatusType" => array("Type" => "mixed", "Default" => "1", "Method" => "Request",
 			"Session" => "Y", "SessionRewrite" => "Y", "PageOutReset" => "Y"),
	);


?>