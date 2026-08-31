<?
foreach ($_REQUEST as $key=>$val) $$key=$val;
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";

if (isset($ID)) {
    switch (strval($mode)) {
        case "image":   headerLocation("/Admin/controls/image/edit.php?ID=$ID");
        break;
        case "text":    headerLocation("/Admin/SimEdit/SimEdit.php?content_id=$ID");
        break;
        case "banner":  headerLocation("/Admin/controls/bannerwtext/edit.php?ID=$ID");
        break;
        case "DynComponent":    headerLocation("/Admin/superadmin/DynPHPContent/edit.php?ID=$ID");
        break;
        default:
            $ErrMsg = "Не определен скрипт для обработкм блока \"<b>$mode</b>\"";
        break;
    }
} else $ErrMsg = "Не задан идентификатор элемента";
?>

<html>
<head>
    <title>Административная консоль</title>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
</head>
<body>

<br>
<font color=#EFBA00>Ошибка:</font>
<br><br>

<?=$ErrMsg?>

</body>
</html>