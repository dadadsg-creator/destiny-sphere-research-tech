<?php
// Скрипт авторизации для чата
// Возвращает по SID данные об игроке

require_once ('Main.php');
//SU_DebugTools::LogAll('CHAT', 'AnnitaChat.log');


$sido = '';
//SU_DebugTools::LogAll($_GET, 'AnnitaChat.log');
if (key_exists('PHPSESSID', $_GET)) {
    $sido = pg_escape_string($_GET['PHPSESSID']);   
}
if (key_exists('VERS', $_GET)) {
    $version = pg_escape_string($_GET['VERS']); 
}

// Тестовый вывод
if ($sido == 777) {
    echo "1\nTestUser\nWorldTest\nAllianceTest\n1\n1\n";
    exit;
}

// Если сид не пришел, или пришел неккорректный 
if (empty($sido)) {
    echo "Sid не передан";
    exit;
}

// Если у нас версия, отличная от данного сервера
if (constant('VERSION_NAME') != $version) {
    switch ($version)
    {
        case 'LOCAL':
            $url = 'http://unlim/ds/chatAuthorize.php';
            break;
        case 'RU_UNLIM':
            $url = 'http://ds-game.ru/ds/chatAuthorize.php';
            break;
        case 'RU_CLASSIC':
            $url = 'http://destinysphere.ru/ds/chatAuthorize.php';
            break;
        case 'LITHUANIA':
            $url = 'http://lt.destinysphere.ru/ds/chatAuthorize.php';
            break;
        case 'LIMITED':
            $url = 'http://www.ds-game.su/ds/chatAuthorize.php';
            break;
        case 'TEST':
            $url = 'http://test.destinysphere.ru/ds/chatAuthorize.php';
            break;
        case 'DE_TEMP':
            $url = 'http://alpha.destinysphere.ru/ds/chatAuthorize.php';
            break;
        default:
            echo "Неверная версия";
            exit;
    }

//SU_DebugTools::LogAll('Редирект на '.$url, 'AnnitaChat.log');
    
    
        $options = array(
        'method' => 'GET',
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'user_agent' => '',
        'content' => $content,
        'proxy' => '',
        'request_fulluri' => '',
        'max_redirects' => ''
        );

        $data = array('PHPSESSID' => $sido, 'VERS' => $version);
        $content = http_build_query($data);
        $opts = array('http' => $options);

        $context  = stream_context_create($opts);

        $result = file_get_contents($url."?".$content, false, $context);

        // Шлем ответ
        echo $result;
//SU_DebugTools::LogAll($result, 'AnnitaChat.log');
        
    exit;
}



SU_GameSessionWork::SessionContinue($sido);

/*echo "<pre>";
print_r($_SESSION);
echo "<br>END<br>";*/

// Проверяем, есть ли в сессии все нужные данные
if (!key_exists('uid', $_SESSION) || !key_exists('world', $_SESSION) || !key_exists('dbId', $_SESSION)) {
    echo "Sid несуществует";
    exit;
}

$dbId = (int)$_SESSION['dbId'];
assert($dbId >= 0);
$userId = (int)$_SESSION['uid'];
assert($userId > 0);
$worldId = (int)$_SESSION['world'];
assert($worldId > 0);

// Выбираем данные из базы
$common = DS_Common::CreateForCurentDB($dbId);

$sql ="
                SELECT 
                    u.username,
                    w.worldname,
                    COALESCE(a.alliancename, '') as alliancename,
                    COALESCE(uhas.id_status, 0) as leader
                FROM 
                    ds_user u
                LEFT JOIN ds_userinstance ui ON (ui.id_user = u.id_user)
                LEFT JOIN ds_world w ON (w.id_world = ui.id_world)
                LEFT JOIN ds_userhasalliance uha ON (uha.id_userinstance = ui.id_userinstance)
                LEFT JOIN ds_alliance a ON (uha.id_alliance = a.id_alliance)
                LEFT JOIN ds_userhasalliancestatus uhas ON (uha.id_userinstance = uhas.id_userinstance)
                WHERE
                u.id_user = ".$userId."
                AND ui.id_world =  ".$worldId;

                $res = $common->getResultArray ( "", $sql, "", "", "" );
                if($res === false)
                {
                    echo "Игрок не найден";
                    exit;
                }
                
$userName = $res[0]['username'];
$world = $res[0]['worldname'];
$alliance = $res[0]['alliancename'];
$allianceLeader = $res[0]['leader'];

$admin = 0;
$sql ="
                SELECT 
                    *
                FROM
                    ds_chat_admin
                WHERE 
                    id_user = $userId";
$res = $common->getResultArray ( "", $sql, "", "", "" );

if(count($res) > 0)
{
    $admin = 1;
}

/*if ($userName == 'dark768') {
    $alliance = 'TestAll';
    $allianceLeader = 1;
    $admin = 1;
}*/
//SU_DebugTools::LogAll("$userId\n$userName\n$world\n$alliance\n$allianceLeader\n$admin\n", 'AnnitaChat.log');

echo iconv('cp1251', 'utf8', "$userId\n$userName\n$world\n$alliance\n$allianceLeader\n$admin\n");

?>