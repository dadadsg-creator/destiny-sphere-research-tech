<?
// Анлим
if ($_SERVER['SERVER_ADDR'] == '94.242.60.31') {
    echo 'xmldata=<?xml version="1.0" encoding="utf-8"?>
    <address chathelp="http://test.destinysphere.ru/ds/help/?name=FIRST_STEP%23chat">
    <links CABINET="user_cabinet.php?action=login" analizator="http://stat.destinysphere.ru/?world=2%26_sid=SIDID" ranking="ranking.php?SID=SIDID" profile="/ds/index_profile.php" forum="http://forum.ds-game.ru/" portal="http://ds-game.ru/" GAMES="SCROLL"/>
    <interface sms="/ds/sms.php" premium="http://ds-game.ru/index.php?p=premium_info_page" service="/ds/billing.php?SID=SIDID" news="http://news.destinysphere.ru"  getpremium="/ds/billing.php?SID=SIDID%26mode=prem" />

    <game><item name="EVILWARRIOR" url="http://www.destinysphere.ru/mini-ds/EvilWarrior2.swf" width="800" height="600"/><item name="BLACKJACK" url="/ds/minigames/bj/bj.php" width="600" height="500"/></game>
    </address>';
}
// Пульсар
else if ($_SERVER['SERVER_ADDR'] == '94.242.60.32') {
    echo 'xmldata=<?xml version="1.0" encoding="utf-8"?>
<address chathelp="http://test.destinysphere.ru/ds/help/?name=FIRST_STEP%23chat">
<links CABINET="user_cabinet.php?action=login" analizator="http://stat.destinysphere.ru/?world=5%26_sid=SIDID" ranking="ranking.php?SID=SIDID" profile="/ds/index_profile.php" forum="http://forum.ds-game.ru/viewforum.php?f=78" portal="http://ds-game.su/" GAMES="SCROLL"/>
<interface sms="/ds/sms.php" premium="http://destinysphere.ru/index.php?p=premium_info_page" service="/ds/billing.php?SID=SIDID" news="http://news.destinysphere.ru"  getpremium="/ds/billing.php?SID=SIDID%26mode=prem" />
<game><item name="EVILWARRIOR" url="http://www.destinysphere.ru/mini-ds/EvilWarrior2.swf" width="800" height="600"/><item name="BLACKJACK" url="/ds/minigames/bj/bj.php" width="600" height="500"/></game>
</address>';
}
// Классика
else if ($_SERVER['SERVER_ADDR'] == '94.242.60.30') {
    echo 'xmldata=<?xml version="1.0" encoding="utf-8"?>
<address chathelp="http://test.destinysphere.ru/ds/help/?name=FIRST_STEP%23chat">
<links CABINET="user_cabinet.php?action=login" analizator="http://stat.destinysphere.ru/?world=3%26_sid=SIDID" ranking="ranking.php?SID=SIDID" profile="/ds/index_profile.php" forum="http://forum.destinysphere.net" portal="http://destinysphere.ru/" GAMES="SCROLL"/>
<interface sms="/ds/sms.php" premium="http://destinysphere.ru/index.php?p=premium_info_page" service="/ds/billing.php?SID=SIDID" news="http://news.destinysphere.ru"  getpremium="/ds/billing.php?SID=SIDID%26mode=prem" />
<game><item name="EVILWARRIOR" url="http://www.destinysphere.ru/mini-ds/EvilWarrior2.swf" width="800" height="600"/><item name="BLACKJACK" url="/ds/minigames/bj/bj.php" width="600" height="500"/></game>
</address>';
}

?>
