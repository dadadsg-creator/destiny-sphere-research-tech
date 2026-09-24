<?
// Анлим
if ($_SERVER['SERVER_ADDR'] == '94.242.60.31') {
    echo 'xmldata=<?xml version="1.0" encoding="utf-8"?>
    <address chathelp="http://test.destinysphere.ru/ds/help/?name=FIRST_STEP%23chat">
    <links CABINET="user_cabinet.php?action=login" ranking="ranking.php?SID=SIDID" profile="/ds/index_profile.php" forum="http://forum.ds-game.ru/" portal="http://ds-game.ru/"/>
    <interface sms="/ds/sms.php" premium="http://ds-game.ru/index.php?p=premium_info_page" service="/ds/billing.php?SID=SIDID" news="http://news.destinysphere.ru"  getpremium="/ds/billing.php?SID=SIDID%26mode=prem" />

    </address>';
}
// Пульсар
else if ($_SERVER['SERVER_ADDR'] == '94.242.60.32') {
    echo 'xmldata=<?xml version="1.0" encoding="utf-8"?>
<address chathelp="http://test.destinysphere.ru/ds/help/?name=FIRST_STEP%23chat">
<links CABINET="user_cabinet.php?action=login" ranking="ranking.php?SID=SIDID" profile="/ds/index_profile.php" forum="http://forum.ds-game.ru/viewforum.php?f=78" portal="http://ds-game.su/"/>
<interface sms="/ds/sms.php" premium="http://destinysphere.ru/index.php?p=premium_info_page" service="/ds/billing.php?SID=SIDID" news="http://news.destinysphere.ru"  getpremium="/ds/billing.php?SID=SIDID%26mode=prem" />
</address>';
}
// Классика
else if ($_SERVER['SERVER_ADDR'] == '94.242.60.30') {
    echo 'xmldata=<?xml version="1.0" encoding="utf-8"?>
<address chathelp="http://test.destinysphere.ru/ds/help/?name=FIRST_STEP%23chat">
<links CABINET="user_cabinet.php?action=login" ranking="ranking.php?SID=SIDID" profile="/ds/index_profile.php" forum="https://forum.destinysphere.ru/" portal="http://destinysphere.ru/"/>
<interface sms="/ds/sms.php" premium="http://destinysphere.ru/index.php?p=premium_info_page" service="/ds/billing.php?SID=SIDID" news="http://news.destinysphere.ru"  getpremium="/ds/billing.php?SID=SIDID%26mode=prem" />
</address>';
}

?>
