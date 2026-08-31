<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if(!isset($_POST['from_date']) || !preg_match("/^\d{2}\.\d{2}\.\d{4}$/", $_POST['from_date'])) $_POST['from_date'] = date("d.m.Y", mktime(0, 0, 0, date("m")-1));
if(!isset($_POST['to_date']) || !preg_match("/^\d{2}\.\d{2}\.\d{4}\$/", $_POST['to_date'])) $_POST['to_date'] = date("d.m.Y");

if(!isset($_POST['from_time']) || !preg_match("/^\d{2}:\d{2}$/", $_POST['from_time'])) $_POST['from_time'] = date("H:i");
if(!isset($_POST['to_time']) || !preg_match("/^\d{2}:\d{2}$/", $_POST['to_time'])) $_POST['to_time'] = date("H:i");
?>

<style type="text/css">
    #restable{border-collapse:collapse;}
    #restable td, #restable th {background: #ddd; color: #000; border: 1px solid #000; padding: 3px 5px;}
    #restable th{background: #999}
</style>

<div style="color:#FFF">
    <form method='post' action='<?=$_SERVER["PHP_SELF"]?>'>
        Date from:
        <input size="11" type="text" name="from_date" value="<?php echo $_POST['from_date'] ?>">
        <input size="5" type="text" name="from_time" value="<?php echo $_POST['from_time'] ?>">
        to
        <input size="11" type="text" name="to_date" value="<?php echo $_POST['to_date'] ?>">
        <input size="5" type="text" name="to_time" value="<?php echo $_POST['to_time'] ?>">
        <input type="checkbox" name="mail_confirmed" id="mail_confirmed" value="1" <?php echo isset($_POST['mail_confirmed']) && $_POST['mail_confirmed'] ==1 ? "checked='checked'" : "" ?>>
        <label for="mail_confirmed">E-mail confirmed</label>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <input type="submit">
    </form>
</div>

<?php

list($fd, $fm, $fY) = explode(".", $_POST['from_date']);
list($td, $tm, $tY) = explode(".", $_POST['to_date']);
list($fH, $fi) = explode(":", $_POST['from_time']);
list($tH, $ti) = explode(":", $_POST['to_time']);

$sql = "SELECT username, email, to_char(registrationdate, 'dd.mm.yyyy HH24:MI') AS regdate, activate_mail, from_partner
        FROM ds_user
        WHERE 
        registrationdate >= '{$fY}-{$fm}-{$fd} {$fH}:{$fi}' AND registrationdate <= '{$tY}-{$tm}-{$td} {$tH}:{$ti}'
        ".(isset($_POST['mail_confirmed']) && $_POST['mail_confirmed'] ==1 ? " AND activate_mail = 1" : "")."
        ORDER BY registrationdate DESC";
$common = & ClassFactory::getInstance ( "DS_Common" );
$common->setDbId(0);
$res = $common->getResultArray ("", $sql);
if ( $common->errormsg ) {
    die ( "sql error" . $res->getMessage());
}

?>

<table id="restable">
    <tr>
        <th>&nbsp;</th>
        <th>Username</th>
        <th>Reg DateTime</th>
        <th>E-Mail</th>
        <th>E-mail Confirmed</th>
        <th>Owner</th>
    </tr>
    <?php
    $partners = array(0 => array("DS", "gray"), 1 => array("Rambler", "blue"));
    $DSCounter = 0;
    $RamblerCounter = 0;
    $counter = 1;
    foreach ($res as $row) {
        if($row['from_partner'] == '') $row['from_partner'] = 0;
        if($row['from_partner'] == 1) $RamblerCounter++;
        if($row['from_partner'] == 0) $DSCounter++;
        echo "<tr>
                <th>".($counter++)."</th>
                <td><b>{$row['username']}</b></td>
                <td>{$row['regdate']}</td>
                <td>{$row['email']}</td>
                <td>".($row['activate_mail'] ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No</span>")."</td>
                <td><span style='color:{$partners[$row['from_partner']][1]}'>{$partners[$row['from_partner']][0]}</span></td>
              </tr>";
    }
    ?>
</table>

<p style="color:white">
<?php
echo "DS: ".$DSCounter."<br> Rambler: ".$RamblerCounter;
?>
</p>
<br><br>
