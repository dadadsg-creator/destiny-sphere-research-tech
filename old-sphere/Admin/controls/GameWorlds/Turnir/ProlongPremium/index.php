<?
ini_set('memory_limit', '16M');
set_time_limit(0);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

if (stripos($_SERVER['HTTP_HOST'], 'ds-game.su')===false) return;

$common = DS_Common::CreateForCurentDB(0);

if (isset($_POST['startDate'])) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['endDate']) &&
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['startDate'])) {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
        $sql = "UPDATE ds_userprofidata 
            SET enddate = enddate + (timestamp '$startDate' - timestamp '$endDate')
            WHERE enddate > '$endDate'";
        $common->executeQuery($sql);
        echo '<font color=#F8F8FF><b>Premium prolonged. Please don\'t execute this procedure twice.</b></font>';
    }
    else echo '<font color=#F8F8FF><b>Wrong date format</b></font>';
}
else {
    echo '
    <form action="" method="POST">
    <input type="hidden" name="action" value="showStat">
    <table border=0>
        <tr>
            <td><font color=#F8F8FF><b>Previous turnir ended (YYYY-MM-DD):</b></font></td>
            <td><input name="endDate"></td>
        </tr>
        <tr>
            <td><font color=#F8F8FF><b>New turnir starts (YYYY-MM-DD):</b></font></td>
            <td><input name="startDate"></td>
        </tr>
    </table>
    <input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Prolong premium&nbsp;&nbsp;&nbsp;&nbsp;">
    </form>';
}


?>